-- ============================================
-- KMC Robotics Club Database Schema
-- MySQL Database Setup Script
-- ============================================

-- Create database
CREATE DATABASE IF NOT EXISTS kmc_robotics_club
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE kmc_robotics_club;

-- ============================================
-- Users Table - Members and Admins
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'member') DEFAULT 'member',
    profile_pic VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    student_id VARCHAR(50) DEFAULT NULL,
    department VARCHAR(100) DEFAULT NULL,
    year_of_study TINYINT DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    skills TEXT DEFAULT NULL,
    linkedin VARCHAR(255) DEFAULT NULL,
    github VARCHAR(255) DEFAULT NULL,
    status ENUM('active', 'inactive', 'pending') DEFAULT 'pending',
    email_verified TINYINT(1) DEFAULT 0,
    verification_token VARCHAR(255) DEFAULT NULL,
    reset_token VARCHAR(255) DEFAULT NULL,
    reset_token_expires DATETIME DEFAULT NULL,
    last_login DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ============================================
-- Events Table
-- ============================================
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NOT NULL,
    short_description VARCHAR(500) DEFAULT NULL,
    event_date DATE NOT NULL,
    start_time TIME DEFAULT NULL,
    end_time TIME DEFAULT NULL,
    location VARCHAR(255) DEFAULT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    category ENUM('workshop', 'competition', 'seminar', 'hackathon', 'meetup', 'other') DEFAULT 'other',
    max_participants INT DEFAULT NULL,
    registration_deadline DATETIME DEFAULT NULL,
    is_featured TINYINT(1) DEFAULT 0,
    status ENUM('upcoming', 'ongoing', 'completed', 'cancelled') DEFAULT 'upcoming',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_event_date (event_date),
    INDEX idx_category (category),
    INDEX idx_status (status),
    INDEX idx_slug (slug)
) ENGINE=InnoDB;

-- ============================================
-- Event Registrations Table (RSVP)
-- ============================================
CREATE TABLE IF NOT EXISTS event_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('registered', 'attended', 'cancelled') DEFAULT 'registered',
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_registration (event_id, user_id),
    INDEX idx_event (event_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- ============================================
-- Team Members Table
-- ============================================
CREATE TABLE IF NOT EXISTS team_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    name VARCHAR(100) NOT NULL,
    role VARCHAR(100) NOT NULL,
    position_order INT DEFAULT 0,
    category ENUM('executive', 'technical', 'creative', 'advisory') DEFAULT 'technical',
    bio TEXT DEFAULT NULL,
    photo_path VARCHAR(255) DEFAULT NULL,
    email VARCHAR(255) DEFAULT NULL,
    linkedin VARCHAR(255) DEFAULT NULL,
    github VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    year_joined YEAR DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_category (category),
    INDEX idx_position (position_order),
    INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- ============================================
-- Gallery Table
-- ============================================
CREATE TABLE IF NOT EXISTS gallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    image_path VARCHAR(255) NOT NULL,
    thumbnail_path VARCHAR(255) DEFAULT NULL,
    category ENUM('projects', 'events', 'workshops', 'competitions', 'team', 'other') DEFAULT 'other',
    event_id INT DEFAULT NULL,
    uploaded_by INT NOT NULL,
    is_approved TINYINT(1) DEFAULT 0,
    is_featured TINYINT(1) DEFAULT 0,
    view_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_category (category),
    INDEX idx_approved (is_approved),
    INDEX idx_featured (is_featured)
) ENGINE=InnoDB;

-- ============================================
-- Messages Table
-- ============================================
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT DEFAULT NULL,
    sender_name VARCHAR(100) DEFAULT NULL,
    sender_email VARCHAR(255) DEFAULT NULL,
    recipient_id INT DEFAULT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    parent_id INT DEFAULT NULL,
    status ENUM('unread', 'read', 'replied', 'archived') DEFAULT 'unread',
    is_from_guest TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME DEFAULT NULL,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (parent_id) REFERENCES messages(id) ON DELETE SET NULL,
    INDEX idx_sender (sender_id),
    INDEX idx_recipient (recipient_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ============================================
-- Notifications Table
-- ============================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('event', 'message', 'system', 'approval') NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT DEFAULT NULL,
    link VARCHAR(255) DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_unread (user_id, is_read)
) ENGINE=InnoDB;

-- ============================================
-- Activity Logs Table
-- ============================================
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT DEFAULT NULL,
    details JSON DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- ============================================
-- Sessions Table (for custom session handling)
-- ============================================
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    payload TEXT NOT NULL,
    last_activity INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB;

