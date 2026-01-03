# Finance Module Specification

## Overview
A comprehensive financial management system for schools supporting Chart of Accounts, budgeting, payroll, fee management, payments, supplier management, and discount policies.

---

## 1. Chart of Accounts (COA)

### Account Structure
```
Account Code Format: XXX-XXXX-XXX
- First 3 digits: Account Type
- Middle 4 digits: Account Category
- Last 3 digits: Sub-account

Example: 400-1001-001 (Tuition Fees - Grade 1 - Term 1)
```

### Account Types
| Code | Type | Description |
|------|------|-------------|
| 100 | Assets | Cash, Bank, Receivables, Fixed Assets |
| 200 | Liabilities | Payables, Loans, Deferred Revenue |
| 300 | Equity | Capital, Retained Earnings |
| 400 | Income | Fees, Grants, Donations, Other Income |
| 500 | Expenses | Salaries, Utilities, Supplies, Depreciation |

### Database Schema
```sql
-- Chart of Accounts
CREATE TABLE chart_of_accounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    account_code VARCHAR(15) NOT NULL,
    account_name VARCHAR(100) NOT NULL,
    account_type ENUM('asset', 'liability', 'equity', 'income', 'expense') NOT NULL,
    parent_account_id INT NULL,
    is_header BOOLEAN DEFAULT FALSE,  -- Header accounts for grouping
    is_active BOOLEAN DEFAULT TRUE,
    normal_balance ENUM('debit', 'credit') NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (school_id, account_code),
    FOREIGN KEY (school_id) REFERENCES schools(id),
    FOREIGN KEY (parent_account_id) REFERENCES chart_of_accounts(id)
);

-- Journal Entries (Double-Entry Bookkeeping)
CREATE TABLE journal_entries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    entry_number VARCHAR(20) NOT NULL,
    entry_date DATE NOT NULL,
    description TEXT,
    reference_type VARCHAR(50),  -- 'invoice', 'payment', 'payroll', 'manual'
    reference_id INT,
    fiscal_year_id INT NOT NULL,
    fiscal_period_id INT NOT NULL,
    status ENUM('draft', 'posted', 'reversed') DEFAULT 'draft',
    posted_by INT,
    posted_at TIMESTAMP NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id)
);

-- Journal Entry Lines
CREATE TABLE journal_entry_lines (
    id INT PRIMARY KEY AUTO_INCREMENT,
    journal_entry_id INT NOT NULL,
    account_id INT NOT NULL,
    debit_amount DECIMAL(15,2) DEFAULT 0,
    credit_amount DECIMAL(15,2) DEFAULT 0,
    description VARCHAR(255),
    cost_center_id INT,  -- For departmental tracking
    FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id),
    FOREIGN KEY (account_id) REFERENCES chart_of_accounts(id)
);

-- Fiscal Years
CREATE TABLE fiscal_years (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_closed BOOLEAN DEFAULT FALSE,
    closed_at TIMESTAMP NULL,
    closed_by INT,
    FOREIGN KEY (school_id) REFERENCES schools(id)
);

-- Fiscal Periods (Months/Terms)
CREATE TABLE fiscal_periods (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fiscal_year_id INT NOT NULL,
    period_number INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_closed BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (fiscal_year_id) REFERENCES fiscal_years(id)
);
```

---

## 2. Budgeting Module

### Features
- Annual/Term-based budgets
- Department/Cost Center budgets
- Budget vs Actual tracking
- Variance analysis
- Budget approval workflow
- Budget revisions with audit trail

### Database Schema
```sql
-- Budget Headers
CREATE TABLE budgets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    fiscal_year_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    budget_type ENUM('annual', 'term', 'project') NOT NULL,
    status ENUM('draft', 'pending_approval', 'approved', 'active', 'closed') DEFAULT 'draft',
    total_income DECIMAL(15,2) DEFAULT 0,
    total_expense DECIMAL(15,2) DEFAULT 0,
    prepared_by INT NOT NULL,
    approved_by INT,
    approved_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id),
    FOREIGN KEY (fiscal_year_id) REFERENCES fiscal_years(id)
);

-- Budget Lines
CREATE TABLE budget_lines (
    id INT PRIMARY KEY AUTO_INCREMENT,
    budget_id INT NOT NULL,
    account_id INT NOT NULL,
    cost_center_id INT,
    period_1_amount DECIMAL(15,2) DEFAULT 0,  -- Jan or Term 1
    period_2_amount DECIMAL(15,2) DEFAULT 0,
    period_3_amount DECIMAL(15,2) DEFAULT 0,
    period_4_amount DECIMAL(15,2) DEFAULT 0,
    period_5_amount DECIMAL(15,2) DEFAULT 0,
    period_6_amount DECIMAL(15,2) DEFAULT 0,
    period_7_amount DECIMAL(15,2) DEFAULT 0,
    period_8_amount DECIMAL(15,2) DEFAULT 0,
    period_9_amount DECIMAL(15,2) DEFAULT 0,
    period_10_amount DECIMAL(15,2) DEFAULT 0,
    period_11_amount DECIMAL(15,2) DEFAULT 0,
    period_12_amount DECIMAL(15,2) DEFAULT 0,
    annual_total DECIMAL(15,2) GENERATED ALWAYS AS (
        period_1_amount + period_2_amount + period_3_amount +
        period_4_amount + period_5_amount + period_6_amount +
        period_7_amount + period_8_amount + period_9_amount +
        period_10_amount + period_11_amount + period_12_amount
    ) STORED,
    notes TEXT,
    FOREIGN KEY (budget_id) REFERENCES budgets(id),
    FOREIGN KEY (account_id) REFERENCES chart_of_accounts(id)
);

-- Cost Centers (Departments)
CREATE TABLE cost_centers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    code VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL,
    parent_id INT,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (school_id) REFERENCES schools(id)
);
```

---

## 3. Payroll Module

### Features
- Employee salary structures
- Allowances and deductions
- Statutory deductions (PAYE, NHIF, NSSF for Kenya)
- Payroll processing per period
- Bank file generation
- P9 and other tax reports
- Payslip generation

### Database Schema
```sql
-- Salary Structures
CREATE TABLE salary_structures (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id)
);

-- Salary Components (Earnings/Deductions)
CREATE TABLE salary_components (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    code VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL,
    component_type ENUM('earning', 'deduction') NOT NULL,
    calculation_type ENUM('fixed', 'percentage', 'formula') NOT NULL,
    is_taxable BOOLEAN DEFAULT TRUE,
    is_statutory BOOLEAN DEFAULT FALSE,  -- PAYE, NHIF, NSSF
    statutory_type ENUM('paye', 'nhif', 'nssf', 'other') NULL,
    affects_gross BOOLEAN DEFAULT TRUE,
    sequence_order INT DEFAULT 0,
    coa_account_id INT,  -- Links to Chart of Accounts
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (school_id) REFERENCES schools(id),
    FOREIGN KEY (coa_account_id) REFERENCES chart_of_accounts(id)
);

-- Employee Salary Assignments
CREATE TABLE employee_salaries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    salary_structure_id INT NOT NULL,
    basic_salary DECIMAL(15,2) NOT NULL,
    effective_from DATE NOT NULL,
    effective_to DATE,
    bank_id INT,
    bank_account_number VARCHAR(50),
    payment_mode ENUM('bank', 'cash', 'mobile_money') DEFAULT 'bank',
    is_current BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES staff(id),
    FOREIGN KEY (salary_structure_id) REFERENCES salary_structures(id)
);

-- Employee Salary Component Values
CREATE TABLE employee_salary_components (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_salary_id INT NOT NULL,
    salary_component_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    percentage DECIMAL(5,2),  -- If calculation_type is percentage
    FOREIGN KEY (employee_salary_id) REFERENCES employee_salaries(id),
    FOREIGN KEY (salary_component_id) REFERENCES salary_components(id)
);

-- Payroll Runs
CREATE TABLE payroll_runs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    payroll_period VARCHAR(20) NOT NULL,  -- e.g., '2024-01'
    fiscal_period_id INT NOT NULL,
    pay_date DATE NOT NULL,
    status ENUM('draft', 'processing', 'pending_approval', 'approved', 'paid', 'cancelled') DEFAULT 'draft',
    total_gross DECIMAL(15,2) DEFAULT 0,
    total_deductions DECIMAL(15,2) DEFAULT 0,
    total_net DECIMAL(15,2) DEFAULT 0,
    employee_count INT DEFAULT 0,
    processed_by INT,
    approved_by INT,
    approved_at TIMESTAMP NULL,
    journal_entry_id INT,  -- Links to accounting
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id),
    FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id)
);

-- Payroll Details (Individual Payslips)
CREATE TABLE payroll_details (
    id INT PRIMARY KEY AUTO_INCREMENT,
    payroll_run_id INT NOT NULL,
    employee_id INT NOT NULL,
    basic_salary DECIMAL(15,2) NOT NULL,
    gross_pay DECIMAL(15,2) NOT NULL,
    total_deductions DECIMAL(15,2) NOT NULL,
    net_pay DECIMAL(15,2) NOT NULL,
    taxable_income DECIMAL(15,2) NOT NULL,
    paye DECIMAL(15,2) DEFAULT 0,
    nhif DECIMAL(15,2) DEFAULT 0,
    nssf DECIMAL(15,2) DEFAULT 0,
    bank_account VARCHAR(50),
    payment_mode ENUM('bank', 'cash', 'mobile_money'),
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    FOREIGN KEY (payroll_run_id) REFERENCES payroll_runs(id),
    FOREIGN KEY (employee_id) REFERENCES staff(id)
);

-- Payroll Component Details
CREATE TABLE payroll_component_details (
    id INT PRIMARY KEY AUTO_INCREMENT,
    payroll_detail_id INT NOT NULL,
    salary_component_id INT NOT NULL,
    component_type ENUM('earning', 'deduction') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    FOREIGN KEY (payroll_detail_id) REFERENCES payroll_details(id),
    FOREIGN KEY (salary_component_id) REFERENCES salary_components(id)
);
```

