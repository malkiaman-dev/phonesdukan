<?php
require_once dirname(__DIR__, 2) . '/app/Controllers/PostController.php';

/* ── Set variables header.php expects ── */
$pageTitle = $metaTitle ?? ($post['title'] ?? 'Blog | Phones Dukan');

require_once dirname(__DIR__, 2) . '/includes/header.php';

/* ── Fetch inline gallery images ── */
$postModel          = new PostModel();
$non_primary_images = $postModel->getNonPrimaryImages($post['id']);

/* ── Shortcode: [pta_calculator] ── */
function pta_calculator_shortcode($data) {
    if (!isset($data['phone_models']) || !isset($data['usd_to_pkr'])) {
        return '<p>Error: Calculator data not available.</p>';
    }

    $calculator_html = '
    <div class="pta-calculator">
        <div class="content-area">
            <h2>PTA Tax Calculator Pakistan ' . date('F Y') . '</h2>
            <p class="exchange-rate-info">Instantly calculate mobile import taxes with our updated PTA Tax Calculator for ' . date('F Y') . ', aligned with Pakistan\'s latest budget and PTA regulations</p>
            <div class="calculator-form">
                <div>
                    <select name="brand" id="brand">
                        <option value="">Select a brand</option>';

    foreach (array_keys($data['phone_models']) as $brand) {
        $calculator_html .= '<option value="' . htmlspecialchars(trim(ucfirst(strtolower($brand)))) . '">' . htmlspecialchars($brand) . '</option>';
    }

    $calculator_html .= '
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div>
                    <select name="model" id="model">
                        <option value="">Select a model</option>
                    </select>
                </div>
                <div id="custom-price-wrapper" class="hidden" style="display: none;">
                    <label for="custom_price">Enter Price (PKR)</label>
                    <input type="number" step="0.01" min="0" name="custom_price" id="custom_price" placeholder="e.g., 200000">
                </div>
                <p class="error-message" style="display: none;"></p>
                <div class="results" style="display: none;">
                    <p class="passport-tax"><strong>Passport Registration:</strong> <span></span></p>
                    <p class="cnic-tax"><strong>CNIC Registration:</strong> <span></span></p>
                </div>
            </div>
        </div>
    </div>';

    $calculator_html .= '
    <script>
        const phoneModels = ' . json_encode($data['phone_models']) . ';
        const usdToPkr = ' . json_encode($data['usd_to_pkr']) . ';
    </script>';

    return $calculator_html;
}

/* ── Shortcode: [cnic_check] ── */
function cnic_check_shortcode() {
    return '
    <div class="cnic-check">
        <div class="content-area">
            <h2>Check Ehsaas Program Eligibility with CNIC</h2>
            <p>Enter your 13-digit CNIC number below to check your eligibility for the Ehsaas Program via SMS to 8171.</p>
            <div class="cnic-form">
                <label for="cnic_input">CNIC Number (without dashes)</label>
                <input type="text" name="cnic_input" id="cnic_input" placeholder="e.g., 1234512345671" maxlength="13" pattern="[0-9]{13}" required>
                <button type="button" onclick="sendCnicSMS()">Submit</button>
                <p class="error-message" style="display: none; color: red;"></p>
            </div>
            <p><strong>Note:</strong> Use the SIM registered with your CNIC. Standard SMS charges apply (Rs. 1–2).</p>
        </div>
        <script>
            function sendCnicSMS() {
                var cnicInput = document.getElementById("cnic_input").value;
                var errorMessage = document.querySelector(".cnic-form .error-message");
                var cnicPattern = /^[0-9]{13}$/;
                if (!cnicPattern.test(cnicInput)) {
                    errorMessage.textContent = "Please enter a valid 13-digit CNIC number without dashes.";
                    errorMessage.style.display = "block";
                    return;
                }
                var smsUri = "sms:8171?body=" + encodeURIComponent(cnicInput);
                try {
                    window.location.href = smsUri;
                    errorMessage.style.display = "none";
                } catch (e) {
                    errorMessage.textContent = "Unable to open SMS app. Please send your CNIC to 8171 manually.";
                    errorMessage.style.display = "block";
                }
            }
        </script>
    </div>';
}

/* ── Process shortcodes ── */
$content = $post['content'];

