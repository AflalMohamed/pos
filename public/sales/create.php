<?php
require '../../includes/auth.php';
require '../../includes/db.php';

checkLogin();
session_start();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle adding product to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'], $_POST['quantity'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = max(1, (int)$_POST['quantity']);

    // Fetch product to confirm it exists and get price
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if ($product) {
        // If product already in cart, update quantity
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = [
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $quantity
            ];
        }
    }
}

// Handle removing item from cart
if (isset($_GET['remove'])) {
    $remove_id = (int)$_GET['remove'];
    unset($_SESSION['cart'][$remove_id]);
}

// Handle checkout
if (isset($_POST['checkout'])) {
    if (!empty($_SESSION['cart'])) {
        $pdo->beginTransaction();

        try {
            // Calculate total
            $total_amount = 0;
            foreach ($_SESSION['cart'] as $item) {
                $total_amount += $item['price'] * $item['quantity'];
            }

            // Insert sale
            $stmt = $pdo->prepare("INSERT INTO sales (user_id, total_amount) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'], $total_amount]);
            $sale_id = $pdo->lastInsertId();

            // Insert sale items and update stock
            $stmt_item = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt_update_stock = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");

            foreach ($_SESSION['cart'] as $pid => $item) {
                $stmt_item->execute([$sale_id, $pid, $item['quantity'], $item['price']]);
                $stmt_update_stock->execute([$item['quantity'], $pid]);
            }

            $pdo->commit();

            // Clear cart
            $_SESSION['cart'] = [];
            $success = "Sale completed successfully!";

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Failed to complete sale: " . $e->getMessage();
        }
    } else {
        $error = "Cart is empty.";
    }
}

// Fetch all products for dropdown
$products = $pdo->query("SELECT id, name, price, stock_quantity FROM products WHERE stock_quantity > 0 ORDER BY name")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Sale</title>
</head>
<body>
    <h1>Create Sale</h1>
    <p><a href="../dashboard.php">Back to Dashboard</a></p>

    <?php if (!empty($error)): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php elseif (!empty($success)): ?>
        <p style="color:green;"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Product:
            <select name="product_id" required>
                <option value="">Select product</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (Stock: <?= $p['stock_quantity'] ?>) - $<?= number_format($p['price'], 2) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Quantity:
            <input type="number" name="quantity" min="1" value="1" required>
        </label>
        <button type="submit">Add to Cart</button>
    </form>

    <h2>Cart</h2>
    <?php if (!empty($_SESSION['cart'])): ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <tr>
                <th>Product</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Subtotal</th>
                <th>Action</th>
            </tr>
            <?php
            $total = 0;
            foreach ($_SESSION['cart'] as $pid => $item):
                $subtotal = $item['price'] * $item['quantity'];
                $total += $subtotal;
            ?>
            <tr>
                <td><?= htmlspecialchars($item['name']) ?></td>
                <td>$<?= number_format($item['price'], 2) ?></td>
                <td><?= $item['quantity'] ?></td>
                <td>$<?= number_format($subtotal, 2) ?></td>
                <td><a href="?remove=<?= $pid ?>" onclick="return confirm('Remove this item?')">Remove</a></td>
            </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="3" align="right"><strong>Total:</strong></td>
                <td colspan="2"><strong>$<?= number_format($total, 2) ?></strong></td>
            </tr>
        </table>

        <form method="POST" style="margin-top: 20px;">
            <button type="submit" name="checkout">Complete Sale</button>
        </form>
    <?php else: ?>
        <p>Cart is empty.</p>
    <?php endif; ?>

</body>
</html>
