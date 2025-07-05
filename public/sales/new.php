<?php
require '../../includes/auth.php';
require '../../includes/db.php';

checkRole(['admin', 'cashier']);  // Only admin and cashier allowed

$error = '';
$success = '';
$receipt = null;

// Fetch all products
$stmt = $pdo->query("SELECT * FROM products");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $items = $_POST['items'] ?? [];
    $customer_payment = isset($_POST['customer_payment']) ? (float)$_POST['customer_payment'] : 0;

    $total_amount = 0;
    $sale_items = [];

    try {
        $pdo->beginTransaction();

        foreach ($items as $product_id => $quantity) {
            $quantity = (int)$quantity;
            if ($quantity > 0) {
                $stmt = $pdo->prepare("SELECT id, name, price, stock_quantity FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$product) {
                    throw new Exception("Product ID $product_id not found.");
                }
                if ($quantity > $product['stock_quantity']) {
                    throw new Exception("Not enough stock for product '{$product['name']}'.");
                }

                $line_total = $product['price'] * $quantity;
                $total_amount += $line_total;
                $sale_items[] = [
                    'product_id' => $product['id'],
                    'name' => $product['name'],
                    'quantity' => $quantity,
                    'price' => $product['price'],
                    'line_total' => $line_total
                ];
            }
        }

        if ($total_amount <= 0) {
            throw new Exception("No items selected.");
        }

        if ($customer_payment < $total_amount) {
            throw new Exception("Customer payment is less than total amount.");
        }

        $balance = $customer_payment - $total_amount;

        $stmt = $pdo->prepare("INSERT INTO sales (user_id, total_amount, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$_SESSION['user_id'], $total_amount]);
        $sale_id = $pdo->lastInsertId();

        $stmt_insert_item = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt_update_stock = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");

        foreach ($sale_items as $item) {
            $stmt_insert_item->execute([$sale_id, $item['product_id'], $item['quantity'], $item['price']]);
            $stmt_update_stock->execute([$item['quantity'], $item['product_id']]);
        }

        $pdo->commit();
        $success = "Sale completed! Total amount: $" . number_format($total_amount, 2) . ". Customer paid: $" . number_format($customer_payment, 2) . ". Balance: $" . number_format($balance, 2);

        $receipt = [
            'sale_id' => $sale_id,
            'date' => date('Y-m-d H:i:s'),
            'items' => $sale_items,
            'total_amount' => $total_amount,
            'customer_payment' => $customer_payment,
            'balance' => $balance,
        ];

        // Refresh product list after stock update
        $stmt = $pdo->query("SELECT * FROM products");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>New Sale - POS</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        /* Print styling for receipt */
        @media print {
            body * {
                visibility: hidden;
            }
            #receipt, #receipt * {
                visibility: visible;
            }
            #receipt {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                padding: 2rem;
                background: #fff;
                color: #111827;
                font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                font-size: 14pt;
            }
            #print-btn, #new-sale-btn {
                display: none;
            }
            #receipt table thead tr {
                background-color: #f9fafb !important;
            }
        }
    </style>
</head>
<body class="bg-gradient-to-r from-indigo-50 via-white to-indigo-50 min-h-screen font-sans">

<header class="bg-indigo-700 text-white shadow-lg py-5">
    <div class="max-w-7xl mx-auto px-6 flex justify-between items-center">
        <h1 class="text-3xl font-extrabold tracking-tight drop-shadow-md">Point of Sale - New Sale</h1>
        <a href="../dashboard.php" 
           class="inline-flex items-center gap-2 bg-white text-indigo-700 font-semibold px-5 py-2 rounded-lg shadow-md hover:bg-indigo-100 transition">
            &larr; Dashboard
        </a>
    </div>
</header>

