<?php
/**
 * Budget Reports Tab
 * Various budget analysis reports
 */

$periodId = $tabData['period_id'] ?? null;
?>

<div class="row g-3">
    <!-- Budget Summary Report -->
    <div class="col-md-4">
        <a href="/finance/budgets/reports/summary" class="card card-link card-link-pop h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <span class="avatar bg-primary-lt me-3">
                        <i class="ti ti-report"></i>
                    </span>
                    <div>
                        <div class="font-weight-medium">Budget Summary</div>
                        <div class="text-muted small">Overview of all budgets by account and cost center</div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Variance Analysis -->
    <div class="col-md-4">
        <a href="/finance/budgets/reports/variance" class="card card-link card-link-pop h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <span class="avatar bg-warning-lt me-3">
                        <i class="ti ti-arrows-diff"></i>
                    </span>
                    <div>
                        <div class="font-weight-medium">Variance Analysis</div>
                        <div class="text-muted small">Compare budgeted vs actual spending</div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Monthly Trend -->
    <div class="col-md-4">
        <a href="/finance/budgets/reports/trend" class="card card-link card-link-pop h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <span class="avatar bg-info-lt me-3">
                        <i class="ti ti-chart-line"></i>
                    </span>
                    <div>
                        <div class="font-weight-medium">Monthly Trend</div>
                        <div class="text-muted small">Spending trends over time</div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Cost Center Analysis -->
    <div class="col-md-4">
        <a href="/finance/budgets/reports/cost-centers" class="card card-link card-link-pop h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <span class="avatar bg-success-lt me-3">
                        <i class="ti ti-building"></i>
                    </span>
                    <div>
                        <div class="font-weight-medium">Cost Center Analysis</div>
                        <div class="text-muted small">Budget utilization by department</div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Overrun Report -->
    <div class="col-md-4">
        <a href="/finance/budgets/reports/overruns" class="card card-link card-link-pop h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <span class="avatar bg-danger-lt me-3">
                        <i class="ti ti-alert-triangle"></i>
                    </span>
                    <div>
                        <div class="font-weight-medium">Overrun Report</div>
                        <div class="text-muted small">All budget overruns and approvals</div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Forecast Report -->
    <div class="col-md-4">
        <a href="/finance/budgets/reports/forecast" class="card card-link card-link-pop h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <span class="avatar bg-purple-lt me-3">
                        <i class="ti ti-crystal-ball"></i>
                    </span>
                    <div>
                        <div class="font-weight-medium">Budget Forecast</div>
                        <div class="text-muted small">Projected year-end spending</div>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Quick Stats -->
<div class="card mt-4">
    <div class="card-header">
        <h4 class="card-title">Quick Insights</h4>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h5>Top 5 Budget Consumers</h5>
                <div class="text-muted">
                    <p class="mb-2">This section will show the top 5 accounts/cost centers with highest budget utilization.</p>
                    <small>Data will be populated when transactions are recorded.</small>
                </div>
            </div>
            <div class="col-md-6">
                <h5>Budgets Requiring Attention</h5>
                <div class="text-muted">
                    <p class="mb-2">Budgets approaching or exceeding their limits will be highlighted here.</p>
                    <small>Monitor this section regularly for proactive budget management.</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Period Management -->
<div class="card mt-4">
    <div class="card-header">
        <h4 class="card-title">Budget Period Management</h4>
        <div class="card-actions">
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#periodModal">
                <i class="ti ti-plus me-1"></i>New Period
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead>
                    <tr>
                        <th>Period Name</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($periods as $period): ?>
                    <tr>
                        <td>
                            <strong><?= e($period['name']) ?></strong>
                            <?php if ($period['is_current']): ?>
                            <span class="badge bg-green ms-2">Current</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('M j, Y', strtotime($period['start_date'])) ?></td>
                        <td><?= date('M j, Y', strtotime($period['end_date'])) ?></td>
                        <td>
                            <?php
                            $statusColors = ['draft' => 'secondary', 'active' => 'success', 'closed' => 'dark'];
                            ?>
                            <span class="badge bg-<?= $statusColors[$period['status']] ?? 'secondary' ?>">
                                <?= ucfirst($period['status']) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <?php if (!$period['is_current'] && $period['status'] === 'active'): ?>
                            <button type="button" class="btn btn-sm btn-ghost-primary" onclick="setCurrentPeriod(<?= $period['id'] ?>)">
                                Set as Current
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- New Period Modal -->
<div class="modal modal-blur fade" id="periodModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Budget Period</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="periodForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Period Name</label>
                        <input type="text" class="form-control" name="name" required
                               placeholder="e.g., Fiscal Year 2027">
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label required">Start Date</label>
                            <input type="date" class="form-control" name="start_date" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">End Date</label>
                            <input type="date" class="form-control" name="end_date" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-check">
                            <input type="checkbox" class="form-check-input" name="is_current">
                            <span class="form-check-label">Set as current period</span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Period</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('periodForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const data = {
        name: formData.get('name'),
        start_date: formData.get('start_date'),
        end_date: formData.get('end_date'),
        is_current: formData.get('is_current') ? true : false
    };

    fetch('/finance/budgets/api/periods', {
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
            bootstrap.Modal.getInstance(document.getElementById('periodModal')).hide();
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to create period');
    });
});

function setCurrentPeriod(id) {
    if (!confirm('Set this as the current budget period?')) return;

    fetch(`/finance/budgets/api/periods/${id}/set-current`, {
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
        alert('Failed to set current period');
    });
}
</script>
