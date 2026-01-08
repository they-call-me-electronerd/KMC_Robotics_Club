<?php
/**
 * KMC Robotics Club - Admin Events Management
 */

require_once __DIR__ . '/../includes/Security.php';
require_once __DIR__ . '/../includes/Database.php';

Security::requireAdmin();

$db = Database::getInstance();

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
    } else {
        $action = $_POST['form_action'] ?? '';
        
        switch ($action) {
            case 'create':
            case 'update':
                $eventData = [
                    'title' => Security::sanitize($_POST['title']),
                    'description' => Security::cleanHtml($_POST['description']),
                    'short_description' => Security::sanitize($_POST['short_description'] ?? ''),
                    'event_date' => $_POST['event_date'],
                    'start_time' => $_POST['start_time'] ?: null,
                    'end_time' => $_POST['end_time'] ?: null,
                    'location' => Security::sanitize($_POST['location'] ?? ''),
                    'category' => $_POST['category'],
                    'max_participants' => !empty($_POST['max_participants']) ? (int)$_POST['max_participants'] : null,
                    'registration_deadline' => $_POST['registration_deadline'] ?: null,
                    'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                    'status' => $_POST['status']
                ];
                
                // Generate slug for new events
                if ($action === 'create') {
                    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $eventData['title']));
                    $slug = preg_replace('/-+/', '-', trim($slug, '-'));
                    $originalSlug = $slug;
                    $counter = 1;
                    while ($db->fetchOne("SELECT id FROM events WHERE slug = :slug", ['slug' => $slug])) {
                        $slug = $originalSlug . '-' . $counter++;
                    }
                    $eventData['slug'] = $slug;
                    $eventData['created_by'] = Security::getCurrentUserId();
                }
                
                // Handle image upload
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $errors = Security::validateFileUpload($_FILES['image']);
                    if (empty($errors)) {
                        $filename = Security::generateSafeFilename($_FILES['image']['name']);
                        if (move_uploaded_file($_FILES['image']['tmp_name'], UPLOAD_EVENTS . '/' . $filename)) {
                            // Delete old image if updating
                            if ($action === 'update' && !empty($_POST['id'])) {
                                $oldEvent = $db->fetchOne("SELECT image_path FROM events WHERE id = :id", ['id' => $_POST['id']]);
                                if ($oldEvent && $oldEvent['image_path'] && file_exists(UPLOAD_EVENTS . '/' . $oldEvent['image_path'])) {
                                    unlink(UPLOAD_EVENTS . '/' . $oldEvent['image_path']);
                                }
                            }
                            $eventData['image_path'] = $filename;
                        }
                    }
                }
                
                if ($action === 'create') {
                    $db->insert('events', $eventData);
                    $message = 'Event created successfully';
                } else {
                    $db->update('events', $eventData, 'id = :id', ['id' => $_POST['id']]);
                    $message = 'Event updated successfully';
                }
                break;
                
            case 'delete':
                $event = $db->fetchOne("SELECT image_path FROM events WHERE id = :id", ['id' => $_POST['id']]);
                if ($event) {
                    if ($event['image_path'] && file_exists(UPLOAD_EVENTS . '/' . $event['image_path'])) {
                        unlink(UPLOAD_EVENTS . '/' . $event['image_path']);
                    }
                    $db->delete('events', 'id = :id', ['id' => $_POST['id']]);
                    $message = 'Event deleted successfully';
                }
                break;
        }
    }
}

// Get events
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$where = '1=1';
$params = [];

if (!empty($_GET['status'])) {
    $where .= ' AND status = :status';
    $params['status'] = $_GET['status'];
}

if (!empty($_GET['category'])) {
    $where .= ' AND category = :category';
    $params['category'] = $_GET['category'];
}

