<?php
require '../../includes/auth.php';
require '../../includes/db.php';

checkLogin();

// Only allow admin users
if ($_SESSION['role'] !== 'admin') {
    die("Access denied.");
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: list.php');
    exit;
}

$id = (int)$_GET['id'];
$error = '';

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: list.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    if (!$username || !in_array($role, ['admin', 'cashier'])) {
        $error = "Please fill all fields correctly.";
    } else {
        // Check username uniqueness excluding current user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $id]);
        if ($stmt->fetch()) {
            $error = "Username already exists.";
        } else {
            if ($password) {
                // Noted: Your original used password_hash field name from older schemas, mapping dynamically
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username = ?, password_hash = ?, role = ? WHERE id = ?");
                $stmt->execute([$username, $hashed_password, $role, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
                $stmt->execute([$username, $role, $id]);
            }
            header('Location: list.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Edit System Operator - Admin Control</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="h-full antialiased text-slate-100 flex flex-col bg-slate-950">

    <nav class="bg-slate-900/80 backdrop-blur-md border-b border-slate-800 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center gap-3.5">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 text-white font-bold shadow-lg shadow-indigo-500/20">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-sm font-bold block tracking-tight text-white">Console Engine</span>
                        <span class="text-[10px] text-slate-400 uppercase tracking-widest font-semibold">Modify Account</span>
                    </div>
                </div>
                <div>
                    <a href="list.php" 
                       class="inline-flex items-center justify-center text-xs font-semibold h-9 px-4 rounded-lg text-slate-300 bg-slate-800 border border-slate-700 hover:bg-slate-700 hover:text-white transition-all duration-150">
                        &larr; Back to Registry
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-1 flex flex-col items-center justify-center px-4 py-12 w-full max-w-xl mx-auto">
        
        <div class="w-full bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-2xl relative overflow-hidden">
            
            <div class="absolute top-0 left-0 right-0 h-[3px] bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>

            <div class="mb-6">
                <h2 class="text-xl font-bold text-white tracking-tight">Edit Profile Parameters</h2>
                <p class="text-xs text-slate-400 mt-1">Update credential nodes or adjust authorization policy rings for account identifier <span class="font-mono text-indigo-400">#<?= sprintf('%03d', $user['id']) ?></span>.</p>
            </div>

            <?php if ($error): ?>
                <div class="mb-5 flex items-start gap-3 bg-rose-500/10 border border-rose-500/20 text-rose-400 p-4 rounded-xl text-sm transition-all animate-shake">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-rose-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <div>
                        <span class="font-bold block">Execution Interrupted</span>
                        <span class="text-xs text-rose-400/90"><?= htmlspecialchars($error) ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Username Handle</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-500 text-xs font-mono select-none">@</span>
                        <input type="text" 
                               name="username" 
                               required 
                               autocomplete="off"
                               value="<?= htmlspecialchars($_POST['username'] ?? $user['username']) ?>"
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl h-11 pl-8 pr-4 text-sm text-white font-medium focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all duration-150 placeholder-slate-600">
                    </div>
                    <p class="text-[10px] text-slate-500 mt-1.5">This handles unique operator profile key indicators inside application stack modules.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Security Key / Password</label>
                    <input type="password" 
                           name="password"
                           placeholder="••••••••••••"
                           class="w-full bg-slate-950 border border-slate-800 rounded-xl h-11 px-4 text-sm text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all duration-150 placeholder-slate-700">
                    <p class="text-[10px] text-amber-400/80 mt-1.5 flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zm-1 9a1 1 0 00-1-1v-4a1 1 0 102 0v4a1 1 0 00-1 1z" clip-rule="evenodd" />
                        </svg>
                        Security Strategy Note: Leave completely blank to preserve current database password hash values.
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Authorization Role Ring</label>
                    <div class="relative">
                        <select name="role" 
                                class="w-full bg-slate-950 border border-slate-800 rounded-xl h-11 px-4 text-sm text-white appearance-none focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all duration-150 cursor-pointer">
                            <option value="cashier" <?= (($_POST['role'] ?? $user['role']) === 'cashier') ? 'selected' : '' ?>>Cashier Account (Limited Access)</option>
                            <option value="admin" <?= (($_POST['role'] ?? $user['role']) === 'admin') ? 'selected' : '' ?>>Administrator (Root Context Scope)</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="pt-2 flex items-center gap-3">
                    <a href="list.php" 
                       class="flex-1 inline-flex items-center justify-center text-xs font-semibold h-11 rounded-xl text-slate-400 bg-slate-950 border border-slate-800 hover:bg-slate-800 hover:text-slate-200 transition-all duration-150">
                        Cancel Changes
                    </a>
                    <button type="submit" 
                            class="flex-1 inline-flex items-center justify-center text-xs font-bold h-11 rounded-xl text-white bg-indigo-600 hover:bg-indigo-500 shadow-lg shadow-indigo-600/10 transition-all duration-150">
                        Commit Safe Updates
                    </button>
                </div>

            </form>
        </div>
        
    </main>

</body>
</html>