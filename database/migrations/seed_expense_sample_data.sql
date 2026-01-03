-- =====================================================
-- SAMPLE DATA FOR EXPENSE TRACKING
-- =====================================================

-- 1. SUPPLIERS
INSERT INTO suppliers (supplier_code, name, category_id, contact_person, email, phone, address, tax_pin, payment_terms, bank_name, bank_branch, bank_account, credit_limit, current_balance, is_active, created_by) VALUES
('SUP-0001', 'Kenya Power & Lighting', 3, 'Customer Care', 'billing@kplc.co.ke', '0800-723253', 'Stima Plaza, Nairobi', 'P051234567A', 30, 'KCB Bank', 'Moi Avenue', '1234567890', 500000.00, 45000.00, 1, 1),
('SUP-0002', 'Nairobi Water Company', 3, 'Billing Dept', 'billing@nairobiwater.co.ke', '020-2711872', 'NWSC Building, Nairobi', 'P051234568B', 30, 'Equity Bank', 'City Hall', '0987654321', 200000.00, 12500.00, 1, 1),
('SUP-0003', 'Safaricom PLC', 3, 'Corporate Sales', 'corporate@safaricom.co.ke', '0722-000000', 'Safaricom House, Nairobi', 'P051234569C', 14, 'NCBA Bank', 'Westlands', '1122334455', 100000.00, 8750.00, 1, 1),
('SUP-0004', 'Tuskys Supermarket', 5, 'Wholesale Dept', 'wholesale@tuskys.com', '020-2711234', 'Tuskys HQ, Nairobi', 'P051234570D', 7, 'Cooperative Bank', 'Industrial Area', '5566778899', 150000.00, 0.00, 1, 1),
('SUP-0005', 'Office Supplies Ltd', 1, 'John Kamau', 'sales@officesupplies.co.ke', '0733-445566', 'Industrial Area, Nairobi', 'P051234571E', 30, 'Stanbic Bank', 'Kenyatta Ave', '6677889900', 100000.00, 25000.00, 1, 1),
('SUP-0006', 'ABC Repairs & Maintenance', 4, 'Peter Ochieng', 'info@abcrepairs.co.ke', '0711-223344', 'Ngong Road, Nairobi', 'P051234572F', 14, 'DTB Bank', 'Ngong Road', '4455667788', 75000.00, 15000.00, 1, 1),
('SUP-0007', 'School Books Distributors', 1, 'Mary Wanjiku', 'orders@schoolbooks.co.ke', '0722-998877', 'Moi Avenue, Nairobi', 'P051234573G', 45, 'KCB Bank', 'Kimathi Street', '9988776655', 500000.00, 150000.00, 1, 1),
('SUP-0008', 'Fresh Foods Kenya', 5, 'James Mwangi', 'supply@freshfoods.co.ke', '0700-112233', 'Wakulima Market', 'P051234574H', 7, 'Equity Bank', 'City Market', '3344556677', 80000.00, 0.00, 1, 1);

-- 2. PURCHASE ORDERS
INSERT INTO purchase_orders (po_number, supplier_id, order_date, expected_delivery_date, status, subtotal, tax_amount, total_amount, notes, prepared_by, approved_by, approved_at) VALUES
('PO-2026-0001', 7, '2026-01-02', '2026-01-10', 'approved', 120000.00, 19200.00, 139200.00, 'Term 1 textbooks order', 1, 1, '2026-01-02 10:00:00'),
('PO-2026-0002', 5, '2026-01-02', '2026-01-05', 'received', 35000.00, 5600.00, 40600.00, 'Stationery for admin office', 1, 1, '2026-01-02 11:00:00'),
('PO-2026-0003', 4, '2026-01-03', '2026-01-03', 'received', 25000.00, 4000.00, 29000.00, 'Kitchen supplies for week 1', 1, 1, '2026-01-03 08:00:00'),
('PO-2026-0004', 6, '2026-01-03', '2026-01-06', 'sent', 45000.00, 7200.00, 52200.00, 'Plumbing repairs - washrooms', 1, 1, '2026-01-03 09:00:00'),
('PO-2026-0005', 8, '2026-01-03', NULL, 'pending_approval', 18000.00, 2880.00, 20880.00, 'Fresh vegetables and fruits', 1, NULL, NULL),
('PO-2026-0006', 5, '2026-01-03', NULL, 'draft', 12000.00, 1920.00, 13920.00, 'Printer cartridges', 1, NULL, NULL);

-- 3. PURCHASE ORDER LINES
INSERT INTO purchase_order_lines (purchase_order_id, line_number, item_description, quantity, unit, unit_price, tax_rate, tax_amount, line_total) VALUES
(1, 1, 'Mathematics Textbook Grade 8', 50, 'pcs', 800.00, 16, 6400.00, 46400.00),
(1, 2, 'English Textbook Grade 8', 50, 'pcs', 750.00, 16, 6000.00, 43500.00),
(1, 3, 'Science Textbook Grade 8', 50, 'pcs', 850.00, 16, 6800.00, 49300.00),
(2, 1, 'A4 Paper Reams (500 sheets)', 20, 'reams', 550.00, 16, 1760.00, 12760.00),
(2, 2, 'Ball Pens Box (50 pcs)', 10, 'boxes', 800.00, 16, 1280.00, 9280.00),
(2, 3, 'Staplers Heavy Duty', 5, 'pcs', 1500.00, 16, 1200.00, 8700.00),
(2, 4, 'Box Files', 20, 'pcs', 350.00, 16, 1120.00, 8120.00),
(3, 1, 'Rice (50kg bag)', 5, 'bags', 2500.00, 16, 2000.00, 14500.00),
(3, 2, 'Cooking Oil (20L)', 3, 'jerricans', 3500.00, 16, 1680.00, 12180.00),
(4, 1, 'Toilet cisterns replacement', 10, 'pcs', 3500.00, 16, 5600.00, 40600.00),
(4, 2, 'Labor charges', 1, 'lot', 10000.00, 16, 1600.00, 11600.00);

