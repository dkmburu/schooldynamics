<?php
/**
 * Fee Categories - Content
 */

$categoryTypes = [
    'mandatory' => ['label' => 'Mandatory', 'color' => 'primary', 'icon' => 'ti-lock'],
    'optional_meal' => ['label' => 'Meals', 'color' => 'orange', 'icon' => 'ti-tools-kitchen-2'],
    'optional_transport' => ['label' => 'Transport', 'color' => 'purple', 'icon' => 'ti-bus'],
    'optional_subject' => ['label' => 'Elective Subjects', 'color' => 'cyan', 'icon' => 'ti-book'],
    'optional_activity' => ['label' => 'Activities', 'color' => 'green', 'icon' => 'ti-ball-football'],
    'optional_other' => ['label' => 'Other Optional', 'color' => 'secondary', 'icon' => 'ti-dots']
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
                    <i class="ti ti-category me-2"></i>
                    Fee Categories
                </h2>
                <div class="text-muted mt-1">
                    Define types of fees: mandatory, meals, transport, activities, etc.
                </div>
            </div>
            <div class="col-auto ms-auto">
                <?php if (hasPermission('Finance.write') || Gate::hasRole('ADMIN')): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="ti ti-plus me-2"></i>Add Category
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
                <?php if (empty($categories)): ?>
                <div class="empty">
                    <div class="empty-img">
                        <i class="ti ti-category" style="font-size: 4rem; color: #adb5bd;"></i>
                    </div>
                    <p class="empty-title">No fee categories defined</p>
                    <p class="empty-subtitle text-muted">
                        Start by adding fee categories like Tuition, Meals, Transport, etc.
                    </p>
                    <?php if (hasPermission('Finance.write') || Gate::hasRole('ADMIN')): ?>
                    <div class="empty-action">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="ti ti-plus me-2"></i>Add First Category
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
                                <th>Name</th>
                                <th>Type</th>
                                <th style="width: 100px;">Recurring</th>
                                <th style="width: 100px;">Items</th>
                                <th style="width: 100px;">Status</th>
                                <th style="width: 80px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                            <?php $typeConfig = $categoryTypes[$category['category_type']] ?? ['label' => $category['category_type'], 'color' => 'secondary', 'icon' => 'ti-tag']; ?>
                            <tr>
                                <td><code><?= e($category['code']) ?></code></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="avatar avatar-sm bg-<?= $typeConfig['color'] ?>-lt me-2">
                                            <i class="ti <?= $typeConfig['icon'] ?>"></i>
                                        </span>
                                        <div>
                                            <strong><?= e($category['name']) ?></strong>
                                            <?php if (!empty($category['description'])): ?>
                                            <div class="text-muted small"><?= e($category['description']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $typeConfig['color'] ?>-lt">
                                        <?= $typeConfig['label'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($category['is_recurring']): ?>
                                    <span class="badge bg-azure-lt"><i class="ti ti-repeat me-1"></i>Yes</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary-lt">One-time</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="/finance/fee-items?category=<?= $category['id'] ?>" class="badge bg-primary">
                                        <?= $category['item_count'] ?? 0 ?> items
                                    </a>
                                </td>
                                <td>
                                    <?php if ($category['is_active']): ?>
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
                                            <a class="dropdown-item" href="#" onclick="editCategory(<?= htmlspecialchars(json_encode($category), ENT_QUOTES) ?>); return false;">
                                                <i class="ti ti-edit me-2"></i>Edit
                                            </a>
                                            <a class="dropdown-item" href="/finance/fee-items?category=<?= $category['id'] ?>">
                                                <i class="ti ti-list me-2"></i>View Items
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

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="/finance/fee-categories/store">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="ti ti-plus me-2"></i>Add Fee Category
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label required">Code</label>
                            <input type="text" name="code" class="form-control" required
                                   placeholder="e.g. TUITION" style="text-transform: uppercase;">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label required">Name</label>
                            <input type="text" name="name" class="form-control" required
                                   placeholder="e.g. Tuition Fees">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Category Type</label>
                            <select name="category_type" class="form-select" required>
                                <option value="">Select type...</option>
                                <?php foreach ($categoryTypes as $type => $config): ?>
                                <option value="<?= $type ?>"><?= $config['label'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sort Order</label>
                            <input type="number" name="sort_order" class="form-control" value="0" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Income Account (COA)</label>
                            <select name="coa_income_account_id" class="form-select">
                                <option value="">Select account...</option>
                                <?php foreach ($income_accounts as $acc): ?>
                                <option value="<?= $acc['id'] ?>">
                                    <?= e($acc['account_code']) ?> - <?= e($acc['account_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Receivable Account (COA)</label>
                            <select name="coa_receivable_account_id" class="form-select">
                                <option value="">Select account...</option>
                                <?php foreach ($ar_accounts as $acc): ?>
                                <option value="<?= $acc['id'] ?>">
                                    <?= e($acc['account_code']) ?> - <?= e($acc['account_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"
                                      placeholder="Optional description"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-check">
                                <input type="checkbox" name="is_recurring" class="form-check-input" value="1" checked>
                                <span class="form-check-label">Recurring (charged every term)</span>
                            </label>
                        </div>
                        <div class="col-md-6">
                            <label class="form-check">
                                <input type="checkbox" name="allow_partial" class="form-check-input" value="1">
                                <span class="form-check-label">Allow partial payments</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="ti ti-x me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-check me-1"></i>Add Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editCategory(category) {
    // For now, redirect to edit - can enhance later with modal
    alert('Edit functionality coming soon. Category: ' + category.name);
}
</script>
