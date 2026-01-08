<?php
$pendingAccounts = $pendingAccounts ?? [];
$stats = $stats ?? ['total' => 0, 'pending' => 0, 'active' => 0, 'suspended' => 0];
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
            <a class="nav-link active" href="/portals/parents/pending">
                <i class="ti ti-user-check me-1"></i> Pending
                <?php if ($stats['pending'] > 0): ?>
                    <span class="badge bg-warning ms-1"><?= $stats['pending'] ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="/portals/parents/notifications">
                <i class="ti ti-bell me-1"></i> Notifications
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="/portals/parents/settings">
                <i class="ti ti-settings me-1"></i> Settings
            </a>
        </li>
    </ul>

    <!-- Pending Approvals -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="ti ti-clock me-2"></i> Pending Registration Requests
            </h3>
            <div class="card-actions">
                <?php if (!empty($pendingAccounts)): ?>
                    <button class="btn btn-success btn-sm" onclick="bulkApprove()">
                        <i class="ti ti-checks me-1"></i> Approve All
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($pendingAccounts)): ?>
            <div class="card-body text-center py-5">
                <i class="ti ti-checkbox text-success mb-3" style="font-size: 4rem;"></i>
                <h3 class="text-muted">No Pending Requests</h3>
                <p class="text-muted mb-0">All parent registration requests have been processed.</p>
            </div>
        <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($pendingAccounts as $account): ?>
                    <div class="list-group-item">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar bg-warning-lt text-warning">
                                    <i class="ti ti-user-plus"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h4 class="mb-1"><?= e($account['guardian_name'] ?? 'Unknown Guardian') ?></h4>
                                        <div class="text-muted">
                                            <i class="ti ti-mail me-1"></i> <?= e($account['email']) ?>
                                            <?php if (!empty($account['phone'])): ?>
                                                <span class="mx-2">|</span>
                                                <i class="ti ti-phone me-1"></i> <?= e($account['phone']) ?>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($account['children'])): ?>
                                            <div class="mt-2">
                                                <strong>Linked Children:</strong>
                                                <?php foreach ($account['children'] as $child): ?>
                                                    <span class="badge bg-blue-lt me-1">
                                                        <?= e($child['name']) ?> (<?= e($child['admission_number']) ?>)
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="text-muted small mt-1">
                                            <i class="ti ti-calendar me-1"></i> Registered: <?= date('M d, Y \a\t H:i', strtotime($account['created_at'])) ?>
                                        </div>
                                    </div>
                                    <div class="btn-list">
                                        <form method="POST" action="/portals/parents/<?= $account['id'] ?>/approve" class="d-inline">
                                            <button type="submit" class="btn btn-success btn-sm">
                                                <i class="ti ti-check me-1"></i> Approve
                                            </button>
                                        </form>
                                        <button class="btn btn-outline-danger btn-sm" onclick="rejectAccount(<?= $account['id'] ?>, '<?= e($account['guardian_name'] ?? 'this account') ?>')">
                                            <i class="ti ti-x me-1"></i> Reject
                                        </button>
                                        <a href="/portals/parents/<?= $account['id'] ?>" class="btn btn-outline-secondary btn-sm">
                                            <i class="ti ti-eye me-1"></i> View
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Info Card -->
    <div class="card mt-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-auto">
                    <span class="bg-blue text-white avatar">
                        <i class="ti ti-info-circle"></i>
                    </span>
                </div>
                <div class="col">
                    <h4 class="mb-1">About Pending Registrations</h4>
                    <p class="text-muted mb-0">
                        When a parent registers on the Parent Portal, their account requires approval before they can access the system.
                        The system verifies that the phone number matches a guardian record in the database. Upon approval, the parent receives
                        an email confirmation and can immediately log in to view their children's information.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal modal-blur fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Registration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectForm" method="POST">
                <div class="modal-body">
                    <p>Are you sure you want to reject the registration for <strong id="rejectName"></strong>?</p>
                    <div class="mb-3">
                        <label class="form-label">Reason (optional)</label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="Enter reason for rejection..."></textarea>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="send_email" class="form-check-input" id="sendRejectEmail" checked>
                        <label class="form-check-label" for="sendRejectEmail">Send rejection email to parent</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Approve Modal -->
<div class="modal modal-blur fade" id="bulkApproveModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <i class="ti ti-checks text-success mb-3" style="font-size: 3rem;"></i>
                <h3>Approve All Pending</h3>
                <div class="text-muted">
                    This will approve all <?= count($pendingAccounts) ?> pending registration(s) and send welcome emails to each parent.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="/portals/parents/approve-all" class="d-inline">
                    <button type="submit" class="btn btn-success">Approve All</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function rejectAccount(id, name) {
    document.getElementById('rejectForm').action = '/portals/parents/' + id + '/reject';
    document.getElementById('rejectName').textContent = name;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

function bulkApprove() {
    new bootstrap.Modal(document.getElementById('bulkApproveModal')).show();
}
</script>
