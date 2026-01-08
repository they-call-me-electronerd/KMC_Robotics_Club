<?php
/**
 * KMC Robotics Club - About Page
 * Information about the club, mission, and history
 */

require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Security.php';
require_once '../includes/Auth.php';

$db = Database::getInstance();
Security::startSession();

$isLoggedIn = Auth::isLoggedIn();
$currentUser = $isLoggedIn ? Auth::getCurrentUser() : null;

// Get stats
$stats = [
    'members' => $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE status = 'active'")['count'] ?? 0,
    'events' => $db->fetchOne("SELECT COUNT(*) as count FROM events")['count'] ?? 0,
    'projects' => $db->fetchOne("SELECT COUNT(*) as count FROM gallery WHERE category = 'Projects'")['count'] ?? 0
];

// Get settings
$settings = [];
$settingsData = $db->fetchAll("SELECT setting_key, setting_value FROM settings");
foreach ($settingsData as $setting) {
    $settings[$setting['setting_key']] = $setting['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - KMC Robotics Club</title>
    
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
                    <a href="about.php" class="text-accent font-medium">About</a>
                    <a href="events.php" class="text-slate-300 hover:text-accent transition">Events</a>
                    <a href="team.php" class="text-slate-300 hover:text-accent transition">Team</a>
                    <a href="gallery.php" class="text-slate-300 hover:text-accent transition">Gallery</a>
                    <?php if ($isLoggedIn): ?>
                        <a href="<?= $currentUser['role'] === 'admin' ? '../admin/dashboard.php' : '../member/dashboard.php' ?>" class="text-slate-300 hover:text-accent transition">Dashboard</a>
                        <a href="../auth/logout.php" class="bg-red-500/20 text-red-400 px-4 py-2 rounded-lg hover:bg-red-500/30 transition">Logout</a>
                    <?php else: ?>
                        <a href="../auth/login.php" class="text-slate-300 hover:text-accent transition">Login</a>
                        <a href="join.php" class="bg-accent text-dark-navy px-4 py-2 rounded-lg font-semibold hover:bg-accent/80 transition">Join Us</a>
                    <?php endif; ?>
                </div>
                
                <button id="mobile-menu-btn" class="md:hidden text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>
    </nav>
    
    <!-- Page Header -->
    <section class="pt-32 pb-20 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16" data-aos="fade-up">
                <h1 class="font-orbitron text-4xl md:text-5xl font-bold mb-4">
                    About <span class="bg-gradient-to-r from-accent to-secondary-accent bg-clip-text text-transparent">KMC Robotics</span>
                </h1>
                <p class="text-slate-400 text-lg max-w-2xl mx-auto">
                    Pioneering robotics education and innovation in Nepal since 2018
                </p>
            </div>
            
            <!-- Mission & Vision -->
            <div class="grid md:grid-cols-2 gap-8 mb-20">
                <div class="bg-light-navy/50 backdrop-blur-sm rounded-lg p-8 border border-slate-800" data-aos="fade-right">
                    <div class="w-14 h-14 bg-accent/10 rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <h2 class="font-orbitron text-2xl font-bold mb-4">Our Mission</h2>
                    <p class="text-slate-300">
                        To empower students with practical skills in robotics, artificial intelligence, and engineering 
                        through hands-on learning experiences, fostering innovation and preparing them for the 
                        technological challenges of tomorrow.
                    </p>
                </div>
                
                <div class="bg-light-navy/50 backdrop-blur-sm rounded-lg p-8 border border-slate-800" data-aos="fade-left">
                    <div class="w-14 h-14 bg-purple-500/10 rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </div>
                    <h2 class="font-orbitron text-2xl font-bold mb-4">Our Vision</h2>
                    <p class="text-slate-300">
                        To be Nepal's leading student robotics community, recognized for producing innovative 
                        tech leaders who contribute to solving real-world problems through technology and 
                        drive the nation's digital transformation.
                    </p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Stats Section -->
    <section class="py-20 bg-light-navy/30 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <div class="text-center" data-aos="fade-up">
                    <div class="text-4xl md:text-5xl font-orbitron font-bold text-accent mb-2"><?= $stats['members'] ?>+</div>
                    <div class="text-slate-400">Active Members</div>
                </div>
                <div class="text-center" data-aos="fade-up" data-aos-delay="100">
                    <div class="text-4xl md:text-5xl font-orbitron font-bold text-accent mb-2"><?= $stats['events'] ?>+</div>
                    <div class="text-slate-400">Events Hosted</div>
                </div>
                <div class="text-center" data-aos="fade-up" data-aos-delay="200">
                    <div class="text-4xl md:text-5xl font-orbitron font-bold text-accent mb-2"><?= $stats['projects'] ?>+</div>
                    <div class="text-slate-400">Projects Built</div>
                </div>
                <div class="text-center" data-aos="fade-up" data-aos-delay="300">
                    <div class="text-4xl md:text-5xl font-orbitron font-bold text-accent mb-2">15+</div>
                    <div class="text-slate-400">Awards Won</div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- What We Do -->
    <section class="py-20 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16" data-aos="fade-up">
                <span class="text-accent font-medium">Our Activities</span>
                <h2 class="font-orbitron text-3xl md:text-4xl font-bold mt-2">What We Do</h2>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-light-navy/50 backdrop-blur-sm rounded-lg p-8 border border-slate-800 hover:border-accent/30 transition" data-aos="fade-up">
                    <div class="w-14 h-14 bg-accent/10 rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Workshops & Training</h3>
                    <p class="text-slate-400">
                        Regular workshops on Arduino, Raspberry Pi, Python, machine learning, and more. 
                        Learn from experienced mentors and industry experts.
                    </p>
                </div>
                
                <div class="bg-light-navy/50 backdrop-blur-sm rounded-lg p-8 border border-slate-800 hover:border-accent/30 transition" data-aos="fade-up" data-aos-delay="100">
                    <div class="w-14 h-14 bg-purple-500/10 rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Project Development</h3>
                    <p class="text-slate-400">
                        Work on real-world projects from concept to completion. Build robots, drones, 
                        IoT devices, and AI applications as part of our project teams.
                    </p>
                </div>
                
                <div class="bg-light-navy/50 backdrop-blur-sm rounded-lg p-8 border border-slate-800 hover:border-accent/30 transition" data-aos="fade-up" data-aos-delay="200">
                    <div class="w-14 h-14 bg-green-500/10 rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Competitions</h3>
                    <p class="text-slate-400">
                        Participate in national and international robotics competitions. 
                        We've won multiple awards and recognition for our innovative projects.
                    </p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Focus Areas -->
    <section class="py-20 bg-light-navy/30 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16" data-aos="fade-up">
                <span class="text-accent font-medium">Expertise</span>
                <h2 class="font-orbitron text-3xl md:text-4xl font-bold mt-2">Our Focus Areas</h2>
            </div>
            
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-dark-navy/50 rounded-lg p-6 border border-slate-800 text-center" data-aos="fade-up">
                    <div class="text-4xl mb-4">🤖</div>
                    <h3 class="font-semibold text-lg mb-2">Robotics</h3>
                    <p class="text-slate-400 text-sm">Autonomous robots, manipulators, and mobile platforms</p>
                </div>
                <div class="bg-dark-navy/50 rounded-lg p-6 border border-slate-800 text-center" data-aos="fade-up" data-aos-delay="50">
                    <div class="text-4xl mb-4">🧠</div>
                    <h3 class="font-semibold text-lg mb-2">AI & ML</h3>
                    <p class="text-slate-400 text-sm">Machine learning, computer vision, and neural networks</p>
                </div>
                <div class="bg-dark-navy/50 rounded-lg p-6 border border-slate-800 text-center" data-aos="fade-up" data-aos-delay="100">
                    <div class="text-4xl mb-4">📡</div>
                    <h3 class="font-semibold text-lg mb-2">IoT</h3>
                    <p class="text-slate-400 text-sm">Smart devices, sensors, and connected systems</p>
                </div>
                <div class="bg-dark-navy/50 rounded-lg p-6 border border-slate-800 text-center" data-aos="fade-up" data-aos-delay="150">
                    <div class="text-4xl mb-4">🚁</div>
                    <h3 class="font-semibold text-lg mb-2">Drones</h3>
                    <p class="text-slate-400 text-sm">UAV design, autonomous flight, and aerial robotics</p>
                </div>
                <div class="bg-dark-navy/50 rounded-lg p-6 border border-slate-800 text-center" data-aos="fade-up" data-aos-delay="200">
                    <div class="text-4xl mb-4">⚡</div>
                    <h3 class="font-semibold text-lg mb-2">Electronics</h3>
                    <p class="text-slate-400 text-sm">Circuit design, PCB development, and embedded systems</p>
                </div>
                <div class="bg-dark-navy/50 rounded-lg p-6 border border-slate-800 text-center" data-aos="fade-up" data-aos-delay="250">
                    <div class="text-4xl mb-4">🖨️</div>
                    <h3 class="font-semibold text-lg mb-2">3D Printing</h3>
                    <p class="text-slate-400 text-sm">Additive manufacturing and rapid prototyping</p>
                </div>
                <div class="bg-dark-navy/50 rounded-lg p-6 border border-slate-800 text-center" data-aos="fade-up" data-aos-delay="300">
                    <div class="text-4xl mb-4">💻</div>
                    <h3 class="font-semibold text-lg mb-2">Programming</h3>
                    <p class="text-slate-400 text-sm">Python, C++, ROS, and embedded programming</p>
                </div>
                <div class="bg-dark-navy/50 rounded-lg p-6 border border-slate-800 text-center" data-aos="fade-up" data-aos-delay="350">
                    <div class="text-4xl mb-4">🔧</div>
                    <h3 class="font-semibold text-lg mb-2">CAD/CAM</h3>
                    <p class="text-slate-400 text-sm">3D modeling, simulation, and manufacturing</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Timeline -->
    <section class="py-20 relative z-10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16" data-aos="fade-up">
                <span class="text-accent font-medium">Our Journey</span>
                <h2 class="font-orbitron text-3xl md:text-4xl font-bold mt-2">Club History</h2>
            </div>
            
            <div class="relative">
                <!-- Timeline line -->
                <div class="absolute left-1/2 transform -translate-x-1/2 w-0.5 h-full bg-slate-700"></div>
                
                <!-- Timeline items -->
                <div class="space-y-12">
                    <div class="relative flex items-center" data-aos="fade-up">
                        <div class="flex-1 text-right pr-8 md:pr-12">
                            <h3 class="font-bold text-lg text-white">Club Founded</h3>
                            <p class="text-slate-400 text-sm mt-1">Started with 15 founding members</p>
                        </div>
                        <div class="absolute left-1/2 transform -translate-x-1/2 w-4 h-4 bg-accent rounded-full border-4 border-dark-navy"></div>
                        <div class="flex-1 pl-8 md:pl-12">
                            <span class="text-accent font-orbitron">2018</span>
                        </div>
                    </div>
                    
                    <div class="relative flex items-center" data-aos="fade-up">
                        <div class="flex-1 text-right pr-8 md:pr-12">
                            <span class="text-accent font-orbitron">2019</span>
                        </div>
                        <div class="absolute left-1/2 transform -translate-x-1/2 w-4 h-4 bg-purple-500 rounded-full border-4 border-dark-navy"></div>
                        <div class="flex-1 pl-8 md:pl-12">
                            <h3 class="font-bold text-lg text-white">First Competition Win</h3>
                            <p class="text-slate-400 text-sm mt-1">Won national robotics competition</p>
                        </div>
                    </div>
                    
                    <div class="relative flex items-center" data-aos="fade-up">
                        <div class="flex-1 text-right pr-8 md:pr-12">
                            <h3 class="font-bold text-lg text-white">Lab Established</h3>
                            <p class="text-slate-400 text-sm mt-1">Dedicated robotics lab opened</p>
                        </div>
                        <div class="absolute left-1/2 transform -translate-x-1/2 w-4 h-4 bg-green-500 rounded-full border-4 border-dark-navy"></div>
                        <div class="flex-1 pl-8 md:pl-12">
                            <span class="text-accent font-orbitron">2020</span>
                        </div>
                    </div>
                    
                    <div class="relative flex items-center" data-aos="fade-up">
                        <div class="flex-1 text-right pr-8 md:pr-12">
                            <span class="text-accent font-orbitron">2022</span>
                        </div>
                        <div class="absolute left-1/2 transform -translate-x-1/2 w-4 h-4 bg-yellow-500 rounded-full border-4 border-dark-navy"></div>
                        <div class="flex-1 pl-8 md:pl-12">
                            <h3 class="font-bold text-lg text-white">100+ Members</h3>
                            <p class="text-slate-400 text-sm mt-1">Reached milestone membership</p>
                        </div>
                    </div>
                    
                    <div class="relative flex items-center" data-aos="fade-up">
                        <div class="flex-1 text-right pr-8 md:pr-12">
                            <h3 class="font-bold text-lg text-white">AI Division Launched</h3>
                            <p class="text-slate-400 text-sm mt-1">Expanded into ML and AI projects</p>
                        </div>
                        <div class="absolute left-1/2 transform -translate-x-1/2 w-4 h-4 bg-accent rounded-full border-4 border-dark-navy"></div>
                        <div class="flex-1 pl-8 md:pl-12">
                            <span class="text-accent font-orbitron">2024</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- CTA Section -->
    <section class="py-20 relative z-10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center" data-aos="fade-up">
            <div class="bg-gradient-to-br from-accent/10 to-secondary-accent/10 rounded-2xl p-12 border border-accent/20">
                <h2 class="font-orbitron text-3xl font-bold mb-4">Ready to Join?</h2>
                <p class="text-slate-400 mb-8 max-w-xl mx-auto">
                    Be part of Nepal's most innovative student robotics community. 
                    Learn, build, and create with us.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="join.php" class="bg-accent text-dark-navy px-8 py-3 rounded-lg font-semibold hover:bg-accent/80 transition inline-flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                        Join the Club
                    </a>
                    <a href="events.php" class="border border-accent text-accent px-8 py-3 rounded-lg font-semibold hover:bg-accent/10 transition inline-flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        View Events
                    </a>
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
                    <a href="events.php" class="hover:text-accent transition">Events</a>
                    <a href="team.php" class="hover:text-accent transition">Team</a>
                </div>
            </div>
        </div>
    </footer>
    
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="../js/main.js"></script>
    <script>
        AOS.init({ duration: 800, once: true });
        
        document.getElementById('mobile-menu-btn').addEventListener('click', () => {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });
    </script>
</body>
</html>
