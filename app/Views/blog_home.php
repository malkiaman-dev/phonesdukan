<?php
$pageTitle = $metaTitle ?? 'Blog | Phones Dukan';
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>

<!-- ── HERO ───────────────────────────────────────────── -->
<section class="blog-hero">
    <div class="blog-shell">
        <p class="blog-hero-label">PHONES DUKAN BLOG</p>
        <h1 class="blog-hero-title">
            Latest <span>Mobile</span> News,<br>Tips &amp; <span>Guides</span>
        </h1>
        <p class="blog-hero-subtitle">
            Stay updated with mobile prices, PTA tax updates, telecom packages,
            smartphone reviews &amp; buying guides across Pakistan.
        </p>
    </div>
</section>

<!-- ── FILTER TABS ────────────────────────────────────── -->
<div class="blog-filter-bar">
    <div class="blog-filter-inner">
        <button class="blog-tab active" data-filter="all">All</button>
        <?php foreach ($categories as $cat): ?>
            <button class="blog-tab" data-filter="<?= htmlspecialchars($cat['slug']) ?>">
                <?= htmlspecialchars($cat['category_name']) ?>
            </button>
        <?php endforeach; ?>
    </div>
</div>

<!-- ── ALL POSTS GRID ────────────────────────────────── -->
<div class="posts-page-wrapper">

    <div class="posts-grid-wrapper" id="blog-grid">
        <?php
        $hasAnyPost = false;
        foreach ($category_posts as $catSlug => $catData):
            if (empty($catData['posts']) || !is_array($catData['posts'])) continue;
            foreach ($catData['posts'] as $post):
                $hasAnyPost  = true;
                $post_url    = '/blog/' . htmlspecialchars($post['category_slug'])
                             . '/' . htmlspecialchars($post['slug']);
                $post_image  = !empty($post['image_url'])
                             ? $post['image_url']
                             : '/public/assets/images/Phones_dukan_favicon.png';
                $post_date   = date('M j, Y', strtotime($post['published_at']));
        ?>
            <article class="post-card"
                     data-category="<?= htmlspecialchars($post['category_slug']) ?>">

                <a href="<?= $post_url ?>" class="post-thumb" tabindex="-1" aria-hidden="true">
                    <div class="post-img-wrapper">
                        <img src="<?= htmlspecialchars($post_image) ?>"
                             alt="<?= htmlspecialchars($post['alt_text'] ?? $post['title']) ?>"
                             loading="lazy" decoding="async">
                    </div>
                    <span class="post-badge"><?= htmlspecialchars($catData['category_name']) ?></span>
                </a>

                <div class="post-content">
                    <p class="post-meta">
                        <span class="post-meta-icon" aria-hidden="true">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </span>
                        <span class="post-cat"><?= htmlspecialchars($catData['category_name']) ?></span>
                        <span class="post-meta-sep" aria-hidden="true">·</span>
                        <span class="post-meta-icon" aria-hidden="true">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        </span>
                        <time class="post-date" datetime="<?= htmlspecialchars($post['published_at']) ?>"><?= $post_date ?></time>
                    </p>
                    <h3 class="post-title">
                        <a href="<?= $post_url ?>"><?= htmlspecialchars($post['title']) ?></a>
                    </h3>
                    <p class="post-excerpt"><?= htmlspecialchars($post['excerpt']) ?></p>
                    <a class="post-readmore" href="<?= $post_url ?>">READ MORE →</a>
                </div>
            </article>
        <?php
            endforeach;
        endforeach;
        ?>
    </div>

    <!-- Empty state shown by JS when a filter has zero results -->
    <p class="blog-no-results" id="blog-no-results" style="display:none;">
        No posts found in this category.
    </p>

    <?php if (!$hasAnyPost): ?>
        <p class="no-posts">No posts available yet.</p>
    <?php endif; ?>

</div>

<!-- ── JSON-LD ─────────────────────────────────────────── -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
        {"@type": "ListItem", "position": 1, "name": "Home",  "item": "https://www.phonesdukan.com/"},
        {"@type": "ListItem", "position": 2, "name": "Blog",  "item": "https://www.phonesdukan.com/blog/"}
    ]
}
</script>

<!-- ── FILTER JS ───────────────────────────────────────── -->
<script>
(function () {
    'use strict';

    const tabs      = document.querySelectorAll('.blog-tab');
    const cards     = document.querySelectorAll('#blog-grid .post-card');
    const noResults = document.getElementById('blog-no-results');

    function applyFilter(filter) {
        let visible = 0;
        cards.forEach(function (card) {
            const match = filter === 'all' || card.dataset.category === filter;
            card.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        if (noResults) noResults.style.display = visible === 0 ? 'block' : 'none';
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) { t.classList.remove('active'); });
            tab.classList.add('active');
            applyFilter(tab.dataset.filter);
        });
    });
}());
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
