<?php
/**
 * High-Fidelity Luxury Login Page - Abe Hotel
 */
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/SettingsManager.php';

$manager = new SettingsManager();
extract($manager->getBrandingVars());
if ($appTagline === 'HOTEL MANAGEMENT SYSTEM') {
    $appTagline = 'ምርጥ አገልግሎት ለመስጠት';
}

function routeUserBasedOnRole($role) {
    switch ($role) {
        case 'admin':
            return 'admin.php';
        case 'cashier':
            return 'cashier.php';
        case 'chef':
            return 'chef.php';
        case 'bar':
            return 'bar.php';
        case 'store_keeper':
        case 'store':
            return 'store.php';
        case 'receptionist':
        case 'reception':
            return 'reception.php';
        case 'display':
            return 'display.php';
        case 'custom':
            // Check specific interface access for custom roles systematically
            $user = getCurrentUser();
            $perms = $user['permissions'] ?? [];
            
            // Priority 1: Direct Interface Access
            if (in_array('cashier:access', $perms)) return 'cashier.php';
            if (in_array('chef:access', $perms)) return 'chef.php';
            if (in_array('bar:access', $perms)) return 'bar.php';
            if (in_array('reception:access', $perms)) return 'reception.php';
            if (in_array('display:access', $perms)) return 'display.php';
            
            // Priority 2: Admin/Management Sections
            if (in_array('overview:view', $perms)) return 'admin.php';
            
            // Priority 3: Feature Specific Hubs (Granular checks)
            if (hasPermissionPattern('/^orders:/')) return 'orders.php';
            if (hasPermissionPattern('/^reports:/')) return 'reports.php';
            if (hasPermissionPattern('/^stock:/')) return 'stock.php';
            if (hasPermissionPattern('/^store:/')) return 'store.php';
            if (hasPermissionPattern('/^users:/')) return 'staff.php';
            if (hasPermissionPattern('/^services:/')) return 'services.php';
            if (hasPermissionPattern('/^settings:/')) return 'settings.php';
            
            return 'index.php';
        default:
            return 'index.php';
    }
}

