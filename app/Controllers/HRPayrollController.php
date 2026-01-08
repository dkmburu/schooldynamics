<?php
/**
 * HR & Payroll Controller
 * Handles all HR and Payroll module functionality
 */

require_once __DIR__ . '/../../config/database.php';

class HRPayrollController
{
    private $db;
    private $schoolId;

    public function __construct()
    {
        $this->db = Database::getTenantConnection();
        $this->schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;
    }

    /**
     * Dashboard - Overview of HR & Payroll
     */
    public function index()
    {
        $stats = $this->getDashboardStats();
        $recentPayrolls = $this->getRecentPayrollRuns();
        $pendingApprovals = $this->getPendingApprovals();
        $upcomingPayroll = $this->getUpcomingPayrollDate();

        $pageTitle = "HR & Payroll Dashboard";
        $contentView = __DIR__ . '/../Views/hr-payroll/_dashboard_content.php';

        require __DIR__ . '/../Views/hr-payroll/index.php';
    }

    /**
     * Staff Directory
     */
    public function staff()
    {
        $filters = [
            'department' => $_GET['department'] ?? '',
            'designation' => $_GET['designation'] ?? '',
            'status' => $_GET['status'] ?? 'active',
            'type' => $_GET['type'] ?? '',
            'search' => $_GET['search'] ?? ''
        ];

        $staff = $this->getStaffList($filters);
        $departments = $this->getDepartments();
        $designations = $this->getDesignations();

        $pageTitle = "Staff Directory";
        $contentView = __DIR__ . '/../Views/hr-payroll/_staff_directory_content.php';

        require __DIR__ . '/../Views/hr-payroll/staff.php';
    }

    /**
     * Add New Staff Form
     */
    public function createStaff()
    {
        // Get all lookup data
        $lookups = $this->getAllLookups();

        $pageTitle = "Add New Staff";
        $contentView = __DIR__ . '/../Views/hr-payroll/_staff_form_content.php';

        require __DIR__ . '/../Views/hr-payroll/staff-form.php';
    }

