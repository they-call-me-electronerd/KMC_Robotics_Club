<?php
/**
 * KMC Robotics Club - Member Profile
 */

require_once __DIR__ . '/../includes/Security.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';

Security::requireAuth();

$db = Database::getInstance();
$auth = new Auth();
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
            case 'update-profile':
                $result = $auth->updateProfile($userId, [
                    'name' => $_POST['name'],
                    'phone' => $_POST['phone'],
                    'bio' => $_POST['bio']
                ]);
                
                if ($result['success']) {
                    $message = 'Profile updated successfully';
                    $user = $db->fetchOne("SELECT * FROM users WHERE id = :id", ['id' => $userId]);
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'change-password':
                $result = $auth->changePassword($userId, $_POST['current_password'], $_POST['new_password']);
                
                if ($result['success']) {
                    $message = 'Password changed successfully';
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'update-avatar':
                if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                    $errors = Security::validateFileUpload($_FILES['avatar'], ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], 2 * 1024 * 1024);
                    
                    if (empty($errors)) {
                        $filename = Security::generateSafeFilename($_FILES['avatar']['name']);
                        $uploadPath = UPLOAD_DIR . '/avatars/' . $filename;
                        
                        if (!is_dir(UPLOAD_DIR . '/avatars')) {
                            mkdir(UPLOAD_DIR . '/avatars', 0755, true);
                        }
                        
                        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadPath)) {
                            // Delete old avatar
                            if ($user['avatar'] && file_exists(UPLOAD_DIR . '/avatars/' . $user['avatar'])) {
                                unlink(UPLOAD_DIR . '/avatars/' . $user['avatar']);
                            }
                            
                            $db->update('users', ['avatar' => $filename], 'id = :id', ['id' => $userId]);
                            $message = 'Avatar updated successfully';
                            $user['avatar'] = $filename;
                        } else {
                            $error = 'Failed to upload avatar';
                        }
                    } else {
                        $error = implode(', ', $errors);
                    }
                }
                break;
        }
    }
}

// Get activity stats
$activityStats = [
    'events_registered' => $db->fetchOne(
        "SELECT COUNT(*) as c FROM event_registrations WHERE user_id = :id",
        ['id' => $userId]
    )['c'],
    'events_attended' => $db->fetchOne(
        "SELECT COUNT(*) as c FROM event_registrations WHERE user_id = :id AND status = 'attended'",
        ['id' => $userId]
    )['c'],
    'gallery_uploads' => $db->fetchOne(
        "SELECT COUNT(*) as c FROM gallery WHERE uploaded_by = :id",
        ['id' => $userId]
    )['c']
];

