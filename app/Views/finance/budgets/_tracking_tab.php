<?php
/**
 * Budget vs Actual Tracking Tab
 * Shows current month budget performance
 */

$tracking = $tabData ?? [];
$currentMonth = date('F Y');
?>

<!-- Month Selector -->
<div class="row mb-3">
    <div class="col-md-4">
        <label class="form-label">Select Month</label>
        <input type="month" class="form-control" id="trackingMonth" value="<?= date('Y-m') ?>" onchange="loadTrackingData()">
    </div>
    <div class="col-md-4">
        <label class="form-label">Filter by Status</label>
        <select class="form-select" id="utilizationFilter" onchange="filterTracking()">
            <option value="">All Budgets</option>
            <option value="under">Under Budget (&lt;80%)</option>
            <option value="warning">Near Limit (80-100%)</option>
            <option value="over">Over Budget (&gt;100%)</option>
        </select>
    </div>
    <div class="col-md-4 d-flex align-items-end">
        <button type="button" class="btn btn-outline-primary" onclick="exportTracking()">
            <i class="ti ti-download me-2"></i>Export Report
        </button>
    </div>
</div>

<?php if (empty($tracking)): ?>
<div class="empty">
    <div class="empty-img">
        <i class="ti ti-chart-bar-off" style="font-size: 4rem; color: #adb5bd;"></i>
    </div>
    <p class="empty-title">No budget data</p>
    <p class="empty-subtitle text-muted">
        No approved budgets found for tracking. Create and approve budgets first.
    </p>
</div>
<?php else: ?>

<!-- Summary Cards -->
<div class="row row-deck row-cards mb-3">
    <?php
    $totalAllocated = array_sum(array_column($tracking, 'allocated_amount'));
    $totalSpent = array_sum(array_column($tracking, 'spent_amount'));
    $totalCommitted = array_sum(array_column($tracking, 'committed_amount'));
    $overBudgetCount = count(array_filter($tracking, fn($t) => ($t['utilization_pct'] ?? 0) > 100));
    ?>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="text-muted small">Monthly Allocation</div>
                <div class="h3 mb-0">KES <?= number_format($totalAllocated) ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="text-muted small">Actual Spent</div>
                <div class="h3 mb-0 text-primary">KES <?= number_format($totalSpent) ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="text-muted small">Committed (POs)</div>
                <div class="h3 mb-0 text-warning">KES <?= number_format($totalCommitted) ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="text-muted small">Over Budget</div>
                <div class="h3 mb-0 <?= $overBudgetCount > 0 ? 'text-danger' : 'text-success' ?>">
                    <?= $overBudgetCount ?> budgets
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tracking Table -->
<div class="table-responsive">
    <table class="table table-vcenter card-table" id="trackingTable">
        <thead>
            <tr>
                <th>Budget</th>
                <th>Account</th>
                <th>Cost Center</th>
                <th class="text-end">Allocated</th>
                <th class="text-end">Spent</th>
                <th class="text-end">Committed</th>
                <th class="text-end">Available</th>
                <th style="width: 150px;">Utilization</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tracking as $item):
                $utilization = $item['utilization_pct'] ?? 0;
                $available = $item['available_amount'] ?? 0;

                if ($utilization > 100) {
                    $statusClass = 'over';
                    $barClass = 'bg-danger';
                } elseif ($utilization > 80) {
                    $statusClass = 'warning';
                    $barClass = 'bg-warning';
                } else {
                    $statusClass = 'under';
                    $barClass = 'bg-success';
                }
            ?>
            <tr data-utilization="<?= $statusClass ?>">
                <td>
                    <code><?= e($item['budget_code']) ?></code><br>
                    <strong><?= e($item['name']) ?></strong>
                </td>
                <td>
                    <span class="text-muted"><?= e($item['account_code']) ?></span><br>
                    <?= e($item['account_name']) ?>
                </td>
                <td><?= e($item['cost_center_name'] ?? '-') ?></td>
                <td class="text-end">KES <?= number_format($item['allocated_amount'] ?? 0) ?></td>
                <td class="text-end">KES <?= number_format($item['spent_amount'] ?? 0) ?></td>
                <td class="text-end">KES <?= number_format($item['committed_amount'] ?? 0) ?></td>
                <td class="text-end <?= $available < 0 ? 'text-danger fw-bold' : 'text-success' ?>">
                    KES <?= number_format($available) ?>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 me-2">
                            <div class="progress progress-sm">
                                <div class="progress-bar <?= $barClass ?>"
                                     style="width: <?= min($utilization, 100) ?>%"></div>
                            </div>
                        </div>
                        <span class="<?= $utilization > 100 ? 'text-danger fw-bold' : '' ?>">
                            <?= number_format($utilization, 1) ?>%
                        </span>
                    </div>
                    <?php if ($utilization > 100): ?>
                    <small class="text-danger">
                        <i class="ti ti-alert-triangle"></i> Over by KES <?= number_format(abs($available)) ?>
                    </small>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Chart Placeholder -->
<div class="card mt-4">
    <div class="card-header">
        <h4 class="card-title">Budget Utilization Chart</h4>
    </div>
    <div class="card-body">
        <div id="utilizationChart" style="height: 300px;">
            <div class="d-flex justify-content-center align-items-center h-100 text-muted">
                <div class="text-center">
                    <i class="ti ti-chart-bar" style="font-size: 3rem;"></i>
                    <p class="mt-2">Chart visualization would display here</p>
                    <small>Integrate ApexCharts or Chart.js for interactive charts</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<script>
function filterTracking() {
    const filter = document.getElementById('utilizationFilter').value;
    document.querySelectorAll('#trackingTable tbody tr').forEach(row => {
        const status = row.dataset.utilization;
        row.style.display = !filter || status === filter ? '' : 'none';
    });
}

function loadTrackingData() {
    const month = document.getElementById('trackingMonth').value;
    window.location.href = `/finance/budgets/tracking?month=${month}#budget-tabs`;
}

function exportTracking() {
    const month = document.getElementById('trackingMonth').value;
    window.open(`/finance/budgets/api/tracking/export?month=${month}`, '_blank');
}
</script>
