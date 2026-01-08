<?php
/**
 * Parent Portal Dashboard Controller
 * Handles parent dashboard and child-related data views
 */

// Load ParentNotificationsController for unread count
require_once __DIR__ . '/ParentNotificationsController.php';

class ParentDashboardController
{
    /**
     * Parent dashboard
     */
    public function index()
    {
        $this->requireParentAuth();

        $pdo = Database::getTenantConnection();
        $parentId = $_SESSION['parent_id'];
        $children = $_SESSION['parent_children'] ?? [];

        // Set unread notification count for badge
        $_SESSION['parent_unread_notifications'] = ParentNotificationsController::getUnreadCount($parentId);

        // Get summary data for all approved/linked children
        $childrenData = [];
        foreach ($children as $child) {
            $childData = [
                'student' => $child,
                'pending_fees' => 0,
                'attendance_rate' => null,
                'recent_grades' => []
            ];

            // Get pending fees
            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(i.total_amount - i.amount_paid), 0) as pending
                FROM invoices i
                WHERE i.student_id = :student_id
                AND i.status IN ('unpaid', 'partial')
            ");
            $stmt->execute(['student_id' => $child['id']]);
            $childData['pending_fees'] = $stmt->fetchColumn() ?: 0;

            // Get attendance rate (last 30 days)
            try {
                $stmt = $pdo->prepare("
                    SELECT
                        COUNT(*) as total_days,
                        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days
                    FROM student_attendance
                    WHERE student_id = :student_id
                    AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                ");
                $stmt->execute(['student_id' => $child['id']]);
                $attendance = $stmt->fetch();
                if ($attendance && $attendance['total_days'] > 0) {
                    $childData['attendance_rate'] = round(($attendance['present_days'] / $attendance['total_days']) * 100);
                }
            } catch (Exception $e) {
                // Attendance data not available
            }

            $childrenData[] = $childData;
        }

        // Get pending linkage requests
        $stmt = $pdo->prepare("
            SELECT id, admission_number, grade_name, status, created_at
            FROM parent_student_requests
            WHERE parent_account_id = :parent_id
            AND status = 'pending'
            ORDER BY created_at DESC
        ");
        $stmt->execute(['parent_id' => $parentId]);
        $pendingRequests = $stmt->fetchAll();

        // Get recent notifications
        $notifications = [];
        $unreadCount = 0;
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM parent_notifications
                WHERE parent_account_id = :parent_id
                ORDER BY created_at DESC
                LIMIT 5
            ");
            $stmt->execute(['parent_id' => $parentId]);
            $notifications = $stmt->fetchAll();

            // Get unread notification count
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM parent_notifications
                WHERE parent_account_id = :parent_id AND read_at IS NULL
            ");
            $stmt->execute(['parent_id' => $parentId]);
            $unreadCount = $stmt->fetchColumn();
        } catch (Exception $e) {
            // parent_notifications table might not exist yet
        }

        Response::view('parent.dashboard', [
            'childrenData' => $childrenData,
            'pendingRequests' => $pendingRequests,
            'notifications' => $notifications,
            'unreadCount' => $unreadCount
        ]);
    }

