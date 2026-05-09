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

    // ── Toggle open/close ───────────────────────────────────────────────────────
    toggle.addEventListener('click', function () {
        isOpen = !isOpen;
        chatWin.classList.toggle('pd-chat-open', isOpen);
        if (isOpen) {
            input.focus();
            msgBox.scrollTop = msgBox.scrollHeight;
        }
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            isOpen = false;
            chatWin.classList.remove('pd-chat-open');
        });
    }

    // ── Close on Escape ─────────────────────────────────────────────────────────
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && isOpen) {
            isOpen = false;
            chatWin.classList.remove('pd-chat-open');
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
            var reply = data.reply || data.error || fallback;
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

        // 2. Convert markdown links [label](url) to <a> tags
        esc = esc.replace(
            /\[([^\]]+)\]\((https?:\/\/[^)\s]+)\)/g,
            '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>'
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
        // Remove welcome message on first real exchange
        var welcome = msgBox.querySelector('.pd-chat-welcome');
        if (welcome) welcome.remove();

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
