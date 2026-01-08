<?php
$startDate = new DateTime($term['start_date']);
$endDate = new DateTime($term['end_date']);
$now = new DateTime();
$isActive = $now >= $startDate && $now <= $endDate;

$statusColors = [
    'draft' => 'secondary',
    'published' => 'info',
    'current' => 'success',
    'completed' => 'dark'
];
$statusColor = $statusColors[$term['status']] ?? 'secondary';
?>

<div class="container-xl">
    <!-- Page header -->
    <div class="page-header d-print-none">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">
                    <a href="/calendar/terms" class="text-muted">
                        <i class="ti ti-arrow-left me-1"></i>
                        Back to Academic Terms
                    </a>
                </div>
                <h2 class="page-title">
                    <?= htmlspecialchars($term['term_name']) ?>
                    <span class="badge bg-<?= $statusColor ?> ms-2"><?= ucfirst($term['status']) ?></span>
                    <?php if ($term['is_current']): ?>
                        <span class="badge bg-success ms-1">Current Term</span>
                    <?php endif; ?>
                </h2>
                <div class="text-muted mt-1">
                    Academic Year: <?= htmlspecialchars($term['academic_year']) ?>
                </div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <a href="/calendar/terms/<?= $term['id'] ?>/edit" class="btn btn-primary">
                        <i class="ti ti-edit me-1"></i>
                        Edit Term
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Term Overview -->
    <div class="page-body">
        <div class="row row-cards mb-3">
            <!-- Term Dates Card -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-calendar-event me-2"></i>
                            Term Dates
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label text-muted">Start Date</label>
                                    <div class="h3 mb-0">
                                        <?= $startDate->format('l, F j, Y') ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label text-muted">End Date</label>
                                    <div class="h3 mb-0">
                                        <?= $endDate->format('l, F j, Y') ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="datagrid-item">
                                    <div class="datagrid-title">Duration</div>
                                    <div class="datagrid-content">
                                        <?php
                                        $interval = $startDate->diff($endDate);
                                        $days = $interval->days;
                                        $weeks = floor($days / 7);
                                        echo $days . ' days (~' . $weeks . ' weeks)';
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="datagrid-item">
                                    <div class="datagrid-title">Current Status</div>
                                    <div class="datagrid-content">
                                        <?php if ($isActive): ?>
                                            <span class="badge bg-success">In Progress</span>
                                        <?php elseif ($now < $startDate): ?>
                                            <span class="badge bg-info">Upcoming</span>
                                        <?php else: ?>
                                            <span class="badge bg-dark">Ended</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($term['notes']): ?>
                            <div class="mt-3">
                                <label class="form-label text-muted">Notes</label>
                                <div class="text-muted">
                                    <?= nl2br(htmlspecialchars($term['notes'])) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-chart-bar me-2"></i>
                            Quick Stats
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="datagrid">
                            <div class="datagrid-item">
                                <div class="datagrid-title">Important Dates</div>
                                <div class="datagrid-content">
                                    <span class="h3 mb-0"><?= count($importantDates) ?></span>
                                </div>
                            </div>
                            <div class="datagrid-item">
                                <div class="datagrid-title">Days Remaining</div>
                                <div class="datagrid-content">
                                    <?php
                                    if ($now < $startDate) {
                                        $daysTo = $now->diff($startDate)->days;
                                        echo '<span class="h3 mb-0">' . $daysTo . '</span> <span class="text-muted">until start</span>';
                                    } elseif ($isActive) {
                                        $daysLeft = $now->diff($endDate)->days;
                                        echo '<span class="h3 mb-0">' . $daysLeft . '</span>';
                                    } else {
                                        echo '<span class="text-muted">Ended</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Important Dates -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="ti ti-calendar-stats me-2"></i>
                    Important Dates
                </h3>
                <div class="card-actions">
                    <div class="btn-list">
                        <!-- Filter Dropdown -->
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                <i class="ti ti-filter me-1"></i>
                                <span id="filterLabel">Filter by Category</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end" style="min-width: 250px;">
                                <div class="dropdown-header">Event Categories</div>
                                <?php
                                // Get unique categories from date types
                                $categories = [];
                                foreach ($dateTypes as $type) {
                                    if (!in_array($type['category'], $categories)) {
                                        $categories[] = $type['category'];
                                    }
                                }
                                ?>
                                <?php foreach ($categories as $category): ?>
                                    <?php
                                    // Get first type in category for color
                                    $categoryColor = '#666';
                                    foreach ($dateTypes as $type) {
                                        if ($type['category'] === $category) {
                                            $categoryColor = $type['color'] ?? '#666';
                                            break;
                                        }
                                    }
                                    ?>
                                    <label class="dropdown-item" style="cursor: pointer;">
                                        <input type="checkbox" class="form-check-input me-2 category-filter" value="<?= htmlspecialchars($category) ?>" checked onchange="applyFilters()">
                                        <i class="ti ti-point-filled" style="color: <?= $categoryColor ?>"></i>
                                        <span class="text-capitalize"><?= htmlspecialchars($category) ?></span>
                                    </label>
                                <?php endforeach; ?>
                                <div class="dropdown-divider"></div>
                                <div class="dropdown-item">
                                    <button class="btn btn-sm btn-link p-0" onclick="selectAllFilters()">Select All</button>
                                    <span class="mx-1">|</span>
                                    <button class="btn btn-sm btn-link p-0" onclick="deselectAllFilters()">Clear All</button>
                                </div>
                            </div>
                        </div>

                        <button class="btn btn-outline-secondary" id="toggleViewBtn" onclick="toggleCalendarView()">
                            <i class="ti ti-list me-1"></i>
                            <span id="viewToggleText">List View</span>
                        </button>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDateModal">
                            <i class="ti ti-plus me-1"></i>
                            Add Important Date
                        </button>
                    </div>
                </div>
            </div>

            <!-- Calendar Grid View (Default) -->
            <div class="card-body" id="calendarGridView">
                <?php
                // Generate calendar grid for the term
                $termStart = new DateTime($term['start_date']);
                $termEnd = new DateTime($term['end_date']);
                $currentMonth = clone $termStart;
                $currentMonth->modify('first day of this month');

                // Create date map for important dates
                $dateMap = [];
                foreach ($importantDates as $date) {
                    $start = new DateTime($date['start_date']);
                    $end = $date['end_date'] ? new DateTime($date['end_date']) : $start;

                    $period = new DatePeriod($start, new DateInterval('P1D'), $end->modify('+1 day'));
                    foreach ($period as $dt) {
                        $key = $dt->format('Y-m-d');
                        if (!isset($dateMap[$key])) {
                            $dateMap[$key] = [];
                        }
                        $dateMap[$key][] = $date;
                    }
                }

                // Generate months
                while ($currentMonth <= $termEnd) {
                    $monthEnd = clone $currentMonth;
                    $monthEnd->modify('last day of this month');
                    ?>
                    <div class="mb-4">
                        <h4 class="mb-3"><?= $currentMonth->format('F Y') ?></h4>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm calendar-table">
                                <thead>
                                    <tr class="text-center">
                                        <th style="width: 14.28%;">Mon</th>
                                        <th style="width: 14.28%;">Tue</th>
                                        <th style="width: 14.28%;">Wed</th>
                                        <th style="width: 14.28%;">Thu</th>
                                        <th style="width: 14.28%;">Fri</th>
                                        <th style="width: 14.28%;">Sat</th>
                                        <th style="width: 14.28%;">Sun</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $firstDayOfMonth = clone $currentMonth;
                                    $dayOfWeek = $firstDayOfMonth->format('N'); // 1 (Monday) to 7 (Sunday)

                                    // Start from the previous Monday
                                    $calendarStart = clone $firstDayOfMonth;
                                    $calendarStart->modify('-' . ($dayOfWeek - 1) . ' days');

                                    $weekCount = 0;
                                    $currentDay = clone $calendarStart;

                                    while ($weekCount < 6 && ($currentDay->format('m') <= $currentMonth->format('m') || $weekCount == 0)) {
                                        echo '<tr>';
                                        for ($i = 0; $i < 7; $i++) {
                                            $dayKey = $currentDay->format('Y-m-d');
                                            $isCurrentMonth = $currentDay->format('m') == $currentMonth->format('m');
                                            $isInTerm = $currentDay >= $termStart && $currentDay <= $termEnd;
                                            $isToday = $currentDay->format('Y-m-d') == date('Y-m-d');
                                            $hasDates = isset($dateMap[$dayKey]);

                                            $cellClass = '';
                                            if (!$isCurrentMonth) $cellClass .= ' text-muted';
                                            if (!$isInTerm) $cellClass .= ' bg-light';
                                            if ($isToday) $cellClass .= ' bg-primary-lt';

                                            echo '<td class="' . $cellClass . ' calendar-day-cell" style="height: 80px; vertical-align: top; padding: 4px; position: relative;" data-date="' . $dayKey . '" onmouseenter="showAddButton(this)" onmouseleave="hideAddButton(this)">';
                                            echo '<div class="fw-bold small">' . $currentDay->format('j') . '</div>';
                                            if ($isInTerm) {
                                                echo '<button class="btn btn-sm btn-icon btn-primary calendar-add-btn" style="position: absolute; top: 2px; right: 2px; display: none; padding: 2px 4px; font-size: 10px;" onclick="openAddDateModal(\'' . $dayKey . '\')" title="Add important date"><i class="ti ti-plus"></i></button>';
                                            }

                                            if ($hasDates && $isInTerm) {
                                                foreach ($dateMap[$dayKey] as $event) {
                                                    $icon = $event['icon'] ?? 'ti-calendar-event';
                                                    $color = $event['color'] ?? '#0054a6';
                                                    $eventJson = htmlspecialchars(json_encode($event), ENT_QUOTES, 'UTF-8');
                                                    echo '<div class="badge badge-sm mt-1 event-badge" data-category="' . htmlspecialchars($event['category']) . '" style="background-color: ' . $color . '15; color: #0054a6; font-size: 9px; display: block; text-align: left; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; cursor: pointer; text-decoration: none; border: 1px solid ' . $color . '30;" title="' . htmlspecialchars($event['title']) . '" onclick="viewDateDetails(' . $eventJson . ')" onmouseover="this.style.textDecoration=\'underline\'" onmouseout="this.style.textDecoration=\'none\'">';
                                                    echo '<i class="ti ' . $icon . ' me-1" style="color: ' . $color . ';"></i>';
                                                    echo htmlspecialchars(substr($event['title'], 0, 12));
                                                    if (strlen($event['title']) > 12) echo '...';
                                                    echo '</div>';
                                                }
                                            }

                                            echo '</td>';
                                            $currentDay->modify('+1 day');
                                        }
                                        echo '</tr>';
                                        $weekCount++;

                                        if ($currentDay > $monthEnd && $currentDay->format('m') != $currentMonth->format('m')) {
                                            break;
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php
                    $currentMonth->modify('first day of next month');
                }
                ?>
            </div>

            <!-- List View (Hidden by default) -->
            <div class="card-body" id="listView" style="display: none;">
                <?php if (empty($importantDates)): ?>
                    <div class="empty">
                        <div class="empty-icon">
                            <i class="ti ti-calendar-off"></i>
                        </div>
                        <p class="empty-title">No important dates added yet</p>
                        <p class="empty-subtitle text-muted">
                            Add exam dates, mid-term breaks, visiting days, and other important dates for this term
                        </p>
                        <div class="empty-action">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDateModal">
                                <i class="ti ti-plus"></i>
                                Add First Important Date
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php
                        // Group by category
                        $datesByCategory = [];
                        foreach ($importantDates as $date) {
                            $datesByCategory[$date['category']][] = $date;
                        }
                        ?>

                        <?php foreach ($datesByCategory as $category => $categoryDates): ?>
                            <div class="list-group-item category-group" data-category="<?= htmlspecialchars($category) ?>">
                                <h4 class="mb-3 text-capitalize">
                                    <i class="ti ti-point-filled me-1" style="color: <?= $categoryDates[0]['color'] ?? '#666' ?>"></i>
                                    <?= htmlspecialchars($category) ?>
                                </h4>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($categoryDates as $date): ?>
                                        <?php
                                        $dateStart = new DateTime($date['start_date']);
                                        $dateEnd = $date['end_date'] ? new DateTime($date['end_date']) : null;
                                        ?>
                                        <div class="list-group-item px-0 event-item" data-category="<?= htmlspecialchars($category) ?>">
                                            <div class="row align-items-center">
                                                <div class="col-auto">
                                                    <span class="avatar" style="background-color: <?= $date['color'] ?>15; color: <?= $date['color'] ?>;">
                                                        <i class="ti <?= $date['icon'] ?>"></i>
                                                    </span>
                                                </div>
                                                <div class="col">
                                                    <div class="mb-1">
                                                        <strong><?= htmlspecialchars($date['title']) ?></strong>
                                                        <span class="badge badge-outline text-muted ms-2">
                                                            <?= htmlspecialchars($date['type_name']) ?>
                                                        </span>
                                                    </div>
                                                    <div class="text-muted small">
                                                        <i class="ti ti-calendar me-1"></i>
                                                        <?php if ($dateEnd): ?>
                                                            <?php
                                                            $daysDiff = $dateStart->diff($dateEnd)->days + 1;
                                                            ?>
                                                            <?= $dateStart->format('M d, Y') ?> - <?= $dateEnd->format('M d, Y') ?>
                                                            <span class="badge bg-info-lt ms-2">
                                                                <?= $daysDiff ?> <?= $daysDiff === 1 ? 'day' : 'days' ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <?= $dateStart->format('l, F j, Y') ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if ($date['description']): ?>
                                                        <div class="text-muted small mt-1">
                                                            <?= htmlspecialchars($date['description']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="mt-1">
                                                        <?php if ($date['affects_timetable']): ?>
                                                            <span class="badge bg-warning">Affects Timetable</span>
                                                        <?php endif; ?>
                                                        <?php if (!$date['is_school_open']): ?>
                                                            <span class="badge bg-danger">School Closed</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="col-auto">
                                                    <div class="btn-list">
                                                        <button class="btn btn-sm btn-outline-secondary"
                                                                onclick='editDate(<?= json_encode($date, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                                            <i class="ti ti-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger"
                                                                onclick="deleteDate(<?= $date['id'] ?>)">
                                                            <i class="ti ti-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Important Date Modal -->
<div class="modal modal-blur fade" id="addDateModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Important Date</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addDateForm" onsubmit="return submitAddDate(event)">
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Date Type -->
                        <div class="col-12">
                            <label class="form-label required">Date Type</label>
                            <select class="form-select" name="date_type_id" id="dateTypeSelect" required onchange="updateDateTypeInfo()">
                                <option value="">Select date type...</option>
                                <?php
                                $typesByCategory = [];
                                foreach ($dateTypes as $type) {
                                    $typesByCategory[$type['category']][] = $type;
                                }
                                ?>
                                <?php foreach ($typesByCategory as $category => $types): ?>
                                    <optgroup label="<?= ucfirst($category) ?>">
                                        <?php foreach ($types as $type): ?>
                                            <option value="<?= $type['id'] ?>"
                                                    data-name="<?= htmlspecialchars($type['name']) ?>"
                                                    data-description="<?= htmlspecialchars($type['description'] ?? '') ?>">
                                                <?= htmlspecialchars($type['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                            <div id="dateTypeDescription" class="form-hint mt-2" style="display: none;"></div>
                        </div>

                        <!-- Title -->
                        <div class="col-12">
                            <label class="form-label required">Title</label>
                            <input type="text" class="form-control" name="title" id="dateTitle" required>
                        </div>

                        <!-- Start Date -->
                        <div class="col-md-6">
                            <label class="form-label required">Start Date</label>
                            <input type="date"
                                   class="form-control"
                                   name="start_date"
                                   min="<?= $term['start_date'] ?>"
                                   max="<?= $term['end_date'] ?>"
                                   required>
                        </div>

                        <!-- End Date -->
                        <div class="col-md-6">
                            <label class="form-label">End Date (Optional)</label>
                            <input type="date"
                                   class="form-control"
                                   name="end_date"
                                   min="<?= $term['start_date'] ?>"
                                   max="<?= $term['end_date'] ?>">
                            <small class="form-hint">Leave blank for single-day events</small>
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="2"></textarea>
                        </div>

                        <!-- Checkboxes -->
                        <div class="col-12">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="affects_timetable" id="affectsTimetable">
                                <label class="form-check-label" for="affectsTimetable">
                                    Affects normal timetable
                                </label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_school_open" id="isSchoolOpen" checked>
                                <label class="form-check-label" for="isSchoolOpen">
                                    School is open on these dates
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i>
                        Add Date
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View/Edit Important Date Modal -->
<div class="modal modal-blur fade" id="viewDateModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-calendar-event me-2" id="viewModalIcon"></i>
                    <span id="viewModalTitle">Event Details</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- View Mode -->
            <div id="viewModeContent">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="card mb-0" id="viewDateCard">
                                <!-- Content will be populated by JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="switchToEditMode()">
                        <i class="ti ti-edit me-1"></i>
                        Edit
                    </button>
                    <button type="button" class="btn btn-danger" onclick="confirmDeleteDate()">
                        <i class="ti ti-trash me-1"></i>
                        Delete
                    </button>
                </div>
            </div>

            <!-- Edit Mode -->
            <div id="editModeContent" style="display: none;">
                <form id="editDateForm" onsubmit="return submitEditDate(event)">
                    <input type="hidden" id="editDateId" name="date_id">
                    <div class="modal-body">
                        <div class="row g-3">
                            <!-- Date Type -->
                            <div class="col-12">
                                <label class="form-label required">Date Type</label>
                                <select class="form-select" name="date_type_id" id="editDateTypeSelect" required>
                                    <option value="">Select date type...</option>
                                    <?php foreach ($typesByCategory as $category => $types): ?>
                                        <optgroup label="<?= ucfirst($category) ?>">
                                            <?php foreach ($types as $type): ?>
                                                <option value="<?= $type['id'] ?>">
                                                    <?= htmlspecialchars($type['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Title -->
                            <div class="col-12">
                                <label class="form-label required">Title</label>
                                <input type="text" class="form-control" name="title" id="editDateTitle" required>
                            </div>

                            <!-- Start Date -->
                            <div class="col-md-6">
                                <label class="form-label required">Start Date</label>
                                <input type="date"
                                       class="form-control"
                                       name="start_date"
                                       id="editStartDate"
                                       min="<?= $term['start_date'] ?>"
                                       max="<?= $term['end_date'] ?>"
                                       required>
                            </div>

                            <!-- End Date -->
                            <div class="col-md-6">
                                <label class="form-label">End Date (Optional)</label>
                                <input type="date"
                                       class="form-control"
                                       name="end_date"
                                       id="editEndDate"
                                       min="<?= $term['start_date'] ?>"
                                       max="<?= $term['end_date'] ?>">
                                <small class="form-hint">Leave blank for single-day events</small>
                            </div>

                            <!-- Description -->
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" id="editDescription" rows="2"></textarea>
                            </div>

                            <!-- Checkboxes -->
                            <div class="col-12">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="affects_timetable" id="editAffectsTimetable">
                                    <label class="form-check-label" for="editAffectsTimetable">
                                        Affects normal timetable
                                    </label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_school_open" id="editIsSchoolOpen">
                                    <label class="form-check-label" for="editIsSchoolOpen">
                                        School is open on these dates
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link" onclick="switchToViewMode()">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check me-1"></i>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleCalendarView() {
    const listView = document.getElementById('listView');
    const calendarView = document.getElementById('calendarGridView');
    const toggleBtn = document.getElementById('toggleViewBtn');
    const toggleText = document.getElementById('viewToggleText');

    if (!listView || !calendarView || !toggleBtn || !toggleText) {
        console.error('Required elements not found');
        return;
    }

    if (listView.style.display === 'none') {
        // Switch to list view
        listView.style.display = 'block';
        calendarView.style.display = 'none';
        toggleText.textContent = 'Calendar View';
        const icon = toggleBtn.querySelector('i');
        if (icon) icon.className = 'ti ti-calendar-month me-1';
    } else {
        // Switch to calendar view
        listView.style.display = 'none';
        calendarView.style.display = 'block';
        toggleText.textContent = 'List View';
        const icon = toggleBtn.querySelector('i');
        if (icon) icon.className = 'ti ti-list me-1';
    }
}

function updateDateTypeInfo() {
    const select = document.getElementById('dateTypeSelect');
    if (!select || !select.options || !select.selectedIndex) return;

    const selectedOption = select.options[select.selectedIndex];
    const description = selectedOption.getAttribute('data-description');
    const name = selectedOption.getAttribute('data-name');
    const descDiv = document.getElementById('dateTypeDescription');
    const titleInput = document.getElementById('dateTitle');

    if (descDiv) {
        if (description) {
            descDiv.textContent = description;
            descDiv.style.display = 'block';
        } else {
            descDiv.style.display = 'none';
        }
    }

    // Auto-fill title if empty
    if (titleInput && name && !titleInput.value) {
        titleInput.value = name;
    }
}

function submitAddDate(event) {
    event.preventDefault();

    const form = event.target;
    const formData = new FormData(form);

    fetch('/calendar/terms/<?= $term['id'] ?>/dates', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Failed to add important date');
        console.error('Error:', error);
    });

    return false;
}

function deleteDate(dateId) {
    if (confirm('Are you sure you want to delete this important date?')) {
        fetch('/calendar/dates/' + dateId + '/delete', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Failed to delete date');
            console.error('Error:', error);
        });
    }
}

function editDate(date) {
    viewDateDetails(date);
    setTimeout(() => switchToEditMode(), 300);
}

let currentDateData = null;

function viewDateDetails(date) {
    if (!date) {
        console.error('No date data provided');
        return;
    }

    currentDateData = date;

    // Update modal title and icon
    const modalIcon = document.getElementById('viewModalIcon');
    const modalTitle = document.getElementById('viewModalTitle');

    if (modalIcon) {
        modalIcon.className = 'ti ' + (date.icon || 'ti-calendar-event') + ' me-2';
    }
    if (modalTitle) {
        modalTitle.textContent = date.title;
    }

    // Format dates
    const startDate = new Date(date.start_date);
    const endDate = date.end_date ? new Date(date.end_date) : null;

    const startDateStr = startDate.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    const endDateStr = endDate ? endDate.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) : null;

    // Build view content
    let content = `
        <div class="card-body">
            <div class="row g-3">
                <div class="col-auto">
                    <span class="avatar avatar-lg" style="background-color: ${date.color}15; color: ${date.color};">
                        <i class="ti ${date.icon || 'ti-calendar-event'}"></i>
                    </span>
                </div>
                <div class="col">
                    <h3 class="mb-1">${date.title}</h3>
                    <div class="text-muted">
                        <span class="badge badge-outline">${date.type_name}</span>
                        <span class="badge bg-${date.category}-lt ms-1">${date.category}</span>
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
                        </div>
                    </div>
                </div>
                ` : ''}
            </div>

            ${date.description ? `
            <div class="mt-3">
                <label class="form-label fw-bold">Description</label>
                <div class="text-muted">${date.description}</div>
            </div>
            ` : ''}

            <div class="mt-3">
                <div class="row">
                    <div class="col-auto">
                        <label class="form-label fw-bold">Timetable Impact</label>
                        <div>
                            ${date.affects_timetable ?
                                '<span class="badge bg-warning"><i class="ti ti-alert-triangle me-1"></i>Affects Timetable</span>' :
                                '<span class="badge bg-success-lt">No Impact</span>'}
                        </div>
                    </div>
                    <div class="col-auto">
                        <label class="form-label fw-bold">School Status</label>
                        <div>
                            ${date.is_school_open ?
                                '<span class="badge bg-success"><i class="ti ti-door-enter me-1"></i>School Open</span>' :
                                '<span class="badge bg-danger"><i class="ti ti-door-exit me-1"></i>School Closed</span>'}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    const viewDateCard = document.getElementById('viewDateCard');
    if (viewDateCard) {
        viewDateCard.innerHTML = content;
    }

    // Show modal
    const modalElement = document.getElementById('viewDateModal');
    if (modalElement && typeof bootstrap !== 'undefined') {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }

    // Reset to view mode
    const viewModeContent = document.getElementById('viewModeContent');
    const editModeContent = document.getElementById('editModeContent');

    if (viewModeContent) viewModeContent.style.display = 'block';
    if (editModeContent) editModeContent.style.display = 'none';
}

function switchToEditMode() {
    if (!currentDateData) return;

    // Populate edit form
    const fields = {
        'editDateId': currentDateData.id,
        'editDateTypeSelect': currentDateData.date_type_id,
        'editDateTitle': currentDateData.title,
        'editStartDate': currentDateData.start_date,
        'editEndDate': currentDateData.end_date || '',
        'editDescription': currentDateData.description || ''
    };

    for (const [id, value] of Object.entries(fields)) {
        const element = document.getElementById(id);
        if (element) element.value = value;
    }

    const affectsTimetable = document.getElementById('editAffectsTimetable');
    const isSchoolOpen = document.getElementById('editIsSchoolOpen');

    if (affectsTimetable) affectsTimetable.checked = currentDateData.affects_timetable == 1;
    if (isSchoolOpen) isSchoolOpen.checked = currentDateData.is_school_open == 1;

    // Switch views
    const viewModeContent = document.getElementById('viewModeContent');
    const editModeContent = document.getElementById('editModeContent');

    if (viewModeContent) viewModeContent.style.display = 'none';
    if (editModeContent) editModeContent.style.display = 'block';
}

function switchToViewMode() {
    const viewModeContent = document.getElementById('viewModeContent');
    const editModeContent = document.getElementById('editModeContent');

    if (viewModeContent) viewModeContent.style.display = 'block';
    if (editModeContent) editModeContent.style.display = 'none';
}

function submitEditDate(event) {
    event.preventDefault();

    const form = event.target;
    const formData = new FormData(form);
    const dateId = document.getElementById('editDateId').value;

    fetch('/calendar/dates/' + dateId + '/update', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Failed to update important date');
        console.error('Error:', error);
    });

    return false;
}

function confirmDeleteDate() {
    if (!currentDateData) return;

    if (confirm('Are you sure you want to delete "' + currentDateData.title + '"?')) {
        deleteDate(currentDateData.id);
    }
}

function showAddButton(cell) {
    const btn = cell.querySelector('.calendar-add-btn');
    if (btn) {
        btn.style.display = 'block';
    }
}

function hideAddButton(cell) {
    const btn = cell.querySelector('.calendar-add-btn');
    if (btn) {
        btn.style.display = 'none';
    }
}

function openAddDateModal(date) {
    // Pre-fill the start date with the clicked date
    const startDateInput = document.querySelector('#addDateModal input[name="start_date"]');
    if (startDateInput) {
        startDateInput.value = date;
    }

    // Open the modal
    const modal = new bootstrap.Modal(document.getElementById('addDateModal'));
    modal.show();
}

function applyFilters() {
    // Get all checked categories
    const checkedCategories = [];
    document.querySelectorAll('.category-filter:checked').forEach(checkbox => {
        checkedCategories.push(checkbox.value);
    });

    // Update filter label
    const filterLabel = document.getElementById('filterLabel');
    if (checkedCategories.length === 0) {
        filterLabel.textContent = 'No Categories Selected';
    } else if (checkedCategories.length === document.querySelectorAll('.category-filter').length) {
        filterLabel.textContent = 'Filter by Category';
    } else {
        filterLabel.textContent = `${checkedCategories.length} Categories`;
    }

    // Filter calendar view events
    document.querySelectorAll('.event-badge').forEach(badge => {
        const category = badge.getAttribute('data-category');
        if (checkedCategories.includes(category)) {
            badge.style.display = 'block';
        } else {
            badge.style.display = 'none';
        }
    });

    // Filter list view items
    document.querySelectorAll('.category-group').forEach(group => {
        const category = group.getAttribute('data-category');
        if (checkedCategories.includes(category)) {
            group.style.display = 'block';
        } else {
            group.style.display = 'none';
        }
    });
}

function selectAllFilters() {
    document.querySelectorAll('.category-filter').forEach(checkbox => {
        checkbox.checked = true;
    });
    applyFilters();
}

function deselectAllFilters() {
    document.querySelectorAll('.category-filter').forEach(checkbox => {
        checkbox.checked = false;
    });
    applyFilters();
}
</script>

