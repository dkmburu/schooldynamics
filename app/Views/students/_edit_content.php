<?php
/**
 * Edit Student Content
 */
$fullName = $student['first_name'] . ' ' . ($student['middle_name'] ? $student['middle_name'] . ' ' : '') . $student['last_name'];
?>

<!-- Page Header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <a href="/students/<?= $student['id'] ?>" class="btn btn-outline-secondary btn-sm me-2">
                    <i class="ti ti-arrow-left"></i>
                </a>
            </div>
            <div class="col">
                <h2 class="page-title">Edit Student</h2>
                <div class="text-muted"><?= e($fullName) ?> (<?= e($student['admission_number']) ?>)</div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <form action="/students/<?= $student['id'] ?>/edit" method="POST">
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Personal Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label required">First Name</label>
                                        <input type="text" name="first_name" class="form-control" value="<?= e($student['first_name']) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Middle Name</label>
                                        <input type="text" name="middle_name" class="form-control" value="<?= e($student['middle_name'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label required">Last Name</label>
                                        <input type="text" name="last_name" class="form-control" value="<?= e($student['last_name']) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Date of Birth</label>
                                        <input type="date" name="date_of_birth" class="form-control" value="<?= e($student['date_of_birth'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Gender</label>
                                        <select name="gender" class="form-select">
                                            <option value="">Select...</option>
                                            <option value="male" <?= $student['gender'] === 'male' ? 'selected' : '' ?>>Male</option>
                                            <option value="female" <?= $student['gender'] === 'female' ? 'selected' : '' ?>>Female</option>
                                            <option value="other" <?= $student['gender'] === 'other' ? 'selected' : '' ?>>Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-select">
                                            <option value="active" <?= $student['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                            <option value="suspended" <?= $student['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                                            <option value="transferred" <?= $student['status'] === 'transferred' ? 'selected' : '' ?>>Transferred</option>
                                            <option value="graduated" <?= $student['status'] === 'graduated' ? 'selected' : '' ?>>Graduated</option>
                                            <option value="withdrawn" <?= $student['status'] === 'withdrawn' ? 'selected' : '' ?>>Withdrawn</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h3 class="card-title">Academic Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Campus</label>
                                        <select name="campus_id" class="form-select">
                                            <?php foreach ($campuses as $campus): ?>
                                            <option value="<?= $campus['id'] ?>" <?= $student['campus_id'] == $campus['id'] ? 'selected' : '' ?>>
                                                <?= e($campus['campus_name']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Class/Stream</label>
                                        <select name="stream_id" class="form-select">
                                            <option value="">Select...</option>
                                            <?php foreach ($streams as $stream): ?>
                                            <option value="<?= $stream['id'] ?>" <?= ($student['stream_id'] ?? '') == $stream['id'] ? 'selected' : '' ?>>
                                                <?= e($stream['display_name']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Student Info</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Admission Number</label>
                                <input type="text" class="form-control" value="<?= e($student['admission_number']) ?>" disabled>
                                <small class="text-muted">Admission number cannot be changed</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Admission Date</label>
                                <input type="text" class="form-control" value="<?= $student['admission_date'] ? date('M j, Y', strtotime($student['admission_date'])) : 'N/A' ?>" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Created</label>
                                <input type="text" class="form-control" value="<?= date('M j, Y g:i A', strtotime($student['created_at'])) ?>" disabled>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-device-floppy icon"></i> Save Changes
                                </button>
                                <a href="/students/<?= $student['id'] ?>" class="btn btn-secondary">
                                    Cancel
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