-- 4. GOODS RECEIVED NOTES
INSERT INTO goods_received_notes (grn_number, purchase_order_id, supplier_id, received_date, delivery_note_number, status, notes, received_by, confirmed_by, confirmed_at) VALUES
('GRN-2026-0001', 2, 5, '2026-01-04', 'DN-OSL-4521', 'confirmed', 'All items received in good condition', 1, 1, '2026-01-04 14:00:00'),
('GRN-2026-0002', 3, 4, '2026-01-03', 'DN-TUS-8832', 'confirmed', 'Received same day', 1, 1, '2026-01-03 15:00:00');

-- 5. GRN LINES
INSERT INTO grn_lines (grn_id, po_line_id, quantity_received, quantity_accepted, quantity_rejected, rejection_reason) VALUES
(1, 4, 20, 20, 0, NULL),
(1, 5, 10, 10, 0, NULL),
(1, 6, 5, 5, 0, NULL),
(1, 7, 20, 20, 0, NULL),
(2, 8, 5, 5, 0, NULL),
(2, 9, 3, 3, 0, NULL);

-- 6. SUPPLIER INVOICES
INSERT INTO supplier_invoices (invoice_number, internal_ref, supplier_id, purchase_order_id, grn_id, invoice_date, due_date, subtotal, tax_amount, total_amount, amount_paid, balance, status, notes, created_by, approved_by, approved_at) VALUES
('INV-KPLC-JAN26', 'SINV-2026-0001', 1, NULL, NULL, '2026-01-01', '2026-01-31', 38793.10, 6206.90, 45000.00, 0.00, 45000.00, 'approved', 'Electricity bill January 2026', 1, 1, '2026-01-02 09:00:00'),
('INV-NWC-JAN26', 'SINV-2026-0002', 2, NULL, NULL, '2026-01-01', '2026-01-31', 10775.86, 1724.14, 12500.00, 0.00, 12500.00, 'approved', 'Water bill January 2026', 1, 1, '2026-01-02 09:15:00'),
('INV-SAF-DEC25', 'SINV-2026-0003', 3, NULL, NULL, '2025-12-25', '2026-01-08', 7543.10, 1206.90, 8750.00, 0.00, 8750.00, 'pending', 'Internet and mobile services Dec', 1, NULL, NULL),
('INV-OSL-0045', 'SINV-2026-0004', 5, 2, 1, '2026-01-04', '2026-02-03', 35000.00, 5600.00, 40600.00, 15000.00, 25600.00, 'partial', 'Stationery supplies', 1, 1, '2026-01-04 16:00:00'),
('INV-TUS-8832', 'SINV-2026-0005', 4, 3, 2, '2026-01-03', '2026-01-10', 25000.00, 4000.00, 29000.00, 29000.00, 0.00, 'paid', 'Kitchen supplies', 1, 1, '2026-01-03 16:00:00'),
('INV-SBD-2026-01', 'SINV-2026-0006', 7, 1, NULL, '2026-01-02', '2026-02-16', 120000.00, 19200.00, 139200.00, 0.00, 139200.00, 'approved', 'Term 1 textbooks', 1, 1, '2026-01-02 12:00:00');

-- 7. SUPPLIER PAYMENTS
INSERT INTO supplier_payments (payment_number, supplier_id, payment_date, payment_method, amount, reference_number, notes, status, prepared_by, approved_by, approved_at, paid_at) VALUES
('SPAY-2026-0001', 4, '2026-01-03', 'mpesa', 29000.00, 'SGB4K5L6M7N8', 'Full payment for kitchen supplies', 'paid', 1, 1, '2026-01-03 16:30:00', '2026-01-03 16:35:00'),
('SPAY-2026-0002', 5, '2026-01-04', 'bank_transfer', 15000.00, 'TRF-2026-0045', 'Partial payment for stationery', 'paid', 1, 1, '2026-01-04 17:00:00', '2026-01-04 17:05:00'),
('SPAY-2026-0003', 1, '2026-01-03', 'cheque', 25000.00, 'CHQ-005521', 'Partial electricity payment', 'approved', 1, 1, '2026-01-03 10:00:00', NULL),
('SPAY-2026-0004', 6, '2026-01-03', 'cash', 15000.00, NULL, 'Advance for plumbing work', 'paid', 1, 1, '2026-01-03 11:00:00', '2026-01-03 11:05:00');

-- 8. PAYMENT ALLOCATIONS
INSERT INTO supplier_payment_allocations (payment_id, supplier_invoice_id, amount) VALUES
(1, 5, 29000.00),
(2, 4, 15000.00);

SELECT 'Sample data inserted successfully!' AS status;
