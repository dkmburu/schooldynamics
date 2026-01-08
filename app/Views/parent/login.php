<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title>Parent Login - <?= e($_SESSION['tenant_name'] ?? 'School') ?></title>

    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="alternate icon" href="/favicon.ico">
    <link href="/vendor/tabler/css/tabler.min.css" rel="stylesheet"/>
    <link href="/vendor/tabler/icons/tabler-icons.min.css" rel="stylesheet"/>

    <style>
        :root { --tblr-primary: #2563eb; --tblr-primary-rgb: 37, 99, 235; }
        body {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 50%, #60a5fa 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 25px 50px rgba(0,0,0,0.25);
            max-width: 400px;
            width: 100%;
            margin: 1rem;
        }
        .auth-header {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
            padding: 2rem;
            text-align: center;
            border-radius: 1rem 1rem 0 0;
        }
        .auth-body { padding: 2rem; }
        .school-logo {
            width: 80px; height: 80px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .school-logo i { font-size: 2.5rem; color: #2563eb; }
        .form-control:focus { border-color: #2563eb; box-shadow: 0 0 0 0.25rem rgba(37, 99, 235, 0.25); }
        .btn-primary { background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); border: none; }
        .btn-primary:hover { background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); }
        .info-box {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
        }
        .info-box i { color: #0284c7; }
    </style>
</head>
<body>
    <?php $schoolName = $_SESSION['tenant_name'] ?? 'School'; ?>

    <div class="auth-card">
        <div class="auth-header">
            <div class="school-logo"><i class="ti ti-school"></i></div>
            <h3 class="mb-1"><?= e($schoolName) ?></h3>
            <p class="mb-0 opacity-75">Parent Portal</p>
        </div>
        <div class="auth-body">
            <?php if ($success = flash('success')): ?>
                <div class="alert alert-success alert-dismissible mb-3">
                    <div class="d-flex"><div><i class="ti ti-check me-2"></i></div><div><?= $success ?></div></div>
                    <a class="btn-close btn-close-sm" data-bs-dismiss="alert"></a>
                </div>
            <?php endif; ?>

            <?php if ($error = flash('error')): ?>
                <div class="alert alert-danger alert-dismissible mb-3">
                    <div class="d-flex"><div><i class="ti ti-alert-circle me-2"></i></div><div><?= $error ?></div></div>
                    <a class="btn-close btn-close-sm" data-bs-dismiss="alert"></a>
                </div>
            <?php endif; ?>

            <h4 class="mb-3 text-center">Welcome Back</h4>

            <div class="info-box mb-4">
                <div class="d-flex align-items-center">
                    <i class="ti ti-device-mobile me-2"></i>
                    <small>We'll send a verification code to your phone</small>
                </div>
            </div>

            <form method="POST" action="/parent/login" id="loginForm">
                <?= csrfField() ?>

                <div class="mb-3">
                    <label class="form-label">National ID / Passport Number</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="ti ti-id-badge-2"></i></span>
                        <input type="text" name="id_number" class="form-control" placeholder="e.g., 12345678" value="<?= e(old('id_number')) ?>" required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Phone Number</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="ti ti-phone"></i></span>
                        <input type="tel" name="phone" class="form-control" placeholder="e.g., 0712345678" value="<?= e(old('phone')) ?>" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-3" id="submitBtn">
                    <i class="ti ti-send me-1"></i> Send Verification Code
                </button>
            </form>

            <hr class="my-4">

            <p class="text-center text-muted mb-3">Don't have an account?</p>
            <a href="/parent/register" class="btn btn-outline-primary w-100">
                <i class="ti ti-user-plus me-1"></i> Register as Parent
            </a>

            <div class="mt-4 text-center">
                <a href="/login" class="text-muted small"><i class="ti ti-arrow-left me-1"></i> Staff Login</a>
            </div>
        </div>
    </div>

    <script src="/vendor/tabler/js/tabler.min.js"></script>
    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Sending Code...';
        });
    </script>
</body>
</html>