$csrfToken = Security::generateCSRFToken();
$activeTab = $_GET['tab'] ?? 'profile';
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="theme-color" content="#050a14" />
    <link rel="icon" type="image/png" href="../assets/images/kmc-rc-logo.png">
    <title>Profile | KMC Robotics Club</title>
    
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
        .tab-btn.active { background: rgba(0, 242, 255, 0.1); color: #00f2ff; }
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
                        <a href="profile.php" class="px-3 py-2 rounded-lg text-accent bg-accent/10">Profile</a>
                        <a href="events.php" class="px-3 py-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition">Events</a>
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
    <main class="pt-20 pb-12 px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold text-white font-orbitron mb-6">My Profile</h1>
        
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
        
        <!-- Profile Header -->
        <div class="member-card rounded-lg p-6 mb-6">
            <div class="flex flex-col sm:flex-row items-center gap-6">
                <div class="relative">
                    <?php if ($user['avatar']): ?>
                    <img src="../uploads/avatars/<?= htmlspecialchars($user['avatar']) ?>" alt="" class="w-24 h-24 rounded-full object-cover">
                    <?php else: ?>
                    <div class="w-24 h-24 rounded-full bg-accent/10 flex items-center justify-center">
                        <span class="text-accent font-bold text-3xl"><?= strtoupper(substr($user['name'], 0, 1)) ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data" class="absolute -bottom-2 -right-2">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <input type="hidden" name="form_action" value="update-avatar">
                        <label class="w-8 h-8 rounded-full bg-accent text-dark-navy flex items-center justify-center cursor-pointer hover:bg-accent/80 transition">
                            <i data-feather="camera" class="w-4 h-4"></i>
                            <input type="file" name="avatar" accept="image/*" class="hidden" onchange="this.form.submit()">
                        </label>
                    </form>
                </div>
                
                <div class="flex-1 text-center sm:text-left">
                    <h2 class="text-xl font-bold text-white"><?= htmlspecialchars($user['name']) ?></h2>
                    <p class="text-slate-400"><?= htmlspecialchars($user['email']) ?></p>
                    <div class="flex items-center gap-4 mt-3 justify-center sm:justify-start">
                        <span class="px-3 py-1 rounded-full text-xs bg-accent/20 text-accent">
                            <?= ucfirst($user['role']) ?>
                        </span>
                        <span class="text-slate-400 text-sm">
                            Member since <?= date('M Y', strtotime($user['created_at'])) ?>
                        </span>
                    </div>
                </div>
                
                <div class="flex gap-6 text-center">
                    <div>
                        <div class="text-2xl font-bold text-white"><?= $activityStats['events_registered'] ?></div>
                        <div class="text-slate-400 text-xs">Events</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-white"><?= $activityStats['events_attended'] ?></div>
                        <div class="text-slate-400 text-xs">Attended</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-white"><?= $activityStats['gallery_uploads'] ?></div>
                        <div class="text-slate-400 text-xs">Uploads</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tabs -->
        <div class="flex gap-2 mb-6">
            <a href="?tab=profile" class="tab-btn px-4 py-2 rounded-lg <?= $activeTab === 'profile' ? 'active' : 'text-slate-400 hover:text-white hover:bg-slate-800' ?> transition">
                <i data-feather="user" class="w-4 h-4 inline mr-2"></i> Profile Info
            </a>
            <a href="?tab=password" class="tab-btn px-4 py-2 rounded-lg <?= $activeTab === 'password' ? 'active' : 'text-slate-400 hover:text-white hover:bg-slate-800' ?> transition">
                <i data-feather="lock" class="w-4 h-4 inline mr-2"></i> Password
            </a>
        </div>
        
        <!-- Tab Content -->
        <div class="member-card rounded-lg p-6">
            <?php if ($activeTab === 'profile'): ?>
            <!-- Profile Form -->
            <h3 class="text-lg font-bold text-white mb-6">Edit Profile</h3>
            <form method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="form_action" value="update-profile">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Full Name *</label>
                        <input type="text" name="name" required class="form-input w-full px-4 py-3 rounded-lg text-white"
                               value="<?= htmlspecialchars($user['name']) ?>">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Email</label>
                        <input type="email" disabled class="form-input w-full px-4 py-3 rounded-lg text-slate-400 cursor-not-allowed"
                               value="<?= htmlspecialchars($user['email']) ?>">
                        <p class="text-slate-500 text-xs mt-1">Email cannot be changed</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Phone</label>
                        <input type="tel" name="phone" class="form-input w-full px-4 py-3 rounded-lg text-white"
                               value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Bio</label>
                    <textarea name="bio" rows="4" class="form-input w-full px-4 py-3 rounded-lg text-white"
                              placeholder="Tell us about yourself..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                </div>
                
                <div class="pt-4">
                    <button type="submit" class="bg-accent/20 text-accent px-6 py-3 rounded-lg hover:bg-accent/30 transition flex items-center gap-2">
                        <i data-feather="save" class="w-4 h-4"></i>
                        Save Changes
                    </button>
                </div>
            </form>
            
            <?php elseif ($activeTab === 'password'): ?>
            <!-- Password Form -->
            <h3 class="text-lg font-bold text-white mb-6">Change Password</h3>
            <form method="POST" class="space-y-6 max-w-md">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="form_action" value="change-password">
                
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Current Password *</label>
                    <input type="password" name="current_password" required class="form-input w-full px-4 py-3 rounded-lg text-white">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">New Password *</label>
                    <input type="password" name="new_password" required minlength="8" class="form-input w-full px-4 py-3 rounded-lg text-white">
                    <p class="text-slate-500 text-xs mt-1">Minimum 8 characters</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Confirm New Password *</label>
                    <input type="password" name="confirm_password" required class="form-input w-full px-4 py-3 rounded-lg text-white">
                </div>
                
                <div class="pt-4">
                    <button type="submit" class="bg-accent/20 text-accent px-6 py-3 rounded-lg hover:bg-accent/30 transition flex items-center gap-2">
                        <i data-feather="lock" class="w-4 h-4"></i>
                        Change Password
                    </button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </main>
    
    <script>feather.replace();</script>
</body>
</html>
