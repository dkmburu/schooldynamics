<?php
/**
 * Migration: Create Multi-Campus Support Tables
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/database.php';

Env::load();

echo "========================================\n";
echo "Creating Multi-Campus Support Tables\n";
echo "========================================\n\n";

try {
    Database::resolveTenant('demo');
    $pdo = Database::getTenantConnection();
    $charset = 'utf8mb4';
    $collation = 'utf8mb4_unicode_ci';

    echo "Connected to tenant database\n\n";

    // 1. Campuses Table
    echo "1. Creating campuses table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `campuses` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `campus_code` VARCHAR(20) NOT NULL UNIQUE COMMENT 'Short code: NBO-HS, NKR-ECD',
            `campus_name` VARCHAR(255) NOT NULL COMMENT 'Nairobi Campus - High School',
            `location` VARCHAR(255) COMMENT 'City/Area',
            `address` TEXT,
            `phone` VARCHAR(20),
            `email` VARCHAR(255),
            `is_main` TINYINT(1) DEFAULT 0 COMMENT 'Main/Head Office campus',
            `is_active` TINYINT(1) DEFAULT 1,
            `sort_order` INT DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_active` (`is_active`),
            INDEX `idx_code` (`campus_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}
    ");
    echo "   ✓ campuses table created\n";

    // 2. Seed default campus
    echo "2. Seeding default campus...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM campuses");
    $count = $stmt->fetch()['count'];

    if ($count == 0) {
        $pdo->exec("
            INSERT INTO campuses (campus_code, campus_name, location, is_main, is_active, sort_order)
            VALUES ('MAIN', 'Main Campus', 'Nairobi', 1, 1, 1)
        ");
        echo "   ✓ Default campus created\n";
    } else {
        echo "   ℹ Campuses already exist\n";
    }

    // 3. Add campus_id to applicants (if not exists)
    echo "3. Adding campus_id to applicants table...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM applicants LIKE 'campus_id'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE applicants ADD COLUMN `campus_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`");
        $pdo->exec("ALTER TABLE applicants ADD FOREIGN KEY (`campus_id`) REFERENCES `campuses`(`id`)");
        echo "   ✓ campus_id added to applicants\n";
    } else {
        echo "   ℹ campus_id already exists in applicants\n";
    }

    // 4. Add campus_id to students (if not exists)
    echo "4. Adding campus_id to students table...\n";
    $tableExists = $pdo->query("SHOW TABLES LIKE 'students'")->rowCount() > 0;
    if ($tableExists) {
        $stmt = $pdo->query("SHOW COLUMNS FROM students LIKE 'campus_id'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE students ADD COLUMN `campus_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`");
            $pdo->exec("ALTER TABLE students ADD FOREIGN KEY (`campus_id`) REFERENCES `campuses`(`id`)");
            echo "   ✓ campus_id added to students\n";
        } else {
            echo "   ℹ campus_id already exists in students\n";
        }
    } else {
        echo "   ℹ students table doesn't exist yet\n";
    }

    echo "\n========================================\n";
    echo "✓ Multi-Campus tables created successfully!\n";
    echo "========================================\n\n";

    echo "Summary:\n";
    echo "  1. campuses table created\n";
    echo "  2. Default 'Main Campus' added\n";
    echo "  3. campus_id added to applicants\n";
    echo "  4. campus_id added to students (if table exists)\n\n";

    echo "Next steps:\n";
    echo "  - Add more campuses via Settings or direct SQL\n";
    echo "  - Update forms to include campus selection\n";
    echo "  - Add campus filter to all lists\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
