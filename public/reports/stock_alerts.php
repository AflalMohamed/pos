<?php
require '../../includes/auth.php';
require '../../includes/db.php';

checkLogin();

$threshold = 5;
$stmt = $pdo->prepare("SELECT id, name, stock_quantity FROM products WHERE stock_quantity <= ? ORDER BY stock_quantity ASC");
$stmt->execute([$threshold]);
$low_stock_products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Inventory Stock Alerts</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen font-sans">

<header class="bg-black text-white shadow py-4">
    <div class="max-w-7xl mx-auto px-6 flex justify-between items-center">
        <h1 class="text-2xl font-semibold tracking-wide">Inventory Stock Alerts</h1>
        <a href="../dashboard.php" 
           class="bg-white text-black font-semibold px-4 py-2 rounded-md hover:bg-gray-100 transition">
            &larr; Back to Dashboard
        </a>
    </div>
</header>

<main class="max-w-5xl mx-auto px-6 py-8">

    <?php if ($low_stock_products): ?>
        <p class="mb-6 text-lg text-gray-700">
            The following products have low stock (≤ <span class="font-semibold"><?= htmlspecialchars($threshold) ?></span>):
        </p>

        <div class="overflow-x-auto bg-white rounded-lg shadow border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200 text-gray-900">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase tracking-wide">Product ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase tracking-wide">Name</th>
                        <th scope="col" class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase tracking-wide">Stock Quantity</th>
                        <th scope="col" class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase tracking-wide">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($low_stock_products as $product): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap align-middle font-medium"><?= htmlspecialchars($product['id']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap align-middle"><?= htmlspecialchars($product['name']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap align-middle">
                                <span class="<?= $product['stock_quantity'] <= 2 ? 'text-red-600 font-semibold' : 'text-yellow-600 font-semibold' ?>">
                                    <?= htmlspecialchars($product['stock_quantity']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap align-middle">
                                <a href="../products/edit.php?id=<?= urlencode($product['id']) ?>" 
                                   class="inline-block bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition font-semibold">
                                    Replenish Stock
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php else: ?>
        <p class="text-center text-gray-600 text-lg mt-12">All products have sufficient stock.</p>
    <?php endif; ?>

</main>

</body>
</html>
