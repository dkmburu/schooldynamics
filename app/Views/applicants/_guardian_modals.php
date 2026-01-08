<?php
/**
 * Guardian Management Modals - 2-Step Wizard
 * Step 1: Search by ID Number (National ID / Passport)
 * Step 2: Link existing guardian OR create new one
 *
 * Includes:
 * - Field audit trail with modal-based change reason prompts
 * - Tracked fields: id_number, first_name, last_name, phone, email
 */
?>

<!-- Step 1: Search Guardian Modal -->
<div class="modal fade" id="guardianSearchModal" tabindex="-1" aria-labelledby="guardianSearchModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="guardianSearchModalLabel">
                    <i class="ti ti-search me-2"></i>Add Guardian - Step 1
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="search_applicant_id" value="">

                <div class="alert alert-info mb-4">
                    <div class="d-flex">
                        <div class="me-3"><i class="ti ti-info-circle" style="font-size: 1.5rem;"></i></div>
                        <div>
                            <strong>Why we need this</strong>
                            <p class="mb-0 small">By searching with the National ID or Passport number first, we can link existing guardians to multiple children without creating duplicate records.</p>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">National ID / Passport Number <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="ti ti-id"></i></span>
                        <input type="text" class="form-control form-control-lg" id="guardian_search_id"
                               placeholder="Enter ID or Passport number" autocomplete="off">
                        <button class="btn btn-primary" type="button" id="searchGuardianBtn" onclick="searchGuardianById()">
                            <i class="ti ti-search me-1"></i> Search
                        </button>
                    </div>
                    <small class="form-hint">This will be used as the unique identifier for this guardian</small>
                </div>

                <!-- Search Results Container -->
                <div id="guardianSearchResults" style="display: none;">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i> Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Step 2: Add/Edit Guardian Details Modal -->
