<?php
/**
 * Transport Tariffs - Content
 * With edit functionality and audit trail
 */
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <a href="/finance" class="btn btn-ghost-secondary btn-sm mb-2">
                    <i class="ti ti-arrow-left me-1"></i>Back to Finance
                </a>
                <h2 class="page-title">
                    <i class="ti ti-bus me-2"></i>
                    Transport Tariffs
                </h2>
                <div class="text-muted mt-1">
                    Zone-based transport pricing (one-way / two-way)
                </div>
            </div>
            <div class="col-auto ms-auto">
                <?php if (hasPermission('Finance.write') || Gate::hasRole('ADMIN')): ?>
                <button class="btn btn-secondary me-2" data-bs-toggle="modal" data-bs-target="#addZoneModal">
                    <i class="ti ti-map-pin me-2"></i>Add Zone
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTariffModal">
                    <i class="ti ti-plus me-2"></i>Add Tariff
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="row">
            <!-- Zones -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Transport Zones</h3>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($zones)): ?>
                        <div class="empty py-4">
                            <p class="empty-title">No zones defined</p>
                            <p class="empty-subtitle text-muted">Add distance-based zones first</p>
                        </div>
                        <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($zones as $zone): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <strong><?= e($zone['zone_code']) ?></strong>
                                        <span class="text-muted">-</span>
                                        <?= e($zone['zone_name']) ?>
                                        <div class="text-muted small">
                                            <?= $zone['min_distance_km'] ?>km - <?= $zone['max_distance_km'] ?>km
                                        </div>
                                        <?php if (!empty($zone['description'])): ?>
                                        <div class="text-muted small"><?= e($zone['description']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (hasPermission('Finance.write') || Gate::hasRole('ADMIN')): ?>
                                    <button class="btn btn-icon btn-ghost-secondary btn-sm"
                                            onclick="editZone(<?= htmlspecialchars(json_encode($zone)) ?>)"
                                            title="Edit Zone">
                                        <i class="ti ti-edit"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Tariffs -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Tariff Rates</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($tariffs)): ?>
                        <div class="empty">
                            <div class="empty-img">
                                <i class="ti ti-bus" style="font-size: 4rem; color: #adb5bd;"></i>
                            </div>
                            <p class="empty-title">No tariffs defined</p>
                            <p class="empty-subtitle text-muted">
                                Add zones first, then set tariff rates per zone.
                            </p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead>
                                    <tr>
                                        <th>Zone</th>
                                        <th>Year</th>
                                        <th>Term</th>
                                        <th>Direction</th>
                                        <th class="text-end">Amount</th>
                                        <th style="width: 200px;">Last Modified</th>
                                        <th style="width: 60px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tariffs as $tariff): ?>
                                    <tr>
                                        <td>
                                            <strong><?= e($tariff['zone_code'] ?? '-') ?></strong>
                                            <span class="text-muted">-</span>
                                            <?= e($tariff['zone_name'] ?? 'Unknown Zone') ?>
                                        </td>
                                        <td><?= e($tariff['academic_year'] ?? '-') ?></td>
                                        <td><?= e($tariff['term_name'] ?? 'All Terms') ?></td>
                                        <td>
                                            <span class="badge bg-<?= $tariff['direction'] == 'two_way' ? 'success' : 'info' ?>-lt">
                                                <?= $tariff['direction'] == 'two_way' ? 'Two Way' : 'One Way' ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <strong>KES <?= number_format($tariff['amount'], 2) ?></strong>
                                        </td>
                                        <td>
                                            <div class="text-muted small">
                                                <?php if (!empty($tariff['updated_at'])): ?>
                                                    <i class="ti ti-clock me-1"></i><?= date('M j, Y', strtotime($tariff['updated_at'])) ?>
                                                <?php elseif (!empty($tariff['created_at'])): ?>
                                                    <i class="ti ti-clock me-1"></i><?= date('M j, Y', strtotime($tariff['created_at'])) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (hasPermission('Finance.write') || Gate::hasRole('ADMIN')): ?>
                                            <button class="btn btn-icon btn-ghost-secondary btn-sm"
                                                    onclick="editTariff(<?= htmlspecialchars(json_encode($tariff)) ?>)"
                                                    title="Edit Tariff">
                                                <i class="ti ti-edit"></i>
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Zone Modal -->
<div class="modal fade" id="addZoneModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/finance/transport-zones/store">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="ti ti-map-pin me-2"></i>Add Transport Zone</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label required">Zone Code</label>
                            <input type="text" name="zone_code" class="form-control" required placeholder="e.g. A">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label required">Zone Name</label>
                            <input type="text" name="zone_name" class="form-control" required placeholder="e.g. Zone A (0-5km)">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Min Distance (km)</label>
                            <input type="number" name="min_distance_km" class="form-control" step="0.1" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Max Distance (km)</label>
                            <input type="number" name="max_distance_km" class="form-control" step="0.1" min="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Zone</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Zone Modal -->
