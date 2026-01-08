<?php
/**
 * Roles Tab Content
 */
?>

<!-- Roles Cards -->
<div class="row g-4">
    <?php if (empty($roles)): ?>
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-user-shield fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No roles found</p>
                    <a href="/settings/roles/create" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i> Create First Role
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($roles as $role): ?>
            <div class="col-md-6 col-lg-4" id="role-row-<?= $role['id'] ?>">
                <div class="card h-100 <?= $role['is_system'] ? 'border-primary' : '' ?>">
                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0"><?= e($role['display_name']) ?></h5>
                            <small class="text-muted font-monospace"><?= e($role['name']) ?></small>
                        </div>
                        <?php if ($role['is_system']): ?>
                            <span class="badge bg-primary">System</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if ($role['description']): ?>
                            <p class="text-muted small mb-3"><?= e($role['description']) ?></p>
                        <?php endif; ?>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="bg-light rounded p-2 text-center">
                                    <div class="h4 mb-0"><?= $role['user_count'] ?? 0 ?></div>
                                    <small class="text-muted">Users</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-light rounded p-2 text-center">
                                    <div class="h4 mb-0"><?= $role['permission_count'] ?? 0 ?></div>
                                    <small class="text-muted">Permissions</small>
                                </div>
                            </div>
                        </div>

                        <!-- Quick permission preview -->
                        <?php if ($role['name'] === 'ADMIN'): ?>
                            <div class="alert alert-info small mb-0 py-2">
                                <i class="fas fa-info-circle me-1"></i>
                                Full access to all modules
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-transparent">
                        <div class="d-flex gap-2">
                            <a href="/settings/roles/<?= $role['id'] ?>/edit" class="btn btn-sm btn-outline-primary flex-fill">
                                <i class="fas fa-edit me-1"></i> Edit Permissions
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="cloneRole(<?= $role['id'] ?>)" title="Clone Role">
                                <i class="fas fa-copy"></i>
                            </button>
                            <?php if (!$role['is_system']): ?>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteRole(<?= $role['id'] ?>, '<?= e($role['display_name']) ?>')" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Role Permission Legend -->
<div class="card mt-4">
    <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i> Permission Types</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="d-flex align-items-start mb-2">
                    <span class="badge bg-info me-2">View</span>
                    <small class="text-muted">Can see/read the data in this section</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex align-items-start mb-2">
                    <span class="badge bg-warning text-dark me-2">Modify</span>
                    <small class="text-muted">Can create, edit, and delete data in this section</small>
                </div>
            </div>
        </div>
    </div>
</div>
