<?php
/**
 * Fee Structures - Content
 */

$statusColors = [
    'draft' => 'secondary',
    'pending_approval' => 'warning',
    'approved' => 'info',
    'published' => 'success',
    'locked' => 'dark'
];
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <a href="/finance" class="btn btn-ghost-secondary btn-sm mb-2">
                    <i class="ti ti-arrow-left me-1"></i>Back to Finance
                </a>
                <h2 class="page-title">
                    <i class="ti ti-file-spreadsheet me-2"></i>
                    Fee Structures
                </h2>
                <div class="text-muted mt-1">
                    Define fee templates per grade, per term
                </div>
            </div>
            <div class="col-auto ms-auto">
                <?php if (hasPermission('Finance.write') || Gate::hasRole('ADMIN')): ?>
                <a href="/finance/fee-structures/create" class="btn btn-primary">
                    <i class="ti ti-plus me-2"></i>Create Fee Structure
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <!-- Filters -->
        <div class="card mb-3">
            <div class="card-body py-2">
                <form method="GET" class="row g-2 align-items-center">
                    <div class="col-auto">
                        <select name="year" class="form-select form-select-sm">
                            <option value="">All Years</option>
                            <?php foreach ($years as $year): ?>
                            <option value="<?= $year['id'] ?>" <?= $year['is_current'] ? 'selected' : '' ?>>
                                <?= e($year['year_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <select name="term" class="form-select form-select-sm">
                            <option value="">All Terms</option>
                            <?php foreach ($terms as $term): ?>
                            <option value="<?= $term['id'] ?>"><?= e($term['term_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <select name="grade" class="form-select form-select-sm">
                            <option value="">All Grades</option>
                            <?php foreach ($grades as $grade): ?>
                            <option value="<?= $grade['id'] ?>"><?= e($grade['grade_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="ti ti-filter me-1"></i>Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <?php if (empty($structures)): ?>
                <div class="empty">
                    <div class="empty-img">
                        <i class="ti ti-file-spreadsheet" style="font-size: 4rem; color: #adb5bd;"></i>
                    </div>
                    <p class="empty-title">No fee structures defined</p>
                    <p class="empty-subtitle text-muted">
                        Create fee structures to define what each grade pays per term.
                    </p>
                    <?php if (hasPermission('Finance.write') || Gate::hasRole('ADMIN')): ?>
                    <div class="empty-action">
                        <a href="/finance/fee-structures/create" class="btn btn-primary">
                            <i class="ti ti-plus me-2"></i>Create First Structure
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-striped">
                        <thead>
                            <tr>
                                <th>Grade</th>
                                <th>Year / Term</th>
                                <th>Campus</th>
                                <th style="width: 120px;">Mandatory</th>
                                <th style="width: 120px;">Optional Max</th>
                                <th style="width: 80px;">Items</th>
                                <th style="width: 100px;">Status</th>
                                <th style="width: 80px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($structures as $structure): ?>
                            <tr>
                                <td>
                                    <strong><?= e($structure['grade_name']) ?></strong>
                                    <div class="text-muted small"><?= e($structure['name']) ?></div>
                                </td>
                                <td>
                                    <?= e($structure['year_name']) ?>
                                    <span class="text-muted">-</span>
                                    <?= e($structure['term_name']) ?>
                                </td>
                                <td><?= e($structure['campus_name']) ?></td>
                                <td class="text-end">
                                    <strong>KES <?= number_format($structure['total_mandatory'], 0) ?></strong>
                                </td>
                                <td class="text-end text-muted">
                                    KES <?= number_format($structure['total_optional_max'], 0) ?>
                                </td>
                                <td>
                                    <span class="badge bg-primary-lt"><?= $structure['line_count'] ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $statusColors[$structure['status']] ?? 'secondary' ?>-lt">
                                        <?= ucfirst(str_replace('_', ' ', $structure['status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-icon btn-ghost-secondary" data-bs-toggle="dropdown">
                                            <i class="ti ti-dots-vertical"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a class="dropdown-item" href="/finance/fee-structures/<?= $structure['id'] ?>">
                                                <i class="ti ti-eye me-2"></i>View / Edit
                                            </a>
                                            <?php if ($structure['status'] === 'draft'): ?>
                                            <a class="dropdown-item" href="#">
                                                <i class="ti ti-send me-2"></i>Submit for Approval
                                            </a>
                                            <?php endif; ?>
                                            <?php if ($structure['status'] === 'approved'): ?>
                                            <a class="dropdown-item" href="#">
                                                <i class="ti ti-world me-2"></i>Publish
                                            </a>
                                            <?php endif; ?>
                                            <a class="dropdown-item" href="#">
                                                <i class="ti ti-copy me-2"></i>Duplicate
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
</div>
