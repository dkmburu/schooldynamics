-- =====================================================
-- Consolidate Communications Module Migration
-- Merge new "Communications" (ID 15) into existing "Communication" (ID 5)
-- =====================================================

-- Step 1: Drop duplicate tables from new Communications module first
-- (Must be done before we can reuse table names)
-- Drop in reverse order of foreign key dependencies

-- Drop separate queue tables first (they reference broadcast_recipients)
DROP TABLE IF EXISTS message_queue_sms;
DROP TABLE IF EXISTS message_queue_whatsapp;
DROP TABLE IF EXISTS message_queue_email;

-- Drop broadcast_recipients (references broadcasts)
DROP TABLE IF EXISTS broadcast_recipients;

-- Drop broadcast_approvers (no FK to broadcasts, but conceptually related)
DROP TABLE IF EXISTS broadcast_approvers;

-- Drop communication_credits (references broadcasts)
DROP TABLE IF EXISTS communication_credits;

-- Drop credit_balances (no FK)
DROP TABLE IF EXISTS credit_balances;

-- Drop broadcasts (references message_templates)
DROP TABLE IF EXISTS broadcasts;

-- Drop message_templates (duplicate of communication_templates)
DROP TABLE IF EXISTS message_templates;

-- Step 2: Now create broadcasts table referencing communication_templates
CREATE TABLE IF NOT EXISTS broadcasts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id INT UNSIGNED NOT NULL,
    source_type ENUM('event', 'fee', 'general', 'emergency', 'announcement') NOT NULL,
    source_id BIGINT UNSIGNED NULL COMMENT 'FK to source table (events, invoices, etc)',
    audience_type ENUM('guardians', 'staff', 'students', 'custom') NOT NULL,
    audience_filters JSON NULL COMMENT 'Filters for audience selection (classes, departments, etc)',
    channels JSON NOT NULL COMMENT 'Array of channels: sms, whatsapp, email',

    -- Message content
    subject VARCHAR(255) NULL COMMENT 'For email and WhatsApp',
    message_body TEXT NOT NULL,
    template_id INT NULL COMMENT 'FK to communication_templates',

    -- Status and approval
    status ENUM('draft', 'pending_approval', 'approved', 'processing', 'completed', 'cancelled') DEFAULT 'draft',
    requires_approval BOOLEAN DEFAULT TRUE,
    approval_otp VARCHAR(6) NULL,
    approval_otp_expires_at DATETIME NULL,
    requested_approver_id BIGINT UNSIGNED NULL COMMENT 'FK to users',
    approved_by BIGINT UNSIGNED NULL COMMENT 'FK to users',
    approved_at DATETIME NULL,
    rejection_reason TEXT NULL,

    -- Scheduling
    send_immediately BOOLEAN DEFAULT TRUE,
    scheduled_send_at DATETIME NULL,

    -- Recipient statistics
    total_recipients INT UNSIGNED DEFAULT 0,
    recipients_with_phone INT UNSIGNED DEFAULT 0 COMMENT 'For SMS/WhatsApp',
    recipients_with_email INT UNSIGNED DEFAULT 0 COMMENT 'For Email',
    eligible_recipients INT UNSIGNED DEFAULT 0 COMMENT 'Recipients with valid contact info for selected channels',

    -- Cost tracking
    estimated_sms_credits DECIMAL(10,2) DEFAULT 0,
    estimated_whatsapp_credits DECIMAL(10,2) DEFAULT 0,
    estimated_email_credits DECIMAL(10,2) DEFAULT 0,
    total_estimated_credits DECIMAL(10,2) DEFAULT 0,

    actual_sms_credits DECIMAL(10,2) DEFAULT 0,
    actual_whatsapp_credits DECIMAL(10,2) DEFAULT 0,
    actual_email_credits DECIMAL(10,2) DEFAULT 0,
    total_actual_credits DECIMAL(10,2) DEFAULT 0,

    -- Sending statistics
    messages_sent INT UNSIGNED DEFAULT 0,
    messages_failed INT UNSIGNED DEFAULT 0,
    messages_pending INT UNSIGNED DEFAULT 0,

    -- Duplicate detection
    similar_broadcast_warning TEXT NULL,
    user_acknowledged_duplicate BOOLEAN DEFAULT FALSE,

    -- Metadata
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    sent_at DATETIME NULL,
    completed_at DATETIME NULL,

    INDEX idx_school_status (school_id, status),
    INDEX idx_source (source_type, source_id),
    INDEX idx_created_by (created_by),
    INDEX idx_scheduled_send (scheduled_send_at),

    FOREIGN KEY (school_id) REFERENCES school_profile(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES communication_templates(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_approver_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Individual recipient tracking
CREATE TABLE IF NOT EXISTS broadcast_recipients (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    broadcast_id BIGINT UNSIGNED NOT NULL,
    recipient_type ENUM('guardian', 'staff', 'student') NOT NULL,
    recipient_id INT UNSIGNED NOT NULL,

    -- Contact info snapshot (at time of broadcast)
    recipient_name VARCHAR(255) NOT NULL,
    phone_number VARCHAR(20) NULL,
    email VARCHAR(255) NULL,

    -- Personalized message (with placeholders replaced)
    personalized_subject VARCHAR(255) NULL,
    personalized_message TEXT NOT NULL,

    -- Link to message_queue entries
    message_queue_id BIGINT UNSIGNED NULL COMMENT 'FK to message_queue for this recipient',

    -- Overall status (aggregated from channels)
    overall_status ENUM('pending', 'sent', 'failed', 'skipped') DEFAULT 'pending',

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_broadcast (broadcast_id),
    INDEX idx_recipient (recipient_type, recipient_id),
    INDEX idx_message_queue (message_queue_id),

    FOREIGN KEY (broadcast_id) REFERENCES broadcasts(id) ON DELETE CASCADE,
    FOREIGN KEY (message_queue_id) REFERENCES message_queue(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Broadcast approval configuration
CREATE TABLE IF NOT EXISTS broadcast_approvers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id INT UNSIGNED NOT NULL,

    user_id BIGINT UNSIGNED NOT NULL COMMENT 'User who can approve broadcasts',
    can_approve_sms BOOLEAN DEFAULT TRUE,
    can_approve_whatsapp BOOLEAN DEFAULT TRUE,
    can_approve_email BOOLEAN DEFAULT TRUE,

    -- Optional: Approval limits
    max_recipients_without_approval INT UNSIGNED NULL COMMENT 'NULL = always requires approval',
    max_cost_without_approval DECIMAL(10,2) NULL,

    is_active BOOLEAN DEFAULT TRUE,

    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_school (school_id),
    INDEX idx_user (user_id),

    FOREIGN KEY (school_id) REFERENCES school_profile(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 3: Add broadcast-specific templates to communication_templates
-- (Templates from the new Communications module)
-- Note: These are the 5 system templates we seeded earlier

-- Template 1: Fee Reminder
INSERT INTO communication_templates (
    category, template_code, template_name, description,
    channels, subject, sms_body, whatsapp_body, email_body,
    variables, is_system, is_active
) VALUES (
    'fee_reminder',
    'broadcast_fee_reminder',
    'Fee Reminder - Standard',
    'Standard fee reminder template with balance and due date',
    '["sms", "whatsapp", "email"]',
    'School Fee Reminder - {{student_name}}',
    'Dear {{guardian_name}}, {{student_name}} has an outstanding balance of KES {{amount_due}}. Please pay by {{due_date}}. Thank you. -{{school_name}}',
    'Dear *{{guardian_name}}*,\n\nThis is a friendly reminder that *{{student_name}}* ({{class_name}}) has an outstanding school fee balance.\n\n*Amount Due:* KES {{amount_due}}\n*Due Date:* {{due_date}}\n*Term:* {{term_name}}\n\nPlease arrange payment at your earliest convenience to avoid any inconvenience.\n\nThank you for your cooperation.\n\n_{{school_name}}_',
    '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;"><h2 style="color: #333;">School Fee Reminder</h2><p>Dear <strong>{{guardian_name}}</strong>,</p><p>This is a friendly reminder regarding the school fee balance for <strong>{{student_name}}</strong> in <strong>{{class_name}}</strong>.</p><div style="background-color: #f5f5f5; padding: 15px; border-left: 4px solid #007bff; margin: 20px 0;"><table style="width: 100%; border-collapse: collapse;"><tr><td style="padding: 5px 0;"><strong>Amount Due:</strong></td><td style="padding: 5px 0; color: #dc3545; font-size: 18px;"><strong>KES {{amount_due}}</strong></td></tr><tr><td style="padding: 5px 0;"><strong>Due Date:</strong></td><td style="padding: 5px 0;">{{due_date}}</td></tr></table></div><p>Thank you for your cooperation.</p><p style="margin-top: 30px; color: #666; font-size: 12px;"><strong>{{school_name}}</strong></p></div>',
    '["guardian_name", "student_name", "class_name", "term_name", "amount_due", "due_date", "school_name"]',
    1,
    1
) ON DUPLICATE KEY UPDATE template_name = VALUES(template_name);

-- Step 4: Update message_queue to link to broadcasts
-- Check if column exists first
SET @dbname = DATABASE();
SET @tablename = 'message_queue';
SET @columnname = 'broadcast_id';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' BIGINT UNSIGNED NULL COMMENT ''Link to broadcast campaign'' AFTER related_entity_id, ADD INDEX idx_broadcast_id (', @columnname, ')')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Note: We cannot add FK constraint here because it would require all existing rows to have broadcast_id
-- FK will be added only for new messages created from broadcasts

-- Step 4: Add new submodules to Communication module (ID 5)
INSERT INTO submodules (module_id, name, display_name, icon, route, sort_order, is_active)
VALUES
    (5, 'broadcasts', 'Broadcasts', 'ti ti-broadcast', '/communication/broadcasts', 15, 1),
    (5, 'credits', 'Communication Credits', 'ti ti-coin', '/communication/credits', 25, 1),
    (5, 'approvals', 'Broadcast Approvals', 'ti ti-check', '/communication/approvals', 35, 1),
    (5, 'history', 'Broadcast History', 'ti ti-history', '/communication/history', 45, 1)
ON DUPLICATE KEY UPDATE
    display_name = VALUES(display_name),
    icon = VALUES(icon);

-- Step 5: Create permissions for new submodules
-- Get the new submodule IDs and create permissions
INSERT INTO permissions (submodule_id, name, display_name, action)
SELECT
    s.id,
    CONCAT('view_', s.name),
    CONCAT('View ', s.display_name),
    'view'
FROM submodules s
WHERE s.module_id = 5
AND s.name IN ('broadcasts', 'credits', 'approvals', 'history')
ON DUPLICATE KEY UPDATE display_name = VALUES(display_name);

INSERT INTO permissions (submodule_id, name, display_name, action)
SELECT
    s.id,
    CONCAT('manage_', s.name),
    CONCAT('Manage ', s.display_name),
    'modify'
FROM submodules s
WHERE s.module_id = 5
AND s.name IN ('broadcasts', 'credits', 'approvals', 'history')
ON DUPLICATE KEY UPDATE display_name = VALUES(display_name);

-- Step 6: Assign new permissions to ADMIN role (ID = 1)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, p.id
FROM permissions p
INNER JOIN submodules s ON p.submodule_id = s.id
WHERE s.module_id = 5
AND s.name IN ('broadcasts', 'credits', 'approvals', 'history')
ON DUPLICATE KEY UPDATE role_id = VALUES(role_id);

-- Step 7: Delete duplicate Communications module (ID 15)
DELETE FROM role_permissions
WHERE permission_id IN (
    SELECT id FROM permissions WHERE submodule_id IN (
        SELECT id FROM submodules WHERE module_id = 15
    )
);

DELETE FROM permissions
WHERE submodule_id IN (
    SELECT id FROM submodules WHERE module_id = 15
);

DELETE FROM submodules WHERE module_id = 15;

DELETE FROM modules WHERE id = 15;

-- Step 8: Tables already dropped at the beginning of this script

-- Note: Keep communication_credits table as it already exists and works
-- Just ensure credit_balances exists for quick lookups

-- Add credit_balances if it doesn't exist
CREATE TABLE IF NOT EXISTS credit_balances (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id INT UNSIGNED NOT NULL,

    sms_credits DECIMAL(10,2) DEFAULT 0.00,
    whatsapp_credits DECIMAL(10,2) DEFAULT 0.00,
    email_credits DECIMAL(10,2) DEFAULT 0.00,

    last_updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_school (school_id),

    FOREIGN KEY (school_id) REFERENCES school_profile(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Done! The Communication module (ID 5) now has all features
