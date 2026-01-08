<?php
$childrenData = $childrenData ?? [];
$pendingRequests = $pendingRequests ?? [];
$notifications = $notifications ?? [];
$unreadCount = $unreadCount ?? 0;
$parentName = $_SESSION['parent_name'] ?? 'Parent';
$hasApprovedStudents = !empty($childrenData);
$hasPendingRequests = !empty($pendingRequests);
?>

<div class="container py-4">
    <!-- Welcome Section -->
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h3 class="mb-1">Welcome, <?= e($parentName) ?></h3>
            <p class="text-muted mb-0">
                <?php if ($hasApprovedStudents): ?>
                    Here's an overview of your children's status
                <?php else: ?>
                    Link your children to view their information
                <?php endif; ?>
            </p>
        </div>
        <a href="/parent/link-student" class="btn btn-primary">
            <i class="ti ti-user-plus me-1"></i> Link Student
        </a>
    </div>

    <!-- Pending Linkage Requests Alert -->
    <?php if ($hasPendingRequests): ?>
        <div class="alert alert-info mb-4">
            <div class="d-flex align-items-start">
                <i class="ti ti-clock-hour-4 me-2 mt-1" style="font-size: 1.25rem;"></i>
                <div class="flex-fill">
                    <h6 class="alert-heading mb-1">Pending Requests</h6>
                    <p class="mb-2 small">You have <?= count($pendingRequests) ?> student linkage request(s) awaiting school approval.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($pendingRequests as $request): ?>
                            <span class="badge bg-blue-lt text-blue">
                                <i class="ti ti-user me-1"></i>
                                <?= e($request['admission_number']) ?> (<?= e($request['grade_name']) ?>)
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Empty State (No approved students yet) -->
    <?php if (!$hasApprovedStudents && !$hasPendingRequests): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <div class="mb-4">
                    <span class="avatar avatar-xl bg-primary-lt" style="width: 80px; height: 80px;">
                        <i class="ti ti-users-group" style="font-size: 2.5rem;"></i>
                    </span>
                </div>
                <h4 class="mb-2">No Students Linked Yet</h4>
                <p class="text-muted mb-4" style="max-width: 400px; margin: 0 auto;">
                    To view your child's academic information, fees, and attendance, you need to link them to your account.
                </p>
                <a href="/parent/link-student" class="btn btn-primary btn-lg">
                    <i class="ti ti-user-plus me-2"></i> Link Your First Student
                </a>
            </div>
        </div>
    <?php elseif (!$hasApprovedStudents && $hasPendingRequests): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <div class="mb-4">
                    <span class="avatar avatar-xl bg-azure-lt" style="width: 80px; height: 80px;">
                        <i class="ti ti-hourglass" style="font-size: 2.5rem;"></i>
                    </span>
                </div>
                <h4 class="mb-2">Awaiting Approval</h4>
                <p class="text-muted mb-0" style="max-width: 400px; margin: 0 auto;">
                    Your student linkage request is being reviewed by the school. You'll be able to see your child's information once approved.
                </p>
            </div>
        </div>
    <?php else: ?>
        <!-- Children Cards -->
        <div class="row g-4 mb-4">
            <?php foreach ($childrenData as $childData): ?>
                <?php $child = $childData['student']; ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card child-card h-100">
                        <div class="card-header bg-primary text-white">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-lg bg-white text-primary me-3" style="width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 1.25rem;">
                                    <?= strtoupper(substr($child['first_name'], 0, 1) . substr($child['last_name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <h5 class="mb-0"><?= e($child['first_name'] . ' ' . $child['last_name']) ?></h5>
                                    <small class="opacity-75"><?= e($child['admission_number'] ?? 'N/A') ?></small>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <small class="text-muted d-block">Class</small>
                                <strong><?= e($child['class_name'] ?? 'Not Assigned') ?></strong>
                            </div>

                            <div class="row g-3">
                                <!-- Pending Fees -->
                                <div class="col-6">
                                    <div class="stat-card bg-danger-lt p-3 text-center" style="border-radius: 0.5rem;">
                                        <div class="h4 mb-0 text-danger">KES <?= number_format($childData['pending_fees'], 0) ?></div>
                                        <small class="text-muted">Pending Fees</small>
                                    </div>
                                </div>

                                <!-- Attendance -->
                                <div class="col-6">
                                    <div class="stat-card bg-success-lt p-3 text-center" style="border-radius: 0.5rem;">
                                        <?php if ($childData['attendance_rate'] !== null): ?>
                                            <div class="h4 mb-0 text-success"><?= $childData['attendance_rate'] ?>%</div>
                                            <small class="text-muted">Attendance</small>
                                        <?php else: ?>
                                            <div class="h4 mb-0 text-muted">--</div>
                                            <small class="text-muted">No Data</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-light">
                            <div class="btn-group w-100" role="group">
                                <a href="/parent/child/<?= $child['id'] ?>/profile" class="btn btn-sm btn-outline-secondary">
                                    <i class="ti ti-user me-1"></i> Profile
                                </a>
                                <a href="/parent/child/<?= $child['id'] ?>/fees" class="btn btn-sm btn-outline-primary">
                                    <i class="ti ti-receipt me-1"></i> Fees
                                </a>
                                <a href="/parent/child/<?= $child['id'] ?>/attendance" class="btn btn-sm btn-outline-success">
                                    <i class="ti ti-calendar me-1"></i> Attendance
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Recent Notifications -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="ti ti-bell me-2"></i> Recent Notifications</h5>
            <?php if ($unreadCount > 0): ?>
                <span class="badge bg-danger"><?= $unreadCount ?> unread</span>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <?php if (empty($notifications)): ?>
                <div class="text-center py-4">
                    <i class="ti ti-bell-off text-muted" style="font-size: 2rem;"></i>
                    <p class="text-muted mt-2 mb-0">No notifications yet</p>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($notifications as $notif): ?>
                        <div class="list-group-item <?= $notif['read_at'] ? '' : 'bg-azure-lt' ?>">
                            <div class="d-flex">
                                <div class="me-3">
                                    <?php
                                    $iconClass = 'ti-bell';
                                    $iconColor = 'text-muted';
                                    switch ($notif['type'] ?? '') {
                                        case 'fee_reminder':
                                            $iconClass = 'ti-receipt';
                                            $iconColor = 'text-danger';
                                            break;
                                        case 'grade_posted':
                                            $iconClass = 'ti-report-analytics';
                                            $iconColor = 'text-success';
                                            break;
                                        case 'attendance':
                                            $iconClass = 'ti-calendar-event';
                                            $iconColor = 'text-warning';
                                            break;
                                        case 'announcement':
                                            $iconClass = 'ti-speakerphone';
                                            $iconColor = 'text-primary';
                                            break;
                                        case 'linkage_approved':
                                            $iconClass = 'ti-user-check';
                                            $iconColor = 'text-success';
                                            break;
                                        case 'linkage_rejected':
                                            $iconClass = 'ti-user-x';
                                            $iconColor = 'text-danger';
                                            break;
                                    }
                                    ?>
                                    <i class="ti <?= $iconClass ?> <?= $iconColor ?>" style="font-size: 1.5rem;"></i>
                                </div>
                                <div class="flex-fill">
                                    <h6 class="mb-1"><?= e($notif['title']) ?></h6>
                                    <p class="text-muted mb-1 small"><?= e($notif['message']) ?></p>
                                    <small class="text-muted">
                                        <i class="ti ti-clock me-1"></i>
                                        <?= date('M d, Y h:i A', strtotime($notif['created_at'])) ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php if (!empty($notifications)): ?>
            <div class="card-footer text-center">
                <a href="/parent/notifications" class="btn btn-sm btn-outline-primary">
                    View All Notifications <i class="ti ti-arrow-right ms-1"></i>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
