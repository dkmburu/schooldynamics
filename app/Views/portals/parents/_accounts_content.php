<?php
$accounts = $accounts ?? [];
$stats = $stats ?? ['total' => 0, 'pending' => 0, 'active' => 0, 'suspended' => 0];
$filters = $filters ?? [];
$pagination = $pagination ?? ['current_page' => 1, 'total_pages' => 1, 'total' => 0];
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
            <a class="nav-link active" href="/portals/parents">
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

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-primary text-white avatar">
                                <i class="ti ti-users"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium"><?= number_format($stats['total']) ?></div>
                            <div class="text-muted">Total Accounts</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-warning text-white avatar">
                                <i class="ti ti-clock"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium"><?= number_format($stats['pending']) ?></div>
                            <div class="text-muted">Pending Approval</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-success text-white avatar">
                                <i class="ti ti-check"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium"><?= number_format($stats['active']) ?></div>
                            <div class="text-muted">Active</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-danger text-white avatar">
                                <i class="ti ti-ban"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium"><?= number_format($stats['suspended']) ?></div>
                            <div class="text-muted">Suspended</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="/portals/parents" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Name, email, phone..." value="<?= e($filters['search'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="suspended" <?= ($filters['status'] ?? '') === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date Registered</label>
                    <select name="date_range" class="form-select">
                        <option value="">All Time</option>
                        <option value="today" <?= ($filters['date_range'] ?? '') === 'today' ? 'selected' : '' ?>>Today</option>
                        <option value="week" <?= ($filters['date_range'] ?? '') === 'week' ? 'selected' : '' ?>>This Week</option>
                        <option value="month" <?= ($filters['date_range'] ?? '') === 'month' ? 'selected' : '' ?>>This Month</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ti ti-search me-1"></i> Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Accounts Table -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Parent Accounts</h3>
            <div class="card-actions">
                <span class="text-muted">
                    Showing <?= count($accounts) ?> of <?= number_format($pagination['total']) ?> accounts
                </span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Parent</th>
                        <th>Contact</th>
                        <th>Children</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Registered</th>
                        <th class="w-1">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($accounts)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="ti ti-users-minus mb-2" style="font-size: 2rem;"></i>
                                <p class="mb-0">No parent accounts found</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($accounts as $account): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="avatar avatar-sm bg-primary-lt me-2">
                                            <?= strtoupper(substr($account['guardian_name'] ?? 'P', 0, 1)) ?>
                                        </span>
                                        <div>
                                            <div class="font-weight-medium"><?= e($account['guardian_name'] ?? 'Unknown') ?></div>
                                            <div class="text-muted small"><?= e($account['relationship'] ?? '') ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div><?= e($account['email']) ?></div>
                                    <div class="text-muted small"><?= e($account['phone'] ?? '') ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-blue-lt"><?= (int)($account['children_count'] ?? 0) ?> student(s)</span>
                                </td>
                                <td>
                                    <?php
                                    $statusColors = ['active' => 'success', 'pending' => 'warning', 'suspended' => 'danger'];
                                    $statusColor = $statusColors[$account['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $statusColor ?>-lt"><?= ucfirst($account['status']) ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($account['last_login_at'])): ?>
                                        <span title="<?= date('Y-m-d H:i:s', strtotime($account['last_login_at'])) ?>">
                                            <?= date('M d, Y', strtotime($account['last_login_at'])) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">Never</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= date('M d, Y', strtotime($account['created_at'])) ?>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-ghost-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                            Actions
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a href="/portals/parents/<?= $account['id'] ?>" class="dropdown-item">
                                                <i class="ti ti-eye me-2"></i> View Details
                                            </a>
                                            <?php if ($account['status'] === 'pending'): ?>
                                                <button class="dropdown-item text-success" onclick="approveAccount(<?= $account['id'] ?>)">
                                                    <i class="ti ti-check me-2"></i> Approve
                                                </button>
                                                <button class="dropdown-item text-danger" onclick="rejectAccount(<?= $account['id'] ?>)">
                                                    <i class="ti ti-x me-2"></i> Reject
                                                </button>
                                            <?php elseif ($account['status'] === 'active'): ?>
                                                <button class="dropdown-item text-warning" onclick="suspendAccount(<?= $account['id'] ?>)">
                                                    <i class="ti ti-ban me-2"></i> Suspend
                                                </button>
                                            <?php elseif ($account['status'] === 'suspended'): ?>
                                                <button class="dropdown-item text-success" onclick="activateAccount(<?= $account['id'] ?>)">
                                                    <i class="ti ti-check me-2"></i> Activate
                                                </button>
                                            <?php endif; ?>
                                            <div class="dropdown-divider"></div>
                                            <button class="dropdown-item" onclick="resetPassword(<?= $account['id'] ?>)">
                                                <i class="ti ti-key me-2"></i> Reset Password
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pagination['total_pages'] > 1): ?>
            <div class="card-footer d-flex align-items-center">
                <p class="m-0 text-muted">
                    Page <?= $pagination['current_page'] ?> of <?= $pagination['total_pages'] ?>
                </p>
                <ul class="pagination m-0 ms-auto">
                    <li class="page-item <?= $pagination['current_page'] <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $pagination['current_page'] - 1 ?>&<?= http_build_query($filters) ?>">
                            <i class="ti ti-chevron-left"></i> Prev
                        </a>
                    </li>
                    <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                        <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query($filters) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $pagination['current_page'] >= $pagination['total_pages'] ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $pagination['current_page'] + 1 ?>&<?= http_build_query($filters) ?>">
                            Next <i class="ti ti-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Confirmation Modals -->
<div class="modal modal-blur fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <i class="ti ti-alert-circle text-warning mb-3" style="font-size: 3rem;"></i>
                <h3 id="confirmTitle">Confirm Action</h3>
                <div class="text-muted" id="confirmMessage">Are you sure you want to proceed?</div>
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
function showConfirmModal(title, message, action, buttonClass, buttonText) {
    document.getElementById('confirmTitle').textContent = title;
    document.getElementById('confirmMessage').textContent = message;
    document.getElementById('confirmForm').action = action;
    document.getElementById('confirmButton').className = 'btn ' + buttonClass;
    document.getElementById('confirmButton').textContent = buttonText;
    new bootstrap.Modal(document.getElementById('confirmModal')).show();
}

function approveAccount(id) {
    showConfirmModal('Approve Account', 'This will activate the parent account and send a welcome email.',
        '/portals/parents/' + id + '/approve', 'btn-success', 'Approve');
}

function rejectAccount(id) {
    showConfirmModal('Reject Account', 'This will reject and delete the pending account request.',
        '/portals/parents/' + id + '/reject', 'btn-danger', 'Reject');
}

function suspendAccount(id) {
    showConfirmModal('Suspend Account', 'This will prevent the parent from logging in until reactivated.',
        '/portals/parents/' + id + '/suspend', 'btn-warning', 'Suspend');
}

function activateAccount(id) {
    showConfirmModal('Activate Account', 'This will reactivate the suspended account.',
        '/portals/parents/' + id + '/activate', 'btn-success', 'Activate');
}

function resetPassword(id) {
    showConfirmModal('Reset Password', 'This will generate a new password and send it to the parent\'s email.',
        '/portals/parents/' + id + '/reset-password', 'btn-primary', 'Reset Password');
}
</script>
