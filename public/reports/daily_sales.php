<?php
require '../../includes/auth.php';
require '../../includes/db.php';

checkRole(['admin', 'cashier']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_sales'])) {
    if (!empty($_POST['select_all_records']) && $_POST['select_all_records'] === '1') {
        $pdo->beginTransaction();
        $pdo->exec("DELETE si FROM sale_items si JOIN sales s ON si.sale_id = s.id WHERE DATE(s.created_at) = CURDATE()");
        $pdo->exec("DELETE FROM sales WHERE DATE(created_at) = CURDATE()");
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

$saleIdFilter = null;
$sales = [];
$totalSalesCount = 0;
$totalRevenue = 0.0;

if (isset($_GET['sale_id']) && trim($_GET['sale_id']) !== '') {
    $saleIdFilter = trim($_GET['sale_id']);
    $stmt = $pdo->prepare("
        SELECT sales.*, users.username 
        FROM sales 
        JOIN users ON sales.user_id = users.id
        WHERE DATE(sales.created_at) = CURDATE() AND sales.id = ?
        ORDER BY sales.created_at DESC
    ");
    $stmt->execute([$saleIdFilter]);
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare("
        SELECT sales.*, users.username 
        FROM sales 
        JOIN users ON sales.user_id = users.id
        WHERE DATE(sales.created_at) = CURDATE()
        ORDER BY sales.created_at DESC
    ");
    $stmt->execute();
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$totalSalesCount = count($sales);
foreach ($sales as $sale) {
    $totalRevenue += (float)$sale['total_amount'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Daily Sales Report - POS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #212529;
            line-height: 1.5;
        }
        .container {
            max-width: 900px;
            margin: 40px auto;
            position: relative;
        }
        .header-summary {
            background: #fff;
            padding: 2rem 2.5rem;
            border-radius: 14px;
            box-shadow: 0 8px 20px rgb(0 0 0 / 0.08);
            margin-bottom: 40px;
            text-align: center;
        }
        .header-summary h1 {
            font-weight: 700;
            font-size: 2rem;
            color: #343a40;
            margin-bottom: 0.75rem;
        }
        .summary-values {
            display: flex;
            justify-content: center;
            gap: 3rem;
            font-size: 1.2rem;
            color: #495057;
            user-select: none;
        }
        .summary-values div {
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }
        .summary-values svg {
            fill: #0d6efd;
            width: 26px;
            height: 26px;
        }
        .controls {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 2rem;
            justify-content: space-between;
            align-items: center;
        }
        .controls-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .search-form {
            flex-grow: 1;
            max-width: 280px;
        }
        .search-form input {
            font-size: 1rem;
            padding: 0.4rem 0.75rem;
        }
        .btn-print, .btn-back {
            white-space: nowrap;
        }
        /* Remove previous absolute positioning for back button */
        /*
        .btn-back {
            position: absolute;
            top: 0;
            left: 0;
            margin: 10px 0 20px 0;
            z-index: 10;
        }
        */
        .sale-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgb(0 0 0 / 0.07);
            margin-bottom: 30px;
            transition: box-shadow 0.3s ease;
        }
        .sale-card:hover {
            box-shadow: 0 12px 35px rgb(0 0 0 / 0.12);
        }
        .sale-header {
            background: #0d6efd;
            color: #fff;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
            padding: 1rem 1.5rem;
            font-weight: 600;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            font-size: 1rem;
            justify-content: space-between;
            user-select: none;
        }
        .sale-header div {
            white-space: nowrap;
        }
        .sale-body {
            padding: 1.2rem 1.5rem 1.8rem;
        }
        table.table {
            margin-bottom: 0;
        }
        table th, table td {
            vertical-align: middle !important;
        }
        .form-check {
            user-select: none;
        }
        @media (max-width: 575px) {
            .sale-header {
                flex-direction: column;
                gap: 0.3rem;
            }
            .summary-values {
                flex-direction: column;
                gap: 1rem;
            }
            .controls {
                justify-content: center;
            }
            .search-form {
                max-width: 100%;
                flex-grow: 1;
            }
        }
        @media print {
            body {
                background: white;
                color: #000;
            }
            .header-summary {
                box-shadow: none;
                margin-bottom: 20px;
                padding: 0;
                text-align: left;
            }
            .summary-values {
                justify-content: flex-start;
                gap: 2rem;
                font-size: 1rem;
                color: #000;
            }
            .controls {
                display: none !important;
            }
            .sale-card {
                box-shadow: none;
                margin-bottom: 15px;
                border: 1px solid #dee2e6;
            }
            .sale-header {
                background: #0d6efd;
                color: white;
                padding: 0.6rem 1rem;
                font-size: 0.95rem;
                justify-content: flex-start;
                gap: 1rem;
            }
            .sale-body {
                padding: 0.6rem 1rem 1rem;
            }
            table th, table td {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>

<div class="container" role="main">

    <header class="header-summary" role="banner" aria-label="Daily sales summary">
        <h1>Daily Sales Report — <?= date('F j, Y') ?></h1>
        <div class="summary-values" role="contentinfo">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" aria-hidden="true" focusable="false" role="img" aria-label="Sales count icon">
                    <path d="M3 2a1 1 0 0 1 1-1h1v2H4v10h8V3h-1V1h1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2z"/>
                </svg>
                <span><strong><?= $totalSalesCount ?></strong> Sale<?= $totalSalesCount !== 1 ? 's' : '' ?> Today</span>
            </div>
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" aria-hidden="true" focusable="false" role="img" aria-label="Revenue icon">
                    <path d="M8.5 14V13h-1v1a3 3 0 1 0 0-6h1v1h-1a2 2 0 1 1 0 4h1v1h-1a3 3 0 1 0 0-6h1v1h-1a2 2 0 1 1 0 4z"/>
                </svg>
                <span><strong>$<?= number_format($totalRevenue, 2) ?></strong> Total Revenue</span>
            </div>
        </div>
    </header>

    <div class="controls" role="search" aria-label="Search sales by ID and actions">

        <form method="get" class="search-form d-flex" novalidate>
            <input
                type="text"
                name="sale_id"
                class="form-control me-2"
                placeholder="Search by Sale ID"
                value="<?= htmlspecialchars($saleIdFilter ?? '', ENT_QUOTES, 'UTF-8') ?>"
                pattern="\d*"
                title="Please enter a valid Sale ID (numbers only)"
                autofocus
                aria-label="Search by Sale ID"
                inputmode="numeric"
                autocomplete="off"
            />
            <button type="submit" class="btn btn-primary">Search</button>
            <button type="button" class="btn btn-outline-secondary ms-2" onclick="window.location.href=window.location.pathname" aria-label="Clear search">
                Clear
            </button>
        </form>

        <div class="d-flex gap-2">
            <a href="../dashboard.php" class="btn btn-outline-primary btn-back" aria-label="Back to dashboard">
                &larr; Back to Dashboard
            </a>
            <button type="button" class="btn btn-outline-secondary btn-print" onclick="window.print()" aria-label="Print sales report">
                🖨️ Print
            </button>
        </div>

    </div>

    <form id="salesForm" method="post" novalidate>
        <input type="hidden" name="delete_sales" value="1" />
        <input type="hidden" name="select_all_records" id="selectAllRecordsInput" value="0" />

        <div class="controls-left align-items-center d-flex gap-3 mb-3" aria-label="Bulk actions">
            <button type="submit" class="btn btn-danger" id="deleteSelectedBtn" disabled aria-disabled="true" aria-label="Delete selected sales">
                Delete Selected
            </button>

            <div class="form-check ms-3">
                <input class="form-check-input" type="checkbox" id="selectAllVisibleCheckbox" aria-label="Select all visible sales" />
                <label class="form-check-label" for="selectAllVisibleCheckbox">Select All Visible</label>
            </div>

            <div class="form-check ms-3">
                <input class="form-check-input" type="checkbox" id="selectAllRecordsCheckbox" aria-label="Select all sales records" />
                <label class="form-check-label" for="selectAllRecordsCheckbox">Select All Records</label>
            </div>
        </div>

        <?php if (empty($sales)): ?>
            <p class="text-muted text-center">No sales found for today<?= $saleIdFilter ? " matching Sale ID " . htmlspecialchars($saleIdFilter) : "" ?>.</p>
        <?php else: ?>
            <?php foreach ($sales as $sale): ?>
                <article class="sale-card" role="article" aria-labelledby="sale-title-<?= $sale['id'] ?>">
                    <header class="sale-header">
                        <div>
                            <input
                                class="form-check-input sale-checkbox"
                                type="checkbox"
                                name="sale_ids[]"
                                value="<?= $sale['id'] ?>"
                                id="sale-checkbox-<?= $sale['id'] ?>"
                                aria-labelledby="sale-label-<?= $sale['id'] ?>"
                            />
                            <label class="form-check-label" for="sale-checkbox-<?= $sale['id'] ?>" id="sale-label-<?= $sale['id'] ?>">
                                Sale #<?= $sale['id'] ?>
                            </label>
                        </div>
                        <div>By: <strong><?= htmlspecialchars($sale['username']) ?></strong></div>
                        <div><?= date('h:i A', strtotime($sale['created_at'])) ?></div>
                        <div>Total: <strong>$<?= number_format($sale['total_amount'], 2) ?></strong></div>
                    </header>
                    <div class="sale-body">
                        <table class="table table-striped table-hover" aria-describedby="sale-title-<?= $sale['id'] ?>">
                            <thead>
                                <tr>
                                    <th scope="col">Product</th>
                                    <th scope="col" class="text-center">Qty</th>
                                    <th scope="col" class="text-end">Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmtItems = $pdo->prepare("
                                    SELECT sale_items.*, products.name 
                                    FROM sale_items 
                                    JOIN products ON sale_items.product_id = products.id 
                                    WHERE sale_items.sale_id = ?
                                ");
                                $stmtItems->execute([$sale['id']]);
                                $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
                                ?>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['name']) ?></td>
                                        <td class="text-center"><?= (int)$item['quantity'] ?></td>
                                        <td class="text-end">$<?= number_format($item['price'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </form>
</div>

<script>
    (() => {
        const form = document.getElementById('salesForm');
        const deleteBtn = document.getElementById('deleteSelectedBtn');
        const selectAllVisibleCheckbox = document.getElementById('selectAllVisibleCheckbox');
        const selectAllRecordsCheckbox = document.getElementById('selectAllRecordsCheckbox');
        const selectAllRecordsInput = document.getElementById('selectAllRecordsInput');
        const saleCheckboxes = () => [...document.querySelectorAll('.sale-checkbox')];

        // Update Delete button disabled state based on checkboxes
        function updateDeleteBtnState() {
            const anyChecked = saleCheckboxes().some(cb => cb.checked);
            deleteBtn.disabled = !anyChecked && selectAllRecordsCheckbox.checked === false;
            deleteBtn.setAttribute('aria-disabled', deleteBtn.disabled.toString());
        }

        // Handle Select All Visible toggle
        selectAllVisibleCheckbox.addEventListener('change', () => {
            const checked = selectAllVisibleCheckbox.checked;
            saleCheckboxes().forEach(cb => cb.checked = checked);
            // When visible selected, deselect 'Select All Records'
            if (checked) {
                selectAllRecordsCheckbox.checked = false;
                selectAllRecordsInput.value = '0';
            }
            updateDeleteBtnState();
        });

        // Handle Select All Records toggle
        selectAllRecordsCheckbox.addEventListener('change', () => {
            const checked = selectAllRecordsCheckbox.checked;
            if (checked) {
                // Deselect visible selects
                selectAllVisibleCheckbox.checked = false;
                saleCheckboxes().forEach(cb => cb.checked = false);
                selectAllRecordsInput.value = '1';
                deleteBtn.disabled = false;
                deleteBtn.setAttribute('aria-disabled', 'false');
            } else {
                selectAllRecordsInput.value = '0';
                updateDeleteBtnState();
            }
        });

        // Update 'Select All Visible' checkbox if user manually selects/deselects items
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

        // Initial button state
        updateDeleteBtnState();
    })();
</script>

</body>
</html>
