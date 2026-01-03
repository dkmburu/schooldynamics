<?php
/**
 * Finance Controller
 * Manages all finance module functionality
 */

class FinanceController
{
    /**
     * Finance Dashboard
     */
    public function index()
    {
        if (!isAuthenticated()) {
            flash('error', 'Please login to access this page.');
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            // Use tenant_id as school_id (they are the same in this multi-tenant setup)
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;

            // Get current academic year and term
            $stmt = $pdo->query("SELECT id, year_name FROM academic_years WHERE is_current = 1 LIMIT 1");
            $currentYear = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['id' => null, 'year_name' => 'Not Set'];

            $stmt = $pdo->query("SELECT id, term_name FROM terms WHERE is_current = 1 LIMIT 1");
            $currentTerm = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['id' => null, 'term_name' => 'Not Set'];

            $stats = [];

            // Total invoiced for current term
            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(total_amount), 0) as total
                FROM invoices
                WHERE school_id = ?
                AND academic_year_id = ?
            ");
            $stmt->execute([$schoolId, $currentYear['id']]);
            $stats['total_invoiced'] = $stmt->fetchColumn() ?: 0;

            // Total collected (confirmed/completed payments)
            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(amount), 0) as total
                FROM payments
                WHERE school_id = ?
                AND status IN ('completed', 'confirmed')
            ");
            $stmt->execute([$schoolId]);
            $stats['total_collected'] = $stmt->fetchColumn() ?: 0;

