<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title><?= $pageTitle ?? 'Dashboard' ?> - <?= e($_SESSION['tenant_name'] ?? 'School') ?></title>

    <!-- CoreUI CSS -->
    <link href="https://cdn.jsdelivr.net/npm/@coreui/[email protected]/dist/css/coreui.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@coreui/[email protected]/dist/css/coreui-utilities.min.css" rel="stylesheet">

    <!-- CoreUI Icons -->
    <link href="https://cdn.jsdelivr.net/npm/@coreui/[email protected]/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@coreui/[email protected]/css/free.min.css" rel="stylesheet">

    <!-- Inter Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --cui-font-sans-serif: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        body {
            font-family: var(--cui-font-sans-serif);
            font-feature-settings: "cv03", "cv04", "cv11";
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Sidebar Customization */
        .sidebar {
            background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
        }

        .sidebar-brand {
            background: rgba(255, 255, 255, 0.05);
            font-weight: 700;
            font-size: 1.25rem;
            letter-spacing: -0.025em;
        }

        .sidebar-nav .nav-link {
            color: rgba(255, 255, 255, 0.8);
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .sidebar-nav .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .sidebar-nav .nav-link.active {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: white;
            font-weight: 600;
        }

        .sidebar-nav .nav-group-toggle {
            color: rgba(255, 255, 255, 0.8);
        }

        .sidebar-nav .nav-group-toggle:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .sidebar-nav .nav-group-items {
            background: rgba(0, 0, 0, 0.2);
        }

        .sidebar-nav .nav-group-items .nav-link {
            padding-left: 3rem;
            font-size: 0.875rem;
            position: relative;
        }

        .sidebar-nav .nav-group-items .nav-link::before {
            content: '';
            position: absolute;
            left: 2rem;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 4px;
            background-color: rgba(255, 255, 255, 0.4);
            border-radius: 1px;
        }

        .sidebar-nav .nav-group-items .nav-link:hover::before {
            background-color: rgba(255, 255, 255, 0.9);
            width: 6px;
            height: 6px;
        }

        /* Header */
        .header {
            background: white;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        /* Breadcrumb */
        .breadcrumb {
            background: transparent;
            padding: 0;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .breadcrumb-item a {
            color: #6c757d;
            text-decoration: none;
        }

        .breadcrumb-item a:hover {
            color: #0d6efd;
        }

        .breadcrumb-item.active {
            color: #212529;
            font-weight: 600;
        }

        /* Cards */
        .card {
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
        }

        .card:hover {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .card-hover-lift {
            transition: all 0.3s ease;
        }

        .card-hover-lift:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }

        /* Stats Cards */
        .stats-card {
            border-left: 4px solid;
        }

        .stats-card.border-primary {
            border-left-color: #0d6efd;
        }

        .stats-card.border-success {
            border-left-color: #198754;
        }

        .stats-card.border-warning {
            border-left-color: #ffc107;
        }

        .stats-card.border-info {
            border-left-color: #0dcaf0;
        }

        .stats-card h3 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .stats-card p {
            color: #6c757d;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0;
        }

        /* Tables */
        .table thead th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid #dee2e6;
        }

        .table tbody tr {
            transition: all 0.2s ease;
        }

        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
            transform: scale(1.005);
        }

        /* Badges */
        .badge {
            font-weight: 600;
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
        }

        /* Buttons */
        .btn {
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            border: none;
        }

        .btn-success {
            background: linear-gradient(135deg, #198754 0%, #146c43 100%);
            border: none;
        }

        /* Page Title */
        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: -0.025em;
            margin-bottom: 0;
        }

        /* Avatar */
        .avatar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.375rem;
            font-weight: 700;
        }

        .avatar-sm {
            width: 2rem;
            height: 2rem;
            font-size: 0.875rem;
        }

        .avatar-xl {
            width: 5rem;
            height: 5rem;
            font-size: 1.5rem;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar sidebar-dark sidebar-fixed" id="sidebar">
        <div class="sidebar-brand d-md-flex">
            <svg class="sidebar-brand-full" width="118" height="46" alt="CoreUI Logo">
                <use xlink:href="assets/brand/coreui.svg#full"></use>
            </svg>
            <svg class="sidebar-brand-narrow" width="46" height="46" alt="CoreUI Logo">
                <use xlink:href="assets/brand/coreui.svg#signet"></use>
            </svg>
        </div>

        <ul class="sidebar-nav" data-coreui="navigation" data-simplebar="">
            <!-- Dashboard -->
            <li class="nav-item">
                <a class="nav-link" href="/dashboard">
                    <svg class="nav-icon">
                        <use xlink:href="@coreui/icons/sprites/free.svg#cil-speedometer"></use>
                    </svg> Dashboard
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

                // Get CoreUI icon
                $iconMap = [
                    'ti ti-school' => 'cil-education',
                    'ti ti-book' => 'cil-book',
                    'ti ti-currency-dollar' => 'cil-dollar',
                    'ti ti-bus' => 'cil-truck',
                    'ti ti-tools-kitchen-2' => 'cil-restaurant',
                    'ti ti-message' => 'cil-chat-bubble',
                    'ti ti-file-analytics' => 'cil-chart-line',
                    'ti ti-checkbox' => 'cil-task',
                ];
                $cuiIcon = $iconMap[$module['icon']] ?? 'cil-folder';
            ?>

            <li class="nav-group">
                <a class="nav-link nav-group-toggle" href="#">
                    <svg class="nav-icon">
                        <use xlink:href="https://cdn.jsdelivr.net/npm/@coreui/icons/sprites/free.svg#<?= $cuiIcon ?>"></use>
                    </svg> <?= e($module['display_name']) ?>
                </a>

                <?php if (!empty($module['submodules'])): ?>
                <ul class="nav-group-items">
                    <?php
                    // Group submodules by section for Students module
                    if ($module['name'] === 'Students'):
                        $applicantItems = array_filter($module['submodules'], fn($sm) => strpos($sm['name'], 'Applicants') !== false || strpos($sm['name'], 'Application') !== false);
                        $studentItems = array_filter($module['submodules'], fn($sm) => strpos($sm['name'], 'AllStudents') !== false || strpos($sm['name'], 'AddStudent') !== false);

                        if (!empty($applicantItems)):
                    ?>
                    <li class="nav-item">
                        <span class="nav-link disabled text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.1em; color: rgba(255,255,255,0.4);">Applicants</span>
                    </li>
                    <?php
                        foreach ($applicantItems as $submodule):
                            $requiresWrite = strpos($submodule['name'], 'New') !== false || strpos($submodule['name'], 'Add') !== false;
                            if ($requiresWrite && !hasPermission($module['name'] . '.write') && !Gate::hasRole('ADMIN')) continue;
                    ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e($submodule['route']) ?>">
                            <?= e($submodule['display_name']) ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if (!empty($studentItems)): ?>
                    <li class="nav-item">
                        <span class="nav-link disabled text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.1em; color: rgba(255,255,255,0.4);">Enrolled Students</span>
                    </li>
                    <?php
                        foreach ($studentItems as $submodule):
                            $requiresWrite = strpos($submodule['name'], 'Add') !== false;
                            if ($requiresWrite && !hasPermission($module['name'] . '.write') && !Gate::hasRole('ADMIN')) continue;
                    ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e($submodule['route']) ?>">
                            <?= e($submodule['display_name']) ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                    <?php endif; ?>

                    <?php else: ?>
                    <?php
                        // Regular modules - just list submodules
                        foreach ($module['submodules'] as $submodule):
                            $requiresWrite = strpos($submodule['name'], 'New') !== false || strpos($submodule['name'], 'Add') !== false || strpos($submodule['name'], 'Create') !== false;
                            if ($requiresWrite && !hasPermission($module['name'] . '.write') && !Gate::hasRole('ADMIN')) continue;
                    ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e($submodule['route']) ?>">
                            <?= e($submodule['display_name']) ?>
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
                <a class="nav-link" href="/settings">
                    <svg class="nav-icon">
                        <use xlink:href="https://cdn.jsdelivr.net/npm/@coreui/icons/sprites/free.svg#cil-settings"></use>
                    </svg> Settings
                </a>
            </li>
            <?php endif; ?>
        </ul>

        <button class="sidebar-toggler" type="button" data-coreui-toggle="unfoldable"></button>
    </div>

    <!-- Main content -->
    <div class="wrapper d-flex flex-column min-vh-100 bg-light">
        <!-- Header -->
        <header class="header header-sticky mb-4">
            <div class="container-fluid">
                <button class="header-toggler px-md-0 me-md-3" type="button" onclick="coreui.Sidebar.getInstance(document.querySelector('#sidebar')).toggle()">
                    <svg class="icon icon-lg">
                        <use xlink:href="https://cdn.jsdelivr.net/npm/@coreui/icons/sprites/free.svg#cil-menu"></use>
                    </svg>
                </button>

                <ul class="header-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link py-0" data-coreui-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                            <div class="avatar avatar-md">
                                <?= strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 2)) ?>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end pt-0">
                            <div class="dropdown-header bg-light py-2">
                                <div class="fw-semibold"><?= e($_SESSION['full_name'] ?? 'User') ?></div>
                                <div class="text-muted small"><?= e($_SESSION['email'] ?? '') ?></div>
                            </div>
                            <a class="dropdown-item" href="/profile">
                                <svg class="icon me-2">
                                    <use xlink:href="https://cdn.jsdelivr.net/npm/@coreui/icons/sprites/free.svg#cil-user"></use>
                                </svg> Profile
                            </a>
                            <a class="dropdown-item" href="/settings">
                                <svg class="icon me-2">
                                    <use xlink:href="https://cdn.jsdelivr.net/npm/@coreui/icons/sprites/free.svg#cil-settings"></use>
                                </svg> Settings
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="/logout">
                                <svg class="icon me-2">
                                    <use xlink:href="https://cdn.jsdelivr.net/npm/@coreui/icons/sprites/free.svg#cil-account-logout"></use>
                                </svg> Logout
                            </a>
                        </div>
                    </li>
                </ul>
            </div>
        </header>

        <!-- Body -->
        <div class="body flex-grow-1 px-3">
            <div class="container-lg">
                <!-- Breadcrumb and Page Title -->
                <?php if (isset($breadcrumbs) && !empty($breadcrumbs)): ?>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                        <?php foreach ($breadcrumbs as $index => $crumb): ?>
                            <?php if ($index === array_key_last($breadcrumbs)): ?>
                                <li class="breadcrumb-item active" aria-current="page"><?= e($crumb['label']) ?></li>
                            <?php else: ?>
                                <li class="breadcrumb-item"><a href="<?= e($crumb['url']) ?>"><?= e($crumb['label']) ?></a></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ol>
                </nav>
                <?php endif; ?>

                <h1 class="page-title mb-4"><?= $pageTitle ?? 'Dashboard' ?></h1>

                <!-- Flash Messages -->
                <?php if ($message = flash('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <svg class="icon flex-shrink-0 me-2">
                        <use xlink:href="https://cdn.jsdelivr.net/npm/@coreui/icons/sprites/free.svg#cil-check-circle"></use>
                    </svg>
                    <?= e($message) ?>
                    <button type="button" class="btn-close" data-coreui-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <?php if ($message = flash('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <svg class="icon flex-shrink-0 me-2">
                        <use xlink:href="https://cdn.jsdelivr.net/npm/@coreui/icons/sprites/free.svg#cil-warning"></use>
                    </svg>
                    <?= e($message) ?>
                    <button type="button" class="btn-close" data-coreui-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <?php if ($message = flash('info')): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <svg class="icon flex-shrink-0 me-2">
                        <use xlink:href="https://cdn.jsdelivr.net/npm/@coreui/icons/sprites/free.svg#cil-info"></use>
                    </svg>
                    <?= e($message) ?>
                    <button type="button" class="btn-close" data-coreui-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <!-- Page Content -->
                <?php require $contentView ?? ''; ?>
            </div>
        </div>

        <!-- Footer -->
        <footer class="footer">
            <div class="container-lg">
                <div class="ms-auto">
                    <span class="text-muted">Academic Year: <strong><?= $_SESSION['current_year'] ?? date('Y') ?></strong></span>
                    <span class="text-muted mx-2">|</span>
                    <span class="text-muted">&copy; <?= date('Y') ?> <?= e($_SESSION['tenant_name'] ?? 'School') ?></span>
                    <span class="text-muted mx-2">|</span>
                    <span class="text-muted">Powered by SchoolDynamics</span>
                </div>
            </div>
        </footer>
    </div>

    <!-- CoreUI and necessary plugins-->
    <script src="https://cdn.jsdelivr.net/npm/@coreui/[email protected]/dist/js/coreui.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/[email protected]/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simplebar@latest/dist/simplebar.min.js"></script>
</body>
</html>
