/**
 * Applicant Profile JavaScript Functions
 * These functions are used by the applicant show/profile page
 * They are extracted to a separate file to work with AJAX navigation
 */

// ========================================
// Edit Applicant Functions
// ========================================

/**
 * Show edit applicant modal with data populated
 */
function showEditApplicantModal(applicantId, applicantData) {
    // Set form values - Personal Information
    document.getElementById('edit_applicant_id').value = applicantId;
    document.getElementById('edit_first_name').value = applicantData.first_name || '';
    document.getElementById('edit_middle_name').value = applicantData.middle_name || '';
    document.getElementById('edit_last_name').value = applicantData.last_name || '';
    document.getElementById('edit_date_of_birth').value = applicantData.date_of_birth || '';
    document.getElementById('edit_gender').value = applicantData.gender || '';
    document.getElementById('edit_nationality').value = applicantData.nationality || '';
    document.getElementById('edit_birth_cert_no').value = applicantData.birth_cert_no || '';

    // Previous School Information
    document.getElementById('edit_previous_school').value = applicantData.previous_school || '';
    document.getElementById('edit_previous_grade').value = applicantData.previous_grade || '';

    // Application Details
    document.getElementById('edit_grade_applying_for_id').value = applicantData.grade_applying_for_id || '';
    document.getElementById('edit_intake_campaign_id').value = applicantData.intake_campaign_id || '';

    // Medical & Additional Information
    document.getElementById('edit_medical_conditions').value = applicantData.medical_conditions || '';
    document.getElementById('edit_special_needs').value = applicantData.special_needs || '';
    document.getElementById('edit_notes').value = applicantData.notes || '';

    // Show modal
    $('#editApplicantModal').modal('show');
}

/**
 * Save applicant details via AJAX
 */
async function saveApplicantDetails() {
    const form = document.getElementById('editApplicantForm');
    const formData = new FormData(form);
    const submitBtn = document.getElementById('btnSaveApplicant');

    // Validate required fields
    const firstName = document.getElementById('edit_first_name').value.trim();
    const lastName = document.getElementById('edit_last_name').value.trim();

    if (!firstName || !lastName) {
        alert('First name and last name are required.');
        return;
    }

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Saving...';

    try {
        const response = await fetch('/applicants/update', {
            method: 'POST',
            body: formData,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        });

        const data = await response.json();

        if (data.success) {
            $('#editApplicantModal').modal('hide');
            alert('Applicant details updated successfully!');
            window.location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to update applicant details'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to update applicant details. Please try again.');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-save mr-1"></i>Save Changes';
    }
}

// ========================================
// Document Tab Functions
// ========================================

/**
 * Toggle document files visibility
 */
function showDocumentFiles(type) {
    const filesDiv = document.getElementById('files-' + type);
    if (filesDiv) {
        filesDiv.style.display = filesDiv.style.display === 'none' ? 'block' : 'none';
    }
}

/**
 * Delete document
 */
function deleteDocument(documentId, applicantId) {
    if (!confirm('Are you sure you want to delete this document? This action cannot be undone.')) return;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/applicants/documents/delete';

    const docInput = document.createElement('input');
    docInput.type = 'hidden';
    docInput.name = 'document_id';
    docInput.value = documentId;
    form.appendChild(docInput);

    const applicantInput = document.createElement('input');
    applicantInput.type = 'hidden';
    applicantInput.name = 'applicant_id';
    applicantInput.value = applicantId;
    form.appendChild(applicantInput);

    document.body.appendChild(form);
    form.submit();
}

// ========================================
// Authorization/Consent System
// ========================================

/**
 * Show authorization request modal
 */
function showAuthorizationModal(applicantId) {
    $('#authorizationModal').modal('show');

    // Reset form
    document.getElementById('authRequestForm').reset();
    document.getElementById('auth_applicant_id').value = applicantId;

    // Load guardian info for prefill
    loadGuardianForAuth(applicantId);
}

/**
 * Load primary guardian info to prefill authorization form
 */
async function loadGuardianForAuth(applicantId) {
    // TODO: Fetch primary guardian info via AJAX
    // For now, we'll let user fill manually
}

/**
 * Send authorization request
 */
async function sendAuthorizationRequest() {
    const form = document.getElementById('authRequestForm');
    const formData = new FormData(form);
    const submitBtn = document.getElementById('btnSendAuthorization');

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Sending...';

    try {
        console.log('Sending authorization request to /applicants/authorization/send');
        console.log('Form data:', Object.fromEntries(formData));

        const response = await fetch('/applicants/authorization/send', {
            method: 'POST',
            body: formData,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        });

        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);

        const responseText = await response.text();
        console.log('Response text:', responseText);

        let data;
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error('Failed to parse JSON:', e);
            throw new Error('Server returned invalid JSON: ' + responseText.substring(0, 200));
        }

        console.log('Parsed data:', data);

        if (data.success) {
            $('#authorizationModal').modal('hide');

            // Show success with code for testing
            alert(`Authorization request sent successfully!\n\nFor testing:\nVerification Code: ${data.verification_code}\nLink: ${data.authorization_url}`);

            // Reload page to show updated badge
            window.location.reload();
        } else {
            console.error('Server error details:', data.error_details);
            console.error('Stack trace:', data.trace);
            alert('Error: ' + data.message + '\n\nDetails: ' + (data.error_details || 'No details available'));
        }
    } catch (error) {
        console.error('Full error:', error);
        alert('Error: ' + error.message);
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-paper-plane mr-1"></i>Send Request';
    }
}

