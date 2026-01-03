<?php
/**
 * Router Database Installation Script
 * Creates the sims_router database and initial schema
 *
 * Run this once to set up the central router database
 */

require_once __DIR__ . '/../config/env.php';

// Load environment
Env::load();

echo "========================================\n";
echo "SchoolDynamics Router DB Installation\n";
echo "========================================\n\n";

// Connect to MySQL without database selection
$host = Env::get('ROUTER_DB_HOST', 'localhost');
$port = Env::get('ROUTER_DB_PORT', '3306');
$dbname = Env::get('ROUTER_DB_NAME', 'sims_router');
$user = Env::get('ROUTER_DB_USER', 'root');
$pass = Env::get('ROUTER_DB_PASS', '');
$charset = Env::get('DB_CHARSET', 'utf8mb4');
$collation = Env::get('DB_COLLATION', 'utf8mb4_unicode_ci');

try {
    // Connect without database
    $dsn = "mysql:host={$host};port={$port};charset={$charset}";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "✓ Connected to MySQL server\n";

    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}`
                CHARACTER SET {$charset}
                COLLATE {$collation}");

    echo "✓ Database '{$dbname}' created/verified\n";

    // Use the database
    $pdo->exec("USE `{$dbname}`");

    // Drop existing tables and recreate with fresh schema
    echo "Checking for existing tables...\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("DROP TABLE IF EXISTS router_audit_logs");
    $pdo->exec("DROP TABLE IF EXISTS tenant_metrics");
    $pdo->exec("DROP TABLE IF EXISTS main_admin_users");
    $pdo->exec("DROP TABLE IF EXISTS tenants");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "✓ Existing tables dropped\n";

    // Create tenants table
    $sql = "
    CREATE TABLE `tenants` (
        `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `subdomain` VARCHAR(100) NOT NULL UNIQUE,
        `school_name` VARCHAR(255) NOT NULL,
        `db_host` VARCHAR(100) NOT NULL DEFAULT 'localhost',
        `db_name` VARCHAR(100) NOT NULL,
        `db_user` VARCHAR(100) NOT NULL,
        `db_pass_enc` TEXT NOT NULL,
        `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
        `maintenance_mode` TINYINT(1) NOT NULL DEFAULT 0,
        `plan_tier` VARCHAR(50) DEFAULT 'standard',
        `max_users` INT UNSIGNED DEFAULT 100,
        `max_students` INT UNSIGNED DEFAULT 1000,
        `features_json` TEXT,
        `branding_json` TEXT,
        `support_contact` VARCHAR(255),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_subdomain` (`subdomain`),
        INDEX `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}";

    $pdo->exec($sql);
    echo "✓ Table 'tenants' created\n";

    // Create main_admin_users table (for super admin access)
    $sql = "
    CREATE TABLE `main_admin_users` (
        `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(100) NOT NULL UNIQUE,
        `email` VARCHAR(255) NOT NULL UNIQUE,
        `password_hash` VARCHAR(255) NOT NULL,
        `full_name` VARCHAR(255) NOT NULL,
        `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
        `last_login_at` TIMESTAMP NULL,
        `last_login_ip` VARCHAR(45) NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_username` (`username`),
        INDEX `idx_email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}";

    $pdo->exec($sql);
    echo "✓ Table 'main_admin_users' created\n";

    // Create tenant_metrics table
    $sql = "
    CREATE TABLE `tenant_metrics` (
        `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `tenant_id` BIGINT UNSIGNED NOT NULL,
        `metric_date` DATE NOT NULL,
        `total_users` INT UNSIGNED DEFAULT 0,
        `total_students` INT UNSIGNED DEFAULT 0,
        `total_staff` INT UNSIGNED DEFAULT 0,
        `storage_used_mb` DECIMAL(10,2) DEFAULT 0,
        `active_sessions` INT UNSIGNED DEFAULT 0,
        `api_calls` INT UNSIGNED DEFAULT 0,
        `sms_sent` INT UNSIGNED DEFAULT 0,
        `emails_sent` INT UNSIGNED DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `unique_tenant_date` (`tenant_id`, `metric_date`),
        INDEX `idx_tenant_id` (`tenant_id`),
        INDEX `idx_metric_date` (`metric_date`),
        CONSTRAINT `fk_tenant_metrics_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}";

    $pdo->exec($sql);
    echo "✓ Table 'tenant_metrics' created\n";

    // Create audit_logs table for router DB operations
    $sql = "
    CREATE TABLE `router_audit_logs` (
        `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `admin_user_id` BIGINT UNSIGNED NULL,
        `action` VARCHAR(100) NOT NULL,
        `entity_type` VARCHAR(100) NOT NULL,
        `entity_id` BIGINT UNSIGNED NULL,
        `description` TEXT,
        `ip_address` VARCHAR(45) NULL,
        `user_agent` VARCHAR(255) NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_admin_user` (`admin_user_id`),
        INDEX `idx_entity` (`entity_type`, `entity_id`),
        INDEX `idx_created_at` (`created_at`),
        CONSTRAINT `fk_router_audit_admin` FOREIGN KEY (`admin_user_id`) REFERENCES `main_admin_users`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}";

    $pdo->exec($sql);
    echo "✓ Table 'router_audit_logs' created\n";

    // Create default main admin user (admin/admin123 - CHANGE IN PRODUCTION!)
    $defaultUsername = 'admin';
    $defaultPassword = 'admin123';
    $passwordHash = password_hash($defaultPassword, PASSWORD_BCRYPT, ['cost' => 12]);

    $stmt = $pdo->prepare("
        INSERT INTO main_admin_users (username, email, password_hash, full_name, status)
        VALUES (:username, :email, :password_hash, :full_name, 'active')
        ON DUPLICATE KEY UPDATE username = username
    ");

    $stmt->execute([
        'username' => $defaultUsername,
        'email' => 'admin@schooldynamics.local',
        'password_hash' => $passwordHash,
        'full_name' => 'System Administrator'
    ]);

    if ($stmt->rowCount() > 0) {
        echo "✓ Default main admin user created\n";
        echo "  Username: {$defaultUsername}\n";
        echo "  Password: {$defaultPassword}\n";
        echo "  ⚠️  CHANGE THIS PASSWORD IN PRODUCTION!\n";
    } else {
        echo "✓ Main admin user already exists\n";
    }

    echo "\n========================================\n";
    echo "Router DB installation completed successfully!\n";
    echo "========================================\n";

} catch (PDOException $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
