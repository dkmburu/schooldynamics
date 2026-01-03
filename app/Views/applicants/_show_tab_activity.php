<?php
/**
 * Applicant Profile - Activity Log Tab
 */

// Helper function to get action icon
function getActionIcon($action) {
    $icons = [
        'created' => 'ti-plus-circle',
        'updated' => 'ti-edit',
        'status_changed' => 'ti-refresh',
        'document_uploaded' => 'ti-upload',
        'guardian_added' => 'ti-user-plus',
        'interview_scheduled' => 'ti-calendar',
        'exam_scheduled' => 'ti-file-text',
        'decision_made' => 'ti-check-circle',
        'admitted' => 'ti-school',
    ];
    return $icons[$action] ?? 'ti-circle';
}

// Helper function to get action color
function getActionColor($action) {
    $colors = [
        'created' => 'success',
        'updated' => 'info',
        'status_changed' => 'primary',
        'document_uploaded' => 'azure',
        'guardian_added' => 'purple',
        'interview_scheduled' => 'indigo',
        'exam_scheduled' => 'cyan',
        'decision_made' => 'lime',
        'admitted' => 'teal',
    ];
    return $colors[$action] ?? 'secondary';
}
?>

<h5 class="overview-subsection-title" style="font-size: 14px; font-weight: 600; color: #605e5c; margin-bottom: 12px;">Activity Timeline</h5>

<?php if (empty($auditLog)): ?>
    <div class="alert alert-info">
        <i class="ti ti-info-circle icon"></i> No activity recorded yet.
    </div>
<?php else: ?>
    <div class="timeline">
        <?php foreach ($auditLog as $log): ?>
        <div class="timeline-event">
            <div class="timeline-event-icon bg-<?= getActionColor($log['action']) ?>">
                <i class="<?= getActionIcon($log['action']) ?>"></i>
            </div>
            <div class="card timeline-event-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <div>
                            <strong><?= e(ucwords(str_replace('_', ' ', $log['action']))) ?></strong>
                            <?php if (!empty($log['description'])): ?>
                                <div class="text-muted"><?= e($log['description']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="text-muted small">
                            <?= formatDateTime($log['created_at']) ?>
                        </div>
                    </div>

                    <?php if (!empty($log['field_name']) && !empty($log['old_value'])): ?>
                    <div class="small text-muted">
                        <strong><?= e(ucwords(str_replace('_', ' ', $log['field_name']))) ?>:</strong>
                        <span class="text-decoration-line-through"><?= e($log['old_value']) ?></span>
                        →
                        <span class="text-success"><?= e($log['new_value']) ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="mt-2 small">
                        <i class="ti ti-user icon"></i> <?= e($log['user_name'] ?? 'System') ?>
                        <?php if (!empty($log['ip_address'])): ?>
                            <span class="text-muted">from <?= e($log['ip_address']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (count($auditLog) >= 20): ?>
    <div class="alert alert-info mt-3">
        <i class="ti ti-info-circle icon"></i> Showing last 20 activities. <a href="#" onclick="alert('Full log coming soon'); return false;">View all activity</a>
    </div>
    <?php endif; ?>
<?php endif; ?>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 9px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: var(--tblr-border-color);
}

.timeline-event {
    position: relative;
    margin-bottom: 1.5rem;
}

.timeline-event-icon {
    position: absolute;
    left: -30px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 10px;
    z-index: 1;
}

.timeline-event-card {
    margin-left: 10px;
}

.timeline-event:last-child {
    margin-bottom: 0;
}
</style>
