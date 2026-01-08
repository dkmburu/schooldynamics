-- Parent Portal Database Schema
-- External user authentication for parents/guardians
-- Run Date: 2026-01-05

-- ============================================================
-- PARENT ACCOUNTS TABLE
-- Links to existing guardians table
-- ============================================================

CREATE TABLE IF NOT EXISTS parent_accounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    guardian_id BIGINT UNSIGNED NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('pending', 'active', 'suspended') DEFAULT 'pending',

    -- Email verification
    email_verified_at TIMESTAMP NULL,
    email_verification_token VARCHAR(100) NULL,
    email_verification_expires TIMESTAMP NULL,

    -- Password reset
    password_reset_token VARCHAR(100) NULL,
    password_reset_expires TIMESTAMP NULL,

    -- Security
    last_login_at TIMESTAMP NULL,
    last_login_ip VARCHAR(45) NULL,
    failed_login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,

    -- Preferences
    notification_preferences JSON NULL,
    language VARCHAR(10) DEFAULT 'en',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_parent_email (email),
    UNIQUE KEY uk_parent_guardian (guardian_id),
    INDEX idx_parent_status (status),
    INDEX idx_parent_phone (phone),

    CONSTRAINT fk_parent_guardian FOREIGN KEY (guardian_id)
        REFERENCES guardians(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PARENT SESSIONS TABLE (for token-based mobile auth)
-- ============================================================

CREATE TABLE IF NOT EXISTS parent_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_account_id BIGINT UNSIGNED NOT NULL,
    token VARCHAR(255) NOT NULL,
    device_type VARCHAR(50) NULL, -- 'web', 'android', 'ios'
    device_name VARCHAR(100) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    last_activity_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uk_session_token (token),
    INDEX idx_session_parent (parent_account_id),
    INDEX idx_session_expires (expires_at),

    CONSTRAINT fk_session_parent FOREIGN KEY (parent_account_id)
        REFERENCES parent_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PARENT NOTIFICATIONS TABLE
-- ============================================================

CREATE TABLE IF NOT EXISTS parent_notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_account_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(50) NOT NULL, -- 'fee_reminder', 'grade_posted', 'attendance', 'announcement'
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    data JSON NULL, -- Additional structured data
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_notif_parent (parent_account_id),
    INDEX idx_notif_read (parent_account_id, read_at),
    INDEX idx_notif_type (type),

    CONSTRAINT fk_notif_parent FOREIGN KEY (parent_account_id)
        REFERENCES parent_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PARENT PORTAL SETTINGS (per tenant)
-- ============================================================

CREATE TABLE IF NOT EXISTS parent_portal_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT NULL,
    setting_type ENUM('string', 'boolean', 'integer', 'json') DEFAULT 'string',
    description VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_portal_setting (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default settings
INSERT INTO parent_portal_settings (setting_key, setting_value, setting_type, description) VALUES
('portal_enabled', 'true', 'boolean', 'Enable/disable parent portal access'),
('self_registration', 'true', 'boolean', 'Allow parents to self-register'),
('require_email_verification', 'true', 'boolean', 'Require email verification before access'),
('show_grades', 'true', 'boolean', 'Allow parents to view student grades'),
('show_attendance', 'true', 'boolean', 'Allow parents to view attendance records'),
('show_fees', 'true', 'boolean', 'Allow parents to view fee statements'),
('show_timetable', 'true', 'boolean', 'Allow parents to view class timetable'),
('allow_online_payment', 'false', 'boolean', 'Enable online fee payment'),
('session_timeout_minutes', '60', 'integer', 'Session timeout in minutes')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

-- ============================================================
-- VERIFICATION QUERIES
-- ============================================================

-- SELECT * FROM parent_accounts;
-- SELECT * FROM parent_portal_settings;
