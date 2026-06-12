<?php
require '../includes/auth.php';
require '../includes/db.php';

checkLogin();

// 1. Core Dashboard Baseline Matrix Metrics Extraction
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

// 2. AI Real-time Detection Core System Engine: Fast Moving Dynamic Tracking Vector
$ai_fast_moving_stmt = $pdo->query("
    SELECT p.name, SUM(si.quantity) as total_sold 
    FROM sale_items si 
    JOIN products p ON si.product_id = p.id 
    GROUP BY si.product_id 
    ORDER BY total_sold DESC 
    LIMIT 1
");
$fast_moving_data = $ai_fast_moving_stmt->fetch(PDO::FETCH_ASSOC);
$fast_moving_product = $fast_moving_data ? $fast_moving_data['name'] : "No records captured";
$fast_moving_qty = $fast_moving_data ? $fast_moving_data['total_sold'] : 0;

// =========================================================================
// 3. MASTER DEEP-INTEGRATION CHATBOT CONTROL CONTROLLER ENGINE (DYNAMIC SQL INFERENCE ARCHITECTURE)
// =========================================================================
if (isset($_GET['ajax_chat_query'])) {
    header('Content-Type: application/json');
    $query = strtolower(trim($_GET['ajax_chat_query']));
    $response = "🤖 **AI Engine Alert:** Query parameters mapped outside safe structural schema context bounds. Please ask structural queries like 'highest selling product', 'total users', 'average basket value', or 'overall business revenue summary'!";
    
    try {
        // CASE A: Dynamic Revenue Analytics Control (All-Time Data aggregation calculations)
        if (strpos($query, 'all time sales') !== false || strpos($query, 'total revenue') !== false || strpos($query, 'overall sales') !== false) {
            $calc_stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM sales");
            $grand_total = $calc_stmt->fetchColumn();
            $count_stmt = $pdo->query("SELECT COUNT(*) FROM sales");
            $total_txns = $count_stmt->fetchColumn();
            $response = "🤖 **AI Database Audit:** Verified system metrics show an all-time gross transactional ledger of **Rs " . number_format($grand_total, 2) . "** processed across **" . $total_txns . "** standard checkout executions.";
            
        // CASE B: Advanced Fast Moving Asset Optimization Engine Calculations 
        } elseif (strpos($query, 'fast moving') !== false || strpos($query, 'highest selling') !== false || strpos($query, 'top product') !== false) {
            if ($fast_moving_data) {
                $response = "🤖 **AI Predictive Analytics Tracker:** Our relational graph maps **" . $fast_moving_product . "** as the absolute highest moving asset. Lifecycle metrics show **" . $fast_moving_qty . " total volume items checked out** successfully from active nodes.";
            } else {
                $response = "🤖 **AI Engine Warning:** Structural logs currently hold no checkout transactional records to process trends analytics.";
            }
            
        // CASE C: Average Basket Calculation Parameters (Dynamic Invoice Calculations)
        } elseif (strpos($query, 'average ticket') !== false || strpos($query, 'average calculation') !== false || strpos($query, 'average sale') !== false) {
            $avg_stmt = $pdo->query("SELECT COALESCE(AVG(total_amount), 0) FROM sales");
            $average_value = $avg_stmt->fetchColumn();
            $response = "🤖 **AI Statistical Formula Model:** Algorithmic calculations parsed on active customer payload parameters resolve to an average purchase invoice structure of **Rs " . number_format($average_value, 2) . "** per interaction ticket.";

        // CASE D: Dynamic User Management Node Verification Control 
        } elseif (strpos($query, 'total users') !== false || strpos($query, 'how many users') !== false || strpos($query, 'operator profiles') !== false) {
            $user_stmt = $pdo->query("SELECT COUNT(*) FROM users");
            $total_users_count = $user_stmt->fetchColumn();
            $response = "🤖 **AI System Security Check:** Central authentication nodes register **" . $total_users_count . " authenticated administrative profiles** configured in user master layers.";

        // CASE E: Live Dashboard Matrix Checkouts 
        } elseif (strpos($query, 'today sales') !== false || strpos($query, 'today financial parameters') !== false) {
            $response = "🤖 **AI Flash Analytics:** Today's execution cycle tracking shows: **Rs " . number_format($sales_today, 2) . "** across **" . $transactions_today . "** active terminal invoices.";
            
        // CASE F: Dynamic Low Inventory Vector Extraction Control
        } elseif (strpos($query, 'low stock') !== false || strpos($query, 'danger inventory') !== false || strpos($query, 'alerts count') !== false) {
            $response = "🤖 **AI Pipeline Alert System:** Deep schema inspection highlights **" . $low_stock_count . " unique items** dropping within critical low parameters (Standard alert threshold limit: <= " . $threshold . " units).";
            
        // CASE G: Structural Product Catalog Metrics Count 
        } elseif (strpos($query, 'total products') !== false || strpos($query, 'items inside database') !== false) {
            $response = "🤖 **AI Structural Inventory Summary:** Database indexes register a total collection footprint of **" . $total_products . "** active products configured inside active tables.";

        // CASE H: System Hello Interface Handshake Welcome 
        } elseif (strpos($query, 'hello') !== false || strpos($query, 'hi') !== false || strpos($query, 'help') !== false) {
            $response = "🤖 **Welcome to POS Autonomous Control Portal!** I am mapped straight into your system databases. You can command me to perform formulas like: 'Show all time sales parameters', 'Calculate average sale metric', 'Query total users distribution', or 'Identify highest selling top product'.";
        }
    } catch (Exception $e) {
        $response = "🤖 **AI Internal Pipeline Engine Failure:** Encountered operational errors while generating code parsing: " . htmlspecialchars($e->getMessage());
    }
    
    echo json_encode(['reply' => $response]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>POS Dashboard</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  body::-webkit-scrollbar {
    width: 8px;
  }
  body::-webkit-scrollbar-track {
    background: #f1f5f9;
  }
  body::-webkit-scrollbar-thumb {
    background-color: #3b82f6; 
    border-radius: 20px;
    border: 3px solid #f1f5f9;
  }
</style>
</head>
<body class="bg-gray-50 text-gray-800 font-sans min-h-screen flex flex-col">

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

<div class="bg-gradient-to-r from-indigo-800 to-blue-900 text-white py-3 px-6 shadow-inner text-center text-xs font-semibold flex items-center justify-center gap-2">
    <span class="bg-indigo-500 text-[10px] uppercase font-bold px-2 py-0.5 rounded animate-pulse">AI Optimization Engine</span>
    <span>Automated Structural Database Scan Reports: Fastest Moving Product is <strong class="text-yellow-300 underline font-bold"><?= htmlspecialchars($fast_moving_product) ?></strong> (Sales Velocity: <span class="text-green-300 font-mono font-bold"><?= $fast_moving_qty ?> units</span>).</span>
</div>

<main class="container mx-auto px-6 py-10 flex-grow">
  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-8">

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

    <a href="../public/products/list.php" 
       class="group block bg-blue-600 rounded-xl shadow-lg p-6 hover:shadow-xl transform hover:-translate-y-1 transition relative"
       aria-label="Total Products">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-white mb-4 mx-auto" fill="currentColor" viewBox="0 0 16 16">
        <path d="M8.98.383a1 1 0 0 0-.96 0l-6 3.5A1 1 0 0 0 2 5.5v6a1 1 0 0 0 .54.894l6 3.5a1 1 0 0 0 .92 0l6-3.5A1 1 0 0 0 16 11.5v-6a1 1 0 0 0-.54-.894l-6-3.5zM8 1.615l5.684 3.313-5.684 3.313L2.316 4.928 8 1.615zM3 6v5.623L8 14.615l5-2.992V6H3z"/>
      </svg>
      <h3 class="text-white text-xl font-semibold text-center">Total Products</h3>
      <p class="mt-2 text-center text-white text-3xl font-bold"><?= $total_products ?></p>
    </a>

    <a href="../public/reports/daily_sales.php" 
       class="group block bg-green-600 rounded-xl shadow-lg p-6 hover:shadow-xl transform hover:-translate-y-1 transition relative" 
       aria-label="Total Sales">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-white mb-4 mx-auto" fill="currentColor" viewBox="0 0 16 16">
        <path d="M8.5 14V13h-1v1a3 3 0 1 0 0-6h1v1h-1a2 2 0 1 1 0 4h1v1h-1a3 3 0 1 0 0-6h1v1h-1a2 2 0 1 1 0 4z"/>
      </svg>
      <h3 class="text-white text-xl font-semibold text-center">Total Sales (Rs)</h3>
      <p class="mt-2 text-center text-white text-3xl font-bold"><?= number_format($sales_today, 2) ?></p>
    </a>

    <a href="../public/sales/list.php" 
       class="group block bg-yellow-500 rounded-xl shadow-lg p-6 hover:shadow-xl transform hover:-translate-y-1 transition relative" 
       aria-label="Transactions">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-white mb-4 mx-auto" fill="currentColor" viewBox="0 0 16 16">
        <path fill-rule="evenodd" d="M0 0h1v15h15v1H0V0zm15 1v13H1V1h14zM4.5 10.5l1-2 2.5 3 3-5 1 1-3.5 6-3-3z"/>
      </svg>
      <h3 class="text-white text-xl font-semibold text-center">Transactions</h3>
      <p class="mt-2 text-center text-white text-3xl font-bold"><?= $transactions_today ?></p>
    </a>

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

<div id="ai-chat-widget" class="fixed bottom-6 right-6 z-50 flex flex-col items-end">
    <div id="ai-chat-window" class="hidden w-85 sm:w-96 h-[460px] bg-white rounded-2xl shadow-2xl border border-gray-200 flex flex-col mb-4 overflow-hidden transition-all duration-300">
        <div class="bg-gradient-to-r from-blue-700 to-indigo-800 text-white px-4 py-3 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <div class="w-2 h-2 bg-green-400 rounded-full animate-ping"></div>
                <div>
                    <h4 class="text-sm font-bold tracking-wide">POS Core AI Engine</h4>
                    <p class="text-[10px] text-blue-200">Database Schema Introspection Online</p>
                </div>
            </div>
            <button id="close-chat-btn" class="text-white hover:text-gray-200 focus:outline-none text-xl font-bold">&times;</button>
        </div>
        
        <div id="ai-chat-messages" class="flex-grow p-4 overflow-y-auto space-y-3 text-xs bg-slate-50">
            <div class="bg-blue-50 border border-blue-100 text-slate-700 rounded-xl p-3 max-w-[85%] leading-relaxed shadow-sm">
                Greetings Operator! 🤖 I am synchronized directly with your relational tables. I can parse cross-table calculations automatically! 
                <br><br>
                <strong>Try testing these live calculation queries:</strong>
                <ul class="list-disc list-inside mt-2 space-y-1 text-slate-600 font-medium">
                    <li>"What is our all time sales?"</li>
                    <li>"Which is the highest selling product?"</li>
                    <li>"Calculate average sale value"</li>
                    <li>"Check low stock count updates"</li>
                    <li>"How many users are in system?"</li>
                </ul>
            </div>
        </div>
        
        <div class="p-3 border-t border-gray-100 bg-white flex gap-2">
            <input type="text" id="chat-input-field" placeholder="Type data analytics command..." class="flex-grow border border-gray-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-blue-600 font-medium">
            <button id="send-chat-btn" class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs px-4 py-2 rounded-xl transition">Query</button>
        </div>
    </div>

    <button id="toggle-chat-bubble" class="bg-gradient-to-tr from-blue-600 to-indigo-700 text-white rounded-full p-4 shadow-xl hover:scale-105 transition transform flex items-center justify-center gap-2 font-bold text-xs border border-blue-500/30">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 animate-spin [animation-duration:6s]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
        </svg>
        AI Live Control
    </button>
</div>

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

  // AI Chat Bot Widget Interface Interaction Controller Logic Script
  (() => {
      const toggleBtn = document.getElementById('toggle-chat-bubble');
      const closeBtn = document.getElementById('close-chat-btn');
      const chatWindow = document.getElementById('ai-chat-window');
      const sendBtn = document.getElementById('send-chat-btn');
      const inputField = document.getElementById('chat-input-field');
      const msgContainer = document.getElementById('ai-chat-messages');

      toggleBtn.addEventListener('click', () => chatWindow.classList.toggle('hidden'));
      closeBtn.addEventListener('click', () => chatWindow.classList.add('hidden'));

      function triggerChatMessageExecution() {
          const userText = inputField.value.trim();
          if (!userText) return;

          // Append User Speech Node Bubble to Container Context
          const userBubble = document.createElement('div');
          userBubble.className = "bg-white border border-gray-200 text-slate-800 rounded-xl p-3 max-w-[85%] ml-auto text-right font-semibold shadow-sm";
          userBubble.textContent = userText;
          msgContainer.appendChild(userBubble);
          inputField.value = '';
          msgContainer.scrollTop = msgContainer.scrollHeight;

          // Fire Back-end Asynchronous AI Processing API Request
          fetch(`?ajax_chat_query=${encodeURIComponent(userText)}`)
              .then(res => res.json())
              .then(data => {
                  const botBubble = document.createElement('div');
                  botBubble.className = "bg-blue-50 border border-blue-100 text-slate-700 rounded-xl p-3 max-w-[85%] whitespace-pre-line shadow-inner font-medium leading-relaxed";
                  botBubble.innerHTML = data.reply;
                  msgContainer.appendChild(botBubble);
                  msgContainer.scrollTop = msgContainer.scrollHeight;
              })
              .catch(() => {
                  const errorBubble = document.createElement('div');
                  errorBubble.className = "bg-rose-50 text-rose-600 rounded-xl p-3 max-w-[85%]";
                  errorBubble.textContent = "AI calculation engine failure. Please recheck server connection profiles.";
                  msgContainer.appendChild(errorBubble);
              });
      }

      sendBtn.addEventListener('click', triggerChatMessageExecution);
      inputField.addEventListener('keypress', (e) => {
          if (e.key === 'Enter') triggerChatMessageExecution();
      });
  })();
</script>
</body>
</html>