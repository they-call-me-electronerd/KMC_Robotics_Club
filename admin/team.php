<?php
/**
 * KMC Robotics Club - Admin Team Management
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
                $memberData = [
                    'name' => Security::sanitize($_POST['name']),
                    'role' => Security::sanitize($_POST['position']),
                    'category' => $_POST['category'],
                    'bio' => Security::sanitize($_POST['bio'] ?? ''),
                    'email' => filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ?: null,
                    'linkedin' => filter_var($_POST['linkedin_url'] ?? '', FILTER_VALIDATE_URL) ?: null,
                    'github' => filter_var($_POST['github_url'] ?? '', FILTER_VALIDATE_URL) ?: null,
                    'position_order' => !empty($_POST['display_order']) ? (int)$_POST['display_order'] : 0,
                    'is_active' => isset($_POST['is_active']) ? 1 : 0
                ];
                
                // Link to user account if email matches
                if ($memberData['email']) {
                    $linkedUser = $db->fetchOne("SELECT id FROM users WHERE email = :email", ['email' => $memberData['email']]);
                    if ($linkedUser) {
                        $memberData['user_id'] = $linkedUser['id'];
                    }
                }
                
                // Handle image upload
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $uploadErrors = Security::validateFileUpload($_FILES['image']);
                    if (empty($uploadErrors)) {
                        $filename = Security::generateSafeFilename($_FILES['image']['name']);
                        $targetPath = UPLOAD_TEAM . '/' . $filename;
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                            // Delete old image if updating
                            if ($action === 'update' && !empty($_POST['id'])) {
                                $oldMember = $db->fetchOne("SELECT photo_path FROM team_members WHERE id = :id", ['id' => $_POST['id']]);
                                if ($oldMember && $oldMember['photo_path'] && file_exists(UPLOAD_TEAM . '/' . $oldMember['photo_path'])) {
                                    unlink(UPLOAD_TEAM . '/' . $oldMember['photo_path']);
                                }
                            }
                            $memberData['photo_path'] = $filename;
                        } else {
                            $error = 'Failed to move uploaded file. Check directory permissions.';
                        }
                    } else {
                        $error = 'Upload error: ' . implode(', ', $uploadErrors);
                    }
                } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                    // Handle other upload errors
                    $uploadErrors = Security::validateFileUpload($_FILES['image']);
                    $error = 'Upload error: ' . implode(', ', $uploadErrors);
                }
                
                if ($action === 'create') {
                    $db->insert('team_members', $memberData);
                    $message = 'Team member added successfully';
                } else {
                    $db->update('team_members', $memberData, 'id = :id', ['id' => $_POST['id']]);
                    $message = 'Team member updated successfully';
                }
                break;
                
            case 'delete':
                $member = $db->fetchOne("SELECT photo_path FROM team_members WHERE id = :id", ['id' => $_POST['id']]);
                if ($member) {
                    if ($member['photo_path'] && file_exists(UPLOAD_TEAM . '/' . $member['photo_path'])) {
                        unlink(UPLOAD_TEAM . '/' . $member['photo_path']);
                    }
                    $db->delete('team_members', 'id = :id', ['id' => $_POST['id']]);
                    $message = 'Team member removed successfully';
                }
                break;
                
            case 'toggle-status':
                $member = $db->fetchOne("SELECT is_active FROM team_members WHERE id = :id", ['id' => $_POST['id']]);
                if ($member) {
                    $db->update('team_members', ['is_active' => !$member['is_active']], 'id = :id', ['id' => $_POST['id']]);
                    $message = 'Status updated successfully';
                }
                break;
        }
    }
}

// Get team members
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

$where = '1=1';
$params = [];

if (!empty($_GET['category'])) {
    $where .= ' AND category = :category';
    $params['category'] = $_GET['category'];
}

if (isset($_GET['active'])) {
    $where .= ' AND is_active = :active';
    $params['active'] = $_GET['active'];
}

$total = $db->count('team_members', $where, $params);
$members = $db->fetchAll(
    "SELECT t.*, u.name as linked_user_name
     FROM team_members t
     LEFT JOIN users u ON t.user_id = u.id
     WHERE {$where}
     ORDER BY t.position_order ASC, t.created_at DESC
     LIMIT {$limit} OFFSET {$offset}",
    $params
);

// Get member for editing
$editMember = null;
if (isset($_GET['edit'])) {
    $editMember = $db->fetchOne("SELECT * FROM team_members WHERE id = :id", ['id' => $_GET['edit']]);
}

// Categories
$categories = ['executive', 'advisor', 'technical', 'creative', 'management', 'alumni'];

$csrfToken = Security::generateCSRFToken();
$showForm = isset($_GET['action']) && $_GET['action'] === 'create' || $editMember;
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="theme-color" content="#050a14" />
    <link rel="icon" type="image/png" href="../assets/images/kmc-rc-logo.png">
    <title>Manage Team | Admin - KMC RC</title>
    
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
        .team-card { transition: all 0.3s ease; }
        .team-card:hover { transform: translateY(-5px); border-color: rgba(0, 242, 255, 0.3); }
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
            <a href="team.php" class="nav-link-admin active flex items-center gap-3 px-6 py-3 text-white">
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
                    <h1 class="text-xl font-bold text-white font-orbitron">Manage Team</h1>
                    <p class="text-sm text-slate-400">Add, edit, and manage team members</p>
                </div>
                <a href="?action=create" class="bg-accent/20 text-accent px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-accent/30 transition">
                    <i data-feather="plus" class="w-4 h-4"></i> Add Member
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
            <!-- Team Member Form -->
            <div class="stat-card-admin rounded-lg p-6 mb-6">
                <h2 class="text-lg font-bold text-white mb-6"><?= $editMember ? 'Edit Team Member' : 'Add New Team Member' ?></h2>
                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="form_action" value="<?= $editMember ? 'update' : 'create' ?>">
                    <?php if ($editMember): ?>
                    <input type="hidden" name="id" value="<?= $editMember['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Full Name *</label>
                            <input type="text" name="name" required class="form-input w-full px-4 py-3 rounded-lg text-white"
                                   value="<?= htmlspecialchars($editMember['name'] ?? '') ?>">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Position/Role *</label>
                            <input type="text" name="position" required class="form-input w-full px-4 py-3 rounded-lg text-white"
                                   value="<?= htmlspecialchars($editMember['role'] ?? '') ?>" placeholder="e.g., President, Technical Lead">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Category</label>
                            <select name="category" class="form-input w-full px-4 py-3 rounded-lg text-white">
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat ?>" <?= ($editMember['category'] ?? '') === $cat ? 'selected' : '' ?>><?= ucfirst($cat) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Email</label>
                            <input type="email" name="email" class="form-input w-full px-4 py-3 rounded-lg text-white"
                                   value="<?= htmlspecialchars($editMember['email'] ?? '') ?>">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Display Order</label>
                            <input type="number" name="display_order" class="form-input w-full px-4 py-3 rounded-lg text-white"
                                   value="<?= $editMember['position_order'] ?? 0 ?>" placeholder="Lower number = higher priority">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">LinkedIn URL</label>
                            <input type="url" name="linkedin_url" class="form-input w-full px-4 py-3 rounded-lg text-white"
                                   value="<?= htmlspecialchars($editMember['linkedin'] ?? '') ?>">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">GitHub URL</label>
                            <input type="url" name="github_url" class="form-input w-full px-4 py-3 rounded-lg text-white"
                                   value="<?= htmlspecialchars($editMember['github'] ?? '') ?>">
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-300 mb-2">Bio</label>
                            <textarea name="bio" rows="3" class="form-input w-full px-4 py-3 rounded-lg text-white"><?= htmlspecialchars($editMember['bio'] ?? '') ?></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Profile Image</label>
                            <input type="file" name="image" accept="image/*" class="form-input w-full px-4 py-3 rounded-lg text-white">
                            <?php if ($editMember && $editMember['photo_path']): ?>
                            <img src="../uploads/team/<?= htmlspecialchars($editMember['photo_path']) ?>" alt="Current image" class="mt-2 h-20 rounded">
                            <?php endif; ?>
                        </div>
                        
                        <div class="flex items-center gap-4 pt-8">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_active" class="w-4 h-4 rounded" <?= ($editMember['is_active'] ?? 1) ? 'checked' : '' ?>>
                                <span class="text-slate-300">Active (visible on website)</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="flex gap-4 pt-4">
                        <button type="submit" class="bg-accent/20 text-accent px-6 py-3 rounded-lg hover:bg-accent/30 transition flex items-center gap-2">
                            <i data-feather="save" class="w-4 h-4"></i>
                            <?= $editMember ? 'Update Member' : 'Add Member' ?>
                        </button>
                        <a href="team.php" class="bg-slate-700 text-white px-6 py-3 rounded-lg hover:bg-slate-600 transition">Cancel</a>
                    </div>
                </form>
            </div>
            <?php endif; ?>
            
            <!-- Category Filter -->
            <div class="flex flex-wrap gap-2 mb-6">
                <a href="team.php" class="px-4 py-2 rounded-lg <?= empty($_GET['category']) ? 'bg-accent text-dark-navy' : 'bg-slate-800 text-white hover:bg-slate-700' ?> transition">
                    All
                </a>
                <?php foreach ($categories as $cat): ?>
                <a href="?category=<?= $cat ?>" class="px-4 py-2 rounded-lg <?= ($_GET['category'] ?? '') === $cat ? 'bg-accent text-dark-navy' : 'bg-slate-800 text-white hover:bg-slate-700' ?> transition">
                    <?= ucfirst($cat) ?>
                </a>
                <?php endforeach; ?>
            </div>
            
            <!-- Team Members Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach ($members as $member): ?>
                <div class="team-card stat-card-admin rounded-lg overflow-hidden">
                    <div class="aspect-square relative">
                        <?php if ($member['photo_path']): ?>
                        <img src="../uploads/team/<?= htmlspecialchars($member['photo_path']) ?>" alt="<?= htmlspecialchars($member['name']) ?>" 
                             class="w-full h-full object-cover">
                        <?php else: ?>
                        <div class="w-full h-full bg-gradient-to-br from-accent/20 to-secondary-accent/20 flex items-center justify-center">
                            <i data-feather="user" class="w-20 h-20 text-accent/50"></i>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!$member['is_active']): ?>
                        <div class="absolute top-2 left-2 bg-red-500/80 text-white text-xs px-2 py-1 rounded">Inactive</div>
                        <?php endif; ?>
                        
                        <div class="absolute top-2 right-2 flex gap-1">
                            <a href="?edit=<?= $member['id'] ?>" class="bg-dark-navy/80 text-accent p-2 rounded hover:bg-dark-navy transition">
                                <i data-feather="edit-2" class="w-4 h-4"></i>
                            </a>
                            <form method="POST" class="inline">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="form_action" value="toggle-status">
                                <input type="hidden" name="id" value="<?= $member['id'] ?>">
                                <button type="submit" class="bg-dark-navy/80 text-<?= $member['is_active'] ? 'yellow' : 'green' ?>-400 p-2 rounded hover:bg-dark-navy transition" title="<?= $member['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                    <i data-feather="<?= $member['is_active'] ? 'eye-off' : 'eye' ?>" class="w-4 h-4"></i>
                                </button>
                            </form>
                            <form method="POST" class="inline" onsubmit="return confirm('Remove this team member?')">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="form_action" value="delete">
                                <input type="hidden" name="id" value="<?= $member['id'] ?>">
                                <button type="submit" class="bg-dark-navy/80 text-red-400 p-2 rounded hover:bg-dark-navy transition">
                                    <i data-feather="trash-2" class="w-4 h-4"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="p-4">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <h3 class="text-white font-bold"><?= htmlspecialchars($member['name']) ?></h3>
                                <p class="text-accent text-sm"><?= htmlspecialchars($member['role']) ?></p>
                            </div>
                            <span class="px-2 py-1 rounded text-xs bg-purple-500/20 text-purple-400"><?= ucfirst($member['category']) ?></span>
                        </div>
                        
                        <?php if ($member['bio']): ?>
                        <p class="text-slate-400 text-sm mt-2 line-clamp-2"><?= htmlspecialchars($member['bio']) ?></p>
                        <?php endif; ?>
                        
                        <div class="flex items-center gap-3 mt-4 pt-4 border-t border-slate-800">
                            <?php if ($member['email']): ?>
                            <a href="mailto:<?= htmlspecialchars($member['email']) ?>" class="text-slate-400 hover:text-accent">
                                <i data-feather="mail" class="w-4 h-4"></i>
                            </a>
                            <?php endif; ?>
                            <?php if ($member['linkedin']): ?>
                            <a href="<?= htmlspecialchars($member['linkedin']) ?>" target="_blank" class="text-slate-400 hover:text-accent">
                                <i data-feather="linkedin" class="w-4 h-4"></i>
                            </a>
                            <?php endif; ?>
                            <?php if ($member['github']): ?>
                            <a href="<?= htmlspecialchars($member['github']) ?>" target="_blank" class="text-slate-400 hover:text-accent">
                                <i data-feather="github" class="w-4 h-4"></i>
                            </a>
                            <?php endif; ?>
                            <span class="ml-auto text-xs text-slate-500">Order: <?= $member['position_order'] ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($members)): ?>
                <div class="col-span-full text-center py-12 text-slate-400">
                    <i data-feather="users" class="w-16 h-16 mx-auto mb-4 opacity-50"></i>
                    <p>No team members found</p>
                    <a href="?action=create" class="inline-block mt-4 text-accent hover:underline">Add your first team member</a>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($total > $limit): ?>
            <div class="mt-8 flex justify-center gap-2">
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
    </main>
    
    <script>feather.replace();</script>
</body>
</html>
