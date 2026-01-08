<?php
/**
 * KMC Robotics Club - Security Utilities
 * Handles authentication, validation, and security functions
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Database.php';

class Security {
    
    /**
     * Start secure session
     */
    public static function startSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', SESSION_HTTPONLY);
            ini_set('session.cookie_secure', SESSION_SECURE);
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_samesite', 'Strict');
            
            session_name(SESSION_NAME);
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path' => SESSION_PATH,
                'secure' => SESSION_SECURE,
                'httponly' => SESSION_HTTPONLY,
                'samesite' => 'Strict'
            ]);
            
            session_start();
            
            // Regenerate session ID periodically
            if (!isset($_SESSION['created'])) {
                $_SESSION['created'] = time();
            } else if (time() - $_SESSION['created'] > 1800) {
                // Regenerate session ID every 30 minutes
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
        }
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken(): string {
        if (empty($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }
    
    /**
     * Verify CSRF token
     */
    public static function verifyCSRFToken(string $token): bool {
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            return false;
        }
        return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }
    
    /**
     * Hash password using bcrypt/argon2
     */
    public static function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }
    
    /**
     * Verify password
     */
    public static function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }
    
    /**
     * Check if password needs rehashing
     */
    public static function needsRehash(string $hash): bool {
        return password_needs_rehash($hash, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }
    
    /**
     * Sanitize input for XSS prevention
     */
    public static function sanitize($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Clean HTML - allow safe tags
     */
    public static function cleanHtml(string $input): string {
        $allowedTags = '<p><br><strong><em><u><ul><ol><li><a><h1><h2><h3><h4><h5><h6>';
        return strip_tags($input, $allowedTags);
    }
    
    /**
     * Validate email
     */
    public static function validateEmail(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate password strength
     */
    public static function validatePassword(string $password): array {
        $errors = [];
        
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            $errors[] = "Password must be at least " . PASSWORD_MIN_LENGTH . " characters long";
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = "Password must contain at least one special character";
        }
        
        return $errors;
    }
    
    /**
     * Generate random token
     */
    public static function generateToken(int $length = 32): string {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Check login attempts (brute force protection)
     */
    public static function checkLoginAttempts(string $email): bool {
        $sessionKey = 'login_attempts_' . md5($email);
        
        if (!isset($_SESSION[$sessionKey])) {
            $_SESSION[$sessionKey] = ['count' => 0, 'time' => time()];
        }
        
        $attempts = &$_SESSION[$sessionKey];
        
        // Reset if lockout period has passed
        if (time() - $attempts['time'] > LOGIN_LOCKOUT_TIME) {
            $attempts = ['count' => 0, 'time' => time()];
        }
        
        return $attempts['count'] < MAX_LOGIN_ATTEMPTS;
    }
    
    /**
     * Record failed login attempt
     */
    public static function recordFailedLogin(string $email): void {
        $sessionKey = 'login_attempts_' . md5($email);
        
        if (!isset($_SESSION[$sessionKey])) {
            $_SESSION[$sessionKey] = ['count' => 0, 'time' => time()];
        }
        
        $_SESSION[$sessionKey]['count']++;
    }
    
    /**
     * Reset login attempts
     */
    public static function resetLoginAttempts(string $email): void {
        $sessionKey = 'login_attempts_' . md5($email);
        unset($_SESSION[$sessionKey]);
    }
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn(): bool {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Check if user is admin
     */
    public static function isAdmin(): bool {
        return self::isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
    
    /**
     * Get current user ID
     */
    public static function getCurrentUserId(): ?int {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get current user role
     */
    public static function getCurrentUserRole(): ?string {
        return $_SESSION['user_role'] ?? null;
    }
    
    /**
     * Require authentication
     */
    public static function requireAuth(): void {
        if (!self::isLoggedIn()) {
            header('Location: ' . APP_URL . '/auth/login.php');
            exit;
        }
    }
    
    /**
     * Require admin access
     */
    public static function requireAdmin(): void {
        if (!self::isAdmin()) {
            header('HTTP/1.1 403 Forbidden');
            if (self::isLoggedIn()) {
                header('Location: ' . APP_URL . '/member/dashboard.php?error=access_denied');
            } else {
                header('Location: ' . APP_URL . '/auth/login.php?redirect=admin');
            }
            exit;
        }
    }
    
    /**
     * Log out user
     */
    public static function logout(): void {
        $_SESSION = [];
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, SESSION_PATH);
        }
        
        session_destroy();
    }
    
    /**
     * Get client IP address
     */
    public static function getClientIP(): string {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 
                   'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Validate file upload
     */
    public static function validateFileUpload(array $file, array $allowedTypes = null, int $maxSize = null): array {
        $errors = [];
        $allowedTypes = $allowedTypes ?? ALLOWED_IMAGE_TYPES;
        $maxSize = $maxSize ?? MAX_FILE_SIZE;
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds server upload limit',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds form upload limit',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
            ];
            $errors[] = $errorMessages[$file['error']] ?? 'Unknown upload error';
            return $errors;
        }
        
        // Check file size
        if ($file['size'] > $maxSize) {
            $errors[] = 'File size exceeds the maximum limit of ' . ($maxSize / 1024 / 1024) . 'MB';
        }
        
        // Check file type
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedTypes)) {
            $errors[] = 'Invalid file type. Allowed types: ' . implode(', ', $allowedTypes);
        }
        
        // Verify MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, ALLOWED_MIME_TYPES)) {
            $errors[] = 'Invalid file content type';
        }
        
        return $errors;
    }
    
    /**
     * Generate safe filename
     */
    public static function generateSafeFilename(string $originalName): string {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $safeName = preg_replace('/[^a-zA-Z0-9]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
        $uniqueId = substr(md5(uniqid(mt_rand(), true)), 0, 8);
        
        return $safeName . '_' . $uniqueId . '.' . $extension;
    }
}

// Initialize session
Security::startSession();
