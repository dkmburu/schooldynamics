<?php
/**
 * Statutory Deductions Content
 */

$statutoryFunds = $statutoryFunds ?? [];
$taxBrackets = $taxBrackets ?? [];
$reliefs = $reliefs ?? [];
$currency = $_SESSION['currency'] ?? 'KES';
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <div class="page-pretitle">HR & Payroll</div>
                <h2 class="page-title">
                    <i class="ti ti-building-bank me-2"></i>Statutory Deductions
                </h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <!-- Statutory Funds -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="ti ti-shield-check me-2"></i>Statutory Funds
                </h3>
                <div class="card-actions">
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newFundModal">
                        <i class="ti ti-plus me-1"></i>Add Fund
                    </button>
                </div>
            </div>
            <?php if (empty($statutoryFunds)): ?>
            <div class="card-body">
                <div class="empty">
                    <p class="empty-title">No statutory funds configured</p>
                    <p class="empty-subtitle text-muted">Configure statutory deductions like NSSF, NHIF, Housing Levy</p>
                </div>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Fund Code</th>
                            <th>Fund Name</th>
                            <th>Calculation</th>
                            <th class="text-end">Rate/Amount</th>
                            <th>Employee/Employer</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($statutoryFunds as $fund): ?>
                        <tr>
                            <td><code><?= e($fund['fund_code']) ?></code></td>
                            <td>
                                <strong><?= e($fund['fund_name']) ?></strong>
                                <?php if (!empty($fund['description'])): ?>
                                <div class="text-muted small"><?= e($fund['description']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $calcType = $fund['calculation_type'] ?? 'percentage';
                                if ($calcType === 'tiered'): ?>
                                <span class="badge bg-purple-lt">Tiered</span>
                                <?php elseif ($calcType === 'percentage'): ?>
                                <span class="badge bg-blue-lt">Percentage</span>
                                <?php else: ?>
                                <span class="badge bg-cyan-lt">Fixed</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if ($calcType === 'tiered'): ?>
                                <span class="text-muted">See tiers</span>
                                <?php elseif ($calcType === 'percentage'): ?>
                                <?= number_format($fund['rate'] ?? 0, 2) ?>%
                                <?php else: ?>
                                <?= $currency ?> <?= number_format($fund['amount'] ?? 0) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small">
                                    <span class="text-muted">EE:</span> <?= number_format($fund['employee_rate'] ?? 0, 1) ?>%
                                    <span class="text-muted ms-2">ER:</span> <?= number_format($fund['employer_rate'] ?? 0, 1) ?>%
                                </div>
                            </td>
                            <td>
                                <?php if ($fund['is_active'] ?? true): ?>
                                <span class="badge bg-success-lt">Active</span>
                                <?php else: ?>
                                <span class="badge bg-secondary-lt">Inactive</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <div class="row">
            <!-- Tax Brackets -->
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-percentage me-2"></i>PAYE Tax Brackets
                        </h3>
                        <div class="card-actions">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editTaxBracketsModal">
                                <i class="ti ti-edit me-1"></i>Edit
                            </button>
                        </div>
                    </div>
                    <?php if (empty($taxBrackets)): ?>
                    <div class="card-body">
                        <div class="empty">
                            <p class="empty-title">No tax brackets configured</p>
                            <p class="empty-subtitle text-muted">Configure PAYE tax bands for payroll calculation</p>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Band</th>
                                    <th class="text-end">From</th>
                                    <th class="text-end">To</th>
                                    <th class="text-end">Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($taxBrackets as $i => $bracket): ?>
                                <tr>
                                    <td>Band <?= $i + 1 ?></td>
                                    <td class="text-end"><?= $currency ?> <?= number_format($bracket['min_amount'] ?? 0) ?></td>
                                    <td class="text-end">
                                        <?php if (($bracket['max_amount'] ?? 0) == 0 || ($bracket['max_amount'] ?? 0) >= 999999999): ?>
                                        <span class="text-muted">& above</span>
                                        <?php else: ?>
                                        <?= $currency ?> <?= number_format($bracket['max_amount']) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <strong><?= number_format($bracket['rate'] ?? 0, 1) ?>%</strong>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tax Reliefs -->
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-discount me-2"></i>Tax Reliefs
                        </h3>
                        <div class="card-actions">
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newReliefModal">
                                <i class="ti ti-plus me-1"></i>Add
                            </button>
                        </div>
                    </div>
                    <?php if (empty($reliefs)): ?>
                    <div class="card-body">
                        <div class="empty">
                            <p class="empty-title">No tax reliefs</p>
                            <p class="empty-subtitle text-muted">Configure reliefs like Personal Relief, Insurance Relief</p>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($reliefs as $relief): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?= e($relief['relief_name']) ?></strong>
                                    <div class="text-muted small"><?= e($relief['description'] ?? '') ?></div>
                                </div>
                                <div class="text-end">
                                    <strong><?= $currency ?> <?= number_format($relief['monthly_amount'] ?? 0) ?></strong>
                                    <div class="text-muted small">/month</div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- NHIF Tiers (if applicable) -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-stairs me-2"></i>NHIF Tiers
                        </h3>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-0">
                            NHIF uses tiered rates based on gross salary. View the complete tier structure in the
                            <a href="#" data-bs-toggle="modal" data-bs-target="#nhifTiersModal">rate table</a>.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Fund Modal -->
<div class="modal modal-blur fade" id="newFundModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Statutory Fund</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="/hr-payroll/statutory-funds">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required">Fund Code</label>
                            <input type="text" name="fund_code" class="form-control" required
                                   placeholder="e.g., NSSF, NHIF">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Fund Name</label>
                            <input type="text" name="fund_name" class="form-control" required
                                   placeholder="e.g., National Social Security Fund">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Calculation Type</label>
                            <select name="calculation_type" class="form-select" required>
                                <option value="percentage">Percentage of Gross</option>
                                <option value="fixed">Fixed Amount</option>
                                <option value="tiered">Tiered Rates</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Rate/Amount</label>
                            <input type="number" name="rate" class="form-control" step="0.01">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Employee Rate (%)</label>
                            <input type="number" name="employee_rate" class="form-control" step="0.01" value="100">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Employer Rate (%)</label>
                            <input type="number" name="employer_rate" class="form-control" step="0.01" value="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
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

<!-- New Relief Modal -->
<div class="modal modal-blur fade" id="newReliefModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Tax Relief</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="/hr-payroll/tax-reliefs">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label required">Relief Name</label>
                            <input type="text" name="relief_name" class="form-control" required
                                   placeholder="e.g., Personal Relief, Insurance Relief">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Monthly Amount</label>
                            <div class="input-group">
                                <span class="input-group-text"><?= $currency ?></span>
                                <input type="number" name="monthly_amount" class="form-control" required step="0.01">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Annual Amount</label>
                            <div class="input-group">
                                <span class="input-group-text"><?= $currency ?></span>
                                <input type="number" name="annual_amount" class="form-control" step="0.01" readonly>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
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

<!-- NHIF Tiers Modal -->
<div class="modal modal-blur fade" id="nhifTiersModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">NHIF Rate Tiers</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th>Gross Salary Range</th>
                                <th class="text-end">Monthly Contribution</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>Up to <?= $currency ?> 5,999</td><td class="text-end"><?= $currency ?> 150</td></tr>
                            <tr><td><?= $currency ?> 6,000 - 7,999</td><td class="text-end"><?= $currency ?> 300</td></tr>
                            <tr><td><?= $currency ?> 8,000 - 11,999</td><td class="text-end"><?= $currency ?> 400</td></tr>
                            <tr><td><?= $currency ?> 12,000 - 14,999</td><td class="text-end"><?= $currency ?> 500</td></tr>
                            <tr><td><?= $currency ?> 15,000 - 19,999</td><td class="text-end"><?= $currency ?> 600</td></tr>
                            <tr><td><?= $currency ?> 20,000 - 24,999</td><td class="text-end"><?= $currency ?> 750</td></tr>
                            <tr><td><?= $currency ?> 25,000 - 29,999</td><td class="text-end"><?= $currency ?> 850</td></tr>
                            <tr><td><?= $currency ?> 30,000 - 34,999</td><td class="text-end"><?= $currency ?> 900</td></tr>
                            <tr><td><?= $currency ?> 35,000 - 39,999</td><td class="text-end"><?= $currency ?> 950</td></tr>
                            <tr><td><?= $currency ?> 40,000 - 44,999</td><td class="text-end"><?= $currency ?> 1,000</td></tr>
                            <tr><td><?= $currency ?> 45,000 - 49,999</td><td class="text-end"><?= $currency ?> 1,100</td></tr>
                            <tr><td><?= $currency ?> 50,000 - 59,999</td><td class="text-end"><?= $currency ?> 1,200</td></tr>
                            <tr><td><?= $currency ?> 60,000 - 69,999</td><td class="text-end"><?= $currency ?> 1,300</td></tr>
                            <tr><td><?= $currency ?> 70,000 - 79,999</td><td class="text-end"><?= $currency ?> 1,400</td></tr>
                            <tr><td><?= $currency ?> 80,000 - 89,999</td><td class="text-end"><?= $currency ?> 1,500</td></tr>
                            <tr><td><?= $currency ?> 90,000 - 99,999</td><td class="text-end"><?= $currency ?> 1,600</td></tr>
                            <tr><td><?= $currency ?> 100,000 & above</td><td class="text-end"><?= $currency ?> 1,700</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-calculate annual amount
document.addEventListener('DOMContentLoaded', function() {
    const monthlyInput = document.querySelector('input[name="monthly_amount"]');
    const annualInput = document.querySelector('input[name="annual_amount"]');

    if (monthlyInput && annualInput) {
        monthlyInput.addEventListener('input', function() {
            const monthly = parseFloat(this.value) || 0;
            annualInput.value = (monthly * 12).toFixed(2);
        });
    }
});
</script>
