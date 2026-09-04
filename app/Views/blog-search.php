<?php
require_once dirname(__DIR__, 2) . '/includes/blog_header.php';
?>

<div class="posts-page-wrapper">

    <h1 style="font-size:22px;font-weight:700;color:#111;margin:0 0 28px;">
        Search Results for: <em><?= htmlspecialchars($search_term) ?></em>
    </h1>

    <?php if (empty($posts)): ?>
        <p class="no-posts">No posts found for your search.</p>
    <?php else: ?>

        <div class="posts-grid-wrapper">
            <?php foreach ($posts as $post):
                $post_url     = '/blog/' . htmlspecialchars($post['category_slug']) . '/' . htmlspecialchars($post['slug']);
                $post_image   = !empty($post['image_url']) ? $post['image_url'] : '/public/assets/images/Phones_dukan_favicon.png';
                $post_date    = date('M j, Y', strtotime($post['published_at']));
                $post_excerpt_raw = !empty($post['excerpt'])
                              ? $post['excerpt']
                              : ($post['content'] ?? '');
                $post_excerpt_text = trim(preg_replace('/\s+/', ' ', strip_tags($post_excerpt_raw)));
                if (strlen($post_excerpt_text) > 140) {
                    $post_excerpt_text = substr($post_excerpt_text, 0, 137) . '...';
                }
            ?>
            <article class="post-card" data-category="<?= htmlspecialchars($post['category_slug']) ?>">

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
                            <?= htmlspecialchars($post['category_name']) ?>
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
            <?php endforeach; ?>
        </div>

        <?php if ($total_pages > 1): ?>
            <nav class="pagination" style="display:flex;justify-content:center;gap:10px;margin-top:40px;">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="/blog-search?paged=<?= $i ?>&s=<?= urlencode($search_term) ?>"
                       style="text-decoration:none;padding:8px 16px;border:1.5px solid <?= $i === $current_page ? '#f7cf04' : '#cfcfcf' ?>;border-radius:6px;background:<?= $i === $current_page ? '#f7cf04' : '#fff' ?>;color:<?= $i === $current_page ? '#111' : '#666' ?>;font-weight:700;font-size:14px;">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </nav>
        <?php endif; ?>

    <?php endif; ?>

</div>

<?php require_once dirname(__DIR__, 2) . '/includes/blog_footer.php'; ?>
