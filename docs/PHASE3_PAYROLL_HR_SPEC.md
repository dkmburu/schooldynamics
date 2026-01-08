# Phase 3: Payroll & HR Module Specification

## Overview

A fully parameterized, multi-country payroll and HR system designed for schools. The system avoids hard-coded country-specific logic, instead using configurable rules stored in the database.

---

## Key Decisions

| Decision | Choice | Notes |
|----------|--------|-------|
| Initial Country | Kenya | Start with KEN, expand later |
| Contractors | Via Expenses Module | Suppliers with 5% WHT; Casuals supported in payroll |
| Mobile Money | Integration-ready | Settings module handles integrations |
| Payslip Delivery | Email + Print | No SMS |
| Currency | Per-country | Linked via school_profile.country_id |
| Time & Attendance | Enhance existing | If available |

---

## 1. Navigation Structure

```
HR & Payroll
├── Dashboard                    # Overview, pending tasks, alerts
├── Staff Management
│   ├── Staff Directory   Y       # All employees with filters
│   ├── Add New Staff    I        # Onboarding wizard
│   ├── Departments     N         # Department/section management
│   └── Designations    N         # Job titles and grades
├── Payroll
│   ├── Salary Structures Y        # Pay grades, scales, components
│   ├── Staff Salaries           # Individual salary assignments
│   ├── Process Payroll          # Monthly/period processing
│   ├── Payroll History          # Past payrolls
│   └── Payslips                 # View/print/email payslips
├── Deductions & Benefits
│   ├── Deduction Types          # Statutory & voluntary deductions
│   ├── Benefit Types            # Allowances, bonuses
│   ├── Staff Deductions         # Individual assignments
│   └── Loans & Advances         # Salary advances, loans
├── Leave Management
│   ├── Leave Types              # Annual, sick, maternity, etc.
│   ├── Leave Requests           # Apply/approve leave
│   ├── Leave Balances           # Staff leave balances
│   └── Holiday Calendar         # Public holidays
├── Reports
│   ├── Payroll Summary          # Period summaries
│   ├── Statutory Reports        # Tax returns, social security
│   ├── Bank Files               # Payment file generation
│   ├── Staff Reports            # Headcount, turnover
│   └── Custom Reports           # Report builder
└── Settings
    ├── Country/Region Config    # Tax rules, statutory setup
    ├── Pay Periods              # Monthly, bi-weekly, etc.
    ├── Approval Workflows       # Payroll approval chain
    └── Email Templates          # Payslip delivery
```

---

## 2. Core Design Principles

### 2.1 Parameterized Architecture

**NO hard-coded country logic.** Everything is configurable:

```
┌─────────────────────────────────────────────────────────────────┐
│                     CONFIGURATION LAYER                         │
├─────────────────────────────────────────────────────────────────┤
│  countries          │ Base country setup, currency, formats     │
│  tax_brackets       │ Progressive tax tables (versioned)        │
│  statutory_funds    │ Social security, pension, insurance       │
│  deduction_rules    │ Calculation formulas (JSON-based)         │
│  statutory_reports  │ Report templates per country              │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                     CALCULATION ENGINE                          │
├─────────────────────────────────────────────────────────────────┤
│  1. Load employee's country config                              │
│  2. Calculate gross (base + allowances)                         │
│  3. Apply deduction rules in sequence                           │
│  4. Calculate taxes using tax_brackets                          │
│  5. Apply statutory fund rules                                  │
│  6. Calculate net pay                                           │
└─────────────────────────────────────────────────────────────────┘
```

### 2.2 Formula Engine

Deductions and calculations use a simple expression language stored in DB:

```json
{
  "formula": "gross * rate / 100",
  "variables": {
    "gross": "calculated_gross",
    "rate": "5.0"
  },
  "conditions": [
    {"if": "gross > cap", "then": "cap * rate / 100"}
  ],
  "cap": 18000,
  "min": 0,
  "max": null
}
```

Supported operations:
- Basic math: `+`, `-`, `*`, `/`, `%`
- Comparisons: `>`, `<`, `>=`, `<=`, `==`
- Functions: `MIN()`, `MAX()`, `ROUND()`, `FLOOR()`, `CEIL()`
- Variables: `gross`, `basic`, `taxable_income`, `employee_age`, etc.

---

## 3. Database Schema

### 3.1 Country & Region Configuration

**Note:** The system already has a `countries` table. We will enhance it with payroll-specific fields.

```sql
-- Enhance existing countries table with payroll fields
ALTER TABLE countries ADD COLUMN currency_code VARCHAR(3) AFTER country_name;
ALTER TABLE countries ADD COLUMN currency_symbol VARCHAR(10) AFTER currency_code;
ALTER TABLE countries ADD COLUMN currency_name VARCHAR(50) AFTER currency_symbol;
ALTER TABLE countries ADD COLUMN date_format VARCHAR(20) DEFAULT 'Y-m-d' AFTER currency_name;
ALTER TABLE countries ADD COLUMN financial_year_start TINYINT DEFAULT 1 AFTER date_format;
ALTER TABLE countries ADD COLUMN decimal_places TINYINT DEFAULT 2 AFTER financial_year_start;
ALTER TABLE countries ADD COLUMN thousand_separator VARCHAR(1) DEFAULT ',' AFTER decimal_places;
ALTER TABLE countries ADD COLUMN decimal_separator VARCHAR(1) DEFAULT '.' AFTER thousand_separator;
ALTER TABLE countries ADD COLUMN phone_code VARCHAR(10) AFTER decimal_separator;

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

-- Add country_id to school_profile
ALTER TABLE school_profile ADD COLUMN country_id INT AFTER currency;
ALTER TABLE school_profile ADD CONSTRAINT fk_school_country FOREIGN KEY (country_id) REFERENCES countries(id);

-- Regions/States within countries (for state-based taxes like Nigeria)
CREATE TABLE payroll_regions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    country_id INT NOT NULL,
    region_code VARCHAR(10) NOT NULL,
    region_name VARCHAR(100) NOT NULL,
    has_separate_tax_rules TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (country_id) REFERENCES countries(id)
);
```

