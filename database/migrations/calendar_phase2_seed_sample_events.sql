-- Seed Sample Events for Calendar Module Phase 2
-- Created: 2026-01-08
-- Description: Add sample events to demonstrate the events management system

-- =============================================================================
-- SAMPLE EVENTS FOR TERM 1 (2025-01-06 to 2025-04-04)
-- =============================================================================

-- Event 1: Sports Day in Term 1
INSERT INTO events (
    school_id, term_id, event_type_id,
    title, description, venue,
    start_date, start_time, end_date, end_time, is_all_day,
    status, visibility,
    rsvp_enabled, rsvp_required, rsvp_deadline, max_attendees, allow_guests, max_guests_per_attendee,
    requires_planning, planning_start_date,
    created_by, created_at, published_at
) VALUES (
    1,
    1,
    (SELECT id FROM term_date_types WHERE code = 'sports_day'),
    'Inter-House Sports Day 2025',
    'Annual inter-house sports competition featuring athletics, football, basketball, and volleyball. All students are required to participate in at least one event.',
    'Main Sports Field',
    '2025-03-15',
    '08:00:00',
    '2025-03-15',
    '17:00:00',
    0,
    'published',
    'public',
    1,
    0,
    '2025-03-10',
    500,
    1,
    2,
    1,
    '2025-02-15',
    1,
    NOW(),
    NOW()
);

SET @sports_day_id = LAST_INSERT_ID();

-- Event owners for Sports Day
INSERT INTO event_owners (event_id, owner_type, owner_id, is_primary, can_edit, can_manage_rsvp, can_view_reports, can_send_notifications)
VALUES (@sports_day_id, 'staff', 1, 1, 1, 1, 1, 1);

-- Event audiences for Sports Day (all students)
INSERT INTO event_audiences (event_id, audience_type, config, can_view, can_rsvp)
VALUES (@sports_day_id, 'all_students', NULL, 1, 1);

-- Event planning tasks for Sports Day
INSERT INTO event_planning_tasks (
    event_id, task_title, task_description, priority,
    assigned_to, assigned_by, due_date, status,
    reminder_enabled, reminder_days_before
) VALUES
(@sports_day_id, 'Book sports equipment', 'Reserve all necessary sports equipment including balls, cones, starting guns, and timing equipment', 'high', 1, 1, '2025-03-01', 'pending', 1, 3),
(@sports_day_id, 'Prepare field markings', 'Mark the field for all athletic events (100m, 200m, relay zones, long jump pit)', 'high', 1, 1, '2025-03-13', 'pending', 1, 2),
(@sports_day_id, 'Arrange first aid station', 'Coordinate with school nurse to set up first aid station with adequate supplies', 'critical', 1, 1, '2025-03-14', 'pending', 1, 1),
(@sports_day_id, 'Print participant lists', 'Print house lists and participant registration forms', 'medium', 1, 1, '2025-03-12', 'pending', 1, 2);

-- Event 2: PTA Meeting in Term 1
INSERT INTO events (
    school_id, term_id, event_type_id,
    title, description, venue,
    start_date, start_time, end_date, is_all_day,
    status, visibility,
    rsvp_enabled, rsvp_required, rsvp_deadline, max_attendees,
    created_by, created_at, published_at
) VALUES (
    1,
    1,
    (SELECT id FROM term_date_types WHERE code = 'pta_meeting'),
    'Term 1 PTA General Meeting',
    'First PTA meeting of the year to discuss school development, budget proposals, and upcoming activities. Light refreshments will be served.',
    'School Hall',
    '2025-02-08',
    '14:00:00',
    NULL,
    0,
    'published',
    'public',
    1,
    0,
    '2025-02-05',
    200,
    1,
    NOW(),
    NOW()
);

SET @pta_meeting_id = LAST_INSERT_ID();

-- Event audiences for PTA Meeting (all parents)
INSERT INTO event_audiences (event_id, audience_type, config, can_view, can_rsvp)
VALUES (@pta_meeting_id, 'all_parents', NULL, 1, 1);

