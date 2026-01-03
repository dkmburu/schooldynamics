-- =====================================================
-- Authorization Audit Trail Table
-- Complete audit log of all authorization-related actions
-- =====================================================

CREATE TABLE IF NOT EXISTS authorization_audit (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Link to Authorization Request
    authorization_request_id INT NOT NULL COMMENT 'Links to authorization_requests table',

    -- Action Details
    action VARCHAR(50) NOT NULL COMMENT 'Action: created, sent, viewed, approved, rejected, expired, revoked, resent',
    action_description TEXT COMMENT 'Human-readable description of the action',

    -- Actor Information
    actor_type VARCHAR(50) COMMENT 'Who performed the action: guardian, staff, system',
    actor_id BIGINT UNSIGNED COMMENT 'User ID (if staff) or NULL for guardian/system',
    actor_name VARCHAR(200) COMMENT 'Name of the person who performed action',

    -- Context Information
    ip_address VARCHAR(45) COMMENT 'IP address where action occurred',
    user_agent TEXT COMMENT 'Browser/device information',
    location VARCHAR(100) COMMENT 'Physical location (for staff actions): office, front_desk, etc.',
    contact_method VARCHAR(50) COMMENT 'How guardian contacted: phone, email, in_person, etc.',

    -- Additional Data
    metadata JSON COMMENT 'Additional context data specific to the action',
    -- Example: {"previous_status": "pending", "new_status": "approved"}
    -- Example: {"channel": "sms", "phone_number": "+123456789"}
    -- Example: {"reason": "Parent requested revocation"}

    -- Timestamp
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Indexes
    INDEX idx_authorization_request_id (authorization_request_id),
    INDEX idx_action (action),
    INDEX idx_actor_type (actor_type),
    INDEX idx_created_at (created_at),

    -- Foreign Key
    FOREIGN KEY (authorization_request_id) REFERENCES authorization_requests(id) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Complete audit trail for all authorization actions';

-- =====================================================
-- Trigger: Auto-log authorization request creation
-- =====================================================

DELIMITER $$

CREATE TRIGGER after_authorization_request_insert
AFTER INSERT ON authorization_requests
FOR EACH ROW
BEGIN
    INSERT INTO authorization_audit (
        authorization_request_id,
        action,
        action_description,
        actor_type,
        actor_id,
        actor_name,
        metadata
    ) VALUES (
        NEW.id,
        'created',
        CONCAT('Authorization request created for ', NEW.entity_type, ' #', NEW.entity_id, ' (', NEW.request_type, ')'),
        'staff',
        NEW.created_by,
        (SELECT full_name FROM users WHERE id = NEW.created_by),
        JSON_OBJECT(
            'entity_type', NEW.entity_type,
            'entity_id', NEW.entity_id,
            'request_type', NEW.request_type,
            'recipient_name', NEW.recipient_name
        )
    );
END$$

DELIMITER ;

-- =====================================================
-- Trigger: Auto-log authorization status changes
-- =====================================================

DELIMITER $$

CREATE TRIGGER after_authorization_request_update
AFTER UPDATE ON authorization_requests
FOR EACH ROW
BEGIN
    -- Log status changes
    IF OLD.status != NEW.status THEN
        INSERT INTO authorization_audit (
            authorization_request_id,
            action,
            action_description,
            actor_type,
            actor_id,
            ip_address,
            user_agent,
            location,
            contact_method,
            metadata
        ) VALUES (
            NEW.id,
            NEW.status,
            CONCAT('Status changed from ', OLD.status, ' to ', NEW.status),
            CASE
                WHEN NEW.authorization_method = 'code_staff' THEN 'staff'
                WHEN NEW.authorization_method = 'link' THEN 'guardian'
                ELSE 'system'
            END,
            NEW.authorized_by_staff_id,
            NEW.approval_ip,
            NEW.approval_user_agent,
            NEW.authorization_location,
            NEW.parent_contact_method,
            JSON_OBJECT(
                'old_status', OLD.status,
                'new_status', NEW.status,
                'authorization_method', NEW.authorization_method,
                'staff_notes', NEW.staff_notes
            )
        );
    END IF;
END$$

DELIMITER ;

-- =====================================================
-- Example Audit Queries
-- =====================================================

-- Get complete audit trail for a specific authorization request
-- SELECT * FROM authorization_audit WHERE authorization_request_id = 123 ORDER BY created_at DESC;

-- Get all actions performed by a specific staff member
-- SELECT * FROM authorization_audit WHERE actor_type = 'staff' AND actor_id = 5 ORDER BY created_at DESC;

-- Get all staff-assisted approvals
-- SELECT * FROM authorization_audit WHERE action = 'approved' AND actor_type = 'staff';

-- Get authorization activity for the last 30 days
-- SELECT * FROM authorization_audit WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY);