---

## 4. Fee Structure Management

### Hierarchy
```
Academic Year
  └── Term (1, 2, 3)
       └── Grade/Class
            └── Fee Structure
                 ├── Mandatory Items
                 │    ├── Tuition
                 │    ├── Development Levy
                 │    └── Exam Fee
                 ├── Optional Curricular (Subject-based)
                 │    ├── Computer Studies
                 │    └── Music Lessons
                 └── Optional Activities
                      ├── Swimming Club
                      ├── Drama Club
                      └── School Trip
```

### Database Schema
```sql
-- Fee Categories
CREATE TABLE fee_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    category_type ENUM('mandatory', 'optional_curricular', 'optional_activity') NOT NULL,
    description TEXT,
    coa_account_id INT,  -- Income account for this category
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (school_id) REFERENCES schools(id),
    FOREIGN KEY (coa_account_id) REFERENCES chart_of_accounts(id)
);

-- Fee Items (Template items)
CREATE TABLE fee_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    fee_category_id INT NOT NULL,
    code VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (school_id) REFERENCES schools(id),
    FOREIGN KEY (fee_category_id) REFERENCES fee_categories(id)
);

-- Fee Structures (Per Grade, Per Term)
CREATE TABLE fee_structures (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    academic_year_id INT NOT NULL,
    term_id INT NOT NULL,
    grade_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    status ENUM('draft', 'pending_approval', 'approved', 'published', 'locked') DEFAULT 'draft',
    total_mandatory DECIMAL(15,2) DEFAULT 0,
    total_optional DECIMAL(15,2) DEFAULT 0,
    prepared_by INT NOT NULL,
    approved_by INT,
    approved_at TIMESTAMP NULL,
    published_at TIMESTAMP NULL,
    locked_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (school_id, academic_year_id, term_id, grade_id),
    FOREIGN KEY (school_id) REFERENCES schools(id),
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id),
    FOREIGN KEY (grade_id) REFERENCES grades(id)
);

-- Fee Structure Lines
CREATE TABLE fee_structure_lines (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fee_structure_id INT NOT NULL,
    fee_item_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    is_mandatory BOOLEAN DEFAULT TRUE,
    applies_to ENUM('all', 'new_students', 'continuing', 'boarding', 'day') DEFAULT 'all',
    notes TEXT,
    FOREIGN KEY (fee_structure_id) REFERENCES fee_structures(id),
    FOREIGN KEY (fee_item_id) REFERENCES fee_items(id)
);

-- Student Optional Items Enrollment
CREATE TABLE student_optional_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    fee_structure_line_id INT NOT NULL,
    academic_year_id INT NOT NULL,
    term_id INT NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    enrolled_by INT NOT NULL,
    status ENUM('active', 'cancelled') DEFAULT 'active',
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (fee_structure_line_id) REFERENCES fee_structure_lines(id)
);
```

---

## 5. Invoicing

### Features
- Batch invoice generation for entire classes/grades
- Individual invoice generation
- Invoice line items from fee structure
- Discount application
- Invoice status tracking
- Credit notes
- Invoice printing/emailing

### Database Schema
```sql
-- Invoices
CREATE TABLE invoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    invoice_number VARCHAR(30) NOT NULL,
    student_id INT NOT NULL,
    guardian_id INT,  -- Bill-to guardian
    academic_year_id INT NOT NULL,
    term_id INT NOT NULL,
    fee_structure_id INT NOT NULL,
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL,
    discount_amount DECIMAL(15,2) DEFAULT 0,
    total_amount DECIMAL(15,2) NOT NULL,
    amount_paid DECIMAL(15,2) DEFAULT 0,
    balance DECIMAL(15,2) GENERATED ALWAYS AS (total_amount - amount_paid) STORED,
    status ENUM('draft', 'sent', 'partial', 'paid', 'overdue', 'cancelled', 'credited') DEFAULT 'draft',
    batch_id INT,  -- For batch-generated invoices
    notes TEXT,
    journal_entry_id INT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL,
    UNIQUE KEY (school_id, invoice_number),
    FOREIGN KEY (school_id) REFERENCES schools(id),
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (fee_structure_id) REFERENCES fee_structures(id),
    FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id)
);

-- Invoice Lines
CREATE TABLE invoice_lines (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_id INT NOT NULL,
    fee_item_id INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    quantity INT DEFAULT 1,
    unit_price DECIMAL(15,2) NOT NULL,
    discount_amount DECIMAL(15,2) DEFAULT 0,
    discount_policy_id INT,
    line_total DECIMAL(15,2) NOT NULL,
    is_optional BOOLEAN DEFAULT FALSE,
    coa_account_id INT,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id),
    FOREIGN KEY (fee_item_id) REFERENCES fee_items(id),
    FOREIGN KEY (discount_policy_id) REFERENCES discount_policies(id),
    FOREIGN KEY (coa_account_id) REFERENCES chart_of_accounts(id)
);

-- Invoice Batches
CREATE TABLE invoice_batches (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    batch_number VARCHAR(30) NOT NULL,
    academic_year_id INT NOT NULL,
    term_id INT NOT NULL,
    description VARCHAR(255),
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    total_invoices INT DEFAULT 0,
    total_amount DECIMAL(15,2) DEFAULT 0,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (school_id) REFERENCES schools(id)
);

-- Credit Notes
CREATE TABLE credit_notes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    credit_note_number VARCHAR(30) NOT NULL,
    invoice_id INT NOT NULL,
    student_id INT NOT NULL,
    credit_date DATE NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('draft', 'approved', 'applied', 'cancelled') DEFAULT 'draft',
    approved_by INT,
    approved_at TIMESTAMP NULL,
    journal_entry_id INT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id),
    FOREIGN KEY (invoice_id) REFERENCES invoices(id),
    FOREIGN KEY (student_id) REFERENCES students(id)
);
```

---

## 6. Payments

### Features
- Multiple payment methods (Cash, Bank, M-Pesa, Card)
- Payment receipts
- Payment allocation to invoices
- Overpayment handling (credit balance)
- Bank reconciliation
- M-Pesa integration

### Database Schema
```sql
-- Payment Methods
CREATE TABLE payment_methods (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    method_type ENUM('cash', 'bank', 'mobile_money', 'card', 'cheque') NOT NULL,
    bank_account_id INT,  -- For bank/mobile money
    is_active BOOLEAN DEFAULT TRUE,
    requires_reference BOOLEAN DEFAULT FALSE,
    coa_account_id INT,  -- Asset account (Cash/Bank)
    FOREIGN KEY (school_id) REFERENCES schools(id),
    FOREIGN KEY (coa_account_id) REFERENCES chart_of_accounts(id)
);

-- Payments Received
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    receipt_number VARCHAR(30) NOT NULL,
    student_id INT NOT NULL,
    payment_date DATE NOT NULL,
    payment_method_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    reference_number VARCHAR(100),  -- Cheque no, M-Pesa code, etc.
    payer_name VARCHAR(100),
    payer_phone VARCHAR(20),
    notes TEXT,
    status ENUM('pending', 'confirmed', 'bounced', 'reversed') DEFAULT 'confirmed',
    journal_entry_id INT,
    received_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (school_id, receipt_number),
    FOREIGN KEY (school_id) REFERENCES schools(id),
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id),
    FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id)
);

-- Payment Allocations (to Invoices)
CREATE TABLE payment_allocations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    payment_id INT NOT NULL,
    invoice_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    allocated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    allocated_by INT NOT NULL,
    FOREIGN KEY (payment_id) REFERENCES payments(id),
    FOREIGN KEY (invoice_id) REFERENCES invoices(id)
);

-- Student Credit Balances (Prepayments/Overpayments)
CREATE TABLE student_credit_balances (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    balance DECIMAL(15,2) DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id)
);

-- Credit Balance Transactions
CREATE TABLE credit_balance_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    transaction_type ENUM('credit', 'debit') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    reference_type VARCHAR(50),  -- 'payment', 'invoice_allocation', 'refund'
    reference_id INT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id)
);
```

---

## 7. Supplier & Procurement

### Features
- Supplier management
- Local Purchase Orders (LPOs)
- Goods Received Notes (GRN)
- Supplier invoices
- Payment to suppliers
- Supplier aging reports