    /**
     * Store New Staff (Personal Info - Step 1)
     */
    public function storeStaff()
    {
        try {
            // Generate staff number if not provided
            $staffNumber = $_POST['staff_number'] ?? '';
            if (empty($staffNumber)) {
                $staffNumber = $this->generateStaffNumber();
            }

            // Insert basic personal info
            $stmt = $this->db->prepare("
                INSERT INTO staff (
                    staff_number, title, first_name, middle_name, last_name,
                    gender, date_of_birth, id_number, nationality, marital_status, religion,
                    blood_group, email, phone, alt_phone, postal_address, physical_address,
                    emergency_contact_name, emergency_contact_relationship, emergency_contact_phone,
                    status, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?,
                    'active', NOW()
                )
            ");

            $stmt->execute([
                $staffNumber,
                $_POST['title'] ?? null,
                $_POST['first_name'] ?? '',
                $_POST['middle_name'] ?? null,
                $_POST['last_name'] ?? '',
                $_POST['gender'] ?? null,
                $_POST['date_of_birth'] ?: null,
                $_POST['id_number'] ?: null,
                $_POST['nationality'] ?? 'Kenyan',
                $_POST['marital_status'] ?? null,
                $_POST['religion'] ?? null,
                $_POST['blood_group'] ?? null,
                $_POST['email'] ?? null,
                $_POST['phone'] ?? null,
                $_POST['alt_phone'] ?? null,
                $_POST['postal_address'] ?? null,
                $_POST['physical_address'] ?? null,
                $_POST['emergency_contact_name'] ?? null,
                $_POST['emergency_contact_relationship'] ?? null,
                $_POST['emergency_contact_phone'] ?? null
            ]);

            $staffId = $this->db->lastInsertId();

            // Handle photo upload
            if (!empty($_FILES['photo']['tmp_name'])) {
                $this->uploadStaffPhoto($staffId, $_FILES['photo']);
            }

            // Set success message
            $_SESSION['flash_success'] = "Staff member created successfully. You can now complete their profile.";

            // Redirect to edit mode to continue with other tabs
            header("Location: /hr-payroll/staff/{$staffId}/edit");
            exit;

        } catch (Exception $e) {
            $_SESSION['flash_error'] = "Error creating staff: " . $e->getMessage();
            header("Location: /hr-payroll/staff/create");
            exit;
        }
    }

    /**
     * Edit Staff Form
     */
    public function editStaff($id)
    {
        $staff = $this->getStaffById($id);

        if (!$staff) {
            $_SESSION['flash_error'] = "Staff member not found.";
            header("Location: /hr-payroll/staff");
            exit;
        }

        // Get related data
        $qualifications = $this->getStaffQualifications($id);
        $employmentHistory = $this->getStaffEmploymentHistory($id);
        $references = $this->getStaffReferences($id);
        $documents = $this->getStaffDocuments($id);
        $lookups = $this->getAllLookups();

        // Get roles for Online Access tab
        $roles = $this->getAllRoles();

        // Get linked user account if exists
        $linkedUser = null;
        $userRoles = [];
        if (!empty($staff['user_id'])) {
            $linkedUser = $this->getUserById($staff['user_id']);
            $userRoles = $this->getUserRoles($staff['user_id']);
        }

        $pageTitle = "Edit Staff: " . $staff['first_name'] . ' ' . $staff['last_name'];
        $contentView = __DIR__ . '/../Views/hr-payroll/_staff_form_content.php';

        require __DIR__ . '/../Views/hr-payroll/staff-form.php';
    }

    /**
     * Update Staff
     */
    public function updateStaff($id)
    {
        try {
            // Get active tab for redirect
            $activeTab = $_POST['active_tab'] ?? 'tab-personal';

            // Update main staff record (only columns that exist in the table)
            $stmt = $this->db->prepare("
                UPDATE staff SET
                    title = ?, first_name = ?, middle_name = ?, last_name = ?,
                    gender = ?, date_of_birth = ?, id_number = ?, nationality = ?,
                    marital_status = ?, religion = ?, blood_group = ?,
                    email = ?, phone = ?, alt_phone = ?, postal_address = ?, physical_address = ?,
                    emergency_contact_name = ?, emergency_contact_relationship = ?, emergency_contact_phone = ?,
                    staff_number = ?, staff_type = ?, employment_type = ?, department = ?, job_title = ?,
                    reports_to = ?, work_location = ?, work_schedule = ?,
                    date_joined = ?, contract_start_date = ?, contract_end_date = ?, probation_end_date = ?,
                    tsc_number = ?,
                    salary_structure_id = ?, salary_grade_id = ?, basic_salary = ?,
                    house_allowance = ?, transport_allowance = ?, other_allowances = ?,
                    teaching_subjects = ?, languages = ?, technical_skills = ?, certifications = ?,
                    references_verified = ?, references_verified_by = ?, references_verification_notes = ?,
                    payment_mode = ?, bank_id = ?, bank_name = ?, bank_branch = ?, bank_account_number = ?,
                    bank_account_name = ?, mpesa_number = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");

            $stmt->execute([
                $_POST['title'] ?? null,
                $_POST['first_name'] ?? '',
                $_POST['middle_name'] ?? null,
                $_POST['last_name'] ?? '',
                $_POST['gender'] ?? null,
                $_POST['date_of_birth'] ?: null,
                $_POST['id_number'] ?: null,
                $_POST['nationality'] ?? 'Kenyan',
                $_POST['marital_status'] ?? null,
                $_POST['religion'] ?? null,
                $_POST['blood_group'] ?? null,
                $_POST['email'] ?? null,
                $_POST['phone'] ?? null,
                $_POST['alt_phone'] ?? null,
                $_POST['postal_address'] ?? null,
                $_POST['physical_address'] ?? null,
                $_POST['emergency_contact_name'] ?? null,
                $_POST['emergency_contact_relationship'] ?? null,
                $_POST['emergency_contact_phone'] ?? null,
                // Employment
                $_POST['staff_number'] ?? null,
                $_POST['staff_type'] ?? null,
                $_POST['employment_type'] ?? null,
                $_POST['department'] ?? null,
                $_POST['job_title'] ?? null,
                $_POST['reports_to'] ?? null,
                $_POST['work_location'] ?? null,
                $_POST['work_schedule'] ?? null,
                $_POST['date_joined'] ?: null,
                $_POST['contract_start_date'] ?: null,
                $_POST['contract_end_date'] ?: null,
                $_POST['probation_end_date'] ?: null,
                $_POST['tsc_number'] ?? null,
                // Salary & Allowances
                $_POST['salary_structure_id'] ?: null,
                $_POST['salary_grade_id'] ?: null,
                $_POST['basic_salary'] ?: null,
                $_POST['house_allowance'] ?: null,
                $_POST['transport_allowance'] ?: null,
                $_POST['other_allowances'] ?: null,
                // Skills & Competencies
                $_POST['teaching_subjects'] ?? null,
                $_POST['languages'] ?? null,
                $_POST['technical_skills'] ?? null,
                $_POST['certifications'] ?? null,
                // Reference Verification
                isset($_POST['references_verified']) ? 1 : 0,
                $_POST['references_verified_by'] ?? null,
                $_POST['references_verification_notes'] ?? null,
                // Bank Details
                $_POST['payment_mode'] ?? 'bank',
                $_POST['bank_id'] ?: null,
                $_POST['bank_name'] ?? null,
                $_POST['bank_branch'] ?? null,
                $_POST['bank_account_number'] ?? null,
                $_POST['bank_account_name'] ?? null,
                $_POST['mpesa_number'] ?? null,
                $id
            ]);

            // Handle photo upload
            if (!empty($_FILES['photo']['tmp_name'])) {
                $this->uploadStaffPhoto($id, $_FILES['photo']);
            }

            // Save qualifications
            $this->saveStaffQualifications($id, $_POST['qualifications'] ?? []);

            // Save employment history
            $this->saveStaffEmploymentHistory($id, $_POST['employment_history'] ?? []);

            // Save references
            $this->saveStaffReferences($id, $_POST['references'] ?? []);

            $_SESSION['flash_success'] = "Staff member updated successfully.";
            header("Location: /hr-payroll/staff/{$id}/edit?tab={$activeTab}");
            exit;

        } catch (Exception $e) {
            $_SESSION['flash_error'] = "Error updating staff: " . $e->getMessage();
            $activeTab = $_POST['active_tab'] ?? 'tab-personal';
            header("Location: /hr-payroll/staff/{$id}/edit?tab={$activeTab}");
            exit;
        }
    }

    /**
     * Show Staff Profile
     */
    public function showStaff($id)
    {
        $staff = $this->getStaffById($id);

        if (!$staff) {
            $_SESSION['flash_error'] = "Staff member not found.";
            header("Location: /hr-payroll/staff");
            exit;
        }

        $qualifications = $this->getStaffQualifications($id);
        $employmentHistory = $this->getStaffEmploymentHistory($id);
        $references = $this->getStaffReferences($id);
        $documents = $this->getStaffDocuments($id);

        $pageTitle = $staff['first_name'] . ' ' . $staff['last_name'];
        $contentView = __DIR__ . '/../Views/hr-payroll/_staff_profile_content.php';

        require __DIR__ . '/../Views/hr-payroll/staff-profile.php';
    }

    /**
     * Generate unique staff number
     */
    private function generateStaffNumber()
    {
        $prefix = 'STF';
        $year = date('Y');

        $stmt = $this->db->prepare("
            SELECT MAX(CAST(SUBSTRING(staff_number, 8) AS UNSIGNED)) as max_num
            FROM staff
            WHERE staff_number LIKE ?
        ");
        $stmt->execute(["{$prefix}{$year}%"]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $nextNum = ($result['max_num'] ?? 0) + 1;
        return $prefix . $year . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get staff by ID
     */
    private function getStaffById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM staff WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get all system roles
     */
    private function getAllRoles()
    {
        $stmt = $this->db->query("SELECT * FROM roles ORDER BY id");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get user by ID
     */
    private function getUserById($userId)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get user's assigned roles
     */
    private function getUserRoles($userId)
    {
        $stmt = $this->db->prepare("SELECT role_id FROM user_roles WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get staff qualifications
     */
    private function getStaffQualifications($staffId)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM staff_qualifications WHERE staff_id = ? ORDER BY year_completed DESC
        ");
        $stmt->execute([$staffId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get staff employment history
     */
    private function getStaffEmploymentHistory($staffId)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM staff_employment_history WHERE staff_id = ? ORDER BY start_date DESC
        ");
        $stmt->execute([$staffId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get staff references
     */
    private function getStaffReferences($staffId)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM staff_references WHERE staff_id = ? ORDER BY id
        ");
        $stmt->execute([$staffId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get staff documents
     */
    private function getStaffDocuments($staffId)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM staff_documents WHERE staff_id = ? ORDER BY uploaded_at DESC
        ");
        $stmt->execute([$staffId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Upload staff photo
     */
    private function uploadStaffPhoto($staffId, $file)
    {
        $uploadDir = __DIR__ . '/../../public/uploads/staff/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = "staff_{$staffId}_" . time() . "." . $ext;
        $filepath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $photoUrl = "/uploads/staff/{$filename}";
            $stmt = $this->db->prepare("UPDATE staff SET photo = ? WHERE id = ?");
            $stmt->execute([$photoUrl, $staffId]);
        }
    }

    /**
     * Save staff qualifications
     */
    private function saveStaffQualifications($staffId, $qualifications)
    {
        // Delete existing and re-insert
        $stmt = $this->db->prepare("DELETE FROM staff_qualifications WHERE staff_id = ?");
        $stmt->execute([$staffId]);

        foreach ($qualifications as $q) {
            if (empty($q['level']) && empty($q['institution'])) continue;

            $stmt = $this->db->prepare("
                INSERT INTO staff_qualifications
                (staff_id, qualification_level, field_of_study, institution, year_completed, grade_obtained, certificate_number)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $staffId,
                $q['level'] ?? null,
                $q['field'] ?? null,
                $q['institution'] ?? null,
                $q['year'] ?: null,
                $q['grade'] ?? null,
                $q['cert_number'] ?? null
            ]);
        }
    }

    /**
     * Save staff employment history
     */
    private function saveStaffEmploymentHistory($staffId, $history)
    {
        $stmt = $this->db->prepare("DELETE FROM staff_employment_history WHERE staff_id = ?");
        $stmt->execute([$staffId]);

        foreach ($history as $h) {
            if (empty($h['employer'])) continue;

            $stmt = $this->db->prepare("
                INSERT INTO staff_employment_history
                (staff_id, employer_name, job_title, start_date, end_date, reason_for_leaving, responsibilities, supervisor_name, supervisor_phone)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $staffId,
                $h['employer'] ?? null,
                $h['position'] ?? null,
                $h['start_date'] ?: null,
                $h['end_date'] ?: null,
                $h['reason_leaving'] ?? null,
                $h['responsibilities'] ?? null,
                $h['supervisor'] ?? null,
                $h['contact_phone'] ?? null
            ]);
        }
    }

    /**
     * Save staff references
     */
    private function saveStaffReferences($staffId, $references)
    {
        $stmt = $this->db->prepare("DELETE FROM staff_references WHERE staff_id = ?");
        $stmt->execute([$staffId]);

        foreach ($references as $r) {
            if (empty($r['name'])) continue;

            $stmt = $this->db->prepare("
                INSERT INTO staff_references
                (staff_id, referee_name, relationship, organization, position, phone, email, years_known)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $staffId,
                $r['name'] ?? null,
                $r['relationship'] ?? null,
                $r['organization'] ?? null,
                $r['position'] ?? null,
                $r['phone'] ?? null,
                $r['email'] ?? null,
                $r['years_known'] ?: null
            ]);
        }
    }

    /**
     * Get all lookup tables for staff form
     */
    private function getAllLookups()
    {
        return [
            'countries' => $this->getCountries(),
            'departments' => $this->getDepartments(),
            'designations' => $this->getDesignations(),
            'salaryStructures' => $this->getSalaryStructures(),
            'salaryGrades' => $this->getSalaryGrades(),
            'banks' => $this->getBanks(),
            'bloodGroups' => $this->getBloodGroups(),
            'staffTypes' => $this->getStaffTypes(),
            'employmentTypes' => $this->getEmploymentTypes(),
            'workSchedules' => $this->getWorkSchedules(),
            'paymentModes' => $this->getPaymentModes(),
            'educationLevels' => $this->getEducationLevels(),
            'leavingReasons' => $this->getLeavingReasons(),
            'maritalStatuses' => $this->getMaritalStatuses(),
            'religions' => $this->getReligions(),
            'emergencyRelationships' => $this->getRelationshipTypes('emergency'),
            'referenceRelationships' => $this->getRelationshipTypes('reference'),
            'nextOfKinRelationships' => $this->getRelationshipTypes('next_of_kin'),
            'documentTypes' => $this->getDocumentTypes(),
        ];
    }

    /**
     * Payroll Processing
     */
    public function payroll()
    {
        $payPeriods = $this->getPayPeriods();
        $currentPeriod = $this->getCurrentPayPeriod();
        $payrollRuns = $this->getPayrollRuns($currentPeriod['id'] ?? null);

        $pageTitle = "Payroll Processing";
        $contentView = __DIR__ . '/../Views/hr-payroll/_payroll_content.php';

        require __DIR__ . '/../Views/hr-payroll/payroll.php';
    }

    /**
     * Payslips
     */
    public function payslips()
    {
        $payPeriods = $this->getPayPeriods();
        $selectedPeriod = $_GET['period'] ?? null;
        $payslips = $this->getPayslips($selectedPeriod);

        $pageTitle = "Payslips";
        $contentView = __DIR__ . '/../Views/hr-payroll/_payslips_content.php';

        require __DIR__ . '/../Views/hr-payroll/payslips.php';
    }

    /**
     * Salary Structures
     */
    public function salaryStructures()
    {
        $structures = $this->getSalaryStructures();
        $grades = $this->getSalaryGrades();
        $components = $this->getPayComponents();

        $pageTitle = "Salary Structures";
        $contentView = __DIR__ . '/../Views/hr-payroll/_salary_structures_content.php';

        require __DIR__ . '/../Views/hr-payroll/salary-structures.php';
    }

    /**
     * Allowances & Deductions (Pay Components)
     */
    public function components()
    {
        $components = $this->getPayComponents();
        $statutoryFunds = $this->getStatutoryFunds();

        $pageTitle = "Allowances & Deductions";
        $contentView = __DIR__ . '/../Views/hr-payroll/_components_content.php';

        require __DIR__ . '/../Views/hr-payroll/components.php';
    }

    /**
     * Loans & Advances
     */
    public function loans()
    {
        $loanTypes = $this->getLoanTypes();
        $pendingLoans = $this->getPendingLoans();
        $activeLoans = $this->getActiveLoans();

        $pageTitle = "Loans & Advances";
        $contentView = __DIR__ . '/../Views/hr-payroll/_loans_content.php';

        require __DIR__ . '/../Views/hr-payroll/loans.php';
    }

    /**
     * Statutory Deductions Setup
     */
    public function statutory()
    {
        $countryId = $this->getSchoolCountryId();
        $statutoryFunds = $this->getStatutoryFundsWithRates($countryId);
        $taxBrackets = $this->getTaxBrackets($countryId);
        $taxReliefs = $this->getTaxReliefs($countryId);

        $pageTitle = "Statutory Deductions";
        $contentView = __DIR__ . '/../Views/hr-payroll/_statutory_content.php';

        require __DIR__ . '/../Views/hr-payroll/statutory.php';
    }

    /**
     * Payroll Reports
     */
    public function reports()
    {
        $payPeriods = $this->getPayPeriods();
        $reportTypes = [
            'payroll_summary' => 'Payroll Summary',
            'department_costs' => 'Department-wise Costs',
            'statutory_returns' => 'Statutory Returns',
            'bank_schedule' => 'Bank Payment Schedule',
            'p9_form' => 'P9 Tax Certificate',
            'ytd_report' => 'Year-to-Date Report'
        ];

        $pageTitle = "Payroll Reports";
        $contentView = __DIR__ . '/../Views/hr-payroll/_reports_content.php';

        require __DIR__ . '/../Views/hr-payroll/reports.php';
    }

    // =========================================================================
    // Data Methods
    // =========================================================================

    private function getDashboardStats()
    {
        // Total staff count
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM staff WHERE status = 'active'");
        $totalStaff = $stmt->fetch()['count'] ?? 0;

        // This month's payroll total
        $currentMonth = date('Y-m-01');
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(ps.net_salary), 0) as total
            FROM payslips ps
            JOIN payroll_runs pr ON ps.payroll_run_id = pr.id
            JOIN pay_periods pp ON pr.pay_period_id = pp.id
            WHERE pp.start_date >= ? AND pr.status IN ('approved', 'paid')
        ");
        $stmt->execute([$currentMonth]);
        $monthlyPayroll = $stmt->fetch()['total'] ?? 0;

        // Pending approvals
        $stmt = $this->db->query("
            SELECT COUNT(*) as count FROM payroll_runs WHERE status = 'pending_approval'
        ");
        $pendingPayrollApprovals = $stmt->fetch()['count'] ?? 0;

        $stmt = $this->db->query("
            SELECT COUNT(*) as count FROM staff_loans WHERE status = 'pending'
        ");
        $pendingLoanApprovals = $stmt->fetch()['count'] ?? 0;

        // Active loans
        $stmt = $this->db->query("
            SELECT COUNT(*) as count, COALESCE(SUM(balance), 0) as balance
            FROM staff_loans WHERE status = 'active'
        ");
        $loanStats = $stmt->fetch();

        return [
            'total_staff' => $totalStaff,
            'monthly_payroll' => $monthlyPayroll,
            'pending_payroll_approvals' => $pendingPayrollApprovals,
            'pending_loan_approvals' => $pendingLoanApprovals,
            'active_loans' => $loanStats['count'] ?? 0,
            'loan_balance' => $loanStats['balance'] ?? 0
        ];
    }

    private function getRecentPayrollRuns()
    {
        $stmt = $this->db->query("
            SELECT pr.*, pp.period_name,
                   pr.total_employees, pr.total_gross, pr.total_net
            FROM payroll_runs pr
            JOIN pay_periods pp ON pr.pay_period_id = pp.id
            ORDER BY pr.created_at DESC
            LIMIT 5
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getPendingApprovals()
    {
        return [
            'payroll' => [],
            'loans' => [],
            'leave' => []
        ];
    }

    private function getUpcomingPayrollDate()
    {
        // Get next pay period end date
        $stmt = $this->db->query("
            SELECT * FROM pay_periods
            WHERE end_date >= CURDATE() AND status = 'draft'
            ORDER BY end_date ASC
            LIMIT 1
        ");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getStaffList($filters)
    {
        $sql = "
            SELECT s.*,
                   s.department as department_name,
                   s.job_title as designation_name,
                   s.basic_salary as current_salary,
                   CONCAT(s.first_name, ' ', COALESCE(s.middle_name, ''), ' ', s.last_name) as full_name
            FROM staff s
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['department'])) {
            $sql .= " AND s.department = ?";
            $params[] = $filters['department'];
        }

        if (!empty($filters['designation'])) {
            $sql .= " AND s.job_title = ?";
            $params[] = $filters['designation'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND s.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['type'])) {
            $sql .= " AND s.employment_type = ?";
            $params[] = $filters['type'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR s.staff_number LIKE ? OR s.id_number LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$search, $search, $search, $search]);
        }

        $sql .= " ORDER BY s.first_name, s.last_name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getDepartments()
    {
        // Get distinct departments from staff table
        $stmt = $this->db->query("
            SELECT DISTINCT department as department_name
            FROM staff
            WHERE department IS NOT NULL AND department != ''
            ORDER BY department
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getDesignations()
    {
        // Get distinct job titles from staff table
        $stmt = $this->db->query("
            SELECT DISTINCT job_title as designation_name
            FROM staff
            WHERE job_title IS NOT NULL AND job_title != ''
            ORDER BY job_title
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getSalaryStructures()
    {
        $stmt = $this->db->query("
            SELECT * FROM salary_structures WHERE is_active = 1 ORDER BY structure_name
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getSalaryGrades()
    {
        $stmt = $this->db->query("
            SELECT * FROM salary_grades WHERE is_active = 1 ORDER BY grade_code
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getPayComponents()
    {
        $stmt = $this->db->query("
            SELECT * FROM pay_components WHERE is_active = 1 ORDER BY display_order, component_name
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getPayPeriods()
    {
        $stmt = $this->db->query("
            SELECT * FROM pay_periods ORDER BY start_date DESC LIMIT 24
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getCurrentPayPeriod()
    {
        $stmt = $this->db->query("
            SELECT * FROM pay_periods
            WHERE start_date <= CURDATE() AND end_date >= CURDATE()
            LIMIT 1
        ");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getPayrollRuns($periodId)
    {
        if (!$periodId) return [];

        $stmt = $this->db->prepare("
            SELECT * FROM payroll_runs WHERE pay_period_id = ? ORDER BY run_number
        ");
        $stmt->execute([$periodId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getPayslips($periodId)
    {
        $sql = "
            SELECT ps.*, s.first_name, s.last_name, s.employee_number,
                   hd.department_name, pp.period_name
            FROM payslips ps
            JOIN staff s ON ps.staff_id = s.id
            JOIN payroll_runs pr ON ps.payroll_run_id = pr.id
            JOIN pay_periods pp ON pr.pay_period_id = pp.id
            LEFT JOIN hr_departments hd ON s.department_id = hd.id
        ";

        if ($periodId) {
            $sql .= " WHERE pr.pay_period_id = ?";
            $stmt = $this->db->prepare($sql . " ORDER BY s.first_name, s.last_name");
            $stmt->execute([$periodId]);
        } else {
            $stmt = $this->db->query($sql . " ORDER BY ps.created_at DESC LIMIT 100");
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getLoanTypes()
    {
        $stmt = $this->db->query("
            SELECT * FROM loan_types WHERE is_active = 1 ORDER BY loan_name
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getPendingLoans()
    {
        $stmt = $this->db->query("
            SELECT sl.*, s.first_name, s.last_name, s.employee_number,
                   lt.loan_name
            FROM staff_loans sl
            JOIN staff s ON sl.staff_id = s.id
            JOIN loan_types lt ON sl.loan_type_id = lt.id
            WHERE sl.status = 'pending'
            ORDER BY sl.requested_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getActiveLoans()
    {
        $stmt = $this->db->query("
            SELECT sl.*, s.first_name, s.last_name, s.employee_number,
                   lt.loan_name
            FROM staff_loans sl
            JOIN staff s ON sl.staff_id = s.id
            JOIN loan_types lt ON sl.loan_type_id = lt.id
            WHERE sl.status = 'active'
            ORDER BY sl.start_date DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getSchoolCountryId()
    {
        $stmt = $this->db->query("SELECT country_id FROM school_profile LIMIT 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['country_id'] ?? 1; // Default to Kenya
    }

    private function getStatutoryFunds()
    {
        $countryId = $this->getSchoolCountryId();
        $stmt = $this->db->prepare("
            SELECT * FROM statutory_funds WHERE country_id = ? AND is_active = 1 ORDER BY fund_name
        ");
        $stmt->execute([$countryId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getStatutoryFundsWithRates($countryId)
    {
        $stmt = $this->db->prepare("
            SELECT sf.*, sfr.employee_rate_type, sfr.employee_rate, sfr.employee_fixed,
                   sfr.employer_rate_type, sfr.employer_rate, sfr.employer_fixed,
                   sfr.employee_max, sfr.employer_max, sfr.calculation_basis
            FROM statutory_funds sf
            LEFT JOIN statutory_fund_rates sfr ON sf.id = sfr.fund_id AND sfr.is_current = 1
            WHERE sf.country_id = ? AND sf.is_active = 1
            ORDER BY sf.fund_name
        ");
        $stmt->execute([$countryId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getTaxBrackets($countryId)
    {
        $stmt = $this->db->prepare("
            SELECT tb.*,
                   (SELECT GROUP_CONCAT(
                       CONCAT(band_order, ':', min_amount, '-', COALESCE(max_amount, 'MAX'), '@', rate, '%')
                       ORDER BY band_order SEPARATOR '; '
                   ) FROM tax_bands WHERE bracket_id = tb.id) as bands
            FROM tax_brackets tb
            WHERE tb.country_id = ? AND tb.is_current = 1
        ");
        $stmt->execute([$countryId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getTaxReliefs($countryId)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM tax_reliefs WHERE country_id = ? AND is_active = 1 ORDER BY relief_name
        ");
        $stmt->execute([$countryId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getBanksList()
    {
        // Common Kenyan banks
        return [
            'KCB Bank',
            'Equity Bank',
            'Co-operative Bank',
            'NCBA Bank',
            'Absa Bank Kenya',
            'Standard Chartered Bank',
            'Stanbic Bank',
            'I&M Bank',
            'Diamond Trust Bank',
            'Family Bank',
            'Bank of Africa',
            'Prime Bank',
            'Sidian Bank',
            'Other'
        ];
    }

    // =========================================================================
    // Lookup Table Methods (Phase 3 - Normalized Dropdowns)
    // =========================================================================

    private function getCountries()
    {
        $stmt = $this->db->query("
            SELECT id, country_code, country_name, nationality
            FROM countries
            WHERE is_active = 1
            ORDER BY country_name
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getBanks()
    {
        $stmt = $this->db->query("
            SELECT id, bank_code, bank_name, swift_code
            FROM banks
            WHERE is_active = 1
            ORDER BY sort_order, bank_name
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getBloodGroups()
    {
        $stmt = $this->db->query("
            SELECT id, blood_group, description
            FROM blood_groups
            WHERE is_active = 1
            ORDER BY sort_order
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getStaffTypes()
    {
        $stmt = $this->db->query("
            SELECT id, type_code, type_name, description
            FROM staff_types
            WHERE is_active = 1
            ORDER BY sort_order
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getEmploymentTypes()
    {
        $stmt = $this->db->query("
            SELECT id, type_code, type_name, description, requires_contract_dates
            FROM employment_types
            WHERE is_active = 1
            ORDER BY sort_order
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getWorkSchedules()
    {
        $stmt = $this->db->query("
            SELECT id, schedule_code, schedule_name, description, start_time, end_time
            FROM work_schedules
            WHERE is_active = 1
            ORDER BY sort_order
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getPaymentModes()
    {
        $stmt = $this->db->query("
            SELECT id, mode_code, mode_name, description, requires_bank_details
            FROM payment_modes
            WHERE is_active = 1
            ORDER BY sort_order
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getEducationLevels()
    {
        $stmt = $this->db->query("
            SELECT id, level_code, level_name, rank_order
            FROM education_levels
            WHERE is_active = 1
            ORDER BY sort_order
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getLeavingReasons()
    {
        $stmt = $this->db->query("
            SELECT id, reason_code, reason_name, category
            FROM leaving_reasons
            WHERE is_active = 1
            ORDER BY sort_order
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getMaritalStatuses()
    {
        $stmt = $this->db->query("
            SELECT id, status_code, status_name
            FROM marital_statuses
            WHERE is_active = 1
            ORDER BY sort_order
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getReligions()
    {
        $stmt = $this->db->query("
            SELECT id, religion_name
            FROM religions
            WHERE is_active = 1
            ORDER BY sort_order
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getRelationshipTypes($category)
    {
        $stmt = $this->db->prepare("
            SELECT id, relationship_name
            FROM relationship_types
            WHERE category = ? AND is_active = 1
            ORDER BY sort_order
        ");
        $stmt->execute([$category]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getDocumentTypes()
    {
        $stmt = $this->db->query("
            SELECT id, type_code, type_name, category, is_required
            FROM staff_document_types
            WHERE is_active = 1
            ORDER BY sort_order
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // Staff Document Upload Methods
    // =========================================================================

    /**
     * Generate upload token for phone camera capture
     */
    public function generateDocumentUploadToken()
    {
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $staffId = $data['staff_id'] ?? null;
            $documentType = $data['document_type'] ?? null;

            if (!$staffId || !$documentType) {
                echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
                return;
            }

            // Verify staff exists
            $stmt = $this->db->prepare("SELECT id FROM staff WHERE id = ?");
            $stmt->execute([$staffId]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Staff member not found']);
                return;
            }

            // Generate unique token
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

            // Store token
            $stmt = $this->db->prepare("
                INSERT INTO document_upload_tokens (
                    token, staff_id, document_type, created_by, expires_at, status, created_at
                ) VALUES (?, ?, ?, ?, ?, 'pending', NOW())
            ");
            $stmt->execute([
                $token,
                $staffId,
                $documentType,
                $_SESSION['user_id'] ?? 0,
                $expiresAt
            ]);

            echo json_encode(['success' => true, 'token' => $token]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Check upload status for polling
     */
    public function checkDocumentUploadStatus($token)
    {
        header('Content-Type: application/json');

        try {
            $stmt = $this->db->prepare("
                SELECT status, expires_at FROM document_upload_tokens WHERE token = ?
            ");
            $stmt->execute([$token]);
            $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$tokenData) {
                echo json_encode(['status' => 'not_found']);
                return;
            }

            if (strtotime($tokenData['expires_at']) < time() && $tokenData['status'] === 'pending') {
                echo json_encode(['status' => 'expired']);
                return;
            }

            echo json_encode(['status' => $tokenData['status']]);

        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Show phone capture page for document upload
     */
    public function showPhoneCapture($token)
    {
        // Verify token
        $stmt = $this->db->prepare("
            SELECT t.*, s.first_name, s.last_name, dt.type_name as document_label
            FROM document_upload_tokens t
            JOIN staff s ON t.staff_id = s.id
            LEFT JOIN staff_document_types dt ON t.document_type = dt.type_code
            WHERE t.token = ? AND t.status = 'pending'
        ");
        $stmt->execute([$token]);
        $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tokenData) {
            $error = 'Invalid or expired upload link';
            require __DIR__ . '/../Views/hr-payroll/phone_capture.php';
            return;
        }

        if (strtotime($tokenData['expires_at']) < time()) {
            $error = 'This upload link has expired. Please generate a new one.';
            require __DIR__ . '/../Views/hr-payroll/phone_capture.php';
            return;
        }

        $uploadToken = $token;
        $documentLabel = $tokenData['document_label'] ?? ucwords(str_replace('_', ' ', $tokenData['document_type']));
        $staffName = $tokenData['first_name'] . ' ' . $tokenData['last_name'];
        $schoolName = $_SESSION['school_name'] ?? 'School';

        require __DIR__ . '/../Views/hr-payroll/phone_capture.php';
    }

    /**
     * Upload document from phone camera
     */
    public function uploadDocumentFromPhone()
    {
        header('Content-Type: application/json');

        try {
            $token = $_POST['upload_token'] ?? '';
            $file = $_FILES['document_file'] ?? null;

            if (!$token || !$file) {
                echo json_encode(['success' => false, 'message' => 'Missing token or file']);
                return;
            }

            // Verify token
            $stmt = $this->db->prepare("
                SELECT * FROM document_upload_tokens
                WHERE token = ? AND status = 'pending'
            ");
            $stmt->execute([$token]);
            $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$tokenData) {
                echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
                return;
            }

            if (strtotime($tokenData['expires_at']) < time()) {
                echo json_encode(['success' => false, 'message' => 'Token has expired']);
                return;
            }

            // Validate file
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            if (!in_array($file['type'], $allowedTypes)) {
                echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPEG and PNG allowed.']);
                return;
            }

            $maxSize = 5 * 1024 * 1024; // 5MB
            if ($file['size'] > $maxSize) {
                echo json_encode(['success' => false, 'message' => 'File too large. Maximum 5MB allowed.']);
                return;
            }

            // Save file
            $staffId = $tokenData['staff_id'];
            $uploadDir = __DIR__ . '/../../storage/documents/staff/' . $staffId;
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $extension = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
            $filename = $tokenData['document_type'] . '_' . time() . '.' . $extension;
            $filepath = $uploadDir . '/' . $filename;

            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                echo json_encode(['success' => false, 'message' => 'Failed to save file']);
                return;
            }

            $this->db->beginTransaction();

            // Insert document record
            $stmt = $this->db->prepare("
                INSERT INTO staff_documents (
                    staff_id, document_type, file_name, document_name, file_path,
                    file_size, mime_type, verification_status, upload_method,
                    uploaded_by, uploaded_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'uploaded', 'phone', ?, NOW())
            ");

            $documentName = ucwords(str_replace('_', ' ', $tokenData['document_type']));
            $stmt->execute([
                $staffId,
                $tokenData['document_type'],
                $filename,
                $documentName,
                'storage/documents/staff/' . $staffId . '/' . $filename,
                $file['size'],
                $file['type'],
                $tokenData['created_by']
            ]);

            // Mark token as completed
            $stmt = $this->db->prepare("
                UPDATE document_upload_tokens
                SET status = 'completed', completed_at = NOW()
                WHERE token = ?
            ");
            $stmt->execute([$token]);

            $this->db->commit();

            echo json_encode(['success' => true, 'message' => 'Document uploaded successfully']);

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Upload document from computer
     */
    public function uploadDocument()
    {
        try {
            $staffId = $_POST['staff_id'] ?? null;
            $documentType = $_POST['document_type'] ?? null;
            $notes = $_POST['notes'] ?? null;
            $file = $_FILES['document_file'] ?? null;

            if (!$staffId || !$documentType || !$file) {
                $_SESSION['flash_error'] = 'Missing required parameters';
                header("Location: /hr-payroll/staff/{$staffId}/edit?tab=tab-documents");
                exit;
            }

            // Validate file
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
            if (!in_array($file['type'], $allowedTypes)) {
                $_SESSION['flash_error'] = 'Invalid file type. Only JPEG, PNG and PDF allowed.';
                header("Location: /hr-payroll/staff/{$staffId}/edit?tab=tab-documents");
                exit;
            }

            $maxSize = 5 * 1024 * 1024; // 5MB
            if ($file['size'] > $maxSize) {
                $_SESSION['flash_error'] = 'File too large. Maximum 5MB allowed.';
                header("Location: /hr-payroll/staff/{$staffId}/edit?tab=tab-documents");
                exit;
            }

            // Save file
            $uploadDir = __DIR__ . '/../../storage/documents/staff/' . $staffId;
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = $documentType . '_' . time() . '.' . $extension;
            $filepath = $uploadDir . '/' . $filename;

            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                $_SESSION['flash_error'] = 'Failed to save file';
                header("Location: /hr-payroll/staff/{$staffId}/edit?tab=tab-documents");
                exit;
            }

            // Insert document record
            $stmt = $this->db->prepare("
                INSERT INTO staff_documents (
                    staff_id, document_type, file_name, document_name, file_path,
                    file_size, mime_type, notes, verification_status, upload_method,
                    uploaded_by, uploaded_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'uploaded', 'computer', ?, NOW())
            ");

            $documentName = ucwords(str_replace('_', ' ', $documentType));
            $stmt->execute([
                $staffId,
                $documentType,
                $filename,
                $documentName,
                'storage/documents/staff/' . $staffId . '/' . $filename,
                $file['size'],
                $file['type'],
                $notes,
                $_SESSION['user_id'] ?? 0
            ]);

            $_SESSION['flash_success'] = 'Document uploaded successfully';
            header("Location: /hr-payroll/staff/{$staffId}/edit?tab=tab-documents");
            exit;

        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'Error uploading document: ' . $e->getMessage();
            header("Location: /hr-payroll/staff/{$staffId}/edit?tab=tab-documents");
            exit;
        }
    }

    /**
     * Delete document
     */
    public function deleteDocument()
    {
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $documentId = $data['document_id'] ?? null;

            if (!$documentId) {
                echo json_encode(['success' => false, 'message' => 'Document ID required']);
                return;
            }

            // Get document info
            $stmt = $this->db->prepare("SELECT * FROM staff_documents WHERE id = ?");
            $stmt->execute([$documentId]);
            $document = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$document) {
                echo json_encode(['success' => false, 'message' => 'Document not found']);
                return;
            }

            // Delete physical file
            $fullPath = __DIR__ . '/../../' . $document['file_path'];
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            // Delete database record
            $stmt = $this->db->prepare("DELETE FROM staff_documents WHERE id = ?");
            $stmt->execute([$documentId]);

            echo json_encode(['success' => true, 'message' => 'Document deleted successfully']);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Download document
     */
    public function downloadDocument($documentId)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM staff_documents WHERE id = ?");
            $stmt->execute([$documentId]);
            $document = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$document) {
                $_SESSION['flash_error'] = 'Document not found';
                header("Location: /hr-payroll/staff");
                exit;
            }

            $fullPath = __DIR__ . '/../../' . $document['file_path'];
            if (!file_exists($fullPath)) {
                $_SESSION['flash_error'] = 'File not found on server';
                header("Location: /hr-payroll/staff/{$document['staff_id']}/edit?tab=tab-documents");
                exit;
            }

            header('Content-Type: ' . ($document['mime_type'] ?? 'application/octet-stream'));
            header('Content-Disposition: attachment; filename="' . $document['file_name'] . '"');
            header('Content-Length: ' . filesize($fullPath));
            readfile($fullPath);
            exit;

        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'Error downloading document';
            header("Location: /hr-payroll/staff");
            exit;
        }
    }

    /**
     * Upload staff photo (with QR code support)
     */
    public function uploadStaffPhotoAjax()
    {
        header('Content-Type: application/json');

        try {
            $staffId = $_POST['staff_id'] ?? null;
            $file = $_FILES['photo'] ?? null;

            if (!$staffId || !$file) {
                echo json_encode(['success' => false, 'message' => 'Missing staff ID or photo']);
                return;
            }

            // Validate file
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            if (!in_array($file['type'], $allowedTypes)) {
                echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPEG and PNG allowed.']);
                return;
            }

            $this->uploadStaffPhoto($staffId, $file);

            // Get updated photo URL
            $stmt = $this->db->prepare("SELECT photo FROM staff WHERE id = ?");
            $stmt->execute([$staffId]);
            $staff = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'message' => 'Photo uploaded successfully',
                'photo_url' => $staff['photo'] ?? null
            ]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
