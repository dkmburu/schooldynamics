<?php
$pendingAdmissions = $pendingAdmissions ?? [];
$maxStudents = $maxStudents ?? 5;
$schoolName = $_SESSION['tenant_name'] ?? 'School';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Header -->
            <div class="mb-4">
                <a href="/parent/dashboard" class="btn btn-ghost-secondary btn-sm mb-3">
                    <i class="ti ti-arrow-left me-1"></i> Back to Dashboard
                </a>
                <h3 class="mb-1">Link Students to Your Account</h3>
                <p class="text-muted mb-0">Enter your child's admission number and grade to request access to their information.</p>
            </div>

            <?php if ($success = flash('success')): ?>
                <div class="alert alert-success alert-dismissible mb-3">
                    <div class="d-flex"><div><i class="ti ti-check me-2"></i></div><div><?= $success ?></div></div>
                    <a class="btn-close btn-close-sm" data-bs-dismiss="alert"></a>
                </div>
            <?php endif; ?>

            <?php if ($error = flash('error')): ?>
                <div class="alert alert-danger alert-dismissible mb-3">
                    <div class="d-flex"><div><i class="ti ti-alert-circle me-2"></i></div><div><?= $error ?></div></div>
                    <a class="btn-close btn-close-sm" data-bs-dismiss="alert"></a>
                </div>
            <?php endif; ?>

            <?php if ($warning = flash('warning')): ?>
                <div class="alert alert-warning alert-dismissible mb-3">
                    <div class="d-flex"><div><i class="ti ti-alert-triangle me-2"></i></div><div><?= $warning ?></div></div>
                    <a class="btn-close btn-close-sm" data-bs-dismiss="alert"></a>
                </div>
            <?php endif; ?>

            <!-- Info Card -->
            <div class="card mb-4 bg-azure-lt border-0">
                <div class="card-body">
                    <div class="d-flex">
                        <i class="ti ti-info-circle me-3 text-azure" style="font-size: 1.5rem;"></i>
                        <div>
                            <h5 class="mb-2">How it works</h5>
                            <ol class="mb-0 ps-3">
                                <li class="mb-1">Enter your child's admission number exactly as shown on their school documents.</li>
                                <li class="mb-1">Enter their current grade/class (e.g., "Grade 5" or "Form 2").</li>
                                <li class="mb-1">Submit the request - the school will verify your relationship.</li>
                                <li>Once approved, you'll see your child's information on your dashboard.</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Requests Warning -->
            <?php if (!empty($pendingAdmissions)): ?>
                <div class="alert alert-info mb-4">
                    <div class="d-flex align-items-center">
                        <i class="ti ti-clock me-2"></i>
                        <div>
                            <strong>Pending Requests:</strong>
                            You already have pending requests for:
                            <?= e(implode(', ', $pendingAdmissions)) ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Link Student Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="ti ti-user-plus me-2"></i> Student Information</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="/parent/link-student" id="linkStudentForm">
                        <?= csrfField() ?>

                        <div id="studentEntries">
                            <!-- First student entry (always visible) -->
                            <div class="student-entry mb-4" data-index="0">
                                <div class="d-flex align-items-center mb-3">
                                    <span class="badge bg-primary me-2">Student 1</span>
                                    <button type="button" class="btn btn-ghost-danger btn-sm ms-auto remove-student d-none">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Admission Number <span class="text-danger">*</span></label>
                                        <input type="text" name="students[0][admission_number]" class="form-control" placeholder="e.g., ADM/2024/001" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Grade / Class <span class="text-danger">*</span></label>
                                        <input type="text" name="students[0][grade_name]" class="form-control" placeholder="e.g., Grade 5 or Form 2" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Add More Button -->
                        <?php if ($maxStudents > 1): ?>
                            <div class="mb-4">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="addStudentBtn">
                                    <i class="ti ti-plus me-1"></i> Add Another Student
                                </button>
                                <small class="text-muted ms-2">Up to <?= $maxStudents ?> students</small>
                            </div>
                        <?php endif; ?>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="/parent/dashboard" class="btn btn-ghost-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="ti ti-send me-1"></i> Submit Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const maxStudents = <?= $maxStudents ?>;
    let studentCount = 1;
    const container = document.getElementById('studentEntries');
    const addBtn = document.getElementById('addStudentBtn');

    if (addBtn) {
        addBtn.addEventListener('click', function() {
            if (studentCount >= maxStudents) {
                alert('Maximum number of students reached.');
                return;
            }

            const index = studentCount;
            const entryHtml = `
                <div class="student-entry mb-4" data-index="${index}">
                    <hr class="mb-4">
                    <div class="d-flex align-items-center mb-3">
                        <span class="badge bg-primary me-2">Student ${index + 1}</span>
                        <button type="button" class="btn btn-ghost-danger btn-sm ms-auto remove-student">
                            <i class="ti ti-trash"></i> Remove
                        </button>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Admission Number <span class="text-danger">*</span></label>
                            <input type="text" name="students[${index}][admission_number]" class="form-control" placeholder="e.g., ADM/2024/001">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Grade / Class <span class="text-danger">*</span></label>
                            <input type="text" name="students[${index}][grade_name]" class="form-control" placeholder="e.g., Grade 5 or Form 2">
                        </div>
                    </div>
                </div>
            `;

            container.insertAdjacentHTML('beforeend', entryHtml);
            studentCount++;

            if (studentCount >= maxStudents) {
                addBtn.disabled = true;
            }

            // Focus on the new admission number field
            container.querySelector(`.student-entry[data-index="${index}"] input`).focus();
        });
    }

    // Remove student handler
    container.addEventListener('click', function(e) {
        if (e.target.closest('.remove-student')) {
            const entry = e.target.closest('.student-entry');
            entry.remove();
            studentCount--;

            if (addBtn) {
                addBtn.disabled = false;
            }

            // Renumber remaining entries
            document.querySelectorAll('.student-entry').forEach((entry, idx) => {
                entry.querySelector('.badge').textContent = `Student ${idx + 1}`;
            });
        }
    });

    // Form submit handler
    document.getElementById('linkStudentForm').addEventListener('submit', function(e) {
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Submitting...';
    });
});
</script>
