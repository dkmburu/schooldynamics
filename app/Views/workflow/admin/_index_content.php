<?php
/**
 * Workflow Admin - List Content
 */
?>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col">
        <h2 class="page-title">
            <i class="fas fa-project-diagram text-primary mr-2"></i>
            Workflow Management
        </h2>
        <p class="text-muted mb-0">
            Design and manage automated workflows for your school processes.
        </p>
    </div>
    <div class="col-auto">
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createWorkflowModal">
            <i class="fas fa-plus mr-1"></i> New Workflow
        </button>
    </div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stats-card border-primary">
            <div class="card-body">
                <h6 class="text-muted text-uppercase">Total Workflows</h6>
                <h3 class="mb-0"><?= count($workflows ?? []) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card border-success">
            <div class="card-body">
                <h6 class="text-muted text-uppercase">Active</h6>
                <h3 class="mb-0"><?= count(array_filter($workflows ?? [], function($w) { return $w['is_active']; })) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card border-warning">
            <div class="card-body">
                <h6 class="text-muted text-uppercase">Draft</h6>
                <h3 class="mb-0"><?= count(array_filter($workflows ?? [], function($w) { return !$w['is_active']; })) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card border-info">
            <div class="card-body">
                <h6 class="text-muted text-uppercase">Active Tickets</h6>
                <h3 class="mb-0"><?= $activeTicketsCount ?? 0 ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Workflows Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title mb-0">
            <i class="fas fa-list mr-2"></i>
            All Workflows
        </h3>
    </div>
    <div class="card-body p-0">
        <?php if (empty($workflows)): ?>
        <div class="text-center py-5">
            <i class="fas fa-project-diagram fa-4x text-muted mb-3"></i>
            <h4 class="text-muted">No workflows yet</h4>
            <p class="text-muted">Create your first workflow to automate school processes.</p>
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createWorkflowModal">
                <i class="fas fa-plus mr-1"></i> Create First Workflow
            </button>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Workflow</th>
                        <th>Code</th>
                        <th>Entity Type</th>
                        <th>Steps</th>
                        <th>Active Tickets</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($workflows as $workflow): ?>
                    <tr>
                        <td>
                            <a href="/workflow/admin/<?= $workflow['id'] ?>" class="font-weight-bold text-primary">
                                <?= e($workflow['name']) ?>
                            </a>
                            <?php if ($workflow['description']): ?>
                            <br><small class="text-muted"><?= e(substr($workflow['description'], 0, 60)) ?><?= strlen($workflow['description']) > 60 ? '...' : '' ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <code class="text-dark"><?= e($workflow['code']) ?></code>
                        </td>
                        <td>
                            <?php
                            $entityIcons = [
                                'applicant' => 'fas fa-user-graduate',
                                'student' => 'fas fa-user',
                                'staff' => 'fas fa-user-tie',
                                'invoice' => 'fas fa-file-invoice-dollar',
                                'leave_request' => 'fas fa-calendar-minus'
                            ];
                            $icon = $entityIcons[$workflow['entity_type']] ?? 'fas fa-cube';
                            ?>
                            <i class="<?= $icon ?> mr-1 text-muted"></i>
                            <?= ucfirst(str_replace('_', ' ', $workflow['entity_type'])) ?>
                        </td>
                        <td>
                            <span class="badge badge-info"><?= $workflow['step_count'] ?? 0 ?> steps</span>
                        </td>
                        <td>
                            <?php if (($workflow['active_tickets'] ?? 0) > 0): ?>
                            <span class="badge badge-warning"><?= $workflow['active_tickets'] ?> active</span>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($workflow['is_active']): ?>
                            <span class="badge badge-success">Active</span>
                            <?php else: ?>
                            <span class="badge badge-secondary">Draft</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="/workflow/admin/<?= $workflow['id'] ?>" class="btn btn-sm btn-primary" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-secondary" title="Clone"
                                        onclick="cloneWorkflow(<?= $workflow['id'] ?>, '<?= e($workflow['name']) ?>')">
                                    <i class="fas fa-copy"></i>
                                </button>
                                <?php if (!$workflow['is_active'] && ($workflow['active_tickets'] ?? 0) == 0): ?>
                                <button type="button" class="btn btn-sm btn-outline-danger" title="Delete"
                                        onclick="deleteWorkflow(<?= $workflow['id'] ?>, '<?= e($workflow['name']) ?>')">
                                    <i class="fas fa-trash"></i>
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
</div>

