<?php

class CreditsController
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getTenantConnection();
    }

    /**
     * Display credits overview and purchase history
     */
    public function index()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        $tenantId = $_SESSION['tenant_id'] ?? null;

        // Get current credit balances
        $balances = $this->getCreditBalances($tenantId);

        // Get recent credit transactions
        $transactions = $this->getRecentTransactions($tenantId);

        // Get monthly usage statistics
        $monthlyUsage = $this->getMonthlyUsage($tenantId);

        // Get credit pricing (could come from settings table)
        $pricing = $this->getCreditPricing();

        $data = [
            'balances' => $balances,
            'transactions' => $transactions,
            'monthlyUsage' => $monthlyUsage,
            'pricing' => $pricing
        ];

        Response::view('communication/credits', $data);
    }

    /**
     * Purchase credits (create purchase order)
     */
    public function purchase()
    {
        if (!isAuthenticated()) {
            Response::json(['success' => false, 'message' => 'Not authenticated']);
            return;
        }

        $tenantId = $_SESSION['tenant_id'] ?? null;
        $userId = $_SESSION['user_id'] ?? null;

        try {
            $data = Request::all();

            // Validate required fields
            if (empty($data['credit_type']) || empty($data['quantity'])) {
                Response::json(['success' => false, 'message' => 'Credit type and quantity are required']);
                return;
            }

            $creditType = $data['credit_type'];
            $quantity = (int)$data['quantity'];
            $paymentMethod = $data['payment_method'] ?? 'manual';
            $reference = $data['reference'] ?? 'PO-' . time();

            // Get pricing
            $pricing = $this->getCreditPricing();
            $unitPrice = $pricing[$creditType]['price'] ?? 0;
            $totalAmount = $quantity * $unitPrice;

            $this->pdo->beginTransaction();

            // Get current balance
            $balances = $this->getCreditBalances($tenantId);
            $currentBalance = $balances["{$creditType}_credits"] ?? 0;

            // Record purchase transaction
            $stmt = $this->pdo->prepare("
                INSERT INTO communication_credits (
                    school_id, credit_type, transaction_type, amount,
                    balance_before, balance_after,
                    purchase_reference, purchase_amount, payment_method,
                    description, created_by
                ) VALUES (
                    :school_id, :credit_type, 'purchase', :amount,
                    :balance_before, :balance_after,
                    :reference, :purchase_amount, :payment_method,
                    :description, :created_by
                )
            ");

            $stmt->execute([
                'school_id' => $tenantId,
                'credit_type' => $creditType,
                'amount' => $quantity,
                'balance_before' => $currentBalance,
                'balance_after' => $currentBalance + $quantity,
                'reference' => $reference,
                'purchase_amount' => $totalAmount,
                'payment_method' => $paymentMethod,
                'description' => "Purchased {$quantity} {$creditType} credits",
                'created_by' => $userId
            ]);

            // Update credit balances
            $this->updateCreditBalance($tenantId, $creditType, $quantity);

            $this->pdo->commit();

            Response::json([
                'success' => true,
                'message' => "Successfully purchased {$quantity} {$creditType} credits",
                'newBalance' => $currentBalance + $quantity
            ]);

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            Response::json(['success' => false, 'message' => 'Failed to purchase credits: ' . $e->getMessage()]);
        }
    }

    /**
     * Get credit balances for school
     */
    private function getCreditBalances($tenantId)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM credit_balances WHERE school_id = :school_id
        ");
        $stmt->execute(['school_id' => $tenantId]);
        $balances = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$balances) {
            // Initialize credit balances if not exists
            $stmt = $this->pdo->prepare("
                INSERT INTO credit_balances (school_id, sms_credits, whatsapp_credits, email_credits)
                VALUES (:school_id, 0, 0, 0)
            ");
            $stmt->execute(['school_id' => $tenantId]);

            return [
                'sms_credits' => 0,
                'whatsapp_credits' => 0,
                'email_credits' => 0
            ];
        }

        return $balances;
    }

    /**
     * Get recent credit transactions
     */
    private function getRecentTransactions($tenantId, $limit = 50)
    {
        $stmt = $this->pdo->prepare("
            SELECT cc.*,
                   u.full_name as created_by_name,
                   b.id as broadcast_id,
                   COALESCE(
                       (SELECT title FROM events WHERE id = b.source_id AND b.source_type = 'event'),
                       'General Broadcast'
                   ) as broadcast_title
            FROM communication_credits cc
            LEFT JOIN users u ON cc.created_by = u.id
            LEFT JOIN broadcasts b ON cc.broadcast_id = b.id
            WHERE cc.school_id = :school_id
            ORDER BY cc.created_at DESC
            LIMIT :limit
        ");
        $stmt->execute(['school_id' => $tenantId, 'limit' => $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get monthly usage statistics
     */
    private function getMonthlyUsage($tenantId)
    {
        $stmt = $this->pdo->prepare("
            SELECT
                credit_type,
                MONTH(created_at) as month,
                YEAR(created_at) as year,
                SUM(CASE WHEN transaction_type = 'usage' THEN ABS(amount) ELSE 0 END) as total_used,
                SUM(CASE WHEN transaction_type = 'purchase' THEN amount ELSE 0 END) as total_purchased
            FROM communication_credits
            WHERE school_id = :school_id
            AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY credit_type, YEAR(created_at), MONTH(created_at)
            ORDER BY year DESC, month DESC, credit_type
        ");
        $stmt->execute(['school_id' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update credit balance
     */
    private function updateCreditBalance($tenantId, $creditType, $amount)
    {
        $columnName = "{$creditType}_credits";

        $stmt = $this->pdo->prepare("
            INSERT INTO credit_balances (school_id, {$columnName})
            VALUES (:school_id, :amount)
            ON DUPLICATE KEY UPDATE {$columnName} = {$columnName} + :amount
        ");

        $stmt->execute([
            'school_id' => $tenantId,
            'amount' => $amount
        ]);
    }

    /**
     * Get credit pricing
     * TODO: Move this to a settings table
     */
    private function getCreditPricing()
    {
        return [
            'sms' => [
                'price' => 1.00,
                'currency' => 'KES',
                'unit' => 'per message (160 chars)'
            ],
            'whatsapp' => [
                'price' => 2.00,
                'currency' => 'KES',
                'unit' => 'per message'
            ],
            'email' => [
                'price' => 0.50,
                'currency' => 'KES',
                'unit' => 'per email'
            ]
        ];
    }
}
