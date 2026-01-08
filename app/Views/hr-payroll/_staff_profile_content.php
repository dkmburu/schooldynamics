<?php
/**
 * Staff Profile View Content
 * Read-only view of staff information with tabs
 */

$staff = $staff ?? [];
$qualifications = $qualifications ?? [];
$employmentHistory = $employmentHistory ?? [];
$references = $references ?? [];
$documents = $documents ?? [];
$currency = $_SESSION['currency'] ?? 'KES';
?>

<style>
.profile-header {
    background: linear-gradient(135deg, #206bc4 0%, #1a5b9d 100%);
    padding: 2rem;
    color: white;
    border-radius: 0.5rem 0.5rem 0 0;
}
.profile-avatar {
    width: 120px;
    height: 150px;
    border: 4px solid white;
    border-radius: 4px;
    object-fit: cover;
    background: #f8fafc;
}
.profile-avatar-placeholder {
    width: 120px;
    height: 150px;
    border: 4px solid white;
    border-radius: 4px;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
}
.info-label {
    font-weight: 500;
    color: #626976;
    font-size: 0.8125rem;
}
.info-value {
    color: #1e293b;
}
.nav-tabs-profile .nav-link {
    padding: 0.5rem 0.75rem;
    font-size: 0.8125rem;
    white-space: nowrap;
}
.nav-tabs-profile .nav-link.active {
    border-bottom: 2px solid #206bc4;
    font-weight: 600;
}
@media (max-width: 768px) {
    .nav-tabs-profile {
        flex-wrap: nowrap;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .nav-tabs-profile .nav-link {
        padding: 0.5rem;
    }
}
</style>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <a href="/hr-payroll/staff" class="btn btn-outline-primary btn-sm">
                    <i class="ti ti-arrow-left me-1"></i>Back
                </a>
            </div>
            <div class="col-auto ms-auto">
                <a href="/hr-payroll/staff/<?= $staff['id'] ?>/edit" class="btn btn-primary">
                    <i class="ti ti-edit me-1"></i>Edit Profile
                </a>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="card">
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <?php if (!empty($staff['photo'])): ?>
                        <img src="<?= e($staff['photo']) ?>" class="profile-avatar" alt="Photo">
                        <?php else: ?>
                        <div class="profile-avatar-placeholder">
                            <i class="ti ti-user" style="font-size: 3rem; opacity: 0.5;"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="col">
                        <div class="mb-1">
                            <span class="badge bg-success-lt text-white"><?= ucfirst($staff['status'] ?? 'active') ?></span>
                            <?php if (!empty($staff['employment_type'])): ?>
                            <span class="badge bg-blue-lt text-white"><?= ucfirst(str_replace('_', ' ', $staff['employment_type'])) ?></span>
                            <?php endif; ?>
                        </div>
                        <h2 class="mb-1">
                            <?= e(($staff['title'] ?? '') . ' ' . ($staff['first_name'] ?? '') . ' ' . ($staff['middle_name'] ?? '') . ' ' . ($staff['last_name'] ?? '')) ?>
                        </h2>
                        <div class="text-white-50">
                            <?= e($staff['job_title'] ?? 'No designation') ?>
                            <?php if (!empty($staff['department'])): ?>
                            &bull; <?= e($staff['department']) ?>
                            <?php endif; ?>
                        </div>
                        <div class="mt-2">
                            <span class="me-3"><i class="ti ti-id me-1"></i><?= e($staff['staff_number'] ?? 'N/A') ?></span>
                            <?php if (!empty($staff['email'])): ?>
                            <span class="me-3"><i class="ti ti-mail me-1"></i><?= e($staff['email']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($staff['phone'])): ?>
                            <span><i class="ti ti-phone me-1"></i><?= e($staff['phone']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Tabs -->
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs nav-tabs-profile nav-fill" data-bs-toggle="tabs">
                    <li class="nav-item">
                        <a href="#profile-overview" class="nav-link active" data-bs-toggle="tab" title="Personal Information">
                            <i class="ti ti-user me-1"></i>Personal
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#profile-employment" class="nav-link" data-bs-toggle="tab" title="Employment Details">
                            <i class="ti ti-briefcase me-1"></i>Job
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#profile-salary" class="nav-link" data-bs-toggle="tab" title="Salary & Compensation">
                            <i class="ti ti-cash me-1"></i>Salary
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#profile-education" class="nav-link" data-bs-toggle="tab" title="Education & Qualifications">
                            <i class="ti ti-school me-1"></i>Education
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#profile-history" class="nav-link" data-bs-toggle="tab" title="Work Experience">
                            <i class="ti ti-briefcase-2 me-1"></i>History
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#profile-documents" class="nav-link" data-bs-toggle="tab" title="Documents">
                            <i class="ti ti-files me-1"></i>Docs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#profile-references" class="nav-link" data-bs-toggle="tab" title="References">
                            <i class="ti ti-users me-1"></i>Refs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#profile-account" class="nav-link" data-bs-toggle="tab" title="User Account">
                            <i class="ti ti-key me-1"></i>Access
                        </a>
                    </li>
                </ul>
            </div>

            <div class="card-body">
                <div class="tab-content">
                    <!-- Overview Tab -->
                    <div class="tab-pane active show" id="profile-overview">
                        <div class="row">
                            <div class="col-md-6">
                                <h4 class="mb-3">Personal Information</h4>
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="info-label">Full Name</div>
                                        <div class="info-value"><?= e(($staff['title'] ?? '') . ' ' . ($staff['first_name'] ?? '') . ' ' . ($staff['middle_name'] ?? '') . ' ' . ($staff['last_name'] ?? '')) ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-label">Gender</div>
                                        <div class="info-value"><?= ucfirst($staff['gender'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-label">Date of Birth</div>
                                        <div class="info-value"><?= !empty($staff['date_of_birth']) ? date('j M Y', strtotime($staff['date_of_birth'])) : 'N/A' ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-label">ID Number</div>
                                        <div class="info-value"><?= e($staff['id_number'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-label">Nationality</div>
                                        <div class="info-value"><?= e($staff['nationality'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-label">Marital Status</div>
                                        <div class="info-value"><?= ucfirst($staff['marital_status'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-label">Religion</div>
                                        <div class="info-value"><?= e($staff['religion'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-label">Blood Group</div>
                                        <div class="info-value"><?= e($staff['blood_group'] ?? 'N/A') ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h4 class="mb-3">Contact Information</h4>
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="info-label">Email</div>
                                        <div class="info-value"><?= e($staff['email'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-label">Phone</div>
                                        <div class="info-value"><?= e($staff['phone'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-label">Alt. Phone</div>
                                        <div class="info-value"><?= e($staff['alt_phone'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-label">Postal Address</div>
                                        <div class="info-value"><?= e($staff['postal_address'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="col-12">
                                        <div class="info-label">Physical Address</div>
                                        <div class="info-value"><?= e($staff['physical_address'] ?? 'N/A') ?></div>
                                    </div>
                                </div>

                                <h4 class="mb-3 mt-4">Emergency Contact</h4>
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="info-label">Name</div>
                                        <div class="info-value"><?= e($staff['emergency_contact_name'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-label">Relationship</div>
                                        <div class="info-value"><?= e($staff['emergency_contact_relationship'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-label">Phone</div>
                                        <div class="info-value"><?= e($staff['emergency_contact_phone'] ?? 'N/A') ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Employment Tab -->
                    <div class="tab-pane" id="profile-employment">
                        <div class="row">
                            <div class="col-md-6">
                                <h4 class="mb-3">Position Details</h4>
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="info-label">Staff Number</div>
                                        <div class="info-value"><?= e($staff['staff_number'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-label">Staff Type</div>
                                        <div class="info-value"><?= ucfirst(str_replace('_', ' ', $staff['staff_type'] ?? 'N/A')) ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-label">Employment Type</div>
                                        <div class="info-value"><?= ucfirst(str_replace('_', ' ', $staff['employment_type'] ?? 'N/A')) ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-label">Department</div>
                                        <div class="info-value"><?= e($staff['department'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-label">Job Title</div>
                                        <div class="info-value"><?= e($staff['job_title'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-label">Reports To</div>
                                        <div class="info-value"><?= e($staff['reports_to'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-label">Work Location</div>
                                        <div class="info-value"><?= e($staff['work_location'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-label">Work Schedule</div>
                                        <div class="info-value"><?= ucfirst(str_replace('_', ' ', $staff['work_schedule'] ?? 'N/A')) ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h4 class="mb-3">Contract & Dates</h4>
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="info-label">Date Joined</div>
                                        <div class="info-value"><?= !empty($staff['date_joined']) ? date('j M Y', strtotime($staff['date_joined'])) : 'N/A' ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-label">Probation End</div>
                                        <div class="info-value"><?= !empty($staff['probation_end_date']) ? date('j M Y', strtotime($staff['probation_end_date'])) : 'N/A' ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-label">Contract Start</div>
                                        <div class="info-value"><?= !empty($staff['contract_start_date']) ? date('j M Y', strtotime($staff['contract_start_date'])) : 'N/A' ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-label">Contract End</div>
                                        <div class="info-value"><?= !empty($staff['contract_end_date']) ? date('j M Y', strtotime($staff['contract_end_date'])) : 'N/A' ?></div>
                                    </div>
                                </div>

                                <h4 class="mb-3 mt-4">Statutory Numbers</h4>
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="info-label">TSC Number</div>
                                        <div class="info-value"><?= e($staff['tsc_number'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-label">KRA PIN</div>
                                        <div class="info-value"><?= e($staff['kra_pin'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-label">NSSF Number</div>
                                        <div class="info-value"><?= e($staff['nssf_number'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-label">NHIF Number</div>
                                        <div class="info-value"><?= e($staff['nhif_number'] ?? 'N/A') ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Salary Tab -->
                    <div class="tab-pane" id="profile-salary">
                        <div class="row">
                            <div class="col-md-6">
                                <h4 class="mb-3">Salary Information</h4>
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="info-label">Basic Salary</div>
                                        <div class="info-value"><?= $currency ?> <?= number_format($staff['basic_salary'] ?? 0) ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-label">House Allowance</div>
                                        <div class="info-value"><?= $currency ?> <?= number_format($staff['house_allowance'] ?? 0) ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-label">Transport Allowance</div>
                                        <div class="info-value"><?= $currency ?> <?= number_format($staff['transport_allowance'] ?? 0) ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-label">Other Allowances</div>
                                        <div class="info-value"><?= $currency ?> <?= number_format($staff['other_allowances'] ?? 0) ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h4 class="mb-3">Bank Details</h4>
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="info-label">Payment Mode</div>
                                        <div class="info-value"><?= ucfirst($staff['payment_mode'] ?? 'bank') ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-label">Bank Name</div>
                                        <div class="info-value"><?= e($staff['bank_name'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-label">Branch</div>
                                        <div class="info-value"><?= e($staff['bank_branch'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-label">Account Number</div>
                                        <div class="info-value"><?= e($staff['bank_account_number'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-label">Account Name</div>
                                        <div class="info-value"><?= e($staff['bank_account_name'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-label">M-Pesa Number</div>
                                        <div class="info-value"><?= e($staff['mpesa_number'] ?? 'N/A') ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Education Tab -->
                    <div class="tab-pane" id="profile-education">
                        <h4 class="mb-3">Academic Qualifications</h4>
                        <?php if (empty($qualifications)): ?>
                        <div class="empty">
                            <div class="empty-icon"><i class="ti ti-school" style="font-size: 2rem;"></i></div>
                            <p class="empty-title">No qualifications recorded</p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-vcenter">
                                <thead>
                                    <tr>
                                        <th>Level</th>
                                        <th>Field of Study</th>
                                        <th>Institution</th>
                                        <th>Year</th>
                                        <th>Grade</th>
                                        <th>Certificate No.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($qualifications as $q): ?>
                                    <tr>
                                        <td><?= e($q['qualification_level'] ?? '') ?></td>
                                        <td><?= e($q['field_of_study'] ?? '') ?></td>
                                        <td><?= e($q['institution'] ?? '') ?></td>
                                        <td><?= e($q['year_completed'] ?? '') ?></td>
                                        <td><?= e($q['grade_obtained'] ?? '') ?></td>
                                        <td><?= e($q['certificate_number'] ?? '') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Experience Tab -->
                    <div class="tab-pane" id="profile-history">
                        <h4 class="mb-3">Previous Employment</h4>
                        <?php if (empty($employmentHistory)): ?>
                        <div class="empty">
                            <div class="empty-icon"><i class="ti ti-briefcase-2" style="font-size: 2rem;"></i></div>
                            <p class="empty-title">No employment history recorded</p>
                        </div>
                        <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($employmentHistory as $eh): ?>
                            <div class="list-group-item">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <span class="avatar bg-primary-lt"><i class="ti ti-building"></i></span>
                                    </div>
                                    <div class="col">
                                        <strong><?= e($eh['employer_name'] ?? '') ?></strong>
                                        <div class="text-muted"><?= e($eh['job_title'] ?? '') ?></div>
                                    </div>
                                    <div class="col-auto text-muted">
                                        <?= !empty($eh['start_date']) ? date('M Y', strtotime($eh['start_date'])) : '' ?>
                                        -
                                        <?= !empty($eh['end_date']) ? date('M Y', strtotime($eh['end_date'])) : 'Present' ?>
                                    </div>
                                </div>
                                <?php if (!empty($eh['responsibilities'])): ?>
                                <div class="mt-2 text-muted small"><?= e($eh['responsibilities']) ?></div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Documents Tab -->
                    <div class="tab-pane" id="profile-documents">
                        <h4 class="mb-3">Uploaded Documents</h4>
                        <?php
                        // Use reusable document list partial
                        $entityType = 'staff';
                        $entityId = $staff['id'];
                        $showDelete = false; // Read-only on profile view
                        $layout = 'cards';
                        require __DIR__ . '/../partials/_document_list.php';
                        ?>
                    </div>

                    <!-- References Tab -->
                    <div class="tab-pane" id="profile-references">
                        <h4 class="mb-3">Professional References</h4>
                        <?php if (empty($references)): ?>
                        <div class="empty">
                            <div class="empty-icon"><i class="ti ti-users" style="font-size: 2rem;"></i></div>
                            <p class="empty-title">No references recorded</p>
                        </div>
                        <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($references as $ref): ?>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start">
                                            <span class="avatar bg-primary-lt me-3">
                                                <i class="ti ti-user"></i>
                                            </span>
                                            <div class="flex-fill">
                                                <h4 class="mb-1"><?= e($ref['referee_name'] ?? '') ?></h4>
                                                <div class="text-muted small mb-2">
                                                    <?= e($ref['position'] ?? '') ?>
                                                    <?php if (!empty($ref['organization'])): ?>
                                                    at <?= e($ref['organization']) ?>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="row g-2 small">
                                                    <?php if (!empty($ref['relationship'])): ?>
                                                    <div class="col-6">
                                                        <span class="text-muted">Relationship:</span>
                                                        <span><?= e($ref['relationship']) ?></span>
                                                    </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($ref['years_known'])): ?>
                                                    <div class="col-6">
                                                        <span class="text-muted">Years Known:</span>
                                                        <span><?= e($ref['years_known']) ?> years</span>
                                                    </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($ref['phone'])): ?>
                                                    <div class="col-6">
                                                        <span class="text-muted">Phone:</span>
                                                        <span><?= e($ref['phone']) ?></span>
                                                    </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($ref['email'])): ?>
                                                    <div class="col-6">
                                                        <span class="text-muted">Email:</span>
                                                        <span><?= e($ref['email']) ?></span>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($ref['is_verified'] ?? false): ?>
                                                <div class="mt-2">
                                                    <span class="badge bg-success"><i class="ti ti-check me-1"></i>Verified</span>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($staff['references_verified'] ?? false): ?>
                        <div class="alert alert-success mt-3">
                            <div class="d-flex">
                                <i class="ti ti-check me-2"></i>
                                <div>
                                    <h4 class="alert-title">References Verified</h4>
                                    <div class="text-muted">
                                        <?php if (!empty($staff['references_verified_by'])): ?>
                                        Verified by: <?= e($staff['references_verified_by']) ?>
                                        <?php endif; ?>
                                        <?php if (!empty($staff['references_verification_notes'])): ?>
                                        <br>Notes: <?= e($staff['references_verification_notes']) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <!-- User Account Tab -->
                    <div class="tab-pane" id="profile-account">
                        <h4 class="mb-3">User Account</h4>
                        <?php if (!empty($staff['user_id'])): ?>
                        <div class="alert alert-success">
                            <i class="ti ti-check me-2"></i>
                            This staff member has an associated user account.
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="info-label">User ID</div>
                                <div class="info-value"><?= e($staff['user_id']) ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-label">Last Login</div>
                                <div class="info-value"><?= !empty($staff['last_login']) ? date('j M Y H:i', strtotime($staff['last_login'])) : 'Never' ?></div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="button" class="btn btn-outline-warning">
                                <i class="ti ti-key me-1"></i>Reset Password
                            </button>
                            <button type="button" class="btn btn-outline-danger">
                                <i class="ti ti-lock me-1"></i>Deactivate Account
                            </button>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info">
                            <i class="ti ti-info-circle me-2"></i>
                            This staff member does not have a user account yet.
                        </div>
                        <button type="button" class="btn btn-primary">
                            <i class="ti ti-user-plus me-1"></i>Create User Account
                        </button>
                        <p class="text-muted mt-2 small">
                            Creating a user account will allow this staff member to log in to the system.
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
