<?php
/**
 * KMC Robotics Club - Forgot Password Page
 */

require_once __DIR__ . '/../includes/Security.php';
require_once __DIR__ . '/../includes/Auth.php';

// Start session
Security::startSession();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $auth = new Auth();
        $result = $auth->requestPasswordReset($_POST['email'] ?? '');
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

$csrfToken = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="theme-color" content="#050a14" />
    <link rel="icon" type="image/png" href="../assets/images/kmc-rc-logo.png">
    <title>Forgot Password | KMC Robotics Club</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        'dark-navy': '#050a14',
                        'light-navy': '#0f1a2e',
                        'slate': '#94a3b8',
                        'light-slate': '#e2e8f0',
                        'accent': '#00f2ff',
                        'accent-glow': 'rgba(0, 242, 255, 0.2)',
                        'secondary-accent': '#7000ff',
                    },
                    fontFamily: {
                        'sci': ['Orbitron', 'sans-serif'],
                        'tech': ['Rajdhani', 'sans-serif'],
                        'mono': ['Roboto Mono', 'monospace'],
                    },
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Orbitron:wght@400;500;600;700&family=Rajdhani:wght@400;500;600;700&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet"/>
    <script src="https://unpkg.com/feather-icons"></script>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/club-styles.css">
    <style>
        .auth-card {
            background: rgba(15, 26, 46, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(0, 242, 255, 0.1);
        }
        .auth-input {
            background: rgba(5, 10, 20, 0.6);
            border: 1px solid rgba(0, 242, 255, 0.2);
            transition: all 0.3s ease;
        }
        .auth-input:focus {
            border-color: #00f2ff;
            box-shadow: 0 0 15px rgba(0, 242, 255, 0.2);
            outline: none;
        }
        .auth-btn {
            background: linear-gradient(135deg, rgba(0, 242, 255, 0.2), rgba(112, 0, 255, 0.2));
            border: 1px solid rgba(0, 242, 255, 0.5);
            transition: all 0.3s ease;
        }
        .auth-btn:hover {
            background: linear-gradient(135deg, rgba(0, 242, 255, 0.3), rgba(112, 0, 255, 0.3));
            box-shadow: 0 0 20px rgba(0, 242, 255, 0.3);
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="antialiased min-h-screen flex items-center justify-center p-4">
    <canvas id="particle-canvas"></canvas>
    
    <div class="shape-blob one"></div>
    <div class="shape-blob two"></div>
    
    <div class="auth-card rounded-xl p-8 w-full max-w-md relative">
        <div class="text-center mb-8">
            <a href="../index.html" class="inline-block">
                <img src="../assets/images/kmc-rc-logo.png" alt="KMC RC" class="w-16 h-16 mx-auto mb-4">
            </a>
            <h1 class="text-2xl font-bold text-white font-orbitron">Reset Password</h1>
            <p class="text-slate-400 mt-2">Enter your email to receive a reset link</p>
        </div>
        
        <?php if ($error): ?>
        <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-lg mb-6 flex items-center gap-3">
            <i data-feather="alert-circle" class="w-5 h-5"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="bg-green-500/10 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg mb-6 flex items-center gap-3">
            <i data-feather="check-circle" class="w-5 h-5"></i>
            <span><?= htmlspecialchars($success) ?></span>
        </div>
        <?php else: ?>
        
        <form method="POST" action="" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            
            <div>
                <label for="email" class="block text-sm font-medium text-slate-300 mb-2">Email Address</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500">
                        <i data-feather="mail" class="w-5 h-5"></i>
                    </span>
                    <input type="email" id="email" name="email" required
                           class="auth-input w-full pl-11 pr-4 py-3 rounded-lg text-white placeholder-slate-500"
                           placeholder="you@example.com">
                </div>
            </div>
            
            <button type="submit" class="auth-btn w-full py-3 rounded-lg text-white font-semibold flex items-center justify-center gap-2">
                <i data-feather="send" class="w-5 h-5"></i>
                Send Reset Link
            </button>
        </form>
        <?php endif; ?>
        
        <div class="mt-6 text-center">
            <a href="login.php" class="text-accent hover:underline flex items-center justify-center gap-2">
                <i data-feather="arrow-left" class="w-4 h-4"></i>
                Back to Login
            </a>
        </div>
    </div>
    
    <script src="../js/main.js"></script>
    <script>feather.replace();</script>
</body>
</html>
