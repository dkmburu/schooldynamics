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
            $schoolId = $_SESSION['school_id'] ?? null;

            $stmt = $pdo->prepare("
                SELECT * FROM chart_of_accounts
                WHERE school_id = ?
                ORDER BY account_code
            ");
            $stmt->execute([$schoolId]);
            $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::view('finance/chart_of_accounts', [
                'pageTitle' => 'Chart of Accounts',
                'accounts' => $accounts
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
            $schoolId = $_SESSION['school_id'] ?? null;
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
            $schoolId = $_SESSION['school_id'] ?? null;
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
            $schoolId = $_SESSION['school_id'] ?? null;

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
            $schoolId = $_SESSION['school_id'] ?? null;

            $stmt = $pdo->prepare("
                SELECT fc.*,
                    (SELECT COUNT(*) FROM fee_items fi WHERE fi.fee_category_id = fc.id) as item_count
                FROM fee_categories fc
                WHERE fc.school_id = ?
                ORDER BY fc.sort_order, fc.name
            ");
            $stmt->execute([$schoolId]);
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::view('finance/fee_categories', [
                'pageTitle' => 'Fee Categories',
                'categories' => $categories
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
            $schoolId = $_SESSION['school_id'] ?? null;

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
            $schoolId = $_SESSION['school_id'] ?? null;

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
            $schoolId = $_SESSION['school_id'] ?? null;

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
            $schoolId = $_SESSION['school_id'] ?? null;

            $stmt = $pdo->prepare("
                SELECT fi.*, fc.name as category_name, fc.code as category_code
                FROM fee_items fi
                LEFT JOIN fee_categories fc ON fc.id = fi.fee_category_id
                WHERE fi.school_id = ?
                ORDER BY fc.sort_order, fi.name
            ");
            $stmt->execute([$schoolId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("
                SELECT id, code, name FROM fee_categories
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
            $schoolId = $_SESSION['school_id'] ?? null;

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

    public function updateFeeItem($id)
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? null;

            $stmt = $pdo->prepare("
                UPDATE fee_items
                SET fee_category_id = ?, code = ?, name = ?, description = ?, default_amount = ?, is_mandatory = ?, is_recurring = ?, frequency = ?, is_active = ?, updated_at = NOW()
                WHERE id = ? AND school_id = ?
            ");
            $stmt->execute([
                $_POST['fee_category_id'],
                $_POST['code'],
                $_POST['name'],
                $_POST['description'] ?? null,
                $_POST['default_amount'] ?? 0,
                isset($_POST['is_mandatory']) ? 1 : 0,
                isset($_POST['is_recurring']) ? 1 : 0,
                $_POST['frequency'] ?? 'term',
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
            $schoolId = $_SESSION['school_id'] ?? null;

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
            $schoolId = $_SESSION['school_id'] ?? null;

            $stmt = $pdo->prepare("
                SELECT fs.*,
                    ay.year_name as academic_year,
                    glg.group_name as grade_group_name,
                    (SELECT COUNT(*) FROM fee_structure_items fsi WHERE fsi.fee_structure_id = fs.id) as item_count,
                    (SELECT SUM(fsi.amount) FROM fee_structure_items fsi WHERE fsi.fee_structure_id = fs.id) as total_amount
                FROM fee_structures fs
                LEFT JOIN academic_years ay ON ay.id = fs.academic_year_id
                LEFT JOIN grade_level_groups glg ON glg.id = fs.grade_level_group_id
                WHERE fs.school_id = ?
                ORDER BY ay.start_date DESC, fs.name
            ");
            $stmt->execute([$schoolId]);
            $structures = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("SELECT id, year_name FROM academic_years WHERE school_id = ? ORDER BY start_date DESC");
            $stmt->execute([$schoolId]);
            $academicYears = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("SELECT id, group_name FROM grade_level_groups WHERE school_id = ? AND is_active = 1 ORDER BY id");
            $stmt->execute([$schoolId]);
            $gradeGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("
                SELECT fi.id, fi.code, fi.name, fi.default_amount, fc.name as category_name
                FROM fee_items fi
                LEFT JOIN fee_categories fc ON fc.id = fi.fee_category_id
                WHERE fi.school_id = ? AND fi.is_active = 1
                ORDER BY fc.sort_order, fi.name
            ");
            $stmt->execute([$schoolId]);
            $feeItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::view('finance/fee_structures', [
                'pageTitle' => 'Fee Structures',
                'structures' => $structures,
                'academicYears' => $academicYears,
                'gradeGroups' => $gradeGroups,
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
            $schoolId = $_SESSION['school_id'] ?? null;

            $stmt = $pdo->prepare("
                SELECT fs.*, ay.year_name as academic_year, glg.group_name as grade_group_name
                FROM fee_structures fs
                LEFT JOIN academic_years ay ON ay.id = fs.academic_year_id
                LEFT JOIN grade_level_groups glg ON glg.id = fs.grade_level_group_id
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
                FROM fee_structure_items fsi
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

    public function storeFeeStructure()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? null;
            $userId = $_SESSION['user_id'] ?? null;

            $stmt = $pdo->prepare("
                INSERT INTO fee_structures (school_id, name, academic_year_id, grade_level_group_id, description, is_active, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $schoolId,
                $_POST['name'],
                $_POST['academic_year_id'],
                $_POST['grade_level_group_id'] ?: null,
                $_POST['description'] ?? null,
                isset($_POST['is_active']) ? 1 : 0,
                $userId
            ]);
            $structureId = $pdo->lastInsertId();

            // Add fee items
            if (!empty($_POST['fee_items']) && is_array($_POST['fee_items'])) {
                $stmt = $pdo->prepare("INSERT INTO fee_structure_items (fee_structure_id, fee_item_id, amount, created_at) VALUES (?, ?, ?, NOW())");
                foreach ($_POST['fee_items'] as $itemId => $amount) {
                    if ($amount > 0) {
                        $stmt->execute([$structureId, $itemId, $amount]);
                    }
                }
            }

            flash('success', 'Fee structure created successfully');
        } catch (Exception $e) {
            error_log('Store Fee Structure error: ' . $e->getMessage());
            flash('error', 'Failed to create fee structure');
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
            $schoolId = $_SESSION['school_id'] ?? null;
            $userId = $_SESSION['user_id'] ?? null;

            $stmt = $pdo->prepare("
                UPDATE fee_structures
                SET name = ?, academic_year_id = ?, grade_level_group_id = ?, description = ?, is_active = ?, updated_by = ?, updated_at = NOW()
                WHERE id = ? AND school_id = ?
            ");
            $stmt->execute([
                $_POST['name'],
                $_POST['academic_year_id'],
                $_POST['grade_level_group_id'] ?: null,
                $_POST['description'] ?? null,
                isset($_POST['is_active']) ? 1 : 0,
                $userId,
                $id,
                $schoolId
            ]);

            // Update fee items
            $stmt = $pdo->prepare("DELETE FROM fee_structure_items WHERE fee_structure_id = ?");
            $stmt->execute([$id]);

            if (!empty($_POST['fee_items']) && is_array($_POST['fee_items'])) {
                $stmt = $pdo->prepare("INSERT INTO fee_structure_items (fee_structure_id, fee_item_id, amount, created_at) VALUES (?, ?, ?, NOW())");
                foreach ($_POST['fee_items'] as $itemId => $amount) {
                    if ($amount > 0) {
                        $stmt->execute([$id, $itemId, $amount]);
                    }
                }
            }

            flash('success', 'Fee structure updated successfully');
        } catch (Exception $e) {
            error_log('Update Fee Structure error: ' . $e->getMessage());
            flash('error', 'Failed to update fee structure');
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
            $schoolId = $_SESSION['school_id'] ?? null;

            $stmt = $pdo->prepare("DELETE FROM fee_structure_items WHERE fee_structure_id = ?");
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
            $schoolId = $_SESSION['school_id'] ?? null;

            $stmt = $pdo->prepare("
                SELECT tt.*,
                    tz.zone_name,
                    ay.year_name as academic_year
                FROM transport_tariffs tt
                LEFT JOIN transport_zones tz ON tz.id = tt.zone_id
                LEFT JOIN academic_years ay ON ay.id = tt.academic_year_id
                WHERE tt.school_id = ?
                ORDER BY ay.start_date DESC, tz.zone_name
            ");
            $stmt->execute([$schoolId]);
            $tariffs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("SELECT id, zone_name, description FROM transport_zones WHERE school_id = ? AND is_active = 1 ORDER BY zone_name");
            $stmt->execute([$schoolId]);
            $zones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("SELECT id, year_name FROM academic_years WHERE school_id = ? ORDER BY start_date DESC");
            $stmt->execute([$schoolId]);
            $academicYears = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::view('finance/transport_tariffs', [
                'pageTitle' => 'Transport Tariffs',
                'tariffs' => $tariffs,
                'zones' => $zones,
                'academicYears' => $academicYears
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
            $schoolId = $_SESSION['school_id'] ?? null;
            $userId = $_SESSION['user_id'] ?? null;

            $stmt = $pdo->prepare("
                INSERT INTO transport_tariffs (school_id, zone_id, academic_year_id, amount_per_term, amount_one_way, is_active, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $schoolId,
                $_POST['zone_id'],
                $_POST['academic_year_id'],
                $_POST['amount_per_term'] ?? 0,
                $_POST['amount_one_way'] ?? 0,
                isset($_POST['is_active']) ? 1 : 0,
                $userId
            ]);

            if ($this->isAjax()) {
                $this->jsonResponse(['success' => true, 'message' => 'Tariff created successfully']);
            } else {
                flash('success', 'Transport tariff created successfully');
                Response::redirect('/finance/transport-tariffs');
            }
        } catch (Exception $e) {
            error_log('Store Tariff error: ' . $e->getMessage());
            if ($this->isAjax()) {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to create tariff']);
            } else {
                flash('error', 'Failed to create transport tariff');
                Response::redirect('/finance/transport-tariffs');
            }
        }
    }

    public function updateTariff($id)
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? null;
            $userId = $_SESSION['user_id'] ?? null;

            $stmt = $pdo->prepare("
                UPDATE transport_tariffs
                SET zone_id = ?, academic_year_id = ?, amount_per_term = ?, amount_one_way = ?, is_active = ?, updated_by = ?, updated_at = NOW()
                WHERE id = ? AND school_id = ?
            ");
            $stmt->execute([
                $_POST['zone_id'],
                $_POST['academic_year_id'],
                $_POST['amount_per_term'] ?? 0,
                $_POST['amount_one_way'] ?? 0,
                isset($_POST['is_active']) ? 1 : 0,
                $userId,
                $id,
                $schoolId
            ]);

            if ($this->isAjax()) {
                $this->jsonResponse(['success' => true, 'message' => 'Tariff updated successfully']);
            } else {
                flash('success', 'Transport tariff updated successfully');
                Response::redirect('/finance/transport-tariffs');
            }
        } catch (Exception $e) {
            error_log('Update Tariff error: ' . $e->getMessage());
            if ($this->isAjax()) {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to update tariff']);
            } else {
                flash('error', 'Failed to update transport tariff');
                Response::redirect('/finance/transport-tariffs');
            }
        }
    }

    public function deleteTariff($id)
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? null;

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
            $schoolId = $_SESSION['school_id'] ?? null;
            $userId = $_SESSION['user_id'] ?? null;

            $stmt = $pdo->prepare("
                INSERT INTO transport_zones (school_id, zone_name, description, areas_covered, is_active, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $schoolId,
                $_POST['zone_name'],
                $_POST['description'] ?? null,
                $_POST['areas_covered'] ?? null,
                isset($_POST['is_active']) ? 1 : 0,
                $userId
            ]);

            if ($this->isAjax()) {
                $this->jsonResponse(['success' => true, 'message' => 'Zone created successfully']);
            } else {
                flash('success', 'Transport zone created successfully');
                Response::redirect('/finance/transport-tariffs');
            }
        } catch (Exception $e) {
            error_log('Store Zone error: ' . $e->getMessage());
            if ($this->isAjax()) {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to create zone']);
            } else {
                flash('error', 'Failed to create transport zone');
                Response::redirect('/finance/transport-tariffs');
            }
        }
    }

    public function updateZone($id)
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? null;
            $userId = $_SESSION['user_id'] ?? null;

            $stmt = $pdo->prepare("
                UPDATE transport_zones
                SET zone_name = ?, description = ?, areas_covered = ?, is_active = ?, updated_by = ?, updated_at = NOW()
                WHERE id = ? AND school_id = ?
            ");
            $stmt->execute([
                $_POST['zone_name'],
                $_POST['description'] ?? null,
                $_POST['areas_covered'] ?? null,
                isset($_POST['is_active']) ? 1 : 0,
                $userId,
                $id,
                $schoolId
            ]);

            if ($this->isAjax()) {
                $this->jsonResponse(['success' => true, 'message' => 'Zone updated successfully']);
            } else {
                flash('success', 'Transport zone updated successfully');
                Response::redirect('/finance/transport-tariffs');
            }
        } catch (Exception $e) {
            error_log('Update Zone error: ' . $e->getMessage());
            if ($this->isAjax()) {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to update zone']);
            } else {
                flash('error', 'Failed to update transport zone');
                Response::redirect('/finance/transport-tariffs');
            }
        }
    }

    public function deleteZone($id)
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? null;

            // Check if zone has tariffs
            $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM transport_tariffs WHERE zone_id = ?");
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
            $schoolId = $_SESSION['school_id'] ?? null;

            $status = Request::get('status', '');
            $fromDate = Request::get('from_date', '');
            $toDate = Request::get('to_date', '');
            $search = Request::get('search', '');

            $where = ['i.school_id = ?'];
            $params = [$schoolId];

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
                    COALESCE(s.first_name, a.first_name) as student_first_name,
                    COALESCE(s.last_name, a.last_name) as student_last_name,
                    COALESCE(s.admission_number, a.admission_number) as admission_number
                FROM invoices i
                LEFT JOIN student_fee_accounts sfa ON sfa.id = i.student_fee_account_id
                LEFT JOIN students s ON s.id = sfa.student_id
                LEFT JOIN applicants a ON a.id = sfa.applicant_id
                WHERE {$whereClause}
                ORDER BY i.created_at DESC
                LIMIT 500
            ");
            $stmt->execute($params);
            $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("SELECT id, grade_name FROM grades WHERE school_id = ? ORDER BY sort_order");
            $stmt->execute([$schoolId]);
            $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::view('finance/invoices', [
                'pageTitle' => 'Invoices',
                'invoices' => $invoices,
                'grades' => $grades,
                'filters' => [
                    'status' => $status,
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                    'search' => $search
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
            $schoolId = $_SESSION['school_id'] ?? null;

            $stmt = $pdo->prepare("
                SELECT i.*,
                    sfa.account_number,
                    COALESCE(s.first_name, a.first_name) as student_first_name,
                    COALESCE(s.last_name, a.last_name) as student_last_name,
                    COALESCE(s.admission_number, a.admission_number) as admission_number,
                    ay.year_name as academic_year
                FROM invoices i
                LEFT JOIN student_fee_accounts sfa ON sfa.id = i.student_fee_account_id
                LEFT JOIN students s ON s.id = sfa.student_id
                LEFT JOIN applicants a ON a.id = sfa.applicant_id
                LEFT JOIN academic_years ay ON ay.id = i.academic_year_id
                WHERE i.id = ? AND i.school_id = ?
            ");
            $stmt->execute([$id, $schoolId]);
            $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$invoice) {
                flash('error', 'Invoice not found');
                Response::redirect('/finance/invoices');
                return;
            }

            $stmt = $pdo->prepare("
                SELECT il.*, fi.name as fee_item_name, fc.name as category_name
                FROM invoice_lines il
                LEFT JOIN fee_items fi ON fi.id = il.fee_item_id
                LEFT JOIN fee_categories fc ON fc.id = fi.fee_category_id
                WHERE il.invoice_id = ?
                ORDER BY il.id
            ");
            $stmt->execute([$id]);
            $lines = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("SELECT * FROM payments WHERE invoice_id = ? ORDER BY payment_date DESC");
            $stmt->execute([$id]);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::view('finance/invoice_view', [
                'pageTitle' => 'Invoice ' . $invoice['invoice_number'],
                'invoice' => $invoice,
                'lines' => $lines,
                'payments' => $payments
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
            $schoolId = $_SESSION['school_id'] ?? null;

            $stmt = $pdo->prepare("SELECT id, year_name FROM academic_years WHERE school_id = ? ORDER BY start_date DESC");
            $stmt->execute([$schoolId]);
            $academicYears = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("SELECT id, grade_name FROM grades WHERE school_id = ? AND is_active = 1 ORDER BY sort_order");
            $stmt->execute([$schoolId]);
            $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("
                SELECT fs.id, fs.name, glg.group_name as grade_group
                FROM fee_structures fs
                LEFT JOIN grade_level_groups glg ON glg.id = fs.grade_level_group_id
                WHERE fs.school_id = ? AND fs.is_active = 1
                ORDER BY fs.name
            ");
            $stmt->execute([$schoolId]);
            $feeStructures = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::view('finance/generate_invoices', [
                'pageTitle' => 'Generate Invoices',
                'academicYears' => $academicYears,
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
            $schoolId = $_SESSION['school_id'] ?? null;
            $userId = $_SESSION['user_id'] ?? null;

            $academicYearId = $_POST['academic_year_id'];
            $termId = $_POST['term_id'] ?? null;
            $gradeId = $_POST['grade_id'] ?? null;
            $feeStructureId = $_POST['fee_structure_id'];
            $dueDate = $_POST['due_date'];

            // Get fee structure items
            $stmt = $pdo->prepare("
                SELECT fsi.*, fi.name as item_name
                FROM fee_structure_items fsi
                LEFT JOIN fee_items fi ON fi.id = fsi.fee_item_id
                WHERE fsi.fee_structure_id = ?
            ");
            $stmt->execute([$feeStructureId]);
            $structureItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($structureItems)) {
                flash('error', 'No fee items in selected structure');
                Response::redirect('/finance/invoices/generate');
                return;
            }

            // Get students to invoice
            $studentWhere = "s.status = 'active' AND sfa.id IS NOT NULL AND s.school_id = ?";
            $studentParams = [$schoolId];

            if ($gradeId) {
                $studentWhere .= " AND st.grade_id = ?";
                $studentParams[] = $gradeId;
            }

            $stmt = $pdo->prepare("
                SELECT s.id, s.first_name, s.last_name, sfa.id as fee_account_id
                FROM students s
                INNER JOIN student_fee_accounts sfa ON sfa.student_id = s.id
                LEFT JOIN student_enrollments se ON se.student_id = s.id AND se.is_current = 1
                LEFT JOIN streams st ON st.id = se.stream_id
                WHERE {$studentWhere}
            ");
            $stmt->execute($studentParams);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $generatedCount = 0;

            foreach ($students as $student) {
                $invoiceNumber = 'INV' . date('Y') . strtoupper(substr(md5(uniqid()), 0, 6));
                $totalAmount = array_sum(array_column($structureItems, 'amount'));

                $stmt = $pdo->prepare("
                    INSERT INTO invoices (school_id, student_fee_account_id, invoice_number, invoice_date, due_date, academic_year_id, term_id, total_amount, amount_paid, balance, status, created_by, created_at)
                    VALUES (?, ?, ?, CURDATE(), ?, ?, ?, ?, 0, ?, 'unpaid', ?, NOW())
                ");
                $stmt->execute([
                    $schoolId, $student['fee_account_id'], $invoiceNumber, $dueDate,
                    $academicYearId, $termId, $totalAmount, $totalAmount, $userId
                ]);
                $invoiceId = $pdo->lastInsertId();

                foreach ($structureItems as $item) {
                    $stmt = $pdo->prepare("
                        INSERT INTO invoice_lines (invoice_id, fee_item_id, description, quantity, unit_price, amount, created_at)
                        VALUES (?, ?, ?, 1, ?, ?, NOW())
                    ");
                    $stmt->execute([$invoiceId, $item['fee_item_id'], $item['item_name'], $item['amount'], $item['amount']]);
                }

                $stmt = $pdo->prepare("UPDATE student_fee_accounts SET current_balance = current_balance + ? WHERE id = ?");
                $stmt->execute([$totalAmount, $student['fee_account_id']]);

                $generatedCount++;
            }

            flash('success', "Generated {$generatedCount} invoices successfully");
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
            $schoolId = $_SESSION['school_id'] ?? null;

            $stmt = $pdo->prepare("
                SELECT cn.*,
                    i.invoice_number,
                    sfa.account_number,
                    COALESCE(s.first_name, a.first_name) as student_first_name,
                    COALESCE(s.last_name, a.last_name) as student_last_name,
                    COALESCE(s.admission_number, a.admission_number) as admission_number,
                    u.first_name as created_by_first_name,
                    u.last_name as created_by_last_name
                FROM credit_notes cn
                LEFT JOIN invoices i ON i.id = cn.invoice_id
                LEFT JOIN student_fee_accounts sfa ON sfa.id = cn.student_fee_account_id
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
            $schoolId = $_SESSION['school_id'] ?? null;

            $stmt = $pdo->prepare("
                SELECT cn.*,
                    i.invoice_number,
                    sfa.account_number,
                    COALESCE(s.first_name, a.first_name) as student_first_name,
                    COALESCE(s.last_name, a.last_name) as student_last_name,
                    COALESCE(s.admission_number, a.admission_number) as admission_number,
                    u.first_name as created_by_first_name,
                    u.last_name as created_by_last_name,
                    u2.first_name as approved_by_first_name,
                    u2.last_name as approved_by_last_name
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
            $schoolId = $_SESSION['school_id'] ?? null;
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
            $schoolId = $_SESSION['school_id'] ?? null;

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
            $schoolId = $_SESSION['school_id'] ?? null;

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
            $schoolId = $_SESSION['school_id'] ?? null;
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
            $schoolId = $_SESSION['school_id'] ?? null;

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
            $schoolId = $_SESSION['school_id'] ?? null;

            $status = Request::get('status', '');
            $paymentMethod = Request::get('payment_method', '');
            $fromDate = Request::get('from_date', date('Y-m-01'));
            $toDate = Request::get('to_date', date('Y-m-d'));
            $search = Request::get('search', '');

            $where = ['p.school_id = ?'];
            $params = [$schoolId];

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
                    i.invoice_number
                FROM payments p
                LEFT JOIN student_fee_accounts sfa ON sfa.id = p.student_fee_account_id
                LEFT JOIN students s ON s.id = sfa.student_id
                LEFT JOIN applicants a ON a.id = sfa.applicant_id
                LEFT JOIN invoices i ON i.id = p.invoice_id
                WHERE {$whereClause}
                ORDER BY p.payment_date DESC, p.created_at DESC
                LIMIT 500
            ");
            $stmt->execute($params);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("SELECT id, code, name FROM payment_methods WHERE school_id = ? AND is_active = 1 ORDER BY name");
            $stmt->execute([$schoolId]);
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
                    'search' => $search
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
            $schoolId = $_SESSION['school_id'] ?? null;

            $stmt = $pdo->prepare("
                SELECT p.*,
                    sfa.account_number,
                    COALESCE(s.first_name, a.first_name) as student_first_name,
                    COALESCE(s.last_name, a.last_name) as student_last_name,
                    COALESCE(s.admission_number, a.admission_number) as admission_number,
                    i.invoice_number,
                    pm.name as payment_method_name,
                    u.first_name as received_by_first_name,
                    u.last_name as received_by_last_name
                FROM payments p
                LEFT JOIN student_fee_accounts sfa ON sfa.id = p.student_fee_account_id
                LEFT JOIN students s ON s.id = sfa.student_id
                LEFT JOIN applicants a ON a.id = sfa.applicant_id
                LEFT JOIN invoices i ON i.id = p.invoice_id
                LEFT JOIN payment_methods pm ON pm.id = p.payment_method_id
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
            $schoolId = $_SESSION['school_id'] ?? null;

            $feeAccountId = Request::get('fee_account_id');
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

            $stmt = $pdo->prepare("SELECT id, code, name FROM payment_methods WHERE school_id = ? AND is_active = 1 ORDER BY sort_order, name");
            $stmt->execute([$schoolId]);
            $paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("SELECT id, account_name, account_number, bank_name FROM bank_accounts WHERE school_id = ? AND is_active = 1 ORDER BY account_name");
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
            $schoolId = $_SESSION['school_id'] ?? null;
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
                INSERT INTO payments (school_id, student_fee_account_id, invoice_id, receipt_number, payment_date, amount, payment_method, payment_method_id, reference_number, notes, status, received_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed', ?, NOW())
            ");
            $stmt->execute([
                $schoolId, $feeAccountId, $invoiceId, $receiptNumber, $paymentDate, $amount,
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
            $schoolId = $_SESSION['school_id'] ?? null;

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

            $stmt = $pdo->prepare("SELECT id, grade_name FROM grades WHERE school_id = ? ORDER BY sort_order");
            $stmt->execute([$schoolId]);
            $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total_accounts, SUM(CASE WHEN current_balance > 0 THEN 1 ELSE 0 END) as with_balance,
                    COALESCE(SUM(current_balance), 0) as total_outstanding
                FROM student_fee_accounts WHERE school_id = ?
            ");
            $stmt->execute([$schoolId]);
            $summary = $stmt->fetch(PDO::FETCH_ASSOC);

            Response::view('finance/student_accounts', [
                'pageTitle' => 'Student Accounts',
                'accounts' => $accounts,
                'grades' => $grades,
                'filters' => ['status' => $status, 'balance_type' => $balanceType, 'search' => $search],
                'summary' => $summary
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
            $schoolId = $_SESSION['school_id'] ?? null;

            $stmt = $pdo->prepare("
                SELECT sfa.*,
                    COALESCE(s.first_name, a.first_name) as first_name,
                    COALESCE(s.last_name, a.last_name) as last_name,
                    COALESCE(s.admission_number, a.admission_number) as admission_number,
                    g.grade_name,
                    st.stream_name
                FROM student_fee_accounts sfa
                LEFT JOIN students s ON s.id = sfa.student_id
                LEFT JOIN applicants a ON a.id = sfa.applicant_id
                LEFT JOIN student_enrollments se ON se.student_id = s.id AND se.is_current = 1
                LEFT JOIN streams st ON st.id = se.stream_id
                LEFT JOIN grades g ON g.id = st.grade_id
                WHERE sfa.id = ? AND sfa.school_id = ?
            ");
            $stmt->execute([$id, $schoolId]);
            $account = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$account) {
                flash('error', 'Account not found');
                Response::redirect('/finance/student-accounts');
                return;
            }

            $stmt = $pdo->prepare("
                SELECT i.*, ay.year_name as academic_year
                FROM invoices i
                LEFT JOIN academic_years ay ON ay.id = i.academic_year_id
                WHERE i.student_fee_account_id = ?
                ORDER BY i.invoice_date DESC
            ");
            $stmt->execute([$id]);
            $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("
                SELECT p.*, i.invoice_number
                FROM payments p
                LEFT JOIN invoices i ON i.id = p.invoice_id
                WHERE p.student_fee_account_id = ?
                ORDER BY p.payment_date DESC
            ");
            $stmt->execute([$id]);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("
                SELECT cn.*, i.invoice_number
                FROM credit_notes cn
                LEFT JOIN invoices i ON i.id = cn.invoice_id
                WHERE cn.student_fee_account_id = ?
                ORDER BY cn.created_at DESC
            ");
            $stmt->execute([$id]);
            $creditNotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::view('finance/student_account_view', [
                'pageTitle' => 'Account: ' . $account['account_number'],
                'account' => $account,
                'invoices' => $invoices,
                'payments' => $payments,
                'creditNotes' => $creditNotes
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
            $schoolId = $_SESSION['school_id'] ?? null;

            $stmt = $pdo->prepare("
                SELECT fa.*,
                    (SELECT COUNT(*) FROM family_account_members fam WHERE fam.family_account_id = fa.id) as member_count,
                    (SELECT SUM(sfa.current_balance) FROM student_fee_accounts sfa
                     INNER JOIN family_account_members fam ON fam.student_fee_account_id = sfa.id
                     WHERE fam.family_account_id = fa.id) as total_balance
                FROM family_accounts fa
                WHERE fa.school_id = ?
                ORDER BY fa.family_name
            ");
            $stmt->execute([$schoolId]);
            $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::view('finance/family_accounts', [
                'pageTitle' => 'Family Accounts',
                'accounts' => $accounts
            ]);
        } catch (Exception $e) {
            error_log('Family Accounts error: ' . $e->getMessage());
            flash('error', 'Failed to load family accounts');
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
            $schoolId = $_SESSION['school_id'] ?? null;

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
            $schoolId = $_SESSION['school_id'] ?? null;
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
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();
            $schoolId = $_SESSION['school_id'] ?? null;

            $fromDate = Request::get('from_date', date('Y-m-01'));
            $toDate = Request::get('to_date', date('Y-m-d'));
            $paymentMethod = Request::get('payment_method', '');

            $where = ['p.school_id = ?', "p.status = 'completed'"];
            $params = [$schoolId];

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

            $stmt = $pdo->prepare("
                SELECT DATE(p.payment_date) as date, COUNT(*) as count, SUM(p.amount) as total
                FROM payments p
                WHERE {$whereClause}
                GROUP BY DATE(p.payment_date)
                ORDER BY DATE(p.payment_date)
            ");
            $stmt->execute($params);
            $dailySummary = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("
                SELECT p.payment_method, COUNT(*) as count, SUM(p.amount) as total
                FROM payments p
                WHERE {$whereClause}
                GROUP BY p.payment_method
                ORDER BY total DESC
            ");
            $stmt->execute($params);
            $byMethod = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total FROM payments p WHERE {$whereClause}");
            $stmt->execute($params);
            $total = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("SELECT id, code, name FROM payment_methods WHERE school_id = ? ORDER BY name");
            $stmt->execute([$schoolId]);
            $paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("SELECT id, grade_name FROM grades WHERE school_id = ? ORDER BY sort_order");
            $stmt->execute([$schoolId]);
            $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::view('finance/reports/collection', [
                'pageTitle' => 'Collection Report',
                'dailySummary' => $dailySummary,
                'byMethod' => $byMethod,
                'total' => $total,
                'filters' => ['from_date' => $fromDate, 'to_date' => $toDate, 'payment_method' => $paymentMethod],
                'paymentMethods' => $paymentMethods,
                'grades' => $grades
            ]);
        } catch (Exception $e) {
            error_log('Collection Report error: ' . $e->getMessage());
            flash('error', 'Failed to generate collection report');
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
            $schoolId = $_SESSION['school_id'] ?? null;

            $minBalance = Request::get('min_balance', '');

            $where = ['sfa.school_id = ?', 'sfa.current_balance > 0'];
            $params = [$schoolId];

            if (!empty($minBalance)) {
                $where[] = 'sfa.current_balance >= ?';
                $params[] = floatval($minBalance);
            }

            $whereClause = implode(' AND ', $where);

            $stmt = $pdo->prepare("
                SELECT g.grade_name, COUNT(DISTINCT sfa.id) as account_count, SUM(sfa.current_balance) as total_outstanding
                FROM student_fee_accounts sfa
                LEFT JOIN students s ON s.id = sfa.student_id
                LEFT JOIN student_enrollments se ON se.student_id = s.id AND se.is_current = 1
                LEFT JOIN streams st ON st.id = se.stream_id
                LEFT JOIN grades g ON g.id = st.grade_id
                WHERE {$whereClause}
                GROUP BY g.id
                ORDER BY total_outstanding DESC
            ");
            $stmt->execute($params);
            $byGrade = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("
                SELECT sfa.account_number, sfa.current_balance,
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

            $stmt = $pdo->prepare("SELECT COUNT(*) as count, COALESCE(SUM(current_balance), 0) as total FROM student_fee_accounts sfa WHERE {$whereClause}");
            $stmt->execute($params);
            $total = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("SELECT id, grade_name FROM grades WHERE school_id = ? ORDER BY sort_order");
            $stmt->execute([$schoolId]);
            $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::view('finance/reports/outstanding', [
                'pageTitle' => 'Outstanding Balances Report',
                'byGrade' => $byGrade,
                'topBalances' => $topBalances,
                'total' => $total,
                'filters' => ['min_balance' => $minBalance],
                'grades' => $grades
            ]);
        } catch (Exception $e) {
            error_log('Outstanding Report error: ' . $e->getMessage());
            flash('error', 'Failed to generate outstanding report');
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
            $schoolId = $_SESSION['school_id'] ?? null;

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
            $schoolId = $_SESSION['school_id'] ?? null;
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
