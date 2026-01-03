<?php
/**
 * Applicants Controller
 * Manages applicant lifecycle from application to admission
 */

class ApplicantsController
{
    /**
     * List all applicants with search and filters
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
            Gate::deny('You need permission to view applicants');
        }

        try {
            $pdo = Database::getTenantConnection();

            // Get search and filter parameters
            $search = Request::get('search', '');
            $status = Request::get('status', '');
            $grade = Request::get('grade', '');
            $page = max(1, (int)Request::get('page', 1));
            $perPage = 50;
            $offset = ($page - 1) * $perPage;

            // Build WHERE clause
            $where = ['1=1'];
            $params = [];

            // Filter by campus (unless viewing all campuses)
            $currentCampusId = $_SESSION['current_campus_id'] ?? null;
            if ($currentCampusId && $currentCampusId !== 'all') {
                $where[] = "a.campus_id = :campus_id";
                $params['campus_id'] = $currentCampusId;
            }

            if (!empty($search)) {
                $where[] = "(a.first_name LIKE :search OR a.last_name LIKE :search OR a.application_ref LIKE :search OR ac.phone LIKE :search OR ac.email LIKE :search)";
                $params['search'] = "%{$search}%";
            }

            if (!empty($status)) {
                $where[] = "a.status = :status";
                $params['status'] = $status;
            }

            if (!empty($grade)) {
                $where[] = "a.grade_applying_for_id = :grade";
                $params['grade'] = $grade;
            }

            $whereClause = implode(' AND ', $where);

            // Get total count
            $stmt = $pdo->prepare("
                SELECT COUNT(DISTINCT a.id) as total
                FROM applicants a
                LEFT JOIN applicant_contacts ac ON ac.applicant_id = a.id AND ac.is_primary = 1
                WHERE {$whereClause}
            ");
            $stmt->execute($params);
            $totalRecords = $stmt->fetchColumn();
            $totalPages = ceil($totalRecords / $perPage);

            // Get applicants
            $stmt = $pdo->prepare("
                SELECT
                    a.*,
                    g.grade_name,
                    ac.phone,
                    ac.email,
                    ay.year_name,
                    ic.campaign_name,
                    (SELECT COUNT(*) FROM applicant_guardians ag WHERE ag.applicant_id = a.id) as guardian_count
                FROM applicants a
                LEFT JOIN grades g ON g.id = a.grade_applying_for_id
                LEFT JOIN applicant_contacts ac ON ac.applicant_id = a.id AND ac.is_primary = 1
                LEFT JOIN academic_years ay ON ay.id = a.academic_year_id
                LEFT JOIN intake_campaigns ic ON ic.id = a.intake_campaign_id
                WHERE {$whereClause}
                ORDER BY a.created_at DESC
                LIMIT {$perPage} OFFSET {$offset}
            ");
            $stmt->execute($params);
            $applicants = $stmt->fetchAll();

            // Get grades for filter
            $stmt = $pdo->query("SELECT id, grade_name FROM grades WHERE is_active = 1 ORDER BY sort_order");
            $grades = $stmt->fetchAll();

            // Get status counts (campus-filtered)
            $statusCountSql = "SELECT status, COUNT(*) as count FROM applicants";
            if ($currentCampusId && $currentCampusId !== 'all') {
                $statusCountSql .= " WHERE campus_id = :campus_id";
            }
            $statusCountSql .= " GROUP BY status";

            $stmt = $pdo->prepare($statusCountSql);
            if ($currentCampusId && $currentCampusId !== 'all') {
                $stmt->execute(['campus_id' => $currentCampusId]);
            } else {
                $stmt->execute();
            }

            $statusCounts = [];
            while ($row = $stmt->fetch()) {
                $statusCounts[$row['status']] = $row['count'];
            }

            Response::view('applicants.index', [
                'applicants' => $applicants,
                'grades' => $grades,
                'statusCounts' => $statusCounts,
                'search' => $search,
                'statusFilter' => $status,
                'gradeFilter' => $grade,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalRecords' => $totalRecords,
                'perPage' => $perPage,
                'breadcrumbs' => [
                    ['label' => 'Students', 'url' => '#'],
                    ['label' => 'All Applicants', 'url' => '/applicants']
                ]
            ]);

        } catch (Exception $e) {
            logMessage("Applicants index error: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Show applicant details
     */
    public function show($id)
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();

            // Get current campus for filtering
            $currentCampusId = $_SESSION['current_campus_id'] ?? null;

