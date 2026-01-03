<?php
/**
 * Applicant Profile - Overview Tab
 */
?>

<style>
/* Overview Tab Specific Styles */
.overview-section-title {
    font-size: 16px;
    font-weight: 600;
    color: #323130;
    margin-bottom: 16px;
    padding-bottom: 8px;
    border-bottom: 2px solid #0078d4;
}
.overview-subsection-title {
    font-size: 14px;
    font-weight: 600;
    color: #605e5c;
    margin-bottom: 12px;
}
.info-table {
    width: 100%;
    border-collapse: collapse;
}
.info-table tr:nth-child(odd) {
    background-color: #faf9f8;
}
.info-table tr:nth-child(even) {
    background-color: #ffffff;
}
.info-table td {
    padding: 10px 12px;
    vertical-align: top;
    border-bottom: 1px solid #edebe9;
}
.info-table .label-cell {
    width: 40%;
    font-size: 12px;
    font-weight: 600;
    color: #605e5c;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.info-table .value-cell {
    font-size: 14px;
    color: #323130;
}
.info-table .value-cell strong {
    font-weight: 600;
    color: #0078d4;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="overview-section-title mb-0" style="border-bottom: none; padding-bottom: 0;">Applicant Information</h2>
    <?php if (hasPermission('Students.write') || Gate::hasRole('ADMIN')): ?>
    <button type="button" class="btn btn-primary" onclick="showEditApplicantModal(<?= $applicant['id'] ?>, <?= htmlspecialchars(json_encode($applicant), ENT_QUOTES, 'UTF-8') ?>)">
        <i class="ti ti-edit me-1"></i> Edit Details
    </button>
    <?php endif; ?>
</div>

<div class="row">
    <!-- Personal Information -->
    <div class="col-md-6 mb-4">
        <h5 class="overview-subsection-title">Personal Information</h5>
        <table class="info-table">
            <tr>
                <td class="label-cell">First Name</td>
                <td class="value-cell"><?= e($applicant['first_name']) ?></td>
            </tr>
            <tr>
                <td class="label-cell">Middle Name</td>
                <td class="value-cell"><?= e($applicant['middle_name'] ?? 'N/A') ?></td>
            </tr>
            <tr>
                <td class="label-cell">Last Name</td>
                <td class="value-cell"><?= e($applicant['last_name']) ?></td>
            </tr>
            <tr>
                <td class="label-cell">Date of Birth</td>
                <td class="value-cell"><?= e($applicant['date_of_birth'] ? formatDate($applicant['date_of_birth']) : 'N/A') ?></td>
            </tr>
            <tr>
                <td class="label-cell">Gender</td>
                <td class="value-cell"><?= e(ucfirst($applicant['gender'] ?? 'N/A')) ?></td>
            </tr>
            <tr>
                <td class="label-cell">Birth Certificate No</td>
                <td class="value-cell"><?= e($applicant['birth_cert_no'] ?? 'N/A') ?></td>
            </tr>
            <tr>
                <td class="label-cell">Nationality</td>
                <td class="value-cell"><?= e($applicant['nationality'] ?? 'N/A') ?></td>
            </tr>
        </table>
    </div>

    <!-- Application Details -->
    <div class="col-md-6 mb-4">
        <h5 class="overview-subsection-title">Application Details</h5>
        <table class="info-table">
            <tr>
                <td class="label-cell">Application Ref</td>
                <td class="value-cell"><strong><?= e($applicant['application_ref']) ?></strong></td>
            </tr>
            <tr>
                <td class="label-cell">Status</td>
                <td class="value-cell"><?= formatStatus($applicant['status']) ?></td>
            </tr>
            <tr>
                <td class="label-cell">Grade Applying For</td>
                <td class="value-cell"><?= e($applicant['grade_name']) ?> (<?= e($applicant['grade_category']) ?>)</td>
            </tr>
            <tr>
                <td class="label-cell">Academic Year</td>
                <td class="value-cell"><?= e($applicant['year_name'] ?? 'N/A') ?></td>
            </tr>
            <tr>
                <td class="label-cell">Campaign</td>
                <td class="value-cell"><?= e($applicant['campaign_name'] ?? 'N/A') ?></td>
            </tr>
            <tr>
                <td class="label-cell">Previous School</td>
                <td class="value-cell"><?= e($applicant['previous_school'] ?? 'N/A') ?></td>
            </tr>
            <tr>
                <td class="label-cell">Application Date</td>
                <td class="value-cell"><?= e($applicant['application_date'] ? formatDate($applicant['application_date']) : 'N/A') ?></td>
            </tr>
            <tr>
                <td class="label-cell">Created By</td>
                <td class="value-cell"><?= e($applicant['created_by_name'] ?? 'N/A') ?></td>
            </tr>
            <tr>
                <td class="label-cell">Created At</td>
                <td class="value-cell"><?= formatDate($applicant['created_at']) ?></td>
            </tr>
        </table>
    </div>
</div>

<hr class="my-4" style="border-color: #edebe9;">

<!-- Contact Information -->
<div class="row">
    <div class="col-12 mb-4">
        <h5 class="overview-subsection-title">Contact Information</h5>
        <?php if (empty($contacts)): ?>
            <div class="alert alert-info" style="font-size: 13px;">
                <i class="fas fa-info-circle"></i> No contact information available.
            </div>
        <?php else: ?>
            <table class="info-table">
                <?php foreach ($contacts as $index => $contact): ?>
                <tr>
                    <td class="label-cell">
                        Phone
                        <?php if ($contact['is_primary']): ?>
                            <span class="badge bg-primary ms-1" style="font-size: 10px;">Primary</span>
                        <?php endif; ?>
                    </td>
                    <td class="value-cell"><?= e($contact['phone']) ?></td>
                </tr>
                <tr>
                    <td class="label-cell">Email</td>
                    <td class="value-cell"><?= e($contact['email'] ?? 'N/A') ?></td>
                </tr>
                <tr>
                    <td class="label-cell">Address</td>
                    <td class="value-cell"><?= e($contact['address'] ?? 'N/A') ?></td>
                </tr>
                <?php if (count($contacts) > 1 && $index < count($contacts) - 1): ?>
                <tr><td colspan="2" style="padding: 4px; background: #edebe9;"></td></tr>
                <?php endif; ?>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</div>

<hr class="my-4" style="border-color: #edebe9;">

<!-- Medical & Special Needs -->
<div class="row">
    <div class="col-md-6 mb-4">
        <h5 class="overview-subsection-title">Medical Conditions</h5>
        <div class="p-3" style="background: #faf9f8; border-radius: 4px; font-size: 14px; color: <?= !empty($applicant['medical_conditions']) ? '#323130' : '#a19f9d' ?>;">
            <?= e($applicant['medical_conditions'] ?? 'None reported') ?>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <h5 class="overview-subsection-title">Special Needs</h5>
        <div class="p-3" style="background: #faf9f8; border-radius: 4px; font-size: 14px; color: <?= !empty($applicant['special_needs']) ? '#323130' : '#a19f9d' ?>;">
            <?= e($applicant['special_needs'] ?? 'None reported') ?>
        </div>
    </div>
</div>

<!-- Notes -->
<div class="row">
    <div class="col-12">
        <h5 class="overview-subsection-title">Notes</h5>
        <div class="p-3" style="background: #faf9f8; border-radius: 4px; font-size: 14px; color: <?= !empty($applicant['notes']) ? '#323130' : '#a19f9d' ?>; line-height: 1.6;">
            <?= !empty($applicant['notes']) ? nl2br(e($applicant['notes'])) : 'No notes' ?>
        </div>
    </div>
</div>
