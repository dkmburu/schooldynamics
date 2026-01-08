<?php
/**
 * Students Controller
 * Manages enrolled students - profile, class allocation, documents, medical, etc.
 */

class StudentsController
{
    /**
     * List all students with search and filters
     */
    public function index()
    {
        // Check authentication
        if (!isAuthenticated()) {
            flash('error', 'Please login to access this page.');
            Response::redirect('/login');
        }

        // Check permission
        if (!hasPermission('Students.view') && !Gate::hasRole('ADMIN')) {
            Gate::deny('You need permission to view students');
        }

        try {
            $pdo = Database::getTenantConnection();

            // Get search and filter parameters
            $search = Request::get('search', '');
            $status = Request::get('status', '');
            $grade = Request::get('grade', '');
            $stream = Request::get('stream', '');
            $page = max(1, (int)Request::get('page', 1));
            $perPage = 50;
            $offset = ($page - 1) * $perPage;

            // Build WHERE clause
            $where = ['s.deleted_at IS NULL'];
            $params = [];

            // Filter by campus
            $currentCampusId = $_SESSION['current_campus_id'] ?? null;
            if ($currentCampusId && $currentCampusId !== 'all') {
                $where[] = "s.campus_id = :campus_id";
                $params['campus_id'] = $currentCampusId;
            }

            if (!empty($search)) {
                $where[] = "(s.first_name LIKE :search OR s.last_name LIKE :search OR s.admission_number LIKE :search OR s.middle_name LIKE :search)";
                $params['search'] = "%{$search}%";
            }

            if (!empty($status)) {
                $where[] = "s.status = :status";
                $params['status'] = $status;
            }

            if (!empty($grade)) {
                $where[] = "g.id = :grade";
                $params['grade'] = $grade;
            }

            if (!empty($stream)) {
                $where[] = "se.stream_id = :stream";
                $params['stream'] = $stream;
            }

            $whereClause = implode(' AND ', $where);

            // Get total count
            // Using nested INNER JOINs within LEFT JOIN for optimal performance
            $stmt = $pdo->prepare("
                SELECT COUNT(DISTINCT s.id) as total
                FROM students s
                LEFT JOIN (
                    student_enrollments se
                    INNER JOIN streams st ON st.id = se.stream_id
                    INNER JOIN grades g ON g.id = st.grade_id
                ) ON se.student_id = s.id AND se.is_current = 1
                WHERE {$whereClause}
            ");
            $stmt->execute($params);
            $totalRecords = $stmt->fetchColumn();
            $totalPages = ceil($totalRecords / $perPage);

            // Get students with current enrollment info
            // Using LEFT JOIN for enrollment (student may not be enrolled yet)
            // Using INNER JOIN for stream->grade chain (if enrolled, these MUST exist)
            $stmt = $pdo->prepare("
                SELECT
                    s.*,
                    st.stream_name,
                    g.grade_name,
                    g.id as grade_id,
                    ay.year_name as academic_year,
                    (SELECT GROUP_CONCAT(CONCAT(gd.first_name, ' ', gd.last_name) SEPARATOR ', ')
                     FROM student_guardians sg
                     INNER JOIN guardians gd ON gd.id = sg.guardian_id
                     WHERE sg.student_id = s.id AND sg.is_primary = 1
                     LIMIT 1) as primary_guardian,
                    (SELECT gd.phone FROM student_guardians sg
                     INNER JOIN guardians gd ON gd.id = sg.guardian_id
                     WHERE sg.student_id = s.id AND sg.is_primary = 1 LIMIT 1) as guardian_phone
                FROM students s
                LEFT JOIN (
                    student_enrollments se
                    INNER JOIN streams st ON st.id = se.stream_id
                    INNER JOIN grades g ON g.id = st.grade_id
                    INNER JOIN academic_years ay ON ay.id = se.academic_year_id
                ) ON se.student_id = s.id AND se.is_current = 1
                WHERE {$whereClause}
                ORDER BY s.last_name, s.first_name ASC
                LIMIT {$perPage} OFFSET {$offset}
            ");
            $stmt->execute($params);
            $students = $stmt->fetchAll();

            // Get grades for filter
            $stmt = $pdo->query("SELECT id, grade_name FROM grades WHERE is_active = 1 ORDER BY sort_order, grade_name");
            $grades = $stmt->fetchAll();

            // Get streams for filter
            $stmt = $pdo->query("
                SELECT st.id, CONCAT(g.grade_name, ' - ', st.stream_name) as display_name
                FROM streams st
                JOIN grades g ON g.id = st.grade_id
                WHERE st.is_active = 1
                ORDER BY g.sort_order, st.stream_name
            ");
            $streams = $stmt->fetchAll();

            // Get status counts
            $stmt = $pdo->query("
                SELECT status, COUNT(*) as count
                FROM students
                WHERE deleted_at IS NULL
                GROUP BY status
            ");
            $statusCounts = [];
            while ($row = $stmt->fetch()) {
                $statusCounts[$row['status']] = $row['count'];
            }

            Response::view('students/index', [
                'students' => $students,
                'grades' => $grades,
                'streams' => $streams,
                'search' => $search,
                'statusFilter' => $status,
                'gradeFilter' => $grade,
                'streamFilter' => $stream,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalRecords' => $totalRecords,
                'perPage' => $perPage,
                'statusCounts' => $statusCounts,
                'pageTitle' => 'All Students'
            ]);

        } catch (Exception $e) {
            error_log("StudentsController::index error: " . $e->getMessage());
            flash('error', 'Failed to load students: ' . $e->getMessage());
            Response::redirect('/dashboard');
        }
    }

    /**
     * Show single student profile with all tabs
     */
    public function show($id)
    {
        if (!isAuthenticated()) {
            flash('error', 'Please login to access this page.');
            Response::redirect('/login');
        }

        if (!hasPermission('Students.view') && !Gate::hasRole('ADMIN')) {
            Gate::deny('You need permission to view students');
        }

        try {
            $pdo = Database::getTenantConnection();

            // Get student with enrollment info
            // Using nested INNER JOINs within LEFT JOIN for optimal performance
            // Campus is always required for a student, so use INNER JOIN
            $stmt = $pdo->prepare("
                SELECT
                    s.*,
                    st.stream_name,
                    st.id as stream_id,
                    g.grade_name,
                    g.id as grade_id,
                    ay.year_name as academic_year,
                    ay.id as academic_year_id,
                    se.enrollment_date,
                    cam.campus_name
                FROM students s
                INNER JOIN campuses cam ON cam.id = s.campus_id
                LEFT JOIN (
                    student_enrollments se
                    INNER JOIN streams st ON st.id = se.stream_id
                    INNER JOIN grades g ON g.id = st.grade_id
                    INNER JOIN academic_years ay ON ay.id = se.academic_year_id
                ) ON se.student_id = s.id AND se.is_current = 1
                WHERE s.id = :id AND s.deleted_at IS NULL
            ");
            $stmt->execute(['id' => $id]);
            $student = $stmt->fetch();

            if (!$student) {
                flash('error', 'Student not found');
                Response::redirect('/students');
            }

            // Get guardians
            $stmt = $pdo->prepare("
                SELECT g.*, sg.relationship, sg.is_primary, sg.can_pickup
                FROM guardians g
                JOIN student_guardians sg ON sg.guardian_id = g.id
                WHERE sg.student_id = :student_id
                ORDER BY sg.is_primary DESC, g.first_name
            ");
            $stmt->execute(['student_id' => $id]);
            $guardians = $stmt->fetchAll();

            // Get fee account
            $stmt = $pdo->prepare("
                SELECT sfa.*,
                    (SELECT SUM(total_amount) FROM invoices WHERE student_fee_account_id = sfa.id AND status != 'cancelled') as total_invoiced,
                    (SELECT SUM(amount) FROM payments WHERE student_fee_account_id = sfa.id AND status = 'confirmed') as total_paid
                FROM student_fee_accounts sfa
                WHERE sfa.student_id = :student_id
                LIMIT 1
            ");
            $stmt->execute(['student_id' => $id]);
            $feeAccount = $stmt->fetch();

            // Get enrollment history
            $stmt = $pdo->prepare("
                SELECT se.*, st.stream_name, g.grade_name, ay.year_name
                FROM student_enrollments se
                JOIN streams st ON st.id = se.stream_id
                JOIN grades g ON g.id = st.grade_id
                JOIN academic_years ay ON ay.id = se.academic_year_id
                WHERE se.student_id = :student_id
                ORDER BY se.enrollment_date DESC
            ");
            $stmt->execute(['student_id' => $id]);
            $enrollmentHistory = $stmt->fetchAll();

            // Get medical info if exists
            $stmt = $pdo->prepare("SELECT * FROM student_medical_info WHERE student_id = :student_id");
            $stmt->execute(['student_id' => $id]);
            $medicalInfo = $stmt->fetch();

            // Get education history if exists
            $stmt = $pdo->prepare("SELECT * FROM student_education_history WHERE student_id = :student_id ORDER BY year_from DESC");
            $stmt->execute(['student_id' => $id]);
            $educationHistory = $stmt->fetchAll();

            // Get documents
            $stmt = $pdo->prepare("
                SELECT sd.*, dt.type_name, dt.icon
                FROM student_documents sd
                LEFT JOIN document_types dt ON dt.id = sd.document_type_id
                WHERE sd.student_id = :student_id
                ORDER BY sd.created_at DESC
            ");
            $stmt->execute(['student_id' => $id]);
            $documents = $stmt->fetchAll();

            // Get transport assignment if any (with zone info)
            $transportAssignment = null;
            try {
                $stmt = $pdo->prepare("
                    SELECT ta.*, tz.zone_name, tz.zone_code
                    FROM transport_assignments ta
                    LEFT JOIN transport_zones tz ON tz.id = ta.route_id
                    WHERE ta.student_id = :student_id AND ta.is_active = 1
                ");
                $stmt->execute(['student_id' => $id]);
                $transportAssignment = $stmt->fetch();
            } catch (Exception $e) {
                // Transport tables may not exist, ignore
            }

            // Get recent invoices
            $stmt = $pdo->prepare("
                SELECT i.*, t.term_name
                FROM invoices i
                LEFT JOIN terms t ON t.id = i.term_id
                WHERE i.student_fee_account_id = :fee_account_id
                ORDER BY i.created_at DESC
                LIMIT 10
            ");
            if ($feeAccount) {
                $stmt->execute(['fee_account_id' => $feeAccount['id']]);
                $recentInvoices = $stmt->fetchAll();
            } else {
                $recentInvoices = [];
            }

            // Get recent payments
            $stmt = $pdo->prepare("
                SELECT p.*, pm.name as payment_method_name
                FROM payments p
                LEFT JOIN payment_methods pm ON pm.id = p.payment_method_id
                WHERE p.student_fee_account_id = :fee_account_id
                ORDER BY p.payment_date DESC
                LIMIT 10
            ");
            if ($feeAccount) {
                $stmt->execute(['fee_account_id' => $feeAccount['id']]);
                $recentPayments = $stmt->fetchAll();
            } else {
                $recentPayments = [];
            }

            // Get activity log
            $stmt = $pdo->prepare("
                SELECT sal.*, u.full_name as user_name
                FROM student_audit_log sal
                LEFT JOIN users u ON u.id = sal.user_id
                WHERE sal.student_id = :student_id
                ORDER BY sal.created_at DESC
                LIMIT 50
            ");
            $stmt->execute(['student_id' => $id]);
            $activityLog = $stmt->fetchAll();

            // Get available streams for class change
            $availableStreams = [];
            try {
                $stmt = $pdo->query("
                    SELECT st.id, CONCAT(g.grade_name, ' - ', st.stream_name) as display_name
                    FROM streams st
                    JOIN grades g ON g.id = st.grade_id
                    WHERE st.is_active = 1
                    ORDER BY g.sort_order, g.grade_name, st.stream_name
                ");
                $availableStreams = $stmt->fetchAll();
            } catch (Exception $e) {
                // streams table may be empty or not exist
            }

            // Get document types for upload
            $documentTypes = [];
            try {
                $stmt = $pdo->query("SELECT * FROM document_types WHERE is_active = 1 ORDER BY type_name");
                $documentTypes = $stmt->fetchAll();
            } catch (Exception $e) {
                // document_types table may not exist yet
            }

            // Get all transport zones for assignment
            $transportRoutes = [];
            try {
                $stmt = $pdo->query("SELECT id, zone_name, zone_code FROM transport_zones WHERE is_active = 1 ORDER BY zone_name");
                $transportRoutes = $stmt->fetchAll();
            } catch (Exception $e) {
                // Transport tables may not exist
            }

            // Get attendance records and summary
            $attendanceMonth = Request::get('attendance_month', date('Y-m'));
            $attendanceRecords = [];
            $attendanceSummary = ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0];
            try {
                // Get attendance records for selected month
                $stmt = $pdo->prepare("
                    SELECT *
                    FROM student_attendance
                    WHERE student_id = :student_id
                    AND DATE_FORMAT(attendance_date, '%Y-%m') = :month
                    ORDER BY attendance_date DESC
                    LIMIT 31
                ");
                $stmt->execute(['student_id' => $id, 'month' => $attendanceMonth]);
                $attendanceRecords = $stmt->fetchAll();

                // Get attendance summary for current academic year
                $stmt = $pdo->prepare("
                    SELECT status, COUNT(*) as count
                    FROM student_attendance
                    WHERE student_id = :student_id
                    GROUP BY status
                ");
                $stmt->execute(['student_id' => $id]);
                while ($row = $stmt->fetch()) {
                    $attendanceSummary[$row['status']] = $row['count'];
                }
            } catch (Exception $e) {
                // Attendance table may not exist
            }

            // Get terms for filter dropdown
            $terms = [];
            try {
                $stmt = $pdo->query("SELECT id, term_name FROM terms ORDER BY term_number");
                $terms = $stmt->fetchAll();
            } catch (Exception $e) {
                // Terms table may not exist or be empty
            }

            // Get assessment data
            $selectedTerm = Request::get('assessment_term', '');
            $subjectPerformance = [];
            $recentAssessments = [];
            $performanceSummary = ['average_score' => 'N/A', 'highest_score' => 'N/A', 'lowest_score' => 'N/A', 'class_rank' => 'N/A'];
            try {
                // Get recent assessments with scores
                $termFilter = $selectedTerm ? "AND a.term_id = :term_id" : "";
                $assessmentParams = ['student_id' => $id];
                if ($selectedTerm) {
                    $assessmentParams['term_id'] = $selectedTerm;
                }

                $stmt = $pdo->prepare("
                    SELECT
                        ar.id,
                        a.assessment_name,
                        a.assessment_type,
                        a.max_score,
                        a.assessment_date,
                        ar.score,
                        ROUND((ar.score / a.max_score) * 100, 1) as score_percent,
                        sub.subject_name
                    FROM assessment_results ar
                    JOIN assessments a ON a.id = ar.assessment_id
                    LEFT JOIN subjects sub ON sub.id = a.subject_id
                    WHERE ar.student_id = :student_id
                    {$termFilter}
                    ORDER BY a.assessment_date DESC
                    LIMIT 20
                ");
                $stmt->execute($assessmentParams);
                $recentAssessments = $stmt->fetchAll();

                // Get subject-wise performance summary
                $stmt = $pdo->prepare("
                    SELECT
                        sub.subject_name,
                        MAX(CASE WHEN a.assessment_type = 'CAT1' THEN ar.score END) as cat1,
                        MAX(CASE WHEN a.assessment_type = 'CAT2' THEN ar.score END) as cat2,
                        MAX(CASE WHEN a.assessment_type IN ('END_TERM', 'EXAM') THEN ar.score END) as end_term,
                        ROUND(AVG(ar.score), 1) as average,
                        CASE
                            WHEN AVG(ar.score) >= 80 THEN 'A'
                            WHEN AVG(ar.score) >= 75 THEN 'A-'
                            WHEN AVG(ar.score) >= 70 THEN 'B+'
                            WHEN AVG(ar.score) >= 65 THEN 'B'
                            WHEN AVG(ar.score) >= 60 THEN 'B-'
                            WHEN AVG(ar.score) >= 55 THEN 'C+'
                            WHEN AVG(ar.score) >= 50 THEN 'C'
                            WHEN AVG(ar.score) >= 45 THEN 'C-'
                            WHEN AVG(ar.score) >= 40 THEN 'D+'
                            WHEN AVG(ar.score) >= 35 THEN 'D'
                            WHEN AVG(ar.score) >= 30 THEN 'D-'
                            ELSE 'E'
                        END as grade
                    FROM assessment_results ar
                    JOIN assessments a ON a.id = ar.assessment_id
                    JOIN subjects sub ON sub.id = a.subject_id
                    WHERE ar.student_id = :student_id
                    {$termFilter}
                    GROUP BY sub.id, sub.subject_name
                    ORDER BY sub.subject_name
                ");
                $stmt->execute($assessmentParams);
                $subjectPerformance = $stmt->fetchAll();

                // Get overall performance summary
                if (!empty($recentAssessments)) {
                    $scores = array_column($recentAssessments, 'score_percent');
                    $performanceSummary['average_score'] = round(array_sum($scores) / count($scores), 1) . '%';
                    $performanceSummary['highest_score'] = round(max($scores), 1) . '%';
                    $performanceSummary['lowest_score'] = round(min($scores), 1) . '%';
                }
            } catch (Exception $e) {
                // Assessment tables may not exist
            }

            Response::view('students/show', [
                'student' => $student,
                'guardians' => $guardians,
                'feeAccount' => $feeAccount,
                'enrollmentHistory' => $enrollmentHistory,
                'medicalInfo' => $medicalInfo,
                'educationHistory' => $educationHistory,
                'documents' => $documents,
                'transportAssignment' => $transportAssignment,
                'recentInvoices' => $recentInvoices,
                'recentPayments' => $recentPayments,
                'activityLog' => $activityLog,
                'availableStreams' => $availableStreams,
                'documentTypes' => $documentTypes,
                'transportRoutes' => $transportRoutes,
                // Attendance data
                'attendanceRecords' => $attendanceRecords,
                'attendanceSummary' => $attendanceSummary,
                'attendanceMonth' => $attendanceMonth,
                // Assessment data
                'terms' => $terms,
                'selectedTerm' => $selectedTerm,
                'subjectPerformance' => $subjectPerformance,
                'recentAssessments' => $recentAssessments,
                'performanceSummary' => $performanceSummary,
                'pageTitle' => $student['first_name'] . ' ' . $student['last_name']
            ]);

        } catch (Exception $e) {
            error_log("StudentsController::show error: " . $e->getMessage());
            flash('error', 'Failed to load student: ' . $e->getMessage());
            Response::redirect('/students');
        }
    }

    /**
     * Show edit form for student
     */
    public function edit($id)
    {
        if (!isAuthenticated()) {
            flash('error', 'Please login to access this page.');
            Response::redirect('/login');
        }

        if (!hasPermission('Students.write') && !Gate::hasRole('ADMIN')) {
            Gate::deny('You need permission to edit students');
        }

        try {
            $pdo = Database::getTenantConnection();

            $stmt = $pdo->prepare("
                SELECT s.*, se.stream_id
                FROM students s
                LEFT JOIN student_enrollments se ON se.student_id = s.id AND se.is_current = 1
                WHERE s.id = :id AND s.deleted_at IS NULL
            ");
            $stmt->execute(['id' => $id]);
            $student = $stmt->fetch();

            if (!$student) {
                flash('error', 'Student not found');
                Response::redirect('/students');
            }

            // Get streams for dropdown
            $stmt = $pdo->query("
                SELECT st.id, CONCAT(g.grade_name, ' - ', st.stream_name) as display_name
                FROM streams st
                JOIN grades g ON g.id = st.grade_id
                WHERE st.is_active = 1
                ORDER BY g.sort_order, g.grade_name, st.stream_name
            ");
            $streams = $stmt->fetchAll();

            // Get campuses
            $stmt = $pdo->query("SELECT id, campus_name FROM campuses WHERE is_active = 1 ORDER BY campus_name");
            $campuses = $stmt->fetchAll();

            Response::view('students/edit', [
                'student' => $student,
                'streams' => $streams,
                'campuses' => $campuses,
                'pageTitle' => 'Edit Student: ' . $student['first_name'] . ' ' . $student['last_name']
            ]);

        } catch (Exception $e) {
            error_log("StudentsController::edit error: " . $e->getMessage());
            flash('error', 'Failed to load student: ' . $e->getMessage());
            Response::redirect('/students');
        }
    }

    /**
     * Update student record
     */
    public function update($id)
    {
        if (!isAuthenticated()) {
            flash('error', 'Please login to access this page.');
            Response::redirect('/login');
        }

        if (!hasPermission('Students.write') && !Gate::hasRole('ADMIN')) {
            Gate::deny('You need permission to edit students');
        }

        try {
            $pdo = Database::getTenantConnection();

            // Validate input
            $firstName = trim(Request::post('first_name', ''));
            $middleName = trim(Request::post('middle_name', ''));
            $lastName = trim(Request::post('last_name', ''));
            $dateOfBirth = Request::post('date_of_birth', '');
            $gender = Request::post('gender', '');
            $status = Request::post('status', 'active');
            $campusId = Request::post('campus_id', null);
            $streamId = Request::post('stream_id', null);

            if (empty($firstName) || empty($lastName)) {
                flash('error', 'First name and last name are required');
                Response::redirect("/students/{$id}/edit");
            }

            // Update student
            $stmt = $pdo->prepare("
                UPDATE students SET
                    first_name = :first_name,
                    middle_name = :middle_name,
                    last_name = :last_name,
                    date_of_birth = :date_of_birth,
                    gender = :gender,
                    status = :status,
                    campus_id = :campus_id,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                'first_name' => $firstName,
                'middle_name' => $middleName ?: null,
                'last_name' => $lastName,
                'date_of_birth' => $dateOfBirth ?: null,
                'gender' => $gender ?: null,
                'status' => $status,
                'campus_id' => $campusId ?: 1,
                'id' => $id
            ]);

            // Update enrollment if stream changed
            if ($streamId) {
                $stmt = $pdo->prepare("
                    UPDATE student_enrollments
                    SET stream_id = :stream_id
                    WHERE student_id = :student_id AND is_current = 1
                ");
                $stmt->execute(['stream_id' => $streamId, 'student_id' => $id]);
            }

            // Log the update
            $this->logActivity($pdo, $id, 'updated', 'Student record updated');

            flash('success', 'Student updated successfully');
            Response::redirect("/students/{$id}");

        } catch (Exception $e) {
            error_log("StudentsController::update error: " . $e->getMessage());
            flash('error', 'Failed to update student: ' . $e->getMessage());
            Response::redirect("/students/{$id}/edit");
        }
    }

    /**
     * Update student medical information
     */
    public function updateMedical($id)
    {
        if (!isAuthenticated()) {
            return Response::json(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        if (!hasPermission('Students.write') && !Gate::hasRole('ADMIN')) {
            return Response::json(['success' => false, 'message' => 'Permission denied'], 403);
        }

        try {
            $pdo = Database::getTenantConnection();

            $bloodGroup = Request::post('blood_group', '');
            $allergies = Request::post('allergies', '');
            $medicalConditions = Request::post('medical_conditions', '');
            $medications = Request::post('medications', '');
            $emergencyContact = Request::post('emergency_contact', '');
            $emergencyPhone = Request::post('emergency_phone', '');
            $doctorName = Request::post('doctor_name', '');
            $doctorPhone = Request::post('doctor_phone', '');
            $notes = Request::post('notes', '');

            // Check if record exists
            $stmt = $pdo->prepare("SELECT id FROM student_medical_info WHERE student_id = :student_id");
            $stmt->execute(['student_id' => $id]);
            $existing = $stmt->fetch();

            if ($existing) {
                $stmt = $pdo->prepare("
                    UPDATE student_medical_info SET
                        blood_group = :blood_group,
                        allergies = :allergies,
                        medical_conditions = :medical_conditions,
                        medications = :medications,
                        emergency_contact = :emergency_contact,
                        emergency_phone = :emergency_phone,
                        doctor_name = :doctor_name,
                        doctor_phone = :doctor_phone,
                        notes = :notes,
                        updated_at = NOW()
                    WHERE student_id = :student_id
                ");
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO student_medical_info
                    (student_id, blood_group, allergies, medical_conditions, medications,
                     emergency_contact, emergency_phone, doctor_name, doctor_phone, notes, created_at)
                    VALUES
                    (:student_id, :blood_group, :allergies, :medical_conditions, :medications,
                     :emergency_contact, :emergency_phone, :doctor_name, :doctor_phone, :notes, NOW())
                ");
            }

            $stmt->execute([
                'student_id' => $id,
                'blood_group' => $bloodGroup ?: null,
                'allergies' => $allergies ?: null,
                'medical_conditions' => $medicalConditions ?: null,
                'medications' => $medications ?: null,
                'emergency_contact' => $emergencyContact ?: null,
                'emergency_phone' => $emergencyPhone ?: null,
                'doctor_name' => $doctorName ?: null,
                'doctor_phone' => $doctorPhone ?: null,
                'notes' => $notes ?: null
            ]);

            $this->logActivity($pdo, $id, 'medical_updated', 'Medical information updated');

            return Response::json(['success' => true, 'message' => 'Medical information saved']);

        } catch (Exception $e) {
            error_log("StudentsController::updateMedical error: " . $e->getMessage());
            return Response::json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Add education history record
     */
    public function addEducationHistory($id)
    {
        if (!isAuthenticated()) {
            return Response::json(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        if (!hasPermission('Students.write') && !Gate::hasRole('ADMIN')) {
            return Response::json(['success' => false, 'message' => 'Permission denied'], 403);
        }

        try {
            $pdo = Database::getTenantConnection();

            $schoolName = trim(Request::post('school_name', ''));
            $schoolAddress = Request::post('school_address', '');
            $yearFrom = Request::post('year_from', '');
            $yearTo = Request::post('year_to', '');
            $gradeCompleted = Request::post('grade_completed', '');
            $reasonForLeaving = Request::post('reason_for_leaving', '');
            $notes = Request::post('notes', '');

            if (empty($schoolName)) {
                return Response::json(['success' => false, 'message' => 'School name is required'], 400);
            }

            $stmt = $pdo->prepare("
                INSERT INTO student_education_history
                (student_id, school_name, school_address, year_from, year_to,
                 grade_completed, reason_for_leaving, notes, created_at)
                VALUES
                (:student_id, :school_name, :school_address, :year_from, :year_to,
                 :grade_completed, :reason_for_leaving, :notes, NOW())
            ");
            $stmt->execute([
                'student_id' => $id,
                'school_name' => $schoolName,
                'school_address' => $schoolAddress ?: null,
                'year_from' => $yearFrom ?: null,
                'year_to' => $yearTo ?: null,
                'grade_completed' => $gradeCompleted ?: null,
                'reason_for_leaving' => $reasonForLeaving ?: null,
                'notes' => $notes ?: null
            ]);

            $historyId = $pdo->lastInsertId();
            $this->logActivity($pdo, $id, 'education_added', "Added education history: {$schoolName}");

            return Response::json([
                'success' => true,
                'message' => 'Education history added',
                'history_id' => $historyId
            ]);

        } catch (Exception $e) {
            error_log("StudentsController::addEducationHistory error: " . $e->getMessage());
            return Response::json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete education history record
     */
    public function deleteEducationHistory($id, $historyId)
    {
        if (!isAuthenticated()) {
            return Response::json(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        if (!hasPermission('Students.write') && !Gate::hasRole('ADMIN')) {
            return Response::json(['success' => false, 'message' => 'Permission denied'], 403);
        }

        try {
            $pdo = Database::getTenantConnection();

            $stmt = $pdo->prepare("DELETE FROM student_education_history WHERE id = :id AND student_id = :student_id");
            $stmt->execute(['id' => $historyId, 'student_id' => $id]);

            $this->logActivity($pdo, $id, 'education_deleted', 'Deleted education history record');

            return Response::json(['success' => true, 'message' => 'Education history deleted']);

        } catch (Exception $e) {
            error_log("StudentsController::deleteEducationHistory error: " . $e->getMessage());
            return Response::json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Change student class/stream
     */
    public function changeClass($id)
    {
        if (!isAuthenticated()) {
            return Response::json(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        if (!hasPermission('Students.write') && !Gate::hasRole('ADMIN')) {
            return Response::json(['success' => false, 'message' => 'Permission denied'], 403);
        }

        try {
            $pdo = Database::getTenantConnection();

            $newStreamId = Request::post('stream_id', '');
            $reason = Request::post('reason', '');

            if (empty($newStreamId)) {
                return Response::json(['success' => false, 'message' => 'Stream is required'], 400);
            }

            // Get current academic year
            $stmt = $pdo->query("SELECT id FROM academic_years WHERE is_current = 1 LIMIT 1");
            $currentYear = $stmt->fetch();

            if (!$currentYear) {
                return Response::json(['success' => false, 'message' => 'No current academic year set'], 400);
            }

            // Mark old enrollment as not current
            $stmt = $pdo->prepare("UPDATE student_enrollments SET is_current = 0 WHERE student_id = :student_id AND is_current = 1");
            $stmt->execute(['student_id' => $id]);

            // Create new enrollment
            $stmt = $pdo->prepare("
                INSERT INTO student_enrollments (student_id, stream_id, academic_year_id, enrollment_date, is_current, created_at)
                VALUES (:student_id, :stream_id, :academic_year_id, CURDATE(), 1, NOW())
            ");
            $stmt->execute([
                'student_id' => $id,
                'stream_id' => $newStreamId,
                'academic_year_id' => $currentYear['id']
            ]);

            $this->logActivity($pdo, $id, 'class_changed', "Class changed. Reason: {$reason}");

            return Response::json(['success' => true, 'message' => 'Class changed successfully']);

        } catch (Exception $e) {
            error_log("StudentsController::changeClass error: " . $e->getMessage());
            return Response::json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Assign transport route to student
     */
    public function assignTransport($id)
    {
        if (!isAuthenticated()) {
            return Response::json(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        if (!hasPermission('Students.write') && !Gate::hasRole('ADMIN')) {
            return Response::json(['success' => false, 'message' => 'Permission denied'], 403);
        }

        try {
            $pdo = Database::getTenantConnection();

            $routeId = Request::post('route_id', '');
            $stopId = Request::post('stop_id', '');
            $pickupTime = Request::post('pickup_time', '');
            $dropoffTime = Request::post('dropoff_time', '');

            if (empty($routeId)) {
                return Response::json(['success' => false, 'message' => 'Route is required'], 400);
            }

            // Deactivate existing assignment
            $stmt = $pdo->prepare("UPDATE transport_assignments SET is_active = 0 WHERE student_id = :student_id");
            $stmt->execute(['student_id' => $id]);

            // Create new assignment
            $stmt = $pdo->prepare("
                INSERT INTO transport_assignments
                (student_id, route_id, stop_id, pickup_time, dropoff_time, is_active, created_at)
                VALUES
                (:student_id, :route_id, :stop_id, :pickup_time, :dropoff_time, 1, NOW())
            ");
            $stmt->execute([
                'student_id' => $id,
                'route_id' => $routeId,
                'stop_id' => $stopId ?: null,
                'pickup_time' => $pickupTime ?: null,
                'dropoff_time' => $dropoffTime ?: null
            ]);

            $this->logActivity($pdo, $id, 'transport_assigned', 'Transport route assigned');

            return Response::json(['success' => true, 'message' => 'Transport assigned successfully']);

        } catch (Exception $e) {
            error_log("StudentsController::assignTransport error: " . $e->getMessage());
            return Response::json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove transport assignment
     */
    public function removeTransport($id)
    {
        if (!isAuthenticated()) {
            return Response::json(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        if (!hasPermission('Students.write') && !Gate::hasRole('ADMIN')) {
            return Response::json(['success' => false, 'message' => 'Permission denied'], 403);
        }

        try {
            $pdo = Database::getTenantConnection();

            $stmt = $pdo->prepare("UPDATE transport_assignments SET is_active = 0 WHERE student_id = :student_id");
            $stmt->execute(['student_id' => $id]);

            $this->logActivity($pdo, $id, 'transport_removed', 'Transport assignment removed');

            return Response::json(['success' => true, 'message' => 'Transport assignment removed']);

        } catch (Exception $e) {
            error_log("StudentsController::removeTransport error: " . $e->getMessage());
            return Response::json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Upload student document
     */
    public function uploadDocument($id)
    {
        if (!isAuthenticated()) {
            return Response::json(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        if (!hasPermission('Students.write') && !Gate::hasRole('ADMIN')) {
            return Response::json(['success' => false, 'message' => 'Permission denied'], 403);
        }

        try {
            $pdo = Database::getTenantConnection();

            if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
                return Response::json(['success' => false, 'message' => 'No file uploaded'], 400);
            }

            $file = $_FILES['document'];
            $documentTypeId = Request::post('document_type_id', null);
            $title = Request::post('title', '');

            // Validate file
            $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($file['type'], $allowedTypes)) {
                return Response::json(['success' => false, 'message' => 'Invalid file type. Allowed: PDF, JPEG, PNG, GIF'], 400);
            }

            $maxSize = 5 * 1024 * 1024; // 5MB
            if ($file['size'] > $maxSize) {
                return Response::json(['success' => false, 'message' => 'File too large. Maximum: 5MB'], 400);
            }

            // Generate filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'student_' . $id . '_' . time() . '_' . uniqid() . '.' . $extension;

            // Create upload directory
            $uploadDir = __DIR__ . '/../../public/uploads/students/' . $id;
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $filePath = $uploadDir . '/' . $filename;
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                return Response::json(['success' => false, 'message' => 'Failed to save file'], 500);
            }

            // Save to database
            $stmt = $pdo->prepare("
                INSERT INTO student_documents
                (student_id, document_type_id, title, file_name, file_path, file_type, file_size, uploaded_by, created_at)
                VALUES
                (:student_id, :document_type_id, :title, :file_name, :file_path, :file_type, :file_size, :uploaded_by, NOW())
            ");
            $stmt->execute([
                'student_id' => $id,
                'document_type_id' => $documentTypeId ?: null,
                'title' => $title ?: $file['name'],
                'file_name' => $filename,
                'file_path' => '/uploads/students/' . $id . '/' . $filename,
                'file_type' => $file['type'],
                'file_size' => $file['size'],
                'uploaded_by' => $_SESSION['user_id'] ?? null
            ]);

            $documentId = $pdo->lastInsertId();
            $this->logActivity($pdo, $id, 'document_uploaded', "Document uploaded: {$title}");

            return Response::json([
                'success' => true,
                'message' => 'Document uploaded successfully',
                'document_id' => $documentId
            ]);

        } catch (Exception $e) {
            error_log("StudentsController::uploadDocument error: " . $e->getMessage());
            return Response::json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete student document
     */
    public function deleteDocument($id, $documentId)
    {
        if (!isAuthenticated()) {
            return Response::json(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        if (!hasPermission('Students.write') && !Gate::hasRole('ADMIN')) {
            return Response::json(['success' => false, 'message' => 'Permission denied'], 403);
        }

        try {
            $pdo = Database::getTenantConnection();

            // Get document info
            $stmt = $pdo->prepare("SELECT * FROM student_documents WHERE id = :id AND student_id = :student_id");
            $stmt->execute(['id' => $documentId, 'student_id' => $id]);
            $document = $stmt->fetch();

            if (!$document) {
                return Response::json(['success' => false, 'message' => 'Document not found'], 404);
            }

            // Delete file
            $filePath = __DIR__ . '/../../public' . $document['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Delete from database
            $stmt = $pdo->prepare("DELETE FROM student_documents WHERE id = :id");
            $stmt->execute(['id' => $documentId]);

            $this->logActivity($pdo, $id, 'document_deleted', "Document deleted: {$document['title']}");

            return Response::json(['success' => true, 'message' => 'Document deleted']);

        } catch (Exception $e) {
            error_log("StudentsController::deleteDocument error: " . $e->getMessage());
            return Response::json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Add guardian to student
     */
    public function addGuardian($id)
    {
        if (!isAuthenticated()) {
            return Response::json(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        if (!hasPermission('Students.write') && !Gate::hasRole('ADMIN')) {
            return Response::json(['success' => false, 'message' => 'Permission denied'], 403);
        }

        try {
            $pdo = Database::getTenantConnection();

            $firstName = trim(Request::post('first_name', ''));
            $lastName = trim(Request::post('last_name', ''));
            $phone = trim(Request::post('phone', ''));
            $email = trim(Request::post('email', ''));
            $idNumber = Request::post('id_number', '');
            $occupation = Request::post('occupation', '');
            $address = Request::post('address', '');
            $relationship = Request::post('relationship', '');
            $isPrimary = Request::post('is_primary', 0) ? 1 : 0;
            $canPickup = Request::post('can_pickup', 1) ? 1 : 0;

            if (empty($firstName) || empty($lastName) || empty($phone)) {
                return Response::json(['success' => false, 'message' => 'First name, last name, and phone are required'], 400);
            }

            // Check if guardian already exists by phone
            $stmt = $pdo->prepare("SELECT id FROM guardians WHERE phone = :phone");
            $stmt->execute(['phone' => $phone]);
            $existingGuardian = $stmt->fetch();

            if ($existingGuardian) {
                $guardianId = $existingGuardian['id'];
            } else {
                // Create new guardian
                $stmt = $pdo->prepare("
                    INSERT INTO guardians (first_name, last_name, phone, email, id_number, occupation, address, created_at)
                    VALUES (:first_name, :last_name, :phone, :email, :id_number, :occupation, :address, NOW())
                ");
                $stmt->execute([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone' => $phone,
                    'email' => $email ?: null,
                    'id_number' => $idNumber ?: null,
                    'occupation' => $occupation ?: null,
                    'address' => $address ?: null
                ]);
                $guardianId = $pdo->lastInsertId();
            }

            // If this is primary, unset other primaries
            if ($isPrimary) {
                $stmt = $pdo->prepare("UPDATE student_guardians SET is_primary = 0 WHERE student_id = :student_id");
                $stmt->execute(['student_id' => $id]);
            }

            // Link guardian to student
            $stmt = $pdo->prepare("
                INSERT INTO student_guardians (student_id, guardian_id, relationship, is_primary, can_pickup, created_at)
                VALUES (:student_id, :guardian_id, :relationship, :is_primary, :can_pickup, NOW())
                ON DUPLICATE KEY UPDATE relationship = :relationship2, is_primary = :is_primary2, can_pickup = :can_pickup2
            ");
            $stmt->execute([
                'student_id' => $id,
                'guardian_id' => $guardianId,
                'relationship' => $relationship ?: null,
                'is_primary' => $isPrimary,
                'can_pickup' => $canPickup,
                'relationship2' => $relationship ?: null,
                'is_primary2' => $isPrimary,
                'can_pickup2' => $canPickup
            ]);

            $this->logActivity($pdo, $id, 'guardian_added', "Guardian added: {$firstName} {$lastName}");

            return Response::json([
                'success' => true,
                'message' => 'Guardian added successfully',
                'guardian_id' => $guardianId
            ]);

        } catch (Exception $e) {
            error_log("StudentsController::addGuardian error: " . $e->getMessage());
            return Response::json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove guardian from student
     */
    public function removeGuardian($id, $guardianId)
    {
        if (!isAuthenticated()) {
            return Response::json(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        if (!hasPermission('Students.write') && !Gate::hasRole('ADMIN')) {
            return Response::json(['success' => false, 'message' => 'Permission denied'], 403);
        }

        try {
            $pdo = Database::getTenantConnection();

            $stmt = $pdo->prepare("DELETE FROM student_guardians WHERE student_id = :student_id AND guardian_id = :guardian_id");
            $stmt->execute(['student_id' => $id, 'guardian_id' => $guardianId]);

            $this->logActivity($pdo, $id, 'guardian_removed', 'Guardian removed');

            return Response::json(['success' => true, 'message' => 'Guardian removed']);

        } catch (Exception $e) {
            error_log("StudentsController::removeGuardian error: " . $e->getMessage());
            return Response::json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update guardian information
     */
    public function updateGuardian($id, $guardianId)
    {
        if (!isAuthenticated()) {
            return Response::json(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        if (!hasPermission('Students.write') && !Gate::hasRole('ADMIN')) {
            return Response::json(['success' => false, 'message' => 'Permission denied'], 403);
        }

        try {
            $pdo = Database::getTenantConnection();

            // Get old values for audit
            $stmt = $pdo->prepare("SELECT * FROM guardians WHERE id = :id");
            $stmt->execute(['id' => $guardianId]);
            $oldGuardian = $stmt->fetch();

            if (!$oldGuardian) {
                return Response::json(['success' => false, 'message' => 'Guardian not found'], 404);
            }

            // Get student_guardians record for audit trail entity
            $stmt = $pdo->prepare("SELECT id FROM student_guardians WHERE student_id = :student_id AND guardian_id = :guardian_id");
            $stmt->execute(['student_id' => $id, 'guardian_id' => $guardianId]);
            $studentGuardian = $stmt->fetch();
            $studentGuardianId = $studentGuardian ? $studentGuardian['id'] : $guardianId;

            // Parse field change reasons from JSON
            $fieldChangeReasons = json_decode(Request::post('field_change_reasons') ?: '{}', true);

            // Define audited fields
            $auditedFields = [
                'id_number' => ['label' => 'National ID / Passport', 'requiresReason' => true],
                'first_name' => ['label' => 'First Name', 'requiresReason' => false],
                'last_name' => ['label' => 'Last Name', 'requiresReason' => false],
                'phone' => ['label' => 'Phone Number', 'requiresReason' => false],
                'email' => ['label' => 'Email Address', 'requiresReason' => false],
            ];

            // Check which fields changed and validate required reasons
            $changedFields = [];
            $newValues = [
                'id_number' => Request::post('id_number'),
                'first_name' => Request::post('first_name'),
                'last_name' => Request::post('last_name'),
                'phone' => Request::post('phone'),
                'email' => Request::post('email'),
            ];

            foreach ($auditedFields as $fieldName => $config) {
                $oldValue = $oldGuardian[$fieldName] ?? '';
                $newValue = $newValues[$fieldName] ?? '';

                if ($oldValue !== $newValue) {
                    // Check if reason is required but not provided
                    if ($config['requiresReason'] && empty($fieldChangeReasons[$fieldName])) {
                        return Response::json([
                            'success' => false,
                            'message' => "Please provide a reason for changing the {$config['label']}"
                        ], 400);
                    }
                    $changedFields[$fieldName] = [
                        'old' => $oldValue,
                        'new' => $newValue,
                        'reason' => $fieldChangeReasons[$fieldName] ?? null,
                        'label' => $config['label']
                    ];
                }
            }

            // Validate ID number uniqueness if changed
            if (isset($changedFields['id_number'])) {
                $stmt = $pdo->prepare("SELECT id FROM guardians WHERE id_number = ? AND id != ?");
                $stmt->execute([Request::post('id_number'), $guardianId]);
                if ($stmt->fetch()) {
                    return Response::json([
                        'success' => false,
                        'message' => 'This ID number is already used by another guardian'
                    ], 400);
                }
            }

            $pdo->beginTransaction();

            // Log all field changes to field_audit_trail
            $userName = $_SESSION['full_name'] ?? 'Unknown';
            foreach ($changedFields as $fieldName => $change) {
                $stmt = $pdo->prepare("
                    INSERT INTO field_audit_trail (
                        entity_type, entity_id, field_name, old_value, new_value,
                        changed_by_user_id, changed_by_name, change_reason, ip_address, user_agent
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    'student_guardians',
                    $studentGuardianId,
                    $fieldName,
                    $change['old'],
                    $change['new'],
                    authUserId(),
                    $userName,
                    $change['reason'],
                    Request::ip(),
                    Request::userAgent()
                ]);
            }

            // Update guardian info
            $stmt = $pdo->prepare("
                UPDATE guardians SET
                    first_name = :first_name,
                    last_name = :last_name,
                    phone = :phone,
                    email = :email,
                    id_number = :id_number,
                    occupation = :occupation,
                    address = :address
                WHERE id = :id
            ");
            $stmt->execute([
                'id' => $guardianId,
                'first_name' => Request::post('first_name'),
                'last_name' => Request::post('last_name'),
                'phone' => Request::post('phone'),
                'email' => Request::post('email'),
                'id_number' => Request::post('id_number'),
                'occupation' => Request::post('occupation'),
                'address' => Request::post('address')
            ]);

            // Update student_guardians relationship fields
            $isPrimary = Request::post('is_primary') ? 1 : 0;
            $canPickup = Request::post('can_pickup') ? 1 : 0;

            $stmt = $pdo->prepare("
                UPDATE student_guardians SET
                    relationship = :relationship,
                    is_primary = :is_primary,
                    can_pickup = :can_pickup
                WHERE student_id = :student_id AND guardian_id = :guardian_id
            ");
            $stmt->execute([
                'student_id' => $id,
                'guardian_id' => $guardianId,
                'relationship' => Request::post('relationship'),
                'is_primary' => $isPrimary,
                'can_pickup' => $canPickup
            ]);

            // Build change description for general audit log
            $changeLabels = array_map(fn($c) => $c['label'], $changedFields);
            $changeDesc = !empty($changeLabels) ? 'Changed: ' . implode(', ', $changeLabels) : 'Guardian info updated';
            $this->logActivity($pdo, $id, 'guardian_updated', "Guardian #{$guardianId} updated. " . $changeDesc);

            $pdo->commit();

            return Response::json(['success' => true, 'message' => 'Guardian updated']);

        } catch (Exception $e) {
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            error_log("StudentsController::updateGuardian error: " . $e->getMessage());
            return Response::json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Toggle primary guardian status
     */
    public function togglePrimaryGuardian($id, $guardianId)
    {
        if (!isAuthenticated()) {
            return Response::json(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        if (!hasPermission('Students.write') && !Gate::hasRole('ADMIN')) {
            return Response::json(['success' => false, 'message' => 'Permission denied'], 403);
        }

        try {
            $pdo = Database::getTenantConnection();

            $isPrimary = Request::post('is_primary') ? 1 : 0;

            if ($isPrimary) {
                // First, unset all other primary guardians for this student
                $stmt = $pdo->prepare("UPDATE student_guardians SET is_primary = 0 WHERE student_id = :student_id");
                $stmt->execute(['student_id' => $id]);
            }

            // Set this guardian's primary status
            $stmt = $pdo->prepare("
                UPDATE student_guardians SET is_primary = :is_primary
                WHERE student_id = :student_id AND guardian_id = :guardian_id
            ");
            $stmt->execute([
                'student_id' => $id,
                'guardian_id' => $guardianId,
                'is_primary' => $isPrimary
            ]);

            $action = $isPrimary ? 'set as primary' : 'removed as primary';
            $this->logActivity($pdo, $id, 'guardian_primary_changed', "Guardian #{$guardianId} {$action}");

            return Response::json(['success' => true, 'message' => 'Primary status updated']);

        } catch (Exception $e) {
            error_log("StudentsController::togglePrimaryGuardian error: " . $e->getMessage());
            return Response::json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Log student activity
     */
    private function logActivity($pdo, $studentId, $action, $description)
    {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO student_audit_log (student_id, action, description, user_id, ip_address, created_at)
                VALUES (:student_id, :action, :description, :user_id, :ip_address, NOW())
            ");
            $stmt->execute([
                'student_id' => $studentId,
                'action' => $action,
                'description' => $description,
                'user_id' => $_SESSION['user_id'] ?? null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("Failed to log student activity: " . $e->getMessage());
        }
    }
}
