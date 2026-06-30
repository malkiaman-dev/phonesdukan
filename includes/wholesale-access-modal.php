<div id="ws-access-modal" class="ws-access-modal" hidden aria-hidden="true">
    <div class="ws-access-modal__backdrop" data-ws-access-close></div>
    <div class="ws-access-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="ws-access-title">
        <button type="button" class="ws-access-modal__close" data-ws-access-close aria-label="Close">&times;</button>
        <div class="ws-access-modal__icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
        </div>
        <h2 id="ws-access-title">Shopkeeper Access</h2>
        <p>The wholesale portal is for registered shopkeepers only. Enter your shopkeeper code to continue.</p>
        <form id="ws-access-form" autocomplete="off">
            <label for="ws-access-code">Shopkeeper Code</label>
            <div class="ws-password-field">
                <input type="password" id="ws-access-code" name="code" class="ws-password-input" placeholder="Enter your code" required autocomplete="off">
                <button type="button" class="ws-password-toggle" aria-label="Show shopkeeper code" title="Show code">
                    <svg class="ws-eye-open" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                    </svg>
                    <svg class="ws-eye-closed" hidden xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>
                    </svg>
                </button>
            </div>
            <p id="ws-access-error" class="ws-access-error" hidden></p>
            <button type="submit" class="ws-access-submit" id="ws-access-submit">Continue</button>
        </form>
    </div>
</div>
