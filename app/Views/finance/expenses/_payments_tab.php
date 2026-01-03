<?php
/**
 * Supplier Payments Tab Content
 */
$payments = $tabData['payments'] ?? [];
$suppliers = $tabData['suppliers'] ?? [];
$filters = $tabData['filters'] ?? [];
?>

<!-- Filters & Actions -->
<div class="row mb-3">
    <div class="col-md-9">
        <form method="GET" action="/finance/expenses/payments" class="row g-2">
            <div class="col-md-2">
                <input type="text" name="search" class="form-control" placeholder="Search..."
                    value="<?= e($filters['search'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <select name="supplier" class="form-select">
                    <option value="">All Suppliers</option>
                    <?php foreach ($suppliers as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= ($filters['supplier'] ?? '') == $s['id'] ? 'selected' : '' ?>>
                            <?= e($s['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="draft" <?= ($filters['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="pending_approval" <?= ($filters['status'] ?? '') === 'pending_approval' ? 'selected' : '' ?>>Pending</option>
                    <option value="approved" <?= ($filters['status'] ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="paid" <?= ($filters['status'] ?? '') === 'paid' ? 'selected' : '' ?>>Paid</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="from_date" class="form-control" value="<?= e($filters['from_date'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <input type="date" name="to_date" class="form-control" value="<?= e($filters['to_date'] ?? '') ?>">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="ti ti-search"></i>
                </button>
            </div>
        </form>
    </div>
    <div class="col-md-3 text-end">
        <button type="button" class="btn btn-success" onclick="alert('Record Payment modal coming soon')">
            <i class="ti ti-plus me-1"></i>Record Payment
        </button>
    </div>
</div>

<?php if (empty($payments)): ?>
    <div class="empty py-5">
        <div class="empty-img">
            <i class="ti ti-cash" style="font-size: 4rem; color: #adb5bd;"></i>
        </div>
        <p class="empty-title">No supplier payments found</p>
        <p class="empty-subtitle text-muted">
            Record payments to suppliers to track your outgoing cash.
        </p>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-hover">
            <thead>
                <tr>
                    <th>Payment #</th>
                    <th>Supplier</th>
                    <th>Date</th>
                    <th>Method</th>
                    <th>Reference</th>
                    <th class="text-end">Amount</th>
                    <th>Status</th>
                    <th>Prepared By</th>
                    <th class="w-1">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $pmt): ?>
                    <tr>
                        <td><strong><?= e($pmt['payment_number']) ?></strong></td>
                        <td><?= e($pmt['supplier_name']) ?></td>
                        <td><?= date('M j, Y', strtotime($pmt['payment_date'])) ?></td>
                        <td>
                            <?php
                            $methodLabels = [
                                'cash' => 'Cash',
                                'cheque' => 'Cheque',
                                'bank_transfer' => 'Bank Transfer',
                                'mpesa' => 'M-Pesa',
                                'rtgs' => 'RTGS'
                            ];
                            ?>
                            <span class="badge bg-secondary-lt"><?= $methodLabels[$pmt['payment_method']] ?? ucfirst($pmt['payment_method']) ?></span>
                        </td>
                        <td><?= e($pmt['reference_number'] ?? '-') ?></td>
                        <td class="text-end fw-bold text-success">KES <?= number_format($pmt['amount'], 2) ?></td>
                        <td>
                            <?php
                            $statusColors = [
                                'draft' => 'bg-secondary',
                                'pending_approval' => 'bg-warning',
                                'approved' => 'bg-info',
                                'paid' => 'bg-success',
                                'cancelled' => 'bg-danger'
                            ];
                            $color = $statusColors[$pmt['status']] ?? 'bg-secondary';
                            ?>
                            <span class="badge <?= $color ?>"><?= ucfirst(str_replace('_', ' ', $pmt['status'])) ?></span>
                        </td>
                        <td><?= e($pmt['prepared_by_name'] ?? '-') ?></td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    Actions
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a href="javascript:void(0)" class="dropdown-item" onclick="alert('View coming soon')">
                                        <i class="ti ti-eye me-2"></i>View
                                    </a>
                                    <a href="javascript:void(0)" class="dropdown-item" onclick="alert('Print coming soon')">
                                        <i class="ti ti-printer me-2"></i>Print Voucher
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
