<?php
$account = $account ?? [];
$children = $children ?? [];
$activityLog = $activityLog ?? [];
?>

<div class="container-xl">
    <!-- Page Header -->
    <div class="page-header d-print-none mb-4">
        <div class="row align-items-center">
            <div class="col-auto">
                <a href="/portals/parents" class="btn btn-outline-secondary btn-sm mb-2">
                    <i class="ti ti-arrow-left me-1"></i> Back to Accounts
                </a>
                <h2 class="page-title">
                    <i class="ti ti-user me-2"></i> Parent Account Details
                </h2>
            </div>
            <div class="col-auto ms-auto">
                <?php if ($account['status'] === 'pending'): ?>
                    <form method="POST" action="/portals/parents/<?= $account['id'] ?>/approve" class="d-inline">
                        <button type="submit" class="btn btn-success">
                            <i class="ti ti-check me-1"></i> Approve
                        </button>
                    </form>
                    <button class="btn btn-outline-danger" onclick="rejectAccount()">
                        <i class="ti ti-x me-1"></i> Reject
                    </button>
                <?php elseif ($account['status'] === 'active'): ?>
                    <button class="btn btn-warning" onclick="suspendAccount()">
                        <i class="ti ti-ban me-1"></i> Suspend
                    </button>
                <?php elseif ($account['status'] === 'suspended'): ?>
                    <form method="POST" action="/portals/parents/<?= $account['id'] ?>/activate" class="d-inline">
                        <button type="submit" class="btn btn-success">
                            <i class="ti ti-check me-1"></i> Activate
                        </button>
                    </form>
                <?php endif; ?>
                <button class="btn btn-outline-primary" onclick="resetPassword()">
                    <i class="ti ti-key me-1"></i> Reset Password
                </button>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Account Information -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <span class="avatar avatar-xl bg-primary-lt text-primary mb-3" style="width: 80px; height: 80px; font-size: 2rem;">
                        <?= strtoupper(substr($account['guardian_name'] ?? 'P', 0, 1)) ?>
                    </span>
                    <h3 class="mb-1"><?= e($account['guardian_name'] ?? 'Unknown') ?></h3>
                    <p class="text-muted mb-3"><?= e($account['relationship'] ?? 'Parent/Guardian') ?></p>

                    <?php
                    $statusColors = ['active' => 'success', 'pending' => 'warning', 'suspended' => 'danger'];
                    $statusColor = $statusColors[$account['status']] ?? 'secondary';
                    ?>
                    <span class="badge bg-<?= $statusColor ?> fs-6"><?= ucfirst($account['status']) ?></span>
                </div>
                <div class="card-body border-top">
                    <dl class="row mb-0">
                        <dt class="col-5 text-muted">Email</dt>
                        <dd class="col-7"><?= e($account['email']) ?></dd>

                        <dt class="col-5 text-muted">Phone</dt>
                        <dd class="col-7"><?= e($account['phone'] ?? 'N/A') ?></dd>

                        <dt class="col-5 text-muted">Registered</dt>
                        <dd class="col-7"><?= date('M d, Y', strtotime($account['created_at'])) ?></dd>

                        <dt class="col-5 text-muted">Last Login</dt>
                        <dd class="col-7">
                            <?= !empty($account['last_login_at']) ? date('M d, Y H:i', strtotime($account['last_login_at'])) : 'Never' ?>
                        </dd>

                        <?php if (!empty($account['last_login_ip'])): ?>
                            <dt class="col-5 text-muted">Last IP</dt>
                            <dd class="col-7"><?= e($account['last_login_ip']) ?></dd>
                        <?php endif; ?>

                        <dt class="col-5 text-muted">Email Verified</dt>
                        <dd class="col-7">
                            <?php if (!empty($account['email_verified_at'])): ?>
                                <span class="text-success"><i class="ti ti-check"></i> Yes</span>
                            <?php else: ?>
                                <span class="text-warning"><i class="ti ti-clock"></i> Pending</span>
                            <?php endif; ?>
                        </dd>

                        <?php if ($account['failed_login_attempts'] > 0): ?>
                            <dt class="col-5 text-muted">Failed Logins</dt>
                            <dd class="col-7">
                                <span class="text-danger"><?= $account['failed_login_attempts'] ?></span>
                                <?php if (!empty($account['locked_until']) && strtotime($account['locked_until']) > time()): ?>
                                    <br><small class="text-muted">Locked until <?= date('H:i', strtotime($account['locked_until'])) ?></small>
                                <?php endif; ?>
                            </dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Children and Activity -->
        <div class="col-lg-8">
            <!-- Linked Children -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="ti ti-school me-2"></i> Linked Children
                    </h3>
                </div>
                <?php if (empty($children)): ?>
                    <div class="card-body text-center text-muted py-4">
                        <i class="ti ti-user-off mb-2" style="font-size: 2rem;"></i>
                        <p class="mb-0">No linked children found</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($children as $child): ?>
                            <div class="list-group-item">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <span class="avatar bg-blue-lt">
                                            <?= strtoupper(substr($child['first_name'] ?? '', 0, 1) . substr($child['last_name'] ?? '', 0, 1)) ?>
                                        </span>
                                    </div>
                                    <div class="col">
                                        <div class="font-weight-medium"><?= e($child['first_name'] . ' ' . $child['last_name']) ?></div>
                                        <div class="text-muted small">
                                            <?= e($child['admission_number']) ?>
                                            <?php if (!empty($child['class_name'])): ?>
                                                <span class="mx-1">|</span> <?= e($child['class_name']) ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <?php
                                        $childStatusColors = ['active' => 'success', 'suspended' => 'warning', 'withdrawn' => 'danger'];
                                        $childStatusColor = $childStatusColors[$child['status'] ?? ''] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $childStatusColor ?>-lt"><?= ucfirst($child['status'] ?? 'Active') ?></span>
                                    </div>
                                    <div class="col-auto">
                                        <a href="/students/<?= $child['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                            <i class="ti ti-external-link"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Activity Log -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="ti ti-history me-2"></i> Recent Activity
                    </h3>
                </div>
                <?php if (empty($activityLog)): ?>
                    <div class="card-body text-center text-muted py-4">
                        <i class="ti ti-activity-heartbeat mb-2" style="font-size: 2rem;"></i>
                        <p class="mb-0">No activity recorded</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush list-group-hoverable">
                        <?php foreach ($activityLog as $activity): ?>
                            <div class="list-group-item">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <?php
                                        $activityIcons = [
                                            'login' => 'ti-login text-success',
                                            'logout' => 'ti-logout text-secondary',
                                            'view_fees' => 'ti-receipt text-blue',
                                            'view_attendance' => 'ti-calendar text-cyan',
                                            'password_change' => 'ti-key text-warning',
                                            'profile_update' => 'ti-user-edit text-primary'
                                        ];
                                        $icon = $activityIcons[$activity['action'] ?? ''] ?? 'ti-activity text-muted';
                                        ?>
                                        <span class="avatar avatar-sm bg-white">
                                            <i class="ti <?= $icon ?>"></i>
                                        </span>
                                    </div>
                                    <div class="col">
                                        <div><?= e($activity['description'] ?? $activity['action']) ?></div>
                                        <div class="text-muted small">
                                            <?= date('M d, Y H:i', strtotime($activity['created_at'])) ?>
                                            <?php if (!empty($activity['ip_address'])): ?>
                                                <span class="mx-1">|</span> <?= e($activity['ip_address']) ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Action Modals -->
