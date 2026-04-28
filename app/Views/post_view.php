<?php
require_once dirname(__DIR__, 2) . '/app/Controllers/PostController.php';
require_once dirname(__DIR__, 2) . '/includes/blog_header.php';

// Fetch non-primary images for the post
$postModel = new PostModel();
$non_primary_images = $postModel->getNonPrimaryImages($post['id']);

// Function to handle [pta_calculator] shortcode
function pta_calculator_shortcode($data) {
    // Ensure $data has phone_models and usd_to_pkr
    if (!isset($data['phone_models']) || !isset($data['usd_to_pkr'])) {
        return '<p>Error: Calculator data not available.</p>';
    }

    // Generate the calculator HTML
    $calculator_html = '
    <div class="pta-calculator">
        <div class="content-area">
            <h2>PTA Tax Calculator Pakistan ' . date('F Y') . '</h2>
            <p class="exchange-rate-info">Instantly calculate mobile import taxes with our updated PTA Tax Calculator for ' . date('F Y') . ', aligned with Pakistan\'s latest budget and PTA regulations</p>
            <div class="calculator-form">
                <div>
                    <select name="brand" id="brand">
                        <option value="">Select a brand</option>';

    foreach (array_keys($data['phone_models']) as $brand) {
        $calculator_html .= '<option value="' . htmlspecialchars(trim(ucfirst(strtolower($brand)))) . '">' . htmlspecialchars($brand) . '</option>';
    }

    $calculator_html .= '
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div>
                    <select name="model" id="model">
                        <option value="">Select a model</option>
                    </select>
                </div>
                <div id="custom-price-wrapper" class="hidden" style="display: none;">
                    <label for="custom_price">Enter Price (PKR)</label>
                    <input type="number" step="0.01" min="0" name="custom_price" id="custom_price" placeholder="e.g., 200000">
                </div>
                <p class="error-message" style="display: none;"></p>
                <div class="results" style="display: none;">
                    <p class="passport-tax"><strong>Passport Registration:</strong> <span></span></p>
                    <p class="cnic-tax"><strong>CNIC Registration:</strong> <span></span></p>
                </div>
            </div>
        </div>
    </div>';

    // Pass phone_models and usd_to_pkr to JavaScript
    $calculator_html .= '
    <script>
        const phoneModels = ' . json_encode($data['phone_models']) . ';
        const usdToPkr = ' . json_encode($data['usd_to_pkr']) . ';
    </script>';

    return $calculator_html;
}

// Function to handle [cnic_check] shortcode
function cnic_check_shortcode() {
    $cnic_html = '
    <div class="cnic-check">
        <div class="content-area">
            <h2>Check Ehsaas Program Eligibility with CNIC</h2>
            <p>Enter your 13-digit CNIC number below to check your eligibility for the Ehsaas Program via SMS to 8171.</p>
            <div class="cnic-form">
                <label for="cnic_input">CNIC Number (without dashes)</label>
                <input type="text" name="cnic_input" id="cnic_input" placeholder="e.g., 1234512345671" maxlength="13" pattern="[0-9]{13}" required>
                <button type="button" onclick="sendCnicSMS()">Submit</button>
                <p class="error-message" style="display: none; color: red;"></p>
            </div>
            <p><strong>Note:</strong> Use the SIM registered with your CNIC. Standard SMS charges apply (Rs. 1–2).</p>
        </div>
        <script>
            function sendCnicSMS() {
                const cnicInput = document.getElementById("cnic_input").value;
                const errorMessage = document.querySelector(".cnic-form .error-message");
                
                // Validate CNIC (13 digits, numbers only)
                const cnicPattern = /^[0-9]{13}$/;
                if (!cnicPattern.test(cnicInput)) {
                    errorMessage.textContent = "Please enter a valid 13-digit CNIC number without dashes.";
                    errorMessage.style.display = "block";
                    return;
                }
                
                // Construct SMS URI
                const smsUri = `sms:8171?body=${encodeURIComponent(cnicInput)}`;
                
                try {
                    // Attempt to open SMS app
                    window.location.href = smsUri;
                    errorMessage.style.display = "none";
                } catch (e) {
                    errorMessage.textContent = "Unable to open SMS app. Please send your CNIC to 8171 manually.";
                    errorMessage.style.display = "block";
                }
            }
        </script>
    </div>';

    return $cnic_html;
}

// Process shortcodes in content
$content = $post['content'];

// Process [image id=X] shortcodes
if (!empty($non_primary_images)) {
    foreach ($non_primary_images as $image) {
        $shortcode = "[image id={$image['id']}]";
        $img_tag = '<div class="gallery-post-wrapper">';
        $img_tag .= '<img class="gallery-image" src="' . htmlspecialchars($image['image_url']) . '" alt="' . htmlspecialchars($image['alt_text'] ?? $post['title']) . '">';
        if (!empty($image['caption'])) {
        }
        $img_tag .= '</div>';
        $content = str_replace($shortcode, $img_tag, $content);
    }
}

// Process [pta_calculator] shortcode
$content = str_replace('[pta_calculator]', pta_calculator_shortcode($data), $content);

// Process [cnic_check] shortcode
$content = str_replace('[cnic_check]', cnic_check_shortcode(), $content);
?>


<body class="bg-gray-100">
<div class="nav-path">
    <span>
        <a href="/">Home</a> > 
        <?php if ($post['category_slug'] === 'news'): ?>
            <a href="/news">News</a> > 
            <a href="/news/<?php echo htmlspecialchars($post['slug']); ?>"><?php echo htmlspecialchars($post['title']); ?></a>
        <?php else: ?>
            <a href="/blog">Blog</a> > 
            <a href="/blog/<?php echo htmlspecialchars($post['category_slug']); ?>"><?php echo htmlspecialchars($post['category_name']); ?></a> > 
            <a href="/blog/<?php echo htmlspecialchars($post['category_slug']); ?>/<?php echo htmlspecialchars($post['slug']); ?>"><?php echo htmlspecialchars($post['title']); ?></a>
        <?php endif; ?>
    </span>
