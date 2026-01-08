<?php
$isEdit = isset($term) && $term !== null;
$formAction = $isEdit ? "/calendar/terms/{$term['id']}/edit" : "/calendar/terms/create";
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
                    <?= $isEdit ? 'Edit Academic Term' : 'Create New Academic Term' ?>
                </h2>
            </div>
        </div>
    </div>

    <!-- Form -->
    <div class="page-body">
        <div class="row">
            <div class="col-lg-8">
                <form method="POST" action="<?= $formAction ?>" id="termForm">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Term Details</h3>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <!-- Academic Year -->
                                <div class="col-md-6">
                                    <label class="form-label required">Academic Year</label>
                                    <input type="text"
                                           class="form-control"
                                           name="academic_year"
                                           placeholder="e.g., 2026/2027"
                                           value="<?= $isEdit ? htmlspecialchars($term['academic_year']) : '' ?>"
                                           required>
                                    <small class="form-hint">Format: YYYY/YYYY</small>
                                </div>

                                <!-- Term Number -->
                                <div class="col-md-6">
                                    <label class="form-label required">Term Number</label>
                                    <select class="form-select" name="term_number" required>
                                        <option value="">Select term number...</option>
                                        <option value="1" <?= $isEdit && $term['term_number'] == 1 ? 'selected' : '' ?>>Term 1</option>
                                        <option value="2" <?= $isEdit && $term['term_number'] == 2 ? 'selected' : '' ?>>Term 2</option>
                                        <option value="3" <?= $isEdit && $term['term_number'] == 3 ? 'selected' : '' ?>>Term 3</option>
                                    </select>
                                </div>

                                <!-- Term Name -->
                                <div class="col-12">
                                    <label class="form-label required">Term Name</label>
                                    <input type="text"
                                           class="form-control"
                                           name="term_name"
                                           placeholder="e.g., Term 1, First Term, Spring Term"
                                           value="<?= $isEdit ? htmlspecialchars($term['term_name']) : '' ?>"
                                           required>
                                </div>

                                <!-- Campus (if multi-campus) -->
                                <?php if (!empty($campuses)): ?>
                                    <div class="col-12">
                                        <label class="form-label">Campus</label>
                                        <select class="form-select" name="campus_id">
                                            <option value="">All Campuses</option>
                                            <?php foreach ($campuses as $campus): ?>
                                                <option value="<?= $campus['id'] ?>"
                                                        <?= $isEdit && $term['campus_id'] == $campus['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($campus['campus_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="form-hint">Leave blank for all campuses</small>
                                    </div>
                                <?php endif; ?>

                                <!-- Start Date -->
                                <div class="col-md-6">
                                    <label class="form-label required">Start Date</label>
                                    <input type="date"
                                           class="form-control"
                                           name="start_date"
                                           id="startDate"
                                           value="<?= $isEdit ? $term['start_date'] : '' ?>"
                                           required
                                           onchange="calculateDuration()">
                                </div>

                                <!-- End Date -->
                                <div class="col-md-6">
                                    <label class="form-label required">End Date</label>
                                    <input type="date"
                                           class="form-control"
                                           name="end_date"
                                           id="endDate"
                                           value="<?= $isEdit ? $term['end_date'] : '' ?>"
                                           required
                                           onchange="calculateDuration()">
                                </div>

                                <!-- Duration Display -->
                                <div class="col-12">
                                    <div class="alert alert-info mb-0" id="durationDisplay" style="display: none;">
                                        <i class="ti ti-info-circle me-2"></i>
                                        <strong>Duration:</strong> <span id="durationText"></span>
                                    </div>
                                </div>

                                <!-- Status -->
                                <div class="col-md-6">
                                    <label class="form-label required">Status</label>
                                    <select class="form-select" name="status" id="statusSelect" required>
                                        <option value="draft" <?= $isEdit && $term['status'] === 'draft' ? 'selected' : '' ?>>
                                            Draft
                                        </option>
                                        <option value="published" <?= $isEdit && $term['status'] === 'published' ? 'selected' : '' ?>>
                                            Published
                                        </option>
                                        <option value="current" <?= $isEdit && $term['status'] === 'current' ? 'selected' : '' ?>>
                                            Current (Active)
                                        </option>
                                        <option value="completed" <?= $isEdit && $term['status'] === 'completed' ? 'selected' : '' ?>>
                                            Completed
                                        </option>
                                    </select>
                                    <small class="form-hint">
                                        Setting to "Current" will mark this as the active term
                                    </small>
                                </div>

                                <!-- Notes -->
                                <div class="col-12">
                                    <label class="form-label">Notes</label>
                                    <textarea class="form-control"
                                              name="notes"
                                              rows="3"
                                              placeholder="Optional notes about this term..."><?= $isEdit ? htmlspecialchars($term['notes'] ?? '') : '' ?></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <div class="d-flex">
                                <a href="/calendar/terms" class="btn btn-link">Cancel</a>
                                <button type="submit" class="btn btn-primary ms-auto">
                                    <i class="ti ti-device-floppy me-1"></i>
                                    <?= $isEdit ? 'Update Term' : 'Create Term' ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Sidebar with Help -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-help me-2"></i>
                            Quick Guide
                        </h3>
                    </div>
                    <div class="card-body">
                        <h4 class="mb-2">Academic Year Format</h4>
                        <p class="text-muted small">
                            Use the format YYYY/YYYY for the academic year. For example, "2026/2027" for the academic year starting in 2026.
                        </p>

                        <hr class="my-3">

                        <h4 class="mb-2">Term Numbers</h4>
                        <p class="text-muted small">
                            Most schools have 3 terms per academic year. Select the appropriate term number (1, 2, or 3).
                        </p>

                        <hr class="my-3">

                        <h4 class="mb-2">Status Options</h4>
                        <ul class="text-muted small ps-3 mb-0">
                            <li><strong>Draft:</strong> Term is being planned</li>
                            <li><strong>Published:</strong> Term is finalized and visible</li>
                            <li><strong>Current:</strong> This is the active term</li>
                            <li><strong>Completed:</strong> Term has ended</li>
                        </ul>

                        <hr class="my-3">

                        <div class="alert alert-warning mb-0">
                            <i class="ti ti-alert-triangle me-2"></i>
                            <strong>Note:</strong> Only one term can be marked as "Current" at a time. Setting a term to "Current" will automatically update other terms.
                        </div>
                    </div>
                </div>

                <!-- Recommended Term Lengths -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-calendar-stats me-2"></i>
                            Recommended Term Lengths
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <div class="list-group-item">
                                <div class="row">
                                    <div class="col">Term 1</div>
                                    <div class="col-auto text-muted">13-14 weeks</div>
                                </div>
                            </div>
                            <div class="list-group-item">
                                <div class="row">
                                    <div class="col">Term 2</div>
                                    <div class="col-auto text-muted">13-14 weeks</div>
                                </div>
                            </div>
                            <div class="list-group-item">
                                <div class="row">
                                    <div class="col">Term 3</div>
                                    <div class="col-auto text-muted">11-12 weeks</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function calculateDuration() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;

    if (startDate && endDate) {
        const start = new Date(startDate);
        const end = new Date(endDate);

        if (end > start) {
            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            const diffWeeks = Math.floor(diffDays / 7);

            const durationDisplay = document.getElementById('durationDisplay');
            const durationText = document.getElementById('durationText');

            durationText.textContent = `${diffDays} days (~${diffWeeks} weeks)`;
            durationDisplay.style.display = 'block';
        } else {
            document.getElementById('durationDisplay').style.display = 'none';
        }
    }
}

// Calculate duration on page load if editing
window.addEventListener('DOMContentLoaded', function() {
    calculateDuration();
});
</script>
