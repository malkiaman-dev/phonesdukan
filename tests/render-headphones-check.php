<?php
$_SERVER['REQUEST_URI'] = '/phonesdukan/headphones/';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['HTTPS'] = 'off';
$_SERVER['REQUEST_METHOD'] = 'GET';

chdir(dirname(__DIR__));
$_SERVER['SCRIPT_NAME'] = '/phonesdukan/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/../index.php';
ob_start();
require 'index.php';
$html = ob_get_clean();

$checks = [
    'hp-hero-title' => str_contains($html, 'hp-hero-title'),
    'cl-hero-title' => str_contains($html, 'cl-hero-title'),
    'headphones.css' => str_contains($html, 'headphones.css'),
    'category-listing.css' => str_contains($html, 'category-listing.css'),
    'na-img-box' => str_contains($html, 'na-img-box'),
    'Latest Headphones' => str_contains($html, 'Latest Headphones'),
];

foreach ($checks as $label => $ok) {
    echo $label . ': ' . ($ok ? 'YES' : 'NO') . PHP_EOL;
}

echo 'HTML length: ' . strlen($html) . PHP_EOL;
echo substr($html, 0, 800) . PHP_EOL;