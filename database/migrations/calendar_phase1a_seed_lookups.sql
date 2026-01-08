-- Calendar Module Phase 1A: Seed Lookup Tables
-- Created: 2025-01-07
-- Description: Seed data for term date types, holiday types

-- =============================================================================
-- TERM DATE TYPES
-- =============================================================================

INSERT INTO term_date_types (code, name, category, color, icon, description, sort_order) VALUES
-- Academic Dates
('term_opening', 'Term Opening', 'academic', '#2563eb', 'ti-school', 'First day of term', 10),
('term_closing', 'Term Closing', 'academic', '#2563eb', 'ti-school-off', 'Last day of term', 20),
('mid_term_break_start', 'Mid-Term Break Start', 'break', '#06b6d4', 'ti-calendar-pause', 'Mid-term break begins', 30),
('mid_term_break_end', 'Mid-Term Break End', 'break', '#06b6d4', 'ti-calendar-check', 'Mid-term break ends', 40),
('visiting_day', 'Visiting Day', 'academic', '#8b5cf6', 'ti-users', 'Parents visit students', 50),
('report_cards', 'Report Card Distribution', 'academic', '#f59e0b', 'ti-file-certificate', 'Distribution of academic reports', 60),
('graduation_day', 'Graduation Day', 'academic', '#10b981', 'ti-award', 'Student graduation ceremony', 70),
('orientation_day', 'Orientation Day', 'academic', '#3b82f6', 'ti-info-circle', 'New student orientation', 80),

-- Exam Dates
('mock_exams_start', 'Mock Exams Start', 'exam', '#dc2626', 'ti-file-text', 'Mock examinations begin', 100),
('mock_exams_end', 'Mock Exams End', 'exam', '#dc2626', 'ti-file-check', 'Mock examinations end', 110),
('midterm_exams_start', 'Mid-Term Exams Start', 'exam', '#ea580c', 'ti-clipboard-text', 'Mid-term assessments begin', 120),
('midterm_exams_end', 'Mid-Term Exams End', 'exam', '#ea580c', 'ti-clipboard-check', 'Mid-term assessments end', 130),
('endterm_exams_start', 'End-Term Exams Start', 'exam', '#b91c1c', 'ti-clipboard-list', 'Final term exams begin', 140),
('endterm_exams_end', 'End-Term Exams End', 'exam', '#b91c1c', 'ti-clipboard-check', 'Final term exams end', 150),
('kcpe_exams', 'KCPE Examinations', 'exam', '#7c2d12', 'ti-certificate', 'Kenya Certificate of Primary Education', 160),
('kcse_exams', 'KCSE Examinations', 'exam', '#7c2d12', 'ti-certificate', 'Kenya Certificate of Secondary Education', 170),

-- Holiday Dates
('school_holiday', 'School Holiday', 'holiday', '#10b981', 'ti-sun', 'School-specific holiday', 200),
('half_term', 'Half-Term Break', 'holiday', '#14b8a6', 'ti-beach', 'Short break within term', 210),
('teachers_holiday', 'Teachers Professional Day', 'holiday', '#06b6d4', 'ti-briefcase', 'Staff development day, no students', 220),

-- Other Important Dates
('inter_house_comp', 'Inter-House Competition', 'academic', '#ec4899', 'ti-trophy', 'Inter-house sports/academic competition', 300),
('prize_giving', 'Prize Giving Day', 'academic', '#f59e0b', 'ti-award', 'Academic excellence awards', 310),
('open_day', 'School Open Day', 'academic', '#8b5cf6', 'ti-door-enter', 'Prospective parent visits', 320)
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- =============================================================================
-- NATIONAL HOLIDAY TYPES
-- =============================================================================

INSERT INTO national_holiday_types (code, name, description) VALUES
('public_holiday', 'Public Holiday', 'National public holiday'),
('religious_holiday', 'Religious Holiday', 'Religious observance holiday'),
('commemoration', 'Commemoration Day', 'National commemoration or memorial day'),
('celebration', 'National Celebration', 'National celebration or festival'),
('civic_holiday', 'Civic Holiday', 'Civic or administrative holiday')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- =============================================================================
-- KENYA NATIONAL HOLIDAYS (2026)
-- =============================================================================

-- These will be used to auto-generate holiday_observances for each year
INSERT INTO national_holidays (holiday_type_id, country_code, holiday_name, holiday_date, year, is_recurring, is_school_holiday, description) VALUES
-- Fixed-date holidays for 2026
((SELECT id FROM national_holiday_types WHERE code = 'public_holiday'), 'KE', 'New Year''s Day', '2026-01-01', 2026, TRUE, TRUE, 'First day of the year'),
((SELECT id FROM national_holiday_types WHERE code = 'commemoration'), 'KE', 'Good Friday', '2026-04-03', 2026, TRUE, TRUE, 'Christian holy day'),
((SELECT id FROM national_holiday_types WHERE code = 'commemoration'), 'KE', 'Easter Monday', '2026-04-06', 2026, TRUE, TRUE, 'Christian holy day'),
((SELECT id FROM national_holiday_types WHERE code = 'public_holiday'), 'KE', 'Labour Day', '2026-05-01', 2026, TRUE, TRUE, 'International Workers'' Day'),
((SELECT id FROM national_holiday_types WHERE code = 'commemoration'), 'KE', 'Madaraka Day', '2026-06-01', 2026, TRUE, TRUE, 'Commemorates Kenya''s attainment of self-rule'),
((SELECT id FROM national_holiday_types WHERE code = 'religious_holiday'), 'KE', 'Eid al-Fitr', '2026-03-20', 2026, TRUE, TRUE, 'Islamic holiday (date varies by lunar calendar)'),
((SELECT id FROM national_holiday_types WHERE code = 'religious_holiday'), 'KE', 'Eid al-Adha', '2026-05-27', 2026, TRUE, TRUE, 'Islamic holiday (date varies by lunar calendar)'),
((SELECT id FROM national_holiday_types WHERE code = 'commemoration'), 'KE', 'Huduma Day', '2026-10-10', 2026, TRUE, TRUE, 'Formerly Moi Day, now Huduma Day'),
((SELECT id FROM national_holiday_types WHERE code = 'commemoration'), 'KE', 'Mashujaa Day', '2026-10-20', 2026, TRUE, TRUE, 'Heroes'' Day'),
((SELECT id FROM national_holiday_types WHERE code = 'celebration'), 'KE', 'Jamhuri Day', '2026-12-12', 2026, TRUE, TRUE, 'Independence Day'),
((SELECT id FROM national_holiday_types WHERE code = 'public_holiday'), 'KE', 'Christmas Day', '2026-12-25', 2026, TRUE, TRUE, 'Christian holiday'),
((SELECT id FROM national_holiday_types WHERE code = 'public_holiday'), 'KE', 'Boxing Day', '2026-12-26', 2026, TRUE, TRUE, 'Day after Christmas')
ON DUPLICATE KEY UPDATE holiday_name=VALUES(holiday_name);

-- =============================================================================
-- AUTO-GENERATE OBSERVANCES FOR 2026
-- =============================================================================

-- Generate observances for all 2026 holidays
INSERT INTO holiday_observances (national_holiday_id, observance_date, year, is_observed)
SELECT id, holiday_date, year, TRUE
FROM national_holidays
WHERE year = 2026
ON DUPLICATE KEY UPDATE observance_date=VALUES(observance_date);
