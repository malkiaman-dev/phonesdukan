<?php
require_once dirname(__DIR__, 3) . '/includes/header.php';
?>

<div class="track-page">
    <div class="track-shell">
        <section class="track-form-column">
            <div class="track-form-card <?= (isset($error) && $error) ? 'track-has-error' : '' ?>">
                <h2 class="track-form-title">Track Your Order</h2>

                <?php if (isset($error) && $error) : ?>
                    <p class="track-form-error" role="alert"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>

                <form method="post" action="/track-order" class="track-form" id="trackOrderForm" novalidate>
                    <div class="track-field-group">
                        <label for="track-email">Email Address</label>
                        <div class="track-input-wrap">
                            <span class="track-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M4 7h16v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7z" stroke="currentColor" stroke-width="1.8"/>
                                    <path d="M4 8l8 6 8-6" stroke="currentColor" stroke-width="1.8"/>
                                </svg>
                            </span>
                            <input
                                id="track-email"
                                class="track-input"
                                type="email"
                                name="email"
                                placeholder="Enter your email address"
                                required
                                value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                            >
                        </div>
                        <small class="track-inline-error" id="trackEmailError"></small>
                    </div>

                    <div class="track-field-group">
                        <label for="track-order-id">Order ID</label>
                        <div class="track-input-wrap">
                            <span class="track-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M5 8h14v10a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8z" stroke="currentColor" stroke-width="1.8"/>
                                    <path d="M9 8V6a3 3 0 1 1 6 0v2" stroke="currentColor" stroke-width="1.8"/>
                                </svg>
                            </span>
                            <input
                                id="track-order-id"
                                class="track-input"
                                type="text"
                                name="order_id"
                                placeholder="Enter your order ID"
                                required
                                value="<?= isset($_POST['order_id']) ? htmlspecialchars($_POST['order_id']) : '' ?>"
                            >
                        </div>
                        <p class="track-helper-text">Find your Order ID in your confirmation email or SMS</p>
                        <small class="track-inline-error" id="trackOrderIdError"></small>
                    </div>

                    <button type="submit" class="track-submit-btn" id="trackSubmitBtn">
                        <span class="track-btn-text">Track Order</span>
                        <span class="track-btn-spinner" aria-hidden="true"></span>
                    </button>
                </form>

                <div class="track-help-links">
                    <span class="track-help-title">Need help?</span>
                    <a href="/contact-us">Contact Support</a>
                    <a href="https://wa.me/923116600031" target="_blank" rel="noopener noreferrer">WhatsApp Support</a>
                    <a href="/contact-us?topic=order-issues">Order Issues</a>
                </div>
            </div>

            <?php if (isset($order) && $order) : ?>
                <div class="track-success-card" id="trackResult">
                    <div class="track-success-head">
                        <span class="track-success-badge">Order Found</span>
                        <h3 class="track-success-title">Order Details</h3>
                    </div>

                    <ul class="order-details">
                        <li><strong>Status:</strong> <span class="track-status-badge"><?= htmlspecialchars($order['order_status']) ?></span></li>
                        <li><strong>Created At:</strong> <span><?= htmlspecialchars($order['created_at']) ?></span></li>
                        <li><strong>Total Price:</strong> <span><?= htmlspecialchars($order['total_price']) . ' ' . htmlspecialchars($order['currency']) ?></span></li>
                        <li><strong>Customer Name:</strong> <span><?= htmlspecialchars($order['customer_name']) ?></span></li>
                        <li><strong>Email:</strong> <span><?= htmlspecialchars($order['customer_email']) ?></span></li>
                        <li>
                            <strong>Shipping:</strong>
                            <span>
                                <?= htmlspecialchars($order['shipping_address']) ?>,
                                <?= htmlspecialchars($order['shipping_city']) ?>,
                                <?= htmlspecialchars($order['shipping_country']) ?>
                            </span>
                        </li>
                        <li><strong>Payment Method:</strong> <span><?= htmlspecialchars($order['payment_method_title']) ?></span></li>
                        <li><strong>Ordered Products:</strong> <span><pre><?= htmlspecialchars($order['ordered_product']) ?></pre></span></li>
                    </ul>
                </div>
            <?php endif; ?>
        </section>

        <section class="track-content-column" aria-label="Order tracking guidance">
            <p class="track-kicker">Order Tracking</p>
            <h1 class="track-heading"><span>Track</span> Your Order Easily</h1>
            <p class="track-subtext">
                Enter your email and order ID to check real-time order status, shipping updates, and delivery progress.
            </p>

            <div class="track-visual" aria-hidden="true">
                <svg viewBox="0 0 620 300" xmlns="http://www.w3.org/2000/svg" role="img">
                    <defs>
                        <linearGradient id="roadGradient" x1="0" y1="0" x2="1" y2="0">
                            <stop offset="0%" stop-color="#fde68a" />
                            <stop offset="100%" stop-color="#facc15" />
                        </linearGradient>
                    </defs>
                    <rect x="40" y="38" width="540" height="220" rx="26" fill="#ffffff" stroke="#e9edf3" />
                    <circle cx="120" cy="95" r="30" fill="#fef3c7" />
                    <circle cx="505" cy="195" r="38" fill="#fff7cc" />
                    <path d="M80 188c65-38 138-54 220-44 76 10 137 32 220 15" stroke="url(#roadGradient)" stroke-width="12" fill="none" stroke-linecap="round"/>
                    <circle cx="96" cy="180" r="12" fill="#facc15" />
                    <circle cx="508" cy="162" r="12" fill="#facc15" />
                    <rect x="254" y="128" width="96" height="55" rx="8" fill="#111827" />
                    <rect x="287" y="108" width="45" height="25" rx="4" fill="#374151" />
                    <circle cx="280" cy="188" r="11" fill="#111827" />
                    <circle cx="335" cy="188" r="11" fill="#111827" />
                    <rect x="136" y="94" width="70" height="50" rx="6" fill="#facc15" stroke="#d4a700" />
                    <path d="M148 110h46M148 122h32" stroke="#111827" stroke-width="3" stroke-linecap="round"/>
                </svg>
            </div>

            <ul class="track-feature-list">
                <li>
                    <span class="track-feature-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M5 12.5l4 4L19 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <span>Real-time order tracking</span>
                </li>
                <li>
                    <span class="track-feature-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M5 12.5l4 4L19 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <span>Fast delivery updates</span>
                </li>
                <li>
                    <span class="track-feature-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M5 12.5l4 4L19 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <span>Secure order lookup</span>
                </li>
                <li>
                    <span class="track-feature-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M5 12.5l4 4L19 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <span>Works for all orders</span>
                </li>
            </ul>

            <p class="track-trust">Trusted by 10,000+ customers across Pakistan</p>
        </section>
    </div>
