<?php
/**
 * WorkflowStep Model
 *
 * Represents a step within a workflow.
 *
 * @author Claude Code Assistant
 * @date December 2025
 */

class WorkflowStep
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
     * Load step by ID
     */
    public function load(int $id): bool
    {
        $stmt = $this->pdo->prepare("SELECT * FROM workflow_steps WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();

        if ($data) {
            $this->data = $data;
            return true;
        }
        return false;
    }

    /**
     * Create a new step
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO workflow_steps (
                workflow_id, code, name, description, step_type,
                actor_roles, assignment_mode, auto_assign_config,
                available_actions, decision_config, parallel_config,
                auto_config, sub_workflow_config,
                require_comment, comment_label,
                sla_hours, escalation_enabled, escalation_hours, escalation_role,
                reminder_enabled, reminder_hours,
                auto_transition_enabled, auto_transition_hours, auto_transition_action,
                auto_transition_next_step, auto_transition_comment,
                sort_order, ui_position_x, ui_position_y, color, icon,
                is_active, created_at
            ) VALUES (
                :workflow_id, :code, :name, :description, :step_type,
                :actor_roles, :assignment_mode, :auto_assign_config,
                :available_actions, :decision_config, :parallel_config,
                :auto_config, :sub_workflow_config,
                :require_comment, :comment_label,
                :sla_hours, :escalation_enabled, :escalation_hours, :escalation_role,
                :reminder_enabled, :reminder_hours,
                :auto_transition_enabled, :auto_transition_hours, :auto_transition_action,
                :auto_transition_next_step, :auto_transition_comment,
                :sort_order, :ui_position_x, :ui_position_y, :color, :icon,
                :is_active, NOW()
            )
        ");

        $stmt->execute([
            'workflow_id' => $data['workflow_id'],
            'code' => $data['code'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'step_type' => $data['step_type'] ?? 'task',
            'actor_roles' => $this->jsonEncode($data['actor_roles'] ?? null),
            'assignment_mode' => $data['assignment_mode'] ?? 'any',
            'auto_assign_config' => $this->jsonEncode($data['auto_assign_config'] ?? null),
            'available_actions' => $this->jsonEncode($data['available_actions'] ?? null),
            'decision_config' => $this->jsonEncode($data['decision_config'] ?? null),
            'parallel_config' => $this->jsonEncode($data['parallel_config'] ?? null),
            'auto_config' => $this->jsonEncode($data['auto_config'] ?? null),
            'sub_workflow_config' => $this->jsonEncode($data['sub_workflow_config'] ?? null),
            'require_comment' => $data['require_comment'] ?? 0,
            'comment_label' => $data['comment_label'] ?? 'Comments',
            'sla_hours' => $data['sla_hours'] ?? null,
            'escalation_enabled' => $data['escalation_enabled'] ?? 0,
            'escalation_hours' => $data['escalation_hours'] ?? null,
            'escalation_role' => $data['escalation_role'] ?? null,
            'reminder_enabled' => $data['reminder_enabled'] ?? 0,
            'reminder_hours' => $data['reminder_hours'] ?? null,
            'auto_transition_enabled' => $data['auto_transition_enabled'] ?? 0,
            'auto_transition_hours' => $data['auto_transition_hours'] ?? null,
            'auto_transition_action' => $data['auto_transition_action'] ?? null,
            'auto_transition_next_step' => $data['auto_transition_next_step'] ?? null,
            'auto_transition_comment' => $data['auto_transition_comment'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'ui_position_x' => $data['ui_position_x'] ?? null,
            'ui_position_y' => $data['ui_position_y'] ?? null,
            'color' => $data['color'] ?? null,
            'icon' => $data['icon'] ?? null,
            'is_active' => $data['is_active'] ?? 1
        ]);

        $id = $this->pdo->lastInsertId();
        $this->load($id);
        return $id;
    }

    /**
     * Update step
     */
    public function update(array $data): bool
    {
        if (!$this->id) {
            return false;
        }

        $jsonFields = [
            'actor_roles', 'auto_assign_config', 'available_actions',
            'decision_config', 'parallel_config', 'auto_config', 'sub_workflow_config'
        ];

        $allowedFields = [
            'name', 'description', 'step_type', 'actor_roles', 'assignment_mode',
            'auto_assign_config', 'available_actions', 'decision_config',
            'parallel_config', 'auto_config', 'sub_workflow_config',
            'require_comment', 'comment_label', 'sla_hours',
            'escalation_enabled', 'escalation_hours', 'escalation_role',
            'reminder_enabled', 'reminder_hours',
            'auto_transition_enabled', 'auto_transition_hours', 'auto_transition_action',
            'auto_transition_next_step', 'auto_transition_comment',
            'sort_order', 'ui_position_x', 'ui_position_y', 'color', 'icon', 'is_active'
        ];

        $fields = [];
        $params = ['id' => $this->id];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $value = $data[$field];
                if (in_array($field, $jsonFields)) {
                    $value = $this->jsonEncode($value);
                }
                $fields[] = "{$field} = :{$field}";
                $params[$field] = $value;
            }
        }

        if (empty($fields)) {
            return true;
        }

        $fields[] = "updated_at = NOW()";

        $sql = "UPDATE workflow_steps SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute($params);

        if ($result) {
            $this->load($this->id);
        }

        return $result;
    }

    /**
     * Delete step
     */
    public function delete(): bool
    {
        if (!$this->id) {
            return false;
        }

        // Check for active tasks
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM workflow_tasks t
            JOIN workflow_tickets tk ON tk.id = t.ticket_id
            WHERE t.step_id = :id AND tk.status = 'active'
        ");
        $stmt->execute(['id' => $this->id]);
        $activeCount = $stmt->fetchColumn();

        if ($activeCount > 0) {
            throw new Exception("Cannot delete step with {$activeCount} active tasks");
        }

        // Soft delete
        $stmt = $this->pdo->prepare("UPDATE workflow_steps SET is_active = 0, updated_at = NOW() WHERE id = :id");
        return $stmt->execute(['id' => $this->id]);
    }

    // =========================================================================
    // FORM FIELDS MANAGEMENT
    // =========================================================================

    /**
     * Get all form fields for this step
     */
    public function getFields(): array
    {
        if (!$this->id) {
            return [];
        }

        $stmt = $this->pdo->prepare("
            SELECT * FROM workflow_step_fields
            WHERE step_id = :id
            ORDER BY sort_order
        ");
        $stmt->execute(['id' => $this->id]);
        return $stmt->fetchAll();
    }

    /**
     * Add a form field to this step
     */
    public function addField(array $fieldData): int
    {
        if (!$this->id) {
            throw new Exception("Step must be saved before adding fields");
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO workflow_step_fields (
                step_id, field_code, field_label, field_type,
                is_required, placeholder, help_text, default_value,
                min_value, max_value, min_length, max_length, pattern,
                options, file_config, show_condition, sort_order, column_width,
                created_at
            ) VALUES (
                :step_id, :field_code, :field_label, :field_type,
                :is_required, :placeholder, :help_text, :default_value,
                :min_value, :max_value, :min_length, :max_length, :pattern,
                :options, :file_config, :show_condition, :sort_order, :column_width,
                NOW()
            )
        ");

        $stmt->execute([
            'step_id' => $this->id,
            'field_code' => $fieldData['field_code'],
            'field_label' => $fieldData['field_label'],
            'field_type' => $fieldData['field_type'],
            'is_required' => $fieldData['is_required'] ?? 0,
            'placeholder' => $fieldData['placeholder'] ?? null,
            'help_text' => $fieldData['help_text'] ?? null,
            'default_value' => $fieldData['default_value'] ?? null,
            'min_value' => $fieldData['min_value'] ?? null,
            'max_value' => $fieldData['max_value'] ?? null,
            'min_length' => $fieldData['min_length'] ?? null,
            'max_length' => $fieldData['max_length'] ?? null,
            'pattern' => $fieldData['pattern'] ?? null,
            'options' => $this->jsonEncode($fieldData['options'] ?? null),
            'file_config' => $this->jsonEncode($fieldData['file_config'] ?? null),
            'show_condition' => $this->jsonEncode($fieldData['show_condition'] ?? null),
            'sort_order' => $fieldData['sort_order'] ?? 0,
            'column_width' => $fieldData['column_width'] ?? 12
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Update a form field
     */
    public function updateField(int $fieldId, array $data): bool
    {
        $allowedFields = [
            'field_label', 'field_type', 'is_required', 'placeholder',
            'help_text', 'default_value', 'min_value', 'max_value',
            'min_length', 'max_length', 'pattern', 'options',
            'file_config', 'show_condition', 'sort_order', 'column_width'
        ];

        $jsonFields = ['options', 'file_config', 'show_condition'];

        $fields = [];
        $params = ['id' => $fieldId];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $value = $data[$field];
                if (in_array($field, $jsonFields)) {
                    $value = $this->jsonEncode($value);
                }
                $fields[] = "{$field} = :{$field}";
                $params[$field] = $value;
            }
        }

        if (empty($fields)) {
            return true;
        }

        $fields[] = "updated_at = NOW()";

        $sql = "UPDATE workflow_step_fields SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Delete a form field
     */
    public function deleteField(int $fieldId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM workflow_step_fields WHERE id = :id AND step_id = :step_id");
        return $stmt->execute(['id' => $fieldId, 'step_id' => $this->id]);
    }

    // =========================================================================
    // TRANSITIONS
    // =========================================================================

    /**
     * Get outgoing transitions from this step
     */
    public function getOutgoingTransitions(): array
    {
        if (!$this->id) {
            return [];
        }

        $stmt = $this->pdo->prepare("
            SELECT t.*, s.code as to_step_code, s.name as to_step_name
            FROM workflow_transitions t
            JOIN workflow_steps s ON s.id = t.to_step_id
            WHERE t.from_step_id = :id
            ORDER BY t.priority
        ");
        $stmt->execute(['id' => $this->id]);
        return $stmt->fetchAll();
    }

    /**
     * Get incoming transitions to this step
     */
    public function getIncomingTransitions(): array
    {
        if (!$this->id) {
            return [];
        }

        $stmt = $this->pdo->prepare("
            SELECT t.*, s.code as from_step_code, s.name as from_step_name
            FROM workflow_transitions t
            JOIN workflow_steps s ON s.id = t.from_step_id
            WHERE t.to_step_id = :id
            ORDER BY t.priority
        ");
        $stmt->execute(['id' => $this->id]);
        return $stmt->fetchAll();
    }

    // =========================================================================
    // TASKS
    // =========================================================================

    /**
     * Get active tasks at this step
     */
    public function getActiveTasks(): array
    {
        if (!$this->id) {
            return [];
        }

        $stmt = $this->pdo->prepare("
            SELECT t.*, tk.ticket_number, tk.entity_type, tk.entity_id
            FROM workflow_tasks t
            JOIN workflow_tickets tk ON tk.id = t.ticket_id
            WHERE t.step_id = :id AND t.status IN ('pending', 'claimed', 'in_progress')
            ORDER BY t.created_at ASC
        ");
        $stmt->execute(['id' => $this->id]);
        return $stmt->fetchAll();
    }

    /**
     * Get task count by status
     */
    public function getTaskCounts(): array
    {
        if (!$this->id) {
            return [];
        }

        $stmt = $this->pdo->prepare("
            SELECT t.status, COUNT(*) as count
            FROM workflow_tasks t
            JOIN workflow_tickets tk ON tk.id = t.ticket_id
            WHERE t.step_id = :id AND tk.status = 'active'
            GROUP BY t.status
        ");
        $stmt->execute(['id' => $this->id]);

        $counts = [];
        while ($row = $stmt->fetch()) {
            $counts[$row['status']] = $row['count'];
        }
        return $counts;
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Get available actions as array
     */
    public function getAvailableActions(): array
    {
        return json_decode($this->data['available_actions'] ?? '[]', true);
    }

    /**
     * Get actor roles as array
     */
    public function getActorRoles(): array
    {
        return json_decode($this->data['actor_roles'] ?? '[]', true);
    }

    /**
     * Get decision config as array
     */
    public function getDecisionConfig(): array
    {
        return json_decode($this->data['decision_config'] ?? '{}', true);
    }

    /**
     * Get parallel config as array
     */
    public function getParallelConfig(): array
    {
        return json_decode($this->data['parallel_config'] ?? '{}', true);
    }

    /**
     * Check if this is a task step (requires human action)
     */
    public function isTaskStep(): bool
    {
        return $this->step_type === 'task';
    }

    /**
     * Check if this is a terminal step
     */
    public function isEndStep(): bool
    {
        return $this->step_type === 'end';
    }

    private function jsonEncode($value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_string($value)) {
            return $value; // Already JSON string
        }
        return json_encode($value);
    }

    // =========================================================================
    // STATIC METHODS
    // =========================================================================

    /**
     * Find steps by workflow ID
     */
    public static function findByWorkflow(int $workflowId): array
    {
        $pdo = Database::getTenantConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM workflow_steps
            WHERE workflow_id = :workflow_id AND is_active = 1
            ORDER BY sort_order, id
        ");
        $stmt->execute(['workflow_id' => $workflowId]);
        return $stmt->fetchAll();
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
