<?php
/**
 * Suppliers Tab Content
 */
$suppliers = $tabData['suppliers'] ?? [];
$filters = $tabData['filters'] ?? [];
?>

<!-- Filters & Actions -->
<div class="row mb-3">
    <div class="col-md-8">
        <form method="GET" action="/finance/expenses/suppliers" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search suppliers..."
                    value="<?= e($filters['search'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($filters['category'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="ti ti-search me-1"></i>Filter
                </button>
            </div>
        </form>
    </div>
    <div class="col-md-4 text-end">
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#supplierModal" onclick="openSupplierModal()">
            <i class="ti ti-plus me-1"></i>Add Supplier
        </button>
    </div>
</div>

<!-- Suppliers Table -->
<?php if (empty($suppliers)): ?>
    <div class="empty py-5">
        <div class="empty-img">
            <i class="ti ti-building-store" style="font-size: 4rem; color: #adb5bd;"></i>
        </div>
        <p class="empty-title">No suppliers found</p>
        <p class="empty-subtitle text-muted">
            Add your first supplier to start managing purchases.
        </p>
        <div class="empty-action">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#supplierModal" onclick="openSupplierModal()">
                <i class="ti ti-plus me-1"></i>Add Supplier
            </button>
        </div>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-hover">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Contact</th>
                    <th>Payment Terms</th>
                    <th class="text-end">Balance</th>
                    <th>Status</th>
                    <th class="w-1">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($suppliers as $supplier): ?>
                    <tr>
                        <td><strong><?= e($supplier['supplier_code']) ?></strong></td>
                        <td>
                            <div><?= e($supplier['name']) ?></div>
                            <?php if ($supplier['contact_person']): ?>
                                <small class="text-muted"><?= e($supplier['contact_person']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= e($supplier['category_name'] ?? '-') ?></td>
                        <td>
                            <?php if ($supplier['phone']): ?>
                                <div><i class="ti ti-phone me-1"></i><?= e($supplier['phone']) ?></div>
                            <?php endif; ?>
                            <?php if ($supplier['email']): ?>
                                <small class="text-muted"><?= e($supplier['email']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= $supplier['payment_terms'] ?> days</td>
                        <td class="text-end">
                            <?php $balance = (float)$supplier['current_balance']; ?>
                            <span class="<?= $balance > 0 ? 'text-danger fw-bold' : '' ?>">
                                KES <?= number_format($balance, 2) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($supplier['is_active']): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    Actions
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a href="javascript:void(0)" class="dropdown-item" onclick="viewSupplier(<?= $supplier['id'] ?>)">
                                        <i class="ti ti-eye me-2"></i>View Details
                                    </a>
                                    <a href="javascript:void(0)" class="dropdown-item" onclick="editSupplier(<?= $supplier['id'] ?>)">
                                        <i class="ti ti-edit me-2"></i>Edit
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a href="javascript:void(0)" class="dropdown-item text-danger" onclick="deleteSupplier(<?= $supplier['id'] ?>, '<?= e($supplier['name']) ?>')">
                                        <i class="ti ti-trash me-2"></i>Delete
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- Supplier Modal -->
<div class="modal modal-blur fade" id="supplierModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="supplierForm" method="POST">
                <input type="hidden" name="supplier_id" id="supplier_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="supplierModalTitle">Add Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label required">Supplier Name</label>
                            <input type="text" name="name" id="supplier_name" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Category</label>
                            <select name="category_id" id="supplier_category" class="form-select">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact Person</label>
                            <input type="text" name="contact_person" id="supplier_contact" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" id="supplier_phone" class="form-control">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="supplier_email" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tax PIN</label>
                            <input type="text" name="tax_pin" id="supplier_tax_pin" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" id="supplier_address" class="form-control" rows="2"></textarea>
                    </div>

                    <hr class="my-3">
                    <h4 class="mb-3">Payment Details</h4>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Payment Terms (Days)</label>
                            <input type="number" name="payment_terms" id="supplier_terms" class="form-control" value="30" min="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Credit Limit (KES)</label>
                            <input type="number" name="credit_limit" id="supplier_credit_limit" class="form-control" value="0" min="0" step="0.01">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Status</label>
                            <label class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="supplier_active" checked>
                                <span class="form-check-label">Active</span>
                            </label>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Bank Name</label>
                            <input type="text" name="bank_name" id="supplier_bank" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Bank Branch</label>
                            <input type="text" name="bank_branch" id="supplier_branch" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Account Number</label>
                            <input type="text" name="bank_account" id="supplier_account" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" id="supplier_notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="supplierSubmitBtn">
                        <i class="ti ti-device-floppy me-1"></i>Save Supplier
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let supplierModal;

document.addEventListener('DOMContentLoaded', function() {
    supplierModal = new bootstrap.Modal(document.getElementById('supplierModal'));

    document.getElementById('supplierForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveSupplier();
    });
});

function openSupplierModal(id = null) {
    document.getElementById('supplierForm').reset();
    document.getElementById('supplier_id').value = '';
    document.getElementById('supplierModalTitle').textContent = 'Add Supplier';
    document.getElementById('supplier_active').checked = true;
    document.getElementById('supplier_terms').value = '30';
    document.getElementById('supplier_credit_limit').value = '0';
}

function editSupplier(id) {
    fetch(`/finance/expenses/api/suppliers/${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const s = data.data;
                document.getElementById('supplier_id').value = s.id;
                document.getElementById('supplier_name').value = s.name || '';
                document.getElementById('supplier_category').value = s.category_id || '';
                document.getElementById('supplier_contact').value = s.contact_person || '';
                document.getElementById('supplier_phone').value = s.phone || '';
                document.getElementById('supplier_email').value = s.email || '';
                document.getElementById('supplier_tax_pin').value = s.tax_pin || '';
                document.getElementById('supplier_address').value = s.address || '';
                document.getElementById('supplier_terms').value = s.payment_terms || 30;
                document.getElementById('supplier_credit_limit').value = s.credit_limit || 0;
                document.getElementById('supplier_active').checked = s.is_active == 1;
                document.getElementById('supplier_bank').value = s.bank_name || '';
                document.getElementById('supplier_branch').value = s.bank_branch || '';
                document.getElementById('supplier_account').value = s.bank_account || '';
                document.getElementById('supplier_notes').value = s.notes || '';

                document.getElementById('supplierModalTitle').textContent = 'Edit Supplier';
                supplierModal.show();
            } else {
                alert('Error loading supplier: ' + data.error);
            }
        })
        .catch(error => {
            alert('Error loading supplier');
            console.error(error);
        });
}

function saveSupplier() {
    const form = document.getElementById('supplierForm');
    const formData = new FormData(form);
    const supplierId = document.getElementById('supplier_id').value;

    const url = supplierId
        ? `/finance/expenses/api/suppliers/${supplierId}`
        : '/finance/expenses/api/suppliers';

    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            supplierModal.hide();
            window.location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        alert('Error saving supplier');
        console.error(error);
    });
}

function viewSupplier(id) {
    // TODO: Show supplier details modal or navigate to detail page
    editSupplier(id);
}

function deleteSupplier(id, name) {
    if (!confirm(`Are you sure you want to delete supplier "${name}"?`)) {
        return;
    }

    fetch(`/finance/expenses/api/suppliers/${id}/delete`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        alert('Error deleting supplier');
        console.error(error);
    });
}
</script>
