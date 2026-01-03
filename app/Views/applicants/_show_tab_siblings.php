<?php
/**
 * Applicant Profile - Siblings/Family Tab
 */
?>

<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0" style="font-size: 14px; font-weight: 600; color: #605e5c;">Siblings & Family Members</h5>
        <?php if (hasPermission('Students.write') || Gate::hasRole('ADMIN')): ?>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSiblingModal">
            <i class="ti ti-user-plus me-2"></i>Add Family Member
        </button>
        <?php endif; ?>
    </div>

    <!-- Current Family Members -->
    <div class="card">
        <div class="card-header bg-light">
            <h6 class="mb-0" style="font-size: 13px; font-weight: 600; color: #605e5c;">
                <i class="ti ti-users me-2"></i>Current Family Members
                <span class="badge bg-primary ms-2"><?= count($siblings) ?></span>
            </h6>
        </div>
        <div class="card-body p-0">
            <?php if (empty($siblings)): ?>
                <div class="text-center text-muted py-4">
                    <i class="ti ti-users" style="font-size: 3rem;"></i>
                    <p class="mt-3">No family members linked yet.</p>
                    <?php if (hasPermission('Students.write') || Gate::hasRole('ADMIN')): ?>
                        <p class="small">Click "Add Family Member" above to link siblings or family members.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th style="width: 90px;">Type</th>
                                <th style="width: 110px;">Number</th>
                                <th style="width: 90px;">Grade</th>
                                <th style="width: 100px;">Balance</th>
                                <th style="width: 85px;">Status</th>
                                <th style="width: 95px;">Admitted</th>
                                <th style="width: 90px;" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($siblings as $sibling): ?>
                            <tr>
                                <td>
                                    <strong><?= e($sibling['full_name']) ?></strong>
                                    <?php if ($sibling['is_primary']): ?>
                                        <span class="badge bg-warning ms-2" title="Primary Contact">
                                            <i class="ti ti-star"></i> Primary
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($sibling['relationship']) && $sibling['relationship'] !== 'sibling'): ?>
                                        <br><small class="text-muted"><?= ucfirst(e($sibling['relationship'])) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($sibling['sibling_type'] === 'student'): ?>
                                        <span class="badge bg-success">
                                            <i class="ti ti-school"></i> Student
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-info">
                                            <i class="ti ti-clock"></i> Applicant
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <code><?= e($sibling['number']) ?></code>
                                </td>
                                <td><?= e($sibling['grade'] ?? 'N/A') ?></td>
                                <td>
                                    <?php if ($sibling['sibling_type'] === 'student'): ?>
                                        <?php if (isset($sibling['fee_balance'])): ?>
                                            <span class="<?= $sibling['fee_balance'] > 0 ? 'text-danger' : 'text-success' ?>">
                                                KES <?= number_format($sibling['fee_balance']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($sibling['sibling_type'] === 'student'): ?>
                                        <?php
                                        $statusBadges = [
                                            'active' => 'success',
                                            'suspended' => 'danger',
                                            'transferred' => 'warning',
                                            'graduated' => 'info',
                                            'withdrawn' => 'secondary'
                                        ];
                                        $badgeClass = $statusBadges[$sibling['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $badgeClass ?>">
                                            <?= ucfirst(e($sibling['status'])) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">
                                            <?= ucfirst(e($sibling['status'])) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($sibling['admission_date'])): ?>
                                        <small><?= date('M d, Y', strtotime($sibling['admission_date'])) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($sibling['sibling_type'] === 'student'): ?>
                                            <a href="/students/<?= $sibling['sibling_id'] ?>" class="btn btn-sm btn-outline-info" title="View Profile">
                                                <i class="ti ti-eye"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="/applicants/<?= $sibling['sibling_id'] ?>" class="btn btn-sm btn-outline-info" title="View Profile">
                                                <i class="ti ti-eye"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (hasPermission('Students.write') || Gate::hasRole('ADMIN')): ?>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteSibling(<?= $sibling['id'] ?>, <?= $applicant['id'] ?>)" title="Remove">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Sibling Modal -->
<div class="modal fade" id="addSiblingModal" tabindex="-1" aria-labelledby="addSiblingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addSiblingModalLabel">
                    <i class="ti ti-user-plus me-2"></i>Add Family Member
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addSiblingForm" data-no-ajax>
                    <input type="hidden" name="applicant_id" value="<?= $applicant['id'] ?>">
                    <input type="hidden" name="sibling_type" id="modal_sibling_type">
                    <input type="hidden" name="sibling_id" id="modal_sibling_id">

                    <!-- Step 1: Search for Family Member -->
                    <div id="step_search">
                        <h6 class="mb-3"><strong>Step 1:</strong> Search for Student or Applicant</h6>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="mb-2">Search In:</label>
                                <div class="btn-group" role="group">
                                    <input type="checkbox" class="btn-check" id="modal_search_students" checked autocomplete="off">
                                    <label class="btn btn-outline-primary" for="modal_search_students">
                                        <i class="ti ti-school me-1"></i> Students
                                    </label>
                                    <input type="checkbox" class="btn-check" id="modal_search_applicants" checked autocomplete="off">
                                    <label class="btn btn-outline-primary" for="modal_search_applicants">
                                        <i class="ti ti-clock me-1"></i> Applicants
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="modal_sibling_search" class="form-label">Search by Name or Number:</label>
                            <input type="text" class="form-control form-control-lg" id="modal_sibling_search" placeholder="Type name, admission number, or application number..." autocomplete="off">
                            <div class="form-text">Type at least 2 characters to search</div>
                        </div>

                        <div id="modal_search_results" class="list-group" style="display: none; max-height: 300px; overflow-y: auto;"></div>
                    </div>

                    <!-- Step 2: Relationship Details (Hidden until selection) -->
                    <div id="step_details" style="display: none;">
                        <h6 class="mb-3"><strong>Step 2:</strong> Specify Relationship Details</h6>

                        <div class="alert alert-success">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>Selected:</strong> <span id="modal_selected_name"></span>
                                    <span class="badge bg-info ms-2" id="modal_selected_badge"></span>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearModalSelection()">
                                    <i class="ti ti-x"></i> Change
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="modal_relationship" class="form-label">Relationship: <span class="text-danger">*</span></label>
                            <select class="form-select" name="relationship" id="modal_relationship" required>
                                <option value="sibling">Sibling</option>
                                <option value="twin">Twin</option>
                                <option value="half-sibling">Half-sibling</option>
                                <option value="step-sibling">Step-sibling</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="modal_sibling_notes" class="form-label">Additional Notes (Optional):</label>
                            <textarea class="form-control" name="notes" id="modal_sibling_notes" rows="3" placeholder="Any additional information about this family relationship..."></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary" id="btnSaveSibling" style="display: none;" onclick="submitSiblingForm()">
                    <i class="ti ti-check me-1"></i>Add Family Member
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Modal Sibling search functionality - IIFE for immediate execution
(function initializeModalSiblingSearch() {
    console.log('=== Initializing Modal Sibling Search ===');

    let searchTimeout;
    const siblingSearch = document.getElementById('modal_sibling_search');
    const searchResults = document.getElementById('modal_search_results');

    if (!siblingSearch) {
        console.error('ERROR: modal_sibling_search element not found!');
        return;
    }

    if (!searchResults) {
        console.error('ERROR: modal_search_results element not found!');
        return;
    }

    console.log('Modal elements found successfully');

    siblingSearch.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();

        console.log('Modal input event triggered, query:', query);

        if (query.length < 2) {
            searchResults.style.display = 'none';
            return;
        }

        searchTimeout = setTimeout(() => {
            console.log('Calling searchFamilyMembers with:', query);
            searchModalFamilyMembers(query);
        }, 300);
    });

    console.log('=== Modal Sibling Search Initialized Successfully ===');

    // Reset modal when it's opened (using vanilla JavaScript for better compatibility)
    const addSiblingModal = document.getElementById('addSiblingModal');
    if (addSiblingModal) {
        addSiblingModal.addEventListener('show.bs.modal', function() {
            console.log('Modal opening, resetting form');
            clearModalSelection();
            document.getElementById('modal_sibling_search').value = '';
            document.getElementById('modal_search_results').style.display = 'none';
        });
    }
})();

