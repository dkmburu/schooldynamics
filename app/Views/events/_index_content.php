<?php
$now = new DateTime();
?>

<div class="container-xl">
    <!-- Page header -->
    <div class="page-header d-print-none">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <i class="ti ti-calendar-event me-2"></i>
                    Events Management
                </h2>
                <div class="text-muted mt-1">
                    Manage school events, activities, and special occasions
                </div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <a href="/calendar" class="btn btn-outline-secondary">
                        <i class="ti ti-calendar me-1"></i>
                        Calendar View
                    </a>
                    <button type="button" class="btn btn-primary" onclick="openCreateEventModal()">
                        <i class="ti ti-plus me-1"></i>
                        Create Event
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <!-- Statistics Cards -->
        <div class="row row-cards mb-3">
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Total Events</div>
                            <div class="ms-auto lh-1">
                                <span class="avatar avatar-sm bg-primary-lt">
                                    <i class="ti ti-calendar-event"></i>
                                </span>
                            </div>
                        </div>
                        <div class="h1 mb-0"><?= $stats['total'] ?? 0 ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Upcoming</div>
                            <div class="ms-auto lh-1">
                                <span class="avatar avatar-sm bg-info-lt">
                                    <i class="ti ti-clock"></i>
                                </span>
                            </div>
                        </div>
                        <div class="h1 mb-0"><?= $stats['upcoming'] ?? 0 ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Published</div>
                            <div class="ms-auto lh-1">
                                <span class="avatar avatar-sm bg-success-lt">
                                    <i class="ti ti-check"></i>
                                </span>
                            </div>
                        </div>
                        <div class="h1 mb-0"><?= $stats['published'] ?? 0 ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Draft</div>
                            <div class="ms-auto lh-1">
                                <span class="avatar avatar-sm bg-secondary-lt">
                                    <i class="ti ti-file"></i>
                                </span>
                            </div>
                        </div>
                        <div class="h1 mb-0"><?= $stats['draft'] ?? 0 ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="/calendar/events" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="all" <?= $currentStatus === 'all' ? 'selected' : '' ?>>All Status</option>
                            <option value="draft" <?= $currentStatus === 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="published" <?= $currentStatus === 'published' ? 'selected' : '' ?>>Published</option>
                            <option value="cancelled" <?= $currentStatus === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            <option value="completed" <?= $currentStatus === 'completed' ? 'selected' : '' ?>>Completed</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Event Type</label>
                        <select name="type" class="form-select" onchange="this.form.submit()">
                            <option value="all" <?= $currentType === 'all' ? 'selected' : '' ?>>All Types</option>
                            <?php foreach ($eventTypes as $type): ?>
                                <option value="<?= $type['id'] ?>" <?= $currentType == $type['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($type['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Academic Term</label>
                        <select name="term" class="form-select" onchange="this.form.submit()">
                            <option value="all" <?= $currentTerm === 'all' ? 'selected' : '' ?>>All Terms</option>
                            <?php foreach ($terms as $term): ?>
                                <option value="<?= $term['id'] ?>" <?= $currentTerm == $term['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($term['term_name']) ?> (<?= htmlspecialchars($term['academic_year']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">View</label>
                        <select name="view" class="form-select" onchange="this.form.submit()">
                            <option value="list" <?= $currentView === 'list' ? 'selected' : '' ?>>List View</option>
                            <option value="cards" <?= $currentView === 'cards' ? 'selected' : '' ?>>Card View</option>
                            <option value="upcoming" <?= $currentView === 'upcoming' ? 'selected' : '' ?>>Upcoming Only</option>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <!-- Events List/Cards -->
        <?php if (empty($events)): ?>
            <div class="card">
                <div class="empty">
                    <div class="empty-icon">
                        <i class="ti ti-calendar-off"></i>
                    </div>
                    <p class="empty-title">No events found</p>
                    <p class="empty-subtitle text-muted">
                        Create your first event to get started
                    </p>
                    <div class="empty-action">
                        <a href="/calendar/events/create" class="btn btn-primary">
                            <i class="ti ti-plus"></i>
                            Create Event
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php if ($currentView === 'cards'): ?>
                <!-- Card View -->
                <div class="row row-cards">
                    <?php foreach ($events as $event): ?>
                        <?php
                        $startDate = new DateTime($event['start_date']);
                        $endDate = $event['end_date'] ? new DateTime($event['end_date']) : null;
                        $isUpcoming = $startDate >= $now;
                        $isPast = $startDate < $now;
                        $statusColors = [
                            'draft' => 'secondary',
                            'published' => 'success',
                            'cancelled' => 'danger',
                            'completed' => 'dark'
                        ];
                        $statusColor = $statusColors[$event['status']] ?? 'secondary';
                        ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex align-items-start mb-3">
                                        <div class="me-3">
                                            <span class="avatar avatar-md" style="background-color: <?= $event['color'] ?>15; color: <?= $event['color'] ?>;">
                                                <i class="ti <?= $event['icon'] ?>"></i>
                                            </span>
                                        </div>
                                        <div class="flex-fill">
                                            <h3 class="card-title mb-1">
                                                <a href="/calendar/events/<?= $event['id'] ?>" class="text-reset">
                                                    <?= htmlspecialchars($event['title']) ?>
                                                </a>
                                            </h3>
                                            <div class="text-muted small">
                                                <span class="badge badge-outline" style="border-color: <?= $event['color'] ?>; color: <?= $event['color'] ?>;">
                                                    <?= htmlspecialchars($event['type_name']) ?>
                                                </span>
                                                <span class="badge bg-<?= $statusColor ?> ms-1">
                                                    <?= ucfirst($event['status']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-muted small mb-2">
                                        <i class="ti ti-calendar me-1"></i>
                                        <?= $startDate->format('M j, Y') ?>
                                        <?php if ($event['start_time']): ?>
                                            at <?= date('g:i A', strtotime($event['start_time'])) ?>
                                        <?php endif; ?>
                                        <?php if ($endDate && $endDate != $startDate): ?>
                                            <br>to <?= $endDate->format('M j, Y') ?>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($event['venue']): ?>
                                        <div class="text-muted small mb-2">
                                            <i class="ti ti-map-pin me-1"></i>
                                            <?= htmlspecialchars($event['venue']) ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($event['rsvp_enabled']): ?>
                                        <div class="text-muted small mb-2">
                                            <i class="ti ti-users me-1"></i>
                                            <?= $event['rsvp_count'] ?> RSVP<?= $event['rsvp_count'] != 1 ? 's' : '' ?>
                                            <?php if ($event['max_attendees']): ?>
                                                / <?= $event['max_attendees'] ?> max
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($isUpcoming): ?>
                                        <div class="text-info small">
                                            <i class="ti ti-clock me-1"></i>
                                            <?php
                                            $daysUntil = $now->diff($startDate)->days;
                                            if ($daysUntil == 0) {
                                                echo 'Today';
                                            } elseif ($daysUntil == 1) {
                                                echo 'Tomorrow';
                                            } else {
                                                echo 'In ' . $daysUntil . ' days';
                                            }
                                            ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="mt-3">
                                        <a href="/calendar/events/<?= $event['id'] ?>" class="btn btn-sm btn-outline-primary w-100">
                                            <i class="ti ti-eye me-1"></i>
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- List View -->
                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Event</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Venue</th>
                                    <th>Status</th>
                                    <th>RSVPs</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($events as $event): ?>
                                    <?php
                                    $startDate = new DateTime($event['start_date']);
                                    $endDate = $event['end_date'] ? new DateTime($event['end_date']) : null;
                                    $isUpcoming = $startDate >= $now;
                                    $statusColors = [
                                        'draft' => 'secondary',
                                        'published' => 'success',
                                        'cancelled' => 'danger',
                                        'completed' => 'dark'
                                    ];
                                    $statusColor = $statusColors[$event['status']] ?? 'secondary';
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="avatar me-2" style="background-color: <?= $event['color'] ?>15; color: <?= $event['color'] ?>;">
                                                    <i class="ti <?= $event['icon'] ?>"></i>
                                                </span>
                                                <div>
                                                    <a href="/calendar/events/<?= $event['id'] ?>" class="text-reset fw-bold">
                                                        <?= htmlspecialchars($event['title']) ?>
                                                    </a>
                                                    <?php if ($event['term_name']): ?>
                                                        <div class="text-muted small">
                                                            <?= htmlspecialchars($event['term_name']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-outline" style="border-color: <?= $event['color'] ?>; color: <?= $event['color'] ?>;">
                                                <?= htmlspecialchars($event['type_name']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div><?= $startDate->format('M j, Y') ?></div>
                                            <?php if ($event['start_time']): ?>
                                                <div class="text-muted small"><?= date('g:i A', strtotime($event['start_time'])) ?></div>
                                            <?php endif; ?>
                                            <?php if ($isUpcoming): ?>
                                                <div class="text-info small">
                                                    <?php
                                                    $daysUntil = $now->diff($startDate)->days;
                                                    if ($daysUntil == 0) {
                                                        echo 'Today';
                                                    } elseif ($daysUntil == 1) {
                                                        echo 'Tomorrow';
                                                    } else {
                                                        echo 'In ' . $daysUntil . ' days';
                                                    }
                                                    ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($event['venue']): ?>
                                                <div class="text-muted">
                                                    <i class="ti ti-map-pin me-1"></i>
                                                    <?= htmlspecialchars($event['venue']) ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $statusColor ?>">
                                                <?= ucfirst($event['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($event['rsvp_enabled']): ?>
                                                <div>
                                                    <?= $event['rsvp_count'] ?> attending
                                                </div>
                                                <?php if ($event['max_attendees']): ?>
                                                    <div class="text-muted small">
                                                        <?= $event['max_attendees'] ?> max
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="ti ti-dots-vertical"></i>
                                                    Actions
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item" href="javascript:void(0)" onclick="viewEvent(<?= $event['id'] ?>)">
                                                            <i class="ti ti-eye me-2"></i>
                                                            View Details
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="javascript:void(0)" onclick="editEvent(<?= $event['id'] ?>)">
                                                            <i class="ti ti-edit me-2"></i>
                                                            Edit Event
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="javascript:void(0)" onclick="deleteEvent(<?= $event['id'] ?>, '<?= htmlspecialchars($event['title'], ENT_QUOTES) ?>')">
                                                            <i class="ti ti-trash me-2"></i>
                                                            Delete Event
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- View Event Modal -->
<div class="modal modal-blur fade" id="viewEventModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Event Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="viewEventContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="switchToEditMode()">
                    <i class="ti ti-edit me-1"></i>
                    Edit Event
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Event Modal -->
<div class="modal modal-blur fade" id="eventFormModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document" style="max-height: 90vh;">
        <div class="modal-content">
            <form id="eventForm" onsubmit="return handleEventSubmit(event)">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventFormTitle">Create Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="eventFormContent" style="max-height: calc(90vh - 140px); overflow-y: auto;">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="eventFormSubmitBtn">
                        <i class="ti ti-check me-1"></i>
                        <span id="eventFormSubmitText">Create Event</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Current event data being viewed/edited
let currentEventId = null;
let currentEventData = null;

/**
 * View event details in modal
 */
function viewEvent(eventId) {
    currentEventId = eventId;

    // Show loading state
    const viewContent = document.getElementById('viewEventContent');
    if (viewContent) {
        viewContent.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;
    }

    // Show modal
    const modalElement = document.getElementById('viewEventModal');
    if (modalElement && typeof bootstrap !== 'undefined') {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }

    // Fetch event details
    fetch(`/calendar/events/${eventId}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            currentEventData = data.event;
            renderEventDetails(data.event);
        } else {
            viewContent.innerHTML = `
                <div class="alert alert-danger">
                    <i class="ti ti-alert-circle me-2"></i>
                    ${data.message || 'Failed to load event details'}
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error fetching event:', error);
        viewContent.innerHTML = `
            <div class="alert alert-danger">
                <i class="ti ti-alert-circle me-2"></i>
                An error occurred while loading event details.
            </div>
        `;
    });
}

/**
 * Render event details in view modal
 */
function renderEventDetails(event) {
    const startDate = new Date(event.start_date);
    const endDate = event.end_date ? new Date(event.end_date) : null;
    const startDateStr = startDate.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    const endDateStr = endDate ? endDate.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) : null;

    const statusColors = {
        'draft': 'secondary',
        'published': 'success',
        'cancelled': 'danger',
        'completed': 'dark'
    };
    const statusColor = statusColors[event.status] || 'secondary';

    let content = `
        <div class="card-body p-0">
            <div class="row g-3">
                <div class="col-auto">
                    <span class="avatar avatar-lg" style="background-color: ${event.color}15; color: ${event.color};">
                        <i class="ti ${event.icon || 'ti-calendar-event'}"></i>
                    </span>
                </div>
                <div class="col">
                    <h3 class="mb-1">${event.title}</h3>
                    <div class="text-muted">
                        <span class="badge badge-outline" style="border-color: ${event.color}; color: ${event.color};">
                            ${event.type_name}
                        </span>
                        <span class="badge bg-${statusColor} ms-1">
                            ${event.status.charAt(0).toUpperCase() + event.status.slice(1)}
                        </span>
                        ${event.visibility === 'private' ? '<span class="badge bg-warning ms-1"><i class="ti ti-lock me-1"></i>Private</span>' : ''}
                    </div>
                </div>
            </div>

            <hr class="my-3">

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="mb-2">
                        <label class="form-label fw-bold">Start Date</label>
                        <div class="text-muted">
                            <i class="ti ti-calendar me-1"></i>
                            ${startDateStr}
                            ${event.start_time ? ' at ' + formatTime(event.start_time) : ''}
                        </div>
                    </div>
                </div>
                ${endDate ? `
                <div class="col-md-6">
                    <div class="mb-2">
                        <label class="form-label fw-bold">End Date</label>
                        <div class="text-muted">
                            <i class="ti ti-calendar me-1"></i>
                            ${endDateStr}
                            ${event.end_time ? ' at ' + formatTime(event.end_time) : ''}
                        </div>
                    </div>
                </div>
                ` : ''}
            </div>

            ${event.venue ? `
            <div class="mt-3">
                <label class="form-label fw-bold">Venue</label>
                <div class="text-muted">
                    <i class="ti ti-map-pin me-1"></i>
                    ${event.venue}
                </div>
            </div>
            ` : ''}

            ${event.description ? `
            <div class="mt-3">
                <label class="form-label fw-bold">Description</label>
                <div class="text-muted">${event.description}</div>
            </div>
            ` : ''}

            ${event.term_name ? `
            <div class="mt-3">
                <label class="form-label fw-bold">Academic Term</label>
                <div class="text-muted">
                    <i class="ti ti-school me-1"></i>
                    ${event.term_name}
                </div>
            </div>
            ` : ''}

            ${event.rsvp_enabled ? `
            <div class="mt-3">
                <label class="form-label fw-bold">RSVP Information</label>
                <div class="row">
                    <div class="col-auto">
                        <div class="text-muted">
                            <i class="ti ti-users me-1"></i>
                            ${event.rsvp_count || 0} attending
                        </div>
                    </div>
                    ${event.max_attendees ? `
                    <div class="col-auto">
                        <div class="text-muted">
                            <i class="ti ti-ticket me-1"></i>
                            Max capacity: ${event.max_attendees}
                        </div>
                    </div>
                    ` : ''}
                </div>
            </div>
            ` : ''}

            ${event.created_by_name ? `
            <div class="mt-3">
                <label class="form-label fw-bold">Created By</label>
                <div class="text-muted">
                    <i class="ti ti-user me-1"></i>
                    ${event.created_by_name}
                </div>
            </div>
            ` : ''}
        </div>
    `;

    const viewContent = document.getElementById('viewEventContent');
    if (viewContent) {
        viewContent.innerHTML = content;
    }
}

/**
 * Create new event (open modal)
 */
function openCreateEventModal() {
    currentEventId = null;
    currentEventData = null;

    // Set modal title
    document.getElementById('eventFormTitle').textContent = 'Create Event';
    document.getElementById('eventFormSubmitText').textContent = 'Create Event';

    // Show loading state
    const formContent = document.getElementById('eventFormContent');
    if (formContent) {
        formContent.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;
    }

    // Show modal
    const modalElement = document.getElementById('eventFormModal');
    if (modalElement && typeof bootstrap !== 'undefined') {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }

    // Fetch form data (event types and terms)
    fetch('/calendar/events/create', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            renderEventForm(null, data.eventTypes || [], data.terms || []);
        } else {
            formContent.innerHTML = `
                <div class="alert alert-danger">
                    <i class="ti ti-alert-circle me-2"></i>
                    ${data.message || 'Failed to load form data'}
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error loading form:', error);
        formContent.innerHTML = `
            <div class="alert alert-danger">
                <i class="ti ti-alert-circle me-2"></i>
                An error occurred while loading the form.
            </div>
        `;
    });
}

/**
 * Edit event in modal
 */
function editEvent(eventId) {
    currentEventId = eventId;

    // Set modal title
    document.getElementById('eventFormTitle').textContent = 'Edit Event';
    document.getElementById('eventFormSubmitText').textContent = 'Save Changes';

    // Show loading state
    const formContent = document.getElementById('eventFormContent');
    if (formContent) {
        formContent.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;
    }

    // Show modal
    const modalElement = document.getElementById('eventFormModal');
    if (modalElement && typeof bootstrap !== 'undefined') {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }

    // Fetch event details
    fetch(`/calendar/events/${eventId}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            currentEventData = data.event;
            renderEventForm(data.event, data.eventTypes || [], data.terms || []);
        } else {
            formContent.innerHTML = `
                <div class="alert alert-danger">
                    <i class="ti ti-alert-circle me-2"></i>
                    ${data.message || 'Failed to load event details'}
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error fetching event:', error);
        formContent.innerHTML = `
            <div class="alert alert-danger">
                <i class="ti ti-alert-circle me-2"></i>
                An error occurred while loading event details.
            </div>
        `;
    });
}

/**
 * Render event form (create or edit)
 */
function renderEventForm(event, eventTypes, terms) {
    const isEdit = event !== null;
    const formContent = document.getElementById('eventFormContent');

    if (!formContent) return;

    // Build form HTML
    const html = `
        <div class="row g-3">
            <!-- Basic Information -->
            <div class="col-12">
                <h3 class="card-title">Basic Information</h3>
            </div>

            <div class="col-md-8">
                <label class="form-label required">Event Title</label>
                <input type="text" class="form-control" name="title" required
                       value="${event?.title || ''}" placeholder="Enter event title">
            </div>

            <div class="col-md-4">
                <label class="form-label required">Event Type</label>
                <select class="form-select" name="event_type_id" required>
                    <option value="">Select type...</option>
                    ${eventTypes.map(type => `
                        <option value="${type.id}" ${event?.event_type_id == type.id ? 'selected' : ''}>
                            ${type.name}
                        </option>
                    `).join('')}
                </select>
            </div>

            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" rows="3"
                          placeholder="Enter event description">${event?.description || ''}</textarea>
            </div>

            <!-- Date & Time -->
            <div class="col-12 mt-4">
                <h3 class="card-title">Date & Time</h3>
            </div>

            <div class="col-md-6">
                <label class="form-label required">Start Date</label>
                <input type="date" class="form-control" name="start_date" required
                       value="${event?.start_date || ''}">
            </div>

            <div class="col-md-6">
                <label class="form-label">Start Time</label>
                <input type="time" class="form-control" name="start_time"
                       value="${event?.start_time || ''}" id="startTime">
            </div>

            <div class="col-md-6">
                <label class="form-label">End Date</label>
                <input type="date" class="form-control" name="end_date"
                       value="${event?.end_date || ''}">
            </div>

            <div class="col-md-6">
                <label class="form-label">End Time</label>
                <input type="time" class="form-control" name="end_time"
                       value="${event?.end_time || ''}" id="endTime">
            </div>

            <div class="col-12">
                <label class="form-check">
                    <input type="checkbox" class="form-check-input" name="is_all_day"
                           ${event?.is_all_day ? 'checked' : ''} onchange="toggleAllDay(this)">
                    <span class="form-check-label">All-day event</span>
                </label>
            </div>

            <!-- Location & Term -->
            <div class="col-12 mt-4">
                <h3 class="card-title">Location & Term</h3>
            </div>

            <div class="col-md-6">
                <label class="form-label">Venue</label>
                <input type="text" class="form-control" name="venue"
                       value="${event?.venue || ''}" placeholder="Enter venue">
            </div>

            <div class="col-md-6">
                <label class="form-label">Academic Term</label>
                <select class="form-select" name="term_id">
                    <option value="">Not linked to term</option>
                    ${terms.map(term => `
                        <option value="${term.id}" ${event?.term_id == term.id ? 'selected' : ''}>
                            ${term.term_name} (${term.academic_year})
                        </option>
                    `).join('')}
                </select>
            </div>

            <!-- Event Settings -->
            <div class="col-12 mt-4">
                <h3 class="card-title">Event Settings</h3>
            </div>

            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <option value="draft" ${event?.status === 'draft' ? 'selected' : ''}>Draft</option>
                    <option value="published" ${event?.status === 'published' ? 'selected' : ''}>Published</option>
                    <option value="cancelled" ${event?.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                    <option value="completed" ${event?.status === 'completed' ? 'selected' : ''}>Completed</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Visibility</label>
                <select class="form-select" name="visibility">
                    <option value="public" ${event?.visibility === 'public' ? 'selected' : ''}>Public</option>
                    <option value="private" ${event?.visibility === 'private' ? 'selected' : ''}>Private</option>
                </select>
            </div>

            <!-- RSVP Settings -->
            <div class="col-12 mt-4">
                <h3 class="card-title">RSVP Settings</h3>
            </div>

            <div class="col-12">
                <label class="form-check form-switch">
                    <input type="checkbox" class="form-check-input" name="rsvp_enabled"
                           ${event?.rsvp_enabled ? 'checked' : ''} onchange="toggleRSVPFields(this)">
                    <span class="form-check-label">Enable RSVP for this event</span>
                </label>
            </div>

            <div id="rsvpFields" class="col-12" style="display: ${event?.rsvp_enabled ? 'block' : 'none'}">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Maximum Attendees</label>
                        <input type="number" class="form-control" name="max_attendees" min="1"
                               value="${event?.max_attendees || ''}" placeholder="No limit">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">RSVP Deadline</label>
                        <input type="date" class="form-control" name="rsvp_deadline"
                               value="${event?.rsvp_deadline || ''}">
                    </div>

                    <div class="col-12">
                        <label class="form-check">
                            <input type="checkbox" class="form-check-input" name="rsvp_required"
                                   ${event?.rsvp_required ? 'checked' : ''}>
                            <span class="form-check-label">RSVP required (mandatory)</span>
                        </label>
                    </div>

                    <div class="col-12">
                        <label class="form-check">
                            <input type="checkbox" class="form-check-input" name="allow_guests"
                                   ${event?.allow_guests ? 'checked' : ''} onchange="toggleGuestFields(this)">
                            <span class="form-check-label">Allow attendees to bring guests</span>
                        </label>
                    </div>

                    <div id="guestFields" style="display: ${event?.allow_guests ? 'block' : 'none'}">
                        <div class="col-md-4">
                            <label class="form-label">Max Guests Per Attendee</label>
                            <input type="number" class="form-control" name="max_guests_per_attendee" min="1" max="10"
                                   value="${event?.max_guests_per_attendee || 2}">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Planning Settings -->
            <div class="col-12 mt-4">
                <h3 class="card-title">Planning Settings</h3>
            </div>

            <div class="col-12">
                <label class="form-check form-switch">
                    <input type="checkbox" class="form-check-input" name="requires_planning"
                           ${event?.requires_planning ? 'checked' : ''} onchange="togglePlanningFields(this)">
                    <span class="form-check-label">This event requires planning and task management</span>
                </label>
            </div>

            <div id="planningFields" style="display: ${event?.requires_planning ? 'block' : 'none'}">
                <div class="col-md-6 mt-3">
                    <label class="form-label">Planning Start Date</label>
                    <input type="date" class="form-control" name="planning_start_date"
                           value="${event?.planning_start_date || ''}">
                    <small class="form-hint">When should planning tasks begin?</small>
                </div>
            </div>
        </div>
    `;

    formContent.innerHTML = html;
}

/**
 * Toggle all-day event
 */
function toggleAllDay(checkbox) {
    const startTime = document.getElementById('startTime');
    const endTime = document.getElementById('endTime');

    if (startTime && endTime) {
        startTime.disabled = checkbox.checked;
        endTime.disabled = checkbox.checked;
        if (checkbox.checked) {
            startTime.value = '';
            endTime.value = '';
        }
    }
}

/**
 * Toggle RSVP fields
 */
function toggleRSVPFields(checkbox) {
    const rsvpFields = document.getElementById('rsvpFields');
    if (rsvpFields) {
        rsvpFields.style.display = checkbox.checked ? 'block' : 'none';
    }
}

/**
 * Toggle guest fields
 */
function toggleGuestFields(checkbox) {
    const guestFields = document.getElementById('guestFields');
    if (guestFields) {
        guestFields.style.display = checkbox.checked ? 'block' : 'none';
    }
}

/**
 * Toggle planning fields
 */
function togglePlanningFields(checkbox) {
    const planningFields = document.getElementById('planningFields');
    if (planningFields) {
        planningFields.style.display = checkbox.checked ? 'block' : 'none';
    }
}

/**
 * Handle event form submission
 */
function handleEventSubmit(e) {
    e.preventDefault();

    const form = document.getElementById('eventForm');
    const formData = new FormData(form);
    const submitBtn = document.getElementById('eventFormSubmitBtn');

    // Disable submit button
    submitBtn.disabled = true;
    const originalText = document.getElementById('eventFormSubmitText').textContent;
    document.getElementById('eventFormSubmitText').textContent = 'Saving...';

    const url = currentEventId ? `/calendar/events/${currentEventId}/edit` : '/calendar/events/create';
    const method = 'POST';

    fetch(url, {
        method: method,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', data.message || (currentEventId ? 'Event updated successfully' : 'Event created successfully'));

            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('eventFormModal'));
            if (modal) {
                modal.hide();
            }

            // Reload page after short delay
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast('error', data.message || 'Failed to save event');
            submitBtn.disabled = false;
            document.getElementById('eventFormSubmitText').textContent = originalText;
        }
    })
    .catch(error => {
        console.error('Error saving event:', error);
        showToast('error', 'An error occurred while saving the event');
        submitBtn.disabled = false;
        document.getElementById('eventFormSubmitText').textContent = originalText;
    });

    return false;
}

/**
 * Switch from view to edit mode
 */
function switchToEditMode() {
    if (!currentEventId) return;

    // Close view modal
    const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewEventModal'));
    if (viewModal) {
        viewModal.hide();
    }

    // Open edit modal
    setTimeout(() => {
        editEvent(currentEventId);
    }, 300);
}

/**
 * Delete event
 */
function deleteEvent(eventId, eventTitle) {
    if (!confirm(`Are you sure you want to delete the event "${eventTitle}"? This action cannot be undone.`)) {
        return;
    }

    fetch(`/calendar/events/${eventId}/delete`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', data.message || 'Event deleted successfully');
            // Reload page after short delay
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast('error', data.message || 'Failed to delete event');
        }
    })
    .catch(error => {
        console.error('Error deleting event:', error);
        showToast('error', 'An error occurred while deleting the event');
    });
}


/**
 * Format time to 12-hour format
 */
function formatTime(timeString) {
    if (!timeString) return '';
    const [hours, minutes] = timeString.split(':');
    const date = new Date();
    date.setHours(parseInt(hours));
    date.setMinutes(parseInt(minutes));
    return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
}

/**
 * Show toast notification
 */
function showToast(type, message) {
    const toast = document.createElement('div');
    toast.className = 'alert alert-' + (type === 'success' ? 'success' : 'danger') + ' position-fixed';
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 250px;';
    toast.innerHTML = '<i class="ti ti-' + (type === 'success' ? 'check' : 'alert-circle') + ' me-2"></i>' + message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}
</script>
