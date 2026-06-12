<?php
require '../../includes/auth.php';
require '../../includes/db.php';

checkRole(['admin', 'cashier']);

$stmt = $pdo->query("
  SELECT sales.id, sales.total_amount, sales.created_at, users.username
  FROM sales
  JOIN users ON sales.user_id = users.id
  ORDER BY sales.created_at DESC
");
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50 text-slate-800">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>POS Terminal Console — Sales History</title>
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
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-500/20">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4zM18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h3a1 1 0 100-2H9z" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-xs font-bold tracking-widest text-slate-400 uppercase block">Audit & Finance</span>
                        <span class="text-lg font-bold text-slate-900 tracking-tight">Sales Ledger</span>
                    </div>
                </div>
                <div>
                    <button onclick="history.back()" 
                            class="inline-flex items-center justify-center text-xs font-semibold h-10 px-4 rounded-xl text-slate-600 hover:text-slate-900 bg-white border border-slate-200 hover:bg-slate-50 transition-all duration-150 shadow-sm">
                        &larr; Go Back
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 w-full">
        
        <div class="mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="text-xl font-bold text-slate-900 tracking-tight">Closed Transactions Logs</h2>
                <p class="text-xs text-slate-500 mt-0.5">Review history streams, trace issuing operators, and output physical invoices.</p>
            </div>
            
            <div class="flex items-center gap-3 w-full sm:w-auto">
                <div class="flex items-center gap-3 bg-white border border-slate-200 px-4 py-2 rounded-xl shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                    <span class="text-xs font-mono font-semibold text-slate-400 uppercase tracking-wider">Total Logs:</span>
                    <span class="text-sm font-mono font-bold text-indigo-600"><?= count($sales) ?></span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-slate-600">
                    <thead>
                        <tr class="bg-slate-50/70 border-b border-slate-200 text-slate-400 text-[10px] font-bold uppercase tracking-wider select-none">
                            <th scope="col" class="px-6 py-4 w-32">Sale Reference</th>
                            <th scope="col" class="px-6 py-4">Timestamp</th>
                            <th scope="col" class="px-6 py-4 w-48">Operator Badge</th>
                            <th scope="col" class="px-6 py-4 text-right w-40">Gross Amount</th>
                            <th scope="col" class="px-6 py-4 text-right pr-8 w-64">Documentation Output</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        <?php if (count($sales) === 0): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center text-slate-400 font-medium">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-300 mx-auto mb-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    No transaction records present in history caches.
                                </td>
                            </div>
                        <?php else: ?>
                            <?php foreach ($sales as $sale): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors duration-150 group">
                                    
                                    <td class="px-6 py-4 whitespace-nowrap font-mono font-bold text-indigo-600">
                                        TRX-<?= sprintf('%05d', $sale['id']) ?>
                                    </td>
                                    
                                    <td class="px-6 py-4 whitespace-nowrap text-slate-500 font-medium">
                                        <?= htmlspecialchars(date('Y-m-d — H:i', strtotime($sale['created_at']))) ?>
                                    </td>
                                    
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <div class="h-6 w-6 rounded-md bg-slate-100 text-slate-600 font-bold text-[10px] flex items-center justify-center uppercase border border-slate-200/60 select-none">
                                                <?= mb_substr(htmlspecialchars($sale['username']), 0, 2) ?>
                                            </div>
                                            <span class="font-semibold text-slate-700"><?= htmlspecialchars($sale['username']) ?></span>
                                        </div>
                                    </td>
                                    
                                    <td class="px-6 py-4 whitespace-nowrap text-right font-mono font-bold text-slate-900 text-sm">
                                        Rs<?= number_format($sale['total_amount'], 2) ?>
                                    </td>
                                    
                                    <td class="px-6 py-4 whitespace-nowrap text-right pr-8 space-x-1">
                                        <a href="print_receipt.php?id=<?= $sale['id'] ?>" target="_blank"
                                           class="inline-flex items-center justify-center text-[11px] font-bold h-8 px-3 rounded-lg text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 shadow-sm transition-all duration-100">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                            </svg>
                                            Receipt
                                        </a>
                                        <a href="download_pdf.php?id=<?= $sale['id'] ?>" target="_blank"
                                           class="inline-flex items-center justify-center text-[11px] font-bold h-8 px-3 rounded-lg text-emerald-600 bg-emerald-50 hover:bg-emerald-100 border border-emerald-100 transition-all duration-100">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            PDF
                                        </a>
                                    </td>
                                    
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

</body>
</html>