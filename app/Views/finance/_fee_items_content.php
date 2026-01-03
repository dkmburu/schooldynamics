<?php
/**
 * Fee Items - Content
 */

$categoryTypes = [
    'mandatory' => ['label' => 'Mandatory', 'color' => 'primary'],
    'optional_meal' => ['label' => 'Meals', 'color' => 'orange'],
    'optional_transport' => ['label' => 'Transport', 'color' => 'purple'],
    'optional_subject' => ['label' => 'Elective', 'color' => 'cyan'],
    'optional_activity' => ['label' => 'Activity', 'color' => 'green'],
    'optional_other' => ['label' => 'Other', 'color' => 'secondary']
];
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <a href="/finance" class="btn btn-ghost-secondary btn-sm mb-2">
                    <i class="ti ti-arrow-left me-1"></i>Back to Finance
                </a>
                <h2 class="page-title">
                    <i class="ti ti-list-details me-2"></i>
                    Fee Items
                </h2>
                <div class="text-muted mt-1">
                    Master list of all chargeable fee items
                </div>
            </div>
            <div class="col-auto ms-auto">
                <?php if (hasPermission('Finance.write') || Gate::hasRole('ADMIN')): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                    <i class="ti ti-plus me-2"></i>Add Fee Item
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="card">
            <div class="card-body">
                <?php if (empty($items)): ?>
                <div class="empty">
                    <div class="empty-img">
                        <i class="ti ti-list-details" style="font-size: 4rem; color: #adb5bd;"></i>
                    </div>
                    <p class="empty-title">No fee items defined</p>
                    <p class="empty-subtitle text-muted">
                        Add fee items like "Tuition - Term 1", "School Lunch", "Piano Lessons", etc.
                    </p>
                    <?php if (hasPermission('Finance.write') || Gate::hasRole('ADMIN')): ?>
                    <div class="empty-action">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                            <i class="ti ti-plus me-2"></i>Add First Item
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-striped">
                        <thead>
                            <tr>
                                <th style="width: 100px;">Code</th>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th style="width: 150px;">Default Amount</th>
                                <th style="width: 100px;">Status</th>
                                <th style="width: 80px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <?php $typeConfig = $categoryTypes[$item['category_type'] ?? 'other'] ?? ['label' => 'Other', 'color' => 'secondary']; ?>
                            <tr>
                                <td><code><?= e($item['code']) ?></code></td>
                                <td>
                                    <strong><?= e($item['name']) ?></strong>
                                    <?php if (!empty($item['description'])): ?>
                                    <div class="text-muted small"><?= e($item['description']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $typeConfig['color'] ?>-lt">
                                        <?= e($item['category_name']) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <strong>KES <?= number_format($item['default_amount'], 2) ?></strong>
                                </td>
                                <td>
                                    <?php if ($item['is_active']): ?>
                                    <span class="badge bg-success-lt">Active</span>
                                    <?php else: ?>
                                    <span class="badge bg-danger-lt">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (hasPermission('Finance.write') || Gate::hasRole('ADMIN')): ?>
                                    <div class="dropdown">
                                        <button class="btn btn-icon btn-ghost-secondary" data-bs-toggle="dropdown">
                                            <i class="ti ti-dots-vertical"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a class="dropdown-item" href="#" onclick="editItem(<?= htmlspecialchars(json_encode($item), ENT_QUOTES) ?>); return false;">
                                                <i class="ti ti-edit me-2"></i>Edit
                                            </a>
                                        </div>
                                    </div>
                                    <?php endif; ?>
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

<!-- Add Fee Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/finance/fee-items/store">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="ti ti-plus me-2"></i>Add Fee Item
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label required">Category</label>
                            <select name="fee_category_id" class="form-select" required>
                                <option value="">Select category...</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>">
                                    <?= e($cat['name']) ?>
                                    (<?= ucwords(str_replace('_', ' ', $cat['category_type'])) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Code</label>
                            <input type="text" name="code" class="form-control" required
                                   placeholder="e.g. TUI001" style="text-transform: uppercase;">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label required">Item Name</label>
                            <input type="text" name="name" class="form-control" required
                                   placeholder="e.g. Tuition Fee - Term 1">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Default Amount (KES)</label>
                            <input type="number" name="default_amount" class="form-control" step="0.01" min="0"
                                   placeholder="0.00">
                            <small class="text-muted">Can be overridden in fee structures</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"
                                      placeholder="Optional description"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="ti ti-x me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-check me-1"></i>Add Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Fee Item Modal -->
<div class="modal fade" id="editItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/finance/fee-items/update" id="editItemForm">
                <input type="hidden" name="id" id="edit_item_id">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="ti ti-edit me-2"></i>Edit Fee Item
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label required">Category</label>
                            <select name="fee_category_id" id="edit_fee_category_id" class="form-select" required>
                                <option value="">Select category...</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>">
                                    <?= e($cat['name']) ?>
                                    (<?= ucwords(str_replace('_', ' ', $cat['category_type'])) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Code</label>
                            <input type="text" name="code" id="edit_code" class="form-control" required
                                   style="text-transform: uppercase;">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label required">Item Name</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Default Amount (KES)</label>
                            <input type="number" name="default_amount" id="edit_default_amount" class="form-control" step="0.01" min="0">
                            <small class="text-muted">Can be overridden in fee structures</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-check">
                                <input type="checkbox" name="is_active" id="edit_is_active" class="form-check-input" value="1">
                                <span class="form-check-label">Active</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="ti ti-x me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editItem(item) {
    document.getElementById('edit_item_id').value = item.id;
    document.getElementById('edit_fee_category_id').value = item.fee_category_id;
    document.getElementById('edit_code').value = item.code;
    document.getElementById('edit_name').value = item.name;
    document.getElementById('edit_default_amount').value = item.default_amount || 0;
    document.getElementById('edit_description').value = item.description || '';
    document.getElementById('edit_is_active').checked = item.is_active == 1;

    new bootstrap.Modal(document.getElementById('editItemModal')).show();
}
</script>
