-- Calendar Module Phase 2: Events & Event Planning Schema
-- Created: 2026-01-08
-- Description: Full-featured event management with ownership, audience targeting, RSVP, and attendance

-- =============================================================================
-- EVENTS TABLE - Main events table
-- =============================================================================

CREATE TABLE IF NOT EXISTS events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id INT UNSIGNED NOT NULL,
    term_id INT UNSIGNED NULL,
    event_type_id INT UNSIGNED NOT NULL,

    -- Basic Information
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    venue VARCHAR(255) NULL,

    -- Date & Time
    start_date DATE NOT NULL,
    start_time TIME NULL,
    end_date DATE NULL,
    end_time TIME NULL,
    is_all_day BOOLEAN DEFAULT FALSE,

    -- Status
    status ENUM('draft', 'published', 'cancelled', 'completed') DEFAULT 'draft',
    visibility ENUM('public', 'private') DEFAULT 'public',

    -- RSVP Configuration
    rsvp_enabled BOOLEAN DEFAULT FALSE,
    rsvp_required BOOLEAN DEFAULT FALSE,
    rsvp_deadline DATE NULL,
    max_attendees INT NULL,
    allow_guests BOOLEAN DEFAULT FALSE,
    max_guests_per_attendee INT DEFAULT 0,

    -- Event Planning
    requires_planning BOOLEAN DEFAULT FALSE,
    planning_start_date DATE NULL,

    -- Tracking
    created_by INT UNSIGNED NOT NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    published_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    cancellation_reason TEXT NULL,

    -- Indexes
    INDEX idx_school (school_id),
    INDEX idx_term (term_id),
    INDEX idx_event_type (event_type_id),
    INDEX idx_status (status),
    INDEX idx_dates (start_date, end_date),
    INDEX idx_created_by (created_by),

    -- Foreign Keys (school_id not enforced in multi-tenant setup)
    FOREIGN KEY (term_id) REFERENCES terms(id) ON DELETE SET NULL,
    FOREIGN KEY (event_type_id) REFERENCES term_date_types(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- EVENT OWNERS TABLE - Event ownership and co-owners
-- =============================================================================

CREATE TABLE IF NOT EXISTS event_owners (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id BIGINT UNSIGNED NOT NULL,
    owner_type ENUM('staff', 'department', 'associate') NOT NULL,
    owner_id INT UNSIGNED NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,

    -- Permissions
    can_edit BOOLEAN DEFAULT FALSE,
    can_manage_rsvp BOOLEAN DEFAULT FALSE,
    can_view_reports BOOLEAN DEFAULT TRUE,
    can_send_notifications BOOLEAN DEFAULT FALSE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_event (event_id),
    INDEX idx_owner (owner_type, owner_id),

    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,

    UNIQUE KEY unique_event_owner (event_id, owner_type, owner_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- EVENT AUDIENCES TABLE - Target audience configuration
-- =============================================================================

CREATE TABLE IF NOT EXISTS event_audiences (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id BIGINT UNSIGNED NOT NULL,
    audience_type ENUM('all_students', 'grade', 'grade_range', 'stream', 'boarding_status',
                       'specific_students', 'all_parents', 'grade_parents', 'student_parents',
                       'all_staff', 'department_staff', 'role_staff', 'specific_staff',
                       'external') NOT NULL,

    -- Configuration (JSON for flexible targeting)
    config JSON NULL COMMENT 'Stores grade numbers, student IDs, staff IDs, etc.',

    -- Permissions for this audience
    can_view BOOLEAN DEFAULT TRUE,
    can_rsvp BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_event (event_id),
    INDEX idx_audience_type (audience_type),

    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- EVENT RSVPS TABLE - RSVP responses
-- =============================================================================

CREATE TABLE IF NOT EXISTS event_rsvps (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id BIGINT UNSIGNED NOT NULL,

    -- Attendee Information
    attendee_type ENUM('student', 'parent', 'staff', 'external') NOT NULL,
    attendee_id INT UNSIGNED NOT NULL,

    -- RSVP Response
    response ENUM('attending', 'not_attending', 'maybe') NOT NULL,
    guest_count INT DEFAULT 0,

    -- Special Requirements
    special_requirements TEXT NULL,
    dietary_restrictions TEXT NULL,

    -- Tracking
    rsvp_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Unique check-in ID
    checkin_code VARCHAR(8) NULL UNIQUE COMMENT 'Unique code for attendance check-in',
    qr_code_path VARCHAR(255) NULL COMMENT 'Path to generated QR code image',

    INDEX idx_event (event_id),
    INDEX idx_attendee (attendee_type, attendee_id),
    INDEX idx_response (response),
    INDEX idx_checkin_code (checkin_code),

    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,

    UNIQUE KEY unique_event_attendee (event_id, attendee_type, attendee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- EVENT ATTENDANCE TABLE - Check-in records
-- =============================================================================

CREATE TABLE IF NOT EXISTS event_attendance (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id BIGINT UNSIGNED NOT NULL,
    rsvp_id BIGINT UNSIGNED NULL COMMENT 'NULL for walk-ins',

    -- Attendee Information (duplicated for walk-ins)
    attendee_type ENUM('student', 'parent', 'staff', 'external') NOT NULL,
    attendee_id INT UNSIGNED NULL,
    attendee_name VARCHAR(255) NULL COMMENT 'For walk-ins without ID',

    -- Check-in Details
    checkin_method ENUM('qr_code', 'unique_id', 'manual', 'walk_in') NOT NULL,
    checkin_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    checkout_time TIMESTAMP NULL,

    -- Guest tracking
    actual_guest_count INT DEFAULT 0,

    -- Check-in location/staff
    checkin_by INT UNSIGNED NULL COMMENT 'Staff who checked them in',
    checkin_notes TEXT NULL,

    INDEX idx_event (event_id),
    INDEX idx_rsvp (rsvp_id),
    INDEX idx_attendee (attendee_type, attendee_id),
    INDEX idx_checkin_time (checkin_time),

    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (rsvp_id) REFERENCES event_rsvps(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- EVENT PLANNING TASKS TABLE - Pre-event task checklist
-- =============================================================================

CREATE TABLE IF NOT EXISTS event_planning_tasks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id BIGINT UNSIGNED NOT NULL,

    -- Task Details
    task_title VARCHAR(255) NOT NULL,
    task_description TEXT NULL,
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',

    -- Assignment
    assigned_to INT UNSIGNED NULL,
    assigned_by INT UNSIGNED NOT NULL,

    -- Deadline
    due_date DATE NOT NULL,
    due_time TIME NULL,

    -- Status
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    completed_at TIMESTAMP NULL,
    completed_by INT UNSIGNED NULL,

    -- Reminders
    reminder_enabled BOOLEAN DEFAULT TRUE,
    reminder_days_before INT DEFAULT 1,
    reminder_sent BOOLEAN DEFAULT FALSE,
    reminder_sent_at TIMESTAMP NULL,

    -- Tracking
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Notes
    completion_notes TEXT NULL,

    INDEX idx_event (event_id),
    INDEX idx_assigned_to (assigned_to),
    INDEX idx_status (status),
    INDEX idx_due_date (due_date),
    INDEX idx_reminder (reminder_enabled, reminder_sent, due_date),

    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- EVENT NOTIFICATIONS TABLE - Notification queue and log
-- =============================================================================

CREATE TABLE IF NOT EXISTS event_notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id BIGINT UNSIGNED NOT NULL,

    -- Notification Details
    notification_type ENUM('creation', 'update', 'reminder', 'rsvp_reminder',
                          'cancellation', 'custom') NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,

    -- Target Audience
    target_type ENUM('all_attendees', 'rsvp_attending', 'rsvp_not_attending',
                     'rsvp_maybe', 'no_rsvp', 'specific_attendees',
                     'event_owners') NOT NULL,
    target_config JSON NULL COMMENT 'Specific attendee IDs if applicable',

    -- Channels
    send_portal_notification BOOLEAN DEFAULT TRUE,
    send_sms BOOLEAN DEFAULT FALSE,
    send_email BOOLEAN DEFAULT FALSE,

    -- Scheduling
    scheduled_for TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,

    -- Status
    status ENUM('pending', 'sent', 'failed', 'cancelled') DEFAULT 'pending',
    recipient_count INT DEFAULT 0,
    sent_count INT DEFAULT 0,
    failed_count INT DEFAULT 0,

    -- Tracking
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_event (event_id),
    INDEX idx_status (status),
    INDEX idx_scheduled (scheduled_for, status),
    INDEX idx_type (notification_type),

    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- EVENT NOTIFICATION RECIPIENTS TABLE - Individual notification tracking
-- =============================================================================

CREATE TABLE IF NOT EXISTS event_notification_recipients (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    notification_id BIGINT UNSIGNED NOT NULL,

    -- Recipient
    recipient_type ENUM('student', 'parent', 'staff') NOT NULL,
    recipient_id INT UNSIGNED NOT NULL,

    -- Delivery Status per Channel
    portal_sent BOOLEAN DEFAULT FALSE,
    portal_read BOOLEAN DEFAULT FALSE,
    portal_read_at TIMESTAMP NULL,

    sms_sent BOOLEAN DEFAULT FALSE,
    sms_delivered BOOLEAN DEFAULT FALSE,
    sms_error TEXT NULL,

    email_sent BOOLEAN DEFAULT FALSE,
    email_opened BOOLEAN DEFAULT FALSE,
    email_error TEXT NULL,

    sent_at TIMESTAMP NULL,

    INDEX idx_notification (notification_id),
    INDEX idx_recipient (recipient_type, recipient_id),

    FOREIGN KEY (notification_id) REFERENCES event_notifications(id) ON DELETE CASCADE,

    UNIQUE KEY unique_notification_recipient (notification_id, recipient_type, recipient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- EVENT COMMENTS TABLE - Event discussion/updates
-- =============================================================================

CREATE TABLE IF NOT EXISTS event_comments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id BIGINT UNSIGNED NOT NULL,

    -- Comment Details
    comment TEXT NOT NULL,
    is_internal BOOLEAN DEFAULT FALSE COMMENT 'Internal notes vs public comments',

    -- Author
    author_type ENUM('staff', 'system') NOT NULL,
    author_id INT UNSIGNED NULL,

    -- Tracking
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_event (event_id),
    INDEX idx_author (author_type, author_id),
    INDEX idx_created_at (created_at),

    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- EVENT ATTACHMENTS TABLE - Files and documents
-- =============================================================================

CREATE TABLE IF NOT EXISTS event_attachments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id BIGINT UNSIGNED NOT NULL,

    -- File Details
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50) NULL,
    file_size INT UNSIGNED NULL,

    -- Metadata
    title VARCHAR(255) NULL,
    description TEXT NULL,

    -- Visibility
    is_public BOOLEAN DEFAULT TRUE COMMENT 'Visible to all vs owners only',

    -- Tracking
    uploaded_by INT UNSIGNED NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_event (event_id),
    INDEX idx_uploaded_by (uploaded_by),

    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
