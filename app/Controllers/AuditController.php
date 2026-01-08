<?php
/**
 * Audit Controller
 * Handles field audit trail operations
 */

class AuditController
{
    /**
     * Check if a field has audit history
     * Returns JSON with has_changes and change_count
     */
    public function checkFieldHistory()
    {
        if (!isAuthenticated()) {
            Response::json(['success' => false, 'message' => 'Authentication required'], 401);
            return;
        }

        try {
            $entityType = Request::get('entity_type');
            $entityId = Request::get('entity_id');
            $fieldName = Request::get('field_name');

            if (empty($entityType) || empty($entityId) || empty($fieldName)) {
                Response::json(['success' => false, 'message' => 'Missing required parameters']);
                return;
            }

            $pdo = Database::getTenantConnection();

            $stmt = $pdo->prepare("
                SELECT COUNT(*) as change_count
                FROM field_audit_trail
                WHERE entity_type = ? AND entity_id = ? AND field_name = ?
            ");
            $stmt->execute([$entityType, $entityId, $fieldName]);
            $result = $stmt->fetch();

            Response::json([
                'success' => true,
                'has_changes' => $result['change_count'] > 0,
                'change_count' => (int) $result['change_count']
            ]);

        } catch (Exception $e) {
            logMessage("Audit check error: " . $e->getMessage(), 'error');
            Response::json(['success' => false, 'message' => 'Error checking audit history'], 500);
        }
    }

    /**
     * Get field audit history
     * Returns JSON with history array
     */
    public function getFieldHistory()
    {
        if (!isAuthenticated()) {
            Response::json(['success' => false, 'message' => 'Authentication required'], 401);
            return;
        }

        try {
            $entityType = Request::get('entity_type');
            $entityId = Request::get('entity_id');
            $fieldName = Request::get('field_name');

            if (empty($entityType) || empty($entityId) || empty($fieldName)) {
                Response::json(['success' => false, 'message' => 'Missing required parameters']);
                return;
            }

            $pdo = Database::getTenantConnection();

            $stmt = $pdo->prepare("
                SELECT
                    id,
                    old_value,
                    new_value,
                    changed_by_name,
                    change_reason,
                    DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') as created_at
                FROM field_audit_trail
                WHERE entity_type = ? AND entity_id = ? AND field_name = ?
                ORDER BY created_at DESC
                LIMIT 50
            ");
            $stmt->execute([$entityType, $entityId, $fieldName]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::json([
                'success' => true,
                'history' => $history
            ]);

        } catch (Exception $e) {
            logMessage("Audit history error: " . $e->getMessage(), 'error');
            Response::json(['success' => false, 'message' => 'Error fetching audit history'], 500);
        }
    }

    /**
     * Get all audit history for an entity
     * Useful for viewing complete change history on a record
     */
    public function getEntityHistory($entityType, $entityId)
    {
        if (!isAuthenticated()) {
            Response::json(['success' => false, 'message' => 'Authentication required'], 401);
            return;
        }

        try {
            $pdo = Database::getTenantConnection();

            // Get field display names
            $stmt = $pdo->prepare("
                SELECT field_name, display_name
                FROM sensitive_fields
                WHERE entity_type = ?
            ");
            $stmt->execute([$entityType]);
            $fieldNames = [];
            while ($row = $stmt->fetch()) {
                $fieldNames[$row['field_name']] = $row['display_name'];
            }

            // Get audit history
            $stmt = $pdo->prepare("
                SELECT
                    id,
                    field_name,
                    old_value,
                    new_value,
                    changed_by_name,
                    change_reason,
                    DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') as created_at
                FROM field_audit_trail
                WHERE entity_type = ? AND entity_id = ?
                ORDER BY created_at DESC
                LIMIT 100
            ");
            $stmt->execute([$entityType, $entityId]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Add display names to history
            foreach ($history as &$item) {
                $item['field_display_name'] = $fieldNames[$item['field_name']] ?? $item['field_name'];
            }

            Response::json([
                'success' => true,
                'history' => $history
            ]);

        } catch (Exception $e) {
            logMessage("Entity audit history error: " . $e->getMessage(), 'error');
            Response::json(['success' => false, 'message' => 'Error fetching audit history'], 500);
        }
    }
}
