<?php
/**
 * Direct Document Download Script
 * Bypasses entire framework to provide clean file downloads
 */

// CRITICAL: Disable ALL output and error display immediately
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Disable output compression
ini_set('zlib.output_compression', 'Off');
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', '1');
}

// Start fresh output buffer to catch ANY stray output
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Load minimal dependencies
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

// Start session for authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load environment
Env::load();

// Verify authentication
if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    http_response_code(403);
    die('Unauthorized');
}

// Get parameters from query string
$entityType = $_GET['entity'] ?? '';
$documentId = $_GET['id'] ?? '';

if (empty($entityType) || empty($documentId)) {
    ob_end_clean();
    http_response_code(400);
    die('Missing parameters');
}

try {
    // Resolve tenant from subdomain
    $subdomain = getSubdomain();
    if (!$subdomain) {
        ob_end_clean();
        http_response_code(404);
        die('Tenant not found');
    }

    $tenantResolved = Database::resolveTenant($subdomain);
    if ($tenantResolved === false) {
        ob_end_clean();
        http_response_code(404);
        die('Tenant not found');
    }

    $pdo = Database::getTenantConnection();

    // Derive table names from entity type
    $documentTable = rtrim($entityType, 's') . '_documents';
    $entityTable = $entityType;
    $foreignKey = rtrim($entityType, 's') . '_id';
    $auditTable = rtrim($entityType, 's') . '_audit';

    // Check if entity table has campus_id column
    $hasCampusId = false;
    $checkStmt = $pdo->prepare("SHOW COLUMNS FROM {$entityTable} LIKE 'campus_id'");
    $checkStmt->execute();
    $hasCampusId = $checkStmt->rowCount() > 0;

    // Get document info with optional campus check
    if ($hasCampusId) {
        $stmt = $pdo->prepare("
            SELECT d.*, e.campus_id
            FROM {$documentTable} d
            JOIN {$entityTable} e ON d.{$foreignKey} = e.id
            WHERE d.id = ?
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT d.*
            FROM {$documentTable} d
            WHERE d.id = ?
        ");
    }
    $stmt->execute([$documentId]);
    $document = $stmt->fetch();

    if (!$document) {
        ob_end_clean();
        http_response_code(404);
        die('Document not found');
    }

    // Verify campus access (only if entity has campus_id)
    if ($hasCampusId) {
        $currentCampusId = $_SESSION['current_campus_id'] ?? null;
        if ($currentCampusId && $currentCampusId !== 'all' && $document['campus_id'] != $currentCampusId) {
            ob_end_clean();
            http_response_code(403);
            die('Access denied');
        }
    }

    // Build file path
    $filepath = __DIR__ . '/../' . $document['file_path'];

    if (!file_exists($filepath)) {
        ob_end_clean();
        http_response_code(404);
        die('File not found on server');
    }

    // Log download to audit table (non-blocking, errors won't stop download)
    try {
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
            $_SESSION['user_id'],
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
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
            $_SESSION['user_id'],
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        error_log("Download audit log failed: " . $e->getMessage());
    }

    // CRITICAL: Close session before sending file (releases session lock)
    session_write_close();

    // Get file info
    $fileSize = filesize($filepath);

    // Sanitize filename - remove any problematic characters
    $fileName = $document['file_name'] ?? $document['document_name'] ?? 'document';
    $fileName = basename($fileName);
    $fileName = preg_replace('/[^\w\-\.\s]/', '_', $fileName);
    if (empty($fileName)) {
        $fileName = 'document_' . $documentId;
    }

    // CRITICAL: Clear ALL output buffers completely before sending headers
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    // Check if headers already sent (debugging)
    if (headers_sent($sentFile, $sentLine)) {
        error_log("DOWNLOAD ERROR: Headers already sent in $sentFile on line $sentLine");
        die('Download error: headers already sent');
    }

    // Force download with application/octet-stream (never render inline)
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: ' . $fileSize);
    header('Cache-Control: private, no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Output file directly using readfile (most reliable method)
    readfile($filepath);

    // Terminate immediately
    exit;

} catch (Exception $e) {
    error_log("Download error: " . $e->getMessage());
    ob_end_clean();
    http_response_code(500);
    die('Download failed');
}
