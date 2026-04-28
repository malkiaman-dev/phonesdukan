// Combined validation for all fields before form submission
function validateAll() {
    let name = document.querySelector('[name="customer_name"]').value.trim();
    let phone = document.getElementById("customer_phone").value.trim();
    let email = document.getElementById("customer_email").value.trim();
    let address = document.querySelector('textarea[name="shipping_address"]').value.trim();
    let city = document.querySelector('input[name="shipping_city"]').value.trim();
    let country = document.querySelector('select[name="shipping_country"]').value.trim();
    let paymentMethod = document.querySelector('input[name="payment_method"]').value; // From hidden
    let screenshot = document.getElementById("payment_screenshot")?.value; // May not exist for COD

    let nameRegex = /^[A-Za-z\s]{3,50}$/; // Allows letters and spaces, 3 to 50 characters
    let phoneRegex = /^03\d{9}$/; // Must start with 03 and be 11 digits
    let emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/; // Simple email validation

    let errors = [];

    if (!nameRegex.test(name)) {
        errors.push("❌ Please enter a valid name (only letters and spaces, 3-50 characters).");
    }

    if (!emailRegex.test(email)) {
        errors.push("❌ Please enter a valid email address.");
    }

    if (!phoneRegex.test(phone)) {
        errors.push("❌ Please enter a valid 11-digit phone number starting with '03'.");
    }

    if (!address) {
        errors.push("❌ Shipping Address is required.");
    }

    if (!city) {
        errors.push("❌ City is required.");
    }

    if (!country) {
        errors.push("❌ Country is required.");
    }

    // Updated: Validate screenshot only if prepaid (from hidden)
    if (paymentMethod === 'prepaid' && !screenshot) {
        errors.push("❌ Payment screenshot is required for prepaid.");
    }

    if (errors.length > 0) {
        showModal(errors.join("\n"));
        return false; // Prevent form submission
    }

    return true; // Allow form submission
}

function showModal(message) {
    document.getElementById("modalMessage").innerText = message;
    document.getElementById("validationModal").style.display = "block";
}

function closeModal() {
    document.getElementById("validationModal").style.display = "none";
    document.getElementById("phoneModal").style.display = "none";
}

function copyAccount(icon) {
    // Robust find: Look for nearest .account-number (handles nesting like your <ol>)
    const accountSpan = icon.closest('li').querySelector('.account-number');
    const accountNum = accountSpan ? accountSpan.dataset.copy : '';
    if (!accountNum) {
        console.error('No account number found—check data-copy attr');
        return;
    }

    console.log('Attempting copy:', accountNum); // Debug: See in DevTools

    // Modern clipboard (desktop + mobile)
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(accountNum).then(() => {
            console.log('Copied:', accountNum);
            feedbackSuccess(icon);
        }).catch(err => {
            console.error('Clipboard API failed:', err);
            fallbackCopy(accountNum, icon);
        });
    } else {
        // Fallback for older/HTTP
        fallbackCopy(accountNum, icon);
    }
}

function feedbackSuccess(icon) {
    const originalTitle = icon.title || 'Copy Account Number';
    icon.title = 'Copied!';
    // Mobile haptic buzz (if supported)
    if (navigator.vibrate) {
        navigator.vibrate(50); // Quick vibe
    }
    setTimeout(() => { icon.title = originalTitle; }, 2000);
}

function copyAccount(icon) {
    // Robust find: Look for nearest .account-number (handles nesting like your <ol>)
    const accountSpan = icon.closest('li').querySelector('.account-number');
    const accountNum = accountSpan ? accountSpan.dataset.copy : '';
    if (!accountNum) {
        return;
    }

    // Modern clipboard (desktop + mobile)
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(accountNum).then(() => {
            feedbackSuccess(icon);
        }).catch(err => {
            fallbackCopy(accountNum, icon);
        });
    } else {
        // Fallback for older/HTTP
        fallbackCopy(accountNum, icon);
    }
}

function feedbackSuccess(icon) {
    const originalTitle = icon.title || 'Copy Account Number';
    icon.title = 'Copied!';
    // Mobile haptic buzz (if supported)
    if (navigator.vibrate) {
        navigator.vibrate(50); // Quick vibe
    }
    setTimeout(() => { icon.title = originalTitle; }, 2000);
}

function copyAccount(icon) {
    // Robust find: Look for nearest .account-number
    const accountSpan = icon.closest('li').querySelector('.account-number');
    const accountNum = accountSpan ? accountSpan.dataset.copy : '';
    if (!accountNum) {
        return;
    }

    // Modern clipboard (desktop + mobile)
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(accountNum).then(() => {
            showCopyFeedback(icon);
        }).catch(err => {
            fallbackCopy(accountNum, icon);
        });
    } else {
        // Fallback for older/HTTP
        fallbackCopy(accountNum, icon);
    }
}

function showCopyFeedback(icon) {
    // Create/remove tooltip
    let tooltip = icon.parentNode.querySelector('.copied-tooltip');
    if (!tooltip) {
        tooltip = document.createElement('span');
        tooltip.className = 'copied-tooltip';
        tooltip.textContent = 'Copied';
        icon.parentNode.appendChild(tooltip); // Append to wrapper
    }
    tooltip.classList.add('show');

    // Green icon tint
    icon.classList.add('copied');

    // Mobile haptic buzz (if supported)
    if (navigator.vibrate) {
        navigator.vibrate(50); // Quick vibe
    }

    // Reset after 2s
    setTimeout(() => {
        tooltip.classList.remove('show');
        setTimeout(() => { icon.parentNode.removeChild(tooltip); }, 300); // Clean up
        icon.classList.remove('copied');
    }, 2000);
}

function fallbackCopy(text, icon) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px'; // Off-screen
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    try {
        const successful = document.execCommand('copy');
        if (successful) {
            showCopyFeedback(icon);
        } else {
            alert('Copy failed—select and copy manually: ' + text); // Last resort
        }
    } catch (err) {
        // Silent fail on catch
    } finally {
        document.body.removeChild(textArea);
    }
}