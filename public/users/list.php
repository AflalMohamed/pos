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
    <title>User Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen font-sans">

<header class="bg-black text-white shadow py-4">
    <div class="max-w-7xl mx-auto px-6 flex justify-between items-center">
        <h1 class="text-2xl font-semibold tracking-wide">User Management</h1>
        <div class="space-x-4">
            <a href="../dashboard.php" 
               class="bg-white text-black font-semibold px-4 py-2 rounded-md hover:bg-gray-100 transition">
                &larr; Back to Dashboard
            </a>
            <a href="add.php" 
               class="bg-green-600 text-white font-semibold px-4 py-2 rounded-md hover:bg-green-700 transition">
                Add New User
            </a>
        </div>
    </div>
</header>

<main class="max-w-5xl mx-auto px-6 py-8">
    <?php if (count($users) > 0): ?>
        <div class="overflow-x-auto bg-white rounded-lg shadow border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200 text-gray-900">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase tracking-wide">ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase tracking-wide">Username</th>
                        <th scope="col" class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase tracking-wide">Role</th>
                        <th scope="col" class="px-6 py-3 text-left text-sm font-semibold text-gray-700 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($users as $user): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap align-middle font-medium"><?= htmlspecialchars($user['id']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap align-middle"><?= htmlspecialchars($user['username']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap align-middle capitalize"><?= htmlspecialchars($user['role']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap align-middle space-x-3">
                                <a href="edit.php?id=<?= urlencode($user['id']) ?>" class="text-blue-600 hover:text-blue-800 font-semibold">Edit</a>
                                |
                                <a href="change_credentials.php?id=<?= urlencode($user['id']) ?>" class="text-purple-600 hover:text-purple-800 font-semibold">Change Credentials</a>
                                <?php if ($user['role'] !== 'admin'): ?>
                                    |
                                    <a href="delete.php?id=<?= urlencode($user['id']) ?>" onclick="return confirm('Delete this user?')" class="text-red-600 hover:text-red-800 font-semibold">Delete</a>
                                <?php else: ?>
                                    | <span class="text-gray-400 select-none cursor-not-allowed">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-center text-gray-600 text-lg mt-12">No users found.</p>
    <?php endif; ?>
</main>

</body>
</html>
