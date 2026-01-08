-- Parent Portal Phase 2A - Test Data
-- Created: 2025-01-07
-- Description: Sample notifications and contacts for testing

-- Get IDs from existing tables
-- Since this is a tenant database, school_id is typically the tenant_id (1)
SET @school_id = 1;
SET @parent_id = (SELECT id FROM parent_accounts WHERE status = 'active' LIMIT 1);
SET @student_id = (SELECT id FROM students LIMIT 1);
SET @grade_id = (SELECT id FROM grades LIMIT 1);

-- Get lookup IDs
SET @type_general = (SELECT id FROM notification_types WHERE code = 'general');
SET @type_academic = (SELECT id FROM notification_types WHERE code = 'academic');
SET @type_fees = (SELECT id FROM notification_types WHERE code = 'fees');
SET @type_event = (SELECT id FROM notification_types WHERE code = 'event');
SET @type_health = (SELECT id FROM notification_types WHERE code = 'health');
SET @type_feedback = (SELECT id FROM notification_types WHERE code = 'feedback_request');

SET @severity_info = (SELECT id FROM notification_severity_levels WHERE code = 'info');
SET @severity_success = (SELECT id FROM notification_severity_levels WHERE code = 'success');
SET @severity_warning = (SELECT id FROM notification_severity_levels WHERE code = 'warning');
SET @severity_urgent = (SELECT id FROM notification_severity_levels WHERE code = 'urgent');

SET @scope_school = (SELECT id FROM notification_scopes WHERE code = 'school');
SET @scope_grade = (SELECT id FROM notification_scopes WHERE code = 'grade');
SET @scope_student = (SELECT id FROM notification_scopes WHERE code = 'student');

SET @action_rsvp = (SELECT id FROM notification_action_types WHERE code = 'rsvp');
SET @action_acknowledge = (SELECT id FROM notification_action_types WHERE code = 'acknowledge');
SET @action_payment = (SELECT id FROM notification_action_types WHERE code = 'payment');
SET @action_feedback = (SELECT id FROM notification_action_types WHERE code = 'feedback');

-- Only insert test data if we have a parent account
INSERT INTO parent_notifications
(parent_account_id, student_id, grade_id, notification_type_id, notification_scope_id, severity_level_id,
 title, message, requires_action, action_type_id, action_url, action_deadline, created_at)
SELECT
    @parent_id,
    NULL,
    NULL,
    @type_general,
    @scope_school,
    @severity_urgent,
    'Urgent: School Closure Tomorrow',
    'Due to heavy rains and flooding, the school will be closed tomorrow, January 8th, 2025. All students are requested to stay home. Classes will resume on January 9th. Stay safe!',
    TRUE,
    @action_acknowledge,
    '/parent/notifications/1',
    DATE_ADD(NOW(), INTERVAL 6 HOUR),
    NOW()
WHERE @parent_id IS NOT NULL;

INSERT INTO parent_notifications
(parent_account_id, student_id, grade_id, notification_type_id, notification_scope_id, severity_level_id,
 title, message, requires_action, action_type_id, action_url, action_deadline, created_at)
SELECT
    @parent_id,
    NULL,
    @grade_id,
    @type_event,
    @scope_grade,
    @severity_warning,
    'Parent-Teacher Meeting - January 15th',
    'You are invited to attend the Grade Parent-Teacher meeting on January 15th, 2025 at 2:00 PM in the school hall. This is an opportunity to discuss your child''s progress and any concerns. Please RSVP by January 10th.',
    TRUE,
    @action_rsvp,
    '/parent/notifications/2',
    DATE_ADD(NOW(), INTERVAL 3 DAY),
    DATE_SUB(NOW(), INTERVAL 2 HOUR)
WHERE @parent_id IS NOT NULL;

INSERT INTO parent_notifications
(parent_account_id, student_id, grade_id, notification_type_id, notification_scope_id, severity_level_id,
 title, message, requires_action, action_type_id, action_url, action_deadline, created_at)
SELECT
    @parent_id,
    @student_id,
    NULL,
    @type_fees,
    @scope_student,
    @severity_warning,
    'Outstanding Fee Balance',
    'Your fee balance for Term 1, 2025 is Ksh 15,000. Please make payment by January 20th to avoid late payment penalties. You can pay via M-Pesa or bank transfer.',
    TRUE,
    @action_payment,
    CONCAT('/parent/child/', @student_id, '/fees'),
    DATE_ADD(NOW(), INTERVAL 13 DAY),
    DATE_SUB(NOW(), INTERVAL 5 HOUR)
WHERE @parent_id IS NOT NULL AND @student_id IS NOT NULL;

INSERT INTO parent_notifications
(parent_account_id, student_id, grade_id, notification_type_id, notification_scope_id, severity_level_id,
 title, message, requires_action, created_at)
SELECT
    @parent_id,
    @student_id,
    NULL,
    @type_academic,
    @scope_student,
    @severity_success,
    'Excellent Performance in Mathematics',
    'Congratulations! Your child scored 95% in the recent Mathematics assessment. They are showing excellent progress. Keep up the good work!',
    FALSE,
    DATE_SUB(NOW(), INTERVAL 1 DAY)
WHERE @parent_id IS NOT NULL AND @student_id IS NOT NULL;

INSERT INTO parent_notifications
(parent_account_id, student_id, grade_id, notification_type_id, notification_scope_id, severity_level_id,
 title, message, requires_action, created_at)
SELECT
    @parent_id,
    NULL,
    NULL,
    @type_general,
    @scope_school,
    @severity_info,
    'New School Calendar Released',
    'The school calendar for Term 1, 2025 has been published. You can view it on the parent portal or download a copy from the school website. Key dates include mid-term break (Feb 10-14) and end of term (April 4th).',
    FALSE,
    DATE_SUB(NOW(), INTERVAL 3 DAY)