async function searchModalFamilyMembers(query) {
    console.log('searchModalFamilyMembers called with query:', query);

    const searchStudents = document.getElementById('modal_search_students').checked;
    const searchApplicants = document.getElementById('modal_search_applicants').checked;
    const searchResults = document.getElementById('modal_search_results');

    console.log('Search options - Students:', searchStudents, 'Applicants:', searchApplicants);

    if (!searchStudents && !searchApplicants) {
        searchResults.innerHTML = '<div class="list-group-item text-warning">Please select at least one search type</div>';
        searchResults.style.display = 'block';
        return;
    }

    try {
        const url = `/applicants/siblings/search?q=${encodeURIComponent(query)}&students=${searchStudents}&applicants=${searchApplicants}&applicant_id=<?= $applicant['id'] ?>`;
        console.log('Fetching:', url);

        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        console.log('Response status:', response.status);

        const data = await response.json();
        console.log('Response data:', data);

        if (data.results && data.results.length > 0) {
            console.log('Found', data.results.length, 'results');
            searchResults.innerHTML = data.results.map(result => `
                <a href="#" class="list-group-item list-group-item-action" onclick="selectModalSibling(event, ${result.id}, '${result.type}', '${result.name}', '${result.number}', '${result.grade || ''}')">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${result.name}</strong>
                            <span class="badge bg-${result.type === 'student' ? 'success' : 'info'} ms-2">
                                ${result.type === 'student' ? 'Student' : 'Applicant'}
                            </span>
                            <br>
                            <small class="text-muted">${result.number}${result.grade ? ' - ' + result.grade : ''}</small>
                        </div>
                    </div>
                </a>
            `).join('');
            searchResults.style.display = 'block';
        } else {
            console.log('No results found');
            searchResults.innerHTML = '<div class="list-group-item text-muted">No results found</div>';
            searchResults.style.display = 'block';
        }
    } catch (error) {
        console.error('Search error:', error);
        searchResults.innerHTML = '<div class="list-group-item text-danger">Search failed. Please try again.</div>';
        searchResults.style.display = 'block';
    }
}

