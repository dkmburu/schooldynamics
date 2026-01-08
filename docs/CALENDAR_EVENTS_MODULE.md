# School Calendar & Events Management Module

**Version:** 1.0
**Created:** 2025-01-07
**Status:** Planning

---

## Overview

A comprehensive calendar and events management system that allows administrators to create, manage, and track school events with sophisticated audience targeting, RSVP functionality, and attendance tracking.

---

## Core Features

### 1. Calendar Management
- **Master Annual Calendar**: Single source of truth for all school activities
- **Multi-Year Support**: Manage current, past, and future academic years
- **Event Categories**: Academic, Sports, Cultural, Administrative, Parent Events, etc.
- **Color Coding**: Visual distinction by event type/category
- **Recurring Events**: Support for daily, weekly, monthly, yearly patterns
- **Event Series**: Link related events (e.g., "Term 1 Exams" spanning multiple days)

### 2. Event Types & Classification

#### Academic Events
- Term start/end dates
- Exam periods (mid-term, end-term)
- Assignment deadlines
- Class schedules
- Grade-specific academic activities
- Curriculum days

#### Non-Academic Events
- Sports days/weeks
- Cultural festivals
- School trips/excursions
- Parent-Teacher meetings
- PTA meetings
- Awards ceremonies
- Open days
- Fundraising events

#### Administrative Events
- Staff meetings
- Training sessions
- Board meetings
- Supplier meetings

---

## 3. Event Ownership & Management

### Event Owners
- **Primary Owner**: Main staff member responsible
- **Co-Owners**: Additional staff/associates involved
- **Owner Types**:
  - School Staff (teachers, admin, support staff)
  - Associates (PTA members, volunteers, external coordinators)
  - Departments (Sports Department, Academics, etc.)

### Owner Permissions
- Create/edit events
- Manage RSVPs
- View attendance reports
- Send event updates/notifications
- Close/cancel events

---

## 4. Audience Targeting System

### Target Audience Types

#### Students
- **All Students**: School-wide
- **By Grade**: Grade 1, Grade 2, etc.
- **By Grade Range**: Grades 1-3, Grades 7-12
- **By Stream/Class**: Grade 5A, Grade 5B
- **By Boarding Status**: Day scholars, Boarders, All
- **Custom Selection**: Pick specific students

#### Parents/Guardians
- **All Parents**: Everyone
- **Grade-Based**: Parents of Grade X students
- **Student-Based**: Parents of specific students
- **Active Only**: Only parents with active portal accounts

#### Staff
- **All Staff**
- **By Department**: Academic, Sports, Admin, etc.
- **By Role**: Teachers, Admin Staff, Support Staff
- **Specific Staff**: Individual selection

#### External
- **Suppliers/Vendors**
- **Alumni**
- **Community Members**
- **Board Members**

### Multi-Audience Events
- Events can target multiple audience types simultaneously
- Each audience type can have different permissions (view only vs RSVP required)

---

## 5. RSVP System

### RSVP Configuration
- **Optional**: Attendees can RSVP but it's not required
- **Required**: Must RSVP to attend
- **Disabled**: No RSVP functionality

### RSVP Options
- **Attending**: Confirmed attendance
- **Not Attending**: Declined
- **Maybe/Tentative**: Unsure
- **Guest Count**: Number of additional guests (e.g., parent bringing siblings)

### RSVP Features
- **Deadline**: Last date to RSVP
- **Capacity Limit**: Maximum attendees (optional)
- **Waitlist**: Auto-enable when capacity reached
- **RSVP Changes**: Allow attendees to modify response before deadline
- **Reminders**: Auto-reminders for those who haven't responded

### RSVP Notifications
- Confirmation email/SMS when RSVP submitted
- Updates when event details change
- Reminders before event date
- Cancellation notifications

---

## 6. Attendance Tracking System

### Check-In Methods

#### Method 1: Unique ID Number
- System generates random unique 6-8 digit number per attendee
- Displayed on confirmation page/email/SMS
- Gate staff enter number to mark attendance
- Validates against RSVP list

#### Method 2: QR Code
- Generate unique QR code per attendee
- Code embedded in confirmation email/SMS
- Staff scan QR code at gate using mobile device
- Instant attendance marking

#### Method 3: Manual Check-In
- Staff view RSVP list on tablet/mobile
- Tick off attendees as they arrive
- Search by name, student ID, parent phone

