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
                    <i class="ti ti-flag me-2"></i>
                    National Holidays
                </h2>
                <div class="text-muted mt-1">Public holidays that affect the academic calendar</div>
            </div>
        </div>
    </div>

    <!-- Year Filter -->
    <div class="page-body">
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Filter by Year</label>
                        <select class="form-select" id="yearFilter" onchange="filterByYear(this.value)">
                            <?php for ($y = $selectedYear - 2; $y <= $selectedYear + 3; $y++): ?>
                                <option value="<?= $y ?>" <?= $selectedYear == $y ? 'selected' : '' ?>>
                                    <?= $y ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <div class="alert alert-info mb-0">
                            <i class="ti ti-info-circle me-2"></i>
                            <strong>Note:</strong> National holidays marked as "School Holiday" will automatically affect the timetable and event scheduling.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Holidays List -->
        <?php if (empty($holidays)): ?>
            <div class="card">
                <div class="empty">
                    <div class="empty-icon">
                        <i class="ti ti-calendar-off"></i>
                    </div>
                    <p class="empty-title">No national holidays found for <?= $selectedYear ?></p>
                    <p class="empty-subtitle text-muted">
                        National holidays will be automatically populated for each year
                    </p>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">National Holidays for <?= $selectedYear ?></h3>
                    <div class="card-actions">
                        <span class="badge badge-outline text-muted">
                            <?= count($holidays) ?> <?= count($holidays) === 1 ? 'Holiday' : 'Holidays' ?>
                        </span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php
                        // Group holidays by month
                        $holidaysByMonth = [];
                        foreach ($holidays as $holiday) {
                            $date = new DateTime($holiday['holiday_date']);
                            $month = $date->format('F');
                            $holidaysByMonth[$month][] = $holiday;
                        }
                        ?>

                        <?php foreach ($holidaysByMonth as $month => $monthHolidays): ?>
                            <!-- Month Header -->
                            <div class="list-group-item bg-light">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <h4 class="mb-0">
                                            <i class="ti ti-calendar-month me-2"></i>
                                            <?= $month ?>
                                        </h4>
                                    </div>
                                    <div class="col-auto">
                                        <span class="badge badge-outline">
                                            <?= count($monthHolidays) ?> <?= count($monthHolidays) === 1 ? 'holiday' : 'holidays' ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Holidays in this month -->
                            <?php foreach ($monthHolidays as $holiday): ?>
                                <?php
                                $holidayDate = new DateTime($holiday['holiday_date']);
                                $dayOfWeek = $holidayDate->format('l');
                                $day = $holidayDate->format('j');
                                $monthShort = $holidayDate->format('M');

                                // Determine holiday type color
                                $typeColors = [
                                    'public_holiday' => 'success',
                                    'religious_holiday' => 'info',
                                    'commemoration' => 'warning',
                                    'celebration' => 'pink',
                                    'civic_holiday' => 'purple'
                                ];

                                // Get the holiday type code from the database (we need to enhance the query to get this)
                                $typeColor = 'primary'; // Default
                                ?>
                                <div class="list-group-item">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <div class="text-center" style="min-width: 60px;">
                                                <div class="text-muted small"><?= $monthShort ?></div>
                                                <div class="h2 mb-0"><?= $day ?></div>
                                                <div class="text-muted small"><?= $dayOfWeek ?></div>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="mb-1">
                                                <strong class="h4"><?= htmlspecialchars($holiday['holiday_name']) ?></strong>
                                                <span class="badge bg-<?= $typeColor ?> ms-2">
                                                    <?= htmlspecialchars($holiday['type_name']) ?>
                                                </span>
                                            </div>
                                            <?php if ($holiday['description']): ?>
                                                <div class="text-muted small">
                                                    <?= htmlspecialchars($holiday['description']) ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="mt-2">
                                                <?php if ($holiday['is_school_holiday']): ?>
                                                    <span class="badge bg-danger">
                                                        <i class="ti ti-school-off me-1"></i>
                                                        School Closed
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">
                                                        <i class="ti ti-school me-1"></i>
                                                        School Open
                                                    </span>
                                                <?php endif; ?>

                                                <?php if ($holiday['is_recurring']): ?>
                                                    <span class="badge badge-outline text-muted ms-1">
                                                        <i class="ti ti-repeat me-1"></i>
                                                        Annual
                                                    </span>
                                                <?php endif; ?>

                                                <?php if ($holiday['recurrence_rule']): ?>
                                                    <span class="badge badge-outline text-info ms-1" title="<?= htmlspecialchars($holiday['recurrence_rule']) ?>">
                                                        <i class="ti ti-info-circle me-1"></i>
                                                        Variable Date
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <div class="text-center">
                                                <?php
                                                $now = new DateTime();
                                                $isPast = $holidayDate < $now;
                                                $isToday = $holidayDate->format('Y-m-d') === $now->format('Y-m-d');
                                                $isSoon = !$isPast && $holidayDate->diff($now)->days <= 14;
                                                ?>

                                                <?php if ($isToday): ?>
                                                    <span class="badge bg-success badge-pill px-3 py-2">
                                                        <i class="ti ti-calendar-event me-1"></i>
                                                        Today
                                                    </span>
                                                <?php elseif ($isSoon): ?>
                                                    <span class="badge bg-warning badge-pill px-3 py-2">
                                                        <i class="ti ti-bell me-1"></i>
                                                        In <?= $holidayDate->diff($now)->days ?> days
                                                    </span>
                                                <?php elseif ($isPast): ?>
                                                    <span class="text-muted small">
                                                        <i class="ti ti-check"></i>
                                                        Passed
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted small">
                                                        In <?= $holidayDate->diff($now)->days ?> days
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Holiday Summary Stats -->
            <div class="row row-cards mt-3">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="subheader">Total Holidays</div>
                            </div>
                            <div class="h1 mb-0"><?= count($holidays) ?></div>
                            <div class="text-muted small">in <?= $selectedYear ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="subheader">School Closed Days</div>
                            </div>
                            <div class="h1 mb-0">
                                <?php
                                $schoolClosedCount = count(array_filter($holidays, function($h) {
                                    return $h['is_school_holiday'];
                                }));
                                echo $schoolClosedCount;
                                ?>
                            </div>
                            <div class="text-muted small">days with no classes</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="subheader">Next Holiday</div>
                            </div>
                            <?php
                            $now = new DateTime();
                            $upcomingHolidays = array_filter($holidays, function($h) use ($now) {
                                $hDate = new DateTime($h['holiday_date']);
                                return $hDate >= $now;
                            });
                            usort($upcomingHolidays, function($a, $b) {
                                return strtotime($a['holiday_date']) - strtotime($b['holiday_date']);
                            });
                            $nextHoliday = !empty($upcomingHolidays) ? $upcomingHolidays[0] : null;
                            ?>
                            <?php if ($nextHoliday): ?>
                                <?php $nextDate = new DateTime($nextHoliday['holiday_date']); ?>
                                <div class="h3 mb-0"><?= htmlspecialchars($nextHoliday['holiday_name']) ?></div>
                                <div class="text-muted small">
                                    <?= $nextDate->format('M j, Y') ?>
                                    (in <?= $nextDate->diff($now)->days ?> days)
                                </div>
                            <?php else: ?>
                                <div class="text-muted">No upcoming holidays</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function filterByYear(year) {
    window.location.href = '/calendar/holidays?year=' + year;
}
</script>
