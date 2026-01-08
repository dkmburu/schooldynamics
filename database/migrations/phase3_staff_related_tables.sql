-- =========================================================================
-- Phase 3: Staff Related Tables
-- Tables for storing qualifications, employment history, references
-- =========================================================================

-- -------------------------------------------------------------------------
-- 1. Add title column to staff table if missing
-- -------------------------------------------------------------------------
SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'staff' AND column_name = 'title');
SET @query = IF(@column_exists = 0,
    'ALTER TABLE staff ADD COLUMN title VARCHAR(20) AFTER id',
    'SELECT 1');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add other missing columns to staff table
SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'staff' AND column_name = 'marital_status');
SET @query = IF(@column_exists = 0,
    'ALTER TABLE staff ADD COLUMN marital_status VARCHAR(20) AFTER nationality',
    'SELECT 1');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'staff' AND column_name = 'religion');
SET @query = IF(@column_exists = 0,
    'ALTER TABLE staff ADD COLUMN religion VARCHAR(50) AFTER marital_status',
    'SELECT 1');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'staff' AND column_name = 'blood_group');
SET @query = IF(@column_exists = 0,
    'ALTER TABLE staff ADD COLUMN blood_group VARCHAR(5) AFTER religion',
    'SELECT 1');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'staff' AND column_name = 'photo');
SET @query = IF(@column_exists = 0,
    'ALTER TABLE staff ADD COLUMN photo VARCHAR(500) AFTER blood_group',
    'SELECT 1');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -------------------------------------------------------------------------
-- 2. Staff Qualifications (Education)
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS staff_qualifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id BIGINT UNSIGNED NOT NULL,
    qualification_level VARCHAR(30) NOT NULL COMMENT 'phd, masters, bachelors, diploma, etc.',
    field_of_study VARCHAR(200),
    institution VARCHAR(255) NOT NULL,
    year_completed YEAR,
    grade_obtained VARCHAR(50),
    certificate_number VARCHAR(100),
    certificate_file VARCHAR(500),
    is_verified TINYINT(1) DEFAULT 0,
    verified_by BIGINT UNSIGNED,
    verified_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_staff (staff_id),
    INDEX idx_level (qualification_level),
    CONSTRAINT fk_qual_staff FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------------------
-- 3. Staff Employment History (Previous Jobs)
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS staff_employment_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id BIGINT UNSIGNED NOT NULL,
    employer_name VARCHAR(255) NOT NULL,
    job_title VARCHAR(200),
    start_date DATE,
    end_date DATE,
    reason_for_leaving VARCHAR(50),
    responsibilities TEXT,
    supervisor_name VARCHAR(200),
    supervisor_phone VARCHAR(30),
    is_verified TINYINT(1) DEFAULT 0,
    verified_by BIGINT UNSIGNED,
    verified_at DATETIME,
    verification_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_staff (staff_id),
    CONSTRAINT fk_emp_hist_staff FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------------------
-- 4. Staff References
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS staff_references (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id BIGINT UNSIGNED NOT NULL,
    referee_name VARCHAR(200) NOT NULL,
    relationship VARCHAR(50),
    organization VARCHAR(200),
    position VARCHAR(200),
    phone VARCHAR(30),
    email VARCHAR(200),
    years_known INT,
    is_verified TINYINT(1) DEFAULT 0,
    verified_by BIGINT UNSIGNED,
    verified_at DATETIME,
    verification_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_staff (staff_id),
    CONSTRAINT fk_ref_staff FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------------------
-- 5. Additional staff contact fields
-- -------------------------------------------------------------------------
SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'staff' AND column_name = 'email');
SET @query = IF(@column_exists = 0,
    'ALTER TABLE staff ADD COLUMN email VARCHAR(200) AFTER emergency_contact_phone',
    'SELECT 1');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'staff' AND column_name = 'phone');
