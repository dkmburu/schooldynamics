<?php
/**
 * Parent Notifications Controller
 * Handles parent portal notifications display and management
 */

class ParentNotificationsController
{
    /**
     * List all notifications for the logged-in parent
     */
    public function index()
    {
        $this->requireParentAuth();

        $pdo = Database::getTenantConnection();
        $parentId = $_SESSION['parent_id'];

        // Get all notifications ordered by most recent
        $stmt = $pdo->prepare("
            SELECT
                pn.*,
                nt.name as type_name,
                nt.icon as type_icon,
                ns.name as scope_name,
                nsl.name as severity_name,
                nsl.color as severity_color,
                nat.name as action_type_name,
                s.first_name as student_first_name,
                s.last_name as student_last_name,
                g.grade_name
            FROM parent_notifications pn
            JOIN notification_types nt ON pn.notification_type_id = nt.id
            JOIN notification_scopes ns ON pn.notification_scope_id = ns.id
            JOIN notification_severity_levels nsl ON pn.severity_level_id = nsl.id
            LEFT JOIN notification_action_types nat ON pn.action_type_id = nat.id
            LEFT JOIN students s ON pn.student_id = s.id
            LEFT JOIN grades g ON pn.grade_id = g.id
            WHERE pn.parent_account_id = :parent_id
            AND pn.dismissed_at IS NULL
            ORDER BY
                pn.read_at IS NULL DESC,
                nsl.sort_order DESC,
                pn.created_at DESC
            LIMIT 100
        ");
        $stmt->execute(['parent_id' => $parentId]);
        $notifications = $stmt->fetchAll();

        // Calculate counts
        $stmt = $pdo->prepare("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN read_at IS NULL THEN 1 ELSE 0 END) as unread,
                SUM(CASE WHEN requires_action = 1 AND action_completed_at IS NULL THEN 1 ELSE 0 END) as action_required
            FROM parent_notifications
            WHERE parent_account_id = :parent_id
            AND dismissed_at IS NULL
        ");
        $stmt->execute(['parent_id' => $parentId]);
        $counts = $stmt->fetch();

        Response::view('parent.notifications', [
            'currentPage' => 'notifications',
            'notifications' => $notifications,
            'counts' => $counts
        ]);
    }

    /**
     * View a single notification
     */
    public function show($notificationId)
    {
        $this->requireParentAuth();

        $pdo = Database::getTenantConnection();
        $parentId = $_SESSION['parent_id'];

        // Get notification details
        $stmt = $pdo->prepare("
            SELECT
                pn.*,
                nt.name as type_name,
                nt.icon as type_icon,
                ns.name as scope_name,
                nsl.name as severity_name,
                nsl.color as severity_color,
                nat.name as action_type_name,
                s.first_name as student_first_name,
                s.last_name as student_last_name,
                g.grade_name
            FROM parent_notifications pn
            JOIN notification_types nt ON pn.notification_type_id = nt.id
            JOIN notification_scopes ns ON pn.notification_scope_id = ns.id
            JOIN notification_severity_levels nsl ON pn.severity_level_id = nsl.id
            LEFT JOIN notification_action_types nat ON pn.action_type_id = nat.id
            LEFT JOIN students s ON pn.student_id = s.id
            LEFT JOIN grades g ON pn.grade_id = g.id
            WHERE pn.id = :id
            AND pn.parent_account_id = :parent_id
        ");
        $stmt->execute([
            'id' => $notificationId,
            'parent_id' => $parentId
        ]);
        $notification = $stmt->fetch();

        if (!$notification) {
            flash('error', 'Notification not found.');
            Response::redirect('/parent/notifications');
        }

        // Mark as read if not already
        if (empty($notification['read_at'])) {
            $stmt = $pdo->prepare("
                UPDATE parent_notifications
                SET read_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute(['id' => $notificationId]);
        }

        Response::view('parent.notification-detail', [
            'currentPage' => 'notifications',
            'notification' => $notification
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId)
    {
        $this->requireParentAuth();

        $pdo = Database::getTenantConnection();
        $parentId = $_SESSION['parent_id'];

        $stmt = $pdo->prepare("
            UPDATE parent_notifications
            SET read_at = NOW()
            WHERE id = :id
            AND parent_account_id = :parent_id
            AND read_at IS NULL
        ");
        $stmt->execute([
            'id' => $notificationId,
            'parent_id' => $parentId
        ]);

        if (Request::isAjax()) {
            Response::json(['success' => true]);
        } else {
            Response::redirect('/parent/notifications');
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $this->requireParentAuth();

        $pdo = Database::getTenantConnection();
        $parentId = $_SESSION['parent_id'];

        $stmt = $pdo->prepare("
            UPDATE parent_notifications
            SET read_at = NOW()
            WHERE parent_account_id = :parent_id
            AND read_at IS NULL
            AND dismissed_at IS NULL
        ");
        $stmt->execute(['parent_id' => $parentId]);

        flash('success', 'All notifications marked as read.');
        Response::redirect('/parent/notifications');
    }

    /**
     * Dismiss a notification
     */
    public function dismiss($notificationId)
    {
        $this->requireParentAuth();

        $pdo = Database::getTenantConnection();
        $parentId = $_SESSION['parent_id'];

        $stmt = $pdo->prepare("
            UPDATE parent_notifications
            SET dismissed_at = NOW()
            WHERE id = :id
            AND parent_account_id = :parent_id
        ");
        $stmt->execute([
            'id' => $notificationId,
            'parent_id' => $parentId
        ]);

        if (Request::isAjax()) {
            Response::json(['success' => true]);
        } else {
            flash('success', 'Notification dismissed.');
            Response::redirect('/parent/notifications');
        }
    }

    /**
     * Get unread notification count (for badge)
     * Called from middleware/dashboard to set session variable
     */
    public static function getUnreadCount($parentId)
    {
        try {
            $pdo = Database::getTenantConnection();

            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM parent_notifications
                WHERE parent_account_id = :parent_id
                AND read_at IS NULL
                AND dismissed_at IS NULL
            ");
            $stmt->execute(['parent_id' => $parentId]);

            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Require parent authentication
     */
    private function requireParentAuth()
    {
        if (!isset($_SESSION['parent_logged_in']) || $_SESSION['parent_logged_in'] !== true) {
            flash('error', 'Please login to access the parent portal.');
            Response::redirect('/parent/login');
        }
    }
}