$total = $db->count('events', $where, $params);
$events = $db->fetchAll(
    "SELECT e.*, u.name as created_by_name,
            (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as registration_count
     FROM events e
     LEFT JOIN users u ON e.created_by = u.id
     WHERE {$where}
     ORDER BY e.event_date DESC
     LIMIT {$limit} OFFSET {$offset}",
    $params
);

// Get event for editing
$editEvent = null;
if (isset($_GET['edit'])) {
    $editEvent = $db->fetchOne("SELECT * FROM events WHERE id = :id", ['id' => $_GET['edit']]);
}

$csrfToken = Security::generateCSRFToken();
$showForm = isset($_GET['action']) && $_GET['action'] === 'create' || $editEvent;
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="theme-color" content="#050a14" />
    <link rel="icon" type="image/png" href="../assets/images/kmc-rc-logo.png">
    <title>Manage Events | Admin - KMC RC</title>
    
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
        .sidebar { background: rgba(15, 26, 46, 0.95); backdrop-filter: blur(20px); border-right: 1px solid rgba(0, 242, 255, 0.1); }
        .stat-card-admin { background: rgba(15, 26, 46, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(0, 242, 255, 0.1); }
        .nav-link-admin { transition: all 0.3s ease; }
        .nav-link-admin:hover, .nav-link-admin.active { background: rgba(0, 242, 255, 0.1); border-left: 3px solid #00f2ff; }
        .form-input { background: rgba(5, 10, 20, 0.6); border: 1px solid rgba(0, 242, 255, 0.2); transition: all 0.3s ease; }
        .form-input:focus { border-color: #00f2ff; box-shadow: 0 0 15px rgba(0, 242, 255, 0.2); outline: none; }
    </style>
</head>
<body class="antialiased min-h-screen flex bg-dark-navy">
    <!-- Sidebar (same as dashboard) -->
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
            <a href="dashboard.php" class="nav-link-admin flex items-center gap-3 px-6 py-3 text-slate-400 hover:text-white">
                <i data-feather="grid" class="w-5 h-5"></i> Dashboard
            </a>
            <a href="users.php" class="nav-link-admin flex items-center gap-3 px-6 py-3 text-slate-400 hover:text-white">
                <i data-feather="users" class="w-5 h-5"></i> Members
            </a>
            <a href="events.php" class="nav-link-admin active flex items-center gap-3 px-6 py-3 text-white">
                <i data-feather="calendar" class="w-5 h-5"></i> Events
            </a>
            <a href="team.php" class="nav-link-admin flex items-center gap-3 px-6 py-3 text-slate-400 hover:text-white">
                <i data-feather="award" class="w-5 h-5"></i> Team
            </a>
            <a href="gallery.php" class="nav-link-admin flex items-center gap-3 px-6 py-3 text-slate-400 hover:text-white">
                <i data-feather="image" class="w-5 h-5"></i> Gallery
            </a>
            <a href="messages.php" class="nav-link-admin flex items-center gap-3 px-6 py-3 text-slate-400 hover:text-white">
                <i data-feather="mail" class="w-5 h-5"></i> Messages
            </a>
            <a href="settings.php" class="nav-link-admin flex items-center gap-3 px-6 py-3 text-slate-400 hover:text-white">
                <i data-feather="settings" class="w-5 h-5"></i> Settings
            </a>
        </nav>
        <div class="absolute bottom-0 left-0 right-0 p-6 border-t border-slate-800">
            <a href="../auth/logout.php" class="flex items-center gap-2 text-slate-400 hover:text-red-400 text-sm">
                <i data-feather="log-out" class="w-4 h-4"></i> Logout
            </a>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="flex-1 lg:ml-64">
        <header class="bg-dark-navy/80 backdrop-blur-lg border-b border-slate-800 px-6 py-4 sticky top-0 z-40">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-white font-orbitron">Manage Events</h1>
                    <p class="text-sm text-slate-400">Create, edit, and manage club events</p>
                </div>
                <a href="?action=create" class="bg-accent/20 text-accent px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-accent/30 transition">
                    <i data-feather="plus" class="w-4 h-4"></i> New Event
                </a>
            </div>
        </header>
        
        <div class="p-6">
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
            
            <?php if ($showForm): ?>
            <!-- Event Form -->
            <div class="stat-card-admin rounded-lg p-6 mb-6">
                <h2 class="text-lg font-bold text-white mb-6"><?= $editEvent ? 'Edit Event' : 'Create New Event' ?></h2>
                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="form_action" value="<?= $editEvent ? 'update' : 'create' ?>">
                    <?php if ($editEvent): ?>
                    <input type="hidden" name="id" value="<?= $editEvent['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-300 mb-2">Event Title *</label>
                            <input type="text" name="title" required class="form-input w-full px-4 py-3 rounded-lg text-white"
                                   value="<?= htmlspecialchars($editEvent['title'] ?? '') ?>">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Event Date *</label>
                            <input type="date" name="event_date" required class="form-input w-full px-4 py-3 rounded-lg text-white"
                                   value="<?= $editEvent['event_date'] ?? '' ?>">
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">Start Time</label>
                                <input type="time" name="start_time" class="form-input w-full px-4 py-3 rounded-lg text-white"
                                       value="<?= $editEvent['start_time'] ?? '' ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">End Time</label>
                                <input type="time" name="end_time" class="form-input w-full px-4 py-3 rounded-lg text-white"
                                       value="<?= $editEvent['end_time'] ?? '' ?>">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Location</label>
                            <input type="text" name="location" class="form-input w-full px-4 py-3 rounded-lg text-white"
                                   value="<?= htmlspecialchars($editEvent['location'] ?? '') ?>">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Category</label>
                            <select name="category" class="form-input w-full px-4 py-3 rounded-lg text-white">
                                <option value="workshop" <?= ($editEvent['category'] ?? '') === 'workshop' ? 'selected' : '' ?>>Workshop</option>
                                <option value="competition" <?= ($editEvent['category'] ?? '') === 'competition' ? 'selected' : '' ?>>Competition</option>
                                <option value="seminar" <?= ($editEvent['category'] ?? '') === 'seminar' ? 'selected' : '' ?>>Seminar</option>
                                <option value="hackathon" <?= ($editEvent['category'] ?? '') === 'hackathon' ? 'selected' : '' ?>>Hackathon</option>
                                <option value="meetup" <?= ($editEvent['category'] ?? '') === 'meetup' ? 'selected' : '' ?>>Meetup</option>
                                <option value="other" <?= ($editEvent['category'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Max Participants</label>
                            <input type="number" name="max_participants" class="form-input w-full px-4 py-3 rounded-lg text-white"
                                   value="<?= $editEvent['max_participants'] ?? '' ?>">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Registration Deadline</label>
                            <input type="datetime-local" name="registration_deadline" class="form-input w-full px-4 py-3 rounded-lg text-white"
                                   value="<?= $editEvent['registration_deadline'] ? date('Y-m-d\TH:i', strtotime($editEvent['registration_deadline'])) : '' ?>">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Status</label>
                            <select name="status" class="form-input w-full px-4 py-3 rounded-lg text-white">
                                <option value="upcoming" <?= ($editEvent['status'] ?? '') === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                                <option value="ongoing" <?= ($editEvent['status'] ?? '') === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                                <option value="completed" <?= ($editEvent['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="cancelled" <?= ($editEvent['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-300 mb-2">Short Description</label>
                            <input type="text" name="short_description" maxlength="500" class="form-input w-full px-4 py-3 rounded-lg text-white"
                                   value="<?= htmlspecialchars($editEvent['short_description'] ?? '') ?>">
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-300 mb-2">Full Description *</label>
                            <textarea name="description" rows="5" required class="form-input w-full px-4 py-3 rounded-lg text-white"><?= htmlspecialchars($editEvent['description'] ?? '') ?></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Event Image</label>
                            <input type="file" name="image" accept="image/*" class="form-input w-full px-4 py-3 rounded-lg text-white">
                            <?php if ($editEvent && $editEvent['image_path']): ?>
                            <img src="../uploads/events/<?= htmlspecialchars($editEvent['image_path']) ?>" alt="Current image" class="mt-2 h-20 rounded">
                            <?php endif; ?>
                        </div>
                        
                        <div class="flex items-center gap-4 pt-8">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_featured" class="w-4 h-4 rounded" <?= ($editEvent['is_featured'] ?? 0) ? 'checked' : '' ?>>
                                <span class="text-slate-300">Featured Event</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="flex gap-4 pt-4">
                        <button type="submit" class="bg-accent/20 text-accent px-6 py-3 rounded-lg hover:bg-accent/30 transition flex items-center gap-2">
                            <i data-feather="save" class="w-4 h-4"></i>
                            <?= $editEvent ? 'Update Event' : 'Create Event' ?>
                        </button>
                        <a href="events.php" class="bg-slate-700 text-white px-6 py-3 rounded-lg hover:bg-slate-600 transition">Cancel</a>
                    </div>
                </form>
            </div>
            <?php endif; ?>
            
            <!-- Events List -->
            <div class="stat-card-admin rounded-lg overflow-hidden">
                <div class="p-4 border-b border-slate-800 flex items-center justify-between">
                    <h2 class="text-lg font-bold text-white">All Events</h2>
                    <div class="flex gap-2">
                        <select onchange="window.location.href='?status='+this.value" class="form-input px-3 py-2 rounded text-sm text-white">
                            <option value="">All Status</option>
                            <option value="upcoming" <?= ($_GET['status'] ?? '') === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                            <option value="ongoing" <?= ($_GET['status'] ?? '') === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                            <option value="completed" <?= ($_GET['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="cancelled" <?= ($_GET['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-800/50">
                            <tr>
                                <th class="text-left text-slate-400 text-sm font-medium px-4 py-3">Event</th>
                                <th class="text-left text-slate-400 text-sm font-medium px-4 py-3">Date</th>
                                <th class="text-left text-slate-400 text-sm font-medium px-4 py-3">Category</th>
                                <th class="text-left text-slate-400 text-sm font-medium px-4 py-3">Registrations</th>
                                <th class="text-left text-slate-400 text-sm font-medium px-4 py-3">Status</th>
                                <th class="text-left text-slate-400 text-sm font-medium px-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $event): ?>
                            <tr class="border-b border-slate-800 hover:bg-slate-800/30">
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-3">
                                        <?php if ($event['image_path']): ?>
                                        <img src="../uploads/events/<?= htmlspecialchars($event['image_path']) ?>" alt="" class="w-12 h-12 rounded object-cover">
                                        <?php else: ?>
                                        <div class="w-12 h-12 rounded bg-accent/10 flex items-center justify-center">
                                            <i data-feather="calendar" class="w-6 h-6 text-accent"></i>
                                        </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="text-white font-medium"><?= htmlspecialchars($event['title']) ?></div>
                                            <div class="text-slate-400 text-sm"><?= htmlspecialchars($event['location'] ?: 'No location') ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-slate-300"><?= date('M d, Y', strtotime($event['event_date'])) ?></td>
                                <td class="px-4 py-4">
                                    <span class="px-2 py-1 rounded text-xs bg-purple-500/20 text-purple-400"><?= ucfirst($event['category']) ?></span>
                                </td>
                                <td class="px-4 py-4 text-white font-medium"><?= $event['registration_count'] ?></td>
                                <td class="px-4 py-4">
                                    <span class="px-2 py-1 rounded text-xs <?php
                                        echo match($event['status']) {
                                            'upcoming' => 'bg-green-500/20 text-green-400',
                                            'ongoing' => 'bg-blue-500/20 text-blue-400',
                                            'completed' => 'bg-slate-500/20 text-slate-400',
                                            'cancelled' => 'bg-red-500/20 text-red-400',
                                            default => 'bg-slate-500/20 text-slate-400'
                                        };
                                    ?>"><?= ucfirst($event['status']) ?></span>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-2">
                                        <a href="?edit=<?= $event['id'] ?>" class="text-accent hover:text-accent/80 p-1" title="Edit">
                                            <i data-feather="edit-2" class="w-4 h-4"></i>
                                        </a>
                                        <form method="POST" class="inline" onsubmit="return confirm('Delete this event?')">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="form_action" value="delete">
                                            <input type="hidden" name="id" value="<?= $event['id'] ?>">
                                            <button type="submit" class="text-red-400 hover:text-red-300 p-1" title="Delete">
                                                <i data-feather="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total > $limit): ?>
                <div class="p-4 border-t border-slate-800 flex justify-center gap-2">
                    <?php for ($i = 1; $i <= ceil($total / $limit); $i++): ?>
                    <a href="?page=<?= $i ?>" class="px-3 py-1 rounded <?= $i === $page ? 'bg-accent text-dark-navy' : 'bg-slate-800 text-white hover:bg-slate-700' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <script>feather.replace();</script>
</body>
</html>
