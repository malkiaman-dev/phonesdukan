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
                <div class="sb-category-item sb-category-item--expandable">
                    <div class="category-heading" id="mobiles-category" tabindex="0">
                        <span class="category-icon">
                            <img src="<?= url('public/assets/images/mobiles_icon.svg'); ?>" alt="" class="category-icon-img">
                        </span>
                        <a href="<?= url('mobiles'); ?>" class="category-link">Mobiles</a>
                        <span class="dropdown-icon" id="mobiles-toggle-icon" aria-hidden="true">&#x25BC;</span>
                    </div>
                    <div class="category-content" id="mobiles-content">
                        <ul>
                            <li><a href="<?= url('mobiles/infinix'); ?>" class="subcategory-link">Infinix</a></li>
                            <li><a href="<?= url('mobiles/oppo'); ?>" class="subcategory-link">Oppo</a></li>
                            <li><a href="<?= url('mobiles/realme'); ?>" class="subcategory-link">Realme</a></li>
                            <li><a href="<?= url('mobiles/samsung'); ?>" class="subcategory-link">Samsung</a></li>
                            <li><a href="<?= url('mobiles/tecno'); ?>" class="subcategory-link">Tecno</a></li>
                            <li><a href="<?= url('mobiles/vivo'); ?>" class="subcategory-link">Vivo</a></li>
                            <li><a href="<?= url('mobiles/xiaomi'); ?>" class="subcategory-link">Xiaomi</a></li>
                        </ul>
                    </div>
                </div>

                <div class="sb-category-item">
                    <div class="category-heading" id="smart-watches-category" tabindex="0">
                        <span class="category-icon">
                            <img src="<?= url('public/assets/images/smartwatches_icon.svg'); ?>" alt="">
                        </span>
                        <a href="<?= url('smart-watches'); ?>" class="category-link">Smart Watches</a>
                    </div>
                </div>

                <div class="sb-category-item">
                    <div class="category-heading" id="power-banks-category" tabindex="0">
                        <span class="category-icon">
                            <img src="<?= url('public/assets/images/power_banks_icon.svg'); ?>" alt="">
                        </span>
                        <a href="<?= url('power-banks'); ?>" class="category-link">Power Banks</a>
                    </div>
                </div>

                <div class="sb-category-item">
                    <div class="category-heading" id="bluetooth-speakers-category" tabindex="0">
                        <span class="category-icon">
                            <img src="<?= url('public/assets/images/speakers_icon.svg'); ?>" alt="">
                        </span>
                        <a href="<?= url('bluetooth-speakers'); ?>" class="category-link">Bluetooth Speakers</a>
                    </div>
                </div>

                <div class="sb-category-item">
                    <div class="category-heading" id="wireless-earbuds-category" tabindex="0">
                        <span class="category-icon">
                            <img src="<?= url('public/assets/images/wireless-earbuds.svg'); ?>" alt="">
                        </span>
                        <a href="<?= url('wireless-earbuds'); ?>" class="category-link">Wireless Earbuds</a>
                    </div>
                </div>

                <div class="sb-category-item">
                    <div class="category-heading" id="mobile-accessories-category" tabindex="0">
                        <span class="category-icon">
                            <img src="<?= url('public/assets/images/accessories_icon.svg'); ?>" alt="">
                        </span>
                        <a href="<?= url('mobile-accessories'); ?>" class="category-link">Mobile Accessories</a>
                    </div>
                </div>
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