    /**
     * View child's fee statement
     */
    public function fees($studentId)
    {
        $this->requireParentAuth();
        $this->verifyChildAccess($studentId);

        $pdo = Database::getTenantConnection();

        // Get student info using grades/streams
        $stmt = $pdo->prepare("
            SELECT s.*,
                   g.grade_name as class_name,
                   st.stream_name as stream
            FROM students s
            LEFT JOIN student_enrollments se ON s.id = se.student_id AND se.is_current = 1
            LEFT JOIN streams st ON se.stream_id = st.id
            LEFT JOIN grades g ON st.grade_id = g.id
            WHERE s.id = :id
        ");
        $stmt->execute(['id' => $studentId]);
        $student = $stmt->fetch();

        // Get invoices
        $stmt = $pdo->prepare("
            SELECT i.*, t.term_name
            FROM invoices i
            LEFT JOIN terms t ON i.term_id = t.id
            WHERE i.student_id = :student_id
            ORDER BY i.created_at DESC
        ");
        $stmt->execute(['student_id' => $studentId]);
        $invoices = $stmt->fetchAll();

        // Get payments
        $stmt = $pdo->prepare("
            SELECT p.*, p.receipt_number
            FROM payments p
            WHERE p.student_id = :student_id
            ORDER BY p.payment_date DESC
            LIMIT 20
        ");
        $stmt->execute(['student_id' => $studentId]);
        $payments = $stmt->fetchAll();

        // Calculate totals
        $totalBilled = 0;
        $totalPaid = 0;
        foreach ($invoices as $inv) {
            $totalBilled += $inv['total_amount'];
            $totalPaid += $inv['amount_paid'];
        }

        Response::view('parent.fees', [
            'student' => $student,
            'invoices' => $invoices,
            'payments' => $payments,
            'totalBilled' => $totalBilled,
            'totalPaid' => $totalPaid,
            'balance' => $totalBilled - $totalPaid
        ]);
    }

    /**
     * View child's attendance
     */
    public function attendance($studentId)
    {
        $this->requireParentAuth();
        $this->verifyChildAccess($studentId);

        $pdo = Database::getTenantConnection();

        // Get student info using grades/streams
        $stmt = $pdo->prepare("
            SELECT s.*,
                   g.grade_name as class_name,
                   st.stream_name as stream
            FROM students s
            LEFT JOIN student_enrollments se ON s.id = se.student_id AND se.is_current = 1
            LEFT JOIN streams st ON se.stream_id = st.id
            LEFT JOIN grades g ON st.grade_id = g.id
            WHERE s.id = :id
        ");
        $stmt->execute(['id' => $studentId]);
        $student = $stmt->fetch();

        // Get attendance records (last 3 months)
        $records = [];
        try {
            $stmt = $pdo->prepare("
                SELECT a.*, u.full_name as marked_by_name
                FROM student_attendance a
                LEFT JOIN users u ON a.recorded_by = u.id
                WHERE a.student_id = :student_id
                AND a.attendance_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
                ORDER BY a.attendance_date DESC
            ");
            $stmt->execute(['student_id' => $studentId]);
            $records = $stmt->fetchAll();
        } catch (Exception $e) {
            // Attendance data not available
        }

        // Calculate summary
        $summary = ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0];
        foreach ($records as $rec) {
            if (isset($summary[$rec['status']])) {
                $summary[$rec['status']]++;
            }
        }
        $total = array_sum($summary);
        $attendanceRate = $total > 0 ? round(($summary['present'] / $total) * 100, 1) : 0;

        Response::view('parent.attendance', [
            'student' => $student,
            'records' => $records,
            'summary' => $summary,
            'attendanceRate' => $attendanceRate
        ]);
    }

    /**
     * View child's profile/overview
     */
    public function childProfile($studentId)
    {
        $this->requireParentAuth();
        $this->verifyChildAccess($studentId);

        $pdo = Database::getTenantConnection();

        // Get full student info using grades/streams instead of classes
        $stmt = $pdo->prepare("
            SELECT s.*,
                   g.grade_name as class_name,
                   st.stream_name as stream,
                   ca.campus_name
            FROM students s
            LEFT JOIN student_enrollments se ON s.id = se.student_id AND se.is_current = 1
            LEFT JOIN streams st ON se.stream_id = st.id
            LEFT JOIN grades g ON st.grade_id = g.id
            LEFT JOIN campuses ca ON s.campus_id = ca.id
            WHERE s.id = :id
        ");
        $stmt->execute(['id' => $studentId]);
        $student = $stmt->fetch();

        // Get class teacher (from stream)
        $classTeacher = null;
        try {
            $stmt = $pdo->prepare("
                SELECT stf.first_name, stf.last_name, stf.phone, stf.email
                FROM staff stf
                JOIN streams st ON st.class_teacher_id = stf.id
                JOIN student_enrollments se ON se.stream_id = st.id AND se.is_current = 1
                WHERE se.student_id = :student_id
            ");
            $stmt->execute(['student_id' => $studentId]);
            $classTeacher = $stmt->fetch();
        } catch (Exception $e) {
            // Class teacher data not available
        }

        Response::view('parent.child-profile', [
            'student' => $student,
            'classTeacher' => $classTeacher
        ]);
    }

    /**
     * Notifications list
     */
    public function notifications()
    {
        $this->requireParentAuth();

        $pdo = Database::getTenantConnection();

        $stmt = $pdo->prepare("
            SELECT * FROM parent_notifications
            WHERE parent_account_id = :parent_id
            ORDER BY created_at DESC
            LIMIT 50
        ");
        $stmt->execute(['parent_id' => $_SESSION['parent_id']]);
        $notifications = $stmt->fetchAll();

        // Mark all as read
        $stmt = $pdo->prepare("
            UPDATE parent_notifications
            SET read_at = NOW()
            WHERE parent_account_id = :parent_id AND read_at IS NULL
        ");
        $stmt->execute(['parent_id' => $_SESSION['parent_id']]);

        Response::view('parent.notifications', [
            'notifications' => $notifications
        ]);
    }

    /**
     * Show link student form
     */
    public function showLinkStudent()
    {
        $this->requireParentAuth();

        $pdo = Database::getTenantConnection();
        $parentId = $_SESSION['parent_id'];

        // Get existing pending requests to avoid duplicates
        $stmt = $pdo->prepare("
            SELECT admission_number FROM parent_student_requests
            WHERE parent_account_id = :parent_id AND status = 'pending'
        ");
        $stmt->execute(['parent_id' => $parentId]);
        $pendingAdmissions = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Get max students per request setting
        $maxStudents = 5;
        try {
            $stmt = $pdo->prepare("
                SELECT setting_value FROM parent_portal_settings
                WHERE setting_key = 'max_students_per_request'
            ");
            $stmt->execute();
            $result = $stmt->fetchColumn();
            if ($result) {
                $maxStudents = (int)$result;
            }
        } catch (Exception $e) {
            // Use default
        }

        Response::view('parent.link-student', [
            'pendingAdmissions' => $pendingAdmissions,
            'maxStudents' => $maxStudents
        ]);
    }

    /**
     * Process link student request
     */
    public function linkStudent()
    {
        $this->requireParentAuth();

        $students = Request::get('students', []);

        if (empty($students)) {
            flash('error', 'Please enter at least one student.');
            Response::redirect('/parent/link-student');
        }

        // Get max students per request
        $pdo = Database::getTenantConnection();
        $maxStudents = 5;
        try {
            $stmt = $pdo->prepare("
                SELECT setting_value FROM parent_portal_settings
                WHERE setting_key = 'max_students_per_request'
            ");
            $stmt->execute();
            $result = $stmt->fetchColumn();
            if ($result) {
                $maxStudents = (int)$result;
            }
        } catch (Exception $e) {
            // Use default
        }

        if (count($students) > $maxStudents) {
            flash('error', "You can only link up to {$maxStudents} students at once.");
            Response::redirect('/parent/link-student');
        }

        $parentId = $_SESSION['parent_id'];
        $successCount = 0;
        $errors = [];

        try {
            $pdo->beginTransaction();

            foreach ($students as $index => $studentData) {
                $admissionNumber = trim($studentData['admission_number'] ?? '');
                $gradeName = trim($studentData['grade_name'] ?? '');

                // Skip empty entries
                if (empty($admissionNumber) && empty($gradeName)) {
                    continue;
                }

                // Validate both fields are filled
                if (empty($admissionNumber) || empty($gradeName)) {
                    $errors[] = "Student " . ($index + 1) . ": Both admission number and grade are required.";
                    continue;
                }

                // Check for existing pending request with same admission number
                $stmt = $pdo->prepare("
                    SELECT id FROM parent_student_requests
                    WHERE parent_account_id = :parent_id
                    AND admission_number = :admission_number
                    AND status = 'pending'
                ");
                $stmt->execute([
                    'parent_id' => $parentId,
                    'admission_number' => $admissionNumber
                ]);

                if ($stmt->fetch()) {
                    $errors[] = "Student '{$admissionNumber}': A pending request already exists.";
                    continue;
                }

                // Check if already linked (approved)
                $stmt = $pdo->prepare("
                    SELECT sg.id FROM student_guardians sg
                    JOIN students s ON sg.student_id = s.id
                    JOIN parent_accounts pa ON sg.guardian_id = pa.guardian_id
                    WHERE pa.id = :parent_id
                    AND s.admission_number = :admission_number
                    AND sg.link_status = 'active'
                ");
                $stmt->execute([
                    'parent_id' => $parentId,
                    'admission_number' => $admissionNumber
                ]);

                if ($stmt->fetch()) {
                    $errors[] = "Student '{$admissionNumber}': Already linked to your account.";
                    continue;
                }

                // Create the request
                $stmt = $pdo->prepare("
                    INSERT INTO parent_student_requests
                    (parent_account_id, admission_number, grade_name, status, created_at)
                    VALUES (:parent_id, :admission_number, :grade_name, 'pending', NOW())
                ");
                $stmt->execute([
                    'parent_id' => $parentId,
                    'admission_number' => $admissionNumber,
                    'grade_name' => $gradeName
                ]);

                $successCount++;
            }

            $pdo->commit();

            if ($successCount > 0) {
                $message = $successCount === 1
                    ? 'Your student linkage request has been submitted for approval.'
                    : "{$successCount} student linkage requests have been submitted for approval.";
                flash('success', $message);
            }

            if (!empty($errors)) {
                flash('warning', implode('<br>', $errors));
            }

            Response::redirect('/parent/dashboard');

        } catch (Exception $e) {
            $pdo->rollBack();
            logMessage("Parent link student error: " . $e->getMessage(), 'error');
            flash('error', 'An error occurred. Please try again.');
            Response::redirect('/parent/link-student');
        }
    }

    /**
     * View linkage request history
     */
    public function linkRequests()
    {
        $this->requireParentAuth();

        $pdo = Database::getTenantConnection();
        $parentId = $_SESSION['parent_id'];

        $stmt = $pdo->prepare("
            SELECT psr.*,
                   s.first_name as student_first_name,
                   s.last_name as student_last_name,
                   u.full_name as reviewed_by_name
            FROM parent_student_requests psr
            LEFT JOIN students s ON psr.student_id = s.id
            LEFT JOIN users u ON psr.reviewed_by = u.id
            WHERE psr.parent_account_id = :parent_id
            ORDER BY psr.created_at DESC
        ");
        $stmt->execute(['parent_id' => $parentId]);
        $requests = $stmt->fetchAll();

        Response::view('parent.link-requests', [
            'requests' => $requests
        ]);
    }

    /**
     * Parent profile/settings
     */
    public function profile()
    {
        $this->requireParentAuth();

        $pdo = Database::getTenantConnection();

        $stmt = $pdo->prepare("
            SELECT pa.*, g.first_name, g.last_name, g.phone as guardian_phone,
                   g.email as guardian_email, g.occupation, g.id_number, g.address
            FROM parent_accounts pa
            JOIN guardians g ON pa.guardian_id = g.id
            WHERE pa.id = :id
        ");
        $stmt->execute(['id' => $_SESSION['parent_id']]);
        $parent = $stmt->fetch();

        Response::view('parent.profile', [
            'parent' => $parent
        ]);
    }

    /**
     * Update password
     */
    public function updatePassword()
    {
        $this->requireParentAuth();

        $current = Request::get('current_password');
        $new = Request::get('new_password');
        $confirm = Request::get('confirm_password');

        if (empty($current) || empty($new)) {
            flash('error', 'Please fill in all password fields.');
            Response::back();
        }

        if ($new !== $confirm) {
            flash('error', 'New passwords do not match.');
            Response::back();
        }

        if (strlen($new) < 8) {
            flash('error', 'Password must be at least 8 characters.');
            Response::back();
        }

        try {
            $pdo = Database::getTenantConnection();

            // Verify current password
            $stmt = $pdo->prepare("SELECT password_hash FROM parent_accounts WHERE id = :id");
            $stmt->execute(['id' => $_SESSION['parent_id']]);
            $parent = $stmt->fetch();

            if (!password_verify($current, $parent['password_hash'])) {
                flash('error', 'Current password is incorrect.');
                Response::back();
            }

            // Update password
            $stmt = $pdo->prepare("
                UPDATE parent_accounts
                SET password_hash = :password_hash, updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                'password_hash' => password_hash($new, PASSWORD_DEFAULT),
                'id' => $_SESSION['parent_id']
            ]);

            flash('success', 'Password updated successfully.');
            Response::redirect('/parent/profile');

        } catch (Exception $e) {
            logMessage("Parent password update error: " . $e->getMessage(), 'error');
            flash('error', 'Failed to update password.');
            Response::back();
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

    /**
     * Verify parent has access to this child
     */
    private function verifyChildAccess($studentId)
    {
        $children = $_SESSION['parent_children'] ?? [];
        $hasAccess = false;

        foreach ($children as $child) {
            if ($child['id'] == $studentId) {
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            flash('error', 'You do not have access to this student.');
            Response::redirect('/parent/dashboard');
        }
    }
}