<div class="modal fade" id="guardianModal" tabindex="-1" aria-labelledby="guardianModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="guardianForm" method="POST" action="/applicants/guardians/store" data-no-ajax="true">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="guardianModalLabel">
                        <i class="ti ti-user-plus me-2"></i>
                        <span id="guardianModalTitle">Add Guardian - Step 2</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="guardian_applicant_id" name="applicant_id" value="<?= $applicant['id'] ?? '' ?>">
                    <input type="hidden" id="guardian_id" name="guardian_id" value="">
                    <input type="hidden" id="guardian_existing" name="existing_guardian" value="0">
                    <input type="hidden" id="guardian_existing_id" name="existing_guardian_id" value="">
                    <!-- Hidden field to store all change reasons as JSON -->
                    <input type="hidden" id="field_change_reasons" name="field_change_reasons" value="{}">

                    <!-- Existing Guardian Alert (shown when linking existing) -->
                    <div id="existingGuardianAlert" class="alert alert-success mb-4" style="display: none;">
                        <div class="d-flex">
                            <div class="me-3"><i class="ti ti-link" style="font-size: 1.5rem;"></i></div>
                            <div>
                                <strong>Linking Existing Guardian</strong>
                                <p class="mb-0 small">This guardian already exists in the system. You're linking them to this applicant.</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- ID Number (read-only when linking existing) -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="guardian_id_number" class="form-label">
                                    National ID / Passport <span class="text-danger">*</span>
                                    <span class="audit-badge badge bg-warning-lt ms-2" data-field="id_number" style="display: none; cursor: pointer;">
                                        <i class="ti ti-history"></i> Modified
                                    </span>
                                </label>
                                <input type="text" class="form-control audited-field" id="guardian_id_number" name="id_number"
                                       data-field-name="id_number" data-field-label="National ID / Passport" data-requires-reason="1" required>
                                <input type="hidden" class="original-value" data-for="id_number" value="">
                            </div>
                        </div>

                        <!-- Relationship -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="guardian_relationship" class="form-label">Relationship <span class="text-danger">*</span></label>
                                <select class="form-select" id="guardian_relationship" name="relationship" required>
                                    <option value="">Select Relationship</option>
                                    <option value="father">Father</option>
                                    <option value="mother">Mother</option>
                                    <option value="legal_guardian">Legal Guardian</option>
                                    <option value="grandparent">Grandparent</option>
                                    <option value="uncle">Uncle</option>
                                    <option value="aunt">Aunt</option>
                                    <option value="sibling">Sibling (Adult)</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- First Name -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="guardian_first_name" class="form-label">
                                    First Name <span class="text-danger">*</span>
                                    <span class="audit-badge badge bg-warning-lt ms-2" data-field="first_name" style="display: none; cursor: pointer;">
                                        <i class="ti ti-history"></i> Modified
                                    </span>
                                </label>
                                <input type="text" class="form-control audited-field" id="guardian_first_name" name="first_name"
                                       data-field-name="first_name" data-field-label="First Name" data-requires-reason="0" required>
                                <input type="hidden" class="original-value" data-for="first_name" value="">
                            </div>
                        </div>

                        <!-- Last Name -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="guardian_last_name" class="form-label">
                                    Last Name <span class="text-danger">*</span>
                                    <span class="audit-badge badge bg-warning-lt ms-2" data-field="last_name" style="display: none; cursor: pointer;">
                                        <i class="ti ti-history"></i> Modified
                                    </span>
                                </label>
                                <input type="text" class="form-control audited-field" id="guardian_last_name" name="last_name"
                                       data-field-name="last_name" data-field-label="Last Name" data-requires-reason="0" required>
                                <input type="hidden" class="original-value" data-for="last_name" value="">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Phone -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="guardian_phone" class="form-label">
                                    Phone <span class="text-danger">*</span>
                                    <span class="audit-badge badge bg-warning-lt ms-2" data-field="phone" style="display: none; cursor: pointer;">
                                        <i class="ti ti-history"></i> Modified
                                    </span>
                                </label>
                                <input type="tel" class="form-control audited-field" id="guardian_phone" name="phone"
                                       data-field-name="phone" data-field-label="Phone Number" data-requires-reason="0" required>
                                <input type="hidden" class="original-value" data-for="phone" value="">
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="guardian_email" class="form-label">
                                    Email
                                    <span class="audit-badge badge bg-warning-lt ms-2" data-field="email" style="display: none; cursor: pointer;">
                                        <i class="ti ti-history"></i> Modified
                                    </span>
                                </label>
                                <input type="email" class="form-control audited-field" id="guardian_email" name="email"
                                       data-field-name="email" data-field-label="Email Address" data-requires-reason="0">
                                <input type="hidden" class="original-value" data-for="email" value="">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Occupation -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="guardian_occupation" class="form-label">Occupation</label>
                                <input type="text" class="form-control" id="guardian_occupation" name="occupation">
                            </div>
                        </div>

                        <!-- Employer -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="guardian_employer" class="form-label">Employer</label>
                                <input type="text" class="form-control" id="guardian_employer" name="employer">
                            </div>
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="mb-3">
                        <label for="guardian_address" class="form-label">Address</label>
                        <textarea class="form-control" id="guardian_address" name="address" rows="2"></textarea>
                    </div>

                    <!-- Is Primary -->
                    <div class="mb-3">
                        <label class="form-check">
                            <input type="checkbox" class="form-check-input" id="guardian_is_primary" name="is_primary" value="1">
                            <span class="form-check-label">
                                <strong>Set as Primary Guardian</strong>
                                <small class="text-muted d-block">The primary guardian will be the main contact for this applicant</small>
                            </span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="ti ti-x me-1"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i> Save Guardian
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Reason Modal (Popup when sensitive fields are changed) -->
<div class="modal fade" id="changeReasonModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning-lt">
                <h5 class="modal-title">
                    <i class="ti ti-alert-triangle me-2 text-warning"></i>
                    <span id="changeReasonModalTitle">Reason for Change</span>
                </h5>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-3">
                    <div class="d-flex">
                        <div class="me-2"><i class="ti ti-info-circle"></i></div>
                        <div>
                            <strong id="changeReasonFieldLabel">Field</strong> is being changed.
                            <span id="changeReasonOldNewValues"></span>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        Why is this change being made? <span id="changeReasonRequiredStar" class="text-danger">*</span>
                    </label>
                    <textarea class="form-control" id="changeReasonInput" rows="3"
                              placeholder="e.g., Correcting typo, Updated after verification, ID was renewed..."></textarea>
                    <small class="text-muted">This will be recorded in the audit trail for compliance purposes.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="changeReasonCancelBtn">
                    <i class="ti ti-x me-1"></i> Cancel Change
                </button>
                <button type="button" class="btn btn-primary" id="changeReasonConfirmBtn">
                    <i class="ti ti-check me-1"></i> Confirm Change
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Field Audit History Modal -->
<div class="modal fade" id="fieldAuditModal" tabindex="-1" aria-labelledby="fieldAuditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning-lt">
                <h5 class="modal-title" id="fieldAuditModalLabel">
                    <i class="ti ti-history me-2"></i>Field Change History
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="fieldAuditContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary"></div>
                        <p class="mt-2 text-muted">Loading history...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// ============================================================================
