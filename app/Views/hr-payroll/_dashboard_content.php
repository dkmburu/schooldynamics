<?php
/**
 * HR & Payroll Dashboard Content
 */

$stats = $stats ?? [];
$recentPayrolls = $recentPayrolls ?? [];
$pendingApprovals = $pendingApprovals ?? [];
$upcomingPayroll = $upcomingPayroll ?? null;

// Get currency from school profile
$currency = $_SESSION['currency'] ?? 'KES';
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="page-pretitle">HR & Payroll</div>
        <h2 class="page-title">
            <i class="ti ti-wallet me-2"></i>Dashboard
        </h2>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <!-- Stats Cards -->
        <div class="row row-deck row-cards mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Total Staff</div>
                        </div>
                        <div class="h1 mb-0">
                            <?= number_format($stats['total_staff'] ?? 0) ?>
                        </div>
                        <div class="text-muted small">Active employees</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Monthly Payroll</div>
                        </div>
                        <div class="h1 mb-0">
                            <?= $currency ?> <?= number_format($stats['monthly_payroll'] ?? 0) ?>
                        </div>
                        <div class="text-muted small">This month</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Pending Approvals</div>
                        </div>
                        <div class="h1 mb-0 <?= (($stats['pending_payroll_approvals'] ?? 0) + ($stats['pending_loan_approvals'] ?? 0)) > 0 ? 'text-warning' : '' ?>">
                            <?= number_format(($stats['pending_payroll_approvals'] ?? 0) + ($stats['pending_loan_approvals'] ?? 0)) ?>
                        </div>
                        <div class="text-muted small">Payroll & Loans</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Active Loans</div>
                        </div>
                        <div class="h1 mb-0">
                            <?= number_format($stats['active_loans'] ?? 0) ?>
                        </div>
                        <div class="text-muted small">
                            <?= $currency ?> <?= number_format($stats['loan_balance'] ?? 0) ?> outstanding
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row row-deck row-cards">
            <!-- Quick Actions -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <a href="/hr-payroll/payroll" class="list-group-item list-group-item-action d-flex align-items-center">
                                <span class="avatar bg-blue-lt me-3">
                                    <i class="ti ti-calculator"></i>
                                </span>
                                <div>
                                    <strong>Process Payroll</strong>
                                    <div class="text-muted small">Start a new payroll run</div>
                                </div>
                            </a>
                            <a href="/hr-payroll/staff/create" class="list-group-item list-group-item-action d-flex align-items-center">
                                <span class="avatar bg-green-lt me-3">
                                    <i class="ti ti-user-plus"></i>
                                </span>
                                <div>
                                    <strong>Add New Staff</strong>
                                    <div class="text-muted small">Onboard a new employee</div>
                                </div>
                            </a>
                            <a href="/hr-payroll/loans" class="list-group-item list-group-item-action d-flex align-items-center">
                                <span class="avatar bg-yellow-lt me-3">
                                    <i class="ti ti-cash"></i>
                                </span>
                                <div>
                                    <strong>Manage Loans</strong>
                                    <div class="text-muted small">Review loan applications</div>
                                </div>
                            </a>
                            <a href="/hr-payroll/reports" class="list-group-item list-group-item-action d-flex align-items-center">
                                <span class="avatar bg-purple-lt me-3">
                                    <i class="ti ti-report"></i>
                                </span>
                                <div>
                                    <strong>Generate Reports</strong>
                                    <div class="text-muted small">Payroll & statutory reports</div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Payroll Runs -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Payroll Runs</h3>
                        <div class="card-actions">
                            <a href="/hr-payroll/payroll" class="btn btn-primary btn-sm">
                                View All
                            </a>
                        </div>
                    </div>
                    <?php if (empty($recentPayrolls)): ?>
                    <div class="card-body">
                        <div class="empty">
                            <div class="empty-icon">
                                <i class="ti ti-calculator" style="font-size: 3rem;"></i>
                            </div>
                            <p class="empty-title">No payroll runs yet</p>
                            <p class="empty-subtitle text-muted">
                                Start by processing your first payroll
                            </p>
                            <div class="empty-action">
                                <a href="/hr-payroll/payroll" class="btn btn-primary">
                                    <i class="ti ti-plus me-1"></i>Process Payroll
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Period</th>
                                    <th>Employees</th>
                                    <th class="text-end">Gross</th>
                                    <th class="text-end">Net</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentPayrolls as $run): ?>
                                <tr>
                                    <td><?= e($run['period_name']) ?></td>
                                    <td><?= number_format($run['total_employees']) ?></td>
                                    <td class="text-end"><?= $currency ?> <?= number_format($run['total_gross']) ?></td>
                                    <td class="text-end"><?= $currency ?> <?= number_format($run['total_net']) ?></td>
                                    <td>
                                        <?php
                                        $statusColors = [
                                            'draft' => 'secondary',
                                            'calculated' => 'info',
                                            'pending_approval' => 'warning',
                                            'approved' => 'success',
                                            'paid' => 'primary'
                                        ];
                                        $color = $statusColors[$run['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $color ?>"><?= ucfirst(str_replace('_', ' ', $run['status'])) ?></span>
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

        <!-- Upcoming Payroll Alert -->
        <?php if ($upcomingPayroll): ?>
        <div class="row mt-4">
            <div class="col-12">
                <div class="alert alert-info" role="alert">
                    <div class="d-flex">
                        <div>
                            <i class="ti ti-calendar-event alert-icon"></i>
                        </div>
                        <div>
                            <h4 class="alert-title">Upcoming Payroll</h4>
                            <div class="text-muted">
                                Next payroll period: <strong><?= e($upcomingPayroll['period_name']) ?></strong>
                                (<?= date('M j, Y', strtotime($upcomingPayroll['start_date'])) ?> - <?= date('M j, Y', strtotime($upcomingPayroll['end_date'])) ?>)
                                - Payment due: <?= date('M j, Y', strtotime($upcomingPayroll['payment_date'])) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
