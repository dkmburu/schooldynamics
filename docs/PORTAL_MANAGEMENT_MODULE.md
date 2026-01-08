# Portal Management Module

## Overview

The Portal Management module provides administrative control over all external user portals (Parents, Suppliers, Drivers, Alumni, etc.). This centralized module allows staff to manage accounts, settings, and communications for external users.

## Module Structure

```
Portal Management (Main Module)
├── Parent Portal (Submodule)
│   ├── Accounts          - Manage parent accounts
│   ├── Registrations     - Approve/reject pending registrations
│   ├── Notifications     - Send and manage notifications
│   └── Settings          - Portal configuration
│
├── Supplier Portal (Submodule) [Future]
│   ├── Accounts          - Manage supplier accounts
│   ├── Registrations     - Approve/reject applications
│   ├── Documents         - Supplier documents/contracts
│   └── Settings          - Portal configuration
│
├── Driver Portal (Submodule) [Future]
│   ├── Accounts          - Manage driver accounts
│   ├── Assignments       - Vehicle/route assignments
│   ├── Tracking          - GPS/location data
│   └── Settings          - Portal configuration
│
└── Alumni Portal (Submodule) [Future]
    ├── Accounts          - Manage alumni accounts
    ├── Directory         - Alumni directory access
    ├── Events            - Alumni events
    └── Settings          - Portal configuration
```

## Navigation Menu

### Menu Icon & Position
- **Icon**: `ti ti-users-group` (Tabler Icons)
- **Position**: After Settings module
- **Label**: "Portal Management"

### Submenu Items (Parent Portal)

| Submenu | Route | Icon | Description |
|---------|-------|------|-------------|
| Parent Accounts | /portals/parents | ti-users | List all parent accounts |
| Pending Approvals | /portals/parents/pending | ti-user-check | Review pending registrations |
| Send Notification | /portals/parents/notifications | ti-bell | Send bulk notifications |
| Portal Settings | /portals/parents/settings | ti-settings | Configure parent portal |

## Database Changes

### Add Module & Submodules

```sql
-- Add Portal Management module
INSERT INTO modules (name, display_name, icon, route, sort_order, is_active) VALUES
('PortalManagement', 'Portal Management', 'ti ti-users-group', '/portals', 15, 1);

-- Get the module ID
SET @portal_module_id = LAST_INSERT_ID();

-- Add Parent Portal submodule
INSERT INTO submodules (module_id, name, display_name, route, sort_order, is_active) VALUES
(@portal_module_id, 'ParentPortal', 'Parent Portal', '/portals/parents', 1, 1);

-- Get submodule ID and create permissions
SET @parent_portal_id = LAST_INSERT_ID();

INSERT INTO permissions (submodule_id, name, display_name, action) VALUES
(@parent_portal_id, 'PortalManagement.ParentPortal', 'Parent Portal Management', 'view'),
(@parent_portal_id, 'PortalManagement.ParentPortal', 'Parent Portal Management', 'modify');

-- Add permissions to ADMIN role (assuming role_id = 1)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions WHERE name = 'PortalManagement.ParentPortal';
```

### Future Portal Submodules

```sql
-- Supplier Portal (when implemented)
INSERT INTO submodules (module_id, name, display_name, route, sort_order, is_active) VALUES
(@portal_module_id, 'SupplierPortal', 'Supplier Portal', '/portals/suppliers', 2, 0);

-- Driver Portal (when implemented)
INSERT INTO submodules (module_id, name, display_name, route, sort_order, is_active) VALUES
(@portal_module_id, 'DriverPortal', 'Driver Portal', '/portals/drivers', 3, 0);

-- Alumni Portal (when implemented)
INSERT INTO submodules (module_id, name, display_name, route, sort_order, is_active) VALUES
(@portal_module_id, 'AlumniPortal', 'Alumni Portal', '/portals/alumni', 4, 0);
```

## Routes

### Parent Portal Management Routes

