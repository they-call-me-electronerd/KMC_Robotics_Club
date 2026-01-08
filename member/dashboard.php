<?php
/**
 * KMC Robotics Club - Member Dashboard
 */

require_once __DIR__ . '/../includes/Security.php';
require_once __DIR__ . '/../includes/Database.php';

Security::requireAuth();

$db = Database::getInstance();
$userId = Security::getCurrentUserId();

// Get user data
$user = $db->fetchOne("SELECT * FROM users WHERE id = :id", ['id' => $userId]);

// Get stats
$stats = [
    'events_attended' => $db->fetchOne(
        "SELECT COUNT(*) as c FROM event_registrations WHERE user_id = :id AND status = 'attended'",
        ['id' => $userId]
    )['c'],
    'events_registered' => $db->fetchOne(
        "SELECT COUNT(*) as c FROM event_registrations WHERE user_id = :id",
        ['id' => $userId]
    )['c'],
    'unread_messages' => $db->fetchOne(
        "SELECT COUNT(*) as c FROM messages WHERE recipient_id = :id AND status = 'unread'",
        ['id' => $userId]
    )['c'],
    'gallery_uploads' => $db->fetchOne(
        "SELECT COUNT(*) as c FROM gallery WHERE uploaded_by = :id",
        ['id' => $userId]
    )['c']
];

// Get upcoming registered events
$upcomingEvents = $db->fetchAll(
    "SELECT e.*, er.status as registration_status
     FROM events e
     INNER JOIN event_registrations er ON e.id = er.event_id
     WHERE er.user_id = :user_id AND e.event_date >= CURDATE()
     ORDER BY e.event_date ASC
     LIMIT 5",
    ['user_id' => $userId]
);

// Get recent notifications
$notifications = $db->fetchAll(
    "SELECT * FROM notifications WHERE user_id = :id ORDER BY created_at DESC LIMIT 5",
    ['id' => $userId]
);