### 3.2 Tax Configuration

```sql
-- Tax bracket tables (versioned for historical accuracy)
CREATE TABLE tax_brackets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    country_id INT NOT NULL,
    region_id INT NULL,                         -- NULL = national, else state-specific
    name VARCHAR(100) NOT NULL,                 -- "PAYE 2024", "PAYE 2025"
    effective_from DATE NOT NULL,
    effective_to DATE NULL,                     -- NULL = current
    is_current TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (country_id) REFERENCES countries(id),
    FOREIGN KEY (region_id) REFERENCES payroll_regions(id)
);

-- Individual tax bands within a bracket
CREATE TABLE tax_bands (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bracket_id INT NOT NULL,
    band_order TINYINT NOT NULL,                -- 1, 2, 3... for ordering
    min_amount DECIMAL(15,2) NOT NULL,          -- 0, 24001, 32333, etc.
    max_amount DECIMAL(15,2) NULL,              -- NULL = no upper limit
    rate DECIMAL(5,2) NOT NULL,                 -- 10.00, 25.00, 30.00
    fixed_amount DECIMAL(15,2) DEFAULT 0,       -- Fixed tax for this band
    description VARCHAR(100),                   -- "First 24,000 @ 10%"
    FOREIGN KEY (bracket_id) REFERENCES tax_brackets(id)
);

-- Tax reliefs and exemptions
CREATE TABLE tax_reliefs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    country_id INT NOT NULL,
    relief_code VARCHAR(50) NOT NULL,           -- PERSONAL_RELIEF, INSURANCE_RELIEF
    relief_name VARCHAR(100) NOT NULL,
    relief_type ENUM('fixed', 'percentage', 'formula') NOT NULL,
    amount DECIMAL(15,2) NULL,                  -- For fixed type
    percentage DECIMAL(5,2) NULL,               -- For percentage type
    formula_config JSON NULL,                   -- For formula type
    max_amount DECIMAL(15,2) NULL,              -- Cap on relief
    effective_from DATE NOT NULL,
    effective_to DATE NULL,
    requires_proof TINYINT(1) DEFAULT 0,        -- Needs documentation
    applies_to ENUM('all', 'resident', 'non_resident') DEFAULT 'all',
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (country_id) REFERENCES countries(id)
);
```

### 3.3 Statutory Funds (Social Security, Pension, Insurance)

```sql
-- Statutory contribution funds
CREATE TABLE statutory_funds (
    id INT PRIMARY KEY AUTO_INCREMENT,
    country_id INT NOT NULL,
    fund_code VARCHAR(50) NOT NULL,             -- NSSF, NHIF, HOUSING_LEVY
    fund_name VARCHAR(100) NOT NULL,            -- "National Social Security Fund"
    fund_type ENUM('pension', 'health', 'insurance', 'levy', 'other') NOT NULL,
    is_mandatory TINYINT(1) DEFAULT 1,
    employer_number_label VARCHAR(50),          -- "NSSF Employer Code"
    employee_number_label VARCHAR(50),          -- "NSSF Member Number"
    website_url VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (country_id) REFERENCES countries(id)
);

-- Contribution rates for statutory funds (versioned)
CREATE TABLE statutory_fund_rates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fund_id INT NOT NULL,
    effective_from DATE NOT NULL,
    effective_to DATE NULL,

    -- Employee contribution
    employee_rate_type ENUM('fixed', 'percentage', 'tiered', 'formula') NOT NULL,
    employee_rate DECIMAL(10,4) NULL,           -- For percentage: 6.0000
    employee_fixed DECIMAL(15,2) NULL,          -- For fixed amount
    employee_formula JSON NULL,                 -- For complex calculations
    employee_min DECIMAL(15,2) NULL,
    employee_max DECIMAL(15,2) NULL,            -- Contribution cap

    -- Employer contribution
    employer_rate_type ENUM('fixed', 'percentage', 'tiered', 'formula') NOT NULL,
    employer_rate DECIMAL(10,4) NULL,
    employer_fixed DECIMAL(15,2) NULL,
    employer_formula JSON NULL,
    employer_min DECIMAL(15,2) NULL,
    employer_max DECIMAL(15,2) NULL,

    -- Calculation basis
    calculation_basis ENUM('gross', 'basic', 'pensionable', 'taxable', 'custom') DEFAULT 'gross',
    custom_basis_formula JSON NULL,             -- If calculation_basis = 'custom'

    is_current TINYINT(1) DEFAULT 0,
    notes TEXT,
    FOREIGN KEY (fund_id) REFERENCES statutory_funds(id)
);

-- Tiered rates (for funds like NHIF Kenya with income bands)
CREATE TABLE statutory_fund_tiers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fund_rate_id INT NOT NULL,
    tier_order TINYINT NOT NULL,
    min_income DECIMAL(15,2) NOT NULL,
    max_income DECIMAL(15,2) NULL,
    employee_amount DECIMAL(15,2) NOT NULL,     -- Fixed amount for this tier
    employer_amount DECIMAL(15,2) NULL,
    FOREIGN KEY (fund_rate_id) REFERENCES statutory_fund_rates(id)
);
```

### 3.4 Salary Structure

