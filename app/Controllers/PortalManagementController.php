<?php
/**
 * Portal Management Controller
 * Admin interface for managing external user portals (Parents, Suppliers, Drivers, etc.)
 */

class PortalManagementController
{
    // ========================================================================
    // PARENT PORTAL MANAGEMENT
    // ========================================================================

    /**
     * List all parent accounts
     */
    public function parentAccounts()
    {
        Gate::authorize('PortalManagement.ParentPortal.view');

        $pdo = Database::getTenantConnection();

        // Get filters
        $search = Request::get('search', '');
        $status = Request::get('status', '');
        $classId = Request::get('class_id', '');
        $page = max(1, (int)Request::get('page', 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        // Build query
        $where = ["1=1"];
        $params = [];

        if (!empty($search)) {
            $where[] = "(g.first_name LIKE :search OR g.last_name LIKE :search OR pa.email LIKE :search OR g.phone LIKE :search)";
            $params['search'] = "%{$search}%";
        }

        if (!empty($status)) {
            $where[] = "pa.status = :status";
            $params['status'] = $status;
        }

        $whereClause = implode(' AND ', $where);

        // Get total count
        $countSql = "
            SELECT COUNT(DISTINCT pa.id)
            FROM parent_accounts pa
            JOIN guardians g ON pa.guardian_id = g.id
            WHERE {$whereClause}
        ";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $totalCount = $stmt->fetchColumn();

        // Get accounts
        $sql = "
            SELECT pa.*,
                   g.first_name, g.last_name, g.phone as guardian_phone,
                   CONCAT(g.first_name, ' ', g.last_name) as guardian_name,
                   (SELECT COUNT(*) FROM student_guardians sg WHERE sg.guardian_id = g.id) as children_count,
                   (SELECT sg2.relationship FROM student_guardians sg2 WHERE sg2.guardian_id = g.id LIMIT 1) as relationship
            FROM parent_accounts pa
            JOIN guardians g ON pa.guardian_id = g.id
            WHERE {$whereClause}
            ORDER BY pa.created_at DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $accounts = $stmt->fetchAll();

        // Get stats
        $stats = $this->getParentPortalStats($pdo);

        // Get grades for filter
        $grades = $pdo->query("SELECT id, grade_name as name FROM grades WHERE is_active = 1 ORDER BY sort_order, grade_name")->fetchAll();

        Response::view('portals.parents.index', [
            'accounts' => $accounts,
            'stats' => $stats,
            'classes' => $grades,
            'filters' => ['search' => $search, 'status' => $status, 'class_id' => $classId],
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $totalCount,
                'total_pages' => ceil($totalCount / $perPage)
            ],
            'activeTab' => 'accounts'
        ]);
    }

    /**
     * List pending registrations
     */
    public function parentPending()
    {
        Gate::authorize('PortalManagement.ParentPortal.view');

        $pdo = Database::getTenantConnection();

        $stmt = $pdo->query("
            SELECT pa.*,
                   g.first_name, g.last_name, g.phone as guardian_phone,
                   CONCAT(g.first_name, ' ', g.last_name) as guardian_name,
                   (SELECT sg2.relationship FROM student_guardians sg2 WHERE sg2.guardian_id = g.id LIMIT 1) as relationship,
                   (SELECT GROUP_CONCAT(CONCAT(s.first_name, ' ', s.last_name) SEPARATOR ', ')
                    FROM students s
                    JOIN student_guardians sg ON s.id = sg.student_id
                    WHERE sg.guardian_id = g.id) as children_names
            FROM parent_accounts pa
            JOIN guardians g ON pa.guardian_id = g.id
            WHERE pa.status = 'pending'
            ORDER BY pa.created_at ASC
        ");
        $pendingAccounts = $stmt->fetchAll();

        $stats = $this->getParentPortalStats($pdo);

        Response::view('portals.parents.pending', [
            'accounts' => $pendingAccounts,
            'stats' => $stats,
            'activeTab' => 'pending'
        ]);
    }

    /**
     * View single parent account
     */
    public function viewParent($id)
    {
        Gate::authorize('PortalManagement.ParentPortal.view');

        $pdo = Database::getTenantConnection();

        // Get account with guardian info
        $stmt = $pdo->prepare("
            SELECT pa.*,
                   g.first_name, g.last_name, g.phone as guardian_phone,
                   g.email as guardian_email, g.occupation, g.id_number,
                   g.address,
                   CONCAT(g.first_name, ' ', g.last_name) as guardian_name,
                   (SELECT sg2.relationship FROM student_guardians sg2 WHERE sg2.guardian_id = g.id LIMIT 1) as relationship
            FROM parent_accounts pa
            JOIN guardians g ON pa.guardian_id = g.id
            WHERE pa.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $account = $stmt->fetch();

        if (!$account) {
            flash('error', 'Parent account not found.');
            Response::redirect('/portals/parents');
        }

        // Get linked children
        $stmt = $pdo->prepare("
            SELECT s.*, g.grade_name as class_name, str.stream_name
            FROM students s
            JOIN student_guardians sg ON s.id = sg.student_id
            LEFT JOIN student_enrollments se ON s.id = se.student_id AND se.is_current = 1
            LEFT JOIN streams str ON se.stream_id = str.id
            LEFT JOIN grades g ON str.grade_id = g.id
            WHERE sg.guardian_id = :guardian_id
        ");
        $stmt->execute(['guardian_id' => $account['guardian_id']]);
        $children = $stmt->fetchAll();

        // Get recent notifications sent to this parent
        $stmt = $pdo->prepare("
            SELECT * FROM parent_notifications
            WHERE parent_account_id = :id
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $stmt->execute(['id' => $id]);
        $notifications = $stmt->fetchAll();

        // Get login history (from sessions if available)
        $loginHistory = [];

        Response::view('portals.parents.view', [
            'account' => $account,
            'children' => $children,
            'notifications' => $notifications,
            'loginHistory' => $loginHistory
        ]);
    }

    /**
     * Approve pending registration
     */
    public function approveParent($id)
    {
        Gate::authorize('PortalManagement.ParentPortal.modify');

        try {
            $pdo = Database::getTenantConnection();

            $stmt = $pdo->prepare("
                UPDATE parent_accounts
                SET status = 'active', email_verified_at = NOW(), updated_at = NOW()
                WHERE id = :id AND status = 'pending'
            ");
            $stmt->execute(['id' => $id]);

            if ($stmt->rowCount() > 0) {
                // TODO: Send approval email to parent
                flash('success', 'Parent account approved successfully.');
            } else {
                flash('error', 'Account not found or already processed.');
            }

        } catch (Exception $e) {
            logMessage("Error approving parent: " . $e->getMessage(), 'error');
            flash('error', 'Failed to approve account.');
        }

        Response::redirect('/portals/parents/pending');
    }

    /**
     * Reject pending registration
     */
    public function rejectParent($id)
    {
        Gate::authorize('PortalManagement.ParentPortal.modify');

        $reason = Request::get('reason', '');

        try {
            $pdo = Database::getTenantConnection();

            // Delete the pending account
            $stmt = $pdo->prepare("DELETE FROM parent_accounts WHERE id = :id AND status = 'pending'");
            $stmt->execute(['id' => $id]);

            if ($stmt->rowCount() > 0) {
                // TODO: Send rejection email with reason
                flash('success', 'Registration rejected.');
            } else {
                flash('error', 'Account not found or already processed.');
            }

        } catch (Exception $e) {
            logMessage("Error rejecting parent: " . $e->getMessage(), 'error');
            flash('error', 'Failed to reject registration.');
        }

        Response::redirect('/portals/parents/pending');
    }

    /**
     * Suspend active account
     */
    public function suspendParent($id)
    {
        Gate::authorize('PortalManagement.ParentPortal.modify');

        try {
            $pdo = Database::getTenantConnection();

            $stmt = $pdo->prepare("
                UPDATE parent_accounts
                SET status = 'suspended', updated_at = NOW()
                WHERE id = :id AND status = 'active'
            ");
            $stmt->execute(['id' => $id]);

            if ($stmt->rowCount() > 0) {
                flash('success', 'Parent account suspended.');
            } else {
                flash('error', 'Account not found or cannot be suspended.');
            }

        } catch (Exception $e) {
            logMessage("Error suspending parent: " . $e->getMessage(), 'error');
            flash('error', 'Failed to suspend account.');
        }

        Response::back();
    }

    /**
     * Activate suspended account
     */
    public function activateParent($id)
    {
        Gate::authorize('PortalManagement.ParentPortal.modify');

        try {
            $pdo = Database::getTenantConnection();

            $stmt = $pdo->prepare("
                UPDATE parent_accounts
                SET status = 'active', updated_at = NOW()
                WHERE id = :id AND status = 'suspended'
            ");
            $stmt->execute(['id' => $id]);

            if ($stmt->rowCount() > 0) {
                flash('success', 'Parent account activated.');
            } else {
                flash('error', 'Account not found or cannot be activated.');
            }

        } catch (Exception $e) {
            logMessage("Error activating parent: " . $e->getMessage(), 'error');
            flash('error', 'Failed to activate account.');
        }

        Response::back();
    }

    /**
     * Reset parent password
     */
    public function resetParentPassword($id)
    {
        Gate::authorize('PortalManagement.ParentPortal.modify');

        try {
            $pdo = Database::getTenantConnection();

            // Generate temporary password
            $tempPassword = bin2hex(random_bytes(4)); // 8 character password
            $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                UPDATE parent_accounts
                SET password_hash = :password, updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute(['password' => $hashedPassword, 'id' => $id]);

            if ($stmt->rowCount() > 0) {
                // Get parent email for display
                $stmt = $pdo->prepare("SELECT email FROM parent_accounts WHERE id = :id");
                $stmt->execute(['id' => $id]);
                $account = $stmt->fetch();

                // TODO: Send email with new password
                flash('success', "Password reset. Temporary password: <strong>{$tempPassword}</strong><br>Please share this with the parent securely.");
            } else {
                flash('error', 'Account not found.');
            }

        } catch (Exception $e) {
            logMessage("Error resetting parent password: " . $e->getMessage(), 'error');
            flash('error', 'Failed to reset password.');
        }

        Response::back();
    }

    /**
     * Delete parent account
     */
    public function deleteParent($id)
    {
        Gate::authorize('PortalManagement.ParentPortal.modify');

        try {
            $pdo = Database::getTenantConnection();

            // Delete notifications first
            $stmt = $pdo->prepare("DELETE FROM parent_notifications WHERE parent_account_id = :id");
            $stmt->execute(['id' => $id]);

            // Delete sessions
            $stmt = $pdo->prepare("DELETE FROM parent_sessions WHERE parent_account_id = :id");
            $stmt->execute(['id' => $id]);

            // Delete account
            $stmt = $pdo->prepare("DELETE FROM parent_accounts WHERE id = :id");
            $stmt->execute(['id' => $id]);

            if (Request::isAjax()) {
                Response::json(['success' => true, 'message' => 'Account deleted successfully.']);
            }

            flash('success', 'Parent account deleted successfully.');

        } catch (Exception $e) {
            logMessage("Error deleting parent: " . $e->getMessage(), 'error');
            if (Request::isAjax()) {
                Response::json(['success' => false, 'message' => 'Failed to delete account.']);
            }
            flash('error', 'Failed to delete account.');
        }

        Response::redirect('/portals/parents');
    }

    /**
     * Approve all pending registrations
     */
    public function approveAllPending()
    {
        Gate::authorize('PortalManagement.ParentPortal.modify');

        try {
            $pdo = Database::getTenantConnection();

            // Get all pending accounts
            $stmt = $pdo->query("SELECT id FROM parent_accounts WHERE status = 'pending'");
            $pendingAccounts = $stmt->fetchAll();

            $approvedCount = 0;
            foreach ($pendingAccounts as $account) {
                $stmt = $pdo->prepare("
                    UPDATE parent_accounts
                    SET status = 'active',
                        email_verified_at = COALESCE(email_verified_at, NOW())
                    WHERE id = :id
                ");
                $stmt->execute(['id' => $account['id']]);
                $approvedCount++;
            }

            flash('success', "Successfully approved {$approvedCount} parent account(s).");

        } catch (Exception $e) {
            logMessage("Error in bulk approval: " . $e->getMessage(), 'error');
            flash('error', 'Failed to approve accounts. Please try again.');
        }

        Response::redirect('/portals/parents/pending');
    }

    /**
     * Show notification form
     */
    public function parentNotifications()
    {
        Gate::authorize('PortalManagement.ParentPortal.modify');

        $pdo = Database::getTenantConnection();

        // Get grades for targeting
        $classes = $pdo->query("SELECT id, grade_name as name FROM grades WHERE is_active = 1 ORDER BY sort_order, grade_name")->fetchAll();

        // Get recent notifications
        $stmt = $pdo->query("
            SELECT pn.*, pa.email, g.first_name, g.last_name
            FROM parent_notifications pn
            JOIN parent_accounts pa ON pn.parent_account_id = pa.id
            JOIN guardians g ON pa.guardian_id = g.id
            ORDER BY pn.created_at DESC
            LIMIT 20
        ");
        $recentNotifications = $stmt->fetchAll();

        // Get parent count
        $parentCount = $pdo->query("SELECT COUNT(*) FROM parent_accounts WHERE status = 'active'")->fetchColumn();

        $stats = $this->getParentPortalStats($pdo);

        Response::view('portals.parents.notifications', [
            'classes' => $classes,
            'recentNotifications' => $recentNotifications,
            'parentCount' => $parentCount,
            'stats' => $stats,
            'activeTab' => 'notifications'
        ]);
    }

    /**
     * Send notification to parents
     */
    public function sendParentNotification()
    {
        Gate::authorize('PortalManagement.ParentPortal.modify');

        $target = Request::get('target'); // 'all', 'class', 'balance', 'specific'
        $classId = Request::get('class_id');
        $type = Request::get('type', 'announcement');
        $title = Request::get('title');
        $message = Request::get('message');

        if (empty($title) || empty($message)) {
            flash('error', 'Title and message are required.');
            Response::back();
        }

        try {
            $pdo = Database::getTenantConnection();

            // Build query to get target parents
            switch ($target) {
                case 'class':
                    $sql = "
                        SELECT DISTINCT pa.id
                        FROM parent_accounts pa
                        JOIN guardians g ON pa.guardian_id = g.id
                        JOIN student_guardians sg ON g.id = sg.guardian_id
                        JOIN students s ON sg.student_id = s.id
                        JOIN student_enrollments se ON s.id = se.student_id AND se.is_current = 1
                        JOIN streams str ON se.stream_id = str.id
                        WHERE pa.status = 'active' AND str.grade_id = :class_id
                    ";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(['class_id' => $classId]);
                    break;

                case 'balance':
                    $sql = "
                        SELECT DISTINCT pa.id
                        FROM parent_accounts pa
                        JOIN guardians g ON pa.guardian_id = g.id
                        JOIN student_guardians sg ON g.id = sg.guardian_id
                        JOIN students s ON sg.student_id = s.id
                        JOIN invoices i ON s.id = i.student_id
                        WHERE pa.status = 'active'
                        AND i.status IN ('unpaid', 'partial')
                        GROUP BY pa.id
                        HAVING SUM(i.total_amount - i.amount_paid) > 0
                    ";
                    $stmt = $pdo->query($sql);
                    break;

                default: // 'all'
                    $sql = "SELECT id FROM parent_accounts WHERE status = 'active'";
                    $stmt = $pdo->query($sql);
            }

            $parentIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($parentIds)) {
                flash('error', 'No parents found matching the criteria.');
                Response::back();
            }

            // Insert notifications
            $insertStmt = $pdo->prepare("
                INSERT INTO parent_notifications (parent_account_id, type, title, message, created_at)
                VALUES (:parent_id, :type, :title, :message, NOW())
            ");

            $count = 0;
            foreach ($parentIds as $parentId) {
                $insertStmt->execute([
                    'parent_id' => $parentId,
                    'type' => $type,
                    'title' => $title,
                    'message' => $message
                ]);
                $count++;
            }

            flash('success', "Notification sent to {$count} parent(s) successfully.");

        } catch (Exception $e) {
            logMessage("Error sending notification: " . $e->getMessage(), 'error');
            flash('error', 'Failed to send notification.');
        }

        Response::redirect('/portals/parents/notifications');
    }

    /**
     * Show portal settings
     */
    public function parentSettings()
    {
        Gate::authorize('PortalManagement.ParentPortal.modify');

        $pdo = Database::getTenantConnection();

        $stmt = $pdo->query("SELECT * FROM parent_portal_settings");
        $settingsRaw = $stmt->fetchAll();

        $settings = [];
        foreach ($settingsRaw as $setting) {
            $value = $setting['setting_value'];
            if ($setting['setting_type'] === 'boolean') {
                $value = $value === 'true' || $value === '1';
            } elseif ($setting['setting_type'] === 'integer') {
                $value = (int)$value;
            }
            $settings[$setting['setting_key']] = [
                'value' => $value,
                'type' => $setting['setting_type'],
                'description' => $setting['description']
            ];
        }

        $stats = $this->getParentPortalStats($pdo);

        Response::view('portals.parents.settings', [
            'settings' => $settings,
            'stats' => $stats,
            'activeTab' => 'settings'
        ]);
    }

    /**
     * Update portal settings
     */
    public function updateParentSettings()
    {
        Gate::authorize('PortalManagement.ParentPortal.modify');

        try {
            $pdo = Database::getTenantConnection();

            $settingKeys = [
                'portal_enabled', 'self_registration', 'require_email_verification',
                'show_grades', 'show_attendance', 'show_fees', 'show_timetable',
                'allow_online_payment', 'session_timeout_minutes'
            ];

            $stmt = $pdo->prepare("
                UPDATE parent_portal_settings
                SET setting_value = :value, updated_at = NOW()
                WHERE setting_key = :key
            ");

            foreach ($settingKeys as $key) {
                $value = Request::get($key);
                if ($value === null) {
                    $value = 'false'; // Checkbox not checked
                } elseif ($value === 'on' || $value === '1') {
                    $value = 'true';
                }

                $stmt->execute(['key' => $key, 'value' => $value]);
            }

            flash('success', 'Portal settings updated successfully.');

        } catch (Exception $e) {
            logMessage("Error updating portal settings: " . $e->getMessage(), 'error');
            flash('error', 'Failed to update settings.');
        }

        Response::redirect('/portals/parents/settings');
    }

    /**
     * Get portal statistics
     */
    private function getParentPortalStats($pdo)
    {
        $stats = [];

        // Total accounts
        $stats['total'] = $pdo->query("SELECT COUNT(*) FROM parent_accounts")->fetchColumn();

        // Active accounts
        $stats['active'] = $pdo->query("SELECT COUNT(*) FROM parent_accounts WHERE status = 'active'")->fetchColumn();

        // Pending accounts
        $stats['pending'] = $pdo->query("SELECT COUNT(*) FROM parent_accounts WHERE status = 'pending'")->fetchColumn();

        // Suspended accounts
        $stats['suspended'] = $pdo->query("SELECT COUNT(*) FROM parent_accounts WHERE status = 'suspended'")->fetchColumn();

        // Active this month
        $stats['active_this_month'] = $pdo->query("
            SELECT COUNT(*) FROM parent_accounts
            WHERE last_login_at >= DATE_FORMAT(NOW(), '%Y-%m-01')
        ")->fetchColumn();

        // New this month
        $stats['new_this_month'] = $pdo->query("
            SELECT COUNT(*) FROM parent_accounts
            WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')
        ")->fetchColumn();

        return $stats;
    }
}
