<?php
require '../../includes/auth.php';
require '../../includes/db.php';

checkLogin();

$stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC");
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50 text-slate-800">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>POS Terminal Console</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="h-full antialiased flex flex-col min-h-screen bg-slate-50/50">

    <!-- Top Premium Navigation Bar -->
    <nav class="border-b border-slate-200 bg-white/80 backdrop-blur-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <div class="flex items-center gap-4">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-lg shadow-blue-500/20">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 011 1v1a1 1 0 01-1 1H5a1 1 0 01-1-1V7zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-xs font-bold tracking-widest text-slate-400 uppercase block">POS Terminal</span>
                        <span class="text-lg font-bold text-slate-900 tracking-tight">Active Register</span>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <a href="../dashboard.php" 
                       class="inline-flex items-center justify-center text-xs font-semibold h-10 px-4 rounded-xl text-slate-600 hover:text-slate-900 bg-white border border-slate-200 hover:bg-slate-50 transition-all duration-150 shadow-sm">
                        &larr; Dashboard
                    </a>
                    <a href="add.php" 
                       class="inline-flex items-center justify-center text-xs font-bold h-10 px-5 rounded-xl text-white bg-blue-600 hover:bg-blue-700 transition-all duration-150 shadow-md shadow-blue-500/10 hover:scale-[1.02] active:scale-[0.98]">
                        + Add New Product
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Dynamic Interface -->
    <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 w-full">
        
        <!-- Header Actions Layout -->
        <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h2 class="text-xl font-bold text-slate-900 tracking-tight">Product Catalog Matrix</h2>
                <p class="text-xs text-slate-500 mt-0.5">Manage live stock records, valuation units, and system SKUs.</p>
            </div>
            
            <!-- Quick Counters Badge -->
            <div class="flex items-center gap-3 w-full md:w-auto">
                <div class="flex-1 md:flex-none flex items-center gap-3 bg-white border border-slate-200 px-4 py-2 rounded-xl shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-xs font-mono font-semibold text-slate-400 uppercase tracking-wider">Total SKUs:</span>
                    <span class="text-sm font-mono font-bold text-blue-600"><?= count($products) ?></span>
                </div>
            </div>
        </div>

        <!-- Product Grid System -->
        <?php if (count($products) === 0): ?>
            <!-- Empty POS Grid Placeholder -->
            <div class="bg-white border-2 border-dashed border-slate-200 rounded-3xl py-20 px-4 text-center shadow-sm">
                <div class="h-16 w-16 bg-slate-50 border border-slate-200 text-slate-400 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-800">No Inventory Items Found</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-xs mx-auto">Your POS catalog is currently unpopulated. Add records to see them appear here as tiles.</p>
                <a href="add.php" class="mt-4 inline-flex items-center text-xs font-bold bg-blue-50 hover:bg-blue-100 text-blue-600 px-4 py-2 rounded-xl transition-colors border border-blue-200/50">
                    Add First Item &rarr;
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php foreach ($products as $product): ?>
                    <!-- Individual Light Grid POS Card -->
                    <div class="group relative bg-white border border-slate-200 hover:border-slate-300/80 rounded-2xl overflow-hidden transition-all duration-300 flex flex-col justify-between shadow-sm hover:shadow-md hover:-translate-y-0.5">
                        
                        <!-- Top Image Media Block -->
                        <div>
                            <div class="relative aspect-video w-full bg-slate-100 border-b border-slate-100 overflow-hidden flex items-center justify-center">
                                <?php if (!empty($product['image_data'])): ?>
                                    <img src="image.php?id=<?= $product['id'] ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-500" />
                                <?php else: ?>
                                    <div class="absolute inset-0 bg-gradient-to-br from-slate-50 to-slate-100 flex flex-col items-center justify-center gap-1.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span class="text-[9px] uppercase font-mono tracking-widest text-slate-400">No Image Asset</span>
                                    </div>
                                <?php endif; ?>

                                <!-- Floating SKU Badge -->
                                <div class="absolute top-3 left-3 bg-white/95 backdrop-blur-md text-slate-500 border border-slate-200 text-[10px] font-mono px-2 py-0.5 rounded-lg shadow-xs tracking-wide">
                                    <?= htmlspecialchars($product['sku'] ?: 'NO_SKU') ?>
                                </div>
                            </div>

                            <!-- Content Area -->
                            <div class="p-5">
                                <h3 class="font-bold text-slate-900 tracking-tight group-hover:text-blue-600 transition-colors line-clamp-1 text-base">
                                    <?= htmlspecialchars($product['name']) ?>
                                </h3>
                                <span class="text-[10px] text-slate-400 font-mono block mt-0.5">ID: #<?= sprintf('%04d', $product['id']) ?></span>
                                
                                <!-- POS Financial Info Block -->
                                <div class="flex items-center justify-between mt-4 bg-slate-50 border border-slate-100 rounded-xl p-3">
                                    <div>
                                        <span class="text-[9px] uppercase tracking-wider font-bold text-slate-400 block">Retail Price</span>
                                        <span class="text-lg font-mono font-bold text-slate-900">
                                            Rs<?= number_format($product['price'], 2) ?>
                                        </span>
                                    </div>

                                    <div class="text-right">
                                        <span class="text-[9px] uppercase tracking-wider font-bold text-slate-400 block mb-0.5">Stock Status</span>
                                        <?php if ($product['stock_quantity'] <= 5): ?>
                                            <span class="px-2 py-0.5 inline-flex text-[10px] font-bold font-mono rounded-lg bg-red-50 text-red-600 border border-red-100">
                                                LOW [<?= htmlspecialchars($product['stock_quantity']) ?>]
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-0.5 inline-flex text-[10px] font-bold font-mono rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100">
                                                QTY [<?= htmlspecialchars($product['stock_quantity']) ?>]
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card Execution Bar -->
                        <div class="px-5 pb-5 pt-0 grid grid-cols-2 gap-2">
                            <a href="edit.php?id=<?= urlencode($product['id']) ?>" 
                               class="inline-flex items-center justify-center text-xs font-semibold h-9 rounded-xl text-slate-700 bg-slate-100 hover:bg-slate-200/70 border border-slate-200/60 transition-colors">
                                Edit Item
                            </a>
                            <a href="delete.php?id=<?= urlencode($product['id']) ?>" 
                               onclick="return confirm('Drop selected product item securely from live tracking storage?')" 
                               class="inline-flex items-center justify-center text-xs font-semibold h-9 rounded-xl text-red-600 bg-red-50 hover:bg-red-100 border border-red-100/70 transition-colors">
                                Delete
                            </a>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

</body>
</html>