```php
// Portal Management - Parent Portal
Router::get('/portals/parents', 'PortalManagementController@parentAccounts');
Router::get('/portals/parents/pending', 'PortalManagementController@parentPending');
Router::post('/portals/parents/:id/approve', 'PortalManagementController@approveParent');
Router::post('/portals/parents/:id/reject', 'PortalManagementController@rejectParent');
Router::post('/portals/parents/:id/suspend', 'PortalManagementController@suspendParent');
Router::post('/portals/parents/:id/activate', 'PortalManagementController@activateParent');
Router::post('/portals/parents/:id/reset-password', 'PortalManagementController@resetParentPassword');
Router::get('/portals/parents/:id', 'PortalManagementController@viewParent');

// Notifications
Router::get('/portals/parents/notifications', 'PortalManagementController@parentNotifications');
Router::post('/portals/parents/notifications/send', 'PortalManagementController@sendParentNotification');

// Settings
Router::get('/portals/parents/settings', 'PortalManagementController@parentSettings');
Router::post('/portals/parents/settings', 'PortalManagementController@updateParentSettings');
```

## Controller Structure

### PortalManagementController

```php
class PortalManagementController
{
    // ========================================
    // PARENT PORTAL MANAGEMENT
    // ========================================

    /**
     * List all parent accounts with filters
     */
    public function parentAccounts()
    {
        Gate::authorize('PortalManagement.ParentPortal.view');
        // ... list accounts with search/filter
    }

    /**
     * List pending registrations
     */
    public function parentPending()
    {
        Gate::authorize('PortalManagement.ParentPortal.view');
        // ... list pending accounts
    }

    /**
     * Approve a pending registration
     */
    public function approveParent($id)
    {
        Gate::authorize('PortalManagement.ParentPortal.modify');
        // ... approve account
    }

    /**
     * Send notification to parents
     */
    public function sendParentNotification()
    {
        Gate::authorize('PortalManagement.ParentPortal.modify');
        // ... send bulk notification
    }

    /**
     * Portal settings
     */
    public function parentSettings()
    {
        Gate::authorize('PortalManagement.ParentPortal.modify');
        // ... show/update settings
    }

    // ========================================
    // SUPPLIER PORTAL MANAGEMENT (Future)
    // ========================================

    // Similar structure for suppliers...

    // ========================================
    // DRIVER PORTAL MANAGEMENT (Future)
    // ========================================

    // Similar structure for drivers...
}
```

## Views Structure

```
app/Views/portals/
├── parents/
│   ├── index.php              # Parent accounts list
│   ├── _accounts_content.php  # Accounts table content
│   ├── pending.php            # Pending approvals
│   ├── _pending_content.php   # Pending table content
│   ├── view.php               # View single account
│   ├── _view_content.php      # Account details
│   ├── notifications.php      # Send notifications
│   ├── _notifications_content.php
│   ├── settings.php           # Portal settings
│   └── _settings_content.php
│
├── suppliers/ (Future)
│   └── ...
│
├── drivers/ (Future)
│   └── ...
│
└── alumni/ (Future)
    └── ...
```

## UI Design

### Parent Accounts List

```
┌─────────────────────────────────────────────────────────────────┐
│ Portal Management > Parent Portal                               │
├─────────────────────────────────────────────────────────────────┤
│ [Accounts] [Pending (5)] [Send Notification] [Settings]         │
├─────────────────────────────────────────────────────────────────┤
│ Filters: [Search...] [Status ▼] [Class ▼] [Search]              │
├─────────────────────────────────────────────────────────────────┤
│ ┌───────────────────────────────────────────────────────────┐   │
│ │ Name            │ Email           │ Children │ Status     │   │
│ ├───────────────────────────────────────────────────────────┤   │
│ │ John Doe        │ john@email.com  │ 2        │ ● Active   │   │
│ │ Jane Smith      │ jane@email.com  │ 1        │ ○ Pending  │   │
│ │ Mike Johnson    │ mike@email.com  │ 3        │ ● Active   │   │
│ └───────────────────────────────────────────────────────────┘   │
│                                                                  │
│ Showing 1-10 of 245 accounts                    [< 1 2 3 ... >] │
└─────────────────────────────────────────────────────────────────┘
```

### Send Notification Form

```
┌─────────────────────────────────────────────────────────────────┐
│ Send Notification to Parents                                    │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│ Recipients:                                                      │
│ ○ All Parents                                                    │
│ ○ Parents of Class: [Select Class ▼]                            │
│ ○ Parents with Fee Balance                                       │
│ ○ Specific Parents: [Select Parents...]                         │
│                                                                  │
│ Notification Type:                                               │
│ [Select Type ▼]                                                  │
│   - Announcement                                                 │
│   - Fee Reminder                                                 │
│   - Event Notice                                                 │
│   - General                                                      │
│                                                                  │
│ Title:                                                           │
│ [_______________________________________________]                │
│                                                                  │
│ Message:                                                         │
│ ┌───────────────────────────────────────────────┐               │
│ │                                               │               │
│ │                                               │               │
│ └───────────────────────────────────────────────┘               │
│                                                                  │
│ Preview: 125 parents will receive this notification              │
│                                                                  │
│                              [Cancel] [Send Notification]        │
└─────────────────────────────────────────────────────────────────┘
```

