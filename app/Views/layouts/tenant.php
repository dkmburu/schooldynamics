<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title><?= $pageTitle ?? 'Dashboard' ?> | <?= e($_SESSION['tenant_name'] ?? 'School') ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="alternate icon" href="/favicon.ico">

    <!-- Tabler Core CSS (Local) -->
    <link href="/vendor/tabler/css/tabler.min.css" rel="stylesheet"/>

    <!-- Tabler Icons (local) -->
    <link href="/vendor/tabler/icons/tabler-icons.min.css" rel="stylesheet"/>

    <!-- System Fonts (no external CDN - faster load) -->

    <!-- Custom Theme -->
    <link rel="stylesheet" href="/css/professional-theme.css">

    <!-- AJAX Router Styles -->
    <link rel="stylesheet" href="/assets/css/ajax-router.css">

    <style>
        :root {
            --tblr-font-sans-serif: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            --tblr-primary: #0078d4;
            --tblr-primary-rgb: 0, 120, 212;
        }
        body {
            font-feature-settings: "cv03", "cv04", "cv11";
        }
        /* Campus selector styling (dark theme) */
        .campus-selector {
            padding: 0.5rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .campus-selector .btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
        }
        .campus-selector .btn:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
        }
        .campus-selector .dropdown-menu {
            background: #1e293b !important;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .campus-selector .dropdown-header {
            color: rgba(255, 255, 255, 0.5) !important;
        }
        .campus-selector .dropdown-item {
            color: rgba(255, 255, 255, 0.7) !important;
        }
        .campus-selector .dropdown-item:hover {
            background: rgba(255, 255, 255, 0.1) !important;
            color: #ffffff !important;
        }
        .campus-selector .dropdown-item.active {
            background: rgba(0, 120, 212, 0.3) !important;
            color: #60a5fa !important;
        }
        /* Nav headers */
        .nav-link-header {
            font-size: 0.625rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: rgba(255, 255, 255, 0.4);
            padding: 1rem 1rem 0.25rem;
        }
        /* Dropdown submenu */
        .navbar-vertical .dropdown-menu {
            position: static !important;
            transform: none !important;
            border: none;
            box-shadow: none;
            padding: 0;
            background: transparent;
        }
        .navbar-vertical .dropdown-menu .dropdown-item {
            padding-left: 2.5rem;
        }

        /* Dropdown item hover for dark sidebar */
        .navbar-dark .dropdown-menu .dropdown-item:hover {
            background-color: rgba(255, 255, 255, 0.1) !important;
            color: #ffffff !important;
        }
    </style>
