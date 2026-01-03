<?php
/**
 * Applicant Profile - Finances Tab
 * Shows fee account, invoices, payments, and statement of account
 */

// Get fee account and financial data
$feeAccount = null;
$invoices = [];
$payments = [];
$statementLines = [];

// Get PDO connection for this tab
$pdo = Database::getTenantConnection();

try {
    // Check if applicant has a fee account
    if (!empty($applicant['student_fee_account_id'])) {
        $stmt = $pdo->prepare("
            SELECT sfa.*
            FROM student_fee_accounts sfa
            WHERE sfa.id = ?
        ");
        $stmt->execute([$applicant['student_fee_account_id']]);
        $feeAccount = $stmt->fetch();

        // Get all invoices for this account
        $stmt = $pdo->prepare("
            SELECT i.*,
                   GROUP_CONCAT(DISTINCT fi.name SEPARATOR ', ') as fee_items
            FROM invoices i
            LEFT JOIN invoice_lines il ON il.invoice_id = i.id
            LEFT JOIN fee_items fi ON fi.id = il.fee_item_id
            WHERE i.student_fee_account_id = ?
            GROUP BY i.id
            ORDER BY i.invoice_date DESC
        ");
        $stmt->execute([$applicant['student_fee_account_id']]);
        $invoices = $stmt->fetchAll();

        // Get all payments for this account
        $stmt = $pdo->prepare("
            SELECT p.*, u.full_name as received_by_name
            FROM payments p
            LEFT JOIN users u ON u.id = p.received_by
            WHERE p.student_fee_account_id = ?
            ORDER BY p.payment_date DESC, p.id DESC
        ");
        $stmt->execute([$applicant['student_fee_account_id']]);
        $payments = $stmt->fetchAll();

        // Build statement of account (chronological ledger)
        // Combine invoices and payments, sorted by date
        $allTransactions = [];

        foreach ($invoices as $inv) {
            $allTransactions[] = [
                'date' => $inv['invoice_date'],
                'type' => 'invoice',
                'reference' => $inv['invoice_number'],
                'description' => 'Invoice - ' . ucfirst($inv['invoice_type']) . ' Fees',
                'debit' => $inv['total_amount'],
                'credit' => 0,
                'status' => $inv['status'],
                'id' => $inv['id']
            ];
        }

        foreach ($payments as $pmt) {
            $allTransactions[] = [
                'date' => $pmt['payment_date'],
                'type' => 'payment',
                'reference' => $pmt['receipt_number'],
                'description' => 'Payment - ' . ucfirst(str_replace('_', ' ', $pmt['payment_method'])) .
                                ($pmt['reference_number'] ? ' (' . $pmt['reference_number'] . ')' : ''),
                'debit' => 0,
                'credit' => $pmt['amount'],
                'status' => $pmt['status'],
                'id' => $pmt['id']
            ];
        }

        // Sort by date
        usort($allTransactions, function($a, $b) {
            return strtotime($a['date']) - strtotime($b['date']);
        });

        // Calculate running balance
        $runningBalance = 0;
        foreach ($allTransactions as &$txn) {
            $runningBalance += $txn['debit'] - $txn['credit'];
            $txn['balance'] = $runningBalance;
        }
        unset($txn);

        $statementLines = $allTransactions;
    }
} catch (Exception $e) {
    logMessage("Finances tab error: " . $e->getMessage(), 'error');
}

// Get admission fee structure for preview (if not yet in pre-admission)
$feeStructurePreview = null;
if (in_array($applicant['status'], ['accepted', 'waitlisted']) && !$feeAccount) {
    try {
        $stmt = $pdo->prepare("
            SELECT afs.*, glg.group_name
            FROM admission_fee_structures afs
            JOIN grade_level_groups glg ON glg.id = afs.grade_level_group_id
            JOIN grade_level_group_members glgm ON glgm.grade_level_group_id = glg.id
            WHERE glgm.grade_id = ?
            AND afs.academic_year_id = ?
            AND afs.status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$applicant['grade_applying_for_id'], $applicant['academic_year_id']]);
        $feeStructurePreview = $stmt->fetch();

        if ($feeStructurePreview) {
            // Get fee items
            $stmt = $pdo->prepare("
                SELECT afsl.*, fi.name as fee_item_name, fi.code as fee_item_code
                FROM admission_fee_structure_lines afsl
                JOIN fee_items fi ON fi.id = afsl.fee_item_id
                WHERE afsl.admission_fee_structure_id = ?
                ORDER BY afsl.sort_order
            ");
            $stmt->execute([$feeStructurePreview['id']]);
            $feeStructurePreview['lines'] = $stmt->fetchAll();
        }
    } catch (Exception $e) {}
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0" style="font-size: 14px; font-weight: 600; color: #605e5c;">
        <i class="ti ti-wallet me-2"></i>Financial Information
    </h5>
    <div>
        <?php if ($feeAccount && $feeAccount['current_balance'] > 0 && (hasPermission('Finance.write') || Gate::hasRole('ADMIN'))): ?>
        <button class="btn btn-success btn-sm" onclick="showPaymentModal(<?= $applicant['id'] ?>)">
            <i class="ti ti-cash me-1"></i> Record Payment
        </button>
        <?php elseif ($applicant['status'] === 'accepted' && !$feeAccount && (hasPermission('Students.write') || Gate::hasRole('ADMIN'))): ?>
        <button class="btn btn-primary btn-sm" onclick="showPreAdmissionModal(<?= $applicant['id'] ?>)">
            <i class="ti ti-file-invoice me-1"></i> Create Fee Account & Invoice
        </button>
        <?php endif; ?>
    </div>
</div>

<?php if ($feeAccount): ?>
<!-- Fee Account Summary -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary-lt">
            <div class="card-body text-center py-3">
                <div class="text-muted small">Account Number</div>
                <div class="h4 mb-0"><?= e($feeAccount['account_number']) ?></div>
                <span class="badge bg-<?= $feeAccount['account_status'] === 'active' ? 'success' : 'warning' ?>">
                    <?= ucfirst($feeAccount['account_status']) ?>
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center py-3">
                <div class="text-muted small">Total Invoiced</div>
                <div class="h4 mb-0">KES <?= number_format(array_sum(array_column($invoices, 'total_amount')), 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center py-3">
                <div class="text-muted small">Total Paid</div>
                <div class="h4 mb-0 text-success">KES <?= number_format(array_sum(array_column($payments, 'amount')), 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card <?= $feeAccount['current_balance'] > 0 ? 'bg-danger-lt' : 'bg-success-lt' ?>">
            <div class="card-body text-center py-3">
                <div class="text-muted small">Current Balance</div>
                <div class="h4 mb-0 <?= $feeAccount['current_balance'] > 0 ? 'text-danger' : 'text-success' ?>">
                    KES <?= number_format(abs($feeAccount['current_balance']), 0) ?>
                    <?= $feeAccount['current_balance'] < 0 ? '(Credit)' : '' ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statement of Account -->
<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">
            <i class="ti ti-list-details me-2"></i>Statement of Account
        </h3>
        <div class="card-actions">
            <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                <i class="ti ti-printer me-1"></i> Print
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (empty($statementLines)): ?>
        <div class="empty py-4">
            <div class="empty-icon">
                <i class="ti ti-receipt-off"></i>
            </div>
            <p class="empty-title">No transactions yet</p>
            <p class="empty-subtitle text-muted">
                Financial transactions will appear here once invoices are generated.
            </p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Reference</th>
                        <th>Description</th>
                        <th class="text-end">Debit (KES)</th>
                        <th class="text-end">Credit (KES)</th>
                        <th class="text-end">Balance (KES)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="bg-light">
                        <td colspan="5" class="text-muted"><strong>Opening Balance</strong></td>
                        <td class="text-end"><strong>0.00</strong></td>
                    </tr>
                    <?php foreach ($statementLines as $line): ?>
                    <tr>
                        <td><?= date('M j, Y', strtotime($line['date'])) ?></td>
                        <td>
                            <?php if ($line['type'] === 'invoice'): ?>
                            <a href="/finance/invoices/<?= $line['id'] ?>" class="text-primary">
                                <?= e($line['reference']) ?>
                            </a>
                            <?php else: ?>
                            <span class="text-success"><?= e($line['reference']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($line['type'] === 'invoice'): ?>
                            <i class="ti ti-file-invoice text-primary me-1"></i>
                            <?php else: ?>
                            <i class="ti ti-cash text-success me-1"></i>
                            <?php endif; ?>
                            <?= e($line['description']) ?>
                            <?php if ($line['status'] === 'paid'): ?>
                            <span class="badge bg-success-lt ms-1">Paid</span>
                            <?php elseif ($line['status'] === 'partial'): ?>
                            <span class="badge bg-warning-lt ms-1">Partial</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?= $line['debit'] > 0 ? number_format($line['debit'], 2) : '-' ?>
                        </td>
                        <td class="text-end text-success">
                            <?= $line['credit'] > 0 ? number_format($line['credit'], 2) : '-' ?>
                        </td>
                        <td class="text-end <?= $line['balance'] > 0 ? 'text-danger' : 'text-success' ?>">
                            <strong><?= number_format(abs($line['balance']), 2) ?></strong>
                            <?= $line['balance'] < 0 ? ' CR' : '' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-light">
                    <tr>
                        <td colspan="3" class="text-end"><strong>Closing Balance</strong></td>
                        <td class="text-end">
                            <strong><?= number_format(array_sum(array_column($statementLines, 'debit')), 2) ?></strong>
                        </td>
                        <td class="text-end text-success">
                            <strong><?= number_format(array_sum(array_column($statementLines, 'credit')), 2) ?></strong>
                        </td>
                        <td class="text-end <?= $feeAccount['current_balance'] > 0 ? 'text-danger' : 'text-success' ?>">
                            <strong><?= number_format(abs($feeAccount['current_balance']), 2) ?></strong>
                            <?= $feeAccount['current_balance'] < 0 ? ' CR' : '' ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Invoices List -->
<?php if (!empty($invoices)): ?>
<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title"><i class="ti ti-file-invoice me-2"></i>Invoices</h3>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Due Date</th>
                    <th class="text-end">Amount</th>
                    <th class="text-end">Paid</th>
                    <th class="text-end">Balance</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $inv): ?>
                <tr>
                    <td>
                        <a href="/finance/invoices/<?= $inv['id'] ?>" class="text-primary fw-bold">
                            <?= e($inv['invoice_number']) ?>
                        </a>
                    </td>
                    <td><?= date('M j, Y', strtotime($inv['invoice_date'])) ?></td>
                    <td><span class="badge bg-primary-lt"><?= ucfirst($inv['invoice_type']) ?></span></td>
                    <td>
                        <?= date('M j, Y', strtotime($inv['due_date'])) ?>
                        <?php if (strtotime($inv['due_date']) < time() && $inv['balance'] > 0): ?>
                        <span class="badge bg-danger ms-1">Overdue</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end"><?= number_format($inv['total_amount'], 2) ?></td>
                    <td class="text-end text-success"><?= number_format($inv['amount_paid'], 2) ?></td>
                    <td class="text-end <?= $inv['balance'] > 0 ? 'text-danger' : '' ?>">
                        <?= number_format($inv['balance'], 2) ?>
                    </td>
                    <td>
                        <?php
                        $statusColors = ['paid' => 'success', 'partial' => 'warning', 'pending' => 'info', 'cancelled' => 'secondary'];
                        ?>
                        <span class="badge bg-<?= $statusColors[$inv['status']] ?? 'secondary' ?>-lt">
                            <?= ucfirst($inv['status']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Payments List -->
<?php if (!empty($payments)): ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="ti ti-cash me-2"></i>Payments</h3>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Receipt #</th>
                    <th>Date</th>
                    <th>Method</th>
                    <th>Reference</th>
                    <th class="text-end">Amount</th>
                    <th>Received By</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $pmt): ?>
                <tr>
                    <td class="fw-bold text-success"><?= e($pmt['receipt_number']) ?></td>
                    <td><?= date('M j, Y', strtotime($pmt['payment_date'])) ?></td>
                    <td>
                        <?php
                        $methodIcons = [
                            'cash' => 'ti-cash',
                            'mpesa' => 'ti-device-mobile',
                            'bank_transfer' => 'ti-building-bank',
                            'cheque' => 'ti-file-check',
                            'card' => 'ti-credit-card'
                        ];
                        ?>
                        <i class="ti <?= $methodIcons[$pmt['payment_method']] ?? 'ti-cash' ?> me-1"></i>
                        <?= ucfirst(str_replace('_', ' ', $pmt['payment_method'])) ?>
                    </td>
                    <td><?= e($pmt['reference_number'] ?? '-') ?></td>
                    <td class="text-end text-success fw-bold">KES <?= number_format($pmt['amount'], 2) ?></td>
                    <td><?= e($pmt['received_by_name'] ?? '-') ?></td>
                    <td>
                        <span class="badge bg-<?= $pmt['status'] === 'confirmed' ? 'success' : 'warning' ?>-lt">
                            <?= ucfirst($pmt['status']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php elseif ($applicant['status'] === 'accepted' && $feeStructurePreview): ?>
<!-- No fee account yet - Show fee structure preview and option to create -->
<div class="alert alert-success mb-4">
    <div class="d-flex align-items-center">
        <i class="ti ti-check me-3" style="font-size: 2rem;"></i>
        <div class="flex-grow-1">
            <h4 class="alert-title">Application Accepted!</h4>
            <div class="text-muted">
                Ready to proceed with admission? Click the button to create a fee account and generate the admission invoice.
            </div>
        </div>
        <?php if (hasPermission('Students.write') || Gate::hasRole('ADMIN')): ?>
        <button class="btn btn-success ms-3" onclick="showPreAdmissionModal(<?= $applicant['id'] ?>)">
            <i class="ti ti-file-invoice me-1"></i> Create Fee Account & Invoice
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- Fee Structure Preview -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="ti ti-receipt me-2"></i>Applicable Admission Fees
        </h3>
        <div class="card-actions">
            <span class="badge bg-info"><?= e($feeStructurePreview['group_name']) ?></span>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Fee Item</th>
                    <th>Code</th>
                    <th class="text-center">Mandatory</th>
                    <th class="text-center">Refundable</th>
                    <th class="text-end">Amount (KES)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($feeStructurePreview['lines'] as $line): ?>
                <tr>
                    <td><?= e($line['fee_item_name']) ?></td>
                    <td><code><?= e($line['fee_item_code']) ?></code></td>
                    <td class="text-center">
                        <?php if ($line['is_mandatory']): ?>
                        <i class="ti ti-check text-success"></i>
                        <?php else: ?>
                        <i class="ti ti-minus text-muted"></i>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if ($line['is_refundable']): ?>
                        <span class="badge bg-info-lt">Refundable</span>
                        <?php else: ?>
                        <i class="ti ti-minus text-muted"></i>
                        <?php endif; ?>
                    </td>
                    <td class="text-end"><?= number_format($line['amount'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="bg-light">
                <tr>
                    <td colspan="4" class="text-end"><strong>Total Admission Fees</strong></td>
                    <td class="text-end">
                        <strong class="text-primary fs-5">KES <?= number_format($feeStructurePreview['total_amount'], 2) ?></strong>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php else: ?>
<!-- No financial information available -->
<div class="empty py-5">
    <div class="empty-img">
        <i class="ti ti-wallet-off" style="font-size: 4rem; color: #adb5bd;"></i>
    </div>
    <p class="empty-title">No Financial Information</p>
    <p class="empty-subtitle text-muted">
        <?php if (in_array($applicant['status'], ['draft', 'submitted', 'screening', 'interview_scheduled', 'interviewed', 'exam_scheduled', 'exam_taken'])): ?>
        Financial information will be available once the application is accepted and proceeds to pre-admission.
        <?php elseif ($applicant['status'] === 'rejected'): ?>
        This application was rejected. No financial account was created.
        <?php elseif ($applicant['status'] === 'waitlisted'): ?>
        This application is waitlisted. Financial account will be created if accepted.
        <?php else: ?>
        No financial records found for this applicant.
        <?php endif; ?>
    </p>
</div>
<?php endif; ?>
