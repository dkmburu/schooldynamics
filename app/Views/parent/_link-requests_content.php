<?php
$requests = $requests ?? [];
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                    <a href="/parent/dashboard" class="btn btn-ghost-secondary btn-sm mb-3">
                        <i class="ti ti-arrow-left me-1"></i> Back to Dashboard
                    </a>
                    <h3 class="mb-1">Student Linkage Requests</h3>
                    <p class="text-muted mb-0">View the status of your student linkage requests.</p>
                </div>
                <a href="/parent/link-student" class="btn btn-primary">
                    <i class="ti ti-user-plus me-1"></i> Link New Student
                </a>
            </div>

            <?php if (empty($requests)): ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="ti ti-file-search text-muted" style="font-size: 3rem;"></i>
                        <h5 class="mt-3">No Requests Found</h5>
                        <p class="text-muted mb-4">You haven't submitted any student linkage requests yet.</p>
                        <a href="/parent/link-student" class="btn btn-primary">
                            <i class="ti ti-user-plus me-1"></i> Link a Student
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Admission Number</th>
                                    <th>Grade</th>
                                    <th>Student Name</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                    <th>Reviewed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $request): ?>
                                    <tr>
                                        <td>
                                            <strong><?= e($request['admission_number']) ?></strong>
                                        </td>
                                        <td><?= e($request['grade_name']) ?></td>
                                        <td>
                                            <?php if ($request['student_id']): ?>
                                                <?= e($request['student_first_name'] . ' ' . $request['student_last_name']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $statusClass = 'bg-secondary';
                                            $statusIcon = 'ti-clock';
                                            switch ($request['status']) {
                                                case 'pending':
                                                    $statusClass = 'bg-yellow text-yellow-fg';
                                                    $statusIcon = 'ti-clock';
                                                    break;
                                                case 'approved':
                                                    $statusClass = 'bg-green text-green-fg';
                                                    $statusIcon = 'ti-check';
                                                    break;
                                                case 'rejected':
                                                    $statusClass = 'bg-red text-red-fg';
                                                    $statusIcon = 'ti-x';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?= $statusClass ?>">
                                                <i class="ti <?= $statusIcon ?> me-1"></i>
                                                <?= ucfirst($request['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-muted">
                                                <?= date('M d, Y', strtotime($request['created_at'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($request['reviewed_at']): ?>
                                                <div>
                                                    <?= date('M d, Y', strtotime($request['reviewed_at'])) ?>
                                                </div>
                                                <?php if ($request['reviewed_by_name']): ?>
                                                    <small class="text-muted">by <?= e($request['reviewed_by_name']) ?></small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php if ($request['status'] === 'rejected' && $request['rejection_reason']): ?>
                                        <tr class="bg-red-lt">
                                            <td colspan="6">
                                                <div class="d-flex align-items-start">
                                                    <i class="ti ti-alert-circle text-danger me-2 mt-1"></i>
                                                    <div>
                                                        <strong class="text-danger">Rejection Reason:</strong>
                                                        <p class="mb-0"><?= e($request['rejection_reason']) ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Legend -->
                <div class="mt-4">
                    <h6 class="text-muted mb-2">Status Legend:</h6>
                    <div class="d-flex flex-wrap gap-3">
                        <span><span class="badge bg-yellow text-yellow-fg me-1"><i class="ti ti-clock"></i></span> Pending - Awaiting school review</span>
                        <span><span class="badge bg-green text-green-fg me-1"><i class="ti ti-check"></i></span> Approved - Student linked</span>
                        <span><span class="badge bg-red text-red-fg me-1"><i class="ti ti-x"></i></span> Rejected - See reason below</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
