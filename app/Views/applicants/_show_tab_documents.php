<?php
/**
 * Applicant Profile - Documents Tab
 */

// Helper function for document status badge
function getDocumentStatusBadge($status) {
    $badges = [
        'pending' => 'warning',
        'uploaded' => 'info',
        'verified' => 'success',
        'rejected' => 'danger'
    ];
    $color = $badges[$status] ?? 'secondary';
    return "<span class='badge badge-{$color}'>" . ucfirst($status) . "</span>";
}
?>

<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0" style="font-size: 14px; font-weight: 600; color: #605e5c;">Document Checklist</h5>
        <small class="text-muted">
            <i class="fas fa-info-circle mr-1"></i>
            All documents are <strong>optional</strong>
        </small>
    </div>
        <?php
        $requiredDocs = [
            'birth_certificate' => ['label' => 'Birth Certificate', 'icon' => 'fa-file-alt', 'required' => false],
            'previous_report' => ['label' => 'Previous School Report', 'icon' => 'fa-graduation-cap', 'required' => false],
            'passport_photo' => ['label' => 'Passport Photo', 'icon' => 'fa-camera', 'required' => false],
            'id_copy_guardian' => ['label' => 'Guardian ID Copy', 'icon' => 'fa-id-card', 'required' => false],
            'immunization_card' => ['label' => 'Immunization Card', 'icon' => 'fa-syringe', 'required' => false],
            'transfer_letter' => ['label' => 'Transfer Letter (if applicable)', 'icon' => 'fa-file-signature', 'required' => false],
        ];

        // Group uploaded documents by type
        $uploadedDocs = [];
        foreach ($documents as $doc) {
            $uploadedDocs[$doc['document_type']][] = $doc;
        }
        ?>

        <div class="list-group list-group-flush">
            <?php foreach ($requiredDocs as $type => $info): ?>
            <?php
                $isUploaded = isset($uploadedDocs[$type]);
                $uploadedFiles = $uploadedDocs[$type] ?? [];
            ?>
            <div class="list-group-item px-0">
                <div class="row align-items-center">
                    <div class="col-auto" style="width: 60px; text-align: center;">
                        <i class="fas <?= $info['icon'] ?> fa-2x <?= $isUploaded ? 'text-success' : 'text-muted' ?>"></i>
                    </div>
                    <div class="col">
                        <div class="d-flex align-items-center">
                            <strong><?= e($info['label']) ?></strong>
                            <?php if (!$info['required']): ?>
                                <span class="badge badge-secondary ml-2" style="font-size: 0.65rem;">Optional</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($isUploaded): ?>
                            <small class="text-success">
                                <i class="fas fa-check-circle mr-1"></i>
                                <?= count($uploadedFiles) ?> file<?= count($uploadedFiles) > 1 ? 's' : '' ?> uploaded
                            </small>
                        <?php else: ?>
                            <small class="text-muted">Not uploaded yet</small>
                        <?php endif; ?>
                    </div>
                    <div class="col-auto">
                        <?php if (hasPermission('Students.write') || Gate::hasRole('ADMIN')): ?>
                            <button class="btn btn-sm btn-outline-primary" onclick="showUploadModal('<?= $type ?>', '<?= e($info['label']) ?>', <?= $applicant['id'] ?>)">
                                <i class="fas fa-upload mr-1"></i> Upload
                            </button>
                        <?php endif; ?>
                        <?php if ($isUploaded): ?>
                            <button class="btn btn-sm btn-outline-secondary" onclick="showDocumentFiles('<?= $type ?>')">
                                <i class="fas fa-eye mr-1"></i> View (<?= count($uploadedFiles) ?>)
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Uploaded Files for this Type (Hidden by default) -->
                <?php if ($isUploaded): ?>
                <div id="files-<?= $type ?>" class="mt-3 ml-5" style="display: none;">
                    <table class="table table-sm table-bordered">
                        <thead class="thead-light">
                            <tr>
                                <th width="40%">File Name</th>
                                <th width="20%">Status</th>
                                <th width="20%">Uploaded</th>
                                <th width="20%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($uploadedFiles as $doc): ?>
                            <tr>
                                <td>
                                    <i class="fas fa-file-image mr-2 text-info"></i>
                                    <?= e($doc['file_name']) ?>
                                    <?php if (!empty($doc['notes'])): ?>
                                        <br><small class="text-muted"><?= e($doc['notes']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= getDocumentStatusBadge($doc['verification_status']) ?></td>
                                <td>
                                    <small><?= date('M d, Y', strtotime($doc['uploaded_at'])) ?></small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-sm btn-info" title="Download" onclick="downloadDocument('applicants', <?= $doc['id'] ?>, '<?= e($doc['file_name']) ?>')">
                                            <i class="fas fa-download me-1"></i> Download
                                        </button>
                                        <?php if (hasPermission('Students.write') || Gate::hasRole('ADMIN')): ?>
                                            <button class="btn btn-sm btn-danger" onclick="deleteDocument(<?= $doc['id'] ?>, <?= $applicant['id'] ?>)" title="Delete">
                                                <i class="fas fa-trash me-1"></i> Delete
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
</div>
