-- RBAC Default Role Permissions Seed
-- Based on permission matrix from RBAC_IMPLEMENTATION_PLAN.md
-- Run after rbac_schema_changes.sql

-- ============================================================
-- ADMIN ROLE (ID: 1) - Full access to everything
-- ============================================================
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, p.id FROM permissions p;

-- ============================================================
-- HEAD_TEACHER ROLE (ID: 2)
-- ============================================================

-- Students Module - view & modify
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, p.id FROM permissions p
WHERE p.name IN (
    'Students.Applications.view', 'Students.Applications.modify',
    'Students.ScreeningQueue.view', 'Students.ScreeningQueue.modify',
    'Students.Records.view', 'Students.Records.modify'
);

-- Academics Module - view & modify
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, p.id FROM permissions p
WHERE p.name IN (
    'Academics.Classes.view', 'Academics.Classes.modify',
    'Academics.Subjects.view', 'Academics.Subjects.modify',
    'Academics.Attendance.view', 'Academics.Attendance.modify'
);

-- Finance Module - view only
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, p.id FROM permissions p
WHERE p.name LIKE 'Finance.%.view';

-- HR Payroll Module - view only (except Payroll which is restricted)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, p.id FROM permissions p
WHERE p.name IN (
    'HRPayroll.Dashboard.view',
    'HRPayroll.StaffDirectory.view',
    'HRPayroll.Payslips.view',
    'HRPayroll.Reports.view'
);

-- Communication - view & modify
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, p.id FROM permissions p
WHERE p.name IN (
    'Communication.Messages.view', 'Communication.Messages.modify',
    'Communication.Templates.view', 'Communication.Templates.modify'
);

-- Reports - view only
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, p.id FROM permissions p
WHERE p.name = 'Reports.All.view';

-- Tasks - view & modify
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, p.id FROM permissions p
WHERE p.name LIKE 'Tasks.%.view' OR p.name LIKE 'Tasks.%.modify';

-- Transport - view & modify
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, p.id FROM permissions p
WHERE p.name LIKE 'Transport.%.view' OR p.name LIKE 'Transport.%.modify';

-- Meals - view & modify
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, p.id FROM permissions p
WHERE p.name LIKE 'Meals.%.view' OR p.name LIKE 'Meals.%.modify';

-- Staff (old module) - view only
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, p.id FROM permissions p
WHERE p.name LIKE 'Staff.%.view';

-- ============================================================
-- TEACHER ROLE (ID: 3)
-- ============================================================

-- Students Module - view only
INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, p.id FROM permissions p
WHERE p.name IN (
    'Students.Applications.view',
    'Students.ScreeningQueue.view',
    'Students.Records.view'
);

-- Academics Module - view + attendance modify
INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, p.id FROM permissions p
WHERE p.name IN (
    'Academics.Classes.view',
    'Academics.Subjects.view',
    'Academics.Attendance.view', 'Academics.Attendance.modify'
);

-- HRPayroll - own payslip view only
INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, p.id FROM permissions p
WHERE p.name = 'HRPayroll.Payslips.view';

-- Communication - view & modify messages
INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, p.id FROM permissions p
WHERE p.name IN (
    'Communication.Messages.view', 'Communication.Messages.modify'
);

-- Reports - view only
INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, p.id FROM permissions p
WHERE p.name = 'Reports.All.view';

-- Tasks - own tasks
INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, p.id FROM permissions p
WHERE p.name IN (
    'Tasks.MyTasks.view', 'Tasks.MyTasks.modify',
    'Tasks.CreateTask.view', 'Tasks.CreateTask.modify'
);

-- ============================================================
-- BURSAR ROLE (ID: 4)
-- ============================================================

-- Students Module - view only (for payment context)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 4, p.id FROM permissions p
WHERE p.name IN (
    'Students.Applications.view',
    'Students.Records.view'
);

-- Finance Module - full access
INSERT INTO role_permissions (role_id, permission_id)
SELECT 4, p.id FROM permissions p
WHERE p.name LIKE 'Finance.%.view' OR p.name LIKE 'Finance.%.modify';

-- HR Payroll - payroll management
INSERT INTO role_permissions (role_id, permission_id)
SELECT 4, p.id FROM permissions p
WHERE p.name IN (
    'HRPayroll.Dashboard.view',
    'HRPayroll.StaffDirectory.view',
    'HRPayroll.PayrollRuns.view', 'HRPayroll.PayrollRuns.modify',
    'HRPayroll.Payslips.view', 'HRPayroll.Payslips.modify',
    'HRPayroll.SalaryStructures.view', 'HRPayroll.SalaryStructures.modify',
    'HRPayroll.Allowances.view', 'HRPayroll.Allowances.modify',
    'HRPayroll.Loans.view', 'HRPayroll.Loans.modify',
    'HRPayroll.Statutory.view', 'HRPayroll.Statutory.modify',
    'HRPayroll.Reports.view'
);

-- Communication - view only
INSERT INTO role_permissions (role_id, permission_id)
SELECT 4, p.id FROM permissions p
WHERE p.name = 'Communication.Messages.view';

-- Reports - view only
INSERT INTO role_permissions (role_id, permission_id)
SELECT 4, p.id FROM permissions p
WHERE p.name = 'Reports.All.view';

-- Transport - view tariffs
INSERT INTO role_permissions (role_id, permission_id)
SELECT 4, p.id FROM permissions p
WHERE p.name = 'Transport.Overview.view';

-- ============================================================
-- CLERK ROLE (ID: 5)
-- ============================================================

-- Students Module - view & modify applications/records
INSERT INTO role_permissions (role_id, permission_id)
SELECT 5, p.id FROM permissions p
WHERE p.name IN (
    'Students.Applications.view', 'Students.Applications.modify',
    'Students.ScreeningQueue.view', 'Students.ScreeningQueue.modify',
    'Students.Records.view', 'Students.Records.modify'
);

-- Academics Module - view only
INSERT INTO role_permissions (role_id, permission_id)
SELECT 5, p.id FROM permissions p
WHERE p.name IN (
    'Academics.Classes.view',
    'Academics.Subjects.view',
    'Academics.Attendance.view'
);

-- Finance Module - limited access (payments, invoices)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 5, p.id FROM permissions p
WHERE p.name IN (
    'Finance.Dashboard.view',
    'Finance.FeeStructures.view',
    'Finance.Invoices.view',
    'Finance.Payments.view', 'Finance.Payments.modify',
    'Finance.StudentAccounts.view',
    'Finance.FamilyAccounts.view',
    'Finance.RecordPayment.view', 'Finance.RecordPayment.modify'
);

-- Communication - view only
INSERT INTO role_permissions (role_id, permission_id)
SELECT 5, p.id FROM permissions p
WHERE p.name = 'Communication.Messages.view';

-- Reports - view only
INSERT INTO role_permissions (role_id, permission_id)
SELECT 5, p.id FROM permissions p
WHERE p.name = 'Reports.All.view';

-- Tasks - own tasks
INSERT INTO role_permissions (role_id, permission_id)
SELECT 5, p.id FROM permissions p
WHERE p.name IN (
    'Tasks.MyTasks.view', 'Tasks.MyTasks.modify',
    'Tasks.CreateTask.view', 'Tasks.CreateTask.modify'
);

-- ============================================================
-- VERIFICATION
-- ============================================================

-- Check permission counts per role
-- SELECT r.name, COUNT(rp.permission_id) as permission_count
-- FROM roles r
-- LEFT JOIN role_permissions rp ON r.id = rp.role_id
-- GROUP BY r.id, r.name
-- ORDER BY r.id;