<!-- Workflow Templates Section -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title mb-0">
            <i class="fas fa-magic mr-2"></i>
            Quick Start Templates
        </h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="card h-100 border-left-primary" style="border-left: 3px solid var(--sd-primary);">
                    <div class="card-body">
                        <h5><i class="fas fa-user-graduate text-primary mr-2"></i>Admissions Workflow</h5>
                        <p class="text-muted small mb-3">Complete student admission process from application to enrollment.</p>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="createFromTemplate('admissions')">
                            Use Template
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card h-100 border-left-success" style="border-left: 3px solid var(--sd-success);">
                    <div class="card-body">
                        <h5><i class="fas fa-calendar-minus text-success mr-2"></i>Leave Request</h5>
                        <p class="text-muted small mb-3">Staff leave request approval workflow with manager review.</p>
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="createFromTemplate('leave_request')">
                            Use Template
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card h-100 border-left-warning" style="border-left: 3px solid var(--sd-warning);">
                    <div class="card-body">
                        <h5><i class="fas fa-file-invoice-dollar text-warning mr-2"></i>Fee Approval</h5>
                        <p class="text-muted small mb-3">Fee waiver and scholarship approval workflow.</p>
                        <button type="button" class="btn btn-sm btn-outline-warning" onclick="createFromTemplate('fee_approval')">
                            Use Template
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Workflow Modal -->
<div class="modal fade" id="createWorkflowModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle mr-2"></i>Create New Workflow
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="createWorkflowForm" method="POST" action="/workflow/admin/create">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Workflow Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required
                               placeholder="e.g., Student Admission Process">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" required
                               placeholder="e.g., ADMISSION" pattern="[A-Z0-9_]+"
                               title="Uppercase letters, numbers and underscores only">
                        <small class="form-text text-muted">Unique identifier (uppercase, no spaces)</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Entity Type <span class="text-danger">*</span></label>
                        <select name="entity_type" class="form-control" required>
                            <option value="">Select entity type...</option>
                            <option value="applicant">Applicant</option>
                            <option value="student">Student</option>
                            <option value="staff">Staff</option>
                            <option value="invoice">Invoice</option>
                            <option value="leave_request">Leave Request</option>
                        </select>
                        <small class="form-text text-muted">The type of record this workflow processes</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"
                                  placeholder="Describe what this workflow does..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus mr-1"></i> Create Workflow
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Auto-generate code from name
document.querySelector('input[name="name"]').addEventListener('input', function() {
    const codeInput = document.querySelector('input[name="code"]');
    if (!codeInput.dataset.manual) {
        codeInput.value = this.value
            .toUpperCase()
            .replace(/[^A-Z0-9\s]/g, '')
            .replace(/\s+/g, '_')
            .substring(0, 30);
    }
});

document.querySelector('input[name="code"]').addEventListener('input', function() {
    this.dataset.manual = 'true';
});

// Create workflow form submission
document.getElementById('createWorkflowForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('/workflow/admin/create', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = '/workflow/admin/' + data.workflow_id;
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to create workflow. Please try again.');
    });
});

function cloneWorkflow(id, name) {
    const newName = prompt('Enter name for the cloned workflow:', name + ' (Copy)');
    if (!newName) return;

    fetch('/workflow/admin/clone', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'workflow_id=' + id + '&new_name=' + encodeURIComponent(newName)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

function deleteWorkflow(id, name) {
    if (!confirm('Are you sure you want to delete "' + name + '"?\n\nThis action cannot be undone.')) {
        return;
    }

    fetch('/workflow/admin/delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'workflow_id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

function createFromTemplate(templateCode) {
    fetch('/workflow/admin/create-from-template', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'template=' + templateCode
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = '/workflow/admin/' + data.workflow_id;
        } else {
            alert('Error: ' + data.message);
        }
    });
}
</script>