```sql
-- Pay components (earnings and deductions)
CREATE TABLE pay_components (
    id INT PRIMARY KEY AUTO_INCREMENT,
    country_id INT NULL,                        -- NULL = global component
    component_code VARCHAR(50) NOT NULL,        -- BASIC, HOUSING, TRANSPORT
    component_name VARCHAR(100) NOT NULL,
    component_type ENUM('earning', 'deduction', 'employer_contribution') NOT NULL,
    category ENUM('basic', 'allowance', 'bonus', 'overtime', 'statutory', 'voluntary', 'loan', 'other') NOT NULL,

    -- Calculation settings
    calculation_type ENUM('fixed', 'percentage', 'formula', 'hourly', 'daily') NOT NULL,
    percentage_of VARCHAR(50) NULL,             -- 'basic', 'gross', component_code
    formula_config JSON NULL,

    -- Tax treatment
    is_taxable TINYINT(1) DEFAULT 1,
    tax_exemption_limit DECIMAL(15,2) NULL,     -- Amount exempt from tax

    -- For statutory components
    statutory_fund_id INT NULL,

    -- Display settings
    display_order INT DEFAULT 0,
    show_on_payslip TINYINT(1) DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,

    FOREIGN KEY (country_id) REFERENCES countries(id),
    FOREIGN KEY (statutory_fund_id) REFERENCES statutory_funds(id)
);

-- Salary grades/scales
CREATE TABLE salary_grades (
    id INT PRIMARY KEY AUTO_INCREMENT,
    grade_code VARCHAR(20) NOT NULL,            -- G1, G2, T1, T2, ADMIN-1
    grade_name VARCHAR(100) NOT NULL,           -- "Grade 1 - Entry Level"
    min_salary DECIMAL(15,2) NOT NULL,
    max_salary DECIMAL(15,2) NOT NULL,
    default_salary DECIMAL(15,2) NULL,
    annual_increment DECIMAL(15,2) NULL,        -- Standard annual raise
    increment_percentage DECIMAL(5,2) NULL,     -- Or percentage raise
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Salary structures (templates)
CREATE TABLE salary_structures (
    id INT PRIMARY KEY AUTO_INCREMENT,
    structure_code VARCHAR(50) NOT NULL,
    structure_name VARCHAR(100) NOT NULL,       -- "Teaching Staff Structure"
    description TEXT,
    is_default TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Components within a salary structure
CREATE TABLE salary_structure_components (
    id INT PRIMARY KEY AUTO_INCREMENT,
    structure_id INT NOT NULL,
    component_id INT NOT NULL,
    is_mandatory TINYINT(1) DEFAULT 0,
    default_value DECIMAL(15,2) NULL,           -- Default amount
    default_percentage DECIMAL(5,2) NULL,       -- Or default %
    calculation_order INT DEFAULT 0,            -- Order of calculation
    FOREIGN KEY (structure_id) REFERENCES salary_structures(id),
    FOREIGN KEY (component_id) REFERENCES pay_components(id)
);
```

### 3.5 Employee Records

```sql
-- Staff/Employee master
CREATE TABLE staff (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    employee_number VARCHAR(50) NOT NULL,       -- School's employee ID
    user_id BIGINT UNSIGNED NULL,               -- Link to users table if applicable

    -- Personal Information
    title VARCHAR(10),                          -- Mr, Mrs, Ms, Dr
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
    employment_type ENUM('permanent', 'contract', 'part_time', 'casual', 'intern') NOT NULL,
    employment_status ENUM('active', 'on_leave', 'suspended', 'terminated', 'retired') DEFAULT 'active',
    date_joined DATE NOT NULL,
    date_confirmed DATE,                        -- Confirmation after probation
    date_terminated DATE,
    termination_reason TEXT,
    probation_period_months TINYINT DEFAULT 3,
    notice_period_days INT DEFAULT 30,

    -- Payroll Settings
    country_id INT NOT NULL,                    -- Which country's rules apply
    region_id INT NULL,                         -- For state-specific taxes
    salary_structure_id INT,
    salary_grade_id INT,
    pay_frequency ENUM('monthly', 'bi_weekly', 'weekly', 'daily') DEFAULT 'monthly',
    payment_method ENUM('bank', 'cash', 'mobile_money', 'cheque') DEFAULT 'bank',

    -- Casual Worker Settings (for daily-wage workers without formal salary)
    is_casual TINYINT(1) DEFAULT 0,             -- Flag for casual workers
    daily_rate DECIMAL(15,2) NULL,              -- Daily wage for casuals
    hourly_rate DECIMAL(15,2) NULL,             -- Hourly rate if applicable

    -- Bank Details
    bank_name VARCHAR(100),
    bank_branch VARCHAR(100),
    bank_account_number VARCHAR(50),
    bank_account_name VARCHAR(200),
    bank_swift_code VARCHAR(20),

    -- Mobile Money (for markets like Kenya, Uganda)
    mobile_money_provider VARCHAR(50),          -- M-Pesa, MTN MoMo
    mobile_money_number VARCHAR(50),

    -- Tax Information
    tax_pin VARCHAR(50),                        -- KRA PIN, TIN, etc.
    is_tax_resident TINYINT(1) DEFAULT 1,
    tax_exemption_certificate VARCHAR(100),

    -- Photo and Documents
    photo_path VARCHAR(255),

    created_by BIGINT UNSIGNED,
    updated_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (country_id) REFERENCES countries(id),
    FOREIGN KEY (region_id) REFERENCES payroll_regions(id),
    FOREIGN KEY (salary_structure_id) REFERENCES salary_structures(id),
    FOREIGN KEY (salary_grade_id) REFERENCES salary_grades(id)
);

-- Casual worker attendance/work log (for calculating pay)
CREATE TABLE casual_work_logs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    staff_id BIGINT UNSIGNED NOT NULL,
    work_date DATE NOT NULL,
    hours_worked DECIMAL(5,2) DEFAULT 8,        -- Hours worked that day
    rate_applied DECIMAL(15,2) NOT NULL,        -- Daily or hourly rate used
    rate_type ENUM('daily', 'hourly') DEFAULT 'daily',
    gross_amount DECIMAL(15,2) NOT NULL,        -- Calculated pay
    description VARCHAR(255),                   -- Work description
    verified_by BIGINT UNSIGNED,
    verified_at DATETIME,
    payroll_run_id INT NULL,                    -- Link to payroll when paid
    status ENUM('pending', 'verified', 'paid') DEFAULT 'pending',
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id),
    UNIQUE KEY unique_staff_date (staff_id, work_date)
);

-- Staff statutory fund registrations
CREATE TABLE staff_statutory_registrations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    staff_id BIGINT UNSIGNED NOT NULL,
    fund_id INT NOT NULL,
    registration_number VARCHAR(100),           -- NSSF number, NHIF number
    registration_date DATE,
    is_active TINYINT(1) DEFAULT 1,
    exemption_certificate VARCHAR(100),         -- If exempt
    exemption_reason TEXT,
    exemption_expiry DATE,
    FOREIGN KEY (staff_id) REFERENCES staff(id),
    FOREIGN KEY (fund_id) REFERENCES statutory_funds(id),
    UNIQUE KEY unique_staff_fund (staff_id, fund_id)
);

-- Staff salary details
CREATE TABLE staff_salaries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    staff_id BIGINT UNSIGNED NOT NULL,
    effective_from DATE NOT NULL,
    effective_to DATE NULL,                     -- NULL = current

    basic_salary DECIMAL(15,2) NOT NULL,
    currency_code VARCHAR(3) NOT NULL,

    -- Salary revision info
    revision_reason ENUM('new_hire', 'promotion', 'annual_increment', 'adjustment', 'demotion') DEFAULT 'new_hire',
    revision_notes TEXT,

    approved_by BIGINT UNSIGNED,
    approved_at DATETIME,

    is_current TINYINT(1) DEFAULT 0,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (staff_id) REFERENCES staff(id)
);

-- Staff salary components (individual overrides)
CREATE TABLE staff_salary_components (
    id INT PRIMARY KEY AUTO_INCREMENT,
    staff_salary_id INT NOT NULL,
    component_id INT NOT NULL,

    amount DECIMAL(15,2) NULL,                  -- Fixed amount override
    percentage DECIMAL(5,2) NULL,               -- Percentage override
    is_active TINYINT(1) DEFAULT 1,

    FOREIGN KEY (staff_salary_id) REFERENCES staff_salaries(id),
    FOREIGN KEY (component_id) REFERENCES pay_components(id)
);
```

