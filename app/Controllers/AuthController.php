<?php
/**
 * Tenant Authentication Controller
 * Handles login/logout for tenant users (staff)
 */

class AuthController
{
    public function login()
    {
        if (Request::isPost()) {
            $username = Request::get('username');
            $password = Request::get('password');
            $csrfToken = Request::get(env('CSRF_TOKEN_NAME', '_csrf_token'));

            // Validate CSRF token
            if (!verifyCsrfToken($csrfToken)) {
                flash('error', 'Invalid security token. Please try again.');
                storeOldInput(Request::only('username'));
                Response::back();
            }

            // Validate input
            if (empty($username) || empty($password)) {
                flash('error', 'Please provide both username and password.');
                storeOldInput(Request::only('username'));
                Response::back();
            }

            try {
                // Get tenant DB connection
                $pdo = Database::getTenantConnection();
                $tenant = Database::getCurrentTenant();

                // Find user
                $stmt = $pdo->prepare("
                    SELECT u.*
                    FROM users u
                    WHERE u.username = :username
                    LIMIT 1
                ");
                $stmt->execute(['username' => $username]);
                $user = $stmt->fetch();

                if (!$user) {
                    logMessage("Failed login attempt for username: {$username} on tenant: {$tenant['subdomain']}", 'warning');
                    $this->handleFailedLogin($pdo, null, $username);
                    flash('error', 'Invalid username or password.');
                    storeOldInput(Request::only('username'));
                    Response::back();
                }

                // Check if account is locked
                if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                    $lockTime = formatDateTime($user['locked_until']);
                    flash('error', "Your account is temporarily locked until {$lockTime}. Please try again later.");
                    Response::back();
                }

                // Check if account is active
                if ($user['status'] !== 'active') {
                    flash('error', 'Your account is not active. Please contact the administrator.');
                    Response::back();
                }

                // Verify password
                if (!password_verify($password, $user['password_hash'])) {
                    logMessage("Failed login attempt (wrong password) for user: {$username}", 'warning');
                    $this->handleFailedLogin($pdo, $user['id'], $username);
                    flash('error', 'Invalid username or password.');
                    storeOldInput(Request::only('username'));
                    Response::back();
                }

                // Login successful - reset failed attempts
                $stmt = $pdo->prepare("
                    UPDATE users
                    SET failed_login_attempts = 0,
                        locked_until = NULL,
                        last_login_at = NOW(),
                        last_login_ip = :ip
                    WHERE id = :id
                ");
                $stmt->execute([
                    'ip' => Request::ip(),
                    'id' => $user['id']
                ]);

                // Get user roles and permissions
                $stmt = $pdo->prepare("
                    SELECT r.id, r.name, r.display_name
                    FROM roles r
                    INNER JOIN user_roles ur ON ur.role_id = r.id
                    WHERE ur.user_id = :user_id
                ");
                $stmt->execute(['user_id' => $user['id']]);
                $roles = $stmt->fetchAll();

                // Get all permissions for user's roles
                $roleIds = array_column($roles, 'id');
                if (!empty($roleIds)) {
                    $placeholders = implode(',', array_fill(0, count($roleIds), '?'));
                    $stmt = $pdo->prepare("
                        SELECT DISTINCT p.name, p.action, s.name as submodule, m.name as module
                        FROM permissions p
                        INNER JOIN role_permissions rp ON rp.permission_id = p.id
                        INNER JOIN submodules s ON s.id = p.submodule_id
                        INNER JOIN modules m ON m.id = s.module_id
                        WHERE rp.role_id IN ({$placeholders})
                    ");
                    $stmt->execute($roleIds);
                    $permissions = $stmt->fetchAll();

                    // Build permission array (e.g., 'students.view', 'finance.write')
                    $userPermissions = [];
                    foreach ($permissions as $perm) {
                        $userPermissions[] = $perm['module'] . '.' . $perm['submodule'] . '.' . $perm['action'];
                    }
                } else {
                    $userPermissions = [];
                }

                // Create session
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['user_roles'] = $roles;
                $_SESSION['user_permissions'] = $userPermissions;
                $_SESSION['tenant_id'] = $tenant['id'];
                $_SESSION['school_id'] = $tenant['id']; // For compatibility with finance/admission modules
                $_SESSION['tenant_subdomain'] = $tenant['subdomain'];
                $_SESSION['tenant_name'] = $tenant['school_name'];

                // Clear old input
                clearOldInput();

                // Log successful login (audit log)
                $this->logAudit($pdo, $user['id'], 'login', 'user', $user['id'], 'User logged in successfully');

                logMessage("User '{$username}' logged in successfully to tenant: {$tenant['subdomain']}", 'info');

                // Redirect to dashboard
                Response::redirect('/dashboard');

            } catch (Exception $e) {
                logMessage("Login error: " . $e->getMessage(), 'error');
                flash('error', 'An error occurred. Please try again.');
                Response::back();
            }
        }

        // Show login form
        Response::view('tenant.login');
    }

    public function logout()
    {
        $username = $_SESSION['username'] ?? 'unknown';
        $tenant = $_SESSION['tenant_subdomain'] ?? 'unknown';
        $userId = $_SESSION['user_id'] ?? null;

        // Log audit trail before destroying session
        if ($userId) {
            try {
                $pdo = Database::getTenantConnection();
                $this->logAudit($pdo, $userId, 'logout', 'user', $userId, 'User logged out');
            } catch (Exception $e) {
                logMessage("Error logging audit on logout: " . $e->getMessage(), 'error');
            }
        }

        // Destroy session
        $_SESSION = [];
        session_destroy();
        session_start(); // Restart for flash message

        logMessage("User '{$username}' logged out from tenant: {$tenant}", 'info');

        flash('success', 'You have been logged out successfully.');
        Response::redirect('/login');
    }

    public function forgotPassword()
    {
        // TODO: Implement password reset
        flash('info', 'Password reset functionality coming soon. Please contact your administrator.');
        Response::redirect('/login');
    }

    /**
     * Handle failed login attempts
     */
    private function handleFailedLogin($pdo, $userId, $username)
    {
        if (!$userId) {
            return;
        }

        $maxAttempts = env('FAILED_LOGIN_ATTEMPTS', 5);
        $lockoutDuration = env('LOCKOUT_DURATION', 900); // 15 minutes

        $stmt = $pdo->prepare("
            UPDATE users
            SET failed_login_attempts = failed_login_attempts + 1,
                locked_until = CASE
                    WHEN failed_login_attempts + 1 >= :max_attempts
                    THEN DATE_ADD(NOW(), INTERVAL :lockout_duration SECOND)
                    ELSE locked_until
                END
            WHERE id = :id
        ");

        $stmt->execute([
            'max_attempts' => $maxAttempts,
            'lockout_duration' => $lockoutDuration,
            'id' => $userId
        ]);

        // Check if account is now locked
        $stmt = $pdo->prepare("SELECT failed_login_attempts FROM users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        $attempts = $stmt->fetchColumn();

        if ($attempts >= $maxAttempts) {
            logMessage("User account '{$username}' locked due to {$attempts} failed login attempts", 'warning');
        }
    }

    /**
     * Log audit trail
     */
    private function logAudit($pdo, $userId, $action, $entityType, $entityId, $description)
    {
        if (!env('AUDIT_ENABLED', true)) {
            return;
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO audit_logs (user_id, action, entity_type, entity_id, url, method, ip_address, user_agent, result)
                VALUES (:user_id, :action, :entity_type, :entity_id, :url, :method, :ip, :user_agent, 'success')
            ");

            $stmt->execute([
                'user_id' => $userId,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'url' => $_SERVER['REQUEST_URI'] ?? '',
                'method' => Request::method(),
                'ip' => Request::ip(),
                'user_agent' => Request::userAgent()
            ]);
        } catch (Exception $e) {
            logMessage("Failed to log audit: " . $e->getMessage(), 'error');
        }
    }
}
