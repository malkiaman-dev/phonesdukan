<?php
session_start();
require_once __DIR__ . '/../database/db.php';

$database = new Database();
$conn = $database->getConnection();

// Fetch list of admins if superadmin is logged in
$admins = [];
if ($_SESSION['admin_role'] === 'superadmin') {
    $stmt = $conn->query("SELECT id, name FROM admins");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // If not superadmin, allow user to only change their own password
    $stmt = $conn->prepare("SELECT id, name FROM admins WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
include __DIR__ . '/admin_sidebar.php';

// Include the header
include __DIR__ . '/admin_header.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Change Password</title>
</head>
<body>
    <h2>Change Password</h2>

    <form action="update_password.php" method="POST">
        <label for="target_id">Select Admin:</label><br>
        <select name="target_id" id="target_id" required>
            <?php foreach ($admins as $admin): ?>
                <option value="<?= htmlspecialchars($admin['id']) ?>">
                    <?= htmlspecialchars($admin['name']) ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <label for="new_password">New Password:</label><br>
        <input type="password" name="new_password" id="new_password" required><br><br>

        <button type="submit">Update Password</button>
    </form>
</body>
</html>