### 3.6 Payroll Processing

```sql
-- Pay periods
CREATE TABLE pay_periods (
    id INT PRIMARY KEY AUTO_INCREMENT,
    period_name VARCHAR(100) NOT NULL,          -- "January 2026"
    period_type ENUM('monthly', 'bi_weekly', 'weekly') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    payment_date DATE NOT NULL,
    status ENUM('draft', 'processing', 'pending_approval', 'approved', 'paid', 'closed') DEFAULT 'draft',

    -- Processing info
    processed_by BIGINT UNSIGNED,
    processed_at DATETIME,
    approved_by BIGINT UNSIGNED,
    approved_at DATETIME,
    paid_by BIGINT UNSIGNED,
    paid_at DATETIME,

    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Payroll runs
CREATE TABLE payroll_runs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pay_period_id INT NOT NULL,
    run_number TINYINT DEFAULT 1,               -- For multiple runs in a period
    run_type ENUM('regular', 'supplementary', 'bonus', 'final') DEFAULT 'regular',

    -- Summary
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

    FOREIGN KEY (pay_period_id) REFERENCES pay_periods(id)
);

-- Individual payslips
CREATE TABLE payslips (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    payroll_run_id INT NOT NULL,
    staff_id BIGINT UNSIGNED NOT NULL,

    -- Employee snapshot (for historical accuracy)
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
    total_cost_to_company DECIMAL(15,2),        -- Net + employer contributions

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

    -- For YTD calculations
    ytd_gross DECIMAL(18,2),
    ytd_tax DECIMAL(18,2),
    ytd_net DECIMAL(18,2),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (payroll_run_id) REFERENCES payroll_runs(id),
    FOREIGN KEY (staff_id) REFERENCES staff(id)
);

-- Payslip line items (earnings and deductions breakdown)
CREATE TABLE payslip_items (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    payslip_id BIGINT UNSIGNED NOT NULL,
    component_id INT NOT NULL,

    component_code VARCHAR(50),                 -- Snapshot
    component_name VARCHAR(100),                -- Snapshot
    component_type ENUM('earning', 'deduction', 'employer_contribution'),

    amount DECIMAL(15,2) NOT NULL,
    is_taxable TINYINT(1) DEFAULT 1,

    -- For percentage-based items
    base_amount DECIMAL(15,2) NULL,
    rate DECIMAL(10,4) NULL,

    calculation_notes TEXT,                     -- How it was calculated

    FOREIGN KEY (payslip_id) REFERENCES payslips(id),
    FOREIGN KEY (component_id) REFERENCES pay_components(id)
);
```

### 3.7 Loans and Advances

