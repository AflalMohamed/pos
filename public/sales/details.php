<?php
require '../../includes/auth.php';
require '../../includes/db.php';

checkLogin();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: list.php');
    exit;
}

$sale_id = (int)$_GET['id'];

// Fetch sale info
$stmt = $pdo->prepare("
    SELECT s.*, u.username 
    FROM sales s
    JOIN users u ON s.user_id = u.id
    WHERE s.id = ?
");
$stmt->execute([$sale_id]);
$sale = $stmt->fetch();

if (!$sale) {
    header('Location: list.php');
    exit;
}

// Fetch sale items
$stmt = $pdo->prepare("
    SELECT si.*, p.name 
    FROM sale_items si
    JOIN products p ON si.product_id = p.id
    WHERE si.sale_id = ?
");
$stmt->execute([$sale_id]);
$items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sale Details #<?= htmlspecialchars($sale_id) ?></title>
</head>
<body>
    <h1>Sale Details #<?= htmlspecialchars($sale_id) ?></h1>
    <p><a href="list.php">Back to Sales History</a></p>

    <p><strong>Date & Time:</strong> <?= htmlspecialchars($sale['created_at']) ?></p>
    <p><strong>Processed By:</strong> <?= htmlspecialchars($sale['username']) ?></p>
    <p><strong>Total Amount:</strong> $<?= number_format($sale['total_amount'], 2) ?></p>

    <h2>Items</h2>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr>
            <th>Product</th>
            <th>Quantity</th>
            <th>Price (each)</th>
            <th>Subtotal</th>
        </tr>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['name']) ?></td>
                <td><?= $item['quantity'] ?></td>
                <td>$<?= number_format($item['price'], 2) ?></td>
                <td>$<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
