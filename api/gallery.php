<?php
/**
 * KMC Robotics Club - Gallery API
 * Endpoints for gallery management
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Security.php';

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

try {
    switch ($method) {
        case 'GET':
            handleGet($db, $action);
            break;
        case 'POST':
            handlePost($db, $action);
            break;
        case 'PUT':
            handlePut($db);
            break;
        case 'DELETE':
            handleDelete($db);
            break;
        default:
            jsonResponse(['error' => 'Method not allowed'], 405);
    }
} catch (Exception $e) {
    error_log("Gallery API Error: " . $e->getMessage());
    jsonResponse(['error' => 'Internal server error'], 500);
}

function handleGet($db, $action) {
    switch ($action) {
        case 'list':
            listGallery($db);
            break;
        case 'get':
            getGalleryItem($db);
            break;
        case 'featured':
            getFeaturedItems($db);
            break;
        case 'pending':
            getPendingItems($db);
            break;
        case 'categories':
            getCategories($db);
            break;
        default:
            listGallery($db);
    }
}

function handlePost($db, $action) {
    switch ($action) {
        case 'upload':
            uploadImage($db);
            break;
        case 'approve':
            Security::requireAdmin();
            approveImage($db);
            break;
        default:
            jsonResponse(['error' => 'Invalid action'], 400);
    }
}

function handlePut($db) {
    Security::requireAdmin();
    
    $data = json_decode(file_get_contents('php://input'), true);
    updateGalleryItem($db, $data);
}

function handleDelete($db) {
    Security::requireAdmin();
    
    $id = $_GET['id'] ?? null;
    if (!$id) {
        jsonResponse(['error' => 'Gallery item ID required'], 400);
    }
    deleteGalleryItem($db, $id);
}

function listGallery($db) {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(50, max(1, (int)($_GET['limit'] ?? GALLERY_PER_PAGE)));
    $offset = ($page - 1) * $limit;
    
    $category = $_GET['category'] ?? null;
    $approved = isset($_GET['approved']) ? (bool)$_GET['approved'] : true;
    
    $where = 'is_approved = :approved';
    $params = ['approved' => $approved ? 1 : 0];
    
    if ($category && $category !== 'all') {
        $where .= ' AND category = :category';
        $params['category'] = $category;
    }
    
    $total = $db->count('gallery', $where, $params);
    
    $sql = "SELECT g.*, u.name as uploaded_by_name
            FROM gallery g
            LEFT JOIN users u ON g.uploaded_by = u.id
            WHERE {$where}
            ORDER BY g.created_at DESC
            LIMIT {$limit} OFFSET {$offset}";
    
    $items = $db->fetchAll($sql, $params);
    
    // Format items
    foreach ($items as &$item) {
        $item['image_url'] = APP_URL . '/uploads/gallery/' . $item['image_path'];
        $item['thumbnail_url'] = $item['thumbnail_path'] 
            ? APP_URL . '/uploads/gallery/thumbnails/' . $item['thumbnail_path']
            : $item['image_url'];
    }
    
    jsonResponse([
        'success' => true,
        'data' => $items,
        'pagination' => [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

function getGalleryItem($db) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        jsonResponse(['error' => 'Gallery item ID required'], 400);
    }
    
    $item = $db->fetchOne(
        "SELECT g.*, u.name as uploaded_by_name
         FROM gallery g
         LEFT JOIN users u ON g.uploaded_by = u.id
         WHERE g.id = :id",
        ['id' => $id]
    );
    
    if (!$item) {
        jsonResponse(['error' => 'Gallery item not found'], 404);
    }
    
    // Increment view count
    $db->query("UPDATE gallery SET view_count = view_count + 1 WHERE id = :id", ['id' => $id]);
    
    $item['image_url'] = APP_URL . '/uploads/gallery/' . $item['image_path'];
    $item['thumbnail_url'] = $item['thumbnail_path'] 
        ? APP_URL . '/uploads/gallery/thumbnails/' . $item['thumbnail_path']
        : $item['image_url'];
    
    jsonResponse(['success' => true, 'data' => $item]);
}

function getFeaturedItems($db) {
    $limit = min(12, max(1, (int)($_GET['limit'] ?? 6)));
    
    $sql = "SELECT g.*, u.name as uploaded_by_name
            FROM gallery g
            LEFT JOIN users u ON g.uploaded_by = u.id
            WHERE g.is_approved = 1 AND g.is_featured = 1
            ORDER BY g.created_at DESC
            LIMIT {$limit}";
    
    $items = $db->fetchAll($sql);
    
    foreach ($items as &$item) {
        $item['image_url'] = APP_URL . '/uploads/gallery/' . $item['image_path'];
        $item['thumbnail_url'] = $item['thumbnail_path'] 
            ? APP_URL . '/uploads/gallery/thumbnails/' . $item['thumbnail_path']
            : $item['image_url'];
    }
    
    jsonResponse(['success' => true, 'data' => $items]);
}

function getPendingItems($db) {
    Security::requireAdmin();
    
    $sql = "SELECT g.*, u.name as uploaded_by_name
            FROM gallery g
            LEFT JOIN users u ON g.uploaded_by = u.id
            WHERE g.is_approved = 0
            ORDER BY g.created_at DESC";
    
    $items = $db->fetchAll($sql);
    
    foreach ($items as &$item) {
        $item['image_url'] = APP_URL . '/uploads/gallery/' . $item['image_path'];
    }
    
    jsonResponse(['success' => true, 'data' => $items]);
}

function getCategories($db) {
    $categories = [
        ['value' => 'all', 'label' => 'All'],
        ['value' => 'projects', 'label' => 'Projects'],
        ['value' => 'events', 'label' => 'Events'],
        ['value' => 'workshops', 'label' => 'Workshops'],
        ['value' => 'competitions', 'label' => 'Competitions'],
        ['value' => 'team', 'label' => 'Team'],
        ['value' => 'other', 'label' => 'Other']
    ];
    
    jsonResponse(['success' => true, 'data' => $categories]);
}

function uploadImage($db) {
    Security::requireAuth();
    
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(['error' => 'No image uploaded'], 400);
    }
    
    $errors = Security::validateFileUpload($_FILES['image']);
    if (!empty($errors)) {
        jsonResponse(['error' => implode('. ', $errors)], 400);
    }
    
    // Generate filename
    $filename = Security::generateSafeFilename($_FILES['image']['name']);
    $filepath = UPLOAD_GALLERY . '/' . $filename;
    
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
        jsonResponse(['error' => 'Failed to save image'], 500);
    }
    
    // Create thumbnail
    $thumbnailPath = createThumbnail($filepath, $filename);
    
    // Auto-approve for admins
    $isApproved = Security::isAdmin() ? 1 : 0;
    
    $itemId = $db->insert('gallery', [
        'title' => Security::sanitize($_POST['title'] ?? pathinfo($filename, PATHINFO_FILENAME)),
        'description' => Security::sanitize($_POST['description'] ?? ''),
        'image_path' => $filename,
        'thumbnail_path' => $thumbnailPath,
        'category' => $_POST['category'] ?? 'other',
        'event_id' => !empty($_POST['event_id']) ? (int)$_POST['event_id'] : null,
        'uploaded_by' => Security::getCurrentUserId(),
        'is_approved' => $isApproved,
        'is_featured' => 0
    ]);
    
    jsonResponse([
        'success' => true,
        'message' => $isApproved ? 'Image uploaded successfully' : 'Image uploaded and pending approval',
        'data' => [
            'id' => $itemId,
            'image_url' => APP_URL . '/uploads/gallery/' . $filename
        ]
    ], 201);
}

function createThumbnail($sourcePath, $filename) {
    $thumbnailDir = UPLOAD_GALLERY . '/thumbnails';
    if (!is_dir($thumbnailDir)) {
        mkdir($thumbnailDir, 0755, true);
    }
    
    $info = getimagesize($sourcePath);
    if (!$info) return null;
    
    $width = $info[0];
    $height = $info[1];
    $type = $info[2];
    
    // Thumbnail dimensions
    $thumbWidth = 400;
    $thumbHeight = 300;
    
    // Calculate aspect ratio
    $srcRatio = $width / $height;
    $thumbRatio = $thumbWidth / $thumbHeight;
    
    if ($srcRatio > $thumbRatio) {
        $newWidth = $thumbWidth;
        $newHeight = $thumbWidth / $srcRatio;
    } else {
        $newHeight = $thumbHeight;
        $newWidth = $thumbHeight * $srcRatio;
    }
    
    // Create image resource
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($sourcePath);
            break;
        case IMAGETYPE_WEBP:
            $source = imagecreatefromwebp($sourcePath);
            break;
        default:
            return null;
    }
    
    if (!$source) return null;
    
    // Create thumbnail
    $thumb = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency for PNG
    if ($type === IMAGETYPE_PNG) {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
    }
    
    imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Save thumbnail
    $thumbFilename = 'thumb_' . $filename;
    $thumbPath = $thumbnailDir . '/' . $thumbFilename;
    
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($thumb, $thumbPath, 85);
            break;
        case IMAGETYPE_PNG:
            imagepng($thumb, $thumbPath, 8);
            break;
        case IMAGETYPE_GIF:
            imagegif($thumb, $thumbPath);
            break;
        case IMAGETYPE_WEBP:
            imagewebp($thumb, $thumbPath, 85);
            break;
    }
    
    imagedestroy($source);
    imagedestroy($thumb);
    
    return $thumbFilename;
}

function approveImage($db) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    
    if (!$id) {
        jsonResponse(['error' => 'Gallery item ID required'], 400);
    }
    
    $db->update('gallery', ['is_approved' => 1], 'id = :id', ['id' => $id]);
    
    // Notify uploader
    $item = $db->fetchOne("SELECT uploaded_by FROM gallery WHERE id = :id", ['id' => $id]);
    if ($item && $item['uploaded_by']) {
        $db->insert('notifications', [
            'user_id' => $item['uploaded_by'],
            'type' => 'approval',
            'title' => 'Image Approved',
            'content' => 'Your gallery image has been approved and is now visible.',
            'link' => '/pages/gallery.php'
        ]);
    }
    
    jsonResponse(['success' => true, 'message' => 'Image approved successfully']);
}

function updateGalleryItem($db, $data) {
    if (empty($data['id'])) {
        jsonResponse(['error' => 'Gallery item ID required'], 400);
    }
    
    $item = $db->fetchOne("SELECT * FROM gallery WHERE id = :id", ['id' => $data['id']]);
    if (!$item) {
        jsonResponse(['error' => 'Gallery item not found'], 404);
    }
    
    $updateData = [];
    $allowedFields = ['title', 'description', 'category', 'is_featured', 'is_approved'];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            if (in_array($field, ['title', 'description'])) {
                $updateData[$field] = Security::sanitize($data[$field]);
            } else {
                $updateData[$field] = $data[$field];
            }
        }
    }
    
    if (!empty($updateData)) {
        $db->update('gallery', $updateData, 'id = :id', ['id' => $data['id']]);
    }
    
    jsonResponse(['success' => true, 'message' => 'Gallery item updated successfully']);
}

function deleteGalleryItem($db, $id) {
    $item = $db->fetchOne("SELECT image_path, thumbnail_path FROM gallery WHERE id = :id", ['id' => $id]);
    
    if (!$item) {
        jsonResponse(['error' => 'Gallery item not found'], 404);
    }
    
    // Delete image files
    if ($item['image_path'] && file_exists(UPLOAD_GALLERY . '/' . $item['image_path'])) {
        unlink(UPLOAD_GALLERY . '/' . $item['image_path']);
    }
    if ($item['thumbnail_path'] && file_exists(UPLOAD_GALLERY . '/thumbnails/' . $item['thumbnail_path'])) {
        unlink(UPLOAD_GALLERY . '/thumbnails/' . $item['thumbnail_path']);
    }
    
    $db->delete('gallery', 'id = :id', ['id' => $id]);
    
    jsonResponse(['success' => true, 'message' => 'Gallery item deleted successfully']);
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}
