/**
 * Product Media – admin add/edit product pages
 */
const ProductMedia = (function () {
    const state = {
        mode: 'add',
        productId: 0,
        videoSource: 'upload',
        videoUrl: '',
        uploadedVideoPath: '',
        thumbnailUrl: '',
        customThumbnailUrl: '',
        customThumbnailUploadedPath: '',
        hasCustomThumbnail: false,
        removeCustomThumbnail: false,
        embedUrl: '',
        hasVideo: false,
        removeVideo: false,
        galleryItems: [],
        ajaxUploadUrl: 'ajax-upload-product-video.php',
        ajaxPreviewUrl: 'ajax-fetch-video-preview.php',
        ajaxThumbUrl: 'ajax-upload-video-thumbnail.php',
        basePath: '',
    };

    function $(id) {
        return document.getElementById(id);
    }

    function esc(s) {
        return String(s || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function mediaUrl(path) {
        if (!path) return '';
        if (/^https?:\/\//i.test(path)) return path;
        const base = state.basePath || '';
        if (base && path.startsWith('/') && !path.startsWith(base + '/')) {
            return base + path;
        }
        return path;
    }

    function init(options) {
        Object.assign(state, options || {});
        state.videoSource = state.videoSource || 'upload';
        bindSourceRadios();
        bindUploadPanel();
        bindUrlPanel();
        bindThumbnailPanel();
        bindGalleryDrag();

        if (state.mode === 'edit' && state.existingVideo) {
            loadExistingVideo(state.existingVideo);
        }

        if (state.mode === 'edit' && Array.isArray(state.initialGalleryItems)) {
            state.galleryItems = state.initialGalleryItems.map(normalizeGalleryItem);
            renderGalleryOrder();
        } else {
            bindAddPageImageInputs();
            rebuildAddGalleryItems();
        }

        const form = document.getElementById('add-product-form')
            || document.getElementById('product-form')
            || document.querySelector('form.product-form');
        if (form) {
            form.addEventListener('submit', function () {
                syncHiddenFields();
            });
        }

        syncHiddenFields();
    }

    function normalizeGalleryItem(item) {
        return {
            type: item.type,
            key: item.key || null,
            id: item.id || null,
            label: item.label || (item.type === 'video' ? 'Video' : 'Image'),
            preview: item.preview || '',
            is_primary: item.is_primary || 0,
        };
    }

    function bindSourceRadios() {
        document.querySelectorAll('input[name="video_source"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                state.videoSource = this.value;
                togglePanels();
                if (state.videoSource !== 'upload') {
                    clearUploadedVideo(false);
                }
                syncHiddenFields();
            });
        });
        togglePanels();
    }

    function togglePanels() {
        const uploadPanel = $('pm-upload-panel');
        const urlPanel = $('pm-url-panel');
        const urlInput = $('pm_video_url');
        if (uploadPanel) uploadPanel.classList.toggle('is-active', state.videoSource === 'upload');
        if (urlPanel) urlPanel.classList.toggle('is-active', state.videoSource !== 'upload');

        const placeholders = {
            youtube: 'https://youtube.com/watch?v=...',
            tiktok: 'https://www.tiktok.com/@user/video/...',
            facebook: 'https://www.facebook.com/watch/?v=...',
            mp4: 'https://example.com/video.mp4',
        };
        if (urlInput && state.videoSource !== 'upload') {
            urlInput.placeholder = placeholders[state.videoSource] || 'https://...';
        }
    }

    function bindUploadPanel() {
        const input = $('pm_video_file');
        const replaceInput = $('pm_video_file_replace');
        if (input) {
            input.addEventListener('change', function () {
                if (this.files && this.files[0]) uploadVideoFile(this.files[0], input);
            });
        }
        if (replaceInput) {
            replaceInput.addEventListener('change', function () {
                if (this.files && this.files[0]) uploadVideoFile(this.files[0], replaceInput);
            });
        }

        const replaceBtn = $('pm_replace_video_btn');
        const removeBtn = $('pm_remove_video_btn');
        if (replaceBtn) {
            replaceBtn.addEventListener('click', function () {
                if (state.videoSource === 'upload') {
                    if (replaceInput) replaceInput.click();
                } else {
                    const urlInput = $('pm_video_url');
                    if (urlInput) urlInput.focus();
                }
            });
        }
        if (removeBtn) {
            removeBtn.addEventListener('click', removeVideo);
        }
    }

    function bindUrlPanel() {
        const urlInput = $('pm_video_url');
        const validateBtn = $('pm_validate_url_btn');
        if (!urlInput) return;

        let debounce;
        urlInput.addEventListener('input', function () {
            clearTimeout(debounce);
            debounce = setTimeout(function () {
                if (urlInput.value.trim()) validateVideoUrl(false);
            }, 600);
        });

        if (validateBtn) {
            validateBtn.addEventListener('click', function () {
                validateVideoUrl(true);
            });
        }
    }

    function bindThumbnailPanel() {
        const input = $('pm_thumb_file');
        const replaceInput = $('pm_thumb_file_replace');
        if (input) {
            input.addEventListener('change', function () {
                if (this.files && this.files[0]) uploadThumbnailFile(this.files[0], input);
            });
        }
        if (replaceInput) {
            replaceInput.addEventListener('change', function () {
                if (this.files && this.files[0]) uploadThumbnailFile(this.files[0], replaceInput);
            });
        }

        const replaceBtn = $('pm_replace_thumb_btn');
        const removeBtn = $('pm_remove_thumb_btn');
        if (replaceBtn) {
            replaceBtn.addEventListener('click', function () {
                if (replaceInput) replaceInput.click();
            });
        }
        if (removeBtn) {
            removeBtn.addEventListener('click', removeThumbnail);
        }
    }

    function showThumbnailPreview(url, source) {
        const card = $('pm_thumb_preview_card');
        const img = $('pm_thumb_preview_img');
        const badge = $('pm_thumb_badge');
        const uploadPanel = $('pm-thumb-upload-panel');
        if (!card || !img) return;

        if (!url) {
            hideThumbnailPreview();
            return;
        }

        img.src = /^data:/.test(url) ? url : mediaUrl(url);
        card.classList.add('is-visible');
        if (uploadPanel) uploadPanel.classList.add('is-hidden');

        if (badge) {
            const isCustom = source === 'custom';
            badge.textContent = isCustom ? 'Custom poster' : 'Auto-generated';
            badge.classList.add('is-visible');
            badge.classList.toggle('is-custom', isCustom);
        }
    }

    function hideThumbnailPreview() {
        const card = $('pm_thumb_preview_card');
        const img = $('pm_thumb_preview_img');
        const badge = $('pm_thumb_badge');
        const uploadPanel = $('pm-thumb-upload-panel');
        if (card) card.classList.remove('is-visible');
        if (img) img.removeAttribute('src');
        if (badge) badge.classList.remove('is-visible', 'is-custom');
        if (uploadPanel) uploadPanel.classList.remove('is-hidden');
    }

    async function uploadThumbnailFile(file, inputEl) {
        const allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (!allowed.includes(file.type)) {
            alert('Use JPG, PNG, or WEBP for the poster image.');
            if (inputEl) inputEl.value = '';
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            alert('Thumbnail too large. Maximum size is 5 MB.');
            if (inputEl) inputEl.value = '';
            return;
        }

        const progressWrap = $('pm_thumb_upload_progress');
        const progressFill = $('pm_thumb_upload_progress_fill');
        const progressText = $('pm_thumb_upload_progress_text');
        if (progressWrap) progressWrap.classList.add('is-visible');
        if (progressFill) progressFill.style.width = '0%';
        if (progressText) progressText.textContent = '0%';

        const fd = new FormData();
        fd.append('video_thumbnail', file);

        try {
            const result = await xhrUpload(state.ajaxThumbUrl, fd, function (pct) {
                if (progressFill) progressFill.style.width = pct + '%';
                if (progressText) progressText.textContent = pct + '%';
            });

            if (!result.success) {
                alert(result.message || 'Thumbnail upload failed.');
                return;
            }

            state.customThumbnailUploadedPath = result.thumbnail_url || '';
            state.customThumbnailUrl = result.thumbnail_url || '';
            state.hasCustomThumbnail = true;
            state.removeCustomThumbnail = false;

            showThumbnailPreview(result.thumbnail_url, 'custom');
            ensureVideoInGallery();
            syncHiddenFields();
        } catch (err) {
            alert('Thumbnail upload error: ' + err.message);
        } finally {
            if (progressWrap) progressWrap.classList.remove('is-visible');
            if (inputEl) inputEl.value = '';
        }
    }

    function removeThumbnail() {
        state.hasCustomThumbnail = false;
        state.customThumbnailUploadedPath = '';
        state.customThumbnailUrl = '';
        state.removeCustomThumbnail = true;

        const replaceInput = $('pm_thumb_file_replace');
        const uploadInput = $('pm_thumb_file');
        if (replaceInput) replaceInput.value = '';
        if (uploadInput) uploadInput.value = '';

        if (state.thumbnailUrl && state.hasVideo) {
            showThumbnailPreview(state.thumbnailUrl, 'auto');
        } else {
            hideThumbnailPreview();
        }

        ensureVideoInGallery();
        syncHiddenFields();
    }

    async function uploadVideoFile(file, inputEl) {
        const allowed = ['video/mp4', 'video/webm', 'video/quicktime'];
        if (!allowed.includes(file.type)) {
            alert('Invalid file type. Allowed: MP4, WebM, MOV.');
            if (inputEl) inputEl.value = '';
            return;
        }
        if (file.size > 104857600) {
            alert('File too large. Maximum size is 100 MB.');
            if (inputEl) inputEl.value = '';
            return;
        }

        const progressWrap = $('pm_upload_progress');
        const progressFill = $('pm_upload_progress_fill');
        const progressText = $('pm_upload_progress_text');
        if (progressWrap) progressWrap.classList.add('is-visible');
        if (progressFill) progressFill.style.width = '0%';
        if (progressText) progressText.textContent = '0%';

        let clientThumb = null;
        try {
            clientThumb = await captureVideoFrame(file);
        } catch (e) {
            clientThumb = null;
        }

        const fd = new FormData();
        fd.append('product_video', file);
        if (clientThumb) {
            fd.append('video_thumbnail', clientThumb, 'frame.jpg');
        }

        try {
            const result = await xhrUpload(state.ajaxUploadUrl, fd, function (pct) {
                if (progressFill) progressFill.style.width = pct + '%';
                if (progressText) progressText.textContent = pct + '%';
            });

            if (!result.success) {
                alert(result.message || 'Upload failed.');
                return;
            }

            state.uploadedVideoPath = result.video_url;
            state.videoUrl = result.video_url;
            state.thumbnailUrl = result.thumbnail_url || state.thumbnailUrl;
            state.embedUrl = mediaUrl(result.video_url);
            state.hasVideo = true;
            state.removeVideo = false;

            showVideoPreview('upload', state.embedUrl, state.thumbnailUrl);
            if (state.thumbnailUrl && !state.hasCustomThumbnail) {
                showThumbnailPreview(state.thumbnailUrl, 'auto');
            }
            ensureVideoInGallery();
            syncHiddenFields();
        } catch (err) {
            alert('Upload error: ' + err.message);
        } finally {
            if (progressWrap) progressWrap.classList.remove('is-visible');
            if (inputEl) inputEl.value = '';
        }
    }

    function xhrUpload(url, formData, onProgress) {
        return new Promise(function (resolve, reject) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', url, true);
            xhr.upload.onprogress = function (e) {
                if (e.lengthComputable && onProgress) {
                    onProgress(Math.round((e.loaded / e.total) * 100));
                }
            };
            xhr.onload = function () {
                try {
                    resolve(JSON.parse(xhr.responseText));
                } catch (e) {
                    reject(new Error('Invalid server response'));
                }
            };
            xhr.onerror = function () {
                reject(new Error('Network error'));
            };
            xhr.send(formData);
        });
    }

    function captureVideoFrame(file) {
        return new Promise(function (resolve, reject) {
            const video = document.createElement('video');
            video.preload = 'metadata';
            video.muted = true;
            video.playsInline = true;
            const url = URL.createObjectURL(file);
            video.src = url;

            video.addEventListener('loadeddata', function () {
                video.currentTime = Math.min(1, video.duration || 1);
            });

            video.addEventListener('seeked', function () {
                try {
                    const canvas = document.createElement('canvas');
                    canvas.width = video.videoWidth || 640;
                    canvas.height = video.videoHeight || 360;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                    canvas.toBlob(function (blob) {
                        URL.revokeObjectURL(url);
                        if (blob) resolve(blob);
                        else reject(new Error('Could not capture frame'));
                    }, 'image/jpeg', 0.85);
                } catch (e) {
                    URL.revokeObjectURL(url);
                    reject(e);
                }
            });

            video.addEventListener('error', function () {
                URL.revokeObjectURL(url);
                reject(new Error('Could not load video'));
            });
        });
    }

    async function validateVideoUrl(showAlert) {
        const urlInput = $('pm_video_url');
        const errorEl = $('pm_url_error');
        if (!urlInput) return false;

        const url = urlInput.value.trim();
        if (!url) {
            setUrlError(errorEl, urlInput, 'Video URL is required.');
            return false;
        }

        const sourceMap = { youtube: 'youtube', tiktok: 'tiktok', facebook: 'facebook', mp4: 'mp4' };
        const fd = new FormData();
        fd.append('video_url', url);
        fd.append('video_source', sourceMap[state.videoSource] || state.videoSource);

        try {
            const res = await fetch(state.ajaxPreviewUrl, { method: 'POST', body: fd });
            const data = await res.json();
            if (!data.success) {
                setUrlError(errorEl, urlInput, data.message || 'Invalid URL.');
                if (showAlert) alert(data.message || 'Invalid URL.');
                return false;
            }

            clearUrlError(errorEl, urlInput);
            state.videoUrl = data.video_url;
            state.thumbnailUrl = data.thumbnail_url || '';
            state.embedUrl = data.embed_url || data.video_url;
            state.hasVideo = true;
            state.removeVideo = false;
            state.uploadedVideoPath = '';

            showVideoPreview(state.videoSource, state.embedUrl, state.thumbnailUrl);
            if (state.thumbnailUrl && !state.hasCustomThumbnail) {
                showThumbnailPreview(state.thumbnailUrl, 'auto');
            }
            ensureVideoInGallery();
            syncHiddenFields();
            return true;
        } catch (e) {
            setUrlError(errorEl, urlInput, 'Could not validate URL.');
            return false;
        }
    }

    function setUrlError(el, input, msg) {
        if (el) {
            el.textContent = msg;
            el.classList.add('is-visible');
        }
        if (input) input.classList.add('is-invalid');
    }

    function clearUrlError(el, input) {
        if (el) el.classList.remove('is-visible');
        if (input) input.classList.remove('is-invalid');
    }

    function showVideoPreview(source, embedUrl, thumbUrl) {
        const card = $('pm_video_preview');
        const media = $('pm_preview_media');
        if (!card || !media) return;

        let html = '';
        if (source === 'upload') {
            html = '<video src="' + esc(mediaUrl(embedUrl)) + '" controls playsinline></video>';
        } else if (source === 'youtube' || source === 'tiktok' || source === 'facebook') {
            html = '<iframe src="' + esc(embedUrl) + '" allowfullscreen loading="lazy"></iframe>';
        } else {
            html = '<video src="' + esc(mediaUrl(embedUrl)) + '" controls playsinline></video>';
        }
        media.innerHTML = html;
        card.classList.add('is-visible');
    }

    function clearUploadedVideo(clearAll) {
        if (clearAll) {
            state.videoUrl = '';
            state.thumbnailUrl = '';
            state.embedUrl = '';
            state.hasVideo = false;
        }
        state.uploadedVideoPath = '';
        const card = $('pm_video_preview');
        const media = $('pm_preview_media');
        if (card) card.classList.remove('is-visible');
        if (media) media.innerHTML = '';
        removeVideoFromGallery();
    }

    function removeVideo() {
        state.hasVideo = false;
        state.removeVideo = true;
        state.videoUrl = '';
        state.uploadedVideoPath = '';
        state.thumbnailUrl = '';
        state.customThumbnailUrl = '';
        state.customThumbnailUploadedPath = '';
        state.hasCustomThumbnail = false;
        state.removeCustomThumbnail = false;
        state.embedUrl = '';

        const urlInput = $('pm_video_url');
        if (urlInput) urlInput.value = '';
        hideThumbnailPreview();

        const card = $('pm_video_preview');
        if (card) card.classList.remove('is-visible');
        removeVideoFromGallery();
        syncHiddenFields();
    }

    function loadExistingVideo(video) {
        if (!video) return;
        state.hasVideo = true;
        state.removeVideo = false;
        state.videoSource = video.video_source || 'upload';
        state.videoUrl = video.video_url || '';
        state.uploadedVideoPath = video.video_source === 'upload' ? video.video_url : '';
        state.thumbnailUrl = video.thumbnail_url || '';
        state.customThumbnailUrl = video.custom_thumbnail_url || '';
        state.customThumbnailUploadedPath = video.custom_thumbnail_url || '';
        state.hasCustomThumbnail = !!video.custom_thumbnail_url;
        state.removeCustomThumbnail = false;

        const radio = document.querySelector('input[name="video_source"][value="' + state.videoSource + '"]');
        if (radio) radio.checked = true;
        togglePanels();

        const urlInput = $('pm_video_url');
        if (urlInput && state.videoSource !== 'upload') {
            urlInput.value = state.videoUrl;
        }

        if (state.hasCustomThumbnail && state.customThumbnailUrl) {
            showThumbnailPreview(state.customThumbnailUrl, 'custom');
        } else if (state.thumbnailUrl) {
            showThumbnailPreview(state.thumbnailUrl, 'auto');
        } else {
            hideThumbnailPreview();
        }

        let embed = state.videoUrl;
        if (state.videoSource === 'youtube') {
            const m = state.videoUrl.match(/[?&]v=([^&]+)/) || state.videoUrl.match(/youtu\.be\/([^?]+)/);
            embed = m ? 'https://www.youtube.com/embed/' + m[1] : state.videoUrl;
        } else if (state.videoSource === 'tiktok') {
            const m = state.videoUrl.match(/\/video\/(\d+)/);
            embed = m ? 'https://www.tiktok.com/embed/v2/' + m[1] : state.videoUrl;
        } else if (state.videoSource === 'facebook') {
            embed = 'https://www.facebook.com/plugins/video.php?href='
                + encodeURIComponent(state.videoUrl) + '&show_text=false&width=560';
        }
        state.embedUrl = embed;
        showVideoPreview(state.videoSource, embed, state.customThumbnailUrl || state.thumbnailUrl);
        syncHiddenFields();
    }

    function bindAddPageImageInputs() {
        const primary = $('primary_image');
        const gallery = $('gallery_images');
        if (primary) {
            primary.addEventListener('change', rebuildAddGalleryItems);
        }
        if (gallery) {
            gallery.addEventListener('change', rebuildAddGalleryItems);
        }
    }

    function rebuildAddGalleryItems() {
        const items = [];
        let imageNum = 0;
        const primary = $('primary_image');
        if (primary && primary.files && primary.files[0]) {
            imageNum++;
            items.push({
                type: 'image',
                key: 'primary',
                label: 'Image ' + imageNum,
                preview: URL.createObjectURL(primary.files[0]),
                is_primary: 1,
            });
        }

        const gallery = $('gallery_images');
        if (gallery && gallery.files) {
            for (let i = 0; i < gallery.files.length; i++) {
                imageNum++;
                items.push({
                    type: 'image',
                    key: 'gallery-' + i,
                    label: 'Image ' + imageNum,
                    preview: URL.createObjectURL(gallery.files[i]),
                    is_primary: 0,
                });
            }
        }

        const videoItem = state.galleryItems.find(function (x) { return x.type === 'video'; });
        if (state.hasVideo) {
            items.push(videoItem || {
                type: 'video',
                key: 'video',
                label: 'Video',
                preview: state.customThumbnailUrl || state.thumbnailUrl || '',
            });
        }

        mergeGalleryOrder(items);
        renderGalleryOrder();
        syncHiddenFields();
    }

    function ensureVideoInGallery() {
        if (state.mode !== 'add') {
            const idx = state.galleryItems.findIndex(function (x) { return x.type === 'video'; });
            const item = {
                type: 'video',
                key: 'video',
                id: state.existingVideoId || null,
                label: 'Video',
                preview: state.customThumbnailUrl || state.thumbnailUrl || '',
            };
            if (idx >= 0) state.galleryItems[idx] = item;
            else state.galleryItems.push(item);
            renderGalleryOrder();
            return;
        }
        rebuildAddGalleryItems();
    }

    function removeVideoFromGallery() {
        state.galleryItems = state.galleryItems.filter(function (x) { return x.type !== 'video'; });
        renderGalleryOrder();
    }

    function mergeGalleryOrder(newItems) {
        const oldOrder = state.galleryItems.map(function (x) {
            return x.key || (x.type + ':' + (x.id || ''));
        });
        const map = {};
        newItems.forEach(function (item) {
            const k = item.key || (item.type + ':' + (item.id || ''));
            map[k] = item;
        });

        const merged = [];
        oldOrder.forEach(function (k) {
            if (map[k]) {
                merged.push(map[k]);
                delete map[k];
            }
        });
        Object.keys(map).forEach(function (k) {
            merged.push(map[k]);
        });
        state.galleryItems = merged;
    }

    function renderGalleryOrder() {
        const list = $('pm_gallery_order_list');
        const empty = $('pm_gallery_order_empty');
        if (!list) return;

        if (!state.galleryItems.length) {
            list.innerHTML = '';
            if (empty) empty.style.display = 'block';
            return;
        }
        if (empty) empty.style.display = 'none';

        list.innerHTML = state.galleryItems.map(function (item, index) {
            const thumb = item.preview
                ? '<img src="' + esc(mediaUrl(item.preview)) + '" alt="">'
                : (item.type === 'video'
                    ? '<span class="pm-video-icon"><i class="fas fa-play-circle"></i></span>'
                    : '<span class="pm-video-icon"><i class="fas fa-image"></i></span>');
            return '<li class="pm-order-item" draggable="true" data-index="' + index + '">' +
                '<span class="pm-order-handle"><i class="fas fa-grip-vertical"></i></span>' +
                '<div class="pm-order-thumb">' + thumb + '</div>' +
                '<span class="pm-order-label">' + esc(item.label) + '</span>' +
                '</li>';
        }).join('');
    }

    function bindGalleryDrag() {
        const list = $('pm_gallery_order_list');
        if (!list) return;

        let dragIndex = null;

        list.addEventListener('dragstart', function (e) {
            const item = e.target.closest('.pm-order-item');
            if (!item) return;
            dragIndex = parseInt(item.dataset.index, 10);
            item.classList.add('is-dragging');
            e.dataTransfer.effectAllowed = 'move';
        });

        list.addEventListener('dragend', function (e) {
            const item = e.target.closest('.pm-order-item');
            if (item) item.classList.remove('is-dragging');
            list.querySelectorAll('.pm-order-item').forEach(function (el) {
                el.classList.remove('is-drag-over');
            });
        });

        list.addEventListener('dragover', function (e) {
            e.preventDefault();
            const item = e.target.closest('.pm-order-item');
            if (!item) return;
            list.querySelectorAll('.pm-order-item').forEach(function (el) {
                el.classList.remove('is-drag-over');
            });
            item.classList.add('is-drag-over');
        });

        list.addEventListener('drop', function (e) {
            e.preventDefault();
            const item = e.target.closest('.pm-order-item');
            if (!item || dragIndex === null) return;
            const dropIndex = parseInt(item.dataset.index, 10);
            if (dragIndex === dropIndex) return;

            const moved = state.galleryItems.splice(dragIndex, 1)[0];
            state.galleryItems.splice(dropIndex, 0, moved);
            dragIndex = null;
            renderGalleryOrder();
            syncHiddenFields();
        });
    }

    function syncHiddenFields() {
        const orderInput = $('gallery_order_json');
        if (orderInput) {
            const payload = state.galleryItems.map(function (item) {
                const row = { type: item.type };
                if (item.id) row.id = item.id;
                if (item.key) row.key = item.key;
                return row;
            });
            orderInput.value = JSON.stringify(payload);
        }

        const fields = {
            video_uploaded_path: $('video_uploaded_path'),
            video_url_hidden: $('video_url_hidden'),
            video_thumbnail_path: $('video_thumbnail_path'),
            remove_video_flag: $('remove_video_flag'),
            has_product_video: $('has_product_video'),
        };

        if (fields.video_uploaded_path) {
            fields.video_uploaded_path.value = state.uploadedVideoPath || '';
        }
        if (fields.video_url_hidden) {
            fields.video_url_hidden.value = state.videoSource !== 'upload' ? (state.videoUrl || '') : (state.uploadedVideoPath || '');
        }
        if (fields.video_thumbnail_path) {
            fields.video_thumbnail_path.value = state.thumbnailUrl || '';
        }
        if (fields.remove_video_flag) {
            fields.remove_video_flag.value = state.removeVideo ? '1' : '0';
        }
        if (fields.has_product_video) {
            fields.has_product_video.value = state.hasVideo ? '1' : '0';
        }

        const customThumbPath = $('video_custom_thumbnail_path');
        const removeCustomThumb = $('remove_custom_thumbnail_flag');
        if (customThumbPath) {
            customThumbPath.value = state.hasCustomThumbnail ? (state.customThumbnailUploadedPath || '') : '';
        }
        if (removeCustomThumb) {
            removeCustomThumb.value = state.removeCustomThumbnail ? '1' : '0';
        }
    }

    return { init: init, rebuildGallery: rebuildAddGalleryItems, syncHiddenFields: syncHiddenFields };
})();
