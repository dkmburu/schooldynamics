<?php
$contentView = __DIR__ . '/_dashboard_content.php';
$pageTitle = "Dashboard";
require __DIR__ . '/../layouts/tenant.php';
?>
<?php return; ?>
<?php
// Create the content file
file_put_contents(__DIR__ . '/_dashboard_content.php', '<?php
?>
<div class="row row-deck row-cards">
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Total Students</div>
                    <div class="ms-auto lh-1">
                        <i class="ti ti-users icon text-primary" style="font-size: 2rem;"></i>
                    </div>
                </div>
                <div class="h1 mb-3"><?= $stats[\'total_students\'] ?? 0 ?></div>
                <div class="d-flex mb-2">
                    <div>Active students</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Staff Members</div>
                    <div class="ms-auto lh-1">
                        <i class="ti ti-briefcase icon text-success" style="font-size: 2rem;"></i>
                    </div>
                </div>
                <div class="h1 mb-3"><?= $stats[\'total_staff\'] ?? 0 ?></div>
                <div class="d-flex mb-2">
                    <div>Teaching & non-teaching</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Classes</div>
                    <div class="ms-auto lh-1">
                        <i class="ti ti-book icon text-info" style="font-size: 2rem;"></i>
                    </div>
                </div>
                <div class="h1 mb-3"><?= $stats[\'total_classes\'] ?? 0 ?></div>
                <div class="d-flex mb-2">
                    <div>Active classes</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Academic Year</div>
                    <div class="ms-auto lh-1">
                        <i class="ti ti-calendar icon text-warning" style="font-size: 2rem;"></i>
                    </div>
                </div>
                <div class="h1 mb-3"><?= e($stats[\'current_year\']) ?></div>
                <div class="d-flex mb-2">
                    <div>Current year</div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($stats[\'recent_students\'])): ?>
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Students</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr class="tbl_sh">
                                <th>Admission No.</th>
                                <th>Student Name</th>
                                <th>Admission Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats[\'recent_students\'] as $student): ?>
                            <tr class="tbl_data">
                                <td><strong><?= e($student[\'admission_number\']) ?></strong></td>
                                <td><?= e($student[\'first_name\'] . \' \' . $student[\'last_name\']) ?></td>
                                <td><?= formatDate($student[\'admission_date\']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <a href="/students" class="btn btn-primary">
                    <i class="ti ti-users icon"></i>
                    View All Students
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <?php if (hasPermission(\'Students.write\') || Gate::hasRole(\'ADMIN\')): ?>
                    <a href="/students/create" class="list-group-item list-group-item-action">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <i class="ti ti-user-plus icon text-primary"></i>
                            </div>
                            <div class="col">
                                <div>Add New Student</div>
                                <div class="text-muted small">Register a new student</div>
                            </div>
                        </div>
                    </a>
                    <?php endif; ?>

                    <?php if (hasPermission(\'Finance.view\') || Gate::hasRole(\'ADMIN\') || Gate::hasRole(\'BURSAR\')): ?>
                    <a href="/finance/invoices" class="list-group-item list-group-item-action">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <i class="ti ti-file-invoice icon text-success"></i>
                            </div>
                            <div class="col">
                                <div>Manage Invoices</div>
                                <div class="text-muted small">View and create fee invoices</div>
                            </div>
                        </div>
                    </a>
                    <?php endif; ?>

                    <a href="/reports" class="list-group-item list-group-item-action">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <i class="ti ti-chart-bar icon text-info"></i>
                            </div>
                            <div class="col">
                                <div>View Reports</div>
                                <div class="text-muted small">Generate school reports</div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Your Roles</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($roles)): ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($roles as $role): ?>
                    <div class="list-group-item">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="status-dot status-dot-animated bg-primary"></span>
                            </div>
                            <div class="col">
                                <strong><?= e($role[\'display_name\']) ?></strong>
                                <div class="text-muted small"><?= e($role[\'name\']) ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-muted">No roles assigned</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
');
?>
