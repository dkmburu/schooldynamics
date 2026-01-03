<?php
/**
 * Collection Report - Content
 * Shows fee collections by date, payment method, and category
 */

// Extract filter values from filters array passed by controller
$fromDate = $filters['from_date'] ?? date('Y-m-01');
$toDate = $filters['to_date'] ?? date('Y-m-d');
$selectedMethod = $filters['payment_method'] ?? '';
$selectedStatus = $filters['status'] ?? '';

$summary = $summary ?? ['total_transactions' => 0, 'total_collected' => 0, 'average_payment' => 0, 'collection_days' => 0];
$dailyBreakdown = $dailyBreakdown ?? [];
$byMethod = $byMethod ?? [];
$transactions = $transactions ?? [];
$paymentMethods = $paymentMethods ?? [];
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <a href="/finance" class="btn btn-ghost-secondary btn-sm mb-2">
                    <i class="ti ti-arrow-left me-1"></i>Back to Finance
                </a>
                <h2 class="page-title">
                    <i class="ti ti-report-money me-2"></i>
                    Collection Report
                </h2>
                <div class="text-muted mt-1">
                    <?= date('M j, Y', strtotime($fromDate)) ?> - <?= date('M j, Y', strtotime($toDate)) ?>
                </div>
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
                        <label class="form-label">From Date</label>
                        <input type="date" name="from_date" class="form-control" value="<?= e($fromDate) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">To Date</label>
                        <input type="date" name="to_date" class="form-control" value="<?= e($toDate) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select">
                            <option value="">All Methods</option>
                            <?php foreach ($paymentMethods as $method): ?>
                                <option value="<?= e($method['code']) ?>" <?= ($selectedMethod ?? '') === $method['code'] ? 'selected' : '' ?>>
                                    <?= e($method['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="confirmed" <?= ($selectedStatus ?? '') === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                            <option value="pending" <?= ($selectedStatus ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="bounced" <?= ($selectedStatus ?? '') === 'bounced' ? 'selected' : '' ?>>Bounced</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ti ti-search me-1"></i>Generate
                        </button>
                    </div>
                    <div class="col-md-2">
                        <div class="btn-group w-100">
                            <a href="?from_date=<?= date('Y-m-01') ?>&to_date=<?= date('Y-m-d') ?>&status=confirmed" class="btn btn-outline-secondary">This Month</a>
                            <a href="?from_date=<?= date('Y-01-01') ?>&to_date=<?= date('Y-m-d') ?>&status=confirmed" class="btn btn-outline-secondary">This Year</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row row-deck row-cards mb-3">
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Total Collected</div>
                        </div>
                        <div class="h1 mb-0 text-success">
                            KES <?= number_format($summary['total_collected'], 2) ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Transactions</div>
                        </div>
                        <div class="h1 mb-0">
                            <?= number_format($summary['total_transactions']) ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Average Payment</div>
                        </div>
                        <div class="h1 mb-0">
                            KES <?= number_format($summary['average_payment'], 2) ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Collection Days</div>
                        </div>
                        <div class="h1 mb-0">
                            <?= number_format($summary['collection_days']) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row row-deck row-cards mb-3">
            <!-- By Payment Method -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">By Payment Method</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($byMethod)): ?>
                            <p class="text-muted">No payments in selected period</p>
                        <?php else: ?>
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Method</th>
                                        <th class="text-end">Count</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($byMethod as $method): ?>
                                        <tr>
                                            <td><?= e($method['method_name']) ?></td>
                                            <td class="text-end"><?= number_format($method['transaction_count']) ?></td>
                                            <td class="text-end">KES <?= number_format($method['method_total'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                            <!-- Simple visual breakdown -->
                            <?php if ($summary['total_collected'] > 0): ?>
                                <div class="mt-3">
                                    <?php foreach ($byMethod as $method):
                                        $percentage = ($method['method_total'] / $summary['total_collected']) * 100;
                                        $colors = ['cash' => 'success', 'mpesa' => 'green', 'bank_transfer' => 'primary', 'cheque' => 'warning', 'card' => 'info', 'other' => 'secondary'];
                                        $color = $colors[$method['payment_method']] ?? 'secondary';
                                    ?>
                                        <div class="mb-2">
                                            <div class="d-flex justify-content-between mb-1">
                                                <small><?= e($method['method_name']) ?></small>
                                                <small><?= number_format($percentage, 1) ?>%</small>
                                            </div>
                                            <div class="progress progress-sm">
                                                <div class="progress-bar bg-<?= $color ?>" style="width: <?= $percentage ?>%"></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Daily Breakdown -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Daily Collections</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($dailyBreakdown)): ?>
                            <p class="text-muted">No collections in selected period</p>
                        <?php else: ?>
                            <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                <table class="table table-sm table-hover">
                                    <thead class="sticky-top bg-white">
                                        <tr>
                                            <th>Date</th>
                                            <th>Day</th>
                                            <th class="text-end">Transactions</th>
                                            <th class="text-end">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($dailyBreakdown as $day): ?>
                                            <tr>
                                                <td><?= date('M j, Y', strtotime($day['payment_date'])) ?></td>
                                                <td><?= date('l', strtotime($day['payment_date'])) ?></td>
                                                <td class="text-end"><?= number_format($day['transaction_count']) ?></td>
                                                <td class="text-end fw-bold">KES <?= number_format($day['daily_total'], 2) ?></td>
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

        <!-- Transaction List -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Transaction Details</h3>
                <div class="card-actions">
                    <span class="text-muted"><?= count($transactions) ?> transactions</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-hover">
                    <thead>
                        <tr>
                            <th>Receipt #</th>
                            <th>Date</th>
                            <th>Payer</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th class="text-end">Amount</th>
                            <th>Status</th>
                            <th>Received By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="ti ti-receipt-off fs-1 mb-2 d-block opacity-50"></i>
                                    No transactions found for the selected period
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $txn): ?>
                                <tr>
                                    <td>
                                        <a href="/finance/payments/<?= $txn['id'] ?>" class="text-reset">
                                            <?= e($txn['receipt_number']) ?>
                                        </a>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($txn['payment_date'])) ?></td>
                                    <td>
                                        <div><?= e($txn['payer_name'] ?: 'N/A') ?></div>
                                        <small class="text-muted"><?= e($txn['payer_ref'] ?: '') ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $methodIcons = ['cash' => 'ti-cash', 'mpesa' => 'ti-device-mobile', 'bank_transfer' => 'ti-building-bank', 'cheque' => 'ti-file-check', 'card' => 'ti-credit-card'];
                                        $icon = $methodIcons[$txn['payment_method']] ?? 'ti-wallet';
                                        ?>
                                        <i class="ti <?= $icon ?> me-1"></i>
                                        <?= e($txn['method_name']) ?>
                                    </td>
                                    <td>
                                        <code><?= e($txn['reference_number'] ?: '-') ?></code>
                                    </td>
                                    <td class="text-end fw-bold">
                                        KES <?= number_format($txn['amount'], 2) ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusColors = ['confirmed' => 'success', 'pending' => 'warning', 'bounced' => 'danger', 'refunded' => 'secondary'];
                                        $color = $statusColors[$txn['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $color ?>-lt"><?= ucfirst($txn['status']) ?></span>
                                    </td>
                                    <td class="text-muted"><?= e($txn['received_by_name'] ?: '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
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
