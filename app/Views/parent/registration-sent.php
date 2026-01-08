<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title>Registration - <?= e($_SESSION['tenant_name'] ?? 'School') ?></title>

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
            max-width: 450px;
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
        .info-icon {
            width: 80px; height: 80px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .info-icon i { font-size: 2.5rem; color: #2563eb; }
        .message-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 1.5rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php
    $schoolName = $_SESSION['tenant_name'] ?? 'School';
    $showContactSchool = $_SESSION['registration_attempts_exceeded'] ?? false;
    // Clear the flag
    unset($_SESSION['registration_attempts_exceeded']);
    ?>

    <div class="auth-card">
        <div class="auth-header">
            <div class="info-icon"><i class="ti ti-mail-forward"></i></div>
            <h3 class="mb-1">Request Submitted</h3>
            <p class="mb-0 opacity-75"><?= e($schoolName) ?> Parent Portal</p>
        </div>
        <div class="auth-body">
            <div class="message-box mb-4">
                <p class="mb-0">
                    If your details match our records, you will receive an SMS with an activation link shortly.
                </p>
            </div>

            <?php if ($showContactSchool): ?>
                <div class="alert alert-warning mb-4">
                    <div class="d-flex align-items-start">
                        <i class="ti ti-alert-triangle me-2 mt-1"></i>
                        <div>
                            <strong>Having trouble?</strong>
                            <p class="mb-0 small">If you've tried multiple times without receiving an SMS, please contact the school office to verify your registration details.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="text-muted small mb-4">
                <p class="mb-2"><strong>Please note:</strong></p>
                <ul class="ps-3 mb-0">
                    <li>SMS may take a few minutes to arrive</li>
                    <li>The activation link expires in 1 hour</li>
                    <li>Your ID and phone must match our guardian records</li>
                </ul>
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-between">
                <a href="/parent/login" class="btn btn-ghost-secondary">
                    <i class="ti ti-arrow-left me-1"></i> Back to Login
                </a>
                <a href="/parent/register" class="btn btn-outline-primary">
                    <i class="ti ti-refresh me-1"></i> Try Again
                </a>
            </div>
        </div>
    </div>

    <script src="/vendor/tabler/js/tabler.min.js"></script>
</body>
</html>
