<?php
require '../../includes/auth.php';
require '../../includes/db.php';

checkLogin();

// Only allow admin users
if ($_SESSION['role'] !== 'admin') {
    die("Access denied.");
}

$stmt = $pdo->query("SELECT id, username, role FROM users ORDER BY username");
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>User Management - Admin Control</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen antialiased text-slate-800">

<header class="bg-slate-900 text-white shadow-md border-b border-slate-800 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-6 py-4 flex flex-col sm:flex-row justify-between items-center gap-4">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-indigo-600 rounded-lg shadow-inner">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 width-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-bold tracking-tight text-white">User Management</h1>
                <p class="text-xs text-slate-400">System Operator Control Registry</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="../dashboard.php" 
               class="bg-slate-800 border border-slate-700 text-slate-200 text-sm font-medium px-4 py-2 rounded-lg hover:bg-slate-700 hover:text-white transition-all shadow-sm">
                &larr; Dashboard
            </a>
            <a href="add.php" 
               class="bg-indigo-600 text-white text-sm font-semibold px-4 py-2 rounded-lg hover:bg-indigo-500 transition-all shadow-md shadow-indigo-600/10 flex items-center gap-1.5">
                <span>+</span> Add New User
            </a>
        </div>
    </div>
</header>

<main class="max-w-6xl mx-auto px-6 py-10">
    <?php if (count($users) > 0): ?>
        <div class="overflow-hidden bg-white rounded-xl shadow-sm border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider text-xs border-b border-slate-200">
                    <tr>
                        <th scope="col" class="px-6 py-4">Database ID</th>
                        <th scope="col" class="px-6 py-4">Username Handle</th>
                        <th scope="col" class="px-6 py-4">Assigned Role Authority</th>
                        <th scope="col" class="px-6 py-4 text-right pr-8">Operational Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    <?php foreach ($users as $user): ?>
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap font-semibold text-slate-500">
                                #<?= htmlspecialchars($user['id']) ?>
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-slate-900">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 font-bold text-xs uppercase border border-slate-200 shadow-inner">
                                        <?= substr(htmlspecialchars($user['username']), 0, 2) ?>
                                    </div>
                                    <span><?= htmlspecialchars($user['username']) ?></span>
                                </div>
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php 
                                    $role = htmlspecialchars($user['role']);
                                    $badgeStyle = ($role === 'admin') 
                                        ? 'bg-rose-50 text-rose-700 border-rose-100' 
                                        : 'bg-emerald-50 text-emerald-700 border-emerald-100';
                                ?>
                                <span class="px-2.5 py-1 inline-flex text-xs font-semibold rounded-full border shadow-sm capitalize <?= $badgeStyle ?>">
                                    <?= $role ?>
                                </span>
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap text-right pr-8 space-x-1.5 text-xs">
                                <a href="edit.php?id=<?= urlencode($user['id']) ?>" 
                                   class="inline-flex items-center bg-white border border-slate-200 px-3 py-1.5 rounded-md font-medium text-slate-700 shadow-sm hover:bg-slate-50 transition">
                                    Edit
                                </a>
                                <a href="change_credentials.php?id=<?= urlencode($user['id']) ?>" 
                                   class="inline-flex items-center bg-white border border-slate-200 px-3 py-1.5 rounded-md font-medium text-purple-700 shadow-sm hover:bg-purple-50 transition">
                                    Keys
                                </a>
                                <?php if ($user['role'] !== 'admin'): ?>
                                    <a href="delete.php?id=<?= urlencode($user['id']) ?>" 
                                       onclick="return confirm('Wipe out selected active operator access privileges safely? This path drops user configs.')" 
                                       class="inline-flex items-center bg-rose-50 border border-rose-100 px-3 py-1.5 rounded-md font-medium text-rose-600 hover:bg-rose-100 transition">
                                        Delete
                                    </a>
                                <?php else: ?>
                                    <span class="inline-flex items-center bg-slate-100 border border-slate-200 px-3 py-1.5 rounded-md font-medium text-slate-400 cursor-not-allowed opacity-60 select-none">
                                        Locked
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="text-center py-16 bg-white rounded-xl shadow-sm border border-slate-200">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <h3 class="text-lg font-bold text-slate-800">No Operations Accounts Found</h3>
            <p class="text-slate-500 max-w-sm mx-auto mt-1 text-sm">System setup maps empty records. Try registering a new system handler or supervisor key profile to initialize.</p>
        </div>
    <?php endif; ?>
</main>

</body>
</html>