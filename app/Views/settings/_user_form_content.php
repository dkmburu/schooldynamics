<?php
/**
 * User Create/Edit Form
 */

$user = $user ?? null;
$mode = $mode ?? 'create';
$roles = $roles ?? [];

// Get old input or current values
$values = [
    'username' => old('username', $user['username'] ?? ''),
    'email' => old('email', $user['email'] ?? ''),
    'full_name' => old('full_name', $user['full_name'] ?? ''),
    'phone' => old('phone', $user['phone'] ?? ''),
    'role_id' => old('role_id', $user['role_id'] ?? ''),
    'status' => old('status', $user['status'] ?? 'active'),
];
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1"><?= $mode === 'create' ? 'Add New User' : 'Edit User' ?></h3>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/settings/users">Users & Roles</a></li>
                    <li class="breadcrumb-item active"><?= $mode === 'create' ? 'Add User' : 'Edit' ?></li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="/settings/users" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Users
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <form method="POST" action="<?= $mode === 'create' ? '/settings/users' : '/settings/users/' . $user['id'] ?>">
                <?= csrfField() ?>
                <?php if ($mode === 'edit'): ?>
                    <input type="hidden" name="_method" value="PUT">
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user me-2"></i> Account Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Username -->
                            <div class="col-md-6">
                                <label class="form-label">Username <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">@</span>
                                    <input type="text" name="username" class="form-control" value="<?= e($values['username']) ?>" required <?= $mode === 'edit' ? 'readonly' : '' ?>>
                                </div>
                                <small class="text-muted">Unique identifier for login</small>
                            </div>

                            <!-- Email -->
                            <div class="col-md-6">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" value="<?= e($values['email']) ?>" required>
                            </div>

                            <!-- Full Name -->
                            <div class="col-md-6">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" class="form-control" value="<?= e($values['full_name']) ?>" required>
                            </div>

                            <!-- Phone -->
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?= e($values['phone']) ?>">
                            </div>

                            <!-- Role -->
                            <div class="col-md-6">
                                <label class="form-label">Role <span class="text-danger">*</span></label>
                                <select name="role_id" class="form-select" required>
                                    <option value="">Select Role...</option>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= $role['id'] ?>" <?= $values['role_id'] == $role['id'] ? 'selected' : '' ?>>
                                            <?= e($role['display_name']) ?>
                                            <?php if ($role['is_system']): ?>(System)<?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Determines user permissions</small>
                            </div>

                            <!-- Status -->
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active" <?= $values['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= $values['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    <option value="suspended" <?= $values['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Password Section -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-lock me-2"></i>
                            <?= $mode === 'create' ? 'Set Password' : 'Change Password' ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($mode === 'edit'): ?>
                            <div class="alert alert-info mb-3">
                                <i class="fas fa-info-circle me-2"></i>
                                Leave password fields empty to keep the current password.
                            </div>
                        <?php endif; ?>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">
                                    Password <?php if ($mode === 'create'): ?><span class="text-danger">*</span><?php endif; ?>
                                </label>
                                <input type="password" name="password" class="form-control" <?= $mode === 'create' ? 'required' : '' ?> minlength="8">
                                <small class="text-muted">Minimum 8 characters</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">
                                    Confirm Password <?php if ($mode === 'create'): ?><span class="text-danger">*</span><?php endif; ?>
                                </label>
                                <input type="password" name="password_confirm" class="form-control" <?= $mode === 'create' ? 'required' : '' ?>>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="/settings/users" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>
                        <?= $mode === 'create' ? 'Create User' : 'Save Changes' ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Side Info -->
        <div class="col-lg-4">
            <?php if ($mode === 'edit' && $user): ?>
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">User Info</h6>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-5">Created</dt>
                            <dd class="col-sm-7"><?= date('M d, Y', strtotime($user['created_at'])) ?></dd>

                            <dt class="col-sm-5">Last Login</dt>
                            <dd class="col-sm-7">
                                <?= $user['last_login_at'] ? date('M d, Y H:i', strtotime($user['last_login_at'])) : 'Never' ?>
                            </dd>

                            <?php if ($user['last_login_ip']): ?>
                                <dt class="col-sm-5">Last IP</dt>
                                <dd class="col-sm-7"><code><?= e($user['last_login_ip']) ?></code></dd>
                            <?php endif; ?>

                            <dt class="col-sm-5">Login Attempts</dt>
                            <dd class="col-sm-7"><?= $user['failed_login_attempts'] ?></dd>
                        </dl>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card <?= $mode === 'edit' ? 'mt-3' : '' ?>">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-lightbulb me-2"></i> Tips</h6>
                </div>
                <div class="card-body small">
                    <ul class="mb-0 ps-3">
                        <li class="mb-2">Each user can only have <strong>one role</strong></li>
                        <li class="mb-2">The role determines what the user can see and do</li>
                        <li class="mb-2"><strong>Admin</strong> role has full access to everything</li>
                        <li>Use strong passwords with letters, numbers, and symbols</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
