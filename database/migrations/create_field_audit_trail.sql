-- ============================================================
-- Field Audit Trail Table
-- Tracks changes to sensitive fields across the system
-- Run Date: 2026-01-06
-- ============================================================

CREATE TABLE IF NOT EXISTS field_audit_trail (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- What was changed
    entity_type VARCHAR(50) NOT NULL COMMENT 'Table name: guardians, students, staff, etc.',
    entity_id BIGINT UNSIGNED NOT NULL COMMENT 'ID of the record that was changed',
    field_name VARCHAR(100) NOT NULL COMMENT 'Name of the field that was changed',

    -- The change itself
    old_value TEXT NULL COMMENT 'Previous value (encrypted for sensitive fields)',
    new_value TEXT NULL COMMENT 'New value (encrypted for sensitive fields)',

    -- Who made the change
    changed_by_user_id BIGINT UNSIGNED NULL COMMENT 'User who made the change',
    changed_by_name VARCHAR(255) NULL COMMENT 'Name of user at time of change',

    -- Context
    change_reason TEXT NULL COMMENT 'Optional reason for the change',
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Indexes for fast lookups
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_field (entity_type, field_name),
    INDEX idx_changed_by (changed_by_user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Define which fields are considered sensitive and should be tracked
-- ============================================================

CREATE TABLE IF NOT EXISTS sensitive_fields (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(50) NOT NULL,
    field_name VARCHAR(100) NOT NULL,
    display_name VARCHAR(255) NOT NULL,
    requires_reason TINYINT(1) DEFAULT 0 COMMENT 'If 1, user must provide reason for change',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE INDEX idx_entity_field (entity_type, field_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed initial sensitive fields
INSERT INTO sensitive_fields (entity_type, field_name, display_name, requires_reason) VALUES
-- Guardian sensitive fields
('guardians', 'id_number', 'National ID / Passport', 1),
('guardians', 'first_name', 'First Name', 0),
('guardians', 'last_name', 'Last Name', 0),

-- Applicant guardian sensitive fields
('applicant_guardians', 'id_number', 'National ID / Passport', 1),
('applicant_guardians', 'first_name', 'First Name', 0),
('applicant_guardians', 'last_name', 'Last Name', 0),

-- Student sensitive fields
('students', 'id_number', 'Student ID Number', 1),
('students', 'birth_certificate_number', 'Birth Certificate Number', 1),
('students', 'first_name', 'First Name', 0),
('students', 'last_name', 'Last Name', 0),
('students', 'date_of_birth', 'Date of Birth', 0),

-- Staff sensitive fields
('staff', 'id_number', 'National ID / Passport', 1),
('staff', 'kra_pin', 'KRA PIN', 1),
('staff', 'nssf_number', 'NSSF Number', 1),
('staff', 'nhif_number', 'NHIF Number', 1),
('staff', 'first_name', 'First Name', 0),
('staff', 'last_name', 'Last Name', 0);

-- ============================================================
-- Verification queries
-- ============================================================

-- DESCRIBE field_audit_trail;
-- DESCRIBE sensitive_fields;
-- SELECT * FROM sensitive_fields ORDER BY entity_type, field_name;
