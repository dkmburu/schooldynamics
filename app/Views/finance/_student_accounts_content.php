<?php
/**
 * Student Accounts - Content
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
                    <i class="ti ti-user-dollar me-2"></i>
                    Student Accounts
                </h2>
                <div class="text-muted mt-1">
                    View student balances and financial statements
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
                            <div class="subheader">Total Accounts</div>
                        </div>
                        <div class="h1 mb-0"><?= number_format($stats['total_accounts'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Active Accounts</div>
                        </div>
                        <div class="h1 mb-0 text-success"><?= number_format($stats['active_accounts'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Total Outstanding</div>
                        </div>
                        <div class="h1 mb-0 text-danger">KES <?= number_format($stats['total_outstanding'] ?? 0, 2) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="/finance/student-accounts" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control"
                               placeholder="Account #, student name, admission #..."
                               value="<?= e($search ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Account Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="active" <?= ($status ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="pending" <?= ($status ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="suspended" <?= ($status ?? '') === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                            <option value="closed" <?= ($status ?? '') === 'closed' ? 'selected' : '' ?>>Closed</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Balance</label>
                        <select name="balance" class="form-select">
                            <option value="">All Balances</option>
                            <option value="owing" <?= ($balanceFilter ?? '') === 'owing' ? 'selected' : '' ?>>Owing (Balance > 0)</option>
                            <option value="credit" <?= ($balanceFilter ?? '') === 'credit' ? 'selected' : '' ?>>Credit (Balance < 0)</option>
                            <option value="zero" <?= ($balanceFilter ?? '') === 'zero' ? 'selected' : '' ?>>Cleared (Balance = 0)</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ti ti-search me-1"></i>Filter
                        </button>
                    </div>
                    <?php if (!empty($search) || !empty($status) || !empty($balanceFilter)): ?>
                    <div class="col-md-2 d-flex align-items-end">
                        <a href="/finance/student-accounts" class="btn btn-secondary w-100">
                            <i class="ti ti-x me-1"></i>Clear
                        </a>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Accounts Table -->
        <div class="card" style="overflow: visible;">
            <div class="card-header">
                <h3 class="card-title">Student Fee Accounts</h3>
                <div class="card-actions">
                    <span class="badge bg-blue"><?= count($accounts ?? []) ?> accounts</span>
                </div>
            </div>
            <div class="card-body p-0" style="overflow: visible;">
                <?php if (empty($accounts)): ?>
                <div class="empty py-5">
                    <div class="empty-img">
                        <i class="ti ti-user-dollar" style="font-size: 4rem; color: #adb5bd;"></i>
                    </div>
                    <p class="empty-title">No accounts found</p>
                    <p class="empty-subtitle text-muted">
                        <?php if (!empty($search) || !empty($status) || !empty($balanceFilter)): ?>
                        Try adjusting your search filters.
                        <?php else: ?>
                        Student accounts are created when students are admitted.
                        <?php endif; ?>
                    </p>
                </div>
                <?php else: ?>
                <div class="table-responsive" style="overflow: visible;">
                    <table class="table table-vcenter card-table table-hover">
                        <thead>
                            <tr>
                                <th>Account #</th>
                                <th>Student / Applicant</th>
                                <th>Grade</th>
                                <th class="text-end">Total Invoiced</th>
                                <th class="text-end">Total Paid</th>
                                <th class="text-end">Balance</th>
                                <th>Status</th>
                                <th class="w-1">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($accounts as $account): ?>
                            <?php
                            // Determine name and reference
                            $name = $account['student_first_name']
                                ? $account['student_first_name'] . ' ' . $account['student_last_name']
                                : $account['applicant_first_name'] . ' ' . $account['applicant_last_name'];
                            $ref = $account['admission_number'] ?? $account['application_ref'] ?? '';
                            $isStudent = !empty($account['student_id']);

                            // Calculate balance
                            $totalInvoiced = $account['total_invoiced'] ?? 0;
                            $totalPaid = $account['total_paid'] ?? 0;
                            $balance = $totalInvoiced - $totalPaid;
                            ?>
                            <tr>
                                <td>
                                    <strong><?= e($account['account_number']) ?></strong>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="avatar avatar-sm bg-<?= $isStudent ? 'primary' : 'secondary' ?>-lt me-2">
                                            <?= strtoupper(substr($name, 0, 1)) ?>
                                        </span>
                                        <div>
                                            <?php if ($isStudent): ?>
                                            <a href="/students/<?= $account['student_id'] ?>" class="text-reset">
                                                <?= e($name) ?>
                                            </a>
                                            <?php else: ?>
                                            <a href="/applicants/<?= $account['applicant_id'] ?>" class="text-reset">
                                                <?= e($name) ?>
                                            </a>
                                            <span class="badge bg-secondary-lt ms-1">Applicant</span>
                                            <?php endif; ?>
                                            <?php if ($ref): ?>
                                            <div class="text-muted small"><?= e($ref) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?= e($account['grade_name'] ?? 'N/A') ?>
                                </td>
                                <td class="text-end">
                                    KES <?= number_format($totalInvoiced, 2) ?>
                                </td>
                                <td class="text-end text-success">
                                    KES <?= number_format($totalPaid, 2) ?>
                                </td>
                                <td class="text-end">
                                    <strong class="<?= $balance > 0 ? 'text-danger' : ($balance < 0 ? 'text-info' : 'text-success') ?>">
                                        KES <?= number_format($balance, 2) ?>
                                    </strong>
                                </td>
                                <td>
                                    <?php
                                    $statusColors = [
                                        'active' => 'bg-success',
                                        'pending' => 'bg-warning',
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
                                            <a href="/finance/student-accounts/<?= $account['id'] ?>" class="dropdown-item">
                                                <i class="ti ti-eye me-2"></i>View Statement
                                            </a>
                                            <a href="/finance/invoices?account=<?= $account['id'] ?>" class="dropdown-item">
                                                <i class="ti ti-file-invoice me-2"></i>View Invoices
                                            </a>
                                            <a href="/finance/payments?account=<?= $account['id'] ?>" class="dropdown-item">
                                                <i class="ti ti-receipt me-2"></i>View Payments
                                            </a>
                                            <div class="dropdown-divider"></div>
                                            <a href="/finance/payments/record?account=<?= $account['id'] ?>" class="dropdown-item">
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
