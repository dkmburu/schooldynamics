# Parent Portal - Phase 2 Enhancements

## Overview
This document outlines the Phase 2 enhancements to the Parent Portal, focusing on improving communication, engagement, and real-time information access for guardians.

---

## 1. Enhanced Notifications System

### Current State
- Basic notification display in dropdown
- No unread count or priority indicators
- No action-required tracking

### Proposed Enhancements

#### 1.1 Notification Badge & Count
- **Icon Label**: Change "Alerts" to "Notifications"
- **Unread Badge**: Display count bubble showing number of unread notifications
- **Real-time Updates**: Badge updates as new notifications arrive

#### 1.2 Notification Categorization & Severity

**Severity Levels:**
- `info` - General information (blue icon)
- `warning` - Important but not urgent (yellow/orange icon)
- `urgent` - Requires attention (red icon)
- `success` - Positive updates (green icon)

**Scope:**
- `school` - School-wide announcements
- `grade` - Grade/class-specific notifications
- `student` - Individual student notifications

**Icon Coding System:**
- 📢 General announcements
- 📚 Academic/learning related
- 💰 Fees/financial
- 🚌 Transport
- 🏥 Health
- 📅 Events/calendar
- ⚠️ Urgent action required
- ✅ Confirmations/completions

#### 1.3 Action-Required Notifications

**Features:**
- Timeline/countdown for time-sensitive items
  - "2 days remaining" - green
  - "1 day remaining" - orange
  - "Due today" - red
  - "Overdue" - dark red with urgent icon
- Action buttons within notification (RSVP, Acknowledge, Pay, etc.)
- Auto-dismiss after action completed
- Reminder escalation (e.g., send SMS if no action after 24 hours)

#### 1.4 Dashboard Highlights

**Home Page:**
- Prominent notification cards for urgent/action-required items
- Quick action buttons
- Visual priority indicators

**Student Cards:**
- Badge overlay on student card if notification affects that student
- Grade/class-level notifications show on all students in that grade
- Color-coded borders based on severity

### Database Schema

```sql
-- Lookup: Action types for notifications
CREATE TABLE notification_action_types (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) UNIQUE NOT NULL,
    name VARCHAR(50) NOT NULL,
    description TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE
);

-- Enhanced notifications table
CREATE TABLE parent_notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_account_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NULL, -- NULL = all children
    grade_id INT UNSIGNED NULL, -- Grade-level notification

    notification_type_id INT UNSIGNED NOT NULL,
    notification_scope_id TINYINT UNSIGNED NOT NULL,
    severity_level_id TINYINT UNSIGNED NOT NULL,

    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    icon_code VARCHAR(50) NULL, -- Emoji or icon class

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
    INDEX idx_action_deadline (action_deadline, action_completed_at)
);
```

---

## 2. Unified Calendar

### Features

#### 2.1 Multi-Student Calendar View
- **Unified Display**: Single calendar showing events for all guardian's children
- **Color Coding**: Each child assigned a unique color
- **Conflict Detection**: Visual indicator when multiple children have events at same time
- **Filter Options**:
  - View all students
  - View individual student
  - View by event type

#### 2.2 Event Types
- School-wide events (holidays, parent-teacher days, cultural days)
- Grade/class-specific events (field trips, exams, presentations)
- Student-specific events (medical appointments, counseling sessions)
- Transport schedule changes
- Fee payment deadlines
- Sports/extracurricular activities

#### 2.3 RSVP & Action Required
- **RSVP Indicator**: Visual badge on calendar event
- **Countdown Timer**: Shows time remaining to respond
- **Quick RSVP**: Click event to RSVP directly from calendar
- **Attendance Confirmation**: For events requiring attendance confirmation
- **Waitlist**: Some events may have capacity limits

#### 2.4 Calendar Views
- Month view (default)
- Week view
- Day view
- List/Agenda view

### Database Schema

