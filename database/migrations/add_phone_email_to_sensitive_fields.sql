-- ============================================================
-- Add Phone and Email to Sensitive Fields
-- Run Date: 2026-01-06
-- ============================================================

-- Add phone and email to sensitive fields for guardians and applicant_guardians
INSERT IGNORE INTO sensitive_fields (entity_type, field_name, display_name, requires_reason) VALUES
-- Applicant guardians
('applicant_guardians', 'phone', 'Phone Number', 0),
('applicant_guardians', 'email', 'Email Address', 0),

-- Main guardians table
('guardians', 'phone', 'Phone Number', 0),
('guardians', 'email', 'Email Address', 0),

-- Students
('students', 'phone', 'Phone Number', 0),
('students', 'email', 'Email Address', 0),

-- Staff
('staff', 'phone', 'Phone Number', 0),
('staff', 'email', 'Email Address', 0);

-- Verify
-- SELECT * FROM sensitive_fields ORDER BY entity_type, field_name;
