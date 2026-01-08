<?php
/**
 * KMC Robotics Club - Admin Messages Management
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
            case 'reply':
                $originalMessage = $db->fetchOne("SELECT * FROM messages WHERE id = :id", ['id' => $_POST['message_id']]);
                if ($originalMessage) {
                    // Mark original as read/replied
                    $db->update('messages', ['status' => 'replied'], 'id = :id', ['id' => $_POST['message_id']]);
                    
                    // Create reply
                    $replyData = [
                        'sender_id' => Security::getCurrentUserId(),
                        'recipient_id' => $originalMessage['sender_id'],
                        'subject' => 'Re: ' . $originalMessage['subject'],
                        'message' => Security::cleanHtml($_POST['reply_message']),
                        'parent_id' => $originalMessage['id']
                    ];
                    
                    $db->insert('messages', $replyData);
                    $message = 'Reply sent successfully';
                }
                break;
                
            case 'mark-read':
                $db->update('messages', ['status' => 'read', 'read_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $_POST['id']]);
                $message = 'Marked as read';
                break;
                
            case 'mark-unread':
                $db->update('messages', ['status' => 'unread'], 'id = :id', ['id' => $_POST['id']]);
                $message = 'Marked as unread';
                break;
                
            case 'archive':
                $db->update('messages', ['status' => 'archived'], 'id = :id', ['id' => $_POST['id']]);
                $message = 'Message archived';
                break;
                
            case 'delete':
                $db->delete('messages', 'id = :id', ['id' => $_POST['id']]);
                $message = 'Message deleted';
                break;
                
            case 'bulk-action':
                if (!empty($_POST['selected'])) {
                    $bulkAction = $_POST['bulk_action'];
                    $count = 0;
                    foreach ($_POST['selected'] as $id) {
                        switch ($bulkAction) {
                            case 'mark-read':
                                $db->update('messages', ['status' => 'read', 'read_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $id]);
                                break;
                            case 'mark-unread':
                                $db->update('messages', ['status' => 'unread'], 'id = :id', ['id' => $id]);
                                break;
                            case 'archive':
                                $db->update('messages', ['status' => 'archived'], 'id = :id', ['id' => $id]);
                                break;
                            case 'delete':
                                $db->delete('messages', 'id = :id', ['id' => $id]);
                                break;
                        }
                        $count++;
                    }
                    $message = "Action applied to {$count} messages";
                }
                break;
        }
    }
}

// Get messages
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$where = 'm.recipient_id IS NULL OR m.recipient_id = :admin_id'; // Contact form messages or messages to admin
$params = ['admin_id' => Security::getCurrentUserId()];

$filter = $_GET['filter'] ?? 'inbox';

switch ($filter) {
    case 'unread':
        $where .= " AND m.status = 'unread'";
        break;
    case 'contact':
        $where = "m.is_from_guest = 1";
        $params = [];
        break;
    case 'archived':
        $where .= " AND m.status = 'archived'";
        break;
    case 'sent':
        $where = 'm.sender_id = :sender_id';
        $params = ['sender_id' => Security::getCurrentUserId()];
        break;
    default: // inbox
        $where .= " AND m.status != 'archived'";
        break;
}

$total = $db->fetchOne(
    "SELECT COUNT(*) as count FROM messages m WHERE {$where}",
    $params
)['count'];

$messages = $db->fetchAll(
    "SELECT m.*, 
            s.name as sender_name, s.email as sender_email, s.profile_pic as sender_avatar,
            r.name as receiver_name
     FROM messages m
     LEFT JOIN users s ON m.sender_id = s.id
     LEFT JOIN users r ON m.recipient_id = r.id
     WHERE {$where}
     ORDER BY m.created_at DESC
     LIMIT {$limit} OFFSET {$offset}",
    $params
);

// Get message for viewing
$viewMessage = null;
if (isset($_GET['view'])) {
    $viewMessage = $db->fetchOne(
        "SELECT m.*, 
                s.name as sender_name, s.email as sender_email, s.profile_pic as sender_avatar
         FROM messages m
         LEFT JOIN users s ON m.sender_id = s.id
         WHERE m.id = :id",
        ['id' => $_GET['view']]
    );
    
    if ($viewMessage && $viewMessage['status'] == 'unread') {
        $db->update('messages', ['status' => 'read', 'read_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $_GET['view']]);
    }
    
    // Get replies
    if ($viewMessage) {
        $replies = $db->fetchAll(
            "SELECT m.*, s.name as sender_name, s.profile_pic as sender_avatar
             FROM messages m
             LEFT JOIN users s ON m.sender_id = s.id
             WHERE m.parent_id = :parent_id
             ORDER BY m.created_at ASC",
            ['parent_id' => $viewMessage['id']]
        );
    }
}

// Stats
$stats = [
    'total' => $db->fetchOne("SELECT COUNT(*) as c FROM messages WHERE recipient_id IS NULL OR recipient_id = :id", ['id' => Security::getCurrentUserId()])['c'],
    'unread' => $db->fetchOne("SELECT COUNT(*) as c FROM messages WHERE (recipient_id IS NULL OR recipient_id = :id) AND status = 'unread'", ['id' => Security::getCurrentUserId()])['c'],
    'contact' => $db->fetchOne("SELECT COUNT(*) as c FROM messages WHERE is_from_guest = 1 AND status = 'unread'")['c']
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
    <title>Messages | Admin - KMC RC</title>
    
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
        .message-row { transition: all 0.2s ease; }
        .message-row:hover { background: rgba(0, 242, 255, 0.05); }
        .message-row.unread { background: rgba(0, 242, 255, 0.08); border-left: 3px solid #00f2ff; }
    </style>
</head>
<body class="antialiased min-h-screen flex bg-dark-navy">
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
            <a href="dashboard.php" class="nav-link-admin flex items-center gap-3 px-6 py-3 text-slate-400 hover:text-white">
                <i data-feather="grid" class="w-5 h-5"></i> Dashboard
            </a>
            <a href="users.php" class="nav-link-admin flex items-center gap-3 px-6 py-3 text-slate-400 hover:text-white">
                <i data-feather="users" class="w-5 h-5"></i> Members
            </a>
            <a href="events.php" class="nav-link-admin flex items-center gap-3 px-6 py-3 text-slate-400 hover:text-white">
                <i data-feather="calendar" class="w-5 h-5"></i> Events
            </a>
            <a href="team.php" class="nav-link-admin flex items-center gap-3 px-6 py-3 text-slate-400 hover:text-white">
                <i data-feather="award" class="w-5 h-5"></i> Team
            </a>
            <a href="gallery.php" class="nav-link-admin flex items-center gap-3 px-6 py-3 text-slate-400 hover:text-white">
                <i data-feather="image" class="w-5 h-5"></i> Gallery
            </a>
            <a href="messages.php" class="nav-link-admin active flex items-center gap-3 px-6 py-3 text-white">
                <i data-feather="mail" class="w-5 h-5"></i> Messages
                <?php if ($stats['unread'] > 0): ?>
                <span class="ml-auto bg-accent text-dark-navy text-xs px-2 py-0.5 rounded-full"><?= $stats['unread'] ?></span>
                <?php endif; ?>
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
                    <h1 class="text-xl font-bold text-white font-orbitron">Messages</h1>
                    <p class="text-sm text-slate-400">View and respond to messages</p>
                </div>
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
            
            <div class="flex gap-6">
                <!-- Sidebar Filters -->
                <div class="w-56 flex-shrink-0 hidden md:block">
                    <div class="stat-card-admin rounded-lg p-4 space-y-2">
                        <a href="?filter=inbox" class="flex items-center justify-between px-3 py-2 rounded-lg <?= $filter === 'inbox' ? 'bg-accent/10 text-accent' : 'text-slate-400 hover:text-white hover:bg-slate-800' ?> transition">
                            <span class="flex items-center gap-2">
                                <i data-feather="inbox" class="w-4 h-4"></i> Inbox
                            </span>
                            <span class="text-sm"><?= $stats['total'] ?></span>
                        </a>
                        <a href="?filter=unread" class="flex items-center justify-between px-3 py-2 rounded-lg <?= $filter === 'unread' ? 'bg-accent/10 text-accent' : 'text-slate-400 hover:text-white hover:bg-slate-800' ?> transition">
                            <span class="flex items-center gap-2">
                                <i data-feather="mail" class="w-4 h-4"></i> Unread
                            </span>
                            <span class="text-sm bg-accent text-dark-navy px-2 rounded-full"><?= $stats['unread'] ?></span>
                        </a>
                        <a href="?filter=contact" class="flex items-center justify-between px-3 py-2 rounded-lg <?= $filter === 'contact' ? 'bg-accent/10 text-accent' : 'text-slate-400 hover:text-white hover:bg-slate-800' ?> transition">
                            <span class="flex items-center gap-2">
                                <i data-feather="user" class="w-4 h-4"></i> Contact Form
                            </span>
                            <?php if ($stats['contact'] > 0): ?>
                            <span class="text-sm bg-yellow-500 text-dark-navy px-2 rounded-full"><?= $stats['contact'] ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="?filter=sent" class="flex items-center justify-between px-3 py-2 rounded-lg <?= $filter === 'sent' ? 'bg-accent/10 text-accent' : 'text-slate-400 hover:text-white hover:bg-slate-800' ?> transition">
                            <span class="flex items-center gap-2">
                                <i data-feather="send" class="w-4 h-4"></i> Sent
                            </span>
                        </a>
                        <a href="?filter=archived" class="flex items-center justify-between px-3 py-2 rounded-lg <?= $filter === 'archived' ? 'bg-accent/10 text-accent' : 'text-slate-400 hover:text-white hover:bg-slate-800' ?> transition">
                            <span class="flex items-center gap-2">
                                <i data-feather="archive" class="w-4 h-4"></i> Archived
                            </span>
                        </a>
                    </div>
                </div>
                
                <!-- Main Content Area -->
                <div class="flex-1">
                    <?php if ($viewMessage): ?>
                    <!-- View Message -->
                    <div class="stat-card-admin rounded-lg">
                        <div class="p-4 border-b border-slate-800 flex items-center justify-between">
                            <a href="messages.php?filter=<?= $filter ?>" class="text-slate-400 hover:text-white flex items-center gap-2">
                                <i data-feather="arrow-left" class="w-4 h-4"></i> Back
                            </a>
                            <div class="flex items-center gap-2">
                                <?php if ($viewMessage['status'] !== 'archived'): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <input type="hidden" name="form_action" value="archive">
                                    <input type="hidden" name="id" value="<?= $viewMessage['id'] ?>">
                                    <button type="submit" class="text-slate-400 hover:text-white p-2" title="Archive">
                                        <i data-feather="archive" class="w-4 h-4"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                                <form method="POST" class="inline" onsubmit="return confirm('Delete this message?')">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <input type="hidden" name="form_action" value="delete">
                                    <input type="hidden" name="id" value="<?= $viewMessage['id'] ?>">
                                    <button type="submit" class="text-red-400 hover:text-red-300 p-2" title="Delete">
                                        <i data-feather="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="p-6">
                            <h2 class="text-xl font-bold text-white mb-4"><?= htmlspecialchars($viewMessage['subject']) ?></h2>
                            
                            <div class="flex items-center gap-4 mb-6 pb-6 border-b border-slate-800">
                                <?php if ($viewMessage['sender_avatar']): ?>
                                <img src="../uploads/profiles/<?= htmlspecialchars($viewMessage['sender_avatar']) ?>" alt="" class="w-12 h-12 rounded-full object-cover">
                                <?php else: ?>
                                <div class="w-12 h-12 rounded-full bg-accent/10 flex items-center justify-center">
                                    <span class="text-accent font-bold text-lg"><?= strtoupper(substr($viewMessage['sender_name'] ?? $viewMessage['sender_email'] ?? 'U', 0, 1)) ?></span>
                                </div>
                                <?php endif; ?>
                                <div>
                                    <div class="text-white font-medium"><?= htmlspecialchars($viewMessage['sender_name'] ?? 'Unknown') ?></div>
                                    <div class="text-slate-400 text-sm"><?= htmlspecialchars($viewMessage['sender_email'] ?? '') ?></div>
                                </div>
                                <div class="ml-auto text-slate-400 text-sm">
                                    <?= date('M d, Y \a\t g:i A', strtotime($viewMessage['created_at'])) ?>
                                </div>
                            </div>
                            
                            <div class="prose prose-invert max-w-none">
                                <div class="text-slate-300 whitespace-pre-wrap"><?= nl2br(htmlspecialchars($viewMessage['message'])) ?></div>
                            </div>
                            
                            <?php if (!empty($replies)): ?>
                            <div class="mt-8 pt-8 border-t border-slate-800">
                                <h3 class="text-lg font-bold text-white mb-4">Replies</h3>
                                <div class="space-y-4">
                                    <?php foreach ($replies as $reply): ?>
                                    <div class="bg-slate-800/50 rounded-lg p-4">
                                        <div class="flex items-center gap-3 mb-3">
                                            <?php if ($reply['sender_avatar']): ?>
                                            <img src="../uploads/profiles/<?= htmlspecialchars($reply['sender_avatar']) ?>" alt="" class="w-8 h-8 rounded-full object-cover">
                                            <?php else: ?>
                                            <div class="w-8 h-8 rounded-full bg-accent/10 flex items-center justify-center">
                                                <span class="text-accent font-bold text-sm"><?= strtoupper(substr($reply['sender_name'] ?? 'U', 0, 1)) ?></span>
                                            </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="text-white text-sm font-medium"><?= htmlspecialchars($reply['sender_name'] ?? 'Unknown') ?></div>
                                                <div class="text-slate-400 text-xs"><?= date('M d, Y \a\t g:i A', strtotime($reply['created_at'])) ?></div>
                                            </div>
                                        </div>
                                        <div class="text-slate-300 text-sm"><?= nl2br(htmlspecialchars($reply['message'])) ?></div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Reply Form -->
                            <?php if ($viewMessage['sender_id']): ?>
                            <div class="mt-8 pt-8 border-t border-slate-800">
                                <h3 class="text-lg font-bold text-white mb-4">Reply</h3>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <input type="hidden" name="form_action" value="reply">
                                    <input type="hidden" name="message_id" value="<?= $viewMessage['id'] ?>">
                                    
                                    <textarea name="reply_message" rows="4" required placeholder="Type your reply..." 
                                              class="form-input w-full px-4 py-3 rounded-lg text-white mb-4"></textarea>
                                    
                                    <button type="submit" class="bg-accent/20 text-accent px-6 py-3 rounded-lg hover:bg-accent/30 transition flex items-center gap-2">
                                        <i data-feather="send" class="w-4 h-4"></i>
                                        Send Reply
                                    </button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php else: ?>
                    <!-- Messages List -->
                    <form method="POST" id="bulkForm">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <input type="hidden" name="form_action" value="bulk-action">
                        
                        <div class="stat-card-admin rounded-lg overflow-hidden">
                            <!-- Bulk Actions -->
                            <div class="p-4 border-b border-slate-800 flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <input type="checkbox" id="selectAll" class="w-4 h-4 rounded">
                                    <select name="bulk_action" class="form-input px-3 py-2 rounded text-sm text-white">
                                        <option value="">Bulk Actions</option>
                                        <option value="mark-read">Mark as Read</option>
                                        <option value="mark-unread">Mark as Unread</option>
                                        <option value="archive">Archive</option>
                                        <option value="delete">Delete</option>
                                    </select>
                                    <button type="submit" class="bg-slate-700 text-white px-4 py-2 rounded text-sm hover:bg-slate-600 transition">
                                        Apply
                                    </button>
                                </div>
                                <div class="text-slate-400 text-sm">
                                    <?= $total ?> messages
                                </div>
                            </div>
                            
                            <!-- Messages -->
                            <div class="divide-y divide-slate-800">
                                <?php foreach ($messages as $msg): ?>
                                <div class="message-row <?= $msg['status'] === 'unread' ? 'unread' : '' ?> flex items-center gap-4 px-4 py-3">
                                    <input type="checkbox" name="selected[]" value="<?= $msg['id'] ?>" class="select-checkbox w-4 h-4 rounded">
                                    
                                    <a href="?view=<?= $msg['id'] ?>&filter=<?= $filter ?>" class="flex-1 flex items-center gap-4 min-w-0">
                                        <?php if ($msg['sender_avatar']): ?>
                                        <img src="../uploads/profiles/<?= htmlspecialchars($msg['sender_avatar']) ?>" alt="" class="w-10 h-10 rounded-full object-cover flex-shrink-0">
                                        <?php else: ?>
                                        <div class="w-10 h-10 rounded-full bg-accent/10 flex items-center justify-center flex-shrink-0">
                                            <span class="text-accent font-bold"><?= strtoupper(substr($msg['sender_name'] ?? $msg['sender_email'] ?? 'U', 0, 1)) ?></span>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="text-white font-medium <?= $msg['status'] === 'unread' ? 'font-bold' : '' ?>">
                                                    <?= htmlspecialchars($msg['sender_name'] ?? 'Unknown') ?>
                                                </span>
                                                <?php if ($msg['is_from_guest']): ?>
                                                <span class="px-2 py-0.5 rounded text-xs bg-yellow-500/20 text-yellow-400">Contact</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-slate-300 truncate <?= $msg['status'] === 'unread' ? 'font-medium' : '' ?>">
                                                <?= htmlspecialchars($msg['subject']) ?>
                                            </div>
                                            <div class="text-slate-400 text-sm truncate">
                                                <?= htmlspecialchars(substr($msg['message'], 0, 80)) ?>...
                                            </div>
                                        </div>
                                        
                                        <div class="text-slate-400 text-sm flex-shrink-0">
                                            <?= date('M d', strtotime($msg['created_at'])) ?>
                                        </div>
                                    </a>
                                </div>
                                <?php endforeach; ?>
                                
                                <?php if (empty($messages)): ?>
                                <div class="px-4 py-12 text-center text-slate-400">
                                    <i data-feather="inbox" class="w-12 h-12 mx-auto mb-4 opacity-50"></i>
                                    <p>No messages found</p>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($total > $limit): ?>
                            <div class="p-4 border-t border-slate-800 flex justify-center gap-2">
                                <?php 
                                $totalPages = ceil($total / $limit);
                                for ($i = 1; $i <= $totalPages; $i++): 
                                ?>
                                <a href="?filter=<?= $filter ?>&page=<?= $i ?>" class="px-3 py-1 rounded <?= $i === $page ? 'bg-accent text-dark-navy' : 'bg-slate-800 text-white hover:bg-slate-700' ?>"><?= $i ?></a>
                                <?php endfor; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        feather.replace();
        
        // Select all checkbox
        document.getElementById('selectAll')?.addEventListener('change', function() {
            document.querySelectorAll('.select-checkbox').forEach(cb => {
                cb.checked = this.checked;
            });
        });
    </script>
</body>
</html>
