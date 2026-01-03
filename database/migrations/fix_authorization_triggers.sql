-- Fix authorization audit triggers to use full_name instead of first_name/last_name

DROP TRIGGER IF EXISTS after_authorization_request_insert;
DROP TRIGGER IF EXISTS after_authorization_request_update;

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