```sql
-- Lookup: Event types
CREATE TABLE event_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    icon VARCHAR(50) NULL,
    default_color VARCHAR(7) NULL, -- Hex color
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Lookup: Event scopes
CREATE TABLE event_scopes (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(50) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE
);

-- Lookup: RSVP response types
CREATE TABLE rsvp_response_types (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(50) NOT NULL,
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE
);

-- Lookup: Calendar item types
CREATE TABLE calendar_item_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(50) NULL,
    is_active BOOLEAN DEFAULT TRUE
);

-- School events/calendar
CREATE TABLE school_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id INT UNSIGNED NOT NULL,
    event_type_id INT UNSIGNED NOT NULL,
    event_scope_id TINYINT UNSIGNED NOT NULL,
    grade_id INT UNSIGNED NULL,
    stream_id INT UNSIGNED NULL,

    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    location VARCHAR(255) NULL,

    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME NOT NULL,
    all_day BOOLEAN DEFAULT FALSE,

    -- RSVP settings
    requires_rsvp BOOLEAN DEFAULT FALSE,
    rsvp_deadline DATETIME NULL,
    max_attendees INT NULL,

    color_code VARCHAR(7) NULL, -- Hex color for calendar display
    icon VARCHAR(50) NULL,

    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (event_type_id) REFERENCES event_types(id),
    FOREIGN KEY (event_scope_id) REFERENCES event_scopes(id),
    FOREIGN KEY (grade_id) REFERENCES grades(id) ON DELETE CASCADE,
    FOREIGN KEY (stream_id) REFERENCES streams(id) ON DELETE CASCADE,
    INDEX idx_school_dates (school_id, start_datetime, end_datetime),
    INDEX idx_grade_dates (grade_id, start_datetime)
);

-- Event RSVPs
CREATE TABLE event_rsvps (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id BIGINT UNSIGNED NOT NULL,
    parent_account_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,

    rsvp_response_type_id TINYINT UNSIGNED NOT NULL,
    num_guests INT DEFAULT 0, -- Additional guests (if allowed)
    notes TEXT NULL,

    responded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_rsvp (event_id, student_id),
    FOREIGN KEY (event_id) REFERENCES school_events(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_account_id) REFERENCES parent_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (rsvp_response_type_id) REFERENCES rsvp_response_types(id)
);

-- Student-specific calendar items
CREATE TABLE student_calendar_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    event_id BIGINT UNSIGNED NULL, -- Links to school_events if applicable

    calendar_item_type_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,

    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME NULL,

    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES school_events(id) ON DELETE CASCADE,
    FOREIGN KEY (calendar_item_type_id) REFERENCES calendar_item_types(id)
);
```

---

## 3. Feedback & Communication System

### Features

#### 3.1 School-Initiated Feedback Requests

**Survey/Poll Features:**
- Rating scales (1-5 stars, 1-10 numeric)
- Multiple choice questions
- Free-text responses
- Event-specific feedback (e.g., "Rate the Cultural Day")
- Expiry dates - auto-hide after expiration
- Anonymous option (configurable per survey)
- Results visibility (optional - show aggregate results to parents)

**Display:**
- Dedicated "Feedback" tab in navigation
- Count badge showing pending feedback requests
- Expiry countdown for each request
- Visual completion status

#### 3.2 Parent-Initiated Communication

**Ticket/Issue Submission:**
- **Category Selection**:
  - 💰 Fees & Payments
  - 🍽️ Food & Nutrition
  - 📚 Learning & Academics
  - 🚌 Transport
  - 🏥 Health & Medical
  - 🏃 Sports & Activities
  - 👥 Behavior & Discipline
  - 🔧 Other/General

