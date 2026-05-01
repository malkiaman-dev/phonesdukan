<?php
// Map controller variable to what header.php expects
$pageTitle = $metaTitle ?? 'Blog | Phones Dukan';

require_once dirname(__DIR__, 2) . '/includes/header.php';
?>

<!-- ── HERO ────────────────────────────────────────────────── -->
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

<!-- ── FILTER TABS ────────────────────────────────────────── -->
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

<!-- ── UNIFIED POSTS GRID ─────────────────────────────────── -->
<div class="posts-page-wrapper">

    <div class="posts-grid-wrapper" id="blog-grid">
        <?php
        $hasAnyPost = false;
        foreach ($category_posts as $catSlug => $catData):
            if (empty($catData['posts']) || !is_array($catData['posts'])) continue;
            foreach ($catData['posts'] as $post):
                $hasAnyPost   = true;
                $post_url     = '/blog/' . htmlspecialchars($post['category_slug'])
                              . '/' . htmlspecialchars($post['slug']);
                $post_image   = !empty($post['image_url'])
                              ? $post['image_url']
                              : '/public/assets/images/Phones_dukan_favicon.png';
                $post_date    = date('M j, Y', strtotime($post['published_at']));
        ?>
            <article class="post-card"
                     data-category="<?= htmlspecialchars($post['category_slug']) ?>">

                <!-- Image -->
                <a href="<?= $post_url ?>" class="post-thumb" tabindex="-1" aria-hidden="true">
                    <div class="post-img-wrapper">
                        <img src="<?= htmlspecialchars($post_image) ?>"
                             alt="<?= htmlspecialchars($post['alt_text'] ?? $post['title']) ?>"
                             loading="lazy" decoding="async">
                    </div>
                    <span class="post-badge"><?= htmlspecialchars($catData['category_name']) ?></span>
                </a>

                <!-- Content -->
                <div class="post-content">
                    <p class="post-meta">
                        <span class="post-cat"><?= htmlspecialchars($catData['category_name']) ?></span>
                        <span class="post-meta-sep">·</span>
                        <span class="post-date"><?= $post_date ?></span>
                    </p>
                    <h3 class="post-title">
                        <a href="<?= $post_url ?>"><?= htmlspecialchars($post['title']) ?></a>
                    </h3>
                    <p class="post-excerpt"><?= htmlspecialchars($post['excerpt']) ?></p>
                    <a class="post-readmore" href="<?= $post_url ?>">
                        Read More <span aria-hidden="true">→</span>
                    </a>
                </div>
            </article>
        <?php
            endforeach;
        endforeach;
        ?>
    </div><!-- /posts-grid-wrapper -->

    <!-- Empty state (hidden by JS when cards exist) -->
    <p class="blog-no-results" id="blog-no-results" style="display:none;">
        No posts found in this category.
    </p>

    <?php if (!$hasAnyPost): ?>
        <p class="no-posts">No posts available yet.</p>
    <?php endif; ?>

    <!-- View All CTA -->
    <?php if ($hasAnyPost): ?>
    <div class="blog-cta-row">
        <a class="blog-view-all-btn" id="blog-view-all-btn" href="/blog">
            View All Posts
        </a>
    </div>
    <?php endif; ?>

</div><!-- /posts-page-wrapper -->

<!-- ── JSON-LD ──────────────────────────────────────────────── -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
        {"@type": "ListItem", "position": 1, "name": "Home", "item": "https://www.phonesdukan.com/"},
        {"@type": "ListItem", "position": 2, "name": "Blog", "item": "https://www.phonesdukan.com/blog/"}
    ]
}
</script>

<!-- ── FILTER LOGIC ─────────────────────────────────────────── -->
<script>
(function () {
    'use strict';

    const tabs       = document.querySelectorAll('.blog-tab');
    const cards      = document.querySelectorAll('.post-card');
    const noResults  = document.getElementById('blog-no-results');
    const viewAllBtn = document.getElementById('blog-view-all-btn');

    function applyFilter(filter, labelText) {
        let visible = 0;

        cards.forEach(function (card) {
            const match = filter === 'all' || card.dataset.category === filter;
            card.style.display = match ? '' : 'none';
            if (match) visible++;
        });

        // Empty-state message
        if (noResults) noResults.style.display = visible === 0 ? 'block' : 'none';

        // Update "View All" button
        if (viewAllBtn) {
            if (filter === 'all') {
                viewAllBtn.href        = '/blog';
                viewAllBtn.textContent = 'View All Posts';
            } else {
                viewAllBtn.href        = '/blog/' + filter;
                viewAllBtn.textContent = 'View All in ' + labelText;
            }
        }
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) { t.classList.remove('active'); });
            tab.classList.add('active');
            applyFilter(tab.dataset.filter, tab.textContent.trim());
        });
    });
}());
</script>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
