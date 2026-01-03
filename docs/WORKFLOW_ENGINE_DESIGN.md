# Workflow Engine Design Document

## Overview

The Workflow Engine is a flexible, configurable system for managing task flows across the School Dynamics platform. It enables automated and manual routing of tasks through defined processes, supporting parallel execution, decision branching, escalations, and comprehensive audit trails.

---

## Core Concepts

### 1. Workflow
A **Workflow** is a template/blueprint that defines a complete process. Examples:
- Applicant Admission Process
- Leave Request Approval
- Fee Waiver Request
- Student Discipline Process
- Purchase Order Approval

### 2. Workflow Step
A **Step** is a single stage within a workflow. Each step defines:
- Who should act (roles)
- What actions are available
- Time limits and escalation rules
- Transition rules (what happens next)

### 3. Workflow Ticket
A **Ticket** is a running instance of a workflow, tied to a specific entity (e.g., a specific applicant). It tracks:
- Current position(s) in the workflow
- Overall status
- All history of actions taken

### 4. Workflow Task
A **Task** is a specific action item assigned to actor(s) at a particular step. When a ticket reaches a step, one or more tasks are created for the designated roles.

---

## Database Schema

### Core Tables

```sql
-- =====================================================
-- WORKFLOW DEFINITIONS (Templates)
-- =====================================================

CREATE TABLE workflows (
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
    --     "cron": "0 8 * * 1",  -- Every Monday at 8am
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
);

-- =====================================================
-- WORKFLOW STEPS (Nodes in the workflow graph)
-- =====================================================

CREATE TABLE workflow_steps (
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
    --   "rule": "campus_head",           -- Assign to head of applicant's campus
    --   "fallback_role": "ADMIN"         -- If rule fails, assign to this role
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
    --   "pass_entity": true  -- Pass same entity to sub-workflow
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
);

-- =====================================================
-- WORKFLOW TRANSITIONS (Edges connecting steps)
-- =====================================================

CREATE TABLE workflow_transitions (
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
);

-- =====================================================
-- WORKFLOW TICKETS (Running instances)
-- =====================================================

CREATE TABLE workflow_tickets (
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
);

-- =====================================================
-- WORKFLOW TASKS (Individual action items)
-- =====================================================

CREATE TABLE workflow_tasks (
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
);

-- =====================================================
-- WORKFLOW HISTORY (Audit trail)
-- =====================================================

CREATE TABLE workflow_history (
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
);

-- =====================================================
-- WORKFLOW COMMENTS (Discussion thread per ticket)
-- =====================================================

CREATE TABLE workflow_comments (
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
);

-- =====================================================
-- WORKFLOW ATTACHMENTS (Files attached to tickets)
-- =====================================================

CREATE TABLE workflow_attachments (
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
);

-- =====================================================
-- USER TASK QUEUE VIEW (Denormalized for performance)
-- =====================================================

CREATE TABLE user_task_queue (
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
);

-- =====================================================
-- ESCALATION QUEUE (For supervisors)
-- =====================================================

CREATE TABLE escalation_queue (
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
);

-- =====================================================
-- STEP FORM FIELDS (Custom data collection per step)
-- =====================================================

CREATE TABLE workflow_step_fields (
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
);

-- =====================================================
-- TASK FORM DATA (Data collected at each task)
-- =====================================================

CREATE TABLE workflow_task_form_data (
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
);

-- =====================================================
-- SUB-WORKFLOW TRACKING
-- =====================================================

CREATE TABLE workflow_sub_tickets (
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
);

-- =====================================================
-- TIME-BASED TRANSITION QUEUE
-- =====================================================

CREATE TABLE workflow_scheduled_transitions (
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
);
```

---

## Visual Representation

### Workflow Graph Structure

