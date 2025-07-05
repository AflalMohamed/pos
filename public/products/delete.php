<?php
require '../../includes/auth.php';
require '../../includes/db.php';

checkLogin();

$error = '';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Check if product is referenced in sale_items
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sale_items WHERE product_id = ?");
    $stmt->execute([$id]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        // Cannot delete because product is referenced in sales
        $error = "Cannot delete product because it is referenced in sales.";
    } else {
        // Safe to delete
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: list.php');
        exit;
    }
} else {
    header('Location: list.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Delete Product</title>
</head>
<body>
    <?php if ($error): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
        <p><a href="list.php">Back to Product List</a></p>
    <?php endif; ?>
</body>
</html>