            // Get applicant with related data
            $stmt = $pdo->prepare("
                SELECT
                    a.*,
                    g.grade_name,
                    g.grade_category,
                    ay.year_name,
                    ic.campaign_name,
                    u.full_name as created_by_name,
                    c.campus_name,
                    sp.school_name
                FROM applicants a
                LEFT JOIN grades g ON g.id = a.grade_applying_for_id
                LEFT JOIN academic_years ay ON ay.id = a.academic_year_id
                LEFT JOIN intake_campaigns ic ON ic.id = a.intake_campaign_id
                LEFT JOIN users u ON u.id = a.created_by
                LEFT JOIN campuses c ON c.id = a.campus_id
                LEFT JOIN school_profile sp ON sp.id = 1
                WHERE a.id = :id
                LIMIT 1
            ");
            $stmt->execute(['id' => $id]);
            $applicant = $stmt->fetch();

            if (!$applicant) {
                flash('error', 'Applicant not found');
                Response::redirect('/applicants');
            }

            // Verify campus access (unless admin viewing all campuses)
            if ($currentCampusId && $currentCampusId !== 'all') {
                if ($applicant['campus_id'] != $currentCampusId) {
                    flash('error', 'This applicant belongs to a different campus');
                    Response::redirect('/applicants');
                }
            }

            // Get contacts
            $stmt = $pdo->prepare("SELECT * FROM applicant_contacts WHERE applicant_id = :id");
            $stmt->execute(['id' => $id]);
            $contacts = $stmt->fetchAll();

            // Get guardians
            $stmt = $pdo->prepare("SELECT * FROM applicant_guardians WHERE applicant_id = :id ORDER BY is_primary DESC");
            $stmt->execute(['id' => $id]);
            $guardians = $stmt->fetchAll();

            // Get documents
            $stmt = $pdo->prepare("SELECT * FROM applicant_documents WHERE applicant_id = :id ORDER BY uploaded_at DESC");
            $stmt->execute(['id' => $id]);
            $documents = $stmt->fetchAll();

            // Get interviews
            $stmt = $pdo->prepare("SELECT * FROM applicant_interviews WHERE applicant_id = :id ORDER BY scheduled_at DESC");
            $stmt->execute(['id' => $id]);
            $interviews = $stmt->fetchAll();

            // Get exams
            $stmt = $pdo->prepare("SELECT * FROM applicant_exams WHERE applicant_id = :id ORDER BY scheduled_at DESC");
            $stmt->execute(['id' => $id]);
            $exams = $stmt->fetchAll();

            // Get decision
            $stmt = $pdo->prepare("SELECT * FROM applicant_decisions WHERE applicant_id = :id ORDER BY created_at DESC LIMIT 1");
            $stmt->execute(['id' => $id]);
            $decision = $stmt->fetch();

            // Get audit log
            $stmt = $pdo->prepare("
                SELECT aa.*, u.full_name as user_name
                FROM applicant_audit aa
                LEFT JOIN users u ON u.id = aa.user_id
                WHERE aa.applicant_id = :id
                ORDER BY aa.created_at DESC
                LIMIT 20
            ");
            $stmt->execute(['id' => $id]);
            $auditLog = $stmt->fetchAll();

            // Get siblings/family members
            $siblings = $this->getSiblings($pdo, $id);

            Response::view('applicants.show', [
                'applicant' => $applicant,
                'contacts' => $contacts,
                'guardians' => $guardians,
                'documents' => $documents,
                'interviews' => $interviews,
                'exams' => $exams,
                'decision' => $decision,
                'auditLog' => $auditLog,
                'siblings' => $siblings,
                'breadcrumbs' => [
                    ['label' => 'Students', 'url' => '#'],
                    ['label' => 'All Applicants', 'url' => '/applicants'],
                    ['label' => $applicant['application_ref'], 'url' => '/applicants/' . $applicant['id']]
                ]
            ]);

        } catch (Exception $e) {
            logMessage("Applicant show error: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Show create form
     */
    public function create()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        if (!hasPermission('Students.write') && !Gate::hasRole('ADMIN')) {
            Gate::deny('You need permission to create applicants');
        }

        try {
            $pdo = Database::getTenantConnection();

            // Get grades
            $stmt = $pdo->query("SELECT id, grade_name, grade_category FROM grades WHERE is_active = 1 ORDER BY sort_order");
            $grades = $stmt->fetchAll();

            // Get active intake campaigns with academic year
            $stmt = $pdo->query("
                SELECT
                    ic.id,
                    ic.campaign_name,
                    ay.year_name as year
                FROM intake_campaigns ic
                LEFT JOIN academic_years ay ON ic.academic_year_id = ay.id
                WHERE ic.status = 'open'
                ORDER BY ic.start_date DESC
            ");
            $campaigns = $stmt->fetchAll();

            // Get current academic year
            $stmt = $pdo->query("SELECT id, year_name FROM academic_years WHERE is_current = 1 LIMIT 1");
            $currentYear = $stmt->fetch();

            // Get countries for nationality/country dropdowns
            $stmt = $pdo->query("SELECT id, country_code, country_name FROM countries WHERE is_active = 1 ORDER BY sort_order, country_name");
            $countries = $stmt->fetchAll();

            Response::view('applicants.create', [
                'grades' => $grades,
                'campaigns' => $campaigns,
                'currentYear' => $currentYear,
                'countries' => $countries
            ]);

        } catch (Exception $e) {
            logMessage("Applicant create form error: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Store new applicant
     */
    public function store()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        if (!hasPermission('Students.write') && !Gate::hasRole('ADMIN')) {
            Gate::deny('You need permission to create applicants');
        }

        try {
            // Store old input for form repopulation on error
            storeOldInput($_POST);

            // Validate input
            $errors = [];

            // Required fields
            if (empty($_POST['first_name'])) {
                $errors['first_name'] = 'First name is required';
            }
            if (empty($_POST['last_name'])) {
                $errors['last_name'] = 'Last name is required';
            }
            if (empty($_POST['grade_applying_for_id'])) {
                $errors['grade_applying_for_id'] = 'Grade applying for is required';
            }

            // Date of birth validation
            if (!empty($_POST['date_of_birth'])) {
                $dob = strtotime($_POST['date_of_birth']);
                if ($dob === false) {
                    $errors['date_of_birth'] = 'Invalid date format';
                } elseif ($dob > time()) {
                    $errors['date_of_birth'] = 'Date of birth cannot be in the future';
                }
            }

            // Email validation
            if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email address';
            }

            // Guardian email validation
            if (!empty($_POST['guardian_email']) && !filter_var($_POST['guardian_email'], FILTER_VALIDATE_EMAIL)) {
                $errors['guardian_email'] = 'Invalid guardian email address';
            }

            // If there are errors, redirect back
            if (!empty($errors)) {
                storeErrors($errors);
                flash('error', 'Please fix the errors and try again');
                Response::redirect('/applicants/create');
            }

            $pdo = Database::getTenantConnection();

            // Get current academic year
            $stmt = $pdo->query("SELECT id FROM academic_years WHERE is_current = 1 LIMIT 1");
            $currentYear = $stmt->fetch();

            if (!$currentYear) {
                flash('error', 'No active academic year found. Please contact administrator.');
                Response::redirect('/applicants/create');
            }

            // Generate unique application reference
            $year = date('Y');
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM applicants WHERE YEAR(created_at) = {$year}");
            $count = $stmt->fetch()['count'];
            $applicationRef = 'APP-' . $year . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

            // Determine status based on action button clicked
            $action = $_POST['action'] ?? 'draft';
            $status = ($action === 'submit') ? 'submitted' : 'draft';
            $submittedAt = ($status === 'submitted') ? date('Y-m-d H:i:s') : null;
            $applicationDate = ($status === 'submitted') ? date('Y-m-d') : null;

            // Get current campus
            $currentCampusId = $_SESSION['current_campus_id'] ?? null;
            if (!$currentCampusId || $currentCampusId === 'all') {
                // If no campus selected or "all" selected, use the main campus
                $stmt = $pdo->query("SELECT id FROM campuses WHERE is_main = 1 LIMIT 1");
                $mainCampus = $stmt->fetch();
                $currentCampusId = $mainCampus['id'] ?? 1;
            }

            // Start transaction
            $pdo->beginTransaction();

            try {
                // Insert applicant
                $stmt = $pdo->prepare("
                    INSERT INTO applicants (
                        campus_id,
                        application_ref,
                        intake_campaign_id,
                        academic_year_id,
                        grade_applying_for_id,
                        first_name,
                        middle_name,
                        last_name,
                        date_of_birth,
                        gender,
                        nationality,
                        previous_school,
                        status,
                        application_date,
                        submitted_at,
                        created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $currentCampusId,
                    $applicationRef,
                    !empty($_POST['intake_campaign_id']) ? $_POST['intake_campaign_id'] : null,
                    $currentYear['id'],
                    $_POST['grade_applying_for_id'],
                    $_POST['first_name'],
                    $_POST['middle_name'] ?? null,
                    $_POST['last_name'],
                    $_POST['date_of_birth'] ?? null,
                    $_POST['gender'] ?? null,
                    $_POST['nationality'] ?? 'Kenya',
                    $_POST['previous_school'] ?? null,
                    $status,
                    $applicationDate,
                    $submittedAt,
                    authUserId()
                ]);

                $applicantId = $pdo->lastInsertId();

                // Insert contact information
                $stmt = $pdo->prepare("
                    INSERT INTO applicant_contacts (
                        applicant_id,
                        phone,
                        email,
                        address,
                        city,
                        country
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $applicantId,
                    $_POST['phone'] ?? null,
                    $_POST['email'] ?? null,
                    $_POST['address'] ?? null,
                    $_POST['city'] ?? null,
                    $_POST['country'] ?? 'Kenya'
                ]);

                // Insert guardian information (if provided)
                if (!empty($_POST['guardian_first_name']) || !empty($_POST['guardian_last_name'])) {
                    $stmt = $pdo->prepare("
                        INSERT INTO applicant_guardians (
                            applicant_id,
                            first_name,
                            last_name,
                            relationship,
                            phone,
                            email,
                            is_primary
                        ) VALUES (?, ?, ?, ?, ?, ?, 1)
                    ");

                    $stmt->execute([
                        $applicantId,
                        $_POST['guardian_first_name'] ?? '',
                        $_POST['guardian_last_name'] ?? '',
                        $_POST['guardian_relationship'] ?? null,
                        $_POST['guardian_phone'] ?? null,
                        $_POST['guardian_email'] ?? null
                    ]);
                }

                // Log the action in audit table
                $stmt = $pdo->prepare("
                    INSERT INTO applicant_audit (
                        applicant_id,
                        action,
                        description,
                        user_id,
                        ip_address,
                        user_agent
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $applicantId,
                    $status === 'submitted' ? 'application_submitted' : 'application_draft_created',
                    $status === 'submitted'
                        ? "Application submitted: {$applicationRef}"
                        : "Draft application created: {$applicationRef}",
                    authUserId(),
                    Request::ip(),
                    Request::userAgent()
                ]);

                // Commit transaction
                $pdo->commit();

                // Clear old input and errors
                clearOldInput();
                clearErrors();

                // Send SMS/Email acknowledgement if submitted
                if ($status === 'submitted' && !empty($_POST['phone'])) {
                    // TODO: Implement SMS sending in Phase 2
                    // sendSMS($_POST['phone'], "Your application {$applicationRef} has been received.");
                    logMessage("SMS acknowledgement queued for {$_POST['phone']}: {$applicationRef}", 'info');
                }

                if ($status === 'submitted' && !empty($_POST['email'])) {
                    // TODO: Implement Email sending in Phase 2
                    // sendEmail($_POST['email'], "Application Received", "Your application {$applicationRef} has been received.");
                    logMessage("Email acknowledgement queued for {$_POST['email']}: {$applicationRef}", 'info');
                }

                // Success message
                $message = $status === 'submitted'
                    ? "Application submitted successfully! Reference: {$applicationRef}"
                    : "Draft application saved successfully! Reference: {$applicationRef}";

                flash('success', $message);
                Response::redirect("/applicants/{$applicantId}");

            } catch (Exception $e) {
                // Rollback transaction on error
                $pdo->rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            logMessage("Applicant store error: " . $e->getMessage(), 'error');
            flash('error', 'Failed to save application. Please try again.');
            Response::redirect('/applicants/create');
        }
    }

    /**
     * Update applicant details (AJAX)
     */
    public function update()
    {
        if (!isAuthenticated()) {
            Response::json(['success' => false, 'message' => 'Not authenticated']);
        }

        if (!hasPermission('Students.write') && !Gate::hasRole('ADMIN')) {
            Response::json(['success' => false, 'message' => 'Permission denied']);
        }

        try {
            $applicantId = $_POST['applicant_id'] ?? null;
            if (!$applicantId) {
                Response::json(['success' => false, 'message' => 'Applicant ID is required']);
            }

            // Validate required fields
            if (empty($_POST['first_name']) || empty($_POST['last_name'])) {
                Response::json(['success' => false, 'message' => 'First name and last name are required']);
            }

            $pdo = Database::getTenantConnection();

            // Verify applicant exists and get current data for audit
            $stmt = $pdo->prepare("SELECT * FROM applicants WHERE id = ?");
            $stmt->execute([$applicantId]);
            $currentApplicant = $stmt->fetch();

            if (!$currentApplicant) {
                Response::json(['success' => false, 'message' => 'Applicant not found']);
            }

            // Check campus access
            $currentCampusId = $_SESSION['current_campus_id'] ?? null;
            if ($currentCampusId && $currentCampusId !== 'all') {
                if ($currentApplicant['campus_id'] != $currentCampusId) {
                    Response::json(['success' => false, 'message' => 'Access denied to this applicant']);
                }
            }

            // Build update query with only changed fields
            $updateFields = [];
            $params = [];
            $changedFields = [];

            $fields = [
                'first_name', 'middle_name', 'last_name', 'date_of_birth',
                'gender', 'nationality', 'birth_cert_no',
                'previous_school', 'previous_grade', 'grade_applying_for_id',
                'intake_campaign_id', 'medical_conditions', 'special_needs', 'notes'
            ];

            foreach ($fields as $field) {
                $newValue = $_POST[$field] ?? null;
                $oldValue = $currentApplicant[$field] ?? null;

                // Normalize empty strings to null for comparison
                if ($newValue === '') $newValue = null;
                if ($oldValue === '') $oldValue = null;

                if ($newValue !== $oldValue) {
                    $updateFields[] = "{$field} = ?";
                    $params[] = $newValue;
                    $changedFields[$field] = [
                        'old' => $oldValue,
                        'new' => $newValue
                    ];
                }
            }

            if (empty($updateFields)) {
                Response::json(['success' => true, 'message' => 'No changes detected']);
            }

            // Add updated_at timestamp
            $updateFields[] = "updated_at = NOW()";
            $params[] = $applicantId;

            // Start transaction
            $pdo->beginTransaction();

            try {
                // Update applicant
                $sql = "UPDATE applicants SET " . implode(', ', $updateFields) . " WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);

                // Log audit entries for each changed field
                $auditStmt = $pdo->prepare("
                    INSERT INTO applicant_audit (
                        applicant_id, action, field_name, old_value, new_value,
                        description, user_id, ip_address, user_agent
                    ) VALUES (?, 'updated', ?, ?, ?, ?, ?, ?, ?)
                ");

                foreach ($changedFields as $fieldName => $values) {
                    $description = "Updated " . ucwords(str_replace('_', ' ', $fieldName));
                    $auditStmt->execute([
                        $applicantId,
                        $fieldName,
                        $values['old'],
                        $values['new'],
                        $description,
                        authUserId(),
                        Request::ip(),
                        Request::userAgent()
                    ]);
                }

                $pdo->commit();

                Response::json([
                    'success' => true,
                    'message' => 'Applicant details updated successfully',
                    'changed_fields' => array_keys($changedFields)
                ]);

            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            logMessage("Applicant update error: " . $e->getMessage(), 'error');
            Response::json(['success' => false, 'message' => 'Failed to update applicant details']);
        }
    }

    /**
     * Screening queue - Applications pending review
     */
    public function screening()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        if (!hasPermission('Students.view') && !Gate::hasRole('ADMIN')) {
            Gate::deny('You need permission to view screening queue');
        }

        try {
            $pdo = Database::getTenantConnection();

            // Get current campus for filtering
            $currentCampusId = $_SESSION['current_campus_id'] ?? null;

            // Get filter parameters
            $filterGrade = Request::get('grade', '');
            $filterCampaign = Request::get('campaign', '');
            $filterStatus = Request::get('status', '');

            // Build query - include all workflow stages
            $sql = "
                SELECT
                    a.*,
                    g.grade_name,
                    ic.campaign_name,
                    ac.phone,
                    ac.email
                FROM applicants a
                LEFT JOIN grades g ON a.grade_applying_for_id = g.id
                LEFT JOIN intake_campaigns ic ON a.intake_campaign_id = ic.id
                LEFT JOIN applicant_contacts ac ON a.id = ac.applicant_id AND ac.is_primary = 1
                WHERE a.status IN ('submitted', 'screening', 'interview_scheduled', 'interviewed',
                                  'exam_scheduled', 'exam_taken', 'accepted', 'waitlisted', 'rejected')
            ";

            $params = [];

            // Filter by campus (unless viewing all campuses)
            if ($currentCampusId && $currentCampusId !== 'all') {
                $sql .= " AND a.campus_id = ?";
                $params[] = $currentCampusId;
            }

            if (!empty($filterGrade)) {
                $sql .= " AND a.grade_applying_for_id = ?";
                $params[] = $filterGrade;
            }

            if (!empty($filterCampaign)) {
                $sql .= " AND a.intake_campaign_id = ?";
                $params[] = $filterCampaign;
            }

            if (!empty($filterStatus)) {
                $sql .= " AND a.status = ?";
                $params[] = $filterStatus;
            }

            $sql .= " ORDER BY a.submitted_at ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $applicants = $stmt->fetchAll();

            // Get grades for filter
            $stmt = $pdo->query("SELECT id, grade_name FROM grades WHERE is_active = 1 ORDER BY sort_order");
            $grades = $stmt->fetchAll();

            // Get campaigns for filter
            $stmt = $pdo->query("SELECT id, campaign_name FROM intake_campaigns WHERE status = 'open' ORDER BY start_date DESC");
            $campaigns = $stmt->fetchAll();

            Response::view('applicants.screening', [
                'applicants' => $applicants,
                'grades' => $grades,
                'campaigns' => $campaigns,
                'filterGrade' => $filterGrade,
                'filterCampaign' => $filterCampaign,
                'filterStatus' => $filterStatus
            ]);

        } catch (Exception $e) {
            logMessage("Screening queue error: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Record decision (accept/reject/waitlist)
     */
    public function decision()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        if (!hasPermission('Students.write') && !Gate::hasRole('ADMIN')) {
            Gate::deny('You need permission to make decisions on applications');
        }

        if (!Request::isPost()) {
            Response::redirect('/applicants');
        }

        $applicantId = $_POST['applicant_id'] ?? null;

        try {
            $decision = $_POST['decision'] ?? null;
            $offerExpiryDate = $_POST['offer_expiry_date'] ?? null;
            $conditions = $_POST['conditions'] ?? null;
            // Support both 'reason' (from unified modal) and 'rejection_reason' (legacy)
            $reason = $_POST['reason'] ?? $_POST['rejection_reason'] ?? null;
            $notes = $_POST['notes'] ?? null;

            // Validation
            if (empty($applicantId) || empty($decision)) {
                flash('error', 'Invalid request');
                Response::redirect('/applicants');
            }

            if (!in_array($decision, ['accepted', 'waitlisted', 'rejected', 'withdrawn'])) {
                flash('error', 'Invalid decision type');
                Response::redirect("/applicants/{$applicantId}");
            }

            // Require reason for rejection
            if ($decision === 'rejected' && empty($reason)) {
                flash('error', 'Rejection reason is required');
                Response::redirect("/applicants/{$applicantId}");
            }

            $pdo = Database::getTenantConnection();

            // Start transaction
            $pdo->beginTransaction();

            try {
                // Insert decision record (skip for withdrawn - it's not really a "decision")
                if ($decision !== 'withdrawn') {
                    $stmt = $pdo->prepare("
                        INSERT INTO applicant_decisions (
                            applicant_id,
                            decision,
                            decision_date,
                            decided_by,
                            conditions,
                            offer_expiry_date,
                            rejection_reason,
                            notes
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                    $stmt->execute([
                        $applicantId,
                        $decision,
                        date('Y-m-d'),
                        authUserId(),
                        $conditions,
                        $offerExpiryDate,
                        $decision === 'rejected' ? $reason : null,
                        $decision === 'rejected' ? $notes : $reason // For accept/waitlist, reason goes to notes
                    ]);
                }

                // Update applicant status
                $newStatus = $decision; // accepted, waitlisted, rejected, or withdrawn
                $stmt = $pdo->prepare("UPDATE applicants SET status = ? WHERE id = ?");
                $stmt->execute([$newStatus, $applicantId]);

                // Build audit description
                $auditDescriptions = [
                    'accepted' => 'Application accepted' . ($reason ? ": {$reason}" : ''),
                    'waitlisted' => 'Application waitlisted: ' . ($reason ?: 'No reason provided'),
                    'rejected' => 'Application rejected: ' . ($reason ?: 'No reason provided'),
                    'withdrawn' => 'Application withdrawn' . ($reason ? ": {$reason}" : '')
                ];

                // Log audit trail
                $stmt = $pdo->prepare("
                    INSERT INTO applicant_audit (
                        applicant_id,
                        action,
                        description,
                        user_id,
                        ip_address,
                        user_agent
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $applicantId,
                    $decision === 'withdrawn' ? 'withdrawn' : 'decision_made',
                    $auditDescriptions[$decision],
                    authUserId(),
                    Request::ip(),
                    Request::userAgent()
                ]);

                // Commit transaction
                $pdo->commit();

                // TODO: Queue SMS/Email notification (Phase 2D)
                logMessage("Decision notification queued for applicant {$applicantId}: {$decision}", 'info');

                // Success message
                $messages = [
                    'accepted' => 'Application accepted! Applicant can now proceed to pre-admission.',
                    'waitlisted' => 'Application waitlisted. Applicant will be notified.',
                    'rejected' => 'Application rejected. Applicant will be notified.',
                    'withdrawn' => 'Application has been withdrawn.'
                ];

                flash('success', $messages[$decision]);
                Response::redirect("/applicants/{$applicantId}");

            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            logMessage("Decision error: " . $e->getMessage(), 'error');
            flash('error', 'Failed to record decision: ' . $e->getMessage());
            Response::redirect("/applicants/{$applicantId}");
        }
    }

    /**
     * Schedule interview for applicant
     */
    public function scheduleInterview()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        if (!hasPermission('Students.write') && !Gate::hasRole('ADMIN')) {
            Gate::deny('You need permission to schedule interviews');
        }

        if (!Request::isPost()) {
            Response::redirect('/applicants/screening');
        }

        try {
            $applicantId = $_POST['applicant_id'] ?? null;
            $interviewDate = $_POST['interview_date'] ?? null;
            $interviewTime = $_POST['interview_time'] ?? null;
            $duration = $_POST['duration_minutes'] ?? 30;
            $location = $_POST['location'] ?? null;
            $panelMembers = $_POST['panel_members'] ?? null;
            $notes = $_POST['notes'] ?? null;
            $sendSMS = isset($_POST['send_sms']);
            $sendEmail = isset($_POST['send_email']);

            // Validation
            if (empty($applicantId) || empty($interviewDate) || empty($interviewTime)) {
                flash('error', 'Applicant ID, date and time are required');
                Response::back();
            }

            $pdo = Database::getTenantConnection();

            // Get campus from applicant
            $stmt = $pdo->prepare("SELECT campus_id FROM applicants WHERE id = ?");
            $stmt->execute([$applicantId]);
            $applicant = $stmt->fetch();

            if (!$applicant) {
                flash('error', 'Applicant not found');
                Response::back();
            }

            $campusId = $applicant['campus_id'];

            // Combine date and time
            $scheduledAt = $interviewDate . ' ' . $interviewTime;

            // Start transaction
            $pdo->beginTransaction();

            try {
                // Insert interview record
                $stmt = $pdo->prepare("
                    INSERT INTO applicant_interviews (
                        applicant_id,
                        campus_id,
                        scheduled_at,
                        duration_minutes,
                        location,
                        panel_members,
                        notes,
                        created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $applicantId,
                    $campusId,
                    $scheduledAt,
                    $duration,
                    $location,
                    $panelMembers,
                    $notes,
                    authUserId()
                ]);

                // Update applicant status
                $stmt = $pdo->prepare("UPDATE applicants SET status = 'interview_scheduled' WHERE id = ?");
                $stmt->execute([$applicantId]);

                // Log audit trail
                $stmt = $pdo->prepare("
                    INSERT INTO applicant_audit (
                        applicant_id,
                        action,
                        description,
                        user_id,
                        ip_address,
                        user_agent
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $applicantId,
                    'interview_scheduled',
                    "Interview scheduled for {$scheduledAt} at {$location}",
                    authUserId(),
                    Request::ip(),
                    Request::userAgent()
                ]);

                $pdo->commit();

                // TODO: Send notifications
                if ($sendSMS || $sendEmail) {
                    logMessage("Interview notification queued for applicant {$applicantId}", 'info');
                }

                flash('success', 'Interview scheduled successfully. Applicant will be notified.');
                Response::redirect("/applicants/{$applicantId}");

            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            logMessage("Schedule interview error: " . $e->getMessage(), 'error');
            flash('error', 'Failed to schedule interview. Please try again.');
            Response::back();
        }
    }

    /**
     * Record interview outcome
     */
    public function interviewOutcome()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        if (!hasPermission('Students.write') && !Gate::hasRole('ADMIN')) {
            Gate::deny('You need permission to record interview outcomes');
        }

        if (!Request::isPost()) {
            Response::redirect('/applicants/screening');
        }

        try {
            $applicantId = $_POST['applicant_id'] ?? null;
            $interviewId = $_POST['interview_id'] ?? null;
            $attended = $_POST['attended'] ?? 'yes';
            $outcome = $_POST['outcome'] ?? null;
            $rating = $_POST['rating'] ?? null;
            $notes = $_POST['notes'] ?? null;

            // Validation
            if (empty($applicantId) || empty($interviewId)) {
                flash('error', 'Invalid request');
                Response::back();
            }

            $pdo = Database::getTenantConnection();

            // Start transaction
            $pdo->beginTransaction();

            try {
                // Update interview record
                // Map attended to status
                $status = ($attended === 'yes') ? 'completed' : 'no_show';

                $stmt = $pdo->prepare("
                    UPDATE applicant_interviews
                    SET status = ?,
                        outcome = ?,
                        score = ?,
                        notes = ?
                    WHERE id = ? AND applicant_id = ?
                ");

                $stmt->execute([
                    $status,
                    $outcome,
                    $rating,
                    $notes,
                    $interviewId,
                    $applicantId
                ]);

                // Update applicant status
                $stmt = $pdo->prepare("UPDATE applicants SET status = 'interviewed' WHERE id = ?");
                $stmt->execute([$applicantId]);

                // Log audit trail
                $stmt = $pdo->prepare("
                    INSERT INTO applicant_audit (
                        applicant_id,
                        action,
                        description,
                        user_id,
                        ip_address,
                        user_agent
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $applicantId,
                    'interview_outcome_recorded',
                    "Interview outcome: {$outcome} (Attended: {$attended})",
                    authUserId(),
                    Request::ip(),
                    Request::userAgent()
                ]);

                $pdo->commit();

                flash('success', 'Interview outcome recorded successfully.');
                Response::redirect("/applicants/{$applicantId}");

            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            logMessage("Interview outcome error: " . $e->getMessage(), 'error');
            flash('error', 'Failed to record interview outcome. Please try again.');
            Response::back();
        }
    }

    /**
     * Schedule exam for applicant
     */
    public function scheduleExam()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        if (!hasPermission('Students.write') && !Gate::hasRole('ADMIN')) {
            Gate::deny('You need permission to schedule exams');
        }

        if (!Request::isPost()) {
            Response::redirect('/applicants/screening');
        }

        try {
            $applicantId = $_POST['applicant_id'] ?? null;
            $examType = $_POST['exam_type'] ?? null;
            $examDate = $_POST['exam_date'] ?? null;
            $examTime = $_POST['exam_time'] ?? null;
            $duration = $_POST['duration_minutes'] ?? 60;
            $location = $_POST['location'] ?? null;
            $subjects = $_POST['subjects'] ?? null;
            $notes = $_POST['notes'] ?? null;
            $sendSMS = isset($_POST['send_sms']);
            $sendEmail = isset($_POST['send_email']);

            // Validation
            if (empty($applicantId) || empty($examType) || empty($examDate) || empty($examTime)) {
                flash('error', 'Applicant ID, exam type, date and time are required');
                Response::back();
            }

            $pdo = Database::getTenantConnection();

            // Get campus from applicant
            $stmt = $pdo->prepare("SELECT campus_id FROM applicants WHERE id = ?");
            $stmt->execute([$applicantId]);
            $applicant = $stmt->fetch();

            if (!$applicant) {
                flash('error', 'Applicant not found');
                Response::back();
            }

            $campusId = $applicant['campus_id'];

            // Combine date and time
            $scheduledAt = $examDate . ' ' . $examTime;

            // Start transaction
            $pdo->beginTransaction();

            try {
                // Insert exam record
                $stmt = $pdo->prepare("
                    INSERT INTO applicant_exams (
                        applicant_id,
                        campus_id,
                        exam_name,
                        scheduled_at,
                        exam_center,
                        notes,
                        created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $applicantId,
                    $campusId,
                    $examType,
                    $scheduledAt,
                    $location,
                    $notes,
                    authUserId()
                ]);

                // Update applicant status
                $stmt = $pdo->prepare("UPDATE applicants SET status = 'exam_scheduled' WHERE id = ?");
                $stmt->execute([$applicantId]);

                // Log audit trail
                $stmt = $pdo->prepare("
                    INSERT INTO applicant_audit (
                        applicant_id,
                        action,
                        description,
                        user_id,
                        ip_address,
                        user_agent
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $applicantId,
                    'exam_scheduled',
                    "{$examType} exam scheduled for {$scheduledAt} at {$location}",
                    authUserId(),
                    Request::ip(),
                    Request::userAgent()
                ]);

                $pdo->commit();

                // TODO: Send notifications
                if ($sendSMS || $sendEmail) {
                    logMessage("Exam notification queued for applicant {$applicantId}", 'info');
                }

                flash('success', 'Exam scheduled successfully. Applicant will be notified.');
                Response::redirect("/applicants/{$applicantId}");

            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            logMessage("Schedule exam error: " . $e->getMessage(), 'error');
            flash('error', 'Failed to schedule exam. Please try again.');
            Response::back();
        }
    }

    /**
     * Record exam score
     */
    public function examScore()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        if (!hasPermission('Students.write') && !Gate::hasRole('ADMIN')) {
            Gate::deny('You need permission to record exam scores');
        }

        if (!Request::isPost()) {
            Response::redirect('/applicants/screening');
        }

        try {
            $applicantId = $_POST['applicant_id'] ?? null;
            $examId = $_POST['exam_id'] ?? null;
            $attended = $_POST['attended'] ?? 'yes';
            $scoreObtained = $_POST['score_obtained'] ?? null;
            $maxScore = $_POST['max_score'] ?? 100;
            $grade = $_POST['grade'] ?? null;
            $notes = $_POST['notes'] ?? null;

            // Validation
            if (empty($applicantId) || empty($examId)) {
                flash('error', 'Invalid request');
                Response::back();
            }

            if ($attended === 'yes' && empty($scoreObtained)) {
                flash('error', 'Score is required when applicant attended');
                Response::back();
            }

            $pdo = Database::getTenantConnection();

            // Calculate percentage if score provided
            $percentage = null;
            if ($scoreObtained !== null && $maxScore > 0) {
                $percentage = ($scoreObtained / $maxScore) * 100;
            }

            // Start transaction
            $pdo->beginTransaction();

            try {
                // Update exam record
                // Map attended to status
                $examStatus = ($attended === 'yes') ? 'taken' : 'absent';

                $stmt = $pdo->prepare("
                    UPDATE applicant_exams
                    SET status = ?,
                        score = ?,
                        total_marks = ?,
                        percentage = ?,
                        grade = ?,
                        notes = ?
                    WHERE id = ? AND applicant_id = ?
                ");

                $stmt->execute([
                    $examStatus,
                    $scoreObtained,
                    $maxScore,
                    $percentage,
                    $grade,
                    $notes,
                    $examId,
                    $applicantId
                ]);

                // Update applicant status
                $stmt = $pdo->prepare("UPDATE applicants SET status = 'exam_taken' WHERE id = ?");
                $stmt->execute([$applicantId]);

                // Log audit trail
                $stmt = $pdo->prepare("
                    INSERT INTO applicant_audit (
                        applicant_id,
                        action,
                        description,
                        user_id,
                        ip_address,
                        user_agent
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ");

                $description = $attended === 'yes'
                    ? "Exam score recorded: {$scoreObtained}/{$maxScore} ({$percentage}%)"
                    : "Exam marked as not attended";

                $stmt->execute([
                    $applicantId,
                    'exam_score_recorded',
                    $description,
                    authUserId(),
                    Request::ip(),
                    Request::userAgent()
                ]);

                $pdo->commit();

                flash('success', 'Exam score recorded successfully.');
                Response::redirect("/applicants/{$applicantId}");

            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            logMessage("Exam score error: " . $e->getMessage(), 'error');
            flash('error', 'Failed to record exam score. Please try again.');
            Response::back();
        }
    }

    /**
     * Simple stage transition (for statuses without additional data)
     */
    public function stageTransition()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        if (!hasPermission('Students.write') && !Gate::hasRole('ADMIN')) {
            Gate::deny('You need permission to update applicant status');
        }

        if (!Request::isPost()) {
            Response::redirect('/applicants');
        }

        $applicantId = $_POST['applicant_id'] ?? null;

        try {
            $newStatus = $_POST['new_status'] ?? null;
            $notes = trim($_POST['notes'] ?? '');

            // Validation
            if (empty($applicantId) || empty($newStatus)) {
                flash('error', 'Invalid request');
                Response::redirect('/applicants');
            }

            // Define valid flexible stages (can be moved to without special handling)
            $flexibleStatuses = [
                'draft', 'submitted', 'screening',
                'interviewed', 'exam_taken'
            ];

            // Strict stages that require special processing (cannot be set directly)
            $strictStatuses = ['pre_admission', 'admitted'];

            if (in_array($newStatus, $strictStatuses)) {
                flash('error', 'This status change requires special processing');
                Response::redirect("/applicants/{$applicantId}");
            }

            // Stages that need modals (interview/exam scheduling, decisions)
            $modalStatuses = ['interview_scheduled', 'exam_scheduled', 'accepted', 'waitlisted', 'rejected', 'withdrawn'];

            if (!in_array($newStatus, $flexibleStatuses) && !in_array($newStatus, $modalStatuses)) {
                flash('error', 'Invalid status transition');
                Response::redirect("/applicants/{$applicantId}");
            }

            $pdo = Database::getTenantConnection();

            // Get current applicant status
            $stmt = $pdo->prepare("SELECT status FROM applicants WHERE id = ?");
            $stmt->execute([$applicantId]);
            $currentApplicant = $stmt->fetch();

            if (!$currentApplicant) {
                flash('error', 'Applicant not found');
                Response::redirect('/applicants');
            }

            $oldStatus = $currentApplicant['status'];

            // Prevent moving from strict stages
            if (in_array($oldStatus, $strictStatuses)) {
                flash('error', 'Cannot change status from ' . ucfirst(str_replace('_', ' ', $oldStatus)));
                Response::redirect("/applicants/{$applicantId}");
            }

            // Start transaction
            $pdo->beginTransaction();

            try {
                // Update applicant status
                $stmt = $pdo->prepare("UPDATE applicants SET status = ? WHERE id = ?");
                $stmt->execute([$newStatus, $applicantId]);

                // Build description with notes if provided
                $description = "Status changed from " . ucfirst(str_replace('_', ' ', $oldStatus)) .
                              " to " . ucfirst(str_replace('_', ' ', $newStatus));
                if (!empty($notes)) {
                    $description .= ". Notes: " . $notes;
                }

                // Log audit trail
                $stmt = $pdo->prepare("
                    INSERT INTO applicant_audit (
                        applicant_id,
                        action,
                        description,
                        old_value,
                        new_value,
                        user_id,
                        ip_address,
                        user_agent
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $applicantId,
                    'stage_transition',
                    $description,
                    $oldStatus,
                    $newStatus,
                    authUserId(),
                    Request::ip(),
                    Request::userAgent()
                ]);

                $pdo->commit();

                $statusLabels = [
                    'draft' => 'Draft',
                    'submitted' => 'Submitted',
                    'screening' => 'Screening',
                    'interview_scheduled' => 'Interview Scheduled',
                    'interviewed' => 'Interviewed',
                    'exam_scheduled' => 'Exam Scheduled',
                    'exam_taken' => 'Exam Taken',
                    'accepted' => 'Accepted',
                    'waitlisted' => 'Waitlisted',
                    'rejected' => 'Rejected',
                    'withdrawn' => 'Withdrawn'
                ];

                $label = $statusLabels[$newStatus] ?? ucfirst(str_replace('_', ' ', $newStatus));
                flash('success', "Applicant moved to {$label} stage.");
                Response::redirect("/applicants/{$applicantId}");

            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            logMessage("Stage transition error: " . $e->getMessage(), 'error');
            flash('error', 'Failed to update status: ' . $e->getMessage());
            Response::redirect("/applicants/{$applicantId}");
        }
    }

    /**
     * Store new guardian for an applicant
     */
    public function storeGuardian()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        if (!hasPermission('Students.write') && !Gate::hasRole('ADMIN')) {
            Gate::deny('You need permission to add guardians');
        }

        try {
            $pdo = Database::getTenantConnection();

            $applicantId = Request::get('applicant_id');
            $firstName = Request::get('first_name');
            $lastName = Request::get('last_name');
            $relationship = Request::get('relationship');
            $phone = Request::get('phone');
            $email = Request::get('email');
            $idNumber = Request::get('id_number');
            $occupation = Request::get('occupation');
            $employer = Request::get('employer');
            $address = Request::get('address');
            $isPrimary = Request::get('is_primary') ? 1 : 0;

            // Validation
            if (empty($applicantId) || empty($firstName) || empty($lastName) || empty($relationship) || empty($phone)) {
                flash('error', 'Please fill in all required fields');
                Response::back();
            }

            // Verify applicant exists and belongs to current campus
            $currentCampusId = $_SESSION['current_campus_id'] ?? null;
            $stmt = $pdo->prepare("SELECT campus_id FROM applicants WHERE id = ?");
            $stmt->execute([$applicantId]);
            $applicant = $stmt->fetch();

            if (!$applicant) {
                flash('error', 'Applicant not found');
                Response::redirect('/applicants');
            }

            // Verify campus access
            if ($currentCampusId && $currentCampusId !== 'all') {
                if ($applicant['campus_id'] != $currentCampusId) {
                    flash('error', 'This applicant belongs to a different campus');
                    Response::redirect('/applicants');
                }
            }

            $pdo->beginTransaction();

            // If setting as primary, unset all other primary guardians first
            if ($isPrimary) {
                $stmt = $pdo->prepare("UPDATE applicant_guardians SET is_primary = 0 WHERE applicant_id = ?");
                $stmt->execute([$applicantId]);
            }

            // Insert guardian
            $stmt = $pdo->prepare("
                INSERT INTO applicant_guardians (
                    applicant_id, first_name, last_name, relationship, phone, email,
                    id_number, occupation, employer, address, is_primary, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $applicantId, $firstName, $lastName, $relationship, $phone, $email,
                $idNumber, $occupation, $employer, $address, $isPrimary
            ]);

            $guardianId = $pdo->lastInsertId();

            // Audit log
            $stmt = $pdo->prepare("
                INSERT INTO applicant_audit (
                    applicant_id, action, description, user_id, ip_address, user_agent, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $applicantId,
                'guardian_added',
                "Guardian added: {$firstName} {$lastName} ({$relationship})",
                authUserId(),
                Request::ip(),
                Request::userAgent()
            ]);

            $pdo->commit();

            flash('success', 'Guardian added successfully');
            Response::redirect('/applicants/' . $applicantId . '#tab-guardians');

        } catch (Exception $e) {
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            logMessage("Guardian store error: " . $e->getMessage(), 'error');
            flash('error', 'Failed to add guardian. Please try again.');
            Response::back();
        }
    }

    /**
     * Update guardian information
     */
    public function updateGuardian()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        if (!hasPermission('Students.write') && !Gate::hasRole('ADMIN')) {
            Gate::deny('You need permission to update guardians');
        }

        try {
            $pdo = Database::getTenantConnection();

            $guardianId = Request::get('guardian_id');
            $applicantId = Request::get('applicant_id');
            $firstName = Request::get('first_name');
            $lastName = Request::get('last_name');
            $relationship = Request::get('relationship');
            $phone = Request::get('phone');
            $email = Request::get('email');
            $idNumber = Request::get('id_number');
            $occupation = Request::get('occupation');
            $employer = Request::get('employer');
            $address = Request::get('address');
            $isPrimary = Request::get('is_primary') ? 1 : 0;

            // Validation
            if (empty($guardianId) || empty($applicantId) || empty($firstName) || empty($lastName) || empty($relationship) || empty($phone)) {
                flash('error', 'Please fill in all required fields');
                Response::back();
            }

            // Verify guardian exists and belongs to applicant
            $stmt = $pdo->prepare("
                SELECT ag.*, a.campus_id
                FROM applicant_guardians ag
                JOIN applicants a ON ag.applicant_id = a.id
                WHERE ag.id = ? AND ag.applicant_id = ?
            ");
            $stmt->execute([$guardianId, $applicantId]);
            $guardian = $stmt->fetch();

            if (!$guardian) {
                flash('error', 'Guardian not found');
                Response::redirect('/applicants');
            }

            // Verify campus access
            $currentCampusId = $_SESSION['current_campus_id'] ?? null;
            if ($currentCampusId && $currentCampusId !== 'all') {
                if ($guardian['campus_id'] != $currentCampusId) {
                    flash('error', 'This guardian belongs to an applicant from a different campus');
                    Response::redirect('/applicants');
                }
            }

            $pdo->beginTransaction();

            // If setting as primary, unset all other primary guardians first
            if ($isPrimary) {
                $stmt = $pdo->prepare("UPDATE applicant_guardians SET is_primary = 0 WHERE applicant_id = ? AND id != ?");
                $stmt->execute([$applicantId, $guardianId]);
            }

            // Update guardian
            $stmt = $pdo->prepare("
                UPDATE applicant_guardians
                SET first_name = ?, last_name = ?, relationship = ?, phone = ?, email = ?,
                    id_number = ?, occupation = ?, employer = ?, address = ?, is_primary = ?,
                    updated_at = NOW()
                WHERE id = ? AND applicant_id = ?
            ");

            $stmt->execute([
                $firstName, $lastName, $relationship, $phone, $email,
                $idNumber, $occupation, $employer, $address, $isPrimary,
                $guardianId, $applicantId
            ]);

            // Audit log
            $stmt = $pdo->prepare("
                INSERT INTO applicant_audit (
                    applicant_id, action, description, user_id, ip_address, user_agent, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $applicantId,
                'guardian_updated',
                "Guardian updated: {$firstName} {$lastName} ({$relationship})",
                authUserId(),
                Request::ip(),
                Request::userAgent()
            ]);

            $pdo->commit();

            flash('success', 'Guardian updated successfully');
            Response::redirect('/applicants/' . $applicantId . '#tab-guardians');

        } catch (Exception $e) {
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            logMessage("Guardian update error: " . $e->getMessage(), 'error');
            flash('error', 'Failed to update guardian. Please try again.');
            Response::back();
        }
    }

    /**
     * Set a guardian as primary
     */
    public function setPrimaryGuardian()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        if (!hasPermission('Students.write') && !Gate::hasRole('ADMIN')) {
            Gate::deny('You need permission to modify guardians');
        }

        try {
            $pdo = Database::getTenantConnection();

            $guardianId = Request::get('guardian_id');
            $applicantId = Request::get('applicant_id');

            // Validation
            if (empty($guardianId) || empty($applicantId)) {
                flash('error', 'Invalid request');
                Response::back();
            }

            // Verify guardian exists and belongs to applicant
            $stmt = $pdo->prepare("
                SELECT ag.*, a.campus_id
                FROM applicant_guardians ag
                JOIN applicants a ON ag.applicant_id = a.id
                WHERE ag.id = ? AND ag.applicant_id = ?
            ");
            $stmt->execute([$guardianId, $applicantId]);
            $guardian = $stmt->fetch();

            if (!$guardian) {
                flash('error', 'Guardian not found');
                Response::redirect('/applicants');
            }

            // Verify campus access
            $currentCampusId = $_SESSION['current_campus_id'] ?? null;
            if ($currentCampusId && $currentCampusId !== 'all') {
                if ($guardian['campus_id'] != $currentCampusId) {
                    flash('error', 'This guardian belongs to an applicant from a different campus');
                    Response::redirect('/applicants');
                }
            }

            $pdo->beginTransaction();

            // Unset all primary guardians for this applicant
            $stmt = $pdo->prepare("UPDATE applicant_guardians SET is_primary = 0 WHERE applicant_id = ?");
            $stmt->execute([$applicantId]);

            // Set this guardian as primary
            $stmt = $pdo->prepare("UPDATE applicant_guardians SET is_primary = 1, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$guardianId]);

            // Audit log
            $stmt = $pdo->prepare("
                INSERT INTO applicant_audit (
                    applicant_id, action, description, user_id, ip_address, user_agent, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");

            $guardianName = $guardian['first_name'] . ' ' . $guardian['last_name'];
            $stmt->execute([
                $applicantId,
                'guardian_primary_set',
                "Primary guardian set: {$guardianName}",
                authUserId(),
                Request::ip(),
                Request::userAgent()
            ]);

            $pdo->commit();

            flash('success', 'Primary guardian set successfully');
            Response::redirect('/applicants/' . $applicantId . '#tab-guardians');

        } catch (Exception $e) {
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            logMessage("Set primary guardian error: " . $e->getMessage(), 'error');
            flash('error', 'Failed to set primary guardian. Please try again.');
            Response::back();
        }
    }

    /**
     * Delete a guardian
     */
    public function deleteGuardian()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        if (!hasPermission('Students.write') && !Gate::hasRole('ADMIN')) {
            Gate::deny('You need permission to delete guardians');
        }

        try {
            $pdo = Database::getTenantConnection();

            $guardianId = Request::get('guardian_id');
            $applicantId = Request::get('applicant_id');

            // Validation
            if (empty($guardianId) || empty($applicantId)) {
                flash('error', 'Invalid request');
                Response::back();
            }

            // Verify guardian exists and belongs to applicant
            $stmt = $pdo->prepare("
                SELECT ag.*, a.campus_id
                FROM applicant_guardians ag
                JOIN applicants a ON ag.applicant_id = a.id
                WHERE ag.id = ? AND ag.applicant_id = ?
            ");
            $stmt->execute([$guardianId, $applicantId]);
            $guardian = $stmt->fetch();

            if (!$guardian) {
                flash('error', 'Guardian not found');
                Response::redirect('/applicants');
            }

            // Verify campus access
            $currentCampusId = $_SESSION['current_campus_id'] ?? null;
            if ($currentCampusId && $currentCampusId !== 'all') {
                if ($guardian['campus_id'] != $currentCampusId) {
                    flash('error', 'This guardian belongs to an applicant from a different campus');
                    Response::redirect('/applicants');
                }
            }

            $pdo->beginTransaction();

            $guardianName = $guardian['first_name'] . ' ' . $guardian['last_name'];

            // Delete guardian
            $stmt = $pdo->prepare("DELETE FROM applicant_guardians WHERE id = ? AND applicant_id = ?");
            $stmt->execute([$guardianId, $applicantId]);

            // Audit log
            $stmt = $pdo->prepare("
                INSERT INTO applicant_audit (
                    applicant_id, action, description, user_id, ip_address, user_agent, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $applicantId,
                'guardian_deleted',
                "Guardian removed: {$guardianName} ({$guardian['relationship']})",
                authUserId(),
                Request::ip(),
                Request::userAgent()
            ]);

            $pdo->commit();

            flash('success', 'Guardian removed successfully');
            Response::redirect('/applicants/' . $applicantId . '#tab-guardians');

        } catch (Exception $e) {
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            logMessage("Guardian delete error: " . $e->getMessage(), 'error');
            flash('error', 'Failed to remove guardian. Please try again.');
            Response::back();
        }
    }

    /**
     * Generate upload token for phone camera capture
     */
    public function generateUploadToken()
    {
        header('Content-Type: application/json');

        if (!isAuthenticated()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        try {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            $applicantId = $data['applicant_id'] ?? null;
            $documentType = $data['document_type'] ?? null;

            if (!$applicantId || !$documentType) {
                echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
                exit;
            }

            $pdo = Database::getTenantConnection();

            // Verify applicant exists and campus access
            $currentCampusId = $_SESSION['current_campus_id'] ?? null;
            $stmt = $pdo->prepare("SELECT campus_id FROM applicants WHERE id = ?");
            $stmt->execute([$applicantId]);
            $applicant = $stmt->fetch();

            if (!$applicant) {
                echo json_encode(['success' => false, 'message' => 'Applicant not found']);
                exit;
            }

            if ($currentCampusId && $currentCampusId !== 'all' && $applicant['campus_id'] != $currentCampusId) {
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                exit;
            }

            // Generate unique token
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

            // Store token in database
            $stmt = $pdo->prepare("
                INSERT INTO document_upload_tokens (
                    token, applicant_id, document_type, created_by, expires_at, status, created_at
                ) VALUES (?, ?, ?, ?, ?, 'pending', NOW())
            ");

            $stmt->execute([$token, $applicantId, $documentType, authUserId(), $expiresAt]);

            echo json_encode(['success' => true, 'token' => $token]);

        } catch (Exception $e) {
            logMessage("Generate upload token error: " . $e->getMessage(), 'error');
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Check upload status for phone camera capture
     */
    public function checkUploadStatus($token)
    {
        header('Content-Type: application/json');

        try {
            $pdo = Database::getTenantConnection();

            $stmt = $pdo->prepare("SELECT status, expires_at FROM document_upload_tokens WHERE token = ?");
            $stmt->execute([$token]);
            $tokenData = $stmt->fetch();

            if (!$tokenData) {
                echo json_encode(['status' => 'invalid']);
                exit;
            }

            if (strtotime($tokenData['expires_at']) < time()) {
                echo json_encode(['status' => 'expired']);
                exit;
            }

            echo json_encode(['status' => $tokenData['status']]);

        } catch (Exception $e) {
            logMessage("Check upload status error: " . $e->getMessage(), 'error');
            echo json_encode(['status' => 'error']);
        }
        exit;
    }

    /**
     * Show phone capture page
     */
    public function showPhoneCapture($token)
    {
        try {
            $pdo = Database::getTenantConnection();

            $stmt = $pdo->prepare("
                SELECT dut.*, a.first_name, a.last_name
                FROM document_upload_tokens dut
                JOIN applicants a ON dut.applicant_id = a.id
                WHERE dut.token = ? AND dut.status = 'pending'
            ");
            $stmt->execute([$token]);
            $tokenData = $stmt->fetch();

            if (!$tokenData) {
                Response::view('documents.phone_capture', [
                    'error' => 'Invalid or expired upload link'
                ]);
                return;
            }

            if (strtotime($tokenData['expires_at']) < time()) {
                Response::view('documents.phone_capture', [
                    'error' => 'This upload link has expired. Please request a new one.'
                ]);
                return;
            }

            $documentLabels = [
                'birth_certificate' => 'Birth Certificate',
                'previous_report' => 'Previous School Report',
                'passport_photo' => 'Passport Photo',
                'id_copy_guardian' => 'Guardian ID Copy',
                'immunization_card' => 'Immunization Card',
                'transfer_letter' => 'Transfer Letter'
            ];

            Response::view('documents.phone_capture', [
                'uploadToken' => $token,
                'documentLabel' => $documentLabels[$tokenData['document_type']] ?? $tokenData['document_type'],
                'applicantName' => $tokenData['first_name'] . ' ' . $tokenData['last_name']
            ]);

        } catch (Exception $e) {
            logMessage("Show phone capture error: " . $e->getMessage(), 'error');
            Response::view('documents.phone_capture', [
                'error' => 'An error occurred. Please try again.'
            ]);
        }
    }

    /**
     * Handle upload from phone camera
     */
    public function uploadFromPhone()
    {
        header('Content-Type: application/json');

        try {
            $uploadToken = Request::get('upload_token');

            if (!$uploadToken) {
                echo json_encode(['success' => false, 'message' => 'Missing upload token']);
                exit;
            }

            $pdo = Database::getTenantConnection();

            // Get token data
            $stmt = $pdo->prepare("SELECT * FROM document_upload_tokens WHERE token = ? AND status = 'pending'");
            $stmt->execute([$uploadToken]);
            $tokenData = $stmt->fetch();

            if (!$tokenData) {
                echo json_encode(['success' => false, 'message' => 'Invalid or already used token']);
                exit;
            }

            if (strtotime($tokenData['expires_at']) < time()) {
                echo json_encode(['success' => false, 'message' => 'Upload link expired']);
                exit;
            }

            // Handle file upload
            if (!isset($_FILES['document_file']) || $_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'message' => 'No file uploaded']);
                exit;
            }

            $file = $_FILES['document_file'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            $maxSize = 5 * 1024 * 1024; // 5MB

            if (!in_array($file['type'], $allowedTypes)) {
                echo json_encode(['success' => false, 'message' => 'Invalid file type']);
                exit;
            }

            if ($file['size'] > $maxSize) {
                echo json_encode(['success' => false, 'message' => 'File too large']);
                exit;
            }

            // Create upload directory
            $uploadDir = __DIR__ . '/../../storage/documents/' . $tokenData['applicant_id'];
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = $tokenData['document_type'] . '_' . time() . '.' . $extension;
            $filepath = $uploadDir . '/' . $filename;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                echo json_encode(['success' => false, 'message' => 'Failed to save file']);
                exit;
            }

            $pdo->beginTransaction();

            // Save document record
            $stmt = $pdo->prepare("
                INSERT INTO applicant_documents (
                    applicant_id, document_type, file_name, document_name, file_path, file_size, mime_type,
                    verification_status, upload_method, uploaded_by, uploaded_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'uploaded', 'phone', ?, NOW())
            ");

            $stmt->execute([
                $tokenData['applicant_id'],
                $tokenData['document_type'],
                $filename,
                $filename,  // document_name same as file_name
                'storage/documents/' . $tokenData['applicant_id'] . '/' . $filename,
                $file['size'],
                $file['type'],
                $tokenData['created_by']
            ]);

            // Mark token as completed
            $stmt = $pdo->prepare("UPDATE document_upload_tokens SET status = 'completed', completed_at = NOW() WHERE token = ?");
            $stmt->execute([$uploadToken]);

            // Audit log
            $stmt = $pdo->prepare("
                INSERT INTO applicant_audit (
                    applicant_id, action, description, user_id, ip_address, user_agent, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $tokenData['applicant_id'],
                'document_uploaded',
                "Document uploaded via phone: " . $tokenData['document_type'],
                $tokenData['created_by'],
                Request::ip(),
                Request::userAgent()
            ]);

            $pdo->commit();

            echo json_encode(['success' => true, 'message' => 'Document uploaded successfully']);

        } catch (Exception $e) {
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            logMessage("Upload from phone error: " . $e->getMessage(), 'error');
            echo json_encode(['success' => false, 'message' => 'Upload failed']);
        }
        exit;
    }

    /**
     * Handle regular computer upload
     */
    public function uploadDocument()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        if (!hasPermission('Students.write') && !Gate::hasRole('ADMIN')) {
            Gate::deny('You need permission to upload documents');
        }

        try {
            $applicantId = Request::get('applicant_id');
            $documentType = Request::get('document_type');
            $notes = Request::get('notes');

            if (!$applicantId || !$documentType) {
                flash('error', 'Missing required fields');
                Response::back();
            }

            // Handle file upload
            if (!isset($_FILES['document_file']) || $_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
                flash('error', 'Please select a file to upload');
                Response::back();
            }

            $file = $_FILES['document_file'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
            $maxSize = 5 * 1024 * 1024; // 5MB

            if (!in_array($file['type'], $allowedTypes)) {
                flash('error', 'Invalid file type. Please upload JPG, PNG, or PDF');
                Response::back();
            }

            if ($file['size'] > $maxSize) {
                flash('error', 'File size exceeds 5MB limit');
                Response::back();
            }

            $pdo = Database::getTenantConnection();

            // Verify applicant and campus access
            $currentCampusId = $_SESSION['current_campus_id'] ?? null;
            $stmt = $pdo->prepare("SELECT campus_id FROM applicants WHERE id = ?");
            $stmt->execute([$applicantId]);
            $applicant = $stmt->fetch();

            if (!$applicant) {
                flash('error', 'Applicant not found');
                Response::redirect('/applicants');
            }

            if ($currentCampusId && $currentCampusId !== 'all' && $applicant['campus_id'] != $currentCampusId) {
                flash('error', 'Access denied');
                Response::redirect('/applicants');
            }

            // Create upload directory
            $uploadDir = __DIR__ . '/../../storage/documents/' . $applicantId;
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = $documentType . '_' . time() . '.' . $extension;
            $filepath = $uploadDir . '/' . $filename;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                flash('error', 'Failed to save file');
                Response::back();
            }

            $pdo->beginTransaction();

            // Save document record
            $stmt = $pdo->prepare("
                INSERT INTO applicant_documents (
                    applicant_id, document_type, file_name, document_name, file_path, file_size, mime_type,
                    notes, verification_status, upload_method, uploaded_by, uploaded_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'uploaded', 'computer', ?, NOW())
            ");

            $stmt->execute([
                $applicantId,
                $documentType,
                $filename,
                $filename,  // document_name same as file_name
                'storage/documents/' . $applicantId . '/' . $filename,
                $file['size'],
                $file['type'],
                $notes,
                authUserId()
            ]);

            // Audit log
            $stmt = $pdo->prepare("
                INSERT INTO applicant_audit (
                    applicant_id, action, description, user_id, ip_address, user_agent, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $applicantId,
                'document_uploaded',
                "Document uploaded: " . $documentType,
                authUserId(),
                Request::ip(),
                Request::userAgent()
            ]);

            $pdo->commit();

            flash('success', 'Document uploaded successfully');
            Response::redirect('/applicants/' . $applicantId . '#tab-documents');

        } catch (Exception $e) {
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            logMessage("Upload document error: " . $e->getMessage(), 'error');
            flash('error', 'Failed to upload document: ' . $e->getMessage());
            Response::back();
        }
    }

    /**
     * Download a document with audit logging
     */
    public function downloadDocument($documentId)
    {
        // Start output buffering immediately
        ob_start();

        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();

            // Get document info with campus check
            $stmt = $pdo->prepare("
                SELECT ad.*, a.campus_id
                FROM applicant_documents ad
                JOIN applicants a ON ad.applicant_id = a.id
                WHERE ad.id = ?
            ");
            $stmt->execute([$documentId]);
            $document = $stmt->fetch();

            if (!$document) {
                flash('error', 'Document not found');
                Response::back();
            }

            // Verify campus access
            $currentCampusId = $_SESSION['current_campus_id'] ?? null;
            if ($currentCampusId && $currentCampusId !== 'all' && $document['campus_id'] != $currentCampusId) {
                flash('error', 'Access denied');
                Response::back();
            }

            // Build file path
            $filepath = __DIR__ . '/../../' . $document['file_path'];

            if (!file_exists($filepath)) {
                flash('error', 'File not found on server');
                Response::back();
            }

            // Log download to audit table
            $stmt = $pdo->prepare("
                INSERT INTO document_downloads (
                    document_id, applicant_id, document_type, file_name,
                    downloaded_by, ip_address, user_agent, downloaded_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $document['id'],
                $document['applicant_id'],
                $document['document_type'],
                $document['file_name'],
                authUserId(),
                Request::ip(),
                Request::userAgent()
            ]);

            // Also log to applicant_audit
            $stmt = $pdo->prepare("
                INSERT INTO applicant_audit (
                    applicant_id, action, description, user_id, ip_address, user_agent, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $document['applicant_id'],
                'document_downloaded',
                "Document downloaded: " . $document['document_type'] . " (" . $document['file_name'] . ")",
                authUserId(),
                Request::ip(),
                Request::userAgent()
            ]);

            // Clear any output buffers to prevent corruption
            while (ob_get_level()) {
                ob_end_clean();
            }

            // Send download headers
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($document['file_name']) . '"');
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: ' . filesize($filepath));
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Expires: 0');

            // Output file in chunks for large files
            $handle = fopen($filepath, 'rb');
            while (!feof($handle)) {
                echo fread($handle, 8192);
                flush();
            }
            fclose($handle);
            exit;

        } catch (Exception $e) {
            logMessage("Download document error: " . $e->getMessage(), 'error');
            flash('error', 'Failed to download document: ' . $e->getMessage());
            Response::back();
        }
    }

    /**
     * Delete a document
     */
    public function deleteDocument()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        if (!hasPermission('Students.write') && !Gate::hasRole('ADMIN')) {
            Gate::deny('You need permission to delete documents');
        }

        try {
            $pdo = Database::getTenantConnection();

            $documentId = Request::get('document_id');
            $applicantId = Request::get('applicant_id');

            if (!$documentId || !$applicantId) {
                flash('error', 'Invalid request');
                Response::back();
            }

            // Get document info
            $stmt = $pdo->prepare("
                SELECT ad.*, a.campus_id
                FROM applicant_documents ad
                JOIN applicants a ON ad.applicant_id = a.id
                WHERE ad.id = ? AND ad.applicant_id = ?
            ");
            $stmt->execute([$documentId, $applicantId]);
            $document = $stmt->fetch();

            if (!$document) {
                flash('error', 'Document not found');
                Response::redirect('/applicants');
            }

            // Verify campus access
            $currentCampusId = $_SESSION['current_campus_id'] ?? null;
            if ($currentCampusId && $currentCampusId !== 'all' && $document['campus_id'] != $currentCampusId) {
                flash('error', 'Access denied');
                Response::redirect('/applicants');
            }

            $pdo->beginTransaction();

            // Delete physical file
            $fullPath = __DIR__ . '/../../' . $document['file_path'];
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            // Delete database record
            $stmt = $pdo->prepare("DELETE FROM applicant_documents WHERE id = ?");
            $stmt->execute([$documentId]);

            // Audit log
            $stmt = $pdo->prepare("
                INSERT INTO applicant_audit (
                    applicant_id, action, description, user_id, ip_address, user_agent, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $applicantId,
                'document_deleted',
                "Document deleted: " . $document['document_type'],
                authUserId(),
                Request::ip(),
                Request::userAgent()
            ]);

            $pdo->commit();

            flash('success', 'Document deleted successfully');
            Response::redirect('/applicants/' . $applicantId . '#tab-documents');

        } catch (Exception $e) {
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            logMessage("Delete document error: " . $e->getMessage(), 'error');
            flash('error', 'Failed to delete document');
            Response::back();
        }
    }

    /**
     * Get siblings/family members for an applicant
     */
    private function getSiblings($pdo, $applicantId)
    {
        $stmt = $pdo->prepare("
            SELECT
                asib.id,
                asib.sibling_type,
                asib.sibling_id,
                asib.relationship,
                asib.is_primary,
                asib.notes,
                CASE
                    WHEN asib.sibling_type = 'student' THEN CONCAT(s.first_name, ' ', COALESCE(s.middle_name, ''), ' ', s.last_name)
                    WHEN asib.sibling_type = 'applicant' THEN CONCAT(a.first_name, ' ', COALESCE(a.middle_name, ''), ' ', a.last_name)
                END as full_name,
                CASE
                    WHEN asib.sibling_type = 'student' THEN s.admission_number
                    WHEN asib.sibling_type = 'applicant' THEN a.application_ref
                END as number,
                CASE
                    WHEN asib.sibling_type = 'student' THEN s.status
                    WHEN asib.sibling_type = 'applicant' THEN a.status
                END as status,
                CASE
                    WHEN asib.sibling_type = 'student' THEN s.admission_date
                    WHEN asib.sibling_type = 'applicant' THEN a.created_at
                END as admission_date,
                CASE
                    WHEN asib.sibling_type = 'applicant' THEN ga.grade_name
                    ELSE NULL
                END as grade,
                NULL as fee_balance
            FROM applicant_siblings asib
            LEFT JOIN students s ON asib.sibling_type = 'student' AND asib.sibling_id = s.id
            LEFT JOIN applicants a ON asib.sibling_type = 'applicant' AND asib.sibling_id = a.id
            LEFT JOIN grades ga ON a.grade_applying_for_id = ga.id
            WHERE asib.applicant_id = ?
            ORDER BY asib.created_at DESC
        ");
        $stmt->execute([$applicantId]);
        return $stmt->fetchAll();
    }

    /**
     * Search for family members (students and applicants)
     */
    public function searchFamilyMembers()
    {
        if (!isAuthenticated()) {
            Response::json(['error' => 'Unauthorized'], 403);
        }

        try {
            $query = $_GET['q'] ?? '';
            $searchStudents = isset($_GET['students']) && $_GET['students'] === 'true';
            $searchApplicants = isset($_GET['applicants']) && $_GET['applicants'] === 'true';
            $applicantId = $_GET['applicant_id'] ?? null;

            if (strlen($query) < 2) {
                Response::json(['results' => []]);
            }

            $pdo = Database::getTenantConnection();
            $currentCampusId = $_SESSION['current_campus_id'] ?? null;
            $results = [];

            // Search students
            if ($searchStudents) {
                $sql = "
                    SELECT
                        s.id,
                        CONCAT(s.first_name, ' ', COALESCE(s.middle_name, ''), ' ', s.last_name) as name,
                        s.admission_number as number,
                        NULL as grade,
                        'student' as type
                    FROM students s
                    WHERE (
                        s.first_name LIKE ? OR
                        s.last_name LIKE ? OR
                        s.admission_number LIKE ?
                    )
                    AND s.deleted_at IS NULL
                ";

                // Campus filtering
                if ($currentCampusId && $currentCampusId !== 'all') {
                    $sql .= " AND s.campus_id = " . intval($currentCampusId);
                }

                // Exclude already linked siblings
                if ($applicantId) {
                    $sql .= " AND s.id NOT IN (
                        SELECT sibling_id FROM applicant_siblings
                        WHERE applicant_id = " . intval($applicantId) . " AND sibling_type = 'student'
                    )";
                }

                $sql .= " LIMIT 10";

                $searchTerm = '%' . $query . '%';
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
                $results = array_merge($results, $stmt->fetchAll());
            }

            // Search applicants
            if ($searchApplicants) {
                $sql = "
                    SELECT
                        a.id,
                        CONCAT(a.first_name, ' ', COALESCE(a.middle_name, ''), ' ', a.last_name) as name,
                        a.application_ref as number,
                        g.grade_name as grade,
                        'applicant' as type
                    FROM applicants a
                    LEFT JOIN grades g ON a.grade_applying_for_id = g.id
                    WHERE (
                        a.first_name LIKE ? OR
                        a.last_name LIKE ? OR
                        a.application_ref LIKE ?
                    )
                    AND a.id != ?
                ";

                // Campus filtering
                if ($currentCampusId && $currentCampusId !== 'all') {
                    $sql .= " AND a.campus_id = " . intval($currentCampusId);
                }

                // Exclude already linked siblings
                if ($applicantId) {
                    $sql .= " AND a.id NOT IN (
                        SELECT sibling_id FROM applicant_siblings
                        WHERE applicant_id = " . intval($applicantId) . " AND sibling_type = 'applicant'
                    )";
                }

                $sql .= " LIMIT 10";

                $searchTerm = '%' . $query . '%';
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $applicantId ?? 0]);
                $results = array_merge($results, $stmt->fetchAll());
            }

            Response::json(['results' => $results]);

        } catch (Exception $e) {
            logMessage("Search family members error: " . $e->getMessage(), 'error');
            Response::json(['error' => 'Search failed'], 500);
        }
    }

    /**
     * Store sibling relationship
     */
    public function storeSibling()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        if (!hasPermission('Students.write') && !Gate::hasRole('ADMIN')) {
            Response::json(['error' => 'Permission denied'], 403);
        }

        try {
            $pdo = Database::getTenantConnection();
            $pdo->beginTransaction();

            $applicantId = $_POST['applicant_id'] ?? null;
            $siblingType = $_POST['sibling_type'] ?? null;
            $siblingId = $_POST['sibling_id'] ?? null;
            $relationship = $_POST['relationship'] ?? 'sibling';
            $notes = $_POST['notes'] ?? null;

            if (!$applicantId || !$siblingType || !$siblingId) {
                Response::json(['success' => false, 'message' => 'Missing required fields'], 400);
                return;
            }

            // Insert sibling relationship
            $stmt = $pdo->prepare("
                INSERT INTO applicant_siblings (
                    applicant_id, sibling_type, sibling_id, relationship, notes, created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $applicantId,
                $siblingType,
                $siblingId,
                $relationship,
                $notes,
                authUserId()
            ]);

            // Log to audit
            $stmt = $pdo->prepare("
                INSERT INTO applicant_audit (
                    applicant_id, action, description, user_id, ip_address, user_agent, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $applicantId,
                'sibling_added',
                "Family member added: $siblingType ID $siblingId",
                authUserId(),
                Request::ip(),
                Request::userAgent()
            ]);

            $pdo->commit();

            Response::json(['success' => true, 'message' => 'Family member added successfully']);

        } catch (Exception $e) {
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            logMessage("Store sibling error: " . $e->getMessage(), 'error');
            Response::json(['success' => false, 'message' => 'Failed to add family member'], 500);
        }
    }

    /**
     * Delete sibling relationship
     */
    public function deleteSibling()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        if (!hasPermission('Students.write') && !Gate::hasRole('ADMIN')) {
            Gate::deny('You need permission to delete family members');
        }

        try {
            $pdo = Database::getTenantConnection();
            $pdo->beginTransaction();

            $siblingId = $_POST['sibling_id'] ?? null;
            $applicantId = $_POST['applicant_id'] ?? null;

            if (!$siblingId || !$applicantId) {
                flash('error', 'Invalid request');
                Response::back();
            }

            // Get sibling info for audit log
            $stmt = $pdo->prepare("SELECT * FROM applicant_siblings WHERE id = ?");
            $stmt->execute([$siblingId]);
            $sibling = $stmt->fetch();

            if (!$sibling) {
                flash('error', 'Family member not found');
                Response::back();
            }

            // Delete sibling relationship
            $stmt = $pdo->prepare("DELETE FROM applicant_siblings WHERE id = ?");
            $stmt->execute([$siblingId]);

            // Log to audit
            $stmt = $pdo->prepare("
                INSERT INTO applicant_audit (
                    applicant_id, action, description, user_id, ip_address, user_agent, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $applicantId,
                'sibling_deleted',
                "Family member removed: {$sibling['sibling_type']} ID {$sibling['sibling_id']}",
                authUserId(),
                Request::ip(),
                Request::userAgent()
            ]);

            $pdo->commit();

            flash('success', 'Family member removed successfully');

            // Use provided hash or default to siblings tab
            $hash = $_POST['_redirect_hash'] ?? '#tab-siblings';
            Response::redirect('/applicants/' . $applicantId . $hash);

        } catch (Exception $e) {
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            logMessage("Delete sibling error: " . $e->getMessage(), 'error');
            flash('error', 'Failed to remove family member');
            Response::back();
        }
    }

    /**
     * Send authorization request to guardian
     */
    public function sendAuthorizationRequest()
    {
        if (!isAuthenticated()) {
            Response::json(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        try {
            $applicantId = $_POST['applicant_id'] ?? null;
            $requestType = $_POST['request_type'] ?? null;
            $recipientName = $_POST['recipient_name'] ?? null;
            $recipientEmail = $_POST['recipient_email'] ?? null;
            $recipientPhone = $_POST['recipient_phone'] ?? null;
            $channels = $_POST['channels'] ?? [];

            // Validate inputs
            if (!$applicantId || !$requestType || !$recipientName) {
                Response::json(['success' => false, 'message' => 'Missing required fields'], 400);
                return;
            }

            if (empty($channels)) {
                Response::json(['success' => false, 'message' => 'Please select at least one communication channel'], 400);
                return;
            }

            // Get applicant data for template variables
            $pdo = Database::getTenantConnection();
            $stmt = $pdo->prepare("SELECT a.*, c.campus_name, sp.school_name FROM applicants a JOIN campuses c ON a.campus_id = c.id LEFT JOIN school_profile sp ON sp.id = 1 WHERE a.id = ?");
            $stmt->execute([$applicantId]);
            $applicant = $stmt->fetch();

            if (!$applicant) {
                Response::json(['success' => false, 'message' => 'Applicant not found'], 404);
                return;
            }

            // Determine template code based on request type
            $templateMap = [
                'data_consent' => 'data_consent_request',
                'photo_consent' => 'photo_consent_request',
                'medical_consent' => 'medical_consent_request'
            ];
            $templateCode = $templateMap[$requestType] ?? 'data_consent_request';

            // Prepare context data for template variables
            $contextData = [
                'school_name' => $applicant['campus_name'] ?? 'Our School',
                'student_name' => $applicant['first_name'] . ' ' . $applicant['last_name'],
                'guardian_name' => $recipientName
            ];

            // Send authorization request using AuthHelper
            $result = AuthHelper::requestAuthorization([
                'entity_type' => 'applicant',
                'entity_id' => $applicantId,
                'request_type' => $requestType,
                'recipient_name' => $recipientName,
                'recipient_email' => $recipientEmail,
                'recipient_phone' => $recipientPhone,
                'template_code' => $templateCode,
                'channels' => $channels,
                'campus_id' => $applicant['campus_id'],
                'created_by' => authUserId(),
                'context_data' => $contextData
            ]);

            Response::json($result);

        } catch (Exception $e) {
            logMessage("Send authorization error: " . $e->getMessage(), 'error');
            Response::json(['success' => false, 'message' => 'Failed to send authorization request'], 500);
        }
    }

    /**
     * Approve authorization by code (staff-assisted)
     */
    public function approveAuthorizationByCode()
    {
        if (!isAuthenticated()) {
            Response::json(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        try {
            $verificationCode = $_POST['verification_code'] ?? null;
            $contactMethod = $_POST['contact_method'] ?? 'phone_call';
            $staffNotes = $_POST['staff_notes'] ?? null;

            if (!$verificationCode) {
                Response::json(['success' => false, 'message' => 'Verification code is required'], 400);
                return;
            }

            // Approve using AuthHelper
            $result = AuthHelper::approveByCode($verificationCode, authUserId(), [
                'location' => 'office',
                'contact_method' => $contactMethod,
                'notes' => $staffNotes
            ]);

            Response::json($result);

        } catch (Exception $e) {
            logMessage("Approve by code error: " . $e->getMessage(), 'error');
            Response::json(['success' => false, 'message' => 'Failed to approve authorization'], 500);
        }
    }

    /**
     * Get authorization history for an applicant (AJAX)
     */
    public function getAuthorizationHistory($id)
    {
        // Check authentication
        if (!isAuthenticated()) {
            Response::json(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        // Check permission
        if (!hasPermission('Students.view') && !Gate::hasRole('ADMIN')) {
            Response::json(['success' => false, 'message' => 'Permission denied'], 403);
            return;
        }

        try {
            // Get authorization history using AuthHelper
            $history = AuthHelper::getHistory('applicant', $id);

            Response::json([
                'success' => true,
                'history' => $history
            ]);

        } catch (Exception $e) {
            logMessage("Get authorization history error: " . $e->getMessage(), 'error');
            Response::json(['success' => false, 'message' => 'Failed to load authorization history'], 500);
        }
    }

    // =========================================================================
    // ADMISSION PROCESS - Pre-Admission & Invoice Generation
    // =========================================================================

    /**
     * Initiate pre-admission process - generates fee account and invoice
     */
    public function initiatePreAdmission()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        if (!hasPermission('Students.write') && !Gate::hasRole('ADMIN')) {
            Gate::deny('You need permission to initiate pre-admission');
        }

        if (!Request::isPost()) {
            Response::redirect('/applicants');
        }

        $applicantId = $_POST['applicant_id'] ?? null;

        if (empty($applicantId)) {
            flash('error', 'Invalid request - no applicant ID');
            Response::redirect('/applicants');
        }

        try {
            $pdo = Database::getTenantConnection();

            // Get applicant details
            $stmt = $pdo->prepare("
                SELECT a.*, g.grade_name, g.grade_category,
                       glgm.grade_level_group_id
                FROM applicants a
                JOIN grades g ON g.id = a.grade_applying_for_id
                LEFT JOIN grade_level_group_members glgm ON glgm.grade_id = g.id
                WHERE a.id = ?
            ");
            $stmt->execute([$applicantId]);
            $applicant = $stmt->fetch();

            if (!$applicant) {
                flash('error', 'Applicant not found');
                Response::redirect('/applicants');
            }

            if ($applicant['status'] !== 'accepted') {
                flash('error', 'Only accepted applicants can proceed to pre-admission');
                Response::redirect("/applicants/{$applicantId}");
            }

            if (!$applicant['grade_level_group_id']) {
                flash('error', 'Grade is not assigned to any fee group. Please configure grade level groups.');
                Response::redirect("/applicants/{$applicantId}");
            }

            // Get admission fee structure for this grade group
            $stmt = $pdo->prepare("
                SELECT afs.*
                FROM admission_fee_structures afs
                WHERE afs.grade_level_group_id = ?
                AND afs.academic_year_id = ?
                AND afs.status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$applicant['grade_level_group_id'], $applicant['academic_year_id']]);
            $feeStructure = $stmt->fetch();

            if (!$feeStructure) {
                flash('error', 'No admission fee structure found for this grade level. Please configure admission fees.');
                Response::redirect("/applicants/{$applicantId}");
            }

            // Start transaction
            $pdo->beginTransaction();

            try {
                // Get school_id with fallback for existing sessions
                $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;

                // 1. Create student fee account
                $accountNumber = $this->generateAccountNumber($pdo, $applicant['campus_id']);

                $stmt = $pdo->prepare("
                    INSERT INTO student_fee_accounts
                    (school_id, account_number, applicant_id, account_status)
                    VALUES (?, ?, ?, 'pending')
                ");
                $stmt->execute([$schoolId, $accountNumber, $applicantId]);
                $feeAccountId = $pdo->lastInsertId();

                // 2. Create invoice
                $invoiceNumber = $this->generateInvoiceNumber($pdo, 'ADM');

                $stmt = $pdo->prepare("
                    INSERT INTO invoices
                    (school_id, invoice_number, invoice_type, student_fee_account_id, applicant_id,
                     academic_year_id, invoice_date, due_date, status, created_by)
                    VALUES (?, ?, 'admission', ?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'pending', ?)
                ");
                $stmt->execute([
                    $schoolId,
                    $invoiceNumber,
                    $feeAccountId,
                    $applicantId,
                    $applicant['academic_year_id'],
                    authUserId()
                ]);
                $invoiceId = $pdo->lastInsertId();

                // 3. Get fee structure lines and create invoice lines
                $stmt = $pdo->prepare("
                    SELECT afsl.*, fi.name as fee_item_name, fi.code as fee_item_code
                    FROM admission_fee_structure_lines afsl
                    JOIN fee_items fi ON fi.id = afsl.fee_item_id
                    WHERE afsl.admission_fee_structure_id = ?
                    ORDER BY afsl.sort_order
                ");
                $stmt->execute([$feeStructure['id']]);
                $feeLines = $stmt->fetchAll();

                $totalAmount = 0;
                $sortOrder = 0;

                foreach ($feeLines as $line) {
                    $sortOrder++;
                    $lineTotal = $line['amount'];
                    $totalAmount += $lineTotal;

                    $stmt = $pdo->prepare("
                        INSERT INTO invoice_lines
                        (invoice_id, fee_item_id, description, quantity, unit_amount, line_total, is_refundable, sort_order)
                        VALUES (?, ?, ?, 1, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $invoiceId,
                        $line['fee_item_id'],
                        $line['fee_item_name'],
                        $line['amount'],
                        $lineTotal,
                        $line['is_refundable'],
                        $sortOrder
                    ]);
                }

                // 4. Update invoice totals
                $stmt = $pdo->prepare("
                    UPDATE invoices
                    SET subtotal = ?, total_amount = ?, balance = ?
                    WHERE id = ?
                ");
                $stmt->execute([$totalAmount, $totalAmount, $totalAmount, $invoiceId]);

                // 5. Update fee account balance
                $stmt = $pdo->prepare("
                    UPDATE student_fee_accounts
                    SET current_balance = ?
                    WHERE id = ?
                ");
                $stmt->execute([$totalAmount, $feeAccountId]);

                // 6. Update applicant to pre_admission status
                $stmt = $pdo->prepare("
                    UPDATE applicants
                    SET status = 'pre_admission',
                        admission_invoice_id = ?,
                        student_fee_account_id = ?
                    WHERE id = ?
                ");
                $stmt->execute([$invoiceId, $feeAccountId, $applicantId]);

                // 7. Log audit trail
                $stmt = $pdo->prepare("
                    INSERT INTO applicant_audit
                    (applicant_id, action, description, user_id, ip_address, user_agent)
                    VALUES (?, 'pre_admission', ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $applicantId,
                    "Pre-admission initiated. Account: {$accountNumber}, Invoice: {$invoiceNumber}, Amount: KES " . number_format($totalAmount, 2),
                    authUserId(),
                    Request::ip(),
                    Request::userAgent()
                ]);

                $pdo->commit();

                flash('success', "Pre-admission initiated. Invoice #{$invoiceNumber} generated for KES " . number_format($totalAmount, 2));
                Response::redirect("/applicants/{$applicantId}");

            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            $errorTrace = $e->getTraceAsString();
            logMessage("Pre-admission error for applicant {$applicantId}: {$errorMsg}\nTrace: {$errorTrace}", 'error');
            flash('error', "Failed to initiate pre-admission: {$errorMsg}");
            Response::redirect("/applicants/{$applicantId}");
        }
    }

    /**
     * Record admission payment and complete admission
     */
    public function recordAdmissionPayment()
    {
        if (!isAuthenticated()) {
            if (Request::isAjax()) {
                Response::json(['success' => false, 'message' => 'Not authenticated']);
            }
            Response::redirect('/login');
        }

        if (!hasPermission('Finance.write') && !Gate::hasRole('ADMIN')) {
            if (Request::isAjax()) {
                Response::json(['success' => false, 'message' => 'Permission denied']);
            }
            Gate::deny('You need permission to record payments');
        }

        if (!Request::isPost()) {
            if (Request::isAjax()) {
                Response::json(['success' => false, 'message' => 'Invalid request method']);
            }
            Response::redirect('/applicants');
        }

        $isAjax = Request::isAjax();

        try {
            $applicantId = $_POST['applicant_id'] ?? null;
            $amount = (float)($_POST['amount'] ?? 0);
            $paymentMethodId = $_POST['payment_method_id'] ?? null;
            $referenceNumber = $_POST['reference_number'] ?? null;
            $paymentDate = $_POST['payment_date'] ?? date('Y-m-d');
            $notes = $_POST['notes'] ?? null;
            $payerBankId = $_POST['payer_bank_id'] ?? null;
            $schoolBankAccountId = $_POST['school_bank_account_id'] ?? null;
            $chequeDate = $_POST['cheque_date'] ?? null;
            $sendSms = !empty($_POST['send_sms']);
            $sendEmail = !empty($_POST['send_email']);

            if (empty($applicantId) || $amount <= 0) {
                if ($isAjax) {
                    Response::json(['success' => false, 'message' => 'Invalid payment details']);
                }
                flash('error', 'Invalid payment details');
                Response::redirect('/applicants');
            }

            $pdo = Database::getTenantConnection();

            // Get applicant with invoice details
            $stmt = $pdo->prepare("
                SELECT a.*, i.id as invoice_id, i.invoice_number, i.total_amount, i.amount_paid, i.balance,
                       sfa.id as fee_account_id, sfa.account_number
                FROM applicants a
                JOIN invoices i ON i.id = a.admission_invoice_id
                JOIN student_fee_accounts sfa ON sfa.id = a.student_fee_account_id
                WHERE a.id = ?
            ");
            $stmt->execute([$applicantId]);
            $applicant = $stmt->fetch();

            if (!$applicant) {
                if ($isAjax) {
                    Response::json(['success' => false, 'message' => 'Applicant or invoice not found']);
                }
                flash('error', 'Applicant or invoice not found');
                Response::redirect('/applicants');
            }

            if ($applicant['status'] !== 'pre_admission') {
                if ($isAjax) {
                    Response::json(['success' => false, 'message' => 'Applicant is not in pre-admission status']);
                }
                flash('error', 'Applicant is not in pre-admission status');
                Response::redirect("/applicants/{$applicantId}");
            }

            // Get payment method details if ID provided
            $paymentMethod = 'cash';
            if ($paymentMethodId) {
                $stmt = $pdo->prepare("SELECT code FROM payment_methods WHERE id = ?");
                $stmt->execute([$paymentMethodId]);
                $methodRow = $stmt->fetch();
                $paymentMethod = $methodRow['code'] ?? 'cash';
            }

            // Check for duplicate payment reference (same reference + bank combination)
            if (!empty($referenceNumber)) {
                $duplicateCheckSql = "
                    SELECT id, receipt_number, amount, payment_date
                    FROM payments
                    WHERE reference_number = ?
                    AND status NOT IN ('bounced', 'refunded')
                ";
                $params = [$referenceNumber];

                // If bank is provided, check for same bank
                if ($payerBankId) {
                    $duplicateCheckSql .= " AND payer_bank_id = ?";
                    $params[] = $payerBankId;
                }

                $stmt = $pdo->prepare($duplicateCheckSql);
                $stmt->execute($params);
                $duplicate = $stmt->fetch();

                if ($duplicate) {
                    $errorMsg = "Duplicate payment detected! Reference '{$referenceNumber}' was already used on " .
                                date('M j, Y', strtotime($duplicate['payment_date'])) .
                                " (Receipt: {$duplicate['receipt_number']}, Amount: KES " . number_format($duplicate['amount'], 2) . ")";
                    if ($isAjax) {
                        Response::json(['success' => false, 'message' => $errorMsg]);
                    }
                    flash('error', $errorMsg);
                    Response::redirect("/applicants/{$applicantId}");
                }
            }

            // Handle file upload for attachment
            $attachmentPath = null;
            if (!empty($_FILES['attachment']['name']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'uploads/payments/' . date('Y/m');
                $fullUploadDir = PUBLIC_PATH . '/' . $uploadDir;

                if (!is_dir($fullUploadDir)) {
                    mkdir($fullUploadDir, 0755, true);
                }

                $ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
                $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
                if (in_array($ext, $allowedExt)) {
                    $filename = 'payment_' . time() . '_' . uniqid() . '.' . $ext;
                    $destination = $fullUploadDir . '/' . $filename;
                    if (move_uploaded_file($_FILES['attachment']['tmp_name'], $destination)) {
                        $attachmentPath = '/' . $uploadDir . '/' . $filename;
                    }
                }
            }

            // Start transaction
            $pdo->beginTransaction();

            try {
                // Get school_id with fallback for existing sessions
                $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;

                // 1. Generate receipt number
                $receiptNumber = $this->generateReceiptNumber($pdo);

                // 2. Record payment with enhanced fields
                $stmt = $pdo->prepare("
                    INSERT INTO payments
                    (school_id, receipt_number, student_fee_account_id, applicant_id, payment_date,
                     amount, payment_method, payment_method_id, reference_number, payer_bank_id,
                     school_bank_account_id, cheque_date, attachment_path, notes, received_by, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                // Determine payment status (cheques start as pending)
                $paymentStatus = ($paymentMethod === 'cheque') ? 'pending' : 'confirmed';

                $stmt->execute([
                    $schoolId,
                    $receiptNumber,
                    $applicant['fee_account_id'],
                    $applicantId,
                    $paymentDate,
                    $amount,
                    $paymentMethod,
                    $paymentMethodId,
                    $referenceNumber,
                    $payerBankId ?: null,
                    $schoolBankAccountId ?: null,
                    $chequeDate ?: null,
                    $attachmentPath,
                    $notes,
                    authUserId(),
                    $paymentStatus
                ]);
                $paymentId = $pdo->lastInsertId();

                // 3. Allocate payment to invoice
                $allocationAmount = min($amount, $applicant['balance']);
                $stmt = $pdo->prepare("
                    INSERT INTO payment_allocations (payment_id, invoice_id, amount_allocated)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$paymentId, $applicant['invoice_id'], $allocationAmount]);

                // 4. Update invoice
                $newAmountPaid = $applicant['amount_paid'] + $allocationAmount;
                $newBalance = $applicant['total_amount'] - $newAmountPaid;
                $newStatus = ($newBalance <= 0) ? 'paid' : 'partial';

                $stmt = $pdo->prepare("
                    UPDATE invoices
                    SET amount_paid = ?, balance = ?, status = ?
                    WHERE id = ?
                ");
                $stmt->execute([$newAmountPaid, $newBalance, $newStatus, $applicant['invoice_id']]);

                // 5. Update fee account balance
                $stmt = $pdo->prepare("
                    UPDATE student_fee_accounts
                    SET current_balance = current_balance - ?
                    WHERE id = ?
                ");
                $stmt->execute([$allocationAmount, $applicant['fee_account_id']]);

                // 6. If fully paid, complete admission
                if ($newBalance <= 0) {
                    // Generate admission number
                    $admissionNumber = $this->generateAdmissionNumber($pdo, $applicant);

                    // Get full applicant data for student creation
                    $stmt = $pdo->prepare("
                        SELECT a.*, g.grade_name
                        FROM applicants a
                        LEFT JOIN grades g ON g.id = a.grade_applying_for_id
                        WHERE a.id = ?
                    ");
                    $stmt->execute([$applicantId]);
                    $fullApplicant = $stmt->fetch();

                    // 6a. Create student record from applicant data
                    $stmt = $pdo->prepare("
                        INSERT INTO students
                        (campus_id, admission_number, first_name, middle_name, last_name,
                         date_of_birth, gender, status, admission_date, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'active', CURDATE(), NOW())
                    ");
                    $stmt->execute([
                        $fullApplicant['campus_id'],
                        $admissionNumber,
                        $fullApplicant['first_name'],
                        $fullApplicant['middle_name'],
                        $fullApplicant['last_name'],
                        $fullApplicant['date_of_birth'],
                        $fullApplicant['gender']
                    ]);
                    $studentId = $pdo->lastInsertId();

                    // 6b. Copy guardians from applicant_guardians to guardians table and link to student
                    $stmt = $pdo->prepare("
                        SELECT * FROM applicant_guardians WHERE applicant_id = ?
                    ");
                    $stmt->execute([$applicantId]);
                    $applicantGuardians = $stmt->fetchAll();

                    foreach ($applicantGuardians as $ag) {
                        // Check if guardian already exists by phone number
                        $stmt = $pdo->prepare("SELECT id FROM guardians WHERE phone = ?");
                        $stmt->execute([$ag['phone']]);
                        $existingGuardian = $stmt->fetch();

                        if ($existingGuardian) {
                            $guardianId = $existingGuardian['id'];
                        } else {
                            // Create guardian record
                            $stmt = $pdo->prepare("
                                INSERT INTO guardians
                                (first_name, last_name, email, phone, id_number, address, occupation, created_at)
                                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                            ");
                            $stmt->execute([
                                $ag['first_name'],
                                $ag['last_name'],
                                $ag['email'],
                                $ag['phone'],
                                $ag['id_number'],
                                $ag['address'],
                                $ag['occupation']
                            ]);
                            $guardianId = $pdo->lastInsertId();
                        }

                        // Link guardian to student
                        $stmt = $pdo->prepare("
                            INSERT INTO student_guardians
                            (student_id, guardian_id, relationship, is_primary, can_pickup, created_at)
                            VALUES (?, ?, ?, ?, 1, NOW())
                        ");
                        $stmt->execute([
                            $studentId,
                            $guardianId,
                            $ag['relationship'],
                            $ag['is_primary']
                        ]);
                    }

                    // 6c. Create initial enrollment if a matching stream exists
                    // Find stream for the grade applying for
                    $stmt = $pdo->prepare("
                        SELECT s.id as stream_id
                        FROM streams s
                        WHERE s.grade_id = ? AND s.is_active = 1
                        LIMIT 1
                    ");
                    $stmt->execute([$fullApplicant['grade_applying_for_id']]);
                    $classStream = $stmt->fetch();

                    if ($classStream && $classStream['stream_id']) {
                        $stmt = $pdo->prepare("
                            INSERT INTO student_enrollments
                            (student_id, stream_id, academic_year_id, enrollment_date, is_current, created_at)
                            VALUES (?, ?, ?, CURDATE(), 1, NOW())
                        ");
                        $stmt->execute([
                            $studentId,
                            $classStream['stream_id'],
                            $fullApplicant['academic_year_id']
                        ]);
                    }

                    // 6d. Update fee account to link to student
                    $stmt = $pdo->prepare("
                        UPDATE student_fee_accounts
                        SET student_id = ?, account_status = 'active'
                        WHERE id = ?
                    ");
                    $stmt->execute([$studentId, $applicant['fee_account_id']]);

                    // 6e. Update applicant to admitted and link to student
                    $stmt = $pdo->prepare("
                        UPDATE applicants
                        SET status = 'admitted',
                            admission_number = ?,
                            admission_date = CURDATE(),
                            student_id = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$admissionNumber, $studentId, $applicantId]);

                    // Log admission completion
                    $stmt = $pdo->prepare("
                        INSERT INTO applicant_audit
                        (applicant_id, action, description, user_id, ip_address, user_agent)
                        VALUES (?, 'admitted', ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $applicantId,
                        "Admission completed. Admission Number: {$admissionNumber}. Student ID: {$studentId}. Full payment received.",
                        authUserId(),
                        Request::ip(),
                        Request::userAgent()
                    ]);

                    // Log student creation in student audit
                    try {
                        $stmt = $pdo->prepare("
                            INSERT INTO student_audit_log
                            (student_id, action, description, user_id, ip_address, created_at)
                            VALUES (?, 'created', ?, ?, ?, NOW())
                        ");
                        $stmt->execute([
                            $studentId,
                            "Student record created from applicant #{$applicantId}. Admission Number: {$admissionNumber}",
                            authUserId(),
                            Request::ip()
                        ]);
                    } catch (Exception $e) {
                        // student_audit_log may not exist yet
                    }

                    // 6f. Copy applicant documents to student documents
                    try {
                        $stmt = $pdo->prepare("SELECT * FROM applicant_documents WHERE applicant_id = ?");
                        $stmt->execute([$applicantId]);
                        $applicantDocs = $stmt->fetchAll();

                        if (!empty($applicantDocs)) {
                            // Map document types to document_types table IDs
                            $docTypeMap = [
                                'birth_certificate' => 1,
                                'id_document' => 4,
                                'passport_photo' => 6,
                                'medical_record' => 3,
                                'previous_report' => 2,
                                'transfer_letter' => 5,
                                'other' => 8
                            ];

                            foreach ($applicantDocs as $doc) {
                                $docTypeId = $docTypeMap[$doc['document_type']] ?? 8; // Default to 'Other'
                                $stmt = $pdo->prepare("
                                    INSERT INTO student_documents
                                    (student_id, document_type_id, title, file_name, file_path, file_size, file_type, created_at)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                                ");
                                $stmt->execute([
                                    $studentId,
                                    $docTypeId,
                                    ucwords(str_replace('_', ' ', $doc['document_type'])),
                                    $doc['file_name'],
                                    $doc['file_path'] ?? ('/uploads/documents/' . $doc['file_name']),
                                    $doc['file_size'] ?? 0,
                                    $doc['mime_type'] ?? 'application/octet-stream'
                                ]);
                            }
                        }
                    } catch (Exception $e) {
                        // Document copy may fail if tables don't exist, ignore
                        error_log("Document copy error: " . $e->getMessage());
                    }

                    $admissionCompleted = true;
                } else {
                    // Partial payment logged
                    $stmt = $pdo->prepare("
                        INSERT INTO applicant_audit
                        (applicant_id, action, description, user_id, ip_address, user_agent)
                        VALUES (?, 'payment_received', ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $applicantId,
                        "Partial payment received. Amount: KES " . number_format($amount, 2) . ". Balance: KES " . number_format($newBalance, 2),
                        authUserId(),
                        Request::ip(),
                        Request::userAgent()
                    ]);

                    $admissionCompleted = false;
                    $admissionNumber = null;
                }

                $pdo->commit();

                // Handle SMS/Email notifications
                if ($sendSms || $sendEmail) {
                    try {
                        // Get contact details from applicant
                        $stmt = $pdo->prepare("
                            SELECT a.phone, a.email, a.first_name, a.last_name
                            FROM applicants a WHERE a.id = ?
                        ");
                        $stmt->execute([$applicantId]);
                        $contact = $stmt->fetch();

                        if ($sendSms && !empty($contact['phone'])) {
                            // Queue SMS notification (implementation depends on SMS service)
                            logMessage("SMS receipt queued for payment {$receiptNumber} to {$contact['phone']}", 'info');
                        }

                        if ($sendEmail && !empty($contact['email'])) {
                            // Queue email notification (implementation depends on email service)
                            logMessage("Email receipt queued for payment {$receiptNumber} to {$contact['email']}", 'info');
                        }
                    } catch (Exception $e) {
                        logMessage("Notification error: " . $e->getMessage(), 'warning');
                    }
                }

                // Return JSON for AJAX requests
                if ($isAjax) {
                    Response::json([
                        'success' => true,
                        'payment_id' => $paymentId,
                        'receipt_number' => $receiptNumber,
                        'amount' => $amount,
                        'remaining_balance' => $newBalance,
                        'admission_number' => $admissionNumber,
                        'admission_completed' => $admissionCompleted,
                        'message' => $admissionCompleted
                            ? "Payment recorded. Admission complete!"
                            : "Payment recorded. Outstanding balance: KES " . number_format($newBalance, 2)
                    ]);
                }

                // Flash message for non-AJAX
                if ($admissionCompleted) {
                    flash('success', "Payment recorded. Admission complete! Admission Number: {$admissionNumber}");
                } else {
                    flash('info', "Payment recorded. Outstanding balance: KES " . number_format($newBalance, 2));
                }

                Response::redirect("/applicants/{$applicantId}");

            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            logMessage("Record admission payment error: " . $e->getMessage(), 'error');
            if ($isAjax) {
                Response::json(['success' => false, 'message' => 'Failed to record payment: ' . $e->getMessage()]);
            }
            flash('error', 'Failed to record payment. Please try again.');
            Response::redirect("/applicants/{$applicantId}");
        }
    }

    /**
     * Get finances tab content via AJAX
     */
    public function financesTab($id)
    {
        if (!isAuthenticated()) {
            Response::json(['error' => 'Not authenticated'], 401);
        }

        try {
            $pdo = Database::getTenantConnection();

            // Get applicant
            $stmt = $pdo->prepare("SELECT * FROM applicants WHERE id = ?");
            $stmt->execute([$id]);
            $applicant = $stmt->fetch();

            if (!$applicant) {
                echo '<div class="alert alert-warning">Applicant not found</div>';
                return;
            }

            // Render just the finances tab content
            $viewPath = VIEWS_PATH . '/applicants/_show_tab_finances.php';
            if (file_exists($viewPath)) {
                // Pass variables to the view
                extract(['applicant' => $applicant, 'pdo' => $pdo]);
                include $viewPath;
            } else {
                echo '<div class="alert alert-warning">Finances tab view not found</div>';
            }

        } catch (Exception $e) {
            logMessage("Finances tab error: " . $e->getMessage(), 'error');
            echo '<div class="alert alert-danger">Error loading finances: ' . e($e->getMessage()) . '</div>';
        }
    }

    /**
     * Generate unique account number
     */
    private function generateAccountNumber($pdo, $campusId)
    {
        $year = date('Y');
        $prefix = "ACC";

        // Get next sequence
        $stmt = $pdo->prepare("
            SELECT MAX(CAST(SUBSTRING(account_number, 8) AS UNSIGNED)) as max_seq
            FROM student_fee_accounts
            WHERE account_number LIKE ?
        ");
        $stmt->execute(["{$prefix}{$year}%"]);
        $result = $stmt->fetch();

        $nextSeq = ($result['max_seq'] ?? 0) + 1;

        return sprintf("%s%s%05d", $prefix, $year, $nextSeq);
    }

    /**
     * Generate unique invoice number
     */
    private function generateInvoiceNumber($pdo, $prefix = 'INV')
    {
        $year = date('Y');
        $month = date('m');

        // Get next sequence
        $stmt = $pdo->prepare("
            SELECT MAX(CAST(SUBSTRING(invoice_number, 10) AS UNSIGNED)) as max_seq
            FROM invoices
            WHERE invoice_number LIKE ?
        ");
        $stmt->execute(["{$prefix}-{$year}{$month}%"]);
        $result = $stmt->fetch();

        $nextSeq = ($result['max_seq'] ?? 0) + 1;

        return sprintf("%s-%s%s%05d", $prefix, $year, $month, $nextSeq);
    }

    /**
     * Generate unique receipt number
     */
    private function generateReceiptNumber($pdo)
    {
        $year = date('Y');
        $prefix = "RCP";

        // Get next sequence
        $stmt = $pdo->prepare("
            SELECT MAX(CAST(SUBSTRING(receipt_number, 8) AS UNSIGNED)) as max_seq
            FROM payments
            WHERE receipt_number LIKE ?
        ");
        $stmt->execute(["{$prefix}{$year}%"]);
        $result = $stmt->fetch();

        $nextSeq = ($result['max_seq'] ?? 0) + 1;

        return sprintf("%s%s%05d", $prefix, $year, $nextSeq);
    }

    /**
     * Generate unique admission number
     * Format: CAMPUS_CODE/YEAR/SEQUENCE (e.g., MKN/2025/00123)
     */
    private function generateAdmissionNumber($pdo, $applicant)
    {
        $year = date('Y');

        // Get campus code
        $stmt = $pdo->prepare("SELECT campus_code FROM campuses WHERE id = ?");
        $stmt->execute([$applicant['campus_id']]);
        $campus = $stmt->fetch();
        $campusCode = $campus['campus_code'] ?? 'SCH';

        // Get next sequence for this campus and year
        $stmt = $pdo->prepare("
            SELECT MAX(CAST(SUBSTRING_INDEX(admission_number, '/', -1) AS UNSIGNED)) as max_seq
            FROM applicants
            WHERE admission_number LIKE ?
        ");
        $stmt->execute(["{$campusCode}/{$year}/%"]);
        $result = $stmt->fetch();

        $nextSeq = ($result['max_seq'] ?? 0) + 1;

        return sprintf("%s/%s/%05d", $campusCode, $year, $nextSeq);
    }
}
