<?php
/**
 * KMC Robotics Club - Admin Users Management
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
            case 'update':
                $userData = [
                    'name' => Security::sanitize($_POST['name']),
                    'email' => filter_var($_POST['email'], FILTER_VALIDATE_EMAIL),
                    'phone' => Security::sanitize($_POST['phone'] ?? ''),
                    'role' => $_POST['role'],
                    'status' => $_POST['status']
                ];
                
                if (!$userData['email']) {
                    $error = 'Invalid email address';
                    break;
                }
                
                // Check email uniqueness
                $existingUser = $db->fetchOne(
                    "SELECT id FROM users WHERE email = :email AND id != :id",
                    ['email' => $userData['email'], 'id' => $_POST['id']]
                );
                
                if ($existingUser) {
                    $error = 'Email address already exists';
                    break;
                }
                
                $db->update('users', $userData, 'id = :id', ['id' => $_POST['id']]);
                $message = 'User updated successfully';
                break;
                
            case 'change-role':
                $db->update('users', ['role' => $_POST['role']], 'id = :id', ['id' => $_POST['id']]);
                $message = 'Role updated successfully';
                break;
                
            case 'change-status':
                $db->update('users', ['status' => $_POST['status']], 'id = :id', ['id' => $_POST['id']]);
                $message = 'Status updated successfully';
                break;
                
            case 'delete':
                // Don't allow deleting yourself
                if ($_POST['id'] == Security::getCurrentUserId()) {
                    $error = 'You cannot delete your own account';
                    break;
                }
                
                $user = $db->fetchOne("SELECT avatar FROM users WHERE id = :id", ['id' => $_POST['id']]);
                if ($user && $user['avatar'] && file_exists('../uploads/avatars/' . $user['avatar'])) {
                    unlink('../uploads/avatars/' . $user['avatar']);
                }
                $db->delete('users', 'id = :id', ['id' => $_POST['id']]);
                $message = 'User deleted successfully';
                break;
        }
    }
}

// Get users
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$where = '1=1';
$params = [];

if (!empty($_GET['role'])) {
    $where .= ' AND role = :role';
    $params['role'] = $_GET['role'];
}

if (!empty($_GET['status'])) {
    $where .= ' AND status = :status';
    $params['status'] = $_GET['status'];
}

if (!empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $where .= ' AND (name LIKE :search OR email LIKE :search)';
    $params['search'] = $search;
}

$total = $db->count('users', $where, $params);
$users = $db->fetchAll(
    "SELECT * FROM users WHERE {$where} ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}",
    $params
);

// Get user for editing
$editUser = null;
if (isset($_GET['edit'])) {
    $editUser = $db->fetchOne("SELECT * FROM users WHERE id = :id", ['id' => $_GET['edit']]);
}

// Stats
$stats = [
    'total' => $db->count('users'),
    'active' => $db->count('users', "status = 'active'"),
    'pending' => $db->count('users', "status = 'pending'"),
    'admins' => $db->count('users', "role = 'admin'")
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
    <title>Manage Members | Admin - KMC RC</title>
    
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
            <a href="users.php" class="nav-link-admin active flex items-center gap-3 px-6 py-3 text-white">
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
                    <h1 class="text-xl font-bold text-white font-orbitron">Manage Members</h1>
                    <p class="text-sm text-slate-400">View and manage club members</p>
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
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="stat-card-admin rounded-lg p-4">
                    <div class="text-slate-400 text-sm">Total Members</div>
                    <div class="text-2xl font-bold text-white"><?= number_format($stats['total']) ?></div>
                </div>
                <div class="stat-card-admin rounded-lg p-4">
                    <div class="text-slate-400 text-sm">Active</div>
                    <div class="text-2xl font-bold text-green-400"><?= number_format($stats['active']) ?></div>
                </div>
                <div class="stat-card-admin rounded-lg p-4">
                    <div class="text-slate-400 text-sm">Pending</div>
                    <div class="text-2xl font-bold text-yellow-400"><?= number_format($stats['pending']) ?></div>
                </div>
                <div class="stat-card-admin rounded-lg p-4">
                    <div class="text-slate-400 text-sm">Admins</div>
                    <div class="text-2xl font-bold text-accent"><?= number_format($stats['admins']) ?></div>
                </div>
            </div>
            
            <?php if ($editUser): ?>
            <!-- Edit User Form -->
            <div class="stat-card-admin rounded-lg p-6 mb-6">
                <h2 class="text-lg font-bold text-white mb-6">Edit Member</h2>
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="form_action" value="update">
                    <input type="hidden" name="id" value="<?= $editUser['id'] ?>">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Full Name *</label>
                            <input type="text" name="name" required class="form-input w-full px-4 py-3 rounded-lg text-white"
                                   value="<?= htmlspecialchars($editUser['name']) ?>">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Email *</label>
                            <input type="email" name="email" required class="form-input w-full px-4 py-3 rounded-lg text-white"
                                   value="<?= htmlspecialchars($editUser['email']) ?>">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Phone</label>
                            <input type="tel" name="phone" class="form-input w-full px-4 py-3 rounded-lg text-white"
                                   value="<?= htmlspecialchars($editUser['phone'] ?? '') ?>">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Role</label>
                            <select name="role" class="form-input w-full px-4 py-3 rounded-lg text-white">
                                <option value="member" <?= $editUser['role'] === 'member' ? 'selected' : '' ?>>Member</option>
                                <option value="moderator" <?= $editUser['role'] === 'moderator' ? 'selected' : '' ?>>Moderator</option>
                                <option value="admin" <?= $editUser['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Status</label>
                            <select name="status" class="form-input w-full px-4 py-3 rounded-lg text-white">
                                <option value="pending" <?= $editUser['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="active" <?= $editUser['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="suspended" <?= $editUser['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="flex gap-4 pt-4">
                        <button type="submit" class="bg-accent/20 text-accent px-6 py-3 rounded-lg hover:bg-accent/30 transition flex items-center gap-2">
                            <i data-feather="save" class="w-4 h-4"></i>
                            Update Member
                        </button>
                        <a href="users.php" class="bg-slate-700 text-white px-6 py-3 rounded-lg hover:bg-slate-600 transition">Cancel</a>
                    </div>
                </form>
            </div>
            <?php endif; ?>
            
            <!-- Filters -->
            <div class="stat-card-admin rounded-lg p-4 mb-6">
                <form method="GET" class="flex flex-wrap gap-4 items-center">
                    <div class="flex-1 min-w-[200px]">
                        <input type="text" name="search" placeholder="Search by name or email..." 
                               class="form-input w-full px-4 py-2 rounded-lg text-white text-sm"
                               value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    </div>
                    <select name="role" class="form-input px-3 py-2 rounded text-sm text-white">
                        <option value="">All Roles</option>
                        <option value="member" <?= ($_GET['role'] ?? '') === 'member' ? 'selected' : '' ?>>Member</option>
                        <option value="moderator" <?= ($_GET['role'] ?? '') === 'moderator' ? 'selected' : '' ?>>Moderator</option>
                        <option value="admin" <?= ($_GET['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                    <select name="status" class="form-input px-3 py-2 rounded text-sm text-white">
                        <option value="">All Status</option>
                        <option value="pending" <?= ($_GET['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="active" <?= ($_GET['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="suspended" <?= ($_GET['status'] ?? '') === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                    </select>
                    <button type="submit" class="bg-accent/20 text-accent px-4 py-2 rounded-lg hover:bg-accent/30 transition">
                        <i data-feather="search" class="w-4 h-4"></i>
                    </button>
                    <a href="users.php" class="text-slate-400 hover:text-white px-3 py-2">Reset</a>
                </form>
            </div>
            
            <!-- Users List -->
            <div class="stat-card-admin rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-800/50">
                            <tr>
                                <th class="text-left text-slate-400 text-sm font-medium px-4 py-3">Member</th>
                                <th class="text-left text-slate-400 text-sm font-medium px-4 py-3">Email</th>
                                <th class="text-left text-slate-400 text-sm font-medium px-4 py-3">Role</th>
                                <th class="text-left text-slate-400 text-sm font-medium px-4 py-3">Status</th>
                                <th class="text-left text-slate-400 text-sm font-medium px-4 py-3">Joined</th>
                                <th class="text-left text-slate-400 text-sm font-medium px-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr class="border-b border-slate-800 hover:bg-slate-800/30">
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-3">
                                        <?php if ($user['avatar']): ?>
                                        <img src="../uploads/avatars/<?= htmlspecialchars($user['avatar']) ?>" alt="" class="w-10 h-10 rounded-full object-cover">
                                        <?php else: ?>
                                        <div class="w-10 h-10 rounded-full bg-accent/10 flex items-center justify-center">
                                            <span class="text-accent font-bold"><?= strtoupper(substr($user['name'], 0, 1)) ?></span>
                                        </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="text-white font-medium"><?= htmlspecialchars($user['name']) ?></div>
                                            <div class="text-slate-400 text-sm"><?= htmlspecialchars($user['phone'] ?? 'No phone') ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-slate-300"><?= htmlspecialchars($user['email']) ?></td>
                                <td class="px-4 py-4">
                                    <span class="px-2 py-1 rounded text-xs <?php
                                        echo match($user['role']) {
                                            'admin' => 'bg-red-500/20 text-red-400',
                                            'moderator' => 'bg-purple-500/20 text-purple-400',
                                            default => 'bg-slate-500/20 text-slate-400'
                                        };
                                    ?>"><?= ucfirst($user['role']) ?></span>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="px-2 py-1 rounded text-xs <?php
                                        echo match($user['status']) {
                                            'active' => 'bg-green-500/20 text-green-400',
                                            'pending' => 'bg-yellow-500/20 text-yellow-400',
                                            'suspended' => 'bg-red-500/20 text-red-400',
                                            default => 'bg-slate-500/20 text-slate-400'
                                        };
                                    ?>"><?= ucfirst($user['status']) ?></span>
                                </td>
                                <td class="px-4 py-4 text-slate-400 text-sm"><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-2">
                                        <a href="?edit=<?= $user['id'] ?>" class="text-accent hover:text-accent/80 p-1" title="Edit">
                                            <i data-feather="edit-2" class="w-4 h-4"></i>
                                        </a>
                                        
                                        <?php if ($user['status'] === 'pending'): ?>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="form_action" value="change-status">
                                            <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                            <input type="hidden" name="status" value="active">
                                            <button type="submit" class="text-green-400 hover:text-green-300 p-1" title="Approve">
                                                <i data-feather="check" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        
                                        <?php if ($user['id'] != Security::getCurrentUserId()): ?>
                                        <form method="POST" class="inline" onsubmit="return confirm('Delete this member?')">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="form_action" value="delete">
                                            <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                            <button type="submit" class="text-red-400 hover:text-red-300 p-1" title="Delete">
                                                <i data-feather="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-slate-400">No members found</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total > $limit): ?>
                <div class="p-4 border-t border-slate-800 flex justify-center gap-2">
                    <?php 
                    $totalPages = ceil($total / $limit);
                    $queryParams = $_GET;
                    for ($i = 1; $i <= $totalPages; $i++): 
                        $queryParams['page'] = $i;
                    ?>
                    <a href="?<?= http_build_query($queryParams) ?>" class="px-3 py-1 rounded <?= $i === $page ? 'bg-accent text-dark-navy' : 'bg-slate-800 text-white hover:bg-slate-700' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <script>feather.replace();</script>
</body>
</html>
