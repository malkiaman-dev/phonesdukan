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
$dealDefaults = getDefaultDealSettings();
$flash = null;
$flashType = 'success';

if (!empty($_SESSION['deal_settings_flash']) && is_array($_SESSION['deal_settings_flash'])) {
    $flash = (string) ($_SESSION['deal_settings_flash']['message'] ?? '');
    $flashType = (string) ($_SESSION['deal_settings_flash']['type'] ?? 'success');
    unset($_SESSION['deal_settings_flash']);
}

if (!empty($_SESSION['deal_settings_draft']) && is_array($_SESSION['deal_settings_draft'])) {
    $settings = array_merge($settings, $_SESSION['deal_settings_draft']);
    unset($_SESSION['deal_settings_draft']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dealEnabled = isset($_POST['deal_enabled']) ? 1 : 0;
    $dealBadge = trim((string) ($_POST['deal_badge'] ?? ''));
    $dealTitle = trim((string) ($_POST['deal_title'] ?? ''));
    $dealSalePrice = trim((string) ($_POST['deal_sale_price'] ?? ''));
    $dealRegularPrice = trim((string) ($_POST['deal_regular_price'] ?? ''));
    $dealEndsAt = trim((string) ($_POST['deal_ends_at'] ?? ''));
    $dealImage = trim((string) ($_POST['deal_image'] ?? ''));
    $dealCtaText = trim((string) ($_POST['deal_cta_text'] ?? ''));
    $dealCtaUrl = trim((string) ($_POST['deal_cta_url'] ?? ''));
    $dealNote = trim((string) ($_POST['deal_note'] ?? ''));

    if ($dealBadge === '') {
        $dealBadge = (string) $dealDefaults['deal_badge'];
    }
    if ($dealCtaText === '') {
        $dealCtaText = (string) $dealDefaults['deal_cta_text'];
    }
    if ($dealNote === '') {
        $dealNote = (string) $dealDefaults['deal_note'];
    }
    if ($dealImage === '') {
        $dealImage = (string) $dealDefaults['deal_image'];
    }
    if ($dealCtaUrl === '') {
        $dealCtaUrl = (string) $dealDefaults['deal_cta_url'];
    }

    if ($dealEndsAt !== '' && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/', $dealEndsAt)) {
        $dealEndsAt = str_replace('T', ' ', substr($dealEndsAt, 0, 16)) . ':00';
    }

    $dealPayload = [
        'deal_enabled' => $dealEnabled,
        'deal_badge' => $dealBadge,
        'deal_title' => $dealTitle,
        'deal_sale_price' => $dealSalePrice,
        'deal_regular_price' => $dealRegularPrice,
        'deal_ends_at' => $dealEndsAt !== '' ? $dealEndsAt : null,
        'deal_image' => $dealImage,
        'deal_cta_text' => $dealCtaText,
        'deal_cta_url' => $dealCtaUrl,
        'deal_note' => $dealNote,
    ];

    if ($dealEnabled === 1 && $dealTitle === '') {
        $_SESSION['deal_settings_flash'] = [
            'message' => 'Please enter a deal title when the popup is enabled.',
            'type' => 'error',
        ];
        $_SESSION['deal_settings_draft'] = $dealPayload;
    } else {
        try {
            $settingsId = isset($settings['id']) ? (int) $settings['id'] : 0;

            if ($settingsId > 0) {
                $update = $conn->prepare(
                    'UPDATE site_settings
                     SET deal_enabled = :deal_enabled,
                         deal_badge = :deal_badge,
                         deal_title = :deal_title,
                         deal_sale_price = :deal_sale_price,
                         deal_regular_price = :deal_regular_price,
                         deal_ends_at = :deal_ends_at,
                         deal_image = :deal_image,
                         deal_cta_text = :deal_cta_text,
                         deal_cta_url = :deal_cta_url,
                         deal_note = :deal_note
                     WHERE id = :id'
                );
                $update->bindValue(':id', $settingsId, PDO::PARAM_INT);
            } else {
                $update = $conn->prepare(
                    'INSERT INTO site_settings (
                        site_name, contact_email, footer_text, announcement_enabled, announcement_text,
                        deal_enabled, deal_badge, deal_title, deal_sale_price, deal_regular_price,
                        deal_ends_at, deal_image, deal_cta_text, deal_cta_url, deal_note
                     ) VALUES (
                        :site_name, :contact_email, :footer_text, 1, :announcement_text,
                        :deal_enabled, :deal_badge, :deal_title, :deal_sale_price, :deal_regular_price,
                        :deal_ends_at, :deal_image, :deal_cta_text, :deal_cta_url, :deal_note
                     )'
                );
                $update->bindValue(':site_name', (string) ($settings['site_name'] ?? 'Phones Dukan'), PDO::PARAM_STR);
                $update->bindValue(':contact_email', (string) ($settings['contact_email'] ?? 'info@phonesdukan.com'), PDO::PARAM_STR);
                $update->bindValue(':footer_text', (string) ($settings['footer_text'] ?? ('© ' . date('Y') . ' Phones Dukan. All Rights Reserved.')), PDO::PARAM_STR);
                $update->bindValue(':announcement_text', getDefaultAnnouncementText(), PDO::PARAM_STR);
            }

            $update->bindValue(':deal_enabled', $dealEnabled, PDO::PARAM_INT);
            $update->bindValue(':deal_badge', $dealBadge, PDO::PARAM_STR);
            $update->bindValue(':deal_title', $dealTitle, PDO::PARAM_STR);
            $update->bindValue(':deal_sale_price', $dealSalePrice, PDO::PARAM_STR);
            $update->bindValue(':deal_regular_price', $dealRegularPrice, PDO::PARAM_STR);
            if ($dealEndsAt === '') {
                $update->bindValue(':deal_ends_at', null, PDO::PARAM_NULL);
            } else {
                $update->bindValue(':deal_ends_at', $dealEndsAt, PDO::PARAM_STR);
            }
            $update->bindValue(':deal_image', $dealImage, PDO::PARAM_STR);
            $update->bindValue(':deal_cta_text', $dealCtaText, PDO::PARAM_STR);
            $update->bindValue(':deal_cta_url', $dealCtaUrl, PDO::PARAM_STR);
            $update->bindValue(':deal_note', $dealNote, PDO::PARAM_STR);
            $update->execute();

            $_SESSION['deal_settings_flash'] = [
                'message' => 'Deal popup settings saved successfully.',
                'type' => 'success',
            ];
        } catch (Throwable $e) {
            error_log('deal-settings.php update: ' . $e->getMessage());
            $_SESSION['deal_settings_flash'] = [
                'message' => 'Failed to save deal settings.',
                'type' => 'error',
            ];
            $_SESSION['deal_settings_draft'] = $dealPayload;
        }
    }

    header('Location: ' . url('admin/deal-settings.php'));
    exit();
}

