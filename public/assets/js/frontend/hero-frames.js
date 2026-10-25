/**
 * Scroll-controlled frame sequences for the homepage hero slider.
 *
 * Each slide has its own canvas + frame sequence. One scroll position drives
 * all of them: scrolling down plays the frames forward, scrolling up plays them
 * backward. The slider itself (autoplay, swipe, keyboard) lives in index.js; it
 * is told to hold autoplay while the animation is in progress.
 *
 * Landscape: the stage is CSS-sticky inside the tall hero section, so it stays
 * on screen until the last frame without leaving the scroll container (wheel,
 * two-finger trackpad and touch scrolling keep working over it).
 * Portrait: the stage is the headline plus the frame right under it; it is not
 * pinned, and the sequence finishes as the headline scrolls away.
 *
 * Frames are never cropped, and the frame's own background is extended into
 * the rest of the stage so there are no visible edges.
 *
 * Performance: frames are fetched once as compressed blobs and decoded off the
 * main thread (createImageBitmap) at exactly the size they are drawn, so each
 * scroll step is a cheap 1:1 copy. Only frames around the current position are
 * kept decoded, within a memory budget that favours the visible slide.
 */
(function () {
    "use strict";

    var GAP = 16;
    var FETCH_WORKERS = 6;
    // Few parallel decodes: more of them compete with scrolling for the CPU (measured:
    // 3 keeps the animation at the page's normal scroll frame rate on a laptop).
    var DECODE_WORKERS = 3;
    var MIN_KEEP = 8;

    function createSequence(slide, hooks) {
        var canvas = slide.querySelector("canvas");
        var ctx = canvas.getContext("2d", { alpha: false });
        var path = canvas.dataset.framePath;
        var ext = canvas.dataset.frameExt || ".webp";
        var count = parseInt(canvas.dataset.frameCount, 10) || 0;
        var IW = parseInt(canvas.dataset.frameWidth, 10) || 1280;
        var IH = parseInt(canvas.dataset.frameHeight, 10) || 720;

        var blobs = new Array(count);      // compressed frames
        var bitmaps = new Array(count);    // decoded frames at the current draw size
        var pending = new Array(count);    // decode in flight (holds the layout generation)
        var cleanEdge = new Array(count);  // frame's side columns are plain background
        var edgeDone = new Array(count);   // edge strips captured for this generation
        var edgeBase = new Array(count);   // plain background colour at each side's top: [left, right]

        var target = 0;
        var direction = 1;
        var speed = 0;
        var keep = MIN_KEEP;
        var decoding = 0;
        var gen = 0;
        var drawnIndex = -1;
        var rafId = 0;
        var L = null;                      // current layout, canvas px

        // Edge strips per frame: the side columns (colAtlas), used to extend the frame's
        // background sideways. Each is stored 3px thick and sampled from the middle, so
        // stretching a strip (which blends in neighbouring pixels) never picks up another.
        var colAtlas = document.createElement("canvas");
        var colCtx = colAtlas.getContext("2d");
        // Above the frame: the frame's top row stretched upward (rowAtlas, also 3px thick).
        // The row is cleaned first: anything bright touching the top edge (the smartphone
        // video's studio lights) is replaced by the background beside it, and the row is
        // smoothed sideways so their remaining glow can't stretch into sharp streaks.
        var rowAtlas = document.createElement("canvas");
        var rowCtx = rowAtlas.getContext("2d");
        var probe = document.createElement("canvas").getContext("2d", { willReadFrequently: true });
        // Blend strip for the side seams: the background band is flat while the frame
        // has grain and a gentle lighting falloff, which reads as a line where they meet,
        // so the band fades into the frame over its outer few pixels (no parts are ever
        // that close to the edge: the nearest is 38px in from a side at full size).
        var feather = document.createElement("canvas");
        var featherCtx = feather.getContext("2d");
        var featherTop = document.createElement("canvas");
        var featherTopCtx = featherTop.getContext("2d");
        var FEATHER = 32 / 1280; // fraction of the frame width
        // Edge strips are read back from the decoded frames, which is too costly to do
        // while scrolling; it runs in idle time (bands borrow a neighbour's strip meanwhile).
        var edgeQueue = [];
        var edgeCount = 0;
        var edgeIdle = 0;
        var whenIdle = window.requestIdleCallback
            ? function (cb) { return window.requestIdleCallback(cb, { timeout: 1000 }); }
            : function (cb) { return setTimeout(cb, 60); };

        function frameUrl(i) {
            return path + String(i + 1).padStart(4, "0") + ext;
        }

        // area: free space for the frame in CSS px ({x, y} offsets inside the stage).
        function resize(area, budgetBytes) {
            var cssW = canvas.clientWidth;
            var cssH = canvas.clientHeight;
            var aw = Math.max(1, cssW - area.x);
            var ah = Math.max(1, cssH - area.y);
            var scaleCss = Math.min(aw / IW, ah / IH);
            // Never render the canvas sharper than the source frames: no quality gain, much more work.
            var dpr = Math.max(1, Math.min(window.devicePixelRatio || 1, 2, 1 / scaleCss));
            var next = {
                cw: Math.round(cssW * dpr),
                ch: Math.round(cssH * dpr),
                dw: Math.min(IW, Math.round(IW * scaleCss * dpr)),
                dh: Math.min(IH, Math.round(IH * scaleCss * dpr))
            };
            next.dx = Math.round(area.x * dpr + (aw * dpr - next.dw) / 2);
            // Sit on the bottom edge: the frame's floor reflection is its brightest edge.
            next.dy = next.ch - next.dh;

            var sizeChanged = !L || next.dw !== L.dw || next.dh !== L.dh;
            L = next;
            if (canvas.width !== L.cw || canvas.height !== L.ch) {
                canvas.width = L.cw;
                canvas.height = L.ch;
            }
            if (sizeChanged) {
                gen++;
                for (var i = 0; i < count; i++) {
                    if (bitmaps[i]) bitmaps[i].close();
                    bitmaps[i] = null;
                    edgeDone[i] = false;
                }
                edgeQueue = [];
                edgeCount = 0;
                colAtlas.width = 6 * count;
                colAtlas.height = L.dh;
                rowAtlas.width = L.dw;
                rowAtlas.height = 3 * count;

            }
            setBudget(budgetBytes);
            drawnIndex = -1;
            render();
        }

        function setBudget(bytes) {
            if (!L) return;
            keep = Math.max(MIN_KEEP, Math.min(count, Math.floor(bytes / (L.dw * L.dh * 4))));
            schedule();
        }

        function captureEdges(i, bmp) {
            var w = bmp.width;
            var h = bmp.height;
            if (probe.canvas.width !== w || probe.canvas.height !== h) {
                probe.canvas.width = w;
                probe.canvas.height = h;
            }
            probe.drawImage(bmp, 0, 0, 1, h, 0, 0, 1, h);
            probe.drawImage(bmp, w - 1, 0, 1, h, 1, 0, 1, h);
            var cols = probe.getImageData(0, 0, 2, h);
            probe.drawImage(bmp, 0, 1, w, 1, 0, h - 1, w, 1);
            // Some frames have parts touching the side edges; stretching those would streak.
            // Parts show up as sharp jumps along the edge column, while plain background
            // (even a bright floor) is a smooth gradient.
            var data = cols.data;
            var jump = 0;
            for (var x = 0; x < 2; x++) {
                for (var y = 3; y < h; y++) {
                    var a = (y * 2 + x) * 4;
                    var b = ((y - 3) * 2 + x) * 4;
                    jump = Math.max(jump,
                        Math.abs(data[a] - data[b]),
                        Math.abs(data[a + 1] - data[b + 1]),
                        Math.abs(data[a + 2] - data[b + 2]));
                }
            }
            cleanEdge[i] = jump <= 25;
            // putImageData writes pixels immediately (canvas-to-canvas draws can be deferred
            // and lose the strip once the probe is reused for the next frame).
            var strips = colCtx.createImageData(6, h);
            for (var r = 0; r < h; r++) {
                for (var c = 0; c < 6; c++) {
                    var from = (r * 2 + (c < 3 ? 0 : 1)) * 4;
                    var to = (r * 6 + c) * 4;
                    strips.data[to] = data[from];
                    strips.data[to + 1] = data[from + 1];
                    strips.data[to + 2] = data[from + 2];
                    strips.data[to + 3] = 255;
                }
            }
            colCtx.putImageData(strips, 6 * i, 0);
            edgeBase[i] = [0, 1].map(function (x) {
                var o = (2 + x) * 4; // row 1
                return "rgb(" + data[o] + "," + data[o + 1] + "," + data[o + 2] + ")";
            });
            var row = probe.getImageData(0, h - 1, w, 1);
            cleanRow(row.data, w);
            for (var k = 0; k < 3; k++) rowCtx.putImageData(row, 0, 3 * i + k);
            edgeDone[i] = true;
            edgeCount++;
        }

        function cleanRow(d, w) {
            var BRIGHT = 70;
            var x = 0;
            // 1) Replace bright stretches by interpolating the background either side.
            while (x < w) {
                if (Math.max(d[x * 4], d[x * 4 + 1], d[x * 4 + 2]) <= BRIGHT) { x++; continue; }
                var start = x;
                while (x < w && Math.max(d[x * 4], d[x * 4 + 1], d[x * 4 + 2]) > BRIGHT) x++;
                var a = start > 0 ? (start - 1) * 4 : -1;
                var b = x < w ? x * 4 : -1;
                if (a < 0 && b < 0) break;
                for (var j = start; j < x; j++) {
                    var t = (j - start + 1) / (x - start + 1);
                    for (var ch = 0; ch < 3; ch++) {
                        var va = a >= 0 ? d[a + ch] : d[b + ch];
                        var vb = b >= 0 ? d[b + ch] : d[a + ch];
                        d[j * 4 + ch] = Math.round(va + (vb - va) * t);
                    }
                }
            }
            // 2) Smooth sideways (3 box-blur passes, edges clamped), so leftover glow
            //    becomes a gentle brightness change instead of streaks.
            var r = Math.max(2, Math.round(w * 0.12));
            var tmp = new Float32Array(w);
            var orig = d.slice();
            for (var pass = 0; pass < 3; pass++) {
                for (var c2 = 0; c2 < 3; c2++) {
                    var sum = 0;
                    for (var q = -r; q <= r; q++) sum += d[Math.min(w - 1, Math.max(0, q)) * 4 + c2];
                    for (var x2 = 0; x2 < w; x2++) {
                        tmp[x2] = sum / (2 * r + 1);
                        sum += d[Math.min(w - 1, x2 + r + 1) * 4 + c2] - d[Math.max(0, x2 - r) * 4 + c2];
                    }
                    for (var x3 = 0; x3 < w; x3++) d[x3 * 4 + c2] = Math.round(tmp[x3]);
                }
            }
            // 3) Keep the end pixels as they were, easing from them into the smoothed row,
            //    so the corners beside the frame still match the side strips exactly.
            var endL = [orig[0], orig[1], orig[2]];
            var endR = [orig[(w - 1) * 4], orig[(w - 1) * 4 + 1], orig[(w - 1) * 4 + 2]];
            for (var e2 = 0; e2 < r && e2 < w / 2; e2++) {
                var f = e2 / r;
                for (var c3 = 0; c3 < 3; c3++) {
                    var L1 = e2 * 4 + c3, R1 = (w - 1 - e2) * 4 + c3;
                    d[L1] = Math.round(endL[c3] * (1 - f) + d[L1] * f);
                    d[R1] = Math.round(endR[c3] * (1 - f) + d[R1] * f);
                }
            }
        }

        function queueEdges(i) {
            edgeQueue.push(i);
            if (!edgeIdle) edgeIdle = whenIdle(runEdges);
        }

        function runEdges(deadline) {
            edgeIdle = 0;
            // The frame on screen first: once it has its own strip, redraw so the side
            // bands match it exactly (while scrolling a neighbour's strip is close enough).
            if (drawnIndex >= 0 && !edgeDone[drawnIndex] && bitmaps[drawnIndex]) {
                captureEdges(drawnIndex, bitmaps[drawnIndex]);
                var shown = drawnIndex;
                drawnIndex = -1;
                draw(shown);
                drawnIndex = shown;
            }
            // At least one per call, so the queue still drains when the page is never idle.
            var first = true;
            while (edgeQueue.length && (first || !deadline || !deadline.timeRemaining || deadline.timeRemaining() > 4)) {
                first = false;
                var j = edgeQueue.shift();
                if (!edgeDone[j] && bitmaps[j]) captureEdges(j, bitmaps[j]);
            }
            if (edgeQueue.length) edgeIdle = whenIdle(runEdges);
        }

        function nearestCleanEdge(i) {
            for (var d = 0; d < count; d++) {
                if (i - d >= 0 && edgeDone[i - d] && cleanEdge[i - d]) return i - d;
                if (i + d < count && edgeDone[i + d] && cleanEdge[i + d]) return i + d;
            }
            return i;
        }

        // Frame to show for the target: the closest ready frame *behind* it in the
        // direction of travel, so motion never flicks backward while a frame decodes
        // (it holds briefly instead). Only if nothing is behind, the closest ahead.
        function pickFrame(i) {
            for (var d = 0; d < count; d++) {
                var behind = i - d * direction;
                if (behind >= 0 && behind < count && bitmaps[behind]) return behind;
            }
            for (var e = 1; e < count; e++) {
                var ahead = i + e * direction;
                if (ahead >= 0 && ahead < count && bitmaps[ahead]) return ahead;
            }
            return -1;
        }

        function draw(idx) {
            var cw = L.cw, ch = L.ch, dw = L.dw, dh = L.dh, dx = L.dx, dy = L.dy;
            var right = dx + dw;
            var k = nearestCleanEdge(idx);
            var e = 6 * k + 1;      // middle of the left strip
            var eR = 6 * k + 4;     // middle of the right strip

            var base = edgeBase[k] || ["#0a0c0e", "#0a0c0e"];
            var rowY = 3 * (edgeDone[idx] ? idx : k) + 1;
            if (dx > 0) {
                ctx.drawImage(rowAtlas, 0, rowY, 1, 1, 0, 0, dx + 1, dy + 1);
                ctx.drawImage(colAtlas, e, 0, 1, dh, 0, dy, dx + 1, dh);
                fadeBand(dx, 0, base[0]);
            }
            if (right < cw) {
                ctx.drawImage(rowAtlas, dw - 1, rowY, 1, 1, right - 1, 0, cw - right + 1, dy + 1);
                ctx.drawImage(colAtlas, eR, 0, 1, dh, right - 1, dy, cw - right + 1, dh);
                fadeBand(right, cw, base[1]);
            }
            if (dy > 0) {
                ctx.drawImage(rowAtlas, 0, rowY, dw, 1, dx, 0, dw, dy + 1);
                // Fade upward to the plain background, so any glow falls off naturally.
                var gv = ctx.createLinearGradient(0, dy, 0, 0);
                gv.addColorStop(0, "rgba(0,0,0,0)");
                gv.addColorStop(1, base[0]);
                ctx.fillStyle = gv;
                ctx.fillRect(dx, 0, dw, dy + 1);
            }
            ctx.drawImage(bitmaps[idx], dx, dy, dw, dh);
            if (dx > 0) blendSeam(e, dx, dy, true);
            if (right < cw) blendSeam(eR, right, dy, false);
            if (dy > 0) blendTop(rowY, dx, dy);
        }

        // Same idea as blendSeam, for the top edge: the band's row fades into the frame
        // (also softens anything touching the top edge, like the smartphone video's lights).
        function blendTop(rowY, dx, dy) {
            var fh = Math.max(2, Math.round(L.dw * 48 / 1280));
            var dw = L.dw;
            if (featherTop.width !== dw || featherTop.height !== fh) {
                featherTop.width = dw;
                featherTop.height = fh;
            }
            featherTopCtx.globalCompositeOperation = "copy";
            featherTopCtx.drawImage(rowAtlas, 0, rowY, dw, 1, 0, 0, dw, fh);
            featherTopCtx.globalCompositeOperation = "destination-in";
            var g = featherTopCtx.createLinearGradient(0, 0, 0, fh);
            g.addColorStop(0, "rgba(0,0,0,1)");
            g.addColorStop(1, "rgba(0,0,0,0)");
            featherTopCtx.fillStyle = g;
            featherTopCtx.fillRect(0, 0, dw, fh);
            ctx.drawImage(featherTop, dx, dy);
        }

        // The stretched edge column repeats any floor light at the frame's edge as a flat
        // stripe across the whole band, which ends abruptly where the frame's own light
        // falls off. Fade the band from the edge colour (at the seam) to the frame's plain
        // background colour (away from it), like the light falling off naturally.
        function fadeBand(fromX, toX, color) {
            var g = ctx.createLinearGradient(fromX, 0, toX, 0);
            g.addColorStop(0, "rgba(0,0,0,0)");
            g.addColorStop(1, color);
            ctx.fillStyle = g;
            ctx.fillRect(Math.min(fromX, toX), 0, Math.abs(toX - fromX) + 1, L.ch);
        }

        // Draw the band's column over the frame edge, fading from opaque at the seam to clear.
        function blendSeam(col, seamX, dy, leftSide) {
            var fw = Math.max(2, Math.round(L.dw * FEATHER));
            var dh = L.dh;
            if (feather.width !== fw || feather.height !== dh) {
                feather.width = fw;
                feather.height = dh;
            }
            featherCtx.globalCompositeOperation = "copy";
            featherCtx.drawImage(colAtlas, col, 0, 1, dh, 0, 0, fw, dh);
            featherCtx.globalCompositeOperation = "destination-in";
            var g = featherCtx.createLinearGradient(0, 0, fw, 0);
            g.addColorStop(0, leftSide ? "rgba(0,0,0,1)" : "rgba(0,0,0,0)");
            g.addColorStop(1, leftSide ? "rgba(0,0,0,0)" : "rgba(0,0,0,1)");
            featherCtx.fillStyle = g;
            featherCtx.fillRect(0, 0, fw, dh);
            ctx.drawImage(feather, leftSide ? seamX : seamX - fw, dy);
        }

        // On screen, or sliding in/out.
        function shown() {
            var c = slide.classList;
            return c.contains("is-active") || c.contains("is-exiting") || c.contains("is-exiting-right") || c.contains("is-entering-left");
        }

        function render() {
            rafId = 0;
            if (!L) return;
            // Hidden slides don't draw (drawing them every scroll tick doubled the GPU work);
            // wake() redraws them when they come into view.
            if (!shown()) { drawnIndex = -1; return; }
            var idx = pickFrame(target);
            if (idx < 0 || idx === drawnIndex) return;
            draw(idx);
            drawnIndex = idx;
            if (!edgeDone[idx] && !edgeIdle) edgeIdle = whenIdle(runEdges);
        }

        function requestRender() {
            if (!rafId) rafId = requestAnimationFrame(render);
        }

        function setProgress(p) {
            var t = Math.min(count - 1, Math.max(0, Math.round(p * (count - 1))));
            if (t === target) return;
            // Smoothed frames-per-update speed (updates arrive once per animation frame).
            speed = speed * 0.6 + Math.abs(t - target) * 0.4;
            direction = t > target ? 1 : -1;
            target = t;
            schedule();
            requestRender();
        }

        // Decode frames around the target (ahead in the scroll direction first)
        // and release ones that have fallen out of the window.
        function schedule() {
            if (!L) return;
            // Hidden slides don't decode while the hero is being scrolled (that competes
            // with the visible animation); they catch up via wake() once scrolling stops.
            if (!shown() && hooks.isBusy()) return;
            var half = Math.floor(keep / 2);
            var ahead = keep - half;
            var lo = direction > 0 ? target - half : target - ahead;
            // Shift a window that runs past either end so the whole budget is used
            // (at the top of the page everything decoded is ahead of the scroll).
            lo = Math.max(0, Math.min(lo, count - keep));
            var hi = Math.min(count - 1, lo + keep - 1);

            for (var i = 0; i < count; i++) {
                if (bitmaps[i] && (i < lo || i > hi) && i !== drawnIndex) {
                    bitmaps[i].close();
                    bitmaps[i] = null;
                }
            }
            // Moving fast, the screen skips frames anyway (it shows every `stride`-th),
            // so decode the frames that will actually be shown first, then fill the gaps.
            var stride = Math.max(1, Math.round(speed));
            if (stride > 1) {
                for (var j = 1; j * stride <= hi - lo && decoding < DECODE_WORKERS; j++) {
                    var next = target + j * stride * direction;
                    if (next >= lo && next <= hi) decode(next);
                }
            }
            for (var d = 0; d <= hi - lo && decoding < DECODE_WORKERS; d++) {
                var a = target + d * direction;
                var b = target - d * direction;
                if (a >= lo && a <= hi) decode(a);
                if (decoding < DECODE_WORKERS && d && b >= lo && b <= hi) decode(b);
            }
        }

        function decode(i) {
            if (bitmaps[i] || pending[i] === gen || !blobs[i] || decoding >= DECODE_WORKERS) return;
            var g = gen;
            pending[i] = g;
            decoding++;
            // Resizing while decoding costs ~40% more, so only ask for it when needed.
            var opts = (L.dw === IW && L.dh === IH) ? undefined
                : { resizeWidth: L.dw, resizeHeight: L.dh, resizeQuality: "high" };
            createImageBitmap(blobs[i], opts)
                .then(function (bmp) {
                    if (g !== gen) { bmp.close(); return; }
                    if (bitmaps[i]) bitmaps[i].close();
                    bitmaps[i] = bmp;
                    if (!edgeDone[i]) {
                        // The first strip is needed right away; the rest wait for idle time.
                        if (!edgeCount) captureEdges(i, bmp);
                        else queueEdges(i);
                    }
                    // Redraw if this frame would now be picked over what is on screen.
                    if (pickFrame(target) !== drawnIndex) {
                        drawnIndex = -1;
                        requestRender();
                    }
                }, function () {})
                .then(function () {
                    decoding--;
                    if (pending[i] === g) pending[i] = null;
                    schedule();
                });
        }

        function fetchFrame(i) {
            return fetch(frameUrl(i))
                .then(function (r) { return r.ok ? r.blob() : null; })
                .then(function (blob) {
                    if (blob) {
                        blobs[i] = blob;
                        schedule();
                    }
                }, function () {});
        }

        // Catch up after scrolling stops or when the slide comes into view.
        function wake() {
            speed = 0;
            drawnIndex = -1;
            schedule();
            requestRender();
        }

        return {
            slide: slide,
            shown: shown,
            wake: wake,
            setBudget: setBudget,
            copy: slide.querySelector("[data-pd-hero-copy]"),
            count: count,
            IW: IW,
            IH: IH,
            resize: resize,
            setProgress: setProgress,
            fetchFrame: fetchFrame
        };
    }

    function init() {
        var section = document.querySelector("[data-pd-hero-frames]");
        if (!section || !window.createImageBitmap) return;
        var stage = section.querySelector("[data-pd-hero-stage]");
        var slides = Array.prototype.slice.call(stage.querySelectorAll("[data-pd-slide]"));
        // Hero is "busy" while its scroll animation is moving (and briefly after).
        var lastMove = 0;
        var hooks = { isBusy: function () { return performance.now() - lastMove < 350; } };
        var seqs = slides.map(function (slide) { return createSequence(slide, hooks); })
            .filter(function (s) { return s.count > 0; });
        if (!seqs.length) return;

        // The site scrolls inside #pd-page-scroll (header stays above it), not the window.
        var scroller = document.getElementById("pd-page-scroll");
        var portraitQuery = window.matchMedia("(orientation: portrait)");
        // Scroll distance (in screen heights) that plays the whole sequence on landscape.
        var scrollLength = parseFloat(stage.dataset.scrollLength) || 0.6;
        var playDistance = 0; // portrait-only scroll distance in px (set in layout)
        var memBytes = (navigator.deviceMemory >= 8 ? 400 : navigator.deviceMemory >= 4 ? 200 : 120) * 1048576;

        function viewportHeight() {
            return scroller ? scroller.clientHeight : window.innerHeight;
        }

        // Memory budget: an even split while the slider autoplays (moving the budget on
        // every autoplay switch made it throw away and re-decode dozens of frames, which
        // kept the page busy and made it stutter). When the visitor starts scrolling the
        // hero, the visible slide gets most of it so more of its frames are ready.
        function budgetFor(s) {
            if (seqs.length < 2 || !focusSeq) return memBytes / seqs.length;
            return s === focusSeq ? memBytes * 0.75 : memBytes * 0.25 / (seqs.length - 1);
        }
        var focusSeq = null;
        function focusVisible() {
            var visible = seqs.filter(function (s) { return s.slide.classList.contains("is-active"); })[0] || null;
            if (visible === focusSeq) return;
            focusSeq = visible;
            seqs.forEach(function (s) { s.setBudget(budgetFor(s)); });
        }

        function layout() {
            var vh = viewportHeight();
            var stageH = vh;
            var area = { x: 0, y: 0 };
            playDistance = 0;
            // Offsets (not getBoundingClientRect) so slides moved sideways by the slider still measure right.
            var maxBottom = 0;
            var maxRight = 0;
            seqs.forEach(function (s) {
                if (!s.copy) return;
                maxBottom = Math.max(maxBottom, s.copy.offsetTop + s.copy.offsetHeight);
                maxRight = Math.max(maxRight, s.copy.offsetLeft + s.copy.offsetWidth);
            });
            if (portraitQuery.matches) {
                // Portrait: the stage is just the headline plus the full-width frame
                // right under it (no empty space), capped at the screen height. It is
                // not pinned (a pinned stage shorter than the screen would leave an empty
                // gap below it); the sequence plays as the headline scrolls away and is
                // fully exploded when the frame reaches the top of the screen.
                var s0 = seqs[0];
                stageH = Math.min(vh, Math.ceil(maxBottom + GAP + s0.IH * (stage.clientWidth / s0.IW)));
                playDistance = Math.max(120, Math.round(maxBottom));
                area.y = maxBottom + GAP;
            } else if (maxRight) {
                area.x = maxRight + GAP;
            }
            section.style.setProperty("--pd-hero-vh", vh + "px");
            section.style.setProperty("--pd-hero-h", stageH + "px");
            section.style.setProperty("--pd-hero-scroll", playDistance ? 0 : scrollLength);
            seqs.forEach(function (s) { s.resize(area, budgetFor(s)); });
            // Recalculate scroll positions only when the hero's size really changed
            // (e.g. not for every mobile address-bar resize), so scrolling never jumps.
            var key = stageH + "|" + playDistance + "|" + section.offsetHeight;
            if (key !== layoutKey) {
                layoutKey = key;
                if (window.ScrollTrigger) ScrollTrigger.refresh();
            }
        }
        var layoutKey = "";

        function setProgress(p) {
            // Tell the slider (index.js) not to autoplay while the animation is in progress.
            if (p > 0.001) stage.setAttribute("data-pd-hero-hold", "");
            else stage.removeAttribute("data-pd-hero-hold");
            if (!hooks.isBusy()) focusVisible(); // a new scroll gesture on the hero
            lastMove = performance.now();
            seqs.forEach(function (s) { s.setProgress(p); });
            clearTimeout(settleTimer);
            settleTimer = setTimeout(wakeAll, 400);
        }
        var settleTimer = 0;
        function wakeAll() { seqs.forEach(function (s) { s.wake(); }); }

        // A slide coming into view (autoplay, dots, swipe) draws its current frame.
        if (window.MutationObserver) {
            var observer = new MutationObserver(wakeAll);
            seqs.forEach(function (s) { observer.observe(s.slide, { attributes: true, attributeFilter: ["class"] }); });
        }

        // Fetch compressed frames: every slide's first frame, then the active slide,
        // then the rest. Each sequence is fetched coarse-to-fine (every 16th frame,
        // then every 8th, ...) so any scroll position has a nearby frame early on and
        // a fast first scroll plays at a lower frame rate instead of stalling.
        var queue = [];
        seqs.forEach(function (s) { queue.push([s, 0]); });
        seqs.slice().sort(function (a, b) { return b.shown() - a.shown(); }).forEach(function (s) {
            var queued = new Array(s.count);
            queued[0] = true;
            [16, 8, 4, 2, 1].forEach(function (step) {
                for (var i = 0; i < s.count; i += step) {
                    if (!queued[i]) { queued[i] = true; queue.push([s, i]); }
                }
                if (!queued[s.count - 1]) { queued[s.count - 1] = true; queue.push([s, s.count - 1]); }
            });
        });
        var nextFetch = 0;
        function pumpFetch() {
            if (nextFetch >= queue.length) return;
            var job = queue[nextFetch++];
            job[0].fetchFrame(job[1]).then(pumpFetch);
        }
        for (var w = 0; w < FETCH_WORKERS; w++) pumpFetch();

        var resizeTimer = 0;
        layout();
        window.addEventListener("resize", function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(layout, 120);
        });
        if (document.fonts && document.fonts.ready) {
            // Headline size can change once web fonts load, which moves the frame area.
            document.fonts.ready.then(layout);
        }

        if (!window.gsap || !window.ScrollTrigger) return;
        gsap.registerPlugin(ScrollTrigger);

        var state = { p: 0 };
        gsap.to(state, {
            p: 1,
            ease: "none",
            onUpdate: function () { setProgress(state.p); },
            scrollTrigger: {
                trigger: section,
                scroller: scroller || window,
                start: "top top",
                // Landscape: plays while the stage is stuck. Portrait: see layout().
                end: function () { return "+=" + (playDistance || Math.max(1, section.offsetHeight - stage.offsetHeight)); },
                // Eases the frame toward the scroll position for smooth playback.
                scrub: 1,
                invalidateOnRefresh: true
            }
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();
