<?php
/**
 * Record Payment - Content
 * Based on applicant payment modal functionality
 */

// Determine if we have a pre-selected invoice/account
$hasInvoice = !empty($invoice);
$personName = '';
$personRef = '';

if ($hasInvoice) {
    $isApplicant = !empty($invoice['applicant_id']);
    $personName = $isApplicant
        ? trim($invoice['applicant_first_name'] . ' ' . $invoice['applicant_last_name'])
        : trim($invoice['student_first_name'] . ' ' . $invoice['student_last_name']);
    $personRef = $isApplicant ? $invoice['application_ref'] : $invoice['admission_number'];
}

// Convert payment methods to JSON for JavaScript
$paymentMethodsJson = json_encode($payment_methods ?? []);
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <a href="javascript:history.back()" class="btn btn-ghost-secondary btn-sm mb-2">
                    <i class="ti ti-arrow-left me-1"></i>Back
                </a>
                <h2 class="page-title">
                    <i class="ti ti-cash me-2"></i>
                    Record Payment
                </h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <form id="paymentForm" method="POST" action="/finance/payments/store" enctype="multipart/form-data">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h3 class="card-title text-white">
                                <i class="ti ti-receipt me-2"></i>Payment Details
                            </h3>
                        </div>
                        <div class="card-body">
                            <!-- Account Selection / Display -->
                            <?php if ($hasInvoice && $fee_account): ?>
                            <!-- Pre-selected from invoice -->
                            <div class="card bg-light mb-4">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="form-label text-muted small">Payer</label>
                                            <div class="fw-bold"><?= e($personName) ?></div>
                                            <div class="text-muted small"><?= e($personRef) ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label text-muted small">Account</label>
                                            <div class="fw-bold"><?= e($fee_account['account_number']) ?></div>
                                        </div>
                                    </div>
                                    <hr class="my-3">
                                    <div class="row">
                                        <div class="col-4">
                                            <label class="form-label text-muted small">Invoice Total</label>
                                            <div class="fw-bold">KES <?= number_format($invoice['total_amount'], 2) ?></div>
                                        </div>
                                        <div class="col-4">
                                            <label class="form-label text-muted small">Paid</label>
                                            <div class="fw-bold text-success">KES <?= number_format($invoice['amount_paid'], 2) ?></div>
                                        </div>
                                        <div class="col-4">
                                            <label class="form-label text-muted small">Balance Due</label>
                                            <div class="fw-bold text-danger" id="invoiceBalance" data-balance="<?= $invoice['balance'] ?>">
                                                KES <?= number_format($invoice['balance'], 2) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="fee_account_id" value="<?= $fee_account['id'] ?>">
                            <input type="hidden" name="invoice_id" value="<?= $invoice['id'] ?>">
                            <?php else: ?>
                            <!-- Account Search -->
                            <div class="mb-4" style="position: relative;">
                                <label class="form-label required">Student / Applicant Account</label>
                                <div class="input-group mb-2">
                                    <span class="input-group-text"><i class="ti ti-search"></i></span>
                                    <input type="text" id="accountSearch" class="form-control"
                                           placeholder="Search by name, admission number, or account number...">
                                </div>
                                <input type="hidden" name="fee_account_id" id="selectedAccountId" required>

                                <!-- Search Results -->
                                <div id="searchResults" class="list-group mb-2" style="display: none; max-height: 300px; overflow-y: auto;"></div>

                                <!-- Selected Account Display -->
                                <div id="selectedAccountCard" class="card bg-light" style="display: none;">
                                    <div class="card-body py-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong id="selectedAccountName"></strong>
                                                <div class="text-muted small" id="selectedAccountRef"></div>
                                            </div>
                                            <div class="text-end">
                                                <div class="text-muted small">Balance Due</div>
                                                <strong class="text-danger" id="selectedAccountBalance"></strong>
                                            </div>
                                            <button type="button" class="btn btn-ghost-secondary btn-sm" onclick="clearAccountSelection()">
                                                <i class="ti ti-x"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="row">
                                <div class="col-md-6">
                                    <!-- Payment Amount -->
                                    <div class="mb-3">
                                        <label class="form-label required">Payment Amount (KES)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">KES</span>
                                            <input type="number" name="amount" id="paymentAmount" class="form-control"
                                                   required step="0.01" min="1"
                                                   <?php if ($hasInvoice): ?>
                                                   max="<?= $invoice['balance'] ?>" value="<?= $invoice['balance'] ?>"
                                                   <?php endif; ?>
                                                   placeholder="0.00">
                                        </div>
                                        <?php if ($hasInvoice): ?>
                                        <small class="text-muted">Max: KES <?= number_format($invoice['balance'], 2) ?></small>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Payment Method -->
                                    <div class="mb-3">
                                        <label class="form-label required">Payment Method</label>
                                        <select name="payment_method_id" id="paymentMethod" class="form-select" required>
                                            <option value="">Select Method...</option>
                                            <?php foreach ($payment_methods ?? [] as $method): ?>
                                            <option value="<?= $method['id'] ?>"
                                                    data-code="<?= e($method['code']) ?>"
                                                    data-requires-reference="<?= $method['requires_reference'] ?>"
                                                    data-reference-label="<?= e($method['reference_label'] ?? 'Reference') ?>"
                                                    data-reference-placeholder="<?= e($method['reference_placeholder'] ?? '') ?>"
                                                    data-requires-bank="<?= $method['requires_bank'] ?>"
                                                    data-requires-cheque-date="<?= $method['requires_cheque_date'] ?? 0 ?>"
                                                    data-allows-attachment="<?= $method['allows_attachment'] ?? 0 ?>">
                                                <?= e($method['name']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Reference Number (dynamic) -->
                                    <div class="mb-3" id="referenceGroup" style="display: none;">
                                        <label class="form-label required" id="referenceLabel">Reference Number</label>
                                        <input type="text" name="reference_number" id="referenceNumber" class="form-control"
                                               placeholder="">
                                    </div>

                                    <!-- Bank Selection (for cheque/transfer) -->
                                    <div class="mb-3" id="bankGroup" style="display: none;">
                                        <label class="form-label">Payer's Bank</label>
                                        <select name="payer_bank_id" id="payerBank" class="form-select">
                                            <option value="">Select Bank...</option>
                                            <?php foreach ($banks ?? [] as $bank): ?>
                                            <option value="<?= $bank['id'] ?>"><?= e($bank['bank_name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Cheque Date -->
                                    <div class="mb-3" id="chequeDateGroup" style="display: none;">
                                        <label class="form-label">Cheque Date</label>
                                        <input type="date" name="cheque_date" id="chequeDate" class="form-control">
                                        <small class="text-muted">Maturity date for post-dated cheques</small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <!-- Payment Date -->
                                    <div class="mb-3">
                                        <label class="form-label required">Payment Date</label>
                                        <input type="date" name="payment_date" class="form-control"
                                               value="<?= date('Y-m-d') ?>" required max="<?= date('Y-m-d') ?>">
                                    </div>

                                    <!-- School Bank Account -->
                                    <?php if (!empty($bank_accounts)): ?>
                                    <div class="mb-3" id="schoolAccountGroup">
                                        <label class="form-label">Deposited To (School Account)</label>
                                        <select name="school_bank_account_id" id="schoolBankAccount" class="form-select">
                                            <option value="">Select School Account...</option>
                                            <?php foreach ($bank_accounts as $acc): ?>
                                            <option value="<?= $acc['id'] ?>" <?= $acc['is_default'] ? 'selected' : '' ?>>
                                                <?= e($acc['bank_name']) ?> - <?= e($acc['account_number']) ?>
                                                <?= $acc['is_default'] ? '(Default)' : '' ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Attachment -->
                                    <div class="mb-3" id="attachmentGroup" style="display: none;">
                                        <label class="form-label">Attachment (Cheque/Slip)</label>
                                        <input type="file" name="attachment" class="form-control" accept="image/*,.pdf">
                                        <small class="text-muted">Upload cheque image or bank deposit slip</small>
                                    </div>

                                    <!-- Notes -->
                                    <div class="mb-3">
                                        <label class="form-label">Notes</label>
                                        <textarea name="notes" class="form-control" rows="2"
                                                  placeholder="Any additional notes..."></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Allocation -->
                            <?php if (!$hasInvoice): ?>
                            <div class="card bg-light mb-3">
                                <div class="card-body py-2">
                                    <label class="form-label small text-muted mb-2">Payment Allocation:</label>
                                    <div class="d-flex gap-3">
                                        <label class="form-check">
                                            <input type="radio" name="allocation_type" value="fifo" class="form-check-input" checked>
                                            <span class="form-check-label">FIFO (Oldest First)</span>
                                        </label>
                                        <label class="form-check">
                                            <input type="radio" name="allocation_type" value="credit" class="form-check-input">
                                            <span class="form-check-label">Hold as Credit</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <?php else: ?>
                            <input type="hidden" name="allocation_type" value="specific">
                            <?php endif; ?>

                            <!-- Post-Payment Actions -->
                            <div class="card bg-light">
                                <div class="card-body py-2">
                                    <label class="form-label small text-muted mb-2">After recording payment:</label>
                                    <div class="d-flex gap-3">
                                        <label class="form-check form-check-inline">
                                            <input type="checkbox" name="send_sms" value="1" class="form-check-input">
                                            <span class="form-check-label"><i class="ti ti-message me-1"></i>Send SMS Receipt</span>
                                        </label>
                                        <label class="form-check form-check-inline">
                                            <input type="checkbox" name="send_email" value="1" class="form-check-input">
                                            <span class="form-check-label"><i class="ti ti-mail me-1"></i>Email Receipt</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="d-flex justify-content-between">
                                <a href="javascript:history.back()" class="btn btn-ghost-secondary">
                                    <i class="ti ti-arrow-left me-1"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-success" id="submitBtn">
                                    <i class="ti ti-check me-1"></i>Record Payment
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="paymentSuccessModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="ti ti-check me-2"></i>Payment Recorded Successfully
                </h5>
            </div>
            <div class="modal-body text-center py-4">
                <div class="mb-4">
                    <span class="avatar avatar-xl bg-success-lt text-success mb-3" style="width: 80px; height: 80px;">
                        <i class="ti ti-check" style="font-size: 2.5rem;"></i>
                    </span>
                    <h3 id="successReceiptNumber">RCP-00001</h3>
                    <p class="text-muted mb-0">Amount: <strong class="text-success" id="successAmount">KES 0.00</strong></p>
                </div>

                <p class="mb-3">What would you like to do next?</p>

                <div class="d-flex flex-column gap-2">
                    <button type="button" class="btn btn-primary" onclick="printReceipt()">
                        <i class="ti ti-printer me-2"></i>Print Receipt
                    </button>
                    <button type="button" class="btn btn-outline-success" onclick="sendReceiptSMS()">
                        <i class="ti ti-message me-2"></i>Send SMS Receipt
                    </button>
                    <button type="button" class="btn btn-outline-info" onclick="sendReceiptEmail()">
                        <i class="ti ti-mail me-2"></i>Email Receipt
                    </button>
                    <hr class="my-2">
                    <div class="d-flex gap-2">
                        <a href="/finance/payments" class="btn btn-ghost-secondary flex-fill">
                            <i class="ti ti-list me-1"></i>View All Payments
                        </a>
                        <a href="/finance/payments/record" class="btn btn-success flex-fill">
                            <i class="ti ti-plus me-1"></i>New Payment
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('paymentForm');
    const paymentMethodSelect = document.getElementById('paymentMethod');
    const accountSearchInput = document.getElementById('accountSearch');
    const searchResults = document.getElementById('searchResults');

    // Dynamic field elements
    const referenceGroup = document.getElementById('referenceGroup');
    const referenceLabel = document.getElementById('referenceLabel');
    const referenceInput = document.getElementById('referenceNumber');
    const bankGroup = document.getElementById('bankGroup');
    const chequeDateGroup = document.getElementById('chequeDateGroup');
    const attachmentGroup = document.getElementById('attachmentGroup');

    // Payment method change handler - use both 'input' and 'change' for immediate response
    if (paymentMethodSelect) {
        paymentMethodSelect.addEventListener('change', updatePaymentMethodFields);
        paymentMethodSelect.addEventListener('input', updatePaymentMethodFields);
    }

    function updatePaymentMethodFields() {
        const option = paymentMethodSelect.options[paymentMethodSelect.selectedIndex];
        if (!option || !option.value) return; // Skip if no selection

        const requiresRef = option?.dataset?.requiresReference === '1';
        const refLabel = option?.dataset?.referenceLabel || 'Reference Number';
        const refPlaceholder = option?.dataset?.referencePlaceholder || '';
        const requiresBank = option?.dataset?.requiresBank === '1';
        const requiresChequeDate = option?.dataset?.requiresChequeDate === '1';
        const allowsAttachment = option?.dataset?.allowsAttachment === '1';

        // Reference field
        if (referenceGroup) {
            referenceGroup.style.display = requiresRef ? 'block' : 'none';
            if (referenceLabel) referenceLabel.textContent = refLabel;
            if (referenceInput) {
                referenceInput.required = requiresRef;
                referenceInput.placeholder = refPlaceholder;
            }
        }

        // Bank selection
        if (bankGroup) {
            bankGroup.style.display = requiresBank ? 'block' : 'none';
        }

        // Cheque date
        if (chequeDateGroup) {
            chequeDateGroup.style.display = requiresChequeDate ? 'block' : 'none';
        }

        // Attachment
        if (attachmentGroup) {
            attachmentGroup.style.display = allowsAttachment ? 'block' : 'none';
        }
    }

    // Account search functionality
    if (accountSearchInput) {
        let searchTimeout;
        accountSearchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();

            if (query.length < 2) {
                searchResults.style.display = 'none';
                return;
            }

            searchTimeout = setTimeout(() => {
                fetch(`/finance/api/search-accounts?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.accounts && data.accounts.length > 0) {
                            renderSearchResults(data.accounts);
                        } else {
                            searchResults.innerHTML = '<div class="list-group-item text-muted">No accounts found</div>';
                            searchResults.style.display = 'block';
                        }
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                    });
            }, 300);
        });

        // Hide results when clicking outside
        document.addEventListener('click', function(e) {
            if (!accountSearchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });
    }

    function renderSearchResults(accounts) {
        searchResults.innerHTML = accounts.map(acc => `
            <a href="#" class="list-group-item list-group-item-action"
               onclick="selectAccount(${acc.id}, '${escapeHtml(acc.name)}', '${escapeHtml(acc.ref)}', '${escapeHtml(acc.account_number)}', ${acc.balance}); return false;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${escapeHtml(acc.name)}</strong>
                        <div class="text-muted small">${escapeHtml(acc.ref)} | ${escapeHtml(acc.account_number)}</div>
                    </div>
                    <div class="text-end">
                        <span class="${acc.balance > 0 ? 'text-danger' : 'text-success'}">
                            KES ${formatNumber(acc.balance)}
                        </span>
                    </div>
                </div>
            </a>
        `).join('');
        searchResults.style.display = 'block';
    }

    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Processing...';

        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success modal
                document.getElementById('successReceiptNumber').textContent = data.receipt_number;
                document.getElementById('successAmount').textContent = 'KES ' + formatNumber(data.amount);
                window.lastPaymentId = data.payment_id;

                const modal = new bootstrap.Modal(document.getElementById('paymentSuccessModal'));
                modal.show();
            } else {
                alert('Error: ' + (data.message || 'Failed to record payment'));
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="ti ti-check me-1"></i>Record Payment';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="ti ti-check me-1"></i>Record Payment';
        });
    });
});

// Account selection functions
window.selectAccount = function(id, name, ref, accountNumber, balance) {
    document.getElementById('selectedAccountId').value = id;
    document.getElementById('selectedAccountName').textContent = name;
    document.getElementById('selectedAccountRef').textContent = ref + ' | ' + accountNumber;
    document.getElementById('selectedAccountBalance').textContent = 'KES ' + formatNumber(balance);

    document.getElementById('accountSearch').style.display = 'none';
    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('selectedAccountCard').style.display = 'block';

    // Pre-fill amount with balance if positive
    if (balance > 0) {
        document.getElementById('paymentAmount').value = balance.toFixed(2);
        document.getElementById('paymentAmount').max = balance;
    }
};

window.clearAccountSelection = function() {
    document.getElementById('selectedAccountId').value = '';
    document.getElementById('accountSearch').value = '';
    document.getElementById('accountSearch').style.display = 'block';
    document.getElementById('selectedAccountCard').style.display = 'none';
    document.getElementById('paymentAmount').value = '';
    document.getElementById('paymentAmount').removeAttribute('max');
};

// Helper functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

function formatNumber(num) {
    return parseFloat(num || 0).toLocaleString('en-KE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

// Post-payment actions
function printReceipt() {
    if (window.lastPaymentId) {
        window.open('/finance/payments/' + window.lastPaymentId + '/receipt', '_blank');
    }
}

function sendReceiptSMS() {
    if (window.lastPaymentId) {
        fetch('/finance/payments/' + window.lastPaymentId + '/send-sms', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            alert(data.success ? 'SMS receipt sent successfully!' : 'Failed: ' + data.message);
        })
        .catch(() => alert('Error sending SMS'));
    }
}

function sendReceiptEmail() {
    if (window.lastPaymentId) {
        fetch('/finance/payments/' + window.lastPaymentId + '/send-email', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            alert(data.success ? 'Email receipt sent successfully!' : 'Failed: ' + data.message);
        })
        .catch(() => alert('Error sending email'));
    }
}
</script>

<style>
.form-label.required::after {
    content: " *";
    color: #dc3545;
}
#searchResults {
    position: absolute;
    z-index: 1000;
    width: calc(100% - 2rem);
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
}
</style>
