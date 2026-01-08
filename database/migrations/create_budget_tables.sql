-- =====================================================
-- Budget Management Tables Migration
-- SchoolDynamics Finance Module - Phase 2
-- Created: January 2026
-- For Multi-Tenant Architecture
-- =====================================================

-- Disable FK checks during migration
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------
-- 1. Budget Periods (Fiscal Years)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS budget_periods (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL COMMENT 'e.g., FY 2026, Academic Year 2026',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('draft', 'active', 'closed') DEFAULT 'draft',
    is_current BOOLEAN DEFAULT FALSE,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    UNIQUE KEY uk_budget_period_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 2. Budgets (Header - links to account/cost center)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS budgets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    budget_period_id INT NOT NULL,
    budget_code VARCHAR(30) NOT NULL,
    name VARCHAR(150) NOT NULL COMMENT 'Budget name/description',
    account_id INT NOT NULL COMMENT 'Links to chart_of_accounts (expense account)',
    cost_center_id INT COMMENT 'Optional cost center',
    annual_amount DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'Total annual budget',
    status ENUM('draft', 'pending_approval', 'approved', 'rejected', 'revised') DEFAULT 'draft',
    notes TEXT,
    created_by BIGINT UNSIGNED NOT NULL,
    approved_by BIGINT UNSIGNED,
    approved_at TIMESTAMP NULL,
    workflow_ticket_id INT COMMENT 'Links to workflow_tickets for approval tracking',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (budget_period_id) REFERENCES budget_periods(id) ON DELETE RESTRICT,
    FOREIGN KEY (account_id) REFERENCES chart_of_accounts(id) ON DELETE RESTRICT,
    FOREIGN KEY (cost_center_id) REFERENCES cost_centers(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uk_budget_period_account (budget_period_id, account_id, cost_center_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 3. Budget Lines (Monthly Allocations)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS budget_lines (
    id INT PRIMARY KEY AUTO_INCREMENT,
    budget_id INT NOT NULL,
    month_year DATE NOT NULL COMMENT 'First day of the month (e.g., 2026-01-01)',
    allocated_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    spent_amount DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'Calculated from actuals',
    committed_amount DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'From approved POs not yet invoiced',
    available_amount DECIMAL(15,2) GENERATED ALWAYS AS (allocated_amount - spent_amount - committed_amount) STORED,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (budget_id) REFERENCES budgets(id) ON DELETE CASCADE,
    UNIQUE KEY uk_budget_month (budget_id, month_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 4. Budget Revisions (Track changes to approved budgets)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS budget_revisions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    budget_id INT NOT NULL,
    revision_number INT NOT NULL DEFAULT 1,
    revision_type ENUM('increase', 'decrease', 'reallocation', 'correction') NOT NULL,
    previous_annual_amount DECIMAL(15,2) NOT NULL,
    new_annual_amount DECIMAL(15,2) NOT NULL,
    change_amount DECIMAL(15,2) GENERATED ALWAYS AS (new_annual_amount - previous_annual_amount) STORED,
    reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    requested_by BIGINT UNSIGNED NOT NULL,
    approved_by BIGINT UNSIGNED,
    approved_at TIMESTAMP NULL,
    workflow_ticket_id INT COMMENT 'Links to workflow_tickets',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (budget_id) REFERENCES budgets(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 5. Budget Revision Lines (Monthly changes in revision)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS budget_revision_lines (
    id INT PRIMARY KEY AUTO_INCREMENT,
    revision_id INT NOT NULL,
    budget_line_id INT NOT NULL,
    previous_amount DECIMAL(15,2) NOT NULL,
    new_amount DECIMAL(15,2) NOT NULL,
    FOREIGN KEY (revision_id) REFERENCES budget_revisions(id) ON DELETE CASCADE,
    FOREIGN KEY (budget_line_id) REFERENCES budget_lines(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 6. Budget Overruns (Track and approve over-budget spending)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS budget_overruns (
    id INT PRIMARY KEY AUTO_INCREMENT,
    budget_id INT NOT NULL,
    budget_line_id INT COMMENT 'Specific month if applicable',
    entity_type ENUM('purchase_order', 'supplier_invoice', 'expense') NOT NULL,
    entity_id INT NOT NULL COMMENT 'ID of the PO/Invoice/Expense',
    overrun_amount DECIMAL(15,2) NOT NULL COMMENT 'Amount exceeding budget',
    budget_available DECIMAL(15,2) NOT NULL COMMENT 'Budget available at time of request',
    requested_amount DECIMAL(15,2) NOT NULL COMMENT 'Total amount requested',
    reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    requested_by BIGINT UNSIGNED NOT NULL,
    approved_by BIGINT UNSIGNED,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT,
    workflow_ticket_id INT COMMENT 'Links to workflow_tickets',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (budget_id) REFERENCES budgets(id) ON DELETE RESTRICT,
    FOREIGN KEY (budget_line_id) REFERENCES budget_lines(id) ON DELETE SET NULL,
    FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 7. Budget Transactions (Actual spending records)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS budget_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    budget_id INT NOT NULL,
    budget_line_id INT NOT NULL,
    transaction_type ENUM('commitment', 'actual', 'reversal') NOT NULL,
    entity_type ENUM('purchase_order', 'supplier_invoice', 'grn', 'payment', 'journal') NOT NULL,
    entity_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL COMMENT 'Positive for spending, negative for reversals',
    transaction_date DATE NOT NULL,
    description VARCHAR(255),
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (budget_id) REFERENCES budgets(id) ON DELETE RESTRICT,
    FOREIGN KEY (budget_line_id) REFERENCES budget_lines(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_budget_trans_entity (entity_type, entity_id),
    INDEX idx_budget_trans_date (transaction_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 8. Budget Templates (For easy budget replication)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS budget_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    account_id INT NOT NULL,
    cost_center_id INT,
    monthly_amounts JSON COMMENT 'JSON array of 12 monthly percentages or amounts',
    is_active BOOLEAN DEFAULT TRUE,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES chart_of_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (cost_center_id) REFERENCES cost_centers(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Re-enable FK checks
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================
CREATE INDEX idx_budgets_period ON budgets(budget_period_id);
CREATE INDEX idx_budgets_status ON budgets(status);
CREATE INDEX idx_budgets_account ON budgets(account_id);
CREATE INDEX idx_budget_lines_month ON budget_lines(month_year);
CREATE INDEX idx_budget_overruns_status ON budget_overruns(status);
CREATE INDEX idx_budget_overruns_entity ON budget_overruns(entity_type, entity_id);

-- =====================================================
-- SEED DATA - Default Budget Period
-- =====================================================
INSERT INTO budget_periods (name, start_date, end_date, status, is_current, created_by)
SELECT 'Fiscal Year 2026', '2026-01-01', '2026-12-31', 'active', TRUE, id
FROM users WHERE id = 1
ON DUPLICATE KEY UPDATE name = VALUES(name);

SELECT 'Budget tables migration completed successfully!' AS status;
