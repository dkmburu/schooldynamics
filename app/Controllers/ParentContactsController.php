<?php
/**
 * Parent Contacts Controller
 * Displays school contact directory for parents
 */

class ParentContactsController
{
    /**
     * Display school contact directory
     */
    public function index()
    {
        $this->requireParentAuth();

        $pdo = Database::getTenantConnection();
        $schoolId = $_SESSION['tenant_id'];

        // Get all active school contacts ordered by display_order
        $stmt = $pdo->prepare("
            SELECT
                sc.*,
                ct.name as type_name,
                ct.icon as type_icon,
                sc.department_name as contact_name,
                sc.contact_person as position_title,
                sc.phone as phone_primary,
                sc.available_hours as office_hours,
                sc.is_emergency,
                FALSE as is_primary
            FROM school_contacts sc
            JOIN contact_types ct ON sc.contact_type_id = ct.id
            WHERE sc.school_id = :school_id
            AND sc.is_active = 1
            ORDER BY sc.display_order ASC, ct.sort_order ASC
        ");
        $stmt->execute(['school_id' => $schoolId]);
        $contacts = $stmt->fetchAll();

        // Get class teacher contacts for parent's children
        $children = $_SESSION['parent_children'] ?? [];
        $classTeachers = [];

        foreach ($children as $child) {
            try {
                $stmt = $pdo->prepare("
                    SELECT
                        s.first_name || ' ' || s.last_name as student_name,
                        g.grade_name,
                        st.stream_name,
                        stf.first_name as teacher_first_name,
                        stf.last_name as teacher_last_name,
                        stf.phone as teacher_phone,
                        stf.email as teacher_email
                    FROM students s
                    LEFT JOIN student_enrollments se ON s.id = se.student_id AND se.is_current = 1
                    LEFT JOIN streams st ON se.stream_id = st.id
                    LEFT JOIN grades g ON st.grade_id = g.id
                    LEFT JOIN staff stf ON st.class_teacher_id = stf.id
                    WHERE s.id = :student_id
                ");
                $stmt->execute(['student_id' => $child['id']]);
                $teacher = $stmt->fetch();

                if ($teacher && !empty($teacher['teacher_first_name'])) {
                    $classTeachers[] = $teacher;
                }
            } catch (Exception $e) {
                // Skip if error
                continue;
            }
        }

        Response::view('parent.contacts', [
            'currentPage' => 'contacts',
            'contacts' => $contacts,
            'classTeachers' => $classTeachers
        ]);
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
