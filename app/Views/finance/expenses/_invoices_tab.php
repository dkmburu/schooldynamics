<?php
/**
 * Supplier Invoices Tab Content
 */
$invoices = $tabData['invoices'] ?? [];
$suppliers = $tabData['suppliers'] ?? [];
$filters = $tabData['filters'] ?? [];
?>

<!-- Filters & Actions -->
<div class="row mb-3">
    <div class="col-md-8">
        <form method="GET" action="/finance/expenses/invoices" class="row g-2">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Search invoice#..."
                    value="<?= e($filters['search'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <select name="supplier" class="form-select">
                    <option value="">All Suppliers</option>
                    <?php foreach ($suppliers as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= ($filters['supplier'] ?? '') == $s['id'] ? 'selected' : '' ?>>
                            <?= e($s['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="approved" <?= ($filters['status'] ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="partial" <?= ($filters['status'] ?? '') === 'partial' ? 'selected' : '' ?>>Partially Paid</option>
                    <option value="paid" <?= ($filters['status'] ?? '') === 'paid' ? 'selected' : '' ?>>Paid</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="ti ti-search me-1"></i>Filter
                </button>
            </div>
        </form>
    </div>
    <div class="col-md-4 text-end">
        <button type="button" class="btn btn-success" onclick="alert('Create Invoice modal coming soon')">
            <i class="ti ti-plus me-1"></i>Add Invoice
        </button>
    </div>
</div>

<?php if (empty($invoices)): ?>
    <div class="empty py-5">
        <div class="empty-img">
            <i class="ti ti-receipt" style="font-size: 4rem; color: #adb5bd;"></i>
        </div>
        <p class="empty-title">No supplier invoices found</p>
        <p class="empty-subtitle text-muted">
            Record supplier invoices to track what you owe.
        </p>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-hover">
            <thead>
                <tr>
                    <th>Our Ref</th>
                    <th>Invoice #</th>
                    <th>Supplier</th>
                    <th>Invoice Date</th>
                    <th>Due Date</th>
                    <th class="text-end">Total</th>
                    <th class="text-end">Balance</th>
                    <th>Status</th>
                    <th class="w-1">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $inv): ?>
                    <tr>
                        <td><strong><?= e($inv['internal_ref']) ?></strong></td>
                        <td><?= e($inv['invoice_number']) ?></td>
                        <td><?= e($inv['supplier_name']) ?></td>
                        <td><?= date('M j, Y', strtotime($inv['invoice_date'])) ?></td>
                        <td>
                            <?php
                            $daysTo = (int)$inv['days_to_due'];
                            $dueClass = '';
                            if ($daysTo < 0) $dueClass = 'text-danger fw-bold';
                            elseif ($daysTo <= 7) $dueClass = 'text-warning';
                            ?>
                            <span class="<?= $dueClass ?>">
                                <?= date('M j, Y', strtotime($inv['due_date'])) ?>
                                <?php if ($daysTo < 0): ?>
                                    <small>(<?= abs($daysTo) ?> days overdue)</small>
                                <?php elseif ($daysTo == 0): ?>
                                    <small>(Due today)</small>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td class="text-end">KES <?= number_format($inv['total_amount'], 2) ?></td>
                        <td class="text-end fw-bold <?= $inv['balance'] > 0 ? 'text-danger' : 'text-success' ?>">
                            KES <?= number_format($inv['balance'], 2) ?>
                        </td>
                        <td>
                            <?php
                            $statusColors = [
                                'draft' => 'bg-secondary',
                                'pending' => 'bg-warning',
                                'approved' => 'bg-info',
                                'partial' => 'bg-cyan',
                                'paid' => 'bg-success',
                                'cancelled' => 'bg-danger'
                            ];
                            $color = $statusColors[$inv['status']] ?? 'bg-secondary';
                            ?>
                            <span class="badge <?= $color ?>"><?= ucfirst($inv['status']) ?></span>
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    Actions
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a href="javascript:void(0)" class="dropdown-item" onclick="alert('View coming soon')">
                                        <i class="ti ti-eye me-2"></i>View
                                    </a>
                                    <?php if ($inv['balance'] > 0): ?>
                                        <a href="javascript:void(0)" class="dropdown-item text-success" onclick="alert('Pay coming soon')">
                                            <i class="ti ti-cash me-2"></i>Record Payment
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