<main class="max-w-7xl mx-auto px-6 py-10">

    <?php if ($error): ?>
        <div class="mb-8 rounded-lg bg-red-100 border border-red-400 text-red-800 px-6 py-4 shadow-sm flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636l-12.728 12.728M6 18h.01M6 6h.01M18 18h.01M18 6h.01" />
            </svg>
            <p class="text-lg font-medium"><?= htmlspecialchars($error) ?></p>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="mb-10 rounded-lg bg-green-100 border border-green-400 text-green-900 px-6 py-4 shadow-md flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
            <p class="text-xl font-semibold"><?= htmlspecialchars($success) ?></p>
        </div>

        <section id="receipt" class="bg-white rounded-xl shadow-lg p-8 max-w-4xl mx-auto mb-12">
            <h2 class="text-3xl font-extrabold text-center mb-8 text-indigo-700">Sales Receipt</h2>
            <div class="flex justify-between text-lg font-semibold mb-6 px-2">
                <p><span class="text-gray-700">Sale ID:</span> <?= htmlspecialchars($receipt['sale_id']) ?></p>
                <p><span class="text-gray-700">Date:</span> <?= htmlspecialchars($receipt['date']) ?></p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full border border-gray-200 rounded-lg table-auto">
                    <thead class="bg-indigo-100 text-indigo-900 text-left text-sm uppercase tracking-wide font-semibold">
                        <tr>
                            <th class="py-3 px-5 border-b border-indigo-200">Product</th>
                            <th class="py-3 px-5 border-b border-indigo-200 text-center">Qty</th>
                            <th class="py-3 px-5 border-b border-indigo-200 text-right">Unit Price</th>
                            <th class="py-3 px-5 border-b border-indigo-200 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($receipt['items'] as $item): ?>
                            <tr class="border-b border-gray-100 hover:bg-indigo-50 transition">
                                <td class="py-3 px-5 font-medium"><?= htmlspecialchars($item['name']) ?></td>
                                <td class="py-3 px-5 text-center"><?= htmlspecialchars($item['quantity']) ?></td>
                                <td class="py-3 px-5 text-right">$<?= number_format($item['price'], 2) ?></td>
                                <td class="py-3 px-5 text-right font-semibold text-indigo-700">$<?= number_format($item['line_total'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-indigo-50 text-indigo-800 font-semibold">
                        <tr>
                            <td colspan="3" class="text-right py-4 px-5 border-t border-indigo-200">Total:</td>
                            <td class="text-right py-4 px-5 border-t border-indigo-200">$<?= number_format($receipt['total_amount'], 2) ?></td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-right py-4 px-5 border-t border-indigo-200">Customer Paid:</td>
                            <td class="text-right py-4 px-5 border-t border-indigo-200">$<?= number_format($receipt['customer_payment'], 2) ?></td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-right py-4 px-5 border-t border-indigo-200">Balance:</td>
                            <td class="text-right py-4 px-5 border-t border-indigo-200">$<?= number_format($receipt['balance'], 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="mt-10 flex justify-center gap-6">
                <button id="print-btn" 
                        onclick="window.print()" 
                        class="bg-indigo-700 text-white px-8 py-3 rounded-lg shadow-lg hover:bg-indigo-800 transition font-semibold tracking-wide">
                    Print Receipt
                </button>

                <button id="new-sale-btn" 
                        onclick="window.location.href='new.php'" 
                        class="bg-gray-600 text-white px-8 py-3 rounded-lg shadow-lg hover:bg-gray-700 transition font-semibold tracking-wide">
                    New Sale
                </button>
            </div>
        </section>
    <?php endif; ?>

    <form method="POST" action="" class="space-y-10" id="sale-form" novalidate>

        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-gray-900">
                <thead class="bg-indigo-100">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wide">Product</th>
                        <th scope="col" class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wide">Image</th>
                        <th scope="col" class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wide">Price</th>
                        <th scope="col" class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wide">Available Stock</th>
                        <th scope="col" class="px-6 py-4 text-left text-sm font-semibold uppercase tracking-wide">Quantity to Sell</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($products as $product): ?>
                        <tr class="hover:bg-indigo-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap font-semibold text-indigo-800 text-lg"><?= htmlspecialchars($product['name']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($product['image_data']): ?>
                                    <img src="data:<?= htmlspecialchars($product['image_type']) ?>;base64,<?= base64_encode($product['image_data']) ?>" 
                                         alt="Product Image" 
                                         class="w-24 h-24 object-cover rounded-lg border border-gray-300 shadow-sm" />
                                <?php else: ?>
                                    <span class="text-gray-400 italic">No image</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap font-mono text-indigo-900">$<?= number_format($product['price'], 2) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap font-semibold text-indigo-700"><?= htmlspecialchars($product['stock_quantity']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="number"
                                       name="items[<?= $product['id'] ?>]"
                                       min="0" max="<?= $product['stock_quantity'] ?>"
                                       value="0"
                                       class="quantity-input w-20 rounded-lg border border-indigo-300 text-center text-lg font-semibold focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <section class="max-w-lg mx-auto bg-white p-8 rounded-xl shadow-lg border border-gray-200">
            <label for="customer_payment" class="block font-semibold mb-3 text-indigo-700 text-lg">Customer Payment Amount</label>
            <input id="customer_payment" name="customer_payment" type="number" min="0" step="0.01" required
                   placeholder="Enter customer payment"
                   class="w-full rounded-lg border border-indigo-300 px-4 py-3 font-semibold text-indigo-900 text-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
        </section>

        <div class="max-w-lg mx-auto text-center">
            <button type="submit" class="bg-indigo-700 text-white font-extrabold tracking-wide px-14 py-4 rounded-lg shadow-lg hover:bg-indigo-800 transition text-2xl w-full">
                Complete Sale
            </button>
        </div>
    </form>

</main>

</body>
</html>
