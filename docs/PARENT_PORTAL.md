# Parent Portal Documentation

## Overview

The Parent Portal is an external-facing module that allows parents/guardians to access their children's information including fee statements, attendance records, and school notifications. It operates with a completely separate authentication system from the staff portal.

## Architecture

### Multi-Portal Design

The Parent Portal is the first implementation of the **External User Portal Architecture**, designed to be a template for future portals (Suppliers, Drivers, Alumni, etc.).

```
┌─────────────────────────────────────────────────────────────┐
│                    SchoolDynamics                           │
├─────────────────────────────────────────────────────────────┤
│  INTERNAL USERS (Staff Portal)    │  EXTERNAL USERS         │
│  ─────────────────────────────    │  ──────────────────     │
│  • Staff authentication           │  • Parent Portal        │
│  • RBAC with roles/permissions    │  • Supplier Portal (*)  │
│  • Full system access             │  • Driver Portal (*)    │
│  • Session: user_id, user_roles   │  • Alumni Portal (*)    │
│                                   │                         │
│                                   │  * Future portals       │
└─────────────────────────────────────────────────────────────┘
```

### Key Design Principles

1. **Separate Authentication** - External users have their own authentication tables and session variables
2. **Data Scoping** - Users can only access data they're authorized to see (e.g., parents see only their children)
3. **Mobile-First UI** - Responsive design optimized for mobile devices
4. **Self-Service** - Registration, password reset without admin intervention
5. **Configurable** - Portal settings can be enabled/disabled per tenant

---

## Database Schema

### Tables

#### `parent_accounts`
Links to the existing `guardians` table for identity verification.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| guardian_id | BIGINT | FK to guardians table |
| email | VARCHAR(255) | Login email (unique) |
| phone | VARCHAR(20) | Contact phone |
| password_hash | VARCHAR(255) | Bcrypt password hash |
| status | ENUM | 'pending', 'active', 'suspended' |
| email_verified_at | TIMESTAMP | When email was verified |
| email_verification_token | VARCHAR(100) | Token for email verification |
| last_login_at | TIMESTAMP | Last successful login |
| last_login_ip | VARCHAR(45) | IP address of last login |
| failed_login_attempts | INT | Counter for lockout |
| locked_until | TIMESTAMP | Account lockout expiry |
| notification_preferences | JSON | User preferences |
| created_at | TIMESTAMP | Account creation date |

#### `parent_sessions`
For token-based authentication (mobile apps).

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| parent_account_id | BIGINT | FK to parent_accounts |
| token | VARCHAR(255) | Session token |
| device_type | VARCHAR(50) | 'web', 'android', 'ios' |
| device_name | VARCHAR(100) | Device identifier |
| expires_at | TIMESTAMP | Token expiry |

#### `parent_notifications`
Notifications sent to parents.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| parent_account_id | BIGINT | FK to parent_accounts |
| type | VARCHAR(50) | 'fee_reminder', 'grade_posted', 'attendance', 'announcement' |
| title | VARCHAR(255) | Notification title |
| message | TEXT | Notification body |
| data | JSON | Additional structured data |
| read_at | TIMESTAMP | When notification was read |
| created_at | TIMESTAMP | When notification was created |

#### `parent_portal_settings`
Configurable settings per tenant.

| Setting Key | Default | Description |
|-------------|---------|-------------|
| portal_enabled | true | Enable/disable portal access |
| self_registration | true | Allow self-registration |
| require_email_verification | true | Require email verification |
| show_grades | true | Allow viewing grades |
| show_attendance | true | Allow viewing attendance |
| show_fees | true | Allow viewing fee statements |
| show_timetable | true | Allow viewing timetable |
| allow_online_payment | false | Enable online payments |
| session_timeout_minutes | 60 | Session timeout |

---

## Authentication Flow

### Registration Process

