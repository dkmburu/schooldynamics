-- =====================================================
-- Phase 3: Kenya Payroll Configuration Seed Data
-- Version: 1.0
-- Description: Seeds Kenya-specific payroll configuration
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Get Kenya's country_id
SET @kenya_id = (SELECT id FROM countries WHERE country_code = 'KE');

-- =====================================================
-- SECTION 1: Kenya PAYE Tax Configuration
-- =====================================================

-- Kenya PAYE Tax Brackets (2024)
INSERT INTO tax_brackets (country_id, name, effective_from, is_current)
VALUES (@kenya_id, 'PAYE 2024', '2024-01-01', 1)
ON DUPLICATE KEY UPDATE is_current = 1;

SET @kenya_tax_bracket = (SELECT id FROM tax_brackets WHERE country_id = @kenya_id AND is_current = 1 LIMIT 1);

-- Clear existing bands for this bracket
DELETE FROM tax_bands WHERE bracket_id = @kenya_tax_bracket;

-- Insert PAYE tax bands (monthly rates)
INSERT INTO tax_bands (bracket_id, band_order, min_amount, max_amount, rate, description) VALUES
(@kenya_tax_bracket, 1, 0, 24000, 10.00, 'First KES 24,000 @ 10%'),
(@kenya_tax_bracket, 2, 24001, 32333, 25.00, 'KES 24,001 - 32,333 @ 25%'),
(@kenya_tax_bracket, 3, 32334, 500000, 30.00, 'KES 32,334 - 500,000 @ 30%'),
(@kenya_tax_bracket, 4, 500001, 800000, 32.50, 'KES 500,001 - 800,000 @ 32.5%'),
(@kenya_tax_bracket, 5, 800001, NULL, 35.00, 'Above KES 800,000 @ 35%');

-- Kenya Personal Relief
INSERT INTO tax_reliefs (country_id, relief_code, relief_name, relief_type, amount, effective_from, is_active)
VALUES (@kenya_id, 'PERSONAL_RELIEF', 'Monthly Personal Relief', 'fixed', 2400.00, '2024-01-01', 1)
ON DUPLICATE KEY UPDATE amount = 2400.00, is_active = 1;

-- Insurance Relief (15% of premiums, max 5,000/month)
INSERT INTO tax_reliefs (country_id, relief_code, relief_name, relief_type, percentage, max_amount, effective_from, requires_proof, is_active)
VALUES (@kenya_id, 'INSURANCE_RELIEF', 'Insurance Relief', 'percentage', 15.00, 5000.00, '2024-01-01', 1, 1)
ON DUPLICATE KEY UPDATE percentage = 15.00, max_amount = 5000.00, is_active = 1;

-- =====================================================
-- SECTION 2: Kenya Statutory Funds
-- =====================================================

-- 2.1 NSSF (National Social Security Fund)
INSERT INTO statutory_funds (country_id, fund_code, fund_name, fund_type, is_mandatory, employer_number_label, employee_number_label, website_url)
VALUES (@kenya_id, 'NSSF', 'National Social Security Fund', 'pension', 1, 'NSSF Employer Code', 'NSSF Member Number', 'https://www.nssf.or.ke')
ON DUPLICATE KEY UPDATE fund_name = 'National Social Security Fund';

SET @nssf_id = (SELECT id FROM statutory_funds WHERE country_id = @kenya_id AND fund_code = 'NSSF');

-- NSSF Rates (Tier I & II combined - 6% each, capped at KES 1,080)
INSERT INTO statutory_fund_rates (fund_id, effective_from, employee_rate_type, employee_rate, employee_max,
    employer_rate_type, employer_rate, employer_max, calculation_basis, is_current, notes)
VALUES (@nssf_id, '2024-02-01', 'percentage', 6.0000, 1080.00, 'percentage', 6.0000, 1080.00, 'pensionable', 1,
    'Tier I (6% up to KES 6,000) + Tier II (6% up to KES 18,000). Total cap KES 1,080 each.')
ON DUPLICATE KEY UPDATE is_current = 1;

-- 2.2 NHIF (National Hospital Insurance Fund)
INSERT INTO statutory_funds (country_id, fund_code, fund_name, fund_type, is_mandatory, employer_number_label, employee_number_label, website_url)
VALUES (@kenya_id, 'NHIF', 'National Hospital Insurance Fund', 'health', 1, 'NHIF Employer Code', 'NHIF Member Number', 'https://www.nhif.or.ke')
ON DUPLICATE KEY UPDATE fund_name = 'National Hospital Insurance Fund';

