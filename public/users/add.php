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
            $error = "Username already exists.";
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
<html>
<head>
    <title>Add User</title>
</head>
<body>
    <h1>Add New User</h1>
    <p><a href="list.php">Back to User List</a></p>

    <?php if ($error): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Username:<br>
            <input type="text" name="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </label><br><br>
        <label>Password:<br>
            <input type="password" name="password" required>
        </label><br><br>
        <label>Role:<br>
            <select name="role">
                <option value="cashier" <?= (($_POST['role'] ?? '') === 'cashier') ? 'selected' : '' ?>>Cashier</option>
                <option value="admin" <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
            </select>
        </label><br><br>
        <button type="submit">Add User</button>
    </form>
</body>
</html>
