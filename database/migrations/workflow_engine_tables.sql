-- =====================================================
-- WORKFLOW ENGINE DATABASE MIGRATION
-- Created: 2025-12-17
-- Description: Creates all 14 tables for the workflow engine
-- =====================================================

-- Ensure we're using the correct database
USE sims_demo;

-- =====================================================
-- 1. WORKFLOWS (Workflow Templates)
-- =====================================================

CREATE TABLE IF NOT EXISTS workflows (
    id INT PRIMARY KEY AUTO_INCREMENT,

    -- Basic Info
    code VARCHAR(50) NOT NULL UNIQUE,          -- e.g., 'APPLICANT_ADMISSION', 'LEAVE_REQUEST'
    name VARCHAR(100) NOT NULL,                 -- Display name
    description TEXT,
    category VARCHAR(50),                       -- 'admissions', 'hr', 'finance', 'discipline'

    -- Entity Binding
    entity_type VARCHAR(50) NOT NULL,           -- 'applicant', 'student', 'invoice', 'leave_request'

    -- Trigger Configuration (JSON)
    -- {
    --   "type": "auto|manual|api|scheduled",
    --   "auto_trigger": {
    --     "event": "insert|update",
    --     "table": "applicants",
    --     "conditions": [{"field": "status", "operator": "=", "value": "submitted"}]
    --   },
    --   "scheduled_trigger": {
    --     "cron": "0 8 * * 1",
    --     "query": "SELECT id FROM applicants WHERE status = 'pending' AND created_at < NOW() - INTERVAL 7 DAY"
    --   }
    -- }
    trigger_config JSON,

    -- Behavior Settings
    allow_multiple_instances TINYINT(1) DEFAULT 0,  -- Can same entity have multiple active tickets?
    parallel_join_mode ENUM('all', 'any', 'custom') DEFAULT 'all',  -- How parallel paths rejoin

    -- Status
    is_active TINYINT(1) DEFAULT 1,
    version INT DEFAULT 1,                      -- For versioning workflow changes

    -- Audit
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_entity_type (entity_type),
    INDEX idx_category (category),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. WORKFLOW STEPS (Nodes in the workflow graph)
-- =====================================================

CREATE TABLE IF NOT EXISTS workflow_steps (
    id INT PRIMARY KEY AUTO_INCREMENT,
    workflow_id INT NOT NULL,

    -- Identification
    code VARCHAR(50) NOT NULL,                  -- e.g., 'INITIAL_REVIEW', 'PRINCIPAL_APPROVAL'
    name VARCHAR(100) NOT NULL,                 -- Display name
    description TEXT,

    -- Step Type
    step_type ENUM(
        'start',              -- Entry point (only one per workflow)
        'task',               -- Normal task step
        'decision',           -- Branching point (user choice or automatic)
        'parallel_split',     -- Fork into parallel branches
        'parallel_join',      -- Merge parallel branches
        'auto',               -- Automatic action (no user interaction)
        'sub_workflow',       -- Invoke another workflow
        'end'                 -- Terminal step (can have multiple)
    ) NOT NULL DEFAULT 'task',

    -- Actor Assignment
    -- Roles that can act on this step (JSON array)
    -- ["ADMIN", "HEAD_TEACHER", "ADMISSIONS_OFFICER"]
    actor_roles JSON,

    -- Assignment Mode
    assignment_mode ENUM(
        'any',                -- Any user with the role can claim/act
        'all',                -- All users with role must act (for parallel approval)
        'auto_assign',        -- System auto-assigns based on rules
        'specific_user'       -- Assigned to specific user (set at runtime)
    ) DEFAULT 'any',

    -- Auto-assignment rules (JSON) - used when assignment_mode = 'auto_assign'
    -- {
    --   "rule": "campus_head",
    --   "fallback_role": "ADMIN"
    -- }
    auto_assign_config JSON,

    -- Available Actions at this step
    -- JSON array of action definitions
    -- [
    --   {"code": "approve", "label": "Approve", "next_step": "PRINCIPAL_REVIEW", "requires_comment": false},
    --   {"code": "reject", "label": "Reject", "next_step": "REJECTED_END", "requires_comment": true},
    --   {"code": "request_info", "label": "Request More Info", "next_step": "AWAITING_INFO", "requires_comment": true}
    -- ]
    available_actions JSON,

    -- Decision Configuration (for step_type = 'decision')
    -- {
    --   "mode": "user_choice|field_condition|both",
    --   "conditions": [
    --     {"field": "grade_category", "operator": "=", "value": "High School", "next_step": "HS_REVIEW"},
    --     {"field": "grade_category", "operator": "=", "value": "Primary", "next_step": "PRIMARY_REVIEW"}
    --   ],
    --   "default_next_step": "GENERAL_REVIEW"
    -- }
    decision_config JSON,

    -- Parallel Configuration (for split/join)
    -- For split: {"branches": ["BRANCH_A", "BRANCH_B", "BRANCH_C"]}
    -- For join: {"join_mode": "all|any", "source_branches": ["BRANCH_A", "BRANCH_B"], "next_step": "AFTER_JOIN"}
    parallel_config JSON,

    -- Auto-complete Configuration (for step_type = 'auto')
    -- {
    --   "action": "update_field|send_notification|call_api",
    --   "config": {...},
    --   "next_step": "NEXT_STEP_CODE"
    -- }
    auto_config JSON,

    -- Sub-workflow Configuration (for step_type = 'sub_workflow')
    -- {
    --   "sub_workflow_code": "DOCUMENT_VERIFICATION",
    --   "wait_for_completion": true,
    --   "outcome_mapping": {
    --     "approved": "NEXT_STEP",
    --     "rejected": "REJECTION_STEP"
    --   },
    --   "pass_entity": true
    -- }
    sub_workflow_config JSON,

    -- Form Configuration
    require_comment TINYINT(1) DEFAULT 0,       -- Is comment mandatory for all actions?
    comment_label VARCHAR(100) DEFAULT 'Comments',  -- Label for comment field

    -- Timeline & Escalation
    sla_hours INT,                              -- Expected completion time in hours (NULL = no limit)
    escalation_enabled TINYINT(1) DEFAULT 0,
    escalation_hours INT,                       -- Hours after which to escalate
    escalation_role VARCHAR(50),                -- Role to notify on escalation
    reminder_enabled TINYINT(1) DEFAULT 0,
    reminder_hours INT,                         -- Hours before SLA to send reminder

    -- Time-Based Auto-Transition
    auto_transition_enabled TINYINT(1) DEFAULT 0,
    auto_transition_hours INT,                  -- Hours after which auto-transition triggers
    auto_transition_action ENUM(
        'auto_approve',       -- Auto-approve and proceed
        'auto_reject',        -- Auto-reject
        'auto_escalate',      -- Escalate (but don't move task)
        'auto_cancel'         -- Cancel the ticket
    ),
    auto_transition_next_step VARCHAR(50),      -- Step to transition to (if applicable)
    auto_transition_comment VARCHAR(500),       -- Comment to add when auto-transitioning

    -- UI Configuration
    sort_order INT DEFAULT 0,                   -- For visual ordering in designer
    ui_position_x INT,                          -- X position in workflow designer
    ui_position_y INT,                          -- Y position in workflow designer
    color VARCHAR(20),                          -- Step color in designer
    icon VARCHAR(50),                           -- FontAwesome icon

    -- Metadata
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (workflow_id) REFERENCES workflows(id) ON DELETE CASCADE,
    UNIQUE KEY uk_workflow_step_code (workflow_id, code),
    INDEX idx_step_type (step_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. WORKFLOW STEP FIELDS (Custom form fields per step)
-- =====================================================

CREATE TABLE IF NOT EXISTS workflow_step_fields (
    id INT PRIMARY KEY AUTO_INCREMENT,
    step_id INT NOT NULL,

    -- Field Definition
    field_code VARCHAR(50) NOT NULL,            -- e.g., 'interview_score', 'rejection_reason'
    field_label VARCHAR(100) NOT NULL,          -- Display label
    field_type ENUM(
        'text',               -- Single line text
        'textarea',           -- Multi-line text
        'number',             -- Numeric input
        'date',               -- Date picker
        'datetime',           -- Date and time picker
        'select',             -- Dropdown select
        'multiselect',        -- Multiple selection
        'checkbox',           -- Boolean checkbox
        'radio',              -- Radio buttons
        'file',               -- File upload
        'rating',             -- Star rating (1-5)
        'score'               -- Numeric score with min/max
    ) NOT NULL,

    -- Field Configuration
    is_required TINYINT(1) DEFAULT 0,
    placeholder VARCHAR(200),
    help_text TEXT,
    default_value VARCHAR(500),

    -- Validation
    min_value INT,                              -- For number/score types
    max_value INT,                              -- For number/score types
    min_length INT,                             -- For text types
    max_length INT,                             -- For text types
    pattern VARCHAR(200),                       -- Regex pattern for validation

    -- Options for select/radio/multiselect (JSON array)
    -- [{"value": "opt1", "label": "Option 1"}, {"value": "opt2", "label": "Option 2"}]
    options JSON,

    -- File upload config (for field_type = 'file')
    -- {"allowed_types": ["pdf", "jpg", "png"], "max_size_mb": 5, "multiple": false}
    file_config JSON,

    -- Conditional visibility
    -- {"field": "decision", "operator": "=", "value": "reject"}
    show_condition JSON,

    -- Display
    sort_order INT DEFAULT 0,
    column_width INT DEFAULT 12,                -- Bootstrap grid (1-12)

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (step_id) REFERENCES workflow_steps(id) ON DELETE CASCADE,
    UNIQUE KEY uk_step_field (step_id, field_code),
    INDEX idx_step (step_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. WORKFLOW TRANSITIONS (Edges connecting steps)
-- =====================================================

CREATE TABLE IF NOT EXISTS workflow_transitions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    workflow_id INT NOT NULL,

    from_step_id INT NOT NULL,
    to_step_id INT NOT NULL,

    -- Transition trigger
    action_code VARCHAR(50),                    -- Which action triggers this transition (NULL for auto/decision)

    -- Conditional transition (optional)
    -- {"field": "amount", "operator": ">", "value": 10000}
    condition_config JSON,

    -- Priority for condition evaluation (lower = higher priority)
    priority INT DEFAULT 0,

    -- Metadata
    label VARCHAR(100),                         -- Display label for the transition arrow
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (workflow_id) REFERENCES workflows(id) ON DELETE CASCADE,
    FOREIGN KEY (from_step_id) REFERENCES workflow_steps(id) ON DELETE CASCADE,
    FOREIGN KEY (to_step_id) REFERENCES workflow_steps(id) ON DELETE CASCADE,
    INDEX idx_from_step (from_step_id),
    INDEX idx_to_step (to_step_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. WORKFLOW TICKETS (Running instances)
-- =====================================================

CREATE TABLE IF NOT EXISTS workflow_tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,

    -- Ticket Identification
    ticket_number VARCHAR(30) NOT NULL UNIQUE,  -- e.g., 'WF-2024-00001'

    -- Linked Workflow
    workflow_id INT NOT NULL,
    workflow_version INT DEFAULT 1,             -- Snapshot of workflow version when started

    -- Entity Binding (Polymorphic)
    entity_type VARCHAR(50) NOT NULL,           -- 'applicant', 'student', 'invoice'
    entity_id INT NOT NULL,                     -- ID in the entity table

    -- Current State
    status ENUM(
        'active',             -- In progress
        'completed',          -- Successfully finished
        'cancelled',          -- Manually cancelled
        'failed',             -- Failed due to error
        'paused'              -- Temporarily paused
    ) NOT NULL DEFAULT 'active',

    -- Tracking parallel execution
    -- JSON object tracking which branches are active/completed
    -- {"BRANCH_A": "completed", "BRANCH_B": "active", "BRANCH_C": "pending"}
    parallel_state JSON,

    -- Outcome (set when completed)
    outcome VARCHAR(50),                        -- 'approved', 'rejected', 'withdrawn', etc.
    outcome_notes TEXT,

    -- Timing
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,

    -- Initiator
    started_by INT,                             -- User who initiated (NULL if auto-triggered)
    started_by_type ENUM('user', 'system', 'api') DEFAULT 'user',

    -- Metadata
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    tags JSON,                                  -- Optional tags for filtering

    -- Audit
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (workflow_id) REFERENCES workflows(id),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_status (status),
    INDEX idx_started_at (started_at),
    INDEX idx_workflow_status (workflow_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 6. WORKFLOW TASKS (Individual action items)
-- =====================================================

CREATE TABLE IF NOT EXISTS workflow_tasks (
    id INT PRIMARY KEY AUTO_INCREMENT,

    -- Links
    ticket_id INT NOT NULL,
    step_id INT NOT NULL,

    -- Task Identification
    task_number VARCHAR(30) NOT NULL,           -- e.g., 'TSK-2024-00001'

    -- Assignment
    assigned_role VARCHAR(50),                  -- Role that should handle this
    assigned_user_id INT,                       -- Specific user (if assigned)
    claimed_by_user_id INT,                     -- User who claimed the task
    claimed_at TIMESTAMP NULL,

    -- Status
    status ENUM(
        'pending',            -- Created, waiting for action
        'claimed',            -- User has claimed but not acted
        'in_progress',        -- User is working on it
        'completed',          -- Action taken
        'skipped',            -- Skipped (e.g., parallel OR mode)
        'cancelled',          -- Ticket was cancelled
        'escalated'           -- Escalated due to SLA breach
    ) NOT NULL DEFAULT 'pending',

    -- Action Taken
    action_code VARCHAR(50),                    -- 'approve', 'reject', etc.
    action_label VARCHAR(100),                  -- Display label of action
    action_comment TEXT,                        -- Comment/notes from actor
    action_data JSON,                           -- Additional data captured
    acted_at TIMESTAMP NULL,
    acted_by_user_id INT,

    -- SLA Tracking
    due_at TIMESTAMP NULL,                      -- When task should be completed
    reminder_sent_at TIMESTAMP NULL,
    escalated_at TIMESTAMP NULL,
    is_overdue TINYINT(1) DEFAULT 0,

    -- For parallel branches
    branch_code VARCHAR(50),                    -- Which branch this task belongs to

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (ticket_id) REFERENCES workflow_tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (step_id) REFERENCES workflow_steps(id),
    INDEX idx_ticket (ticket_id),
    INDEX idx_status (status),
    INDEX idx_assigned_role (assigned_role, status),
    INDEX idx_assigned_user (assigned_user_id, status),
    INDEX idx_claimed_user (claimed_by_user_id, status),
    INDEX idx_due_at (due_at),
    INDEX idx_overdue (is_overdue, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 7. WORKFLOW TASK FORM DATA (Data collected at tasks)
-- =====================================================

CREATE TABLE IF NOT EXISTS workflow_task_form_data (
    id INT PRIMARY KEY AUTO_INCREMENT,
    task_id INT NOT NULL,
    field_id INT NOT NULL,

    -- Field value (stored as text, parsed based on field type)
    field_value TEXT,

    -- For file uploads, store file reference
    file_attachment_id INT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (task_id) REFERENCES workflow_tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (field_id) REFERENCES workflow_step_fields(id) ON DELETE CASCADE,
    UNIQUE KEY uk_task_field (task_id, field_id),
    INDEX idx_task (task_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 8. WORKFLOW HISTORY (Audit trail)
-- =====================================================

CREATE TABLE IF NOT EXISTS workflow_history (
    id INT PRIMARY KEY AUTO_INCREMENT,

    -- Links
    ticket_id INT NOT NULL,
    task_id INT,                                -- NULL for ticket-level events
    step_id INT,

    -- Event Type
    event_type ENUM(
        'ticket_created',
        'ticket_completed',
        'ticket_cancelled',
        'ticket_paused',
        'ticket_resumed',
        'task_created',
        'task_claimed',
        'task_released',       -- User released claim
        'task_reassigned',
        'task_completed',
        'task_skipped',
        'task_escalated',
        'reminder_sent',
        'transition',          -- Moved to next step
        'parallel_split',
        'parallel_join',
        'comment_added',
        'field_updated',       -- Entity field was updated
        'error'
    ) NOT NULL,

    -- Event Details
    description TEXT,

    -- Actor
    actor_type ENUM('user', 'system', 'api') NOT NULL,
    actor_user_id INT,
    actor_name VARCHAR(100),                    -- Snapshot of name at time of action

    -- State Snapshot (for debugging/audit)
    from_step_code VARCHAR(50),
    to_step_code VARCHAR(50),
    action_code VARCHAR(50),

    -- Additional Data
    event_data JSON,                            -- Any additional event-specific data

    -- Timestamp
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (ticket_id) REFERENCES workflow_tickets(id) ON DELETE CASCADE,
    INDEX idx_ticket (ticket_id),
    INDEX idx_event_type (event_type),
    INDEX idx_created_at (created_at),
    INDEX idx_actor (actor_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 9. WORKFLOW COMMENTS (Discussion thread per ticket)
-- =====================================================

CREATE TABLE IF NOT EXISTS workflow_comments (
    id INT PRIMARY KEY AUTO_INCREMENT,

    ticket_id INT NOT NULL,
    task_id INT,                                -- Optional: tied to specific task

    -- Comment Content
    comment TEXT NOT NULL,

    -- Visibility
    is_internal TINYINT(1) DEFAULT 0,           -- Internal comments not visible to external parties

    -- Author
    user_id INT NOT NULL,
    user_name VARCHAR(100),                     -- Snapshot

    -- Mentions (JSON array of user IDs)
    mentions JSON,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (ticket_id) REFERENCES workflow_tickets(id) ON DELETE CASCADE,
    INDEX idx_ticket (ticket_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 10. WORKFLOW ATTACHMENTS (Files attached to tickets)
-- =====================================================

CREATE TABLE IF NOT EXISTS workflow_attachments (
    id INT PRIMARY KEY AUTO_INCREMENT,

    ticket_id INT NOT NULL,
    task_id INT,                                -- Optional: tied to specific task

    -- File Info
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50),
    file_size INT,

    -- Uploader
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (ticket_id) REFERENCES workflow_tickets(id) ON DELETE CASCADE,
    INDEX idx_ticket (ticket_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 11. WORKFLOW SUB-TICKETS (Sub-workflow tracking)
-- =====================================================

CREATE TABLE IF NOT EXISTS workflow_sub_tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,

    -- Parent ticket and step that spawned this
    parent_ticket_id INT NOT NULL,
    parent_task_id INT NOT NULL,                -- The task that triggered sub-workflow
    parent_step_id INT NOT NULL,

    -- Child ticket
    child_ticket_id INT NOT NULL,

    -- Configuration
    -- {
    --   "wait_for_completion": true,
    --   "outcome_mapping": {
    --     "approved": "CONTINUE_TO_NEXT",
    --     "rejected": "REJECT_PARENT"
    --   }
    -- }
    config JSON,

    -- Status
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,

    FOREIGN KEY (parent_ticket_id) REFERENCES workflow_tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_task_id) REFERENCES workflow_tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (child_ticket_id) REFERENCES workflow_tickets(id) ON DELETE CASCADE,
    INDEX idx_parent (parent_ticket_id),
    INDEX idx_child (child_ticket_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 12. WORKFLOW SCHEDULED TRANSITIONS (Time-based actions)
-- =====================================================

CREATE TABLE IF NOT EXISTS workflow_scheduled_transitions (
    id INT PRIMARY KEY AUTO_INCREMENT,

    task_id INT NOT NULL,
    ticket_id INT NOT NULL,

    -- Scheduled action
    scheduled_action ENUM(
        'auto_approve',       -- Auto-approve and proceed
        'auto_reject',        -- Auto-reject
        'auto_escalate',      -- Escalate to supervisor
        'auto_cancel',        -- Cancel the ticket
        'reminder'            -- Send reminder only
    ) NOT NULL,

    -- When to execute
    scheduled_at TIMESTAMP NOT NULL,

    -- Execution status
    status ENUM('pending', 'executed', 'cancelled') DEFAULT 'pending',
    executed_at TIMESTAMP NULL,
    execution_result TEXT,

    -- Configuration (what to do when executed)
    -- {
    --   "next_step": "AUTO_APPROVED_STEP",
    --   "comment": "Auto-approved after 5 days of no action",
    --   "notify_roles": ["ADMIN"]
    -- }
    action_config JSON,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (task_id) REFERENCES workflow_tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (ticket_id) REFERENCES workflow_tickets(id) ON DELETE CASCADE,
    INDEX idx_scheduled (scheduled_at, status),
    INDEX idx_task (task_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 13. USER TASK QUEUE (Denormalized for performance)
-- =====================================================

CREATE TABLE IF NOT EXISTS user_task_queue (
    id INT PRIMARY KEY AUTO_INCREMENT,

    user_id INT NOT NULL,
    task_id INT NOT NULL,

    -- Denormalized for quick display
    ticket_id INT NOT NULL,
    ticket_number VARCHAR(30) NOT NULL,
    workflow_name VARCHAR(100) NOT NULL,
    step_name VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NOT NULL,
    entity_display VARCHAR(200),                -- e.g., "John Doe - Grade 5 Application"

    -- Status & Priority
    task_status VARCHAR(20) NOT NULL,
    priority VARCHAR(20) NOT NULL,

    -- Timing
    due_at TIMESTAMP NULL,
    is_overdue TINYINT(1) DEFAULT 0,
    is_escalated TINYINT(1) DEFAULT 0,

    -- Assignment Type
    is_role_assigned TINYINT(1) DEFAULT 1,      -- Assigned to role (vs specific user)
    assigned_role VARCHAR(50),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_user_task (user_id, task_id),
    INDEX idx_user_status (user_id, task_status),
    INDEX idx_user_priority (user_id, priority),
    INDEX idx_overdue (is_overdue),
    INDEX idx_escalated (is_escalated)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 14. ESCALATION QUEUE (For supervisors)
-- =====================================================

CREATE TABLE IF NOT EXISTS escalation_queue (
    id INT PRIMARY KEY AUTO_INCREMENT,

    task_id INT NOT NULL,
    ticket_id INT NOT NULL,

    -- Original assignee info
    original_role VARCHAR(50),
    original_user_id INT,

    -- Escalation target
    escalated_to_role VARCHAR(50) NOT NULL,
    escalated_to_user_id INT,

    -- Escalation reason
    reason ENUM('sla_breach', 'manual', 'complexity') NOT NULL,
    notes TEXT,

    -- Status
    status ENUM('pending', 'acknowledged', 'resolved') DEFAULT 'pending',
    acknowledged_at TIMESTAMP NULL,
    acknowledged_by INT,
    resolved_at TIMESTAMP NULL,
    resolved_by INT,
    resolution_notes TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (task_id) REFERENCES workflow_tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (ticket_id) REFERENCES workflow_tickets(id) ON DELETE CASCADE,
    INDEX idx_escalated_to_role (escalated_to_role, status),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SEQUENCE TABLE FOR TICKET/TASK NUMBERS
-- =====================================================

CREATE TABLE IF NOT EXISTS workflow_sequences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sequence_type VARCHAR(20) NOT NULL UNIQUE,  -- 'ticket' or 'task'
    current_year INT NOT NULL,
    last_number INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initialize sequences
INSERT INTO workflow_sequences (sequence_type, current_year, last_number) VALUES
    ('ticket', YEAR(NOW()), 0),
    ('task', YEAR(NOW()), 0)
ON DUPLICATE KEY UPDATE sequence_type = sequence_type;

-- =====================================================
-- HELPER FUNCTION: Generate Ticket Number
-- =====================================================

DELIMITER //

CREATE FUNCTION IF NOT EXISTS generate_ticket_number()
RETURNS VARCHAR(30)
DETERMINISTIC
BEGIN
    DECLARE new_number INT;
    DECLARE current_yr INT;
    DECLARE seq_year INT;

    SET current_yr = YEAR(NOW());

    -- Get current sequence year
    SELECT current_year INTO seq_year FROM workflow_sequences WHERE sequence_type = 'ticket' FOR UPDATE;

    -- Reset if new year
    IF seq_year != current_yr THEN
        UPDATE workflow_sequences SET current_year = current_yr, last_number = 1 WHERE sequence_type = 'ticket';
        SET new_number = 1;
    ELSE
        UPDATE workflow_sequences SET last_number = last_number + 1 WHERE sequence_type = 'ticket';
        SELECT last_number INTO new_number FROM workflow_sequences WHERE sequence_type = 'ticket';
    END IF;

    RETURN CONCAT('WF-', current_yr, '-', LPAD(new_number, 5, '0'));
END//

-- =====================================================
-- HELPER FUNCTION: Generate Task Number
-- =====================================================

CREATE FUNCTION IF NOT EXISTS generate_task_number()
RETURNS VARCHAR(30)
DETERMINISTIC
BEGIN
    DECLARE new_number INT;
    DECLARE current_yr INT;
    DECLARE seq_year INT;

    SET current_yr = YEAR(NOW());

    -- Get current sequence year
    SELECT current_year INTO seq_year FROM workflow_sequences WHERE sequence_type = 'task' FOR UPDATE;

    -- Reset if new year
    IF seq_year != current_yr THEN
        UPDATE workflow_sequences SET current_year = current_yr, last_number = 1 WHERE sequence_type = 'task';
        SET new_number = 1;
    ELSE
        UPDATE workflow_sequences SET last_number = last_number + 1 WHERE sequence_type = 'task';
        SELECT last_number INTO new_number FROM workflow_sequences WHERE sequence_type = 'task';
    END IF;

    RETURN CONCAT('TSK-', current_yr, '-', LPAD(new_number, 5, '0'));
END//

DELIMITER ;

-- =====================================================
-- SUMMARY
-- =====================================================
-- Tables created:
-- 1.  workflows                      - Workflow templates
-- 2.  workflow_steps                 - Steps within workflows
-- 3.  workflow_step_fields           - Custom form fields per step
-- 4.  workflow_transitions           - Connections between steps
-- 5.  workflow_tickets               - Running workflow instances
-- 6.  workflow_tasks                 - Individual task items
-- 7.  workflow_task_form_data        - Data collected at tasks
-- 8.  workflow_history               - Audit trail
-- 9.  workflow_comments              - Discussion threads
-- 10. workflow_attachments           - File attachments
-- 11. workflow_sub_tickets           - Sub-workflow tracking
-- 12. workflow_scheduled_transitions - Time-based action queue
-- 13. user_task_queue                - Denormalized task list
-- 14. escalation_queue               - Supervisor escalations
-- +   workflow_sequences             - For generating ticket/task numbers
--
-- Helper functions:
-- - generate_ticket_number()
-- - generate_task_number()
-- =====================================================
