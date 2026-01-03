<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title>Main Admin Login - SchoolDynamics</title>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/css/tabler.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet"/>
    <style>
        @import url('https://rsms.me/inter/inter.css');
        :root {
            --tblr-font-sans-serif: 'Inter', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
        }
        body {
            font-feature-settings: "cv03", "cv04", "cv11";
        }
    </style>
</head>
<body class="d-flex flex-column bg-white">
    <div class="page page-center">
        <div class="container container-tight py-4">
            <div class="text-center mb-4">
                <a href="/" class="navbar-brand navbar-brand-autodark">
                    <h1 class="mb-0"><span class="text-primary">School</span><span class="text-secondary">Dynamics</span></h1>
                </a>
                <div class="text-muted mt-2">Main Administrator Portal</div>
            </div>

            <div class="card card-md">
                <div class="card-body">
                    <h2 class="h2 text-center mb-4">Login to your account</h2>

                    <?php if ($error = flash('error')): ?>
                    <div class="alert alert-danger" role="alert">
                        <div class="d-flex">
                            <div><i class="ti ti-alert-circle icon"></i></div>
                            <div><?= e($error) ?></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($success = flash('success')): ?>
                    <div class="alert alert-success" role="alert">
                        <div class="d-flex">
                            <div><i class="ti ti-check icon"></i></div>
                            <div><?= e($success) ?></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <form action="/login" method="post" autocomplete="off">
                        <?= csrfField() ?>

                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" placeholder="Enter username"
                                   value="<?= e(old('username')) ?>" autocomplete="off" required autofocus>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Password</label>
                            <div class="input-group input-group-flat">
                                <input type="password" name="password" class="form-control" placeholder="Enter password"
                                       autocomplete="off" required>
                                <span class="input-group-text">
                                    <a href="#" class="link-secondary" title="Show password" data-bs-toggle="tooltip">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                </span>
                            </div>
                        </div>

                        <div class="form-footer">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ti ti-login icon"></i>
                                Sign in
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="text-center text-muted mt-3">
                <small>
                    This is a restricted area. Unauthorized access is prohibited.<br>
                    &copy; <?= date('Y') ?> SchoolDynamics. All rights reserved.
                </small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/js/tabler.min.js"></script>
</body>
</html>