```sql
-- Loan types
CREATE TABLE loan_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    loan_code VARCHAR(50) NOT NULL,
    loan_name VARCHAR(100) NOT NULL,            -- "Salary Advance", "Staff Loan"
    max_amount DECIMAL(15,2),
    max_repayment_months INT,
    interest_rate DECIMAL(5,2) DEFAULT 0,       -- Annual interest rate
    interest_type ENUM('simple', 'compound', 'flat') DEFAULT 'simple',
    requires_approval TINYINT(1) DEFAULT 1,
    max_concurrent_loans TINYINT DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1
);

-- Staff loans
CREATE TABLE staff_loans (
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

    notes TEXT,

    FOREIGN KEY (staff_id) REFERENCES staff(id),
    FOREIGN KEY (loan_type_id) REFERENCES loan_types(id)
);

-- Loan repayment schedule
CREATE TABLE loan_repayments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    loan_id INT NOT NULL,
    installment_number INT NOT NULL,
    due_date DATE NOT NULL,
    principal_amount DECIMAL(15,2),
    interest_amount DECIMAL(15,2),
    total_amount DECIMAL(15,2) NOT NULL,

    paid_amount DECIMAL(15,2) DEFAULT 0,
    paid_date DATE,
    payslip_id BIGINT UNSIGNED NULL,            -- Link to payslip if auto-deducted

    status ENUM('pending', 'paid', 'partial', 'overdue') DEFAULT 'pending',

    FOREIGN KEY (loan_id) REFERENCES staff_loans(id),
    FOREIGN KEY (payslip_id) REFERENCES payslips(id)
);
```

### 3.8 Leave Management

```sql
-- Leave types
CREATE TABLE leave_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    country_id INT NULL,                        -- NULL = global
    leave_code VARCHAR(50) NOT NULL,
    leave_name VARCHAR(100) NOT NULL,           -- "Annual Leave", "Sick Leave"

    -- Entitlement
    default_days_per_year DECIMAL(5,2),
    max_days_per_year DECIMAL(5,2),

    -- Rules
    is_paid TINYINT(1) DEFAULT 1,
    pay_percentage DECIMAL(5,2) DEFAULT 100,    -- % of salary paid
    requires_approval TINYINT(1) DEFAULT 1,
    requires_documentation TINYINT(1) DEFAULT 0,
    min_days_notice INT DEFAULT 0,              -- Days advance notice required

    -- Carry forward
    allow_carry_forward TINYINT(1) DEFAULT 0,
    max_carry_forward_days DECIMAL(5,2),
    carry_forward_expiry_months INT,            -- Months until carried leave expires

    -- Encashment
    allow_encashment TINYINT(1) DEFAULT 0,
    max_encashment_days DECIMAL(5,2),

    -- Gender specific (maternity/paternity)
    applicable_gender ENUM('all', 'male', 'female') DEFAULT 'all',

    is_active TINYINT(1) DEFAULT 1,

    FOREIGN KEY (country_id) REFERENCES countries(id)
);

-- Staff leave balances
CREATE TABLE staff_leave_balances (
    id INT PRIMARY KEY AUTO_INCREMENT,
    staff_id BIGINT UNSIGNED NOT NULL,
    leave_type_id INT NOT NULL,
    year INT NOT NULL,

    entitled_days DECIMAL(5,2) DEFAULT 0,
    carried_forward DECIMAL(5,2) DEFAULT 0,
    additional_days DECIMAL(5,2) DEFAULT 0,     -- Bonus leave granted

    used_days DECIMAL(5,2) DEFAULT 0,
    pending_days DECIMAL(5,2) DEFAULT 0,        -- Requested but not yet taken

    encashed_days DECIMAL(5,2) DEFAULT 0,
    forfeited_days DECIMAL(5,2) DEFAULT 0,

    balance DECIMAL(5,2) GENERATED ALWAYS AS (
        entitled_days + carried_forward + additional_days - used_days - pending_days - encashed_days - forfeited_days
    ) STORED,

    FOREIGN KEY (staff_id) REFERENCES staff(id),
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(id),
    UNIQUE KEY unique_balance (staff_id, leave_type_id, year)
);

-- Leave requests
CREATE TABLE leave_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    staff_id BIGINT UNSIGNED NOT NULL,
    leave_type_id INT NOT NULL,

    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    days_requested DECIMAL(5,2) NOT NULL,

    is_half_day TINYINT(1) DEFAULT 0,
    half_day_period ENUM('morning', 'afternoon'),

    reason TEXT,
    attachment_path VARCHAR(255),               -- Medical certificate, etc.

    status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',

    approved_by BIGINT UNSIGNED,
    approved_at DATETIME,
    rejection_reason TEXT,

    -- For workflow integration
    workflow_ticket_id INT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (staff_id) REFERENCES staff(id),
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(id)
);

-- Public holidays
CREATE TABLE public_holidays (
    id INT PRIMARY KEY AUTO_INCREMENT,
    country_id INT NOT NULL,
    region_id INT NULL,                         -- NULL = national holiday
    holiday_date DATE NOT NULL,
    holiday_name VARCHAR(100) NOT NULL,
    is_recurring TINYINT(1) DEFAULT 0,          -- Same date every year
    recurring_month TINYINT,
    recurring_day TINYINT,
    year INT,                                   -- If not recurring
    is_active TINYINT(1) DEFAULT 1,

    FOREIGN KEY (country_id) REFERENCES countries(id),
    FOREIGN KEY (region_id) REFERENCES payroll_regions(id)
);
```

### 3.9 Statutory Reporting

```sql
-- Report templates for statutory returns
CREATE TABLE statutory_report_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    country_id INT NOT NULL,
    fund_id INT NULL,                           -- If fund-specific

    report_code VARCHAR(50) NOT NULL,           -- P9_KENYA, PAYE_RETURN
    report_name VARCHAR(100) NOT NULL,
    report_description TEXT,

    frequency ENUM('monthly', 'quarterly', 'annual') NOT NULL,
    due_day_of_period INT,                      -- Day of month/quarter due

    -- Template definition
    template_config JSON NOT NULL,              -- Field mappings, calculations
    output_format ENUM('pdf', 'excel', 'csv', 'xml', 'api') DEFAULT 'excel',

    is_active TINYINT(1) DEFAULT 1,

    FOREIGN KEY (country_id) REFERENCES countries(id),
    FOREIGN KEY (fund_id) REFERENCES statutory_funds(id)
);

-- Generated statutory reports
CREATE TABLE statutory_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    template_id INT NOT NULL,
    pay_period_id INT NULL,                     -- If period-specific

    report_period_start DATE NOT NULL,
    report_period_end DATE NOT NULL,

    status ENUM('draft', 'generated', 'submitted', 'accepted', 'rejected') DEFAULT 'draft',

    file_path VARCHAR(255),
    submission_reference VARCHAR(100),          -- Reference from authority
    submission_date DATE,

    generated_by BIGINT UNSIGNED,
    generated_at DATETIME,
    submitted_by BIGINT UNSIGNED,
    submitted_at DATETIME,

    notes TEXT,

    FOREIGN KEY (template_id) REFERENCES statutory_report_templates(id),
    FOREIGN KEY (pay_period_id) REFERENCES pay_periods(id)
);
```

