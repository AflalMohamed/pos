<?php
require '../../includes/auth.php';
require '../../includes/db.php';

checkLogin();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    exit('Invalid product ID');
}

$id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT image_data, image_type FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product || !$product['image_data']) {
    // Output a placeholder image or 404
    header('Content-Type: image/png');
    readfile('../../assets/no-image.png'); // create or use your placeholder image here
    exit;
}

// Output the image with correct header
header('Content-Type: ' . $product['image_type']);
echo $product['image_data'];
exit;
