<?php
/**
 * Tenant Dashboard Controller
 */

class DashboardController
{
    public function index()
    {
        // Check authentication
        if (!isAuthenticated()) {
            flash('error', 'Please login to access the dashboard.');
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $tenant = Database::getCurrentTenant();

            // Get statistics
            $stats = [];

            // Total students
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM students WHERE status = 'active' AND deleted_at IS NULL");
            $stats['total_students'] = $stmt->fetchColumn();

            // Total staff
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE status = 'active'");
            $stats['total_staff'] = $stmt->fetchColumn();

            // Total grades
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM grades WHERE is_active = 1");
            $stats['total_classes'] = $stmt->fetchColumn();

            // Current academic year
            $stmt = $pdo->query("SELECT year_name FROM academic_years WHERE is_current = 1 LIMIT 1");
            $stats['current_year'] = $stmt->fetchColumn() ?: 'Not Set';

            // Recent students (last 5)
            $stmt = $pdo->query("
                SELECT admission_number, first_name, last_name, admission_date
                FROM students
                WHERE deleted_at IS NULL
                ORDER BY created_at DESC
                LIMIT 5
            ");
            $stats['recent_students'] = $stmt->fetchAll();

            Response::view('tenant.dashboard', [
                'stats' => $stats,
                'user_name' => $_SESSION['full_name'],
                'tenant_name' => $tenant['school_name'] ?? 'School',
                'roles' => $_SESSION['user_roles'] ?? []
            ]);

        } catch (Exception $e) {
            logMessage("Dashboard error: " . $e->getMessage(), 'error');
            echo "Error loading dashboard: " . e($e->getMessage());
        }
    }
}
