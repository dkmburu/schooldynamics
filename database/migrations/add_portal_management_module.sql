-- ============================================================
-- Portal Management Module
-- Add module, submodules, and permissions for managing external portals
-- Run Date: 2026-01-05
-- ============================================================

-- Add Portal Management module
INSERT INTO modules (name, display_name, icon, sort_order, is_active) VALUES
('PortalManagement', 'Portal Management', 'ti ti-users-group', 15, 1);

SET @portal_module_id = LAST_INSERT_ID();

-- ============================================================
-- PARENT PORTAL SUBMODULE
-- ============================================================

INSERT INTO submodules (module_id, name, display_name, route, sort_order, is_active) VALUES
(@portal_module_id, 'ParentPortal', 'Parent Portal', '/portals/parents', 1, 1);

SET @parent_portal_submodule_id = LAST_INSERT_ID();

-- Create permissions for Parent Portal
INSERT INTO permissions (submodule_id, name, display_name, action) VALUES
(@parent_portal_submodule_id, 'PortalManagement.ParentPortal', 'Parent Portal Management', 'view'),
(@parent_portal_submodule_id, 'PortalManagement.ParentPortal', 'Parent Portal Management', 'modify');

-- ============================================================
-- SUPPLIER PORTAL SUBMODULE (Placeholder - Inactive)
-- ============================================================

INSERT INTO submodules (module_id, name, display_name, route, sort_order, is_active) VALUES
(@portal_module_id, 'SupplierPortal', 'Supplier Portal', '/portals/suppliers', 2, 0);

SET @supplier_portal_submodule_id = LAST_INSERT_ID();

-- Create permissions for Supplier Portal (for future use)
INSERT INTO permissions (submodule_id, name, display_name, action) VALUES
(@supplier_portal_submodule_id, 'PortalManagement.SupplierPortal', 'Supplier Portal Management', 'view'),
(@supplier_portal_submodule_id, 'PortalManagement.SupplierPortal', 'Supplier Portal Management', 'modify');

-- ============================================================
-- DRIVER PORTAL SUBMODULE (Placeholder - Inactive)
-- ============================================================

INSERT INTO submodules (module_id, name, display_name, route, sort_order, is_active) VALUES
(@portal_module_id, 'DriverPortal', 'Driver Portal', '/portals/drivers', 3, 0);

SET @driver_portal_submodule_id = LAST_INSERT_ID();

-- Create permissions for Driver Portal (for future use)
INSERT INTO permissions (submodule_id, name, display_name, action) VALUES
(@driver_portal_submodule_id, 'PortalManagement.DriverPortal', 'Driver Portal Management', 'view'),
(@driver_portal_submodule_id, 'PortalManagement.DriverPortal', 'Driver Portal Management', 'modify');

-- ============================================================
-- ALUMNI PORTAL SUBMODULE (Placeholder - Inactive)
-- ============================================================

INSERT INTO submodules (module_id, name, display_name, route, sort_order, is_active) VALUES
(@portal_module_id, 'AlumniPortal', 'Alumni Portal', '/portals/alumni', 4, 0);

SET @alumni_portal_submodule_id = LAST_INSERT_ID();

-- Create permissions for Alumni Portal (for future use)
INSERT INTO permissions (submodule_id, name, display_name, action) VALUES
(@alumni_portal_submodule_id, 'PortalManagement.AlumniPortal', 'Alumni Portal Management', 'view'),
(@alumni_portal_submodule_id, 'PortalManagement.AlumniPortal', 'Alumni Portal Management', 'modify');

-- ============================================================
-- ASSIGN PERMISSIONS TO ADMIN ROLE
-- ============================================================

-- Grant all Portal Management permissions to ADMIN (role_id = 1)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, p.id
FROM permissions p
JOIN submodules s ON p.submodule_id = s.id
JOIN modules m ON s.module_id = m.id
WHERE m.name = 'PortalManagement'
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Grant Parent Portal view permission to HEAD_TEACHER (role_id = 2)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, p.id
FROM permissions p
WHERE p.name = 'PortalManagement.ParentPortal' AND p.action = 'view'
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Grant Parent Portal view permission to BURSAR (role_id = 3)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, p.id
FROM permissions p
WHERE p.name = 'PortalManagement.ParentPortal' AND p.action = 'view'
ON DUPLICATE KEY UPDATE role_id = role_id;

-- ============================================================
-- VERIFICATION QUERIES
-- ============================================================

-- SELECT m.name as module, s.name as submodule, p.name as permission, p.action
-- FROM modules m
-- JOIN submodules s ON m.id = s.module_id
-- JOIN permissions p ON s.id = p.submodule_id
-- WHERE m.name = 'PortalManagement'
-- ORDER BY s.sort_order, p.action;

-- SELECT r.name as role, p.name as permission, p.action
-- FROM role_permissions rp
-- JOIN roles r ON rp.role_id = r.id
-- JOIN permissions p ON rp.permission_id = p.id
-- WHERE p.name LIKE 'PortalManagement.%'
-- ORDER BY r.id, p.name;
