<?php
require '../../includes/auth.php';
require '../../includes/db.php';
require '../../vendor/autoload.php'; // Load Composer dependencies

use Mpdf\Mpdf;

checkRole(['admin', 'cashier']);

if (!isset($_GET['id'])) {
    die('Sale ID is required.');
}

$sale_id = intval($_GET['id']);

// Fetch sale
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

// Fetch items
$itemsStmt = $pdo->prepare("
  SELECT si.quantity, p.name, p.price
  FROM sale_items si
  JOIN products p ON si.product_id = p.id
  WHERE si.sale_id = ?
");
$itemsStmt->execute([$sale_id]);
$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

// Build HTML
$html = "<h2>MY SHOP</h2>";
$html .= "<p>Date: {$sale['created_at']}<br> Cashier: " . htmlspecialchars($sale['username']) . "<br> Sale ID: {$sale_id}</p>";
$html .= "<table border='1' cellpadding='5' cellspacing='0' width='100%'>
  <thead>
    <tr><th>Item</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr>
  </thead><tbody>";

foreach ($items as $item) {
  $subtotal = $item['price'] * $item['quantity'];
  $html .= "<tr>
    <td>" . htmlspecialchars($item['name']) . "</td>
    <td>{$item['quantity']}</td>
    <td>$" . number_format($item['price'], 2) . "</td>
    <td>$" . number_format($subtotal, 2) . "</td>
  </tr>";
}

$html .= "</tbody></table>";
$html .= "<h4>Total: $" . number_format($sale['total_amount'], 2) . "</h4>";

// Generate PDF
$mpdf = new Mpdf();
$mpdf->WriteHTML($html);
$mpdf->Output("Receipt_Sale_{$sale_id}.pdf", 'D'); // D = force download
