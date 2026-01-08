<?php
/**
 * KMC Robotics Club - Join/Register Page
 * Public membership application form
 */

require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Security.php';
require_once '../includes/Auth.php';

$db = Database::getInstance();
Security::startSession();

// Redirect if already logged in
if (Auth::isLoggedIn()) {
    $user = Auth::getCurrentUser();
    header('Location: ' . ($user['role'] === 'admin' ? '../admin/dashboard.php' : '../member/dashboard.php'));
    exit;
}

// Check if registration is enabled
$registrationEnabled = $db->fetchOne("SELECT setting_value FROM settings WHERE setting_key = 'registration_enabled'");
$registrationEnabled = $registrationEnabled ? $registrationEnabled['setting_value'] === '1' : true;

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $registrationEnabled) {
    // Validate CSRF
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $phone = trim($_POST['phone'] ?? '');
        
        // Validation
        if (empty($fullName)) {
            $errors[] = 'Full name is required.';
        } elseif (strlen($fullName) < 2) {
            $errors[] = 'Name must be at least 2 characters.';
        }
        
        if (empty($email)) {
            $errors[] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } else {
            // Check if email exists
            $existing = $db->fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
            if ($existing) {
                $errors[] = 'This email is already registered. <a href="../auth/login.php" class="text-accent hover:underline">Login instead?</a>';
            }
        }
        
        if (empty($password)) {
            $errors[] = 'Password is required.';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain uppercase, lowercase, and numbers.';
        }
        
        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }
        
        if (empty($errors)) {
            $result = Auth::register($email, $password, $fullName, $phone);
            
            if ($result['success']) {
                $success = true;
            } else {
                $errors[] = $result['message'];
            }
        }
    }
}

