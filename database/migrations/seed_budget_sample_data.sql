-- =====================================================
-- Budget Sample Data
-- For testing the Budget Management module
-- =====================================================

-- Disable FK checks (needed due to FK constraint issues)
SET FOREIGN_KEY_CHECKS = 0;

-- Ensure we have a budget period
INSERT INTO budget_periods (name, start_date, end_date, status, is_current, created_by)
VALUES ('Fiscal Year 2026', '2026-01-01', '2026-12-31', 'active', TRUE, 1)
ON DUPLICATE KEY UPDATE status = 'active', is_current = TRUE;

SET @period_id = (SELECT id FROM budget_periods WHERE is_current = TRUE LIMIT 1);

-- Use actual expense account IDs from chart_of_accounts
SET @acc_supplies = 35;    -- 500-2002-000 Supplies
SET @acc_utilities = 34;   -- 500-2001-000 Utilities
SET @acc_maintenance = 36; -- 500-2003-000 Maintenance
SET @acc_salaries = 31;    -- 500-1001-000 Salaries and Wages
SET @acc_operating = 33;   -- 500-2000-000 Operating Expenses

-- Get cost center IDs
SET @cc_admin = (SELECT id FROM cost_centers WHERE code = 'ADMIN' LIMIT 1);
SET @cc_acad = (SELECT id FROM cost_centers WHERE code = 'ACAD' LIMIT 1);
SET @cc_maint = (SELECT id FROM cost_centers WHERE code = 'MAINT' LIMIT 1);
SET @cc_trans = (SELECT id FROM cost_centers WHERE code = 'TRANS' LIMIT 1);
SET @cc_cater = (SELECT id FROM cost_centers WHERE code = 'CATER' LIMIT 1);

-- Insert sample budgets (different statuses)
INSERT INTO budgets (budget_period_id, budget_code, name, account_id, cost_center_id, annual_amount, status, notes, created_by, approved_by, approved_at)
VALUES
-- Approved budgets
(@period_id, 'BUD-5002-ADMIN-0001', 'Office Supplies - Administration', @acc_supplies, @cc_admin, 240000.00, 'approved', 'Stationery, printing supplies, and office consumables', 1, 1, NOW()),
(@period_id, 'BUD-5002-ACAD-0002', 'Teaching Materials - Academic', @acc_supplies, @cc_acad, 360000.00, 'approved', 'Books, charts, lab supplies for teaching', 1, 1, NOW()),
(@period_id, 'BUD-5001-ADMIN-0003', 'Utilities - Administration', @acc_utilities, @cc_admin, 480000.00, 'approved', 'Electricity, water, internet bills', 1, 1, NOW()),
(@period_id, 'BUD-5003-MAINT-0004', 'Building Maintenance', @acc_maintenance, @cc_maint, 600000.00, 'approved', 'Repairs, painting, plumbing, electrical work', 1, 1, NOW()),
(@period_id, 'BUD-5000-TRANS-0005', 'Transport Operations', @acc_operating, @cc_trans, 720000.00, 'approved', 'Fuel, vehicle maintenance, driver allowances', 1, 1, NOW()),
-- Pending approval
(@period_id, 'BUD-5000-CATER-0006', 'School Catering', @acc_operating, @cc_cater, 1200000.00, 'pending_approval', 'Food supplies and catering services', 1, NULL, NULL),
-- Draft
(@period_id, 'BUD-5002-MAINT-0007', 'Maintenance Supplies', @acc_supplies, @cc_maint, 180000.00, 'draft', 'Cleaning supplies, tools, and equipment', 1, NULL, NULL);

-- Get budget IDs for allocations
SET @bud1 = (SELECT id FROM budgets WHERE budget_code = 'BUD-5002-ADMIN-0001');
SET @bud2 = (SELECT id FROM budgets WHERE budget_code = 'BUD-5002-ACAD-0002');
SET @bud3 = (SELECT id FROM budgets WHERE budget_code = 'BUD-5001-ADMIN-0003');
SET @bud4 = (SELECT id FROM budgets WHERE budget_code = 'BUD-5003-MAINT-0004');
SET @bud5 = (SELECT id FROM budgets WHERE budget_code = 'BUD-5000-TRANS-0005');
SET @bud6 = (SELECT id FROM budgets WHERE budget_code = 'BUD-5000-CATER-0006');
SET @bud7 = (SELECT id FROM budgets WHERE budget_code = 'BUD-5002-MAINT-0007');

-- Insert monthly allocations for Budget 1 (Office Supplies - even distribution)
INSERT INTO budget_lines (budget_id, month_year, allocated_amount, spent_amount, committed_amount, notes) VALUES
(@bud1, '2026-01-01', 20000.00, 18500.00, 1200.00, NULL),
(@bud1, '2026-02-01', 20000.00, 15200.00, 3500.00, NULL),
(@bud1, '2026-03-01', 20000.00, 12000.00, 0.00, NULL),
(@bud1, '2026-04-01', 20000.00, 8500.00, 2000.00, 'Holiday month - lower usage'),
(@bud1, '2026-05-01', 20000.00, 0.00, 0.00, NULL),
(@bud1, '2026-06-01', 20000.00, 0.00, 0.00, NULL),
(@bud1, '2026-07-01', 20000.00, 0.00, 0.00, NULL),
(@bud1, '2026-08-01', 20000.00, 0.00, 0.00, 'Holiday month'),
(@bud1, '2026-09-01', 20000.00, 0.00, 0.00, NULL),
(@bud1, '2026-10-01', 20000.00, 0.00, 0.00, NULL),
(@bud1, '2026-11-01', 20000.00, 0.00, 0.00, NULL),
(@bud1, '2026-12-01', 20000.00, 0.00, 0.00, 'Holiday month');

