<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title>Login - <?= e(Database::getCurrentTenant()['school_name'] ?? 'School') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/css/tabler.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet"/>
    <style>
        @import url('https://rsms.me/inter/inter.css');
        :root {
            --tblr-font-sans-serif: 'Inter', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
        }
        body {
            font-feature-settings: "cv03", "cv04", "cv11";
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body class="d-flex flex-column">
    <div class="page page-center">
        <div class="container container-tight py-4">
            <div class="text-center mb-4">
                <a href="/" class="navbar-brand navbar-brand-autodark">
                    <h1 class="mb-0 text-white"><?= e(Database::getCurrentTenant()['school_name'] ?? 'School Management System') ?></h1>
                </a>
                <div class="text-white mt-2 opacity-75">Staff Portal</div>
            </div>

            <div class="card card-md">
                <div class="card-body">
                    <h2 class="h2 text-center mb-4">Sign in to your account</h2>

                    <?php if ($error = flash('error')): ?>
                    <div class="alert alert-danger alert-dismissible" role="alert">
                        <div class="d-flex">
                            <div><i class="ti ti-alert-circle icon"></i></div>
                            <div><?= e($error) ?></div>
                        </div>
                        <a class="btn-close" data-bs-dismiss="alert"></a>
                    </div>
                    <?php endif; ?>

                    <?php if ($success = flash('success')): ?>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <div class="d-flex">
                            <div><i class="ti ti-check icon"></i></div>
                            <div><?= e($success) ?></div>
                        </div>
                        <a class="btn-close" data-bs-dismiss="alert"></a>
                    </div>
                    <?php endif; ?>

                    <?php if ($info = flash('info')): ?>
                    <div class="alert alert-info alert-dismissible" role="alert">
                        <div class="d-flex">
                            <div><i class="ti ti-info-circle icon"></i></div>
                            <div><?= e($info) ?></div>
                        </div>
                        <a class="btn-close" data-bs-dismiss="alert"></a>
                    </div>
                    <?php endif; ?>

                    <form action="/login" method="post" autocomplete="off">
                        <?= csrfField() ?>

                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" placeholder="Enter your username"
                                   value="<?= e(old('username')) ?>" autocomplete="off" required autofocus>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">
                                Password
                                <span class="form-label-description">
                                    <a href="/forgot-password" tabindex="-1">Forgot password?</a>
                                </span>
                            </label>
                            <div class="input-group input-group-flat">
                                <input type="password" name="password" id="password" class="form-control"
                                       placeholder="Enter your password" autocomplete="off" required>
                                <span class="input-group-text">
                                    <a href="#" class="link-secondary" title="Show password"
                                       onclick="togglePassword(event)">
                                        <i class="ti ti-eye" id="password-icon"></i>
                                    </a>
                                </span>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-check">
                                <input type="checkbox" class="form-check-input" name="remember"/>
                                <span class="form-check-label">Remember me on this device</span>
                            </label>
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

            <div class="text-center text-white mt-3 opacity-75">
                <small>
                    Powered by SchoolDynamics &copy; <?= date('Y') ?>
                </small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/js/tabler.min.js"></script>
    <script>
        function togglePassword(e) {
            e.preventDefault();
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('password-icon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.classList.remove('ti-eye');
                passwordIcon.classList.add('ti-eye-off');
            } else {
                passwordInput.type = 'password';
                passwordIcon.classList.remove('ti-eye-off');
                passwordIcon.classList.add('ti-eye');
            }
        }
    </script>
</body>
</html>
