<?php
$stats = $stats ?? ['total' => 0, 'pending' => 0, 'active' => 0, 'suspended' => 0];
$classes = $classes ?? [];
$recentNotifications = $recentNotifications ?? [];
?>

<div class="container-xl">
    <!-- Page Header -->
    <div class="page-header d-print-none mb-4">
        <div class="row align-items-center">
            <div class="col-auto">
                <h2 class="page-title">
                    <i class="ti ti-users-group me-2"></i> Parent Portal Management
                </h2>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link" href="/portals/parents">
                <i class="ti ti-users me-1"></i> Accounts
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="/portals/parents/pending">
                <i class="ti ti-user-check me-1"></i> Pending
                <?php if ($stats['pending'] > 0): ?>
                    <span class="badge bg-warning ms-1"><?= $stats['pending'] ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="/portals/parents/notifications">
                <i class="ti ti-bell me-1"></i> Notifications
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="/portals/parents/settings">
                <i class="ti ti-settings me-1"></i> Settings
            </a>
        </li>
    </ul>

    <div class="row g-4">
        <!-- Send Notification Form -->
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="ti ti-send me-2"></i> Send Notification
                    </h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="/portals/parents/notifications/send" id="notificationForm">
                        <!-- Recipients -->
                        <div class="mb-3">
                            <label class="form-label required">Recipients</label>
                            <div class="form-selectgroup form-selectgroup-boxes d-flex flex-column">
                                <label class="form-selectgroup-item flex-fill">
                                    <input type="radio" name="recipient_type" value="all" class="form-selectgroup-input" checked onchange="toggleRecipientOptions()">
                                    <div class="form-selectgroup-label d-flex align-items-center p-3">
                                        <div class="me-3">
                                            <span class="form-selectgroup-check"></span>
                                        </div>
                                        <div>
                                            <strong>All Parents</strong>
                                            <div class="text-muted">Send to all <?= number_format($stats['active']) ?> active parent accounts</div>
                                        </div>
                                    </div>
                                </label>
                                <label class="form-selectgroup-item flex-fill">
                                    <input type="radio" name="recipient_type" value="class" class="form-selectgroup-input" onchange="toggleRecipientOptions()">
                                    <div class="form-selectgroup-label d-flex align-items-center p-3">
                                        <div class="me-3">
                                            <span class="form-selectgroup-check"></span>
                                        </div>
                                        <div>
                                            <strong>Parents by Class</strong>
                                            <div class="text-muted">Send to parents of students in specific class(es)</div>
                                        </div>
                                    </div>
                                </label>
                                <label class="form-selectgroup-item flex-fill">
                                    <input type="radio" name="recipient_type" value="fee_balance" class="form-selectgroup-input" onchange="toggleRecipientOptions()">
                                    <div class="form-selectgroup-label d-flex align-items-center p-3">
                                        <div class="me-3">
                                            <span class="form-selectgroup-check"></span>
                                        </div>
                                        <div>
                                            <strong>Parents with Fee Balance</strong>
                                            <div class="text-muted">Send to parents whose children have unpaid fees</div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Class Selection (hidden by default) -->
                        <div class="mb-3" id="classSelection" style="display: none;">
                            <label class="form-label">Select Class(es)</label>
                            <select name="classes[]" class="form-select" multiple size="5">
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?= $class['id'] ?>"><?= e($class['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-hint">Hold Ctrl/Cmd to select multiple classes</small>
                        </div>

                        <!-- Notification Type -->
                        <div class="mb-3">
                            <label class="form-label required">Notification Type</label>
                            <select name="type" class="form-select" required>
                                <option value="announcement">Announcement</option>
                                <option value="fee_reminder">Fee Reminder</option>
                                <option value="event">Event Notice</option>
                                <option value="general">General</option>
                            </select>
                        </div>

                        <!-- Title -->
                        <div class="mb-3">
                            <label class="form-label required">Title</label>
                            <input type="text" name="title" class="form-control" placeholder="Enter notification title..." required maxlength="255">
                        </div>

                        <!-- Message -->
                        <div class="mb-3">
                            <label class="form-label required">Message</label>
                            <textarea name="message" class="form-control" rows="6" placeholder="Enter notification message..." required></textarea>
                            <small class="form-hint">You can use placeholders: {parent_name}, {student_name}, {fee_balance}</small>
                        </div>

                        <!-- Preview Count -->
                        <div class="alert alert-info mb-3" id="recipientPreview">
                            <div class="d-flex">
                                <div class="me-2">
                                    <i class="ti ti-users"></i>
                                </div>
                                <div>
                                    <strong id="recipientCount"><?= number_format($stats['active']) ?></strong> parent(s) will receive this notification
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary" onclick="previewNotification()">
                                <i class="ti ti-eye me-1"></i> Preview
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-send me-1"></i> Send Notification
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Recent Notifications -->
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="ti ti-history me-2"></i> Recent Notifications
                    </h3>
                </div>
                <?php if (empty($recentNotifications)): ?>
                    <div class="card-body text-center text-muted py-4">
                        <i class="ti ti-bell-off mb-2" style="font-size: 2rem;"></i>
                        <p class="mb-0">No notifications sent yet</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush list-group-hoverable">
                        <?php foreach ($recentNotifications as $notification): ?>
                            <div class="list-group-item">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <?php
                                        $typeIcons = [
                                            'announcement' => 'ti-speakerphone text-primary',
                                            'fee_reminder' => 'ti-cash text-warning',
                                            'event' => 'ti-calendar-event text-success',
                                            'general' => 'ti-bell text-secondary'
                                        ];
                                        $icon = $typeIcons[$notification['type'] ?? ''] ?? 'ti-bell text-muted';
                                        ?>
                                        <span class="avatar avatar-sm bg-white">
                                            <i class="ti <?= $icon ?>"></i>
                                        </span>
                                    </div>
                                    <div class="col">
                                        <div class="d-flex justify-content-between">
                                            <strong><?= e($notification['title']) ?></strong>
                                            <small class="text-muted"><?= date('M d', strtotime($notification['created_at'])) ?></small>
                                        </div>
                                        <div class="text-muted small text-truncate" style="max-width: 250px;">
                                            <?= e($notification['message']) ?>
                                        </div>
                                        <div class="text-muted small">
                                            <i class="ti ti-users me-1"></i> Sent to <?= number_format($notification['recipient_count'] ?? 0) ?> parent(s)
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quick Stats -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="ti ti-chart-bar me-2"></i> Notification Stats
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h1 mb-0"><?= number_format($stats['active']) ?></div>
                                <div class="text-muted small">Active Parents</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h1 mb-0"><?= count($recentNotifications) ?></div>
                                <div class="text-muted small">Sent This Month</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal modal-blur fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Notification Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="card bg-light">
                    <div class="card-body">
                        <div class="d-flex align-items-start mb-3">
                            <span class="avatar bg-primary-lt me-3" id="previewIcon">
                                <i class="ti ti-bell"></i>
                            </span>
                            <div>
                                <h4 class="mb-1" id="previewTitle">Notification Title</h4>
                                <small class="text-muted">Just now</small>
                            </div>
                        </div>
                        <p class="mb-0" id="previewMessage">Notification message will appear here...</p>
                    </div>
                </div>
                <div class="alert alert-secondary mt-3 mb-0">
                    <i class="ti ti-info-circle me-1"></i>
                    This is how the notification will appear to parents in their portal.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function toggleRecipientOptions() {
    const recipientType = document.querySelector('input[name="recipient_type"]:checked').value;
    const classSelection = document.getElementById('classSelection');
    const recipientCount = document.getElementById('recipientCount');

    classSelection.style.display = recipientType === 'class' ? 'block' : 'none';

    // Update recipient count (in a real app, this would be an AJAX call)
    const counts = {
        'all': <?= $stats['active'] ?>,
        'class': 0,
        'fee_balance': Math.floor(<?= $stats['active'] ?> * 0.4)  // Estimate
    };
    recipientCount.textContent = counts[recipientType].toLocaleString();
}

function previewNotification() {
    const title = document.querySelector('input[name="title"]').value || 'Notification Title';
    const message = document.querySelector('textarea[name="message"]').value || 'Notification message will appear here...';
    const type = document.querySelector('select[name="type"]').value;

    const icons = {
        'announcement': 'ti-speakerphone',
        'fee_reminder': 'ti-cash',
        'event': 'ti-calendar-event',
        'general': 'ti-bell'
    };

    document.getElementById('previewTitle').textContent = title;
    document.getElementById('previewMessage').textContent = message;
    document.getElementById('previewIcon').innerHTML = '<i class="ti ' + (icons[type] || 'ti-bell') + '"></i>';

    new bootstrap.Modal(document.getElementById('previewModal')).show();
}
</script>
