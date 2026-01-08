<?php
/**
 * Budgets - Content (tabbed view)
 * Tabs: Budget Setup, Approval Queue, Budget vs Actual, Reports
 */

$activeTab = $activeTab ?? 'setup';
$stats = $stats ?? ['total_budget' => 0, 'total_spent' => 0, 'total_committed' => 0, 'total_available' => 0, 'pending_approvals' => 0, 'budgets_overrun' => 0, 'utilization_rate' => 0];
$periods = $periods ?? [];
$currentPeriod = $currentPeriod ?? [];
$accounts = $accounts ?? [];
$costCenters = $costCenters ?? [];
$tabData = $tabData ?? [];
?>

<div class="page-header d-print-none py-2">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <a href="/finance" class="btn btn-ghost-secondary btn-sm mb-1">
                    <i class="ti ti-arrow-left me-1"></i>Back to Finance
                </a>
                <h2 class="page-title mb-0">
                    <i class="ti ti-calculator me-2"></i>
                    Budget Management
                </h2>
                <div class="text-muted small">
                    <?= e($currentPeriod['name'] ?? 'No Period Set') ?>
                    <?php if (!empty($currentPeriod['start_date'])): ?>
                        (<?= date('M Y', strtotime($currentPeriod['start_date'])) ?> - <?= date('M Y', strtotime($currentPeriod['end_date'])) ?>)
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-auto ms-auto">
                <div class="btn-list">
                    <?php if ($activeTab === 'setup'): ?>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#budgetModal">
                        <i class="ti ti-plus me-2"></i>New Budget
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body pt-2">
    <div class="container-xl">
        <!-- Summary Stats -->
        <div class="row row-deck row-cards mb-2">
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Total Budget</div>
                        </div>
                        <div class="h1 mb-0">KES <?= number_format($stats['total_budget']) ?></div>
                        <div class="text-muted small">Annual allocation</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Spent + Committed</div>
                        </div>
                        <div class="h1 mb-0 text-warning">
                            KES <?= number_format($stats['total_spent'] + $stats['total_committed']) ?>
                        </div>
                        <div class="text-muted small">
                            <?= number_format($stats['utilization_rate'], 1) ?>% utilization
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Available</div>
                        </div>
                        <div class="h1 mb-0 text-success">KES <?= number_format($stats['total_available']) ?></div>
                        <div class="text-muted small">Remaining budget</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Pending Approvals</div>
                            <?php if ($stats['budgets_overrun'] > 0): ?>
                            <div class="ms-auto">
                                <span class="badge bg-danger"><?= $stats['budgets_overrun'] ?> overrun</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="h1 mb-0 <?= $stats['pending_approvals'] > 0 ? 'text-danger' : '' ?>">
                            <?= number_format($stats['pending_approvals']) ?>
                        </div>
                        <div class="text-muted small">Awaiting action</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Anchor for tab navigation - below stat cards -->
        <a id="budget-tabs" name="budget-tabs"></a>

        <!-- Tabs -->
        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
                    <li class="nav-item">
                        <a href="/finance/budgets/setup#budget-tabs" class="nav-link <?= $activeTab === 'setup' ? 'active' : '' ?>">
                            <i class="ti ti-settings me-2"></i>Budget Setup
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/finance/budgets/approvals#budget-tabs" class="nav-link <?= $activeTab === 'approvals' ? 'active' : '' ?>">
                            <i class="ti ti-checklist me-2"></i>Approval Queue
                            <?php if ($stats['pending_approvals'] > 0): ?>
                            <span class="badge bg-red ms-2"><?= $stats['pending_approvals'] ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/finance/budgets/tracking#budget-tabs" class="nav-link <?= $activeTab === 'tracking' ? 'active' : '' ?>">
                            <i class="ti ti-chart-bar me-2"></i>Budget vs Actual
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/finance/budgets/reports#budget-tabs" class="nav-link <?= $activeTab === 'reports' ? 'active' : '' ?>">
                            <i class="ti ti-report me-2"></i>Reports
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <?php
                // Load tab-specific content
                switch ($activeTab) {
                    case 'setup':
                        include __DIR__ . '/budgets/_setup_tab.php';
                        break;
                    case 'approvals':
                        include __DIR__ . '/budgets/_approvals_tab.php';
                        break;
                    case 'tracking':
                        include __DIR__ . '/budgets/_tracking_tab.php';
                        break;
                    case 'reports':
                        include __DIR__ . '/budgets/_reports_tab.php';
                        break;
                }
                ?>
            </div>
        </div>
    </div>
</div>