-- Event 3: Science Fair in Term 1
INSERT INTO events (
    school_id, term_id, event_type_id,
    title, description, venue,
    start_date, start_time, end_date, end_time, is_all_day,
    status, visibility,
    rsvp_enabled, max_attendees, allow_guests, max_guests_per_attendee,
    requires_planning, planning_start_date,
    created_by, created_at, published_at
) VALUES (
    1,
    1,
    (SELECT id FROM term_date_types WHERE code = 'science_fair'),
    'Annual Science & Innovation Fair',
    'Students showcase their science projects and innovations. Open to parents and the public. Prizes for top 3 projects in each category.',
    'School Library & Labs',
    '2025-03-28',
    '09:00:00',
    '2025-03-28',
    '15:00:00',
    0,
    'published',
    'public',
    1,
    300,
    1,
    3,
    1,
    '2025-02-28',
    1,
    NOW(),
    NOW()
);

SET @science_fair_id = LAST_INSERT_ID();

-- Event audiences for Science Fair (students grade 7-12 and all parents)
INSERT INTO event_audiences (event_id, audience_type, config, can_view, can_rsvp)
VALUES
(@science_fair_id, 'grade_range', '{"from": 7, "to": 12}', 1, 1),
(@science_fair_id, 'all_parents', NULL, 1, 1);

-- =============================================================================
-- SAMPLE EVENTS FOR TERM 2 (2025-05-05 to 2025-08-01)
-- =============================================================================

-- Event 4: School Trip in Term 2
INSERT INTO events (
    school_id, term_id, event_type_id,
    title, description, venue,
    start_date, start_time, end_date, end_time, is_all_day,
    status, visibility,
    rsvp_enabled, rsvp_required, rsvp_deadline, max_attendees,
    requires_planning, planning_start_date,
    created_by, created_at, published_at
) VALUES (
    1,
    2,
    (SELECT id FROM term_date_types WHERE code = 'school_trip'),
    'Educational Trip to Nairobi National Museum',
    'Full-day educational trip for Grade 6-8 students to Nairobi National Museum. Transport, entry fees, and lunch included. Students must bring water and sunscreen.',
    'Nairobi National Museum',
    '2025-06-14',
    '07:00:00',
    '2025-06-14',
    '18:00:00',
    0,
    'published',
    'public',
    1,
    1,
    '2025-06-07',
    80,
    1,
    '2025-05-14',
    1,
    NOW(),
    NOW()
);

SET @trip_id = LAST_INSERT_ID();

-- Event audiences for School Trip (grades 6-8 students and their parents)
INSERT INTO event_audiences (event_id, audience_type, config, can_view, can_rsvp)
VALUES
(@trip_id, 'grade_range', '{"from": 6, "to": 8}', 1, 1),
(@trip_id, 'grade_parents', '{"grades": [6, 7, 8]}', 1, 0);

-- Event planning tasks for School Trip
INSERT INTO event_planning_tasks (
    event_id, task_title, task_description, priority,
    assigned_to, assigned_by, due_date, status,
    reminder_enabled, reminder_days_before
) VALUES
(@trip_id, 'Book transport buses', 'Reserve 2 buses (40 seats each) from approved transport company', 'critical', 1, 1, '2025-05-30', 'pending', 1, 7),
(@trip_id, 'Collect consent forms', 'Ensure all participating students have signed parental consent forms', 'critical', 1, 1, '2025-06-10', 'pending', 1, 3),
(@trip_id, 'Prepare student groups', 'Assign students to groups with designated teacher supervisors', 'high', 1, 1, '2025-06-12', 'pending', 1, 2);

-- Event 5: Parent-Teacher Conference in Term 2
INSERT INTO events (
    school_id, term_id, event_type_id,
    title, description, venue,
    start_date, start_time, end_date, end_time, is_all_day,
    status, visibility,
    rsvp_enabled, rsvp_required, rsvp_deadline,
    created_by, created_at, published_at
) VALUES (
    1,
    2,
    (SELECT id FROM term_date_types WHERE code = 'parent_teacher_conf'),
    'Mid-Year Parent-Teacher Conferences',
    'Individual consultations between parents and subject teachers to discuss student progress. Please book your 15-minute slot in advance.',
    'Classrooms A1-A12',
    '2025-06-23',
    '08:00:00',
    '2025-06-24',
    '16:00:00',
    0,
    'published',
    'public',
    1,
    1,
    '2025-06-18',
    1,
    NOW(),
    NOW()
);

SET @ptc_id = LAST_INSERT_ID();

-- Event audiences for Parent-Teacher Conference (all parents and all staff)
INSERT INTO event_audiences (event_id, audience_type, config, can_view, can_rsvp)
VALUES
(@ptc_id, 'all_parents', NULL, 1, 1),
(@ptc_id, 'all_staff', NULL, 1, 0);

