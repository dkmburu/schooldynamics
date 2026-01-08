<?php
/**
 * Allowances & Deductions Content
 */

$components = $components ?? [];
$currency = $_SESSION['currency'] ?? 'KES';
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <div class="page-pretitle">HR & Payroll</div>
                <h2 class="page-title">
                    <i class="ti ti-adjustments-horizontal me-2"></i>Allowances & Deductions
                </h2>
            </div>
            <div class="col-auto ms-auto">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newComponentModal">
                    <i class="ti ti-plus me-1"></i>New Component
                </button>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="row">
            <!-- Allowances -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-plus text-success me-2"></i>Allowances
                        </h3>
                    </div>
                    <?php
                    $allowances = array_filter($components, fn($c) => ($c['component_type'] ?? '') === 'allowance');
                    if (empty($allowances)):
                    ?>
                    <div class="card-body">
                        <div class="empty">
                            <p class="empty-title">No allowances defined</p>
                            <p class="empty-subtitle text-muted">Create allowance components like House, Transport, etc.</p>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th class="text-end">Default Amount</th>
                                    <th>Taxable</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allowances as $comp): ?>
                                <tr>
                                    <td><code><?= e($comp['component_code']) ?></code></td>
                                    <td><?= e($comp['component_name']) ?></td>
                                    <td>
                                        <?php if (($comp['calculation_type'] ?? '') === 'percentage'): ?>
                                        <span class="badge bg-blue-lt">Percentage</span>
                                        <?php else: ?>
                                        <span class="badge bg-cyan-lt">Fixed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if (($comp['calculation_type'] ?? '') === 'percentage'): ?>
                                        <?= number_format($comp['default_amount'] ?? 0, 1) ?>%
                                        <?php else: ?>
                                        <?= $currency ?> <?= number_format($comp['default_amount'] ?? 0) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($comp['is_taxable'] ?? false): ?>
                                        <span class="badge bg-yellow-lt">Taxable</span>
                                        <?php else: ?>
                                        <span class="badge bg-green-lt">Non-Taxable</span>
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

            <!-- Deductions -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-minus text-danger me-2"></i>Deductions
                        </h3>
                    </div>
                    <?php
                    $deductions = array_filter($components, fn($c) => ($c['component_type'] ?? '') === 'deduction');
                    if (empty($deductions)):
                    ?>
                    <div class="card-body">
                        <div class="empty">
                            <p class="empty-title">No deductions defined</p>
                            <p class="empty-subtitle text-muted">Create deduction components like Loan Recovery, Savings, etc.</p>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th class="text-end">Default Amount</th>
                                    <th>Mandatory</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($deductions as $comp): ?>
                                <tr>
                                    <td><code><?= e($comp['component_code']) ?></code></td>
                                    <td><?= e($comp['component_name']) ?></td>
                                    <td>
                                        <?php if (($comp['calculation_type'] ?? '') === 'percentage'): ?>
                                        <span class="badge bg-blue-lt">Percentage</span>
                                        <?php else: ?>
                                        <span class="badge bg-cyan-lt">Fixed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if (($comp['calculation_type'] ?? '') === 'percentage'): ?>
                                        <?= number_format($comp['default_amount'] ?? 0, 1) ?>%
                                        <?php else: ?>
                                        <?= $currency ?> <?= number_format($comp['default_amount'] ?? 0) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($comp['is_mandatory'] ?? false): ?>
                                        <span class="badge bg-red-lt">Mandatory</span>
                                        <?php else: ?>
                                        <span class="badge bg-muted">Optional</span>
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
</div>

<!-- New Component Modal -->
<div class="modal modal-blur fade" id="newComponentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Pay Component</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="/hr-payroll/components">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required">Component Code</label>
                            <input type="text" name="component_code" class="form-control" required
                                   placeholder="e.g., HOUSE, TRANS">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Component Name</label>
                            <input type="text" name="component_name" class="form-control" required
                                   placeholder="e.g., House Allowance">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Type</label>
                            <select name="component_type" class="form-select" required>
                                <option value="allowance">Allowance (Added)</option>
                                <option value="deduction">Deduction (Subtracted)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Calculation</label>
                            <select name="calculation_type" class="form-select" required>
                                <option value="fixed">Fixed Amount</option>
                                <option value="percentage">Percentage of Basic</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Default Amount/Rate</label>
                            <input type="number" name="default_amount" class="form-control" step="0.01">
                        </div>
                        <div class="col-md-6">
                            <div class="mt-4">
                                <label class="form-check">
                                    <input type="checkbox" name="is_taxable" class="form-check-input" value="1">
                                    <span class="form-check-label">Taxable</span>
                                </label>
                                <label class="form-check">
                                    <input type="checkbox" name="is_mandatory" class="form-check-input" value="1">
                                    <span class="form-check-label">Mandatory</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>
