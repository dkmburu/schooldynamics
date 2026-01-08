<?php
/**
 * Users Tab Content
 */
?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" action="/settings/users" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small text-muted">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Username, name, or email..." value="<?= e($filters['search'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Role</label>
                <select name="role" class="form-select">
                    <option value="">All Roles</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= $role['id'] ?>" <?= ($filters['role'] ?? '') == $role['id'] ? 'selected' : '' ?>>
                            <?= e($role['display_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    <option value="suspended" <?= ($filters['status'] ?? '') === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-outline-primary me-2">
                    <i class="fas fa-search me-1"></i> Search
                </button>
                <a href="/settings/users" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-1"></i> Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-body p-0">
        <?php if (empty($users)): ?>
            <div class="text-center py-5">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <p class="text-muted">No users found</p>
                <a href="/settings/users/create" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i> Add First User
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr id="user-row-<?= $user['id'] ?>">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-md bg-primary text-white me-3" style="width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                                            <?= strtoupper(substr($user['full_name'], 0, 2)) ?>
                                        </div>
                                        <div>
                                            <strong><?= e($user['full_name']) ?></strong>
                                            <br>
                                            <small class="text-muted">@<?= e($user['username']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?= e($user['email']) ?></td>
                                <td>
                                    <?php if ($user['role_name']): ?>
                                        <span class="badge bg-<?= $user['role_name'] === 'ADMIN' ? 'danger' : 'primary' ?>-lt">
                                            <?= e($user['role_display_name']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-lt">No Role</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $statusColors = ['active' => 'success', 'inactive' => 'warning', 'suspended' => 'danger'];
                                    $statusColor = $statusColors[$user['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $statusColor ?>-lt"><?= ucfirst($user['status']) ?></span>
                                </td>
                                <td>
                                    <?php if ($user['last_login_at']): ?>
                                        <small><?= date('M d, Y H:i', strtotime($user['last_login_at'])) ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">Never</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="/settings/users/<?= $user['id'] ?>/edit" class="btn btn-outline-primary" title="Edit">
                                            <i class="fas fa-edit me-1"></i> Edit
                                        </a>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <button type="button" class="btn btn-outline-<?= $user['status'] === 'active' ? 'warning' : 'success' ?>" onclick="toggleUserStatus(<?= $user['id'] ?>)" title="<?= $user['status'] === 'active' ? 'Deactivate' : 'Activate' ?>">
                                                <i class="fas fa-<?= $user['status'] === 'active' ? 'ban' : 'check' ?> me-1"></i>
                                                <?= $user['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger" onclick="deleteUser(<?= $user['id'] ?>, '<?= e($user['username']) ?>')" title="Delete">
                                                <i class="fas fa-trash me-1"></i> Delete
                                            </button>
                                        <?php endif; ?>
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
