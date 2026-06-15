<?php
require_once dirname(__DIR__) . '/app/Models/CatalogModel.php';
$sidebarCatalogModel = new CatalogModel();
$sidebarCategories = $sidebarCatalogModel->getSidebarCategories();
?>
<div id="sidebar-container" class="sidebar-container">
    <div id="sidebar-overlay" class="sidebar-overlay"></div>
    <aside id="sidebar" class="sidebar">
        <div class="sb-header">
            <a href="<?= url(); ?>" class="sb-logo" aria-label="Phones Dukan home">
                <img src="<?= url('public/assets/images/phonesdukan_logo.webp'); ?>" alt="Phones Dukan" decoding="async">
            </a>
            <button type="button" id="close-sidebar" class="sb-close-btn" aria-label="Close menu">
                <img src="<?= url('public/assets/images/close-icon.svg'); ?>" alt="">
            </button>
        </div>

        <div class="sb-scroll">
            <nav class="sb-quick-nav" aria-label="Quick links">
                <a href="<?= url('my-account'); ?>" class="sb-nav-link <?= !empty($userId) ? 'sb-nav-link--user' : '' ?>">
                    <?php if (!empty($userId)): ?>
                        <span class="sb-nav-icon sb-nav-icon--avatar">
                            <?php if (!empty($headerUserPhoto)): ?>
                                <img src="<?= url(htmlspecialchars($headerUserPhoto)) ?>" alt="<?= htmlspecialchars($headerUserInitials ?? '') ?>">
                            <?php else: ?>
                                <span><?= htmlspecialchars($headerUserInitials ?? 'U') ?></span>
                            <?php endif; ?>
                        </span>
                        <span class="sb-nav-text">My Account</span>
                    <?php else: ?>
                        <span class="sb-nav-icon">
                            <img src="<?= url('public/assets/images/my-account.svg'); ?>" alt="" class="sb-icon">
                        </span>
                        <span class="sb-nav-text">My Account</span>
                    <?php endif; ?>
                </a>

                <a href="<?= url('track-order'); ?>" class="sb-nav-link">
                    <span class="sb-nav-icon">
                        <img src="<?= url('public/assets/images/track-order-icon.svg'); ?>" alt="" class="sb-icon">
                    </span>
                    <span class="sb-nav-text">Track my Order</span>
                </a>

                <a href="<?= url('wholesale'); ?>" class="sb-nav-link">
                    <span class="sb-nav-icon">
                        <img src="<?= url('public/assets/images/wholesale.svg'); ?>" alt="" class="sb-icon">
                    </span>
                    <span class="sb-nav-text">Wholesaler</span>
                    <span class="new-badge">New</span>
                </a>

                <a href="<?= url('blog'); ?>" class="sb-nav-link">
                    <span class="sb-nav-icon">
                        <img src="<?= url('public/assets/images/blog.svg'); ?>" alt="" class="sb-icon">
                    </span>
                    <span class="sb-nav-text">Blogs</span>
                </a>
            </nav>

            <p class="sb-section-label">Shop by Category</p>

            <div class="sb-categories">
                <?php foreach ($sidebarCategories as $sidebarCategory): ?>
                    <?php
                    $categoryId = (int) ($sidebarCategory['category_id'] ?? 0);
                    $categorySlug = (string) ($sidebarCategory['slug'] ?? '');
                    $categoryName = (string) ($sidebarCategory['category_name'] ?? '');
                    $categoryIcon = CatalogModel::sidebarCategoryIcon($categorySlug);
                    $categoryChildren = $sidebarCatalogModel->getSidebarCategoryChildren($categoryId, $categorySlug);
                    $isExpandable = $categoryChildren !== [];
                    $itemClass = 'sb-category-item' . ($isExpandable ? ' sb-category-item--expandable' : '');
                    $headingId = htmlspecialchars($categorySlug . '-category', ENT_QUOTES, 'UTF-8');
                    $contentId = htmlspecialchars($categorySlug . '-content', ENT_QUOTES, 'UTF-8');
                    $toggleId = htmlspecialchars($categorySlug . '-toggle-icon', ENT_QUOTES, 'UTF-8');
                    ?>
                    <div class="<?= $itemClass ?>">
                        <div class="category-heading" id="<?= $headingId ?>" tabindex="0">
                            <span class="category-icon">
                                <img src="<?= url($categoryIcon); ?>" alt="" class="category-icon-img">
                            </span>
                            <a href="<?= url($categorySlug); ?>" class="category-link"><?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') ?></a>
                            <?php if ($isExpandable): ?>
                                <span class="dropdown-icon" id="<?= $toggleId ?>" aria-hidden="true">&#x25BC;</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($isExpandable): ?>
                            <div class="category-content" id="<?= $contentId ?>">
                                <ul>
                                    <?php foreach ($categoryChildren as $child): ?>
                                        <li>
                                            <a href="<?= url($child['href']); ?>" class="subcategory-link">
                                                <?= htmlspecialchars($child['name'], ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="sb-price-list">
                <p class="sb-section-label">Best Deals</p>
                <a href="<?= url('mobiles-price-list/best-mobiles-under-30000'); ?>" class="sb-price-link">Best Mobiles Under 30,000</a>
                <a href="<?= url('mobiles-price-list/best-mobiles-under-40000'); ?>" class="sb-price-link">Best Mobiles Under 40,000</a>
                <a href="<?= url('mobiles-price-list/best-mobiles-under-50000'); ?>" class="sb-price-link">Best Mobiles Under 50,000</a>
            </div>
        </div>

        <div class="sb-footer">
            <a href="<?= url('logout'); ?>" class="sb-logout-link">
                <img src="<?= url('public/assets/images/logout-icon.svg'); ?>" alt="" class="sb-icon">
                Logout
            </a>
        </div>
    </aside>
</div>
