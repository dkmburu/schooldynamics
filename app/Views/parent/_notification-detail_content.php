<?php
$notification = $notification ?? null;

if (!$notification) {
    echo '<div class="alert alert-danger">Notification not found.</div>';
    return;
}

$severityColor = $notification['severity_color'] ?? 'blue';
$iconClass = $notification['type_icon'] ?? 'ti-bell';
// Use light grey background for icon
$bgClass = 'bg-secondary-lt';
$textClass = 'text-secondary';
$isUnread = empty($notification['read_at']);

// Calculate time remaining for action items
$timeRemaining = '';
$isOverdue = false;
if ($notification['requires_action'] && $notification['action_deadline'] && empty($notification['action_completed_at'])) {
    $deadline = strtotime($notification['action_deadline']);
    $now = time();
    $diff = $deadline - $now;

    if ($diff < 0) {
        $isOverdue = true;
        $timeRemaining = '<span class="badge bg-danger fs-4"><i class="ti ti-alert-triangle me-1"></i>Overdue</span>';
    } elseif ($diff < 86400) { // Less than 1 day
        $hours = ceil($diff / 3600);
        $timeRemaining = '<span class="badge bg-danger fs-4">' . $hours . ' hour' . ($hours > 1 ? 's' : '') . ' remaining</span>';
    } elseif ($diff < 172800) { // Less than 2 days
        $timeRemaining = '<span class="badge bg-warning fs-4">1 day remaining</span>';
    } else {
        $days = ceil($diff / 86400);
        $timeRemaining = '<span class="badge bg-info fs-4">' . $days . ' days remaining</span>';
    }
}
?>

<div class="container py-4">
    <!-- Back Button -->
    <div class="mb-3">
        <a href="/parent/notifications" class="btn btn-sm btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i> Back to Notifications
        </a>
    </div>

    <!-- Notification Card -->
    <div class="card">
        <div class="card-header <?= $bgClass ?>">
            <div class="d-flex align-items-start justify-content-between">
                <div class="d-flex align-items-start">
                    <div class="avatar me-3" style="width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background-color: #e9ecef; color: #6c757d;">
                        <i class="ti <?= $iconClass ?>" style="font-size: 2rem;"></i>
                    </div>
                    <div>
                        <h3 class="mb-1"><?= e($notification['title']) ?></h3>
                        <div class="text-muted">
                            <small>
                                <i class="ti ti-clock me-1"></i><?= date('F d, Y \a\t h:i A', strtotime($notification['created_at'])) ?>
                                <span class="mx-2">·</span>
                                <span class="badge bg-<?= $severityColor ?>-lt"><?= $notification['severity_name'] ?></span>
                                <span class="mx-2">·</span>
                                <span class="badge bg-primary-lt"><?= $notification['type_name'] ?></span>
                            </small>
                        </div>
                    </div>
                </div>
                <div class="text-end">
                    <?php if (!empty($timeRemaining)): ?>
                        <?= $timeRemaining ?>
                    <?php elseif ($notification['requires_action'] && !empty($notification['action_completed_at'])): ?>
                        <span class="badge bg-success fs-4"><i class="ti ti-check me-1"></i>Completed</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card-body">
            <!-- Student/Grade Context -->
            <?php if (!empty($notification['student_first_name'])): ?>
                <div class="alert alert-info mb-4">
                    <div class="d-flex align-items-center">
                        <i class="ti ti-user me-2" style="font-size: 1.5rem;"></i>
                        <div>
                            <strong>Student:</strong> <?= e($notification['student_first_name'] . ' ' . $notification['student_last_name']) ?>
                        </div>
                    </div>
                </div>
            <?php elseif (!empty($notification['grade_name'])): ?>
                <div class="alert alert-info mb-4">
                    <div class="d-flex align-items-center">
                        <i class="ti ti-school me-2" style="font-size: 1.5rem;"></i>
                        <div>
                            <strong>Grade/Class:</strong> <?= e($notification['grade_name']) ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Notification Message -->
            <div class="mb-4">
                <h5 class="mb-3">Message</h5>
                <div class="notification-message" style="line-height: 1.8; font-size: 1.05rem;">
                    <?= nl2br(e($notification['message'])) ?>
                </div>
            </div>

            <!-- Action Required Section -->
            <?php if ($notification['requires_action'] && empty($notification['action_completed_at'])): ?>
                <div class="card bg-<?= $isOverdue ? 'danger' : 'warning' ?>-lt mb-4">
                    <div class="card-body">
                        <h5 class="mb-3">
                            <i class="ti ti-alert-triangle me-2"></i>Action Required
                        </h5>
                        <div class="row">
                            <div class="col-md-8">
                                <p class="mb-2"><strong>Action Type:</strong> <?= e($notification['action_type_name']) ?></p>
                                <?php if ($notification['action_deadline']): ?>
                                    <p class="mb-0">
                                        <strong>Deadline:</strong>
                                        <?= date('F d, Y \a\t h:i A', strtotime($notification['action_deadline'])) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4 text-end">
                                <?php if (!empty($notification['action_url'])): ?>
                                    <a href="<?= e($notification['action_url']) ?>" class="btn btn-<?= $isOverdue ? 'danger' : 'warning' ?>">
                                        <i class="ti ti-arrow-right me-1"></i>Take Action
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php elseif ($notification['requires_action'] && !empty($notification['action_completed_at'])): ?>
                <div class="card bg-success-lt mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <i class="ti ti-check-circle me-3" style="font-size: 2rem; color: var(--tblr-success);"></i>
                            <div>
                                <h5 class="mb-1">Action Completed</h5>
                                <p class="text-muted mb-0">
                                    <small>Completed on <?= date('F d, Y \a\t h:i A', strtotime($notification['action_completed_at'])) ?></small>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                <div>
                    <?php if (empty($notification['dismissed_at'])): ?>
                        <button type="button" class="btn btn-outline-danger" onclick="dismissNotification(<?= $notification['id'] ?>)">
                            <i class="ti ti-trash me-1"></i>Dismiss
                        </button>
                    <?php endif; ?>
                </div>
                <div class="text-muted">
                    <small>
                        <i class="ti ti-info-circle me-1"></i>
                        <?= $notification['scope_name'] ?> Notification
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function dismissNotification(notificationId) {
    if (!confirm('Are you sure you want to dismiss this notification?')) {
        return;
    }

    fetch(`/parent/notifications/${notificationId}/dismiss`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = '/parent/notifications';
        } else {
            alert('Failed to dismiss notification. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}
</script>
