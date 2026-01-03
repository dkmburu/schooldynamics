<?php
/**
 * Chart of Accounts - Content
 */

$accountTypes = [
    'asset' => ['label' => 'Assets', 'icon' => 'ti-wallet', 'color' => 'primary'],
    'liability' => ['label' => 'Liabilities', 'icon' => 'ti-credit-card', 'color' => 'danger'],
    'equity' => ['label' => 'Equity', 'icon' => 'ti-building-bank', 'color' => 'info'],
    'income' => ['label' => 'Income', 'icon' => 'ti-arrow-up-circle', 'color' => 'success'],
    'expense' => ['label' => 'Expenses', 'icon' => 'ti-arrow-down-circle', 'color' => 'warning']
];
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <a href="/finance" class="btn btn-ghost-secondary btn-sm mb-2">
                    <i class="ti ti-arrow-left me-1"></i>Back to Finance
                </a>
                <h2 class="page-title">
                    <i class="ti ti-list-tree me-2"></i>
                    Chart of Accounts
                </h2>
                <div class="text-muted mt-1">
                    Manage your accounting structure
                </div>
            </div>
            <div class="col-auto ms-auto">
                <?php if (hasPermission('Finance.write') || Gate::hasRole('ADMIN')): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAccountModal">
                    <i class="ti ti-plus me-2"></i>Add Account
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <!-- Account Type Tabs -->
        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                    <?php $first = true; foreach ($accountTypes as $type => $config): ?>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link <?= $first ? 'active' : '' ?>"
                           id="tab-<?= $type ?>"
                           data-bs-toggle="tab"
                           href="#pane-<?= $type ?>"
                           role="tab">
                            <i class="ti <?= $config['icon'] ?> me-2 text-<?= $config['color'] ?>"></i>
                            <?= $config['label'] ?>
                            <span class="badge bg-<?= $config['color'] ?>-lt ms-2">
                                <?= count($accounts_by_type[$type] ?? []) ?>
                            </span>
                        </a>
                    </li>
                    <?php $first = false; endforeach; ?>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <?php $first = true; foreach ($accountTypes as $type => $config): ?>
                    <div class="tab-pane fade <?= $first ? 'show active' : '' ?>"
                         id="pane-<?= $type ?>"
                         role="tabpanel">

                        <?php if (empty($accounts_by_type[$type])): ?>
                        <div class="empty">
                            <div class="empty-img">
                                <i class="ti <?= $config['icon'] ?>" style="font-size: 4rem; color: #adb5bd;"></i>
                            </div>
                            <p class="empty-title">No <?= strtolower($config['label']) ?> accounts</p>
                            <p class="empty-subtitle text-muted">
                                Add <?= strtolower($config['label']) ?> accounts to start tracking.
                            </p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table table-striped">
                                <thead>
                                    <tr>
                                        <th style="width: 150px;">Account Code</th>
                                        <th>Account Name</th>
                                        <th style="width: 120px;">Normal Balance</th>
                                        <th style="width: 100px;">Type</th>
                                        <th style="width: 100px;">Status</th>
                                        <th style="width: 80px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($accounts_by_type[$type] as $account): ?>
                                    <tr class="<?= $account['is_header'] ? 'bg-light fw-bold' : '' ?>">
                                        <td>
                                            <code><?= e($account['account_code']) ?></code>
                                        </td>
                                        <td>
                                            <?php if ($account['is_header']): ?>
                                                <i class="ti ti-folder text-muted me-1"></i>
                                            <?php else: ?>
                                                <span style="padding-left: <?= (substr_count($account['account_code'], '-') - 1) * 20 ?>px"></span>
                                            <?php endif; ?>
                                            <?= e($account['account_name']) ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $account['normal_balance'] == 'debit' ? 'azure' : 'purple' ?>-lt">
                                                <?= ucfirst($account['normal_balance']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($account['is_header']): ?>
                                            <span class="badge bg-secondary-lt">Header</span>
                                            <?php else: ?>
                                            <span class="badge bg-primary-lt">Detail</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($account['is_active']): ?>
                                            <span class="badge bg-success-lt">Active</span>
                                            <?php else: ?>
                                            <span class="badge bg-danger-lt">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (hasPermission('Finance.write') || Gate::hasRole('ADMIN')): ?>
                                            <div class="dropdown">
                                                <button class="btn btn-icon btn-ghost-secondary" data-bs-toggle="dropdown">
                                                    <i class="ti ti-dots-vertical"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <a class="dropdown-item" href="#" onclick="editAccount(<?= htmlspecialchars(json_encode($account), ENT_QUOTES) ?>); return false;">
                                                        <i class="ti ti-edit me-2"></i>Edit
                                                    </a>
                                                    <?php if (!$account['is_header']): ?>
                                                    <a class="dropdown-item" href="#">
                                                        <i class="ti ti-file-text me-2"></i>View Transactions
                                                    </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php $first = false; endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Account Modal -->
<div class="modal fade" id="addAccountModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="/finance/chart-of-accounts/store" id="addAccountForm">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="ti ti-plus me-2"></i>Add Account
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label required">Account Code</label>
                            <input type="text" name="account_code" class="form-control"
                                   placeholder="e.g. 400-1000-001" required
                                   pattern="[0-9]{3}-[0-9]{4}-[0-9]{3}">
                            <small class="text-muted">Format: XXX-XXXX-XXX</small>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label required">Account Name</label>
                            <input type="text" name="account_name" class="form-control" required
                                   placeholder="Enter account name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Account Type</label>
                            <select name="account_type" class="form-select" required>
                                <option value="">Select type...</option>
                                <?php foreach ($accountTypes as $type => $config): ?>
                                <option value="<?= $type ?>"><?= $config['label'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Parent Account</label>
                            <select name="parent_account_id" class="form-select">
                                <option value="">No parent (top-level)</option>
                                <?php foreach ($accounts as $account): ?>
                                <?php if ($account['is_header']): ?>
                                <option value="<?= $account['id'] ?>">
                                    <?= e($account['account_code']) ?> - <?= e($account['account_name']) ?>
                                </option>
                                <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"
                                      placeholder="Optional description"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-check">
                                <input type="checkbox" name="is_header" class="form-check-input" value="1">
                                <span class="form-check-label">This is a header account (for grouping only)</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="ti ti-x me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-check me-1"></i>Add Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Account Modal -->
<div class="modal fade" id="editAccountModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="/finance/chart-of-accounts/update" id="editAccountForm">
                <input type="hidden" name="id" id="edit_account_id">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="ti ti-edit me-2"></i>Edit Account
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Account Code</label>
                            <input type="text" id="edit_account_code" class="form-control" readonly disabled>
                            <small class="text-muted">Cannot be changed</small>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label required">Account Name</label>
                            <input type="text" name="account_name" id="edit_account_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Parent Account</label>
                            <select name="parent_account_id" id="edit_parent_account_id" class="form-select">
                                <option value="">No parent (top-level)</option>
                                <?php foreach ($accounts as $account): ?>
                                <?php if ($account['is_header']): ?>
                                <option value="<?= $account['id'] ?>">
                                    <?= e($account['account_code']) ?> - <?= e($account['account_name']) ?>
                                </option>
                                <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="is_active" id="edit_is_active" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-check">
                                <input type="checkbox" name="is_header" id="edit_is_header" class="form-check-input" value="1">
                                <span class="form-check-label">This is a header account</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="ti ti-x me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editAccount(account) {
    document.getElementById('edit_account_id').value = account.id;
    document.getElementById('edit_account_code').value = account.account_code;
    document.getElementById('edit_account_name').value = account.account_name;
    document.getElementById('edit_parent_account_id').value = account.parent_account_id || '';
    document.getElementById('edit_description').value = account.description || '';
    document.getElementById('edit_is_active').value = account.is_active ? '1' : '0';
    document.getElementById('edit_is_header').checked = account.is_header == 1;

    new bootstrap.Modal(document.getElementById('editAccountModal')).show();
}
</script>
