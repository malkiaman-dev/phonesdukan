document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    var toggle   = document.getElementById('pd-chatbot-toggle');
    var chatWin  = document.getElementById('pd-chatbot-win');
    var closeBtn = document.getElementById('pd-chatbot-close');
    var form     = document.getElementById('pd-chatbot-form');
    var input    = document.getElementById('pd-chatbot-input');
    var msgBox   = document.getElementById('pd-chatbot-messages');
    var sendBtn  = document.getElementById('pd-chatbot-send');

    if (!toggle || !chatWin || !form || !input || !msgBox) return;

    var history  = [];
    var isOpen   = false;
    var isBusy   = false;

    // ── Out-of-scope reply (no API call needed) ─────────────────────────────────
    var OUT_OF_SCOPE_REPLY = 'Ha, that is a bit outside what I can help with! I am only here for Phones Dukan shopping. Is there a phone, watch, earbuds, or accessory I can find for you?';

    // ── Domain check — fast client-side filter before hitting the API ───────────
    function isPhonesDukanQuery(text) {
        var t = (text || '').toLowerCase().trim();
        if (!t) return true;

        var smallTalk = ['hi','hello','hey','salam','assalam','aoa','thanks','thank you','ok','okay','how are you','who are you','what are you'];
        if (smallTalk.some(function(h) { return t === h || t.indexOf(h) === 0; })) return true;

        var allowed = ['phone','mobile','watch','earbud','speaker','charger','accessory','accessories',
            'cable','cover','case','power bank','powerbank','price','buy','order','deliver','return',
            'policy','warranty','samsung','xiaomi','oppo','vivo','realme','tecno','infinix','nokia',
            'iphone','apple','huawei','redmi','poco','audionic','ronin','qualcomm','oneplus',
            'battery','camera','gaming','budget','cheap','best','location','address','contact',
            'whatsapp','store','product','stock','available','cash','cod','earphone','headphone',
            'tws','airpod','bluetooth','smartwatch','brand','model','suggest','recommend'];
        if (allowed.some(function(h) { return t.indexOf(h) !== -1; })) return true;

        var blocked = ['python','javascript','java',' code','program','html','css','2+2','math',
            'joke','poem','song','recipe','weather','news','movie','cricket','football',
            'homework','essay','calculate','c++','algorithm','write a'];
        if (blocked.some(function(h) { return t.indexOf(h) !== -1; })) return false;

        if (/\b\d+\s*[+\-*/]\s*\d+\b/.test(t)) return false;

        return true;
    }

    // ── Sanitize reply — strip em/en dashes the model may still produce ─────────
    function sanitizeReply(text) {
        if (!text) return text;
        return text
            .replace(/[–—―]/g, ',')
            .replace(/\s+,/g, ',')
            .replace(/,{2,}/g, ',')
            .replace(/[ \t]{2,}/g, ' ')
            .trim();
    }

    // ── Mobile keyboard: reposition window when keyboard opens/closes ───────────
    function isMobile() { return window.innerWidth <= 480; }

    function applyMobileViewport() {
        if (!isMobile() || !isOpen) return;
        var vv  = window.visualViewport;
        var vvh = vv ? vv.height   : window.innerHeight;
        var vvt = vv ? vv.offsetTop : 0;

        // Pin window between 8px below viewport top and 8px above keyboard
        chatWin.style.top    = (vvt + 8) + 'px';
        chatWin.style.bottom = 'auto';
        chatWin.style.height = (vvh - 72) + 'px'; // 64px toggle area + 8px gap
        chatWin.style.maxHeight = 'none';

        // Scroll latest message into view
        setTimeout(function () { msgBox.scrollTop = msgBox.scrollHeight; }, 80);
    }

    function resetMobileViewport() {
        chatWin.style.top = '';
        chatWin.style.bottom = '';
        chatWin.style.height = '';
        chatWin.style.maxHeight = '';
    }

    if (window.visualViewport) {
        window.visualViewport.addEventListener('resize', applyMobileViewport);
        window.visualViewport.addEventListener('scroll', applyMobileViewport);
    }

    // ── Toggle open/close ───────────────────────────────────────────────────────
    toggle.addEventListener('click', function () {
        isOpen = !isOpen;
        chatWin.classList.toggle('pd-chat-open', isOpen);
        if (isOpen) {
            applyMobileViewport();
            input.focus();
            msgBox.scrollTop = msgBox.scrollHeight;
        } else {
            resetMobileViewport();
        }
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            isOpen = false;
            chatWin.classList.remove('pd-chat-open');
            resetMobileViewport();
        });
    }

    // ── Close on Escape ─────────────────────────────────────────────────────────
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && isOpen) {
            isOpen = false;
            chatWin.classList.remove('pd-chat-open');
            resetMobileViewport();
        }
    });

    // ── Submit form ─────────────────────────────────────────────────────────────
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        sendMessage();
    });

    // Allow Enter to send (Shift+Enter for newline)
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    function sendMessage() {
        if (isBusy) return;
        var text = input.value.trim();
        if (!text) return;

        input.value = '';
        addMessage('user', text);

        isBusy = true;
        if (sendBtn) sendBtn.disabled = true;

        // Block off-topic queries instantly without hitting the API
        if (!isPhonesDukanQuery(text)) {
            addMessage('assistant', OUT_OF_SCOPE_REPLY);
            isBusy = false;
            if (sendBtn) sendBtn.disabled = false;
            input.focus();
            return;
        }

        var typing = addTyping();

        var endpoint = (window.pdWithBase ? window.pdWithBase('/public/ajax/chatbot.php') : '/public/ajax/chatbot.php');

        var fallback = 'We could not reach our assistant right now. Please visit phonesdukan.com or call (+92) 3116600031 and we will be happy to help.';

        fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: text, history: history }),
        })
        .then(function (res) {
            return res.text().then(function (raw) {
                try { return JSON.parse(raw); }
                catch (e) { return { error: fallback }; }
            });
        })
        .then(function (data) {
            typing.remove();
            var reply = sanitizeReply(data.reply) || data.error || fallback;
            addMessage('assistant', reply);

            if (data.reply) {
                history.push({ role: 'user',      content: text });
                history.push({ role: 'assistant', content: data.reply });
                if (history.length > 20) history = history.slice(-20);
            }
        })
        .catch(function () {
            typing.remove();
            addMessage('assistant', fallback);
        })
        .finally(function () {
            isBusy = false;
            if (sendBtn) sendBtn.disabled = false;
            input.focus();
        });
    }

    // ── Render text safely with clickable links ─────────────────────────────────
    function renderText(text) {
        // 1. Escape HTML to prevent XSS
        var esc = text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');

        // 2. Convert markdown links [label](url) to <a> tags — supports https, http, tel, mailto
        esc = esc.replace(
            /\[([^\]]+)\]\(((?:https?:\/\/|tel:|mailto:)[^)\s]+)\)/g,
            function (_, label, url) {
                var isTel    = url.indexOf('tel:')    === 0;
                var isMail   = url.indexOf('mailto:') === 0;
                var target   = (isTel || isMail) ? '' : ' target="_blank" rel="noopener noreferrer"';
                return '<a href="' + url + '"' + target + '>' + label + '</a>';
            }
        );

        // 3. Convert any remaining bare URLs to "View Product" links
        esc = esc.replace(
            /(?<![="(])((https?:\/\/)[^\s<,]+)/g,
            '<a href="$1" target="_blank" rel="noopener noreferrer">View Product</a>'
        );

        // 4. Convert newlines to <br>
        esc = esc.replace(/\n/g, '<br>');

        return esc;
    }

    // ── DOM helpers ─────────────────────────────────────────────────────────────
    function addMessage(role, text) {
        var div = document.createElement('div');
        div.className = 'pd-chat-msg pd-chat-msg--' + role;
        if (role === 'assistant') {
            div.innerHTML = renderText(text);
        } else {
            div.textContent = text;
        }
        msgBox.appendChild(div);
        msgBox.scrollTop = msgBox.scrollHeight;
        return div;
    }

    function addTyping() {
        var div = document.createElement('div');
        div.className = 'pd-chat-msg pd-chat-msg--assistant pd-chat-typing';
        div.innerHTML = '<span></span><span></span><span></span>';
        msgBox.appendChild(div);
        msgBox.scrollTop = msgBox.scrollHeight;
        return div;
    }
});