```
                    ┌─────────────────────────────────────────────────────────────┐
                    │                      WORKFLOW                                │
                    │  (Admission Process)                                         │
                    └─────────────────────────────────────────────────────────────┘
                                              │
                                              ▼
                                    ┌─────────────────┐
                                    │     START       │
                                    │ (Application    │
                                    │  Submitted)     │
                                    └────────┬────────┘
                                             │
                                             ▼
                                    ┌─────────────────┐
                                    │  INITIAL_REVIEW │
                                    │  (Admissions    │
                                    │   Officer)      │
                                    └────────┬────────┘
                                             │
                            ┌────────────────┼────────────────┐
                            │ Approve        │ Request Info   │ Reject
                            ▼                ▼                ▼
                   ┌─────────────────┐ ┌──────────────┐ ┌─────────────┐
                   │    DECISION     │ │ AWAIT_INFO   │ │ REJECTED    │
                   │ (Grade-based)   │ │              │ │    END      │
                   └────────┬────────┘ └──────┬───────┘ └─────────────┘
                            │                 │
            ┌───────────────┼───────────────┐ │ (Info Provided)
            │ High School   │ Primary       │ └──────────────────┐
            ▼               ▼               │                    │
   ┌─────────────────┐ ┌─────────────────┐  │                    │
   │ HS_PRINCIPAL    │ │ PS_HEAD         │  │                    │
   │ (Principal)     │ │ (Head Teacher)  │  │                    │
   └────────┬────────┘ └────────┬────────┘  │                    │
            │                   │           │                    │
            └─────────┬─────────┘           │                    │
                      ▼                     │                    │
            ┌─────────────────┐             │                    │
            │  PARALLEL_SPLIT │◄────────────┘                    │
            │ (Documents +    │                                  │
            │  Interview)     │                                  │
            └────────┬────────┘                                  │
                     │                                           │
         ┌───────────┴───────────┐                               │
         ▼                       ▼                               │
┌─────────────────┐     ┌─────────────────┐                      │
│  DOC_VERIFY     │     │  INTERVIEW      │                      │
│  (Registrar)    │     │  (Admissions)   │                      │
└────────┬────────┘     └────────┬────────┘                      │
         │                       │                               │
         └───────────┬───────────┘                               │
                     ▼                                           │
            ┌─────────────────┐                                  │
            │  PARALLEL_JOIN  │                                  │
            │  (All must      │                                  │
            │   complete)     │                                  │
            └────────┬────────┘                                  │
                     │                                           │
                     ▼                                           │
            ┌─────────────────┐                                  │
            │  FINAL_DECISION │                                  │
            │  (Principal)    │                                  │
            └────────┬────────┘                                  │
                     │                                           │
         ┌───────────┼───────────┐                               │
         │ Accept    │ Waitlist  │ Reject                        │
         ▼           ▼           ▼                               │
┌─────────────┐ ┌─────────────┐ ┌─────────────┐                  │
│  ACCEPTED   │ │ WAITLISTED  │ │ REJECTED    │                  │
│    END      │ │    END      │ │    END      │◄─────────────────┘
└─────────────┘ └─────────────┘ └─────────────┘
```

---

## Key Features

### 1. Trigger Types

| Type | Description | Example |
|------|-------------|---------|
| `auto` | Triggered by database/application events | When applicant status = 'submitted' |
| `manual` | User initiates workflow | Staff clicks "Start Admission Review" |
| `api` | External system calls API | Third-party application portal |
| `scheduled` | Time-based trigger | Daily check for stale applications |

### 2. Step Types

| Type | Description | Actor Required |
|------|-------------|----------------|
| `start` | Entry point | No |
| `task` | Normal task requiring human action | Yes |
| `decision` | Branching based on conditions/choice | Maybe |
| `parallel_split` | Fork into multiple branches | No |
| `parallel_join` | Merge branches back together | No |
| `auto` | Automatic system action | No |
| `sub_workflow` | Invoke another workflow as sub-process | No |
| `end` | Terminal state | No |

### 3. Available Actions

Standard actions that can be configured per step:

| Action | Description |
|--------|-------------|
| `approve` | Approve and move forward |
| `reject` | Reject and end/move to rejection path |
| `request_info` | Request more information |
| `escalate` | Escalate to supervisor |
| `delegate` | Reassign to another user |
| `hold` | Put on hold temporarily |
| `comment` | Add comment without action |

### 4. Parallel Execution Modes

| Mode | Description |
|------|-------------|
| `all` | ALL parallel branches must complete before joining |
| `any` | ANY one branch completing triggers the join |
| `custom` | Custom logic defined in configuration |

### 5. SLA & Escalation

- **SLA Hours**: Expected completion time per step
- **Reminder**: Notification sent X hours before SLA breach
- **Escalation**: Alert supervisor when SLA is breached
- **Note**: Task stays with original assignee (per requirements)

### 6. Time-Based Auto-Transitions

