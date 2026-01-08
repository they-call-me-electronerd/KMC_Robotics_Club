<?php
/**
 * KMC Robotics Club - Events Page
 * Lists all published events with filtering
 */

require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Security.php';
require_once '../includes/Auth.php';

$db = Database::getInstance();
Security::startSession();

$isLoggedIn = Auth::isLoggedIn();
$currentUser = $isLoggedIn ? Auth::getCurrentUser() : null;

// Filters
$category = $_GET['category'] ?? '';
$period = $_GET['period'] ?? 'upcoming';

// Build query
$where = "WHERE status != 'cancelled'";
$params = [];

if ($category) {
    $where .= " AND category = ?";
    $params[] = $category;
}

if ($period === 'upcoming') {
    $where .= " AND event_date >= CURDATE()";
    $orderBy = "ORDER BY event_date ASC";
} elseif ($period === 'past') {
    $where .= " AND event_date < CURDATE()";
    $orderBy = "ORDER BY event_date DESC";
} else {
    $orderBy = "ORDER BY event_date DESC";
}

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 9;
$offset = ($page - 1) * $perPage;

$countQuery = "SELECT COUNT(*) as total FROM events $where";
$total = $db->fetchOne($countQuery, $params)['total'];
$totalPages = ceil($total / $perPage);

$query = "SELECT * FROM events $where $orderBy LIMIT $perPage OFFSET $offset";
$events = $db->fetchAll($query, $params);

// Get categories for filter
$categories = $db->fetchAll("SELECT DISTINCT category FROM events WHERE status != 'cancelled' ORDER BY category");

