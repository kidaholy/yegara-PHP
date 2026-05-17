<?php
/**
 * Ultimate Luxury Login Page
 */
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (isAuthenticated()) {
    header('Location: admin.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (login($email, $password)) {
        header('Location: admin.php');
        exit;
    } else {
        $error = 'Invalid credentials or account suspended.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Abe Hotel & Spa Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0f1110;
            overflow: hidden;
        }
        .font-playfair { font-family: 'Playfair Display', serif; }
        
        /* Particle Background */
        #particles-canvas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            opacity: 0.4;
        }

        .glass {
            background: rgba(21, 24, 23, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(197, 160, 89, 0.15);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .gold-glow {
            text-shadow: 0 0 20px rgba(197, 160, 89, 0.4);
        }

        .input-gold {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(197, 160, 89, 0.1);
            transition: all 0.3s ease;
        }
        .input-gold:focus {
            border-color: #c5a059;
            background: rgba(197, 160, 89, 0.05);
            outline: none;
            box-shadow: 0 0 0 4px rgba(197, 160, 89, 0.1);
        }

        .gold-btn {
            background: linear-gradient(135deg, #c5a059 0%, #9f7d45 100%);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .gold-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(197, 160, 89, 0.4);
            filter: brightness(1.1);
        }

        .gold-mesh {
            background-image: radial-gradient(#c5a059 0.5px, transparent 0.5px);
            background-size: 32px 32px;
            opacity: 0.05;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">
    <canvas id="particles-canvas"></canvas>
    <div class="fixed inset-0 gold-mesh pointer-events-none"></div>

    <div class="w-full max-w-[440px] px-6 animate-in fade-in zoom-in duration-700">
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-tr from-charcoal-light to-charcoal border border-gold/20 mb-6 shadow-2xl relative group">
                <i data-lucide="building-2" class="w-8 h-8 text-gold group-hover:scale-110 transition-transform"></i>
                <div class="absolute inset-0 bg-gold/10 rounded-2xl blur opacity-0 group-hover:opacity-100 transition-opacity"></div>
            </div>
            <h1 class="text-4xl font-black text-white font-playfair tracking-tighter gold-glow">PRIME ADDIS</h1>
            <p class="text-[10px] uppercase font-bold tracking-[0.3em] text-gold/60 mt-3">Luxury Hotel & Spa Management</p>
        </div>

        <div class="glass p-10 rounded-[2.5rem] relative overflow-hidden">
            <!-- Decorative corner -->
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-gold/10 rounded-full blur-3xl"></div>

            <form method="POST" class="space-y-6 relative">
                <?php if ($error): ?>
                    <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-xl text-xs font-bold flex items-center gap-3">
                        <i data-lucide="alert-circle" class="w-4 h-4"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="space-y-1.5">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-gold/50 ml-1">Identity Access</label>
                    <div class="relative group">
                        <i data-lucide="mail" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gold/30 group-focus-within:text-gold transition-colors"></i>
                        <input type="email" name="email" placeholder="staff@primeaddis.com" required 
                               class="input-gold w-full py-4 pl-12 pr-4 rounded-2xl text-sm text-white placeholder:text-white/10">
                    </div>
                </div>

                <div class="space-y-1.5">
                    <div class="flex justify-between items-center px-1">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-gold/50">Security Key</label>
                        <a href="#" class="text-[10px] font-bold text-gold/40 hover:text-gold transition-colors">Lost Access?</a>
                    </div>
                    <div class="relative group">
                        <i data-lucide="lock" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gold/30 group-focus-within:text-gold transition-colors"></i>
                        <input type="password" name="password" placeholder="••••••••" required 
                               class="input-gold w-full py-4 pl-12 pr-4 rounded-2xl text-sm text-white placeholder:text-white/10">
                    </div>
                </div>

                <button type="submit" class="gold-btn w-full py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest text-charcoal shadow-2xl">
                    Enter Dashboard
                </button>

                <p class="text-[10px] text-center text-white/20 font-medium"> Version 2.0.1 • Secure Hybrid Architecture </p>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();

        // Particle System
        const canvas = document.getElementById('particles-canvas');
        const ctx = canvas.getContext('2d');
        let particles = [];

        function resize() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }
        window.addEventListener('resize', resize);
        resize();

        class Particle {
            constructor() {
                this.reset();
            }
            reset() {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * canvas.height;
                this.size = Math.random() * 1.5 + 0.5;
                this.speedX = (Math.random() - 0.5) * 0.3;
                this.speedY = (Math.random() - 0.5) * 0.3;
                this.opacity = Math.random() * 0.5 + 0.2;
            }
            update() {
                this.x += this.speedX;
                this.y += this.speedY;
                if (this.x < 0 || this.x > canvas.width || this.y < 0 || this.y > canvas.height) this.reset();
            }
            draw() {
                ctx.fillStyle = `rgba(197, 160, 89, ${this.opacity})`;
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fill();
            }
        }

        for (let i = 0; i < 100; i++) particles.push(new Particle());

        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            particles.forEach(p => {
                p.update();
                p.draw();
            });
            requestAnimationFrame(animate);
        }
        animate();
    </script>
</body>
</html>
