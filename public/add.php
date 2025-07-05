<?php
require '../../includes/auth.php';
require '../../includes/db.php';

checkLogin();

// Get products to show in dropdown/select
$products = $pdo->query("SELECT id, name, price, stock_quantity FROM products")->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'] ?? null;
    $quantity = (int)($_POST['quantity'] ?? 0);

    if ($product_id && $quantity > 0) {
        // Get product info
        $stmt = $pdo->prepare("SELECT price, stock_quantity FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if (!$product) {
            $error = "Invalid product selected.";
        } elseif ($product['stock_quantity'] < $quantity) {
            $error = "Not enough stock available.";
        } else {
            $total_amount = $product['price'] * $quantity;

            // Start transaction
            $pdo->beginTransaction();

            try {
                // Insert sale record
                $stmt = $pdo->prepare("INSERT INTO sales (product_id, quantity, total_amount, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$product_id, $quantity, $total_amount]);

                // Update product stock
                $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
                $stmt->execute([$quantity, $product_id]);

                $pdo->commit();

                $success = "Sale recorded successfully!";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Failed to record sale: " . $e->getMessage();
            }
        }
    } else {
        $error = "Please select a product and enter a valid quantity.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Sale - POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    <h1>Add Sale</h1>
    <p><a href="../dashboard.php">Back to Dashboard</a></p>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label for="product_id" class="form-label">Product</label>
            <select id="product_id" name="product_id" class="form-select" required>
                <option value="">Select product</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= $p['id'] ?>">
                        <?= htmlspecialchars($p['name']) ?> — $<?= number_format($p['price'], 2) ?> (Stock: <?= $p['stock_quantity'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="quantity" class="form-label">Quantity</label>
            <input type="number" min="1" name="quantity" id="quantity" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Record Sale</button>
    </form>
</div>
</body>
</html>
