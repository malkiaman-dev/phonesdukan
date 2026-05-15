<?php
require_once dirname(__DIR__, 2) . '/app/Controllers/PostController.php';
require_once dirname(__DIR__, 1) . '/Helpers/SeoHelper.php';

/* ── Meta for main header.php ── */
$pageTitle = $metaTitle ?? ($post['title'] ?? 'Blog | Phones Dukan');

/* ── Breadcrumbs for BreadcrumbList schema ── */
if (isset($post) && is_array($post)) {
    $breadcrumbs = SeoHelper::blogBreadcrumbs(
        (string)($post['category_slug'] ?? 'blog'),
        (string)($post['category_name'] ?? ucfirst($post['category_slug'] ?? 'Blog')),
        (string)($post['title']         ?? ''),
        (string)($post['slug']          ?? '')
    );
}

/* ── Article schema — output via $schema so footer.php picks it up ── */
if (isset($post) && is_array($post)) {
    $postImageUrl = !empty($post['image_url'])
        ? 'https://www.phonesdukan.com' . ltrim($post['image_url'], '/')
        : 'https://www.phonesdukan.com/public/assets/images/phonesdukan_logo.webp';

    $schema = SeoHelper::articleSchema([
        'title'         => $post['title']          ?? $pageTitle,
        'description'   => $metaDescription        ?? ($post['excerpt'] ?? ''),
        'url'           => 'https://www.phonesdukan.com/blog/'
                           . ($post['category_slug'] ?? 'blog') . '/'
                           . ($post['slug']          ?? '') . '/',
        'image'         => $postImageUrl,
        'datePublished' => $post['published_at']   ?? date('Y-m-d'),
        'dateModified'  => $post['updated_at']     ?? ($post['published_at'] ?? date('Y-m-d')),
        'authorName'    => 'Phones Dukan',
    ]);
}

require_once dirname(__DIR__, 2) . '/includes/header.php';

/* ── Fetch non-primary gallery images ── */
$postModel          = new PostModel();
$non_primary_images = $postModel->getNonPrimaryImages($post['id']);

/* ── Fetch related posts (same category, excluding current) ── */
$_related_raw   = $postModel->getPaginatedPosts(4, 0, $post['category_slug']);
$related_posts  = array_slice(
    array_filter($_related_raw, function($p) use ($post) { return (int)$p['id'] !== (int)$post['id']; }),
    0, 3
);

/* ──────────────────────────────────────────────────────────
   SHORTCODE: [pta_calculator]
   ────────────────────────────────────────────────────────── */
function pta_calculator_shortcode($data) {
    if (!isset($data['phone_models']) || !isset($data['usd_to_pkr'])) {
        return '<p>Error: Calculator data not available.</p>';
    }
    $html = '
    <div class="pta-calculator">
      <div class="content-area">
        <h2>PTA Tax Calculator Pakistan ' . date('F Y') . '</h2>
        <p class="exchange-rate-info">Instantly calculate mobile import taxes with our updated PTA Tax Calculator for ' . date('F Y') . ', aligned with Pakistan\'s latest budget and PTA regulations.</p>
        <div class="calculator-form">
          <div>
            <select name="brand" id="brand">
              <option value="">Select a brand</option>';
    foreach (array_keys($data['phone_models']) as $brand) {
        $html .= '<option value="' . htmlspecialchars(trim(ucfirst(strtolower($brand)))) . '">' . htmlspecialchars($brand) . '</option>';
    }
    $html .= '
              <option value="Other">Other</option>
            </select>
          </div>
          <div>
            <select name="model" id="model">
              <option value="">Select a model</option>
            </select>
          </div>
          <div id="custom-price-wrapper" class="hidden" style="display:none;">
            <label for="custom_price">Enter Price (PKR)</label>
            <input type="number" step="0.01" min="0" name="custom_price" id="custom_price" placeholder="e.g., 200000">
          </div>
          <p class="error-message" style="display:none;"></p>
          <div class="results" style="display:none;">
            <p class="passport-tax"><strong>Passport Registration:</strong> <span></span></p>
            <p class="cnic-tax"><strong>CNIC Registration:</strong> <span></span></p>
          </div>
        </div>
      </div>
    </div>
    <script>
      const phoneModels = ' . json_encode($data['phone_models']) . ';
      const usdToPkr    = ' . json_encode($data['usd_to_pkr'])    . ';
    </script>';
    return $html;
}

/* ──────────────────────────────────────────────────────────
   SHORTCODE: [cnic_check]
   ────────────────────────────────────────────────────────── */
