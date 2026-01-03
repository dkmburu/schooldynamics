<?php
/**
 * Applicants List Content
 */
?>

<!-- Page Header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <h2 class="page-title">
                    <i class="ti ti-users me-2"></i>All Applicants
                </h2>
            </div>
            <div class="col-auto ms-auto">
                <ol class="breadcrumb breadcrumb-arrows">
                    <li class="breadcrumb-item"><a href="/dashboard">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Students</a></li>
                    <li class="breadcrumb-item active">All Applicants</li>
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
                <form method="GET" action="/applicants" class="d-flex gap-2">
                    <input type="text" name="search" class="form-control" placeholder="Search by name, ref, phone, email..."
                           value="<?= e($search) ?>" style="max-width: 400px;">

                    <select name="status" class="form-select" style="max-width: 200px;">
                        <option value="">All Statuses</option>
                        <option value="draft" <?= $statusFilter === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="submitted" <?= $statusFilter === 'submitted' ? 'selected' : '' ?>>Submitted</option>
                        <option value="screening" <?= $statusFilter === 'screening' ? 'selected' : '' ?>>Screening</option>
                        <option value="interview_scheduled" <?= $statusFilter === 'interview_scheduled' ? 'selected' : '' ?>>Interview Scheduled</option>
                        <option value="interviewed" <?= $statusFilter === 'interviewed' ? 'selected' : '' ?>>Interviewed</option>
                        <option value="exam_scheduled" <?= $statusFilter === 'exam_scheduled' ? 'selected' : '' ?>>Exam Scheduled</option>
                        <option value="exam_taken" <?= $statusFilter === 'exam_taken' ? 'selected' : '' ?>>Exam Taken</option>
                        <option value="accepted" <?= $statusFilter === 'accepted' ? 'selected' : '' ?>>Accepted</option>
                        <option value="waitlisted" <?= $statusFilter === 'waitlisted' ? 'selected' : '' ?>>Waitlisted</option>
                        <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        <option value="pre_admission" <?= $statusFilter === 'pre_admission' ? 'selected' : '' ?>>Pre-Admission</option>
                        <option value="admitted" <?= $statusFilter === 'admitted' ? 'selected' : '' ?>>Admitted</option>
                    </select>

                    <select name="grade" class="form-select" style="max-width: 150px;">
                        <option value="">All Grades</option>
                        <?php foreach ($grades as $grade): ?>
                            <option value="<?= $grade['id'] ?>" <?= $gradeFilter == $grade['id'] ? 'selected' : '' ?>>
                                <?= e($grade['grade_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-search icon"></i> Search
                    </button>

                    <?php if (!empty($search) || !empty($statusFilter) || !empty($gradeFilter)): ?>
                    <a href="/applicants" class="btn btn-secondary">
                        <i class="ti ti-x icon"></i> Clear
                    </a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="col-auto">
                <?php if (hasPermission('Students.write') || Gate::hasRole('ADMIN')): ?>
                <a href="/applicants/create" class="btn btn-success">
                    <i class="ti ti-plus icon"></i>
                    New Applicant
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Status Overview Cards -->
        <div class="row row-deck mb-3">
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm card-accent-left card-hover-lift">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-primary text-white avatar">
                                    <i class="ti ti-user-check"></i>
                                </span>
                            </div>
                            <div class="col">
                                <h3 class="mb-0"><?= $statusCounts['submitted'] ?? 0 ?></h3>
                                <p class="text-muted mb-0">Submitted</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm card-accent-success card-hover-lift">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-success text-white avatar">
                                    <i class="ti ti-circle-check"></i>
                                </span>
                            </div>
                            <div class="col">
                                <h3 class="mb-0"><?= $statusCounts['accepted'] ?? 0 ?></h3>
                                <p class="text-muted mb-0">Accepted</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm card-accent-warning card-hover-lift">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-info text-white avatar">
                                    <i class="ti ti-calendar"></i>
                                </span>
                            </div>
                            <div class="col">
                                <h3 class="mb-0"><?= $statusCounts['interview_scheduled'] ?? 0 ?></h3>
                                <p class="text-muted mb-0">Interview Scheduled</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm card-accent-info card-hover-lift">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-info text-white avatar">
                                    <i class="ti ti-file-text"></i>
                                </span>
                            </div>
                            <div class="col">
                                <h3 class="mb-0"><?= $totalRecords ?></h3>
                                <p class="text-muted mb-0">Total Applicants</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Applicants Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    Applicants List
                    <span class="text-muted ms-2">(<?= $totalRecords ?> total)</span>
                </h3>
            </div>
            <div class="card-body">
                <?php if (empty($applicants)): ?>
                <div class="empty">
                    <div class="empty-icon">
                        <i class="ti ti-users icon"></i>
                    </div>
                    <p class="empty-title">No applicants found</p>
                    <p class="empty-subtitle text-muted">
                        <?php if (!empty($search) || !empty($statusFilter) || !empty($gradeFilter)): ?>
                            Try adjusting your search filters
                        <?php else: ?>
                            Start by adding your first applicant
                        <?php endif; ?>
                    </p>
                    <?php if (empty($search) && empty($statusFilter) && empty($gradeFilter)): ?>
                    <div class="empty-action">
                        <a href="/applicants/create" class="btn btn-primary">
                            <i class="ti ti-plus icon"></i>
                            Add First Applicant
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-vcenter table-hover">
                        <thead>
                            <tr class="tbl_sh">
                                <th>Ref No.</th>
                                <th>Name</th>
                                <th>Grade</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applicants as $applicant): ?>
                            <tr class="tbl_data">
                                <td>
                                    <strong><?= e($applicant['application_ref']) ?></strong>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php $avatarName = urlencode($applicant['first_name'] . ' ' . $applicant['last_name']); ?>
                                        <span class="avatar avatar-sm me-2" style="background-image: url(https://ui-avatars.com/api/?name=<?= $avatarName ?>&size=64)"></span>
                                        <div>
                                            <div><?= e($applicant['first_name'] . ' ' . $applicant['last_name']) ?></div>
                                            <div class="text-muted small"><?= e($applicant['campaign_name'] ?? 'N/A') ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?= e($applicant['grade_name']) ?>
                                </td>
                                <td>
                                    <div class="small">
                                        <?php if ($applicant['phone']): ?>
                                        <div><?= e($applicant['phone']) ?></div>
                                        <?php endif; ?>
                                        <?php if ($applicant['email']): ?>
                                        <div><?= e($applicant['email']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $statusBadges = [
                                        'submitted' => 'badge-dot badge-primary',
                                        'screening' => 'badge-dot badge-info',
                                        'interview_scheduled' => 'badge-dot badge-warning',
                                        'exam_scheduled' => 'badge-dot badge-warning',
                                        'interviewed' => 'badge-dot badge-primary',
                                        'exam_taken' => 'badge-dot badge-primary',
                                        'accepted' => 'badge-dot badge-success',
                                        'admitted' => 'badge-dot badge-success',
                                        'waitlisted' => 'badge-dot badge-warning',
                                        'rejected' => 'badge-dot badge-danger',
                                    ];
                                    $statusBadgeClass = $statusBadges[$applicant['status']] ?? 'badge-dot badge-secondary';
                                    ?>
                                    <span class="<?= $statusBadgeClass ?>"><?= formatStatus($applicant['status']) ?></span>
                                </td>
                                <td>
                                    <div class="small">
                                        <?= formatDate($applicant['application_date'] ?? $applicant['created_at']) ?>
                                    </div>
                                </td>
                                <td>
                                    <a href="/applicants/<?= $applicant['id'] ?>" class="btn btn-sm btn-primary">
                                        View Details
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
                        <a class="page-link" href="?page=<?= $currentPage - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&grade=<?= urlencode($gradeFilter) ?>">
                            <i class="ti ti-chevron-left"></i> Prev
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                    <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&grade=<?= urlencode($gradeFilter) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($currentPage < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $currentPage + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&grade=<?= urlencode($gradeFilter) ?>">
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
