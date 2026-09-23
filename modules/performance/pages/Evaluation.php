<?php
require_once __DIR__ . '/../controller/EvaluationController.php';

$controller = new EvaluationController();
$dashboard = $controller->getDashboardData();
$stats = $dashboard['stats'] ?? [];
$employees = $dashboard['employees'] ?? [];
$evaluations = $controller->getEvaluations($controller->getFilters());
$departments = $dashboard['departments'] ?? [];
$cycles = $dashboard['cycles'] ?? [];
$messages = $controller->getMessages();
$csrfToken = $controller->getCsrfToken();
$filters = $controller->getFilters();

$defaultCriteria = [
    'Job Performance',
    'Productivity',
    'Attendance',
    'Goal Achievement',
    'KPI Achievement',
    'Teamwork',
    'Communication',
    'Professionalism',
    'Adaptability',
    'Initiative',
];

$ratingOptions = [
    ['value' => 5, 'label' => '5 - Outstanding'],
    ['value' => 4, 'label' => '4 - Exceeds Expectations'],
    ['value' => 3, 'label' => '3 - Very Good'],
    ['value' => 2, 'label' => '2 - Needs Improvement'],
    ['value' => 1, 'label' => '1 - Unsatisfactory'],
];

$employeeLookup = [];
foreach ($employees as $employee) {
    $employeeLookup[] = [
        'employee_id' => (string) ($employee['employee_id'] ?? ''),
        'employee_name' => (string) ($employee['employee_name'] ?? ''),
        'department' => (string) ($employee['department'] ?? ''),
        'position' => (string) ($employee['position'] ?? ''),
    ];
}

$emptyState = empty($evaluations);
?>

<link rel="stylesheet" href="css/pages/evaluation.css"> 

