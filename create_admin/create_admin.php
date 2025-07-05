<?php
require 'includes/db.php'; // adjust path if needed

$username = 'admin';
$password = 'admin123'; // change this password ASAP
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, 'admin')");

try {
    $stmt->execute([$username, $hashed_password]);
    echo "Admin user created successfully. Username: $username, Password: $password";
} catch (PDOException $e) {
    echo "Error creating admin user: " . $e->getMessage();
}
