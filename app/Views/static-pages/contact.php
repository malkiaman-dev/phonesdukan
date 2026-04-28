<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/app/Controllers/ContactController.php';
$controller = new ContactController();
$controller->handleFormSubmission();
?>

<!-- HTML structure as before -->
<?php if (!empty($controller->error)) echo "<div class='alert alert-danger'>" . htmlspecialchars($controller->error) . "</div>"; ?>
<?php if (!empty($controller->success)) echo "<div class='alert alert-success'>" . htmlspecialchars($controller->success) . "</div>"; ?>

<section class="contact-page">
    <div class="contact-details">
        <div class="contact-box">
            <i class="fas fa-envelope contact-icon"></i>
            <h3>Email Us</h3>
            <p><a href="mailto:info@electro-engineers.com.pk">info@phonesdukan.com</a></p>
        </div>
        <div class="contact-box">
            <i class="fas fa-phone contact-icon"></i>
            <h3>Call Us</h3>
            <p><a href="tel:+923170003777">(+92) 3170003777</a></p>
        </div>
        <div class="contact-box">
            <i class="fas fa-map-marker-alt contact-icon"></i>
            <h3>Visit Us</h3>
            <p><a href="https://www.google.com/maps/dir//Al-ghaffar+shoping+mall,+G-11+Markaz+G+11+Markaz+G-11,+Islamabad,+Islamabad+Capital+Territory+44000/@33.6682651,72.9160123,12z/data=!4m8!4m7!1m0!1m5!1m1!1s0x38df957d29581291:0xe598ee9ef0015b3d!2m2!1d72.9984135!2d33.6682924?entry=ttu&g_ep=EgoyMDI0MDkwNC4wIKXMDSoASAFQAw%3D%3D">Al-Ghaffar Shoping Mall G-11 Markaz, Islamabad</a></p>
        </div>
    </div>
    <div class="contact-content">
        <div class="contact-map">
        <iframe 
            src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d13282.281486461163!2d72.9984135!3d33.6682924!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x38df957d29581291%3A0xe598ee9ef0015b3d!2sMobile%20Island!5e0!3m2!1sen!2s!4v1726505074794!5m2!1sen!2s" 
            allowfullscreen="" 
            loading="lazy">
        </iframe>
        </div>
        <div class="contact-form-wrapper">
    <h3>Contact Form</h3>

    <!-- Show error or success message -->
    <?php
    if (isset($_SESSION['success'])) {
        echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
        unset($_SESSION['success']);
    }
    if (isset($_SESSION['error'])) {
        echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
        unset($_SESSION['error']);
    }
    ?>

    <form method="POST" action="" class="contact-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

        <input type="text" name="name" placeholder="Your Name" required>
        <input type="email" name="email" placeholder="Your Email" required>
        <input type="text" name="subject" placeholder="Subject" required>
        <textarea name="message" placeholder="Your Message" required></textarea>
        <button type="submit" name="submit">Send Message</button>
        <div id="form-status-message"></div>
    </form>
    <script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form');
    const statusDiv = document.getElementById('form-status-message');

    form.addEventListener('submit', function () {
        statusDiv.textContent = "Sending your message. Please wait...";
    });
});
</script>
</div>

    </div>
    </div>
</section>
<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>