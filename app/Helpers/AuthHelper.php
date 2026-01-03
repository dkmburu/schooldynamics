<?php
/**
 * Authorization Helper
 * Reusable authorization/consent system for any entity type
 *
 * Usage Examples:
 * - Check status: AuthHelper::getStatus('applicant', 123, 'data_consent')
 * - Check if authorized: AuthHelper::isAuthorized('applicant', 123, 'data_consent')
 * - Request authorization: AuthHelper::requestAuthorization([...])
 * - Render badge: AuthHelper::badge('applicant', 123, 'data_consent')
 */

class AuthHelper
{
    /**
     * Check if an entity has valid authorization
     *
     * @param string $entityType Entity type (applicant, student, employee, etc.)
     * @param int $entityId Entity ID
     * @param string $requestType Authorization type (data_consent, photo_consent, etc.)
     * @return bool
     */
    public static function isAuthorized(string $entityType, int $entityId, string $requestType): bool
    {
        try {
            $pdo = Database::getTenantConnection();

            $stmt = $pdo->prepare("
                SELECT id
                FROM authorization_requests
                WHERE entity_type = ?
                  AND entity_id = ?
                  AND request_type = ?
                  AND status = 'approved'
                  AND (expires_at IS NULL OR expires_at > NOW())
                LIMIT 1
            ");

            $stmt->execute([$entityType, $entityId, $requestType]);

            return $stmt->fetch() !== false;

        } catch (Exception $e) {
            logMessage("Error checking authorization: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Get authorization status details
     *
     * @param string $entityType
     * @param int $entityId
     * @param string $requestType
     * @return array|null Array with status details or null if not found
     */
    public static function getStatus(string $entityType, int $entityId, string $requestType): ?array
    {
        try {
            $pdo = Database::getTenantConnection();

            $stmt = $pdo->prepare("
                SELECT
                    id,
                    status,
                    recipient_name,
                    recipient_email,
                    recipient_phone,
                    sent_at,
                    approved_at,
                    expires_at,
                    authorization_method,
                    authorized_by_staff_id,
                    authorization_location,
                    parent_contact_method
                FROM authorization_requests
                WHERE entity_type = ?
                  AND entity_id = ?
                  AND request_type = ?
                ORDER BY created_at DESC
                LIMIT 1
            ");

            $stmt->execute([$entityType, $entityId, $requestType]);
            $result = $stmt->fetch();

            return $result ?: null;

        } catch (Exception $e) {
            logMessage("Error getting authorization status: " . $e->getMessage(), 'error');
            return null;
        }
    }

    /**
     * Get all authorization requests for an entity
     *
     * @param string $entityType
     * @param int $entityId
     * @return array
     */
    public static function getAllForEntity(string $entityType, int $entityId): array
    {
        try {
            $pdo = Database::getTenantConnection();

            $stmt = $pdo->prepare("
                SELECT
                    ar.*,
                    ct.template_name,
                    ct.authorization_type
                FROM authorization_requests ar
                LEFT JOIN communication_templates ct ON ar.message_template = ct.template_code
                WHERE ar.entity_type = ?
                  AND ar.entity_id = ?
                ORDER BY ar.created_at DESC
            ");

            $stmt->execute([$entityType, $entityId]);

            return $stmt->fetchAll();

        } catch (Exception $e) {
            logMessage("Error getting authorization requests: " . $e->getMessage(), 'error');
            return [];
        }
    }

    /**
     * Request authorization from guardian
     *
     * @param array $params [
     *   'entity_type' => 'applicant',
     *   'entity_id' => 123,
     *   'request_type' => 'data_consent',
     *   'recipient_name' => 'John Doe',
     *   'recipient_email' => 'john@example.com',
     *   'recipient_phone' => '+1234567890',
     *   'template_code' => 'data_consent_request',
     *   'channels' => ['sms', 'email'],
     *   'campus_id' => 1,
     *   'created_by' => 5,
     *   'context_data' => [...] // Additional data for template variables
     * ]
     * @return array ['success' => bool, 'message' => string, 'request_id' => int|null]
     */
    public static function requestAuthorization(array $params): array
    {
        try {
            // Validate required params
            $required = ['entity_type', 'entity_id', 'request_type', 'recipient_name',
                        'template_code', 'campus_id', 'created_by'];
            foreach ($required as $field) {
                if (empty($params[$field])) {
                    return ['success' => false, 'message' => "Missing required field: $field"];
                }
            }

            // Get template
            $template = self::getTemplate($params['template_code']);
            if (!$template) {
                return ['success' => false, 'message' => 'Template not found'];
            }

            // Generate secure token and verification code
            $token = bin2hex(random_bytes(32));
            $verificationCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Build authorization URL
            $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
            $authUrl = $baseUrl . "/authorize/" . $token;

            // Calculate expiry date
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$template['validity_days']} days"));

            // Prepare template variables
            $contextData = $params['context_data'] ?? [];
            $variables = array_merge($contextData, [
                'code' => $verificationCode,
                'link' => $authUrl,
                'validity_days' => $template['validity_days'],
                'guardian_name' => $params['recipient_name']
            ]);

            // Substitute variables in message
            $subject = self::substituteVariables($template['subject'], $variables);
            $smsBody = self::substituteVariables($template['sms_body'], $variables);
            $emailBody = self::substituteVariables($template['email_body'], $variables);

            // Insert authorization request
            $pdo = Database::getTenantConnection();

            $stmt = $pdo->prepare("
                INSERT INTO authorization_requests (
                    entity_type, entity_id, request_type,
                    recipient_name, recipient_email, recipient_phone,
                    verification_code, token,
                    message_template, subject, message_body,
                    channels_sent, status, sent_at, expires_at,
                    campus_id, created_by
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), ?, ?, ?
                )
            ");

            $stmt->execute([
                $params['entity_type'],
                $params['entity_id'],
                $params['request_type'],
                $params['recipient_name'],
                $params['recipient_email'] ?? null,
                $params['recipient_phone'] ?? null,
                $verificationCode,
                $token,
                $params['template_code'],
                $subject,
                json_encode(['sms' => $smsBody, 'email' => $emailBody]),
                json_encode($params['channels'] ?? ['sms', 'email']),
                $expiresAt,
                $params['campus_id'],
                $params['created_by']
            ]);

            $requestId = $pdo->lastInsertId();

            // Queue messages for each requested channel
            $queuedChannels = [];
            $channels = $params['channels'] ?? ['sms', 'email'];

            foreach ($channels as $channel) {
                // Skip if channel doesn't have required recipient info
                if ($channel === 'sms' && empty($params['recipient_phone'])) continue;
                if ($channel === 'email' && empty($params['recipient_email'])) continue;
                if ($channel === 'whatsapp' && empty($params['recipient_phone'])) continue;

                // Determine message body based on channel
                $messageBody = '';
                if ($channel === 'sms') {
                    $messageBody = $smsBody;
                } elseif ($channel === 'email') {
                    $messageBody = $emailBody;
                } elseif ($channel === 'whatsapp') {
                    $messageBody = $smsBody; // Use SMS body for WhatsApp
                }

                // Queue the message
                $stmt = $pdo->prepare("
                    INSERT INTO message_queue (
                        channel, message_type,
                        recipient_name, recipient_email, recipient_phone,
                        subject, message_body,
                        priority, status,
                        related_entity_type, related_entity_id,
                        campus_id, created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $channel,
                    'authorization_request',
                    $params['recipient_name'],
                    $params['recipient_email'] ?? null,
                    $params['recipient_phone'] ?? null,
                    $subject,
                    $messageBody,
                    2, // High priority (1-10 scale, 2=high)
                    'queued',
                    'authorization_request',
                    $requestId,
                    $params['campus_id'],
                    $params['created_by']
                ]);

                $queuedChannels[] = $channel;
            }

            logMessage("Authorization request #{$requestId} created and " . count($queuedChannels) . " message(s) queued. Code: {$verificationCode}, Link: {$authUrl}", 'info');

            // Queue audit log (async, non-blocking)
            AuditService::logAuthorizationCreated(
                $requestId,
                $params['entity_type'],
                $params['entity_id'],
                $params['request_type'],
                $params['recipient_name'],
                $params['campus_id'],
                $params['created_by']
            );

            return [
                'success' => true,
                'message' => 'Authorization request created and messages queued for sending',
                'request_id' => $requestId,
                'queued_channels' => $queuedChannels,
                'verification_code' => $verificationCode, // For testing/display
                'authorization_url' => $authUrl // For testing/display
            ];

        } catch (Exception $e) {
            logMessage("Error requesting authorization: " . $e->getMessage(), 'error');
            return [
                'success' => false,
                'message' => 'Failed to send authorization request',
                'error_details' => $e->getMessage(), // Include error for debugging
                'trace' => $e->getTraceAsString()
            ];
        }
    }

    /**
     * Approve authorization request (via link)
     *
     * @param string $token Security token from URL
     * @param array $metadata Additional metadata (IP, user agent, etc.)
     * @return array ['success' => bool, 'message' => string]
     */
    public static function approveByLink(string $token, array $metadata = []): array
    {
        try {
            $pdo = Database::getTenantConnection();

            // Find pending request by token
            $stmt = $pdo->prepare("
                SELECT * FROM authorization_requests
                WHERE token = ?
                  AND status = 'pending'
                  AND expires_at > NOW()
            ");
            $stmt->execute([$token]);
            $request = $stmt->fetch();

            if (!$request) {
                return ['success' => false, 'message' => 'Invalid or expired authorization link'];
            }

            // Update to approved
            $stmt = $pdo->prepare("
                UPDATE authorization_requests
                SET status = 'approved',
                    approved_at = NOW(),
                    authorization_method = 'link',
                    approval_ip = ?,
                    approval_user_agent = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $metadata['ip'] ?? $_SERVER['REMOTE_ADDR'] ?? null,
                $metadata['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? null,
                $request['id']
            ]);

            // Queue audit log for status change
            AuditService::logStatusChange(
                $request['id'],
                $request['status'],
                'approved',
                'link',
                [
                    'actor_id' => null,
                    'ip_address' => $metadata['ip'] ?? $_SERVER['REMOTE_ADDR'] ?? null,
                    'user_agent' => $metadata['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? null
                ],
                $request['campus_id'],
                $request['created_by']
            );

            return [
                'success' => true,
                'message' => 'Authorization approved successfully',
                'request' => $request
            ];

        } catch (Exception $e) {
            logMessage("Error approving authorization: " . $e->getMessage(), 'error');
            return ['success' => false, 'message' => 'Failed to approve authorization'];
        }
    }

    /**
     * Approve authorization request (via code - staff assisted)
     *
     * @param string $code Verification code
     * @param int $staffId Staff member ID entering the code
     * @param array $details ['location', 'contact_method', 'notes']
     * @return array ['success' => bool, 'message' => string]
     */
    public static function approveByCode(string $code, int $staffId, array $details = []): array
    {
        try {
            $pdo = Database::getTenantConnection();

            // Find pending request by code
            $stmt = $pdo->prepare("
                SELECT * FROM authorization_requests
                WHERE verification_code = ?
                  AND status = 'pending'
                  AND expires_at > NOW()
            ");
            $stmt->execute([$code]);
            $request = $stmt->fetch();

            if (!$request) {
                return ['success' => false, 'message' => 'Invalid or expired verification code'];
            }

            // Update to approved
            $stmt = $pdo->prepare("
                UPDATE authorization_requests
                SET status = 'approved',
                    approved_at = NOW(),
                    authorization_method = 'code_staff',
                    authorized_by_staff_id = ?,
                    authorization_location = ?,
                    parent_contact_method = ?,
                    staff_notes = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $staffId,
                $details['location'] ?? 'office',
                $details['contact_method'] ?? 'phone_call',
                $details['notes'] ?? null,
                $request['id']
            ]);

            // Queue audit log for status change
            AuditService::logStatusChange(
                $request['id'],
                $request['status'],
                'approved',
                'code_staff',
                [
                    'actor_id' => $staffId,
                    'location' => $details['location'] ?? 'office',
                    'contact_method' => $details['contact_method'] ?? 'phone_call',
                    'staff_notes' => $details['notes'] ?? null
                ],
                $request['campus_id'],
                $staffId
            );

            return [
                'success' => true,
                'message' => 'Authorization approved successfully',
                'request' => $request
            ];

        } catch (Exception $e) {
            logMessage("Error approving authorization by code: " . $e->getMessage(), 'error');
            return ['success' => false, 'message' => 'Failed to approve authorization'];
        }
    }

    /**
     * Render authorization status badge
     *
     * @param string $entityType
     * @param int $entityId
     * @param string $requestType
     * @return string HTML badge
     */
    public static function badge(string $entityType, int $entityId, string $requestType): string
    {
        $status = self::getStatus($entityType, $entityId, $requestType);

        if (!$status) {
            return '<span class="badge badge-danger"><i class="fas fa-exclamation-circle mr-1"></i>Not Requested</span>';
        }

        $badges = [
            'approved' => '<span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Authorized</span>',
            'pending' => '<span class="badge badge-warning"><i class="fas fa-clock mr-1"></i>Pending Authorization</span>',
            'rejected' => '<span class="badge badge-danger"><i class="fas fa-times-circle mr-1"></i>Declined</span>',
            'expired' => '<span class="badge badge-secondary"><i class="fas fa-hourglass-end mr-1"></i>Expired</span>',
            'revoked' => '<span class="badge badge-dark"><i class="fas fa-ban mr-1"></i>Revoked</span>'
        ];

        return $badges[$status['status']] ?? '<span class="badge badge-secondary">Unknown</span>';
    }

    /**
     * Get communication template
     *
     * @param string $templateCode
     * @return array|null
     */
    private static function getTemplate(string $templateCode): ?array
    {
        try {
            $pdo = Database::getTenantConnection();

            $stmt = $pdo->prepare("
                SELECT * FROM communication_templates
                WHERE template_code = ? AND is_active = 1
            ");
            $stmt->execute([$templateCode]);

            return $stmt->fetch() ?: null;

        } catch (Exception $e) {
            logMessage("Error getting template: " . $e->getMessage(), 'error');
            return null;
        }
    }

    /**
     * Substitute variables in template string
     *
     * @param string $template
     * @param array $variables
     * @return string
     */
    private static function substituteVariables(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $template = str_replace("{{" . $key . "}}", $value, $template);
        }
        return $template;
    }

    /**
     * Get authorization history/audit trail for an entity
     *
     * @param string $entityType
     * @param int $entityId
     * @param string|null $requestType Filter by specific request type (optional)
     * @return array
     */
    public static function getHistory(string $entityType, int $entityId, ?string $requestType = null): array
    {
        try {
            $pdo = Database::getTenantConnection();

            $sql = "
                SELECT
                    ar.id,
                    ar.request_type,
                    ar.recipient_name,
                    ar.verification_code,
                    ar.status,
                    ar.authorization_method,
                    ar.created_at as request_created_at,
                    ar.approved_at,
                    aa.action,
                    aa.action_description,
                    aa.actor_type,
                    aa.actor_name,
                    aa.ip_address,
                    aa.location,
                    aa.contact_method,
                    aa.created_at as action_date
                FROM authorization_requests ar
                LEFT JOIN authorization_audit aa ON ar.id = aa.authorization_request_id
                WHERE ar.entity_type = ?
                  AND ar.entity_id = ?
            ";

            $params = [$entityType, $entityId];

            if ($requestType) {
                $sql .= " AND ar.request_type = ?";
                $params[] = $requestType;
            }

            $sql .= " ORDER BY aa.created_at DESC, ar.created_at DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $history = $stmt->fetchAll();

            // Mask verification codes (show only first 3 digits)
            foreach ($history as &$record) {
                if (!empty($record['verification_code'])) {
                    $code = $record['verification_code'];
                    $record['masked_code'] = substr($code, 0, 3) . '***';
                }
            }

            return $history;

        } catch (Exception $e) {
            logMessage("Error getting authorization history: " . $e->getMessage(), 'error');
            return [];
        }
    }

    /**
     * Mark expired requests as expired (cron job)
     */
    public static function markExpiredRequests(): int
    {
        try {
            $pdo = Database::getTenantConnection();

            $stmt = $pdo->prepare("
                UPDATE authorization_requests
                SET status = 'expired'
                WHERE status = 'pending'
                  AND expires_at <= NOW()
            ");

            $stmt->execute();

            return $stmt->rowCount();

        } catch (Exception $e) {
            logMessage("Error marking expired requests: " . $e->getMessage(), 'error');
            return 0;
        }
    }
}
