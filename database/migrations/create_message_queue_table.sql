-- =====================================================
-- Message Queue Table
-- Centralized queue for all outgoing communications
-- Supports SMS, Email, WhatsApp with priority handling
-- =====================================================

CREATE TABLE IF NOT EXISTS message_queue (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Message Type & Channel
    channel ENUM('sms', 'email', 'whatsapp') NOT NULL COMMENT 'Communication channel',
    message_type VARCHAR(100) NOT NULL COMMENT 'Type: authorization_request, exam_notification, etc.',

    -- Recipient Information
    recipient_name VARCHAR(200) COMMENT 'Recipient name',
    recipient_email VARCHAR(255) COMMENT 'Email address (required for email channel)',
    recipient_phone VARCHAR(20) COMMENT 'Phone number (required for SMS/WhatsApp)',

    -- Message Content
    subject VARCHAR(500) COMMENT 'Subject line (for email)',
    message_body TEXT NOT NULL COMMENT 'Message content (after template substitution)',

    -- Priority & Processing
    priority TINYINT UNSIGNED DEFAULT 5 COMMENT 'Priority: 1=highest, 10=lowest, 5=normal',
    status ENUM('queued', 'processing', 'sent', 'failed', 'cancelled') DEFAULT 'queued',

    -- Scheduling
    scheduled_at TIMESTAMP NULL COMMENT 'When to send (NULL = send immediately)',

    -- Processing Tracking
    attempts INT UNSIGNED DEFAULT 0 COMMENT 'Number of send attempts',
    max_attempts INT UNSIGNED DEFAULT 3 COMMENT 'Maximum retry attempts',
    sent_at TIMESTAMP NULL COMMENT 'When successfully sent',
    failed_at TIMESTAMP NULL COMMENT 'When permanently failed',
    last_attempt_at TIMESTAMP NULL COMMENT 'Last attempt timestamp',

    -- Error Tracking
    error_message TEXT COMMENT 'Last error message if failed',
    error_code VARCHAR(50) COMMENT 'Error code from gateway',

    -- Gateway Information
    gateway_provider VARCHAR(50) COMMENT 'SMS/Email gateway used (twilio, sendgrid, etc.)',
    gateway_message_id VARCHAR(255) COMMENT 'Message ID from gateway',
    gateway_response JSON COMMENT 'Full response from gateway',

    -- Related Entity (Optional polymorphic relationship)
    related_entity_type VARCHAR(50) COMMENT 'Related entity: applicant, student, authorization_request, etc.',
    related_entity_id INT COMMENT 'Related entity ID',

    -- Multi-tenant Support
    campus_id INT UNSIGNED NOT NULL COMMENT 'Campus this message belongs to',

    -- Audit Fields
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NOT NULL COMMENT 'User who created/triggered this message',

    -- Indexes for Performance
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_channel (channel),
    INDEX idx_scheduled_at (scheduled_at),
    INDEX idx_status_priority (status, priority, scheduled_at),
    INDEX idx_campus_id (campus_id),
    INDEX idx_related_entity (related_entity_type, related_entity_id),
    INDEX idx_created_at (created_at),

    -- Foreign Key Constraints
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (campus_id) REFERENCES campuses(id) ON DELETE RESTRICT

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Centralized queue for all outgoing communications (SMS, Email, WhatsApp)';

-- =====================================================
-- Message Queue Processing Helpers
-- =====================================================

-- View: Get messages ready to send (queued, scheduled time passed, not exceeded max attempts)
CREATE OR REPLACE VIEW messages_ready_to_send AS
SELECT
    mq.*,
    c.campus_name,
    u.full_name as created_by_name
FROM message_queue mq
JOIN campuses c ON mq.campus_id = c.id
JOIN users u ON mq.created_by = u.id
WHERE mq.status = 'queued'
  AND mq.attempts < mq.max_attempts
  AND (mq.scheduled_at IS NULL OR mq.scheduled_at <= NOW())
ORDER BY mq.priority ASC, mq.created_at ASC;

-- View: Get failed messages that can be retried
CREATE OR REPLACE VIEW messages_ready_to_retry AS
SELECT
    mq.*,
    TIMESTAMPDIFF(MINUTE, mq.last_attempt_at, NOW()) as minutes_since_last_attempt
FROM message_queue mq
WHERE mq.status = 'failed'
  AND mq.attempts < mq.max_attempts
  AND TIMESTAMPDIFF(MINUTE, mq.last_attempt_at, NOW()) >= 5  -- Wait 5 minutes between retries
ORDER BY mq.priority ASC, mq.last_attempt_at ASC;

-- =====================================================
-- Example Queries for Message Processing Service
-- =====================================================

-- Get next batch of messages to send (top 100 by priority)
-- SELECT * FROM messages_ready_to_send LIMIT 100;

-- Mark message as processing
-- UPDATE message_queue SET status = 'processing', updated_at = NOW() WHERE id = ? AND status = 'queued';

-- Mark message as sent
-- UPDATE message_queue
-- SET status = 'sent', sent_at = NOW(), gateway_message_id = ?, gateway_response = ?, updated_at = NOW()
-- WHERE id = ?;

-- Mark message as failed (for retry)
-- UPDATE message_queue
-- SET status = 'failed', attempts = attempts + 1, last_attempt_at = NOW(),
--     error_message = ?, error_code = ?, updated_at = NOW()
-- WHERE id = ?;

-- Mark message as permanently failed (exceeded max attempts)
-- UPDATE message_queue
-- SET status = 'failed', failed_at = NOW(), attempts = attempts + 1,
--     last_attempt_at = NOW(), error_message = ?, updated_at = NOW()
-- WHERE id = ? AND attempts >= max_attempts - 1;

-- Cancel pending messages
-- UPDATE message_queue SET status = 'cancelled', updated_at = NOW()
-- WHERE status IN ('queued', 'failed') AND related_entity_type = ? AND related_entity_id = ?;

-- Get message statistics
-- SELECT
--     channel,
--     status,
--     COUNT(*) as count,
--     AVG(attempts) as avg_attempts
-- FROM message_queue
-- WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
-- GROUP BY channel, status;
