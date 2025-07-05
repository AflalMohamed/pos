<?php
require '../../includes/auth.php';
require '../../includes/db.php';

checkLogin();

if ($_SESSION['role'] !== 'admin') {
    die("Access denied.");
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid user ID.");
}

$userId = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    die("User not found.");
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newUsername = trim($_POST['username'] ?? '');
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($newUsername)) {
        $errors[] = "Username cannot be empty.";
    }

    // Check if username already exists (except current user)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
    $stmt->execute([$newUsername, $userId]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = "Username is already taken.";
    }

    if ($newPassword !== '') {
        if (strlen($newPassword) < 6) {
            $errors[] = "Password must be at least 6 characters.";
        }
        if ($newPassword !== $confirmPassword) {
            $errors[] = "Password and confirmation do not match.";
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
        $stmt->execute([$newUsername, $userId]);

        if ($newPassword !== '') {
            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $userId]);
        }

        $success = true;
        $user['username'] = $newUsername;
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Change Credentials - <?= htmlspecialchars($user['username']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen font-sans">

<header class="bg-black text-white shadow py-4">
    <div class="max-w-4xl mx-auto px-6 flex justify-between items-center">
        <h1 class="text-2xl font-semibold tracking-wide">Change Credentials</h1>
        <a href="index.php" class="bg-white text-black font-semibold px-4 py-2 rounded-md hover:bg-gray-100 transition">
            &larr; Back to User Management
        </a>
    </div>
</header>

<main class="max-w-4xl mx-auto px-6 py-8 bg-white rounded shadow mt-6">
    <?php if ($success): ?>
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            User credentials updated successfully.
        </div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            <ul class="list-disc pl-5">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
        <div>
            <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
            <input
                type="text"
                id="username"
                name="username"
                value="<?= htmlspecialchars($user['username']) ?>"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-black focus:border-black"
                required
            />
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">
                New Password (leave blank to keep current password)
            </label>
            <input
                type="password"
                id="password"
                name="password"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-black focus:border-black"
                autocomplete="new-password"
            />
        </div>

        <div>
            <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-black focus:border-black"
                autocomplete="new-password"
            />
        </div>

        <div>
            <button type="submit" class="bg-black text-white px-6 py-2 rounded-md hover:bg-gray-900 transition font-semibold">
                Save Changes
            </button>
        </div>
    </form>
</main>

</body>
</html>
