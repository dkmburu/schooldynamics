<?php
/**
 * Staff Form Content (Add/Edit) - Comprehensive Multi-Tab Form
 * Full-width layout with floating bottom action bar
 */

// Extract lookup data
$countries = $lookups['countries'] ?? [];
$departments = $lookups['departments'] ?? [];
$designations = $lookups['designations'] ?? [];
$salaryStructures = $lookups['salaryStructures'] ?? [];
$salaryGrades = $lookups['salaryGrades'] ?? [];
$banks = $lookups['banks'] ?? [];
$bloodGroups = $lookups['bloodGroups'] ?? [];
$staffTypes = $lookups['staffTypes'] ?? [];
$employmentTypes = $lookups['employmentTypes'] ?? [];
$workSchedules = $lookups['workSchedules'] ?? [];
$paymentModes = $lookups['paymentModes'] ?? [];
$educationLevels = $lookups['educationLevels'] ?? [];
$leavingReasons = $lookups['leavingReasons'] ?? [];
$maritalStatuses = $lookups['maritalStatuses'] ?? [];
$religions = $lookups['religions'] ?? [];
$emergencyRelationships = $lookups['emergencyRelationships'] ?? [];
$referenceRelationships = $lookups['referenceRelationships'] ?? [];
$documentTypes = $lookups['documentTypes'] ?? [];

$staff = $staff ?? null;
$isEdit = !empty($staff);
$currency = $_SESSION['currency'] ?? 'KES';

// For edit mode, get related data
$employmentHistory = $employmentHistory ?? [];
$qualifications = $qualifications ?? [];
$documents = $documents ?? [];
$references = $references ?? [];
?>

<style>
/* Make tabs compact and prevent scrolling */
.nav-tabs-scroll {
    overflow-x: auto;
    flex-wrap: nowrap;
    -webkit-overflow-scrolling: touch;
}
.nav-tabs-scroll .nav-item {
    flex-shrink: 0;
    padding: 0;
}
.nav-tabs-scroll .nav-link {
    padding: 0.5rem 0.5rem;
    font-size: 0.8125rem;
    white-space: nowrap;
}
.nav-tabs-scroll .nav-link i {
    font-size: 0.9rem;
    margin-right: 0.25rem !important;
}
/* Disabled tabs styling for new staff */
.nav-tabs-scroll .nav-link.disabled {
    opacity: 0.5;
    cursor: not-allowed;
    color: #adb5bd;
}
.tab-locked-message {
    text-align: center;
    padding: 3rem 2rem;
    color: #6c757d;
}
.tab-locked-message i {
    font-size: 3rem;
    opacity: 0.3;
    margin-bottom: 1rem;
}
</style>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <div class="page-pretitle">HR & Payroll</div>
                <h2 class="page-title">
                    <i class="ti ti-user-plus me-2"></i><?= $isEdit ? 'Edit Staff Profile' : 'Add New Staff' ?>
                </h2>
            </div>
            <div class="col-auto ms-auto">
                <a href="/hr-payroll/staff" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-1"></i>Back to Directory
                </a>
            </div>
        </div>
    </div>
</div>

<?php
// Get active tab from URL parameter (for maintaining tab after save)
$activeTab = $_GET['tab'] ?? 'tab-personal';
$validTabs = ['tab-personal', 'tab-employment', 'tab-salary', 'tab-education', 'tab-history', 'tab-documents', 'tab-references', 'tab-access'];
if (!in_array($activeTab, $validTabs)) {
    $activeTab = 'tab-personal';
}
?>

