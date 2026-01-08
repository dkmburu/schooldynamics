<?php
/**
 * Loans & Advances Content
 */

$loans = $loans ?? [];
$loanTypes = $loanTypes ?? [];
$staff = $staff ?? [];
$currency = $_SESSION['currency'] ?? 'KES';
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <div class="page-pretitle">HR & Payroll</div>
                <h2 class="page-title">
                    <i class="ti ti-cash me-2"></i>Loans & Advances
                </h2>
            </div>
            <div class="col-auto ms-auto">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newLoanModal">
                    <i class="ti ti-plus me-1"></i>New Loan
                </button>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-primary text-white avatar">
                                    <i class="ti ti-file-text"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">Active Loans</div>
                                <div class="text-muted"><?= count(array_filter($loans, fn($l) => ($l['status'] ?? '') === 'active')) ?> loans</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-yellow text-white avatar">
                                    <i class="ti ti-clock"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">Pending Approval</div>
                                <div class="text-muted"><?= count(array_filter($loans, fn($l) => ($l['status'] ?? '') === 'pending')) ?> requests</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-info text-white avatar">
                                    <i class="ti ti-currency-dollar"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">Total Disbursed</div>
                                <div class="text-muted"><?= $currency ?> <?= number_format(array_sum(array_column($loans, 'loan_amount'))) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-green text-white avatar">
                                    <i class="ti ti-check"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">Fully Paid</div>
                                <div class="text-muted"><?= count(array_filter($loans, fn($l) => ($l['status'] ?? '') === 'paid')) ?> loans</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loans List -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Staff Loans & Advances</h3>
                <div class="card-actions">
                    <select class="form-select form-select-sm w-auto">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="active">Active</option>
                        <option value="paid">Fully Paid</option>
                    </select>
                </div>
            </div>
            <?php if (empty($loans)): ?>
            <div class="card-body">
                <div class="empty">
                    <div class="empty-icon">
                        <i class="ti ti-cash"></i>
                    </div>
                    <p class="empty-title">No loans recorded</p>
                    <p class="empty-subtitle text-muted">Staff loan requests will appear here</p>
                    <div class="empty-action">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newLoanModal">
                            <i class="ti ti-plus me-1"></i>Add Loan
                        </button>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Staff</th>
                            <th>Loan Type</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Monthly Deduction</th>
                            <th class="text-end">Balance</th>
                            <th>Status</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($loans as $loan): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm me-2"><?= strtoupper(substr($loan['staff_name'] ?? 'S', 0, 1)) ?></span>
                                    <div><?= e($loan['staff_name'] ?? 'Unknown') ?></div>
                                </div>
                            </td>
                            <td><?= e($loan['loan_type'] ?? 'N/A') ?></td>
                            <td class="text-end"><?= $currency ?> <?= number_format($loan['loan_amount'] ?? 0) ?></td>
                            <td class="text-end"><?= $currency ?> <?= number_format($loan['monthly_deduction'] ?? 0) ?></td>
                            <td class="text-end"><?= $currency ?> <?= number_format($loan['balance'] ?? 0) ?></td>
                            <td>
                                <?php
                                $status = $loan['status'] ?? 'pending';
                                $statusClass = match($status) {
                                    'active' => 'bg-primary-lt',
                                    'approved' => 'bg-info-lt',
                                    'paid' => 'bg-success-lt',
                                    'rejected' => 'bg-danger-lt',
                                    default => 'bg-warning-lt'
                                };
                                ?>
                                <span class="badge <?= $statusClass ?>"><?= ucfirst($status) ?></span>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-ghost-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                        Actions
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a href="#" class="dropdown-item">
                                            <i class="ti ti-eye me-2"></i>View Details
                                        </a>
                                        <?php if ($status === 'pending'): ?>
                                        <a href="#" class="dropdown-item text-success">
                                            <i class="ti ti-check me-2"></i>Approve
                                        </a>
                                        <a href="#" class="dropdown-item text-danger">
                                            <i class="ti ti-x me-2"></i>Reject
                                        </a>
                                        <?php endif; ?>
                                        <a href="#" class="dropdown-item">
                                            <i class="ti ti-history me-2"></i>Payment History
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
        </div>

        <!-- Loan Types -->
        <div class="card mt-4">
            <div class="card-header">
                <h3 class="card-title">Loan Types</h3>
                <div class="card-actions">
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newLoanTypeModal">
                        <i class="ti ti-plus me-1"></i>Add Type
                    </button>
                </div>
            </div>
            <?php if (empty($loanTypes)): ?>
            <div class="card-body">
                <div class="empty">
                    <p class="empty-title">No loan types configured</p>
                    <p class="empty-subtitle text-muted">Define loan types like Salary Advance, Emergency Loan, etc.</p>
                </div>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Type Name</th>
                            <th class="text-end">Max Amount</th>
                            <th class="text-end">Interest Rate</th>
                            <th class="text-end">Max Tenure (Months)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($loanTypes as $type): ?>
                        <tr>
                            <td>
                                <strong><?= e($type['type_name']) ?></strong>
                                <?php if (!empty($type['description'])): ?>
                                <div class="text-muted small"><?= e($type['description']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-end"><?= $currency ?> <?= number_format($type['max_amount'] ?? 0) ?></td>
                            <td class="text-end"><?= number_format($type['interest_rate'] ?? 0, 1) ?>%</td>
                            <td class="text-end"><?= $type['max_tenure_months'] ?? 12 ?></td>
                            <td>
                                <?php if ($type['is_active'] ?? true): ?>
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
    </div>
</div>

<!-- New Loan Modal -->
<div class="modal modal-blur fade" id="newLoanModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Staff Loan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="/hr-payroll/loans">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required">Staff Member</label>
                            <select name="staff_id" class="form-select" required>
                                <option value="">Select Staff</option>
                                <?php foreach ($staff as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= e($s['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Loan Type</label>
                            <select name="loan_type_id" class="form-select" required>
                                <option value="">Select Type</option>
                                <?php foreach ($loanTypes as $type): ?>
                                <option value="<?= $type['id'] ?>"><?= e($type['type_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Loan Amount</label>
                            <div class="input-group">
                                <span class="input-group-text"><?= $currency ?></span>
                                <input type="number" name="loan_amount" class="form-control" required step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Interest Rate (%)</label>
                            <input type="number" name="interest_rate" class="form-control" step="0.1" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Tenure (Months)</label>
                            <input type="number" name="tenure_months" class="form-control" required min="1" max="60">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Monthly Deduction</label>
                            <div class="input-group">
                                <span class="input-group-text"><?= $currency ?></span>
                                <input type="number" name="monthly_deduction" class="form-control" step="0.01" readonly>
                            </div>
                            <small class="form-hint">Auto-calculated based on amount and tenure</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Purpose/Reason</label>
                            <textarea name="purpose" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- New Loan Type Modal -->
<div class="modal modal-blur fade" id="newLoanTypeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Loan Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="/hr-payroll/loan-types">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label required">Type Name</label>
                            <input type="text" name="type_name" class="form-control" required
                                   placeholder="e.g., Salary Advance, Emergency Loan">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Max Amount</label>
                            <input type="number" name="max_amount" class="form-control" step="0.01">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Interest Rate (%)</label>
                            <input type="number" name="interest_rate" class="form-control" step="0.1" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Max Tenure</label>
                            <input type="number" name="max_tenure_months" class="form-control" value="12">
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

<script>
// Auto-calculate monthly deduction
document.addEventListener('DOMContentLoaded', function() {
    const amountInput = document.querySelector('input[name="loan_amount"]');
    const tenureInput = document.querySelector('input[name="tenure_months"]');
    const deductionInput = document.querySelector('input[name="monthly_deduction"]');

    function calculateDeduction() {
        const amount = parseFloat(amountInput.value) || 0;
        const tenure = parseInt(tenureInput.value) || 1;
        const deduction = amount / tenure;
        deductionInput.value = deduction.toFixed(2);
    }

    if (amountInput && tenureInput) {
        amountInput.addEventListener('input', calculateDeduction);
        tenureInput.addEventListener('input', calculateDeduction);
    }
});
</script>
