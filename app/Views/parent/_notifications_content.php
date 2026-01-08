<?php
$notifications = $notifications ?? [];
$counts = $counts ?? ['total' => 0, 'unread' => 0, 'action_required' => 0];
?>

<div class="container py-4">
    <!-- Header with Actions -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-0"><i class="ti ti-bell me-2"></i> Notifications</h3>
            <small class="text-muted"><?= $counts['unread'] ?> unread · <?= $counts['action_required'] ?> require action</small>
        </div>
        <?php if ($counts['unread'] > 0): ?>
            <a href="/parent/notifications/mark-all-read" class="btn btn-sm btn-outline-primary">
                <i class="ti ti-check-all me-1"></i> Mark All Read
            </a>
        <?php endif; ?>
    </div>

    <!-- Summary Cards -->
    <?php if ($counts['action_required'] > 0): ?>
        <div class="alert alert-warning mb-4">
            <div class="d-flex align-items-center">
                <i class="ti ti-alert-triangle me-2" style="font-size: 1.5rem;"></i>
                <div>
                    <strong>Action Required</strong><br>
                    <small>You have <?= $counts['action_required'] ?> notification<?= $counts['action_required'] > 1 ? 's' : '' ?> requiring your attention</small>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Notifications List -->
    <div class="card">
        <div class="card-body p-0">
            <?php if (empty($notifications)): ?>
                <div class="text-center py-5">
                    <i class="ti ti-bell-off text-muted" style="font-size: 4rem;"></i>
                    <h4 class="mt-3">No Notifications</h4>
                    <p class="text-muted">You don't have any notifications yet.</p>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($notifications as $notif): ?>
                        <?php
                        $isUnread = empty($notif['read_at']);
                        $severityColor = $notif['severity_color'] ?? 'blue';
                        $iconClass = $notif['type_icon'] ?? 'ti-bell';
                        // Use light grey background for icons instead of severity color
                        $bgClass = 'bg-secondary-lt';
                        $textClass = 'text-secondary';

                        // Calculate time remaining for action items
                        $timeRemaining = '';
                        if ($notif['requires_action'] && $notif['action_deadline'] && empty($notif['action_completed_at'])) {
                            $deadline = strtotime($notif['action_deadline']);
                            $now = time();
                            $diff = $deadline - $now;

                            if ($diff < 0) {
                                $timeRemaining = '<span class="badge bg-danger">Overdue</span>';
                            } elseif ($diff < 86400) { // Less than 1 day
                                $hours = ceil($diff / 3600);
                                $timeRemaining = '<span class="badge bg-danger">' . $hours . 'h remaining</span>';
                            } elseif ($diff < 172800) { // Less than 2 days
                                $timeRemaining = '<span class="badge bg-warning">1 day remaining</span>';
                            } else {
                                $days = ceil($diff / 86400);
                                $timeRemaining = '<span class="badge bg-info">' . $days . ' days remaining</span>';
                            }
                        }
                        ?>
                        <a href="/parent/notifications/<?= $notif['id'] ?>" class="list-group-item list-group-item-action <?= $isUnread ? 'bg-light' : '' ?>">
                            <div class="d-flex">
                                <div class="me-3">
                                    <div class="avatar" style="width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background-color: #e9ecef; color: #6c757d;">
                                        <i class="ti <?= $iconClass ?>" style="font-size: 1.5rem;"></i>
                                    </div>
                                </div>
                                <div class="flex-fill">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <div>
                                            <h5 class="mb-0 <?= $isUnread ? 'fw-bold' : '' ?>"><?= e($notif['title']) ?></h5>
                                            <?php if (!empty($notif['student_first_name'])): ?>
                                                <small class="text-muted">
                                                    <i class="ti ti-user me-1"></i><?= e($notif['student_first_name'] . ' ' . $notif['student_last_name']) ?>
                                                </small>
                                            <?php elseif (!empty($notif['grade_name'])): ?>
                                                <small class="text-muted">
                                                    <i class="ti ti-school me-1"></i><?= e($notif['grade_name']) ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted"><?= date('M d, Y', strtotime($notif['created_at'])) ?></small>
                                            <?php if ($isUnread): ?>
                                                <br><span class="badge bg-primary">New</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <p class="text-muted mb-1"><?= nl2br(e(substr($notif['message'], 0, 150))) ?><?= strlen($notif['message']) > 150 ? '...' : '' ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="ti ti-clock me-1"></i><?= date('h:i A', strtotime($notif['created_at'])) ?>
                                            <span class="mx-1">·</span>
                                            <span class="badge badge-sm bg-<?= $severityColor ?>-lt"><?= $notif['severity_name'] ?></span>
                                        </small>
                                        <?php if (!empty($timeRemaining)): ?>
                                            <?= $timeRemaining ?>
                                        <?php elseif ($notif['requires_action'] && !empty($notif['action_completed_at'])): ?>
                                            <span class="badge bg-success"><i class="ti ti-check me-1"></i>Completed</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
