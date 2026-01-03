<?php
/**
 * Task Detail View Content
 */

$canAct = in_array($task->status, ['pending', 'claimed', 'in_progress']);
$isClaimed = $task->claimed_by_user_id == ($_SESSION['user_id'] ?? 0);
$isOverdue = !empty($task->due_at) && strtotime($task->due_at) < time() && $canAct;
?>

<!-- Page Header -->
<div class="row mb-3">
    <div class="col">
        <h2 class="page-title">
            <i class="fas fa-clipboard-check mr-2"></i>
            Task: <?= e($task->task_number) ?>
            <?php if ($isOverdue): ?>
            <span class="badge badge-danger ml-2">OVERDUE</span>
            <?php endif; ?>
        </h2>
        <p class="text-muted mb-0">
            <a href="/workflow/tickets/<?= $ticket->id ?>" class="text-muted">
                <i class="fas fa-ticket-alt mr-1"></i> <?= e($ticket->ticket_number) ?>
            </a>
            <span class="mx-2">|</span>
            <span class="badge badge-info"><?= e($workflow['name']) ?></span>
        </p>
    </div>
    <div class="col-auto">
        <a href="/tasks" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Back to Tasks
        </a>
    </div>
</div>

<div class="row">
    <!-- Main Content -->
    <div class="col-lg-8">
        <!-- Task Info Card -->
        <div class="card mb-3">
            <div class="card-header bg-<?= $canAct ? 'primary' : 'secondary' ?> text-white">
                <h3 class="card-title mb-0">
                    <i class="fas fa-info-circle mr-2"></i>
                    <?= e($step['name']) ?>
                </h3>
            </div>
            <div class="card-body">
                <?php if (!empty($step['description'])): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-2"></i>
                    <?= nl2br(e($step['description'])) ?>
                </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6">
                        <dl>
                            <dt>Status</dt>
                            <dd>
                                <?php
                                $statusClasses = [
                                    'pending' => 'badge-secondary',
                                    'claimed' => 'badge-primary',
                                    'in_progress' => 'badge-info',
                                    'completed' => 'badge-success',
                                    'skipped' => 'badge-light',
                                    'cancelled' => 'badge-dark',
                                    'escalated' => 'badge-warning'
                                ];
                                ?>
                                <span class="badge <?= $statusClasses[$task->status] ?? 'badge-secondary' ?>">
                                    <?= ucfirst(str_replace('_', ' ', $task->status)) ?>
                                </span>
                            </dd>

                            <dt>Assigned To</dt>
                            <dd>
                                <?php if ($task->assigned_role): ?>
                                <span class="badge badge-outline-primary">
                                    <i class="fas fa-users mr-1"></i> <?= e($task->assigned_role) ?>
                                </span>
                                <?php endif; ?>
                                <?php if ($task->claimed_by_user_id): ?>
                                <br>
                                <small class="text-primary">
                                    <i class="fas fa-user-check mr-1"></i>
                                    Claimed by: <?= e($task->getClaimedByUser()['first_name'] ?? 'User') ?> <?= e($task->getClaimedByUser()['last_name'] ?? '') ?>
                                </small>
                                <?php endif; ?>
                            </dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl>
                            <dt>Created</dt>
                            <dd><?= date('M j, Y g:i A', strtotime($task->created_at)) ?></dd>

                            <?php if ($task->due_at): ?>
                            <dt>Due</dt>
                            <dd class="<?= $isOverdue ? 'text-danger font-weight-bold' : '' ?>">
                                <i class="far fa-clock mr-1"></i>
                                <?= date('M j, Y g:i A', strtotime($task->due_at)) ?>
                                <?php if ($isOverdue): ?>
                                <br><small class="text-danger">OVERDUE</small>
                                <?php endif; ?>
                            </dd>
                            <?php endif; ?>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Entity Info Card -->
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="fas fa-link mr-2"></i>
                    Related <?= ucfirst($ticket->entity_type) ?>
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
                            <i class="fas fa-external-link-alt mr-1"></i> View Details
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <p class="text-muted mb-0">Entity not found</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Action Form Card (only if task is actionable) -->
        <?php if ($canAct): ?>
        <div class="card mb-3">
            <div class="card-header bg-success text-white">
                <h3 class="card-title mb-0">
                    <i class="fas fa-play-circle mr-2"></i>
                    Take Action
                </h3>
            </div>
            <div class="card-body">
                <?php if (!$isClaimed && $task->status === 'pending'): ?>
                <!-- Need to claim first -->
                <div class="alert alert-warning">
                    <i class="fas fa-hand-paper mr-2"></i>
                    <strong>Claim Required:</strong> You must claim this task before taking action.
                </div>
                <button type="button" class="btn btn-lg btn-success" onclick="claimTask(<?= $task->id ?>)">
                    <i class="fas fa-hand-paper mr-2"></i> Claim This Task
                </button>
                <?php else: ?>
                <!-- Action Form -->
                <form id="taskActionForm" method="POST" action="/tasks/complete" enctype="multipart/form-data">
                    <input type="hidden" name="task_id" value="<?= $task->id ?>">

                    <!-- Custom Form Fields -->
                    <?php if (!empty($formFields)): ?>
                    <div class="mb-4">
                        <h5 class="mb-3"><i class="fas fa-edit mr-2"></i> Required Information</h5>
                        <div class="row">
                            <?php foreach ($formFields as $field): ?>
                            <?php
                            $fieldId = 'field_' . $field['field_code'];
                            $existingValue = $formData[$field['field_code']]['value'] ?? $field['default_value'] ?? '';
                            $isRequired = $field['is_required'] ? 'required' : '';
                            $colWidth = $field['column_width'] ?? 12;
                            ?>
                            <div class="col-md-<?= $colWidth ?> mb-3">
                                <label for="<?= $fieldId ?>" class="form-label">
                                    <?= e($field['field_label']) ?>
                                    <?php if ($field['is_required']): ?><span class="text-danger">*</span><?php endif; ?>
                                </label>

                                <?php if ($field['field_type'] === 'text'): ?>
                                <input type="text" class="form-control" id="<?= $fieldId ?>" name="<?= $fieldId ?>"
                                       value="<?= e($existingValue) ?>" placeholder="<?= e($field['placeholder'] ?? '') ?>"
                                       <?= $isRequired ?> <?= $field['min_length'] ? "minlength=\"{$field['min_length']}\"" : '' ?>
                                       <?= $field['max_length'] ? "maxlength=\"{$field['max_length']}\"" : '' ?>>

                                <?php elseif ($field['field_type'] === 'textarea'): ?>
                                <textarea class="form-control" id="<?= $fieldId ?>" name="<?= $fieldId ?>" rows="3"
                                          placeholder="<?= e($field['placeholder'] ?? '') ?>" <?= $isRequired ?>><?= e($existingValue) ?></textarea>

                                <?php elseif ($field['field_type'] === 'number' || $field['field_type'] === 'score'): ?>
                                <input type="number" class="form-control" id="<?= $fieldId ?>" name="<?= $fieldId ?>"
                                       value="<?= e($existingValue) ?>" <?= $isRequired ?>
                                       <?= $field['min_value'] !== null ? "min=\"{$field['min_value']}\"" : '' ?>
                                       <?= $field['max_value'] !== null ? "max=\"{$field['max_value']}\"" : '' ?>>
                                <?php if ($field['field_type'] === 'score' && $field['min_value'] !== null && $field['max_value'] !== null): ?>
                                <small class="text-muted">Score range: <?= $field['min_value'] ?> - <?= $field['max_value'] ?></small>
                                <?php endif; ?>

                                <?php elseif ($field['field_type'] === 'date'): ?>
                                <input type="date" class="form-control" id="<?= $fieldId ?>" name="<?= $fieldId ?>"
                                       value="<?= e($existingValue) ?>" <?= $isRequired ?>>

                                <?php elseif ($field['field_type'] === 'datetime'): ?>
                                <input type="datetime-local" class="form-control" id="<?= $fieldId ?>" name="<?= $fieldId ?>"
                                       value="<?= e($existingValue) ?>" <?= $isRequired ?>>

                                <?php elseif ($field['field_type'] === 'select'): ?>
                                <?php $options = json_decode($field['options'] ?? '[]', true); ?>
                                <select class="form-control" id="<?= $fieldId ?>" name="<?= $fieldId ?>" <?= $isRequired ?>>
                                    <option value="">-- Select --</option>
                                    <?php foreach ($options as $opt): ?>
                                    <option value="<?= e($opt['value']) ?>" <?= $existingValue === $opt['value'] ? 'selected' : '' ?>>
                                        <?= e($opt['label']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>

                                <?php elseif ($field['field_type'] === 'multiselect'): ?>
                                <?php $options = json_decode($field['options'] ?? '[]', true); ?>
                                <?php $selectedValues = is_array($existingValue) ? $existingValue : json_decode($existingValue ?? '[]', true); ?>
                                <select class="form-control select2" id="<?= $fieldId ?>" name="<?= $fieldId ?>[]" multiple <?= $isRequired ?>>
                                    <?php foreach ($options as $opt): ?>
                                    <option value="<?= e($opt['value']) ?>" <?= in_array($opt['value'], $selectedValues) ? 'selected' : '' ?>>
                                        <?= e($opt['label']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>

                                <?php elseif ($field['field_type'] === 'checkbox'): ?>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="<?= $fieldId ?>" name="<?= $fieldId ?>"
                                           value="1" <?= $existingValue ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="<?= $fieldId ?>">Yes</label>
                                </div>

                                <?php elseif ($field['field_type'] === 'radio'): ?>
                                <?php $options = json_decode($field['options'] ?? '[]', true); ?>
                                <?php foreach ($options as $opt): ?>
                                <div class="form-check">
                                    <input type="radio" class="form-check-input" name="<?= $fieldId ?>"
                                           id="<?= $fieldId ?>_<?= $opt['value'] ?>" value="<?= e($opt['value']) ?>"
                                           <?= $existingValue === $opt['value'] ? 'checked' : '' ?> <?= $isRequired ?>>
                                    <label class="form-check-label" for="<?= $fieldId ?>_<?= $opt['value'] ?>">
                                        <?= e($opt['label']) ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>

                                <?php elseif ($field['field_type'] === 'rating'): ?>
                                <div class="rating-input" id="<?= $fieldId ?>_container">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fa-star rating-star <?= $i <= (int)$existingValue ? 'fas text-warning' : 'far text-muted' ?>"
                                       data-value="<?= $i ?>" style="cursor: pointer; font-size: 1.5rem;"></i>
                                    <?php endfor; ?>
                                    <input type="hidden" id="<?= $fieldId ?>" name="<?= $fieldId ?>" value="<?= e($existingValue) ?>" <?= $isRequired ?>>
                                </div>

                                <?php elseif ($field['field_type'] === 'file'): ?>
                                <?php $fileConfig = json_decode($field['file_config'] ?? '{}', true); ?>
                                <input type="file" class="form-control" id="<?= $fieldId ?>" name="<?= $fieldId ?><?= ($fileConfig['multiple'] ?? false) ? '[]' : '' ?>"
                                       <?= ($fileConfig['multiple'] ?? false) ? 'multiple' : '' ?> <?= $isRequired ?>
                                       accept="<?= implode(',', array_map(fn($t) => '.' . $t, $fileConfig['allowed_types'] ?? [])) ?>">
                                <?php if (!empty($fileConfig['allowed_types'])): ?>
                                <small class="text-muted">Allowed: <?= implode(', ', $fileConfig['allowed_types']) ?></small>
                                <?php endif; ?>
                                <?php endif; ?>

                                <?php if (!empty($field['help_text'])): ?>
                                <small class="form-text text-muted"><?= e($field['help_text']) ?></small>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <hr>
                    <?php endif; ?>

                    <!-- Comment Field -->
                    <div class="mb-4">
                        <label for="comment" class="form-label">
                            <i class="fas fa-comment mr-1"></i>
                            <?= e($step['comment_label'] ?? 'Comments') ?>
                            <?php if ($step['require_comment']): ?><span class="text-danger">*</span><?php endif; ?>
                        </label>
                        <textarea class="form-control" id="comment" name="comment" rows="3"
                                  placeholder="Enter your comments here..."
                                  <?= $step['require_comment'] ? 'required' : '' ?>></textarea>
                    </div>

                    <!-- File Attachments -->
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-paperclip mr-1"></i> Attachments (Optional)
                        </label>
                        <input type="file" class="form-control" name="attachments[]" multiple>
                        <small class="text-muted">You can attach multiple files</small>
                    </div>

                    <hr>

                    <!-- Action Buttons -->
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-check-circle mr-1"></i> Select Action:</label>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($availableActions as $action): ?>
                        <?php
                        $btnClass = 'btn-secondary';
                        if (stripos($action['code'], 'approve') !== false || stripos($action['code'], 'accept') !== false) {
                            $btnClass = 'btn-success';
                        } elseif (stripos($action['code'], 'reject') !== false) {
                            $btnClass = 'btn-danger';
                        } elseif (stripos($action['code'], 'request') !== false || stripos($action['code'], 'info') !== false) {
                            $btnClass = 'btn-warning';
                        } elseif (stripos($action['code'], 'escalate') !== false) {
                            $btnClass = 'btn-info';
                        }
                        ?>
                        <button type="button" class="btn <?= $btnClass ?> btn-action mr-2 mb-2"
                                data-action="<?= e($action['code']) ?>"
                                data-requires-comment="<?= ($action['requires_comment'] ?? false) ? '1' : '0' ?>">
                            <i class="fas fa-check mr-1"></i>
                            <?= e($action['label']) ?>
                        </button>
                        <?php endforeach; ?>
                    </div>

                    <input type="hidden" name="action_code" id="action_code" value="">
                </form>

                <?php if ($isClaimed): ?>
                <hr>
                <button type="button" class="btn btn-outline-secondary" onclick="releaseTask(<?= $task->id ?>)">
                    <i class="fas fa-times mr-1"></i> Release Task (Unclaim)
                </button>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Completed Task Info -->
        <?php if ($task->status === 'completed'): ?>
        <div class="card mb-3">
            <div class="card-header bg-success text-white">
                <h3 class="card-title mb-0">
                    <i class="fas fa-check-circle mr-2"></i>
                    Task Completed
                </h3>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Action Taken:</dt>
                    <dd class="col-sm-9">
                        <span class="badge badge-success"><?= e($task->action_label ?? $task->action_code) ?></span>
                    </dd>

                    <dt class="col-sm-3">Completed By:</dt>
                    <dd class="col-sm-9">
                        <?php $actedBy = $task->getActedByUser(); ?>
                        <?= e(($actedBy['first_name'] ?? '') . ' ' . ($actedBy['last_name'] ?? '')) ?>
                    </dd>

                    <dt class="col-sm-3">Completed At:</dt>
                    <dd class="col-sm-9">
                        <?= $task->acted_at ? date('M j, Y g:i A', strtotime($task->acted_at)) : '-' ?>
                    </dd>

                    <?php if ($task->action_comment): ?>
                    <dt class="col-sm-3">Comment:</dt>
                    <dd class="col-sm-9"><?= nl2br(e($task->action_comment)) ?></dd>
                    <?php endif; ?>
                </dl>

                <!-- Display form data if any -->
                <?php if (!empty($formData)): ?>
                <hr>
                <h6>Submitted Data:</h6>
                <dl class="row mb-0">
                    <?php foreach ($formData as $code => $data): ?>
                    <dt class="col-sm-4"><?= e($data['label']) ?>:</dt>
                    <dd class="col-sm-8"><?= e($data['value']) ?></dd>
                    <?php endforeach; ?>
                </dl>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Task History -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="fas fa-history mr-2"></i>
                    Task History
                </h3>
            </div>
            <div class="card-body p-0">
                <?php if (empty($history)): ?>
                <p class="text-muted p-3 mb-0">No history available</p>
                <?php else: ?>
                <div class="timeline p-3">
                    <?php foreach ($history as $h): ?>
                    <div class="timeline-item mb-3">
                        <div class="timeline-marker bg-primary"></div>
                        <div class="timeline-content">
                            <small class="text-muted"><?= date('M j, Y g:i A', strtotime($h['created_at'])) ?></small>
                            <p class="mb-0">
                                <strong><?= e($h['actor_name'] ?? 'System') ?></strong>
                                - <?= e($h['description']) ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Attachments -->
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="fas fa-paperclip mr-2"></i>
                    Attachments
                </h3>
            </div>
            <div class="card-body">
                <?php if (empty($attachments)): ?>
                <p class="text-muted mb-0">No attachments</p>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($attachments as $att): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <div>
                            <i class="fas fa-file mr-2"></i>
                            <?= e($att['file_name']) ?>
                            <br>
                            <small class="text-muted">
                                <?= e($att['first_name'] ?? '') ?> <?= e($att['last_name'] ?? '') ?>
                                - <?= date('M j', strtotime($att['uploaded_at'])) ?>
                            </small>
                        </div>
                        <a href="/download/workflow_attachment/<?= $att['id'] ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-download"></i>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="fas fa-link mr-2"></i>
                    Quick Links
                </h3>
            </div>
            <div class="card-body">
                <a href="/workflow/tickets/<?= $ticket->id ?>" class="btn btn-block btn-outline-primary mb-2">
                    <i class="fas fa-ticket-alt mr-2"></i>
                    View Full Ticket
                </a>
                <?php if ($ticket->entity_type === 'applicant'): ?>
                <a href="/applicants/<?= $ticket->entity_id ?>" class="btn btn-block btn-outline-info mb-2">
                    <i class="fas fa-user mr-2"></i>
                    View Applicant Profile
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Handle action button clicks
document.querySelectorAll('.btn-action').forEach(btn => {
    btn.addEventListener('click', function() {
        const actionCode = this.dataset.action;
        const requiresComment = this.dataset.requiresComment === '1';
        const commentField = document.getElementById('comment');

        // Check if comment is required
        if (requiresComment && !commentField.value.trim()) {
            alert('Comment is required for this action.');
            commentField.focus();
            return;
        }

        // Confirm action
        if (!confirm('Are you sure you want to take this action?')) {
            return;
        }

        // Set action code and submit
        document.getElementById('action_code').value = actionCode;
        document.getElementById('taskActionForm').submit();
    });
});

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
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to claim task. Please try again.');
    });
}

function releaseTask(taskId) {
    if (!confirm('Release this task? It will go back to the task pool.')) return;

    fetch('/tasks/release', {
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
            window.location.href = '/tasks';
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to release task. Please try again.');
    });
}

// Rating star click handler
document.querySelectorAll('.rating-star').forEach(star => {
    star.addEventListener('click', function() {
        const value = parseInt(this.dataset.value);
        const container = this.closest('.rating-input');
        const input = container.querySelector('input[type="hidden"]');

        input.value = value;

        container.querySelectorAll('.rating-star').forEach((s, i) => {
            if (i < value) {
                s.classList.remove('far', 'text-muted');
                s.classList.add('fas', 'text-warning');
            } else {
                s.classList.remove('fas', 'text-warning');
                s.classList.add('far', 'text-muted');
            }
        });
    });
});
</script>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}
.timeline-item {
    position: relative;
    padding-left: 20px;
    border-left: 2px solid #dee2e6;
}
.timeline-marker {
    position: absolute;
    left: -8px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
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
.gap-2 {
    gap: 0.5rem;
}
</style>