### Attendance Features
- **Real-Time Dashboard**: Live attendance count during event
- **Late Arrivals**: Track check-in time
- **Early Departures**: Optional check-out tracking
- **No-Shows**: Identify confirmed attendees who didn't attend
- **Walk-Ins**: Register attendees who didn't RSVP
- **Guest Registration**: Add +1 guests at gate

### Attendance Reports
- Export attendance list (CSV, PDF)
- Compare RSVP vs actual attendance
- Track attendance patterns per user
- Grade/class attendance summaries

---

## 7. Notification & Communication

### Event Notifications
- **Creation**: Notify target audiences when event is published
- **Updates**: Alert when event details change (time, venue, etc.)
- **Reminders**: Scheduled reminders (1 week, 1 day, 1 hour before)
- **RSVP Reminders**: For those who haven't responded
- **Cancellation**: Immediate notification if event cancelled

### Notification Channels
- Parent Portal notifications
- SMS messages
- Email (if available)
- Push notifications (future)

### Batch Notifications
- Send custom messages to all attendees
- Target specific RSVP groups (attending only, declined, etc.)
- Post-event thank you messages

---

## 8. Calendar Views & Filters

### View Options
- **Month View**: Traditional monthly calendar
- **Week View**: Detailed weekly schedule
- **Day View**: Hour-by-hour timeline
- **Agenda/List View**: Chronological list
- **Year View**: Annual overview with key dates