SET @nhif_id = (SELECT id FROM statutory_funds WHERE country_id = @kenya_id AND fund_code = 'NHIF');

-- NHIF is tiered (employee only)
INSERT INTO statutory_fund_rates (fund_id, effective_from, employee_rate_type, employer_rate_type, employer_fixed, is_current, notes)
VALUES (@nhif_id, '2024-01-01', 'tiered', 'fixed', 0, 1, 'Tiered rates based on gross salary. Employer contribution is zero.')
ON DUPLICATE KEY UPDATE is_current = 1;

SET @nhif_rate_id = (SELECT id FROM statutory_fund_rates WHERE fund_id = @nhif_id AND is_current = 1 LIMIT 1);

-- Clear existing tiers
DELETE FROM statutory_fund_tiers WHERE fund_rate_id = @nhif_rate_id;

-- NHIF Tiered rates
INSERT INTO statutory_fund_tiers (fund_rate_id, tier_order, min_income, max_income, employee_amount, employer_amount) VALUES
(@nhif_rate_id, 1, 0, 5999, 150, 0),
(@nhif_rate_id, 2, 6000, 7999, 300, 0),
(@nhif_rate_id, 3, 8000, 11999, 400, 0),
(@nhif_rate_id, 4, 12000, 14999, 500, 0),
(@nhif_rate_id, 5, 15000, 19999, 600, 0),
(@nhif_rate_id, 6, 20000, 24999, 750, 0),
(@nhif_rate_id, 7, 25000, 29999, 850, 0),
(@nhif_rate_id, 8, 30000, 34999, 900, 0),
(@nhif_rate_id, 9, 35000, 39999, 950, 0),
(@nhif_rate_id, 10, 40000, 44999, 1000, 0),
(@nhif_rate_id, 11, 45000, 49999, 1100, 0),
(@nhif_rate_id, 12, 50000, 59999, 1200, 0),
(@nhif_rate_id, 13, 60000, 69999, 1300, 0),
(@nhif_rate_id, 14, 70000, 79999, 1400, 0),
(@nhif_rate_id, 15, 80000, 89999, 1500, 0),
(@nhif_rate_id, 16, 90000, 99999, 1600, 0),
(@nhif_rate_id, 17, 100000, NULL, 1700, 0);

-- 2.3 Housing Levy (Affordable Housing Levy)
INSERT INTO statutory_funds (country_id, fund_code, fund_name, fund_type, is_mandatory, website_url)
VALUES (@kenya_id, 'HOUSING_LEVY', 'Affordable Housing Levy', 'levy', 1, 'https://www.housingfund.go.ke')
ON DUPLICATE KEY UPDATE fund_name = 'Affordable Housing Levy';

SET @housing_id = (SELECT id FROM statutory_funds WHERE country_id = @kenya_id AND fund_code = 'HOUSING_LEVY');

INSERT INTO statutory_fund_rates (fund_id, effective_from, employee_rate_type, employee_rate,
    employer_rate_type, employer_rate, calculation_basis, is_current, notes)
VALUES (@housing_id, '2024-03-01', 'percentage', 1.5000, 'percentage', 1.5000, 'gross', 1,
    '1.5% employee + 1.5% employer on gross salary')
ON DUPLICATE KEY UPDATE is_current = 1;

-- 2.4 NITA (National Industrial Training Authority) - Employer only
INSERT INTO statutory_funds (country_id, fund_code, fund_name, fund_type, is_mandatory, website_url)
VALUES (@kenya_id, 'NITA', 'National Industrial Training Authority', 'levy', 1, 'https://www.nita.go.ke')
ON DUPLICATE KEY UPDATE fund_name = 'National Industrial Training Authority';

SET @nita_id = (SELECT id FROM statutory_funds WHERE country_id = @kenya_id AND fund_code = 'NITA');

INSERT INTO statutory_fund_rates (fund_id, effective_from, employee_rate_type, employee_fixed,
    employer_rate_type, employer_fixed, is_current, notes)
VALUES (@nita_id, '2024-01-01', 'fixed', 0, 'fixed', 50.00, 1, 'KES 50 per employee per month. Employer only.')
ON DUPLICATE KEY UPDATE is_current = 1;

-- =====================================================
-- SECTION 3: Default Pay Components
-- =====================================================

