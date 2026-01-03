<?php
/**
 * Workflow Engine
 *
 * Core engine for managing workflows, tickets, and tasks.
 * Handles workflow lifecycle, task routing, parallel execution,
 * decision branching, and time-based transitions.
 *
 * @author Claude Code Assistant
 * @date December 2025
 */

class WorkflowEngine
{
    private $pdo;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->pdo = Database::getTenantConnection();
    }

    // =========================================================================
    // WORKFLOW MANAGEMENT
    // =========================================================================

    /**
     * Start a new workflow instance for an entity
     *
     * @param string $workflowCode The workflow code (e.g., 'APPLICANT_ADMISSION')
     * @param string $entityType The entity type (e.g., 'applicant')
     * @param int $entityId The entity ID
     * @param int|null $userId The user starting the workflow (null for system/auto)
     * @return array ['success' => bool, 'ticket' => array|null, 'message' => string]
     */
    public function startWorkflow(string $workflowCode, string $entityType, int $entityId, ?int $userId = null): array
    {
        try {
            $this->pdo->beginTransaction();

            // Get the workflow
            $workflow = $this->getWorkflowByCode($workflowCode);
            if (!$workflow) {
                throw new Exception("Workflow '{$workflowCode}' not found or inactive");
            }

            // Validate entity type matches
            if ($workflow['entity_type'] !== $entityType) {
                throw new Exception("Workflow entity type mismatch. Expected '{$workflow['entity_type']}', got '{$entityType}'");
            }

            // Check if entity already has an active ticket for this workflow
            if (!$workflow['allow_multiple_instances']) {
                $existingTicket = $this->getActiveTicketForEntity($workflow['id'], $entityType, $entityId);
                if ($existingTicket) {
                    throw new Exception("Entity already has an active workflow ticket: {$existingTicket['ticket_number']}");
                }
            }

            // Generate ticket number
            $ticketNumber = $this->generateTicketNumber();

            // Create the ticket
            $stmt = $this->pdo->prepare("
                INSERT INTO workflow_tickets (
                    ticket_number, workflow_id, workflow_version,
                    entity_type, entity_id, status,
                    started_by, started_by_type,
                    created_at
                ) VALUES (
                    :ticket_number, :workflow_id, :workflow_version,
                    :entity_type, :entity_id, 'active',
                    :started_by, :started_by_type,
                    NOW()
                )
            ");

            $stmt->execute([
                'ticket_number' => $ticketNumber,
                'workflow_id' => $workflow['id'],
                'workflow_version' => $workflow['version'],
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'started_by' => $userId,
                'started_by_type' => $userId ? 'user' : 'system'
            ]);

            $ticketId = $this->pdo->lastInsertId();

            // Log ticket creation
            $this->logHistory($ticketId, null, null, 'ticket_created', 'Workflow started', $userId);

            // Get the start step
            $startStep = $this->getStartStep($workflow['id']);
            if (!$startStep) {
                throw new Exception("No start step defined for workflow '{$workflowCode}'");
            }

            // Process from start step
            $this->processStep($ticketId, $startStep, $userId);

            $this->pdo->commit();

            // Return the ticket data
            $ticket = $this->getTicketById($ticketId);

            return [
                'success' => true,
                'ticket' => $ticket,
                'message' => "Workflow started successfully. Ticket: {$ticketNumber}"
            ];

        } catch (Exception $e) {
            $this->pdo->rollBack();
            $this->logError("startWorkflow error: " . $e->getMessage());
            return [
                'success' => false,
                'ticket' => null,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Start a sub-workflow from a parent ticket
     *
     * @param int $parentTicketId The parent ticket ID
     * @param int $parentTaskId The task that spawned the sub-workflow
     * @param string $subWorkflowCode The sub-workflow code
     * @return array ['success' => bool, 'ticket' => array|null, 'message' => string]
     */
    public function startSubWorkflow(int $parentTicketId, int $parentTaskId, string $subWorkflowCode): array
    {
        try {
            $this->pdo->beginTransaction();

            // Get parent ticket
            $parentTicket = $this->getTicketById($parentTicketId);
            if (!$parentTicket) {
                throw new Exception("Parent ticket not found");
            }

            // Get parent task
            $parentTask = $this->getTaskById($parentTaskId);
            if (!$parentTask) {
                throw new Exception("Parent task not found");
            }

            // Get sub-workflow
            $subWorkflow = $this->getWorkflowByCode($subWorkflowCode);
            if (!$subWorkflow) {
                throw new Exception("Sub-workflow '{$subWorkflowCode}' not found");
            }

            // Start the sub-workflow with the same entity
            $result = $this->startWorkflow(
                $subWorkflowCode,
                $parentTicket['entity_type'],
                $parentTicket['entity_id'],
                null // System initiated
            );

            if (!$result['success']) {
                throw new Exception("Failed to start sub-workflow: {$result['message']}");
            }

            $childTicketId = $result['ticket']['id'];

            // Get parent step for config
            $parentStep = $this->getStepById($parentTask['step_id']);
            $subWorkflowConfig = json_decode($parentStep['sub_workflow_config'] ?? '{}', true);

            // Link parent and child
            $stmt = $this->pdo->prepare("
                INSERT INTO workflow_sub_tickets (
                    parent_ticket_id, parent_task_id, parent_step_id,
                    child_ticket_id, config, status, created_at
                ) VALUES (
                    :parent_ticket_id, :parent_task_id, :parent_step_id,
                    :child_ticket_id, :config, 'active', NOW()
                )
            ");

            $stmt->execute([
                'parent_ticket_id' => $parentTicketId,
                'parent_task_id' => $parentTaskId,
                'parent_step_id' => $parentTask['step_id'],
                'child_ticket_id' => $childTicketId,
                'config' => json_encode($subWorkflowConfig)
            ]);

            $this->pdo->commit();

            return [
                'success' => true,
                'ticket' => $result['ticket'],
                'message' => "Sub-workflow started successfully"
            ];

        } catch (Exception $e) {
            $this->pdo->rollBack();
            $this->logError("startSubWorkflow error: " . $e->getMessage());
            return [
                'success' => false,
                'ticket' => null,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Cancel a workflow ticket
     *
     * @param int $ticketId The ticket ID
     * @param string $reason Reason for cancellation
     * @param int $userId User cancelling the ticket
     * @return array ['success' => bool, 'message' => string]
     */
    public function cancelTicket(int $ticketId, string $reason, int $userId): array
    {
        try {
            $this->pdo->beginTransaction();

            $ticket = $this->getTicketById($ticketId);
            if (!$ticket) {
                throw new Exception("Ticket not found");
            }

            if ($ticket['status'] !== 'active' && $ticket['status'] !== 'paused') {
                throw new Exception("Cannot cancel ticket with status: {$ticket['status']}");
            }

            // Update ticket status
            $stmt = $this->pdo->prepare("
                UPDATE workflow_tickets
                SET status = 'cancelled', outcome = 'cancelled', outcome_notes = :reason,
                    completed_at = NOW(), updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute(['id' => $ticketId, 'reason' => $reason]);

            // Cancel all pending tasks
            $stmt = $this->pdo->prepare("
                UPDATE workflow_tasks
                SET status = 'cancelled', updated_at = NOW()
                WHERE ticket_id = :ticket_id AND status IN ('pending', 'claimed', 'in_progress')
            ");
            $stmt->execute(['ticket_id' => $ticketId]);

            // Cancel scheduled transitions
            $stmt = $this->pdo->prepare("
                UPDATE workflow_scheduled_transitions
                SET status = 'cancelled'
                WHERE ticket_id = :ticket_id AND status = 'pending'
            ");
            $stmt->execute(['ticket_id' => $ticketId]);

            // Log
            $this->logHistory($ticketId, null, null, 'ticket_cancelled', "Cancelled: {$reason}", $userId);

            // Handle sub-tickets (cancel them too)
            $this->cancelSubTickets($ticketId, $reason, $userId);

            $this->pdo->commit();

            return ['success' => true, 'message' => 'Ticket cancelled successfully'];

        } catch (Exception $e) {
            $this->pdo->rollBack();
            $this->logError("cancelTicket error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Pause a workflow ticket
     */
    public function pauseTicket(int $ticketId, int $userId): array
    {
        try {
            $ticket = $this->getTicketById($ticketId);
            if (!$ticket || $ticket['status'] !== 'active') {
                throw new Exception("Cannot pause ticket - not active");
            }

            $stmt = $this->pdo->prepare("
                UPDATE workflow_tickets SET status = 'paused', updated_at = NOW() WHERE id = :id
            ");
            $stmt->execute(['id' => $ticketId]);

            $this->logHistory($ticketId, null, null, 'ticket_paused', 'Ticket paused', $userId);

            return ['success' => true, 'message' => 'Ticket paused'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Resume a paused workflow ticket
     */
    public function resumeTicket(int $ticketId, int $userId): array
    {
        try {
            $ticket = $this->getTicketById($ticketId);
            if (!$ticket || $ticket['status'] !== 'paused') {
                throw new Exception("Cannot resume ticket - not paused");
            }

            $stmt = $this->pdo->prepare("
                UPDATE workflow_tickets SET status = 'active', updated_at = NOW() WHERE id = :id
            ");
            $stmt->execute(['id' => $ticketId]);

            $this->logHistory($ticketId, null, null, 'ticket_resumed', 'Ticket resumed', $userId);

            return ['success' => true, 'message' => 'Ticket resumed'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // =========================================================================
    // TASK ACTIONS
    // =========================================================================

    /**
     * Claim a task (user takes ownership)
     *
     * @param int $taskId The task ID
     * @param int $userId The user claiming the task
     * @return array ['success' => bool, 'message' => string]
     */
    public function claimTask(int $taskId, int $userId): array
    {
        try {
            $task = $this->getTaskById($taskId);
            if (!$task) {
                throw new Exception("Task not found");
            }

            if ($task['status'] !== 'pending') {
                throw new Exception("Task cannot be claimed - status: {$task['status']}");
            }

            // Verify user has the required role
            if ($task['assigned_role'] && !$this->userHasRole($userId, $task['assigned_role'])) {
                throw new Exception("User does not have the required role: {$task['assigned_role']}");
            }

            $stmt = $this->pdo->prepare("
                UPDATE workflow_tasks
                SET status = 'claimed', claimed_by_user_id = :user_id, claimed_at = NOW(), updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute(['id' => $taskId, 'user_id' => $userId]);

            $this->logHistory($task['ticket_id'], $taskId, $task['step_id'], 'task_claimed', 'Task claimed', $userId);

            // Update user task queue
            $this->updateUserTaskQueue($taskId);

            return ['success' => true, 'message' => 'Task claimed successfully'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Release a claimed task back to the pool
     */
    public function releaseTask(int $taskId, int $userId): array
    {
        try {
            $task = $this->getTaskById($taskId);
            if (!$task) {
                throw new Exception("Task not found");
            }

            if ($task['status'] !== 'claimed' || $task['claimed_by_user_id'] != $userId) {
                throw new Exception("Cannot release task - not claimed by this user");
            }

            $stmt = $this->pdo->prepare("
                UPDATE workflow_tasks
                SET status = 'pending', claimed_by_user_id = NULL, claimed_at = NULL, updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute(['id' => $taskId]);

            $this->logHistory($task['ticket_id'], $taskId, $task['step_id'], 'task_released', 'Task released', $userId);

            // Update user task queue
            $this->updateUserTaskQueue($taskId);

            return ['success' => true, 'message' => 'Task released'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Complete a task with an action
     *
     * @param int $taskId The task ID
     * @param string $actionCode The action code (e.g., 'approve', 'reject')
     * @param int $userId The user completing the task
     * @param string|null $comment Optional comment
     * @param array|null $formData Optional form data collected
     * @param array|null $files Optional file uploads
     * @return array ['success' => bool, 'message' => string]
     */
    public function completeTask(
        int $taskId,
        string $actionCode,
        int $userId,
        ?string $comment = null,
        ?array $formData = null,
        ?array $files = null
    ): array {
        try {
            $this->pdo->beginTransaction();

            $task = $this->getTaskById($taskId);
            if (!$task) {
                throw new Exception("Task not found");
            }

            if (!in_array($task['status'], ['pending', 'claimed', 'in_progress'])) {
                throw new Exception("Task cannot be completed - status: {$task['status']}");
            }

            $step = $this->getStepById($task['step_id']);
            if (!$step) {
                throw new Exception("Step not found for task");
            }

            // Validate action is available for this step
            $availableActions = json_decode($step['available_actions'] ?? '[]', true);
            $actionConfig = null;
            foreach ($availableActions as $action) {
                if ($action['code'] === $actionCode) {
                    $actionConfig = $action;
                    break;
                }
            }

            if (!$actionConfig) {
                throw new Exception("Action '{$actionCode}' is not available for this step");
            }

            // Check if comment is required
            if (($step['require_comment'] || ($actionConfig['requires_comment'] ?? false)) && empty($comment)) {
                throw new Exception("Comment is required for this action");
            }

            // Validate required form fields
            $requiredFields = $this->getRequiredFormFields($step['id']);
            if (!empty($requiredFields) && empty($formData)) {
                throw new Exception("Required form fields must be filled");
            }

            // Update the task
            $stmt = $this->pdo->prepare("
                UPDATE workflow_tasks
                SET status = 'completed',
                    action_code = :action_code,
                    action_label = :action_label,
                    action_comment = :comment,
                    action_data = :action_data,
                    acted_at = NOW(),
                    acted_by_user_id = :user_id,
                    claimed_by_user_id = COALESCE(claimed_by_user_id, :user_id2),
                    updated_at = NOW()
                WHERE id = :id
            ");

            $stmt->execute([
                'id' => $taskId,
                'action_code' => $actionCode,
                'action_label' => $actionConfig['label'] ?? $actionCode,
                'comment' => $comment,
                'action_data' => $formData ? json_encode($formData) : null,
                'user_id' => $userId,
                'user_id2' => $userId
            ]);

            // Save form data
            if ($formData) {
                $this->saveTaskFormData($taskId, $formData);
            }

            // Handle file uploads
            if ($files) {
                $this->saveTaskAttachments($task['ticket_id'], $taskId, $files, $userId);
            }

            // Log completion
            $this->logHistory(
                $task['ticket_id'],
                $taskId,
                $task['step_id'],
                'task_completed',
                "Action: {$actionConfig['label']}. " . ($comment ? "Comment: {$comment}" : ''),
                $userId,
                ['action_code' => $actionCode, 'from_step' => $step['code']]
            );

            // Cancel scheduled transitions for this task
            $this->cancelTaskScheduledTransitions($taskId);

            // Remove from user task queue
            $this->removeFromUserTaskQueue($taskId);

            // Determine next step and process transition
            $nextStepCode = $actionConfig['next_step'] ?? null;
            if ($nextStepCode) {
                $ticket = $this->getTicketById($task['ticket_id']);
                $nextStep = $this->getStepByCode($ticket['workflow_id'], $nextStepCode);
                if ($nextStep) {
                    $this->processStep($task['ticket_id'], $nextStep, $userId, $step['code']);
                }
            }

            $this->pdo->commit();

            return ['success' => true, 'message' => 'Task completed successfully'];

        } catch (Exception $e) {
            $this->pdo->rollBack();
            $this->logError("completeTask error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Reassign a task to another user
     */
    public function reassignTask(int $taskId, int $newUserId, int $reassignedBy): array
    {
        try {
            $task = $this->getTaskById($taskId);
            if (!$task) {
                throw new Exception("Task not found");
            }

            if (!in_array($task['status'], ['pending', 'claimed', 'in_progress'])) {
                throw new Exception("Cannot reassign completed task");
            }

            $stmt = $this->pdo->prepare("
                UPDATE workflow_tasks
                SET assigned_user_id = :new_user,
                    claimed_by_user_id = :new_user,
                    claimed_at = NOW(),
                    status = 'claimed',
                    updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute(['id' => $taskId, 'new_user' => $newUserId]);

            $this->logHistory($task['ticket_id'], $taskId, $task['step_id'], 'task_reassigned',
                "Reassigned to user ID: {$newUserId}", $reassignedBy);

            $this->updateUserTaskQueue($taskId);

            return ['success' => true, 'message' => 'Task reassigned successfully'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // =========================================================================
    // STEP PROCESSING
    // =========================================================================

    /**
     * Process a workflow step (internal)
     *
     * @param int $ticketId The ticket ID
     * @param array $step The step data
     * @param int|null $userId Acting user (for history)
     * @param string|null $fromStepCode Previous step code (for history)
     */
    private function processStep(int $ticketId, array $step, ?int $userId = null, ?string $fromStepCode = null): void
    {
        // Log transition if coming from another step
        if ($fromStepCode) {
            $this->logHistory($ticketId, null, $step['id'], 'transition',
                "Transitioned from {$fromStepCode} to {$step['code']}", $userId,
                ['from_step' => $fromStepCode, 'to_step' => $step['code']]);
        }

        switch ($step['step_type']) {
            case 'start':
                // Start step - find first transition and move to next step
                $nextStep = $this->getNextStepFromTransitions($step);
                if ($nextStep) {
                    $this->processStep($ticketId, $nextStep, $userId, $step['code']);
                }
                break;

            case 'task':
                // Create task for actors
                $this->createTaskForStep($ticketId, $step);
                break;

            case 'decision':
                // Evaluate decision conditions
                $this->processDecisionStep($ticketId, $step, $userId);
                break;

            case 'parallel_split':
                // Fork into parallel branches
                $this->processParallelSplit($ticketId, $step, $userId);
                break;

            case 'parallel_join':
                // Check if all branches complete
                $this->processParallelJoin($ticketId, $step, $userId);
                break;

            case 'auto':
                // Execute auto action and proceed
                $this->processAutoStep($ticketId, $step, $userId);
                break;

            case 'sub_workflow':
                // Start sub-workflow
                $this->processSubWorkflowStep($ticketId, $step, $userId);
                break;

            case 'end':
                // Complete the ticket
                $this->completeTicket($ticketId, $step, $userId);
                break;
        }
    }

    /**
     * Create a task for a step
     */
    private function createTaskForStep(int $ticketId, array $step, ?string $branchCode = null): void
    {
        $ticket = $this->getTicketById($ticketId);
        $taskNumber = $this->generateTaskNumber();

        // Determine assignment
        $assignedRole = null;
        $assignedUserId = null;

        $actorRoles = json_decode($step['actor_roles'] ?? '[]', true);
        if (!empty($actorRoles)) {
            $assignedRole = $actorRoles[0]; // Primary role
        }

        if ($step['assignment_mode'] === 'auto_assign') {
            $assignedUserId = $this->autoAssignUser($step, $ticket);
        }

        // Calculate due date
        $dueAt = null;
        if ($step['sla_hours']) {
            $dueAt = date('Y-m-d H:i:s', strtotime("+{$step['sla_hours']} hours"));
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO workflow_tasks (
                ticket_id, step_id, task_number,
                assigned_role, assigned_user_id,
                status, due_at, branch_code, created_at
            ) VALUES (
                :ticket_id, :step_id, :task_number,
                :assigned_role, :assigned_user_id,
                'pending', :due_at, :branch_code, NOW()
            )
        ");

        $stmt->execute([
            'ticket_id' => $ticketId,
            'step_id' => $step['id'],
            'task_number' => $taskNumber,
            'assigned_role' => $assignedRole,
            'assigned_user_id' => $assignedUserId,
            'due_at' => $dueAt,
            'branch_code' => $branchCode
        ]);

        $taskId = $this->pdo->lastInsertId();

        // Log task creation
        $this->logHistory($ticketId, $taskId, $step['id'], 'task_created',
            "Task created: {$taskNumber} for step {$step['name']}");

        // Schedule auto-transitions if enabled
        if ($step['auto_transition_enabled'] && $step['auto_transition_hours']) {
            $this->scheduleAutoTransition($taskId, $ticketId, $step);
        }

        // Schedule reminder if enabled
        if ($step['reminder_enabled'] && $step['reminder_hours']) {
            $this->scheduleReminder($taskId, $ticketId, $step);
        }

        // Add to user task queue
        $this->addToUserTaskQueue($taskId);
    }

    /**
     * Process a decision step
     */
    private function processDecisionStep(int $ticketId, array $step, ?int $userId): void
    {
        $config = json_decode($step['decision_config'] ?? '{}', true);
        $mode = $config['mode'] ?? 'field_condition';

        if ($mode === 'user_choice') {
            // Create a task for user to make choice
            $this->createTaskForStep($ticketId, $step);
            return;
        }

        // Auto-evaluate field conditions
        $ticket = $this->getTicketById($ticketId);
        $entityData = $this->getEntityData($ticket['entity_type'], $ticket['entity_id']);

        $nextStepCode = $config['default_next_step'] ?? null;

        foreach ($config['conditions'] ?? [] as $condition) {
            if ($this->evaluateCondition($condition, $entityData)) {
                $nextStepCode = $condition['next_step'];
                break;
            }
        }

        if ($nextStepCode) {
            $nextStep = $this->getStepByCode($ticket['workflow_id'], $nextStepCode);
            if ($nextStep) {
                $this->processStep($ticketId, $nextStep, $userId, $step['code']);
            }
        }
    }

    /**
     * Process a parallel split
     */
    private function processParallelSplit(int $ticketId, array $step, ?int $userId): void
    {
        $config = json_decode($step['parallel_config'] ?? '{}', true);
        $branches = $config['branches'] ?? [];

        $ticket = $this->getTicketById($ticketId);
        $parallelState = [];

        foreach ($branches as $branchCode) {
            $branchStep = $this->getStepByCode($ticket['workflow_id'], $branchCode);
            if ($branchStep) {
                $parallelState[$branchCode] = 'active';
                // Process each branch
                if ($branchStep['step_type'] === 'task') {
                    $this->createTaskForStep($ticketId, $branchStep, $branchCode);
                } else {
                    $this->processStep($ticketId, $branchStep, $userId, $step['code']);
                }
            }
        }

        // Update ticket parallel state
        $stmt = $this->pdo->prepare("
            UPDATE workflow_tickets SET parallel_state = :state, updated_at = NOW() WHERE id = :id
        ");
        $stmt->execute(['id' => $ticketId, 'state' => json_encode($parallelState)]);

        $this->logHistory($ticketId, null, $step['id'], 'parallel_split',
            "Split into branches: " . implode(', ', $branches), $userId);
    }

    /**
     * Process a parallel join
     */
    private function processParallelJoin(int $ticketId, array $step, ?int $userId): void
    {
        $config = json_decode($step['parallel_config'] ?? '{}', true);
        $joinMode = $config['join_mode'] ?? 'all';
        $sourceBranches = $config['source_branches'] ?? [];
        $nextStepCode = $config['next_step'] ?? null;

        $ticket = $this->getTicketById($ticketId);
        $workflow = $this->getWorkflowById($ticket['workflow_id']);
        $parallelState = json_decode($ticket['parallel_state'] ?? '{}', true);

        // Check completion status of source branches
        $completedCount = 0;
        foreach ($sourceBranches as $branch) {
            if (($parallelState[$branch] ?? '') === 'completed') {
                $completedCount++;
            }
        }

        $shouldProceed = false;
        if ($joinMode === 'all') {
            $shouldProceed = ($completedCount === count($sourceBranches));
        } elseif ($joinMode === 'any') {
            $shouldProceed = ($completedCount > 0);
        }

        if ($shouldProceed && $nextStepCode) {
            $this->logHistory($ticketId, null, $step['id'], 'parallel_join',
                "Branches joined ({$completedCount}/" . count($sourceBranches) . ")", $userId);

            // Skip remaining branches if 'any' mode
            if ($joinMode === 'any') {
                $this->skipPendingBranchTasks($ticketId, $sourceBranches);
            }

            $nextStep = $this->getStepByCode($workflow['id'], $nextStepCode);
            if ($nextStep) {
                $this->processStep($ticketId, $nextStep, $userId, $step['code']);
            }
        }
    }

    /**
     * Process an auto step
     */
    private function processAutoStep(int $ticketId, array $step, ?int $userId): void
    {
        $config = json_decode($step['auto_config'] ?? '{}', true);
        $action = $config['action'] ?? null;
        $nextStepCode = $config['next_step'] ?? null;

        $ticket = $this->getTicketById($ticketId);

        // Execute the auto action
        switch ($action) {
            case 'update_field':
                $this->executeFieldUpdate($ticket, $config['config'] ?? []);
                break;
            case 'send_notification':
                $this->sendNotification($ticket, $config['config'] ?? []);
                break;
            case 'call_api':
                $this->callExternalApi($ticket, $config['config'] ?? []);
                break;
        }

        $this->logHistory($ticketId, null, $step['id'], 'task_completed',
            "Auto step completed: {$step['name']}", null);

        // Move to next step
        if ($nextStepCode) {
            $nextStep = $this->getStepByCode($ticket['workflow_id'], $nextStepCode);
            if ($nextStep) {
                $this->processStep($ticketId, $nextStep, $userId, $step['code']);
            }
        }
    }

    /**
     * Process a sub-workflow step
     */
    private function processSubWorkflowStep(int $ticketId, array $step, ?int $userId): void
    {
        $config = json_decode($step['sub_workflow_config'] ?? '{}', true);
        $subWorkflowCode = $config['sub_workflow_code'] ?? null;

        if (!$subWorkflowCode) {
            $this->logError("Sub-workflow step missing sub_workflow_code");
            return;
        }

        // Create a "waiting" task to track the parent side
        $taskNumber = $this->generateTaskNumber();
        $stmt = $this->pdo->prepare("
            INSERT INTO workflow_tasks (
                ticket_id, step_id, task_number, status, created_at
            ) VALUES (
                :ticket_id, :step_id, :task_number, 'pending', NOW()
            )
        ");
        $stmt->execute([
            'ticket_id' => $ticketId,
            'step_id' => $step['id'],
            'task_number' => $taskNumber
        ]);
        $parentTaskId = $this->pdo->lastInsertId();

        // Start the sub-workflow
        $result = $this->startSubWorkflow($ticketId, $parentTaskId, $subWorkflowCode);

        if (!$result['success']) {
            $this->logError("Failed to start sub-workflow: {$result['message']}");
        }
    }

    /**
     * Complete a ticket (reached end step)
     */
    private function completeTicket(int $ticketId, array $endStep, ?int $userId): void
    {
        // Determine outcome from step code (e.g., APPROVED_END, REJECTED_END)
        $outcome = 'completed';
        if (stripos($endStep['code'], 'approved') !== false || stripos($endStep['code'], 'accepted') !== false) {
            $outcome = 'approved';
        } elseif (stripos($endStep['code'], 'rejected') !== false) {
            $outcome = 'rejected';
        } elseif (stripos($endStep['code'], 'waitlist') !== false) {
            $outcome = 'waitlisted';
        }

        $stmt = $this->pdo->prepare("
            UPDATE workflow_tickets
            SET status = 'completed', outcome = :outcome, completed_at = NOW(), updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute(['id' => $ticketId, 'outcome' => $outcome]);

        $this->logHistory($ticketId, null, $endStep['id'], 'ticket_completed',
            "Workflow completed with outcome: {$outcome}", $userId);

        // Check if this is a sub-workflow and notify parent
        $this->handleSubWorkflowCompletion($ticketId, $outcome, $userId);
    }

    /**
     * Handle sub-workflow completion
     */
    private function handleSubWorkflowCompletion(int $childTicketId, string $outcome, ?int $userId): void
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM workflow_sub_tickets WHERE child_ticket_id = :child_id AND status = 'active'
        ");
        $stmt->execute(['child_id' => $childTicketId]);
        $subTicket = $stmt->fetch();

        if (!$subTicket) {
            return; // Not a sub-workflow
        }

        // Update sub-ticket status
        $stmt = $this->pdo->prepare("
            UPDATE workflow_sub_tickets SET status = 'completed', completed_at = NOW() WHERE id = :id
        ");
        $stmt->execute(['id' => $subTicket['id']]);

        $config = json_decode($subTicket['config'] ?? '{}', true);
        $outcomeMapping = $config['outcome_mapping'] ?? [];

        // Complete the parent task
        $stmt = $this->pdo->prepare("
            UPDATE workflow_tasks SET status = 'completed', action_code = :outcome, acted_at = NOW() WHERE id = :id
        ");
        $stmt->execute(['id' => $subTicket['parent_task_id'], 'outcome' => $outcome]);

        // Determine next step for parent based on outcome
        $nextStepCode = $outcomeMapping[$outcome] ?? null;
        if ($nextStepCode) {
            $parentTicket = $this->getTicketById($subTicket['parent_ticket_id']);
            $nextStep = $this->getStepByCode($parentTicket['workflow_id'], $nextStepCode);
            if ($nextStep) {
                $this->processStep($subTicket['parent_ticket_id'], $nextStep, $userId);
            }
        }
    }

    // =========================================================================
    // TASK QUERIES
    // =========================================================================

    /**
     * Get tasks for a user (by role or direct assignment)
     */
    public function getTasksForUser(int $userId, ?string $status = null): array
    {
        $userRoles = $this->getUserRoles($userId);

        $sql = "
            SELECT t.*, s.name as step_name, s.code as step_code,
                   w.name as workflow_name, w.code as workflow_code,
                   tk.ticket_number, tk.entity_type, tk.entity_id, tk.priority
            FROM workflow_tasks t
            JOIN workflow_steps s ON s.id = t.step_id
            JOIN workflow_tickets tk ON tk.id = t.ticket_id
            JOIN workflows w ON w.id = tk.workflow_id
            WHERE tk.status = 'active'
              AND (t.assigned_user_id = :user_id
                   OR t.claimed_by_user_id = :user_id2
                   OR (t.assigned_role IN (" . $this->buildPlaceholders($userRoles) . ") AND t.claimed_by_user_id IS NULL))
        ";

        $params = ['user_id' => $userId, 'user_id2' => $userId];
        foreach ($userRoles as $i => $role) {
            $params["role_{$i}"] = $role;
        }

        if ($status) {
            $sql .= " AND t.status = :status";
            $params['status'] = $status;
        } else {
            $sql .= " AND t.status IN ('pending', 'claimed', 'in_progress')";
        }

        $sql .= " ORDER BY t.is_overdue DESC, tk.priority DESC, t.due_at ASC, t.created_at ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get tasks for a specific role
     */
    public function getTasksForRole(string $role, ?string $status = null): array
    {
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

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get escalated tasks for a supervisor
     */
    public function getEscalatedTasks(int $supervisorId): array
    {
        // Get supervisor's roles
        $roles = $this->getUserRoles($supervisorId);

        $sql = "
            SELECT e.*, t.task_number, s.name as step_name, w.name as workflow_name,
                   tk.ticket_number, tk.entity_type, tk.entity_id
            FROM escalation_queue e
            JOIN workflow_tasks t ON t.id = e.task_id
            JOIN workflow_steps s ON s.id = t.step_id
            JOIN workflow_tickets tk ON tk.id = t.ticket_id
            JOIN workflows w ON w.id = tk.workflow_id
            WHERE e.status = 'pending'
              AND (e.escalated_to_user_id = :user_id
                   OR e.escalated_to_role IN (" . $this->buildPlaceholders($roles) . "))
            ORDER BY e.created_at ASC
        ";

        $params = ['user_id' => $supervisorId];
        foreach ($roles as $i => $role) {
            $params["role_{$i}"] = $role;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get overdue tasks
     */
    public function getOverdueTasks(): array
    {
        $stmt = $this->pdo->query("
            SELECT t.*, s.name as step_name, w.name as workflow_name,
                   tk.ticket_number, tk.entity_type, tk.entity_id
            FROM workflow_tasks t
            JOIN workflow_steps s ON s.id = t.step_id
            JOIN workflow_tickets tk ON tk.id = t.ticket_id
            JOIN workflows w ON w.id = tk.workflow_id
            WHERE t.status IN ('pending', 'claimed', 'in_progress')
              AND t.due_at IS NOT NULL
              AND t.due_at < NOW()
            ORDER BY t.due_at ASC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Get form fields for a task
     */
    public function getTaskFormFields(int $taskId): array
    {
        $task = $this->getTaskById($taskId);
        if (!$task) {
            return [];
        }

        return $this->getStepFormFields($task['step_id']);
    }

    // =========================================================================
    // TICKET QUERIES
    // =========================================================================

    /**
     * Get tickets for an entity
     */
    public function getTicketsByEntity(string $entityType, int $entityId): array
    {
        $stmt = $this->pdo->prepare("
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
     * Get ticket history (audit trail)
     */
    public function getTicketHistory(int $ticketId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT h.*, s.name as step_name, s.code as step_code
            FROM workflow_history h
            LEFT JOIN workflow_steps s ON s.id = h.step_id
            WHERE h.ticket_id = :ticket_id
            ORDER BY h.created_at ASC
        ");
        $stmt->execute(['ticket_id' => $ticketId]);
        return $stmt->fetchAll();
    }

    /**
     * Get ticket timeline (tasks with their actions)
     */
    public function getTicketTimeline(int $ticketId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT t.*, s.name as step_name, s.code as step_code
            FROM workflow_tasks t
            JOIN workflow_steps s ON s.id = t.step_id
            WHERE t.ticket_id = :ticket_id
            ORDER BY t.created_at ASC
        ");
        $stmt->execute(['ticket_id' => $ticketId]);
        return $stmt->fetchAll();
    }

    /**
     * Get sub-workflows for a ticket
     */
    public function getSubWorkflows(int $ticketId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT st.*, tk.ticket_number as child_ticket_number, tk.status as child_status,
                   w.name as child_workflow_name
            FROM workflow_sub_tickets st
            JOIN workflow_tickets tk ON tk.id = st.child_ticket_id
            JOIN workflows w ON w.id = tk.workflow_id
            WHERE st.parent_ticket_id = :ticket_id
            ORDER BY st.created_at ASC
        ");
        $stmt->execute(['ticket_id' => $ticketId]);
        return $stmt->fetchAll();
    }

    /**
     * Get parent workflow if this is a sub-workflow
     */
    public function getParentWorkflow(int $ticketId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT st.*, tk.ticket_number as parent_ticket_number, w.name as parent_workflow_name
            FROM workflow_sub_tickets st
            JOIN workflow_tickets tk ON tk.id = st.parent_ticket_id
            JOIN workflows w ON w.id = tk.workflow_id
            WHERE st.child_ticket_id = :ticket_id
        ");
        $stmt->execute(['ticket_id' => $ticketId]);
        return $stmt->fetch() ?: null;
    }

    // =========================================================================
    // WORKFLOW QUERIES
    // =========================================================================

    /**
     * Get available workflows for an entity type
     */
    public function getAvailableWorkflows(string $entityType): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM workflows
            WHERE entity_type = :entity_type AND is_active = 1
            ORDER BY name
        ");
        $stmt->execute(['entity_type' => $entityType]);
        return $stmt->fetchAll();
    }

    /**
     * Get workflow steps
     */
    public function getWorkflowSteps(int $workflowId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM workflow_steps
            WHERE workflow_id = :workflow_id AND is_active = 1
            ORDER BY sort_order, id
        ");
        $stmt->execute(['workflow_id' => $workflowId]);
        return $stmt->fetchAll();
    }

    /**
     * Get form fields for a step
     */
    public function getStepFormFields(int $stepId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM workflow_step_fields
            WHERE step_id = :step_id
            ORDER BY sort_order
        ");
        $stmt->execute(['step_id' => $stepId]);
        return $stmt->fetchAll();
    }

    // =========================================================================
    // BACKGROUND PROCESSING (Cron Jobs)
    // =========================================================================

    /**
     * Process scheduled workflow triggers
     */
    public function processScheduledTriggers(): void
    {
        $stmt = $this->pdo->query("
            SELECT * FROM workflows
            WHERE is_active = 1 AND trigger_config IS NOT NULL
        ");

        while ($workflow = $stmt->fetch()) {
            $config = json_decode($workflow['trigger_config'], true);
            if (($config['type'] ?? '') !== 'scheduled') {
                continue;
            }

            // Check if cron expression matches current time
            // (Simplified - in production use a proper cron library)
            $this->processScheduledWorkflow($workflow, $config);
        }
    }

    /**
     * Process task reminders
     */
    public function processReminders(): void
    {
        $stmt = $this->pdo->query("
            SELECT st.*, t.task_number, tk.ticket_number
            FROM workflow_scheduled_transitions st
            JOIN workflow_tasks t ON t.id = st.task_id
            JOIN workflow_tickets tk ON tk.id = st.ticket_id
            WHERE st.scheduled_action = 'reminder'
              AND st.status = 'pending'
              AND st.scheduled_at <= NOW()
        ");

        while ($scheduled = $stmt->fetch()) {
            $this->executeReminder($scheduled);
        }
    }

    /**
     * Process escalations
     */
    public function processEscalations(): void
    {
        // Find tasks that have exceeded their escalation time
        $stmt = $this->pdo->query("
            SELECT t.*, s.escalation_role, s.escalation_hours
            FROM workflow_tasks t
            JOIN workflow_steps s ON s.id = t.step_id
            JOIN workflow_tickets tk ON tk.id = t.ticket_id
            WHERE t.status IN ('pending', 'claimed', 'in_progress')
              AND s.escalation_enabled = 1
              AND t.escalated_at IS NULL
              AND t.created_at < DATE_SUB(NOW(), INTERVAL s.escalation_hours HOUR)
              AND tk.status = 'active'
        ");

        while ($task = $stmt->fetch()) {
            $this->escalateTask($task);
        }
    }

    /**
     * Process auto-transitions
     */
    public function processAutoTransitions(): void
    {
        $stmt = $this->pdo->query("
            SELECT st.*, t.ticket_id, t.step_id
            FROM workflow_scheduled_transitions st
            JOIN workflow_tasks t ON t.id = st.task_id
            WHERE st.status = 'pending'
              AND st.scheduled_action IN ('auto_approve', 'auto_reject', 'auto_escalate', 'auto_cancel')
              AND st.scheduled_at <= NOW()
        ");

        while ($scheduled = $stmt->fetch()) {
            $this->executeAutoTransition($scheduled);
        }
    }

    // =========================================================================
    // FORM DATA
    // =========================================================================

    /**
     * Save form data for a task
     */
    public function saveTaskFormData(int $taskId, array $formData): bool
    {
        try {
            foreach ($formData as $fieldCode => $value) {
                // Get field ID
                $task = $this->getTaskById($taskId);
                $stmt = $this->pdo->prepare("
                    SELECT id FROM workflow_step_fields
                    WHERE step_id = :step_id AND field_code = :field_code
                ");
                $stmt->execute(['step_id' => $task['step_id'], 'field_code' => $fieldCode]);
                $field = $stmt->fetch();

                if (!$field) continue;

                $stmt = $this->pdo->prepare("
                    INSERT INTO workflow_task_form_data (task_id, field_id, field_value, created_at)
                    VALUES (:task_id, :field_id, :value, NOW())
                    ON DUPLICATE KEY UPDATE field_value = :value2, updated_at = NOW()
                ");
                $stmt->execute([
                    'task_id' => $taskId,
                    'field_id' => $field['id'],
                    'value' => is_array($value) ? json_encode($value) : $value,
                    'value2' => is_array($value) ? json_encode($value) : $value
                ]);
            }
            return true;
        } catch (Exception $e) {
            $this->logError("saveTaskFormData error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get form data for a task
     */
    public function getTaskFormData(int $taskId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT fd.*, f.field_code, f.field_type, f.field_label
            FROM workflow_task_form_data fd
            JOIN workflow_step_fields f ON f.id = fd.field_id
            WHERE fd.task_id = :task_id
        ");
        $stmt->execute(['task_id' => $taskId]);
        return $stmt->fetchAll();
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    private function getWorkflowByCode(string $code): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM workflows WHERE code = :code AND is_active = 1");
        $stmt->execute(['code' => $code]);
        return $stmt->fetch() ?: null;
    }

    private function getWorkflowById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM workflows WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    private function getTicketById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM workflow_tickets WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    private function getTaskById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM workflow_tasks WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    private function getStepById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM workflow_steps WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    private function getStepByCode(int $workflowId, string $code): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM workflow_steps WHERE workflow_id = :workflow_id AND code = :code
        ");
        $stmt->execute(['workflow_id' => $workflowId, 'code' => $code]);
        return $stmt->fetch() ?: null;
    }

    private function getStartStep(int $workflowId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM workflow_steps WHERE workflow_id = :workflow_id AND step_type = 'start' LIMIT 1
        ");
        $stmt->execute(['workflow_id' => $workflowId]);
        return $stmt->fetch() ?: null;
    }

    private function getActiveTicketForEntity(int $workflowId, string $entityType, int $entityId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM workflow_tickets
            WHERE workflow_id = :workflow_id AND entity_type = :entity_type AND entity_id = :entity_id
              AND status IN ('active', 'paused')
            LIMIT 1
        ");
        $stmt->execute([
            'workflow_id' => $workflowId,
            'entity_type' => $entityType,
            'entity_id' => $entityId
        ]);
        return $stmt->fetch() ?: null;
    }

    private function getNextStepFromTransitions(array $step): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT s.* FROM workflow_transitions t
            JOIN workflow_steps s ON s.id = t.to_step_id
            WHERE t.from_step_id = :step_id
            ORDER BY t.priority
            LIMIT 1
        ");
        $stmt->execute(['step_id' => $step['id']]);
        return $stmt->fetch() ?: null;
    }

    private function getRequiredFormFields(int $stepId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM workflow_step_fields WHERE step_id = :step_id AND is_required = 1
        ");
        $stmt->execute(['step_id' => $stepId]);
        return $stmt->fetchAll();
    }

    private function generateTicketNumber(): string
    {
        $stmt = $this->pdo->query("SELECT generate_ticket_number()");
        return $stmt->fetchColumn();
    }

    private function generateTaskNumber(): string
    {
        $stmt = $this->pdo->query("SELECT generate_task_number()");
        return $stmt->fetchColumn();
    }

    private function logHistory(
        int $ticketId,
        ?int $taskId,
        ?int $stepId,
        string $eventType,
        string $description,
        ?int $userId = null,
        ?array $eventData = null
    ): void {
        $actorName = null;
        if ($userId) {
            $stmt = $this->pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) FROM users WHERE id = :id");
            $stmt->execute(['id' => $userId]);
            $actorName = $stmt->fetchColumn();
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO workflow_history (
                ticket_id, task_id, step_id, event_type, description,
                actor_type, actor_user_id, actor_name, event_data, created_at
            ) VALUES (
                :ticket_id, :task_id, :step_id, :event_type, :description,
                :actor_type, :actor_user_id, :actor_name, :event_data, NOW()
            )
        ");

        $stmt->execute([
            'ticket_id' => $ticketId,
            'task_id' => $taskId,
            'step_id' => $stepId,
            'event_type' => $eventType,
            'description' => $description,
            'actor_type' => $userId ? 'user' : 'system',
            'actor_user_id' => $userId,
            'actor_name' => $actorName,
            'event_data' => $eventData ? json_encode($eventData) : null
        ]);
    }

    private function logError(string $message): void
    {
        if (function_exists('logMessage')) {
            logMessage($message, 'error');
        } else {
            error_log("[WorkflowEngine] {$message}");
        }
    }

    private function getUserRoles(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT r.name FROM roles r
            JOIN user_roles ur ON ur.role_id = r.id
            WHERE ur.user_id = :user_id
        ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    private function userHasRole(int $userId, string $role): bool
    {
        $roles = $this->getUserRoles($userId);
        return in_array($role, $roles);
    }

    private function buildPlaceholders(array $items): string
    {
        if (empty($items)) {
            return "''"; // Return empty string placeholder
        }
        $placeholders = [];
        foreach ($items as $i => $item) {
            $placeholders[] = ":role_{$i}";
        }
        return implode(', ', $placeholders);
    }

    private function autoAssignUser(array $step, array $ticket): ?int
    {
        $config = json_decode($step['auto_assign_config'] ?? '{}', true);
        $rule = $config['rule'] ?? null;

        // Implement auto-assignment rules based on entity
        switch ($rule) {
            case 'campus_head':
                return $this->getCampusHead($ticket['entity_type'], $ticket['entity_id']);
            case 'department_head':
                return $this->getDepartmentHead($ticket['entity_type'], $ticket['entity_id']);
            default:
                return null;
        }
    }

    private function getCampusHead(string $entityType, int $entityId): ?int
    {
        // Implementation depends on entity type
        // For applicants, get the campus from the applicant and find the head
        return null; // Placeholder
    }

    private function getDepartmentHead(string $entityType, int $entityId): ?int
    {
        return null; // Placeholder
    }

    private function evaluateCondition(array $condition, array $data): bool
    {
        $field = $condition['field'] ?? '';
        $operator = $condition['operator'] ?? '=';
        $value = $condition['value'] ?? null;
        $fieldValue = $data[$field] ?? null;

        switch ($operator) {
            case '=':
            case '==':
                return $fieldValue == $value;
            case '!=':
            case '<>':
                return $fieldValue != $value;
            case '>':
                return $fieldValue > $value;
            case '<':
                return $fieldValue < $value;
            case '>=':
                return $fieldValue >= $value;
            case '<=':
                return $fieldValue <= $value;
            case 'in':
                return in_array($fieldValue, (array)$value);
            case 'not_in':
                return !in_array($fieldValue, (array)$value);
            case 'contains':
                return stripos($fieldValue, $value) !== false;
            case 'is_null':
                return $fieldValue === null;
            case 'is_not_null':
                return $fieldValue !== null;
            default:
                return false;
        }
    }

    private function getEntityData(string $entityType, int $entityId): array
    {
        $tableName = $this->getEntityTable($entityType);
        if (!$tableName) {
            return [];
        }

        $stmt = $this->pdo->prepare("SELECT * FROM {$tableName} WHERE id = :id");
        $stmt->execute(['id' => $entityId]);
        return $stmt->fetch() ?: [];
    }

    private function getEntityTable(string $entityType): ?string
    {
        $mapping = [
            'applicant' => 'applicants',
            'student' => 'students',
            'staff' => 'staff',
            'invoice' => 'invoices',
            'leave_request' => 'leave_requests',
        ];
        return $mapping[$entityType] ?? null;
    }

    private function cancelSubTickets(int $ticketId, string $reason, int $userId): void
    {
        $stmt = $this->pdo->prepare("
            SELECT child_ticket_id FROM workflow_sub_tickets
            WHERE parent_ticket_id = :ticket_id AND status = 'active'
        ");
        $stmt->execute(['ticket_id' => $ticketId]);

        while ($row = $stmt->fetch()) {
            $this->cancelTicket($row['child_ticket_id'], "Parent cancelled: {$reason}", $userId);
        }
    }

    private function skipPendingBranchTasks(int $ticketId, array $branches): void
    {
        foreach ($branches as $branch) {
            $stmt = $this->pdo->prepare("
                UPDATE workflow_tasks
                SET status = 'skipped', updated_at = NOW()
                WHERE ticket_id = :ticket_id AND branch_code = :branch AND status = 'pending'
            ");
            $stmt->execute(['ticket_id' => $ticketId, 'branch' => $branch]);
        }
    }

    private function scheduleAutoTransition(int $taskId, int $ticketId, array $step): void
    {
        $scheduledAt = date('Y-m-d H:i:s', strtotime("+{$step['auto_transition_hours']} hours"));

        $stmt = $this->pdo->prepare("
            INSERT INTO workflow_scheduled_transitions (
                task_id, ticket_id, scheduled_action, scheduled_at, action_config, created_at
            ) VALUES (
                :task_id, :ticket_id, :action, :scheduled_at, :config, NOW()
            )
        ");

        $stmt->execute([
            'task_id' => $taskId,
            'ticket_id' => $ticketId,
            'action' => $step['auto_transition_action'],
            'scheduled_at' => $scheduledAt,
            'config' => json_encode([
                'next_step' => $step['auto_transition_next_step'],
                'comment' => $step['auto_transition_comment']
            ])
        ]);
    }

    private function scheduleReminder(int $taskId, int $ticketId, array $step): void
    {
        $scheduledAt = date('Y-m-d H:i:s', strtotime("+{$step['reminder_hours']} hours"));

        $stmt = $this->pdo->prepare("
            INSERT INTO workflow_scheduled_transitions (
                task_id, ticket_id, scheduled_action, scheduled_at, created_at
            ) VALUES (
                :task_id, :ticket_id, 'reminder', :scheduled_at, NOW()
            )
        ");

        $stmt->execute([
            'task_id' => $taskId,
            'ticket_id' => $ticketId,
            'scheduled_at' => $scheduledAt
        ]);
    }

    private function cancelTaskScheduledTransitions(int $taskId): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE workflow_scheduled_transitions
            SET status = 'cancelled'
            WHERE task_id = :task_id AND status = 'pending'
        ");
        $stmt->execute(['task_id' => $taskId]);
    }

    private function updateUserTaskQueue(int $taskId): void
    {
        $task = $this->getTaskById($taskId);
        if (!$task) return;

        // Remove old entries for this task
        $this->removeFromUserTaskQueue($taskId);

        // Add new entries
        $this->addToUserTaskQueue($taskId);
    }

    private function addToUserTaskQueue(int $taskId): void
    {
        $task = $this->getTaskById($taskId);
        if (!$task || !in_array($task['status'], ['pending', 'claimed'])) return;

        $ticket = $this->getTicketById($task['ticket_id']);
        $step = $this->getStepById($task['step_id']);
        $workflow = $this->getWorkflowById($ticket['workflow_id']);

        $entityDisplay = $this->getEntityDisplayName($ticket['entity_type'], $ticket['entity_id']);

        // Get users who should see this task
        $userIds = [];

        if ($task['claimed_by_user_id']) {
            $userIds[] = $task['claimed_by_user_id'];
        } elseif ($task['assigned_user_id']) {
            $userIds[] = $task['assigned_user_id'];
        } elseif ($task['assigned_role']) {
            $userIds = $this->getUsersWithRole($task['assigned_role']);
        }

        foreach ($userIds as $userId) {
            $stmt = $this->pdo->prepare("
                INSERT INTO user_task_queue (
                    user_id, task_id, ticket_id, ticket_number,
                    workflow_name, step_name, entity_type, entity_id, entity_display,
                    task_status, priority, due_at, is_overdue, is_escalated,
                    is_role_assigned, assigned_role, created_at
                ) VALUES (
                    :user_id, :task_id, :ticket_id, :ticket_number,
                    :workflow_name, :step_name, :entity_type, :entity_id, :entity_display,
                    :task_status, :priority, :due_at, :is_overdue, :is_escalated,
                    :is_role_assigned, :assigned_role, NOW()
                ) ON DUPLICATE KEY UPDATE
                    task_status = :task_status2, is_overdue = :is_overdue2, updated_at = NOW()
            ");

            $stmt->execute([
                'user_id' => $userId,
                'task_id' => $taskId,
                'ticket_id' => $ticket['id'],
                'ticket_number' => $ticket['ticket_number'],
                'workflow_name' => $workflow['name'],
                'step_name' => $step['name'],
                'entity_type' => $ticket['entity_type'],
                'entity_id' => $ticket['entity_id'],
                'entity_display' => $entityDisplay,
                'task_status' => $task['status'],
                'priority' => $ticket['priority'],
                'due_at' => $task['due_at'],
                'is_overdue' => $task['is_overdue'],
                'is_escalated' => $task['escalated_at'] ? 1 : 0,
                'is_role_assigned' => $task['assigned_role'] && !$task['claimed_by_user_id'] ? 1 : 0,
                'assigned_role' => $task['assigned_role'],
                'task_status2' => $task['status'],
                'is_overdue2' => $task['is_overdue']
            ]);
        }
    }

    private function removeFromUserTaskQueue(int $taskId): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM user_task_queue WHERE task_id = :task_id");
        $stmt->execute(['task_id' => $taskId]);
    }

    private function getUsersWithRole(string $role): array
    {
        $stmt = $this->pdo->prepare("
            SELECT ur.user_id FROM user_roles ur
            JOIN roles r ON r.id = ur.role_id
            WHERE r.name = :role
        ");
        $stmt->execute(['role' => $role]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    private function getEntityDisplayName(string $entityType, int $entityId): string
    {
        switch ($entityType) {
            case 'applicant':
                $stmt = $this->pdo->prepare("
                    SELECT CONCAT(first_name, ' ', last_name, ' - ', application_ref) FROM applicants WHERE id = :id
                ");
                break;
            case 'student':
                $stmt = $this->pdo->prepare("
                    SELECT CONCAT(first_name, ' ', last_name, ' - ', admission_number) FROM students WHERE id = :id
                ");
                break;
            default:
                return "Entity #{$entityId}";
        }

        $stmt->execute(['id' => $entityId]);
        return $stmt->fetchColumn() ?: "Entity #{$entityId}";
    }

    private function saveTaskAttachments(int $ticketId, int $taskId, array $files, int $userId): void
    {
        foreach ($files as $file) {
            if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                continue;
            }

            $uploadDir = 'storage/workflow_attachments/' . date('Y/m');
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $newName = uniqid('wf_') . '.' . $ext;
            $filePath = $uploadDir . '/' . $newName;

            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO workflow_attachments (
                        ticket_id, task_id, file_name, file_path, file_type, file_size, uploaded_by
                    ) VALUES (
                        :ticket_id, :task_id, :file_name, :file_path, :file_type, :file_size, :uploaded_by
                    )
                ");

                $stmt->execute([
                    'ticket_id' => $ticketId,
                    'task_id' => $taskId,
                    'file_name' => $file['name'],
                    'file_path' => $filePath,
                    'file_type' => $file['type'] ?? null,
                    'file_size' => $file['size'] ?? 0,
                    'uploaded_by' => $userId
                ]);
            }
        }
    }

    private function executeFieldUpdate(array $ticket, array $config): void
    {
        $tableName = $this->getEntityTable($ticket['entity_type']);
        if (!$tableName) return;

        $field = $config['field'] ?? null;
        $value = $config['value'] ?? null;

        if ($field && $value !== null) {
            $stmt = $this->pdo->prepare("UPDATE {$tableName} SET {$field} = :value WHERE id = :id");
            $stmt->execute(['value' => $value, 'id' => $ticket['entity_id']]);
        }
    }

    private function sendNotification(array $ticket, array $config): void
    {
        // Integration with existing notification system
        // Placeholder - implement based on your notification service
    }

    private function callExternalApi(array $ticket, array $config): void
    {
        // External API call implementation
        // Placeholder
    }

    private function processScheduledWorkflow(array $workflow, array $config): void
    {
        // Implementation for scheduled workflow triggers
        // Placeholder
    }

    private function executeReminder(array $scheduled): void
    {
        $config = json_decode($scheduled['action_config'] ?? '{}', true);

        // Send reminder notification
        // Mark as executed
        $stmt = $this->pdo->prepare("
            UPDATE workflow_scheduled_transitions
            SET status = 'executed', executed_at = NOW(), execution_result = 'Reminder sent'
            WHERE id = :id
        ");
        $stmt->execute(['id' => $scheduled['id']]);
    }

    private function escalateTask(array $task): void
    {
        // Create escalation queue entry
        $stmt = $this->pdo->prepare("
            INSERT INTO escalation_queue (
                task_id, ticket_id, original_role, original_user_id,
                escalated_to_role, reason, created_at
            ) VALUES (
                :task_id, :ticket_id, :original_role, :original_user_id,
                :escalated_to_role, 'sla_breach', NOW()
            )
        ");

        $stmt->execute([
            'task_id' => $task['id'],
            'ticket_id' => $task['ticket_id'],
            'original_role' => $task['assigned_role'],
            'original_user_id' => $task['claimed_by_user_id'],
            'escalated_to_role' => $task['escalation_role']
        ]);

        // Update task
        $stmt = $this->pdo->prepare("
            UPDATE workflow_tasks SET escalated_at = NOW(), updated_at = NOW() WHERE id = :id
        ");
        $stmt->execute(['id' => $task['id']]);

        // Log
        $this->logHistory($task['ticket_id'], $task['id'], $task['step_id'], 'task_escalated',
            "Task escalated to {$task['escalation_role']} due to SLA breach");
    }

    private function executeAutoTransition(array $scheduled): void
    {
        $config = json_decode($scheduled['action_config'] ?? '{}', true);
        $task = $this->getTaskById($scheduled['task_id']);

        if (!$task || $task['status'] !== 'pending') {
            // Task already handled
            $stmt = $this->pdo->prepare("
                UPDATE workflow_scheduled_transitions SET status = 'cancelled' WHERE id = :id
            ");
            $stmt->execute(['id' => $scheduled['id']]);
            return;
        }

        switch ($scheduled['scheduled_action']) {
            case 'auto_approve':
            case 'auto_reject':
                // Complete the task automatically
                $actionCode = str_replace('auto_', '', $scheduled['scheduled_action']);
                $comment = $config['comment'] ?? "Auto-{$actionCode} due to timeout";

                // Update task
                $stmt = $this->pdo->prepare("
                    UPDATE workflow_tasks
                    SET status = 'completed', action_code = :action,
                        action_comment = :comment, acted_at = NOW(), updated_at = NOW()
                    WHERE id = :id
                ");
                $stmt->execute([
                    'id' => $task['id'],
                    'action' => $actionCode,
                    'comment' => $comment
                ]);

                $this->logHistory($task['ticket_id'], $task['id'], $task['step_id'], 'task_completed',
                    $comment, null, ['auto' => true]);

                // Process next step
                $nextStepCode = $config['next_step'] ?? null;
                if ($nextStepCode) {
                    $ticket = $this->getTicketById($task['ticket_id']);
                    $nextStep = $this->getStepByCode($ticket['workflow_id'], $nextStepCode);
                    if ($nextStep) {
                        $this->processStep($task['ticket_id'], $nextStep);
                    }
                }
                break;

            case 'auto_escalate':
                $step = $this->getStepById($task['step_id']);
                $task['escalation_role'] = $step['escalation_role'];
                $this->escalateTask($task);
                break;

            case 'auto_cancel':
                $this->cancelTicket($task['ticket_id'], 'Auto-cancelled due to timeout', 0);
                break;
        }

        // Mark scheduled transition as executed
        $stmt = $this->pdo->prepare("
            UPDATE workflow_scheduled_transitions
            SET status = 'executed', executed_at = NOW(),
                execution_result = :result
            WHERE id = :id
        ");
        $stmt->execute([
            'id' => $scheduled['id'],
            'result' => "Executed: {$scheduled['scheduled_action']}"
        ]);
    }
}