<!-- Budget Modal -->
<div class="modal modal-blur fade" id="budgetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="budgetModalTitle">New Budget</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="budgetForm">
                <div class="modal-body">
                    <input type="hidden" name="budget_id" id="budget_id">
                    <input type="hidden" name="budget_period_id" value="<?= $currentPeriod['id'] ?? '' ?>">

                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label required">Budget Name</label>
                            <input type="text" class="form-control" name="name" id="budget_name" required
                                   placeholder="e.g., Office Supplies - Administration">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Annual Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">KES</span>
                                <input type="number" class="form-control" name="annual_amount" id="annual_amount"
                                       required min="0" step="0.01" placeholder="0.00">
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label required">Expense Account</label>
                            <select class="form-select" name="account_id" id="account_id" required>
                                <option value="">Select Account...</option>
                                <?php foreach ($accounts as $account): ?>
                                <option value="<?= $account['id'] ?>">
                                    <?= e($account['account_code']) ?> - <?= e($account['account_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cost Center</label>
                            <select class="form-select" name="cost_center_id" id="cost_center_id">
                                <option value="">All Departments</option>
                                <?php foreach ($costCenters as $cc): ?>
                                <option value="<?= $cc['id'] ?>"><?= e($cc['code']) ?> - <?= e($cc['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" id="budget_notes" rows="2"
                                  placeholder="Budget description or notes..."></textarea>
                    </div>

                    <!-- Monthly Allocation Section -->
                    <div class="card bg-light">
                        <div class="card-header">
                            <h4 class="card-title mb-0">Monthly Allocations</h4>
                            <div class="card-actions">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary" onclick="distributeEvenly()">
                                        Distribute Evenly
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="clearAllocations()">
                                        Clear
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row g-2" id="monthlyAllocations">
                                <!-- Will be populated by JavaScript -->
                            </div>
                            <div class="mt-2 text-end">
                                <strong>Total: KES <span id="allocationTotal">0.00</span></strong>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveBudgetBtn">Save Budget</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const currentPeriod = <?= json_encode($currentPeriod) ?>;
const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

document.addEventListener('DOMContentLoaded', function() {
    initializeMonthlyAllocations();

    // Handle form submission
    document.getElementById('budgetForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveBudget();
    });

    // Update allocations when annual amount changes
    document.getElementById('annual_amount').addEventListener('change', function() {
        if (confirm('Distribute this amount evenly across months?')) {
            distributeEvenly();
        }
    });
});

function initializeMonthlyAllocations() {
    if (!currentPeriod.start_date) return;

    const container = document.getElementById('monthlyAllocations');
    const startDate = new Date(currentPeriod.start_date);
    const endDate = new Date(currentPeriod.end_date);

    let html = '';
    let current = new Date(startDate);

    while (current <= endDate) {
        const monthKey = current.toISOString().substring(0, 7);
        const monthName = months[current.getMonth()];
        const year = current.getFullYear();

        html += `
            <div class="col-md-3 col-sm-4 col-6">
                <label class="form-label small">${monthName} ${year}</label>
                <input type="number" class="form-control form-control-sm monthly-amount"
                       data-month="${monthKey}" min="0" step="0.01" value="0"
                       onchange="updateAllocationTotal()">
            </div>
        `;

        current.setMonth(current.getMonth() + 1);
    }

    container.innerHTML = html;
}

function distributeEvenly() {
    const annualAmount = parseFloat(document.getElementById('annual_amount').value) || 0;
    const inputs = document.querySelectorAll('.monthly-amount');
    const monthlyAmount = (annualAmount / inputs.length).toFixed(2);

    inputs.forEach(input => {
        input.value = monthlyAmount;
    });

    updateAllocationTotal();
}

function clearAllocations() {
    document.querySelectorAll('.monthly-amount').forEach(input => {
        input.value = '0';
    });
    updateAllocationTotal();
}

function updateAllocationTotal() {
    let total = 0;
    document.querySelectorAll('.monthly-amount').forEach(input => {
        total += parseFloat(input.value) || 0;
    });
    document.getElementById('allocationTotal').textContent = total.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function saveBudget() {
    const form = document.getElementById('budgetForm');
    const formData = new FormData(form);
    const budgetId = formData.get('budget_id');

    // Collect monthly amounts
    const monthlyAmounts = [];
    document.querySelectorAll('.monthly-amount').forEach(input => {
        monthlyAmounts.push(parseFloat(input.value) || 0);
    });

    const data = {
        budget_period_id: formData.get('budget_period_id'),
        name: formData.get('name'),
        account_id: formData.get('account_id'),
        cost_center_id: formData.get('cost_center_id') || null,
        annual_amount: parseFloat(formData.get('annual_amount')) || 0,
        notes: formData.get('notes'),
        monthly_amounts: monthlyAmounts
    };

    const url = budgetId
        ? `/finance/budgets/api/budgets/${budgetId}`
        : '/finance/budgets/api/budgets';

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('budgetModal')).hide();
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while saving the budget');
    });
}

function editBudget(id) {
    fetch(`/finance/budgets/api/budgets/${id}`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const budget = result.data;
                document.getElementById('budget_id').value = budget.id;
                document.getElementById('budget_name').value = budget.name;
                document.getElementById('annual_amount').value = budget.annual_amount;
                document.getElementById('account_id').value = budget.account_id;
                document.getElementById('cost_center_id').value = budget.cost_center_id || '';
                document.getElementById('budget_notes').value = budget.notes || '';
                document.getElementById('budgetModalTitle').textContent = 'Edit Budget';

                // Populate monthly allocations
                if (budget.lines) {
                    budget.lines.forEach(line => {
                        const monthKey = line.month_year.substring(0, 7);
                        const input = document.querySelector(`input[data-month="${monthKey}"]`);
                        if (input) {
                            input.value = line.allocated_amount;
                        }
                    });
                    updateAllocationTotal();
                }

                new bootstrap.Modal(document.getElementById('budgetModal')).show();
            } else {
                alert('Error: ' + result.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to load budget details');
        });
}

function deleteBudget(id, name) {
    if (!confirm(`Are you sure you want to delete budget "${name}"?\nThis action cannot be undone.`)) {
        return;
    }

    fetch(`/finance/budgets/api/budgets/${id}/delete`, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to delete budget');
    });
}

function submitForApproval(id) {
    if (!confirm('Submit this budget for approval?')) return;

    fetch(`/finance/budgets/api/budgets/${id}/submit`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert('Budget submitted for approval');
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to submit budget');
    });
}

// Reset modal when closed
document.getElementById('budgetModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('budgetForm').reset();
    document.getElementById('budget_id').value = '';
    document.getElementById('budgetModalTitle').textContent = 'New Budget';
    clearAllocations();
});
</script>
