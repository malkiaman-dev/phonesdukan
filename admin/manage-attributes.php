<?php
session_start();
// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit();
}

// Include the database connection file
require_once __DIR__ . '/../database/db.php';
$toastMessage = '';
$toastType = 'success';

if (isset($_SESSION['attr_toast'])) {
    $toastMessage = (string)($_SESSION['attr_toast']['message'] ?? '');
    $toastType = (string)($_SESSION['attr_toast']['type'] ?? 'success');
    unset($_SESSION['attr_toast']);
}

// Check if the attribute is being added
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_attribute'])) {
    $attribute_name = $_POST['attribute_name'];

    // Establish connection with the database
    $conn = (new Database())->getConnection();

    // SQL query to insert a new attribute into the product_attributes table
    $query = "INSERT INTO product_attributes (attribute_name) VALUES (:attribute_name)";
    $stmt = $conn->prepare($query);

    // Bind the values to the query
    $stmt->bindParam(':attribute_name', $attribute_name);

    // Execute the query
    if ($stmt->execute()) {
        $_SESSION['attr_toast'] = ['type' => 'success', 'message' => 'Attribute added successfully.'];
    } else {
        $_SESSION['attr_toast'] = ['type' => 'error', 'message' => 'Error adding attribute.'];
    }
    header('Location: manage-attributes.php');
    exit();
}

// Check if the value is being added
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_value'])) {
    $attribute_id = $_POST['attribute_id'];
    $value = $_POST['value'];

    // Establish connection with the database
    $conn = (new Database())->getConnection();

    // SQL query to insert a new value for the attribute
    $query = "INSERT INTO product_attribute_values (attribute_id, value) VALUES (:attribute_id, :value)";
    $stmt = $conn->prepare($query);

    // Bind the values to the query
    $stmt->bindParam(':attribute_id', $attribute_id);
    $stmt->bindParam(':value', $value);

    // Execute the query
    if ($stmt->execute()) {
        $_SESSION['attr_toast'] = ['type' => 'success', 'message' => 'Value added successfully.'];
    } else {
        $_SESSION['attr_toast'] = ['type' => 'error', 'message' => 'Error adding value.'];
    }
    header('Location: manage-attributes.php');
    exit();
}

// Check if the attribute is being deleted
if (isset($_GET['delete_attribute_id'])) {
    $delete_attribute_id = $_GET['delete_attribute_id'];

    // Establish connection with the database
    $conn = (new Database())->getConnection();

    // SQL query to delete the attribute
    $query = "DELETE FROM product_attributes WHERE attribute_id = :attribute_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':attribute_id', $delete_attribute_id);

    // Execute the query
    if ($stmt->execute()) {
        $_SESSION['attr_toast'] = ['type' => 'success', 'message' => 'Attribute deleted successfully.'];
    } else {
        $_SESSION['attr_toast'] = ['type' => 'error', 'message' => 'Error deleting attribute.'];
    }
    header('Location: manage-attributes.php');
    exit();
}

// Check if the attribute value is being deleted
if (isset($_GET['delete_value_id'])) {
    $delete_value_id = $_GET['delete_value_id'];

    // Establish connection with the database
    $conn = (new Database())->getConnection();

    // SQL query to delete the attribute value
    $query = "DELETE FROM product_attribute_values WHERE value_id = :value_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':value_id', $delete_value_id);

    // Execute the query
    if ($stmt->execute()) {
        $_SESSION['attr_toast'] = ['type' => 'success', 'message' => 'Value deleted successfully.'];
    } else {
        $_SESSION['attr_toast'] = ['type' => 'error', 'message' => 'Error deleting value.'];
    }
    header('Location: manage-attributes.php');
    exit();
}

// Fetch existing attributes from the database
$conn = (new Database())->getConnection();
$query = "SELECT * FROM product_attributes";
$stmt = $conn->prepare($query);
$stmt->execute();