<div class="modal fade" id="editZoneModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/finance/transport-zones/update">
                <input type="hidden" name="id" id="editZoneId">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="ti ti-edit me-2"></i>Edit Transport Zone</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label required">Zone Code</label>
                            <input type="text" name="zone_code" id="editZoneCode" class="form-control" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label required">Zone Name</label>
                            <input type="text" name="zone_name" id="editZoneName" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Min Distance (km)</label>
                            <input type="number" name="min_distance_km" id="editZoneMinDistance" class="form-control" step="0.1" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Max Distance (km)</label>
                            <input type="number" name="max_distance_km" id="editZoneMaxDistance" class="form-control" step="0.1" min="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Zone</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Tariff Modal -->
<div class="modal fade" id="addTariffModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/finance/transport-tariffs/store">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="ti ti-plus me-2"></i>Add Tariff</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required">Academic Year</label>
                            <select name="academic_year_id" class="form-select" required>
                                <option value="">Select year...</option>
                                <?php foreach ($academicYears as $year): ?>
                                <option value="<?= $year['id'] ?>" <?= $year['is_current'] ? 'selected' : '' ?>><?= e($year['year_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Zone</label>
                            <select name="transport_zone_id" class="form-select" required>
                                <option value="">Select zone...</option>
                                <?php foreach ($zones as $zone): ?>
                                <option value="<?= $zone['id'] ?>"><?= e($zone['zone_code']) ?> - <?= e($zone['zone_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Direction</label>
                            <select name="direction" class="form-select" required>
                                <option value="two_way">Two Way</option>
                                <option value="one_way">One Way</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Amount (KES)</label>
                            <input type="number" name="amount" class="form-control" required step="0.01" min="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Tariff</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Tariff Modal -->
<div class="modal fade" id="editTariffModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/finance/transport-tariffs/update">
                <input type="hidden" name="id" id="editTariffId">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="ti ti-edit me-2"></i>Edit Tariff</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required">Academic Year</label>
                            <select name="academic_year_id" id="editTariffYear" class="form-select" required>
                                <option value="">Select year...</option>
                                <?php foreach ($academicYears as $year): ?>
                                <option value="<?= $year['id'] ?>"><?= e($year['year_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Zone</label>
                            <select name="transport_zone_id" id="editTariffZone" class="form-select" required>
                                <option value="">Select zone...</option>
                                <?php foreach ($zones as $zone): ?>
                                <option value="<?= $zone['id'] ?>"><?= e($zone['zone_code']) ?> - <?= e($zone['zone_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Direction</label>
                            <select name="direction" id="editTariffDirection" class="form-select" required>
                                <option value="two_way">Two Way</option>
                                <option value="one_way">One Way</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Amount (KES)</label>
                            <input type="number" name="amount" id="editTariffAmount" class="form-control" required step="0.01" min="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Tariff</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Expose functions globally for onclick handlers (needed for AJAX navigation)
window.editZone = function(zone) {
    document.getElementById('editZoneId').value = zone.id;
    document.getElementById('editZoneCode').value = zone.zone_code;
    document.getElementById('editZoneName').value = zone.zone_name;
    document.getElementById('editZoneMinDistance').value = zone.min_distance_km || '';
    document.getElementById('editZoneMaxDistance').value = zone.max_distance_km || '';

    const modal = new bootstrap.Modal(document.getElementById('editZoneModal'));
    modal.show();
};

window.editTariff = function(tariff) {
    document.getElementById('editTariffId').value = tariff.id;
    document.getElementById('editTariffYear').value = tariff.academic_year_id;
    document.getElementById('editTariffZone').value = tariff.transport_zone_id;
    document.getElementById('editTariffDirection').value = tariff.direction;
    document.getElementById('editTariffAmount').value = tariff.amount;

    const modal = new bootstrap.Modal(document.getElementById('editTariffModal'));
    modal.show();
};
</script>
