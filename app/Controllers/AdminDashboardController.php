<?php
/**
 * Main Admin Dashboard Controller
 */

class AdminDashboardController
{
    public function index()
    {
        // Check authentication
        if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
            flash('error', 'Please login to access the dashboard.');
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getRouterConnection();

            // Get statistics
            $stats = [];

            // Total tenants
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM tenants");
            $stats['total_tenants'] = $stmt->fetchColumn();

            // Active tenants
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM tenants WHERE status = 'active'");
            $stats['active_tenants'] = $stmt->fetchColumn();

            // Inactive/suspended tenants
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM tenants WHERE status != 'active'");
            $stats['inactive_tenants'] = $stmt->fetchColumn();

            // Recent tenants
            $stmt = $pdo->query("
                SELECT subdomain, school_name, status, created_at
                FROM tenants
                ORDER BY created_at DESC
                LIMIT 10
            ");
            $stats['recent_tenants'] = $stmt->fetchAll();

            Response::view('admin.dashboard', [
                'stats' => $stats,
                'admin_name' => $_SESSION['admin_full_name']
            ]);

        } catch (Exception $e) {
            logMessage("Admin dashboard error: " . $e->getMessage(), 'error');
            echo "Error loading dashboard: " . e($e->getMessage());
        }
    }
}
