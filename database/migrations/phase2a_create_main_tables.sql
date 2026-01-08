-- Parent Portal Phase 2A - Main Tables Migration
-- Created: 2025-01-07
-- Description: Creates notifications and contact directory tables

-- Enhanced notifications table
CREATE TABLE IF NOT EXISTS parent_notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_account_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NULL COMMENT 'NULL = all children',
    grade_id INT UNSIGNED NULL COMMENT 'Grade-level notification',

    notification_type_id INT UNSIGNED NOT NULL,
    notification_scope_id TINYINT UNSIGNED NOT NULL,
    severity_level_id TINYINT UNSIGNED NOT NULL,

    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    icon_code VARCHAR(50) NULL COMMENT 'Emoji or icon class',

    -- Action tracking
    requires_action BOOLEAN DEFAULT FALSE,
    action_type_id TINYINT UNSIGNED NULL,
    action_url VARCHAR(255) NULL,
    action_deadline DATETIME NULL,
    action_completed_at DATETIME NULL,

    -- Status
    read_at DATETIME NULL,
    dismissed_at DATETIME NULL,

    -- Metadata
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (parent_account_id) REFERENCES parent_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (grade_id) REFERENCES grades(id) ON DELETE CASCADE,
    FOREIGN KEY (notification_type_id) REFERENCES notification_types(id),
    FOREIGN KEY (notification_scope_id) REFERENCES notification_scopes(id),
    FOREIGN KEY (severity_level_id) REFERENCES notification_severity_levels(id),
    FOREIGN KEY (action_type_id) REFERENCES notification_action_types(id),

    INDEX idx_parent_unread (parent_account_id, read_at),
    INDEX idx_action_deadline (action_deadline, action_completed_at),
    INDEX idx_created_at (created_at),
    INDEX idx_severity (severity_level_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- School contact directory
CREATE TABLE IF NOT EXISTS school_contacts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id INT UNSIGNED NOT NULL,

    contact_type_id INT UNSIGNED NOT NULL,
    department_name VARCHAR(100) NOT NULL,

    contact_person VARCHAR(255) NULL,
    phone VARCHAR(20) NULL,
    email VARCHAR(255) NULL,
    office_location VARCHAR(255) NULL,
    available_hours VARCHAR(100) NULL,

    is_emergency BOOLEAN DEFAULT FALSE,
    is_24_7 BOOLEAN DEFAULT FALSE,

    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (contact_type_id) REFERENCES contact_types(id),

    INDEX idx_school_active (school_id, is_active, display_order),
    INDEX idx_emergency (is_emergency)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
