<?php
/**
 * Audit Service
 * Async audit logging using message queue for better performance
 *
 * Benefits:
 * - Non-blocking: Audit logs don't slow down main operations
 * - Scalable: Can be processed by background workers
 * - Reliable: Queued messages won't be lost
 * - Flexible: Easy to modify without database migrations
 */

class AuditService
{
    /**
     * Queue an audit log entry (async via message queue)
     *
     * @param array $params [
     *   'authorization_request_id' => int,
     *   'action' => string,
     *   'action_description' => string,
     *   'actor_type' => string,
     *   'actor_id' => int|null,
     *   'actor_name' => string|null,
     *   'ip_address' => string|null,
     *   'user_agent' => string|null,
     *   'location' => string|null,
     *   'contact_method' => string|null,
     *   'metadata' => array,
     *   'campus_id' => int,
     *   'created_by' => int
     * ]
     * @return bool Success status
     */
    public static function queueAuditLog(array $params): bool
    {
        try {
            $pdo = Database::getTenantConnection();

            // Prepare audit data as message body
            $auditData = [
                'authorization_request_id' => $params['authorization_request_id'],
                'action' => $params['action'],
                'action_description' => $params['action_description'] ?? '',
                'actor_type' => $params['actor_type'] ?? 'system',
                'actor_id' => $params['actor_id'] ?? null,
                'actor_name' => $params['actor_name'] ?? self::getActorName($params),
                'ip_address' => $params['ip_address'] ?? null,
                'user_agent' => $params['user_agent'] ?? null,
                'location' => $params['location'] ?? null,
                'contact_method' => $params['contact_method'] ?? null,
                'metadata' => $params['metadata'] ?? []
            ];

            // Queue as a special message type
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
                'system', // channel (not SMS/Email, just for queue processing)
                'audit_log',
                'System', // recipient_name (not applicable for audit)
                null, // recipient_email
                null, // recipient_phone
                'Authorization Audit Log', // subject
                json_encode($auditData), // message_body contains audit data
                10, // Low priority (audit logs are important but not urgent)
                'queued',
                'authorization_audit',
                $params['authorization_request_id'],
                $params['campus_id'],
                $params['created_by']
            ]);

            return true;

        } catch (Exception $e) {
            // Don't fail the main operation if audit queueing fails
            logMessage("Failed to queue audit log: " . $e->getMessage(), 'warning');
            return false;
        }
    }

    /**
     * Process audit log from queue (called by background worker)
     *
     * @param array $auditData Audit data from message_body
     * @return bool Success status
     */
    public static function processAuditLog(array $auditData): bool
    {
        try {
            $pdo = Database::getTenantConnection();

            $stmt = $pdo->prepare("
                INSERT INTO authorization_audit (
                    authorization_request_id,
                    action,
                    action_description,
                    actor_type,
                    actor_id,
                    actor_name,
                    ip_address,
                    user_agent,
                    location,
                    contact_method,
                    metadata
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $auditData['authorization_request_id'],
                $auditData['action'],
                $auditData['action_description'],
                $auditData['actor_type'],
                $auditData['actor_id'],
                $auditData['actor_name'],
                $auditData['ip_address'],
                $auditData['user_agent'],
                $auditData['location'],
                $auditData['contact_method'],
                json_encode($auditData['metadata'])
            ]);

            return true;

        } catch (Exception $e) {
            logMessage("Failed to process audit log: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Log authorization request creation
     *
     * @param int $requestId
     * @param string $entityType
     * @param int $entityId
     * @param string $requestType
     * @param string $recipientName
     * @param int $campusId
     * @param int $userId
     */
    public static function logAuthorizationCreated(
        int $requestId,
        string $entityType,
        int $entityId,
        string $requestType,
        string $recipientName,
        int $campusId,
        int $userId
    ): void {
        self::queueAuditLog([
            'authorization_request_id' => $requestId,
            'action' => 'created',
            'action_description' => "Authorization request created for {$entityType} #{$entityId} ({$requestType})",
            'actor_type' => 'staff',
            'actor_id' => $userId,
            'metadata' => [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'request_type' => $requestType,
                'recipient_name' => $recipientName
            ],
            'campus_id' => $campusId,
            'created_by' => $userId
        ]);
    }

    /**
     * Log authorization status change
     *
     * @param int $requestId
     * @param string $oldStatus
     * @param string $newStatus
     * @param string $authMethod
     * @param array $details Additional details
     * @param int $campusId
     * @param int $userId
     */
    public static function logStatusChange(
        int $requestId,
        string $oldStatus,
        string $newStatus,
        string $authMethod,
        array $details,
        int $campusId,
        int $userId
    ): void {
        // Determine actor type based on method
        $actorType = 'system';
        if ($authMethod === 'code_staff') {
            $actorType = 'staff';
        } elseif ($authMethod === 'link') {
            $actorType = 'guardian';
        }

        self::queueAuditLog([
            'authorization_request_id' => $requestId,
            'action' => $newStatus,
            'action_description' => "Status changed from {$oldStatus} to {$newStatus}",
            'actor_type' => $actorType,
            'actor_id' => $details['actor_id'] ?? null,
            'ip_address' => $details['ip_address'] ?? null,
            'user_agent' => $details['user_agent'] ?? null,
            'location' => $details['location'] ?? null,
            'contact_method' => $details['contact_method'] ?? null,
            'metadata' => [
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'authorization_method' => $authMethod,
                'staff_notes' => $details['staff_notes'] ?? null
            ],
            'campus_id' => $campusId,
            'created_by' => $userId
        ]);
    }

    /**
     * Get actor name based on actor type and ID
     *
     * @param array $params
     * @return string
     */
    private static function getActorName(array $params): string
    {
        static $cache = [];

        if (!empty($params['actor_name'])) {
            return $params['actor_name'];
        }

        $actorId = $params['actor_id'] ?? null;
        if (!$actorId) {
            return $params['actor_type'] === 'guardian' ? 'Guardian' : 'System';
        }

        if (isset($cache[$actorId])) {
            return $cache[$actorId];
        }

        try {
            $pdo = Database::getTenantConnection();
            $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
            $stmt->execute([$actorId]);
            $name = $stmt->fetchColumn();
            $cache[$actorId] = $name ?: 'Unknown User';
            return $cache[$actorId];
        } catch (Exception $e) {
            return 'Unknown User';
        }
    }

    /**
     * Sync processing of audit log (for immediate needs)
     * Only use when absolutely necessary - prefer queueAuditLog for better performance
     *
     * @param array $params Same as queueAuditLog
     * @return bool Success status
     */
    public static function logImmediately(array $params): bool
    {
        try {
            $pdo = Database::getTenantConnection();

            $stmt = $pdo->prepare("
                INSERT INTO authorization_audit (
                    authorization_request_id,
                    action,
                    action_description,
                    actor_type,
                    actor_id,
                    actor_name,
                    ip_address,
                    user_agent,
                    location,
                    contact_method,
                    metadata
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $params['authorization_request_id'],
                $params['action'],
                $params['action_description'] ?? '',
                $params['actor_type'] ?? 'system',
                $params['actor_id'] ?? null,
                $params['actor_name'] ?? self::getActorName($params),
                $params['ip_address'] ?? null,
                $params['user_agent'] ?? null,
                $params['location'] ?? null,
                $params['contact_method'] ?? null,
                json_encode($params['metadata'] ?? [])
            ]);

            return true;

        } catch (Exception $e) {
            logMessage("Failed to log audit immediately: " . $e->getMessage(), 'warning');
            return false;
        }
    }
}
