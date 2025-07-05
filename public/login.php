<?php 
session_start(); 
require '../includes/db.php'; 
 
$error = ''; 
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
    $username = trim($_POST['username']); 
    $password = $_POST['password']; 
 
    if ($username && $password) { 
        $stmt = $pdo->prepare("SELECT id, password_hash, role FROM users WHERE username = ?"); 
        $stmt->execute([$username]); 
        $user = $stmt->fetch(PDO::FETCH_ASSOC); 
 
        if ($user && password_verify($password, $user['password_hash'])) { 
            $_SESSION['user_id'] = $user['id']; 
            $_SESSION['username'] = $username; 
            $_SESSION['role'] = $user['role']; 
 
            header('Location: dashboard.php'); 
            exit; 
        } else { 
            $error = "Invalid username or password."; 
        } 
    } else { 
        $error = "Please enter username and password."; 
    } 
} 
?> 
 
<!DOCTYPE html> 
<html lang="en" class="scroll-smooth bg-white"> 
<head> 
    <meta charset="UTF-8" /> 
    <meta name="viewport" content="width=device-width, initial-scale=1" /> 
    <title>Login - POS</title> 
    <script src="https://cdn.tailwindcss.com"></script> 
</head> 
<body class="min-h-screen flex items-center justify-center px-4 py-12"> 
    <div class="max-w-md w-full bg-black rounded-3xl shadow-2xl p-12"> 
        <h1 class="text-4xl font-extrabold text-center mb-10 text-white tracking-wide">POS Login</h1> 
 
        <?php if ($error): ?> 
            <div class="mb-6 rounded-md bg-red-800 bg-opacity-90 p-4 text-red-300 font-semibold shadow-md" role="alert"> 
                <?= htmlspecialchars($error) ?> 
            </div> 
        <?php endif; ?> 
 
        <form method="POST" action="" class="space-y-7"> 
            <div> 
                <label for="username" class="block text-sm font-semibold mb-2 text-gray-300 tracking-wide">Username</label> 
                <input 
                    type="text" 
                    name="username" 
                    id="username" 
                    required 
                    autofocus 
                    autocomplete="username" 
                    placeholder="Enter your username"
                    class="w-full px-5 py-3 bg-gray-900 border border-gray-700 rounded-lg shadow-inner placeholder-gray-500 text-gray-200 focus:outline-none focus:ring-2 focus:ring-white focus:border-white transition" 
                /> 
            </div> 
            <div> 
                <label for="password" class="block text-sm font-semibold mb-2 text-gray-300 tracking-wide">Password</label> 
                <input 
                    type="password" 
                    name="password" 
                    id="password" 
                    required 
                    autocomplete="current-password" 
                    placeholder="••••••••"
                    class="w-full px-5 py-3 bg-gray-900 border border-gray-700 rounded-lg shadow-inner placeholder-gray-500 text-gray-200 focus:outline-none focus:ring-2 focus:ring-white focus:border-white transition" 
                /> 
            </div> 
            <button 
                type="submit" 
                class="w-full bg-white hover:bg-gray-100 active:bg-gray-200 text-black font-bold py-3 rounded-lg shadow-lg transition" 
            > 
                Log In 
            </button> 
        </form> 
 
        <p class="mt-8 text-center text-gray-400 text-sm select-none tracking-wide"> 
            &copy; <?= date('Y') ?> POS System. All rights reserved. 
        </p> 
    </div> 
</body> 
</html>
