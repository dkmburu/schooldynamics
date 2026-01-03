<?php
/**
 * Applicant Profile - Guardians Tab
 */
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0" style="font-size: 14px; font-weight: 600; color: #605e5c;">Prospective Guardians</h5>
    <?php if (hasPermission('Students.write') || Gate::hasRole('ADMIN')): ?>
    <button class="btn btn-primary" onclick="showAddGuardianModal(<?= $applicant['id'] ?>)">
        <i class="ti ti-plus me-2"></i> Add Guardian
    </button>
    <?php endif; ?>
</div>

<?php if (empty($guardians)): ?>
    <div class="alert alert-warning">
        <i class="ti ti-alert-triangle me-2"></i> No guardians added yet. Please add at least one guardian for this applicant.
    </div>
<?php else: ?>
    <div class="row">
        <?php foreach ($guardians as $guardian): ?>
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h6 class="mb-1" style="font-size: 14px; font-weight: 600; color: #323130;">
                                <?= e($guardian['first_name'] . ' ' . $guardian['last_name']) ?>
                                <?php if ($guardian['is_primary']): ?>
                                    <span class="badge bg-primary ms-2" style="font-size: 0.65rem; vertical-align: middle;">Primary</span>
                                <?php endif; ?>
                            </h6>
                            <div class="text-muted"><?= e(ucwords(str_replace('_', ' ', $guardian['relationship']))) ?></div>
                        </div>
                        <?php if (hasPermission('Students.write') || Gate::hasRole('ADMIN')): ?>
                        <div class="dropdown">
                            <button class="btn btn-primary btn-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                <i class="ti ti-dots-vertical"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="#" onclick="showEditGuardianModal(<?= $guardian['id'] ?>, <?= htmlspecialchars(json_encode($guardian), ENT_QUOTES, 'UTF-8') ?>); return false;">
                                    <i class="ti ti-edit me-2"></i> Edit
                                </a>
                                <?php if (!$guardian['is_primary']): ?>
                                <a class="dropdown-item" href="#" onclick="setPrimaryGuardian(<?= $guardian['id'] ?>, <?= $applicant['id'] ?>); return false;">
                                    <i class="ti ti-star me-2"></i> Set as Primary
                                </a>
                                <?php endif; ?>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-danger" href="#" onclick="deleteGuardian(<?= $guardian['id'] ?>, <?= $applicant['id'] ?>); return false;">
                                    <i class="ti ti-trash me-2"></i> Remove
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <table class="table table-borderless table-sm mb-0">
                        <tr>
                            <td style="width: 40%;"><i class="ti ti-phone text-muted me-2"></i> Phone:</td>
                            <td><strong><?= e($guardian['phone']) ?></strong></td>
                        </tr>
                        <?php if (!empty($guardian['email'])): ?>
                        <tr>
                            <td><i class="ti ti-mail text-muted me-2"></i> Email:</td>
                            <td><?= e($guardian['email']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($guardian['id_number'])): ?>
                        <tr>
                            <td><i class="ti ti-id text-muted me-2"></i> ID Number:</td>
                            <td><?= e($guardian['id_number']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($guardian['occupation'])): ?>
                        <tr>
                            <td><i class="ti ti-briefcase text-muted me-2"></i> Occupation:</td>
                            <td><?= e($guardian['occupation']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($guardian['employer'])): ?>
                        <tr>
                            <td><i class="ti ti-building text-muted me-2"></i> Employer:</td>
                            <td><?= e($guardian['employer']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($guardian['address'])): ?>
                        <tr>
                            <td><i class="ti ti-map-pin text-muted me-2"></i> Address:</td>
                            <td><?= e($guardian['address']) ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