<div class="evaluation-page">
    <div class="evaluation-topbar">
        <div class="evaluation-title-card">
            <div>
                <h1>Evaluation</h1>
                <p>Assess employee performance based on goals, KPIs, feedback, and overall contributions.</p>
            </div>
            <button type="button" class="primary-btn" id="openEvaluationModal">Create Evaluation</button>
        </div>
    </div>

    <?php if (!empty($messages['success'])): ?>
        <div class="alert success" role="alert"><?= htmlspecialchars($messages['success']) ?></div>
    <?php endif; ?>
    <?php if (!empty($messages['error'])): ?>
        <div class="alert error" role="alert"><?= htmlspecialchars($messages['error']) ?></div>
    <?php endif; ?>

    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-header">
                <span class="icon blue"><i class="fa-solid fa-users"></i></span>
                <span class="label">Total Employees</span>
            </div>
            <div class="summary-value"><?= (int) ($stats['total_employees'] ?? 0) ?></div>
        </div>
        <div class="summary-card">
            <div class="summary-header">
                <span class="icon green"><i class="fa-solid fa-circle-check"></i></span>
                <span class="label">Completed</span>
            </div>
            <div class="summary-value"><?= (int) ($stats['completed'] ?? 0) ?></div>
        </div>
        <div class="summary-card">
            <div class="summary-header">
                <span class="icon orange"><i class="fa-solid fa-clock"></i></span>
                <span class="label">In Progress</span>
            </div>
            <div class="summary-value"><?= (int) ($stats['in_progress'] ?? 0) ?></div>
        </div>
        <div class="summary-card">
            <div class="summary-header">
                <span class="icon pink"><i class="fa-solid fa-hourglass-half"></i></span>
                <span class="label">Not Evaluated</span>
            </div>
            <div class="summary-value"><?= (int) ($stats['not_evaluated'] ?? 0) ?></div>
        </div>
    </div>

    <div class="evaluation-panel">
        <div class="filters-row">
            <div class="search-wrap">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="evaluationSearchInput" value="<?= htmlspecialchars($filters['search']) ?>" placeholder="Search employee name or ID" autocomplete="off">
            </div>
            <select id="departmentFilter">
                <option value="">All Departments</option>
                <?php foreach ($departments as $department): ?>
                    <?php $deptName = (string) ($department['department'] ?? ''); ?>
                    <option value="<?= htmlspecialchars($deptName) ?>" <?= ($filters['department'] === $deptName) ? 'selected' : '' ?>><?= htmlspecialchars($deptName) ?></option>
                <?php endforeach; ?>
            </select>
            <select id="cycleFilter">
                <option value="">All Cycles</option>
                <?php foreach ($cycles as $cycle): ?>
                    <?php $cycleName = (string) ($cycle['cycle_name'] ?? ''); ?>
                    <option value="<?= htmlspecialchars($cycleName) ?>" <?= ($filters['cycle'] === $cycleName) ? 'selected' : '' ?>><?= htmlspecialchars($cycleName) ?></option>
                <?php endforeach; ?>
            </select>
            <select id="statusFilter">
                <option value="">All Status</option>
                <option value="Not Evaluated" <?= ($filters['status'] === 'Not Evaluated') ? 'selected' : '' ?>>Not Evaluated</option>
                <option value="In Progress" <?= ($filters['status'] === 'In Progress') ? 'selected' : '' ?>>In Progress</option>
                <option value="Completed" <?= ($filters['status'] === 'Completed') ? 'selected' : '' ?>>Completed</option>
            </select>
            <button type="button" id="applyEvaluationFilters" class="primary-btn small-btn">Apply</button>
            <button type="button" id="clearEvaluationFilters" class="secondary-btn small-btn">Clear</button>
        </div>

        <div class="section-head">
            <h2>Evaluation List</h2>
        </div>

        <?php if ($emptyState): ?>
            <div class="empty-state">
                <h3>No evaluation records yet.</h3>
                <p>Create an evaluation to begin assessing employee performance.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Employee ID</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Evaluation Cycle</th>
                            <th>Score</th>
                            <th>Rating</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($evaluations as $evaluation): ?>
                            <?php $score = $evaluation['overall_score'] ?? null; ?>
                            <tr>
                                <td>
                                    <div class="employee-cell">
                                        <div class="avatar-small"><?= htmlspecialchars(substr(trim((string) ($evaluation['employee_name'] ?? 'U')), 0, 1)) ?></div>
                                        <div>
                                            <strong><?= htmlspecialchars((string) ($evaluation['employee_name'] ?? 'Unknown')) ?></strong>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars((string) ($evaluation['employee_id'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($evaluation['department'] ?? 'N/A')) ?></td>
                                <td><?= htmlspecialchars((string) ($evaluation['position'] ?? 'N/A')) ?></td>
                                <td><?= htmlspecialchars((string) ($evaluation['evaluation_cycle'] ?? 'N/A')) ?></td>
                                <td><?= $score !== null && $score !== '' ? number_format((float) $score, 2) . '%' : '—' ?></td>
                                <td>
                                    <?php if (!empty($evaluation['overall_rating'])): ?>
                                        <span class="rating-badge"><?= htmlspecialchars((string) $evaluation['overall_rating']) ?></span>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge <?= strtolower(str_replace(' ', '-', (string) ($evaluation['status'] ?? 'Not Evaluated'))) ?>"><?= htmlspecialchars((string) ($evaluation['status'] ?? 'Not Evaluated')) ?></span>
                                </td>
                                <td>
                                    <div class="action-group">
                                        <button type="button" class="action-link view-evaluation" data-evaluation-id="<?= (int) ($evaluation['evaluation_id'] ?? 0) ?>">View</button>
                                        <button type="button" class="action-link edit-evaluation" data-evaluation-id="<?= (int) ($evaluation['evaluation_id'] ?? 0) ?>">Edit</button>
                                        <button type="button" class="action-link delete-evaluation danger" data-evaluation-id="<?= (int) ($evaluation['evaluation_id'] ?? 0) ?>">Delete</button>
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

<div class="modal-backdrop" id="evaluationModal" aria-hidden="true">
    <div class="evaluation-modal">
        <div class="modal-header">
            <h3 id="modalTitle">Create Evaluation</h3>
            <button type="button" class="close-btn" id="closeEvaluationModal">×</button>
        </div>

        <form method="POST" id="evaluationForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="action" id="evaluationAction" value="create_evaluation">
            <input type="hidden" name="evaluation_id" id="evaluationId" value="">

            <div class="modal-section">
                <h4>Employee Information</h4>
                <div class="form-grid two-col">
                    <div class="field employee-search-field">
                        <label>Employee ID</label>
                        <input type="text" name="employee_id" id="employeeIdField" placeholder="Type EMP001 or employee name" autocomplete="off" required>
                        <div id="employeeSuggestions" class="employee-suggestions"></div>
                    </div>
                    <div class="field">
                        <label>Employee Name</label>
                        <input type="text" name="employee_name" id="employeeNameField" readonly>
                    </div>
                    <div class="field">
                        <label>Department</label>
                        <input type="text" name="department" id="departmentField" readonly>
                    </div>
                    <div class="field">
                        <label>Position</label>
                        <input type="text" name="position" id="positionField" readonly>
                    </div>
                </div>
            </div>

            <div class="modal-section">
                <h4>Evaluation Information</h4>
                <div class="form-grid two-col">
                    <div class="field">
                        <label>Evaluation Cycle</label>
                        <input type="text" name="evaluation_cycle" id="evaluationCycleField" required>
                    </div>
                    <div class="field">
                        <label>Evaluation Period</label>
                        <div class="date-inline">
                            <input type="date" name="evaluation_period_start" id="evaluationPeriodStartField">
                            <span>to</span>
                            <input type="date" name="evaluation_period_end" id="evaluationPeriodEndField">
                        </div>
                    </div>
                    <div class="field">
                        <label>Evaluator</label>
                        <input type="text" name="evaluator_name" value="<?= htmlspecialchars($_SESSION['employee_name'] ?? 'System') ?>" required>
                    </div>
                    <div class="field">
                        <label>Evaluation Date</label>
                        <input type="date" name="evaluation_date" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
            </div>

            <div class="modal-section">
                <h4>Performance Criteria</h4>
                <div class="criteria-list">
                    <?php foreach ($defaultCriteria as $criterion): ?>
                        <div class="criterion-row">
                            <div class="criterion-name"><?= htmlspecialchars($criterion) ?></div>
                            <input type="hidden" name="criterion_name[]" value="<?= htmlspecialchars($criterion) ?>">
                            <label>
                                <span>Rating</span>
                                <select name="criterion_rating[]" class="criterion-rating">
                                    <option value="">Select</option>
                                    <?php foreach ($ratingOptions as $option): ?>
                                        <option value="<?= (int) $option['value'] ?>"><?= htmlspecialchars((string) $option['label']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>
                                <span>Score</span>
                                <input type="text" class="criterion-score" readonly value="0%">
                            </label>
                            <label class="full-width">
                                <span>Comments</span>
                                <textarea name="criterion_comment[]" rows="2" placeholder="Add comment"></textarea>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="modal-section">
                <h4>Overall Summary</h4>
                <div class="summary-fields">
                    <div class="field">
                        <label>Overall Score</label>
                        <input type="text" id="overallScoreDisplay" value="0%" readonly>
                    </div>
                    <div class="field">
                        <label>Overall Rating</label>
                        <input type="text" id="overallRatingDisplay" value="Not Available" readonly>
                    </div>
                </div>
                <div class="form-grid two-col">
                    <div class="field">
                        <label>Strengths</label>
                        <textarea name="strengths" rows="3" placeholder="Strengths observed..."></textarea>
                    </div>
                    <div class="field">
                        <label>Areas for Improvement</label>
                        <textarea name="areas_for_improvement" rows="3" placeholder="Areas requiring improvement..."></textarea>
                    </div>
                    <div class="field">
                        <label>Recommended Training</label>
                        <textarea name="recommended_training" rows="3" placeholder="Recommended training programs..."></textarea>
                    </div>
                    <div class="field">
                        <label>Final Remarks</label>
                        <textarea name="final_remarks" rows="3" placeholder="Final evaluator remarks..."></textarea>
                    </div>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="secondary-btn" id="cancelEvaluationModal">Close</button>
                <button type="submit" class="primary-btn">Save Evaluation</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-backdrop detail-modal" id="evaluationDetailModal" aria-hidden="true">
    <div class="evaluation-detail-modal">
        <div class="modal-header">
            <h3>Employee Evaluation Detail</h3>
            <button type="button" class="close-btn" data-close-detail-modal>×</button>
        </div>
        <div id="evaluationDetailContent"></div>
    </div>
</div>

<script>
const employeeList = <?= json_encode($employeeLookup, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
const evaluationRecords = <?= json_encode($evaluations, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
const evaluationModal = document.getElementById('evaluationModal');
const evaluationForm = document.getElementById('evaluationForm');
const employeeIdField = document.getElementById('employeeIdField');
const employeeNameField = document.getElementById('employeeNameField');
const departmentField = document.getElementById('departmentField');
const positionField = document.getElementById('positionField');
const employeeSuggestions = document.getElementById('employeeSuggestions');
const overallScoreDisplay = document.getElementById('overallScoreDisplay');
const overallRatingDisplay = document.getElementById('overallRatingDisplay');

function findEmployee(query) {
    const q = (query || '').trim().toLowerCase();
    if (!q) return employeeList;
    return employeeList.filter((employee) => {
        const text = [employee.employee_id, employee.employee_name, employee.department, employee.position].join(' ').toLowerCase();
        return text.includes(q);
    }).slice(0, 8);
}

function renderSuggestions(query) {
    const matches = findEmployee(query);
    if (!matches.length) {
        employeeSuggestions.innerHTML = '<div class="suggestion-empty">No matching employees found.</div>';
        return;
    }
    employeeSuggestions.innerHTML = matches.map((employee) => {
        return '<button type="button" class="suggestion-item" data-employee-id="' + employee.employee_id + '"><strong>' + (employee.employee_name || 'Unknown') + '</strong><span>' + (employee.employee_id || '') + ' • ' + (employee.department || '') + ' • ' + (employee.position || '') + '</span></button>';
    }).join('');
}

function populateEmployeeData(employee) {
    if (!employee) return;
    employeeIdField.value = employee.employee_id || '';
    employeeNameField.value = employee.employee_name || '';
    departmentField.value = employee.department || '';
    positionField.value = employee.position || '';
    employeeSuggestions.innerHTML = '';
}

function calculateOverallFromCriteria() {
    const ratings = Array.from(document.querySelectorAll('.criterion-rating')).map((select) => Number(select.value || 0));
    const valid = ratings.filter((value) => value > 0);
    if (!valid.length) {
        overallScoreDisplay.value = '0%';
        overallRatingDisplay.value = 'Not Available';
        return;
    }
    const average = (valid.reduce((sum, value) => sum + value, 0) / valid.length) * 20;
    overallScoreDisplay.value = average.toFixed(2) + '%';
    let rating = 'Unsatisfactory';
    if (average >= 90) rating = 'Outstanding';
    else if (average >= 80) rating = 'Exceeds Expectations';
    else if (average >= 70) rating = 'Very Good';
    else if (average >= 60) rating = 'Good';
    else if (average >= 40) rating = 'Needs Improvement';
    overallRatingDisplay.value = rating;
}

function syncScores() {
    document.querySelectorAll('.criterion-row').forEach((row) => {
        const select = row.querySelector('.criterion-rating');
        const scoreInput = row.querySelector('.criterion-score');
        const value = Number(select.value || 0);
        scoreInput.value = value ? (value * 20) + '%' : '0%';
    });
    calculateOverallFromCriteria();
}

function renderDetail(item) {
    const criteria = item.criteria && item.criteria.length ? item.criteria : [];
    const rows = criteria.map((criterion) => {
        const label = criterion.criterion_name || 'Criterion';
        const rating = criterion.rating ? criterion.rating + ' / 5' : '—';
        const comment = criterion.comments ? '<div class="detail-comment"><strong>Comment:</strong> ' + criterion.comments + '</div>' : '';
        return '<div class="detail-criterion"><div class="detail-row"><span>' + label + '</span><strong>' + rating + '</strong></div>' + comment + '</div>';
    }).join('');

    const html = '<div class="detail-layout">' +
        '<div class="detail-header-box"><div class="avatar-large">' + ((item.employee_name || 'U').charAt(0).toUpperCase()) + '</div><div><h4>' + (item.employee_name || 'Unknown') + '</h4><p>' + (item.employee_id || 'N/A') + '</p></div></div>' +
        '<div class="detail-grid">' +
        '<div><label>Employee</label><p>' + (item.employee_name || 'Unknown') + '</p></div>' +
        '<div><label>Employee ID</label><p>' + (item.employee_id || 'N/A') + '</p></div>' +
        '<div><label>Department</label><p>' + (item.department || 'N/A') + '</p></div>' +
        '<div><label>Position</label><p>' + (item.position || 'N/A') + '</p></div>' +
        '<div><label>Evaluation Cycle</label><p>' + (item.evaluation_cycle || 'N/A') + '</p></div>' +
        '<div><label>Evaluation Period</label><p>' + (item.evaluation_period_start || 'N/A') + ' to ' + (item.evaluation_period_end || 'N/A') + '</p></div>' +
        '<div><label>Evaluator</label><p>' + (item.evaluator_name || 'N/A') + '</p></div>' +
        '<div><label>Evaluation Date</label><p>' + (item.evaluation_date || 'N/A') + '</p></div>' +
        '<div><label>Overall Score</label><p>' + (item.overall_score !== null && item.overall_score !== '' ? item.overall_score + '%' : '—') + '</p></div>' +
        '<div><label>Overall Rating</label><p>' + (item.overall_rating || '—') + '</p></div>' +
        '</div>' +
        '<div class="detail-section"><h5>Performance Criteria</h5>' + rows + '</div>' +
        '<div class="detail-section"><h5>Strengths</h5><p>' + (item.strengths || 'No data available') + '</p></div>' +
        '<div class="detail-section"><h5>Areas for Improvement</h5><p>' + (item.areas_for_improvement || 'No data available') + '</p></div>' +
        '<div class="detail-section"><h5>Recommended Training</h5><p>' + (item.recommended_training || 'No data available') + '</p></div>' +
        '<div class="detail-section"><h5>Final Remarks</h5><p>' + (item.final_remarks || 'No final remarks') + '</p></div>' +
        '</div>';
    document.getElementById('evaluationDetailContent').innerHTML = html;
}

employeeIdField.addEventListener('input', function () {
    const query = this.value.trim();
    if (!query) {
        employeeNameField.value = '';
        departmentField.value = '';
        positionField.value = '';
        employeeSuggestions.innerHTML = '';
        return;
    }
    const matches = findEmployee(query);
    renderSuggestions(query);
    if (matches.length) {
        const match = matches.find((employee) => String(employee.employee_id).toLowerCase() === query.toLowerCase()) || matches[0];
        populateEmployeeData(match);
    }
});

document.addEventListener('click', function (event) {
    const createButton = event.target.closest('#openEvaluationModal');
    if (createButton) openCreateModal();

    const closeButton = event.target.closest('#closeEvaluationModal') || event.target.closest('#cancelEvaluationModal');
    if (closeButton) {
        evaluationModal.classList.remove('open');
        evaluationModal.setAttribute('aria-hidden', 'true');
    }

    const suggestionButton = event.target.closest('.suggestion-item');
    if (suggestionButton) {
        const employeeId = suggestionButton.dataset.employeeId;
        const match = employeeList.find((employee) => String(employee.employee_id) === String(employeeId));
        if (match) populateEmployeeData(match);
    }

    const viewButton = event.target.closest('.view-evaluation');
    if (viewButton) {
        const item = evaluationRecords.find((evaluation) => Number(evaluation.evaluation_id) === Number(viewButton.dataset.evaluationId));
        if (item) {
            renderDetail(item);
            const modal = document.getElementById('evaluationDetailModal');
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
        }
    }

    const editButton = event.target.closest('.edit-evaluation');
    if (editButton) {
        const item = evaluationRecords.find((evaluation) => Number(evaluation.evaluation_id) === Number(editButton.dataset.evaluationId));
        if (item) {
            document.getElementById('modalTitle').textContent = 'Edit Evaluation';
            document.getElementById('evaluationAction').value = 'update_evaluation';
            document.getElementById('evaluationId').value = item.evaluation_id;
            const employee = employeeList.find((entry) => String(entry.employee_id) === String(item.employee_id)) || { employee_id: item.employee_id, employee_name: item.employee_name, department: item.department, position: item.position };
            populateEmployeeData(employee);
            document.getElementById('evaluationCycleField').value = item.evaluation_cycle || '';
            document.querySelector('input[name="evaluation_period_start"]').value = item.evaluation_period_start || '';
            document.querySelector('input[name="evaluation_period_end"]').value = item.evaluation_period_end || '';
            document.querySelector('input[name="evaluator_name"]').value = item.evaluator_name || '';
            document.querySelector('input[name="evaluation_date"]').value = item.evaluation_date || '';
            document.querySelector('textarea[name="strengths"]').value = item.strengths || '';
            document.querySelector('textarea[name="areas_for_improvement"]').value = item.areas_for_improvement || '';
            document.querySelector('textarea[name="recommended_training"]').value = item.recommended_training || '';
            document.querySelector('textarea[name="final_remarks"]').value = item.final_remarks || '';

            const criteria = item.criteria || [];
            document.querySelectorAll('.criterion-row').forEach((row) => {
                const name = row.querySelector('input[name="criterion_name[]"]').value;
                const match = criteria.find((itemC) => (itemC.criterion_name || '').toLowerCase() === name.toLowerCase());
                row.querySelector('.criterion-rating').value = match ? (match.rating || '') : '';
                row.querySelector('textarea[name="criterion_comment[]"]').value = match ? (match.comments || '') : '';
            });
            syncScores();
            evaluationModal.classList.add('open');
            evaluationModal.setAttribute('aria-hidden', 'false');
        }
    }

    const deleteButton = event.target.closest('.delete-evaluation');
    if (deleteButton) {
        const id = deleteButton.dataset.evaluationId;
        if (confirm('Are you sure you want to delete this evaluation?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '?page=evaluation';
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = '<?= htmlspecialchars($csrfToken) ?>';
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'delete_evaluation';
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'evaluation_id';
            idInput.value = id;
            form.appendChild(csrfInput);
            form.appendChild(actionInput);
            form.appendChild(idInput);
            document.body.appendChild(form);
            form.submit();
        }
    }

    const closeDetailModal = event.target.closest('[data-close-detail-modal]');
    if (closeDetailModal) {
        const modal = document.getElementById('evaluationDetailModal');
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
    }
});

function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Create Evaluation';
    document.getElementById('evaluationAction').value = 'create_evaluation';
    document.getElementById('evaluationId').value = '';
    evaluationForm.reset();
    employeeNameField.value = '';
    departmentField.value = '';
    positionField.value = '';
    overallScoreDisplay.value = '0%';
    overallRatingDisplay.value = 'Not Available';
    document.querySelectorAll('.criterion-rating').forEach((select) => { select.value = ''; });
    syncScores();
    evaluationModal.classList.add('open');
    evaluationModal.setAttribute('aria-hidden', 'false');
}

document.querySelectorAll('.criterion-rating').forEach((select) => {
    select.addEventListener('change', syncScores);
});

const applyBtn = document.getElementById('applyEvaluationFilters');
if (applyBtn) {
    applyBtn.addEventListener('click', function () {
        const params = new URLSearchParams();
        params.set('page', 'evaluation');
        const search = document.getElementById('evaluationSearchInput').value.trim();
        const department = document.getElementById('departmentFilter').value;
        const cycle = document.getElementById('cycleFilter').value;
        const status = document.getElementById('statusFilter').value;
        if (search) params.set('search', search);
        if (department) params.set('department', department);
        if (cycle) params.set('evaluation_cycle', cycle);
        if (status) params.set('status', status);
        window.location.href = '?' + params.toString();
    });
}

const clearBtn = document.getElementById('clearEvaluationFilters');
if (clearBtn) {
    clearBtn.addEventListener('click', function () {
        window.location.href = '?page=evaluation';
    });
}

syncScores();
</script>
