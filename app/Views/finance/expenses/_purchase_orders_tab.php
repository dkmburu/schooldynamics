<?php
/**
 * Purchase Orders Tab Content
 */
$orders = $tabData['orders'] ?? [];
$suppliers = $tabData['suppliers'] ?? [];
$filters = $tabData['filters'] ?? [];
?>

<!-- Filters & Actions -->
<div class="row mb-3">
    <div class="col-md-8">
        <form method="GET" action="/finance/expenses/purchase-orders" class="row g-2">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Search PO#..."
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
                    <option value="draft" <?= ($filters['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="pending_approval" <?= ($filters['status'] ?? '') === 'pending_approval' ? 'selected' : '' ?>>Pending Approval</option>
                    <option value="approved" <?= ($filters['status'] ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="sent" <?= ($filters['status'] ?? '') === 'sent' ? 'selected' : '' ?>>Sent</option>
                    <option value="received" <?= ($filters['status'] ?? '') === 'received' ? 'selected' : '' ?>>Received</option>
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
        <button type="button" class="btn btn-success" onclick="alert('Create PO modal coming soon')">
            <i class="ti ti-plus me-1"></i>Create Purchase Order
        </button>
    </div>
</div>

<?php if (empty($orders)): ?>
    <div class="empty py-5">
        <div class="empty-img">
            <i class="ti ti-file-invoice" style="font-size: 4rem; color: #adb5bd;"></i>
        </div>
        <p class="empty-title">No purchase orders found</p>
        <p class="empty-subtitle text-muted">
            Create your first purchase order to manage supplier orders.
        </p>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-hover">
            <thead>
                <tr>
                    <th>PO Number</th>
                    <th>Supplier</th>
                    <th>Order Date</th>
                    <th>Expected Delivery</th>
                    <th class="text-end">Total</th>
                    <th>Status</th>
                    <th>Prepared By</th>
                    <th class="w-1">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><strong><?= e($order['po_number']) ?></strong></td>
                        <td><?= e($order['supplier_name']) ?></td>
                        <td><?= date('M j, Y', strtotime($order['order_date'])) ?></td>
                        <td><?= $order['expected_delivery_date'] ? date('M j, Y', strtotime($order['expected_delivery_date'])) : '-' ?></td>
                        <td class="text-end fw-bold">KES <?= number_format($order['total_amount'], 2) ?></td>
                        <td>
                            <?php
                            $statusColors = [
                                'draft' => 'bg-secondary',
                                'pending_approval' => 'bg-warning',
                                'approved' => 'bg-info',
                                'sent' => 'bg-primary',
                                'partial' => 'bg-cyan',
                                'received' => 'bg-success',
                                'cancelled' => 'bg-danger'
                            ];
                            $color = $statusColors[$order['status']] ?? 'bg-secondary';
                            ?>
                            <span class="badge <?= $color ?>"><?= ucfirst(str_replace('_', ' ', $order['status'])) ?></span>
                        </td>
                        <td><?= e($order['prepared_by_name'] ?? '-') ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="alert('View PO coming soon')">
                                <i class="ti ti-eye"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
