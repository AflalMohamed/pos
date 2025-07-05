<?php
require '../../includes/auth.php';
require '../../includes/db.php';

checkLogin();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $sku = trim($_POST['sku']);
    $price = $_POST['price'];
    $stock_quantity = $_POST['stock_quantity'];
    $image_data = null;
    $image_type = null;

    if (!$name || !$sku || !is_numeric($price) || !is_numeric($stock_quantity)) {
        $error = "Please fill in all fields correctly.";
    } else {
        // Check SKU uniqueness
        $stmt = $pdo->prepare("SELECT id FROM products WHERE sku = ?");
        $stmt->execute([$sku]);
        if ($stmt->fetch()) {
            $error = "SKU already exists.";
        } else {
            // Handle image upload if file is uploaded
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['image']['tmp_name'];
                $fileType = mime_content_type($fileTmpPath);

                // Allowed mime types
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];

                if (in_array($fileType, $allowedMimeTypes)) {
                    $image_data = file_get_contents($fileTmpPath);
                    $image_type = $fileType;
                } else {
                    $error = 'Upload failed. Allowed file types: jpg, jpeg, png, gif';
                }
            }

            if (!$error) {
                $stmt = $pdo->prepare("INSERT INTO products (name, sku, price, stock_quantity, image_data, image_type) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $sku, $price, $stock_quantity, $image_data, $image_type]);
                header('Location: list.php');
                exit;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Add New Product</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen font-sans text-gray-800">

<nav class="bg-gray-800 p-4 text-white flex justify-between items-center">
    <h1 class="text-xl font-bold">Add New Product</h1>
    <a href="list.php" class="hover:underline">Back to Product List</a>
</nav>

<main class="max-w-3xl mx-auto p-6 bg-white rounded-lg shadow mt-8">
    <?php if ($error): ?>
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-6">
        <div>
            <label for="name" class="block font-semibold mb-1">Name</label>
            <input id="name" name="name" type="text" required
                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                   class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-600" />
        </div>

        <div>
            <label for="sku" class="block font-semibold mb-1">SKU</label>
            <input id="sku" name="sku" type="text" required
                   value="<?= htmlspecialchars($_POST['sku'] ?? '') ?>"
                   class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-600" />
        </div>

        <div>
            <label for="price" class="block font-semibold mb-1">Price</label>
            <input id="price" name="price" type="number" step="0.01" required
                   value="<?= htmlspecialchars($_POST['price'] ?? '') ?>"
                   class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-600" />
        </div>

        <div>
            <label for="stock_quantity" class="block font-semibold mb-1">Stock Quantity</label>
            <input id="stock_quantity" name="stock_quantity" type="number" required
                   value="<?= htmlspecialchars($_POST['stock_quantity'] ?? '0') ?>"
                   class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-600" />
        </div>

        <div>
            <label for="image" class="block font-semibold mb-1">Product Image (optional)</label>
            <input id="image" name="image" type="file" accept="image/*"
                   class="block w-full text-gray-600" />
            <p class="text-sm text-gray-500 mt-1">Allowed file types: jpg, jpeg, png, gif</p>
        </div>

        <div>
            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded font-semibold transition">
                Add Product
            </button>
        </div>
    </form>
</main>

</body>
</html>
