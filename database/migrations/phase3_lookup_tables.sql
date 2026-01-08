-- =========================================================================
-- Phase 3: HR & Payroll Lookup Tables
-- Normalized reference data for dropdowns
-- =========================================================================

-- -------------------------------------------------------------------------
-- 1. Countries Table - Add nationality column if missing
-- -------------------------------------------------------------------------
SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'countries' AND column_name = 'nationality');
SET @query = IF(@column_exists = 0, 'ALTER TABLE countries ADD COLUMN nationality VARCHAR(100) AFTER country_name', 'SELECT 1');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update nationalities for existing countries
UPDATE countries SET nationality = 'Kenyan' WHERE country_code = 'KE';
UPDATE countries SET nationality = 'Ugandan' WHERE country_code = 'UG';
UPDATE countries SET nationality = 'Tanzanian' WHERE country_code = 'TZ';
UPDATE countries SET nationality = 'Rwandan' WHERE country_code = 'RW';
UPDATE countries SET nationality = 'South Sudanese' WHERE country_code = 'SS';
UPDATE countries SET nationality = 'Ethiopian' WHERE country_code = 'ET';
UPDATE countries SET nationality = 'Nigerian' WHERE country_code = 'NG';
UPDATE countries SET nationality = 'Ghanaian' WHERE country_code = 'GH';
UPDATE countries SET nationality = 'South African' WHERE country_code = 'ZA';
UPDATE countries SET nationality = 'Indian' WHERE country_code = 'IN';
UPDATE countries SET nationality = 'British' WHERE country_code = 'GB';
UPDATE countries SET nationality = 'American' WHERE country_code = 'US';

