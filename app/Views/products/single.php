<?php require_once dirname(__DIR__, 3) . '/includes/header.php'; ?>

<div class="product-container">
    <h1><?php echo htmlspecialchars($product['name']); ?></h1>
    <img src="<?= url('public/uploads/' . htmlspecialchars($product['image'])); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
    <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
    <p><strong>Price:</strong> Rs. <?php echo number_format($product['price']); ?></p>
</div>

<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>
