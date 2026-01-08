<?php
/**
 * Roles Controller
 * Manages roles and their permissions
 */

class RolesController
{
    /**
     * List all roles (Users & Roles Management - Roles Tab)
     */
    public function index()
    {
        Gate::authorize('Settings.UsersManagement.modify');

        $pdo = Database::getTenantConnection();

        // Get roles with user count
        $stmt = $pdo->query("
            SELECT r.*,
                   COUNT(u.id) as user_count,
                   (SELECT COUNT(*) FROM role_permissions WHERE role_id = r.id) as permission_count
            FROM roles r
            LEFT JOIN users u ON u.role_id = r.id
            GROUP BY r.id
            ORDER BY r.is_system DESC, r.display_name ASC
        ");
        $roles = $stmt->fetchAll();

        Response::view('settings.users', [
            'roles' => $roles,
            'users' => [],
            'activeTab' => 'roles'
        ]);
    }

    /**
     * Show create role form
     */
    public function create()
    {
        Gate::authorize('Settings.UsersManagement.modify');

        $pdo = Database::getTenantConnection();

        // Get modules and submodules with permissions
        $permissions = $this->getPermissionMatrix($pdo, null);

        Response::view('settings.role-form', [
            'role' => null,
            'permissions' => $permissions,
            'mode' => 'create'
        ]);
    }

    /**
     * Store new role
     */
    public function store()
    {
        Gate::authorize('Settings.UsersManagement.modify');

        $data = Request::only('name', 'display_name', 'description');
        $permissionIds = Request::get('permissions', []);

        // Validate
        $errors = [];
        if (empty($data['name'])) $errors[] = 'Role name is required';
        if (empty($data['display_name'])) $errors[] = 'Display name is required';

        // Format role name (uppercase, underscores)
        $data['name'] = strtoupper(str_replace(' ', '_', preg_replace('/[^a-zA-Z0-9_\s]/', '', $data['name'])));

        if (!empty($errors)) {
            flash('error', implode('<br>', $errors));
            storeOldInput($data);
            Response::back();
        }

        try {
            $pdo = Database::getTenantConnection();
            $pdo->beginTransaction();

            // Check if role name exists
            $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = :name");
            $stmt->execute(['name' => $data['name']]);
            if ($stmt->fetch()) {
                flash('error', 'A role with this name already exists');
                storeOldInput($data);
                Response::back();
            }

            // Create role
            $stmt = $pdo->prepare("
                INSERT INTO roles (name, display_name, description, is_system, created_at)
                VALUES (:name, :display_name, :description, 0, NOW())
            ");
            $stmt->execute([
                'name' => $data['name'],
                'display_name' => $data['display_name'],
                'description' => $data['description'] ?: null
            ]);

            $roleId = $pdo->lastInsertId();

            // Assign permissions
            if (!empty($permissionIds)) {
                $stmt = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)");
                foreach ($permissionIds as $permId) {
                    $stmt->execute(['role_id' => $roleId, 'permission_id' => $permId]);
                }
            }

            $pdo->commit();
            flash('success', 'Role created successfully');
            Response::redirect('/settings/users?tab=roles');

        } catch (Exception $e) {
            $pdo->rollBack();
            logMessage("Error creating role: " . $e->getMessage(), 'error');
            flash('error', 'Failed to create role. Please try again.');
            storeOldInput($data);
            Response::back();
        }
    }

    /**
     * Show edit role form
     */
    public function edit($id)
    {
        Gate::authorize('Settings.UsersManagement.modify');

        $pdo = Database::getTenantConnection();

        // Get role
        $stmt = $pdo->prepare("SELECT * FROM roles WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $role = $stmt->fetch();

        if (!$role) {
            flash('error', 'Role not found');
            Response::redirect('/settings/users?tab=roles');
        }

        // Get permissions matrix with current role's permissions checked
        $permissions = $this->getPermissionMatrix($pdo, $id);

        Response::view('settings.role-form', [
            'role' => $role,
            'permissions' => $permissions,
            'mode' => 'edit'
        ]);
    }

    /**
     * Update role
     */
    public function update($id)
    {
        Gate::authorize('Settings.UsersManagement.modify');

        $pdo = Database::getTenantConnection();

        // Get role
        $stmt = $pdo->prepare("SELECT * FROM roles WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $role = $stmt->fetch();

        if (!$role) {
            flash('error', 'Role not found');
            Response::redirect('/settings/users?tab=roles');
        }

        $data = Request::only('name', 'display_name', 'description');
        $permissionIds = Request::get('permissions', []);

        // System roles can only have permissions updated, not name/display
        if (!$role['is_system']) {
            // Validate
            if (empty($data['display_name'])) {
                flash('error', 'Display name is required');
                storeOldInput($data);
                Response::back();
            }

            // Format role name (uppercase, underscores) if provided
            if (!empty($data['name'])) {
                $data['name'] = strtoupper(str_replace(' ', '_', preg_replace('/[^a-zA-Z0-9_\s]/', '', $data['name'])));

                // Check if name already exists for another role
                $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = :name AND id != :id");
                $stmt->execute(['name' => $data['name'], 'id' => $id]);
                if ($stmt->fetch()) {
                    flash('error', 'A role with this name already exists');
                    storeOldInput($data);
                    Response::back();
                }
            }
        }

        try {
            $pdo->beginTransaction();

            // Update role (except for system roles - only permissions)
            if (!$role['is_system']) {
                $stmt = $pdo->prepare("
                    UPDATE roles
                    SET name = :name, display_name = :display_name, description = :description, updated_at = NOW()
                    WHERE id = :id
                ");
                $stmt->execute([
                    'name' => $data['name'] ?: $role['name'],
                    'display_name' => $data['display_name'],
                    'description' => $data['description'] ?: null,
                    'id' => $id
                ]);
            }

            // Update permissions (clear and re-add)
            $stmt = $pdo->prepare("DELETE FROM role_permissions WHERE role_id = :role_id");
            $stmt->execute(['role_id' => $id]);

            if (!empty($permissionIds)) {
                $stmt = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)");
                foreach ($permissionIds as $permId) {
                    $stmt->execute(['role_id' => $id, 'permission_id' => $permId]);
                }
            }

            $pdo->commit();
            flash('success', 'Role updated successfully');
            Response::redirect('/settings/users?tab=roles');

        } catch (Exception $e) {
            $pdo->rollBack();
            logMessage("Error updating role: " . $e->getMessage(), 'error');
            flash('error', 'Failed to update role. Please try again.');
            Response::back();
        }
    }

    /**
     * Delete role
     */
    public function destroy($id)
    {
        Gate::authorize('Settings.UsersManagement.modify');

        try {
            $pdo = Database::getTenantConnection();

            // Get role
            $stmt = $pdo->prepare("SELECT * FROM roles WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $role = $stmt->fetch();

            if (!$role) {
                if (Request::isAjax()) {
                    Response::json(['success' => false, 'message' => 'Role not found']);
                }
                flash('error', 'Role not found');
                Response::redirect('/settings/users?tab=roles');
            }

            // Prevent deleting system roles
            if ($role['is_system']) {
                if (Request::isAjax()) {
                    Response::json(['success' => false, 'message' => 'System roles cannot be deleted']);
                }
                flash('error', 'System roles cannot be deleted');
                Response::redirect('/settings/users?tab=roles');
            }

            // Check if role has users
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role_id = :role_id");
            $stmt->execute(['role_id' => $id]);
            $userCount = $stmt->fetchColumn();

            if ($userCount > 0) {
                if (Request::isAjax()) {
                    Response::json(['success' => false, 'message' => "Cannot delete role. {$userCount} user(s) are assigned to this role."]);
                }
                flash('error', "Cannot delete role. {$userCount} user(s) are assigned to this role.");
                Response::redirect('/settings/users?tab=roles');
            }

            // Delete role permissions first
            $stmt = $pdo->prepare("DELETE FROM role_permissions WHERE role_id = :role_id");
            $stmt->execute(['role_id' => $id]);

            // Delete role
            $stmt = $pdo->prepare("DELETE FROM roles WHERE id = :id");
            $stmt->execute(['id' => $id]);

            if (Request::isAjax()) {
                Response::json(['success' => true, 'message' => 'Role deleted successfully']);
            }

            flash('success', 'Role deleted successfully');
            Response::redirect('/settings/users?tab=roles');

        } catch (Exception $e) {
            logMessage("Error deleting role: " . $e->getMessage(), 'error');
            if (Request::isAjax()) {
                Response::json(['success' => false, 'message' => 'Failed to delete role']);
            }
            flash('error', 'Failed to delete role. Please try again.');
            Response::redirect('/settings/users?tab=roles');
        }
    }

    /**
     * Clone role
     */
    public function clone($id)
    {
        Gate::authorize('Settings.UsersManagement.modify');

        try {
            $pdo = Database::getTenantConnection();
            $pdo->beginTransaction();

            // Get source role
            $stmt = $pdo->prepare("SELECT * FROM roles WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $sourceRole = $stmt->fetch();

            if (!$sourceRole) {
                flash('error', 'Source role not found');
                Response::redirect('/settings/users?tab=roles');
            }

            // Create new role name
            $newName = $sourceRole['name'] . '_COPY';
            $newDisplayName = $sourceRole['display_name'] . ' (Copy)';

            // Ensure unique name
            $counter = 1;
            while (true) {
                $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = :name");
                $stmt->execute(['name' => $newName]);
                if (!$stmt->fetch()) break;
                $newName = $sourceRole['name'] . '_COPY_' . $counter++;
                $newDisplayName = $sourceRole['display_name'] . ' (Copy ' . $counter . ')';
            }

            // Create new role
            $stmt = $pdo->prepare("
                INSERT INTO roles (name, display_name, description, is_system, created_at)
                VALUES (:name, :display_name, :description, 0, NOW())
            ");
            $stmt->execute([
                'name' => $newName,
                'display_name' => $newDisplayName,
                'description' => $sourceRole['description']
            ]);

            $newRoleId = $pdo->lastInsertId();

            // Copy permissions
            $stmt = $pdo->prepare("
                INSERT INTO role_permissions (role_id, permission_id)
                SELECT :new_role_id, permission_id
                FROM role_permissions
                WHERE role_id = :source_role_id
            ");
            $stmt->execute([
                'new_role_id' => $newRoleId,
                'source_role_id' => $id
            ]);

            $pdo->commit();

            if (Request::isAjax()) {
                Response::json([
                    'success' => true,
                    'message' => 'Role cloned successfully',
                    'newRoleId' => $newRoleId
                ]);
            }

            flash('success', 'Role cloned successfully. You can now edit the new role.');
            Response::redirect("/settings/roles/{$newRoleId}/edit");

        } catch (Exception $e) {
            $pdo->rollBack();
            logMessage("Error cloning role: " . $e->getMessage(), 'error');
            if (Request::isAjax()) {
                Response::json(['success' => false, 'message' => 'Failed to clone role']);
            }
            flash('error', 'Failed to clone role. Please try again.');
            Response::redirect('/settings/users?tab=roles');
        }
    }

    /**
     * Get permission matrix grouped by module
     */
    private function getPermissionMatrix($pdo, $roleId = null)
    {
        // Get all permissions with module/submodule info
        $stmt = $pdo->query("
            SELECT p.id, p.name, p.display_name, p.action,
                   s.id as submodule_id, s.name as submodule_name, s.display_name as submodule_display,
                   m.id as module_id, m.name as module_name, m.display_name as module_display, m.icon as module_icon
            FROM permissions p
            INNER JOIN submodules s ON p.submodule_id = s.id
            INNER JOIN modules m ON s.module_id = m.id
            WHERE s.is_active = 1
            ORDER BY m.sort_order, s.sort_order, p.action
        ");
        $permissions = $stmt->fetchAll();

        // Get role's current permissions if editing
        $rolePermissions = [];
        if ($roleId) {
            $stmt = $pdo->prepare("SELECT permission_id FROM role_permissions WHERE role_id = :role_id");
            $stmt->execute(['role_id' => $roleId]);
            $rolePermissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        // Group by module -> submodule -> permissions
        $matrix = [];
        foreach ($permissions as $perm) {
            $moduleId = $perm['module_id'];
            $submoduleId = $perm['submodule_id'];

            if (!isset($matrix[$moduleId])) {
                $matrix[$moduleId] = [
                    'id' => $moduleId,
                    'name' => $perm['module_name'],
                    'display_name' => $perm['module_display'],
                    'icon' => $perm['module_icon'],
                    'submodules' => []
                ];
            }

            if (!isset($matrix[$moduleId]['submodules'][$submoduleId])) {
                $matrix[$moduleId]['submodules'][$submoduleId] = [
                    'id' => $submoduleId,
                    'name' => $perm['submodule_name'],
                    'display_name' => $perm['submodule_display'],
                    'permissions' => []
                ];
            }

            $matrix[$moduleId]['submodules'][$submoduleId]['permissions'][] = [
                'id' => $perm['id'],
                'name' => $perm['name'],
                'display_name' => $perm['display_name'],
                'action' => $perm['action'],
                'checked' => in_array($perm['id'], $rolePermissions)
            ];
        }

        return $matrix;
    }
}
