<div id="sidebar-container" class="sidebar-container">
    <div id="sidebar-overlay" class="sidebar-overlay"></div> <!-- Black overlay with low opacity -->
    <aside id="sidebar" class="sidebar">
        <div id="close-sidebar" class="close-sidebar">
            <a href="<?= url(); ?>">
                <img src="https://www.phonesdukan.com/wp-content/uploads/2024/10/phonesdukan_logo.webp" alt="PhonesDukan" style="width: 170px; height: auto;">
            </a>
            <img src="<?= url('public/assets/images/close-icon.svg'); ?>" alt="Close Icon">
        </div>
        <!-- Login Box Section -->
        <div class="sb-login-box">
            <!-- Login Button -->
            <a href="<?= url('my-account'); ?>" class="sb-login-link">
                <img src="<?= url('public/assets/images/my-account.svg'); ?>" alt="Login" class="sb-icon"> My Account
            </a>

            <!-- Track My Order (Visible when logged out) -->
            <a href="<?= url('track-order'); ?>" class="sb-login-link">
                <img src="<?= url('public/assets/images/track-order-icon.svg'); ?>" alt="Track My Order" class="sb-icon"> Track my Order
            </a>

            <!-- Wholesaler Link -->
            <a href="<?= url('wholesale'); ?>" class="sb-login-link">
                <img src="<?= url('public/assets/images/wholesale.svg'); ?>" alt="Wholesale" class="sb-icon"> Wholesaler
                <span class="new-badge">New</span>
            </a>

            <!-- Blogs Link -->
            <a href="<?= url('blog'); ?>" class="sb-login-link">
                <img src="<?= url('public/assets/images/blog.svg'); ?>" alt="Blog" class="sb-icon"> Blogs
            </a>

            <!-- Logout Link -->
            <a href="<?= url('logout'); ?>" class="sb-login-link">
                <img src="<?= url('public/assets/images/logout-icon.svg'); ?>" alt="Logout" class="sb-icon"> Logout
            </a>
        </div>
        <div class="sb-mobile-category">
            <div class="sb-category-item">
                <div class="category-heading" id="mobiles-category" tabindex="0">
                    <span class="category-icon">
                        <img src="<?= url('public/assets/images/mobiles_icon.svg'); ?>" alt="Mobile Icon" class="category-icon-img">
                    </span>
                    <a href="<?= url('mobiles'); ?>" class="category-link">Mobiles</a>
                    <span class="dropdown-icon" id="mobiles-toggle-icon">&#x25BC;</span> <!-- Down arrow icon -->
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
        </div>
        <!-- Smart Watches -->
        <div class="sb-all-category">
            <div class="sb-category-item">
                <div class="category-heading" id="smart-watches-category" tabindex="0">
                    <span class="category-icon">
                        <img src="<?= url('public/assets/images/smartwatches_icon.svg'); ?>" alt="Smart Watches Icon">
                    </span>
                    <a href="<?= url('smart-watches'); ?>" class="category-link">Smart Watches</a>
                </div>
            </div>
        </div>
        <!-- Power Banks -->
        <div class="sb-all-category">
            <div class="sb-category-item">
                <div class="category-heading" id="power-banks-category" tabindex="0">
                    <span class="category-icon">
                        <img src="<?= url('public/assets/images/power_banks_icon.svg'); ?>" alt="Power Banks Icon">
                    </span>
                    <a href="<?= url('power-banks'); ?>" class="category-link">Power Banks</a>
                </div>
            </div>
        </div>
        <!-- Bluetooth Speakers -->
        <div class="sb-all-category">
            <div class="sb-category-item">
                <div class="category-heading" id="bluetooth-speakers-category" tabindex="0">
                    <span class="category-icon">
                        <img src="<?= url('public/assets/images/speakers_icon.svg'); ?>" alt="Bluetooth Speakers Icon">
                    </span>
                    <a href="<?= url('bluetooth-speakers'); ?>" class="category-link">Bluetooth Speakers</a>
                </div>
            </div>
        </div>
        <!-- Wireless Earbuds -->
        <div class="sb-all-category">
            <div class="sb-category-item">
                <div class="category-heading" id="wireless-earbuds-category" tabindex="0">
                    <span class="category-icon">
                        <img src="<?= url('public/assets/images/wireless-earbuds.svg'); ?>" alt="Wireless Earbuds Icon">
                    </span>
                    <a href="<?= url('wireless-earbuds'); ?>" class="category-link">Wireless Earbuds</a>
                </div>
            </div>
        </div>
        <!-- Mobile Accessories -->
        <div class="sb-all-category">
            <div class="sb-category-item">
                <div class="category-heading" id="mobile-accessories-category" tabindex="0">
                    <span class="category-icon">
                        <img src="<?= url('public/assets/images/accessories_icon.svg'); ?>" alt="Mobile Accessories Icon">
                    </span>
                    <a href="<?= url('mobile-accessories'); ?>" class="category-link">Mobile Accessories</a>
                </div>
            </div>
        </div>
        <!-- Mobile Price List -->
        <div class="price-list-items">
            <a href="<?= url('mobiles-price-list/best-mobiles-under-30000'); ?>" class="price-list-item">
                <button class="best-mobile-button">Best Mobiles Under 30000</button>
            </a>
            <a href="<?= url('mobiles-price-list/best-mobiles-under-40000'); ?>" class="price-list-item">
                <button class="best-mobile-button">Best Mobiles Under 40000</button>
            </a>
            <a href="<?= url('mobiles-price-list/best-mobiles-under-50000'); ?>" class="price-list-item">
                <button class="best-mobile-button">Best Mobiles Under 50000</button>
            </a>
        </div>
    </aside>
</div>