function selectModalSibling(event, id, type, name, number, grade) {
    event.preventDefault();

    document.getElementById('modal_sibling_type').value = type;
    document.getElementById('modal_sibling_id').value = id;
    document.getElementById('modal_selected_name').textContent = name;
    document.getElementById('modal_selected_badge').textContent = number + (grade ? ' - ' + grade : '');
    document.getElementById('modal_selected_badge').className = 'badge bg-' + (type === 'student' ? 'success' : 'info') + ' ms-2';

    // Show step 2 and hide step 1
    document.getElementById('step_search').style.display = 'none';
    document.getElementById('step_details').style.display = 'block';
    document.getElementById('btnSaveSibling').style.display = 'inline-block';
    document.getElementById('modal_search_results').style.display = 'none';
}

function clearModalSelection() {
    // Reset to step 1
    document.getElementById('step_search').style.display = 'block';
    document.getElementById('step_details').style.display = 'none';
    document.getElementById('btnSaveSibling').style.display = 'none';

    // Clear values
    document.getElementById('modal_sibling_search').value = '';
    document.getElementById('modal_sibling_notes').value = '';
    document.getElementById('modal_relationship').value = 'sibling';
    document.getElementById('modal_search_results').style.display = 'none';
}

// Add sibling form submission function
async function submitSiblingForm() {
    const form = document.getElementById('addSiblingForm');
    if (!form) {
        console.error('addSiblingForm not found');
        return;
    }

    console.log('Form submit triggered');

    const formData = new FormData(form);

    // Log form data for debugging
    console.log('Form data:');
    for (let [key, value] of formData.entries()) {
        console.log(`  ${key}: ${value}`);
    }

    // Disable button to prevent double submission
    const btnSave = document.getElementById('btnSaveSibling');
    btnSave.disabled = true;
    btnSave.innerHTML = '<i class="ti ti-loader ti-spin me-1"></i>Saving...';

    try {
        console.log('Submitting to /applicants/siblings/store');
        const response = await fetch('/applicants/siblings/store', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        console.log('Response status:', response.status);

        const data = await response.json();
        console.log('Response data:', data);

        if (data.success) {
            // Close modal using Bootstrap 5 API
            const modal = bootstrap.Modal.getInstance(document.getElementById('addSiblingModal'));
            if (modal) modal.hide();

            // Show success message
            alert('Family member added successfully!');

            // Reload but keep the hash to stay on current tab
            const currentHash = window.location.hash || '#tab-siblings';
            window.location.href = window.location.pathname + window.location.search + currentHash;
            window.location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to add family member'));
            btnSave.disabled = false;
            btnSave.innerHTML = '<i class="ti ti-check me-1"></i>Add Family Member';
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to add family member. Please try again.');
        btnSave.disabled = false;
        btnSave.innerHTML = '<i class="ti ti-check me-1"></i>Add Family Member';
    }
}

// Delete sibling
function deleteSibling(siblingId, applicantId) {
    if (!confirm('Are you sure you want to remove this family member? This action cannot be undone.')) {
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    // Preserve the current tab hash when submitting
    const currentHash = window.location.hash || '#tab-siblings';
    form.action = '/applicants/siblings/delete';
    form.setAttribute('data-no-ajax', 'true');  // Prevent AJAX router from intercepting

    const siblingIdInput = document.createElement('input');
    siblingIdInput.type = 'hidden';
    siblingIdInput.name = 'sibling_id';
    siblingIdInput.value = siblingId;
    form.appendChild(siblingIdInput);

    const applicantIdInput = document.createElement('input');
    applicantIdInput.type = 'hidden';
    applicantIdInput.name = 'applicant_id';
    applicantIdInput.value = applicantId;
    form.appendChild(applicantIdInput);

    // Add hidden input to preserve hash after redirect
    const hashInput = document.createElement('input');
    hashInput.type = 'hidden';
    hashInput.name = '_redirect_hash';
    hashInput.value = currentHash;
    form.appendChild(hashInput);

    document.body.appendChild(form);
    form.submit();
}
</script>
