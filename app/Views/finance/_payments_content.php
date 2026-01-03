<?php
/**
 * Payments History - Content
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
                    <i class="ti ti-receipt-2 me-2"></i>
                    Payment History
                </h2>
            </div>
            <div class="col-auto ms-auto">
                <a href="/finance/payments/record" class="btn btn-primary">
                    <i class="ti ti-cash me-2"></i>Record Payment
                </a>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <!-- Summary Cards -->
        <div class="row row-deck row-cards mb-3">
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Total Payments</div>
                        </div>
                        <div class="h1 mb-0"><?= number_format($totalPayments ?? 0) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Total Amount (Confirmed)</div>
                        </div>
                        <div class="h1 mb-0 text-success">KES <?= number_format($totalAmount ?? 0, 2) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="/finance/payments" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Receipt, reference, name..."
                               value="<?= e($search ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="confirmed" <?= ($status ?? '') === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                            <option value="pending" <?= ($status ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="cancelled" <?= ($status ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select">
                            <option value="">All Methods</option>
                            <?php foreach ($paymentMethods ?? [] as $method): ?>
                            <option value="<?= e($method) ?>" <?= ($paymentMethod ?? '') === $method ? 'selected' : '' ?>>
                                <?= ucfirst(str_replace('_', ' ', $method)) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="<?= e($dateFrom ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" class="form-control" value="<?= e($dateTo ?? '') ?>">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ti ti-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Payments Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Payments</h3>
                <div class="card-actions">
                    <span class="badge bg-blue"><?= count($payments ?? []) ?> records</span>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($payments)): ?>
                <div class="empty py-5">
                    <div class="empty-img">
                        <i class="ti ti-receipt-2" style="font-size: 4rem; color: #adb5bd;"></i>
                    </div>
                    <p class="empty-title">No payments found</p>
                    <p class="empty-subtitle text-muted">
                        <?php if (!empty($search) || !empty($status) || !empty($dateFrom)): ?>
                        Try adjusting your search filters.
                        <?php else: ?>
                        Record payments as they are received from students/parents.
                        <?php endif; ?>
                    </p>
                    <div class="empty-action">
                        <a href="/finance/payments/record" class="btn btn-primary">
                            <i class="ti ti-cash me-2"></i>Record First Payment
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover">
                        <thead>
                            <tr>
                                <th>Receipt #</th>
                                <th>Date</th>
                                <th>Payer</th>
                                <th>Account</th>
                                <th>Method</th>
                                <th>Reference</th>
                                <th class="text-end">Amount</th>
                                <th>Status</th>
                                <th class="w-1">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td>
                                    <strong><?= e($payment['receipt_number']) ?></strong>
                                </td>
                                <td>
                                    <?= date('M j, Y', strtotime($payment['payment_date'])) ?>
                                    <div class="text-muted small"><?= date('g:i A', strtotime($payment['created_at'])) ?></div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php
                                        $payerName = $payment['payer_first_name'] . ' ' . $payment['payer_last_name'];
                                        $payerRef = $payment['payer_ref'] ?? '';
                                        ?>
                                        <span class="avatar avatar-sm bg-primary-lt me-2">
                                            <?= strtoupper(substr($payment['payer_first_name'] ?? 'N', 0, 1)) ?>
                                        </span>
                                        <div>
                                            <div><?= e($payerName) ?></div>
                                            <?php if ($payerRef): ?>
                                            <div class="text-muted small"><?= e($payerRef) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?= e($payment['account_number'] ?? 'N/A') ?>
                                </td>
                                <td>
                                    <?php
                                    $methodIcons = [
                                        'cash' => 'ti-cash',
                                        'mpesa' => 'ti-device-mobile',
                                        'bank_transfer' => 'ti-building-bank',
                                        'cheque' => 'ti-file-text',
                                        'card' => 'ti-credit-card'
                                    ];
                                    $icon = $methodIcons[$payment['payment_method']] ?? 'ti-cash';
                                    ?>
                                    <span class="badge bg-blue-lt">
                                        <i class="ti <?= $icon ?> me-1"></i>
                                        <?= ucfirst(str_replace('_', ' ', $payment['payment_method'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?= e($payment['reference_number'] ?? '-') ?>
                                </td>
                                <td class="text-end">
                                    <strong>KES <?= number_format($payment['amount'], 2) ?></strong>
                                </td>
                                <td>
                                    <?php
                                    $statusColors = [
                                        'confirmed' => 'bg-success',
                                        'pending' => 'bg-warning',
                                        'cancelled' => 'bg-danger'
                                    ];
                                    $statusColor = $statusColors[$payment['status']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $statusColor ?>"><?= ucfirst($payment['status']) ?></span>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            Actions
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a href="/finance/payments/<?= $payment['id'] ?>" class="dropdown-item">
                                                <i class="ti ti-eye me-2"></i>View Details
                                            </a>
                                            <a href="/finance/payments/<?= $payment['id'] ?>/receipt" class="dropdown-item" target="_blank">
                                                <i class="ti ti-printer me-2"></i>Print Receipt
                                            </a>
                                            <?php if ($payment['status'] === 'pending'): ?>
                                            <div class="dropdown-divider"></div>
                                            <a href="#" class="dropdown-item text-success" onclick="confirmPayment(<?= $payment['id'] ?>); return false;">
                                                <i class="ti ti-check me-2"></i>Confirm Payment
                                            </a>
                                            <a href="#" class="dropdown-item text-danger" onclick="cancelPayment(<?= $payment['id'] ?>); return false;">
                                                <i class="ti ti-x me-2"></i>Cancel Payment
                                            </a>
                                            <?php endif; ?>
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

<script>
function confirmPayment(id) {
    if (confirm('Confirm this payment? This will update account balances.')) {
        fetch(`/finance/payments/${id}/confirm`, { method: 'POST' })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => alert('Error confirming payment'));
    }
}

function cancelPayment(id) {
    if (confirm('Cancel this payment? This cannot be undone.')) {
        fetch(`/finance/payments/${id}/cancel`, { method: 'POST' })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => alert('Error cancelling payment'));
    }
}
</script>
