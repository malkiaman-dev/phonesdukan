<?php
if (!isPhonesDukanApp()) {
    return;
}

$pdWhatsappUrl = 'https://wa.me/923116600031';
$pdWhatsappIcon = url('public/assets/images/whatsapp_icon.svg');
?>
<!-- ── WhatsApp (in-app only) ──────────────────────────────────────────────── -->
<a href="<?= htmlspecialchars($pdWhatsappUrl, ENT_QUOTES, 'UTF-8'); ?>"
   id="pd-whatsapp-toggle"
   class="pd-whatsapp-fab"
   target="_blank"
   rel="noopener noreferrer"
   aria-label="Chat on WhatsApp"
   title="Chat on WhatsApp">
    <img src="<?= htmlspecialchars($pdWhatsappIcon, ENT_QUOTES, 'UTF-8'); ?>" alt="" aria-hidden="true">
</a>
