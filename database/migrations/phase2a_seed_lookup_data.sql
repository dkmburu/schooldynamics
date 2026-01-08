-- Parent Portal Phase 2A - Seed Lookup Data
-- Created: 2025-01-07
-- Description: Seeds all lookup tables with initial data

-- Notification Types
INSERT INTO notification_types (code, name, description, icon) VALUES
('general', 'General', 'General school announcements', 'ti-bell'),
('academic', 'Academic', 'Academic and learning related', 'ti-book'),
('fees', 'Fees & Payments', 'Financial and fee-related', 'ti-currency-dollar'),
('transport', 'Transport', 'Transport and bus-related', 'ti-bus'),
('health', 'Health & Medical', 'Health and medical notifications', 'ti-heartbeat'),
('event', 'Events', 'School events and activities', 'ti-calendar-event'),
('feedback_request', 'Feedback Request', 'Survey and feedback requests', 'ti-message-circle')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Notification Severity Levels
INSERT INTO notification_severity_levels (code, name, color, sort_order) VALUES
('info', 'Information', 'blue', 1),
('success', 'Success', 'green', 2),
('warning', 'Warning', 'orange', 3),
('urgent', 'Urgent', 'red', 4)
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Notification Scopes
INSERT INTO notification_scopes (code, name) VALUES
('school', 'School-wide'),
('grade', 'Grade/Class Level'),
('student', 'Individual Student')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Notification Action Types
INSERT INTO notification_action_types (code, name, description) VALUES
('rsvp', 'RSVP', 'Respond to event invitation'),
('acknowledge', 'Acknowledge', 'Acknowledge receipt'),
('payment', 'Make Payment', 'Proceed to payment'),
('feedback', 'Provide Feedback', 'Complete survey/feedback'),
('other', 'Other Action', 'Custom action required')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Contact Types
INSERT INTO contact_types (code, name, description, icon, sort_order) VALUES
('main_office', 'Main Office', 'School main reception', 'ti-building', 1),
('principal', 'Principal/Head Teacher', 'School principal office', 'ti-tie', 2),
('accounts', 'Accounts Department', 'Finance and fees office', 'ti-calculator', 3),
('medical', 'Medical/Health Office', 'School nurse and medical services', 'ti-first-aid-kit', 4),
('transport', 'Transport Coordinator', 'School transport office', 'ti-bus', 5),
('counseling', 'Counseling Services', 'School counselor', 'ti-mood-smile', 6),
('emergency', 'Emergency Contact', '24/7 emergency line', 'ti-alert-triangle', 7),
('other', 'Other', 'Other departments', 'ti-phone', 8)
ON DUPLICATE KEY UPDATE name=VALUES(name);
