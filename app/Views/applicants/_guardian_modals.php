<?php
/**
 * Guardian Management Modals
 */
?>

<!-- Add/Edit Guardian Modal -->
<div class="modal fade" id="guardianModal" tabindex="-1" aria-labelledby="guardianModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="guardianForm" method="POST" action="/applicants/guardians/store" data-no-ajax="true">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="guardianModalLabel">
                        <i class="ti ti-user-plus me-2"></i>
                        <span id="guardianModalTitle">Add Guardian</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="guardian_applicant_id" name="applicant_id" value="<?= $applicant['id'] ?? '' ?>">
                    <input type="hidden" id="guardian_id" name="guardian_id" value="">

                    <div class="row">
                        <!-- First Name -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="guardian_first_name">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="guardian_first_name" name="first_name" required>
                            </div>
                        </div>

                        <!-- Last Name -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="guardian_last_name">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="guardian_last_name" name="last_name" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Relationship -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="guardian_relationship">Relationship <span class="text-danger">*</span></label>
                                <select class="form-control" id="guardian_relationship" name="relationship" required>
                                    <option value="">Select Relationship</option>
                                    <option value="father">Father</option>
                                    <option value="mother">Mother</option>
                                    <option value="legal_guardian">Legal Guardian</option>
                                    <option value="grandparent">Grandparent</option>
                                    <option value="uncle">Uncle</option>
                                    <option value="aunt">Aunt</option>
                                    <option value="sibling">Sibling</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>

                        <!-- Phone -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="guardian_phone">Phone <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="guardian_phone" name="phone" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Email -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="guardian_email">Email</label>
                                <input type="email" class="form-control" id="guardian_email" name="email">
                            </div>
                        </div>

                        <!-- ID Number -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="guardian_id_number">ID/Passport Number</label>
                                <input type="text" class="form-control" id="guardian_id_number" name="id_number">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Occupation -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="guardian_occupation">Occupation</label>
                                <input type="text" class="form-control" id="guardian_occupation" name="occupation">
                            </div>
                        </div>

                        <!-- Employer -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="guardian_employer">Employer</label>
                                <input type="text" class="form-control" id="guardian_employer" name="employer">
                            </div>
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="form-group">
                        <label for="guardian_address">Address</label>
                        <textarea class="form-control" id="guardian_address" name="address" rows="2"></textarea>
                    </div>

                    <!-- Is Primary -->
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="guardian_is_primary" name="is_primary" value="1">
                            <label class="custom-control-label" for="guardian_is_primary">
                                Set as Primary Guardian
                                <small class="text-muted d-block">The primary guardian will be the main contact for this applicant</small>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="ti ti-x me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-2"></i>Save Guardian
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Open add guardian modal
function showAddGuardianModal(applicantId) {
    // Reset form
    document.getElementById('guardianForm').reset();
    document.getElementById('guardianForm').action = '/applicants/guardians/store';
    document.getElementById('guardian_applicant_id').value = applicantId;
    document.getElementById('guardian_id').value = '';
    document.getElementById('guardianModalTitle').textContent = 'Add Guardian';

    // Show modal
    $('#guardianModal').modal('show');
}

// Open edit guardian modal
function showEditGuardianModal(guardianId, data) {
    // Set form action to update
    document.getElementById('guardianForm').action = '/applicants/guardians/update';
    document.getElementById('guardian_id').value = guardianId;
    document.getElementById('guardian_applicant_id').value = data.applicant_id;
    document.getElementById('guardianModalTitle').textContent = 'Edit Guardian';

    // Populate form fields
    document.getElementById('guardian_first_name').value = data.first_name || '';
    document.getElementById('guardian_last_name').value = data.last_name || '';
    document.getElementById('guardian_relationship').value = data.relationship || '';
    document.getElementById('guardian_phone').value = data.phone || '';
    document.getElementById('guardian_email').value = data.email || '';
    document.getElementById('guardian_id_number').value = data.id_number || '';
    document.getElementById('guardian_occupation').value = data.occupation || '';
    document.getElementById('guardian_employer').value = data.employer || '';
    document.getElementById('guardian_address').value = data.address || '';
    document.getElementById('guardian_is_primary').checked = data.is_primary == 1;

    // Show modal
    $('#guardianModal').modal('show');
}

// Set guardian as primary
function setPrimaryGuardian(guardianId, applicantId) {
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
}

// Delete guardian
function deleteGuardian(guardianId, applicantId) {
    if (!confirm('Are you sure you want to remove this guardian? This action cannot be undone.')) return;

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
}
</script>
