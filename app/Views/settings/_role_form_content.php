<?php
/**
 * Role Create/Edit Form with Permission Matrix
 */

$role = $role ?? null;
$mode = $mode ?? 'create';
$permissions = $permissions ?? [];

// Get old input or current values
$values = [
    'name' => old('name', $role['name'] ?? ''),
    'display_name' => old('display_name', $role['display_name'] ?? ''),
    'description' => old('description', $role['description'] ?? ''),
];
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1"><?= $mode === 'create' ? 'Create New Role' : 'Edit Role: ' . e($role['display_name']) ?></h3>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/settings/users?tab=roles">Users & Roles</a></li>
                    <li class="breadcrumb-item active"><?= $mode === 'create' ? 'Create Role' : 'Edit' ?></li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="/settings/users?tab=roles" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Roles
            </a>
        </div>
    </div>

    <form method="POST" action="<?= $mode === 'create' ? '/settings/roles' : '/settings/roles/' . $role['id'] ?>">
        <?= csrfField() ?>
        <?php if ($mode === 'edit'): ?>
            <input type="hidden" name="_method" value="PUT">
        <?php endif; ?>

        <div class="row">
            <!-- Role Details -->
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user-shield me-2"></i> Role Details</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($role && $role['is_system']): ?>
                            <div class="alert alert-warning mb-3">
                                <i class="fas fa-lock me-2"></i>
                                This is a system role. Name cannot be changed.
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">Role Name <span class="text-danger">*</span></label>
                            <?php if ($role && $role['is_system']): ?>
                                <input type="text" class="form-control" value="<?= e($role['name']) ?>" disabled>
                            <?php else: ?>
                                <input type="text" name="name" class="form-control font-monospace" value="<?= e($values['name']) ?>" placeholder="ROLE_NAME" required>
                                <small class="text-muted">Uppercase, no spaces (e.g., DEPARTMENT_HEAD)</small>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Display Name <span class="text-danger">*</span></label>
                            <input type="text" name="display_name" class="form-control" value="<?= e($values['display_name']) ?>" placeholder="Department Head" required <?= ($role && $role['is_system']) ? 'readonly' : '' ?>>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Brief description of this role..."><?= e($values['description']) ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <button type="button" class="btn btn-sm btn-outline-primary w-100 mb-2" onclick="selectAll()">
                            <i class="fas fa-check-double me-1"></i> Select All Permissions
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary w-100 mb-2" onclick="deselectAll()">
                            <i class="fas fa-times me-1"></i> Deselect All
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-info w-100" onclick="selectAllView()">
                            <i class="fas fa-eye me-1"></i> Select All View Permissions
                        </button>
                    </div>
                </div>
            </div>

            <!-- Permissions Matrix -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-key me-2"></i> Permissions</h5>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" onclick="expandAll()">
                                <i class="fas fa-expand-alt"></i> Expand All
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="collapseAll()">
                                <i class="fas fa-compress-alt"></i> Collapse All
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="accordion" id="permissionsAccordion">
                            <?php foreach ($permissions as $moduleId => $module): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#module-<?= $moduleId ?>">
                                            <i class="<?= $module['icon'] ?? 'fas fa-folder' ?> me-2"></i>
                                            <strong><?= e($module['display_name']) ?></strong>
                                            <span class="badge bg-secondary ms-2 module-count" data-module="<?= $moduleId ?>">0</span>
                                        </button>
                                    </h2>
                                    <div id="module-<?= $moduleId ?>" class="accordion-collapse collapse" data-bs-parent="#permissionsAccordion">
                                        <div class="accordion-body p-0">
                                            <table class="table table-sm table-hover mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th style="width: 50%">Submodule</th>
                                                        <th class="text-center" style="width: 25%">View</th>
                                                        <th class="text-center" style="width: 25%">Modify</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($module['submodules'] as $submoduleId => $submodule): ?>
                                                        <tr>
                                                            <td>
                                                                <small class="text-muted font-monospace"><?= e($submodule['name']) ?></small>
                                                                <br><?= e($submodule['display_name']) ?>
                                                            </td>
                                                            <?php foreach (['view', 'modify'] as $action): ?>
                                                                <?php
                                                                $perm = null;
                                                                foreach ($submodule['permissions'] as $p) {
                                                                    if ($p['action'] === $action) {
                                                                        $perm = $p;
                                                                        break;
                                                                    }
                                                                }
                                                                ?>
                                                                <td class="text-center">
                                                                    <?php if ($perm): ?>
                                                                        <div class="form-check d-flex justify-content-center">
                                                                            <input type="checkbox"
                                                                                   class="form-check-input permission-checkbox"
                                                                                   name="permissions[]"
                                                                                   value="<?= $perm['id'] ?>"
                                                                                   data-module="<?= $moduleId ?>"
                                                                                   data-action="<?= $action ?>"
                                                                                   <?= $perm['checked'] ? 'checked' : '' ?>>
                                                                        </div>
                                                                    <?php else: ?>
                                                                        <span class="text-muted">-</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                            <?php endforeach; ?>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                                <tfoot class="table-light">
                                                    <tr>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-link p-0" onclick="toggleModuleAll(<?= $moduleId ?>)">
                                                                Toggle All in <?= e($module['display_name']) ?>
                                                            </button>
                                                        </td>
                                                        <td colspan="2"></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="/settings/users?tab=roles" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>
                        <?= $mode === 'create' ? 'Create Role' : 'Save Changes' ?>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Define all functions on window for AJAX compatibility
