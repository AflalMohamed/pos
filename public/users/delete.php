<?php
require '../../includes/auth.php';
require '../../includes/db.php';

checkLogin();

if ($_SESSION['role'] !== 'admin') {
    die("Access denied.");
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Prevent deleting admins
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if ($user && $user['role'] !== 'admin') {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
    }
}

header('Location: list.php');
exit;
