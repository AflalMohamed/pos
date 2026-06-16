<?php
require '../includes/auth.php';
require '../includes/db.php';

checkLogin();

// 1. Core Dashboard Metrics Extraction
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

// Fast Moving Product Tracking
$ai_fast_moving_stmt = $pdo->query("
    SELECT p.name, SUM(si.quantity) as total_sold 
    FROM sale_items si 
    JOIN products p ON si.product_id = p.id 
    GROUP BY si.product_id 
    ORDER BY total_sold DESC 
    LIMIT 1
");
$fast_moving_data = $ai_fast_moving_stmt->fetch(PDO::FETCH_ASSOC);
$fast_moving_product = $fast_moving_data ? $fast_moving_data['name'] : "No records";
$fast_moving_qty = $fast_moving_data ? $fast_moving_data['total_sold'] : 0;

// All-time metrics for Gemini analysis
$grand_total_sales = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM sales")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// =========================================================================
// 2. GOOGLE GEMINI AI MULTI-MODEL FALLBACK INTEGRATION
// =========================================================================
if (isset($_GET['ajax_chat_query'])) {
    header('Content-Type: application/json');
    $user_query = trim($_GET['ajax_chat_query']);
    
    // ⚠️ உங்களது சரியான Gemini API Key-ஐ இங்கே கொடுக்கவும்
    $gemini_api_key = 'your gemini api here '; 
    
    // System Context Setup
    $system_context = "You are a friendly, highly intelligent human store assistant for a POS system dashboard. 
    You are talking directly to the Admin. Use a helpful, professional yet warm human tone. 
    Here is the live store data from the database to answer any calculation or report requests:
    - Today's Total Sales Revenue: Rs " . number_format($sales_today, 2) . "
    - Today's Total Transactions: " . $transactions_today . "
    - Total Active Products in Catalog: " . $total_products . "
    - All-Time Lifetime Gross Sales Revenue: Rs " . number_format($grand_total_sales, 2) . "
    - Current Low Stock Alert Items (Stock <= $threshold): " . $low_stock_count . " items
    - Highest Selling / Fast Moving Product: '" . $fast_moving_product . "' with " . $fast_moving_qty . " units sold so far.
    - Total Registered Users/Staff: " . $total_users . "
    - Available Database Tables: products, sales, sale_items, users
    
    If the admin asks for reports, summaries, greetings, or calculations, use this data to dynamically compile a human-like response. Keep it scannable using bullet points if needed.";

    $payload = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $system_context . "\n\nAdmin Question: " . $user_query]
                ]
            ]
        ]
    ];

    // 🛠️ ஏதேனும் ஒன்று வேலை செய்யும் வகையில் பல மாடல்களின் எண்ட்பாயிண்ட்டுகள்:
    $endpoints_to_try = [
        "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent",
        "https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent",
        "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent",
        "https://generativelanguage.googleapis.com/v1/models/gemini-1.5-pro:generateContent"
    ];

    $response_text = "";
    $api_call_success = false;
    $error_logs = [];

    // லூப் மூலம் ஒவ்வொரு மாடலாகச் சோதித்தல்
    foreach ($endpoints_to_try as $base_url) {
        $api_url = $base_url . "?key=" . $gemini_api_key;
        
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Localhost பிக்ஸ்
        
        $result = curl_exec($ch);
        
        if ($result !== false) {
            $response_data = json_decode($result, true);
            
            // வெற்றிகரமாகப் பதில் கிடைத்தால் லூப்பை உடைத்து வெளியேறும்
            if (isset($response_data['candidates'][0]['content']['parts'][0]['text'])) {
                $response_text = $response_data['candidates'][0]['content']['parts'][0]['text'];
                $api_call_success = true;
                curl_close($ch);
                break; 
            } elseif (isset($response_data['error']['message'])) {
                $model_name = basename(parse_url($base_url, PHP_URL_PATH));
                $error_logs[] = $model_name . " -> " . $response_data['error']['message'];
            }
        } else {
            $error_logs[] = "cURL Error -> " . curl_error($ch);
        }
        curl_close($ch);
    }

    // அனைத்து மாடல்களும் தோல்வியுற்றால் மட்டும் எர்ரர் காட்டும்
    if (!$api_call_success) {
        $response_text = "Gemini AI Fallback Failed. Tried multiple models:\n" . implode("\n", $error_logs);
    }

    echo json_encode(['reply' => $response_text]);
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
    <span class="bg-indigo-500 text-[10px] uppercase font-bold px-2 py-0.5 rounded">Store Assistant</span>
    <span>Live Update: Today's fastest moving product is <strong class="text-yellow-300 underline font-bold"><?= htmlspecialchars($fast_moving_product) ?></strong> with <span class="text-green-300 font-mono font-bold"><?= $fast_moving_qty ?> units</span> sold.</span>
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
      <svg xmlns="http://www.w3.org/2000/xl" class="h-12 w-12 text-white mb-4 mx-auto" fill="currentColor" viewBox="0 0 16 16">
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
                    <h4 class="text-sm font-bold tracking-wide">Store Assistant (Gemini)</h4>
                    <p class="text-[10px] text-blue-200">Online • Intelligent Reporting Active</p>
                </div>
            </div>
            <button id="close-chat-btn" class="text-white hover:text-gray-200 focus:outline-none text-xl font-bold">&times;</button>
        </div>
        
        <div id="ai-chat-messages" class="flex-grow p-4 overflow-y-auto space-y-3 text-xs bg-slate-50">
            <div class="bg-blue-50 border border-blue-100 text-slate-700 rounded-xl p-3 max-w-[85%] leading-relaxed shadow-sm">
                Hello Admin! 👋 I am your smart store assistant powered by Gemini. 
                <br><br>
                I can create sales reports, summarize dashboard data, or perform metrics calculations dynamically. How can I help you today?
            </div>
        </div>
        
        <div class="p-3 border-t border-gray-100 bg-white flex gap-2">
            <input type="text" id="chat-input-field" placeholder="Ask for a sales summary or query..." class="flex-grow border border-gray-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-blue-600 font-medium">
            <button id="send-chat-btn" class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs px-4 py-2 rounded-xl transition">Send</button>
        </div>
    </div>

    <button id="toggle-chat-bubble" class="bg-gradient-to-tr from-blue-600 to-indigo-700 text-white rounded-full p-4 shadow-xl hover:scale-105 transition transform flex items-center justify-center gap-2 font-bold text-xs border border-blue-500/30">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
        Chat with Assistant
    </button>
