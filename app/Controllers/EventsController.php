<?php

class EventsController
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getTenantConnection();
    }

    // =============================================================================
    // MAIN EVENT VIEWS
    // =============================================================================

    /**
     * Display all events (list/calendar view)
     */
    public function index()
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        $tenantId = $_SESSION['tenant_id'] ?? null;
        $currentYear = date('Y');

        // Get filter parameters
        $view = $_GET['view'] ?? 'list'; // list, calendar, upcoming
        $status = $_GET['status'] ?? 'all';
        $type = $_GET['type'] ?? 'all';
        $term = $_GET['term'] ?? 'all';

        // Get all events
        $events = $this->getEvents($tenantId, $status, $type, $term);

        // Get filter options
        $eventTypes = $this->getEventTypes();
        $terms = $this->getAcademicTerms($tenantId);
        $stats = $this->getEventStats($tenantId);

        Response::view('events.index', [
            'pageTitle' => 'Events Management',
            'events' => $events,
            'eventTypes' => $eventTypes,
            'terms' => $terms,
            'stats' => $stats,
            'currentView' => $view,
            'currentStatus' => $status,
            'currentType' => $type,
            'currentTerm' => $term
        ]);
    }

    /**
     * Show single event details
     * Returns JSON if AJAX request, otherwise redirects
     */
    public function show($id)
    {
        if (!isAuthenticated()) {
            if (Request::isAjax()) {
                Response::json(['success' => false, 'message' => 'Not authenticated']);
                return;
            }
            Response::redirect('/login');
        }

        $tenantId = $_SESSION['tenant_id'] ?? null;
        $event = $this->getEventById($id, $tenantId);

        if (!$event) {
            if (Request::isAjax()) {
                Response::json(['success' => false, 'message' => 'Event not found']);
                return;
            }
            flash('error', 'Event not found');
            Response::redirect('/calendar/events');
            return;
        }

        // If AJAX request, return JSON
        if (Request::isAjax()) {
            // Get additional event details
            $event['owners'] = $this->getEventOwners($id);
            $event['audiences'] = $this->getEventAudiences($id);
            $event['rsvp_count'] = $this->getEventRSVPCount($id);

            Response::json([
                'success' => true,
                'event' => $event,
                'eventTypes' => $this->getEventTypes(),
                'terms' => $this->getAcademicTerms($tenantId)
            ]);
            return;
        }

        // For non-AJAX, redirect to index
        flash('info', 'Event details view coming soon. Event: ' . $event['title']);
        Response::redirect('/calendar/events');
    }

    /**
     * Show create event form
     * Returns JSON with form data if AJAX request, otherwise redirects
     */
    public function create()
    {
        if (!isAuthenticated()) {
            if (Request::isAjax()) {
                Response::json(['success' => false, 'message' => 'Not authenticated']);
                return;
            }
            Response::redirect('/login');
        }

        $tenantId = $_SESSION['tenant_id'] ?? null;

        // If AJAX request, return form data
        if (Request::isAjax()) {
            Response::json([
                'success' => true,
                'eventTypes' => $this->getEventTypes(),
                'terms' => $this->getAcademicTerms($tenantId)
            ]);
            return;
        }

        // For non-AJAX, redirect to index
        flash('info', 'Please use the Create Event button on the events page.');
        Response::redirect('/calendar/events');
    }

    /**
     * Store new event
     */
    public function store()
    {
        if (!isAuthenticated()) {
            Response::json(['success' => false, 'message' => 'Not authenticated']);
            return;
        }

        $tenantId = $_SESSION['tenant_id'] ?? null;
        $userId = $_SESSION['user_id'] ?? null;

        // Validate required fields
        $requiredFields = ['event_type_id', 'title', 'start_date'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                flash('error', 'Please fill all required fields');
                Response::redirect('/calendar/events/create');
                return;
            }
        }

        // Prepare event data
        $data = [
            'school_id' => $tenantId,
            'term_id' => !empty($_POST['term_id']) ? $_POST['term_id'] : null,
            'event_type_id' => $_POST['event_type_id'],
            'title' => $_POST['title'],
            'description' => $_POST['description'] ?? null,
            'venue' => $_POST['venue'] ?? null,
            'start_date' => $_POST['start_date'],
            'start_time' => !empty($_POST['start_time']) ? $_POST['start_time'] : null,
            'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
            'end_time' => !empty($_POST['end_time']) ? $_POST['end_time'] : null,
            'is_all_day' => isset($_POST['is_all_day']) ? 1 : 0,
            'status' => $_POST['status'] ?? 'draft',
            'visibility' => $_POST['visibility'] ?? 'public',
            'rsvp_enabled' => isset($_POST['rsvp_enabled']) ? 1 : 0,
            'rsvp_required' => isset($_POST['rsvp_required']) ? 1 : 0,
            'rsvp_deadline' => !empty($_POST['rsvp_deadline']) ? $_POST['rsvp_deadline'] : null,
            'max_attendees' => !empty($_POST['max_attendees']) ? $_POST['max_attendees'] : null,
            'allow_guests' => isset($_POST['allow_guests']) ? 1 : 0,
            'max_guests_per_attendee' => !empty($_POST['max_guests_per_attendee']) ? $_POST['max_guests_per_attendee'] : 0,
            'requires_planning' => isset($_POST['requires_planning']) ? 1 : 0,
            'planning_start_date' => !empty($_POST['planning_start_date']) ? $_POST['planning_start_date'] : null,
            'created_by' => $userId,
            'published_at' => $_POST['status'] === 'published' ? date('Y-m-d H:i:s') : null
        ];

        try {
            $this->pdo->beginTransaction();

            // Insert event
            $stmt = $this->pdo->prepare("
                INSERT INTO events (
                    school_id, term_id, event_type_id, title, description, venue,
                    start_date, start_time, end_date, end_time, is_all_day,
                    status, visibility,
                    rsvp_enabled, rsvp_required, rsvp_deadline, max_attendees,
                    allow_guests, max_guests_per_attendee,
                    requires_planning, planning_start_date,
                    created_by, published_at
                ) VALUES (
                    :school_id, :term_id, :event_type_id, :title, :description, :venue,
                    :start_date, :start_time, :end_date, :end_time, :is_all_day,
                    :status, :visibility,
                    :rsvp_enabled, :rsvp_required, :rsvp_deadline, :max_attendees,
                    :allow_guests, :max_guests_per_attendee,
                    :requires_planning, :planning_start_date,
                    :created_by, :published_at
                )
            ");

            $stmt->execute($data);
            $eventId = $this->pdo->lastInsertId();

            // Add event owner (creator as primary owner)
            $this->addEventOwner($eventId, 'staff', $userId, true);

            // Add audiences if specified
            if (!empty($_POST['audiences'])) {
                foreach ($_POST['audiences'] as $audienceData) {
                    $this->addEventAudience($eventId, $audienceData);
                }
            }

            $this->pdo->commit();

            if (Request::isAjax()) {
                Response::json([
                    'success' => true,
                    'message' => 'Event created successfully',
                    'eventId' => $eventId
                ]);
                return;
            }

            flash('success', 'Event created successfully');
            Response::redirect('/calendar/events');

        } catch (Exception $e) {
            $this->pdo->rollBack();

            if (Request::isAjax()) {
                Response::json([
                    'success' => false,
                    'message' => 'Failed to create event: ' . $e->getMessage()
                ]);
                return;
            }

            flash('error', 'Failed to create event: ' . $e->getMessage());
            Response::redirect('/calendar/events');
        }
    }

    /**
     * Show edit event form (redirects to index for now - implement modal/inline form later)
     */
    public function edit($id)
    {
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        $tenantId = $_SESSION['tenant_id'] ?? null;
        $event = $this->getEventById($id, $tenantId);

        if (!$event) {
            flash('error', 'Event not found');
            Response::redirect('/calendar/events');
            return;
        }

        // TODO: Implement edit form modal or inline editing
        // For now, redirect back to events list
        flash('info', 'Event editing interface coming soon. Event ID: ' . $id);
        Response::redirect('/calendar/events');
    }

    /**
     * Update event
     */
    public function update($id)
    {
        if (!isAuthenticated()) {
            Response::json(['success' => false, 'message' => 'Not authenticated']);
            return;
        }

        $tenantId = $_SESSION['tenant_id'] ?? null;
        $userId = $_SESSION['user_id'] ?? null;

        // Verify event exists and belongs to this school
        $event = $this->getEventById($id, $tenantId);
        if (!$event) {
            Response::json(['success' => false, 'message' => 'Event not found']);
            return;
        }

        // Prepare update data
        $data = [
            'id' => $id,
            'term_id' => !empty($_POST['term_id']) ? $_POST['term_id'] : null,
            'event_type_id' => $_POST['event_type_id'],
            'title' => $_POST['title'],
            'description' => $_POST['description'] ?? null,
            'venue' => $_POST['venue'] ?? null,
            'start_date' => $_POST['start_date'],
            'start_time' => !empty($_POST['start_time']) ? $_POST['start_time'] : null,
            'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
            'end_time' => !empty($_POST['end_time']) ? $_POST['end_time'] : null,
            'is_all_day' => isset($_POST['is_all_day']) ? 1 : 0,
            'status' => $_POST['status'] ?? 'draft',
            'visibility' => $_POST['visibility'] ?? 'public',
            'rsvp_enabled' => isset($_POST['rsvp_enabled']) ? 1 : 0,
            'rsvp_required' => isset($_POST['rsvp_required']) ? 1 : 0,
            'rsvp_deadline' => !empty($_POST['rsvp_deadline']) ? $_POST['rsvp_deadline'] : null,
            'max_attendees' => !empty($_POST['max_attendees']) ? $_POST['max_attendees'] : null,
            'allow_guests' => isset($_POST['allow_guests']) ? 1 : 0,
            'max_guests_per_attendee' => !empty($_POST['max_guests_per_attendee']) ? $_POST['max_guests_per_attendee'] : 0,
            'requires_planning' => isset($_POST['requires_planning']) ? 1 : 0,
            'planning_start_date' => !empty($_POST['planning_start_date']) ? $_POST['planning_start_date'] : null,
            'updated_by' => $userId
        ];

        // Handle status change to published
        if ($data['status'] === 'published' && $event['status'] !== 'published') {
            $data['published_at'] = date('Y-m-d H:i:s');
        }

        // Handle cancellation
        if ($data['status'] === 'cancelled' && $event['status'] !== 'cancelled') {
            $data['cancelled_at'] = date('Y-m-d H:i:s');
            $data['cancellation_reason'] = $_POST['cancellation_reason'] ?? null;
        }

        try {
            $stmt = $this->pdo->prepare("
                UPDATE events SET
                    term_id = :term_id,
                    event_type_id = :event_type_id,
                    title = :title,
                    description = :description,
                    venue = :venue,
                    start_date = :start_date,
                    start_time = :start_time,
                    end_date = :end_date,
                    end_time = :end_time,
                    is_all_day = :is_all_day,
                    status = :status,
                    visibility = :visibility,
                    rsvp_enabled = :rsvp_enabled,
                    rsvp_required = :rsvp_required,
                    rsvp_deadline = :rsvp_deadline,
                    max_attendees = :max_attendees,
                    allow_guests = :allow_guests,
                    max_guests_per_attendee = :max_guests_per_attendee,
                    requires_planning = :requires_planning,
                    planning_start_date = :planning_start_date,
                    updated_by = :updated_by
                WHERE id = :id
            ");

            $stmt->execute($data);

            if (Request::isAjax()) {
                Response::json([
                    'success' => true,
                    'message' => 'Event updated successfully'
                ]);
                return;
            }

            flash('success', 'Event updated successfully');
            Response::redirect('/calendar/events');

        } catch (Exception $e) {
            if (Request::isAjax()) {
                Response::json([
                    'success' => false,
                    'message' => 'Failed to update event: ' . $e->getMessage()
                ]);
                return;
            }

            flash('error', 'Failed to update event: ' . $e->getMessage());
            Response::redirect('/calendar/events');
        }
    }

    /**
     * Delete event
     */
    public function delete($id)
    {
        if (!isAuthenticated()) {
            Response::json(['success' => false, 'message' => 'Not authenticated']);
            return;
        }

        $tenantId = $_SESSION['tenant_id'] ?? null;

        // Verify event exists and belongs to this school
        $event = $this->getEventById($id, $tenantId);
        if (!$event) {
            Response::json(['success' => false, 'message' => 'Event not found']);
            return;
        }

        // Check if event can be deleted
        if ($event['status'] === 'completed') {
            Response::json(['success' => false, 'message' => 'Cannot delete completed events']);
            return;
        }

        $stmt = $this->pdo->prepare("DELETE FROM events WHERE id = ? AND school_id = ?");

        if ($stmt->execute([$id, $tenantId])) {
            Response::json(['success' => true, 'message' => 'Event deleted successfully']);
        } else {
            Response::json(['success' => false, 'message' => 'Failed to delete event']);
        }
    }

    // =============================================================================
    // HELPER METHODS
    // =============================================================================

    private function getEvents($tenantId, $status = 'all', $type = 'all', $term = 'all')
    {
        $sql = "
            SELECT e.*,
                   tdt.name as type_name,
                   tdt.category,
                   tdt.color,
                   tdt.icon,
                   t.term_name,
                   (SELECT COUNT(*) FROM event_rsvps WHERE event_id = e.id AND response = 'attending') as rsvp_count
            FROM events e
            JOIN term_date_types tdt ON e.event_type_id = tdt.id
            LEFT JOIN terms t ON e.term_id = t.id
            WHERE e.school_id = ?
        ";
        $params = [$tenantId];

        if ($status !== 'all') {
            $sql .= " AND e.status = ?";
            $params[] = $status;
        }

        if ($type !== 'all') {
            $sql .= " AND e.event_type_id = ?";
            $params[] = $type;
        }

        if ($term !== 'all') {
            $sql .= " AND e.term_id = ?";
            $params[] = $term;
        }

        $sql .= " ORDER BY e.start_date DESC, e.start_time DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getEventById($id, $tenantId)
    {
        $stmt = $this->pdo->prepare("
            SELECT e.*,
                   tdt.name as type_name,
                   tdt.category,
                   tdt.color,
                   tdt.icon,
                   t.term_name,
                   u.full_name as created_by_name
            FROM events e
            JOIN term_date_types tdt ON e.event_type_id = tdt.id
            LEFT JOIN terms t ON e.term_id = t.id
            LEFT JOIN users u ON e.created_by = u.id
            WHERE e.id = ? AND e.school_id = ?
        ");
        $stmt->execute([$id, $tenantId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getEventTypes()
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM term_date_types
            WHERE category = 'events' AND is_active = 1
            ORDER BY sort_order
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getAcademicTerms($tenantId)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM terms
            WHERE school_id = ?
            ORDER BY start_date DESC
        ");
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getStaff($tenantId)
    {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT u.id, u.full_name as name, u.email, u.username
            FROM users u
            INNER JOIN user_roles ur ON u.id = ur.user_id
            INNER JOIN roles r ON ur.role_id = r.id
            WHERE u.status = 'active'
            AND r.name IN ('ADMIN', 'HEAD_TEACHER', 'TEACHER', 'CLERK', 'BURSAR')
            ORDER BY u.full_name
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getDepartments($tenantId)
    {
        // Placeholder - implement based on your department structure
        return [];
    }

    private function getEventAudiences($eventId)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM event_audiences
            WHERE event_id = ?
            ORDER BY id
        ");
        $stmt->execute([$eventId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getEventOwners($eventId)
    {
        $stmt = $this->pdo->prepare("
            SELECT eo.*, u.full_name as owner_name
            FROM event_owners eo
            LEFT JOIN users u ON eo.owner_id = u.id AND eo.owner_type = 'staff'
            WHERE eo.event_id = ?
            ORDER BY eo.is_primary DESC
        ");
        $stmt->execute([$eventId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getEventRSVPs($eventId)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM event_rsvps
            WHERE event_id = ?
            ORDER BY rsvp_date DESC
        ");
        $stmt->execute([$eventId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getEventRSVPCount($eventId)
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count
            FROM event_rsvps
            WHERE event_id = ? AND response = 'attending'
        ");
        $stmt->execute([$eventId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }

    private function getEventPlanningTasks($eventId)
    {
        $stmt = $this->pdo->prepare("
            SELECT ept.*, u.full_name as assigned_to_name
            FROM event_planning_tasks ept
            LEFT JOIN users u ON ept.assigned_to = u.id
            WHERE ept.event_id = ?
            ORDER BY ept.due_date ASC, ept.priority DESC
        ");
        $stmt->execute([$eventId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getEventAttendance($eventId)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM event_attendance
            WHERE event_id = ?
            ORDER BY checkin_time DESC
        ");
        $stmt->execute([$eventId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getEventComments($eventId)
    {
        $stmt = $this->pdo->prepare("
            SELECT ec.*, u.full_name as author_name
            FROM event_comments ec
            LEFT JOIN users u ON ec.author_id = u.id
            WHERE ec.event_id = ?
            ORDER BY ec.created_at DESC
        ");
        $stmt->execute([$eventId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getEventAttachments($eventId)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM event_attachments
            WHERE event_id = ?
            ORDER BY uploaded_at DESC
        ");
        $stmt->execute([$eventId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getEventStats($tenantId)
    {
        $stmt = $this->pdo->prepare("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN start_date >= CURDATE() THEN 1 ELSE 0 END) as upcoming
            FROM events
            WHERE school_id = ?
        ");
        $stmt->execute([$tenantId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function calculateRSVPStats($rsvps, $event)
    {
        $stats = [
            'total_responses' => count($rsvps),
            'attending' => 0,
            'not_attending' => 0,
            'maybe' => 0,
            'guests' => 0,
            'attendance_rate' => 0
        ];

        foreach ($rsvps as $rsvp) {
            $stats[$rsvp['response']]++;
            $stats['guests'] += $rsvp['guest_count'];
        }

        if ($stats['total_responses'] > 0) {
            $stats['attendance_rate'] = round(($stats['attending'] / $stats['total_responses']) * 100, 1);
        }

        // Calculate capacity usage if max_attendees is set
        if ($event['max_attendees']) {
            $totalAttendees = $stats['attending'] + $stats['guests'];
            $stats['capacity_used'] = $totalAttendees;
            $stats['capacity_percentage'] = round(($totalAttendees / $event['max_attendees']) * 100, 1);
            $stats['capacity_remaining'] = $event['max_attendees'] - $totalAttendees;
        }

        return $stats;
    }

    private function addEventOwner($eventId, $ownerType, $ownerId, $isPrimary = false)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO event_owners (event_id, owner_type, owner_id, is_primary, can_edit, can_manage_rsvp, can_view_reports, can_send_notifications)
            VALUES (?, ?, ?, ?, 1, 1, 1, 1)
        ");
        $stmt->execute([$eventId, $ownerType, $ownerId, $isPrimary ? 1 : 0]);
    }

    private function addEventAudience($eventId, $audienceData)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO event_audiences (event_id, audience_type, config, can_view, can_rsvp)
            VALUES (?, ?, ?, 1, 1)
        ");
        $stmt->execute([
            $eventId,
            $audienceData['type'],
            isset($audienceData['config']) ? json_encode($audienceData['config']) : null
        ]);
    }
}
