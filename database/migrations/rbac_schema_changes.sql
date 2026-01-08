-- RBAC Schema Changes Migration
-- Simplified RBAC: One user = One role, Two permissions (view, modify)
-- Run Date: 2026-01-05

-- ============================================================
-- PHASE 1: Add role_id to users table
-- ============================================================

-- Add role_id column to users
ALTER TABLE users
ADD COLUMN role_id INT UNSIGNED NULL AFTER status,
ADD INDEX idx_users_role (role_id);

-- Migrate existing user_roles data to users.role_id (take first role if multiple)
UPDATE users u
SET u.role_id = (
    SELECT ur.role_id FROM user_roles ur WHERE ur.user_id = u.id LIMIT 1
)
WHERE u.role_id IS NULL;

-- Add foreign key constraint
ALTER TABLE users
ADD CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL;

-- ============================================================
-- PHASE 2: Update permissions table action ENUM
-- ============================================================

-- Change action ENUM to only view and modify
ALTER TABLE permissions
MODIFY COLUMN action ENUM('view', 'modify') NOT NULL;

-- ============================================================
-- PHASE 3: Clear and regenerate permissions from submodules
-- ============================================================

-- Clear existing permissions
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE role_permissions;
TRUNCATE TABLE permissions;
SET FOREIGN_KEY_CHECKS = 1;

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

-- ============================================================
-- PHASE 4: Add user_id to staff table (link staff to user account)
-- ============================================================

-- Check if column exists before adding
SET @column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'staff'
    AND COLUMN_NAME = 'user_id'
);

-- Add user_id column if it doesn't exist (run manually if needed)
-- ALTER TABLE staff
-- ADD COLUMN user_id BIGINT UNSIGNED NULL AFTER id,
-- ADD INDEX idx_staff_user (user_id),
-- ADD CONSTRAINT fk_staff_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

-- ============================================================
-- PHASE 5: Drop user_roles table (after migration verified)
-- ============================================================

-- Only drop after verifying data migration was successful
-- DROP TABLE IF EXISTS user_roles;

-- ============================================================
-- VERIFICATION QUERIES
-- ============================================================

-- Verify users have roles assigned
-- SELECT id, username, full_name, role_id FROM users;

-- Verify permissions were generated
-- SELECT COUNT(*) as total_permissions FROM permissions;
-- SELECT p.name, p.display_name, p.action, s.name as submodule
-- FROM permissions p
-- JOIN submodules s ON p.submodule_id = s.id
-- ORDER BY s.name, p.action;
