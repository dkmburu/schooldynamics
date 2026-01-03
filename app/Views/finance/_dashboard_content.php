<?php
/**
 * Finance Dashboard - Content
 */
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <h2 class="page-title">
                    <i class="ti ti-currency-dollar me-2"></i>
                    Finance Dashboard
                </h2>
                <div class="text-muted mt-1">
                    <?= e($current_year['year_name'] ?? 'No Year Set') ?> - <?= e($current_term['term_name'] ?? 'No Term Set') ?>
                </div>
            </div>
            <div class="col-auto ms-auto">
                <div class="btn-list">
                    <a href="/finance/expenses" class="btn" style="background-color: #f76707; color: white; border-color: #f76707;" onmouseover="this.style.backgroundColor='#e8590c'; this.style.borderColor='#e8590c';" onmouseout="this.style.backgroundColor='#f76707'; this.style.borderColor='#f76707';">
                        <i class="ti ti-building-store me-2"></i>Expenses
                    </a>
                    <a href="/finance/invoices/generate" class="btn btn-primary">
                        <i class="ti ti-file-plus me-2"></i>Generate Invoices
                    </a>
                    <a href="/finance/payments/record" class="btn btn-success">
                        <i class="ti ti-cash me-2"></i>Record Payment
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <!-- KPI Cards -->
        <div class="row row-deck row-cards mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Total Invoiced</div>
                            <div class="ms-auto lh-1">
                                <i class="ti ti-file-invoice text-primary" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <div class="h1 mb-3">KES <?= number_format($stats['total_invoiced']) ?></div>
                        <div class="d-flex mb-2">
                            <div class="text-muted">Current term</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Total Collected</div>
                            <div class="ms-auto lh-1">
                                <i class="ti ti-cash text-success" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <div class="h1 mb-3 text-success">KES <?= number_format($stats['total_collected']) ?></div>
                        <div class="d-flex mb-2">
                            <div class="text-muted">Current term</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Outstanding</div>
                            <div class="ms-auto lh-1">
                                <i class="ti ti-alert-circle text-danger" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <div class="h1 mb-3 text-danger">KES <?= number_format($stats['total_outstanding']) ?></div>
                        <div class="d-flex mb-2">
                            <div class="text-muted"><?= $stats['students_with_balance'] ?> students</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Collection Rate</div>
                            <div class="ms-auto lh-1">
                                <i class="ti ti-chart-pie text-info" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <div class="h1 mb-3"><?= number_format($stats['collection_rate'], 1) ?>%</div>
                        <div class="d-flex mb-2">
                            <div class="progress progress-sm w-100">
                                <div class="progress-bar bg-info" style="width: <?= $stats['collection_rate'] ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row row-deck row-cards">
            <!-- Quick Links -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Quick Actions</h3>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="/finance/chart-of-accounts" class="list-group-item list-group-item-action">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <i class="ti ti-list-tree text-primary"></i>
                                </div>
                                <div class="col">Chart of Accounts</div>
                                <div class="col-auto">
                                    <i class="ti ti-chevron-right text-muted"></i>
                                </div>
                            </div>
                        </a>
                        <a href="/finance/fee-categories" class="list-group-item list-group-item-action">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <i class="ti ti-category text-success"></i>
                                </div>
                                <div class="col">Fee Categories</div>
                                <div class="col-auto">
                                    <i class="ti ti-chevron-right text-muted"></i>
                                </div>
                            </div>
                        </a>
                        <a href="/finance/fee-items" class="list-group-item list-group-item-action">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <i class="ti ti-list-details text-info"></i>
                                </div>
                                <div class="col">Fee Items</div>
                                <div class="col-auto">
                                    <i class="ti ti-chevron-right text-muted"></i>
                                </div>
                            </div>
                        </a>
                        <a href="/finance/fee-structures" class="list-group-item list-group-item-action">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <i class="ti ti-file-spreadsheet text-warning"></i>
                                </div>
                                <div class="col">Fee Structures</div>
                                <div class="col-auto">
                                    <i class="ti ti-chevron-right text-muted"></i>
                                </div>
                            </div>
                        </a>
                        <a href="/finance/transport-tariffs" class="list-group-item list-group-item-action">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <i class="ti ti-bus text-purple"></i>
                                </div>
                                <div class="col">Transport Tariffs</div>
                                <div class="col-auto">
                                    <i class="ti ti-chevron-right text-muted"></i>
                                </div>
                            </div>
                        </a>
                        <a href="/finance/expenses" class="list-group-item list-group-item-action">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <i class="ti ti-shopping-cart text-danger"></i>
                                </div>
                                <div class="col">Expenses & Suppliers</div>
                                <div class="col-auto">
                                    <i class="ti ti-chevron-right text-muted"></i>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Payments -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Payments</h3>
                        <div class="card-actions">
                            <a href="/finance/payments" class="btn btn-sm btn-primary">
                                View All
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($stats['recent_payments'])): ?>
                        <div class="empty">
                            <div class="empty-img">
                                <i class="ti ti-receipt-off" style="font-size: 4rem; color: #adb5bd;"></i>
                            </div>
                            <p class="empty-title">No recent payments</p>
                            <p class="empty-subtitle text-muted">
                                Payments will appear here as they are recorded.
                            </p>
                            <div class="empty-action">
                                <a href="/finance/payments/record" class="btn btn-primary">
                                    <i class="ti ti-plus me-2"></i>Record Payment
                                </a>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead>
                                    <tr>
                                        <th>Receipt #</th>
                                        <th>Student</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats['recent_payments'] as $payment): ?>
                                    <tr>
                                        <td><strong><?= e($payment['receipt_number']) ?></strong></td>
                                        <td><?= e($payment['payer_name'] ?? 'N/A') ?></td>
                                        <td class="text-success">KES <?= number_format($payment['amount'], 2) ?></td>
                                        <td><span class="badge"><?= e($payment['method_name'] ?? ucfirst($payment['payment_method'])) ?></span></td>
                                        <td><?= date('M j, Y', strtotime($payment['payment_date'])) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reports Section -->
        <div class="row row-deck row-cards mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Reports</h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <a href="/finance/reports/collection" class="card card-link card-link-pop">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <span class="avatar bg-primary-lt me-3">
                                                <i class="ti ti-report-money"></i>
                                            </span>
                                            <div>
                                                <div class="font-weight-medium">Collection Report</div>
                                                <div class="text-muted small">Daily/weekly/monthly collections</div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="/finance/reports/outstanding" class="card card-link card-link-pop">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <span class="avatar bg-danger-lt me-3">
                                                <i class="ti ti-alert-circle"></i>
                                            </span>
                                            <div>
                                                <div class="font-weight-medium">Outstanding Balances</div>
                                                <div class="text-muted small">Debtors list by amount</div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="/finance/reports/income" class="card card-link card-link-pop">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <span class="avatar bg-success-lt me-3">
                                                <i class="ti ti-file-analytics"></i>
                                            </span>
                                            <div>
                                                <div class="font-weight-medium">Income Statement</div>
                                                <div class="text-muted small">Revenue by category</div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
