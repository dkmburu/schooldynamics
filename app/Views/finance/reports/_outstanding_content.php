<?php
/**
 * Outstanding Balances Report - Content
 * Shows students with unpaid fee balances
 */

// Extract filter values
$minBalance = $filters['min_balance'] ?? '';
$selectedGrade = $filters['grade'] ?? '';
$selectedSort = $filters['sort'] ?? 'balance_desc';

$byGrade = $byGrade ?? [];
$topBalances = $topBalances ?? [];
$total = $total ?? ['count' => 0, 'total' => 0];
$grades = $grades ?? [];
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <a href="/finance" class="btn btn-ghost-secondary btn-sm mb-2">
                    <i class="ti ti-arrow-left me-1"></i>Back to Finance
                </a>
                <h2 class="page-title">
                    <i class="ti ti-alert-circle me-2"></i>
                    Outstanding Balances Report
                </h2>
                <div class="text-muted mt-1">Debtors list sorted by amount owed</div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <button type="button" class="btn btn-secondary" onclick="window.print()">
                    <i class="ti ti-printer me-1"></i>Print Report
                </button>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <!-- Filters -->
        <div class="card mb-3 d-print-none">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label">Minimum Balance</label>
                        <input type="number" name="min_balance" class="form-control" value="<?= e($minBalance) ?>" placeholder="e.g. 1000">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Grade</label>
                        <select name="grade" class="form-select">
                            <option value="">All Grades</option>
                            <?php foreach ($grades as $grade): ?>
                                <option value="<?= $grade['id'] ?>" <?= $selectedGrade == $grade['id'] ? 'selected' : '' ?>>
                                    <?= e($grade['grade_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ti ti-filter me-1"></i>Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row row-deck row-cards mb-3">
            <div class="col-sm-6 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Total Outstanding</div>
                        </div>
                        <div class="h1 mb-0 text-danger">
                            KES <?= number_format($total['total'], 2) ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Accounts with Balance</div>
                        </div>
                        <div class="h1 mb-0">
                            <?= number_format($total['count']) ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Average Balance</div>
                        </div>
                        <div class="h1 mb-0">
                            KES <?= $total['count'] > 0 ? number_format($total['total'] / $total['count'], 2) : '0.00' ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row row-deck row-cards mb-3">
            <!-- By Grade -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Outstanding by Grade</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($byGrade)): ?>
                            <p class="text-muted">No outstanding balances</p>
                        <?php else: ?>
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Grade</th>
                                        <th class="text-end">Students</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($byGrade as $grade): ?>
                                        <tr>
                                            <td><?= e($grade['grade_name'] ?: 'Unassigned') ?></td>
                                            <td class="text-end"><?= number_format($grade['account_count']) ?></td>
                                            <td class="text-end text-danger">KES <?= number_format($grade['total_outstanding'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                            <!-- Visual breakdown -->
                            <?php if ($total['total'] > 0): ?>
                                <div class="mt-3">
                                    <?php foreach ($byGrade as $grade):
                                        $percentage = ($grade['total_outstanding'] / $total['total']) * 100;
                                    ?>
                                        <div class="mb-2">
                                            <div class="d-flex justify-content-between mb-1">
                                                <small><?= e($grade['grade_name'] ?: 'Unassigned') ?></small>
                                                <small><?= number_format($percentage, 1) ?>%</small>
                                            </div>
                                            <div class="progress progress-sm">
                                                <div class="progress-bar bg-danger" style="width: <?= $percentage ?>%"></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Top Debtors -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Top 50 Outstanding Balances</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Account</th>
                                    <th>Student</th>
                                    <th>Grade</th>
                                    <th class="text-end">Balance</th>
                                    <th class="text-end d-print-none">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($topBalances)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="ti ti-check fs-1 mb-2 d-block opacity-50"></i>
                                            No outstanding balances found
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php $rank = 1; foreach ($topBalances as $account): ?>
                                        <tr>
                                            <td class="text-muted"><?= $rank++ ?></td>
                                            <td>
                                                <a href="/finance/student-accounts/<?= $account['id'] ?>" class="text-reset">
                                                    <?= e($account['account_number']) ?>
                                                </a>
                                            </td>
                                            <td>
                                                <div><?= e($account['first_name'] . ' ' . $account['last_name']) ?></div>
                                                <?php if (!empty($account['admission_number'])): ?>
                                                    <small class="text-muted"><?= e($account['admission_number']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= e($account['grade_name'] ?: '-') ?></td>
                                            <td class="text-end fw-bold text-danger">
                                                KES <?= number_format($account['current_balance'], 2) ?>
                                            </td>
                                            <td class="text-end d-print-none">
                                                <a href="/finance/student-accounts/<?= $account['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="ti ti-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .d-print-none { display: none !important; }
    .card { break-inside: avoid; }
    .page-body { padding: 0; }
    .container-xl { max-width: 100%; }
}
</style>