-- 3.1 Earnings
INSERT INTO pay_components (country_id, component_code, component_name, component_type, category, calculation_type, is_taxable, display_order, is_system)
VALUES
(@kenya_id, 'BASIC', 'Basic Salary', 'earning', 'basic', 'fixed', 1, 1, 1),
(@kenya_id, 'HOUSING', 'Housing Allowance', 'earning', 'allowance', 'fixed', 1, 2, 0),
(@kenya_id, 'TRANSPORT', 'Transport Allowance', 'earning', 'allowance', 'fixed', 1, 3, 0),
(@kenya_id, 'MEDICAL', 'Medical Allowance', 'earning', 'allowance', 'fixed', 1, 4, 0),
(@kenya_id, 'AIRTIME', 'Airtime Allowance', 'earning', 'allowance', 'fixed', 1, 5, 0),
(@kenya_id, 'OVERTIME', 'Overtime Pay', 'earning', 'overtime', 'formula', 1, 10, 0),
(@kenya_id, 'BONUS', 'Bonus', 'earning', 'bonus', 'fixed', 1, 15, 0),
(@kenya_id, 'LEAVE_PAY', 'Leave Allowance', 'earning', 'allowance', 'fixed', 1, 16, 0),
(@kenya_id, 'REIMBURSEMENT', 'Expense Reimbursement', 'earning', 'reimbursement', 'fixed', 0, 20, 0)
ON DUPLICATE KEY UPDATE component_name = VALUES(component_name);

-- 3.2 Statutory Deductions (linked to funds)
INSERT INTO pay_components (country_id, component_code, component_name, component_type, category, calculation_type, is_taxable, statutory_fund_id, display_order, is_system)
VALUES
(@kenya_id, 'PAYE', 'PAYE Tax', 'deduction', 'statutory', 'formula', 0, NULL, 100, 1),
(@kenya_id, 'NSSF_EE', 'NSSF (Employee)', 'deduction', 'statutory', 'formula', 0, @nssf_id, 101, 1),
(@kenya_id, 'NHIF_EE', 'NHIF', 'deduction', 'statutory', 'formula', 0, @nhif_id, 102, 1),
(@kenya_id, 'HOUSING_EE', 'Housing Levy (Employee)', 'deduction', 'statutory', 'formula', 0, @housing_id, 103, 1)
ON DUPLICATE KEY UPDATE component_name = VALUES(component_name);

-- 3.3 Employer Contributions
INSERT INTO pay_components (country_id, component_code, component_name, component_type, category, calculation_type, is_taxable, statutory_fund_id, display_order, show_on_payslip, is_system)
VALUES
(@kenya_id, 'NSSF_ER', 'NSSF (Employer)', 'employer_contribution', 'statutory', 'formula', 0, @nssf_id, 200, 0, 1),
(@kenya_id, 'HOUSING_ER', 'Housing Levy (Employer)', 'employer_contribution', 'statutory', 'formula', 0, @housing_id, 201, 0, 1),
(@kenya_id, 'NITA_ER', 'NITA Levy', 'employer_contribution', 'statutory', 'formula', 0, @nita_id, 202, 0, 1)
ON DUPLICATE KEY UPDATE component_name = VALUES(component_name);

-- 3.4 Voluntary Deductions
INSERT INTO pay_components (country_id, component_code, component_name, component_type, category, calculation_type, is_taxable, display_order, is_system)
VALUES
(@kenya_id, 'SACCO', 'SACCO Contribution', 'deduction', 'voluntary', 'fixed', 0, 110, 0),
(@kenya_id, 'WELFARE', 'Staff Welfare', 'deduction', 'voluntary', 'fixed', 0, 111, 0),
(@kenya_id, 'LOAN_DEDUCT', 'Loan Deduction', 'deduction', 'loan', 'fixed', 0, 120, 0),
(@kenya_id, 'ADVANCE_DEDUCT', 'Salary Advance Deduction', 'deduction', 'loan', 'fixed', 0, 121, 0)
ON DUPLICATE KEY UPDATE component_name = VALUES(component_name);

-- =====================================================
-- SECTION 4: Default Salary Structure
-- =====================================================

INSERT INTO salary_structures (structure_code, structure_name, description, is_default)
VALUES ('TEACHING_STAFF', 'Teaching Staff Structure', 'Default structure for teaching staff', 1)
ON DUPLICATE KEY UPDATE is_default = 1;

SET @teaching_structure = (SELECT id FROM salary_structures WHERE structure_code = 'TEACHING_STAFF');

