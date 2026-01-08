-- Seed Sample Data for Calendar Module
-- Created: 2026-01-08
-- Description: Add important dates to existing terms for visualization

-- =============================================================================
-- TERM 1 (2025-01-06 to 2025-04-04) - IMPORTANT DATES
-- =============================================================================

-- Term 1 Opening
INSERT INTO term_important_dates (term_id, date_type_id, start_date, end_date, title, description, affects_timetable, is_school_open, created_by)
VALUES (
    1,
    (SELECT id FROM term_date_types WHERE code = 'term_opening'),
    '2025-01-06',
    NULL,
    'Term 1 Opening Day',
    'First day of Term 1 for 2025/2026 academic year',
    0,
    1,
    1
);

-- Orientation Day (for new students)
INSERT INTO term_important_dates (term_id, date_type_id, start_date, end_date, title, description, affects_timetable, is_school_open, created_by)
VALUES (
    1,
    (SELECT id FROM term_date_types WHERE code = 'orientation_day'),
    '2025-01-07',
    NULL,
    'New Students Orientation',
    'Orientation program for new students and parents',
    1,
    1,
    1
);

-- Mid-term break
INSERT INTO term_important_dates (term_id, date_type_id, start_date, end_date, title, description, affects_timetable, is_school_open, created_by)
VALUES (
    1,
    (SELECT id FROM term_date_types WHERE code = 'mid_term_break_start'),
    '2025-02-17',
    '2025-02-21',
    'Mid-Term Break',
    'One week mid-term break for Term 1',
    1,
    0,
    1
);

-- Mid-term exams
INSERT INTO term_important_dates (term_id, date_type_id, start_date, end_date, title, description, affects_timetable, is_school_open, created_by)
VALUES (
    1,
    (SELECT id FROM term_date_types WHERE code = 'midterm_exams_start'),
    '2025-02-10',
    '2025-02-14',
    'Mid-Term Examinations',
    'Mid-term assessment examinations for all grades',
    1,
    1,
    1
);

-- End of term exams
INSERT INTO term_important_dates (term_id, date_type_id, start_date, end_date, title, description, affects_timetable, is_school_open, created_by)
VALUES (
    1,
    (SELECT id FROM term_date_types WHERE code = 'endterm_exams_start'),
    '2025-03-24',
    '2025-03-31',
    'End of Term 1 Examinations',
    'Final examinations for Term 1',
    1,
    1,
    1
);

-- Visiting Day
INSERT INTO term_important_dates (term_id, date_type_id, start_date, end_date, title, description, affects_timetable, is_school_open, created_by)
VALUES (
    1,
    (SELECT id FROM term_date_types WHERE code = 'visiting_day'),
    '2025-02-22',
    NULL,
    'Parents Visiting Day',
    'Parents visit day - students meet with parents',
    1,
    1,
    1
);

-- Report Cards Distribution
INSERT INTO term_important_dates (term_id, date_type_id, start_date, end_date, title, description, affects_timetable, is_school_open, created_by)
VALUES (
    1,
    (SELECT id FROM term_date_types WHERE code = 'report_cards'),
    '2025-04-03',
    NULL,
    'Term 1 Report Cards Distribution',
    'Students receive their Term 1 academic reports',
    0,
    1,
    1
);

-- Term 1 Closing
INSERT INTO term_important_dates (term_id, date_type_id, start_date, end_date, title, description, affects_timetable, is_school_open, created_by)
VALUES (
    1,
    (SELECT id FROM term_date_types WHERE code = 'term_closing'),
    '2025-04-04',
    NULL,
    'Term 1 Closing Day',
    'Last day of Term 1 - students go on holiday',
    0,
    1,
    1
);

-- =============================================================================
-- TERM 2 (2025-05-05 to 2025-08-01) - IMPORTANT DATES
-- =============================================================================

-- Term 2 Opening
INSERT INTO term_important_dates (term_id, date_type_id, start_date, end_date, title, description, affects_timetable, is_school_open, created_by)
VALUES (
    2,
    (SELECT id FROM term_date_types WHERE code = 'term_opening'),
    '2025-05-05',
    NULL,
    'Term 2 Opening Day',
    'First day of Term 2',
    0,
    1,
    1
);

