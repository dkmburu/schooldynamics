<?php
/**
 * Reusable Document List Partial
 *
 * Usage:
 * $documents - Array of documents
 * $entityType - 'applicants' or 'staff' (for download URL)
 * $entityId - The ID of the parent entity (for delete)
 * $showUpload - Whether to show upload button (default: false)
 * $showDelete - Whether to show delete button (default: true)
 * $layout - 'table' or 'cards' (default: 'table')
 */

$documents = $documents ?? [];
$entityType = $entityType ?? 'applicants';
$entityId = $entityId ?? null;
$showUpload = $showUpload ?? false;
$showDelete = $showDelete ?? true;
$layout = $layout ?? 'table';

// Determine date field (staff uses uploaded_at, applicants might use created_at)
$dateField = 'uploaded_at';
?>

<?php if (empty($documents)): ?>
<div class="empty py-4">
    <div class="empty-icon">
        <i class="ti ti-file-off" style="font-size: 2.5rem; opacity: 0.5;"></i>
    </div>
    <p class="empty-title">No documents uploaded</p>
    <p class="empty-subtitle text-muted">
        Documents will appear here once uploaded
    </p>
</div>
<?php elseif ($layout === 'cards'): ?>
<!-- Card Layout -->
<div class="row g-3">
    <?php foreach ($documents as $doc): ?>
    <div class="col-md-4 col-lg-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start">
                    <span class="avatar bg-primary-lt me-3">
                        <i class="ti ti-file-text"></i>
                    </span>
                    <div class="flex-fill min-width-0">
                        <strong class="d-block text-truncate" title="<?= e($doc['document_type'] ?? 'Document') ?>">
                            <?= e(ucwords(str_replace('_', ' ', $doc['document_type'] ?? 'Document'))) ?>
                        </strong>
                        <div class="text-muted small">
                            <?= date('j M Y', strtotime($doc[$dateField] ?? $doc['created_at'] ?? 'now')) ?>
                        </div>
                        <?php if (!empty($doc['verification_status'])): ?>
                        <?php
                        $statusColors = ['uploaded' => 'warning', 'verified' => 'success', 'rejected' => 'danger'];
                        $color = $statusColors[$doc['verification_status']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?= $color ?>-lt mt-1"><?= ucfirst($doc['verification_status']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="card-footer pt-0">
                <div class="btn-group btn-group-sm w-100">
                    <button type="button" class="btn btn-outline-primary" onclick="downloadDocument('<?= $entityType ?>', <?= $doc['id'] ?>, '<?= e($doc['file_name'] ?? $doc['document_name'] ?? 'document') ?>')">
                        <i class="ti ti-download me-1"></i> Download
                    </button>
                    <?php if ($showDelete): ?>
                    <button type="button" class="btn btn-outline-danger" onclick="deleteDocumentConfirm('<?= $entityType ?>', <?= $doc['id'] ?>, <?= $entityId ?>)">
                        <i class="ti ti-trash"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<!-- Table Layout -->
<div class="table-responsive">
    <table class="table table-vcenter">
        <thead>
            <tr>
                <th>Document Type</th>
                <th>File Name</th>
                <th>Upload Method</th>
                <th>Date</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($documents as $doc): ?>
            <tr id="doc-row-<?= $doc['id'] ?>">
                <td>
                    <i class="ti ti-file me-2"></i>
                    <?= e(ucwords(str_replace('_', ' ', $doc['document_type'] ?? 'Document'))) ?>
                </td>
                <td class="text-muted"><?= e($doc['file_name'] ?? $doc['document_name'] ?? 'N/A') ?></td>
                <td>
                    <?php if (($doc['upload_method'] ?? 'computer') === 'phone'): ?>
                    <span class="badge bg-info-lt"><i class="ti ti-device-mobile me-1"></i>Phone</span>
                    <?php else: ?>
                    <span class="badge bg-secondary-lt"><i class="ti ti-device-laptop me-1"></i>Computer</span>
                    <?php endif; ?>
                </td>
                <td><?= date('M j, Y', strtotime($doc[$dateField] ?? $doc['created_at'] ?? 'now')) ?></td>
                <td>
                    <?php
                    $statusColors = ['uploaded' => 'warning', 'verified' => 'success', 'rejected' => 'danger'];
                    $color = $statusColors[$doc['verification_status'] ?? 'uploaded'] ?? 'secondary';
                    ?>
                    <span class="badge bg-<?= $color ?>"><?= ucfirst($doc['verification_status'] ?? 'uploaded') ?></span>
                </td>
                <td class="text-end">
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-sm btn-outline-primary" title="Download" onclick="downloadDocument('<?= $entityType ?>', <?= $doc['id'] ?>, '<?= e($doc['file_name'] ?? $doc['document_name'] ?? 'document') ?>')">
                            <i class="ti ti-download me-1"></i> Download
                        </button>
                        <?php if ($showDelete): ?>
                        <button type="button" class="btn btn-sm btn-outline-danger" title="Delete" onclick="deleteDocumentConfirm('<?= $entityType ?>', <?= $doc['id'] ?>, <?= $entityId ?>)">
                            <i class="ti ti-trash"></i>
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

<script>
// Download document using iframe (most reliable method, bypasses all routing)
if (typeof window.downloadDocument === 'undefined') {
    window.downloadDocument = function(entity, docId, fileName) {
        const iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        iframe.src = '/download.php?entity=' + encodeURIComponent(entity) + '&id=' + encodeURIComponent(docId);
        document.body.appendChild(iframe);
        setTimeout(function() {
            if (iframe.parentNode) {
                iframe.parentNode.removeChild(iframe);
            }
        }, 30000);
    };
}

// Delete document with confirmation
if (typeof window.deleteDocumentConfirm === 'undefined') {
    window.deleteDocumentConfirm = async function(entity, docId, entityId) {
        if (!confirm('Are you sure you want to delete this document?')) {
            return;
        }

        try {
            const endpoint = entity === 'staff' ? '/hr-payroll/documents/delete' : '/applicants/documents/delete';
            const bodyData = entity === 'staff'
                ? { document_id: docId }
                : { document_id: docId, applicant_id: entityId };

            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(bodyData)
            });

            const data = await response.json();

            if (data.success) {
                // Remove row from table or reload page
                const row = document.getElementById('doc-row-' + docId);
                if (row) {
                    row.remove();
                } else {
                    window.location.reload();
                }
            } else {
                alert('Failed to delete document: ' + (data.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error deleting document:', error);
            alert('Failed to delete document. Please try again.');
        }
    };
}
</script>