<div class="modal modal-blur fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <i class="ti mb-3" id="confirmIcon" style="font-size: 3rem;"></i>
                <h3 id="confirmTitle">Confirm Action</h3>
                <div class="text-muted" id="confirmMessage"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="confirmForm" method="POST" class="d-inline">
                    <button type="submit" class="btn" id="confirmButton">Confirm</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function showConfirmModal(title, message, action, buttonClass, buttonText, iconClass) {
    document.getElementById('confirmTitle').textContent = title;
    document.getElementById('confirmMessage').textContent = message;
    document.getElementById('confirmForm').action = action;
    document.getElementById('confirmButton').className = 'btn ' + buttonClass;
    document.getElementById('confirmButton').textContent = buttonText;
    document.getElementById('confirmIcon').className = 'ti ' + iconClass + ' mb-3';
    new bootstrap.Modal(document.getElementById('confirmModal')).show();
}

function rejectAccount() {
    showConfirmModal('Reject Account', 'This will reject and delete this pending account request.',
        '/portals/parents/<?= $account['id'] ?>/reject', 'btn-danger', 'Reject', 'ti-x text-danger');
}

function suspendAccount() {
    showConfirmModal('Suspend Account', 'This will prevent the parent from logging in until reactivated.',
        '/portals/parents/<?= $account['id'] ?>/suspend', 'btn-warning', 'Suspend', 'ti-ban text-warning');
}

function resetPassword() {
    showConfirmModal('Reset Password', 'This will generate a new password and send it to the parent\'s email.',
        '/portals/parents/<?= $account['id'] ?>/reset-password', 'btn-primary', 'Reset Password', 'ti-key text-primary');
}
</script>
