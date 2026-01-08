<?php
/**
 * Budget Setup Tab - Simplified View
 * Shows budget highlights with expandable details
 */

$budgets = $tabData ?? [];

// Group budgets by status for summary
$approvedBudgets = array_filter($budgets, function($b) { return $b['status'] === 'approved'; });
$pendingBudgets = array_filter($budgets, function($b) { return $b['status'] === 'pending_approval'; });
$draftBudgets = array_filter($budgets, function($b) { return $b['status'] === 'draft'; });

$totalAllocated = array_sum(array_column($approvedBudgets, 'annual_amount'));
$totalSpent = array_sum(array_column($budgets, 'total_spent')) + array_sum(array_column($budgets, 'total_committed'));
?>

<!-- Filters -->
<div class="row mb-3">
    <div class="col-md-4">
        <div class="input-group">
            <span class="input-group-text"><i class="ti ti-search"></i></span>
            <input type="text" class="form-control" id="budgetSearch" placeholder="Search budgets...">
        </div>
    </div>
    <div class="col-md-3">
        <select class="form-select" id="statusFilter">
            <option value="">All Status</option>
            <option value="draft">Draft (<?= count($draftBudgets) ?>)</option>
            <option value="pending_approval">Pending Approval (<?= count($pendingBudgets) ?>)</option>
            <option value="approved">Approved (<?= count($approvedBudgets) ?>)</option>
        </select>
    </div>
    <div class="col-md-3">
        <select class="form-select" id="costCenterFilter">
            <option value="">All Cost Centers</option>
            <?php foreach ($costCenters as $cc): ?>
            <option value="<?= $cc['id'] ?>"><?= e($cc['code']) ?> - <?= e($cc['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<?php if (empty($budgets)): ?>
<div class="empty">
    <div class="empty-img">
        <i class="ti ti-calculator-off" style="font-size: 4rem; color: #adb5bd;"></i>
    </div>
    <p class="empty-title">No budgets configured</p>
    <p class="empty-subtitle text-muted">
        Start by creating budget allocations for expense accounts.
    </p>
    <div class="empty-action">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#budgetModal">
            <i class="ti ti-plus me-2"></i>Create First Budget
        </button>
    </div>
</div>
<?php else: ?>

<!-- Budget Cards - Simplified View -->
<div class="row row-cards" id="budgetCards">
    <?php foreach ($budgets as $budget):
        $spent = $budget['total_spent'] + $budget['total_committed'];
        $available = $budget['annual_amount'] - $spent;
        $utilization = $budget['annual_amount'] > 0 ? ($spent / $budget['annual_amount']) * 100 : 0;

        // Determine status color
        if ($utilization > 100) {
            $progressClass = 'bg-danger';
            $statusIcon = 'ti-alert-triangle text-danger';
        } elseif ($utilization > 80) {
            $progressClass = 'bg-warning';
            $statusIcon = 'ti-alert-circle text-warning';
        } else {
            $progressClass = 'bg-success';
            $statusIcon = 'ti-circle-check text-success';
        }

        $statusColors = [
            'draft' => 'secondary',
            'pending_approval' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger'
        ];
        $badgeColor = $statusColors[$budget['status']] ?? 'secondary';
    ?>
    <div class="col-md-6 col-lg-4 budget-card"
         data-status="<?= $budget['status'] ?>"
         data-cost-center="<?= $budget['cost_center_id'] ?? '' ?>"
         data-search="<?= strtolower($budget['name'] . ' ' . $budget['budget_code'] . ' ' . $budget['account_name']) ?>">
        <div class="card">
            <div class="card-header">
                <div class="d-flex w-100">
                    <div class="flex-grow-1">
                        <h4 class="card-title mb-1"><?= e($budget['name']) ?></h4>
                        <div class="text-muted small">
                            <code><?= e($budget['budget_code']) ?></code>
                            <span class="mx-1">|</span>
                            <?= e($budget['cost_center_name'] ?? 'All Depts') ?>
                        </div>
                    </div>
                    <div>
                        <span class="badge bg-<?= $badgeColor ?>"><?= ucfirst(str_replace('_', ' ', $budget['status'])) ?></span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Key Figures -->
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="text-muted small">Annual Budget</div>
                        <div class="h4 mb-0">KES <?= number_format($budget['annual_amount']) ?></div>
                    </div>
                    <div class="col-6 text-end">
                        <div class="text-muted small">Available</div>
                        <div class="h4 mb-0 <?= $available < 0 ? 'text-danger' : 'text-success' ?>">
                            KES <?= number_format($available) ?>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mb-2">
                    <div class="d-flex justify-content-between small mb-1">
                        <span><i class="<?= $statusIcon ?> me-1"></i><?= number_format($utilization, 1) ?>% utilized</span>
                        <span>Spent: KES <?= number_format($spent) ?></span>
                    </div>
                    <div class="progress progress-sm">
                        <div class="progress-bar <?= $progressClass ?>" style="width: <?= min($utilization, 100) ?>%"></div>
                    </div>
                </div>

                <!-- Account Info -->
                <div class="text-muted small">
                    <i class="ti ti-file-invoice me-1"></i>
                    <?= e($budget['account_code']) ?> - <?= e($budget['account_name']) ?>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-primary flex-grow-1" onclick="viewBudgetDetails(<?= $budget['id'] ?>)">
                        <i class="ti ti-eye me-1"></i>View Details
                    </button>
                    <?php if ($budget['status'] === 'draft'): ?>
                    <button type="button" class="btn btn-sm btn-ghost-secondary" onclick="editBudget(<?= $budget['id'] ?>)" title="Edit">
                        <i class="ti ti-edit"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-ghost-success" onclick="submitForApproval(<?= $budget['id'] ?>)" title="Submit">
                        <i class="ti ti-send"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-ghost-danger" onclick="deleteBudget(<?= $budget['id'] ?>, '<?= e(addslashes($budget['name'])) ?>')" title="Delete">
                        <i class="ti ti-trash"></i>
                    </button>
                    <?php elseif ($budget['status'] === 'approved'): ?>
                    <button type="button" class="btn btn-sm btn-ghost-secondary" onclick="editMonthlyAllocations(<?= $budget['id'] ?>)" title="Edit Allocations">
                        <i class="ti ti-calendar-event"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Summary Footer -->
<div class="card mt-3">
    <div class="card-body py-2">
        <div class="row text-center">
            <div class="col-md-3">
                <div class="text-muted small">Total Budgets</div>
                <strong><?= count($budgets) ?></strong>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Total Allocated (Approved)</div>
                <strong>KES <?= number_format($totalAllocated) ?></strong>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Total Utilized</div>
                <strong>KES <?= number_format($totalSpent) ?></strong>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Overall Utilization</div>
                <strong><?= $totalAllocated > 0 ? number_format(($totalSpent / $totalAllocated) * 100, 1) : 0 ?>%</strong>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Budget Details Modal (with Monthly Breakdown) -->
<div class="modal modal-blur fade" id="budgetDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Budget Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="budgetDetailsContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2">Loading...</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="editAllocationsBtn" style="display:none;" onclick="switchToEditMode()">
                    <i class="ti ti-edit me-1"></i>Edit Monthly Allocations
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Monthly Allocations Modal -->
<div class="modal modal-blur fade" id="editAllocationsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Monthly Allocations</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editAllocationsForm">
                <div class="modal-body">
                    <input type="hidden" id="edit_budget_id">
                    <div class="alert alert-info">
                        <i class="ti ti-info-circle me-2"></i>
                        Modify the allocated amounts for each month. Changes will be saved immediately.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm" id="allocationsTable">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th class="text-end">Allocated</th>
                                    <th class="text-end">Spent</th>
                                    <th class="text-end">Committed</th>
                                    <th class="text-end">Available</th>
                                </tr>
                            </thead>
                            <tbody id="allocationsTableBody">
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td>Total</td>
                                    <td class="text-end" id="totalAllocated">0</td>
                                    <td class="text-end" id="totalSpent">0</td>
                                    <td class="text-end" id="totalCommitted">0</td>
                                    <td class="text-end" id="totalAvailable">0</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
let currentBudgetId = null;
let currentBudgetData = null;

// Filter functionality
document.getElementById('budgetSearch').addEventListener('input', filterBudgets);
document.getElementById('statusFilter').addEventListener('change', filterBudgets);
document.getElementById('costCenterFilter').addEventListener('change', filterBudgets);

function filterBudgets() {
    const search = document.getElementById('budgetSearch').value.toLowerCase();
    const status = document.getElementById('statusFilter').value;
    const costCenter = document.getElementById('costCenterFilter').value;

    document.querySelectorAll('.budget-card').forEach(card => {
        const cardSearch = card.dataset.search;
        const cardStatus = card.dataset.status;
        const cardCostCenter = card.dataset.costCenter;

        const matchSearch = !search || cardSearch.includes(search);
        const matchStatus = !status || cardStatus === status;
        const matchCostCenter = !costCenter || cardCostCenter === costCenter;

        card.style.display = matchSearch && matchStatus && matchCostCenter ? '' : 'none';
    });
}

function viewBudgetDetails(id) {
    currentBudgetId = id;
    const modal = new bootstrap.Modal(document.getElementById('budgetDetailsModal'));
    modal.show();

    fetch(`/finance/budgets/api/budgets/${id}`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                currentBudgetData = result.data;
                renderBudgetDetails(result.data);

                // Show edit button for approved budgets
                const editBtn = document.getElementById('editAllocationsBtn');
                editBtn.style.display = result.data.status === 'approved' ? 'inline-block' : 'none';
            } else {
                document.getElementById('budgetDetailsContent').innerHTML =
                    `<div class="alert alert-danger">${result.message}</div>`;
            }
        })
        .catch(error => {
            document.getElementById('budgetDetailsContent').innerHTML =
                '<div class="alert alert-danger">Failed to load budget details</div>';
        });
}

