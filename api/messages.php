<?php
/**
 * KMC Robotics Club - Messages API
 * Endpoints for messaging system
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
    error_log("Messages API Error: " . $e->getMessage());
    jsonResponse(['error' => 'Internal server error'], 500);
}

function handleGet($db, $action) {
    switch ($action) {
        case 'inbox':
            getInbox($db);
            break;
        case 'sent':
            getSentMessages($db);
            break;
        case 'get':
            getMessage($db);
            break;
        case 'unread-count':
            getUnreadCount($db);
            break;
        case 'admin-inbox':
            getAdminInbox($db);
            break;
        default:
            getInbox($db);
    }
}

function handlePost($db, $action) {
    switch ($action) {
        case 'send':
            sendMessage($db);
            break;
        case 'contact':
            sendContactMessage($db);
            break;
        case 'reply':
            Security::requireAuth();
            replyToMessage($db);
            break;
        default:
            jsonResponse(['error' => 'Invalid action'], 400);
    }
}

function handlePut($db, $action) {
    Security::requireAuth();
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'mark-read':
            markAsRead($db, $data);
            break;
        case 'mark-unread':
            markAsUnread($db, $data);
            break;
        case 'archive':
            archiveMessage($db, $data);
            break;
        default:
            jsonResponse(['error' => 'Invalid action'], 400);
    }
}

function handleDelete($db) {
    Security::requireAuth();
    
    $id = $_GET['id'] ?? null;
    if (!$id) {
        jsonResponse(['error' => 'Message ID required'], 400);
    }
    deleteMessage($db, $id);
}

function getInbox($db) {
    Security::requireAuth();
    
    $userId = Security::getCurrentUserId();
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    
    $status = $_GET['status'] ?? null;
    
    $where = 'recipient_id = :user_id AND status != :archived';
    $params = ['user_id' => $userId, 'archived' => 'archived'];
    
    if ($status) {
        $where .= ' AND status = :status';
        $params['status'] = $status;
    }
    
    $total = $db->count('messages', $where, $params);
    
    $sql = "SELECT m.*, 
            COALESCE(u.name, m.sender_name) as sender_display_name,
            u.profile_pic as sender_profile_pic
            FROM messages m
            LEFT JOIN users u ON m.sender_id = u.id
            WHERE {$where}
            ORDER BY m.created_at DESC
            LIMIT {$limit} OFFSET {$offset}";
    
    $messages = $db->fetchAll($sql, $params);
    
    jsonResponse([
        'success' => true,
        'data' => $messages,
        'pagination' => [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

function getSentMessages($db) {
    Security::requireAuth();
    
    $userId = Security::getCurrentUserId();
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    
    $total = $db->count('messages', 'sender_id = :user_id', ['user_id' => $userId]);
    
    $sql = "SELECT m.*, u.name as recipient_name
            FROM messages m
            LEFT JOIN users u ON m.recipient_id = u.id
            WHERE m.sender_id = :user_id
            ORDER BY m.created_at DESC
            LIMIT {$limit} OFFSET {$offset}";
    
    $messages = $db->fetchAll($sql, ['user_id' => $userId]);
    
    jsonResponse([
        'success' => true,
        'data' => $messages,
        'pagination' => [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

function getMessage($db) {
    Security::requireAuth();
    
    $id = $_GET['id'] ?? null;
    if (!$id) {
        jsonResponse(['error' => 'Message ID required'], 400);
    }
    
    $userId = Security::getCurrentUserId();
    
    $sql = "SELECT m.*, 
            COALESCE(sender.name, m.sender_name) as sender_display_name,
            sender.profile_pic as sender_profile_pic,
            recipient.name as recipient_name
            FROM messages m
            LEFT JOIN users sender ON m.sender_id = sender.id
            LEFT JOIN users recipient ON m.recipient_id = recipient.id
            WHERE m.id = :id AND (m.sender_id = :user_id OR m.recipient_id = :user_id2)";
    
    $message = $db->fetchOne($sql, ['id' => $id, 'user_id' => $userId, 'user_id2' => $userId]);
    
    if (!$message) {
        jsonResponse(['error' => 'Message not found'], 404);
    }
    
    // Mark as read if recipient
    if ($message['recipient_id'] == $userId && $message['status'] === 'unread') {
        $db->update('messages', [
            'status' => 'read',
            'read_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $id]);
        $message['status'] = 'read';
    }
    
    // Get replies
    $replies = $db->fetchAll(
        "SELECT m.*, COALESCE(u.name, m.sender_name) as sender_display_name
         FROM messages m
         LEFT JOIN users u ON m.sender_id = u.id
         WHERE m.parent_id = :parent_id
         ORDER BY m.created_at ASC",
        ['parent_id' => $id]
    );
    
    $message['replies'] = $replies;
    
    jsonResponse(['success' => true, 'data' => $message]);
}

function getUnreadCount($db) {
    Security::requireAuth();
    
    $userId = Security::getCurrentUserId();
    $count = $db->count('messages', 'recipient_id = :user_id AND status = :status', 
                        ['user_id' => $userId, 'status' => 'unread']);
    
    jsonResponse(['success' => true, 'count' => $count]);
}

function getAdminInbox($db) {
    Security::requireAdmin();
    
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    
    $status = $_GET['status'] ?? null;
    
    // Get messages sent to admins (recipient_id is null means to all admins)
    $where = '(recipient_id IS NULL OR recipient_id IN (SELECT id FROM users WHERE role = :admin_role))';
    $params = ['admin_role' => 'admin'];
    
    if ($status) {
        $where .= ' AND status = :status';
        $params['status'] = $status;
    }
    
    $total = $db->count('messages', $where, $params);
    
    $sql = "SELECT m.*, 
            COALESCE(u.name, m.sender_name) as sender_display_name,
            u.email as sender_email_user
            FROM messages m
            LEFT JOIN users u ON m.sender_id = u.id
            WHERE {$where}
            ORDER BY 
                CASE WHEN m.status = 'unread' THEN 0 ELSE 1 END,
                m.created_at DESC
            LIMIT {$limit} OFFSET {$offset}";
    
    $messages = $db->fetchAll($sql, $params);
    
    jsonResponse([
        'success' => true,
        'data' => $messages,
        'pagination' => [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

function sendMessage($db) {
    Security::requireAuth();
    
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    if (empty($data['subject']) || empty($data['message'])) {
        jsonResponse(['error' => 'Subject and message are required'], 400);
    }
    
    $recipientId = $data['recipient_id'] ?? null;
    
    // If no recipient specified, send to admins
    if (!$recipientId) {
        $recipientId = null; // Will be handled as message to all admins
    }
    
    $messageId = $db->insert('messages', [
        'sender_id' => Security::getCurrentUserId(),
        'recipient_id' => $recipientId,
        'subject' => Security::sanitize($data['subject']),
        'message' => Security::cleanHtml($data['message']),
        'status' => 'unread',
        'is_from_guest' => 0
    ]);
    
    // Create notification for recipient(s)
    if ($recipientId) {
        $db->insert('notifications', [
            'user_id' => $recipientId,
            'type' => 'message',
            'title' => 'New Message',
            'content' => 'You have received a new message: ' . substr($data['subject'], 0, 50),
            'link' => '/member/messages.php?id=' . $messageId
        ]);
    }
    
    jsonResponse([
        'success' => true,
        'message' => 'Message sent successfully',
        'data' => ['id' => $messageId]
    ], 201);
}

function sendContactMessage($db) {
    // Public endpoint for contact form
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    // Validate CSRF for form submission
    if (isset($data['csrf_token']) && !Security::verifyCSRFToken($data['csrf_token'])) {
        jsonResponse(['error' => 'Invalid request'], 403);
    }
    
    if (empty($data['name']) || empty($data['email']) || empty($data['subject']) || empty($data['message'])) {
        jsonResponse(['error' => 'All fields are required'], 400);
    }
    
    if (!Security::validateEmail($data['email'])) {
        jsonResponse(['error' => 'Invalid email address'], 400);
    }
    
    $messageId = $db->insert('messages', [
        'sender_id' => Security::isLoggedIn() ? Security::getCurrentUserId() : null,
        'sender_name' => Security::sanitize($data['name']),
        'sender_email' => Security::sanitize($data['email']),
        'recipient_id' => null, // To admins
        'subject' => Security::sanitize($data['subject']),
        'message' => Security::cleanHtml($data['message']),
        'status' => 'unread',
        'is_from_guest' => Security::isLoggedIn() ? 0 : 1
    ]);
    
    jsonResponse([
        'success' => true,
        'message' => 'Message sent successfully. We will get back to you soon!'
    ], 201);
}

function replyToMessage($db) {
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    if (empty($data['parent_id']) || empty($data['message'])) {
        jsonResponse(['error' => 'Parent message ID and reply message are required'], 400);
    }
    
    $parentMessage = $db->fetchOne("SELECT * FROM messages WHERE id = :id", ['id' => $data['parent_id']]);
    
    if (!$parentMessage) {
        jsonResponse(['error' => 'Parent message not found'], 404);
    }
    
    $userId = Security::getCurrentUserId();
    
    // Determine recipient (the other party in the conversation)
    $recipientId = $parentMessage['sender_id'] == $userId 
        ? $parentMessage['recipient_id'] 
        : $parentMessage['sender_id'];
    
    $messageId = $db->insert('messages', [
        'sender_id' => $userId,
        'recipient_id' => $recipientId,
        'subject' => 'Re: ' . $parentMessage['subject'],
        'message' => Security::cleanHtml($data['message']),
        'parent_id' => $parentMessage['id'],
        'status' => 'unread',
        'is_from_guest' => 0
    ]);
    
    // Update parent message status
    $db->update('messages', ['status' => 'replied'], 'id = :id', ['id' => $parentMessage['id']]);
    
    // Notify recipient
    if ($recipientId) {
        $db->insert('notifications', [
            'user_id' => $recipientId,
            'type' => 'message',
            'title' => 'New Reply',
            'content' => 'You have received a reply to: ' . substr($parentMessage['subject'], 0, 50),
            'link' => '/member/messages.php?id=' . $parentMessage['id']
        ]);
    }
    
    jsonResponse([
        'success' => true,
        'message' => 'Reply sent successfully',
        'data' => ['id' => $messageId]
    ], 201);
}

function markAsRead($db, $data) {
    $id = $data['id'] ?? null;
    if (!$id) {
        jsonResponse(['error' => 'Message ID required'], 400);
    }
    
    $userId = Security::getCurrentUserId();
    
    $db->update('messages', [
        'status' => 'read',
        'read_at' => date('Y-m-d H:i:s')
    ], 'id = :id AND recipient_id = :user_id', ['id' => $id, 'user_id' => $userId]);
    
    jsonResponse(['success' => true, 'message' => 'Message marked as read']);
}

function markAsUnread($db, $data) {
    $id = $data['id'] ?? null;
    if (!$id) {
        jsonResponse(['error' => 'Message ID required'], 400);
    }
    
    $userId = Security::getCurrentUserId();
    
    $db->update('messages', [
        'status' => 'unread',
        'read_at' => null
    ], 'id = :id AND recipient_id = :user_id', ['id' => $id, 'user_id' => $userId]);
    
    jsonResponse(['success' => true, 'message' => 'Message marked as unread']);
}

function archiveMessage($db, $data) {
    $id = $data['id'] ?? null;
    if (!$id) {
        jsonResponse(['error' => 'Message ID required'], 400);
    }
    
    $userId = Security::getCurrentUserId();
    
    $db->update('messages', ['status' => 'archived'], 
                'id = :id AND (sender_id = :user_id OR recipient_id = :user_id2)', 
                ['id' => $id, 'user_id' => $userId, 'user_id2' => $userId]);
    
    jsonResponse(['success' => true, 'message' => 'Message archived']);
}

function deleteMessage($db, $id) {
    $userId = Security::getCurrentUserId();
    
    $message = $db->fetchOne(
        "SELECT * FROM messages WHERE id = :id AND (sender_id = :user_id OR recipient_id = :user_id2)",
        ['id' => $id, 'user_id' => $userId, 'user_id2' => $userId]
    );
    
    if (!$message) {
        jsonResponse(['error' => 'Message not found'], 404);
    }
    
    // Soft delete or hard delete based on your preference
    $db->delete('messages', 'id = :id', ['id' => $id]);
    
    jsonResponse(['success' => true, 'message' => 'Message deleted successfully']);
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}
