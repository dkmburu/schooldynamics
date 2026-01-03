<?php
/**
 * Screening Workflow Modals
 * Stage-specific forms for transitions
 */
?>

<!-- Schedule Interview Modal -->
<div class="modal fade" id="interviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="interviewForm" method="POST" action="/applicants/schedule-interview" data-no-ajax="true">
                <input type="hidden" name="applicant_id" id="interview_applicant_id">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-calendar-alt mr-2"></i>Schedule Interview
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="required">Interview Date</label>
                                <input type="date" name="interview_date" class="form-control" required
                                       min="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="required">Interview Time</label>
                                <input type="time" name="interview_time" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Duration (minutes)</label>
                                <select name="duration_minutes" class="form-control">
                                    <option value="15">15 minutes</option>
                                    <option value="30" selected>30 minutes</option>
                                    <option value="45">45 minutes</option>
                                    <option value="60">60 minutes</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Location / Virtual Link</label>
                                <input type="text" name="location" class="form-control"
                                       placeholder="e.g., Admin Office or Zoom link">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Panel Members / Interviewer</label>
                        <input type="text" name="panel_members" class="form-control"
                               placeholder="Names of interview panel members">
                    </div>

                    <div class="form-group">
                        <label>Internal Notes</label>
                        <textarea name="notes" class="form-control" rows="2"
                                  placeholder="Any special notes about this interview"></textarea>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" name="send_sms" value="1" class="form-check-input" id="interview_send_sms" checked>
                        <label class="form-check-label" for="interview_send_sms">
                            Send SMS notification to applicant
                        </label>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" name="send_email" value="1" class="form-check-input" id="interview_send_email" checked>
                        <label class="form-check-label" for="interview_send_email">
                            Send email notification to applicant
                        </label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="ti ti-x me-1"></i>Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-calendar-check mr-2"></i>Schedule Interview
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Record Interview Outcome Modal -->
<div class="modal fade" id="interviewOutcomeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="interviewOutcomeForm" method="POST" action="/applicants/interview-outcome" data-no-ajax="true">
                <input type="hidden" name="applicant_id" id="interview_outcome_applicant_id">

                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-clipboard-check mr-2"></i>Record Interview Outcome
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label class="required">Outcome</label>
                        <select name="outcome" class="form-control" required>
                            <option value="">Select outcome</option>
                            <option value="excellent">Excellent</option>
                            <option value="good">Good</option>
                            <option value="average">Average</option>
                            <option value="poor">Poor</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Score (out of 100)</label>
                        <input type="number" name="score" class="form-control" min="0" max="100"
                               placeholder="Optional numeric score">
                    </div>

                    <div class="form-group">
                        <label>Interview Notes</label>
                        <textarea name="notes" class="form-control" rows="4"
                                  placeholder="Key observations, strengths, areas of concern..."></textarea>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        After recording the outcome, you can make a final decision (Accept/Waitlist/Reject) or schedule an exam.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="ti ti-x me-1"></i>Cancel</button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-save mr-2"></i>Save Outcome
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Schedule Exam Modal -->
<div class="modal fade" id="examModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="examForm" method="POST" action="/applicants/schedule-exam" data-no-ajax="true">
                <input type="hidden" name="applicant_id" id="exam_applicant_id">

                <div class="modal-header bg-purple text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-pencil-alt mr-2"></i>Schedule Entrance Exam
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="required">Exam Date</label>
                                <input type="date" name="exam_date" class="form-control" required
                                       min="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="required">Exam Time</label>
                                <input type="time" name="exam_time" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Exam Name / Paper</label>
                                <input type="text" name="exam_name" class="form-control"
                                       placeholder="e.g., General Knowledge Test" value="Entrance Exam">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Paper Code</label>
                                <input type="text" name="paper_code" class="form-control"
                                       placeholder="e.g., EE-2025-001">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Exam Center / Venue</label>
                        <input type="text" name="exam_center" class="form-control"
                               placeholder="e.g., Main Hall, Block A">
                    </div>

                    <div class="form-group">
                        <label>Candidate Number</label>
                        <input type="text" name="candidate_number" class="form-control"
                               placeholder="Auto-generated if left blank">
                    </div>

                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="2"
                                  placeholder="Special instructions or requirements"></textarea>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" name="send_sms" value="1" class="form-check-input" id="exam_send_sms" checked>
                        <label class="form-check-label" for="exam_send_sms">
                            Send SMS notification to applicant
                        </label>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" name="send_email" value="1" class="form-check-input" id="exam_send_email" checked>
                        <label class="form-check-label" for="exam_send_email">
                            Send email notification to applicant
                        </label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="ti ti-x me-1"></i>Cancel</button>
                    <button type="submit" class="btn btn-purple">
                        <i class="fas fa-calendar-check mr-2"></i>Schedule Exam
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Record Exam Score Modal -->
<div class="modal fade" id="examScoreModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="examScoreForm" method="POST" action="/applicants/exam-score" data-no-ajax="true">
                <input type="hidden" name="applicant_id" id="exam_score_applicant_id">

                <div class="modal-header bg-purple text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-clipboard-check mr-2"></i>Record Exam Score
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="required">Score</label>
                                <input type="number" name="score" class="form-control" required
                                       min="0" step="0.01" placeholder="e.g., 85">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="required">Total Marks</label>
                                <input type="number" name="total_marks" class="form-control" required
                                       min="0" step="0.01" value="100">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Grade</label>
                        <select name="grade" class="form-control">
                            <option value="">Auto-calculate</option>
                            <option value="A">A - Excellent</option>
                            <option value="B">B - Good</option>
                            <option value="C">C - Average</option>
                            <option value="D">D - Below Average</option>
                            <option value="E">E - Poor</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Exam Notes</label>
                        <textarea name="notes" class="form-control" rows="3"
                                  placeholder="Performance notes, observations..."></textarea>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        After recording the score, you can make a final decision (Accept/Waitlist/Reject).
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="ti ti-x me-1"></i>Cancel</button>
                    <button type="submit" class="btn btn-purple">
                        <i class="fas fa-save mr-2"></i>Save Score
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Accept Application Modal -->
<div class="modal fade" id="acceptModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="acceptForm" method="POST" action="/applicants/decision" data-no-ajax="true">
                <input type="hidden" name="applicant_id" id="accept_applicant_id">
                <input type="hidden" name="decision" value="accepted">

                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-check-circle mr-2"></i>Accept Application
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label class="required">Offer Expiry Date</label>
                        <input type="date" name="offer_expiry_date" class="form-control" required
                               min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                               value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                        <small class="form-text text-muted">Deadline for accepting the offer</small>
                    </div>

                    <div class="form-group">
                        <label>Conditions (Optional)</label>
                        <textarea name="conditions" class="form-control" rows="2"
                                  placeholder="Any conditions for acceptance (e.g., pending documents)"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="2"
                                  placeholder="Internal notes about this decision"></textarea>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" name="send_sms" value="1" class="form-check-input" id="accept_send_sms" checked>
                        <label class="form-check-label" for="accept_send_sms">
                            Send SMS notification with offer details
                        </label>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" name="send_email" value="1" class="form-check-input" id="accept_send_email" checked>
                        <label class="form-check-label" for="accept_send_email">
                            Send email with offer letter
                        </label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="ti ti-x me-1"></i>Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check mr-2"></i>Confirm Acceptance
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Waitlist Application Modal -->
<div class="modal fade" id="waitlistModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="waitlistForm" method="POST" action="/applicants/decision" data-no-ajax="true">
                <input type="hidden" name="applicant_id" id="waitlist_applicant_id">
                <input type="hidden" name="decision" value="waitlisted">

                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-clock mr-2"></i>Waitlist Application
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label>Reason for Waitlisting</label>
                        <select name="waitlist_reason" class="form-control">
                            <option value="">Select reason</option>
                            <option value="capacity">Capacity constraints</option>
                            <option value="pending_slots">Pending confirmation from other applicants</option>
                            <option value="borderline_performance">Borderline performance</option>
                            <option value="other">Other (specify in notes)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="3" required
                                  placeholder="Explain waitlist reason and next steps"></textarea>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" name="send_sms" value="1" class="form-check-input" id="waitlist_send_sms" checked>
                        <label class="form-check-label" for="waitlist_send_sms">
                            Send SMS notification
                        </label>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" name="send_email" value="1" class="form-check-input" id="waitlist_send_email" checked>
                        <label class="form-check-label" for="waitlist_send_email">
                            Send email notification
                        </label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="ti ti-x me-1"></i>Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-clock mr-2"></i>Confirm Waitlist
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Application Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="rejectForm" method="POST" action="/applicants/decision" data-no-ajax="true">
                <input type="hidden" name="applicant_id" id="reject_applicant_id">
                <input type="hidden" name="decision" value="rejected">

                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-times-circle mr-2"></i>Reject Application
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label class="required">Rejection Reason</label>
                        <select name="rejection_reason" class="form-control" required>
                            <option value="">Select reason</option>
                            <option value="age_requirement">Does not meet age requirements</option>
                            <option value="capacity_full">Grade capacity full</option>
                            <option value="incomplete_docs">Incomplete documentation</option>
                            <option value="interview_performance">Interview performance</option>
                            <option value="exam_score">Entrance exam score</option>
                            <option value="background_check">Background check concerns</option>
                            <option value="other">Other (specify in notes)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Additional Notes</label>
                        <textarea name="notes" class="form-control" rows="3"
                                  placeholder="Additional context for rejection"></textarea>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" name="send_sms" value="1" class="form-check-input" id="reject_send_sms" checked>
                        <label class="form-check-label" for="reject_send_sms">
                            Send SMS notification
                        </label>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" name="send_email" value="1" class="form-check-input" id="reject_send_email" checked>
                        <label class="form-check-label" for="reject_send_email">
                            Send email notification
                        </label>
                    </div>

                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Warning:</strong> This action cannot be easily undone. Please ensure all information is correct.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="ti ti-x me-1"></i>Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times mr-2"></i>Confirm Rejection
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.bg-purple {
    background-color: #6f42c1 !important;
}
.btn-purple {
    background-color: #6f42c1;
    color: white;
    border-color: #6f42c1;
}
.btn-purple:hover {
    background-color: #5a32a3;
    border-color: #5a32a3;
    color: white;
}
.required::after {
    content: " *";
    color: #dc3545;
}
</style>