function renderBudgetDetails(budget) {
    // Calculate totals from lines
    let totalAllocated = 0, totalSpent = 0, totalCommitted = 0;

    let linesHtml = '';
    if (budget.lines && budget.lines.length > 0) {
        budget.lines.forEach(line => {
            const date = new Date(line.month_year);
            const monthName = months[date.getMonth()] + ' ' + date.getFullYear();
            const allocated = parseFloat(line.allocated_amount) || 0;
            const spent = parseFloat(line.spent_amount) || 0;
            const committed = parseFloat(line.committed_amount) || 0;
            const available = parseFloat(line.available_amount) || 0;

            totalAllocated += allocated;
            totalSpent += spent;
            totalCommitted += committed;

            const utilization = allocated > 0 ? ((spent + committed) / allocated * 100) : 0;
            const availableClass = available < 0 ? 'text-danger fw-bold' : (available < allocated * 0.2 ? 'text-warning' : 'text-success');
            const rowClass = available < 0 ? 'table-danger' : '';

            linesHtml += `
                <tr class="${rowClass}">
                    <td><strong>${monthName}</strong></td>
                    <td class="text-end">KES ${allocated.toLocaleString()}</td>
                    <td class="text-end">KES ${spent.toLocaleString()}</td>
                    <td class="text-end">KES ${committed.toLocaleString()}</td>
                    <td class="text-end ${availableClass}">KES ${available.toLocaleString()}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="progress progress-sm flex-grow-1 me-2" style="width: 60px;">
                                <div class="progress-bar ${utilization > 100 ? 'bg-danger' : utilization > 80 ? 'bg-warning' : 'bg-success'}"
                                     style="width: ${Math.min(utilization, 100)}%"></div>
                            </div>
                            <small class="${utilization > 100 ? 'text-danger' : ''}">${utilization.toFixed(0)}%</small>
                        </div>
                    </td>
                </tr>
            `;
        });
    }

    const totalAvailable = totalAllocated - totalSpent - totalCommitted;
    const overallUtil = totalAllocated > 0 ? ((totalSpent + totalCommitted) / totalAllocated * 100) : 0;

    document.getElementById('budgetDetailsContent').innerHTML = `
        <!-- Budget Summary -->
        <div class="row mb-4">
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-5">Budget Code:</dt>
                    <dd class="col-7"><code>${budget.budget_code}</code></dd>
                    <dt class="col-5">Name:</dt>
                    <dd class="col-7"><strong>${budget.name}</strong></dd>
                    <dt class="col-5">Account:</dt>
                    <dd class="col-7">${budget.account_code} - ${budget.account_name}</dd>
                    <dt class="col-5">Cost Center:</dt>
                    <dd class="col-7">${budget.cost_center_name || 'All Departments'}</dd>
                </dl>
            </div>
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-5">Period:</dt>
                    <dd class="col-7">${budget.period_name}</dd>
                    <dt class="col-5">Status:</dt>
                    <dd class="col-7"><span class="badge bg-${budget.status === 'approved' ? 'success' : budget.status === 'pending_approval' ? 'warning' : 'secondary'}">${budget.status.replace('_', ' ')}</span></dd>
                    <dt class="col-5">Annual Budget:</dt>
                    <dd class="col-7"><strong class="h5">KES ${parseFloat(budget.annual_amount).toLocaleString()}</strong></dd>
                </dl>
            </div>
        </div>

        <!-- Overall Progress -->
        <div class="card bg-light mb-4">
            <div class="card-body py-3">
                <div class="row text-center">
                    <div class="col-3">
                        <div class="text-muted small">Allocated</div>
                        <div class="h5 mb-0">KES ${totalAllocated.toLocaleString()}</div>
                    </div>
                    <div class="col-3">
                        <div class="text-muted small">Spent</div>
                        <div class="h5 mb-0 text-primary">KES ${totalSpent.toLocaleString()}</div>
                    </div>
                    <div class="col-3">
                        <div class="text-muted small">Committed</div>
                        <div class="h5 mb-0 text-warning">KES ${totalCommitted.toLocaleString()}</div>
                    </div>
                    <div class="col-3">
                        <div class="text-muted small">Available</div>
                        <div class="h5 mb-0 ${totalAvailable < 0 ? 'text-danger' : 'text-success'}">KES ${totalAvailable.toLocaleString()}</div>
                    </div>
                </div>
                <div class="progress mt-3" style="height: 8px;">
                    <div class="progress-bar bg-primary" style="width: ${(totalSpent / totalAllocated * 100)}%" title="Spent"></div>
                    <div class="progress-bar bg-warning" style="width: ${(totalCommitted / totalAllocated * 100)}%" title="Committed"></div>
                </div>
                <div class="text-center mt-1 small text-muted">${overallUtil.toFixed(1)}% utilized</div>
            </div>
        </div>

        <!-- Monthly Breakdown -->
        <h4 class="mb-3"><i class="ti ti-calendar me-2"></i>Monthly Breakdown</h4>
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Month</th>
                        <th class="text-end">Allocated</th>
                        <th class="text-end">Spent</th>
                        <th class="text-end">Committed</th>
                        <th class="text-end">Available</th>
                        <th style="width: 120px;">Utilization</th>
                    </tr>
                </thead>
                <tbody>
                    ${linesHtml || '<tr><td colspan="6" class="text-center text-muted">No allocations yet</td></tr>'}
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td>Total</td>
                        <td class="text-end">KES ${totalAllocated.toLocaleString()}</td>
                        <td class="text-end">KES ${totalSpent.toLocaleString()}</td>
                        <td class="text-end">KES ${totalCommitted.toLocaleString()}</td>
                        <td class="text-end ${totalAvailable < 0 ? 'text-danger' : 'text-success'}">KES ${totalAvailable.toLocaleString()}</td>
                        <td>${overallUtil.toFixed(1)}%</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        ${budget.notes ? `<div class="mt-3 p-3 bg-light rounded"><strong>Notes:</strong><br>${budget.notes}</div>` : ''}
    `;
}