### Filtering
- By event category/type
- By audience (my events, my child's events, etc.)
- By grade/class
- By RSVP status (events I've confirmed, declined, pending)
- By date range
- By owner/organizer

### Color Coding
- Academic: Blue
- Sports: Green
- Cultural: Purple
- Administrative: Grey
- Parent Events: Orange
- Custom: User-defined

---

## 9. Event Details & Information

### Basic Information
- Title
- Description (rich text with formatting)
- Category/Type
- Date & Time (start, end)
- All-day event option
- Venue/Location
- Google Maps integration (optional)

### Advanced Details
- Event agenda/schedule
- Dress code
- What to bring
- Cost/fees (if applicable)
- Contact person(s)
- Documents/attachments
- Links to related resources

### Visibility & Privacy
- Public: Visible to all target audiences
- Draft: Only visible to owners
- Published: Live and visible
- Archived: Past events, read-only

---

## 10. Database Schema (Normalized)

### Core Tables

#### `event_categories`
```sql
- id
- code (academic, sports, cultural, administrative, parent, other)
- name
- description
- color_hex
- icon
- is_active
```

#### `event_types`
```sql
- id
- category_id (FK)
- code (term_start, exam, sports_day, ptm, etc.)
- name
- description
- default_duration_hours
- requires_rsvp (default)
- is_active
```

#### `events`
```sql
- id
- school_id (FK)
- campus_id (FK, nullable)
- event_type_id (FK)
- title
- description (TEXT)
- start_datetime
- end_datetime
- is_all_day
- venue_location
- venue_map_link
- event_details (JSON: agenda, dress_code, cost, etc.)
- status (draft, published, cancelled, completed, archived)
- visibility (public, private)
- max_capacity (nullable)
- rsvp_enabled
- rsvp_required
- rsvp_deadline
- check_in_method (unique_id, qr_code, manual, multiple)
- created_by (FK to users)
- created_at
- updated_at
```

#### `event_owners`
```sql
- id
- event_id (FK)
- owner_type (staff, associate, department)
- owner_id (FK to staff/users/departments)
- is_primary (boolean)
- permissions (JSON: can_edit, can_manage_rsvp, can_check_in)
```

#### `event_recurrence`
```sql
- id
- event_id (FK to parent event)
- recurrence_pattern (daily, weekly, monthly, yearly, custom)
- recurrence_interval (every X days/weeks/etc)
- recurrence_days (JSON: [mon, wed, fri] for weekly)
- recurrence_end_date
- exceptions (JSON: dates to skip)
```

#### `event_series`
```sql
- id
- series_name
- description
- created_by
- created_at
```

#### `event_series_members`
```sql
- event_id (FK)
- series_id (FK)
- sequence_order
```

### Audience Targeting Tables

#### `audience_types`
```sql
- id
- code (students_all, students_grade, parents_all, staff_all, etc.)
- name
- description
- category (student, parent, staff, external)
```

#### `event_audiences`
```sql
- id
- event_id (FK)
- audience_type_id (FK)
- audience_filter (JSON: grade_ids, class_ids, specific_user_ids, etc.)
- can_rsvp (boolean)
- can_view_only (boolean)
```

### RSVP Tables

#### `rsvp_statuses`
```sql
- id
- code (attending, not_attending, maybe, waitlist)
- name
- color
- sort_order
```

#### `event_rsvps`
```sql
- id
- event_id (FK)
- user_id (FK to parent_accounts/staff/students)
- user_type (parent, student, staff)
- rsvp_status_id (FK)
- guest_count
- response_date
- unique_check_in_code (8 chars, indexed)
- qr_code_data (encrypted)
- notes (dietary restrictions, special needs, etc.)
- created_at
- updated_at
```

### Attendance Tables

#### `event_attendance`
```sql
- id
- event_id (FK)
- rsvp_id (FK, nullable if walk-in)
- user_id (FK)
- user_type
- check_in_method (unique_id, qr_code, manual)
- check_in_time
- checked_in_by (FK to staff user)
- check_out_time (nullable)
- is_walk_in (boolean)
- guest_count
- notes
```

---

## 11. User Interfaces

### Admin Interface (Staff Backend)

#### Calendar Dashboard
- Full calendar view with all events
- Quick filters by category, date range, owner
- Color-coded events
- Drag-and-drop to reschedule (with confirmation)
- Quick event creation modal

#### Event Management
- **Create Event**: Multi-step wizard
  - Step 1: Basic details (title, type, date/time)
  - Step 2: Target audience selection
  - Step 3: RSVP configuration
  - Step 4: Attendance settings
  - Step 5: Notifications
  - Step 6: Review & publish

- **Event List**: Filterable table view
  - Search by title, owner, category
  - Bulk actions (publish, cancel, archive)
  - Export events list

- **Event Detail Page**:
  - View/edit all event details
  - RSVP summary (attending, declined, pending, no-shows)
  - Real-time attendance dashboard
  - Send notifications to attendees
  - Download reports

#### RSVP Management
- View all RSVPs for an event
- Filter by status, user type
- Export RSVP list
- Send reminders
- Manually add/remove RSVPs

#### Attendance Check-In
- **Mobile-Optimized Interface**:
  - Large input for unique ID entry
  - QR code scanner (camera access)
  - Manual list with search
  - Instant feedback (success/error)
  - Current attendance count
  - Walk-in registration form

### Parent Portal Interface

#### Calendar View
- Monthly/weekly view of relevant events
- Filter by child (if multiple children)
- Show only events targeting parents
- Color-coded by category
- Badge for events requiring RSVP

#### Event Detail
- Full event information
- RSVP form (if enabled)
- Unique ID / QR code display after RSVP
- Add to personal calendar (iCal, Google Calendar)
- Share event details

#### My Events
- List of upcoming events
- Filter by RSVP status
- Past events attended
- Download attendance certificates (if applicable)

### Student Portal (Future)
- Similar to parent portal
- Grade-appropriate event visibility
- Student-specific events

---

## 12. Permissions & Access Control

### Admin Permissions
- `calendar.view_all`: View all events
- `calendar.create`: Create new events
- `calendar.edit_own`: Edit own events
- `calendar.edit_all`: Edit any event
- `calendar.delete`: Delete events
- `calendar.publish`: Publish/unpublish events
- `calendar.manage_rsvp`: Manage RSVPs
- `calendar.check_in`: Mark attendance
- `calendar.reports`: Access reports

### Event Owner Permissions
- View event details
- Edit event (if primary owner or granted permission)
- Manage RSVPs
- Check-in attendees
- Send notifications to attendees
- View reports

### Parent/Guardian Permissions
- View events targeting them
- RSVP to events
- View own RSVP history
- Receive notifications

---

## 13. Integration Points

### Parent Portal Notifications
- Link to existing `parent_notifications` table
- Auto-create notification when event is published
- Update notification when event changes
- Mark as action-required if RSVP is mandatory

### SMS/Email System
- Integrate with existing message queue (`message_queue` table)
- Send RSVP confirmations via SMS
- Event reminders via SMS/Email
- QR code / Unique ID delivery

### Workflow System
- Create workflow ticket for event approval (if required)
- Event cancellation workflow
- Incident reporting during events

### Finance Module (Future)
- Link paid events to invoicing
- Track event-related expenses
- Budget allocation for events

---

## 14. Reporting & Analytics

### Event Reports
- Events by category/type (monthly, yearly)
- Most attended events
- RSVP response rates
- No-show rates
- Attendance trends by grade/class

### User Reports
- Parent engagement (RSVP rate, attendance rate)
- Student participation in events
- Staff event management metrics

### Export Options
- PDF: Printable event details, attendance sheets
- CSV: Raw data for analysis
- Excel: Formatted reports with charts

---

## 15. Module Structure & Navigation

### Admin Backend Structure
```
Calendar (Main Module)
├── Dashboard (Calendar Overview)
├── Events (Sub-module with tabs)
│   ├── All Events (List/Grid view)
│   ├── Create Event
│   ├── Categories & Types
│   ├── RSVP Management
│   └── Attendance Reports
├── Term Dates (Academic Calendar Setup)
└── Settings
```

### Calendar Dashboard Features
- Global calendar view with multiple display modes
- Quick filters and search
- Event creation shortcuts
- Upcoming events widget
- RSVP summary widget

---

## 16. Academic Calendar & Term Management

### Term Dates Configuration
Essential foundation for the entire calendar system.

#### Term Structure
```sql
-- New table for academic terms
CREATE TABLE academic_terms (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id INT UNSIGNED NOT NULL,
    academic_year VARCHAR(20) NOT NULL, -- e.g., "2024/2025"
    term_number TINYINT NOT NULL, -- 1, 2, 3
    term_name VARCHAR(50) NOT NULL, -- "Term 1", "First Term", etc.
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id)
);

-- Important dates within terms
CREATE TABLE term_important_dates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    term_id INT UNSIGNED NOT NULL,
    date_type_id INT UNSIGNED NOT NULL, -- FK to lookup
    start_date DATE NOT NULL,
    end_date DATE NULL, -- for multi-day periods
    description VARCHAR(255),
    FOREIGN KEY (term_id) REFERENCES academic_terms(id),
    FOREIGN KEY (date_type_id) REFERENCES term_date_types(id)
);

-- Lookup table for date types
CREATE TABLE term_date_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL, -- 'academic', 'exam', 'holiday', 'break'
    color VARCHAR(20),
    icon VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE
);
```

#### Date Types (Seed Data)
- **Academic Dates**:
  - Term Opening
  - Term Closing
  - Mid-Term Break Start
  - Mid-Term Break End
  - Visiting Day
  - Report Card Distribution

- **Exam Dates**:
  - Mock Exams Start/End
  - Mid-Term Exams Start/End
  - End-Term Exams Start/End
  - National Exams (KCPE, KCSE, etc.)

- **Holiday Dates**:
  - Public Holidays
  - School Holidays
  - Half-Term Break

#### Term Setup Workflow
1. Define academic year (e.g., 2024/2025)
2. Set number of terms (typically 3)
3. For each term:
   - Set term start and end dates
   - Define mid-term break dates
   - Set exam periods
   - Mark important dates
4. System auto-populates calendar with these dates
5. These dates show as background/highlighted on all calendar views

---

## 17. Enhanced Calendar Views

### Multiple View Modes (User Selectable)

#### Monthly View
- Traditional month calendar
- Navigate month by month
- All events for the month visible

#### Bi-Monthly View (2-Month View)
- See two consecutive months side-by-side
- Better context for planning
- Ideal for desktop screens

#### Termly View (3-4 Month View)
- View entire term at once
- Shows 3-4 months based on term length
- Academic calendar overlay (term dates, exam periods)
- Ideal for term planning

#### Four-Month View
- Custom 4-month rolling window
- Good for semester planning
- Flexible date range

#### Annual View (Year at a Glance)
- 12-month compact grid
- High-level overview
- Click month to zoom in
- Shows major events only (capacity limit)

### View Switching
- Dropdown selector: "View: Monthly | Bi-Monthly | Termly | 4-Month | Annual"
- Remember user preference in session/localStorage
- Print-friendly versions of all views

### Calendar Filtering System

#### Multi-Select Filters
Users can select multiple filters simultaneously:

**By Category** (checkboxes):
- [ ] Academic
- [ ] Sports
- [ ] Cultural
- [ ] PTA/Parent Events
- [ ] Exams
- [ ] Holidays
- [ ] Administrative
- [ ] Other

**By Audience** (if admin):
- [ ] Students All
- [ ] Students by Grade (expandable sub-options)
- [ ] Parents
- [ ] Staff Only
- [ ] Public/Community

**By Status**:
- [ ] Upcoming
- [ ] Past
- [ ] Cancelled
- [ ] Draft

**By RSVP** (for parent view):
- [ ] Attending
- [ ] Declined
- [ ] Pending Response
- [ ] No RSVP Required

#### Filter Display
- Active filters shown as dismissible chips/tags
- "Clear All Filters" button
- Filter count badge ("3 filters active")
- Save filter presets (e.g., "My Academic Events", "All Sports")

#### Term Calendar Overlay
When in Termly/4-Month/Annual view:
- Term start/end dates highlighted
- Exam periods shaded (e.g., light red background)
- Mid-term breaks shaded (e.g., light blue)
- Holidays shaded (e.g., light green)
- Legend explaining colors

---

## 18. Event Planning & Task Management

### Pre-Event Task Checklists

For complex events (trips, major events), create planning task lists:

#### Task Planning Table
```sql
CREATE TABLE event_planning_tasks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id BIGINT UNSIGNED NOT NULL,
    task_description VARCHAR(255) NOT NULL,
    task_owner_id INT UNSIGNED NOT NULL, -- FK to staff
    task_deadline DATE NOT NULL,
    reminder_days_before INT DEFAULT 14, -- Remind X days before deadline
    reminder_sent_at DATETIME NULL,
    status VARCHAR(20) DEFAULT 'pending', -- pending, in_progress, completed
    completed_at DATETIME NULL,
    completed_by INT UNSIGNED NULL,
    notes TEXT NULL,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (task_owner_id) REFERENCES staff(id)
);
```

#### Example: School Trip to Zambia

| Task Description | Owner | Deadline | Remind Days Before | Status |
|------------------|-------|----------|-------------------|--------|
| Confirm all travelers | John Doe | April 4, 2024 | 14 | Pending |
| Book flights | Jane Smith | March 15, 2024 | 21 | Completed |
| Arrange accommodation | John Doe | March 20, 2024 | 14 | In Progress |
| Obtain travel permits | Admin Office | March 10, 2024 | 30 | Pending |
| Collect consent forms | Class Teachers | April 1, 2024 | 7 | Pending |
| Arrange transport to airport | Transport Dept | April 3, 2024 | 3 | Pending |
| Brief parents & students | John Doe | April 5, 2024 | 7 | Pending |
| Prepare itinerary documents | Jane Smith | March 25, 2024 | 14 | Pending |

#### Task Management Features
- **Add tasks** when creating/editing event
- **Assign tasks** to specific staff members
- **Set deadlines** with calendar picker
- **Configure reminders** (X days before deadline)
- **Track status** (pending → in progress → completed)
- **Reorder tasks** (drag-and-drop)
- **Task notifications** sent to assigned staff
- **Reminder notifications** auto-sent based on reminder_days_before
- **Task dashboard** for staff to view their assigned tasks across all events

#### Task Notification Flow
1. Task created → Owner notified immediately
2. Reminder date reached → Reminder notification sent
3. Deadline approaching (1 day before) → Urgent reminder sent
4. Deadline passed + incomplete → Overdue alert to owner and event creator
5. Task completed → Event creator notified

#### Integration with Workflow Module
- Option to create workflow ticket for complex tasks
- Link task to existing workflow for approvals
- Escalation if task overdue

---

## 19. Implementation Phases (REVISED)

### Phase 1A: Foundation (PRIORITY 1)
**Academic Calendar Setup**
- [ ] Database schema for academic terms and term dates
- [ ] Admin interface to define academic years and terms
- [ ] Set term start/end, mid-term breaks, exam periods
- [ ] Term date types lookup table and seed data
- [ ] Calendar view with term overlay

### Phase 1B: Core Calendar & Events (PRIORITY 2)
- [ ] Event database schema
- [ ] Event categories and types
- [ ] Event creation wizard (admin)
- [ ] Multiple calendar views (Monthly, Bi-Monthly, Termly, Annual)
- [ ] Calendar filtering system (multi-select by category, audience, status)
- [ ] Event owners management
- [ ] Basic parent portal calendar view

### Phase 2: Event Planning & Tasks (PRIORITY 3)
- [ ] Pre-event task management system
- [ ] Task assignment to staff
- [ ] Task deadline tracking and reminders
- [ ] Task status workflow
- [ ] Staff task dashboard
- [ ] Task notifications

### Phase 3: RSVP System
- [ ] RSVP configuration per event
- [ ] Parent portal RSVP functionality
- [ ] RSVP management interface (admin)
- [ ] Capacity and waitlist management
- [ ] RSVP notifications and reminders
- [ ] Unique ID generation

### Phase 4: Attendance Tracking
- [ ] Unique ID check-in system
- [ ] QR code generation and scanning
- [ ] Mobile check-in interface
- [ ] Real-time attendance dashboard
- [ ] Walk-in registration
- [ ] Attendance reports

### Phase 5: Advanced Features
- [ ] Recurring events
- [ ] Event series
- [ ] Advanced audience targeting (custom selections)
- [ ] iCal/Google Calendar export
- [ ] SMS delivery of QR codes
- [ ] Event feedback/surveys
- [ ] Attendance certificates

---

## 20. UI Wireframe Concepts

### Admin Calendar Dashboard

```
┌──────────────────────────────────────────────────────────────────┐
│  Calendar                                                         │
│  ┌─────────────┬────────────────────────────────────────────┐   │
│  │ Dashboard   │  Events  │  Term Dates  │  Settings       │   │
│  └─────────────┴────────────────────────────────────────────┘   │
│                                                                    │
│  View: [Termly ▼]    Filters: [📁 Academic ✕] [⚽ Sports ✕]     │
│                                                                    │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  January 2024        February 2024        March 2024       │ │
│  │  ┌───┬───┬───┐      ┌───┬───┬───┐       ┌───┬───┬───┐    │ │
│  │  │ 1 │ 2 │ 3 │      │   │   │ 1 │       │   │   │ 1 │    │ │
│  │  ├───┼───┼───┤      ├───┼───┼───┤       ├───┼───┼───┤    │ │
│  │  │ 8 │ 9 │10 │      │ 5 │ 6 │ 7 │       │ 4 │ 5 │ 6 │    │ │
│  │  │   │🎓│   │       │   │⚽│   │       │📝│   │   │    │ │
│  │  [TERM 1 STARTS]     [SPORTS DAY]        [EXAMS START]     │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                    │
│  Upcoming Events                    Quick Actions                 │
│  • Sports Day - Feb 6              [+ Create Event]               │
│  • Mid-Term Break - Feb 10-14      [⚙ Manage Term Dates]        │
│  • Parent Meeting - Mar 1          [📊 View Reports]             │
└──────────────────────────────────────────────────────────────────┘
```

### Event Planning Tasks Interface

```
┌──────────────────────────────────────────────────────────────────┐
│  Event: School Trip to Zambia - June 15-20, 2024                │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ Details │ Audience │ RSVP │ Planning Tasks │ Attendance   │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                    │
│  Planning Tasks                                [+ Add Task]       │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ Task               │ Owner      │ Deadline  │ Remind │ Status│ │
│  ├────────────────────┼────────────┼───────────┼────────┼───────┤ │
│  │ Confirm travelers  │ John Doe   │ Apr 4     │ 14 days│ ⏳    │ │
│  │ Book flights       │ Jane Smith │ Mar 15    │ 21 days│ ✅    │ │
│  │ Get travel permits │ Admin      │ Mar 10    │ 30 days│ ⚠️    │ │
│  │ Collect consent    │ Teachers   │ Apr 1     │ 7 days │ ⏳    │ │
│  └────────────────────┴────────────┴───────────┴────────┴───────┘ │
│                                                                    │
│  Task Progress: 1/4 completed                                     │
│  ███████░░░░░░░░░░░░░░░░░░░░░ 25%                                │
└──────────────────────────────────────────────────────────────────┘
```

---

## 15. Implementation Phases

### Phase 2: RSVP System
- [ ] RSVP configuration per event
- [ ] Parent portal RSVP functionality
- [ ] RSVP management interface (admin)
- [ ] Capacity and waitlist management
- [ ] RSVP notifications and reminders
- [ ] Unique ID generation

### Phase 3: Attendance Tracking
- [ ] Unique ID check-in system
- [ ] QR code generation and scanning
- [ ] Mobile check-in interface
- [ ] Real-time attendance dashboard
- [ ] Walk-in registration
- [ ] Attendance reports

### Phase 4: Advanced Features
- [ ] Recurring events
- [ ] Event series
- [ ] Advanced audience targeting (custom selections)
- [ ] iCal/Google Calendar export
- [ ] SMS delivery of QR codes
- [ ] Event feedback/surveys
- [ ] Attendance certificates

---

## 16. Sample Use Cases

### Use Case 1: Sports Day
- **Event**: Annual Sports Day
- **Owner**: Sports Department Head
- **Target Audience**: All students + their parents
- **RSVP**: Optional (helps with planning refreshments)
- **Attendance**: QR code check-in at gate
- **Notifications**:
  - Announcement 2 weeks before
  - Reminder 1 day before with QR code
  - Day-of reminder with venue details

### Use Case 2: Parent-Teacher Meeting (Grade 3)
- **Event**: Term 1 PTM - Grade 3
- **Owners**: Grade 3 Class Teachers (3A, 3B, 3C)
- **Target Audience**: Parents of Grade 3 students only
- **RSVP**: Required (with time slot selection)
- **Capacity**: 30 parents per session
- **Attendance**: Manual check-in by teachers
- **Notifications**:
  - Invitation 1 week before with RSVP deadline
  - Confirmation with time slot after RSVP
  - Reminder 1 day before with location details

### Use Case 3: Board Meeting
- **Event**: Quarterly Board Meeting
- **Owner**: School Principal
- **Target Audience**: Board Members only
- **RSVP**: Required
- **Attendance**: Unique ID check-in
- **Privacy**: Private event, not visible to parents/students

### Use Case 4: Cultural Festival
- **Event**: Multicultural Day
- **Owners**: Cultural Committee (5 staff members)
- **Target Audience**: All students, all parents, all staff
- **RSVP**: Optional
- **Capacity**: 500 people
- **Attendance**: QR code + Walk-in registration
- **Notifications**:
  - Save the date 1 month before
  - Detailed info 2 weeks before
  - RSVP reminder 1 week before
  - Day-of reminder with parking instructions

---

## 17. Security & Privacy Considerations

### Data Security
- Unique check-in codes must be cryptographically random
- QR codes should contain encrypted data
- Attendance data is sensitive (GDPR/data protection)
- Access logs for who viewed attendance data

### Privacy Controls
- Parents only see events relevant to their children
- Staff see events based on permissions
- Private events hidden from unauthorized users
- Opt-out option for non-mandatory events

### Audit Trail
- Track who created/modified events
- Log all RSVP changes
- Record attendance check-ins with staff member
- Track notification delivery

---

## 18. Future Enhancements

- **AI/ML**: Predict attendance based on historical data
- **Weather Integration**: Auto-alert if outdoor event and bad weather predicted
- **Photo Gallery**: Post-event photo sharing
- **Live Streaming**: Virtual attendance for remote parents
- **Event Feedback**: Post-event surveys
- **Gamification**: Badges for event participation
- **Multi-Language**: Event details in multiple languages
- **Social Sharing**: Share events on social media
- **Payment Integration**: Pay for event tickets via portal

---

## Questions for Discussion

1. **Should we allow parents to add personal calendar items** (e.g., "Child's birthday party") or keep it school-events only?

2. **Event approval workflow**: Should events require approval before publishing, or trust event owners?

3. **RSVP time slots**: For events like PTM, should we allow selection of specific time slots within the event?

4. **Guest management**: For parent events, should we track guest details (names, relationship) or just count?

5. **Event conflicts**: Should system warn if creating event that conflicts with another major event?

6. **Historical data**: How many years of past events should we keep in the active system?

7. **Mobile app**: Should attendance check-in be web-based or require native mobile app for QR scanning?

8. **Reminders frequency**: What's appropriate? (1 week, 1 day, 1 hour before? Configurable per event?)

9. **No-show penalties**: Should we track no-show rates and apply any consequences for repeated no-shows on mandatory events?

10. **Multi-campus**: How should calendar work for schools with multiple campuses? Separate calendars or unified with campus filter?

---

**End of Specification**
