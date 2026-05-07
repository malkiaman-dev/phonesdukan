<?php
$metaTitle = "Latest News in Pakistan - Phones Dukan | Tech & Social Welfare Updates";
$metaDescription = "Explore the latest news in Pakistan, including tech updates, mobile launches, gadget reviews, and social welfare programs like Ehsaas, from Phones Dukan.";
$metaKeywords = "latest news Pakistan, tech news Pakistan, mobile news Pakistan, social welfare Pakistan, Ehsaas Program 2025, Phones Dukan";

$metaRobots = "index, follow";
$ogImage = "https://www.phonesdukan.com/public/assets/images/phonesdukan_logo.webp";

require_once dirname(__DIR__, 3) . '/includes/blog_header.php';
?>

<!-- Breadcrumb Navigation -->
<div class="nav-path">
    <span>
        <a href="/blog">Home</a> > 
        <a href="/news">News</a>
    </span>
</div>

<!-- Category Introduction -->
<div class="category-section">
    <div class="category-price-info">
        <h1><span>Latest News in Pakistan</span> - <?php echo date('F Y'); ?></h1>
        <p>Stay informed with Phones Dukan’s latest news updates in Pakistan, covering technology, mobile phones, gadgets, and social welfare initiatives. From smartphone launches to government schemes, we’ve got you covered.</p>
    </div>
</div>

<!-- Post Listing -->
<div class="posts-page-wrapper">
    <div class="posts-grid-container">
        <div class="posts-grid-wrapper">
            <?php if (!empty($posts) && is_array($posts) && isset($posts[0]) && is_array($posts[0])): ?>
                <?php foreach ($posts as $post): ?>
                    <?php
                    $post_url = "/" . htmlspecialchars($post['category_slug']) . "/" . htmlspecialchars($post['slug']);
                    $post_image = !empty($post['image_url']) ? $post['image_url'] : 'default-image.jpg';
                    $excerptText = trim((string) ($post['excerpt'] ?? ''));
                    if ($excerptText === '') {
                        $fallbackSource = trim(strip_tags((string) ($post['content'] ?? '')));
                        if ($fallbackSource !== '') {
                            $excerptText = mb_substr($fallbackSource, 0, 130) . (mb_strlen($fallbackSource) > 130 ? '...' : '');
                        }
                    }
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
                            <p><?php echo htmlspecialchars($excerptText); ?></p>
                        </div>
                        <div class="post-date">
                            <span><?php echo date('F j, Y', strtotime($post['published_at'])); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No posts found.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pagination -->
    <div class="pagination">
        <?php if ($paged > 1): ?>
            <a href="/news?paged=<?php echo $paged - 1; ?>" class="prev">Previous</a>
        <?php endif; ?>
        <?php
        $start = max(1, $paged - 2);
        $end = min($total_pages, $paged + 2);
        for ($i = $start; $i <= $end; $i++): ?>
            <a href="/news?paged=<?php echo $i; ?>" class="<?php echo ($i == $paged) ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
        <?php if ($paged < $total_pages): ?>
            <a href="/news?paged=<?php echo $paged + 1; ?>" class="next">Next</a>
        <?php endif; ?>
    </div>
</div>

<div class="content-container">
<h2>Explore More with Phones Dukan News</h2>

<h3>Tech News Pakistan</h3>
<p>Love tech? Phones Dukan keeps you updated with the latest tech trends in Pakistan. From new gadgets to faster internet services, we share what’s making waves in the tech world. Stay tuned to learn about innovations that can make your life easier and smarter. Visit phonesdukan.com for tips, tricks, and the latest tech updates!</p>

<h3>Mobile News Pakistan</h3>
<p>Want a new phone? Phones Dukan brings you the latest on smartphones in Pakistan. Whether it’s budget-friendly mobiles or high-end devices with cool features like 5G and great cameras, we’ve got the details to help you choose the perfect phone. Check out phonesdukan.com for reviews and exclusive deals!</p>

