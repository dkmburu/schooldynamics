# Role-Based Access Control (RBAC) Implementation Plan

## Design Principles

1. **One User = One Role** - Simplicity over flexibility
2. **Two Permission Types Only** - `view` and `modify`
3. **Module.Submodule based permissions** - Granular but manageable

---

## Current State

### Existing Tables
```
roles              - 5 system roles (ADMIN, HEAD_TEACHER, TEACHER, BURSAR, CLERK)
permissions        - Empty (needs population)
role_permissions   - Links roles to permissions
user_roles         - TO BE DELETED (many-to-many not needed)
users              - User accounts (needs role_id FK)
modules            - 12 main navigation modules
submodules         - 73+ navigation items with routes
```

### Current Gate Implementation
- `Gate` class in `app/Middlewares/PermissionMiddleware.php`
- `hasPermission()` helper in `app/Helpers/functions.php`
- Session stores `$_SESSION['user_roles']` and `$_SESSION['user_permissions']`

---

## Database Changes

### 1. Add `role_id` to `users` table
```sql
ALTER TABLE users
ADD COLUMN role_id INT UNSIGNED NULL AFTER status,
ADD INDEX idx_users_role (role_id),
ADD CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id);
```

### 2. Drop `user_roles` table
```sql
-- First migrate existing data to users.role_id (take first role if multiple)
UPDATE users u
SET u.role_id = (
    SELECT ur.role_id FROM user_roles ur WHERE ur.user_id = u.id LIMIT 1
);

-- Then drop the table
DROP TABLE IF EXISTS user_roles;
```

### 3. Update `permissions` table - change action ENUM
```sql
ALTER TABLE permissions
MODIFY COLUMN action ENUM('view', 'modify') NOT NULL;
```

### 4. Add `user_id` to `staff` table (link staff to user account)
```sql
ALTER TABLE staff
ADD COLUMN user_id BIGINT UNSIGNED NULL AFTER id,
ADD INDEX idx_staff_user (user_id),
ADD CONSTRAINT fk_staff_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;
```

### 5. Auto-populate permissions from submodules
```sql
-- Clear existing permissions
TRUNCATE TABLE permissions;
TRUNCATE TABLE role_permissions;

-- Generate VIEW permissions for all active submodules
INSERT INTO permissions (submodule_id, name, display_name, action)
SELECT
    s.id,
    CONCAT(s.name, '.view'),
    CONCAT('View ', s.display_name),
    'view'
FROM submodules s
WHERE s.is_active = 1;

-- Generate MODIFY permissions for all active submodules
INSERT INTO permissions (submodule_id, name, display_name, action)
SELECT
    s.id,
    CONCAT(s.name, '.modify'),
    CONCAT('Modify ', s.display_name),
    'modify'
FROM submodules s
WHERE s.is_active = 1;
```

---

## Permission Naming Convention

Format: `Submodule.Name.action`

Examples:
- `Students.Applications.view` - Can view applications
- `Students.Applications.modify` - Can create/edit/delete applications
- `Finance.Invoices.view` - Can view invoices
- `Finance.Invoices.modify` - Can create/edit/delete invoices
- `HRPayroll.StaffDirectory.view` - Can view staff
- `HRPayroll.StaffDirectory.modify` - Can add/edit staff

---

## Default Role Permissions Matrix

| Submodule | ADMIN | HEAD_TEACHER | TEACHER | BURSAR | CLERK |
|-----------|-------|--------------|---------|--------|-------|
| **Students Module** |
| Students.Applications | view, modify | view, modify | view | view | view, modify |
| Students.ScreeningQueue | view, modify | view, modify | view | - | view, modify |
| Students.Records | view, modify | view, modify | view | view | view, modify |
| **Academics Module** |
| Academics.Classes | view, modify | view, modify | view | - | view |
| Academics.Subjects | view, modify | view, modify | view | - | view |
| Academics.Attendance | view, modify | view, modify | view, modify | - | view |
| **Finance Module** |
| Finance.Dashboard | view, modify | view | - | view, modify | view |
| Finance.FeeStructures | view, modify | view | - | view, modify | view |
| Finance.Invoices | view, modify | view | - | view, modify | view |
| Finance.Payments | view, modify | view | - | view, modify | view, modify |
| Finance.Reports | view, modify | view | - | view | - |
| Finance.Expenses | view, modify | view | - | view, modify | view |
| Finance.Budgets | view, modify | view | - | view, modify | - |
| **HR & Payroll Module** |
| HRPayroll.Dashboard | view, modify | view | - | view | - |
| HRPayroll.StaffDirectory | view, modify | view | - | view | - |
| HRPayroll.Payroll | view, modify | - | - | view, modify | - |
| HRPayroll.Payslips | view, modify | view | view* | view, modify | - |
| HRPayroll.Reports | view, modify | view | - | view | - |
| **Settings Module** |
| Settings.* | view, modify | - | - | - | - |
| **Communication Module** |
| Communication.Messages | view, modify | view, modify | view, modify | view | view |
| Communication.Templates | view, modify | view, modify | - | - | - |
| **Reports Module** |
| Reports.All | view, modify | view | view | view | view |

*Teachers can only view their own payslips (handled in code)

---

## Implementation Phases

