<?php
$parent = $parent ?? [];
?>

<div class="container py-4">
    <h3 class="mb-4"><i class="ti ti-settings me-2"></i> Profile & Settings</h3>

    <div class="row g-4">
        <!-- Profile Information -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ti ti-user me-2"></i> Profile Information</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="avatar avatar-xl bg-primary text-white mx-auto mb-3" style="width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 600;">
                            <?= strtoupper(substr($parent['first_name'] ?? '', 0, 1) . substr($parent['last_name'] ?? '', 0, 1)) ?>
                        </div>
                        <h4 class="mb-1"><?= e(($parent['first_name'] ?? '') . ' ' . ($parent['last_name'] ?? '')) ?></h4>
                        <p class="text-muted mb-0"><?= e(ucfirst($parent['relationship'] ?? 'Guardian')) ?></p>
                    </div>

                    <dl class="row mb-0">
                        <dt class="col-5 text-muted">Email</dt>
                        <dd class="col-7"><?= e($parent['email'] ?? 'N/A') ?></dd>

                        <dt class="col-5 text-muted">Phone</dt>
                        <dd class="col-7"><?= e($parent['guardian_phone'] ?? $parent['phone'] ?? 'N/A') ?></dd>

                        <dt class="col-5 text-muted">Occupation</dt>
                        <dd class="col-7"><?= e($parent['occupation'] ?? 'N/A') ?></dd>

                        <dt class="col-5 text-muted">Account Status</dt>
                        <dd class="col-7">
                            <?php
                            $statusColors = ['active' => 'success', 'pending' => 'warning', 'suspended' => 'danger'];
                            $statusColor = $statusColors[$parent['status'] ?? ''] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?= $statusColor ?>-lt"><?= ucfirst($parent['status'] ?? 'Active') ?></span>
                        </dd>

                        <dt class="col-5 text-muted">Member Since</dt>
                        <dd class="col-7"><?= !empty($parent['created_at']) ? date('M d, Y', strtotime($parent['created_at'])) : 'N/A' ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Change Password -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ti ti-lock me-2"></i> Change Password</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="/parent/update-password">
                        <?= csrfField() ?>

                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" required minlength="8">
                            <small class="text-muted">At least 8 characters</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ti ti-check me-1"></i> Update Password
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- My Children -->
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ti ti-users me-2"></i> My Children</h5>
                </div>
                <div class="card-body p-0">
                    <?php $children = $_SESSION['parent_children'] ?? []; ?>
                    <?php if (empty($children)): ?>
                        <div class="text-center py-4">
                            <i class="ti ti-users-group text-muted" style="font-size: 2rem;"></i>
                            <p class="text-muted mt-2 mb-0">No children linked to your account</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($children as $child): ?>
                                <div class="list-group-item">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar bg-primary-lt text-primary me-3" style="width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                <?= strtoupper(substr($child['first_name'], 0, 1) . substr($child['last_name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <strong><?= e($child['first_name'] . ' ' . $child['last_name']) ?></strong>
                                                <br>
                                                <small class="text-muted"><?= e($child['admission_number'] ?? '') ?> | <?= e($child['class_name'] ?? 'No Class') ?></small>
                                            </div>
                                        </div>
                                        <a href="/parent/child/<?= $child['id'] ?>/profile" class="btn btn-sm btn-outline-primary">
                                            View <i class="ti ti-chevron-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout -->
    <div class="text-center mt-4">
        <a href="/parent/logout" class="btn btn-outline-danger">
            <i class="ti ti-logout me-1"></i> Sign Out
        </a>
    </div>
</div>
