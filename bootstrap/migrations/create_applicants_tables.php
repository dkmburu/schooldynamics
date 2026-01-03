<?php
/**
 * Migration: Create Applicants Tables
 * Creates all tables needed for applicant lifecycle management
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/database.php';

Env::load();

echo "========================================\n";
echo "Creating Applicants Module Tables\n";
echo "========================================\n\n";

// Get tenant database connection (using demo for now)
try {
    Database::resolveTenant('demo');
    $pdo = Database::getTenantConnection();
    $charset = 'utf8mb4';
    $collation = 'utf8mb4_unicode_ci';

    echo "Connected to tenant database\n\n";

    // 1. Grades/Levels Lookup Table
    echo "1. Creating grades table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `grades` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `grade_name` VARCHAR(50) NOT NULL,
            `grade_level` INT NOT NULL COMMENT 'Numeric level for sorting',
            `grade_category` ENUM('Pre-Primary', 'Primary', 'Secondary') NOT NULL,
            `min_age` INT COMMENT 'Minimum age in years',
            `max_age` INT COMMENT 'Maximum age in years',
            `capacity` INT DEFAULT 40,
            `is_active` TINYINT(1) DEFAULT 1,
            `sort_order` INT DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_active` (`is_active`),
            INDEX `idx_category` (`grade_category`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}
    ");
    echo "   ✓ grades table created\n";

    // 2. Intake Years (use academic_years but add intake-specific fields)
    echo "2. Creating intake_campaigns table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `intake_campaigns` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `campaign_name` VARCHAR(255) NOT NULL,
            `academic_year_id` INT UNSIGNED NOT NULL,
            `start_date` DATE NOT NULL,
            `end_date` DATE NOT NULL,
            `application_fee` DECIMAL(10,2) DEFAULT 0,
            `status` ENUM('draft', 'open', 'closed') DEFAULT 'draft',
            `description` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years`(`id`) ON DELETE CASCADE,
            INDEX `idx_status` (`status`),
            INDEX `idx_year` (`academic_year_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}
    ");
    echo "   ✓ intake_campaigns table created\n";

    // 3. Applicants (Core)
    echo "3. Creating applicants table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `applicants` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `application_ref` VARCHAR(50) NOT NULL UNIQUE,
            `intake_campaign_id` INT UNSIGNED,
            `academic_year_id` INT UNSIGNED NOT NULL,
            `grade_applying_for_id` INT UNSIGNED NOT NULL,
            `first_name` VARCHAR(100) NOT NULL,
            `middle_name` VARCHAR(100),
            `last_name` VARCHAR(100) NOT NULL,
            `date_of_birth` DATE,
            `gender` ENUM('male', 'female', 'other'),
            `nationality` VARCHAR(100) DEFAULT 'Kenya',
            `religion` VARCHAR(100),
            `birth_certificate_no` VARCHAR(100),
            `previous_school` VARCHAR(255),
            `previous_grade` VARCHAR(50),
            `status` ENUM(
                'draft',
                'submitted',
                'screening',
                'interview_scheduled',
                'interviewed',
                'exam_scheduled',
                'exam_taken',
                'accepted',
                'waitlisted',
                'rejected',
                'pre_admission',
                'admitted'
            ) DEFAULT 'draft',
            `application_date` DATE,
            `submitted_at` TIMESTAMP NULL,
            `special_needs` TEXT,
            `notes` TEXT,
            `created_by` BIGINT UNSIGNED,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years`(`id`),
            FOREIGN KEY (`grade_applying_for_id`) REFERENCES `grades`(`id`),
            FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
            INDEX `idx_ref` (`application_ref`),
            INDEX `idx_status` (`status`),
            INDEX `idx_year` (`academic_year_id`),
            INDEX `idx_grade` (`grade_applying_for_id`),
            INDEX `idx_name` (`last_name`, `first_name`),
            INDEX `idx_submitted` (`submitted_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}
    ");
    echo "   ✓ applicants table created\n";

    // 4. Applicant Contacts
    echo "4. Creating applicant_contacts table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `applicant_contacts` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `applicant_id` BIGINT UNSIGNED NOT NULL,
            `phone` VARCHAR(20),
            `alt_phone` VARCHAR(20),
            `email` VARCHAR(255),
            `address` TEXT,
            `city` VARCHAR(100),
            `county` VARCHAR(100),
            `country` VARCHAR(100) DEFAULT 'Kenya',
            `postal_code` VARCHAR(20),
            `is_primary` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`applicant_id`) REFERENCES `applicants`(`id`) ON DELETE CASCADE,
            INDEX `idx_applicant` (`applicant_id`),
            INDEX `idx_phone` (`phone`),
            INDEX `idx_email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}
    ");
    echo "   ✓ applicant_contacts table created\n";

    // 5. Applicant Guardians (Prospective)
    echo "5. Creating applicant_guardians table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `applicant_guardians` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `applicant_id` BIGINT UNSIGNED NOT NULL,
            `first_name` VARCHAR(100) NOT NULL,
            `last_name` VARCHAR(100) NOT NULL,
            `relationship` VARCHAR(50) COMMENT 'Mother, Father, Guardian, Other',
            `phone` VARCHAR(20),
            `alt_phone` VARCHAR(20),
            `email` VARCHAR(255),
            `id_number` VARCHAR(50),
            `occupation` VARCHAR(255),
            `employer` VARCHAR(255),
            `address` TEXT,
            `is_primary` TINYINT(1) DEFAULT 0,
            `is_emergency_contact` TINYINT(1) DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`applicant_id`) REFERENCES `applicants`(`id`) ON DELETE CASCADE,
            INDEX `idx_applicant` (`applicant_id`),
            INDEX `idx_phone` (`phone`),
            INDEX `idx_email` (`email`),
            INDEX `idx_primary` (`is_primary`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}
    ");
    echo "   ✓ applicant_guardians table created\n";

    // 6. Applicant Documents
    echo "6. Creating applicant_documents table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `applicant_documents` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `applicant_id` BIGINT UNSIGNED NOT NULL,
            `document_type` VARCHAR(100) NOT NULL COMMENT 'Birth Cert, Transfer Letter, Photo, etc',
            `document_name` VARCHAR(255) NOT NULL,
            `file_path` VARCHAR(500) NOT NULL,
            `file_size` INT UNSIGNED COMMENT 'Size in bytes',
            `mime_type` VARCHAR(100),
            `uploaded_by` BIGINT UNSIGNED,
            `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`applicant_id`) REFERENCES `applicants`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
            INDEX `idx_applicant` (`applicant_id`),
            INDEX `idx_type` (`document_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}
    ");
    echo "   ✓ applicant_documents table created\n";

    // 7. Applicant Interviews
    echo "7. Creating applicant_interviews table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `applicant_interviews` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `applicant_id` BIGINT UNSIGNED NOT NULL,
            `scheduled_at` DATETIME NOT NULL,
            `duration_minutes` INT DEFAULT 30,
            `location` VARCHAR(255),
            `interviewer_id` BIGINT UNSIGNED COMMENT 'User ID of interviewer',
            `panel_members` TEXT COMMENT 'JSON array of user IDs',
            `status` ENUM('scheduled', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
            `outcome` ENUM('excellent', 'good', 'average', 'poor') NULL,
            `score` DECIMAL(5,2) COMMENT 'Score out of 100',
            `notes` TEXT,
            `rubric_scores` TEXT COMMENT 'JSON with detailed rubric',
            `created_by` BIGINT UNSIGNED,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`applicant_id`) REFERENCES `applicants`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`interviewer_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
            FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
            INDEX `idx_applicant` (`applicant_id`),
            INDEX `idx_scheduled` (`scheduled_at`),
            INDEX `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}
    ");
    echo "   ✓ applicant_interviews table created\n";

    // 8. Applicant Exams
    echo "8. Creating applicant_exams table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `applicant_exams` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `applicant_id` BIGINT UNSIGNED NOT NULL,
            `exam_name` VARCHAR(255) NOT NULL,
            `scheduled_at` DATETIME NOT NULL,
            `exam_center` VARCHAR(255),
            `paper_code` VARCHAR(50),
            `candidate_number` VARCHAR(50),
            `status` ENUM('scheduled', 'taken', 'cancelled', 'absent') DEFAULT 'scheduled',
            `score` DECIMAL(5,2) COMMENT 'Raw score',
            `total_marks` DECIMAL(5,2) DEFAULT 100,
            `percentage` DECIMAL(5,2) COMMENT 'Calculated percentage',
            `grade` VARCHAR(10) COMMENT 'A, B, C, etc',
            `notes` TEXT,
            `created_by` BIGINT UNSIGNED,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`applicant_id`) REFERENCES `applicants`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
            INDEX `idx_applicant` (`applicant_id`),
            INDEX `idx_scheduled` (`scheduled_at`),
            INDEX `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}
    ");
    echo "   ✓ applicant_exams table created\n";

    // 9. Applicant Decisions
    echo "9. Creating applicant_decisions table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `applicant_decisions` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `applicant_id` BIGINT UNSIGNED NOT NULL,
            `decision` ENUM('accepted', 'waitlisted', 'rejected') NOT NULL,
            `decision_date` DATE NOT NULL,
            `decided_by` BIGINT UNSIGNED,
            `conditions` TEXT COMMENT 'Any conditions for acceptance',
            `offer_expiry_date` DATE,
            `rejection_reason` TEXT,
            `notes` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`applicant_id`) REFERENCES `applicants`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`decided_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
            INDEX `idx_applicant` (`applicant_id`),
            INDEX `idx_decision` (`decision`),
            INDEX `idx_date` (`decision_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}
    ");
    echo "   ✓ applicant_decisions table created\n";

    // 10. Applicant Audit Log
    echo "10. Creating applicant_audit table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `applicant_audit` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `applicant_id` BIGINT UNSIGNED NOT NULL,
            `action` VARCHAR(100) NOT NULL,
            `description` TEXT,
            `old_value` TEXT COMMENT 'JSON of previous state',
            `new_value` TEXT COMMENT 'JSON of new state',
            `user_id` BIGINT UNSIGNED,
            `ip_address` VARCHAR(45),
            `user_agent` VARCHAR(255),
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`applicant_id`) REFERENCES `applicants`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
            INDEX `idx_applicant` (`applicant_id`),
            INDEX `idx_action` (`action`),
            INDEX `idx_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}
    ");
    echo "   ✓ applicant_audit table created\n";

    echo "\n========================================\n";
    echo "✓ All tables created successfully!\n";
    echo "========================================\n\n";

    echo "Tables created:\n";
    echo "  1. grades\n";
    echo "  2. intake_campaigns\n";
    echo "  3. applicants\n";
    echo "  4. applicant_contacts\n";
    echo "  5. applicant_guardians\n";
    echo "  6. applicant_documents\n";
    echo "  7. applicant_interviews\n";
    echo "  8. applicant_exams\n";
    echo "  9. applicant_decisions\n";
    echo " 10. applicant_audit\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