---

## 4. Sample Country Configurations

### 4.1 Kenya Configuration

```sql
-- NOTE: Kenya already exists in countries table with id=1
-- Get Kenya's country_id
SET @kenya_id = (SELECT id FROM countries WHERE country_code = 'KE');

-- Kenya PAYE Tax Brackets (2024)
INSERT INTO tax_brackets (country_id, name, effective_from, is_current)
VALUES (@kenya_id, 'PAYE 2024', '2024-01-01', 1);

SET @kenya_tax_bracket = LAST_INSERT_ID();

INSERT INTO tax_bands (bracket_id, band_order, min_amount, max_amount, rate, description) VALUES
(@kenya_tax_bracket, 1, 0, 24000, 10.00, 'First 24,000'),
(@kenya_tax_bracket, 2, 24001, 32333, 25.00, '24,001 - 32,333'),
(@kenya_tax_bracket, 3, 32334, 500000, 30.00, '32,334 - 500,000'),
(@kenya_tax_bracket, 4, 500001, 800000, 32.50, '500,001 - 800,000'),
(@kenya_tax_bracket, 5, 800001, NULL, 35.00, 'Above 800,000');

-- Kenya Personal Relief
INSERT INTO tax_reliefs (country_id, relief_code, relief_name, relief_type, amount, effective_from)
VALUES (@kenya_id, 'PERSONAL_RELIEF', 'Monthly Personal Relief', 'fixed', 2400.00, '2024-01-01');

-- Kenya NSSF (Tier I & II)
INSERT INTO statutory_funds (country_id, fund_code, fund_name, fund_type, employer_number_label, employee_number_label)
VALUES (@kenya_id, 'NSSF', 'National Social Security Fund', 'pension', 'NSSF Employer Code', 'NSSF Member Number');

SET @nssf_id = LAST_INSERT_ID();

INSERT INTO statutory_fund_rates (fund_id, effective_from, employee_rate_type, employee_rate, employee_max,
    employer_rate_type, employer_rate, employer_max, calculation_basis, is_current)
VALUES (@nssf_id, '2024-02-01', 'percentage', 6.0000, 1080.00, 'percentage', 6.0000, 1080.00, 'pensionable', 1);

-- Kenya NHIF (Tiered) - Note: Being replaced by SHA/SHIF but still in use
INSERT INTO statutory_funds (country_id, fund_code, fund_name, fund_type, employer_number_label, employee_number_label)
VALUES (@kenya_id, 'NHIF', 'National Hospital Insurance Fund', 'health', 'NHIF Employer Code', 'NHIF Member Number');

SET @nhif_id = LAST_INSERT_ID();

INSERT INTO statutory_fund_rates (fund_id, effective_from, employee_rate_type, employer_rate_type, is_current)
VALUES (@nhif_id, '2024-01-01', 'tiered', 'fixed', 1);

SET @nhif_rate_id = LAST_INSERT_ID();

INSERT INTO statutory_fund_tiers (fund_rate_id, tier_order, min_income, max_income, employee_amount) VALUES
(@nhif_rate_id, 1, 0, 5999, 150),
(@nhif_rate_id, 2, 6000, 7999, 300),
(@nhif_rate_id, 3, 8000, 11999, 400),
(@nhif_rate_id, 4, 12000, 14999, 500),
(@nhif_rate_id, 5, 15000, 19999, 600),
(@nhif_rate_id, 6, 20000, 24999, 750),
(@nhif_rate_id, 7, 25000, 29999, 850),
(@nhif_rate_id, 8, 30000, 34999, 900),
(@nhif_rate_id, 9, 35000, 39999, 950),
(@nhif_rate_id, 10, 40000, 44999, 1000),
(@nhif_rate_id, 11, 45000, 49999, 1100),
(@nhif_rate_id, 12, 50000, 59999, 1200),
(@nhif_rate_id, 13, 60000, 69999, 1300),
(@nhif_rate_id, 14, 70000, 79999, 1400),
(@nhif_rate_id, 15, 80000, 89999, 1500),
(@nhif_rate_id, 16, 90000, 99999, 1600),
(@nhif_rate_id, 17, 100000, NULL, 1700);

-- Kenya Housing Levy (Affordable Housing Levy)
INSERT INTO statutory_funds (country_id, fund_code, fund_name, fund_type)
VALUES (@kenya_id, 'HOUSING_LEVY', 'Affordable Housing Levy', 'levy');

SET @housing_id = LAST_INSERT_ID();

INSERT INTO statutory_fund_rates (fund_id, effective_from, employee_rate_type, employee_rate,
    employer_rate_type, employer_rate, calculation_basis, is_current)
VALUES (@housing_id, '2024-03-01', 'percentage', 1.5000, 'percentage', 1.5000, 'gross', 1);

-- Kenya NITA (National Industrial Training Authority) - Employer only
INSERT INTO statutory_funds (country_id, fund_code, fund_name, fund_type, is_mandatory)
VALUES (@kenya_id, 'NITA', 'National Industrial Training Authority', 'levy', 1);

SET @nita_id = LAST_INSERT_ID();

INSERT INTO statutory_fund_rates (fund_id, effective_from, employee_rate_type, employee_fixed,
    employer_rate_type, employer_fixed, is_current, notes)
VALUES (@nita_id, '2024-01-01', 'fixed', 0, 'fixed', 50.00, 1, 'KES 50 per employee per month');
```

