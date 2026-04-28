<?php
require_once dirname(__DIR__, 3) . '/app/Models/PostModel.php';
$postModel = new PostModel();

// Fetch recent posts (limit 5)
$recent_posts = $postModel->getPaginatedPosts(5, 0, null);

// Fetch all categories
$categories = $postModel->getAllCategories();
?>

<aside class="sidebar-content">
    <div class="sidebar-section">
    <h3>Recent Posts</h3>
    <ul>
        <?php if (!empty($recent_posts)): ?>
            <?php foreach ($recent_posts as $recent_post): ?>
                <li class="recent-post-item">
                    <?php if (!empty($recent_post['image_url'])): ?>
                        <div class="post-image-section">
                            <img src="<?php echo htmlspecialchars($recent_post['image_url']); ?>"
                                 alt="<?php echo htmlspecialchars($recent_post['alt_text'] ?? 'Post thumbnail'); ?>">
                        </div>
                    <?php endif; ?>
                    <div class="recent-post-title">
                        <a href="/blog/<?php echo htmlspecialchars($recent_post['category_slug'] ?? 'uncategorized'); ?>/<?php echo htmlspecialchars($recent_post['slug']); ?>">
                            <?php echo htmlspecialchars($recent_post['title'] ?? 'No Title'); ?>
                        </a>
                    </div>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <li>No recent posts available.</li>
        <?php endif; ?>
    </ul>
</div>
    <div class="sidebar-section">
        <h3>Categories</h3>
        <ul>
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $category): ?>
                    <li>
                        <a href="/blog/<?php echo htmlspecialchars($category['slug']); ?>">
                            <?php echo htmlspecialchars($category['category_name']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li>No categories available.</li>
            <?php endif; ?>
        </ul>
    </div>
</aside>