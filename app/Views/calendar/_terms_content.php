<div class="container-xl">
    <!-- Page header -->
    <div class="page-header d-print-none">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <i class="ti ti-calendar-event me-2"></i>
                    Academic Terms
                </h2>
                <div class="text-muted mt-1">Manage academic year terms and term dates</div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <a href="/calendar/holidays" class="btn btn-outline-secondary">
                        <i class="ti ti-flag me-1"></i>
                        National Holidays
                    </a>
                    <a href="/calendar/terms/create" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i>
                        Create New Term
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Academic Year Filter -->
    <div class="page-body">
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Filter by Academic Year</label>
                        <select class="form-select" id="yearFilter" onchange="filterByYear(this.value)">
                            <option value="">All Years</option>
                            <?php foreach ($years as $year): ?>
                                <option value="<?= htmlspecialchars($year) ?>" <?= $selectedYear === $year ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($year) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Terms List -->
        <?php if (empty($terms)): ?>
            <div class="card">
                <div class="empty">
                    <div class="empty-icon">
                        <i class="ti ti-calendar-off"></i>
                    </div>
                    <p class="empty-title">No academic terms found</p>
                    <p class="empty-subtitle text-muted">
                        Get started by creating your first academic term for the school year
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
            <div class="row row-cards">
                <?php
                // Group terms by academic year
                $termsByYear = [];
                foreach ($terms as $term) {
                    $termsByYear[$term['academic_year']][] = $term;
                }
                ?>

                <?php foreach ($termsByYear as $year => $yearTerms): ?>
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Academic Year: <?= htmlspecialchars($year) ?></h3>
                                <div class="card-actions">
                                    <span class="badge badge-outline text-muted">
                                        <?= count($yearTerms) ?> <?= count($yearTerms) === 1 ? 'Term' : 'Terms' ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <?php foreach ($yearTerms as $term): ?>
                                        <?php
                                        $statusColors = [
                                            'draft' => 'secondary',
                                            'published' => 'info',
                                            'current' => 'success',
                                            'completed' => 'dark'
                                        ];
                                        $statusColor = $statusColors[$term['status']] ?? 'secondary';

                                        $startDate = new DateTime($term['start_date']);
                                        $endDate = new DateTime($term['end_date']);
                                        $now = new DateTime();

                                        $isActive = $now >= $startDate && $now <= $endDate;
                                        ?>
                                        <div class="list-group-item">
                                            <div class="row align-items-center">
                                                <div class="col-auto">
                                                    <span class="avatar" style="background-color: #e9ecef; color: #6c757d;">
                                                        <i class="ti ti-calendar fs-2"></i>
                                                    </span>
                                                </div>
                                                <div class="col">
                                                    <div class="d-flex align-items-center mb-1">
                                                        <strong class="me-2"><?= htmlspecialchars($term['term_name']) ?></strong>
                                                        <span class="badge bg-<?= $statusColor ?>"><?= ucfirst($term['status']) ?></span>
                                                        <?php if ($term['is_current']): ?>
                                                            <span class="badge bg-success ms-1">Current</span>
                                                        <?php endif; ?>
                                                        <?php if ($isActive && !$term['is_current']): ?>
                                                            <span class="badge bg-warning ms-1">Active Period</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="text-muted small">
                                                        <i class="ti ti-calendar-event me-1"></i>
                                                        <?= $startDate->format('M d, Y') ?> - <?= $endDate->format('M d, Y') ?>
                                                        <span class="ms-3">
                                                            <i class="ti ti-clock me-1"></i>
                                                            <?php
                                                            $interval = $startDate->diff($endDate);
                                                            $weeks = floor($interval->days / 7);
                                                            echo $interval->days . ' days (~' . $weeks . ' weeks)';
                                                            ?>
                                                        </span>
                                                        <?php if ($term['campus_id']): ?>
                                                            <span class="ms-3">
                                                                <i class="ti ti-building me-1"></i>
                                                                Campus ID: <?= $term['campus_id'] ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if ($term['notes']): ?>
                                                        <div class="text-muted small mt-1">
                                                            <i class="ti ti-notes me-1"></i>
                                                            <?= htmlspecialchars($term['notes']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-auto ms-auto">
                                                    <div class="btn-list justify-content-end">
                                                        <a href="/calendar/terms/<?= $term['id'] ?>" class="btn btn-sm btn-primary">
                                                            <i class="ti ti-eye me-1"></i>
                                                            View Details
                                                        </a>
                                                        <a href="/calendar/terms/<?= $term['id'] ?>/edit" class="btn btn-sm btn-outline-secondary" title="Edit Term">
                                                            <i class="ti ti-edit"></i>
                                                        </a>
                                                        <?php if (!$term['is_current']): ?>
                                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteTerm(<?= $term['id'] ?>)" title="Delete Term">
                                                                <i class="ti ti-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function filterByYear(year) {
    if (year) {
        window.location.href = '/calendar/terms?year=' + encodeURIComponent(year);
    } else {
        window.location.href = '/calendar/terms';
    }
}

function deleteTerm(termId) {
    if (confirm('Are you sure you want to delete this academic term? This will also delete all important dates associated with it.')) {
        fetch('/calendar/terms/' + termId + '/delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
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
            alert('Failed to delete term');
            console.error('Error:', error);
        });
    }
}
</script>
