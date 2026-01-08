-- Parent Portal Phase 2A - Update parent_notifications table
-- Created: 2025-01-07
-- Description: Drop old parent_notifications table and recreate with new structure

-- Drop the old table (backup data first if needed in production)
DROP TABLE IF EXISTS parent_notifications;

-- Recreate with new structure
CREATE TABLE parent_notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_account_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NULL COMMENT 'Specific student this notification is about',
    grade_id INT UNSIGNED NULL COMMENT 'Grade/class level this notification is for',
    notification_type_id INT UNSIGNED NOT NULL,
    notification_scope_id TINYINT UNSIGNED NOT NULL,
    severity_level_id TINYINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    icon_code VARCHAR(50) NULL COMMENT 'Optional custom icon override',
    requires_action BOOLEAN DEFAULT FALSE,
    action_type_id TINYINT UNSIGNED NULL,
    action_url VARCHAR(255) NULL COMMENT 'URL for action button',
    action_deadline DATETIME NULL COMMENT 'Deadline for action',
    action_completed_at DATETIME NULL COMMENT 'When action was completed',
    read_at DATETIME NULL COMMENT 'When notification was read',
    dismissed_at DATETIME NULL COMMENT 'When notification was dismissed',
    created_by INT UNSIGNED NULL COMMENT 'User ID who created this notification',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_parent (parent_account_id),
    INDEX idx_student (student_id),
    INDEX idx_grade (grade_id),
    INDEX idx_type (notification_type_id),
    INDEX idx_scope (notification_scope_id),
    INDEX idx_severity (severity_level_id),
    INDEX idx_action_type (action_type_id),
    INDEX idx_read (read_at),
    INDEX idx_dismissed (dismissed_at),
    INDEX idx_created (created_at),

    FOREIGN KEY (parent_account_id) REFERENCES parent_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (notification_type_id) REFERENCES notification_types(id),
    FOREIGN KEY (notification_scope_id) REFERENCES notification_scopes(id),
    FOREIGN KEY (severity_level_id) REFERENCES notification_severity_levels(id),
    FOREIGN KEY (action_type_id) REFERENCES notification_action_types(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
