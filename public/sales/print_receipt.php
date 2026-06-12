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
<html lang="en" class="h-full bg-slate-100 text-slate-800">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>POS Ledger System — Invoice #<?= sprintf('%05d', $sale_id) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        
        /* Directives overriding print states perfectly for thermal tape / standard papers */
        @media print {
            body { background-color: transparent !important; color: #000000 !important; padding: 0 !important; }
            .no-print { display: none !important; }
            .print-card { border: none !important; box-shadow: none !important; max-width: 100% !important; background: white !important; padding: 0 !important; }
        }
    </style>
</head>
<body class="antialiased bg-slate-100 min-h-screen py-10 px-4 flex flex-col items-center justify-start">

    <!-- Action Bar & Navigation Interactivity Tokens -->
    <div class="w-full max-w-md mb-4 flex justify-between items-center no-print px-1">
        <a href="../sales/list.php" class="text-xs font-semibold text-slate-500 hover:text-slate-900 transition-colors flex items-center gap-1">
            &larr; Back to History
        </a>
        <span class="text-[10px] font-mono font-bold uppercase text-slate-400 tracking-wider bg-slate-200/60 px-2 py-0.5 rounded">
            Archived Entry
        </span>
    </div>

    <!-- Main Core Invoice Layout Card Container -->
    <div class="print-card w-full max-w-md bg-white border border-slate-200 rounded-3xl p-6 md:p-8 shadow-sm relative overflow-hidden">
        
        <!-- Subtle Premium Structural Ribbon Design (Non-printing) -->
        <div class="absolute top-0 left-0 right-0 h-1.5 bg-blue-600 no-print"></div>

        <!-- Receipt Header Matrix -->
        <div class="text-center pb-6 border-b border-dashed border-slate-200">
            <h2 class="text-xl font-bold tracking-tight text-slate-900 uppercase">POS Retail Terminal</h2>
            <p class="text-xs text-slate-400 font-medium mt-0.5">Commercial Transaction Document</p>
        </div>

        <!-- Audit Trail Metadata Layout -->
        <div class="py-6 space-y-2.5 text-xs border-b border-dashed border-slate-200">
            <div class="flex justify-between items-center">
                <span class="font-semibold text-slate-400 uppercase tracking-wide text-[10px]">Reference Identification</span>
                <span class="font-mono font-bold text-slate-900">#<?= sprintf('%05d', $sale_id) ?></span>
            </div>
            <div class="flex justify-between items-center">
                <span class="font-semibold text-slate-400 uppercase tracking-wide text-[10px]">Timestamp Protocol</span>
                <span class="font-mono font-medium text-slate-700"><?= date('Y-m-d H:i:s', strtotime($sale['created_at'])) ?></span>
            </div>
            <div class="flex justify-between items-center">
                <span class="font-semibold text-slate-400 uppercase tracking-wide text-[10px]">Issuing Registrar</span>
                <span class="font-semibold text-slate-800">@<?= htmlspecialchars($sale['username']) ?></span>
            </div>
        </div>

        <!-- Ledger Items Processing Matrix -->
        <div class="py-6">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100 pb-2">
                        <th scope="col" class="pb-2">Description Matrix</th>
                        <th scope="col" class="pb-2 text-center w-12">Qty</th>
                        <th scope="col" class="pb-2 text-right w-20">Unit</th>
                        <th scope="col" class="pb-2 text-right w-24">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 text-xs font-medium text-slate-700">
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td class="py-3 text-slate-900 pr-2 font-semibold"><?= htmlspecialchars($item['name']) ?></td>
                            <td class="py-3 text-center font-mono font-bold text-slate-400"><?= $item['quantity'] ?></td>
                            <td class="py-3 text-right font-mono text-slate-500">Rs<?= number_format($item['price'], 2) ?></td>
                            <td class="py-3 text-right font-mono font-bold text-slate-900">Rs<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Summary Totals Execution Segment -->
        <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4 space-y-2">
            <div class="flex justify-between items-center text-xs">
                <span class="font-bold text-slate-400 uppercase tracking-wider text-[10px]">Itemization Summary</span>
                <span class="font-mono font-semibold text-slate-600"><?= count($items) ?> Component line items</span>
            </div>
            <div class="flex justify-between items-center pt-2 border-t border-slate-200/60">
                <span class="text-xs font-bold text-slate-900 uppercase tracking-wide">Gross Balance Due</span>
                <span class="text-xl font-mono font-bold text-blue-600">Rs<?= number_format($sale['total_amount'], 2) ?></span>
            </div>
        </div>

        <!-- Footer Verification Memo -->
        <div class="text-center pt-6 mt-6 border-t border-slate-100">
            <p class="text-[11px] font-medium text-slate-400">Thank you for your business.</p>
            <p class="text-[9px] font-mono font-semibold tracking-tight text-slate-300 uppercase mt-0.5 select-none">System Terminal Verified Output</p>
        </div>

    </div>

    <!-- Active Device Printing Engine Execution Call Button (Non-printing) -->
    <div class="w-full max-w-md mt-6 no-print">
        <button onclick="window.print()" 
                class="w-full inline-flex items-center justify-center text-xs font-bold h-12 px-6 rounded-xl text-white bg-blue-600 hover:bg-blue-700 transition-all duration-150 shadow-md shadow-blue-500/10 hover:scale-[1.01] active:scale-[0.99]">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-3a2 2 0 00-2-2H9a2 2 0 00-2 2v3a2 2 0 002 2zm5-11h.01J" />
            </svg>
            Commit Print Sequence
        </button>
    </div>

</body>
</html>