-- Insert monthly allocations for Budget 2 (Teaching Materials - academic focus)
INSERT INTO budget_lines (budget_id, month_year, allocated_amount, spent_amount, committed_amount, notes) VALUES
(@bud2, '2026-01-01', 40000.00, 38000.00, 0.00, 'Term 1 start - high demand'),
(@bud2, '2026-02-01', 30000.00, 25000.00, 4500.00, NULL),
(@bud2, '2026-03-01', 30000.00, 22000.00, 0.00, NULL),
(@bud2, '2026-04-01', 20000.00, 0.00, 0.00, 'Holiday'),
(@bud2, '2026-05-01', 40000.00, 0.00, 0.00, 'Term 2 start'),
(@bud2, '2026-06-01', 30000.00, 0.00, 0.00, NULL),
(@bud2, '2026-07-01', 30000.00, 0.00, 0.00, NULL),
(@bud2, '2026-08-01', 20000.00, 0.00, 0.00, 'Holiday'),
(@bud2, '2026-09-01', 40000.00, 0.00, 0.00, 'Term 3 start'),
(@bud2, '2026-10-01', 30000.00, 0.00, 0.00, NULL),
(@bud2, '2026-11-01', 30000.00, 0.00, 0.00, NULL),
(@bud2, '2026-12-01', 20000.00, 0.00, 0.00, 'Holiday');

-- Insert monthly allocations for Budget 3 (Utilities - consistent)
INSERT INTO budget_lines (budget_id, month_year, allocated_amount, spent_amount, committed_amount, notes) VALUES
(@bud3, '2026-01-01', 40000.00, 42500.00, 0.00, 'Over budget - AC usage'),
(@bud3, '2026-02-01', 40000.00, 38000.00, 0.00, NULL),
(@bud3, '2026-03-01', 40000.00, 35000.00, 0.00, NULL),
(@bud3, '2026-04-01', 40000.00, 28000.00, 0.00, 'Holiday - reduced'),
(@bud3, '2026-05-01', 40000.00, 0.00, 0.00, NULL),
(@bud3, '2026-06-01', 40000.00, 0.00, 0.00, NULL),
(@bud3, '2026-07-01', 40000.00, 0.00, 0.00, NULL),
(@bud3, '2026-08-01', 40000.00, 0.00, 0.00, NULL),
(@bud3, '2026-09-01', 40000.00, 0.00, 0.00, NULL),
(@bud3, '2026-10-01', 40000.00, 0.00, 0.00, NULL),
(@bud3, '2026-11-01', 40000.00, 0.00, 0.00, NULL),
(@bud3, '2026-12-01', 40000.00, 0.00, 0.00, NULL);

-- Insert monthly allocations for Budget 4 (Building Maintenance)
INSERT INTO budget_lines (budget_id, month_year, allocated_amount, spent_amount, committed_amount, notes) VALUES
(@bud4, '2026-01-01', 50000.00, 45000.00, 0.00, NULL),
(@bud4, '2026-02-01', 50000.00, 35000.00, 12000.00, NULL),
(@bud4, '2026-03-01', 50000.00, 28000.00, 0.00, NULL),
(@bud4, '2026-04-01', 50000.00, 55000.00, 0.00, 'Major repairs during holiday'),
(@bud4, '2026-05-01', 50000.00, 0.00, 0.00, NULL),
(@bud4, '2026-06-01', 50000.00, 0.00, 0.00, NULL),
(@bud4, '2026-07-01', 50000.00, 0.00, 0.00, NULL),
(@bud4, '2026-08-01', 50000.00, 0.00, 0.00, NULL),
(@bud4, '2026-09-01', 50000.00, 0.00, 0.00, NULL),
(@bud4, '2026-10-01', 50000.00, 0.00, 0.00, NULL),
(@bud4, '2026-11-01', 50000.00, 0.00, 0.00, NULL),
(@bud4, '2026-12-01', 50000.00, 0.00, 0.00, NULL);

