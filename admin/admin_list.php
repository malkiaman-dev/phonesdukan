<?php
session_start();
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'superadmin') {
    header('Location: login.php');
    exit;
}
include __DIR__ . '/admin_sidebar.php';

// Include the header
include __DIR__ . '/admin_header.php';
require_once __DIR__ . '/../database/db.php';

$database = new Database();
$conn = $database->getConnection();

$stmt = $conn->query("SELECT id, name, email, role FROM admins ORDER BY role DESC");

?>

<!DOCTYPE html>
<html>
<head>
    <title>All Admins - Phones Dukan</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        table {
            width: 90%;
            margin: 30px auto;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        th, td {
            padding: 12px 18px;
            border: 1px solid #ccc;
            text-align: left;
        }

        th {
            background: #f0f0f0;
        }

        .btn {
            padding: 6px 12px;
            background-color: #007bff;
            color: white;
            border-radius: 4px;
            border: none;
            text-decoration: none;
            transition: background 0.3s;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        h2 {
            text-align: center;
            margin-top: 30px;
        }
    </style>
</head>
<body>

    <h2>All Admins</h2>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($admin = $stmt->fetch(PDO::FETCH_ASSOC)) : ?>
                <tr>
                    <td><?= htmlspecialchars($admin['id']) ?></td>
                    <td><?= htmlspecialchars($admin['name']) ?></td>
                    <td><?= htmlspecialchars($admin['email']) ?></td>
                    <td><?= htmlspecialchars($admin['role']) ?></td>
                    <td>
                        <a class="btn" href="change_password.php?id=<?= $admin['id'] ?>">Change Password</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

</body>
</html>
