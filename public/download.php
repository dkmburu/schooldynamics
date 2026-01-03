<?php
/**
 * Direct Document Download Script
 * Bypasses entire framework to provide clean file downloads
 */

// Load minimal dependencies only (no output buffering)
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
    http_response_code(403);
    die('Unauthorized');
}

// Get parameters from query string
$entityType = $_GET['entity'] ?? '';
$documentId = $_GET['id'] ?? '';

if (empty($entityType) || empty($documentId)) {
    http_response_code(400);
    die('Missing parameters');
}

try {
    // Resolve tenant from subdomain
    $subdomain = getSubdomain();
    if (!$subdomain) {
        http_response_code(404);
        die('Tenant not found');
    }

    $tenantResolved = Database::resolveTenant($subdomain);
    if ($tenantResolved === false) {
        http_response_code(404);
        die('Tenant not found');
    }

    $pdo = Database::getTenantConnection();

    // Derive table names from entity type
    $documentTable = rtrim($entityType, 's') . '_documents';
    $entityTable = $entityType;
    $foreignKey = rtrim($entityType, 's') . '_id';
    $auditTable = rtrim($entityType, 's') . '_audit';

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
        http_response_code(404);
        die('Document not found');
    }

    // Verify campus access
    $currentCampusId = $_SESSION['current_campus_id'] ?? null;
    if ($currentCampusId && $currentCampusId !== 'all' && $document['campus_id'] != $currentCampusId) {
        http_response_code(403);
        die('Access denied');
    }

    // Build file path
    $filepath = __DIR__ . '/../' . $document['file_path'];

    if (!file_exists($filepath)) {
        http_response_code(404);
        die('File not found on server');
    }

    // Log download to audit table
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
        // Log error but continue with download
        error_log("Download audit log failed: " . $e->getMessage());
    }

    // Clear ALL output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Disable error output
    ini_set('display_errors', 0);
    error_reporting(0);

    // Disable compression
    if (function_exists('apache_setenv')) {
        @apache_setenv('no-gzip', '1');
    }
    ini_set('zlib.output_compression', '0');

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
    error_log("Download error: " . $e->getMessage());
    http_response_code(500);
    die('Download failed');
}