if (isAuthenticated()) {
    $user = getCurrentUser();
    header('Location: ' . routeUserBasedOnRole($user['role'] ?? ''));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $res = login($email, $password);
    if ($res['success']) {
        header('Location: ' . routeUserBasedOnRole($res['user']['role'] ?? ''));
        exit;
    } else {
        $error = $res['message'] ?? 'Invalid credentials or account suspended.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo htmlspecialchars($appName); ?></title>
    <?php if ($faviconUrl): ?>
    <link rel="icon" href="<?php echo htmlspecialchars($faviconUrl); ?>" />
    <?php endif; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,700;0,900;1,700;1,900&family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,600;1,700&family=Great+Vibes&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --accent: #c5a059;
            --background: #0a0a0a;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background);
            color: white;
            overflow: hidden;
        }
        .font-serif-lux { font-family: 'Cormorant Garamond', serif; }
        
        .glass-card {
            background: rgba(15, 17, 16, 0.7);
            backdrop-filter: blur(25px);
            border: 1px solid rgba(197, 160, 89, 0.1);
            box-shadow: 0 40px 100px rgba(0,0,0,0.8);
        }

        .gold-input {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
        }
        .gold-input:focus {
            border-color: var(--accent);
            background: rgba(197, 160, 89, 0.05);
            outline: none;
            box-shadow: 0 0 15px rgba(197, 160, 89, 0.1);
        }

        .gold-btn {
            background: linear-gradient(to right, #c5a059, #d4af37, #c5a059);
            color: black;
            font-weight: 900;
            transition: all 0.4s ease;
        }
        .gold-btn:hover {
            transform: translateY(-2px);
            filter: brightness(1.1);
            box-shadow: 0 10px 30px rgba(197, 160, 89, 0.3);
        }
        .gold-btn:active {
            transform: translateY(0);
        }
        
        .gold-glow {
            text-shadow: 0 0 20px rgba(197, 160, 89, 0.3);
        }
    </style>
</head>
<body class="min-h-screen relative flex items-center justify-center p-6">
    <!-- Background Asset -->
    <div class="absolute inset-0 z-0">
        <img src="assets/welcome_bg.png" alt="Abe Hotel Background" class="w-full h-full object-cover opacity-40">
        <div class="absolute inset-0 bg-gradient-to-b from-black/80 via-black/40 to-black/90"></div>
    </div>

    <!-- Header -->
    <header class="absolute top-0 w-full px-8 lg:px-12 py-8 flex justify-between items-center z-50">
        <a href="index.php" class="flex items-center gap-4 hover:opacity-90 transition-opacity">
            <?php if ($logoUrl): ?>
                <div class="w-12 h-12 rounded-full overflow-hidden border-2 border-[#c5a059]/40 bg-black/40 shadow-xl flex-shrink-0">
                    <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="Logo" class="w-full h-full object-cover">
                </div>
            <?php else: ?>
                <div class="w-12 h-12 rounded-full border-2 border-[#c5a059] flex flex-col items-center justify-center p-1 bg-black/40 backdrop-blur-sm shadow-xl">
                    <span class="text-[8px] font-black tracking-widest text-[#c5a059] leading-none mb-0.5"><?php echo htmlspecialchars(strtoupper(substr($appName, 0, 3))); ?></span>
                    <span class="text-[6px] font-bold tracking-[0.2em] text-[#c5a059] leading-none">SYS</span>
                </div>
            <?php endif; ?>
            <div class="hidden sm:block">
                <h2 class="text-[#c5a059] font-black text-lg italic tracking-tight leading-none mb-1"><?php echo htmlspecialchars($appName); ?></h2>
                <p class="text-[8px] text-[#c5a059]/60 font-bold uppercase tracking-widest"><?php echo htmlspecialchars($appTagline); ?></p>
            </div>
        </a>
        <button class="w-10 h-10 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-white/40 hover:bg-white/10 transition-all">
            <i data-lucide="languages" class="w-5 h-5"></i>
        </button>
    </header>

    <!-- Login Container -->
    <div class="relative z-10 w-full max-w-[480px] animate-in fade-in zoom-in duration-1000">
        <div class="glass-card rounded-[2.5rem] p-12 relative">
            <div class="flex flex-col items-center mb-10">
                <!-- Logo -->
                <?php if ($logoUrl): ?>
                    <div class="w-20 h-20 rounded-full overflow-hidden border-2 border-[#c5a059]/40 bg-black/30 shadow-2xl mb-6">
                        <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="Logo" class="w-full h-full object-cover">
                    </div>
                <?php else: ?>
                    <div class="w-16 h-16 rounded-full border-2 border-[#c5a059] flex flex-col items-center justify-center p-1 bg-black/40 backdrop-blur-sm shadow-xl mb-6">
                        <span class="text-[9px] font-black tracking-widest text-[#c5a059] leading-none mb-0.5"><?php echo htmlspecialchars(strtoupper(substr($appName, 0, 3))); ?></span>
                        <span class="text-[7px] font-bold tracking-[0.2em] text-[#c5a059] leading-none">SYS</span>
                    </div>
                <?php endif; ?>
                <h2 class="text-[#c5a059] font-black text-2xl italic tracking-tight leading-none mb-1 text-center"><?php echo htmlspecialchars($appName); ?></h2>
                <p class="text-[8px] text-[#c5a059]/40 font-bold uppercase tracking-widest text-center"><?php echo htmlspecialchars($appTagline); ?></p>
            </div>

            <div class="text-center mb-10">
                <div class="flex items-center justify-center gap-4 mb-2">
                    <span class="w-8 h-[1px] bg-[#c5a059]/30"></span>
                    <h3 class="font-serif-lux italic text-[#c5a059] text-3xl">Welcome Back</h3>
                    <span class="w-8 h-[1px] bg-[#c5a059]/30"></span>
                </div>
                <p class="text-[10px] uppercase font-black tracking-[0.45em] text-[#c5a059]/60">Sign in to your account</p>
            </div>

            <form method="POST" class="space-y-8">
                <?php if ($error): ?>
                    <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-2xl text-[10px] font-black uppercase tracking-widest flex items-center gap-3">
                        <i data-lucide="alert-circle" class="w-4 h-4"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="space-y-2">
                    <label class="text-[9px] font-black uppercase tracking-[0.2em] text-white/30 ml-1">Email Address</label>
                    <input type="email" name="email" placeholder="user@abehotel.com" required 
                           class="gold-input w-full py-4 px-6 rounded-xl text-sm text-white placeholder:text-white/10 appearance-none">
                </div>

                <div class="space-y-2">
                    <label class="text-[9px] font-black uppercase tracking-[0.2em] text-white/30 ml-1">Password</label>
                    <input type="password" name="password" placeholder="••••••••" required 
                           class="gold-input w-full py-4 px-6 rounded-xl text-sm text-white placeholder:text-white/10 appearance-none">
                </div>

                <button type="submit" class="gold-btn w-full py-5 rounded-xl text-[10px] font-black uppercase tracking-[0.3em] shadow-2xl mt-4">
                    Sign In
                </button>
            </form>

            <div class="mt-12 pt-8 border-t border-white/5 text-center">
                <a href="index.php" class="text-[10px] font-black uppercase tracking-[0.4em] text-white/20 hover:text-[#c5a059] transition-colors flex items-center justify-center gap-3 group">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5 group-hover:-translate-x-1 transition-transform"></i>
                    Return to Lobby
                </a>
            </div>
        </div>
    </div>

    <script>lucide.createIcons();</script>
</body>
</html>