<div class="page-body">
    <div class="container-xl">
        <form method="POST" action="/hr-payroll/staff<?= $isEdit ? '/' . $staff['id'] : '' ?>" enctype="multipart/form-data" id="staffForm">
            <input type="hidden" name="active_tab" id="active_tab" value="<?= $activeTab ?>">
            <div class="row">
                <!-- Main Content with Tabs (Full Width) -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <ul class="nav nav-tabs card-header-tabs nav-tabs-scroll nav-fill" data-bs-toggle="tabs" id="staffTabs">
                                <li class="nav-item">
                                    <a href="#tab-personal" class="nav-link <?= $activeTab == 'tab-personal' ? 'active' : '' ?>" data-bs-toggle="tab" title="Personal Information">
                                        <i class="ti ti-user me-1"></i>Personal
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#tab-employment" class="nav-link <?= $activeTab == 'tab-employment' ? 'active' : '' ?> <?= !$isEdit ? 'disabled' : '' ?>" data-bs-toggle="<?= $isEdit ? 'tab' : '' ?>" title="Employment Details">
                                        <i class="ti ti-briefcase me-1"></i>Job
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#tab-salary" class="nav-link <?= $activeTab == 'tab-salary' ? 'active' : '' ?> <?= !$isEdit ? 'disabled' : '' ?>" data-bs-toggle="<?= $isEdit ? 'tab' : '' ?>" title="Salary & Role">
                                        <i class="ti ti-cash me-1"></i>Salary
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#tab-education" class="nav-link <?= $activeTab == 'tab-education' ? 'active' : '' ?> <?= !$isEdit ? 'disabled' : '' ?>" data-bs-toggle="<?= $isEdit ? 'tab' : '' ?>" title="Education & Skills">
                                        <i class="ti ti-school me-1"></i>Education
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#tab-history" class="nav-link <?= $activeTab == 'tab-history' ? 'active' : '' ?> <?= !$isEdit ? 'disabled' : '' ?>" data-bs-toggle="<?= $isEdit ? 'tab' : '' ?>" title="Work Experience">
                                        <i class="ti ti-briefcase-2 me-1"></i>History
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#tab-documents" class="nav-link <?= $activeTab == 'tab-documents' ? 'active' : '' ?> <?= !$isEdit ? 'disabled' : '' ?>" data-bs-toggle="<?= $isEdit ? 'tab' : '' ?>" title="Documents">
                                        <i class="ti ti-files me-1"></i>Docs
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#tab-references" class="nav-link <?= $activeTab == 'tab-references' ? 'active' : '' ?> <?= !$isEdit ? 'disabled' : '' ?>" data-bs-toggle="<?= $isEdit ? 'tab' : '' ?>" title="References">
                                        <i class="ti ti-address-book me-1"></i>Refs
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#tab-access" class="nav-link <?= $activeTab == 'tab-access' ? 'active' : '' ?> <?= !$isEdit ? 'disabled' : '' ?>" data-bs-toggle="<?= $isEdit ? 'tab' : '' ?>" title="Online Access">
                                        <i class="ti ti-key me-1"></i>Access
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($_SESSION['flash_success'])): ?>
                            <div class="alert alert-success alert-dismissible mb-3" role="alert">
                                <div class="d-flex">
                                    <div>
                                        <i class="ti ti-check me-2"></i>
                                    </div>
                                    <div>
                                        <h4 class="alert-title">Success!</h4>
                                        <div class="text-muted"><?= e($_SESSION['flash_success']) ?></div>
                                    </div>
                                </div>
                                <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                            </div>
                            <?php unset($_SESSION['flash_success']); ?>
                            <?php endif; ?>

                            <?php if (!empty($_SESSION['flash_error'])): ?>
                            <div class="alert alert-danger alert-dismissible mb-3" role="alert">
                                <div class="d-flex">
                                    <div>
                                        <i class="ti ti-alert-circle me-2"></i>
                                    </div>
                                    <div>
                                        <h4 class="alert-title">Error</h4>
                                        <div class="text-muted"><?= e($_SESSION['flash_error']) ?></div>
                                    </div>
                                </div>
                                <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                            </div>
                            <?php unset($_SESSION['flash_error']); ?>
                            <?php endif; ?>

                            <div class="tab-content">
                                <!-- Tab 1: Personal Information -->
                                <div class="tab-pane <?= $activeTab == 'tab-personal' ? 'active show' : '' ?>" id="tab-personal">
                                    <div class="row">
                                        <!-- Passport Photo Column -->
                                        <div class="col-md-3 col-lg-2 mb-4">
                                            <div class="text-center">
                                                <div class="passport-photo-container mb-2" style="width: 140px; height: 170px; margin: 0 auto; border: 2px dashed #c5d2dc; border-radius: 4px; background: #f8fafc; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative;">
                                                    <?php if (!empty($staff['photo'])): ?>
                                                    <img src="<?= e($staff['photo']) ?>" id="photo-preview" style="width: 100%; height: 100%; object-fit: cover;">
                                                    <?php else: ?>
                                                    <div id="photo-placeholder" class="text-center text-muted">
                                                        <i class="ti ti-user" style="font-size: 3rem; opacity: 0.3;"></i>
                                                        <div class="small mt-1" style="font-size: 0.7rem;">Passport Photo</div>
                                                    </div>
                                                    <img src="" id="photo-preview" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                                                    <?php endif; ?>
                                                </div>
                                                <input type="file" name="photo" id="photo-input" class="d-none" accept="image/*">
                                                <?php if ($isEdit): ?>
                                                <div class="dropdown">
                                                    <a href="#" class="small dropdown-toggle" data-bs-toggle="dropdown">
                                                        <i class="ti ti-upload me-1"></i><?= !empty($staff['photo']) ? 'Change' : 'Upload' ?> Photo
                                                    </a>
                                                    <div class="dropdown-menu dropdown-menu-end">
                                                        <a class="dropdown-item" href="#" onclick="document.getElementById('photo-input').click(); return false;">
                                                            <i class="ti ti-device-laptop me-2"></i>From Computer
                                                        </a>
                                                        <a class="dropdown-item" href="#" id="btn-photo-qr">
                                                            <i class="ti ti-qrcode me-2"></i>Scan QR with Phone
                                                        </a>
                                                    </div>
                                                </div>
                                                <?php else: ?>
                                                <a href="#" class="small" onclick="document.getElementById('photo-input').click(); return false;">
                                                    <i class="ti ti-upload me-1"></i>Upload Photo
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <!-- Personal Details Column -->
                                        <div class="col-md-9 col-lg-10">
                                            <h4 class="mb-3">Personal Information</h4>
                                            <div class="row g-3">
                                                <div class="col-md-2">
                                                    <label class="form-label">Title</label>
                                                    <input type="text" name="title" class="form-control" list="titles-list"
                                                           value="<?= e($staff['title'] ?? '') ?>" placeholder="Mr., Mrs., Ms.">
                                                    <datalist id="titles-list">
                                                        <option value="Mr.">
                                                        <option value="Mrs.">
                                                        <option value="Ms.">
                                                        <option value="Miss">
                                                        <option value="Dr.">
                                                        <option value="Prof.">
                                                        <option value="Rev.">
                                                        <option value="Hon.">
                                                    </datalist>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label required">First Name</label>
                                                    <input type="text" name="first_name" class="form-control" required
                                                           value="<?= e($staff['first_name'] ?? '') ?>">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Middle Name</label>
                                                    <input type="text" name="middle_name" class="form-control"
                                                           value="<?= e($staff['middle_name'] ?? '') ?>">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label required">Last Name</label>
                                                    <input type="text" name="last_name" class="form-control" required
                                                           value="<?= e($staff['last_name'] ?? '') ?>">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Gender</label>
                                                    <select name="gender" class="form-select">
                                                        <option value="">Select</option>
                                                        <option value="male" <?= ($staff['gender'] ?? '') == 'male' ? 'selected' : '' ?>>Male</option>
                                                        <option value="female" <?= ($staff['gender'] ?? '') == 'female' ? 'selected' : '' ?>>Female</option>
                                                        <option value="other" <?= ($staff['gender'] ?? '') == 'other' ? 'selected' : '' ?>>Other</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Date of Birth</label>
                                                    <input type="date" name="date_of_birth" class="form-control"
                                                           value="<?= e($staff['date_of_birth'] ?? '') ?>">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">ID Number</label>
                                                    <input type="text" name="id_number" class="form-control"
                                                           value="<?= e($staff['id_number'] ?? '') ?>">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Nationality</label>
                                                    <select name="nationality" class="form-select">
                                                        <option value="">Select Country</option>
                                                        <?php foreach ($countries as $country): ?>
                                                        <option value="<?= e($country['nationality'] ?? $country['country_name']) ?>"
                                                                <?= ($staff['nationality'] ?? 'Kenyan') == ($country['nationality'] ?? $country['country_name']) ? 'selected' : '' ?>>
                                                            <?= e($country['nationality'] ?? $country['country_name']) ?>
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Marital Status</label>
                                                    <select name="marital_status" class="form-select">
                                                        <option value="">Select</option>
                                                        <?php foreach ($maritalStatuses as $ms): ?>
                                                        <option value="<?= e($ms['status_code']) ?>"
                                                                <?= ($staff['marital_status'] ?? '') == $ms['status_code'] ? 'selected' : '' ?>>
                                                            <?= e($ms['status_name']) ?>
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Religion</label>
                                                    <select name="religion" class="form-select">
                                                        <option value="">Select</option>
                                                        <?php foreach ($religions as $r): ?>
                                                        <option value="<?= e($r['religion_name']) ?>"
                                                                <?= ($staff['religion'] ?? '') == $r['religion_name'] ? 'selected' : '' ?>>
                                                            <?= e($r['religion_name']) ?>
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Blood Group</label>
                                                    <select name="blood_group" class="form-select">
                                                        <option value="">Select</option>
                                                        <?php foreach ($bloodGroups as $bg): ?>
                                                        <option value="<?= e($bg['blood_group']) ?>"
                                                                <?= ($staff['blood_group'] ?? '') == $bg['blood_group'] ? 'selected' : '' ?>>
                                                            <?= e($bg['blood_group']) ?> (<?= e($bg['description']) ?>)
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <hr class="my-4">
                                            <h4 class="mb-3">Contact Information</h4>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">Email Address</label>
                                                    <input type="email" name="email" class="form-control"
                                                           value="<?= e($staff['email'] ?? '') ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Phone Number</label>
                                                    <input type="tel" name="phone" class="form-control"
                                                           value="<?= e($staff['phone'] ?? '') ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Alternative Phone</label>
                                                    <input type="tel" name="alt_phone" class="form-control"
                                                           value="<?= e($staff['alt_phone'] ?? '') ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Postal Address</label>
                                                    <input type="text" name="postal_address" class="form-control"
                                                           value="<?= e($staff['postal_address'] ?? '') ?>"
                                                           placeholder="P.O. Box 123-00100, Nairobi">
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label">Physical Address</label>
                                                    <textarea name="physical_address" class="form-control" rows="2"
                                                              placeholder="Estate, Street, Building..."><?= e($staff['physical_address'] ?? '') ?></textarea>
                                                </div>
                                            </div>

                                            <hr class="my-4">
                                            <h4 class="mb-3">Emergency Contact</h4>
                                            <div class="row g-3">
                                                <div class="col-md-4">
                                                    <label class="form-label">Contact Name</label>
                                                    <input type="text" name="emergency_contact_name" class="form-control"
                                                           value="<?= e($staff['emergency_contact_name'] ?? '') ?>">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Relationship</label>
                                                    <select name="emergency_contact_relationship" class="form-select">
                                                        <option value="">Select</option>
                                                        <?php foreach ($emergencyRelationships as $rel): ?>
                                                        <option value="<?= e($rel['relationship_name']) ?>"
                                                                <?= ($staff['emergency_contact_relationship'] ?? '') == $rel['relationship_name'] ? 'selected' : '' ?>>
                                                            <?= e($rel['relationship_name']) ?>
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Contact Phone</label>
                                                    <input type="tel" name="emergency_contact_phone" class="form-control"
                                                           value="<?= e($staff['emergency_contact_phone'] ?? '') ?>">
                                                </div>
                                            </div>
                                        </div><!-- End Personal Details Column -->
                                    </div><!-- End row with photo and details -->
                                </div>

                                <!-- Tab 2: Employment Details -->
                                <div class="tab-pane <?= $activeTab == 'tab-employment' ? 'active show' : '' ?>" id="tab-employment">
                                    <?php if (!$isEdit): ?>
                                    <div class="tab-locked-message">
                                        <i class="ti ti-lock d-block"></i>
                                        <h4>Save Personal Information First</h4>
                                        <p class="text-muted">Complete and save the personal information to unlock this section.</p>
                                    </div>
                                    <?php else: ?>
                                    <h4 class="mb-3">Employment Details</h4>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Staff Number</label>
                                            <input type="text" name="staff_number" class="form-control"
                                                   value="<?= e($staff['staff_number'] ?? '') ?>"
                                                   placeholder="Auto-generated if empty">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label required">Staff Type</label>
                                            <select name="staff_type" class="form-select" required>
                                                <option value="">Select Type</option>
                                                <?php foreach ($staffTypes as $st): ?>
                                                <option value="<?= e($st['type_code']) ?>"
                                                        <?= ($staff['staff_type'] ?? '') == $st['type_code'] ? 'selected' : '' ?>>
                                                    <?= e($st['type_name']) ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label required">Employment Type</label>
                                            <select name="employment_type" class="form-select" required id="employment_type">
                                                <option value="">Select Type</option>
                                                <?php foreach ($employmentTypes as $et): ?>
                                                <option value="<?= e($et['type_code']) ?>"
                                                        data-requires-contract="<?= $et['requires_contract_dates'] ?>"
                                                        <?= ($staff['employment_type'] ?? '') == $et['type_code'] ? 'selected' : '' ?>>
                                                    <?= e($et['type_name']) ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label required">Department</label>
                                            <input type="text" name="department" class="form-control" required
                                                   list="departments-list"
                                                   value="<?= e($staff['department'] ?? '') ?>"
                                                   placeholder="Type or select department">
                                            <datalist id="departments-list">
                                                <?php foreach ($departments as $dept): ?>
                                                <option value="<?= e($dept['department_name']) ?>">
                                                <?php endforeach; ?>
                                            </datalist>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Job Title</label>
                                            <input type="text" name="job_title" class="form-control"
                                                   list="designations-list"
                                                   value="<?= e($staff['job_title'] ?? '') ?>"
                                                   placeholder="Type or select job title">
                                            <datalist id="designations-list">
                                                <?php foreach ($designations as $des): ?>
                                                <option value="<?= e($des['designation_name']) ?>">
                                                <?php endforeach; ?>
                                            </datalist>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Reports To</label>
                                            <input type="text" name="reports_to" class="form-control"
                                                   value="<?= e($staff['reports_to'] ?? '') ?>"
                                                   placeholder="Supervisor name">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Work Location</label>
                                            <input type="text" name="work_location" class="form-control"
                                                   value="<?= e($staff['work_location'] ?? '') ?>"
                                                   placeholder="Main Campus, Branch, etc.">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Work Schedule</label>
                                            <select name="work_schedule" class="form-select">
                                                <option value="">Select Schedule</option>
                                                <?php foreach ($workSchedules as $ws): ?>
                                                <option value="<?= e($ws['schedule_code']) ?>"
                                                        <?= ($staff['work_schedule'] ?? '') == $ws['schedule_code'] ? 'selected' : '' ?>>
                                                    <?= e($ws['schedule_name']) ?>
                                                    <?php if ($ws['start_time'] && $ws['end_time']): ?>
                                                    (<?= date('g:ia', strtotime($ws['start_time'])) ?> - <?= date('g:ia', strtotime($ws['end_time'])) ?>)
                                                    <?php endif; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <hr class="my-4">
                                    <h4 class="mb-3">Contract & Dates</h4>
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label required">Date Joined</label>
                                            <input type="date" name="date_joined" class="form-control" required
                                                   value="<?= e($staff['date_joined'] ?? date('Y-m-d')) ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Contract Start</label>
                                            <input type="date" name="contract_start_date" class="form-control"
                                                   value="<?= e($staff['contract_start_date'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Contract End</label>
                                            <input type="date" name="contract_end_date" class="form-control"
                                                   value="<?= e($staff['contract_end_date'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Probation End</label>
                                            <input type="date" name="probation_end_date" class="form-control"
                                                   value="<?= e($staff['probation_end_date'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">TSC Number</label>
                                            <input type="text" name="tsc_number" class="form-control"
                                                   value="<?= e($staff['tsc_number'] ?? '') ?>"
                                                   placeholder="For teaching staff">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">KRA PIN</label>
                                            <input type="text" name="kra_pin" class="form-control"
                                                   value="<?= e($staff['kra_pin'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">NSSF Number</label>
                                            <input type="text" name="nssf_number" class="form-control"
                                                   value="<?= e($staff['nssf_number'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">NHIF Number</label>
                                            <input type="text" name="nhif_number" class="form-control"
                                                   value="<?= e($staff['nhif_number'] ?? '') ?>">
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Tab 3: Salary & Bank Details -->
                                <div class="tab-pane <?= $activeTab == 'tab-salary' ? 'active show' : '' ?>" id="tab-salary">
                                    <?php if (!$isEdit): ?>
                                    <div class="tab-locked-message">
                                        <i class="ti ti-lock d-block"></i>
                                        <h4>Save Personal Information First</h4>
                                        <p class="text-muted">Complete and save the personal information to unlock this section.</p>
                                    </div>
                                    <?php else: ?>
                                    <h4 class="mb-3">Salary Grade & Role Assignment</h4>
                                    <div class="alert alert-info mb-3">
                                        <i class="ti ti-info-circle me-2"></i>
                                        Select a salary grade to auto-fill the basic salary based on the grade's default amount.
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Salary Structure</label>
                                            <select name="salary_structure_id" class="form-select">
                                                <option value="">Select Structure</option>
                                                <?php foreach ($salaryStructures as $ss): ?>
                                                <option value="<?= $ss['id'] ?>" <?= ($staff['salary_structure_id'] ?? '') == $ss['id'] ? 'selected' : '' ?>>
                                                    <?= e($ss['structure_name']) ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Salary Grade / Role</label>
                                            <select name="salary_grade_id" class="form-select" id="salary_grade_select">
                                                <option value="">Select Grade / Role</option>
                                                <?php foreach ($salaryGrades as $sg): ?>
                                                <option value="<?= $sg['id'] ?>"
                                                        data-min="<?= $sg['min_salary'] ?>"
                                                        data-max="<?= $sg['max_salary'] ?>"
                                                        data-default="<?= $sg['default_salary'] ?>"
                                                        <?= ($staff['salary_grade_id'] ?? '') == $sg['id'] ? 'selected' : '' ?>>
                                                    <?= e($sg['grade_code'] . ' - ' . ($sg['grade_name'] ?? '')) ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="text-muted" id="salary-range-hint"></small>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Basic Salary</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><?= $currency ?></span>
                                                <input type="number" name="basic_salary" class="form-control" step="0.01"
                                                       value="<?= e($staff['basic_salary'] ?? '') ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">House Allowance</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><?= $currency ?></span>
                                                <input type="number" name="house_allowance" class="form-control" step="0.01"
                                                       value="<?= e($staff['house_allowance'] ?? '') ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Transport Allowance</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><?= $currency ?></span>
                                                <input type="number" name="transport_allowance" class="form-control" step="0.01"
                                                       value="<?= e($staff['transport_allowance'] ?? '') ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Other Allowances</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><?= $currency ?></span>
                                                <input type="number" name="other_allowances" class="form-control" step="0.01"
                                                       value="<?= e($staff['other_allowances'] ?? '') ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="my-4">
                                    <h4 class="mb-3">Bank Details</h4>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Payment Mode</label>
                                            <select name="payment_mode" class="form-select" id="payment_mode">
                                                <option value="">Select Mode</option>
                                                <?php foreach ($paymentModes as $pm): ?>
                                                <option value="<?= e($pm['mode_code']) ?>"
                                                        data-requires-bank="<?= $pm['requires_bank_details'] ?>"
                                                        <?= ($staff['payment_mode'] ?? 'bank') == $pm['mode_code'] ? 'selected' : '' ?>>
                                                    <?= e($pm['mode_name']) ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4" id="bank_name_container">
                                            <label class="form-label">Bank Name</label>
                                            <select name="bank_id" class="form-select">
                                                <option value="">Select Bank</option>
                                                <?php foreach ($banks as $bank): ?>
                                                <option value="<?= $bank['id'] ?>" <?= ($staff['bank_id'] ?? '') == $bank['id'] ? 'selected' : '' ?>>
                                                    <?= e($bank['bank_name']) ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4" id="bank_branch_container">
                                            <label class="form-label">Branch Name</label>
                                            <input type="text" name="bank_branch" class="form-control"
                                                   value="<?= e($staff['bank_branch'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-4" id="account_number_container">
                                            <label class="form-label">Account Number</label>
                                            <input type="text" name="bank_account_number" class="form-control"
                                                   value="<?= e($staff['bank_account_number'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-4" id="account_name_container">
                                            <label class="form-label">Account Name</label>
                                            <input type="text" name="bank_account_name" class="form-control"
                                                   value="<?= e($staff['bank_account_name'] ?? '') ?>"
                                                   placeholder="Name as it appears on bank account">
                                        </div>
                                        <div class="col-md-4" id="mpesa_number_container" style="display: none;">
                                            <label class="form-label">M-Pesa Number</label>
                                            <input type="text" name="mpesa_number" class="form-control"
                                                   value="<?= e($staff['mpesa_number'] ?? '') ?>"
                                                   placeholder="07XXXXXXXX">
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Tab 4: Education & Skills -->
                                <div class="tab-pane <?= $activeTab == 'tab-education' ? 'active show' : '' ?>" id="tab-education">
                                    <?php if (!$isEdit): ?>
                                    <div class="tab-locked-message">
                                        <i class="ti ti-lock d-block"></i>
                                        <h4>Save Personal Information First</h4>
                                        <p class="text-muted">Complete and save the personal information to unlock this section.</p>
                                    </div>
                                    <?php else: ?>
                                    <h4 class="mb-3">Academic Qualifications</h4>
                                    <div class="alert alert-info">
                                        <i class="ti ti-info-circle me-2"></i>
                                        Add educational background, certifications, and professional skills.
                                    </div>

                                    <div id="qualifications-container">
                                        <?php if (empty($qualifications)): ?>
                                        <div class="qualification-entry card mb-3">
                                            <div class="card-body">
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <label class="form-label">Qualification Level</label>
                                                        <select name="qualifications[0][level]" class="form-select qualification-level-select">
                                                            <option value="">Select</option>
                                                            <?php foreach ($educationLevels as $el): ?>
                                                            <option value="<?= e($el['level_code']) ?>"><?= e($el['level_name']) ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Field of Study</label>
                                                        <input type="text" name="qualifications[0][field]" class="form-control"
                                                               placeholder="e.g., Education, Computer Science">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Institution</label>
                                                        <input type="text" name="qualifications[0][institution]" class="form-control"
                                                               placeholder="University/College name">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label">Year Completed</label>
                                                        <input type="number" name="qualifications[0][year]" class="form-control"
                                                               min="1970" max="<?= date('Y') ?>">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label">Grade/Class</label>
                                                        <input type="text" name="qualifications[0][grade]" class="form-control"
                                                               placeholder="e.g., First Class, B+">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Certificate Number</label>
                                                        <input type="text" name="qualifications[0][cert_number]" class="form-control">
                                                    </div>
                                                    <div class="col-md-2 d-flex align-items-end">
                                                        <button type="button" class="btn btn-outline-danger btn-remove-qualification w-100" disabled>
                                                            <i class="ti ti-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php else: ?>
                                        <?php foreach ($qualifications as $i => $q): ?>
                                        <div class="qualification-entry card mb-3">
                                            <div class="card-body">
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <label class="form-label">Qualification Level</label>
                                                        <select name="qualifications[<?= $i ?>][level]" class="form-select qualification-level-select">
                                                            <option value="">Select</option>
                                                            <?php foreach ($educationLevels as $el): ?>
                                                            <option value="<?= e($el['level_code']) ?>" <?= ($q['qualification_level'] ?? '') == $el['level_code'] ? 'selected' : '' ?>>
                                                                <?= e($el['level_name']) ?>
                                                            </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Field of Study</label>
                                                        <input type="text" name="qualifications[<?= $i ?>][field]" class="form-control"
                                                               value="<?= e($q['field_of_study'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Institution</label>
                                                        <input type="text" name="qualifications[<?= $i ?>][institution]" class="form-control"
                                                               value="<?= e($q['institution'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label">Year Completed</label>
                                                        <input type="number" name="qualifications[<?= $i ?>][year]" class="form-control"
                                                               value="<?= e($q['year_completed'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label">Grade/Class</label>
                                                        <input type="text" name="qualifications[<?= $i ?>][grade]" class="form-control"
                                                               value="<?= e($q['grade_obtained'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Certificate Number</label>
                                                        <input type="text" name="qualifications[<?= $i ?>][cert_number]" class="form-control"
                                                               value="<?= e($q['certificate_number'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-md-2 d-flex align-items-end">
                                                        <button type="button" class="btn btn-outline-danger btn-remove-qualification w-100">
                                                            <i class="ti ti-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" class="btn btn-outline-primary" id="add-qualification">
                                        <i class="ti ti-plus me-1"></i>Add Qualification
                                    </button>

                                    <hr class="my-4">
                                    <h4 class="mb-3">Professional Skills & Competencies</h4>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Teaching Subjects (if applicable)</label>
                                            <input type="text" name="teaching_subjects" class="form-control"
                                                   value="<?= e($staff['teaching_subjects'] ?? '') ?>"
                                                   placeholder="e.g., Mathematics, Physics, Chemistry">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Languages Spoken</label>
                                            <input type="text" name="languages" class="form-control"
                                                   value="<?= e($staff['languages'] ?? '') ?>"
                                                   placeholder="e.g., English, Swahili, French">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Technical Skills</label>
                                            <textarea name="technical_skills" class="form-control" rows="2"
                                                      placeholder="e.g., MS Office, Python, Laboratory Equipment..."><?= e($staff['technical_skills'] ?? '') ?></textarea>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Certifications & Professional Memberships</label>
                                            <textarea name="certifications" class="form-control" rows="2"
                                                      placeholder="e.g., KNUT Member, First Aid Certified, TSC Registered..."><?= e($staff['certifications'] ?? '') ?></textarea>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Tab 5: Work History -->
                                <div class="tab-pane <?= $activeTab == 'tab-history' ? 'active show' : '' ?>" id="tab-history">
                                    <?php if (!$isEdit): ?>
                                    <div class="tab-locked-message">
                                        <i class="ti ti-lock d-block"></i>
                                        <h4>Save Personal Information First</h4>
                                        <p class="text-muted">Complete and save the personal information to unlock this section.</p>
                                    </div>
                                    <?php else: ?>
                                    <h4 class="mb-3">Previous Employment History</h4>
                                    <div class="alert alert-info">
                                        <i class="ti ti-info-circle me-2"></i>
                                        Record previous work experience, starting with the most recent.
                                    </div>

                                    <div id="employment-history-container">
                                        <?php if (empty($employmentHistory)): ?>
                                        <div class="employment-entry card mb-3">
                                            <div class="card-body">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Employer / Organization</label>
                                                        <input type="text" name="employment_history[0][employer]" class="form-control"
                                                               placeholder="Company/School name">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Job Title / Position</label>
                                                        <input type="text" name="employment_history[0][position]" class="form-control">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label">Start Date</label>
                                                        <input type="date" name="employment_history[0][start_date]" class="form-control">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label">End Date</label>
                                                        <input type="date" name="employment_history[0][end_date]" class="form-control">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Reason for Leaving</label>
                                                        <select name="employment_history[0][reason_leaving]" class="form-select leaving-reason-select">
                                                            <option value="">Select</option>
                                                            <?php foreach ($leavingReasons as $lr): ?>
                                                            <option value="<?= e($lr['reason_code']) ?>"><?= e($lr['reason_name']) ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label">Key Responsibilities</label>
                                                        <textarea name="employment_history[0][responsibilities]" class="form-control" rows="2"
                                                                  placeholder="Brief description of duties and achievements"></textarea>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Supervisor Name</label>
                                                        <input type="text" name="employment_history[0][supervisor]" class="form-control">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Contact Phone (for verification)</label>
                                                        <input type="tel" name="employment_history[0][contact_phone]" class="form-control">
                                                    </div>
                                                    <div class="col-12 text-end">
                                                        <button type="button" class="btn btn-outline-danger btn-remove-employment" disabled>
                                                            <i class="ti ti-trash me-1"></i>Remove
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php else: ?>
                                        <?php foreach ($employmentHistory as $i => $eh): ?>
                                        <div class="employment-entry card mb-3">
                                            <div class="card-body">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Employer / Organization</label>
                                                        <input type="text" name="employment_history[<?= $i ?>][employer]" class="form-control"
                                                               value="<?= e($eh['employer_name'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Job Title / Position</label>
                                                        <input type="text" name="employment_history[<?= $i ?>][position]" class="form-control"
                                                               value="<?= e($eh['job_title'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label">Start Date</label>
                                                        <input type="date" name="employment_history[<?= $i ?>][start_date]" class="form-control"
                                                               value="<?= e($eh['start_date'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label">End Date</label>
                                                        <input type="date" name="employment_history[<?= $i ?>][end_date]" class="form-control"
                                                               value="<?= e($eh['end_date'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Reason for Leaving</label>
                                                        <select name="employment_history[<?= $i ?>][reason_leaving]" class="form-select leaving-reason-select">
                                                            <option value="">Select</option>
                                                            <?php foreach ($leavingReasons as $lr): ?>
                                                            <option value="<?= e($lr['reason_code']) ?>" <?= ($eh['reason_for_leaving'] ?? '') == $lr['reason_code'] ? 'selected' : '' ?>>
                                                                <?= e($lr['reason_name']) ?>
                                                            </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label">Key Responsibilities</label>
                                                        <textarea name="employment_history[<?= $i ?>][responsibilities]" class="form-control" rows="2"><?= e($eh['responsibilities'] ?? '') ?></textarea>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Supervisor Name</label>
                                                        <input type="text" name="employment_history[<?= $i ?>][supervisor]" class="form-control"
                                                               value="<?= e($eh['supervisor_name'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Contact Phone (for verification)</label>
                                                        <input type="tel" name="employment_history[<?= $i ?>][contact_phone]" class="form-control"
                                                               value="<?= e($eh['supervisor_phone'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-12 text-end">
                                                        <button type="button" class="btn btn-outline-danger btn-remove-employment">
                                                            <i class="ti ti-trash me-1"></i>Remove
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" class="btn btn-outline-primary" id="add-employment">
                                        <i class="ti ti-plus me-1"></i>Add Previous Employment
                                    </button>
                                    <?php endif; ?>
                                </div>

                                <!-- Tab 6: Documents -->
                                <div class="tab-pane <?= $activeTab == 'tab-documents' ? 'active show' : '' ?>" id="tab-documents">
                                    <?php if (!$isEdit): ?>
                                    <div class="tab-locked-message">
                                        <i class="ti ti-lock d-block"></i>
                                        <h4>Save Personal Information First</h4>
                                        <p class="text-muted">Complete and save the personal information to unlock this section.</p>
                                    </div>
                                    <?php else: ?>
                                    <h4 class="mb-3">Document Uploads</h4>
                                    <div class="alert alert-info">
                                        <i class="ti ti-info-circle me-2"></i>
                                        Upload scanned copies of important documents. You can upload from computer or use your phone camera via QR code.
                                    </div>

                                    <!-- Uploaded Documents List -->
                                    <?php if (!empty($documents)): ?>
                                    <div class="mb-4">
                                        <h5>Uploaded Documents</h5>
                                        <div class="table-responsive">
                                            <table class="table table-vcenter">
                                                <thead>
                                                    <tr>
                                                        <th>Document</th>
                                                        <th>Type</th>
                                                        <th>Upload Method</th>
                                                        <th>Date</th>
                                                        <th>Status</th>
                                                        <th class="text-end">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($documents as $doc): ?>
                                                    <tr id="doc-row-<?= $doc['id'] ?>">
                                                        <td>
                                                            <i class="ti ti-file me-2"></i>
                                                            <?= e($doc['document_name'] ?? $doc['file_name']) ?>
                                                        </td>
                                                        <td><?= e(ucwords(str_replace('_', ' ', $doc['document_type']))) ?></td>
                                                        <td>
                                                            <?php if ($doc['upload_method'] === 'phone'): ?>
                                                            <span class="badge bg-info"><i class="ti ti-device-mobile me-1"></i>Phone</span>
                                                            <?php else: ?>
                                                            <span class="badge bg-secondary"><i class="ti ti-device-laptop me-1"></i>Computer</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?= date('M j, Y', strtotime($doc['uploaded_at'])) ?></td>
                                                        <td>
                                                            <?php
                                                            $statusColors = ['uploaded' => 'warning', 'verified' => 'success', 'rejected' => 'danger'];
                                                            $color = $statusColors[$doc['verification_status'] ?? 'uploaded'] ?? 'secondary';
                                                            ?>
                                                            <span class="badge bg-<?= $color ?>"><?= ucfirst($doc['verification_status'] ?? 'uploaded') ?></span>
                                                        </td>
                                                        <td class="text-end">
                                                            <button type="button" class="btn btn-sm btn-outline-primary" title="Download" onclick="downloadDocument('staff', <?= $doc['id'] ?>, '<?= e($doc['file_name']) ?>')">
                                                                <i class="ti ti-download me-1"></i> Download
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-doc" data-doc-id="<?= $doc['id'] ?>" title="Delete">
                                                                <i class="ti ti-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <hr>
                                    <?php endif; ?>

                                    <!-- Document Type Cards for Upload -->
                                    <h5 class="mb-3">Upload New Document</h5>
                                    <div class="row g-3">
                                        <?php
                                        $docTypeIcons = [
                                            'national_id' => 'ti-id',
                                            'passport' => 'ti-passport',
                                            'birth_cert' => 'ti-file-certificate',
                                            'academic_cert' => 'ti-certificate',
                                            'transcript' => 'ti-file-text',
                                            'cv' => 'ti-file-cv',
                                            'offer_letter' => 'ti-mail',
                                            'contract' => 'ti-file-invoice',
                                            'tsc_cert' => 'ti-license',
                                            'kra_pin' => 'ti-building-bank',
                                            'good_conduct' => 'ti-shield-check',
                                            'medical_cert' => 'ti-heart-plus',
                                            'passport_photo' => 'ti-photo',
                                            'other' => 'ti-files'
                                        ];
                                        foreach ($documentTypes as $dt):
                                            $icon = $docTypeIcons[$dt['type_code']] ?? 'ti-file';
                                            // Check if this document type is already uploaded
                                            $isUploaded = false;
                                            foreach ($documents as $doc) {
                                                if ($doc['document_type'] === $dt['type_code']) {
                                                    $isUploaded = true;
                                                    break;
                                                }
                                            }
                                        ?>
                                        <div class="col-md-4 col-lg-3">
                                            <div class="card h-100 <?= $isUploaded ? 'border-success' : '' ?>">
                                                <div class="card-body text-center">
                                                    <i class="ti <?= $icon ?> mb-2" style="font-size: 2rem; color: <?= $isUploaded ? '#2fb344' : '#206bc4' ?>;"></i>
                                                    <h6 class="mb-2"><?= e($dt['type_name']) ?></h6>
                                                    <?php if ($dt['is_required']): ?>
                                                    <span class="badge bg-red-lt mb-2">Required</span>
                                                    <?php endif; ?>
                                                    <?php if ($isUploaded): ?>
                                                    <div class="text-success small mb-2"><i class="ti ti-check me-1"></i>Uploaded</div>
                                                    <?php endif; ?>
                                                    <button type="button" class="btn btn-sm btn-primary w-100 btn-upload-doc"
                                                            data-doc-type="<?= e($dt['type_code']) ?>"
                                                            data-doc-label="<?= e($dt['type_name']) ?>">
                                                        <i class="ti ti-upload me-1"></i><?= $isUploaded ? 'Replace' : 'Upload' ?>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Tab 7: References -->
                                <div class="tab-pane <?= $activeTab == 'tab-references' ? 'active show' : '' ?>" id="tab-references">
                                    <?php if (!$isEdit): ?>
                                    <div class="tab-locked-message">
                                        <i class="ti ti-lock d-block"></i>
                                        <h4>Save Personal Information First</h4>
                                        <p class="text-muted">Complete and save the personal information to unlock this section.</p>
                                    </div>
                                    <?php else: ?>
                                    <h4 class="mb-3">Professional References</h4>
                                    <div class="alert alert-info">
                                        <i class="ti ti-info-circle me-2"></i>
                                        Provide at least two professional references (not family members).
                                    </div>

                                    <div id="references-container">
                                        <?php for ($i = 0; $i < max(2, count($references)); $i++): ?>
                                        <div class="reference-entry card mb-3">
                                            <div class="card-header">
                                                <h5 class="card-title mb-0">Reference <?= $i + 1 ?></h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Full Name</label>
                                                        <input type="text" name="references[<?= $i ?>][name]" class="form-control"
                                                               value="<?= e($references[$i]['referee_name'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Relationship</label>
                                                        <select name="references[<?= $i ?>][relationship]" class="form-select reference-relationship-select">
                                                            <option value="">Select</option>
                                                            <?php foreach ($referenceRelationships as $rr): ?>
                                                            <option value="<?= e($rr['relationship_name']) ?>"
                                                                    <?= ($references[$i]['relationship'] ?? '') == $rr['relationship_name'] ? 'selected' : '' ?>>
                                                                <?= e($rr['relationship_name']) ?>
                                                            </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Organization / Company</label>
                                                        <input type="text" name="references[<?= $i ?>][organization]" class="form-control"
                                                               value="<?= e($references[$i]['organization'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Position / Title</label>
                                                        <input type="text" name="references[<?= $i ?>][position]" class="form-control"
                                                               value="<?= e($references[$i]['position'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Phone Number</label>
                                                        <input type="tel" name="references[<?= $i ?>][phone]" class="form-control"
                                                               value="<?= e($references[$i]['phone'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Email</label>
                                                        <input type="email" name="references[<?= $i ?>][email]" class="form-control"
                                                               value="<?= e($references[$i]['email'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Years Known</label>
                                                        <input type="number" name="references[<?= $i ?>][years_known]" class="form-control" min="0"
                                                               value="<?= e($references[$i]['years_known'] ?? '') ?>">
                                                    </div>
                                                </div>
                                            </div>
                                            <?php if ($i >= 2): ?>
                                            <div class="card-footer text-end">
                                                <button type="button" class="btn btn-outline-danger btn-remove-reference">
                                                    <i class="ti ti-trash me-1"></i>Remove
                                                </button>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endfor; ?>
                                    </div>
                                    <button type="button" class="btn btn-outline-primary" id="add-reference">
                                        <i class="ti ti-plus me-1"></i>Add Another Reference
                                    </button>

                                    <hr class="my-4">
                                    <h4 class="mb-3">Reference Verification Status</h4>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-check">
                                                <input type="checkbox" name="references_verified" class="form-check-input" value="1"
                                                       <?= ($staff['references_verified'] ?? false) ? 'checked' : '' ?>>
                                                <span class="form-check-label">References have been verified</span>
                                            </label>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Verified By</label>
                                            <input type="text" name="references_verified_by" class="form-control"
                                                   value="<?= e($staff['references_verified_by'] ?? '') ?>">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Verification Notes</label>
                                            <textarea name="references_verification_notes" class="form-control" rows="2"><?= e($staff['references_verification_notes'] ?? '') ?></textarea>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Tab 8: Online Access -->
                                <div class="tab-pane <?= $activeTab == 'tab-access' ? 'active show' : '' ?>" id="tab-access">
                                    <?php if (!$isEdit): ?>
                                    <div class="tab-locked-message">
                                        <i class="ti ti-lock d-block"></i>
                                        <h4>Save Personal Information First</h4>
                                        <p class="text-muted">Complete and save the personal information to unlock this section.</p>
                                    </div>
                                    <?php else: ?>
                                    <?php
                                    // Check if staff has linked user account
                                    $hasUserAccount = !empty($staff['user_id']);
                                    $linkedUser = $linkedUser ?? null;
                                    ?>

                                    <div class="row">
                                        <div class="col-lg-8">
                                            <h4 class="mb-3">User Account & System Access</h4>

                                            <?php if ($hasUserAccount && $linkedUser): ?>
                                            <!-- Existing User Account -->
                                            <div class="alert alert-success">
                                                <div class="d-flex align-items-center">
                                                    <div class="me-3">
                                                        <span class="avatar avatar-lg bg-success-lt">
                                                            <i class="ti ti-user-check fs-1"></i>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <h4 class="mb-1">User Account Active</h4>
                                                        <p class="mb-0">
                                                            Username: <strong><?= e($linkedUser['username']) ?></strong><br>
                                                            Email: <strong><?= e($linkedUser['email']) ?></strong><br>
                                                            Status: <span class="badge bg-<?= $linkedUser['status'] == 'active' ? 'success' : 'warning' ?>"><?= ucfirst($linkedUser['status']) ?></span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="card mb-3">
                                                <div class="card-header">
                                                    <h5 class="card-title mb-0"><i class="ti ti-shield me-2"></i>Assigned Roles</h5>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row g-2">
                                                        <?php foreach ($roles ?? [] as $role): ?>
                                                        <div class="col-md-4">
                                                            <label class="form-check">
                                                                <input type="checkbox" name="user_roles[]" class="form-check-input"
                                                                       value="<?= $role['id'] ?>"
                                                                       <?= in_array($role['id'], $userRoles ?? []) ? 'checked' : '' ?>>
                                                                <span class="form-check-label"><?= e($role['display_name']) ?></span>
                                                            </label>
                                                        </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="card">
                                                <div class="card-header">
                                                    <h5 class="card-title mb-0"><i class="ti ti-settings me-2"></i>Account Actions</h5>
                                                </div>
                                                <div class="card-body">
                                                    <div class="btn-list">
                                                        <button type="button" class="btn btn-outline-warning" onclick="sendPasswordReset()">
                                                            <i class="ti ti-key me-1"></i>Send Password Reset Link
                                                        </button>
                                                        <?php if ($linkedUser['status'] == 'active'): ?>
                                                        <button type="button" class="btn btn-outline-danger" onclick="suspendAccount()">
                                                            <i class="ti ti-user-off me-1"></i>Suspend Account
                                                        </button>
                                                        <?php else: ?>
                                                        <button type="button" class="btn btn-outline-success" onclick="activateAccount()">
                                                            <i class="ti ti-user-check me-1"></i>Activate Account
                                                        </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <?php else: ?>
                                            <!-- No User Account - Create New -->
                                            <div class="alert alert-info">
                                                <div class="d-flex align-items-center">
                                                    <div class="me-3">
                                                        <span class="avatar avatar-lg bg-info-lt">
                                                            <i class="ti ti-user-plus fs-1"></i>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <h4 class="mb-1">No User Account</h4>
                                                        <p class="mb-0">This staff member does not have a system login account. Create one below to enable online access.</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="card mb-3">
                                                <div class="card-header">
                                                    <h5 class="card-title mb-0"><i class="ti ti-user-plus me-2"></i>Create User Account</h5>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label required">Username</label>
                                                            <input type="text" name="new_username" class="form-control" id="new_username"
                                                                   value="<?= e(strtolower($staff['first_name'] . '.' . $staff['last_name'])) ?>"
                                                                   placeholder="e.g., john.doe">
                                                            <small class="text-muted">Used for login</small>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label required">Email</label>
                                                            <input type="email" name="new_user_email" class="form-control" id="new_user_email"
                                                                   value="<?= e($staff['email'] ?? '') ?>"
                                                                   placeholder="staff@school.ac.ke">
                                                            <small class="text-muted">For password reset and notifications</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="card mb-3">
                                                <div class="card-header">
                                                    <h5 class="card-title mb-0"><i class="ti ti-shield me-2"></i>Assign Role(s)</h5>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row g-2">
                                                        <?php foreach ($roles ?? [] as $role): ?>
                                                        <div class="col-md-4">
                                                            <label class="form-check">
                                                                <input type="checkbox" name="user_roles[]" class="form-check-input"
                                                                       value="<?= $role['id'] ?>">
                                                                <span class="form-check-label"><?= e($role['display_name']) ?></span>
                                                                <?php if (!empty($role['description'])): ?>
                                                                <small class="d-block text-muted"><?= e($role['description']) ?></small>
                                                                <?php endif; ?>
                                                            </label>
                                                        </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="card mb-3">
                                                <div class="card-header">
                                                    <h5 class="card-title mb-0"><i class="ti ti-send me-2"></i>Send Credentials</h5>
                                                </div>
                                                <div class="card-body">
                                                    <p class="text-muted mb-3">Choose how to notify the staff member about their new account. They will receive a link to set their password.</p>
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <label class="form-check form-check-inline">
                                                                <input type="checkbox" name="send_email_notification" class="form-check-input" value="1" checked>
                                                                <span class="form-check-label">
                                                                    <i class="ti ti-mail me-1"></i>Send Email
                                                                </span>
                                                            </label>
                                                            <small class="d-block text-muted ms-4">
                                                                To: <?= e($staff['email'] ?? 'No email on record') ?>
                                                            </small>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-check form-check-inline">
                                                                <input type="checkbox" name="send_sms_notification" class="form-check-input" value="1">
                                                                <span class="form-check-label">
                                                                    <i class="ti ti-message me-1"></i>Send SMS
                                                                </span>
                                                            </label>
                                                            <small class="d-block text-muted ms-4">
                                                                To: <?= e($staff['phone'] ?? 'No phone on record') ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="alert alert-warning">
                                                <i class="ti ti-alert-triangle me-2"></i>
                                                <strong>Note:</strong> Click "Update Staff" to create the user account and send credentials.
                                            </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="col-lg-4">
                                            <div class="card bg-azure-lt">
                                                <div class="card-body">
                                                    <h4><i class="ti ti-info-circle me-2"></i>About Online Access</h4>
                                                    <ul class="list-unstyled mb-0">
                                                        <li class="mb-2"><i class="ti ti-check text-success me-2"></i>Allows staff to log into the system</li>
                                                        <li class="mb-2"><i class="ti ti-check text-success me-2"></i>Access based on assigned role(s)</li>
                                                        <li class="mb-2"><i class="ti ti-check text-success me-2"></i>View payslips & leave balances</li>
                                                        <li class="mb-2"><i class="ti ti-check text-success me-2"></i>Submit leave requests</li>
                                                        <li class="mb-2"><i class="ti ti-check text-success me-2"></i>Update personal information</li>
                                                    </ul>
                                                </div>
                                            </div>

                                            <div class="card mt-3">
                                                <div class="card-header">
                                                    <h5 class="card-title mb-0">Available Roles</h5>
                                                </div>
                                                <div class="list-group list-group-flush">
                                                    <div class="list-group-item">
                                                        <div class="d-flex">
                                                            <span class="badge bg-danger me-2">ADMIN</span>
                                                            <span>Full system access</span>
                                                        </div>
                                                    </div>
                                                    <div class="list-group-item">
                                                        <div class="d-flex">
                                                            <span class="badge bg-purple me-2">HEAD_TEACHER</span>
                                                            <span>School management</span>
                                                        </div>
                                                    </div>
                                                    <div class="list-group-item">
                                                        <div class="d-flex">
                                                            <span class="badge bg-blue me-2">TEACHER</span>
                                                            <span>Teaching & assessment</span>
                                                        </div>
                                                    </div>
                                                    <div class="list-group-item">
                                                        <div class="d-flex">
                                                            <span class="badge bg-green me-2">BURSAR</span>
                                                            <span>Finance & fees access</span>
                                                        </div>
                                                    </div>
                                                    <div class="list-group-item">
                                                        <div class="d-flex">
                                                            <span class="badge bg-yellow me-2">CLERK</span>
                                                            <span>Data entry & basic ops</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <!-- Card Footer with Action Buttons -->
                        <div class="card-footer">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="text-muted">
                                        <?php if ($isEdit): ?>
                                        Editing: <strong><?= e($staff['first_name'] . ' ' . $staff['last_name']) ?></strong>
                                        <?php else: ?>
                                        <i class="ti ti-info-circle me-1"></i>
                                        Step 1: Save personal information to unlock other tabs
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="col-auto ms-auto">
                                    <div class="btn-list">
                                        <a href="/hr-payroll/staff" class="btn btn-outline-secondary">
                                            <i class="ti ti-x me-1"></i>Cancel
                                        </a>
                                        <?php if ($isEdit): ?>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="ti ti-device-floppy me-1"></i>Save Changes
                                        </button>
                                        <?php else: ?>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="ti ti-arrow-right me-1"></i>Save & Continue
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// JSON data for dynamic form elements
const educationLevelsData = <?= json_encode($educationLevels) ?>;
const leavingReasonsData = <?= json_encode($leavingReasons) ?>;
const referenceRelationshipsData = <?= json_encode($referenceRelationships) ?>;
const salaryGradesData = <?= json_encode($salaryGrades) ?>;
const currency = '<?= $currency ?>';