</div>

<style>
.track-page {
    position: relative;
    overflow: hidden;
    padding: 46px 16px 60px;
    background: linear-gradient(145deg, #f4f6fa 0%, #fbfcff 50%, #eef2f8 100%);
}

.track-page::before,
.track-page::after {
    content: "";
    position: absolute;
    border-radius: 50%;
    filter: blur(2px);
    pointer-events: none;
}

.track-page::before {
    width: 340px;
    height: 340px;
    left: -120px;
    top: -110px;
    background: radial-gradient(circle, rgba(255, 215, 0, 0.25) 0%, rgba(255, 215, 0, 0) 70%);
}

.track-page::after {
    width: 260px;
    height: 260px;
    right: -90px;
    bottom: -100px;
    background: radial-gradient(circle, rgba(17, 24, 39, 0.08) 0%, rgba(17, 24, 39, 0) 70%);
}

.track-shell {
    position: relative;
    z-index: 1;
    max-width: 1180px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr;
    gap: 24px;
}

.track-form-column {
    order: 1;
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.track-content-column {
    order: 2;
    padding: 22px 24px 18px;
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.72);
    backdrop-filter: blur(5px);
    border: 1px solid rgba(255, 255, 255, 0.85);
    box-shadow: 0 18px 45px rgba(17, 24, 39, 0.13);
}

.track-kicker {
    margin: 0;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: #5b6476;
}

.track-heading {
    margin: 4px 0 8px;
    font-size: clamp(28px, 5vw, 40px);
    line-height: 1.18;
    color: #111827;
}

.track-heading span {
    color: #facc15;
}

.track-subtext {
    margin: 0;
    color: #4b5563;
    font-size: 15px;
    line-height: 1.55;
    max-width: 640px;
}

.track-visual {
    margin-top: 12px;
    border-radius: 16px;
    overflow: hidden;
    border: 1px solid #e6ebf2;
    background: #ffffff;
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.4);
}