### Database Schema
```sql
-- Suppliers
CREATE TABLE suppliers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    supplier_code VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    tax_pin VARCHAR(20),
    payment_terms INT DEFAULT 30,  -- Days
    bank_name VARCHAR(100),
    bank_account VARCHAR(50),
    credit_limit DECIMAL(15,2) DEFAULT 0,
    current_balance DECIMAL(15,2) DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    coa_account_id INT,  -- Payable account
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (school_id, supplier_code),
    FOREIGN KEY (school_id) REFERENCES schools(id),
    FOREIGN KEY (coa_account_id) REFERENCES chart_of_accounts(id)
);

-- Purchase Orders (LPOs)
CREATE TABLE purchase_orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    po_number VARCHAR(30) NOT NULL,
    supplier_id INT NOT NULL,
    order_date DATE NOT NULL,
    expected_delivery_date DATE,
    status ENUM('draft', 'pending_approval', 'approved', 'sent', 'partial', 'received', 'cancelled') DEFAULT 'draft',
    subtotal DECIMAL(15,2) NOT NULL,
    tax_amount DECIMAL(15,2) DEFAULT 0,
    total_amount DECIMAL(15,2) NOT NULL,
    notes TEXT,
    prepared_by INT NOT NULL,
    approved_by INT,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (school_id, po_number),
    FOREIGN KEY (school_id) REFERENCES schools(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
);

-- Purchase Order Lines
CREATE TABLE purchase_order_lines (
    id INT PRIMARY KEY AUTO_INCREMENT,
    purchase_order_id INT NOT NULL,
    item_description VARCHAR(255) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit VARCHAR(20) DEFAULT 'pcs',
    unit_price DECIMAL(15,2) NOT NULL,
    tax_rate DECIMAL(5,2) DEFAULT 0,
    line_total DECIMAL(15,2) NOT NULL,
    quantity_received DECIMAL(10,2) DEFAULT 0,
    coa_expense_account_id INT,
    cost_center_id INT,
    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id),
    FOREIGN KEY (coa_expense_account_id) REFERENCES chart_of_accounts(id),
    FOREIGN KEY (cost_center_id) REFERENCES cost_centers(id)
);

-- Goods Received Notes
CREATE TABLE goods_received_notes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    grn_number VARCHAR(30) NOT NULL,
    purchase_order_id INT NOT NULL,
    supplier_id INT NOT NULL,
    received_date DATE NOT NULL,
    delivery_note_number VARCHAR(50),
    status ENUM('draft', 'confirmed') DEFAULT 'draft',
    notes TEXT,
    received_by INT NOT NULL,
    confirmed_by INT,
    confirmed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (school_id, grn_number),
    FOREIGN KEY (school_id) REFERENCES schools(id),
    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
);

-- GRN Lines
CREATE TABLE grn_lines (
    id INT PRIMARY KEY AUTO_INCREMENT,
    grn_id INT NOT NULL,
    po_line_id INT NOT NULL,
    quantity_received DECIMAL(10,2) NOT NULL,
    notes TEXT,
    FOREIGN KEY (grn_id) REFERENCES goods_received_notes(id),
    FOREIGN KEY (po_line_id) REFERENCES purchase_order_lines(id)
);

-- Supplier Invoices (Bills)
CREATE TABLE supplier_invoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    invoice_number VARCHAR(50) NOT NULL,
    supplier_id INT NOT NULL,
    purchase_order_id INT,
    grn_id INT,
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL,
    tax_amount DECIMAL(15,2) DEFAULT 0,
    total_amount DECIMAL(15,2) NOT NULL,
    amount_paid DECIMAL(15,2) DEFAULT 0,
    balance DECIMAL(15,2) GENERATED ALWAYS AS (total_amount - amount_paid) STORED,
    status ENUM('draft', 'pending_approval', 'approved', 'partial', 'paid', 'cancelled') DEFAULT 'draft',
    journal_entry_id INT,
    notes TEXT,
    created_by INT NOT NULL,
    approved_by INT,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id),
    FOREIGN KEY (grn_id) REFERENCES goods_received_notes(id),
    FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id)
);

-- Supplier Invoice Lines
CREATE TABLE supplier_invoice_lines (
    id INT PRIMARY KEY AUTO_INCREMENT,
    supplier_invoice_id INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    quantity DECIMAL(10,2) DEFAULT 1,
    unit_price DECIMAL(15,2) NOT NULL,
    tax_rate DECIMAL(5,2) DEFAULT 0,
    line_total DECIMAL(15,2) NOT NULL,
    coa_expense_account_id INT,
    cost_center_id INT,
    FOREIGN KEY (supplier_invoice_id) REFERENCES supplier_invoices(id),
    FOREIGN KEY (coa_expense_account_id) REFERENCES chart_of_accounts(id)
);

-- Supplier Payments
CREATE TABLE supplier_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    payment_number VARCHAR(30) NOT NULL,
    supplier_id INT NOT NULL,
    payment_date DATE NOT NULL,
    payment_method_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    reference_number VARCHAR(100),
    notes TEXT,
    status ENUM('pending', 'approved', 'paid', 'cancelled') DEFAULT 'pending',
    journal_entry_id INT,
    prepared_by INT NOT NULL,
    approved_by INT,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (school_id, payment_number),
    FOREIGN KEY (school_id) REFERENCES schools(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id),
    FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id)
);

-- Supplier Payment Allocations
CREATE TABLE supplier_payment_allocations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    supplier_payment_id INT NOT NULL,
    supplier_invoice_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    FOREIGN KEY (supplier_payment_id) REFERENCES supplier_payments(id),
    FOREIGN KEY (supplier_invoice_id) REFERENCES supplier_invoices(id)
);
```

---

## 8. Discount Policies

### Features
- Multiple discount types
- Automatic application based on rules
- Sibling discounts
- Staff children discounts
- Scholarship discounts
- Early payment discounts
- Need-based discounts

### Database Schema
```sql
-- Discount Policies
CREATE TABLE discount_policies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    code VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL,
    discount_type ENUM('sibling', 'staff_child', 'scholarship', 'early_payment', 'need_based', 'promotional', 'other') NOT NULL,
    calculation_type ENUM('percentage', 'fixed_amount') NOT NULL,
    value DECIMAL(10,2) NOT NULL,  -- Percentage or fixed amount
    applies_to ENUM('all_fees', 'tuition_only', 'specific_items') DEFAULT 'all_fees',
    is_stackable BOOLEAN DEFAULT FALSE,  -- Can combine with other discounts
    priority INT DEFAULT 0,  -- Higher priority applied first
    requires_approval BOOLEAN DEFAULT FALSE,
    auto_apply BOOLEAN DEFAULT FALSE,  -- Auto-apply based on rules
    valid_from DATE,
    valid_to DATE,
    max_discount_amount DECIMAL(15,2),  -- Cap on discount
    is_active BOOLEAN DEFAULT TRUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (school_id, code),
    FOREIGN KEY (school_id) REFERENCES schools(id)
);

-- Sibling Discount Rules
CREATE TABLE sibling_discount_rules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    discount_policy_id INT NOT NULL,
    sibling_position INT NOT NULL,  -- 2nd child, 3rd child, etc.
    discount_percentage DECIMAL(5,2) NOT NULL,
    FOREIGN KEY (discount_policy_id) REFERENCES discount_policies(id)
);

-- Example: 2nd child = 10%, 3rd child = 15%, 4th+ = 20%

-- Discount Policy Fee Items (for 'specific_items' type)
CREATE TABLE discount_policy_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    discount_policy_id INT NOT NULL,
    fee_item_id INT NOT NULL,
    FOREIGN KEY (discount_policy_id) REFERENCES discount_policies(id),
    FOREIGN KEY (fee_item_id) REFERENCES fee_items(id)
);

-- Student Discounts (Applied discounts)
CREATE TABLE student_discounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    discount_policy_id INT NOT NULL,
    academic_year_id INT NOT NULL,
    override_value DECIMAL(10,2),  -- If different from policy
    reason TEXT,
    status ENUM('pending', 'approved', 'rejected', 'expired') DEFAULT 'pending',
    approved_by INT,
    approved_at TIMESTAMP NULL,
    valid_from DATE,
    valid_to DATE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (discount_policy_id) REFERENCES discount_policies(id),
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id)
);
```

---

## 9. Reports

### Financial Reports
1. **Income Statement (P&L)** - Revenue and expenses by period
2. **Balance Sheet** - Assets, liabilities, equity
3. **Trial Balance** - All account balances
4. **Cash Flow Statement** - Cash inflows/outflows
5. **Budget vs Actual** - Variance analysis

### Fee Reports
1. **Fee Collection Summary** - By class, term, payment method
2. **Outstanding Fees Report** - Aging analysis
3. **Discount Report** - Discounts applied
4. **Payment History** - Student payment history
5. **Invoice Aging Report** - 30/60/90 days

### Payroll Reports
1. **Payroll Summary** - Monthly/annual
2. **Bank Transfer List** - For salary payments
3. **P9 Forms** - Annual tax certificates
4. **Statutory Deductions** - PAYE, NHIF, NSSF summaries

### Supplier Reports
1. **Supplier Aging** - Outstanding payables
2. **Purchase Order Status** - Open/closed POs
3. **Supplier Payment History**

---

## 10. Workflow & Approvals

### Approval Workflows
| Document | Levels |
|----------|--------|
| Budget | Preparer → Finance Manager → Principal → Board |
| Fee Structure | Preparer → Finance Manager → Principal |
| Invoice Batch | Auto-generated (no approval) or Manual approval |
| Purchase Order | Preparer → HOD → Finance → Principal (based on amount) |
| Supplier Payment | Preparer → Finance Manager → Principal |
| Payroll | HR → Finance Manager → Principal |
| Credit Note | Finance Officer → Finance Manager |
| Discount (Need-based) | Admissions → Finance → Principal |

---

## 11. Integration Points

### External Systems
1. **M-Pesa API** - Payment collection
2. **Bank APIs** - Payment files, reconciliation
3. **KRA iTax** - Tax filing (P9, returns)
4. **SMS/Email** - Invoice notifications, reminders

### Internal Modules
1. **Students Module** - Student data, enrollment
2. **Staff Module** - Employee data for payroll
3. **Academics** - Grade/Class data for fee structures
4. **Admissions** - Applicant fee tracking

---

## 12. Security & Access Control

