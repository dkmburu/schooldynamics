# Finance Module - Expense Tracking Implementation Plan

## Overview

This document outlines the implementation plan for expense tracking in SchoolDynamics, enabling a complete Income Statement (Profit & Loss) with both revenue and expenses.

---

## Implementation Phases

### Phase 1: Expense Tracking Foundation (Current)
- Suppliers Management
- Purchase Orders (LPOs)
- Goods Received Notes (GRN)
- Supplier Invoices
- Supplier Payments

### Phase 2: Budgeting
- Budget Creation & Approval
- Budget vs Actual Tracking
- Variance Analysis

### Phase 3: Payroll
- Salary Structures
- Payroll Processing
- Statutory Deductions (PAYE, NHIF, NSSF)

### Phase 4: Complete Financial Reporting
- Income Statement (P&L)
- Balance Sheet
- Cash Flow Statement

---

## Phase 1: Expense Tracking Foundation

### 1.1 Suppliers Management

#### Features
- Supplier registration and management
- Contact information and payment terms
- Bank details for payments
- Credit limit tracking
- Supplier balance tracking
- Supplier categories (goods, services, utilities, etc.)

#### Database Tables
```sql
-- Supplier Categories
CREATE TABLE supplier_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (school_id) REFERENCES schools(id)
);

-- Suppliers (already in spec - verify exists)
CREATE TABLE suppliers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    supplier_code VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL,
    category_id INT,
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    tax_pin VARCHAR(20),
    payment_terms INT DEFAULT 30,  -- Days
    bank_name VARCHAR(100),
    bank_branch VARCHAR(100),
    bank_account VARCHAR(50),
    credit_limit DECIMAL(15,2) DEFAULT 0,
    current_balance DECIMAL(15,2) DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (school_id, supplier_code),
    FOREIGN KEY (school_id) REFERENCES schools(id),
    FOREIGN KEY (category_id) REFERENCES supplier_categories(id)
);
```

#### Routes
```
GET  /finance/suppliers              - List all suppliers
GET  /finance/suppliers/create       - Show create form
POST /finance/suppliers/store        - Store new supplier
GET  /finance/suppliers/:id          - View supplier details
GET  /finance/suppliers/:id/edit     - Edit supplier form
POST /finance/suppliers/:id/update   - Update supplier
GET  /finance/suppliers/:id/statement - Supplier statement
```

#### UI Screens
1. **Suppliers List** - Table with search, filter by category/status
2. **Add/Edit Supplier** - Form with validation
3. **Supplier Detail** - Profile, transactions, balance

---

### 1.2 Purchase Orders (LPOs)

#### Features
- Create purchase orders to suppliers
- Multi-line items with quantities and prices
- Approval workflow (optional based on amount thresholds)
- Link to expense accounts (Chart of Accounts)
- Track order status (Draft → Approved → Sent → Received)
- Partial receiving support

#### Database Tables
```sql
-- Purchase Orders (verify exists from spec)
CREATE TABLE purchase_orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    po_number VARCHAR(30) NOT NULL,
    supplier_id INT NOT NULL,
    order_date DATE NOT NULL,
    expected_delivery_date DATE,
    status ENUM('draft', 'pending_approval', 'approved', 'sent', 'partial', 'received', 'cancelled') DEFAULT 'draft',
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(15,2) DEFAULT 0,
    total_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    notes TEXT,
    delivery_address TEXT,
    prepared_by INT NOT NULL,
    approved_by INT,
    approved_at TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (school_id, po_number),
    FOREIGN KEY (school_id) REFERENCES schools(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
);

-- Purchase Order Lines
CREATE TABLE purchase_order_lines (
    id INT PRIMARY KEY AUTO_INCREMENT,
    purchase_order_id INT NOT NULL,
    line_number INT NOT NULL,
    item_description VARCHAR(255) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit VARCHAR(20) DEFAULT 'pcs',
    unit_price DECIMAL(15,2) NOT NULL,
    tax_rate DECIMAL(5,2) DEFAULT 0,
    tax_amount DECIMAL(15,2) DEFAULT 0,
    line_total DECIMAL(15,2) NOT NULL,
    quantity_received DECIMAL(10,2) DEFAULT 0,
    expense_account_id INT,          -- Links to chart_of_accounts
    cost_center_id INT,              -- Department tracking
    notes TEXT,
    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (expense_account_id) REFERENCES chart_of_accounts(id),
    FOREIGN KEY (cost_center_id) REFERENCES cost_centers(id)
);
```

#### Routes
```
GET  /finance/purchase-orders              - List all POs
GET  /finance/purchase-orders/create       - Create PO form
POST /finance/purchase-orders/store        - Store new PO
GET  /finance/purchase-orders/:id          - View PO details
GET  /finance/purchase-orders/:id/edit     - Edit PO (if draft)
POST /finance/purchase-orders/:id/update   - Update PO
POST /finance/purchase-orders/:id/approve  - Approve PO
POST /finance/purchase-orders/:id/send     - Mark as sent to supplier
GET  /finance/purchase-orders/:id/print    - Print PO
POST /finance/purchase-orders/:id/cancel   - Cancel PO
```

