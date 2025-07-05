<?php
require '../includes/auth.php';
require '../includes/db.php';

checkLogin();

$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE DATE(created_at) = CURDATE()");
$stmt->execute();
$sales_today = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE DATE(created_at) = CURDATE()");
$stmt->execute();
$transactions_today = $stmt->fetchColumn();

$threshold = 5;
$stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE stock_quantity <= ?");
$stmt->execute([$threshold]);
$low_stock_count = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>POS Dashboard</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  /* Custom scrollbar for smoother look */
  body::-webkit-scrollbar {
    width: 8px;
  }
  body::-webkit-scrollbar-track {
    background: #f1f5f9;
  }
  body::-webkit-scrollbar-thumb {
    background-color: #3b82f6; /* Tailwind blue-500 */
    border-radius: 20px;
    border: 3px solid #f1f5f9;
  }
</style>
</head>
<body class="bg-gray-50 text-gray-800 font-sans min-h-screen flex flex-col">

<!-- Navbar -->
<nav class="bg-blue-700 text-white shadow-md">
  <div class="container mx-auto px-6 py-4 flex justify-between items-center">
    <a href="#" class="text-2xl font-extrabold tracking-wide">POS Dashboard</a>
    <button id="nav-toggle" class="block md:hidden focus:outline-none" aria-label="Toggle menu">
      <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M4 6h16M4 12h16M4 18h16"/>
      </svg>
    </button>
    <ul id="nav-menu" class="hidden md:flex space-x-6 text-sm font-medium items-center">
      <li><a href="../public/products/list.php" class="hover:text-blue-300 transition">Manage Products</a></li>
      <li><a href="../public/sales/new.php" class="hover:text-blue-300 transition">New Sale</a></li>
      <li><a href="../public/sales/list.php" class="hover:text-blue-300 transition">Sales History</a></li>
      <li><a href="../public/users/list.php" class="hover:text-blue-300 transition">User Management</a></li>
      <li><a href="../public/reports/daily_sales.php" class="hover:text-blue-300 transition">Sales Reports</a></li>
      <li><a href="../public/reports/stock_alerts.php" class="hover:text-blue-300 transition flex items-center relative">
        Stock Alerts
        <?php if ($low_stock_count > 0): ?>
          <span class="absolute -top-2 -right-3 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full animate-pulse">
            <?= $low_stock_count ?>
          </span>
        <?php endif; ?>
      </a></li>
      <li><a href="logout.php" class="hover:text-blue-300 transition">Logout</a></li>
    </ul>
  </div>
</nav>

