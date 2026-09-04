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
ensureSiteSettingsSchema($conn);

$settings = getSiteSettings($conn);
$flash = null;
$flashType = 'success';

if (!empty($_SESSION['settings_flash']) && is_array($_SESSION['settings_flash'])) {
    $flash = (string) ($_SESSION['settings_flash']['message'] ?? '');
    $flashType = (string) ($_SESSION['settings_flash']['type'] ?? 'success');
    unset($_SESSION['settings_flash']);
}

if (!empty($_SESSION['settings_draft']) && is_array($_SESSION['settings_draft'])) {
    $settings = array_merge($settings, $_SESSION['settings_draft']);
    unset($_SESSION['settings_draft']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $siteName = trim((string) ($_POST['site_name'] ?? ''));
    $contactEmail = trim((string) ($_POST['contact_email'] ?? ''));
    $footerText = trim((string) ($_POST['footer_text'] ?? ''));
    $announcementEnabled = isset($_POST['announcement_enabled']) ? 1 : 0;
    $announcementText = trim((string) ($_POST['announcement_text'] ?? ''));
    if ($announcementText === '') {
        $announcementText = getDefaultAnnouncementText();
    }

    $draft = [
        'announcement_enabled' => $announcementEnabled,
        'announcement_text' => $announcementText,
        'site_name' => $siteName,
        'contact_email' => $contactEmail,
        'footer_text' => $footerText,
    ];

    if ($siteName === '' || $contactEmail === '' || !filter_var($contactEmail, FILTER_VALIDATE_EMAIL) || $footerText === '') {
        $_SESSION['settings_flash'] = [
            'message' => 'Please fill all fields with valid values.',
            'type' => 'error',
        ];
        $_SESSION['settings_draft'] = $draft;
    } else {
        try {
            $settingsId = isset($settings['id']) ? (int) $settings['id'] : 0;

            if ($settingsId > 0) {
                $update = $conn->prepare(
                    'UPDATE site_settings
                     SET site_name = :site_name,
                         contact_email = :contact_email,
                         footer_text = :footer_text,
                         announcement_enabled = :announcement_enabled,
                         announcement_text = :announcement_text
                     WHERE id = :id'
                );
                $update->bindValue(':id', $settingsId, PDO::PARAM_INT);
            } else {
                $update = $conn->prepare(
                    'INSERT INTO site_settings (site_name, contact_email, footer_text, announcement_enabled, announcement_text)
                     VALUES (:site_name, :contact_email, :footer_text, :announcement_enabled, :announcement_text)'
                );
            }

            $update->bindValue(':site_name', $siteName, PDO::PARAM_STR);
            $update->bindValue(':contact_email', $contactEmail, PDO::PARAM_STR);
            $update->bindValue(':footer_text', $footerText, PDO::PARAM_STR);
            $update->bindValue(':announcement_enabled', $announcementEnabled, PDO::PARAM_INT);
            $update->bindValue(':announcement_text', $announcementText, PDO::PARAM_STR);
            $update->execute();

            $_SESSION['settings_flash'] = [
                'message' => 'Settings updated successfully.',
                'type' => 'success',
            ];
        } catch (Throwable $e) {
            error_log('settings.php update: ' . $e->getMessage());
            $_SESSION['settings_flash'] = [
                'message' => 'Failed to update settings.',
                'type' => 'error',
            ];
            $_SESSION['settings_draft'] = $draft;
        }
    }

    header('Location: ' . url('admin/settings.php'));
    exit();
}

$announcementOn = (int) ($settings['announcement_enabled'] ?? 1) === 1;
$announcementTextValue = trim((string) ($settings['announcement_text'] ?? ''));
if ($announcementTextValue === '') {
    $announcementTextValue = getDefaultAnnouncementText();
}

include __DIR__ . '/admin_header.php';
include __DIR__ . '/admin_sidebar.php';
?>
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
    .set-wrap,
    .set-wrap * {
        box-sizing: border-box;
    }
    .set-wrap {
        max-width: 1020px;
        width: 100%;
        margin: 28px auto 64px;
        padding: 0 18px 40px;
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
        font-size: clamp(1.5rem, 2vw, 1.75rem);
        font-weight: 600;
        line-height: 1.25;
        letter-spacing: -0.02em;
        color: #ffffff !important;
    }
    .set-sub {
        margin: 0;
        color: rgba(255, 255, 255, 0.78);
        font-size: 0.875rem;
        font-weight: 400;
        line-height: 1.5;
    }
    .set-card {
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 18px;
        padding: 20px;
        box-shadow: 0 12px 34px rgba(15, 15, 16, 0.06);
        margin-bottom: 16px;
        overflow: hidden;
        max-width: 100%;
    }
    .set-card-title {
        margin: 0 0 4px;
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--black);
    }
    .set-card-sub {
        margin: 0 0 18px;
        color: var(--text-soft);
        font-size: 0.86rem;
        line-height: 1.45;
    }
    .set-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
        width: 100%;
        max-width: 100%;
    }
    .set-field {
        display: flex;
        flex-direction: column;
        gap: 8px;
        min-width: 0;
        max-width: 100%;
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
        display: block;
        width: 100%;
        max-width: 100%;
        min-width: 0;
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
    .set-toggle-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 14px 16px;
        border: 1px solid var(--border);
        border-radius: 14px;
        background: var(--muted);
        max-width: 100%;
    }
    .set-toggle-copy {
        min-width: 0;
    }
    .set-toggle-copy strong {
        display: block;
        font-size: 0.92rem;
        color: var(--black);
        margin-bottom: 4px;
    }
    .set-toggle-copy span {
        display: block;
        font-size: 0.82rem;
        color: var(--text-soft);
        line-height: 1.4;
    }
    .set-switch {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        user-select: none;
        flex-shrink: 0;
    }
    .set-switch input {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }
    .set-switch-track {
        width: 48px;
        height: 28px;
        border-radius: 999px;
        background: #d4d4d8;
        border: 1px solid #c4c4cc;
        position: relative;
        transition: background .18s ease, border-color .18s ease;
    }
    .set-switch-track::after {
        content: '';
        position: absolute;
        top: 2px;
        left: 2px;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: #fff;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.18);
        transition: transform .18s ease;
    }
    .set-switch input:checked + .set-switch-track {
        background: var(--yellow);
        border-color: #eab308;
    }
    .set-switch input:checked + .set-switch-track::after {
        transform: translateX(20px);
    }
    .set-switch-state {
        min-width: 64px;
        font-size: 0.82rem;
        font-weight: 700;
        color: var(--black);
    }
    .set-actions {
        margin-top: 4px;
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
        text-decoration: none !important;
    }
    .set-btn:focus,
    .set-btn:active,
    .set-btn:visited {
        text-decoration: none !important;
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
        .set-toggle-row {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>
<div class="set-wrap">
    <div class="set-head">
        <h2 class="set-title">Site Settings</h2>
        <p class="set-sub">Manage announcement bar and global storefront details. Deal popup has its own tab.</p>
    </div>

    <form method="POST" action="">
        <div class="set-card">
            <h3 class="set-card-title">Announcement Bar</h3>
            <p class="set-card-sub">Show or hide the yellow top announcement bar and edit the scrolling messages.</p>
            <div class="set-toggle-row">
                <div class="set-toggle-copy">
                    <strong>Top announcement bar</strong>
                    <span>When enabled, visitors see the scrolling store message above the navbar.</span>
                </div>
                <label class="set-switch" for="announcement_enabled">
                    <input
                        type="checkbox"
                        id="announcement_enabled"
                        name="announcement_enabled"
                        value="1"
                        <?php echo $announcementOn ? 'checked' : ''; ?>
                    >
                    <span class="set-switch-track" aria-hidden="true"></span>
                    <span class="set-switch-state" id="announcementStateLabel"><?php echo $announcementOn ? 'Enabled' : 'Disabled'; ?></span>
                </label>
            </div>
            <div class="set-field" style="margin-top: 16px;">
                <label class="set-label" for="announcement_text">Announcement messages</label>
                <textarea
                    class="set-textarea"
                    id="announcement_text"
                    name="announcement_text"
                    rows="5"
                    placeholder="One message per line"
                ><?php echo htmlspecialchars($announcementTextValue, ENT_QUOTES, 'UTF-8'); ?></textarea>
                <span style="display:block;margin-top:8px;font-size:0.8rem;color:var(--text-soft);line-height:1.4;">
                    Put each message on a new line. Optional bold: <code>&lt;strong&gt;text&lt;/strong&gt;</code>
                </span>
            </div>
        </div>

        <div class="set-card">
            <h3 class="set-card-title">General</h3>
            <p class="set-card-sub">Basic store identity and contact details.</p>
            <div class="set-grid">
                <div class="set-field">
                    <label class="set-label" for="site_name">Site Name</label>
                    <input
                        class="set-input"
                        id="site_name"
                        type="text"
                        name="site_name"
                        value="<?php echo htmlspecialchars((string) ($settings['site_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
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
                        value="<?php echo htmlspecialchars((string) ($settings['contact_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
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
                    ><?php echo htmlspecialchars((string) ($settings['footer_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
            </div>
        </div>

        <div class="set-actions">
            <button type="submit" class="set-btn">Update Settings</button>
            <a class="set-btn set-btn-outline" href="<?php echo htmlspecialchars(url('admin/deal-settings.php'), ENT_QUOTES, 'UTF-8'); ?>">Deal Popup</a>
            <a class="set-btn set-btn-outline" href="<?php echo htmlspecialchars(url('admin/dashboard.php'), ENT_QUOTES, 'UTF-8'); ?>">Back to Dashboard</a>
        </div>
    </form>
</div>

<?php if ($flash !== null): ?>
    <div id="settingsToast" class="set-toast <?php echo $flashType === 'error' ? 'set-toast-error' : ''; ?>">
        <?php echo htmlspecialchars($flash, ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var toast = document.getElementById('settingsToast');
    if (toast) {
        requestAnimationFrame(function () {
            toast.classList.add('is-show');
        });
        setTimeout(function () {
            toast.classList.remove('is-show');
        }, 3200);
    }

    var toggle = document.getElementById('announcement_enabled');
    var label = document.getElementById('announcementStateLabel');
    if (toggle && label) {
        toggle.addEventListener('change', function () {
            label.textContent = toggle.checked ? 'Enabled' : 'Disabled';
        });
    }
});
</script>
