<?php
/**
 * WorkflowTask Model
 *
 * Represents an individual task within a workflow ticket.
 *
 * @author Claude Code Assistant
 * @date December 2025
 */

class WorkflowTask
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
     * Load task by ID
     */
    public function load(int $id): bool
    {
        $stmt = $this->pdo->prepare("SELECT * FROM workflow_tasks WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();

        if ($data) {
            $this->data = $data;
            return true;
        }
        return false;
    }

    /**
     * Load task by number
     */
    public function loadByNumber(string $taskNumber): bool
    {
        $stmt = $this->pdo->prepare("SELECT * FROM workflow_tasks WHERE task_number = :num");
        $stmt->execute(['num' => $taskNumber]);
        $data = $stmt->fetch();

        if ($data) {
            $this->data = $data;
            return true;
        }
        return false;
    }

    // =========================================================================
    // RELATED DATA
    // =========================================================================

    /**
     * Get the ticket for this task
     */
    public function getTicket(): ?array
    {
        if (!$this->ticket_id) {
            return null;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM workflow_tickets WHERE id = :id");
        $stmt->execute(['id' => $this->ticket_id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get the step for this task
     */
    public function getStep(): ?array
    {
        if (!$this->step_id) {
            return null;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM workflow_steps WHERE id = :id");
        $stmt->execute(['id' => $this->step_id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get available actions for this task
     */
    public function getAvailableActions(): array
    {
        $step = $this->getStep();
        if (!$step) {
            return [];
        }

        return json_decode($step['available_actions'] ?? '[]', true);
    }

    /**
     * Get form fields for this task
     */
    public function getFormFields(): array
    {
        if (!$this->step_id) {
            return [];
        }

        $stmt = $this->pdo->prepare("
            SELECT * FROM workflow_step_fields
            WHERE step_id = :step_id
            ORDER BY sort_order
        ");
        $stmt->execute(['step_id' => $this->step_id]);
        return $stmt->fetchAll();
    }

    /**
     * Get form data submitted for this task
     */
    public function getFormData(): array
    {
        if (!$this->id) {
            return [];
        }

        $stmt = $this->pdo->prepare("
            SELECT fd.*, f.field_code, f.field_label, f.field_type
            FROM workflow_task_form_data fd
            JOIN workflow_step_fields f ON f.id = fd.field_id
            WHERE fd.task_id = :task_id
        ");
        $stmt->execute(['task_id' => $this->id]);

        $data = [];
        while ($row = $stmt->fetch()) {
            $data[$row['field_code']] = [
                'value' => $row['field_value'],
                'label' => $row['field_label'],
                'type' => $row['field_type']
            ];
        }
        return $data;
    }

    /**
     * Get action data as array
     */
    public function getActionData(): array
    {
        return json_decode($this->data['action_data'] ?? '{}', true);
    }

    // =========================================================================
    // USER INFO
    // =========================================================================

    /**
     * Get assigned user info
     */
    public function getAssignedUser(): ?array
    {
        if (!$this->assigned_user_id) {
            return null;
        }

        $stmt = $this->pdo->prepare("SELECT id, first_name, last_name, email FROM users WHERE id = :id");
        $stmt->execute(['id' => $this->assigned_user_id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get claimed by user info
     */
    public function getClaimedByUser(): ?array
    {
        if (!$this->claimed_by_user_id) {
            return null;
        }

        $stmt = $this->pdo->prepare("SELECT id, first_name, last_name, email FROM users WHERE id = :id");
        $stmt->execute(['id' => $this->claimed_by_user_id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get acted by user info
     */
    public function getActedByUser(): ?array
    {
        if (!$this->acted_by_user_id) {
            return null;
        }

        $stmt = $this->pdo->prepare("SELECT id, first_name, last_name, email FROM users WHERE id = :id");
        $stmt->execute(['id' => $this->acted_by_user_id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get users who can act on this task (by role)
     */
    public function getEligibleUsers(): array
    {
        if (!$this->assigned_role) {
            return [];
        }

        $stmt = $this->pdo->prepare("
            SELECT u.id, u.first_name, u.last_name, u.email
            FROM users u
            JOIN user_roles ur ON ur.user_id = u.id
            JOIN roles r ON r.id = ur.role_id
            WHERE r.name = :role AND u.is_active = 1
        ");
        $stmt->execute(['role' => $this->assigned_role]);
        return $stmt->fetchAll();
    }

    // =========================================================================
    // STATUS HELPERS
    // =========================================================================

    /**
     * Check if task can be acted upon
     */
    public function canAct(): bool
    {
        return in_array($this->status, ['pending', 'claimed', 'in_progress']);
    }

    /**
     * Check if task is pending (unclaimed)
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if task is claimed
     */
    public function isClaimed(): bool
    {
        return $this->status === 'claimed';
    }

    /**
     * Check if task is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if task is overdue
     */
    public function isOverdue(): bool
    {
        if (!$this->due_at) {
            return false;
        }
        return strtotime($this->due_at) < time() && $this->canAct();
    }

    /**
     * Check if task is escalated
     */
    public function isEscalated(): bool
    {
        return !empty($this->escalated_at);
    }

    /**
     * Get time until due (negative if overdue)
     */
    public function getTimeUntilDue(): ?int
    {
        if (!$this->due_at) {
            return null;
        }
        return strtotime($this->due_at) - time();
    }

    /**
     * Get time until due formatted
     */
    public function getTimeUntilDueFormatted(): ?string
    {
        $seconds = $this->getTimeUntilDue();
        if ($seconds === null) {
            return null;
        }

        $absSeconds = abs($seconds);
        $hours = floor($absSeconds / 3600);
        $days = floor($hours / 24);

        if ($days > 0) {
            $str = $days . ' day' . ($days > 1 ? 's' : '');
        } elseif ($hours > 0) {
            $str = $hours . ' hour' . ($hours > 1 ? 's' : '');
        } else {
            $minutes = floor($absSeconds / 60);
            $str = $minutes . ' minute' . ($minutes > 1 ? 's' : '');
        }

        return $seconds < 0 ? "{$str} overdue" : "{$str} remaining";
    }

    // =========================================================================
    // ATTACHMENTS
    // =========================================================================

    /**
     * Get attachments for this task
     */
    public function getAttachments(): array
    {
        if (!$this->id) {
            return [];
        }

        $stmt = $this->pdo->prepare("
            SELECT a.*, u.first_name, u.last_name
            FROM workflow_attachments a
            LEFT JOIN users u ON u.id = a.uploaded_by
            WHERE a.task_id = :task_id
            ORDER BY a.uploaded_at ASC
        ");
        $stmt->execute(['task_id' => $this->id]);
        return $stmt->fetchAll();
    }

    // =========================================================================
    // HISTORY
    // =========================================================================

    /**
     * Get history entries for this task
     */
    public function getHistory(): array
    {
        if (!$this->id) {
            return [];
        }

        $stmt = $this->pdo->prepare("
            SELECT * FROM workflow_history
            WHERE task_id = :task_id
            ORDER BY created_at ASC
        ");
        $stmt->execute(['task_id' => $this->id]);
        return $stmt->fetchAll();
    }

    // =========================================================================
    // STATIC METHODS
    // =========================================================================

    /**
     * Find tasks for a user (by role or direct assignment)
     */
    public static function findForUser(int $userId, ?string $status = null): array
    {
        $pdo = Database::getTenantConnection();

        // Get user's roles
        $stmt = $pdo->prepare("
            SELECT r.name FROM roles r
            JOIN user_roles ur ON ur.role_id = r.id
            WHERE ur.user_id = :user_id
        ");
        $stmt->execute(['user_id' => $userId]);
        $roles = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        if (empty($roles)) {
            $roles = ['__NO_ROLE__']; // Placeholder to avoid SQL error
        }

        $rolePlaceholders = implode(',', array_fill(0, count($roles), '?'));

        $sql = "
            SELECT t.*, s.name as step_name, s.code as step_code,
                   w.name as workflow_name, w.code as workflow_code,
                   tk.ticket_number, tk.entity_type, tk.entity_id, tk.priority
            FROM workflow_tasks t
            JOIN workflow_steps s ON s.id = t.step_id
            JOIN workflow_tickets tk ON tk.id = t.ticket_id
            JOIN workflows w ON w.id = tk.workflow_id
            WHERE tk.status = 'active'
              AND (t.assigned_user_id = ?
                   OR t.claimed_by_user_id = ?
                   OR (t.assigned_role IN ({$rolePlaceholders}) AND t.claimed_by_user_id IS NULL))
        ";

        $params = [$userId, $userId, ...$roles];

        if ($status) {
            $sql .= " AND t.status = ?";
            $params[] = $status;
        } else {
            $sql .= " AND t.status IN ('pending', 'claimed', 'in_progress')";
        }

        $sql .= " ORDER BY t.is_overdue DESC, tk.priority DESC, t.due_at ASC, t.created_at ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Find tasks by role
     */
    public static function findByRole(string $role, ?string $status = null): array
    {
        $pdo = Database::getTenantConnection();

        $sql = "
            SELECT t.*, s.name as step_name, w.name as workflow_name,
                   tk.ticket_number, tk.entity_type, tk.entity_id
            FROM workflow_tasks t
            JOIN workflow_steps s ON s.id = t.step_id
            JOIN workflow_tickets tk ON tk.id = t.ticket_id
            JOIN workflows w ON w.id = tk.workflow_id
            WHERE t.assigned_role = :role AND tk.status = 'active'
        ";
        $params = ['role' => $role];

        if ($status) {
            $sql .= " AND t.status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY t.due_at ASC, t.created_at ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Find overdue tasks
     */
    public static function findOverdue(): array
    {
        $pdo = Database::getTenantConnection();

        $stmt = $pdo->query("
            SELECT t.*, s.name as step_name, w.name as workflow_name,
                   tk.ticket_number, tk.entity_type, tk.entity_id
            FROM workflow_tasks t
            JOIN workflow_steps s ON s.id = t.step_id
            JOIN workflow_tickets tk ON tk.id = t.ticket_id
            JOIN workflows w ON w.id = tk.workflow_id
            WHERE t.status IN ('pending', 'claimed', 'in_progress')
              AND t.due_at IS NOT NULL
              AND t.due_at < NOW()
              AND tk.status = 'active'
            ORDER BY t.due_at ASC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Get task count by status for a user
     */
    public static function getCountsForUser(int $userId): array
    {
        $pdo = Database::getTenantConnection();

        // Get user's roles
        $stmt = $pdo->prepare("
            SELECT r.name FROM roles r
            JOIN user_roles ur ON ur.role_id = r.id
            WHERE ur.user_id = :user_id
        ");
        $stmt->execute(['user_id' => $userId]);
        $roles = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        if (empty($roles)) {
            return ['pending' => 0, 'claimed' => 0, 'overdue' => 0];
        }

        $rolePlaceholders = implode(',', array_fill(0, count($roles), '?'));

        $sql = "
            SELECT
                SUM(CASE WHEN t.status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN t.status = 'claimed' AND t.claimed_by_user_id = ? THEN 1 ELSE 0 END) as claimed,
                SUM(CASE WHEN t.due_at < NOW() AND t.status IN ('pending', 'claimed') THEN 1 ELSE 0 END) as overdue
            FROM workflow_tasks t
            JOIN workflow_tickets tk ON tk.id = t.ticket_id
            WHERE tk.status = 'active'
              AND (t.assigned_user_id = ?
                   OR t.claimed_by_user_id = ?
                   OR (t.assigned_role IN ({$rolePlaceholders}) AND t.claimed_by_user_id IS NULL))
              AND t.status IN ('pending', 'claimed', 'in_progress')
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $userId, $userId, ...$roles]);
        $result = $stmt->fetch();

        return [
            'pending' => (int)($result['pending'] ?? 0),
            'claimed' => (int)($result['claimed'] ?? 0),
            'overdue' => (int)($result['overdue'] ?? 0)
        ];
    }

    /**
     * Get task statistics
     */
    public static function getStatistics(?int $workflowId = null): array
    {
        $pdo = Database::getTenantConnection();

        $where = "1=1";
        $params = [];

        if ($workflowId) {
            $where .= " AND tk.workflow_id = :workflow_id";
            $params['workflow_id'] = $workflowId;
        }

        // Status counts
        $stmt = $pdo->prepare("
            SELECT t.status, COUNT(*) as count
            FROM workflow_tasks t
            JOIN workflow_tickets tk ON tk.id = t.ticket_id
            WHERE {$where}
            GROUP BY t.status
        ");
        $stmt->execute($params);

        $statusCounts = [];
        while ($row = $stmt->fetch()) {
            $statusCounts[$row['status']] = $row['count'];
        }

        // Average completion time
        $stmt = $pdo->prepare("
            SELECT AVG(TIMESTAMPDIFF(HOUR, t.created_at, t.acted_at)) as avg_hours
            FROM workflow_tasks t
            JOIN workflow_tickets tk ON tk.id = t.ticket_id
            WHERE {$where} AND t.status = 'completed' AND t.acted_at IS NOT NULL
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
