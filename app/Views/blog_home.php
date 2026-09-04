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
                $post_date    = date('M j, Y', strtotime($post['published_at']));
                $post_excerpt_raw = !empty($post['excerpt'])
                              ? $post['excerpt']
                              : ($post['content'] ?? '');
                $post_excerpt_text = trim(preg_replace('/\s+/', ' ', strip_tags($post_excerpt_raw)));
                if (strlen($post_excerpt_text) > 140) {
                    $post_excerpt_text = substr($post_excerpt_text, 0, 137) . '...';
                }
        ?>
            <article class="post-card"
                     data-category="<?= htmlspecialchars($post['category_slug']) ?>">

                <a href="<?= $post_url ?>" class="post-thumb" tabindex="-1" aria-hidden="true">
                    <div class="post-img-wrapper">
                        <img src="<?= htmlspecialchars($post_image) ?>"
                             alt="<?= htmlspecialchars($post['alt_text'] ?? $post['title']) ?>"
                             loading="lazy" decoding="async">
                    </div>
                </a>

                <div class="post-content">
                    <div class="post-meta">
                        <span class="post-meta-item">
                            <svg class="post-meta-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="8" r="4" stroke="#f7d117" stroke-width="2.2"/><path d="M4 20c0-3.866 3.582-7 8-7s8 3.134 8 7" stroke="#f7d117" stroke-width="2.2" stroke-linecap="round"/></svg>
                            <?= htmlspecialchars($catData['category_name']) ?>
                        </span>
                        <span class="post-meta-item">
                            <svg class="post-meta-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="3" y="4" width="18" height="18" rx="2" stroke="#f7d117" stroke-width="2.2"/><path d="M3 10h18M8 2v4M16 2v4" stroke="#f7d117" stroke-width="2.2" stroke-linecap="round"/></svg>
                            <time class="post-date" datetime="<?= htmlspecialchars($post['published_at']) ?>"><?= $post_date ?></time>
                        </span>
                    </div>
                    <h3 class="post-title">
                        <a href="<?= $post_url ?>"><?= htmlspecialchars($post['title']) ?></a>
                    </h3>
                    <p class="post-excerpt"><?= htmlspecialchars($post_excerpt_text !== '' ? $post_excerpt_text : 'Read this post to learn more insights and updates from Phones Dukan.') ?></p>
                    <a class="post-readmore" href="<?= $post_url ?>">READ MORE <span class="post-readmore-arrow">&rarr;</span></a>
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
