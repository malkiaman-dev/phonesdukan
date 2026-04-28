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
?>

<div class="checkout-container">
    <?php if (isset($cartEmpty) && $cartEmpty): ?>
        <p>Your cart is empty. <a href="/">Continue Shopping</a></p>
    <?php else: ?>
        <form id="checkoutForm" method="POST" enctype="multipart/form-data" novalidate onsubmit="return validateAll()">
            <input type="hidden" name="payment_method" value="<?php echo htmlspecialchars($selectedPaymentMethod); ?>">
            <div class="checkout-content">
                <div class="form-section">
                    <!-- Customer Information -->
                    <div class="customer-container">
                        <h2>Customer Information</h2>
                        <div class="form-group">
                            <label for="customer_name">Full Name *</label>
                            <input type="text" id="customer_name" name="customer_name" required placeholder="Enter your full name">
                        </div>
                        <div class="form-group">
                            <label for="customer_email">Email Address *</label>
                            <input type="email" id="customer_email" name="customer_email" required placeholder="Enter your email">
                        </div>
                        <div class="form-group">
                            <label for="customer_phone">Phone Number *</label>
                            <input type="tel" id="customer_phone" name="customer_phone" required placeholder="03XXXXXXXXX" maxlength="11" pattern="^03\d{9}$">
                        </div>
                    </div>

                    <!-- Shipping Information -->
                    <div class="shipping-container">
                        <h2>Shipping Information</h2>
                        <div class="form-group">
                            <label for="shipping_address">Shipping Address *</label>
                            <textarea id="shipping_address" name="shipping_address" required placeholder="Enter your full address" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="shipping_city">City *</label>
                            <input type="text" id="shipping_city" name="shipping_city" required placeholder="Enter your city">
                        </div>
                        <div class="form-group">
                            <label for="shipping_country">Country *</label>
                            <select id="shipping_country" name="shipping_country" required>
                                <option value="">Select Country</option>
                                <option value="Pakistan">Pakistan</option>
                                <!-- Add more countries if needed -->
                            </select>
                        </div>
                    </div>

                    <!-- Payment Information -->
                    <div class="payment-container">
                        <h2>Payment Information</h2>
                        <div class="payment-method">
                            <label>Selected Payment: <?php echo ucfirst(str_replace('_', ' ', $selectedPaymentMethod)); ?></label>
                            <?php if ($selectedPaymentMethod === 'prepaid'): ?>
                                <!-- Prepaid Bank Details & Screenshot (Static show for prepaid) -->
                                <div id="prepaid-details" style="display: block; width: 100%; margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                                    <h4>Bank Transfer Details:</h4>
                                    <ul style="list-style: none; padding: 0;">
    <li>
        <strong>Bank:</strong> Easypaisa<br>
        <strong>Account Holder:</strong> Nayyer Sultan<br>
        <div class="copy-wrapper">
            <strong>Account Number:</strong> 
            <span class="account-number" data-copy="03116600031">03116600031</span>
            <img src="/public/assets/images/copy-icon.svg" alt="Copy" class="copy-icon" style="cursor: pointer; width: 20px; height: 20px; margin-left: 5px; vertical-align: middle;" onclick="copyAccount(this)">
        </div>
    </li>
    <li>
        <strong>Bank:</strong> Jazz Cash<br>
        <strong>Account Holder:</strong> Nayyer Sultan<br>
        <div class="copy-wrapper">
            <strong>Account Number:</strong> 
            <span class="account-number" data-copy="03116600031">03116600031</span>
            <img src="/public/assets/images/copy-icon.svg" alt="Copy" class="copy-icon" style="cursor: pointer; width: 20px; height: 20px; margin-left: 5px; vertical-align: middle;" onclick="copyAccount(this)">
        </div>
    </li>
    <li>
        <strong>Bank:</strong> Faysal Bank Limited<br>
        <strong>Account Holder:</strong> Nayyer Sultan<br>
        <div class="copy-wrapper">
            <strong>Account Number:</strong> 
            <span class="account-number" data-copy="3152301000002893">3152301000002893</span>
            <img src="/public/assets/images/copy-icon.svg" alt="Copy" class="copy-icon" style="cursor: pointer; width: 20px; height: 20px; margin-left: 5px; vertical-align: middle;" onclick="copyAccount(this)">
        </div>
    </li>
</ul>
                                    <div class="form-group" style="margin-top: 10px;">
                                        <label for="payment_screenshot">Payment Screenshot *</label>
                                        <input type="file" id="payment_screenshot" name="payment_screenshot" accept="image/*" required>
                                    </div>
                                </div>
                            <?php endif; ?>

                        </div>

                        <div class="order-notes">
                            <label for="customer_note">Order Notes:</label>
                            <textarea id="customer_note" name="customer_note" placeholder="Any special instructions (optional)"></textarea>
                        </div>
                    </div>
                </div>

                <div class="order-section">
                    <!-- Order Summary -->
                    <div class="order-summary">
                        <h3>Order Summary</h3>
                        <div class="summary-container">
                            <?php
                            $totalCartValue = array_sum(array_column($cartItems, 'subtotal')); // Calculate the total cart value
                            $totalPriceWithDelivery = $totalCartValue + $deliveryCharge; // Add delivery charge to the total
                            ?>
                            
                            <!-- Loop through cart items to display them -->
                            <?php foreach ($cartItems as $item): ?>
                                <div class="summary-item">
                                    <div class="summary-item-label">Product Image:</div>
                                    <div class="summary-item-value">
                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" width="80" height="80">
                                    </div>
                                </div>
                                <div class="summary-item">
                                    <div class="summary-item-label">Product Name:</div>
                                    <div class="summary-item-value"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                </div>
                                <div class="summary-item">
                                    <div class="summary-item-label">Quantity:</div>
                                    <div class="summary-item-value"><?php echo htmlspecialchars($item['quantity']); ?></div>
                                </div>
                                <div class="summary-item">
                                    <div class="summary-item-label">Subtotal:</div>
                                    <div class="summary-item-value">Rs. <?php echo number_format($item['subtotal'], 2); ?></div>
                                </div>
                            <?php endforeach; ?>

                            <!-- Delivery Charge and Total -->
                            <div class="summary-item">
                                <div class="summary-item-label">Delivery Charges:</div>
                                <div class="summary-item-value">Rs. <?php echo number_format($deliveryCharge, 2); ?></div>
                            </div>
                            <div class="summary-item">
                                <div class="summary-item-label">Total:</div>
                                <div class="summary-item-value">Rs. <?php echo number_format($totalPriceWithDelivery, 2); ?></div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="submit-button">Place Order</button>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<!-- Modal Popup for Validation -->
<div id="validationModal" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);">
    <p id="modalMessage">Invalid input!</p>
    <button onclick="closeModal()">OK</button>
</div>

<!-- Modal Popup for Phone Validation -->
<div id="phoneModal" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.3) z-index: 100;">
    <p>❌ Please enter a valid 11-digit phone number starting with "03".</p>
    <button onclick="closeModal()">OK</button>
</div>

<?php if (!empty($errorMessage)): ?>
    <p style="color: red;"> <?php echo htmlspecialchars($errorMessage); ?> </p>
<?php endif; ?>

<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>