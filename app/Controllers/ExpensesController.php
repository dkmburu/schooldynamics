<?php

/**
 * Expenses Controller
 * Handles Suppliers, Purchase Orders, GRN, Supplier Invoices, and Payments
 * Multi-tenant architecture - no school_id filtering needed (each tenant has own DB)
 */
class ExpensesController
{
    // ==================== MAIN INDEX (TABBED VIEW) ====================

    public function index($tab = 'suppliers')
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        try {
            $pdo = Database::getTenantConnection();

            // Validate tab
            $validTabs = ['suppliers', 'purchase-orders', 'grn', 'invoices', 'payments'];
            if (!in_array($tab, $validTabs)) {
                $tab = 'suppliers';
            }

            // Get summary stats for dashboard cards
            $stats = $this->getExpenseStats($pdo);

            // Get supplier categories for forms
            $categories = $this->getSupplierCategories($pdo);

            // Get data based on active tab
            $tabData = [];
            switch ($tab) {
                case 'suppliers':
                    $tabData = $this->getSuppliersData($pdo);
                    break;
                case 'purchase-orders':
                    $tabData = $this->getPurchaseOrdersData($pdo);
                    break;
                case 'grn':
                    $tabData = $this->getGRNData($pdo);
                    break;
                case 'invoices':
                    $tabData = $this->getSupplierInvoicesData($pdo);
                    break;
                case 'payments':
                    $tabData = $this->getSupplierPaymentsData($pdo);
                    break;
            }

            Response::view('finance/expenses', [
                'pageTitle' => 'Expense Management',
                'activeTab' => $tab,
                'stats' => $stats,
                'categories' => $categories,
                'tabData' => $tabData
            ]);
        } catch (Exception $e) {
            error_log('Expenses Controller error: ' . $e->getMessage());
            if (Response::isAjax()) {
                echo '<div class="alert alert-danger m-3"><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
                return;
            }
            flash('error', 'Failed to load expenses: ' . $e->getMessage());
            Response::redirect('/finance');
        }
    }

    // ==================== STATS & HELPER METHODS ====================

    private function getExpenseStats($pdo)
    {
        $stats = [
            'total_suppliers' => 0,
            'active_suppliers' => 0,
            'pending_orders' => 0,
            'outstanding_payables' => 0,
            'payments_this_month' => 0
        ];

        try {
            // Supplier counts
            $stmt = $pdo->query("
                SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active
                FROM suppliers
            ");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['total_suppliers'] = (int)($row['total'] ?? 0);
            $stats['active_suppliers'] = (int)($row['active'] ?? 0);

            // Pending purchase orders
            $stmt = $pdo->query("
                SELECT COUNT(*) as count
                FROM purchase_orders
                WHERE status IN ('draft', 'pending_approval', 'approved', 'sent')
            ");
            $stats['pending_orders'] = (int)$stmt->fetchColumn();

            // Outstanding payables (unpaid supplier invoices)
            $stmt = $pdo->query("
                SELECT COALESCE(SUM(balance), 0) as total
                FROM supplier_invoices
                WHERE status IN ('pending', 'approved', 'partial')
            ");
            $stats['outstanding_payables'] = (float)$stmt->fetchColumn();

            // Payments this month
            $stmt = $pdo->query("
                SELECT COALESCE(SUM(amount), 0) as total
                FROM supplier_payments
                WHERE status = 'paid'
                AND MONTH(payment_date) = MONTH(CURRENT_DATE())
                AND YEAR(payment_date) = YEAR(CURRENT_DATE())
            ");
            $stats['payments_this_month'] = (float)$stmt->fetchColumn();

        } catch (PDOException $e) {
            // Tables may not exist yet - return defaults
            error_log('Expense stats error: ' . $e->getMessage());
        }

        return $stats;
    }

    private function getSupplierCategories($pdo)
    {
        try {
            $stmt = $pdo->query("
                SELECT id, name FROM supplier_categories
                WHERE is_active = 1
                ORDER BY name
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    private function getSuppliersData($pdo)
    {
        $search = Request::get('search', '');
        $status = Request::get('status', '');
        $category = Request::get('category', '');

        $where = ['1=1'];
        $params = [];

        if (!empty($search)) {
            $where[] = "(s.name LIKE ? OR s.supplier_code LIKE ? OR s.contact_person LIKE ?)";
            $searchTerm = '%' . $search . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if ($status !== '') {
            $where[] = 's.is_active = ?';
            $params[] = ($status === 'active') ? 1 : 0;
        }

        if (!empty($category)) {
            $where[] = 's.category_id = ?';
            $params[] = $category;
        }

        $whereClause = implode(' AND ', $where);

        try {
            $stmt = $pdo->prepare("
                SELECT s.*, sc.name as category_name
                FROM suppliers s
                LEFT JOIN supplier_categories sc ON sc.id = s.category_id
                WHERE {$whereClause}
                ORDER BY s.name
            ");
            $stmt->execute($params);
            $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $suppliers = [];
        }

        return [
            'suppliers' => $suppliers,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'category' => $category
            ]
        ];
    }

    private function getPurchaseOrdersData($pdo)
    {
        $search = Request::get('search', '');
        $status = Request::get('status', '');
        $supplier = Request::get('supplier', '');

        $where = ['1=1'];
        $params = [];

        if (!empty($search)) {
            $where[] = "(po.po_number LIKE ? OR s.name LIKE ?)";
            $searchTerm = '%' . $search . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (!empty($status)) {
            $where[] = 'po.status = ?';
            $params[] = $status;
        }

        if (!empty($supplier)) {
            $where[] = 'po.supplier_id = ?';
            $params[] = $supplier;
        }

        $whereClause = implode(' AND ', $where);

        try {
            $stmt = $pdo->prepare("
                SELECT po.*, s.name as supplier_name,
                    u1.full_name as prepared_by_name,
                    u2.full_name as approved_by_name
                FROM purchase_orders po
                LEFT JOIN suppliers s ON s.id = po.supplier_id
                LEFT JOIN users u1 ON u1.id = po.prepared_by
                LEFT JOIN users u2 ON u2.id = po.approved_by
                WHERE {$whereClause}
                ORDER BY po.order_date DESC, po.id DESC
            ");
            $stmt->execute($params);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $orders = [];
        }

        // Get suppliers for filter dropdown
        try {
            $stmt = $pdo->query("SELECT id, name FROM suppliers WHERE is_active = 1 ORDER BY name");
            $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $suppliers = [];
        }

        return [
            'orders' => $orders,
            'suppliers' => $suppliers,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'supplier' => $supplier
            ]
        ];
    }

    private function getGRNData($pdo)
    {
        $search = Request::get('search', '');
        $status = Request::get('status', '');

        $where = ['1=1'];
        $params = [];

        if (!empty($search)) {
            $where[] = "(grn.grn_number LIKE ? OR po.po_number LIKE ? OR s.name LIKE ?)";
            $searchTerm = '%' . $search . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (!empty($status)) {
            $where[] = 'grn.status = ?';
            $params[] = $status;
        }

        $whereClause = implode(' AND ', $where);

        try {
            $stmt = $pdo->prepare("
                SELECT grn.*, po.po_number, s.name as supplier_name,
                    u1.full_name as received_by_name,
                    u2.full_name as confirmed_by_name
                FROM goods_received_notes grn
                LEFT JOIN purchase_orders po ON po.id = grn.purchase_order_id
                LEFT JOIN suppliers s ON s.id = grn.supplier_id
                LEFT JOIN users u1 ON u1.id = grn.received_by
                LEFT JOIN users u2 ON u2.id = grn.confirmed_by
                WHERE {$whereClause}
                ORDER BY grn.received_date DESC, grn.id DESC
            ");
            $stmt->execute($params);
            $grns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $grns = [];
        }

        return [
            'grns' => $grns,
            'filters' => [
                'search' => $search,
                'status' => $status
            ]
        ];
    }

    private function getSupplierInvoicesData($pdo)
    {
        $search = Request::get('search', '');
        $status = Request::get('status', '');
        $supplier = Request::get('supplier', '');

        $where = ['1=1'];
        $params = [];

        if (!empty($search)) {
            $where[] = "(si.invoice_number LIKE ? OR si.internal_ref LIKE ? OR s.name LIKE ?)";
            $searchTerm = '%' . $search . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (!empty($status)) {
            $where[] = 'si.status = ?';
            $params[] = $status;
        }

        if (!empty($supplier)) {
            $where[] = 'si.supplier_id = ?';
            $params[] = $supplier;
        }

        $whereClause = implode(' AND ', $where);

        try {
            $stmt = $pdo->prepare("
                SELECT si.*, s.name as supplier_name,
                    po.po_number,
                    u1.full_name as created_by_name,
                    u2.full_name as approved_by_name,
                    DATEDIFF(si.due_date, CURRENT_DATE()) as days_to_due
                FROM supplier_invoices si
                LEFT JOIN suppliers s ON s.id = si.supplier_id
                LEFT JOIN purchase_orders po ON po.id = si.purchase_order_id
                LEFT JOIN users u1 ON u1.id = si.created_by
                LEFT JOIN users u2 ON u2.id = si.approved_by
                WHERE {$whereClause}
                ORDER BY si.due_date ASC, si.id DESC
            ");
            $stmt->execute($params);
            $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $invoices = [];
        }

        // Get suppliers for filter dropdown
        try {
            $stmt = $pdo->query("SELECT id, name FROM suppliers WHERE is_active = 1 ORDER BY name");
            $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $suppliers = [];
        }

        return [
            'invoices' => $invoices,
            'suppliers' => $suppliers,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'supplier' => $supplier
            ]
        ];
    }

    private function getSupplierPaymentsData($pdo)
    {
        $search = Request::get('search', '');
        $status = Request::get('status', '');
        $supplier = Request::get('supplier', '');
        $fromDate = Request::get('from_date', '');
        $toDate = Request::get('to_date', '');

        $where = ['1=1'];
        $params = [];

        if (!empty($search)) {
            $where[] = "(sp.payment_number LIKE ? OR sp.reference_number LIKE ? OR s.name LIKE ?)";
            $searchTerm = '%' . $search . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (!empty($status)) {
            $where[] = 'sp.status = ?';
            $params[] = $status;
        }

        if (!empty($supplier)) {
            $where[] = 'sp.supplier_id = ?';
            $params[] = $supplier;
        }

        if (!empty($fromDate)) {
            $where[] = 'sp.payment_date >= ?';
            $params[] = $fromDate;
        }

        if (!empty($toDate)) {
            $where[] = 'sp.payment_date <= ?';
            $params[] = $toDate;
        }

        $whereClause = implode(' AND ', $where);

        try {
            $stmt = $pdo->prepare("
                SELECT sp.*, s.name as supplier_name,
                    u1.full_name as prepared_by_name,
                    u2.full_name as approved_by_name
                FROM supplier_payments sp
                LEFT JOIN suppliers s ON s.id = sp.supplier_id
                LEFT JOIN users u1 ON u1.id = sp.prepared_by
                LEFT JOIN users u2 ON u2.id = sp.approved_by
                WHERE {$whereClause}
                ORDER BY sp.payment_date DESC, sp.id DESC
            ");
            $stmt->execute($params);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $payments = [];
        }

        // Get suppliers for filter dropdown
        try {
            $stmt = $pdo->query("SELECT id, name FROM suppliers WHERE is_active = 1 ORDER BY name");
            $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $suppliers = [];
        }

        return [
            'payments' => $payments,
            'suppliers' => $suppliers,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'supplier' => $supplier,
                'from_date' => $fromDate,
                'to_date' => $toDate
            ]
        ];
    }

    // ==================== SUPPLIERS API ====================

    public function getSuppliers()
    {
        if (!isAuthenticated()) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }

        try {
            $pdo = Database::getTenantConnection();
            $data = $this->getSuppliersData($pdo);
            return Response::json(['success' => true, 'data' => $data['suppliers']]);
        } catch (Exception $e) {
            return Response::json(['error' => $e->getMessage()], 500);
        }
    }

    public function getSupplier($id)
    {
        if (!isAuthenticated()) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }

        try {
            $pdo = Database::getTenantConnection();

            $stmt = $pdo->prepare("
                SELECT s.*, sc.name as category_name
                FROM suppliers s
                LEFT JOIN supplier_categories sc ON sc.id = s.category_id
                WHERE s.id = ?
            ");
            $stmt->execute([$id]);
            $supplier = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$supplier) {
                return Response::json(['error' => 'Supplier not found'], 404);
            }

            return Response::json(['success' => true, 'data' => $supplier]);
        } catch (Exception $e) {
            return Response::json(['error' => $e->getMessage()], 500);
        }
    }

    public function storeSupplier()
    {
        if (!isAuthenticated()) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }

        try {
            $pdo = Database::getTenantConnection();
            $userId = $_SESSION['user_id'] ?? null;

            // Generate supplier code
            $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING(supplier_code, 5) AS UNSIGNED)) as max_num FROM suppliers WHERE supplier_code LIKE 'SUP-%'");
            $maxNum = $stmt->fetchColumn() ?: 0;
            $supplierCode = 'SUP-' . str_pad($maxNum + 1, 4, '0', STR_PAD_LEFT);

            $stmt = $pdo->prepare("
                INSERT INTO suppliers (
                    supplier_code, name, category_id, contact_person,
                    email, phone, address, tax_pin, payment_terms,
                    bank_name, bank_branch, bank_account, credit_limit, is_active, notes, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $supplierCode,
                $_POST['name'] ?? '',
                !empty($_POST['category_id']) ? $_POST['category_id'] : null,
                $_POST['contact_person'] ?? null,
                $_POST['email'] ?? null,
                $_POST['phone'] ?? null,
                $_POST['address'] ?? null,
                $_POST['tax_pin'] ?? null,
                $_POST['payment_terms'] ?? 30,
                $_POST['bank_name'] ?? null,
                $_POST['bank_branch'] ?? null,
                $_POST['bank_account'] ?? null,
                $_POST['credit_limit'] ?? 0,
                isset($_POST['is_active']) ? 1 : 1,
                $_POST['notes'] ?? null,
                $userId
            ]);

            $supplierId = $pdo->lastInsertId();

            flash('success', 'Supplier created successfully');
            return Response::json(['success' => true, 'id' => $supplierId, 'code' => $supplierCode]);
        } catch (Exception $e) {
            error_log('Store supplier error: ' . $e->getMessage());
            return Response::json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateSupplier($id)
    {
        if (!isAuthenticated()) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }

        try {
            $pdo = Database::getTenantConnection();

            $stmt = $pdo->prepare("
                UPDATE suppliers SET
                    name = ?, category_id = ?, contact_person = ?, email = ?, phone = ?,
                    address = ?, tax_pin = ?, payment_terms = ?, bank_name = ?, bank_branch = ?,
                    bank_account = ?, credit_limit = ?, is_active = ?, notes = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $_POST['name'] ?? '',
                !empty($_POST['category_id']) ? $_POST['category_id'] : null,
                $_POST['contact_person'] ?? null,
                $_POST['email'] ?? null,
                $_POST['phone'] ?? null,
                $_POST['address'] ?? null,
                $_POST['tax_pin'] ?? null,
                $_POST['payment_terms'] ?? 30,
                $_POST['bank_name'] ?? null,
                $_POST['bank_branch'] ?? null,
                $_POST['bank_account'] ?? null,
                $_POST['credit_limit'] ?? 0,
                isset($_POST['is_active']) ? 1 : 0,
                $_POST['notes'] ?? null,
                $id
            ]);

            flash('success', 'Supplier updated successfully');
            return Response::json(['success' => true]);
        } catch (Exception $e) {
            error_log('Update supplier error: ' . $e->getMessage());
            return Response::json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteSupplier($id)
    {
        if (!isAuthenticated()) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }

        try {
            $pdo = Database::getTenantConnection();

            // Check if supplier has any transactions
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM purchase_orders WHERE supplier_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                return Response::json(['error' => 'Cannot delete supplier with existing transactions. Deactivate instead.'], 400);
            }

            $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ?");
            $stmt->execute([$id]);

            flash('success', 'Supplier deleted successfully');
            return Response::json(['success' => true]);
        } catch (Exception $e) {
            return Response::json(['error' => $e->getMessage()], 500);
        }
    }

    // ==================== PURCHASE ORDERS API ====================

    public function getPurchaseOrders()
    {
        // Implementation similar to getSuppliers
        return Response::json(['success' => true, 'data' => []]);
    }

    public function getPurchaseOrder($id)
    {
        // Get single PO with lines
        return Response::json(['success' => true, 'data' => null]);
    }

    public function storePurchaseOrder()
    {
        // Create PO with lines
        return Response::json(['success' => true]);
    }

    public function updatePurchaseOrder($id)
    {
        // Update PO
        return Response::json(['success' => true]);
    }

    public function approvePurchaseOrder($id)
    {
        // Approve PO
        return Response::json(['success' => true]);
    }

    public function cancelPurchaseOrder($id)
    {
        // Cancel PO
        return Response::json(['success' => true]);
    }

    // ==================== GRN API ====================

    public function getGRNs()
    {
        return Response::json(['success' => true, 'data' => []]);
    }

    public function getGRN($id)
    {
        return Response::json(['success' => true, 'data' => null]);
    }

    public function storeGRN()
    {
        return Response::json(['success' => true]);
    }

    public function confirmGRN($id)
    {
        return Response::json(['success' => true]);
    }

    // ==================== SUPPLIER INVOICES API ====================

    public function getSupplierInvoices()
    {
        return Response::json(['success' => true, 'data' => []]);
    }

    public function getSupplierInvoice($id)
    {
        return Response::json(['success' => true, 'data' => null]);
    }

    public function storeSupplierInvoice()
    {
        return Response::json(['success' => true]);
    }

    public function updateSupplierInvoice($id)
    {
        return Response::json(['success' => true]);
    }

    public function approveSupplierInvoice($id)
    {
        return Response::json(['success' => true]);
    }

    // ==================== SUPPLIER PAYMENTS API ====================

    public function getSupplierPayments()
    {
        return Response::json(['success' => true, 'data' => []]);
    }

    public function getSupplierPayment($id)
    {
        return Response::json(['success' => true, 'data' => null]);
    }

    public function storeSupplierPayment()
    {
        return Response::json(['success' => true]);
    }

    public function approveSupplierPayment($id)
    {
        return Response::json(['success' => true]);
    }
}
