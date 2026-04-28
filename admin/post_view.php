<?php
require_once dirname(__DIR__, 1) . '/app/Controllers/PostController.php';
require_once dirname(__DIR__, 1) . '/includes/blog_header.php';

// Fetch non-primary images for the post
$postModel = new PostModel();
$non_primary_images = $postModel->getNonPrimaryImages($post['id']);

// Process shortcodes in content (e.g., [image id=1])
$content = $post['content'];
if (!empty($non_primary_images)) {
    foreach ($non_primary_images as $image) {
        $shortcode = "[image id={$image['id']}]";
        $img_tag = '<div class="post-img-wrapper">';
        $img_tag .= '<img src="' . htmlspecialchars($image['image_url']) . '" alt="' . htmlspecialchars($image['alt_text'] ?? $post['title']) . '">';
        if (!empty($image['caption'])) {
            $img_tag .= '<p class="post-caption">' . htmlspecialchars($image['caption']) . '</p>';
        }
        $img_tag .= '</div>';
        $content = str_replace($shortcode, $img_tag, $content);
    }
}
?>
<div class="nav-path">
                    <span>
                        <a href="/blog">Home</a> > 
                        <a href="/<?php echo htmlspecialchars($post['category_slug']); ?>">
                            <?php echo htmlspecialchars($post['category_name']); ?>
                        </a> > 
                        <!-- Add subcategory here if applicable, e.g., via post_categories parent_id -->
                        <a href="/<?php echo htmlspecialchars($post['category_slug']); ?>/<?php echo htmlspecialchars($post['slug']); ?>">
                            <?php echo htmlspecialchars($post['title']); ?>
                        </a>
                    </span>
                </div>
<div class="post-page-wrapper">
    <div class="post-content-container">
        <div class="post-content">
            <article>
                <?php if (!empty($post['image_url'])): ?>
                    <div class="post-img-wrapper">
                        <img src="<?php echo htmlspecialchars($post['image_url']); ?>" alt="<?php echo htmlspecialchars($post['alt_text'] ?? $post['title']); ?>">
                        <?php if (!empty($post['caption'])): ?>
                            <p class="post-caption"><?php echo htmlspecialchars($post['caption']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <h1><?php echo htmlspecialchars($post['title']); ?></h1>
                <div class="post-meta">
                    <span>Published on <?php echo date('F j, Y', strtotime($post['published_at'])); ?></span>
                    | <a href="/<?php echo htmlspecialchars($post['category_slug']); ?>">
                        <?php echo htmlspecialchars($post['category_name']); ?>
                    </a>
                </div>
                <div class="post-body">
                    <?php echo $content; // Output processed content with images ?>
                </div>
            </article>
        </div>
        <aside class="post-sidebar">
            <?php require_once dirname(__DIR__, 1) . '/app/Views/layouts/post_sidebar.php'; ?>
        </aside>
    </div>
</div>

<?php
// Note: The following SEO meta tags should be in /includes/header.php, not here
/*
<head>
    <title><?php echo htmlspecialchars($post['meta_title'] ?? $post['title']); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($post['meta_description'] ?? $post['excerpt']); ?>">
</head>
*/
?>
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
        {
            "@type": "ListItem",
            "position": 1,
            "name": "Home",
            "item": "https://www.phonesdukan.com/blog/"
        },
        {
            "@type": "ListItem",
            "position": 2,
            "name": "<?php echo htmlspecialchars($post['category_name']); ?>",
            "item": "https://www.phonesdukan.com/<?php echo htmlspecialchars($post['category_slug']); ?>/"
        },
        {
            "@type": "ListItem",
            "position": 3,
            "name": "<?php echo htmlspecialchars($post['title']); ?>",
            "item": "https://www.phonesdukan.com/<?php echo htmlspecialchars($post['category_slug']); ?>/<?php echo htmlspecialchars($post['slug']); ?>/"
        }
    ]
}
</script>
<?php require_once dirname(__DIR__, 1) . '/includes/footer.php'; ?>