</head>
<body class="layout-fluid">
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
    $currentCampusId = null;
    try {
        $pdo = Database::getTenantConnection();
        $stmt = $pdo->query("SELECT * FROM campuses WHERE is_active = 1 ORDER BY sort_order, campus_name");
        $campuses = $stmt->fetchAll();

        // Get current campus from session or default to main
        $currentCampusId = $_SESSION['current_campus_id'] ?? null;
        if (!$currentCampusId && !empty($campuses)) {
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

        foreach ($campuses as $campus) {
            if ($campus['id'] == $currentCampusId) {
                $currentCampus = $campus;
                break;
            }
        }
    } catch (Exception $e) {
        // Silently fail if campuses table doesn't exist yet
    }

    // Get task counts
    $taskCounts = ['pending' => 0, 'claimed' => 0, 'overdue' => 0];
    $totalPendingTasks = 0;
    if (isset($_SESSION['user_id'])) {
        try {
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
            // Silently fail
        }
    }
    ?>

    <div class="page">
        <!-- Sidebar -->
        <aside class="navbar navbar-vertical navbar-expand-lg navbar-dark">
            <div class="container-fluid">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu" aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <h1 class="navbar-brand">
                    <a href="/dashboard">
                        <span class="text-primary">School</span><span class="text-white">Dynamics</span>
                    </a>
                </h1>

                <div class="navbar-nav flex-row d-lg-none">
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown">
                            <span class="avatar avatar-sm bg-primary text-white"><?= strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1)) ?></span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="/profile"><i class="ti ti-user me-2"></i>Profile</a>
                            <a class="dropdown-item" href="/logout" data-no-ajax="true"><i class="ti ti-logout me-2"></i>Logout</a>
                        </div>
                    </div>
                </div>

                <div class="collapse navbar-collapse" id="sidebar-menu">
                    <!-- Campus Selector -->
                    <?php if (count($campuses) > 1): ?>
                    <div class="campus-selector">
                        <div class="dropdown w-100">
                            <button class="btn btn-primary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="ti ti-building me-2"></i>
                                <?= e($currentCampus['campus_name'] ?? 'Select Campus') ?>
                            </button>
                            <div class="dropdown-menu w-100">
                                <span class="dropdown-header">Switch Campus</span>
                                <?php foreach ($campuses as $campus): ?>
                                <a href="/switch-campus?campus_id=<?= $campus['id'] ?>" class="dropdown-item <?= $campus['id'] == $currentCampusId ? 'active' : '' ?>" data-no-ajax="true">
                                    <?php if ($campus['id'] == $currentCampusId): ?>
                                        <i class="ti ti-check me-2 text-success"></i>
                                    <?php else: ?>
                                        <i class="ti ti-circle me-2"></i>
                                    <?php endif; ?>
                                    <?= e($campus['campus_name']) ?>
                                    <?php if ($campus['is_main']): ?>
                                        <span class="badge bg-primary ms-auto">Main</span>
                                    <?php endif; ?>
                                </a>
                                <?php endforeach; ?>
                                <?php if (Gate::hasRole('ADMIN')): ?>
                                <div class="dropdown-divider"></div>
                                <a href="/switch-campus?campus_id=all" class="dropdown-item text-muted" data-no-ajax="true">
                                    <i class="ti ti-world me-2"></i> All Campuses
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <ul class="navbar-nav pt-lg-3">
                        <!-- Dashboard -->
                        <li class="nav-item">
                            <a class="nav-link" href="/dashboard">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <i class="ti ti-home"></i>
                                </span>
                                <span class="nav-link-title">Dashboard</span>
                            </a>
                        </li>

                        <!-- My Tasks -->
                        <li class="nav-item">
                            <a class="nav-link" href="/tasks">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <i class="ti ti-checkbox"></i>
                                </span>
                                <span class="nav-link-title">My Tasks</span>
                                <?php if ($totalPendingTasks > 0): ?>
                                <span class="badge bg-yellow text-yellow-fg"><?= $totalPendingTasks ?></span>
                                <?php endif; ?>
                            </a>
                        </li>

                        <?php
                        $navModules = getNavigationModules();
                        foreach ($navModules as $module):
                            // Check module permission using new RBAC
                            // Gate::canAccessModule checks if user has ANY permission for submodules in this module
                            // ADMIN role automatically bypasses all checks
                            $hasModulePermission = Gate::canAccessModule($module['name']);

                            if (!$hasModulePermission) continue;

                            // Get Tabler icon from database or map
                            $icon = $module['icon'] ?? 'ti ti-folder';
                            // Ensure it's a Tabler icon format
                            if (strpos($icon, 'ti ') !== 0 && strpos($icon, 'fas ') !== 0) {
                                $icon = 'ti ti-' . str_replace(['ti-', 'ti '], '', $icon);
                            }
                            // Convert Font Awesome to Tabler if needed
                            if (strpos($icon, 'fas ') === 0) {
                                $faToTabler = [
                                    'fas fa-users' => 'ti ti-users',
                                    'fas fa-user-graduate' => 'ti ti-school',
                                    'fas fa-book' => 'ti ti-book',
                                    'fas fa-dollar-sign' => 'ti ti-currency-dollar',
                                    'fas fa-bus' => 'ti ti-bus',
                                    'fas fa-utensils' => 'ti ti-tools-kitchen-2',
                                    'fas fa-comments' => 'ti ti-messages',
                                    'fas fa-chart-line' => 'ti ti-chart-bar',
                                    'fas fa-tasks' => 'ti ti-checkbox',
                                    'fas fa-cog' => 'ti ti-settings',
                                ];
                                $icon = $faToTabler[$icon] ?? 'ti ti-folder';
                            }

                            $hasSubmodules = !empty($module['submodules']);
                        ?>

                        <li class="nav-item <?= $hasSubmodules ? 'dropdown' : '' ?>">
                            <?php if ($hasSubmodules): ?>
                            <a class="nav-link dropdown-toggle" href="#navbar-<?= strtolower($module['name']) ?>" data-bs-toggle="dropdown" data-bs-auto-close="false" role="button" aria-expanded="false">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <i class="<?= $icon ?>"></i>
                                </span>
                                <span class="nav-link-title"><?= e($module['display_name']) ?></span>
                            </a>
                            <div class="dropdown-menu">
                                <?php
                                    foreach ($module['submodules'] as $submodule):
                                        // Check submodule view permission using new RBAC format
                                        // Permission name format: Submodule.Name.view (e.g., Finance.Dashboard.view)
                                        if (!Gate::canView($submodule['name'])) continue;

                                        // Check if this is a create/add action requiring modify permission
                                        $requiresModify = strpos($submodule['name'], 'New') !== false ||
                                                         strpos($submodule['name'], 'Add') !== false ||
                                                         strpos($submodule['name'], 'Create') !== false;
                                        if ($requiresModify && !Gate::canModify($submodule['name'])) continue;

                                        $subIcon = convertSubmoduleIconToTabler($submodule['icon'] ?? '');
                                ?>
                                <a class="dropdown-item" href="<?= e($submodule['route']) ?>">
                                    <i class="<?= $subIcon ?> me-2"></i><?= e($submodule['display_name']) ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <a class="nav-link" href="<?= e($module['submodules'][0]['route'] ?? '#') ?>">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <i class="<?= $icon ?>"></i>
                                </span>
                                <span class="nav-link-title"><?= e($module['display_name']) ?></span>
                            </a>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </aside>

        <!-- Page Wrapper -->
        <div class="page-wrapper">
            <!-- Top Header -->
            <header class="navbar navbar-expand-md d-none d-lg-flex d-print-none">
                <div class="container-xl">
                    <!-- School Name (Left) -->
                    <div class="d-flex align-items-center">
                        <span class="text-muted fw-medium"><?= e($schoolName) ?></span>
                    </div>

                    <!-- Spacer -->
                    <div class="flex-grow-1"></div>

                    <!-- User Dropdown (Right) -->
                    <div class="navbar-nav flex-row align-items-center">
                        <div class="nav-item dropdown">
                            <a href="#" class="nav-link d-flex lh-1 text-reset p-0 align-items-center" data-bs-toggle="dropdown">
                                <span class="avatar avatar-sm bg-primary text-white"><?= strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1)) ?></span>
                                <div class="d-none d-xl-block ps-2">
                                    <div><?= e($_SESSION['full_name'] ?? 'User') ?></div>
                                    <div class="mt-1 small text-muted"><?= e($_SESSION['role_name'] ?? 'User') ?></div>
                                </div>
                                <i class="ti ti-chevron-down ms-2 text-muted"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                                <a href="/profile" class="dropdown-item"><i class="ti ti-user me-2"></i>Profile</a>
                                <a href="/settings" class="dropdown-item"><i class="ti ti-settings me-2"></i>Settings</a>
                                <div class="dropdown-divider"></div>
                                <a href="/logout" class="dropdown-item" data-no-ajax="true"><i class="ti ti-logout me-2"></i>Logout</a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content Area (updated by AJAX router) -->
            <div class="page-content-area">
                <!-- Flash Messages -->
                <?php if ($message = flash('success')): ?>
                <div class="container-xl mt-3">
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <div class="d-flex">
                            <div><i class="ti ti-check icon alert-icon"></i></div>
                            <div><?= e($message) ?></div>
                        </div>
                        <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($message = flash('error')): ?>
                <div class="container-xl mt-3">
                    <div class="alert alert-danger alert-dismissible" role="alert">
                        <div class="d-flex">
                            <div><i class="ti ti-alert-circle icon alert-icon"></i></div>
                            <div><?= e($message) ?></div>
                        </div>
                        <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($message = flash('info')): ?>
                <div class="container-xl mt-3">
                    <div class="alert alert-info alert-dismissible" role="alert">
                        <div class="d-flex">
                            <div><i class="ti ti-info-circle icon alert-icon"></i></div>
                            <div><?= e($message) ?></div>
                        </div>
                        <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Page Content (includes page-header and page-body from view) -->
                <?php require $contentView ?? ''; ?>
            </div>

            <!-- Footer -->
            <footer class="footer footer-transparent d-print-none">
                <div class="container-xl">
                    <div class="row text-center align-items-center flex-row-reverse">
                        <div class="col-lg-auto ms-lg-auto">
                            <span class="text-muted">Academic Year: <?= $_SESSION['current_year'] ?? date('Y') ?></span>
                        </div>
                        <div class="col-12 col-lg-auto mt-3 mt-lg-0">
                            <ul class="list-inline list-inline-dots mb-0">
                                <li class="list-inline-item">
                                    Copyright &copy; <?= date('Y') ?>
                                    <a href="." class="link-secondary"><?= e($_SESSION['tenant_name'] ?? 'School') ?></a>.
                                    All rights reserved.
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- Tabler Core (Local) -->
    <script src="/vendor/tabler/js/tabler.min.js"></script>

    <!-- jQuery (local - for compatibility with existing scripts) -->
    <script src="/vendor/jquery-3.6.0.min.js"></script>

    <!-- AJAX Router -->
    <script src="/assets/js/ajax-router.js"></script>

    <!-- Persistent Tabs - only load on pages with tabs -->
    <?php if (strpos($_SERVER['REQUEST_URI'] ?? '', '/applicants/') !== false &&
              strpos($_SERVER['REQUEST_URI'] ?? '', '/create') === false): ?>
    <script src="/assets/js/persistent-tabs.js"></script>
    <script src="/assets/js/applicant-profile.js"></script>
    <?php elseif (strpos($_SERVER['REQUEST_URI'] ?? '', '/students/') !== false): ?>
    <script src="/assets/js/persistent-tabs.js"></script>
    <?php endif; ?>

    <script>
        // Bootstrap 5 modal compatibility for jQuery
        if (typeof jQuery !== 'undefined') {
            jQuery.fn.modal = function(action) {
                return this.each(function() {
                    const modal = bootstrap.Modal.getOrCreateInstance(this);
                    if (action === 'show') modal.show();
                    else if (action === 'hide') modal.hide();
                    else if (action === 'toggle') modal.toggle();
                });
            };
        }
    </script>
</body>
</html>
