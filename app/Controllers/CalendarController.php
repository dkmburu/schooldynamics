<?php
/**
 * Calendar Controller
 * Manages academic calendar, terms, and important dates
 */

class CalendarController
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getTenantConnection();
    }

    // =============================================================================
    // MAIN CALENDAR VIEW
    // =============================================================================

    /**
     * Main calendar dashboard - shows current academic year calendar
     */
    public function index()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        $tenantId = $_SESSION['tenant_id'] ?? null;
        $currentYear = date('Y');

        // Get current term first to determine academic year
        $currentTerm = $this->getCurrentTerm($tenantId);

        // Get current academic year or default to current term's year, or fall back to current year
        $academicYear = $_GET['year'] ?? ($currentTerm['academic_year'] ?? $currentYear . '/' . ($currentYear + 1));

        // Get all terms for this academic year
        $terms = $this->getAcademicTerms($tenantId, $academicYear);

        // Get national holidays for current and next year
        $holidays = $this->getNationalHolidays($currentYear);

        // Get calendar settings
        $settings = $this->getCalendarSettings($tenantId, $academicYear);

        Response::view('calendar.index', [
            'pageTitle' => 'Academic Calendar',
            'currentPage' => 'calendar',
            'academicYear' => $academicYear,
            'terms' => $terms,
            'currentTerm' => $currentTerm,
            'holidays' => $holidays,
            'settings' => $settings
        ]);
    }

    // =============================================================================
    // ACADEMIC TERMS MANAGEMENT
    // =============================================================================

    /**
     * List all academic terms
     */
    public function terms()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        $tenantId = $_SESSION['tenant_id'] ?? null;
        $academicYear = $_GET['year'] ?? null;

        // Get all academic years for dropdown
        $years = $this->getAcademicYears($tenantId);

        // Get terms (filtered by year if provided)
        $terms = $this->getAcademicTerms($tenantId, $academicYear);

        Response::view('calendar.terms', [
            'pageTitle' => 'Academic Terms',
            'currentPage' => 'calendar',
            'years' => $years,
            'terms' => $terms,
            'selectedYear' => $academicYear
        ]);
    }

    /**
     * Show form to create new academic term
     */
    public function createTerm()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        $tenantId = $_SESSION['tenant_id'] ?? null;

        // Get campuses if multi-campus
        $campuses = $this->getCampuses($tenantId);

        Response::view('calendar.term_form', [
            'pageTitle' => 'Create Academic Term',
            'currentPage' => 'calendar',
            'term' => null,
            'campuses' => $campuses
        ]);
    }

    /**
     * Store new academic term
     */
    public function storeTerm()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        $tenantId = $_SESSION['tenant_id'] ?? null;
        $userId = $_SESSION['user_id'] ?? null;

        $data = [
            'school_id' => $tenantId,
            'campus_id' => $_POST['campus_id'] ?: null,
            'academic_year' => $_POST['academic_year'],
            'term_number' => $_POST['term_number'],
            'term_name' => $_POST['term_name'],
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'status' => $_POST['status'] ?? 'draft',
            'notes' => $_POST['notes'] ?? null,
            'created_by' => $userId
        ];

        // Validate dates
        if (strtotime($data['end_date']) <= strtotime($data['start_date'])) {
            flash('error', 'End date must be after start date');
            Response::redirect('/calendar/terms/create');
        }

        // Check for overlapping terms
        if ($this->hasOverlappingTerms($tenantId, $data['campus_id'], $data['start_date'], $data['end_date'])) {
            flash('error', 'This term overlaps with an existing term');
            Response::redirect('/calendar/terms/create');
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO terms
            (school_id, campus_id, academic_year, term_number, term_name, start_date, end_date, status, notes, created_by)
            VALUES (:school_id, :campus_id, :academic_year, :term_number, :term_name, :start_date, :end_date, :status, :notes, :created_by)
        ");

        if ($stmt->execute($data)) {
            $termId = $this->pdo->lastInsertId();

            // If status is 'current', update other terms to not current
            if ($data['status'] === 'current') {
                $this->setCurrentTerm($tenantId, $termId);
            }

            flash('success', 'Academic term created successfully');
            Response::redirect('/calendar/terms/' . $termId);
        } else {
            flash('error', 'Failed to create academic term');
            Response::redirect('/calendar/terms/create');
        }
    }

    /**
     * Show single term with important dates
     */
    public function showTerm($id)
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        $tenantId = $_SESSION['tenant_id'] ?? null;

        $term = $this->getTermById($id, $tenantId);

        if (!$term) {
            flash('error', 'Term not found');
            Response::redirect('/calendar/terms');
        }

        // Get important dates for this term
        $importantDates = $this->getTermImportantDates($id);

        // Get date types for dropdown
        $dateTypes = $this->getTermDateTypes();

        Response::view('calendar.term_detail', [
            'pageTitle' => $term['term_name'] . ' - ' . $term['academic_year'],
            'currentPage' => 'calendar',
            'term' => $term,
            'importantDates' => $importantDates,
            'dateTypes' => $dateTypes
        ]);
    }

    /**
     * Edit academic term
     */
    public function editTerm($id)
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        $tenantId = $_SESSION['tenant_id'] ?? null;

        $term = $this->getTermById($id, $tenantId);

        if (!$term) {
            flash('error', 'Term not found');
            Response::redirect('/calendar/terms');
        }

        $campuses = $this->getCampuses($tenantId);

        Response::view('calendar.term_form', [
            'pageTitle' => 'Edit Academic Term',
            'currentPage' => 'calendar',
            'term' => $term,
            'campuses' => $campuses
        ]);
    }

    /**
     * Update academic term
     */
    public function updateTerm($id)
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        $tenantId = $_SESSION['tenant_id'] ?? null;

        $term = $this->getTermById($id, $tenantId);

        if (!$term) {
            flash('error', 'Term not found');
            Response::redirect('/calendar/terms');
        }

        $data = [
            'campus_id' => $_POST['campus_id'] ?: null,
            'academic_year' => $_POST['academic_year'],
            'term_number' => $_POST['term_number'],
            'term_name' => $_POST['term_name'],
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'status' => $_POST['status'] ?? 'draft',
            'notes' => $_POST['notes'] ?? null,
            'id' => $id
        ];

        // Validate dates
        if (strtotime($data['end_date']) <= strtotime($data['start_date'])) {
            flash('error', 'End date must be after start date');
            Response::redirect('/calendar/terms/' . $id . '/edit');
        }

        // Check for overlapping terms (excluding current term)
        if ($this->hasOverlappingTerms($tenantId, $data['campus_id'], $data['start_date'], $data['end_date'], $id)) {
            flash('error', 'This term overlaps with an existing term');
            Response::redirect('/calendar/terms/' . $id . '/edit');
        }

        $stmt = $this->pdo->prepare("
            UPDATE terms
            SET campus_id = :campus_id,
                academic_year = :academic_year,
                term_number = :term_number,
                term_name = :term_name,
                start_date = :start_date,
                end_date = :end_date,
                status = :status,
                notes = :notes
            WHERE id = :id AND school_id = :school_id
        ");

        $data['school_id'] = $tenantId;

        if ($stmt->execute($data)) {
            // If status is 'current', update other terms to not current
            if ($data['status'] === 'current') {
                $this->setCurrentTerm($tenantId, $id);
            }

            flash('success', 'Academic term updated successfully');
            Response::redirect('/calendar/terms/' . $id);
        } else {
            flash('error', 'Failed to update academic term');
            Response::redirect('/calendar/terms/' . $id . '/edit');
        }
    }

    /**
     * Delete academic term
     */
    public function deleteTerm($id)
    {
        if (!isAuthenticated()) {
            Response::json(['success' => false, 'message' => 'Not authenticated']);
            return;
        }

        $tenantId = $_SESSION['tenant_id'] ?? null;

        $term = $this->getTermById($id, $tenantId);

        if (!$term) {
            Response::json(['success' => false, 'message' => 'Term not found']);
            return;
        }

        // Check if term is current
        if ($term['is_current']) {
            Response::json(['success' => false, 'message' => 'Cannot delete current term']);
            return;
        }

        $stmt = $this->pdo->prepare("DELETE FROM terms WHERE id = ? AND school_id = ?");

        if ($stmt->execute([$id, $tenantId])) {
            Response::json(['success' => true, 'message' => 'Term deleted successfully']);
        } else {
            Response::json(['success' => false, 'message' => 'Failed to delete term']);
        }
    }

    // =============================================================================
    // IMPORTANT DATES MANAGEMENT
    // =============================================================================

    /**
     * Add important date to term
     */
    public function addImportantDate($termId)
    {
        if (!isAuthenticated()) {
            Response::json(['success' => false, 'message' => 'Not authenticated']);
            return;
        }

        $tenantId = $_SESSION['tenant_id'] ?? null;
        $userId = $_SESSION['user_id'] ?? null;

        $term = $this->getTermById($termId, $tenantId);

        if (!$term) {
            Response::json(['success' => false, 'message' => 'Term not found']);
            return;
        }

        $data = [
            'term_id' => $termId,
            'date_type_id' => $_POST['date_type_id'],
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'] ?: null,
            'title' => $_POST['title'],
            'description' => $_POST['description'] ?? null,
            'affects_timetable' => isset($_POST['affects_timetable']) ? 1 : 0,
            'is_school_open' => isset($_POST['is_school_open']) ? 1 : 0,
            'created_by' => $userId
        ];

        // Validate dates are within term
        if (strtotime($data['start_date']) < strtotime($term['start_date']) ||
            strtotime($data['start_date']) > strtotime($term['end_date'])) {
            Response::json(['success' => false, 'message' => 'Date must be within term dates']);
            return;
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO term_important_dates
            (term_id, date_type_id, start_date, end_date, title, description, affects_timetable, is_school_open, created_by)
            VALUES (:term_id, :date_type_id, :start_date, :end_date, :title, :description, :affects_timetable, :is_school_open, :created_by)
        ");

        if ($stmt->execute($data)) {
            Response::json(['success' => true, 'message' => 'Important date added successfully']);
        } else {
            Response::json(['success' => false, 'message' => 'Failed to add important date']);
        }
    }

    /**
     * Update important date
     */
    public function updateImportantDate($id)
    {
        if (!isAuthenticated()) {
            Response::json(['success' => false, 'message' => 'Not authenticated']);
            return;
        }

        $tenantId = $_SESSION['tenant_id'] ?? null;

        // Verify the date belongs to this school
        $stmt = $this->pdo->prepare("
            SELECT tid.*, t.school_id
            FROM term_important_dates tid
            JOIN terms t ON tid.term_id = t.id
            WHERE tid.id = ? AND t.school_id = ?
        ");
        $stmt->execute([$id, $tenantId]);
        $existingDate = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existingDate) {
            Response::json(['success' => false, 'message' => 'Date not found']);
            return;
        }

        $data = [
            'date_type_id' => $_POST['date_type_id'],
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'] ?: null,
            'title' => $_POST['title'],
            'description' => $_POST['description'] ?? null,
            'affects_timetable' => isset($_POST['affects_timetable']) ? 1 : 0,
            'is_school_open' => isset($_POST['is_school_open']) ? 1 : 0,
            'id' => $id
        ];

        $stmt = $this->pdo->prepare("
            UPDATE term_important_dates
            SET date_type_id = :date_type_id,
                start_date = :start_date,
                end_date = :end_date,
                title = :title,
                description = :description,
                affects_timetable = :affects_timetable,
                is_school_open = :is_school_open
            WHERE id = :id
        ");

        if ($stmt->execute($data)) {
            Response::json(['success' => true, 'message' => 'Important date updated successfully']);
        } else {
            Response::json(['success' => false, 'message' => 'Failed to update important date']);
        }
    }

    /**
     * Delete important date
     */
    public function deleteImportantDate($id)
    {
        if (!isAuthenticated()) {
            Response::json(['success' => false, 'message' => 'Not authenticated']);
            return;
        }

        $tenantId = $_SESSION['tenant_id'] ?? null;

        // Verify the date belongs to this school
        $stmt = $this->pdo->prepare("
            SELECT tid.id
            FROM term_important_dates tid
            JOIN terms t ON tid.term_id = t.id
            WHERE tid.id = ? AND t.school_id = ?
        ");
        $stmt->execute([$id, $tenantId]);

        if (!$stmt->fetch()) {
            Response::json(['success' => false, 'message' => 'Date not found']);
            return;
        }

        $stmt = $this->pdo->prepare("DELETE FROM term_important_dates WHERE id = ?");

        if ($stmt->execute([$id])) {
            Response::json(['success' => true, 'message' => 'Important date deleted successfully']);
        } else {
            Response::json(['success' => false, 'message' => 'Failed to delete important date']);
        }
    }

    // =============================================================================
    // NATIONAL HOLIDAYS
    // =============================================================================

    /**
     * List national holidays
     */
    public function holidays()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        $year = $_GET['year'] ?? date('Y');

        $holidays = $this->getNationalHolidays($year);
        $holidayTypes = $this->getNationalHolidayTypes();

        Response::view('calendar.holidays', [
            'pageTitle' => 'National Holidays',
            'currentPage' => 'calendar',
            'holidays' => $holidays,
            'holidayTypes' => $holidayTypes,
            'selectedYear' => $year
        ]);
    }

    // =============================================================================
    // HELPER METHODS
    // =============================================================================

    private function getAcademicTerms($tenantId, $academicYear = null)
    {
        $sql = "SELECT * FROM terms WHERE school_id = ?";
        $params = [$tenantId];

        if ($academicYear) {
            $sql .= " AND academic_year = ?";
            $params[] = $academicYear;
        }

        $sql .= " ORDER BY start_date DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getCurrentTerm($tenantId)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM terms
            WHERE school_id = ? AND is_current = 1
            LIMIT 1
        ");
        $stmt->execute([$tenantId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getTermById($id, $tenantId)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM terms
            WHERE id = ? AND school_id = ?
        ");
        $stmt->execute([$id, $tenantId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getTermImportantDates($termId)
    {
        $stmt = $this->pdo->prepare("
            SELECT tid.*, tdt.name as type_name, tdt.category, tdt.color, tdt.icon
            FROM term_important_dates tid
            JOIN term_date_types tdt ON tid.date_type_id = tdt.id
            WHERE tid.term_id = ?
            ORDER BY tid.start_date ASC
        ");
        $stmt->execute([$termId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getTermDateTypes()
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM term_date_types
            WHERE is_active = 1
            ORDER BY category, sort_order
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getNationalHolidays($year)
    {
        $stmt = $this->pdo->prepare("
            SELECT nh.*, nht.name as type_name
            FROM national_holidays nh
            JOIN national_holiday_types nht ON nh.holiday_type_id = nht.id
            WHERE nh.year = ? AND nh.is_active = 1
            ORDER BY nh.holiday_date ASC
        ");
        $stmt->execute([$year]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getNationalHolidayTypes()
    {
        $stmt = $this->pdo->prepare("SELECT * FROM national_holiday_types WHERE is_active = 1");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getAcademicYears($tenantId)
    {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT academic_year
            FROM terms
            WHERE school_id = ?
            ORDER BY academic_year DESC
        ");
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function getCampuses($tenantId)
    {
        // Assuming there's a campuses table
        $stmt = $this->pdo->prepare("SELECT * FROM campuses WHERE school_id = ? AND is_active = 1");
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getCalendarSettings($tenantId, $academicYear)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM academic_calendar_settings
            WHERE school_id = ? AND academic_year = ?
        ");
        $stmt->execute([$tenantId, $academicYear]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function hasOverlappingTerms($tenantId, $campusId, $startDate, $endDate, $excludeTermId = null)
    {
        $sql = "
            SELECT COUNT(*)
            FROM terms
            WHERE school_id = ?
            AND (campus_id = ? OR campus_id IS NULL OR ? IS NULL)
            AND (
                (start_date BETWEEN ? AND ?)
                OR (end_date BETWEEN ? AND ?)
                OR (? BETWEEN start_date AND end_date)
                OR (? BETWEEN start_date AND end_date)
            )
        ";

        $params = [$tenantId, $campusId, $campusId, $startDate, $endDate, $startDate, $endDate, $startDate, $endDate];

        if ($excludeTermId) {
            $sql .= " AND id != ?";
            $params[] = $excludeTermId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }

    private function setCurrentTerm($tenantId, $termId)
    {
        // Set all terms to not current
        $stmt = $this->pdo->prepare("UPDATE terms SET is_current = 0 WHERE school_id = ?");
        $stmt->execute([$tenantId]);

        // Set specified term as current
        $stmt = $this->pdo->prepare("UPDATE terms SET is_current = 1 WHERE id = ? AND school_id = ?");
        $stmt->execute([$termId, $tenantId]);
    }
}
