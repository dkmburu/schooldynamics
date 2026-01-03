<?php
/**
 * Workflow Ticket Detail View Content
 */

$isActive = $ticket->status === 'active';
$isCompleted = $ticket->status === 'completed';
$isPaused = $ticket->status === 'paused';
?>

<!-- Page Header -->
<div class="row mb-3">
    <div class="col">
        <h2 class="page-title">
            <i class="fas fa-ticket-alt mr-2"></i>
            Ticket: <?= e($ticket->ticket_number) ?>
            <?php
            $statusBadges = [
                'active' => 'badge-success',
                'completed' => 'badge-primary',
                'cancelled' => 'badge-dark',
                'failed' => 'badge-danger',
                'paused' => 'badge-warning'
            ];
            ?>
            <span class="badge <?= $statusBadges[$ticket->status] ?? 'badge-secondary' ?> ml-2">
                <?= ucfirst($ticket->status) ?>
            </span>
        </h2>
        <p class="text-muted mb-0">
            <span class="badge badge-info"><?= e($workflow['name']) ?></span>
            <span class="mx-2">|</span>
            Started: <?= date('M j, Y g:i A', strtotime($ticket->started_at)) ?>
            <?php if ($ticket->completed_at): ?>
            <span class="mx-2">|</span>
            Completed: <?= date('M j, Y g:i A', strtotime($ticket->completed_at)) ?>
            <?php endif; ?>
        </p>
    </div>
    <div class="col-auto">
        <div class="btn-group">
            <a href="/tasks" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Back to Tasks
            </a>
            <?php if ($isActive): ?>
            <button type="button" class="btn btn-warning" onclick="pauseTicket(<?= $ticket->id ?>)">
                <i class="fas fa-pause mr-1"></i> Pause
            </button>
            <button type="button" class="btn btn-danger" onclick="showCancelModal()">
                <i class="fas fa-times mr-1"></i> Cancel
            </button>
            <?php elseif ($isPaused): ?>
            <button type="button" class="btn btn-success" onclick="resumeTicket(<?= $ticket->id ?>)">
                <i class="fas fa-play mr-1"></i> Resume
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row">
    <!-- Main Content -->
    <div class="col-lg-8">
        <!-- Entity Info Card -->
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="fas fa-link mr-2"></i>
                    <?= ucfirst($ticket->entity_type) ?> Information
                </h3>
            </div>
            <div class="card-body">
                <?php if ($entity): ?>
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="avatar avatar-lg bg-primary text-white">
                            <?= strtoupper(substr($entityDisplayName, 0, 2)) ?>
                        </div>
                    </div>
                    <div class="col">
                        <h4 class="mb-1"><?= e($entityDisplayName) ?></h4>
                        <p class="text-muted mb-0">
                            <?= ucfirst($ticket->entity_type) ?> ID: <?= $ticket->entity_id ?>
                        </p>
                    </div>
                    <div class="col-auto">
                        <?php
                        $entityUrl = '#';
                        if ($ticket->entity_type === 'applicant') {
                            $entityUrl = "/applicants/{$ticket->entity_id}";
                        } elseif ($ticket->entity_type === 'student') {
                            $entityUrl = "/students/{$ticket->entity_id}";
                        }
                        ?>
                        <a href="<?= $entityUrl ?>" class="btn btn-outline-primary" target="_blank">
                            <i class="fas fa-external-link-alt mr-1"></i> View Profile
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <p class="text-muted mb-0">Entity not found</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Active Tasks -->
        <?php if (!empty($activeTasks)): ?>
        <div class="card mb-3">
            <div class="card-header bg-warning">
                <h3 class="card-title mb-0">
                    <i class="fas fa-tasks mr-2"></i>
                    Active Tasks (<?= count($activeTasks) ?>)
                </h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Task</th>
                                <th>Step</th>
                                <th>Assigned To</th>
                                <th>Status</th>
                                <th>Due</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activeTasks as $t): ?>
                            <tr>
                                <td>
                                    <a href="/tasks/<?= $t['id'] ?>" class="font-weight-bold">
                                        <?= e($t['task_number']) ?>
                                    </a>
                                </td>
                                <td><?= e($t['step_name']) ?></td>
                                <td>
                                    <?php if ($t['assigned_role']): ?>
                                    <span class="badge badge-outline-primary"><?= e($t['assigned_role']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $t['status'] === 'claimed' ? 'primary' : 'secondary' ?>">
                                        <?= ucfirst($t['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($t['due_at']): ?>
                                    <?= date('M j, g:i A', strtotime($t['due_at'])) ?>
                                    <?php else: ?>
                                    -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="/tasks/<?= $t['id'] ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Outcome (if completed) -->
        <?php if ($isCompleted && $ticket->outcome): ?>
        <div class="card mb-3">
            <div class="card-header bg-<?= $ticket->outcome === 'approved' ? 'success' : ($ticket->outcome === 'rejected' ? 'danger' : 'info') ?> text-white">
                <h3 class="card-title mb-0">
                    <i class="fas fa-flag-checkered mr-2"></i>
                    Outcome: <?= ucfirst($ticket->outcome) ?>
                </h3>
            </div>
            <?php if ($ticket->outcome_notes): ?>
            <div class="card-body">
                <?= nl2br(e($ticket->outcome_notes)) ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Timeline -->
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="fas fa-stream mr-2"></i>
                    Timeline
                </h3>
            </div>
            <div class="card-body">
                <?php if (empty($timeline)): ?>
                <p class="text-muted mb-0">No events yet</p>
                <?php else: ?>
                <div class="timeline-vertical">
                    <?php foreach ($timeline as $event): ?>
                    <?php
                    $iconClass = 'fas fa-circle';
                    $bgClass = 'bg-secondary';

                    if ($event['event_type'] === 'ticket_created') {
                        $iconClass = 'fas fa-play-circle';
                        $bgClass = 'bg-success';
                    } elseif ($event['event_type'] === 'ticket_completed') {
                        $iconClass = 'fas fa-check-circle';
                        $bgClass = 'bg-primary';
                    } elseif ($event['event_type'] === 'task_created') {
                        $iconClass = 'fas fa-plus-circle';
                        $bgClass = 'bg-info';
                    } elseif ($event['event_type'] === 'task_completed') {
                        $iconClass = 'fas fa-check';
                        $bgClass = 'bg-success';
                    } elseif ($event['event_type'] === 'task_claimed') {
                        $iconClass = 'fas fa-hand-paper';
                        $bgClass = 'bg-primary';
                    } elseif ($event['event_type'] === 'transition') {
                        $iconClass = 'fas fa-arrow-right';
                        $bgClass = 'bg-info';
                    } elseif ($event['event_type'] === 'comment') {
                        $iconClass = 'fas fa-comment';
                        $bgClass = 'bg-warning';
                    } elseif ($event['event_type'] === 'task_escalated') {
                        $iconClass = 'fas fa-exclamation-triangle';
                        $bgClass = 'bg-danger';
                    }
                    ?>
                    <div class="timeline-item pb-3">
                        <div class="timeline-icon <?= $bgClass ?>">
                            <i class="<?= $iconClass ?> text-white"></i>
                        </div>
                        <div class="timeline-content ml-3">
                            <div class="d-flex justify-content-between">
                                <strong><?= e($event['actor_name'] ?? 'System') ?></strong>
                                <small class="text-muted"><?= date('M j, g:i A', strtotime($event['timestamp'])) ?></small>
                            </div>
                            <p class="mb-0"><?= e($event['description']) ?></p>
                            <?php if ($event['step_name']): ?>
                            <small class="text-muted">Step: <?= e($event['step_name']) ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- All Tasks -->
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="fas fa-list mr-2"></i>
                    All Tasks (<?= count($tasks) ?>)
                </h3>
            </div>
            <div class="card-body p-0">
                <?php if (empty($tasks)): ?>
                <p class="text-muted p-3 mb-0">No tasks created yet</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Task</th>
                                <th>Step</th>
                                <th>Status</th>
                                <th>Action</th>
                                <th>Acted By</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tasks as $t): ?>
                            <?php
                            $rowClass = '';
                            if ($t['status'] === 'completed') $rowClass = 'table-success';
                            elseif ($t['status'] === 'cancelled' || $t['status'] === 'skipped') $rowClass = 'table-secondary';
                            ?>
                            <tr class="<?= $rowClass ?>">
                                <td>
                                    <a href="/tasks/<?= $t['id'] ?>"><?= e($t['task_number']) ?></a>
                                </td>
                                <td><?= e($t['step_name']) ?></td>
                                <td>
                                    <?php
                                    $statusClasses = [
                                        'pending' => 'badge-secondary',
                                        'claimed' => 'badge-primary',
                                        'in_progress' => 'badge-info',
                                        'completed' => 'badge-success',
                                        'skipped' => 'badge-light',
                                        'cancelled' => 'badge-dark'
                                    ];
                                    ?>
                                    <span class="badge <?= $statusClasses[$t['status']] ?? 'badge-secondary' ?>">
                                        <?= ucfirst($t['status']) ?>
                                    </span>
                                </td>
                                <td><?= e($t['action_label'] ?? $t['action_code'] ?? '-') ?></td>
                                <td>
                                    <?php if ($t['acted_by_user_id']): ?>
                                    User #<?= $t['acted_by_user_id'] ?>
                                    <?php else: ?>
                                    -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= $t['acted_at'] ? date('M j, g:i A', strtotime($t['acted_at'])) : '-' ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Ticket Info -->
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="fas fa-info-circle mr-2"></i>
                    Ticket Details
                </h3>
            </div>
            <div class="card-body">
                <dl class="mb-0">
                    <dt>Workflow</dt>
                    <dd><?= e($workflow['name']) ?></dd>

                    <dt>Priority</dt>
                    <dd>
                        <?php
                        $priorityClasses = [
                            'urgent' => 'badge-danger',
                            'high' => 'badge-warning',
                            'normal' => 'badge-info',
                            'low' => 'badge-secondary'
                        ];
                        ?>
                        <span class="badge <?= $priorityClasses[$ticket->priority] ?? 'badge-secondary' ?>">
                            <?= ucfirst($ticket->priority) ?>
                        </span>
                    </dd>

                    <dt>Started</dt>
                    <dd><?= date('M j, Y g:i A', strtotime($ticket->started_at)) ?></dd>

                    <?php if ($ticket->completed_at): ?>
                    <dt>Completed</dt>
                    <dd><?= date('M j, Y g:i A', strtotime($ticket->completed_at)) ?></dd>

                    <dt>Duration</dt>
                    <dd><?= $ticket->getDurationFormatted() ?></dd>
                    <?php endif; ?>

                    <dt>Started By</dt>
                    <dd><?= $ticket->started_by_type === 'system' ? 'System (Auto)' : 'User #' . $ticket->started_by ?></dd>
                </dl>
            </div>
        </div>

        <!-- Sub-workflows -->
        <?php if (!empty($subWorkflows)): ?>
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="fas fa-sitemap mr-2"></i>
                    Sub-Workflows
                </h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($subWorkflows as $sub): ?>
                    <li class="list-group-item">
                        <a href="/workflow/tickets/<?= $sub['child_ticket_id'] ?>">
                            <?= e($sub['child_ticket_number']) ?>
                        </a>
                        <br>
                        <small class="text-muted"><?= e($sub['child_workflow_name']) ?></small>
                        <span class="badge badge-<?= $sub['child_status'] === 'completed' ? 'success' : 'warning' ?> float-right">
                            <?= ucfirst($sub['child_status']) ?>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <!-- Parent workflow (if this is a sub-workflow) -->
        <?php if ($parentTicket): ?>
        <div class="card mb-3">
            <div class="card-header bg-info text-white">
                <h3 class="card-title mb-0">
                    <i class="fas fa-level-up-alt mr-2"></i>
                    Parent Workflow
                </h3>
            </div>
            <div class="card-body">
                <a href="/workflow/tickets/<?= $parentTicket['parent_ticket_id'] ?>" class="font-weight-bold">
                    <?= e($parentTicket['parent_ticket_number']) ?>
                </a>
                <br>
                <small class="text-muted"><?= e($parentTicket['parent_workflow_name']) ?></small>
            </div>
        </div>
        <?php endif; ?>

        <!-- Comments -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">
                    <i class="fas fa-comments mr-2"></i>
                    Comments (<?= count($comments) ?>)
                </h3>
            </div>
            <div class="card-body">
                <?php if (empty($comments)): ?>
                <p class="text-muted mb-3">No comments yet</p>
                <?php else: ?>
                <div class="comments-list mb-3" style="max-height: 300px; overflow-y: auto;">
                    <?php foreach ($comments as $c): ?>
                    <div class="comment mb-3 pb-2 border-bottom">
                        <div class="d-flex justify-content-between">
                            <strong><?= e($c['user_name']) ?></strong>
                            <small class="text-muted"><?= date('M j, g:i A', strtotime($c['created_at'])) ?></small>
                        </div>
                        <p class="mb-0"><?= nl2br(e($c['comment'])) ?></p>
                        <?php if ($c['is_internal']): ?>
                        <small class="badge badge-light">Internal</small>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Add Comment Form -->
                <form method="POST" action="/workflow/comment">
                    <input type="hidden" name="ticket_id" value="<?= $ticket->id ?>">
                    <div class="mb-2">
                        <textarea class="form-control" name="comment" rows="2" placeholder="Add a comment..." required></textarea>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="is_internal" id="is_internal" value="1">
                            <label class="form-check-label" for="is_internal">
                                <small>Internal only</small>
                            </label>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="fas fa-paper-plane mr-1"></i> Post
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Attachments -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="fas fa-paperclip mr-2"></i>
                    Attachments (<?= count($attachments) ?>)
                </h3>
            </div>
            <div class="card-body">
                <?php if (empty($attachments)): ?>
                <p class="text-muted mb-0">No attachments</p>
                <?php else: ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($attachments as $att): ?>
                    <li class="mb-2">
                        <i class="fas fa-file mr-2"></i>
                        <a href="/download/workflow_attachment/<?= $att['id'] ?>">
                            <?= e($att['file_name']) ?>
                        </a>
                        <br>
                        <small class="text-muted">
                            <?= e($att['first_name'] ?? '') ?> - <?= date('M j', strtotime($att['uploaded_at'])) ?>
                        </small>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Ticket Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Cancel Ticket</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form method="POST" action="/workflow/cancel">
                <div class="modal-body">
                    <input type="hidden" name="ticket_id" value="<?= $ticket->id ?>">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        This will cancel the workflow and all pending tasks. This action cannot be undone.
                    </div>
                    <div class="form-group">
                        <label for="cancel_reason">Reason for cancellation <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="reason" id="cancel_reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Cancel Ticket</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showCancelModal() {
    $('#cancelModal').modal('show');
}

function pauseTicket(ticketId) {
    if (!confirm('Pause this ticket? All active tasks will remain but no new tasks will be created.')) return;

    fetch('/workflow/pause', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'ticket_id=' + ticketId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

function resumeTicket(ticketId) {
    if (!confirm('Resume this ticket?')) return;

    fetch('/workflow/resume', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'ticket_id=' + ticketId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}
</script>

<style>
.timeline-vertical {
    position: relative;
}
.timeline-item {
    display: flex;
    position: relative;
}
.timeline-item:not(:last-child):before {
    content: '';
    position: absolute;
    left: 15px;
    top: 35px;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}
.timeline-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.timeline-icon i {
    font-size: 12px;
}
.timeline-content {
    flex: 1;
    padding-bottom: 10px;
}
.avatar {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-weight: bold;
}
.avatar-lg {
    width: 64px;
    height: 64px;
    font-size: 1.5rem;
}
</style>