### Portal Settings

```
┌─────────────────────────────────────────────────────────────────┐
│ Parent Portal Settings                                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│ General Settings                                                 │
│ ├─ Portal Enabled            [✓]                                │
│ ├─ Allow Self-Registration   [✓]                                │
│ └─ Require Email Verification [✓]                               │
│                                                                  │
│ Feature Access                                                   │
│ ├─ Show Fee Statements       [✓]                                │
│ ├─ Show Attendance Records   [✓]                                │
│ ├─ Show Grades/Results       [✓]                                │
│ ├─ Show Timetable            [✓]                                │
│ └─ Allow Online Payments     [ ]                                │
│                                                                  │
│ Security                                                         │
│ ├─ Session Timeout (minutes) [60]                               │
│ ├─ Max Login Attempts        [5]                                │
│ └─ Lockout Duration (mins)   [15]                               │
│                                                                  │
│                                         [Cancel] [Save Settings] │
└─────────────────────────────────────────────────────────────────┘
```

## Permission Matrix

| Role | View Accounts | Modify Accounts | Send Notifications | Manage Settings |
|------|--------------|-----------------|-------------------|-----------------|
| Admin | ✓ | ✓ | ✓ | ✓ |
| Head Teacher | ✓ | ✓ | ✓ | ✗ |
| Bursar | ✓ | ✗ | ✓ (fee-related) | ✗ |
| Clerk | ✓ | ✗ | ✗ | ✗ |
| Teacher | ✗ | ✗ | ✗ | ✗ |

## Implementation Phases

### Phase 1: Parent Portal Management (Current)
- [x] Parent Portal database tables
- [x] Parent authentication & registration
- [x] Parent dashboard & views
- [ ] Portal Management module navigation
- [ ] Parent accounts management UI
- [ ] Pending approvals workflow
- [ ] Bulk notifications
- [ ] Portal settings UI

### Phase 2: Supplier Portal (Future)
- Supplier accounts table
- Supplier authentication
- Supplier dashboard (view POs, invoices, payments)
- Supplier document uploads
- Admin management UI

### Phase 3: Driver Portal (Future)
- Driver accounts table
- Driver authentication
- Route assignments
- Trip logging
- GPS tracking integration
- Admin management UI

### Phase 4: Alumni Portal (Future)
- Alumni accounts table
- Alumni authentication
- Alumni directory
- Event management
- Donation/contribution tracking
- Admin management UI

## API Endpoints (Future Mobile App)

```
POST   /api/v1/parent/auth/login
POST   /api/v1/parent/auth/register
POST   /api/v1/parent/auth/refresh-token
POST   /api/v1/parent/auth/logout
GET    /api/v1/parent/children
GET    /api/v1/parent/children/:id/fees
GET    /api/v1/parent/children/:id/attendance
GET    /api/v1/parent/notifications
POST   /api/v1/parent/notifications/:id/read
GET    /api/v1/parent/profile
PUT    /api/v1/parent/profile
POST   /api/v1/parent/change-password
```

## Statistics Dashboard

The Portal Management module should display:

```
┌─────────────────────────────────────────────────────────────────┐
│ Portal Overview                                                  │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│ ┌─────────────┐ ┌─────────────┐ ┌─────────────┐ ┌─────────────┐ │
│ │ 245         │ │ 12          │ │ 89%         │ │ 5           │ │
│ │ Total       │ │ Pending     │ │ Active      │ │ Suspended   │ │
│ │ Parents     │ │ Approvals   │ │ This Month  │ │ Accounts    │ │
│ └─────────────┘ └─────────────┘ └─────────────┘ └─────────────┘ │
│                                                                  │
│ Recent Activity                                                  │
│ • John Doe registered - 2 hours ago                             │
│ • Fee reminder sent to 45 parents - 1 day ago                   │
│ • Jane Smith account activated - 2 days ago                     │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```
