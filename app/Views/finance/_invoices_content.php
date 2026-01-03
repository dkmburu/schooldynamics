<?php
/**
 * Invoices List - Content
 */
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <a href="/finance" class="btn btn-ghost-secondary btn-sm mb-2">
                    <i class="ti ti-arrow-left me-1"></i>Back to Finance
                </a>
                <h2 class="page-title">
                    <i class="ti ti-file-invoice me-2"></i>
                    All Invoices
                </h2>
            </div>
            <div class="col-auto ms-auto">
                <a href="/finance/invoices/generate" class="btn btn-primary">
                    <i class="ti ti-file-plus me-2"></i>Generate Invoices
                </a>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="card">
            <div class="card-header">
                <form method="GET" class="row g-2 align-items-center">
                    <div class="col-auto">
                        <input type="text" name="search" class="form-control form-control-sm"
                               placeholder="Search invoice #, student..." value="<?= e($search ?? '') ?>">
                    </div>
                    <div class="col-auto">
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All Status</option>
                            <option value="draft">Draft</option>
                            <option value="sent">Sent</option>
                            <option value="partial">Partial</option>
                            <option value="paid">Paid</option>
                            <option value="overdue">Overdue</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="ti ti-search me-1"></i>Search
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-body">
                <?php if (empty($invoices)): ?>
                <div class="empty">
                    <div class="empty-img">
                        <i class="ti ti-file-invoice" style="font-size: 4rem; color: #adb5bd;"></i>
                    </div>
                    <p class="empty-title">No invoices found</p>
                    <p class="empty-subtitle text-muted">
                        Generate invoices from fee structures to start billing students.
                    </p>
                    <div class="empty-action">
                        <a href="/finance/invoices/generate" class="btn btn-primary">
                            <i class="ti ti-file-plus me-2"></i>Generate Invoices
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-striped">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Student</th>
                                <th>Grade</th>
                                <th class="text-end">Amount</th>
                                <th class="text-end">Paid</th>
                                <th class="text-end">Balance</th>
                                <th>Status</th>
                                <th>Due Date</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoices as $inv):
                                // Determine person name (applicant or student)
                                $isApplicant = !empty($inv['applicant_id']);
                                $personName = $isApplicant
                                    ? trim($inv['applicant_first_name'] . ' ' . $inv['applicant_last_name'])
                                    : trim($inv['student_first_name'] . ' ' . $inv['student_last_name']);
                                $personRef = $isApplicant ? $inv['application_ref'] : $inv['admission_number'];
                                $personGrade = $inv['applicant_grade'] ?? 'N/A';

                                // Status badge color
                                $statusColors = [
                                    'draft' => 'secondary',
                                    'pending' => 'warning',
                                    'partial' => 'info',
                                    'paid' => 'success',
                                    'cancelled' => 'danger',
                                    'refunded' => 'purple'
                                ];
                                $statusColor = $statusColors[$inv['status']] ?? 'secondary';
                            ?>
                            <tr>
                                <td>
                                    <a href="/finance/invoices/<?= $inv['id'] ?>">
                                        <strong><?= e($inv['invoice_number']) ?></strong>
                                    </a>
                                    <div class="small text-muted"><?= ucfirst($inv['invoice_type']) ?></div>
                                </td>
                                <td>
                                    <div><?= e($personName) ?></div>
                                    <div class="small text-muted"><?= e($personRef) ?></div>
                                </td>
                                <td><?= e($personGrade) ?></td>
                                <td class="text-end">KES <?= number_format($inv['total_amount'], 2) ?></td>
                                <td class="text-end text-success">KES <?= number_format($inv['amount_paid'] ?? 0, 2) ?></td>
                                <td class="text-end <?= $inv['balance'] > 0 ? 'text-danger' : 'text-success' ?>">
                                    KES <?= number_format($inv['balance'], 2) ?>
                                </td>
                                <td><span class="badge bg-<?= $statusColor ?>-lt"><?= ucfirst($inv['status']) ?></span></td>
                                <td><?= formatDate($inv['due_date']) ?></td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-icon btn-ghost-secondary" data-bs-toggle="dropdown">
                                            <i class="ti ti-dots-vertical"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a class="dropdown-item" href="/finance/invoices/<?= $inv['id'] ?>">
                                                <i class="ti ti-eye me-2"></i>View
                                            </a>
                                            <a class="dropdown-item" href="/finance/invoices/<?= $inv['id'] ?>" target="_blank" onclick="window.print(); return false;">
                                                <i class="ti ti-printer me-2"></i>Print
                                            </a>
                                            <?php if ($inv['balance'] > 0): ?>
                                            <a class="dropdown-item" href="/finance/payments/record?invoice_id=<?= $inv['id'] ?>">
                                                <i class="ti ti-cash me-2"></i>Record Payment
                                            </a>
                                            <?php endif; ?>
                                        </div>
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
</div>
