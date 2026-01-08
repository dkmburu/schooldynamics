-- =====================================================
-- Phase 3: HR & Payroll Module - Database Migration
-- Version: 1.0
-- Description: Creates all tables for HR & Payroll system
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- SECTION 1: Enhance Existing Tables
-- =====================================================

-- Helper procedure to add column if not exists
DROP PROCEDURE IF EXISTS add_column_if_not_exists;
DELIMITER //
CREATE PROCEDURE add_column_if_not_exists(
    IN p_table_name VARCHAR(64),
    IN p_column_name VARCHAR(64),
    IN p_column_definition VARCHAR(255)
)
BEGIN
    SET @table_name = p_table_name;
    SET @column_name = p_column_name;
    SET @db_name = DATABASE();

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = @db_name
        AND table_name = @table_name
        AND column_name = @column_name
    ) THEN
        SET @sql = CONCAT('ALTER TABLE ', p_table_name, ' ADD COLUMN ', p_column_name, ' ', p_column_definition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //
DELIMITER ;

-- Helper procedure to add foreign key if not exists
DROP PROCEDURE IF EXISTS add_fk_if_not_exists;
DELIMITER //
CREATE PROCEDURE add_fk_if_not_exists(
    IN p_table_name VARCHAR(64),
    IN p_fk_name VARCHAR(64),
    IN p_fk_definition VARCHAR(500)
)
BEGIN
    SET @db_name = DATABASE();

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
        WHERE TABLE_SCHEMA = @db_name
        AND TABLE_NAME = p_table_name
        AND CONSTRAINT_NAME = p_fk_name
    ) THEN
        SET @sql = CONCAT('ALTER TABLE ', p_table_name, ' ADD CONSTRAINT ', p_fk_name, ' ', p_fk_definition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //
DELIMITER ;

-- 1.1 Add currency and payroll fields to countries table
CALL add_column_if_not_exists('countries', 'currency_code', 'VARCHAR(3) AFTER country_name');
CALL add_column_if_not_exists('countries', 'currency_symbol', 'VARCHAR(10) AFTER currency_code');
CALL add_column_if_not_exists('countries', 'currency_name', 'VARCHAR(50) AFTER currency_symbol');
CALL add_column_if_not_exists('countries', 'date_format', "VARCHAR(20) DEFAULT 'Y-m-d' AFTER currency_name");
CALL add_column_if_not_exists('countries', 'financial_year_start', 'TINYINT DEFAULT 1 AFTER date_format');
CALL add_column_if_not_exists('countries', 'decimal_places', 'TINYINT DEFAULT 2 AFTER financial_year_start');
CALL add_column_if_not_exists('countries', 'thousand_separator', "VARCHAR(1) DEFAULT ',' AFTER decimal_places");
CALL add_column_if_not_exists('countries', 'decimal_separator', "VARCHAR(1) DEFAULT '.' AFTER thousand_separator");
CALL add_column_if_not_exists('countries', 'phone_code', 'VARCHAR(10) AFTER decimal_separator');

-- Update existing countries with currency data
UPDATE countries SET currency_code = 'KES', currency_symbol = 'KES', currency_name = 'Kenyan Shilling', phone_code = '+254' WHERE country_code = 'KE';
UPDATE countries SET currency_code = 'UGX', currency_symbol = 'USh', currency_name = 'Ugandan Shilling', phone_code = '+256' WHERE country_code = 'UG';
UPDATE countries SET currency_code = 'TZS', currency_symbol = 'TSh', currency_name = 'Tanzanian Shilling', phone_code = '+255' WHERE country_code = 'TZ';
UPDATE countries SET currency_code = 'RWF', currency_symbol = 'FRw', currency_name = 'Rwandan Franc', phone_code = '+250' WHERE country_code = 'RW';
UPDATE countries SET currency_code = 'ETB', currency_symbol = 'Br', currency_name = 'Ethiopian Birr', phone_code = '+251' WHERE country_code = 'ET';
UPDATE countries SET currency_code = 'SSP', currency_symbol = 'SSP', currency_name = 'South Sudanese Pound', phone_code = '+211' WHERE country_code = 'SS';
UPDATE countries SET currency_code = 'SOS', currency_symbol = 'Sh', currency_name = 'Somali Shilling', phone_code = '+252' WHERE country_code = 'SO';
UPDATE countries SET currency_code = 'CDF', currency_symbol = 'FC', currency_name = 'Congolese Franc', phone_code = '+243' WHERE country_code = 'CD';
UPDATE countries SET currency_code = 'ZAR', currency_symbol = 'R', currency_name = 'South African Rand', phone_code = '+27' WHERE country_code = 'ZA';
UPDATE countries SET currency_code = 'NGN', currency_symbol = '₦', currency_name = 'Nigerian Naira', phone_code = '+234' WHERE country_code = 'NG';

-- 1.2 Add country_id to school_profile
CALL add_column_if_not_exists('school_profile', 'country_id', 'INT AFTER currency');

-- Set default country_id based on currency (Kenya for KES)
UPDATE school_profile sp
SET country_id = (SELECT id FROM countries WHERE country_code = 'KE' LIMIT 1)
WHERE sp.currency = 'KES' AND sp.country_id IS NULL;

-- =====================================================
-- SECTION 2: Payroll Regions (for state-based taxes)
-- =====================================================

CREATE TABLE IF NOT EXISTS payroll_regions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    country_id INT NOT NULL,
    region_code VARCHAR(10) NOT NULL,
    region_name VARCHAR(100) NOT NULL,
    has_separate_tax_rules TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (country_id) REFERENCES countries(id),
    UNIQUE KEY unique_region (country_id, region_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- SECTION 3: Tax Configuration
-- =====================================================

-- 3.1 Tax brackets (versioned)
CREATE TABLE IF NOT EXISTS tax_brackets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    country_id INT NOT NULL,
    region_id INT NULL,
    name VARCHAR(100) NOT NULL,
    effective_from DATE NOT NULL,
    effective_to DATE NULL,
    is_current TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (country_id) REFERENCES countries(id),
    FOREIGN KEY (region_id) REFERENCES payroll_regions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3.2 Tax bands within brackets
CREATE TABLE IF NOT EXISTS tax_bands (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bracket_id INT NOT NULL,
    band_order TINYINT NOT NULL,
    min_amount DECIMAL(15,2) NOT NULL,
    max_amount DECIMAL(15,2) NULL,
    rate DECIMAL(5,2) NOT NULL,
    fixed_amount DECIMAL(15,2) DEFAULT 0,
    description VARCHAR(100),
    FOREIGN KEY (bracket_id) REFERENCES tax_brackets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3.3 Tax reliefs
CREATE TABLE IF NOT EXISTS tax_reliefs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    country_id INT NOT NULL,
    relief_code VARCHAR(50) NOT NULL,
    relief_name VARCHAR(100) NOT NULL,
    relief_type ENUM('fixed', 'percentage', 'formula') NOT NULL,
    amount DECIMAL(15,2) NULL,
    percentage DECIMAL(5,2) NULL,
    formula_config JSON NULL,
    max_amount DECIMAL(15,2) NULL,
    effective_from DATE NOT NULL,
    effective_to DATE NULL,
    requires_proof TINYINT(1) DEFAULT 0,
    applies_to ENUM('all', 'resident', 'non_resident') DEFAULT 'all',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (country_id) REFERENCES countries(id),
    UNIQUE KEY unique_relief (country_id, relief_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- SECTION 4: Statutory Funds
-- =====================================================

-- 4.1 Statutory funds master
CREATE TABLE IF NOT EXISTS statutory_funds (
    id INT PRIMARY KEY AUTO_INCREMENT,
    country_id INT NOT NULL,
    fund_code VARCHAR(50) NOT NULL,
    fund_name VARCHAR(100) NOT NULL,
    fund_type ENUM('pension', 'health', 'insurance', 'levy', 'other') NOT NULL,
    is_mandatory TINYINT(1) DEFAULT 1,
    employer_number_label VARCHAR(50),
    employee_number_label VARCHAR(50),
    website_url VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (country_id) REFERENCES countries(id),
    UNIQUE KEY unique_fund (country_id, fund_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4.2 Statutory fund rates (versioned)
CREATE TABLE IF NOT EXISTS statutory_fund_rates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fund_id INT NOT NULL,
    effective_from DATE NOT NULL,
    effective_to DATE NULL,

    -- Employee contribution
    employee_rate_type ENUM('fixed', 'percentage', 'tiered', 'formula') NOT NULL,
    employee_rate DECIMAL(10,4) NULL,
    employee_fixed DECIMAL(15,2) NULL,
    employee_formula JSON NULL,
    employee_min DECIMAL(15,2) NULL,
    employee_max DECIMAL(15,2) NULL,

    -- Employer contribution
    employer_rate_type ENUM('fixed', 'percentage', 'tiered', 'formula') NOT NULL DEFAULT 'fixed',
    employer_rate DECIMAL(10,4) NULL,
    employer_fixed DECIMAL(15,2) NULL,
    employer_formula JSON NULL,
    employer_min DECIMAL(15,2) NULL,
    employer_max DECIMAL(15,2) NULL,

    -- Calculation basis
    calculation_basis ENUM('gross', 'basic', 'pensionable', 'taxable', 'custom') DEFAULT 'gross',
    custom_basis_formula JSON NULL,

    is_current TINYINT(1) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fund_id) REFERENCES statutory_funds(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4.3 Tiered rates for funds like NHIF
CREATE TABLE IF NOT EXISTS statutory_fund_tiers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fund_rate_id INT NOT NULL,
    tier_order TINYINT NOT NULL,
    min_income DECIMAL(15,2) NOT NULL,
    max_income DECIMAL(15,2) NULL,
    employee_amount DECIMAL(15,2) NOT NULL,
    employer_amount DECIMAL(15,2) NULL,
    FOREIGN KEY (fund_rate_id) REFERENCES statutory_fund_rates(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- SECTION 5: Salary Structure
-- =====================================================

-- 5.1 Pay components
CREATE TABLE IF NOT EXISTS pay_components (
    id INT PRIMARY KEY AUTO_INCREMENT,
    country_id INT NULL,
    component_code VARCHAR(50) NOT NULL,
    component_name VARCHAR(100) NOT NULL,
    component_type ENUM('earning', 'deduction', 'employer_contribution') NOT NULL,
    category ENUM('basic', 'allowance', 'bonus', 'overtime', 'statutory', 'voluntary', 'loan', 'reimbursement', 'other') NOT NULL,

    -- Calculation settings
    calculation_type ENUM('fixed', 'percentage', 'formula', 'hourly', 'daily') NOT NULL,
    percentage_of VARCHAR(50) NULL,
    formula_config JSON NULL,

    -- Tax treatment
    is_taxable TINYINT(1) DEFAULT 1,
    tax_exemption_limit DECIMAL(15,2) NULL,

    -- For statutory components
    statutory_fund_id INT NULL,

    -- Display settings
    display_order INT DEFAULT 0,
    show_on_payslip TINYINT(1) DEFAULT 1,
    is_system TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (country_id) REFERENCES countries(id),
    FOREIGN KEY (statutory_fund_id) REFERENCES statutory_funds(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5.2 Salary grades
CREATE TABLE IF NOT EXISTS salary_grades (
    id INT PRIMARY KEY AUTO_INCREMENT,
    grade_code VARCHAR(20) NOT NULL,
    grade_name VARCHAR(100) NOT NULL,
    min_salary DECIMAL(15,2) NOT NULL,
    max_salary DECIMAL(15,2) NOT NULL,
    default_salary DECIMAL(15,2) NULL,
    annual_increment DECIMAL(15,2) NULL,
    increment_percentage DECIMAL(5,2) NULL,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_grade (grade_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5.3 Salary structures (templates)
CREATE TABLE IF NOT EXISTS salary_structures (
    id INT PRIMARY KEY AUTO_INCREMENT,
    structure_code VARCHAR(50) NOT NULL,
    structure_name VARCHAR(100) NOT NULL,
    description TEXT,
    is_default TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_structure (structure_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5.4 Components within salary structure
CREATE TABLE IF NOT EXISTS salary_structure_components (
    id INT PRIMARY KEY AUTO_INCREMENT,
    structure_id INT NOT NULL,
    component_id INT NOT NULL,
    is_mandatory TINYINT(1) DEFAULT 0,
    default_value DECIMAL(15,2) NULL,
    default_percentage DECIMAL(5,2) NULL,
    calculation_order INT DEFAULT 0,
    FOREIGN KEY (structure_id) REFERENCES salary_structures(id) ON DELETE CASCADE,
    FOREIGN KEY (component_id) REFERENCES pay_components(id),
    UNIQUE KEY unique_structure_component (structure_id, component_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- SECTION 6: Departments & Designations
-- =====================================================

-- 6.1 Departments
CREATE TABLE IF NOT EXISTS hr_departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    department_code VARCHAR(20) NOT NULL,
    department_name VARCHAR(100) NOT NULL,
    parent_id INT NULL,
    head_staff_id BIGINT UNSIGNED NULL,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_dept (department_code),
    FOREIGN KEY (parent_id) REFERENCES hr_departments(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6.2 Designations/Job Titles
CREATE TABLE IF NOT EXISTS hr_designations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    designation_code VARCHAR(20) NOT NULL,
    designation_name VARCHAR(100) NOT NULL,
    department_id INT NULL,
    salary_grade_id INT NULL,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_designation (designation_code),
    FOREIGN KEY (department_id) REFERENCES hr_departments(id),
    FOREIGN KEY (salary_grade_id) REFERENCES salary_grades(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- SECTION 7: Staff/Employee Records
-- =====================================================

-- 7.1 Staff master table
CREATE TABLE IF NOT EXISTS staff (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    employee_number VARCHAR(50) NOT NULL,
    user_id BIGINT UNSIGNED NULL,

    -- Personal Information
    title VARCHAR(10),
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    last_name VARCHAR(100) NOT NULL,
    gender ENUM('male', 'female', 'other'),
    date_of_birth DATE,
    nationality VARCHAR(100),
    national_id_number VARCHAR(50),
    passport_number VARCHAR(50),
    marital_status ENUM('single', 'married', 'divorced', 'widowed'),

    -- Contact Information
    email VARCHAR(255),
    phone VARCHAR(50),
    alternative_phone VARCHAR(50),
    address_line1 VARCHAR(255),
    address_line2 VARCHAR(255),
    city VARCHAR(100),
    state_region VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100),

    -- Emergency Contact
    emergency_contact_name VARCHAR(200),
    emergency_contact_phone VARCHAR(50),
    emergency_contact_relationship VARCHAR(50),

    -- Employment Details
    department_id INT,
    designation_id INT,
    employment_type ENUM('permanent', 'contract', 'part_time', 'casual', 'intern') NOT NULL DEFAULT 'permanent',
    employment_status ENUM('active', 'on_leave', 'suspended', 'terminated', 'retired') DEFAULT 'active',
    date_joined DATE NOT NULL,
    date_confirmed DATE,
    date_terminated DATE,
    termination_reason TEXT,
    probation_period_months TINYINT DEFAULT 3,
    notice_period_days INT DEFAULT 30,

    -- Payroll Settings
    country_id INT NOT NULL,
    region_id INT NULL,
    salary_structure_id INT,
    salary_grade_id INT,
    pay_frequency ENUM('monthly', 'bi_weekly', 'weekly', 'daily') DEFAULT 'monthly',
    payment_method ENUM('bank', 'cash', 'mobile_money', 'cheque') DEFAULT 'bank',

    -- Casual Worker Settings
    is_casual TINYINT(1) DEFAULT 0,
    daily_rate DECIMAL(15,2) NULL,
    hourly_rate DECIMAL(15,2) NULL,

    -- Bank Details
    bank_name VARCHAR(100),
    bank_branch VARCHAR(100),
    bank_account_number VARCHAR(50),
    bank_account_name VARCHAR(200),
    bank_swift_code VARCHAR(20),
    bank_code VARCHAR(20),

    -- Mobile Money
    mobile_money_provider VARCHAR(50),
    mobile_money_number VARCHAR(50),

    -- Tax Information
    tax_pin VARCHAR(50),
    is_tax_resident TINYINT(1) DEFAULT 1,
    tax_exemption_certificate VARCHAR(100),

    -- Photo and Documents
    photo_path VARCHAR(255),

    created_by BIGINT UNSIGNED,
    updated_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_employee (employee_number),
    FOREIGN KEY (country_id) REFERENCES countries(id),
    FOREIGN KEY (region_id) REFERENCES payroll_regions(id),
    FOREIGN KEY (department_id) REFERENCES hr_departments(id),
    FOREIGN KEY (designation_id) REFERENCES hr_designations(id),
    FOREIGN KEY (salary_structure_id) REFERENCES salary_structures(id),
    FOREIGN KEY (salary_grade_id) REFERENCES salary_grades(id),
    INDEX idx_staff_status (employment_status),
    INDEX idx_staff_type (employment_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add FK to hr_departments for head_staff_id
CALL add_fk_if_not_exists('hr_departments', 'fk_dept_head', 'FOREIGN KEY (head_staff_id) REFERENCES staff(id) ON DELETE SET NULL');

-- 7.2 Staff statutory registrations
CREATE TABLE IF NOT EXISTS staff_statutory_registrations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    staff_id BIGINT UNSIGNED NOT NULL,
    fund_id INT NOT NULL,
    registration_number VARCHAR(100),
    registration_date DATE,
    is_active TINYINT(1) DEFAULT 1,
    exemption_certificate VARCHAR(100),
    exemption_reason TEXT,
    exemption_expiry DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
    FOREIGN KEY (fund_id) REFERENCES statutory_funds(id),
    UNIQUE KEY unique_staff_fund (staff_id, fund_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7.3 Staff salary records (versioned)
CREATE TABLE IF NOT EXISTS staff_salaries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    staff_id BIGINT UNSIGNED NOT NULL,
    effective_from DATE NOT NULL,
    effective_to DATE NULL,

    basic_salary DECIMAL(15,2) NOT NULL,
    currency_code VARCHAR(3) NOT NULL DEFAULT 'KES',

    revision_reason ENUM('new_hire', 'promotion', 'annual_increment', 'adjustment', 'demotion') DEFAULT 'new_hire',
    revision_notes TEXT,

    approved_by BIGINT UNSIGNED,
    approved_at DATETIME,

    is_current TINYINT(1) DEFAULT 0,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
    INDEX idx_salary_current (staff_id, is_current)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7.4 Staff salary component overrides
CREATE TABLE IF NOT EXISTS staff_salary_components (
    id INT PRIMARY KEY AUTO_INCREMENT,
    staff_salary_id INT NOT NULL,
    component_id INT NOT NULL,
    amount DECIMAL(15,2) NULL,
    percentage DECIMAL(5,2) NULL,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (staff_salary_id) REFERENCES staff_salaries(id) ON DELETE CASCADE,
    FOREIGN KEY (component_id) REFERENCES pay_components(id),
    UNIQUE KEY unique_salary_component (staff_salary_id, component_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7.5 Casual work logs
CREATE TABLE IF NOT EXISTS casual_work_logs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    staff_id BIGINT UNSIGNED NOT NULL,
    work_date DATE NOT NULL,
    hours_worked DECIMAL(5,2) DEFAULT 8,
    rate_applied DECIMAL(15,2) NOT NULL,
    rate_type ENUM('daily', 'hourly') DEFAULT 'daily',
    gross_amount DECIMAL(15,2) NOT NULL,
    description VARCHAR(255),
    verified_by BIGINT UNSIGNED,
    verified_at DATETIME,
    payroll_run_id INT NULL,
    status ENUM('pending', 'verified', 'paid') DEFAULT 'pending',
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
    UNIQUE KEY unique_staff_date (staff_id, work_date),
    INDEX idx_casual_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- SECTION 8: Payroll Processing
-- =====================================================

-- 8.1 Pay periods
CREATE TABLE IF NOT EXISTS pay_periods (
    id INT PRIMARY KEY AUTO_INCREMENT,
    period_name VARCHAR(100) NOT NULL,
    period_type ENUM('monthly', 'bi_weekly', 'weekly') NOT NULL DEFAULT 'monthly',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    payment_date DATE NOT NULL,
    status ENUM('draft', 'processing', 'pending_approval', 'approved', 'paid', 'closed') DEFAULT 'draft',

    processed_by BIGINT UNSIGNED,
    processed_at DATETIME,
    approved_by BIGINT UNSIGNED,
    approved_at DATETIME,
    paid_by BIGINT UNSIGNED,
    paid_at DATETIME,

    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_period (period_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8.2 Payroll runs
CREATE TABLE IF NOT EXISTS payroll_runs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pay_period_id INT NOT NULL,
    run_number TINYINT DEFAULT 1,
    run_type ENUM('regular', 'supplementary', 'bonus', 'final') DEFAULT 'regular',

    total_employees INT DEFAULT 0,
    total_gross DECIMAL(18,2) DEFAULT 0,
    total_deductions DECIMAL(18,2) DEFAULT 0,
    total_employer_costs DECIMAL(18,2) DEFAULT 0,
    total_net DECIMAL(18,2) DEFAULT 0,

    status ENUM('draft', 'calculated', 'pending_approval', 'approved', 'paid') DEFAULT 'draft',

    calculated_by BIGINT UNSIGNED,
    calculated_at DATETIME,
    approved_by BIGINT UNSIGNED,
    approved_at DATETIME,

    workflow_ticket_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (pay_period_id) REFERENCES pay_periods(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8.3 Payslips
CREATE TABLE IF NOT EXISTS payslips (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    payroll_run_id INT NOT NULL,
    staff_id BIGINT UNSIGNED NOT NULL,

    -- Employee snapshot
    employee_number VARCHAR(50),
    employee_name VARCHAR(300),
    department_name VARCHAR(100),
    designation_name VARCHAR(100),

    -- Pay details
    days_worked DECIMAL(5,2) DEFAULT 0,
    days_absent DECIMAL(5,2) DEFAULT 0,
    days_leave DECIMAL(5,2) DEFAULT 0,
    hours_overtime DECIMAL(6,2) DEFAULT 0,

    -- Calculated amounts
    basic_salary DECIMAL(15,2) NOT NULL,
    gross_earnings DECIMAL(15,2) NOT NULL,
    total_deductions DECIMAL(15,2) NOT NULL,
    net_salary DECIMAL(15,2) NOT NULL,

    -- Employer costs
    employer_contributions DECIMAL(15,2) DEFAULT 0,
    total_cost_to_company DECIMAL(15,2),

    -- Tax details
    taxable_income DECIMAL(15,2),
    tax_payable DECIMAL(15,2),
    tax_relief DECIMAL(15,2),
    net_tax DECIMAL(15,2),

    -- Payment
    payment_method ENUM('bank', 'cash', 'mobile_money', 'cheque'),
    payment_reference VARCHAR(100),
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    paid_at DATETIME,

    -- Bank details snapshot
    bank_name VARCHAR(100),
    bank_account_number VARCHAR(50),
    bank_account_name VARCHAR(200),

    -- YTD calculations
    ytd_gross DECIMAL(18,2),
    ytd_tax DECIMAL(18,2),
    ytd_net DECIMAL(18,2),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (payroll_run_id) REFERENCES payroll_runs(id),
    FOREIGN KEY (staff_id) REFERENCES staff(id),
    INDEX idx_payslip_staff (staff_id),
    INDEX idx_payslip_run (payroll_run_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8.4 Payslip line items
CREATE TABLE IF NOT EXISTS payslip_items (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    payslip_id BIGINT UNSIGNED NOT NULL,
    component_id INT NOT NULL,

    component_code VARCHAR(50),
    component_name VARCHAR(100),
    component_type ENUM('earning', 'deduction', 'employer_contribution'),

    amount DECIMAL(15,2) NOT NULL,
    is_taxable TINYINT(1) DEFAULT 1,

    base_amount DECIMAL(15,2) NULL,
    rate DECIMAL(10,4) NULL,

    calculation_notes TEXT,

    FOREIGN KEY (payslip_id) REFERENCES payslips(id) ON DELETE CASCADE,
    FOREIGN KEY (component_id) REFERENCES pay_components(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- SECTION 9: Loans & Advances
-- =====================================================

-- 9.1 Loan types
CREATE TABLE IF NOT EXISTS loan_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    loan_code VARCHAR(50) NOT NULL,
    loan_name VARCHAR(100) NOT NULL,
    max_amount DECIMAL(15,2),
    max_repayment_months INT,
    interest_rate DECIMAL(5,2) DEFAULT 0,
    interest_type ENUM('simple', 'compound', 'flat') DEFAULT 'simple',
    requires_approval TINYINT(1) DEFAULT 1,
    max_concurrent_loans TINYINT DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_loan_type (loan_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9.2 Staff loans
CREATE TABLE IF NOT EXISTS staff_loans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    staff_id BIGINT UNSIGNED NOT NULL,
    loan_type_id INT NOT NULL,

    loan_amount DECIMAL(15,2) NOT NULL,
    interest_amount DECIMAL(15,2) DEFAULT 0,
    total_repayable DECIMAL(15,2) NOT NULL,

    monthly_deduction DECIMAL(15,2) NOT NULL,
    repayment_months INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,

    amount_paid DECIMAL(15,2) DEFAULT 0,
    balance DECIMAL(15,2),

    status ENUM('pending', 'approved', 'active', 'completed', 'cancelled') DEFAULT 'pending',

    requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    approved_by BIGINT UNSIGNED,
    approved_at DATETIME,

    workflow_ticket_id INT NULL,
    notes TEXT,

    FOREIGN KEY (staff_id) REFERENCES staff(id),
    FOREIGN KEY (loan_type_id) REFERENCES loan_types(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9.3 Loan repayments
CREATE TABLE IF NOT EXISTS loan_repayments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    loan_id INT NOT NULL,
    installment_number INT NOT NULL,
    due_date DATE NOT NULL,
    principal_amount DECIMAL(15,2),
    interest_amount DECIMAL(15,2),
    total_amount DECIMAL(15,2) NOT NULL,

    paid_amount DECIMAL(15,2) DEFAULT 0,
    paid_date DATE,
    payslip_id BIGINT UNSIGNED NULL,

    status ENUM('pending', 'paid', 'partial', 'overdue') DEFAULT 'pending',

    FOREIGN KEY (loan_id) REFERENCES staff_loans(id) ON DELETE CASCADE,
    FOREIGN KEY (payslip_id) REFERENCES payslips(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- SECTION 10: Leave Management
-- =====================================================
-- NOTE: leave_types, leave_balances, leave_applications tables already exist
-- We'll enhance them with additional columns for payroll integration

-- 10.1 Add payroll-related columns to existing leave_types table
CALL add_column_if_not_exists('leave_types', 'country_id', 'INT UNSIGNED NULL AFTER id');
CALL add_column_if_not_exists('leave_types', 'default_days_per_year', 'INT NULL AFTER max_days_per_year');
CALL add_column_if_not_exists('leave_types', 'pay_percentage', 'DECIMAL(5,2) DEFAULT 100 AFTER is_paid');
CALL add_column_if_not_exists('leave_types', 'requires_documentation', 'TINYINT(1) DEFAULT 0 AFTER requires_approval');
CALL add_column_if_not_exists('leave_types', 'min_days_notice', 'INT DEFAULT 0 AFTER requires_documentation');
CALL add_column_if_not_exists('leave_types', 'allow_carry_forward', 'TINYINT(1) DEFAULT 0 AFTER min_days_notice');
CALL add_column_if_not_exists('leave_types', 'max_carry_forward_days', 'INT NULL AFTER allow_carry_forward');
CALL add_column_if_not_exists('leave_types', 'carry_forward_expiry_months', 'INT NULL AFTER max_carry_forward_days');
CALL add_column_if_not_exists('leave_types', 'allow_encashment', 'TINYINT(1) DEFAULT 0 AFTER carry_forward_expiry_months');
CALL add_column_if_not_exists('leave_types', 'max_encashment_days', 'INT NULL AFTER allow_encashment');
CALL add_column_if_not_exists('leave_types', 'applicable_gender', "ENUM('all', 'male', 'female') DEFAULT 'all' AFTER max_encashment_days");

-- 10.2 Add payroll-related columns to existing leave_balances table
CALL add_column_if_not_exists('leave_balances', 'entitled_days', 'INT DEFAULT 0 AFTER days_allocated');
CALL add_column_if_not_exists('leave_balances', 'carried_forward', 'INT DEFAULT 0 AFTER entitled_days');
CALL add_column_if_not_exists('leave_balances', 'additional_days', 'INT DEFAULT 0 AFTER carried_forward');
CALL add_column_if_not_exists('leave_balances', 'encashed_days', 'INT DEFAULT 0 AFTER days_pending');
CALL add_column_if_not_exists('leave_balances', 'forfeited_days', 'INT DEFAULT 0 AFTER encashed_days');

-- 10.3 Add columns to existing leave_applications table
CALL add_column_if_not_exists('leave_applications', 'is_half_day', 'TINYINT(1) DEFAULT 0 AFTER days_requested');
CALL add_column_if_not_exists('leave_applications', 'half_day_period', "ENUM('morning', 'afternoon') NULL AFTER is_half_day");
CALL add_column_if_not_exists('leave_applications', 'attachment_path', 'VARCHAR(255) NULL AFTER reason');
CALL add_column_if_not_exists('leave_applications', 'workflow_ticket_id', 'INT NULL AFTER approved_at');

-- 10.4 Public holidays
CREATE TABLE IF NOT EXISTS public_holidays (
    id INT PRIMARY KEY AUTO_INCREMENT,
    country_id INT NOT NULL,
    region_id INT NULL,
    holiday_date DATE NOT NULL,
    holiday_name VARCHAR(100) NOT NULL,
    is_recurring TINYINT(1) DEFAULT 0,
    recurring_month TINYINT,
    recurring_day TINYINT,
    year INT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (country_id) REFERENCES countries(id),
    FOREIGN KEY (region_id) REFERENCES payroll_regions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- SECTION 11: Time & Attendance
-- =====================================================

-- 11.1 Attendance records
CREATE TABLE IF NOT EXISTS staff_attendance (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    staff_id BIGINT UNSIGNED NOT NULL,
    attendance_date DATE NOT NULL,

    clock_in TIME,
    clock_out TIME,

    status ENUM('present', 'absent', 'half_day', 'leave', 'holiday', 'weekend') DEFAULT 'present',
    late_minutes INT DEFAULT 0,
    early_leave_minutes INT DEFAULT 0,
    overtime_minutes INT DEFAULT 0,

    leave_request_id INT NULL,

    remarks TEXT,
    recorded_by BIGINT UNSIGNED,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
    FOREIGN KEY (leave_request_id) REFERENCES leave_requests(id),
    UNIQUE KEY unique_attendance (staff_id, attendance_date),
    INDEX idx_attendance_date (attendance_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- SECTION 12: Statutory Reporting
-- =====================================================

-- 12.1 Report templates
CREATE TABLE IF NOT EXISTS statutory_report_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    country_id INT NOT NULL,
    fund_id INT NULL,

    report_code VARCHAR(50) NOT NULL,
    report_name VARCHAR(100) NOT NULL,
    report_description TEXT,

    frequency ENUM('monthly', 'quarterly', 'annual') NOT NULL,
    due_day_of_period INT,

    template_config JSON NOT NULL,
    output_format ENUM('pdf', 'excel', 'csv', 'xml', 'api') DEFAULT 'excel',

    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (country_id) REFERENCES countries(id),
    FOREIGN KEY (fund_id) REFERENCES statutory_funds(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12.2 Generated reports
CREATE TABLE IF NOT EXISTS statutory_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    template_id INT NOT NULL,
    pay_period_id INT NULL,

    report_period_start DATE NOT NULL,
    report_period_end DATE NOT NULL,

    status ENUM('draft', 'generated', 'submitted', 'accepted', 'rejected') DEFAULT 'draft',

    file_path VARCHAR(255),
    submission_reference VARCHAR(100),
    submission_date DATE,

    generated_by BIGINT UNSIGNED,
    generated_at DATETIME,
    submitted_by BIGINT UNSIGNED,
    submitted_at DATETIME,

    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (template_id) REFERENCES statutory_report_templates(id),
    FOREIGN KEY (pay_period_id) REFERENCES pay_periods(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- SECTION 13: School Bank Accounts (for payroll)
-- =====================================================

CREATE TABLE IF NOT EXISTS school_bank_accounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    account_name VARCHAR(200) NOT NULL,
    bank_name VARCHAR(100) NOT NULL,
    bank_branch VARCHAR(100),
    account_number VARCHAR(50) NOT NULL,
    account_type ENUM('current', 'savings') DEFAULT 'current',
    swift_code VARCHAR(20),
    bank_code VARCHAR(20),

    -- Purpose flags
    is_payroll_account TINYINT(1) DEFAULT 0,
    is_fees_account TINYINT(1) DEFAULT 0,
    is_expenses_account TINYINT(1) DEFAULT 0,

    -- For reconciliation
    opening_balance DECIMAL(18,2) DEFAULT 0,
    current_balance DECIMAL(18,2) DEFAULT 0,

    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- SECTION 14: Staff Import Tracking
-- =====================================================

CREATE TABLE IF NOT EXISTS staff_imports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255),
    total_rows INT DEFAULT 0,
    successful_rows INT DEFAULT 0,
    failed_rows INT DEFAULT 0,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    error_log JSON,
    imported_by BIGINT UNSIGNED,
    imported_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- Cleanup helper procedures
DROP PROCEDURE IF EXISTS add_column_if_not_exists;
DROP PROCEDURE IF EXISTS add_fk_if_not_exists;

SELECT 'Phase 3 HR & Payroll tables created successfully!' AS status;