### Permissions
```
Finance.coa.view
Finance.coa.create
Finance.coa.edit

Finance.budget.view
Finance.budget.create
Finance.budget.approve

Finance.fees.view
Finance.fees.structure.manage
Finance.fees.invoice.create
Finance.fees.payment.receive
Finance.fees.discount.apply
Finance.fees.discount.approve

Finance.payroll.view
Finance.payroll.process
Finance.payroll.approve

Finance.suppliers.view
Finance.suppliers.manage
Finance.po.create
Finance.po.approve
Finance.payment.approve

Finance.reports.view
Finance.reports.export
```

---

## Implementation Priority

### Phase 1 (Core)
1. Chart of Accounts
2. Fee Categories & Items
3. Fee Structures
4. Student Invoicing
5. Payment Collection
6. Basic Reports

### Phase 2 (Extended)
1. Discount Policies
2. Batch Invoicing
3. Credit Notes
4. Payment Allocation
5. Bank Reconciliation

### Phase 3 (Procurement)
1. Supplier Management
2. Purchase Orders
3. GRN
4. Supplier Invoices
5. Supplier Payments

### Phase 4 (Payroll)
1. Salary Structures
2. Payroll Processing
3. Statutory Deductions
4. Payslip Generation
5. Bank File Export

### Phase 5 (Advanced)
1. Budgeting
2. Budget vs Actual
3. Multi-currency (if needed)
4. Advanced Reporting
5. External Integrations

---

## Design Decisions (Confirmed)

| # | Decision | Resolution |
|---|----------|------------|
| 1 | Multi-campus accounting | Separate books per campus with consolidated view |
| 2 | Currency | Single currency (KES) |
| 3 | Tax handling | Configurable per line item (VAT, WHT, Levies, etc.) |
| 4 | Approval thresholds | Dynamic workflow configuration |
| 5 | Payment integration | Multi-gateway with automatic reconciliation |
| 6 | Payroll frequency | Monthly (standard for Kenya) |
| 7 | Fiscal year | Aligned to academic year for budget tracking |

---

## 13. Account Number Architecture

### The Challenge
1. **Student Account Numbers**: Each student needs a unique account for tracking their fees
2. **Family/Sibling Payments**: Parents with multiple children want ONE account to pay for all
3. **Applicant Payments**: Applicants need to pay before admission (no student record yet)

### Solution: Dual Account System

```
┌─────────────────────────────────────────────────────────────────┐
│                    FAMILY ACCOUNT (FA)                          │
│                    FA-2024-00001                                │
│                                                                 │
│  Primary Guardian: John Doe                                     │
│  Phone: 0722123456                                              │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ Linked Accounts:                                         │   │
│  │                                                          │   │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐   │   │
│  │  │ SA-2024-001  │  │ SA-2024-002  │  │ AA-2024-003  │   │   │
│  │  │ Jane Doe     │  │ James Doe    │  │ Joy Doe      │   │   │
│  │  │ (Grade 5)    │  │ (Grade 3)    │  │ (Applicant)  │   │   │
│  │  │ Student      │  │ Student      │  │ Applying G1  │   │   │
│  │  └──────────────┘  └──────────────┘  └──────────────┘   │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  Total Outstanding: KES 150,000                                 │
│  Credit Balance: KES 5,000                                      │
└─────────────────────────────────────────────────────────────────┘
```

### Account Types

| Type | Code | Purpose | Example |
|------|------|---------|---------|
| **Family Account** | FA-YYYY-NNNNN | Parent/Guardian account for consolidated payments | FA-2024-00001 |
| **Student Account** | SA-YYYY-NNNNN | Individual student fee tracking | SA-2024-00123 |
| **Applicant Account** | AA-YYYY-NNNNN | Pre-admission payment tracking | AA-2024-00456 |

### Account Number Generation
```
Format: {TYPE}-{YEAR}-{SEQUENCE}

Examples:
- FA-2024-00001 (Family Account)
- SA-2024-00123 (Student Account)
- AA-2024-00456 (Applicant Account)

For M-Pesa/Bank Integration (numeric only):
- Family: 1202400001 (1 + YYMM + 5-digit sequence)
- Student: 2202400123 (2 + YYMM + 5-digit sequence)
- Applicant: 3202400456 (3 + YYMM + 5-digit sequence)
```

### Payment Flow

```
Payment Received (e.g., M-Pesa)
         │
         ▼
┌─────────────────────────┐
│ Parse Account Number    │
│ from reference          │
└─────────────────────────┘
         │
         ▼
┌─────────────────────────┐
│ Is it a Family Account? │
├─────────────────────────┤
│ YES: Credit to FA       │───► Auto-allocate to oldest
│      balance            │     invoices across children
├─────────────────────────┤
│ NO: Is Student Account? │───► Credit to SA, allocate
│                         │     to student's invoices
├─────────────────────────┤
│ NO: Is Applicant Acct?  │───► Credit to AA, allocate
│                         │     to application fees
└─────────────────────────┘
```

### Database Schema Addition

```sql
-- Family Accounts
CREATE TABLE family_accounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    account_number VARCHAR(20) NOT NULL,
    numeric_account VARCHAR(15) NOT NULL,  -- For payment gateways
    primary_guardian_id INT NOT NULL,
    account_name VARCHAR(100) NOT NULL,  -- "Doe Family" or guardian name
    phone VARCHAR(20),  -- Primary phone for payments
    email VARCHAR(100),
    credit_balance DECIMAL(15,2) DEFAULT 0,
    total_outstanding DECIMAL(15,2) DEFAULT 0,
    status ENUM('active', 'suspended', 'closed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (school_id, account_number),
    UNIQUE KEY (numeric_account),
    FOREIGN KEY (school_id) REFERENCES schools(id)
);

-- Student Accounts (linked to family)
CREATE TABLE student_accounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    account_number VARCHAR(20) NOT NULL,
    numeric_account VARCHAR(15) NOT NULL,
    student_id INT NOT NULL,
    family_account_id INT,  -- Links to family for consolidated view
    credit_balance DECIMAL(15,2) DEFAULT 0,
    total_outstanding DECIMAL(15,2) DEFAULT 0,
    status ENUM('active', 'suspended', 'closed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (school_id, account_number),
    UNIQUE KEY (numeric_account),
    UNIQUE KEY (student_id),
    FOREIGN KEY (school_id) REFERENCES schools(id),
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (family_account_id) REFERENCES family_accounts(id)
);

-- Applicant Accounts (linked to family if siblings exist)
CREATE TABLE applicant_accounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    account_number VARCHAR(20) NOT NULL,
    numeric_account VARCHAR(15) NOT NULL,
    applicant_id INT NOT NULL,
    family_account_id INT,  -- Links to family if siblings already enrolled
    credit_balance DECIMAL(15,2) DEFAULT 0,
    total_outstanding DECIMAL(15,2) DEFAULT 0,
    status ENUM('active', 'converted', 'closed') DEFAULT 'active',
    converted_to_student_account_id INT,  -- After admission
    converted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (school_id, account_number),
    UNIQUE KEY (numeric_account),
    UNIQUE KEY (applicant_id),
    FOREIGN KEY (school_id) REFERENCES schools(id),
    FOREIGN KEY (applicant_id) REFERENCES applicants(id),
    FOREIGN KEY (family_account_id) REFERENCES family_accounts(id),
    FOREIGN KEY (converted_to_student_account_id) REFERENCES student_accounts(id)
);

-- Family Account Members (for quick lookups)
CREATE TABLE family_account_members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    family_account_id INT NOT NULL,
    member_type ENUM('student', 'applicant') NOT NULL,
    student_id INT,
    applicant_id INT,
    relationship VARCHAR(50),  -- 'child', 'ward'
    is_active BOOLEAN DEFAULT TRUE,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (family_account_id) REFERENCES family_accounts(id),
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (applicant_id) REFERENCES applicants(id)
);
```

### Applicant to Student Conversion

When an applicant is admitted:

```
1. Applicant accepted → admitted
         │
         ▼
2. Student record created
         │
         ▼
3. Student Account created (SA-YYYY-NNNNN)
         │
         ▼
4. Applicant Account marked as 'converted'
   - converted_to_student_account_id = new SA
   - Any credit balance transferred to SA
         │
         ▼
5. If family account exists:
   - Link SA to family account
   - Update family_account_members
         │
         ▼
6. If no family account:
   - Create new family account
   - Link SA to it
   - Primary guardian from applicant guardians
```

### Sibling Detection & Family Account Creation

```sql
-- When creating applicant account, check for existing family
-- Based on guardian phone/email matching

SELECT fa.id as family_account_id
FROM family_accounts fa
JOIN guardians g ON g.phone = fa.phone OR g.email = fa.email
WHERE g.applicant_id = :new_applicant_id
   OR g.student_id IN (SELECT student_id FROM family_account_members)
LIMIT 1;

-- If found, link applicant to existing family
-- If not found, create new family account when first payment needed
```

---

## 14. Tax Configuration

### Tax Types
```sql
CREATE TABLE tax_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    code VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL,
    rate DECIMAL(5,2) NOT NULL,  -- Percentage
    is_inclusive BOOLEAN DEFAULT FALSE,  -- Price includes tax?
    applies_to ENUM('income', 'expense', 'both') DEFAULT 'income',
    coa_account_id INT,  -- Tax payable/receivable account
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (school_id) REFERENCES schools(id),
    FOREIGN KEY (coa_account_id) REFERENCES chart_of_accounts(id)
);

-- Example taxes:
-- VAT (16%), WHT (5%), Catering Levy (2%), Transport Tax, etc.
```

