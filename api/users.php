<?php
/**
 * KMC Robotics Club - Users API
 * Endpoints for user management (Admin only)
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
            handlePut($db, $action);
            break;
        case 'DELETE':
            handleDelete($db);
            break;
        default:
            jsonResponse(['error' => 'Method not allowed'], 405);
    }
} catch (Exception $e) {
    error_log("Users API Error: " . $e->getMessage());
    jsonResponse(['error' => 'Internal server error'], 500);
}

function handleGet($db, $action) {
    switch ($action) {
        case 'list':
            Security::requireAdmin();
            listUsers($db);
            break;
        case 'get':
            getUser($db);
            break;
        case 'profile':
            getProfile($db);
            break;
        case 'stats':
            Security::requireAdmin();
            getUserStats($db);
            break;
        default:
            Security::requireAdmin();
            listUsers($db);
    }
}

function handlePost($db, $action) {
    Security::requireAdmin();
    
    switch ($action) {
        case 'create':
            createUser($db);
            break;
        default:
            jsonResponse(['error' => 'Invalid action'], 400);
    }
}

function handlePut($db, $action) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'update':
            updateUser($db, $data);
            break;
        case 'change-role':
            Security::requireAdmin();
            changeUserRole($db, $data);
            break;
        case 'change-status':
            Security::requireAdmin();
            changeUserStatus($db, $data);
            break;
        default:
            jsonResponse(['error' => 'Invalid action'], 400);
    }
}

function handleDelete($db) {
    Security::requireAdmin();
    
    $id = $_GET['id'] ?? null;
    if (!$id) {
        jsonResponse(['error' => 'User ID required'], 400);
    }
    deleteUser($db, $id);
}

function listUsers($db) {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    
    $role = $_GET['role'] ?? null;
    $status = $_GET['status'] ?? null;
    $search = $_GET['search'] ?? null;
    
    $where = '1=1';
    $params = [];
    
    if ($role) {
        $where .= ' AND role = :role';
        $params['role'] = $role;
    }
    
    if ($status) {
        $where .= ' AND status = :status';
        $params['status'] = $status;
    }
    
    if ($search) {
        $where .= ' AND (name LIKE :search OR email LIKE :search2)';
        $params['search'] = "%{$search}%";
        $params['search2'] = "%{$search}%";
    }
    
    $total = $db->count('users', $where, $params);
    
    $sql = "SELECT id, name, email, role, profile_pic, phone, department, 
                   year_of_study, status, email_verified, last_login, created_at
            FROM users
            WHERE {$where}
            ORDER BY created_at DESC
            LIMIT {$limit} OFFSET {$offset}";
    
    $users = $db->fetchAll($sql, $params);
    
    // Format users
    foreach ($users as &$user) {
        $user['profile_pic_url'] = $user['profile_pic'] 
            ? APP_URL . '/uploads/profiles/' . $user['profile_pic'] 
            : null;
    }
    
    jsonResponse([
        'success' => true,
        'data' => $users,
        'pagination' => [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

function getUser($db) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        jsonResponse(['error' => 'User ID required'], 400);
    }
    
    // Check permissions
    $currentUserId = Security::getCurrentUserId();
    if (!Security::isAdmin() && $currentUserId != $id) {
        jsonResponse(['error' => 'Access denied'], 403);
    }
    
    $user = $db->fetchOne(
        "SELECT id, name, email, role, profile_pic, phone, student_id, department,
                year_of_study, bio, skills, linkedin, github, status, email_verified,
                last_login, created_at
         FROM users WHERE id = :id",
        ['id' => $id]
    );
    
    if (!$user) {
        jsonResponse(['error' => 'User not found'], 404);
    }
    
    $user['profile_pic_url'] = $user['profile_pic'] 
        ? APP_URL . '/uploads/profiles/' . $user['profile_pic'] 
        : null;
    
    // Get additional stats for admin
    if (Security::isAdmin()) {
        $user['event_registrations'] = $db->count('event_registrations', 'user_id = :id', ['id' => $id]);
        $user['gallery_uploads'] = $db->count('gallery', 'uploaded_by = :id', ['id' => $id]);
        $user['messages_sent'] = $db->count('messages', 'sender_id = :id', ['id' => $id]);
    }
    
    jsonResponse(['success' => true, 'data' => $user]);
}

function getProfile($db) {
    Security::requireAuth();
    
    $userId = Security::getCurrentUserId();
    
    $user = $db->fetchOne(
        "SELECT id, name, email, role, profile_pic, phone, student_id, department,
                year_of_study, bio, skills, linkedin, github, status, email_verified,
                last_login, created_at
         FROM users WHERE id = :id",
        ['id' => $userId]
    );
    
    if (!$user) {
        jsonResponse(['error' => 'User not found'], 404);
    }
    
    $user['profile_pic_url'] = $user['profile_pic'] 
        ? APP_URL . '/uploads/profiles/' . $user['profile_pic'] 
        : null;
    
    // Get user's activity
    $user['event_registrations'] = $db->fetchAll(
        "SELECT e.id, e.title, e.event_date, er.registered_at, er.status
         FROM event_registrations er
         JOIN events e ON er.event_id = e.id
         WHERE er.user_id = :user_id
         ORDER BY e.event_date DESC
         LIMIT 5",
        ['user_id' => $userId]
    );
    
    $user['unread_notifications'] = $db->count('notifications', 
        'user_id = :user_id AND is_read = 0', ['user_id' => $userId]);
    
    $user['unread_messages'] = $db->count('messages', 
        'recipient_id = :user_id AND status = :status', 
        ['user_id' => $userId, 'status' => 'unread']);
    
    jsonResponse(['success' => true, 'data' => $user]);
}

function getUserStats($db) {
    $stats = [
        'total_users' => $db->count('users'),
        'active_members' => $db->count('users', 'status = :status AND role = :role', 
                                       ['status' => 'active', 'role' => 'member']),
        'pending_members' => $db->count('users', 'status = :status', ['status' => 'pending']),
        'admins' => $db->count('users', 'role = :role', ['role' => 'admin']),
        'new_this_month' => $db->count('users', 'created_at >= :date', 
                                       ['date' => date('Y-m-01')])
    ];
    
    // Get registrations by department
    $departmentStats = $db->fetchAll(
        "SELECT department, COUNT(*) as count 
         FROM users 
         WHERE department IS NOT NULL AND department != ''
         GROUP BY department 
         ORDER BY count DESC"
    );
    $stats['by_department'] = $departmentStats;
    
    // Get registrations by year
    $yearStats = $db->fetchAll(
        "SELECT year_of_study, COUNT(*) as count 
         FROM users 
         WHERE year_of_study IS NOT NULL
         GROUP BY year_of_study 
         ORDER BY year_of_study"
    );
    $stats['by_year'] = $yearStats;
    
    jsonResponse(['success' => true, 'data' => $stats]);
}

function createUser($db) {
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    $required = ['name', 'email', 'password'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            jsonResponse(['error' => ucfirst($field) . ' is required'], 400);
        }
    }
    
    // Check if email exists
    if ($db->fetchOne("SELECT id FROM users WHERE email = :email", ['email' => $data['email']])) {
        jsonResponse(['error' => 'Email already registered'], 400);
    }
    
    $userId = $db->insert('users', [
        'name' => Security::sanitize($data['name']),
        'email' => strtolower(trim($data['email'])),
        'password_hash' => Security::hashPassword($data['password']),
        'role' => $data['role'] ?? 'member',
        'status' => $data['status'] ?? 'active',
        'email_verified' => 1,
        'phone' => Security::sanitize($data['phone'] ?? ''),
        'department' => Security::sanitize($data['department'] ?? ''),
        'year_of_study' => !empty($data['year_of_study']) ? (int)$data['year_of_study'] : null
    ]);
    
    jsonResponse([
        'success' => true,
        'message' => 'User created successfully',
        'data' => ['id' => $userId]
    ], 201);
}

function updateUser($db, $data) {
    Security::requireAuth();
    
    $id = $data['id'] ?? null;
    if (!$id) {
        jsonResponse(['error' => 'User ID required'], 400);
    }
    
    // Check permissions
    $currentUserId = Security::getCurrentUserId();
    if (!Security::isAdmin() && $currentUserId != $id) {
        jsonResponse(['error' => 'Access denied'], 403);
    }
    
    $user = $db->fetchOne("SELECT * FROM users WHERE id = :id", ['id' => $id]);
    if (!$user) {
        jsonResponse(['error' => 'User not found'], 404);
    }
    
    $updateData = [];
    $allowedFields = ['name', 'phone', 'bio', 'skills', 'linkedin', 'github', 'department', 'year_of_study'];
    
    // Admin can update more fields
    if (Security::isAdmin()) {
        $allowedFields = array_merge($allowedFields, ['student_id', 'email']);
    }
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            if (in_array($field, ['name', 'phone', 'bio', 'skills', 'linkedin', 'github', 'department', 'student_id'])) {
                $updateData[$field] = Security::sanitize($data[$field]);
            } elseif ($field === 'email') {
                $updateData[$field] = strtolower(trim($data[$field]));
            } else {
                $updateData[$field] = $data[$field];
            }
        }
    }
    
    if (!empty($updateData)) {
        $db->update('users', $updateData, 'id = :id', ['id' => $id]);
        
        // Update session if current user
        if ($currentUserId == $id && isset($updateData['name'])) {
            $_SESSION['user_name'] = $updateData['name'];
        }
    }
    
    jsonResponse(['success' => true, 'message' => 'User updated successfully']);
}

function changeUserRole($db, $data) {
    $id = $data['id'] ?? null;
    $role = $data['role'] ?? null;
    
    if (!$id || !$role) {
        jsonResponse(['error' => 'User ID and role are required'], 400);
    }
    
    if (!in_array($role, ['admin', 'member'])) {
        jsonResponse(['error' => 'Invalid role'], 400);
    }
    
    // Prevent changing own role
    if ($id == Security::getCurrentUserId()) {
        jsonResponse(['error' => 'Cannot change your own role'], 400);
    }
    
    $user = $db->fetchOne("SELECT id FROM users WHERE id = :id", ['id' => $id]);
    if (!$user) {
        jsonResponse(['error' => 'User not found'], 404);
    }
    
    $db->update('users', ['role' => $role], 'id = :id', ['id' => $id]);
    
    // Log activity
    $db->insert('activity_logs', [
        'user_id' => Security::getCurrentUserId(),
        'action' => 'role_changed',
        'entity_type' => 'users',
        'entity_id' => $id,
        'details' => json_encode(['new_role' => $role]),
        'ip_address' => Security::getClientIP()
    ]);
    
    jsonResponse(['success' => true, 'message' => 'User role updated successfully']);
}

function changeUserStatus($db, $data) {
    $id = $data['id'] ?? null;
    $status = $data['status'] ?? null;
    
    if (!$id || !$status) {
        jsonResponse(['error' => 'User ID and status are required'], 400);
    }
    
    if (!in_array($status, ['active', 'inactive', 'pending'])) {
        jsonResponse(['error' => 'Invalid status'], 400);
    }
    
    // Prevent changing own status
    if ($id == Security::getCurrentUserId()) {
        jsonResponse(['error' => 'Cannot change your own status'], 400);
    }
    
    $user = $db->fetchOne("SELECT id FROM users WHERE id = :id", ['id' => $id]);
    if (!$user) {
        jsonResponse(['error' => 'User not found'], 404);
    }
    
    $db->update('users', ['status' => $status], 'id = :id', ['id' => $id]);
    
    // Notify user
    $db->insert('notifications', [
        'user_id' => $id,
        'type' => 'system',
        'title' => 'Account Status Updated',
        'content' => 'Your account status has been changed to: ' . ucfirst($status)
    ]);
    
    jsonResponse(['success' => true, 'message' => 'User status updated successfully']);
}

function deleteUser($db, $id) {
    // Prevent self-deletion
    if ($id == Security::getCurrentUserId()) {
        jsonResponse(['error' => 'Cannot delete your own account'], 400);
    }
    
    $user = $db->fetchOne("SELECT profile_pic FROM users WHERE id = :id", ['id' => $id]);
    if (!$user) {
        jsonResponse(['error' => 'User not found'], 404);
    }
    
    // Delete profile picture
    if ($user['profile_pic'] && file_exists(UPLOAD_PROFILES . '/' . $user['profile_pic'])) {
        unlink(UPLOAD_PROFILES . '/' . $user['profile_pic']);
    }
    
    $db->delete('users', 'id = :id', ['id' => $id]);
    
    jsonResponse(['success' => true, 'message' => 'User deleted successfully']);
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}
