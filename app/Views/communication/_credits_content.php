<div class="page-wrapper">
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">Communication Credits</h2>
                    <div class="text-muted mt-1">Manage your SMS, WhatsApp, and Email credits</div>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <button type="button" class="btn btn-primary" onclick="openPurchaseModal()">
                        <i class="ti ti-plus me-1"></i>
                        Purchase Credits
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <!-- Credit Balance Cards -->
            <div class="row row-cards mb-3">
                <!-- SMS Credits -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="subheader">SMS Credits</div>
                            </div>
                            <div class="h1 mb-3"><?= number_format($balances['sms_credits'] ?? 0, 0) ?></div>
                            <div class="d-flex mb-2">
                                <div>Available balance</div>
                            </div>
                            <div class="progress progress-sm">
                                <div class="progress-bar bg-primary" style="width: <?= min(100, ($balances['sms_credits'] ?? 0) / 10) ?>%" role="progressbar"></div>
                            </div>
                            <div class="text-muted mt-2">
                                <i class="ti ti-info-circle"></i>
                                <?= $pricing['sms']['currency'] ?> <?= $pricing['sms']['price'] ?> <?= $pricing['sms']['unit'] ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- WhatsApp Credits -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="subheader">WhatsApp Credits</div>
                            </div>
                            <div class="h1 mb-3"><?= number_format($balances['whatsapp_credits'] ?? 0, 0) ?></div>
                            <div class="d-flex mb-2">
                                <div>Available balance</div>
                            </div>
                            <div class="progress progress-sm">
                                <div class="progress-bar bg-success" style="width: <?= min(100, ($balances['whatsapp_credits'] ?? 0) / 10) ?>%" role="progressbar"></div>
                            </div>
                            <div class="text-muted mt-2">
                                <i class="ti ti-info-circle"></i>
                                <?= $pricing['whatsapp']['currency'] ?> <?= $pricing['whatsapp']['price'] ?> <?= $pricing['whatsapp']['unit'] ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Email Credits -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="subheader">Email Credits</div>
                            </div>
                            <div class="h1 mb-3"><?= number_format($balances['email_credits'] ?? 0, 0) ?></div>
                            <div class="d-flex mb-2">
                                <div>Available balance</div>
                            </div>
                            <div class="progress progress-sm">
                                <div class="progress-bar bg-info" style="width: <?= min(100, ($balances['email_credits'] ?? 0) / 10) ?>%" role="progressbar"></div>
                            </div>
                            <div class="text-muted mt-2">
                                <i class="ti ti-info-circle"></i>
                                <?= $pricing['email']['currency'] ?> <?= $pricing['email']['price'] ?> <?= $pricing['email']['unit'] ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transaction History -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Transaction History</h3>
                    <div class="ms-auto">
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="transaction-filter" id="filter-all" autocomplete="off" checked onchange="filterTransactions('all')">
                            <label for="filter-all" class="btn btn-sm">All</label>

                            <input type="radio" class="btn-check" name="transaction-filter" id="filter-purchase" autocomplete="off" onchange="filterTransactions('purchase')">
                            <label for="filter-purchase" class="btn btn-sm">Purchases</label>

                            <input type="radio" class="btn-check" name="transaction-filter" id="filter-usage" autocomplete="off" onchange="filterTransactions('usage')">
                            <label for="filter-usage" class="btn btn-sm">Usage</label>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Transaction</th>
                                <th>Credits</th>
                                <th>Balance</th>
                                <th>Reference</th>
                                <th>User</th>
                            </tr>
                        </thead>
                        <tbody id="transactionTableBody">
                            <?php if (empty($transactions)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="ti ti-inbox icon mb-2" style="font-size: 3rem;"></i>
                                        <div>No transactions yet</div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $txn): ?>
                                    <tr class="transaction-row" data-type="<?= e($txn['transaction_type']) ?>">
                                        <td>
                                            <div><?= date('M d, Y', strtotime($txn['created_at'])) ?></div>
                                            <div class="text-muted small"><?= date('h:i A', strtotime($txn['created_at'])) ?></div>
                                        </td>
                                        <td>
                                            <?php
                                            $typeColors = [
                                                'sms' => 'primary',
                                                'whatsapp' => 'success',
                                                'email' => 'info'
                                            ];
                                            $color = $typeColors[$txn['credit_type']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $color ?>-lt"><?= strtoupper($txn['credit_type']) ?></span>
                                        </td>
                                        <td>
                                            <?php
                                            $txnTypes = [
                                                'purchase' => ['icon' => 'ti-shopping-cart', 'color' => 'success', 'label' => 'Purchase'],
                                                'usage' => ['icon' => 'ti-send', 'color' => 'primary', 'label' => 'Usage'],
                                                'refund' => ['icon' => 'ti-receipt-refund', 'color' => 'warning', 'label' => 'Refund'],
                                                'adjustment' => ['icon' => 'ti-adjustments', 'color' => 'info', 'label' => 'Adjustment']
                                            ];
                                            $type = $txnTypes[$txn['transaction_type']] ?? $txnTypes['usage'];
                                            ?>
                                            <div class="d-flex align-items-center">
                                                <span class="avatar avatar-sm me-2 bg-<?= $type['color'] ?>-lt">
                                                    <i class="ti <?= $type['icon'] ?>"></i>
                                                </span>
                                                <div>
                                                    <div><?= $type['label'] ?></div>
                                                    <?php if ($txn['broadcast_title']): ?>
                                                        <div class="text-muted small"><?= e($txn['broadcast_title']) ?></div>
                                                    <?php elseif ($txn['description']): ?>
                                                        <div class="text-muted small"><?= e($txn['description']) ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($txn['amount'] > 0): ?>
                                                <span class="text-success">+<?= number_format($txn['amount'], 0) ?></span>
                                            <?php else: ?>
                                                <span class="text-danger"><?= number_format($txn['amount'], 0) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="text-muted">
                                                <?= number_format($txn['balance_before'], 0) ?> → <?= number_format($txn['balance_after'], 0) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($txn['purchase_reference']): ?>
                                                <code class="small"><?= e($txn['purchase_reference']) ?></code>
                                                <?php if ($txn['purchase_amount']): ?>
                                                    <div class="text-muted small">KES <?= number_format($txn['purchase_amount'], 2) ?></div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="text-muted small"><?= e($txn['created_by_name'] ?? 'System') ?></div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Purchase Credits Modal -->
<div class="modal modal-blur fade" id="purchaseCreditsModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form id="purchaseCreditsForm" onsubmit="return handlePurchaseSubmit(event)">
                <div class="modal-header">
                    <h5 class="modal-title">Purchase Credits</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Credit Type</label>
                        <select class="form-select" name="credit_type" id="creditType" required onchange="updatePurchaseSummary()">
                            <option value="">Select type...</option>
                            <option value="sms">SMS (<?= $pricing['sms']['currency'] ?> <?= $pricing['sms']['price'] ?> <?= $pricing['sms']['unit'] ?>)</option>
                            <option value="whatsapp">WhatsApp (<?= $pricing['whatsapp']['currency'] ?> <?= $pricing['whatsapp']['price'] ?> <?= $pricing['whatsapp']['unit'] ?>)</option>
                            <option value="email">Email (<?= $pricing['email']['currency'] ?> <?= $pricing['email']['price'] ?> <?= $pricing['email']['unit'] ?>)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">Quantity</label>
                        <input type="number" class="form-control" name="quantity" id="quantity" min="1" step="1" required onchange="updatePurchaseSummary()" placeholder="Enter quantity">
                        <small class="form-hint">Number of credits to purchase</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Payment Method</label>
                        <select class="form-select" name="payment_method">
                            <option value="manual">Manual/Bank Transfer</option>
                            <option value="mpesa">M-Pesa</option>
                            <option value="card">Credit/Debit Card</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reference Number</label>
                        <input type="text" class="form-control" name="reference" placeholder="Optional payment reference">
                    </div>

                    <!-- Purchase Summary -->
                    <div class="alert alert-info" id="purchaseSummary" style="display: none;">
                        <h4 class="alert-title">Purchase Summary</h4>
                        <div class="text-muted" id="summaryDetails"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="purchaseSubmitBtn">
                        <i class="ti ti-check me-1"></i>
                        Purchase Credits
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const pricing = <?= json_encode($pricing) ?>;

function openPurchaseModal() {
    const modalElement = document.getElementById('purchaseCreditsModal');
    if (modalElement && typeof bootstrap !== 'undefined') {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }
}

function updatePurchaseSummary() {
    const creditType = document.getElementById('creditType').value;
    const quantity = parseInt(document.getElementById('quantity').value) || 0;
    const summaryDiv = document.getElementById('purchaseSummary');
    const detailsDiv = document.getElementById('summaryDetails');

    if (creditType && quantity > 0) {
        const unitPrice = pricing[creditType].price;
        const total = quantity * unitPrice;
        const currency = pricing[creditType].currency;

        detailsDiv.innerHTML = `
            <div><strong>Credit Type:</strong> ${creditType.toUpperCase()}</div>
            <div><strong>Quantity:</strong> ${quantity.toLocaleString()} credits</div>
            <div><strong>Unit Price:</strong> ${currency} ${unitPrice.toFixed(2)}</div>
            <div class="mt-2"><strong>Total Amount:</strong> <span class="h3">${currency} ${total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span></div>
        `;
        summaryDiv.style.display = 'block';
    } else {
        summaryDiv.style.display = 'none';
    }
}

function handlePurchaseSubmit(e) {
    e.preventDefault();

    const form = document.getElementById('purchaseCreditsForm');
    const formData = new FormData(form);
    const submitBtn = document.getElementById('purchaseSubmitBtn');

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Processing...';

    fetch('/communication/credits/purchase', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', data.message);

            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('purchaseCreditsModal'));
            if (modal) {
                modal.hide();
            }

            // Reload page after short delay
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast('error', data.message || 'Failed to purchase credits');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="ti ti-check me-1"></i>Purchase Credits';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'An error occurred while processing your purchase');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="ti ti-check me-1"></i>Purchase Credits';
    });

    return false;
}

function filterTransactions(type) {
    const rows = document.querySelectorAll('.transaction-row');
    rows.forEach(row => {
        if (type === 'all' || row.dataset.type === type) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function showToast(type, message) {
    const toast = document.createElement('div');
    toast.className = 'alert alert-' + (type === 'success' ? 'success' : 'danger') + ' position-fixed';
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 250px;';
    toast.innerHTML = '<i class="ti ti-' + (type === 'success' ? 'check' : 'alert-circle') + ' me-2"></i>' + message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}
</script>
