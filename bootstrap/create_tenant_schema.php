<?php
/**
 * Tenant Database Schema Creator
 * Creates the complete tenant database schema based on the spec
 */

function createTenantSchema($pdo, $tenantName) {
    $charset = 'utf8mb4';
    $collation = 'utf8mb4_unicode_ci';

    echo "Creating schema for tenant: {$tenantName}\n";
    echo "========================================\n";

    // System tables
    $tables = [
        // Migrations tracking
        'migrations' => "
        CREATE TABLE IF NOT EXISTS `migrations` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `migration` VARCHAR(255) NOT NULL,
            `batch` INT NOT NULL,
            `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `unique_migration` (`migration`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}",

        // Modules and Submodules
        'modules' => "
        CREATE TABLE IF NOT EXISTS `modules` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `display_name` VARCHAR(255) NOT NULL,
            `icon` VARCHAR(50),
            `sort_order` INT DEFAULT 0,
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}",

        'submodules' => "
        CREATE TABLE IF NOT EXISTS `submodules` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `module_id` INT UNSIGNED NOT NULL,
            `name` VARCHAR(100) NOT NULL,
            `display_name` VARCHAR(255) NOT NULL,
            `route` VARCHAR(255),
            `icon` VARCHAR(50),
            `sort_order` INT DEFAULT 0,
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`module_id`) REFERENCES `modules`(`id`) ON DELETE CASCADE,
            INDEX `idx_module_id` (`module_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}",

        // RBAC - Roles and Permissions
        'roles' => "
        CREATE TABLE IF NOT EXISTS `roles` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL UNIQUE,
            `display_name` VARCHAR(255) NOT NULL,
            `description` TEXT,
            `is_system` TINYINT(1) DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}",

        'permissions' => "
        CREATE TABLE IF NOT EXISTS `permissions` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `submodule_id` INT UNSIGNED NOT NULL,
            `name` VARCHAR(100) NOT NULL,
            `display_name` VARCHAR(255) NOT NULL,
            `action` ENUM('view', 'write', 'approve', 'export', 'delete') NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`submodule_id`) REFERENCES `submodules`(`id`) ON DELETE CASCADE,
            UNIQUE KEY `unique_permission` (`submodule_id`, `action`),
            INDEX `idx_submodule` (`submodule_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}",

        'role_permissions' => "
        CREATE TABLE IF NOT EXISTS `role_permissions` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `role_id` INT UNSIGNED NOT NULL,
            `permission_id` INT UNSIGNED NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE,
            UNIQUE KEY `unique_role_permission` (`role_id`, `permission_id`),
            INDEX `idx_role` (`role_id`),
            INDEX `idx_permission` (`permission_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}",

        // Users (Staff)
        'users' => "
        CREATE TABLE IF NOT EXISTS `users` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(100) NOT NULL UNIQUE,
            `email` VARCHAR(255) NOT NULL UNIQUE,
            `password_hash` VARCHAR(255) NOT NULL,
            `full_name` VARCHAR(255) NOT NULL,
            `phone` VARCHAR(20),
            `employee_number` VARCHAR(50),
            `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
            `last_login_at` TIMESTAMP NULL,
            `last_login_ip` VARCHAR(45),
            `failed_login_attempts` INT DEFAULT 0,
            `locked_until` TIMESTAMP NULL,
            `password_reset_token` VARCHAR(100),
            `password_reset_expires` TIMESTAMP NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_username` (`username`),
            INDEX `idx_email` (`email`),
            INDEX `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}",

        'user_roles' => "
        CREATE TABLE IF NOT EXISTS `user_roles` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `user_id` BIGINT UNSIGNED NOT NULL,
            `role_id` INT UNSIGNED NOT NULL,
            `assigned_by` BIGINT UNSIGNED,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
            UNIQUE KEY `unique_user_role` (`user_id`, `role_id`),
            INDEX `idx_user` (`user_id`),
            INDEX `idx_role` (`role_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}",

        // Audit Logs
        'audit_logs' => "
        CREATE TABLE IF NOT EXISTS `audit_logs` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `user_id` BIGINT UNSIGNED,
            `action` VARCHAR(100) NOT NULL,
            `entity_type` VARCHAR(100) NOT NULL,
            `entity_id` BIGINT UNSIGNED,
            `url` VARCHAR(500),
            `method` VARCHAR(10),
            `payload_hash` VARCHAR(64),
            `before_snapshot` JSON,
            `after_snapshot` JSON,
            `ip_address` VARCHAR(45),
            `user_agent` VARCHAR(255),
            `result` ENUM('success', 'failure') DEFAULT 'success',
            `error_message` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
            INDEX `idx_user` (`user_id`),
            INDEX `idx_entity` (`entity_type`, `entity_id`),
            INDEX `idx_created_at` (`created_at`),
            INDEX `idx_action` (`action`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}",

        // School Profile & Settings
        'school_profile' => "
        CREATE TABLE IF NOT EXISTS `school_profile` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `school_name` VARCHAR(255) NOT NULL,
            `motto` VARCHAR(255),
            `address` TEXT,
            `phone` VARCHAR(20),
            `email` VARCHAR(255),
            `website` VARCHAR(255),
            `logo_path` VARCHAR(255),
            `principal_name` VARCHAR(255),
            `registration_number` VARCHAR(100),
            `currency` VARCHAR(10) DEFAULT 'KES',
            `timezone` VARCHAR(50) DEFAULT 'Africa/Nairobi',
            `academic_year_start_month` INT DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}",

        'settings' => "
        CREATE TABLE IF NOT EXISTS `settings` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `key` VARCHAR(255) NOT NULL UNIQUE,
            `value` TEXT,
            `type` ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
            `group` VARCHAR(100),
            `description` TEXT,
            `is_public` TINYINT(1) DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_key` (`key`),
            INDEX `idx_group` (`group`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}",

        // Academic Calendar
        'academic_years' => "
        CREATE TABLE IF NOT EXISTS `academic_years` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `year_name` VARCHAR(100) NOT NULL,
            `start_date` DATE NOT NULL,
            `end_date` DATE NOT NULL,
            `is_current` TINYINT(1) DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_current` (`is_current`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}",

        'terms' => "
        CREATE TABLE IF NOT EXISTS `terms` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `academic_year_id` INT UNSIGNED NOT NULL,
            `term_name` VARCHAR(100) NOT NULL,
            `term_number` INT NOT NULL,
            `start_date` DATE NOT NULL,
            `end_date` DATE NOT NULL,
            `is_current` TINYINT(1) DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years`(`id`) ON DELETE CASCADE,
            INDEX `idx_year` (`academic_year_id`),
            INDEX `idx_current` (`is_current`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}",

        // Students & Guardians (Core tables - will expand in next iteration)
        'students' => "
        CREATE TABLE IF NOT EXISTS `students` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `admission_number` VARCHAR(50) NOT NULL UNIQUE,
            `first_name` VARCHAR(100) NOT NULL,
            `middle_name` VARCHAR(100),
            `last_name` VARCHAR(100) NOT NULL,
            `date_of_birth` DATE,
            `gender` ENUM('male', 'female', 'other'),
            `status` ENUM('active', 'suspended', 'transferred', 'graduated', 'withdrawn') DEFAULT 'active',
            `admission_date` DATE,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted_at` TIMESTAMP NULL,
            INDEX `idx_admission_number` (`admission_number`),
            INDEX `idx_status` (`status`),
            INDEX `idx_deleted` (`deleted_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}",

        'guardians' => "
        CREATE TABLE IF NOT EXISTS `guardians` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `first_name` VARCHAR(100) NOT NULL,
            `last_name` VARCHAR(100) NOT NULL,
            `email` VARCHAR(255),
            `phone` VARCHAR(20),
            `id_number` VARCHAR(50),
            `address` TEXT,
            `occupation` VARCHAR(255),
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_phone` (`phone`),
            INDEX `idx_email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}",

        'student_guardians' => "
        CREATE TABLE IF NOT EXISTS `student_guardians` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `student_id` BIGINT UNSIGNED NOT NULL,
            `guardian_id` BIGINT UNSIGNED NOT NULL,
            `relationship` VARCHAR(50),
            `is_primary` TINYINT(1) DEFAULT 0,
            `can_pickup` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`guardian_id`) REFERENCES `guardians`(`id`) ON DELETE CASCADE,
            INDEX `idx_student` (`student_id`),
            INDEX `idx_guardian` (`guardian_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}",

        // Classes & Streams
        'classes' => "
        CREATE TABLE IF NOT EXISTS `classes` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `class_name` VARCHAR(100) NOT NULL,
            `level` INT,
            `capacity` INT,
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}",

        'streams' => "
        CREATE TABLE IF NOT EXISTS `streams` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `class_id` INT UNSIGNED NOT NULL,
            `stream_name` VARCHAR(50) NOT NULL,
            `capacity` INT,
            `teacher_id` BIGINT UNSIGNED,
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`teacher_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
            INDEX `idx_class` (`class_id`),
            INDEX `idx_teacher` (`teacher_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}",

        'student_enrollments' => "
        CREATE TABLE IF NOT EXISTS `student_enrollments` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `student_id` BIGINT UNSIGNED NOT NULL,
            `stream_id` INT UNSIGNED NOT NULL,
            `academic_year_id` INT UNSIGNED NOT NULL,
            `enrollment_date` DATE NOT NULL,
            `is_current` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`stream_id`) REFERENCES `streams`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years`(`id`) ON DELETE CASCADE,
            INDEX `idx_student` (`student_id`),
            INDEX `idx_stream` (`stream_id`),
            INDEX `idx_year` (`academic_year_id`),
            INDEX `idx_current` (`is_current`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation}"
    ];

    $count = 0;
    foreach ($tables as $tableName => $sql) {
        try {
            $pdo->exec($sql);
            echo "  ✓ {$tableName}\n";
            $count++;
        } catch (PDOException $e) {
            echo "  ✗ {$tableName}: " . $e->getMessage() . "\n";
        }
    }

    echo "\n✓ Created {$count} tables successfully\n\n";
}
