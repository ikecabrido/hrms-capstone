<?php
require_once __DIR__ . '/../classes/KpiController.php';

$controller = new KpiController();

$filters = [
    'search' => trim((string) ($_GET['search'] ?? '')),
    'department' => trim((string) ($_GET['department'] ?? '')),
    'employee_id' => trim((string) ($_GET['employee_id'] ?? '')),
    'status' => trim((string) ($_GET['status'] ?? '')),
    'semester' => trim((string) ($_GET['semester'] ?? 'this-semester')),
];

$dashboard = $controller->getDashboardData($filters);
$summary = $dashboard['summary'];
$employees = $dashboard['employees'];
$departments = $dashboard['departments'];
$kpis = $dashboard['kpis'];
$statusDistribution = $dashboard['status_distribution'];
$trend = $dashboard['monthly_trend'];
$employeeSummary = $dashboard['employee_summary'];
$message = $dashboard['message'];
$messageType = $dashboard['message_type'];
$historyMap = $dashboard['history_map'];
$selectedEmployeeId = !empty($filters['employee_id']) ? (int) $filters['employee_id'] : (int) ($employeeSummary['employee_id'] ?? 0);

$chartLabels = json_encode($trend['labels']);
$chartValues = json_encode($trend['data']);
$donutData = json_encode(array_map(fn($item) => [
    'label' => $item['label'],
    'value' => (float) $item['value'],
    'color' => $item['color'],
    'percentage' => (float) $item['percentage'],
], $statusDistribution['items']));

$statCards = [
    [
        'label' => 'Total KPI',
        'value' => number_format((int) ($summary['total_kpis'] ?? 0)),
        'meta' => 'Active KPIs',
        'icon' => 'fa-bullseye',
        'type' => 'highlight-blue',
    ],
    [
        'label' => 'Employees',
        'value' => number_format((int) ($summary['employees_with_kpi'] ?? 0)),
        'meta' => 'With KPI Assigned',
        'icon' => 'fa-users',
        'type' => 'highlight-gray',
    ],
    [
        'label' => 'Overall Achievement',
        'value' => number_format((float) ($summary['overall_achievement'] ?? 0), 2) . '%',
        'meta' => 'Current Performance',
        'icon' => 'fa-chart-line',
        'type' => 'highlight-emerald',
    ],
    [
        'label' => 'Achieved KPI',
        'value' => number_format((int) ($summary['achieved_kpis'] ?? 0)),
        'meta' => 'Completed Target',
        'icon' => 'fa-trophy',
        'type' => 'highlight-sky',
    ],
    [
        'label' => 'Needs Improvement',
        'value' => number_format((int) ($summary['needs_improvement'] ?? 0)),
        'meta' => 'Below Target',
        'icon' => 'fa-triangle-exclamation',
        'type' => 'highlight-amber',
    ],
];

function kpiBadgeClass(string $status): string
{
    $status = strtolower(trim($status));
    if (str_contains($status, 'achieved')) {
        return 'status-achieved';
    }
    if (str_contains($status, 'progress')) {
        return 'status-progress';
    }
    if (str_contains($status, 'partial')) {
        return 'status-partial';
    }

    return 'status-not-achieved';
}

function kpiProgressClass(float $value): string
{
    if ($value >= 90) {
        return 'success';
    }
    if ($value >= 70) {
        return 'warning';
    }

    return 'danger';
}
?>

<link rel="stylesheet" href="css/pages/kpi-tracking.css">