function switchToEditMode() {
    if (!currentBudgetData) return;

    // Close details modal
    bootstrap.Modal.getInstance(document.getElementById('budgetDetailsModal')).hide();

    // Open edit modal
    editMonthlyAllocations(currentBudgetId);
}

function editMonthlyAllocations(id) {
    document.getElementById('edit_budget_id').value = id;

    fetch(`/finance/budgets/api/budgets/${id}`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                populateAllocationsTable(result.data);
                new bootstrap.Modal(document.getElementById('editAllocationsModal')).show();
            } else {
                alert('Error: ' + result.message);
            }
        })
        .catch(error => {
            alert('Failed to load budget details');
        });
}

function populateAllocationsTable(budget) {
    const tbody = document.getElementById('allocationsTableBody');
    let html = '';
    let totalAlloc = 0, totalSpent = 0, totalComm = 0;

    if (budget.lines && budget.lines.length > 0) {
        budget.lines.forEach(line => {
            const date = new Date(line.month_year);
            const monthName = months[date.getMonth()] + ' ' + date.getFullYear();
            const allocated = parseFloat(line.allocated_amount) || 0;
            const spent = parseFloat(line.spent_amount) || 0;
            const committed = parseFloat(line.committed_amount) || 0;
            const available = allocated - spent - committed;

            totalAlloc += allocated;
            totalSpent += spent;
            totalComm += committed;

            const isEditable = spent === 0 && committed === 0;

            html += `
                <tr>
                    <td><strong>${monthName}</strong></td>
                    <td class="text-end">
                        <input type="number" class="form-control form-control-sm text-end allocation-input"
                               data-line-id="${line.id}" value="${allocated}" min="0" step="0.01"
                               ${!isEditable ? 'readonly' : ''} onchange="updateTotals()"
                               style="width: 120px; display: inline-block;">
                        ${!isEditable ? '<small class="text-muted d-block">Has transactions</small>' : ''}
                    </td>
                    <td class="text-end text-muted">KES ${spent.toLocaleString()}</td>
                    <td class="text-end text-muted">KES ${committed.toLocaleString()}</td>
                    <td class="text-end ${available < 0 ? 'text-danger' : 'text-success'} available-cell"
                        data-spent="${spent}" data-committed="${committed}">
                        KES ${available.toLocaleString()}
                    </td>
                </tr>
            `;
        });
    }

    tbody.innerHTML = html;
    updateTotals();
}