.track-visual svg {
    display: block;
    width: 100%;
    height: auto;
    max-height: 210px;
}

.track-feature-list {
    list-style: none;
    margin: 14px 0 0;
    padding: 0;
    display: grid;
    gap: 6px;
}

.track-feature-list li {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    font-size: 14px;
    line-height: 1.35;
    font-weight: 700;
    color: #111111 !important;
}

.track-feature-list li span:last-child {
    color: #111111 !important;
    font-style: normal;
}

.track-feature-icon {
    width: 22px;
    height: 22px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #111111;
    background: #facc15;
    flex: 0 0 22px;
}

.track-feature-icon svg {
    width: 14px;
    height: 14px;
}

.track-trust {
    margin: 12px 0 0;
    font-size: 13px;
    font-weight: 600;
    color: #333333;
}

.track-form-card {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 18px 45px rgba(17, 24, 39, 0.13);
    padding: 30px;
    border: 1px solid #edf0f5;
}

.track-form-title {
    margin: 0 0 16px;
    color: #111827;
    font-size: 26px;
}

.track-form-error {
    margin: 0 0 14px;
    padding: 10px 12px;
    border-radius: 10px;
    border: 1px solid #fecaca;
    background: #fef2f2;
    color: #b91c1c;
    font-size: 14px;
}

.track-field-group {
    margin-bottom: 13px;
}

.track-field-group label {
    display: block;
    margin-bottom: 7px;
    font-size: 14px;
    font-weight: 600;
    color: #1f2937;
}

.track-input-wrap {
    position: relative;
}

.track-input-icon {
    position: absolute;
    top: 50%;
    left: 14px;
    transform: translateY(-50%);
    color: #6b7280;
    width: 19px;
    height: 19px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.track-input-icon svg {
    width: 19px;
    height: 19px;
}

.track-input {
    width: 100%;
    height: 50px;
    border-radius: 10px;
    border: 1px solid #d8dee8;
    padding: 0 14px 0 44px;
    font-size: 15px;
    background: #fff;
    color: #111827;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.track-input:focus {
    outline: none;
    border-color: #facc15;
    box-shadow: 0 0 0 4px rgba(250, 204, 21, 0.24);
}

.track-input.is-invalid,
.track-has-error .track-input {
    border-color: #ef4444;
}

.track-inline-error {
    min-height: 18px;
    margin-top: 3px;
    display: block;
    font-size: 12px;
    color: #b91c1c;
}

.track-helper-text {
    margin: 6px 0 0;
    font-size: 12px;
    color: #6b7280;
}

.track-submit-btn {
    width: 100%;
    height: 50px;
    border: none;
    border-radius: 12px;
    background: #ffd700;
    color: #111111;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    margin-top: 4px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: transform 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
}

.track-submit-btn:hover {
    background: #e9c300;
    transform: scale(1.015);
    box-shadow: 0 10px 24px rgba(201, 161, 7, 0.35);
}

.track-submit-btn:active {
    transform: scale(0.99);
}

.track-btn-spinner {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    border: 2px solid rgba(17, 17, 17, 0.25);
    border-top-color: #111111;
    display: none;
    animation: track-spin 0.7s linear infinite;
}

.track-submit-btn.is-loading {
    pointer-events: none;
    opacity: 0.95;
}

.track-submit-btn.is-loading .track-btn-spinner {
    display: inline-block;
}

.track-help-links {
    margin-top: 18px;
    border-top: 1px solid #edf1f6;
    padding-top: 16px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px 14px;
    align-items: center;
}

.track-help-title {
    color: #111827;
    font-weight: 700;
    margin-right: 2px;
}

.track-help-links a {
    color: #374151;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
}

.track-help-links a:hover {
    color: #111827;
    text-decoration: underline;
}

.track-success-card {
    background: #ffffff;
    border: 1px solid #eceff4;
    border-radius: 16px;
    box-shadow: 0 16px 36px rgba(15, 23, 42, 0.09);
    padding: 22px;
}

.track-success-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
}

