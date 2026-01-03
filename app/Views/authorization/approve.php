<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authorization Request</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .auth-card {
            max-width: 600px;
            width: 100%;
        }
        .success-icon {
            font-size: 4rem;
            color: #28a745;
        }
        .error-icon {
            font-size: 4rem;
            color: #dc3545;
        }
        .warning-icon {
            font-size: 4rem;
            color: #ffc107;
        }
    </style>
</head>
<body>

<div class="auth-card">
    <div class="card shadow">
        <?php if (isset($error)): ?>
            <!-- Error State -->
            <div class="card-body text-center py-5">
                <i class="fas fa-exclamation-triangle error-icon mb-3"></i>
                <h2 class="text-danger mb-3">Error</h2>
                <p class="lead"><?= e($error) ?></p>
            </div>

        <?php elseif (isset($success)): ?>
            <!-- Success State -->
            <div class="card-body text-center py-5">
                <i class="fas fa-check-circle success-icon mb-3"></i>
                <h2 class="text-success mb-3">Authorization Approved!</h2>
                <p class="lead">Thank you for approving this authorization request<?php if (!empty($request['school_name'])): ?> for <strong><?= e($request['school_name']) ?><?php if (!empty($request['campus_name'])): ?> - <?= e($request['campus_name']) ?><?php endif; ?></strong><?php endif; ?>.</p>
                <hr class="my-4">
                <div class="text-left">
                    <?php if (!empty($request['school_name'])): ?>
                    <p><strong>School:</strong> <?= e($request['school_name']) ?><?php if (!empty($request['campus_name'])): ?> - <?= e($request['campus_name']) ?><?php endif; ?></p>
                    <?php endif; ?>
                    <p><strong>Request Type:</strong> <?= ucwords(str_replace('_', ' ', e($request['request_type'] ?? 'N/A'))) ?></p>
                    <p><strong>Approved At:</strong> <?= date('F d, Y h:i A') ?></p>
                    <p><strong>Reference:</strong> <?= e($request['id'] ?? 'N/A') ?></p>
                </div>
                <div class="alert alert-info mt-4">
                    <i class="fas fa-info-circle mr-2"></i>
                    You can safely close this page. <?= e($request['school_name'] ?? 'The school') ?> has been notified of your approval.
                </div>
            </div>

        <?php elseif (isset($already_approved)): ?>
            <!-- Already Approved State -->
            <div class="card-body text-center py-5">
                <i class="fas fa-check-circle warning-icon mb-3"></i>
                <h2 class="text-warning mb-3">Already Approved</h2>
                <p class="lead">This authorization request has already been approved.</p>
                <hr class="my-4">
                <div class="text-left">
                    <p><strong>Approved At:</strong> <?= date('F d, Y h:i A', strtotime($request['approved_at'])) ?></p>
                    <p><strong>Reference:</strong> <?= e($request['id'] ?? 'N/A') ?></p>
                </div>
            </div>

        <?php elseif (isset($expired)): ?>
            <!-- Expired State -->
            <div class="card-body text-center py-5">
                <i class="fas fa-hourglass-end error-icon mb-3"></i>
                <h2 class="text-danger mb-3">Link Expired</h2>
                <p class="lead">This authorization link has expired.</p>
                <hr class="my-4">
                <div class="text-left">
                    <p><strong>Expired At:</strong> <?= date('F d, Y h:i A', strtotime($request['expires_at'])) ?></p>
                </div>
                <div class="alert alert-warning mt-4">
                    <i class="fas fa-phone mr-2"></i>
                    Please contact the school to request a new authorization link.
                </div>
            </div>

        <?php elseif (isset($rejected)): ?>
            <!-- Rejected State -->
            <div class="card-body text-center py-5">
                <i class="fas fa-times-circle error-icon mb-3"></i>
                <h2 class="text-danger mb-3">Authorization Declined</h2>
                <p class="lead">This authorization request has been declined.</p>
            </div>

        <?php elseif (isset($request)): ?>
            <!-- Approval Form -->
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">
                    <i class="fas fa-file-signature mr-2"></i>
                    Authorization Request
                </h4>
                <?php if (!empty($request['school_name'])): ?>
                <p class="mb-0 mt-2"><small><i class="fas fa-school mr-1"></i><?= e($request['school_name']) ?><?php if (!empty($request['campus_name'])): ?> - <?= e($request['campus_name']) ?><?php endif; ?></small></p>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>Important:</strong> Please review the authorization request from <strong><?= e($request['school_name'] ?? 'the school') ?></strong> carefully before approving.
                </div>

                <h5 class="mb-3">Request Details</h5>
                <table class="table table-borderless">
                    <tr>
                        <th width="40%">School:</th>
                        <td><strong><?= e($request['school_name'] ?? 'N/A') ?><?php if (!empty($request['campus_name'])): ?> - <?= e($request['campus_name']) ?><?php endif; ?></strong></td>
                    </tr>
                    <tr>
                        <th>Authorization Type:</th>
                        <td><strong><?= ucwords(str_replace('_', ' ', e($request['request_type']))) ?></strong></td>
                    </tr>
                    <tr>
                        <th>Recipient:</th>
                        <td><?= e($request['recipient_name']) ?></td>
                    </tr>
                    <tr>
                        <th>Valid Until:</th>
                        <td><?= date('F d, Y h:i A', strtotime($request['expires_at'])) ?></td>
                    </tr>
                    <tr>
                        <th>Verification Code:</th>
                        <td><code style="font-size: 1.2rem; letter-spacing: 0.2rem;"><?= e($request['verification_code']) ?></code></td>
                    </tr>
                </table>

                <?php if ($request['request_description']): ?>
                <div class="alert alert-light border">
                    <h6>Description:</h6>
                    <p class="mb-0"><?= nl2br(e($request['request_description'])) ?></p>
                </div>
                <?php endif; ?>

                <!-- View Application Details Button -->
                <?php if ($request['entity_type'] === 'applicant'): ?>
                <div class="mt-3">
                    <button class="btn btn-outline-info btn-block" type="button" data-toggle="collapse" data-target="#applicationDetails">
                        <i class="fas fa-eye mr-2"></i>View Application Details
                    </button>
                    <div class="collapse mt-3" id="applicationDetails">
                        <?php
                        // Fetch applicant details
                        try {
                            $pdo = Database::getTenantConnection();
                            $stmt = $pdo->prepare("
                                SELECT a.*, g.grade_name, c.campus_name
                                FROM applicants a
                                LEFT JOIN grades g ON a.grade_applying_for_id = g.id
                                LEFT JOIN campuses c ON a.campus_id = c.id
                                WHERE a.id = ?
                            ");
                            $stmt->execute([$request['entity_id']]);
                            $applicant = $stmt->fetch();

                            if ($applicant):
                        ?>
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-user mr-2"></i>Applicant Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Full Name:</strong> <?= e($applicant['first_name'] . ' ' . ($applicant['middle_name'] ? $applicant['middle_name'] . ' ' : '') . $applicant['last_name']) ?></p>
                                        <p><strong>Date of Birth:</strong> <?= e($applicant['date_of_birth'] ? date('M d, Y', strtotime($applicant['date_of_birth'])) : 'N/A') ?></p>
                                        <p><strong>Gender:</strong> <?= e(ucfirst($applicant['gender'] ?? 'N/A')) ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Grade Applying For:</strong> <?= e($applicant['grade_name'] ?? 'N/A') ?></p>
                                        <p><strong>Campus:</strong> <?= e($applicant['campus_name'] ?? 'N/A') ?></p>
                                        <p><strong>Application Status:</strong> <span class="badge badge-primary"><?= e(ucfirst($applicant['status'])) ?></span></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php
                            // Fetch guardians
                            $stmt = $pdo->prepare("SELECT * FROM applicant_guardians WHERE applicant_id = ? ORDER BY is_primary DESC");
                            $stmt->execute([$request['entity_id']]);
                            $guardians = $stmt->fetchAll();

                            if (!empty($guardians)):
                        ?>
                        <div class="card mt-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-users mr-2"></i>Guardian Information</h6>
                            </div>
                            <div class="card-body">
                                <?php foreach ($guardians as $guardian): ?>
                                <div class="mb-3 pb-3 border-bottom">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Name:</strong> <?= e($guardian['first_name'] . ' ' . $guardian['last_name']) ?>
                                                <?php if ($guardian['is_primary']): ?>
                                                    <span class="badge badge-primary ml-2">Primary</span>
                                                <?php endif; ?>
                                            </p>
                                            <p><strong>Relationship:</strong> <?= e(ucfirst($guardian['relationship'] ?? 'N/A')) ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Phone:</strong> <?= e($guardian['phone'] ?? 'N/A') ?></p>
                                            <p><strong>Email:</strong> <?= e($guardian['email'] ?? 'N/A') ?></p>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php
                            // Fetch siblings
                            $stmt = $pdo->prepare("SELECT * FROM applicant_siblings WHERE applicant_id = ?");
                            $stmt->execute([$request['entity_id']]);
                            $siblings = $stmt->fetchAll();

                            if (!empty($siblings)):
                        ?>
                        <div class="card mt-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-user-friends mr-2"></i>Siblings/Family</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-borderless">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Age/Grade</th>
                                                <th>School</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($siblings as $sibling): ?>
                                            <tr>
                                                <td><?= e($sibling['sibling_name']) ?></td>
                                                <td><?= e($sibling['sibling_grade'] ?? $sibling['sibling_age'] ?? 'N/A') ?></td>
                                                <td><?= e($sibling['sibling_school'] ?? 'N/A') ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php
                            endif; // if applicant
                        } catch (Exception $e) {
                            echo '<div class="alert alert-warning">Unable to load application details.</div>';
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="alert alert-warning mt-4">
                    <h6><i class="fas fa-exclamation-triangle mr-2"></i>What am I authorizing?</h6>
                    <?php if ($request['request_type'] === 'data_consent'): ?>
                        <p class="mb-0">You are authorizing the school to process, store, and manage student data in accordance with applicable data protection regulations.</p>
                    <?php elseif ($request['request_type'] === 'photo_consent'): ?>
                        <p class="mb-0">You are authorizing the school to use photos and videos of the student in school publications, website, and social media.</p>
                    <?php elseif ($request['request_type'] === 'medical_consent'): ?>
                        <p class="mb-0">You are authorizing the school to administer emergency medical treatment if needed.</p>
                    <?php else: ?>
                        <p class="mb-0">Please review the authorization type and description above.</p>
                    <?php endif; ?>
                </div>

                <form method="POST" action="/authorize/<?= e($request['token']) ?>">
                    <div class="row mt-4">
                        <div class="col-md-6 mb-2">
                            <button type="submit" name="action" value="approve" class="btn btn-success btn-lg btn-block">
                                <i class="fas fa-check mr-2"></i>Approve Authorization
                            </button>
                        </div>
                        <div class="col-md-6 mb-2">
                            <button type="submit" name="action" value="reject" class="btn btn-danger btn-lg btn-block">
                                <i class="fas fa-times mr-2"></i>Decline
                            </button>
                        </div>
                    </div>
                </form>

                <div class="text-center mt-4">
                    <small class="text-muted">
                        <i class="fas fa-lock mr-1"></i>
                        This is a secure authorization page. Your action will be recorded.
                    </small>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="text-center mt-3">
        <small class="text-white">
            <i class="fas fa-shield-alt mr-1"></i>
            Secured Authorization System
        </small>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