### Fee Item Tax Assignment
```sql
CREATE TABLE fee_item_taxes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fee_item_id INT NOT NULL,
    tax_type_id INT NOT NULL,
    FOREIGN KEY (fee_item_id) REFERENCES fee_items(id),
    FOREIGN KEY (tax_type_id) REFERENCES tax_types(id)
);

-- Invoice line will calculate: amount + (amount * tax_rate)
-- Or if inclusive: amount / (1 + tax_rate) = base, tax = amount - base
```

---

## 15. Payment Gateway Integration

### Multi-Gateway Architecture

```
                    ┌─────────────────────────┐
                    │   Payment Gateway Hub   │
                    │   /api/payments/webhook │
                    └───────────┬─────────────┘
                                │
        ┌───────────────────────┼───────────────────────┐
        │                       │                       │
        ▼                       ▼                       ▼
┌───────────────┐     ┌───────────────┐     ┌───────────────┐
│    M-Pesa     │     │  Bank API     │     │  Card Gateway │
│   (Safaricom) │     │  (Equity/KCB) │     │  (Pesapal)    │
└───────────────┘     └───────────────┘     └───────────────┘
```

### Database Schema

```sql
-- Payment Gateways
CREATE TABLE payment_gateways (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    gateway_code VARCHAR(20) NOT NULL,  -- 'mpesa', 'equity', 'kcb', 'pesapal'
    gateway_name VARCHAR(100) NOT NULL,
    gateway_type ENUM('mobile_money', 'bank', 'card', 'wallet') NOT NULL,
    config JSON,  -- API keys, secrets, endpoints (encrypted)
    paybill_number VARCHAR(20),  -- M-Pesa paybill/till
    bank_account VARCHAR(50),    -- Bank account number
    is_active BOOLEAN DEFAULT TRUE,
    is_primary BOOLEAN DEFAULT FALSE,
    webhook_secret VARCHAR(255),
    last_reconciled_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id)
);

-- Gateway Transactions (raw from gateway)
CREATE TABLE gateway_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    gateway_id INT NOT NULL,
    transaction_id VARCHAR(100) NOT NULL,  -- Gateway's transaction ID
    transaction_type ENUM('payment', 'reversal', 'refund') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'KES',
    account_reference VARCHAR(50),  -- Account number used
    payer_phone VARCHAR(20),
    payer_name VARCHAR(100),
    transaction_time TIMESTAMP NOT NULL,
    status ENUM('pending', 'success', 'failed', 'reversed') NOT NULL,
    raw_response JSON,  -- Full gateway response
    reconciliation_status ENUM('pending', 'matched', 'unmatched', 'manual') DEFAULT 'pending',
    matched_payment_id INT,  -- Links to payments table after reconciliation
    reconciled_at TIMESTAMP,
    reconciled_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (gateway_id, transaction_id),
    FOREIGN KEY (school_id) REFERENCES schools(id),
    FOREIGN KEY (gateway_id) REFERENCES payment_gateways(id),
    FOREIGN KEY (matched_payment_id) REFERENCES payments(id)
);

-- Reconciliation Batches
CREATE TABLE reconciliation_batches (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    gateway_id INT NOT NULL,
    batch_date DATE NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    total_transactions INT DEFAULT 0,
    matched_count INT DEFAULT 0,
    unmatched_count INT DEFAULT 0,
    total_amount DECIMAL(15,2) DEFAULT 0,
    matched_amount DECIMAL(15,2) DEFAULT 0,
    started_at TIMESTAMP,
    completed_at TIMESTAMP,
    processed_by INT,
    notes TEXT,
    FOREIGN KEY (school_id) REFERENCES schools(id),
    FOREIGN KEY (gateway_id) REFERENCES payment_gateways(id)
);
```

### Auto-Reconciliation Flow

```
Webhook Received
      │
      ▼
┌─────────────────────────┐
│ 1. Log raw transaction  │
│    gateway_transactions │
└─────────────────────────┘
      │
      ▼
┌─────────────────────────┐
│ 2. Parse account_ref    │
│    to find account      │
└─────────────────────────┘
      │
      ├── Found Family Account ──► Credit FA, allocate to invoices
      │
      ├── Found Student Account ─► Credit SA, allocate to invoices
      │
      ├── Found Applicant Account► Credit AA, allocate to app fees
      │
      └── Not Found ──────────────► Mark as 'unmatched'
                                    (Manual reconciliation needed)
      │
      ▼
┌─────────────────────────┐
│ 3. Create payment record│
│    Update invoice status│
│    Create journal entry │
└─────────────────────────┘
      │
      ▼
┌─────────────────────────┐
│ 4. Send receipt via     │
│    SMS/Email            │
└─────────────────────────┘
```

---

## 16. Workflow Configuration

### Dynamic Approval Thresholds

```sql
-- Approval Workflows
CREATE TABLE approval_workflows (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    workflow_code VARCHAR(50) NOT NULL,
    workflow_name VARCHAR(100) NOT NULL,
    document_type ENUM('budget', 'purchase_order', 'payment', 'credit_note',
                       'fee_structure', 'discount', 'payroll', 'journal') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (school_id, workflow_code),
    FOREIGN KEY (school_id) REFERENCES schools(id)
);

-- Approval Levels
CREATE TABLE approval_levels (
    id INT PRIMARY KEY AUTO_INCREMENT,
    workflow_id INT NOT NULL,
    level_number INT NOT NULL,
    level_name VARCHAR(100) NOT NULL,
    min_amount DECIMAL(15,2) DEFAULT 0,  -- Threshold
    max_amount DECIMAL(15,2),            -- NULL = unlimited
    approver_type ENUM('role', 'user', 'department_head', 'any_of_role') NOT NULL,
    approver_role_id INT,                -- If type is 'role'
    approver_user_id INT,                -- If type is 'user'
    can_skip_if_self BOOLEAN DEFAULT FALSE,
    requires_all BOOLEAN DEFAULT FALSE,  -- All approvers at level must approve
    FOREIGN KEY (workflow_id) REFERENCES approval_workflows(id)
);

-- Example: Purchase Order Workflow
-- Level 1: 0 - 10,000 → Department Head
-- Level 2: 10,001 - 50,000 → Finance Manager
-- Level 3: 50,001 - 200,000 → Principal
-- Level 4: 200,001+ → Board Finance Committee
```

### Approval Tracking

```sql
-- Approval Requests
CREATE TABLE approval_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    workflow_id INT NOT NULL,
    document_type VARCHAR(50) NOT NULL,
    document_id INT NOT NULL,
    document_amount DECIMAL(15,2),
    current_level INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    requested_by INT NOT NULL,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP,
    FOREIGN KEY (workflow_id) REFERENCES approval_workflows(id)
);

-- Approval Actions
CREATE TABLE approval_actions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    approval_request_id INT NOT NULL,
    level_id INT NOT NULL,
    action ENUM('approved', 'rejected', 'returned', 'delegated') NOT NULL,
    acted_by INT NOT NULL,
    acted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    comments TEXT,
    delegated_to INT,
    FOREIGN KEY (approval_request_id) REFERENCES approval_requests(id),
    FOREIGN KEY (level_id) REFERENCES approval_levels(id)
);
```

---

## Resolved Design Decisions

### 1. Applicant/Admission Fee Structure

Applicant fees are **separate line items** with different refund policies:

| Fee Type | Refundable | Notes |
|----------|------------|-------|
| Application Fee | No | Non-refundable processing fee |
| Caution Money/Deposit | Yes | Refundable upon leaving school |
| First Term Tuition (Partial) | Conditional | Part of regular term fees, credited on admission |

```sql
-- Applicant Fee Items (separate from regular fee structure)
CREATE TABLE applicant_fee_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    academic_year_id INT NOT NULL,
    grade_id INT,  -- NULL = applies to all grades
    code VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    is_refundable BOOLEAN DEFAULT FALSE,
    refund_policy TEXT,  -- e.g., "Refundable upon withdrawal before admission"
    credit_to_term_fees BOOLEAN DEFAULT FALSE,  -- If true, amount credited to first term invoice
    is_required BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    coa_account_id INT,
    FOREIGN KEY (school_id) REFERENCES schools(id),
    FOREIGN KEY (coa_account_id) REFERENCES chart_of_accounts(id)
);

-- Applicant Invoices (before admission)
CREATE TABLE applicant_invoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    invoice_number VARCHAR(30) NOT NULL,
    applicant_id INT NOT NULL,
    applicant_account_id INT NOT NULL,
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL,
    total_amount DECIMAL(15,2) NOT NULL,
    amount_paid DECIMAL(15,2) DEFAULT 0,
    balance DECIMAL(15,2) GENERATED ALWAYS AS (total_amount - amount_paid) STORED,
    status ENUM('draft', 'sent', 'partial', 'paid', 'cancelled', 'converted') DEFAULT 'draft',
    converted_to_invoice_id INT,  -- Links to student invoice after admission
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (school_id, invoice_number),
    FOREIGN KEY (school_id) REFERENCES schools(id),
    FOREIGN KEY (applicant_id) REFERENCES applicants(id),
    FOREIGN KEY (applicant_account_id) REFERENCES applicant_accounts(id)
);

-- Applicant Invoice Lines
CREATE TABLE applicant_invoice_lines (
    id INT PRIMARY KEY AUTO_INCREMENT,
    applicant_invoice_id INT NOT NULL,
    applicant_fee_item_id INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    is_refundable BOOLEAN DEFAULT FALSE,
    credit_to_term_fees BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (applicant_invoice_id) REFERENCES applicant_invoices(id),
    FOREIGN KEY (applicant_fee_item_id) REFERENCES applicant_fee_items(id)
);
```

