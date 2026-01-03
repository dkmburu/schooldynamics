<?php
/**
 * Goods Received Notes Tab Content
 */
$grns = $tabData['grns'] ?? [];
$filters = $tabData['filters'] ?? [];
?>

<!-- Filters -->
<div class="row mb-3">
    <div class="col-md-8">
        <form method="GET" action="/finance/expenses/grn" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search GRN#, PO#..."
                    value="<?= e($filters['search'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="draft" <?= ($filters['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="confirmed" <?= ($filters['status'] ?? '') === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="ti ti-search me-1"></i>Filter
                </button>
            </div>
        </form>
    </div>
</div>

<?php if (empty($grns)): ?>
    <div class="empty py-5">
        <div class="empty-img">
            <i class="ti ti-package" style="font-size: 4rem; color: #adb5bd;"></i>
        </div>
        <p class="empty-title">No goods received notes found</p>
        <p class="empty-subtitle text-muted">
            GRNs are created when goods are received against purchase orders.
        </p>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-hover">
            <thead>
                <tr>
                    <th>GRN Number</th>
                    <th>PO Number</th>
                    <th>Supplier</th>
                    <th>Received Date</th>
                    <th>Delivery Note</th>
                    <th>Status</th>
                    <th>Received By</th>
                    <th class="w-1">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($grns as $grn): ?>
                    <tr>
                        <td><strong><?= e($grn['grn_number']) ?></strong></td>
                        <td><?= e($grn['po_number'] ?? '-') ?></td>
                        <td><?= e($grn['supplier_name']) ?></td>
                        <td><?= date('M j, Y', strtotime($grn['received_date'])) ?></td>
                        <td><?= e($grn['delivery_note_number'] ?? '-') ?></td>
                        <td>
                            <?php if ($grn['status'] === 'confirmed'): ?>
                                <span class="badge bg-success">Confirmed</span>
                            <?php else: ?>
                                <span class="badge bg-warning">Draft</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($grn['received_by_name'] ?? '-') ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="alert('View GRN coming soon')">
                                <i class="ti ti-eye"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
