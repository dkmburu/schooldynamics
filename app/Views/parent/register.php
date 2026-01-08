<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title>Parent Registration - <?= e($_SESSION['tenant_name'] ?? 'School') ?></title>

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
            padding: 1rem 0;
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
            padding: 1.5rem 2rem;
            text-align: center;
            border-radius: 1rem 1rem 0 0;
        }
        .auth-body { padding: 2rem; }
        .school-logo {
            width: 60px; height: 60px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.75rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .school-logo i { font-size: 1.75rem; color: #2563eb; }
        .form-control:focus { border-color: #2563eb; box-shadow: 0 0 0 0.25rem rgba(37, 99, 235, 0.25); }
        .btn-primary { background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); border: none; }
        .btn-primary:hover { background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); }
        .info-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 1rem;
        }
        .info-box i { color: #3b82f6; }
    </style>
</head>
<body>
    <?php $schoolName = $_SESSION['tenant_name'] ?? 'School'; ?>

    <div class="auth-card">
        <div class="auth-header">
            <div class="school-logo"><i class="ti ti-school"></i></div>
            <h4 class="mb-0"><?= e($schoolName) ?></h4>
            <small class="opacity-75">Parent Portal Registration</small>
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

            <h4 class="mb-2 text-center">Create Account</h4>
            <p class="text-muted text-center mb-4">Register to access your child's information</p>

            <div class="info-box mb-4">
                <div class="d-flex align-items-start">
                    <i class="ti ti-info-circle me-2 mt-1"></i>
                    <div>
                        <strong class="d-block mb-1">How it works</strong>
                        <small class="text-muted">
                            Enter your National ID and phone number as registered with the school.
                            We'll send you an SMS with a link to activate your account. No password needed!
                        </small>
                    </div>
                </div>
            </div>

            <form method="POST" action="/parent/register" id="registerForm">
                <?= csrfField() ?>

                <div class="mb-3">
                    <label class="form-label">National ID / Passport Number <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="ti ti-id-badge-2"></i></span>
                        <input type="text" name="id_number" class="form-control" placeholder="e.g., 12345678" value="<?= e(old('id_number')) ?>" required autofocus>
                    </div>
                    <small class="text-muted">Must match your ID number in our guardian records</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="ti ti-phone"></i></span>
                        <input type="tel" name="phone" class="form-control" placeholder="e.g., 0712345678" value="<?= e(old('phone')) ?>" required>
                    </div>
                    <small class="text-muted">We'll send the activation link to this number</small>
                </div>

                <div class="mb-4">
                    <label class="form-check">
                        <input type="checkbox" class="form-check-input" name="terms" id="termsCheckbox" required>
                        <span class="form-check-label">
                            I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms of Service</a>
                            and <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">Privacy Policy</a>
                        </span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-3" id="submitBtn">
                    <i class="ti ti-send me-1"></i> Send Activation Link
                </button>
            </form>

            <hr class="my-3">

            <p class="text-center text-muted mb-0">
                Already have an account? <a href="/parent/login">Sign In</a>
            </p>
        </div>
    </div>

    <!-- Terms of Service Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Terms of Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>Parent Portal Terms of Service</h6>
                    <p>By using the <?= e($schoolName) ?> Parent Portal, you agree to the following terms:</p>

                    <h6>1. Account Access</h6>
                    <p>Your account provides access to your child's academic records, attendance, and financial information. You are responsible for maintaining the confidentiality of your account.</p>

                    <h6>2. Data Accuracy</h6>
                    <p>The information displayed is based on school records. If you notice any discrepancies, please contact the school administration.</p>

                    <h6>3. Acceptable Use</h6>
                    <p>You agree to use the portal only for legitimate purposes related to your child's education. Any misuse may result in account suspension.</p>

                    <h6>4. Communication</h6>
                    <p>By registering, you consent to receive SMS and email notifications regarding your child's education and portal updates.</p>

                    <h6>5. Security</h6>
                    <p>You must immediately notify the school if you suspect unauthorized access to your account.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">I Understand</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Privacy Policy Modal -->
    <div class="modal fade" id="privacyModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Privacy Policy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>Parent Portal Privacy Policy</h6>

                    <h6>Information We Collect</h6>
                    <p>We collect and store your National ID/Passport number and phone number for identity verification purposes.</p>

                    <h6>How We Use Your Information</h6>
                    <ul>
                        <li>To verify your identity as a guardian</li>
                        <li>To send authentication codes and notifications via SMS</li>
                        <li>To provide access to your child's educational information</li>
                    </ul>

                    <h6>Data Protection</h6>
                    <p>Your personal information is encrypted and stored securely. We do not share your data with third parties except as required by law.</p>

                    <h6>Your Rights</h6>
                    <p>You have the right to access, correct, or request deletion of your personal data by contacting the school administration.</p>

                    <h6>Contact</h6>
                    <p>For privacy-related concerns, please contact the school's data protection officer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">I Understand</button>
                </div>
            </div>
        </div>
    </div>

    <script src="/vendor/tabler/js/tabler.min.js"></script>
    <script>
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Sending...';
        });
    </script>
</body>
</html>