$dealOn = (int) ($settings['deal_enabled'] ?? 0) === 1;
$dealEndsAtValue = trim((string) ($settings['deal_ends_at'] ?? ''));
$dealEndsAtInput = '';
$dealExpired = false;
if ($dealEndsAtValue !== '') {
    $ts = strtotime($dealEndsAtValue);
    if ($ts !== false) {
        $dealEndsAtInput = date('Y-m-d\TH:i', $ts);
        $dealExpired = $ts < time();
    }
}

$dealLive = $dealOn && !$dealExpired && trim((string) ($settings['deal_title'] ?? '')) !== '';
$previewImage = resolveDealImageUrl((string) ($settings['deal_image'] ?? $dealDefaults['deal_image']));
$previewSale = formatDealPriceDisplay((string) ($settings['deal_sale_price'] ?? ''));
$previewRegular = formatDealPriceDisplay((string) ($settings['deal_regular_price'] ?? ''));

$productOptions = [];
try {
    $productSql = "SELECT p.product_id, p.product_name, p.product_slug, p.sale_price, p.regular_price,
                          pi.image_url
                   FROM products p
                   LEFT JOIN product_images pi
                     ON pi.product_id = p.product_id AND pi.is_primary = 1 AND pi.status = 1
                   ORDER BY p.product_id DESC
                   LIMIT 120";
    $productOptions = $conn->query($productSql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $productOptions = [];
}

$productPickerData = [];
foreach ($productOptions as $product) {
    $image = trim((string) ($product['image_url'] ?? ''));
    if ($image !== '' && $image[0] === '/') {
        $image = ltrim($image, '/');
    }
    $cta = '';
    try {
        if (function_exists('buildProductPathFromRow')) {
            $cta = ltrim((string) buildProductPathFromRow($product), '/');
        }
    } catch (Throwable $e) {
        $cta = '';
    }
    $productPickerData[] = [
        'id' => (int) ($product['product_id'] ?? 0),
        'name' => (string) ($product['product_name'] ?? ''),
        'sale' => (string) ($product['sale_price'] ?? ''),
        'regular' => (string) ($product['regular_price'] ?? ''),
        'image' => $image,
        'cta' => $cta,
    ];
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
    .deal-wrap,
    .deal-wrap * { box-sizing: border-box; }
    .deal-wrap {
        max-width: 1180px;
        width: 100%;
        margin: 0 auto 40px;
        padding: 8px 28px 40px;
        box-sizing: border-box;
    }
    .deal-head {
        background: linear-gradient(120deg, var(--black) 0%, var(--black-soft) 100%);
        color: var(--white);
        border-radius: 20px;
        padding: 26px 24px;
        border: 1px solid #1f1f24;
        margin-bottom: 18px;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }
    .deal-title {
        margin: 0 0 6px;
        font-size: clamp(1.5rem, 2vw, 1.75rem);
        font-weight: 600;
        line-height: 1.25;
        letter-spacing: -0.02em;
        color: #ffffff !important;
    }
    .deal-sub {
        margin: 0;
        color: rgba(255, 255, 255, 0.78);
        font-size: 0.875rem;
        line-height: 1.5;
    }
    .deal-status {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.03em;
        text-transform: uppercase;
        background: rgba(255,255,255,0.08);
        color: #fff;
        border: 1px solid rgba(255,255,255,0.12);
        white-space: nowrap;
    }
    .deal-status.is-live { background: rgba(250, 204, 21, 0.16); color: #facc15; border-color: rgba(250, 204, 21, 0.35); }
    .deal-status.is-off { background: rgba(239, 68, 68, 0.14); color: #fca5a5; border-color: rgba(239, 68, 68, 0.28); }
    .deal-status.is-expired { background: rgba(245, 158, 11, 0.16); color: #fcd34d; border-color: rgba(245, 158, 11, 0.3); }
    .deal-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) minmax(280px, 0.8fr);
        gap: 16px;
        align-items: start;
    }
    .deal-card {
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 18px;
        padding: 20px;
        box-shadow: 0 12px 34px rgba(15, 15, 16, 0.06);
        margin-bottom: 16px;
        overflow: visible;
        max-width: 100%;
        position: relative;
        z-index: 1;
    }
    .deal-card:has(.deal-custom-select.is-open) {
        z-index: 50;
    }
    .deal-card-title {
        margin: 0 0 4px;
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--black);
    }
    .deal-card-sub {
        margin: 0 0 18px;
        color: var(--text-soft);
        font-size: 0.86rem;
        line-height: 1.45;
    }
    .deal-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
        width: 100%;
    }
    .deal-field {
        display: flex;
        flex-direction: column;
        gap: 8px;
        min-width: 0;
        max-width: 100%;
    }
    .deal-field-full { grid-column: 1 / -1; }
    .deal-label {
        font-size: .84rem;
        font-weight: 700;
        color: var(--black);
    }
    .deal-hint {
        display: block;
        margin-top: 6px;
        font-size: 0.8rem;
        color: var(--text-soft);
        line-height: 1.4;
    }
    .deal-input,
    .deal-select {
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
    .deal-input:focus,
    .deal-select:focus {
        border-color: var(--yellow);
        box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.18);
    }
    .deal-toggle-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 14px 16px;
        border: 1px solid var(--border);
        border-radius: 14px;
        background: var(--muted);
        max-width: 100%;
        margin-bottom: 16px;
    }
    .deal-toggle-copy strong {
        display: block;
        font-size: 0.92rem;
        color: var(--black);
        margin-bottom: 4px;
    }
    .deal-toggle-copy span {
        display: block;
        font-size: 0.82rem;
        color: var(--text-soft);
        line-height: 1.4;
    }
    .deal-switch {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        user-select: none;
        flex-shrink: 0;
    }
    .deal-switch input { position: absolute; opacity: 0; width: 0; height: 0; }
    .deal-switch-track {
        width: 48px;
        height: 28px;
        border-radius: 999px;
        background: #d4d4d8;
        border: 1px solid #c4c4cc;
        position: relative;
        transition: background .18s ease, border-color .18s ease;
    }
    .deal-switch-track::after {
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
    .deal-switch input:checked + .deal-switch-track {
        background: var(--yellow);
        border-color: #eab308;
    }
    .deal-switch input:checked + .deal-switch-track::after {
        transform: translateX(20px);
    }
    .deal-switch-state {
        min-width: 64px;
        font-size: 0.82rem;
        font-weight: 700;
        color: var(--black);
    }
    .deal-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 4px;
    }
    .deal-btn {
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
    .deal-btn:hover {
        color: var(--yellow);
        transform: translateY(-1px);
        text-decoration: none !important;
    }
    .deal-btn-outline {
        background: #fff;
        color: var(--black);
        border-color: var(--border);
    }
    .deal-preview {
        position: sticky;
        top: 76px;
    }
    .deal-preview-box {
        border-radius: 18px;
        overflow: hidden;
        border: 1px solid var(--border);
        background: #111;
        box-shadow: 0 16px 40px rgba(15, 15, 16, 0.12);
    }
    .deal-preview-media {
        min-height: 220px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
        background:
            radial-gradient(ellipse 70% 55% at 50% 42%, rgba(247, 209, 23, 0.22) 0%, transparent 62%),
            linear-gradient(160deg, #1c1c1c 0%, #0d0d0d 42%, #16120a 100%);
    }
    .deal-preview-media img {
        width: min(78%, 220px);
        max-height: 180px;
        object-fit: contain;
        filter: drop-shadow(0 16px 24px rgba(0,0,0,.45));
    }
    .deal-preview-copy {
        background: #fff;
        padding: 18px;
    }
    .deal-preview-badge {
        display: inline-flex;
        padding: 5px 10px;
        border-radius: 999px;
        background: #111;
        color: #f7d117;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        margin-bottom: 10px;
    }
    .deal-preview-title {
        margin: 0 0 8px;
        font-size: 1.05rem;
        font-weight: 800;
        color: #111;
        line-height: 1.25;
    }
    .deal-preview-prices {
        display: flex;
        align-items: baseline;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 8px;
    }
    .deal-preview-sale {
        font-size: 1.35rem;
        font-weight: 900;
        color: #111;
    }
    .deal-preview-old {
        color: #9ca3af;
        text-decoration: line-through;
        font-weight: 600;
        font-size: 0.92rem;
    }
    .deal-preview-meta {
        margin: 0;
        color: var(--text-soft);
        font-size: 0.8rem;
        line-height: 1.4;
    }
    .deal-toast {
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
    .deal-toast.is-show { transform: translateY(0); opacity: 1; }
    .deal-toast-error { border-left-color: #f59e0b; }

    .deal-custom-select {
        position: relative;
        width: 100%;
        z-index: 20;
    }
    .deal-custom-select.is-open {
        z-index: 80;
    }
    .deal-custom-select-btn {
        position: relative;
        width: 100%;
        height: 48px;
        border: 1px solid var(--border);
        border-radius: 12px;
        background: #fff;
        color: var(--black);
        padding: 0 44px 0 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
        transition: border-color .2s ease, box-shadow .2s ease, border-radius .15s ease;
        text-align: left;
        font-size: .92rem;
        font-weight: 600;
    }
    .deal-custom-select-btn:hover {
        border-color: var(--yellow);
    }
    .deal-custom-select.is-open .deal-custom-select-btn {
        border-color: var(--yellow);
        border-bottom-left-radius: 0;
        border-bottom-right-radius: 0;
        box-shadow: none;
    }
    .deal-custom-select-btn:focus-visible {
        outline: none;
        border-color: var(--yellow);
        box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.18);
    }
    .deal-custom-select-value {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        color: var(--black);
        padding-right: 8px;
    }
    .deal-custom-select-arrow {
        position: absolute;
        right: 16px;
        top: 50%;
        width: 8px;
        height: 8px;
        margin-top: -6px;
        border-right: 2px solid #6b7280;
        border-bottom: 2px solid #6b7280;
        transform: rotate(45deg);
        transition: transform .18s ease;
        pointer-events: none;
    }
    .deal-custom-select.is-open .deal-custom-select-arrow {
        margin-top: -2px;
        transform: rotate(-135deg);
    }
    .deal-custom-select-menu {
        position: absolute;
        left: 0;
        right: 0;
        top: 100%;
        z-index: 90;
        display: none;
        background: #fff;
        border: 1px solid var(--yellow);
        border-top: 0;
        border-radius: 0 0 14px 14px;
        box-shadow: 0 18px 36px rgba(15, 23, 42, 0.14);
        overflow: hidden;
    }
    .deal-custom-select.is-open .deal-custom-select-menu {
        display: block;
    }
    .deal-custom-select-search {
        padding: 10px 10px 8px;
        border-bottom: 1px solid #eee;
        background: #fff;
    }
    .deal-custom-select-search input {
        width: 100%;
        height: 40px;
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 0 12px;
        font-size: .88rem;
        outline: none;
        background: #fafafa;
        box-sizing: border-box;
        box-shadow: none;
    }
    .deal-custom-select-search input:focus {
        border-color: #e5e7eb;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.14);
    }
    .deal-custom-select-search input::-webkit-search-decoration,
    .deal-custom-select-search input::-webkit-search-cancel-button,
    .deal-custom-select-search input::-webkit-search-results-button {
        -webkit-appearance: none;
    }
    .deal-custom-select-list {
        max-height: 260px;
        overflow: auto;
        padding: 6px;
        background: #fff;
    }
    .deal-custom-select-option {
        padding: 10px 12px;
        border-radius: 10px;
        cursor: pointer;
        font-size: .88rem;
        font-weight: 600;
        color: #374151;
        line-height: 1.35;
    }
    .deal-custom-select-option:hover,
    .deal-custom-select-option.is-active {
        background: #fffbeb;
        color: #111;
    }
    .deal-custom-select-option.is-selected {
        background: #fffbeb;
        color: #111;
        border-left: 3px solid var(--yellow);
    }
    .deal-custom-select-empty {
        padding: 16px 12px;
        color: var(--text-soft);
        font-size: .86rem;
        text-align: center;
    }
    .deal-upload-box {
        display: grid;
        gap: 6px;
        padding: 20px;
        border: 1px dashed var(--border);
        border-radius: 14px;
        background: #fff;
        cursor: pointer;
        transition: border-color .2s ease, background-color .2s ease, box-shadow .2s ease;
        user-select: none;
    }
    .deal-upload-box:hover,
    .deal-upload-box.is-dragging {
        border-color: var(--yellow);
        background: #fffbeb;
        box-shadow: 0 10px 22px rgba(17, 17, 17, 0.05);
    }
    .deal-upload-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        background: var(--yellow);
        color: #111;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
    }
    .deal-upload-title {
        font-weight: 700;
        color: #111;
        font-size: 14px;
    }
    .deal-upload-help {
        font-size: 12px;
        color: #6b7280;
    }
    .deal-upload-filename {
        font-size: 12px;
        color: #111;
        opacity: 0.85;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .deal-upload-status {
        margin-top: 8px;
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--text-soft);
    }
    .deal-upload-status.is-ok { color: #16a34a; }
    .deal-upload-status.is-err { color: #dc2626; }
    .deal-file-input {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        border: 0;
    }
    .deal-image-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 12px;
    }

    @media (max-width: 980px) {
        .deal-layout { grid-template-columns: 1fr; }
        .deal-preview { position: static; }
    }
    @media (max-width: 720px) {
        .deal-grid { grid-template-columns: 1fr; }
        .deal-toggle-row { flex-direction: column; align-items: flex-start; }
    }
</style>

<main class="content">
<div class="deal-wrap">
    <div class="deal-head">
        <div>
            <h2 class="deal-title">Deal Popup</h2>
            <p class="deal-sub">Enable, update, and manage the Deal of the Day banner shown to website visitors.</p>
        </div>
        <?php if ($dealLive): ?>
            <span class="deal-status is-live">Live on storefront</span>
        <?php elseif ($dealOn && $dealExpired): ?>
            <span class="deal-status is-expired">Enabled but expired</span>
        <?php else: ?>
            <span class="deal-status is-off">Disabled</span>
        <?php endif; ?>
    </div>

    <div class="deal-layout">
        <form method="POST" action="" id="dealSettingsForm">
            <div class="deal-card">
                <h3 class="deal-card-title">Popup status</h3>
                <p class="deal-card-sub">Turn the deal banner on or off for all storefront visitors.</p>
                <div class="deal-toggle-row">
                    <div class="deal-toggle-copy">
                        <strong>Show deal popup</strong>
                        <span>When enabled, the popup appears shortly after page load until the visitor closes it.</span>
                    </div>
                    <label class="deal-switch" for="deal_enabled">
                        <input type="checkbox" id="deal_enabled" name="deal_enabled" value="1" <?php echo $dealOn ? 'checked' : ''; ?>>
                        <span class="deal-switch-track" aria-hidden="true"></span>
                        <span class="deal-switch-state" id="dealStateLabel"><?php echo $dealOn ? 'Enabled' : 'Disabled'; ?></span>
                    </label>
                </div>
            </div>

            <div class="deal-card">
                <h3 class="deal-card-title">Fill from product</h3>
                <p class="deal-card-sub">Search and pick a catalog product to auto-fill title, prices, image, and product link.</p>
                <div class="deal-field">
                    <label class="deal-label">Select product</label>
                    <div class="deal-custom-select" id="dealProductSelect">
                        <button type="button" class="deal-custom-select-btn" id="dealProductSelectBtn" aria-haspopup="listbox" aria-expanded="false">
                            <span class="deal-custom-select-value" id="dealProductSelectValue">— Choose a product (optional) —</span>
                            <span class="deal-custom-select-arrow" aria-hidden="true"></span>
                        </button>
                        <div class="deal-custom-select-menu" id="dealProductSelectMenu">
                            <div class="deal-custom-select-search">
                                <input type="search" id="dealProductSearch" placeholder="Search products..." autocomplete="off">
                            </div>
                            <div class="deal-custom-select-list" id="dealProductSelectList" role="listbox"></div>
                        </div>
                    </div>
                    <input type="hidden" id="deal_product_picker" value="">
                    <span class="deal-hint">You can still edit any field manually after selecting a product.</span>
                </div>
            </div>

            <div class="deal-card">
                <h3 class="deal-card-title">Deal content</h3>
                <p class="deal-card-sub">Configure the message, pricing, countdown, and call-to-action.</p>
                <div class="deal-grid">
                    <div class="deal-field">
                        <label class="deal-label" for="deal_badge">Badge</label>
                        <input class="deal-input" id="deal_badge" type="text" name="deal_badge"
                            value="<?php echo htmlspecialchars((string) ($settings['deal_badge'] ?? $dealDefaults['deal_badge']), ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="Deal of the Day">
                    </div>
                    <div class="deal-field">
                        <label class="deal-label" for="deal_ends_at">Offer ends at</label>
                        <input class="deal-input" id="deal_ends_at" type="datetime-local" name="deal_ends_at"
                            value="<?php echo htmlspecialchars($dealEndsAtInput, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="deal-field deal-field-full">
                        <label class="deal-label" for="deal_title">Deal title</label>
                        <input class="deal-input" id="deal_title" type="text" name="deal_title"
                            value="<?php echo htmlspecialchars((string) ($settings['deal_title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="Product or offer title">
                    </div>
                    <div class="deal-field">
                        <label class="deal-label" for="deal_sale_price">Sale price</label>
                        <input class="deal-input" id="deal_sale_price" type="text" name="deal_sale_price"
                            value="<?php echo htmlspecialchars((string) ($settings['deal_sale_price'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="4999">
                    </div>
                    <div class="deal-field">
                        <label class="deal-label" for="deal_regular_price">Regular price</label>
                        <input class="deal-input" id="deal_regular_price" type="text" name="deal_regular_price"
                            value="<?php echo htmlspecialchars((string) ($settings['deal_regular_price'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="7999">
                    </div>
                    <div class="deal-field deal-field-full">
                        <label class="deal-label">Product image</label>
                        <div class="deal-image-row">
                            <label class="deal-upload-box" for="deal_image_file" id="dealUploadBox">
                                <span class="deal-upload-icon" aria-hidden="true"><i class="fas fa-upload"></i></span>
                                <span class="deal-upload-title">Click to upload from device</span>
                                <span class="deal-upload-help">PNG, JPG, WEBP up to 5MB</span>
                                <span class="deal-upload-filename" id="dealUploadFilename">No file selected</span>
                            </label>
                            <input class="deal-file-input" type="file" id="deal_image_file" accept="image/jpeg,image/png,image/webp,image/gif,.jpg,.jpeg,.png,.webp,.gif">
                            <div class="deal-upload-status" id="dealUploadStatus"></div>
                            <label class="deal-label" for="deal_image">Or paste image path / URL</label>
                            <input class="deal-input" id="deal_image" type="text" name="deal_image"
                                value="<?php echo htmlspecialchars((string) ($settings['deal_image'] ?? $dealDefaults['deal_image']), ENT_QUOTES, 'UTF-8'); ?>"
                                placeholder="public/uploads/your-product.webp">
                            <span class="deal-hint">Upload from your device, or use a path from Media Library / uploads.</span>
                        </div>
                    </div>
                    <div class="deal-field">
                        <label class="deal-label" for="deal_cta_text">Button text</label>
                        <input class="deal-input" id="deal_cta_text" type="text" name="deal_cta_text"
                            value="<?php echo htmlspecialchars((string) ($settings['deal_cta_text'] ?? $dealDefaults['deal_cta_text']), ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="Shop This Deal">
                    </div>
                    <div class="deal-field">
                        <label class="deal-label" for="deal_cta_url">Button link</label>
                        <input class="deal-input" id="deal_cta_url" type="text" name="deal_cta_url"
                            value="<?php echo htmlspecialchars((string) ($settings['deal_cta_url'] ?? $dealDefaults['deal_cta_url']), ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="product-url/ or full link">
                    </div>
                    <div class="deal-field deal-field-full">
                        <label class="deal-label" for="deal_note">Countdown label</label>
                        <input class="deal-input" id="deal_note" type="text" name="deal_note"
                            value="<?php echo htmlspecialchars((string) ($settings['deal_note'] ?? $dealDefaults['deal_note']), ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="Hurry up! Offer ends in:">
                    </div>
                </div>
            </div>

            <div class="deal-actions">
                <button type="submit" class="deal-btn">Save Deal Settings</button>
                <a class="deal-btn deal-btn-outline" href="<?php echo htmlspecialchars(url(), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Preview Website</a>
                <a class="deal-btn deal-btn-outline" href="<?php echo htmlspecialchars(url('admin/settings.php'), ENT_QUOTES, 'UTF-8'); ?>">Site Settings</a>
            </div>
        </form>

        <aside class="deal-preview">
            <div class="deal-card" style="margin-bottom:0;">
                <h3 class="deal-card-title">Live preview</h3>
                <p class="deal-card-sub">Quick look at how the deal content will appear.</p>
                <div class="deal-preview-box">
                    <div class="deal-preview-media">
                        <img id="dealPreviewImage" src="<?php echo htmlspecialchars($previewImage, ENT_QUOTES, 'UTF-8'); ?>" alt="Deal preview">
                    </div>
                    <div class="deal-preview-copy">
                        <div class="deal-preview-badge" id="dealPreviewBadge"><?php echo htmlspecialchars((string) ($settings['deal_badge'] ?? $dealDefaults['deal_badge']), ENT_QUOTES, 'UTF-8'); ?></div>
                        <h4 class="deal-preview-title" id="dealPreviewTitle"><?php echo htmlspecialchars((string) ($settings['deal_title'] ?? 'Deal title'), ENT_QUOTES, 'UTF-8'); ?></h4>
                        <div class="deal-preview-prices">
                            <span class="deal-preview-sale" id="dealPreviewSale"><?php echo htmlspecialchars($previewSale !== '' ? $previewSale : 'Rs. 0', ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="deal-preview-old" id="dealPreviewRegular"><?php echo htmlspecialchars($previewRegular, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <p class="deal-preview-meta" id="dealPreviewNote"><?php echo htmlspecialchars((string) ($settings['deal_note'] ?? $dealDefaults['deal_note']), ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>
</main>

<?php if ($flash !== null): ?>
    <div id="dealToast" class="deal-toast <?php echo $flashType === 'error' ? 'deal-toast-error' : ''; ?>">
        <?php echo htmlspecialchars($flash, ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php endif; ?>

<script>
window.PD_DEAL_PRODUCTS = <?php echo json_encode($productPickerData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
window.PD_DEAL_UPLOAD_URL = <?php echo json_encode(url('admin/ajax-upload-deal-image.php'), JSON_UNESCAPED_SLASHES); ?>;
window.PD_BASE_PATH = <?php echo json_encode(rtrim((string) (defined('BASE_PATH') ? BASE_PATH : getBasePath()), '/'), JSON_UNESCAPED_SLASHES); ?>;
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var toast = document.getElementById('dealToast');
    if (toast) {
        requestAnimationFrame(function () { toast.classList.add('is-show'); });
        setTimeout(function () { toast.classList.remove('is-show'); }, 3200);
    }

    var toggle = document.getElementById('deal_enabled');
    var label = document.getElementById('dealStateLabel');
    if (toggle && label) {
        toggle.addEventListener('change', function () {
            label.textContent = toggle.checked ? 'Enabled' : 'Disabled';
        });
    }

    function formatPrice(raw) {
        var value = String(raw || '').trim();
        if (!value) return '';
        if (/[a-zA-Z]/.test(value)) return value;
        var numeric = value.replace(/[^\d.]/g, '');
        if (!numeric || isNaN(Number(numeric))) return value;
        var num = Number(numeric);
        return 'Rs. ' + num.toLocaleString('en-PK', {
            maximumFractionDigits: Math.abs(num % 1) < 0.001 ? 0 : 2
        });
    }

    function resolvePreviewImage(path) {
        var src = String(path || '').trim().replace(/\\/g, '/');
        if (!src) return '';
        if (/^https?:\/\//i.test(src)) return src;

        var base = String(window.PD_BASE_PATH || '').replace(/\/+$/, '');
        src = src.replace(/^\/+/, '');
        if (base) {
            var baseTrim = base.replace(/^\/+/, '');
            if (src.indexOf(baseTrim + '/') === 0) {
                src = src.slice(baseTrim.length + 1);
            }
        }
        return (base ? base : '') + '/' + src;
    }

    function syncPreview() {
        var badge = document.getElementById('deal_badge');
        var title = document.getElementById('deal_title');
        var sale = document.getElementById('deal_sale_price');
        var regular = document.getElementById('deal_regular_price');
        var note = document.getElementById('deal_note');
        var image = document.getElementById('deal_image');

        var previewBadge = document.getElementById('dealPreviewBadge');
        var previewTitle = document.getElementById('dealPreviewTitle');
        var previewSale = document.getElementById('dealPreviewSale');
        var previewRegular = document.getElementById('dealPreviewRegular');
        var previewNote = document.getElementById('dealPreviewNote');
        var previewImage = document.getElementById('dealPreviewImage');

        if (previewBadge && badge) previewBadge.textContent = badge.value || 'Deal of the Day';
        if (previewTitle && title) previewTitle.textContent = title.value || 'Deal title';
        if (previewSale && sale) previewSale.textContent = formatPrice(sale.value) || 'Rs. 0';
        if (previewRegular && regular) previewRegular.textContent = formatPrice(regular.value);
        if (previewNote && note) previewNote.textContent = note.value || 'Hurry up! Offer ends in:';
        if (previewImage && image) {
            var next = resolvePreviewImage(image.value);
            if (next) previewImage.src = next;
        }
    }

    ['deal_badge', 'deal_title', 'deal_sale_price', 'deal_regular_price', 'deal_note', 'deal_image'].forEach(function (id) {
        var el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('input', syncPreview);
        el.addEventListener('change', syncPreview);
    });

    function applyProduct(match) {
        if (!match) return;
        var title = document.getElementById('deal_title');
        var sale = document.getElementById('deal_sale_price');
        var regular = document.getElementById('deal_regular_price');
        var image = document.getElementById('deal_image');
        var cta = document.getElementById('deal_cta_url');
        var filename = document.getElementById('dealUploadFilename');

        if (title) title.value = match.name || '';
        if (sale) {
            var saleVal = Number(match.sale || 0);
            var regularVal = Number(match.regular || 0);
            sale.value = (saleVal > 0 && regularVal > 0 && saleVal < regularVal)
                ? String(saleVal)
                : (regularVal > 0 ? String(regularVal) : String(match.sale || ''));
        }
        if (regular) regular.value = match.regular || '';
        if (image && match.image) image.value = match.image;
        if (cta && match.cta) cta.value = match.cta;
        if (filename && match.image) {
            filename.textContent = String(match.image).split('/').pop();
        }
        syncPreview();
    }

    // Searchable product picker
    (function initProductPicker() {
        var root = document.getElementById('dealProductSelect');
        var btn = document.getElementById('dealProductSelectBtn');
        var menu = document.getElementById('dealProductSelectMenu');
        var list = document.getElementById('dealProductSelectList');
        var search = document.getElementById('dealProductSearch');
        var valueEl = document.getElementById('dealProductSelectValue');
        var hidden = document.getElementById('deal_product_picker');
        var products = window.PD_DEAL_PRODUCTS || [];
        if (!root || !btn || !menu || !list || !search || !valueEl || !hidden) return;

        var selectedId = '';

        function openMenu() {
            root.classList.add('is-open');
            btn.setAttribute('aria-expanded', 'true');
            search.value = '';
            renderList('');
            setTimeout(function () { search.focus(); }, 0);
        }

        function closeMenu() {
            root.classList.remove('is-open');
            btn.setAttribute('aria-expanded', 'false');
        }

        function renderList(query) {
            var q = String(query || '').trim().toLowerCase();
            list.innerHTML = '';
            var matched = products.filter(function (item) {
                if (!q) return true;
                return String(item.name || '').toLowerCase().indexOf(q) !== -1;
            });

            if (!matched.length) {
                var empty = document.createElement('div');
                empty.className = 'deal-custom-select-empty';
                empty.textContent = 'No products found';
                list.appendChild(empty);
                return;
            }

            matched.slice(0, 80).forEach(function (item) {
                var opt = document.createElement('div');
                opt.className = 'deal-custom-select-option';
                opt.setAttribute('role', 'option');
                opt.dataset.id = String(item.id);
                opt.textContent = item.name || ('Product #' + item.id);
                if (String(item.id) === String(selectedId)) {
                    opt.classList.add('is-selected');
                }
                opt.addEventListener('click', function () {
                    selectedId = String(item.id);
                    hidden.value = selectedId;
                    valueEl.textContent = item.name || ('Product #' + item.id);
                    applyProduct(item);
                    closeMenu();
                });
                list.appendChild(opt);
            });
        }

        btn.addEventListener('click', function () {
            if (root.classList.contains('is-open')) {
                closeMenu();
            } else {
                openMenu();
            }
        });

        search.addEventListener('input', function () {
            renderList(search.value);
        });

        search.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                e.preventDefault();
                closeMenu();
                btn.focus();
            }
        });

        document.addEventListener('click', function (e) {
            if (!root.contains(e.target)) {
                closeMenu();
            }
        });
    })();

    // Device image upload
    (function initImageUpload() {
        var fileInput = document.getElementById('deal_image_file');
        var uploadBox = document.getElementById('dealUploadBox');
        var filenameEl = document.getElementById('dealUploadFilename');
        var statusEl = document.getElementById('dealUploadStatus');
        var imageField = document.getElementById('deal_image');
        if (!fileInput || !uploadBox || !filenameEl || !statusEl || !imageField) return;

        function setStatus(message, type) {
            statusEl.textContent = message || '';
            statusEl.classList.remove('is-ok', 'is-err');
            if (type) statusEl.classList.add(type);
        }

        async function uploadFile(file) {
            if (!file) return;
            filenameEl.textContent = file.name;
            setStatus('Uploading…', '');

            var fd = new FormData();
            fd.append('deal_image', file);

            try {
                var res = await fetch(window.PD_DEAL_UPLOAD_URL, {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin'
                });
                var data = await res.json();
                if (!data || !data.success || !data.path) {
                    throw new Error((data && data.message) || 'Upload failed');
                }
                imageField.value = data.path;
                setStatus('Image uploaded successfully.', 'is-ok');
                syncPreview();
            } catch (err) {
                setStatus(err.message || 'Upload failed.', 'is-err');
            }
        }

        fileInput.addEventListener('change', function () {
            var file = fileInput.files && fileInput.files[0];
            if (file) uploadFile(file);
        });

        ['dragenter', 'dragover'].forEach(function (evt) {
            uploadBox.addEventListener(evt, function (e) {
                e.preventDefault();
                e.stopPropagation();
                uploadBox.classList.add('is-dragging');
            });
        });
        ['dragleave', 'drop'].forEach(function (evt) {
            uploadBox.addEventListener(evt, function (e) {
                e.preventDefault();
                e.stopPropagation();
                uploadBox.classList.remove('is-dragging');
            });
        });
        uploadBox.addEventListener('drop', function (e) {
            var file = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
            if (file) uploadFile(file);
        });

        if (imageField.value) {
            filenameEl.textContent = imageField.value.split('/').pop();
        }
    })();
});
</script>
