<?php
/**
 * Screening Queue - Stage-based tabs (Tabler.io version)
 * Shows applicants in various workflow stages for processing
 */

// Count applicants by stage
$stageCounts = [
    'submitted' => 0,
    'screening' => 0,
    'interview_scheduled' => 0,
    'interviewed' => 0,
    'exam_scheduled' => 0,
    'exam_taken' => 0,
    'accepted' => 0,
    'waitlisted' => 0,
    'rejected' => 0
];

$stageApplicants = [
    'submitted' => [],
    'screening' => [],
    'interview_scheduled' => [],
    'interviewed' => [],
    'exam_scheduled' => [],
    'exam_taken' => [],
    'accepted' => [],
    'waitlisted' => [],
    'rejected' => []
];

foreach ($applicants as $applicant) {
    $status = $applicant['status'];
    if (isset($stageApplicants[$status])) {
        $stageApplicants[$status][] = $applicant;
        $stageCounts[$status]++;
    }
}

// Active tab from URL or default to 'submitted'
$activeTab = $_GET['tab'] ?? 'submitted';
$validTabs = ['submitted', 'screening', 'interview', 'exam', 'decisions'];
if (!in_array($activeTab, $validTabs)) {
    $activeTab = 'submitted';
}
?>

<!-- Page Header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <h2 class="page-title">
                    <i class="ti ti-filter me-2"></i>Screening & Workflow
                </h2>
                <div class="text-muted mt-1">Process applications through interview, exam, and decision stages</div>
            </div>
            <div class="col-auto ms-auto">
                <a href="/applicants" class="btn btn-outline-secondary">
                    <i class="ti ti-list me-1"></i> All Applicants
                </a>
                <a href="/applicants/create" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i> New Applicant
                </a>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <!-- Filters Card -->
        <div class="card mb-3">
            <div class="card-body py-2">
                <form method="GET" action="/applicants/screening" class="row g-2 align-items-center">
                    <input type="hidden" name="tab" value="<?= e($activeTab) ?>">
                    <div class="col-auto">
                        <label class="form-label mb-0 me-2">Grade:</label>
                    </div>
                    <div class="col-auto">
                        <select name="grade" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 150px;">
                            <option value="">All Grades</option>
                            <?php foreach ($grades as $grade): ?>
                                <option value="<?= $grade['id'] ?>" <?= ($filterGrade ?? '') == $grade['id'] ? 'selected' : '' ?>>
                                    <?= e($grade['grade_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="form-label mb-0 me-2">Campaign:</label>
                    </div>
                    <div class="col-auto">
                        <select name="campaign" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 180px;">
                            <option value="">All Campaigns</option>
                            <?php foreach ($campaigns ?? [] as $campaign): ?>
                                <option value="<?= $campaign['id'] ?>" <?= ($filterCampaign ?? '') == $campaign['id'] ? 'selected' : '' ?>>
                                    <?= e($campaign['campaign_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (!empty($filterGrade) || !empty($filterCampaign)): ?>
                    <div class="col-auto">
                        <a href="/applicants/screening?tab=<?= e($activeTab) ?>" class="btn btn-sm btn-ghost-secondary">
                            <i class="ti ti-x me-1"></i> Clear
                        </a>
                    </div>
                    <?php endif; ?>
                    <div class="col-auto ms-auto">
                        <span class="badge bg-primary"><?= count($applicants) ?> Total Applications</span>
                    </div>
                </form>
            </div>
        </div>

        <!-- Main Card with Tabs -->
        <div class="card">
            <div class="card-header p-0 pt-2">
                <ul class="nav nav-tabs nav-tabs-alt card-header-tabs" data-bs-toggle="tabs">
                    <li class="nav-item">
                        <a href="#tab-submitted" class="nav-link <?= $activeTab === 'submitted' ? 'active' : '' ?>" data-bs-toggle="tab">
                            <i class="ti ti-inbox me-1"></i> Submitted
                            <span class="badge bg-primary-lt ms-2"><?= $stageCounts['submitted'] ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#tab-screening" class="nav-link <?= $activeTab === 'screening' ? 'active' : '' ?>" data-bs-toggle="tab">
                            <i class="ti ti-search me-1"></i> Screening
                            <span class="badge bg-info-lt ms-2"><?= $stageCounts['screening'] ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#tab-interview" class="nav-link <?= $activeTab === 'interview' ? 'active' : '' ?>" data-bs-toggle="tab">
                            <i class="ti ti-calendar-event me-1"></i> Interview
                            <span class="badge bg-warning-lt ms-2"><?= $stageCounts['interview_scheduled'] + $stageCounts['interviewed'] ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#tab-exam" class="nav-link <?= $activeTab === 'exam' ? 'active' : '' ?>" data-bs-toggle="tab">
                            <i class="ti ti-writing me-1"></i> Exam
                            <span class="badge bg-purple-lt ms-2"><?= $stageCounts['exam_scheduled'] + $stageCounts['exam_taken'] ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#tab-decisions" class="nav-link <?= $activeTab === 'decisions' ? 'active' : '' ?>" data-bs-toggle="tab">
                            <i class="ti ti-gavel me-1"></i> Decisions
                            <span class="badge bg-success-lt ms-2"><?= $stageCounts['accepted'] + $stageCounts['waitlisted'] + $stageCounts['rejected'] ?></span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="card-body p-0">
                <div class="tab-content">
                    <!-- Submitted Tab -->
                    <div class="tab-pane <?= $activeTab === 'submitted' ? 'active show' : '' ?>" id="tab-submitted">
                        <?= renderStageTableTabler($stageApplicants['submitted'], 'submitted') ?>
                    </div>

                    <!-- Screening Tab -->
                    <div class="tab-pane <?= $activeTab === 'screening' ? 'active show' : '' ?>" id="tab-screening">
                        <?= renderStageTableTabler($stageApplicants['screening'], 'screening') ?>
                    </div>

                    <!-- Interview Tab -->
                    <div class="tab-pane <?= $activeTab === 'interview' ? 'active show' : '' ?>" id="tab-interview">
                        <?php if (!empty($stageApplicants['interview_scheduled'])): ?>
                        <div class="px-3 py-2 bg-light border-bottom">
                            <span class="text-muted fw-medium"><i class="ti ti-calendar-time me-1"></i> Scheduled (<?= count($stageApplicants['interview_scheduled']) ?>)</span>
                        </div>
                        <?= renderStageTableTabler($stageApplicants['interview_scheduled'], 'interview_scheduled') ?>
                        <?php endif; ?>

                        <?php if (!empty($stageApplicants['interviewed'])): ?>
                        <div class="px-3 py-2 bg-light border-bottom border-top">
                            <span class="text-muted fw-medium"><i class="ti ti-circle-check me-1"></i> Completed (<?= count($stageApplicants['interviewed']) ?>)</span>
                        </div>
                        <?= renderStageTableTabler($stageApplicants['interviewed'], 'interviewed') ?>
                        <?php endif; ?>

                        <?php if (empty($stageApplicants['interview_scheduled']) && empty($stageApplicants['interviewed'])): ?>
                        <?= renderEmptyState('No applicants in interview stage') ?>
                        <?php endif; ?>
                    </div>

                    <!-- Exam Tab -->
                    <div class="tab-pane <?= $activeTab === 'exam' ? 'active show' : '' ?>" id="tab-exam">
                        <?php if (!empty($stageApplicants['exam_scheduled'])): ?>
                        <div class="px-3 py-2 bg-light border-bottom">
                            <span class="text-muted fw-medium"><i class="ti ti-calendar-time me-1"></i> Scheduled (<?= count($stageApplicants['exam_scheduled']) ?>)</span>
                        </div>
                        <?= renderStageTableTabler($stageApplicants['exam_scheduled'], 'exam_scheduled') ?>
                        <?php endif; ?>

                        <?php if (!empty($stageApplicants['exam_taken'])): ?>
                        <div class="px-3 py-2 bg-light border-bottom border-top">
                            <span class="text-muted fw-medium"><i class="ti ti-circle-check me-1"></i> Completed (<?= count($stageApplicants['exam_taken']) ?>)</span>
                        </div>
                        <?= renderStageTableTabler($stageApplicants['exam_taken'], 'exam_taken') ?>
                        <?php endif; ?>

                        <?php if (empty($stageApplicants['exam_scheduled']) && empty($stageApplicants['exam_taken'])): ?>
                        <?= renderEmptyState('No applicants in exam stage') ?>
                        <?php endif; ?>
                    </div>

                    <!-- Decisions Tab -->
                    <div class="tab-pane <?= $activeTab === 'decisions' ? 'active show' : '' ?>" id="tab-decisions">
                        <?php
                        $decisionsApplicants = array_merge(
                            $stageApplicants['accepted'],
                            $stageApplicants['waitlisted'],
                            $stageApplicants['rejected']
                        );
                        ?>
                        <?php if (!empty($decisionsApplicants)): ?>
                        <?= renderStageTableTabler($decisionsApplicants, 'decisions') ?>
                        <?php else: ?>
                        <?= renderEmptyState('No decisions recorded yet') ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
/**
 * Render empty state
 */
function renderEmptyState($message) {
    return '
    <div class="empty py-5">
        <div class="empty-icon">
            <i class="ti ti-inbox" style="font-size: 3rem;"></i>
        </div>
        <p class="empty-title">' . e($message) . '</p>
        <p class="empty-subtitle text-muted">Applications will appear here when they reach this stage.</p>
    </div>';
}

/**
 * Render applicants table for a specific stage (Tabler version)
 */
function renderStageTableTabler($applicants, $stage) {
    if (empty($applicants)) {
        return renderEmptyState('No applicants in this stage');
    }

    ob_start();
    ?>
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-hover">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Applicant</th>
                    <th>Grade</th>
                    <th>Contact</th>
                    <th>Submitted</th>
                    <th>Status</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($applicants as $applicant): ?>
                    <?php
                    $submittedDate = strtotime($applicant['submitted_at'] ?? 'now');
                    $daysPending = floor((time() - $submittedDate) / 86400);
                    $urgencyClass = $daysPending > 7 ? 'text-danger' : ($daysPending > 3 ? 'text-warning' : 'text-muted');
                    ?>
                    <tr>
                        <td>
                            <a href="/applicants/<?= $applicant['id'] ?>" class="text-reset">
                                <strong><?= e($applicant['application_ref']) ?></strong>
                            </a>
                        </td>
                        <td>
                            <a href="/applicants/<?= $applicant['id'] ?>" class="text-reset">
                                <?= e($applicant['first_name'] . ' ' . $applicant['last_name']) ?>
                            </a>
                        </td>
                        <td class="text-muted"><?= e($applicant['grade_name'] ?? 'N/A') ?></td>
                        <td>
                            <?php if (!empty($applicant['phone'])): ?>
                                <div><i class="ti ti-phone me-1 text-muted"></i><?= e($applicant['phone']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($applicant['email'])): ?>
                                <div class="text-muted small"><i class="ti ti-mail me-1"></i><?= e($applicant['email']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div><?= formatDate($applicant['submitted_at'] ?? '') ?></div>
                            <div class="<?= $urgencyClass ?> small"><?= $daysPending ?> days ago</div>
                        </td>
                        <td>
                            <?= renderStatusBadgeTabler($applicant['status']) ?>
                        </td>
                        <td>
                            <?= renderActionsDropdownTabler($applicant) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render status badge (Tabler version)
 */
function renderStatusBadgeTabler($status) {
    $badges = [
        'submitted' => 'bg-primary-lt',
        'screening' => 'bg-info-lt',
        'interview_scheduled' => 'bg-warning-lt',
        'interviewed' => 'bg-cyan-lt',
        'exam_scheduled' => 'bg-purple-lt',
        'exam_taken' => 'bg-indigo-lt',
        'accepted' => 'bg-success-lt',
        'waitlisted' => 'bg-warning-lt',
        'rejected' => 'bg-danger-lt',
        'pre_admission' => 'bg-teal-lt',
        'admitted' => 'bg-green-lt'
    ];
    $class = $badges[$status] ?? 'bg-secondary-lt';
    $label = ucwords(str_replace('_', ' ', $status));
    return '<span class="badge ' . $class . '">' . $label . '</span>';
}

/**
 * Render actions dropdown (Tabler version)
 */
function renderActionsDropdownTabler($applicant) {
    $status = $applicant['status'];
    $id = $applicant['id'];

    ob_start();
    ?>
    <div class="dropdown">
        <button class="btn btn-sm dropdown-toggle align-text-top" data-bs-toggle="dropdown">
            Actions
        </button>
        <div class="dropdown-menu dropdown-menu-end">
            <a class="dropdown-item" href="/applicants/<?= $id ?>">
                <i class="ti ti-eye me-2"></i> View Details
            </a>
            <div class="dropdown-divider"></div>
            <span class="dropdown-header">Move to Stage</span>

            <?php if ($status === 'submitted'): ?>
                <a class="dropdown-item" href="#" onclick="quickStageMove(<?= $id ?>, 'screening'); return false;">
                    <i class="ti ti-search me-2 text-info"></i> Start Screening
                </a>
                <a class="dropdown-item" href="#" onclick="showInterviewModal(<?= $id ?>); return false;">
                    <i class="ti ti-calendar me-2 text-warning"></i> Schedule Interview
                </a>
                <a class="dropdown-item" href="#" onclick="showExamModal(<?= $id ?>); return false;">
                    <i class="ti ti-writing me-2 text-purple"></i> Schedule Exam
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#" onclick="showDecisionModal(<?= $id ?>, 'accepted'); return false;">
                    <i class="ti ti-check me-2 text-success"></i> Accept
                </a>
                <a class="dropdown-item" href="#" onclick="showDecisionModal(<?= $id ?>, 'rejected'); return false;">
                    <i class="ti ti-x me-2 text-danger"></i> Reject
                </a>

            <?php elseif ($status === 'screening'): ?>
                <a class="dropdown-item" href="#" onclick="showInterviewModal(<?= $id ?>); return false;">
                    <i class="ti ti-calendar me-2 text-warning"></i> Schedule Interview
                </a>
                <a class="dropdown-item" href="#" onclick="showExamModal(<?= $id ?>); return false;">
                    <i class="ti ti-writing me-2 text-purple"></i> Schedule Exam
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#" onclick="showDecisionModal(<?= $id ?>, 'accepted'); return false;">
                    <i class="ti ti-check me-2 text-success"></i> Accept
                </a>
                <a class="dropdown-item" href="#" onclick="showDecisionModal(<?= $id ?>, 'waitlisted'); return false;">
                    <i class="ti ti-clock me-2 text-warning"></i> Waitlist
                </a>
                <a class="dropdown-item" href="#" onclick="showDecisionModal(<?= $id ?>, 'rejected'); return false;">
                    <i class="ti ti-x me-2 text-danger"></i> Reject
                </a>

            <?php elseif ($status === 'interview_scheduled'): ?>
                <a class="dropdown-item" href="#" onclick="showInterviewOutcomeModal(<?= $id ?>); return false;">
                    <i class="ti ti-circle-check me-2"></i> Record Outcome
                </a>
                <a class="dropdown-item" href="#" onclick="showInterviewModal(<?= $id ?>, true); return false;">
                    <i class="ti ti-calendar-event me-2"></i> Reschedule
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#" onclick="showDecisionModal(<?= $id ?>, 'accepted'); return false;">
                    <i class="ti ti-check me-2 text-success"></i> Accept
                </a>
                <a class="dropdown-item" href="#" onclick="showDecisionModal(<?= $id ?>, 'rejected'); return false;">
                    <i class="ti ti-x me-2 text-danger"></i> Reject
                </a>

            <?php elseif ($status === 'interviewed'): ?>
                <a class="dropdown-item" href="#" onclick="showExamModal(<?= $id ?>); return false;">
                    <i class="ti ti-writing me-2 text-purple"></i> Schedule Exam
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#" onclick="showDecisionModal(<?= $id ?>, 'accepted'); return false;">
                    <i class="ti ti-check me-2 text-success"></i> Accept
                </a>
                <a class="dropdown-item" href="#" onclick="showDecisionModal(<?= $id ?>, 'waitlisted'); return false;">
                    <i class="ti ti-clock me-2 text-warning"></i> Waitlist
                </a>
                <a class="dropdown-item" href="#" onclick="showDecisionModal(<?= $id ?>, 'rejected'); return false;">
                    <i class="ti ti-x me-2 text-danger"></i> Reject
                </a>

            <?php elseif ($status === 'exam_scheduled'): ?>
                <a class="dropdown-item" href="#" onclick="showExamScoreModal(<?= $id ?>); return false;">
                    <i class="ti ti-circle-check me-2"></i> Record Score
                </a>
                <a class="dropdown-item" href="#" onclick="showExamModal(<?= $id ?>, true); return false;">
                    <i class="ti ti-calendar-event me-2"></i> Reschedule
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#" onclick="showDecisionModal(<?= $id ?>, 'accepted'); return false;">
                    <i class="ti ti-check me-2 text-success"></i> Accept
                </a>
                <a class="dropdown-item" href="#" onclick="showDecisionModal(<?= $id ?>, 'rejected'); return false;">
                    <i class="ti ti-x me-2 text-danger"></i> Reject
                </a>

            <?php elseif ($status === 'exam_taken'): ?>
                <a class="dropdown-item" href="#" onclick="showDecisionModal(<?= $id ?>, 'accepted'); return false;">
                    <i class="ti ti-check me-2 text-success"></i> Accept
                </a>
                <a class="dropdown-item" href="#" onclick="showDecisionModal(<?= $id ?>, 'waitlisted'); return false;">
                    <i class="ti ti-clock me-2 text-warning"></i> Waitlist
                </a>
                <a class="dropdown-item" href="#" onclick="showDecisionModal(<?= $id ?>, 'rejected'); return false;">
                    <i class="ti ti-x me-2 text-danger"></i> Reject
                </a>

            <?php elseif (in_array($status, ['accepted', 'waitlisted', 'rejected'])): ?>
                <a class="dropdown-item" href="/applicants/<?= $id ?>#tab-activity">
                    <i class="ti ti-history me-2"></i> View History
                </a>
                <?php if ($status !== 'accepted'): ?>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#" onclick="showDecisionModal(<?= $id ?>, 'accepted'); return false;">
                    <i class="ti ti-check me-2 text-success"></i> Change to Accept
                </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>

<!-- Decision Modal -->
<div class="modal fade" id="decisionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="decisionForm" method="POST" action="/applicants/decision">
                <input type="hidden" name="applicant_id" id="decision_applicant_id">
                <input type="hidden" name="decision" id="decision_type">
                <div class="modal-header" id="decisionModalHeader">
                    <h5 class="modal-title" id="decisionModalTitle">Application Decision</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="decision-accept-fields" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">Offer Expiry Date</label>
                            <input type="date" name="offer_expiry_date" class="form-control"
                                   min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                            <div class="form-hint">Deadline for accepting the offer</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Conditions (Optional)</label>
                            <textarea name="conditions" class="form-control" rows="2"
                                      placeholder="Any conditions for acceptance"></textarea>
                        </div>
                    </div>

                    <div id="decision-reject-fields" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label required">Rejection Reason</label>
                            <select name="rejection_reason" class="form-select" id="rejectionReasonSelect">
                                <option value="">Select reason</option>
                                <option value="age_requirement">Does not meet age requirements</option>
                                <option value="capacity_full">Grade capacity full</option>
                                <option value="incomplete_docs">Incomplete documentation</option>
                                <option value="interview_performance">Interview performance</option>
                                <option value="exam_score">Entrance exam score</option>
                                <option value="other">Other (specify in notes)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" id="notesLabel">Additional Notes</label>
                        <textarea name="reason" class="form-control" rows="3" id="notesInput"
                                  placeholder="Additional notes about this decision"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="decision-submit-btn">Confirm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Quick stage move (no modal)
function quickStageMove(applicantId, newStatus) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/applicants/stage-transition';

    const idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'applicant_id';
    idInput.value = applicantId;
    form.appendChild(idInput);

    const statusInput = document.createElement('input');
    statusInput.type = 'hidden';
    statusInput.name = 'new_status';
    statusInput.value = newStatus;
    form.appendChild(statusInput);

    document.body.appendChild(form);
    form.submit();
}

// Decision Modal
function showDecisionModal(applicantId, decision) {
    document.getElementById('decision_applicant_id').value = applicantId;
    document.getElementById('decision_type').value = decision;

    const header = document.getElementById('decisionModalHeader');
    const title = document.getElementById('decisionModalTitle');
    const submitBtn = document.getElementById('decision-submit-btn');
    const acceptFields = document.getElementById('decision-accept-fields');
    const rejectFields = document.getElementById('decision-reject-fields');
    const notesLabel = document.getElementById('notesLabel');
    const notesInput = document.getElementById('notesInput');

    // Reset
    header.className = 'modal-header';
    acceptFields.style.display = 'none';
    rejectFields.style.display = 'none';
    notesInput.required = false;

    if (decision === 'accepted') {
        header.classList.add('bg-success', 'text-white');
        title.textContent = 'Accept Application';
        submitBtn.className = 'btn btn-success';
        submitBtn.textContent = 'Confirm Accept';
        acceptFields.style.display = 'block';
        notesLabel.textContent = 'Acceptance Notes (Optional)';
    } else if (decision === 'waitlisted') {
        header.classList.add('bg-warning');
        title.textContent = 'Waitlist Application';
        submitBtn.className = 'btn btn-warning';
        submitBtn.textContent = 'Confirm Waitlist';
        notesLabel.textContent = 'Reason for Waitlisting';
        notesInput.required = true;
    } else if (decision === 'rejected') {
        header.classList.add('bg-danger', 'text-white');
        title.textContent = 'Reject Application';
        submitBtn.className = 'btn btn-danger';
        submitBtn.textContent = 'Confirm Reject';
        rejectFields.style.display = 'block';
        notesLabel.textContent = 'Additional Notes';
    }

    const modal = new bootstrap.Modal(document.getElementById('decisionModal'));
    modal.show();
}

// Placeholder functions for interview/exam modals
function showInterviewModal(applicantId, isReschedule = false) {
    window.location.href = '/applicants/' + applicantId + '?action=schedule_interview';
}

function showInterviewOutcomeModal(applicantId) {
    window.location.href = '/applicants/' + applicantId + '?action=interview_outcome';
}

function showExamModal(applicantId, isReschedule = false) {
    window.location.href = '/applicants/' + applicantId + '?action=schedule_exam';
}

function showExamScoreModal(applicantId) {
    window.location.href = '/applicants/' + applicantId + '?action=exam_score';
}

// Clean up modal on page load/show
window.addEventListener('pageshow', function() {
    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
    document.body.classList.remove('modal-open');
    document.body.style.removeProperty('overflow');
    document.body.style.removeProperty('padding-right');
});

// Cleanup modal before form submit
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('decisionForm');
    if (form) {
        form.addEventListener('submit', function() {
            const modalEl = document.getElementById('decisionModal');
            if (modalEl) {
                modalEl.classList.remove('show');
                modalEl.style.display = 'none';
            }
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
            document.body.classList.remove('modal-open');
        });
    }
});
</script>

<style>
.bg-purple-lt {
    background-color: rgba(111, 66, 193, 0.1) !important;
    color: #6f42c1 !important;
}
.text-purple {
    color: #6f42c1 !important;
}
.empty {
    text-align: center;
}
.empty-icon {
    color: #adb5bd;
    margin-bottom: 1rem;
}
/* Fix dropdown menu z-index to appear above other table rows */
.table .dropdown-menu {
    z-index: 1050;
    position: absolute;
}
.table .dropdown {
    position: static;
}
/* Ensure the dropdown menu doesn't get clipped by table-responsive, tabs, or card */
.table-responsive {
    overflow: visible !important;
}
.card-body.p-0 {
    overflow: visible;
}
.tab-content {
    overflow: visible;
}
.tab-pane {
    overflow: visible;
}
.card {
    overflow: visible;
}
</style>
