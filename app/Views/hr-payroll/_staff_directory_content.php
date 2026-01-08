<?php
/**
 * Staff Directory Content
 */

$staff = $staff ?? [];
$departments = $departments ?? [];
$designations = $designations ?? [];
$filters = $filters ?? [];
$currency = $_SESSION['currency'] ?? 'KES';
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <div class="page-pretitle">HR & Payroll</div>
                <h2 class="page-title">
                    <i class="ti ti-users me-2"></i>Staff Directory
                </h2>
            </div>
            <div class="col-auto ms-auto">
                <a href="/hr-payroll/staff/create" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i>Add New Staff
                </a>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <!-- Filters -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Department</label>
                        <select name="department" class="form-select">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                            <option value="<?= e($dept['department_name']) ?>" <?= ($filters['department'] ?? '') == $dept['department_name'] ? 'selected' : '' ?>>
                                <?= e($dept['department_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Designation</label>
                        <select name="designation" class="form-select">
                            <option value="">All Designations</option>
                            <?php foreach ($designations as $des): ?>
                            <option value="<?= e($des['designation_name']) ?>" <?= ($filters['designation'] ?? '') == $des['designation_name'] ? 'selected' : '' ?>>
                                <?= e($des['designation_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?= ($filters['status'] ?? 'active') == 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($filters['status'] ?? '') == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            <option value="terminated" <?= ($filters['status'] ?? '') == 'terminated' ? 'selected' : '' ?>>Terminated</option>
                            <option value="" <?= ($filters['status'] ?? 'active') == '' ? 'selected' : '' ?>>All</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select">
                            <option value="">All Types</option>
                            <option value="permanent" <?= ($filters['type'] ?? '') == 'permanent' ? 'selected' : '' ?>>Permanent</option>
                            <option value="contract" <?= ($filters['type'] ?? '') == 'contract' ? 'selected' : '' ?>>Contract</option>
                            <option value="part_time" <?= ($filters['type'] ?? '') == 'part_time' ? 'selected' : '' ?>>Part Time</option>
                            <option value="casual" <?= ($filters['type'] ?? '') == 'casual' ? 'selected' : '' ?>>Casual</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Name, ID, Email..."
                               value="<?= e($filters['search'] ?? '') ?>">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ti ti-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Staff Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Staff List</h3>
                <div class="card-actions">
                    <span class="badge bg-blue"><?= count($staff) ?> staff found</span>
                </div>
            </div>
            <?php if (empty($staff)): ?>
            <div class="card-body">
                <div class="empty">
                    <div class="empty-icon">
                        <i class="ti ti-users" style="font-size: 3rem;"></i>
                    </div>
                    <p class="empty-title">No staff found</p>
                    <p class="empty-subtitle text-muted">
                        Try adjusting your filters or add new staff
                    </p>
                    <div class="empty-action">
                        <a href="/hr-payroll/staff/create" class="btn btn-primary">
                            <i class="ti ti-plus me-1"></i>Add New Staff
                        </a>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-striped">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Designation</th>
                            <th>Type</th>
                            <th class="text-end">Basic Salary</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staff as $s): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm me-2 bg-primary-lt">
                                        <?= strtoupper(substr($s['first_name'] ?? '', 0, 1) . substr($s['last_name'] ?? '', 0, 1)) ?>
                                    </span>
                                    <div>
                                        <strong><?= e($s['first_name'] . ' ' . $s['last_name']) ?></strong>
                                        <div class="text-muted small"><?= e($s['staff_number'] ?? 'N/A') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?= e($s['department_name'] ?? 'N/A') ?></td>
                            <td><?= e($s['designation_name'] ?? 'N/A') ?></td>
                            <td>
                                <span class="badge bg-secondary">
                                    <?= ucfirst(str_replace('_', ' ', $s['employment_type'] ?? 'permanent')) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <?php if (!empty($s['current_salary'])): ?>
                                <?= $currency ?> <?= number_format($s['current_salary']) ?>
                                <?php else: ?>
                                <span class="text-muted">Not set</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $statusColors = [
                                    'active' => 'success',
                                    'inactive' => 'secondary',
                                    'terminated' => 'danger',
                                    'on_leave' => 'warning'
                                ];
                                $color = $statusColors[$s['status'] ?? 'active'] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $color ?>"><?= ucfirst($s['status'] ?? 'active') ?></span>
                            </td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Actions
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a class="dropdown-item" href="/hr-payroll/staff/<?= $s['id'] ?>">
                                            <i class="ti ti-eye me-2"></i>View Profile
                                        </a>
                                        <a class="dropdown-item" href="/hr-payroll/staff/<?= $s['id'] ?>/edit">
                                            <i class="ti ti-edit me-2"></i>Edit Details
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="/hr-payroll/staff/<?= $s['id'] ?>/payslips">
                                            <i class="ti ti-file-invoice me-2"></i>View Payslips
                                        </a>
                                        <a class="dropdown-item" href="/hr-payroll/leave?staff=<?= $s['id'] ?>">
                                            <i class="ti ti-calendar-time me-2"></i>Leave History
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item text-warning" href="#" data-bs-toggle="modal" data-bs-target="#deactivateModal" data-staff-id="<?= $s['id'] ?>">
                                            <i class="ti ti-user-off me-2"></i>Deactivate
                                        </a>
                                    </div>
                                </div>
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

<style>
/* Fix dropdown menu appearing behind cards */
.table-responsive {
    overflow: visible !important;
}
.card {
    overflow: visible;
}
.dropdown-menu {
    z-index: 1050;
}
</style>
