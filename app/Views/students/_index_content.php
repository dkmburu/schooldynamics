<?php
/**
 * Students List Content
 */
?>

<!-- Page Header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <h2 class="page-title">
                    <i class="ti ti-users me-2"></i>All Students
                </h2>
            </div>
            <div class="col-auto ms-auto">
                <ol class="breadcrumb breadcrumb-arrows">
                    <li class="breadcrumb-item"><a href="/dashboard">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Students</a></li>
                    <li class="breadcrumb-item active">All Students</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <!-- Search/Filter Bar -->
        <div class="row mb-3">
            <div class="col">
                <form method="GET" action="/students" class="d-flex gap-2 flex-wrap">
                    <input type="text" name="search" class="form-control" placeholder="Search by name or admission no..."
                           value="<?= e($search) ?>" style="max-width: 300px;">

                    <select name="status" class="form-select" style="max-width: 150px;">
                        <option value="">All Statuses</option>
                        <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="suspended" <?= $statusFilter === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                        <option value="transferred" <?= $statusFilter === 'transferred' ? 'selected' : '' ?>>Transferred</option>
                        <option value="graduated" <?= $statusFilter === 'graduated' ? 'selected' : '' ?>>Graduated</option>
                        <option value="withdrawn" <?= $statusFilter === 'withdrawn' ? 'selected' : '' ?>>Withdrawn</option>
                    </select>

                    <select name="grade" class="form-select" style="max-width: 150px;">
                        <option value="">All Grades</option>
                        <?php foreach ($grades as $grade): ?>
                            <option value="<?= $grade['id'] ?>" <?= $gradeFilter == $grade['id'] ? 'selected' : '' ?>>
                                <?= e($grade['grade_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="stream" class="form-select" style="max-width: 180px;">
                        <option value="">All Streams</option>
                        <?php foreach ($streams as $stream): ?>
                            <option value="<?= $stream['id'] ?>" <?= $streamFilter == $stream['id'] ? 'selected' : '' ?>>
                                <?= e($stream['display_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-search icon"></i> Search
                    </button>

                    <?php if (!empty($search) || !empty($statusFilter) || !empty($gradeFilter) || !empty($streamFilter)): ?>
                    <a href="/students" class="btn btn-secondary">
                        <i class="ti ti-x icon"></i> Clear
                    </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Status Overview Cards -->
        <div class="row row-deck mb-3">
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm card-hover-lift">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-success text-white avatar">
                                    <i class="ti ti-user-check"></i>
                                </span>
                            </div>
                            <div class="col">
                                <h3 class="mb-0"><?= $statusCounts['active'] ?? 0 ?></h3>
                                <p class="text-muted mb-0">Active Students</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm card-hover-lift">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-warning text-white avatar">
                                    <i class="ti ti-user-pause"></i>
                                </span>
                            </div>
                            <div class="col">
                                <h3 class="mb-0"><?= $statusCounts['suspended'] ?? 0 ?></h3>
                                <p class="text-muted mb-0">Suspended</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm card-hover-lift">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-info text-white avatar">
                                    <i class="ti ti-school"></i>
                                </span>
                            </div>
                            <div class="col">
                                <h3 class="mb-0"><?= $statusCounts['graduated'] ?? 0 ?></h3>
                                <p class="text-muted mb-0">Graduated</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm card-hover-lift">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-primary text-white avatar">
                                    <i class="ti ti-users"></i>
                                </span>
                            </div>
                            <div class="col">
                                <h3 class="mb-0"><?= $totalRecords ?></h3>
                                <p class="text-muted mb-0">Total Students</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Students Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    Students List
                    <span class="text-muted ms-2">(<?= $totalRecords ?> total)</span>
                </h3>
            </div>
            <div class="card-body">
                <?php if (empty($students)): ?>
                <div class="empty">
                    <div class="empty-icon">
                        <i class="ti ti-users icon"></i>
                    </div>
                    <p class="empty-title">No students found</p>
                    <p class="empty-subtitle text-muted">
                        <?php if (!empty($search) || !empty($statusFilter) || !empty($gradeFilter) || !empty($streamFilter)): ?>
                            Try adjusting your search filters
                        <?php else: ?>
                            Students appear here after completing the admission process from Applicants
                        <?php endif; ?>
                    </p>
                    <?php if (empty($search) && empty($statusFilter) && empty($gradeFilter) && empty($streamFilter)): ?>
                    <div class="empty-action">
                        <a href="/applicants" class="btn btn-primary">
                            <i class="ti ti-arrow-right icon"></i>
                            Go to Applicants
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-vcenter table-hover">
                        <thead>
                            <tr class="tbl_sh">
                                <th>Admission No.</th>
                                <th>Name</th>
                                <th>Class/Stream</th>
                                <th>Status</th>
                                <th>Admission Date</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                            <tr class="tbl_data">
                                <td>
                                    <strong><?= e($student['admission_number']) ?></strong>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php
                                        $fullName = $student['first_name'] . ' ' . ($student['middle_name'] ? $student['middle_name'] . ' ' : '') . $student['last_name'];
                                        $avatarName = urlencode($student['first_name'] . ' ' . $student['last_name']);
                                        ?>
                                        <span class="avatar avatar-sm me-2" style="background-image: url(https://ui-avatars.com/api/?name=<?= $avatarName ?>&size=64&background=0078d4&color=fff)"></span>
                                        <div>
                                            <div><?= e($fullName) ?></div>
                                            <div class="text-muted small">
                                                <?= e($student['gender'] ? ucfirst($student['gender']) : 'N/A') ?>
                                                <?php if ($student['date_of_birth']): ?>
                                                 | <?= date('M j, Y', strtotime($student['date_of_birth'])) ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($student['grade_name'] && $student['stream_name']): ?>
                                        <span class="badge bg-blue-lt"><?= e($student['grade_name']) ?></span>
                                        <span class="text-muted"> - <?= e($student['stream_name']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Not Assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $statusBadges = [
                                        'active' => 'bg-success',
                                        'suspended' => 'bg-warning',
                                        'transferred' => 'bg-info',
                                        'graduated' => 'bg-primary',
                                        'withdrawn' => 'bg-secondary',
                                    ];
                                    $badgeClass = $statusBadges[$student['status']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= ucfirst($student['status']) ?></span>
                                </td>
                                <td>
                                    <div class="small">
                                        <?= $student['admission_date'] ? date('M j, Y', strtotime($student['admission_date'])) : 'N/A' ?>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <a href="/students/<?= $student['id'] ?>" class="btn btn-sm btn-primary">
                                        View
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($totalPages > 1): ?>
            <div class="card-footer d-flex align-items-center">
                <p class="m-0 text-muted">
                    Showing <span><?= (($currentPage - 1) * $perPage) + 1 ?></span>
                    to <span><?= min($currentPage * $perPage, $totalRecords) ?></span>
                    of <span><?= $totalRecords ?></span> entries
                </p>
                <ul class="pagination m-0 ms-auto">
                    <?php if ($currentPage > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $currentPage - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&grade=<?= urlencode($gradeFilter) ?>&stream=<?= urlencode($streamFilter) ?>">
                            <i class="ti ti-chevron-left"></i> Prev
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                    <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&grade=<?= urlencode($gradeFilter) ?>&stream=<?= urlencode($streamFilter) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($currentPage < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $currentPage + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&grade=<?= urlencode($gradeFilter) ?>&stream=<?= urlencode($streamFilter) ?>">
                            Next <i class="ti ti-chevron-right"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