<script>
// Modal functions
function showInterviewModal(applicantId, isReschedule = false) {
    document.getElementById('interview_applicant_id').value = applicantId;
    $('#interviewModal').modal('show');
}

function showInterviewOutcomeModal(applicantId) {
    document.getElementById('interview_outcome_applicant_id').value = applicantId;
    $('#interviewOutcomeModal').modal('show');
}

function showExamModal(applicantId, isReschedule = false) {
    document.getElementById('exam_applicant_id').value = applicantId;
    $('#examModal').modal('show');
}

function showExamScoreModal(applicantId) {
    document.getElementById('exam_score_applicant_id').value = applicantId;
    $('#examScoreModal').modal('show');
}

function showAcceptModal(applicantId) {
    document.getElementById('accept_applicant_id').value = applicantId;
    $('#acceptModal').modal('show');
}

function showWaitlistModal(applicantId) {
    document.getElementById('waitlist_applicant_id').value = applicantId;
    $('#waitlistModal').modal('show');
}

function showRejectModal(applicantId) {
    document.getElementById('reject_applicant_id').value = applicantId;
    $('#rejectModal').modal('show');
}

function cancelInterview(applicantId) {
    if (!confirm('Cancel this interview? This will move the applicant back to Screening stage.')) return;
    // Implement cancel logic
}

function cancelExam(applicantId) {
    if (!confirm('Cancel this exam? This will move the applicant back to Screening stage.')) return;
    // Implement cancel logic
}

function viewDecisionHistory(applicantId) {
    window.location.href = '/applicants/' + applicantId + '#tab-history';
}

// Simple stage transitions
function moveToScreening(applicantId) {
    if (!confirm('Move this applicant to Screening stage?')) return;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/applicants/stage-transition';

    const applicantInput = document.createElement('input');
    applicantInput.type = 'hidden';
    applicantInput.name = 'applicant_id';
    applicantInput.value = applicantId;
    form.appendChild(applicantInput);

    const statusInput = document.createElement('input');
    statusInput.type = 'hidden';
    statusInput.name = 'new_status';
    statusInput.value = 'screening';
    form.appendChild(statusInput);

    document.body.appendChild(form);
    form.submit();
}
</script>