-- Mid-term break
INSERT INTO term_important_dates (term_id, date_type_id, start_date, end_date, title, description, affects_timetable, is_school_open, created_by)
VALUES (
    2,
    (SELECT id FROM term_date_types WHERE code = 'mid_term_break_start'),
    '2025-06-16',
    '2025-06-20',
    'Mid-Term Break',
    'One week mid-term break for Term 2',
    1,
    0,
    1
);

-- Mid-term exams
INSERT INTO term_important_dates (term_id, date_type_id, start_date, end_date, title, description, affects_timetable, is_school_open, created_by)
VALUES (
    2,
    (SELECT id FROM term_date_types WHERE code = 'midterm_exams_start'),
    '2025-06-09',
    '2025-06-13',
    'Mid-Term Examinations',
    'Mid-term assessment examinations for all grades',
    1,
    1,
    1
);

-- Inter-House Sports Competition
INSERT INTO term_important_dates (term_id, date_type_id, start_date, end_date, title, description, affects_timetable, is_school_open, created_by)
VALUES (
    2,
    (SELECT id FROM term_date_types WHERE code = 'inter_house_comp'),
    '2025-06-27',
    '2025-06-28',
    'Inter-House Sports Day',
    'Annual inter-house sports competition',
    1,
    1,
    1
);

-- Mock Exams (for exam classes)
INSERT INTO term_important_dates (term_id, date_type_id, start_date, end_date, title, description, affects_timetable, is_school_open, created_by)
VALUES (
    2,
    (SELECT id FROM term_date_types WHERE code = 'mock_exams_start'),
    '2025-07-14',
    '2025-07-21',
    'Mock Examinations',
    'Mock examinations for KCPE and KCSE candidates',
    1,
    1,
    1
);

-- End of term exams
INSERT INTO term_important_dates (term_id, date_type_id, start_date, end_date, title, description, affects_timetable, is_school_open, created_by)
VALUES (
    2,
    (SELECT id FROM term_date_types WHERE code = 'endterm_exams_start'),
    '2025-07-21',
    '2025-07-28',
    'End of Term 2 Examinations',
    'Final examinations for Term 2',
    1,
    1,
    1
);

-- Visiting Day
INSERT INTO term_important_dates (term_id, date_type_id, start_date, end_date, title, description, affects_timetable, is_school_open, created_by)
VALUES (
    2,
    (SELECT id FROM term_date_types WHERE code = 'visiting_day'),
    '2025-06-21',
    NULL,
    'Parents Visiting Day',
    'Parents visit day - students meet with parents',
    1,
    1,
    1
);

-- Report Cards Distribution
INSERT INTO term_important_dates (term_id, date_type_id, start_date, end_date, title, description, affects_timetable, is_school_open, created_by)
VALUES (
    2,
    (SELECT id FROM term_date_types WHERE code = 'report_cards'),
    '2025-07-31',
    NULL,
    'Term 2 Report Cards Distribution',
    'Students receive their Term 2 academic reports',
    0,
    1,
    1
);

-- Term 2 Closing
INSERT INTO term_important_dates (term_id, date_type_id, start_date, end_date, title, description, affects_timetable, is_school_open, created_by)
VALUES (
    2,
    (SELECT id FROM term_date_types WHERE code = 'term_closing'),
    '2025-08-01',
    NULL,
    'Term 2 Closing Day',
    'Last day of Term 2',
    0,
    1,
    1
);

-- =============================================================================
-- TERM 3 (2025-09-01 to 2025-11-28) - IMPORTANT DATES
-- =============================================================================

-- Term 3 Opening
INSERT INTO term_important_dates (term_id, date_type_id, start_date, end_date, title, description, affects_timetable, is_school_open, created_by)
VALUES (
    3,
    (SELECT id FROM term_date_types WHERE code = 'term_opening'),
    '2025-09-01',
    NULL,
    'Term 3 Opening Day',
    'First day of Term 3',
    0,
    1,
    1
);

-- Mid-term break
INSERT INTO term_important_dates (term_id, date_type_id, start_date, end_date, title, description, affects_timetable, is_school_open, created_by)
VALUES (
    3,
    (SELECT id FROM term_date_types WHERE code = 'mid_term_break_start'),
    '2025-10-13',
    '2025-10-17',
    'Mid-Term Break',
    'One week mid-term break for Term 3',
    1,
    0,
    1
);