<div class="kpi-module">
    <div class="kpi-toolbar">
        <div>
            <h2>KPI Tracking</h2>
            <p>Track school employee performance, monitor KPI progress, and review achievement outcomes.</p>
        </div>
    </div>

    <div class="kpi-tracking-page">
    <div class="kpi-stat-row">
        <?php foreach ($statCards as $card): ?>
            <div class="kpi-stat-card <?= htmlspecialchars($card['type']) ?>">
                <div class="kpi-stat-head">
                    <span class="kpi-stat-icon"><i class="fa-solid <?= htmlspecialchars($card['icon']) ?>"></i></span>
                    <span><?= htmlspecialchars($card['label']) ?></span>
                </div>
                <div class="kpi-stat-number"><?= $card['value'] ?></div>
                <div class="kpi-stat-sub"><?= htmlspecialchars($card['meta']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="kpi-dashboard-row">
        <div class="kpi-panel">
            <div class="panel-topline">
                <h3>KPI Progress Overview</h3>
                <div class="filter-select small-select">
                    <select id="kpi-semester-filter" aria-label="Semester filter">
                        <option value="this-semester" <?= ($filters['semester'] === 'this-semester' || $filters['semester'] === '') ? 'selected' : '' ?>>This Semester</option>
                        <option value="last-quarter" <?= ($filters['semester'] === 'last-quarter') ? 'selected' : '' ?>>Last Quarter</option>
                        <option value="this-year" <?= ($filters['semester'] === 'this-year') ? 'selected' : '' ?>>This Year</option>
                        <option value="all" <?= ($filters['semester'] === 'all') ? 'selected' : '' ?>>All Time</option>
                    </select>
                </div>
            </div>
            <div class="chart-wrap">
                <canvas id="kpi-trend-chart" class="trend-chart" aria-label="KPI Progress Overview chart"></canvas>
            </div>
        </div>

        <div class="kpi-panel">
            <div class="panel-topline">
                <h3>KPI Status Distribution</h3>
            </div>
            <div class="donut-wrap">
                <div class="donut-chart" id="kpi-donut-chart" aria-label="KPI status donut chart">
                    <div class="donut-center"><strong><?= number_format((int) ($statusDistribution['total'] ?? 0)) ?></strong></div>
                </div>
                <div class="donut-legend">
                    <?php foreach ($statusDistribution['items'] as $item): ?>
                        <div class="legend-row">
                            <span class="legend-label"><i style="background: <?= htmlspecialchars($item['color']) ?>;"></i><?= htmlspecialchars($item['label']) ?></span>
                            <span><?= number_format((float) $item['percentage'], 1) ?>%</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="kpi-table-layout">
        <div class="kpi-panel">
            <div class="table-header">
                <h3>KPI List</h3>
                <div class="table-actions">
                    <div class="table-toolbar">
                        <div class="filter-select">
                            <select id="department-filter" name="department">
                                <option value="">Department</option>
                                <?php foreach ($departments as $department): ?>
                                    <option value="<?= (int) ($department['department_id'] ?? 0) ?>" <?= (string) ($filters['department'] ?? '') === (string) ($department['department_id'] ?? '') ? 'selected' : '' ?>><?= htmlspecialchars((string) ($department['department_name'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-select">
                            <select id="status-filter" name="status">
                                <option value="">Status</option>
                                <option value="Achieved" <?= ($filters['status'] === 'Achieved') ? 'selected' : '' ?>>Achieved</option>
                                <option value="In Progress" <?= ($filters['status'] === 'In Progress') ? 'selected' : '' ?>>In Progress</option>
                                <option value="Partially Achieved" <?= ($filters['status'] === 'Partially Achieved') ? 'selected' : '' ?>>Partially Achieved</option>
                                <option value="Not Achieved" <?= ($filters['status'] === 'Not Achieved') ? 'selected' : '' ?>>Not Achieved</option>
                            </select>
                        </div>
                        <button type="button" class="btn btn-primary" id="apply-kpi-filters">Apply Filter</button>
                        <button type="button" class="btn btn-secondary" id="reset-kpi-filters">Reset</button>
                    </div>
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="kpi-search" value="<?= htmlspecialchars((string) ($filters['search'] ?? '')) ?>" placeholder="Search Employee" aria-label="Search employee">
                    </div>
                    <button type="button" class="btn btn-add" id="open-kpi-form"><i class="fa-solid fa-plus"></i> Add KPI</button>
                </div>
            </div>

            <?php if (empty($kpis)): ?>
                <div class="empty-state">
                    <div>
                        <h4>No KPI records available</h4>
                        <p>Create or assign a KPI to an employee to start tracking performance.</p>
                        <button type="button" class="btn btn-add" id="empty-add-kpi"><i class="fa-solid fa-plus"></i> Add KPI</button>
                    </div>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="kpi-table">
                        <thead>
                            <tr>
                                <th>KPI Title</th>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Target</th>
                                <th>Actual</th>
                                <th>Progress</th>
                                <th>Weight</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($kpis as $kpi): ?>
                                <?php
                                $progressValue = (float) ($kpi['progress_percentage'] ?? 0);
                                $status = trim((string) ($kpi['status'] ?? 'In Progress'));
                                $kpiId = (int) ($kpi['kpi_id'] ?? 0);
                                $progressBarWidth = min(max($progressValue, 0), 100);
                                ?>
                                <tr>
                                    <td>
                                        <div class="kpi-title-cell">
                                            <span class="kpi-pill" style="background: <?= htmlspecialchars($kpi['status'] === 'Achieved' ? '#57d17b' : ($kpi['status'] === 'Not Achieved' ? '#ff6b6b' : ($kpi['status'] === 'Partially Achieved' ? '#f7c86d' : '#5ab8ff'))) ?>;">K</span>
                                            <span><?= htmlspecialchars((string) ($kpi['kpi_title'] ?? 'N/A')) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="employee-cell">
                                            <span class="employee-avatar" style="background: #dfeeff; color: #1e3a8a;"><?= strtoupper(substr(trim((string) ($kpi['employee_name'] ?? 'U')), 0, 2)) ?></span>
                                            <span><?= htmlspecialchars((string) ($kpi['employee_name'] ?? 'Unknown Employee')) ?></span>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars((string) ($kpi['department_name'] ?? 'Unassigned')) ?></td>
                                    <td><?= htmlspecialchars((string) ($kpi['target_value'] ?? '0')) ?><?= htmlspecialchars((string) ($kpi['unit'] ?? '%')) ?></td>
                                    <td><?= htmlspecialchars((string) ($kpi['actual_value'] ?? '0')) ?><?= htmlspecialchars((string) ($kpi['unit'] ?? '%')) ?></td>
                                    <td>
                                        <div class="progress-cell">
                                            <div class="progress-mini">
                                                <span style="width: <?= $progressBarWidth ?>%; background: <?= $progressBarWidth >= 90 ? '#57d17b' : ($progressBarWidth >= 70 ? '#f7c86d' : '#ff6b6b') ?>"></span>
                                            </div>
                                            <span><?= number_format($progressBarWidth, 0) ?>%</span>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars((string) ($kpi['weight'] ?? '0')) ?>%</td>
                                    <td><span class="status-badge <?= kpiBadgeClass($status) ?>"><?= htmlspecialchars($status) ?></span></td>
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

<div id="kpi-form-modal" class="modal hidden" aria-hidden="true">
    <div class="modal-backdrop" data-close-kpi-modal="true"></div>
    <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="kpi-modal-title">
        <div class="modal-header">
            <h3 id="kpi-modal-title">Add KPI</h3>
            <button type="button" class="modal-close" aria-label="Close" data-close-kpi-modal="true">×</button>
        </div>
        <form id="kpi-form" method="post">
            <input type="hidden" name="action" value="add_kpi">
            <div class="modal-grid">
                <div class="field-group">
                    <label for="kpi_employee_id">Employee</label>
                    <select id="kpi_employee_id" name="employee_id" required>
                        <option value="">Select employee</option>
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?= (int) ($employee['employee_id'] ?? 0) ?>"><?= htmlspecialchars((string) ($employee['employee_name'] ?? '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field-group">
                    <label for="kpi_department_id">Department</label>
                    <select id="kpi_department_id" name="department_id">
                        <option value="">Select department</option>
                        <?php foreach ($departments as $department): ?>
                            <option value="<?= (int) ($department['department_id'] ?? 0) ?>"><?= htmlspecialchars((string) ($department['department_name'] ?? '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field-group full-width">
                    <label for="kpi_title">KPI Title</label>
                    <input id="kpi_title" name="kpi_title" type="text" placeholder="Attendance Rate" required>
                </div>
                <div class="field-group full-width">
                    <label for="kpi_description">Description</label>
                    <input id="kpi_description" name="description" type="text" placeholder="Track teacher attendance and punctuality.">
                </div>
                <div class="field-group">
                    <label for="kpi_target_value">Target</label>
                    <input id="kpi_target_value" name="target_value" type="number" min="0" step="0.01" value="95" required>
                </div>
                <div class="field-group">
                    <label for="kpi_actual_value">Actual</label>
                    <input id="kpi_actual_value" name="actual_value" type="number" min="0" step="0.01" value="98">
                </div>
                <div class="field-group">
                    <label for="kpi_unit">Unit</label>
                    <select id="kpi_unit" name="unit">
                        <option value="%">%</option>
                        <option value="count">count</option>
                        <option value="sessions">sessions</option>
                        <option value="trainings">trainings</option>
                    </select>
                </div>
                <div class="field-group">
                    <label for="kpi_weight">Weight</label>
                    <input id="kpi_weight" name="weight" type="number" min="0" step="0.01" value="15">
                </div>
                <div class="field-group">
                    <label for="kpi_start_date">Start Date</label>
                    <input id="kpi_start_date" name="start_date" type="date">
                </div>
                <div class="field-group">
                    <label for="kpi_due_date">Due Date</label>
                    <input id="kpi_due_date" name="due_date" type="date">
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" data-close-kpi-modal="true">Cancel</button>
                <button type="submit" class="btn btn-primary">Save KPI</button>
            </div>
        </form>
    </div>
</div>

<script>
const summaryConfig = {
    labels: <?= $chartLabels ?>,
    values: <?= $chartValues ?>,
    donutData: <?= $donutData ?>,
};

const trendCtx = document.getElementById('kpi-trend-chart');
if (trendCtx) {
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: summaryConfig.labels,
            datasets: [{
                data: summaryConfig.values,
                borderColor: '#5a9bff',
                backgroundColor: 'rgba(90, 155, 255, 0.16)',
                borderWidth: 3,
                pointRadius: 4,
                pointHoverRadius: 5,
                pointBackgroundColor: '#4a7bf7',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                fill: true,
                tension: 0.38,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: { padding: { top: 10, right: 10, bottom: 0, left: 0 } },
            plugins: {
                legend: { display: false },
                tooltip: { enabled: true }
            },
            scales: {
                x: {
                    grid: { display: true, color: 'rgba(148, 163, 184, 0.12)' },
                    ticks: { color: '#64748b', font: { size: 11 } },
                    border: { display: false }
                },
                y: {
                    min: 0,
                    max: 100,
                    ticks: {
                        stepSize: 20,
                        color: '#64748b',
                        callback: value => value + '%'
                    },
                    grid: { color: 'rgba(148, 163, 184, 0.12)' },
                    border: { display: false }
                }
            }
        }
    });
}

const donutChart = document.getElementById('kpi-donut-chart');
if (donutChart && summaryConfig.donutData.length) {
    const total = summaryConfig.donutData.reduce((sum, item) => sum + Number(item.value || 0), 0);
    let start = 0;
    const segments = summaryConfig.donutData.map(item => {
        const value = Number(item.value || 0);
        const end = start + (total > 0 ? (value / total) * 100 : 0);
        const segment = `${item.color} ${start}% ${end}%`;
        start = end;
        return segment;
    });
    donutChart.style.background = `conic-gradient(${segments.join(', ')})`;
    donutChart.style.position = 'relative';
}

const buildFilterUrl = () => {
    const params = new URLSearchParams(window.location.search);
    const filters = {
        search: document.getElementById('kpi-search')?.value ?? '',
        department: document.getElementById('department-filter')?.value ?? '',
        employee_id: document.getElementById('employee-filter')?.value ?? '',
        status: document.getElementById('status-filter')?.value ?? '',
        semester: document.getElementById('kpi-semester-filter')?.value ?? 'this-semester',
    };

    for (const key of ['search', 'department', 'employee_id', 'status', 'semester']) {
        if (filters[key]) {
            params.set(key, filters[key]);
        } else {
            params.delete(key);
        }
    }

    return '?' + params.toString();
};

const applyKpiFilters = () => {
    window.location.href = buildFilterUrl();
};

document.getElementById('apply-kpi-filters')?.addEventListener('click', applyKpiFilters);
document.getElementById('reset-kpi-filters')?.addEventListener('click', () => {
    window.location.href = '?page=kpi-tracking';
});

document.getElementById('kpi-search')?.addEventListener('keydown', (event) => {
    if (event.key === 'Enter') {
        applyKpiFilters();
    }
});

document.getElementById('kpi-semester-filter')?.addEventListener('change', applyKpiFilters);

const modal = document.getElementById('kpi-form-modal');
const openModal = () => {
    modal?.classList.remove('hidden');
    modal?.setAttribute('aria-hidden', 'false');
};
const closeModal = () => {
    modal?.classList.add('hidden');
    modal?.setAttribute('aria-hidden', 'true');
};

document.getElementById('open-kpi-form')?.addEventListener('click', openModal);
document.getElementById('empty-add-kpi')?.addEventListener('click', openModal);
document.querySelectorAll('[data-close-kpi-modal="true"]').forEach((element) => {
    element.addEventListener('click', closeModal);
});

document.getElementById('view-full-kpi-details')?.addEventListener('click', () => {
    const employeeId = document.getElementById('employee-filter')?.value || <?= json_encode((int) ($selectedEmployeeId ?: 0)) ?>;
    const target = employeeId ? `?page=kpi-tracking&employee_id=${employeeId}` : '?page=kpi-tracking';
    window.location.href = target;
});

document.querySelectorAll('[data-action="view"]').forEach((button) => {
    button.addEventListener('click', () => {
        const kpiId = button.getAttribute('data-kpi-id');
        const target = '?page=kpi-tracking&employee_id=' + (<?= json_encode((int) ($selectedEmployeeId ?: 0)) ?> || '') + '&kpi_id=' + kpiId;
        window.location.href = target;
    });
});
</script>
