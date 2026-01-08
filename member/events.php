<?php
/**
 * KMC Robotics Club - Member Events
 */

require_once __DIR__ . '/../includes/Security.php';
require_once __DIR__ . '/../includes/Database.php';

Security::requireAuth();

$db = Database::getInstance();
$userId = Security::getCurrentUserId();

// Get user data
$user = $db->fetchOne("SELECT * FROM users WHERE id = :id", ['id' => $userId]);

$message = '';
$error = '';

// Handle RSVP
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
    } else {
        $action = $_POST['form_action'] ?? '';
        $eventId = (int)$_POST['event_id'];
        
        switch ($action) {
            case 'register':
                // Check if already registered
                $existing = $db->fetchOne(
                    "SELECT id FROM event_registrations WHERE event_id = :event_id AND user_id = :user_id",
                    ['event_id' => $eventId, 'user_id' => $userId]
                );
                
                if ($existing) {
                    $error = 'You are already registered for this event';
                } else {
                    // Check if event is full
                    $event = $db->fetchOne("SELECT * FROM events WHERE id = :id", ['id' => $eventId]);
                    if ($event && $event['max_participants']) {
                        $currentCount = $db->fetchOne(
                            "SELECT COUNT(*) as c FROM event_registrations WHERE event_id = :id",
                            ['id' => $eventId]
                        )['c'];
                        
                        if ($currentCount >= $event['max_participants']) {
                            $error = 'This event is full';
                            break;
                        }
                    }
                    
                    $db->insert('event_registrations', [
                        'event_id' => $eventId,
                        'user_id' => $userId,
                        'status' => 'registered'
                    ]);
                    $message = 'Successfully registered for the event!';
                }
                break;
                
            case 'cancel':
                $db->delete('event_registrations', 'event_id = :event_id AND user_id = :user_id', [
                    'event_id' => $eventId,
                    'user_id' => $userId
                ]);
                $message = 'Registration cancelled';
                break;
        }
    }
}

// Get events
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

$filter = $_GET['filter'] ?? 'upcoming';