-- ============================================
-- Settings Table
-- ============================================
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT DEFAULT NULL,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description VARCHAR(255) DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- Insert Default Admin User
-- Password: Admin@123 (hashed with bcrypt)
-- ============================================
INSERT INTO users (name, email, password_hash, role, status, email_verified) VALUES
('Admin', 'admin@kmcrc.edu.np', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', 1);

-- ============================================
-- Insert Default Settings
-- ============================================
INSERT INTO settings (setting_key, setting_value, setting_type, description) VALUES
('site_name', 'KMC Robotics Club', 'string', 'Website name'),
('site_email', 'contact@kmcrc.edu.np', 'string', 'Contact email'),
('site_phone', '+977-1-4123456', 'string', 'Contact phone'),
('max_upload_size', '5242880', 'number', 'Max upload size in bytes (5MB)'),
('allowed_image_types', '["jpg","jpeg","png","gif","webp"]', 'json', 'Allowed image file types'),
('events_per_page', '10', 'number', 'Events per page'),
('gallery_per_page', '12', 'number', 'Gallery items per page'),
('registration_enabled', 'true', 'boolean', 'Allow new registrations'),
('email_notifications', 'true', 'boolean', 'Enable email notifications');

-- ============================================
-- Insert Sample Team Members
-- ============================================
INSERT INTO team_members (name, role, position_order, category, bio, photo_path, linkedin, github) VALUES
('Rajesh Sharma', 'President', 1, 'executive', 'Computer Engineering student passionate about autonomous systems and AI. Leading the club''s vision for technical excellence.', NULL, '#', '#'),
('Priya Thapa', 'Vice President', 2, 'executive', 'Electronics enthusiast specializing in embedded systems. Coordinates technical workshops and mentorship programs.', NULL, '#', '#'),
('Anil Gurung', 'Secretary', 3, 'executive', 'Organized and detail-oriented, managing club communications, documentation, and event coordination.', NULL, '#', '#'),
('Suman Maharjan', 'Technical Lead', 4, 'technical', 'Expert in robotics and automation. Leads technical projects and R&D initiatives.', NULL, '#', '#'),
('Maya Rai', 'Creative Director', 5, 'creative', 'Handles all design and creative aspects of the club including branding and media.', NULL, '#', '#');

-- ============================================
-- Insert Sample Events
-- ============================================
INSERT INTO events (title, slug, description, short_description, event_date, start_time, end_time, location, category, max_participants, status, created_by) VALUES
('Introduction to Arduino & Embedded Systems', 'arduino-workshop-2026', 'A hands-on workshop for beginners to learn the fundamentals of microcontrollers, sensors, and basic circuit design. Build your first IoT project!', 'Learn Arduino basics and build your first IoT project.', '2026-01-25', '14:00:00', '17:00:00', 'Computer Lab 3, KMC', 'workshop', 30, 'upcoming', 1),
('RoboWars 2026 - Inter-College Competition', 'robowars-2026', 'The ultimate battle of autonomous robots! Teams compete in maze solving, line following, and sumo wrestling challenges. Cash prizes worth NPR 50,000!', 'Inter-college robotics competition with NPR 50,000 prizes.', '2026-02-15', '09:00:00', '18:00:00', 'Main Auditorium, KMC', 'competition', 100, 'upcoming', 1),
('AI & Machine Learning Workshop', 'ai-ml-workshop-2026', 'Deep dive into artificial intelligence and machine learning concepts with practical exercises using Python and TensorFlow.', 'Practical AI/ML workshop with Python and TensorFlow.', '2026-03-10', '10:00:00', '16:00:00', 'Computer Lab 2, KMC', 'workshop', 25, 'upcoming', 1);

-- ============================================
-- Create Views for Common Queries
-- ============================================

-- View: Upcoming Events
CREATE OR REPLACE VIEW v_upcoming_events AS
SELECT e.*, u.name AS created_by_name,
       (SELECT COUNT(*) FROM event_registrations er WHERE er.event_id = e.id) AS registration_count
FROM events e
LEFT JOIN users u ON e.created_by = u.id
WHERE e.event_date >= CURDATE() AND e.status = 'upcoming'
ORDER BY e.event_date ASC;

-- View: Active Team Members
CREATE OR REPLACE VIEW v_active_team AS
SELECT * FROM team_members
WHERE is_active = 1
ORDER BY position_order ASC, category ASC;

-- View: Approved Gallery
CREATE OR REPLACE VIEW v_approved_gallery AS
SELECT g.*, u.name AS uploaded_by_name
FROM gallery g
LEFT JOIN users u ON g.uploaded_by = u.id
WHERE g.is_approved = 1
ORDER BY g.created_at DESC;

-- View: Unread Messages
CREATE OR REPLACE VIEW v_unread_messages AS
SELECT m.*, u.name AS sender_name_user
FROM messages m
LEFT JOIN users u ON m.sender_id = u.id
WHERE m.status = 'unread'
ORDER BY m.created_at DESC;

-- ============================================
-- Stored Procedures
-- ============================================

DELIMITER //

-- Procedure: Get Dashboard Stats
CREATE PROCEDURE sp_get_dashboard_stats()
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM users WHERE status = 'active') AS total_members,
        (SELECT COUNT(*) FROM users WHERE status = 'pending') AS pending_members,
        (SELECT COUNT(*) FROM events WHERE status = 'upcoming') AS upcoming_events,
        (SELECT COUNT(*) FROM events) AS total_events,
        (SELECT COUNT(*) FROM gallery WHERE is_approved = 1) AS gallery_items,
        (SELECT COUNT(*) FROM gallery WHERE is_approved = 0) AS pending_gallery,
        (SELECT COUNT(*) FROM messages WHERE status = 'unread') AS unread_messages,
        (SELECT COUNT(*) FROM team_members WHERE is_active = 1) AS team_members;
END //

-- Procedure: Log Activity
CREATE PROCEDURE sp_log_activity(
    IN p_user_id INT,
    IN p_action VARCHAR(100),
    IN p_entity_type VARCHAR(50),
    IN p_entity_id INT,
    IN p_details JSON,
    IN p_ip_address VARCHAR(45),
    IN p_user_agent TEXT
)
BEGIN
    INSERT INTO activity_logs (user_id, action, entity_type, entity_id, details, ip_address, user_agent)
    VALUES (p_user_id, p_action, p_entity_type, p_entity_id, p_details, p_ip_address, p_user_agent);
END //

DELIMITER ;

-- ============================================
-- Indexes for Performance Optimization
-- ============================================
-- Additional composite indexes for common queries
CREATE INDEX idx_events_date_status ON events(event_date, status);
CREATE INDEX idx_gallery_approved_category ON gallery(is_approved, category);
CREATE INDEX idx_users_role_status ON users(role, status);
