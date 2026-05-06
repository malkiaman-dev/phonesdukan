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

$settings = [];
$flash = null;
$flashType = 'success';

try {
    $stmt = $conn->query('SELECT * FROM site_settings ORDER BY id ASC LIMIT 1');
    $settings = $stmt ? ($stmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];
} catch (Throwable $e) {
    $flash = 'Unable to load current settings.';
    $flashType = 'error';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $siteName = trim((string)($_POST['site_name'] ?? ''));
    $contactEmail = trim((string)($_POST['contact_email'] ?? ''));
    $footerText = trim((string)($_POST['footer_text'] ?? ''));

    if ($siteName === '' || $contactEmail === '' || !filter_var($contactEmail, FILTER_VALIDATE_EMAIL) || $footerText === '') {
        $flash = 'Please fill all fields with valid values.';
        $flashType = 'error';
    } else {
        try {
            $settingsId = isset($settings['id']) ? (int)$settings['id'] : 0;

            if ($settingsId > 0) {
                $update = $conn->prepare(
                    'UPDATE site_settings
                     SET site_name = :site_name, contact_email = :contact_email, footer_text = :footer_text
                     WHERE id = :id'
                );
                $update->bindValue(':id', $settingsId, PDO::PARAM_INT);
            } else {
                $update = $conn->prepare(
                    'INSERT INTO site_settings (site_name, contact_email, footer_text)
                     VALUES (:site_name, :contact_email, :footer_text)'
                );
            }

            $update->bindValue(':site_name', $siteName, PDO::PARAM_STR);
            $update->bindValue(':contact_email', $contactEmail, PDO::PARAM_STR);
            $update->bindValue(':footer_text', $footerText, PDO::PARAM_STR);
            $update->execute();

            if ($settingsId === 0) {
                $settingsId = (int)$conn->lastInsertId();
            }

            $settings = [
                'id' => $settingsId,
                'site_name' => $siteName,
                'contact_email' => $contactEmail,
                'footer_text' => $footerText
            ];
            $flash = 'Settings updated successfully.';
            $flashType = 'success';
        } catch (Throwable $e) {
            $flash = 'Failed to update settings.';
            $flashType = 'error';
        }
    }
}

include __DIR__ . '/admin_sidebar.php';
include __DIR__ . '/admin_header.php';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings</title>
    <style>
        :root {
            --black: #0f0f10;
            --black-soft: #18181b;
            --yellow: #facc15;
            --white: #ffffff;
            --muted: #f7f7f8;
            --border: #e4e4e7;
            --text-soft: #5b5b66;
        }
        html, body {
            overflow: hidden;
        }
        .set-wrap {
            max-width: 1020px;
            margin: 28px auto 36px;
            padding: 0 18px;
        }
        .set-head {
            background: linear-gradient(120deg, var(--black) 0%, var(--black-soft) 100%);
            color: var(--white);
            border-radius: 20px;
            padding: 26px 24px;
            border: 1px solid #1f1f24;
            margin-bottom: 18px;
        }
        .set-title {
            margin: 0 0 6px;
            font-size: 1.38rem;
            font-weight: 800;
            letter-spacing: .2px;
            color: #ffffff !important;
        }
        .set-sub {
            margin: 0;
            color: rgba(255, 255, 255, 0.78);
            font-size: .93rem;
        }
        .set-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 20px;
            box-shadow: 0 12px 34px rgba(15, 15, 16, 0.06);
        }
        .set-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }
        .set-field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .set-field-full {
            grid-column: 1 / -1;
        }
        .set-label {
            font-size: .84rem;
            font-weight: 700;
            color: var(--black);
        }
        .set-input,
        .set-textarea {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: #fff;
            color: var(--black);
            font-size: .92rem;
            padding: 11px 13px;
            outline: none;
            transition: border-color .2s ease, box-shadow .2s ease;
        }
        .set-textarea {
            min-height: 120px;
            resize: vertical;
        }
        .set-input:focus,
        .set-textarea:focus {
            border-color: var(--yellow);
            box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.18);
            border-radius: 12px;
        }
        .set-actions {
            margin-top: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .set-btn {
            height: 43px;
            border: 1px solid var(--black);
            background: var(--black);
            color: var(--white);
            border-radius: 12px;
            padding: 0 16px;
            font-size: .87rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: color .16s ease, transform .16s ease;
        }
        .set-btn:hover {
            color: var(--yellow);
            transform: translateY(-1px);
        }
        .set-btn-outline {
            background: #fff;
            color: var(--black);
            border-color: var(--border);
        }
        .set-toast {
            position: fixed;
            right: 20px;
            bottom: 18px;
            min-width: 240px;
            max-width: 420px;
            background: #111;
            color: #fff;
            border: 1px solid #26262b;
            border-left: 4px solid var(--yellow);
            padding: 12px 14px;
            border-radius: 12px;
            font-size: .88rem;
            font-weight: 700;
            transform: translateY(12px);
            opacity: 0;
            pointer-events: none;
            transition: transform .2s ease, opacity .2s ease;
            z-index: 9999;
        }
        .set-toast.is-show {
            transform: translateY(0);
            opacity: 1;
        }
        .set-toast-error {
            border-left-color: #f59e0b;
        }
        @media (max-width: 880px) {
            .set-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="set-wrap">
    <div class="set-head">
        <h2 class="set-title">Site Settings</h2>
        <p class="set-sub">Manage global storefront details used across your website.</p>
    </div>

    <div class="set-card">
        <form method="POST" action="">
            <div class="set-grid">
                <div class="set-field">
                    <label class="set-label" for="site_name">Site Name</label>
                    <input
                        class="set-input"
                        id="site_name"
                        type="text"
                        name="site_name"
                        value="<?php echo htmlspecialchars((string)($settings['site_name'] ?? '')); ?>"
                        placeholder="Enter your site name"
                        required
                    >
                </div>

                <div class="set-field">
                    <label class="set-label" for="contact_email">Contact Email</label>
                    <input
                        class="set-input"
                        id="contact_email"
                        type="email"
                        name="contact_email"
                        value="<?php echo htmlspecialchars((string)($settings['contact_email'] ?? '')); ?>"
                        placeholder="Enter contact email"
                        required
                    >
                </div>

                <div class="set-field set-field-full">
                    <label class="set-label" for="footer_text">Footer Text</label>
                    <textarea
                        class="set-textarea"
                        id="footer_text"
                        name="footer_text"
                        placeholder="Write footer text displayed on your website"
                        required
                    ><?php echo htmlspecialchars((string)($settings['footer_text'] ?? '')); ?></textarea>
                </div>
            </div>

            <div class="set-actions">
                <button type="submit" class="set-btn">Update Settings</button>
                <a class="set-btn set-btn-outline" href="<?php echo htmlspecialchars(url('admin/dashboard.php')); ?>">Back to Dashboard</a>
            </div>
        </form>
    </div>
</div>

<?php if ($flash !== null): ?>
    <div id="settingsToast" class="set-toast <?php echo $flashType === 'error' ? 'set-toast-error' : ''; ?>">
        <?php echo htmlspecialchars($flash); ?>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var toast = document.getElementById('settingsToast');
    if (!toast) return;
    requestAnimationFrame(function () {
        toast.classList.add('is-show');
    });
    setTimeout(function () {
        toast.classList.remove('is-show');
    }, 3200);
});
</script>
</body>
</html>