window.updateAllCounts = function() {
    const modules = {};
    document.querySelectorAll('.permission-checkbox').forEach(function(checkbox) {
        const moduleId = checkbox.dataset.module;
        if (!modules[moduleId]) modules[moduleId] = 0;
        if (checkbox.checked) modules[moduleId]++;
    });

    document.querySelectorAll('.module-count').forEach(function(badge) {
        const moduleId = badge.dataset.module;
        badge.textContent = modules[moduleId] || 0;
        badge.className = 'badge ms-2 module-count ' + (modules[moduleId] > 0 ? 'bg-primary' : 'bg-secondary');
    });
};

window.selectAll = function() {
    document.querySelectorAll('.permission-checkbox').forEach(cb => cb.checked = true);
    window.updateAllCounts();
};

window.deselectAll = function() {
    document.querySelectorAll('.permission-checkbox').forEach(cb => cb.checked = false);
    window.updateAllCounts();
};

window.selectAllView = function() {
    document.querySelectorAll('.permission-checkbox[data-action="view"]').forEach(cb => cb.checked = true);
    window.updateAllCounts();
};

window.toggleModuleAll = function(moduleId) {
    const checkboxes = document.querySelectorAll('.permission-checkbox[data-module="' + moduleId + '"]');
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    checkboxes.forEach(cb => cb.checked = !allChecked);
    window.updateAllCounts();
};

window.expandAll = function() {
    document.querySelectorAll('.accordion-collapse').forEach(function(el) {
        el.classList.add('show');
    });
};

window.collapseAll = function() {
    document.querySelectorAll('.accordion-collapse').forEach(function(el) {
        el.classList.remove('show');
    });
};

// Initialize on page load
window.initRolePermissions = function() {
    // Update counts
    window.updateAllCounts();

    // Bind change events to checkboxes
    document.querySelectorAll('.permission-checkbox').forEach(function(checkbox) {
        checkbox.removeEventListener('change', window.updateAllCounts);
        checkbox.addEventListener('change', window.updateAllCounts);
    });
};

// Run initialization
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', window.initRolePermissions);
} else {
    window.initRolePermissions();
}
</script>

<style>
.permission-checkbox {
    width: 1.2rem;
    height: 1.2rem;
    cursor: pointer;
}
.accordion-button:not(.collapsed) {
    background-color: rgba(var(--bs-primary-rgb), 0.1);
}
</style>
