<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title>Verify Code - <?= e($_SESSION['tenant_name'] ?? 'School') ?></title>

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

        /* OTP Input styling */
        .otp-inputs {
            display: flex;
            justify-content: center;
            gap: 0.75rem;
        }
        .otp-input {
            width: 56px;
            height: 64px;
            font-size: 1.75rem;
            text-align: center;
            border: 2px solid #e2e8f0;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.2s;
        }
        .otp-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
            outline: none;
        }
        .otp-input.filled {
            border-color: #10b981;
            background: #f0fdf4;
        }
        .phone-display {
            background: #f8fafc;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            text-align: center;
        }
        .phone-display .phone-number {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e40af;
        }
        .timer-display {
            font-size: 1.5rem;
            font-weight: 600;
            color: #dc2626;
        }
        .timer-display.active {
            color: #059669;
        }
    </style>
</head>
<body>
    <?php
    $schoolName = $_SESSION['tenant_name'] ?? 'School';
    $maskedPhone = $_SESSION['otp_phone'] ?? '****';
    $expiresAt = $_SESSION['otp_expires_at'] ?? time() + 300;
    $remainingSeconds = max(0, $expiresAt - time());
    ?>

    <div class="auth-card">
        <div class="auth-header">
            <div class="school-logo"><i class="ti ti-shield-lock"></i></div>
            <h3 class="mb-1">Verify Your Identity</h3>
            <p class="mb-0 opacity-75">Enter the code sent to your phone</p>
        </div>
        <div class="auth-body">
            <?php if ($error = flash('error')): ?>
                <div class="alert alert-danger alert-dismissible mb-3">
                    <div class="d-flex"><div><i class="ti ti-alert-circle me-2"></i></div><div><?= $error ?></div></div>
                    <a class="btn-close btn-close-sm" data-bs-dismiss="alert"></a>
                </div>
            <?php endif; ?>

            <?php if ($success = flash('success')): ?>
                <div class="alert alert-success alert-dismissible mb-3">
                    <div class="d-flex"><div><i class="ti ti-check me-2"></i></div><div><?= $success ?></div></div>
                    <a class="btn-close btn-close-sm" data-bs-dismiss="alert"></a>
                </div>
            <?php endif; ?>

            <div class="phone-display mb-4">
                <small class="text-muted d-block">Code sent to</small>
                <span class="phone-number"><?= e($maskedPhone) ?></span>
            </div>

            <form method="POST" action="/parent/verify-otp" id="otpForm">
                <?= csrfField() ?>

                <!-- Hidden field to store the complete OTP -->
                <input type="hidden" name="otp" id="otpHidden">

                <div class="otp-inputs mb-4">
                    <input type="text" class="otp-input" maxlength="1" data-index="0" inputmode="numeric" pattern="[0-9]" autofocus>
                    <input type="text" class="otp-input" maxlength="1" data-index="1" inputmode="numeric" pattern="[0-9]">
                    <input type="text" class="otp-input" maxlength="1" data-index="2" inputmode="numeric" pattern="[0-9]">
                    <input type="text" class="otp-input" maxlength="1" data-index="3" inputmode="numeric" pattern="[0-9]">
                    <input type="text" class="otp-input" maxlength="1" data-index="4" inputmode="numeric" pattern="[0-9]">
                </div>

                <div class="text-center mb-4">
                    <small class="text-muted">Code expires in</small>
                    <div class="timer-display active" id="timer">5:00</div>
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-3" id="submitBtn" disabled>
                    <i class="ti ti-check me-1"></i> Verify Code
                </button>
            </form>

            <div class="text-center">
                <p class="text-muted mb-2">Didn't receive the code?</p>
                <form method="POST" action="/parent/resend-otp" id="resendForm" class="d-inline">
                    <?= csrfField() ?>
                    <button type="submit" class="btn btn-ghost-primary btn-sm" id="resendBtn" disabled>
                        <i class="ti ti-refresh me-1"></i> Resend Code
                    </button>
                </form>
                <small class="d-block text-muted mt-2" id="resendTimer">You can resend in <span id="resendCountdown">60</span>s</small>
            </div>

            <hr class="my-4">

            <div class="text-center">
                <a href="/parent/login" class="text-muted small">
                    <i class="ti ti-arrow-left me-1"></i> Back to Login
                </a>
            </div>
        </div>
    </div>

    <script src="/vendor/tabler/js/tabler.min.js"></script>
    <script>
        // OTP Input handling
        const inputs = document.querySelectorAll('.otp-input');
        const otpHidden = document.getElementById('otpHidden');
        const submitBtn = document.getElementById('submitBtn');
        const form = document.getElementById('otpForm');

        function updateOtpValue() {
            let otp = '';
            inputs.forEach(input => {
                otp += input.value;
            });
            otpHidden.value = otp;

            // Enable submit button when all 5 digits are entered
            submitBtn.disabled = otp.length !== 5;

            // Auto-submit when complete
            if (otp.length === 5) {
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Verifying...';
                form.submit();
            }
        }

        inputs.forEach((input, index) => {
            input.addEventListener('input', function(e) {
                // Only allow numbers
                this.value = this.value.replace(/[^0-9]/g, '');

                if (this.value) {
                    this.classList.add('filled');
                    // Move to next input
                    if (index < inputs.length - 1) {
                        inputs[index + 1].focus();
                    }
                } else {
                    this.classList.remove('filled');
                }

                updateOtpValue();
            });

            input.addEventListener('keydown', function(e) {
                // Handle backspace
                if (e.key === 'Backspace' && !this.value && index > 0) {
                    inputs[index - 1].focus();
                    inputs[index - 1].value = '';
                    inputs[index - 1].classList.remove('filled');
                }

                // Handle paste
                if (e.key === 'v' && (e.ctrlKey || e.metaKey)) {
                    return; // Allow paste
                }
            });

            // Handle paste event
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '').slice(0, 5);

                pastedData.split('').forEach((digit, i) => {
                    if (inputs[i]) {
                        inputs[i].value = digit;
                        inputs[i].classList.add('filled');
                    }
                });

                if (pastedData.length > 0) {
                    const lastFilledIndex = Math.min(pastedData.length - 1, inputs.length - 1);
                    inputs[lastFilledIndex].focus();
                }

                updateOtpValue();
            });
        });

        // Timer countdown
        let remainingSeconds = <?= $remainingSeconds ?>;
        const timerDisplay = document.getElementById('timer');

        function updateTimer() {
            const minutes = Math.floor(remainingSeconds / 60);
            const seconds = remainingSeconds % 60;
            timerDisplay.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;

            if (remainingSeconds <= 60) {
                timerDisplay.classList.remove('active');
            }

            if (remainingSeconds <= 0) {
                timerDisplay.textContent = 'Expired';
                submitBtn.disabled = true;
                return;
            }

            remainingSeconds--;
            setTimeout(updateTimer, 1000);
        }
        updateTimer();

        // Resend countdown
        let resendCountdown = 60;
        const resendBtn = document.getElementById('resendBtn');
        const resendCountdownEl = document.getElementById('resendCountdown');
        const resendTimerEl = document.getElementById('resendTimer');

        function updateResendTimer() {
            resendCountdownEl.textContent = resendCountdown;

            if (resendCountdown <= 0) {
                resendBtn.disabled = false;
                resendTimerEl.style.display = 'none';
                return;
            }

            resendCountdown--;
            setTimeout(updateResendTimer, 1000);
        }
        updateResendTimer();

        // Resend form submit handler
        document.getElementById('resendForm').addEventListener('submit', function() {
            resendBtn.disabled = true;
            resendBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Sending...';
        });
    </script>
</body>
</html>
