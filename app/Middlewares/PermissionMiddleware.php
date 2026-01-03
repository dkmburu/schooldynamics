<?php
/**
 * Permission Middleware
 * Checks if user has required permission to access a route
 */

class PermissionMiddleware implements Middleware
{
    private $requiredPermission;

    public function __construct($permission)
    {
        $this->requiredPermission = $permission;
    }

    public function handle($next)
    {
        if (!isAuthenticated()) {
            flash('error', 'Please login to access this page.');
            Response::redirect('/login');
        }

        if (!hasPermission($this->requiredPermission)) {
            http_response_code(403);
            echo "<h1>403 - Forbidden</h1>";
            echo "<p>You do not have permission to access this resource.</p>";
            echo "<p>Required permission: <strong>{$this->requiredPermission}</strong></p>";
            echo "<p><a href='/dashboard'>Back to Dashboard</a></p>";
            exit;
        }

        return $next();
    }
}

/**
 * Check Permission Gate
 * Quick helper to check permissions in controllers/views
 */
class Gate
{
    /**
     * Check if user has permission
     */
    public static function allows($permission)
    {
        return hasPermission($permission);
    }

    /**
     * Check if user has any of the permissions
     */
    public static function any(...$permissions)
    {
        $userPermissions = $_SESSION['user_permissions'] ?? [];

        foreach ($permissions as $permission) {
            if (in_array($permission, $userPermissions)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has all of the permissions
     */
    public static function all(...$permissions)
    {
        $userPermissions = $_SESSION['user_permissions'] ?? [];

        foreach ($permissions as $permission) {
            if (!in_array($permission, $userPermissions)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if user has a specific role
     */
    public static function hasRole($roleName)
    {
        $roles = $_SESSION['user_roles'] ?? [];

        foreach ($roles as $role) {
            if ($role['name'] === $roleName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Deny access with 403 response
     */
    public static function deny($message = 'Access denied')
    {
        http_response_code(403);
        echo "<h1>403 - Forbidden</h1>";
        echo "<p>{$message}</p>";
        echo "<p><a href='/dashboard'>Back to Dashboard</a></p>";
        exit;
    }
}
