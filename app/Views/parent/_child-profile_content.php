<?php
$student = $student ?? [];
$classTeacher = $classTeacher ?? null;
?>

<div class="container py-4">
    <!-- Back Button -->
    <a href="/parent/dashboard" class="btn btn-sm btn-outline-secondary mb-3">
        <i class="ti ti-arrow-left me-1"></i> Back to Dashboard
    </a>

    <!-- Student Header Card -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="avatar avatar-xl bg-primary text-white" style="width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 600;">
                        <?= strtoupper(substr($student['first_name'] ?? '', 0, 1) . substr($student['last_name'] ?? '', 0, 1)) ?>
                    </div>
                </div>
                <div class="col">
                    <h3 class="mb-1"><?= e($student['first_name'] . ' ' . ($student['middle_name'] ? $student['middle_name'] . ' ' : '') . $student['last_name']) ?></h3>
                    <p class="text-muted mb-0">
                        <i class="ti ti-id me-1"></i> <?= e($student['admission_number'] ?? 'N/A') ?>
                        <?php if (!empty($student['class_name'])): ?>
                            <span class="mx-2">|</span>
                            <i class="ti ti-school me-1"></i> <?= e($student['class_name']) ?><?= !empty($student['stream']) ? ' - ' . e($student['stream']) : '' ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Personal Information -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ti ti-user me-2"></i> Personal Information</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5 text-muted">Date of Birth</dt>
                        <dd class="col-7"><?= !empty($student['date_of_birth']) ? date('M d, Y', strtotime($student['date_of_birth'])) : 'N/A' ?></dd>

                        <dt class="col-5 text-muted">Gender</dt>
                        <dd class="col-7"><?= e(ucfirst($student['gender'] ?? 'N/A')) ?></dd>

                        <dt class="col-5 text-muted">Blood Group</dt>
                        <dd class="col-7"><?= e($student['blood_group'] ?? 'N/A') ?></dd>

                        <dt class="col-5 text-muted">Nationality</dt>
                        <dd class="col-7"><?= e($student['nationality'] ?? 'Kenyan') ?></dd>

                        <dt class="col-5 text-muted">Religion</dt>
                        <dd class="col-7"><?= e($student['religion'] ?? 'N/A') ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Academic Information -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ti ti-school me-2"></i> Academic Information</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5 text-muted">Admission No.</dt>
                        <dd class="col-7"><?= e($student['admission_number'] ?? 'N/A') ?></dd>

                        <dt class="col-5 text-muted">Admission Date</dt>
                        <dd class="col-7"><?= !empty($student['admission_date']) ? date('M d, Y', strtotime($student['admission_date'])) : 'N/A' ?></dd>

                        <dt class="col-5 text-muted">Current Class</dt>
                        <dd class="col-7"><?= e($student['class_name'] ?? 'Not Assigned') ?></dd>

                        <dt class="col-5 text-muted">Stream</dt>
                        <dd class="col-7"><?= e($student['stream'] ?? 'N/A') ?></dd>

                        <dt class="col-5 text-muted">Campus</dt>
                        <dd class="col-7"><?= e($student['campus_name'] ?? 'Main Campus') ?></dd>

                        <dt class="col-5 text-muted">Status</dt>
                        <dd class="col-7">
                            <?php
                            $statusColors = ['active' => 'success', 'suspended' => 'warning', 'withdrawn' => 'danger', 'graduated' => 'info'];
                            $statusColor = $statusColors[$student['status'] ?? ''] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?= $statusColor ?>-lt"><?= ucfirst($student['status'] ?? 'Active') ?></span>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Class Teacher -->
        <?php if ($classTeacher): ?>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="ti ti-chalkboard me-2"></i> Class Teacher</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-lg bg-primary-lt text-primary me-3" style="width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <i class="ti ti-user" style="font-size: 1.5rem;"></i>
                            </div>
                            <div>
                                <h5 class="mb-1"><?= e($classTeacher['first_name'] . ' ' . $classTeacher['last_name']) ?></h5>
                                <?php if (!empty($classTeacher['phone'])): ?>
                                    <p class="text-muted mb-0">
                                        <i class="ti ti-phone me-1"></i>
                                        <a href="tel:<?= e($classTeacher['phone']) ?>"><?= e($classTeacher['phone']) ?></a>
                                    </p>
                                <?php endif; ?>
                                <?php if (!empty($classTeacher['email'])): ?>
                                    <p class="text-muted mb-0">
                                        <i class="ti ti-mail me-1"></i>
                                        <a href="mailto:<?= e($classTeacher['email']) ?>"><?= e($classTeacher['email']) ?></a>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ti ti-click me-2"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="/parent/child/<?= $student['id'] ?>/fees" class="btn btn-outline-primary">
                            <i class="ti ti-receipt me-2"></i> View Fee Statement
                        </a>
                        <a href="/parent/child/<?= $student['id'] ?>/attendance" class="btn btn-outline-success">
                            <i class="ti ti-calendar-stats me-2"></i> View Attendance
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