- **Ticket Fields**:
  - Subject/Title
  - Description
  - Affected student(s)
  - Urgency level (parent's perspective)
  - Attachments (photos, documents)
  - Preferred response method (SMS, Email, Call)

- **Ticket Tracking**:
  - Unique ticket number
  - Status tracking (Submitted, Acknowledged, In Progress, Resolved, Closed)
  - SMS confirmation with ticket number
  - Response notifications
  - Escalation if no response within SLA

- **Routing**:
  - Auto-route to appropriate department/desk
  - Assign to relevant staff member
  - CC class teacher for academic issues

#### 3.3 Contact Directory

**School Contact Information:**
- Main office phone & email
- Principal/Head Teacher
- Accounts/Finance office
- Medical/Health office
- Transport coordinator
- Counseling services

**Class Teacher Contacts:**
- For each child, display their class teacher(s)
- Official contact hours
- Email address
- Office phone (if available)
- Subject teachers (for secondary schools)

**Emergency Contacts:**
- 24/7 emergency line
- Security desk
- Medical emergency protocol

### Database Schema

#### Integration with Existing Workflow System

**Note:** The SchoolDynamics system already has a comprehensive workflow engine with the following tables:
- `workflows` - Workflow definitions
- `workflow_steps` - Steps within workflows
- `workflow_tickets` - Workflow instances
- `workflow_tasks` - Tasks assigned to users
- `workflow_comments` - Comments on tickets
- `workflow_attachments` - File attachments
- `workflow_history` - Audit trail

**Integration Strategy:**
We will leverage the existing workflow system and create a "Parent Feedback" workflow with the following configuration:

1. **New Entity Type**: `parent_feedback`
2. **Workflow Code**: `PARENT_FEEDBACK_WORKFLOW`
3. **Category-Based Routing**: Each parent ticket category (Fees, Food, Learning, etc.) routes to the appropriate department

```sql
-- Lookup: Notification types
CREATE TABLE notification_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    icon VARCHAR(50) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Lookup: Notification severity levels
CREATE TABLE notification_severity_levels (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(50) NOT NULL,
    color VARCHAR(20) NULL, -- CSS color class
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE
);

-- Lookup: Notification scopes
CREATE TABLE notification_scopes (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(50) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE
);

-- Lookup: Feedback request scopes
CREATE TABLE feedback_request_scopes (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) UNIQUE NOT NULL,
    name VARCHAR(50) NOT NULL,
    description TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE
);

-- Lookup: Feedback types
CREATE TABLE feedback_types (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) UNIQUE NOT NULL,
    name VARCHAR(50) NOT NULL,
    description TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE
);

-- Lookup: Parent feedback categories
CREATE TABLE parent_feedback_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    icon VARCHAR(50) NULL,

    -- Default routing
    default_department VARCHAR(100) NULL,
    default_assigned_role VARCHAR(50) NULL,
    default_sla_hours INT NULL,
    default_escalation_hours INT NULL,
    requires_urgent_notification BOOLEAN DEFAULT FALSE,

    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Lookup: Contact methods
CREATE TABLE contact_methods (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(50) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE
);

-- Lookup: Urgency levels
CREATE TABLE urgency_levels (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(50) NOT NULL,
    color VARCHAR(20) NULL,
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE
);

-- Feedback/survey requests from school
CREATE TABLE feedback_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id INT UNSIGNED NOT NULL,
    feedback_request_scope_id TINYINT UNSIGNED NOT NULL,
    grade_id INT UNSIGNED NULL,

    title VARCHAR(255) NOT NULL,
    description TEXT NULL,

    feedback_type_id TINYINT UNSIGNED NOT NULL,
    event_reference VARCHAR(255) NULL, -- e.g., "Cultural Day 2025"

    -- Survey configuration (JSON)
    questions JSON NOT NULL, -- Array of question objects
    allow_anonymous BOOLEAN DEFAULT FALSE,
    show_results BOOLEAN DEFAULT FALSE,

    start_date DATETIME NOT NULL,
    expiry_date DATETIME NOT NULL,

    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_active (school_id, expiry_date),
    FOREIGN KEY (feedback_request_scope_id) REFERENCES feedback_request_scopes(id),
    FOREIGN KEY (feedback_type_id) REFERENCES feedback_types(id),
    FOREIGN KEY (grade_id) REFERENCES grades(id) ON DELETE CASCADE
);

-- Parent responses to feedback requests
CREATE TABLE feedback_responses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    feedback_request_id BIGINT UNSIGNED NOT NULL,
    parent_account_id BIGINT UNSIGNED NULL, -- NULL if anonymous

    responses JSON NOT NULL, -- Array of answers matching questions

    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (feedback_request_id) REFERENCES feedback_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_account_id) REFERENCES parent_accounts(id) ON DELETE SET NULL
);

-- Parent feedback tickets - Links to workflow system
-- This table stores parent-specific data that links to workflow_tickets
CREATE TABLE parent_feedback_tickets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workflow_ticket_id INT NOT NULL, -- Links to workflow_tickets table

    parent_account_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NULL, -- NULL if general inquiry

    category_id INT UNSIGNED NOT NULL,
    subject VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    urgency_level_id TINYINT UNSIGNED NOT NULL,

    -- Communication preferences
    preferred_contact_method_id TINYINT UNSIGNED NOT NULL,

    -- SMS notification tracking
    confirmation_sms_sent BOOLEAN DEFAULT FALSE,
    confirmation_sms_sent_at TIMESTAMP NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_workflow_ticket (workflow_ticket_id),
    FOREIGN KEY (workflow_ticket_id) REFERENCES workflow_tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_account_id) REFERENCES parent_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES parent_feedback_categories(id),
    FOREIGN KEY (urgency_level_id) REFERENCES urgency_levels(id),
    FOREIGN KEY (preferred_contact_method_id) REFERENCES contact_methods(id),

    INDEX idx_parent_tickets (parent_account_id, category_id),
    INDEX idx_category (category_id)
);

-- NOTE: The following are handled by existing workflow tables:
-- - Ticket status tracking: workflow_tickets.status
-- - Task assignments: workflow_tasks (assigned_user_id, assigned_role)
-- - Comments/Updates: workflow_comments (with is_internal flag for staff-only notes)
-- - Attachments: workflow_attachments
-- - History/Audit: workflow_history
-- - Ticket number: workflow_tickets.ticket_number

-- Lookup: Contact types
CREATE TABLE contact_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    icon VARCHAR(50) NULL,
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE
);

-- School contact directory
CREATE TABLE school_contacts (
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
    INDEX idx_school_active (school_id, is_active, display_order)
);
```

---

## 4. Transport Tracking

### Features

#### 4.1 Pickup & Drop-off Tracking

**Current Implementation:**
- Display pickup time and drop-off time for each child
- Historical log of pickups/drop-offs
- Filter by date range
- Identify late pickups/drop-offs

**Data Display:**
- **Student Transport Status**: Subscribed/Not subscribed
- **Route Information**: Route name, bus number
- **Schedule**: Expected pickup/drop-off times
- **Actual Times**:
  - Picked up at: [timestamp]
  - Dropped off at: [timestamp]
  - Duration of trip
- **Status Indicators**:
  - ✅ On time (green)
  - ⚠️ Delayed (yellow)
  - ❌ Missed (red)
  - 🚌 In transit (blue)

**Alerts:**
- Push notification when child picked up
- Push notification when child dropped off
- Alert if delayed beyond threshold (e.g., 15 mins)

#### 4.2 Map View (Future Enhancement)

**Phase 2A (Current):**
- Static map showing route
- Pickup/drop-off points marked
- School location

**Phase 2B (Future):**
- Real-time bus location tracking
- ETA to pickup point
- Live route visualization
- Geofencing alerts

#### 4.3 Driver Contact Information

**Display:**
- Driver name
- Official phone number
- Bus/vehicle registration number
- Route supervisor contact (if applicable)
- Transport coordinator contact

**Emergency Protocol:**
- Escalation contact if driver unreachable
- Emergency hotline

### Database Schema

```sql
-- Lookup: Transport trip types
CREATE TABLE transport_trip_types (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(50) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE
);

-- Lookup: Transport log status
CREATE TABLE transport_log_statuses (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) UNIQUE NOT NULL,
    name VARCHAR(50) NOT NULL,
    color VARCHAR(20) NULL, -- CSS class for status color
    is_positive BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE
);

-- Transport routes
CREATE TABLE transport_routes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id INT UNSIGNED NOT NULL,

    route_name VARCHAR(100) NOT NULL,
    route_code VARCHAR(20) NOT NULL,

    driver_name VARCHAR(255) NULL,
    driver_phone VARCHAR(20) NULL,
    driver_license VARCHAR(50) NULL,

    vehicle_registration VARCHAR(50) NULL,
    vehicle_capacity INT NULL,

    supervisor_name VARCHAR(255) NULL,
    supervisor_phone VARCHAR(20) NULL,

    -- Route details (JSON or separate table)
    route_stops JSON NULL, -- Array of {stop_name, latitude, longitude, sequence}

    morning_start_time TIME NULL,
    evening_start_time TIME NULL,

    is_active BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_school_active (school_id, is_active)
);

-- Student transport subscriptions
CREATE TABLE student_transport (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    route_id BIGINT UNSIGNED NOT NULL,

    pickup_stop VARCHAR(255) NOT NULL,
    dropoff_stop VARCHAR(255) NOT NULL,

    expected_pickup_time TIME NULL,
    expected_dropoff_time TIME NULL,

    subscription_start_date DATE NOT NULL,
    subscription_end_date DATE NULL,

    is_active BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (route_id) REFERENCES transport_routes(id) ON DELETE CASCADE,
    INDEX idx_student_active (student_id, is_active)
);

-- Daily transport logs
CREATE TABLE transport_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_transport_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    route_id BIGINT UNSIGNED NOT NULL,

    log_date DATE NOT NULL,
    trip_type_id TINYINT UNSIGNED NOT NULL,

    expected_time TIME NULL,
    actual_time TIME NULL,

    status_id TINYINT UNSIGNED NOT NULL,
    delay_minutes INT DEFAULT 0,

    -- Location (for future GPS tracking)
    latitude DECIMAL(10, 8) NULL,
    longitude DECIMAL(11, 8) NULL,

    notes TEXT NULL,
    logged_by INT UNSIGNED NULL, -- Driver or transport coordinator

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (student_transport_id) REFERENCES student_transport(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (route_id) REFERENCES transport_routes(id) ON DELETE CASCADE,
    FOREIGN KEY (trip_type_id) REFERENCES transport_trip_types(id),
    FOREIGN KEY (status_id) REFERENCES transport_log_statuses(id),

    INDEX idx_student_date (student_id, log_date),
    INDEX idx_route_date (route_id, log_date),
    INDEX idx_trip_type_status (trip_type_id, status_id)
);
```

---

## UI/UX Design Guidelines

### Navigation Updates

**Current:**
```
[Home] [Notifications ▼] [Settings]
```

**Proposed:**
```
[Home] [Calendar] [Notifications 🔴3] [Feedback 🔴2] [Transport]

User Menu ▼:
- Profile
- Contact School
- Help
- Logout
```

### Color Scheme

**Student Color Codes (for calendar):**
- Student 1: Blue (#3B82F6)
- Student 2: Green (#10B981)
- Student 3: Purple (#8B5CF6)
- Student 4: Orange (#F59E0B)
- Student 5: Pink (#EC4899)

**Severity Colors:**
- Info: Blue (#3B82F6)
- Warning: Orange (#F59E0B)
- Urgent: Red (#EF4444)
- Success: Green (#10B981)

### Responsive Design
- Mobile-first approach
- Touch-friendly buttons (min 44px height)
- Swipe gestures for calendar navigation
- Bottom navigation on mobile
- Collapsible sections for long content

---

## Implementation Phases

### Phase 2A (Priority 1) - Weeks 1-2
1. Enhanced notifications system
   - Database migration
   - Backend API for notifications
   - Badge counter implementation
   - Severity & action-required features

2. Contact directory
   - Simple display of school contacts
   - Class teacher information per student

### Phase 2B (Priority 2) - Weeks 3-4
3. Feedback system
   - School-initiated surveys
   - Parent ticket submission
   - Ticket tracking UI
   - Email/SMS integration

4. Calendar - Basic implementation
   - Database schema
   - Month view calendar
   - Event display
   - Basic RSVP

### Phase 2C (Priority 3) - Weeks 5-6
5. Transport tracking
   - Database schema
   - Pickup/drop-off log display
   - Driver contact information
   - Historical tracking

6. Calendar - Advanced features
   - Multi-student color coding
   - Conflict detection
   - Week/day views
   - Export to Google Calendar/iCal

### Phase 2D (Future)
7. Real-time transport tracking with GPS
8. Push notifications (web push/mobile app)
9. Parent mobile app (iOS/Android)
10. In-app messaging with teachers

---

## Technical Considerations

### Performance
- Index all foreign keys and date columns
- Cache frequently accessed data (school contacts, active routes)
- Paginate long lists (notifications, transport logs)
- Lazy-load calendar events (load only visible month)

### Security
- Validate parent can only access their own children's data
- Sanitize all user inputs (ticket descriptions, feedback responses)
- Rate-limit ticket submissions (prevent spam)
- Encrypt sensitive data (phone numbers in logs)

### Scalability
- Use queue system for sending notifications
- Batch process transport logs
- Archive old notifications/tickets
- Optimize calendar queries with date range filters

### Integration Points
- SMS gateway for ticket confirmations & transport alerts
- Email service for notifications
- Future: Push notification service
- Future: GPS tracking service for transport

---

## Success Metrics

### Engagement
- Notification open rate
- RSVP completion rate
- Feedback survey completion rate
- Ticket submission rate

### Satisfaction
- Parent satisfaction scores (via surveys)
- Response time to tickets (SLA compliance)
- Ticket resolution rate

### Efficiency
- Reduction in phone calls to school office
- Faster issue resolution times
- Increased event attendance (via RSVP tracking)

---

## Migration Strategy

### Data Migration
1. Create new tables with migrations
2. Seed sample data for testing
3. Populate school_contacts with default data
4. No data migration needed (new features)

### Rollout Plan
1. **Soft Launch**: Enable for pilot group of parents (1-2 classes)
2. **Feedback Collection**: Gather feedback for 1 week
3. **Iteration**: Fix bugs and improve UX
4. **Full Launch**: Enable for all parents
5. **Training**: Provide user guide and video tutorials

---

## Open Questions & Decisions Needed

1. **Notification Delivery**:
   - Email and/or SMS for urgent notifications?
   - In-app only for non-urgent?

2. **Ticket SLA**:
   - What's the expected response time per category?
   - Auto-escalation rules?

3. **Calendar Permissions**:
   - Can parents add their own events?
   - Or read-only school calendar?

4. **Transport Alerts**:
   - Real-time SMS on pickup/dropoff?
   - Or just in-app notifications?

5. **Feedback Surveys**:
   - Who can create surveys (admin only, teachers, dept heads)?
   - Approval workflow needed?

6. **Anonymous Feedback**:
   - Allow fully anonymous submissions?
   - Or track but hide from aggregate results?

---

## Parent Feedback Workflow Configuration

### Workflow Definition

```sql
-- Insert the Parent Feedback workflow
INSERT INTO workflows (code, name, description, category, entity_type, allow_multiple_instances, is_active, created_at)
VALUES (
    'PARENT_FEEDBACK_WORKFLOW',
    'Parent Feedback & Support Requests',
    'Handles parent-initiated feedback, inquiries, and support requests with category-based routing',
    'parent_portal',
    'parent_feedback',
    1, -- Allow multiple tickets per parent
    1, -- Active
    NOW()
);
```

### Workflow Steps

The Parent Feedback workflow consists of the following steps:

1. **Start** - Parent submits ticket
2. **Auto-Route** - System automatically routes to appropriate department based on category
3. **Acknowledge** - Staff acknowledges receipt (auto-sends SMS to parent)
4. **Investigate** - Assigned staff investigates and works on resolution
5. **Decision: Resolved?**
   - **Yes** → Mark as Resolved
   - **No** → Escalate to supervisor
6. **Escalated Review** - Supervisor reviews escalated ticket
7. **Resolution** - Staff provides resolution
8. **Parent Notification** - Auto-send SMS with resolution
9. **End** - Ticket closed

### Category-Based Routing Rules

```json
{
  "fees": {
    "department": "Accounts",
    "assigned_role": "accountant",
    "sla_hours": 24,
    "escalation_hours": 48
  },
  "food": {
    "department": "Catering",
    "assigned_role": "catering_manager",
    "sla_hours": 24,
    "escalation_hours": 48
  },
  "learning": {
    "department": "Academics",
    "assigned_role": "class_teacher",
    "sla_hours": 48,
    "escalation_hours": 72,
    "cc_roles": ["academic_coordinator"]
  },
  "transport": {
    "department": "Transport",
    "assigned_role": "transport_coordinator",
    "sla_hours": 12,
    "escalation_hours": 24,
    "urgent_notification": true
  },
  "health": {
    "department": "Medical",
    "assigned_role": "nurse",
    "sla_hours": 4,
    "escalation_hours": 8,
    "urgent_notification": true
  },
  "sports": {
    "department": "Sports",
    "assigned_role": "sports_coordinator",
    "sla_hours": 48,
    "escalation_hours": 96
  },
  "behavior": {
    "department": "Student Welfare",
    "assigned_role": "counselor",
    "sla_hours": 24,
    "escalation_hours": 48,
    "cc_roles": ["class_teacher", "principal"]
  },
  "other": {
    "department": "Administration",
    "assigned_role": "admin_officer",
    "sla_hours": 48,
    "escalation_hours": 96
  }
}
```

### Parent Portal Integration Points

#### Creating a Ticket from Parent Portal

```php
// ParentFeedbackController.php - submitTicket()

// 1. Create workflow ticket
$workflowTicket = WorkflowService::createTicket([
    'workflow_code' => 'PARENT_FEEDBACK_WORKFLOW',
    'entity_type' => 'parent_feedback',
    'entity_id' => $parentFeedbackId, // ID from parent_feedback_tickets
    'priority' => $urgency, // map parent urgency to workflow priority
    'started_by_type' => 'parent'
]);

// 2. Create parent_feedback_tickets entry
$parentFeedbackId = DB::insert('parent_feedback_tickets', [
    'workflow_ticket_id' => $workflowTicket['id'],
    'parent_account_id' => $_SESSION['parent_id'],
    'student_id' => $studentId,
    'category' => $category,
    'subject' => $subject,
    'description' => $description,
    'parent_urgency' => $urgency,
    'preferred_contact_method' => $contactMethod
]);

// 3. Add attachments to workflow_attachments table
foreach ($attachments as $file) {
    WorkflowService::addAttachment($workflowTicket['id'], null, $file);
}

// 4. Queue confirmation SMS
$ticketNumber = $workflowTicket['ticket_number'];
$message = "Your support ticket #{$ticketNumber} has been submitted. We'll respond within {$slaHours} hours.";
MessageQueue::queueSMS($parentPhone, $message);

// 5. Mark SMS as sent
DB::update('parent_feedback_tickets', $parentFeedbackId, [
    'confirmation_sms_sent' => true,
    'confirmation_sms_sent_at' => date('Y-m-d H:i:s')
]);
```

#### Viewing Ticket Status from Parent Portal

```php
// ParentFeedbackController.php - myTickets()

$tickets = DB::query("
    SELECT
        pft.*,
        wt.ticket_number,
        wt.status as workflow_status,
        wt.priority,
        wt.started_at,
        wt.completed_at,
        s.first_name as student_first_name,
        s.last_name as student_last_name,
        -- Get latest comment that's visible to parent
        (SELECT comment FROM workflow_comments
         WHERE ticket_id = wt.id
         AND (is_internal = 0 OR is_internal IS NULL)
         ORDER BY created_at DESC LIMIT 1) as latest_update
    FROM parent_feedback_tickets pft
    JOIN workflow_tickets wt ON pft.workflow_ticket_id = wt.id
    LEFT JOIN students s ON pft.student_id = s.id
    WHERE pft.parent_account_id = :parent_id
    ORDER BY wt.started_at DESC
", ['parent_id' => $_SESSION['parent_id']]);
```

#### Adding Comments from Parent Portal

```php
// ParentFeedbackController.php - addComment()

WorkflowService::addComment([
    'ticket_id' => $workflowTicketId,
    'comment' => $parentComment,
    'user_id' => null, // Parent, not a staff user
    'user_name' => $parentName,
    'is_internal' => false // Visible to all
]);

// Notify assigned staff via email/SMS
NotificationService::notifyTicketUpdate($workflowTicketId, 'Parent added a comment');
```

### Staff Integration Points

Staff will interact with parent tickets through the existing workflow dashboard:

1. **Task Queue**: Parent feedback tickets appear in assigned staff member's task queue
2. **Ticket Detail**: View parent details, student info, category, description
3. **Actions Available**:
   - Acknowledge receipt
   - Add comments (public or internal)
   - Upload attachments
   - Change status
   - Reassign to another staff member
   - Escalate to supervisor
   - Mark as resolved
   - Close ticket

4. **Auto-SMS Triggers**:
   - When ticket acknowledged → SMS to parent
   - When comment added → SMS to parent (if public comment)
   - When status changes to resolved → SMS with resolution
   - When escalated → SMS to parent with update

---

## Appendix

### Sample Notification Templates
```json
{
  "type": "fee_reminder",
  "severity": "warning",
  "requires_action": true,
  "action_deadline": "2025-01-15",
  "title": "Fee Payment Reminder",
  "message": "Term 1 fees for {student_name} are due in 2 days. Outstanding balance: KES {amount}.",
  "action_url": "/parent/child/{student_id}/fees"
}
```

### Sample Survey JSON
```json
{
  "title": "Cultural Day Feedback",
  "questions": [
    {
      "id": 1,
      "type": "rating",
      "question": "How would you rate the Cultural Day event?",
      "scale": 5,
      "required": true
    },
    {
      "id": 2,
      "type": "multiple_choice",
      "question": "Which performance did you enjoy most?",
      "options": ["Traditional Dance", "Drama", "Music", "Art Exhibition"],
      "required": false
    },
    {
      "id": 3,
      "type": "text",
      "question": "Any suggestions for improvement?",
      "required": false
    }
  ]
}
```

---

## Lookup Tables Seed Data

All lookup tables must be seeded with initial data during migration. Below are the required seed values:

### Notification System Lookups

```sql
-- Notification Types
INSERT INTO notification_types (code, name, description, icon) VALUES
('general', 'General', 'General school announcements', 'ti-bell'),
('academic', 'Academic', 'Academic and learning related', 'ti-book'),
('fees', 'Fees & Payments', 'Financial and fee-related', 'ti-currency-dollar'),
('transport', 'Transport', 'Transport and bus-related', 'ti-bus'),
('health', 'Health & Medical', 'Health and medical notifications', 'ti-heartbeat'),
('event', 'Events', 'School events and activities', 'ti-calendar-event'),
('feedback_request', 'Feedback Request', 'Survey and feedback requests', 'ti-message-circle');

-- Notification Severity Levels
INSERT INTO notification_severity_levels (code, name, color, sort_order) VALUES
('info', 'Information', 'blue', 1),
('success', 'Success', 'green', 2),
('warning', 'Warning', 'orange', 3),
('urgent', 'Urgent', 'red', 4);

-- Notification Scopes
INSERT INTO notification_scopes (code, name) VALUES
('school', 'School-wide'),
('grade', 'Grade/Class Level'),
('student', 'Individual Student');

-- Notification Action Types
INSERT INTO notification_action_types (code, name, description) VALUES
('rsvp', 'RSVP', 'Respond to event invitation'),
('acknowledge', 'Acknowledge', 'Acknowledge receipt'),
('payment', 'Make Payment', 'Proceed to payment'),
('feedback', 'Provide Feedback', 'Complete survey/feedback'),
('other', 'Other Action', 'Custom action required');
```

### Calendar & Events Lookups

```sql
-- Event Types
INSERT INTO event_types (code, name, description, icon, default_color, sort_order) VALUES
('holiday', 'Holiday', 'School holidays and closures', 'ti-sun', '#F59E0B', 1),
('parent_teacher', 'Parent-Teacher Day', 'Parent-teacher conferences', 'ti-users', '#8B5CF6', 2),
('cultural', 'Cultural Event', 'Cultural days and celebrations', 'ti-palette', '#EC4899', 3),
('sports', 'Sports Event', 'Sports and athletics', 'ti-ball-basketball', '#10B981', 4),
('academic', 'Academic Event', 'Exams, presentations, field trips', 'ti-book', '#3B82F6', 5),
('transport', 'Transport Change', 'Transport schedule changes', 'ti-bus', '#6B7280', 6),
('other', 'Other', 'Other school events', 'ti-calendar', '#9CA3AF', 7);

-- Event Scopes
INSERT INTO event_scopes (code, name) VALUES
('school', 'School-wide'),
('grade', 'Grade/Class Specific'),
('student', 'Individual Student');

-- RSVP Response Types
INSERT INTO rsvp_response_types (code, name, sort_order) VALUES
('attending', 'Attending', 1),
('not_attending', 'Not Attending', 2),
('maybe', 'Maybe', 3);

-- Calendar Item Types
INSERT INTO calendar_item_types (code, name, icon) VALUES
('appointment', 'Appointment', 'ti-calendar-time'),
('reminder', 'Reminder', 'ti-bell'),
('deadline', 'Deadline', 'ti-alert-circle'),
('other', 'Other', 'ti-calendar');
```

### Feedback & Communication Lookups

```sql
-- Feedback Request Scopes
INSERT INTO feedback_request_scopes (code, name, description) VALUES
('all_parents', 'All Parents', 'Survey sent to all parents'),
('grade', 'Grade/Class', 'Survey sent to parents of specific grade/class'),
('specific_parents', 'Specific Parents', 'Survey sent to selected parents');

-- Feedback Types
INSERT INTO feedback_types (code, name, description) VALUES
('rating', 'Rating Scale', 'Star ratings or numeric scales'),
('multiple_choice', 'Multiple Choice', 'Select from predefined options'),
('text', 'Free Text', 'Open-ended text responses'),
('mixed', 'Mixed Questions', 'Combination of question types');

-- Parent Feedback Categories
INSERT INTO parent_feedback_categories (code, name, description, icon, default_department, default_assigned_role, default_sla_hours, default_escalation_hours, requires_urgent_notification, sort_order) VALUES
('fees', 'Fees & Payments', 'Questions about school fees, invoices, payments', 'ti-currency-dollar', 'Accounts', 'accountant', 24, 48, 0, 1),
('food', 'Food & Nutrition', 'Catering, meals, nutrition concerns', 'ti-tools-kitchen', 'Catering', 'catering_manager', 24, 48, 0, 2),
('learning', 'Learning & Academics', 'Academic performance, curriculum, teaching', 'ti-book', 'Academics', 'class_teacher', 48, 72, 0, 3),
('transport', 'Transport', 'School bus, routes, pickup/dropoff', 'ti-bus', 'Transport', 'transport_coordinator', 12, 24, 1, 4),
('health', 'Health & Medical', 'Medical issues, health concerns', 'ti-heartbeat', 'Medical', 'nurse', 4, 8, 1, 5),
('sports', 'Sports & Activities', 'Sports, extracurricular activities', 'ti-ball-basketball', 'Sports', 'sports_coordinator', 48, 96, 0, 6),
('behavior', 'Behavior & Discipline', 'Student behavior, discipline matters', 'ti-mood-sad', 'Student Welfare', 'counselor', 24, 48, 0, 7),
('other', 'Other/General', 'General inquiries', 'ti-help', 'Administration', 'admin_officer', 48, 96, 0, 8);

-- Contact Methods
INSERT INTO contact_methods (code, name) VALUES
('sms', 'SMS'),
('email', 'Email'),
('phone', 'Phone Call');

-- Urgency Levels
INSERT INTO urgency_levels (code, name, color, sort_order) VALUES
('low', 'Low', 'green', 1),
('medium', 'Medium', 'orange', 2),
('high', 'High', 'red', 3);
```

### Contact Directory Lookups

```sql
-- Contact Types
INSERT INTO contact_types (code, name, description, icon, sort_order) VALUES
('main_office', 'Main Office', 'School main reception', 'ti-building', 1),
('principal', 'Principal/Head Teacher', 'School principal office', 'ti-tie', 2),
('accounts', 'Accounts Department', 'Finance and fees office', 'ti-calculator', 3),
('medical', 'Medical/Health Office', 'School nurse and medical services', 'ti-first-aid-kit', 4),
('transport', 'Transport Coordinator', 'School transport office', 'ti-bus', 5),
('counseling', 'Counseling Services', 'School counselor', 'ti-mood-smile', 6),
('emergency', 'Emergency Contact', '24/7 emergency line', 'ti-alert-triangle', 7),
('other', 'Other', 'Other departments', 'ti-phone', 8);
```

### Transport Tracking Lookups

```sql
-- Transport Trip Types
INSERT INTO transport_trip_types (code, name) VALUES
('pickup', 'Pickup (Morning)'),
('dropoff', 'Drop-off (Afternoon)');

-- Transport Log Statuses
INSERT INTO transport_log_statuses (code, name, color, is_positive, sort_order) VALUES
('on_time', 'On Time', 'green', 1, 1),
('late', 'Delayed', 'orange', 0, 2),
('very_late', 'Very Late', 'red', 0, 3),
('missed', 'Missed', 'red', 0, 4),
('cancelled', 'Cancelled', 'gray', 0, 5);
```

---

**Document Version**: 2.0
**Created**: 2025-01-07
**Last Updated**: 2025-01-07
**Author**: SchoolDynamics Development Team
**Changes in v2.0**: Replaced all ENUM fields with normalized lookup tables and foreign keys
