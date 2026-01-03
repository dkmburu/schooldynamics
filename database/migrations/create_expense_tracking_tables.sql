-- =====================================================
-- Expense Tracking Tables Migration
-- SchoolDynamics Finance Module - Phase 1
-- Created: January 2026
-- For Multi-Tenant Architecture (no schools FK needed)
-- =====================================================

-- Disable FK checks during migration
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------
-- 1. Cost Centers (create first as it's referenced)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS cost_centers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    parent_id INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES cost_centers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 2. Supplier Categories
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS supplier_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 3. Suppliers
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS suppliers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    supplier_code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    category_id INT,
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    tax_pin VARCHAR(20),
    payment_terms INT DEFAULT 30 COMMENT 'Payment terms in days',
    bank_name VARCHAR(100),
    bank_branch VARCHAR(100),
    bank_account VARCHAR(50),
    credit_limit DECIMAL(15,2) DEFAULT 0,
    current_balance DECIMAL(15,2) DEFAULT 0 COMMENT 'Amount owed to supplier',
    is_active BOOLEAN DEFAULT TRUE,
    notes TEXT,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES supplier_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 4. Purchase Orders (LPOs)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS purchase_orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    po_number VARCHAR(30) NOT NULL UNIQUE,
    supplier_id INT NOT NULL,
    order_date DATE NOT NULL,
    expected_delivery_date DATE,
    status ENUM('draft', 'pending_approval', 'approved', 'sent', 'partial', 'received', 'cancelled') DEFAULT 'draft',
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(15,2) DEFAULT 0,
    total_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    notes TEXT,
    delivery_address TEXT,
    prepared_by BIGINT UNSIGNED NOT NULL,
    approved_by BIGINT UNSIGNED,
    approved_at TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT,
    FOREIGN KEY (prepared_by) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 5. Purchase Order Lines
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS purchase_order_lines (
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
    expense_account_id INT COMMENT 'Links to chart_of_accounts',
    cost_center_id INT COMMENT 'Department tracking',
    notes TEXT,
    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (expense_account_id) REFERENCES chart_of_accounts(id) ON DELETE SET NULL,
    FOREIGN KEY (cost_center_id) REFERENCES cost_centers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 6. Goods Received Notes (GRN)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS goods_received_notes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    grn_number VARCHAR(30) NOT NULL UNIQUE,
    purchase_order_id INT NOT NULL,
    supplier_id INT NOT NULL,
    received_date DATE NOT NULL,
    delivery_note_number VARCHAR(50),
    status ENUM('draft', 'confirmed') DEFAULT 'draft',
    notes TEXT,
    received_by BIGINT UNSIGNED NOT NULL,
    confirmed_by BIGINT UNSIGNED,
    confirmed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE RESTRICT,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT,
    FOREIGN KEY (received_by) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (confirmed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 7. GRN Lines
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS grn_lines (
    id INT PRIMARY KEY AUTO_INCREMENT,
    grn_id INT NOT NULL,
    po_line_id INT NOT NULL,
    quantity_received DECIMAL(10,2) NOT NULL,
    quantity_accepted DECIMAL(10,2) NOT NULL,
    quantity_rejected DECIMAL(10,2) DEFAULT 0,
    rejection_reason TEXT,
    FOREIGN KEY (grn_id) REFERENCES goods_received_notes(id) ON DELETE CASCADE,
    FOREIGN KEY (po_line_id) REFERENCES purchase_order_lines(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 8. Supplier Invoices
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS supplier_invoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_number VARCHAR(50) NOT NULL COMMENT 'Supplier invoice number',
    internal_ref VARCHAR(30) NOT NULL UNIQUE COMMENT 'Our internal reference',
    supplier_id INT NOT NULL,
    purchase_order_id INT COMMENT 'Optional link to PO',
    grn_id INT COMMENT 'Optional link to GRN',
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL,
    tax_amount DECIMAL(15,2) DEFAULT 0,
    total_amount DECIMAL(15,2) NOT NULL,
    amount_paid DECIMAL(15,2) DEFAULT 0,
    balance DECIMAL(15,2) NOT NULL,
    status ENUM('draft', 'pending', 'approved', 'partial', 'paid', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    created_by BIGINT UNSIGNED NOT NULL,
    approved_by BIGINT UNSIGNED,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT,
    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE SET NULL,
    FOREIGN KEY (grn_id) REFERENCES goods_received_notes(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 9. Supplier Invoice Lines
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS supplier_invoice_lines (
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
    FOREIGN KEY (expense_account_id) REFERENCES chart_of_accounts(id) ON DELETE SET NULL,
    FOREIGN KEY (cost_center_id) REFERENCES cost_centers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 10. Supplier Payments
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS supplier_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    payment_number VARCHAR(30) NOT NULL UNIQUE,
    supplier_id INT NOT NULL,
    payment_date DATE NOT NULL,
    payment_method ENUM('cash', 'cheque', 'bank_transfer', 'mpesa', 'rtgs') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    reference_number VARCHAR(100) COMMENT 'Cheque no, transfer ref, etc.',
    bank_account_id INT COMMENT 'Which bank account used',
    notes TEXT,
    status ENUM('draft', 'pending_approval', 'approved', 'paid', 'cancelled') DEFAULT 'draft',
    prepared_by BIGINT UNSIGNED NOT NULL,
    approved_by BIGINT UNSIGNED,
    approved_at TIMESTAMP NULL,
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT,
    FOREIGN KEY (prepared_by) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 11. Supplier Payment Allocations
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS supplier_payment_allocations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    payment_id INT NOT NULL,
    supplier_invoice_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    allocated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES supplier_payments(id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_invoice_id) REFERENCES supplier_invoices(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Re-enable FK checks
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- SEED DATA - Default Categories
-- =====================================================
INSERT INTO supplier_categories (name, description) VALUES
('Goods & Materials', 'Suppliers of physical goods and materials'),
('Services', 'Service providers'),
('Utilities', 'Electricity, water, internet providers'),
('Maintenance', 'Repair and maintenance services'),
('Food & Catering', 'Food suppliers and caterers'),
('Transport', 'Transport and logistics'),
('Professional Services', 'Legal, audit, consultancy')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Default Cost Centers
INSERT INTO cost_centers (code, name) VALUES
('ADMIN', 'Administration'),
('ACAD', 'Academic'),
('MAINT', 'Maintenance'),
('TRANS', 'Transport'),
('CATER', 'Catering'),
('SPORT', 'Sports & Co-curricular')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================
CREATE INDEX idx_suppliers_active ON suppliers(is_active);
CREATE INDEX idx_po_status ON purchase_orders(status);
CREATE INDEX idx_po_supplier ON purchase_orders(supplier_id);
CREATE INDEX idx_grn_po ON goods_received_notes(purchase_order_id);
CREATE INDEX idx_sinv_status ON supplier_invoices(status);
CREATE INDEX idx_sinv_supplier ON supplier_invoices(supplier_id);
CREATE INDEX idx_sinv_due_date ON supplier_invoices(due_date);
CREATE INDEX idx_spay_supplier ON supplier_payments(supplier_id);

SELECT 'Migration completed successfully!' AS status;
