<?php
/**
 * Escalations View Content (For Supervisors)
 */
?>

<!-- Page Header -->
<div class="row mb-3">
    <div class="col">
        <h2 class="page-title">
            <i class="fas fa-exclamation-triangle text-warning mr-2"></i>
            Escalations
        </h2>
        <p class="text-muted mb-0">
            Tasks that have been escalated to your attention due to SLA breaches or manual escalation.
        </p>
    </div>
    <div class="col-auto">
        <a href="/tasks" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Back to My Tasks
        </a>
    </div>
</div>

<!-- Escalations List -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title mb-0">
            <i class="fas fa-list mr-2"></i>
            Pending Escalations
            <span class="badge badge-warning ml-2"><?= count($escalations) ?></span>
        </h3>
    </div>
    <div class="card-body p-0">
        <?php if (empty($escalations)): ?>
        <div class="text-center py-5">
            <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
            <h4 class="text-muted">No pending escalations</h4>
            <p class="text-muted">All escalations have been handled.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th style="width: 40px;"></th>
                        <th>Task</th>
                        <th>Workflow</th>
                        <th>Entity</th>
                        <th>Original Assignee</th>
                        <th>Reason</th>
                        <th>Escalated</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($escalations as $esc): ?>
                    <tr>
                        <td class="text-center">
                            <i class="fas fa-exclamation-triangle text-danger"></i>
                        </td>
                        <td>
                            <a href="/tasks/<?= $esc['task_id'] ?>" class="font-weight-bold">
                                <?= e($esc['task_number']) ?>
                            </a>
                            <br>
                            <small class="text-muted"><?= e($esc['step_name']) ?></small>
                        </td>
                        <td>
                            <span class="badge badge-info"><?= e($esc['workflow_name']) ?></span>
                            <br>
                            <small>
                                <a href="/workflow/tickets/<?= $esc['ticket_id'] ?>" class="text-muted">
                                    <?= e($esc['ticket_number']) ?>
                                </a>
                            </small>
                        </td>
                        <td>
                            <?= ucfirst($esc['entity_type']) ?> #<?= $esc['entity_id'] ?>
                        </td>
                        <td>
                            <?php if ($esc['original_role']): ?>
                            <span class="badge badge-outline-secondary"><?= e($esc['original_role']) ?></span>
                            <?php endif; ?>
                            <?php if ($esc['original_user_id']): ?>
                            <br><small>User #<?= $esc['original_user_id'] ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $reasonBadges = [
                                'sla_breach' => ['badge-danger', 'SLA Breach'],
                                'manual' => ['badge-warning', 'Manual'],
                                'complexity' => ['badge-info', 'Complexity']
                            ];
                            $badge = $reasonBadges[$esc['reason']] ?? ['badge-secondary', ucfirst($esc['reason'])];
                            ?>
                            <span class="badge <?= $badge[0] ?>"><?= $badge[1] ?></span>
                            <?php if ($esc['notes']): ?>
                            <br><small class="text-muted"><?= e(substr($esc['notes'], 0, 50)) ?>...</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= date('M j, g:i A', strtotime($esc['created_at'])) ?>
                            <br>
                            <small class="text-muted">
                                <?php
                                $hoursAgo = round((time() - strtotime($esc['created_at'])) / 3600);
                                echo $hoursAgo < 24 ? "{$hoursAgo}h ago" : round($hoursAgo / 24) . "d ago";
                                ?>
                            </small>
                        </td>
                        <td>
                            <?php
                            $statusClasses = [
                                'pending' => 'badge-warning',
                                'acknowledged' => 'badge-info',
                                'resolved' => 'badge-success'
                            ];
                            ?>
                            <span class="badge <?= $statusClasses[$esc['status']] ?? 'badge-secondary' ?>">
                                <?= ucfirst($esc['status']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="/tasks/<?= $esc['task_id'] ?>" class="btn btn-sm btn-primary" title="View Task">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($esc['status'] === 'pending'): ?>
                                <button type="button" class="btn btn-sm btn-success" title="Acknowledge"
                                        onclick="acknowledgeEscalation(<?= $esc['id'] ?>)">
                                    <i class="fas fa-check"></i>
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

<!-- What to do section -->
<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title mb-0">
            <i class="fas fa-info-circle mr-2"></i>
            About Escalations
        </h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <h5><i class="fas fa-clock text-danger mr-2"></i> SLA Breach</h5>
                <p class="text-muted">Tasks that exceeded their expected completion time. The original assignee still has the task, but you should follow up.</p>
            </div>
            <div class="col-md-4">
                <h5><i class="fas fa-hand-point-up text-warning mr-2"></i> Manual Escalation</h5>
                <p class="text-muted">Tasks manually escalated by the assignee, typically due to complexity or need for guidance.</p>
            </div>
            <div class="col-md-4">
                <h5><i class="fas fa-check-circle text-success mr-2"></i> Your Actions</h5>
                <p class="text-muted">Acknowledge escalations to show you've seen them. You can reassign tasks or provide guidance via comments.</p>
            </div>
        </div>
    </div>
</div>

<script>
function acknowledgeEscalation(escalationId) {
    if (!confirm('Acknowledge this escalation?')) return;

    fetch('/tasks/escalations/acknowledge', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'escalation_id=' + escalationId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to acknowledge escalation. Please try again.');
    });
}
</script>
