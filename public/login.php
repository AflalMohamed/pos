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
            $error = "Invalid username or password configuration."; 
        } 
    } else { 
        $error = "Please fill in all security parameter fields."; 
    } 
} 
?> 
 
<!DOCTYPE html> 
<html lang="en" class="scroll-smooth h-full bg-slate-50"> 
<head> 
    <meta charset="UTF-8" /> 
    <meta name="viewport" content="width=device-width, initial-scale=1" /> 
    <title>POS System — Secure Portal Login</title> 
    <script src="https://cdn.tailwindcss.com"></script> 
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
    </style>
</head> 
<body class="antialiased min-h-screen bg-slate-50 flex flex-col justify-center relative px-4 py-12"> 
    
    <div class="absolute inset-0 bg-[linear-gradient(to_right,#e2e8f0_1px,transparent_1px),linear-gradient(to_bottom,#e2e8f0_1px,transparent_1px)] bg-[size:4rem_4rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_0%,#000_70%,transparent_100%)] pointer-events-none"></div>

    <div class="sm:mx-auto sm:w-full sm:max-w-md relative z-10">
        <div class="flex flex-col items-center justify-center text-center mb-8">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-lg shadow-blue-600/20 ring-1 ring-blue-700/30 mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <div>
                <span class="text-[10px] font-bold tracking-widest text-blue-600 uppercase block leading-none">Central Management Node</span>
                <span class="text-2xl font-extrabold tracking-tight text-slate-900">POS Control Interface</span>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-8 sm:p-10 shadow-xl shadow-slate-200/50 relative">
            <div class="absolute top-0 left-0 right-0 h-[3px] bg-blue-600 rounded-t-2xl"></div>
            
            <div class="mb-6 text-left">
                <h2 class="text-lg font-bold tracking-tight text-slate-800">Operator Gateway Authentication</h2>
                <p class="text-xs text-slate-500 mt-1 font-medium">Please connect inside the core database stream using your official credentials.</p>
            </div>
 
            <?php if ($error): ?> 
                <div class="mb-5 rounded-xl bg-red-50 border border-red-200 p-4 text-red-700 text-xs font-semibold flex items-start gap-2.5 shadow-sm" role="alert"> 
                    <svg class="h-4 w-4 shrink-0 mt-0.5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <span class="block font-bold uppercase tracking-wider text-[10px] text-red-800 mb-0.5">Authentication Exception</span>
                        <?= htmlspecialchars($error) ?> 
                    </div>
                </div> 
            <?php endif; ?> 
 
            <form method="POST" action="" class="space-y-5"> 
                <div> 
                    <label for="username" class="block text-xs font-bold uppercase tracking-wider mb-2 text-slate-600">Operator Username</label> 
                    <div class="relative rounded-xl shadow-sm">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 0116 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            name="username" 
                            id="username" 
                            required 
                            autofocus 
                            autocomplete="username" 
                            placeholder="e.g., admin_node"
                            class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl placeholder-slate-400 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition duration-150 font-medium font-mono" 
                        /> 
                    </div>
                </div> 

                <div> 
                    <label for="password" class="block text-xs font-bold uppercase tracking-wider mb-2 text-slate-600">Secure Password</label> 
                    <div class="relative rounded-xl shadow-sm">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <input 
                            type="password" 
                            name="password" 
                            id="password" 
                            required 
                            autocomplete="current-password" 
                            placeholder="••••••••"
                            class="w-full pl-10 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl placeholder-slate-400 text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition duration-150 font-medium font-mono" 
                        /> 
                        <button type="button" id="password-toggle-trigger" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 focus:outline-none">
                            <svg xmlns="http://www.w3.org/2000/svg" id="eye-icon-open" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div> 

                <button 
                    type="submit" 
                    class="w-full bg-blue-600 hover:bg-blue-700 active:scale-[0.99] text-white font-bold py-2.5 px-4 rounded-xl shadow-md shadow-blue-600/10 hover:shadow-blue-600/20 transition transform duration-150 text-sm tracking-wide" 
                > 
                    Establish Terminal Session 
                </button> 
            </form> 
        </div>

        <div class="mt-8 flex flex-col sm:flex-row justify-between items-center text-[11px] text-slate-400 font-medium px-2 gap-2 select-none">
            <p>&copy; <?= date('Y') ?> Core Framework Systems. Verified Security Link.</p>
            <p class="font-mono text-[10px] bg-white border border-slate-200 text-slate-500 px-2 py-0.5 rounded shadow-sm">ENC: SHA_256_VERIFIED</p>
        </div>
    </div>

    <script>
        (() => {
            const toggleTrigger = document.getElementById('password-toggle-trigger');
            const passField = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon-open');

            if (toggleTrigger && passField) {
                toggleTrigger.addEventListener('click', () => {
                    const isPass = passField.type === 'password';
                    passField.type = isPass ? 'text' : 'password';
                    
                    // Modify SVG graphics arrays instantly based on node viewport parameters
                    if (isPass) {
                        eyeIcon.setAttribute('stroke', '#2563eb'); // Focus professional corporate blue on reveal
                        eyeIcon.innerHTML = `
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                        `;
                    } else {
                        eyeIcon.setAttribute('stroke', 'currentColor');
                        eyeIcon.innerHTML = `
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        `;
                    }
                });
            }
        })();
    </script>
</body> 
</html>