switch ($filter) {
    case 'registered':
        $events = $db->fetchAll(
            "SELECT e.*, er.status as registration_status, er.created_at as registered_at
             FROM events e
             INNER JOIN event_registrations er ON e.id = er.event_id
             WHERE er.user_id = :user_id
             ORDER BY e.event_date DESC
             LIMIT {$limit} OFFSET {$offset}",
            ['user_id' => $userId]
        );
        $total = $db->fetchOne(
            "SELECT COUNT(*) as c FROM event_registrations WHERE user_id = :user_id",
            ['user_id' => $userId]
        )['c'];
        break;
        
    case 'past':
        $events = $db->fetchAll(
            "SELECT e.*, 
                    (SELECT status FROM event_registrations WHERE event_id = e.id AND user_id = :user_id) as registration_status
             FROM events e
             WHERE e.event_date < CURDATE()
             ORDER BY e.event_date DESC
             LIMIT {$limit} OFFSET {$offset}",
            ['user_id' => $userId]
        );
        $total = $db->count('events', "event_date < CURDATE()");
        break;
        
    default: // upcoming
        $events = $db->fetchAll(
            "SELECT e.*, 
                    (SELECT status FROM event_registrations WHERE event_id = e.id AND user_id = :user_id) as registration_status,
                    (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as registration_count
             FROM events e
             WHERE e.event_date >= CURDATE() AND e.status IN ('upcoming', 'ongoing')
             ORDER BY e.event_date ASC
             LIMIT {$limit} OFFSET {$offset}",
            ['user_id' => $userId]
        );
        $total = $db->count('events', "event_date >= CURDATE() AND status IN ('upcoming', 'ongoing')");
        break;
}

// Get stats
$stats = [
    'upcoming' => $db->count('events', "event_date >= CURDATE() AND status IN ('upcoming', 'ongoing')"),
    'registered' => $db->fetchOne(
        "SELECT COUNT(*) as c FROM event_registrations WHERE user_id = :id",
        ['id' => $userId]
    )['c'],
    'attended' => $db->fetchOne(
        "SELECT COUNT(*) as c FROM event_registrations WHERE user_id = :id AND status = 'attended'",
        ['id' => $userId]
    )['c']
];

$csrfToken = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="theme-color" content="#050a14" />
    <link rel="icon" type="image/png" href="../assets/images/kmc-rc-logo.png">
    <title>Events | KMC Robotics Club</title>
    
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
        .event-card { transition: all 0.3s ease; }
        .event-card:hover { transform: translateY(-5px); border-color: rgba(0, 242, 255, 0.3); }
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
                        <a href="dashboard.php" class="px-3 py-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition">Dashboard</a>
                        <a href="profile.php" class="px-3 py-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition">Profile</a>
                        <a href="events.php" class="px-3 py-2 rounded-lg text-accent bg-accent/10">Events</a>
                        <a href="messages.php" class="px-3 py-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition">Messages</a>
                    </div>
                </div>
                
                <div class="flex items-center gap-4">
                    <span class="text-white text-sm hidden sm:inline"><?= htmlspecialchars($user['name']) ?></span>
                    <a href="../auth/logout.php" class="text-slate-400 hover:text-red-400 p-2" title="Logout">
                        <i data-feather="log-out" class="w-5 h-5"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main class="pt-20 pb-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <h1 class="text-2xl font-bold text-white font-orbitron">Events</h1>
            
            <!-- Stats -->
            <div class="flex gap-4">
                <div class="text-center">
                    <div class="text-xl font-bold text-accent"><?= $stats['upcoming'] ?></div>
                    <div class="text-slate-400 text-xs">Upcoming</div>
                </div>
                <div class="text-center">
                    <div class="text-xl font-bold text-green-400"><?= $stats['registered'] ?></div>
                    <div class="text-slate-400 text-xs">Registered</div>
                </div>
                <div class="text-center">
                    <div class="text-xl font-bold text-purple-400"><?= $stats['attended'] ?></div>
                    <div class="text-slate-400 text-xs">Attended</div>
                </div>
            </div>
        </div>
        
        <?php if ($message): ?>
        <div class="bg-green-500/10 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg mb-6 flex items-center gap-3">
            <i data-feather="check-circle" class="w-5 h-5"></i>
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-lg mb-6 flex items-center gap-3">
            <i data-feather="alert-circle" class="w-5 h-5"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        
        <!-- Filters -->
        <div class="flex gap-2 mb-6">
            <a href="?filter=upcoming" class="px-4 py-2 rounded-lg <?= $filter === 'upcoming' ? 'bg-accent text-dark-navy' : 'bg-slate-800 text-white hover:bg-slate-700' ?> transition">
                Upcoming
            </a>
            <a href="?filter=registered" class="px-4 py-2 rounded-lg <?= $filter === 'registered' ? 'bg-accent text-dark-navy' : 'bg-slate-800 text-white hover:bg-slate-700' ?> transition">
                My Registrations
            </a>
            <a href="?filter=past" class="px-4 py-2 rounded-lg <?= $filter === 'past' ? 'bg-accent text-dark-navy' : 'bg-slate-800 text-white hover:bg-slate-700' ?> transition">
                Past Events
            </a>
        </div>
        
        <!-- Events Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($events as $event): ?>
            <div class="event-card member-card rounded-lg overflow-hidden">
                <?php if ($event['image_path']): ?>
                <img src="../uploads/events/<?= htmlspecialchars($event['image_path']) ?>" alt="" class="w-full h-48 object-cover">
                <?php else: ?>
                <div class="w-full h-48 bg-gradient-to-br from-accent/20 to-secondary-accent/20 flex items-center justify-center">
                    <i data-feather="calendar" class="w-16 h-16 text-accent/50"></i>
                </div>
                <?php endif; ?>
                
                <div class="p-4">
                    <div class="flex items-start justify-between mb-2">
                        <span class="px-2 py-1 rounded text-xs bg-purple-500/20 text-purple-400"><?= ucfirst($event['category']) ?></span>
                        <?php if ($event['is_featured']): ?>
                        <span class="px-2 py-1 rounded text-xs bg-yellow-500/20 text-yellow-400">Featured</span>
                        <?php endif; ?>
                    </div>
                    
                    <h3 class="text-lg font-bold text-white mb-2"><?= htmlspecialchars($event['title']) ?></h3>
                    
                    <div class="space-y-2 text-slate-400 text-sm mb-4">
                        <div class="flex items-center gap-2">
                            <i data-feather="calendar" class="w-4 h-4"></i>
                            <?= date('M d, Y', strtotime($event['event_date'])) ?>
                            <?php if ($event['start_time']): ?>
                            @ <?= date('g:i A', strtotime($event['start_time'])) ?>
                            <?php endif; ?>
                        </div>
                        <?php if ($event['location']): ?>
                        <div class="flex items-center gap-2">
                            <i data-feather="map-pin" class="w-4 h-4"></i>
                            <?= htmlspecialchars($event['location']) ?>
                        </div>
                        <?php endif; ?>
                        <?php if (isset($event['registration_count']) && $event['max_participants']): ?>
                        <div class="flex items-center gap-2">
                            <i data-feather="users" class="w-4 h-4"></i>
                            <?= $event['registration_count'] ?>/<?= $event['max_participants'] ?> registered
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($event['short_description']): ?>
                    <p class="text-slate-300 text-sm mb-4 line-clamp-2"><?= htmlspecialchars($event['short_description']) ?></p>
                    <?php endif; ?>
                    
                    <div class="flex items-center justify-between pt-4 border-t border-slate-800">
                        <?php if ($event['registration_status']): ?>
                        <span class="text-green-400 text-sm flex items-center gap-1">
                            <i data-feather="check-circle" class="w-4 h-4"></i>
                            <?= ucfirst($event['registration_status']) ?>
                        </span>
                        <?php if ($event['event_date'] >= date('Y-m-d')): ?>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <input type="hidden" name="form_action" value="cancel">
                            <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                            <button type="submit" class="text-red-400 text-sm hover:text-red-300" onclick="return confirm('Cancel registration?')">
                                Cancel
                            </button>
                        </form>
                        <?php endif; ?>
                        <?php else: ?>
                        <?php if ($event['event_date'] >= date('Y-m-d') && $event['status'] !== 'cancelled'): ?>
                        <form method="POST" class="flex-1">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <input type="hidden" name="form_action" value="register">
                            <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                            <button type="submit" class="w-full bg-accent/20 text-accent px-4 py-2 rounded-lg hover:bg-accent/30 transition flex items-center justify-center gap-2">
                                <i data-feather="user-plus" class="w-4 h-4"></i>
                                Register
                            </button>
                        </form>
                        <?php else: ?>
                        <span class="text-slate-500 text-sm">Event ended</span>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($events)): ?>
            <div class="col-span-full text-center py-12 text-slate-400">
                <i data-feather="calendar" class="w-16 h-16 mx-auto mb-4 opacity-50"></i>
                <p>No events found</p>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if ($total > $limit): ?>
        <div class="mt-8 flex justify-center gap-2">
            <?php 
            $totalPages = ceil($total / $limit);
            for ($i = 1; $i <= $totalPages; $i++): 
            ?>
            <a href="?filter=<?= $filter ?>&page=<?= $i ?>" class="px-3 py-1 rounded <?= $i === $page ? 'bg-accent text-dark-navy' : 'bg-slate-800 text-white hover:bg-slate-700' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </main>
    
    <script>feather.replace();</script>
</body>
</html>
