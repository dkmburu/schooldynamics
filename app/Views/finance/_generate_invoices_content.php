<?php
/**
 * Generate Invoices - Content
 */
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <a href="/finance/invoices" class="btn btn-ghost-secondary btn-sm mb-2">
                    <i class="ti ti-arrow-left me-1"></i>Back to Invoices
                </a>
                <h2 class="page-title">
                    <i class="ti ti-file-plus me-2"></i>
                    Generate Invoices
                </h2>
                <div class="text-muted mt-1">
                    Batch create invoices for students based on fee structures
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <form method="POST" action="/finance/invoices/generate">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Invoice Generation Settings</h3>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label required">Academic Year</label>
                                    <select name="academic_year_id" class="form-select" required>
                                        <?php if ($current_year): ?>
                                        <option value="<?= $current_year['id'] ?>" selected>
                                            <?= e($current_year['year_name']) ?> (Current)
                                        </option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label required">Term</label>
                                    <select name="term_id" class="form-select" required>
                                        <?php if ($current_term): ?>
                                        <option value="<?= $current_term['id'] ?>" selected>
                                            <?= e($current_term['term_name']) ?> (Current)
                                        </option>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Campus</label>
                                    <select name="campus_id" class="form-select">
                                        <option value="">All Campuses</option>
                                        <?php foreach ($campuses as $campus): ?>
                                        <option value="<?= $campus['id'] ?>"><?= e($campus['campus_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Grades</label>
                                    <div class="row g-2">
                                        <?php foreach ($grades as $grade): ?>
                                        <div class="col-md-4">
                                            <label class="form-check">
                                                <input type="checkbox" name="grade_ids[]" value="<?= $grade['id'] ?>" class="form-check-input" checked>
                                                <span class="form-check-label"><?= e($grade['grade_name']) ?></span>
                                            </label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <small class="text-muted">Leave all checked to generate for all grades</small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label required">Invoice Date</label>
                                    <input type="date" name="invoice_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label required">Due Date</label>
                                    <input type="date" name="due_date" class="form-control" value="<?= date('Y-m-d', strtotime('+14 days')) ?>" required>
                                </div>

                                <div class="col-12">
                                    <hr class="my-3">
                                    <h4>Options</h4>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-check">
                                        <input type="checkbox" name="include_balance_forward" value="1" class="form-check-input" checked>
                                        <span class="form-check-label">Include balance forward from previous term</span>
                                    </label>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-check">
                                        <input type="checkbox" name="apply_credit_balances" value="1" class="form-check-input" checked>
                                        <span class="form-check-label">Apply credit balances</span>
                                    </label>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-check">
                                        <input type="checkbox" name="apply_discounts" value="1" class="form-check-input" checked>
                                        <span class="form-check-label">Apply discount policies</span>
                                    </label>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-check">
                                        <input type="checkbox" name="skip_existing" value="1" class="form-check-input" checked>
                                        <span class="form-check-label">Skip students with existing invoices</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-muted">
                                    <i class="ti ti-info-circle me-1"></i>
                                    This will generate invoices based on published fee structures.
                                </div>
                                <div class="btn-list">
                                    <a href="/finance/invoices" class="btn btn-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ti ti-file-plus me-2"></i>Generate Invoices
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