function updateTotals() {
    let totalAlloc = 0, totalSpent = 0, totalComm = 0;

    document.querySelectorAll('.allocation-input').forEach(input => {
        const row = input.closest('tr');
        const allocated = parseFloat(input.value) || 0;
        const availableCell = row.querySelector('.available-cell');
        const spent = parseFloat(availableCell.dataset.spent) || 0;
        const committed = parseFloat(availableCell.dataset.committed) || 0;
        const available = allocated - spent - committed;

        totalAlloc += allocated;
        totalSpent += spent;
        totalComm += committed;

        availableCell.textContent = 'KES ' + available.toLocaleString();
        availableCell.className = 'text-end available-cell ' + (available < 0 ? 'text-danger' : 'text-success');
    });

    document.getElementById('totalAllocated').textContent = 'KES ' + totalAlloc.toLocaleString();
    document.getElementById('totalSpent').textContent = 'KES ' + totalSpent.toLocaleString();
    document.getElementById('totalCommitted').textContent = 'KES ' + totalComm.toLocaleString();
    document.getElementById('totalAvailable').textContent = 'KES ' + (totalAlloc - totalSpent - totalComm).toLocaleString();
}

// Handle allocations form submission
document.getElementById('editAllocationsForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const budgetId = document.getElementById('edit_budget_id').value;
    const allocations = [];

    document.querySelectorAll('.allocation-input').forEach(input => {
        if (!input.readOnly) {
            allocations.push({
                id: input.dataset.lineId,
                amount: parseFloat(input.value) || 0
            });
        }
    });

    fetch(`/finance/budgets/api/budgets/${budgetId}/allocations`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ allocations: allocations })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('editAllocationsModal')).hide();
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    })
    .catch(error => {
        alert('Failed to save allocations');
    });
});
</script>