| Action | Description |
|--------|-------------|
| `auto_approve` | After X hours, auto-approve and move to next step |
| `auto_reject` | After X hours, auto-reject and move to rejection step |
| `auto_escalate` | After X hours, escalate but keep task with assignee |
| `auto_cancel` | After X hours, cancel the entire ticket |

### 7. Step Form Fields

Each step can define custom form fields that actors must fill when completing a task:

**Example: Interview Step Form**
```json
{
  "step_code": "INTERVIEW",
  "require_comment": true,
  "comment_label": "Interview Notes",
  "fields": [
    {
      "field_code": "interview_score",
      "field_label": "Interview Score",
      "field_type": "score",
      "is_required": true,
      "min_value": 0,
      "max_value": 100
    },
    {
      "field_code": "interviewer_recommendation",
      "field_label": "Recommendation",
      "field_type": "select",
      "is_required": true,
      "options": [
        {"value": "strongly_recommend", "label": "Strongly Recommend"},
        {"value": "recommend", "label": "Recommend"},
        {"value": "neutral", "label": "Neutral"},
        {"value": "not_recommend", "label": "Do Not Recommend"}
      ]
    },
    {
      "field_code": "supporting_documents",
      "field_label": "Supporting Documents",
      "field_type": "file",
      "is_required": false,
      "file_config": {
        "allowed_types": ["pdf", "jpg", "png"],
        "max_size_mb": 10,
        "multiple": true
      }
    }
  ]
}
```

### 8. Sub-Workflows

Sub-workflows allow reusable processes to be invoked from parent workflows:

**Example: Document Verification Sub-Workflow**
```
Parent Workflow: Applicant Admission
    │
    ├── Step: INITIAL_REVIEW
    │
    ├── Step: DOCUMENT_CHECK (type: sub_workflow)
    │       └── Invokes: DOCUMENT_VERIFICATION workflow
    │           ├── Step: VERIFY_BIRTH_CERT
    │           ├── Step: VERIFY_TRANSCRIPTS
    │           └── Step: VERIFICATION_COMPLETE (outcome: approved/rejected)
    │
    ├── (Waits for sub-workflow to complete)
    │
    └── Step: FINAL_DECISION (continues based on sub-workflow outcome)
```

---

## API Design

### WorkflowEngine Class Methods

```php
class WorkflowEngine
{
    // Workflow Management
    public function startWorkflow(string $workflowCode, string $entityType, int $entityId, ?int $userId = null): WorkflowTicket;
    public function startSubWorkflow(int $parentTicketId, int $parentTaskId, string $subWorkflowCode): WorkflowTicket;
    public function cancelTicket(int $ticketId, string $reason, int $userId): bool;
    public function pauseTicket(int $ticketId, int $userId): bool;
    public function resumeTicket(int $ticketId, int $userId): bool;

    // Task Actions
    public function claimTask(int $taskId, int $userId): bool;
    public function releaseTask(int $taskId, int $userId): bool;
    public function completeTask(int $taskId, string $actionCode, int $userId, ?string $comment = null, ?array $formData = null, ?array $files = null): bool;
    public function reassignTask(int $taskId, int $newUserId, int $reassignedBy): bool;

    // Task Queries
    public function getTasksForUser(int $userId, ?string $status = null): array;
    public function getTasksForRole(string $role, ?string $status = null): array;
    public function getEscalatedTasks(int $supervisorId): array;
    public function getOverdueTasks(): array;
    public function getTaskFormFields(int $taskId): array;

    // Ticket Queries
    public function getTicketsByEntity(string $entityType, int $entityId): array;
    public function getTicketHistory(int $ticketId): array;
    public function getTicketTimeline(int $ticketId): array;
    public function getSubWorkflows(int $ticketId): array;
    public function getParentWorkflow(int $ticketId): ?array;

    // Workflow Queries
    public function getAvailableWorkflows(string $entityType): array;
    public function getWorkflowSteps(int $workflowId): array;
    public function getStepFormFields(int $stepId): array;

    // Background Processing (cron jobs)
    public function processScheduledTriggers(): void;
    public function processReminders(): void;
    public function processEscalations(): void;
    public function processAutoTransitions(): void;

    // Form Data
    public function saveTaskFormData(int $taskId, array $formData): bool;
    public function getTaskFormData(int $taskId): array;
}
```

---

## UI Components

### 1. Task Inbox (My Tasks)
- List of tasks assigned to current user (by role or direct)
- Filters: Status, Priority, Overdue, Workflow Type
- Quick actions: Claim, Complete, View Details

