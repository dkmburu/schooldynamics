<?php
/**
 * Budgets Controller
 * Handles budget management with tabbed interface
 * Tabs: Budget Setup, Approval Queue, Budget vs Actual, Reports
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Services/WorkflowEngine.php';

class BudgetsController
{
    private $db;
    private $schoolId;

    public function __construct()
    {
        $this->db = Database::getTenantConnection();
        $this->schoolId = $_SESSION['school_id'] ?? $_SESSION['tenant_id'] ?? 1;
    }

    /**
     * Main budgets page with tabs
     */
    public function index($tab = 'setup')
    {
        $validTabs = ['setup', 'approvals', 'tracking', 'reports'];
        $activeTab = in_array($tab, $validTabs) ? $tab : 'setup';

        $stats = $this->getBudgetStats();
        $periods = $this->getBudgetPeriods();
        $currentPeriod = $this->getCurrentPeriod();
        $accounts = $this->getExpenseAccounts();
        $costCenters = $this->getCostCenters();

        // Get tab-specific data
        $tabData = [];
        switch ($activeTab) {
            case 'setup':
                $tabData = $this->getBudgetsData($currentPeriod['id'] ?? null);
                break;
            case 'approvals':
                $tabData = $this->getPendingApprovals();
                break;
            case 'tracking':
                $tabData = $this->getBudgetVsActual($currentPeriod['id'] ?? null);
                break;
            case 'reports':
                $tabData = $this->getReportsData($currentPeriod['id'] ?? null);
                break;
        }

        $pageTitle = "Budget Management";
        $contentView = __DIR__ . '/../Views/finance/_budgets_content.php';

        require __DIR__ . '/../Views/finance/budgets.php';
    }

    /**
     * Get budget statistics
     */
    private function getBudgetStats()
    {
        $currentPeriod = $this->getCurrentPeriod();
        $periodId = $currentPeriod['id'] ?? 0;
        $currentMonth = date('Y-m-01');

        // Total annual budget
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(annual_amount), 0) as total_budget
            FROM budgets
            WHERE budget_period_id = ? AND status = 'approved'
        ");
        $stmt->execute([$periodId]);
        $totalBudget = $stmt->fetch()['total_budget'];

        // Total spent (from budget_transactions)
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(bt.amount), 0) as total_spent
            FROM budget_transactions bt
            JOIN budgets b ON bt.budget_id = b.id
            WHERE b.budget_period_id = ? AND bt.transaction_type = 'actual'
        ");
        $stmt->execute([$periodId]);
        $totalSpent = $stmt->fetch()['total_spent'];

        // Total committed (from POs not yet invoiced)
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(bt.amount), 0) as total_committed
            FROM budget_transactions bt
            JOIN budgets b ON bt.budget_id = b.id
            WHERE b.budget_period_id = ? AND bt.transaction_type = 'commitment'
        ");
        $stmt->execute([$periodId]);
        $totalCommitted = $stmt->fetch()['total_committed'];

        // Pending approvals count
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as pending_count
            FROM budgets WHERE status = 'pending_approval'
        ");
        $stmt->execute();
        $pendingBudgets = $stmt->fetch()['pending_count'];

        $stmt = $this->db->prepare("
            SELECT COUNT(*) as pending_count
            FROM budget_overruns WHERE status = 'pending'
        ");
        $stmt->execute();
        $pendingOverruns = $stmt->fetch()['pending_count'];

        // Budgets with overruns this month
        $stmt = $this->db->prepare("
            SELECT COUNT(DISTINCT bl.budget_id) as overrun_count
            FROM budget_lines bl
            WHERE bl.month_year = ? AND bl.available_amount < 0
        ");
        $stmt->execute([$currentMonth]);
        $budgetsOverrun = $stmt->fetch()['overrun_count'];

        return [
            'total_budget' => $totalBudget,
            'total_spent' => $totalSpent,
            'total_committed' => $totalCommitted,
            'total_available' => $totalBudget - $totalSpent - $totalCommitted,
            'pending_approvals' => $pendingBudgets + $pendingOverruns,
            'budgets_overrun' => $budgetsOverrun,
            'utilization_rate' => $totalBudget > 0 ? (($totalSpent + $totalCommitted) / $totalBudget) * 100 : 0
        ];
    }

    /**
     * Get all budget periods
     */
    private function getBudgetPeriods()
    {
        $stmt = $this->db->query("
            SELECT id, name, start_date, end_date, status, is_current
            FROM budget_periods
            ORDER BY start_date DESC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Get current budget period
     */
    private function getCurrentPeriod()
    {
        $stmt = $this->db->query("
            SELECT id, name, start_date, end_date, status
            FROM budget_periods
            WHERE is_current = TRUE
            LIMIT 1
        ");
        return $stmt->fetch() ?: [];
    }

    /**
     * Get expense accounts from chart of accounts
     */
    private function getExpenseAccounts()
    {
        $stmt = $this->db->query("
            SELECT id, account_code, account_name, parent_account_id
            FROM chart_of_accounts
            WHERE account_type IN ('expense', 'cost_of_sales')
            AND is_active = 1
            ORDER BY account_code
        ");
        return $stmt->fetchAll();
    }

    /**
     * Get cost centers
     */
    private function getCostCenters()
    {
        $stmt = $this->db->query("
            SELECT id, code, name
            FROM cost_centers
            WHERE is_active = TRUE
            ORDER BY code
        ");
        return $stmt->fetchAll();
    }

    /**
     * Get budgets for setup tab
     */
    private function getBudgetsData($periodId)
    {
        if (!$periodId) return [];

        $stmt = $this->db->prepare("
            SELECT b.*,
                   coa.account_code, coa.account_name,
                   cc.code as cost_center_code, cc.name as cost_center_name,
                   u.full_name as created_by_name,
                   ua.full_name as approved_by_name,
                   COALESCE(SUM(bl.spent_amount), 0) as total_spent,
                   COALESCE(SUM(bl.committed_amount), 0) as total_committed
            FROM budgets b
            JOIN chart_of_accounts coa ON b.account_id = coa.id
            LEFT JOIN cost_centers cc ON b.cost_center_id = cc.id
            LEFT JOIN users u ON b.created_by = u.id
            LEFT JOIN users ua ON b.approved_by = ua.id
            LEFT JOIN budget_lines bl ON b.id = bl.budget_id
            WHERE b.budget_period_id = ?
            GROUP BY b.id
            ORDER BY coa.account_code, cc.code
        ");
        $stmt->execute([$periodId]);
        return $stmt->fetchAll();
    }

    /**
     * Get budget lines (monthly allocations)
     */
    public function getBudgetLines($budgetId)
    {
        $stmt = $this->db->prepare("
            SELECT id, month_year, allocated_amount, spent_amount,
                   committed_amount, available_amount, notes
            FROM budget_lines
            WHERE budget_id = ?
            ORDER BY month_year
        ");
        $stmt->execute([$budgetId]);
        return $stmt->fetchAll();
    }

    /**
     * Get pending approvals for approval queue tab
     */
    private function getPendingApprovals()
    {
        // Pending budget approvals - with approver from workflow (using subquery for approver)
        $stmt = $this->db->prepare("
            SELECT 'budget' as type, b.id, b.budget_code, b.name, b.annual_amount as amount,
                   coa.account_name, u.full_name as requested_by, b.created_at as requested_at,
                   b.notes,
                   (
                       SELECT COALESCE(approver.full_name, wt.assigned_role, 'Pending Assignment')
                       FROM workflow_tickets wkt
                       LEFT JOIN workflow_tasks wt ON wt.ticket_id = wkt.id AND wt.status IN ('pending', 'claimed')
                       LEFT JOIN users approver ON approver.id = COALESCE(wt.claimed_by_user_id, wt.assigned_user_id)
                       WHERE wkt.entity_type = 'budget' AND wkt.entity_id = b.id AND wkt.status = 'active'
                       LIMIT 1
                   ) as approver_name,
                   (
                       SELECT wt.assigned_role
                       FROM workflow_tickets wkt
                       LEFT JOIN workflow_tasks wt ON wt.ticket_id = wkt.id AND wt.status IN ('pending', 'claimed')
                       WHERE wkt.entity_type = 'budget' AND wkt.entity_id = b.id AND wkt.status = 'active'
                       LIMIT 1
                   ) as approver_role
            FROM budgets b
            JOIN chart_of_accounts coa ON b.account_id = coa.id
            JOIN users u ON b.created_by = u.id
            WHERE b.status = 'pending_approval'
            ORDER BY b.created_at
        ");
        $stmt->execute();
        $pendingBudgets = $stmt->fetchAll();

        // Pending budget revisions - with approver from workflow (using subquery for approver)
        $stmt = $this->db->prepare("
            SELECT 'revision' as type, br.id, b.budget_code,
                   CONCAT('Revision #', br.revision_number, ' - ', b.name) as name,
                   (br.new_annual_amount - br.previous_annual_amount) as amount,
                   coa.account_name, u.full_name as requested_by, br.created_at as requested_at,
                   br.reason as notes,
                   (
                       SELECT COALESCE(approver.full_name, wt.assigned_role, 'Pending Assignment')
                       FROM workflow_tickets wkt
                       LEFT JOIN workflow_tasks wt ON wt.ticket_id = wkt.id AND wt.status IN ('pending', 'claimed')
                       LEFT JOIN users approver ON approver.id = COALESCE(wt.claimed_by_user_id, wt.assigned_user_id)
                       WHERE wkt.entity_type = 'budget_revision' AND wkt.entity_id = br.id AND wkt.status = 'active'
                       LIMIT 1
                   ) as approver_name,
                   (
                       SELECT wt.assigned_role
                       FROM workflow_tickets wkt
                       LEFT JOIN workflow_tasks wt ON wt.ticket_id = wkt.id AND wt.status IN ('pending', 'claimed')
                       WHERE wkt.entity_type = 'budget_revision' AND wkt.entity_id = br.id AND wkt.status = 'active'
                       LIMIT 1
                   ) as approver_role
            FROM budget_revisions br
            JOIN budgets b ON br.budget_id = b.id
            JOIN chart_of_accounts coa ON b.account_id = coa.id
            JOIN users u ON br.requested_by = u.id
            WHERE br.status = 'pending'
            ORDER BY br.created_at
        ");
        $stmt->execute();
        $pendingRevisions = $stmt->fetchAll();

        // Pending overrun approvals - with approver from workflow (using subquery for approver)
        $stmt = $this->db->prepare("
            SELECT 'overrun' as type, bo.id, b.budget_code,
                   CONCAT('Overrun - ', bo.entity_type, ' #', bo.entity_id) as name,
                   bo.overrun_amount as amount,
                   coa.account_name, u.full_name as requested_by, bo.created_at as requested_at,
                   bo.reason as notes,
                   (
                       SELECT COALESCE(approver.full_name, wt.assigned_role, 'Pending Assignment')
                       FROM workflow_tickets wkt
                       LEFT JOIN workflow_tasks wt ON wt.ticket_id = wkt.id AND wt.status IN ('pending', 'claimed')
                       LEFT JOIN users approver ON approver.id = COALESCE(wt.claimed_by_user_id, wt.assigned_user_id)
                       WHERE wkt.entity_type = 'budget_overrun' AND wkt.entity_id = bo.id AND wkt.status = 'active'
                       LIMIT 1
                   ) as approver_name,
                   (
                       SELECT wt.assigned_role
                       FROM workflow_tickets wkt
                       LEFT JOIN workflow_tasks wt ON wt.ticket_id = wkt.id AND wt.status IN ('pending', 'claimed')
                       WHERE wkt.entity_type = 'budget_overrun' AND wkt.entity_id = bo.id AND wkt.status = 'active'
                       LIMIT 1
                   ) as approver_role
            FROM budget_overruns bo
            JOIN budgets b ON bo.budget_id = b.id
            JOIN chart_of_accounts coa ON b.account_id = coa.id
            JOIN users u ON bo.requested_by = u.id
            WHERE bo.status = 'pending'
            ORDER BY bo.created_at
        ");
        $stmt->execute();
        $pendingOverruns = $stmt->fetchAll();

        return [
            'budgets' => $pendingBudgets,
            'revisions' => $pendingRevisions,
            'overruns' => $pendingOverruns
        ];
    }

    /**
     * Get budget vs actual data for tracking tab
     */
    private function getBudgetVsActual($periodId)
    {
        if (!$periodId) return [];

        $currentMonth = date('Y-m-01');

        $stmt = $this->db->prepare("
            SELECT b.id, b.budget_code, b.name, b.annual_amount,
                   coa.account_code, coa.account_name,
                   cc.name as cost_center_name,
                   bl.month_year, bl.allocated_amount, bl.spent_amount,
                   bl.committed_amount, bl.available_amount,
                   CASE
                       WHEN bl.allocated_amount > 0
                       THEN ((bl.spent_amount + bl.committed_amount) / bl.allocated_amount) * 100
                       ELSE 0
                   END as utilization_pct
            FROM budgets b
            JOIN chart_of_accounts coa ON b.account_id = coa.id
            LEFT JOIN cost_centers cc ON b.cost_center_id = cc.id
            LEFT JOIN budget_lines bl ON b.id = bl.budget_id AND bl.month_year = ?
            WHERE b.budget_period_id = ? AND b.status = 'approved'
            ORDER BY coa.account_code
        ");
        $stmt->execute([$currentMonth, $periodId]);
        return $stmt->fetchAll();
    }

    /**
     * Get reports data
     */
    private function getReportsData($periodId)
    {
        return [
            'period_id' => $periodId
        ];
    }

    // =========================================
    // API METHODS
    // =========================================

    /**
     * Create new budget
     */
    public function storeBudget()
    {
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents('php://input'), true);

            // Validate required fields
            $required = ['budget_period_id', 'name', 'account_id', 'annual_amount'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field '{$field}' is required");
                }
            }

            // Check for duplicate budget (same account + cost center + period)
            $stmt = $this->db->prepare("
                SELECT id, budget_code, name FROM budgets
                WHERE budget_period_id = ?
                AND account_id = ?
                AND (cost_center_id = ? OR (cost_center_id IS NULL AND ? IS NULL))
            ");
            $stmt->execute([
                $data['budget_period_id'],
                $data['account_id'],
                $data['cost_center_id'] ?? null,
                $data['cost_center_id'] ?? null
            ]);
            $existing = $stmt->fetch();

            if ($existing) {
                throw new Exception("A budget already exists for this account and cost center: {$existing['budget_code']} - {$existing['name']}");
            }

            // Generate budget code
            $budgetCode = $this->generateBudgetCode($data['account_id'], $data['cost_center_id'] ?? null);

            $this->db->beginTransaction();

            // Insert budget
            $stmt = $this->db->prepare("
                INSERT INTO budgets (budget_period_id, budget_code, name, account_id, cost_center_id,
                                    annual_amount, notes, status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'draft', ?)
            ");
            $stmt->execute([
                $data['budget_period_id'],
                $budgetCode,
                $data['name'],
                $data['account_id'],
                $data['cost_center_id'] ?? null,
                $data['annual_amount'],
                $data['notes'] ?? null,
                $_SESSION['user_id']
            ]);

            $budgetId = $this->db->lastInsertId();

            // Create monthly allocations
            $this->createMonthlyAllocations($budgetId, $data);

            $this->db->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Budget created successfully',
                'budget_id' => $budgetId,
                'budget_code' => $budgetCode
            ]);

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Generate budget code
     */
    private function generateBudgetCode($accountId, $costCenterId)
    {
        // Get account code
        $stmt = $this->db->prepare("SELECT account_code FROM chart_of_accounts WHERE id = ?");
        $stmt->execute([$accountId]);
        $account = $stmt->fetch();
        $accountCode = $account['account_code'] ?? 'XXX';

        // Get cost center code
        $ccCode = '';
        if ($costCenterId) {
            $stmt = $this->db->prepare("SELECT code FROM cost_centers WHERE id = ?");
            $stmt->execute([$costCenterId]);
            $cc = $stmt->fetch();
            $ccCode = '-' . ($cc['code'] ?? '');
        }

        // Get sequence
        $stmt = $this->db->query("SELECT COUNT(*) + 1 as seq FROM budgets");
        $seq = str_pad($stmt->fetch()['seq'], 4, '0', STR_PAD_LEFT);

        return "BUD-{$accountCode}{$ccCode}-{$seq}";
    }

    /**
     * Create monthly allocations for a budget
     */
    private function createMonthlyAllocations($budgetId, $data)
    {
        // Get budget period dates
        $stmt = $this->db->prepare("
            SELECT start_date, end_date FROM budget_periods WHERE id = ?
        ");
        $stmt->execute([$data['budget_period_id']]);
        $period = $stmt->fetch();

        $startDate = new DateTime($period['start_date']);
        $endDate = new DateTime($period['end_date']);
        $annualAmount = $data['annual_amount'];

        // Check for custom monthly amounts
        $monthlyAmounts = $data['monthly_amounts'] ?? [];

        $insertStmt = $this->db->prepare("
            INSERT INTO budget_lines (budget_id, month_year, allocated_amount, notes)
            VALUES (?, ?, ?, ?)
        ");

        $current = clone $startDate;
        $monthIndex = 0;

        while ($current <= $endDate) {
            $monthYear = $current->format('Y-m-01');

            // Use custom amount if provided, otherwise distribute evenly
            if (!empty($monthlyAmounts[$monthIndex])) {
                $amount = $monthlyAmounts[$monthIndex];
            } else {
                // Default: distribute evenly across months
                $totalMonths = $startDate->diff($endDate)->m + ($startDate->diff($endDate)->y * 12) + 1;
                $amount = round($annualAmount / $totalMonths, 2);
            }

            $insertStmt->execute([
                $budgetId,
                $monthYear,
                $amount,
                $data['monthly_notes'][$monthIndex] ?? null
            ]);

            $current->modify('+1 month');
            $monthIndex++;
        }
    }

    /**
     * Update budget
     */
    public function updateBudget($id)
    {
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents('php://input'), true);

            // Check if budget exists and is editable
            $stmt = $this->db->prepare("SELECT status FROM budgets WHERE id = ?");
            $stmt->execute([$id]);
            $budget = $stmt->fetch();

            if (!$budget) {
                throw new Exception('Budget not found');
            }

            if ($budget['status'] === 'approved') {
                throw new Exception('Cannot edit approved budget. Please create a revision.');
            }

            $stmt = $this->db->prepare("
                UPDATE budgets SET
                    name = ?,
                    account_id = ?,
                    cost_center_id = ?,
                    annual_amount = ?,
                    notes = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $data['name'],
                $data['account_id'],
                $data['cost_center_id'] ?? null,
                $data['annual_amount'],
                $data['notes'] ?? null,
                $id
            ]);

            echo json_encode(['success' => true, 'message' => 'Budget updated successfully']);

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Update monthly allocations
     */
    public function updateAllocations($budgetId)
    {
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents('php://input'), true);

            $this->db->beginTransaction();

            $updateStmt = $this->db->prepare("
                UPDATE budget_lines SET allocated_amount = ?, notes = ? WHERE id = ?
            ");

            foreach ($data['allocations'] as $alloc) {
                $updateStmt->execute([
                    $alloc['amount'],
                    $alloc['notes'] ?? null,
                    $alloc['id']
                ]);
            }

            // Update annual total
            $stmt = $this->db->prepare("
                UPDATE budgets SET
                    annual_amount = (SELECT SUM(allocated_amount) FROM budget_lines WHERE budget_id = ?),
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$budgetId, $budgetId]);

            $this->db->commit();

            echo json_encode(['success' => true, 'message' => 'Allocations updated successfully']);

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Submit budget for approval
     */
    public function submitForApproval($id)
    {
        header('Content-Type: application/json');

        try {
            // Get budget details
            $stmt = $this->db->prepare("
                SELECT b.*, coa.account_name
                FROM budgets b
                JOIN chart_of_accounts coa ON b.account_id = coa.id
                WHERE b.id = ?
            ");
            $stmt->execute([$id]);
            $budget = $stmt->fetch();

            if (!$budget) {
                throw new Exception('Budget not found');
            }

            if ($budget['status'] !== 'draft') {
                throw new Exception('Only draft budgets can be submitted for approval');
            }

            $this->db->beginTransaction();

            // Start workflow
            $workflow = new WorkflowEngine($this->db);
            $ticketId = $workflow->startWorkflow(
                'budget_approval',
                "Budget Approval: {$budget['name']}",
                $_SESSION['user_id'],
                'budget',
                $id,
                [
                    'budget_code' => $budget['budget_code'],
                    'account_name' => $budget['account_name'],
                    'annual_amount' => $budget['annual_amount']
                ]
            );

            // Update budget status
            $stmt = $this->db->prepare("
                UPDATE budgets SET
                    status = 'pending_approval',
                    workflow_ticket_id = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$ticketId, $id]);

            $this->db->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Budget submitted for approval',
                'ticket_id' => $ticketId
            ]);

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Approve budget
     */
    public function approveBudget($id)
    {
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents('php://input'), true);

            $stmt = $this->db->prepare("SELECT * FROM budgets WHERE id = ?");
            $stmt->execute([$id]);
            $budget = $stmt->fetch();

            if (!$budget) {
                throw new Exception('Budget not found');
            }

            $this->db->beginTransaction();

            // Complete workflow task if exists
            if ($budget['workflow_ticket_id']) {
                $workflow = new WorkflowEngine($this->db);
                // Get the current task for this ticket
                $stmt = $this->db->prepare("
                    SELECT id FROM workflow_tasks
                    WHERE ticket_id = ? AND status = 'pending'
                    ORDER BY created_at LIMIT 1
                ");
                $stmt->execute([$budget['workflow_ticket_id']]);
                $task = $stmt->fetch();

                if ($task) {
                    $workflow->completeTask($task['id'], $_SESSION['user_id'], 'approved', $data['comments'] ?? '');
                }
            }

            // Update budget status
            $stmt = $this->db->prepare("
                UPDATE budgets SET
                    status = 'approved',
                    approved_by = ?,
                    approved_at = NOW(),
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $id]);

            $this->db->commit();

            echo json_encode(['success' => true, 'message' => 'Budget approved successfully']);

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Reject budget
     */
    public function rejectBudget($id)
    {
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['reason'])) {
                throw new Exception('Rejection reason is required');
            }

            $stmt = $this->db->prepare("SELECT * FROM budgets WHERE id = ?");
            $stmt->execute([$id]);
            $budget = $stmt->fetch();

            if (!$budget) {
                throw new Exception('Budget not found');
            }

            $this->db->beginTransaction();

            // Complete workflow task if exists
            if ($budget['workflow_ticket_id']) {
                $workflow = new WorkflowEngine($this->db);
                $stmt = $this->db->prepare("
                    SELECT id FROM workflow_tasks
                    WHERE ticket_id = ? AND status = 'pending'
                    ORDER BY created_at LIMIT 1
                ");
                $stmt->execute([$budget['workflow_ticket_id']]);
                $task = $stmt->fetch();

                if ($task) {
                    $workflow->completeTask($task['id'], $_SESSION['user_id'], 'rejected', $data['reason']);
                }
            }

            // Update budget status
            $stmt = $this->db->prepare("
                UPDATE budgets SET
                    status = 'rejected',
                    notes = CONCAT(IFNULL(notes, ''), '\n[Rejected: ', ?, ']'),
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$data['reason'], $id]);

            $this->db->commit();

            echo json_encode(['success' => true, 'message' => 'Budget rejected']);

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Delete budget (draft only)
     */
    public function deleteBudget($id)
    {
        header('Content-Type: application/json');

        try {
            $stmt = $this->db->prepare("SELECT status FROM budgets WHERE id = ?");
            $stmt->execute([$id]);
            $budget = $stmt->fetch();

            if (!$budget) {
                throw new Exception('Budget not found');
            }

            if ($budget['status'] !== 'draft') {
                throw new Exception('Only draft budgets can be deleted');
            }

            $stmt = $this->db->prepare("DELETE FROM budgets WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode(['success' => true, 'message' => 'Budget deleted successfully']);

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Approve budget overrun
     */
    public function approveOverrun($id)
    {
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents('php://input'), true);

            $stmt = $this->db->prepare("SELECT * FROM budget_overruns WHERE id = ?");
            $stmt->execute([$id]);
            $overrun = $stmt->fetch();

            if (!$overrun) {
                throw new Exception('Overrun request not found');
            }

            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                UPDATE budget_overruns SET
                    status = 'approved',
                    approved_by = ?,
                    approved_at = NOW(),
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $id]);

            $this->db->commit();

            echo json_encode(['success' => true, 'message' => 'Overrun approved successfully']);

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Reject budget overrun
     */
    public function rejectOverrun($id)
    {
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['reason'])) {
                throw new Exception('Rejection reason is required');
            }

            $stmt = $this->db->prepare("
                UPDATE budget_overruns SET
                    status = 'rejected',
                    rejection_reason = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$data['reason'], $id]);

            echo json_encode(['success' => true, 'message' => 'Overrun rejected']);

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Check budget availability for a transaction
     * Returns warning if over budget (soft control)
     */
    public function checkBudgetAvailability()
    {
        header('Content-Type: application/json');

        try {
            $accountId = $_GET['account_id'] ?? null;
            $costCenterId = $_GET['cost_center_id'] ?? null;
            $amount = $_GET['amount'] ?? 0;
            $month = $_GET['month'] ?? date('Y-m-01');

            if (!$accountId || !$amount) {
                throw new Exception('Account ID and amount are required');
            }

            // Find matching budget
            $stmt = $this->db->prepare("
                SELECT b.id, b.budget_code, b.name, bl.allocated_amount, bl.spent_amount,
                       bl.committed_amount, bl.available_amount
                FROM budgets b
                JOIN budget_periods bp ON b.budget_period_id = bp.id
                JOIN budget_lines bl ON b.id = bl.budget_id
                WHERE b.account_id = ?
                AND (b.cost_center_id = ? OR (b.cost_center_id IS NULL AND ? IS NULL))
                AND bp.is_current = TRUE
                AND bl.month_year = ?
                AND b.status = 'approved'
                LIMIT 1
            ");
            $stmt->execute([$accountId, $costCenterId, $costCenterId, $month]);
            $budget = $stmt->fetch();

            if (!$budget) {
                echo json_encode([
                    'success' => true,
                    'has_budget' => false,
                    'message' => 'No approved budget found for this account/cost center'
                ]);
                return;
            }

            $available = $budget['available_amount'];
            $isOverBudget = $amount > $available;
            $overrunAmount = $isOverBudget ? $amount - $available : 0;

            echo json_encode([
                'success' => true,
                'has_budget' => true,
                'budget_id' => $budget['id'],
                'budget_code' => $budget['budget_code'],
                'budget_name' => $budget['name'],
                'allocated' => $budget['allocated_amount'],
                'spent' => $budget['spent_amount'],
                'committed' => $budget['committed_amount'],
                'available' => $available,
                'requested_amount' => $amount,
                'is_over_budget' => $isOverBudget,
                'overrun_amount' => $overrunAmount,
                'warning' => $isOverBudget
                    ? "This transaction exceeds available budget by KES " . number_format($overrunAmount, 2) . ". Overrun approval will be required."
                    : null
            ]);

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Request budget overrun approval
     */
    public function requestOverrunApproval()
    {
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents('php://input'), true);

            $required = ['budget_id', 'entity_type', 'entity_id', 'requested_amount', 'reason'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field '{$field}' is required");
                }
            }

            // Get budget info
            $stmt = $this->db->prepare("
                SELECT b.id, bl.available_amount, bl.id as budget_line_id
                FROM budgets b
                JOIN budget_lines bl ON b.id = bl.budget_id
                WHERE b.id = ? AND bl.month_year = ?
            ");
            $stmt->execute([$data['budget_id'], $data['month'] ?? date('Y-m-01')]);
            $budget = $stmt->fetch();

            if (!$budget) {
                throw new Exception('Budget not found');
            }

            $overrunAmount = $data['requested_amount'] - $budget['available_amount'];

            $this->db->beginTransaction();

            // Create overrun request
            $stmt = $this->db->prepare("
                INSERT INTO budget_overruns
                (budget_id, budget_line_id, entity_type, entity_id, overrun_amount,
                 budget_available, requested_amount, reason, requested_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['budget_id'],
                $budget['budget_line_id'],
                $data['entity_type'],
                $data['entity_id'],
                $overrunAmount,
                $budget['available_amount'],
                $data['requested_amount'],
                $data['reason'],
                $_SESSION['user_id']
            ]);

            $overrunId = $this->db->lastInsertId();

            // Start workflow for overrun approval
            $workflow = new WorkflowEngine($this->db);
            $ticketId = $workflow->startWorkflow(
                'budget_overrun_approval',
                "Budget Overrun: {$data['entity_type']} #{$data['entity_id']}",
                $_SESSION['user_id'],
                'budget_overrun',
                $overrunId,
                [
                    'overrun_amount' => $overrunAmount,
                    'requested_amount' => $data['requested_amount']
                ]
            );

            // Update overrun with ticket ID
            $stmt = $this->db->prepare("
                UPDATE budget_overruns SET workflow_ticket_id = ? WHERE id = ?
            ");
            $stmt->execute([$ticketId, $overrunId]);

            $this->db->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Overrun approval request submitted',
                'overrun_id' => $overrunId
            ]);

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Get single budget with details
     */
    public function getBudget($id)
    {
        header('Content-Type: application/json');

        try {
            $stmt = $this->db->prepare("
                SELECT b.*,
                       coa.account_code, coa.account_name,
                       cc.code as cost_center_code, cc.name as cost_center_name,
                       bp.name as period_name
                FROM budgets b
                JOIN chart_of_accounts coa ON b.account_id = coa.id
                LEFT JOIN cost_centers cc ON b.cost_center_id = cc.id
                JOIN budget_periods bp ON b.budget_period_id = bp.id
                WHERE b.id = ?
            ");
            $stmt->execute([$id]);
            $budget = $stmt->fetch();

            if (!$budget) {
                throw new Exception('Budget not found');
            }

            // Get monthly allocations
            $budget['lines'] = $this->getBudgetLines($id);

            echo json_encode(['success' => true, 'data' => $budget]);

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Replicate budget allocations from template or another budget
     */
    public function replicateAllocations($budgetId)
    {
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents('php://input'), true);

            $sourceType = $data['source_type'] ?? 'even'; // even, template, budget
            $sourceId = $data['source_id'] ?? null;
            $annualAmount = $data['annual_amount'] ?? 0;
            $adjustments = $data['adjustments'] ?? []; // Month-specific adjustments

            // Get budget period for this budget
            $stmt = $this->db->prepare("
                SELECT bp.start_date, bp.end_date
                FROM budgets b
                JOIN budget_periods bp ON b.budget_period_id = bp.id
                WHERE b.id = ?
            ");
            $stmt->execute([$budgetId]);
            $period = $stmt->fetch();

            if (!$period) {
                throw new Exception('Budget not found');
            }

            $allocations = [];
            $startDate = new DateTime($period['start_date']);
            $endDate = new DateTime($period['end_date']);

            if ($sourceType === 'even') {
                // Distribute evenly
                $totalMonths = $startDate->diff($endDate)->m + ($startDate->diff($endDate)->y * 12) + 1;
                $monthlyAmount = round($annualAmount / $totalMonths, 2);

                $current = clone $startDate;
                while ($current <= $endDate) {
                    $monthKey = $current->format('Y-m');
                    $amount = $adjustments[$monthKey] ?? $monthlyAmount;
                    $allocations[] = [
                        'month_year' => $current->format('Y-m-01'),
                        'amount' => $amount
                    ];
                    $current->modify('+1 month');
                }
            } elseif ($sourceType === 'template' && $sourceId) {
                // Use template percentages
                $stmt = $this->db->prepare("SELECT monthly_amounts FROM budget_templates WHERE id = ?");
                $stmt->execute([$sourceId]);
                $template = $stmt->fetch();

                if ($template && $template['monthly_amounts']) {
                    $percentages = json_decode($template['monthly_amounts'], true);
                    $current = clone $startDate;
                    $monthIndex = 0;

                    while ($current <= $endDate) {
                        $pct = $percentages[$monthIndex] ?? (100 / 12);
                        $monthKey = $current->format('Y-m');
                        $amount = $adjustments[$monthKey] ?? round($annualAmount * ($pct / 100), 2);
                        $allocations[] = [
                            'month_year' => $current->format('Y-m-01'),
                            'amount' => $amount
                        ];
                        $current->modify('+1 month');
                        $monthIndex++;
                    }
                }
            } elseif ($sourceType === 'budget' && $sourceId) {
                // Copy from another budget's allocations
                $stmt = $this->db->prepare("
                    SELECT month_year, allocated_amount FROM budget_lines WHERE budget_id = ? ORDER BY month_year
                ");
                $stmt->execute([$sourceId]);
                $sourceLines = $stmt->fetchAll();

                foreach ($sourceLines as $line) {
                    $monthKey = date('Y-m', strtotime($line['month_year']));
                    $amount = $adjustments[$monthKey] ?? $line['allocated_amount'];
                    $allocations[] = [
                        'month_year' => $line['month_year'],
                        'amount' => $amount
                    ];
                }
            }

            // Update budget lines
            $this->db->beginTransaction();

            $updateStmt = $this->db->prepare("
                UPDATE budget_lines SET allocated_amount = ? WHERE budget_id = ? AND month_year = ?
            ");

            foreach ($allocations as $alloc) {
                $updateStmt->execute([$alloc['amount'], $budgetId, $alloc['month_year']]);
            }

            // Recalculate annual amount
            $stmt = $this->db->prepare("
                UPDATE budgets SET
                    annual_amount = (SELECT SUM(allocated_amount) FROM budget_lines WHERE budget_id = ?),
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$budgetId, $budgetId]);

            $this->db->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Allocations replicated successfully',
                'allocations' => $allocations
            ]);

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Create budget period
     */
    public function storePeriod()
    {
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents('php://input'), true);

            $stmt = $this->db->prepare("
                INSERT INTO budget_periods (name, start_date, end_date, status, is_current, created_by)
                VALUES (?, ?, ?, 'draft', ?, ?)
            ");
            $stmt->execute([
                $data['name'],
                $data['start_date'],
                $data['end_date'],
                $data['is_current'] ?? false,
                $_SESSION['user_id']
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Budget period created',
                'id' => $this->db->lastInsertId()
            ]);

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
