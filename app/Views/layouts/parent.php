<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title><?= $pageTitle ?? 'Parent Portal' ?> | <?= e($_SESSION['tenant_name'] ?? 'School') ?></title>

    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="alternate icon" href="/favicon.ico">
    <link href="/vendor/tabler/css/tabler.min.css" rel="stylesheet"/>
    <link href="/vendor/tabler/icons/tabler-icons.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="/css/professional-theme.css">

    <style>
        :root {
            --tblr-font-sans-serif: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            --tblr-primary: #2563eb;
            --tblr-primary-rgb: 37, 99, 235;
        }
        body { font-feature-settings: "cv03", "cv04", "cv11"; background: #f1f5f9; }
        .parent-header { background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); }
        .parent-nav {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .parent-nav .nav-link {
            color: #64748b;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 0.75rem;
            text-decoration: none;
            transition: all 0.2s;
        }
        .parent-nav .nav-link i { font-size: 1.25rem; margin-bottom: 0.25rem; }
        .parent-nav .nav-link:hover, .parent-nav .nav-link.active { color: #2563eb; background: #eff6ff; }
        .child-card { border-radius: 1rem; overflow: hidden; transition: transform 0.2s, box-shadow 0.2s; }
        .child-card:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .stat-card { border-radius: 0.75rem; border: none; }
        .notification-badge { position: absolute; top: 0.5rem; right: 0.5rem; min-width: 1.25rem; height: 1.25rem; font-size: 0.65rem; }
        @media (max-width: 768px) {
            .parent-nav { position: fixed; bottom: 0; left: 0; right: 0; top: auto; }
            .page-wrapper { padding-bottom: 70px; }
        }
    </style>
</head>
<body>
    <?php
    $schoolName = $_SESSION['tenant_name'] ?? 'School';
    $parentName = $_SESSION['parent_name'] ?? 'Parent';
    $unreadNotifications = $_SESSION['parent_unread_notifications'] ?? 0;
    $children = $_SESSION['parent_children'] ?? [];
    $currentPage = $currentPage ?? '';
    ?>

    <div class="page">
        <!-- Header -->
        <header class="parent-header text-white py-3">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0"><?= e($schoolName) ?></h4>
                        <small class="opacity-75">Parent Portal</small>
                    </div>
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                            <div class="avatar avatar-sm bg-white text-primary me-2" style="width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <?= strtoupper(substr($parentName, 0, 1)) ?>
                            </div>
                            <span class="d-none d-sm-inline"><?= e($parentName) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/parent/profile"><i class="ti ti-user me-2"></i> My Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="/parent/logout"><i class="ti ti-logout me-2"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </header>

        <!-- Navigation -->
        <nav class="parent-nav">
            <div class="container">
                <div class="d-flex justify-content-around">
                    <a href="/parent/dashboard" class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                        <i class="ti ti-home"></i>
                        <span>Home</span>
                    </a>
                    <?php if (count($children) === 1): ?>
                        <a href="/parent/child/<?= $children[0]['id'] ?>/fees" class="nav-link <?= $currentPage === 'fees' ? 'active' : '' ?>">
                            <i class="ti ti-receipt"></i>
                            <span>Fees</span>
                        </a>
                        <a href="/parent/child/<?= $children[0]['id'] ?>/attendance" class="nav-link <?= $currentPage === 'attendance' ? 'active' : '' ?>">
                            <i class="ti ti-calendar-check"></i>
                            <span>Attendance</span>
                        </a>
                    <?php endif; ?>
                    <a href="/parent/notifications" class="nav-link position-relative <?= $currentPage === 'notifications' ? 'active' : '' ?>">
                        <i class="ti ti-bell"></i>
                        <span>Notifications</span>
                        <?php if ($unreadNotifications > 0): ?>
                            <span class="badge bg-danger notification-badge"><?= $unreadNotifications > 9 ? '9+' : $unreadNotifications ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="/parent/contacts" class="nav-link <?= $currentPage === 'contacts' ? 'active' : '' ?>">
                        <i class="ti ti-phone"></i>
                        <span>Contacts</span>
                    </a>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <div class="page-wrapper">
            <div class="page-body">
                <?php if (isset($_SESSION['_flash_success'])): ?>
                    <div class="container mt-3">
                        <div class="alert alert-success alert-dismissible" role="alert">
                            <div class="d-flex">
                                <div><i class="ti ti-check me-2"></i></div>
                                <div><?= flash('success') ?></div>
                            </div>
                            <a class="btn-close" data-bs-dismiss="alert"></a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['_flash_error'])): ?>
                    <div class="container mt-3">
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            <div class="d-flex">
                                <div><i class="ti ti-alert-circle me-2"></i></div>
                                <div><?= flash('error') ?></div>
                            </div>
                            <a class="btn-close" data-bs-dismiss="alert"></a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (isset($contentView) && file_exists($contentView)): ?>
                    <?php require $contentView; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="/vendor/tabler/js/tabler.min.js"></script>
</body>
</html>
