<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title><?= $pageTitle ?? 'Dashboard' ?> - <?= e($_SESSION['tenant_name'] ?? 'School') ?></title>
    <!-- CSS files -->
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/css/tabler.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet"/>
    <link href="/css/professional-theme.css" rel="stylesheet"/>
    <style>
        @import url('https://rsms.me/inter/inter.css');

        /* Legacy table classes for compatibility */
        .tbl_sh { background-color: #f8f9fa; font-weight: 600; }
        .tbl_data { background-color: white; }
    </style>
</head>
<body>
    <div class="page">
        <!-- Sidebar -->
        <aside class="navbar navbar-vertical navbar-expand-lg navbar-dark">
            <div class="container-fluid">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <h1 class="navbar-brand navbar-brand-autodark">
                    <a href="/dashboard">
                        <span class="text-white"><?= e($_SESSION['tenant_name'] ?? 'School') ?></span>
                    </a>
                </h1>
                <div class="navbar-nav flex-row d-lg-none">
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown">
                            <span class="avatar avatar-sm">
                                <?= strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 2)) ?>
                            </span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="/profile">Profile</a>
                            <a class="dropdown-item" href="/logout">Logout</a>
                        </div>
                    </div>
                </div>
                <div class="collapse navbar-collapse" id="sidebar-menu">
                    <ul class="navbar-nav pt-lg-3">
                        <li class="nav-item">
                            <a class="nav-link" href="/dashboard">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <i class="ti ti-home"></i>
                                </span>
                                <span class="nav-link-title">Dashboard</span>
                            </a>
                        </li>

                        <?php
                        $navModules = getNavigationModules();
                        foreach ($navModules as $module):
                            // Check module permission
                            $hasModulePermission = hasPermission($module['name'] . '.view') || Gate::hasRole('ADMIN');

                            // Special cases for role-based access
                            if ($module['name'] === 'Academics' && Gate::hasRole('TEACHER')) {
                                $hasModulePermission = true;
                            }
                            if ($module['name'] === 'Finance' && Gate::hasRole('BURSAR')) {
                                $hasModulePermission = true;
                            }

                            if (!$hasModulePermission) continue;
                        ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#navbar-<?= strtolower($module['name']) ?>" data-bs-toggle="dropdown" role="button">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <i class="<?= e($module['icon']) ?>"></i>
                                </span>
                                <span class="nav-link-title"><?= e($module['display_name']) ?></span>
                            </a>
                            <?php if (!empty($module['submodules'])): ?>
                            <div class="dropdown-menu">
                                <?php
                                // Group submodules by section for Students module
                                if ($module['name'] === 'Students'):
                                    $applicantItems = array_filter($module['submodules'], fn($sm) => strpos($sm['name'], 'Applicants') !== false || strpos($sm['name'], 'Application') !== false);
                                    $studentItems = array_filter($module['submodules'], fn($sm) => strpos($sm['name'], 'AllStudents') !== false || strpos($sm['name'], 'AddStudent') !== false);

                                    if (!empty($applicantItems)):
                                ?>
                                <div class="dropdown-item disabled text-muted small">APPLICANTS</div>
                                <?php
                                    foreach ($applicantItems as $submodule):
                                        $requiresWrite = strpos($submodule['name'], 'New') !== false || strpos($submodule['name'], 'Add') !== false;
                                        if ($requiresWrite && !hasPermission($module['name'] . '.write') && !Gate::hasRole('ADMIN')) continue;
                                ?>
                                <a class="dropdown-item" href="<?= e($submodule['route']) ?>">
                                    <?= e($submodule['display_name']) ?>
                                </a>
                                <?php endforeach; ?>
                                <?php endif; ?>

                                <?php if (!empty($studentItems)): ?>
                                <div class="dropdown-divider"></div>
                                <div class="dropdown-item disabled text-muted small">ENROLLED STUDENTS</div>
                                <?php
                                    foreach ($studentItems as $submodule):
                                        $requiresWrite = strpos($submodule['name'], 'Add') !== false;
                                        if ($requiresWrite && !hasPermission($module['name'] . '.write') && !Gate::hasRole('ADMIN')) continue;
                                ?>
                                <a class="dropdown-item" href="<?= e($submodule['route']) ?>">
                                    <?= e($submodule['display_name']) ?>
                                </a>
                                <?php endforeach; ?>
                                <?php endif; ?>
                                <?php else: ?>
                                <?php
                                    // Regular modules - just list submodules
                                    foreach ($module['submodules'] as $submodule):
                                        $requiresWrite = strpos($submodule['name'], 'New') !== false || strpos($submodule['name'], 'Add') !== false || strpos($submodule['name'], 'Create') !== false;
                                        if ($requiresWrite && !hasPermission($module['name'] . '.write') && !Gate::hasRole('ADMIN')) continue;
                                ?>
                                <a class="dropdown-item" href="<?= e($submodule['route']) ?>">
                                    <?= e($submodule['display_name']) ?>
                                </a>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>

                        <?php if (Gate::hasRole('ADMIN') || Gate::hasRole('HEAD_TEACHER')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/settings">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <i class="ti ti-settings"></i>
                                </span>
                                <span class="nav-link-title">Settings</span>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </aside>

        <!-- Page Header & Content -->
        <div class="page-wrapper">
            <div class="page-header d-print-none">
                <div class="container-xl">
                    <div class="row g-2 align-items-center">
                        <div class="col">
                            <!-- Breadcrumbs -->
                            <?php if (isset($breadcrumbs) && !empty($breadcrumbs)): ?>
                            <div class="page-pretitle">
                                <ol class="breadcrumb breadcrumb-arrows" aria-label="breadcrumbs">
                                    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                                    <?php foreach ($breadcrumbs as $index => $crumb): ?>
                                        <?php if ($index === array_key_last($breadcrumbs)): ?>
                                            <li class="breadcrumb-item active" aria-current="page"><?= e($crumb['label']) ?></li>
                                        <?php else: ?>
                                            <li class="breadcrumb-item"><a href="<?= e($crumb['url']) ?>"><?= e($crumb['label']) ?></a></li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ol>
                            </div>
                            <?php endif; ?>
                            <h2 class="page-title"><?= $pageTitle ?? 'Dashboard' ?></h2>
                        </div>
                        <div class="col-auto ms-auto d-print-none">
                            <div class="btn-list">
                                <a href="/profile" class="btn btn-ghost-dark d-none d-sm-inline-block">
                                    <i class="ti ti-user icon"></i>
                                    <?= e($_SESSION['full_name'] ?? 'User') ?>
                                </a>
                                <a href="/logout" class="btn btn-outline-danger d-none d-sm-inline-block">
                                    <i class="ti ti-logout icon"></i>
                                    Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Flash Messages -->
            <div class="page-body">
                <div class="container-xl">
                    <?php if ($message = flash('success')): ?>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <div class="d-flex">
                            <div><i class="ti ti-check icon alert-icon"></i></div>
                            <div><?= e($message) ?></div>
                        </div>
                        <a class="btn-close" data-bs-dismiss="alert"></a>
                    </div>
                    <?php endif; ?>

                    <?php if ($message = flash('error')): ?>
                    <div class="alert alert-danger alert-dismissible" role="alert">
                        <div class="d-flex">
                            <div><i class="ti ti-alert-circle icon alert-icon"></i></div>
                            <div><?= e($message) ?></div>
                        </div>
                        <a class="btn-close" data-bs-dismiss="alert"></a>
                    </div>
                    <?php endif; ?>

                    <?php if ($message = flash('info')): ?>
                    <div class="alert alert-info alert-dismissible" role="alert">
                        <div class="d-flex">
                            <div><i class="ti ti-info-circle icon alert-icon"></i></div>
                            <div><?= e($message) ?></div>
                        </div>
                        <a class="btn-close" data-bs-dismiss="alert"></a>
                    </div>
                    <?php endif; ?>

                    <?php require $contentView ?? ''; ?>
                </div>
            </div>

            <!-- Footer -->
            <footer class="footer footer-transparent d-print-none">
                <div class="container-xl">
                    <div class="row text-center align-items-center flex-row-reverse">
                        <div class="col-lg-auto ms-lg-auto">
                            <ul class="list-inline list-inline-dots mb-0">
                                <li class="list-inline-item">
                                    Academic Year: <strong><?= $_SESSION['current_year'] ?? date('Y') ?></strong>
                                </li>
                            </ul>
                        </div>
                        <div class="col-12 col-lg-auto mt-3 mt-lg-0">
                            <ul class="list-inline list-inline-dots mb-0">
                                <li class="list-inline-item">
                                    &copy; <?= date('Y') ?> <?= e($_SESSION['tenant_name'] ?? 'School') ?>
                                </li>
                                <li class="list-inline-item">
                                    Powered by SchoolDynamics
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- Tabler Core -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/js/tabler.min.js"></script>
</body>
</html>
