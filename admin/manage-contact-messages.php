<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../database/db.php';

// Initialize the database connection
$database = new Database();
$conn = $database->getConnection();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header("Location: login.php");
    exit();
}

// Include the sidebar and header
include __DIR__ . '/admin_sidebar.php';
include __DIR__ . '/admin_header.php';

// Fetch all contact messages
$query = "SELECT id, name, email, subject, message, created_at FROM contact_messages ORDER BY created_at DESC";
$stmt = $conn->query($query);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Contact Messages - Phones Dukan</title>
    <style>
        .msg-wrap {
            padding: 20px;
        }
        .msg-title {
            font-size: 24px;
            margin-bottom: 20px;
        }
        .msg-table-wrap {
            overflow-x: auto;
        }
        .msg-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .msg-table th, .msg-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .msg-table th {
            background: #f4f4f4;
            font-weight: bold;
        }
        .msg-table td.message {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .msg-table td.subject {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .msg-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .msg-btn.view {
            background: #007bff;
            color: #fff;
        }
        .msg-btn.view:hover {
            background: #0056b3;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }
        .modal-content .close {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <!-- Main Content -->
    <div class="msg-wrap">
        <h2 class="msg-title">Manage Contact Messages</h2>
        <div class="msg-table-wrap">
            <table class="msg-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Created At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $message) { ?>
                        <tr>
                            <td><?= htmlspecialchars($message['id']) ?></td>
                            <td><?= htmlspecialchars($message['name']) ?></td>
                            <td><?= htmlspecialchars($message['email']) ?></td>
                            <td class="subject"><?= htmlspecialchars($message['subject']) ?></td>
                            <td class="message">
                                <?php
                                // Decode HTML entities and remove <br> tags for preview
                                $decoded_message = html_entity_decode($message['message'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                $clean_message = str_replace(['<br />', '<br>'], ' ', $decoded_message);
                                echo htmlspecialchars(substr($clean_message, 0, 50)) . '...';
                                ?>
                            </td>
                            <td><?= date('d-M-Y H:i', strtotime($message['created_at'])) ?></td>
                            <td>
                                <button class="msg-btn view" data-message-id="<?= htmlspecialchars($message['id']) ?>">View</button>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Message Details Modal -->
    <div id="msg-modal" class="modal">
        <div class="modal-content">
            <span class="close">×</span>
            <div id="msg-detail-content">
                <p>Loading...</p>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const modalContainerId = "custom-msg-modal";

        // Handle View button clicks
        document.querySelectorAll(".msg-btn.view").forEach(function (button) {
            button.addEventListener("click", function () {
                let messageId = this.getAttribute("data-message-id");

                fetch("get_message_details.php?message_id=" + messageId)
                    .then(response => response.text())
                    .then(data => {
                        let modalContainer = document.getElementById(modalContainerId);

                        if (!modalContainer) {
                            modalContainer = document.createElement("div");
                            modalContainer.id = modalContainerId;
                            modalContainer.className = "modal";
                            document.body.appendChild(modalContainer);
                        }

                        modalContainer.innerHTML = data;
                        modalContainer.style.display = "flex";

                        // Attach close event
                        setTimeout(() => {
                            const closeButton = modalContainer.querySelector(".close");
                            if (closeButton) {
                                closeButton.addEventListener("click", function () {
                                    modalContainer.style.display = "none";
                                });
                            }
                        }, 100);
                    })
                    .catch(error => console.error("Error loading message details:", error));
            });
        });

        // Close modal when clicking outside
        document.addEventListener("click", function (event) {
            const modalContainer = document.getElementById(modalContainerId);
            if (modalContainer && event.target === modalContainer) {
                modalContainer.style.display = "none";
            }
        });
    });
    </script>
</body>
</html>