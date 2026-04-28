<?php
require_once dirname(__DIR__, 1) . '/app/Models/PostModel.php';

// Fetch categories for footer links
$postModel = new PostModel();
$categories = $postModel->getAllCategories();
?>

<footer class="bg-gray-900 text-white py-10">
    <div class="container mx-auto px-4">
        <!-- Main Footer Content -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <!-- Branding Section -->
            <div class="branding">
                <a href="<?= url('blog/'); ?>" class="logo" rel="home" aria-label="Phones Dukan Blog Home">
                    <img src="<?= url('public/assets/images/phonesdukan_logo.webp'); ?>" alt="Phones Dukan Logo" class="logo-img">
                </a>
                <p class="text-gray-400">Bringing you the newest tech updates, mobile insights, and digital trends in Pakistan.</p>
                <p class="contact-email">
                    Email: <a href="mailto:info@phonesdukan.com" class="hover:text-blue-400" aria-label="Contact Phones Dukan via email">info@phonesdukan.com</a>
                </p>
                <!-- Social Media Icons -->
                <div class="social-icons mt-4 flex space-x-4">
                    <!-- Facebook -->
                    <a href="https://www.facebook.com/phonesdukan" aria-label="Visit our Facebook page" class="footer-social-icon facebook-icon">
                        <img src="https://www.phonesdukan.com/public/assets/images/facebook_icon.svg" alt="Facebook">
                    </a>
                    <!-- YouTube -->
                    <a href="https://www.youtube.com/c/MobileIsland" aria-label="Visit our YouTube channel" class="footer-social-icon youtube-icon">
                        <img src="https://www.phonesdukan.com/public/assets/images/youtube_icon.svg" alt="YouTube">
                    </a>
                    <!-- Instagram -->
                    <a href="https://www.instagram.com/phonesdukan/" aria-label="Visit our Instagram page" class="footer-social-icon instagram-icon">
                        <img src="https://www.phonesdukan.com/public/assets/images/instagram_icon.svg" alt="Instagram">
                    </a>
                    <!-- TikTok -->
                    <a href="https://www.tiktok.com/@phonesdukan" aria-label="Visit our TikTok page" class="footer-social-icon tiktok-icon">
                        <img src="https://www.phonesdukan.com/public/assets/images/tiktok_icon.svg" alt="TikTok">
                    </a>
                    <!-- Twitter -->
                    <a href="https://x.com/phonesdukan/" aria-label="Visit our Twitter page" class="footer-social-icon twitter-icon">
                        <img src="https://www.phonesdukan.com/public/assets/images/twitter_icon.svg" alt="Twitter">
                    </a>
                </div>
            </div>
            <!-- Blog Categories -->
            <div>
                <h4 class="text-lg font-semibold mb-4">Blog Categories</h4>
                <ul class="space-y-2">
                    <?php foreach ($categories as $category): ?>
                        <li>
                            <a href="<?= url('blog/' . htmlspecialchars($category['slug'])); ?>" class="hover:text-blue-400">
                                <?php echo htmlspecialchars($category['category_name']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <!-- Quick Links -->
            <div>
                <h4 class="text-lg font-semibold mb-4">Quick Links</h4>
                <ul class="space-y-2">
                    <li><a href="<?= url('blog/'); ?>" class="hover:text-blue-400">Blog Home</a></li>
                    <li><a href="<?= url('post-sitemap.xml'); ?>" class="hover:text-blue-400">Sitemap</a></li>
                    <li><a href="<?= url('about-us'); ?>" class="hover:text-blue-400">About Us</a></li>
                    <li><a href="<?= url('contact-us'); ?>" class="hover:text-blue-400">Contact</a></li>
                </ul>
            </div>
            <!-- Newsletter Signup -->
            <div>
                <h4 class="text-lg font-semibold mb-4">Stay Updated</h4>
                <p class="text-gray-400 mb-4">Subscribe to our newsletter for the latest blog updates.</p>
                <div class="flex flex-col gap-2">
                    <input type="email" placeholder="Enter your email" class="p-2 rounded text-gray-900" aria-label="Email for newsletter">
                    <button type="button" class="bg-blue-600 hover:bg-blue-700 text-white p-2 rounded">Subscribe</button>
                </div>
            </div>
        </div>
        <!-- Copyright -->
        <div class="mt-8 border-t border-gray-700 pt-4 text-center text-gray-400">
            <p>© <?php echo date('Y'); ?> Phones Dukan. All rights reserved.</p>
        </div>
    </div>
    <script src="<?= getBaseURL(); ?>public/assets/js/faqs.js"></script>
    <script src="<?= getBaseURL(); ?>public/assets/js/frontend/blog_search.js"></script>
    <!-- Organization Schema -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "Phones Dukan",
        "url": "https://www.phonesdukan.com",
        "logo": "http://custom.local/public/assets/images/phonesdukan_logo.webp",
        "sameAs": [
            "https://www.facebook.com/phonesdukan",
            "https://www.youtube.com/c/MobileIsland",
            "https://www.instagram.com/phonesdukan/",
            "https://www.tiktok.com/@phonesdukan",
            "https://x.com/phonesdukan/"
        ]
    }
    </script>
</footer>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-4182308742558451"
     crossorigin="anonymous"></script>
     
     <script type="text/javascript">
    (function(c,l,a,r,i,t,y){
        c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
        t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
        y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
    })(window, document, "clarity", "script", "rbu44uocrv");
</script>
<?php
// Close any open database connections if necessary
unset($postModel);
?>