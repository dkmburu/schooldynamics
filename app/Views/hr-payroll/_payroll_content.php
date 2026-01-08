<?php
/**
 * Payroll Processing Content
 */

$payPeriods = $payPeriods ?? [];
$currentPeriod = $currentPeriod ?? null;
$payrollRuns = $payrollRuns ?? [];
$currency = $_SESSION['currency'] ?? 'KES';
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <div class="page-pretitle">HR & Payroll</div>
                <h2 class="page-title">
                    <i class="ti ti-calculator me-2"></i>Payroll Processing
                </h2>
            </div>
            <div class="col-auto ms-auto">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newPayrollModal">
                    <i class="ti ti-plus me-1"></i>New Payroll Run
                </button>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <!-- Current Period Info -->
        <?php if ($currentPeriod): ?>
        <div class="alert alert-info mb-3">
            <div class="d-flex">
                <div>
                    <i class="ti ti-calendar alert-icon"></i>
                </div>
                <div>
                    <h4 class="alert-title">Current Pay Period: <?= e($currentPeriod['period_name']) ?></h4>
                    <div class="text-muted">
                        <?= date('M j, Y', strtotime($currentPeriod['start_date'])) ?> - <?= date('M j, Y', strtotime($currentPeriod['end_date'])) ?>
                        | Payment Date: <?= date('M j, Y', strtotime($currentPeriod['payment_date'])) ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Payroll Runs -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Payroll Runs</h3>
                    </div>
                    <?php if (empty($payrollRuns)): ?>
                    <div class="card-body">
                        <div class="empty">
                            <div class="empty-icon">
                                <i class="ti ti-calculator" style="font-size: 3rem;"></i>
                            </div>
                            <p class="empty-title">No payroll runs yet</p>
                            <p class="empty-subtitle text-muted">
                                Create a new payroll run to process staff salaries
                            </p>
                            <div class="empty-action">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newPayrollModal">
                                    <i class="ti ti-plus me-1"></i>New Payroll Run
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Run #</th>
                                    <th>Type</th>
                                    <th>Employees</th>
                                    <th class="text-end">Gross</th>
                                    <th class="text-end">Net</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payrollRuns as $run): ?>
                                <tr>
                                    <td><strong>#<?= $run['run_number'] ?></strong></td>
                                    <td>
                                        <span class="badge bg-secondary"><?= ucfirst($run['run_type']) ?></span>
                                    </td>
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
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="/hr-payroll/payroll/<?= $run['id'] ?>" class="btn btn-outline-primary" title="View">
                                                <i class="ti ti-eye"></i>
                                            </a>
                                            <?php if ($run['status'] == 'draft'): ?>
                                            <button type="button" class="btn btn-outline-success" title="Calculate">
                                                <i class="ti ti-calculator"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Stats & Actions -->
            <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title">Pay Periods</h3>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php foreach (array_slice($payPeriods, 0, 5) as $period): ?>
                        <a href="/hr-payroll/payroll?period=<?= $period['id'] ?>"
                           class="list-group-item list-group-item-action <?= ($currentPeriod['id'] ?? 0) == $period['id'] ? 'active' : '' ?>">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong><?= e($period['period_name']) ?></strong>
                                    <div class="text-muted small">
                                        <?= date('M j', strtotime($period['start_date'])) ?> - <?= date('M j, Y', strtotime($period['end_date'])) ?>
                                    </div>
                                </div>
                                <span class="badge bg-<?= $period['status'] == 'closed' ? 'secondary' : 'primary' ?>">
                                    <?= ucfirst($period['status']) ?>
                                </span>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="card-footer">
                        <button type="button" class="btn btn-sm btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#newPeriodModal">
                            <i class="ti ti-plus me-1"></i>Create Pay Period
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Payroll Run Modal -->
<div class="modal modal-blur fade" id="newPayrollModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Payroll Run</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="/hr-payroll/payroll">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Pay Period</label>
                        <select name="pay_period_id" class="form-select" required>
                            <option value="">Select Period</option>
                            <?php foreach ($payPeriods as $period): ?>
                            <?php if ($period['status'] != 'closed'): ?>
                            <option value="<?= $period['id'] ?>"><?= e($period['period_name']) ?></option>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Run Type</label>
                        <select name="run_type" class="form-select">
                            <option value="regular">Regular</option>
                            <option value="supplementary">Supplementary</option>
                            <option value="bonus">Bonus</option>
                            <option value="final">Final Settlement</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Run</button>
                </div>
            </form>
        </div>
    </div>
</div>