**Admission Conversion Flow:**
```
Applicant Admitted
       │
       ▼
┌──────────────────────────────────────┐
│ 1. Create Student Record             │
│ 2. Create Student Account (SA)       │
│ 3. Create Family Account (FA)        │
│    - Link SA to FA                   │
└──────────────────────────────────────┘
       │
       ▼
┌──────────────────────────────────────┐
│ 4. Process Applicant Invoice Lines:  │
│                                      │
│    ├─ Non-refundable fees           │
│    │  → Already recognized as income │
│    │                                 │
│    ├─ Refundable deposits           │
│    │  → Transfer to student liability│
│    │    (Caution Money Payable)     │
│    │                                 │
│    └─ Credit to term fees           │
│       → Deduct from first term      │
│         invoice as "Advance Payment" │
└──────────────────────────────────────┘
       │
       ▼
┌──────────────────────────────────────┐
│ 5. Generate First Term Invoice       │
│    - Full fee structure amount       │
│    - Less: Advance from application  │
│    = Net amount due                  │
└──────────────────────────────────────┘
```

### 2. Family Account Creation Timing

**Decision:** Create Family Account **only after admission**

```
Timeline:
─────────────────────────────────────────────────────────────────►

  APPLICATION PHASE              │        STUDENT PHASE
                                 │
  ┌─────────────┐               │    ┌─────────────┐
  │ Applicant   │               │    │ Student     │
  │ Account     │  ──ADMITTED──►│    │ Account     │
  │ (AA-...)    │               │    │ (SA-...)    │
  └─────────────┘               │    └──────┬──────┘
        │                       │           │
        │                       │           ▼
        │                       │    ┌─────────────┐
   Uses AA for                  │    │ Family      │
   application                  │    │ Account     │
   payments                     │    │ (FA-...)    │
                                │    └─────────────┘
                                │           │
                                │    Created at admission,
                                │    links all siblings
```

**Rationale:**
- Simplifies pre-admission tracking
- Family relationship confirmed only at admission
- Siblings identified via guardian matching at admission time

### 3. Payment Allocation Priority

**Decision:** FIFO by default, with **invoice-specific override**

```sql
-- Add allocation_mode to payments
ALTER TABLE payments ADD COLUMN allocation_mode
    ENUM('auto_fifo', 'specific_invoice') DEFAULT 'auto_fifo';

ALTER TABLE payments ADD COLUMN target_invoice_id INT NULL;
-- If allocation_mode = 'specific_invoice', allocate to this invoice only
```

**Allocation Logic:**
```
Payment Received
       │
       ▼
┌─────────────────────────────┐
│ Is target_invoice_id set?   │
├─────────────────────────────┤
│ YES: Allocate to that       │───► Allocate full amount to
│      specific invoice       │     specified invoice
│      (e.g., School Trip)    │     (excess → credit balance)
├─────────────────────────────┤
│ NO: Use FIFO                │───► Get oldest unpaid invoices
│     (default)               │     across all children in FA
│                             │     Allocate in order of:
│                             │     1. Invoice date (oldest first)
│                             │     2. Due date (most urgent first)
└─────────────────────────────┘
```

**Use Cases:**
| Scenario | Mode | Behavior |
|----------|------|----------|
| Regular term fee payment | auto_fifo | Clears oldest balances first |
| School trip payment | specific_invoice | Only applies to trip invoice |
| Exam fee payment | specific_invoice | Only applies to exam invoice |
| General deposit | auto_fifo | Clears oldest, excess → credit |

### 4. Campus-Specific Accounts

**Decision:** Separate accounts per campus (no cross-campus consolidation)

```
School Group: ABC Schools
├── Campus A (Primary)
│   └── Family Account: FA-A-2024-00001
│       ├── SA-A-2024-001 (Child 1 - Grade 3)
│       └── SA-A-2024-002 (Child 2 - Grade 1)
│
└── Campus B (Secondary)
    └── Family Account: FA-B-2024-00001  ← SEPARATE ACCOUNT
        └── SA-B-2024-001 (Child 3 - Form 2)
```

**Schema Update:**
```sql
-- Family accounts are campus-specific
ALTER TABLE family_accounts ADD COLUMN campus_id INT NOT NULL;
ALTER TABLE family_accounts ADD FOREIGN KEY (campus_id) REFERENCES campuses(id);

-- Account number includes campus code
-- Format: FA-{CAMPUS_CODE}-{YEAR}-{SEQUENCE}
-- Example: FA-NPR-2024-00001 (Nairobi Primary)
--          FA-NSC-2024-00001 (Nairobi Secondary)
```

**Rationale:**
- Campuses may have different fee structures
- Separate accounting for each campus
- Clear audit trail per campus
- School group can still generate consolidated reports

**Cross-Campus Visibility:**
- Parent portal shows accounts from ALL campuses
- Each account managed separately
- Consolidated view for reporting only (not payment)

---

## 17. Applicant Financial Flow (Complete)

```
┌─────────────────────────────────────────────────────────────────────┐
│                        APPLICANT PHASE                              │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  1. Application Created                                             │
│     └─► Applicant Account (AA) auto-generated                      │
│         Account: AA-NPR-2024-00456                                  │
│                                                                     │
│  2. Application Fee Invoice Generated                               │
│     ┌─────────────────────────────────────────────────────────┐    │
│     │ Invoice: AAI-2024-00456                                  │    │
│     │ ─────────────────────────────────────────────────────── │    │
│     │ Application Fee (Non-refundable)         KES  2,000     │    │
│     │ Caution Money (Refundable)               KES 10,000     │    │
│     │ Admission Deposit (Credit to Term 1)     KES 20,000     │    │
│     │ ─────────────────────────────────────────────────────── │    │
│     │ TOTAL DUE                                KES 32,000     │    │
│     └─────────────────────────────────────────────────────────┘    │
│                                                                     │
│  3. Payment Received (M-Pesa to AA-NPR-2024-00456)                 │
│     └─► Invoice marked as PAID                                     │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
                              │
                              │ ADMITTED
                              ▼
┌─────────────────────────────────────────────────────────────────────┐
│                        ADMISSION CONVERSION                         │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  4. Student Record Created                                          │
│     └─► Student Account (SA) generated: SA-NPR-2024-00789          │
│                                                                     │
│  5. Family Account Created                                          │
│     └─► FA-NPR-2024-00123 (linked to guardian)                     │
│         └─► SA-NPR-2024-00789 linked to FA                         │
│                                                                     │
│  6. Applicant Account Converted                                     │
│     └─► AA marked as 'converted'                                   │
│         └─► converted_to_student_account_id = SA-NPR-2024-00789    │
│                                                                     │
│  7. Financial Items Processed:                                      │
│     ┌─────────────────────────────────────────────────────────┐    │
│     │ Application Fee (KES 2,000)                              │    │
│     │ └─► Already recognized as income (no action)            │    │
│     │                                                          │    │
│     │ Caution Money (KES 10,000)                               │    │
│     │ └─► Transfer to Student's "Caution Money Liability"     │    │
│     │     (Refundable when student leaves)                    │    │
│     │                                                          │    │
│     │ Admission Deposit (KES 20,000)                           │    │
│     │ └─► Credit to Student Account balance                   │    │
│     │     (Will offset first term invoice)                    │    │
│     └─────────────────────────────────────────────────────────┘    │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────────┐
│                        STUDENT PHASE                                │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  8. First Term Invoice Generated                                    │
│     ┌─────────────────────────────────────────────────────────┐    │
│     │ Invoice: INV-2024-00001                                  │    │
│     │ Student: SA-NPR-2024-00789                               │    │
│     │ ─────────────────────────────────────────────────────── │    │
│     │ Tuition Fee - Term 1                     KES 85,000     │    │
│     │ Development Levy                         KES  5,000     │    │
│     │ Activity Fee                             KES  3,000     │    │
│     │ Computer Lab                             KES  2,000     │    │
│     │ ─────────────────────────────────────────────────────── │    │
│     │ SUBTOTAL                                 KES 95,000     │    │
│     │ Less: Credit from Admission Deposit     (KES 20,000)    │    │
│     │ ─────────────────────────────────────────────────────── │    │
│     │ NET AMOUNT DUE                           KES 75,000     │    │
│     └─────────────────────────────────────────────────────────┘    │
│                                                                     │
│  9. Subsequent Payments                                             │
│     └─► Pay using Family Account: FA-NPR-2024-00123                │
│         (Covers all children at this campus)                       │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Summary of Resolved Decisions

| Question | Decision |
|----------|----------|
| Applicant fees structure | Separate items: Application (non-refundable), Caution (refundable), Deposit (credits to term) |
| Family account timing | Created **only at admission** |
| Payment allocation | **FIFO by default**, with invoice-specific override for targeted payments |
| Cross-campus accounts | **Separate per campus**, consolidated view for reporting only |

---

## 18. Fee Structure Design (Detailed)

### The Challenge
Different grades have different fee structures with varying:
- Mandatory vs optional items
- Subject-based fees (Piano, Ballet, etc.)
- Transport tariffs (distance-based, one-way/two-way)
- Activity fees (trips, clubs)
- All feeding into batch invoice generation

### Fee Structure Hierarchy

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         FEE STRUCTURE TEMPLATE                          │
│                    Academic Year 2024 | Term 1 | Grade 1                │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  MANDATORY FEES (Auto-included in every invoice)                        │
│  ───────────────────────────────────────────────────────────────────── │
│  │ Tuition Fee                                    KES  20,000         │ │
│  │ Development Levy                               KES   2,000         │ │
│  │ Exam Fee                                       KES   1,500         │ │
│  └─────────────────────────────────────────────────────────────────── │
│                                                                         │
│  OPTIONAL - MEALS (Student enrolls or not)                             │
│  ───────────────────────────────────────────────────────────────────── │
│  │ Full Board (Breakfast + Lunch + Snack)         KES   4,000         │ │
│  │ Lunch Only                                     KES   2,500         │ │
│  │ Snack Only                                     KES     800         │ │
│  └─────────────────────────────────────────────────────────────────── │
│                                                                         │
│  OPTIONAL - TRANSPORT (Distance-based tariff)                          │
│  ───────────────────────────────────────────────────────────────────── │
│  │ Zone A (0-5km) - Two Way                       KES   3,000         │ │
│  │ Zone A (0-5km) - One Way                       KES   1,800         │ │
│  │ Zone B (5-10km) - Two Way                      KES   4,500         │ │
│  │ Zone B (5-10km) - One Way                      KES   2,700         │ │
│  │ Zone C (10-20km) - Two Way                     KES   6,000         │ │
│  │ Zone C (10-20km) - One Way                     KES   3,600         │ │
│  └─────────────────────────────────────────────────────────────────── │
│                                                                         │
│  OPTIONAL - ACTIVITIES (Per-event/term)                                │
│  ───────────────────────────────────────────────────────────────────── │
│  │ Swimming Club                                  KES   2,000         │ │
│  │ Drama Club                                     KES   1,500         │ │
│  │ School Trip - Nairobi National Park            KES  19,000         │ │
│  └─────────────────────────────────────────────────────────────────── │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                         FEE STRUCTURE TEMPLATE                          │
│                    Academic Year 2024 | Term 1 | Grade 8                │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  MANDATORY FEES                                                         │
│  ───────────────────────────────────────────────────────────────────── │
│  │ Tuition Fee (Core Subjects)                    KES  40,000         │ │
│  │ Development Levy                               KES   3,000         │ │
│  │ Exam Fee                                       KES   2,000         │ │
│  │ Lab Fee                                        KES   1,500         │ │
│  └─────────────────────────────────────────────────────────────────── │
│                                                                         │
│  OPTIONAL - ELECTIVE SUBJECTS (Student chooses)                        │
│  ───────────────────────────────────────────────────────────────────── │
│  │ Piano Lessons                                  KES   3,000         │ │
│  │ Ballet Classes                                 KES   5,000         │ │
│  │ French (Extra Language)                        KES   4,000         │ │
│  │ Computer Programming                           KES   3,500         │ │
│  │ Art & Design                                   KES   2,500         │ │
│  └─────────────────────────────────────────────────────────────────── │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### Database Schema (Refined)

```sql
-- =====================================================
-- FEE CATEGORIES (Types of fees)
-- =====================================================
CREATE TABLE fee_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    code VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL,
    category_type ENUM(
        'mandatory',           -- Always included
        'optional_meal',       -- Meals - mutually exclusive options
        'optional_transport',  -- Transport - tariff-based
        'optional_subject',    -- Elective subjects
        'optional_activity',   -- Clubs, trips, events
        'optional_other'       -- Miscellaneous optional
    ) NOT NULL,
    is_recurring BOOLEAN DEFAULT TRUE,  -- Charged every term or one-time
    allow_partial BOOLEAN DEFAULT FALSE, -- Can pay in installments
    coa_income_account_id INT,  -- Revenue account
    coa_receivable_account_id INT,  -- AR account
    description TEXT,
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (school_id, code),
    FOREIGN KEY (school_id) REFERENCES schools(id),
    FOREIGN KEY (coa_income_account_id) REFERENCES chart_of_accounts(id),
    FOREIGN KEY (coa_receivable_account_id) REFERENCES chart_of_accounts(id)
);