function cnic_check_shortcode() {
    return '
    <div class="cnic-check">
      <div class="content-area">
        <h2>Check Ehsaas Program Eligibility with CNIC</h2>
        <p>Enter your 13-digit CNIC number below to check eligibility via SMS to 8171.</p>
        <div class="cnic-form">
          <label for="cnic_input">CNIC Number (without dashes)</label>
          <input type="text" name="cnic_input" id="cnic_input" placeholder="e.g., 1234512345671" maxlength="13" pattern="[0-9]{13}" required>
          <button type="button" onclick="sendCnicSMS()">Submit</button>
          <p class="error-message" style="display:none;color:red;"></p>
        </div>
        <p><strong>Note:</strong> Use the SIM registered with your CNIC. Standard SMS charges apply (Rs. 1–2).</p>
      </div>
      <script>
        function sendCnicSMS(){
          var v=document.getElementById("cnic_input").value,e=document.querySelector(".cnic-form .error-message");
          if(!/^[0-9]{13}$/.test(v)){e.textContent="Please enter a valid 13-digit CNIC.";e.style.display="block";return;}
          try{window.location.href="sms:8171?body="+encodeURIComponent(v);e.style.display="none";}
          catch(ex){e.textContent="Unable to open SMS app. Send your CNIC to 8171 manually.";e.style.display="block";}
        }
      </script>
    </div>';
}

/* ── Process shortcodes ── */
$content = $post['content'];

if (!empty($non_primary_images)) {
    foreach ($non_primary_images as $img) {
        $content = str_replace(
            "[image id={$img['id']}]",
            '<figure class="pv-inline-fig"><img src="' . htmlspecialchars($img['image_url']) . '" alt="' . htmlspecialchars($img['alt_text'] ?? $post['title']) . '" loading="lazy"></figure>',
            $content
        );
    }
}
$content = str_replace('[pta_calculator]', pta_calculator_shortcode($data), $content);
$content = str_replace('[cnic_check]',     cnic_check_shortcode(),           $content);

/* ── Date ── */
$pub  = new DateTime($post['published_at']);
$upd  = new DateTime($post['updated_at']);
$diff = (int)(($pub->diff($upd)->days * 24) + $pub->diff($upd)->h);
$display_date = ($diff > 24) ? 'Updated ' . $upd->format('F j, Y') : $pub->format('F j, Y');

/* ── Category ── */
$cat_href  = ($post['category_slug'] === 'news') ? '/news' : '/blog/' . htmlspecialchars($post['category_slug']);
$cat_label = htmlspecialchars($post['category_name'] ?? 'Blog');

/* ── Share URLs ── */
$page_url = 'https://www.phonesdukan.com' . $cat_href . '/' . htmlspecialchars($post['slug']);
$share_fb = 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($page_url);
$share_x  = 'https://x.com/intent/post?url='               . rawurlencode($page_url) . '&text=' . rawurlencode($post['title']);
$share_wa = 'https://api.whatsapp.com/send?text='           . rawurlencode($post['title'] . ' ' . $page_url);
$share_li = 'https://www.linkedin.com/shareArticle?mini=true&url=' . rawurlencode($page_url) . '&title=' . rawurlencode($post['title']);

/* ── Reusable share button helper ── */
function pv_share_btns(string $fb, string $x, string $wa, string $li): string {
    return '
    <a href="' . $fb . '" target="_blank" rel="noopener noreferrer" class="pv-share-btn pv-share-fb" aria-label="Share on Facebook">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
        </svg>
    </a>
    <a href="' . $x  . '" target="_blank" rel="noopener noreferrer" class="pv-share-btn pv-share-x"  aria-label="Share on X">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round">
            <line x1="18" y1="6"  x2="6"  y2="18"/>
            <line x1="6"  y1="6"  x2="18" y2="18"/>
        </svg>
    </a>
    <a href="' . $wa . '" target="_blank" rel="noopener noreferrer" class="pv-share-btn pv-share-wa" aria-label="Share on WhatsApp">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
        </svg>
    </a>
    <a href="' . $li . '" target="_blank" rel="noopener noreferrer" class="pv-share-btn pv-share-li" aria-label="Share on LinkedIn">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/>
            <rect x="2" y="9" width="4" height="12"/>
            <circle cx="4" cy="4" r="2"/>
        </svg>
    </a>';
}
?>
<link rel="stylesheet" href="<?= url('public/assets/css/frontend/post_view.css') ?>">

<!-- ══ STICKY SHARE SIDEBAR (desktop) ══════════════════ -->
<div class="pv-sticky-share" id="pv-sticky-share" aria-label="Share this article">
    <span class="pv-sticky-label">Share</span>
    <?= pv_share_btns($share_fb, $share_x, $share_wa, $share_li) ?>
</div>

<!-- ══ HERO ════════════════════════════════════════════ -->
<div class="pv-hero">
    <div class="pv-hero-inner">

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

        <div class="pv-top-meta">
            <a href="<?= $cat_href ?>" class="pv-cat-badge">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                <?= $cat_label ?>
            </a>
            <span class="pv-meta-dot" aria-hidden="true">·</span>
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#aaa" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <time class="pv-meta-date"><?= htmlspecialchars($display_date) ?></time>
        </div>

        <h1 class="pv-title"><?= htmlspecialchars($post['title']) ?></h1>

        <div class="pv-share-row">
            <span class="pv-share-label">Share</span>
            <?= pv_share_btns($share_fb, $share_x, $share_wa, $share_li) ?>
        </div>

    </div>
</div>

