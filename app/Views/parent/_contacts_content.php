<?php
$contacts = $contacts ?? [];
$classTeachers = $classTeachers ?? [];
?>

<div class="container py-4">
    <!-- Header -->
    <div class="mb-4">
        <h3 class="mb-0"><i class="ti ti-phone me-2"></i> School Contacts</h3>
        <small class="text-muted">Directory of school departments and key contacts</small>
    </div>

    <!-- Class Teachers Section -->
    <?php if (!empty($classTeachers)): ?>
        <div class="mb-4">
            <h4 class="mb-3"><i class="ti ti-user-check me-2"></i> Class Teachers</h4>
            <div class="row">
                <?php foreach ($classTeachers as $teacher): ?>
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-start">
                                    <div class="avatar bg-primary-lt text-primary me-3" style="width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                        <i class="ti ti-user" style="font-size: 1.5rem;"></i>
                                    </div>
                                    <div class="flex-fill">
                                        <h5 class="mb-1"><?= e($teacher['teacher_first_name'] . ' ' . $teacher['teacher_last_name']) ?></h5>
                                        <p class="text-muted mb-2">
                                            <small>
                                                <i class="ti ti-user me-1"></i><?= e($teacher['student_name']) ?>
                                                <?php if (!empty($teacher['grade_name'])): ?>
                                                    <span class="mx-1">·</span>
                                                    <i class="ti ti-school me-1"></i><?= e($teacher['grade_name']) ?>
                                                    <?php if (!empty($teacher['stream_name'])): ?>
                                                        <?= e($teacher['stream_name']) ?>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </small>
                                        </p>
                                        <div class="contact-info">
                                            <?php if (!empty($teacher['teacher_phone'])): ?>
                                                <div class="mb-1">
                                                    <i class="ti ti-phone text-muted me-2"></i>
                                                    <a href="tel:<?= e($teacher['teacher_phone']) ?>"><?= e($teacher['teacher_phone']) ?></a>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($teacher['teacher_email'])): ?>
                                                <div>
                                                    <i class="ti ti-mail text-muted me-2"></i>
                                                    <a href="mailto:<?= e($teacher['teacher_email']) ?>"><?= e($teacher['teacher_email']) ?></a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- School Contacts Section -->
    <h4 class="mb-3"><i class="ti ti-building me-2"></i> School Departments</h4>

    <?php if (empty($contacts)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="ti ti-phone-off text-muted" style="font-size: 4rem;"></i>
                <h4 class="mt-3">No Contacts Available</h4>
                <p class="text-muted">School contact information is not available at this time.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <?php
            // Group contacts by type
            $groupedContacts = [];
            foreach ($contacts as $contact) {
                $typeId = $contact['contact_type_id'];
                if (!isset($groupedContacts[$typeId])) {
                    $groupedContacts[$typeId] = [
                        'type_name' => $contact['type_name'],
                        'type_icon' => $contact['type_icon'] ?? 'ti-phone',
                        'contacts' => []
                    ];
                }
                $groupedContacts[$typeId]['contacts'][] = $contact;
            }

            foreach ($groupedContacts as $group):
                $iconClass = $group['type_icon'];
                $isEmergency = strpos(strtolower($group['type_name']), 'emergency') !== false;
                $cardClass = $isEmergency ? 'border-danger' : '';
                $badgeClass = $isEmergency ? 'bg-danger' : 'bg-primary';
            ?>
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card h-100 <?= $cardClass ?>">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="ti <?= $iconClass ?> me-2"></i>
                                <?= e($group['type_name']) ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($group['contacts'] as $contact): ?>
                                <div class="mb-3 pb-3 <?= $contact !== end($group['contacts']) ? 'border-bottom' : '' ?>">
                                    <?php if (!empty($contact['department_name'])): ?>
                                        <h6 class="mb-2"><?= e($contact['department_name']) ?></h6>
                                    <?php endif; ?>

                                    <?php if (!empty($contact['contact_person'])): ?>
                                        <p class="text-muted mb-2"><small><?= e($contact['contact_person']) ?></small></p>
                                    <?php endif; ?>

                                    <?php if (!empty($contact['phone'])): ?>
                                        <div class="mb-1">
                                            <i class="ti ti-phone text-muted me-2"></i>
                                            <a href="tel:<?= e($contact['phone']) ?>" class="<?= $isEmergency ? 'text-danger fw-bold' : '' ?>">
                                                <?= e($contact['phone']) ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($contact['email'])): ?>
                                        <div class="mb-1">
                                            <i class="ti ti-mail text-muted me-2"></i>
                                            <a href="mailto:<?= e($contact['email']) ?>">
                                                <?= e($contact['email']) ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($contact['office_location'])): ?>
                                        <div class="mb-1">
                                            <i class="ti ti-map-pin text-muted me-2"></i>
                                            <small><?= e($contact['office_location']) ?></small>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($contact['available_hours'])): ?>
                                        <div>
                                            <i class="ti ti-clock text-muted me-2"></i>
                                            <small class="text-muted"><?= e($contact['available_hours']) ?></small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Quick Links -->
    <div class="card mt-4 bg-light">
        <div class="card-body">
            <h5 class="mb-3"><i class="ti ti-info-circle me-2"></i> Important Information</h5>
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-2">
                        <i class="ti ti-clock text-primary me-2"></i>
                        <strong>Office Hours:</strong> Monday - Friday, 8:00 AM - 5:00 PM
                    </p>
                    <p class="mb-0">
                        <i class="ti ti-alert-circle text-warning me-2"></i>
                        <strong>Emergency:</strong> For urgent matters outside office hours, use the emergency contact above
                    </p>
                </div>
                <div class="col-md-6">
                    <p class="mb-2">
                        <i class="ti ti-message-circle text-primary me-2"></i>
                        Need to raise a concern? <a href="/parent/feedback">Submit a ticket</a>
                    </p>
                    <p class="mb-0">
                        <i class="ti ti-bell text-primary me-2"></i>
                        Check your <a href="/parent/notifications">notifications</a> for important updates
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