-- Add components to teaching structure
INSERT INTO salary_structure_components (structure_id, component_id, is_mandatory, calculation_order)
SELECT @teaching_structure, id, 1, display_order FROM pay_components WHERE component_code = 'BASIC' AND country_id = @kenya_id
ON DUPLICATE KEY UPDATE is_mandatory = 1;

INSERT INTO salary_structure_components (structure_id, component_id, is_mandatory, calculation_order)
SELECT @teaching_structure, id, 0, display_order FROM pay_components WHERE component_code = 'HOUSING' AND country_id = @kenya_id
ON DUPLICATE KEY UPDATE is_mandatory = 0;

INSERT INTO salary_structure_components (structure_id, component_id, is_mandatory, calculation_order)
SELECT @teaching_structure, id, 0, display_order FROM pay_components WHERE component_code = 'TRANSPORT' AND country_id = @kenya_id
ON DUPLICATE KEY UPDATE is_mandatory = 0;

-- Non-teaching structure
INSERT INTO salary_structures (structure_code, structure_name, description, is_default)
VALUES ('NON_TEACHING', 'Non-Teaching Staff Structure', 'Structure for administrative and support staff', 0)
ON DUPLICATE KEY UPDATE is_default = 0;

-- =====================================================
-- SECTION 5: Default Salary Grades
-- =====================================================

INSERT INTO salary_grades (grade_code, grade_name, min_salary, max_salary, default_salary, annual_increment, description)
VALUES
('T1', 'Teacher Grade 1 - Entry', 25000, 35000, 28000, 2000, 'Entry level teaching position'),
('T2', 'Teacher Grade 2 - Junior', 35000, 50000, 40000, 2500, 'Junior teacher with 2-4 years experience'),
('T3', 'Teacher Grade 3 - Senior', 50000, 75000, 60000, 3000, 'Senior teacher with 5-10 years experience'),
('T4', 'Teacher Grade 4 - Head of Dept', 75000, 100000, 85000, 4000, 'Department head'),
('T5', 'Teacher Grade 5 - Deputy Principal', 100000, 150000, 120000, 5000, 'Deputy principal'),
('T6', 'Principal', 150000, 250000, 180000, 6000, 'School principal'),
('A1', 'Admin Grade 1 - Support', 18000, 25000, 20000, 1500, 'Support staff - cleaners, watchmen'),
('A2', 'Admin Grade 2 - Clerk', 25000, 40000, 30000, 2000, 'Administrative clerks, secretaries'),
('A3', 'Admin Grade 3 - Officer', 40000, 60000, 50000, 2500, 'Administrative officers'),
('A4', 'Admin Grade 4 - Manager', 60000, 100000, 75000, 3500, 'Administrative managers')
ON DUPLICATE KEY UPDATE min_salary = VALUES(min_salary), max_salary = VALUES(max_salary);

-- =====================================================
-- SECTION 6: Default Leave Types (Kenya)
-- =====================================================

INSERT INTO leave_types (country_id, leave_code, leave_name, default_days_per_year, max_days_per_year, is_paid, requires_approval, allow_carry_forward, max_carry_forward_days)
VALUES
(@kenya_id, 'ANNUAL', 'Annual Leave', 21, 30, 1, 1, 1, 10),
(@kenya_id, 'SICK', 'Sick Leave', 14, 30, 1, 1, 0, NULL),
(@kenya_id, 'MATERNITY', 'Maternity Leave', 90, 90, 1, 1, 0, NULL),
(@kenya_id, 'PATERNITY', 'Paternity Leave', 14, 14, 1, 1, 0, NULL),
(@kenya_id, 'COMPASSIONATE', 'Compassionate Leave', 5, 10, 1, 1, 0, NULL),
(@kenya_id, 'STUDY', 'Study Leave', 0, 30, 0, 1, 0, NULL),
(@kenya_id, 'UNPAID', 'Unpaid Leave', 0, 30, 0, 1, 0, NULL)
ON DUPLICATE KEY UPDATE default_days_per_year = VALUES(default_days_per_year);

-- Update gender-specific leave
UPDATE leave_types SET applicable_gender = 'female', requires_documentation = 1 WHERE leave_code = 'MATERNITY';
UPDATE leave_types SET applicable_gender = 'male' WHERE leave_code = 'PATERNITY';
UPDATE leave_types SET requires_documentation = 1 WHERE leave_code = 'SICK';

-- =====================================================
-- SECTION 7: Kenya Public Holidays 2026
-- =====================================================

