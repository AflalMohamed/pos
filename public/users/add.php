<?php
require '../../includes/auth.php';
require '../../includes/db.php';

checkLogin();

if ($_SESSION['role'] !== 'admin') {
    die("Access denied.");
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    if (!$username || !$password || !in_array($role, ['admin', 'cashier'])) {
        $error = "Please fill all fields correctly.";
    } else {
        // Check username uniqueness
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = "Username already exists in the system directory.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)");
            $stmt->execute([$username, $hashed_password, $role]);
            header('Location: list.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50 text-slate-800">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>POS Control Panel — Add Operator</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="h-full antialiased flex flex-col min-h-screen bg-slate-50/50">

    <nav class="border-b border-slate-200 bg-white/80 backdrop-blur-md sticky top-0 z-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <div class="flex items-center gap-4">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-violet-600 text-white shadow-lg shadow-violet-500/20">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-xs font-bold tracking-widest text-slate-400 uppercase block">Access Management</span>
                        <span class="text-lg font-bold text-slate-900 tracking-tight">Register Terminal Account</span>
                    </div>
                </div>
                <div>
                    <a href="list.php" 
                       class="inline-flex items-center justify-center text-xs font-semibold h-10 px-4 rounded-xl text-slate-600 hover:text-slate-900 bg-white border border-slate-200 hover:bg-slate-50 transition-all duration-150 shadow-sm">
                        &larr; Back to Directory
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-1 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 w-full">
        
        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-sm text-red-700 rounded-2xl flex items-start gap-3 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500 flex-shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <div>
                    <span class="font-bold block">Authorization Exception</span>
                    <p class="text-xs text-red-600 mt-0.5"><?= htmlspecialchars($error) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <div class="bg-white border border-slate-200 rounded-3xl p-6 md:p-8 shadow-sm">
            <form method="POST" class="space-y-6">
                
                <div>
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-4">Credentials Specification</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <div>
                            <label for="username" class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5">Profile Username</label>
                            <div class="relative">
                                <span class="absolute left-4 top-3.5 text-slate-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </span>
                                <input id="username" name="username" type="text" required autocomplete="off" placeholder="e.g., cashier_alex"
                                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                       class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-11 pr-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-violet-500 focus:bg-white focus:ring-4 focus:ring-violet-500/10 transition-all font-medium" />
                            </div>
                        </div>

                        <div>
                            <label for="password" class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5">Security Password</label>
                            <div class="relative">
                                <span class="absolute left-4 top-3.5 text-slate-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </span>
                                <input id="password" name="password" type="password" required placeholder="••••••••"
                                       class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-11 pr-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-violet-500 focus:bg-white focus:ring-4 focus:ring-violet-500/10 transition-all" />
                            </div>
                        </div>

                    </div>
                </div>

                <div class="border-t border-slate-100"></div>

                <div>
                    <label for="role" class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5">Assign Clear Level Privilege</label>
                    <div class="relative max-w-md">
                        <select id="role" name="role" 
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-900 focus:outline-none focus:border-violet-500 focus:bg-white focus:ring-4 focus:ring-violet-500/10 transition-all appearance-none font-semibold cursor-pointer">
                            <option value="cashier" <?= (($_POST['role'] ?? '') === 'cashier') ? 'selected' : '' ?>>Terminal Cashier (Sales Only)</option>
                            <option value="admin" <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>System Administrator (Full Authority)</option>
                        </select>
                        <span class="absolute right-4 top-4 pointer-events-none text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-2">Administrators retain authorization keys to edit global items catalog matrices, wipe system metrics, and audit ledger logs.</p>
                </div>

                <div class="pt-4 flex justify-end">
                    <button type="submit"
                            class="w-full md:w-auto inline-flex items-center justify-center text-xs font-bold h-12 px-8 rounded-xl text-white bg-violet-600 hover:bg-violet-700 transition-all duration-150 shadow-md shadow-violet-500/10 hover:scale-[1.02] active:scale-[0.98]">
                        Provision Account Access &rarr;
                    </button>
                </div>

            </form>
        </div>
    </main>

</body>
</html>