<!-- Dashboard Cards -->
<main class="container mx-auto px-6 py-10 flex-grow">
  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-8">

    <!-- New Sale -->
    <a href="../public/sales/new.php" 
       class="group block bg-teal-600 rounded-xl shadow-lg p-6 hover:shadow-xl transform hover:-translate-y-1 transition relative" 
       aria-label="New Sale">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-white mb-4 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" >
        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"/>
        <line x1="12" y1="8" x2="12" y2="16" stroke="currentColor" stroke-linecap="round" stroke-width="2"/>
        <line x1="8" y1="12" x2="16" y2="12" stroke="currentColor" stroke-linecap="round" stroke-width="2"/>
      </svg>
      <h3 class="text-white text-xl font-semibold text-center">New Sale</h3>
    </a>

    <!-- Total Products -->
    <a href="../public/products/list.php" 
       class="group block bg-blue-600 rounded-xl shadow-lg p-6 hover:shadow-xl transform hover:-translate-y-1 transition relative"
       aria-label="Total Products">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-white mb-4 mx-auto" fill="currentColor" viewBox="0 0 16 16">
        <path d="M8.98.383a1 1 0 0 0-.96 0l-6 3.5A1 1 0 0 0 2 5.5v6a1 1 0 0 0 .54.894l6 3.5a1 1 0 0 0 .92 0l6-3.5A1 1 0 0 0 16 11.5v-6a1 1 0 0 0-.54-.894l-6-3.5zM8 1.615l5.684 3.313-5.684 3.313L2.316 4.928 8 1.615zM3 6v5.623L8 14.615l5-2.992V6H3z"/>
      </svg>
      <h3 class="text-white text-xl font-semibold text-center">Total Products</h3>
      <p class="mt-2 text-center text-white text-3xl font-bold"><?= $total_products ?></p>
    </a>

    <!-- Total Sales -->
    <a href="../public/reports/daily_sales.php" 
       class="group block bg-green-600 rounded-xl shadow-lg p-6 hover:shadow-xl transform hover:-translate-y-1 transition relative" 
       aria-label="Total Sales">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-white mb-4 mx-auto" fill="currentColor" viewBox="0 0 16 16">
        <path d="M8.5 14V13h-1v1a3 3 0 1 0 0-6h1v1h-1a2 2 0 1 1 0 4h1v1h-1a3 3 0 1 0 0-6h1v1h-1a2 2 0 1 1 0 4z"/>
      </svg>
      <h3 class="text-white text-xl font-semibold text-center">Total Sales ($)</h3>
      <p class="mt-2 text-center text-white text-3xl font-bold"><?= number_format($sales_today, 2) ?></p>
    </a>

    <!-- Transactions -->
    <a href="../public/sales/list.php" 
       class="group block bg-yellow-500 rounded-xl shadow-lg p-6 hover:shadow-xl transform hover:-translate-y-1 transition relative" 
       aria-label="Transactions">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-white mb-4 mx-auto" fill="currentColor" viewBox="0 0 16 16">
        <path fill-rule="evenodd" d="M0 0h1v15h15v1H0V0zm15 1v13H1V1h14zM4.5 10.5l1-2 2.5 3 3-5 1 1-3.5 6-3-3z"/>
      </svg>
      <h3 class="text-white text-xl font-semibold text-center">Transactions</h3>
      <p class="mt-2 text-center text-white text-3xl font-bold"><?= $transactions_today ?></p>
    </a>

    <!-- Low Stock Products -->
    <a href="../public/reports/stock_alerts.php" 
       class="group relative block bg-red-700 rounded-2xl shadow-xl p-6 hover:shadow-2xl transform hover:-translate-y-1 transition duration-300"
       aria-label="Low Stock Products">

      <?php if ($low_stock_count > 0): ?>
      <div class="absolute top-3 right-3 flex items-center justify-center w-10 h-10 bg-red-100 rounded-full shadow-lg animate-pulse ring-4 ring-red-400" title="Low stock alert">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 4h.01M4.93 19h14.14a2 2 0 001.64-3.12L13.87 5a2 2 0 00-3.74 0L3.3 15.88A2 2 0 004.93 19z" />
        </svg>
      </div>
      <?php endif; ?>

      <div class="mb-5 mx-auto w-14 h-14 rounded-full bg-red-600 bg-gradient-to-br from-red-500 to-red-700 flex items-center justify-center shadow-lg drop-shadow-lg">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-100 opacity-90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" >
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 4h.01M4.93 19h14.14a2 2 0 001.64-3.12L13.87 5a2 2 0 00-3.74 0L3.3 15.88A2 2 0 004.93 19z" />
        </svg>
      </div>

      <h3 class="text-white text-xl font-semibold text-center tracking-wide drop-shadow-sm">Low Stock Products</h3>
      <p class="mt-2 text-center text-white text-4xl font-extrabold drop-shadow-md"><?= $low_stock_count ?></p>
    </a>

  </div>
</main>

<!-- Footer -->
<footer class="bg-blue-700 text-white py-4 text-center text-sm">
  &copy; <?= date('Y') ?> POS Dashboard. All rights reserved.
</footer>

<script>
  // Toggle navigation menu on small screens
  const navToggle = document.getElementById('nav-toggle');
  const navMenu = document.getElementById('nav-menu');

  navToggle.addEventListener('click', () => {
    navMenu.classList.toggle('hidden');
  });
</script>
</body>
</html>