INSERT INTO public_holidays (country_id, holiday_date, holiday_name, is_recurring, recurring_month, recurring_day, year)
VALUES
(@kenya_id, '2026-01-01', 'New Year''s Day', 1, 1, 1, NULL),
(@kenya_id, '2026-04-03', 'Good Friday', 0, NULL, NULL, 2026),
(@kenya_id, '2026-04-06', 'Easter Monday', 0, NULL, NULL, 2026),
(@kenya_id, '2026-05-01', 'Labour Day', 1, 5, 1, NULL),
(@kenya_id, '2026-06-01', 'Madaraka Day', 1, 6, 1, NULL),
(@kenya_id, '2026-10-10', 'Huduma Day', 1, 10, 10, NULL),
(@kenya_id, '2026-10-20', 'Mashujaa Day', 1, 10, 20, NULL),
(@kenya_id, '2026-12-12', 'Jamhuri Day', 1, 12, 12, NULL),
(@kenya_id, '2026-12-25', 'Christmas Day', 1, 12, 25, NULL),
(@kenya_id, '2026-12-26', 'Boxing Day', 1, 12, 26, NULL)
ON DUPLICATE KEY UPDATE holiday_name = VALUES(holiday_name);

-- =====================================================
-- SECTION 8: Default Loan Types
-- =====================================================

INSERT INTO loan_types (loan_code, loan_name, max_amount, max_repayment_months, interest_rate, interest_type, requires_approval, max_concurrent_loans)
VALUES
('SALARY_ADVANCE', 'Salary Advance', 50000, 3, 0, 'flat', 1, 1),
('STAFF_LOAN', 'Staff Loan', 500000, 24, 5, 'simple', 1, 1),
('EMERGENCY_LOAN', 'Emergency Loan', 100000, 6, 0, 'flat', 1, 1)
ON DUPLICATE KEY UPDATE max_amount = VALUES(max_amount);

-- =====================================================
-- SECTION 9: Default Departments
-- =====================================================

INSERT INTO hr_departments (department_code, department_name, description)
VALUES
('ADMIN', 'Administration', 'School administration and management'),
('TEACHING', 'Teaching Staff', 'All teaching personnel'),
('SUPPORT', 'Support Staff', 'Non-teaching support staff'),
('FINANCE', 'Finance', 'Finance and accounts department'),
('ICT', 'ICT', 'Information and Communication Technology')
ON DUPLICATE KEY UPDATE department_name = VALUES(department_name);

-- =====================================================
-- SECTION 10: Default Designations
-- =====================================================

SET @admin_dept = (SELECT id FROM hr_departments WHERE department_code = 'ADMIN');
SET @teaching_dept = (SELECT id FROM hr_departments WHERE department_code = 'TEACHING');
SET @support_dept = (SELECT id FROM hr_departments WHERE department_code = 'SUPPORT');
SET @finance_dept = (SELECT id FROM hr_departments WHERE department_code = 'FINANCE');

INSERT INTO hr_designations (designation_code, designation_name, department_id, description)
VALUES
('PRINCIPAL', 'Principal', @admin_dept, 'School Principal'),
('DEP_PRINCIPAL', 'Deputy Principal', @admin_dept, 'Deputy Principal'),
('HOD', 'Head of Department', @teaching_dept, 'Subject or department head'),
('SENIOR_TEACHER', 'Senior Teacher', @teaching_dept, 'Experienced teaching staff'),
('TEACHER', 'Teacher', @teaching_dept, 'Teaching staff'),
('INTERN_TEACHER', 'Intern Teacher', @teaching_dept, 'Teaching intern'),
('BURSAR', 'Bursar', @finance_dept, 'School bursar/accountant'),
('ACCOUNTS_CLERK', 'Accounts Clerk', @finance_dept, 'Accounts assistant'),
('SECRETARY', 'Secretary', @admin_dept, 'Administrative secretary'),
('LIBRARIAN', 'Librarian', @support_dept, 'School librarian'),
('LAB_TECH', 'Laboratory Technician', @support_dept, 'Science laboratory technician'),
('DRIVER', 'Driver', @support_dept, 'School bus driver'),
('SECURITY', 'Security Guard', @support_dept, 'School security'),
('CLEANER', 'Cleaner', @support_dept, 'Cleaning staff'),
('COOK', 'Cook', @support_dept, 'Kitchen staff'),
('MATRON', 'Matron/Patron', @support_dept, 'Boarding matron/patron')
ON DUPLICATE KEY UPDATE designation_name = VALUES(designation_name);

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Kenya payroll configuration seeded successfully!' AS status;