// Fetch all attributes as an associative array
$attributes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch existing attribute values
$values_query = "SELECT * FROM product_attribute_values";
$values_stmt = $conn->prepare($values_query);
$values_stmt->execute();

// Fetch all attribute values as an associative array
$attribute_values = $values_stmt->fetchAll(PDO::FETCH_ASSOC);

// Include layout after all redirect-capable logic to avoid "headers already sent".
include __DIR__ . '/admin_header.php';
include __DIR__ . '/admin_sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Attributes</title>
    <style>
        :root {
            --black: #111111;
            --yellow: #facc15;
            --yellow-hover: #eab308;
            --light-yellow: #fffbeb;
            --white: #ffffff;
            --bg: #f8fafc;
            --border: #e5e7eb;
            --muted: #6b7280;
        }

        .attr-page {
            max-width: 1240px;
            margin: 0 auto;
            padding: 24px;
            background: var(--bg);
        }

        /* Prevent global admin span styling (red badges) inside this page */
        .attr-page span {
            background: transparent !important;
            background-color: transparent !important;
            color: inherit !important;
            padding: 0 !important;
            margin: 0 !important;
            border-radius: 0 !important;
        }

        .attr-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 12px 30px rgba(17, 17, 17, 0.06);
        }

        .header-card {
            padding: 22px 24px;
            margin-bottom: 24px;
        }

        .header-card h1 {
            margin: 0;
            color: var(--black);
            font-size: 1.9rem;
            letter-spacing: -0.02em;
        }

        .header-subtitle {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 0.95rem;
        }

        .header-helper {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 0.85rem;
        }

        .forms-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }

        .form-card {
            padding: 24px;
        }

        .form-card h2 {
            margin: 0 0 14px;
            color: var(--black);
            font-size: 1.08rem;
            font-weight: 800;
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

        .field input,
        .field select {
            width: 100%;
            height: 48px;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 0 14px;
            background: var(--white);
            color: var(--black);
            font-size: 0.92rem;
            outline: none;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .field input::placeholder {
            color: #9ca3af;
        }

        .field input:focus,
        .field select:focus {
            border-color: var(--yellow);
            box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.18);
        }

        .field select {
            -webkit-appearance: none;
            appearance: none;
            padding-right: 38px;
            background-image:
                linear-gradient(45deg, transparent 50%, #111 50%),
                linear-gradient(135deg, #111 50%, transparent 50%);
            background-position:
                calc(100% - 18px) 20px,
                calc(100% - 12px) 20px;
            background-size: 6px 6px, 6px 6px;
            background-repeat: no-repeat;
        }

        .native-attr-select {
            position: absolute;
            opacity: 0;
            pointer-events: none;
            width: 1px;
            height: 1px;
        }

        .attr-select-wrap {
            position: relative;
        }

        .attr-select-display {
            width: 100%;
            height: 48px;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 0 38px 0 14px;
            background: var(--white);
            color: var(--black);
            text-align: left;
            font-size: 0.92rem;
            font-weight: 600;
            cursor: pointer;
            position: relative;
        }

        .attr-select-display::after {
            content: "";
            position: absolute;
            right: 14px;
            top: 50%;
            width: 8px;
            height: 8px;
            border-right: 2px solid var(--black);
            border-bottom: 2px solid var(--black);
            transform: translateY(-65%) rotate(45deg);
        }

        .attr-select-wrap.is-open .attr-select-display,
        .attr-select-display:hover {
            border-color: var(--yellow);
            box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.18);
        }

        .attr-select-options {
            position: absolute;
            left: 0;
            right: 0;
            z-index: 40;
            margin-top: 6px;
            list-style: none;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 10px;
            box-shadow: 0 12px 24px rgba(17, 17, 17, 0.12);
            padding: 6px;
            display: none;
            max-height: 220px;
            overflow-y: auto;
        }

        .attr-select-wrap.is-open .attr-select-options {
            display: block;
        }

        .attr-select-option {
            width: 100%;
            border: 0;
            background: transparent;
            border-radius: 8px;
            text-align: left;
            padding: 8px 10px;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--black);
            cursor: pointer;
        }

        .attr-select-option:hover {
            background: var(--light-yellow);
        }

        .attr-select-option.is-selected {
            background: var(--yellow);
        }

        .attr-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 110px;
            height: 44px;
            padding: 0 16px;
            border: 1px solid var(--black);
            border-radius: 10px;
            background: var(--black);
            color: var(--white) !important;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform .12s ease, box-shadow .12s ease, color .12s ease;
            text-decoration: none !important;
        }

        .attr-btn:hover {
            color: var(--yellow) !important;
            transform: translateY(-1px);
            box-shadow: 0 10px 18px rgba(17, 17, 17, 0.14);
            text-decoration: none !important;
        }

        .attributes-card {
            padding: 24px;
        }

        .attributes-card h2 {
            margin: 0;
            color: var(--black);
            font-size: 1.08rem;
            font-weight: 800;
        }

        .section-subtitle {
            margin: 6px 0 14px;
            color: var(--muted);
            font-size: 0.9rem;
        }

        .attrs-table-wrap {
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow-x: auto;
            overflow-y: hidden;
            background: #fff;
        }

        .attrs-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .attrs-table th,
        .attrs-table td {
            padding: 14px;
            border-bottom: 1px solid var(--border);
            text-align: left;
            vertical-align: top;
            font-size: 0.9rem;
        }

        .attrs-table th {
            background: #f9fafb;
            color: var(--black);
            font-weight: 800;
        }

        .attrs-table tbody tr:hover {
            background: var(--light-yellow);
        }

        .attrs-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .value-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .value-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 8px;
            background: var(--light-yellow);
            border: 1px solid var(--yellow);
            border-radius: 999px;
            color: var(--black);
            max-width: 100%;
        }

        .value-text {
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--black) !important;
            background: transparent !important;
            word-break: break-word;
        }

        .value-actions {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .mini-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 26px;
            padding: 0 8px;
            border: 1px solid var(--black);
            border-radius: 999px;
            background: var(--black);
            color: var(--white) !important;
            text-decoration: none;
            font-size: 0.72rem;
            font-weight: 700;
            white-space: nowrap;
            text-decoration: none !important;
        }

        .mini-btn:hover {
            color: var(--yellow) !important;
            text-decoration: none !important;
        }

        .row-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .empty-state {
            padding: 18px;
            text-align: center;
            color: var(--muted);
            border: 1px dashed var(--border);
            border-radius: 12px;
            background: #fcfcfd;
        }

        .confirm-overlay {
            position: fixed;
            inset: 0;
            background: rgba(17, 17, 17, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1200;
            padding: 16px;
        }

        .confirm-overlay.is-open {
            display: flex;
        }

        .confirm-modal {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(17, 17, 17, 0.18);
            padding: 20px;
        }

        .confirm-title {
            margin: 0 0 8px;
            color: var(--black);
            font-size: 1.02rem;
            font-weight: 800;
        }

        .confirm-text {
            margin: 0;
            color: var(--muted);
            font-size: 0.92rem;
        }

        .confirm-actions {
            margin-top: 16px;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
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

        @media (max-width: 980px) {
            .forms-grid {
                grid-template-columns: 1fr;
            }

            .attr-page {
                padding: 14px;
            }
        }

        @media (max-width: 768px) {
            .attrs-table,
            .attrs-table thead,
            .attrs-table tbody,
            .attrs-table tr,
            .attrs-table th,
            .attrs-table td {
                display: block;
                width: 100%;
            }

            .attrs-table thead {
                display: none;
            }

            .attrs-table tr {
                border-bottom: 1px solid var(--border);
                padding: 8px 0;
            }

            .attrs-table td {
                border-bottom: 0;
                padding: 8px 12px;
            }

            .attrs-table td::before {
                display: block;
                margin-bottom: 4px;
                color: var(--muted);
                font-size: 0.72rem;
                font-weight: 700;
                letter-spacing: 0.06em;
                text-transform: uppercase;
            }

            .attrs-table td[data-label="Attribute Name"]::before { content: "Attribute Name"; }
            .attrs-table td[data-label="Attribute Values"]::before { content: "Attribute Values"; }
            .attrs-table td[data-label="Actions"]::before { content: "Actions"; }
        }
    </style>
</head>
<body>
    <div class="attr-page">
        <div class="attr-card header-card">
            <h1>Manage Product Attributes</h1>
            <p class="header-subtitle">Create, organize, and manage product attribute values</p>
            <p class="header-helper">Use attributes like storage, color, RAM, size, etc.</p>
        </div>

        <div class="forms-grid">
            <div class="attr-card form-card">
                <h2>Add New Attribute</h2>
                <form method="POST" action="">
                    <div class="field">
                        <label for="attribute_name">Attribute Name</label>
                        <input id="attribute_name" type="text" name="attribute_name" placeholder="Enter attribute name, example: Storage" required>
                    </div>
                    <button class="attr-btn" type="submit" name="add_attribute">Add Attribute</button>
                </form>
            </div>

            <div class="attr-card form-card">
                <h2>Add New Value to Attribute</h2>
                <form method="POST" action="">
                    <div class="field">
                        <label for="attribute_id">Select Attribute</label>
                        <select id="attribute_id" name="attribute_id" class="native-attr-select" required>
                            <?php foreach ($attributes as $attribute) : ?>
                                <option value="<?php echo $attribute['attribute_id']; ?>"><?php echo htmlspecialchars($attribute['attribute_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="attr-select-wrap" data-attr-select>
                            <button type="button" class="attr-select-display" data-attr-display>
                                <?php echo !empty($attributes) ? htmlspecialchars($attributes[0]['attribute_name']) : 'Select attribute'; ?>
                            </button>
                            <ul class="attr-select-options" data-attr-options>
                                <?php foreach ($attributes as $idx => $attribute) : ?>
                                    <li>
                                        <button type="button" class="attr-select-option <?php echo $idx === 0 ? 'is-selected' : ''; ?>" data-value="<?php echo $attribute['attribute_id']; ?>">
                                            <?php echo htmlspecialchars($attribute['attribute_name']); ?>
                                        </button>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <div class="field">
                        <label for="value">Attribute Value</label>
                        <input id="value" type="text" name="value" placeholder="Enter value, example: 256GB - 8GB RAM" required>
                    </div>
                    <button class="attr-btn" type="submit" name="add_value">Add Value</button>
                </form>
            </div>
        </div>

        <div class="attr-card attributes-card">
            <h2>Existing Attributes and Their Values</h2>
            <p class="section-subtitle">View and manage all saved product options</p>

            <?php if (empty($attributes)): ?>
                <div class="empty-state">No product attributes found.</div>
            <?php else: ?>
                <div class="attrs-table-wrap">
                    <table class="attrs-table">
                        <thead>
                            <tr>
                                <th>Attribute Name</th>
                                <th>Attribute Values</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attributes as $attribute) : ?>
                                <tr>
                                    <td data-label="Attribute Name"><?php echo htmlspecialchars($attribute['attribute_name']); ?></td>
                                    <td data-label="Attribute Values">
                                        <div class="value-list">
                                            <?php
                                                $attribute_id = $attribute['attribute_id'];
                                                $hasValues = false;
                                                foreach ($attribute_values as $value) {
                                                    if ($value['attribute_id'] == $attribute_id) {
                                                        $hasValues = true;
                                            ?>
                                                <div class="value-item">
                                                    <span class="value-text"><?php echo htmlspecialchars($value['value']); ?></span>
                                                    <span class="value-actions">
                                                        <a class="mini-btn" href="edit-attribute-value.php?edit_value_id=<?php echo $value['value_id']; ?>">Edit</a>
                                                        <a class="mini-btn delete-action" href="?delete_value_id=<?php echo $value['value_id']; ?>" data-confirm-message="Are you sure you want to delete this value?">Delete</a>
                                                    </span>
                                                </div>
                                            <?php
                                                    }
                                                }
                                                if (!$hasValues) {
                                                    echo '<span class="value-text" style="color: var(--muted);">No values added.</span>';
                                                }
                                            ?>
                                        </div>
                                    </td>
                                    <td data-label="Actions">
                                        <div class="row-actions">
                                            <a class="attr-btn" href="edit-attribute.php?edit_id=<?php echo $attribute['attribute_id']; ?>">Edit</a>
                                            <a class="attr-btn delete-action" href="?delete_attribute_id=<?php echo $attribute['attribute_id']; ?>" data-confirm-message="Are you sure you want to delete this attribute?">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div id="confirmOverlay" class="confirm-overlay" aria-hidden="true">
        <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
            <h3 id="confirmTitle" class="confirm-title">Confirm Delete</h3>
            <p id="confirmText" class="confirm-text">Are you sure you want to delete this item?</p>
            <div class="confirm-actions">
                <button type="button" class="attr-btn" id="confirmCancelBtn">Cancel</button>
                <button type="button" class="attr-btn" id="confirmYesBtn">Delete</button>
            </div>
        </div>
    </div>
    <div id="attrToast" class="toast <?php echo $toastType === 'error' ? 'is-error' : ''; ?>" data-message="<?php echo htmlspecialchars($toastMessage, ENT_QUOTES, 'UTF-8'); ?>">
        <?php echo htmlspecialchars($toastMessage); ?>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-attr-select]').forEach(function (wrap) {
            const display = wrap.querySelector('[data-attr-display]');
            const options = Array.from(wrap.querySelectorAll('.attr-select-option'));
            const nativeSelect = wrap.previousElementSibling;
            if (!display || !nativeSelect || !nativeSelect.classList.contains('native-attr-select')) return;

            function setValue(value) {
                nativeSelect.value = value;
                const selected = options.find(function (opt) { return opt.dataset.value === value; });
                display.textContent = selected ? selected.textContent.trim() : value;
                options.forEach(function (opt) {
                    opt.classList.toggle('is-selected', opt.dataset.value === value);
                });
            }

            display.addEventListener('click', function (e) {
                e.stopPropagation();
                document.querySelectorAll('.attr-select-wrap.is-open').forEach(function (openWrap) {
                    if (openWrap !== wrap) openWrap.classList.remove('is-open');
                });
                wrap.classList.toggle('is-open');
            });

            options.forEach(function (opt) {
                opt.addEventListener('click', function () {
                    setValue(this.dataset.value || '');
                    wrap.classList.remove('is-open');
                });
            });

            setValue(nativeSelect.value || options[0]?.dataset.value || '');
        });

        document.addEventListener('click', function () {
            document.querySelectorAll('.attr-select-wrap.is-open').forEach(function (openWrap) {
                openWrap.classList.remove('is-open');
            });
        });

        const confirmOverlay = document.getElementById('confirmOverlay');
        const confirmText = document.getElementById('confirmText');
        const confirmCancelBtn = document.getElementById('confirmCancelBtn');
        const confirmYesBtn = document.getElementById('confirmYesBtn');
        let pendingHref = '';

        document.querySelectorAll('.delete-action').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                pendingHref = this.getAttribute('href') || '';
                if (confirmText) {
                    confirmText.textContent = this.getAttribute('data-confirm-message') || 'Are you sure you want to delete this item?';
                }
                confirmOverlay?.classList.add('is-open');
            });
        });

        confirmCancelBtn?.addEventListener('click', function () {
            confirmOverlay?.classList.remove('is-open');
            pendingHref = '';
        });

        confirmYesBtn?.addEventListener('click', function () {
            if (pendingHref) {
                window.location.href = pendingHref;
            }
        });

        confirmOverlay?.addEventListener('click', function (e) {
            if (e.target === confirmOverlay) {
                confirmOverlay.classList.remove('is-open');
                pendingHref = '';
            }
        });

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
