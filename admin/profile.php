<?php
session_start();
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();

$adminId = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;
$profile = null;
$flash = null;
$flashType = 'success';

if ($adminId > 0) {
    $stmt = $conn->prepare('SELECT id, name, email, role FROM admins WHERE id = :id LIMIT 1');
    $stmt->bindValue(':id', $adminId, PDO::PARAM_INT);
    $stmt->execute();
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$profile) {
    header('Location: ' . url('admin/dashboard.php'));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));

    if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $flash = 'Please enter a valid name and email.';
        $flashType = 'error';
    } else {
        try {
            $check = $conn->prepare('SELECT id FROM admins WHERE email = :email AND id != :id LIMIT 1');
            $check->bindValue(':email', $email, PDO::PARAM_STR);
            $check->bindValue(':id', $adminId, PDO::PARAM_INT);
            $check->execute();
            if ($check->fetch(PDO::FETCH_ASSOC)) {
                $flash = 'This email is already used by another admin.';
                $flashType = 'error';
            } else {
                $update = $conn->prepare('UPDATE admins SET name = :name, email = :email WHERE id = :id');
                $update->bindValue(':name', $name, PDO::PARAM_STR);
                $update->bindValue(':email', $email, PDO::PARAM_STR);
                $update->bindValue(':id', $adminId, PDO::PARAM_INT);
                $update->execute();

                $_SESSION['admin_name'] = $name;
                $profile['name'] = $name;
                $profile['email'] = $email;
                $flash = 'Profile updated successfully.';
                $flashType = 'success';
            }
        } catch (Throwable $e) {
            $flash = 'Failed to update profile.';
            $flashType = 'error';
        }
    }
}

include __DIR__ . '/admin_sidebar.php';
include __DIR__ . '/admin_header.php';
?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile</title>
    <style>
        :root {
            --black: #111111;
            --yellow: #facc15;
            --light-yellow: #fffbeb;
            --white: #ffffff;
            --bg: #f8fafc;
            --border: #e5e7eb;
            --muted: #6b7280;
        }

        .pro-wrap {
            max-width: 980px;
            margin: 0 auto;
            padding: 20px;
        }

        .pro-header,
        .pro-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 12px 30px rgba(17, 17, 17, 0.06);
        }

        .pro-header {
            padding: 20px 24px;
            margin-bottom: 16px;
        }

        .pro-title {
            margin: 0;
            font-size: clamp(1.5rem, 2vw, 1.75rem);
            font-weight: 600;
            line-height: 1.25;
            letter-spacing: -0.02em;
            color: var(--black);
        }
        .pro-subtitle {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 0.875rem;
            font-weight: 400;
            line-height: 1.5;
        }

        .pro-card { padding: 22px; }

        .pro-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }
        .pro-field.full { grid-column: 1 / -1; }

        .pro-field label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--black);
        }

        .pro-input {
            width: 100%;
            height: 48px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: #fff;
            color: var(--black);
            padding: 0 14px;
            font-size: 0.92rem;
            outline: none;
            box-sizing: border-box;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .pro-input[readonly] {
            background: #f9fafb;
            color: var(--muted);
        }
        .pro-input:focus, .pro-input:focus-visible {
            outline: none !important;
            border-color: var(--yellow);
            box-shadow: 0 0 0 3px rgba(250,204,21,0.18);
        }

        .pro-actions {
            margin-top: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .pro-btn {
            height: 44px;
            padding: 0 16px;
            border-radius: 12px;
            border: 1px solid var(--black);
            background: var(--black);
            color: #fff !important;
            font-size: 0.9rem;
            font-weight: 800;
            text-decoration: none !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: color .15s ease, transform .15s ease;
        }
        .pro-btn:hover {
            color: var(--yellow) !important;
            transform: translateY(-1px);
        }

        .pro-btn-outline {
            border: 1px solid var(--border);
            background: #fff;
            color: var(--black) !important;
        }
        .pro-btn-outline:hover {
            color: var(--black) !important;
            border-color: var(--yellow);
            background: var(--light-yellow);
            transform: translateY(-1px);
        }

        .pro-toast {
            position: fixed;
            right: 22px;
            bottom: 22px;
            z-index: 4000;
            min-width: 260px;
            max-width: 380px;
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 0.9rem;
            font-weight: 800;
            border: 1px solid var(--border);
            box-shadow: 0 16px 30px rgba(17, 17, 17, 0.15);
            opacity: 0;
            transform: translateY(10px);
            pointer-events: none;
            transition: opacity .2s ease, transform .2s ease;
        }
        .pro-toast.is-show { opacity: 1; transform: translateY(0); }
        .pro-toast-success { background: #111111; color: #ffffff; border-color: #111111; }
        .pro-toast-error { background: var(--light-yellow); color: var(--black); border-color: var(--yellow); }

        @media (max-width: 860px) {
            .pro-grid { grid-template-columns: 1fr; }
        }
    </style>
<div class="pro-wrap">
    <div class="pro-header">
        <h2 class="pro-title">Admin Profile</h2>
        <p class="pro-subtitle">Manage your account information and keep it up to date.</p>
    </div>

    <div class="pro-card">
        <form method="POST" action="">
            <div class="pro-grid">
                <div class="pro-field">
                    <label for="name">Name</label>
                    <input class="pro-input" id="name" type="text" name="name" value="<?php echo htmlspecialchars((string)$profile['name']); ?>" required>
                </div>
                <div class="pro-field">
                    <label for="email">Email</label>
                    <input class="pro-input" id="email" type="email" name="email" value="<?php echo htmlspecialchars((string)$profile['email']); ?>" required>
                </div>
                <div class="pro-field">
                    <label for="role">Role</label>
                    <input class="pro-input" id="role" type="text" value="<?php echo htmlspecialchars((string)$profile['role']); ?>" readonly>
                </div>
                <div class="pro-field">
                    <label for="admin_id">Admin ID</label>
                    <input class="pro-input" id="admin_id" type="text" value="<?php echo (int)$profile['id']; ?>" readonly>
                </div>
            </div>

            <div class="pro-actions">
                <button type="submit" class="pro-btn">Save Changes</button>
                <a class="pro-btn pro-btn-outline" href="<?php echo htmlspecialchars(url('admin/dashboard.php')); ?>">Back to Dashboard</a>
            </div>
        </form>
    </div>
</div>

<?php if ($flash !== null): ?>
    <div id="proToast" class="pro-toast <?php echo $flashType === 'error' ? 'pro-toast-error' : 'pro-toast-success'; ?>">
        <?php echo htmlspecialchars($flash); ?>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const toast = document.getElementById('proToast');
    if (toast) {
        requestAnimationFrame(() => toast.classList.add('is-show'));
        setTimeout(() => toast.classList.remove('is-show'), 3200);
    }
});
</script>
