<?php
/**
 * KMC Robotics Club - Homepage
 * Dynamic content loaded from database
 */

require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/Security.php';
require_once 'includes/Auth.php';

$db = Database::getInstance();
Security::startSession();

// Get site settings
$settings = [];
$settingsData = $db->fetchAll("SELECT setting_key, setting_value FROM settings");
foreach ($settingsData as $setting) {
    $settings[$setting['setting_key']] = $setting['setting_value'];
}

// Get stats
$stats = [
    'members' => $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE status = 'active'")['count'] ?? 0,
    'events' => $db->fetchOne("SELECT COUNT(*) as count FROM events WHERE status != 'cancelled'")['count'] ?? 0,
    'projects' => $db->fetchOne("SELECT COUNT(*) as count FROM gallery WHERE is_approved = 1 AND category = 'Projects'")['count'] ?? 0,
    'awards' => 15 // Static for now
];

// Get upcoming events
$upcomingEvents = $db->fetchAll(
    "SELECT * FROM events WHERE status IN ('upcoming', 'ongoing') AND event_date >= CURDATE() ORDER BY event_date ASC LIMIT 3"
);

// Get featured gallery items
$featuredGallery = $db->fetchAll(
    "SELECT * FROM gallery WHERE is_approved = 1 AND is_featured = 1 ORDER BY created_at DESC LIMIT 6"
);

// Get executive team
$executiveTeam = $db->fetchAll(
    "SELECT * FROM team_members WHERE is_active = 1 AND category = 'executive' ORDER BY position_order ASC LIMIT 4"
);

$isLoggedIn = Auth::isLoggedIn();
$currentUser = $isLoggedIn ? Auth::getCurrentUser() : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($settings['site_name'] ?? 'KMC Robotics Club') ?> - Building Tomorrow's Innovators</title>
    <meta name="description" content="<?= htmlspecialchars($settings['site_description'] ?? 'KMC Robotics Club - Empowering students through robotics, AI, and engineering education') ?>">
    
    <!-- Tailwind CSS CDN -->
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
                        'rajdhani': ['Rajdhani', 'sans-serif'],
                        'roboto-mono': ['Roboto Mono', 'monospace']
                    }
                }
            }
        }
    </script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700&family=Rajdhani:wght@300;400;500;600;700&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/club-styles.css">
