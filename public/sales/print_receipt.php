<?php
require '../../includes/auth.php';
require '../../includes/db.php';

checkRole(['admin', 'cashier']);

if (!isset($_GET['id'])) {
    die('Sale ID is required.');
}

$sale_id = intval($_GET['id']);

// Get sale info
$stmt = $pdo->prepare("
    SELECT sales.*, users.username 
    FROM sales 
    JOIN users ON sales.user_id = users.id 
    WHERE sales.id = ?
");
$stmt->execute([$sale_id]);
$sale = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sale) {
    die('Sale not found.');
}

// Get sale items
$itemsStmt = $pdo->prepare("
    SELECT si.quantity, p.name, p.price
    FROM sale_items si
    JOIN products p ON si.product_id = p.id
    WHERE si.sale_id = ?
");
$itemsStmt->execute([$sale_id]);
$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Receipt - Sale #<?= $sale_id ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .receipt { max-width: 400px; margin: 0 auto; }
        .receipt h2 { text-align: center; }
    </style>
</head>
<body class="p-4">
    <div class="receipt">
        <h2>MY SHOP</h2>
        <p>Date: <?= $sale['created_at'] ?><br>
           Cashier: <?= htmlspecialchars($sale['username']) ?><br>
           Sale ID: <?= $sale_id ?></p>

        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td><?= $item['quantity'] ?></td>
                        <td>$<?= number_format($item['price'], 2) ?></td>
                        <td>$<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h5>Total: $<?= number_format($sale['total_amount'], 2) ?></h5>

        <button onclick="window.print()" class="btn btn-primary w-100">Print Receipt</button>
    </div>
</body>
</html>
