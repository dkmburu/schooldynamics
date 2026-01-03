<?php
/**
 * Fee Structure - Edit/Create Content
 */

$isEdit = !empty($structure);
$statusColors = [
    'draft' => 'secondary',
    'pending_approval' => 'warning',
    'approved' => 'info',
    'published' => 'success',
    'locked' => 'dark'
];

// Group fee items by category for easier selection
$feeItemsByCategory = [];
foreach ($fee_items as $item) {
    $categoryName = $item['category_name'];
    if (!isset($feeItemsByCategory[$categoryName])) {
        $feeItemsByCategory[$categoryName] = [];
    }
    $feeItemsByCategory[$categoryName][] = $item;
}

// Index existing lines by fee_item_id for easier lookup
$existingLines = [];
foreach ($lines as $line) {
    $existingLines[$line['fee_item_id']] = $line;
}
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <a href="/finance/fee-structures" class="btn btn-ghost-secondary btn-sm mb-2">
                    <i class="ti ti-arrow-left me-1"></i>Back to Fee Structures
                </a>
                <h2 class="page-title">
                    <i class="ti ti-file-spreadsheet me-2"></i>
                    <?= $isEdit ? 'Edit Fee Structure' : 'Create Fee Structure' ?>
                </h2>
                <?php if ($isEdit): ?>
                <div class="text-muted mt-1">
                    <?= e($structure['name'] ?? '') ?>
                    <span class="badge bg-<?= $statusColors[$structure['status']] ?? 'secondary' ?>-lt ms-2">
                        <?= ucfirst(str_replace('_', ' ', $structure['status'])) ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <form method="POST" action="/finance/fee-structures/save" id="feeStructureForm">
            <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= $structure['id'] ?>">
            <?php endif; ?>

            <!-- Structure Header -->
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Structure Details</h3>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required">Structure Name</label>
                            <input type="text" name="name" class="form-control"
                                   value="<?= e($structure['name'] ?? '') ?>"
                                   placeholder="e.g. Grade 1 - Term 1 2025 Fees" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Campus</label>
                            <select name="campus_id" class="form-select" required>
                                <option value="">Select campus...</option>
                                <?php foreach ($campuses as $campus): ?>
                                <option value="<?= $campus['id'] ?>"
                                        <?= ($structure['campus_id'] ?? '') == $campus['id'] ? 'selected' : '' ?>>
                                    <?= e($campus['campus_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Academic Year</label>
                            <select name="academic_year_id" class="form-select" required>
                                <option value="">Select year...</option>
                                <?php foreach ($years as $year): ?>
                                <option value="<?= $year['id'] ?>"
                                        <?= ($structure['academic_year_id'] ?? '') == $year['id'] ? 'selected' : '' ?>
                                        <?= $year['is_current'] ? 'class="fw-bold"' : '' ?>>
                                    <?= e($year['year_name']) ?><?= $year['is_current'] ? ' (Current)' : '' ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Term</label>
                            <select name="term_id" class="form-select" required>
                                <option value="">Select term...</option>
                                <?php foreach ($terms as $term): ?>
                                <option value="<?= $term['id'] ?>"
                                        <?= ($structure['term_id'] ?? '') == $term['id'] ? 'selected' : '' ?>>
                                    <?= e($term['term_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Grade</label>
                            <select name="grade_id" class="form-select" required>
                                <option value="">Select grade...</option>
                                <?php foreach ($grades as $grade): ?>
                                <option value="<?= $grade['id'] ?>"
                                        <?= ($structure['grade_id'] ?? '') == $grade['id'] ? 'selected' : '' ?>>
                                    <?= e($grade['grade_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fee Items Selection -->
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Fee Items</h3>
                    <div class="card-actions">
                        <span class="text-muted">Select items and set amounts for this fee structure</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table" id="feeItemsTable">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">
                                        <input type="checkbox" class="form-check-input" id="selectAllItems">
                                    </th>
                                    <th>Fee Item</th>
                                    <th>Category</th>
                                    <th style="width: 150px;">Amount (KES)</th>
                                    <th style="width: 100px;">Mandatory</th>
                                    <th style="width: 120px;">Student Type</th>
                                    <th style="width: 120px;">Option Group</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $lineIndex = 0; ?>
                                <?php foreach ($feeItemsByCategory as $categoryName => $categoryItems): ?>
                                    <tr class="bg-light">
                                        <td colspan="7" class="fw-bold text-muted">
                                            <i class="ti ti-folder me-1"></i><?= e($categoryName) ?>
                                        </td>
                                    </tr>
                                    <?php foreach ($categoryItems as $item): ?>
                                    <?php
                                        $existingLine = $existingLines[$item['id']] ?? null;
                                        $isSelected = $existingLine !== null;
                                        $amount = $existingLine ? $existingLine['amount'] : $item['default_amount'];
                                        $isMandatory = $existingLine ? $existingLine['is_mandatory'] : 1;
                                        $studentType = $existingLine ? $existingLine['applies_to_student_type'] : 'all';
                                        $optionGroup = $existingLine ? $existingLine['option_group'] : '';
                                    ?>
                                    <tr class="fee-item-row <?= $isSelected ? 'table-active' : '' ?>"
                                        data-item-id="<?= $item['id'] ?>"
                                        data-default-amount="<?= $item['default_amount'] ?>">
                                        <td>
                                            <input type="checkbox" class="form-check-input item-checkbox"
                                                   name="lines[<?= $lineIndex ?>][selected]"
                                                   data-index="<?= $lineIndex ?>"
                                                   <?= $isSelected ? 'checked' : '' ?>>
                                        </td>
                                        <td>
                                            <strong><?= e($item['name']) ?></strong>
                                            <input type="hidden" name="lines[<?= $lineIndex ?>][fee_item_id]" value="<?= $item['id'] ?>">
                                            <div class="text-muted small"><?= e($item['code']) ?></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $item['category_type'] === 'recurring' ? 'primary' : 'secondary' ?>-lt">
                                                <?= ucfirst($item['category_type']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <input type="number" name="lines[<?= $lineIndex ?>][amount]"
                                                   class="form-control form-control-sm amount-input"
                                                   value="<?= $amount ?>" step="0.01" min="0"
                                                   <?= !$isSelected ? 'disabled' : '' ?>>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" name="lines[<?= $lineIndex ?>][is_mandatory]"
                                                   class="form-check-input mandatory-checkbox"
                                                   <?= $isMandatory ? 'checked' : '' ?>
                                                   <?= !$isSelected ? 'disabled' : '' ?>>
                                        </td>
                                        <td>
                                            <select name="lines[<?= $lineIndex ?>][applies_to_student_type]"
                                                    class="form-select form-select-sm student-type-select"
                                                    <?= !$isSelected ? 'disabled' : '' ?>>
                                                <option value="all" <?= $studentType === 'all' ? 'selected' : '' ?>>All</option>
                                                <option value="new" <?= $studentType === 'new' ? 'selected' : '' ?>>New Only</option>
                                                <option value="returning" <?= $studentType === 'returning' ? 'selected' : '' ?>>Returning</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="lines[<?= $lineIndex ?>][option_group]"
                                                   class="form-control form-control-sm option-group-input"
                                                   placeholder="e.g. meals"
                                                   value="<?= e($optionGroup) ?>"
                                                   <?= !$isSelected ? 'disabled' : '' ?>>
                                        </td>
                                    </tr>
                                    <?php $lineIndex++; ?>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Summary Card -->
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-lg bg-primary-lt me-3">
                                    <i class="ti ti-receipt-2"></i>
                                </div>
                                <div>
                                    <div class="text-muted small">Total Mandatory</div>
                                    <div class="h3 mb-0" id="totalMandatory">KES 0</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-lg bg-info-lt me-3">
                                    <i class="ti ti-list-check"></i>
                                </div>
                                <div>
                                    <div class="text-muted small">Total Optional (Max)</div>
                                    <div class="h3 mb-0" id="totalOptional">KES 0</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-lg bg-success-lt me-3">
                                    <i class="ti ti-calculator"></i>
                                </div>
                                <div>
                                    <div class="text-muted small">Grand Total</div>
                                    <div class="h3 mb-0" id="grandTotal">KES 0</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <a href="/finance/fee-structures" class="btn btn-secondary">
                            <i class="ti ti-x me-1"></i>Cancel
                        </a>
                        <div>
                            <button type="submit" name="action" value="draft" class="btn btn-outline-primary me-2">
                                <i class="ti ti-device-floppy me-1"></i>Save as Draft
                            </button>
                            <button type="submit" name="action" value="save" class="btn btn-primary">
                                <i class="ti ti-check me-1"></i>Save Structure
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('feeStructureForm');
    const selectAllCheckbox = document.getElementById('selectAllItems');
    const itemCheckboxes = document.querySelectorAll('.item-checkbox');

    // Calculate totals
    function calculateTotals() {
        let mandatory = 0;
        let optional = 0;

        document.querySelectorAll('.fee-item-row').forEach(row => {
            const checkbox = row.querySelector('.item-checkbox');
            const amountInput = row.querySelector('.amount-input');
            const mandatoryCheckbox = row.querySelector('.mandatory-checkbox');

            if (checkbox.checked) {
                const amount = parseFloat(amountInput.value) || 0;
                if (mandatoryCheckbox.checked) {
                    mandatory += amount;
                } else {
                    optional += amount;
                }
            }
        });

        document.getElementById('totalMandatory').textContent = 'KES ' + mandatory.toLocaleString('en-KE', {minimumFractionDigits: 0, maximumFractionDigits: 0});
        document.getElementById('totalOptional').textContent = 'KES ' + optional.toLocaleString('en-KE', {minimumFractionDigits: 0, maximumFractionDigits: 0});
        document.getElementById('grandTotal').textContent = 'KES ' + (mandatory + optional).toLocaleString('en-KE', {minimumFractionDigits: 0, maximumFractionDigits: 0});
    }

    // Toggle row inputs based on checkbox
    function toggleRowInputs(checkbox) {
        const row = checkbox.closest('.fee-item-row');
        const inputs = row.querySelectorAll('.amount-input, .mandatory-checkbox, .student-type-select, .option-group-input');

        inputs.forEach(input => {
            input.disabled = !checkbox.checked;
        });

        if (checkbox.checked) {
            row.classList.add('table-active');
            // Set default amount if empty
            const amountInput = row.querySelector('.amount-input');
            if (!amountInput.value || amountInput.value === '0') {
                amountInput.value = row.dataset.defaultAmount;
            }
        } else {
            row.classList.remove('table-active');
        }

        calculateTotals();
    }

    // Select all checkbox
    selectAllCheckbox.addEventListener('change', function() {
        itemCheckboxes.forEach(cb => {
            cb.checked = this.checked;
            toggleRowInputs(cb);
        });
    });

    // Individual checkboxes
    itemCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            toggleRowInputs(this);

            // Update select all checkbox state
            const allChecked = Array.from(itemCheckboxes).every(c => c.checked);
            const someChecked = Array.from(itemCheckboxes).some(c => c.checked);
            selectAllCheckbox.checked = allChecked;
            selectAllCheckbox.indeterminate = someChecked && !allChecked;
        });
    });

    // Recalculate on amount/mandatory change
    document.querySelectorAll('.amount-input, .mandatory-checkbox').forEach(input => {
        input.addEventListener('change', calculateTotals);
        input.addEventListener('input', calculateTotals);
    });

    // Before form submit, remove unchecked items
    form.addEventListener('submit', function(e) {
        document.querySelectorAll('.fee-item-row').forEach(row => {
            const checkbox = row.querySelector('.item-checkbox');
            if (!checkbox.checked) {
                row.querySelectorAll('input, select').forEach(input => {
                    input.disabled = true;
                });
            }
        });
    });

    // Initial calculation
    calculateTotals();
});
</script>
