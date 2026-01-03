<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title><?= $pageTitle ?? 'Admin Portal' ?> - SchoolDynamics</title>
    <!-- CSS files (local) -->
    <link href="/vendor/tabler/css/tabler.min.css" rel="stylesheet"/>
    <link href="/vendor/tabler/icons/tabler-icons.min.css" rel="stylesheet"/>
    <style>
        :root {
            --tblr-font-sans-serif: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        body {
            font-feature-settings: "cv03", "cv04", "cv11";
        }
    </style>
</head>
<body>
    <div class="page">
        <!-- Navbar -->
        <header class="navbar navbar-expand-md navbar-light d-print-none">
            <div class="container-xl">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
                    <a href="/dashboard">
                        <span class="text-primary">School</span><span class="text-secondary">Dynamics</span>
                        <small class="text-muted ms-2">Admin</small>
                    </a>
                </h1>
                <div class="navbar-nav flex-row order-md-last">
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown">
                            <span class="avatar avatar-sm bg-primary text-white"><?= strtoupper(substr($_SESSION['admin_full_name'] ?? 'A', 0, 1)) ?></span>
                            <div class="d-none d-xl-block ps-2">
                                <div><?= e($_SESSION['admin_full_name'] ?? 'Admin') ?></div>
                                <div class="mt-1 small text-muted">Main Administrator</div>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="/profile">Profile</a>
                            <a class="dropdown-item" href="/settings">Settings</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="/logout">Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="navbar-expand-md">
            <div class="collapse navbar-collapse" id="navbar-menu">
                <div class="navbar navbar-light">
                    <div class="container-xl">
                        <ul class="navbar-nav">
                            <li class="nav-item">
                                <a class="nav-link" href="/dashboard">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-home"></i>
                                    </span>
                                    <span class="nav-link-title">Dashboard</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/tenants">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-building"></i>
                                    </span>
                                    <span class="nav-link-title">Tenants</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/metrics">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-chart-bar"></i>
                                    </span>
                                    <span class="nav-link-title">Metrics</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/users">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-users"></i>
                                    </span>
                                    <span class="nav-link-title">Admin Users</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/audit-logs">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-file-text"></i>
                                    </span>
                                    <span class="nav-link-title">Audit Logs</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page Content -->
        <div class="page-wrapper">
            <?php if ($message = flash('success')): ?>
            <div class="container-xl mt-3">
                <div class="alert alert-success alert-dismissible" role="alert">
                    <div class="d-flex">
                        <div><i class="ti ti-check icon alert-icon"></i></div>
                        <div><?= e($message) ?></div>
                    </div>
                    <a class="btn-close" data-bs-dismiss="alert"></a>
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
                    <a class="btn-close" data-bs-dismiss="alert"></a>
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
                    <a class="btn-close" data-bs-dismiss="alert"></a>
                </div>
            </div>
            <?php endif; ?>

            <?php require $contentView ?? ''; ?>

            <!-- Footer -->
            <footer class="footer footer-transparent d-print-none">
                <div class="container-xl">
                    <div class="row text-center align-items-center">
                        <div class="col-12 col-lg-auto mt-3 mt-lg-0">
                            <ul class="list-inline list-inline-dots mb-0">
                                <li class="list-inline-item">
                                    Copyright &copy; <?= date('Y') ?>
                                    <a href="." class="link-secondary">SchoolDynamics</a>.
                                    All rights reserved.
                                </li>
                                <li class="list-inline-item">
                                    Version 1.0.0
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- Tabler Core (local) -->
    <script src="/vendor/tabler/js/tabler.min.js"></script>
</body>
</html>
