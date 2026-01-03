<?php
/**
 * Main Admin Authentication Controller
 * Handles login/logout for super admin users
 */

class AdminAuthController
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
                // Get router DB connection
                $pdo = Database::getRouterConnection();

                // Find user
                $stmt = $pdo->prepare("
                    SELECT * FROM main_admin_users
                    WHERE username = :username AND status = 'active'
                    LIMIT 1
                ");
                $stmt->execute(['username' => $username]);
                $user = $stmt->fetch();

                if (!$user) {
                    // Log failed attempt
                    logMessage("Failed admin login attempt for username: {$username}", 'warning');
                    flash('error', 'Invalid username or password.');
                    storeOldInput(Request::only('username'));
                    Response::back();
                }

                // Verify password
                if (!password_verify($password, $user['password_hash'])) {
                    logMessage("Failed admin login attempt (wrong password) for username: {$username}", 'warning');
                    flash('error', 'Invalid username or password.');
                    storeOldInput(Request::only('username'));
                    Response::back();
                }

                // Login successful - create session
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_user_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_full_name'] = $user['full_name'];
                $_SESSION['admin_email'] = $user['email'];

                // Update last login
                $stmt = $pdo->prepare("
                    UPDATE main_admin_users
                    SET last_login_at = NOW(), last_login_ip = :ip
                    WHERE id = :id
                ");
                $stmt->execute([
                    'ip' => Request::ip(),
                    'id' => $user['id']
                ]);

                // Clear old input
                clearOldInput();

                // Log successful login
                logMessage("Admin user '{$username}' logged in successfully from " . Request::ip(), 'info');

                // Redirect to dashboard
                Response::redirect('/dashboard');

            } catch (Exception $e) {
                logMessage("Admin login error: " . $e->getMessage(), 'error');
                flash('error', 'An error occurred. Please try again.');
                Response::back();
            }
        }

        // Show login form (will be handled by view)
        Response::view('admin.login');
    }

    public function logout()
    {
        $username = $_SESSION['admin_username'] ?? 'unknown';

        // Destroy admin session
        unset($_SESSION['admin_logged_in']);
        unset($_SESSION['admin_user_id']);
        unset($_SESSION['admin_username']);
        unset($_SESSION['admin_full_name']);
        unset($_SESSION['admin_email']);

        logMessage("Admin user '{$username}' logged out", 'info');

        flash('success', 'You have been logged out successfully.');
        Response::redirect('/login');
    }
}
