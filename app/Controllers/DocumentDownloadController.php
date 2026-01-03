<?php
/**
 * Document Download Controller
 *
 * Standalone controller for handling document downloads with audit logging.
 * Designed to be reusable across all modules (Applicants, Students, Staff, etc.)
 *
 * @author Claude Code Assistant
 * @date November 2025
 */

class DocumentDownloadController
{
    /**
     * Download a document with audit logging
     *
     * @param string $entityType Entity type (applicants, students, staff)
     * @param int $documentId Document ID
     */
    public function download($entityType, $documentId)
    {
        // Verify authentication
        if (!isAuthenticated()) {
            http_response_code(403);
            die('Unauthorized');
        }

        try {
            // Log parameters for debugging
            logMessage("Download request - Entity: $entityType, Doc ID: $documentId", 'debug');

            $pdo = Database::getTenantConnection();

            // Derive table names from entity type
            $documentTable = rtrim($entityType, 's') . '_documents';
            $entityTable = $entityType;
            $foreignKey = rtrim($entityType, 's') . '_id';
            $auditTable = rtrim($entityType, 's') . '_audit';

            logMessage("Download - Tables: doc=$documentTable, entity=$entityTable, fk=$foreignKey", 'debug');

            // Get document info with campus check
            $stmt = $pdo->prepare("
                SELECT d.*, e.campus_id
                FROM {$documentTable} d
                JOIN {$entityTable} e ON d.{$foreignKey} = e.id
                WHERE d.id = ?
            ");
            $stmt->execute([$documentId]);
            $document = $stmt->fetch();

            if (!$document) {
                logMessage("Download - Document not found: ID $documentId", 'error');
                http_response_code(404);
                die('Document not found');
            }

            logMessage("Download - Found document: " . $document['file_name'], 'debug');

            // Verify campus access
            $currentCampusId = $_SESSION['current_campus_id'] ?? null;
            if ($currentCampusId && $currentCampusId !== 'all' && $document['campus_id'] != $currentCampusId) {
                http_response_code(403);
                die('Access denied');
            }

            // Build file path
            $filepath = __DIR__ . '/../../' . $document['file_path'];

            logMessage("Download - File path: $filepath", 'debug');
            logMessage("Download - File exists: " . (file_exists($filepath) ? 'YES' : 'NO'), 'debug');

            if (!file_exists($filepath)) {
                logMessage("Download - File not found at: $filepath", 'error');
                http_response_code(404);
                die('File not found on server: ' . $filepath);
            }

            // Log download to audit table
            $stmt = $pdo->prepare("
                INSERT INTO document_downloads (
                    document_id, {$foreignKey}, document_type, file_name,
                    downloaded_by, ip_address, user_agent, downloaded_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $document['id'],
                $document[$foreignKey],
                $document['document_type'],
                $document['file_name'],
                authUserId(),
                Request::ip(),
                Request::userAgent()
            ]);

            // Also log to entity audit table
            $stmt = $pdo->prepare("
                INSERT INTO {$auditTable} (
                    {$foreignKey}, action, description, user_id, ip_address, user_agent, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $document[$foreignKey],
                'document_downloaded',
                "Document downloaded: " . $document['document_type'] . " (" . $document['file_name'] . ")",
                authUserId(),
                Request::ip(),
                Request::userAgent()
            ]);

            // Clear ALL output buffers
            while (ob_get_level()) {
                ob_end_clean();
            }

            // Disable error output
            @ini_set('display_errors', 0);
            error_reporting(0);

            // Disable compression
            if (function_exists('apache_setenv')) {
                @apache_setenv('no-gzip', '1');
            }
            @ini_set('zlib.output_compression', '0');

            // Send download headers
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($document['file_name']) . '"');
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: ' . filesize($filepath));
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Expires: 0');

            // Output file directly
            readfile($filepath);

            // Terminate immediately
            exit;

        } catch (Exception $e) {
            logMessage("Download error: " . $e->getMessage(), 'error');
            http_response_code(500);
            die('Download failed: ' . $e->getMessage());
        }
    }
}