WHERE @parent_id IS NOT NULL;

INSERT INTO parent_notifications
(parent_account_id, student_id, grade_id, notification_type_id, notification_scope_id, severity_level_id,
 title, message, requires_action, action_type_id, action_url, action_deadline, created_at)
SELECT
    @parent_id,
    NULL,
    NULL,
    @type_feedback,
    @scope_school,
    @severity_info,
    'Parent Feedback Survey - School Facilities',
    'We value your feedback! Please take 5 minutes to complete our survey about school facilities and services. Your input helps us improve the learning environment for all students.',
    TRUE,
    @action_feedback,
    '/parent/notifications/6',
    DATE_ADD(NOW(), INTERVAL 7 DAY),
    DATE_SUB(NOW(), INTERVAL 12 HOUR)
WHERE @parent_id IS NOT NULL;

INSERT INTO parent_notifications
(parent_account_id, student_id, grade_id, notification_type_id, notification_scope_id, severity_level_id,
 title, message, requires_action, created_at, read_at)
SELECT
    @parent_id,
    @student_id,
    NULL,
    @type_health,
    @scope_student,
    @severity_warning,
    'Medical Form Required',
    'Please update your child''s medical information form. We need current emergency contact details and any medical conditions we should be aware of.',
    FALSE,
    DATE_SUB(NOW(), INTERVAL 5 DAY),
    DATE_SUB(NOW(), INTERVAL 4 DAY)
WHERE @parent_id IS NOT NULL AND @student_id IS NOT NULL;

-- Sample School Contacts
SET @contact_main = (SELECT id FROM contact_types WHERE code = 'main_office');
SET @contact_principal = (SELECT id FROM contact_types WHERE code = 'principal');
SET @contact_accounts = (SELECT id FROM contact_types WHERE code = 'accounts');
SET @contact_medical = (SELECT id FROM contact_types WHERE code = 'medical');
SET @contact_transport = (SELECT id FROM contact_types WHERE code = 'transport');
SET @contact_emergency = (SELECT id FROM contact_types WHERE code = 'emergency');

-- Main Office
INSERT INTO school_contacts
(school_id, contact_type_id, department_name, contact_person, phone, email,
 office_location, available_hours, is_emergency, display_order, is_active)
SELECT
    @school_id,
    @contact_main,
    'Main Office',
    'Reception Desk',
    '+254 712 345 001',
    'info@schooldynamics.ac.ke',
    'Administration Block, Ground Floor',
    'Mon-Fri: 8:00 AM - 5:00 PM',
    FALSE,
    1,
    TRUE
WHERE @school_id IS NOT NULL;

-- Principal's Office
INSERT INTO school_contacts
(school_id, contact_type_id, department_name, contact_person, phone, email,
 office_location, available_hours, is_emergency, display_order, is_active)
SELECT
    @school_id,
    @contact_principal,
    'Principal Office',
    'Dr. Mary Wanjiku',
    '+254 712 345 002',
    'principal@schooldynamics.ac.ke',
    'Administration Block, 2nd Floor',
    'Mon-Fri: 9:00 AM - 4:00 PM (By Appointment)',
    FALSE,
    2,
    TRUE
WHERE @school_id IS NOT NULL;

-- Accounts Department
INSERT INTO school_contacts
(school_id, contact_type_id, department_name, contact_person, phone, email,
 office_location, available_hours, is_emergency, display_order, is_active)
SELECT
    @school_id,
    @contact_accounts,
    'Accounts Department',
    'John Kamau',
    '+254 712 345 003',
    'accounts@schooldynamics.ac.ke',
    'Administration Block, 1st Floor, Room 105',
    'Mon-Fri: 8:00 AM - 5:00 PM, Sat: 9:00 AM - 1:00 PM',
    FALSE,
    3,
    TRUE
WHERE @school_id IS NOT NULL;

-- Medical/Health Office
INSERT INTO school_contacts
(school_id, contact_type_id, department_name, contact_person, phone, email,
 office_location, available_hours, is_emergency, display_order, is_active)
SELECT
    @school_id,
    @contact_medical,
    'Medical Office',
    'Nurse Jane Achieng',
    '+254 712 345 004',
    'medical@schooldynamics.ac.ke',
    'Medical Center, Near Main Gate',
    'Mon-Fri: 7:30 AM - 5:30 PM',
    FALSE,
    4,
    TRUE
WHERE @school_id IS NOT NULL;

-- Transport Coordinator
INSERT INTO school_contacts
(school_id, contact_type_id, department_name, contact_person, phone, email,
 office_location, available_hours, is_emergency, display_order, is_active)
SELECT
    @school_id,
    @contact_transport,
    'Transport Office',
    'Peter Omondi',
    '+254 712 345 005',
    'transport@schooldynamics.ac.ke',
    'Transport Office, Near Bus Park',
    'Mon-Fri: 6:30 AM - 6:00 PM',
    FALSE,
    5,
    TRUE
WHERE @school_id IS NOT NULL;

-- Emergency Contact
INSERT INTO school_contacts
(school_id, contact_type_id, department_name, contact_person, phone, email,
 office_location, available_hours, is_emergency, is_24_7, display_order, is_active)
SELECT
    @school_id,
    @contact_emergency,
    'Emergency Hotline',
    'Security & Emergency Response',
    '+254 712 345 999',
    'security@schooldynamics.ac.ke',
    'Security Office, Main Gate',
    '24/7 (Always Available)',
    TRUE,
    TRUE,
    6,
    TRUE
WHERE @school_id IS NOT NULL;
