<?php
/**
 * Step Editor Modal
 * Advanced settings for workflow steps
 */
?>

<!-- Step Editor Modal -->
<div class="modal fade" id="stepEditorModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="stepModalTitle">
                    <i class="fas fa-edit mr-2"></i>Edit Step
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="stepId">

                <!-- Nav tabs -->
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#tabBasic">Basic</a>
                    </li>
                    <li class="nav-item task-only-field">
                        <a class="nav-link" data-toggle="tab" href="#tabAssignment">Assignment</a>
                    </li>
                    <li class="nav-item task-only-field">
                        <a class="nav-link" data-toggle="tab" href="#tabActions">Actions</a>
                    </li>
                    <li class="nav-item task-only-field">
                        <a class="nav-link" data-toggle="tab" href="#tabSla">SLA</a>
                    </li>
                    <li class="nav-item task-only-field">
                        <a class="nav-link" data-toggle="tab" href="#tabForm">Form Fields</a>
                    </li>
                </ul>

                <!-- Tab content -->
                <div class="tab-content">
                    <!-- Basic Tab -->
                    <div class="tab-pane fade show active" id="tabBasic">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Step Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="stepName" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Code <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="stepCode" pattern="[A-Z0-9_]+" title="Uppercase letters, numbers and underscores only">
                                    <small class="form-text text-muted">Unique identifier for this step</small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Step Type</label>
                                    <input type="text" class="form-control" id="stepType" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Icon</label>
                                    <select class="form-control" id="stepIcon">
                                        <option value="">Default</option>
                                        <option value="fas fa-check">Check</option>
                                        <option value="fas fa-file">Document</option>
                                        <option value="fas fa-user">User</option>
                                        <option value="fas fa-envelope">Email</option>
                                        <option value="fas fa-dollar-sign">Money</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" id="stepDescription" rows="3" placeholder="Describe what happens in this step..."></textarea>
                        </div>
                    </div>

                    <!-- Assignment Tab -->
                    <div class="tab-pane fade task-only-field" id="tabAssignment">
                        <div class="form-group">
                            <label class="form-label">Assigned Role(s)</label>
                            <input type="text" class="form-control" id="stepRoles" placeholder="e.g., ADMISSIONS_OFFICER, REGISTRAR">
                            <small class="form-text text-muted">Comma-separated list of roles that can work on this task</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Assignment Mode</label>
                            <select class="form-control" id="stepAssignmentMode">
                                <option value="any">Any user with role (pool)</option>
                                <option value="specific">Specific user</option>
                                <option value="round_robin">Round robin</option>
                                <option value="load_balanced">Load balanced</option>
                            </select>
                            <small class="form-text text-muted">How tasks are assigned to users</small>
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="stepRequireClaim">
                                <label class="custom-control-label" for="stepRequireClaim">Require user to claim task before working</label>
                            </div>
                        </div>
                    </div>

                    <!-- Actions Tab -->
                    <div class="tab-pane fade task-only-field" id="tabActions">
                        <p class="text-muted mb-3">Define the actions available when completing this task.</p>

                        <div id="actionsContainer">
                            <!-- Actions will be added here dynamically -->
                        </div>

                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addAction()">
                            <i class="fas fa-plus mr-1"></i> Add Action
                        </button>

                        <hr>

                        <h6>Common Actions</h6>
                        <div class="btn-group-toggle d-flex flex-wrap gap-2" data-toggle="buttons">
                            <label class="btn btn-sm btn-outline-success mr-2 mb-2">
                                <input type="checkbox" data-action="approve"> Approve
                            </label>
                            <label class="btn btn-sm btn-outline-danger mr-2 mb-2">
                                <input type="checkbox" data-action="reject"> Reject
                            </label>
                            <label class="btn btn-sm btn-outline-warning mr-2 mb-2">
                                <input type="checkbox" data-action="request_info"> Request Info
                            </label>
                            <label class="btn btn-sm btn-outline-info mr-2 mb-2">
                                <input type="checkbox" data-action="escalate"> Escalate
                            </label>
                            <label class="btn btn-sm btn-outline-secondary mr-2 mb-2">
                                <input type="checkbox" data-action="defer"> Defer
                            </label>
                        </div>
                    </div>

                    <!-- SLA Tab -->
                    <div class="tab-pane fade task-only-field" id="tabSla">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">SLA Hours</label>
                                    <input type="number" class="form-control" id="stepSlaHours" min="0" placeholder="e.g., 24">
                                    <small class="form-text text-muted">Expected completion time in hours</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Reminder Hours</label>
                                    <input type="number" class="form-control" id="stepReminderHours" min="0" placeholder="e.g., 20">
                                    <small class="form-text text-muted">Send reminder before SLA expires</small>
                                </div>
                            </div>
                        </div>

                        <h6 class="mt-3">Escalation</h6>
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="stepEscalationEnabled">
                                <label class="custom-control-label" for="stepEscalationEnabled">Enable escalation on SLA breach</label>
                            </div>
                        </div>
                        <div class="row escalation-settings" style="display: none;">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Escalate After (hours)</label>
                                    <input type="number" class="form-control" id="stepEscalationHours" min="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Escalate To Role</label>
                                    <input type="text" class="form-control" id="stepEscalationRole" placeholder="e.g., SUPERVISOR">
                                </div>
                            </div>
                        </div>

                        <h6 class="mt-3">Auto Transition</h6>
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="stepAutoTransition">
                                <label class="custom-control-label" for="stepAutoTransition">Auto-transition if not acted upon</label>
                            </div>
                        </div>
                        <div class="row auto-transition-settings" style="display: none;">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">After (hours)</label>
                                    <input type="number" class="form-control" id="stepAutoTransitionHours" min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Action</label>
                                    <select class="form-control" id="stepAutoTransitionAction">
                                        <option value="approve">Approve</option>
                                        <option value="reject">Reject</option>
                                        <option value="defer">Defer</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Comment</label>
                                    <input type="text" class="form-control" id="stepAutoTransitionComment" placeholder="Auto-approved">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Fields Tab -->
                    <div class="tab-pane fade task-only-field" id="tabForm">
                        <p class="text-muted mb-3">Define custom form fields for this task.</p>

                        <div id="formFieldsContainer">
                            <!-- Form fields will be added here dynamically -->
                        </div>

                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addFormField()">
                            <i class="fas fa-plus mr-1"></i> Add Field
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="WorkflowDesigner.saveStepFromModal()">
                    <i class="fas fa-save mr-1"></i> Save Step
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Step Modal Functions
document.getElementById('stepEscalationEnabled').addEventListener('change', function() {
    document.querySelector('.escalation-settings').style.display = this.checked ? 'flex' : 'none';
});

