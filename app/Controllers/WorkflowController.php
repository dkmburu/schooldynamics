<?php
/**
 * Workflow Controller
 *
 * Handles workflow task management, task inbox, and workflow operations.
 *
 * @author Claude Code Assistant
 * @date December 2025
 */

require_once __DIR__ . '/../Services/WorkflowEngine.php';
require_once __DIR__ . '/../Models/Workflow.php';
require_once __DIR__ . '/../Models/WorkflowStep.php';
require_once __DIR__ . '/../Models/WorkflowTicket.php';
require_once __DIR__ . '/../Models/WorkflowTask.php';

class WorkflowController
{
    private $engine;

    public function __construct()
    {
        $this->engine = new WorkflowEngine();
    }

    // =========================================================================
    // TASK INBOX (My Tasks)
    // =========================================================================

    /**
     * Display task inbox for current user
     */
    public function inbox()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        $userId = $_SESSION['user_id'];
        $status = Request::get('status', '');
        $priority = Request::get('priority', '');

        // Get tasks for user
        $tasks = WorkflowTask::findForUser($userId, $status ?: null);

        // Get task counts
        $counts = WorkflowTask::getCountsForUser($userId);

        // Filter by priority if specified
        if ($priority) {
            $tasks = array_filter($tasks, fn($t) => $t['priority'] === $priority);
        }