#### Workflow
```
┌──────────┐     ┌──────────────────┐     ┌──────────┐     ┌────────┐     ┌──────────┐
│  Draft   │ ──► │ Pending Approval │ ──► │ Approved │ ──► │  Sent  │ ──► │ Received │
└──────────┘     └──────────────────┘     └──────────┘     └────────┘     └──────────┘
     │                   │                      │              │
     │                   │                      │              ▼
     └───────────────────┴──────────────────────┴──────► [Cancelled]
```

---

### 1.3 Goods Received Notes (GRN)

#### Features
- Record goods received against Purchase Orders
- Partial receiving (multiple GRNs per PO)
- Quality check notes
- Update PO status based on received quantities

#### Database Tables
```sql
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
    quantity_accepted DECIMAL(10,2) NOT NULL,
    quantity_rejected DECIMAL(10,2) DEFAULT 0,
    rejection_reason TEXT,
    FOREIGN KEY (grn_id) REFERENCES goods_received_notes(id) ON DELETE CASCADE,
    FOREIGN KEY (po_line_id) REFERENCES purchase_order_lines(id)
);
```

#### Routes
```
GET  /finance/grn                    - List all GRNs
GET  /finance/grn/create/:po_id      - Create GRN for PO
POST /finance/grn/store              - Store GRN
GET  /finance/grn/:id                - View GRN
POST /finance/grn/:id/confirm        - Confirm GRN
```

---

### 1.4 Supplier Invoices

#### Features
- Record invoices from suppliers
- Link to Purchase Orders (optional)
- Track invoice due dates
- Aging analysis (Current, 30, 60, 90+ days)

#### Database Tables
```sql
-- Supplier Invoices
CREATE TABLE supplier_invoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    invoice_number VARCHAR(50) NOT NULL,      -- Supplier's invoice number
    internal_ref VARCHAR(30) NOT NULL,        -- Our reference number
    supplier_id INT NOT NULL,
    purchase_order_id INT,                    -- Optional link to PO
    grn_id INT,                               -- Optional link to GRN
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL,
    tax_amount DECIMAL(15,2) DEFAULT 0,
    total_amount DECIMAL(15,2) NOT NULL,
    amount_paid DECIMAL(15,2) DEFAULT 0,
    balance DECIMAL(15,2) NOT NULL,
    status ENUM('draft', 'pending', 'approved', 'partial', 'paid', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    created_by INT NOT NULL,
    approved_by INT,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (school_id, internal_ref),
    FOREIGN KEY (school_id) REFERENCES schools(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id),
    FOREIGN KEY (grn_id) REFERENCES goods_received_notes(id)
);

-- Supplier Invoice Lines
CREATE TABLE supplier_invoice_lines (
    id INT PRIMARY KEY AUTO_INCREMENT,
    supplier_invoice_id INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    quantity DECIMAL(10,2) DEFAULT 1,
    unit_price DECIMAL(15,2) NOT NULL,
    tax_rate DECIMAL(5,2) DEFAULT 0,
    tax_amount DECIMAL(15,2) DEFAULT 0,
    line_total DECIMAL(15,2) NOT NULL,
    expense_account_id INT,
    cost_center_id INT,
    FOREIGN KEY (supplier_invoice_id) REFERENCES supplier_invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (expense_account_id) REFERENCES chart_of_accounts(id),
    FOREIGN KEY (cost_center_id) REFERENCES cost_centers(id)
);
```

#### Routes
```
GET  /finance/supplier-invoices              - List all invoices
GET  /finance/supplier-invoices/create       - Create invoice form
POST /finance/supplier-invoices/store        - Store invoice
GET  /finance/supplier-invoices/:id          - View invoice
POST /finance/supplier-invoices/:id/approve  - Approve invoice
GET  /finance/supplier-invoices/aging        - Aging report
```

---

### 1.5 Supplier Payments

#### Features
- Record payments to suppliers
- Allocate to specific invoices
- Multiple payment methods (Cash, Cheque, Bank Transfer, M-Pesa)
- Payment approval workflow
- Automatic supplier balance update

#### Database Tables
```sql
-- Supplier Payments
CREATE TABLE supplier_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    school_id INT NOT NULL,
    payment_number VARCHAR(30) NOT NULL,
    supplier_id INT NOT NULL,
    payment_date DATE NOT NULL,
    payment_method ENUM('cash', 'cheque', 'bank_transfer', 'mpesa', 'rtgs') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    reference_number VARCHAR(100),            -- Cheque no, transfer ref, etc.
    bank_account_id INT,                      -- Which bank account used
    notes TEXT,
    status ENUM('draft', 'pending_approval', 'approved', 'paid', 'cancelled') DEFAULT 'draft',
    prepared_by INT NOT NULL,
    approved_by INT,
    approved_at TIMESTAMP NULL,
    paid_at TIMESTAMP NULL,
    journal_entry_id INT,                     -- Links to accounting
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (school_id, payment_number),
    FOREIGN KEY (school_id) REFERENCES schools(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id)
);

-- Payment Allocations (link payment to invoices)
CREATE TABLE supplier_payment_allocations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    payment_id INT NOT NULL,
    supplier_invoice_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    allocated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES supplier_payments(id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_invoice_id) REFERENCES supplier_invoices(id)
);
```

