<?php
/**
 * Users Controller
 * Manages user accounts and role assignments
 */

class UsersController
{
    /**
     * List all users (Users & Roles Management - Users Tab)
     * Also handles Roles tab when ?tab=roles
     */
    public function index()
    {
        Gate::authorize('Settings.UsersManagement.view');

        $pdo = Database::getTenantConnection();
        $activeTab = Request::get('tab', 'users');

        // If roles tab, get roles data
        if ($activeTab === 'roles') {
            // Get roles with user count
            $stmt = $pdo->query("
                SELECT r.*,
                       COUNT(DISTINCT ur.user_id) as user_count,
                       (SELECT COUNT(*) FROM role_permissions WHERE role_id = r.id) as permission_count
                FROM roles r
                LEFT JOIN user_roles ur ON ur.role_id = r.id
                GROUP BY r.id
                ORDER BY r.is_system DESC, r.display_name ASC
            ");
            $roles = $stmt->fetchAll();

            Response::view('settings.users', [
                'roles' => $roles,
                'users' => [],
                'filters' => [],
                'activeTab' => 'roles'
            ]);
            return;
        }

        // Users tab (default)
        // Get filters
        $search = Request::get('search', '');
        $roleFilter = Request::get('role', '');
        $statusFilter = Request::get('status', '');

        // Build query
        $where = [];
        $params = [];

        if ($search) {
            $where[] = "(u.username LIKE :search OR u.full_name LIKE :search2 OR u.email LIKE :search3)";
            $params['search'] = "%{$search}%";
            $params['search2'] = "%{$search}%";
            $params['search3'] = "%{$search}%";
        }

        if ($roleFilter) {
            $where[] = "ur.role_id = :role_id";
            $params['role_id'] = $roleFilter;
        }

        if ($statusFilter) {
            $where[] = "u.status = :status";
            $params['status'] = $statusFilter;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Get users with roles (using GROUP_CONCAT for multiple roles)
        $stmt = $pdo->prepare("
            SELECT u.*,
                   GROUP_CONCAT(DISTINCT r.name ORDER BY r.name) as role_name,
                   GROUP_CONCAT(DISTINCT r.display_name ORDER BY r.display_name SEPARATOR ', ') as role_display_name
            FROM users u
            LEFT JOIN user_roles ur ON ur.user_id = u.id
            LEFT JOIN roles r ON ur.role_id = r.id
            {$whereClause}
            GROUP BY u.id
            ORDER BY u.created_at DESC
        ");
        $stmt->execute($params);
        $users = $stmt->fetchAll();

        // Get all roles for filter dropdown
        $stmt = $pdo->query("SELECT * FROM roles ORDER BY display_name");
        $roles = $stmt->fetchAll();

        Response::view('settings.users', [
            'users' => $users,
            'roles' => $roles,
            'filters' => [
                'search' => $search,
                'role' => $roleFilter,
                'status' => $statusFilter
            ],
            'activeTab' => 'users'
        ]);
    }

    /**
     * Show create user form
     */
    public function create()
    {
        Gate::authorize('Settings.UsersManagement.modify');

        $pdo = Database::getTenantConnection();
        $stmt = $pdo->query("SELECT * FROM roles ORDER BY display_name");
        $roles = $stmt->fetchAll();

        Response::view('settings.user-form', [
            'user' => null,
            'roles' => $roles,
            'mode' => 'create'
        ]);
    }

    /**
     * Store new user
     */
    public function store()
    {
        Gate::authorize('Settings.UsersManagement.modify');

        $data = Request::only('username', 'email', 'full_name', 'phone', 'password', 'password_confirm', 'role_id', 'status');

        // Validate required fields
        $errors = [];
        if (empty($data['username'])) $errors[] = 'Username is required';
        if (empty($data['email'])) $errors[] = 'Email is required';
        if (empty($data['full_name'])) $errors[] = 'Full name is required';
        if (empty($data['password'])) $errors[] = 'Password is required';
        if ($data['password'] !== $data['password_confirm']) $errors[] = 'Passwords do not match';
        if (strlen($data['password']) < 8) $errors[] = 'Password must be at least 8 characters';

        if (!empty($errors)) {
            flash('error', implode('<br>', $errors));
            storeOldInput($data);
            Response::back();
        }

        try {
            $pdo = Database::getTenantConnection();

            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
            $stmt->execute(['username' => $data['username'], 'email' => $data['email']]);
            if ($stmt->fetch()) {
                flash('error', 'Username or email already exists');
                storeOldInput($data);
                Response::back();
            }

            $pdo->beginTransaction();

            // Insert user
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, full_name, phone, password_hash, status, created_at)
                VALUES (:username, :email, :full_name, :phone, :password_hash, :status, NOW())
            ");

            $stmt->execute([
                'username' => $data['username'],
                'email' => $data['email'],
                'full_name' => $data['full_name'],
                'phone' => $data['phone'] ?: null,
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                'status' => $data['status'] ?: 'active'
            ]);

            $userId = $pdo->lastInsertId();

            // Assign role in user_roles table
            if (!empty($data['role_id'])) {
                $stmt = $pdo->prepare("
                    INSERT INTO user_roles (user_id, role_id, created_at)
                    VALUES (:user_id, :role_id, NOW())
                ");
                $stmt->execute([
                    'user_id' => $userId,
                    'role_id' => $data['role_id']
                ]);
            }

            $pdo->commit();

            flash('success', 'User created successfully');
            Response::redirect('/settings/users');

        } catch (Exception $e) {
            $pdo->rollBack();
            logMessage("Error creating user: " . $e->getMessage(), 'error');
            flash('error', 'Failed to create user. Please try again.');
            storeOldInput($data);
            Response::back();
        }
    }

    /**
     * Show edit user form
     */
    public function edit($id)
    {
        Gate::authorize('Settings.UsersManagement.modify');

        $pdo = Database::getTenantConnection();

        // Get user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();

        if (!$user) {
            flash('error', 'User not found');
            Response::redirect('/settings/users');
        }

        // Get user's current role (first one if multiple)
        $stmt = $pdo->prepare("
            SELECT role_id FROM user_roles
            WHERE user_id = :user_id
            LIMIT 1
        ");
        $stmt->execute(['user_id' => $id]);
        $userRole = $stmt->fetch();
        $user['role_id'] = $userRole['role_id'] ?? null;

        // Get roles
        $stmt = $pdo->query("SELECT * FROM roles ORDER BY display_name");
        $roles = $stmt->fetchAll();

        Response::view('settings.user-form', [
            'user' => $user,
            'roles' => $roles,
            'mode' => 'edit'
        ]);
    }

    /**
     * Update user
     */
    public function update($id)
    {
        Gate::authorize('Settings.UsersManagement.modify');

        $data = Request::only('username', 'email', 'full_name', 'phone', 'password', 'password_confirm', 'role_id', 'status');

        // Validate required fields
        $errors = [];
        if (empty($data['username'])) $errors[] = 'Username is required';
        if (empty($data['email'])) $errors[] = 'Email is required';
        if (empty($data['full_name'])) $errors[] = 'Full name is required';
        if (!empty($data['password']) && $data['password'] !== $data['password_confirm']) {
            $errors[] = 'Passwords do not match';
        }
        if (!empty($data['password']) && strlen($data['password']) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }

        if (!empty($errors)) {
            flash('error', implode('<br>', $errors));
            storeOldInput($data);
            Response::back();
        }

        try {
            $pdo = Database::getTenantConnection();

            // Check if username or email exists for another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = :username OR email = :email) AND id != :id");
            $stmt->execute(['username' => $data['username'], 'email' => $data['email'], 'id' => $id]);
            if ($stmt->fetch()) {
                flash('error', 'Username or email already exists for another user');
                storeOldInput($data);
                Response::back();
            }

            $pdo->beginTransaction();

            // Build update query
            $updateFields = [
                'username = :username',
                'email = :email',
                'full_name = :full_name',
                'phone = :phone',
                'status = :status',
                'updated_at = NOW()'
            ];

            $params = [
                'username' => $data['username'],
                'email' => $data['email'],
                'full_name' => $data['full_name'],
                'phone' => $data['phone'] ?: null,
                'status' => $data['status'] ?: 'active',
                'id' => $id
            ];

            // Add password if provided
            if (!empty($data['password'])) {
                $updateFields[] = 'password_hash = :password_hash';
                $params['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            $stmt = $pdo->prepare("UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = :id");
            $stmt->execute($params);

            // Update role in user_roles table
            if (!empty($data['role_id'])) {
                // Delete existing roles
                $stmt = $pdo->prepare("DELETE FROM user_roles WHERE user_id = :user_id");
                $stmt->execute(['user_id' => $id]);

                // Insert new role
                $stmt = $pdo->prepare("
                    INSERT INTO user_roles (user_id, role_id, created_at)
                    VALUES (:user_id, :role_id, NOW())
                ");
                $stmt->execute([
                    'user_id' => $id,
                    'role_id' => $data['role_id']
                ]);
            }

            $pdo->commit();

            flash('success', 'User updated successfully');
            Response::redirect('/settings/users');

        } catch (Exception $e) {
            $pdo->rollBack();
            logMessage("Error updating user: " . $e->getMessage(), 'error');
            flash('error', 'Failed to update user. Please try again.');
            storeOldInput($data);
            Response::back();
        }
    }

    /**
     * Delete user
     */
    public function destroy($id)
    {
        Gate::authorize('Settings.UsersManagement.modify');

        try {
            $pdo = Database::getTenantConnection();

            // Prevent deleting own account
            if ($id == $_SESSION['user_id']) {
                if (Request::isAjax()) {
                    Response::json(['success' => false, 'message' => 'You cannot delete your own account']);
                }
                flash('error', 'You cannot delete your own account');
                Response::back();
            }

            // Delete user
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute(['id' => $id]);

            if (Request::isAjax()) {
                Response::json(['success' => true, 'message' => 'User deleted successfully']);
            }

            flash('success', 'User deleted successfully');
            Response::redirect('/settings/users');

        } catch (Exception $e) {
            logMessage("Error deleting user: " . $e->getMessage(), 'error');
            if (Request::isAjax()) {
                Response::json(['success' => false, 'message' => 'Failed to delete user']);
            }
            flash('error', 'Failed to delete user. Please try again.');
            Response::back();
        }
    }

    /**
     * Toggle user status (AJAX)
     */
    public function toggleStatus($id)
    {
        Gate::authorize('Settings.UsersManagement.modify');

        try {
            $pdo = Database::getTenantConnection();

            // Prevent toggling own status
            if ($id == $_SESSION['user_id']) {
                Response::json(['success' => false, 'message' => 'You cannot change your own status']);
            }

            // Get current status
            $stmt = $pdo->prepare("SELECT status FROM users WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $user = $stmt->fetch();

            if (!$user) {
                Response::json(['success' => false, 'message' => 'User not found']);
            }

            $newStatus = $user['status'] === 'active' ? 'inactive' : 'active';

            $stmt = $pdo->prepare("UPDATE users SET status = :status, updated_at = NOW() WHERE id = :id");
            $stmt->execute(['status' => $newStatus, 'id' => $id]);

            Response::json([
                'success' => true,
                'message' => 'User status updated',
                'newStatus' => $newStatus
            ]);

        } catch (Exception $e) {
            logMessage("Error toggling user status: " . $e->getMessage(), 'error');
            Response::json(['success' => false, 'message' => 'Failed to update status']);
        }
    }
}