### 2. Escalations Tab (For Supervisors)
- Tasks that have breached SLA for their reports
- Acknowledge escalation
- Add notes/guidance

### 3. Workflow History (Per Entity)
- Timeline view of all actions taken
- Who did what, when
- Comments and attachments

### 4. Workflow Designer (Admin)
- Visual drag-and-drop workflow builder
- Configure steps, transitions, and conditions
- Test workflow execution

### 5. Workflow Dashboard (Admin)
- Active tickets by workflow
- SLA compliance metrics
- Bottleneck identification

---

## Integration Points

### 1. Entity Integration (Polymorphic)

The workflow system uses a polymorphic relationship pattern:

```php
// Finding workflows for an entity
$tickets = WorkflowTicket::where('entity_type', 'applicant')
                         ->where('entity_id', $applicantId)
                         ->get();

// Starting a workflow
$engine->startWorkflow('APPLICANT_ADMISSION', 'applicant', $applicantId);
```

### 2. Trigger Integration

Auto-triggers can be implemented via:
- Application-level hooks (recommended)
- Database triggers (for insert/update events)
- Scheduled cron jobs

```php
// In ApplicantsController after status change
if ($newStatus === 'submitted') {
    $workflowEngine->startWorkflow('APPLICANT_ADMISSION', 'applicant', $applicantId);
}
```

### 3. Notification Integration

The workflow engine should integrate with the existing notification system:
- Task assignments → Notification
- Reminders → Notification
- Escalations → Notification
- Workflow completion → Notification

---

## Sample Workflow Definitions

### 1. Applicant Admission Workflow

```json
{
  "code": "APPLICANT_ADMISSION",
  "name": "Applicant Admission Process",
  "entity_type": "applicant",
  "trigger_config": {
    "type": "auto",
    "auto_trigger": {
      "event": "update",
      "table": "applicants",
      "conditions": [
        {"field": "status", "operator": "=", "value": "submitted"}
      ]
    }
  },
  "steps": [
    {
      "code": "INITIAL_REVIEW",
      "name": "Initial Application Review",
      "step_type": "task",
      "actor_roles": ["ADMISSIONS_OFFICER"],
      "sla_hours": 48,
      "available_actions": [
        {"code": "approve", "label": "Approve for Review", "next_step": "GRADE_DECISION"},
        {"code": "request_info", "label": "Request More Info", "next_step": "AWAITING_INFO"},
        {"code": "reject", "label": "Reject", "next_step": "REJECTED_END"}
      ]
    },
    {
      "code": "GRADE_DECISION",
      "name": "Route by Grade Level",
      "step_type": "decision",
      "decision_config": {
        "mode": "field_condition",
        "conditions": [
          {"field": "grade_category", "operator": "=", "value": "High School", "next_step": "HS_REVIEW"},
          {"field": "grade_category", "operator": "=", "value": "Primary", "next_step": "PS_REVIEW"}
        ],
        "default_next_step": "GENERAL_REVIEW"
      }
    }
  ]
}
```

### 2. Leave Request Workflow

```json
{
  "code": "STAFF_LEAVE_REQUEST",
  "name": "Staff Leave Request",
  "entity_type": "leave_request",
  "trigger_config": {
    "type": "auto",
    "auto_trigger": {
      "event": "insert",
      "table": "leave_requests"
    }
  },
  "steps": [
    {
      "code": "HOD_APPROVAL",
      "name": "Head of Department Approval",
      "step_type": "task",
      "actor_roles": ["HOD"],
      "auto_assign_config": {
        "rule": "department_head",
        "fallback_role": "ADMIN"
      },
      "sla_hours": 24,
      "available_actions": [
        {"code": "approve", "label": "Approve", "next_step": "HR_PROCESSING"},
        {"code": "reject", "label": "Reject", "next_step": "REJECTED_END"}
      ]
    }
  ]
}
```

---

## Implementation Phases

### Phase 1: Core Engine
- [ ] Database schema creation
- [ ] WorkflowEngine core class
- [ ] Basic CRUD for workflows and steps
- [ ] Ticket/Task creation and management
- [ ] History logging

### Phase 2: Task Management UI
- [ ] My Tasks inbox
- [ ] Task detail view
- [ ] Claim/Complete actions
- [ ] Comments and attachments