### 4.2 Uganda Configuration (Future Expansion Example)

```sql
-- NOTE: Uganda already exists in countries table with id=2
-- Get Uganda's country_id
SET @uganda_id = (SELECT id FROM countries WHERE country_code = 'UG');

-- Uganda PAYE (2024)
INSERT INTO tax_brackets (country_id, name, effective_from, is_current)
VALUES (@uganda_id, 'PAYE 2024', '2024-01-01', 1);

SET @uganda_tax_bracket = LAST_INSERT_ID();

INSERT INTO tax_bands (bracket_id, band_order, min_amount, max_amount, rate, description) VALUES
(@uganda_tax_bracket, 1, 0, 235000, 0.00, 'Tax-free threshold'),
(@uganda_tax_bracket, 2, 235001, 335000, 10.00, '235,001 - 335,000'),
(@uganda_tax_bracket, 3, 335001, 410000, 20.00, '335,001 - 410,000'),
(@uganda_tax_bracket, 4, 410001, 10000000, 30.00, '410,001 - 10,000,000'),
(@uganda_tax_bracket, 5, 10000001, NULL, 40.00, 'Above 10,000,000');

-- Uganda NSSF
INSERT INTO statutory_funds (country_id, fund_code, fund_name, fund_type, employer_number_label, employee_number_label)
VALUES (@uganda_id, 'NSSF_UG', 'National Social Security Fund', 'pension', 'NSSF Employer Number', 'NSSF Member Number');

SET @nssf_ug_id = LAST_INSERT_ID();

INSERT INTO statutory_fund_rates (fund_id, effective_from, employee_rate_type, employee_rate,
    employer_rate_type, employer_rate, calculation_basis, is_current)
VALUES (@nssf_ug_id, '2024-01-01', 'percentage', 5.0000, 'percentage', 10.0000, 'gross', 1);
```

---

## 5. Payroll Calculation Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                    PAYROLL PROCESSING STEPS                     │
└─────────────────────────────────────────────────────────────────┘

Step 1: INITIALIZE
├── Create pay period if not exists
├── Create payroll run
└── Get list of active employees

Step 2: FOR EACH EMPLOYEE
│
├── 2.1 Load Configuration
│   ├── Get employee's country config
│   ├── Get current tax brackets
│   ├── Get statutory fund rates
│   └── Get salary structure & components
│
├── 2.2 Calculate Attendance
│   ├── Working days in period
│   ├── Days present/absent
│   ├── Leave days (paid/unpaid)
│   └── Overtime hours
│
├── 2.3 Calculate Gross Earnings
│   ├── Basic salary (pro-rated if needed)
│   ├── Fixed allowances
│   ├── Percentage-based allowances
│   ├── Overtime pay
│   ├── Bonuses
│   └── Reimbursements
│
├── 2.4 Calculate Pre-Tax Deductions
│   ├── Pension contributions (if tax-exempt)
│   └── Other tax-exempt deductions
│
├── 2.5 Calculate Taxable Income
│   ├── Gross - Tax-exempt items
│   └── Apply exemption limits
│
├── 2.6 Calculate Tax (PAYE)
│   ├── Apply tax brackets progressively
│   ├── Calculate gross tax
│   ├── Apply tax reliefs
│   └── Net tax payable
│
├── 2.7 Calculate Post-Tax Deductions
│   ├── Statutory (NHIF, etc.)
│   ├── Loan repayments
│   ├── Voluntary deductions
│   └── Other deductions
│
├── 2.8 Calculate Employer Contributions
│   ├── Employer NSSF
│   ├── Employer NHIF (if applicable)
│   └── Other employer costs
│
├── 2.9 Calculate Net Pay
│   └── Gross - All Deductions
│
└── 2.10 Save Payslip & Items

Step 3: FINALIZE
├── Calculate run totals
├── Update run status
└── Generate summary report
```

---

## 6. API Endpoints

```
# Staff Management
GET    /hr/api/staff                    # List all staff
POST   /hr/api/staff                    # Create new staff
GET    /hr/api/staff/:id                # Get staff details
POST   /hr/api/staff/:id                # Update staff
POST   /hr/api/staff/:id/salary         # Update salary
GET    /hr/api/staff/:id/salary-history # Salary history
GET    /hr/api/staff/:id/payslips       # Staff payslips

# Salary Structures
GET    /hr/api/salary-structures        # List structures
POST   /hr/api/salary-structures        # Create structure
GET    /hr/api/salary-grades            # List grades
POST   /hr/api/salary-grades            # Create grade

# Payroll Processing
GET    /hr/api/pay-periods              # List pay periods
POST   /hr/api/pay-periods              # Create pay period
GET    /hr/api/payroll-runs             # List runs
POST   /hr/api/payroll-runs             # Create run
POST   /hr/api/payroll-runs/:id/calculate  # Calculate payroll
POST   /hr/api/payroll-runs/:id/approve    # Approve payroll
POST   /hr/api/payroll-runs/:id/pay        # Mark as paid

# Payslips
GET    /hr/api/payslips/:id             # Get payslip
GET    /hr/api/payslips/:id/pdf         # Download PDF
POST   /hr/api/payslips/:id/email       # Email to employee

# Leave Management
GET    /hr/api/leave-types              # List leave types
GET    /hr/api/leave-requests           # List requests
POST   /hr/api/leave-requests           # Submit request
POST   /hr/api/leave-requests/:id/approve  # Approve
POST   /hr/api/leave-requests/:id/reject   # Reject