```
1. Parent enters child's admission number
2. System finds student and their guardians
3. Parent enters email + phone
4. System verifies phone matches a guardian record
5. Account created with 'pending' status
6. Email verification sent (if enabled)
7. After verification, status changes to 'active'
```

### Login Process

```
1. Parent enters email + password
2. System checks account status
3. If locked, show lockout message
4. Verify password
5. On success:
   - Reset failed attempts
   - Update last_login_at/ip
   - Store in session:
     * parent_logged_in = true
     * parent_id = account ID
     * parent_guardian_id = guardian ID
     * parent_name = guardian name
     * parent_children = array of linked students
```

### Session Variables

| Variable | Description |
|----------|-------------|
| `parent_logged_in` | Boolean flag |
| `parent_id` | parent_accounts.id |
| `parent_guardian_id` | guardians.id |
| `parent_name` | Display name |
| `parent_children` | Array of student records |
| `parent_unread_notifications` | Unread notification count |

---

## Routes

### Public Routes (No Auth Required)

| Method | URL | Controller Method | Description |
|--------|-----|-------------------|-------------|
| GET | /parent | - | Redirect to login/dashboard |
| GET | /parent/login | showLogin | Login page |
| POST | /parent/login | login | Process login |
| GET | /parent/register | showRegister | Registration page |
| POST | /parent/register | register | Process registration |
| GET | /parent/verify-email/:token | verifyEmail | Email verification |
| GET | /parent/forgot-password | showForgotPassword | Forgot password page |
| POST | /parent/forgot-password | sendResetLink | Send reset email |
| GET | /parent/reset-password/:token | showResetPassword | Reset password page |
| POST | /parent/reset-password | resetPassword | Process password reset |

### Protected Routes (Require Parent Auth)

| Method | URL | Controller Method | Description |
|--------|-----|-------------------|-------------|
| GET | /parent/dashboard | index | Main dashboard |
| GET | /parent/logout | logout | Logout |
| GET | /parent/notifications | notifications | View all notifications |
| GET | /parent/profile | profile | View/edit profile |
| POST | /parent/update-password | updatePassword | Change password |
| GET | /parent/child/:id/profile | childProfile | View child's profile |
| GET | /parent/child/:id/fees | fees | View child's fee statement |
| GET | /parent/child/:id/attendance | attendance | View child's attendance |

---

## Security Features

### Data Scoping

The `verifyChildAccess($studentId)` method ensures parents can only view their linked children:

```php
private function verifyChildAccess($studentId)
{
    $children = $_SESSION['parent_children'] ?? [];
    $hasAccess = false;

    foreach ($children as $child) {
        if ($child['id'] == $studentId) {
            $hasAccess = true;
            break;
        }
    }

    if (!$hasAccess) {
        flash('error', 'You do not have access to this student.');
        Response::redirect('/parent/dashboard');
    }
}
```

### Account Lockout

After 5 failed login attempts, the account is locked for 15 minutes.

### Password Requirements

- Minimum 8 characters
- Passwords are hashed using `PASSWORD_DEFAULT` (bcrypt)

### Email Verification

When enabled, accounts remain in 'pending' status until email is verified via a unique token.

---

## Views Structure

```
app/Views/parent/
├── login.php                 # Self-contained login page
├── register.php              # Self-contained registration page
├── forgot-password.php       # Self-contained forgot password
├── reset-password.php        # Self-contained reset password
├── dashboard.php             # Uses parent layout
├── _dashboard_content.php    # Dashboard content
├── fees.php                  # Uses parent layout
├── _fees_content.php         # Fee statement content
├── attendance.php            # Uses parent layout
├── _attendance_content.php   # Attendance content
├── child-profile.php         # Uses parent layout
├── _child-profile_content.php # Child profile content
├── notifications.php         # Uses parent layout
├── _notifications_content.php # Notifications content
├── profile.php               # Uses parent layout
└── _profile_content.php      # Profile content

app/Views/layouts/
└── parent.php                # Parent portal layout (mobile-friendly)
```

