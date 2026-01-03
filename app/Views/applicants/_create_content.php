<?php
/**
 * Applicant Application Capture Form
 * Creates new applicant in Draft status
 */
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <a href="/applicants" class="btn btn-ghost-secondary btn-sm mb-2">
                    <i class="ti ti-arrow-left me-1"></i>Back to Applications
                </a>
                <h2 class="page-title">
                    <i class="ti ti-user-plus me-2"></i>New Application
                </h2>
                <div class="text-muted mt-1">
                    Create a new student application
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <form method="POST" action="/applicants/create" enctype="multipart/form-data" id="applicant-form">
            <div class="row">
                <div class="col-lg-8">
                    <!-- Personal Information Card -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Personal Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label required">First Name</label>
                                    <input type="text" class="form-control" name="first_name" required
                                           placeholder="Enter first name" value="<?= old('first_name') ?>">
                                    <?php if ($error = error('first_name')): ?>
                                        <small class="text-danger"><?= e($error) ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Middle Name</label>
                                    <input type="text" class="form-control" name="middle_name"
                                           placeholder="Enter middle name" value="<?= old('middle_name') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label required">Last Name</label>
                                    <input type="text" class="form-control" name="last_name" required
                                           placeholder="Enter last name" value="<?= old('last_name') ?>">
                                    <?php if ($error = error('last_name')): ?>
                                        <small class="text-danger"><?= e($error) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label required">Date of Birth</label>
                                    <input type="date" class="form-control" name="date_of_birth" required
                                           max="<?= date('Y-m-d') ?>" value="<?= old('date_of_birth') ?>">
                                    <?php if ($error = error('date_of_birth')): ?>
                                        <small class="text-danger"><?= e($error) ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label required">Gender</label>
                                    <select class="form-select" name="gender" required>
                                        <option value="">Select gender</option>
                                        <option value="Male" <?= old('gender') === 'Male' ? 'selected' : '' ?>>Male</option>
                                        <option value="Female" <?= old('gender') === 'Female' ? 'selected' : '' ?>>Female</option>
                                    </select>
                                    <?php if ($error = error('gender')): ?>
                                        <small class="text-danger"><?= e($error) ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label required">Nationality</label>
                                    <select class="form-select" name="nationality" required>
                                        <option value="">Select nationality</option>
                                        <?php foreach ($countries ?? [] as $country): ?>
                                            <option value="<?= e($country['country_name']) ?>"
                                                    <?= old('nationality', 'Kenya') === $country['country_name'] ? 'selected' : '' ?>>
                                                <?= e($country['country_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if ($error = error('nationality')): ?>
                                        <small class="text-danger"><?= e($error) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label required">Grade Applying For</label>
                                    <select class="form-select" name="grade_applying_for_id" required>
                                        <option value="">Select grade</option>
                                        <?php foreach ($grades as $grade): ?>
                                            <option value="<?= $grade['id'] ?>"
                                                    <?= old('grade_applying_for_id') == $grade['id'] ? 'selected' : '' ?>>
                                                <?= e($grade['grade_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if ($error = error('grade_applying_for_id')): ?>
                                        <small class="text-danger"><?= e($error) ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Campaign / Intake</label>
                                    <select class="form-select" name="intake_campaign_id">
                                        <option value="">Select campaign (optional)</option>
                                        <?php if (!empty($campaigns)): ?>
                                            <?php foreach ($campaigns as $campaign): ?>
                                                <option value="<?= $campaign['id'] ?>"
                                                        <?= old('intake_campaign_id') == $campaign['id'] ? 'selected' : '' ?>>
                                                    <?= e($campaign['campaign_name']) ?><?= !empty($campaign['year']) ? ' (' . e($campaign['year']) . ')' : '' ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <label class="form-label">Previous School</label>
                                    <input type="text" class="form-control" name="prior_school"
                                           placeholder="Name of previous school (if applicable)"
                                           value="<?= old('prior_school') ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information Card -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Contact Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label required">Phone Number</label>
                                    <input type="tel" class="form-control" name="phone" required
                                           placeholder="e.g., 0712345678" value="<?= old('phone') ?>">
                                    <small class="form-hint">Primary contact number</small>
                                    <?php if ($error = error('phone')): ?>
                                        <small class="text-danger"><?= e($error) ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" class="form-control" name="email"
                                           placeholder="email@example.com" value="<?= old('email') ?>">
                                    <small class="form-hint">Optional but recommended</small>
                                    <?php if ($error = error('email')): ?>
                                        <small class="text-danger"><?= e($error) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="form-label">Physical Address</label>
                                    <textarea class="form-control" name="address" rows="2"
                                              placeholder="Street address, apartment, etc."><?= old('address') ?></textarea>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">City / Town</label>
                                    <input type="text" class="form-control" name="city"
                                           placeholder="Enter city" value="<?= old('city') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Country</label>
                                    <select class="form-select" name="country">
                                        <option value="">Select country</option>
                                        <?php foreach ($countries ?? [] as $country): ?>
                                            <option value="<?= e($country['country_name']) ?>"
                                                    <?= old('country', 'Kenya') === $country['country_name'] ? 'selected' : '' ?>>
                                                <?= e($country['country_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Guardian Information Card -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Primary Guardian / Parent</h3>
                            <div class="card-actions">
                                <span class="text-muted small">You can add more guardians later</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label required">Guardian First Name</label>
                                    <input type="text" class="form-control" name="guardian_first_name" required
                                           placeholder="Enter first name" value="<?= old('guardian_first_name') ?>">
                                    <?php if ($error = error('guardian_first_name')): ?>
                                        <small class="text-danger"><?= e($error) ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label required">Guardian Last Name</label>
                                    <input type="text" class="form-control" name="guardian_last_name" required
                                           placeholder="Enter last name" value="<?= old('guardian_last_name') ?>">
                                    <?php if ($error = error('guardian_last_name')): ?>
                                        <small class="text-danger"><?= e($error) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label required">Relationship</label>
                                    <select class="form-select" name="guardian_relationship" required>
                                        <option value="">Select</option>
                                        <option value="Father" <?= old('guardian_relationship') === 'Father' ? 'selected' : '' ?>>Father</option>
                                        <option value="Mother" <?= old('guardian_relationship') === 'Mother' ? 'selected' : '' ?>>Mother</option>
                                        <option value="Guardian" <?= old('guardian_relationship') === 'Guardian' ? 'selected' : '' ?>>Guardian</option>
                                        <option value="Sibling" <?= old('guardian_relationship') === 'Sibling' ? 'selected' : '' ?>>Sibling</option>
                                        <option value="Other" <?= old('guardian_relationship') === 'Other' ? 'selected' : '' ?>>Other</option>
                                    </select>
                                    <?php if ($error = error('guardian_relationship')): ?>
                                        <small class="text-danger"><?= e($error) ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label required">Guardian Phone</label>
                                    <input type="tel" class="form-control" name="guardian_phone" required
                                           placeholder="e.g., 0712345678" value="<?= old('guardian_phone') ?>">
                                    <?php if ($error = error('guardian_phone')): ?>
                                        <small class="text-danger"><?= e($error) ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Guardian Email</label>
                                    <input type="email" class="form-control" name="guardian_email"
                                           placeholder="email@example.com" value="<?= old('guardian_email') ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons at Bottom -->
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <a href="/applicants" class="btn btn-ghost-secondary">
                                    <i class="ti ti-x me-1"></i>Cancel
                                </a>
                                <div class="btn-list">
                                    <button type="submit" name="action" value="draft" class="btn btn-secondary">
                                        <i class="ti ti-device-floppy me-1"></i>Save as Draft
                                    </button>
                                    <button type="submit" name="action" value="submit" class="btn btn-primary">
                                        <i class="ti ti-send me-1"></i>Submit Application
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Help Sidebar -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="ti ti-help me-2"></i>Help</h3>
                        </div>
                        <div class="card-body">
                            <h5>Required Fields</h5>
                            <p class="text-muted">
                                Fields marked with <span class="text-danger">*</span> are required.
                            </p>

                            <h5 class="mt-3">Save vs Submit</h5>
                            <p class="text-muted">
                                <strong>Save as Draft:</strong> Save progress and continue later.<br>
                                <strong>Submit Application:</strong> Complete and submit for review.
                            </p>

                            <h5 class="mt-3">After Submission</h5>
                            <p class="text-muted">
                                An SMS/Email acknowledgement will be sent with the application reference number.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
.required::after {
    content: " *";
    color: #dc3545;
}
</style>

<script>
// Form validation
document.getElementById('applicant-form').addEventListener('submit', function(e) {
    const action = e.submitter.value;

    if (action === 'submit') {
        const requiredFields = this.querySelectorAll('[required]');
        let allValid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                allValid = false;
                field.classList.add('is-invalid');
            } else {
                field.classList.remove('is-invalid');
            }
        });

        if (!allValid) {
            e.preventDefault();
            alert('Please fill in all required fields before submitting.');
        }
    }
});

// Auto-format phone numbers (Kenyan format)
document.querySelectorAll('input[type="tel"]').forEach(input => {
    input.addEventListener('blur', function() {
        let value = this.value.replace(/\D/g, '');
        if (value.startsWith('254')) {
            value = '0' + value.substring(3);
        }
        this.value = value;
    });
});
</script>
