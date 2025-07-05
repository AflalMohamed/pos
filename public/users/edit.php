<?php
require '../../includes/auth.php';
require '../../includes/db.php';

checkLogin();

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
<html>
<head>
    <title>Edit User</title>
</head>
<body>
    <h1>Edit User</h1>
    <p><a href="list.php">Back to User List</a></p>

    <?php if ($error): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Username:<br>
            <input type="text" name="username" required value="<?= htmlspecialchars($_POST['username'] ?? $user['username']) ?>">
        </label><br><br>
        <label>Password (leave blank to keep current):<br>
            <input type="password" name="password">
        </label><br><br>
        <label>Role:<br>
            <select name="role">
                <option value="cashier" <?= (($_POST['role'] ?? $user['role']) === 'cashier') ? 'selected' : '' ?>>Cashier</option>
                <option value="admin" <?= (($_POST['role'] ?? $user['role']) === 'admin') ? 'selected' : '' ?>>Admin</option>
            </select>
        </label><br><br>
        <button type="submit">Update User</button>
    </form>
</body>
</html>