---

## UI Features

### Mobile-First Design

- Bottom navigation bar on mobile devices
- Touch-friendly buttons and controls
- Responsive cards and tables
- Sticky header with school branding

### Navigation

For single-child parents:
- Home | Fees | Attendance | Alerts | Settings

For multi-child parents:
- Home | Alerts | Settings
- (Fees/Attendance accessed per child from dashboard)

### Dashboard Components

1. **Children Cards** - Overview of each child with:
   - Photo placeholder (initials)
   - Name and admission number
   - Current class
   - Pending fees amount
   - Attendance rate (last 30 days)
   - Quick action buttons

2. **Recent Notifications** - Latest 5 notifications with:
   - Type-specific icons
   - Title and message preview
   - Timestamp

---

## Integration Points

### Sending Notifications

To send a notification to a parent from anywhere in the system:

```php
$pdo = Database::getTenantConnection();

// Find parent account for a student
$stmt = $pdo->prepare("
    SELECT pa.id
    FROM parent_accounts pa
    JOIN guardians g ON pa.guardian_id = g.id
    JOIN student_guardians sg ON g.id = sg.guardian_id
    WHERE sg.student_id = :student_id
");
$stmt->execute(['student_id' => $studentId]);
$parentAccounts = $stmt->fetchAll();

// Send notification
foreach ($parentAccounts as $pa) {
    $stmt = $pdo->prepare("
        INSERT INTO parent_notifications
        (parent_account_id, type, title, message, data, created_at)
        VALUES (:parent_id, :type, :title, :message, :data, NOW())
    ");
    $stmt->execute([
        'parent_id' => $pa['id'],
        'type' => 'fee_reminder',
        'title' => 'Fee Payment Reminder',
        'message' => 'Your child has a pending fee balance of KES 50,000',
        'data' => json_encode(['student_id' => $studentId, 'amount' => 50000])
    ]);
}
```

### Fee Reminder Automation

The system can automatically send fee reminders:

```php
// Example: Send reminders for accounts with balance > 0
$stmt = $pdo->query("
    SELECT s.id as student_id,
           CONCAT(s.first_name, ' ', s.last_name) as student_name,
           SUM(i.total_amount - i.amount_paid) as balance
    FROM students s
    JOIN invoices i ON s.id = i.student_id
    WHERE i.status IN ('unpaid', 'partial')
    GROUP BY s.id
    HAVING balance > 0
");
// ... create notifications for each
```

---

## Admin Management (Portal Management Module)

Administrators can manage the Parent Portal through:

### Settings > Portal Management > Parents

- **Accounts** - View/manage parent accounts
- **Pending Approvals** - Approve pending registrations
- **Notifications** - Send bulk notifications
- **Settings** - Configure portal settings
- **Reports** - Portal usage analytics

---

## Future Enhancements

1. **Online Payments** - Integrate M-Pesa/card payments
2. **Push Notifications** - Mobile app push notifications
3. **Chat/Messaging** - Direct messaging with teachers
4. **Document Downloads** - Report cards, receipts
5. **Event Calendar** - School events and meetings
6. **Homework Tracking** - View assignments
7. **Bus Tracking** - Real-time school bus location

---

## Troubleshooting

### Common Issues

**Q: Parent can't register - "Guardian not found"**
A: Ensure the guardian's phone number in the system matches exactly what the parent enters (including country code format).

**Q: Parent can't see a child**
A: Check that the guardian is linked to the student in `student_guardians` table.

**Q: Account stuck in 'pending' status**
A: Either the parent hasn't verified their email, or an admin needs to manually activate the account.

**Q: Login says "Account locked"**
A: Wait 15 minutes or have an admin reset `failed_login_attempts` and clear `locked_until`.

---

## Migration Script

Run the migration to create the parent portal tables:

```bash
mysql -u root -p database_name < database/migrations/parent_portal_tables.sql
```

Or execute via the application's migration system.