            // Total outstanding (all accounts with balance > 0)
            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(current_balance), 0) as total,
                       COUNT(*) as count
                FROM student_fee_accounts
                WHERE school_id = ? AND current_balance > 0
            ");
            $stmt->execute([$schoolId]);
            $outstandingData = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['total_outstanding'] = $outstandingData['total'] ?? 0;
            $stats['students_with_balance'] = $outstandingData['count'] ?? 0;

            // Collection rate
            if ($stats['total_invoiced'] > 0) {
                $stats['collection_rate'] = ($stats['total_collected'] / $stats['total_invoiced']) * 100;
            } else {
                $stats['collection_rate'] = 0;
            }

            // Recent payments with payer info
            $stmt = $pdo->prepare("
                SELECT p.id, p.receipt_number, p.amount, p.payment_date, p.payment_method,
                    COALESCE(CONCAT(s.first_name, ' ', s.last_name), CONCAT(a.first_name, ' ', a.last_name)) as payer_name,
                    pm.name as method_name
                FROM payments p
                LEFT JOIN student_fee_accounts sfa ON sfa.id = p.student_fee_account_id
                LEFT JOIN students s ON s.id = sfa.student_id
                LEFT JOIN applicants a ON a.id = sfa.applicant_id
                LEFT JOIN payment_methods pm ON pm.code = p.payment_method
                WHERE p.school_id = ? AND p.status IN ('completed', 'confirmed')
                ORDER BY p.payment_date DESC, p.created_at DESC
                LIMIT 10
            ");
            $stmt->execute([$schoolId]);
            $stats['recent_payments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::view('finance/dashboard', [
                'pageTitle' => 'Finance Dashboard',
                'stats' => $stats,
                'current_year' => $currentYear,
                'current_term' => $currentTerm
            ]);
        } catch (Exception $e) {
            error_log('Finance Dashboard error: ' . $e->getMessage());
            flash('error', 'Failed to load dashboard: ' . $e->getMessage());
            Response::redirect('/dashboard');
        }
    }

    // ==================== CHART OF ACCOUNTS ====================

    public function chartOfAccounts()
    {
        if (!isAuthenticated()) {
            flash('error', 'Please login to access this page.');
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;

            $stmt = $pdo->prepare("
                SELECT * FROM chart_of_accounts
                WHERE school_id = ?
                ORDER BY account_type, account_code
            ");
            $stmt->execute([$schoolId]);
            $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Group accounts by type for the view tabs
            $accounts_by_type = [
                'asset' => [],
                'liability' => [],
                'equity' => [],
                'income' => [],
                'expense' => []
            ];

            foreach ($accounts as $account) {
                $type = $account['account_type'] ?? 'asset';
                if (isset($accounts_by_type[$type])) {
                    $accounts_by_type[$type][] = $account;
                }
            }

            Response::view('finance/chart_of_accounts', [
                'pageTitle' => 'Chart of Accounts',
                'accounts' => $accounts,
                'accounts_by_type' => $accounts_by_type
            ]);
        } catch (Exception $e) {
            error_log('Chart of Accounts error: ' . $e->getMessage());
            flash('error', 'Failed to load chart of accounts');
            Response::redirect('/finance');
        }
    }

    public function storeAccount()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;
            $userId = $_SESSION['user_id'] ?? null;

            $stmt = $pdo->prepare("
                INSERT INTO chart_of_accounts (school_id, account_code, account_name, account_type, parent_id, description, is_active, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $schoolId,
                $_POST['account_code'],
                $_POST['account_name'],
                $_POST['account_type'],
                $_POST['parent_id'] ?: null,
                $_POST['description'] ?? null,
                isset($_POST['is_active']) ? 1 : 0,
                $userId
            ]);

            flash('success', 'Account created successfully');
        } catch (Exception $e) {
            error_log('Store Account error: ' . $e->getMessage());
            flash('error', 'Failed to create account');
        }
        Response::redirect('/finance/chart-of-accounts');
    }

    public function updateAccount($id)
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;
            $userId = $_SESSION['user_id'] ?? null;

            $stmt = $pdo->prepare("
                UPDATE chart_of_accounts
                SET account_code = ?, account_name = ?, account_type = ?, parent_id = ?, description = ?, is_active = ?, updated_by = ?, updated_at = NOW()
                WHERE id = ? AND school_id = ?
            ");
            $stmt->execute([
                $_POST['account_code'],
                $_POST['account_name'],
                $_POST['account_type'],
                $_POST['parent_id'] ?: null,
                $_POST['description'] ?? null,
                isset($_POST['is_active']) ? 1 : 0,
                $userId,
                $id,
                $schoolId
            ]);

            flash('success', 'Account updated successfully');
        } catch (Exception $e) {
            error_log('Update Account error: ' . $e->getMessage());
            flash('error', 'Failed to update account');
        }
        Response::redirect('/finance/chart-of-accounts');
    }

    public function deleteAccount($id)
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;

            $stmt = $pdo->prepare("DELETE FROM chart_of_accounts WHERE id = ? AND school_id = ?");
            $stmt->execute([$id, $schoolId]);

            flash('success', 'Account deleted successfully');
        } catch (Exception $e) {
            error_log('Delete Account error: ' . $e->getMessage());
            flash('error', 'Failed to delete account');
        }
        Response::redirect('/finance/chart-of-accounts');
    }

    // ==================== FEE CATEGORIES ====================

    public function feeCategories()
    {
        if (!isAuthenticated()) {
            flash('error', 'Please login to access this page.');
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;

            $stmt = $pdo->prepare("
                SELECT fc.*,
                    (SELECT COUNT(*) FROM fee_items fi WHERE fi.fee_category_id = fc.id) as item_count
                FROM fee_categories fc
                WHERE fc.school_id = ?
                ORDER BY fc.sort_order, fc.name
            ");
            $stmt->execute([$schoolId]);
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get income accounts (type = 'income') for dropdown
            $stmt = $pdo->prepare("
                SELECT id, account_code, account_name FROM chart_of_accounts
                WHERE school_id = ? AND account_type = 'income' AND is_active = 1
                ORDER BY account_code
            ");
            $stmt->execute([$schoolId]);
            $income_accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get asset accounts (for accounts receivable) for dropdown
            $stmt = $pdo->prepare("
                SELECT id, account_code, account_name FROM chart_of_accounts
                WHERE school_id = ? AND account_type = 'asset' AND is_active = 1
                ORDER BY account_code
            ");
            $stmt->execute([$schoolId]);
            $ar_accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::view('finance/fee_categories', [
                'pageTitle' => 'Fee Categories',
                'categories' => $categories,
                'income_accounts' => $income_accounts,
                'ar_accounts' => $ar_accounts
            ]);
        } catch (Exception $e) {
            error_log('Fee Categories error: ' . $e->getMessage());
            flash('error', 'Failed to load fee categories');
            Response::redirect('/finance');
        }
    }

    public function storeFeeCategory()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;

            $stmt = $pdo->prepare("
                INSERT INTO fee_categories (school_id, code, name, category_type, description, is_refundable, is_active, sort_order, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $schoolId,
                $_POST['code'],
                $_POST['name'],
                $_POST['category_type'],
                $_POST['description'] ?? null,
                isset($_POST['is_refundable']) ? 1 : 0,
                isset($_POST['is_active']) ? 1 : 0,
                $_POST['sort_order'] ?? 0
            ]);

            flash('success', 'Fee category created successfully');
        } catch (Exception $e) {
            error_log('Store Fee Category error: ' . $e->getMessage());
            flash('error', 'Failed to create fee category');
        }
        Response::redirect('/finance/fee-categories');
    }

    public function updateFeeCategory($id)
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;

            $stmt = $pdo->prepare("
                UPDATE fee_categories
                SET code = ?, name = ?, category_type = ?, description = ?, is_refundable = ?, is_active = ?, sort_order = ?, updated_at = NOW()
                WHERE id = ? AND school_id = ?
            ");
            $stmt->execute([
                $_POST['code'],
                $_POST['name'],
                $_POST['category_type'],
                $_POST['description'] ?? null,
                isset($_POST['is_refundable']) ? 1 : 0,
                isset($_POST['is_active']) ? 1 : 0,
                $_POST['sort_order'] ?? 0,
                $id,
                $schoolId
            ]);

            flash('success', 'Fee category updated successfully');
        } catch (Exception $e) {
            error_log('Update Fee Category error: ' . $e->getMessage());
            flash('error', 'Failed to update fee category');
        }
        Response::redirect('/finance/fee-categories');
    }

    public function deleteFeeCategory($id)
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;

            // Check if category has items
            $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM fee_items WHERE fee_category_id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['cnt'] > 0) {
                flash('error', 'Cannot delete category with existing fee items');
                Response::redirect('/finance/fee-categories');
                return;
            }

            $stmt = $pdo->prepare("DELETE FROM fee_categories WHERE id = ? AND school_id = ?");
            $stmt->execute([$id, $schoolId]);

            flash('success', 'Fee category deleted successfully');
        } catch (Exception $e) {
            error_log('Delete Fee Category error: ' . $e->getMessage());
            flash('error', 'Failed to delete fee category');
        }
        Response::redirect('/finance/fee-categories');
    }

    // ==================== FEE ITEMS ====================

    public function feeItems()
    {
        if (!isAuthenticated()) {
            flash('error', 'Please login to access this page.');
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;

            $stmt = $pdo->prepare("
                SELECT fi.*, fc.name as category_name, fc.code as category_code, fc.category_type
                FROM fee_items fi
                LEFT JOIN fee_categories fc ON fc.id = fi.fee_category_id
                WHERE fi.school_id = ?
                ORDER BY fc.sort_order, fi.name
            ");
            $stmt->execute([$schoolId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("
                SELECT id, code, name, category_type FROM fee_categories
                WHERE school_id = ? AND is_active = 1
                ORDER BY sort_order, name
            ");
            $stmt->execute([$schoolId]);
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::view('finance/fee_items', [
                'pageTitle' => 'Fee Items',
                'items' => $items,
                'categories' => $categories
            ]);
        } catch (Exception $e) {
            error_log('Fee Items error: ' . $e->getMessage());
            flash('error', 'Failed to load fee items');
            Response::redirect('/finance');
        }
    }

    public function storeFeeItem()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;

            $stmt = $pdo->prepare("
                INSERT INTO fee_items (school_id, fee_category_id, code, name, description, default_amount, is_mandatory, is_recurring, frequency, is_active, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $schoolId,
                $_POST['fee_category_id'],
                $_POST['code'],
                $_POST['name'],
                $_POST['description'] ?? null,
                $_POST['default_amount'] ?? 0,
                isset($_POST['is_mandatory']) ? 1 : 0,
                isset($_POST['is_recurring']) ? 1 : 0,
                $_POST['frequency'] ?? 'term',
                isset($_POST['is_active']) ? 1 : 0
            ]);

            flash('success', 'Fee item created successfully');
        } catch (Exception $e) {
            error_log('Store Fee Item error: ' . $e->getMessage());
            flash('error', 'Failed to create fee item');
        }
        Response::redirect('/finance/fee-items');
    }

    public function updateFeeItem($id = null)
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        // Get ID from POST if not provided in URL
        $id = $id ?? $_POST['id'] ?? null;
        if (!$id) {
            flash('error', 'No fee item ID provided');
            Response::redirect('/finance/fee-items');
            return;
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;

            $stmt = $pdo->prepare("
                UPDATE fee_items
                SET fee_category_id = ?, code = ?, name = ?, description = ?, default_amount = ?, is_active = ?, updated_at = NOW()
                WHERE id = ? AND school_id = ?
            ");
            $stmt->execute([
                $_POST['fee_category_id'],
                strtoupper($_POST['code']),
                $_POST['name'],
                $_POST['description'] ?? null,
                $_POST['default_amount'] ?? 0,
                isset($_POST['is_active']) ? 1 : 0,
                $id,
                $schoolId
            ]);

            flash('success', 'Fee item updated successfully');
        } catch (Exception $e) {
            error_log('Update Fee Item error: ' . $e->getMessage());
            flash('error', 'Failed to update fee item');
        }
        Response::redirect('/finance/fee-items');
    }

    public function deleteFeeItem($id)
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;

            $stmt = $pdo->prepare("DELETE FROM fee_items WHERE id = ? AND school_id = ?");
            $stmt->execute([$id, $schoolId]);

            flash('success', 'Fee item deleted successfully');
        } catch (Exception $e) {
            error_log('Delete Fee Item error: ' . $e->getMessage());
            flash('error', 'Failed to delete fee item');
        }
        Response::redirect('/finance/fee-items');
    }

    // ==================== FEE STRUCTURES ====================

    public function feeStructures()
    {
        if (!isAuthenticated()) {
            flash('error', 'Please login to access this page.');
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;
            error_log("Fee Structures: Starting with schoolId = $schoolId");

            $stmt = $pdo->prepare("
                SELECT fs.*,
                    ay.year_name,
                    t.term_name,
                    g.grade_name,
                    c.campus_name,
                    (SELECT COUNT(*) FROM fee_structure_lines fsl WHERE fsl.fee_structure_id = fs.id) as line_count
                FROM fee_structures fs
                LEFT JOIN academic_years ay ON ay.id = fs.academic_year_id
                LEFT JOIN terms t ON t.id = fs.term_id
                LEFT JOIN grades g ON g.id = fs.grade_id
                LEFT JOIN campuses c ON c.id = fs.campus_id
                WHERE fs.school_id = ?
                ORDER BY ay.start_date DESC, g.sort_order, fs.name
            ");
            $stmt->execute([$schoolId]);
            $structures = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Fee Structures: Got " . count($structures) . " structures");

            // academic_years has no school_id column
            $stmt = $pdo->query("SELECT id, year_name, is_current FROM academic_years ORDER BY start_date DESC");
            $years = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Fee Structures: Got " . count($years) . " years");

            // Get grades (not grade groups)
            $stmt = $pdo->query("SELECT id, grade_name FROM grades WHERE is_active = 1 ORDER BY sort_order");
            $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Fee Structures: Got " . count($grades) . " grades");

            // Get terms
            $stmt = $pdo->query("SELECT id, term_name FROM terms ORDER BY term_number");
            $terms = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Fee Structures: Got " . count($terms) . " terms");

            // Get campuses (no school_id in this table)
            $stmt = $pdo->query("SELECT id, campus_name FROM campuses WHERE is_active = 1 ORDER BY sort_order, campus_name");
            $campuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Fee Structures: Got " . count($campuses) . " campuses");

            $stmt = $pdo->prepare("
                SELECT fi.id, fi.code, fi.name, fi.default_amount, fc.name as category_name
                FROM fee_items fi
                LEFT JOIN fee_categories fc ON fc.id = fi.fee_category_id
                WHERE fi.school_id = ? AND fi.is_active = 1
                ORDER BY fc.sort_order, fi.name
            ");
            $stmt->execute([$schoolId]);
            $feeItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Fee Structures: Got " . count($feeItems) . " feeItems, rendering view");

            Response::view('finance/fee_structures', [
                'pageTitle' => 'Fee Structures',
                'structures' => $structures,
                'years' => $years,
                'grades' => $grades,
                'terms' => $terms,
                'campuses' => $campuses,
                'feeItems' => $feeItems
            ]);
        } catch (Exception $e) {
            error_log('Fee Structures error: ' . $e->getMessage());
            flash('error', 'Failed to load fee structures');
            Response::redirect('/finance');
        }
    }

    public function viewFeeStructure($id)
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;

            $stmt = $pdo->prepare("
                SELECT fs.*, ay.year_name as academic_year, t.term_name, g.grade_name, c.campus_name
                FROM fee_structures fs
                LEFT JOIN academic_years ay ON ay.id = fs.academic_year_id
                LEFT JOIN terms t ON t.id = fs.term_id
                LEFT JOIN grades g ON g.id = fs.grade_id
                LEFT JOIN campuses c ON c.id = fs.campus_id
                WHERE fs.id = ? AND fs.school_id = ?
            ");
            $stmt->execute([$id, $schoolId]);
            $structure = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$structure) {
                flash('error', 'Fee structure not found');
                Response::redirect('/finance/fee-structures');
                return;
            }

            $stmt = $pdo->prepare("
                SELECT fsi.*, fi.code, fi.name as item_name, fc.name as category_name
                FROM fee_structure_lines fsi
                LEFT JOIN fee_items fi ON fi.id = fsi.fee_item_id
                LEFT JOIN fee_categories fc ON fc.id = fi.fee_category_id
                WHERE fsi.fee_structure_id = ?
                ORDER BY fc.sort_order, fi.name
            ");
            $stmt->execute([$id]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::view('finance/fee_structure_view', [
                'pageTitle' => 'View Fee Structure',
                'structure' => $structure,
                'items' => $items
            ]);
        } catch (Exception $e) {
            error_log('View Fee Structure error: ' . $e->getMessage());
            flash('error', 'Failed to load fee structure');
            Response::redirect('/finance/fee-structures');
        }
    }

    public function editFeeStructure($id = null)
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;

            $structure = null;
            $lines = [];

            if ($id) {
                // Editing existing structure
                $stmt = $pdo->prepare("
                    SELECT fs.*
                    FROM fee_structures fs
                    WHERE fs.id = ? AND fs.school_id = ?
                ");
                $stmt->execute([$id, $schoolId]);
                $structure = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$structure) {
                    flash('error', 'Fee structure not found');
                    Response::redirect('/finance/fee-structures');
                    return;
                }

                // Get existing fee structure lines
                $stmt = $pdo->prepare("
                    SELECT fsl.*, fi.code, fi.name as item_name, fc.name as category_name
                    FROM fee_structure_lines fsl
                    LEFT JOIN fee_items fi ON fi.id = fsl.fee_item_id
                    LEFT JOIN fee_categories fc ON fc.id = fi.fee_category_id
                    WHERE fsl.fee_structure_id = ?
                    ORDER BY fc.sort_order, fi.name
                ");
                $stmt->execute([$id]);
                $lines = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            // Get dropdowns data
            $stmt = $pdo->query("SELECT id, year_name, is_current FROM academic_years ORDER BY start_date DESC");
            $years = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->query("SELECT id, term_name FROM terms ORDER BY term_number");
            $terms = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->query("SELECT id, grade_name FROM grades WHERE is_active = 1 ORDER BY sort_order");
            $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->query("SELECT id, campus_name FROM campuses WHERE is_active = 1 ORDER BY sort_order, campus_name");
            $campuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get fee items with category info
            $stmt = $pdo->prepare("
                SELECT fi.id, fi.code, fi.name, fi.default_amount, fc.name as category_name, fc.id as category_id, fc.category_type
                FROM fee_items fi
                LEFT JOIN fee_categories fc ON fc.id = fi.fee_category_id
                WHERE fi.school_id = ? AND fi.is_active = 1
                ORDER BY fc.sort_order, fi.name
            ");
            $stmt->execute([$schoolId]);
            $fee_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::view('finance/fee_structure_edit', [
                'pageTitle' => $id ? 'Edit Fee Structure' : 'Create Fee Structure',
                'structure' => $structure,
                'lines' => $lines,
                'years' => $years,
                'terms' => $terms,
                'grades' => $grades,
                'campuses' => $campuses,
                'fee_items' => $fee_items
            ]);
        } catch (Exception $e) {
            error_log('Edit Fee Structure error: ' . $e->getMessage());
            flash('error', 'Failed to load fee structure form');
            Response::redirect('/finance/fee-structures');
        }
    }

    public function saveFeeStructure()
    {
        // Unified save handler - calls store or update based on ID presence
        if (!empty($_POST['id'])) {
            return $this->updateFeeStructure($_POST['id']);
        } else {
            return $this->storeFeeStructure();
        }
    }

    public function storeFeeStructure()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;
            $userId = $_SESSION['user_id'] ?? null;

            $stmt = $pdo->prepare("
                INSERT INTO fee_structures (school_id, campus_id, academic_year_id, term_id, grade_id, name, status, prepared_by, notes, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'draft', ?, ?, NOW())
            ");
            $stmt->execute([
                $schoolId,
                $_POST['campus_id'] ?? 1,
                $_POST['academic_year_id'],
                $_POST['term_id'],
                $_POST['grade_id'],
                $_POST['name'],
                $userId,
                $_POST['notes'] ?? null
            ]);
            $structureId = $pdo->lastInsertId();

            // Add fee structure lines
            if (!empty($_POST['lines']) && is_array($_POST['lines'])) {
                $stmt = $pdo->prepare("
                    INSERT INTO fee_structure_lines (fee_structure_id, fee_item_id, amount, is_mandatory, applies_to_student_type, option_group, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                foreach ($_POST['lines'] as $line) {
                    if (!empty($line['selected']) && !empty($line['fee_item_id'])) {
                        $stmt->execute([
                            $structureId,
                            $line['fee_item_id'],
                            floatval($line['amount'] ?? 0),
                            isset($line['is_mandatory']) ? 1 : 0,
                            $line['applies_to_student_type'] ?? 'all',
                            $line['option_group'] ?? null
                        ]);
                    }
                }
            }

            flash('success', 'Fee structure created successfully');
        } catch (Exception $e) {
            error_log('Store Fee Structure error: ' . $e->getMessage());
            flash('error', 'Failed to create fee structure: ' . $e->getMessage());
        }
        Response::redirect('/finance/fee-structures');
    }

    public function updateFeeStructure($id)
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;

            $stmt = $pdo->prepare("
                UPDATE fee_structures
                SET name = ?, campus_id = ?, academic_year_id = ?, term_id = ?, grade_id = ?, notes = ?, updated_at = NOW()
                WHERE id = ? AND school_id = ?
            ");
            $stmt->execute([
                $_POST['name'],
                $_POST['campus_id'] ?? 1,
                $_POST['academic_year_id'],
                $_POST['term_id'],
                $_POST['grade_id'],
                $_POST['notes'] ?? null,
                $id,
                $schoolId
            ]);

            // Update fee structure lines
            $stmt = $pdo->prepare("DELETE FROM fee_structure_lines WHERE fee_structure_id = ?");
            $stmt->execute([$id]);

            if (!empty($_POST['lines']) && is_array($_POST['lines'])) {
                $stmt = $pdo->prepare("
                    INSERT INTO fee_structure_lines (fee_structure_id, fee_item_id, amount, is_mandatory, applies_to_student_type, option_group, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                foreach ($_POST['lines'] as $line) {
                    if (!empty($line['selected']) && !empty($line['fee_item_id'])) {
                        $stmt->execute([
                            $id,
                            $line['fee_item_id'],
                            floatval($line['amount'] ?? 0),
                            isset($line['is_mandatory']) ? 1 : 0,
                            $line['applies_to_student_type'] ?? 'all',
                            $line['option_group'] ?? null
                        ]);
                    }
                }
            }

            flash('success', 'Fee structure updated successfully');
        } catch (Exception $e) {
            error_log('Update Fee Structure error: ' . $e->getMessage());
            flash('error', 'Failed to update fee structure: ' . $e->getMessage());
        }
        Response::redirect('/finance/fee-structures');
    }

    public function deleteFeeStructure($id)
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;

            $stmt = $pdo->prepare("DELETE FROM fee_structure_lines WHERE fee_structure_id = ?");
            $stmt->execute([$id]);

            $stmt = $pdo->prepare("DELETE FROM fee_structures WHERE id = ? AND school_id = ?");
            $stmt->execute([$id, $schoolId]);

            flash('success', 'Fee structure deleted successfully');
        } catch (Exception $e) {
            error_log('Delete Fee Structure error: ' . $e->getMessage());
            flash('error', 'Failed to delete fee structure');
        }
        Response::redirect('/finance/fee-structures');
    }

    // ==================== TRANSPORT TARIFFS ====================

    public function transportTariffs()
    {
        if (!isAuthenticated()) {
            flash('error', 'Please login to access this page.');
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;

            // Get tariffs with zone and year info
            $stmt = $pdo->prepare("
                SELECT tt.*,
                    tz.zone_code,
                    tz.zone_name,
                    tz.min_distance_km,
                    tz.max_distance_km,
                    ay.year_name as academic_year,
                    t.term_name
                FROM transport_tariffs tt
                LEFT JOIN transport_zones tz ON tz.id = tt.transport_zone_id
                LEFT JOIN academic_years ay ON ay.id = tt.academic_year_id
                LEFT JOIN terms t ON t.id = tt.term_id
                WHERE tt.school_id = ?
                ORDER BY ay.start_date DESC, tz.zone_name, tt.direction
            ");
            $stmt->execute([$schoolId]);
            $tariffs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get zones for dropdown
            $stmt = $pdo->prepare("SELECT id, zone_code, zone_name, min_distance_km, max_distance_km, description FROM transport_zones WHERE school_id = ? AND is_active = 1 ORDER BY zone_name");
            $stmt->execute([$schoolId]);
            $zones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get academic years for dropdown
            $stmt = $pdo->query("SELECT id, year_name, is_current FROM academic_years ORDER BY start_date DESC");
            $academicYears = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get terms for dropdown
            $stmt = $pdo->query("SELECT id, term_name FROM terms ORDER BY term_number");
            $terms = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::view('finance/transport_tariffs', [
                'pageTitle' => 'Transport Tariffs',
                'tariffs' => $tariffs,
                'zones' => $zones,
                'academicYears' => $academicYears,
                'terms' => $terms
            ]);
        } catch (Exception $e) {
            error_log('Transport Tariffs error: ' . $e->getMessage());
            flash('error', 'Failed to load transport tariffs');
            Response::redirect('/finance');
        }
    }

    public function storeTariff()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;
            $userId = $_SESSION['user_id'] ?? null;

            $stmt = $pdo->prepare("
                INSERT INTO transport_tariffs (school_id, academic_year_id, term_id, transport_zone_id, direction, amount, is_active, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $schoolId,
                $_POST['academic_year_id'],
                $_POST['term_id'] ?: null,
                $_POST['transport_zone_id'],
                $_POST['direction'],
                $_POST['amount'] ?? 0,
                isset($_POST['is_active']) ? 1 : 1,
                $userId
            ]);

            flash('success', 'Transport tariff created successfully');
            Response::redirect('/finance/transport-tariffs');
        } catch (Exception $e) {
            error_log('Store Tariff error: ' . $e->getMessage());
            flash('error', 'Failed to create transport tariff');
            Response::redirect('/finance/transport-tariffs');
        }
    }

    public function updateTariff($id = null)
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        $id = $id ?? $_POST['id'] ?? null;
        if (!$id) {
            flash('error', 'No tariff ID provided');
            Response::redirect('/finance/transport-tariffs');
            return;
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;
            $userId = $_SESSION['user_id'] ?? null;

            $stmt = $pdo->prepare("
                UPDATE transport_tariffs
                SET academic_year_id = ?, term_id = ?, transport_zone_id = ?, direction = ?, amount = ?, is_active = ?, updated_by = ?, updated_at = NOW()
                WHERE id = ? AND school_id = ?
            ");
            $stmt->execute([
                $_POST['academic_year_id'],
                $_POST['term_id'] ?: null,
                $_POST['transport_zone_id'],
                $_POST['direction'],
                $_POST['amount'] ?? 0,
                isset($_POST['is_active']) ? 1 : 0,
                $userId,
                $id,
                $schoolId
            ]);

            flash('success', 'Transport tariff updated successfully');
            Response::redirect('/finance/transport-tariffs');
        } catch (Exception $e) {
            error_log('Update Tariff error: ' . $e->getMessage());
            flash('error', 'Failed to update transport tariff');
            Response::redirect('/finance/transport-tariffs');
        }
    }

    public function deleteTariff($id)
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;

            $stmt = $pdo->prepare("DELETE FROM transport_tariffs WHERE id = ? AND school_id = ?");
            $stmt->execute([$id, $schoolId]);

            if ($this->isAjax()) {
                $this->jsonResponse(['success' => true, 'message' => 'Tariff deleted successfully']);
            } else {
                flash('success', 'Transport tariff deleted successfully');
                Response::redirect('/finance/transport-tariffs');
            }
        } catch (Exception $e) {
            error_log('Delete Tariff error: ' . $e->getMessage());
            if ($this->isAjax()) {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to delete tariff']);
            } else {
                flash('error', 'Failed to delete transport tariff');
                Response::redirect('/finance/transport-tariffs');
            }
        }
    }

    // ==================== TRANSPORT ZONES ====================

    public function storeZone()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;
            $userId = $_SESSION['user_id'] ?? null;

            $stmt = $pdo->prepare("
                INSERT INTO transport_zones (school_id, zone_code, zone_name, min_distance_km, max_distance_km, description, is_active, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $schoolId,
                strtoupper($_POST['zone_code']),
                $_POST['zone_name'],
                $_POST['min_distance_km'] ?: null,
                $_POST['max_distance_km'] ?: null,
                $_POST['description'] ?? null,
                isset($_POST['is_active']) ? 1 : 1,
                $userId
            ]);

            flash('success', 'Transport zone created successfully');
            Response::redirect('/finance/transport-tariffs');
        } catch (Exception $e) {
            error_log('Store Zone error: ' . $e->getMessage());
            flash('error', 'Failed to create transport zone');
            Response::redirect('/finance/transport-tariffs');
        }
    }

    public function updateZone($id = null)
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        $id = $id ?? $_POST['id'] ?? null;
        if (!$id) {
            flash('error', 'No zone ID provided');
            Response::redirect('/finance/transport-tariffs');
            return;
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;
            $userId = $_SESSION['user_id'] ?? null;

            $stmt = $pdo->prepare("
                UPDATE transport_zones
                SET zone_code = ?, zone_name = ?, min_distance_km = ?, max_distance_km = ?, description = ?, is_active = ?, updated_by = ?, updated_at = NOW()
                WHERE id = ? AND school_id = ?
            ");
            $stmt->execute([
                strtoupper($_POST['zone_code']),
                $_POST['zone_name'],
                $_POST['min_distance_km'] ?: null,
                $_POST['max_distance_km'] ?: null,
                $_POST['description'] ?? null,
                isset($_POST['is_active']) ? 1 : 0,
                $userId,
                $id,
                $schoolId
            ]);

            flash('success', 'Transport zone updated successfully');
            Response::redirect('/finance/transport-tariffs');
        } catch (Exception $e) {
            error_log('Update Zone error: ' . $e->getMessage());
            flash('error', 'Failed to update transport zone');
            Response::redirect('/finance/transport-tariffs');
        }
    }

    public function deleteZone($id)
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;

            // Check if zone has tariffs
            $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM transport_tariffs WHERE transport_zone_id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['cnt'] > 0) {
                if ($this->isAjax()) {
                    $this->jsonResponse(['success' => false, 'message' => 'Cannot delete zone with existing tariffs']);
                } else {
                    flash('error', 'Cannot delete zone with existing tariffs');
                    Response::redirect('/finance/transport-tariffs');
                }
                return;
            }

            $stmt = $pdo->prepare("DELETE FROM transport_zones WHERE id = ? AND school_id = ?");
            $stmt->execute([$id, $schoolId]);

            if ($this->isAjax()) {
                $this->jsonResponse(['success' => true, 'message' => 'Zone deleted successfully']);
            } else {
                flash('success', 'Transport zone deleted successfully');
                Response::redirect('/finance/transport-tariffs');
            }
        } catch (Exception $e) {
            error_log('Delete Zone error: ' . $e->getMessage());
            if ($this->isAjax()) {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to delete zone']);
            } else {
                flash('error', 'Failed to delete transport zone');
                Response::redirect('/finance/transport-tariffs');
            }
        }
    }

    // ==================== INVOICES ====================

    public function invoices()
    {
        if (!isAuthenticated()) {
            flash('error', 'Please login to access this page.');
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;

            $status = Request::get('status', '');
            $fromDate = Request::get('from_date', '');
            $toDate = Request::get('to_date', '');
            $search = Request::get('search', '');
            $accountId = Request::get('account', '');

            $where = ['i.school_id = ?'];
            $params = [$schoolId];

            if (!empty($accountId)) {
                $where[] = 'i.student_fee_account_id = ?';
                $params[] = $accountId;
            }
            if (!empty($status)) {
                $where[] = 'i.status = ?';
                $params[] = $status;
            }
            if (!empty($fromDate)) {
                $where[] = 'DATE(i.invoice_date) >= ?';
                $params[] = $fromDate;
            }
            if (!empty($toDate)) {
                $where[] = 'DATE(i.invoice_date) <= ?';
                $params[] = $toDate;
            }
            if (!empty($search)) {
                $where[] = "(i.invoice_number LIKE ? OR CONCAT(COALESCE(s.first_name, a.first_name), ' ', COALESCE(s.last_name, a.last_name)) LIKE ?)";
                $params[] = '%' . $search . '%';
                $params[] = '%' . $search . '%';
            }

            $whereClause = implode(' AND ', $where);

            $stmt = $pdo->prepare("
                SELECT i.*,
                    sfa.account_number,
                    sfa.applicant_id,
                    s.first_name as student_first_name,
                    s.last_name as student_last_name,
                    s.admission_number,
                    a.first_name as applicant_first_name,
                    a.last_name as applicant_last_name,
                    a.application_ref,
                    g.grade_name as applicant_grade
                FROM invoices i
                LEFT JOIN student_fee_accounts sfa ON sfa.id = i.student_fee_account_id
                LEFT JOIN students s ON s.id = sfa.student_id
                LEFT JOIN applicants a ON a.id = sfa.applicant_id
                LEFT JOIN grades g ON g.id = a.grade_applying_for_id
                WHERE {$whereClause}
                ORDER BY i.created_at DESC
                LIMIT 500
            ");
            $stmt->execute($params);
            $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->query("SELECT id, grade_name FROM grades WHERE is_active = 1 ORDER BY sort_order");
            $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::view('finance/invoices', [
                'pageTitle' => 'Invoices',
                'invoices' => $invoices,
                'grades' => $grades,
                'filters' => [
                    'status' => $status,
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                    'search' => $search,
                    'account' => $accountId
                ]
            ]);
        } catch (Exception $e) {
            error_log('Invoices error: ' . $e->getMessage());
            flash('error', 'Failed to load invoices');
            Response::redirect('/finance');
        }
    }

    public function viewInvoice($id)
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;

            $stmt = $pdo->prepare("
                SELECT i.*,
                    sfa.account_number,
                    sfa.applicant_id,
                    s.first_name as student_first_name,
                    s.last_name as student_last_name,
                    s.admission_number,
                    a.first_name as applicant_first_name,
                    a.last_name as applicant_last_name,
                    a.application_ref,
                    ay.year_name,
                    t.term_name,
                    g_s.grade_name as student_grade,
                    g_a.grade_name as applicant_grade
                FROM invoices i
                LEFT JOIN student_fee_accounts sfa ON sfa.id = i.student_fee_account_id
                LEFT JOIN students s ON s.id = sfa.student_id
                LEFT JOIN applicants a ON a.id = sfa.applicant_id
                LEFT JOIN academic_years ay ON ay.id = i.academic_year_id
                LEFT JOIN terms t ON t.id = i.term_id
                LEFT JOIN student_enrollments se ON se.student_id = s.id AND se.is_current = 1
                LEFT JOIN streams st ON st.id = se.stream_id
                LEFT JOIN grades g_s ON g_s.id = st.grade_id
                LEFT JOIN grades g_a ON g_a.id = a.grade_applying_for_id
                WHERE i.id = ? AND i.school_id = ?
            ");
            $stmt->execute([$id, $schoolId]);
            $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$invoice) {
                flash('error', 'Invoice not found');
                Response::redirect('/finance/invoices');
                return;
            }

            // Get school info for invoice header
            $stmt = $pdo->prepare("SELECT * FROM campuses WHERE id = ? LIMIT 1");
            $stmt->execute([$_SESSION['current_campus_id'] ?? 1]);
            $campus = $stmt->fetch(PDO::FETCH_ASSOC);
            $school = [
                'school_name' => $campus['campus_name'] ?? 'School Name',
                'address' => $campus['address'] ?? '',
                'phone' => $campus['phone'] ?? '',
                'email' => $campus['email'] ?? ''
            ];

            // Get invoice lines
            $stmt = $pdo->prepare("
                SELECT il.*, fi.name as fee_item_name, fi.code as fee_item_code, fc.name as category_name
                FROM invoice_lines il
                LEFT JOIN fee_items fi ON fi.id = il.fee_item_id
                LEFT JOIN fee_categories fc ON fc.id = fi.fee_category_id
                WHERE il.invoice_id = ?
                ORDER BY il.id
            ");
            $stmt->execute([$id]);
            $lines = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get payments for this account (payments are linked to fee account, not invoice)
            $stmt = $pdo->prepare("
                SELECT p.*, pm.name as method_name
                FROM payments p
                LEFT JOIN payment_methods pm ON pm.code = p.payment_method
                WHERE p.student_fee_account_id = ? AND p.status = 'confirmed'
                ORDER BY p.payment_date DESC
            ");
            $stmt->execute([$invoice['student_fee_account_id']]);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::view('finance/view_invoice', [
                'pageTitle' => 'Invoice ' . $invoice['invoice_number'],
                'invoice' => $invoice,
                'lines' => $lines,
                'payments' => $payments,
                'school' => $school
            ]);
        } catch (Exception $e) {
            error_log('View Invoice error: ' . $e->getMessage());
            flash('error', 'Failed to load invoice');
            Response::redirect('/finance/invoices');
        }
    }

    public function generateInvoices()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;

            // Get current academic year
            $stmt = $pdo->query("SELECT id, year_name FROM academic_years WHERE is_current = 1 LIMIT 1");
            $currentYear = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$currentYear) {
                $stmt = $pdo->query("SELECT id, year_name FROM academic_years ORDER BY start_date DESC LIMIT 1");
                $currentYear = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            // Get current term
            $stmt = $pdo->query("SELECT id, term_name FROM terms WHERE is_current = 1 LIMIT 1");
            $currentTerm = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$currentTerm) {
                $stmt = $pdo->query("SELECT id, term_name FROM terms ORDER BY term_number LIMIT 1");
                $currentTerm = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            // Get campuses
            $stmt = $pdo->query("SELECT id, campus_name FROM campuses WHERE is_active = 1 ORDER BY campus_name");
            $campuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get grades
            $stmt = $pdo->query("SELECT id, grade_name FROM grades WHERE is_active = 1 ORDER BY sort_order");
            $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get fee structures
            $stmt = $pdo->prepare("
                SELECT fs.id, fs.name, g.grade_name as grade_group
                FROM fee_structures fs
                LEFT JOIN grades g ON g.id = fs.grade_id
                WHERE fs.school_id = ? AND fs.status = 'published'
                ORDER BY fs.name
            ");
            $stmt->execute([$schoolId]);
            $feeStructures = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::view('finance/generate_invoices', [
                'pageTitle' => 'Generate Invoices',
                'current_year' => $currentYear,
                'current_term' => $currentTerm,
                'campuses' => $campuses,
                'grades' => $grades,
                'feeStructures' => $feeStructures
            ]);
        } catch (Exception $e) {
            error_log('Generate Invoices error: ' . $e->getMessage());
            flash('error', 'Failed to load invoice generation page');
            Response::redirect('/finance/invoices');
        }
    }

    public function processGenerateInvoices()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;
            $userId = $_SESSION['user_id'] ?? null;

            // Get form data
            $academicYearId = $_POST['academic_year_id'];
            $termId = $_POST['term_id'] ?? null;
            $campusId = $_POST['campus_id'] ?? null;
            $gradeIds = $_POST['grade_ids'] ?? [];
            $invoiceDate = $_POST['invoice_date'] ?? date('Y-m-d');
            $dueDate = $_POST['due_date'];
            $skipExisting = isset($_POST['skip_existing']);

            if (empty($gradeIds)) {
                flash('error', 'Please select at least one grade');
                Response::redirect('/finance/invoices/generate');
                return;
            }

            $generatedCount = 0;
            $skippedCount = 0;

            // Process each selected grade
            foreach ($gradeIds as $gradeId) {
                // Find published fee structure for this grade, year, and term
                $stmt = $pdo->prepare("
                    SELECT fs.id, fs.name
                    FROM fee_structures fs
                    WHERE fs.school_id = ?
                        AND fs.academic_year_id = ?
                        AND fs.term_id = ?
                        AND fs.grade_id = ?
                        AND fs.status = 'published'
                    LIMIT 1
                ");
                $stmt->execute([$schoolId, $academicYearId, $termId, $gradeId]);
                $feeStructure = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$feeStructure) {
                    continue; // No fee structure for this grade
                }

                // Get fee structure lines
                $stmt = $pdo->prepare("
                    SELECT fsl.*, fi.name as item_name, fi.code as item_code
                    FROM fee_structure_lines fsl
                    LEFT JOIN fee_items fi ON fi.id = fsl.fee_item_id
                    WHERE fsl.fee_structure_id = ?
                ");
                $stmt->execute([$feeStructure['id']]);
                $structureLines = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($structureLines)) {
                    continue; // No items in this structure
                }

                // Get students enrolled in this grade
                $studentQuery = "
                    SELECT s.id, s.first_name, s.last_name, sfa.id as fee_account_id
                    FROM students s
                    INNER JOIN student_fee_accounts sfa ON sfa.student_id = s.id
                    INNER JOIN student_enrollments se ON se.student_id = s.id AND se.is_current = 1
                    INNER JOIN streams st ON st.id = se.stream_id
                    WHERE s.status = 'active'
                        AND st.grade_id = ?
                ";
                $params = [$gradeId];

                if ($campusId) {
                    $studentQuery .= " AND s.campus_id = ?";
                    $params[] = $campusId;
                }

                $stmt = $pdo->prepare($studentQuery);
                $stmt->execute($params);
                $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($students as $student) {
                    // Check for existing invoice if skip is enabled
                    if ($skipExisting) {
                        $stmt = $pdo->prepare("
                            SELECT id FROM invoices
                            WHERE student_fee_account_id = ?
                                AND academic_year_id = ?
                                AND term_id = ?
                            LIMIT 1
                        ");
                        $stmt->execute([$student['fee_account_id'], $academicYearId, $termId]);
                        if ($stmt->fetch()) {
                            $skippedCount++;
                            continue; // Already has invoice
                        }
                    }

                    // Calculate total
                    $totalAmount = array_sum(array_column($structureLines, 'amount'));

                    // Generate invoice number
                    $invoiceNumber = 'INV' . date('Y') . strtoupper(substr(md5(uniqid()), 0, 6));

                    // Create invoice
                    $stmt = $pdo->prepare("
                        INSERT INTO invoices (school_id, student_fee_account_id, invoice_number, invoice_date, due_date,
                            academic_year_id, term_id, total_amount, amount_paid, balance, status, created_by, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, 'unpaid', ?, NOW())
                    ");
                    $stmt->execute([
                        $schoolId, $student['fee_account_id'], $invoiceNumber, $invoiceDate, $dueDate,
                        $academicYearId, $termId, $totalAmount, $totalAmount, $userId
                    ]);
                    $invoiceId = $pdo->lastInsertId();

                    // Create invoice lines
                    foreach ($structureLines as $line) {
                        $stmt = $pdo->prepare("
                            INSERT INTO invoice_lines (invoice_id, fee_item_id, description, quantity, unit_price, amount, created_at)
                            VALUES (?, ?, ?, 1, ?, ?, NOW())
                        ");
                        $stmt->execute([$invoiceId, $line['fee_item_id'], $line['item_name'], $line['amount'], $line['amount']]);
                    }

                    // Update fee account balance
                    $stmt = $pdo->prepare("UPDATE student_fee_accounts SET current_balance = current_balance + ? WHERE id = ?");
                    $stmt->execute([$totalAmount, $student['fee_account_id']]);

                    $generatedCount++;
                }
            }

            $message = "Generated {$generatedCount} invoices successfully";
            if ($skippedCount > 0) {
                $message .= " ({$skippedCount} skipped due to existing invoices)";
            }
            flash('success', $message);
        } catch (Exception $e) {
            error_log('Process Generate Invoices error: ' . $e->getMessage());
            flash('error', 'Failed to generate invoices: ' . $e->getMessage());
        }
        Response::redirect('/finance/invoices');
    }

    // ==================== CREDIT NOTES ====================

    public function creditNotes()
    {
        if (!isAuthenticated()) {
            flash('error', 'Please login to access this page.');
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;

            $stmt = $pdo->prepare("
                SELECT cn.*,
                    i.invoice_number,
                    sfa.account_number,
                    COALESCE(s.first_name, a.first_name) as student_first_name,
                    COALESCE(s.last_name, a.last_name) as student_last_name,
                    COALESCE(s.admission_number, a.admission_number) as admission_number,
                    u.full_name as created_by_name
                FROM credit_notes cn
                LEFT JOIN invoices i ON i.id = cn.invoice_id
                LEFT JOIN student_fee_accounts sfa ON sfa.id = cn.fee_account_id
                LEFT JOIN students s ON s.id = sfa.student_id
                LEFT JOIN applicants a ON a.id = sfa.applicant_id
                LEFT JOIN users u ON u.id = cn.created_by
                WHERE cn.school_id = ?
                ORDER BY cn.created_at DESC
            ");
            $stmt->execute([$schoolId]);
            $creditNotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get fee accounts for dropdown - include both active AND pending accounts
            $stmt = $pdo->prepare("
                SELECT sfa.id, sfa.account_number,
                    COALESCE(s.first_name, a.first_name) as first_name,
                    COALESCE(s.last_name, a.last_name) as last_name,
                    COALESCE(s.admission_number, a.admission_number) as admission_number,
                    sfa.current_balance
                FROM student_fee_accounts sfa
                LEFT JOIN students s ON s.id = sfa.student_id
                LEFT JOIN applicants a ON a.id = sfa.applicant_id
                WHERE sfa.school_id = ? AND sfa.account_status IN ('active', 'pending')
                ORDER BY COALESCE(s.last_name, a.last_name), COALESCE(s.first_name, a.first_name)
            ");
            $stmt->execute([$schoolId]);
            $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get invoices with balance for dropdown
            $stmt = $pdo->prepare("
                SELECT i.id, i.invoice_number, i.balance, i.student_fee_account_id as fee_account_id
                FROM invoices i
                WHERE i.school_id = ? AND i.balance > 0
                ORDER BY i.invoice_date DESC
            ");
            $stmt->execute([$schoolId]);
            $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::view('finance/credit_notes', [
                'pageTitle' => 'Credit Notes',
                'creditNotes' => $creditNotes,
                'accounts' => $accounts,
                'invoices' => $invoices
            ]);
        } catch (Exception $e) {
            error_log('Credit Notes error: ' . $e->getMessage());
            flash('error', 'Failed to load credit notes');
            Response::redirect('/finance');
        }
    }

    public function viewCreditNote($id)
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;

            $stmt = $pdo->prepare("
                SELECT cn.*,
                    i.invoice_number,
                    sfa.account_number,
                    COALESCE(s.first_name, a.first_name) as student_first_name,
                    COALESCE(s.last_name, a.last_name) as student_last_name,
                    COALESCE(s.admission_number, a.admission_number) as admission_number,
                    u.full_name as created_by_name,
                    u2.full_name as approved_by_name
                FROM credit_notes cn
                LEFT JOIN invoices i ON i.id = cn.invoice_id
                LEFT JOIN student_fee_accounts sfa ON sfa.id = cn.student_fee_account_id
                LEFT JOIN students s ON s.id = sfa.student_id
                LEFT JOIN applicants a ON a.id = sfa.applicant_id
                LEFT JOIN users u ON u.id = cn.created_by
                LEFT JOIN users u2 ON u2.id = cn.approved_by
                WHERE cn.id = ? AND cn.school_id = ?
            ");
            $stmt->execute([$id, $schoolId]);
            $creditNote = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$creditNote) {
                flash('error', 'Credit note not found');
                Response::redirect('/finance/credit-notes');
                return;
            }

            Response::view('finance/credit_note_view', [
                'pageTitle' => 'Credit Note ' . $creditNote['credit_note_number'],
                'creditNote' => $creditNote
            ]);
        } catch (Exception $e) {
            error_log('View Credit Note error: ' . $e->getMessage());
            flash('error', 'Failed to load credit note');
            Response::redirect('/finance/credit-notes');
        }
    }

    public function storeCreditNote()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;
            $userId = $_SESSION['user_id'] ?? null;

            $feeAccountId = $_POST['fee_account_id'] ?? $_POST['student_fee_account_id'] ?? null;
            $invoiceId = $_POST['invoice_id'] ?: null;
            $amount = floatval($_POST['amount']);
            $reason = $_POST['reason'];
            $notes = $_POST['notes'] ?? null;
            $creditType = $_POST['credit_type'] ?? 'adjustment';

            $creditNoteNumber = 'CN' . date('Y') . strtoupper(substr(md5(uniqid()), 0, 6));

            $stmt = $pdo->prepare("
                INSERT INTO credit_notes (school_id, credit_note_number, student_fee_account_id, invoice_id, amount, credit_type, reason, notes, status, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?, NOW())
            ");
            $stmt->execute([
                $schoolId, $creditNoteNumber, $feeAccountId, $invoiceId, $amount, $creditType, $reason, $notes, $userId
            ]);

            if ($this->isAjax()) {
                $this->jsonResponse(['success' => true, 'message' => 'Credit note created successfully']);
            } else {
                flash('success', 'Credit note created successfully');
                Response::redirect('/finance/credit-notes');
            }
        } catch (Exception $e) {
            error_log('Store Credit Note error: ' . $e->getMessage());
            if ($this->isAjax()) {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to create credit note']);
            } else {
                flash('error', 'Failed to create credit note');
                Response::redirect('/finance/credit-notes');
            }
        }
    }

    public function updateCreditNote($id)
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;

            $stmt = $pdo->prepare("SELECT status FROM credit_notes WHERE id = ? AND school_id = ?");
            $stmt->execute([$id, $schoolId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$existing || $existing['status'] !== 'draft') {
                if ($this->isAjax()) {
                    $this->jsonResponse(['success' => false, 'message' => 'Only draft credit notes can be edited']);
                } else {
                    flash('error', 'Only draft credit notes can be edited');
                    Response::redirect('/finance/credit-notes');
                }
                return;
            }

            $stmt = $pdo->prepare("
                UPDATE credit_notes
                SET invoice_id = ?, amount = ?, reason = ?, notes = ?, updated_at = NOW()
                WHERE id = ? AND school_id = ?
            ");
            $stmt->execute([
                $_POST['invoice_id'] ?: null,
                floatval($_POST['amount']),
                $_POST['reason'],
                $_POST['notes'] ?? null,
                $id,
                $schoolId
            ]);

            if ($this->isAjax()) {
                $this->jsonResponse(['success' => true, 'message' => 'Credit note updated successfully']);
            } else {
                flash('success', 'Credit note updated successfully');
                Response::redirect('/finance/credit-notes');
            }
        } catch (Exception $e) {
            error_log('Update Credit Note error: ' . $e->getMessage());
            if ($this->isAjax()) {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to update credit note']);
            } else {
                flash('error', 'Failed to update credit note');
                Response::redirect('/finance/credit-notes');
            }
        }
    }

    public function deleteCreditNote($id)
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;

            $stmt = $pdo->prepare("SELECT status FROM credit_notes WHERE id = ? AND school_id = ?");
            $stmt->execute([$id, $schoolId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$existing || $existing['status'] !== 'draft') {
                if ($this->isAjax()) {
                    $this->jsonResponse(['success' => false, 'message' => 'Only draft credit notes can be deleted']);
                } else {
                    flash('error', 'Only draft credit notes can be deleted');
                    Response::redirect('/finance/credit-notes');
                }
                return;
            }

            $stmt = $pdo->prepare("DELETE FROM credit_notes WHERE id = ? AND school_id = ?");
            $stmt->execute([$id, $schoolId]);

            if ($this->isAjax()) {
                $this->jsonResponse(['success' => true, 'message' => 'Credit note deleted successfully']);
            } else {
                flash('success', 'Credit note deleted successfully');
                Response::redirect('/finance/credit-notes');
            }
        } catch (Exception $e) {
            error_log('Delete Credit Note error: ' . $e->getMessage());
            if ($this->isAjax()) {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to delete credit note']);
            } else {
                flash('error', 'Failed to delete credit note');
                Response::redirect('/finance/credit-notes');
            }
        }
    }

    public function approveCreditNote($id)
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;
            $userId = $_SESSION['user_id'] ?? null;

            $stmt = $pdo->prepare("SELECT * FROM credit_notes WHERE id = ? AND school_id = ?");
            $stmt->execute([$id, $schoolId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$existing || $existing['status'] !== 'draft') {
                if ($this->isAjax()) {
                    $this->jsonResponse(['success' => false, 'message' => 'Only draft credit notes can be approved']);
                } else {
                    flash('error', 'Only draft credit notes can be approved');
                    Response::redirect('/finance/credit-notes');
                }
                return;
            }

            $stmt = $pdo->prepare("UPDATE credit_notes SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
            $stmt->execute([$userId, $id]);

            if ($this->isAjax()) {
                $this->jsonResponse(['success' => true, 'message' => 'Credit note approved successfully']);
            } else {
                flash('success', 'Credit note approved successfully');
                Response::redirect('/finance/credit-notes');
            }
        } catch (Exception $e) {
            error_log('Approve Credit Note error: ' . $e->getMessage());
            if ($this->isAjax()) {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to approve credit note']);
            } else {
                flash('error', 'Failed to approve credit note');
                Response::redirect('/finance/credit-notes');
            }
        }
    }

    public function applyCreditNote($id)
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;

            $stmt = $pdo->prepare("SELECT * FROM credit_notes WHERE id = ? AND school_id = ?");
            $stmt->execute([$id, $schoolId]);
            $creditNote = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$creditNote || $creditNote['status'] !== 'approved') {
                if ($this->isAjax()) {
                    $this->jsonResponse(['success' => false, 'message' => 'Only approved credit notes can be applied']);
                } else {
                    flash('error', 'Only approved credit notes can be applied');
                    Response::redirect('/finance/credit-notes');
                }
                return;
            }

            // Update fee account balance
            $stmt = $pdo->prepare("UPDATE student_fee_accounts SET current_balance = current_balance - ? WHERE id = ?");
            $stmt->execute([$creditNote['amount'], $creditNote['student_fee_account_id']]);

            // If linked to invoice, update invoice
            if ($creditNote['invoice_id']) {
                $stmt = $pdo->prepare("
                    UPDATE invoices
                    SET amount_paid = amount_paid + ?,
                        balance = balance - ?,
                        status = CASE WHEN balance - ? <= 0 THEN 'paid' WHEN amount_paid + ? > 0 THEN 'partial' ELSE status END
                    WHERE id = ?
                ");
                $stmt->execute([
                    $creditNote['amount'], $creditNote['amount'], $creditNote['amount'], $creditNote['amount'], $creditNote['invoice_id']
                ]);
            }

            // Update credit note status
            $stmt = $pdo->prepare("UPDATE credit_notes SET status = 'applied', applied_at = NOW() WHERE id = ?");
            $stmt->execute([$id]);

            if ($this->isAjax()) {
                $this->jsonResponse(['success' => true, 'message' => 'Credit note applied successfully']);
            } else {
                flash('success', 'Credit note applied successfully');
                Response::redirect('/finance/credit-notes');
            }
        } catch (Exception $e) {
            error_log('Apply Credit Note error: ' . $e->getMessage());
            if ($this->isAjax()) {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to apply credit note']);
            } else {
                flash('error', 'Failed to apply credit note');
                Response::redirect('/finance/credit-notes');
            }
        }
    }

    // ==================== PAYMENTS ====================

    public function payments()
    {
        if (!isAuthenticated()) {
            flash('error', 'Please login to access this page.');
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;

            $status = Request::get('status', '');
            $paymentMethod = Request::get('payment_method', '');
            $fromDate = Request::get('from_date', '');
            $toDate = Request::get('to_date', '');
            $search = Request::get('search', '');
            $accountId = Request::get('account', '');

            $where = ['p.school_id = ?'];
            $params = [$schoolId];

            if (!empty($accountId)) {
                $where[] = 'p.student_fee_account_id = ?';
                $params[] = $accountId;
            }
            if (!empty($status)) {
                $where[] = 'p.status = ?';
                $params[] = $status;
            }
            if (!empty($paymentMethod)) {
                $where[] = 'p.payment_method = ?';
                $params[] = $paymentMethod;
            }
            if (!empty($fromDate)) {
                $where[] = 'DATE(p.payment_date) >= ?';
                $params[] = $fromDate;
            }
            if (!empty($toDate)) {
                $where[] = 'DATE(p.payment_date) <= ?';
                $params[] = $toDate;
            }
            if (!empty($search)) {
                $where[] = "(p.receipt_number LIKE ? OR p.reference_number LIKE ? OR CONCAT(COALESCE(s.first_name, a.first_name), ' ', COALESCE(s.last_name, a.last_name)) LIKE ?)";
                $params[] = '%' . $search . '%';
                $params[] = '%' . $search . '%';
                $params[] = '%' . $search . '%';
            }

            $whereClause = implode(' AND ', $where);

            $stmt = $pdo->prepare("
                SELECT p.*,
                    sfa.account_number,
                    COALESCE(s.first_name, a.first_name) as student_first_name,
                    COALESCE(s.last_name, a.last_name) as student_last_name,
                    COALESCE(s.admission_number, a.admission_number) as admission_number,
                    pm.name as payment_method_name
                FROM payments p
                LEFT JOIN student_fee_accounts sfa ON sfa.id = p.student_fee_account_id
                LEFT JOIN students s ON s.id = sfa.student_id
                LEFT JOIN applicants a ON a.id = sfa.applicant_id
                LEFT JOIN payment_methods pm ON pm.code = p.payment_method
                WHERE {$whereClause}
                ORDER BY p.payment_date DESC, p.created_at DESC
                LIMIT 500
            ");
            $stmt->execute($params);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->query("SELECT id, code, name FROM payment_methods WHERE is_active = 1 ORDER BY sort_order, name");
            $paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calculate totals
            $stmt = $pdo->prepare("SELECT COUNT(*) as count, COALESCE(SUM(p.amount), 0) as total_amount FROM payments p WHERE {$whereClause}");
            $stmt->execute($params);
            $totals = $stmt->fetch(PDO::FETCH_ASSOC);

            Response::view('finance/payments', [
                'pageTitle' => 'Payments',
                'payments' => $payments,
                'paymentMethods' => $paymentMethods,
                'filters' => [
                    'status' => $status,
                    'payment_method' => $paymentMethod,
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                    'search' => $search,
                    'account' => $accountId
                ],
                'totals' => $totals
            ]);
        } catch (Exception $e) {
            error_log('Payments error: ' . $e->getMessage());
            flash('error', 'Failed to load payments');
            Response::redirect('/finance');
        }
    }

    public function viewPayment($id)
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;

            $stmt = $pdo->prepare("
                SELECT p.*,
                    sfa.account_number,
                    COALESCE(s.first_name, a.first_name) as student_first_name,
                    COALESCE(s.last_name, a.last_name) as student_last_name,
                    COALESCE(s.admission_number, a.admission_number) as admission_number,
                    pm.name as payment_method_name,
                    u.full_name as received_by_name
                FROM payments p
                LEFT JOIN student_fee_accounts sfa ON sfa.id = p.student_fee_account_id
                LEFT JOIN students s ON s.id = sfa.student_id
                LEFT JOIN applicants a ON a.id = sfa.applicant_id
                LEFT JOIN payment_methods pm ON pm.code = p.payment_method
                LEFT JOIN users u ON u.id = p.received_by
                WHERE p.id = ? AND p.school_id = ?
            ");
            $stmt->execute([$id, $schoolId]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$payment) {
                flash('error', 'Payment not found');
                Response::redirect('/finance/payments');
                return;
            }

            Response::view('finance/payment_view', [
                'pageTitle' => 'Payment ' . $payment['receipt_number'],
                'payment' => $payment
            ]);
        } catch (Exception $e) {
            error_log('View Payment error: ' . $e->getMessage());
            flash('error', 'Failed to load payment');
            Response::redirect('/finance/payments');
        }
    }

    public function recordPayment()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;

            $feeAccountId = Request::get('fee_account_id') ?: Request::get('account');
            $invoiceId = Request::get('invoice_id');

            $feeAccount = null;
            $invoice = null;

            if ($feeAccountId) {
                $stmt = $pdo->prepare("
                    SELECT sfa.*, COALESCE(s.first_name, a.first_name) as first_name, COALESCE(s.last_name, a.last_name) as last_name,
                        COALESCE(s.admission_number, a.admission_number) as admission_number
                    FROM student_fee_accounts sfa
                    LEFT JOIN students s ON s.id = sfa.student_id
                    LEFT JOIN applicants a ON a.id = sfa.applicant_id
                    WHERE sfa.id = ? AND sfa.school_id = ?
                ");
                $stmt->execute([$feeAccountId, $schoolId]);
                $feeAccount = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            if ($invoiceId) {
                $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? AND school_id = ?");
                $stmt->execute([$invoiceId, $schoolId]);
                $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            $stmt = $pdo->query("SELECT id, code, name FROM payment_methods WHERE is_active = 1 ORDER BY sort_order, name");
            $paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("
                SELECT ba.id, ba.account_name, ba.account_number, b.bank_name
                FROM bank_accounts ba
                LEFT JOIN banks b ON b.id = ba.bank_id
                WHERE ba.school_id = ? AND ba.is_active = 1
                ORDER BY ba.account_name
            ");
            $stmt->execute([$schoolId]);
            $bankAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::view('finance/record_payment', [
                'pageTitle' => 'Record Payment',
                'feeAccount' => $feeAccount,
                'invoice' => $invoice,
                'paymentMethods' => $paymentMethods,
                'bankAccounts' => $bankAccounts
            ]);
        } catch (Exception $e) {
            error_log('Record Payment error: ' . $e->getMessage());
            flash('error', 'Failed to load payment form');
            Response::redirect('/finance/payments');
        }
    }

    public function storePayment()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;
            $userId = $_SESSION['user_id'] ?? null;

            $feeAccountId = $_POST['student_fee_account_id'];
            $invoiceId = $_POST['invoice_id'] ?: null;
            $amount = floatval($_POST['amount']);
            $paymentMethodId = $_POST['payment_method_id'];
            $paymentDate = $_POST['payment_date'];
            $referenceNumber = $_POST['reference_number'] ?? null;
            $notes = $_POST['notes'] ?? null;

            $receiptNumber = 'RCP' . date('Y') . strtoupper(substr(md5(uniqid()), 0, 6));

            $stmt = $pdo->prepare("SELECT code FROM payment_methods WHERE id = ?");
            $stmt->execute([$paymentMethodId]);
            $pm = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("
                INSERT INTO payments (school_id, student_fee_account_id, receipt_number, payment_date, amount, payment_method, payment_method_id, reference_number, notes, status, received_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', ?, NOW())
            ");
            $stmt->execute([
                $schoolId, $feeAccountId, $receiptNumber, $paymentDate, $amount,
                $pm['code'] ?? 'cash', $paymentMethodId, $referenceNumber, $notes, $userId
            ]);
            $paymentId = $pdo->lastInsertId();

            // Update fee account balance
            $stmt = $pdo->prepare("UPDATE student_fee_accounts SET current_balance = current_balance - ?, total_paid = total_paid + ? WHERE id = ?");
            $stmt->execute([$amount, $amount, $feeAccountId]);

            // Update invoice if linked
            if ($invoiceId) {
                $stmt = $pdo->prepare("
                    UPDATE invoices
                    SET amount_paid = amount_paid + ?,
                        balance = balance - ?,
                        status = CASE WHEN balance - ? <= 0 THEN 'paid' WHEN amount_paid + ? > 0 THEN 'partial' ELSE status END
                    WHERE id = ?
                ");
                $stmt->execute([$amount, $amount, $amount, $amount, $invoiceId]);
            }

            if ($this->isAjax()) {
                $this->jsonResponse(['success' => true, 'message' => 'Payment recorded successfully', 'receipt_number' => $receiptNumber, 'payment_id' => $paymentId]);
            } else {
                flash('success', 'Payment recorded successfully. Receipt: ' . $receiptNumber);
                Response::redirect('/finance/payments/' . $paymentId);
            }
        } catch (Exception $e) {
            error_log('Store Payment error: ' . $e->getMessage());
            if ($this->isAjax()) {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to record payment']);
            } else {
                flash('error', 'Failed to record payment');
                Response::redirect('/finance/payments/record');
            }
        }
    }

    // ==================== STUDENT ACCOUNTS ====================

    public function studentAccounts()
    {
        if (!isAuthenticated()) {
            flash('error', 'Please login to access this page.');
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;

            $status = Request::get('status', '');
            $balanceType = Request::get('balance_type', '');
            $search = Request::get('search', '');

            $where = ['sfa.school_id = ?'];
            $params = [$schoolId];

            if (!empty($status)) {
                $where[] = 'sfa.account_status = ?';
                $params[] = $status;
            }
            if (!empty($balanceType)) {
                if ($balanceType === 'outstanding') $where[] = 'sfa.current_balance > 0';
                elseif ($balanceType === 'credit') $where[] = 'sfa.current_balance < 0';
                elseif ($balanceType === 'zero') $where[] = 'sfa.current_balance = 0';
            }
            if (!empty($search)) {
                $where[] = "(sfa.account_number LIKE ? OR CONCAT(COALESCE(s.first_name, a.first_name), ' ', COALESCE(s.last_name, a.last_name)) LIKE ? OR COALESCE(s.admission_number, a.admission_number) LIKE ?)";
                $params[] = '%' . $search . '%';
                $params[] = '%' . $search . '%';
                $params[] = '%' . $search . '%';
            }

            $whereClause = implode(' AND ', $where);

            $stmt = $pdo->prepare("
                SELECT sfa.*,
                    COALESCE(s.first_name, a.first_name) as first_name,
                    COALESCE(s.last_name, a.last_name) as last_name,
                    COALESCE(s.admission_number, a.admission_number) as admission_number,
                    g.grade_name
                FROM student_fee_accounts sfa
                LEFT JOIN students s ON s.id = sfa.student_id
                LEFT JOIN applicants a ON a.id = sfa.applicant_id
                LEFT JOIN student_enrollments se ON se.student_id = s.id AND se.is_current = 1
                LEFT JOIN streams st ON st.id = se.stream_id
                LEFT JOIN grades g ON g.id = st.grade_id
                WHERE {$whereClause}
                ORDER BY COALESCE(s.last_name, a.last_name), COALESCE(s.first_name, a.first_name)
                LIMIT 500
            ");
            $stmt->execute($params);
            $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->query("SELECT id, grade_name FROM grades WHERE is_active = 1 ORDER BY sort_order");
            $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get summary stats
            $stmt = $pdo->prepare("
                SELECT
                    COUNT(*) as total_accounts,
                    SUM(CASE WHEN account_status = 'active' THEN 1 ELSE 0 END) as active_accounts,
                    COALESCE(SUM(CASE WHEN current_balance > 0 THEN current_balance ELSE 0 END), 0) as total_outstanding
                FROM student_fee_accounts WHERE school_id = ?
            ");
            $stmt->execute([$schoolId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            // Calculate total_invoiced and total_paid for each account
            foreach ($accounts as &$account) {
                // Get total invoiced
                $stmtInv = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as total FROM invoices WHERE student_fee_account_id = ?");
                $stmtInv->execute([$account['id']]);
                $account['total_invoiced'] = $stmtInv->fetchColumn();

                // Get total paid
                $stmtPay = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE student_fee_account_id = ? AND status = 'confirmed'");
                $stmtPay->execute([$account['id']]);
                $account['total_paid'] = $stmtPay->fetchColumn();
            }
            unset($account); // Break reference

            Response::view('finance/student_accounts', [
                'pageTitle' => 'Student Accounts',
                'accounts' => $accounts,
                'grades' => $grades,
                'filters' => ['status' => $status, 'balance_type' => $balanceType, 'search' => $search],
                'stats' => $stats
            ]);
        } catch (Exception $e) {
            error_log('Student Accounts error: ' . $e->getMessage());
            flash('error', 'Failed to load student accounts');
            Response::redirect('/finance');
        }
    }

    public function viewStudentAccount($id)
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;

            // Get account with both student and applicant name fields for view compatibility
            $stmt = $pdo->prepare("
                SELECT sfa.*,
                    s.first_name as student_first_name,
                    s.last_name as student_last_name,
                    a.first_name as applicant_first_name,
                    a.last_name as applicant_last_name,
                    COALESCE(s.admission_number, a.admission_number) as admission_number,
                    a.application_ref,
                    g.grade_name,
                    st.stream_name
                FROM student_fee_accounts sfa
                LEFT JOIN students s ON s.id = sfa.student_id
                LEFT JOIN applicants a ON a.id = sfa.applicant_id
                LEFT JOIN student_enrollments se ON se.student_id = s.id AND se.is_current = 1
                LEFT JOIN streams st ON st.id = se.stream_id
                LEFT JOIN grades g ON g.id = COALESCE(st.grade_id, a.grade_applying_for_id)
                WHERE sfa.id = ? AND sfa.school_id = ?
            ");
            $stmt->execute([$id, $schoolId]);
            $account = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$account) {
                flash('error', 'Account not found');
                Response::redirect('/finance/student-accounts');
                return;
            }

            // Get invoices
            $stmt = $pdo->prepare("
                SELECT i.*, ay.year_name as academic_year
                FROM invoices i
                LEFT JOIN academic_years ay ON ay.id = i.academic_year_id
                WHERE i.student_fee_account_id = ?
                ORDER BY i.invoice_date DESC
            ");
            $stmt->execute([$id]);
            $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get payments
            $stmt = $pdo->prepare("
                SELECT p.*, pm.name as payment_method_name
                FROM payments p
                LEFT JOIN payment_methods pm ON pm.code = p.payment_method
                WHERE p.student_fee_account_id = ? AND p.status = 'confirmed'
                ORDER BY p.payment_date DESC
            ");
            $stmt->execute([$id]);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get credit notes
            $stmt = $pdo->prepare("
                SELECT cn.*, i.invoice_number
                FROM credit_notes cn
                LEFT JOIN invoices i ON i.id = cn.invoice_id
                WHERE cn.fee_account_id = ? AND cn.status = 'applied'
                ORDER BY cn.issue_date DESC
            ");
            $stmt->execute([$id]);
            $creditNotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Build statement - combine invoices and payments in chronological order
            $statement = [];

            // Add invoices as debits
            foreach ($invoices as $inv) {
                $statement[] = [
                    'date' => $inv['invoice_date'],
                    'type' => 'invoice',
                    'reference' => $inv['invoice_number'],
                    'description' => 'Invoice - ' . ($inv['academic_year'] ?? 'N/A'),
                    'debit' => floatval($inv['total_amount']),
                    'credit' => 0,
                    'status' => $inv['status'],
                    'id' => $inv['id']
                ];
            }

            // Add payments as credits
            foreach ($payments as $pay) {
                $statement[] = [
                    'date' => $pay['payment_date'],
                    'type' => 'payment',
                    'reference' => $pay['receipt_number'],
                    'description' => 'Payment - ' . ($pay['payment_method_name'] ?? ucfirst($pay['payment_method'])),
                    'debit' => 0,
                    'credit' => floatval($pay['amount']),
                    'status' => $pay['status'],
                    'id' => $pay['id']
                ];
            }

            // Add credit notes as credits
            foreach ($creditNotes as $cn) {
                $statement[] = [
                    'date' => $cn['issue_date'],
                    'type' => 'credit_note',
                    'reference' => $cn['credit_note_number'],
                    'description' => 'Credit Note - ' . ucfirst($cn['credit_type']),
                    'debit' => 0,
                    'credit' => floatval($cn['amount']),
                    'status' => $cn['status'],
                    'id' => $cn['id']
                ];
            }

            // Sort by date (oldest first for running balance)
            usort($statement, function($a, $b) {
                return strtotime($a['date']) - strtotime($b['date']);
            });

            // Calculate running balance
            $runningBalance = 0;
            foreach ($statement as &$entry) {
                $runningBalance += $entry['debit'] - $entry['credit'];
                $entry['balance'] = $runningBalance;
            }
            unset($entry);

            // Reverse to show newest first
            $statement = array_reverse($statement);

            // Calculate totals
            $totalInvoiced = array_sum(array_column($invoices, 'total_amount'));
            $totalPaid = array_sum(array_column($payments, 'amount'));
            $totalCredits = array_sum(array_column($creditNotes, 'amount'));
            $balance = $totalInvoiced - $totalPaid - $totalCredits;

            Response::view('finance/student_account_statement', [
                'pageTitle' => 'Account: ' . $account['account_number'],
                'account' => $account,
                'invoices' => $invoices,
                'payments' => $payments,
                'creditNotes' => $creditNotes,
                'statement' => $statement,
                'totalInvoiced' => $totalInvoiced,
                'totalPaid' => $totalPaid + $totalCredits,
                'balance' => $balance
            ]);
        } catch (Exception $e) {
            error_log('View Student Account error: ' . $e->getMessage());
            flash('error', 'Failed to load student account');
            Response::redirect('/finance/student-accounts');
        }
    }

    // ==================== FAMILY ACCOUNTS ====================

    public function familyAccounts()
    {
        if (!isAuthenticated()) {
            flash('error', 'Please login to access this page.');
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;

            // Get filter parameters
            $search = Request::get('search', '');
            $status = Request::get('status', '');

            // Initialize defaults
            $accounts = [];
            $stats = ['total_families' => 0, 'active_families' => 0, 'linked_students' => 0];
            $potentialFamilies = [];

            // Check if family_accounts table exists and get accounts
            try {
                // Build WHERE clause for accounts - check which columns exist
                $where = ['1=1'];
                $params = [];

                // Try to check for school_id column
                $stmt = $pdo->query("SHOW COLUMNS FROM family_accounts LIKE 'school_id'");
                $hasSchoolId = $stmt->fetch() !== false;

                if ($hasSchoolId) {
                    $where[] = 'fa.school_id = ?';
                    $params[] = $schoolId;
                }

                if (!empty($search)) {
                    $where[] = "(fa.account_number LIKE ? OR fa.family_name LIKE ?)";
                    $searchTerm = '%' . $search . '%';
                    $params[] = $searchTerm;
                    $params[] = $searchTerm;
                }

                if (!empty($status)) {
                    $where[] = 'fa.account_status = ?';
                    $params[] = $status;
                }

                $whereClause = implode(' AND ', $where);

                // Get family accounts - simplified query without guardian join initially
                $stmt = $pdo->prepare("
                    SELECT fa.*,
                        (SELECT COUNT(*) FROM family_account_members fam WHERE fam.family_account_id = fa.id) as member_count,
                        (SELECT COALESCE(SUM(sfa.current_balance), 0) FROM student_fee_accounts sfa
                         INNER JOIN family_account_members fam ON fam.student_fee_account_id = sfa.id
                         WHERE fam.family_account_id = fa.id) as total_balance
                    FROM family_accounts fa
                    WHERE {$whereClause}
                    ORDER BY fa.family_name
                ");
                $stmt->execute($params);
                $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Get stats
                $statsWhere = $hasSchoolId ? 'WHERE school_id = ?' : '';
                $statsParams = $hasSchoolId ? [$schoolId] : [];

                $stmt = $pdo->prepare("
                    SELECT
                        COUNT(*) as total_families,
                        SUM(CASE WHEN account_status = 'active' THEN 1 ELSE 0 END) as active_families
                    FROM family_accounts
                    {$statsWhere}
                ");
                $stmt->execute($statsParams);
                $stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: $stats;

                // Count linked students
                $stmt = $pdo->query("SELECT COUNT(DISTINCT student_fee_account_id) as linked_students FROM family_account_members");
                $linkedStats = $stmt->fetch(PDO::FETCH_ASSOC);
                $stats['linked_students'] = $linkedStats['linked_students'] ?? 0;

            } catch (PDOException $e) {
                // Table might not exist - that's OK, show empty state
                error_log('Family accounts table query failed: ' . $e->getMessage());
            }

            // Find potential families (guardians with multiple children not in family accounts)
            try {
                $stmt = $pdo->prepare("
                    SELECT g.id as guardian_id,
                           CONCAT(g.first_name, ' ', g.last_name) as guardian_name,
                           COUNT(DISTINCT sg.student_id) as child_count,
                           GROUP_CONCAT(DISTINCT CONCAT(s.first_name, ' ', s.last_name) SEPARATOR ', ') as children
                    FROM guardians g
                    INNER JOIN student_guardians sg ON sg.guardian_id = g.id
                    INNER JOIN students s ON s.id = sg.student_id
                    INNER JOIN student_fee_accounts sfa ON sfa.student_id = s.id
                    LEFT JOIN family_account_members fam ON fam.student_fee_account_id = sfa.id
                    WHERE g.school_id = ? AND fam.id IS NULL
                    GROUP BY g.id, g.first_name, g.last_name
                    HAVING child_count >= 2
                    ORDER BY child_count DESC
                    LIMIT 10
                ");
                $stmt->execute([$schoolId]);
                $potentialFamilies = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                // Potential families query is optional - may fail if tables don't exist
                error_log('Potential families query failed: ' . $e->getMessage());
            }

            Response::view('finance/family_accounts', [
                'pageTitle' => 'Family Accounts',
                'accounts' => $accounts,
                'stats' => $stats,
                'potentialFamilies' => $potentialFamilies,
                'search' => $search,
                'status' => $status
            ]);
        } catch (Exception $e) {
            error_log('Family Accounts error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            if (Response::isAjax()) {
                echo '<div class="alert alert-danger m-3"><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
                return;
            }
            flash('error', 'Failed to load family accounts: ' . $e->getMessage());
            Response::redirect('/finance');
        }
    }

    public function viewFamilyAccount($id)
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;

            $stmt = $pdo->prepare("SELECT * FROM family_accounts WHERE id = ? AND school_id = ?");
            $stmt->execute([$id, $schoolId]);
            $account = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$account) {
                flash('error', 'Family account not found');
                Response::redirect('/finance/family-accounts');
                return;
            }

            $stmt = $pdo->prepare("
                SELECT fam.*, sfa.account_number, sfa.current_balance,
                    COALESCE(s.first_name, a.first_name) as first_name,
                    COALESCE(s.last_name, a.last_name) as last_name,
                    COALESCE(s.admission_number, a.admission_number) as admission_number
                FROM family_account_members fam
                INNER JOIN student_fee_accounts sfa ON sfa.id = fam.student_fee_account_id
                LEFT JOIN students s ON s.id = sfa.student_id
                LEFT JOIN applicants a ON a.id = sfa.applicant_id
                WHERE fam.family_account_id = ?
            ");
            $stmt->execute([$id]);
            $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::view('finance/family_account_view', [
                'pageTitle' => 'Family: ' . $account['family_name'],
                'account' => $account,
                'members' => $members
            ]);
        } catch (Exception $e) {
            error_log('View Family Account error: ' . $e->getMessage());
            flash('error', 'Failed to load family account');
            Response::redirect('/finance/family-accounts');
        }
    }

    public function storeFamilyAccount()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;
            $userId = $_SESSION['user_id'] ?? null;

            $stmt = $pdo->prepare("
                INSERT INTO family_accounts (school_id, family_name, primary_contact_name, primary_contact_phone, primary_contact_email, billing_address, notes, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $schoolId, $_POST['family_name'], $_POST['primary_contact_name'] ?? null,
                $_POST['primary_contact_phone'] ?? null, $_POST['primary_contact_email'] ?? null,
                $_POST['billing_address'] ?? null, $_POST['notes'] ?? null, $userId
            ]);
            $familyId = $pdo->lastInsertId();

            if (!empty($_POST['member_accounts']) && is_array($_POST['member_accounts'])) {
                $stmt = $pdo->prepare("INSERT INTO family_account_members (family_account_id, student_fee_account_id, created_at) VALUES (?, ?, NOW())");
                foreach ($_POST['member_accounts'] as $accountId) {
                    $stmt->execute([$familyId, $accountId]);
                }
            }

            flash('success', 'Family account created successfully');
        } catch (Exception $e) {
            error_log('Store Family Account error: ' . $e->getMessage());
            flash('error', 'Failed to create family account');
        }
        Response::redirect('/finance/family-accounts');
    }

    // ==================== REPORTS ====================

    public function collectionReport()
    {
        error_log('collectionReport() method called - URI: ' . $_SERVER['REQUEST_URI']);

        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;

            $fromDate = Request::get('from_date', date('Y-m-01'));
            $toDate = Request::get('to_date', date('Y-m-d'));
            $paymentMethod = Request::get('payment_method', '');
            $status = Request::get('status', '');

            // Build WHERE clause
            $where = ['p.school_id = ?'];
            $params = [$schoolId];

            // Status filter - default to confirmed if not specified
            if (!empty($status)) {
                $where[] = 'p.status = ?';
                $params[] = $status;
            } else {
                $where[] = "p.status IN ('completed', 'confirmed')";
            }

            if (!empty($fromDate)) {
                $where[] = 'DATE(p.payment_date) >= ?';
                $params[] = $fromDate;
            }
            if (!empty($toDate)) {
                $where[] = 'DATE(p.payment_date) <= ?';
                $params[] = $toDate;
            }
            if (!empty($paymentMethod)) {
                $where[] = 'p.payment_method = ?';
                $params[] = $paymentMethod;
            }

            $whereClause = implode(' AND ', $where);

            // Daily breakdown - matches view expectation: payment_date, transaction_count, daily_total
            $stmt = $pdo->prepare("
                SELECT DATE(p.payment_date) as payment_date,
                       COUNT(*) as transaction_count,
                       SUM(p.amount) as daily_total
                FROM payments p
                WHERE {$whereClause}
                GROUP BY DATE(p.payment_date)
                ORDER BY DATE(p.payment_date) DESC
            ");
            $stmt->execute($params);
            $dailyBreakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // By payment method - matches view: payment_method, method_name, transaction_count, method_total
            $stmt = $pdo->prepare("
                SELECT p.payment_method,
                       COALESCE(pm.name, UPPER(p.payment_method)) as method_name,
                       COUNT(*) as transaction_count,
                       SUM(p.amount) as method_total
                FROM payments p
                LEFT JOIN payment_methods pm ON pm.code = p.payment_method
                WHERE {$whereClause}
                GROUP BY p.payment_method, pm.name
                ORDER BY method_total DESC
            ");
            $stmt->execute($params);
            $byMethod = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Summary totals
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total_transactions,
                       COALESCE(SUM(amount), 0) as total_collected,
                       COUNT(DISTINCT DATE(payment_date)) as collection_days
                FROM payments p
                WHERE {$whereClause}
            ");
            $stmt->execute($params);
            $totals = $stmt->fetch(PDO::FETCH_ASSOC);

            $summary = [
                'total_transactions' => (int)$totals['total_transactions'],
                'total_collected' => (float)$totals['total_collected'],
                'collection_days' => (int)$totals['collection_days'],
                'average_payment' => $totals['total_transactions'] > 0
                    ? $totals['total_collected'] / $totals['total_transactions']
                    : 0
            ];

            // Transaction details - matches view expectations
            $stmt = $pdo->prepare("
                SELECT p.id, p.receipt_number, p.payment_date, p.amount,
                       p.payment_method, COALESCE(pm.name, UPPER(p.payment_method)) as method_name,
                       p.reference_number, p.status,
                       CONCAT(COALESCE(s.first_name, a.first_name, ''), ' ', COALESCE(s.last_name, a.last_name, '')) as payer_name,
                       COALESCE(s.admission_number, a.admission_number) as payer_ref,
                       u.full_name as received_by_name
                FROM payments p
                LEFT JOIN payment_methods pm ON pm.code = p.payment_method
                LEFT JOIN student_fee_accounts sfa ON sfa.id = p.student_fee_account_id
                LEFT JOIN students s ON s.id = sfa.student_id
                LEFT JOIN applicants a ON a.id = sfa.applicant_id
                LEFT JOIN users u ON u.id = p.received_by
                WHERE {$whereClause}
                ORDER BY p.payment_date DESC, p.id DESC
                LIMIT 500
            ");
            $stmt->execute($params);
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Payment methods for filter dropdown
            $stmt = $pdo->query("SELECT id, code, name FROM payment_methods WHERE is_active = 1 ORDER BY sort_order, name");
            $paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::view('finance/reports/collection', [
                'pageTitle' => 'Collection Report',
                'summary' => $summary,
                'dailyBreakdown' => $dailyBreakdown,
                'byMethod' => $byMethod,
                'transactions' => $transactions,
                'filters' => [
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                    'payment_method' => $paymentMethod,
                    'status' => $status
                ],
                'paymentMethods' => $paymentMethods
            ]);
        } catch (Exception $e) {
            error_log('Collection Report error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            // For AJAX requests, show inline error instead of redirecting
            if (Response::isAjax()) {
                echo '<div class="alert alert-danger m-3"><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
                return;
            }
            flash('error', 'Failed to generate collection report: ' . $e->getMessage());
            Response::redirect('/finance');
        }
    }

    public function outstandingReport()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;

            $minBalance = Request::get('min_balance', '');
            $gradeFilter = Request::get('grade', '');

            $where = ['sfa.school_id = ?', 'sfa.current_balance > 0'];
            $params = [$schoolId];

            if (!empty($minBalance)) {
                $where[] = 'sfa.current_balance >= ?';
                $params[] = floatval($minBalance);
            }

            if (!empty($gradeFilter)) {
                $where[] = 'g.id = ?';
                $params[] = $gradeFilter;
            }

            $whereClause = implode(' AND ', $where);

            // By grade summary (always show all grades for the chart)
            $gradeWhere = ['sfa.school_id = ?', 'sfa.current_balance > 0'];
            $gradeParams = [$schoolId];
            if (!empty($minBalance)) {
                $gradeWhere[] = 'sfa.current_balance >= ?';
                $gradeParams[] = floatval($minBalance);
            }
            $gradeWhereClause = implode(' AND ', $gradeWhere);

            $stmt = $pdo->prepare("
                SELECT g.id as grade_id, g.grade_name, COUNT(DISTINCT sfa.id) as account_count, SUM(sfa.current_balance) as total_outstanding
                FROM student_fee_accounts sfa
                LEFT JOIN students s ON s.id = sfa.student_id
                LEFT JOIN student_enrollments se ON se.student_id = s.id AND se.is_current = 1
                LEFT JOIN streams st ON st.id = se.stream_id
                LEFT JOIN grades g ON g.id = st.grade_id
                WHERE {$gradeWhereClause}
                GROUP BY g.id, g.grade_name
                ORDER BY total_outstanding DESC
            ");
            $stmt->execute($gradeParams);
            $byGrade = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Top balances (filtered)
            $stmt = $pdo->prepare("
                SELECT sfa.id, sfa.account_number, sfa.current_balance,
                    COALESCE(s.first_name, a.first_name) as first_name,
                    COALESCE(s.last_name, a.last_name) as last_name,
                    COALESCE(s.admission_number, a.admission_number) as admission_number,
                    g.grade_name
                FROM student_fee_accounts sfa
                LEFT JOIN students s ON s.id = sfa.student_id
                LEFT JOIN applicants a ON a.id = sfa.applicant_id
                LEFT JOIN student_enrollments se ON se.student_id = s.id AND se.is_current = 1
                LEFT JOIN streams st ON st.id = se.stream_id
                LEFT JOIN grades g ON g.id = st.grade_id
                WHERE {$whereClause}
                ORDER BY sfa.current_balance DESC
                LIMIT 50
            ");
            $stmt->execute($params);
            $topBalances = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Totals (filtered)
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count, COALESCE(SUM(sfa.current_balance), 0) as total
                FROM student_fee_accounts sfa
                LEFT JOIN students s ON s.id = sfa.student_id
                LEFT JOIN student_enrollments se ON se.student_id = s.id AND se.is_current = 1
                LEFT JOIN streams st ON st.id = se.stream_id
                LEFT JOIN grades g ON g.id = st.grade_id
                WHERE {$whereClause}
            ");
            $stmt->execute($params);
            $total = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $pdo->query("SELECT id, grade_name FROM grades WHERE is_active = 1 ORDER BY sort_order");
            $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::view('finance/reports/outstanding', [
                'pageTitle' => 'Outstanding Balances Report',
                'byGrade' => $byGrade,
                'topBalances' => $topBalances,
                'total' => $total,
                'filters' => ['min_balance' => $minBalance, 'grade' => $gradeFilter],
                'grades' => $grades
            ]);
        } catch (Exception $e) {
            error_log('Outstanding Report error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            if (Response::isAjax()) {
                echo '<div class="alert alert-danger m-3"><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
                return;
            }
            flash('error', 'Failed to generate outstanding report: ' . $e->getMessage());
            Response::redirect('/finance');
        }
    }

    public function incomeStatement()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;

            $fromDate = Request::get('from_date', date('Y-01-01'));
            $toDate = Request::get('to_date', date('Y-m-d'));

            $stmt = $pdo->prepare("
                SELECT fc.name as category_name,
                    SUM(il.amount) as invoiced_amount,
                    SUM(CASE WHEN i.status IN ('paid', 'partial') THEN il.amount * (i.amount_paid / i.total_amount) ELSE 0 END) as collected_amount
                FROM invoice_lines il
                INNER JOIN invoices i ON i.id = il.invoice_id
                LEFT JOIN fee_items fi ON fi.id = il.fee_item_id
                LEFT JOIN fee_categories fc ON fc.id = fi.fee_category_id
                WHERE i.school_id = ? AND DATE(i.invoice_date) BETWEEN ? AND ?
                GROUP BY fc.id
                ORDER BY collected_amount DESC
            ");
            $stmt->execute([$schoolId, $fromDate, $toDate]);
            $incomeByCategory = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("
                SELECT DATE_FORMAT(p.payment_date, '%Y-%m') as month, SUM(p.amount) as total_collected
                FROM payments p
                WHERE p.school_id = ? AND p.status = 'completed' AND DATE(p.payment_date) BETWEEN ? AND ?
                GROUP BY DATE_FORMAT(p.payment_date, '%Y-%m')
                ORDER BY month
            ");
            $stmt->execute([$schoolId, $fromDate, $toDate]);
            $monthlySummary = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("
                SELECT
                    (SELECT COALESCE(SUM(total_amount), 0) FROM invoices WHERE school_id = ? AND DATE(invoice_date) BETWEEN ? AND ?) as total_invoiced,
                    (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE school_id = ? AND status = 'completed' AND DATE(payment_date) BETWEEN ? AND ?) as total_collected
            ");
            $stmt->execute([$schoolId, $fromDate, $toDate, $schoolId, $fromDate, $toDate]);
            $totals = $stmt->fetch(PDO::FETCH_ASSOC);

            Response::view('finance/reports/income', [
                'pageTitle' => 'Income Statement',
                'incomeByCategory' => $incomeByCategory,
                'monthlySummary' => $monthlySummary,
                'totals' => $totals,
                'filters' => ['from_date' => $fromDate, 'to_date' => $toDate]
            ]);
        } catch (Exception $e) {
            error_log('Income Statement error: ' . $e->getMessage());
            flash('error', 'Failed to generate income statement');
            Response::redirect('/finance');
        }
    }

    // ==================== API ENDPOINTS ====================

    public function searchAccounts()
    {
        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;
            $search = $_GET['q'] ?? $_GET['search'] ?? '';

            if (strlen($search) < 2) {
                $this->jsonResponse(['results' => []]);
                return;
            }

            $stmt = $pdo->prepare("
                SELECT sfa.id, sfa.account_number, sfa.current_balance,
                    COALESCE(s.first_name, a.first_name) as first_name,
                    COALESCE(s.last_name, a.last_name) as last_name,
                    COALESCE(s.admission_number, a.admission_number) as admission_number
                FROM student_fee_accounts sfa
                LEFT JOIN students s ON s.id = sfa.student_id
                LEFT JOIN applicants a ON a.id = sfa.applicant_id
                WHERE sfa.school_id = ?
                AND sfa.account_status IN ('active', 'pending')
                AND (sfa.account_number LIKE ? OR CONCAT(COALESCE(s.first_name, a.first_name), ' ', COALESCE(s.last_name, a.last_name)) LIKE ? OR COALESCE(s.admission_number, a.admission_number) LIKE ?)
                ORDER BY COALESCE(s.last_name, a.last_name), COALESCE(s.first_name, a.first_name)
                LIMIT 20
            ");
            $searchParam = '%' . $search . '%';
            $stmt->execute([$schoolId, $searchParam, $searchParam, $searchParam]);
            $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $results = [];
            foreach ($accounts as $acc) {
                $results[] = [
                    'id' => $acc['id'],
                    'text' => $acc['account_number'] . ' - ' . $acc['first_name'] . ' ' . $acc['last_name'] . ($acc['admission_number'] ? ' (' . $acc['admission_number'] . ')' : ''),
                    'account_number' => $acc['account_number'],
                    'name' => $acc['first_name'] . ' ' . $acc['last_name'],
                    'admission_number' => $acc['admission_number'],
                    'balance' => $acc['current_balance']
                ];
            }

            $this->jsonResponse(['results' => $results]);
        } catch (Exception $e) {
            error_log('Search Accounts error: ' . $e->getMessage());
            $this->jsonResponse(['results' => [], 'error' => 'Search failed']);
        }
    }

    public function getAccountInvoices($accountId)
    {
        try {
            $pdo = Database::getTenantConnection();

            $stmt = $pdo->prepare("
                SELECT id, invoice_number, invoice_date, total_amount, balance, status
                FROM invoices
                WHERE student_fee_account_id = ? AND balance > 0
                ORDER BY invoice_date DESC
            ");
            $stmt->execute([$accountId]);
            $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->jsonResponse(['success' => true, 'invoices' => $invoices]);
        } catch (Exception $e) {
            error_log('Get Account Invoices error: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Failed to load invoices']);
        }
    }

    // ==================== HELPER METHODS ====================

    private function isAjax()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private function jsonResponse($data)
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
