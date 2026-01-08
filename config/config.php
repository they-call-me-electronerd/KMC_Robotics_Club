<?php
/**
 * KMC Robotics Club - Configuration File
 * Contains all application configuration settings
 */

// Prevent direct access
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Environment (development, staging, production)
define('APP_ENV', 'development');

// Debug mode
define('DEBUG_MODE', APP_ENV === 'development');

// Error reporting based on environment
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Application Settings
define('APP_NAME', 'KMC Robotics Club');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/KMC_Robotics_Club');
define('APP_EMAIL', 'contact@kmcrc.edu.np');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'kmc_robotics_club');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Session Configuration
define('SESSION_NAME', 'KMCRC_SESSION');
define('SESSION_LIFETIME', 7200); // 2 hours
define('SESSION_PATH', '/');
define('SESSION_SECURE', false); // Set to true in production with HTTPS
define('SESSION_HTTPONLY', true);

// Security Configuration
define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// File Upload Configuration
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_MIME_TYPES', [
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp'
]);

// Upload directories
define('UPLOAD_EVENTS', UPLOAD_PATH . '/events');
define('UPLOAD_GALLERY', UPLOAD_PATH . '/gallery');
define('UPLOAD_TEAM', UPLOAD_PATH . '/team');
define('UPLOAD_PROFILES', UPLOAD_PATH . '/profiles');

// Pagination
define('ITEMS_PER_PAGE', 10);
define('GALLERY_PER_PAGE', 12);
define('EVENTS_PER_PAGE', 10);

// Email Configuration (SMTP)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_FROM_EMAIL', 'noreply@kmcrc.edu.np');
define('SMTP_FROM_NAME', 'KMC Robotics Club');

// API Rate Limiting
define('API_RATE_LIMIT', 100); // requests per minute
define('API_RATE_WINDOW', 60); // seconds

// Timezone
date_default_timezone_set('Asia/Kathmandu');

// Create upload directories if they don't exist
$uploadDirs = [UPLOAD_PATH, UPLOAD_EVENTS, UPLOAD_GALLERY, UPLOAD_TEAM, UPLOAD_PROFILES];
foreach ($uploadDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}
