<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
$pageTitle = "Checkout - Phones Dukan";
$metaDescription = "Review your order and complete your checkout at Phones Dukan. Enjoy fast processing and reliable delivery across Pakistan.";
$metaRobots = "noindex, nofollow"; // Optional; default is good
require_once __DIR__ . '/../../../database/db.php';
require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once __DIR__ . '/../../Controllers/OrderController.php';

$database = new Database();
$conn = $database->getConnection();
$orderController = new OrderController();

$deliveryCharge = 149;  // Flat delivery charge of Rs. 149
$session_id = session_id();
$cartItems = [];
$orderResult = null;

try {
    $stmt = $conn->prepare('SELECT cart.product_id, cart.quantity, cart.subtotal, cart.payment_method, products.product_name, product_images.image_url
                            FROM cart
                            JOIN products ON cart.product_id = products.product_id
                            LEFT JOIN product_images ON cart.product_id = product_images.product_id AND product_images.is_primary = 1
                            WHERE cart.session_id = ?');
    $stmt->execute([$session_id]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage(), 3, __DIR__ . '/../../../logs/orders.log');
    echo 'Database error: ' . htmlspecialchars($e->getMessage());
    exit();
}

if (empty($cartItems)) {
    $cartEmpty = true;
}

$selectedPaymentMethod = !empty($cartItems) ? $cartItems[0]['payment_method'] ?? 'cod' : 'cod';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    $requiredFields = ['customer_name', 'customer_email', 'customer_phone', 'shipping_address', 'shipping_city', 'shipping_country'];

    foreach ($requiredFields as $field) {
        $_POST[$field] = trim($_POST[$field]);  // Remove spaces
        if (empty($_POST[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
        }
    }

    if (!empty($_POST['customer_email']) && !filter_var($_POST['customer_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    }

    if (!empty($_POST['customer_phone']) && !preg_match('/^03\d{9}$/', $_POST['customer_phone'])) {
        $errors[] = 'Phone number must be 11 digits and start with 03.';
    }

    // New: Validate payment method and screenshot for prepaid
    $paymentMethod = $_POST['payment_method'] ?? $selectedPaymentMethod; // Fallback to cart's choice
    if ($paymentMethod === 'prepaid') {
        if (empty($_FILES['payment_screenshot']['name']) || $_FILES['payment_screenshot']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Payment screenshot is required for prepaid.';
        } else {
            $fileType = mime_content_type($_FILES['payment_screenshot']['tmp_name']);
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            if (!in_array($fileType, $allowedTypes)) {
                $errors[] = 'Screenshot must be a JPG or PNG image.';
            }
            if ($_FILES['payment_screenshot']['size'] > 5 * 1024 * 1024) { // 5MB
                $errors[] = 'Screenshot too large (max 5MB).';
            }
        }
    }

    if (!empty($errors)) {
        echo json_encode(['status' => 'error', 'messages' => $errors]);
        exit();
    }

    // Sanitize inputs before inserting into DB
    $customer_name = htmlspecialchars($_POST['customer_name']);
    $customer_email = htmlspecialchars($_POST['customer_email']);
    $customer_phone = htmlspecialchars($_POST['customer_phone']);
    $shipping_address = htmlspecialchars($_POST['shipping_address']);
    $shipping_city = htmlspecialchars($_POST['shipping_city']);
    $shipping_country = htmlspecialchars($_POST['shipping_country']);

    $orderData = [
        'user_id' => 1,
        'total_price' => array_sum(array_column($cartItems, 'subtotal')) + $deliveryCharge,  // Add delivery charge to total price
        'order_status' => 'pending',
        'created_at' => date('Y-m-d H:i:s'),
        'currency' => 'PKR',
        'payment_method' => $paymentMethod,
        'payment_method_title' => $paymentMethod === 'cod' ? 'Cash on Delivery' : 'Prepaid (Bank Transfer)',
        'customer_name' => $customer_name,
        'customer_email' => $customer_email,
        'customer_phone' => $customer_phone,
        'shipping_address' => $shipping_address,
        'shipping_city' => $shipping_city,
        'shipping_country' => $shipping_country,
        'customer_note' => htmlspecialchars($_POST['customer_note'] ?? ''),
        'applied_coupons' => ''
    ];

    // Enhanced: Handle screenshot upload for prepaid (with logging + fallback)
    $screenshotPath = null;
    $tempPath = null;
    if ($paymentMethod === 'prepaid' && !empty($_FILES['payment_screenshot']['name'])) {
        $uploadDir = __DIR__ . '/../../../private/screenshots/';
        error_log('🔍 Upload Debug: Starting upload to ' . $uploadDir . ' | File: ' . $_FILES['payment_screenshot']['name'] . ' | Size: ' . $_FILES['payment_screenshot']['size'] . ' | Tmp: ' . $_FILES['payment_screenshot']['tmp_name']);

        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                $err = error_get_last();
                error_log('❌ Dir Creation Failed: ' . ($err['message'] ?? 'Unknown'));
                $errors[] = 'Server error: Unable to create upload folder.';
                echo json_encode(['status' => 'error', 'messages' => $errors]);
                exit();
            }
            error_log('✅ Dir created: ' . $uploadDir);
        }

        // Create .htaccess to deny public access
        $htaccessPath = $uploadDir . '.htaccess';
        if (!file_exists($htaccessPath)) {
            if (!file_put_contents($htaccessPath, "Deny from all\n")) {
                $err = error_get_last();
                error_log('⚠️ .htaccess Creation Warning (non-fatal): ' . ($err['message'] ?? 'Unknown'));
                // Proceed anyway—security fallback, but log it
            } else {
                error_log('✅ .htaccess created');
            }
        }

        $fileExt = strtolower(pathinfo($_FILES['payment_screenshot']['name'], PATHINFO_EXTENSION));
        $targetExt = ($fileExt === 'png') ? 'jpg' : $fileExt;
        $fileName = 'order_temp_' . time() . '.' . $targetExt;
        $tempPath = $uploadDir . $fileName;

        // Try move first, fallback to copy if fails (some hosts prefer it)
        $uploadSuccess = move_uploaded_file($_FILES['payment_screenshot']['tmp_name'], $tempPath);
        if (!$uploadSuccess) {
            error_log('❌ Move Failed: Trying copy fallback...');
            $uploadSuccess = copy($_FILES['payment_screenshot']['tmp_name'], $tempPath);
        }

        if (!$uploadSuccess) {
            $err = error_get_last();
            error_log('❌ Upload Failed Completely: ' . ($err['message'] ?? 'Unknown') . ' | Perms on ' . $uploadDir . ': ' . substr(sprintf('%o', fileperms($uploadDir)), -4));
            $errors[] = 'Failed to upload screenshot (perms issue—check logs).';
            echo json_encode(['status' => 'error', 'messages' => $errors]);
            exit();
        }

        error_log('✅ Upload Success: File saved to ' . $tempPath . ' | Size: ' . filesize($tempPath));

        // Convert PNG to JPG if needed (GD fallback with error check)
        if ($fileExt === 'png') {
            $image = imagecreatefrompng($tempPath);
            if ($image) {
                $jpgPath = $uploadDir . 'order_temp_' . time() . '.jpg';
                if (imagejpeg($image, $jpgPath, 90)) {
                    imagedestroy($image);
                    unlink($tempPath);
                    $tempPath = $jpgPath;
                    $fileName = basename($jpgPath);
                    error_log('✅ PNG converted to JPG: ' . $jpgPath);
                } else {
                    error_log('⚠️ PNG Conversion Failed (non-fatal—using original)');
                }
            } else {
                error_log('❌ PNG Load Failed');
            }
        }

        $screenshotPath = 'private/screenshots/' . $fileName; // Relative path for DB
    }

    // Debugging
    error_log('🔍 Checkout Data: ' . json_encode($orderData));

    $orderResult = $orderController->createOrder($orderData, $cartItems, $screenshotPath ? ['path' => $screenshotPath, 'temp_file' => $tempPath] : null);

    if ($orderResult['success']) {
        // If prepaid, rename screenshot with order_id
        if ($paymentMethod === 'prepaid' && $screenshotPath && $tempPath && file_exists($tempPath)) {
            $newFileName = 'order_' . $orderResult['order_id'] . '_' . time() . '.jpg';
            $newPath = dirname($tempPath) . '/' . $newFileName;
            if (rename($tempPath, $newPath)) {
                error_log('✅ Screenshot Renamed: ' . $newPath);
                // Update DB with final path
                $updateStmt = $conn->prepare('UPDATE orders SET payment_screenshot = ? WHERE order_id = ?');
                $updateStmt->execute(['private/screenshots/' . $newFileName, $orderResult['order_id']]);
            } else {
                error_log('⚠️ Rename Failed (keeping temp: ' . $tempPath . ')');
            }
        } elseif ($tempPath && file_exists($tempPath)) {
            unlink($tempPath); // Cleanup unused temp
        }

        // Clear cart after successful order
        $clearStmt = $conn->prepare('DELETE FROM cart WHERE session_id = ?');
        $clearStmt->execute([$session_id]);

        // Redirect to thank you page (MATCH ROUTER PATTERN)
        header('Location: /thankyou/order_id=' . $orderResult['order_id']);
        exit();
    } else {
        $errorMessage = $orderResult['message'];
        // Cleanup temp file if failed
        if (isset($tempPath) && file_exists($tempPath)) {
            unlink($tempPath);
            error_log('🧹 Temp Cleanup: ' . $tempPath);
        }
    }
}

// Template variables
$totalCartValue        = !empty($cartItems) ? array_sum(array_column($cartItems, 'subtotal')) : 0;
$totalPriceWithDelivery = $totalCartValue + $deliveryCharge;
?>

<div class="checkout-page">

    <!-- ── Page header ── -->
    <div class="co-page-header">
        <h1 class="co-page-title">Checkout</h1>
        <p class="co-page-subtitle">Complete your details below to place your order securely.</p>
    </div>

    <div class="checkout-container">

        <?php if (isset($cartEmpty) && $cartEmpty): ?>
        <div class="co-empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#bbb" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            <p>Your cart is empty.</p>
            <a href="/shop" class="co-empty-btn">Continue Shopping</a>
        </div>
        <?php else: ?>

        <form id="checkoutForm" method="POST" enctype="multipart/form-data" novalidate onsubmit="return validateAll()">
            <input type="hidden" name="payment_method" value="<?php echo htmlspecialchars($selectedPaymentMethod); ?>">

            <div class="checkout-content">

                <!-- ════════════════════════════════════════
                     LEFT  — Customer / Shipping / Payment
                ════════════════════════════════════════ -->
                <div class="form-section">

                    <!-- ── Card 1: Customer Information ── -->
                    <div class="co-card customer-container">
                        <div class="co-card-header">
                            <svg class="co-card-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <h2>Customer Information</h2>
                        </div>

                        <div class="form-group">
                            <label for="customer_name">Full Name <span class="co-req">*</span></label>
                            <div class="co-input-wrap">
                                <span class="co-input-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                </span>
                                <input type="text" id="customer_name" name="customer_name" required placeholder="Enter your full name">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="customer_email">Email Address <span class="co-req">*</span></label>
                            <div class="co-input-wrap">
                                <span class="co-input-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                </span>
                                <input type="email" id="customer_email" name="customer_email" required placeholder="Enter your email">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="customer_phone">Phone Number <span class="co-req">*</span></label>
                            <div class="co-input-wrap">
                                <span class="co-input-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.4 2 2 0 0 1 3.6 1.22h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.91 8.77a16 16 0 0 0 6.15 6.15l.95-.95a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                </span>
                                <input type="tel" id="customer_phone" name="customer_phone" required placeholder="03XXXXXXXXX" maxlength="11" pattern="^03\d{9}$">
                            </div>
                        </div>
                    </div><!-- /.co-card customer-container -->

                    <!-- ── Card 2: Shipping Information ── -->
                    <div class="co-card shipping-container">
                        <div class="co-card-header">
                            <svg class="co-card-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                            <h2>Shipping Information</h2>
                        </div>

                        <div class="form-group">
                            <label for="shipping_address">Shipping Address <span class="co-req">*</span></label>
                            <div class="co-input-wrap co-input-wrap--area">
                                <span class="co-input-icon co-input-icon--top">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                </span>
                                <textarea id="shipping_address" name="shipping_address" required placeholder="House no., street, area, landmark..." rows="3"></textarea>
                            </div>
                        </div>

                        <div class="co-form-row">
                            <div class="form-group">
                                <label for="shipping_city">City <span class="co-req">*</span></label>
                                <div class="co-input-wrap">
                                    <span class="co-input-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                                    </span>
                                    <input type="text" id="shipping_city" name="shipping_city" required placeholder="Your city">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="co-country-trigger">Country <span class="co-req">*</span></label>

                                <!-- Real select kept hidden — form submission + JS validation read from it -->
                                <select id="shipping_country" name="shipping_country" required class="co-select-hidden" aria-hidden="true" tabindex="-1">
                                    <option value="">Select Country</option>
                                    <option value="Pakistan">Pakistan</option>
                                </select>

                                <!-- Custom dropdown wrapper -->
                                <div class="co-custom-select" id="coCountrySelect">

                                    <!-- Trigger — looks like a styled input -->
                                    <div class="co-select-trigger" id="co-country-trigger"
                                         role="combobox" aria-expanded="false"
                                         aria-haspopup="listbox" aria-controls="co-country-panel"
                                         tabindex="0">
                                        <span class="co-input-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                                        </span>
                                        <span class="co-select-display" id="co-country-display">Select Country</span>
                                        <span class="co-select-caret" id="co-country-caret">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                                        </span>
                                    </div>

                                    <!-- Dropdown panel -->
                                    <div class="co-select-panel" id="co-country-panel" role="listbox" aria-label="Country">
                                        <div class="co-select-option co-select-option--placeholder" data-value="" role="option" aria-selected="true">Select Country</div>
                                        <div class="co-select-option" data-value="Pakistan" role="option" aria-selected="false">Pakistan</div>
                                    </div>

                                </div><!-- /.co-custom-select -->
                            </div>
                        </div>
                    </div><!-- /.co-card shipping-container -->

                    <!-- ── Card 3: Payment Information ── -->
                    <div class="co-card payment-container">
                        <div class="co-card-header">
                            <svg class="co-card-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                            <h2>Payment Information</h2>
                        </div>

                        <div class="payment-method">
                            <div class="co-payment-pill">
                                <div class="co-payment-pill-icon">
                                    <?php if ($selectedPaymentMethod === 'cod'): ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                                    <?php else: ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                                    <?php endif; ?>
                                </div>
                                <div class="co-payment-pill-body">
                                    <p class="co-payment-name"><?php echo $selectedPaymentMethod === 'cod' ? 'Cash on Delivery' : 'Bank Transfer (Prepaid)'; ?></p>
                                    <p class="co-payment-hint"><?php echo $selectedPaymentMethod === 'cod' ? 'Pay when your order arrives at your door.' : 'Transfer funds to confirm your order.'; ?></p>
                                </div>
                                <span class="co-payment-check">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                </span>
                            </div>

                            <?php if ($selectedPaymentMethod === 'prepaid'): ?>
                            <div id="prepaid-details" style="display:block; width:100%; margin-top:16px; padding:16px; background:#fafafa; border:1px solid #e5e7eb; border-radius:10px;">
                                <h4 style="margin:0 0 14px; font-size:13px; font-weight:700; color:#111; text-transform:uppercase; letter-spacing:0.05em;">Bank Transfer Details</h4>
                                <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:14px;">
                                    <li style="padding:12px; background:#fff; border:1px solid #e5e7eb; border-radius:8px; font-size:13px; line-height:1.7;">
                                        <strong>Bank:</strong> Easypaisa<br>
                                        <strong>Account Holder:</strong> Nayyer Sultan<br>
                                        <div class="copy-wrapper" style="display:inline-flex; align-items:center; gap:6px; margin-top:4px;">
                                            <strong>Account:</strong>
                                            <span class="account-number" data-copy="03116600031" style="font-family:monospace; background:#f5f5f5; padding:2px 8px; border-radius:4px;">03116600031</span>
                                            <img src="/public/assets/images/copy-icon.svg" alt="Copy" class="copy-icon" style="cursor:pointer;width:18px;height:18px;vertical-align:middle;opacity:0.6;" onclick="copyAccount(this)">
                                        </div>
                                    </li>
                                    <li style="padding:12px; background:#fff; border:1px solid #e5e7eb; border-radius:8px; font-size:13px; line-height:1.7;">
                                        <strong>Bank:</strong> Jazz Cash<br>
                                        <strong>Account Holder:</strong> Nayyer Sultan<br>
                                        <div class="copy-wrapper" style="display:inline-flex; align-items:center; gap:6px; margin-top:4px;">
                                            <strong>Account:</strong>
                                            <span class="account-number" data-copy="03116600031" style="font-family:monospace; background:#f5f5f5; padding:2px 8px; border-radius:4px;">03116600031</span>
                                            <img src="/public/assets/images/copy-icon.svg" alt="Copy" class="copy-icon" style="cursor:pointer;width:18px;height:18px;vertical-align:middle;opacity:0.6;" onclick="copyAccount(this)">
                                        </div>
                                    </li>
                                    <li style="padding:12px; background:#fff; border:1px solid #e5e7eb; border-radius:8px; font-size:13px; line-height:1.7;">
                                        <strong>Bank:</strong> Faysal Bank Limited<br>
                                        <strong>Account Holder:</strong> Nayyer Sultan<br>
                                        <div class="copy-wrapper" style="display:inline-flex; align-items:center; gap:6px; margin-top:4px;">
                                            <strong>Account:</strong>
                                            <span class="account-number" data-copy="3152301000002893" style="font-family:monospace; background:#f5f5f5; padding:2px 8px; border-radius:4px;">3152301000002893</span>
                                            <img src="/public/assets/images/copy-icon.svg" alt="Copy" class="copy-icon" style="cursor:pointer;width:18px;height:18px;vertical-align:middle;opacity:0.6;" onclick="copyAccount(this)">
                                        </div>
                                    </li>
                                </ul>
                                <div class="form-group" style="margin-top:16px;">
                                    <label for="payment_screenshot" style="font-size:13px; font-weight:600; color:#111;">Payment Screenshot <span class="co-req">*</span></label>
                                    <input type="file" id="payment_screenshot" name="payment_screenshot" accept="image/*" required style="margin-top:6px; width:100%; font-size:13px;">
                                </div>
                            </div>
                            <?php endif; ?>
                        </div><!-- /.payment-method -->

                        <div class="order-notes">
                            <div class="form-group">
                                <label for="customer_note">Order Notes <span class="co-optional">(optional)</span></label>
                                <div class="co-input-wrap co-input-wrap--area">
                                    <span class="co-input-icon co-input-icon--top">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                    </span>
                                    <textarea id="customer_note" name="customer_note" placeholder="Any special instructions for your order..."></textarea>
                                </div>
                            </div>
                        </div>

                    </div><!-- /.co-card payment-container -->

                </div><!-- /.form-section -->

                <!-- ════════════════════════════════════════
                     RIGHT  — Order Summary + CTA
                ════════════════════════════════════════ -->
                <div class="order-section">
                    <div class="order-summary co-summary-card">

                        <h3>Order Summary</h3>

                        <!-- Product rows -->
                        <div class="co-summary-products">
                            <?php foreach ($cartItems as $item): ?>
                            <div class="co-summary-product">
                                <img
                                    src="<?php echo htmlspecialchars($item['image_url']); ?>"
                                    alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                    class="co-summary-img">
                                <div class="co-summary-info">
                                    <p class="co-summary-name"><?php echo htmlspecialchars($item['product_name']); ?></p>
                                    <p class="co-summary-meta">Qty: <?php echo htmlspecialchars($item['quantity']); ?></p>
                                </div>
                                <span class="co-summary-price">Rs. <?php echo number_format($item['subtotal'], 2); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pricing breakdown -->
                        <div class="co-pricing-rows">
                            <div class="co-pricing-row">
                                <span class="co-pricing-label">Subtotal</span>
                                <span class="co-pricing-value">Rs. <?php echo number_format($totalCartValue, 2); ?></span>
                            </div>
                            <div class="co-pricing-row">
                                <span class="co-pricing-label">Delivery</span>
                                <span class="co-pricing-value">Rs. <?php echo number_format($deliveryCharge, 2); ?></span>
                            </div>
                        </div>

                        <div class="co-pricing-divider"></div>

                        <div class="co-pricing-total">
                            <span class="co-pricing-total-label">Total</span>
                            <span class="co-pricing-total-value">Rs. <?php echo number_format($totalPriceWithDelivery, 2); ?></span>
                        </div>

                        <!-- Place Order CTA -->
                        <button type="submit" class="submit-button">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                            Place Order
                        </button>

                        <!-- Trust signals -->
                        <div class="co-trust">
                            <div class="co-trust-item">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                <span>Secure Checkout</span>
                            </div>
                            <div class="co-trust-item">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                                <span>Cash on Delivery</span>
                            </div>
                            <div class="co-trust-item">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                                <span>Fast Delivery</span>
                            </div>
                        </div>

                    </div><!-- /.co-summary-card -->
                </div><!-- /.order-section -->

            </div><!-- /.checkout-content -->
        </form>

        <?php endif; ?>

    </div><!-- /.checkout-container -->
</div><!-- /.checkout-page -->

<!-- ── Validation Modal ── -->
<div id="validationModal" class="co-modal" style="display:none;">
    <div class="co-modal-overlay" onclick="closeModal()"></div>
    <div class="co-modal-box">
        <div class="co-modal-icon-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#f7cf04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        </div>
        <p id="modalMessage">Invalid input!</p>
        <button class="co-modal-btn" onclick="closeModal()">Got it</button>
    </div>
</div>

<!-- ── Phone Modal ── -->
<div id="phoneModal" class="co-modal" style="display:none;">
    <div class="co-modal-overlay" onclick="closeModal()"></div>
    <div class="co-modal-box">
        <div class="co-modal-icon-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#f7cf04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        </div>
        <p>Please enter a valid 11-digit phone number starting with "03".</p>
        <button class="co-modal-btn" onclick="closeModal()">Got it</button>
    </div>
</div>

<?php if (!empty($errorMessage)): ?>
<div class="co-server-error">
    <?php echo htmlspecialchars($errorMessage); ?>
</div>
<?php endif; ?>

<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>
