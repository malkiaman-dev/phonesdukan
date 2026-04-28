<?php
require_once dirname(__DIR__, 2) . '/includes/blog_header.php';
?>
<div class="nav-path">
    <span><a href="/">Home</a> > <a href="/blog">Blog</a></span>
</div>

<div class="posts-page-wrapper">
    <!-- Category Sections -->
    <?php foreach ($category_posts as $category_slug => $category_data): ?>
        <?php if (!empty($category_data['posts']) && is_array($category_data['posts'])): ?>
            <section class="category-section">
                <div class="category-header">
                    <h2><?php echo htmlspecialchars($category_data['category_name']); ?></h2>
                    <div class="view-all">
                        <a href="/blog/<?php echo htmlspecialchars($category_slug); ?>">View All</a>
                    </div>
                </div>
                <div class="posts-grid-container">
                    <div class="posts-grid-wrapper">
                        <?php foreach ($category_data['posts'] as $post): ?>
                            <?php
                            $post_url = "/blog/" . htmlspecialchars($post['category_slug']) . "/" . htmlspecialchars($post['slug']);
                            $post_image = !empty($post['image_url']) ? $post['image_url'] : 'default-image.jpg';
                            ?>
                            <div class="post-card">
                                <a href="<?php echo $post_url; ?>">
                                    <div class="post-img-wrapper">
                                        <div class="post-img">
                                            <img src="<?php echo $post_image; ?>" alt="<?php echo htmlspecialchars($post['alt_text'] ?? $post['title']); ?>">
                                        </div>
                                    </div>
                                </a>
                                <h3 class="post-title">
                                    <a href="<?php echo $post_url; ?>">
                                        <?php echo htmlspecialchars($post['title']); ?>
                                    </a>
                                </h3>
                                <div class="post-excerpt">
                                    <p><?php echo htmlspecialchars($post['excerpt']); ?></p>
                                </div>
                                <div class="post-date">
                                    <span><?php echo date('F j, Y', strtotime($post['published_at'])); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php if (empty($category_posts) || !array_filter($category_posts, function($data) { return !empty($data['posts']); })): ?>
        <p>No posts found.</p>
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