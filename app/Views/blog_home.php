<?php
require_once dirname(__DIR__, 2) . '/includes/blog_header.php';
?>

<section class="blog-hero">
    <div class="blog-shell">
        <p class="blog-hero-label">PHONES DUKAN BLOG</p>
        <h1 class="blog-hero-title">Latest <span>Mobile</span> News, Tips &amp; <span>Guides</span></h1>
        <p class="blog-hero-subtitle">Stay updated with mobile prices, PTA tax updates, telecom packages, smartphone reviews, and practical buying guides across Pakistan.</p>
    </div>
</section>

<div class="posts-page-wrapper">
    <?php foreach ($category_posts as $category_slug => $category_data): ?>
        <?php if (!empty($category_data['posts']) && is_array($category_data['posts'])): ?>
            <section class="category-section" id="<?= htmlspecialchars($category_slug) ?>">
                <div class="category-header">
                    <h2><span><?= htmlspecialchars($category_data['category_name']) ?></span></h2>
                    <div class="view-all">
                        <a class="view-all-btn" href="/blog/<?php echo htmlspecialchars($category_slug); ?>">View All</a>
                    </div>
                </div>

                <div class="posts-grid-container">
                    <div class="posts-grid-wrapper">
                        <?php foreach ($category_data['posts'] as $post): ?>
                            <?php
                            $post_url = "/blog/" . htmlspecialchars($post['category_slug']) . "/" . htmlspecialchars($post['slug']);
                            $post_image = !empty($post['image_url']) ? $post['image_url'] : 'default-image.jpg';
                            ?>
                            <article class="post-card">
                                <a href="<?php echo $post_url; ?>" class="post-thumb">
                                    <div class="post-img-wrapper">
                                        <div class="post-img">
                                            <img
                                                src="<?php echo $post_image; ?>"
                                                alt="<?php echo htmlspecialchars($post['alt_text'] ?? $post['title']); ?>"
                                                loading="lazy"
                                                decoding="async"
                                            >
                                        </div>
                                    </div>
                                    <span class="post-badge"><?php echo htmlspecialchars($category_data['category_name']); ?></span>
                                </a>

                                <div class="post-content">
                                    <p class="post-date"><?php echo date('F j, Y', strtotime($post['published_at'])); ?></p>
                                    <h3 class="post-title">
                                        <a href="<?php echo $post_url; ?>"><?php echo htmlspecialchars($post['title']); ?></a>
                                    </h3>
                                    <div class="post-excerpt">
                                        <p><?php echo htmlspecialchars($post['excerpt']); ?></p>
                                    </div>
                                    <a class="post-readmore" href="<?php echo $post_url; ?>">Read More</a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php if (empty($category_posts) || !array_filter($category_posts, function ($data) {
        return !empty($data['posts']);
    })): ?>
        <p class="no-posts">No posts found.</p>
    <?php endif; ?>
</div>

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

<?php require_once dirname(__DIR__, 2) . '/includes/blog_footer.php'; ?>
