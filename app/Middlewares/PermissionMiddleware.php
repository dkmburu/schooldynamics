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
 *
 * RBAC Design: One user = One role, Two permissions (view, modify)
 * Permission format: Submodule.Name.action (e.g., 'Students.Applications.view')
 */
class Gate
{
    /**
     * Check if user has permission
     * ADMIN role bypasses all permission checks
     */
    public static function allows($permission)
    {
        // ADMIN bypasses all checks
        if (self::isAdmin()) {
            return true;
        }
        return in_array($permission, $_SESSION['user_permissions'] ?? []);
    }

    /**
     * Check if user can view a submodule
     * @param string $submodule e.g., 'Students.Applications' or 'Finance.Dashboard'
     */
    public static function canView($submodule)
    {
        return self::allows($submodule . '.view');
    }

    /**
     * Check if user can modify a submodule (create/edit/delete)
     * @param string $submodule e.g., 'Students.Applications' or 'Finance.Dashboard'
     */
    public static function canModify($submodule)
    {
        return self::allows($submodule . '.modify');
    }

    /**
     * Check if user has any of the permissions
     */
    public static function any(...$permissions)
    {
        if (self::isAdmin()) {
            return true;
        }

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
        if (self::isAdmin()) {
            return true;
        }

        $userPermissions = $_SESSION['user_permissions'] ?? [];

        foreach ($permissions as $permission) {
            if (!in_array($permission, $userPermissions)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if user has a specific role (single role system)
     */
    public static function hasRole($roleName)
    {
        $role = $_SESSION['user_role'] ?? null;
        return $role && $role['name'] === $roleName;
    }

    /**
     * Get user's current role
     */
    public static function getRole()
    {
        return $_SESSION['user_role'] ?? null;
    }

    /**
     * Get user's role name
     */
    public static function getRoleName()
    {
        $role = self::getRole();
        return $role ? $role['name'] : null;
    }

    /**
     * Check if user is admin
     */
    public static function isAdmin()
    {
        return self::hasRole('ADMIN');
    }

    /**
     * Check if user can access any submodule within a module
     * @param string $moduleName e.g., 'Finance', 'Students'
     */
    public static function canAccessModule($moduleName)
    {
        if (self::isAdmin()) {
            return true;
        }

        $userPermissions = $_SESSION['user_permissions'] ?? [];
        foreach ($userPermissions as $permission) {
            // Permission format: Module.Submodule.action
            if (strpos($permission, $moduleName . '.') === 0) {
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

    /**
     * Authorize or deny - shorthand for controller checks
     */
    public static function authorize($permission, $message = null)
    {
        if (!self::allows($permission)) {
            self::deny($message ?? "You do not have permission: {$permission}");
        }
    }
}
