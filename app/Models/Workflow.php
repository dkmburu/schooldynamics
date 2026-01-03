<?php
/**
 * Workflow Model
 *
 * Represents a workflow template/definition.
 *
 * @author Claude Code Assistant
 * @date December 2025
 */

class Workflow
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
     * Load workflow by ID
     */
    public function load(int $id): bool
    {
        $stmt = $this->pdo->prepare("SELECT * FROM workflows WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();

        if ($data) {
            $this->data = $data;
            return true;
        }
        return false;
    }

    /**
     * Load workflow by code
     */
    public function loadByCode(string $code): bool
    {
        $stmt = $this->pdo->prepare("SELECT * FROM workflows WHERE code = :code");
        $stmt->execute(['code' => $code]);
        $data = $stmt->fetch();

        if ($data) {
            $this->data = $data;
            return true;
        }
        return false;
    }

    /**
     * Create a new workflow
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO workflows (
                code, name, description, category, entity_type,
                trigger_config, allow_multiple_instances, parallel_join_mode,
                is_active, created_by, created_at
            ) VALUES (
                :code, :name, :description, :category, :entity_type,
                :trigger_config, :allow_multiple, :join_mode,
                :is_active, :created_by, NOW()
            )
        ");

        $stmt->execute([
            'code' => $data['code'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? null,
            'entity_type' => $data['entity_type'],
            'trigger_config' => isset($data['trigger_config']) ? json_encode($data['trigger_config']) : null,
            'allow_multiple' => $data['allow_multiple_instances'] ?? 0,
            'join_mode' => $data['parallel_join_mode'] ?? 'all',
            'is_active' => $data['is_active'] ?? 1,
            'created_by' => $data['created_by'] ?? null
        ]);

        $id = $this->pdo->lastInsertId();
        $this->load($id);
        return $id;
    }

    /**
     * Update workflow
     */
    public function update(array $data): bool
    {
        if (!$this->id) {
            return false;
        }

        $fields = [];
        $params = ['id' => $this->id];

        $allowedFields = [
            'name', 'description', 'category', 'trigger_config',
            'allow_multiple_instances', 'parallel_join_mode', 'is_active'
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $value = $data[$field];
                if ($field === 'trigger_config' && is_array($value)) {
                    $value = json_encode($value);
                }
                $fields[] = "{$field} = :{$field}";
                $params[$field] = $value;
            }
        }

        if (empty($fields)) {
            return true;
        }

        // Increment version on update
        $fields[] = "version = version + 1";
        $fields[] = "updated_at = NOW()";

        $sql = "UPDATE workflows SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute($params);

        if ($result) {
            $this->load($this->id);
        }

        return $result;
    }

    /**
     * Delete workflow (soft delete by deactivating)
     */
    public function delete(): bool
    {
        if (!$this->id) {
            return false;
        }

        // Check for active tickets
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM workflow_tickets WHERE workflow_id = :id AND status = 'active'
        ");
        $stmt->execute(['id' => $this->id]);
        $activeCount = $stmt->fetchColumn();

        if ($activeCount > 0) {
            throw new Exception("Cannot delete workflow with {$activeCount} active tickets");
        }

        $stmt = $this->pdo->prepare("UPDATE workflows SET is_active = 0, updated_at = NOW() WHERE id = :id");
        return $stmt->execute(['id' => $this->id]);
    }

    /**
     * Hard delete workflow (only if no tickets exist)
     */
    public function hardDelete(): bool
    {
        if (!$this->id) {
            return false;
        }

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM workflow_tickets WHERE workflow_id = :id");
        $stmt->execute(['id' => $this->id]);
        $ticketCount = $stmt->fetchColumn();

        if ($ticketCount > 0) {
            throw new Exception("Cannot delete workflow with existing tickets");
        }

        $stmt = $this->pdo->prepare("DELETE FROM workflows WHERE id = :id");
        return $stmt->execute(['id' => $this->id]);
    }

    // =========================================================================
    // STEPS MANAGEMENT
    // =========================================================================

    /**
     * Get all steps for this workflow
     */
    public function getSteps(): array
    {
        if (!$this->id) {
            return [];
        }

        $stmt = $this->pdo->prepare("
            SELECT * FROM workflow_steps
            WHERE workflow_id = :id AND is_active = 1
            ORDER BY sort_order, id
        ");
        $stmt->execute(['id' => $this->id]);
        return $stmt->fetchAll();
    }

    /**
     * Get start step
     */
    public function getStartStep(): ?array
    {
        if (!$this->id) {
            return null;
        }

        $stmt = $this->pdo->prepare("
            SELECT * FROM workflow_steps
            WHERE workflow_id = :id AND step_type = 'start'
            LIMIT 1
        ");
        $stmt->execute(['id' => $this->id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get end steps
     */
    public function getEndSteps(): array
    {
        if (!$this->id) {
            return [];
        }

        $stmt = $this->pdo->prepare("
            SELECT * FROM workflow_steps
            WHERE workflow_id = :id AND step_type = 'end'
        ");
        $stmt->execute(['id' => $this->id]);
        return $stmt->fetchAll();
    }

    /**
     * Add a step to this workflow
     */
    public function addStep(array $stepData): int
    {
        if (!$this->id) {
            throw new Exception("Workflow must be saved before adding steps");
        }

        $step = new WorkflowStep();
        $stepData['workflow_id'] = $this->id;
        return $step->create($stepData);
    }

    /**
     * Get step by code
     */
    public function getStepByCode(string $code): ?array
    {
        if (!$this->id) {
            return null;
        }

        $stmt = $this->pdo->prepare("
            SELECT * FROM workflow_steps
            WHERE workflow_id = :workflow_id AND code = :code
        ");
        $stmt->execute(['workflow_id' => $this->id, 'code' => $code]);
        return $stmt->fetch() ?: null;
    }

    // =========================================================================
    // TRANSITIONS MANAGEMENT
    // =========================================================================

    /**
     * Get all transitions for this workflow
     */
    public function getTransitions(): array
    {
        if (!$this->id) {
            return [];
        }

        $stmt = $this->pdo->prepare("
            SELECT t.*,
                   fs.code as from_step_code, fs.name as from_step_name,
                   ts.code as to_step_code, ts.name as to_step_name
            FROM workflow_transitions t
            JOIN workflow_steps fs ON fs.id = t.from_step_id
            JOIN workflow_steps ts ON ts.id = t.to_step_id
            WHERE t.workflow_id = :id
            ORDER BY t.priority
        ");
        $stmt->execute(['id' => $this->id]);
        return $stmt->fetchAll();
    }

    /**
     * Add a transition
     */
    public function addTransition(int $fromStepId, int $toStepId, ?string $actionCode = null, ?array $condition = null): int
    {
        if (!$this->id) {
            throw new Exception("Workflow must be saved before adding transitions");
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO workflow_transitions (
                workflow_id, from_step_id, to_step_id, action_code, condition_config, created_at
            ) VALUES (
                :workflow_id, :from_step_id, :to_step_id, :action_code, :condition, NOW()
            )
        ");

        $stmt->execute([
            'workflow_id' => $this->id,
            'from_step_id' => $fromStepId,
            'to_step_id' => $toStepId,
            'action_code' => $actionCode,
            'condition' => $condition ? json_encode($condition) : null
        ]);

        return $this->pdo->lastInsertId();
    }

    // =========================================================================
    // TICKETS
    // =========================================================================

    /**
     * Get all tickets for this workflow
     */
    public function getTickets(?string $status = null): array
    {
        if (!$this->id) {
            return [];
        }

        $sql = "SELECT * FROM workflow_tickets WHERE workflow_id = :id";
        $params = ['id' => $this->id];

        if ($status) {
            $sql .= " AND status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get ticket counts by status
     */
    public function getTicketCounts(): array
    {
        if (!$this->id) {
            return [];
        }

        $stmt = $this->pdo->prepare("
            SELECT status, COUNT(*) as count
            FROM workflow_tickets
            WHERE workflow_id = :id
            GROUP BY status
        ");
        $stmt->execute(['id' => $this->id]);

        $counts = [];
        while ($row = $stmt->fetch()) {
            $counts[$row['status']] = $row['count'];
        }
        return $counts;
    }

    // =========================================================================
    // VALIDATION
    // =========================================================================

    /**
     * Validate workflow structure
     */
    public function validate(): array
    {
        $errors = [];

        if (!$this->id) {
            $errors[] = "Workflow not loaded";
            return $errors;
        }

        $steps = $this->getSteps();

        // Check for start step
        $startSteps = array_filter($steps, fn($s) => $s['step_type'] === 'start');
        if (count($startSteps) === 0) {
            $errors[] = "Workflow must have a start step";
        } elseif (count($startSteps) > 1) {
            $errors[] = "Workflow can only have one start step";
        }

        // Check for at least one end step
        $endSteps = array_filter($steps, fn($s) => $s['step_type'] === 'end');
        if (count($endSteps) === 0) {
            $errors[] = "Workflow must have at least one end step";
        }

        // Check all task steps have actor roles
        foreach ($steps as $step) {
            if ($step['step_type'] === 'task') {
                $roles = json_decode($step['actor_roles'] ?? '[]', true);
                if (empty($roles)) {
                    $errors[] = "Task step '{$step['code']}' must have at least one actor role";
                }
            }
        }

        // Check transitions
        $transitions = $this->getTransitions();
        $stepCodes = array_column($steps, 'code');

        foreach ($transitions as $t) {
            if (!in_array($t['from_step_code'], $stepCodes)) {
                $errors[] = "Transition references non-existent from_step: {$t['from_step_code']}";
            }
            if (!in_array($t['to_step_code'], $stepCodes)) {
                $errors[] = "Transition references non-existent to_step: {$t['to_step_code']}";
            }
        }

        return $errors;
    }

    // =========================================================================
    // IMPORT/EXPORT
    // =========================================================================

    /**
     * Export workflow as JSON
     */
    public function export(): array
    {
        if (!$this->id) {
            return [];
        }

        $steps = $this->getSteps();
        $stepsWithFields = [];

        foreach ($steps as $step) {
            $stmt = $this->pdo->prepare("SELECT * FROM workflow_step_fields WHERE step_id = :id ORDER BY sort_order");
            $stmt->execute(['id' => $step['id']]);
            $step['fields'] = $stmt->fetchAll();
            $stepsWithFields[] = $step;
        }

        return [
            'workflow' => $this->data,
            'steps' => $stepsWithFields,
            'transitions' => $this->getTransitions(),
            'exported_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Import workflow from JSON
     */
    public static function import(array $data, int $createdBy): int
    {
        $pdo = Database::getTenantConnection();
        $pdo->beginTransaction();

        try {
            $workflow = new self();

            // Modify code to avoid conflicts
            $workflowData = $data['workflow'];
            $workflowData['code'] = $workflowData['code'] . '_' . time();
            $workflowData['created_by'] = $createdBy;

            $workflowId = $workflow->create($workflowData);

            $stepIdMap = []; // Old ID => New ID

            // Create steps
            foreach ($data['steps'] as $stepData) {
                $oldStepId = $stepData['id'];
                unset($stepData['id'], $stepData['workflow_id'], $stepData['created_at'], $stepData['updated_at']);

                $fields = $stepData['fields'] ?? [];
                unset($stepData['fields']);

                $step = new WorkflowStep();
                $stepData['workflow_id'] = $workflowId;
                $newStepId = $step->create($stepData);
                $stepIdMap[$oldStepId] = $newStepId;

                // Create fields
                foreach ($fields as $fieldData) {
                    unset($fieldData['id'], $fieldData['step_id'], $fieldData['created_at'], $fieldData['updated_at']);
                    $fieldData['step_id'] = $newStepId;

                    $stmt = $pdo->prepare("
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
                    $stmt->execute($fieldData);
                }
            }

            // Create transitions with mapped IDs
            foreach ($data['transitions'] as $transData) {
                $fromStepId = $stepIdMap[$transData['from_step_id']] ?? null;
                $toStepId = $stepIdMap[$transData['to_step_id']] ?? null;

                if ($fromStepId && $toStepId) {
                    $workflow->addTransition(
                        $fromStepId,
                        $toStepId,
                        $transData['action_code'] ?? null,
                        $transData['condition_config'] ? json_decode($transData['condition_config'], true) : null
                    );
                }
            }

            $pdo->commit();
            return $workflowId;

        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // =========================================================================
    // STATIC METHODS
    // =========================================================================

    /**
     * Find all workflows
     */
    public static function all(?bool $activeOnly = true): array
    {
        $pdo = Database::getTenantConnection();
        $sql = "SELECT * FROM workflows";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY name";

        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Find workflows by entity type
     */
    public static function findByEntityType(string $entityType): array
    {
        $pdo = Database::getTenantConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM workflows
            WHERE entity_type = :entity_type AND is_active = 1
            ORDER BY name
        ");
        $stmt->execute(['entity_type' => $entityType]);
        return $stmt->fetchAll();
    }

    /**
     * Find workflows by category
     */
    public static function findByCategory(string $category): array
    {
        $pdo = Database::getTenantConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM workflows
            WHERE category = :category AND is_active = 1
            ORDER BY name
        ");
        $stmt->execute(['category' => $category]);
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

    /**
     * Get trigger config as array
     */
    public function getTriggerConfig(): array
    {
        return json_decode($this->data['trigger_config'] ?? '{}', true);
    }
}