// Get user's registrations
$userRegistrations = [];
if ($isLoggedIn) {
    $regs = $db->fetchAll("SELECT event_id FROM event_registrations WHERE user_id = ?", [$currentUser['id']]);
    $userRegistrations = array_column($regs, 'event_id');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - KMC Robotics Club</title>
    
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
                    <a href="events.php" class="text-accent font-medium">Events</a>
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
        
        <div id="mobile-menu" class="hidden md:hidden bg-light-navy border-t border-slate-800">
            <div class="px-4 py-4 space-y-2">
                <a href="../index.php" class="block text-slate-300 hover:text-accent py-2">Home</a>
                <a href="about.php" class="block text-slate-300 hover:text-accent py-2">About</a>
                <a href="events.php" class="block text-accent py-2">Events</a>
                <a href="team.php" class="block text-slate-300 hover:text-accent py-2">Team</a>
                <a href="gallery.php" class="block text-slate-300 hover:text-accent py-2">Gallery</a>
            </div>
        </div>
    </nav>
    
    <!-- Page Header -->
    <section class="pt-32 pb-12 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="font-orbitron text-4xl md:text-5xl font-bold mb-4" data-aos="fade-up">
                <span class="bg-gradient-to-r from-accent to-secondary-accent bg-clip-text text-transparent">Events</span>
            </h1>
            <p class="text-slate-400 text-lg max-w-2xl mx-auto" data-aos="fade-up" data-aos-delay="100">
                Join our workshops, competitions, and meetups. Learn, collaborate, and grow with us.
            </p>
        </div>
    </section>
    
    <!-- Filters -->
    <section class="pb-8 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap gap-4 items-center justify-between" data-aos="fade-up">
                <!-- Period Filter -->
                <div class="flex gap-2">
                    <a href="?period=upcoming<?= $category ? '&category=' . urlencode($category) : '' ?>" 
                       class="px-4 py-2 rounded-lg <?= $period === 'upcoming' ? 'bg-accent text-dark-navy' : 'bg-light-navy text-slate-300 hover:bg-slate-800' ?> transition">
                        Upcoming
                    </a>
                    <a href="?period=past<?= $category ? '&category=' . urlencode($category) : '' ?>" 
                       class="px-4 py-2 rounded-lg <?= $period === 'past' ? 'bg-accent text-dark-navy' : 'bg-light-navy text-slate-300 hover:bg-slate-800' ?> transition">
                        Past
                    </a>
                    <a href="?period=all<?= $category ? '&category=' . urlencode($category) : '' ?>" 
                       class="px-4 py-2 rounded-lg <?= $period === 'all' ? 'bg-accent text-dark-navy' : 'bg-light-navy text-slate-300 hover:bg-slate-800' ?> transition">
                        All
                    </a>
                </div>
                
                <!-- Category Filter -->
                <div class="flex gap-2 flex-wrap">
                    <a href="?period=<?= $period ?>" 
                       class="px-4 py-2 rounded-lg <?= !$category ? 'bg-purple-500/20 text-purple-400 border border-purple-500/30' : 'bg-light-navy text-slate-300 hover:bg-slate-800' ?> transition">
                        All Categories
                    </a>
                    <?php foreach ($categories as $cat): ?>
                    <a href="?period=<?= $period ?>&category=<?= urlencode($cat['category']) ?>" 
                       class="px-4 py-2 rounded-lg <?= $category === $cat['category'] ? 'bg-purple-500/20 text-purple-400 border border-purple-500/30' : 'bg-light-navy text-slate-300 hover:bg-slate-800' ?> transition">
                        <?= htmlspecialchars($cat['category']) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Events Grid -->
    <section class="py-12 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <?php if (empty($events)): ?>
                <div class="text-center py-16 bg-light-navy/30 rounded-lg border border-slate-800">
                    <svg class="w-16 h-16 mx-auto text-slate-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <h3 class="text-xl font-semibold text-slate-400">No events found</h3>
                    <p class="text-slate-500 mt-2">Check back later for new events!</p>
                </div>
            <?php else: ?>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($events as $index => $event): ?>
                    <div class="bg-light-navy/50 backdrop-blur-sm rounded-lg overflow-hidden border border-slate-800 hover:border-accent/30 transition group" 
                         data-aos="fade-up" data-aos-delay="<?= ($index % 3) * 100 ?>">
                        <div class="aspect-video relative overflow-hidden">
                            <?php if ($event['image_path']): ?>
                                <img src="../uploads/events/<?= htmlspecialchars($event['image_path']) ?>" 
                                     alt="<?= htmlspecialchars($event['title']) ?>" 
                                     class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
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
                            
                            <?php if (strtotime($event['event_date']) < time()): ?>
                                <span class="absolute top-2 left-2 bg-slate-700/80 text-slate-300 text-xs px-2 py-1 rounded">Past Event</span>
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
                            
                            <div class="flex items-center justify-between mt-4 pt-4 border-t border-slate-700">
                                <?php if ($event['max_participants']): ?>
                                    <?php 
                                    $regCount = $db->fetchOne("SELECT COUNT(*) as count FROM event_registrations WHERE event_id = ?", [$event['id']])['count'];
                                    $spotsLeft = $event['max_participants'] - $regCount;
                                    ?>
                                    <span class="text-sm <?= $spotsLeft > 0 ? 'text-green-400' : 'text-red-400' ?>">
                                        <?= $spotsLeft > 0 ? "$spotsLeft spots left" : "Fully booked" ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-sm text-slate-500">Open registration</span>
                                <?php endif; ?>
                                
                                <?php if (strtotime($event['event_date']) >= time()): ?>
                                    <?php if ($isLoggedIn): ?>
                                        <?php if (in_array($event['id'], $userRegistrations)): ?>
                                            <span class="text-green-400 text-sm flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                Registered
                                            </span>
                                        <?php else: ?>
                                            <button onclick="registerEvent(<?= $event['id'] ?>)" 
                                                    class="text-accent text-sm hover:underline flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                                </svg>
                                                Register
                                            </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <a href="../auth/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" 
                                           class="text-accent text-sm hover:underline">Login to Register</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="flex justify-center gap-2 mt-12">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&period=<?= $period ?><?= $category ? '&category=' . urlencode($category) : '' ?>" 
                           class="px-4 py-2 bg-light-navy rounded-lg text-slate-300 hover:bg-slate-800 transition">
                            Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="?page=<?= $i ?>&period=<?= $period ?><?= $category ? '&category=' . urlencode($category) : '' ?>" 
                           class="px-4 py-2 rounded-lg <?= $i === $page ? 'bg-accent text-dark-navy' : 'bg-light-navy text-slate-300 hover:bg-slate-800' ?> transition">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>&period=<?= $period ?><?= $category ? '&category=' . urlencode($category) : '' ?>" 
                           class="px-4 py-2 bg-light-navy rounded-lg text-slate-300 hover:bg-slate-800 transition">
                            Next
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="bg-light-navy/50 border-t border-slate-800 py-8 relative z-10 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between text-sm text-slate-400">
                <p>&copy; <?= date('Y') ?> KMC Robotics Club. All rights reserved.</p>
                <div class="flex gap-4 mt-4 md:mt-0">
                    <a href="../index.php" class="hover:text-accent transition">Home</a>
                    <a href="about.php" class="hover:text-accent transition">About</a>
                    <a href="team.php" class="hover:text-accent transition">Team</a>
                </div>
            </div>
        </div>
    </footer>
    
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="../js/main.js"></script>
    <script src="../js/api.js"></script>
    <script>
        AOS.init({ duration: 800, once: true });
        
        document.getElementById('mobile-menu-btn').addEventListener('click', () => {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });
        
        async function registerEvent(eventId) {
            try {
                const response = await KMCRC.Events.register(eventId);
                KMCRC.Utils.showNotification('Successfully registered!', 'success');
                setTimeout(() => location.reload(), 1000);
            } catch (error) {
                KMCRC.Utils.showNotification(error.message || 'Failed to register', 'error');
            }
        }
    </script>
</body>
</html>