</div>

<footer class="bg-blue-700 text-white py-4 text-center text-sm">
  &copy; <?= date('Y') ?> POS Dashboard. All rights reserved.
</footer>

<script>
  const navToggle = document.getElementById('nav-toggle');
  const navMenu = document.getElementById('nav-menu');

  navToggle.addEventListener('click', () => {
    navMenu.classList.toggle('hidden');
  });

  // Chat Widget Logic
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

          // Append User Speech
          const userBubble = document.createElement('div');
          userBubble.className = "bg-white border border-gray-200 text-slate-800 rounded-xl p-3 max-w-[85%] ml-auto text-right font-semibold shadow-sm";
          userBubble.textContent = userText;
          msgContainer.appendChild(userBubble);
          inputField.value = '';
          msgContainer.scrollTop = msgContainer.scrollHeight;

          // Fetch Gemini AI response
          fetch(`?ajax_chat_query=${encodeURIComponent(userText)}`)
              .then(res => res.json())
              .then(data => {
                  const botBubble = document.createElement('div');
                  botBubble.className = "bg-blue-50 border border-blue-100 text-slate-700 rounded-xl p-3 max-w-[85%] whitespace-pre-line shadow-inner font-medium leading-relaxed text-left";
                  
                  let formattedReply = data.reply.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                  botBubble.innerHTML = formattedReply;
                  
                  msgContainer.appendChild(botBubble);
                  msgContainer.scrollTop = msgContainer.scrollHeight;
              })
              .catch(() => {
                  const errorBubble = document.createElement('div');
                  errorBubble.className = "bg-rose-50 text-rose-600 rounded-xl p-3 max-w-[85%]";
                  errorBubble.textContent = "Sorry Admin, I'm having trouble connecting to Gemini. Please check the API config.";
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