-- Insert monthly allocations for Budget 5 (Transport - variable)
INSERT INTO budget_lines (budget_id, month_year, allocated_amount, spent_amount, committed_amount, notes) VALUES
(@bud5, '2026-01-01', 70000.00, 65000.00, 0.00, NULL),
(@bud5, '2026-02-01', 70000.00, 58000.00, 8000.00, NULL),
(@bud5, '2026-03-01', 70000.00, 52000.00, 0.00, NULL),
(@bud5, '2026-04-01', 40000.00, 25000.00, 0.00, 'Holiday - reduced trips'),
(@bud5, '2026-05-01', 70000.00, 0.00, 0.00, NULL),
(@bud5, '2026-06-01', 70000.00, 0.00, 0.00, NULL),
(@bud5, '2026-07-01', 70000.00, 0.00, 0.00, NULL),
(@bud5, '2026-08-01', 40000.00, 0.00, 0.00, 'Holiday'),
(@bud5, '2026-09-01', 70000.00, 0.00, 0.00, NULL),
(@bud5, '2026-10-01', 70000.00, 0.00, 0.00, NULL),
(@bud5, '2026-11-01', 70000.00, 0.00, 0.00, NULL),
(@bud5, '2026-12-01', 40000.00, 0.00, 0.00, 'Holiday');

-- Insert monthly allocations for Budget 6 (Catering - pending approval)
INSERT INTO budget_lines (budget_id, month_year, allocated_amount, spent_amount, committed_amount, notes) VALUES
(@bud6, '2026-01-01', 120000.00, 0.00, 0.00, NULL),
(@bud6, '2026-02-01', 120000.00, 0.00, 0.00, NULL),
(@bud6, '2026-03-01', 120000.00, 0.00, 0.00, NULL),
(@bud6, '2026-04-01', 60000.00, 0.00, 0.00, 'Holiday'),
(@bud6, '2026-05-01', 120000.00, 0.00, 0.00, NULL),
(@bud6, '2026-06-01', 120000.00, 0.00, 0.00, NULL),
(@bud6, '2026-07-01', 120000.00, 0.00, 0.00, NULL),
(@bud6, '2026-08-01', 60000.00, 0.00, 0.00, 'Holiday'),
(@bud6, '2026-09-01', 120000.00, 0.00, 0.00, NULL),
(@bud6, '2026-10-01', 120000.00, 0.00, 0.00, NULL),
(@bud6, '2026-11-01', 120000.00, 0.00, 0.00, NULL),
(@bud6, '2026-12-01', 60000.00, 0.00, 0.00, 'Holiday');

-- Insert monthly allocations for Budget 7 (Maintenance Supplies - draft)
INSERT INTO budget_lines (budget_id, month_year, allocated_amount, spent_amount, committed_amount, notes) VALUES
(@bud7, '2026-01-01', 15000.00, 0.00, 0.00, NULL),
(@bud7, '2026-02-01', 15000.00, 0.00, 0.00, NULL),
(@bud7, '2026-03-01', 15000.00, 0.00, 0.00, NULL),
(@bud7, '2026-04-01', 15000.00, 0.00, 0.00, NULL),
(@bud7, '2026-05-01', 15000.00, 0.00, 0.00, NULL),
(@bud7, '2026-06-01', 15000.00, 0.00, 0.00, NULL),
(@bud7, '2026-07-01', 15000.00, 0.00, 0.00, NULL),
(@bud7, '2026-08-01', 15000.00, 0.00, 0.00, NULL),
(@bud7, '2026-09-01', 15000.00, 0.00, 0.00, NULL),
(@bud7, '2026-10-01', 15000.00, 0.00, 0.00, NULL),
(@bud7, '2026-11-01', 15000.00, 0.00, 0.00, NULL),
(@bud7, '2026-12-01', 15000.00, 0.00, 0.00, NULL);

-- Insert some budget transactions (actuals)
INSERT INTO budget_transactions (budget_id, budget_line_id, transaction_type, entity_type, entity_id, amount, transaction_date, description, created_by)
SELECT b.id, bl.id, 'actual', 'supplier_invoice', 1, bl.spent_amount, bl.month_year, 'Monthly expenses', 1
FROM budgets b
JOIN budget_lines bl ON b.id = bl.budget_id
WHERE bl.spent_amount > 0;

-- Insert some commitments
INSERT INTO budget_transactions (budget_id, budget_line_id, transaction_type, entity_type, entity_id, amount, transaction_date, description, created_by)
SELECT b.id, bl.id, 'commitment', 'purchase_order', 1, bl.committed_amount, bl.month_year, 'Pending PO', 1
FROM budgets b
JOIN budget_lines bl ON b.id = bl.budget_id
WHERE bl.committed_amount > 0;

-- Insert a pending overrun request
INSERT INTO budget_overruns (budget_id, budget_line_id, entity_type, entity_id, overrun_amount, budget_available, requested_amount, reason, status, requested_by)
SELECT @bud3, bl.id, 'supplier_invoice', 101, 2500.00, -2500.00, 45000.00,
       'Unexpected increase in electricity rates due to tariff changes. Essential for school operations.',
       'pending', 1
FROM budget_lines bl WHERE bl.budget_id = @bud3 AND bl.month_year = '2026-01-01';

-- Insert a pending budget revision
INSERT INTO budget_revisions (budget_id, revision_number, revision_type, previous_annual_amount, new_annual_amount, reason, status, requested_by)
VALUES (@bud4, 1, 'increase', 600000.00, 720000.00, 'Additional maintenance work required for new science block. Need to increase monthly allocations by 10,000 each.', 'pending', 1);

-- Re-enable FK checks
SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Budget sample data inserted successfully!' AS status;
