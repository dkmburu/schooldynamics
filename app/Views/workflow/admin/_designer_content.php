<?php
/**
 * Workflow Designer Content
 * Visual workflow builder with drag-and-drop steps and connections
 */

$workflowData = [
    'id' => $workflow->id,
    'name' => $workflow->name,
    'code' => $workflow->code,
    'entity_type' => $workflow->entity_type,
    'description' => $workflow->description,
    'is_active' => $workflow->is_active,
    'steps' => $steps ?? [],
    'transitions' => $transitions ?? []
];
?>

<style>
/* Workflow Designer Styles */
.workflow-designer {
    display: flex;
    flex-direction: column;
    height: calc(100vh - 180px);
    min-height: 600px;
}

.designer-toolbar {
    background: white;
    border-bottom: 1px solid var(--sd-gray-200);
    padding: 12px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.designer-toolbar .btn-group {
    margin-right: 8px;
}

.designer-main {
    display: flex;
    flex: 1;
    overflow: hidden;
}

.step-palette {
    width: 220px;
    background: var(--sd-gray-50);
    border-right: 1px solid var(--sd-gray-200);
    padding: 16px;
    overflow-y: auto;
}

.palette-section {
    margin-bottom: 20px;
}

.palette-section h6 {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    color: var(--sd-gray-500);
    margin-bottom: 10px;
    letter-spacing: 0.5px;
}

.palette-item {
    background: white;
    border: 1px solid var(--sd-gray-200);
    border-radius: 6px;
    padding: 10px 12px;
    margin-bottom: 8px;
    cursor: grab;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: all 0.15s ease;
}

.palette-item:hover {
    border-color: var(--sd-primary);
    box-shadow: 0 2px 8px rgba(0, 120, 212, 0.15);
}

.palette-item:active {
    cursor: grabbing;
}

.palette-item i {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    font-size: 12px;
}

.palette-item .step-name {
    font-size: 13px;
    font-weight: 500;
    color: var(--sd-gray-700);
}

.palette-item[data-type="start"] i { background: #e8f5e9; color: #2e7d32; }
.palette-item[data-type="end"] i { background: #ffebee; color: #c62828; }
.palette-item[data-type="task"] i { background: #e3f2fd; color: #1565c0; }
.palette-item[data-type="decision"] i { background: #fff3e0; color: #e65100; }
.palette-item[data-type="parallel"] i { background: #f3e5f5; color: #7b1fa2; }
.palette-item[data-type="auto"] i { background: #e0f2f1; color: #00695c; }
.palette-item[data-type="sub_workflow"] i { background: #fce4ec; color: #c2185b; }

.canvas-container {
    flex: 1;
    overflow: auto;
    background: #f8f9fa;
    background-image:
        linear-gradient(rgba(0,0,0,0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0,0,0,0.03) 1px, transparent 1px);
    background-size: 20px 20px;
    position: relative;
}

.workflow-canvas {
    position: relative;
    width: 3000px;
    height: 2000px;
    min-width: 100%;
    min-height: 100%;
}

/* Step Nodes */
.workflow-step {
    position: absolute;
    background: white;
    border: 2px solid var(--sd-gray-300);
    border-radius: 8px;
    min-width: 160px;
    max-width: 200px;
    cursor: move;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: box-shadow 0.15s ease, border-color 0.15s ease;
    z-index: 10;
}

.workflow-step:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}

.workflow-step.selected {
    border-color: var(--sd-primary);
    box-shadow: 0 0 0 3px rgba(0, 120, 212, 0.2);
}

.workflow-step.dragging {
    opacity: 0.8;
    z-index: 100;
}

.step-header {
    padding: 10px 12px;
    display: flex;
    align-items: center;
    gap: 8px;
    border-bottom: 1px solid var(--sd-gray-100);
}

.step-icon {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    flex-shrink: 0;
}

.step-title {
    flex: 1;
    font-size: 13px;
    font-weight: 600;
    color: var(--sd-gray-800);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.step-body {
    padding: 8px 12px;
    font-size: 11px;
    color: var(--sd-gray-500);
}

.step-code {
    font-family: monospace;
    background: var(--sd-gray-100);
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 10px;
}

.step-role {
    margin-top: 4px;
}

.step-role i {
    margin-right: 4px;
}

/* Step type colors */
.workflow-step[data-type="start"] { border-color: #4caf50; }
.workflow-step[data-type="start"] .step-icon { background: #e8f5e9; color: #2e7d32; }

.workflow-step[data-type="end"] { border-color: #f44336; }
.workflow-step[data-type="end"] .step-icon { background: #ffebee; color: #c62828; }

.workflow-step[data-type="task"] { border-color: #2196f3; }
.workflow-step[data-type="task"] .step-icon { background: #e3f2fd; color: #1565c0; }

.workflow-step[data-type="decision"] { border-color: #ff9800; }
.workflow-step[data-type="decision"] .step-icon { background: #fff3e0; color: #e65100; }

.workflow-step[data-type="parallel"] { border-color: #9c27b0; }
.workflow-step[data-type="parallel"] .step-icon { background: #f3e5f5; color: #7b1fa2; }

.workflow-step[data-type="auto"] { border-color: #009688; }
.workflow-step[data-type="auto"] .step-icon { background: #e0f2f1; color: #00695c; }

.workflow-step[data-type="sub_workflow"] { border-color: #e91e63; }
.workflow-step[data-type="sub_workflow"] .step-icon { background: #fce4ec; color: #c2185b; }

/* Connection points */
.connection-point {
    position: absolute;
    width: 12px;
    height: 12px;
    background: white;
    border: 2px solid var(--sd-gray-400);
    border-radius: 50%;
    cursor: crosshair;
    z-index: 20;
    transition: all 0.15s ease;
}

.connection-point:hover {
    background: var(--sd-primary);
    border-color: var(--sd-primary);
    transform: scale(1.3);
}

.connection-point.output {
    right: -6px;
    top: 50%;
    transform: translateY(-50%);
}

.connection-point.input {
    left: -6px;
    top: 50%;
    transform: translateY(-50%);
}

.connection-point.output:hover,
.connection-point.input:hover {
    transform: translateY(-50%) scale(1.3);
}

/* SVG Connections */
.connections-svg {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 5;
}

.connection-line {
    fill: none;
    stroke: var(--sd-gray-400);
    stroke-width: 2;
    pointer-events: stroke;
    cursor: pointer;
}

.connection-line:hover {
    stroke: var(--sd-primary);
    stroke-width: 3;
}

.connection-line.selected {
    stroke: var(--sd-primary);
    stroke-width: 3;
}

.connection-arrow {
    fill: var(--sd-gray-400);
}

.connection-label {
    font-size: 11px;
    fill: var(--sd-gray-600);
    text-anchor: middle;
}

/* Properties Panel */
.properties-panel {
    width: 320px;
    background: white;
    border-left: 1px solid var(--sd-gray-200);
    overflow-y: auto;
    display: none;
}

.properties-panel.active {
    display: block;
}

.properties-header {
    padding: 16px;
    border-bottom: 1px solid var(--sd-gray-200);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.properties-header h5 {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
}

.properties-body {
    padding: 16px;
}

.properties-section {
    margin-bottom: 20px;
}

.properties-section h6 {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    color: var(--sd-gray-500);
    margin-bottom: 12px;
    letter-spacing: 0.5px;
}

.prop-group {
    margin-bottom: 12px;
}

.prop-group label {
    display: block;
    font-size: 12px;
    font-weight: 500;
    color: var(--sd-gray-600);
    margin-bottom: 4px;
}

.prop-group input,
.prop-group select,
.prop-group textarea {
    width: 100%;
    padding: 8px 10px;
    border: 1px solid var(--sd-gray-300);
    border-radius: 4px;
    font-size: 13px;
}

.prop-group input:focus,
.prop-group select:focus,
.prop-group textarea:focus {
    border-color: var(--sd-primary);
    outline: none;
    box-shadow: 0 0 0 2px rgba(0, 120, 212, 0.1);
}

/* Validation warnings */
.validation-warnings {
    background: #fff8e1;
    border: 1px solid #ffcc02;
    border-radius: 6px;
    padding: 12px;
    margin-bottom: 16px;
}

.validation-warnings h6 {
    color: #f57c00;
    font-size: 12px;
    margin-bottom: 8px;
}

.validation-warnings ul {
    margin: 0;
    padding-left: 16px;
    font-size: 12px;
    color: #e65100;
}

/* Zoom controls */
.zoom-controls {
    position: absolute;
    bottom: 20px;
    right: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.15);
    display: flex;
    overflow: hidden;
    z-index: 100;
}

.zoom-controls button {
    border: none;
    background: white;
    padding: 10px 14px;
    cursor: pointer;
    font-size: 14px;
    color: var(--sd-gray-600);
    transition: background 0.15s ease;
}

.zoom-controls button:hover {
    background: var(--sd-gray-100);
}

.zoom-controls .zoom-level {
    padding: 10px 12px;
    font-size: 12px;
    font-weight: 500;
    border-left: 1px solid var(--sd-gray-200);
    border-right: 1px solid var(--sd-gray-200);
    min-width: 50px;
    text-align: center;
}
</style>

<!-- Page Header -->
<div class="row mb-3">
    <div class="col">
        <h2 class="page-title">
            <i class="fas fa-project-diagram text-primary mr-2"></i>
            <?= e($workflow->name) ?>
            <?php if ($workflow->is_active): ?>
            <span class="badge badge-success ml-2">Active</span>
            <?php else: ?>
            <span class="badge badge-secondary ml-2">Draft</span>
            <?php endif; ?>
        </h2>
        <p class="text-muted mb-0">
            <code><?= e($workflow->code) ?></code> &bull;
            <?= ucfirst(str_replace('_', ' ', $workflow->entity_type)) ?> workflow
        </p>
    </div>
    <div class="col-auto">
        <a href="/workflow/admin" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Back to List
        </a>
    </div>
</div>

<!-- Validation Warnings -->
<?php if (!empty($validationErrors)): ?>
<div class="validation-warnings">
    <h6><i class="fas fa-exclamation-triangle mr-1"></i> Validation Issues</h6>
    <ul>
        <?php foreach ($validationErrors as $error): ?>
        <li><?= e($error) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<!-- Workflow Designer -->
<div class="card">
    <div class="workflow-designer">
        <!-- Toolbar -->
        <div class="designer-toolbar">
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-primary" id="saveWorkflow">
                    <i class="fas fa-save mr-1"></i> Save
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle dropdown-toggle-split" data-toggle="dropdown">
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="#" onclick="saveAndActivate()">
                        <i class="fas fa-check-circle mr-2"></i> Save & Activate
                    </a>
                    <a class="dropdown-item" href="#" onclick="saveAsDraft()">
                        <i class="fas fa-file mr-2"></i> Save as Draft
                    </a>
                </div>
            </div>

            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="undoBtn" disabled>
                    <i class="fas fa-undo"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="redoBtn" disabled>
                    <i class="fas fa-redo"></i>
                </button>
            </div>

            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deleteSelected()" id="deleteBtn" disabled>
                    <i class="fas fa-trash"></i>
                </button>
            </div>

            <div class="ml-auto d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="autoLayout()">
                    <i class="fas fa-magic mr-1"></i> Auto Layout
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-toggle="modal" data-target="#workflowSettingsModal">
                    <i class="fas fa-cog mr-1"></i> Settings
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleProperties()">
                    <i class="fas fa-sliders-h mr-1"></i> Properties
                </button>
            </div>
        </div>

        <!-- Main Designer Area -->
        <div class="designer-main">
            <!-- Step Palette -->
            <div class="step-palette">
                <div class="palette-section">
                    <h6>Flow Control</h6>
                    <div class="palette-item" data-type="start" draggable="true">
                        <i class="fas fa-play-circle"></i>
                        <span class="step-name">Start</span>
                    </div>
                    <div class="palette-item" data-type="end" draggable="true">
                        <i class="fas fa-stop-circle"></i>
                        <span class="step-name">End</span>
                    </div>
                </div>

                <div class="palette-section">
                    <h6>Step Types</h6>
                    <div class="palette-item" data-type="task" draggable="true">
                        <i class="fas fa-tasks"></i>
                        <span class="step-name">Task</span>
                    </div>
                    <div class="palette-item" data-type="decision" draggable="true">
                        <i class="fas fa-code-branch"></i>
                        <span class="step-name">Decision</span>
                    </div>
                    <div class="palette-item" data-type="parallel" draggable="true">
                        <i class="fas fa-columns"></i>
                        <span class="step-name">Parallel</span>
                    </div>
                    <div class="palette-item" data-type="auto" draggable="true">
                        <i class="fas fa-bolt"></i>
                        <span class="step-name">Auto Action</span>
                    </div>
                    <div class="palette-item" data-type="sub_workflow" draggable="true">
                        <i class="fas fa-sitemap"></i>
                        <span class="step-name">Sub-Workflow</span>
                    </div>
                </div>

                <div class="palette-section">
                    <h6>Info</h6>
                    <div class="small text-muted">
                        <p>Drag steps onto the canvas to build your workflow.</p>
                        <p>Click and drag from connection points to create transitions.</p>
                    </div>
                </div>
            </div>

            <!-- Canvas -->
            <div class="canvas-container" id="canvasContainer">
                <div class="workflow-canvas" id="workflowCanvas">
                    <svg class="connections-svg" id="connectionsSvg">
                        <defs>
                            <marker id="arrowhead" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
                                <polygon points="0 0, 10 3.5, 0 7" fill="var(--sd-gray-400)" />
                            </marker>
                        </defs>
                    </svg>
                    <!-- Steps will be rendered here -->
                </div>

                <!-- Zoom Controls -->
                <div class="zoom-controls">
                    <button type="button" onclick="zoomOut()"><i class="fas fa-minus"></i></button>
                    <span class="zoom-level" id="zoomLevel">100%</span>
                    <button type="button" onclick="zoomIn()"><i class="fas fa-plus"></i></button>
                    <button type="button" onclick="resetZoom()"><i class="fas fa-expand"></i></button>
                </div>
            </div>

            <!-- Properties Panel -->
            <div class="properties-panel" id="propertiesPanel">
                <div class="properties-header">
                    <h5 id="propertiesTitle">Step Properties</h5>
                    <button type="button" class="btn btn-sm btn-link" onclick="toggleProperties()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="properties-body" id="propertiesBody">
                    <p class="text-muted text-center py-4">Select a step to view properties</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Workflow Settings Modal -->
<div class="modal fade" id="workflowSettingsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-cog mr-2"></i>Workflow Settings</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Workflow Name</label>
                    <input type="text" class="form-control" id="settingsName" value="<?= e($workflow->name) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Code</label>
                    <input type="text" class="form-control" id="settingsCode" value="<?= e($workflow->code) ?>" readonly>
                    <small class="form-text text-muted">Code cannot be changed after creation</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Entity Type</label>
                    <select class="form-control" id="settingsEntityType">
                        <option value="applicant" <?= $workflow->entity_type == 'applicant' ? 'selected' : '' ?>>Applicant</option>
                        <option value="student" <?= $workflow->entity_type == 'student' ? 'selected' : '' ?>>Student</option>
                        <option value="staff" <?= $workflow->entity_type == 'staff' ? 'selected' : '' ?>>Staff</option>
                        <option value="invoice" <?= $workflow->entity_type == 'invoice' ? 'selected' : '' ?>>Invoice</option>
                        <option value="leave_request" <?= $workflow->entity_type == 'leave_request' ? 'selected' : '' ?>>Leave Request</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" id="settingsDescription" rows="3"><?= e($workflow->description) ?></textarea>
                </div>
                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="settingsActive" <?= $workflow->is_active ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="settingsActive">Workflow is Active</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveWorkflowSettings()">Save Settings</button>
            </div>
        </div>
    </div>
</div>

<!-- Step Editor Modal -->
<?php include __DIR__ . '/_step_modal.php'; ?>

<script>
// Workflow Designer JavaScript
const WorkflowDesigner = {
    workflowId: <?= $workflow->id ?>,
    data: <?= json_encode($workflowData) ?>,
    steps: {},
    transitions: [],
    selectedStep: null,
    selectedTransition: null,
    isDragging: false,
    isConnecting: false,
    connectionStart: null,
    zoom: 1,
    history: [],
    historyIndex: -1,

    init() {
        this.loadWorkflow();
        this.setupEventListeners();
        this.renderConnections();
        this.saveState();
    },

    loadWorkflow() {
        // Load steps from data
        (this.data.steps || []).forEach(step => {
            this.addStepToCanvas(step);
        });

        // Load transitions
        this.transitions = this.data.transitions || [];
    },

    setupEventListeners() {
        const canvas = document.getElementById('workflowCanvas');
        const container = document.getElementById('canvasContainer');

        // Drag from palette
        document.querySelectorAll('.palette-item').forEach(item => {
            item.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('stepType', item.dataset.type);
            });
        });

        // Drop on canvas
        canvas.addEventListener('dragover', (e) => e.preventDefault());
        canvas.addEventListener('drop', (e) => {
            e.preventDefault();
            const stepType = e.dataTransfer.getData('stepType');
            if (stepType) {
                const rect = canvas.getBoundingClientRect();
                const x = (e.clientX - rect.left + container.scrollLeft) / this.zoom;
                const y = (e.clientY - rect.top + container.scrollTop) / this.zoom;
                this.createStep(stepType, x, y);
            }
        });

        // Click on canvas to deselect
        canvas.addEventListener('click', (e) => {
            if (e.target === canvas || e.target.classList.contains('connections-svg')) {
                this.deselectAll();
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Delete' || e.key === 'Backspace') {
                if (!['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) {
                    this.deleteSelected();
                }
            }
            if (e.ctrlKey && e.key === 'z') {
                e.preventDefault();
                this.undo();
            }
            if (e.ctrlKey && e.key === 'y') {
                e.preventDefault();
                this.redo();
            }
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                this.save();
            }
        });

        // Save button
        document.getElementById('saveWorkflow').addEventListener('click', () => this.save());
    },

    createStep(type, x, y) {
        const id = 'step_' + Date.now();
        const step = {
            id: id,
            code: this.generateStepCode(type),
            name: this.getDefaultStepName(type),
            step_type: type,
            ui_position_x: Math.round(x),
            ui_position_y: Math.round(y),
            actor_roles: [],
            available_actions: type === 'task' ? ['approve', 'reject'] : [],
            sla_hours: null,
            is_new: true
        };

        this.addStepToCanvas(step);
        this.saveState();
        this.selectStep(id);
    },

    addStepToCanvas(step) {
        const canvas = document.getElementById('workflowCanvas');
        const stepEl = document.createElement('div');
        stepEl.className = 'workflow-step';
        stepEl.id = step.id || 'step_' + step.id;
        stepEl.dataset.stepId = step.id;
        stepEl.dataset.type = step.step_type;
        stepEl.style.left = (step.ui_position_x || 100) + 'px';
        stepEl.style.top = (step.ui_position_y || 100) + 'px';

        const icon = this.getStepIcon(step.step_type);
        const roleText = step.actor_roles && step.actor_roles.length > 0
            ? step.actor_roles.join(', ')
            : '';

        stepEl.innerHTML = `
            <div class="step-header">
                <div class="step-icon"><i class="${icon}"></i></div>
                <div class="step-title">${this.escapeHtml(step.name)}</div>
            </div>
            <div class="step-body">
                <div class="step-code">${this.escapeHtml(step.code)}</div>
                ${roleText ? `<div class="step-role"><i class="fas fa-user"></i>${this.escapeHtml(roleText)}</div>` : ''}
            </div>
            ${step.step_type !== 'start' ? '<div class="connection-point input"></div>' : ''}
            ${step.step_type !== 'end' ? '<div class="connection-point output"></div>' : ''}
        `;

        // Make draggable
        this.makeStepDraggable(stepEl);

        // Click to select
        stepEl.addEventListener('click', (e) => {
            e.stopPropagation();
            this.selectStep(step.id);
        });

        // Double-click to edit
        stepEl.addEventListener('dblclick', (e) => {
            e.stopPropagation();
            this.editStep(step.id);
        });

        // Connection points
        const outputPoint = stepEl.querySelector('.connection-point.output');
        const inputPoint = stepEl.querySelector('.connection-point.input');

        if (outputPoint) {
            outputPoint.addEventListener('mousedown', (e) => {
                e.stopPropagation();
                this.startConnection(step.id, 'output', e);
            });
        }

        if (inputPoint) {
            inputPoint.addEventListener('mouseup', (e) => {
                e.stopPropagation();
                if (this.isConnecting) {
                    this.endConnection(step.id);
                }
            });
        }

        canvas.appendChild(stepEl);
        this.steps[step.id] = step;
    },

    makeStepDraggable(stepEl) {
        let startX, startY, startLeft, startTop;

        stepEl.addEventListener('mousedown', (e) => {
            if (e.target.classList.contains('connection-point')) return;

            this.isDragging = true;
            stepEl.classList.add('dragging');

            startX = e.clientX;
            startY = e.clientY;
            startLeft = parseInt(stepEl.style.left);
            startTop = parseInt(stepEl.style.top);

            const onMouseMove = (e) => {
                if (!this.isDragging) return;

                const dx = (e.clientX - startX) / this.zoom;
                const dy = (e.clientY - startY) / this.zoom;

                stepEl.style.left = Math.max(0, startLeft + dx) + 'px';
                stepEl.style.top = Math.max(0, startTop + dy) + 'px';

                this.renderConnections();
            };

            const onMouseUp = () => {
                if (this.isDragging) {
                    this.isDragging = false;
                    stepEl.classList.remove('dragging');

                    // Update step position
                    const stepId = stepEl.dataset.stepId;
                    if (this.steps[stepId]) {
                        this.steps[stepId].ui_position_x = parseInt(stepEl.style.left);
                        this.steps[stepId].ui_position_y = parseInt(stepEl.style.top);
                        this.saveState();
                    }

                    document.removeEventListener('mousemove', onMouseMove);
                    document.removeEventListener('mouseup', onMouseUp);
                }
            };

            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup', onMouseUp);
        });
    },

    startConnection(stepId, type, e) {
        this.isConnecting = true;
        this.connectionStart = { stepId, type };
        document.body.style.cursor = 'crosshair';

        const onMouseUp = () => {
            this.isConnecting = false;
            this.connectionStart = null;
            document.body.style.cursor = '';
            document.removeEventListener('mouseup', onMouseUp);
        };

        document.addEventListener('mouseup', onMouseUp);
    },

    endConnection(toStepId) {
        if (!this.connectionStart) return;

        const fromStepId = this.connectionStart.stepId;

        if (fromStepId === toStepId) return;

        // Check if connection already exists
        const exists = this.transitions.some(t =>
            t.from_step_id == fromStepId && t.to_step_id == toStepId
        );

        if (!exists) {
            this.transitions.push({
                from_step_id: fromStepId,
                to_step_id: toStepId,
                action_code: 'default',
                is_new: true
            });
            this.renderConnections();
            this.saveState();
        }

        this.isConnecting = false;
        this.connectionStart = null;
        document.body.style.cursor = '';
    },

    renderConnections() {
        const svg = document.getElementById('connectionsSvg');

        // Clear existing connections (except defs)
        Array.from(svg.children).forEach(child => {
            if (child.tagName !== 'defs') {
                svg.removeChild(child);
            }
        });

        this.transitions.forEach((trans, index) => {
            const fromEl = document.querySelector(`[data-step-id="${trans.from_step_id}"]`);
            const toEl = document.querySelector(`[data-step-id="${trans.to_step_id}"]`);

            if (!fromEl || !toEl) return;

            const fromRect = fromEl.getBoundingClientRect();
            const toRect = toEl.getBoundingClientRect();
            const canvasRect = document.getElementById('workflowCanvas').getBoundingClientRect();

            const x1 = (fromRect.right - canvasRect.left) / this.zoom;
            const y1 = (fromRect.top + fromRect.height / 2 - canvasRect.top) / this.zoom;
            const x2 = (toRect.left - canvasRect.left) / this.zoom;
            const y2 = (toRect.top + toRect.height / 2 - canvasRect.top) / this.zoom;

            // Create curved path
            const midX = (x1 + x2) / 2;
            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('d', `M ${x1} ${y1} C ${midX} ${y1}, ${midX} ${y2}, ${x2} ${y2}`);
            path.setAttribute('class', 'connection-line');
            path.setAttribute('marker-end', 'url(#arrowhead)');
            path.dataset.index = index;

            path.addEventListener('click', (e) => {
                e.stopPropagation();
                this.selectTransition(index);
            });

            svg.appendChild(path);

            // Add label if action code exists
            if (trans.action_code && trans.action_code !== 'default') {
                const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                text.setAttribute('x', midX);
                text.setAttribute('y', (y1 + y2) / 2 - 5);
                text.setAttribute('class', 'connection-label');
                text.textContent = trans.action_code;
                svg.appendChild(text);
            }
        });
    },

    selectStep(stepId) {
        this.deselectAll();
        this.selectedStep = stepId;

        const stepEl = document.querySelector(`[data-step-id="${stepId}"]`);
        if (stepEl) {
            stepEl.classList.add('selected');
        }

        document.getElementById('deleteBtn').disabled = false;
        this.showStepProperties(stepId);
    },

    selectTransition(index) {
        this.deselectAll();
        this.selectedTransition = index;

        const path = document.querySelector(`path[data-index="${index}"]`);
        if (path) {
            path.classList.add('selected');
        }

        document.getElementById('deleteBtn').disabled = false;
        this.showTransitionProperties(index);
    },

    deselectAll() {
        this.selectedStep = null;
        this.selectedTransition = null;

        document.querySelectorAll('.workflow-step.selected').forEach(el => el.classList.remove('selected'));
        document.querySelectorAll('.connection-line.selected').forEach(el => el.classList.remove('selected'));

        document.getElementById('deleteBtn').disabled = true;
        document.getElementById('propertiesBody').innerHTML = '<p class="text-muted text-center py-4">Select a step to view properties</p>';
    },

    deleteSelected() {
        if (this.selectedStep) {
            // Don't allow deleting if there are active tickets
            const step = this.steps[this.selectedStep];
            if (step && !step.is_new) {
                if (!confirm('Delete this step? This cannot be undone.')) return;
            }

            // Remove transitions connected to this step
            this.transitions = this.transitions.filter(t =>
                t.from_step_id != this.selectedStep && t.to_step_id != this.selectedStep
            );

            // Remove step element
            const stepEl = document.querySelector(`[data-step-id="${this.selectedStep}"]`);
            if (stepEl) stepEl.remove();

            delete this.steps[this.selectedStep];
            this.deselectAll();
            this.renderConnections();
            this.saveState();
        }

        if (this.selectedTransition !== null) {
            this.transitions.splice(this.selectedTransition, 1);
            this.deselectAll();
            this.renderConnections();
            this.saveState();
        }
    },

    showStepProperties(stepId) {
        const step = this.steps[stepId];
        if (!step) return;

        document.getElementById('propertiesPanel').classList.add('active');
        document.getElementById('propertiesTitle').textContent = 'Step Properties';

        const html = `
            <div class="properties-section">
                <h6>Basic Info</h6>
                <div class="prop-group">
                    <label>Name</label>
                    <input type="text" id="propName" value="${this.escapeHtml(step.name)}" onchange="WorkflowDesigner.updateStepProperty('${stepId}', 'name', this.value)">
                </div>
                <div class="prop-group">
                    <label>Code</label>
                    <input type="text" id="propCode" value="${this.escapeHtml(step.code)}" ${step.is_new ? '' : 'readonly'} onchange="WorkflowDesigner.updateStepProperty('${stepId}', 'code', this.value)">
                </div>
                <div class="prop-group">
                    <label>Type</label>
                    <input type="text" value="${step.step_type}" readonly>
                </div>
            </div>
            ${step.step_type === 'task' ? `
            <div class="properties-section">
                <h6>Assignment</h6>
                <div class="prop-group">
                    <label>Assigned Role</label>
                    <input type="text" id="propRole" value="${(step.actor_roles || []).join(', ')}"
                           placeholder="e.g., ADMISSIONS_OFFICER"
                           onchange="WorkflowDesigner.updateStepProperty('${stepId}', 'actor_roles', this.value.split(',').map(s => s.trim()).filter(s => s))">
                </div>
            </div>
            <div class="properties-section">
                <h6>SLA & Escalation</h6>
                <div class="prop-group">
                    <label>SLA Hours</label>
                    <input type="number" id="propSla" value="${step.sla_hours || ''}" placeholder="e.g., 24"
                           onchange="WorkflowDesigner.updateStepProperty('${stepId}', 'sla_hours', this.value ? parseInt(this.value) : null)">
                </div>
            </div>
            ` : ''}
            <div class="properties-section">
                <button type="button" class="btn btn-sm btn-outline-primary btn-block" onclick="WorkflowDesigner.editStep('${stepId}')">
                    <i class="fas fa-edit mr-1"></i> Advanced Settings
                </button>
            </div>
        `;

        document.getElementById('propertiesBody').innerHTML = html;
    },

    showTransitionProperties(index) {
        const trans = this.transitions[index];
        if (!trans) return;

        document.getElementById('propertiesPanel').classList.add('active');
        document.getElementById('propertiesTitle').textContent = 'Transition Properties';

        const fromStep = this.steps[trans.from_step_id];
        const toStep = this.steps[trans.to_step_id];

        const html = `
            <div class="properties-section">
                <h6>Connection</h6>
                <div class="prop-group">
                    <label>From</label>
                    <input type="text" value="${fromStep ? fromStep.name : 'Unknown'}" readonly>
                </div>
                <div class="prop-group">
                    <label>To</label>
                    <input type="text" value="${toStep ? toStep.name : 'Unknown'}" readonly>
                </div>
            </div>
            <div class="properties-section">
                <h6>Condition</h6>
                <div class="prop-group">
                    <label>Action Code</label>
                    <select onchange="WorkflowDesigner.updateTransitionProperty(${index}, 'action_code', this.value)">
                        <option value="default" ${trans.action_code === 'default' ? 'selected' : ''}>Default</option>
                        <option value="approve" ${trans.action_code === 'approve' ? 'selected' : ''}>Approve</option>
                        <option value="reject" ${trans.action_code === 'reject' ? 'selected' : ''}>Reject</option>
                        <option value="request_info" ${trans.action_code === 'request_info' ? 'selected' : ''}>Request Info</option>
                        <option value="escalate" ${trans.action_code === 'escalate' ? 'selected' : ''}>Escalate</option>
                    </select>
                </div>
            </div>
            <div class="properties-section">
                <button type="button" class="btn btn-sm btn-outline-danger btn-block" onclick="WorkflowDesigner.deleteSelected()">
                    <i class="fas fa-trash mr-1"></i> Delete Transition
                </button>
            </div>
        `;

        document.getElementById('propertiesBody').innerHTML = html;
    },

    updateStepProperty(stepId, property, value) {
        if (this.steps[stepId]) {
            this.steps[stepId][property] = value;

            // Update display
            const stepEl = document.querySelector(`[data-step-id="${stepId}"]`);
            if (stepEl) {
                if (property === 'name') {
                    stepEl.querySelector('.step-title').textContent = value;
                }
                if (property === 'code') {
                    stepEl.querySelector('.step-code').textContent = value;
                }
            }

            this.saveState();
        }
    },

    updateTransitionProperty(index, property, value) {
        if (this.transitions[index]) {
            this.transitions[index][property] = value;
            this.renderConnections();
            this.saveState();
        }
    },

    editStep(stepId) {
        const step = this.steps[stepId];
        if (!step) return;

        // Populate modal
        document.getElementById('stepModalTitle').textContent = 'Edit Step: ' + step.name;
        document.getElementById('stepId').value = stepId;
        document.getElementById('stepName').value = step.name;
        document.getElementById('stepCode').value = step.code;
        document.getElementById('stepType').value = step.step_type;
        document.getElementById('stepDescription').value = step.description || '';
        document.getElementById('stepRoles').value = (step.actor_roles || []).join(', ');
        document.getElementById('stepSlaHours').value = step.sla_hours || '';

        // Show/hide fields based on step type
        const taskFields = document.querySelectorAll('.task-only-field');
        taskFields.forEach(el => {
            el.style.display = step.step_type === 'task' ? 'block' : 'none';
        });

        $('#stepEditorModal').modal('show');
    },

    saveStepFromModal() {
        const stepId = document.getElementById('stepId').value;
        if (!this.steps[stepId]) return;

        this.steps[stepId].name = document.getElementById('stepName').value;
        this.steps[stepId].description = document.getElementById('stepDescription').value;
        this.steps[stepId].actor_roles = document.getElementById('stepRoles').value.split(',').map(s => s.trim()).filter(s => s);
        this.steps[stepId].sla_hours = document.getElementById('stepSlaHours').value ? parseInt(document.getElementById('stepSlaHours').value) : null;

        // Update display
        const stepEl = document.querySelector(`[data-step-id="${stepId}"]`);
        if (stepEl) {
            stepEl.querySelector('.step-title').textContent = this.steps[stepId].name;
            const roleEl = stepEl.querySelector('.step-role');
            if (roleEl) {
                roleEl.innerHTML = '<i class="fas fa-user"></i>' + this.steps[stepId].actor_roles.join(', ');
            }
        }

        $('#stepEditorModal').modal('hide');
        this.showStepProperties(stepId);
        this.saveState();
    },

    save() {
        const data = {
            workflow_id: this.workflowId,
            name: document.getElementById('settingsName')?.value || this.data.name,
            description: document.getElementById('settingsDescription')?.value || this.data.description,
            entity_type: document.getElementById('settingsEntityType')?.value || this.data.entity_type,
            is_active: document.getElementById('settingsActive')?.checked ? 1 : 0,
            steps: Object.values(this.steps),
            transitions: this.transitions
        };

        fetch('/workflow/admin/save', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ workflow_id: this.workflowId, data: JSON.stringify(data) })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                // Show success message
                alert('Workflow saved successfully!');
                // Mark all steps as not new
                Object.values(this.steps).forEach(step => step.is_new = false);
                this.transitions.forEach(trans => trans.is_new = false);
            } else {
                alert('Error: ' + result.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to save workflow. Please try again.');
        });
    },

    saveState() {
        const state = {
            steps: JSON.parse(JSON.stringify(this.steps)),
            transitions: JSON.parse(JSON.stringify(this.transitions))
        };

        // Remove future states if we're not at the end
        if (this.historyIndex < this.history.length - 1) {
            this.history = this.history.slice(0, this.historyIndex + 1);
        }

        this.history.push(state);
        this.historyIndex = this.history.length - 1;

        // Limit history size
        if (this.history.length > 50) {
            this.history.shift();
            this.historyIndex--;
        }

        this.updateUndoRedoButtons();
    },

    undo() {
        if (this.historyIndex > 0) {
            this.historyIndex--;
            this.restoreState(this.history[this.historyIndex]);
        }
    },

    redo() {
        if (this.historyIndex < this.history.length - 1) {
            this.historyIndex++;
            this.restoreState(this.history[this.historyIndex]);
        }
    },

    restoreState(state) {
        // Clear canvas
        document.querySelectorAll('.workflow-step').forEach(el => el.remove());

        // Restore steps
        this.steps = {};
        Object.values(state.steps).forEach(step => {
            this.addStepToCanvas(step);
        });

        // Restore transitions
        this.transitions = state.transitions;
        this.renderConnections();

        this.deselectAll();
        this.updateUndoRedoButtons();
    },

    updateUndoRedoButtons() {
        document.getElementById('undoBtn').disabled = this.historyIndex <= 0;
        document.getElementById('redoBtn').disabled = this.historyIndex >= this.history.length - 1;
    },

    // Utility functions
    generateStepCode(type) {
        const prefix = type.toUpperCase();
        const count = Object.values(this.steps).filter(s => s.step_type === type).length + 1;
        return `${prefix}_${count}`;
    },

    getDefaultStepName(type) {
        const names = {
            start: 'Start',
            end: 'End',
            task: 'New Task',
            decision: 'Decision',
            parallel: 'Parallel Split',
            auto: 'Auto Action',
            sub_workflow: 'Sub-Workflow'
        };
        return names[type] || 'New Step';
    },

    getStepIcon(type) {
        const icons = {
            start: 'fas fa-play-circle',
            end: 'fas fa-stop-circle',
            task: 'fas fa-tasks',
            decision: 'fas fa-code-branch',
            parallel: 'fas fa-columns',
            auto: 'fas fa-bolt',
            sub_workflow: 'fas fa-sitemap'
        };
        return icons[type] || 'fas fa-circle';
    },

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }
};

// Zoom functions
function zoomIn() {
    WorkflowDesigner.zoom = Math.min(WorkflowDesigner.zoom + 0.1, 2);
    updateZoom();
}

function zoomOut() {
    WorkflowDesigner.zoom = Math.max(WorkflowDesigner.zoom - 0.1, 0.5);
    updateZoom();
}

function resetZoom() {
    WorkflowDesigner.zoom = 1;
    updateZoom();
}

function updateZoom() {
    const canvas = document.getElementById('workflowCanvas');
    canvas.style.transform = `scale(${WorkflowDesigner.zoom})`;
    canvas.style.transformOrigin = 'top left';
    document.getElementById('zoomLevel').textContent = Math.round(WorkflowDesigner.zoom * 100) + '%';
    WorkflowDesigner.renderConnections();
}

function toggleProperties() {
    const panel = document.getElementById('propertiesPanel');
    panel.classList.toggle('active');
}

function autoLayout() {
    // Simple auto-layout algorithm
    const steps = Object.values(WorkflowDesigner.steps);
    const startStep = steps.find(s => s.step_type === 'start');

    if (!startStep) return;

    // BFS to arrange steps
    const visited = new Set();
    const queue = [[startStep, 0, 0]]; // [step, level, index]
    const levelCounts = {};

    while (queue.length > 0) {
        const [step, level, index] = queue.shift();
        if (visited.has(step.id)) continue;
        visited.add(step.id);

        // Position step
        step.ui_position_x = 100 + level * 200;
        levelCounts[level] = (levelCounts[level] || 0) + 1;
        step.ui_position_y = 100 + (levelCounts[level] - 1) * 120;

        // Update DOM
        const el = document.querySelector(`[data-step-id="${step.id}"]`);
        if (el) {
            el.style.left = step.ui_position_x + 'px';
            el.style.top = step.ui_position_y + 'px';
        }

        // Find connected steps
        WorkflowDesigner.transitions
            .filter(t => t.from_step_id == step.id)
            .forEach(t => {
                const nextStep = WorkflowDesigner.steps[t.to_step_id];
                if (nextStep && !visited.has(nextStep.id)) {
                    queue.push([nextStep, level + 1, levelCounts[level + 1] || 0]);
                }
            });
    }

    WorkflowDesigner.renderConnections();
    WorkflowDesigner.saveState();
}

function deleteSelected() {
    WorkflowDesigner.deleteSelected();
}

function saveWorkflowSettings() {
    WorkflowDesigner.data.name = document.getElementById('settingsName').value;
    WorkflowDesigner.data.description = document.getElementById('settingsDescription').value;
    WorkflowDesigner.data.entity_type = document.getElementById('settingsEntityType').value;
    WorkflowDesigner.data.is_active = document.getElementById('settingsActive').checked ? 1 : 0;

    // Update page title
    document.querySelector('.page-title').innerHTML = `
        <i class="fas fa-project-diagram text-primary mr-2"></i>
        ${WorkflowDesigner.escapeHtml(WorkflowDesigner.data.name)}
        ${WorkflowDesigner.data.is_active ? '<span class="badge badge-success ml-2">Active</span>' : '<span class="badge badge-secondary ml-2">Draft</span>'}
    `;

    $('#workflowSettingsModal').modal('hide');
}

function saveAndActivate() {
    document.getElementById('settingsActive').checked = true;
    WorkflowDesigner.save();
}

function saveAsDraft() {
    document.getElementById('settingsActive').checked = false;
    WorkflowDesigner.save();
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    WorkflowDesigner.init();
});
</script>
