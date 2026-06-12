<?php
require '../../includes/auth.php';
require '../../includes/db.php';

checkLogin();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: list.php');
    exit;
}

$id = (int)$_GET['id'];
$error = '';

// Fetch product
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: list.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $sku = trim($_POST['sku']);
    $price = $_POST['price'];
    $stock_quantity = $_POST['stock_quantity'];

    $image_data = $product['image_data'];
    $image_type = $product['image_type'];

    if (!$name || !$sku || !is_numeric($price) || !is_numeric($stock_quantity)) {
        $error = "Please fill in all fields correctly.";
    } else {
        // Check SKU uniqueness
        $stmt = $pdo->prepare("SELECT id FROM products WHERE sku = ? AND id != ?");
        $stmt->execute([$sku, $id]);
        if ($stmt->fetch()) {
            $error = "SKU already exists for another product.";
        } else {
            // Handle new image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['image']['tmp_name'];
                $fileType = mime_content_type($fileTmpPath);
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];

                if (in_array($fileType, $allowedMimeTypes)) {
                    $image_data = file_get_contents($fileTmpPath);
                    $image_type = $fileType;
                } else {
                    $error = 'Upload failed. Allowed file types: jpg, jpeg, png, gif';
                }
            }

            if (!$error) {
                $stmt = $pdo->prepare("UPDATE products SET name = ?, sku = ?, price = ?, stock_quantity = ?, image_data = ?, image_type = ? WHERE id = ?");
                $stmt->execute([$name, $sku, $price, $stock_quantity, $image_data, $image_type, $id]);
                header('Location: list.php');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50 text-slate-800">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>POS Terminal Console — Edit Item</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="h-full antialiased flex flex-col min-h-screen bg-slate-50/50">

    <nav class="border-b border-slate-200 bg-white/80 backdrop-blur-md sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <div class="flex items-center gap-4">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-500 text-white shadow-lg shadow-amber-500/20">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-xs font-bold tracking-widest text-slate-400 uppercase block"> Editor</span>
                        <span class="text-lg font-bold text-slate-900 tracking-tight">Modify Inventory Token</span>
                    </div>
                </div>
                <div>
                    <a href="list.php" 
                       class="inline-flex items-center justify-center text-xs font-semibold h-10 px-4 rounded-xl text-slate-600 hover:text-slate-900 bg-white border border-slate-200 hover:bg-slate-50 transition-all duration-150 shadow-sm">
                        &larr; Cancel Modifications
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-1 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10 w-full">
        
        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-sm text-red-700 rounded-2xl flex items-start gap-3 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500 flex-shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <div>
                    <span class="font-bold block">Update Rejection</span>
                    <p class="text-xs text-red-600 mt-0.5"><?= htmlspecialchars($error) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white border border-slate-200 rounded-3xl p-6 md:p-8 shadow-sm space-y-6">
                    <div>
                        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-4">Core Identification</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="name" class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5">Product Identity Name</label>
                                <input id="name" name="name" type="text" required
                                       value="<?= htmlspecialchars($_POST['name'] ?? $product['name']) ?>"
                                       class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-900 focus:outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10 transition-all font-medium" />
                            </div>

                            <div>
                                <label for="sku" class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5">SKU Code / Barcode</label>
                                <input id="sku" name="sku" type="text" required
                                       value="<?= htmlspecialchars($_POST['sku'] ?? $product['sku']) ?>"
                                       class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-mono text-slate-900 focus:outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10 transition-all font-bold" />
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-slate-100"></div>

                    <div>
                        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-4">Financials & Inventory Metrics</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="price" class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5">Register Value (Rs)</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-3.5 text-sm font-mono text-slate-400 font-bold">Rs</span>
                                    <input id="price" name="price" type="number" step="0.01" required
                                           value="<?= htmlspecialchars($_POST['price'] ?? $product['price']) ?>"
                                           class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-8 pr-4 py-3 text-sm font-mono text-slate-900 focus:outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10 transition-all font-bold" />
                                </div>
                            </div>

                            <div>
                                <label for="stock_quantity" class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5">Current Stock Level</label>
                                <input id="stock_quantity" name="stock_quantity" type="number" required
                                       value="<?= htmlspecialchars($_POST['stock_quantity'] ?? $product['stock_quantity']) ?>"
                                       class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-mono text-slate-900 focus:outline-none focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10 transition-all font-bold" />
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-slate-100"></div>

                    <div>
                        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-3">Replace Media Asset</h3>
                        <div class="relative border-2 border-dashed border-slate-200 hover:border-slate-300 rounded-2xl bg-slate-50/50 hover:bg-slate-50 transition-colors p-6 text-center group">
                            <input id="image" name="image" type="file" accept="image/*"
                                   class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" />
                            <div class="space-y-1.5 pointer-events-none">
                                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-7 w-7 text-slate-400 group-hover:text-blue-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <div class="text-xs font-semibold text-slate-600">
                                    <span class="text-blue-600 group-hover:underline">Upload replacement photo</span> or drop file
                                </div>
                                <p class="text-[10px] font-mono tracking-wider text-slate-400">JPG, JPEG, PNG, or GIF</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm flex flex-col items-center text-center">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block mb-4 w-full text-left border-b border-slate-100 pb-2">Active Asset Preview</span>
                    
                    <div class="w-full aspect-square bg-slate-50 rounded-2xl border border-slate-200 overflow-hidden flex items-center justify-center p-2 mb-3">
                        <?php if ($product['image_data']): ?>
                            <img src="data:<?= htmlspecialchars($product['image_type']) ?>;base64,<?= base64_encode($product['image_data']) ?>"
                                 alt="Current Product Image"
                                 class="h-full w-full object-cover rounded-xl shadow-inner" />
                        <?php else: ?>
                            <div class="flex flex-col items-center justify-center text-slate-300 gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                <span class="text-[10px] font-mono uppercase tracking-wider text-slate-400">No Asset Bound</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="text-left w-full bg-slate-50 border border-slate-100 p-3 rounded-xl font-mono text-[11px] text-slate-500 space-y-1">
                        <div><span class="font-bold text-slate-400">Internal ID:</span> #<?= sprintf('%04d', $product['id']) ?></div>
                        <div><span class="font-bold text-slate-400">Status:</span> Live Register Tile</div>
                    </div>
                </div>

                <button type="submit"
                        class="w-full inline-flex items-center justify-center text-xs font-bold h-12 px-6 rounded-xl text-white bg-blue-600 hover:bg-blue-700 transition-all duration-150 shadow-md shadow-blue-500/10 hover:scale-[1.01] active:scale-[0.99]">
                    Commit Configuration Updates
                </button>
            </div>

        </form>
    </main>

</body>
</html>