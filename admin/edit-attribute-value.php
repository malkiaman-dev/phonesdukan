<?php
session_start();
// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit();
}
// Include the header
include __DIR__ . '/admin_header.php';

// Include the sidebar only if the admin is logged in
include __DIR__ . '/admin_sidebar.php';
// Include the database connection file
require_once __DIR__ . '/../database/db.php';
$toastMessage = '';
$toastType = 'success';

if (isset($_SESSION['attr_toast'])) {
    $toastMessage = (string)($_SESSION['attr_toast']['message'] ?? '');
    $toastType = (string)($_SESSION['attr_toast']['type'] ?? 'success');
    unset($_SESSION['attr_toast']);
}

// Check if the attribute value is being edited
if (isset($_GET['edit_value_id'])) {
    $edit_value_id = $_GET['edit_value_id'];

    // Establish connection with the database
    $conn = (new Database())->getConnection();

    // Fetch the existing attribute value details from the database
    $query = "SELECT * FROM product_attribute_values WHERE value_id = :value_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':value_id', $edit_value_id);
    $stmt->execute();

    // Get the attribute value details
    $attribute_value = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle the edit form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_value'])) {
    $value_id = $_POST['value_id'];
    $value = $_POST['value'];

    // Update the attribute value in the database
    $conn = (new Database())->getConnection();
    $query = "UPDATE product_attribute_values SET value = :value WHERE value_id = :value_id";
    $stmt = $conn->prepare($query);

    $stmt->bindParam(':value_id', $value_id);
    $stmt->bindParam(':value', $value);

    if ($stmt->execute()) {
        $_SESSION['attr_toast'] = ['type' => 'success', 'message' => 'Value updated successfully.'];
        header("Location: manage-attributes.php");
        exit;
    } else {
        $toastMessage = 'Error updating value.';
        $toastType = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Attribute Value</title>
    <style>
        :root {
            --black: #111111;
            --yellow: #facc15;
            --white: #ffffff;
            --bg: #f8fafc;
            --border: #e5e7eb;
            --muted: #6b7280;
        }

        .edit-wrap {
            max-width: 760px;
            margin: 0 auto;
            padding: 24px;
            background: var(--bg);
        }

        .edit-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 12px 30px rgba(17, 17, 17, 0.06);
            padding: 24px;
        }

        .edit-card h1 {
            margin: 0 0 6px;
            color: var(--black);
            font-size: 1.9rem;
            letter-spacing: -0.02em;
        }

        .subtext {
            margin: 0 0 16px;
            color: var(--muted);
            font-size: 0.92rem;
        }

        .field {
            margin-bottom: 14px;
        }

        .field label {
            display: block;
            margin-bottom: 6px;
            color: var(--black);
            font-size: 0.9rem;
            font-weight: 700;
        }

        .field input {
            width: 100%;
            height: 48px;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 0 14px;
            background: #fff;
            color: var(--black);
            outline: none;
            font-size: 0.92rem;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .field input:focus {
            border-color: var(--yellow);
            box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.18);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 44px;
            padding: 0 16px;
            border: 1px solid var(--black);
            border-radius: 10px;
            background: var(--black);
            color: #fff !important;
            font-size: 0.88rem;
            font-weight: 700;
            text-decoration: none !important;
            cursor: pointer;
            transition: transform .12s ease, box-shadow .12s ease, color .12s ease;
        }

        .btn:hover {
            color: var(--yellow) !important;
            transform: translateY(-1px);
            box-shadow: 0 10px 18px rgba(17, 17, 17, 0.14);
            text-decoration: none !important;
        }

        .empty-state {
            padding: 16px;
            border: 1px dashed var(--border);
            border-radius: 12px;
            color: var(--muted);
            background: #fcfcfd;
        }

        .toast {
            position: fixed;
            right: 16px;
            bottom: 16px;
            z-index: 1300;
            background: #111;
            color: #fff;
            border: 1px solid var(--yellow);
            border-left: 5px solid var(--yellow);
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 0.9rem;
            font-weight: 600;
            box-shadow: 0 14px 30px rgba(17, 17, 17, 0.3);
            display: none;
        }

        .toast.is-error {
            border-color: var(--yellow);
            border-left-color: var(--yellow);
        }

        .toast.show {
            display: block;
        }
    </style>
</head>
<body>
    <div class="edit-wrap">
        <div class="edit-card">
            <h1>Edit Attribute Value</h1>
            <p class="subtext">Update this value and save changes.</p>

            <?php if (!empty($attribute_value)): ?>
            <form method="POST" action="">
                <input type="hidden" name="value_id" value="<?php echo $attribute_value['value_id']; ?>">

                <div class="field">
                    <label for="value">Attribute Value</label>
                    <input id="value" type="text" name="value" value="<?php echo htmlspecialchars($attribute_value['value']); ?>" required>
                </div>

                <button class="btn" type="submit" name="edit_value">Update Value</button>
            </form>
            <?php else: ?>
                <div class="empty-state">Attribute Value not found!</div>
            <?php endif; ?>
        </div>
    </div>
    <div id="attrToast" class="toast <?php echo $toastType === 'error' ? 'is-error' : ''; ?>" data-message="<?php echo htmlspecialchars($toastMessage, ENT_QUOTES, 'UTF-8'); ?>">
        <?php echo htmlspecialchars($toastMessage); ?>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const toast = document.getElementById('attrToast');
        if (toast && toast.dataset.message) {
            toast.classList.add('show');
            setTimeout(function () {
                toast.classList.remove('show');
            }, 3200);
        }
    });
    </script>

</body>
</html>
