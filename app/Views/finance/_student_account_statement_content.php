<?php
/**
 * Student Account Statement - Content
 */
$name = $account['student_first_name']
    ? $account['student_first_name'] . ' ' . $account['student_last_name']
    : $account['applicant_first_name'] . ' ' . $account['applicant_last_name'];
$ref = $account['admission_number'] ?? $account['application_ref'] ?? '';
$isStudent = !empty($account['student_id']);
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <a href="/finance/student-accounts" class="btn btn-ghost-secondary btn-sm mb-2">
                    <i class="ti ti-arrow-left me-1"></i>Back to Student Accounts
                </a>
                <h2 class="page-title">
                    <i class="ti ti-file-text me-2"></i>
                    Account Statement
                </h2>
                <div class="text-muted mt-1">
                    <?= e($name) ?> <?php if ($ref): ?>(<?= e($ref) ?>)<?php endif; ?>
                </div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <a href="/finance/payments/record?account=<?= $account['id'] ?>" class="btn btn-primary">
                        <i class="ti ti-cash me-1"></i>Record Payment
                    </a>
                    <button type="button" class="btn btn-secondary" onclick="window.print()">
                        <i class="ti ti-printer me-1"></i>Print
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <!-- Account Summary -->
        <div class="row row-deck row-cards mb-3">
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Account Number</div>
                        </div>
                        <div class="h2 mb-0"><?= e($account['account_number']) ?></div>
                        <div class="text-muted small"><?= e($account['grade_name'] ?? 'N/A') ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Total Invoiced</div>
                        </div>
                        <div class="h2 mb-0">KES <?= number_format($totalInvoiced, 2) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Total Paid</div>
                        </div>
                        <div class="h2 mb-0 text-success">KES <?= number_format($totalPaid, 2) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Balance Due</div>
                        </div>
                        <div class="h2 mb-0 <?= $balance > 0 ? 'text-danger' : ($balance < 0 ? 'text-info' : 'text-success') ?>">
                            KES <?= number_format($balance, 2) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statement Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Transaction History</h3>
                <div class="card-actions">
                    <span class="badge bg-blue"><?= count($statement) ?> transactions</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reference</th>
                            <th>Description</th>
                            <th class="text-end">Debit</th>
                            <th class="text-end">Credit</th>
                            <th class="text-end">Balance</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($statement)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="ti ti-file-off fs-1 mb-2 d-block opacity-50"></i>
                                No transactions found
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($statement as $entry): ?>
                            <tr>
                                <td><?= date('M j, Y', strtotime($entry['date'])) ?></td>
                                <td>
                                    <?php if ($entry['type'] === 'invoice'): ?>
                                        <a href="/finance/invoices/view/<?= $entry['reference'] ?>" class="text-reset">
                                            <?= e($entry['reference']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-success"><?= e($entry['reference']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($entry['type'] === 'invoice'): ?>
                                        <i class="ti ti-file-invoice me-1 text-muted"></i>
                                    <?php else: ?>
                                        <i class="ti ti-cash me-1 text-success"></i>
                                    <?php endif; ?>
                                    <?= e($entry['description']) ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($entry['debit'] > 0): ?>
                                        <span class="text-danger">KES <?= number_format($entry['debit'], 2) ?></span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($entry['credit'] > 0): ?>
                                        <span class="text-success">KES <?= number_format($entry['credit'], 2) ?></span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-bold <?= $entry['balance'] > 0 ? 'text-danger' : 'text-success' ?>">
                                    KES <?= number_format($entry['balance'], 2) ?>
                                </td>
                                <td>
                                    <?php
                                    $statusColors = [
                                        'confirmed' => 'success',
                                        'pending' => 'warning',
                                        'draft' => 'secondary',
                                        'paid' => 'success',
                                        'partial' => 'info',
                                        'overdue' => 'danger',
                                        'cancelled' => 'dark',
                                        'bounced' => 'danger'
                                    ];
                                    $color = $statusColors[$entry['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $color ?>-lt"><?= ucfirst($entry['status']) ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <?php if (!empty($statement)): ?>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="3" class="text-end fw-bold">Totals:</td>
                            <td class="text-end fw-bold text-danger">KES <?= number_format($totalInvoiced, 2) ?></td>
                            <td class="text-end fw-bold text-success">KES <?= number_format($totalPaid, 2) ?></td>
                            <td class="text-end fw-bold <?= $balance > 0 ? 'text-danger' : 'text-success' ?>">
                                KES <?= number_format($balance, 2) ?>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="row mt-3 d-print-none">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="btn-list">
                            <a href="/finance/invoices?account=<?= $account['id'] ?>" class="btn btn-outline-primary">
                                <i class="ti ti-file-invoice me-1"></i>View All Invoices (<?= count($invoices) ?>)
                            </a>
                            <a href="/finance/payments?account=<?= $account['id'] ?>" class="btn btn-outline-success">
                                <i class="ti ti-receipt me-1"></i>View All Payments (<?= count($payments) ?>)
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .d-print-none { display: none !important; }
    .card { break-inside: avoid; border: 1px solid #dee2e6; }
    .page-body { padding: 0; }
    .container-xl { max-width: 100%; }
    .table th, .table td { padding: 0.5rem; }
}
</style>