</div>
    <div class="post-page-wrapper">
        <div class="post-content-container">
            <div class="post-content">
                <article>
                    <?php if (!empty($post['image_url'])): ?>
                        <div class="post-img-wrapper">
                            <img src="<?php echo htmlspecialchars($post['image_url']); ?>" alt="<?php echo htmlspecialchars($post['alt_text'] ?? $post['title']); ?>">
                        </div>
                    <?php endif; ?>
                    <h1><?php echo htmlspecialchars($post['title']); ?></h1>
                    <div class="post-meta">
                        <span class="post-date">
                            <?php
                            // Convert timestamps to DateTime objects
                            $published_at = new DateTime($post['published_at']);
                            $updated_at = new DateTime($post['updated_at']);
                            
                            // Calculate the difference in hours
                            $interval = $published_at->diff($updated_at);
                            $hours_diff = ($interval->days * 24) + $interval->h;
                            
                            // If updated_at is more than 24 hours after published_at, show "Updated on"
                            if ($hours_diff > 24) {
                                echo 'Updated on ' . $updated_at->format('F j, Y');
                            } else {
                                echo 'Published on ' . $published_at->format('F j, Y');
                            }
                            ?>
                        </span>
                        <span class="separator"> | </span>
                        <?php if ($post['category_slug'] === 'news'): ?>
                            <a href="/news" class="category-link">News</a>
                        <?php else: ?>
                            <a href="/blog/<?php echo htmlspecialchars($post['category_slug']); ?>" class="category-link"><?php echo htmlspecialchars($post['category_name']); ?></a>
                        <?php endif; ?>
                        <span class="separator"> | </span>
                        <span class="social-share">
                            <!-- Facebook -->
                            <a href="https://www.facebook.com/sharer/sharer.php?u=https%3A%2F%2Fwww.phonesdukan.com%2Fblog%2Fpta-tax-calculator" target="_blank" class="social-icon">
                                <img src="/public/assets/images/facebook_icon.svg" alt="Share on Facebook">
                            </a>
                            <!-- X -->
                            <a href="https://x.com/intent/post?url=https%3A%2F%2Fwww.phonesdukan.com%2Fblog%2Fpta-tax-calculator&amp;text=PTA+Tax+Calculator+July+2025%3A+Easy+Guide+to+Check+%26amp%3B+Pay+Mobile+Tax+in+Pakistan" target="_blank" class="social-icon">
                                <img src="/public/assets/images/twitter_icon.svg" alt="Share on X">
                            </a>
                            <!-- LinkedIn -->
                            <a href="https://www.linkedin.com/shareArticle?mini=true&amp;url=https%3A%2F%2Fwww.phonesdukan.com%2Fblog%2Fpta-tax-calculator&amp;title=PTA+Tax+Calculator+July+2025%3A+Easy+Guide+to+Check+%26amp%3B+Pay+Mobile+Tax+in+Pakistan" target="_blank" class="social-icon">
                                <img src="/public/assets/images/linkedin-icon.svg" alt="Share on LinkedIn">
                            </a>
                            <!-- WhatsApp -->
                            <a href="https://api.whatsapp.com/send?text=PTA+Tax+Calculator+July+2025%3A+Easy+Guide+to+Check+%26amp%3B+Pay+Mobile+Tax+in+Pakistan+https%3A%2F%2Fwww.phonesdukan.com%2Fblog%2Fpta-tax-calculator" target="_blank" class="social-icon">
                                <img src="/public/assets/images/whatsapp-icon.svg" alt="Share on WhatsApp">
                            </a>
                        </span>
                    </div>
                    <div class="post-body">
                        <?php echo $content; // Output processed content with images, calculator, and CNIC form ?>
                    </div>
                </article>
            </div>
            <aside class="post-sidebar">
                <?php require_once dirname(__DIR__, 2) . '/app/Views/layouts/post_sidebar.php'; ?>
            </aside>
        </div>
    </div>

    <script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
        {"@type": "ListItem", "position": 1, "name": "Home", "item": "https://www.phonesdukan.com/"},
        <?php if ($post['category_slug'] === 'news'): ?>
            {"@type": "ListItem", "position": 2, "name": "News", "item": "https://www.phonesdukan.com/news/"},
            {"@type": "ListItem", "position": 3, "name": "<?php echo htmlspecialchars($post['title']); ?>", "item": "https://www.phonesdukan.com/news/<?php echo htmlspecialchars($post['slug']); ?>/"}
        <?php else: ?>
            {"@type": "ListItem", "position": 2, "name": "Blog", "item": "https://www.phonesdukan.com/blog/"},
            {"@type": "ListItem", "position": 3, "name": "<?php echo htmlspecialchars($post['category_name']); ?>", "item": "https://www.phonesdukan.com/blog/<?php echo htmlspecialchars($post['category_slug']); ?>/"},
            {"@type": "ListItem", "position": 4, "name": "<?php echo htmlspecialchars($post['title']); ?>", "item": "https://www.phonesdukan.com/blog/<?php echo htmlspecialchars($post['category_slug']); ?>/<?php echo htmlspecialchars($post['slug']); ?>/"}
        <?php endif; ?>
    ]
}
</script>

    <script src="/public/assets/js/pta_calculator.js"></script>
    <?php require_once dirname(__DIR__, 2) . '/includes/blog_footer.php'; ?>