<?php
/**
 * KMC Robotics Club - Team API
 * Endpoints for team member management
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
    error_log("Team API Error: " . $e->getMessage());
    jsonResponse(['error' => 'Internal server error'], 500);
}

function handleGet($db, $action) {
    switch ($action) {
        case 'list':
            listTeamMembers($db);
            break;
        case 'get':
            getTeamMember($db);
            break;
        case 'executive':
            getExecutiveTeam($db);
            break;
        case 'categories':
            getCategories($db);
            break;
        default:
            listTeamMembers($db);
    }
}

function handlePost($db, $action) {
    Security::requireAdmin();
    
    $data = $_POST;
    createTeamMember($db, $data);
}

function handlePut($db) {
    Security::requireAdmin();
    
    $data = json_decode(file_get_contents('php://input'), true);
    updateTeamMember($db, $data);
}

function handleDelete($db) {
    Security::requireAdmin();
    
    $id = $_GET['id'] ?? null;
    if (!$id) {
        jsonResponse(['error' => 'Member ID required'], 400);
    }
    deleteTeamMember($db, $id);
}

function listTeamMembers($db) {
    $category = $_GET['category'] ?? null;
    $active = isset($_GET['active']) ? (bool)$_GET['active'] : true;
    
    $where = 'is_active = :active';
    $params = ['active' => $active ? 1 : 0];
    
    if ($category) {
        $where .= ' AND category = :category';
        $params['category'] = $category;
    }
    
    $sql = "SELECT * FROM team_members WHERE {$where} ORDER BY position_order ASC, name ASC";
    $members = $db->fetchAll($sql, $params);
    
    // Format members
    foreach ($members as &$member) {
        $member['photo_url'] = $member['photo_path'] 
            ? APP_URL . '/uploads/team/' . $member['photo_path'] 
            : null;
    }
    
    jsonResponse([
        'success' => true,
        'data' => $members,
        'total' => count($members)
    ]);
}

function getTeamMember($db) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        jsonResponse(['error' => 'Member ID required'], 400);
    }
    
    $member = $db->fetchOne("SELECT * FROM team_members WHERE id = :id", ['id' => $id]);
    
    if (!$member) {
        jsonResponse(['error' => 'Member not found'], 404);
    }
    
    $member['photo_url'] = $member['photo_path'] 
        ? APP_URL . '/uploads/team/' . $member['photo_path'] 
        : null;
    
    jsonResponse(['success' => true, 'data' => $member]);
}

function getExecutiveTeam($db) {
    $sql = "SELECT * FROM team_members 
            WHERE is_active = 1 AND category = 'executive' 
            ORDER BY position_order ASC";
    
    $members = $db->fetchAll($sql);
    
    foreach ($members as &$member) {
        $member['photo_url'] = $member['photo_path'] 
            ? APP_URL . '/uploads/team/' . $member['photo_path'] 
            : null;
    }
    
    jsonResponse(['success' => true, 'data' => $members]);
}

function getCategories($db) {
    $categories = [
        ['value' => 'executive', 'label' => 'Executive Committee'],
        ['value' => 'technical', 'label' => 'Technical Team'],
        ['value' => 'creative', 'label' => 'Creative Team'],
        ['value' => 'advisory', 'label' => 'Advisory Board']
    ];
    
    jsonResponse(['success' => true, 'data' => $categories]);
}

function createTeamMember($db, $data) {
    $required = ['name', 'role'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            jsonResponse(['error' => ucfirst($field) . ' is required'], 400);
        }
    }
    
    // Handle photo upload
    $photoPath = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $errors = Security::validateFileUpload($_FILES['photo']);
        if (empty($errors)) {
            $photoPath = Security::generateSafeFilename($_FILES['photo']['name']);
            move_uploaded_file($_FILES['photo']['tmp_name'], UPLOAD_TEAM . '/' . $photoPath);
        }
    }
    
    // Get max position order
    $maxOrder = $db->fetchOne("SELECT MAX(position_order) as max_order FROM team_members WHERE category = :category", 
                              ['category' => $data['category'] ?? 'technical']);
    $positionOrder = ($maxOrder['max_order'] ?? 0) + 1;
    
    $memberId = $db->insert('team_members', [
        'name' => Security::sanitize($data['name']),
        'role' => Security::sanitize($data['role']),
        'position_order' => $data['position_order'] ?? $positionOrder,
        'category' => $data['category'] ?? 'technical',
        'bio' => Security::sanitize($data['bio'] ?? ''),
        'photo_path' => $photoPath,
        'email' => Security::sanitize($data['email'] ?? ''),
        'linkedin' => Security::sanitize($data['linkedin'] ?? ''),
        'github' => Security::sanitize($data['github'] ?? ''),
        'year_joined' => $data['year_joined'] ?? date('Y'),
        'is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1,
        'user_id' => $data['user_id'] ?? null
    ]);
    
    jsonResponse([
        'success' => true,
        'message' => 'Team member added successfully',
        'data' => ['id' => $memberId]
    ], 201);
}

function updateTeamMember($db, $data) {
    if (empty($data['id'])) {
        jsonResponse(['error' => 'Member ID required'], 400);
    }
    
    $member = $db->fetchOne("SELECT * FROM team_members WHERE id = :id", ['id' => $data['id']]);
    if (!$member) {
        jsonResponse(['error' => 'Member not found'], 404);
    }
    
    $updateData = [];
    $allowedFields = ['name', 'role', 'position_order', 'category', 'bio', 
                      'email', 'linkedin', 'github', 'year_joined', 'is_active'];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            if (in_array($field, ['name', 'role', 'bio', 'email', 'linkedin', 'github'])) {
                $updateData[$field] = Security::sanitize($data[$field]);
            } else {
                $updateData[$field] = $data[$field];
            }
        }
    }
    
    if (!empty($updateData)) {
        $db->update('team_members', $updateData, 'id = :id', ['id' => $data['id']]);
    }
    
    jsonResponse(['success' => true, 'message' => 'Team member updated successfully']);
}

function deleteTeamMember($db, $id) {
    $member = $db->fetchOne("SELECT photo_path FROM team_members WHERE id = :id", ['id' => $id]);
    
    if (!$member) {
        jsonResponse(['error' => 'Member not found'], 404);
    }
    
    // Delete photo file
    if ($member['photo_path'] && file_exists(UPLOAD_TEAM . '/' . $member['photo_path'])) {
        unlink(UPLOAD_TEAM . '/' . $member['photo_path']);
    }
    
    $db->delete('team_members', 'id = :id', ['id' => $id]);
    
    jsonResponse(['success' => true, 'message' => 'Team member deleted successfully']);
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}
