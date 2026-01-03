<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $pageTitle ?? 'Dashboard' ?> | <?= e($_SESSION['tenant_name'] ?? 'School') ?></title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- AdminLTE -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">

    <style>
        /* Minimal custom styling */
        .brand-link {
            font-weight: 600;
            font-size: 1.1rem;
        }

        /* Status badge dots */
        .badge-dot {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0;
            background: transparent !important;
            border: none;
            color: #6c757d;
            font-weight: 500;
            font-size: 0.875rem;
        }

        .badge-dot::before {
            content: '';
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .badge-dot.badge-primary::before { background-color: #007bff; }
        .badge-dot.badge-success::before { background-color: #28a745; }
        .badge-dot.badge-warning::before { background-color: #ffc107; }
        .badge-dot.badge-danger::before { background-color: #dc3545; }
        .badge-dot.badge-info::before { background-color: #17a2b8; }
        .badge-dot.badge-secondary::before { background-color: #6c757d; }

        /* Reduce sidebar navigation font sizes - EXTRA SMALL */
        .main-sidebar .nav-sidebar > .nav-item > .nav-link {
            font-size: 0.65rem !important;
            padding: 0.3rem 0.4rem !important;
        }

        .main-sidebar .nav-sidebar > .nav-item > .nav-link > p {
            font-size: 0.65rem !important;
        }

        .main-sidebar .nav-sidebar .nav-treeview > .nav-item > .nav-link {
            font-size: 0.6rem !important;
            padding: 0.25rem 0.4rem 0.25rem 2rem !important;
        }

        .main-sidebar .nav-sidebar .nav-treeview > .nav-item > .nav-link > p {
            font-size: 0.6rem !important;
        }

        .main-sidebar .nav-sidebar .nav-header {
            font-size: 0.55rem !important;
            padding: 0.25rem 0.8rem !important;
        }

        .main-sidebar .nav-icon {
            font-size: 0.65rem !important;
            width: 1rem !important;
            margin-right: 0.3rem !important;
        }

        /* Reduce tab navigation font sizes */
        .nav-tabs .nav-link {
            font-size: 0.7rem !important;
            padding: 0.35rem 0.7rem !important;
        }

        .nav-tabs .nav-link i {
            font-size: 0.65rem !important;
        }

        .nav-tabs .badge {
            font-size: 0.6rem !important;
            padding: 0.1rem 0.35rem !important;
        }

        .nav-pills .nav-link {
            font-size: 0.7rem !important;
            padding: 0.35rem 0.7rem !important;
        }

        .nav-pills .nav-link i {
            font-size: 0.65rem !important;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="/dashboard" class="nav-link">Home</a>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <!-- User Dropdown -->
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-user"></i>
                    <span class="d-none d-md-inline ml-1"><?= e($_SESSION['full_name'] ?? 'User') ?></span>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <span class="dropdown-item dropdown-header"><?= e($_SESSION['full_name'] ?? 'User') ?></span>
                    <div class="dropdown-divider"></div>
                    <a href="/profile" class="dropdown-item">
                        <i class="fas fa-user mr-2"></i> Profile
                    </a>
                    <a href="/settings" class="dropdown-item">
                        <i class="fas fa-cog mr-2"></i> Settings
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="/logout" class="dropdown-item">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                </div>
            </li>
        </ul>
    </nav>

    <!-- Main Sidebar -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="/dashboard" class="brand-link">
            <span class="brand-text font-weight-light"><?= e($_SESSION['tenant_name'] ?? 'School Dynamics') ?></span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false" style="font-size: small;">

                    <!-- Dashboard -->
                    <li class="nav-item">
                        <a href="/dashboard" class="nav-link">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>

                    <?php
                    $navModules = getNavigationModules();
                    foreach ($navModules as $module):
                        // Check module permission
                        $hasModulePermission = hasPermission($module['name'] . '.view') || Gate::hasRole('ADMIN');

                        if ($module['name'] === 'Academics' && Gate::hasRole('TEACHER')) {
                            $hasModulePermission = true;
                        }
                        if ($module['name'] === 'Finance' && Gate::hasRole('BURSAR')) {
                            $hasModulePermission = true;
                        }

                        if (!$hasModulePermission) continue;

                        // Icon mapping
                        $iconMap = [
                            'ti ti-school' => 'fas fa-user-graduate',
                            'ti ti-book' => 'fas fa-book',
                            'ti ti-currency-dollar' => 'fas fa-dollar-sign',
                            'ti ti-bus' => 'fas fa-bus',
                            'ti ti-tools-kitchen-2' => 'fas fa-utensils',
                            'ti ti-message' => 'fas fa-comments',
                            'ti ti-file-analytics' => 'fas fa-chart-line',
                            'ti ti-checkbox' => 'fas fa-tasks',
                        ];
                        $icon = $iconMap[$module['icon']] ?? 'fas fa-folder';
                    ?>

                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="nav-icon <?= $icon ?>"></i>
                            <p>
                                <?= e($module['display_name']) ?>
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>

                        <?php if (!empty($module['submodules'])): ?>
                        <ul class="nav nav-treeview">
                            <?php
                            // Group submodules for Students module
                            if ($module['name'] === 'Students'):
                                $applicantItems = array_filter($module['submodules'], fn($sm) =>
                                    strpos($sm['name'], 'Applicants') !== false ||
                                    strpos($sm['name'], 'Application') !== false
                                );
                                $studentItems = array_filter($module['submodules'], fn($sm) =>
                                    strpos($sm['name'], 'AllStudents') !== false ||
                                    strpos($sm['name'], 'AddStudent') !== false
                                );

                                if (!empty($applicantItems)):
                            ?>
                            <li class="nav-header">APPLICANTS</li>
                            <?php
                                foreach ($applicantItems as $submodule):
                                    $requiresWrite = strpos($submodule['name'], 'New') !== false ||
                                                    strpos($submodule['name'], 'Add') !== false;
                                    if ($requiresWrite && !hasPermission($module['name'] . '.write') && !Gate::hasRole('ADMIN')) continue;
                            ?>
                            <li class="nav-item">
                                <a href="<?= e($submodule['route']) ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p><?= e($submodule['display_name']) ?></p>
                                </a>
                            </li>
                            <?php endforeach; ?>
                            <?php endif; ?>

                            <?php if (!empty($studentItems)): ?>
                            <li class="nav-header">ENROLLED STUDENTS</li>
                            <?php
                                foreach ($studentItems as $submodule):
                                    $requiresWrite = strpos($submodule['name'], 'Add') !== false;
                                    if ($requiresWrite && !hasPermission($module['name'] . '.write') && !Gate::hasRole('ADMIN')) continue;
                            ?>
                            <li class="nav-item">
                                <a href="<?= e($submodule['route']) ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p><?= e($submodule['display_name']) ?></p>
                                </a>
                            </li>
                            <?php endforeach; ?>
                            <?php endif; ?>

                            <?php else: ?>
                            <?php
                                foreach ($module['submodules'] as $submodule):
                                    $requiresWrite = strpos($submodule['name'], 'New') !== false ||
                                                    strpos($submodule['name'], 'Add') !== false ||
                                                    strpos($submodule['name'], 'Create') !== false;
                                    if ($requiresWrite && !hasPermission($module['name'] . '.write') && !Gate::hasRole('ADMIN')) continue;
                            ?>
                            <li class="nav-item">
                                <a href="<?= e($submodule['route']) ?>" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p><?= e($submodule['display_name']) ?></p>
                                </a>
                            </li>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>

                    <!-- Settings -->
                    <?php if (Gate::hasRole('ADMIN') || Gate::hasRole('HEAD_TEACHER')): ?>
                    <li class="nav-item">
                        <a href="/settings" class="nav-link">
                            <i class="nav-icon fas fa-cog"></i>
                            <p>Settings</p>
                        </a>
                    </li>
                    <?php endif; ?>

                </ul>
            </nav>
        </div>
    </aside>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <!-- Content Header -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0"><?= $pageTitle ?? 'Dashboard' ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <?php if (isset($breadcrumbs) && !empty($breadcrumbs)): ?>
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="/dashboard">Home</a></li>
                            <?php foreach ($breadcrumbs as $index => $crumb): ?>
                                <?php if ($index === array_key_last($breadcrumbs)): ?>
                                    <li class="breadcrumb-item active"><?= e($crumb['label']) ?></li>
                                <?php else: ?>
                                    <li class="breadcrumb-item"><a href="<?= e($crumb['url']) ?>"><?= e($crumb['label']) ?></a></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ol>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">

                <!-- Flash Messages -->
                <?php if ($message = flash('success')): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <i class="icon fas fa-check"></i> <?= e($message) ?>
                </div>
                <?php endif; ?>

                <?php if ($message = flash('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <i class="icon fas fa-ban"></i> <?= e($message) ?>
                </div>
                <?php endif; ?>

                <?php if ($message = flash('info')): ?>
                <div class="alert alert-info alert-dismissible fade show">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <i class="icon fas fa-info"></i> <?= e($message) ?>
                </div>
                <?php endif; ?>

                <!-- Page Content -->
                <?php require $contentView ?? ''; ?>

            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="main-footer">
        <strong>Copyright &copy; <?= date('Y') ?> <?= e($_SESSION['tenant_name'] ?? 'School') ?>.</strong>
        All rights reserved.
        <div class="float-right d-none d-sm-inline-block">
            <b>Academic Year:</b> <?= $_SESSION['current_year'] ?? date('Y') ?>
        </div>
    </footer>

</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

</body>
</html>
