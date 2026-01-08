<?php
$student = $student ?? [];
$records = $records ?? [];
$summary = $summary ?? ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0];
$attendanceRate = $attendanceRate ?? 0;
?>

<div class="container py-4">
    <!-- Back Button & Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="/parent/dashboard" class="btn btn-sm btn-outline-secondary mb-2">
                <i class="ti ti-arrow-left me-1"></i> Back
            </a>
            <h3 class="mb-0"><?= e($student['first_name'] . ' ' . $student['last_name']) ?></h3>
            <small class="text-muted"><?= e($student['admission_number'] ?? '') ?> | <?= e($student['class_name'] ?? 'No Class') ?></small>
        </div>
    </div>

    <!-- Attendance Rate Card -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="avatar avatar-xl <?= $attendanceRate >= 80 ? 'bg-success' : ($attendanceRate >= 60 ? 'bg-warning' : 'bg-danger') ?> text-white" style="width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 700;">
                        <?= $attendanceRate ?>%
                    </div>
                </div>
                <div class="col">
                    <h4 class="mb-1">Attendance Rate</h4>
                    <p class="text-muted mb-0">Last 3 months</p>
                    <div class="progress mt-2" style="height: 8px;">
                        <div class="progress-bar <?= $attendanceRate >= 80 ? 'bg-success' : ($attendanceRate >= 60 ? 'bg-warning' : 'bg-danger') ?>" style="width: <?= $attendanceRate ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-3">
            <div class="card stat-card bg-success-lt">
                <div class="card-body text-center py-3">
                    <div class="h3 mb-0 text-success"><?= $summary['present'] ?></div>
                    <small class="text-muted">Present</small>
                </div>
            </div>
        </div>
        <div class="col-3">
            <div class="card stat-card bg-danger-lt">
                <div class="card-body text-center py-3">
                    <div class="h3 mb-0 text-danger"><?= $summary['absent'] ?></div>
                    <small class="text-muted">Absent</small>
                </div>
            </div>
        </div>
        <div class="col-3">
            <div class="card stat-card bg-warning-lt">
                <div class="card-body text-center py-3">
                    <div class="h3 mb-0 text-warning"><?= $summary['late'] ?></div>
                    <small class="text-muted">Late</small>
                </div>
            </div>
        </div>
        <div class="col-3">
            <div class="card stat-card bg-info-lt">
                <div class="card-body text-center py-3">
                    <div class="h3 mb-0 text-info"><?= $summary['excused'] ?></div>
                    <small class="text-muted">Excused</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Records -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="ti ti-calendar-stats me-2"></i> Attendance History</h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($records)): ?>
                <div class="text-center py-4">
                    <i class="ti ti-calendar-off text-muted" style="font-size: 2rem;"></i>
                    <p class="text-muted mt-2 mb-0">No attendance records found</p>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($records as $record): ?>
                        <?php
                        $statusIcons = [
                            'present' => ['icon' => 'ti-check', 'color' => 'success'],
                            'absent' => ['icon' => 'ti-x', 'color' => 'danger'],
                            'late' => ['icon' => 'ti-clock', 'color' => 'warning'],
                            'excused' => ['icon' => 'ti-notes', 'color' => 'info'],
                        ];
                        $statusInfo = $statusIcons[$record['status']] ?? ['icon' => 'ti-minus', 'color' => 'secondary'];
                        ?>
                        <div class="list-group-item">
                            <div class="d-flex align-items-center">
                                <div class="avatar bg-<?= $statusInfo['color'] ?>-lt text-<?= $statusInfo['color'] ?> me-3" style="width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                    <i class="ti <?= $statusInfo['icon'] ?>"></i>
                                </div>
                                <div class="flex-fill">
                                    <div class="d-flex justify-content-between">
                                        <strong><?= date('l, M d, Y', strtotime($record['attendance_date'] ?? $record['date'] ?? '')) ?></strong>
                                        <span class="badge bg-<?= $statusInfo['color'] ?>-lt text-<?= $statusInfo['color'] ?>"><?= ucfirst($record['status']) ?></span>
                                    </div>
                                    <?php if (!empty($record['remarks'])): ?>
                                        <small class="text-muted"><?= e($record['remarks']) ?></small>
                                    <?php endif; ?>
                                    <?php if (!empty($record['marked_by_name'])): ?>
                                        <small class="text-muted d-block">Marked by: <?= e($record['marked_by_name']) ?></small>
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