-- =====================================================
-- FEE ITEMS (Master list of chargeable items)
-- =====================================================
CREATE TABLE fee_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    fee_category_id INT NOT NULL,
    code VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    default_amount DECIMAL(15,2),  -- Default, can be overridden per grade
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (school_id, code),
    FOREIGN KEY (school_id) REFERENCES schools(id),
    FOREIGN KEY (fee_category_id) REFERENCES fee_categories(id)
);

-- =====================================================
-- TRANSPORT ZONES (For distance-based pricing)
-- =====================================================
CREATE TABLE transport_zones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    zone_code VARCHAR(10) NOT NULL,
    zone_name VARCHAR(100) NOT NULL,
    min_distance_km DECIMAL(5,2),
    max_distance_km DECIMAL(5,2),
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    UNIQUE KEY (school_id, zone_code),
    FOREIGN KEY (school_id) REFERENCES schools(id)
);

-- =====================================================
-- TRANSPORT TARIFFS (Price per zone per direction)
-- =====================================================
CREATE TABLE transport_tariffs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    academic_year_id INT NOT NULL,
    term_id INT,  -- NULL = applies to all terms
    transport_zone_id INT NOT NULL,
    direction ENUM('one_way', 'two_way') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    fee_item_id INT NOT NULL,  -- Links to fee_items for COA
    is_active BOOLEAN DEFAULT TRUE,
    UNIQUE KEY (school_id, academic_year_id, term_id, transport_zone_id, direction),
    FOREIGN KEY (school_id) REFERENCES schools(id),
    FOREIGN KEY (transport_zone_id) REFERENCES transport_zones(id),
    FOREIGN KEY (fee_item_id) REFERENCES fee_items(id)
);

-- =====================================================
-- FEE STRUCTURES (Per Grade, Per Term)
-- =====================================================
CREATE TABLE fee_structures (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    campus_id INT NOT NULL,
    academic_year_id INT NOT NULL,
    term_id INT NOT NULL,
    grade_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    status ENUM('draft', 'pending_approval', 'approved', 'published', 'locked') DEFAULT 'draft',
    total_mandatory DECIMAL(15,2) DEFAULT 0,
    total_optional_max DECIMAL(15,2) DEFAULT 0,  -- If all optional selected
    version INT DEFAULT 1,
    effective_date DATE,
    prepared_by INT NOT NULL,
    approved_by INT,
    approved_at TIMESTAMP NULL,
    published_at TIMESTAMP NULL,
    locked_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (school_id, campus_id, academic_year_id, term_id, grade_id),
    FOREIGN KEY (school_id) REFERENCES schools(id),
    FOREIGN KEY (campus_id) REFERENCES campuses(id),
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id),
    FOREIGN KEY (grade_id) REFERENCES grades(id)
);

-- =====================================================
-- FEE STRUCTURE LINES (Items in the structure)
-- =====================================================
CREATE TABLE fee_structure_lines (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fee_structure_id INT NOT NULL,
    fee_item_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    is_mandatory BOOLEAN DEFAULT FALSE,

    -- Applicability rules
    applies_to_student_type ENUM('all', 'new', 'continuing') DEFAULT 'all',
    applies_to_boarding_status ENUM('all', 'boarding', 'day') DEFAULT 'all',
    applies_to_gender ENUM('all', 'male', 'female') DEFAULT 'all',

    -- For mutually exclusive options (e.g., meal plans)
    option_group VARCHAR(50),  -- e.g., 'meal_plan' - student picks one from group

    -- For transport
    transport_tariff_id INT,  -- If this line is transport-based

    -- Display
    sort_order INT DEFAULT 0,
    notes TEXT,

    FOREIGN KEY (fee_structure_id) REFERENCES fee_structures(id) ON DELETE CASCADE,
    FOREIGN KEY (fee_item_id) REFERENCES fee_items(id),
    FOREIGN KEY (transport_tariff_id) REFERENCES transport_tariffs(id)
);

