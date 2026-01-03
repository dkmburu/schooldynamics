<?php
$contentView = __DIR__ . '/_dashboard_content.php';
require __DIR__ . '/../layouts/admin.php';
?>
<?php return; // Everything below is in the content view ?>
<?php
// File: app/Views/admin/_dashboard_content.php
file_put_contents(__DIR__ . '/_dashboard_content.php', '<?php
$pageTitle = "Dashboard";
?>
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">Dashboard</h2>
                <div class="text-muted mt-1">Welcome back, <?= e($admin_name) ?>!</div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="row row-deck row-cards">
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Total Tenants</div>
                        </div>
                        <div class="h1 mb-3"><?= $stats[\'total_tenants\'] ?? 0 ?></div>
                        <div class="d-flex mb-2">
                            <div>All registered schools</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Active Tenants</div>
                        </div>
                        <div class="h1 mb-3 text-success"><?= $stats[\'active_tenants\'] ?? 0 ?></div>
                        <div class="d-flex mb-2">
                            <div>Currently operational</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Inactive Tenants</div>
                        </div>
                        <div class="h1 mb-3 text-warning"><?= $stats[\'inactive_tenants\'] ?? 0 ?></div>
                        <div class="d-flex mb-2">
                            <div>Suspended or inactive</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">System Health</div>
                        </div>
                        <div class="h1 mb-3 text-success">
                            <i class="ti ti-circle-check"></i>
                        </div>
                        <div class="d-flex mb-2">
                            <div>All systems operational</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Tenants</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-vcenter">
                                <thead>
                                    <tr>
                                        <th>Subdomain</th>
                                        <th>School Name</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($stats[\'recent_tenants\'])): ?>
                                        <?php foreach ($stats[\'recent_tenants\'] as $tenant): ?>
                                        <tr>
                                            <td><strong><?= e($tenant[\'subdomain\']) ?></strong></td>
                                            <td><?= e($tenant[\'school_name\']) ?></td>
                                            <td>
                                                <?php if ($tenant[\'status\'] === \'active\'): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning"><?= ucfirst(e($tenant[\'status\'])) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= formatDateTime($tenant[\'created_at\']) ?></td>
                                            <td>
                                                <a href="/tenants/<?= $tenant[\'id\'] ?>/edit" class="btn btn-sm btn-primary">
                                                    <i class="ti ti-edit icon"></i> Edit
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No tenants yet</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="/tenants" class="btn btn-primary">
                            <i class="ti ti-building icon"></i>
                            View All Tenants
                        </a>
                        <a href="/tenants/create" class="btn btn-success ms-2">
                            <i class="ti ti-plus icon"></i>
                            Add New Tenant
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
');
?>
