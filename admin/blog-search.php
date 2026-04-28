<?php
require_once dirname(__DIR__, 1) . '/includes/blog_header.php';
?>

<main class="content">
    <div class="container">
        <h1>Search Results for: <?= htmlspecialchars($search_term) ?></h1>
        <?php if (empty($posts)): ?>
            <p>No posts found for your search.</p>
        <?php else: ?>
            <div class="posts-grid">
                <?php foreach ($posts as $post): ?>
                    <a href="<?= getBaseURL() . htmlspecialchars($post['category_slug']) . '/' . htmlspecialchars($post['slug']) ?>" class="post-link">
                        <article class="post">
                            <?php if ($post['image_url']): ?>
                                <img src="<?= htmlspecialchars($post['image_url']) ?>" alt="<?= htmlspecialchars($post['alt_text'] ?? $post['title']) ?>">
                            <?php endif; ?>
                            <h2><?= htmlspecialchars($post['title']) ?></h2>
                            <p><?= htmlspecialchars($post['excerpt']) ?></p>
                            <p><small>Published on: <?= date('F j, Y', strtotime($post['published_at'])) ?></small></p>
                        </article>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($total_pages > 1): ?>
            <nav class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="<?= getBaseURL(); ?>blog-search?paged=<?= $i ?>&s=<?= urlencode($search_term) ?>" class="<?= $i === $current_page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </nav>
        <?php endif; ?>
    </div>
</main>

<?php require_once dirname(__DIR__, 1) . '/includes/footer.php'; ?>