        Response::view('workflow.inbox', [
            'tasks' => $tasks,
            'counts' => $counts,
            'statusFilter' => $status,
            'priorityFilter' => $priority,
            'breadcrumbs' => [
                ['label' => 'Tasks', 'url' => '#'],
                ['label' => 'My Tasks', 'url' => '/tasks']
            ]
        ]);
    }

    /**
     * View a specific task
     */
    public function viewTask($taskId)
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        $task = new WorkflowTask((int)$taskId);
        if (!$task->id) {
            flash('error', 'Task not found');
            Response::redirect('/tasks');
        }

        $ticket = new WorkflowTicket($task->ticket_id);
        $step = $task->getStep();
        $workflow = $ticket->getWorkflow();

        // Check if user can view this task
        $userId = $_SESSION['user_id'];
        if (!$this->canUserAccessTask($userId, $task)) {
            flash('error', 'You do not have permission to view this task');
            Response::redirect('/tasks');
        }

        Response::view('workflow.task', [
            'task' => $task,
            'ticket' => $ticket,
            'step' => $step,
            'workflow' => $workflow,
            'availableActions' => $task->getAvailableActions(),
            'formFields' => $task->getFormFields(),
            'formData' => $task->getFormData(),
            'attachments' => $task->getAttachments(),
            'history' => $task->getHistory(),
            'entity' => $ticket->getEntity(),
            'entityDisplayName' => $ticket->getEntityDisplayName(),
            'breadcrumbs' => [
                ['label' => 'Tasks', 'url' => '#'],
                ['label' => 'My Tasks', 'url' => '/tasks'],
                ['label' => $task->task_number, 'url' => "/tasks/{$taskId}"]
            ]
        ]);
    }

    /**
     * Claim a task
     */
    public function claimTask()
    {
        if (!isAuthenticated()) {
            return Response::json(['success' => false, 'message' => 'Not authenticated']);
        }

        $taskId = (int)Request::post('task_id');
        $userId = $_SESSION['user_id'];

        $result = $this->engine->claimTask($taskId, $userId);

        if (Request::isAjax()) {
            return Response::json($result);
        }

        if ($result['success']) {
            flash('success', 'Task claimed successfully');
        } else {
            flash('error', $result['message']);
        }

        Response::redirect("/tasks/{$taskId}");
    }

    /**
     * Release a claimed task
     */
    public function releaseTask()
    {
        if (!isAuthenticated()) {
            return Response::json(['success' => false, 'message' => 'Not authenticated']);
        }

        $taskId = (int)Request::post('task_id');
        $userId = $_SESSION['user_id'];

        $result = $this->engine->releaseTask($taskId, $userId);

        if (Request::isAjax()) {
            return Response::json($result);
        }

        if ($result['success']) {
            flash('success', 'Task released');
        } else {
            flash('error', $result['message']);
        }

        Response::redirect('/tasks');
    }

    /**
     * Complete a task with an action
     */
    public function completeTask()
    {
        if (!isAuthenticated()) {
            return Response::json(['success' => false, 'message' => 'Not authenticated']);
        }

        $taskId = (int)Request::post('task_id');
        $actionCode = Request::post('action_code');
        $comment = Request::post('comment');
        $userId = $_SESSION['user_id'];

        // Collect form data (all fields except reserved ones)
        $reserved = ['task_id', 'action_code', 'comment'];
        $formData = [];
        foreach ($_POST as $key => $value) {
            if (!in_array($key, $reserved) && strpos($key, 'field_') === 0) {
                $fieldCode = substr($key, 6); // Remove 'field_' prefix
                $formData[$fieldCode] = $value;
            }
        }

        // Handle file uploads
        $files = $_FILES['attachments'] ?? null;

        $result = $this->engine->completeTask(
            $taskId,
            $actionCode,
            $userId,
            $comment,
            !empty($formData) ? $formData : null,
            $files
        );

        if (Request::isAjax()) {
            return Response::json($result);
        }

        if ($result['success']) {
            flash('success', 'Task completed successfully');
            Response::redirect('/tasks');
        } else {
            flash('error', $result['message']);
            Response::redirect("/tasks/{$taskId}");
        }
    }

    /**
     * Reassign a task
     */
    public function reassignTask()
    {
        if (!isAuthenticated()) {
            return Response::json(['success' => false, 'message' => 'Not authenticated']);
        }

        $taskId = (int)Request::post('task_id');
        $newUserId = (int)Request::post('new_user_id');
        $reassignedBy = $_SESSION['user_id'];

        $result = $this->engine->reassignTask($taskId, $newUserId, $reassignedBy);

        if (Request::isAjax()) {
            return Response::json($result);
        }

        if ($result['success']) {
            flash('success', 'Task reassigned successfully');
        } else {
            flash('error', $result['message']);
        }

        Response::redirect("/tasks/{$taskId}");
    }

    // =========================================================================
    // TICKET OPERATIONS
    // =========================================================================

    /**
     * View workflow tickets for an entity
     */
    public function entityTickets($entityType, $entityId)
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        $tickets = WorkflowTicket::findByEntity($entityType, $entityId);
        $availableWorkflows = Workflow::findByEntityType($entityType);

        if (Request::isAjax()) {
            return Response::json([
                'success' => true,
                'tickets' => $tickets,
                'availableWorkflows' => $availableWorkflows
            ]);
        }

        Response::view('workflow.entity_tickets', [
            'tickets' => $tickets,
            'availableWorkflows' => $availableWorkflows,
            'entityType' => $entityType,
            'entityId' => $entityId
        ]);
    }

    /**
     * View a specific ticket
     */
    public function viewTicket($ticketId)
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        $ticket = new WorkflowTicket((int)$ticketId);
        if (!$ticket->id) {
            flash('error', 'Ticket not found');
            Response::redirect('/tasks');
        }

        $workflow = $ticket->getWorkflow();

        Response::view('workflow.ticket', [
            'ticket' => $ticket,
            'workflow' => $workflow,
            'tasks' => $ticket->getTasks(),
            'activeTasks' => $ticket->getActiveTasks(),
            'history' => $ticket->getHistory(),
            'comments' => $ticket->getComments(),
            'attachments' => $ticket->getAttachments(),
            'timeline' => $ticket->getTimeline(),
            'subWorkflows' => $ticket->getSubWorkflows(),
            'parentTicket' => $ticket->getParentTicket(),
            'entity' => $ticket->getEntity(),
            'entityDisplayName' => $ticket->getEntityDisplayName(),
            'breadcrumbs' => [
                ['label' => 'Tasks', 'url' => '#'],
                ['label' => 'Tickets', 'url' => '/workflow/tickets'],
                ['label' => $ticket->ticket_number, 'url' => "/workflow/tickets/{$ticketId}"]
            ]
        ]);
    }

    /**
     * Start a workflow for an entity
     */
    public function startWorkflow()
    {
        if (!isAuthenticated()) {
            return Response::json(['success' => false, 'message' => 'Not authenticated']);
        }

        $workflowCode = Request::post('workflow_code');
        $entityType = Request::post('entity_type');
        $entityId = (int)Request::post('entity_id');
        $userId = $_SESSION['user_id'];

        $result = $this->engine->startWorkflow($workflowCode, $entityType, $entityId, $userId);

        if (Request::isAjax()) {
            return Response::json($result);
        }

        if ($result['success']) {
            flash('success', $result['message']);
            Response::redirect("/workflow/tickets/{$result['ticket']['id']}");
        } else {
            flash('error', $result['message']);
            Response::redirect($_SERVER['HTTP_REFERER'] ?? '/tasks');
        }
    }

    /**
     * Cancel a workflow ticket
     */
    public function cancelTicket()
    {
        if (!isAuthenticated()) {
            return Response::json(['success' => false, 'message' => 'Not authenticated']);
        }

        // Check permission
        if (!hasPermission('Workflow.cancel') && !Gate::hasRole('ADMIN')) {
            return Response::json(['success' => false, 'message' => 'Permission denied']);
        }

        $ticketId = (int)Request::post('ticket_id');
        $reason = Request::post('reason');
        $userId = $_SESSION['user_id'];

        $result = $this->engine->cancelTicket($ticketId, $reason, $userId);

        if (Request::isAjax()) {
            return Response::json($result);
        }

        if ($result['success']) {
            flash('success', 'Ticket cancelled');
        } else {
            flash('error', $result['message']);
        }

        Response::redirect('/tasks');
    }

    /**
     * Pause a workflow ticket
     */
    public function pauseTicket()
    {
        if (!isAuthenticated()) {
            return Response::json(['success' => false, 'message' => 'Not authenticated']);
        }

        $ticketId = (int)Request::post('ticket_id');
        $userId = $_SESSION['user_id'];

        $result = $this->engine->pauseTicket($ticketId, $userId);

        if (Request::isAjax()) {
            return Response::json($result);
        }

        flash($result['success'] ? 'success' : 'error', $result['message']);
        Response::redirect("/workflow/tickets/{$ticketId}");
    }

    /**
     * Resume a paused ticket
     */
    public function resumeTicket()
    {
        if (!isAuthenticated()) {
            return Response::json(['success' => false, 'message' => 'Not authenticated']);
        }

        $ticketId = (int)Request::post('ticket_id');
        $userId = $_SESSION['user_id'];

        $result = $this->engine->resumeTicket($ticketId, $userId);

        if (Request::isAjax()) {
            return Response::json($result);
        }

        flash($result['success'] ? 'success' : 'error', $result['message']);
        Response::redirect("/workflow/tickets/{$ticketId}");
    }

    /**
     * Add a comment to a ticket
     */
    public function addComment()
    {
        if (!isAuthenticated()) {
            return Response::json(['success' => false, 'message' => 'Not authenticated']);
        }

        $ticketId = (int)Request::post('ticket_id');
        $comment = Request::post('comment');
        $taskId = Request::post('task_id') ? (int)Request::post('task_id') : null;
        $isInternal = Request::post('is_internal') ? true : false;
        $userId = $_SESSION['user_id'];

        try {
            $ticket = new WorkflowTicket($ticketId);
            $commentId = $ticket->addComment($userId, $comment, $taskId, $isInternal);

            $result = ['success' => true, 'comment_id' => $commentId];
        } catch (Exception $e) {
            $result = ['success' => false, 'message' => $e->getMessage()];
        }

        if (Request::isAjax()) {
            return Response::json($result);
        }

        flash($result['success'] ? 'success' : 'error', $result['success'] ? 'Comment added' : $result['message']);
        Response::redirect("/workflow/tickets/{$ticketId}");
    }

    // =========================================================================
    // ESCALATIONS
    // =========================================================================

    /**
     * View escalation queue (for supervisors)
     */
    public function escalations()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        $userId = $_SESSION['user_id'];
        $escalations = $this->engine->getEscalatedTasks($userId);

        Response::view('workflow.escalations', [
            'escalations' => $escalations,
            'breadcrumbs' => [
                ['label' => 'Tasks', 'url' => '#'],
                ['label' => 'Escalations', 'url' => '/tasks/escalations']
            ]
        ]);
    }

    /**
     * Acknowledge an escalation
     */
    public function acknowledgeEscalation()
    {
        if (!isAuthenticated()) {
            return Response::json(['success' => false, 'message' => 'Not authenticated']);
        }

        $escalationId = (int)Request::post('escalation_id');
        $userId = $_SESSION['user_id'];

        try {
            $pdo = Database::getTenantConnection();
            $stmt = $pdo->prepare("
                UPDATE escalation_queue
                SET status = 'acknowledged', acknowledged_at = NOW(), acknowledged_by = :user_id
                WHERE id = :id
            ");
            $stmt->execute(['id' => $escalationId, 'user_id' => $userId]);

            $result = ['success' => true, 'message' => 'Escalation acknowledged'];
        } catch (Exception $e) {
            $result = ['success' => false, 'message' => $e->getMessage()];
        }

        if (Request::isAjax()) {
            return Response::json($result);
        }

        flash($result['success'] ? 'success' : 'error', $result['message']);
        Response::redirect('/tasks/escalations');
    }

    // =========================================================================
    // WORKFLOW MANAGEMENT (Admin)
    // =========================================================================

    /**
     * List all workflows (admin)
     */
    public function listWorkflows()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        if (!hasPermission('Workflow.manage') && !Gate::hasRole('ADMIN')) {
            flash('error', 'Permission denied');
            Response::redirect('/dashboard');
        }

        $pdo = Database::getTenantConnection();

        // Get workflows with step counts and active tickets
        $stmt = $pdo->query("
            SELECT w.*,
                   (SELECT COUNT(*) FROM workflow_steps WHERE workflow_id = w.id AND is_active = 1) as step_count,
                   (SELECT COUNT(*) FROM workflow_tickets WHERE workflow_id = w.id AND status = 'active') as active_tickets
            FROM workflows w
            ORDER BY w.name
        ");
        $workflows = $stmt->fetchAll();

        // Get total active tickets
        $stmt = $pdo->query("SELECT COUNT(*) FROM workflow_tickets WHERE status = 'active'");
        $activeTicketsCount = $stmt->fetchColumn();

        Response::view('workflow.admin.index', [
            'workflows' => $workflows,
            'activeTicketsCount' => $activeTicketsCount,
            'breadcrumbs' => [
                ['label' => 'Settings', 'url' => '#'],
                ['label' => 'Workflows', 'url' => '/workflow/admin']
            ]
        ]);
    }

    /**
     * Create a new workflow
     */
    public function createWorkflow()
    {
        if (!isAuthenticated()) {
            return Response::json(['success' => false, 'message' => 'Not authenticated']);
        }

        if (!hasPermission('Workflow.manage') && !Gate::hasRole('ADMIN')) {
            return Response::json(['success' => false, 'message' => 'Permission denied']);
        }

        $name = Request::post('name');
        $code = Request::post('code');
        $entityType = Request::post('entity_type');
        $description = Request::post('description');

        if (!$name || !$code || !$entityType) {
            return Response::json(['success' => false, 'message' => 'Name, code and entity type are required']);
        }

        try {
            $pdo = Database::getTenantConnection();

            // Check for duplicate code
            $stmt = $pdo->prepare("SELECT id FROM workflows WHERE code = :code");
            $stmt->execute(['code' => $code]);
            if ($stmt->fetch()) {
                return Response::json(['success' => false, 'message' => 'A workflow with this code already exists']);
            }

            // Create workflow
            $stmt = $pdo->prepare("
                INSERT INTO workflows (name, code, description, entity_type, is_active, created_at)
                VALUES (:name, :code, :description, :entity_type, 0, NOW())
            ");
            $stmt->execute([
                'name' => $name,
                'code' => $code,
                'description' => $description,
                'entity_type' => $entityType
            ]);

            $workflowId = $pdo->lastInsertId();

            // Create default start and end steps
            $stmt = $pdo->prepare("
                INSERT INTO workflow_steps (workflow_id, code, name, step_type, sort_order, ui_position_x, ui_position_y, is_active, created_at)
                VALUES
                (:wf_id, 'START', 'Start', 'start', 1, 100, 100, 1, NOW()),
                (:wf_id2, 'END', 'End', 'end', 999, 500, 100, 1, NOW())
            ");
            $stmt->execute(['wf_id' => $workflowId, 'wf_id2' => $workflowId]);

            return Response::json(['success' => true, 'workflow_id' => $workflowId, 'message' => 'Workflow created']);
        } catch (Exception $e) {
            return Response::json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Clone an existing workflow
     */
    public function cloneWorkflow()
    {
        if (!isAuthenticated()) {
            return Response::json(['success' => false, 'message' => 'Not authenticated']);
        }

        if (!hasPermission('Workflow.manage') && !Gate::hasRole('ADMIN')) {
            return Response::json(['success' => false, 'message' => 'Permission denied']);
        }

        $workflowId = (int)Request::post('workflow_id');
        $newName = Request::post('new_name');

        if (!$workflowId || !$newName) {
            return Response::json(['success' => false, 'message' => 'Workflow ID and new name are required']);
        }

        try {
            $pdo = Database::getTenantConnection();

            // Load source workflow
            $stmt = $pdo->prepare("SELECT * FROM workflows WHERE id = :id");
            $stmt->execute(['id' => $workflowId]);
            $source = $stmt->fetch();

            if (!$source) {
                return Response::json(['success' => false, 'message' => 'Source workflow not found']);
            }

            // Generate unique code
            $newCode = $source['code'] . '_COPY_' . time();

            // Create new workflow
            $stmt = $pdo->prepare("
                INSERT INTO workflows (name, code, description, entity_type, trigger_config, is_active, created_at)
                VALUES (:name, :code, :description, :entity_type, :trigger_config, 0, NOW())
            ");
            $stmt->execute([
                'name' => $newName,
                'code' => $newCode,
                'description' => $source['description'],
                'entity_type' => $source['entity_type'],
                'trigger_config' => $source['trigger_config']
            ]);

            $newWorkflowId = $pdo->lastInsertId();

            // Clone steps
            $stmt = $pdo->prepare("SELECT * FROM workflow_steps WHERE workflow_id = :id ORDER BY sort_order");
            $stmt->execute(['id' => $workflowId]);
            $steps = $stmt->fetchAll();

            $stepIdMap = [];
            foreach ($steps as $step) {
                $oldId = $step['id'];
                unset($step['id']);
                $step['workflow_id'] = $newWorkflowId;
                $step['created_at'] = date('Y-m-d H:i:s');
                $step['updated_at'] = null;

                $columns = implode(', ', array_keys($step));
                $placeholders = ':' . implode(', :', array_keys($step));
                $stmt = $pdo->prepare("INSERT INTO workflow_steps ({$columns}) VALUES ({$placeholders})");
                $stmt->execute($step);

                $stepIdMap[$oldId] = $pdo->lastInsertId();
            }

            // Clone transitions with updated step IDs
            $stmt = $pdo->prepare("SELECT * FROM workflow_transitions WHERE workflow_id = :id");
            $stmt->execute(['id' => $workflowId]);
            $transitions = $stmt->fetchAll();

            foreach ($transitions as $trans) {
                unset($trans['id']);
                $trans['workflow_id'] = $newWorkflowId;
                $trans['from_step_id'] = $stepIdMap[$trans['from_step_id']] ?? null;
                $trans['to_step_id'] = $stepIdMap[$trans['to_step_id']] ?? null;
                $trans['created_at'] = date('Y-m-d H:i:s');

                if ($trans['from_step_id'] && $trans['to_step_id']) {
                    $columns = implode(', ', array_keys($trans));
                    $placeholders = ':' . implode(', :', array_keys($trans));
                    $stmt = $pdo->prepare("INSERT INTO workflow_transitions ({$columns}) VALUES ({$placeholders})");
                    $stmt->execute($trans);
                }
            }

            return Response::json(['success' => true, 'workflow_id' => $newWorkflowId, 'message' => 'Workflow cloned']);
        } catch (Exception $e) {
            return Response::json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Delete a workflow
     */
    public function deleteWorkflow()
    {
        if (!isAuthenticated()) {
            return Response::json(['success' => false, 'message' => 'Not authenticated']);
        }

        if (!hasPermission('Workflow.manage') && !Gate::hasRole('ADMIN')) {
            return Response::json(['success' => false, 'message' => 'Permission denied']);
        }

        $workflowId = (int)Request::post('workflow_id');

        try {
            $pdo = Database::getTenantConnection();

            // Check for active tickets
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM workflow_tickets WHERE workflow_id = :id AND status = 'active'");
            $stmt->execute(['id' => $workflowId]);
            if ($stmt->fetchColumn() > 0) {
                return Response::json(['success' => false, 'message' => 'Cannot delete workflow with active tickets']);
            }

            // Delete transitions, steps, then workflow
            $pdo->prepare("DELETE FROM workflow_transitions WHERE workflow_id = :id")->execute(['id' => $workflowId]);
            $pdo->prepare("DELETE FROM workflow_step_fields WHERE step_id IN (SELECT id FROM workflow_steps WHERE workflow_id = :id)")->execute(['id' => $workflowId]);
            $pdo->prepare("DELETE FROM workflow_steps WHERE workflow_id = :id")->execute(['id' => $workflowId]);
            $pdo->prepare("DELETE FROM workflows WHERE id = :id")->execute(['id' => $workflowId]);

            return Response::json(['success' => true, 'message' => 'Workflow deleted']);
        } catch (Exception $e) {
            return Response::json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Create workflow from template
     */
    public function createFromTemplate()
    {
        if (!isAuthenticated()) {
            return Response::json(['success' => false, 'message' => 'Not authenticated']);
        }

        if (!hasPermission('Workflow.manage') && !Gate::hasRole('ADMIN')) {
            return Response::json(['success' => false, 'message' => 'Permission denied']);
        }

        $template = Request::post('template');

        $templates = [
            'admissions' => [
                'name' => 'Admissions Workflow',
                'code' => 'ADMISSIONS',
                'entity_type' => 'applicant',
                'description' => 'Complete student admission process from application to enrollment.',
                'steps' => [
                    ['code' => 'START', 'name' => 'Start', 'type' => 'start', 'x' => 100, 'y' => 200],
                    ['code' => 'DOCUMENT_REVIEW', 'name' => 'Document Review', 'type' => 'task', 'role' => 'ADMISSIONS_OFFICER', 'x' => 250, 'y' => 200],
                    ['code' => 'SCREENING', 'name' => 'Screening/Interview', 'type' => 'task', 'role' => 'ADMISSIONS_OFFICER', 'x' => 450, 'y' => 200],
                    ['code' => 'DECISION', 'name' => 'Admission Decision', 'type' => 'decision', 'x' => 650, 'y' => 200],
                    ['code' => 'APPROVED', 'name' => 'Enrollment Processing', 'type' => 'task', 'role' => 'REGISTRAR', 'x' => 850, 'y' => 100],
                    ['code' => 'REJECTED', 'name' => 'Send Rejection', 'type' => 'auto', 'x' => 850, 'y' => 300],
                    ['code' => 'END', 'name' => 'End', 'type' => 'end', 'x' => 1050, 'y' => 200]
                ]
            ],
            'leave_request' => [
                'name' => 'Leave Request Workflow',
                'code' => 'LEAVE_REQUEST',
                'entity_type' => 'leave_request',
                'description' => 'Staff leave request approval workflow with manager review.',
                'steps' => [
                    ['code' => 'START', 'name' => 'Start', 'type' => 'start', 'x' => 100, 'y' => 200],
                    ['code' => 'MANAGER_REVIEW', 'name' => 'Manager Review', 'type' => 'task', 'role' => 'DEPARTMENT_HEAD', 'x' => 300, 'y' => 200],
                    ['code' => 'HR_REVIEW', 'name' => 'HR Review', 'type' => 'task', 'role' => 'HR_MANAGER', 'x' => 500, 'y' => 200],
                    ['code' => 'END', 'name' => 'End', 'type' => 'end', 'x' => 700, 'y' => 200]
                ]
            ],
            'fee_approval' => [
                'name' => 'Fee Approval Workflow',
                'code' => 'FEE_APPROVAL',
                'entity_type' => 'invoice',
                'description' => 'Fee waiver and scholarship approval workflow.',
                'steps' => [
                    ['code' => 'START', 'name' => 'Start', 'type' => 'start', 'x' => 100, 'y' => 200],
                    ['code' => 'BURSAR_REVIEW', 'name' => 'Bursar Review', 'type' => 'task', 'role' => 'BURSAR', 'x' => 300, 'y' => 200],
                    ['code' => 'PRINCIPAL_APPROVAL', 'name' => 'Principal Approval', 'type' => 'task', 'role' => 'PRINCIPAL', 'x' => 500, 'y' => 200],
                    ['code' => 'END', 'name' => 'End', 'type' => 'end', 'x' => 700, 'y' => 200]
                ]
            ]
        ];

        if (!isset($templates[$template])) {
            return Response::json(['success' => false, 'message' => 'Template not found']);
        }

        $tmpl = $templates[$template];

        try {
            $pdo = Database::getTenantConnection();

            // Check for duplicate code
            $stmt = $pdo->prepare("SELECT id FROM workflows WHERE code = :code");
            $stmt->execute(['code' => $tmpl['code']]);
            if ($stmt->fetch()) {
                $tmpl['code'] .= '_' . time();
            }

            // Create workflow
            $stmt = $pdo->prepare("
                INSERT INTO workflows (name, code, description, entity_type, is_active, created_at)
                VALUES (:name, :code, :description, :entity_type, 0, NOW())
            ");
            $stmt->execute([
                'name' => $tmpl['name'],
                'code' => $tmpl['code'],
                'description' => $tmpl['description'],
                'entity_type' => $tmpl['entity_type']
            ]);

            $workflowId = $pdo->lastInsertId();

            // Create steps
            $sortOrder = 1;
            foreach ($tmpl['steps'] as $step) {
                $stmt = $pdo->prepare("
                    INSERT INTO workflow_steps (
                        workflow_id, code, name, step_type, actor_roles,
                        sort_order, ui_position_x, ui_position_y, is_active, created_at
                    ) VALUES (
                        :wf_id, :code, :name, :type, :roles,
                        :sort, :x, :y, 1, NOW()
                    )
                ");
                $stmt->execute([
                    'wf_id' => $workflowId,
                    'code' => $step['code'],
                    'name' => $step['name'],
                    'type' => $step['type'],
                    'roles' => isset($step['role']) ? json_encode([$step['role']]) : null,
                    'sort' => $sortOrder++,
                    'x' => $step['x'],
                    'y' => $step['y']
                ]);
            }

            return Response::json(['success' => true, 'workflow_id' => $workflowId, 'message' => 'Workflow created from template']);
        } catch (Exception $e) {
            return Response::json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * View/Edit a workflow (admin) - Visual Designer
     */
    public function editWorkflow($workflowId)
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        if (!hasPermission('Workflow.manage') && !Gate::hasRole('ADMIN')) {
            flash('error', 'Permission denied');
            Response::redirect('/dashboard');
        }

        $workflow = new Workflow((int)$workflowId);
        if (!$workflow->id) {
            flash('error', 'Workflow not found');
            Response::redirect('/workflow/admin');
        }

        Response::view('workflow.admin.designer', [
            'workflow' => $workflow,
            'steps' => $workflow->getSteps(),
            'transitions' => $workflow->getTransitions(),
            'ticketCounts' => $workflow->getTicketCounts(),
            'validationErrors' => $workflow->validate(),
            'breadcrumbs' => [
                ['label' => 'Settings', 'url' => '#'],
                ['label' => 'Workflows', 'url' => '/workflow/admin'],
                ['label' => $workflow->name, 'url' => "/workflow/admin/{$workflowId}"]
            ]
        ]);
    }

    /**
     * Get workflow data as JSON (for designer)
     */
    public function getWorkflowJson($workflowId)
    {
        if (!isAuthenticated()) {
            return Response::json(['success' => false, 'message' => 'Not authenticated']);
        }

        $workflow = new Workflow((int)$workflowId);
        if (!$workflow->id) {
            return Response::json(['success' => false, 'message' => 'Workflow not found']);
        }

        return Response::json([
            'success' => true,
            'data' => $workflow->export()
        ]);
    }

    /**
     * Save workflow from designer (full data including steps and transitions)
     */
    public function saveWorkflow()
    {
        if (!isAuthenticated()) {
            return Response::json(['success' => false, 'message' => 'Not authenticated']);
        }

        if (!hasPermission('Workflow.manage') && !Gate::hasRole('ADMIN')) {
            return Response::json(['success' => false, 'message' => 'Permission denied']);
        }

        $workflowId = (int)Request::post('workflow_id');
        $data = Request::post('data');

        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        if (!$data) {
            return Response::json(['success' => false, 'message' => 'Invalid workflow data']);
        }

        $pdo = Database::getTenantConnection();
        $pdo->beginTransaction();

        try {
            $workflow = new Workflow($workflowId);
            if (!$workflow->id) {
                return Response::json(['success' => false, 'message' => 'Workflow not found']);
            }

            // Update workflow properties
            if (isset($data['workflow'])) {
                $workflow->update($data['workflow']);
            }

            // Build step code -> existing ID map for reference
            $existingSteps = $workflow->getSteps();
            $existingStepMap = [];
            foreach ($existingSteps as $step) {
                $existingStepMap[$step['code']] = $step['id'];
            }

            // Process steps from designer
            $stepCodeToId = [];       // Maps step code -> database ID
            $jsIdToCode = [];         // Maps JS step ID (step_xxx) -> step code
            $processedStepCodes = [];

            if (!empty($data['steps'])) {
                foreach ($data['steps'] as $stepData) {
                    $code = $stepData['code'] ?? null;
                    if (!$code) continue;

                    $processedStepCodes[] = $code;

                    // Track JS ID to code mapping for transitions
                    if (!empty($stepData['id'])) {
                        $jsIdToCode[$stepData['id']] = $code;
                    }

                    // Parse escalation config
                    $escalationEnabled = 0;
                    $escalationHours = null;
                    $escalationRole = null;
                    if (!empty($stepData['escalation'])) {
                        $esc = $stepData['escalation'];
                        $escalationEnabled = !empty($esc['enabled']) ? 1 : 0;
                        $escalationHours = $esc['hours'] ?? null;
                        $escalationRole = $esc['role'] ?? null;
                    }

                    // Parse auto-transition config
                    $autoTransitionEnabled = 0;
                    $autoTransitionHours = null;
                    $autoTransitionAction = null;
                    $autoTransitionComment = null;
                    if (!empty($stepData['auto_transition'])) {
                        $auto = $stepData['auto_transition'];
                        $autoTransitionEnabled = !empty($auto['enabled']) ? 1 : 0;
                        $autoTransitionHours = $auto['hours'] ?? null;
                        $autoTransitionAction = $auto['action'] ?? null;
                        $autoTransitionComment = $auto['comment'] ?? null;
                    }

                    // Parse reminder config
                    $reminderEnabled = 0;
                    $reminderHours = $stepData['reminder_hours'] ?? null;
                    if ($reminderHours) {
                        $reminderEnabled = 1;
                    }

                    // Prepare step data matching actual table columns
                    $stepRecord = [
                        'workflow_id' => $workflowId,
                        'code' => $code,
                        'name' => $stepData['name'] ?? $code,
                        'step_type' => $stepData['type'] ?? 'task',
                        'description' => $stepData['description'] ?? null,
                        'icon' => $stepData['icon'] ?? null,
                        'actor_roles' => isset($stepData['roles']) ? (is_array($stepData['roles']) ? json_encode($stepData['roles']) : $stepData['roles']) : null,
                        'assignment_mode' => $stepData['assignment_mode'] ?? 'any',
                        'available_actions' => isset($stepData['actions']) ? json_encode($stepData['actions']) : null,
                        'sla_hours' => $stepData['sla_hours'] ?? null,
                        'reminder_enabled' => $reminderEnabled,
                        'reminder_hours' => $reminderHours,
                        'escalation_enabled' => $escalationEnabled,
                        'escalation_hours' => $escalationHours,
                        'escalation_role' => $escalationRole,
                        'auto_transition_enabled' => $autoTransitionEnabled,
                        'auto_transition_hours' => $autoTransitionHours,
                        'auto_transition_action' => $autoTransitionAction,
                        'auto_transition_comment' => $autoTransitionComment,
                        'ui_position_x' => $stepData['x'] ?? 0,
                        'ui_position_y' => $stepData['y'] ?? 0,
                        'sort_order' => $stepData['sort'] ?? 0
                    ];

                    if (isset($existingStepMap[$code])) {
                        // Update existing step
                        $stepId = $existingStepMap[$code];
                        $updateFields = [];
                        $updateParams = ['id' => $stepId];

                        foreach ($stepRecord as $field => $value) {
                            if ($field === 'workflow_id' || $field === 'code') continue;
                            $updateFields[] = "{$field} = :{$field}";
                            $updateParams[$field] = $value;
                        }
                        $updateFields[] = "updated_at = NOW()";

                        $stmt = $pdo->prepare("UPDATE workflow_steps SET " . implode(', ', $updateFields) . " WHERE id = :id");
                        $stmt->execute($updateParams);

                        $stepCodeToId[$code] = $stepId;
                    } else {
                        // Insert new step
                        $stmt = $pdo->prepare("
                            INSERT INTO workflow_steps (
                                workflow_id, code, name, step_type, description, icon,
                                actor_roles, assignment_mode, available_actions,
                                sla_hours, reminder_enabled, reminder_hours,
                                escalation_enabled, escalation_hours, escalation_role,
                                auto_transition_enabled, auto_transition_hours, auto_transition_action, auto_transition_comment,
                                ui_position_x, ui_position_y, sort_order, is_active, created_at
                            ) VALUES (
                                :workflow_id, :code, :name, :step_type, :description, :icon,
                                :actor_roles, :assignment_mode, :available_actions,
                                :sla_hours, :reminder_enabled, :reminder_hours,
                                :escalation_enabled, :escalation_hours, :escalation_role,
                                :auto_transition_enabled, :auto_transition_hours, :auto_transition_action, :auto_transition_comment,
                                :ui_position_x, :ui_position_y, :sort_order, 1, NOW()
                            )
                        ");
                        $stmt->execute($stepRecord);
                        $stepCodeToId[$code] = $pdo->lastInsertId();
                    }
                }
            }

            // Remove steps that are no longer in the designer
            foreach ($existingStepMap as $code => $stepId) {
                if (!in_array($code, $processedStepCodes)) {
                    // Soft delete by setting is_active = 0
                    $stmt = $pdo->prepare("UPDATE workflow_steps SET is_active = 0, updated_at = NOW() WHERE id = :id");
                    $stmt->execute(['id' => $stepId]);
                }
            }

            // Clear existing transitions and recreate
            $stmt = $pdo->prepare("DELETE FROM workflow_transitions WHERE workflow_id = :workflow_id");
            $stmt->execute(['workflow_id' => $workflowId]);

            // Process transitions from designer
            if (!empty($data['transitions'])) {
                foreach ($data['transitions'] as $trans) {
                    // Get from/to identifiers (could be JS step ID or step code)
                    $fromId = $trans['from_step_id'] ?? $trans['from'] ?? $trans['from_step_code'] ?? null;
                    $toId = $trans['to_step_id'] ?? $trans['to'] ?? $trans['to_step_code'] ?? null;

                    if (!$fromId || !$toId) continue;

                    // Convert JS IDs to codes if needed, then to database IDs
                    $fromCode = $jsIdToCode[$fromId] ?? $fromId;
                    $toCode = $jsIdToCode[$toId] ?? $toId;

                    $fromStepId = $stepCodeToId[$fromCode] ?? null;
                    $toStepId = $stepCodeToId[$toCode] ?? null;

                    if (!$fromStepId || !$toStepId) continue;

                    $stmt = $pdo->prepare("
                        INSERT INTO workflow_transitions (
                            workflow_id, from_step_id, to_step_id, action_code,
                            condition_config, label, priority, created_at
                        ) VALUES (
                            :workflow_id, :from_step_id, :to_step_id, :action_code,
                            :condition_config, :label, :priority, NOW()
                        )
                    ");
                    $stmt->execute([
                        'workflow_id' => $workflowId,
                        'from_step_id' => $fromStepId,
                        'to_step_id' => $toStepId,
                        'action_code' => $trans['action_code'] ?? $trans['action'] ?? null,
                        'condition_config' => isset($trans['condition']) ? json_encode($trans['condition']) : null,
                        'label' => $trans['label'] ?? null,
                        'priority' => $trans['priority'] ?? 0
                    ]);
                }
            }

            $pdo->commit();

            return Response::json(['success' => true, 'message' => 'Workflow saved successfully']);
        } catch (Exception $e) {
            $pdo->rollBack();
            return Response::json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // =========================================================================
    // API ENDPOINTS
    // =========================================================================

    /**
     * Get available workflows for entity type (API)
     */
    public function getAvailableWorkflows()
    {
        if (!isAuthenticated()) {
            return Response::json(['success' => false, 'message' => 'Not authenticated']);
        }

        $entityType = Request::get('entity_type');
        $workflows = $this->engine->getAvailableWorkflows($entityType);

        return Response::json(['success' => true, 'workflows' => $workflows]);
    }

    /**
     * Get task counts for dashboard widget
     */
    public function getTaskCounts()
    {
        if (!isAuthenticated()) {
            return Response::json(['success' => false, 'message' => 'Not authenticated']);
        }

        $userId = $_SESSION['user_id'];
        $counts = WorkflowTask::getCountsForUser($userId);

        return Response::json(['success' => true, 'counts' => $counts]);
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Check if user can access a task
     */
    private function canUserAccessTask(int $userId, WorkflowTask $task): bool
    {
        // Direct assignment
        if ($task->assigned_user_id == $userId || $task->claimed_by_user_id == $userId) {
            return true;
        }

        // Role-based access
        if ($task->assigned_role) {
            $pdo = Database::getTenantConnection();
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM user_roles ur
                JOIN roles r ON r.id = ur.role_id
                WHERE ur.user_id = :user_id AND r.name = :role
            ");
            $stmt->execute(['user_id' => $userId, 'role' => $task->assigned_role]);
            return $stmt->fetchColumn() > 0;
        }

        // Admin access
        return Gate::hasRole('ADMIN');
    }
}
