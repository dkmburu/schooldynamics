<?php
/**
 * Expenses - Content (tabbed view)
 * Tabs: Suppliers, Purchase Orders, GRN, Invoices, Payments
 */

$activeTab = $activeTab ?? 'suppliers';
$stats = $stats ?? ['total_suppliers' => 0, 'active_suppliers' => 0, 'pending_orders' => 0, 'outstanding_payables' => 0, 'payments_this_month' => 0];
$categories = $categories ?? [];
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
                    <i class="ti ti-shopping-cart me-2"></i>
                    Expense Management
                </h2>
                <div class="text-muted small">Manage suppliers, purchase orders, and payments</div>
            </div>
        </div>
    </div>
</div>

<div class="page-body pt-2">
    <div class="container-xl">
        <!-- Anchor for tab navigation - scrolls to show stats cards at top -->
        <a id="expense-tabs" name="expense-tabs"></a>

        <!-- Summary Stats -->
        <div class="row row-deck row-cards mb-2">
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Total Suppliers</div>
                            <div class="ms-auto">
                                <span class="text-muted"><?= $stats['active_suppliers'] ?> active</span>
                            </div>
                        </div>
                        <div class="h1 mb-0"><?= number_format($stats['total_suppliers']) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Pending Orders</div>
                        </div>
                        <div class="h1 mb-0 text-warning"><?= number_format($stats['pending_orders']) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Outstanding Payables</div>
                        </div>
                        <div class="h1 mb-0 text-danger">KES <?= number_format($stats['outstanding_payables']) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Paid This Month</div>
                        </div>
                        <div class="h1 mb-0 text-success">KES <?= number_format($stats['payments_this_month']) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
                    <li class="nav-item">
                        <a href="/finance/expenses/suppliers#expense-tabs" class="nav-link <?= $activeTab === 'suppliers' ? 'active' : '' ?>">
                            <i class="ti ti-building-store me-2"></i>Suppliers
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/finance/expenses/purchase-orders#expense-tabs" class="nav-link <?= $activeTab === 'purchase-orders' ? 'active' : '' ?>">
                            <i class="ti ti-file-invoice me-2"></i>Purchase Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/finance/expenses/grn#expense-tabs" class="nav-link <?= $activeTab === 'grn' ? 'active' : '' ?>">
                            <i class="ti ti-package me-2"></i>Goods Received
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/finance/expenses/invoices#expense-tabs" class="nav-link <?= $activeTab === 'invoices' ? 'active' : '' ?>">
                            <i class="ti ti-receipt me-2"></i>Supplier Invoices
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/finance/expenses/payments#expense-tabs" class="nav-link <?= $activeTab === 'payments' ? 'active' : '' ?>">
                            <i class="ti ti-cash me-2"></i>Payments
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <?php
                // Load tab-specific content
                switch ($activeTab) {
                    case 'suppliers':
                        include __DIR__ . '/expenses/_suppliers_tab.php';
                        break;
                    case 'purchase-orders':
                        include __DIR__ . '/expenses/_purchase_orders_tab.php';
                        break;
                    case 'grn':
                        include __DIR__ . '/expenses/_grn_tab.php';
                        break;
                    case 'invoices':
                        include __DIR__ . '/expenses/_invoices_tab.php';
                        break;
                    case 'payments':
                        include __DIR__ . '/expenses/_payments_tab.php';
                        break;
                }
                ?>
            </div>
        </div>
    </div>
</div>