// Guardian Modal Functions - Audit Trail System
// ============================================================================

// Configuration for audited fields
const AUDITED_FIELDS = {
    'id_number': { label: 'National ID / Passport', requiresReason: true },
    'first_name': { label: 'First Name', requiresReason: false },
    'last_name': { label: 'Last Name', requiresReason: false },
    'phone': { label: 'Phone Number', requiresReason: false },
    'email': { label: 'Email Address', requiresReason: false }
};

// Store for tracking changes during edit session
let pendingChanges = {};
let originalValues = {};
let changeReasons = {};
let currentChangeField = null;
let changeReasonResolve = null;

// ============================================================================
// Search Modal Functions (Step 1)
// ============================================================================

window.showAddGuardianModal = function(applicantId) {
    document.getElementById('search_applicant_id').value = applicantId;
    document.getElementById('guardian_search_id').value = '';
    document.getElementById('guardianSearchResults').style.display = 'none';
    document.getElementById('guardianSearchResults').innerHTML = '';

    var modalEl = document.getElementById('guardianSearchModal');
    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();

    setTimeout(function() {
        document.getElementById('guardian_search_id').focus();
    }, 500);
};

window.searchGuardianById = function() {
    var idNumber = document.getElementById('guardian_search_id').value.trim();
    var applicantId = document.getElementById('search_applicant_id').value;
    var resultsContainer = document.getElementById('guardianSearchResults');

    if (!idNumber) {
        alert('Please enter an ID or Passport number');
        return;
    }

    resultsContainer.style.display = 'block';
    resultsContainer.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div> Searching...</div>';

    fetch('/applicants/guardians/search?id_number=' + encodeURIComponent(idNumber) + '&applicant_id=' + applicantId)
        .then(response => response.json())
        .then(data => {
            if (data.found) {
                resultsContainer.innerHTML = `
                    <div class="card bg-success-lt">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <span class="avatar avatar-lg bg-success text-white me-3">
                                    <i class="ti ti-user-check"></i>
                                </span>
                                <div>
                                    <h4 class="mb-0">${escapeHtml(data.guardian.first_name)} ${escapeHtml(data.guardian.last_name)}</h4>
                                    <div class="text-muted">${escapeHtml(data.guardian.id_number)}</div>
                                </div>
                            </div>
                            <table class="table table-sm table-borderless mb-3">
                                <tr><td class="text-muted" style="width:100px;">Phone:</td><td>${escapeHtml(data.guardian.phone || 'N/A')}</td></tr>
                                <tr><td class="text-muted">Email:</td><td>${escapeHtml(data.guardian.email || 'N/A')}</td></tr>
                                ${data.guardian.linked_children > 0 ? `<tr><td class="text-muted">Children:</td><td><span class="badge bg-blue">${data.guardian.linked_children} already linked</span></td></tr>` : ''}
                            </table>
                            ${data.already_linked ?
                                `<div class="alert alert-warning mb-0">
                                    <i class="ti ti-alert-circle me-2"></i>
                                    This guardian is already linked to this applicant.
                                </div>` :
                                `<button type="button" class="btn btn-success w-100" onclick="linkExistingGuardian(${data.guardian.id}, ${applicantId})">
                                    <i class="ti ti-link me-2"></i> Link This Guardian
                                </button>`
                            }
                        </div>
                    </div>
                `;
            } else {
                resultsContainer.innerHTML = `
                    <div class="card bg-warning-lt">
                        <div class="card-body text-center">
                            <i class="ti ti-user-plus text-warning mb-2" style="font-size: 3rem;"></i>
                            <h4>Guardian Not Found</h4>
                            <p class="text-muted mb-3">No guardian with ID "${escapeHtml(idNumber)}" exists in the system.</p>
                            <button type="button" class="btn btn-warning" onclick="showCreateGuardianForm('${escapeHtml(idNumber)}', ${applicantId})">
                                <i class="ti ti-plus me-2"></i> Create New Guardian
                            </button>
                        </div>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Search error:', error);
            resultsContainer.innerHTML = `
                <div class="alert alert-danger">
                    <i class="ti ti-alert-circle me-2"></i>
                    Error searching for guardian. Please try again.
                </div>
            `;
        });
};

window.linkExistingGuardian = function(guardianId, applicantId) {
    var searchModal = bootstrap.Modal.getInstance(document.getElementById('guardianSearchModal'));
    searchModal.hide();

    fetch('/applicants/guardians/get/' + guardianId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showGuardianForm(data.guardian, applicantId, true);
            }
        });
};

window.showCreateGuardianForm = function(idNumber, applicantId) {
    var searchModal = bootstrap.Modal.getInstance(document.getElementById('guardianSearchModal'));
    searchModal.hide();

    showGuardianForm({ id_number: idNumber }, applicantId, false);
};

// ============================================================================
// Guardian Form Modal (Step 2)
// ============================================================================

window.showGuardianForm = function(guardian, applicantId, isExisting) {
    // Reset everything
    resetGuardianForm();

    document.getElementById('guardianForm').action = '/applicants/guardians/store';
    document.getElementById('guardian_applicant_id').value = applicantId;
    document.getElementById('guardian_id').value = guardian.id || '';
    document.getElementById('guardian_existing').value = isExisting ? '1' : '0';
    document.getElementById('guardian_existing_id').value = isExisting ? (guardian.id || '') : '';

    document.getElementById('guardianModalTitle').textContent = isExisting ? 'Link Guardian' : 'Add Guardian - Step 2';
    document.getElementById('existingGuardianAlert').style.display = isExisting ? 'block' : 'none';

    // Fill form fields
    document.getElementById('guardian_id_number').value = guardian.id_number || '';
    document.getElementById('guardian_first_name').value = guardian.first_name || '';
    document.getElementById('guardian_last_name').value = guardian.last_name || '';
    document.getElementById('guardian_phone').value = guardian.phone || '';
    document.getElementById('guardian_email').value = guardian.email || '';
    document.getElementById('guardian_occupation').value = guardian.occupation || '';
    document.getElementById('guardian_employer').value = guardian.employer || '';
    document.getElementById('guardian_address').value = guardian.address || '';

    // If linking existing, make fields read-only
    var auditedFields = document.querySelectorAll('.audited-field');
    auditedFields.forEach(function(field) {
        field.readOnly = isExisting;
    });

    // Hide all audit badges for new/link mode
    document.querySelectorAll('.audit-badge').forEach(b => b.style.display = 'none');

    setTimeout(function() {
        var modalEl = document.getElementById('guardianModal');
        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }, 300);
};

window.showEditGuardianModal = function(guardianId, data) {
    // Reset everything
    resetGuardianForm();

    document.getElementById('guardianForm').action = '/applicants/guardians/update';
    document.getElementById('guardian_id').value = guardianId;
    document.getElementById('guardian_applicant_id').value = data.applicant_id;
    document.getElementById('guardian_existing').value = '0';
    document.getElementById('guardian_existing_id').value = '';
    document.getElementById('guardianModalTitle').textContent = 'Edit Guardian';
    document.getElementById('existingGuardianAlert').style.display = 'none';

    // Populate form fields and store original values
    setFieldValue('id_number', data.id_number || '');
    setFieldValue('first_name', data.first_name || '');
    setFieldValue('last_name', data.last_name || '');
    setFieldValue('phone', data.phone || '');
    setFieldValue('email', data.email || '');

    document.getElementById('guardian_relationship').value = data.relationship || '';
    document.getElementById('guardian_occupation').value = data.occupation || '';
    document.getElementById('guardian_employer').value = data.employer || '';
    document.getElementById('guardian_address').value = data.address || '';
    document.getElementById('guardian_is_primary').checked = data.is_primary == 1;

    // Make all audited fields editable
    document.querySelectorAll('.audited-field').forEach(function(field) {
        field.readOnly = false;
    });

    // Check audit history for each audited field
    Object.keys(AUDITED_FIELDS).forEach(function(fieldName) {
        checkFieldAuditHistory('applicant_guardians', guardianId, fieldName);
    });

    var modalEl = document.getElementById('guardianModal');
    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
};

function setFieldValue(fieldName, value) {
    var fieldId = 'guardian_' + fieldName;
    var field = document.getElementById(fieldId);
    if (field) {
        field.value = value;
    }
    // Store original value
    var originalInput = document.querySelector('.original-value[data-for="' + fieldName + '"]');
    if (originalInput) {
        originalInput.value = value;
    }
    originalValues[fieldName] = value;
}

function resetGuardianForm() {
    document.getElementById('guardianForm').reset();
    pendingChanges = {};
    originalValues = {};
    changeReasons = {};
    document.getElementById('field_change_reasons').value = '{}';
    document.querySelectorAll('.audit-badge').forEach(b => b.style.display = 'none');
    document.querySelectorAll('.audited-field').forEach(f => f.classList.remove('border-warning'));
}

// ============================================================================
// Change Reason Modal System
// ============================================================================

function showChangeReasonModal(fieldName, oldValue, newValue, requiresReason) {
    return new Promise((resolve, reject) => {
        var config = AUDITED_FIELDS[fieldName];
        var label = config ? config.label : fieldName;

        document.getElementById('changeReasonModalTitle').textContent = 'Reason for Changing ' + label;
        document.getElementById('changeReasonFieldLabel').textContent = label;

        // Show old -> new values
        var valuesHtml = '';
        if (oldValue) {
            valuesHtml = '<br><small class="text-muted">' +
                '<code class="text-danger">' + escapeHtml(oldValue) + '</code> → ' +
                '<code class="text-success">' + escapeHtml(newValue) + '</code></small>';
        }
        document.getElementById('changeReasonOldNewValues').innerHTML = valuesHtml;

        // Show/hide required star
        document.getElementById('changeReasonRequiredStar').style.display = requiresReason ? 'inline' : 'none';

        // Clear previous input
        document.getElementById('changeReasonInput').value = '';

        currentChangeField = fieldName;
        changeReasonResolve = resolve;

        var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('changeReasonModal'));
        modal.show();

        setTimeout(() => {
            document.getElementById('changeReasonInput').focus();
        }, 300);
    });
}

// Confirm button handler
document.getElementById('changeReasonConfirmBtn').addEventListener('click', function() {
    var reason = document.getElementById('changeReasonInput').value.trim();
    var config = AUDITED_FIELDS[currentChangeField];
    var requiresReason = config && config.requiresReason;

    if (requiresReason && !reason) {
        alert('Please provide a reason for this change.');
        document.getElementById('changeReasonInput').focus();
        return;
    }

    // Store the reason
    changeReasons[currentChangeField] = reason;
    document.getElementById('field_change_reasons').value = JSON.stringify(changeReasons);

    // Mark field as having a pending change
    pendingChanges[currentChangeField] = true;
    var fieldEl = document.getElementById('guardian_' + currentChangeField);
    if (fieldEl) {
        fieldEl.classList.add('border-warning');
    }

    var modal = bootstrap.Modal.getInstance(document.getElementById('changeReasonModal'));
    modal.hide();

    if (changeReasonResolve) {
        changeReasonResolve({ confirmed: true, reason: reason });
    }
});

// Cancel button handler
document.getElementById('changeReasonCancelBtn').addEventListener('click', function() {
    // Revert the field to original value
    var originalValue = originalValues[currentChangeField] || '';
    var fieldEl = document.getElementById('guardian_' + currentChangeField);
    if (fieldEl) {
        fieldEl.value = originalValue;
        fieldEl.classList.remove('border-warning');
    }

    // Remove from pending changes
    delete pendingChanges[currentChangeField];
    delete changeReasons[currentChangeField];
    document.getElementById('field_change_reasons').value = JSON.stringify(changeReasons);

    var modal = bootstrap.Modal.getInstance(document.getElementById('changeReasonModal'));
    modal.hide();

    if (changeReasonResolve) {
        changeReasonResolve({ confirmed: false });
    }
});

// ============================================================================
// Audit History Functions
// ============================================================================

window.checkFieldAuditHistory = function(entityType, entityId, fieldName) {
    if (!entityId) return;

    fetch('/api/audit/check?entity_type=' + entityType + '&entity_id=' + entityId + '&field_name=' + fieldName)
        .then(response => response.json())
        .then(data => {
            var badge = document.querySelector('.audit-badge[data-field="' + fieldName + '"]');
            if (badge && data.success && data.has_changes) {
                badge.style.display = 'inline';
                badge.setAttribute('data-change-count', data.change_count);
                badge.onclick = function() {
                    showFieldAuditHistory(entityType, entityId, fieldName);
                };
            }
        })
        .catch(err => console.log('Audit check error:', err));
};

window.showFieldAuditHistory = function(entityType, entityId, fieldName) {
    var content = document.getElementById('fieldAuditContent');
    content.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">Loading history...</p></div>';

    var config = AUDITED_FIELDS[fieldName];
    document.getElementById('fieldAuditModalLabel').innerHTML = '<i class="ti ti-history me-2"></i>' +
        (config ? config.label : fieldName) + ' - Change History';

    var auditModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('fieldAuditModal'));
    auditModal.show();

    fetch('/api/audit/history?entity_type=' + entityType + '&entity_id=' + entityId + '&field_name=' + fieldName)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.history.length > 0) {
                var html = '<div class="table-responsive"><table class="table table-vcenter">';
                html += '<thead><tr><th>Date</th><th>Changed By</th><th>Old Value</th><th>New Value</th><th>Reason</th></tr></thead><tbody>';

                data.history.forEach(function(item) {
                    html += '<tr>';
                    html += '<td><small>' + escapeHtml(item.created_at) + '</small></td>';
                    html += '<td>' + escapeHtml(item.changed_by_name || 'System') + '</td>';
                    html += '<td><code class="text-danger">' + escapeHtml(item.old_value || '-') + '</code></td>';
                    html += '<td><code class="text-success">' + escapeHtml(item.new_value || '-') + '</code></td>';
                    html += '<td><small class="text-muted">' + escapeHtml(item.change_reason || '-') + '</small></td>';
                    html += '</tr>';
                });

                html += '</tbody></table></div>';
                content.innerHTML = html;
            } else {
                content.innerHTML = '<div class="alert alert-info"><i class="ti ti-info-circle me-2"></i>No changes have been recorded for this field.</div>';
            }
        })
        .catch(err => {
            content.innerHTML = '<div class="alert alert-danger"><i class="ti ti-alert-circle me-2"></i>Error loading history.</div>';
        });
};

// ============================================================================
// Other Guardian Actions
// ============================================================================

window.setPrimaryGuardian = function(guardianId, applicantId) {
    if (!confirm('Set this guardian as the primary contact?')) return;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/applicants/guardians/set-primary';

    const guardianInput = document.createElement('input');
    guardianInput.type = 'hidden';
    guardianInput.name = 'guardian_id';
    guardianInput.value = guardianId;
    form.appendChild(guardianInput);

    const applicantInput = document.createElement('input');
    applicantInput.type = 'hidden';
    applicantInput.name = 'applicant_id';
    applicantInput.value = applicantId;
    form.appendChild(applicantInput);

    document.body.appendChild(form);
    form.submit();
};

window.deleteGuardian = function(guardianId, applicantId) {
    if (!confirm('Are you sure you want to remove this guardian from this applicant?')) return;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/applicants/guardians/delete';

    const guardianInput = document.createElement('input');
    guardianInput.type = 'hidden';
    guardianInput.name = 'guardian_id';
    guardianInput.value = guardianId;
    form.appendChild(guardianInput);

    const applicantInput = document.createElement('input');
    applicantInput.type = 'hidden';
    applicantInput.name = 'applicant_id';
    applicantInput.value = applicantId;
    form.appendChild(applicantInput);

    document.body.appendChild(form);
    form.submit();
};

// ============================================================================
// Helper Functions
// ============================================================================

function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ============================================================================
// Event Listeners
// ============================================================================

document.addEventListener('DOMContentLoaded', function() {
    // Enter key triggers search
    var searchInput = document.getElementById('guardian_search_id');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchGuardianById();
            }
        });
    }

    // Detect changes on audited fields and show reason modal
    document.querySelectorAll('.audited-field').forEach(function(field) {
        field.addEventListener('blur', function() {
            var fieldName = this.getAttribute('data-field-name');
            var originalValue = originalValues[fieldName] || '';
            var currentValue = this.value.trim();
            var isEditing = document.getElementById('guardianForm').action.includes('update');

            // Only trigger for edit mode, when value actually changed, and not already processed
            if (isEditing && originalValue !== currentValue && !pendingChanges[fieldName]) {
                var config = AUDITED_FIELDS[fieldName];
                var requiresReason = config && config.requiresReason;

                showChangeReasonModal(fieldName, originalValue, currentValue, requiresReason);
            }
        });
    });

    // Form submission - validate all required change reasons are provided
    var guardianForm = document.getElementById('guardianForm');
    if (guardianForm) {
        guardianForm.addEventListener('submit', function(e) {
            var isEditing = this.action.includes('update');
            if (!isEditing) return true;

            // Check for any changed fields that require reasons
            var missingReasons = [];
            document.querySelectorAll('.audited-field').forEach(function(field) {
                var fieldName = field.getAttribute('data-field-name');
                var originalValue = originalValues[fieldName] || '';
                var currentValue = field.value.trim();
                var config = AUDITED_FIELDS[fieldName];

                if (originalValue !== currentValue && config && config.requiresReason) {
                    if (!changeReasons[fieldName]) {
                        missingReasons.push(config.label);
                    }
                }
            });

            if (missingReasons.length > 0) {
                e.preventDefault();
                alert('Please provide a reason for changing: ' + missingReasons.join(', '));
                return false;
            }

            // Update the hidden field with all reasons
            document.getElementById('field_change_reasons').value = JSON.stringify(changeReasons);
        });
    }
});
</script>
