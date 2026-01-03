<?php
/**
 * Task Inbox Content (My Tasks)
 */
?>

<!-- Page Header -->
<div class="row mb-3 align-items-center">
    <div class="col">
        <h2 class="page-title">
            <i class="ti ti-checkbox me-2"></i> My Tasks
        </h2>
    </div>
    <div class="col-auto">
        <a href="/tasks/escalations" class="btn btn-outline-warning">
            <i class="ti ti-alert-triangle me-1"></i>
            Escalations
            <?php if (($counts['overdue'] ?? 0) > 0): ?>
            <span class="badge bg-danger ms-1"><?= $counts['overdue'] ?></span>
            <?php endif; ?>
        </a>
    </div>
</div>

<!-- Status Overview Cards (Tabler Style) -->
<div class="row row-deck row-cards mb-4">
    <div class="col-md-4">
        <div class="card stats-card border-info">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Pending Tasks</div>
                </div>
                <div class="h1 mb-3"><?= $counts['pending'] ?? 0 ?></div>
                <a href="/tasks?status=pending" class="text-muted">
                    View All <i class="ti ti-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stats-card border-primary">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Claimed by Me</div>
                </div>
                <div class="h1 mb-3"><?= $counts['claimed'] ?? 0 ?></div>
                <a href="/tasks?status=claimed" class="text-muted">
                    View All <i class="ti ti-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stats-card border-danger">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Overdue</div>
                </div>
                <div class="h1 mb-3"><?= $counts['overdue'] ?? 0 ?></div>
                <a href="/tasks?status=overdue" class="text-muted">
                    View All <i class="ti ti-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="/tasks" class="row align-items-center g-2">
            <div class="col-auto">
                <label class="form-label mb-0 me-2">Filter:</label>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="claimed" <?= $statusFilter === 'claimed' ? 'selected' : '' ?>>Claimed</option>
                    <option value="in_progress" <?= $statusFilter === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="priority" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Priorities</option>
                    <option value="urgent" <?= $priorityFilter === 'urgent' ? 'selected' : '' ?>>Urgent</option>
                    <option value="high" <?= $priorityFilter === 'high' ? 'selected' : '' ?>>High</option>
                    <option value="normal" <?= $priorityFilter === 'normal' ? 'selected' : '' ?>>Normal</option>
                    <option value="low" <?= $priorityFilter === 'low' ? 'selected' : '' ?>>Low</option>
                </select>
            </div>
            <?php if (!empty($statusFilter) || !empty($priorityFilter)): ?>
            <div class="col-auto">
                <a href="/tasks" class="btn btn-sm btn-outline-secondary">
                    <i class="ti ti-x me-1"></i> Clear Filters
                </a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Tasks List -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="ti ti-list me-2"></i>
            Tasks
            <span class="text-muted ms-2">(<?= count($tasks) ?> total)</span>
        </h3>
    </div>
    <div class="card-body p-0">
        <?php if (empty($tasks)): ?>
        <div class="text-center py-5">
            <i class="ti ti-circle-check text-success mb-3" style="font-size: 4rem;"></i>
            <h4 class="text-muted">No pending tasks</h4>
            <p class="text-muted">You're all caught up!</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead>
                    <tr>
                        <th style="width: 40px;"></th>
                        <th>Task</th>
                        <th>Workflow</th>
                        <th>Entity</th>
                        <th>Status</th>
                        <th>Due</th>
                        <th>Priority</th>
                        <th style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $task): ?>
                    <?php
                        $isOverdue = !empty($task['due_at']) && strtotime($task['due_at']) < time() && $task['status'] !== 'completed';
                        $isClaimed = $task['claimed_by_user_id'] == ($_SESSION['user_id'] ?? 0);
                    ?>
                    <tr class="<?= $isOverdue ? 'table-danger' : '' ?>">
                        <td class="text-center">
                            <?php if ($isOverdue): ?>
                            <i class="ti ti-alert-triangle text-danger" title="Overdue"></i>
                            <?php elseif ($task['priority'] === 'urgent'): ?>
                            <i class="ti ti-flame text-danger" title="Urgent"></i>
                            <?php elseif ($task['priority'] === 'high'): ?>
                            <i class="ti ti-arrow-up text-warning" title="High Priority"></i>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="/tasks/<?= $task['id'] ?>" class="fw-bold text-dark">
                                <?= e($task['task_number']) ?>
                            </a>
                            <br>
                            <small class="text-muted"><?= e($task['step_name']) ?></small>
                        </td>
                        <td>
                            <span class="badge bg-info"><?= e($task['workflow_name']) ?></span>
                            <br>
                            <small class="text-muted">
                                <a href="/workflow/tickets/<?= $task['ticket_id'] ?>" class="text-muted">
                                    <?= e($task['ticket_number']) ?>
                                </a>
                            </small>
                        </td>
                        <td>
                            <?php
                            $entityUrl = '#';
                            if ($task['entity_type'] === 'applicant') {
                                $entityUrl = "/applicants/{$task['entity_id']}";
                            } elseif ($task['entity_type'] === 'student') {
                                $entityUrl = "/students/{$task['entity_id']}";
                            }
                            ?>
                            <a href="<?= $entityUrl ?>" class="text-dark">
                                <?= ucfirst($task['entity_type']) ?> #<?= $task['entity_id'] ?>
                            </a>
                        </td>
                        <td>
                            <?php
                            $statusClasses = [
                                'pending' => 'bg-secondary',
                                'claimed' => 'bg-primary',
                                'in_progress' => 'bg-info',
                                'completed' => 'bg-success',
                                'skipped' => 'bg-light text-dark',
                                'cancelled' => 'bg-dark',
                                'escalated' => 'bg-warning'
                            ];
                            $statusClass = $statusClasses[$task['status']] ?? 'bg-secondary';
                            ?>
                            <span class="badge <?= $statusClass ?>">
                                <?= ucfirst(str_replace('_', ' ', $task['status'])) ?>
                            </span>
                            <?php if ($isClaimed): ?>
                            <br><small class="text-primary"><i class="ti ti-hand-stop"></i> Claimed by you</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($task['due_at'])): ?>
                            <?php
                            $dueDate = strtotime($task['due_at']);
                            $now = time();
                            $diff = $dueDate - $now;
                            $diffHours = round($diff / 3600);
                            $diffDays = round($diff / 86400);

                            if ($diff < 0) {
                                $dueClass = 'text-danger font-weight-bold';
                                $dueText = abs($diffHours) < 24 ? abs($diffHours) . 'h overdue' : abs($diffDays) . 'd overdue';
                            } elseif ($diffHours < 24) {
                                $dueClass = 'text-warning';
                                $dueText = $diffHours . 'h remaining';
                            } else {
                                $dueClass = 'text-muted';
                                $dueText = $diffDays . ' days';
                            }
                            ?>
                            <span class="<?= $dueClass ?>">
                                <i class="far fa-clock mr-1"></i>
                                <?= $dueText ?>
                            </span>
                            <br>
                            <small class="text-muted"><?= date('M j, g:i A', $dueDate) ?></small>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $priorityClasses = [
                                'urgent' => 'badge-danger',
                                'high' => 'badge-warning',
                                'normal' => 'badge-info',
                                'low' => 'badge-secondary'
                            ];
                            $priorityClass = $priorityClasses[$task['priority']] ?? 'badge-secondary';
                            ?>
                            <span class="badge <?= $priorityClass ?>">
                                <?= ucfirst($task['priority']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="/tasks/<?= $task['id'] ?>" class="btn btn-sm btn-primary" title="View Task">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($task['status'] === 'pending' && !$isClaimed): ?>
                                <button type="button" class="btn btn-sm btn-success" onclick="claimTask(<?= $task['id'] ?>)" title="Claim Task">
                                    <i class="fas fa-hand-paper"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function claimTask(taskId) {
    if (!confirm('Claim this task? You will be responsible for completing it.')) return;

    fetch('/tasks/claim', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'task_id=' + taskId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = '/tasks/' + taskId;
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to claim task. Please try again.');
    });
}
</script>
