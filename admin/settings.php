<?php
/**
 * KMC Robotics Club - Admin Settings
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
            case 'general':
                $settings = [
                    'site_name' => Security::sanitize($_POST['site_name']),
                    'site_description' => Security::sanitize($_POST['site_description']),
                    'contact_email' => filter_var($_POST['contact_email'], FILTER_VALIDATE_EMAIL),
                    'contact_phone' => Security::sanitize($_POST['contact_phone']),
                    'contact_address' => Security::sanitize($_POST['contact_address']),
                    'facebook_url' => filter_var($_POST['facebook_url'] ?? '', FILTER_VALIDATE_URL) ?: '',
                    'instagram_url' => filter_var($_POST['instagram_url'] ?? '', FILTER_VALIDATE_URL) ?: '',
                    'linkedin_url' => filter_var($_POST['linkedin_url'] ?? '', FILTER_VALIDATE_URL) ?: '',
                    'github_url' => filter_var($_POST['github_url'] ?? '', FILTER_VALIDATE_URL) ?: '',
                ];
                
                foreach ($settings as $key => $value) {
                    $exists = $db->fetchOne("SELECT id FROM settings WHERE setting_key = :key", ['key' => $key]);
                    if ($exists) {
                        $db->update('settings', ['setting_value' => $value], "setting_key = :key", ['key' => $key]);
                    } else {
                        $db->insert('settings', ['setting_key' => $key, 'setting_value' => $value, 'setting_group' => 'general']);
                    }
                }
                $message = 'General settings saved successfully';
                break;
                
            case 'registration':
                $settings = [
                    'registration_enabled' => isset($_POST['registration_enabled']) ? '1' : '0',
                    'email_verification' => isset($_POST['email_verification']) ? '1' : '0',
                    'admin_approval' => isset($_POST['admin_approval']) ? '1' : '0',
                    'default_role' => $_POST['default_role'],
                ];
                
                foreach ($settings as $key => $value) {
                    $exists = $db->fetchOne("SELECT id FROM settings WHERE setting_key = :key", ['key' => $key]);
                    if ($exists) {
                        $db->update('settings', ['setting_value' => $value], "setting_key = :key", ['key' => $key]);
                    } else {
                        $db->insert('settings', ['setting_key' => $key, 'setting_value' => $value, 'setting_group' => 'registration']);
                    }
                }
                $message = 'Registration settings saved successfully';
                break;
                
            case 'email':
                $settings = [
                    'smtp_host' => Security::sanitize($_POST['smtp_host']),
                    'smtp_port' => (int)$_POST['smtp_port'],
                    'smtp_username' => Security::sanitize($_POST['smtp_username']),
                    'smtp_encryption' => $_POST['smtp_encryption'],
                    'email_from_name' => Security::sanitize($_POST['email_from_name']),
                    'email_from_address' => filter_var($_POST['email_from_address'], FILTER_VALIDATE_EMAIL),
                ];
                
                // Only update password if provided
                if (!empty($_POST['smtp_password'])) {
                    $settings['smtp_password'] = $_POST['smtp_password'];
                }
                
                foreach ($settings as $key => $value) {
                    $exists = $db->fetchOne("SELECT id FROM settings WHERE setting_key = :key", ['key' => $key]);
                    if ($exists) {
                        $db->update('settings', ['setting_value' => $value], "setting_key = :key", ['key' => $key]);
                    } else {
                        $db->insert('settings', ['setting_key' => $key, 'setting_value' => $value, 'setting_group' => 'email']);
                    }
                }
                $message = 'Email settings saved successfully';
                break;
                
            case 'maintenance':
                $maintenanceMode = isset($_POST['maintenance_mode']) ? '1' : '0';
                $maintenanceMessage = Security::sanitize($_POST['maintenance_message'] ?? '');
                
                $exists = $db->fetchOne("SELECT id FROM settings WHERE setting_key = 'maintenance_mode'");
                if ($exists) {
                    $db->update('settings', ['setting_value' => $maintenanceMode], "setting_key = 'maintenance_mode'");
                } else {
                    $db->insert('settings', ['setting_key' => 'maintenance_mode', 'setting_value' => $maintenanceMode, 'setting_group' => 'system']);
                }
                
                $exists = $db->fetchOne("SELECT id FROM settings WHERE setting_key = 'maintenance_message'");
                if ($exists) {
                    $db->update('settings', ['setting_value' => $maintenanceMessage], "setting_key = 'maintenance_message'");
                } else {
                    $db->insert('settings', ['setting_key' => 'maintenance_message', 'setting_value' => $maintenanceMessage, 'setting_group' => 'system']);
                }
                
                $message = 'Maintenance settings saved successfully';
                break;
        }
    }
}

// Get current settings
function getSetting($key, $default = '') {
    global $db;
    $result = $db->fetchOne("SELECT setting_value FROM settings WHERE setting_key = :key", ['key' => $key]);
    return $result ? $result['setting_value'] : $default;
}

$csrfToken = Security::generateCSRFToken();
$activeTab = $_GET['tab'] ?? 'general';
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="theme-color" content="#050a14" />
    <link rel="icon" type="image/png" href="../assets/images/kmc-rc-logo.png">
    <title>Settings | Admin - KMC RC</title>
    
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
        .tab-btn { transition: all 0.3s ease; }
        .tab-btn.active { background: rgba(0, 242, 255, 0.1); color: #00f2ff; border-color: #00f2ff; }
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
            <a href="messages.php" class="nav-link-admin flex items-center gap-3 px-6 py-3 text-slate-400 hover:text-white">
                <i data-feather="mail" class="w-5 h-5"></i> Messages
            </a>
            <a href="settings.php" class="nav-link-admin active flex items-center gap-3 px-6 py-3 text-white">
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
                    <h1 class="text-xl font-bold text-white font-orbitron">Settings</h1>
                    <p class="text-sm text-slate-400">Configure site settings and preferences</p>
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
            
            <!-- Tabs -->
            <div class="flex flex-wrap gap-2 mb-6">
                <a href="?tab=general" class="tab-btn px-4 py-2 rounded-lg border border-slate-700 <?= $activeTab === 'general' ? 'active' : 'text-slate-400 hover:text-white' ?>">
                    <i data-feather="globe" class="w-4 h-4 inline mr-2"></i> General
                </a>
                <a href="?tab=registration" class="tab-btn px-4 py-2 rounded-lg border border-slate-700 <?= $activeTab === 'registration' ? 'active' : 'text-slate-400 hover:text-white' ?>">
                    <i data-feather="user-plus" class="w-4 h-4 inline mr-2"></i> Registration
                </a>
                <a href="?tab=email" class="tab-btn px-4 py-2 rounded-lg border border-slate-700 <?= $activeTab === 'email' ? 'active' : 'text-slate-400 hover:text-white' ?>">
                    <i data-feather="mail" class="w-4 h-4 inline mr-2"></i> Email
                </a>
                <a href="?tab=maintenance" class="tab-btn px-4 py-2 rounded-lg border border-slate-700 <?= $activeTab === 'maintenance' ? 'active' : 'text-slate-400 hover:text-white' ?>">
                    <i data-feather="tool" class="w-4 h-4 inline mr-2"></i> Maintenance
                </a>
            </div>
            
            <!-- Tab Content -->
            <div class="stat-card-admin rounded-lg p-6">
                <?php if ($activeTab === 'general'): ?>
                <!-- General Settings -->
                <h2 class="text-lg font-bold text-white mb-6">General Settings</h2>
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="form_action" value="general">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Site Name</label>
                            <input type="text" name="site_name" class="form-input w-full px-4 py-3 rounded-lg text-white"
                                   value="<?= htmlspecialchars(getSetting('site_name', 'KMC Robotics Club')) ?>">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Contact Email</label>
                            <input type="email" name="contact_email" class="form-input w-full px-4 py-3 rounded-lg text-white"
                                   value="<?= htmlspecialchars(getSetting('contact_email')) ?>">
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-300 mb-2">Site Description</label>
                            <textarea name="site_description" rows="2" class="form-input w-full px-4 py-3 rounded-lg text-white"><?= htmlspecialchars(getSetting('site_description')) ?></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Contact Phone</label>
                            <input type="tel" name="contact_phone" class="form-input w-full px-4 py-3 rounded-lg text-white"
                                   value="<?= htmlspecialchars(getSetting('contact_phone')) ?>">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Contact Address</label>
                            <input type="text" name="contact_address" class="form-input w-full px-4 py-3 rounded-lg text-white"
                                   value="<?= htmlspecialchars(getSetting('contact_address')) ?>">
                        </div>
                    </div>
                    
                    <div class="border-t border-slate-800 pt-6">
                        <h3 class="text-white font-medium mb-4">Social Media Links</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">Facebook URL</label>
                                <input type="url" name="facebook_url" class="form-input w-full px-4 py-3 rounded-lg text-white"
                                       value="<?= htmlspecialchars(getSetting('facebook_url')) ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">Instagram URL</label>
                                <input type="url" name="instagram_url" class="form-input w-full px-4 py-3 rounded-lg text-white"
                                       value="<?= htmlspecialchars(getSetting('instagram_url')) ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">LinkedIn URL</label>
                                <input type="url" name="linkedin_url" class="form-input w-full px-4 py-3 rounded-lg text-white"
                                       value="<?= htmlspecialchars(getSetting('linkedin_url')) ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">GitHub URL</label>
                                <input type="url" name="github_url" class="form-input w-full px-4 py-3 rounded-lg text-white"
                                       value="<?= htmlspecialchars(getSetting('github_url')) ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="pt-4">
                        <button type="submit" class="bg-accent/20 text-accent px-6 py-3 rounded-lg hover:bg-accent/30 transition flex items-center gap-2">
                            <i data-feather="save" class="w-4 h-4"></i>
                            Save Settings
                        </button>
                    </div>
                </form>
                
                <?php elseif ($activeTab === 'registration'): ?>
                <!-- Registration Settings -->
                <h2 class="text-lg font-bold text-white mb-6">Registration Settings</h2>
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="form_action" value="registration">
                    
                    <div class="space-y-4">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="registration_enabled" class="w-5 h-5 rounded" <?= getSetting('registration_enabled', '1') === '1' ? 'checked' : '' ?>>
                            <div>
                                <div class="text-white font-medium">Enable Registration</div>
                                <div class="text-slate-400 text-sm">Allow new users to register accounts</div>
                            </div>
                        </label>
                        
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="email_verification" class="w-5 h-5 rounded" <?= getSetting('email_verification', '1') === '1' ? 'checked' : '' ?>>
                            <div>
                                <div class="text-white font-medium">Email Verification</div>
                                <div class="text-slate-400 text-sm">Require users to verify their email address</div>
                            </div>
                        </label>
                        
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="admin_approval" class="w-5 h-5 rounded" <?= getSetting('admin_approval', '0') === '1' ? 'checked' : '' ?>>
                            <div>
                                <div class="text-white font-medium">Admin Approval</div>
                                <div class="text-slate-400 text-sm">Require admin approval for new registrations</div>
                            </div>
                        </label>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Default Role</label>
                        <select name="default_role" class="form-input w-full px-4 py-3 rounded-lg text-white">
                            <option value="member" <?= getSetting('default_role', 'member') === 'member' ? 'selected' : '' ?>>Member</option>
                            <option value="moderator" <?= getSetting('default_role') === 'moderator' ? 'selected' : '' ?>>Moderator</option>
                        </select>
                    </div>
                    
                    <div class="pt-4">
                        <button type="submit" class="bg-accent/20 text-accent px-6 py-3 rounded-lg hover:bg-accent/30 transition flex items-center gap-2">
                            <i data-feather="save" class="w-4 h-4"></i>
                            Save Settings
                        </button>
                    </div>
                </form>
                
                <?php elseif ($activeTab === 'email'): ?>
                <!-- Email Settings -->
                <h2 class="text-lg font-bold text-white mb-6">Email Settings (SMTP)</h2>
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="form_action" value="email">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">SMTP Host</label>
                            <input type="text" name="smtp_host" class="form-input w-full px-4 py-3 rounded-lg text-white"
                                   value="<?= htmlspecialchars(getSetting('smtp_host')) ?>" placeholder="smtp.example.com">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">SMTP Port</label>
                            <input type="number" name="smtp_port" class="form-input w-full px-4 py-3 rounded-lg text-white"
                                   value="<?= htmlspecialchars(getSetting('smtp_port', '587')) ?>">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">SMTP Username</label>
                            <input type="text" name="smtp_username" class="form-input w-full px-4 py-3 rounded-lg text-white"
                                   value="<?= htmlspecialchars(getSetting('smtp_username')) ?>">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">SMTP Password</label>
                            <input type="password" name="smtp_password" class="form-input w-full px-4 py-3 rounded-lg text-white"
                                   placeholder="Enter new password to change">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Encryption</label>
                            <select name="smtp_encryption" class="form-input w-full px-4 py-3 rounded-lg text-white">
                                <option value="tls" <?= getSetting('smtp_encryption', 'tls') === 'tls' ? 'selected' : '' ?>>TLS</option>
                                <option value="ssl" <?= getSetting('smtp_encryption') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                <option value="none" <?= getSetting('smtp_encryption') === 'none' ? 'selected' : '' ?>>None</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">From Name</label>
                            <input type="text" name="email_from_name" class="form-input w-full px-4 py-3 rounded-lg text-white"
                                   value="<?= htmlspecialchars(getSetting('email_from_name', 'KMC Robotics Club')) ?>">
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-300 mb-2">From Email Address</label>
                            <input type="email" name="email_from_address" class="form-input w-full px-4 py-3 rounded-lg text-white"
                                   value="<?= htmlspecialchars(getSetting('email_from_address')) ?>">
                        </div>
                    </div>
                    
                    <div class="pt-4">
                        <button type="submit" class="bg-accent/20 text-accent px-6 py-3 rounded-lg hover:bg-accent/30 transition flex items-center gap-2">
                            <i data-feather="save" class="w-4 h-4"></i>
                            Save Settings
                        </button>
                    </div>
                </form>
                
                <?php elseif ($activeTab === 'maintenance'): ?>
                <!-- Maintenance Settings -->
                <h2 class="text-lg font-bold text-white mb-6">Maintenance Mode</h2>
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="form_action" value="maintenance">
                    
                    <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-4 mb-6">
                        <div class="flex items-start gap-3">
                            <i data-feather="alert-triangle" class="w-5 h-5 text-yellow-400 mt-0.5"></i>
                            <div>
                                <div class="text-yellow-400 font-medium">Warning</div>
                                <div class="text-slate-300 text-sm">Enabling maintenance mode will prevent non-admin users from accessing the site.</div>
                            </div>
                        </div>
                    </div>
                    
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="maintenance_mode" class="w-5 h-5 rounded" <?= getSetting('maintenance_mode', '0') === '1' ? 'checked' : '' ?>>
                        <div>
                            <div class="text-white font-medium">Enable Maintenance Mode</div>
                            <div class="text-slate-400 text-sm">Show maintenance page to visitors</div>
                        </div>
                    </label>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Maintenance Message</label>
                        <textarea name="maintenance_message" rows="4" class="form-input w-full px-4 py-3 rounded-lg text-white"
                                  placeholder="We're currently performing scheduled maintenance. Please check back soon."><?= htmlspecialchars(getSetting('maintenance_message')) ?></textarea>
                    </div>
                    
                    <div class="pt-4">
                        <button type="submit" class="bg-accent/20 text-accent px-6 py-3 rounded-lg hover:bg-accent/30 transition flex items-center gap-2">
                            <i data-feather="save" class="w-4 h-4"></i>
                            Save Settings
                        </button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <script>feather.replace();</script>
</body>
</html>