if (!empty($non_primary_images)) {
    foreach ($non_primary_images as $image) {
        $shortcode = "[image id={$image['id']}]";
        $img_tag   = '<figure class="pv-inline-fig">'
                   . '<img src="' . htmlspecialchars($image['image_url']) . '" alt="' . htmlspecialchars($image['alt_text'] ?? $post['title']) . '" loading="lazy">'
                   . '</figure>';
        $content   = str_replace($shortcode, $img_tag, $content);
    }
}

$content = str_replace('[pta_calculator]', pta_calculator_shortcode($data), $content);
$content = str_replace('[cnic_check]',     cnic_check_shortcode(),           $content);

/* ── Date logic ── */
$published_at   = new DateTime($post['published_at']);
$updated_at     = new DateTime($post['updated_at']);
$hours_diff     = (int)(($published_at->diff($updated_at)->days * 24) + $published_at->diff($updated_at)->h);
$display_date   = ($hours_diff > 24)
    ? 'Updated ' . $updated_at->format('F j, Y')
    : $published_at->format('F j, Y');

/* ── Category href ── */
$cat_href  = ($post['category_slug'] === 'news') ? '/news' : '/blog/' . htmlspecialchars($post['category_slug']);
$cat_label = htmlspecialchars($post['category_name'] ?? 'Blog');
?>
<link rel="stylesheet" href="<?= url('public/assets/css/frontend/post_view.css') ?>">

<!-- ══════════════════════════════════════════
     POST HERO
══════════════════════════════════════════ -->
<div class="pv-hero">
    <div class="pv-hero-inner">

        <!-- Breadcrumb -->
        <nav class="pv-breadcrumb" aria-label="Breadcrumb">
            <a href="/">Home</a>
            <span aria-hidden="true">›</span>
            <?php if ($post['category_slug'] === 'news'): ?>
                <a href="/news">News</a>
                <span aria-hidden="true">›</span>
                <span><?= htmlspecialchars($post['title']) ?></span>
            <?php else: ?>
                <a href="/blog">Blog</a>
                <span aria-hidden="true">›</span>
                <a href="<?= $cat_href ?>"><?= $cat_label ?></a>
                <span aria-hidden="true">›</span>
                <span><?= htmlspecialchars($post['title']) ?></span>
            <?php endif; ?>
        </nav>

        <!-- Category badge + date -->
        <div class="pv-top-meta">
            <a href="<?= $cat_href ?>" class="pv-cat-badge"><?= $cat_label ?></a>
            <span class="pv-meta-dot" aria-hidden="true">·</span>
            <time class="pv-meta-date"><?= htmlspecialchars($display_date) ?></time>
        </div>

        <!-- Title -->
        <h1 class="pv-title"><?= htmlspecialchars($post['title']) ?></h1>

        <!-- Social share row -->
        <div class="pv-share-row">
            <span class="pv-share-label">Share:</span>
            <a href="https://www.facebook.com/sharer/sharer.php?u=https%3A%2F%2Fwww.phonesdukan.com%2Fblog%2Fpta-tax-calculator"
               target="_blank" rel="noopener noreferrer" class="pv-share-icon pv-share-fb" aria-label="Share on Facebook">
                <img src="/public/assets/images/facebook_icon.svg" alt="">
            </a>
            <a href="https://x.com/intent/post?url=https%3A%2F%2Fwww.phonesdukan.com%2Fblog%2Fpta-tax-calculator&text=<?= rawurlencode($post['title']) ?>"
               target="_blank" rel="noopener noreferrer" class="pv-share-icon pv-share-x" aria-label="Share on X">
                <img src="/public/assets/images/twitter_icon.svg" alt="">
            </a>
            <a href="https://api.whatsapp.com/send?text=<?= rawurlencode($post['title']) ?>+https%3A%2F%2Fwww.phonesdukan.com%2Fblog%2Fpta-tax-calculator"
               target="_blank" rel="noopener noreferrer" class="pv-share-icon pv-share-wa" aria-label="Share on WhatsApp">
                <img src="/public/assets/images/whatsapp-icon.svg" alt="">
            </a>
            <a href="https://www.linkedin.com/shareArticle?mini=true&url=https%3A%2F%2Fwww.phonesdukan.com%2Fblog%2Fpta-tax-calculator&title=<?= rawurlencode($post['title']) ?>"
               target="_blank" rel="noopener noreferrer" class="pv-share-icon pv-share-li" aria-label="Share on LinkedIn">
                <img src="/public/assets/images/linkedin-icon.svg" alt="">
            </a>
        </div>

    </div>