#### Routes
```
GET  /finance/supplier-payments              - List all payments
GET  /finance/supplier-payments/create       - Create payment form
POST /finance/supplier-payments/store        - Store payment
GET  /finance/supplier-payments/:id          - View payment
POST /finance/supplier-payments/:id/approve  - Approve payment
GET  /finance/supplier-payments/:id/print    - Print payment voucher
```

---

## Expense Categories (Chart of Accounts)

Standard expense accounts to be seeded:

| Code | Account Name | Category |
|------|--------------|----------|
| 500-1000-000 | Salaries & Wages | Staff Costs |
| 500-1001-000 | PAYE | Staff Costs |
| 500-1002-000 | NHIF Contribution | Staff Costs |
| 500-1003-000 | NSSF Contribution | Staff Costs |
| 500-2000-000 | Teaching Materials | Academic |
| 500-2001-000 | Books & Stationery | Academic |
| 500-2002-000 | Laboratory Supplies | Academic |
| 500-3000-000 | Electricity | Utilities |
| 500-3001-000 | Water | Utilities |
| 500-3002-000 | Internet & Phone | Utilities |
| 500-4000-000 | Building Maintenance | Maintenance |
| 500-4001-000 | Equipment Repair | Maintenance |
| 500-4002-000 | Cleaning & Sanitation | Maintenance |
| 500-5000-000 | Transport & Fuel | Operations |
| 500-5001-000 | Food & Catering | Operations |
| 500-5002-000 | Security Services | Operations |
| 500-6000-000 | Insurance | Administrative |
| 500-6001-000 | Bank Charges | Administrative |
| 500-6002-000 | Professional Fees | Administrative |
| 500-6003-000 | Licenses & Permits | Administrative |

---

## UI/UX Design Guidelines

### Navigation
Add to Finance sidebar:
```
Finance
├── Dashboard
├── Fee Management
│   ├── Fee Structures
│   ├── Fee Items
│   └── Transport Tariffs
├── Billing
│   ├── Invoices
│   ├── Payments
│   └── Credit Notes
├── Accounts
│   ├── Student Accounts
│   └── Family Accounts
├── Expenses (NEW)
│   ├── Suppliers
│   ├── Purchase Orders
│   ├── Goods Received
│   ├── Supplier Invoices
│   └── Supplier Payments
├── Reports
│   ├── Collection Report
│   ├── Outstanding Balances
│   ├── Income Statement
│   └── Supplier Aging
└── Settings
    ├── Chart of Accounts
    ├── Payment Methods
    └── Expense Categories
```

### Dashboard Additions
- Total Outstanding to Suppliers
- Payments Due This Week
- Recent Supplier Payments
- Expense by Category Chart

---

## Implementation Order

1. **Database Setup**
   - Create/verify tables exist
   - Seed expense accounts
   - Seed supplier categories

2. **Suppliers Module**
   - CRUD operations
   - List with filters
   - Supplier statement

3. **Purchase Orders**
   - Create/Edit PO
   - Line items management
   - Approval workflow
   - Print LPO

4. **Goods Received Notes**
   - Create from PO
   - Partial receiving
   - Update PO status

5. **Supplier Invoices**
   - Create invoice
   - Link to PO (optional)
   - Aging report

6. **Supplier Payments**
   - Record payment
   - Allocate to invoices
   - Payment voucher

7. **Reports**
   - Supplier Statement
   - Aging Analysis
   - Expense by Category
   - Expense by Period

---

## Security & Permissions

| Permission | Description |
|------------|-------------|
| `finance.suppliers.view` | View suppliers list |
| `finance.suppliers.create` | Add new suppliers |
| `finance.suppliers.edit` | Edit supplier details |
| `finance.po.view` | View purchase orders |
| `finance.po.create` | Create purchase orders |
| `finance.po.approve` | Approve purchase orders |
| `finance.invoices.supplier.view` | View supplier invoices |
| `finance.invoices.supplier.create` | Create supplier invoices |
| `finance.invoices.supplier.approve` | Approve supplier invoices |
| `finance.payments.supplier.view` | View supplier payments |
| `finance.payments.supplier.create` | Create supplier payments |
| `finance.payments.supplier.approve` | Approve supplier payments |

---

## Next Steps

After completing Phase 1:
1. **Phase 2: Budgeting** - Create budget module with variance tracking
2. **Phase 3: Payroll** - Implement payroll processing
3. **Phase 4: Financial Reports** - Complete Income Statement with expenses

---

*Document Created: January 2026*
*Last Updated: January 2026*
