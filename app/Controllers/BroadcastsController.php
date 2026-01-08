<?php

class BroadcastsController
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getTenantConnection();
    }

    /**
     * Display broadcasts list
     */
    public function index()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        $tenantId = $_SESSION['tenant_id'] ?? null;
        $userId = $_SESSION['user_id'] ?? null;

        // Get filters from request
        $status = Request::get('status', 'all');
        $source = Request::get('source', 'all');
        $dateFrom = Request::get('date_from');
        $dateTo = Request::get('date_to');

        // Build WHERE clause
        $whereConditions = ['b.school_id = :school_id'];
        $params = ['school_id' => $tenantId];

        if ($status !== 'all') {
            $whereConditions[] = 'b.status = :status';
            $params['status'] = $status;
        }

        if ($source !== 'all') {
            $whereConditions[] = 'b.source_type = :source';
            $params['source'] = $source;
        }

        if ($dateFrom) {
            $whereConditions[] = 'DATE(b.created_at) >= :date_from';
            $params['date_from'] = $dateFrom;
        }

        if ($dateTo) {
            $whereConditions[] = 'DATE(b.created_at) <= :date_to';
            $params['date_to'] = $dateTo;
        }

        $whereClause = implode(' AND ', $whereConditions);

        // Fetch broadcasts with statistics
        $stmt = $this->pdo->prepare("
            SELECT b.*,
                   u.full_name as created_by_name,
                   approver.full_name as approved_by_name,
                   mt.name as template_name,
                   COALESCE(
                       (SELECT title FROM events WHERE id = b.source_id AND b.source_type = 'event'),
                       CONCAT('Invoice #', b.source_id)
                   ) as source_title
            FROM broadcasts b
            LEFT JOIN users u ON b.created_by = u.id
            LEFT JOIN users approver ON b.approved_by = approver.id
            LEFT JOIN message_templates mt ON b.template_id = mt.id
            WHERE {$whereClause}
            ORDER BY b.created_at DESC
        ");
        $stmt->execute($params);
        $broadcasts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get summary statistics
        $statsStmt = $this->pdo->prepare("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
                SUM(CASE WHEN status = 'pending_approval' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
            FROM broadcasts
            WHERE school_id = :school_id
        ");
        $statsStmt->execute(['school_id' => $tenantId]);
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

        // Get credit balances
        $creditBalances = $this->getCreditBalances($tenantId);

        // Prepare view data
        $data = [
            'broadcasts' => $broadcasts,
            'stats' => $stats,
            'creditBalances' => $creditBalances,
            'filters' => [
                'status' => $status,
                'source' => $source,
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ]
        ];

        Response::view('communication/broadcasts', $data);
    }

    /**
     * Show create broadcast wizard (Step 1)
     */
    public function create()
    {
        if (!isAuthenticated()) {
            if (Request::isAjax()) {
                Response::json(['success' => false, 'message' => 'Not authenticated']);
                return;
            }
            Response::redirect('/login');
        }

        $tenantId = $_SESSION['tenant_id'] ?? null;

        // Get data for wizard
        $data = [
            'classes' => $this->getClasses($tenantId),
            'departments' => $this->getDepartments($tenantId),
            'events' => $this->getUpcomingEvents($tenantId),
            'templates' => $this->getTemplates($tenantId),
            'creditBalances' => $this->getCreditBalances($tenantId)
        ];

        if (Request::isAjax()) {
            Response::json(['success' => true, 'data' => $data]);
            return;
        }

        Response::view('communication/broadcasts_create', $data);
    }

    /**
     * Store new broadcast (saves draft or submits for approval)
     */
    public function store()
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
            $required = ['audience_type', 'channels', 'message_body'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    Response::json(['success' => false, 'message' => "Field {$field} is required"]);
                    return;
                }
            }

            // Decode JSON fields
            $channels = json_decode($data['channels'], true);
            $audienceFilters = !empty($data['audience_filters']) ? json_decode($data['audience_filters'], true) : null;

            // Calculate estimated credits
            $smsCredits = 0;
            $whatsappCredits = 0;
            $emailCredits = 0;

            if (in_array('sms', $channels)) {
                $smsLength = strlen($data['message_body']);
                $smsCredits = ceil($smsLength / 160);
            }
            if (in_array('whatsapp', $channels)) {
                $whatsappCredits = 1;
            }
            if (in_array('email', $channels)) {
                $emailCredits = 1;
            }

            $this->pdo->beginTransaction();

            // Insert broadcast
            $stmt = $this->pdo->prepare("
                INSERT INTO broadcasts (
                    school_id, source_type, source_id, audience_type, audience_filters,
                    channels, subject, message_body, template_id, status,
                    send_immediately, scheduled_send_at,
                    estimated_sms_credits, estimated_whatsapp_credits, estimated_email_credits,
                    total_estimated_credits, created_by
                ) VALUES (
                    :school_id, :source_type, :source_id, :audience_type, :audience_filters,
                    :channels, :subject, :message_body, :template_id, :status,
                    :send_immediately, :scheduled_send_at,
                    :estimated_sms_credits, :estimated_whatsapp_credits, :estimated_email_credits,
                    :total_estimated_credits, :created_by
                )
            ");

            $status = !empty($data['submit_for_approval']) ? 'pending_approval' : 'draft';
            $sendImmediately = !empty($data['send_immediately']) ? 1 : 0;
            $scheduledSendAt = !empty($data['scheduled_send_at']) ? $data['scheduled_send_at'] : null;

            $stmt->execute([
                'school_id' => $tenantId,
                'source_type' => $data['source_type'] ?? 'general',
                'source_id' => $data['source_id'] ?? null,
                'audience_type' => $data['audience_type'],
                'audience_filters' => json_encode($audienceFilters),
                'channels' => json_encode($channels),
                'subject' => $data['subject'] ?? null,
                'message_body' => $data['message_body'],
                'template_id' => $data['template_id'] ?? null,
                'status' => $status,
                'send_immediately' => $sendImmediately,
                'scheduled_send_at' => $scheduledSendAt,
                'estimated_sms_credits' => $smsCredits,
                'estimated_whatsapp_credits' => $whatsappCredits,
                'estimated_email_credits' => $emailCredits,
                'total_estimated_credits' => $smsCredits + $whatsappCredits + $emailCredits,
                'created_by' => $userId
            ]);

            $broadcastId = $this->pdo->lastInsertId();

            $this->pdo->commit();

            Response::json([
                'success' => true,
                'message' => $status === 'draft' ? 'Broadcast saved as draft' : 'Broadcast submitted for approval',
                'broadcastId' => $broadcastId
            ]);

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            Response::json(['success' => false, 'message' => 'Failed to save broadcast: ' . $e->getMessage()]);
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
     * Get classes for audience selection
     */
    private function getClasses($tenantId)
    {
        $stmt = $this->pdo->prepare("
            SELECT id, name FROM classes WHERE school_id = :school_id ORDER BY name
        ");
        $stmt->execute(['school_id' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get departments for staff audience
     */
    private function getDepartments($tenantId)
    {
        // For now, return empty array - departments can be added later
        return [];
    }

    /**
     * Get upcoming events for source selection
     */
    private function getUpcomingEvents($tenantId)
    {
        $stmt = $this->pdo->prepare("
            SELECT id, title, start_date, start_time
            FROM events
            WHERE school_id = :school_id
            AND start_date >= CURDATE()
            AND status IN ('draft', 'published')
            ORDER BY start_date ASC
            LIMIT 20
        ");
        $stmt->execute(['school_id' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get message templates
     */
    private function getTemplates($tenantId)
    {
        $stmt = $this->pdo->prepare("
            SELECT id, template_code, template_name as name, description, category,
                   channels, subject, sms_body, whatsapp_body, email_body,
                   variables, is_system, is_active
            FROM communication_templates
            WHERE (campus_id IS NULL OR campus_id = :campus_id)
            AND is_active = 1
            ORDER BY is_system DESC, template_name ASC
        ");
        $stmt->execute(['campus_id' => $tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
