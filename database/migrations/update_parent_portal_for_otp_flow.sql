-- ============================================================
-- Parent Portal - OTP-Based Authentication Flow
-- Run Date: 2026-01-06
-- ============================================================

-- -------------------------------------------------------------
-- 1. Update parent_accounts table for OTP flow (no password)
-- -------------------------------------------------------------

-- Make email nullable (we're using phone as primary)
ALTER TABLE parent_accounts
    MODIFY COLUMN email VARCHAR(255) NULL,
    MODIFY COLUMN password_hash VARCHAR(255) NULL;

-- Add OTP-related columns
ALTER TABLE parent_accounts
    ADD COLUMN otp_code VARCHAR(10) NULL AFTER phone,
    ADD COLUMN otp_expires_at TIMESTAMP NULL AFTER otp_code,
    ADD COLUMN magic_link_token VARCHAR(100) NULL AFTER otp_expires_at,
    ADD COLUMN magic_link_expires_at TIMESTAMP NULL AFTER magic_link_token,
    ADD COLUMN session_token VARCHAR(100) NULL AFTER magic_link_expires_at,
    ADD COLUMN session_expires_at TIMESTAMP NULL AFTER session_token,
    ADD COLUMN terms_accepted_at TIMESTAMP NULL AFTER language,
    ADD COLUMN terms_accepted_ip VARCHAR(45) NULL AFTER terms_accepted_at;

-- Add index for OTP and magic link lookups
ALTER TABLE parent_accounts
    ADD INDEX idx_otp_code (otp_code, otp_expires_at),
    ADD INDEX idx_magic_link (magic_link_token, magic_link_expires_at),
    ADD INDEX idx_session_token (session_token, session_expires_at);

-- -------------------------------------------------------------
-- 2. Create parent_student_requests table for pending linkages
-- -------------------------------------------------------------

CREATE TABLE IF NOT EXISTS parent_student_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Who is requesting
    parent_account_id BIGINT UNSIGNED NOT NULL,

    -- What they entered (before validation)
    admission_number VARCHAR(50) NOT NULL,
    grade_name VARCHAR(100) NOT NULL COMMENT 'Grade/class name entered by parent',

    -- If matched to actual student
    student_id BIGINT UNSIGNED NULL COMMENT 'Matched student ID (null until validated)',

    -- Request status
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',

    -- Admin action
    reviewed_by BIGINT UNSIGNED NULL COMMENT 'Admin user who reviewed',
    reviewed_at TIMESTAMP NULL,
    rejection_reason TEXT NULL,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Indexes
    INDEX idx_parent_account (parent_account_id),
    INDEX idx_student (student_id),
    INDEX idx_status (status),
    INDEX idx_admission_number (admission_number),

    -- Foreign keys
    CONSTRAINT fk_psr_parent_account FOREIGN KEY (parent_account_id)
        REFERENCES parent_accounts(id) ON DELETE CASCADE,
    CONSTRAINT fk_psr_student FOREIGN KEY (student_id)
        REFERENCES students(id) ON DELETE SET NULL,
    CONSTRAINT fk_psr_reviewer FOREIGN KEY (reviewed_by)
        REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 3. Update student_guardians to support pending status
-- -------------------------------------------------------------

-- Add status column if not exists and source tracking
ALTER TABLE student_guardians
    ADD COLUMN IF NOT EXISTS link_status ENUM('active', 'pending', 'rejected') DEFAULT 'active' AFTER can_pickup,
    ADD COLUMN IF NOT EXISTS linked_via ENUM('admin', 'parent_portal', 'admission') DEFAULT 'admin' AFTER link_status,
    ADD COLUMN IF NOT EXISTS request_id BIGINT UNSIGNED NULL AFTER linked_via;

ALTER TABLE student_guardians
    ADD INDEX IF NOT EXISTS idx_link_status (link_status);

-- -------------------------------------------------------------
-- 4. Create SMS log table for audit purposes
-- -------------------------------------------------------------

CREATE TABLE IF NOT EXISTS parent_sms_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_account_id BIGINT UNSIGNED NULL,
    phone_number VARCHAR(20) NOT NULL,
    message_type ENUM('registration_link', 'otp', 'notification') NOT NULL,
    message_content TEXT NULL COMMENT 'Masked for security',
    provider_response TEXT NULL,
    status ENUM('pending', 'sent', 'delivered', 'failed') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_parent_account (parent_account_id),
    INDEX idx_phone (phone_number),
    INDEX idx_type_status (message_type, status),

    CONSTRAINT fk_sms_parent FOREIGN KEY (parent_account_id)
        REFERENCES parent_accounts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 5. Update parent_portal_settings with new options
-- -------------------------------------------------------------

INSERT INTO parent_portal_settings (setting_key, setting_value, setting_type, description) VALUES
('otp_expiry_minutes', '5', 'integer', 'OTP code expiry time in minutes'),
('magic_link_expiry_hours', '1', 'integer', 'Registration magic link expiry in hours'),
('session_expiry_days', '30', 'integer', 'Parent session expiry in days'),
('require_admin_approval', 'true', 'boolean', 'Require admin approval for student linkage'),
('max_students_per_request', '5', 'integer', 'Maximum students that can be linked in one request'),
('sms_provider', 'africas_talking', 'string', 'SMS provider to use')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

-- -------------------------------------------------------------
-- Verification queries
-- -------------------------------------------------------------

-- DESCRIBE parent_accounts;
-- DESCRIBE parent_student_requests;
-- DESCRIBE student_guardians;
-- SELECT * FROM parent_portal_settings;
