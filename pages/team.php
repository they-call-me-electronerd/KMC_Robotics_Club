<?php
/**
 * KMC Robotics Club - Team Page
 * Displays all team members by category
 */

require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Security.php';
require_once '../includes/Auth.php';

$db = Database::getInstance();
Security::startSession();

$isLoggedIn = Auth::isLoggedIn();
$currentUser = $isLoggedIn ? Auth::getCurrentUser() : null;

// Get category filter
$categoryFilter = $_GET['category'] ?? '';

// Get all categories
$categories = $db->fetchAll("SELECT DISTINCT category FROM team_members WHERE is_active = 1 ORDER BY category");

// Build query
$where = "WHERE is_active = 1";
$params = [];

if ($categoryFilter) {
    $where .= " AND category = ?";
    $params[] = $categoryFilter;
}

// Get executive team first
$executives = $db->fetchAll(
    "SELECT * FROM team_members $where AND is_executive = 1 ORDER BY display_order ASC",
    $params
);

// Get other members
$members = $db->fetchAll(
    "SELECT * FROM team_members $where AND is_executive = 0 ORDER BY display_order ASC, name ASC",
    $params
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Team - KMC Robotics Club</title>
    
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
                    <a href="team.php" class="text-accent font-medium">Team</a>
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
        
        <div id="mobile-menu" class="hidden md:hidden bg-light-navy border-t border-slate-800">
            <div class="px-4 py-4 space-y-2">
                <a href="../index.php" class="block text-slate-300 hover:text-accent py-2">Home</a>
                <a href="about.php" class="block text-slate-300 hover:text-accent py-2">About</a>
                <a href="events.php" class="block text-slate-300 hover:text-accent py-2">Events</a>
                <a href="team.php" class="block text-accent py-2">Team</a>
                <a href="gallery.php" class="block text-slate-300 hover:text-accent py-2">Gallery</a>
            </div>
        </div>
    </nav>
    
    <!-- Page Header -->
    <section class="pt-32 pb-12 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="font-orbitron text-4xl md:text-5xl font-bold mb-4" data-aos="fade-up">
                Meet Our <span class="bg-gradient-to-r from-accent to-secondary-accent bg-clip-text text-transparent">Team</span>
            </h1>
            <p class="text-slate-400 text-lg max-w-2xl mx-auto" data-aos="fade-up" data-aos-delay="100">
                Passionate individuals dedicated to advancing robotics education and innovation in Nepal.
            </p>
        </div>
    </section>
    
    <!-- Category Filter -->
    <section class="pb-8 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap gap-2 justify-center" data-aos="fade-up">
                <a href="team.php" 
                   class="px-4 py-2 rounded-lg <?= !$categoryFilter ? 'bg-accent text-dark-navy' : 'bg-light-navy text-slate-300 hover:bg-slate-800' ?> transition">
                    All Teams
                </a>
                <?php foreach ($categories as $cat): ?>
                <a href="?category=<?= urlencode($cat['category']) ?>" 
                   class="px-4 py-2 rounded-lg <?= $categoryFilter === $cat['category'] ? 'bg-accent text-dark-navy' : 'bg-light-navy text-slate-300 hover:bg-slate-800' ?> transition">
                    <?= htmlspecialchars($cat['category']) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <!-- Executive Team -->
    <?php if (!empty($executives)): ?>
    <section class="py-12 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="font-orbitron text-2xl md:text-3xl font-bold text-center mb-12" data-aos="fade-up">
                <span class="text-accent">Executive</span> Committee
            </h2>
            
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php foreach ($executives as $index => $member): ?>
                <div class="bg-light-navy/50 backdrop-blur-sm rounded-lg overflow-hidden border border-slate-800 hover:border-accent/30 transition group" 
                     data-aos="fade-up" data-aos-delay="<?= ($index % 4) * 100 ?>">
                    <div class="aspect-square relative overflow-hidden">
                        <?php if ($member['image_path']): ?>
                            <img src="../uploads/team/<?= htmlspecialchars($member['image_path']) ?>" 
                                 alt="<?= htmlspecialchars($member['name']) ?>" 
                                 class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                        <?php else: ?>
                            <div class="w-full h-full bg-gradient-to-br from-accent/20 to-secondary-accent/20 flex items-center justify-center">
                                <span class="text-accent font-bold text-6xl"><?= strtoupper(substr($member['name'], 0, 1)) ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="absolute inset-0 bg-gradient-to-t from-dark-navy via-transparent to-transparent opacity-60"></div>
                    </div>
                    
                    <div class="p-6 text-center -mt-12 relative">
                        <div class="w-20 h-20 mx-auto rounded-full overflow-hidden border-4 border-dark-navy bg-dark-navy mb-4">
                            <?php if ($member['image_path']): ?>
                                <img src="../uploads/team/<?= htmlspecialchars($member['image_path']) ?>" 
                                     alt="<?= htmlspecialchars($member['name']) ?>" 
                                     class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full bg-gradient-to-br from-accent to-secondary-accent flex items-center justify-center">
                                    <span class="text-dark-navy font-bold text-2xl"><?= strtoupper(substr($member['name'], 0, 1)) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <h3 class="text-xl font-bold text-white"><?= htmlspecialchars($member['name']) ?></h3>
                        <p class="text-accent font-medium"><?= htmlspecialchars($member['position']) ?></p>
                        <span class="text-xs text-slate-500"><?= htmlspecialchars($member['category']) ?></span>
                        
                        <?php if ($member['bio']): ?>
                            <p class="text-slate-400 text-sm mt-3 line-clamp-3"><?= htmlspecialchars($member['bio']) ?></p>
                        <?php endif; ?>
                        
                        <div class="flex justify-center gap-3 mt-4">
                            <?php if ($member['linkedin_url']): ?>
                                <a href="<?= htmlspecialchars($member['linkedin_url']) ?>" target="_blank" 
                                   class="w-9 h-9 bg-slate-800/50 rounded-lg flex items-center justify-center text-slate-400 hover:bg-accent hover:text-dark-navy transition">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                                </a>
                            <?php endif; ?>
                            <?php if ($member['github_url']): ?>
                                <a href="<?= htmlspecialchars($member['github_url']) ?>" target="_blank" 
                                   class="w-9 h-9 bg-slate-800/50 rounded-lg flex items-center justify-center text-slate-400 hover:bg-accent hover:text-dark-navy transition">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>
                                </a>
                            <?php endif; ?>
                            <?php if ($member['email']): ?>
                                <a href="mailto:<?= htmlspecialchars($member['email']) ?>" 
                                   class="w-9 h-9 bg-slate-800/50 rounded-lg flex items-center justify-center text-slate-400 hover:bg-accent hover:text-dark-navy transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Other Team Members -->
    <?php if (!empty($members)): ?>
    <section class="py-12 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <?php if (!empty($executives)): ?>
            <h2 class="font-orbitron text-2xl md:text-3xl font-bold text-center mb-12" data-aos="fade-up">
                Team <span class="text-accent">Members</span>
            </h2>
            <?php endif; ?>
            
            <div class="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <?php foreach ($members as $index => $member): ?>
                <div class="bg-light-navy/50 backdrop-blur-sm rounded-lg p-6 border border-slate-800 hover:border-accent/30 transition text-center group" 
                     data-aos="fade-up" data-aos-delay="<?= ($index % 4) * 50 ?>">
                    <div class="w-20 h-20 mx-auto rounded-full overflow-hidden mb-4 border-2 border-accent/30 group-hover:border-accent transition">
                        <?php if ($member['image_path']): ?>
                            <img src="../uploads/team/<?= htmlspecialchars($member['image_path']) ?>" 
                                 alt="<?= htmlspecialchars($member['name']) ?>" 
                                 class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full bg-gradient-to-br from-accent/20 to-secondary-accent/20 flex items-center justify-center">
                                <span class="text-accent font-bold text-2xl"><?= strtoupper(substr($member['name'], 0, 1)) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <h3 class="text-lg font-bold text-white"><?= htmlspecialchars($member['name']) ?></h3>
                    <p class="text-accent text-sm"><?= htmlspecialchars($member['position']) ?></p>
                    <span class="text-xs text-slate-500"><?= htmlspecialchars($member['category']) ?></span>
                    
                    <div class="flex justify-center gap-2 mt-4">
                        <?php if ($member['linkedin_url']): ?>
                            <a href="<?= htmlspecialchars($member['linkedin_url']) ?>" target="_blank" 
                               class="text-slate-500 hover:text-accent transition">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                            </a>
                        <?php endif; ?>
                        <?php if ($member['github_url']): ?>
                            <a href="<?= htmlspecialchars($member['github_url']) ?>" target="_blank" 
                               class="text-slate-500 hover:text-accent transition">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>
                            </a>
                        <?php endif; ?>
                        <?php if ($member['email']): ?>
                            <a href="mailto:<?= htmlspecialchars($member['email']) ?>" 
                               class="text-slate-500 hover:text-accent transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <?php if (empty($executives) && empty($members)): ?>
    <section class="py-20 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center py-16 bg-light-navy/30 rounded-lg border border-slate-800">
                <svg class="w-16 h-16 mx-auto text-slate-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <h3 class="text-xl font-semibold text-slate-400">No team members found</h3>
                <p class="text-slate-500 mt-2">Team information will be added soon!</p>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Join CTA -->
    <section class="py-20 relative z-10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center" data-aos="fade-up">
            <div class="bg-gradient-to-br from-accent/10 to-secondary-accent/10 rounded-2xl p-12 border border-accent/20">
                <h2 class="font-orbitron text-3xl font-bold mb-4">Want to Join Our Team?</h2>
                <p class="text-slate-400 mb-8">We're always looking for passionate individuals to join our robotics community.</p>
                <a href="join.php" class="inline-flex items-center gap-2 bg-accent text-dark-navy px-8 py-3 rounded-lg font-semibold hover:bg-accent/80 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                    Apply Now
                </a>
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
        
        document.getElementById('mobile-menu-btn').addEventListener('click', () => {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });
    </script>
</body>
</html>
