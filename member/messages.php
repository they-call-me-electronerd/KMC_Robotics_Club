<?php
/**
 * KMC Robotics Club - Member Messages
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
    } else {
        $action = $_POST['form_action'] ?? '';
        
        switch ($action) {
            case 'send':
                $receiverId = (int)$_POST['receiver_id'];
                $subject = Security::sanitize($_POST['subject']);
                $messageText = Security::cleanHtml($_POST['message']);
                
                if (empty($subject) || empty($messageText)) {
                    $error = 'Subject and message are required';
                } else {
                    $db->insert('messages', [
                        'sender_id' => $userId,
                        'recipient_id' => $receiverId,
                        'subject' => $subject,
                        'message' => $messageText
                    ]);
                    $message = 'Message sent successfully';
                }
                break;
                
            case 'reply':
                $parentId = (int)$_POST['parent_id'];
                $messageText = Security::cleanHtml($_POST['reply_message']);
                
                $parentMessage = $db->fetchOne("SELECT * FROM messages WHERE id = :id", ['id' => $parentId]);
                if ($parentMessage && !empty($messageText)) {
                    $db->insert('messages', [
                        'sender_id' => $userId,
                        'recipient_id' => $parentMessage['sender_id'],
                        'subject' => 'Re: ' . $parentMessage['subject'],
                        'message' => $messageText,
                        'parent_id' => $parentId
                    ]);
                    $db->update('messages', ['status' => 'replied'], 'id = :id', ['id' => $parentId]);
                    $message = 'Reply sent successfully';
                }
                break;
                
            case 'delete':
                $msgId = (int)$_POST['message_id'];
                $db->delete('messages', 'id = :id AND (sender_id = :uid OR recipient_id = :uid)', [
                    'id' => $msgId,
                    'uid' => $userId
                ]);
                $message = 'Message deleted';
                break;
        }
    }
}

// Get messages
$filter = $_GET['filter'] ?? 'inbox';

switch ($filter) {
    case 'sent':
        $messages = $db->fetchAll(
            "SELECT m.*, r.name as receiver_name, r.avatar as receiver_avatar
             FROM messages m
             LEFT JOIN users r ON m.recipient_id = r.id
             WHERE m.sender_id = :user_id AND m.parent_id IS NULL
             ORDER BY m.created_at DESC",
            ['user_id' => $userId]
        );
        break;
        
    default: // inbox
        $messages = $db->fetchAll(
            "SELECT m.*, s.name as sender_name, s.avatar as sender_avatar
             FROM messages m
             LEFT JOIN users s ON m.sender_id = s.id
             WHERE m.recipient_id = :user_id AND m.status != 'archived'
             ORDER BY m.created_at DESC",
            ['user_id' => $userId]
        );
        break;
}

// Get message for viewing
$viewMessage = null;
$replies = [];
if (isset($_GET['view'])) {
    $viewMessage = $db->fetchOne(
        "SELECT m.*, s.name as sender_name, s.email as sender_email, s.avatar as sender_avatar
         FROM messages m
         LEFT JOIN users s ON m.sender_id = s.id
         WHERE m.id = :id AND (m.recipient_id = :user_id OR m.sender_id = :user_id)",
        ['id' => $_GET['view'], 'user_id' => $userId]
    );
    
    if ($viewMessage && $viewMessage['status'] == 'unread' && $viewMessage['recipient_id'] == $userId) {
        $db->update('messages', ['status' => 'read', 'read_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $_GET['view']]);
    }
    
    if ($viewMessage) {
        $replies = $db->fetchAll(
            "SELECT m.*, s.name as sender_name, s.avatar as sender_avatar
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
    'inbox' => $db->fetchOne(
        "SELECT COUNT(*) as c FROM messages WHERE recipient_id = :id AND status != 'archived'",
        ['id' => $userId]
    )['c'],
    'unread' => $db->fetchOne(
        "SELECT COUNT(*) as c FROM messages WHERE recipient_id = :id AND status = 'unread'",
        ['id' => $userId]
    )['c'],
    'sent' => $db->fetchOne(
        "SELECT COUNT(*) as c FROM messages WHERE sender_id = :id AND parent_id IS NULL",
        ['id' => $userId]
    )['c']
];

// Get users for compose
$users = $db->fetchAll(
    "SELECT id, name, avatar FROM users WHERE id != :id AND status = 'active' ORDER BY name",
    ['id' => $userId]
);

$csrfToken = Security::generateCSRFToken();
$showCompose = isset($_GET['compose']);
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="theme-color" content="#050a14" />
    <link rel="icon" type="image/png" href="../assets/images/kmc-rc-logo.png">
    <title>Messages | KMC Robotics Club</title>
    
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
        .form-input { background: rgba(5, 10, 20, 0.6); border: 1px solid rgba(0, 242, 255, 0.2); transition: all 0.3s ease; }
        .form-input:focus { border-color: #00f2ff; box-shadow: 0 0 15px rgba(0, 242, 255, 0.2); outline: none; }
        .message-row { transition: all 0.2s ease; }
        .message-row:hover { background: rgba(0, 242, 255, 0.05); }
        .message-row.unread { background: rgba(0, 242, 255, 0.08); border-left: 3px solid #00f2ff; }
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
                        <a href="events.php" class="px-3 py-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition">Events</a>
                        <a href="messages.php" class="px-3 py-2 rounded-lg text-accent bg-accent/10 relative">
                            Messages
                            <?php if ($stats['unread'] > 0): ?>
                            <span class="absolute -top-1 -right-1 w-5 h-5 bg-accent text-dark-navy text-xs rounded-full flex items-center justify-center"><?= $stats['unread'] ?></span>
                            <?php endif; ?>
                        </a>
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
    <main class="pt-20 pb-12 px-4 sm:px-6 lg:px-8 max-w-6xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-white font-orbitron">Messages</h1>
            <a href="?compose" class="bg-accent/20 text-accent px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-accent/30 transition">
                <i data-feather="edit" class="w-4 h-4"></i> Compose
            </a>
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
        
        <div class="flex gap-6">
            <!-- Sidebar -->
            <div class="w-48 flex-shrink-0 hidden md:block">
                <div class="member-card rounded-lg p-4 space-y-2">
                    <a href="?filter=inbox" class="flex items-center justify-between px-3 py-2 rounded-lg <?= $filter === 'inbox' ? 'bg-accent/10 text-accent' : 'text-slate-400 hover:text-white hover:bg-slate-800' ?> transition">
                        <span class="flex items-center gap-2">
                            <i data-feather="inbox" class="w-4 h-4"></i> Inbox
                        </span>
                        <?php if ($stats['unread'] > 0): ?>
                        <span class="text-sm bg-accent text-dark-navy px-2 rounded-full"><?= $stats['unread'] ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="?filter=sent" class="flex items-center gap-2 px-3 py-2 rounded-lg <?= $filter === 'sent' ? 'bg-accent/10 text-accent' : 'text-slate-400 hover:text-white hover:bg-slate-800' ?> transition">
                        <i data-feather="send" class="w-4 h-4"></i> Sent
                    </a>
                </div>
            </div>
            
            <!-- Main Area -->
            <div class="flex-1">
                <?php if ($showCompose): ?>
                <!-- Compose Form -->
                <div class="member-card rounded-lg p-6">
                    <h2 class="text-lg font-bold text-white mb-6">New Message</h2>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <input type="hidden" name="form_action" value="send">
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">To</label>
                            <select name="receiver_id" required class="form-input w-full px-4 py-3 rounded-lg text-white">
                                <option value="">Select recipient...</option>
                                <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Subject</label>
                            <input type="text" name="subject" required class="form-input w-full px-4 py-3 rounded-lg text-white">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Message</label>
                            <textarea name="message" rows="6" required class="form-input w-full px-4 py-3 rounded-lg text-white"></textarea>
                        </div>
                        
                        <div class="flex gap-4">
                            <button type="submit" class="bg-accent/20 text-accent px-6 py-3 rounded-lg hover:bg-accent/30 transition flex items-center gap-2">
                                <i data-feather="send" class="w-4 h-4"></i>
                                Send Message
                            </button>
                            <a href="messages.php" class="bg-slate-700 text-white px-6 py-3 rounded-lg hover:bg-slate-600 transition">Cancel</a>
                        </div>
                    </form>
                </div>
                
                <?php elseif ($viewMessage): ?>
                <!-- View Message -->
                <div class="member-card rounded-lg">
                    <div class="p-4 border-b border-slate-800 flex items-center justify-between">
                        <a href="messages.php?filter=<?= $filter ?>" class="text-slate-400 hover:text-white flex items-center gap-2">
                            <i data-feather="arrow-left" class="w-4 h-4"></i> Back
                        </a>
                        <form method="POST" onsubmit="return confirm('Delete this message?')">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <input type="hidden" name="form_action" value="delete">
                            <input type="hidden" name="message_id" value="<?= $viewMessage['id'] ?>">
                            <button type="submit" class="text-red-400 hover:text-red-300 p-2">
                                <i data-feather="trash-2" class="w-4 h-4"></i>
                            </button>
                        </form>
                    </div>
                    
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-white mb-4"><?= htmlspecialchars($viewMessage['subject']) ?></h2>
                        
                        <div class="flex items-center gap-4 mb-6 pb-6 border-b border-slate-800">
                            <?php if ($viewMessage['sender_avatar']): ?>
                            <img src="../uploads/avatars/<?= htmlspecialchars($viewMessage['sender_avatar']) ?>" alt="" class="w-12 h-12 rounded-full object-cover">
                            <?php else: ?>
                            <div class="w-12 h-12 rounded-full bg-accent/10 flex items-center justify-center">
                                <span class="text-accent font-bold text-lg"><?= strtoupper(substr($viewMessage['sender_name'] ?? 'U', 0, 1)) ?></span>
                            </div>
                            <?php endif; ?>
                            <div>
                                <div class="text-white font-medium"><?= htmlspecialchars($viewMessage['sender_name'] ?? 'Unknown') ?></div>
                                <div class="text-slate-400 text-sm"><?= date('M d, Y \a\t g:i A', strtotime($viewMessage['created_at'])) ?></div>
                            </div>
                        </div>
                        
                        <div class="text-slate-300 whitespace-pre-wrap mb-6"><?= nl2br(htmlspecialchars($viewMessage['message'])) ?></div>
                        
                        <?php if (!empty($replies)): ?>
                        <div class="border-t border-slate-800 pt-6">
                            <h3 class="text-lg font-bold text-white mb-4">Replies</h3>
                            <div class="space-y-4">
                                <?php foreach ($replies as $reply): ?>
                                <div class="bg-slate-800/50 rounded-lg p-4">
                                    <div class="flex items-center gap-3 mb-3">
                                        <?php if ($reply['sender_avatar']): ?>
                                        <img src="../uploads/avatars/<?= htmlspecialchars($reply['sender_avatar']) ?>" alt="" class="w-8 h-8 rounded-full object-cover">
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
                        <?php if ($viewMessage['sender_id'] != $userId): ?>
                        <div class="border-t border-slate-800 pt-6 mt-6">
                            <h3 class="text-lg font-bold text-white mb-4">Reply</h3>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="form_action" value="reply">
                                <input type="hidden" name="parent_id" value="<?= $viewMessage['id'] ?>">
                                
                                <textarea name="reply_message" rows="4" required placeholder="Type your reply..." class="form-input w-full px-4 py-3 rounded-lg text-white mb-4"></textarea>
                                
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
                <div class="member-card rounded-lg overflow-hidden">
                    <div class="divide-y divide-slate-800">
                        <?php foreach ($messages as $msg): ?>
                        <a href="?view=<?= $msg['id'] ?>&filter=<?= $filter ?>" class="message-row <?= !$msg['is_read'] && $filter === 'inbox' ? 'unread' : '' ?> flex items-center gap-4 px-4 py-3 block">
                            <?php 
                            $avatar = $filter === 'sent' ? $msg['receiver_avatar'] : $msg['sender_avatar'];
                            $name = $filter === 'sent' ? $msg['receiver_name'] : $msg['sender_name'];
                            ?>
                            <?php if ($avatar): ?>
                            <img src="../uploads/avatars/<?= htmlspecialchars($avatar) ?>" alt="" class="w-10 h-10 rounded-full object-cover flex-shrink-0">
                            <?php else: ?>
                            <div class="w-10 h-10 rounded-full bg-accent/10 flex items-center justify-center flex-shrink-0">
                                <span class="text-accent font-bold"><?= strtoupper(substr($name ?? 'U', 0, 1)) ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-white font-medium <?= !$msg['is_read'] && $filter === 'inbox' ? 'font-bold' : '' ?>">
                                        <?= htmlspecialchars($name ?? 'Unknown') ?>
                                    </span>
                                </div>
                                <div class="text-slate-300 truncate <?= !$msg['is_read'] && $filter === 'inbox' ? 'font-medium' : '' ?>">
                                    <?= htmlspecialchars($msg['subject']) ?>
                                </div>
                                <div class="text-slate-400 text-sm truncate">
                                    <?= htmlspecialchars(substr($msg['message'], 0, 60)) ?>...
                                </div>
                            </div>
                            
                            <div class="text-slate-400 text-sm flex-shrink-0">
                                <?= date('M d', strtotime($msg['created_at'])) ?>
                            </div>
                        </a>
                        <?php endforeach; ?>
                        
                        <?php if (empty($messages)): ?>
                        <div class="px-4 py-12 text-center text-slate-400">
                            <i data-feather="inbox" class="w-12 h-12 mx-auto mb-4 opacity-50"></i>
                            <p>No messages</p>
                            <a href="?compose" class="inline-block mt-3 text-accent hover:underline">Compose a new message</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <script>feather.replace();</script>
</body>
</html>
