<?php
/**
 * Document Upload Service
 *
 * Reusable service for handling document uploads across different entities
 * (Applicants, Students, Staff, etc.)
 *
 * @usage
 * $service = new DocumentUploadService('applicants', 15);
 * $token = $service->generateToken('birth_certificate');
 *
 * @author Claude Code Assistant
 * @date November 2025
 */

class DocumentUploadService
{
    private $entityType;        // 'applicants', 'students', 'staff', etc.
    private $entityId;          // ID of the entity
    private $tableName;         // Database table name (e.g., 'applicant_documents')
    private $foreignKeyColumn;  // Foreign key column name (e.g., 'applicant_id')

    /**
     * Constructor
     *
     * @param string $entityType Type of entity (applicants, students, staff, etc.)
     * @param int $entityId ID of the entity
     */
    public function __construct($entityType, $entityId = null)
    {
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->tableName = rtrim($entityType, 's') . '_documents';
        $this->foreignKeyColumn = rtrim($entityType, 's') . '_id';
    }

    /**
     * Generate a secure upload token for phone camera capture
     *
     * @param string $documentType Type of document being uploaded
     * @return array ['success' => bool, 'token' => string, 'message' => string]
     */
    public function generateToken($documentType)
    {
        try {
            // Verify entity exists
            if (!$this->verifyEntityExists()) {
                return [
                    'success' => false,
                    'message' => ucfirst($this->entityType) . ' not found'
                ];
            }

            // Verify campus access
            if (!$this->verifyCampusAccess()) {
                return [
                    'success' => false,
                    'message' => 'Access denied - entity belongs to different campus'
                ];
            }

            $pdo = Database::getTenantConnection();

            // Generate unique token
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

            // Store token in database
            $stmt = $pdo->prepare("
                INSERT INTO document_upload_tokens (
                    token, {$this->foreignKeyColumn}, document_type, created_by,
                    expires_at, status, created_at
                ) VALUES (?, ?, ?, ?, ?, 'pending', NOW())
            ");

            $stmt->execute([
                $token,
                $this->entityId,
                $documentType,
                authUserId(),
                $expiresAt
            ]);

            return [
                'success' => true,
                'token' => $token
            ];

        } catch (Exception $e) {
            logMessage("DocumentUploadService::generateToken error: " . $e->getMessage(), 'error');
            return [
                'success' => false,
                'message' => 'Failed to generate token: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Upload document from computer
     *
     * @param array $file $_FILES array element
     * @param string $documentType Type of document
     * @param string $notes Optional notes
     * @return array ['success' => bool, 'message' => string, 'document_id' => int]
     */
    public function uploadFromComputer($file, $documentType, $notes = null)
    {
        try {
            // Validate file
            $validation = $this->validateFile($file);
            if (!$validation['success']) {
                return $validation;
            }

            // Verify entity and campus access
            if (!$this->verifyEntityExists()) {
                return ['success' => false, 'message' => 'Entity not found'];
            }

            if (!$this->verifyCampusAccess()) {
                return ['success' => false, 'message' => 'Access denied'];
            }

            // Save file
            $saveResult = $this->saveFile($file, $documentType);
            if (!$saveResult['success']) {
                return $saveResult;
            }

            $pdo = Database::getTenantConnection();
            $pdo->beginTransaction();

            // Insert document record
            $stmt = $pdo->prepare("
                INSERT INTO {$this->tableName} (
                    {$this->foreignKeyColumn}, document_type, file_name, file_path,
                    file_size, mime_type, notes, verification_status, upload_method,
                    uploaded_by, uploaded_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'uploaded', 'computer', ?, NOW())
            ");

            $stmt->execute([
                $this->entityId,
                $documentType,
                $saveResult['filename'],
                $saveResult['filepath'],
                $file['size'],
                $file['type'],
                $notes,
                authUserId()
            ]);

            $documentId = $pdo->lastInsertId();

            // Audit log
            $this->logAudit('document_uploaded', "Document uploaded: {$documentType}", $pdo);

            $pdo->commit();

            return [
                'success' => true,
                'message' => 'Document uploaded successfully',
                'document_id' => $documentId
            ];

        } catch (Exception $e) {
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            logMessage("DocumentUploadService::uploadFromComputer error: " . $e->getMessage(), 'error');
            return [
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Upload document from phone camera using token
     *
     * @param string $token Upload token
     * @param array $file $_FILES array element
     * @return array ['success' => bool, 'message' => string]
     */
    public function uploadFromPhone($token, $file)
    {
        try {
            $pdo = Database::getTenantConnection();

            // Verify token
            $tokenData = $this->verifyToken($token);
            if (!$tokenData) {
                return ['success' => false, 'message' => 'Invalid or expired token'];
            }

            // Validate file
            $validation = $this->validateFile($file, ['image/jpeg', 'image/png', 'image/jpg']);
            if (!$validation['success']) {
                return $validation;
            }

            // Save file
            $saveResult = $this->saveFile($file, $tokenData['document_type'], $tokenData[$this->foreignKeyColumn]);
            if (!$saveResult['success']) {
                return $saveResult;
            }

            $pdo->beginTransaction();

            // Insert document record
            $stmt = $pdo->prepare("
                INSERT INTO {$this->tableName} (
                    {$this->foreignKeyColumn}, document_type, file_name, file_path,
                    file_size, mime_type, verification_status, upload_method,
                    uploaded_by, uploaded_at
                ) VALUES (?, ?, ?, ?, ?, ?, 'uploaded', 'phone', ?, NOW())
            ");

            $stmt->execute([
                $tokenData[$this->foreignKeyColumn],
                $tokenData['document_type'],
                $saveResult['filename'],
                $saveResult['filepath'],
                $file['size'],
                $file['type'],
                $tokenData['created_by']
            ]);

            // Mark token as completed
            $stmt = $pdo->prepare("
                UPDATE document_upload_tokens
                SET status = 'completed', completed_at = NOW()
                WHERE token = ?
            ");
            $stmt->execute([$token]);

            // Audit log
            $this->entityId = $tokenData[$this->foreignKeyColumn];
            $this->logAudit('document_uploaded', "Document uploaded via phone: {$tokenData['document_type']}", $pdo);

            $pdo->commit();

            return [
                'success' => true,
                'message' => 'Document uploaded successfully'
            ];

        } catch (Exception $e) {
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            logMessage("DocumentUploadService::uploadFromPhone error: " . $e->getMessage(), 'error');
            return [
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete a document
     *
     * @param int $documentId Document ID
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteDocument($documentId)
    {
        try {
            $pdo = Database::getTenantConnection();

            // Get document info
            $stmt = $pdo->prepare("SELECT * FROM {$this->tableName} WHERE id = ?");
            $stmt->execute([$documentId]);
            $document = $stmt->fetch();

            if (!$document) {
                return ['success' => false, 'message' => 'Document not found'];
            }

            $pdo->beginTransaction();

            // Delete physical file
            $fullPath = __DIR__ . '/../../' . $document['file_path'];
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            // Delete database record
            $stmt = $pdo->prepare("DELETE FROM {$this->tableName} WHERE id = ?");
            $stmt->execute([$documentId]);

            // Audit log
            $this->entityId = $document[$this->foreignKeyColumn];
            $this->logAudit('document_deleted', "Document deleted: {$document['document_type']}", $pdo);

            $pdo->commit();

            return [
                'success' => true,
                'message' => 'Document deleted successfully'
            ];

        } catch (Exception $e) {
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            logMessage("DocumentUploadService::deleteDocument error: " . $e->getMessage(), 'error');
            return [
                'success' => false,
                'message' => 'Delete failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get all documents for the entity
     *
     * @return array Array of documents
     */
    public function getDocuments()
    {
        $pdo = Database::getTenantConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM {$this->tableName}
            WHERE {$this->foreignKeyColumn} = ?
            ORDER BY uploaded_at DESC
        ");
        $stmt->execute([$this->entityId]);
        return $stmt->fetchAll();
    }

    /**
     * Validate uploaded file
     *
     * @param array $file $_FILES element
     * @param array $allowedTypes Optional custom allowed types
     * @return array ['success' => bool, 'message' => string]
     */
    private function validateFile($file, $allowedTypes = null)
    {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'No file uploaded or upload error'];
        }

        $allowedTypes = $allowedTypes ?? [
            'image/jpeg',
            'image/png',
            'image/jpg',
            'application/pdf'
        ];

        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'message' => 'Invalid file type'];
        }

        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'File size exceeds 5MB limit'];
        }

        return ['success' => true];
    }

    /**
     * Save file to storage
     *
     * @param array $file $_FILES element
     * @param string $documentType Document type
     * @param int $customEntityId Optional custom entity ID
     * @return array ['success' => bool, 'filename' => string, 'filepath' => string]
     */
    private function saveFile($file, $documentType, $customEntityId = null)
    {
        $entityId = $customEntityId ?? $this->entityId;
        $uploadDir = __DIR__ . '/../../storage/documents/' . $entityId;

        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $documentType . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['success' => false, 'message' => 'Failed to save file'];
        }

        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => 'storage/documents/' . $entityId . '/' . $filename
        ];
    }

    /**
     * Verify entity exists
     *
     * @return bool
     */
    private function verifyEntityExists()
    {
        $pdo = Database::getTenantConnection();
        $entityTable = $this->entityType;
        $stmt = $pdo->prepare("SELECT id FROM {$entityTable} WHERE id = ?");
        $stmt->execute([$this->entityId]);
        return (bool) $stmt->fetch();
    }

    /**
     * Verify campus access
     *
     * @return bool
     */
    private function verifyCampusAccess()
    {
        $currentCampusId = $_SESSION['current_campus_id'] ?? null;

        // All campuses access
        if ($currentCampusId === 'all') {
            return true;
        }

        $pdo = Database::getTenantConnection();
        $entityTable = $this->entityType;
        $stmt = $pdo->prepare("SELECT campus_id FROM {$entityTable} WHERE id = ?");
        $stmt->execute([$this->entityId]);
        $entity = $stmt->fetch();

        return $entity && $entity['campus_id'] == $currentCampusId;
    }

    /**
     * Verify upload token
     *
     * @param string $token Token string
     * @return array|false Token data or false
     */
    private function verifyToken($token)
    {
        $pdo = Database::getTenantConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM document_upload_tokens
            WHERE token = ? AND status = 'pending'
        ");
        $stmt->execute([$token]);
        $tokenData = $stmt->fetch();

        if (!$tokenData) {
            return false;
        }

        if (strtotime($tokenData['expires_at']) < time()) {
            return false;
        }

        return $tokenData;
    }

    /**
     * Log to audit table
     *
     * @param string $action Action type
     * @param string $description Description
     * @param PDO $pdo Database connection
     */
    private function logAudit($action, $description, $pdo)
    {
        // Assuming audit table follows pattern: [entity]_audit
        $auditTable = rtrim($this->entityType, 's') . '_audit';

        try {
            $stmt = $pdo->prepare("
                INSERT INTO {$auditTable} (
                    {$this->foreignKeyColumn}, action, description, user_id,
                    ip_address, user_agent, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $this->entityId,
                $action,
                $description,
                authUserId(),
                Request::ip(),
                Request::userAgent()
            ]);
        } catch (Exception $e) {
            // Log but don't fail the main operation
            logMessage("Audit log error: " . $e->getMessage(), 'error');
        }
    }
}
