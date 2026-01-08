-- Seed Event Types for Calendar Module Phase 2
-- Created: 2026-01-08
-- Description: Add "events" category and event-specific date types to term_date_types table

-- =============================================================================
-- ADD EVENT-SPECIFIC DATE TYPES
-- =============================================================================

-- Sports Day
INSERT INTO term_date_types (code, name, category, description, color, icon, is_active, sort_order)
VALUES (
    'sports_day',
    'Sports Day',
    'events',
    'Inter-house or inter-school sports competition and athletic events',
    '#FF6B35',
    'ti-trophy',
    1,
    60
);

-- Cultural Festival
INSERT INTO term_date_types (code, name, category, description, color, icon, is_active, sort_order)
VALUES (
    'cultural_festival',
    'Cultural Festival',
    'events',
    'Cultural celebrations, music, drama, and arts festival',
    '#9D4EDD',
    'ti-mask',
    1,
    61
);

-- PTA Meeting
INSERT INTO term_date_types (code, name, category, description, color, icon, is_active, sort_order)
VALUES (
    'pta_meeting',
    'PTA Meeting',
    'events',
    'Parent-Teacher Association meeting',
    '#3A86FF',
    'ti-users',
    1,
    62
);

-- School Trip / Excursion
INSERT INTO term_date_types (code, name, category, description, color, icon, is_active, sort_order)
VALUES (
    'school_trip',
    'School Trip',
    'events',
    'Educational trips, excursions, and field visits',
    '#06A77D',
    'ti-bus',
    1,
    63
);

-- Parent-Teacher Conference
INSERT INTO term_date_types (code, name, category, description, color, icon, is_active, sort_order)
VALUES (
    'parent_teacher_conf',
    'Parent-Teacher Conference',
    'events',
    'Individual parent-teacher consultation sessions',
    '#F77F00',
    'ti-calendar-time',
    1,
    64
);

-- Awards Ceremony
INSERT INTO term_date_types (code, name, category, description, color, icon, is_active, sort_order)
VALUES (
    'awards_ceremony',
    'Awards Ceremony',
    'events',
    'Student recognition and achievement awards ceremony',
    '#FFD60A',
    'ti-award',
    1,
    65
);

-- Open Day
INSERT INTO term_date_types (code, name, category, description, color, icon, is_active, sort_order)
VALUES (
    'open_day_event',
    'Open Day',
    'events',
    'School open day for prospective students and parents',
    '#00BBF9',
    'ti-door-enter',
    1,
    66
);

-- Fundraising Event
INSERT INTO term_date_types (code, name, category, description, color, icon, is_active, sort_order)
VALUES (
    'fundraising',
    'Fundraising Event',
    'events',
    'Fundraising activities and charity events',
    '#F15BB5',
    'ti-cash',
    1,
    67
);

-- Staff Meeting
INSERT INTO term_date_types (code, name, category, description, color, icon, is_active, sort_order)
VALUES (
    'staff_meeting',
    'Staff Meeting',
    'events',
    'Staff meetings and departmental discussions',
    '#5E548E',
    'ti-briefcase',
    1,
    68
);

-- Training Session / Workshop
INSERT INTO term_date_types (code, name, category, description, color, icon, is_active, sort_order)
VALUES (
    'training_workshop',
    'Training/Workshop',
    'events',
    'Staff or student training sessions and workshops',
    '#00A896',
    'ti-school',
    1,
    69
);

-- Board Meeting
INSERT INTO term_date_types (code, name, category, description, color, icon, is_active, sort_order)
VALUES (
    'board_meeting',
    'Board Meeting',
    'events',
    'Board of Management or Governors meeting',
    '#2E294E',
    'ti-shield-check',
    1,
    70
);

-- Community Service
INSERT INTO term_date_types (code, name, category, description, color, icon, is_active, sort_order)
VALUES (
    'community_service',
    'Community Service',
    'events',
    'Community outreach and service activities',
    '#80B918',
    'ti-heart-handshake',
    1,
    71
);

-- Science Fair / Exhibition
INSERT INTO term_date_types (code, name, category, description, color, icon, is_active, sort_order)
VALUES (
    'science_fair',
    'Science Fair',
    'events',
    'Science exhibitions and project presentations',
    '#00B4D8',
    'ti-atom',
    1,
    72
);

-- Career Day
INSERT INTO term_date_types (code, name, category, description, color, icon, is_active, sort_order)
VALUES (
    'career_day',
    'Career Day',
    'events',
    'Career guidance and professional mentorship day',
    '#FF9F1C',
    'ti-tie',
    1,
    73
);

-- Health & Wellness Day
INSERT INTO term_date_types (code, name, category, description, color, icon, is_active, sort_order)
VALUES (
    'health_wellness',
    'Health & Wellness Day',
    'events',
    'Health screening, wellness activities, and medical camps',
    '#2EC4B6',
    'ti-heart-rate-monitor',
    1,
    74
);

-- =============================================================================
-- VERIFICATION QUERY
-- =============================================================================

-- Count date types by category including new events category
-- SELECT category, COUNT(*) as type_count
-- FROM term_date_types
-- WHERE is_active = 1
-- GROUP BY category
-- ORDER BY category;

-- Show all event types
-- SELECT id, code, name, color, icon, affects_timetable, allow_range
-- FROM term_date_types
-- WHERE category = 'events' AND is_active = 1
-- ORDER BY sort_order;