</head>
<body class="bg-dark-navy text-white font-rajdhani overflow-x-hidden">
    <!-- Particle Background -->
    <div id="particles-container" class="fixed inset-0 z-0"></div>
    
    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-dark-navy/80 backdrop-blur-md border-b border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <a href="index.php" class="flex items-center gap-2">
                    <div class="w-10 h-10 bg-gradient-to-br from-accent to-secondary-accent rounded-lg flex items-center justify-center">
                        <span class="font-orbitron font-bold text-dark-navy">KR</span>
                    </div>
                    <span class="font-orbitron font-bold text-lg hidden sm:block">KMC Robotics</span>
                </a>
                
                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center gap-6">
                    <a href="index.php" class="text-accent font-medium">Home</a>
                    <a href="pages/about.php" class="text-slate-300 hover:text-accent transition">About</a>
                    <a href="pages/events.php" class="text-slate-300 hover:text-accent transition">Events</a>
                    <a href="pages/team.php" class="text-slate-300 hover:text-accent transition">Team</a>
                    <a href="pages/gallery.php" class="text-slate-300 hover:text-accent transition">Gallery</a>
                    <?php if ($isLoggedIn): ?>
                        <?php if ($currentUser['role'] === 'admin'): ?>
                            <a href="admin/dashboard.php" class="text-slate-300 hover:text-accent transition">Admin</a>
                        <?php else: ?>
                            <a href="member/dashboard.php" class="text-slate-300 hover:text-accent transition">Dashboard</a>
                        <?php endif; ?>
                        <a href="auth/logout.php" class="bg-red-500/20 text-red-400 px-4 py-2 rounded-lg hover:bg-red-500/30 transition">Logout</a>
                    <?php else: ?>
                        <a href="auth/login.php" class="text-slate-300 hover:text-accent transition">Login</a>
                        <a href="pages/join.php" class="bg-accent text-dark-navy px-4 py-2 rounded-lg font-semibold hover:bg-accent/80 transition">Join Us</a>
                    <?php endif; ?>
                </div>
                
                <!-- Mobile Menu Button -->
                <button id="mobile-menu-btn" class="md:hidden text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-light-navy border-t border-slate-800">
            <div class="px-4 py-4 space-y-2">
                <a href="index.php" class="block text-accent py-2">Home</a>
                <a href="pages/about.php" class="block text-slate-300 hover:text-accent py-2">About</a>
                <a href="pages/events.php" class="block text-slate-300 hover:text-accent py-2">Events</a>
                <a href="pages/team.php" class="block text-slate-300 hover:text-accent py-2">Team</a>
                <a href="pages/gallery.php" class="block text-slate-300 hover:text-accent py-2">Gallery</a>
                <?php if ($isLoggedIn): ?>
                    <?php if ($currentUser['role'] === 'admin'): ?>
                        <a href="admin/dashboard.php" class="block text-slate-300 hover:text-accent py-2">Admin</a>
                    <?php else: ?>
                        <a href="member/dashboard.php" class="block text-slate-300 hover:text-accent py-2">Dashboard</a>
                    <?php endif; ?>
                    <a href="auth/logout.php" class="block text-red-400 py-2">Logout</a>
                <?php else: ?>
                    <a href="auth/login.php" class="block text-slate-300 hover:text-accent py-2">Login</a>
                    <a href="pages/join.php" class="block bg-accent text-dark-navy px-4 py-2 rounded-lg font-semibold text-center">Join Us</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <section class="relative min-h-screen flex items-center justify-center pt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
            <div data-aos="fade-up" data-aos-duration="1000">
                <h1 class="font-orbitron text-4xl md:text-6xl lg:text-7xl font-bold mb-6">
                    <span class="text-white">Building</span>
                    <span class="bg-gradient-to-r from-accent to-secondary-accent bg-clip-text text-transparent"> Tomorrow's</span>
                    <br>
                    <span class="text-white">Innovators</span>
                </h1>
                <p class="text-slate-300 text-lg md:text-xl max-w-2xl mx-auto mb-8">
                    Join Nepal's premier student robotics community. Learn, build, and innovate with cutting-edge technology.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="pages/join.php" class="bg-accent text-dark-navy px-8 py-3 rounded-lg font-semibold text-lg hover:bg-accent/80 transition transform hover:scale-105">
                        Join the Club
                    </a>
                    <a href="pages/events.php" class="border border-accent text-accent px-8 py-3 rounded-lg font-semibold text-lg hover:bg-accent/10 transition">
                        Explore Events
                    </a>
                </div>
            </div>
            
            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mt-16" data-aos="fade-up" data-aos-delay="200">
                <div class="bg-light-navy/50 backdrop-blur-sm rounded-lg p-6 border border-slate-800">
                    <div class="text-3xl md:text-4xl font-orbitron font-bold text-accent"><?= $stats['members'] ?>+</div>
                    <div class="text-slate-400 mt-2">Active Members</div>
                </div>
                <div class="bg-light-navy/50 backdrop-blur-sm rounded-lg p-6 border border-slate-800">
                    <div class="text-3xl md:text-4xl font-orbitron font-bold text-accent"><?= $stats['events'] ?>+</div>
                    <div class="text-slate-400 mt-2">Events Hosted</div>
                </div>
                <div class="bg-light-navy/50 backdrop-blur-sm rounded-lg p-6 border border-slate-800">
                    <div class="text-3xl md:text-4xl font-orbitron font-bold text-accent"><?= $stats['projects'] ?>+</div>
                    <div class="text-slate-400 mt-2">Projects Built</div>
                </div>
                <div class="bg-light-navy/50 backdrop-blur-sm rounded-lg p-6 border border-slate-800">
                    <div class="text-3xl md:text-4xl font-orbitron font-bold text-accent"><?= $stats['awards'] ?>+</div>
                    <div class="text-slate-400 mt-2">Awards Won</div>
                </div>
            </div>
        </div>
        
        <!-- Scroll Indicator -->
        <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce">
            <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
            </svg>
        </div>
    </section>
    
    <!-- About Section -->
    <section class="py-20 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <div data-aos="fade-right">
                    <span class="text-accent font-medium">About Us</span>
                    <h2 class="font-orbitron text-3xl md:text-4xl font-bold mt-2 mb-6">
                        Pioneering Robotics Education in Nepal
                    </h2>
                    <p class="text-slate-300 mb-4">
                        KMC Robotics Club is a student-led organization dedicated to fostering innovation and technical excellence in robotics, artificial intelligence, and engineering.
                    </p>
                    <p class="text-slate-300 mb-6">
                        Founded with the vision of creating Nepal's next generation of tech leaders, we provide hands-on learning experiences, workshops, and competitions that prepare students for the future.
                    </p>
                    <a href="pages/about.php" class="inline-flex items-center gap-2 text-accent hover:underline">
                        Learn More 
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
                <div class="grid grid-cols-2 gap-4" data-aos="fade-left">
                    <div class="bg-gradient-to-br from-accent/20 to-transparent rounded-lg p-6 border border-accent/20">
                        <svg class="w-10 h-10 text-accent mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                        <h3 class="font-semibold text-white mb-2">Innovation</h3>
                        <p class="text-slate-400 text-sm">Pushing boundaries with cutting-edge technology</p>
                    </div>
                    <div class="bg-gradient-to-br from-secondary-accent/20 to-transparent rounded-lg p-6 border border-secondary-accent/20">
                        <svg class="w-10 h-10 text-secondary-accent mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        <h3 class="font-semibold text-white mb-2">Learning</h3>
                        <p class="text-slate-400 text-sm">Hands-on workshops and mentorship</p>
                    </div>
                    <div class="bg-gradient-to-br from-purple-500/20 to-transparent rounded-lg p-6 border border-purple-500/20">
                        <svg class="w-10 h-10 text-purple-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <h3 class="font-semibold text-white mb-2">Community</h3>
                        <p class="text-slate-400 text-sm">Building connections that last</p>
                    </div>
                    <div class="bg-gradient-to-br from-green-500/20 to-transparent rounded-lg p-6 border border-green-500/20">
                        <svg class="w-10 h-10 text-green-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                        </svg>
                        <h3 class="font-semibold text-white mb-2">Excellence</h3>
                        <p class="text-slate-400 text-sm">Award-winning projects and teams</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Upcoming Events -->
    <?php if (!empty($upcomingEvents)): ?>
    <section class="py-20 bg-light-navy/30 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12" data-aos="fade-up">
                <span class="text-accent font-medium">What's Happening</span>
                <h2 class="font-orbitron text-3xl md:text-4xl font-bold mt-2">Upcoming Events</h2>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8">
                <?php foreach ($upcomingEvents as $index => $event): ?>
                <div class="bg-light-navy/50 backdrop-blur-sm rounded-lg overflow-hidden border border-slate-800 hover:border-accent/30 transition" data-aos="fade-up" data-aos-delay="<?= $index * 100 ?>">
                    <div class="aspect-video relative overflow-hidden">
                        <?php if ($event['image_path']): ?>
                            <img src="uploads/events/<?= htmlspecialchars($event['image_path']) ?>" alt="<?= htmlspecialchars($event['title']) ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full bg-gradient-to-br from-accent/20 to-secondary-accent/20 flex items-center justify-center">
                                <svg class="w-16 h-16 text-accent/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        <?php endif; ?>
                        <?php if ($event['is_featured']): ?>
                            <span class="absolute top-2 right-2 bg-yellow-500/80 text-dark-navy text-xs px-2 py-1 rounded font-semibold">Featured</span>
                        <?php endif; ?>
                    </div>
                    <div class="p-6">
                        <span class="text-xs text-purple-400 bg-purple-500/20 px-2 py-1 rounded"><?= htmlspecialchars($event['category']) ?></span>
                        <h3 class="text-xl font-bold text-white mt-3"><?= htmlspecialchars($event['title']) ?></h3>
                        <div class="flex items-center gap-2 text-slate-400 text-sm mt-3">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <?= date('M d, Y', strtotime($event['event_date'])) ?>
                            <?php if ($event['event_time']): ?>
                                at <?= date('g:i A', strtotime($event['event_time'])) ?>
                            <?php endif; ?>
                        </div>
                        <?php if ($event['location']): ?>
                        <div class="flex items-center gap-2 text-slate-400 text-sm mt-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <?= htmlspecialchars($event['location']) ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($event['short_description']): ?>
                            <p class="text-slate-300 text-sm mt-3 line-clamp-2"><?= htmlspecialchars($event['short_description']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-10">
                <a href="pages/events.php" class="inline-flex items-center gap-2 border border-accent text-accent px-6 py-3 rounded-lg hover:bg-accent/10 transition">
                    View All Events
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Team Section -->
    <?php if (!empty($executiveTeam)): ?>
    <section class="py-20 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12" data-aos="fade-up">
                <span class="text-accent font-medium">Our Team</span>
                <h2 class="font-orbitron text-3xl md:text-4xl font-bold mt-2">Meet the Leadership</h2>
            </div>
            
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php foreach ($executiveTeam as $index => $member): ?>
                <div class="bg-light-navy/50 backdrop-blur-sm rounded-lg p-6 border border-slate-800 hover:border-accent/30 transition text-center" data-aos="fade-up" data-aos-delay="<?= $index * 100 ?>">
                    <div class="w-24 h-24 mx-auto rounded-full overflow-hidden mb-4 border-2 border-accent/30">
                        <?php if ($member['photo_path']): ?>
                            <img src="uploads/team/<?= htmlspecialchars($member['photo_path']) ?>" alt="<?= htmlspecialchars($member['name']) ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full bg-gradient-to-br from-accent/20 to-secondary-accent/20 flex items-center justify-center">
                                <span class="text-accent font-bold text-2xl"><?= strtoupper(substr($member['name'], 0, 1)) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <h3 class="text-lg font-bold text-white"><?= htmlspecialchars($member['name']) ?></h3>
                    <p class="text-accent text-sm"><?= htmlspecialchars($member['role']) ?></p>
                    <?php if ($member['bio']): ?>
                        <p class="text-slate-400 text-sm mt-2 line-clamp-2"><?= htmlspecialchars($member['bio']) ?></p>
                    <?php endif; ?>
                    <div class="flex justify-center gap-3 mt-4">
                        <?php if ($member['linkedin']): ?>
                            <a href="<?= htmlspecialchars($member['linkedin']) ?>" target="_blank" class="text-slate-400 hover:text-accent transition">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                            </a>
                        <?php endif; ?>
                        <?php if ($member['github']): ?>
                            <a href="<?= htmlspecialchars($member['github']) ?>" target="_blank" class="text-slate-400 hover:text-accent transition">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>
                            </a>
                        <?php endif; ?>
                        <?php if ($member['email']): ?>
                            <a href="mailto:<?= htmlspecialchars($member['email']) ?>" class="text-slate-400 hover:text-accent transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-10">
                <a href="pages/team.php" class="inline-flex items-center gap-2 border border-accent text-accent px-6 py-3 rounded-lg hover:bg-accent/10 transition">
                    View Full Team
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Gallery Section -->
    <?php if (!empty($featuredGallery)): ?>
    <section class="py-20 bg-light-navy/30 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12" data-aos="fade-up">
                <span class="text-accent font-medium">Our Work</span>
                <h2 class="font-orbitron text-3xl md:text-4xl font-bold mt-2">Featured Gallery</h2>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <?php foreach ($featuredGallery as $index => $item): ?>
                <div class="relative aspect-square overflow-hidden rounded-lg cursor-pointer group" data-aos="fade-up" data-aos-delay="<?= $index * 50 ?>">
                    <img src="uploads/gallery/<?= htmlspecialchars($item['thumbnail_path'] ?: $item['image_path']) ?>" 
                         alt="<?= htmlspecialchars($item['title']) ?>" 
                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                    <div class="absolute inset-0 bg-gradient-to-t from-dark-navy/80 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end p-4">
                        <div>
                            <h4 class="text-white font-medium"><?= htmlspecialchars($item['title'] ?: 'Untitled') ?></h4>
                            <p class="text-slate-400 text-sm"><?= htmlspecialchars($item['category']) ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-10">
                <a href="pages/gallery.php" class="inline-flex items-center gap-2 border border-accent text-accent px-6 py-3 rounded-lg hover:bg-accent/10 transition">
                    View Full Gallery
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Contact Section -->
    <section class="py-20 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-2 gap-12">
                <div data-aos="fade-right">
                    <span class="text-accent font-medium">Get in Touch</span>
                    <h2 class="font-orbitron text-3xl md:text-4xl font-bold mt-2 mb-6">Contact Us</h2>
                    <p class="text-slate-300 mb-8">
                        Have questions about the club? Want to collaborate or sponsor an event? We'd love to hear from you!
                    </p>
                    
                    <div class="space-y-4">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-accent/10 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-white">Location</h4>
                                <p class="text-slate-400">Kathmandu Model College, Bagbazar, Kathmandu</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-accent/10 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-white">Email</h4>
                                <a href="mailto:<?= htmlspecialchars($settings['contact_email'] ?? 'robotics@kmc.edu.np') ?>" class="text-slate-400 hover:text-accent">
                                    <?= htmlspecialchars($settings['contact_email'] ?? 'robotics@kmc.edu.np') ?>
                                </a>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-accent/10 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-white">Phone</h4>
                                <p class="text-slate-400"><?= htmlspecialchars($settings['contact_phone'] ?? '+977-01-4240740') ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Social Links -->
                    <div class="mt-8">
                        <h4 class="font-semibold text-white mb-4">Follow Us</h4>
                        <div class="flex gap-4">
                            <?php if (!empty($settings['facebook_url'])): ?>
                            <a href="<?= htmlspecialchars($settings['facebook_url']) ?>" target="_blank" class="w-10 h-10 bg-slate-800 rounded-lg flex items-center justify-center text-slate-400 hover:bg-accent hover:text-dark-navy transition">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            </a>
                            <?php endif; ?>
                            <?php if (!empty($settings['instagram_url'])): ?>
                            <a href="<?= htmlspecialchars($settings['instagram_url']) ?>" target="_blank" class="w-10 h-10 bg-slate-800 rounded-lg flex items-center justify-center text-slate-400 hover:bg-accent hover:text-dark-navy transition">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                            </a>
                            <?php endif; ?>
                            <?php if (!empty($settings['twitter_url'])): ?>
                            <a href="<?= htmlspecialchars($settings['twitter_url']) ?>" target="_blank" class="w-10 h-10 bg-slate-800 rounded-lg flex items-center justify-center text-slate-400 hover:bg-accent hover:text-dark-navy transition">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                            </a>
                            <?php endif; ?>
                            <?php if (!empty($settings['youtube_url'])): ?>
                            <a href="<?= htmlspecialchars($settings['youtube_url']) ?>" target="_blank" class="w-10 h-10 bg-slate-800 rounded-lg flex items-center justify-center text-slate-400 hover:bg-accent hover:text-dark-navy transition">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                            </a>
                            <?php endif; ?>
                            <?php if (!empty($settings['github_url'])): ?>
                            <a href="<?= htmlspecialchars($settings['github_url']) ?>" target="_blank" class="w-10 h-10 bg-slate-800 rounded-lg flex items-center justify-center text-slate-400 hover:bg-accent hover:text-dark-navy transition">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Form -->
                <div data-aos="fade-left">
                    <form id="contact-form" class="bg-light-navy/50 backdrop-blur-sm rounded-lg p-8 border border-slate-800">
                        <div class="mb-6">
                            <label class="block text-slate-300 mb-2">Name</label>
                            <input type="text" name="name" required 
                                   class="w-full bg-dark-navy border border-slate-700 rounded-lg px-4 py-3 text-white placeholder-slate-500 focus:border-accent focus:outline-none"
                                   placeholder="Your name">
                        </div>
                        <div class="mb-6">
                            <label class="block text-slate-300 mb-2">Email</label>
                            <input type="email" name="email" required 
                                   class="w-full bg-dark-navy border border-slate-700 rounded-lg px-4 py-3 text-white placeholder-slate-500 focus:border-accent focus:outline-none"
                                   placeholder="your@email.com">
                        </div>
                        <div class="mb-6">
                            <label class="block text-slate-300 mb-2">Subject</label>
                            <input type="text" name="subject" required 
                                   class="w-full bg-dark-navy border border-slate-700 rounded-lg px-4 py-3 text-white placeholder-slate-500 focus:border-accent focus:outline-none"
                                   placeholder="Subject">
                        </div>
                        <div class="mb-6">
                            <label class="block text-slate-300 mb-2">Message</label>
                            <textarea name="message" rows="4" required 
                                      class="w-full bg-dark-navy border border-slate-700 rounded-lg px-4 py-3 text-white placeholder-slate-500 focus:border-accent focus:outline-none resize-none"
                                      placeholder="Your message"></textarea>
                        </div>
                        <button type="submit" 
                                class="w-full bg-accent text-dark-navy py-3 rounded-lg font-semibold hover:bg-accent/80 transition flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                            Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="bg-light-navy/50 border-t border-slate-800 py-12 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-10 h-10 bg-gradient-to-br from-accent to-secondary-accent rounded-lg flex items-center justify-center">
                            <span class="font-orbitron font-bold text-dark-navy">KR</span>
                        </div>
                        <span class="font-orbitron font-bold text-lg">KMC Robotics</span>
                    </div>
                    <p class="text-slate-400 text-sm">
                        Building tomorrow's innovators through robotics, AI, and engineering education.
                    </p>
                </div>
                
                <div>
                    <h4 class="font-semibold text-white mb-4">Quick Links</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="pages/about.php" class="text-slate-400 hover:text-accent transition">About Us</a></li>
                        <li><a href="pages/events.php" class="text-slate-400 hover:text-accent transition">Events</a></li>
                        <li><a href="pages/team.php" class="text-slate-400 hover:text-accent transition">Our Team</a></li>
                        <li><a href="pages/gallery.php" class="text-slate-400 hover:text-accent transition">Gallery</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-semibold text-white mb-4">Resources</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="pages/join.php" class="text-slate-400 hover:text-accent transition">Join Us</a></li>
                        <li><a href="#" class="text-slate-400 hover:text-accent transition">Projects</a></li>
                        <li><a href="#" class="text-slate-400 hover:text-accent transition">Tutorials</a></li>
                        <li><a href="#" class="text-slate-400 hover:text-accent transition">FAQs</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-semibold text-white mb-4">Contact</h4>
                    <ul class="space-y-2 text-sm text-slate-400">
                        <li>Kathmandu Model College</li>
                        <li>Bagbazar, Kathmandu</li>
                        <li><?= htmlspecialchars($settings['contact_email'] ?? 'robotics@kmc.edu.np') ?></li>
                        <li><?= htmlspecialchars($settings['contact_phone'] ?? '+977-01-4240740') ?></li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-slate-800 mt-8 pt-8 flex flex-col md:flex-row items-center justify-between text-sm text-slate-400">
                <p>&copy; <?= date('Y') ?> KMC Robotics Club. All rights reserved.</p>
                <div class="flex gap-4 mt-4 md:mt-0">
                    <a href="#" class="hover:text-accent transition">Privacy Policy</a>
                    <a href="#" class="hover:text-accent transition">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Scripts -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="js/main.js"></script>
    <script src="js/api.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            once: true
        });
        
        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        
        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
    </script>
</body>
</html>
