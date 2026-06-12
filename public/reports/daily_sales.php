<?php
require '../../includes/auth.php';
require '../../includes/db.php';

checkRole(['admin', 'cashier']);

// 1. Determine active time-frame criteria parameter mapping
$filter_type = $_GET['filter_type'] ?? 'daily';
$selected_month = $_GET['selected_month'] ?? date('Y-m');
$selected_year = $_GET['selected_year'] ?? date('Y');

// Construct Base Date Bound String Clauses
if ($filter_type === 'monthly') {
    $dateCondition = "DATE_FORMAT(sales.created_at, '%Y-%m') = :time_bound";
    $time_parameter = $selected_month;
    $human_date_label = date('F Y', strtotime($selected_month . '-01'));
} elseif ($filter_type === 'yearly') {
    $dateCondition = "DATE_FORMAT(sales.created_at, '%Y') = :time_bound";
    $time_parameter = $selected_year;
    $human_date_label = "Calendar Year " . $selected_year;
} else {
    $filter_type = 'daily'; // Safe Fallback
    $dateCondition = "DATE(sales.created_at) = CURDATE()";
    $human_date_label = date('l, F j, Y');
}

// 2. Handle Data Destructive Request Routines Safely
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_sales'])) {
    if (!empty($_POST['select_all_records']) && $_POST['select_all_records'] === '1') {
        $pdo->beginTransaction();
        // Delete items bounded within the active filtered selection period safely
        if ($filter_type === 'daily') {
            $pdo->exec("DELETE si FROM sale_items si JOIN sales s ON si.sale_id = s.id WHERE DATE(s.created_at) = CURDATE()");
            $pdo->exec("DELETE FROM sales WHERE DATE(created_at) = CURDATE()");
        } else {
            $delStmtItems = $pdo->prepare("DELETE si FROM sale_items si JOIN sales s ON si.sale_id = s.id WHERE $dateCondition");
            $delStmtItems->execute(['time_bound' => $time_parameter]);
            
            $delStmtSales = $pdo->prepare("DELETE FROM sales WHERE $dateCondition");
            $delStmtSales->execute(['time_bound' => $time_parameter]);
        }
        $pdo->commit();
    } else {
        $idsToDelete = $_POST['sale_ids'] ?? [];
        if (!empty($idsToDelete) && is_array($idsToDelete)) {
            $idsToDelete = array_filter($idsToDelete, fn($id) => ctype_digit($id));
            if (!empty($idsToDelete)) {
                $placeholders = implode(',', array_fill(0, count($idsToDelete), '?'));
                $pdo->beginTransaction();

                $deleteItemsStmt = $pdo->prepare("DELETE FROM sale_items WHERE sale_id IN ($placeholders)");
                $deleteItemsStmt->execute($idsToDelete);

                $deleteStmt = $pdo->prepare("DELETE FROM sales WHERE id IN ($placeholders)");
                $deleteStmt->execute($idsToDelete);

                $pdo->commit();
            }
        }
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// 3. Extract Invoice Payload Data Rows Bounded to Selected Context
$saleIdFilter = (isset($_GET['sale_id']) && trim($_GET['sale_id']) !== '') ? trim($_GET['sale_id']) : null;

$queryStr = "SELECT sales.*, users.username FROM sales JOIN users ON sales.user_id = users.id WHERE $dateCondition";
$queryParams = ($filter_type !== 'daily') ? ['time_bound' => $time_parameter] : [];

if ($saleIdFilter) {
    $queryStr .= " AND sales.id = :sale_id";
    $queryParams['sale_id'] = $saleIdFilter;
}
$queryStr .= " ORDER BY sales.created_at DESC";

$stmt = $pdo->prepare($queryStr);
$stmt->execute($queryParams);
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Metrics Outlines
$totalSalesCount = count($sales);
$totalRevenue = 0.0;
foreach ($sales as $sale) {
    $totalRevenue += (float)$sale['total_amount'];
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50/50 text-slate-800">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>POS System — Financial Ledger Reports</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        @media print {
            body { background: #fff !important; padding: 0 !important; }
            .no-print { display: none !important; }
            .print-card { box-shadow: none !important; border: 1px solid #e2e8f0 !important; page-break-inside: avoid; }
        }
    </style>
</head>
<body class="h-full antialiased flex flex-col min-h-screen bg-slate-50/50">

    <nav class="border-b border-slate-200 bg-white/80 backdrop-blur-md sticky top-0 z-50 no-print">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 text-white shadow-md shadow-blue-500/20">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 2a1 1 0 011-1h2a1 1 0 011 1v14a1 1 0 01-1 1h-2a1 1 0 01-1-1V2z"/>
                        </svg>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold tracking-widest text-slate-400 uppercase block">Audit Terminal</span>
                        <span class="text-base font-bold text-slate-900 tracking-tight">Sales Analytics Desk</span>
                    </div>
                </div>
                <a href="../dashboard.php" class="inline-flex items-center justify-center text-xs font-semibold h-10 px-4 rounded-xl text-slate-600 hover:text-slate-900 bg-white border border-slate-200 hover:bg-slate-50 transition-all shadow-sm">
                    &larr; Return to Dashboard
                </a>
            </div>
        </div>
    </nav>

    <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 w-full space-y-8">
        
        <header class="bg-gradient-to-br from-slate-900 via-slate-800 to-blue-950 text-white rounded-2xl p-6 md:p-8 shadow-xl flex flex-col md:flex-row justify-between items-start md:items-center gap-6 relative overflow-hidden">
            <div class="relative z-10">
                <span class="text-[10px] font-bold uppercase tracking-widest text-blue-400">Archived Statement Logs</span>
                <h1 class="text-2xl font-bold tracking-tight mt-1">Financial Performance Ledger</h1>
                <p class="text-xs text-slate-300 font-medium mt-1">Reflecting calculations bounded to: <span class="text-white font-semibold underline underline-offset-4 decoration-blue-500"><?= htmlspecialchars($human_date_label) ?></span></p>
            </div>
            
            <div class="flex items-center gap-4 w-full md:w-auto relative z-10">
                <div class="bg-white/5 border border-white/10 backdrop-blur rounded-xl p-4 flex items-center gap-3 flex-1 md:flex-initial min-w-[140px]">
                    <div class="text-2xl font-mono font-bold tracking-tight"><?= $totalSalesCount ?></div>
                    <div class="text-[10px] font-bold uppercase text-slate-400 tracking-wider leading-none">Total<br>Invoices</div>
                </div>
                <div class="bg-white/5 border border-white/10 backdrop-blur rounded-xl p-4 flex items-center gap-3 flex-1 md:flex-initial min-w-[180px]">
                    <div class="text-2xl font-mono font-bold text-emerald-400 tracking-tight">Rs <?= number_format($totalRevenue, 2) ?></div>
                    <div class="text-[10px] font-bold uppercase text-slate-400 tracking-wider leading-none">Aggregated<br>Revenue</div>
                </div>
            </div>
        </header>

        <section class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-4 no-print">
            <div class="flex flex-col lg:flex-row justify-between gap-4 items-start lg:items-center">
                
                <form method="get" class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                    <div class="inline-flex p-1 bg-slate-100 rounded-xl border border-slate-200/60">
                        <a href="?filter_type=daily" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all <?= $filter_type === 'daily' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900' ?>">Daily</a>
                        <a href="?filter_type=monthly" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all <?= $filter_type === 'monthly' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900' ?>">Monthly Mapping</a>
                        <a href="?filter_type=yearly" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all <?= $filter_type === 'yearly' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900' ?>">Annual Metrics</a>
                    </div>
                    
                    <input type="hidden" name="filter_type" value="<?= htmlspecialchars($filter_type) ?>">

                    <?php if ($filter_type === 'monthly'): ?>
                        <input type="month" name="selected_month" value="<?= htmlspecialchars($selected_month) ?>" class="bg-slate-50 border border-slate-200 text-xs font-semibold rounded-xl h-9 px-3 focus:outline-none focus:border-blue-500">
                    <?php elseif ($filter_type === 'yearly'): ?>
                        <select name="selected_year" class="bg-slate-50 border border-slate-200 text-xs font-semibold rounded-xl h-9 px-3 focus:outline-none focus:border-blue-500">
                            <?php for($y = date('Y'); $y >= date('Y')-5; $y--): ?>
                                <option value="<?= $y ?>" <?= (int)$selected_year === $y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    <?php endif; ?>

                    <div class="flex items-center bg-slate-50 border border-slate-200 rounded-xl px-2.5 h-9 w-full sm:w-64">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        <input type="text" name="sale_id" placeholder="Invoice reference number..." value="<?= htmlspecialchars($saleIdFilter ?? '') ?>" class="bg-transparent text-xs font-medium w-full focus:outline-none placeholder-slate-400">
                    </div>

                    <button type="submit" class="h-9 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition-all shadow-sm">Execute Query</button>
                    <?php if ($saleIdFilter || $filter_type !== 'daily'): ?>
                        <a href="?" class="h-9 px-3 rounded-xl border border-slate-200 text-slate-500 hover:text-slate-900 flex items-center justify-center text-xs font-semibold transition-all hover:bg-slate-50">Clear Filters</a>
                    <?php endif; ?>
                </form>

                <button type="button" onclick="window.print()" class="h-9 px-4 rounded-xl border border-slate-200 hover:bg-slate-50 text-xs font-bold text-slate-700 flex items-center gap-1.5 transition-all w-full lg:w-auto justify-center shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-3a2 2 0 00-2-2H9a2 2 0 00-2 2v3a2 2 0 002 2zm5-11h.01" /></svg>
                    Print Audit Ledger
                </button>
            </div>
        </section>

        <form id="salesForm" method="post" novalidate>
            <input type="hidden" name="delete_sales" value="1" />
            <input type="hidden" name="select_all_records" id="selectAllRecordsInput" value="0" />

            <?php if (!empty($sales)): ?>
                <div class="bg-rose-50/50 border border-rose-100 rounded-xl p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 no-print mb-6">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                        <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 select-none cursor-pointer">
                            <input type="checkbox" id="selectAllVisibleCheckbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 h-4 w-4">
                            Select All Visible (<?= count($sales) ?>)
                        </label>
                        <label class="inline-flex items-center gap-2 text-xs font-bold text-rose-600 select-none cursor-pointer">
                            <input type="checkbox" id="selectAllRecordsCheckbox" class="rounded border-rose-300 text-rose-600 focus:ring-rose-500 h-4 w-4">
                            Target Entire Filter Range Period Block
                        </label>
                    </div>
                    <button type="submit" id="deleteSelectedBtn" class="w-full sm:w-auto inline-flex items-center justify-center text-xs font-bold h-9 px-4 rounded-xl text-white bg-rose-600 hover:bg-rose-700 disabled:opacity-40 disabled:cursor-not-allowed transition-all shadow-sm shadow-rose-500/10" disabled>
                        Permanently Delete Records
                    </button>
                </div>
            <?php endif; ?>

            <?php if (empty($sales)): ?>
                <div class="bg-white border-2 border-dashed border-slate-200 rounded-2xl py-20 px-4 text-center shadow-sm max-w-xl mx-auto">
                    <div class="h-12 w-12 bg-slate-50 text-slate-400 rounded-xl flex items-center justify-center mx-auto mb-3 border border-slate-100 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-800">No Record Profiles Tracked</h3>
                    <p class="text-xs text-slate-400 mt-1 max-w-xs mx-auto">The database evaluation engine returned zero structured transaction metrics conforming to the active target configuration parameters.</p>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($sales as $sale): ?>
                        <article class="print-card bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden transition-all duration-150 hover:shadow-md">
                            
                            <header class="bg-slate-50/70 border-b border-slate-200 px-5 py-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" name="sale_ids[]" value="<?= $sale['id'] ?>" id="sale-checkbox-<?= $sale['id'] ?>" class="sale-checkbox rounded border-slate-300 text-blue-600 focus:ring-blue-500 h-4 w-4 no-print cursor-pointer">
                                    <label for="sale-checkbox-<?= $sale['id'] ?>" class="text-sm font-bold text-slate-900 tracking-tight cursor-pointer">Invoice #<?= sprintf('%05d', $sale['id']) ?></label>
                                </div>
                                <div class="flex flex-wrap items-center gap-2.5 text-[11px] font-semibold text-slate-500">
                                    <span class="bg-white border border-slate-200 px-2.5 py-1 rounded-lg">Operator: <strong class="text-slate-800 font-bold">@<?= htmlspecialchars($sale['username']) ?></strong></span>
                                    <span class="bg-white border border-slate-200 px-2.5 py-1 rounded-lg">Date: <strong class="text-slate-800 font-bold"><?= date('Y-m-d', strtotime($sale['created_at'])) ?></strong></span>
                                    <span class="bg-white border border-slate-200 px-2.5 py-1 rounded-lg">Time: <strong class="text-slate-800 font-bold"><?= date('h:i A', strtotime($sale['created_at'])) ?></strong></span>
                                    <span class="text-sm font-mono font-bold text-blue-600 sm:pl-3">Rs <?= number_format($sale['total_amount'], 2) ?></span>
                                </div>
                            </header>

                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse text-slate-600">
                                    <thead>
                                        <tr class="bg-slate-50/20 border-b border-slate-100 text-slate-400 text-[10px] font-bold uppercase tracking-wider select-none">
                                            <th scope="col" class="px-5 py-3">Product Name Reference</th>
                                            <th scope="col" class="px-5 py-3 w-32 text-center">Quantity</th>
                                            <th scope="col" class="px-5 py-3 w-40 text-right pr-6">Unit Evaluation</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50 text-xs">
                                        <?php
                                        $stmtItems = $pdo->prepare("SELECT sale_items.*, products.name FROM sale_items JOIN products ON sale_items.product_id = products.id WHERE sale_items.sale_id = ?");
                                        $stmtItems->execute([$sale['id']]);
                                        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($items as $item): ?>
                                            <tr class="hover:bg-slate-50/40">
                                                <td class="px-5 py-2.5 font-semibold text-slate-800"><?= htmlspecialchars($item['name']) ?></td>
                                                <td class="px-5 py-2.5 text-center font-mono font-bold text-slate-400 bg-slate-50/30"><?= (int)$item['quantity'] ?></td>
                                                <td class="px-5 py-2.5 text-right font-mono font-bold text-slate-800 pr-6">Rs <?= number_format($item['price'], 2) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </form>
    </main>

    <script>
        (() => {
            const form = document.getElementById('salesForm');
            if(!form) return;

            const deleteBtn = document.getElementById('deleteSelectedBtn');
            const selectAllVisibleCheckbox = document.getElementById('selectAllVisibleCheckbox');
            const selectAllRecordsCheckbox = document.getElementById('selectAllRecordsCheckbox');
            const selectAllRecordsInput = document.getElementById('selectAllRecordsInput');
            const saleCheckboxes = () => [...document.querySelectorAll('.sale-checkbox')];

            function updateDeleteBtnState() {
                const anyChecked = saleCheckboxes().some(cb => cb.checked);
                deleteBtn.disabled = !anyChecked && !selectAllRecordsCheckbox.checked;
            }

            selectAllVisibleCheckbox.addEventListener('change', () => {
                const isChecked = selectAllVisibleCheckbox.checked;
                saleCheckboxes().forEach(cb => cb.checked = isChecked);
                if (isChecked) {
                    selectAllRecordsCheckbox.checked = false;
                    selectAllRecordsInput.value = '0';
                }
                updateDeleteBtnState();
            });

            selectAllRecordsCheckbox.addEventListener('change', () => {
                if (selectAllRecordsCheckbox.checked) {
                    selectAllVisibleCheckbox.checked = false;
                    saleCheckboxes().forEach(cb => cb.checked = false);
                    selectAllRecordsInput.value = '1';
                    deleteBtn.disabled = false;
                } else {
                    selectAllRecordsInput.value = '0';
                    updateDeleteBtnState();
                }
            });

            saleCheckboxes().forEach(cb => {
                cb.addEventListener('change', () => {
                    const allChecked = saleCheckboxes().every(cb => cb.checked);
                    selectAllVisibleCheckbox.checked = allChecked;
                    if (allChecked) {
                        selectAllRecordsCheckbox.checked = false;
                        selectAllRecordsInput.value = '0';
                    }
                    updateDeleteBtnState();
                });
            });

            form.addEventListener('submit', (e) => {
                const isMassPurge = selectAllRecordsCheckbox.checked;
                const confirmPrompt = isMassPurge
                    ? "CRITICAL WARNING:\nYou are attempting a complete range block purge. This will permanently erase ALL transaction logs inside this filtered selection timeline. This action is irreversible.\n\nDo you authorize this data purge?"
                    : "Confirm Database Dropping:\nAre you sure you want to permanently erase the selected individual invoice logs?";
                
                if (!confirm(confirmPrompt)) {
                    e.preventDefault();
                }
            });

            updateDeleteBtnState();
        })();
    </script>
</body>
</html>