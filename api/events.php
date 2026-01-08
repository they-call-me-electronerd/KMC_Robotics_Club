<?php
/**
 * KMC Robotics Club - Events API
 * Endpoints for event management
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
            handleDelete($db, $action);
            break;
        default:
            jsonResponse(['error' => 'Method not allowed'], 405);
    }
} catch (Exception $e) {
    error_log("Events API Error: " . $e->getMessage());
    jsonResponse(['error' => 'Internal server error'], 500);
}

function handleGet($db, $action) {
    switch ($action) {
        case 'list':
            listEvents($db);
            break;
        case 'get':
            getEvent($db);
            break;
        case 'upcoming':
            getUpcomingEvents($db);
            break;
        case 'past':
            getPastEvents($db);
            break;
        case 'featured':
            getFeaturedEvents($db);
            break;
        default:
            listEvents($db);
    }
}

function handlePost($db, $action) {
    Security::requireAdmin();
    
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    switch ($action) {
        case 'create':
            createEvent($db, $data);
            break;
        case 'register':
            registerForEvent($db, $data);
            break;
        default:
            jsonResponse(['error' => 'Invalid action'], 400);
    }
}

function handlePut($db, $action) {
    Security::requireAdmin();
    
    $data = json_decode(file_get_contents('php://input'), true);
    updateEvent($db, $data);
}

function handleDelete($db, $action) {
    Security::requireAdmin();
    
    $id = $_GET['id'] ?? null;
    if (!$id) {
        jsonResponse(['error' => 'Event ID required'], 400);
    }
    deleteEvent($db, $id);
}

function listEvents($db) {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(50, max(1, (int)($_GET['limit'] ?? EVENTS_PER_PAGE)));
    $offset = ($page - 1) * $limit;
    
    $category = $_GET['category'] ?? null;
    $status = $_GET['status'] ?? null;
    
    $where = '1=1';
    $params = [];
    
    if ($category) {
        $where .= ' AND category = :category';
        $params['category'] = $category;
    }
    
    if ($status) {
        $where .= ' AND status = :status';
        $params['status'] = $status;
    }
    
    $total = $db->count('events', $where, $params);
    
    $sql = "SELECT e.*, u.name as created_by_name,
            (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as registration_count
            FROM events e
            LEFT JOIN users u ON e.created_by = u.id
            WHERE {$where}
            ORDER BY e.event_date DESC, e.created_at DESC
            LIMIT {$limit} OFFSET {$offset}";
    
    $events = $db->fetchAll($sql, $params);
    
    // Format events
    foreach ($events as &$event) {
        $event['image_url'] = $event['image_path'] 
            ? APP_URL . '/uploads/events/' . $event['image_path'] 
            : null;
    }
    
    jsonResponse([
        'success' => true,
        'data' => $events,
        'pagination' => [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

function getEvent($db) {
    $id = $_GET['id'] ?? null;
    $slug = $_GET['slug'] ?? null;
    
    if (!$id && !$slug) {
        jsonResponse(['error' => 'Event ID or slug required'], 400);
    }
    
    $where = $id ? 'e.id = :identifier' : 'e.slug = :identifier';
    $identifier = $id ?: $slug;
    
    $sql = "SELECT e.*, u.name as created_by_name,
            (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as registration_count
            FROM events e
            LEFT JOIN users u ON e.created_by = u.id
            WHERE {$where}";
    
    $event = $db->fetchOne($sql, ['identifier' => $identifier]);
    
    if (!$event) {
        jsonResponse(['error' => 'Event not found'], 404);
    }
    
    $event['image_url'] = $event['image_path'] 
        ? APP_URL . '/uploads/events/' . $event['image_path'] 
        : null;
    
    // Check if current user is registered
    if (Security::isLoggedIn()) {
        $registration = $db->fetchOne(
            "SELECT * FROM event_registrations WHERE event_id = :event_id AND user_id = :user_id",
            ['event_id' => $event['id'], 'user_id' => Security::getCurrentUserId()]
        );
        $event['is_registered'] = (bool)$registration;
    }
    
    jsonResponse(['success' => true, 'data' => $event]);
}

function getUpcomingEvents($db) {
    $limit = min(20, max(1, (int)($_GET['limit'] ?? 6)));
    
    $sql = "SELECT e.*, 
            (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as registration_count
            FROM events e
            WHERE e.event_date >= CURDATE() AND e.status = 'upcoming'
            ORDER BY e.event_date ASC
            LIMIT {$limit}";
    
    $events = $db->fetchAll($sql);
    
    foreach ($events as &$event) {
        $event['image_url'] = $event['image_path'] 
            ? APP_URL . '/uploads/events/' . $event['image_path'] 
            : null;
    }
    
    jsonResponse(['success' => true, 'data' => $events]);
}

function getPastEvents($db) {
    $limit = min(20, max(1, (int)($_GET['limit'] ?? 10)));
    
    $sql = "SELECT e.*, 
            (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as registration_count
            FROM events e
            WHERE e.event_date < CURDATE() OR e.status = 'completed'
            ORDER BY e.event_date DESC
            LIMIT {$limit}";
    
    $events = $db->fetchAll($sql);
    
    foreach ($events as &$event) {
        $event['image_url'] = $event['image_path'] 
            ? APP_URL . '/uploads/events/' . $event['image_path'] 
            : null;
    }
    
    jsonResponse(['success' => true, 'data' => $events]);
}

function getFeaturedEvents($db) {
    $sql = "SELECT e.*, 
            (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as registration_count
            FROM events e
            WHERE e.is_featured = 1 AND e.event_date >= CURDATE()
            ORDER BY e.event_date ASC
            LIMIT 3";
    
    $events = $db->fetchAll($sql);
    
    foreach ($events as &$event) {
        $event['image_url'] = $event['image_path'] 
            ? APP_URL . '/uploads/events/' . $event['image_path'] 
            : null;
    }
    
    jsonResponse(['success' => true, 'data' => $events]);
}

function createEvent($db, $data) {
    $required = ['title', 'description', 'event_date'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            jsonResponse(['error' => ucfirst($field) . ' is required'], 400);
        }
    }
    
    // Generate slug
    $slug = createSlug($data['title']);
    $originalSlug = $slug;
    $counter = 1;
    
    while ($db->fetchOne("SELECT id FROM events WHERE slug = :slug", ['slug' => $slug])) {
        $slug = $originalSlug . '-' . $counter;
        $counter++;
    }
    
    // Handle image upload
    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $errors = Security::validateFileUpload($_FILES['image']);
        if (empty($errors)) {
            $imagePath = Security::generateSafeFilename($_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'], UPLOAD_EVENTS . '/' . $imagePath);
        }
    }
    
    $eventId = $db->insert('events', [
        'title' => Security::sanitize($data['title']),
        'slug' => $slug,
        'description' => Security::cleanHtml($data['description']),
        'short_description' => Security::sanitize($data['short_description'] ?? ''),
        'event_date' => $data['event_date'],
        'start_time' => $data['start_time'] ?? null,
        'end_time' => $data['end_time'] ?? null,
        'location' => Security::sanitize($data['location'] ?? ''),
        'image_path' => $imagePath,
        'category' => $data['category'] ?? 'other',
        'max_participants' => !empty($data['max_participants']) ? (int)$data['max_participants'] : null,
        'registration_deadline' => $data['registration_deadline'] ?? null,
        'is_featured' => !empty($data['is_featured']) ? 1 : 0,
        'status' => $data['status'] ?? 'upcoming',
        'created_by' => Security::getCurrentUserId()
    ]);
    
    jsonResponse([
        'success' => true,
        'message' => 'Event created successfully',
        'data' => ['id' => $eventId, 'slug' => $slug]
    ], 201);
}

function updateEvent($db, $data) {
    if (empty($data['id'])) {
        jsonResponse(['error' => 'Event ID required'], 400);
    }
    
    $event = $db->fetchOne("SELECT * FROM events WHERE id = :id", ['id' => $data['id']]);
    if (!$event) {
        jsonResponse(['error' => 'Event not found'], 404);
    }
    
    $updateData = [];
    $allowedFields = ['title', 'description', 'short_description', 'event_date', 
                      'start_time', 'end_time', 'location', 'category', 
                      'max_participants', 'registration_deadline', 'is_featured', 'status'];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            if (in_array($field, ['title', 'short_description', 'location'])) {
                $updateData[$field] = Security::sanitize($data[$field]);
            } elseif ($field === 'description') {
                $updateData[$field] = Security::cleanHtml($data[$field]);
            } else {
                $updateData[$field] = $data[$field];
            }
        }
    }
    
    if (!empty($updateData)) {
        $db->update('events', $updateData, 'id = :id', ['id' => $data['id']]);
    }
    
    jsonResponse(['success' => true, 'message' => 'Event updated successfully']);
}

function deleteEvent($db, $id) {
    $event = $db->fetchOne("SELECT image_path FROM events WHERE id = :id", ['id' => $id]);
    
    if (!$event) {
        jsonResponse(['error' => 'Event not found'], 404);
    }
    
    // Delete image file
    if ($event['image_path'] && file_exists(UPLOAD_EVENTS . '/' . $event['image_path'])) {
        unlink(UPLOAD_EVENTS . '/' . $event['image_path']);
    }
    
    $db->delete('events', 'id = :id', ['id' => $id]);
    
    jsonResponse(['success' => true, 'message' => 'Event deleted successfully']);
}

function registerForEvent($db, $data) {
    Security::requireAuth();
    
    $eventId = $data['event_id'] ?? null;
    if (!$eventId) {
        jsonResponse(['error' => 'Event ID required'], 400);
    }
    
    $event = $db->fetchOne("SELECT * FROM events WHERE id = :id", ['id' => $eventId]);
    if (!$event) {
        jsonResponse(['error' => 'Event not found'], 404);
    }
    
    // Check if already registered
    $existing = $db->fetchOne(
        "SELECT id FROM event_registrations WHERE event_id = :event_id AND user_id = :user_id",
        ['event_id' => $eventId, 'user_id' => Security::getCurrentUserId()]
    );
    
    if ($existing) {
        jsonResponse(['error' => 'Already registered for this event'], 400);
    }
    
    // Check max participants
    if ($event['max_participants']) {
        $count = $db->count('event_registrations', 'event_id = :event_id', ['event_id' => $eventId]);
        if ($count >= $event['max_participants']) {
            jsonResponse(['error' => 'Event is full'], 400);
        }
    }
    
    // Check registration deadline
    if ($event['registration_deadline'] && strtotime($event['registration_deadline']) < time()) {
        jsonResponse(['error' => 'Registration deadline has passed'], 400);
    }
    
    $db->insert('event_registrations', [
        'event_id' => $eventId,
        'user_id' => Security::getCurrentUserId()
    ]);
    
    jsonResponse(['success' => true, 'message' => 'Successfully registered for event']);
}

function createSlug($string) {
    $slug = strtolower(trim($string));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    return trim($slug, '-');
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}
