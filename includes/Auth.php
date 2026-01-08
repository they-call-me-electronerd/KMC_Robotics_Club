<?php
/**
 * KMC Robotics Club - Authentication Class
 * Handles user login, registration, and session management
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Security.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Register a new user
     */
    public function register(array $data): array {
        $response = ['success' => false, 'message' => '', 'errors' => []];
        
        // Validate required fields
        $required = ['name', 'email', 'password', 'confirm_password'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $response['errors'][$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }
        
        if (!empty($response['errors'])) {
            $response['message'] = 'Please fill in all required fields';
            return $response;
        }
        
        // Sanitize inputs
        $name = Security::sanitize($data['name']);
        $email = strtolower(trim($data['email']));
        $password = $data['password'];
        
        // Validate email
        if (!Security::validateEmail($email)) {
            $response['errors']['email'] = 'Invalid email address';
            $response['message'] = 'Please provide a valid email address';
            return $response;
        }
        
        // Check if email exists
        $existingUser = $this->db->fetchOne(
            "SELECT id FROM users WHERE email = :email",
            ['email' => $email]
        );
        
        if ($existingUser) {
            $response['errors']['email'] = 'Email address already registered';
            $response['message'] = 'This email is already registered. Please login or use a different email.';
            return $response;
        }
        
        // Validate password
        $passwordErrors = Security::validatePassword($password);
        if (!empty($passwordErrors)) {
            $response['errors']['password'] = implode('. ', $passwordErrors);
            $response['message'] = 'Password does not meet requirements';
            return $response;
        }
        
        // Confirm password match
        if ($password !== $data['confirm_password']) {
            $response['errors']['confirm_password'] = 'Passwords do not match';
            $response['message'] = 'Password confirmation does not match';
            return $response;
        }
        
        // Hash password
        $passwordHash = Security::hashPassword($password);
        
        // Generate verification token
        $verificationToken = Security::generateToken();
        
        // Insert user
        try {
            $userId = $this->db->insert('users', [
                'name' => $name,
                'email' => $email,
                'password_hash' => $passwordHash,
                'role' => 'member',
                'status' => 'pending',
                'verification_token' => $verificationToken,
                'phone' => Security::sanitize($data['phone'] ?? ''),
                'student_id' => Security::sanitize($data['student_id'] ?? ''),
                'department' => Security::sanitize($data['department'] ?? ''),
                'year_of_study' => isset($data['year_of_study']) ? (int)$data['year_of_study'] : null
            ]);
            
            // Log activity
            $this->logActivity(null, 'user_registered', 'users', $userId, [
                'email' => $email,
                'name' => $name
            ]);
            
            $response['success'] = true;
            $response['message'] = 'Registration successful! Please check your email to verify your account.';
            $response['user_id'] = $userId;
            $response['verification_token'] = $verificationToken;
            
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            $response['message'] = 'Registration failed. Please try again later.';
        }
        
        return $response;
    }
    
    /**
     * Login user
     */
    public function login(string $email, string $password, bool $remember = false): array {
        $response = ['success' => false, 'message' => ''];
        
        $email = strtolower(trim($email));
        
        // Check brute force
        if (!Security::checkLoginAttempts($email)) {
            $response['message'] = 'Too many login attempts. Please try again in ' . (LOGIN_LOCKOUT_TIME / 60) . ' minutes.';
            return $response;
        }
        
        // Validate inputs
        if (empty($email) || empty($password)) {
            $response['message'] = 'Email and password are required';
            return $response;
        }
        
        // Get user
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE email = :email",
            ['email' => $email]
        );
        
        if (!$user) {
            Security::recordFailedLogin($email);
            $response['message'] = 'Invalid email or password';
            return $response;
        }
        
        // Check if account is active
        if ($user['status'] === 'inactive') {
            $response['message'] = 'Your account has been deactivated. Please contact admin.';
            return $response;
        }
        
        // Verify password
        if (!Security::verifyPassword($password, $user['password_hash'])) {
            Security::recordFailedLogin($email);
            $response['message'] = 'Invalid email or password';
            return $response;
        }
        
        // Check if password needs rehash
        if (Security::needsRehash($user['password_hash'])) {
            $newHash = Security::hashPassword($password);
            $this->db->update('users', ['password_hash' => $newHash], 'id = :id', ['id' => $user['id']]);
        }
        
        // Reset login attempts
        Security::resetLoginAttempts($email);
        
        // Regenerate session ID
        session_regenerate_id(true);
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_profile_pic'] = $user['profile_pic'];
        $_SESSION['login_time'] = time();
        
        // Update last login
        $this->db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $user['id']]);
        
        // Log activity
        $this->logActivity($user['id'], 'user_login', 'users', $user['id']);
        
        $response['success'] = true;
        $response['message'] = 'Login successful';
        $response['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ];
        
        return $response;
    }
    
    /**
     * Logout user
     */
    public function logout(): void {
        $userId = Security::getCurrentUserId();
        if ($userId) {
            $this->logActivity($userId, 'user_logout', 'users', $userId);
        }
        Security::logout();
    }
    
    /**
     * Get current user data
     */
    public function getCurrentUser(): ?array {
        if (!Security::isLoggedIn()) {
            return null;
        }
        
        $userId = Security::getCurrentUserId();
        return $this->db->fetchOne(
            "SELECT id, name, email, role, profile_pic, phone, student_id, department, 
                    year_of_study, bio, skills, linkedin, github, status, created_at, last_login
             FROM users WHERE id = :id",
            ['id' => $userId]
        );
    }
    
    /**
     * Update user profile
     */
    public function updateProfile(int $userId, array $data): array {
        $response = ['success' => false, 'message' => ''];
        
        $allowedFields = ['name', 'phone', 'bio', 'skills', 'linkedin', 'github', 'department', 'year_of_study'];
        $updateData = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = Security::sanitize($data[$field]);
            }
        }
        
        if (empty($updateData)) {
            $response['message'] = 'No data to update';
            return $response;
        }
        
        try {
            $this->db->update('users', $updateData, 'id = :id', ['id' => $userId]);
            
            // Update session if name changed
            if (isset($updateData['name'])) {
                $_SESSION['user_name'] = $updateData['name'];
            }
            
            $this->logActivity($userId, 'profile_updated', 'users', $userId);
            
            $response['success'] = true;
            $response['message'] = 'Profile updated successfully';
            
        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            $response['message'] = 'Failed to update profile';
        }
        
        return $response;
    }
    
    /**
     * Change password
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): array {
        $response = ['success' => false, 'message' => ''];
        
        // Get user
        $user = $this->db->fetchOne("SELECT password_hash FROM users WHERE id = :id", ['id' => $userId]);
        
        if (!$user) {
            $response['message'] = 'User not found';
            return $response;
        }
        
        // Verify current password
        if (!Security::verifyPassword($currentPassword, $user['password_hash'])) {
            $response['message'] = 'Current password is incorrect';
            return $response;
        }
        
        // Validate new password
        $passwordErrors = Security::validatePassword($newPassword);
        if (!empty($passwordErrors)) {
            $response['message'] = implode('. ', $passwordErrors);
            return $response;
        }
        
        // Update password
        $newHash = Security::hashPassword($newPassword);
        $this->db->update('users', ['password_hash' => $newHash], 'id = :id', ['id' => $userId]);
        
        $this->logActivity($userId, 'password_changed', 'users', $userId);
        
        $response['success'] = true;
        $response['message'] = 'Password changed successfully';
        
        return $response;
    }
    
    /**
     * Update profile picture
     */
    public function updateProfilePicture(int $userId, array $file): array {
        $response = ['success' => false, 'message' => ''];
        
        // Validate file
        $errors = Security::validateFileUpload($file);
        if (!empty($errors)) {
            $response['message'] = implode('. ', $errors);
            return $response;
        }
        
        // Generate filename
        $filename = Security::generateSafeFilename($file['name']);
        $filepath = UPLOAD_PROFILES . '/' . $filename;
        
        // Get old profile pic to delete
        $user = $this->db->fetchOne("SELECT profile_pic FROM users WHERE id = :id", ['id' => $userId]);
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Delete old profile pic
            if ($user && $user['profile_pic'] && file_exists(UPLOAD_PROFILES . '/' . $user['profile_pic'])) {
                unlink(UPLOAD_PROFILES . '/' . $user['profile_pic']);
            }
            
            // Update database
            $this->db->update('users', ['profile_pic' => $filename], 'id = :id', ['id' => $userId]);
            $_SESSION['user_profile_pic'] = $filename;
            
            $this->logActivity($userId, 'profile_picture_updated', 'users', $userId);
            
            $response['success'] = true;
            $response['message'] = 'Profile picture updated successfully';
            $response['filename'] = $filename;
        } else {
            $response['message'] = 'Failed to upload file';
        }
        
        return $response;
    }
    
    /**
     * Request password reset
     */
    public function requestPasswordReset(string $email): array {
        $response = ['success' => false, 'message' => ''];
        
        $email = strtolower(trim($email));
        
        $user = $this->db->fetchOne("SELECT id, name FROM users WHERE email = :email", ['email' => $email]);
        
        // Always show success to prevent email enumeration
        $response['success'] = true;
        $response['message'] = 'If an account with that email exists, you will receive a password reset link.';
        
        if ($user) {
            $token = Security::generateToken();
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $this->db->update('users', [
                'reset_token' => $token,
                'reset_token_expires' => $expires
            ], 'id = :id', ['id' => $user['id']]);
            
            $response['reset_token'] = $token; // In production, send this via email
            
            $this->logActivity($user['id'], 'password_reset_requested', 'users', $user['id']);
        }
        
        return $response;
    }
    
    /**
     * Reset password with token
     */
    public function resetPassword(string $token, string $newPassword): array {
        $response = ['success' => false, 'message' => ''];
        
        $user = $this->db->fetchOne(
            "SELECT id FROM users WHERE reset_token = :token AND reset_token_expires > NOW()",
            ['token' => $token]
        );
        
        if (!$user) {
            $response['message'] = 'Invalid or expired reset token';
            return $response;
        }
        
        // Validate new password
        $passwordErrors = Security::validatePassword($newPassword);
        if (!empty($passwordErrors)) {
            $response['message'] = implode('. ', $passwordErrors);
            return $response;
        }
        
        // Update password and clear reset token
        $newHash = Security::hashPassword($newPassword);
        $this->db->update('users', [
            'password_hash' => $newHash,
            'reset_token' => null,
            'reset_token_expires' => null
        ], 'id = :id', ['id' => $user['id']]);
        
        $this->logActivity($user['id'], 'password_reset_completed', 'users', $user['id']);
        
        $response['success'] = true;
        $response['message'] = 'Password reset successfully. You can now login with your new password.';
        
        return $response;
    }
    
    /**
     * Verify email
     */
    public function verifyEmail(string $token): array {
        $response = ['success' => false, 'message' => ''];
        
        $user = $this->db->fetchOne(
            "SELECT id FROM users WHERE verification_token = :token",
            ['token' => $token]
        );
        
        if (!$user) {
            $response['message'] = 'Invalid verification token';
            return $response;
        }
        
        $this->db->update('users', [
            'email_verified' => 1,
            'verification_token' => null,
            'status' => 'active'
        ], 'id = :id', ['id' => $user['id']]);
        
        $this->logActivity($user['id'], 'email_verified', 'users', $user['id']);
        
        $response['success'] = true;
        $response['message'] = 'Email verified successfully. Your account is now active.';
        
        return $response;
    }
    
    /**
     * Log activity
     */
    private function logActivity(?int $userId, string $action, string $entityType, ?int $entityId, array $details = []): void {
        try {
            $this->db->insert('activity_logs', [
                'user_id' => $userId,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'details' => json_encode($details),
                'ip_address' => Security::getClientIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        } catch (Exception $e) {
            error_log("Activity log error: " . $e->getMessage());
        }
    }
}
