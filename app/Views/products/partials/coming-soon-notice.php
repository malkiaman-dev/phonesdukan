<?php
$comingSoonWaText = 'Hi, I am interested in ' . ($product['product_name'] ?? 'this product') . ' which is coming soon';
if (!empty($expectedComingDateLabel)) {
    $comingSoonWaText .= ' (expected around ' . $expectedComingDateLabel . ')';
}
$comingSoonWaText .= '. Please notify me when it is available.';
$comingSoonWaUrl = 'https://wa.me/923116600031?text=' . rawurlencode($comingSoonWaText);
?>
<div class="coming-soon-notice coming-soon-card coming-soon-card--notice" role="status" aria-live="polite">
    <div class="coming-soon-notice__accent" aria-hidden="true"></div>
    <div class="coming-soon-notice__glow" aria-hidden="true"></div>
    <div class="coming-soon-notice__icon" aria-hidden="true">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <polyline points="12 6 12 12 16 14"/>
        </svg>
    </div>
    <div class="coming-soon-notice__body">
        <span class="coming-soon-notice__eyebrow">Launching Soon</span>
        <strong class="coming-soon-notice__title">Coming Soon</strong>
        <p class="coming-soon-notice__text">
            This product is not available to order yet. In the meantime, you can explore photos, specifications, and reviews below, and contact us for any questions or updates.
        </p>
        <div class="coming-soon-notice__actions">
            <a href="<?= htmlspecialchars($comingSoonWaUrl) ?>" class="coming-soon-notice__btn coming-soon-notice__btn--primary" target="_blank" rel="noopener noreferrer">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                    <path d="M12 0C5.373 0 0 5.373 0 12c0 2.625.846 5.059 2.284 7.034L.789 23.492a.75.75 0 0 0 .917.917l4.458-1.495A11.945 11.945 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/>
                </svg>
                Notify on WhatsApp
            </a>
            <a href="<?= htmlspecialchars(url('coming-soon-products')) ?>" class="coming-soon-notice__btn coming-soon-notice__btn--secondary">
                More Coming Soon
            </a>
        </div>
    </div>
</div>
