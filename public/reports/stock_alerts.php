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
<html lang="en" class="h-full bg-slate-50 text-slate-800">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>POS Terminal Console — Stock Alerts</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="h-full antialiased flex flex-col min-h-screen bg-slate-50/50">

    <nav class="border-b border-slate-200 bg-white/80 backdrop-blur-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <div class="flex items-center gap-4">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-rose-500 text-white shadow-lg shadow-rose-500/20">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-xs font-bold tracking-widest text-slate-400 uppercase block">System Health</span>
                        <span class="text-lg font-bold text-slate-900 tracking-tight">Depletion Monitors</span>
                    </div>
                </div>
                <div>
                    <a href="../dashboard.php" 
                       class="inline-flex items-center justify-center text-xs font-semibold h-10 px-4 rounded-xl text-slate-600 hover:text-slate-900 bg-white border border-slate-200 hover:bg-slate-50 transition-all duration-150 shadow-sm">
                        &larr; Dashboard
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 w-full">
        
        <div class="mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="text-xl font-bold text-slate-900 tracking-tight">Critical Stock Thresholds</h2>
                <p class="text-xs text-slate-500 mt-0.5">Live items requiring immediate operational procurement or vendor fulfillment mapping.</p>
            </div>
            
            <div class="flex items-center gap-3 w-full sm:w-auto">
                <div class="flex items-center gap-3 bg-white border border-slate-200 px-4 py-2 rounded-xl shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                    <span class="text-xs font-mono font-semibold text-slate-400 uppercase tracking-wider">Trigger Limit:</span>
                    <span class="text-sm font-mono font-bold text-slate-900">≤ <?= htmlspecialchars($threshold) ?> Units</span>
                </div>
            </div>
        </div>

        <?php if ($low_stock_products): ?>
            
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-slate-600">
                        <thead>
                            <tr class="bg-slate-50/70 border-b border-slate-200 text-slate-400 text-[10px] font-bold uppercase tracking-wider select-none">
                                <th scope="col" class="px-6 py-4 w-36">Product Identity</th>
                                <th scope="col" class="px-6 py-4">Item Catalog Label</th>
                                <th scope="col" class="px-6 py-4 w-48">Remaining Count</th>
                                <th scope="col" class="px-6 py-4 text-right pr-8 w-48">Procurement Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            <?php foreach ($low_stock_products as $product): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors duration-150 group">
                                    
                                    <td class="px-6 py-4 whitespace-nowrap font-mono font-bold text-slate-400">
                                        #<?= sprintf('%04d', $product['id']) ?>
                                    </td>
                                    
                                    <td class="px-6 py-4 whitespace-nowrap font-semibold text-slate-800">
                                        <?= htmlspecialchars($product['name']) ?>
                                    </td>
                                    
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($product['stock_quantity'] <= 2): ?>
                                            <span class="px-2.5 py-1 inline-flex items-center text-[10px] font-bold font-mono rounded-lg bg-red-50 text-red-600 border border-red-100 gap-1.5 uppercase">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                                                Critical: <?= htmlspecialchars($product['stock_quantity']) ?> left
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2.5 py-1 inline-flex items-center text-[10px] font-bold font-mono rounded-lg bg-amber-50 text-amber-600 border border-amber-100 gap-1.5 uppercase">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                Warning: <?= htmlspecialchars($product['stock_quantity']) ?> left
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="px-6 py-4 whitespace-nowrap text-right pr-8">
                                        <a href="../products/edit.php?id=<?= urlencode($product['id']) ?>" 
                                           class="inline-flex items-center justify-center text-[11px] font-bold h-8 px-4 rounded-lg text-white bg-blue-600 hover:bg-blue-700 transition-colors shadow-sm shadow-blue-500/10">
                                            Replenish Stock
                                        </a>
                                    </td>
                                    
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php else: ?>
            
            <div class="bg-white border-2 border-dashed border-slate-200 rounded-3xl py-20 px-4 text-center shadow-sm max-w-xl mx-auto">
                <div class="h-16 w-16 bg-emerald-50 text-emerald-500 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-emerald-100 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-800">Operational Integrity Intact</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-xs mx-auto">Every recorded SKU item is floating comfortably above the low alert parameters. No manual restocks required.</p>
            </div>

        <?php endif; ?>
    </main>

</body>
</html>