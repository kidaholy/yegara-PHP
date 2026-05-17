<?php
/**
 * Login page for the PHP Management System
 */
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (isAuthenticated()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $result = login($email, $password);
    if ($result['success']) {
        header('Location: index.php');
        exit;
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Prime Addis</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --background: 222.2 84% 4.9%;
            --foreground: 210 40% 98%;
            --primary: 210 40% 98%;
            --primary-foreground: 222.2 47.4% 11.2%;
            --secondary: 217.2 32.6% 17.5%;
            --accent: 217.2 32.6% 17.5%;
            --border: 217.2 32.6% 17.5%;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: hsl(var(--background));
            color: hsl(var(--foreground));
            background-image: radial-gradient(circle at 50% -20%, #1e293b 0%, #020617 100%);
        }
        .glass {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-10">
            <h1 class="text-4xl font-bold font-playfair tracking-tight mb-2 text-white">Prime Addis</h1>
            <p class="text-slate-400">Management System</p>
        </div>

        <div class="glass rounded-2xl p-8 shadow-2xl relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-500 to-purple-600"></div>
            
            <form method="POST" class="space-y-6">
                <h2 class="text-xl font-semibold mb-6">Welcome Back</h2>
                
                <?php if ($error): ?>
                <div class="bg-red-500/10 border border-red-500/50 text-red-400 p-3 rounded-lg text-sm flex items-center gap-3">
                    <i data-lucide="alert-circle" class="w-4 h-4"></i>
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-300">Email Address</label>
                    <div class="relative">
                        <i data-lucide="mail" class="absolute left-3 top-3 w-4 h-4 text-slate-500"></i>
                        <input type="email" name="email" required
                               class="w-full bg-slate-900/50 border border-slate-700/50 rounded-lg py-2.5 pl-10 pr-4 focus:outline-none focus:ring-2 focus:ring-blue-500/50 transition-all text-white placeholder-slate-600"
                               placeholder="name@example.com">
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-300">Password</label>
                    <div class="relative">
                        <i data-lucide="lock" class="absolute left-3 top-3 w-4 h-4 text-slate-500"></i>
                        <input type="password" name="password" required
                               class="w-full bg-slate-900/50 border border-slate-700/50 rounded-lg py-2.5 pl-10 pr-4 focus:outline-none focus:ring-2 focus:ring-blue-500/50 transition-all text-white placeholder-slate-600"
                               placeholder="••••••••">
                    </div>
                </div>

                <button type="submit" 
                        class="w-full bg-white text-slate-950 font-semibold py-3 rounded-lg hover:bg-slate-200 transition-colors flex items-center justify-center gap-2">
                    Sign In
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
            </form>
        </div>

        <p class="text-center mt-8 text-sm text-slate-500">
            &copy; <?php echo date('Y'); ?> Prime Addis Management. All rights reserved.
        </p>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
