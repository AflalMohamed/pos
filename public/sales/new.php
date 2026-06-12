<?php 
require '../../includes/auth.php'; 
require '../../includes/db.php'; 

checkRole(['admin', 'cashier']);

$error = ''; 
$success = ''; 
$receipt = null; 

// Handle Rollback/Edit action if user triggers edit on a completed invoice
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'edit_invoice') {
    $target_sale_id = (int)$_POST['target_sale_id'];
    try {
        $pdo->beginTransaction();
        
        // 1. Fetch transaction items to reverse stock
        $stmt = $pdo->prepare("SELECT product_id, quantity FROM sale_items WHERE sale_id = ?");
        $stmt->execute([$target_sale_id]);
        $revert_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 2. Revert quantities back to original inventory stock
        $stmt_revert = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
        foreach ($revert_items as $r_item) {
            $stmt_revert->execute([$r_item['quantity'], $r_item['product_id']]);
        }
        
        // 3. Remove transaction rows from ledger records
        $stmt_del_items = $pdo->prepare("DELETE FROM sale_items WHERE sale_id = ?");
        $stmt_del_items->execute([$target_sale_id]);
        
        $stmt_del_sale = $pdo->prepare("DELETE FROM sales WHERE id = ?");
        $stmt_del_sale->execute([$target_sale_id]);
        
        $pdo->commit();
        $success = "Invoice items reverted successfully! You can modify selection and resubmit.";
        
        // Pre-fill fields logic mapping inside Javascript UI block
        $load_saved_items = $revert_items;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = "Failed to unlock invoice modification: " . $e->getMessage();
    }
}

$stmt = $pdo->query("SELECT * FROM products ORDER BY name ASC"); 
$products = $stmt->fetchAll(PDO::FETCH_ASSOC); 

// Product details array for Javascript calculation engine
$products_json = [];
foreach ($products as $p) {
    $products_json[$p['id']] = [
        'name' => $p['name'],
        'price' => (float)$p['price'],
        'stock' => (int)$p['stock_quantity']
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action_type'])) { 
    $items = $_POST['items'] ?? []; 
    $customer_payment = isset($_POST['customer_payment']) ? (float)$_POST['customer_payment'] : 0; 
    $discount_amount = isset($_POST['discount_amount']) ? (float)$_POST['discount_amount'] : 0;

    $subtotal_amount = 0; 
    $sale_items = []; 

    try { 
        $pdo->beginTransaction(); 

        foreach ($items as $product_id => $quantity) { 
            $quantity = (int)$quantity; 
            if ($quantity > 0) { 
                $stmt = $pdo->prepare("SELECT id, name, price, stock_quantity FROM products WHERE id = ?"); 
                $stmt->execute([$product_id]); 
                $product = $stmt->fetch(PDO::FETCH_ASSOC); 

                if (!$product) throw new Exception("Product ID $product_id not found.");
                if ($quantity > $product['stock_quantity']) throw new Exception("Not enough stock for {$product['name']}.");

                $line_total = $product['price'] * $quantity; 
                $subtotal_amount += $line_total; 
                $sale_items[] = [ 
                    'product_id' => $product['id'], 
                    'name' => $product['name'], 
                    'quantity' => $quantity, 
                    'price' => $product['price'], 
                    'line_total' => $line_total 
                ]; 
            } 
        } 

        if ($subtotal_amount <= 0) throw new Exception("Please select at least one item.");
        
        $final_payable_amount = $subtotal_amount - $discount_amount;
        if ($final_payable_amount < 0) $final_payable_amount = 0;

        if ($customer_payment < $final_payable_amount) throw new Exception("Insufficient payment amount.");

        $balance = $customer_payment - $final_payable_amount; 
        
        // Updated query to save discount metadata matrix directly if database column exists
        $stmt = $pdo->prepare("INSERT INTO sales (user_id, total_amount, discount_amount, created_at) VALUES (?, ?, ?, NOW())"); 
        $stmt->execute([$_SESSION['user_id'], $final_payable_amount, $discount_amount]); 
        $sale_id = $pdo->lastInsertId(); 

        $stmt_insert_item = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, price) VALUES (?, ?, ?, ?)"); 
        $stmt_update_stock = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?"); 

        foreach ($sale_items as $item) { 
            $stmt_insert_item->execute([$sale_id, $item['product_id'], $item['quantity'], $item['price']]); 
            $stmt_update_stock->execute([$item['quantity'], $item['product_id']]); 
        } 

        $pdo->commit(); 
        $success = "Transaction processing successful!"; 
        $receipt = [ 
            'sale_id' => $sale_id, 
            'date' => date('d M Y, h:i A'), 
            'items' => $sale_items, 
            'subtotal' => $subtotal_amount,
            'discount' => $discount_amount,
            'total_amount' => $final_payable_amount, 
            'customer_payment' => $customer_payment, 
            'balance' => $balance 
        ]; 
        
        // Reload matrix metrics
        $stmt = $pdo->query("SELECT * FROM products ORDER BY name ASC"); 
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC); 
        foreach ($products as $p) {
            $products_json[$p['id']] = ['name' => $p['name'], 'price' => (float)$p['price'], 'stock' => (int)$p['stock_quantity']];
        }
    } catch (Exception $e) { 
        if ($pdo->inTransaction()) $pdo->rollBack(); 
        $error = $e->getMessage(); 
    } 
} 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Terminal Systems</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        .product-card:hover { transform: translateY(-4px); transition: all 0.2s ease; }
        @media print {
            .no-print { display: none !important; }
            #receipt-box { position: absolute; left: 0; top: 0; width: 100%; border: none; box-shadow: none; }
        }
        input[type=number]::-webkit-inner-spin-button, 
        input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
    </style>