SET @query = IF(@column_exists = 0,
    'ALTER TABLE staff ADD COLUMN phone VARCHAR(30) AFTER email',
    'SELECT 1');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'staff' AND column_name = 'alt_phone');
SET @query = IF(@column_exists = 0,
    'ALTER TABLE staff ADD COLUMN alt_phone VARCHAR(30) AFTER phone',
    'SELECT 1');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'staff' AND column_name = 'postal_address');
SET @query = IF(@column_exists = 0,
    'ALTER TABLE staff ADD COLUMN postal_address VARCHAR(200) AFTER alt_phone',
    'SELECT 1');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'staff' AND column_name = 'physical_address');
SET @query = IF(@column_exists = 0,
    'ALTER TABLE staff ADD COLUMN physical_address TEXT AFTER postal_address',
    'SELECT 1');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'staff' AND column_name = 'emergency_contact_relationship');
SET @query = IF(@column_exists = 0,
    'ALTER TABLE staff ADD COLUMN emergency_contact_relationship VARCHAR(50) AFTER emergency_contact_name',
    'SELECT 1');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -------------------------------------------------------------------------
-- 6. Additional employment fields
-- -------------------------------------------------------------------------
SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'staff' AND column_name = 'work_schedule');
SET @query = IF(@column_exists = 0,
    'ALTER TABLE staff ADD COLUMN work_schedule VARCHAR(30) AFTER department',
    'SELECT 1');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'staff' AND column_name = 'work_location');
SET @query = IF(@column_exists = 0,
    'ALTER TABLE staff ADD COLUMN work_location VARCHAR(200) AFTER work_schedule',
    'SELECT 1');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'staff' AND column_name = 'reports_to');
SET @query = IF(@column_exists = 0,
    'ALTER TABLE staff ADD COLUMN reports_to VARCHAR(200) AFTER work_location',
    'SELECT 1');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'staff' AND column_name = 'probation_end_date');
SET @query = IF(@column_exists = 0,
    'ALTER TABLE staff ADD COLUMN probation_end_date DATE AFTER contract_end_date',
    'SELECT 1');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -------------------------------------------------------------------------
-- 7. Salary & Bank fields
-- -------------------------------------------------------------------------
SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'staff' AND column_name = 'salary_structure_id');
SET @query = IF(@column_exists = 0,
    'ALTER TABLE staff ADD COLUMN salary_structure_id BIGINT UNSIGNED AFTER basic_salary',
    'SELECT 1');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'staff' AND column_name = 'salary_grade_id');
SET @query = IF(@column_exists = 0,
    'ALTER TABLE staff ADD COLUMN salary_grade_id BIGINT UNSIGNED AFTER salary_structure_id',
    'SELECT 1');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'staff' AND column_name = 'payment_mode');
SET @query = IF(@column_exists = 0,
    'ALTER TABLE staff ADD COLUMN payment_mode VARCHAR(20) DEFAULT ''bank'' AFTER bank_account_number',
    'SELECT 1');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'staff' AND column_name = 'bank_id');
SET @query = IF(@column_exists = 0,
    'ALTER TABLE staff ADD COLUMN bank_id INT AFTER payment_mode',
    'SELECT 1');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'staff' AND column_name = 'bank_branch');
SET @query = IF(@column_exists = 0,
    'ALTER TABLE staff ADD COLUMN bank_branch VARCHAR(100) AFTER bank_name',
    'SELECT 1');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'staff' AND column_name = 'bank_account_name');
SET @query = IF(@column_exists = 0,
    'ALTER TABLE staff ADD COLUMN bank_account_name VARCHAR(200) AFTER bank_account_number',
    'SELECT 1');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'staff' AND column_name = 'mpesa_number');
SET @query = IF(@column_exists = 0,
    'ALTER TABLE staff ADD COLUMN mpesa_number VARCHAR(15) AFTER bank_account_name',
    'SELECT 1');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Staff related tables created/updated successfully!' AS status;
