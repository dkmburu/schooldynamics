<?php
/**
 * Create School Profile Table
 * Stores tenant-wide school information
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/database.php';

Env::load();

echo "========================================\n";
echo "Creating School Profile Table\n";
echo "========================================\n\n";

try {
    Database::resolveTenant('demo');
    $pdo = Database::getTenantConnection();
    $charset = 'utf8mb4';
    $collation = 'utf8mb4_unicode_ci';

    // Create school_profile table
    echo "1. Creating school_profile table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `school_profile` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `school_name` VARCHAR(255) NOT NULL COMMENT 'Official school name',
            `short_name` VARCHAR(100) COMMENT 'Abbreviated name',
            `motto` VARCHAR(255),
            `logo_path` VARCHAR(500),
            `address` TEXT,
            `city` VARCHAR(100),
            `country` VARCHAR(100) DEFAULT 'Kenya',
            `phone` VARCHAR(20),
            `email` VARCHAR(255),
            `website` VARCHAR(255),

            -- Registration Details
            `registration_number` VARCHAR(100) COMMENT 'Ministry of Education registration',
            `tsc_registration` VARCHAR(100) COMMENT 'TSC registration number',

            -- Academic
            `curriculum` VARCHAR(100) COMMENT 'CBC, 8-4-4, IGCSE, IB, etc.',
            `academic_year_start_month` INT DEFAULT 1 COMMENT 'Month when academic year starts',

            -- Settings
            `timezone` VARCHAR(50) DEFAULT 'Africa/Nairobi',
            `currency_code` VARCHAR(10) DEFAULT 'KES',
            `date_format` VARCHAR(20) DEFAULT 'd/m/Y',

            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}
    ");
    echo "   ✓ school_profile table created\n";

    // Seed default school profile
    echo "2. Seeding default school profile...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM school_profile");
    if ($stmt->fetch()['count'] == 0) {
        $pdo->exec("
            INSERT INTO school_profile (
                school_name,
                short_name,
                motto,
                address,
                city,
                country,
                phone,
                email,
                website,
                curriculum,
                academic_year_start_month
            ) VALUES (
                'Demo High School',
                'DHS',
                'Excellence Through Education',
                'Karen Road, Karen',
                'Nairobi',
                'Kenya',
                '+254712345678',
                'info@demohighschool.ac.ke',
                'www.demohighschool.ac.ke',
                'CBC',
                1
            )
        ");
        echo "   ✓ Default school profile created: Demo High School\n";
    } else {
        echo "   ℹ School profile already exists\n";
    }

    echo "\n========================================\n";
    echo "✓ School Profile table ready!\n";
    echo "========================================\n\n";

    // Display current profile
    $stmt = $pdo->query("SELECT * FROM school_profile LIMIT 1");
    $profile = $stmt->fetch();

    if ($profile) {
        echo "Current School Profile:\n";
        echo "  Name: {$profile['school_name']}\n";
        echo "  Short Name: {$profile['short_name']}\n";
        echo "  Motto: {$profile['motto']}\n";
        echo "  Location: {$profile['city']}, {$profile['country']}\n";
        echo "  Curriculum: {$profile['curriculum']}\n";
    }

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
