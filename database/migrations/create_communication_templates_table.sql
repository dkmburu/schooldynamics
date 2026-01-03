-- =====================================================
-- Communication Templates Table
-- Centralized template management for ALL system communications
-- (SMS, Email, WhatsApp, In-App Notifications)
-- =====================================================

CREATE TABLE IF NOT EXISTS communication_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Template Identification
    category VARCHAR(50) NOT NULL COMMENT 'Category: authorization, notifications, reminders, etc.',
    template_code VARCHAR(100) NOT NULL UNIQUE COMMENT 'Unique code: data_consent_request, payment_reminder, etc.',
    template_name VARCHAR(200) NOT NULL COMMENT 'Human-readable name',
    description TEXT COMMENT 'What this template is used for',

    -- Multi-Channel Support
    channels JSON NOT NULL COMMENT 'Array of supported channels: ["sms", "email", "whatsapp"]',

    -- Message Content
    subject VARCHAR(500) COMMENT 'Subject line (for email)',
    sms_body TEXT COMMENT 'SMS message template (160 chars recommended)',
    email_body TEXT COMMENT 'Email HTML/text body template',
    whatsapp_body TEXT COMMENT 'WhatsApp message template',

    -- Template Variables
    variables JSON COMMENT 'Array of available variables: ["{{school_name}}", "{{student_name}}", etc.]',

    -- Authorization-Specific Settings
    requires_authorization BOOLEAN DEFAULT 0 COMMENT 'Is this an authorization request template?',
    authorization_type VARCHAR(50) COMMENT 'Type: data_consent, medical_consent, photo_consent, etc.',
    validity_days INT DEFAULT 30 COMMENT 'How many days is the authorization valid?',

    -- Template Settings
    is_system BOOLEAN DEFAULT 0 COMMENT 'System template (cannot be deleted)',
    is_active BOOLEAN DEFAULT 1 COMMENT 'Is this template active?',

    -- Multi-tenant Support
    campus_id INT COMMENT 'NULL = available to all campuses, or specific campus only',

    -- Audit Fields
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT COMMENT 'User ID who created this template',
    updated_by INT COMMENT 'User ID who last updated this template',

    -- Indexes
    INDEX idx_category (category),
    INDEX idx_template_code (template_code),
    INDEX idx_authorization_type (authorization_type),
    INDEX idx_campus_id (campus_id),
    INDEX idx_is_active (is_active)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Centralized communication template management';

-- =====================================================
-- Insert Default Authorization Templates
-- =====================================================

INSERT INTO communication_templates (
    category, template_code, template_name, description,
    channels, subject, sms_body, email_body, whatsapp_body,
    variables, requires_authorization, authorization_type,
    validity_days, is_system, is_active
) VALUES
(
    'authorization',
    'data_consent_request',
    'Data Consent Authorization Request',
    'Request parent/guardian authorization to process and store student data',
    '["sms", "email", "whatsapp"]',
    'Authorization Required: {{school_name}} Data Consent',
    'Dear {{guardian_name}}, {{school_name}} requires your authorization to process data for {{student_name}}. Code: {{code}}. Click: {{link}} or call us with code. Valid {{validity_days}} days.',
    '<h2>Authorization Request</h2><p>Dear {{guardian_name}},</p><p>{{school_name}} requires your authorization to process and store data for <strong>{{student_name}}</strong>.</p><p><strong>Verification Code:</strong> {{code}}</p><p><a href="{{link}}" style="background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;">Click Here to Authorize</a></p><p>Or call us and provide the code: <strong>{{code}}</strong></p><p>This request expires in {{validity_days}} days.</p><p>Thank you,<br>{{school_name}}</p>',
    'Dear {{guardian_name}}, {{school_name}} requires your authorization to process data for {{student_name}}. Code: {{code}}. Link: {{link}} Valid {{validity_days}} days.',
    '["{{school_name}}", "{{guardian_name}}", "{{student_name}}", "{{code}}", "{{link}}", "{{validity_days}}"]',
    1,
    'data_consent',
    30,
    1,
    1
),
(
    'authorization',
    'photo_consent_request',
    'Photo/Media Consent Authorization',
    'Request authorization to use student photos/videos in school media',
    '["sms", "email", "whatsapp"]',
    'Photo Consent Required: {{school_name}}',
    'Dear {{guardian_name}}, {{school_name}} requests consent to use photos/videos of {{student_name}} in school media. Code: {{code}}. Link: {{link}} Valid {{validity_days}} days.',
    '<h2>Photo/Media Consent Request</h2><p>Dear {{guardian_name}},</p><p>{{school_name}} requests your consent to use photos and videos of <strong>{{student_name}}</strong> in school publications, website, and social media.</p><p><strong>Verification Code:</strong> {{code}}</p><p><a href="{{link}}" style="background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;">Click Here to Authorize</a></p><p>Or call us and provide the code: <strong>{{code}}</strong></p><p>This request expires in {{validity_days}} days.</p><p>Thank you,<br>{{school_name}}</p>',
    'Dear {{guardian_name}}, {{school_name}} requests consent to use photos/videos of {{student_name}} in school media. Code: {{code}}. Link: {{link}} Valid {{validity_days}} days.',
    '["{{school_name}}", "{{guardian_name}}", "{{student_name}}", "{{code}}", "{{link}}", "{{validity_days}}"]',
    1,
    'photo_consent',
    365,
    1,
    1
),
(
    'authorization',
    'medical_consent_request',
    'Medical Treatment Consent',
    'Request authorization for emergency medical treatment',
    '["sms", "email", "whatsapp"]',
    'Medical Consent Required: {{school_name}}',
    'Dear {{guardian_name}}, {{school_name}} requires medical consent for {{student_name}}. Code: {{code}}. Link: {{link}} Valid {{validity_days}} days.',
    '<h2>Medical Treatment Consent</h2><p>Dear {{guardian_name}},</p><p>{{school_name}} requires your consent to administer emergency medical treatment to <strong>{{student_name}}</strong> if needed.</p><p><strong>Verification Code:</strong> {{code}}</p><p><a href="{{link}}" style="background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;">Click Here to Authorize</a></p><p>Or call us and provide the code: <strong>{{code}}</strong></p><p>This request expires in {{validity_days}} days.</p><p>Thank you,<br>{{school_name}}</p>',
    'Dear {{guardian_name}}, {{school_name}} requires medical consent for {{student_name}}. Code: {{code}}. Link: {{link}} Valid {{validity_days}} days.',
    '["{{school_name}}", "{{guardian_name}}", "{{student_name}}", "{{code}}", "{{link}}", "{{validity_days}}"]',
    1,
    'medical_consent',
    365,
    1,
    1
);