document.getElementById('stepAutoTransition').addEventListener('change', function() {
    document.querySelector('.auto-transition-settings').style.display = this.checked ? 'flex' : 'none';
});

function addAction() {
    const container = document.getElementById('actionsContainer');
    const index = container.children.length;

    const html = `
        <div class="card mb-2 action-item">
            <div class="card-body p-3">
                <div class="row">
                    <div class="col-md-4">
                        <input type="text" class="form-control form-control-sm" placeholder="Action code" data-field="code">
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control form-control-sm" placeholder="Label" data-field="label">
                    </div>
                    <div class="col-md-3">
                        <select class="form-control form-control-sm" data-field="style">
                            <option value="primary">Primary</option>
                            <option value="success">Success</option>
                            <option value="danger">Danger</option>
                            <option value="warning">Warning</option>
                            <option value="secondary">Secondary</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.action-item').remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', html);
}

function addFormField() {
    const container = document.getElementById('formFieldsContainer');

    const html = `
        <div class="card mb-2 form-field-item">
            <div class="card-body p-3">
                <div class="row mb-2">
                    <div class="col-md-4">
                        <input type="text" class="form-control form-control-sm" placeholder="Field code" data-field="code">
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control form-control-sm" placeholder="Label" data-field="label">
                    </div>
                    <div class="col-md-3">
                        <select class="form-control form-control-sm" data-field="type">
                            <option value="text">Text</option>
                            <option value="textarea">Textarea</option>
                            <option value="select">Dropdown</option>
                            <option value="checkbox">Checkbox</option>
                            <option value="date">Date</option>
                            <option value="number">Number</option>
                            <option value="file">File Upload</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.form-field-item').remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-8">
                        <input type="text" class="form-control form-control-sm" placeholder="Options (comma-separated for dropdowns)" data-field="options">
                    </div>
                    <div class="col-md-4">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="field_required_${Date.now()}" data-field="required">
                            <label class="custom-control-label" for="field_required_${Date.now()}">Required</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', html);
}
</script>
