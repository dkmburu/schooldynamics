<?php
/**
 * Student Profile Content - Comprehensive View with Tabs
 */
$fullName = $student['first_name'] . ' ' . ($student['middle_name'] ? $student['middle_name'] . ' ' : '') . $student['last_name'];
?>

<!-- Page Header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <a href="/students" class="btn btn-outline-secondary btn-sm me-2">
                    <i class="ti ti-arrow-left"></i>
                </a>
            </div>
            <div class="col">
                <div class="d-flex align-items-center">
                    <?php $avatarName = urlencode($student['first_name'] . ' ' . $student['last_name']); ?>
                    <span class="avatar avatar-lg me-3" style="background-image: url(https://ui-avatars.com/api/?name=<?= $avatarName ?>&size=128&background=0078d4&color=fff)"></span>
                    <div>
                        <h2 class="page-title mb-1"><?= e($fullName) ?></h2>
                        <div class="text-muted">
                            <span class="badge bg-primary me-2"><?= e($student['admission_number']) ?></span>
                            <?php if ($student['grade_name']): ?>
                            <span class="badge bg-blue-lt me-2"><?= e($student['grade_name']) ?> - <?= e($student['stream_name'] ?? 'N/A') ?></span>
                            <?php endif; ?>
                            <?php
                            $statusColors = [
                                'active' => 'bg-success',
                                'suspended' => 'bg-warning',
                                'transferred' => 'bg-info',
                                'graduated' => 'bg-primary',
                                'withdrawn' => 'bg-secondary',
                            ];
                            $statusColor = $statusColors[$student['status']] ?? 'bg-secondary';
                            ?>
                            <span class="badge <?= $statusColor ?>"><?= ucfirst($student['status']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-auto ms-auto">
                <?php if (hasPermission('Students.write') || Gate::hasRole('ADMIN')): ?>
                <a href="/students/<?= $student['id'] ?>/edit" class="btn btn-primary">
                    <i class="ti ti-edit icon"></i> Edit Student
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <!-- Tabs Navigation - Icon Only with Tooltips -->
        <div class="card">
            <div class="card-header student-tabs-header">
                <ul class="nav nav-tabs card-header-tabs nav-tabs-icon-only" data-bs-toggle="tabs" role="tablist" id="student-tabs">
                    <li class="nav-item" role="presentation">
                        <a href="#tab-overview" class="nav-link active icon-tab" data-bs-toggle="tab" role="tab" data-tooltip="Overview">
                            <i class="ti ti-user" style="font-size: 24px;"></i>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a href="#tab-guardians" class="nav-link icon-tab" data-bs-toggle="tab" role="tab" data-tooltip="Guardians (<?= count($guardians) ?>)">
                            <i class="ti ti-users" style="font-size: 24px;"></i>
                            <?php if (count($guardians) > 0): ?><span class="tab-badge"><?= count($guardians) ?></span><?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a href="#tab-class" class="nav-link icon-tab" data-bs-toggle="tab" role="tab" data-tooltip="Class Info">
                            <i class="ti ti-school" style="font-size: 24px;"></i>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a href="#tab-attendance" class="nav-link icon-tab" data-bs-toggle="tab" role="tab" data-tooltip="Attendance">
                            <i class="ti ti-calendar-check" style="font-size: 24px;"></i>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a href="#tab-assessments" class="nav-link icon-tab" data-bs-toggle="tab" role="tab" data-tooltip="Assessments">
                            <i class="ti ti-report-analytics" style="font-size: 24px;"></i>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a href="#tab-finances" class="nav-link icon-tab" data-bs-toggle="tab" role="tab" data-tooltip="Finances">
                            <i class="ti ti-currency-dollar" style="font-size: 24px;"></i>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a href="#tab-medical" class="nav-link icon-tab" data-bs-toggle="tab" role="tab" data-tooltip="Medical">
                            <i class="ti ti-heartbeat" style="font-size: 24px;"></i>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a href="#tab-education" class="nav-link icon-tab" data-bs-toggle="tab" role="tab" data-tooltip="Education History">
                            <i class="ti ti-certificate" style="font-size: 24px;"></i>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a href="#tab-documents" class="nav-link icon-tab" data-bs-toggle="tab" role="tab" data-tooltip="Documents (<?= count($documents) ?>)">
                            <i class="ti ti-files" style="font-size: 24px;"></i>
                            <?php if (count($documents) > 0): ?><span class="tab-badge"><?= count($documents) ?></span><?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a href="#tab-transport" class="nav-link icon-tab" data-bs-toggle="tab" role="tab" data-tooltip="Transport">
                            <i class="ti ti-bus" style="font-size: 24px;"></i>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a href="#tab-activity" class="nav-link icon-tab" data-bs-toggle="tab" role="tab" data-tooltip="Activity Log">
                            <i class="ti ti-history" style="font-size: 24px;"></i>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="card-body">
                <div class="tab-content">
                    <!-- Overview Tab -->
                    <div class="tab-pane active show" id="tab-overview" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="mb-0">Student Overview</h3>
                            <?php if (hasPermission('Students.write') || Gate::hasRole('ADMIN')): ?>
                            <a href="/students/<?= $student['id'] ?>/edit" class="btn btn-primary">
                                <i class="ti ti-edit icon"></i> Edit Student
                            </a>
                            <?php endif; ?>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title"><i class="ti ti-user me-2"></i>Personal Information</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="datagrid">
                                            <div class="datagrid-item">
                                                <div class="datagrid-title">Full Name</div>
                                                <div class="datagrid-content"><?= e($fullName) ?></div>
                                            </div>
                                            <div class="datagrid-item">
                                                <div class="datagrid-title">Admission Number</div>
                                                <div class="datagrid-content"><strong><?= e($student['admission_number']) ?></strong></div>
                                            </div>
                                            <div class="datagrid-item">
                                                <div class="datagrid-title">Date of Birth</div>
                                                <div class="datagrid-content">
                                                    <?= $student['date_of_birth'] ? date('F j, Y', strtotime($student['date_of_birth'])) : 'Not specified' ?>
                                                </div>
                                            </div>
                                            <div class="datagrid-item">
                                                <div class="datagrid-title">Gender</div>
                                                <div class="datagrid-content"><?= $student['gender'] ? ucfirst($student['gender']) : 'Not specified' ?></div>
                                            </div>
                                            <div class="datagrid-item">
                                                <div class="datagrid-title">Campus</div>
                                                <div class="datagrid-content"><?= e($student['campus_name'] ?? 'Main Campus') ?></div>
                                            </div>
                                            <div class="datagrid-item">
                                                <div class="datagrid-title">Status</div>
                                                <div class="datagrid-content">
                                                    <span class="badge <?= $statusColor ?>"><?= ucfirst($student['status']) ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title"><i class="ti ti-school me-2"></i>Academic Information</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="datagrid">
                                            <div class="datagrid-item">
                                                <div class="datagrid-title">Current Grade</div>
                                                <div class="datagrid-content"><?= e($student['grade_name'] ?? 'Not Assigned') ?></div>
                                            </div>
                                            <div class="datagrid-item">
                                                <div class="datagrid-title">Stream</div>
                                                <div class="datagrid-content"><?= e($student['stream_name'] ?? 'N/A') ?></div>
                                            </div>
                                            <div class="datagrid-item">
                                                <div class="datagrid-title">Academic Year</div>
                                                <div class="datagrid-content"><?= e($student['academic_year'] ?? 'N/A') ?></div>
                                            </div>
                                            <div class="datagrid-item">
                                                <div class="datagrid-title">Admission Date</div>
                                                <div class="datagrid-content">
                                                    <?= $student['admission_date'] ? date('F j, Y', strtotime($student['admission_date'])) : 'Not specified' ?>
                                                </div>
                                            </div>
                                            <div class="datagrid-item">
                                                <div class="datagrid-title">Enrollment Date</div>
                                                <div class="datagrid-content">
                                                    <?= $student['enrollment_date'] ? date('F j, Y', strtotime($student['enrollment_date'])) : 'N/A' ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Quick Stats -->
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h3 class="card-title"><i class="ti ti-chart-bar me-2"></i>Quick Stats</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="row text-center">
                                            <div class="col-4">
                                                <div class="h1 mb-0"><?= count($guardians) ?></div>
                                                <div class="text-muted small">Guardians</div>
                                            </div>
                                            <div class="col-4">
                                                <div class="h1 mb-0"><?= count($documents) ?></div>
                                                <div class="text-muted small">Documents</div>
                                            </div>
                                            <div class="col-4">
                                                <div class="h1 mb-0"><?= count($enrollmentHistory) ?></div>
                                                <div class="text-muted small">Enrollments</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Guardians Tab -->
                    <div class="tab-pane" id="tab-guardians" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="mb-0">Guardians / Family</h3>
                            <?php if (hasPermission('Students.write') || Gate::hasRole('ADMIN')): ?>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGuardianModal">
                                <i class="ti ti-plus icon"></i> Add Guardian
                            </button>
                            <?php endif; ?>
                        </div>

                        <?php if (empty($guardians)): ?>
                        <div class="empty">
                            <div class="empty-icon"><i class="ti ti-users icon"></i></div>
                            <p class="empty-title">No guardians added</p>
                            <p class="empty-subtitle text-muted">Add guardians to maintain emergency contact information.</p>
                        </div>
                        <?php else: ?>
                        <div class="row row-cards">
                            <?php foreach ($guardians as $guardian): ?>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <?php $gAvatarName = urlencode($guardian['first_name'] . ' ' . $guardian['last_name']); ?>
                                            <span class="avatar avatar-lg me-3" style="background-image: url(https://ui-avatars.com/api/?name=<?= $gAvatarName ?>&size=64&background=6c757d&color=fff)"></span>
                                            <div class="flex-grow-1">
                                                <h3 class="mb-0">
                                                    <?= e($guardian['first_name'] . ' ' . $guardian['last_name']) ?>
                                                    <?php if ($guardian['is_primary']): ?>
                                                    <span class="badge bg-green-lt ms-2">Primary</span>
                                                    <?php endif; ?>
                                                </h3>
                                                <div class="text-muted"><?= e($guardian['relationship'] ?? 'Guardian') ?></div>
                                            </div>
                                            <?php if (hasPermission('Students.write') || Gate::hasRole('ADMIN')): ?>
                                            <div class="dropdown">
                                                <button class="btn btn-icon btn-ghost-secondary" data-bs-toggle="dropdown">
                                                    <i class="ti ti-dots-vertical"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <button class="dropdown-item" onclick="editGuardian(<?= htmlspecialchars(json_encode($guardian), ENT_QUOTES, 'UTF-8') ?>)">
                                                        <i class="ti ti-edit me-2"></i>Edit
                                                    </button>
                                                    <button class="dropdown-item" onclick="togglePrimaryGuardian(<?= $guardian['id'] ?>, <?= $guardian['is_primary'] ? 0 : 1 ?>)">
                                                        <i class="ti ti-star me-2"></i><?= $guardian['is_primary'] ? 'Remove as Primary' : 'Set as Primary' ?>
                                                    </button>
                                                    <div class="dropdown-divider"></div>
                                                    <button class="dropdown-item text-danger" onclick="removeGuardian(<?= $guardian['id'] ?>)">
                                                        <i class="ti ti-trash me-2"></i>Remove
                                                    </button>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mt-3">
                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="mb-2">
                                                        <i class="ti ti-phone me-1 text-muted"></i>
                                                        <a href="tel:<?= e($guardian['phone']) ?>"><?= e($guardian['phone'] ?? 'N/A') ?></a>
                                                    </div>
                                                    <div class="mb-2">
                                                        <i class="ti ti-mail me-1 text-muted"></i>
                                                        <?= e($guardian['email'] ?? 'N/A') ?>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="mb-2">
                                                        <i class="ti ti-id me-1 text-muted"></i>
                                                        <?= e($guardian['id_number'] ?? 'N/A') ?>
                                                    </div>
                                                    <div class="mb-2">
                                                        <i class="ti ti-briefcase me-1 text-muted"></i>
                                                        <?= e($guardian['occupation'] ?? 'N/A') ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php if ($guardian['can_pickup']): ?>
                                            <span class="badge bg-blue-lt"><i class="ti ti-check me-1"></i>Can Pick Up</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Class Info Tab -->
                    <div class="tab-pane" id="tab-class" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="mb-0">Class Information</h3>
                            <?php if (hasPermission('Students.write') || Gate::hasRole('ADMIN')): ?>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#changeClassModal">
                                <i class="ti ti-switch-horizontal icon"></i> Change Class
                            </button>
                            <?php endif; ?>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Current Enrollment</h3>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($student['grade_name']): ?>
                                        <div class="datagrid">
                                            <div class="datagrid-item">
                                                <div class="datagrid-title">Grade</div>
                                                <div class="datagrid-content"><strong><?= e($student['grade_name']) ?></strong></div>
                                            </div>
                                            <div class="datagrid-item">
                                                <div class="datagrid-title">Stream</div>
                                                <div class="datagrid-content"><?= e($student['stream_name'] ?? 'N/A') ?></div>
                                            </div>
                                            <div class="datagrid-item">
                                                <div class="datagrid-title">Academic Year</div>
                                                <div class="datagrid-content"><?= e($student['academic_year']) ?></div>
                                            </div>
                                            <div class="datagrid-item">
                                                <div class="datagrid-title">Enrolled On</div>
                                                <div class="datagrid-content"><?= $student['enrollment_date'] ? date('M j, Y', strtotime($student['enrollment_date'])) : 'N/A' ?></div>
                                            </div>
                                        </div>
                                        <?php else: ?>
                                        <div class="empty py-4">
                                            <div class="empty-icon"><i class="ti ti-school icon"></i></div>
                                            <p class="empty-title">Not Enrolled</p>
                                            <p class="empty-subtitle text-muted">Student is not currently enrolled in any class.</p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Enrollment History</h3>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($enrollmentHistory)): ?>
                                        <div class="empty py-4">
                                            <p class="empty-title">No enrollment history</p>
                                        </div>
                                        <?php else: ?>
                                        <div class="list-group list-group-flush">
                                            <?php foreach ($enrollmentHistory as $enrollment): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex justify-content-between">
                                                    <div>
                                                        <strong><?= e($enrollment['grade_name']) ?></strong> - <?= e($enrollment['stream_name']) ?>
                                                        <div class="text-muted small"><?= e($enrollment['year_name']) ?></div>
                                                    </div>
                                                    <div class="text-end">
                                                        <div class="small"><?= date('M j, Y', strtotime($enrollment['enrollment_date'])) ?></div>
                                                        <?php if ($enrollment['is_current']): ?>
                                                        <span class="badge bg-green-lt">Current</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance Tab -->
                    <div class="tab-pane" id="tab-attendance" role="tabpanel">
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <h3 class="mb-0">Attendance Records</h3>
                            </div>
                            <div class="col-md-4">
                                <form method="GET" action="" class="d-flex gap-2">
                                    <select name="attendance_month" class="form-select form-select-sm">
                                        <?php
                                        $currentMonth = date('Y-m');
                                        for ($i = 0; $i < 12; $i++) {
                                            $month = date('Y-m', strtotime("-$i months"));
                                            $monthName = date('F Y', strtotime("-$i months"));
                                            $selected = ($attendanceMonth ?? $currentMonth) === $month ? 'selected' : '';
                                            echo "<option value=\"$month\" $selected>$monthName</option>";
                                        }
                                        ?>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                                </form>
                            </div>
                        </div>

                        <!-- Attendance Summary Cards -->
                        <div class="row row-deck mb-3">
                            <div class="col-sm-6 col-lg-3">
                                <div class="card card-sm">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-auto">
                                                <span class="bg-success text-white avatar">
                                                    <i class="ti ti-check"></i>
                                                </span>
                                            </div>
                                            <div class="col">
                                                <h3 class="mb-0"><?= $attendanceSummary['present'] ?? 0 ?></h3>
                                                <p class="text-muted mb-0">Present</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <div class="card card-sm">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-auto">
                                                <span class="bg-danger text-white avatar">
                                                    <i class="ti ti-x"></i>
                                                </span>
                                            </div>
                                            <div class="col">
                                                <h3 class="mb-0"><?= $attendanceSummary['absent'] ?? 0 ?></h3>
                                                <p class="text-muted mb-0">Absent</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <div class="card card-sm">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-auto">
                                                <span class="bg-warning text-white avatar">
                                                    <i class="ti ti-clock"></i>
                                                </span>
                                            </div>
                                            <div class="col">
                                                <h3 class="mb-0"><?= $attendanceSummary['late'] ?? 0 ?></h3>
                                                <p class="text-muted mb-0">Late</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <div class="card card-sm">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-auto">
                                                <span class="bg-info text-white avatar">
                                                    <i class="ti ti-file-certificate"></i>
                                                </span>
                                            </div>
                                            <div class="col">
                                                <h3 class="mb-0"><?= $attendanceSummary['excused'] ?? 0 ?></h3>
                                                <p class="text-muted mb-0">Excused</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Attendance Rate -->
                        <?php
                        $totalDays = ($attendanceSummary['present'] ?? 0) + ($attendanceSummary['absent'] ?? 0) + ($attendanceSummary['late'] ?? 0) + ($attendanceSummary['excused'] ?? 0);
                        $attendanceRate = $totalDays > 0 ? round((($attendanceSummary['present'] ?? 0) + ($attendanceSummary['late'] ?? 0)) / $totalDays * 100, 1) : 0;
                        ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="me-2">Attendance Rate</span>
                                    <span class="ms-auto fw-bold <?= $attendanceRate >= 90 ? 'text-success' : ($attendanceRate >= 75 ? 'text-warning' : 'text-danger') ?>">
                                        <?= $attendanceRate ?>%
                                    </span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-<?= $attendanceRate >= 90 ? 'success' : ($attendanceRate >= 75 ? 'warning' : 'danger') ?>"
                                         style="width: <?= $attendanceRate ?>%"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Attendance Records -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Recent Attendance</h3>
                            </div>
                            <div class="card-body">
                                <?php if (empty($attendanceRecords)): ?>
                                <div class="empty py-4">
                                    <div class="empty-icon"><i class="ti ti-calendar-off icon"></i></div>
                                    <p class="empty-title">No attendance records</p>
                                    <p class="empty-subtitle text-muted">Attendance records will appear here once recorded.</p>
                                </div>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-vcenter">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Status</th>
                                                <th>Check-in</th>
                                                <th>Check-out</th>
                                                <th>Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($attendanceRecords as $record): ?>
                                            <tr>
                                                <td><?= date('D, M j, Y', strtotime($record['attendance_date'])) ?></td>
                                                <td>
                                                    <?php
                                                    $statusClasses = [
                                                        'present' => 'bg-success',
                                                        'absent' => 'bg-danger',
                                                        'late' => 'bg-warning',
                                                        'excused' => 'bg-info',
                                                        'half_day' => 'bg-purple'
                                                    ];
                                                    $statusClass = $statusClasses[$record['status']] ?? 'bg-secondary';
                                                    ?>
                                                    <span class="badge <?= $statusClass ?>"><?= ucfirst(str_replace('_', ' ', $record['status'])) ?></span>
                                                </td>
                                                <td><?= $record['check_in_time'] ? date('g:i A', strtotime($record['check_in_time'])) : '-' ?></td>
                                                <td><?= $record['check_out_time'] ? date('g:i A', strtotime($record['check_out_time'])) : '-' ?></td>
                                                <td class="text-muted"><?= e($record['remarks'] ?? '-') ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Assessments Tab -->
                    <div class="tab-pane" id="tab-assessments" role="tabpanel">
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <h3 class="mb-0">Academic Performance</h3>
                            </div>
                            <div class="col-md-4">
                                <form method="GET" action="" class="d-flex gap-2">
                                    <select name="assessment_term" class="form-select form-select-sm">
                                        <option value="">All Terms</option>
                                        <?php foreach ($terms ?? [] as $term): ?>
                                        <option value="<?= $term['id'] ?>" <?= ($selectedTerm ?? '') == $term['id'] ? 'selected' : '' ?>>
                                            <?= e($term['term_name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                                </form>
                            </div>
                        </div>

                        <!-- Performance Summary -->
                        <div class="row row-deck mb-3">
                            <div class="col-sm-6 col-lg-3">
                                <div class="card card-sm">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-auto">
                                                <span class="bg-primary text-white avatar">
                                                    <i class="ti ti-chart-line"></i>
                                                </span>
                                            </div>
                                            <div class="col">
                                                <h3 class="mb-0"><?= $performanceSummary['average_score'] ?? 'N/A' ?></h3>
                                                <p class="text-muted mb-0">Average Score</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <div class="card card-sm">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-auto">
                                                <span class="bg-success text-white avatar">
                                                    <i class="ti ti-trophy"></i>
                                                </span>
                                            </div>
                                            <div class="col">
                                                <h3 class="mb-0"><?= $performanceSummary['highest_score'] ?? 'N/A' ?></h3>
                                                <p class="text-muted mb-0">Highest Score</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <div class="card card-sm">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-auto">
                                                <span class="bg-warning text-white avatar">
                                                    <i class="ti ti-trending-down"></i>
                                                </span>
                                            </div>
                                            <div class="col">
                                                <h3 class="mb-0"><?= $performanceSummary['lowest_score'] ?? 'N/A' ?></h3>
                                                <p class="text-muted mb-0">Lowest Score</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <div class="card card-sm">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-auto">
                                                <span class="bg-info text-white avatar">
                                                    <i class="ti ti-list-numbers"></i>
                                                </span>
                                            </div>
                                            <div class="col">
                                                <h3 class="mb-0"><?= $performanceSummary['class_rank'] ?? 'N/A' ?></h3>
                                                <p class="text-muted mb-0">Class Rank</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Subject-wise Performance -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h3 class="card-title">Subject Performance</h3>
                            </div>
                            <div class="card-body">
                                <?php if (empty($subjectPerformance)): ?>
                                <div class="empty py-4">
                                    <div class="empty-icon"><i class="ti ti-book-off icon"></i></div>
                                    <p class="empty-title">No assessment records</p>
                                    <p class="empty-subtitle text-muted">Subject performance will appear here once assessments are recorded.</p>
                                </div>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-vcenter">
                                        <thead>
                                            <tr>
                                                <th>Subject</th>
                                                <th>CAT 1</th>
                                                <th>CAT 2</th>
                                                <th>End Term</th>
                                                <th>Average</th>
                                                <th>Grade</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($subjectPerformance as $subject): ?>
                                            <tr>
                                                <td><strong><?= e($subject['subject_name']) ?></strong></td>
                                                <td><?= $subject['cat1'] ?? '-' ?></td>
                                                <td><?= $subject['cat2'] ?? '-' ?></td>
                                                <td><?= $subject['end_term'] ?? '-' ?></td>
                                                <td>
                                                    <?php if ($subject['average']): ?>
                                                    <strong class="<?= $subject['average'] >= 70 ? 'text-success' : ($subject['average'] >= 50 ? 'text-warning' : 'text-danger') ?>">
                                                        <?= number_format($subject['average'], 1) ?>
                                                    </strong>
                                                    <?php else: ?>
                                                    -
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($subject['grade']): ?>
                                                    <span class="badge bg-<?= in_array($subject['grade'], ['A', 'A-']) ? 'success' : (in_array($subject['grade'], ['B+', 'B', 'B-']) ? 'primary' : (in_array($subject['grade'], ['C+', 'C', 'C-']) ? 'warning' : 'danger')) ?>">
                                                        <?= e($subject['grade']) ?>
                                                    </span>
                                                    <?php else: ?>
                                                    -
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Recent Assessments -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Recent Assessments</h3>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recentAssessments)): ?>
                                <div class="empty py-4">
                                    <p class="empty-title">No recent assessments</p>
                                </div>
                                <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($recentAssessments as $assessment): ?>
                                    <div class="list-group-item">
                                        <div class="row align-items-center">
                                            <div class="col-auto">
                                                <span class="avatar bg-<?= $assessment['score_percent'] >= 70 ? 'success' : ($assessment['score_percent'] >= 50 ? 'warning' : 'danger') ?>-lt">
                                                    <?= round($assessment['score_percent']) ?>%
                                                </span>
                                            </div>
                                            <div class="col">
                                                <strong><?= e($assessment['assessment_name']) ?></strong>
                                                <div class="text-muted small">
                                                    <?= e($assessment['subject_name']) ?> | <?= e($assessment['assessment_type']) ?>
                                                </div>
                                            </div>
                                            <div class="col-auto text-end">
                                                <div><?= $assessment['score'] ?>/<?= $assessment['max_score'] ?></div>
                                                <div class="text-muted small"><?= date('M j, Y', strtotime($assessment['assessment_date'])) ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Medical Tab -->
                    <div class="tab-pane" id="tab-medical" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="mb-0">Medical Information</h3>
                            <?php if (hasPermission('Students.write') || Gate::hasRole('ADMIN')): ?>
                            <button type="submit" form="medicalForm" class="btn btn-primary">
                                <i class="ti ti-device-floppy icon"></i> Save Medical Info
                            </button>
                            <?php endif; ?>
                        </div>
                        <form id="medicalForm" onsubmit="saveMedicalInfo(event)">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h3 class="card-title"><i class="ti ti-heartbeat me-2"></i>Health Details</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label class="form-label">Blood Group</label>
                                                <select name="blood_group" class="form-select">
                                                    <option value="">Select...</option>
                                                    <?php
                                                    $bloodGroups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                                                    foreach ($bloodGroups as $bg):
                                                    ?>
                                                    <option value="<?= $bg ?>" <?= ($medicalInfo['blood_group'] ?? '') === $bg ? 'selected' : '' ?>><?= $bg ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Allergies</label>
                                                <textarea name="allergies" class="form-control" rows="2" placeholder="List any known allergies..."><?= e($medicalInfo['allergies'] ?? '') ?></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Medical Conditions</label>
                                                <textarea name="medical_conditions" class="form-control" rows="2" placeholder="Any chronic conditions..."><?= e($medicalInfo['medical_conditions'] ?? '') ?></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Current Medications</label>
                                                <textarea name="medications" class="form-control" rows="2" placeholder="Any regular medications..."><?= e($medicalInfo['medications'] ?? '') ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h3 class="card-title"><i class="ti ti-phone-call me-2"></i>Emergency Contacts</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label class="form-label">Emergency Contact Name</label>
                                                <input type="text" name="emergency_contact" class="form-control" value="<?= e($medicalInfo['emergency_contact'] ?? '') ?>" placeholder="Name...">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Emergency Contact Phone</label>
                                                <input type="text" name="emergency_phone" class="form-control" value="<?= e($medicalInfo['emergency_phone'] ?? '') ?>" placeholder="Phone number...">
                                            </div>
                                            <hr>
                                            <div class="mb-3">
                                                <label class="form-label">Doctor/Physician Name</label>
                                                <input type="text" name="doctor_name" class="form-control" value="<?= e($medicalInfo['doctor_name'] ?? '') ?>" placeholder="Doctor's name...">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Doctor's Phone</label>
                                                <input type="text" name="doctor_phone" class="form-control" value="<?= e($medicalInfo['doctor_phone'] ?? '') ?>" placeholder="Doctor's phone...">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Additional Notes</label>
                                                <textarea name="notes" class="form-control" rows="2" placeholder="Any other medical notes..."><?= e($medicalInfo['notes'] ?? '') ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Education History Tab -->
                    <div class="tab-pane" id="tab-education" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="mb-0">Previous Schools Attended</h3>
                            <?php if (hasPermission('Students.write') || Gate::hasRole('ADMIN')): ?>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEducationModal">
                                <i class="ti ti-plus icon"></i> Add School
                            </button>
                            <?php endif; ?>
                        </div>

                        <?php if (empty($educationHistory)): ?>
                        <div class="empty">
                            <div class="empty-icon"><i class="ti ti-school icon"></i></div>
                            <p class="empty-title">No education history recorded</p>
                            <p class="empty-subtitle text-muted">Add previous schools the student has attended.</p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-vcenter">
                                <thead>
                                    <tr>
                                        <th>School Name</th>
                                        <th>Period</th>
                                        <th>Grade Completed</th>
                                        <th>Reason for Leaving</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($educationHistory as $edu): ?>
                                    <tr>
                                        <td>
                                            <strong><?= e($edu['school_name']) ?></strong>
                                            <?php if ($edu['school_address']): ?>
                                            <div class="text-muted small"><?= e($edu['school_address']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $edu['year_from'] ?? 'N/A' ?> - <?= $edu['year_to'] ?? 'N/A' ?></td>
                                        <td><?= e($edu['grade_completed'] ?? 'N/A') ?></td>
                                        <td><?= e($edu['reason_for_leaving'] ?? 'N/A') ?></td>
                                        <td>
                                            <?php if (hasPermission('Students.write') || Gate::hasRole('ADMIN')): ?>
                                            <button class="btn btn-ghost-danger btn-icon" onclick="deleteEducationHistory(<?= $edu['id'] ?>)">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Documents Tab -->
                    <div class="tab-pane" id="tab-documents" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="mb-0">Student Documents</h3>
                            <?php if (hasPermission('Students.write') || Gate::hasRole('ADMIN')): ?>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadDocumentModal">
                                <i class="ti ti-upload icon"></i> Upload Document
                            </button>
                            <?php endif; ?>
                        </div>

                        <?php if (empty($documents)): ?>
                        <div class="empty">
                            <div class="empty-icon"><i class="ti ti-files icon"></i></div>
                            <p class="empty-title">No documents uploaded</p>
                            <p class="empty-subtitle text-muted">Upload student documents like birth certificate, ID copies, etc.</p>
                        </div>
                        <?php else: ?>
                        <div class="row row-cards">
                            <?php foreach ($documents as $doc): ?>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <span class="avatar bg-blue-lt me-3">
                                                <i class="ti ti-file-text"></i>
                                            </span>
                                            <div class="flex-grow-1">
                                                <h4 class="mb-1"><?= e($doc['title']) ?></h4>
                                                <div class="text-muted small">
                                                    <?= e($doc['type_name'] ?? 'Document') ?>
                                                    <br>
                                                    <?= round($doc['file_size'] / 1024, 1) ?> KB
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-3 d-flex gap-2">
                                            <a href="<?= e($doc['file_path']) ?>" target="_blank" class="btn btn-sm btn-primary">
                                                <i class="ti ti-eye icon"></i> View
                                            </a>
                                            <?php if (hasPermission('Students.write') || Gate::hasRole('ADMIN')): ?>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteDocument(<?= $doc['id'] ?>)">
                                                <i class="ti ti-trash icon"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Finances Tab -->
                    <div class="tab-pane" id="tab-finances" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="mb-0">Financial Information</h3>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Fee Account Summary</h3>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($feeAccount): ?>
                                        <div class="datagrid">
                                            <div class="datagrid-item">
                                                <div class="datagrid-title">Account Number</div>
                                                <div class="datagrid-content"><strong><?= e($feeAccount['account_number']) ?></strong></div>
                                            </div>
                                            <div class="datagrid-item">
                                                <div class="datagrid-title">Account Status</div>
                                                <div class="datagrid-content">
                                                    <span class="badge bg-<?= $feeAccount['account_status'] === 'active' ? 'success' : 'secondary' ?>">
                                                        <?= ucfirst($feeAccount['account_status']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="datagrid-item">
                                                <div class="datagrid-title">Total Invoiced</div>
                                                <div class="datagrid-content">KES <?= number_format($feeAccount['total_invoiced'] ?? 0, 2) ?></div>
                                            </div>
                                            <div class="datagrid-item">
                                                <div class="datagrid-title">Total Paid</div>
                                                <div class="datagrid-content text-success">KES <?= number_format($feeAccount['total_paid'] ?? 0, 2) ?></div>
                                            </div>
                                            <div class="datagrid-item">
                                                <div class="datagrid-title">Current Balance</div>
                                                <div class="datagrid-content text-danger fw-bold">KES <?= number_format($feeAccount['current_balance'] ?? 0, 2) ?></div>
                                            </div>
                                        </div>
                                        <?php else: ?>
                                        <div class="empty py-4">
                                            <p class="empty-title">No fee account</p>
                                            <p class="empty-subtitle text-muted">Fee account will be created upon admission.</p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
                                            <li class="nav-item">
                                                <a href="#fin-invoices" class="nav-link active" data-bs-toggle="tab">Invoices</a>
                                            </li>
                                            <li class="nav-item">
                                                <a href="#fin-payments" class="nav-link" data-bs-toggle="tab">Payments</a>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="card-body">
                                        <div class="tab-content">
                                            <div class="tab-pane active show" id="fin-invoices">
                                                <?php if (empty($recentInvoices)): ?>
                                                <div class="empty py-4">
                                                    <p class="empty-title">No invoices</p>
                                                </div>
                                                <?php else: ?>
                                                <div class="table-responsive">
                                                    <table class="table table-vcenter">
                                                        <thead>
                                                            <tr>
                                                                <th>Invoice #</th>
                                                                <th>Term</th>
                                                                <th>Amount</th>
                                                                <th>Balance</th>
                                                                <th>Status</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($recentInvoices as $inv): ?>
                                                            <tr>
                                                                <td><?= e($inv['invoice_number']) ?></td>
                                                                <td><?= e($inv['term_name'] ?? 'N/A') ?></td>
                                                                <td>KES <?= number_format($inv['total_amount'], 2) ?></td>
                                                                <td>KES <?= number_format($inv['balance'], 2) ?></td>
                                                                <td>
                                                                    <?php
                                                                    $invStatusColors = [
                                                                        'paid' => 'bg-success',
                                                                        'partial' => 'bg-warning',
                                                                        'unpaid' => 'bg-danger',
                                                                        'cancelled' => 'bg-secondary'
                                                                    ];
                                                                    ?>
                                                                    <span class="badge <?= $invStatusColors[$inv['status']] ?? 'bg-secondary' ?>"><?= ucfirst($inv['status']) ?></span>
                                                                </td>
                                                            </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="tab-pane" id="fin-payments">
                                                <?php if (empty($recentPayments)): ?>
                                                <div class="empty py-4">
                                                    <p class="empty-title">No payments recorded</p>
                                                </div>
                                                <?php else: ?>
                                                <div class="table-responsive">
                                                    <table class="table table-vcenter">
                                                        <thead>
                                                            <tr>
                                                                <th>Receipt #</th>
                                                                <th>Date</th>
                                                                <th>Method</th>
                                                                <th>Amount</th>
                                                                <th>Status</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($recentPayments as $pay): ?>
                                                            <tr>
                                                                <td><?= e($pay['receipt_number']) ?></td>
                                                                <td><?= date('M j, Y', strtotime($pay['payment_date'])) ?></td>
                                                                <td><?= e($pay['payment_method_name'] ?? 'N/A') ?></td>
                                                                <td>KES <?= number_format($pay['amount'], 2) ?></td>
                                                                <td>
                                                                    <span class="badge bg-<?= $pay['status'] === 'completed' ? 'success' : 'secondary' ?>"><?= ucfirst($pay['status']) ?></span>
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
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Transport Tab -->
                    <div class="tab-pane" id="tab-transport" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="mb-0">Transport Information</h3>
                            <?php if (hasPermission('Students.write') || Gate::hasRole('ADMIN')): ?>
                            <div class="btn-list">
                                <?php if ($transportAssignment): ?>
                                <button type="button" class="btn btn-outline-danger" onclick="removeTransport()">
                                    <i class="ti ti-x icon"></i> Remove
                                </button>
                                <?php endif; ?>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignTransportModal">
                                    <i class="ti ti-plus icon"></i> <?= $transportAssignment ? 'Change' : 'Assign' ?> Zone
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title"><i class="ti ti-bus me-2"></i>Transport Assignment</h3>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($transportAssignment): ?>
                                        <div class="datagrid">
                                            <div class="datagrid-item">
                                                <div class="datagrid-title">Zone</div>
                                                <div class="datagrid-content"><strong><?= e($transportAssignment['zone_name'] ?? 'N/A') ?></strong></div>
                                            </div>
                                            <div class="datagrid-item">
                                                <div class="datagrid-title">Zone Code</div>
                                                <div class="datagrid-content"><?= e($transportAssignment['zone_code'] ?? 'N/A') ?></div>
                                            </div>
                                            <div class="datagrid-item">
                                                <div class="datagrid-title">Pickup Time</div>
                                                <div class="datagrid-content"><?= $transportAssignment['pickup_time'] ? date('g:i A', strtotime($transportAssignment['pickup_time'])) : 'N/A' ?></div>
                                            </div>
                                            <div class="datagrid-item">
                                                <div class="datagrid-title">Drop-off Time</div>
                                                <div class="datagrid-content"><?= $transportAssignment['dropoff_time'] ? date('g:i A', strtotime($transportAssignment['dropoff_time'])) : 'N/A' ?></div>
                                            </div>
                                        </div>
                                        <?php else: ?>
                                        <div class="empty py-4">
                                            <div class="empty-icon"><i class="ti ti-bus icon"></i></div>
                                            <p class="empty-title">No transport assigned</p>
                                            <p class="empty-subtitle text-muted">This student is not assigned to any transport route.</p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Activity Tab -->
                    <div class="tab-pane" id="tab-activity" role="tabpanel">
                        <h3 class="mb-3">Activity Log</h3>
                        <?php if (empty($activityLog)): ?>
                        <div class="empty">
                            <div class="empty-icon"><i class="ti ti-history icon"></i></div>
                            <p class="empty-title">No activity recorded</p>
                        </div>
                        <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($activityLog as $activity): ?>
                            <div class="list-group-item">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <span class="avatar avatar-sm bg-blue-lt">
                                            <i class="ti ti-activity"></i>
                                        </span>
                                    </div>
                                    <div class="col">
                                        <div class="d-flex justify-content-between">
                                            <strong><?= e($activity['description']) ?></strong>
                                            <span class="text-muted small"><?= date('M j, Y g:i A', strtotime($activity['created_at'])) ?></span>
                                        </div>
                                        <div class="text-muted small">
                                            By: <?= e($activity['user_name'] ?? 'System') ?>
                                            | Action: <?= e($activity['action']) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Guardian Modal -->
<div class="modal modal-blur fade" id="addGuardianModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="addGuardianForm" onsubmit="addGuardian(event)">
                <div class="modal-header">
                    <h5 class="modal-title">Add Guardian</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required">First Name</label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required">Last Name</label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required">Phone Number</label>
                                <input type="text" name="phone" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">ID Number</label>
                                <input type="text" name="id_number" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Relationship</label>
                                <select name="relationship" class="form-select">
                                    <option value="">Select...</option>
                                    <option value="Father">Father</option>
                                    <option value="Mother">Mother</option>
                                    <option value="Guardian">Guardian</option>
                                    <option value="Uncle">Uncle</option>
                                    <option value="Aunt">Aunt</option>
                                    <option value="Grandparent">Grandparent</option>
                                    <option value="Sibling">Sibling</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Occupation</label>
                                <input type="text" name="occupation" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3 pt-4">
                                <label class="form-check">
                                    <input type="checkbox" name="is_primary" value="1" class="form-check-input">
                                    <span class="form-check-label">Primary Guardian</span>
                                </label>
                                <label class="form-check">
                                    <input type="checkbox" name="can_pickup" value="1" class="form-check-input" checked>
                                    <span class="form-check-label">Can Pick Up Student</span>
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Guardian</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Guardian Modal -->
<div class="modal modal-blur fade" id="editGuardianModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editGuardianForm" onsubmit="saveGuardian(event)">
                <input type="hidden" name="guardian_id" id="edit_guardian_id">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Guardian</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required">First Name</label>
                                <input type="text" name="first_name" id="edit_first_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required">Last Name</label>
                                <input type="text" name="last_name" id="edit_last_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required">Phone Number</label>
                                <input type="text" name="phone" id="edit_phone" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" id="edit_email" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">ID Number</label>
                                <input type="text" name="id_number" id="edit_id_number" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Relationship</label>
                                <select name="relationship" id="edit_relationship" class="form-select">
                                    <option value="">Select...</option>
                                    <option value="Father">Father</option>
                                    <option value="Mother">Mother</option>
                                    <option value="Guardian">Guardian</option>
                                    <option value="Uncle">Uncle</option>
                                    <option value="Aunt">Aunt</option>
                                    <option value="Grandparent">Grandparent</option>
                                    <option value="Sibling">Sibling</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Occupation</label>
                                <input type="text" name="occupation" id="edit_occupation" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3 pt-4">
                                <label class="form-check">
                                    <input type="checkbox" name="is_primary" id="edit_is_primary" value="1" class="form-check-input">
                                    <span class="form-check-label">Primary Guardian</span>
                                </label>
                                <label class="form-check">
                                    <input type="checkbox" name="can_pickup" id="edit_can_pickup" value="1" class="form-check-input">
                                    <span class="form-check-label">Can Pick Up Student</span>
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="address" id="edit_address" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Class Modal -->
<div class="modal modal-blur fade" id="changeClassModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="changeClassForm" onsubmit="changeClass(event)">
                <div class="modal-header">
                    <h5 class="modal-title">Change Class/Stream</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">New Class/Stream</label>
                        <select name="stream_id" class="form-select" required>
                            <option value="">Select Class...</option>
                            <?php foreach ($availableStreams as $stream): ?>
                            <option value="<?= $stream['id'] ?>" <?= $stream['id'] == $student['stream_id'] ? 'selected' : '' ?>>
                                <?= e($stream['display_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason for Change</label>
                        <textarea name="reason" class="form-control" rows="2" placeholder="Promotion, transfer, etc."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Change Class</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Education History Modal -->
<div class="modal modal-blur fade" id="addEducationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addEducationForm" onsubmit="addEducationHistory(event)">
                <div class="modal-header">
                    <h5 class="modal-title">Add Previous School</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">School Name</label>
                        <input type="text" name="school_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">School Address</label>
                        <input type="text" name="school_address" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Year From</label>
                                <input type="number" name="year_from" class="form-control" min="1990" max="<?= date('Y') ?>">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Year To</label>
                                <input type="number" name="year_to" class="form-control" min="1990" max="<?= date('Y') ?>">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Grade/Class Completed</label>
                        <input type="text" name="grade_completed" class="form-control" placeholder="e.g., Grade 6, Form 2">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason for Leaving</label>
                        <input type="text" name="reason_for_leaving" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add School</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Upload Document Modal -->
<div class="modal modal-blur fade" id="uploadDocumentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="uploadDocumentForm" onsubmit="uploadDocument(event)" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Document</label>
                        <input type="file" name="document" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.gif" required>
                        <small class="text-muted">Allowed: PDF, JPG, PNG, GIF. Max 5MB.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Document Type</label>
                        <select name="document_type_id" class="form-select">
                            <option value="">Select Type...</option>
                            <?php foreach ($documentTypes as $type): ?>
                            <option value="<?= $type['id'] ?>"><?= e($type['type_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Title/Description</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g., Birth Certificate">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Transport Modal -->
<div class="modal modal-blur fade" id="assignTransportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="assignTransportForm" onsubmit="assignTransport(event)">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Transport Zone</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Transport Zone</label>
                        <select name="route_id" class="form-select" required>
                            <option value="">Select Zone...</option>
                            <?php foreach ($transportRoutes as $zone): ?>
                            <option value="<?= $zone['id'] ?>"><?= e($zone['zone_name']) ?> (<?= e($zone['zone_code'] ?? '') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Pickup Time</label>
                                <input type="time" name="pickup_time" class="form-control">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Drop-off Time</label>
                                <input type="time" name="dropoff_time" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign Zone</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Student tabs header with darker background */
.student-tabs-header {
    background-color: #E9EFF5 !important;
    padding: 1rem !important;
    border-bottom: none !important;
    margin-bottom: 0 !important;
    box-shadow: none !important;
}

/* Override nav-tabs border that creates the gap */
.student-tabs-header .nav-tabs {
    border-bottom: none !important;
    margin-bottom: 0 !important;
}

/* Remove gap between header and body */
.student-tabs-header + .card-body {
    padding-top: 1.5rem !important;
    border-top: none !important;
    margin-top: 0 !important;
}

/* Ensure the parent card has no internal gaps */
.card:has(.student-tabs-header) {
    overflow: visible;
}
.card:has(.student-tabs-header) > .card-header {
    border-bottom: none !important;
}
.card:has(.student-tabs-header) > .card-body {
    border-top: none !important;
}

/* Icon-only tabs with CSS tooltips */
.nav-tabs-icon-only .nav-item {
    margin-right: 0.5rem !important;
}
.nav-tabs-icon-only .icon-tab {
    position: relative;
    padding: 1rem 1.25rem !important;
    line-height: 1;
    display: flex !important;
    align-items: center;
    justify-content: center;
    border-radius: 8px !important;
    transition: all 0.2s ease;
    border: none !important;
    background: #fff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}
/* Force larger icons - Tabler Icons use font-size for sizing */
.nav-tabs-icon-only .icon-tab i,
.nav-tabs-icon-only .icon-tab i.ti,
.nav-tabs-icon-only .icon-tab .ti {
    font-size: 1.5rem !important;
    line-height: 1 !important;
    display: inline-block !important;
}
/* Apply size class directly */
.student-tabs-header .icon-tab i {
    font-size: 24px !important;
}
.nav-tabs-icon-only .icon-tab:hover {
    background: rgba(0, 120, 212, 0.15) !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
.nav-tabs-icon-only .icon-tab .tab-badge {
    position: absolute;
    top: 0;
    right: 0;
    font-size: 0.65rem;
    padding: 0.2rem 0.4rem;
    border-radius: 10px;
    background: var(--tblr-primary);
    color: #fff;
    line-height: 1;
    min-width: 1.1rem;
    font-weight: 600;
}

/* CSS Tooltip - positioned below icon */
.nav-tabs-icon-only .icon-tab[data-tooltip]::after {
    content: attr(data-tooltip);
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    margin-top: 8px;
    background: #1e293b;
    color: #fff;
    padding: 0.4rem 0.75rem;
    border-radius: 4px;
    font-size: 0.8rem;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.15s, visibility 0.15s;
    z-index: 1050;
    pointer-events: none;
    font-weight: 500;
}
.nav-tabs-icon-only .icon-tab[data-tooltip]::before {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    margin-top: 3px;
    border: 5px solid transparent;
    border-bottom-color: #1e293b;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.15s, visibility 0.15s;
    z-index: 1050;
}
.nav-tabs-icon-only .icon-tab[data-tooltip]:hover::after,
.nav-tabs-icon-only .icon-tab[data-tooltip]:hover::before {
    opacity: 1;
    visibility: visible;
}

/* Active tab styling */
.nav-tabs-icon-only .icon-tab.active {
    background: var(--tblr-primary) !important;
    color: #fff !important;
    border-color: var(--tblr-primary) !important;
    box-shadow: 0 4px 12px rgba(0, 120, 212, 0.35);
}
.nav-tabs-icon-only .icon-tab.active:hover {
    transform: none;
}
.nav-tabs-icon-only .icon-tab.active .tab-badge {
    background: #fff;
    color: var(--tblr-primary);
}

/* Override Tabler nav-tabs border */
.nav-tabs-icon-only.nav-tabs {
    border-bottom: none !important;
}
</style>

<script>
const studentId = <?= $student['id'] ?>;

// Save Medical Information
function saveMedicalInfo(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);

    fetch(`/students/${studentId}/medical`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Medical information saved successfully!');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => alert('Error saving medical info: ' + err.message));
}

// Add Guardian
function addGuardian(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);

    fetch(`/students/${studentId}/guardians`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => alert('Error adding guardian: ' + err.message));
}

// Remove Guardian
function removeGuardian(guardianId) {
    if (!confirm('Are you sure you want to remove this guardian?')) return;

    fetch(`/students/${studentId}/guardians/${guardianId}/delete`, {
        method: 'POST'
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => alert('Error removing guardian: ' + err.message));
}

// Edit Guardian - populate modal and show
function editGuardian(guardian) {
    document.getElementById('edit_guardian_id').value = guardian.id;
    document.getElementById('edit_first_name').value = guardian.first_name || '';
    document.getElementById('edit_last_name').value = guardian.last_name || '';
    document.getElementById('edit_phone').value = guardian.phone || '';
    document.getElementById('edit_email').value = guardian.email || '';
    document.getElementById('edit_id_number').value = guardian.id_number || '';
    document.getElementById('edit_relationship').value = guardian.relationship || '';
    document.getElementById('edit_occupation').value = guardian.occupation || '';
    document.getElementById('edit_is_primary').checked = guardian.is_primary == 1;
    document.getElementById('edit_can_pickup').checked = guardian.can_pickup == 1;
    document.getElementById('edit_address').value = guardian.address || '';

    const modal = new bootstrap.Modal(document.getElementById('editGuardianModal'));
    modal.show();
}

// Save Guardian changes
function saveGuardian(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const guardianId = formData.get('guardian_id');

    fetch(`/students/${studentId}/guardians/${guardianId}/update`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => alert('Error updating guardian: ' + err.message));
}

// Toggle Primary Guardian status
function togglePrimaryGuardian(guardianId, isPrimary) {
    const formData = new FormData();
    formData.append('is_primary', isPrimary);

    fetch(`/students/${studentId}/guardians/${guardianId}/toggle-primary`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => alert('Error removing guardian: ' + err.message));
}

// Change Class
function changeClass(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);

    fetch(`/students/${studentId}/change-class`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => alert('Error changing class: ' + err.message));
}

// Add Education History
function addEducationHistory(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);

    fetch(`/students/${studentId}/education-history`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => alert('Error adding education history: ' + err.message));
}

// Delete Education History
function deleteEducationHistory(historyId) {
    if (!confirm('Are you sure you want to delete this education record?')) return;

    fetch(`/students/${studentId}/education-history/${historyId}/delete`, {
        method: 'POST'
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => alert('Error deleting record: ' + err.message));
}

// Upload Document
function uploadDocument(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);

    fetch(`/students/${studentId}/documents`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => alert('Error uploading document: ' + err.message));
}

// Delete Document
function deleteDocument(documentId) {
    if (!confirm('Are you sure you want to delete this document?')) return;

    fetch(`/students/${studentId}/documents/${documentId}/delete`, {
        method: 'POST'
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => alert('Error deleting document: ' + err.message));
}

// Assign Transport
function assignTransport(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);

    fetch(`/students/${studentId}/transport`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => alert('Error assigning transport: ' + err.message));
}

// Remove Transport
function removeTransport() {
    if (!confirm('Are you sure you want to remove transport assignment?')) return;

    fetch(`/students/${studentId}/transport/remove`, {
        method: 'POST'
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => alert('Error removing transport: ' + err.message));
}
</script>