# Loans
GET    /hr/api/loan-types               # List loan types
GET    /hr/api/loans                    # List all loans
POST   /hr/api/loans                    # Apply for loan
POST   /hr/api/loans/:id/approve        # Approve loan

# Reports
GET    /hr/api/reports/payroll-summary  # Payroll summary
GET    /hr/api/reports/statutory/:code  # Generate statutory report
GET    /hr/api/reports/bank-file        # Generate bank file

# Configuration
GET    /hr/api/config/countries         # List countries
GET    /hr/api/config/tax-brackets      # Get tax brackets
GET    /hr/api/config/statutory-funds   # Get statutory funds
POST   /hr/api/config/statutory-funds   # Configure fund
```

---

## 7. Implementation Phases

### Phase 3.1: Foundation (Core Setup)
- [ ] Database migrations
- [ ] Country configuration system
- [ ] Staff management (CRUD)
- [ ] Department & designation management
- [ ] Basic navigation and views

### Phase 3.2: Salary Configuration
- [ ] Pay components management
- [ ] Salary structures
- [ ] Salary grades
- [ ] Staff salary assignment
- [ ] Salary revision history

### Phase 3.3: Payroll Processing
- [ ] Pay period management
- [ ] Payroll calculation engine
- [ ] Formula parser for deductions
- [ ] Tax calculation (progressive brackets)
- [ ] Statutory fund calculations
- [ ] Payslip generation
- [ ] Payroll approval workflow

### Phase 3.4: Deductions & Benefits
- [ ] Loan management
- [ ] Advance salary
- [ ] Voluntary deductions
- [ ] Benefits tracking

### Phase 3.5: Leave Management
- [ ] Leave types configuration
- [ ] Leave balance tracking
- [ ] Leave request/approval
- [ ] Holiday calendar
- [ ] Leave impact on payroll

### Phase 3.6: Reports & Compliance
- [ ] Payslip PDF generation
- [ ] Payroll summary reports
- [ ] Statutory report templates
- [ ] Bank file generation
- [ ] P9 form (Kenya)
- [ ] Other country-specific reports

### Phase 3.7: Integration
- [ ] Budget module integration (staff costs)
- [ ] Expense module integration (reimbursements)
- [ ] Workflow integration (approvals)
- [ ] Notification system (payslip emails)

---

## 8. Country Expansion Checklist

When adding a new country:

1. **Basic Setup**
   - [ ] Add country record with currency settings
   - [ ] Add regions if state-based taxes exist

2. **Tax Configuration**
   - [ ] Create tax bracket with bands
   - [ ] Add applicable tax reliefs
   - [ ] Document tax calculation rules

3. **Statutory Funds**
   - [ ] Identify mandatory contributions
   - [ ] Create fund records
   - [ ] Configure rates (percentage/tiered/formula)
   - [ ] Set employer vs employee splits

4. **Leave Entitlements**
   - [ ] Review statutory leave requirements
   - [ ] Create leave types with legal minimums
   - [ ] Configure maternity/paternity specifics

5. **Reporting**
   - [ ] Identify required statutory reports
   - [ ] Create report templates
   - [ ] Define output formats

6. **Testing**
   - [ ] Create sample employees
   - [ ] Run test payroll calculations
   - [ ] Verify statutory deductions
   - [ ] Generate test reports

---

## 9. Security Considerations

- Payroll data is highly sensitive - implement row-level security
- Audit logging for all payroll operations
- Salary information visible only to authorized users
- Payslip access restricted to employee + HR + Finance
- API endpoints protected by role-based permissions
- Bank details encrypted at rest
- Two-factor approval for payroll processing

---

## 10. Resolved Decisions

| Question | Decision |
|----------|----------|
| Multi-currency | No - staff paid in school's country currency only |
| Contractor payments | Via Expenses module (suppliers with 5% WHT) |
| Casual workers | Supported in Payroll with daily/hourly rates |
| Mobile money | Integration-ready via Settings module |
| Payslip delivery | Email + Print only |
| Initial country | Kenya (expand to Uganda later) |

---

## 11. Integration Points

### 11.1 Settings Module (Integrations)
The Settings module will handle all external integrations:

```
Settings > Integrations
├── Mobile Money
│   ├── M-Pesa (Safaricom)
│   ├── M-Pesa (Paybill/Till)
│   └── Airtel Money
├── Banking
│   ├── Bank file formats (KCB, Equity, NCBA, etc.)
│   └── RTGS/EFT settings
└── Government Portals
    ├── KRA iTax (future)
    └── eCitizen (future)
```

### 11.2 Budget Module Integration
- Staff costs posted to budget lines
- Payroll creates journal entries against expense accounts
- Budget warnings when processing payroll

### 11.3 Expense Module Integration
- Reimbursements can be added to payroll
- Salary advances tracked

### 11.4 Workflow Integration
- Payroll approval workflow
- Leave request approval
- Loan approval

---

## 12. Resolved Questions

| Question | Decision |
|----------|----------|
| Historical data migration | Excel upload for existing staff (names, phone, email, role) |
| Time & attendance | New UI for recording attendance; hardware integration later |
| Bank accounts | School bank accounts marked for payroll; employee bank details per staff |

---

## 13. Staff Import Template

Excel columns for bulk staff upload:
- employee_number (required)
- first_name (required)
- middle_name
- last_name (required)
- email
- phone
- gender (male/female)
- date_of_birth
- national_id_number
- department
- designation
- employment_type (permanent/contract/part_time/casual)
- date_joined (required)
- basic_salary
- bank_name
- bank_account_number
- bank_account_name
- mobile_money_provider
- mobile_money_number
- tax_pin