-- Mid-term exams
INSERT INTO term_important_dates (term_id, date_type_id, start_date, end_date, title, description, affects_timetable, is_school_open, created_by)
VALUES (
    3,
    (SELECT id FROM term_date_types WHERE code = 'midterm_exams_start'),
    '2025-10-06',
    '2025-10-10',
    'Mid-Term Examinations',
    'Mid-term assessment examinations for all grades',
    1,
    1,
    1
);

-- KCPE Examinations
INSERT INTO term_important_dates (term_id, date_type_id, start_date, end_date, title, description, affects_timetable, is_school_open, created_by)
VALUES (
    3,
    (SELECT id FROM term_date_types WHERE code = 'kcpe_exams'),
    '2025-10-27',
    '2025-10-29',
    'KCPE National Examinations',
    'Kenya Certificate of Primary Education examinations',
    1,
    1,
    1
);

-- KCSE Examinations
INSERT INTO term_important_dates (term_id, date_type_id, start_date, end_date, title, description, affects_timetable, is_school_open, created_by)
VALUES (
    3,
    (SELECT id FROM term_date_types WHERE code = 'kcse_exams'),
    '2025-11-03',
    '2025-11-21',
    'KCSE National Examinations',
    'Kenya Certificate of Secondary Education examinations',
    1,
    1,
    1
);

-- End of term exams (for lower grades)
INSERT INTO term_important_dates (term_id, date_type_id, start_date, end_date, title, description, affects_timetable, is_school_open, created_by)
VALUES (
    3,
    (SELECT id FROM term_date_types WHERE code = 'endterm_exams_start'),
    '2025-11-10',
    '2025-11-17',
    'End of Term 3 Examinations',
    'Final examinations for Term 3 (lower grades)',
    1,
    1,
    1
);

-- Visiting Day
INSERT INTO term_important_dates (term_id, date_type_id, start_date, end_date, title, description, affects_timetable, is_school_open, created_by)
VALUES (
    3,
    (SELECT id FROM term_date_types WHERE code = 'visiting_day'),
    '2025-10-18',
    NULL,
    'Parents Visiting Day',
    'Parents visit day - students meet with parents',
    1,
    1,
    1
);

-- Graduation Day
INSERT INTO term_important_dates (term_id, date_type_id, start_date, end_date, title, description, affects_timetable, is_school_open, created_by)
VALUES (
    3,
    (SELECT id FROM term_date_types WHERE code = 'graduation_day'),
    '2025-11-22',
    NULL,
    'Graduation Ceremony',
    'Graduation ceremony for completing students',
    1,
    1,
    1
);

-- Prize Giving Day
INSERT INTO term_important_dates (term_id, date_type_id, start_date, end_date, title, description, affects_timetable, is_school_open, created_by)
VALUES (
    3,
    (SELECT id FROM term_date_types WHERE code = 'prize_giving'),
    '2025-11-27',
    NULL,
    'Annual Prize Giving Day',
    'Academic excellence awards and prize distribution',
    1,
    1,
    1
);

-- Report Cards Distribution
INSERT INTO term_important_dates (term_id, date_type_id, start_date, end_date, title, description, affects_timetable, is_school_open, created_by)
VALUES (
    3,
    (SELECT id FROM term_date_types WHERE code = 'report_cards'),
    '2025-11-27',
    NULL,
    'Term 3 Report Cards Distribution',
    'Students receive their Term 3 academic reports',
    0,
    1,
    1
);

-- Term 3 Closing
INSERT INTO term_important_dates (term_id, date_type_id, start_date, end_date, title, description, affects_timetable, is_school_open, created_by)
VALUES (
    3,
    (SELECT id FROM term_date_types WHERE code = 'term_closing'),
    '2025-11-28',
    NULL,
    'Term 3 Closing Day',
    'Last day of Term 3 and academic year',
    0,
    1,
    1
);

-- =============================================================================
-- VERIFICATION QUERY
-- =============================================================================

-- Count important dates by term
-- SELECT t.term_name, COUNT(tid.id) as dates_count
-- FROM terms t
-- LEFT JOIN term_important_dates tid ON t.id = tid.term_id
-- GROUP BY t.id, t.term_name
-- ORDER BY t.term_number;
