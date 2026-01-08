<?php
/**
 * Payroll Reports Content
 */

$payPeriods = $payPeriods ?? [];
$currency = $_SESSION['currency'] ?? 'KES';
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <div class="page-pretitle">HR & Payroll</div>
                <h2 class="page-title">
                    <i class="ti ti-report-analytics me-2"></i>Payroll Reports
                </h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <!-- Report Categories -->
        <div class="row">
            <!-- Payroll Summary Reports -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-file-description me-2"></i>Payroll Summary Reports
                        </h3>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="/hr-payroll/reports/payroll-summary" class="list-group-item list-group-item-action">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <span class="avatar bg-primary-lt"><i class="ti ti-file-spreadsheet"></i></span>
                                </div>
                                <div>
                                    <strong>Payroll Summary</strong>
                                    <div class="text-muted small">Monthly payroll totals by department</div>
                                </div>
                            </div>
                        </a>
                        <a href="/hr-payroll/reports/payroll-register" class="list-group-item list-group-item-action">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <span class="avatar bg-blue-lt"><i class="ti ti-list-details"></i></span>
                                </div>
                                <div>
                                    <strong>Payroll Register</strong>
                                    <div class="text-muted small">Detailed payroll listing for all staff</div>
                                </div>
                            </div>
                        </a>
                        <a href="/hr-payroll/reports/bank-schedule" class="list-group-item list-group-item-action">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <span class="avatar bg-cyan-lt"><i class="ti ti-building-bank"></i></span>
                                </div>
                                <div>
                                    <strong>Bank Schedule</strong>
                                    <div class="text-muted small">Salary transfer schedule by bank</div>
                                </div>
                            </div>
                        </a>
                        <a href="/hr-payroll/reports/variance" class="list-group-item list-group-item-action">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <span class="avatar bg-yellow-lt"><i class="ti ti-chart-arrows"></i></span>
                                </div>
                                <div>
                                    <strong>Variance Report</strong>
                                    <div class="text-muted small">Compare payroll between periods</div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Statutory Reports -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-building-bank me-2"></i>Statutory Reports
                        </h3>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="/hr-payroll/reports/paye" class="list-group-item list-group-item-action">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <span class="avatar bg-red-lt"><i class="ti ti-receipt-tax"></i></span>
                                </div>
                                <div>
                                    <strong>PAYE Report</strong>
                                    <div class="text-muted small">Pay As You Earn tax deductions</div>
                                </div>
                            </div>
                        </a>
                        <a href="/hr-payroll/reports/nssf" class="list-group-item list-group-item-action">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <span class="avatar bg-green-lt"><i class="ti ti-shield-check"></i></span>
                                </div>
                                <div>
                                    <strong>NSSF Report</strong>
                                    <div class="text-muted small">National Social Security Fund contributions</div>
                                </div>
                            </div>
                        </a>
                        <a href="/hr-payroll/reports/nhif" class="list-group-item list-group-item-action">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <span class="avatar bg-purple-lt"><i class="ti ti-heart-plus"></i></span>
                                </div>
                                <div>
                                    <strong>NHIF Report</strong>
                                    <div class="text-muted small">National Hospital Insurance Fund contributions</div>
                                </div>
                            </div>
                        </a>
                        <a href="/hr-payroll/reports/housing-levy" class="list-group-item list-group-item-action">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <span class="avatar bg-orange-lt"><i class="ti ti-home"></i></span>
                                </div>
                                <div>
                                    <strong>Housing Levy Report</strong>
                                    <div class="text-muted small">Affordable Housing Levy contributions</div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <!-- Staff Reports -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-users me-2"></i>Staff Reports
                        </h3>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="/hr-payroll/reports/staff-earnings" class="list-group-item list-group-item-action">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <span class="avatar bg-teal-lt"><i class="ti ti-coin"></i></span>
                                </div>
                                <div>
                                    <strong>Staff Earnings</strong>
                                    <div class="text-muted small">Individual earnings breakdown</div>
                                </div>
                            </div>
                        </a>
                        <a href="/hr-payroll/reports/department-summary" class="list-group-item list-group-item-action">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <span class="avatar bg-indigo-lt"><i class="ti ti-building"></i></span>
                                </div>
                                <div>
                                    <strong>Department Summary</strong>
                                    <div class="text-muted small">Payroll costs by department</div>
                                </div>
                            </div>
                        </a>
                        <a href="/hr-payroll/reports/loans-outstanding" class="list-group-item list-group-item-action">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <span class="avatar bg-pink-lt"><i class="ti ti-cash"></i></span>
                                </div>
                                <div>
                                    <strong>Outstanding Loans</strong>
                                    <div class="text-muted small">Staff loan balances and recovery schedule</div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Export & Year End -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-calendar-stats me-2"></i>Year-End & Export
                        </h3>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="/hr-payroll/reports/p9" class="list-group-item list-group-item-action">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <span class="avatar bg-lime-lt"><i class="ti ti-file-certificate"></i></span>
                                </div>
                                <div>
                                    <strong>P9 Forms</strong>
                                    <div class="text-muted small">Annual tax deduction certificates</div>
                                </div>
                            </div>
                        </a>
                        <a href="/hr-payroll/reports/annual-summary" class="list-group-item list-group-item-action">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <span class="avatar bg-azure-lt"><i class="ti ti-calendar-stats"></i></span>
                                </div>
                                <div>
                                    <strong>Annual Summary</strong>
                                    <div class="text-muted small">Yearly payroll totals and analysis</div>
                                </div>
                            </div>
                        </a>
                        <a href="/hr-payroll/reports/export" class="list-group-item list-group-item-action">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <span class="avatar bg-gray-lt"><i class="ti ti-download"></i></span>
                                </div>
                                <div>
                                    <strong>Export Data</strong>
                                    <div class="text-muted small">Export payroll data (Excel, CSV, PDF)</div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Report Generator -->
        <div class="card mt-4">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="ti ti-wand me-2"></i>Quick Report Generator
                </h3>
            </div>
            <div class="card-body">
                <form method="GET" action="/hr-payroll/reports/generate">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Report Type</label>
                            <select name="report_type" class="form-select" required>
                                <option value="">Select Report</option>
                                <optgroup label="Payroll">
                                    <option value="payroll_summary">Payroll Summary</option>
                                    <option value="payroll_register">Payroll Register</option>
                                    <option value="bank_schedule">Bank Schedule</option>
                                </optgroup>
                                <optgroup label="Statutory">
                                    <option value="paye">PAYE Report</option>
                                    <option value="nssf">NSSF Report</option>
                                    <option value="nhif">NHIF Report</option>
                                    <option value="housing_levy">Housing Levy</option>
                                </optgroup>
                                <optgroup label="Staff">
                                    <option value="staff_earnings">Staff Earnings</option>
                                    <option value="department_summary">Department Summary</option>
                                </optgroup>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Pay Period</label>
                            <select name="pay_period_id" class="form-select">
                                <option value="">All Periods</option>
                                <?php foreach ($payPeriods as $period): ?>
                                <option value="<?= $period['id'] ?>">
                                    <?= e($period['period_name'] ?? date('M Y', strtotime($period['start_date']))) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">From Date</label>
                            <input type="date" name="from_date" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">To Date</label>
                            <input type="date" name="to_date" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Format</label>
                            <select name="format" class="form-select">
                                <option value="html">View Online</option>
                                <option value="pdf">PDF</option>
                                <option value="excel">Excel</option>
                                <option value="csv">CSV</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-file-analytics me-1"></i>Generate Report
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
