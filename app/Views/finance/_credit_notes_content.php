<?php
/**
 * Credit Notes - Content
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
                    <i class="ti ti-receipt-refund me-2"></i>
                    Credit Notes
                </h2>
                <div class="text-muted mt-1">
                    Issue credit notes for refunds and adjustments
                </div>
            </div>
            <div class="col-auto ms-auto">
                <?php if (hasPermission('Finance.write') || Gate::hasRole('ADMIN')): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCreditNoteModal">
                    <i class="ti ti-plus me-2"></i>Create Credit Note
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <!-- Summary Stats -->
        <div class="row row-deck row-cards mb-3">
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Total Credit Notes</div>
                        </div>
                        <div class="h1 mb-0"><?= number_format($stats['total_count'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Pending Approval</div>
                        </div>
                        <div class="h1 mb-0 text-warning"><?= number_format($stats['draft_count'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Approved</div>
                        </div>
                        <div class="h1 mb-0 text-info"><?= number_format($stats['approved_count'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Total Applied</div>
                        </div>
                        <div class="h1 mb-0 text-success">KES <?= number_format($stats['total_applied'] ?? 0, 2) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="/finance/credit-notes" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control"
                               placeholder="Credit note #, student name, account #..."
                               value="<?= e($search ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="draft" <?= ($status ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="approved" <?= ($status ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="applied" <?= ($status ?? '') === 'applied' ? 'selected' : '' ?>>Applied</option>
                            <option value="cancelled" <?= ($status ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select">
                            <option value="">All Types</option>
                            <option value="refund" <?= ($credit_type ?? '') === 'refund' ? 'selected' : '' ?>>Refund</option>
                            <option value="adjustment" <?= ($credit_type ?? '') === 'adjustment' ? 'selected' : '' ?>>Adjustment</option>
                            <option value="discount" <?= ($credit_type ?? '') === 'discount' ? 'selected' : '' ?>>Discount</option>
                            <option value="write_off" <?= ($credit_type ?? '') === 'write_off' ? 'selected' : '' ?>>Write Off</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ti ti-search me-1"></i>Filter
                        </button>
                    </div>
                    <?php if (!empty($search) || !empty($status) || !empty($credit_type)): ?>
                    <div class="col-md-2 d-flex align-items-end">
                        <a href="/finance/credit-notes" class="btn btn-secondary w-100">
                            <i class="ti ti-x me-1"></i>Clear
                        </a>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Credit Notes Table -->
        <div class="card" style="overflow: visible;">
            <div class="card-header">
                <h3 class="card-title">Credit Notes</h3>
                <div class="card-actions">
                    <span class="badge bg-blue"><?= count($credit_notes ?? []) ?> records</span>
                </div>
            </div>
            <div class="card-body p-0" style="overflow: visible;">
                <?php if (empty($credit_notes)): ?>
                <div class="empty py-5">
                    <div class="empty-img">
                        <i class="ti ti-receipt-refund" style="font-size: 4rem; color: #adb5bd;"></i>
                    </div>
                    <p class="empty-title">No credit notes found</p>
                    <p class="empty-subtitle text-muted">
                        Credit notes are used for refunds and invoice adjustments.
                    </p>
                    <?php if (hasPermission('Finance.write') || Gate::hasRole('ADMIN')): ?>
                    <div class="empty-action">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCreditNoteModal">
                            <i class="ti ti-plus me-2"></i>Create Credit Note
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="table-responsive" style="overflow: visible;">
                    <table class="table table-vcenter card-table table-hover">
                        <thead>
                            <tr>
                                <th>Credit Note #</th>
                                <th>Account / Student</th>
                                <th>Type</th>
                                <th class="text-end">Amount</th>
                                <th>Invoice</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th class="w-1">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($credit_notes as $cn): ?>
                            <?php
                            $name = $cn['student_first_name']
                                ? $cn['student_first_name'] . ' ' . $cn['student_last_name']
                                : $cn['applicant_first_name'] . ' ' . $cn['applicant_last_name'];

                            $statusColors = [
                                'draft' => 'bg-warning',
                                'approved' => 'bg-info',
                                'applied' => 'bg-success',
                                'cancelled' => 'bg-secondary'
                            ];
                            $statusColor = $statusColors[$cn['status']] ?? 'bg-secondary';

                            $typeLabels = [
                                'refund' => 'Refund',
                                'adjustment' => 'Adjustment',
                                'discount' => 'Discount',
                                'write_off' => 'Write Off'
                            ];
                            $typeLabel = $typeLabels[$cn['credit_type']] ?? ucfirst($cn['credit_type']);
                            ?>
                            <tr>
                                <td>
                                    <strong><?= e($cn['credit_note_number']) ?></strong>
                                </td>
                                <td>
                                    <div>
                                        <?= e($name) ?>
                                        <div class="text-muted small"><?= e($cn['account_number']) ?></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-lt"><?= $typeLabel ?></span>
                                </td>
                                <td class="text-end">
                                    <strong class="text-success">KES <?= number_format($cn['amount'], 2) ?></strong>
                                </td>
                                <td>
                                    <?php if ($cn['invoice_number']): ?>
                                    <a href="/finance/invoices/<?= $cn['invoice_id'] ?>" class="text-reset">
                                        <?= e($cn['invoice_number']) ?>
                                    </a>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $statusColor ?>"><?= ucfirst($cn['status']) ?></span>
                                </td>
                                <td>
                                    <?= date('M j, Y', strtotime($cn['issue_date'])) ?>
                                    <div class="text-muted small">
                                        <?php if ($cn['created_by_name']): ?>
                                        by <?= e($cn['created_by_name']) ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            Actions
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <button class="dropdown-item" onclick="viewCreditNote(<?= htmlspecialchars(json_encode($cn)) ?>)">
                                                <i class="ti ti-eye me-2"></i>View Details
                                            </button>
                                            <?php if ($cn['status'] === 'draft' && (hasPermission('Finance.write') || Gate::hasRole('ADMIN'))): ?>
                                            <div class="dropdown-divider"></div>
                                            <form method="POST" action="/finance/credit-notes/<?= $cn['id'] ?>/approve" class="d-inline">
                                                <button type="submit" class="dropdown-item text-info" onclick="return confirm('Approve this credit note?')">
                                                    <i class="ti ti-check me-2"></i>Approve
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                            <?php if ($cn['status'] === 'approved' && (hasPermission('Finance.write') || Gate::hasRole('ADMIN'))): ?>
                                            <div class="dropdown-divider"></div>
                                            <form method="POST" action="/finance/credit-notes/<?= $cn['id'] ?>/apply" class="d-inline">
                                                <button type="submit" class="dropdown-item text-success" onclick="return confirm('Apply this credit note to the account? This will reduce the account balance.')">
                                                    <i class="ti ti-circle-check me-2"></i>Apply to Account
                                                </button>
                                            </form>
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

<!-- Create Credit Note Modal -->
<div class="modal fade" id="createCreditNoteModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="/finance/credit-notes/store">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="ti ti-plus me-2"></i>Create Credit Note</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Fee Account Search -->
                        <div class="col-12">
                            <label class="form-label required">Fee Account</label>
                            <div class="position-relative">
                                <input type="text" id="accountSearchInput" class="form-control"
                                       placeholder="Type to search by name, account #, or admission #..."
                                       autocomplete="off">
                                <input type="hidden" name="fee_account_id" id="feeAccountId" required>
                                <div id="accountSearchResults" class="position-absolute w-100 bg-white border rounded shadow-sm"
                                     style="display:none; z-index:1050; max-height:200px; overflow-y:auto;"></div>
                            </div>
                            <div id="selectedAccountInfo" class="mt-2" style="display:none;">
                                <span class="badge bg-primary-lt" id="selectedAccountBadge"></span>
                                <button type="button" class="btn btn-sm btn-ghost-danger ms-2" onclick="clearAccountSelection()">
                                    <i class="ti ti-x"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Link to Invoice (Optional)</label>
                            <select name="invoice_id" id="invoiceSelect" class="form-select">
                                <option value="">No specific invoice (or no unpaid invoices)</option>
                                <?php foreach ($invoices ?? [] as $invoice): ?>
                                <option value="<?= $invoice['id'] ?>" data-account="<?= $invoice['fee_account_id'] ?>" data-balance="<?= $invoice['balance'] ?>">
                                    <?= e($invoice['invoice_number']) ?> - Balance: KES <?= number_format($invoice['balance'], 2) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Only unpaid/partial invoices shown. If linked, credit reduces invoice balance.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Credit Type</label>
                            <select name="credit_type" class="form-select" required>
                                <option value="">Select type...</option>
                                <option value="refund">Refund - Money returned to parent/guardian</option>
                                <option value="adjustment">Adjustment - Correct billing error</option>
                                <option value="discount">Discount - Promotional or merit-based</option>
                                <option value="write_off">Write Off - Bad debt forgiveness</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Amount (KES)</label>
                            <input type="number" name="amount" class="form-control" required step="0.01" min="0.01" placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            &nbsp;
                        </div>
                        <div class="col-12">
                            <label class="form-label required">Reason</label>
                            <textarea name="reason" class="form-control" rows="2" required placeholder="Explain why this credit note is being issued..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Additional Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Any additional information..."></textarea>
                        </div>

                        <!-- Notification Options -->
                        <div class="col-12">
                            <label class="form-label">Notifications (sent when approved)</label>
                            <div class="row">
                                <div class="col-auto">
                                    <label class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="send_sms" value="1">
                                        <span class="form-check-label">
                                            <i class="ti ti-message me-1"></i>Send SMS
                                        </span>
                                    </label>
                                </div>
                                <div class="col-auto">
                                    <label class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="send_email" value="1">
                                        <span class="form-check-label">
                                            <i class="ti ti-mail me-1"></i>Send Email
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info mt-3 mb-0">
                        <i class="ti ti-info-circle me-2"></i>
                        Credit notes are created in <strong>Draft</strong> status and must be approved before being applied to the account.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Credit Note</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Credit Note Modal -->
<div class="modal fade" id="viewCreditNoteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-receipt-refund me-2"></i>Credit Note Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <dl class="row">
                    <dt class="col-5">Credit Note #:</dt>
                    <dd class="col-7" id="viewCnNumber"></dd>

                    <dt class="col-5">Account:</dt>
                    <dd class="col-7" id="viewCnAccount"></dd>

                    <dt class="col-5">Type:</dt>
                    <dd class="col-7" id="viewCnType"></dd>

                    <dt class="col-5">Amount:</dt>
                    <dd class="col-7" id="viewCnAmount"></dd>

                    <dt class="col-5">Status:</dt>
                    <dd class="col-7" id="viewCnStatus"></dd>

                    <dt class="col-5">Issue Date:</dt>
                    <dd class="col-7" id="viewCnDate"></dd>

                    <dt class="col-5">Reason:</dt>
                    <dd class="col-7" id="viewCnReason"></dd>

                    <dt class="col-5" id="viewCnNotesLabel" style="display:none;">Notes:</dt>
                    <dd class="col-7" id="viewCnNotes"></dd>

                    <dt class="col-5">Created By:</dt>
                    <dd class="col-7" id="viewCnCreatedBy"></dd>

                    <dt class="col-5" id="viewCnApprovedLabel" style="display:none;">Approved By:</dt>
                    <dd class="col-7" id="viewCnApprovedBy"></dd>
                </dl>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// Expose function globally for onclick handlers (needed for AJAX navigation)
window.viewCreditNote = function(cn) {
    var name = cn.student_first_name
        ? cn.student_first_name + ' ' + cn.student_last_name
        : cn.applicant_first_name + ' ' + cn.applicant_last_name;

    var typeLabels = {
        'refund': 'Refund',
        'adjustment': 'Adjustment',
        'discount': 'Discount',
        'write_off': 'Write Off'
    };

    var statusColors = {
        'draft': 'warning',
        'approved': 'info',
        'applied': 'success',
        'cancelled': 'secondary'
    };

    document.getElementById('viewCnNumber').textContent = cn.credit_note_number;
    document.getElementById('viewCnAccount').innerHTML = name + '<br><small class="text-muted">' + cn.account_number + '</small>';
    document.getElementById('viewCnType').textContent = typeLabels[cn.credit_type] || cn.credit_type;
    document.getElementById('viewCnAmount').innerHTML = '<strong class="text-success">KES ' + parseFloat(cn.amount).toLocaleString('en-US', {minimumFractionDigits: 2}) + '</strong>';
    document.getElementById('viewCnStatus').innerHTML = '<span class="badge bg-' + (statusColors[cn.status] || 'secondary') + '">' + cn.status.charAt(0).toUpperCase() + cn.status.slice(1) + '</span>';
    document.getElementById('viewCnDate').textContent = new Date(cn.issue_date).toLocaleDateString('en-US', {year: 'numeric', month: 'short', day: 'numeric'});
    document.getElementById('viewCnReason').textContent = cn.reason;

    if (cn.notes) {
        document.getElementById('viewCnNotesLabel').style.display = '';
        document.getElementById('viewCnNotes').textContent = cn.notes;
    } else {
        document.getElementById('viewCnNotesLabel').style.display = 'none';
        document.getElementById('viewCnNotes').textContent = '';
    }

    document.getElementById('viewCnCreatedBy').textContent = cn.created_by_name || '-';

    if (cn.approved_by_name) {
        document.getElementById('viewCnApprovedLabel').style.display = '';
        document.getElementById('viewCnApprovedBy').textContent = cn.approved_by_name + ' on ' + new Date(cn.approved_at).toLocaleDateString();
    } else {
        document.getElementById('viewCnApprovedLabel').style.display = 'none';
        document.getElementById('viewCnApprovedBy').textContent = '';
    }

    var modal = new bootstrap.Modal(document.getElementById('viewCreditNoteModal'));
    modal.show();
};

// Account search data
var accountsData = <?= json_encode($accounts ?? []) ?>;
var invoiceSelect = document.getElementById('invoiceSelect');

// Clear account selection
window.clearAccountSelection = function() {
    document.getElementById('feeAccountId').value = '';
    document.getElementById('accountSearchInput').value = '';
    document.getElementById('accountSearchInput').disabled = false;
    document.getElementById('selectedAccountInfo').style.display = 'none';
    filterInvoicesByAccount('');
};

// Filter invoices based on selected account
function filterInvoicesByAccount(selectedAccountId) {
    if (!invoiceSelect) return;
    var options = invoiceSelect.querySelectorAll('option');
    options.forEach(function(option) {
        if (!option.value) return;
        var accountId = option.getAttribute('data-account');
        if (selectedAccountId && accountId !== selectedAccountId) {
            option.style.display = 'none';
        } else {
            option.style.display = '';
        }
    });
    invoiceSelect.value = '';
}

// Account search functionality
(function() {
    var searchInput = document.getElementById('accountSearchInput');
    var resultsDiv = document.getElementById('accountSearchResults');
    var hiddenInput = document.getElementById('feeAccountId');
    var selectedInfo = document.getElementById('selectedAccountInfo');
    var selectedBadge = document.getElementById('selectedAccountBadge');
    var searchTimeout;

    if (!searchInput) return;

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        var query = this.value.toLowerCase().trim();

        if (query.length < 2) {
            resultsDiv.style.display = 'none';
            return;
        }

        searchTimeout = setTimeout(function() {
            var matches = accountsData.filter(function(acc) {
                var searchStr = (acc.account_number + ' ' + acc.first_name + ' ' + acc.last_name + ' ' + (acc.admission_number || '')).toLowerCase();
                return searchStr.indexOf(query) !== -1;
            }).slice(0, 10); // Limit to 10 results

            if (matches.length === 0) {
                resultsDiv.innerHTML = '<div class="p-2 text-muted">No accounts found</div>';
            } else {
                resultsDiv.innerHTML = matches.map(function(acc) {
                    return '<div class="p-2 border-bottom cursor-pointer account-result" data-id="' + acc.id + '" data-name="' + acc.first_name + ' ' + acc.last_name + '" data-account="' + acc.account_number + '" style="cursor:pointer;">' +
                        '<strong>' + acc.account_number + '</strong> - ' + acc.first_name + ' ' + acc.last_name +
                        (acc.admission_number ? ' <span class="text-muted">(' + acc.admission_number + ')</span>' : '') +
                        '</div>';
                }).join('');
            }
            resultsDiv.style.display = 'block';
        }, 200);
    });

    // Handle result click
    resultsDiv.addEventListener('click', function(e) {
        var result = e.target.closest('.account-result');
        if (result) {
            var id = result.getAttribute('data-id');
            var name = result.getAttribute('data-name');
            var account = result.getAttribute('data-account');

            hiddenInput.value = id;
            searchInput.value = '';
            searchInput.disabled = true;
            selectedBadge.textContent = account + ' - ' + name;
            selectedInfo.style.display = 'block';
            resultsDiv.style.display = 'none';

            // Filter invoices for this account
            filterInvoicesByAccount(id);
        }
    });

    // Hide results when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
            resultsDiv.style.display = 'none';
        }
    });

    // Show results on focus if there's a query
    searchInput.addEventListener('focus', function() {
        if (this.value.length >= 2) {
            resultsDiv.style.display = 'block';
        }
    });
})();
</script>