/**
 * Show enter code modal (staff-assisted authorization)
 */
function showEnterCodeModal(applicantId) {
    $('#enterCodeModal').modal('show');
    document.getElementById('code_applicant_id').value = applicantId;
    document.getElementById('verification_code_input').value = '';
    document.getElementById('staff_notes').value = '';
}

/**
 * Submit authorization code (staff-assisted)
 */
async function submitAuthorizationCode() {
    const form = document.getElementById('enterCodeForm');
    const formData = new FormData(form);
    const submitBtn = document.getElementById('btnSubmitCode');

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Verifying...';

    try {
        const response = await fetch('/applicants/authorization/approve-by-code', {
            method: 'POST',
            body: formData,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        });

        const data = await response.json();

        if (data.success) {
            $('#enterCodeModal').modal('hide');
            alert('Authorization approved successfully!');
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to verify code. Please try again.');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-check mr-1"></i>Submit Code';
    }
}

/**
 * Show authorization history modal and load data
 */
async function showAuthorizationHistoryModal(applicantId) {
    $('#authHistoryModal').modal('show');

    // Show loading state
    document.getElementById('authHistoryContent').innerHTML = `
        <div class="text-center py-5">
            <i class="fas fa-spinner fa-spin fa-3x text-muted"></i>
            <p class="mt-3 text-muted">Loading authorization history...</p>
        </div>
    `;

    try {
        const response = await fetch(`/applicants/${applicantId}/authorization-history`, {
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        });

        const data = await response.json();

        if (data.success && data.history) {
            if (data.history.length === 0) {
                document.getElementById('authHistoryContent').innerHTML = `
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        No authorization requests have been made for this applicant yet.
                    </div>
                `;
            } else {
                // Build table HTML
                let html = `
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Authorization Type</th>
                                    <th>Action</th>
                                    <th>Actor</th>
                                    <th>Method</th>
                                    <th>Code</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                data.history.forEach(record => {
                    if (!record.action_date) return;

                    const date = new Date(record.action_date);
                    const formattedDate = date.toLocaleDateString('en-US', {
                        month: 'short', day: 'numeric', year: 'numeric',
                        hour: 'numeric', minute: '2-digit', hour12: true
                    });

                    const requestType = record.request_type.replace(/_/g, ' ')
                        .split(' ')
                        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                        .join(' ');

                    let methodBadge = '-';
                    if (record.authorization_method === 'link') {
                        methodBadge = '<span class="badge badge-success"><i class="fas fa-link mr-1"></i>Link</span>';
                    } else if (record.authorization_method === 'code_staff') {
                        methodBadge = '<span class="badge badge-primary"><i class="fas fa-user-shield mr-1"></i>Staff</span>';
                    } else if (record.authorization_method) {
                        methodBadge = `<span class="badge badge-secondary">${record.authorization_method}</span>`;
                    }

                    const statusBadges = {
                        'approved': 'badge-success',
                        'pending': 'badge-warning',
                        'rejected': 'badge-danger',
                        'expired': 'badge-secondary'
                    };
                    const badgeClass = statusBadges[record.status] || 'badge-secondary';
                    const statusText = record.status.charAt(0).toUpperCase() + record.status.slice(1);

                    html += `
                        <tr>
                            <td><small>${formattedDate}</small></td>
                            <td><span class="badge badge-info">${requestType}</span></td>
                            <td><small>${record.action_description || '-'}</small></td>
                            <td><small>${record.actor_name || record.actor_type || '-'}</small></td>
                            <td>${methodBadge}</td>
                            <td>${record.masked_code ? `<code class="text-muted" style="font-size: 0.9rem;">${record.masked_code}</code>` : '<span class="text-muted">-</span>'}</td>
                            <td><span class="badge ${badgeClass}">${statusText}</span></td>
                        </tr>
                    `;
                });

                html += `
                            </tbody>
                        </table>
                    </div>
                `;

                document.getElementById('authHistoryContent').innerHTML = html;
            }
        } else {
            document.getElementById('authHistoryContent').innerHTML = `
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Unable to load authorization history. Please try again.
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading authorization history:', error);
        document.getElementById('authHistoryContent').innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle mr-2"></i>
                Failed to load authorization history. Please try again.
            </div>
        `;
    }
}
