<?php
/**
 * View Invoice Content
 * Displays a single invoice with line items and payment history
 */

// Determine who the invoice is for (applicant or student)
$isApplicant = !empty($invoice['applicant_id']);
$personName = $isApplicant
    ? trim($invoice['applicant_first_name'] . ' ' . $invoice['applicant_last_name'])
    : trim($invoice['student_first_name'] . ' ' . $invoice['student_last_name']);
$personRef = $isApplicant
    ? $invoice['application_ref']
    : $invoice['admission_number'];
$personGrade = $isApplicant
    ? $invoice['applicant_grade']
    : ($invoice['student_grade'] ?? 'N/A');

// Status badge colors
$statusColors = [
    'draft' => 'secondary',
    'pending' => 'warning',
    'partial' => 'info',
    'paid' => 'success',
    'cancelled' => 'danger',
    'refunded' => 'purple'
];
$statusColor = $statusColors[$invoice['status']] ?? 'secondary';

// Invoice type labels
$typeLabels = [
    'admission' => 'Admission Invoice',
    'term_fees' => 'Term Fees Invoice',
    'other' => 'Other Invoice'
];
$typeLabel = $typeLabels[$invoice['invoice_type']] ?? 'Invoice';
?>

<!-- Page Header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <a href="javascript:history.back()" class="btn btn-ghost-secondary btn-sm mb-2">
                    <i class="ti ti-arrow-left me-1"></i>Back
                </a>
                <h2 class="page-title">
                    <i class="ti ti-file-invoice me-2"></i><?= e($invoice['invoice_number']) ?>
                </h2>
                <div class="text-muted mt-1">
                    <?= $typeLabel ?>
                    <span class="badge bg-<?= $statusColor ?>-lt ms-2"><?= ucfirst($invoice['status']) ?></span>
                </div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                        <i class="ti ti-printer me-1"></i>Print
                    </button>
                    <?php if ($invoice['status'] === 'pending' || $invoice['status'] === 'partial'): ?>
                    <a href="/finance/payments/record?invoice_id=<?= $invoice['id'] ?>" class="btn btn-success">
                        <i class="ti ti-cash me-1"></i>Record Payment
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <!-- Invoice Card -->
        <div class="card" id="printable-invoice">
            <div class="card-body">
                <!-- Header Section -->
                <div class="row mb-4">
                    <div class="col-6">
                        <!-- School Info -->
                        <h3 class="mb-1"><?= e($school['school_name'] ?? 'School Name') ?></h3>
                        <?php if (!empty($school['address'])): ?>
                        <div class="text-muted"><?= nl2br(e($school['address'])) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($school['phone'])): ?>
                        <div class="text-muted"><i class="ti ti-phone me-1"></i><?= e($school['phone']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($school['email'])): ?>
                        <div class="text-muted"><i class="ti ti-mail me-1"></i><?= e($school['email']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-6 text-end">
                        <h1 class="display-6 text-primary mb-0"><?= strtoupper($typeLabel) ?></h1>
                        <div class="h2 mb-0"><?= e($invoice['invoice_number']) ?></div>
                        <div class="text-muted mt-2">
                            <div><strong>Date:</strong> <?= formatDate($invoice['invoice_date']) ?></div>
                            <div><strong>Due:</strong> <?= formatDate($invoice['due_date']) ?></div>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Bill To Section -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5 class="text-muted mb-2">Bill To</h5>
                        <div class="fw-bold fs-4"><?= e($personName) ?></div>
                        <div class="text-muted">
                            <?php if ($isApplicant): ?>
                            <div><strong>Application Ref:</strong> <?= e($personRef) ?></div>
                            <?php else: ?>
                            <div><strong>Admission No:</strong> <?= e($personRef) ?></div>
                            <?php endif; ?>
                            <div><strong>Grade:</strong> <?= e($personGrade) ?></div>
                            <?php if (!empty($invoice['account_number'])): ?>
                            <div><strong>Account:</strong> <?= e($invoice['account_number']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <h5 class="text-muted mb-2">Invoice Details</h5>
                        <div>
                            <strong>Academic Year:</strong> <?= e($invoice['year_name'] ?? 'N/A') ?>
                        </div>
                        <?php if (!empty($invoice['term_name'])): ?>
                        <div>
                            <strong>Term:</strong> <?= e($invoice['term_name']) ?>
                        </div>
                        <?php endif; ?>
                        <div class="mt-2">
                            <span class="badge bg-<?= $statusColor ?> fs-5"><?= ucfirst($invoice['status']) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Line Items Table -->
                <div class="table-responsive mb-4">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Description</th>
                                <th class="text-center" style="width: 80px;">Qty</th>
                                <th class="text-end" style="width: 150px;">Unit Price</th>
                                <th class="text-end" style="width: 120px;">Discount</th>
                                <th class="text-end" style="width: 150px;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($lines)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No line items</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($lines as $index => $line): ?>
                            <tr>
                                <td class="text-muted"><?= $index + 1 ?></td>
                                <td>
                                    <div class="fw-medium"><?= e($line['fee_item_name'] ?? $line['description']) ?></div>
                                    <?php if (!empty($line['category_name'])): ?>
                                    <small class="text-muted"><?= e($line['category_name']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?= $line['quantity'] ?></td>
                                <td class="text-end"><?= number_format($line['unit_amount'], 2) ?></td>
                                <td class="text-end">
                                    <?php if ($line['discount_amount'] > 0): ?>
                                    <span class="text-danger">-<?= number_format($line['discount_amount'], 2) ?></span>
                                    <?php else: ?>
                                    -
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-medium"><?= number_format($line['line_total'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" class="text-end"><strong>Subtotal</strong></td>
                                <td class="text-end"><?= number_format($invoice['subtotal'], 2) ?></td>
                            </tr>
                            <?php if ($invoice['discount_amount'] > 0): ?>
                            <tr>
                                <td colspan="5" class="text-end text-danger"><strong>Discount</strong></td>
                                <td class="text-end text-danger">-<?= number_format($invoice['discount_amount'], 2) ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr class="table-primary">
                                <td colspan="5" class="text-end"><strong class="fs-4">Total</strong></td>
                                <td class="text-end"><strong class="fs-4">KES <?= number_format($invoice['total_amount'], 2) ?></strong></td>
                            </tr>
                            <?php if ($invoice['amount_paid'] > 0): ?>
                            <tr>
                                <td colspan="5" class="text-end text-success"><strong>Amount Paid</strong></td>
                                <td class="text-end text-success">-<?= number_format($invoice['amount_paid'], 2) ?></td>
                            </tr>
                            <tr class="<?= $invoice['balance'] > 0 ? 'table-warning' : 'table-success' ?>">
                                <td colspan="5" class="text-end"><strong class="fs-4">Balance Due</strong></td>
                                <td class="text-end"><strong class="fs-4">KES <?= number_format($invoice['balance'], 2) ?></strong></td>
                            </tr>
                            <?php endif; ?>
                        </tfoot>
                    </table>
                </div>

                <!-- Notes -->
                <?php if (!empty($invoice['notes'])): ?>
                <div class="mb-4">
                    <h5 class="text-muted">Notes</h5>
                    <p class="mb-0"><?= nl2br(e($invoice['notes'])) ?></p>
                </div>
                <?php endif; ?>

                <!-- Payment Instructions (print only) -->
                <div class="d-none d-print-block mt-4 pt-4 border-top">
                    <h5>Payment Instructions</h5>
                    <p class="text-muted mb-0">
                        Please make payment to the school's official bank account and reference this invoice number: <strong><?= e($invoice['invoice_number']) ?></strong>
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <div class="card-footer text-muted d-print-none">
                <div class="row">
                    <div class="col">
                        <small>
                            Created: <?= formatDateTime($invoice['created_at']) ?>
                            <?php if (!empty($invoice['created_by_name'])): ?>
                            by <?= e($invoice['created_by_name']) ?>
                            <?php endif; ?>
                        </small>
                    </div>
                    <div class="col-auto">
                        <small>Invoice ID: <?= $invoice['id'] ?></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment History -->
        <?php if (!empty($payments)): ?>
        <div class="card mt-4 d-print-none">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="ti ti-cash me-2"></i>Payment History
                </h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Receipt #</th>
                            <th>Date</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th class="text-end">Amount</th>
                            <th>Recorded By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><strong><?= e($payment['receipt_number'] ?? 'N/A') ?></strong></td>
                            <td><?= formatDate($payment['payment_date']) ?></td>
                            <td><?= e($payment['method_name'] ?? 'Cash') ?></td>
                            <td><?= e($payment['reference_number'] ?? '-') ?></td>
                            <td class="text-end text-success fw-medium">KES <?= number_format($payment['amount'], 2) ?></td>
                            <td><?= e($payment['recorded_by_name'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
@media print {
    .page-header,
    .navbar,
    .footer,
    .d-print-none {
        display: none !important;
    }
    .page-body {
        padding: 0 !important;
        margin: 0 !important;
    }
    .container-xl {
        max-width: 100% !important;
        padding: 0 !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    body {
        background: white !important;
    }
}
</style>