</div>

<!-- ══════════════════════════════════════════
     FEATURED IMAGE
══════════════════════════════════════════ -->
<?php if (!empty($post['image_url'])): ?>
<div class="pv-feat-wrap">
    <div class="pv-shell">
        <figure class="pv-feat-fig">
            <img src="<?= htmlspecialchars($post['image_url']) ?>"
                 alt="<?= htmlspecialchars($post['alt_text'] ?? $post['title']) ?>"
                 loading="eager">
        </figure>
    </div>
</div>
<?php endif; ?>

<!-- ══════════════════════════════════════════
     POST CONTENT
══════════════════════════════════════════ -->
<div class="pv-content-wrap">
    <div class="pv-shell">
        <div class="pv-body">
            <?= $content ?>
        </div>

        <!-- Bottom share strip -->
        <div class="pv-bottom-share">
            <span class="pv-share-label">Share this post:</span>
            <div class="pv-share-icons">
                <a href="https://www.facebook.com/sharer/sharer.php?u=https%3A%2F%2Fwww.phonesdukan.com%2Fblog%2Fpta-tax-calculator"
                   target="_blank" rel="noopener noreferrer" class="pv-share-icon pv-share-fb" aria-label="Share on Facebook">
                    <img src="/public/assets/images/facebook_icon.svg" alt="">
                </a>
                <a href="https://x.com/intent/post?url=https%3A%2F%2Fwww.phonesdukan.com%2Fblog%2Fpta-tax-calculator&text=<?= rawurlencode($post['title']) ?>"
                   target="_blank" rel="noopener noreferrer" class="pv-share-icon pv-share-x" aria-label="Share on X">
                    <img src="/public/assets/images/twitter_icon.svg" alt="">
                </a>
                <a href="https://api.whatsapp.com/send?text=<?= rawurlencode($post['title']) ?>+https%3A%2F%2Fwww.phonesdukan.com%2Fblog%2Fpta-tax-calculator"
                   target="_blank" rel="noopener noreferrer" class="pv-share-icon pv-share-wa" aria-label="Share on WhatsApp">
                    <img src="/public/assets/images/whatsapp-icon.svg" alt="">
                </a>
                <a href="https://www.linkedin.com/shareArticle?mini=true&url=https%3A%2F%2Fwww.phonesdukan.com%2Fblog%2Fpta-tax-calculator&title=<?= rawurlencode($post['title']) ?>"
                   target="_blank" rel="noopener noreferrer" class="pv-share-icon pv-share-li" aria-label="Share on LinkedIn">
                    <img src="/public/assets/images/linkedin-icon.svg" alt="">
                </a>
            </div>
        </div>

    </div>
</div>

<!-- ══════════════════════════════════════════
     JSON-LD SCHEMA
══════════════════════════════════════════ -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
        {"@type": "ListItem", "position": 1, "name": "Home", "item": "https://www.phonesdukan.com/"},
        <?php if ($post['category_slug'] === 'news'): ?>
            {"@type": "ListItem", "position": 2, "name": "News", "item": "https://www.phonesdukan.com/news/"},
            {"@type": "ListItem", "position": 3, "name": "<?= htmlspecialchars($post['title']) ?>", "item": "https://www.phonesdukan.com/news/<?= htmlspecialchars($post['slug']) ?>/"}
        <?php else: ?>
            {"@type": "ListItem", "position": 2, "name": "Blog", "item": "https://www.phonesdukan.com/blog/"},
            {"@type": "ListItem", "position": 3, "name": "<?= htmlspecialchars($post['category_name']) ?>", "item": "https://www.phonesdukan.com/blog/<?= htmlspecialchars($post['category_slug']) ?>/"},
            {"@type": "ListItem", "position": 4, "name": "<?= htmlspecialchars($post['title']) ?>", "item": "https://www.phonesdukan.com/blog/<?= htmlspecialchars($post['category_slug']) ?>/<?= htmlspecialchars($post['slug']) ?>/"}
        <?php endif; ?>
    ]
}
</script>

<script src="/public/assets/js/pta_calculator.js"></script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
