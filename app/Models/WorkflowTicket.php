<?php
/**
 * WorkflowTicket Model
 *
 * Represents a running instance of a workflow.
 *
 * @author Claude Code Assistant
 * @date December 2025
 */

class WorkflowTicket
{
    private $pdo;
    private $data = [];

    public function __construct(?int $id = null)
    {
        $this->pdo = Database::getTenantConnection();

        if ($id) {
            $this->load($id);
        }
    }

    // =========================================================================
    // CRUD OPERATIONS
    // =========================================================================

    /**
     * Load ticket by ID
     */
    public function load(int $id): bool
    {
        $stmt = $this->pdo->prepare("SELECT * FROM workflow_tickets WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();

        if ($data) {
            $this->data = $data;
            return true;
        }
        return false;
    }

    /**
     * Load ticket by number
     */
    public function loadByNumber(string $ticketNumber): bool
    {
        $stmt = $this->pdo->prepare("SELECT * FROM workflow_tickets WHERE ticket_number = :num");
        $stmt->execute(['num' => $ticketNumber]);
        $data = $stmt->fetch();

        if ($data) {
            $this->data = $data;
            return true;
        }
        return false;
    }

    // =========================================================================
    // WORKFLOW INFO
    // =========================================================================

    /**
     * Get the workflow for this ticket
     */
    public function getWorkflow(): ?array
    {
        if (!$this->workflow_id) {
            return null;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM workflows WHERE id = :id");
        $stmt->execute(['id' => $this->workflow_id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get the entity associated with this ticket
     */
    public function getEntity(): ?array
    {
        if (!$this->entity_type || !$this->entity_id) {
            return null;
        }

        $tableMap = [
            'applicant' => 'applicants',
            'student' => 'students',
            'staff' => 'staff',
            'invoice' => 'invoices',
            'leave_request' => 'leave_requests'
        ];

        $table = $tableMap[$this->entity_type] ?? null;
        if (!$table) {
            return null;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM {$table} WHERE id = :id");
        $stmt->execute(['id' => $this->entity_id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get entity display name
     */
    public function getEntityDisplayName(): string
    {
        $entity = $this->getEntity();
        if (!$entity) {
            return "Unknown Entity #{$this->entity_id}";
        }

        switch ($this->entity_type) {
            case 'applicant':
                return trim(($entity['first_name'] ?? '') . ' ' . ($entity['last_name'] ?? ''));
            case 'student':
                return trim(($entity['first_name'] ?? '') . ' ' . ($entity['last_name'] ?? ''));
            case 'staff':
                return trim(($entity['first_name'] ?? '') . ' ' . ($entity['last_name'] ?? ''));
            default:
                return "{$this->entity_type} #{$this->entity_id}";
        }
    }

    // =========================================================================
    // TASKS
    // =========================================================================

    /**
     * Get all tasks for this ticket
     */
    public function getTasks(?string $status = null): array
    {
        if (!$this->id) {
            return [];
        }

        $sql = "
            SELECT t.*, s.code as step_code, s.name as step_name, s.step_type
            FROM workflow_tasks t
            JOIN workflow_steps s ON s.id = t.step_id
            WHERE t.ticket_id = :id
        ";
        $params = ['id' => $this->id];

        if ($status) {
            $sql .= " AND t.status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY t.created_at ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get active (pending/in-progress) tasks
     */
    public function getActiveTasks(): array
    {
        if (!$this->id) {
            return [];
        }

        $stmt = $this->pdo->prepare("
            SELECT t.*, s.code as step_code, s.name as step_name
            FROM workflow_tasks t
            JOIN workflow_steps s ON s.id = t.step_id
            WHERE t.ticket_id = :id AND t.status IN ('pending', 'claimed', 'in_progress')
            ORDER BY t.created_at ASC
        ");
        $stmt->execute(['id' => $this->id]);
        return $stmt->fetchAll();
    }

    /**
     * Get current step(s) - may be multiple if parallel execution
     */
    public function getCurrentSteps(): array
    {
        $activeTasks = $this->getActiveTasks();
        $stepIds = array_unique(array_column($activeTasks, 'step_id'));

        if (empty($stepIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($stepIds), '?'));
        $stmt = $this->pdo->prepare("SELECT * FROM workflow_steps WHERE id IN ({$placeholders})");
        $stmt->execute($stepIds);
        return $stmt->fetchAll();
    }

    // =========================================================================
    // HISTORY & COMMENTS
    // =========================================================================

    /**
     * Get ticket history (audit trail)
     */
    public function getHistory(): array
    {
        if (!$this->id) {
            return [];
        }

        $stmt = $this->pdo->prepare("
            SELECT h.*, s.code as step_code, s.name as step_name
            FROM workflow_history h
            LEFT JOIN workflow_steps s ON s.id = h.step_id
            WHERE h.ticket_id = :id
            ORDER BY h.created_at ASC
        ");
        $stmt->execute(['id' => $this->id]);
        return $stmt->fetchAll();
    }

    /**
     * Get comments
     */
    public function getComments(): array
    {
        if (!$this->id) {
            return [];
        }

        $stmt = $this->pdo->prepare("
            SELECT c.*, t.task_number
            FROM workflow_comments c
            LEFT JOIN workflow_tasks t ON t.id = c.task_id
            WHERE c.ticket_id = :id
            ORDER BY c.created_at ASC
        ");
        $stmt->execute(['id' => $this->id]);
        return $stmt->fetchAll();
    }

    /**
     * Add a comment
     */
    public function addComment(int $userId, string $comment, ?int $taskId = null, bool $isInternal = false): int
    {
        if (!$this->id) {
            throw new Exception("Ticket must be loaded to add comments");
        }

        // Get user name
        $stmt = $this->pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) FROM users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        $userName = $stmt->fetchColumn();

        $stmt = $this->pdo->prepare("
            INSERT INTO workflow_comments (
                ticket_id, task_id, comment, is_internal, user_id, user_name, created_at
            ) VALUES (
                :ticket_id, :task_id, :comment, :is_internal, :user_id, :user_name, NOW()
            )
        ");

        $stmt->execute([
            'ticket_id' => $this->id,
            'task_id' => $taskId,
            'comment' => $comment,
            'is_internal' => $isInternal ? 1 : 0,
            'user_id' => $userId,
            'user_name' => $userName
        ]);

        return $this->pdo->lastInsertId();
    }

    // =========================================================================
    // ATTACHMENTS
    // =========================================================================

    /**
     * Get attachments
     */
    public function getAttachments(): array
    {
        if (!$this->id) {
            return [];
        }

        $stmt = $this->pdo->prepare("
            SELECT a.*, t.task_number, u.first_name, u.last_name
            FROM workflow_attachments a
            LEFT JOIN workflow_tasks t ON t.id = a.task_id
            LEFT JOIN users u ON u.id = a.uploaded_by
            WHERE a.ticket_id = :id
            ORDER BY a.uploaded_at ASC
        ");
        $stmt->execute(['id' => $this->id]);
        return $stmt->fetchAll();
    }

    // =========================================================================
    // SUB-WORKFLOWS
    // =========================================================================

    /**
     * Get child sub-workflows
     */
    public function getSubWorkflows(): array
    {
        if (!$this->id) {
            return [];
        }

        $stmt = $this->pdo->prepare("
            SELECT st.*, tk.ticket_number as child_ticket_number, tk.status as child_status,
                   w.name as child_workflow_name, w.code as child_workflow_code
            FROM workflow_sub_tickets st
            JOIN workflow_tickets tk ON tk.id = st.child_ticket_id
            JOIN workflows w ON w.id = tk.workflow_id
            WHERE st.parent_ticket_id = :id
            ORDER BY st.created_at ASC
        ");
        $stmt->execute(['id' => $this->id]);
        return $stmt->fetchAll();
    }

    /**
     * Get parent workflow if this is a sub-workflow
     */
    public function getParentTicket(): ?array
    {
        if (!$this->id) {
            return null;
        }

        $stmt = $this->pdo->prepare("
            SELECT st.*, tk.ticket_number as parent_ticket_number, tk.status as parent_status,
                   w.name as parent_workflow_name
            FROM workflow_sub_tickets st
            JOIN workflow_tickets tk ON tk.id = st.parent_ticket_id
            JOIN workflows w ON w.id = tk.workflow_id
            WHERE st.child_ticket_id = :id
        ");
        $stmt->execute(['id' => $this->id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Check if this is a sub-workflow
     */
    public function isSubWorkflow(): bool
    {
        return $this->getParentTicket() !== null;
    }

    // =========================================================================
    // STATUS HELPERS
    // =========================================================================

    /**
     * Check if ticket is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if ticket is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if ticket is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if ticket is paused
     */
    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    /**
     * Get duration in hours since started
     */
    public function getDurationHours(): float
    {
        $start = strtotime($this->started_at);
        $end = $this->completed_at ? strtotime($this->completed_at) : time();
        return round(($end - $start) / 3600, 2);
    }

    /**
     * Get human-readable duration
     */
    public function getDurationFormatted(): string
    {
        $hours = $this->getDurationHours();

        if ($hours < 1) {
            return round($hours * 60) . ' minutes';
        } elseif ($hours < 24) {
            return round($hours, 1) . ' hours';
        } else {
            return round($hours / 24, 1) . ' days';
        }
    }

    // =========================================================================
    // PARALLEL STATE
    // =========================================================================

    /**
     * Get parallel execution state
     */
    public function getParallelState(): array
    {
        return json_decode($this->data['parallel_state'] ?? '{}', true);
    }

    /**
     * Update parallel state for a branch
     */
    public function updateBranchState(string $branchCode, string $state): bool
    {
        $parallelState = $this->getParallelState();
        $parallelState[$branchCode] = $state;

        $stmt = $this->pdo->prepare("
            UPDATE workflow_tickets SET parallel_state = :state, updated_at = NOW() WHERE id = :id
        ");
        $result = $stmt->execute(['id' => $this->id, 'state' => json_encode($parallelState)]);

        if ($result) {
            $this->data['parallel_state'] = json_encode($parallelState);
        }

        return $result;
    }

    // =========================================================================
    // TIMELINE VIEW
    // =========================================================================

    /**
     * Get a unified timeline of all events
     */
    public function getTimeline(): array
    {
        $timeline = [];

        // Add history events
        foreach ($this->getHistory() as $h) {
            $timeline[] = [
                'type' => 'history',
                'event_type' => $h['event_type'],
                'description' => $h['description'],
                'actor_name' => $h['actor_name'],
                'step_name' => $h['step_name'],
                'timestamp' => $h['created_at'],
                'data' => $h
            ];
        }

        // Add comments
        foreach ($this->getComments() as $c) {
            $timeline[] = [
                'type' => 'comment',
                'event_type' => 'comment',
                'description' => $c['comment'],
                'actor_name' => $c['user_name'],
                'step_name' => null,
                'timestamp' => $c['created_at'],
                'data' => $c
            ];
        }

        // Sort by timestamp
        usort($timeline, function ($a, $b) {
            return strtotime($a['timestamp']) - strtotime($b['timestamp']);
        });

        return $timeline;
    }

    // =========================================================================
    // STATIC METHODS
    // =========================================================================

    /**
     * Find tickets by entity
     */
    public static function findByEntity(string $entityType, int $entityId): array
    {
        $pdo = Database::getTenantConnection();
        $stmt = $pdo->prepare("
            SELECT tk.*, w.name as workflow_name, w.code as workflow_code
            FROM workflow_tickets tk
            JOIN workflows w ON w.id = tk.workflow_id
            WHERE tk.entity_type = :entity_type AND tk.entity_id = :entity_id
            ORDER BY tk.created_at DESC
        ");
        $stmt->execute(['entity_type' => $entityType, 'entity_id' => $entityId]);
        return $stmt->fetchAll();
    }

    /**
     * Find active tickets by entity
     */
    public static function findActiveByEntity(string $entityType, int $entityId): array
    {
        $pdo = Database::getTenantConnection();
        $stmt = $pdo->prepare("
            SELECT tk.*, w.name as workflow_name
            FROM workflow_tickets tk
            JOIN workflows w ON w.id = tk.workflow_id
            WHERE tk.entity_type = :entity_type AND tk.entity_id = :entity_id
              AND tk.status IN ('active', 'paused')
            ORDER BY tk.created_at DESC
        ");
        $stmt->execute(['entity_type' => $entityType, 'entity_id' => $entityId]);
        return $stmt->fetchAll();
    }

    /**
     * Find tickets by workflow
     */
    public static function findByWorkflow(int $workflowId, ?string $status = null): array
    {
        $pdo = Database::getTenantConnection();

        $sql = "SELECT * FROM workflow_tickets WHERE workflow_id = :workflow_id";
        $params = ['workflow_id' => $workflowId];

        if ($status) {
            $sql .= " AND status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get ticket statistics
     */
    public static function getStatistics(?int $workflowId = null): array
    {
        $pdo = Database::getTenantConnection();

        $where = "1=1";
        $params = [];

        if ($workflowId) {
            $where .= " AND workflow_id = :workflow_id";
            $params['workflow_id'] = $workflowId;
        }

        // Status counts
        $stmt = $pdo->prepare("
            SELECT status, COUNT(*) as count
            FROM workflow_tickets
            WHERE {$where}
            GROUP BY status
        ");
        $stmt->execute($params);

        $statusCounts = [];
        while ($row = $stmt->fetch()) {
            $statusCounts[$row['status']] = $row['count'];
        }

        // Average completion time
        $stmt = $pdo->prepare("
            SELECT AVG(TIMESTAMPDIFF(HOUR, started_at, completed_at)) as avg_hours
            FROM workflow_tickets
            WHERE {$where} AND status = 'completed' AND completed_at IS NOT NULL
        ");
        $stmt->execute($params);
        $avgHours = $stmt->fetchColumn() ?: 0;

        return [
            'status_counts' => $statusCounts,
            'total' => array_sum($statusCounts),
            'avg_completion_hours' => round($avgHours, 2)
        ];
    }

    // =========================================================================
    // MAGIC METHODS
    // =========================================================================

    public function __get($name)
    {
        return $this->data[$name] ?? null;
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
