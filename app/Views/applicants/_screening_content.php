<?php
/**
 * Screening Queue - Applications pending review
 * Shows only submitted applications awaiting decision
 */
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Screening Queue</h3>
                <div class="card-actions">
                    <span class="badge bg-primary"><?= count($applicants) ?> Pending Review</span>
                </div>
            </div>

            <!-- Filters -->
            <div class="card-body border-bottom py-3">
                <form method="GET" action="/applicants/screening" class="row g-2 align-items-center">
                    <div class="col-auto">
                        <label class="form-label mb-0 me-2">Grade:</label>
                    </div>
                    <div class="col-auto">
                        <select name="grade" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Grades</option>
                            <?php foreach ($grades as $grade): ?>
                                <option value="<?= $grade['id'] ?>" <?= $filterGrade == $grade['id'] ? 'selected' : '' ?>>
                                    <?= e($grade['grade_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-auto">
                        <label class="form-label mb-0 me-2">Campaign:</label>
                    </div>
                    <div class="col-auto">
                        <select name="campaign" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Campaigns</option>
                            <?php foreach ($campaigns as $campaign): ?>
                                <option value="<?= $campaign['id'] ?>" <?= $filterCampaign == $campaign['id'] ? 'selected' : '' ?>>
                                    <?= e($campaign['campaign_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-auto">
                        <label class="form-label mb-0 me-2">Status:</label>
                    </div>
                    <div class="col-auto">
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="submitted" <?= $filterStatus == 'submitted' ? 'selected' : '' ?>>Submitted</option>
                            <option value="screening" <?= $filterStatus == 'screening' ? 'selected' : '' ?>>In Screening</option>
                            <option value="interview_scheduled" <?= $filterStatus == 'interview_scheduled' ? 'selected' : '' ?>>Interview Scheduled</option>
                        </select>
                    </div>

                    <?php if ($filterGrade || $filterCampaign || $filterStatus): ?>
                        <div class="col-auto">
                            <a href="/applicants/screening" class="btn btn-sm btn-secondary">
                                <i class="ti ti-x"></i> Clear Filters
                            </a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Table -->
            <div class="card-body p-0">
                <?php if (empty($applicants)): ?>
                    <div class="text-center py-5">
                        <i class="ti ti-inbox ti-lg text-muted mb-3" style="font-size: 3rem;"></i>
                        <p class="text-muted">No applications in screening queue</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-hover">
                            <thead>
                                <tr>
                                    <th>Ref</th>
                                    <th>Applicant Name</th>
                                    <th>Grade</th>
                                    <th>Campaign</th>
                                    <th>Submitted</th>
                                    <th>Days Pending</th>
                                    <th>Status</th>
                                    <th class="w-1">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($applicants as $applicant): ?>
                                    <?php
                                    $submittedDate = !empty($applicant['submitted_at']) ? strtotime($applicant['submitted_at']) : strtotime($applicant['created_at'] ?? 'now');
                                    $daysPending = floor((time() - $submittedDate) / 86400);
                                    $urgencyClass = $daysPending > 7 ? 'text-danger' : ($daysPending > 3 ? 'text-warning' : '');
                                    ?>
                                    <tr>
                                        <td>
                                            <a href="/applicants/<?= $applicant['id'] ?>">
                                                <strong><?= e($applicant['application_ref']) ?></strong>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="avatar avatar-sm bg-primary-lt me-2">
                                                    <?= strtoupper(substr($applicant['first_name'], 0, 1) . substr($applicant['last_name'], 0, 1)) ?>
                                                </span>
                                                <div>
                                                    <a href="/applicants/<?= $applicant['id'] ?>" class="text-reset">
                                                        <?= e($applicant['first_name'] . ' ' . $applicant['last_name']) ?>
                                                    </a>
                                                    <?php if (!empty($applicant['phone'])): ?>
                                                        <div class="text-muted small">
                                                            <i class="ti ti-phone"></i> <?= e($applicant['phone']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= e($applicant['grade_name']) ?></td>
                                        <td><?= e($applicant['campaign_name'] ?? 'N/A') ?></td>
                                        <td><?= formatDate($applicant['submitted_at']) ?></td>
                                        <td>
                                            <span class="<?= $urgencyClass ?>">
                                                <?= $daysPending ?> day<?= $daysPending != 1 ? 's' : '' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $statusConfig = match($applicant['status']) {
                                                'submitted' => ['class' => 'bg-primary', 'label' => 'Submitted'],
                                                'screening' => ['class' => 'bg-info', 'label' => 'In Screening'],
                                                'interview_scheduled' => ['class' => 'bg-warning', 'label' => 'Interview Scheduled'],
                                                default => ['class' => 'bg-secondary', 'label' => formatStatus($applicant['status'])]
                                            };
                                            ?>
                                            <span class="badge <?= $statusConfig['class'] ?>">
                                                <?= $statusConfig['label'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    Actions
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <a href="/applicants/<?= $applicant['id'] ?>" class="dropdown-item">
                                                        <i class="ti ti-eye me-2"></i> View Details
                                                    </a>
                                                    <div class="dropdown-divider"></div>
                                                    <h6 class="dropdown-header">Make Decision</h6>
                                                    <a href="#" class="dropdown-item text-success" onclick="showDecisionModal(<?= $applicant['id'] ?>, 'accepted'); return false;">
                                                        <i class="ti ti-check me-2"></i> Accept Application
                                                    </a>
                                                    <a href="#" class="dropdown-item text-warning" onclick="showDecisionModal(<?= $applicant['id'] ?>, 'waitlisted'); return false;">
                                                        <i class="ti ti-clock me-2"></i> Add to Waitlist
                                                    </a>
                                                    <a href="#" class="dropdown-item text-danger" onclick="showDecisionModal(<?= $applicant['id'] ?>, 'rejected'); return false;">
                                                        <i class="ti ti-x me-2"></i> Reject Application
                                                    </a>
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

            <?php if (!empty($applicants)): ?>
                <div class="card-footer">
                    <div class="row align-items-center">
                        <div class="col-sm-6">
                            <span class="text-muted">
                                Showing <?= count($applicants) ?> application<?= count($applicants) != 1 ? 's' : '' ?>
                            </span>
                        </div>
                        <div class="col-sm-6 text-end">
                            <span class="text-muted">
                                <span class="text-danger">Red</span> = Over 7 days |
                                <span class="text-warning">Yellow</span> = 4-7 days
                            </span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Decision Modal -->
<div class="modal modal-blur fade" id="decisionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="decisionForm" method="POST" action="/applicants/decision">
                <input type="hidden" name="applicant_id" id="decision_applicant_id">
                <input type="hidden" name="decision" id="decision_type">

                <div class="modal-header">
                    <h5 class="modal-title" id="decisionModalTitle">Application Decision</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div id="decision-accept-fields" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">Offer Expiry Date</label>
                            <input type="date" name="offer_expiry_date" class="form-control"
                                   min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                            <span class="form-hint">Deadline for accepting the offer</span>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Conditions (Optional)</label>
                            <textarea name="conditions" class="form-control" rows="3"
                                      placeholder="Any conditions for acceptance (e.g., pending documents)"></textarea>
                        </div>
                    </div>

                    <div id="decision-reject-fields" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label required">Rejection Reason</label>
                            <select name="rejection_reason" class="form-select">
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
                        <label class="form-label">Additional Notes</label>
                        <textarea name="notes" class="form-control" rows="3"
                                  placeholder="Additional notes about this decision"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="decision-submit-btn">Confirm Decision</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showDecisionModal(applicantId, decision) {
    document.getElementById('decision_applicant_id').value = applicantId;
    document.getElementById('decision_type').value = decision;

    // Update modal title and button
    const titles = {
        'accepted': 'Accept Application',
        'waitlisted': 'Waitlist Application',
        'rejected': 'Reject Application'
    };

    const buttonClasses = {
        'accepted': 'btn-success',
        'waitlisted': 'btn-warning',
        'rejected': 'btn-danger'
    };

    document.getElementById('decisionModalTitle').textContent = titles[decision];

    const submitBtn = document.getElementById('decision-submit-btn');
    submitBtn.className = 'btn ' + buttonClasses[decision];
    submitBtn.textContent = 'Confirm ' + decision.charAt(0).toUpperCase() + decision.slice(1);

    // Show/hide relevant fields
    document.getElementById('decision-accept-fields').style.display = decision === 'accepted' ? 'block' : 'none';
    document.getElementById('decision-reject-fields').style.display = decision === 'rejected' ? 'block' : 'none';

    // Show modal using Bootstrap 5 API
    const modal = new bootstrap.Modal(document.getElementById('decisionModal'));
    modal.show();
}
</script>

<style>
.form-label.required::after {
    content: " *";
    color: #dc3545;
}
</style>
