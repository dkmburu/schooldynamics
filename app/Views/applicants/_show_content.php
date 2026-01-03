<?php

/**
 * Applicant Profile Content with Tabs
 */

// Load payment configuration from database for the payment modal
$paymentMethods = [];
$banks = [];
$schoolBankAccounts = [];

try {
    $pdo = Database::getTenantConnection();

    // Get active payment methods
    $stmt = $pdo->query("SELECT * FROM payment_methods WHERE is_active = 1 ORDER BY sort_order");
    $paymentMethods = $stmt->fetchAll();

    // Get banks for cheque/transfer payments
    $stmt = $pdo->query("SELECT * FROM banks WHERE is_active = 1 ORDER BY bank_name");
    $banks = $stmt->fetchAll();

    // Get school bank accounts with bank name
    $stmt = $pdo->query("
        SELECT ba.*, b.bank_name
        FROM bank_accounts ba
        LEFT JOIN banks b ON b.id = ba.bank_id
        WHERE ba.is_active = 1
        ORDER BY ba.is_default DESC, ba.account_name
    ");
    $schoolBankAccounts = $stmt->fetchAll();
} catch (Exception $e) {
    // Use defaults if tables don't exist
    $paymentMethods = [
        ['id' => 1, 'code' => 'cash', 'name' => 'Cash', 'icon' => 'ti-cash', 'requires_reference' => 0, 'reference_label' => null, 'requires_bank' => 0, 'requires_cheque_date' => 0, 'allow_attachment' => 0],
        ['id' => 2, 'code' => 'mpesa', 'name' => 'M-Pesa', 'icon' => 'ti-device-mobile', 'requires_reference' => 1, 'reference_label' => 'M-Pesa Transaction Code', 'requires_bank' => 0, 'requires_cheque_date' => 0, 'allow_attachment' => 0],
        ['id' => 3, 'code' => 'bank_transfer', 'name' => 'Bank Transfer', 'icon' => 'ti-building-bank', 'requires_reference' => 1, 'reference_label' => 'Transfer Reference', 'requires_bank' => 1, 'requires_cheque_date' => 0, 'allow_attachment' => 1],
        ['id' => 4, 'code' => 'cheque', 'name' => 'Cheque', 'icon' => 'ti-file-check', 'requires_reference' => 1, 'reference_label' => 'Cheque Number', 'requires_bank' => 1, 'requires_cheque_date' => 1, 'allow_attachment' => 1],
    ];
}

// Convert payment methods to JSON for JavaScript
$paymentMethodsJson = json_encode($paymentMethods);

// Helper function to format status for timeline
function getStatusProgress($status)
{
    $allStatuses = [
        'draft' => 1,
        'submitted' => 2,
        'screening' => 3,
        'interview_scheduled' => 4,
        'interviewed' => 5,
        'exam_scheduled' => 6,
        'exam_taken' => 7,
        'accepted' => 8,
        'waitlisted' => 8,
        'rejected' => 8,
        'pre_admission' => 9,
        'admitted' => 10
    ];
    return $allStatuses[$status] ?? 0;
}

$statusProgress = getStatusProgress($applicant['status']);
$primaryContact = $contacts[0] ?? null;
?>

<!-- Profile Card with Tabs -->
<div class="card">
    <!-- Profile Header -->
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-auto">
                <?php $avatarName = urlencode($applicant['first_name'] . ' ' . $applicant['last_name']); ?>
                <span class="avatar avatar-xl rounded" style="background-image: url(https://ui-avatars.com/api/?name=<?= $avatarName ?>&size=128&background=0078d4&color=fff)"></span>
            </div>
            <div class="col">
                <h2 class="page-title mb-1"><?= e($applicant['first_name'] . ' ' . $applicant['last_name']) ?></h2>
                <div class="text-muted mb-2">
                    <span class="me-3"><i class="ti ti-id me-1"></i><?= e($applicant['application_ref']) ?></span>
                    <span class="me-3"><i class="ti ti-school me-1"></i><?= e($applicant['grade_name']) ?></span>
                    <span><i class="ti ti-calendar me-1"></i><?= e($applicant['campaign_name'] ?? 'N/A') ?></span>
                </div>
                <!-- Status Badge -->
                <?php
                $statusColors = [
                    'draft' => 'secondary',
                    'submitted' => 'primary',
                    'screening' => 'info',
                    'interview_scheduled' => 'warning',
                    'interviewed' => 'warning',
                    'exam_scheduled' => 'purple',
                    'exam_taken' => 'purple',
                    'accepted' => 'success',
                    'waitlisted' => 'warning',
                    'rejected' => 'danger',
                    'pre_admission' => 'info',
                    'admitted' => 'success'
                ];
                $statusColor = $statusColors[$applicant['status']] ?? 'secondary';
                ?>
                <span class="badge bg-<?= $statusColor ?>"><?= formatStatus($applicant['status']) ?></span>
            </div>
            <div class="col-auto">
                <!-- Data Consent Status -->
                <div class="d-flex align-items-center mb-2">
                    <span class="text-muted me-2">Data Consent:</span>
                    <?= AuthHelper::badge('applicant', $applicant['id'], 'data_consent') ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Bar -->
    <?php if (hasPermission('Students.write') || Gate::hasRole('ADMIN')): ?>
    <?php
    // Define flexible stages that can be skipped/jumped
    $flexibleStages = [
        'draft' => ['label' => 'Draft', 'icon' => 'ti-file', 'color' => 'secondary'],
        'submitted' => ['label' => 'Submitted', 'icon' => 'ti-send', 'color' => 'info'],
        'screening' => ['label' => 'Screening', 'icon' => 'ti-search', 'color' => 'primary'],
        'interview_scheduled' => ['label' => 'Interview Scheduled', 'icon' => 'ti-calendar', 'color' => 'warning'],
        'interviewed' => ['label' => 'Interviewed', 'icon' => 'ti-user-check', 'color' => 'info'],
        'exam_scheduled' => ['label' => 'Exam Scheduled', 'icon' => 'ti-writing', 'color' => 'warning'],
        'exam_taken' => ['label' => 'Exam Taken', 'icon' => 'ti-checklist', 'color' => 'info'],
    ];
    $decisionStages = [
        'accepted' => ['label' => 'Accept', 'icon' => 'ti-check', 'color' => 'success'],
        'waitlisted' => ['label' => 'Waitlist', 'icon' => 'ti-clock', 'color' => 'warning'],
        'rejected' => ['label' => 'Reject', 'icon' => 'ti-x', 'color' => 'danger'],
    ];
    // Strict stages (controlled by business rules)
    $strictStages = ['pre_admission', 'admitted', 'withdrawn'];
    $currentStatus = $applicant['status'];
    $isFlexibleStage = array_key_exists($currentStatus, $flexibleStages);
    $isDecisionStage = array_key_exists($currentStatus, $decisionStages);
    ?>
    <div class="card-body border-top border-bottom bg-light py-2">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <!-- Primary Actions Dropdown -->
            <div class="dropdown">
                <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="ti ti-settings me-1"></i> Actions
                </button>
                <div class="dropdown-menu">
                    <?php if ($isFlexibleStage || $isDecisionStage): ?>
                        <span class="dropdown-header">Quick Actions</span>

                        <?php if ($currentStatus === 'draft'): ?>
                            <a class="dropdown-item" href="/applicants/<?= $applicant['id'] ?>/edit">
                                <i class="ti ti-edit me-2"></i> Edit Application
                            </a>
                        <?php endif; ?>

                        <?php if (in_array($currentStatus, ['interview_scheduled'])): ?>
                            <a class="dropdown-item" href="#" onclick="showInterviewOutcomeModal(<?= $applicant['id'] ?>); return false;">
                                <i class="ti ti-circle-check me-2"></i> Record Interview Outcome
                            </a>
                            <a class="dropdown-item" href="#" onclick="showInterviewModal(<?= $applicant['id'] ?>, true); return false;">
                                <i class="ti ti-calendar-event me-2"></i> Reschedule Interview
                            </a>
                        <?php endif; ?>

                        <?php if (in_array($currentStatus, ['exam_scheduled'])): ?>
                            <a class="dropdown-item" href="#" onclick="showExamScoreModal(<?= $applicant['id'] ?>); return false;">
                                <i class="ti ti-circle-check me-2"></i> Record Exam Score
                            </a>
                            <a class="dropdown-item" href="#" onclick="showExamModal(<?= $applicant['id'] ?>, true); return false;">
                                <i class="ti ti-calendar-event me-2"></i> Reschedule Exam
                            </a>
                        <?php endif; ?>

                        <div class="dropdown-divider"></div>
                        <span class="dropdown-header">Move to Stage</span>

                        <?php
                        // Determine available stages based on current status
                        $availableStages = [];

                        if ($currentStatus === 'draft') {
                            $availableStages = ['submitted', 'screening', 'interview_scheduled', 'exam_scheduled', 'accepted', 'rejected', 'withdrawn'];
                        } elseif ($currentStatus === 'submitted') {
                            $availableStages = ['screening', 'interview_scheduled', 'exam_scheduled', 'accepted', 'rejected', 'withdrawn'];
                        } elseif ($currentStatus === 'screening') {
                            $availableStages = ['interview_scheduled', 'exam_scheduled', 'accepted', 'waitlisted', 'rejected', 'withdrawn'];
                        } elseif (in_array($currentStatus, ['interview_scheduled', 'interviewed'])) {
                            $availableStages = ['exam_scheduled', 'accepted', 'waitlisted', 'rejected', 'withdrawn'];
                        } elseif (in_array($currentStatus, ['exam_scheduled', 'exam_taken'])) {
                            $availableStages = ['accepted', 'waitlisted', 'rejected', 'withdrawn'];
                        } elseif (in_array($currentStatus, ['waitlisted', 'rejected'])) {
                            $availableStages = ['screening', 'interview_scheduled', 'exam_scheduled', 'accepted', 'withdrawn'];
                        }

                        foreach ($availableStages as $stage):
                            $stageInfo = $flexibleStages[$stage] ?? $decisionStages[$stage] ?? ['label' => ucfirst(str_replace('_', ' ', $stage)), 'icon' => 'ti-arrow-right', 'color' => 'secondary'];
                            // Special handling for stages that need modals
                            if ($stage === 'interview_scheduled'): ?>
                                <a class="dropdown-item" href="#" onclick="showInterviewModal(<?= $applicant['id'] ?>); return false;">
                                    <i class="ti ti-calendar me-2 text-<?= $stageInfo['color'] ?>"></i> Schedule Interview
                                </a>
                            <?php elseif ($stage === 'exam_scheduled'): ?>
                                <a class="dropdown-item" href="#" onclick="showExamModal(<?= $applicant['id'] ?>); return false;">
                                    <i class="ti ti-writing me-2 text-<?= $stageInfo['color'] ?>"></i> Schedule Exam
                                </a>
                            <?php elseif ($stage === 'accepted'): ?>
                                <a class="dropdown-item" href="#" onclick="showDecisionModal(<?= $applicant['id'] ?>, 'accepted'); return false;">
                                    <i class="ti <?= $stageInfo['icon'] ?> me-2 text-<?= $stageInfo['color'] ?>"></i> <?= $stageInfo['label'] ?>
                                </a>
                            <?php elseif ($stage === 'waitlisted'): ?>
                                <a class="dropdown-item" href="#" onclick="showDecisionModal(<?= $applicant['id'] ?>, 'waitlisted'); return false;">
                                    <i class="ti <?= $stageInfo['icon'] ?> me-2 text-<?= $stageInfo['color'] ?>"></i> <?= $stageInfo['label'] ?>
                                </a>
                            <?php elseif ($stage === 'rejected'): ?>
                                <a class="dropdown-item" href="#" onclick="showDecisionModal(<?= $applicant['id'] ?>, 'rejected'); return false;">
                                    <i class="ti <?= $stageInfo['icon'] ?> me-2 text-<?= $stageInfo['color'] ?>"></i> <?= $stageInfo['label'] ?>
                                </a>
                            <?php elseif ($stage === 'withdrawn'): ?>
                                <a class="dropdown-item" href="#" onclick="showDecisionModal(<?= $applicant['id'] ?>, 'withdrawn'); return false;">
                                    <i class="ti ti-door-exit me-2 text-secondary"></i> Withdraw Application
                                </a>
                            <?php else: ?>
                                <a class="dropdown-item" href="#" onclick="showStageTransitionModal(<?= $applicant['id'] ?>, '<?= $stage ?>'); return false;">
                                    <i class="ti <?= $stageInfo['icon'] ?> me-2 text-<?= $stageInfo['color'] ?>"></i> <?= $stageInfo['label'] ?>
                                </a>
                            <?php endif;
                        endforeach; ?>

                    <?php elseif ($currentStatus === 'accepted'): ?>
                        <span class="dropdown-header">Admission Process</span>
                        <a class="dropdown-item text-success" href="#" onclick="showPreAdmissionModal(<?= $applicant['id'] ?>); return false;">
                            <i class="ti ti-file-invoice me-2"></i> Generate Invoice & Proceed
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="#tab-history" onclick="bootstrap.Tab.getOrCreateInstance(document.querySelector('#tab-activity-tab')).show(); return false;">
                            <i class="ti ti-history me-2"></i> View Decision History
                        </a>

                    <?php elseif ($currentStatus === 'pre_admission'): ?>
                        <span class="dropdown-header">Payment & Admission</span>
                        <a class="dropdown-item text-success" href="#" onclick="showPaymentModal(<?= $applicant['id'] ?>); return false;">
                            <i class="ti ti-cash me-2"></i> Record Payment
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="#" onclick="viewInvoice(<?= $applicant['admission_invoice_id'] ?? 0 ?>); return false;">
                            <i class="ti ti-file-invoice me-2"></i> View Invoice
                        </a>
                        <a class="dropdown-item" href="#">
                            <i class="ti ti-printer me-2"></i> Print Invoice
                        </a>

                    <?php elseif ($currentStatus === 'admitted'): ?>
                        <span class="dropdown-header">Student Actions</span>
                        <a class="dropdown-item" href="/students/<?= $applicant['student_id'] ?? '' ?>">
                            <i class="ti ti-user me-2"></i> View Student Profile
                        </a>
                        <a class="dropdown-item" href="#tab-history" onclick="bootstrap.Tab.getOrCreateInstance(document.querySelector('#tab-activity-tab')).show(); return false;">
                            <i class="ti ti-history me-2"></i> View History
                        </a>

                    <?php elseif ($currentStatus === 'withdrawn'): ?>
                        <span class="dropdown-header">Application Withdrawn</span>
                        <a class="dropdown-item" href="#tab-history" onclick="bootstrap.Tab.getOrCreateInstance(document.querySelector('#tab-activity-tab')).show(); return false;">
                            <i class="ti ti-history me-2"></i> View History
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="#" onclick="showStageTransitionModal(<?= $applicant['id'] ?>, 'submitted'); return false;">
                            <i class="ti ti-refresh me-2"></i> Reactivate Application
                        </a>
                    <?php endif; ?>

                    <div class="dropdown-divider"></div>
                    <span class="dropdown-header">Other Actions</span>
                    <a class="dropdown-item" href="#">
                        <i class="ti ti-mail me-2"></i> Send Email
                    </a>
                    <a class="dropdown-item" href="#">
                        <i class="ti ti-message me-2"></i> Send SMS
                    </a>
                    <a class="dropdown-item" href="#">
                        <i class="ti ti-download me-2"></i> Download Documents
                    </a>
                </div>
            </div>

            <!-- Data Consent Dropdown -->
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="ti ti-shield-check me-1"></i> Data Consent
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="#" onclick="showAuthorizationModal(<?= $applicant['id'] ?>); return false;">
                        <i class="ti ti-send me-2"></i> Request Authorization
                    </a>
                    <a class="dropdown-item" href="#" onclick="showEnterCodeModal(<?= $applicant['id'] ?>); return false;">
                        <i class="ti ti-key me-2"></i> Enter Code
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="#" onclick="showAuthorizationHistoryModal(<?= $applicant['id'] ?>); return false;">
                        <i class="ti ti-history me-2"></i> Authorization History
                    </a>
                </div>
            </div>

            <!-- Quick Action Buttons -->
            <button type="button" class="btn btn-outline-secondary" onclick="showEditApplicantModal(<?= $applicant['id'] ?>, <?= htmlspecialchars(json_encode($applicant), ENT_QUOTES, 'UTF-8') ?>)">
                <i class="ti ti-edit me-1"></i> Edit
            </button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Application Progress -->
    <div class="card-body border-bottom">
        <h4 class="subheader mb-3">Application Progress</h4>
        <div class="row">
            <div class="col-12">
                <div class="progress progress-sm mb-2">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?= ($statusProgress / 10) * 100 ?>%"></div>
                </div>
                <div class="d-flex justify-content-between">
                    <small class="text-muted">Submitted</small>
                    <small class="text-muted">Screening</small>
                    <small class="text-muted">Interview</small>
                    <small class="text-muted">Exam</small>
                    <small class="text-muted">Decision</small>
                    <small class="text-muted">Admitted</small>
                </div>
            </div>
        </div>
    </div>

    <?php if (in_array($applicant['status'], ['pre_admission', 'admitted'])): ?>
    <!-- Admission Info Card -->
    <div class="card-body border-bottom bg-light">
        <?php
        // Fetch admission details
        $admissionInfo = null;
        if (!empty($applicant['student_fee_account_id'])) {
            try {
                $stmt = $pdo->prepare("
                    SELECT sfa.account_number, i.invoice_number, i.total_amount, i.amount_paid, i.balance, i.status as invoice_status
                    FROM student_fee_accounts sfa
                    LEFT JOIN invoices i ON i.id = ?
                    WHERE sfa.id = ?
                ");
                $stmt->execute([$applicant['admission_invoice_id'] ?? 0, $applicant['student_fee_account_id']]);
                $admissionInfo = $stmt->fetch();
            } catch (Exception $e) {}
        }
        ?>

        <div class="row align-items-center">
            <?php if ($applicant['status'] === 'admitted'): ?>
            <!-- Admitted - Show admission number prominently -->
            <div class="col-md-4 text-center border-end">
                <div class="text-muted small">Admission Number</div>
                <div class="h2 mb-0 text-success"><?= e($applicant['admission_number'] ?? 'N/A') ?></div>
                <div class="text-muted small">Admitted on <?= date('M j, Y', strtotime($applicant['admission_date'] ?? 'now')) ?></div>
            </div>
            <?php endif; ?>

            <?php if ($admissionInfo): ?>
            <div class="col-md-<?= $applicant['status'] === 'admitted' ? '3' : '4' ?> text-center <?= $applicant['status'] !== 'admitted' ? 'border-end' : '' ?>">
                <div class="text-muted small">Fee Account</div>
                <div class="h4 mb-0"><?= e($admissionInfo['account_number']) ?></div>
            </div>
            <div class="col-md-<?= $applicant['status'] === 'admitted' ? '2' : '3' ?> text-center">
                <div class="text-muted small">Invoice</div>
                <div class="h5 mb-0"><?= e($admissionInfo['invoice_number']) ?></div>
                <span class="badge bg-<?= $admissionInfo['invoice_status'] === 'paid' ? 'success' : ($admissionInfo['invoice_status'] === 'partial' ? 'warning' : 'danger') ?>-lt">
                    <?= ucfirst($admissionInfo['invoice_status']) ?>
                </span>
            </div>
            <div class="col-md-3 text-center">
                <div class="text-muted small"><?= $applicant['status'] === 'admitted' ? 'Total Paid' : 'Balance Due' ?></div>
                <?php if ($applicant['status'] === 'admitted'): ?>
                <div class="h4 mb-0 text-success">KES <?= number_format($admissionInfo['amount_paid'], 0) ?></div>
                <?php else: ?>
                <div class="h4 mb-0 text-danger">KES <?= number_format($admissionInfo['balance'], 0) ?></div>
                <small class="text-muted">of KES <?= number_format($admissionInfo['total_amount'], 0) ?></small>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($applicant['status'] === 'pre_admission'): ?>
            <div class="col-md-2 text-center">
                <button class="btn btn-success" onclick="showPaymentModal(<?= $applicant['id'] ?>)">
                    <i class="ti ti-cash me-1"></i> Pay Now
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tab Navigation -->
    <div class="card-header">
        <ul class="nav nav-tabs" id="applicant-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-overview-tab" data-bs-toggle="tab" data-bs-target="#tab-overview" type="button" role="tab" aria-controls="tab-overview" aria-selected="true">
                    <i class="ti ti-user me-2"></i>Overview
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-guardians-tab" data-bs-toggle="tab" data-bs-target="#tab-guardians" type="button" role="tab" aria-controls="tab-guardians" aria-selected="false">
                    <i class="ti ti-users me-2"></i>Guardians
                    <span class="badge bg-primary ms-2"><?= count($guardians) ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-documents-tab" data-bs-toggle="tab" data-bs-target="#tab-documents" type="button" role="tab" aria-controls="tab-documents" aria-selected="false">
                    <i class="ti ti-file-text me-2"></i>Documents
                    <span class="badge bg-primary ms-2"><?= count($documents) ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-siblings-tab" data-bs-toggle="tab" data-bs-target="#tab-siblings" type="button" role="tab" aria-controls="tab-siblings" aria-selected="false">
                    <i class="ti ti-friends me-2"></i>Siblings/Family
                    <span class="badge bg-primary ms-2"><?= count($siblings) ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-finances-tab" data-bs-toggle="tab" data-bs-target="#tab-finances" type="button" role="tab" aria-controls="tab-finances" aria-selected="false">
                    <i class="ti ti-currency-dollar me-2"></i>Finances
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-activity-tab" data-bs-toggle="tab" data-bs-target="#tab-activity" type="button" role="tab" aria-controls="tab-activity" aria-selected="false">
                    <i class="ti ti-history me-2"></i>Activity Log
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content" id="custom-tabs-content">
            <!-- Overview Tab -->
            <div class="tab-pane fade show active" id="tab-overview" role="tabpanel" aria-labelledby="tab-overview-tab">
                <?php require __DIR__ . '/_show_tab_overview.php'; ?>
            </div>

            <!-- Guardians Tab -->
            <div class="tab-pane fade" id="tab-guardians" role="tabpanel" aria-labelledby="tab-guardians-tab">
                <?php require __DIR__ . '/_show_tab_guardians.php'; ?>
            </div>

            <!-- Documents Tab -->
            <div class="tab-pane fade" id="tab-documents" role="tabpanel" aria-labelledby="tab-documents-tab">
                <?php require __DIR__ . '/_show_tab_documents.php'; ?>
            </div>

            <!-- Siblings/Family Tab -->
            <div class="tab-pane fade" id="tab-siblings" role="tabpanel" aria-labelledby="tab-siblings-tab">
                <?php require __DIR__ . '/_show_tab_siblings.php'; ?>
            </div>

            <!-- Finances Tab -->
            <div class="tab-pane fade" id="tab-finances" role="tabpanel" aria-labelledby="tab-finances-tab">
                <?php require __DIR__ . '/_show_tab_finances.php'; ?>
            </div>

            <!-- Activity Log Tab -->
            <div class="tab-pane fade" id="tab-activity" role="tabpanel" aria-labelledby="tab-activity-tab">
                <?php require __DIR__ . '/_show_tab_activity.php'; ?>
            </div>
        </div>
    </div>
</div>

<style>
    /* Subheader style */
    .subheader {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--sd-gray-500);
    }

    /* Action bar gap helper */
    .gap-2 {
        gap: 0.5rem;
    }

    /* Purple badge */
    .bg-purple {
        background-color: #6f42c1 !important;
    }

    .badge-purple {
        background-color: #6f42c1;
        color: white;
    }

    .badge-dot.badge-purple::before {
        background-color: #6f42c1;
    }
</style>

<!-- Include Stage Transition Modals -->
<?php require __DIR__ . '/_screening_modals.php'; ?>

<!-- Include Guardian Management Modals -->
<?php require __DIR__ . '/_guardian_modals.php'; ?>

<!-- Include Document Upload Modals -->
<?php require __DIR__ . '/_document_upload_modals.php'; ?>

<!-- Authorization Request Modal -->
<div class="modal fade" id="authorizationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="ti ti-file-certificate me-2"></i>Request Authorization
                    <?php if (!empty($applicant['school_name'])): ?>
                        <br><small><i class="ti ti-school me-1"></i><?= e($applicant['school_name']) ?><?php if (!empty($applicant['campus_name'])): ?> - <?= e($applicant['campus_name']) ?><?php endif; ?></small>
                    <?php endif; ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="authRequestForm">
                    <input type="hidden" name="applicant_id" id="auth_applicant_id">

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>About Authorization Requests</strong><br>
                        The guardian will receive a message from <strong><?= e($applicant['school_name'] ?? 'your school') ?><?php if (!empty($applicant['campus_name'])): ?> - <?= e($applicant['campus_name']) ?><?php endif; ?></strong> with a verification code and link to approve the authorization.
                        They can either click the link on a smartphone or call/visit to provide the code.
                    </div>

                    <div class="form-group">
                        <label for="auth_type">Authorization Type <span class="text-danger">*</span></label>
                        <select class="form-control" name="request_type" id="auth_type" required>
                            <option value="">-- Select Type --</option>
                            <option value="data_consent">Data Consent (Process & Store Student Data)</option>
                            <option value="photo_consent">Photo/Media Consent (Use in School Media)</option>
                            <option value="medical_consent">Medical Consent (Emergency Treatment)</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="recipient_name">Guardian Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="recipient_name" id="recipient_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="recipient_phone">Phone Number</label>
                                <input type="tel" class="form-control" name="recipient_phone" id="recipient_phone" placeholder="+1234567890">
                                <small class="text-muted">Include country code for SMS</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="recipient_email">Email Address</label>
                        <input type="email" class="form-control" name="recipient_email" id="recipient_email">
                    </div>

                    <div class="form-group">
                        <label>Send Via <span class="text-danger">*</span></label>
                        <div>
                            <div class="custom-control custom-checkbox custom-control-inline">
                                <input type="checkbox" class="custom-control-input" id="channel_sms" name="channels[]" value="sms" checked>
                                <label class="custom-control-label" for="channel_sms">
                                    <i class="fas fa-sms mr-1"></i> SMS
                                </label>
                            </div>
                            <div class="custom-control custom-checkbox custom-control-inline">
                                <input type="checkbox" class="custom-control-input" id="channel_email" name="channels[]" value="email">
                                <label class="custom-control-label" for="channel_email">
                                    <i class="fas fa-envelope mr-1"></i> Email
                                </label>
                            </div>
                        </div>
                        <small class="text-muted">Select at least one channel</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary" id="btnSendAuthorization" onclick="sendAuthorizationRequest()">
                    <i class="ti ti-send me-1"></i>Send Request
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Enter Authorization Code Modal (Staff-Assisted) -->
<div class="modal fade" id="enterCodeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="ti ti-key me-2"></i>Enter Authorization Code
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="enterCodeForm">
                    <input type="hidden" name="applicant_id" id="code_applicant_id">

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Staff-Assisted Authorization</strong><br>
                        Use this when a guardian calls or visits with their verification code.
                    </div>

                    <div class="form-group">
                        <label for="verification_code_input">Verification Code (6 digits) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-lg text-center"
                               name="verification_code" id="verification_code_input"
                               placeholder="000000" maxlength="6" pattern="[0-9]{6}" required
                               style="font-size: 1.5rem; letter-spacing: 0.5rem;">
                    </div>

                    <div class="form-group">
                        <label for="contact_method">How did guardian contact?</label>
                        <select class="form-control" name="contact_method" id="contact_method">
                            <option value="phone_call">Phone Call</option>
                            <option value="in_person">In Person (Office Visit)</option>
                            <option value="whatsapp">WhatsApp Message</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="staff_notes">Notes (optional)</label>
                        <textarea class="form-control" name="staff_notes" id="staff_notes" rows="2"
                                  placeholder="Any additional notes about this authorization..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-success" id="btnSubmitCode" onclick="submitAuthorizationCode()">
                    <i class="ti ti-check me-1"></i>Submit Code
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Authorization History Modal -->
<div class="modal fade" id="authHistoryModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="ti ti-history me-2"></i>Authorization History
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="authHistoryContent">
                    <div class="text-center py-5">
                        <i class="fas fa-spinner fa-spin fa-3x text-muted"></i>
                        <p class="mt-3 text-muted">Loading authorization history...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Applicant Modal -->
<div class="modal fade" id="editApplicantModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="ti ti-edit me-2"></i>Edit Applicant Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editApplicantForm">
                    <input type="hidden" name="applicant_id" id="edit_applicant_id">

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_first_name">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="first_name" id="edit_first_name" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_middle_name">Middle Name</label>
                                <input type="text" class="form-control" name="middle_name" id="edit_middle_name">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_last_name">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="last_name" id="edit_last_name" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_date_of_birth">Date of Birth</label>
                                <input type="date" class="form-control" name="date_of_birth" id="edit_date_of_birth">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_gender">Gender</label>
                                <select class="form-control" name="gender" id="edit_gender">
                                    <option value="">-- Select --</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_nationality">Nationality</label>
                                <select class="form-control" name="nationality" id="edit_nationality">
                                    <option value="">-- Select Country --</option>
                                    <?php
                                    $pdo = Database::getTenantConnection();
                                    $stmt = $pdo->query("SELECT country_name FROM countries WHERE is_active = 1 ORDER BY sort_order, country_name");
                                    $countries = $stmt->fetchAll();
                                    foreach ($countries as $country):
                                    ?>
                                    <option value="<?= e($country['country_name']) ?>"><?= e($country['country_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_birth_cert_no">Birth Certificate No</label>
                                <input type="text" class="form-control" name="birth_cert_no" id="edit_birth_cert_no">
                            </div>
                        </div>
                    </div>

                    <hr class="my-3">
                    <h6 class="text-muted mb-3"><i class="ti ti-school me-2"></i>Previous School Information</h6>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="edit_previous_school">Previous School</label>
                                <input type="text" class="form-control" name="previous_school" id="edit_previous_school">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_previous_grade">Previous Grade</label>
                                <input type="text" class="form-control" name="previous_grade" id="edit_previous_grade">
                            </div>
                        </div>
                    </div>

                    <hr class="my-3">
                    <h6 class="text-muted mb-3"><i class="ti ti-certificate me-2"></i>Application Details</h6>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_grade_applying_for_id">Grade Applying For <span class="text-danger">*</span></label>
                                <select class="form-control" name="grade_applying_for_id" id="edit_grade_applying_for_id" required>
                                    <option value="">-- Select Grade --</option>
                                    <?php
                                    $pdo = Database::getTenantConnection();
                                    $stmt = $pdo->query("SELECT id, grade_name, grade_category FROM grades WHERE is_active = 1 ORDER BY sort_order");
                                    $grades = $stmt->fetchAll();
                                    foreach ($grades as $grade):
                                    ?>
                                    <option value="<?= $grade['id'] ?>"><?= e($grade['grade_name']) ?> (<?= e($grade['grade_category']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_intake_campaign_id">Intake Campaign</label>
                                <select class="form-control" name="intake_campaign_id" id="edit_intake_campaign_id">
                                    <option value="">-- Select Campaign --</option>
                                    <?php
                                    $stmt = $pdo->query("SELECT id, campaign_name FROM intake_campaigns WHERE status IN ('open', 'active') ORDER BY start_date DESC");
                                    $campaigns = $stmt->fetchAll();
                                    foreach ($campaigns as $campaign):
                                    ?>
                                    <option value="<?= $campaign['id'] ?>"><?= e($campaign['campaign_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <hr class="my-3">
                    <h6 class="text-muted mb-3"><i class="ti ti-stethoscope me-2"></i>Medical & Additional Information</h6>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_medical_conditions">Medical Conditions</label>
                                <textarea class="form-control" name="medical_conditions" id="edit_medical_conditions" rows="2" placeholder="Any medical conditions or allergies..."></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_special_needs">Special Needs</label>
                                <textarea class="form-control" name="special_needs" id="edit_special_needs" rows="2" placeholder="Any special needs or requirements..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="edit_notes">Notes</label>
                        <textarea class="form-control" name="notes" id="edit_notes" rows="3" placeholder="Additional notes about this applicant..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary" id="btnSaveApplicant" onclick="saveApplicantDetails()">
                    <i class="ti ti-device-floppy me-1"></i>Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Pre-Admission Modal -->
<div class="modal fade" id="preAdmissionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/applicants/initiate-pre-admission" id="preAdmissionForm">
                <input type="hidden" name="applicant_id" id="preAdmissionApplicantId">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="ti ti-file-invoice me-2"></i>Initiate Pre-Admission
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <div class="d-flex">
                            <div class="me-3">
                                <i class="ti ti-info-circle" style="font-size: 1.5rem;"></i>
                            </div>
                            <div>
                                <h4 class="alert-title">This action will:</h4>
                                <ul class="mb-0">
                                    <li>Create a student fee account</li>
                                    <li>Generate an admission invoice based on the grade's fee structure</li>
                                    <li>Move applicant to <strong>Pre-Admission</strong> status</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <?php
                    // Get admission fee structure preview
                    $feePreview = [];
                    $totalAmount = 0;
                    try {
                        $stmt = $pdo->prepare("
                            SELECT afs.total_amount, glg.group_name
                            FROM admission_fee_structures afs
                            JOIN grade_level_groups glg ON glg.id = afs.grade_level_group_id
                            JOIN grade_level_group_members glgm ON glgm.grade_level_group_id = glg.id
                            WHERE glgm.grade_id = ?
                            AND afs.academic_year_id = ?
                            AND afs.status = 'active'
                            LIMIT 1
                        ");
                        $stmt->execute([$applicant['grade_applying_for_id'], $applicant['academic_year_id']]);
                        $feePreview = $stmt->fetch();
                        if ($feePreview) {
                            $totalAmount = $feePreview['total_amount'];
                        }
                    } catch (Exception $e) {
                        // Ignore
                    }
                    ?>

                    <table class="table table-sm table-borderless mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted" style="width: 140px;">Applicant</td>
                                <td class="fw-bold"><?= e($applicant['first_name'] . ' ' . $applicant['last_name']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Grade</td>
                                <td><?= e($applicant['grade_name']) ?></td>
                            </tr>
                            <?php if ($feePreview): ?>
                            <tr>
                                <td class="text-muted">Fee Group</td>
                                <td><?= e($feePreview['group_name']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Admission Fees</td>
                                <td><span class="badge bg-success">KES <?= number_format($totalAmount, 2) ?></span></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <?php if (!$feePreview): ?>
                    <div class="alert alert-warning">
                        <i class="ti ti-alert-triangle me-2"></i>
                        No admission fee structure found for this grade. Please configure admission fees first.
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <?php if ($feePreview): ?>
                    <button type="submit" class="btn btn-success">
                        <i class="ti ti-check me-1"></i>Generate Invoice & Proceed
                    </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Record Payment Modal (Enhanced) -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- Payment Form View -->
            <div id="paymentFormView">
                <form id="paymentForm" enctype="multipart/form-data">
                    <input type="hidden" name="applicant_id" id="paymentApplicantId">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">
                            <i class="ti ti-cash me-2"></i>Record Admission Payment
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <?php if ($applicant['status'] === 'pre_admission' && isset($applicant['admission_invoice_id'])):
                            // Get invoice details
                            $invoiceDetails = null;
                            try {
                                $stmt = $pdo->prepare("
                                    SELECT i.*, sfa.account_number
                                    FROM invoices i
                                    JOIN student_fee_accounts sfa ON sfa.id = i.student_fee_account_id
                                    WHERE i.id = ?
                                ");
                                $stmt->execute([$applicant['admission_invoice_id']]);
                                $invoiceDetails = $stmt->fetch();
                            } catch (Exception $e) {}
                        ?>

                        <?php if ($invoiceDetails): ?>
                        <!-- Invoice Summary -->
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label text-muted small">Invoice Number</label>
                                <div class="fw-bold"><?= e($invoiceDetails['invoice_number']) ?></div>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted small">Account Number</label>
                                <div class="fw-bold"><?= e($invoiceDetails['account_number']) ?></div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-4">
                                <label class="form-label text-muted small">Total Amount</label>
                                <div class="fw-bold">KES <?= number_format($invoiceDetails['total_amount'], 2) ?></div>
                            </div>
                            <div class="col-4">
                                <label class="form-label text-muted small">Paid</label>
                                <div class="fw-bold text-success">KES <?= number_format($invoiceDetails['amount_paid'], 2) ?></div>
                            </div>
                            <div class="col-4">
                                <label class="form-label text-muted small">Balance</label>
                                <div class="fw-bold text-danger" id="invoiceBalance" data-balance="<?= $invoiceDetails['balance'] ?>">KES <?= number_format($invoiceDetails['balance'], 2) ?></div>
                            </div>
                        </div>

                        <hr>
                        <?php endif; ?>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-6">
                                <!-- Payment Amount -->
                                <div class="mb-3">
                                    <label class="form-label required">Payment Amount (KES)</label>
                                    <input type="number" name="amount" id="paymentAmount" class="form-control" required
                                           step="0.01" min="1" <?php if (isset($invoiceDetails)): ?>max="<?= $invoiceDetails['balance'] ?>" value="<?= $invoiceDetails['balance'] ?>"<?php endif; ?>>
                                    <?php if (isset($invoiceDetails)): ?>
                                    <small class="text-muted">Balance due: KES <?= number_format($invoiceDetails['balance'], 2) ?></small>
                                    <?php endif; ?>
                                </div>

                                <!-- Payment Method -->
                                <div class="mb-3">
                                    <label class="form-label required">Payment Method</label>
                                    <select name="payment_method_id" id="modalPaymentMethod" class="form-select" required>
                                        <option value="">Select Method...</option>
                                        <?php foreach ($paymentMethods as $method): ?>
                                        <option value="<?= $method['id'] ?>"
                                                data-code="<?= e($method['code']) ?>"
                                                data-requires-reference="<?= $method['requires_reference'] ?>"
                                                data-reference-label="<?= e($method['reference_label'] ?? 'Reference') ?>"
                                                data-requires-bank="<?= $method['requires_bank'] ?>"
                                                data-requires-cheque-date="<?= $method['requires_cheque_date'] ?>"
                                                data-allow-attachment="<?= $method['allows_attachment'] ?? $method['allow_attachment'] ?? 0 ?>">
                                            <i class="ti <?= e($method['icon']) ?>"></i> <?= e($method['name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Reference Number (dynamic) -->
                                <div class="mb-3" id="modalReferenceGroup" style="display: none;">
                                    <label class="form-label required" id="modalReferenceLabel">Reference Number</label>
                                    <input type="text" name="reference_number" id="modalReferenceNumber" class="form-control">
                                </div>

                                <!-- Bank Selection (for cheque/transfer) -->
                                <div class="mb-3" id="modalBankGroup" style="display: none;">
                                    <label class="form-label required">Payer's Bank</label>
                                    <select name="payer_bank_id" id="modalPayerBank" class="form-select">
                                        <option value="">Select Bank...</option>
                                        <?php foreach ($banks as $bank): ?>
                                        <option value="<?= $bank['id'] ?>"><?= e($bank['bank_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Cheque Date -->
                                <div class="mb-3" id="modalChequeDateGroup" style="display: none;">
                                    <label class="form-label required">Cheque Date</label>
                                    <input type="date" name="cheque_date" id="modalChequeDate" class="form-control">
                                    <small class="text-muted">Maturity date for post-dated cheques</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <!-- Payment Date -->
                                <div class="mb-3">
                                    <label class="form-label required">Payment Date</label>
                                    <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                </div>

                                <!-- School Bank Account -->
                                <?php if (!empty($schoolBankAccounts)): ?>
                                <div class="mb-3" id="modalSchoolAccountGroup">
                                    <label class="form-label required">Deposited To</label>
                                    <select name="school_bank_account_id" id="modalSchoolBankAccount" class="form-select" required>
                                        <option value="">Select School Account...</option>
                                        <?php foreach ($schoolBankAccounts as $acc): ?>
                                        <option value="<?= $acc['id'] ?>" <?= $acc['is_default'] ? 'selected' : '' ?>>
                                            <?= e($acc['bank_name']) ?> - <?= e($acc['account_number']) ?>
                                            <?= $acc['is_default'] ? '(Default)' : '' ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php endif; ?>

                                <!-- Attachment -->
                                <div class="mb-3" id="modalAttachmentGroup" style="display: none;">
                                    <label class="form-label">Attachment (Cheque/Slip Image)</label>
                                    <input type="file" name="attachment" class="form-control" accept="image/*,.pdf">
                                    <small class="text-muted">Optional: Upload cheque image or bank slip</small>
                                </div>

                                <!-- Notes -->
                                <div class="mb-3">
                                    <label class="form-label">Notes</label>
                                    <textarea name="notes" class="form-control" rows="2" placeholder="Any additional notes..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Post-Payment Actions -->
                        <div class="card bg-light mt-3">
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

                        <div class="alert alert-info mt-3 mb-0">
                            <i class="ti ti-info-circle me-2"></i>
                            Full payment will complete the admission and generate an admission number.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" id="submitPaymentBtn">
                            <i class="ti ti-check me-1"></i>Record Payment
                        </button>
                    </div>
                </form>
            </div>

            <!-- Success View (shown after payment recorded) -->
            <div id="paymentSuccessView" style="display: none;">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="ti ti-check me-2"></i>Payment Recorded Successfully
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <div class="mb-3">
                        <span class="avatar avatar-xl bg-success-lt text-success">
                            <i class="ti ti-check" style="font-size: 3rem;"></i>
                        </span>
                    </div>
                    <h3 class="mb-2">Payment Recorded!</h3>
                    <p class="text-muted mb-3">Receipt Number: <strong id="successReceiptNumber"></strong></p>
                    <p class="text-muted mb-3">Amount: <strong id="successAmount"></strong></p>
                    <div id="successAdmissionInfo" style="display: none;" class="alert alert-success">
                        <i class="ti ti-confetti me-2"></i>
                        <strong>Admission Complete!</strong><br>
                        Admission Number: <strong id="successAdmissionNumber"></strong>
                    </div>

                    <div class="btn-list mt-4">
                        <button type="button" class="btn btn-outline-primary" onclick="printPaymentReceipt()">
                            <i class="ti ti-printer me-1"></i>Print Receipt
                        </button>
                        <button type="button" class="btn btn-primary" onclick="closePaymentModal()">
                            <i class="ti ti-x me-1"></i>Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stage Transition Modal (for flexible stages) -->
<div class="modal fade" id="stageTransitionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/applicants/stage-transition" id="stageTransitionForm">
                <input type="hidden" name="applicant_id" id="stageTransitionApplicantId">
                <input type="hidden" name="new_status" id="stageTransitionNewStatus">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="ti ti-arrow-right me-2"></i>Move to <span id="stageTransitionLabel">Stage</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Applicant</label>
                        <div class="form-control-plaintext fw-bold"><?= e($applicant['first_name'] . ' ' . $applicant['last_name']) ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Current Status</label>
                        <div>
                            <?php
                            $statusLabels = [
                                'draft' => ['label' => 'Draft', 'color' => 'secondary'],
                                'submitted' => ['label' => 'Submitted', 'color' => 'info'],
                                'screening' => ['label' => 'Screening', 'color' => 'primary'],
                                'interview_scheduled' => ['label' => 'Interview Scheduled', 'color' => 'warning'],
                                'interviewed' => ['label' => 'Interviewed', 'color' => 'info'],
                                'exam_scheduled' => ['label' => 'Exam Scheduled', 'color' => 'warning'],
                                'exam_taken' => ['label' => 'Exam Taken', 'color' => 'info'],
                                'accepted' => ['label' => 'Accepted', 'color' => 'success'],
                                'waitlisted' => ['label' => 'Waitlisted', 'color' => 'warning'],
                                'rejected' => ['label' => 'Rejected', 'color' => 'danger'],
                                'withdrawn' => ['label' => 'Withdrawn', 'color' => 'secondary'],
                            ];
                            $currentInfo = $statusLabels[$applicant['status']] ?? ['label' => ucfirst($applicant['status']), 'color' => 'secondary'];
                            ?>
                            <span class="badge bg-<?= $currentInfo['color'] ?>"><?= $currentInfo['label'] ?></span>
                            <i class="ti ti-arrow-right mx-2"></i>
                            <span class="badge bg-primary" id="stageTransitionNewBadge">New Stage</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes (optional)</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Reason for stage change..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-check me-1"></i>Confirm Move
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Decision Modal (for Accept/Waitlist/Reject/Withdraw) -->
<div class="modal fade" id="decisionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/applicants/decision" id="decisionForm">
                <input type="hidden" name="applicant_id" id="decisionApplicantId">
                <input type="hidden" name="decision" id="decisionType">
                <div class="modal-header" id="decisionModalHeader">
                    <h5 class="modal-title">
                        <i class="ti ti-gavel me-2"></i><span id="decisionModalTitle">Make Decision</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Applicant</label>
                        <div class="form-control-plaintext fw-bold"><?= e($applicant['first_name'] . ' ' . $applicant['last_name']) ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Grade Applying For</label>
                        <div class="form-control-plaintext"><?= e($applicant['grade_name'] ?? 'N/A') ?></div>
                    </div>
                    <div class="mb-3" id="decisionReasonGroup">
                        <label class="form-label" id="decisionReasonLabel">Reason</label>
                        <textarea name="reason" class="form-control" rows="3" id="decisionReasonInput" placeholder="Enter reason for this decision..."></textarea>
                    </div>
                    <div class="alert" id="decisionAlert" style="display: none;">
                        <i class="ti ti-info-circle me-2"></i>
                        <span id="decisionAlertText"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn" id="decisionSubmitBtn">
                        <i class="ti ti-check me-1"></i><span id="decisionSubmitText">Confirm</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Payment methods configuration from PHP
window.paymentMethodsConfig = <?= $paymentMethodsJson ?>;
window.lastPaymentId = null;

// Pre-Admission Modal - assigned directly to window for AJAX compatibility
window.showPreAdmissionModal = function(applicantId) {
    document.getElementById('preAdmissionApplicantId').value = applicantId;
    const modal = new bootstrap.Modal(document.getElementById('preAdmissionModal'));
    modal.show();
};

// Payment Modal - Enhanced
window.showPaymentModal = function(applicantId) {
    document.getElementById('paymentApplicantId').value = applicantId;
    // Reset to form view
    document.getElementById('paymentFormView').style.display = 'block';
    document.getElementById('paymentSuccessView').style.display = 'none';
    // Reset form
    document.getElementById('paymentForm').reset();
    // Reset dynamic fields
    window.updatePaymentMethodFields();

    const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
    modal.show();
};

// Update dynamic fields based on payment method selection
window.updatePaymentMethodFields = function() {
    const select = document.getElementById('modalPaymentMethod');
    const option = select.options[select.selectedIndex];

    // Get data attributes
    const requiresRef = option?.dataset?.requiresReference === '1';
    const refLabel = option?.dataset?.referenceLabel || 'Reference Number';
    const requiresBank = option?.dataset?.requiresBank === '1';
    const requiresChequeDate = option?.dataset?.requiresChequeDate === '1';
    const allowAttachment = option?.dataset?.allowAttachment === '1';

    // Reference field
    const refGroup = document.getElementById('modalReferenceGroup');
    const refLabelEl = document.getElementById('modalReferenceLabel');
    const refInput = document.getElementById('modalReferenceNumber');
    if (refGroup) {
        refGroup.style.display = requiresRef ? 'block' : 'none';
        if (refLabelEl) refLabelEl.textContent = refLabel;
        if (refInput) refInput.required = requiresRef;
    }

    // Bank selection (required when visible)
    const bankGroup = document.getElementById('modalBankGroup');
    const bankSelect = document.getElementById('modalPayerBank');
    if (bankGroup) {
        bankGroup.style.display = requiresBank ? 'block' : 'none';
        if (bankSelect) bankSelect.required = requiresBank;
    }

    // Cheque date (required when visible)
    const chequeDateGroup = document.getElementById('modalChequeDateGroup');
    const chequeDateInput = document.getElementById('modalChequeDate');
    if (chequeDateGroup) {
        chequeDateGroup.style.display = requiresChequeDate ? 'block' : 'none';
        if (chequeDateInput) chequeDateInput.required = requiresChequeDate;
    }

    // Attachment (optional - only shown when allowed, not required)
    const attachmentGroup = document.getElementById('modalAttachmentGroup');
    if (attachmentGroup) {
        attachmentGroup.style.display = allowAttachment ? 'block' : 'none';
    }
};

// Handle payment form submission via AJAX
window.handlePaymentFormSubmit = function(event) {
    event.preventDefault();

    const form = document.getElementById('paymentForm');
    const formData = new FormData(form);
    const submitBtn = document.getElementById('submitPaymentBtn');

    // Disable button and show loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Processing...';

    fetch('/applicants/record-admission-payment', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        }
        // If not JSON, it might be a redirect or error page
        throw new Error('Server returned non-JSON response');
    })
    .then(data => {
        if (data.success) {
            // Close the modal immediately
            const modalEl = document.getElementById('paymentModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();

            // Store payment info in sessionStorage for display after refresh
            const paymentInfo = {
                type: 'success',
                receipt_number: data.receipt_number,
                amount: data.amount,
                admission_completed: data.admission_completed,
                admission_number: data.admission_number,
                remaining_balance: data.remaining_balance,
                message: data.message
            };
            sessionStorage.setItem('paymentResult', JSON.stringify(paymentInfo));

            // Remember to stay on Finances tab
            sessionStorage.setItem('activeTab', 'tab-finances');

            // Reload the page to show updated data
            window.location.reload();
        } else {
            // Show error in the modal
            showPaymentError(data.message || 'Failed to record payment');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="ti ti-check me-1"></i>Record Payment';
        }
    })
    .catch(error => {
        console.error('Payment error:', error);
        showPaymentError('An error occurred while processing the payment. Please try again.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="ti ti-check me-1"></i>Record Payment';
    });
};

// Show payment error in a styled alert inside modal
window.showPaymentError = function(message) {
    // Check if error container exists, create if not
    let errorContainer = document.getElementById('paymentErrorContainer');
    if (!errorContainer) {
        errorContainer = document.createElement('div');
        errorContainer.id = 'paymentErrorContainer';
        errorContainer.className = 'alert alert-danger alert-dismissible fade show mb-3';
        errorContainer.innerHTML = '<button type="button" class="btn-close" data-bs-dismiss="alert"></button><i class="ti ti-alert-circle me-2"></i><span id="paymentErrorMessage"></span>';

        const modalBody = document.querySelector('#paymentModal .modal-body');
        if (modalBody) {
            modalBody.insertBefore(errorContainer, modalBody.firstChild);
        }
    }

    document.getElementById('paymentErrorMessage').textContent = message;
    errorContainer.style.display = 'block';
    errorContainer.classList.add('show');
};

// Check for payment result message on page load and display it
window.checkPaymentResult = function() {
    const resultStr = sessionStorage.getItem('paymentResult');
    if (resultStr) {
        try {
            const result = JSON.parse(resultStr);
            sessionStorage.removeItem('paymentResult'); // Clear it immediately

            // Create and show the alert on the page
            let alertClass = result.type === 'success' ? 'alert-success' : 'alert-danger';
            let icon = result.type === 'success' ? 'ti-check' : 'ti-alert-circle';

            let message = '';
            if (result.type === 'success') {
                message = '<strong>Payment Recorded Successfully!</strong><br>';
                message += 'Receipt: <strong>' + result.receipt_number + '</strong> | ';
                message += 'Amount: <strong>KES ' + parseFloat(result.amount).toLocaleString('en-KE', {minimumFractionDigits: 2}) + '</strong>';

                if (result.admission_completed && result.admission_number) {
                    message += '<br><span class="text-success"><i class="ti ti-confetti me-1"></i>Admission Complete! Number: <strong>' + result.admission_number + '</strong></span>';
                } else if (result.remaining_balance > 0) {
                    message += '<br>Remaining Balance: <strong class="text-warning">KES ' + parseFloat(result.remaining_balance).toLocaleString('en-KE', {minimumFractionDigits: 2}) + '</strong>';
                }
            } else {
                message = result.message || 'An error occurred';
            }

            // Find a good place to insert the alert (before the card)
            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show mb-3" role="alert">
                    <div class="d-flex align-items-start">
                        <i class="ti ${icon} me-2" style="font-size: 1.5rem;"></i>
                        <div>${message}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;

            // Insert at the top of the finances tab
            const financesTab = document.getElementById('tab-finances');
            if (financesTab) {
                financesTab.insertAdjacentHTML('afterbegin', alertHtml);
            } else {
                // Fallback: insert before the main card
                const mainCard = document.querySelector('.card');
                if (mainCard) {
                    mainCard.insertAdjacentHTML('beforebegin', alertHtml);
                }
            }

            // Auto-dismiss after 10 seconds
            setTimeout(() => {
                const alert = document.querySelector('.alert-dismissible');
                if (alert) {
                    alert.classList.remove('show');
                    setTimeout(() => alert.remove(), 150);
                }
            }, 10000);

        } catch (e) {
            console.error('Error parsing payment result:', e);
        }
    }
};

// Print payment receipt
window.printPaymentReceipt = function() {
    if (window.lastPaymentId) {
        window.open('/finance/payments/' + window.lastPaymentId + '?print=1', '_blank');
    }
};

// Close payment modal
window.closePaymentModal = function() {
    const modalEl = document.getElementById('paymentModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();
};

// Refresh the finances tab content
window.refreshFinancesTab = function() {
    const financesTabPane = document.getElementById('tab-finances');
    if (!financesTabPane) {
        // Tab doesn't exist, do full page reload
        window.location.reload();
        return;
    }

    // Get applicant ID from the page
    const applicantId = document.getElementById('paymentApplicantId')?.value ||
                        document.querySelector('[data-applicant-id]')?.dataset.applicantId;

    if (!applicantId) {
        window.location.reload();
        return;
    }

    // Show loading indicator in the tab
    const originalContent = financesTabPane.innerHTML;
    financesTabPane.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Refreshing...</p></div>';

    // Fetch updated finances tab content
    fetch('/applicants/' + applicantId + '/finances-tab', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (response.ok) {
            return response.text();
        }
        throw new Error('Failed to refresh');
    })
    .then(html => {
        financesTabPane.innerHTML = html;
        // Reinitialize any components in the tab
        if (window.initializePageComponents) {
            window.initializePageComponents();
        }
    })
    .catch(error => {
        console.error('Error refreshing finances tab:', error);
        // Fallback to page reload
        window.location.reload();
    });
};

// View Invoice
window.viewInvoice = function(invoiceId) {
    if (invoiceId) {
        window.open('/finance/invoices/' + invoiceId, '_blank');
    }
};

// Initialize payment method field change listener
document.addEventListener('DOMContentLoaded', function() {
    const methodSelect = document.getElementById('modalPaymentMethod');
    if (methodSelect) {
        methodSelect.addEventListener('change', window.updatePaymentMethodFields);
    }

    // Handle payment form submission
    const paymentForm = document.getElementById('paymentForm');
    if (paymentForm) {
        paymentForm.addEventListener('submit', window.handlePaymentFormSubmit);
    }

    // Check for payment result message and display it
    window.checkPaymentResult();

    // Restore active tab from sessionStorage if set
    const savedTab = sessionStorage.getItem('activeTab');
    if (savedTab) {
        sessionStorage.removeItem('activeTab'); // Clear it
        const tabTrigger = document.querySelector(`[data-bs-target="#${savedTab}"]`);
        if (tabTrigger) {
            const tab = new bootstrap.Tab(tabTrigger);
            tab.show();
        }
    }
});

// Stage labels for display
window.stageLabels = {
    'draft': { label: 'Draft', color: 'secondary' },
    'submitted': { label: 'Submitted', color: 'info' },
    'screening': { label: 'Screening', color: 'primary' },
    'interview_scheduled': { label: 'Interview Scheduled', color: 'warning' },
    'interviewed': { label: 'Interviewed', color: 'info' },
    'exam_scheduled': { label: 'Exam Scheduled', color: 'warning' },
    'exam_taken': { label: 'Exam Taken', color: 'info' },
    'accepted': { label: 'Accepted', color: 'success' },
    'waitlisted': { label: 'Waitlisted', color: 'warning' },
    'rejected': { label: 'Rejected', color: 'danger' },
    'withdrawn': { label: 'Withdrawn', color: 'secondary' },
    'pre_admission': { label: 'Pre-Admission', color: 'info' },
    'admitted': { label: 'Admitted', color: 'success' }
};

// Stage Transition Modal (for flexible stages like submitted, screening, etc.)
window.showStageTransitionModal = function(applicantId, newStatus) {
    document.getElementById('stageTransitionApplicantId').value = applicantId;
    document.getElementById('stageTransitionNewStatus').value = newStatus;

    const stageInfo = window.stageLabels[newStatus] || { label: newStatus.replace('_', ' '), color: 'primary' };
    document.getElementById('stageTransitionLabel').textContent = stageInfo.label;
    document.getElementById('stageTransitionNewBadge').textContent = stageInfo.label;
    document.getElementById('stageTransitionNewBadge').className = 'badge bg-' + stageInfo.color;

    const modal = new bootstrap.Modal(document.getElementById('stageTransitionModal'));
    modal.show();
};

// Decision Modal (for Accept/Waitlist/Reject/Withdraw)
window.showDecisionModal = function(applicantId, decision) {
    document.getElementById('decisionApplicantId').value = applicantId;
    document.getElementById('decisionType').value = decision;

    const header = document.getElementById('decisionModalHeader');
    const title = document.getElementById('decisionModalTitle');
    const reasonLabel = document.getElementById('decisionReasonLabel');
    const reasonInput = document.getElementById('decisionReasonInput');
    const alert = document.getElementById('decisionAlert');
    const alertText = document.getElementById('decisionAlertText');
    const submitBtn = document.getElementById('decisionSubmitBtn');
    const submitText = document.getElementById('decisionSubmitText');

    // Reset
    header.className = 'modal-header';
    alert.style.display = 'none';
    reasonInput.required = false;

    if (decision === 'accepted') {
        header.classList.add('bg-success', 'text-white');
        title.textContent = 'Accept Applicant';
        reasonLabel.textContent = 'Acceptance Notes (optional)';
        reasonInput.placeholder = 'Any notes about the acceptance...';
        submitBtn.className = 'btn btn-success';
        submitText.textContent = 'Accept Applicant';
        alert.className = 'alert alert-success';
        alert.style.display = 'block';
        alertText.textContent = 'Accepted applicants can proceed to pre-admission and fee payment.';
    } else if (decision === 'waitlisted') {
        header.classList.add('bg-warning');
        title.textContent = 'Waitlist Applicant';
        reasonLabel.textContent = 'Reason for Waitlisting';
        reasonInput.placeholder = 'Enter reason for placing on waitlist...';
        reasonInput.required = true;
        submitBtn.className = 'btn btn-warning';
        submitText.textContent = 'Add to Waitlist';
        alert.className = 'alert alert-warning';
        alert.style.display = 'block';
        alertText.textContent = 'Waitlisted applicants can be accepted later if space becomes available.';
    } else if (decision === 'rejected') {
        header.classList.add('bg-danger', 'text-white');
        title.textContent = 'Reject Applicant';
        reasonLabel.textContent = 'Reason for Rejection';
        reasonInput.placeholder = 'Enter reason for rejection (required)...';
        reasonInput.required = true;
        submitBtn.className = 'btn btn-danger';
        submitText.textContent = 'Reject Applicant';
        alert.className = 'alert alert-danger';
        alert.style.display = 'block';
        alertText.textContent = 'This will end the application process. The applicant can be reconsidered later if needed.';
    } else if (decision === 'withdrawn') {
        header.classList.add('bg-secondary', 'text-white');
        title.textContent = 'Withdraw Application';
        reasonLabel.textContent = 'Reason for Withdrawal';
        reasonInput.placeholder = 'Enter reason for withdrawal...';
        submitBtn.className = 'btn btn-secondary';
        submitText.textContent = 'Withdraw Application';
        alert.className = 'alert alert-info';
        alert.style.display = 'block';
        alertText.textContent = 'Withdrawn applications can be reactivated later if needed.';
    }

    const modal = new bootstrap.Modal(document.getElementById('decisionModal'));
    modal.show();
};

// Backward compatibility - these call the unified decision modal
window.showAcceptModal = function(applicantId) { window.showDecisionModal(applicantId, 'accepted'); };
window.showWaitlistModal = function(applicantId) { window.showDecisionModal(applicantId, 'waitlisted'); };
window.showRejectModal = function(applicantId) { window.showDecisionModal(applicantId, 'rejected'); };

// Clean up modal backdrop
window.cleanupModal = function(modalId) {
    const modalEl = document.getElementById(modalId);
    if (modalEl) {
        modalEl.classList.remove('show');
        modalEl.style.display = 'none';
        modalEl.setAttribute('aria-hidden', 'true');
    }
    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
    document.body.classList.remove('modal-open');
    document.body.style.removeProperty('overflow');
    document.body.style.removeProperty('padding-right');
};

// Clean up on page show (handles back/forward cache)
window.addEventListener('pageshow', function(event) {
    // If page is loaded from cache (back/forward navigation)
    if (event.persisted) {
        window.cleanupModal('preAdmissionModal');
        window.cleanupModal('paymentModal');
        window.cleanupModal('stageTransitionModal');
        window.cleanupModal('decisionModal');
    }
    // Also clean up any stray backdrops
    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
    document.body.classList.remove('modal-open');
    document.body.style.removeProperty('overflow');
    document.body.style.removeProperty('padding-right');
});

document.addEventListener('DOMContentLoaded', function() {
    // Clean up any stray modal state on page load
    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
    document.body.classList.remove('modal-open');
    document.body.style.removeProperty('overflow');
    document.body.style.removeProperty('padding-right');

    // Add cleanup to all modal forms before submit
    const modalForms = [
        { formId: 'preAdmissionForm', modalId: 'preAdmissionModal' },
        { formId: 'paymentForm', modalId: 'paymentModal' },
        { formId: 'stageTransitionForm', modalId: 'stageTransitionModal' },
        { formId: 'decisionForm', modalId: 'decisionModal' }
    ];

    modalForms.forEach(({formId, modalId}) => {
        const form = document.getElementById(formId);
        if (form) {
            form.addEventListener('submit', function() {
                window.cleanupModal(modalId);
            });
        }
    });
});
</script>

<style>
/* Add padding above the tabs */
.card-header .nav-tabs {
    margin-top: 10px;
}

/* Fix dropdown menu z-index to appear above cards */
.table .dropdown-menu,
.card .dropdown-menu {
    z-index: 1050;
}
.table .dropdown,
.card .dropdown {
    position: relative;
}
/* Ensure the dropdown menu doesn't get clipped by table-responsive or card */
.table-responsive {
    overflow: visible !important;
}
.card-body {
    overflow: visible;
}
</style>

