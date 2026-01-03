<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

$pdo = Database::getRouterConnection();
$pdo->exec('DROP TABLE IF EXISTS router_audit_logs');
$pdo->exec('DROP TABLE IF EXISTS tenant_metrics');
echo 'Tables dropped successfully';
?>
