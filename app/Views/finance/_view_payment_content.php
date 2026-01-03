<?php
/**
 * View Payment/Receipt Content
 * Displays payment details with print-friendly receipt
 */

// Determine who the payment is for
$isApplicant = !empty($payment['applicant_id']);
$personName = $isApplicant
    ? trim($payment['applicant_first_name'] . ' ' . $payment['applicant_last_name'])
    : trim($payment['student_first_name'] . ' ' . $payment['student_last_name']);
$personRef = $isApplicant
    ? $payment['application_ref']
    : $payment['admission_number'];

// Status badge colors
$statusColors = [
    'pending' => 'warning',
    'confirmed' => 'success',
    'bounced' => 'danger',
    'refunded' => 'purple'
];
$statusColor = $statusColors[$payment['status']] ?? 'secondary';

// Auto print if requested
$autoPrint = isset($_GET['print']);
?>

<!-- Page Header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <a href="javascript:history.back()" class="btn btn-ghost-secondary btn-sm mb-2">
                    <i class="ti ti-arrow-left me-1"></i>Back
                </a>
                <h2 class="page-title">
                    <i class="ti ti-receipt me-2"></i><?= e($payment['receipt_number']) ?>
                </h2>
                <div class="text-muted mt-1">
                    Payment Receipt
                    <span class="badge bg-<?= $statusColor ?>-lt ms-2"><?= ucfirst($payment['status']) ?></span>
                </div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                        <i class="ti ti-printer me-1"></i>Print
                    </button>
                    <button type="button" class="btn btn-outline-success" onclick="sendSMS(<?= $payment['id'] ?>)">
                        <i class="ti ti-message me-1"></i>SMS
                    </button>
                    <button type="button" class="btn btn-outline-info" onclick="sendEmail(<?= $payment['id'] ?>)">
                        <i class="ti ti-mail me-1"></i>Email
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Receipt Card -->
                <div class="card" id="printable-receipt">
                    <div class="card-body p-4">
                        <!-- Header -->
                        <div class="row mb-4">
                            <div class="col-6">
                                <h3 class="mb-1"><?= e($school['school_name'] ?? 'School Name') ?></h3>
                                <?php if (!empty($school['address'])): ?>
                                <div class="text-muted small"><?= nl2br(e($school['address'])) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($school['phone'])): ?>
                                <div class="text-muted small"><i class="ti ti-phone me-1"></i><?= e($school['phone']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($school['email'])): ?>
                                <div class="text-muted small"><i class="ti ti-mail me-1"></i><?= e($school['email']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-6 text-end">
                                <h1 class="display-6 text-success mb-0">RECEIPT</h1>
                                <div class="h2 mb-0"><?= e($payment['receipt_number']) ?></div>
                                <div class="text-muted mt-2">
                                    <div><strong>Date:</strong> <?= date('F j, Y', strtotime($payment['payment_date'])) ?></div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Received From -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5 class="text-muted mb-2">Received From</h5>
                                <div class="fw-bold fs-4"><?= e($personName) ?></div>
                                <div class="text-muted">
                                    <?php if ($isApplicant): ?>
                                    <div><strong>Application Ref:</strong> <?= e($personRef) ?></div>
                                    <?php else: ?>
                                    <div><strong>Admission No:</strong> <?= e($personRef) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($payment['fee_account_number'])): ?>
                                    <div><strong>Account:</strong> <?= e($payment['fee_account_number']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <h5 class="text-muted mb-2">Payment Details</h5>
                                <div>
                                    <strong>Method:</strong>
                                    <?php if ($payment['payment_method_icon']): ?>
                                    <i class="ti <?= e($payment['payment_method_icon']) ?> me-1"></i>
                                    <?php endif; ?>
                                    <?= e($payment['payment_method_name'] ?? ucfirst($payment['payment_method'])) ?>
                                </div>
                                <?php if (!empty($payment['reference_number'])): ?>
                                <div><strong>Reference:</strong> <?= e($payment['reference_number']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($payment['payer_bank_name'])): ?>
                                <div><strong>Bank:</strong> <?= e($payment['payer_bank_name']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($payment['cheque_date'])): ?>
                                <div><strong>Cheque Date:</strong> <?= date('M j, Y', strtotime($payment['cheque_date'])) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Amount Box -->
                        <div class="bg-success-lt rounded-3 p-4 text-center mb-4">
                            <div class="text-muted mb-1">Amount Received</div>
                            <div class="display-5 fw-bold text-success">
                                KES <?= number_format($payment['amount'], 2) ?>
                            </div>
                            <div class="text-muted small mt-2">
                                <?= ucfirst(numberToWords($payment['amount'])) ?> Kenya Shillings Only
                            </div>
                        </div>

                        <!-- Deposited To (if applicable) -->
                        <?php if (!empty($payment['school_account_name'])): ?>
                        <div class="mb-4">
                            <h5 class="text-muted mb-2">Deposited To</h5>
                            <div class="border rounded p-3">
                                <div class="fw-bold"><?= e($payment['school_bank_name']) ?></div>
                                <div class="text-muted">
                                    <?= e($payment['school_account_name']) ?> - <?= e($payment['school_account_number']) ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Notes -->
                        <?php if (!empty($payment['notes'])): ?>
                        <div class="mb-4">
                            <h5 class="text-muted">Notes</h5>
                            <p class="mb-0"><?= nl2br(e($payment['notes'])) ?></p>
                        </div>
                        <?php endif; ?>

                        <!-- Status Banner for Pending Cheques -->
                        <?php if ($payment['status'] === 'pending'): ?>
                        <div class="alert alert-warning mb-4">
                            <i class="ti ti-alert-triangle me-2"></i>
                            <strong>Pending Confirmation:</strong> This payment is awaiting clearance.
                        </div>
                        <?php endif; ?>

                        <!-- Signature Line (print only) -->
                        <div class="d-none d-print-block mt-5 pt-4">
                            <div class="row">
                                <div class="col-6">
                                    <div class="border-bottom" style="height: 40px;"></div>
                                    <div class="text-muted small mt-1">Received By</div>
                                </div>
                                <div class="col-6">
                                    <div class="border-bottom" style="height: 40px;"></div>
                                    <div class="text-muted small mt-1">Official Stamp</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="card-footer text-muted d-print-none">
                        <div class="row">
                            <div class="col">
                                <small>
                                    Recorded: <?= date('M j, Y g:i A', strtotime($payment['created_at'])) ?>
                                    <?php if (!empty($payment['received_by_name'])): ?>
                                    by <?= e($payment['received_by_name']) ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                            <div class="col-auto">
                                <small>Payment ID: <?= $payment['id'] ?></small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attachment Preview -->
                <?php if (!empty($payment['attachment_path'])): ?>
                <div class="card mt-3 d-print-none">
                    <div class="card-header">
                        <h3 class="card-title"><i class="ti ti-paperclip me-2"></i>Attachment</h3>
                    </div>
                    <div class="card-body text-center">
                        <?php
                        $ext = strtolower(pathinfo($payment['attachment_path'], PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                        <img src="<?= e($payment['attachment_path']) ?>" alt="Payment Attachment" class="img-fluid rounded" style="max-height: 400px;">
                        <?php else: ?>
                        <a href="<?= e($payment['attachment_path']) ?>" target="_blank" class="btn btn-outline-primary">
                            <i class="ti ti-file me-2"></i>View Attachment
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function sendSMS(paymentId) {
    if (confirm('Send SMS receipt to the registered phone number?')) {
        fetch('/finance/payments/' + paymentId + '/send-sms', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            alert(data.success ? 'SMS sent successfully!' : 'Failed: ' + data.message);
        })
        .catch(() => alert('Error sending SMS.'));
    }
}

function sendEmail(paymentId) {
    if (confirm('Send email receipt to the registered email address?')) {
        fetch('/finance/payments/' + paymentId + '/send-email', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            alert(data.success ? 'Email sent successfully!' : 'Failed: ' + data.message);
        })
        .catch(() => alert('Error sending email.'));
    }
}

<?php if ($autoPrint): ?>
window.onload = function() {
    setTimeout(function() {
        window.print();
    }, 500);
};
<?php endif; ?>
</script>

<style>
@media print {
    .page-header,
    .navbar,
    .footer,
    .d-print-none {
        display: none !important;
    }
    .page-body {
        padding: 0 !important;
        margin: 0 !important;
    }
    .container-xl {
        max-width: 100% !important;
        padding: 0 !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    body {
        background: white !important;
    }
    #printable-receipt {
        margin: 0 !important;
    }
}
</style>

<?php
/**
 * Convert number to words (simple implementation for amounts)
 */
function numberToWords($num) {
    $num = number_format($num, 2, '.', '');
    $parts = explode('.', $num);
    $whole = intval($parts[0]);
    $cents = intval($parts[1]);

    $ones = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine',
             'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen',
             'seventeen', 'eighteen', 'nineteen'];
    $tens = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];

    $words = '';

    if ($whole == 0) {
        $words = 'zero';
    } else {
        if ($whole >= 1000000) {
            $millions = floor($whole / 1000000);
            $words .= numberToWords($millions) . ' million ';
            $whole %= 1000000;
        }
        if ($whole >= 1000) {
            $thousands = floor($whole / 1000);
            if ($thousands < 20) {
                $words .= $ones[$thousands] . ' thousand ';
            } else {
                $words .= $tens[floor($thousands / 10)] . ' ' . $ones[$thousands % 10] . ' thousand ';
            }
            $whole %= 1000;
        }
        if ($whole >= 100) {
            $words .= $ones[floor($whole / 100)] . ' hundred ';
            $whole %= 100;
        }
        if ($whole >= 20) {
            $words .= $tens[floor($whole / 10)] . ' ';
            $whole %= 10;
        }
        if ($whole > 0 && $whole < 20) {
            $words .= $ones[$whole] . ' ';
        }
    }

    if ($cents > 0) {
        $words .= 'and ' . $cents . '/100 ';
    }

    return trim($words);
}
?>