document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss success alerts after 5 seconds
    const successAlert = document.querySelector('.alert-success');
    if (successAlert) {
        // Scroll to make sure alert is visible
        successAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(successAlert);
            bsAlert.close();
        }, 5000);
    }

    // Track active tab for form submission
    const staffTabs = document.getElementById('staffTabs');
    const activeTabInput = document.getElementById('active_tab');

    if (staffTabs && activeTabInput) {
        staffTabs.querySelectorAll('.nav-link').forEach(tab => {
            tab.addEventListener('shown.bs.tab', function(e) {
                const tabId = e.target.getAttribute('href').substring(1); // Remove the #
                activeTabInput.value = tabId;
            });
        });
    }

    // Salary Grade auto-fill functionality
    const salaryGradeSelect = document.getElementById('salary_grade_select');
    const basicSalaryInput = document.querySelector('input[name="basic_salary"]');
    const salaryRangeHint = document.getElementById('salary-range-hint');

    function updateSalaryRangeHint() {
        if (!salaryGradeSelect || !salaryRangeHint) return;
        const selectedOption = salaryGradeSelect.options[salaryGradeSelect.selectedIndex];
        if (selectedOption && selectedOption.value) {
            const min = parseFloat(selectedOption.dataset.min) || 0;
            const max = parseFloat(selectedOption.dataset.max) || 0;
            salaryRangeHint.textContent = `Range: ${currency} ${min.toLocaleString()} - ${currency} ${max.toLocaleString()}`;
        } else {
            salaryRangeHint.textContent = '';
        }
    }

    if (salaryGradeSelect && basicSalaryInput) {
        // Show range hint on page load
        updateSalaryRangeHint();

        salaryGradeSelect.addEventListener('change', function() {
            updateSalaryRangeHint();

            const selectedId = this.value;
            if (selectedId) {
                const grade = salaryGradesData.find(g => g.id == selectedId);
                if (grade) {
                    // Show confirmation modal or auto-fill
                    const currentSalary = parseFloat(basicSalaryInput.value) || 0;
                    const defaultSalary = parseFloat(grade.default_salary) || 0;

                    if (currentSalary === 0 || confirm(
                        `Apply salary from ${grade.grade_code} - ${grade.grade_name}?\n\n` +
                        `Default Salary: ${currency} ${defaultSalary.toLocaleString()}\n` +
                        `Range: ${currency} ${parseFloat(grade.min_salary).toLocaleString()} - ${currency} ${parseFloat(grade.max_salary).toLocaleString()}\n\n` +
                        `Current: ${currency} ${currentSalary.toLocaleString()}`
                    )) {
                        basicSalaryInput.value = defaultSalary;
                        // Highlight the field briefly
                        basicSalaryInput.style.backgroundColor = '#d4edda';
                        setTimeout(() => {
                            basicSalaryInput.style.backgroundColor = '';
                        }, 1500);
                    }
                }
            }
        });
    }

    // Show/hide bank details based on payment mode
    const paymentModeSelect = document.getElementById('payment_mode');
    const bankFields = ['bank_name_container', 'bank_branch_container', 'account_number_container', 'account_name_container'];
    const mpesaField = document.getElementById('mpesa_number_container');

    function toggleBankFields() {
        const selected = paymentModeSelect?.options[paymentModeSelect.selectedIndex];
        const requiresBank = selected?.dataset.requiresBank === '1';
        const isMpesa = paymentModeSelect?.value === 'mpesa';

        bankFields.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.style.display = requiresBank ? 'block' : 'none';
        });
        if (mpesaField) mpesaField.style.display = isMpesa ? 'block' : 'none';
    }

    paymentModeSelect?.addEventListener('change', toggleBankFields);
    toggleBankFields();

    // Photo preview functionality
    const photoInput = document.getElementById('photo-input');
    const photoPreview = document.getElementById('photo-preview');
    const photoPlaceholder = document.getElementById('photo-placeholder');

    photoInput?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                photoPreview.src = e.target.result;
                photoPreview.style.display = 'block';
                if (photoPlaceholder) photoPlaceholder.style.display = 'none';
            };
            reader.readAsDataURL(file);
        }
    });

    // Generate qualification level options HTML
    function getQualificationOptions() {
        return educationLevelsData.map(el =>
            `<option value="${el.level_code}">${el.level_name}</option>`
        ).join('');
    }

    // Generate leaving reasons options HTML
    function getLeavingReasonOptions() {
        return leavingReasonsData.map(lr =>
            `<option value="${lr.reason_code}">${lr.reason_name}</option>`
        ).join('');
    }

    // Generate reference relationship options HTML
    function getReferenceRelationshipOptions() {
        return referenceRelationshipsData.map(rr =>
            `<option value="${rr.relationship_name}">${rr.relationship_name}</option>`
        ).join('');
    }

    // Add Qualification
    let qualificationIndex = <?= !empty($qualifications) ? count($qualifications) : 1 ?>;
    const addQualificationBtn = document.getElementById('add-qualification');
    const qualificationsContainer = document.getElementById('qualifications-container');

    if (addQualificationBtn && qualificationsContainer) {
        addQualificationBtn.addEventListener('click', function() {
            const template = `
            <div class="qualification-entry card mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Qualification Level</label>
                            <select name="qualifications[${qualificationIndex}][level]" class="form-select qualification-level-select">
                                <option value="">Select</option>
                                ${getQualificationOptions()}
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Field of Study</label>
                            <input type="text" name="qualifications[${qualificationIndex}][field]" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Institution</label>
                            <input type="text" name="qualifications[${qualificationIndex}][institution]" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Year Completed</label>
                            <input type="number" name="qualifications[${qualificationIndex}][year]" class="form-control" min="1970" max="${new Date().getFullYear()}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Grade/Class</label>
                            <input type="text" name="qualifications[${qualificationIndex}][grade]" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Certificate Number</label>
                            <input type="text" name="qualifications[${qualificationIndex}][cert_number]" class="form-control">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-danger btn-remove-qualification w-100">
                                <i class="ti ti-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            `;
            qualificationsContainer.insertAdjacentHTML('beforeend', template);
            qualificationIndex++;
        });
    }

    // Add Employment History
    let employmentIndex = <?= !empty($employmentHistory) ? count($employmentHistory) : 1 ?>;
    const addEmploymentBtn = document.getElementById('add-employment');
    const employmentContainer = document.getElementById('employment-history-container');

    if (addEmploymentBtn && employmentContainer) {
        addEmploymentBtn.addEventListener('click', function() {
            const template = `
            <div class="employment-entry card mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Employer / Organization</label>
                            <input type="text" name="employment_history[${employmentIndex}][employer]" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Job Title / Position</label>
                            <input type="text" name="employment_history[${employmentIndex}][position]" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="employment_history[${employmentIndex}][start_date]" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="employment_history[${employmentIndex}][end_date]" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reason for Leaving</label>
                            <select name="employment_history[${employmentIndex}][reason_leaving]" class="form-select leaving-reason-select">
                                <option value="">Select</option>
                                ${getLeavingReasonOptions()}
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Key Responsibilities</label>
                            <textarea name="employment_history[${employmentIndex}][responsibilities]" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Supervisor Name</label>
                            <input type="text" name="employment_history[${employmentIndex}][supervisor]" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Phone</label>
                            <input type="tel" name="employment_history[${employmentIndex}][contact_phone]" class="form-control">
                        </div>
                        <div class="col-12 text-end">
                            <button type="button" class="btn btn-outline-danger btn-remove-employment">
                                <i class="ti ti-trash me-1"></i>Remove
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            `;
            employmentContainer.insertAdjacentHTML('beforeend', template);
            employmentIndex++;
        });
    }

    // Add Reference
    let referenceIndex = <?= !empty($references) ? count($references) : 2 ?>;
    const addReferenceBtn = document.getElementById('add-reference');
    const referencesContainer = document.getElementById('references-container');

    if (addReferenceBtn && referencesContainer) {
        addReferenceBtn.addEventListener('click', function() {
            const template = `
            <div class="reference-entry card mb-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">Reference ${referenceIndex + 1}</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="references[${referenceIndex}][name]" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Relationship</label>
                            <select name="references[${referenceIndex}][relationship]" class="form-select reference-relationship-select">
                                <option value="">Select</option>
                                ${getReferenceRelationshipOptions()}
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Organization / Company</label>
                            <input type="text" name="references[${referenceIndex}][organization]" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Position / Title</label>
                            <input type="text" name="references[${referenceIndex}][position]" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="references[${referenceIndex}][phone]" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Email</label>
                            <input type="email" name="references[${referenceIndex}][email]" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Years Known</label>
                            <input type="number" name="references[${referenceIndex}][years_known]" class="form-control" min="0">
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button type="button" class="btn btn-outline-danger btn-remove-reference">
                        <i class="ti ti-trash me-1"></i>Remove
                    </button>
                </div>
            </div>
            `;
            referencesContainer.insertAdjacentHTML('beforeend', template);
            referenceIndex++;
        });
    }

    // Remove entries (event delegation)
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-remove-qualification')) {
            e.target.closest('.qualification-entry').remove();
        }
        if (e.target.closest('.btn-remove-employment')) {
            e.target.closest('.employment-entry').remove();
        }
        if (e.target.closest('.btn-remove-reference')) {
            e.target.closest('.reference-entry').remove();
        }
    });

    // =========================================================================
    // Document Upload Functionality
    // =========================================================================

    const staffId = <?= $isEdit ? $staff['id'] : 'null' ?>;
    let uploadToken = null;
    let uploadCheckInterval = null;

    // Upload button click - show modal
    document.querySelectorAll('.btn-upload-doc').forEach(btn => {
        btn.addEventListener('click', function() {
            const docType = this.dataset.docType;
            const docLabel = this.dataset.docLabel;
            showUploadModal(docType, docLabel);
        });
    });

    function showUploadModal(docType, docLabel) {
        document.getElementById('upload_doc_type').value = docType;
        document.getElementById('upload_doc_type_label').textContent = docLabel;

        // Reset modal state
        document.getElementById('step_choose_method').style.display = 'block';
        document.getElementById('step_computer_upload').style.display = 'none';
        document.getElementById('step_phone_qr').style.display = 'none';

        // Clear any existing intervals
        if (uploadCheckInterval) {
            clearInterval(uploadCheckInterval);
            uploadCheckInterval = null;
        }

        // Show modal
        const uploadModal = new bootstrap.Modal(document.getElementById('documentUploadModal'));
        uploadModal.show();
    }

    window.selectUploadMethod = function(method) {
        const docType = document.getElementById('upload_doc_type').value;

        if (method === 'computer') {
            document.getElementById('step_choose_method').style.display = 'none';
            document.getElementById('step_computer_upload').style.display = 'block';
            document.getElementById('computer_doc_type').value = docType;
        } else if (method === 'phone') {
            document.getElementById('step_choose_method').style.display = 'none';
            document.getElementById('step_phone_qr').style.display = 'block';
            generateQRCode(docType);
        }
    };

    window.backToMethodSelection = function() {
        document.getElementById('step_choose_method').style.display = 'block';
        document.getElementById('step_computer_upload').style.display = 'none';
        document.getElementById('step_phone_qr').style.display = 'none';

        if (uploadCheckInterval) {
            clearInterval(uploadCheckInterval);
            uploadCheckInterval = null;
        }
    };

    async function generateQRCode(docType) {
        try {
            document.getElementById('qr_code_spinner').style.display = 'block';
            document.getElementById('qr_code_display').style.display = 'none';

            const response = await fetch('/hr-payroll/documents/generate-upload-token', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    staff_id: staffId,
                    document_type: docType
                })
            });

            const data = await response.json();

            if (data.success) {
                uploadToken = data.token;
                const uploadUrl = window.location.origin + '/hr-payroll/documents/capture/' + uploadToken;

                // Clear previous QR code
                const qrContainer = document.getElementById('qr_code');
                qrContainer.innerHTML = '';

                // Generate QR code
                new QRCode(qrContainer, {
                    text: uploadUrl,
                    width: 250,
                    height: 250,
                    colorDark: '#000000',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.H
                });

                document.getElementById('qr_code_spinner').style.display = 'none';
                document.getElementById('qr_code_display').style.display = 'block';

                // Start checking for upload completion
                startUploadCheck(uploadToken);
            } else {
                alert('Failed to generate upload link: ' + (data.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error generating QR code:', error);
            alert('Failed to generate QR code. Please try again.');
        }
    }

    function startUploadCheck(token) {
        document.getElementById('upload_status').style.display = 'block';
        document.getElementById('upload_status_text').textContent = 'Waiting for upload from phone...';

        uploadCheckInterval = setInterval(async () => {
            try {
                const response = await fetch('/hr-payroll/documents/check-upload-status/' + token, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const data = await response.json();

                if (data.status === 'completed') {
                    clearInterval(uploadCheckInterval);
                    document.getElementById('upload_status_text').innerHTML = '<i class="ti ti-circle-check me-2"></i>Document uploaded successfully!';
                    document.getElementById('upload_status').classList.remove('alert-info');
                    document.getElementById('upload_status').classList.add('alert-success');

                    setTimeout(() => {
                        bootstrap.Modal.getInstance(document.getElementById('documentUploadModal')).hide();
                        window.location.reload();
                    }, 2000);
                } else if (data.status === 'expired') {
                    clearInterval(uploadCheckInterval);
                    document.getElementById('upload_status_text').innerHTML = '<i class="ti ti-alert-triangle me-2"></i>Upload link expired. Please generate a new one.';
                    document.getElementById('upload_status').classList.remove('alert-info');
                    document.getElementById('upload_status').classList.add('alert-warning');
                }
            } catch (error) {
                console.error('Error checking upload status:', error);
            }
        }, 3000);
    }

    // File preview for computer upload
    const documentFileInput = document.getElementById('document_file');
    if (documentFileInput) {
        documentFileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const label = this.nextElementSibling;
                if (label) label.textContent = file.name;

                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        document.getElementById('doc_preview_image').src = e.target.result;
                        document.getElementById('doc_file_preview').style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    document.getElementById('doc_file_preview').style.display = 'none';
                }
            }
        });
    }

    // Download document using iframe (most reliable method, bypasses all routing)
    window.downloadDocument = function(entity, docId, fileName) {
        // Create a hidden iframe to trigger the download
        const iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        iframe.src = '/download.php?entity=' + encodeURIComponent(entity) + '&id=' + encodeURIComponent(docId);
        document.body.appendChild(iframe);

        // Remove iframe after download starts (give it 30 seconds)
        setTimeout(function() {
            if (iframe.parentNode) {
                iframe.parentNode.removeChild(iframe);
            }
        }, 30000);
    };

    // Delete document
    document.querySelectorAll('.btn-delete-doc').forEach(btn => {
        btn.addEventListener('click', async function() {
            const docId = this.dataset.docId;
            if (!confirm('Are you sure you want to delete this document?')) return;

            try {
                const response = await fetch('/hr-payroll/documents/delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ document_id: docId })
                });

                const data = await response.json();

                if (data.success) {
                    document.getElementById('doc-row-' + docId).remove();
                } else {
                    alert('Failed to delete document: ' + (data.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error deleting document:', error);
                alert('Failed to delete document');
            }
        });
    });

    // Clean up interval when modal closes
    const docUploadModal = document.getElementById('documentUploadModal');
    if (docUploadModal) {
        docUploadModal.addEventListener('hidden.bs.modal', function () {
            if (uploadCheckInterval) {
                clearInterval(uploadCheckInterval);
                uploadCheckInterval = null;
            }
        });
    }

    // =========================================================================
    // Staff Photo QR Upload
    // =========================================================================

    let photoUploadToken = null;
    let photoCheckInterval = null;

    const btnPhotoQr = document.getElementById('btn-photo-qr');
    if (btnPhotoQr) {
        btnPhotoQr.addEventListener('click', function(e) {
            e.preventDefault();
            showPhotoQrModal();
        });
    }

    async function showPhotoQrModal() {
        const modal = new bootstrap.Modal(document.getElementById('photoQrModal'));
        modal.show();

        // Generate QR code for photo upload
        try {
            document.getElementById('photo_qr_spinner').style.display = 'block';
            document.getElementById('photo_qr_display').style.display = 'none';
            document.getElementById('photo_qr_status').style.display = 'none';

            const response = await fetch('/hr-payroll/documents/generate-upload-token', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    staff_id: staffId,
                    document_type: 'passport_photo'
                })
            });

            const data = await response.json();

            if (data.success) {
                photoUploadToken = data.token;
                const uploadUrl = window.location.origin + '/hr-payroll/documents/capture/' + photoUploadToken;

                // Clear previous QR code
                const qrContainer = document.getElementById('photo_qr_code');
                qrContainer.innerHTML = '';

                // Generate QR code
                new QRCode(qrContainer, {
                    text: uploadUrl,
                    width: 200,
                    height: 200,
                    colorDark: '#000000',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.H
                });

                document.getElementById('photo_qr_spinner').style.display = 'none';
                document.getElementById('photo_qr_display').style.display = 'block';

                // Start checking for upload completion
                startPhotoUploadCheck(photoUploadToken);
            } else {
                alert('Failed to generate upload link: ' + (data.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error generating QR code:', error);
            alert('Failed to generate QR code. Please try again.');
        }
    }

    function startPhotoUploadCheck(token) {
        document.getElementById('photo_qr_status').style.display = 'block';
        document.getElementById('photo_qr_status_text').textContent = 'Waiting for photo from phone...';

        photoCheckInterval = setInterval(async () => {
            try {
                const response = await fetch('/hr-payroll/documents/check-upload-status/' + token, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const data = await response.json();

                if (data.status === 'completed') {
                    clearInterval(photoCheckInterval);
                    document.getElementById('photo_qr_status_text').innerHTML = '<i class="ti ti-circle-check me-2"></i>Photo uploaded successfully!';
                    document.getElementById('photo_qr_status').classList.remove('alert-info');
                    document.getElementById('photo_qr_status').classList.add('alert-success');

                    setTimeout(() => {
                        bootstrap.Modal.getInstance(document.getElementById('photoQrModal')).hide();
                        window.location.reload();
                    }, 2000);
                } else if (data.status === 'expired') {
                    clearInterval(photoCheckInterval);
                    document.getElementById('photo_qr_status_text').innerHTML = '<i class="ti ti-alert-triangle me-2"></i>Link expired. Please try again.';
                    document.getElementById('photo_qr_status').classList.remove('alert-info');
                    document.getElementById('photo_qr_status').classList.add('alert-warning');
                }
            } catch (error) {
                console.error('Error checking upload status:', error);
            }
        }, 3000);
    }

    // Clean up interval when photo modal closes
    const photoQrModal = document.getElementById('photoQrModal');
    if (photoQrModal) {
        photoQrModal.addEventListener('hidden.bs.modal', function () {
            if (photoCheckInterval) {
                clearInterval(photoCheckInterval);
                photoCheckInterval = null;
            }
        });
    }
});
</script>

<!-- QR Code Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<!-- Document Upload Modal -->
<?php if ($isEdit): ?>
<div class="modal fade" id="documentUploadModal" tabindex="-1" aria-labelledby="documentUploadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="documentUploadModalLabel">
                    <i class="ti ti-upload me-2"></i>
                    Upload <span id="upload_doc_type_label"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="upload_doc_type" value="">

                <!-- Step 1: Choose Upload Method -->
                <div id="step_choose_method">
                    <h5 class="mb-4">How would you like to upload the document?</h5>

                    <div class="row">
                        <!-- Option 1: Upload from Computer -->
                        <div class="col-md-6 mb-3">
                            <div class="card h-100 border-primary" style="cursor: pointer;" onclick="selectUploadMethod('computer')">
                                <div class="card-body text-center">
                                    <i class="ti ti-device-laptop mb-3" style="font-size: 4rem; color: #206bc4;"></i>
                                    <h5 class="card-title">Upload from Computer</h5>
                                    <p class="card-text text-muted">
                                        Select a file from your computer
                                    </p>
                                    <button type="button" class="btn btn-primary" onclick="selectUploadMethod('computer')">
                                        <i class="ti ti-folder me-2"></i>Choose File
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Option 2: Capture with Phone Camera -->
                        <div class="col-md-6 mb-3">
                            <div class="card h-100 border-success" style="cursor: pointer;" onclick="selectUploadMethod('phone')">
                                <div class="card-body text-center">
                                    <i class="ti ti-device-mobile mb-3" style="font-size: 4rem; color: #2fb344;"></i>
                                    <h5 class="card-title">Capture with Phone</h5>
                                    <p class="card-text text-muted">
                                        Scan QR code to take a photo
                                    </p>
                                    <button type="button" class="btn btn-success" onclick="selectUploadMethod('phone')">
                                        <i class="ti ti-qrcode me-2"></i>Generate QR Code
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2a: Computer Upload Form -->
                <div id="step_computer_upload" style="display: none;">
                    <button type="button" class="btn btn-sm btn-secondary mb-3" onclick="backToMethodSelection()">
                        <i class="ti ti-arrow-left me-2"></i>Back
                    </button>

                    <form method="POST" action="/hr-payroll/documents/upload" enctype="multipart/form-data">
                        <input type="hidden" name="staff_id" value="<?= $staff['id'] ?>">
                        <input type="hidden" name="document_type" id="computer_doc_type">

                        <div class="mb-3">
                            <label for="document_file" class="form-label">Select File <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="document_file" name="document_file" accept="image/*,.pdf" required>
                            <small class="form-text text-muted">
                                Accepted formats: JPG, PNG, PDF. Maximum file size: 5MB
                            </small>
                        </div>

                        <div class="mb-3">
                            <label for="document_notes" class="form-label">Notes (Optional)</label>
                            <textarea class="form-control" id="document_notes" name="notes" rows="2" placeholder="Add any notes about this document..."></textarea>
                        </div>

                        <!-- File Preview -->
                        <div id="doc_file_preview" class="mb-3" style="display: none;">
                            <h6>Preview:</h6>
                            <img id="doc_preview_image" src="" alt="Preview" class="img-thumbnail" style="max-width: 300px; max-height: 300px;">
                        </div>

                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="ti ti-x me-1"></i>Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-upload me-2"></i>Upload Document
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Step 2b: Phone Camera QR Code -->
                <div id="step_phone_qr" style="display: none;">
                    <button type="button" class="btn btn-sm btn-secondary mb-3" onclick="backToMethodSelection()">
                        <i class="ti ti-arrow-left me-2"></i>Back
                    </button>

                    <div class="text-center">
                        <h5 class="mb-3">Scan QR Code with Your Phone</h5>
                        <p class="text-muted mb-4">
                            Open your phone's camera and point it at this QR code to start capturing the document
                        </p>

                        <!-- QR Code Display -->
                        <div id="qr_code_container" class="mb-4">
                            <div id="qr_code_spinner" class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Generating QR code...</span>
                                </div>
                                <p class="mt-2">Generating secure link...</p>
                            </div>
                            <div id="qr_code_display" style="display: none;">
                                <div id="qr_code" style="display: inline-block;"></div>
                            </div>
                        </div>

                        <!-- Upload Status -->
                        <div id="upload_status" class="alert alert-info" style="display: none;">
                            <i class="ti ti-info-circle me-2"></i>
                            <span id="upload_status_text">Waiting for upload from phone...</span>
                        </div>

                        <!-- Session Info -->
                        <div class="card bg-light">
                            <div class="card-body">
                                <small class="text-muted">
                                    <i class="ti ti-lock me-1"></i>
                                    This is a secure, one-time link that expires in <strong>10 minutes</strong>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Photo QR Upload Modal -->
<div class="modal fade" id="photoQrModal" tabindex="-1" aria-labelledby="photoQrModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="photoQrModalLabel">
                    <i class="ti ti-camera me-2"></i>
                    Capture Photo with Phone
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <p class="text-muted mb-4">
                    Scan this QR code with your phone camera to capture a passport photo
                </p>

                <!-- QR Code Display -->
                <div id="photo_qr_spinner" class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Generating QR code...</span>
                    </div>
                    <p class="mt-2">Generating secure link...</p>
                </div>
                <div id="photo_qr_display" style="display: none;">
                    <div id="photo_qr_code" style="display: inline-block;"></div>
                </div>

                <!-- Upload Status -->
                <div id="photo_qr_status" class="alert alert-info mt-3" style="display: none;">
                    <i class="ti ti-info-circle me-2"></i>
                    <span id="photo_qr_status_text">Waiting for photo from phone...</span>
                </div>

                <!-- Info -->
                <div class="card bg-light mt-3">
                    <div class="card-body">
                        <small class="text-muted">
                            <i class="ti ti-lock me-1"></i>
                            Secure one-time link - Expires in <strong>10 minutes</strong>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
