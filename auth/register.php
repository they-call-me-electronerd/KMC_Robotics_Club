<?php
/**
 * KMC Robotics Club - Registration Page
 */

require_once __DIR__ . '/../includes/Security.php';
require_once __DIR__ . '/../includes/Auth.php';

// Start session
Security::startSession();

// Redirect if already logged in
if (Security::isLoggedIn()) {
    $redirect = Security::isAdmin() ? '../admin/dashboard.php' : '../member/dashboard.php';
    header('Location: ' . $redirect);
    exit;
}

$errors = [];
$success = '';
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Invalid request. Please try again.';
    } else {
        $auth = new Auth();
        $result = $auth->register($_POST);
        
        if ($result['success']) {
            header('Location: login.php?registered=1');
            exit;
        } else {
            $errors = $result['errors'] ?? [];
            if (empty($errors) && $result['message']) {
                $errors['general'] = $result['message'];
            }
            $formData = $_POST;
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
    <title>Register | KMC Robotics Club</title>
    
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
        .auth-input.error {
            border-color: #ef4444;
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
        .password-strength {
            height: 4px;
            border-radius: 2px;
            transition: all 0.3s ease;
        }
    </style>
</head>
<body class="antialiased min-h-screen flex items-center justify-center p-4 py-8">
    <canvas id="particle-canvas"></canvas>
    
    <div class="shape-blob one"></div>
    <div class="shape-blob two"></div>
    
    <div class="auth-card rounded-xl p-8 w-full max-w-lg relative">
        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="../index.html" class="inline-block">
                <img src="../assets/images/kmc-rc-logo.png" alt="KMC RC" class="w-16 h-16 mx-auto mb-4">
            </a>
            <h1 class="text-2xl font-bold text-white font-orbitron">Join KMC RC</h1>
            <p class="text-slate-400 mt-2">Create your member account</p>
        </div>
        
        <!-- Error Messages -->
        <?php if (!empty($errors['general'])): ?>
        <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-lg mb-6 flex items-center gap-3">
            <i data-feather="alert-circle" class="w-5 h-5 flex-shrink-0"></i>
            <span><?= htmlspecialchars($errors['general']) ?></span>
        </div>
        <?php endif; ?>
        
        <!-- Registration Form -->
        <form method="POST" action="" class="space-y-5" id="registerForm">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            
            <!-- Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-slate-300 mb-2">Full Name *</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500">
                        <i data-feather="user" class="w-5 h-5"></i>
                    </span>
                    <input type="text" id="name" name="name" required
                           class="auth-input w-full pl-11 pr-4 py-3 rounded-lg text-white placeholder-slate-500 <?= isset($errors['name']) ? 'error' : '' ?>"
                           placeholder="Your full name"
                           value="<?= htmlspecialchars($formData['name'] ?? '') ?>">
                </div>
                <?php if (isset($errors['name'])): ?>
                <p class="mt-1 text-sm text-red-400"><?= htmlspecialchars($errors['name']) ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-slate-300 mb-2">Email Address *</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500">
                        <i data-feather="mail" class="w-5 h-5"></i>
                    </span>
                    <input type="email" id="email" name="email" required
                           class="auth-input w-full pl-11 pr-4 py-3 rounded-lg text-white placeholder-slate-500 <?= isset($errors['email']) ? 'error' : '' ?>"
                           placeholder="you@example.com"
                           value="<?= htmlspecialchars($formData['email'] ?? '') ?>">
                </div>
                <?php if (isset($errors['email'])): ?>
                <p class="mt-1 text-sm text-red-400"><?= htmlspecialchars($errors['email']) ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Phone & Student ID -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="phone" class="block text-sm font-medium text-slate-300 mb-2">Phone Number</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500">
                            <i data-feather="phone" class="w-5 h-5"></i>
                        </span>
                        <input type="tel" id="phone" name="phone"
                               class="auth-input w-full pl-11 pr-4 py-3 rounded-lg text-white placeholder-slate-500"
                               placeholder="98XXXXXXXX"
                               value="<?= htmlspecialchars($formData['phone'] ?? '') ?>">
                    </div>
                </div>
                <div>
                    <label for="student_id" class="block text-sm font-medium text-slate-300 mb-2">Student ID</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500">
                            <i data-feather="credit-card" class="w-5 h-5"></i>
                        </span>
                        <input type="text" id="student_id" name="student_id"
                               class="auth-input w-full pl-11 pr-4 py-3 rounded-lg text-white placeholder-slate-500"
                               placeholder="KMC-XXXX"
                               value="<?= htmlspecialchars($formData['student_id'] ?? '') ?>">
                    </div>
                </div>
            </div>
            
            <!-- Department & Year -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="department" class="block text-sm font-medium text-slate-300 mb-2">Department</label>
                    <select id="department" name="department"
                            class="auth-input w-full px-4 py-3 rounded-lg text-white">
                        <option value="">Select Department</option>
                        <option value="Computer Engineering" <?= ($formData['department'] ?? '') === 'Computer Engineering' ? 'selected' : '' ?>>Computer Engineering</option>
                        <option value="Electronics Engineering" <?= ($formData['department'] ?? '') === 'Electronics Engineering' ? 'selected' : '' ?>>Electronics Engineering</option>
                        <option value="Civil Engineering" <?= ($formData['department'] ?? '') === 'Civil Engineering' ? 'selected' : '' ?>>Civil Engineering</option>
                        <option value="Architecture" <?= ($formData['department'] ?? '') === 'Architecture' ? 'selected' : '' ?>>Architecture</option>
                        <option value="BIT" <?= ($formData['department'] ?? '') === 'BIT' ? 'selected' : '' ?>>BIT</option>
                        <option value="BCA" <?= ($formData['department'] ?? '') === 'BCA' ? 'selected' : '' ?>>BCA</option>
                        <option value="Other" <?= ($formData['department'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div>
                    <label for="year_of_study" class="block text-sm font-medium text-slate-300 mb-2">Year of Study</label>
                    <select id="year_of_study" name="year_of_study"
                            class="auth-input w-full px-4 py-3 rounded-lg text-white">
                        <option value="">Select Year</option>
                        <option value="1" <?= ($formData['year_of_study'] ?? '') == '1' ? 'selected' : '' ?>>1st Year</option>
                        <option value="2" <?= ($formData['year_of_study'] ?? '') == '2' ? 'selected' : '' ?>>2nd Year</option>
                        <option value="3" <?= ($formData['year_of_study'] ?? '') == '3' ? 'selected' : '' ?>>3rd Year</option>
                        <option value="4" <?= ($formData['year_of_study'] ?? '') == '4' ? 'selected' : '' ?>>4th Year</option>
                    </select>
                </div>
            </div>
            
            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-slate-300 mb-2">Password *</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500">
                        <i data-feather="lock" class="w-5 h-5"></i>
                    </span>
                    <input type="password" id="password" name="password" required
                           class="auth-input w-full pl-11 pr-12 py-3 rounded-lg text-white placeholder-slate-500 <?= isset($errors['password']) ? 'error' : '' ?>"
                           placeholder="Min. 8 characters"
                           onkeyup="checkPasswordStrength()">
                    <button type="button" onclick="togglePassword('password')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-accent">
                        <i data-feather="eye" class="w-5 h-5"></i>
                    </button>
                </div>
                <div class="mt-2 bg-slate-800 rounded-full overflow-hidden">
                    <div class="password-strength bg-slate-600" id="passwordStrength" style="width: 0%"></div>
                </div>
                <p class="mt-1 text-xs text-slate-500" id="passwordHint">Use 8+ characters with uppercase, lowercase, numbers & symbols</p>
                <?php if (isset($errors['password'])): ?>
                <p class="mt-1 text-sm text-red-400"><?= htmlspecialchars($errors['password']) ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Confirm Password -->
            <div>
                <label for="confirm_password" class="block text-sm font-medium text-slate-300 mb-2">Confirm Password *</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500">
                        <i data-feather="lock" class="w-5 h-5"></i>
                    </span>
                    <input type="password" id="confirm_password" name="confirm_password" required
                           class="auth-input w-full pl-11 pr-12 py-3 rounded-lg text-white placeholder-slate-500 <?= isset($errors['confirm_password']) ? 'error' : '' ?>"
                           placeholder="Confirm your password">
                    <button type="button" onclick="togglePassword('confirm_password')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-accent">
                        <i data-feather="eye" class="w-5 h-5"></i>
                    </button>
                </div>
                <?php if (isset($errors['confirm_password'])): ?>
                <p class="mt-1 text-sm text-red-400"><?= htmlspecialchars($errors['confirm_password']) ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Terms -->
            <div class="flex items-start gap-3">
                <input type="checkbox" id="terms" name="terms" required
                       class="mt-1 w-4 h-4 rounded border-slate-600 bg-dark-navy text-accent focus:ring-accent">
                <label for="terms" class="text-sm text-slate-400">
                    I agree to the <a href="#" class="text-accent hover:underline">Terms of Service</a> and 
                    <a href="#" class="text-accent hover:underline">Privacy Policy</a>
                </label>
            </div>
            
            <button type="submit" class="auth-btn w-full py-3 rounded-lg text-white font-semibold flex items-center justify-center gap-2">
                <i data-feather="user-plus" class="w-5 h-5"></i>
                Create Account
            </button>
        </form>
        
        <div class="mt-6 text-center">
            <p class="text-slate-400">
                Already have an account? 
                <a href="login.php" class="text-accent hover:underline font-medium">Sign in</a>
            </p>
        </div>
        
        <div class="mt-8 pt-6 border-t border-slate-700/50 text-center">
            <a href="../index.html" class="text-slate-400 hover:text-accent flex items-center justify-center gap-2 text-sm">
                <i data-feather="arrow-left" class="w-4 h-4"></i>
                Back to Home
            </a>
        </div>
    </div>
    
    <script src="../js/main.js"></script>
    <script>
        feather.replace();
        
        function togglePassword(fieldId) {
            const input = document.getElementById(fieldId);
            const icon = input.parentElement.querySelector('[data-feather]');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('data-feather', 'eye-off');
            } else {
                input.type = 'password';
                icon.setAttribute('data-feather', 'eye');
            }
            feather.replace();
        }
        
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthBar = document.getElementById('passwordStrength');
            const hint = document.getElementById('passwordHint');
            
            let strength = 0;
            let color = '#ef4444';
            
            if (password.length >= 8) strength += 25;
            if (/[A-Z]/.test(password)) strength += 25;
            if (/[a-z]/.test(password)) strength += 25;
            if (/[0-9]/.test(password)) strength += 12.5;
            if (/[^A-Za-z0-9]/.test(password)) strength += 12.5;
            
            if (strength <= 25) {
                color = '#ef4444';
                hint.textContent = 'Weak password';
            } else if (strength <= 50) {
                color = '#f59e0b';
                hint.textContent = 'Fair password';
            } else if (strength <= 75) {
                color = '#10b981';
                hint.textContent = 'Good password';
            } else {
                color = '#00f2ff';
                hint.textContent = 'Strong password!';
            }
            
            strengthBar.style.width = strength + '%';
            strengthBar.style.backgroundColor = color;
        }
    </script>
</body>
</html>
