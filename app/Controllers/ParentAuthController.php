<?php
/**
 * Parent Portal Authentication Controller
 * Handles OTP-based registration and login for parent/guardian accounts
 *
 * Flow:
 * 1. Registration: ID + Phone + ToS → SMS magic link → Auto-login
 * 2. Login: ID + Phone → SMS OTP → Session (30 days)
 */

class ParentAuthController
{
    /**
     * Show login page
     */
    public function showLogin()
    {
        if ($this->isParentLoggedIn()) {
            Response::redirect('/parent/dashboard');
        }

        $schoolName = $this->getSchoolName();
        Response::view('parent.login', ['schoolName' => $schoolName]);
    }

    /**
     * Process login - send OTP
     */
    public function login()
    {
        $idNumber = trim(Request::get('id_number'));
        $phone = $this->normalizePhone(Request::get('phone'));
        $csrfToken = Request::get(env('CSRF_TOKEN_NAME', '_csrf_token'));

        // Validate CSRF
        if (!verifyCsrfToken($csrfToken)) {
            flash('error', 'Invalid security token. Please try again.');
            storeOldInput(['id_number' => $idNumber, 'phone' => Request::get('phone')]);
            Response::back();
        }

        if (empty($idNumber) || empty($phone)) {
            flash('error', 'Please enter your ID number and phone number.');
            storeOldInput(['id_number' => $idNumber, 'phone' => Request::get('phone')]);
            Response::back();
        }

        try {
            $pdo = Database::getTenantConnection();

            // Compare last 9 digits to handle format differences (0722xxx vs 254722xxx)
            $phoneDigits = preg_replace('/[^0-9]/', '', $phone);
            $phoneLast9 = substr($phoneDigits, -9);

            // Find parent account by guardian's ID number and phone
            $stmt = $pdo->prepare("
                SELECT pa.*, g.first_name, g.last_name, g.phone as guardian_phone
                FROM parent_accounts pa
                JOIN guardians g ON pa.guardian_id = g.id
                WHERE g.id_number = :id_number
                AND RIGHT(REGEXP_REPLACE(pa.phone, '[^0-9]', ''), 9) = :phone_last9
                LIMIT 1
            ");
            $stmt->execute(['id_number' => $idNumber, 'phone_last9' => $phoneLast9]);
            $parent = $stmt->fetch();

            if (!$parent) {
                // Generic message - don't reveal if account exists or not
                flash('error', 'Unable to verify your credentials. Please check your details or register if you haven\'t already.');
                storeOldInput(['id_number' => $idNumber, 'phone' => Request::get('phone')]);
                Response::back();
            }

            // Check account status - use generic message
            if ($parent['status'] === 'suspended') {
                flash('error', 'Unable to verify your credentials. Please contact the school for assistance.');
                Response::back();
            }

            // Check if locked - this can be specific since user already passed ID/phone check
            if ($parent['locked_until'] && strtotime($parent['locked_until']) > time()) {
                $lockTime = date('H:i', strtotime($parent['locked_until']));
                flash('error', "Too many failed attempts. Please try again after {$lockTime}.");
                Response::back();
            }

            // Generate OTP
            $otp = $this->generateOTP();
            $otpExpiry = $this->getPortalSetting('otp_expiry_minutes') ?: 5;

            $stmt = $pdo->prepare("
                UPDATE parent_accounts
                SET otp_code = :otp,
                    otp_expires_at = DATE_ADD(NOW(), INTERVAL :expiry MINUTE),
                    failed_login_attempts = 0,
                    locked_until = NULL
                WHERE id = :id
            ");
            $stmt->execute(['otp' => $otp, 'expiry' => $otpExpiry, 'id' => $parent['id']]);

            // Send OTP via SMS
            $this->sendOtpSms($pdo, $parent['id'], $phone, $otp);

            // Store parent_id in session for OTP verification
            $_SESSION['parent_otp_pending'] = $parent['id'];
            $_SESSION['parent_otp_phone'] = $phone;

            flash('success', 'A verification code has been sent to your phone.');
            Response::redirect('/parent/verify-otp');

        } catch (Exception $e) {
            logMessage("Parent login error: " . $e->getMessage(), 'error');
            flash('error', 'An error occurred. Please try again.');
            Response::back();
        }
    }

    /**
     * Show OTP verification page
     */
    public function showVerifyOtp()
    {
        if (!isset($_SESSION['parent_otp_pending'])) {
            Response::redirect('/parent/login');
        }

        $phone = $_SESSION['parent_otp_phone'] ?? '';
        $maskedPhone = $this->maskPhone($phone);
        $schoolName = $this->getSchoolName();

        Response::view('parent.verify-otp', [
            'maskedPhone' => $maskedPhone,
            'schoolName' => $schoolName
        ]);
    }

    /**
     * Verify OTP and complete login
     */
    public function verifyOtp()
    {
        if (!isset($_SESSION['parent_otp_pending'])) {
            Response::redirect('/parent/login');
        }

        $otp = trim(Request::get('otp'));
        $parentId = $_SESSION['parent_otp_pending'];

        if (empty($otp) || strlen($otp) !== 5) {
            flash('error', 'Please enter a valid 5-digit code.');
            Response::back();
        }

        try {
            $pdo = Database::getTenantConnection();

            // Verify OTP
            $stmt = $pdo->prepare("
                SELECT pa.*, g.first_name, g.last_name
                FROM parent_accounts pa
                JOIN guardians g ON pa.guardian_id = g.id
                WHERE pa.id = :id
                  AND pa.otp_code = :otp
                  AND pa.otp_expires_at > NOW()
            ");
            $stmt->execute(['id' => $parentId, 'otp' => $otp]);
            $parent = $stmt->fetch();

            if (!$parent) {
                $this->handleFailedOtp($pdo, $parentId);
                flash('error', 'Invalid or expired code. Please try again.');
                Response::back();
            }

            // Clear OTP and setup session
            $this->createParentSession($pdo, $parent);

            // Clear pending OTP session
            unset($_SESSION['parent_otp_pending']);
            unset($_SESSION['parent_otp_phone']);

            Response::redirect('/parent/dashboard');

        } catch (Exception $e) {
            logMessage("OTP verification error: " . $e->getMessage(), 'error');
            flash('error', 'An error occurred. Please try again.');
            Response::back();
        }
    }

    /**
     * Resend OTP
     */
    public function resendOtp()
    {
        if (!isset($_SESSION['parent_otp_pending'])) {
            return Response::json(['success' => false, 'message' => 'Session expired']);
        }

        try {
            $pdo = Database::getTenantConnection();
            $parentId = $_SESSION['parent_otp_pending'];
            $phone = $_SESSION['parent_otp_phone'];

            // Generate new OTP
            $otp = $this->generateOTP();
            $otpExpiry = $this->getPortalSetting('otp_expiry_minutes') ?: 5;

            $stmt = $pdo->prepare("
                UPDATE parent_accounts
                SET otp_code = :otp,
                    otp_expires_at = DATE_ADD(NOW(), INTERVAL :expiry MINUTE)
                WHERE id = :id
            ");
            $stmt->execute(['otp' => $otp, 'expiry' => $otpExpiry, 'id' => $parentId]);

            // Send OTP via SMS
            $this->sendOtpSms($pdo, $parentId, $phone, $otp);

            return Response::json(['success' => true, 'message' => 'New code sent']);

        } catch (Exception $e) {
            logMessage("Resend OTP error: " . $e->getMessage(), 'error');
            return Response::json(['success' => false, 'message' => 'Failed to resend code']);
        }
    }

    /**
     * Show registration page
     */
    public function showRegister()
    {
        if ($this->isParentLoggedIn()) {
            Response::redirect('/parent/dashboard');
        }

        // Check if self-registration is enabled
        if (!$this->getPortalSetting('self_registration')) {
            flash('error', 'Self-registration is not available. Please contact the school.');
            Response::redirect('/parent/login');
        }

        $schoolName = $this->getSchoolName();
        Response::view('parent.register', ['schoolName' => $schoolName]);
    }

    /**
     * Process registration
     * Validates ID + Phone match in guardians table, sends magic link
     */
    public function register()
    {
        if (!$this->getPortalSetting('self_registration')) {
            flash('error', 'Self-registration is not available.');
            Response::redirect('/parent/login');
        }

        $idNumber = trim(Request::get('id_number'));
        $phone = $this->normalizePhone(Request::get('phone'));
        $termsAccepted = Request::get('terms');
        $csrfToken = Request::get(env('CSRF_TOKEN_NAME', '_csrf_token'));

        if (!verifyCsrfToken($csrfToken)) {
            flash('error', 'Invalid security token.');
            storeOldInput(['id_number' => $idNumber, 'phone' => Request::get('phone')]);
            Response::back();
        }

        // Validation
        $errors = [];
        if (empty($idNumber)) $errors[] = 'National ID / Passport number is required';
        if (empty($phone)) $errors[] = 'Phone number is required';
        if (!$termsAccepted) $errors[] = 'You must accept the Terms of Service';

        if (!empty($errors)) {
            flash('error', implode('<br>', $errors));
            storeOldInput(['id_number' => $idNumber, 'phone' => Request::get('phone')]);
            Response::back();
        }

        // Track registration attempts in session (per IP/session to prevent enumeration)
        $maxAttempts = 5;
        $attemptKey = 'registration_attempts';
        $attemptTimeKey = 'registration_attempt_time';
        $attemptWindow = 3600; // 1 hour window

        // Reset counter if window expired
        if (isset($_SESSION[$attemptTimeKey]) && (time() - $_SESSION[$attemptTimeKey]) > $attemptWindow) {
            unset($_SESSION[$attemptKey]);
            unset($_SESSION[$attemptTimeKey]);
        }

        // Initialize or increment attempt counter
        if (!isset($_SESSION[$attemptKey])) {
            $_SESSION[$attemptKey] = 0;
            $_SESSION[$attemptTimeKey] = time();
        }
        $_SESSION[$attemptKey]++;

        // Check if attempts exceeded - show contact school message
        if ($_SESSION[$attemptKey] >= $maxAttempts) {
            $_SESSION['registration_attempts_exceeded'] = true;
        }

        try {
            $pdo = Database::getTenantConnection();

            // Check if account already exists for this phone
            $stmt = $pdo->prepare("SELECT id FROM parent_accounts WHERE phone = :phone");
            $stmt->execute(['phone' => $phone]);
            if ($stmt->fetch()) {
                // Don't reveal that account exists - redirect to confirmation page
                Response::redirect('/parent/registration-sent');
            }

            // Find guardian by ID number AND phone (compare last 9 digits to handle format differences)
            // Phone might be stored as 0722xxx, 254722xxx, or +254722xxx
            $phoneDigits = preg_replace('/[^0-9]/', '', $phone);
            $phoneLast9 = substr($phoneDigits, -9);

            $stmt = $pdo->prepare("
                SELECT id as guardian_id, first_name, last_name, phone
                FROM guardians
                WHERE id_number = :id_number
                AND RIGHT(REGEXP_REPLACE(phone, '[^0-9]', ''), 9) = :phone_last9
                LIMIT 1
            ");
            $stmt->execute(['id_number' => $idNumber, 'phone_last9' => $phoneLast9]);
            $guardian = $stmt->fetch();

            if (!$guardian) {
                // Don't reveal that guardian doesn't exist - redirect to confirmation page
                Response::redirect('/parent/registration-sent');
            }

            // Check if guardian already has a parent account
            $stmt = $pdo->prepare("SELECT id FROM parent_accounts WHERE guardian_id = :guardian_id");
            $stmt->execute(['guardian_id' => $guardian['guardian_id']]);
            if ($stmt->fetch()) {
                // Don't reveal that account exists - redirect to confirmation page
                Response::redirect('/parent/registration-sent');
            }

            // Valid registration - reset attempt counter since this is a real user
            unset($_SESSION[$attemptKey]);
            unset($_SESSION[$attemptTimeKey]);

            // Create parent account
            $magicToken = bin2hex(random_bytes(32));
            $linkExpiryHours = $this->getPortalSetting('magic_link_expiry_hours') ?: 1;

            $stmt = $pdo->prepare("
                INSERT INTO parent_accounts
                (guardian_id, phone, status, magic_link_token, magic_link_expires_at, terms_accepted_at, terms_accepted_ip)
                VALUES
                (:guardian_id, :phone, 'pending', :token, DATE_ADD(NOW(), INTERVAL :expiry HOUR), NOW(), :ip)
            ");

            $stmt->execute([
                'guardian_id' => $guardian['guardian_id'],
                'phone' => $phone,
                'token' => $magicToken,
                'expiry' => $linkExpiryHours,
                'ip' => Request::ip()
            ]);

            $parentAccountId = $pdo->lastInsertId();

            // Send magic link via SMS
            $this->sendMagicLinkSms($pdo, $parentAccountId, $phone, $magicToken, $guardian['first_name']);

            flash('success', 'Registration successful! Please check your phone for a verification link.');
            Response::redirect('/parent/registration-sent');

        } catch (Exception $e) {
            logMessage("Parent registration error: " . $e->getMessage(), 'error');
            flash('error', 'Registration failed. Please try again.');
            storeOldInput(['id_number' => $idNumber, 'phone' => Request::get('phone')]);
            Response::back();
        }
    }

    /**
     * Show registration sent confirmation page
     */
    public function showRegistrationSent()
    {
        $schoolName = $this->getSchoolName();
        Response::view('parent.registration-sent', ['schoolName' => $schoolName]);
    }

    /**
     * Handle magic link click (from SMS)
     */
    public function verifyMagicLink($token)
    {
        try {
            $pdo = Database::getTenantConnection();

            // Find valid token
            $stmt = $pdo->prepare("
                SELECT pa.*, g.first_name, g.last_name
                FROM parent_accounts pa
                JOIN guardians g ON pa.guardian_id = g.id
                WHERE pa.magic_link_token = :token
                  AND pa.magic_link_expires_at > NOW()
            ");
            $stmt->execute(['token' => $token]);
            $parent = $stmt->fetch();

            if (!$parent) {
                flash('error', 'This link has expired or is invalid. Please register again.');
                Response::redirect('/parent/register');
            }

            // Activate account and clear magic link
            $stmt = $pdo->prepare("
                UPDATE parent_accounts
                SET status = 'active',
                    magic_link_token = NULL,
                    magic_link_expires_at = NULL
                WHERE id = :id
            ");
            $stmt->execute(['id' => $parent['id']]);

            // Create session
            $this->createParentSession($pdo, $parent);

            flash('success', 'Welcome to the Parent Portal! Please link your child to continue.');
            Response::redirect('/parent/dashboard');

        } catch (Exception $e) {
            logMessage("Magic link verification error: " . $e->getMessage(), 'error');
            flash('error', 'An error occurred. Please try again.');
            Response::redirect('/parent/login');
        }
    }

    /**
     * Logout
     */
    public function logout()
    {
        // Clear session token in database
        if (isset($_SESSION['parent_id'])) {
            try {
                $pdo = Database::getTenantConnection();
                $stmt = $pdo->prepare("UPDATE parent_accounts SET session_token = NULL, session_expires_at = NULL WHERE id = :id");
                $stmt->execute(['id' => $_SESSION['parent_id']]);
            } catch (Exception $e) {
                // Ignore errors during logout
            }
        }

        // Clear session variables
        unset($_SESSION['parent_logged_in']);
        unset($_SESSION['parent_id']);
        unset($_SESSION['parent_guardian_id']);
        unset($_SESSION['parent_phone']);
        unset($_SESSION['parent_name']);
        unset($_SESSION['parent_children']);

        flash('success', 'You have been logged out.');
        Response::redirect('/parent/login');
    }

    /**
     * Create parent session after successful auth
     */
    private function createParentSession($pdo, $parent)
    {
        // Generate session token for persistent login
        $sessionToken = bin2hex(random_bytes(32));
        $sessionDays = $this->getPortalSetting('session_expiry_days') ?: 30;

        $stmt = $pdo->prepare("
            UPDATE parent_accounts
            SET otp_code = NULL,
                otp_expires_at = NULL,
                session_token = :token,
                session_expires_at = DATE_ADD(NOW(), INTERVAL :days DAY),
                last_login_at = NOW(),
                last_login_ip = :ip,
                failed_login_attempts = 0,
                locked_until = NULL
            WHERE id = :id
        ");
        $stmt->execute([
            'token' => $sessionToken,
            'days' => $sessionDays,
            'ip' => Request::ip(),
            'id' => $parent['id']
        ]);

        // Get linked students (only active/approved links)
        $stmt = $pdo->prepare("
            SELECT s.id, s.first_name, s.last_name, s.admission_number,
                   g.grade_name as class_name, g.id as class_id, sg.link_status
            FROM students s
            JOIN student_guardians sg ON s.id = sg.student_id
            LEFT JOIN student_enrollments se ON s.id = se.student_id AND se.is_current = 1
            LEFT JOIN streams st ON se.stream_id = st.id
            LEFT JOIN grades g ON st.grade_id = g.id
            WHERE sg.guardian_id = :guardian_id
            AND sg.link_status = 'active'
            AND s.status = 'active'
            ORDER BY s.first_name
        ");
        $stmt->execute(['guardian_id' => $parent['guardian_id']]);
        $children = $stmt->fetchAll();

        // Get pending linkage requests
        $stmt = $pdo->prepare("
            SELECT id, admission_number, grade_name, status, created_at
            FROM parent_student_requests
            WHERE parent_account_id = :parent_id
            ORDER BY created_at DESC
        ");
        $stmt->execute(['parent_id' => $parent['id']]);
        $pendingRequests = $stmt->fetchAll();

        // Create session
        $_SESSION['parent_logged_in'] = true;
        $_SESSION['parent_id'] = $parent['id'];
        $_SESSION['parent_guardian_id'] = $parent['guardian_id'];
        $_SESSION['parent_phone'] = $parent['phone'];
        $_SESSION['parent_name'] = $parent['first_name'] . ' ' . $parent['last_name'];
        $_SESSION['parent_children'] = $children;
        $_SESSION['parent_pending_requests'] = $pendingRequests;
        $_SESSION['parent_session_token'] = $sessionToken;
        $_SESSION['tenant_id'] = Database::getCurrentTenant()['id'];

        // Set cookie for persistent login
        setcookie('parent_session', $sessionToken, time() + ($sessionDays * 86400), '/', '', true, true);
    }

    /**
     * Generate 5-digit OTP
     */
    private function generateOTP()
    {
        return str_pad(random_int(0, 99999), 5, '0', STR_PAD_LEFT);
    }

    /**
     * Send OTP via SMS using message queue
     */
    private function sendOtpSms($pdo, $parentId, $phone, $otp)
    {
        $schoolName = $this->getSchoolName();

        // Get guardian name
        $stmt = $pdo->prepare("
            SELECT g.first_name, g.last_name
            FROM parent_accounts pa
            JOIN guardians g ON pa.guardian_id = g.id
            WHERE pa.id = :id
        ");
        $stmt->execute(['id' => $parentId]);
        $guardian = $stmt->fetch();
        $guardianName = $guardian ? $guardian['first_name'] . ' ' . $guardian['last_name'] : 'Parent';

        // Get template
        $stmt = $pdo->prepare("SELECT * FROM communication_templates WHERE template_code = 'parent_portal_login_otp' AND is_active = 1");
        $stmt->execute();
        $template = $stmt->fetch();

        if ($template) {
            // Substitute variables
            $message = $template['sms_body'];
            $message = str_replace('{{school_name}}', $schoolName, $message);
            $message = str_replace('{{guardian_name}}', $guardianName, $message);
            $message = str_replace('{{code}}', $otp, $message);
        } else {
            // Fallback message
            $message = "{$schoolName} Parent Portal: Your login code is {$otp}. Valid for 5 minutes. Do not share this code.";
        }

        // Queue the SMS message
        $this->queueSmsMessage($pdo, $phone, $guardianName, 'parent_portal_login_otp', $message, 'parent_account', $parentId);

        // Also log in parent SMS log
        $this->logSms($pdo, $parentId, $phone, 'otp', 'OTP code sent');

        return true;
    }

    /**
     * Send magic link via SMS using message queue
     */
    private function sendMagicLinkSms($pdo, $parentId, $phone, $token, $firstName)
    {
        $tenant = Database::getCurrentTenant();
        $tenantDomain = $tenant['domain'] ?? 'demo.schooldynamics.local';
        $schoolName = $tenant['name'] ?? 'School';

        // Build magic link URL
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $link = "{$protocol}://{$tenantDomain}/parent/verify/{$token}";

        // Get template
        $stmt = $pdo->prepare("SELECT * FROM communication_templates WHERE template_code = 'parent_portal_registration' AND is_active = 1");
        $stmt->execute();
        $template = $stmt->fetch();

        if ($template) {
            // Substitute variables
            $message = $template['sms_body'];
            $message = str_replace('{{school_name}}', $schoolName, $message);
            $message = str_replace('{{guardian_name}}', $firstName, $message);
            $message = str_replace('{{link}}', $link, $message);
        } else {
            // Fallback message
            $message = "Dear {$firstName}, welcome to {$schoolName} Parent Portal! Click to activate your account: {$link} This link expires in 1 hour.";
        }

        // Queue the SMS message
        $this->queueSmsMessage($pdo, $phone, $firstName, 'parent_portal_registration', $message, 'parent_account', $parentId);

        // Also log in parent SMS log
        $this->logSms($pdo, $parentId, $phone, 'registration_link', 'Magic link sent');

        return true;
    }

    /**
     * Queue SMS message for sending
     */
    private function queueSmsMessage($pdo, $phone, $recipientName, $messageType, $messageBody, $entityType = null, $entityId = null)
    {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO message_queue (
                    channel, message_type,
                    recipient_name, recipient_phone,
                    subject, message_body,
                    priority, status,
                    related_entity_type, related_entity_id,
                    campus_id, created_by
                ) VALUES (
                    'sms', :message_type,
                    :recipient_name, :phone,
                    NULL, :message_body,
                    1, 'queued',
                    :entity_type, :entity_id,
                    :campus_id, NULL
                )
            ");

            // Get campus_id from session or default to 1
            $campusId = $_SESSION['campus_id'] ?? 1;

            $stmt->execute([
                'message_type' => $messageType,
                'recipient_name' => $recipientName,
                'phone' => $phone,
                'message_body' => $messageBody,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'campus_id' => $campusId
            ]);

            $messageId = $pdo->lastInsertId();
            logMessage("SMS queued (ID: {$messageId}) to {$phone}: {$messageType}", 'info');

            return $messageId;

        } catch (Exception $e) {
            logMessage("Failed to queue SMS: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Log SMS for audit
     */
    private function logSms($pdo, $parentId, $phone, $type, $content)
    {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO parent_sms_log (parent_account_id, phone_number, message_type, message_content, status, sent_at)
                VALUES (:parent_id, :phone, :type, :content, 'sent', NOW())
            ");
            $stmt->execute([
                'parent_id' => $parentId,
                'phone' => $phone,
                'type' => $type,
                'content' => substr($content, 0, 50) . '...' // Mask content
            ]);
        } catch (Exception $e) {
            logMessage("SMS log error: " . $e->getMessage(), 'error');
        }
    }

    /**
     * Handle failed OTP attempt
     */
    private function handleFailedOtp($pdo, $parentId)
    {
        $maxAttempts = 5;
        $lockoutDuration = 900; // 15 minutes

        $stmt = $pdo->prepare("
            UPDATE parent_accounts
            SET failed_login_attempts = failed_login_attempts + 1,
                locked_until = CASE
                    WHEN failed_login_attempts + 1 >= :max_attempts
                    THEN DATE_ADD(NOW(), INTERVAL :lockout SECOND)
                    ELSE locked_until
                END
            WHERE id = :id
        ");

        $stmt->execute([
            'max_attempts' => $maxAttempts,
            'lockout' => $lockoutDuration,
            'id' => $parentId
        ]);
    }

    /**
     * Normalize phone number (Kenya format)
     */
    private function normalizePhone($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Convert 07xx to 254xxx
        if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
            $phone = '254' . substr($phone, 1);
        }

        // Add 254 if just 9 digits starting with 7
        if (strlen($phone) === 9 && substr($phone, 0, 1) === '7') {
            $phone = '254' . $phone;
        }

        return $phone;
    }

    /**
     * Mask phone for display
     */
    private function maskPhone($phone)
    {
        if (strlen($phone) > 6) {
            return substr($phone, 0, 4) . '****' . substr($phone, -2);
        }
        return '****';
    }

    /**
     * Check if parent is logged in
     */
    private function isParentLoggedIn()
    {
        return isset($_SESSION['parent_logged_in']) && $_SESSION['parent_logged_in'] === true;
    }

    /**
     * Get portal setting
     */
    private function getPortalSetting($key)
    {
        static $settings = null;

        if ($settings === null) {
            try {
                $pdo = Database::getTenantConnection();
                $stmt = $pdo->query("SELECT setting_key, setting_value, setting_type FROM parent_portal_settings");
                $rows = $stmt->fetchAll();

                $settings = [];
                foreach ($rows as $row) {
                    $value = $row['setting_value'];
                    switch ($row['setting_type']) {
                        case 'boolean':
                            $value = $value === 'true' || $value === '1';
                            break;
                        case 'integer':
                            $value = (int) $value;
                            break;
                        case 'json':
                            $value = json_decode($value, true);
                            break;
                    }
                    $settings[$row['setting_key']] = $value;
                }
            } catch (Exception $e) {
                $settings = [];
            }
        }

        return $settings[$key] ?? null;
    }

    /**
     * Get school name from tenant
     */
    private function getSchoolName()
    {
        try {
            $tenant = Database::getCurrentTenant();
            return $tenant['name'] ?? 'School';
        } catch (Exception $e) {
            return 'School';
        }
    }
}
