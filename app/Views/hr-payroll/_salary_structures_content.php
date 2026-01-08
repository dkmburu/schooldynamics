<?php
/**
 * Salary Structures Content
 */

$structures = $structures ?? [];
$grades = $grades ?? [];
$components = $components ?? [];
$currency = $_SESSION['currency'] ?? 'KES';
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <div class="page-pretitle">HR & Payroll</div>
                <h2 class="page-title">
                    <i class="ti ti-hierarchy-2 me-2"></i>Salary Structures
                </h2>
            </div>
            <div class="col-auto ms-auto">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newStructureModal">
                    <i class="ti ti-plus me-1"></i>New Structure
                </button>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="row">
            <!-- Salary Structures -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Salary Structures</h3>
                    </div>
                    <?php if (empty($structures)): ?>
                    <div class="card-body">
                        <div class="empty">
                            <p class="empty-title">No salary structures</p>
                            <p class="empty-subtitle text-muted">Create salary structures to define pay scales</p>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($structures as $struct): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?= e($struct['structure_name']) ?></strong>
                                    <div class="text-muted small"><?= e($struct['description'] ?? 'No description') ?></div>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary" title="Edit">
                                        <i class="ti ti-edit"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Salary Grades -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Salary Grades</h3>
                        <div class="card-actions">
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newGradeModal">
                                <i class="ti ti-plus me-1"></i>Add Grade
                            </button>
                        </div>
                    </div>
                    <?php if (empty($grades)): ?>
                    <div class="card-body">
                        <div class="empty">
                            <p class="empty-title">No salary grades</p>
                            <p class="empty-subtitle text-muted">Create grades with min/max salary ranges</p>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th class="text-end">Min Salary</th>
                                    <th class="text-end">Max Salary</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($grades as $grade): ?>
                                <tr>
                                    <td><code><?= e($grade['grade_code']) ?></code></td>
                                    <td><?= e($grade['grade_name']) ?></td>
                                    <td class="text-end"><?= $currency ?> <?= number_format($grade['min_salary'] ?? 0) ?></td>
                                    <td class="text-end"><?= $currency ?> <?= number_format($grade['max_salary'] ?? 0) ?></td>
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

<!-- New Structure Modal -->
<div class="modal modal-blur fade" id="newStructureModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Salary Structure</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="/hr-payroll/salary-structures">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Structure Name</label>
                        <input type="text" name="structure_name" class="form-control" required
                               placeholder="e.g., Teaching Staff, Admin Staff">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- New Grade Modal -->
<div class="modal modal-blur fade" id="newGradeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Salary Grade</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="/hr-payroll/salary-grades">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required">Grade Code</label>
                            <input type="text" name="grade_code" class="form-control" required
                                   placeholder="e.g., T1, G1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Grade Name</label>
                            <input type="text" name="grade_name" class="form-control" required
                                   placeholder="e.g., Entry Level">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Structure</label>
                            <select name="structure_id" class="form-select">
                                <option value="">No Structure</option>
                                <?php foreach ($structures as $struct): ?>
                                <option value="<?= $struct['id'] ?>"><?= e($struct['structure_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Basic Amount</label>
                            <input type="number" name="basic_amount" class="form-control" step="0.01">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Min Salary</label>
                            <input type="number" name="min_salary" class="form-control" step="0.01">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Max Salary</label>
                            <input type="number" name="max_salary" class="form-control" step="0.01">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>
