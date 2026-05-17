<?php
/**
 * Refined Login page for the PHP Management System
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
    <title>Sign In - Prime Addis</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --background: 222 47% 4%;
            --foreground: 213 31% 91%;
            --border: 216 34% 17%;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: hsl(var(--background));
            color: hsl(var(--foreground));
            background-image: 
                radial-gradient(circle at 0% 0%, rgba(59, 130, 246, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 100% 100%, rgba(147, 51, 234, 0.05) 0%, transparent 50%);
            -webkit-font-smoothing: antialiased;
        }
        .glass {
            background: rgba(3, 7, 18, 0.4);
            backdrop-filter: blur(24px);
            border: 1px solid hsl(var(--border));
        }
        .animate-in {
            animation: animateIn 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }
        @keyframes animateIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-[400px] animate-in">
        <div class="text-center mb-10">
            <div class="w-12 h-12 rounded-2xl bg-white mx-auto mb-6 flex items-center justify-center shadow-[0_0_20px_rgba(255,255,255,0.1)]">
                <i data-lucide="hotel" class="w-7 h-7 text-slate-950"></i>
            </div>
            <h1 class="text-3xl font-bold font-playfair tracking-tight mb-2 text-white">Prime Addis</h1>
            <p class="text-muted-foreground text-sm font-medium opacity-60">Management System</p>
        </div>

        <div class="glass rounded-3xl p-8 shadow-2xl relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 opacity-50"></div>
            
            <form method="POST" class="space-y-6" id="login-form">
                <div class="space-y-1.5 mb-8">
                    <h2 class="text-xl font-semibold text-white">Welcome back</h2>
                    <p class="text-xs text-muted-foreground">Enter your credentials to access your dashboard</p>
                </div>
                
                <?php if ($error): ?>
                <div class="bg-red-500/10 border border-red-500/20 text-red-400 p-3.5 rounded-xl text-xs font-medium flex items-center gap-3 animate-pulse">
                    <i data-lucide="alert-circle" class="w-4 h-4"></i>
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>

                <div class="space-y-2">
                    <label class="text-xs font-bold uppercase tracking-wider text-muted-foreground opacity-70 ml-1">Email</label>
                    <div class="relative group">
                        <i data-lucide="mail" class="absolute left-3.5 top-3 w-4 h-4 text-muted-foreground transition-colors group-focus-within:text-white"></i>
                        <input type="email" name="email" required
                               class="w-full bg-white/5 border border-border rounded-xl py-2.5 pl-11 pr-4 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500/50 transition-all text-sm text-white placeholder-slate-600"
                               placeholder="admin@primeaddis.com">
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="flex justify-between items-center ml-1">
                        <label class="text-xs font-bold uppercase tracking-wider text-muted-foreground opacity-70">Password</label>
                        <a href="#" class="text-[10px] font-bold text-blue-400 hover:text-blue-300 transition-colors uppercase tracking-tighter">Forgot?</a>
                    </div>
                    <div class="relative group">
                        <i data-lucide="lock" class="absolute left-3.5 top-3 w-4 h-4 text-muted-foreground transition-colors group-focus-within:text-white"></i>
                        <input type="password" name="password" required
                               class="w-full bg-white/5 border border-border rounded-xl py-2.5 pl-11 pr-4 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500/50 transition-all text-sm text-white placeholder-slate-600"
                               placeholder="••••••••">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" id="submit-btn"
                            class="w-full bg-white text-slate-950 font-bold py-3 rounded-xl hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-2 shadow-[0_4px_20px_rgba(255,255,255,0.1)]">
                        <span>Sign In</span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </button>
                </div>
            </form>
        </div>

        <div class="flex items-center justify-center gap-4 mt-12 opacity-30 grayscale pointer-events-none">
            <span class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground">Compatible with</span>
            <div class="flex gap-4">
                <i data-lucide="shield-check" class="w-5 h-5"></i>
                <i data-lucide="zap" class="w-5 h-5"></i>
                <i data-lucide="server" class="w-5 h-5"></i>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
        
        // Simple client-side loading state
        const form = document.getElementById('login-form');
        const btn = document.getElementById('submit-btn');
        
        form.addEventListener('submit', () => {
            btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i><span>Signing In...</span>';
            btn.disabled = true;
            lucide.createIcons();
        });
    </script>
</body>
</html>
