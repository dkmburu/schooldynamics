-- =====================================================
-- Authorization Requests Table
-- Tracks all authorization/consent requests across the system
-- Reusable for any entity type (applicants, students, employees, etc.)
-- =====================================================

CREATE TABLE IF NOT EXISTS authorization_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Polymorphic Relationship (Reusable across entities)
    entity_type VARCHAR(50) NOT NULL COMMENT 'Entity type: applicant, student, employee, etc.',
    entity_id INT NOT NULL COMMENT 'ID of the entity (applicant_id, student_id, etc.)',

    -- Authorization Details
    request_type VARCHAR(50) NOT NULL COMMENT 'Type: data_consent, photo_consent, medical_consent, etc.',
    request_description TEXT COMMENT 'Optional description of what is being authorized',

    -- Recipient Information
    recipient_name VARCHAR(200) NOT NULL COMMENT 'Guardian/parent name',
    recipient_email VARCHAR(255) COMMENT 'Email address (optional if using SMS only)',
    recipient_phone VARCHAR(20) COMMENT 'Phone number (optional if using email only)',

    -- Security & Verification
    verification_code VARCHAR(10) NOT NULL COMMENT '6-digit code for phone-based authorization',
    token VARCHAR(64) NOT NULL UNIQUE COMMENT 'Secure token for link-based authorization',

    -- Message Content
    message_template VARCHAR(100) COMMENT 'Template code used (links to communication_templates)',
    subject VARCHAR(500) COMMENT 'Actual subject sent',
    message_body TEXT COMMENT 'Actual message body sent (after variable substitution)',
    channels_sent JSON COMMENT 'Array of channels message was sent to: ["sms", "email"]',

    -- Status & Lifecycle
    status ENUM('pending', 'approved', 'rejected', 'expired', 'revoked') DEFAULT 'pending',
    sent_at TIMESTAMP NULL COMMENT 'When the request was sent',
    approved_at TIMESTAMP NULL COMMENT 'When authorization was granted',
    rejected_at TIMESTAMP NULL COMMENT 'When authorization was denied',
    expires_at TIMESTAMP NULL COMMENT 'When this request expires',

    -- Authorization Method Tracking
    authorization_method ENUM('link', 'code_staff', 'code_phone') COMMENT 'How was it authorized?',
    -- link: Guardian clicked link on smartphone
    -- code_staff: Staff entered code on behalf of guardian (guardian called/visited)
    -- code_phone: Guardian entered code via phone system (future feature)

    -- Staff-Assisted Authorization Details
    authorized_by_staff_id BIGINT UNSIGNED COMMENT 'Staff member who entered code on behalf of guardian',
    authorization_location VARCHAR(100) COMMENT 'Where authorization occurred: office, phone_call, etc.',
    parent_contact_method VARCHAR(50) COMMENT 'How parent contacted: phone_call, in_person, etc.',
    staff_notes TEXT COMMENT 'Staff notes about the authorization',

    -- Revocation Support
    revoked_at TIMESTAMP NULL COMMENT 'When authorization was revoked',
    revoked_by BIGINT UNSIGNED COMMENT 'User ID who revoked (parent or admin)',
    revocation_reason TEXT COMMENT 'Why was it revoked',

    -- IP & Security Tracking
    approval_ip VARCHAR(45) COMMENT 'IP address of approval',
    approval_user_agent TEXT COMMENT 'Browser/device info',

    -- Multi-tenant Support
    campus_id INT NOT NULL COMMENT 'Campus this request belongs to',

    -- Audit Fields
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NOT NULL COMMENT 'User ID who created the request',

    -- Indexes for Performance
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_request_type (request_type),
    INDEX idx_status (status),
    INDEX idx_verification_code (verification_code),
    INDEX idx_token (token),
    INDEX idx_campus_id (campus_id),
    INDEX idx_expires_at (expires_at),
    INDEX idx_created_at (created_at),

    -- Foreign Key Constraints
    FOREIGN KEY (authorized_by_staff_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Authorization/consent request tracking (reusable across all entities)';

-- =====================================================
-- Example Queries for Common Operations
-- =====================================================

-- Get all authorization requests for a specific applicant
-- SELECT * FROM authorization_requests WHERE entity_type = 'applicant' AND entity_id = 123;

-- Check if data consent is authorized for an applicant
-- SELECT * FROM authorization_requests
-- WHERE entity_type = 'applicant' AND entity_id = 123
--   AND request_type = 'data_consent' AND status = 'approved'
--   AND (expires_at IS NULL OR expires_at > NOW());

-- Get all pending authorizations for a campus
-- SELECT * FROM authorization_requests WHERE campus_id = 1 AND status = 'pending';

-- Find authorization by verification code
-- SELECT * FROM authorization_requests WHERE verification_code = '123456' AND status = 'pending' AND expires_at > NOW();

-- Get all staff-assisted authorizations
-- SELECT * FROM authorization_requests WHERE authorization_method = 'code_staff';