<h3>Social Welfare Pakistan</h3>
<p>At Phones Dukan, we care about our community. We share updates on social welfare programs in Pakistan that support families, education, and healthcare. From government initiatives to local efforts, our news page keeps you informed about opportunities that can improve your life. Stay connected with Phones Dukan for the latest updates!</p>

<h3>Why Phones Dukan News?</h3>
<p>Phones Dukan is more than just a phone store—it’s your source for reliable news on tech, mobiles, and social welfare in Pakistan. We write in simple English so everyone can understand. Whether you’re a tech enthusiast or just want to know what’s happening, phonesdukan.com has everything you need. Keep exploring our news page to stay in the know!</p>
</div>
<!-- Schema.org for Breadcrumb -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
        {
            "@type": "ListItem",
            "position": 1,
            "name": "Home",
            "item": "https://www.phonesdukan.com/"
        },
        {
            "@type": "ListItem",
            "position": 2,
            "name": "News",
            "item": "https://www.phonesdukan.com/news/"
        }
    ]
}
</script>

<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "WebPage",
    "name": "Latest News in Pakistan - Phones Dukan | Tech & Social Welfare Updates",
    "url": "https://www.phonesdukan.com/news/",
    "description": "Stay updated with the latest news in Pakistan, covering technology, mobile phones, gadgets, social welfare programs like Ehsaas, and more, from Phones Dukan.",
    "keywords": "latest news Pakistan, tech news Pakistan, mobile news Pakistan, social welfare Pakistan, Ehsaas Program 2025, Phones Dukan",
    "publisher": {
        "@type": "Organization",
        "name": "Phones Dukan",
        "logo": {
            "@type": "ImageObject",
            "url": "https://www.phonesdukan.com/public/assets/images/phonesdukan_logo.webp",
            "width": 200,
            "height": 60
        }
    },
    "mainEntity": [
        <?php
        $articles = [];
        if (!empty($posts) && is_array($posts)) {
            foreach ($posts as $index => $post) {
                // Determine article category for keywords
                $isSocialWelfare = strpos(strtolower($post['title'] ?? ''), 'ehsaas') !== false || 
                                  strpos(strtolower($post['category_slug'] ?? ''), 'social') !== false;
                $keywords = $isSocialWelfare 
                    ? 'Ehsaas Program Pakistan, social welfare Pakistan, 8171 CNIC check'
                    : 'tech news Pakistan, mobile phones Pakistan, gadget updates';
                
                $articles[] = json_encode([
                    '@type' => 'NewsArticle',
                    'headline' => htmlspecialchars($post['title'] ?? 'Latest News in Pakistan', ENT_QUOTES, 'UTF-8'),
                    'url' => "https://www.phonesdukan.com/news/" . htmlspecialchars($post['slug'] ?? ''),
                    'datePublished' => date('c', strtotime($post['published_at'] ?? 'now')),
                    'dateModified' => date('c', strtotime($post['updated_at'] ?? $post['published_at'] ?? 'now')),
                    'author' => [
                        '@type' => 'Person',
                        'name' => htmlspecialchars($post['author_name'] ?? 'Phones Dukan Team')
                    ],
                    'publisher' => [
                        '@type' => 'Organization',
                        'name' => 'Phones Dukan',
                        'logo' => [
                            '@type' => 'ImageObject',
                            'url' => 'https://www.phonesdukan.com/public/assets/images/phonesdukan_logo.webp',
                            'width' => 200,
                            'height' => 60
                        ]
                    ],
                    'image' => [
                        '@type' => 'ImageObject',
                        'url' => !empty($post['image_url']) ? htmlspecialchars('https://www.phonesdukan.com' . $post['image_url']) : 'https://www.phonesdukan.com/public/assets/images/default-news.jpg',
                        'width' => 1200,
                        'height' => 800
                    ],
                    'keywords' => !empty($post['keywords']) ? htmlspecialchars($post['keywords']) : $keywords
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
            echo implode(',', $articles);
        }
        ?>
    ]
}
</script>

<?php require_once dirname(__DIR__, 3) . '/includes/blog_footer.php'; ?>