-- =============================================================================
-- SAMPLE EVENTS FOR TERM 3 (2025-09-01 to 2025-11-28)
-- =============================================================================

-- Event 6: Cultural Festival in Term 3
INSERT INTO events (
    school_id, term_id, event_type_id,
    title, description, venue,
    start_date, start_time, end_date, end_time, is_all_day,
    status, visibility,
    rsvp_enabled, max_attendees, allow_guests, max_guests_per_attendee,
    requires_planning, planning_start_date,
    created_by, created_at, published_at
) VALUES (
    1,
    3,
    (SELECT id FROM term_date_types WHERE code = 'cultural_festival'),
    'Multicultural Day Celebration',
    'Celebration of Kenya\'s diverse cultures through music, dance, food, and traditional dress. Students present cultural performances and exhibitions.',
    'School Grounds & Main Hall',
    '2025-10-24',
    '10:00:00',
    '2025-10-24',
    '16:00:00',
    0,
    'published',
    'public',
    1,
    600,
    1,
    4,
    1,
    '2025-09-24',
    1,
    NOW(),
    NOW()
);

SET @cultural_fest_id = LAST_INSERT_ID();

-- Event audiences for Cultural Festival (all students and all parents)
INSERT INTO event_audiences (event_id, audience_type, config, can_view, can_rsvp)
VALUES
(@cultural_fest_id, 'all_students', NULL, 1, 1),
(@cultural_fest_id, 'all_parents', NULL, 1, 1);

-- Event 7: Career Day in Term 3
INSERT INTO events (
    school_id, term_id, event_type_id,
    title, description, venue,
    start_date, start_time, end_date, is_all_day,
    status, visibility,
    rsvp_enabled, rsvp_deadline,
    created_by, created_at, published_at
) VALUES (
    1,
    3,
    (SELECT id FROM term_date_types WHERE code = 'career_day'),
    'Career Guidance & Mentorship Day',
    'Guest speakers from various professions share their career journeys and insights. Q&A sessions and one-on-one mentorship opportunities available.',
    'Main Hall',
    '2025-11-07',
    '09:00:00',
    NULL,
    0,
    'published',
    'public',
    1,
    '2025-11-03',
    1,
    NOW(),
    NOW()
);

SET @career_day_id = LAST_INSERT_ID();

-- Event audiences for Career Day (grades 10-12 only)
INSERT INTO event_audiences (event_id, audience_type, config, can_view, can_rsvp)
VALUES (@career_day_id, 'grade_range', '{"from": 10, "to": 12}', 1, 1);

-- Event 8: Health & Wellness Day in Term 3
INSERT INTO events (
    school_id, term_id, event_type_id,
    title, description, venue,
    start_date, start_time, end_date, is_all_day,
    status, visibility,
    rsvp_enabled,
    created_by, created_at, published_at
) VALUES (
    1,
    3,
    (SELECT id FROM term_date_types WHERE code = 'health_wellness'),
    'School Health Screening & Wellness Fair',
    'Free health screenings including vision, dental, and general check-ups. Mental health awareness sessions and fitness demonstrations.',
    'School Clinic & Assembly Area',
    '2025-09-20',
    '08:00:00',
    NULL,
    1,
    'published',
    'public',
    0,
    1,
    NOW(),
    NOW()
);

SET @health_day_id = LAST_INSERT_ID();

-- Event audiences for Health Day (all students)
INSERT INTO event_audiences (event_id, audience_type, config, can_view, can_rsvp)
VALUES (@health_day_id, 'all_students', NULL, 1, 0);

-- =============================================================================
-- VERIFICATION QUERIES
-- =============================================================================

-- Count events by term
-- SELECT t.term_name, COUNT(e.id) as event_count
-- FROM terms t
-- LEFT JOIN events e ON t.id = e.term_id
-- GROUP BY t.id, t.term_name
-- ORDER BY t.term_number;

-- Show all events with their types
-- SELECT
--     e.title,
--     tdt.name as event_type,
--     tdt.category,
--     e.start_date,
--     e.status,
--     e.rsvp_enabled,
--     e.max_attendees
-- FROM events e
-- JOIN term_date_types tdt ON e.event_type_id = tdt.id
-- ORDER BY e.start_date;

-- Count planning tasks by event
-- SELECT
--     e.title,
--     COUNT(ept.id) as task_count
-- FROM events e
-- LEFT JOIN event_planning_tasks ept ON e.id = ept.event_id
-- GROUP BY e.id, e.title;
