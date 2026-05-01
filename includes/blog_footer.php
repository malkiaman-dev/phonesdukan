<?php
require_once dirname(__DIR__, 1) . '/app/Models/PostModel.php';
$postModel = new PostModel();
$categories = $postModel->getAllCategories();
?>

</main>

<footer id="footer" class="blog-premium-footer">
    <div class="footer-widgets">
        <div class="footer-widget footer-brand">
            <a href="<?= url(); ?>" class="footer-brand-logo" aria-label="Visit Phones Dukan Home">
                <img src="<?= url('public/assets/images/phonesdukan_logo.png'); ?>" alt="Phones Dukan Logo">
            </a>
            <p class="footer-brand-about">PhonesDukan is your trusted store for mobiles, smart watches, earbuds, accessories, power banks, and speakers across Pakistan.</p>
            <div class="footer-social-icons">
                <a href="https://www.facebook.com/phonesdukan" aria-label="Visit our Facebook page" class="footer-social-icon facebook-icon">
                    <img src="<?= url('public/assets/images/facebook_icon.svg'); ?>" alt="Facebook">
                </a>
                <a href="https://www.youtube.com/c/MobileIsland" aria-label="Visit our YouTube channel" class="footer-social-icon youtube-icon">
                    <img src="<?= url('public/assets/images/youtube_icon.svg'); ?>" alt="YouTube">
                </a>
                <a href="https://www.instagram.com/phonesdukan/" aria-label="Visit our Instagram page" class="footer-social-icon instagram-icon">
                    <img src="<?= url('public/assets/images/instagram_icon.svg'); ?>" alt="Instagram">
                </a>
                <a href="https://www.tiktok.com/@phonesdukan" aria-label="Visit our TikTok page" class="footer-social-icon tiktok-icon">
                    <img src="<?= url('public/assets/images/tiktok_icon.svg'); ?>" alt="TikTok">
                </a>
                <a href="https://x.com/phonesdukan/" aria-label="Visit our Twitter page" class="footer-social-icon twitter-icon">
                    <img src="<?= url('public/assets/images/twitter_icon.svg'); ?>" alt="Twitter">
                </a>
            </div>
        </div>

        <div class="footer-widget footer-locations">
            <h4>Contact Info</h4>
            <div class="footer-block">
                <img src="<?= url('public/assets/images/map_icon.svg'); ?>" alt="Location Icon" class="footer-icon">
                <div class="footer-block-content">
                    <a href="https://www.google.com/maps/dir//Al-ghaffar+shoping+mall,+G-11+Markaz+G+11+Markaz+G-11,+Islamabad,+Islamabad+Capital+Territory+44000/@33.6682651,72.9160123,12z/data=!4m8!4m7!1m0!1m5!1m1!1s0x38df957d29581291:0xe598ee9ef0015b3d!2m2!1d72.9984135!2d33.6682924?entry=ttu&g_ep=EgoyMDI0MDkwNC4wIKXMDSoASAFQAw%3D%3D" class="footer-block-caption">
                        Al-Ghaffar Shoping Mall shop 13B, G-11 Markaz Islamabad, Pakistan
                    </a>
                </div>
            </div>
            <div class="footer-block">
                <img src="<?= url('public/assets/images/email_icon.svg'); ?>" alt="Email Icon" class="footer-icon">
                <div class="footer-block-content">
                    <a href="mailto:info@phonesdukan.com" class="footer-block-caption">info@phonesdukan.com</a>
                </div>
            </div>
            <div class="footer-block">
                <img src="<?= url('public/assets/images/phone_icon.svg'); ?>" alt="Phone Icon" class="footer-icon">
                <div class="footer-block-content">
                    <a href="tel:+923116600031" class="footer-block-caption">(+92) 3116600031</a>
                </div>
            </div>
            <div class="footer-block">
                <img src="<?= url('public/assets/images/second_phone.svg'); ?>" alt="Phone Icon" class="footer-icon">
                <div class="footer-block-content">
                    <a href="tel:051-2756587" class="footer-block-caption">051-2756587</a>
                </div>
            </div>
        </div>

        <div class="footer-widget footer-categories">
            <h4>Blog Categories</h4>
            <ul>
                <?php foreach ($categories as $category): ?>
                    <li>
                        <a href="<?= url('blog/' . htmlspecialchars($category['slug'])); ?>">
                            <?php echo htmlspecialchars($category['category_name']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="footer-widget footer-links">
            <h4>Quick Links</h4>
            <ul>
                <li><a href="<?= url(); ?>">Home</a></li>
                <li><a href="<?= url('blog/'); ?>">Blog Home</a></li>
                <li><a href="<?= url('post-sitemap.xml'); ?>">Blog Sitemap</a></li>
                <li><a href="<?= url('about-us'); ?>">About Us</a></li>
                <li><a href="<?= url('contact-us/'); ?>">Contact</a></li>
                <li><a href="<?= url('return-policy/'); ?>">Return Policy</a></li>
            </ul>
        </div>
    </div>
</footer>

<div class="footer-info">
    <div>
        <p>&copy; <?php echo date('Y'); ?> Phones Dukan • All rights reserved</p>
    </div>
    <div class="payment-image">
        <img src="<?= url('public/assets/images/payment.webp'); ?>" alt="Secure Payment">
    </div>
</div>

<script src="<?= getBaseURL(); ?>public/assets/js/faqs.js"></script>
<script src="<?= getBaseURL(); ?>public/assets/js/frontend/blog_search.js"></script>

<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Organization",
    "name": "Phones Dukan",
    "url": "https://www.phonesdukan.com",
    "logo": "https://www.phonesdukan.com/public/assets/images/phonesdukan_logo.webp",
    "sameAs": [
        "https://www.facebook.com/phonesdukan",
        "https://www.youtube.com/c/MobileIsland",
        "https://www.instagram.com/phonesdukan/",
        "https://www.tiktok.com/@phonesdukan",
        "https://x.com/phonesdukan/"
    ]
}
</script>

<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-4182308742558451" crossorigin="anonymous"></script>

<script type="text/javascript">
(function(c,l,a,r,i,t,y){
    c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
    t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
    y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
})(window, document, "clarity", "script", "rbu44uocrv");
</script>

<?php unset($postModel); ?>
</body>
</html>
