<?php
/**
 * KMC Robotics Club - Admin Dashboard
 */

require_once __DIR__ . '/../includes/Security.php';
require_once __DIR__ . '/../includes/Database.php';

Security::requireAdmin();

$db = Database::getInstance();

// Get dashboard stats
$stats = [
    'total_members' => $db->count('users', 'role = :role', ['role' => 'member']),
    'active_members' => $db->count('users', 'role = :role AND status = :status', ['role' => 'member', 'status' => 'active']),
    'pending_members' => $db->count('users', 'status = :status', ['status' => 'pending']),
    'upcoming_events' => $db->count('events', 'event_date >= CURDATE() AND status = :status', ['status' => 'upcoming']),
    'total_events' => $db->count('events'),
    'gallery_items' => $db->count('gallery', 'is_approved = 1'),
    'pending_gallery' => $db->count('gallery', 'is_approved = 0'),
    'unread_messages' => $db->count('messages', 'status = :status AND (recipient_id IS NULL OR recipient_id IN (SELECT id FROM users WHERE role = :role))', ['status' => 'unread', 'role' => 'admin']),
    'team_members' => $db->count('team_members', 'is_active = 1')
];

// Get recent activity
$recentActivity = $db->fetchAll(
    "SELECT al.*, u.name as user_name 
     FROM activity_logs al 
     LEFT JOIN users u ON al.user_id = u.id 
     ORDER BY al.created_at DESC 
     LIMIT 10"
);

// Get recent registrations
$recentUsers = $db->fetchAll(
    "SELECT id, name, email, department, status, created_at 
     FROM users 
     ORDER BY created_at DESC 
     LIMIT 5"
);

// Get upcoming events
$upcomingEvents = $db->fetchAll(
    "SELECT id, title, event_date, start_time, location, 
            (SELECT COUNT(*) FROM event_registrations WHERE event_id = events.id) as registrations
     FROM events 
     WHERE event_date >= CURDATE() 
     ORDER BY event_date ASC 
     LIMIT 5"
);

// Get recent messages
$recentMessages = $db->fetchAll(
    "SELECT m.*, COALESCE(u.name, m.sender_name) as sender_display_name
     FROM messages m
     LEFT JOIN users u ON m.sender_id = u.id
     WHERE m.recipient_id IS NULL OR m.recipient_id IN (SELECT id FROM users WHERE role = 'admin')
     ORDER BY m.created_at DESC
     LIMIT 5"
);

