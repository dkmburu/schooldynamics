<?php
/**
 * Reset Router DB tables (drop and recreate)
 */

require_once __DIR__ . '/../config/env.php';

Env::load();

$host = Env::get('ROUTER_DB_HOST', 'localhost');
$port = Env::get('ROUTER_DB_PORT', '3306');
$dbname = Env::get('ROUTER_DB_NAME', 'sims_router');
$user = Env::get('ROUTER_DB_USER', 'root');
$pass = Env::get('ROUTER_DB_PASS', '');

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname}";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    echo "Dropping existing tables...\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("DROP TABLE IF EXISTS router_audit_logs");
    $pdo->exec("DROP TABLE IF EXISTS tenant_metrics");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "✓ Tables dropped\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
