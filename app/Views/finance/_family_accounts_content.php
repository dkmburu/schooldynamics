<?php
/**
 * Family Accounts - Content
 */
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <a href="/finance" class="btn btn-ghost-secondary btn-sm mb-2">
                    <i class="ti ti-arrow-left me-1"></i>Back to Finance
                </a>
                <h2 class="page-title">
                    <i class="ti ti-users-group me-2"></i>
                    Family Accounts
                </h2>
                <div class="text-muted mt-1">
                    Consolidated billing for families with multiple students
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <!-- Summary Stats -->
        <div class="row row-deck row-cards mb-3">
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Total Families</div>
                        </div>
                        <div class="h1 mb-0"><?= number_format($stats['total_families'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Active Families</div>
                        </div>
                        <div class="h1 mb-0 text-success"><?= number_format($stats['active_families'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Linked Students</div>
                        </div>
                        <div class="h1 mb-0 text-primary"><?= number_format($stats['linked_students'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Potential Families Alert -->
        <?php if (!empty($potentialFamilies)): ?>
        <div class="alert alert-info mb-3">
            <div class="d-flex">
                <div>
                    <i class="ti ti-bulb alert-icon"></i>
                </div>
                <div>
                    <h4 class="alert-title">Potential Families Detected</h4>
                    <div class="text-muted">
                        We found <?= count($potentialFamilies) ?> guardian(s) with multiple children who haven't been grouped into family accounts yet.
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="ti ti-user-plus me-2"></i>Create Family Accounts
                </h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Guardian</th>
                                <th>Children</th>
                                <th class="w-1">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($potentialFamilies as $pf): ?>
                            <tr>
                                <td>
                                    <strong><?= e($pf['guardian_name']) ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-primary me-1"><?= $pf['child_count'] ?> children</span>
                                    <span class="text-muted"><?= e($pf['children']) ?></span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary"
                                            onclick="showCreateFamilyModal(<?= $pf['guardian_id'] ?>, '<?= e($pf['guardian_name']) ?>')">
                                        <i class="ti ti-plus me-1"></i>Create Family
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="/finance/family-accounts" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control"
                               placeholder="Account #, family name, guardian..."
                               value="<?= e($search ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="active" <?= ($status ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="suspended" <?= ($status ?? '') === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                            <option value="closed" <?= ($status ?? '') === 'closed' ? 'selected' : '' ?>>Closed</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ti ti-search me-1"></i>Filter
                        </button>
                    </div>
                    <?php if (!empty($search) || !empty($status)): ?>
                    <div class="col-md-2 d-flex align-items-end">
                        <a href="/finance/family-accounts" class="btn btn-secondary w-100">
                            <i class="ti ti-x me-1"></i>Clear
                        </a>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Family Accounts Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Family Accounts</h3>
                <div class="card-actions">
                    <span class="badge bg-blue"><?= count($accounts ?? []) ?> families</span>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($accounts)): ?>
                <div class="empty py-5">
                    <div class="empty-img">
                        <i class="ti ti-users-group" style="font-size: 4rem; color: #adb5bd;"></i>
                    </div>
                    <p class="empty-title">No family accounts yet</p>
                    <p class="empty-subtitle text-muted">
                        <?php if (!empty($potentialFamilies)): ?>
                        Click "Create Family" above to create family accounts for guardians with multiple children.
                        <?php else: ?>
                        Family accounts will appear here when guardians have multiple students enrolled.
                        <?php endif; ?>
                    </p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover">
                        <thead>
                            <tr>
                                <th>Account #</th>
                                <th>Family Name</th>
                                <th>Primary Guardian</th>
                                <th class="text-center">Members</th>
                                <th>Billing Type</th>
                                <th class="text-end">Total Balance</th>
                                <th>Status</th>
                                <th class="w-1">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($accounts as $account): ?>
                            <tr>
                                <td>
                                    <a href="/finance/family-accounts/<?= $account['id'] ?>">
                                        <strong><?= e($account['account_number']) ?></strong>
                                    </a>
                                </td>
                                <td>
                                    <a href="/finance/family-accounts/<?= $account['id'] ?>" class="text-reset">
                                        <?= e($account['family_name']) ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="avatar avatar-sm bg-secondary-lt me-2">
                                            <?= strtoupper(substr($account['guardian_first_name'] ?? 'N', 0, 1)) ?>
                                        </span>
                                        <div>
                                            <?= e(($account['guardian_first_name'] ?? '') . ' ' . ($account['guardian_last_name'] ?? '')) ?>
                                            <?php if ($account['guardian_phone']): ?>
                                            <div class="text-muted small"><?= e($account['guardian_phone']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary"><?= $account['member_count'] ?></span>
                                </td>
                                <td>
                                    <span class="badge <?= $account['billing_type'] === 'consolidated' ? 'bg-blue-lt' : 'bg-secondary-lt' ?>">
                                        <?= ucfirst($account['billing_type']) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <?php $balance = $account['total_balance'] ?? 0; ?>
                                    <strong class="<?= $balance > 0 ? 'text-danger' : ($balance < 0 ? 'text-info' : 'text-success') ?>">
                                        KES <?= number_format($balance, 2) ?>
                                    </strong>
                                </td>
                                <td>
                                    <?php
                                    $statusColors = [
                                        'active' => 'bg-success',
                                        'suspended' => 'bg-danger',
                                        'closed' => 'bg-secondary'
                                    ];
                                    $statusColor = $statusColors[$account['account_status']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $statusColor ?>"><?= ucfirst($account['account_status']) ?></span>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            Actions
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a href="/finance/family-accounts/<?= $account['id'] ?>" class="dropdown-item">
                                                <i class="ti ti-eye me-2"></i>View Details
                                            </a>
                                            <a href="/finance/family-accounts/<?= $account['id'] ?>/statement" class="dropdown-item">
                                                <i class="ti ti-file-text me-2"></i>Family Statement
                                            </a>
                                            <div class="dropdown-divider"></div>
                                            <a href="/finance/payments/record?family=<?= $account['id'] ?>" class="dropdown-item">
                                                <i class="ti ti-cash me-2"></i>Record Payment
                                            </a>
                                        </div>
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
    </div>
</div>

<!-- Create Family Modal -->
<div class="modal modal-blur fade" id="createFamilyModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="/finance/family-accounts/create">
                <input type="hidden" name="guardian_id" id="create_guardian_id">
                <div class="modal-header">
                    <h5 class="modal-title">Create Family Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Guardian</label>
                        <input type="text" class="form-control" id="create_guardian_name" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Family Name</label>
                        <input type="text" name="family_name" class="form-control" id="create_family_name"
                               placeholder="e.g. The Mwangi Family">
                        <span class="form-hint">Leave blank to auto-generate from guardian's last name</span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Billing Type</label>
                        <select name="billing_type" class="form-select">
                            <option value="consolidated">Consolidated (one invoice for all children)</option>
                            <option value="individual">Individual (separate invoices per child)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Billing Email (Optional)</label>
                        <input type="email" name="billing_email" class="form-control"
                               placeholder="Override guardian's email for billing">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i>Create Family Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showCreateFamilyModal(guardianId, guardianName) {
    document.getElementById('create_guardian_id').value = guardianId;
    document.getElementById('create_guardian_name').value = guardianName;
    document.getElementById('create_family_name').value = '';

    const modal = new bootstrap.Modal(document.getElementById('createFamilyModal'));
    modal.show();
}
</script>
