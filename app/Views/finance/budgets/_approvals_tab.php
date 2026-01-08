<?php
/**
 * Budget Approval Queue Tab
 * Pending budgets, revisions, and overrun approvals
 */

$pendingBudgets = $tabData['budgets'] ?? [];
$pendingRevisions = $tabData['revisions'] ?? [];
$pendingOverruns = $tabData['overruns'] ?? [];
$totalPending = count($pendingBudgets) + count($pendingRevisions) + count($pendingOverruns);
?>

<?php if ($totalPending === 0): ?>
<div class="empty">
    <div class="empty-img">
        <i class="ti ti-checkbox" style="font-size: 4rem; color: #2fb344;"></i>
    </div>
    <p class="empty-title">All caught up!</p>
    <p class="empty-subtitle text-muted">
        There are no pending budget approvals at this time.
    </p>
</div>
<?php else: ?>

<!-- Pending Budgets -->
<?php if (!empty($pendingBudgets)): ?>
<div class="mb-4">
    <h4 class="mb-3">
        <i class="ti ti-file-invoice text-warning me-2"></i>
        Pending Budget Approvals
        <span class="badge bg-warning ms-2"><?= count($pendingBudgets) ?></span>
    </h4>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Budget</th>
                    <th>Account</th>
                    <th class="text-end">Amount</th>
                    <th>Requested By</th>
                    <th>Approver</th>
                    <th>Date</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pendingBudgets as $item): ?>
                <tr>
                    <td>
                        <code><?= e($item['budget_code']) ?></code><br>
                        <strong><?= e($item['name']) ?></strong>
                    </td>
                    <td><?= e($item['account_name']) ?></td>
                    <td class="text-end"><strong>KES <?= number_format($item['amount']) ?></strong></td>
                    <td><?= e($item['requested_by']) ?></td>
                    <td>
                        <span class="<?= ($item['approver_name'] ?? '') === 'Pending Assignment' ? 'text-muted' : '' ?>">
                            <?= e($item['approver_name'] ?? 'Not Assigned') ?>
                        </span>
                        <?php if (!empty($item['approver_role'])): ?>
                        <br><small class="text-muted"><?= e($item['approver_role']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= date('M j, Y', strtotime($item['requested_at'])) ?></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-success" onclick="approveBudget(<?= $item['id'] ?>)">
                                <i class="ti ti-check me-1"></i>Approve
                            </button>
                            <button type="button" class="btn btn-danger" onclick="rejectBudget(<?= $item['id'] ?>)">
                                <i class="ti ti-x me-1"></i>Reject
                            </button>
                        </div>
                    </td>
                </tr>
                <?php if ($item['notes']): ?>
                <tr class="bg-light">
                    <td colspan="7" class="py-1">
                        <small class="text-muted"><i class="ti ti-note me-1"></i><?= e($item['notes']) ?></small>
                    </td>
                </tr>
                <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Pending Revisions -->
<?php if (!empty($pendingRevisions)): ?>
<div class="mb-4">
    <h4 class="mb-3">
        <i class="ti ti-refresh text-info me-2"></i>
        Pending Revision Approvals
        <span class="badge bg-info ms-2"><?= count($pendingRevisions) ?></span>
    </h4>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Budget</th>
                    <th>Account</th>
                    <th class="text-end">Change Amount</th>
                    <th>Requested By</th>
                    <th>Approver</th>
                    <th>Date</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pendingRevisions as $item): ?>
                <tr>
                    <td>
                        <code><?= e($item['budget_code']) ?></code><br>
                        <strong><?= e($item['name']) ?></strong>
                    </td>
                    <td><?= e($item['account_name']) ?></td>
                    <td class="text-end <?= $item['amount'] >= 0 ? 'text-success' : 'text-danger' ?>">
                        <strong><?= $item['amount'] >= 0 ? '+' : '' ?>KES <?= number_format($item['amount']) ?></strong>
                    </td>
                    <td><?= e($item['requested_by']) ?></td>
                    <td>
                        <span class="<?= ($item['approver_name'] ?? '') === 'Pending Assignment' ? 'text-muted' : '' ?>">
                            <?= e($item['approver_name'] ?? 'Not Assigned') ?>
                        </span>
                        <?php if (!empty($item['approver_role'])): ?>
                        <br><small class="text-muted"><?= e($item['approver_role']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= date('M j, Y', strtotime($item['requested_at'])) ?></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-success" onclick="approveRevision(<?= $item['id'] ?>)">
                                <i class="ti ti-check me-1"></i>Approve
                            </button>
                            <button type="button" class="btn btn-danger" onclick="rejectRevision(<?= $item['id'] ?>)">
                                <i class="ti ti-x me-1"></i>Reject
                            </button>
                        </div>
                    </td>
                </tr>
                <?php if ($item['notes']): ?>
                <tr class="bg-light">
                    <td colspan="7" class="py-1">
                        <small class="text-muted"><i class="ti ti-note me-1"></i>Reason: <?= e($item['notes']) ?></small>
                    </td>
                </tr>
                <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Pending Overruns -->
<?php if (!empty($pendingOverruns)): ?>
<div class="mb-4">
    <h4 class="mb-3">
        <i class="ti ti-alert-triangle text-danger me-2"></i>
        Budget Overrun Requests
        <span class="badge bg-danger ms-2"><?= count($pendingOverruns) ?></span>
    </h4>
    <div class="alert alert-warning">
        <i class="ti ti-alert-circle me-2"></i>
        These transactions exceed their allocated budgets and require approval to proceed.
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Budget</th>
                    <th>Transaction</th>
                    <th class="text-end">Overrun Amount</th>
                    <th>Requested By</th>
                    <th>Approver</th>
                    <th>Date</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pendingOverruns as $item): ?>
                <tr>
                    <td>
                        <code><?= e($item['budget_code']) ?></code><br>
                        <?= e($item['account_name']) ?>
                    </td>
                    <td>
                        <span class="badge bg-secondary"><?= ucfirst(str_replace('_', ' ', $item['name'])) ?></span>
                    </td>
                    <td class="text-end text-danger">
                        <strong>KES <?= number_format($item['amount']) ?></strong>
                    </td>
                    <td><?= e($item['requested_by']) ?></td>
                    <td>
                        <span class="<?= ($item['approver_name'] ?? '') === 'Pending Assignment' ? 'text-muted' : '' ?>">
                            <?= e($item['approver_name'] ?? 'Not Assigned') ?>
                        </span>
                        <?php if (!empty($item['approver_role'])): ?>
                        <br><small class="text-muted"><?= e($item['approver_role']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= date('M j, Y', strtotime($item['requested_at'])) ?></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-success" onclick="approveOverrun(<?= $item['id'] ?>)">
                                <i class="ti ti-check me-1"></i>Approve
                            </button>
                            <button type="button" class="btn btn-danger" onclick="rejectOverrun(<?= $item['id'] ?>)">
                                <i class="ti ti-x me-1"></i>Reject
                            </button>
                        </div>
                    </td>
                </tr>
                <?php if ($item['notes']): ?>
                <tr class="bg-light">
                    <td colspan="7" class="py-1">
                        <small class="text-muted"><i class="ti ti-note me-1"></i>Reason: <?= e($item['notes']) ?></small>
                    </td>
                </tr>
                <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>

<!-- Rejection Reason Modal -->
<div class="modal modal-blur fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="rejectType">
                <input type="hidden" id="rejectId">
                <div class="mb-3">
                    <label class="form-label required">Rejection Reason</label>
                    <textarea class="form-control" id="rejectReason" rows="3" required
                              placeholder="Please provide a reason for rejection..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="confirmReject()">Reject</button>
            </div>
        </div>
    </div>
</div>

<script>
function approveBudget(id) {
    if (!confirm('Approve this budget?')) return;
    submitApproval('budget', id, 'approve');
}

function rejectBudget(id) {
    openRejectModal('budget', id);
}

function approveRevision(id) {
    if (!confirm('Approve this budget revision?')) return;
    submitApproval('revision', id, 'approve');
}

function rejectRevision(id) {
    openRejectModal('revision', id);
}

function approveOverrun(id) {
    if (!confirm('Approve this budget overrun? The transaction will be allowed to proceed.')) return;
    submitApproval('overrun', id, 'approve');
}

function rejectOverrun(id) {
    openRejectModal('overrun', id);
}

function openRejectModal(type, id) {
    document.getElementById('rejectType').value = type;
    document.getElementById('rejectId').value = id;
    document.getElementById('rejectReason').value = '';
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

function confirmReject() {
    const type = document.getElementById('rejectType').value;
    const id = document.getElementById('rejectId').value;
    const reason = document.getElementById('rejectReason').value.trim();

    if (!reason) {
        alert('Please provide a rejection reason');
        return;
    }

    submitApproval(type, id, 'reject', reason);
}

function submitApproval(type, id, action, reason = null) {
    let url;
    switch (type) {
        case 'budget':
            url = `/finance/budgets/api/budgets/${id}/${action}`;
            break;
        case 'revision':
            url = `/finance/budgets/api/revisions/${id}/${action}`;
            break;
        case 'overrun':
            url = `/finance/budgets/api/overruns/${id}/${action}`;
            break;
    }

    const data = reason ? { reason: reason } : {};

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('rejectModal'))?.hide();
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
    });
}
</script>