-- =====================================================
-- STUDENT FEE ENROLLMENTS (What each student is enrolled in)
-- =====================================================
CREATE TABLE student_fee_enrollments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    fee_structure_id INT NOT NULL,
    fee_structure_line_id INT NOT NULL,
    academic_year_id INT NOT NULL,
    term_id INT NOT NULL,

    -- For transport
    transport_zone_id INT,
    transport_direction ENUM('one_way', 'two_way'),
    pickup_location TEXT,

    -- Status
    status ENUM('active', 'cancelled', 'transferred') DEFAULT 'active',
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    enrolled_by INT NOT NULL,
    cancelled_at TIMESTAMP NULL,
    cancelled_by INT,
    cancellation_reason TEXT,

    UNIQUE KEY (student_id, fee_structure_line_id, academic_year_id, term_id),
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (fee_structure_id) REFERENCES fee_structures(id),
    FOREIGN KEY (fee_structure_line_id) REFERENCES fee_structure_lines(id),
    FOREIGN KEY (transport_zone_id) REFERENCES transport_zones(id)
);
```

### Invoice Generation Flow

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    BATCH INVOICE GENERATION                             │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  INPUT:                                                                 │
│  ├─ Academic Year: 2024                                                │
│  ├─ Term: 1                                                            │
│  ├─ Grade(s): Grade 1, Grade 2, ... (or all)                          │
│  └─ Invoice Date: 2024-01-05                                           │
│                                                                         │
│  PROCESS FOR EACH STUDENT:                                              │
│  ─────────────────────────────────────────────────────────────────────  │
│                                                                         │
│  1. Get Student's Fee Structure (based on grade)                        │
│     └─► fee_structures WHERE grade_id = student.grade_id               │
│                                                                         │
│  2. Add MANDATORY Lines                                                 │
│     └─► fee_structure_lines WHERE is_mandatory = TRUE                  │
│                                                                         │
│  3. Add OPTIONAL Lines (based on enrollments)                          │
│     └─► student_fee_enrollments for this student/term                  │
│         ├─ Meal plan selection                                         │
│         ├─ Transport (zone + direction)                                │
│         ├─ Elective subjects                                           │
│         └─ Activities/clubs                                            │
│                                                                         │
│  4. Apply DISCOUNTS                                                     │
│     └─► student_discounts for this student                             │
│         ├─ Sibling discount                                            │
│         ├─ Staff child discount                                        │
│         └─ Scholarship                                                  │
│                                                                         │
│  5. Calculate BALANCE FORWARD                                           │
│     └─► Previous term unpaid invoices                                  │
│                                                                         │
│  6. Apply CREDIT BALANCE                                                │
│     └─► student_accounts.credit_balance                                │
│                                                                         │
│  7. Generate Invoice                                                    │
│     └─► Create invoice + lines + journal entry                         │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### Invoice Structure Example

```
┌─────────────────────────────────────────────────────────────────────────┐
│                              INVOICE                                     │
│                         INV-2024-00001                                   │
├─────────────────────────────────────────────────────────────────────────┤
│  Student: John Doe (SA-NPR-2024-00789)                                  │
│  Grade: Grade 1 | Term: 1 | Academic Year: 2024                         │
│  Invoice Date: 05-Jan-2024 | Due Date: 15-Jan-2024                      │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  BALANCE FORWARD                                                        │
│  ─────────────────────────────────────────────────────────────────────  │
│  Previous Term Balance (INV-2023-00456)            KES      5,000.00   │
│                                                    ───────────────────  │
│  Subtotal B/F                                      KES      5,000.00   │
│                                                                         │
│  MANDATORY FEES                                                         │
│  ─────────────────────────────────────────────────────────────────────  │
│  Tuition Fee - Term 1                              KES     20,000.00   │
│  Development Levy                                  KES      2,000.00   │
│  Exam Fee                                          KES      1,500.00   │
│                                                    ───────────────────  │
│  Subtotal Mandatory                                KES     23,500.00   │
│                                                                         │
│  OPTIONAL FEES                                                          │
│  ─────────────────────────────────────────────────────────────────────  │
│  Lunch Only (Meal Plan)                            KES      2,500.00   │
│  Transport - Zone B (Two Way)                      KES      4,500.00   │
│  Swimming Club                                     KES      2,000.00   │
│  School Trip - Nairobi National Park               KES     19,000.00   │
│                                                    ───────────────────  │
│  Subtotal Optional                                 KES     28,000.00   │
│                                                                         │
│  ─────────────────────────────────────────────────────────────────────  │
│  GROSS TOTAL                                       KES     56,500.00   │
│                                                                         │
│  DISCOUNTS                                                              │
│  ─────────────────────────────────────────────────────────────────────  │
│  Sibling Discount (10% on Tuition)                (KES      2,000.00)  │
│  Staff Child Discount (15% on Total)              (KES      8,175.00)  │
│                                                    ───────────────────  │
│  Total Discounts                                  (KES     10,175.00)  │
│                                                                         │
│  ─────────────────────────────────────────────────────────────────────  │
│  NET TOTAL                                         KES     46,325.00   │
│                                                                         │
│  CREDITS APPLIED                                                        │
│  ─────────────────────────────────────────────────────────────────────  │
│  Credit Balance Applied                           (KES      3,000.00)  │
│                                                                         │
│  ═══════════════════════════════════════════════════════════════════   │
│  AMOUNT DUE                                        KES     43,325.00   │
│  ═══════════════════════════════════════════════════════════════════   │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### Double-Entry Accounting for Invoices

```sql
-- When invoice is generated:

-- Journal Entry: INV-2024-00001
-- Description: Student fees invoice - John Doe - Term 1 2024

-- DEBIT: Accounts Receivable (per student)     KES 43,325.00
--   CR: Tuition Fee Income                                    KES 18,000.00
--   CR: Development Levy Income                               KES  2,000.00
--   CR: Exam Fee Income                                       KES  1,500.00
--   CR: Meal Income                                           KES  2,500.00
--   CR: Transport Income                                      KES  4,500.00
--   CR: Activities Income                                     KES 21,000.00
--   CR: Discount Allowed (Contra-Revenue)                    (KES 10,175.00)
--   CR: Student Credit Balance Applied                        KES  3,000.00
--   CR: Balance B/F (Already in AR)                           KES      0.00
```

```sql
-- Invoice Generation Journal Entry
CREATE TABLE invoice_journal_entries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_id INT NOT NULL,
    journal_entry_id INT NOT NULL,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id),
    FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id)
);

-- Journal Entry Lines for Invoice
-- Simplified example:
INSERT INTO journal_entry_lines (journal_entry_id, account_id, debit_amount, credit_amount, description)
VALUES
-- Debit AR
(1, 110100, 43325.00, 0, 'Student fees - John Doe'),
-- Credit Income accounts
(1, 400100, 0, 18000.00, 'Tuition Fee'),
(1, 400200, 0, 2000.00, 'Development Levy'),
(1, 400300, 0, 1500.00, 'Exam Fee'),
(1, 400400, 0, 2500.00, 'Meal Income'),
(1, 400500, 0, 4500.00, 'Transport Income'),
(1, 400600, 0, 21000.00, 'Activities Income'),
-- Debit Discount (contra-revenue)
(1, 400900, 10175.00, 0, 'Discount Allowed'),
-- Debit Credit Balance (reduce liability)
(1, 210100, 3000.00, 0, 'Credit Balance Applied');
```

### Payment Double-Entry

```sql
-- When payment received: KES 20,000 via M-Pesa

-- Journal Entry: PMT-2024-00001
-- DEBIT: M-Pesa Clearing Account              KES 20,000.00
--   CR: Accounts Receivable - John Doe                       KES 20,000.00

-- When M-Pesa settles to bank:
-- DEBIT: Bank Account                         KES 19,800.00
-- DEBIT: M-Pesa Charges                       KES    200.00
--   CR: M-Pesa Clearing Account                              KES 20,000.00
```

### Invoice Batching Schema

```sql
-- Invoice Batches
CREATE TABLE invoice_batches (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    campus_id INT NOT NULL,
    batch_number VARCHAR(30) NOT NULL,
    academic_year_id INT NOT NULL,
    term_id INT NOT NULL,
    batch_type ENUM('term_fees', 'activity', 'supplementary') DEFAULT 'term_fees',

    -- Filters applied
    grade_ids JSON,  -- NULL = all grades, or [1,2,3]
    student_type ENUM('all', 'new', 'continuing'),

    -- Batch settings
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    include_balance_forward BOOLEAN DEFAULT TRUE,
    apply_credit_balances BOOLEAN DEFAULT TRUE,

    -- Results
    status ENUM('draft', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'draft',
    total_students INT DEFAULT 0,
    total_invoices INT DEFAULT 0,
    total_amount DECIMAL(15,2) DEFAULT 0,
    total_discounts DECIMAL(15,2) DEFAULT 0,
    total_credits_applied DECIMAL(15,2) DEFAULT 0,
    net_amount DECIMAL(15,2) DEFAULT 0,

    -- Processing
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_by INT NOT NULL,
    error_log TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (school_id, batch_number),
    FOREIGN KEY (school_id) REFERENCES schools(id),
    FOREIGN KEY (campus_id) REFERENCES campuses(id)
);

-- Invoices (Updated with batch reference)
ALTER TABLE invoices ADD COLUMN batch_id INT NULL;
ALTER TABLE invoices ADD COLUMN balance_forward DECIMAL(15,2) DEFAULT 0;
ALTER TABLE invoices ADD COLUMN credit_applied DECIMAL(15,2) DEFAULT 0;
ALTER TABLE invoices ADD FOREIGN KEY (batch_id) REFERENCES invoice_batches(id);
```

### Student Optional Enrollment UI Flow

```
┌─────────────────────────────────────────────────────────────────────────┐
│              STUDENT FEE ENROLLMENT - John Doe (Grade 1)                │
├─────────────────────────────────────────────────────────────────────────┤
│  Term: 1 | Academic Year: 2024                                          │
│                                                                         │
│  MEAL PLAN (Select one)                                                │
│  ───────────────────────────────────────────────────────────────────── │
│  ○ No Meals                                                            │
│  ○ Snack Only                                    KES    800            │
│  ● Lunch Only                                    KES  2,500  ✓         │
│  ○ Full Board (Breakfast + Lunch + Snack)        KES  4,000            │
│                                                                         │
│  TRANSPORT                                                              │
│  ───────────────────────────────────────────────────────────────────── │
│  ☑ Requires Transport                                                  │
│                                                                         │
│  Zone: [Zone B (5-10km)     ▼]                                         │
│  Direction: ● Two Way  ○ One Way                                       │
│  Pickup Location: [Westlands Roundabout          ]                     │
│                                          Amount: KES  4,500            │
│                                                                         │
│  ACTIVITIES & CLUBS (Select all that apply)                            │
│  ───────────────────────────────────────────────────────────────────── │
│  ☑ Swimming Club                                 KES  2,000            │
│  ☐ Drama Club                                    KES  1,500            │
│  ☑ School Trip - Nairobi National Park           KES 19,000            │
│  ☐ Music Club                                    KES  1,800            │
│                                                                         │
│  ───────────────────────────────────────────────────────────────────── │
│  ESTIMATED TOTAL (excluding mandatory fees):      KES 28,000           │
│                                                                         │
│  [Cancel]                                    [Save Enrollment]          │
└─────────────────────────────────────────────────────────────────────────┘
```
