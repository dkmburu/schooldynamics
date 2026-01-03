<?php
/**
 * Migration: Create Staff Module Tables
 * Creates all tables needed for staff management, leave, attendance
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/database.php';

Env::load();

echo "========================================\n";
echo "Creating Staff Module Tables\n";
echo "========================================\n\n";

try {
    Database::resolveTenant('demo');
    $pdo = Database::getTenantConnection();
    $charset = 'utf8mb4';
    $collation = 'utf8mb4_unicode_ci';

    echo "Connected to tenant database\n\n";

    // 1. Staff Table
    echo "1. Creating staff table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `staff` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `staff_number` VARCHAR(50) NOT NULL UNIQUE COMMENT 'e.g., STF-2025-001',
            `user_id` BIGINT UNSIGNED COMMENT 'Link to users table for portal login',

            -- Personal Information
            `first_name` VARCHAR(100) NOT NULL,
            `middle_name` VARCHAR(100),
            `last_name` VARCHAR(100) NOT NULL,
            `date_of_birth` DATE,
            `gender` ENUM('male', 'female', 'other'),
            `nationality` VARCHAR(100) DEFAULT 'Kenya',
            `id_number` VARCHAR(50) UNIQUE COMMENT 'National ID',

            -- Employment Information
            `staff_type` ENUM('teaching', 'non-teaching', 'admin', 'support') NOT NULL,
            `employment_type` ENUM('permanent', 'contract', 'part-time', 'casual', 'intern') NOT NULL,
            `job_title` VARCHAR(255) COMMENT 'Teacher, Principal, Accountant, etc.',
            `department` VARCHAR(100) COMMENT 'Mathematics, Admin, Sports, etc.',
            `tsc_number` VARCHAR(50) COMMENT 'TSC Registration Number (Kenya)',

            -- Dates
            `date_joined` DATE,
            `contract_start_date` DATE,
            `contract_end_date` DATE,
            `date_left` DATE,

            -- Status
            `status` ENUM('active', 'on_leave', 'suspended', 'terminated', 'retired', 'resigned') DEFAULT 'active',

            -- Salary & Banking
            `basic_salary` DECIMAL(12,2),
            `bank_name` VARCHAR(100),
            `bank_account_number` VARCHAR(50),

            -- Emergency Contact
            `emergency_contact_name` VARCHAR(255),
            `emergency_contact_phone` VARCHAR(20),

            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
            INDEX `idx_staff_number` (`staff_number`),
            INDEX `idx_status` (`status`),
            INDEX `idx_type` (`staff_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}
    ");
    echo "   ✓ staff table created\n";

    // 2. Staff Contacts
    echo "2. Creating staff_contacts table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `staff_contacts` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `staff_id` BIGINT UNSIGNED NOT NULL,
            `phone` VARCHAR(20),
            `alt_phone` VARCHAR(20),
            `email` VARCHAR(255),
            `personal_email` VARCHAR(255),
            `address` TEXT,
            `city` VARCHAR(100),
            `country` VARCHAR(100) DEFAULT 'Kenya',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`staff_id`) REFERENCES `staff`(`id`) ON DELETE CASCADE,
            INDEX `idx_staff` (`staff_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}
    ");
    echo "   ✓ staff_contacts table created\n";

    // 3. Staff Campus Assignments
    echo "3. Creating staff_campus_assignments table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `staff_campus_assignments` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `staff_id` BIGINT UNSIGNED NOT NULL,
            `campus_id` INT UNSIGNED NOT NULL,
            `is_primary` TINYINT(1) DEFAULT 0 COMMENT 'Primary campus',
            `position` VARCHAR(255) COMMENT 'Role at this campus',
            `start_date` DATE NOT NULL,
            `end_date` DATE,
            `workload_percentage` INT DEFAULT 100 COMMENT 'Percentage of time at this campus',
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`staff_id`) REFERENCES `staff`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`campus_id`) REFERENCES `campuses`(`id`) ON DELETE CASCADE,
            INDEX `idx_staff_campus` (`staff_id`, `campus_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}
    ");
    echo "   ✓ staff_campus_assignments table created\n";

    // 4. Leave Types
    echo "4. Creating leave_types table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `leave_types` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `leave_name` VARCHAR(100) NOT NULL COMMENT 'Annual Leave, Sick Leave, Maternity',
            `leave_code` VARCHAR(20) NOT NULL UNIQUE,
            `max_days_per_year` INT COMMENT 'Maximum days allowed per year',
            `is_paid` TINYINT(1) DEFAULT 1,
            `requires_approval` TINYINT(1) DEFAULT 1,
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_code` (`leave_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}
    ");
    echo "   ✓ leave_types table created\n";

    // 5. Seed default leave types
    echo "5. Seeding default leave types...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM leave_types");
    if ($stmt->fetch()['count'] == 0) {
        $leaveTypes = [
            ['Annual Leave', 'ANNUAL', 21, 1],
            ['Sick Leave', 'SICK', 14, 1],
            ['Maternity Leave', 'MATERNITY', 90, 1],
            ['Paternity Leave', 'PATERNITY', 14, 1],
            ['Compassionate Leave', 'COMPASSIONATE', 7, 1],
            ['Study Leave', 'STUDY', NULL, 0],
            ['Unpaid Leave', 'UNPAID', NULL, 0],
        ];

        $stmt = $pdo->prepare("
            INSERT INTO leave_types (leave_name, leave_code, max_days_per_year, is_paid)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($leaveTypes as $type) {
            $stmt->execute($type);
            echo "   ✓ {$type[0]}\n";
        }
    } else {
        echo "   ℹ Leave types already exist\n";
    }

    // 6. Leave Applications
    echo "6. Creating leave_applications table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `leave_applications` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `leave_ref` VARCHAR(50) NOT NULL UNIQUE COMMENT 'LV-2025-001',
            `staff_id` BIGINT UNSIGNED NOT NULL,
            `leave_type_id` INT UNSIGNED NOT NULL,

            -- Leave Details
            `start_date` DATE NOT NULL,
            `end_date` DATE NOT NULL,
            `days_requested` INT NOT NULL,
            `reason` TEXT NOT NULL,
            `contact_during_leave` VARCHAR(20),

            -- Approval Workflow
            `status` ENUM('draft', 'submitted', 'pending_hod', 'pending_principal', 'approved', 'rejected', 'cancelled') DEFAULT 'draft',
            `submitted_at` TIMESTAMP NULL,
            `approved_by` BIGINT UNSIGNED,
            `approved_at` TIMESTAMP NULL,
            `approver_comments` TEXT,
            `rejection_reason` TEXT,

            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            FOREIGN KEY (`staff_id`) REFERENCES `staff`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types`(`id`),
            INDEX `idx_staff` (`staff_id`),
            INDEX `idx_status` (`status`),
            INDEX `idx_dates` (`start_date`, `end_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}
    ");
    echo "   ✓ leave_applications table created\n";

    // 7. Leave Balances
    echo "7. Creating leave_balances table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `leave_balances` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `staff_id` BIGINT UNSIGNED NOT NULL,
            `leave_type_id` INT UNSIGNED NOT NULL,
            `academic_year_id` INT UNSIGNED NOT NULL,
            `days_allocated` INT NOT NULL,
            `days_taken` INT DEFAULT 0,
            `days_pending` INT DEFAULT 0,
            `days_remaining` INT GENERATED ALWAYS AS (`days_allocated` - `days_taken` - `days_pending`) STORED,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`staff_id`) REFERENCES `staff`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types`(`id`),
            UNIQUE KEY `unique_staff_leave_year` (`staff_id`, `leave_type_id`, `academic_year_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}
    ");
    echo "   ✓ leave_balances table created\n";

    // 8. Staff Attendance
    echo "8. Creating staff_attendance table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `staff_attendance` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `staff_id` BIGINT UNSIGNED NOT NULL,
            `campus_id` INT UNSIGNED NOT NULL,
            `attendance_date` DATE NOT NULL,
            `clock_in` TIME,
            `clock_out` TIME,
            `status` ENUM('present', 'absent', 'late', 'half_day', 'on_leave') NOT NULL,
            `marked_by` BIGINT UNSIGNED,
            `notes` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`staff_id`) REFERENCES `staff`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`campus_id`) REFERENCES `campuses`(`id`),
            UNIQUE KEY `unique_staff_date` (`staff_id`, `attendance_date`),
            INDEX `idx_date` (`attendance_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}
    ");
    echo "   ✓ staff_attendance table created\n";

    // 9. Staff Documents
    echo "9. Creating staff_documents table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `staff_documents` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `staff_id` BIGINT UNSIGNED NOT NULL,
            `document_type` VARCHAR(100) NOT NULL,
            `document_name` VARCHAR(255) NOT NULL,
            `file_path` VARCHAR(500) NOT NULL,
            `uploaded_by` BIGINT UNSIGNED,
            `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`staff_id`) REFERENCES `staff`(`id`) ON DELETE CASCADE,
            INDEX `idx_staff` (`staff_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}
    ");
    echo "   ✓ staff_documents table created\n";

    echo "\n========================================\n";
    echo "✓ All Staff tables created successfully!\n";
    echo "========================================\n\n";

    echo "Tables created:\n";
    echo "  1. staff\n";
    echo "  2. staff_contacts\n";
    echo "  3. staff_campus_assignments\n";
    echo "  4. leave_types (with 7 default types)\n";
    echo "  5. leave_applications\n";
    echo "  6. leave_balances\n";
    echo "  7. staff_attendance\n";
    echo "  8. staff_documents\n\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
