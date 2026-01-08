<?php
$student = $student ?? [];
$invoices = $invoices ?? [];
$payments = $payments ?? [];
$totalBilled = $totalBilled ?? 0;
$totalPaid = $totalPaid ?? 0;
$balance = $balance ?? 0;
?>

<div class="container py-4">
    <!-- Back Button & Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="/parent/dashboard" class="btn btn-sm btn-outline-secondary mb-2">
                <i class="ti ti-arrow-left me-1"></i> Back
            </a>
            <h3 class="mb-0"><?= e($student['first_name'] . ' ' . $student['last_name']) ?></h3>
            <small class="text-muted"><?= e($student['admission_number'] ?? '') ?> | <?= e($student['class_name'] ?? 'No Class') ?></small>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-4">
            <div class="card stat-card bg-primary-lt">
                <div class="card-body text-center py-3">
                    <div class="h4 mb-0 text-primary">KES <?= number_format($totalBilled, 0) ?></div>
                    <small class="text-muted">Total Billed</small>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card stat-card bg-success-lt">
                <div class="card-body text-center py-3">
                    <div class="h4 mb-0 text-success">KES <?= number_format($totalPaid, 0) ?></div>
                    <small class="text-muted">Total Paid</small>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card stat-card <?= $balance > 0 ? 'bg-danger-lt' : 'bg-success-lt' ?>">
                <div class="card-body text-center py-3">
                    <div class="h4 mb-0 <?= $balance > 0 ? 'text-danger' : 'text-success' ?>">KES <?= number_format(abs($balance), 0) ?></div>
                    <small class="text-muted"><?= $balance > 0 ? 'Balance Due' : 'Credit' ?></small>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoices -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="ti ti-file-invoice me-2"></i> Invoices</h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($invoices)): ?>
                <div class="text-center py-4">
                    <i class="ti ti-file-off text-muted" style="font-size: 2rem;"></i>
                    <p class="text-muted mt-2 mb-0">No invoices found</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-vcenter mb-0">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Term</th>
                                <th class="text-end">Amount</th>
                                <th class="text-end">Paid</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoices as $invoice): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($invoice['invoice_number']) ?></strong>
                                        <br>
                                        <small class="text-muted"><?= date('M d, Y', strtotime($invoice['created_at'])) ?></small>
                                    </td>
                                    <td><?= e($invoice['term_name'] ?? '-') ?></td>
                                    <td class="text-end">KES <?= number_format($invoice['total_amount'], 0) ?></td>
                                    <td class="text-end">KES <?= number_format($invoice['amount_paid'], 0) ?></td>
                                    <td>
                                        <?php
                                        $statusColors = [
                                            'paid' => 'success',
                                            'partial' => 'warning',
                                            'unpaid' => 'danger',
                                            'cancelled' => 'secondary'
                                        ];
                                        $statusColor = $statusColors[$invoice['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $statusColor ?>-lt"><?= ucfirst($invoice['status']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Payments -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="ti ti-cash me-2"></i> Recent Payments</h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($payments)): ?>
                <div class="text-center py-4">
                    <i class="ti ti-cash-off text-muted" style="font-size: 2rem;"></i>
                    <p class="text-muted mt-2 mb-0">No payments recorded</p>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($payments as $payment): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>KES <?= number_format($payment['amount'], 0) ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <?= e($payment['payment_method'] ?? 'N/A') ?> |
                                        Ref: <?= e($payment['reference_number'] ?? 'N/A') ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted"><?= date('M d, Y', strtotime($payment['payment_date'])) ?></small>
                                    <br>
                                    <small class="text-muted">Invoice: <?= e($payment['invoice_number']) ?></small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
