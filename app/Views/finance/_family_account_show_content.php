<?php
/**
 * Family Account Detail - Content
 */
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <a href="/finance/family-accounts" class="btn btn-ghost-secondary btn-sm mb-2">
                    <i class="ti ti-arrow-left me-1"></i>Back to Family Accounts
                </a>
                <h2 class="page-title">
                    <i class="ti ti-users-group me-2"></i>
                    <?= e($family['family_name']) ?>
                </h2>
                <div class="text-muted mt-1">
                    Account: <?= e($family['account_number']) ?>
                </div>
            </div>
            <div class="col-auto ms-auto">
                <div class="btn-list">
                    <a href="/finance/family-accounts/<?= $family['id'] ?>/statement" class="btn btn-secondary" target="_blank">
                        <i class="ti ti-printer me-1"></i>Print Statement
                    </a>
                    <a href="/finance/payments/record?family=<?= $family['id'] ?>" class="btn btn-primary">
                        <i class="ti ti-cash me-1"></i>Record Payment
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <!-- Summary Cards -->
        <div class="row row-deck row-cards mb-3">
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Family Members</div>
                        </div>
                        <div class="h1 mb-0"><?= count($members) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Total Invoiced</div>
                        </div>
                        <div class="h1 mb-0">KES <?= number_format($totalInvoiced, 2) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Total Paid</div>
                        </div>
                        <div class="h1 mb-0 text-success">KES <?= number_format($totalPaid, 2) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Total Balance</div>
                        </div>
                        <div class="h1 mb-0 <?= $totalBalance > 0 ? 'text-danger' : ($totalBalance < 0 ? 'text-info' : 'text-success') ?>">
                            KES <?= number_format($totalBalance, 2) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column: Family Info + Members -->
            <div class="col-lg-4">
                <!-- Family Information -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title">Family Information</h3>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-5">Account #:</dt>
                            <dd class="col-7"><?= e($family['account_number']) ?></dd>

                            <dt class="col-5">Status:</dt>
                            <dd class="col-7">
                                <?php
                                $statusColors = [
                                    'active' => 'bg-success',
                                    'suspended' => 'bg-danger',
                                    'closed' => 'bg-secondary'
                                ];
                                $statusColor = $statusColors[$family['account_status']] ?? 'bg-secondary';
                                ?>
                                <span class="badge <?= $statusColor ?>"><?= ucfirst($family['account_status']) ?></span>
                            </dd>

                            <dt class="col-5">Billing Type:</dt>
                            <dd class="col-7">
                                <span class="badge <?= $family['billing_type'] === 'consolidated' ? 'bg-blue-lt' : 'bg-secondary-lt' ?>">
                                    <?= ucfirst($family['billing_type']) ?>
                                </span>
                            </dd>

                            <dt class="col-5">Primary Guardian:</dt>
                            <dd class="col-7">
                                <?= e(($family['guardian_first_name'] ?? '') . ' ' . ($family['guardian_last_name'] ?? '')) ?>
                            </dd>

                            <?php if ($family['guardian_phone']): ?>
                            <dt class="col-5">Phone:</dt>
                            <dd class="col-7"><?= e($family['guardian_phone']) ?></dd>
                            <?php endif; ?>

                            <?php if ($family['guardian_email']): ?>
                            <dt class="col-5">Email:</dt>
                            <dd class="col-7"><?= e($family['guardian_email']) ?></dd>
                            <?php endif; ?>

                            <?php if ($family['billing_email'] && $family['billing_email'] !== $family['guardian_email']): ?>
                            <dt class="col-5">Billing Email:</dt>
                            <dd class="col-7"><?= e($family['billing_email']) ?></dd>
                            <?php endif; ?>
                        </dl>
                    </div>
                </div>

                <!-- Family Members -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Family Members</h3>
                        <div class="card-actions">
                            <span class="badge bg-primary"><?= count($members) ?></span>
                        </div>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php if (empty($members)): ?>
                        <div class="list-group-item text-muted text-center py-4">
                            No members linked yet
                        </div>
                        <?php else: ?>
                        <?php foreach ($members as $member): ?>
                        <?php
                        $balance = ($member['total_invoiced'] ?? 0) - ($member['total_paid'] ?? 0);
                        $profileUrl = $member['type'] === 'student'
                            ? "/students/{$member['student_id']}"
                            : "/applicants/{$member['applicant_id']}";
                        ?>
                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="avatar bg-<?= $member['type'] === 'student' ? 'primary' : 'secondary' ?>-lt">
                                        <?= strtoupper(substr($member['first_name'], 0, 1)) ?>
                                    </span>
                                </div>
                                <div class="col text-truncate">
                                    <a href="<?= $profileUrl ?>" class="text-reset d-block">
                                        <?= e($member['first_name'] . ' ' . $member['last_name']) ?>
                                    </a>
                                    <div class="d-block text-muted text-truncate mt-n1">
                                        <?= e($member['grade_name'] ?? 'N/A') ?>
                                        <?php if ($member['type'] !== 'student'): ?>
                                        <span class="badge bg-secondary-lt ms-1">Applicant</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <span class="<?= $balance > 0 ? 'text-danger' : 'text-success' ?>">
                                        KES <?= number_format($balance, 2) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column: Transactions -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Transactions</h3>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recentTransactions)): ?>
                        <div class="empty py-5">
                            <div class="empty-img">
                                <i class="ti ti-receipt" style="font-size: 3rem; color: #adb5bd;"></i>
                            </div>
                            <p class="empty-title">No transactions yet</p>
                            <p class="empty-subtitle text-muted">
                                Invoices and payments for family members will appear here.
                            </p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Reference</th>
                                        <th>Student</th>
                                        <th class="text-end">Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentTransactions as $tx): ?>
                                    <tr>
                                        <td>
                                            <?= date('M j, Y', strtotime($tx['date'])) ?>
                                        </td>
                                        <td>
                                            <?php if ($tx['type'] === 'invoice'): ?>
                                            <span class="badge bg-blue-lt">
                                                <i class="ti ti-file-invoice me-1"></i>Invoice
                                            </span>
                                            <?php else: ?>
                                            <span class="badge bg-green-lt">
                                                <i class="ti ti-cash me-1"></i>Payment
                                            </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $txUrl = $tx['type'] === 'invoice'
                                                ? "/finance/invoices/{$tx['id']}"
                                                : "/finance/payments/{$tx['id']}";
                                            ?>
                                            <a href="<?= $txUrl ?>"><?= e($tx['ref']) ?></a>
                                        </td>
                                        <td><?= e($tx['student_name']) ?></td>
                                        <td class="text-end">
                                            <strong class="<?= $tx['type'] === 'payment' ? 'text-success' : '' ?>">
                                                <?= $tx['type'] === 'payment' ? '+' : '' ?>KES <?= number_format($tx['amount'], 2) ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <?php
                                            $statusColors = [
                                                'confirmed' => 'bg-success',
                                                'paid' => 'bg-success',
                                                'pending' => 'bg-warning',
                                                'unpaid' => 'bg-warning',
                                                'cancelled' => 'bg-danger',
                                                'overdue' => 'bg-danger'
                                            ];
                                            $color = $statusColors[$tx['status']] ?? 'bg-secondary';
                                            ?>
                                            <span class="badge <?= $color ?>"><?= ucfirst($tx['status']) ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Per-Student Breakdown -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">Balance Breakdown by Student</h3>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($members)): ?>
                        <div class="text-center py-4 text-muted">No members</div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Grade</th>
                                        <th class="text-end">Invoiced</th>
                                        <th class="text-end">Paid</th>
                                        <th class="text-end">Balance</th>
                                        <th class="w-1">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($members as $member): ?>
                                    <?php
                                    $invoiced = $member['total_invoiced'] ?? 0;
                                    $paid = $member['total_paid'] ?? 0;
                                    $balance = $invoiced - $paid;
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="avatar avatar-sm bg-<?= $member['type'] === 'student' ? 'primary' : 'secondary' ?>-lt me-2">
                                                    <?= strtoupper(substr($member['first_name'], 0, 1)) ?>
                                                </span>
                                                <?= e($member['first_name'] . ' ' . $member['last_name']) ?>
                                                <?php if ($member['ref']): ?>
                                                <span class="text-muted ms-2">(<?= e($member['ref']) ?>)</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?= e($member['grade_name'] ?? 'N/A') ?></td>
                                        <td class="text-end">KES <?= number_format($invoiced, 2) ?></td>
                                        <td class="text-end text-success">KES <?= number_format($paid, 2) ?></td>
                                        <td class="text-end">
                                            <strong class="<?= $balance > 0 ? 'text-danger' : ($balance < 0 ? 'text-info' : 'text-success') ?>">
                                                KES <?= number_format($balance, 2) ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <a href="/finance/student-accounts/<?= $member['id'] ?>" class="btn btn-sm">
                                                View Account
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="bg-light">
                                    <tr>
                                        <th colspan="2">Family Total</th>
                                        <th class="text-end">KES <?= number_format($totalInvoiced, 2) ?></th>
                                        <th class="text-end text-success">KES <?= number_format($totalPaid, 2) ?></th>
                                        <th class="text-end">
                                            <strong class="<?= $totalBalance > 0 ? 'text-danger' : ($totalBalance < 0 ? 'text-info' : 'text-success') ?>">
                                                KES <?= number_format($totalBalance, 2) ?>
                                            </strong>
                                        </th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