.track-success-badge {
    font-size: 12px;
    color: #111827;
    background: #fff8cc;
    border: 1px solid #fde68a;
    padding: 5px 10px;
    border-radius: 999px;
    font-weight: 700;
}

.track-success-title {
    margin: 0;
    font-size: 20px;
    color: #111827;
}

.order-details {
    list-style: none;
    margin: 0;
    padding: 0;
}

.order-details li {
    margin: 0;
    padding: 11px 0;
    border-bottom: 1px dashed #e4e9f1;
    display: grid;
    grid-template-columns: 130px 1fr;
    gap: 14px;
    align-items: start;
}

.order-details li:last-child {
    border-bottom: none;
}

.order-details strong {
    color: #4b5563;
    font-size: 14px;
}

.order-details span {
    color: #111827;
    font-size: 14px;
    word-break: break-word;
}

.order-details pre {
    margin: 0;
    font: inherit;
    color: inherit;
    white-space: pre-wrap;
    word-break: break-word;
}

.track-status-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 999px;
    background: #fef9c3;
    border: 1px solid #fde68a;
    font-weight: 600;
}

@keyframes track-spin {
    to {
        transform: rotate(360deg);
    }
}

@media (min-width: 992px) {
    .track-shell {
        grid-template-columns: minmax(0, 3fr) minmax(0, 2fr);
        gap: 28px;
        align-items: start;
    }

    .track-content-column {
        order: 1;
        padding: 22px 24px 18px;
    }

    .track-form-column {
        order: 2;
    }
}

@media (max-width: 767px) {
    .track-page {
        padding: 26px 12px 34px;
    }

    .track-content-column,
    .track-form-card,
    .track-success-card {
        padding: 20px;
        border-radius: 14px;
    }

    .track-visual svg {
        max-height: 165px;
    }

    .track-heading {
        font-size: 30px;
    }

    .track-subtext {
        font-size: 15px;
        line-height: 1.6;
    }

    .track-feature-list {
        gap: 8px;
    }

    .track-help-links {
        margin-top: 16px;
    }

    .order-details li {
        grid-template-columns: 1fr;
        gap: 6px;
    }
}
</style>

<script>
(function () {
    const form = document.getElementById('trackOrderForm');

    if (!form) {
        return;
    }

    const emailInput = document.getElementById('track-email');
    const orderIdInput = document.getElementById('track-order-id');
    const emailError = document.getElementById('trackEmailError');
    const orderIdError = document.getElementById('trackOrderIdError');
    const submitBtn = document.getElementById('trackSubmitBtn');

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    function setFieldError(input, slot, message) {
        input.classList.add('is-invalid');
        if (slot) slot.textContent = message;
    }

    function clearFieldError(input, slot) {
        input.classList.remove('is-invalid');
        if (slot) slot.textContent = '';
    }

    emailInput.addEventListener('input', function () {
        clearFieldError(emailInput, emailError);
    });

    orderIdInput.addEventListener('input', function () {
        clearFieldError(orderIdInput, orderIdError);
    });

    form.addEventListener('submit', function (event) {
        let hasError = false;

        clearFieldError(emailInput, emailError);
        clearFieldError(orderIdInput, orderIdError);

        if (!emailInput.value.trim()) {
            setFieldError(emailInput, emailError, 'Email is required.');
            hasError = true;
        } else if (!emailRegex.test(emailInput.value.trim())) {
            setFieldError(emailInput, emailError, 'Enter a valid email address.');
            hasError = true;
        }

        if (!orderIdInput.value.trim()) {
            setFieldError(orderIdInput, orderIdError, 'Order ID is required.');
            hasError = true;
        }

        if (hasError) {
            event.preventDefault();
            return;
        }

        submitBtn.classList.add('is-loading');
        submitBtn.setAttribute('aria-busy', 'true');
        submitBtn.disabled = true;
    });
})();
</script>

<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>