$csrfToken = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="theme-color" content="#050a14" />
    <link rel="icon" type="image/png" href="../assets/images/kmc-rc-logo.png">
    <title>Admin Dashboard | KMC Robotics Club</title>
    
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
        .sidebar {
            background: rgba(15, 26, 46, 0.95);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(0, 242, 255, 0.1);
        }
        .stat-card-admin {
            background: rgba(15, 26, 46, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(0, 242, 255, 0.1);
            transition: all 0.3s ease;
        }
        .stat-card-admin:hover {
            border-color: rgba(0, 242, 255, 0.3);
            box-shadow: 0 0 20px rgba(0, 242, 255, 0.1);
        }
        .nav-link-admin {
            transition: all 0.3s ease;
        }
        .nav-link-admin:hover, .nav-link-admin.active {
            background: rgba(0, 242, 255, 0.1);
            border-left: 3px solid #00f2ff;
        }
        .table-row:hover {
            background: rgba(0, 242, 255, 0.05);
        }
    </style>
</head>
<body class="antialiased min-h-screen flex">
    <!-- Sidebar -->
    <aside class="sidebar w-64 min-h-screen fixed left-0 top-0 z-50 hidden lg:block">
        <div class="p-6">
            <a href="../index.html" class="flex items-center gap-3">
                <img src="../assets/images/kmc-rc-logo.png" alt="KMC RC" class="w-10 h-10">
                <div>
                    <div class="text-white font-bold font-orbitron">KMC RC</div>
                    <div class="text-xs text-slate-400">Admin Panel</div>
                </div>
            </a>
        </div>
        
        <nav class="mt-6">
            <a href="dashboard.php" class="nav-link-admin active flex items-center gap-3 px-6 py-3 text-white">
                <i data-feather="grid" class="w-5 h-5"></i>
                Dashboard
            </a>
            <a href="users.php" class="nav-link-admin flex items-center gap-3 px-6 py-3 text-slate-400 hover:text-white">
                <i data-feather="users" class="w-5 h-5"></i>
                Members
                <?php if ($stats['pending_members'] > 0): ?>
                <span class="ml-auto bg-accent/20 text-accent text-xs px-2 py-0.5 rounded-full"><?= $stats['pending_members'] ?></span>
                <?php endif; ?>
            </a>
            <a href="events.php" class="nav-link-admin flex items-center gap-3 px-6 py-3 text-slate-400 hover:text-white">
                <i data-feather="calendar" class="w-5 h-5"></i>
                Events
            </a>
            <a href="team.php" class="nav-link-admin flex items-center gap-3 px-6 py-3 text-slate-400 hover:text-white">
                <i data-feather="award" class="w-5 h-5"></i>
                Team
            </a>
            <a href="gallery.php" class="nav-link-admin flex items-center gap-3 px-6 py-3 text-slate-400 hover:text-white">
                <i data-feather="image" class="w-5 h-5"></i>
                Gallery
                <?php if ($stats['pending_gallery'] > 0): ?>
                <span class="ml-auto bg-yellow-500/20 text-yellow-400 text-xs px-2 py-0.5 rounded-full"><?= $stats['pending_gallery'] ?></span>
                <?php endif; ?>
            </a>
            <a href="messages.php" class="nav-link-admin flex items-center gap-3 px-6 py-3 text-slate-400 hover:text-white">
                <i data-feather="mail" class="w-5 h-5"></i>
                Messages
                <?php if ($stats['unread_messages'] > 0): ?>
                <span class="ml-auto bg-red-500/20 text-red-400 text-xs px-2 py-0.5 rounded-full"><?= $stats['unread_messages'] ?></span>
                <?php endif; ?>
            </a>
            <a href="settings.php" class="nav-link-admin flex items-center gap-3 px-6 py-3 text-slate-400 hover:text-white">
                <i data-feather="settings" class="w-5 h-5"></i>
                Settings
            </a>
        </nav>
        
        <div class="absolute bottom-0 left-0 right-0 p-6 border-t border-slate-800">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-accent/20 flex items-center justify-center">
                    <?php if ($_SESSION['user_profile_pic']): ?>
                    <img src="../uploads/profiles/<?= htmlspecialchars($_SESSION['user_profile_pic']) ?>" alt="Profile" class="w-full h-full rounded-full object-cover">
                    <?php else: ?>
                    <i data-feather="user" class="w-5 h-5 text-accent"></i>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="text-white text-sm font-medium"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
                    <div class="text-xs text-slate-400">Administrator</div>
                </div>
            </div>
            <a href="../auth/logout.php" class="flex items-center gap-2 text-slate-400 hover:text-red-400 text-sm">
                <i data-feather="log-out" class="w-4 h-4"></i>
                Logout
            </a>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="flex-1 lg:ml-64">
        <!-- Top Bar -->
        <header class="bg-dark-navy/80 backdrop-blur-lg border-b border-slate-800 px-6 py-4 sticky top-0 z-40">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-white font-orbitron">Dashboard</h1>
                    <p class="text-sm text-slate-400">Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?></p>
                </div>
                <div class="flex items-center gap-4">
                    <button class="lg:hidden text-white" id="mobileMenuBtn">
                        <i data-feather="menu" class="w-6 h-6"></i>
                    </button>
                    <a href="../index.html" class="text-slate-400 hover:text-accent flex items-center gap-2 text-sm">
                        <i data-feather="external-link" class="w-4 h-4"></i>
                        View Site
                    </a>
                </div>
            </div>
        </header>
        
        <div class="p-6">
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="stat-card-admin rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-lg bg-accent/10 flex items-center justify-center">
                            <i data-feather="users" class="w-6 h-6 text-accent"></i>
                        </div>
                        <span class="text-green-400 text-sm flex items-center gap-1">
                            <i data-feather="trending-up" class="w-4 h-4"></i>
                            +<?= $stats['pending_members'] ?> pending
                        </span>
                    </div>
                    <div class="text-3xl font-bold text-white font-orbitron"><?= $stats['active_members'] ?></div>
                    <div class="text-slate-400 text-sm">Active Members</div>
                </div>
                
                <div class="stat-card-admin rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-lg bg-purple-500/10 flex items-center justify-center">
                            <i data-feather="calendar" class="w-6 h-6 text-purple-400"></i>
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-white font-orbitron"><?= $stats['upcoming_events'] ?></div>
                    <div class="text-slate-400 text-sm">Upcoming Events</div>
                </div>
                
                <div class="stat-card-admin rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-lg bg-green-500/10 flex items-center justify-center">
                            <i data-feather="image" class="w-6 h-6 text-green-400"></i>
                        </div>
                        <?php if ($stats['pending_gallery'] > 0): ?>
                        <span class="text-yellow-400 text-sm"><?= $stats['pending_gallery'] ?> pending</span>
                        <?php endif; ?>
                    </div>
                    <div class="text-3xl font-bold text-white font-orbitron"><?= $stats['gallery_items'] ?></div>
                    <div class="text-slate-400 text-sm">Gallery Items</div>
                </div>
                
                <div class="stat-card-admin rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-lg bg-red-500/10 flex items-center justify-center">
                            <i data-feather="mail" class="w-6 h-6 text-red-400"></i>
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-white font-orbitron"><?= $stats['unread_messages'] ?></div>
                    <div class="text-slate-400 text-sm">Unread Messages</div>
                </div>
            </div>
            
            <!-- Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Recent Registrations -->
                <div class="stat-card-admin rounded-lg p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-bold text-white">Recent Registrations</h2>
                        <a href="users.php" class="text-accent text-sm hover:underline">View All</a>
                    </div>
                    <div class="space-y-4">
                        <?php foreach ($recentUsers as $user): ?>
                        <div class="flex items-center justify-between py-3 border-b border-slate-800 last:border-0 table-row">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-accent/10 flex items-center justify-center">
                                    <i data-feather="user" class="w-5 h-5 text-accent"></i>
                                </div>
                                <div>
                                    <div class="text-white font-medium"><?= htmlspecialchars($user['name']) ?></div>
                                    <div class="text-slate-400 text-sm"><?= htmlspecialchars($user['email']) ?></div>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="inline-block px-2 py-1 rounded text-xs <?= $user['status'] === 'active' ? 'bg-green-500/20 text-green-400' : ($user['status'] === 'pending' ? 'bg-yellow-500/20 text-yellow-400' : 'bg-red-500/20 text-red-400') ?>">
                                    <?= ucfirst($user['status']) ?>
                                </span>
                                <div class="text-slate-500 text-xs mt-1"><?= date('M d', strtotime($user['created_at'])) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Upcoming Events -->
                <div class="stat-card-admin rounded-lg p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-bold text-white">Upcoming Events</h2>
                        <a href="events.php" class="text-accent text-sm hover:underline">View All</a>
                    </div>
                    <div class="space-y-4">
                        <?php foreach ($upcomingEvents as $event): ?>
                        <div class="flex items-center justify-between py-3 border-b border-slate-800 last:border-0 table-row">
                            <div class="flex items-center gap-3">
                                <div class="text-center bg-accent/10 rounded-lg p-2 min-w-[50px]">
                                    <div class="text-accent font-bold"><?= date('d', strtotime($event['event_date'])) ?></div>
                                    <div class="text-slate-400 text-xs"><?= date('M', strtotime($event['event_date'])) ?></div>
                                </div>
                                <div>
                                    <div class="text-white font-medium"><?= htmlspecialchars($event['title']) ?></div>
                                    <div class="text-slate-400 text-sm flex items-center gap-1">
                                        <i data-feather="map-pin" class="w-3 h-3"></i>
                                        <?= htmlspecialchars($event['location'] ?: 'TBD') ?>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-accent font-bold"><?= $event['registrations'] ?></div>
                                <div class="text-slate-500 text-xs">registered</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($upcomingEvents)): ?>
                        <div class="text-center py-8 text-slate-400">
                            <i data-feather="calendar" class="w-12 h-12 mx-auto mb-3 opacity-50"></i>
                            <p>No upcoming events</p>
                            <a href="events.php?action=create" class="text-accent text-sm hover:underline">Create one now</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Messages -->
                <div class="stat-card-admin rounded-lg p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-bold text-white">Recent Messages</h2>
                        <a href="messages.php" class="text-accent text-sm hover:underline">View All</a>
                    </div>
                    <div class="space-y-4">
                        <?php foreach ($recentMessages as $message): ?>
                        <div class="flex items-start gap-3 py-3 border-b border-slate-800 last:border-0 table-row">
                            <div class="w-10 h-10 rounded-full bg-accent/10 flex items-center justify-center flex-shrink-0">
                                <i data-feather="mail" class="w-5 h-5 text-accent"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <div class="text-white font-medium truncate"><?= htmlspecialchars($message['sender_display_name'] ?: 'Guest') ?></div>
                                    <span class="text-slate-500 text-xs"><?= date('M d', strtotime($message['created_at'])) ?></span>
                                </div>
                                <div class="text-slate-300 text-sm truncate"><?= htmlspecialchars($message['subject']) ?></div>
                                <div class="text-slate-500 text-xs truncate"><?= htmlspecialchars(substr($message['message'], 0, 60)) ?>...</div>
                            </div>
                            <?php if ($message['status'] === 'unread'): ?>
                            <span class="w-2 h-2 rounded-full bg-accent flex-shrink-0"></span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($recentMessages)): ?>
                        <div class="text-center py-8 text-slate-400">
                            <i data-feather="inbox" class="w-12 h-12 mx-auto mb-3 opacity-50"></i>
                            <p>No messages yet</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="stat-card-admin rounded-lg p-6">
                    <h2 class="text-lg font-bold text-white mb-6">Quick Actions</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <a href="events.php?action=create" class="flex flex-col items-center justify-center p-4 rounded-lg border border-slate-700 hover:border-accent hover:bg-accent/5 transition-all">
                            <i data-feather="plus-circle" class="w-8 h-8 text-accent mb-2"></i>
                            <span class="text-white text-sm">Add Event</span>
                        </a>
                        <a href="team.php?action=create" class="flex flex-col items-center justify-center p-4 rounded-lg border border-slate-700 hover:border-accent hover:bg-accent/5 transition-all">
                            <i data-feather="user-plus" class="w-8 h-8 text-purple-400 mb-2"></i>
                            <span class="text-white text-sm">Add Team Member</span>
                        </a>
                        <a href="gallery.php?action=upload" class="flex flex-col items-center justify-center p-4 rounded-lg border border-slate-700 hover:border-accent hover:bg-accent/5 transition-all">
                            <i data-feather="upload" class="w-8 h-8 text-green-400 mb-2"></i>
                            <span class="text-white text-sm">Upload to Gallery</span>
                        </a>
                        <a href="users.php?status=pending" class="flex flex-col items-center justify-center p-4 rounded-lg border border-slate-700 hover:border-accent hover:bg-accent/5 transition-all">
                            <i data-feather="user-check" class="w-8 h-8 text-yellow-400 mb-2"></i>
                            <span class="text-white text-sm">Approve Members</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Mobile Menu Overlay -->
    <div id="mobileMenuOverlay" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden"></div>
    
    <!-- Mobile Sidebar -->
    <div id="mobileSidebar" class="sidebar fixed inset-y-0 left-0 w-64 z-50 transform -translate-x-full lg:hidden transition-transform duration-300">
        <!-- Same content as desktop sidebar -->
        <div class="p-6">
            <div class="flex items-center justify-between">
                <a href="../index.html" class="flex items-center gap-3">
                    <img src="../assets/images/kmc-rc-logo.png" alt="KMC RC" class="w-10 h-10">
                    <div>
                        <div class="text-white font-bold font-orbitron">KMC RC</div>
                        <div class="text-xs text-slate-400">Admin Panel</div>
                    </div>
                </a>
                <button id="closeMobileMenu" class="text-slate-400 hover:text-white">
                    <i data-feather="x" class="w-6 h-6"></i>
                </button>
            </div>
        </div>
        
        <nav class="mt-6">
            <a href="dashboard.php" class="nav-link-admin active flex items-center gap-3 px-6 py-3 text-white">
                <i data-feather="grid" class="w-5 h-5"></i>
                Dashboard
            </a>
            <a href="users.php" class="nav-link-admin flex items-center gap-3 px-6 py-3 text-slate-400 hover:text-white">
                <i data-feather="users" class="w-5 h-5"></i>
                Members
            </a>
            <a href="events.php" class="nav-link-admin flex items-center gap-3 px-6 py-3 text-slate-400 hover:text-white">
                <i data-feather="calendar" class="w-5 h-5"></i>
                Events
            </a>
            <a href="team.php" class="nav-link-admin flex items-center gap-3 px-6 py-3 text-slate-400 hover:text-white">
                <i data-feather="award" class="w-5 h-5"></i>
                Team
            </a>
            <a href="gallery.php" class="nav-link-admin flex items-center gap-3 px-6 py-3 text-slate-400 hover:text-white">
                <i data-feather="image" class="w-5 h-5"></i>
                Gallery
            </a>
            <a href="messages.php" class="nav-link-admin flex items-center gap-3 px-6 py-3 text-slate-400 hover:text-white">
                <i data-feather="mail" class="w-5 h-5"></i>
                Messages
            </a>
            <a href="settings.php" class="nav-link-admin flex items-center gap-3 px-6 py-3 text-slate-400 hover:text-white">
                <i data-feather="settings" class="w-5 h-5"></i>
                Settings
            </a>
        </nav>
    </div>
    
    <script>
        feather.replace();
        
        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileSidebar = document.getElementById('mobileSidebar');
        const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
        const closeMobileMenu = document.getElementById('closeMobileMenu');
        
        function openMobileMenu() {
            mobileSidebar.classList.remove('-translate-x-full');
            mobileMenuOverlay.classList.remove('hidden');
        }
        
        function closeMobileMenuFn() {
            mobileSidebar.classList.add('-translate-x-full');
            mobileMenuOverlay.classList.add('hidden');
        }
        
        mobileMenuBtn?.addEventListener('click', openMobileMenu);
        closeMobileMenu?.addEventListener('click', closeMobileMenuFn);
        mobileMenuOverlay?.addEventListener('click', closeMobileMenuFn);
    </script>
</body>
</html>