<!-- ══ FEATURED IMAGE ══════════════════════════════════ -->
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

<!-- ══ CONTENT ═════════════════════════════════════════ -->
<div class="pv-content-wrap">
    <div class="pv-shell">

        <!-- Table of Contents (auto-built by JS if 3+ H2s) -->
        <div class="pv-toc" id="pv-toc" style="display:none;" aria-label="Table of contents">
            <p class="pv-toc-title">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                Contents
            </p>
            <ol class="pv-toc-list" id="pv-toc-list"></ol>
        </div>

        <div class="pv-body" id="pv-body">
            <?= $content ?>
        </div>

        <!-- Inline CTA -->
        <div class="pv-cta-block">
            <div class="pv-cta-text">
                <p class="pv-cta-eyebrow">Phones Dukan</p>
                <p class="pv-cta-heading">Looking for the best mobiles or accessories?</p>
                <p class="pv-cta-sub">Explore the latest deals, compare prices, and shop trusted brands.</p>
            </div>
            <a href="<?= url() ?>" class="pv-cta-btn">Explore Deals →</a>
        </div>

        <!-- Bottom share strip -->
        <div class="pv-bottom-share">
            <span class="pv-share-label">Share this article</span>
            <div class="pv-share-btns-row">
                <?= pv_share_btns($share_fb, $share_x, $share_wa, $share_li) ?>
            </div>
        </div>

    </div>
</div>

<!-- ══ RELATED BLOGS ════════════════════════════════════ -->
<?php if (!empty($related_posts)): ?>
<section class="pv-related-section">
    <div class="pv-shell">
        <h2 class="pv-related-title">Related <span>Blogs</span></h2>
        <div class="pv-related-grid">
            <?php foreach ($related_posts as $rp):
                $rp_slug = ($rp['category_slug'] === 'news')
                    ? '/news/' . htmlspecialchars($rp['slug'])
                    : '/blog/' . htmlspecialchars($rp['category_slug']) . '/' . htmlspecialchars($rp['slug']);
                $rp_img  = !empty($rp['image_url']) ? $rp['image_url'] : '/public/assets/images/Phones_dukan_favicon.png';
                $rp_date = date('M j, Y', strtotime($rp['published_at']));
            ?>
                <article class="post-card">
                    <a href="<?= $rp_slug ?>" class="post-thumb" tabindex="-1" aria-hidden="true">
                        <div class="post-img-wrapper">
                            <img src="<?= htmlspecialchars($rp_img) ?>"
                                 alt="<?= htmlspecialchars($rp['alt_text'] ?? $rp['title']) ?>"
                                 loading="lazy">
                        </div>
                        <span class="post-badge"><?= htmlspecialchars($rp['category_name'] ?? '') ?></span>
                    </a>
                    <div class="post-content">
                        <p class="post-meta">
                            <span class="post-meta-icon" aria-hidden="true">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            </span>
                            <time class="post-date"><?= $rp_date ?></time>
                        </p>
                        <h3 class="post-title">
                            <a href="<?= $rp_slug ?>"><?= htmlspecialchars($rp['title']) ?></a>
                        </h3>
                        <p class="post-excerpt"><?= htmlspecialchars($rp['excerpt'] ?? '') ?></p>
                        <a class="post-readmore" href="<?= $rp_slug ?>">READ MORE →</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ══ JSON-LD ══════════════════════════════════════════ -->
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

<!-- ══ PAGE JS ══════════════════════════════════════════ -->
<script>
(function () {
    'use strict';

    /* ── Table of Contents (auto-generate from H2s) ── */
    var body    = document.getElementById('pv-body');
    var toc     = document.getElementById('pv-toc');
    var tocList = document.getElementById('pv-toc-list');

    if (body && toc && tocList) {
        var headings = body.querySelectorAll('h2');
        if (headings.length >= 3) {
            headings.forEach(function (h, i) {
                var id = 'pv-section-' + i;
                h.id = id;
                var li = document.createElement('li');
                var a  = document.createElement('a');
                a.href = '#' + id;
                a.textContent = h.textContent;
                li.appendChild(a);
                tocList.appendChild(li);
            });
            toc.style.display = 'block';
        }
    }

    /* ── Wrap tables for mobile scroll ── */
    if (body) {
        body.querySelectorAll('table').forEach(function (table) {
            if (!table.closest('.pv-table-scroll')) {
                var wrapper = document.createElement('div');
                wrapper.className = 'pv-table-scroll';
                table.parentNode.insertBefore(wrapper, table);
                wrapper.appendChild(table);
            }
        });
    }

    /* ── Sticky share: show only when content is in view ── */
    var sticky = document.getElementById('pv-sticky-share');
    var contentWrap = document.querySelector('.pv-content-wrap');
    if (sticky && contentWrap) {
        var observer = new IntersectionObserver(function (entries) {
            sticky.classList.toggle('pv-sticky-share--visible', entries[0].isIntersecting);
        }, { rootMargin: '0px 0px -100px 0px' });
        observer.observe(contentWrap);
    }
}());
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