-- -------------------------------------------------------------------------
-- 2. Relationship Types (for emergency contacts, references, etc.)
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS relationship_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category ENUM('emergency', 'reference', 'next_of_kin', 'dependent') NOT NULL,
    relationship_name VARCHAR(50) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    UNIQUE KEY uk_category_name (category, relationship_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO relationship_types (category, relationship_name, sort_order) VALUES
-- Emergency Contact relationships
('emergency', 'Spouse', 1),
('emergency', 'Parent', 2),
('emergency', 'Sibling', 3),
('emergency', 'Child', 4),
('emergency', 'Guardian', 5),
('emergency', 'Friend', 6),
('emergency', 'Colleague', 7),
('emergency', 'Other', 99),
-- Reference relationships
('reference', 'Former Supervisor', 1),
('reference', 'Former Colleague', 2),
('reference', 'Mentor/Teacher', 3),
('reference', 'Professional Acquaintance', 4),
('reference', 'Religious Leader', 5),
('reference', 'Community Leader', 6),
('reference', 'Other', 99),
-- Next of Kin
('next_of_kin', 'Spouse', 1),
('next_of_kin', 'Parent', 2),
('next_of_kin', 'Child', 3),
('next_of_kin', 'Sibling', 4),
('next_of_kin', 'Guardian', 5),
('next_of_kin', 'Other', 99),
-- Dependents
('dependent', 'Child', 1),
('dependent', 'Spouse', 2),
('dependent', 'Parent', 3),
('dependent', 'Sibling', 4),
('dependent', 'Other', 99);

-- -------------------------------------------------------------------------
-- 3. Blood Groups
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS blood_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    blood_group VARCHAR(5) NOT NULL UNIQUE,
    description VARCHAR(50),
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO blood_groups (blood_group, description, sort_order) VALUES
('A+', 'A Positive', 1),
('A-', 'A Negative', 2),
('B+', 'B Positive', 3),
('B-', 'B Negative', 4),
('AB+', 'AB Positive', 5),
('AB-', 'AB Negative', 6),
('O+', 'O Positive', 7),
('O-', 'O Negative', 8);

-- -------------------------------------------------------------------------
-- 4. Staff Types
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS staff_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_code VARCHAR(20) NOT NULL UNIQUE,
    type_name VARCHAR(50) NOT NULL,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO staff_types (type_code, type_name, description, sort_order) VALUES
('teaching', 'Teaching Staff', 'Teachers and instructors', 1),
('non-teaching', 'Non-Teaching Staff', 'Administrative and support staff not involved in teaching', 2),
('admin', 'Administrative Staff', 'Office and management personnel', 3),
('support', 'Support Staff', 'Maintenance, security, and other support roles', 4),
('intern', 'Intern', 'Temporary training positions', 5);

-- -------------------------------------------------------------------------
-- 5. Employment Types
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS employment_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_code VARCHAR(20) NOT NULL UNIQUE,
    type_name VARCHAR(50) NOT NULL,
    description TEXT,
    requires_contract_dates TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO employment_types (type_code, type_name, description, requires_contract_dates, sort_order) VALUES
('permanent', 'Permanent', 'Full-time permanent employment', 0, 1),
('contract', 'Contract', 'Fixed-term contract employment', 1, 2),
('part-time', 'Part-Time', 'Part-time employment', 0, 3),
('casual', 'Casual', 'Casual/daily worker', 0, 4),
('intern', 'Intern', 'Internship position', 1, 5),
('probation', 'Probation', 'On probationary period', 1, 6);

-- -------------------------------------------------------------------------
-- 6. Work Schedules
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS work_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_code VARCHAR(20) NOT NULL UNIQUE,
    schedule_name VARCHAR(50) NOT NULL,
    description TEXT,
    start_time TIME,
    end_time TIME,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO work_schedules (schedule_code, schedule_name, description, start_time, end_time, sort_order) VALUES
('full-time', 'Full Time', 'Standard full-time schedule', '08:00:00', '17:00:00', 1),
('morning', 'Morning Shift', 'Morning shift schedule', '06:00:00', '14:00:00', 2),
('afternoon', 'Afternoon Shift', 'Afternoon shift schedule', '12:00:00', '20:00:00', 3),
('evening', 'Evening Shift', 'Evening shift schedule', '14:00:00', '22:00:00', 4),
('flexible', 'Flexible Hours', 'Flexible working hours', NULL, NULL, 5),
('remote', 'Remote Work', 'Work from home/remote', NULL, NULL, 6);

-- -------------------------------------------------------------------------
-- 7. Payment Modes
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS payment_modes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mode_code VARCHAR(20) NOT NULL UNIQUE,
    mode_name VARCHAR(50) NOT NULL,
    description TEXT,
    requires_bank_details TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO payment_modes (mode_code, mode_name, description, requires_bank_details, sort_order) VALUES
('bank', 'Bank Transfer', 'Direct bank transfer/EFT', 1, 1),
('mpesa', 'M-Pesa', 'Mobile money transfer via M-Pesa', 0, 2),
('cheque', 'Cheque', 'Payment by cheque', 0, 3),
('cash', 'Cash', 'Cash payment', 0, 4);

-- -------------------------------------------------------------------------
-- 8. Education Levels
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS education_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level_code VARCHAR(20) NOT NULL UNIQUE,
    level_name VARCHAR(100) NOT NULL,
    description TEXT,
    rank_order INT DEFAULT 0 COMMENT 'Higher = more advanced',
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO education_levels (level_code, level_name, rank_order, sort_order) VALUES
('phd', 'PhD / Doctorate', 100, 1),
('masters', 'Master\'s Degree', 90, 2),
('pgdip', 'Postgraduate Diploma', 85, 3),
('bachelors', 'Bachelor\'s Degree', 80, 4),
('higher_diploma', 'Higher Diploma', 75, 5),
('diploma', 'Diploma', 70, 6),
('certificate', 'Certificate', 60, 7),
('a_level', 'A-Level / KACE', 50, 8),
('kcse', 'KCSE / O-Level', 40, 9),
('kcpe', 'KCPE / Primary', 30, 10),
('other', 'Other', 0, 99);

-- -------------------------------------------------------------------------
-- 9. Leaving Reasons
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS leaving_reasons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reason_code VARCHAR(30) NOT NULL UNIQUE,
    reason_name VARCHAR(100) NOT NULL,
    category ENUM('voluntary', 'involuntary', 'neutral') DEFAULT 'voluntary',
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO leaving_reasons (reason_code, reason_name, category, sort_order) VALUES
('better_opportunity', 'Better Opportunity', 'voluntary', 1),
('career_growth', 'Career Growth', 'voluntary', 2),
('higher_education', 'Pursuing Higher Education', 'voluntary', 3),
('relocation', 'Relocation', 'voluntary', 4),
('family_reasons', 'Family Reasons', 'voluntary', 5),
('health_reasons', 'Health Reasons', 'neutral', 6),
('personal_reasons', 'Personal Reasons', 'voluntary', 7),
('contract_end', 'Contract Ended', 'neutral', 8),
('retirement', 'Retirement', 'neutral', 9),
('termination', 'Termination', 'involuntary', 10),
('laid_off', 'Laid Off / Retrenchment', 'involuntary', 11),
('business_closure', 'Business Closure', 'involuntary', 12),
('misconduct', 'Misconduct', 'involuntary', 13),
('other', 'Other', 'neutral', 99);

-- -------------------------------------------------------------------------
-- 10. Marital Status
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS marital_statuses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    status_code VARCHAR(20) NOT NULL UNIQUE,
    status_name VARCHAR(50) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO marital_statuses (status_code, status_name, sort_order) VALUES
('single', 'Single', 1),
('married', 'Married', 2),
('divorced', 'Divorced', 3),
('widowed', 'Widowed', 4),
('separated', 'Separated', 5);

-- -------------------------------------------------------------------------
-- 11. Banks (Kenya specific)
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS banks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bank_code VARCHAR(10) NOT NULL UNIQUE,
    bank_name VARCHAR(100) NOT NULL,
    swift_code VARCHAR(20),
    country_code VARCHAR(3) DEFAULT 'KE',
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO banks (bank_code, bank_name, swift_code, sort_order) VALUES
('01', 'Kenya Commercial Bank (KCB)', 'KCBLKENX', 1),
('11', 'Co-operative Bank of Kenya', 'KCOOKENA', 2),
('68', 'Equity Bank', 'EABORKE1', 3),
('07', 'NCBA Bank', 'CBABORKE', 4),
('03', 'Barclays Bank / Absa Kenya', 'BABORKE1', 5),
('02', 'Standard Chartered Bank', 'SCBLKENA', 6),
('31', 'Stanbic Bank Kenya', 'SBICKENX', 7),
('57', 'I&M Bank', 'IMABORKE', 8),
('63', 'Diamond Trust Bank', 'DTKEKENX', 9),
('70', 'Family Bank', 'FABORKEN', 10),
('19', 'Bank of Africa Kenya', 'AFRIKENX', 11),
('10', 'Prime Bank', 'PRABORKE', 12),
('66', 'Sidian Bank', 'ABORKENX', 13),
('72', 'Gulf African Bank', 'GABORKEN', 14),
('50', 'Paramount Bank', 'PABORKEN', 15),
('51', 'Credit Bank', 'CRBORKEN', 16),
('74', 'First Community Bank', 'FCBORKEN', 17),
('76', 'Mayfair Bank', 'MFABORKE', 18),
('54', 'Victoria Commercial Bank', 'VCABORKE', 19),
('99', 'Other', NULL, 99);

-- -------------------------------------------------------------------------
-- 12. Staff Document Types (separate from existing document_types)
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS staff_document_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_code VARCHAR(30) NOT NULL UNIQUE,
    type_name VARCHAR(100) NOT NULL,
    category ENUM('identity', 'academic', 'professional', 'statutory', 'employment', 'other') DEFAULT 'other',
    is_required TINYINT(1) DEFAULT 0,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO staff_document_types (type_code, type_name, category, is_required, sort_order) VALUES
('national_id', 'National ID Card', 'identity', 1, 1),
('passport', 'Passport', 'identity', 0, 2),
('birth_cert', 'Birth Certificate', 'identity', 0, 3),
('academic_cert', 'Academic Certificates', 'academic', 1, 10),
('transcript', 'Academic Transcripts', 'academic', 0, 11),
('cv', 'CV / Resume', 'employment', 1, 20),
('offer_letter', 'Offer Letter', 'employment', 0, 21),
('contract', 'Employment Contract', 'employment', 0, 22),
('tsc_cert', 'TSC Registration Certificate', 'professional', 0, 30),
('professional_cert', 'Professional Certifications', 'professional', 0, 31),
('kra_pin', 'KRA PIN Certificate', 'statutory', 1, 40),
('nssf_card', 'NSSF Card', 'statutory', 0, 41),
('nhif_card', 'NHIF Card', 'statutory', 0, 42),
('good_conduct', 'Certificate of Good Conduct', 'statutory', 1, 43),
('medical_cert', 'Medical Fitness Certificate', 'statutory', 0, 44),
('recommendation', 'Recommendation Letters', 'other', 0, 50),
('other', 'Other Documents', 'other', 0, 99);

-- -------------------------------------------------------------------------
-- 13. Religions
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS religions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    religion_name VARCHAR(50) NOT NULL UNIQUE,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO religions (religion_name, sort_order) VALUES
('Christianity', 1),
('Islam', 2),
('Hinduism', 3),
('Buddhism', 4),
('Traditional African', 5),
('Sikhism', 6),
('Judaism', 7),
('Other', 98),
('Prefer not to say', 99);

SELECT 'Lookup tables created successfully!' AS status;