### Phase 1: Database Migration
- [ ] Create migration file with all schema changes
- [ ] Migrate `user_roles` data to `users.role_id`
- [ ] Drop `user_roles` table
- [ ] Update `permissions` ENUM
- [ ] Generate permissions from submodules
- [ ] Seed default role-permission mappings

### Phase 2: Update Authentication
- [ ] Update `AuthController::login()` to load single role
- [ ] Update session structure (`$_SESSION['user_role']` instead of `user_roles`)
- [ ] Update `Gate` class methods
- [ ] Update `hasPermission()` helper

### Phase 3: Role Management UI
- [ ] Create `RolesController`
- [ ] Roles listing page (`/settings/roles`)
- [ ] Role form with permission matrix (checkboxes grouped by module)
- [ ] Edit role permissions
- [ ] Clone role functionality
- [ ] Protect system roles from deletion

### Phase 4: User Management Integration
- [ ] Add role dropdown to user create/edit form
- [ ] Show role in users listing
- [ ] Filter users by role

### Phase 5: Staff-User Linking
- [ ] Add "Create User Account" button on staff profile
- [ ] Auto-populate user form from staff data
- [ ] Link staff.user_id after account creation
- [ ] Show linked user info on staff profile

### Phase 6: Navigation Filtering
- [ ] Update `getNavigationModules()` to filter by permissions
- [ ] Hide modules where user has no `view` permission
- [ ] Hide submodules user can't view

### Phase 7: Controller Enforcement
- [ ] Add permission checks to all controllers
- [ ] Use middleware for route-level protection
- [ ] Check `modify` permission before create/update/delete operations

---

## File Structure

```
app/
├── Controllers/
│   └── RolesController.php           # NEW
├── Views/
│   └── settings/
│       ├── roles.php                 # NEW - Roles listing
│       ├── role-form.php             # NEW - Create/Edit role
│       └── _roles_content.php        # NEW
└── Middlewares/
    └── PermissionMiddleware.php      # UPDATE

database/
└── migrations/
    ├── rbac_schema_changes.sql       # NEW
    └── rbac_seed_data.sql            # NEW
```

---

## Routes to Add

```php
// Role Management
Router::get('/settings/roles', 'RolesController@index');
Router::get('/settings/roles/create', 'RolesController@create');
Router::post('/settings/roles', 'RolesController@store');
Router::get('/settings/roles/:id/edit', 'RolesController@edit');
Router::put('/settings/roles/:id', 'RolesController@update');
Router::delete('/settings/roles/:id', 'RolesController@destroy');
Router::post('/settings/roles/:id/clone', 'RolesController@clone');

// Staff User Account
Router::post('/hr-payroll/staff/:id/create-account', 'HRPayrollController@createUserAccount');
```

---

## Updated Session Structure

**Before (multiple roles):**
```php
$_SESSION['user_roles'] = [
    ['id' => 1, 'name' => 'ADMIN', 'display_name' => 'System Administrator'],
    ['id' => 4, 'name' => 'BURSAR', 'display_name' => 'Bursar']
];
$_SESSION['user_permissions'] = ['Finance.Invoices.view', 'Finance.Invoices.write', ...];
```

**After (single role):**
```php
$_SESSION['user_role'] = [
    'id' => 1,
    'name' => 'ADMIN',
    'display_name' => 'System Administrator'
];
$_SESSION['user_permissions'] = ['Finance.Invoices.view', 'Finance.Invoices.modify', ...];
```

---

## Updated Gate Class

```php
class Gate
{
    public static function allows($permission)
    {
        // ADMIN bypasses all checks
        if (self::isAdmin()) {
            return true;
        }
        return in_array($permission, $_SESSION['user_permissions'] ?? []);
    }

    public static function canView($submodule)
    {
        return self::allows($submodule . '.view');
    }

    public static function canModify($submodule)
    {
        return self::allows($submodule . '.modify');
    }

    public static function hasRole($roleName)
    {
        $role = $_SESSION['user_role'] ?? null;
        return $role && $role['name'] === $roleName;
    }

    public static function isAdmin()
    {
        return self::hasRole('ADMIN');
    }
}
```

---

## Usage Examples

### In Views
```php
<?php if (Gate::canView('Students.Applications')): ?>
    <a href="/applicants">View Applications</a>
<?php endif; ?>

<?php if (Gate::canModify('Students.Applications')): ?>
    <a href="/applicants/create" class="btn btn-primary">Add Application</a>
    <button class="btn btn-danger">Delete</button>
<?php endif; ?>
```

### In Controllers
```php
public function create()
{
    if (!Gate::canModify('Students.Applications')) {
        Gate::deny('You do not have permission to create applications');
    }
    // ... continue
}
```

### Route Middleware
```php
Router::get('/applicants', 'ApplicantsController@index')
    ->middleware(new PermissionMiddleware('Students.Applications.view'));

Router::post('/applicants', 'ApplicantsController@store')
    ->middleware(new PermissionMiddleware('Students.Applications.modify'));
```

---

## Security Notes

1. **ADMIN role** - Always has full access (bypass permission checks)
2. **System roles** - Cannot be deleted (is_system = 1)
3. **Audit logging** - Log all role/permission changes
4. **Session refresh** - Regenerate session after role change

---

## Next Steps

1. Approve this simplified plan
2. Create and run database migration
3. Implement Phase 1-2 (Database + Auth updates)
4. Implement Phase 3 (Role Management UI)
5. Continue with remaining phases
