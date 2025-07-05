<?php
require '../../includes/auth.php';
require '../../includes/db.php';

checkLogin();

$stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC");
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Product List</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen font-sans text-gray-800">

<nav class="bg-gray-800 p-4 text-white flex justify-between items-center">
    <h1 class="text-xl font-bold">Product List</h1>
    <div class="space-x-4">
        <a href="../dashboard.php" class="hover:underline">Dashboard</a>
        <a href="add.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition">Add New Product</a>
    </div>
</nav>

<main class="max-w-7xl mx-auto p-6">
    <div class="overflow-x-auto shadow rounded-lg bg-white">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock Qty</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (count($products) === 0): ?>
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">No products found.</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($product['image_data']): ?>
                                <img src="image.php?id=<?= $product['id'] ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="h-16 w-16 object-cover rounded-md border border-gray-300" />
                            <?php else: ?>
                                <img src="https://via.placeholder.com/64" alt="No Image" class="h-16 w-16 object-cover rounded-md border border-gray-300" />
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap font-semibold"><?= htmlspecialchars($product['name']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-600"><?= htmlspecialchars($product['sku']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-green-600 font-semibold">$<?= number_format($product['price'], 2) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($product['stock_quantity'] <= 5): ?>
                                <span class="text-red-600 font-semibold"><?= htmlspecialchars($product['stock_quantity']) ?></span>
                            <?php else: ?>
                                <?= htmlspecialchars($product['stock_quantity']) ?>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap space-x-2">
                            <a href="edit.php?id=<?= $product['id'] ?>" class="text-blue-600 hover:underline">Edit</a>
                            <a href="delete.php?id=<?= $product['id'] ?>" onclick="return confirm('Delete this product?')" class="text-red-600 hover:underline">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

</body>
</html>