$csrfToken = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Us - KMC Robotics Club</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'dark-navy': '#050a14',
                        'light-navy': '#0a1628',
                        'accent': '#00f2ff',
                        'secondary-accent': '#7000ff'
                    },
                    fontFamily: {
                        'orbitron': ['Orbitron', 'sans-serif'],
                        'rajdhani': ['Rajdhani', 'sans-serif']
                    }
                }
            }
        }
    </script>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body class="bg-dark-navy text-white font-rajdhani min-h-screen">
    <!-- Particle Background -->
    <div id="particles-container" class="fixed inset-0 z-0"></div>
    
    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-dark-navy/80 backdrop-blur-md border-b border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="../index.php" class="flex items-center gap-2">
                    <div class="w-10 h-10 bg-gradient-to-br from-accent to-secondary-accent rounded-lg flex items-center justify-center">
                        <span class="font-orbitron font-bold text-dark-navy">KR</span>
                    </div>
                    <span class="font-orbitron font-bold text-lg hidden sm:block">KMC Robotics</span>
                </a>
                
                <div class="hidden md:flex items-center gap-6">
                    <a href="../index.php" class="text-slate-300 hover:text-accent transition">Home</a>
                    <a href="about.php" class="text-slate-300 hover:text-accent transition">About</a>
                    <a href="events.php" class="text-slate-300 hover:text-accent transition">Events</a>
                    <a href="team.php" class="text-slate-300 hover:text-accent transition">Team</a>
                    <a href="gallery.php" class="text-slate-300 hover:text-accent transition">Gallery</a>
                    <a href="../auth/login.php" class="text-slate-300 hover:text-accent transition">Login</a>
                    <a href="join.php" class="bg-accent text-dark-navy px-4 py-2 rounded-lg font-semibold">Join Us</a>
                </div>
                
                <button id="mobile-menu-btn" class="md:hidden text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <section class="min-h-screen pt-24 pb-12 flex items-center relative z-10">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- Left Column - Info -->
                <div data-aos="fade-right">
                    <h1 class="font-orbitron text-4xl md:text-5xl font-bold mb-6">
                        Join the <span class="bg-gradient-to-r from-accent to-secondary-accent bg-clip-text text-transparent">Future</span>
                    </h1>
                    <p class="text-slate-300 text-lg mb-8">
                        Become a part of Nepal's leading robotics community. Learn cutting-edge technology, 
                        participate in competitions, and build projects that matter.
                    </p>
                    
                    <div class="space-y-4 mb-8">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-accent/10 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-white">Learn from Experts</h3>
                                <p class="text-slate-400 text-sm">Access workshops and mentorship from industry professionals</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-purple-500/10 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-white">Build Real Projects</h3>
                                <p class="text-slate-400 text-sm">Work on hands-on robotics and AI projects</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-green-500/10 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-white">Join a Community</h3>
                                <p class="text-slate-400 text-sm">Connect with like-minded innovators and builders</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-yellow-500/10 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-white">Compete & Win</h3>
                                <p class="text-slate-400 text-sm">Participate in national and international competitions</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column - Form -->
                <div data-aos="fade-left">
                    <?php if (!$registrationEnabled): ?>
                        <div class="bg-light-navy/50 backdrop-blur-sm rounded-lg p-8 border border-slate-800 text-center">
                            <svg class="w-16 h-16 mx-auto text-yellow-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <h2 class="font-orbitron text-2xl font-bold mb-4">Registration Closed</h2>
                            <p class="text-slate-400 mb-6">New member registrations are currently closed. Please check back later or contact us for more information.</p>
                            <a href="../index.php" class="inline-flex items-center gap-2 border border-accent text-accent px-6 py-3 rounded-lg hover:bg-accent/10 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                </svg>
                                Back to Home
                            </a>
                        </div>
                    <?php elseif ($success): ?>
                        <div class="bg-light-navy/50 backdrop-blur-sm rounded-lg p-8 border border-green-500/30 text-center">
                            <svg class="w-16 h-16 mx-auto text-green-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <h2 class="font-orbitron text-2xl font-bold mb-4 text-green-400">Welcome Aboard!</h2>
                            <p class="text-slate-300 mb-6">Your account has been created successfully. You can now log in to access your member dashboard.</p>
                            <a href="../auth/login.php" class="inline-flex items-center gap-2 bg-accent text-dark-navy px-6 py-3 rounded-lg font-semibold hover:bg-accent/80 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                                </svg>
                                Login Now
                            </a>
                        </div>
                    <?php else: ?>
                        <form method="POST" class="bg-light-navy/50 backdrop-blur-sm rounded-lg p-8 border border-slate-800">
                            <h2 class="font-orbitron text-2xl font-bold mb-6">Create Account</h2>
                            
                            <?php if (!empty($errors)): ?>
                                <div class="bg-red-500/10 border border-red-500/30 rounded-lg p-4 mb-6">
                                    <ul class="text-red-400 text-sm space-y-1">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?= $error ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            
                            <div class="mb-5">
                                <label class="block text-slate-300 mb-2">Full Name *</label>
                                <input type="text" name="full_name" required
                                       value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                                       class="w-full bg-dark-navy border border-slate-700 rounded-lg px-4 py-3 text-white placeholder-slate-500 focus:border-accent focus:outline-none"
                                       placeholder="Enter your full name">
                            </div>
                            
                            <div class="mb-5">
                                <label class="block text-slate-300 mb-2">Email Address *</label>
                                <input type="email" name="email" required
                                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                       class="w-full bg-dark-navy border border-slate-700 rounded-lg px-4 py-3 text-white placeholder-slate-500 focus:border-accent focus:outline-none"
                                       placeholder="you@example.com">
                            </div>
                            
                            <div class="mb-5">
                                <label class="block text-slate-300 mb-2">Phone Number</label>
                                <input type="tel" name="phone"
                                       value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                                       class="w-full bg-dark-navy border border-slate-700 rounded-lg px-4 py-3 text-white placeholder-slate-500 focus:border-accent focus:outline-none"
                                       placeholder="+977-98XXXXXXXX">
                            </div>
                            
                            <div class="mb-5">
                                <label class="block text-slate-300 mb-2">Password *</label>
                                <input type="password" name="password" required
                                       class="w-full bg-dark-navy border border-slate-700 rounded-lg px-4 py-3 text-white placeholder-slate-500 focus:border-accent focus:outline-none"
                                       placeholder="Min 8 characters">
                                <p class="text-slate-500 text-xs mt-1">Must contain uppercase, lowercase, and numbers</p>
                            </div>
                            
                            <div class="mb-6">
                                <label class="block text-slate-300 mb-2">Confirm Password *</label>
                                <input type="password" name="confirm_password" required
                                       class="w-full bg-dark-navy border border-slate-700 rounded-lg px-4 py-3 text-white placeholder-slate-500 focus:border-accent focus:outline-none"
                                       placeholder="Re-enter password">
                            </div>
                            
                            <button type="submit" 
                                    class="w-full bg-accent text-dark-navy py-3 rounded-lg font-semibold hover:bg-accent/80 transition flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                </svg>
                                Create Account
                            </button>
                            
                            <p class="text-center text-slate-400 text-sm mt-6">
                                Already have an account? 
                                <a href="../auth/login.php" class="text-accent hover:underline">Login here</a>
                            </p>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="bg-light-navy/50 border-t border-slate-800 py-8 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between text-sm text-slate-400">
                <p>&copy; <?= date('Y') ?> KMC Robotics Club. All rights reserved.</p>
                <div class="flex gap-4 mt-4 md:mt-0">
                    <a href="../index.php" class="hover:text-accent transition">Home</a>
                    <a href="about.php" class="hover:text-accent transition">About</a>
                    <a href="events.php" class="hover:text-accent transition">Events</a>
                </div>
            </div>
        </div>
    </footer>
    
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="../js/main.js"></script>
    <script>
        AOS.init({ duration: 800, once: true });
        
        document.getElementById('mobile-menu-btn')?.addEventListener('click', () => {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });
    </script>
</body>
</html>
