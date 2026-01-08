<?php
$stats = $stats ?? ['total' => 0, 'pending' => 0, 'active' => 0, 'suspended' => 0];
$settings = $settings ?? [];

// Default settings
$defaults = [
    'portal_enabled' => true,
    'self_registration' => true,
    'require_email_verification' => true,
    'require_admin_approval' => false,
    'show_grades' => true,
    'show_attendance' => true,
    'show_fees' => true,
    'show_timetable' => true,
    'allow_online_payment' => false,
    'session_timeout_minutes' => 60,
    'max_login_attempts' => 5,
    'lockout_duration_minutes' => 15,
    'welcome_message' => 'Welcome to the Parent Portal. Here you can view your children\'s academic progress, fee statements, and more.'
];

// Merge with actual settings
foreach ($defaults as $key => $value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}
?>

<div class="container-xl">
    <!-- Page Header -->
    <div class="page-header d-print-none mb-4">
        <div class="row align-items-center">
            <div class="col-auto">
                <h2 class="page-title">
                    <i class="ti ti-users-group me-2"></i> Parent Portal Management
                </h2>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link" href="/portals/parents">
                <i class="ti ti-users me-1"></i> Accounts
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="/portals/parents/pending">
                <i class="ti ti-user-check me-1"></i> Pending
                <?php if ($stats['pending'] > 0): ?>
                    <span class="badge bg-warning ms-1"><?= $stats['pending'] ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="/portals/parents/notifications">
                <i class="ti ti-bell me-1"></i> Notifications
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="/portals/parents/settings">
                <i class="ti ti-settings me-1"></i> Settings
            </a>
        </li>
    </ul>

    <form method="POST" action="/portals/parents/settings">
        <div class="row g-4">
            <!-- General Settings -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-settings me-2"></i> General Settings
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-check form-switch">
                                <input type="checkbox" name="portal_enabled" class="form-check-input" <?= $settings['portal_enabled'] ? 'checked' : '' ?>>
                                <span class="form-check-label">
                                    <strong>Portal Enabled</strong>
                                    <div class="text-muted small">When disabled, parents cannot access the portal</div>
                                </span>
                            </label>
                        </div>

                        <div class="mb-3">
                            <label class="form-check form-switch">
                                <input type="checkbox" name="self_registration" class="form-check-input" <?= $settings['self_registration'] ? 'checked' : '' ?>>
                                <span class="form-check-label">
                                    <strong>Allow Self-Registration</strong>
                                    <div class="text-muted small">Parents can create their own accounts</div>
                                </span>
                            </label>
                        </div>

                        <div class="mb-3">
                            <label class="form-check form-switch">
                                <input type="checkbox" name="require_email_verification" class="form-check-input" <?= $settings['require_email_verification'] ? 'checked' : '' ?>>
                                <span class="form-check-label">
                                    <strong>Require Email Verification</strong>
                                    <div class="text-muted small">Parents must verify their email before accessing portal</div>
                                </span>
                            </label>
                        </div>

                        <div class="mb-3">
                            <label class="form-check form-switch">
                                <input type="checkbox" name="require_admin_approval" class="form-check-input" <?= $settings['require_admin_approval'] ? 'checked' : '' ?>>
                                <span class="form-check-label">
                                    <strong>Require Admin Approval</strong>
                                    <div class="text-muted small">New registrations must be approved by staff</div>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Security Settings -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-shield me-2"></i> Security Settings
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Session Timeout (minutes)</label>
                            <input type="number" name="session_timeout_minutes" class="form-control" value="<?= (int)$settings['session_timeout_minutes'] ?>" min="5" max="480">
                            <small class="form-hint">Parents will be logged out after this period of inactivity</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Max Login Attempts</label>
                            <input type="number" name="max_login_attempts" class="form-control" value="<?= (int)$settings['max_login_attempts'] ?>" min="3" max="10">
                            <small class="form-hint">Account will be locked after this many failed attempts</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Lockout Duration (minutes)</label>
                            <input type="number" name="lockout_duration_minutes" class="form-control" value="<?= (int)$settings['lockout_duration_minutes'] ?>" min="5" max="60">
                            <small class="form-hint">How long accounts remain locked after failed attempts</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feature Access -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-toggle-left me-2"></i> Feature Access
                        </h3>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Control what information parents can view in their portal:</p>

                        <div class="mb-3">
                            <label class="form-check form-switch">
                                <input type="checkbox" name="show_fees" class="form-check-input" <?= $settings['show_fees'] ? 'checked' : '' ?>>
                                <span class="form-check-label">
                                    <i class="ti ti-receipt me-2 text-primary"></i>
                                    <strong>Fee Statements</strong>
                                    <div class="text-muted small">View fee balances, invoices, and payment history</div>
                                </span>
                            </label>
                        </div>

                        <div class="mb-3">
                            <label class="form-check form-switch">
                                <input type="checkbox" name="show_attendance" class="form-check-input" <?= $settings['show_attendance'] ? 'checked' : '' ?>>
                                <span class="form-check-label">
                                    <i class="ti ti-calendar-stats me-2 text-success"></i>
                                    <strong>Attendance Records</strong>
                                    <div class="text-muted small">View daily attendance and absence reports</div>
                                </span>
                            </label>
                        </div>

                        <div class="mb-3">
                            <label class="form-check form-switch">
                                <input type="checkbox" name="show_grades" class="form-check-input" <?= $settings['show_grades'] ? 'checked' : '' ?>>
                                <span class="form-check-label">
                                    <i class="ti ti-report-analytics me-2 text-warning"></i>
                                    <strong>Grades & Results</strong>
                                    <div class="text-muted small">View exam results and academic progress</div>
                                </span>
                            </label>
                        </div>

                        <div class="mb-3">
                            <label class="form-check form-switch">
                                <input type="checkbox" name="show_timetable" class="form-check-input" <?= $settings['show_timetable'] ? 'checked' : '' ?>>
                                <span class="form-check-label">
                                    <i class="ti ti-table me-2 text-cyan"></i>
                                    <strong>Class Timetable</strong>
                                    <div class="text-muted small">View class schedule and subjects</div>
                                </span>
                            </label>
                        </div>

                        <hr class="my-4">

                        <div class="mb-3">
                            <label class="form-check form-switch">
                                <input type="checkbox" name="allow_online_payment" class="form-check-input" <?= $settings['allow_online_payment'] ? 'checked' : '' ?>>
                                <span class="form-check-label">
                                    <i class="ti ti-credit-card me-2 text-danger"></i>
                                    <strong>Online Payments</strong>
                                    <div class="text-muted small">Allow parents to pay fees online (requires payment gateway setup)</div>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Welcome Message -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-message me-2"></i> Welcome Message
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Dashboard Welcome Message</label>
                            <textarea name="welcome_message" class="form-control" rows="4"><?= e($settings['welcome_message']) ?></textarea>
                            <small class="form-hint">This message is displayed on the parent portal dashboard</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="card mt-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted">
                            <i class="ti ti-info-circle me-1"></i>
                            Changes will take effect immediately for all users
                        </span>
                    </div>
                    <div>
                        <button type="reset" class="btn btn-outline-secondary me-2">
                            <i class="ti ti-refresh me-1"></i> Reset
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-1"></i> Save Settings
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
