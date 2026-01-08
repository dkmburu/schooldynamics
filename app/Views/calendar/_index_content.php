<?php
$now = new DateTime();
$currentMonth = $now->format('F Y');
?>

<div class="container-xl">
    <!-- Page header -->
    <div class="page-header d-print-none">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <i class="ti ti-calendar me-2"></i>
                    Academic Calendar
                </h2>
                <div class="text-muted mt-1">
                    Academic Year: <?= htmlspecialchars($academicYear) ?>
                    <?php if ($currentTerm): ?>
                        <span class="ms-3">
                            <i class="ti ti-point-filled text-success"></i>
                            Current Term: <?= htmlspecialchars($currentTerm['term_name']) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <a href="/calendar/holidays" class="btn btn-outline-secondary">
                        <i class="ti ti-flag me-1"></i>
                        Holidays
                    </a>
                    <a href="/calendar/terms" class="btn btn-primary">
                        <i class="ti ti-calendar-event me-1"></i>
                        Manage Terms
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <!-- Academic Year Selector -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3 align-items-center">
                    <div class="col-md-4">
                        <label class="form-label">Academic Year</label>
                        <select class="form-select" onchange="window.location.href='/calendar?year=' + this.value">
                            <?php
                            $currentYear = date('Y');
                            for ($y = $currentYear - 1; $y <= $currentYear + 2; $y++) {
                                $yearOption = $y . '/' . ($y + 1);
                                $selected = $yearOption === $academicYear ? 'selected' : '';
                                echo "<option value=\"{$yearOption}\" {$selected}>{$yearOption}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <?php if (empty($terms)): ?>
                            <div class="alert alert-warning mb-0">
                                <i class="ti ti-alert-triangle me-2"></i>
                                <strong>No terms configured.</strong> Please create academic terms to view the calendar.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($terms)): ?>
            <!-- Empty State -->
            <div class="card">
                <div class="empty">
                    <div class="empty-icon">
                        <i class="ti ti-calendar-off"></i>
                    </div>
                    <p class="empty-title">No academic calendar configured</p>
                    <p class="empty-subtitle text-muted">
                        Get started by creating academic terms for the year <?= htmlspecialchars($academicYear) ?>
                    </p>
                    <div class="empty-action">
                        <a href="/calendar/terms/create" class="btn btn-primary">
                            <i class="ti ti-plus"></i>
                            Create First Term
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Academic Terms Timeline -->
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="ti ti-timeline me-2"></i>
                        Academic Terms Timeline
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row row-cards">
                        <?php foreach ($terms as $index => $term): ?>
                            <?php
                            $startDate = new DateTime($term['start_date']);
                            $endDate = new DateTime($term['end_date']);
                            $isActive = $now >= $startDate && $now <= $endDate;
                            $isPast = $now > $endDate;
                            $isFuture = $now < $startDate;

                            $statusColors = [
                                'draft' => 'secondary',
                                'published' => 'info',
                                'current' => 'success',
                                'completed' => 'dark'
                            ];
                            $statusColor = $statusColors[$term['status']] ?? 'secondary';
                            ?>
                            <div class="col-md-4">
                                <div class="card card-sm <?= $term['is_current'] ? 'border-success' : '' ?>">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="me-3">
                                                <span class="avatar <?= $term['is_current'] ? 'bg-success' : 'bg-secondary' ?>">
                                                    <i class="ti ti-calendar"></i>
                                                </span>
                                            </div>
                                            <div>
                                                <div class="font-weight-medium">
                                                    <?= htmlspecialchars($term['term_name']) ?>
                                                </div>
                                                <div class="text-muted small">
                                                    <span class="badge bg-<?= $statusColor ?>">
                                                        <?= ucfirst($term['status']) ?>
                                                    </span>
                                                    <?php if ($term['is_current']): ?>
                                                        <span class="badge bg-success ms-1">Current</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-muted small mb-2">
                                            <i class="ti ti-calendar-event me-1"></i>
                                            <?= $startDate->format('M j') ?> - <?= $endDate->format('M j, Y') ?>
                                        </div>
                                        <div class="text-muted small mb-2">
                                            <i class="ti ti-clock me-1"></i>
                                            <?php
                                            $interval = $startDate->diff($endDate);
                                            $weeks = floor($interval->days / 7);
                                            echo $interval->days . ' days (~' . $weeks . ' weeks)';
                                            ?>
                                        </div>
                                        <?php if ($isActive): ?>
                                            <div class="progress progress-sm mb-2">
                                                <?php
                                                $totalDays = $startDate->diff($endDate)->days;
                                                $elapsedDays = $startDate->diff($now)->days;
                                                $progress = ($elapsedDays / $totalDays) * 100;
                                                ?>
                                                <div class="progress-bar bg-success" style="width: <?= $progress ?>%"></div>
                                            </div>
                                            <div class="text-muted small">
                                                <?= round($progress) ?>% complete
                                                (<?= $now->diff($endDate)->days ?> days remaining)
                                            </div>
                                        <?php elseif ($isFuture): ?>
                                            <div class="text-info small">
                                                <i class="ti ti-clock-hour-3 me-1"></i>
                                                Starts in <?= $now->diff($startDate)->days ?> days
                                            </div>
                                        <?php else: ?>
                                            <div class="text-muted small">
                                                <i class="ti ti-check me-1"></i>
                                                Ended <?= $endDate->format('M j, Y') ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="mt-3">
                                            <a href="/calendar/terms/<?= $term['id'] ?>" class="btn btn-sm btn-outline-primary w-100">
                                                View Details
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Upcoming Important Dates -->
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="ti ti-calendar-stats me-2"></i>
                        Upcoming Important Dates
                    </h3>
                </div>
                <div class="card-body">
                    <?php
                    // Get all important dates from all terms, filter upcoming ones
                    $upcomingDates = [];
                    foreach ($terms as $term) {
                        // In a real implementation, we'd fetch important dates for each term
                        // For now, this is a placeholder
                    }
                    ?>
                    <div class="text-muted">
                        <i class="ti ti-info-circle me-2"></i>
                        Click on individual terms above to view and manage important dates
                    </div>
                </div>
            </div>

            <!-- National Holidays -->
            <?php if (!empty($holidays)): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-flag me-2"></i>
                            Upcoming National Holidays
                        </h3>
                        <div class="card-actions">
                            <a href="/calendar/holidays" class="btn btn-sm btn-outline-primary">
                                View All Holidays
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <?php
                            // Filter and show only upcoming holidays (next 5)
                            $upcomingHolidays = array_filter($holidays, function($h) use ($now) {
                                $hDate = new DateTime($h['holiday_date']);
                                return $hDate >= $now;
                            });
                            usort($upcomingHolidays, function($a, $b) {
                                return strtotime($a['holiday_date']) - strtotime($b['holiday_date']);
                            });
                            $upcomingHolidays = array_slice($upcomingHolidays, 0, 5);
                            ?>

                            <?php if (empty($upcomingHolidays)): ?>
                                <div class="text-muted">No upcoming national holidays in the current period</div>
                            <?php else: ?>
                                <?php foreach ($upcomingHolidays as $holiday): ?>
                                    <?php
                                    $holidayDate = new DateTime($holiday['holiday_date']);
                                    $daysUntil = $now->diff($holidayDate)->days;
                                    $isToday = $holidayDate->format('Y-m-d') === $now->format('Y-m-d');
                                    ?>
                                    <div class="list-group-item">
                                        <div class="row align-items-center">
                                            <div class="col-auto">
                                                <div class="text-center" style="min-width: 50px;">
                                                    <div class="text-muted small"><?= $holidayDate->format('M') ?></div>
                                                    <div class="h3 mb-0"><?= $holidayDate->format('j') ?></div>
                                                    <div class="text-muted small"><?= $holidayDate->format('D') ?></div>
                                                </div>
                                            </div>
                                            <div class="col">
                                                <div>
                                                    <strong><?= htmlspecialchars($holiday['holiday_name']) ?></strong>
                                                    <?php if ($holiday['is_school_holiday']): ?>
                                                        <span class="badge bg-danger ms-2">School Closed</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-muted small">
                                                    <?= htmlspecialchars($holiday['type_name']) ?>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <?php if ($isToday): ?>
                                                    <span class="badge bg-success">Today</span>
                                                <?php elseif ($daysUntil <= 7): ?>
                                                    <span class="badge bg-warning">In <?= $daysUntil ?> <?= $daysUntil === 1 ? 'day' : 'days' ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted small">In <?= $daysUntil ?> days</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