// Get featured events
$featuredEvents = $db->fetchAll(
    "SELECT * FROM events 
     WHERE status = 'upcoming' AND is_featured = 1 AND event_date >= CURDATE()
     ORDER BY event_date ASC
     LIMIT 3"
);
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="theme-color" content="#050a14" />
    <link rel="icon" type="image/png" href="../assets/images/kmc-rc-logo.png">
    <title>Dashboard | KMC Robotics Club</title>
    
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
        .member-card { background: rgba(15, 26, 46, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(0, 242, 255, 0.1); }
        .member-nav { background: rgba(15, 26, 46, 0.95); backdrop-filter: blur(20px); }
    </style>
</head>
<body class="antialiased min-h-screen bg-dark-navy">
    <!-- Navigation -->
    <nav class="member-nav fixed top-0 left-0 right-0 z-50 border-b border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-6">
                    <a href="../index.html" class="flex items-center gap-3">
                        <img src="../assets/images/kmc-rc-logo.png" alt="KMC RC" class="w-8 h-8">
                        <span class="text-white font-bold font-orbitron hidden sm:inline">KMC RC</span>
                    </a>
                    <div class="hidden md:flex items-center gap-1">
                        <a href="dashboard.php" class="px-3 py-2 rounded-lg text-accent bg-accent/10">Dashboard</a>
                        <a href="profile.php" class="px-3 py-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition">Profile</a>
                        <a href="events.php" class="px-3 py-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition">Events</a>
                        <a href="messages.php" class="px-3 py-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition relative">
                            Messages
                            <?php if ($stats['unread_messages'] > 0): ?>
                            <span class="absolute -top-1 -right-1 w-5 h-5 bg-accent text-dark-navy text-xs rounded-full flex items-center justify-center"><?= $stats['unread_messages'] ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
                
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-3">
                        <?php if ($user['avatar']): ?>
                        <img src="../uploads/avatars/<?= htmlspecialchars($user['avatar']) ?>" alt="" class="w-8 h-8 rounded-full object-cover">
                        <?php else: ?>
                        <div class="w-8 h-8 rounded-full bg-accent/10 flex items-center justify-center">
                            <span class="text-accent font-bold text-sm"><?= strtoupper(substr($user['name'], 0, 1)) ?></span>
                        </div>
                        <?php endif; ?>
                        <span class="text-white text-sm hidden sm:inline"><?= htmlspecialchars($user['name']) ?></span>
                    </div>
                    <a href="../auth/logout.php" class="text-slate-400 hover:text-red-400 p-2" title="Logout">
                        <i data-feather="log-out" class="w-5 h-5"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main class="pt-20 pb-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
        <!-- Welcome Section -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-white font-orbitron">Welcome back, <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?>! 👋</h1>
            <p class="text-slate-400 mt-1">Here's what's happening with your account</p>
        </div>
        
        <!-- Stats Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="member-card rounded-lg p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-accent/10 flex items-center justify-center">
                        <i data-feather="calendar" class="w-5 h-5 text-accent"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-white"><?= $stats['events_registered'] ?></div>
                        <div class="text-slate-400 text-sm">Registered Events</div>
                    </div>
                </div>
            </div>
            
            <div class="member-card rounded-lg p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-green-500/10 flex items-center justify-center">
                        <i data-feather="check-circle" class="w-5 h-5 text-green-400"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-white"><?= $stats['events_attended'] ?></div>
                        <div class="text-slate-400 text-sm">Events Attended</div>
                    </div>
                </div>
            </div>
            
            <div class="member-card rounded-lg p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-purple-500/10 flex items-center justify-center">
                        <i data-feather="mail" class="w-5 h-5 text-purple-400"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-white"><?= $stats['unread_messages'] ?></div>
                        <div class="text-slate-400 text-sm">Unread Messages</div>
                    </div>
                </div>
            </div>
            
            <div class="member-card rounded-lg p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-yellow-500/10 flex items-center justify-center">
                        <i data-feather="image" class="w-5 h-5 text-yellow-400"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-white"><?= $stats['gallery_uploads'] ?></div>
                        <div class="text-slate-400 text-sm">Gallery Uploads</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Upcoming Events -->
            <div class="lg:col-span-2">
                <div class="member-card rounded-lg overflow-hidden">
                    <div class="p-4 border-b border-slate-800 flex items-center justify-between">
                        <h2 class="text-lg font-bold text-white">Your Upcoming Events</h2>
                        <a href="events.php" class="text-accent text-sm hover:underline">View All</a>
                    </div>
                    
                    <?php if (!empty($upcomingEvents)): ?>
                    <div class="divide-y divide-slate-800">
                        <?php foreach ($upcomingEvents as $event): ?>
                        <div class="p-4 flex items-center gap-4">
                            <div class="text-center bg-accent/10 rounded-lg p-3 min-w-[60px]">
                                <div class="text-accent text-xl font-bold"><?= date('d', strtotime($event['event_date'])) ?></div>
                                <div class="text-slate-400 text-xs uppercase"><?= date('M', strtotime($event['event_date'])) ?></div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-white font-medium truncate"><?= htmlspecialchars($event['title']) ?></h3>
                                <div class="flex items-center gap-4 text-slate-400 text-sm mt-1">
                                    <?php if ($event['start_time']): ?>
                                    <span class="flex items-center gap-1">
                                        <i data-feather="clock" class="w-4 h-4"></i>
                                        <?= date('g:i A', strtotime($event['start_time'])) ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if ($event['location']): ?>
                                    <span class="flex items-center gap-1">
                                        <i data-feather="map-pin" class="w-4 h-4"></i>
                                        <?= htmlspecialchars($event['location']) ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="px-2 py-1 rounded text-xs bg-green-500/20 text-green-400">
                                <?= ucfirst($event['registration_status']) ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="p-8 text-center text-slate-400">
                        <i data-feather="calendar" class="w-12 h-12 mx-auto mb-3 opacity-50"></i>
                        <p>You haven't registered for any upcoming events</p>
                        <a href="events.php" class="inline-block mt-3 text-accent hover:underline">Browse Events</a>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Featured Events -->
                <?php if (!empty($featuredEvents)): ?>
                <div class="member-card rounded-lg overflow-hidden mt-6">
                    <div class="p-4 border-b border-slate-800">
                        <h2 class="text-lg font-bold text-white">Featured Events</h2>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 p-4">
                        <?php foreach ($featuredEvents as $event): ?>
                        <div class="bg-slate-800/50 rounded-lg overflow-hidden">
                            <?php if ($event['image_path']): ?>
                            <img src="../uploads/events/<?= htmlspecialchars($event['image_path']) ?>" alt="" class="w-full h-32 object-cover">
                            <?php else: ?>
                            <div class="w-full h-32 bg-gradient-to-br from-accent/20 to-secondary-accent/20 flex items-center justify-center">
                                <i data-feather="calendar" class="w-10 h-10 text-accent/50"></i>
                            </div>
                            <?php endif; ?>
                            <div class="p-3">
                                <h3 class="text-white font-medium text-sm truncate"><?= htmlspecialchars($event['title']) ?></h3>
                                <p class="text-slate-400 text-xs mt-1"><?= date('M d, Y', strtotime($event['event_date'])) ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Right Sidebar -->
            <div class="space-y-6">
                <!-- Profile Card -->
                <div class="member-card rounded-lg p-6 text-center">
                    <?php if ($user['avatar']): ?>
                    <img src="../uploads/avatars/<?= htmlspecialchars($user['avatar']) ?>" alt="" class="w-20 h-20 rounded-full object-cover mx-auto mb-4">
                    <?php else: ?>
                    <div class="w-20 h-20 rounded-full bg-accent/10 flex items-center justify-center mx-auto mb-4">
                        <span class="text-accent font-bold text-2xl"><?= strtoupper(substr($user['name'], 0, 1)) ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <h3 class="text-white font-bold"><?= htmlspecialchars($user['name']) ?></h3>
                    <p class="text-slate-400 text-sm"><?= htmlspecialchars($user['email']) ?></p>
                    <span class="inline-block mt-2 px-3 py-1 rounded-full text-xs bg-accent/20 text-accent">
                        <?= ucfirst($user['role']) ?>
                    </span>
                    
                    <div class="mt-4 pt-4 border-t border-slate-800">
                        <div class="text-slate-400 text-sm">Member since</div>
                        <div class="text-white"><?= date('F Y', strtotime($user['created_at'])) ?></div>
                    </div>
                    
                    <a href="profile.php" class="block mt-4 bg-accent/20 text-accent px-4 py-2 rounded-lg hover:bg-accent/30 transition">
                        Edit Profile
                    </a>
                </div>
                
                <!-- Notifications -->
                <div class="member-card rounded-lg overflow-hidden">
                    <div class="p-4 border-b border-slate-800 flex items-center justify-between">
                        <h2 class="text-lg font-bold text-white">Notifications</h2>
                    </div>
                    
                    <?php if (!empty($notifications)): ?>
                    <div class="divide-y divide-slate-800 max-h-64 overflow-y-auto">
                        <?php foreach ($notifications as $notif): ?>
                        <div class="p-3 flex items-start gap-3 <?= !$notif['is_read'] ? 'bg-accent/5' : '' ?>">
                            <div class="w-8 h-8 rounded-full bg-accent/10 flex items-center justify-center flex-shrink-0">
                                <i data-feather="bell" class="w-4 h-4 text-accent"></i>
                            </div>
                            <div>
                                <p class="text-slate-300 text-sm"><?= htmlspecialchars($notif['message']) ?></p>
                                <p class="text-slate-500 text-xs mt-1"><?= date('M d, g:i A', strtotime($notif['created_at'])) ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="p-6 text-center text-slate-400">
                        <i data-feather="bell-off" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                        <p class="text-sm">No notifications</p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Quick Links -->
                <div class="member-card rounded-lg p-4">
                    <h3 class="text-white font-bold mb-3">Quick Links</h3>
                    <div class="space-y-2">
                        <a href="../pages/gallery.php" class="flex items-center gap-2 text-slate-400 hover:text-accent transition">
                            <i data-feather="image" class="w-4 h-4"></i> View Gallery
                        </a>
                        <a href="../pages/team.php" class="flex items-center gap-2 text-slate-400 hover:text-accent transition">
                            <i data-feather="users" class="w-4 h-4"></i> Meet the Team
                        </a>
                        <a href="../pages/about.html" class="flex items-center gap-2 text-slate-400 hover:text-accent transition">
                            <i data-feather="info" class="w-4 h-4"></i> About Us
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <script>feather.replace();</script>
</body>
</html>
