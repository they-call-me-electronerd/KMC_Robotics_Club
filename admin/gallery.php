<?php
/**
 * KMC Robotics Club - Admin Gallery Management
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
            case 'upload':
                if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                    $category = $_POST['category'] ?? 'general';
                    $eventId = !empty($_POST['event_id']) ? (int)$_POST['event_id'] : null;
                    $successCount = 0;
                    $errorCount = 0;
                    
                    foreach ($_FILES['images']['name'] as $key => $name) {
                        if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                            $file = [
                                'name' => $name,
                                'type' => $_FILES['images']['type'][$key],
                                'tmp_name' => $_FILES['images']['tmp_name'][$key],
                                'error' => $_FILES['images']['error'][$key],
                                'size' => $_FILES['images']['size'][$key]
                            ];
                            
                            $errors = Security::validateFileUpload($file);
                            if (empty($errors)) {
                                $filename = Security::generateSafeFilename($name);
                                $thumbFilename = 'thumb_' . $filename;
                                
                                if (move_uploaded_file($file['tmp_name'], UPLOAD_GALLERY . '/' . $filename)) {
                                    // Create thumbnail
                                    $thumbPath = UPLOAD_GALLERY . '/thumbnails/' . $thumbFilename;
                                    if (!is_dir(UPLOAD_GALLERY . '/thumbnails')) {
                                        mkdir(UPLOAD_GALLERY . '/thumbnails', 0755, true);
                                    }
                                    
                                    $imgInfo = getimagesize(UPLOAD_GALLERY . '/' . $filename);
                                    if ($imgInfo) {
                                        $srcW = $imgInfo[0];
                                        $srcH = $imgInfo[1];
                                        $thumbW = 300;
                                        $thumbH = (int)($srcH * ($thumbW / $srcW));
                                        
                                        $srcImg = match($imgInfo[2]) {
                                            IMAGETYPE_JPEG => imagecreatefromjpeg(UPLOAD_GALLERY . '/' . $filename),
                                            IMAGETYPE_PNG => imagecreatefrompng(UPLOAD_GALLERY . '/' . $filename),
                                            IMAGETYPE_GIF => imagecreatefromgif(UPLOAD_GALLERY . '/' . $filename),
                                            IMAGETYPE_WEBP => imagecreatefromwebp(UPLOAD_GALLERY . '/' . $filename),
                                            default => null
                                        };
                                        
                                        if ($srcImg) {
                                            $thumbImg = imagecreatetruecolor($thumbW, $thumbH);
                                            imagecopyresampled($thumbImg, $srcImg, 0, 0, 0, 0, $thumbW, $thumbH, $srcW, $srcH);
                                            imagejpeg($thumbImg, $thumbPath, 85);
                                            imagedestroy($srcImg);
                                            imagedestroy($thumbImg);
                                        }
                                    }
                                    
                                    $db->insert('gallery', [
                                        'title' => pathinfo($name, PATHINFO_FILENAME),
                                        'image_path' => $filename,
                                        'thumbnail_path' => $thumbFilename,
                                        'category' => $category,
                                        'event_id' => $eventId,
                                        'uploaded_by' => Security::getCurrentUserId(),
                                        'is_approved' => 1
                                    ]);
                                    $successCount++;
                                } else {
                                    $errorCount++;
                                }
                            } else {
                                $errorCount++;
                            }
                        }
                    }
                    
                    $message = "Uploaded {$successCount} images successfully";
                    if ($errorCount > 0) {
                        $message .= ", {$errorCount} failed";
                    }
                }
                break;
                
            case 'update':
                $galleryData = [
                    'title' => Security::sanitize($_POST['title']),
                    'description' => Security::sanitize($_POST['description'] ?? ''),
                    'category' => $_POST['category'],
                    'event_id' => !empty($_POST['event_id']) ? (int)$_POST['event_id'] : null,
                    'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                    'is_approved' => isset($_POST['is_approved']) ? 1 : 0
                ];
                
                $db->update('gallery', $galleryData, 'id = :id', ['id' => $_POST['id']]);
                $message = 'Image updated successfully';
                break;
                
            case 'delete':
                $image = $db->fetchOne("SELECT image_path, thumbnail_path FROM gallery WHERE id = :id", ['id' => $_POST['id']]);
                if ($image) {
                    if ($image['image_path'] && file_exists(UPLOAD_GALLERY . '/' . $image['image_path'])) {
                        unlink(UPLOAD_GALLERY . '/' . $image['image_path']);
                    }
                    if ($image['thumbnail_path'] && file_exists(UPLOAD_GALLERY . '/thumbnails/' . $image['thumbnail_path'])) {
                        unlink(UPLOAD_GALLERY . '/thumbnails/' . $image['thumbnail_path']);
                    }
                    $db->delete('gallery', 'id = :id', ['id' => $_POST['id']]);
                    $message = 'Image deleted successfully';
                }
                break;
                
            case 'bulk-delete':
                if (!empty($_POST['selected'])) {
                    $deleted = 0;
                    foreach ($_POST['selected'] as $id) {
                        $image = $db->fetchOne("SELECT image_path, thumbnail_path FROM gallery WHERE id = :id", ['id' => $id]);
                        if ($image) {
                            if ($image['image_path'] && file_exists(UPLOAD_GALLERY . '/' . $image['image_path'])) {
                                unlink(UPLOAD_GALLERY . '/' . $image['image_path']);
                            }
                            if ($image['thumbnail_path'] && file_exists(UPLOAD_GALLERY . '/thumbnails/' . $image['thumbnail_path'])) {
                                unlink(UPLOAD_GALLERY . '/thumbnails/' . $image['thumbnail_path']);
                            }
                            $db->delete('gallery', 'id = :id', ['id' => $id]);
                            $deleted++;
                        }
                    }
                    $message = "Deleted {$deleted} images";
                }
                break;
                
            case 'approve':
                $db->update('gallery', ['is_approved' => 1], 'id = :id', ['id' => $_POST['id']]);
                $message = 'Image approved';
                break;
                
            case 'feature':
                $image = $db->fetchOne("SELECT is_featured FROM gallery WHERE id = :id", ['id' => $_POST['id']]);
                if ($image) {
                    $db->update('gallery', ['is_featured' => !$image['is_featured']], 'id = :id', ['id' => $_POST['id']]);
                    $message = $image['is_featured'] ? 'Removed from featured' : 'Added to featured';
                }
                break;
        }
    }
}

// Get gallery images
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 24;
$offset = ($page - 1) * $limit;

$where = '1=1';
$params = [];

if (!empty($_GET['category'])) {
    $where .= ' AND category = :category';
    $params['category'] = $_GET['category'];
}

if (isset($_GET['pending'])) {
    $where .= ' AND is_approved = 0';
}

if (isset($_GET['featured'])) {
    $where .= ' AND is_featured = 1';
}

$total = $db->count('gallery', $where, $params);
$images = $db->fetchAll(
    "SELECT g.*, u.name as uploaded_by_name, e.title as event_title
     FROM gallery g
     LEFT JOIN users u ON g.uploaded_by = u.id
     LEFT JOIN events e ON g.event_id = e.id
     WHERE {$where}
     ORDER BY g.created_at DESC
     LIMIT {$limit} OFFSET {$offset}",
    $params
);

// Get image for editing
$editImage = null;
if (isset($_GET['edit'])) {
    $editImage = $db->fetchOne("SELECT * FROM gallery WHERE id = :id", ['id' => $_GET['edit']]);
}

// Get events for dropdown
$events = $db->fetchAll("SELECT id, title FROM events ORDER BY event_date DESC LIMIT 50");

// Categories
$categories = ['general', 'workshop', 'competition', 'team', 'project', 'event'];

// Stats
$stats = [
    'total' => $db->count('gallery'),
    'pending' => $db->count('gallery', 'is_approved = 0'),
    'featured' => $db->count('gallery', 'is_featured = 1')
];

$csrfToken = Security::generateCSRFToken();
$showUpload = isset($_GET['action']) && $_GET['action'] === 'upload';
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="theme-color" content="#050a14" />
    <link rel="icon" type="image/png" href="../assets/images/kmc-rc-logo.png">
    <title>Manage Gallery | Admin - KMC RC</title>
    
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
        .gallery-card { transition: all 0.3s ease; }
        .gallery-card:hover { transform: scale(1.02); }
        .gallery-card.selected { border-color: #00f2ff; box-shadow: 0 0 20px rgba(0, 242, 255, 0.3); }
        .upload-zone { border: 2px dashed rgba(0, 242, 255, 0.3); transition: all 0.3s ease; }
        .upload-zone:hover, .upload-zone.dragover { border-color: #00f2ff; background: rgba(0, 242, 255, 0.05); }
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
            <a href="gallery.php" class="nav-link-admin active flex items-center gap-3 px-6 py-3 text-white">
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
                    <h1 class="text-xl font-bold text-white font-orbitron">Manage Gallery</h1>
                    <p class="text-sm text-slate-400">Upload and manage gallery images</p>
                </div>
                <div class="flex gap-2">
                    <?php if ($stats['pending'] > 0): ?>
                    <a href="?pending" class="bg-yellow-500/20 text-yellow-400 px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-yellow-500/30 transition">
                        <i data-feather="clock" class="w-4 h-4"></i> <?= $stats['pending'] ?> Pending
                    </a>
                    <?php endif; ?>
                    <a href="?action=upload" class="bg-accent/20 text-accent px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-accent/30 transition">
                        <i data-feather="upload" class="w-4 h-4"></i> Upload
                    </a>
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
            
            <!-- Stats -->
            <div class="grid grid-cols-3 gap-4 mb-6">
                <div class="stat-card-admin rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-white"><?= number_format($stats['total']) ?></div>
                    <div class="text-slate-400 text-sm">Total Images</div>
                </div>
                <div class="stat-card-admin rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-yellow-400"><?= number_format($stats['pending']) ?></div>
                    <div class="text-slate-400 text-sm">Pending Approval</div>
                </div>
                <div class="stat-card-admin rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-accent"><?= number_format($stats['featured']) ?></div>
                    <div class="text-slate-400 text-sm">Featured</div>
                </div>
            </div>
            
            <?php if ($showUpload): ?>
            <!-- Upload Form -->
            <div class="stat-card-admin rounded-lg p-6 mb-6">
                <h2 class="text-lg font-bold text-white mb-6">Upload Images</h2>
                <form method="POST" enctype="multipart/form-data" id="uploadForm">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="form_action" value="upload">
                    
                    <div class="upload-zone rounded-lg p-8 text-center mb-6" id="dropZone">
                        <i data-feather="upload-cloud" class="w-12 h-12 text-accent mx-auto mb-4"></i>
                        <p class="text-white mb-2">Drag & drop images here or click to browse</p>
                        <p class="text-slate-400 text-sm">Supports JPG, PNG, GIF, WebP (max 5MB each)</p>
                        <input type="file" name="images[]" id="fileInput" multiple accept="image/*" class="hidden">
                    </div>
                    
                    <div id="previewContainer" class="grid grid-cols-4 gap-4 mb-6 hidden"></div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Category</label>
                            <select name="category" class="form-input w-full px-4 py-3 rounded-lg text-white">
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat ?>"><?= ucfirst($cat) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Related Event (Optional)</label>
                            <select name="event_id" class="form-input w-full px-4 py-3 rounded-lg text-white">
                                <option value="">None</option>
                                <?php foreach ($events as $event): ?>
                                <option value="<?= $event['id'] ?>"><?= htmlspecialchars($event['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="flex gap-4 pt-6">
                        <button type="submit" id="uploadBtn" disabled class="bg-accent/20 text-accent px-6 py-3 rounded-lg hover:bg-accent/30 transition flex items-center gap-2 disabled:opacity-50">
                            <i data-feather="upload" class="w-4 h-4"></i>
                            Upload Images
                        </button>
                        <a href="gallery.php" class="bg-slate-700 text-white px-6 py-3 rounded-lg hover:bg-slate-600 transition">Cancel</a>
                    </div>
                </form>
            </div>
            <?php endif; ?>
            
            <?php if ($editImage): ?>
            <!-- Edit Form -->
            <div class="stat-card-admin rounded-lg p-6 mb-6">
                <h2 class="text-lg font-bold text-white mb-6">Edit Image</h2>
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="form_action" value="update">
                    <input type="hidden" name="id" value="<?= $editImage['id'] ?>">
                    
                    <div class="flex gap-6">
                        <img src="../uploads/gallery/<?= htmlspecialchars($editImage['image_path']) ?>" alt="" class="w-48 h-48 object-cover rounded-lg">
                        
                        <div class="flex-1 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">Title</label>
                                <input type="text" name="title" class="form-input w-full px-4 py-3 rounded-lg text-white"
                                       value="<?= htmlspecialchars($editImage['title'] ?? '') ?>">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">Description</label>
                                <textarea name="description" rows="2" class="form-input w-full px-4 py-3 rounded-lg text-white"><?= htmlspecialchars($editImage['description'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-2">Category</label>
                                    <select name="category" class="form-input w-full px-4 py-3 rounded-lg text-white">
                                        <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat ?>" <?= ($editImage['category'] ?? '') === $cat ? 'selected' : '' ?>><?= ucfirst($cat) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-2">Event</label>
                                    <select name="event_id" class="form-input w-full px-4 py-3 rounded-lg text-white">
                                        <option value="">None</option>
                                        <?php foreach ($events as $event): ?>
                                        <option value="<?= $event['id'] ?>" <?= ($editImage['event_id'] ?? '') == $event['id'] ? 'selected' : '' ?>><?= htmlspecialchars($event['title']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="flex gap-6">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="is_approved" class="w-4 h-4 rounded" <?= ($editImage['is_approved'] ?? 0) ? 'checked' : '' ?>>
                                    <span class="text-slate-300">Approved</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="is_featured" class="w-4 h-4 rounded" <?= ($editImage['is_featured'] ?? 0) ? 'checked' : '' ?>>
                                    <span class="text-slate-300">Featured</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex gap-4">
                        <button type="submit" class="bg-accent/20 text-accent px-6 py-3 rounded-lg hover:bg-accent/30 transition flex items-center gap-2">
                            <i data-feather="save" class="w-4 h-4"></i>
                            Save Changes
                        </button>
                        <a href="gallery.php" class="bg-slate-700 text-white px-6 py-3 rounded-lg hover:bg-slate-600 transition">Cancel</a>
                    </div>
                </form>
            </div>
            <?php endif; ?>
            
            <!-- Filters -->
            <div class="flex flex-wrap gap-2 mb-6">
                <a href="gallery.php" class="px-4 py-2 rounded-lg <?= empty($_GET['category']) && !isset($_GET['pending']) && !isset($_GET['featured']) ? 'bg-accent text-dark-navy' : 'bg-slate-800 text-white hover:bg-slate-700' ?> transition">
                    All
                </a>
                <?php foreach ($categories as $cat): ?>
                <a href="?category=<?= $cat ?>" class="px-4 py-2 rounded-lg <?= ($_GET['category'] ?? '') === $cat ? 'bg-accent text-dark-navy' : 'bg-slate-800 text-white hover:bg-slate-700' ?> transition">
                    <?= ucfirst($cat) ?>
                </a>
                <?php endforeach; ?>
                <a href="?featured" class="px-4 py-2 rounded-lg <?= isset($_GET['featured']) ? 'bg-accent text-dark-navy' : 'bg-slate-800 text-white hover:bg-slate-700' ?> transition">
                    ⭐ Featured
                </a>
            </div>
            
            <!-- Gallery Grid -->
            <form method="POST" id="bulkForm">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="form_action" value="bulk-delete">
                
                <div class="flex items-center justify-between mb-4">
                    <div class="text-slate-400 text-sm">
                        Showing <?= count($images) ?> of <?= $total ?> images
                    </div>
                    <button type="submit" id="bulkDeleteBtn" class="bg-red-500/20 text-red-400 px-4 py-2 rounded-lg hover:bg-red-500/30 transition hidden" onclick="return confirm('Delete selected images?')">
                        <i data-feather="trash-2" class="w-4 h-4 inline"></i> Delete Selected
                    </button>
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <?php foreach ($images as $image): ?>
                    <div class="gallery-card stat-card-admin rounded-lg overflow-hidden relative group" data-id="<?= $image['id'] ?>">
                        <div class="aspect-square relative">
                            <img src="../uploads/gallery/<?= htmlspecialchars($image['thumbnail_path'] ?: $image['image_path']) ?>" 
                                 alt="<?= htmlspecialchars($image['title'] ?? '') ?>" 
                                 class="w-full h-full object-cover">
                            
                            <?php if (!$image['is_approved']): ?>
                            <div class="absolute top-2 left-2 bg-yellow-500/80 text-white text-xs px-2 py-1 rounded">Pending</div>
                            <?php endif; ?>
                            
                            <?php if ($image['is_featured']): ?>
                            <div class="absolute top-2 right-2 bg-accent/80 text-dark-navy text-xs px-2 py-1 rounded">⭐</div>
                            <?php endif; ?>
                            
                            <!-- Hover Actions -->
                            <div class="absolute inset-0 bg-dark-navy/80 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-2">
                                <input type="checkbox" name="selected[]" value="<?= $image['id'] ?>" class="select-checkbox absolute top-2 left-2 w-5 h-5">
                                
                                <a href="?edit=<?= $image['id'] ?>" class="bg-accent/20 text-accent p-2 rounded hover:bg-accent/30">
                                    <i data-feather="edit-2" class="w-4 h-4"></i>
                                </a>
                                
                                <?php if (!$image['is_approved']): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <input type="hidden" name="form_action" value="approve">
                                    <input type="hidden" name="id" value="<?= $image['id'] ?>">
                                    <button type="submit" class="bg-green-500/20 text-green-400 p-2 rounded hover:bg-green-500/30">
                                        <i data-feather="check" class="w-4 h-4"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                                
                                <form method="POST" class="inline">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <input type="hidden" name="form_action" value="feature">
                                    <input type="hidden" name="id" value="<?= $image['id'] ?>">
                                    <button type="submit" class="bg-purple-500/20 text-purple-400 p-2 rounded hover:bg-purple-500/30" title="<?= $image['is_featured'] ? 'Remove from featured' : 'Add to featured' ?>">
                                        <i data-feather="star" class="w-4 h-4"></i>
                                    </button>
                                </form>
                                
                                <form method="POST" class="inline" onsubmit="return confirm('Delete this image?')">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <input type="hidden" name="form_action" value="delete">
                                    <input type="hidden" name="id" value="<?= $image['id'] ?>">
                                    <button type="submit" class="bg-red-500/20 text-red-400 p-2 rounded hover:bg-red-500/30">
                                        <i data-feather="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="p-2">
                            <div class="text-white text-sm truncate"><?= htmlspecialchars($image['title'] ?? 'Untitled') ?></div>
                            <div class="text-slate-400 text-xs"><?= ucfirst($image['category']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($images)): ?>
                    <div class="col-span-full text-center py-12 text-slate-400">
                        <i data-feather="image" class="w-16 h-16 mx-auto mb-4 opacity-50"></i>
                        <p>No images found</p>
                        <a href="?action=upload" class="inline-block mt-4 text-accent hover:underline">Upload your first image</a>
                    </div>
                    <?php endif; ?>
                </div>
            </form>
            
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
    
    <script>
        feather.replace();
        
        // File upload handling
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const previewContainer = document.getElementById('previewContainer');
        const uploadBtn = document.getElementById('uploadBtn');
        
        if (dropZone) {
            dropZone.addEventListener('click', () => fileInput.click());
            
            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.classList.add('dragover');
            });
            
            dropZone.addEventListener('dragleave', () => {
                dropZone.classList.remove('dragover');
            });
            
            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropZone.classList.remove('dragover');
                fileInput.files = e.dataTransfer.files;
                handleFiles(e.dataTransfer.files);
            });
            
            fileInput.addEventListener('change', (e) => {
                handleFiles(e.target.files);
            });
            
            function handleFiles(files) {
                previewContainer.innerHTML = '';
                previewContainer.classList.remove('hidden');
                uploadBtn.disabled = files.length === 0;
                
                Array.from(files).forEach(file => {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        const div = document.createElement('div');
                        div.className = 'aspect-square rounded-lg overflow-hidden';
                        div.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
                        previewContainer.appendChild(div);
                    };
                    reader.readAsDataURL(file);
                });
            }
        }
        
        // Bulk selection
        document.querySelectorAll('.select-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                const selected = document.querySelectorAll('.select-checkbox:checked').length;
                document.getElementById('bulkDeleteBtn').classList.toggle('hidden', selected === 0);
                checkbox.closest('.gallery-card').classList.toggle('selected', checkbox.checked);
            });
        });
    </script>
</body>
</html>
