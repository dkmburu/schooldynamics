<?php
/**
 * Payslips Content
 */

$payPeriods = $payPeriods ?? [];
$payslips = $payslips ?? [];
$selectedPeriod = $selectedPeriod ?? null;
$currency = $_SESSION['currency'] ?? 'KES';
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <div class="page-pretitle">HR & Payroll</div>
                <h2 class="page-title">
                    <i class="ti ti-file-invoice me-2"></i>Payslips
                </h2>
            </div>
            <div class="col-auto ms-auto d-flex gap-2">
                <form method="GET" class="d-flex gap-2">
                    <select name="period" class="form-select" onchange="this.form.submit()">
                        <option value="">All Periods</option>
                        <?php foreach ($payPeriods as $period): ?>
                        <option value="<?= $period['id'] ?>" <?= $selectedPeriod == $period['id'] ? 'selected' : '' ?>>
                            <?= e($period['period_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <?php if (!empty($payslips)): ?>
                <button type="button" class="btn btn-primary">
                    <i class="ti ti-download me-1"></i>Export All
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Payslip List</h3>
                <div class="card-actions">
                    <span class="badge bg-blue"><?= count($payslips) ?> payslips</span>
                </div>
            </div>
            <?php if (empty($payslips)): ?>
            <div class="card-body">
                <div class="empty">
                    <div class="empty-icon">
                        <i class="ti ti-file-invoice" style="font-size: 3rem;"></i>
                    </div>
                    <p class="empty-title">No payslips found</p>
                    <p class="empty-subtitle text-muted">
                        Payslips will appear here after processing payroll
                    </p>
                    <div class="empty-action">
                        <a href="/hr-payroll/payroll" class="btn btn-primary">
                            <i class="ti ti-calculator me-1"></i>Process Payroll
                        </a>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-striped">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Period</th>
                            <th class="text-end">Gross</th>
                            <th class="text-end">Deductions</th>
                            <th class="text-end">Net</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payslips as $ps): ?>
                        <tr>
                            <td>
                                <div>
                                    <strong><?= e($ps['first_name'] . ' ' . $ps['last_name']) ?></strong>
                                    <div class="text-muted small"><?= e($ps['employee_number'] ?? 'N/A') ?></div>
                                </div>
                            </td>
                            <td><?= e($ps['department_name'] ?? 'N/A') ?></td>
                            <td><?= e($ps['period_name'] ?? 'N/A') ?></td>
                            <td class="text-end"><?= $currency ?> <?= number_format($ps['gross_earnings'] ?? 0) ?></td>
                            <td class="text-end text-danger"><?= $currency ?> <?= number_format($ps['total_deductions'] ?? 0) ?></td>
                            <td class="text-end"><strong><?= $currency ?> <?= number_format($ps['net_salary'] ?? 0) ?></strong></td>
                            <td>
                                <?php
                                $statusColors = [
                                    'pending' => 'warning',
                                    'paid' => 'success',
                                    'failed' => 'danger'
                                ];
                                $color = $statusColors[$ps['payment_status'] ?? 'pending'] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $color ?>"><?= ucfirst($ps['payment_status'] ?? 'pending') ?></span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="/hr-payroll/payslips/<?= $ps['id'] ?>" class="btn btn-outline-primary" title="View">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                    <a href="/hr-payroll/payslips/<?= $ps['id'] ?>/pdf" class="btn btn-outline-secondary" title="Download PDF">
                                        <i class="ti ti-download"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-info" title="Send Email">
                                        <i class="ti ti-mail"></i>
                                    </button>
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
</div>
