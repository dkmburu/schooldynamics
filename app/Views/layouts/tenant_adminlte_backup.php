<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $pageTitle ?? 'Dashboard' ?> | <?= e($_SESSION['tenant_name'] ?? 'School') ?></title>

    <!-- Segoe UI Font -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- AdminLTE Base -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">

    <!-- AJAX Router Styles -->
    <link rel="stylesheet" href="/assets/css/ajax-router.css">

    <style>
        /* ============================================
           MICROSOFT/TABLER BLUE THEME
           ============================================ */
        :root {
            --primary: #0078d4;
            --primary-dark: #106ebe;
            --primary-light: #deecf9;
            --success: #107c10;
            --warning: #ffb900;
            --danger: #d13438;
            --gray-50: #faf9f8;
            --gray-100: #f3f2f1;
            --gray-200: #edebe9;
            --gray-300: #d2d0ce;
            --gray-400: #a19f9d;
            --gray-500: #605e5c;
            --gray-600: #484644;
            --gray-700: #323130;
            --gray-800: #252423;
            --gray-900: #1b1a19;
            --text-xs: 11px;
            --text-sm: 12px;
            --text-base: 14px;
            --text-lg: 16px;
        }

        /* Base Typography */
        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, 'Inter', sans-serif;
            font-size: var(--text-base);
            color: var(--gray-700);
            background-color: var(--gray-100);
        }

        /* Sidebar - Dark clean style */
        .main-sidebar {
            background: var(--gray-900) !important;
        }

        .sidebar-dark-primary .sidebar {
            background: transparent !important;
        }

        .brand-link {
            font-weight: 600;
            font-size: var(--text-lg);
            border-bottom: 1px solid rgba(255,255,255,0.08) !important;
            padding: 14px 16px !important;
        }

        .brand-text {
            font-size: var(--text-base) !important;
        }

        /* Sidebar Navigation */
        .sidebar-dark-primary .nav-sidebar .nav-link {
            color: rgba(255,255,255,0.75) !important;
            font-size: var(--text-sm) !important;
            padding: 10px 12px !important;
            margin: 1px 8px !important;
            border-radius: 4px;
            transition: background-color 0.15s ease;
        }

        .sidebar-dark-primary .nav-sidebar .nav-link:hover {
            background-color: rgba(255,255,255,0.06) !important;
            color: #fff !important;
        }

        .sidebar-dark-primary .nav-sidebar .nav-link.active {
            background-color: var(--primary) !important;
            color: #fff !important;
            font-weight: 500;
        }

        .sidebar-dark-primary .nav-sidebar > .nav-item.menu-open > .nav-link {
            background-color: rgba(255,255,255,0.04) !important;
            color: #fff !important;
        }

        .main-sidebar .nav-icon {
            font-size: var(--text-sm) !important;
            width: 20px !important;
            margin-right: 10px !important;
            opacity: 0.8;
        }

        .main-sidebar .nav-sidebar .nav-treeview {
            background: rgba(0,0,0,0.15);
            margin: 4px 8px;
            border-radius: 4px;
            padding: 4px 0;
        }

        .main-sidebar .nav-sidebar .nav-treeview > .nav-item > .nav-link {
            font-size: var(--text-sm) !important;
            padding: 8px 12px 8px 36px !important;
            margin: 0 !important;
            border-radius: 0;
        }

        .main-sidebar .nav-sidebar .nav-header {
            font-size: 10px !important;
            font-weight: 600;
            letter-spacing: 0.5px;
            color: rgba(255,255,255,0.4) !important;
            padding: 12px 12px 4px !important;
            text-transform: uppercase;
        }

        /* Header */
        .main-header {
            background: #fff !important;
            border-bottom: 1px solid var(--gray-200) !important;
            box-shadow: none !important;
        }

        .main-header .nav-link {
            color: var(--gray-600) !important;
            font-size: var(--text-base);
        }

        /* Content Area */
        .content-wrapper {
            background: var(--gray-100) !important;
        }

        .content-header h1 {
            font-size: 20px !important;
            font-weight: 600;
            color: var(--gray-800);
        }

        /* Cards */
        .card {
            border: 1px solid var(--gray-200);
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            background: #fff;
        }

        .card:hover {
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        }

        .card-header {
            background: #fff;
            border-bottom: 1px solid var(--gray-200);
            padding: 16px 20px;
            font-weight: 600;
            font-size: var(--text-base);
        }

        .card-body {
            padding: 20px;
            font-size: var(--text-base);
        }

        /* Stats Cards */
        .small-box {
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            border: 1px solid var(--gray-200);
            position: relative;
            overflow: hidden;
        }

        .small-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--primary);
        }

        .small-box.bg-info { background: #fff !important; }
        .small-box.bg-info::before { background: var(--primary); }
        .small-box.bg-success { background: #fff !important; }
        .small-box.bg-success::before { background: var(--success); }
        .small-box.bg-warning { background: #fff !important; }
        .small-box.bg-warning::before { background: var(--warning); }
        .small-box.bg-danger { background: #fff !important; }
        .small-box.bg-danger::before { background: var(--danger); }

        .small-box .inner h3 {
            font-size: 32px;
            font-weight: 600;
            color: var(--gray-800);
        }

        .small-box .inner p {
            font-size: var(--text-xs);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--gray-500);
        }

        .small-box .icon {
            color: var(--gray-200) !important;
        }

        .small-box .small-box-footer {
            background: var(--gray-50);
            color: var(--gray-600);
            font-size: var(--text-sm);
        }

        /* Info Box (Dashboard stats) */
        .info-box {
            border: 1px solid var(--gray-200);
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            min-height: auto;
            padding: 20px;
        }

        .info-box .info-box-icon {
            width: 48px;
            height: 48px;
            font-size: 24px;
            line-height: 48px;
            border-radius: 6px;
        }

        .info-box-content {
            padding: 0 0 0 16px;
        }

        .info-box-text {
            font-size: var(--text-xs) !important;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--gray-500);
        }

        .info-box-number {
            font-size: 28px !important;
            font-weight: 600;
            color: var(--gray-800);
        }

        /* Buttons - Compact style */ 
        .btn {
            font-weight: 500;
            font-size: var(--text-sm);
            padding: 5px 12px;
            border-radius: 4px;
            line-height: 1.4;
        }

        .btn-sm {
            font-size: var(--text-xs);
            padding: 4px 10px;
        }

        .btn-lg {
            font-size: var(--text-base);
            padding: 8px 16px;
        }

        .btn-primary {
            background: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .btn-secondary {
            background: var(--gray-100);
            border-color: var(--gray-300);
            color: var(--gray-700);
        }

        .btn-secondary:hover {
            background: var(--gray-200);
            border-color: var(--gray-400);
            color: var(--gray-800);
        }

        .btn-success {
            background: var(--success);
            border-color: var(--success);
        }

        .btn-danger {
            background: var(--danger);
            border-color: var(--danger);
        }

        .btn-outline-primary {
            color: var(--primary);
            border-color: var(--primary);
            background: transparent;
        }

        .btn-outline-primary:hover {
            background: var(--primary);
            color: #fff;
        }

        /* Tables */
        .table thead th {
            font-size: var(--text-xs);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--gray-600);
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
            padding: 12px 16px;
        }

        .table td {
            font-size: var(--text-base);
            color: var(--gray-700);
            padding: 12px 16px;
            vertical-align: middle;
        }

        /* Tabs - Modern underline style */
        .nav-tabs {
            border-bottom: 1px solid var(--gray-200);
        }

        .nav-tabs .nav-link {
            font-size: var(--text-base) !important;
            font-weight: 500;
            color: var(--gray-500);
            padding: 12px 20px !important;
            border: none !important;
            border-radius: 0 !important;
            position: relative;
            background: transparent !important;
        }

        .nav-tabs .nav-link::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 2px;
            background: transparent;
        }

        .nav-tabs .nav-link:hover {
            color: var(--primary);
            border-color: transparent !important;
        }

        .nav-tabs .nav-link.active {
            color: var(--primary) !important;
            font-weight: 600;
            background: transparent !important;
            border-color: transparent !important;
        }

        .nav-tabs .nav-link.active::after {
            background: var(--primary);
        }

        .nav-tabs .nav-link i {
            font-size: var(--text-base) !important;
            margin-right: 6px;
        }

        .nav-tabs .badge {
            font-size: 10px !important;
            padding: 2px 6px !important;
        }

        /* Forms */
        .form-control, .form-select, select.form-control {
            font-size: var(--text-base);
            padding: 8px 12px;
            border: 1px solid var(--gray-300);
            border-radius: 6px;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(0,120,212,0.15);
        }

        .form-label, label {
            font-size: var(--text-sm);
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 6px;
        }

        /* Badges */
        .badge-dot {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0;
            background: transparent !important;
            font-weight: 500;
            font-size: var(--text-base);
            color: var(--gray-600);
        }

        .badge-dot::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .badge-dot.badge-primary::before { background: var(--primary); }
        .badge-dot.badge-success::before { background: var(--success); }
        .badge-dot.badge-warning::before { background: var(--warning); }
        .badge-dot.badge-danger::before { background: var(--danger); }
        .badge-dot.badge-info::before { background: var(--primary); }
        .badge-dot.badge-secondary::before { background: var(--gray-400); }

        /* List groups */
        .list-group-item {
            border: none;
            border-bottom: 1px solid var(--gray-200);
            padding: 14px 20px;
            font-size: var(--text-base);
        }

        .list-group-item:last-child {
            border-bottom: none;
        }

        .list-group-item:hover {
            background: var(--gray-50);
        }

        /* Footer */
        .main-footer {
            font-size: var(--text-sm);
            color: var(--gray-500);
            border-top: 1px solid var(--gray-200);
            background: #fff;
        }

        /* Breadcrumb */
        .breadcrumb {
            font-size: var(--text-sm);
            background: transparent;
        }

        .breadcrumb-item a {
            color: var(--gray-500);
        }

        .breadcrumb-item.active {
            color: var(--gray-700);
        }

        /* Dropdown in sidebar */
        .main-sidebar .dropdown-menu {
            background: var(--gray-800);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .main-sidebar .dropdown-item {
            color: rgba(255,255,255,0.8);
            font-size: var(--text-sm);
        }

        .main-sidebar .dropdown-item:hover {
            background: rgba(255,255,255,0.1);
            color: #fff;
        }

        .main-sidebar .dropdown-header {
            color: rgba(255,255,255,0.5);
            font-size: 10px;
            text-transform: uppercase;
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
                    <a href="/logout" class="dropdown-item" data-no-ajax="true">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                </div>
            </li>
        </ul>
    </nav>

    <!-- Main Sidebar -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <?php
        // Get school name from school_profile
        $schoolName = 'School Dynamics';
        try {
            $pdo = Database::getTenantConnection();
            $stmt = $pdo->query("SELECT school_name FROM school_profile LIMIT 1");
            $profile = $stmt->fetch();
            if ($profile && !empty($profile['school_name'])) {
                $schoolName = $profile['school_name'];
            }
        } catch (Exception $e) {
            // Use default if table doesn't exist
        }

        // Get campuses and current campus
        $campuses = [];
        $currentCampus = null;
        try {
            $pdo = Database::getTenantConnection();
            $stmt = $pdo->query("SELECT * FROM campuses WHERE is_active = 1 ORDER BY sort_order, campus_name");
            $campuses = $stmt->fetchAll();

            // Get current campus from session or default to main
            $currentCampusId = $_SESSION['current_campus_id'] ?? null;
            if (!$currentCampusId && !empty($campuses)) {
                // Set default to main campus or first campus
                foreach ($campuses as $campus) {
                    if ($campus['is_main']) {
                        $currentCampusId = $campus['id'];
                        break;
                    }
                }
                if (!$currentCampusId) {
                    $currentCampusId = $campuses[0]['id'];
                }
                $_SESSION['current_campus_id'] = $currentCampusId;
            }

            // Find current campus details
            foreach ($campuses as $campus) {
                if ($campus['id'] == $currentCampusId) {
                    $currentCampus = $campus;
                    break;
                }
            }
        } catch (Exception $e) {
            // Silently fail if campuses table doesn't exist yet
        }
        ?>

        <!-- Brand Logo with Campus Selector -->
        <div class="brand-link brand-link-block">
            <a href="/dashboard" class="brand-link-inner">
                <span class="brand-text font-weight-light brand-text-light"><?= e($schoolName) ?></span>
            </a>

            <!-- Campus Selector (directly below school name) -->
            <?php if (count($campuses) > 1): ?>
            <div class="campus-dropdown-container">
                <div class="dropdown w-100">
                    <button type="button" class="btn btn-primary btn-sm btn-block dropdown-toggle campus-dropdown-btn" id="campusDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-building mr-1"></i>
                        <span><?= e($currentCampus['campus_name'] ?? 'Select Campus') ?></span>
                    </button>
                    <div class="dropdown-menu w-100 dropdown-menu-full" aria-labelledby="campusDropdown">
                        <h6 class="dropdown-header">Switch Campus</h6>
                        <div class="dropdown-divider"></div>
                        <?php foreach ($campuses as $campus): ?>
                            <a href="/switch-campus?campus_id=<?= $campus['id'] ?>"
                               class="dropdown-item <?= $campus['id'] == $currentCampusId ? 'active' : '' ?>"
                               data-no-ajax="true">
                                <?php if ($campus['id'] == $currentCampusId): ?>
                                    <i class="fas fa-check mr-2 text-success"></i>
                                <?php else: ?>
                                    <i class="far fa-circle mr-2"></i>
                                <?php endif; ?>
                                <?= e($campus['campus_name']) ?>
                                <?php if ($campus['is_main']): ?>
                                    <span class="badge badge-primary badge-sm float-right">Main</span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                        <?php if (Gate::hasRole('ADMIN')): ?>
                            <div class="dropdown-divider"></div>
                            <a href="/switch-campus?campus_id=all" class="dropdown-item text-muted" data-no-ajax="true">
                                <i class="fas fa-globe mr-2"></i> All Campuses
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="sidebar">

            <!-- Sidebar Menu -->
            <nav class="<?= count($campuses) > 1 ? '' : 'mt-2' ?>">
                <ul class="nav nav-pills nav-sidebar flex-column"  data-widget="treeview" role="menu" data-accordion="false">

                    <!-- Dashboard -->
                    <li class="nav-item">
                        <a href="/dashboard" class="nav-link">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>

                    <!-- My Tasks (Workflow) -->
                    <?php
                    $taskCounts = ['pending' => 0, 'claimed' => 0, 'overdue' => 0];
                    $totalPendingTasks = 0;
                    if (isset($_SESSION['user_id'])) {
                        try {
                            // Load the WorkflowTask model if not already loaded
                            if (!class_exists('WorkflowTask')) {
                                $modelPath = __DIR__ . '/../../Models/WorkflowTask.php';
                                if (file_exists($modelPath)) {
                                    require_once $modelPath;
                                }
                            }
                            if (class_exists('WorkflowTask')) {
                                $taskCounts = WorkflowTask::getCountsForUser($_SESSION['user_id']);
                                $totalPendingTasks = ($taskCounts['pending'] ?? 0) + ($taskCounts['claimed'] ?? 0);
                            }
                        } catch (Exception $e) {
                            // Silently fail - tasks count is optional
                        }
                    }
                    ?>
                    <li class="nav-item">
                        <a href="/tasks" class="nav-link">
                            <i class="nav-icon fas fa-tasks"></i>
                            <p>
                                My Tasks
                                <?php if ($totalPendingTasks > 0): ?>
                                <span class="badge badge-warning right"><?= $totalPendingTasks ?></span>
                                <?php endif; ?>
                            </p>
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
                                $applicantItems = array_filter($module['submodules'], function($sm) {
                                    return strpos($sm['name'], 'Applicants') !== false ||
                                           strpos($sm['name'], 'Application') !== false ||
                                           strpos($sm['name'], 'Screening') !== false;
                                });
                                $studentItems = array_filter($module['submodules'], function($sm) {
                                    return strpos($sm['name'], 'AllStudents') !== false ||
                                           strpos($sm['name'], 'AddStudent') !== false;
                                });

                                if (!empty($applicantItems)):
                            ?>
                            <li class="nav-header">APPLICANTS</li>
                            <?php
                                foreach ($applicantItems as $submodule):
                                    $requiresWrite = strpos($submodule['name'], 'New') !== false ||
                                                    strpos($submodule['name'], 'Add') !== false;
                                    if ($requiresWrite && !hasPermission($module['name'] . '.write') && !Gate::hasRole('ADMIN')) continue;
                                    $subIcon = convertSubmoduleIcon($submodule['icon'] ?? '');
                            ?>
                            <li class="nav-item">
                                <a href="<?= e($submodule['route']) ?>" class="nav-link">
                                    <i class="<?= $subIcon ?> nav-icon"></i>
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
                                    $subIcon = convertSubmoduleIcon($submodule['icon'] ?? '');
                            ?>
                            <li class="nav-item">
                                <a href="<?= e($submodule['route']) ?>" class="nav-link">
                                    <i class="<?= $subIcon ?> nav-icon"></i>
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
                                    $subIcon = convertSubmoduleIcon($submodule['icon'] ?? '');
                            ?>
                            <li class="nav-item">
                                <a href="<?= e($submodule['route']) ?>" class="nav-link">
                                    <i class="<?= $subIcon ?> nav-icon"></i>
                                    <p><?= e($submodule['display_name']) ?></p>
                                </a>
                            </li>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>

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

<!-- AJAX Router -->
<script src="/assets/js/ajax-router.js"></script>

<!-- Persistent Tabs -->
<script src="/assets/js/persistent-tabs.js"></script>

<!-- Applicant Profile Functions -->
<script src="/assets/js/applicant-profile.js"></script>

</body>
</html>