### Phase 3: Triggers & Automation
- [ ] Application-level trigger hooks
- [ ] Scheduled trigger processing
- [ ] Auto-complete steps

### Phase 4: Parallel Processing
- [ ] Parallel split/join logic
- [ ] Branch tracking
- [ ] Join mode handling

### Phase 5: SLA & Escalations
- [ ] SLA tracking
- [ ] Reminder processing
- [ ] Escalation queue
- [ ] Supervisor view

### Phase 6: Workflow Designer
- [ ] Visual workflow builder
- [ ] Step configuration UI
- [ ] Transition configuration
- [ ] Testing mode

### Phase 7: Analytics & Reporting
- [ ] Workflow performance metrics
- [ ] SLA compliance reports
- [ ] Bottleneck analysis
- [ ] User workload reports

---

## Security Considerations

1. **Role-based Access**: Only users with appropriate roles can see/act on tasks
2. **Audit Trail**: Every action is logged with user and timestamp
3. **Data Validation**: All transitions must be valid according to workflow definition
4. **Ticket Isolation**: Users can only access tickets they're authorized for

---

## Design Decisions (Confirmed)

### 1. Workflow Versioning
**Decision**: Editing a workflow SHOULD affect running tickets.

- When a workflow is modified, active tickets follow the new definition
- No version snapshots - single source of truth
- History log captures the state at time of each action for audit purposes
- If a step is removed that has active tasks, those tasks must be manually resolved

### 2. Sub-Workflows
**Decision**: YES - Sub-workflows are supported.

A step can invoke another workflow as a "sub-workflow":
- Parent ticket waits for sub-workflow to complete
- Sub-workflow outcome can determine parent's next step
- Useful for reusable processes (e.g., "Document Verification" used by multiple parent workflows)

### 3. Time-Based Transitions
**Decision**: YES - Configurable per step.

Options include:
- **Auto-approve**: After X days without action, auto-approve and proceed
- **Auto-reject**: After X days without action, auto-reject
- **Auto-escalate**: After X days, escalate AND continue waiting
- **Auto-cancel**: After X days, cancel the ticket entirely

### 4. Step Forms
**Decision**: YES - Steps can collect additional data.

Each step can define:
- **Official comment** (always available, may be required)
- **Custom form fields** configured per step:
  - Text input
  - Textarea
  - Number
  - Date
  - Select/Dropdown
  - Checkbox
  - File upload
  - Rating/Score

---

## Appendix A: Complete Table Summary

| Table | Purpose | Key Fields |
|-------|---------|------------|
| `workflows` | Workflow templates | code, name, entity_type, trigger_config |
| `workflow_steps` | Steps within workflows | step_type, actor_roles, available_actions, sla_hours |
| `workflow_step_fields` | Custom form fields per step | field_code, field_type, is_required, options |
| `workflow_transitions` | Connections between steps | from_step_id, to_step_id, action_code |
| `workflow_tickets` | Running workflow instances | ticket_number, entity_type, entity_id, status |
| `workflow_tasks` | Individual task items | ticket_id, step_id, assigned_role, status |
| `workflow_task_form_data` | Data collected at tasks | task_id, field_id, field_value |
| `workflow_history` | Audit trail | ticket_id, event_type, actor_user_id |
| `workflow_comments` | Discussion threads | ticket_id, comment, user_id |
| `workflow_attachments` | File attachments | ticket_id, file_name, file_path |
| `workflow_sub_tickets` | Sub-workflow tracking | parent_ticket_id, child_ticket_id |
| `workflow_scheduled_transitions` | Time-based actions | task_id, scheduled_action, scheduled_at |
| `user_task_queue` | Denormalized task list | user_id, task_id, entity_display |
| `escalation_queue` | Supervisor escalations | task_id, escalated_to_role, status |

**Total: 14 tables**

---

## Appendix B: Glossary

| Term | Definition |
|------|------------|
| Workflow | A template defining a business process |
| Step | A single stage in a workflow |
| Ticket | A running instance of a workflow |
| Task | An action item at a specific step |
| Actor | The user/role responsible for a task |
| Transition | Movement from one step to another |
| SLA | Service Level Agreement (time limit) |
| Escalation | Alerting supervisor when SLA is breached |
| Sub-workflow | A workflow invoked by another workflow |
| Form Field | Custom data collection element on a step |
| Auto-transition | Time-based automatic action |