</head>
<body class="min-h-screen">

    <nav class="bg-white shadow-sm border-b sticky top-0 z-50 no-print">
        <div class="max-w-[1400px] mx-auto px-4 h-16 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="../dashboard.php" class="p-2 hover:bg-gray-100 rounded-full transition-colors group" title="Back to Dashboard">
                    <svg class="w-6 h-6 text-gray-600 group-hover:text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <h1 class="text-xl font-bold text-gray-800">POS <span class="text-indigo-600 tracking-tight">Terminal</span></h1>
            </div>
            <div class="hidden md:block w-96">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-width="2"></path></svg>
                    </span>
                    <input type="text" id="product-search" placeholder="Search by name..." onkeyup="filterProducts()" 
                        class="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-gray-50">
                </div>
            </div>
            <div class="flex items-center text-sm font-medium text-gray-500">
                <span class="mr-2">User:</span>
                <span class="text-gray-900 bg-gray-100 px-3 py-1 rounded-full"><?= htmlspecialchars($_SESSION['username'] ?? 'Staff') ?></span>
            </div>
        </div>
    </nav>

    <div class="max-w-[1400px] mx-auto p-4 lg:p-6">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <div class="lg:col-span-8 no-print">
                <?php if ($error): ?>
                    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center gap-3">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" /></svg>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center gap-3">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" /></svg>
                        <span><?= htmlspecialchars($success) ?></span>
                    </div>
                <?php endif; ?>

                <form id="pos-main-form" method="POST">
                    <div id="product-grid" class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
                        <?php foreach ($products as $product): ?>
                            <div class="product-box bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col product-card">
                                <div class="h-32 bg-gray-50 flex items-center justify-center relative">
                                    <?php if ($product['image_data']): ?>
                                        <img src="data:<?= $product['image_type'] ?>;base64,<?= base64_encode($product['image_data']) ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-width="2"></path></svg>
                                    <?php endif; ?>
                                    
                                    <div class="absolute top-2 right-2 bg-black/60 backdrop-blur-sm text-white text-[10px] px-2 py-0.5 rounded font-bold uppercase">
                                        Stock: <?= $product['stock_quantity'] ?>
                                    </div>
                                </div>
                                <div class="p-3 flex flex-col flex-grow">
                                    <h3 class="text-sm font-semibold text-gray-800 mb-1 line-clamp-1"><?= htmlspecialchars($product['name']) ?></h3>
                                    <p class="text-indigo-600 font-bold text-sm mb-3 font-mono">Rs<?= number_format($product['price'], 2) ?></p>
                                    
                                    <div class="mt-auto flex items-center bg-gray-100 rounded-lg p-1">
                                        <button type="button" onclick="changeQty('p-<?= $product['id'] ?>', -1)" class="w-8 h-8 flex items-center justify-center text-gray-500 hover:bg-white rounded shadow-sm transition-all">-</button>
                                        <input type="number" id="p-<?= $product['id'] ?>" name="items[<?= $product['id'] ?>]" value="0" min="0" max="<?= $product['stock_quantity'] ?>" onchange="updateDemoReceipt()" onkeyup="updateDemoReceipt()"
                                            class="w-full bg-transparent text-center text-sm font-bold border-none focus:ring-0">
                                        <button type="button" onclick="changeQty('p-<?= $product['id'] ?>', 1, <?= $product['stock_quantity'] ?>)" class="w-8 h-8 flex items-center justify-center text-gray-500 hover:bg-white rounded shadow-sm transition-all">+</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="customer_payment" id="final_payment_val">
                    <input type="hidden" name="discount_amount" id="final_discount_val">
                </form>
            </div>

            <div class="lg:col-span-4 space-y-6">
                
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 no-print space-y-5">
                    <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2 border-b pb-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        Order Checkout
                    </h2>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-400 uppercase mb-1 tracking-wide">Discount Setup</label>
                            <select id="discount_type" onchange="updateDemoReceipt()" class="w-full bg-gray-50 border border-gray-200 p-2.5 rounded-lg text-sm font-medium focus:ring-2 focus:ring-indigo-500 outline-none text-gray-700">
                                <option value="flat">Flat Cash (Rs)</option>
                                <option value="percent">Percentage (%)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-400 uppercase mb-1 tracking-wide">Rate / Amount</label>
                            <input type="number" id="discount_rate_value" step="any" min="0" value="0" oninput="updateDemoReceipt()"
                                class="w-full bg-gray-50 border border-gray-200 p-2 rounded-lg text-sm font-mono font-bold text-right focus:ring-2 focus:ring-indigo-500 outline-none text-gray-800">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-2 tracking-wide">Enter Cash Received</label>
                        <input type="number" id="payment_ui_field" step="0.01" placeholder="0.00" oninput="updateDemoReceipt()"
                            class="w-full text-2xl font-mono font-bold bg-gray-50 border border-gray-200 p-4 rounded-xl focus:ring-4 focus:ring-indigo-100 outline-none transition-all text-gray-800">
                    </div>
                    
                    <button onclick="submitPosForm()" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 rounded-xl shadow-lg shadow-indigo-100 transition-all flex items-center justify-center gap-2">
                        <span>Process Sale</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M14 5l7 7m0 0l-7 7m7-7H3" stroke-width="2"></path></svg>
                    </button>
                </div>

                <div id="demo-receipt-box" class="bg-amber-50/60 rounded-2xl border-2 border-dashed border-amber-300 overflow-hidden no-print hidden">
                    <div class="p-6">
                        <div class="text-center mb-4 border-b border-amber-200 pb-2">
                            <h3 class="font-bold text-amber-800 text-sm tracking-wide uppercase">LIVE BILL PREVIEW (DEMO)</h3>
                        </div>
                        <div id="demo-items-list" class="space-y-2 mb-4"></div>
                        
                        <div class="space-y-1.5 border-t border-amber-200 pt-3 text-sm">
                            <div class="flex justify-between text-gray-600">
                                <span>Subtotal Basket:</span>
                                <span class="font-mono font-semibold text-gray-900" id="demo-subtotal">Rs0.00</span>
                            </div>
                            <div class="flex justify-between text-red-600 font-medium">
                                <span>Discount Deduction:</span>
                                <span class="font-mono font-semibold" id="demo-discount">-Rs0.00</span>
                            </div>
                            <div class="flex justify-between text-gray-800 font-bold border-b border-dotted pb-1 mb-1">
                                <span>Net Total Payable:</span>
                                <span class="font-mono text-gray-950" id="demo-total">Rs0.00</span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Cash Received:</span>
                                <span class="font-mono font-semibold text-gray-900" id="demo-cash">Rs0.00</span>
                            </div>
                            <div class="flex justify-between items-center text-amber-700 font-bold text-md pt-1">
                                <span>Change Return:</span>
                                <span class="font-mono text-lg" id="demo-balance">Rs0.00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($receipt): ?>
                    <div id="receipt-box" class="bg-white rounded-2xl shadow-xl border border-indigo-100 overflow-hidden">
                        <div class="p-6">
                            <div class="no-print mb-4 flex justify-end">
                                <form method="POST" onsubmit="return confirm('Unlock invoice for editing? This will safely revert product stock counts.');">
                                    <input type="hidden" name="action_type" value="edit_invoice">
                                    <input type="hidden" name="target_sale_id" value="<?= $receipt['sale_id'] ?>">
                                    <button type="submit" class="bg-amber-100 hover:bg-amber-200 border border-amber-300 text-amber-800 font-semibold px-3 py-1.5 rounded-lg text-xs flex items-center gap-1.5 transition-colors">
                                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        Modify or Return Bill
                                    </button>
                                </form>
                            </div>

                            <div class="text-center mb-6 border-b border-dashed pb-4">
                                <h3 class="font-black text-gray-900 text-xl">STORE RECEIPT</h3>
                                <p class="text-gray-400 text-xs mt-1 italic"><?= $receipt['date'] ?></p>
                            </div>
                            
                            <div class="space-y-3 mb-6">
                                <?php foreach ($receipt['items'] as $item): ?>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600 font-medium"><?= htmlspecialchars($item['name']) ?> <span class="text-xs text-gray-400">x<?= $item['quantity'] ?></span></span>
                                        <span class="font-mono font-bold text-gray-900">Rs<?= number_format($item['line_total'], 2) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="space-y-2 border-t pt-4">
                                <div class="flex justify-between text-gray-500 font-medium">
                                    <span>Subtotal</span>
                                    <span class="font-mono">Rs<?= number_format($receipt['subtotal'], 2) ?></span>
                                </div>
                                <div class="flex justify-between text-red-600 font-medium">
                                    <span>Discount Added</span>
                                    <span class="font-mono">-Rs<?= number_format($receipt['discount'], 2) ?></span>
                                </div>
                                <div class="flex justify-between text-gray-800 font-bold border-t border-dotted pt-1">
                                    <span>Net Total Amount</span>
                                    <span class="font-mono">Rs<?= number_format($receipt['total_amount'], 2) ?></span>
                                </div>
                                <div class="flex justify-between text-gray-500 font-medium">
                                    <span>Cash Tendered</span>
                                    <span class="font-mono">Rs<?= number_format($receipt['customer_payment'], 2) ?></span>
                                </div>
                                <div class="flex justify-between items-center text-indigo-600 font-bold text-lg border-t pt-2 mt-1">
                                    <span>BALANCE</span>
                                    <span class="font-mono">Rs<?= number_format($receipt['balance'], 2) ?></span>
                                </div>
                            </div>

                            <button onclick="window.print()" class="no-print mt-6 w-full py-2 bg-gray-900 text-white rounded-lg font-semibold hover:bg-black transition-colors flex items-center justify-center gap-2 text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" stroke-width="2"></path></svg>
                                Print Receipt
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <script>
        const productsMaster = <?= json_encode($products_json) ?>;
        let activeCalculatedDiscountGlobal = 0;

        window.addEventListener('DOMContentLoaded', () => {
            <?php if (isset($load_saved_items)): ?>
                const savedMatrix = <?= json_encode($load_saved_items) ?>;
                savedMatrix.forEach(record => {
                    const targetInput = document.getElementById(`p-${record.product_id}`);
                    if (targetInput) {
                        targetInput.value = record.quantity;
                    }
                });
                updateDemoReceipt();
            <?php endif; ?>
        });

        function changeQty(id, delta, max) {
            const el = document.getElementById(id);
            let val = parseInt(el.value) || 0;
            let newVal = val + delta;
            
            if (newVal >= 0 && (!max || newVal <= max)) {
                el.value = newVal;
                updateDemoReceipt();
            }
        }

        function updateDemoReceipt() {
            const demoBox = document.getElementById('demo-receipt-box');
            const listContainer = document.getElementById('demo-items-list');
            listContainer.innerHTML = '';
            
            let subtotalAmount = 0;
            let hasItems = false;

            for (const id in productsMaster) {
                const inputEl = document.getElementById(`p-${id}`);
                if (inputEl) {
                    const qty = parseInt(inputEl.value) || 0;
                    if (qty > 0) {
                        hasItems = true;
                        const p = productsMaster[id];
                        const lineTotal = p.price * qty;
                        subtotalAmount += lineTotal;

                        const itemRow = document.createElement('div');
                        itemRow.className = 'flex justify-between text-xs text-gray-700';
                        itemRow.innerHTML = `<span>${p.name} <b class="text-amber-700">x${qty}</b></span> <span>Rs${lineTotal.toFixed(2)}</span>`;
                        listContainer.appendChild(itemRow);
                    }
                }
            }

            // Calculation Matrix logic for Percent vs Flat Cash discounts configuration mappings
            const discountType = document.getElementById('discount_type').value;
            const discountRateRaw = parseFloat(document.getElementById('discount_rate_value').value) || 0;
            let dynamicDiscountComputed = 0;

            if (discountType === 'percent') {
                dynamicDiscountComputed = (subtotalAmount * discountRateRaw) / 100;
            } else {
                dynamicDiscountComputed = discountRateRaw;
            }

            if (dynamicDiscountComputed > subtotalAmount) {
                dynamicDiscountComputed = subtotalAmount;
            }
            activeCalculatedDiscountGlobal = dynamicDiscountComputed;

            let netTotalPayable = subtotalAmount - dynamicDiscountComputed;
            if (netTotalPayable < 0) netTotalPayable = 0;

            const rawCash = parseFloat(document.getElementById('payment_ui_field').value) || 0;
            let balance = rawCash - netTotalPayable;
            if (balance < 0) balance = 0;

            document.getElementById('demo-subtotal').innerText = `Rs${subtotalAmount.toFixed(2)}`;
            document.getElementById('demo-discount').innerText = `-Rs${dynamicDiscountComputed.toFixed(2)}`;
            document.getElementById('demo-total').innerText = `Rs${netTotalPayable.toFixed(2)}`;
            document.getElementById('demo-cash').innerText = `Rs${rawCash.toFixed(2)}`;
            document.getElementById('demo-balance').innerText = `Rs${balance.toFixed(2)}`;

            if (hasItems) {
                demoBox.classList.remove('hidden');
            } else {
                demoBox.classList.add('hidden');
            }
        }

        function filterProducts() {
            const input = document.getElementById('product-search').value.toLowerCase();
            const cards = document.querySelectorAll('.product-box');
            
            cards.forEach(card => {
                const title = card.querySelector('h3').innerText.toLowerCase();
                card.style.display = title.includes(input) ? 'flex' : 'none';
            });
        }

        function submitPosForm() {
            const paymentInput = document.getElementById('payment_ui_field').value;
            if (!paymentInput || paymentInput <= 0) {
                alert("Please enter a valid customer payment amount!");
                return;
            }
            document.getElementById('final_payment_val').value = paymentInput;
            document.getElementById('final_discount_val').value = activeCalculatedDiscountGlobal;
            document.getElementById('pos-main-form').submit();
        }
    </script>
</body>
</html>