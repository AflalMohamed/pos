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
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Sales History</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen font-sans p-8">

  <header class="mb-6 flex items-center justify-between max-w-7xl mx-auto">
    <h1 class="text-4xl font-extrabold text-gray-900 tracking-tight">Sales History</h1>
    <button
      onclick="history.back()"
      class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-md shadow-sm text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-gray-400"
      aria-label="Go back"
      type="button"
    >
      &larr; Back
    </button>
  </header>

  <main class="max-w-7xl mx-auto bg-white rounded-xl shadow-lg border border-gray-200 p-6">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 text-gray-800">
        <thead class="bg-gray-50 sticky top-0 z-10">
          <tr>
            <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-gray-700 uppercase tracking-wide">Sale ID</th>
            <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-gray-700 uppercase tracking-wide">Date</th>
            <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-gray-700 uppercase tracking-wide">Cashier</th>
            <th scope="col" class="px-6 py-4 text-right text-sm font-semibold text-gray-700 uppercase tracking-wide">Total</th>
            <th scope="col" class="px-6 py-4 text-center text-sm font-semibold text-gray-700 uppercase tracking-wide">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
          <?php if (count($sales) === 0): ?>
            <tr>
              <td colspan="5" class="px-6 py-6 text-center text-gray-400 italic text-lg">No sales records found.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($sales as $sale): ?>
              <tr class="hover:bg-indigo-50 focus-within:bg-indigo-100 transition duration-150">
                <td class="px-6 py-4 whitespace-nowrap font-medium text-indigo-700"><?= htmlspecialchars($sale['id']) ?></td>
                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars(date('Y-m-d H:i', strtotime($sale['created_at']))) ?></td>
                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($sale['username']) ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-right font-semibold text-gray-900">$<?= number_format($sale['total_amount'], 2) ?></td>
                <td class="px-6 py-4 whitespace-nowrap text-center space-x-3">
                  <a href="print_receipt.php?id=<?= $sale['id'] ?>" target="_blank"
                     class="inline-block px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md shadow hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1 transition">
                    View / Print
                  </a>
                  <a href="download_pdf.php?id=<?= $sale['id'] ?>" target="_blank"
                     class="inline-block px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md shadow hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-1 transition">
                    Download PDF
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>

</body>
</html>
