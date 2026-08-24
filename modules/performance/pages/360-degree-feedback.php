 <?php
require_once __DIR__ . '/../../../auth/session.php';
require_once __DIR__ . '/../classes/FeedbackController.php';

$selectedCycle = trim((string) ($_GET['cycle'] ?? ''));
$selectedRaterType = trim((string) ($_GET['rater_type'] ?? 'all'));
$controller = new FeedbackController();
$dashboard = $controller->getDashboardData([
    'cycle' => $selectedCycle,
    'rater_type' => $selectedRaterType,
]);

$stats = $dashboard['stats'];
$overview = $dashboard['overview'];
$raterBreakdown = $dashboard['raterBreakdown'];
$progress = $dashboard['progress'];
$overall = $dashboard['overall'];
$competencySummary = $dashboard['competencySummary'];
$recentSubmissions = $dashboard['recentSubmissions'];
$cycles = $dashboard['cycles'];

if (!empty($_GET['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode($dashboard);
    exit;
}

$cycleOptions = [];
foreach ($cycles as $cycle) {
    $cycleLabel = trim((string) ($cycle['title'] ?? '')) ?: (trim((string) ($cycle['cycle_period'] ?? '')) ?: 'Cycle ' . (int) ($cycle['cycle_id'] ?? 0));
    $cycleKey = trim((string) ($cycle['cycle_period'] ?? $cycleLabel));
    $cycleOptions[$cycleKey] = $cycleLabel;
}

$selectedCycleLabel = $selectedCycle !== '' ? ($cycleOptions[$selectedCycle] ?? $selectedCycle) : 'All Cycles';
$overviewEmpty = empty($overview['datasets'][0]['data']) || array_sum($overview['datasets'][0]['data']) === 0;
$progressEmpty = empty($progress['items']) || $progress['total'] <= 0;
$raterEmpty = $raterBreakdown['total'] <= 0;
$recentEmpty = empty($recentSubmissions);

function formatScore(float $score): string
{
    return number_format($score, 2, '.', '');
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
    :root {
        --bg: #f3f6fb;
        --panel: #ffffff;
        --panel-soft: #f8fafc;
        --line: #e7ecf3;
        --text: #1d2433;
        --muted: #687586;
        --blue: #3b82f6;
        --cyan: #2dd4bf;
        --purple: #8b5cf6;
        --orange: #f59e0b;
        --green: #22c55e;
        --red: #ef4444;
        --shadow: 0 10px 20px rgba(15, 23, 42, 0.04);
    }

    * { box-sizing: border-box; }

    .feedback-dashboard-root {
        background: var(--bg);
        min-height: 100vh;
        padding: 18px 0 32px;
        color: var(--text);
        font-family: "Segoe UI", Tahoma, sans-serif;
    }

    .fd-shell {
        max-width: 1280px;
        margin: 0 auto;
        padding: 0 12px;
    }

    .fd-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        margin-bottom: 18px;
    }

    .fd-header h1 {
        margin: 0;
        font-size: 1.55rem;
        font-weight: 700;
        color: var(--text);
    }

    .fd-header p {
        margin: 4px 0 0;
        font-size: 0.82rem;
        color: var(--muted);
    }

    .fd-header-actions {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .fd-select {
        border: 1px solid var(--line);
        border-radius: 8px;
        background: var(--panel);
        color: var(--text);
        padding: 10px 12px;
        min-width: 160px;
        font-size: 0.88rem;
        box-shadow: var(--shadow);
    }

    .fd-button {
        appearance: none;
        border: 1px solid transparent;
        background: var(--panel);
        color: var(--text);
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 0.84rem;
        font-weight: 600;
        cursor: pointer;
        transition: 0.2s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: var(--shadow);
    }

    .fd-button:hover { transform: translateY(-1px); }
    .fd-button.primary {
        background: linear-gradient(180deg, #4f8cf7, #2f6fe8);
        color: #fff;
    }
    .fd-button.secondary {
        background: #fff;
        border-color: var(--line);
    }

    .fd-cards {
        display: grid;
        grid-template-columns: repeat(4, minmax(190px, 1fr));
        gap: 16px;
        margin-bottom: 22px;
    }

    .fd-card {
        background: var(--panel);
        border: 1px solid var(--line);
        border-radius: 14px;
        box-shadow: var(--shadow);
        padding: 16px 18px;
        min-height: 112px;
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .fd-card-icon {
        width: 44px;
        height: 44px;
        display: grid;
        place-items: center;
        border-radius: 12px;
        font-size: 1.15rem;
        color: var(--blue);
        background: rgba(59, 130, 246, 0.08);
    }

    .fd-card:nth-child(2) .fd-card-icon { background: rgba(139, 92, 246, 0.1); color: var(--purple); }
    .fd-card:nth-child(3) .fd-card-icon { background: rgba(34, 197, 94, 0.1); color: var(--green); }
    .fd-card:nth-child(4) .fd-card-icon { background: rgba(245, 158, 11, 0.12); color: var(--orange); }

    .fd-card-data {
        flex: 1;
        min-width: 0;
    }

    .fd-card-number {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 700;
        line-height: 1.2;
        letter-spacing: -0.02em;
    }

    .fd-card-label {
        display: block;
        font-size: 0.75rem;
        color: var(--muted);
        margin-top: 2px;
    }

    .fd-card-link {
        display: inline-block;
        margin-top: 8px;
        font-size: 0.76rem;
        color: var(--blue);
        text-decoration: none;
        font-weight: 600;
    }

    .fd-main-grid {
        display: grid;
        grid-template-columns: 1.1fr 1fr;
        gap: 18px;
        margin-bottom: 18px;
    }

    .fd-panel {
        background: var(--panel);
        border: 1px solid var(--line);
        border-radius: 14px;
        box-shadow: var(--shadow);
        padding: 18px 18px 14px;
    }

    .fd-panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        margin-bottom: 14px;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--line);
    }

    .fd-panel-header h2,
    .fd-panel-header h3 {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
    }

    .fd-select-inline {
        border: 1px solid var(--line);
        border-radius: 7px;
        padding: 7px 10px;
        background: #fff;
        font-size: 0.76rem;
        color: var(--text);
        width: 140px;
    }

    .fd-radar-wrap {
        position: relative;
        height: 290px;
        margin-bottom: 10px;
    }

    .fd-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 10px 16px;
        justify-content: center;
        margin-top: 6px;
    }

    .fd-legend-item {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        font-size: 0.72rem;
        color: var(--muted);
    }

    .fd-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
    }

    .fd-donut-layout {
        display: grid;
        grid-template-columns: 200px 1fr;
        gap: 8px;
        align-items: center;
        min-height: 227px;
    }

    .fd-donut-wrap {
        position: relative;
        width: 180px;
        height: 180px;
        margin: 0 auto;
    }

    .fd-donut-total {
        position: absolute;
        inset: 0;
        display: grid;
        place-items: center;
        text-align: center;
    }

    .fd-donut-total strong {
        display: block;
        font-size: 1.2rem;
        line-height: 1;
    }

    .fd-donut-total span {
        display: block;
        font-size: 0.68rem;
        color: var(--muted);
        margin-top: 6px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .fd-breakdown {
        display: flex;
        flex-direction: column;
        gap: 8px;
        padding-right: 8px;
    }

    .fd-breakdown-row {
        display: grid;
        grid-template-columns: 1fr auto auto;
        align-items: center;
        gap: 8px;
        padding: 6px 8px;
        border-radius: 8px;
        background: #f8fafc;
    }

    .fd-breakdown-label {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 0.75rem;
        color: var(--text);
    }

    .fd-breakdown-row .fd-count {
        font-size: 0.8rem;
        color: var(--muted);
        font-weight: 600;
    }

    .fd-breakdown-row .fd-percent {
        font-size: 0.74rem;
        color: var(--muted);
    }

    .fd-progress-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-top: 10px;
    }

    .fd-progress-item {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 8px;
    }

    .fd-progress-meta {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.76rem;
        color: var(--text);
        margin-bottom: 4px;
    }

    .fd-progress-meta i {
        width: 16px;
        text-align: center;
        font-size: 0.72rem;
    }

    .fd-progress-bar {
        width: 100%;
        height: 9px;
        border-radius: 999px;
        background: #edf2f7;
        overflow: hidden;
        display: block;
        position: relative;
    }

    .fd-progress-fill {
        height: 100%;
        border-radius: inherit;
        display: block;
    }

    .fd-progress-score {
        font-size: 0.74rem;
        color: var(--muted);
        font-weight: 600;
        min-width: 66px;
        text-align: right;
    }

    .fd-link-row {
        margin-top: 12px;
        display: flex;
        justify-content: flex-end;
    }

    .fd-score-box {
        display: flex;
        align-items: center;
        gap: 20px;
        margin-top: 18px;
        padding: 8px 0;
    }

    .fd-score-ring {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        background: linear-gradient(135deg, #3b82f6, #4f46e5);
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .fd-score-ring::before {
        content: "";
        position: absolute;
        inset: 9px;
        border-radius: 50%;
        background: #fff;
    }

    .fd-score-ring strong {
        position: relative;
        font-size: 1.1rem;
        z-index: 1;
        color: var(--text);
    }

    .fd-score-copy {
        flex: 1;
    }

    .fd-score-copy .big {
        font-size: 1.65rem;
        font-weight: 700;
        letter-spacing: -0.03em;
    }

    .fd-score-copy .tiny {
        display: block;
        font-size: 0.78rem;
        color: var(--muted);
        margin-top: 4px;
    }

    .fd-score-bar {
        width: 100%;
        height: 10px;
        background: #edf2f7;
        border-radius: 999px;
        overflow: hidden;
        margin-top: 10px;
    }

    .fd-score-fill {
        height: 100%;
        background: linear-gradient(90deg, #4f8cf7, #4f46e5);
        border-radius: inherit;
    }

    .fd-insights-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 18px;
        margin-bottom: 18px;
    }

    .fd-insight-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-top: 10px;
    }

    .fd-insight-item {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        border-radius: 12px;
        background: #f8fafc;
        border: 1px solid #edf2f7;
        padding: 12px;
    }

    .fd-insight-icon {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        display: grid;
        place-items: center;
        font-size: 0.8rem;
        background: rgba(34, 197, 94, 0.12);
        color: #16a34a;
    }

    .fd-insight-item.warning .fd-insight-icon {
        background: rgba(245, 158, 11, 0.12);
        color: #d97706;
    }

    .fd-insight-item strong {
        display: block;
        font-size: 0.8rem;
        color: var(--text);
    }

    .fd-insight-item span {
        display: block;
        font-size: 0.74rem;
        color: var(--muted);
        margin-top: 3px;
    }

    .fd-table-section {
        background: var(--panel);
        border: 1px solid var(--line);
        border-radius: 14px;
        box-shadow: var(--shadow);
        padding: 18px;
    }

    .fd-table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }

    .fd-table-header h3 {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
    }

    .fd-table-wrap {
        overflow-x: auto;
    }

    table.fd-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 880px;
    }

    .fd-table th,
    .fd-table td {
        padding: 12px 10px;
        border-bottom: 1px solid var(--line);
        text-align: left;
        vertical-align: middle;
    }

    .fd-table th {
        color: var(--muted);
        font-size: 0.75rem;
        letter-spacing: 0.02em;
        font-weight: 700;
        text-transform: uppercase;
        background: #fafcff;
    }

    .fd-table td {
        font-size: 0.8rem;
        color: var(--text);
    }

    .fd-person {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .fd-avatar {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: linear-gradient(135deg, #dde8ff, #f0e7ff);
        display: grid;
        place-items: center;
        color: #475569;
        font-weight: 700;
        font-size: 0.72rem;
        flex-shrink: 0;
    }

    .fd-person strong {
        display: block;
        font-size: 0.8rem;
    }

    .fd-person span {
        display: block;
        font-size: 0.7rem;
        color: var(--muted);
        margin-top: 2px;
    }

    .fd-status {
        display: inline-flex;
        align-items: center;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 600;
    }

    .fd-status.completed { background: rgba(34,197,94,0.12); color: #15803d; }
    .fd-status.in-progress { background: rgba(59,130,246,0.12); color: #1d4ed8; }
    .fd-status.pending { background: rgba(245,158,11,0.12); color: #b45309; }
    .fd-status.overdue { background: rgba(239,68,68,0.12); color: #b91c1c; }

    .fd-score-pill {
        display: inline-block;
        padding: 5px 8px;
        border-radius: 7px;
        background: rgba(245,158,11,0.12);
        color: #9a5b00;
        font-weight: 700;
        font-size: 0.72rem;
    }

    .fd-action-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 32px;
        border-radius: 7px;
        border: 1px solid #d9e6ff;
        background: #f8fbff;
        color: var(--blue);
        font-weight: 600;
        font-size: 0.72rem;
        padding: 0 10px;
        cursor: pointer;
        text-decoration: none;
    }

    .fd-empty {
        text-align: center;
        padding: 40px 16px 18px;
        color: var(--muted);
        font-size: 0.9rem;
    }

    .fd-empty i {
        display: block;
        font-size: 2rem;
        margin-bottom: 8px;
        color: #cbd5e1;
    }

    .fd-modal {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.45);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 4000;
    }

    .fd-modal.visible { display: flex; }

    .fd-modal-panel {
        max-width: 620px;
        width: min(92vw, 620px);
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 28px 70px rgba(15, 23, 42, 0.22);
        border: 1px solid var(--line);
        overflow: hidden;
    }

    .fd-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 18px;
        border-bottom: 1px solid var(--line);
    }

    .fd-modal-header h4 {
        margin: 0;
        font-size: 1rem;
    }

    .fd-close-btn {
        border: none;
        background: transparent;
        color: var(--muted);
        font-size: 1.2rem;
        cursor: pointer;
    }

    .fd-modal-body {
        padding: 18px;
        display: grid;
        gap: 12px;
    }

    .fd-detail-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(180px,1fr));
        gap: 10px 16px;
    }

    .fd-detail-item { }

    .fd-detail-item label {
        display: block;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--muted);
        margin-bottom: 4px;
    }

    .fd-detail-item strong {
        font-size: 0.85rem;
        color: var(--text);
    }

    .fd-detail-block {
        border-top: 1px solid var(--line);
        padding-top: 12px;
    }

    @media (max-width: 980px) {
        .fd-cards { grid-template-columns: repeat(2, minmax(180px,1fr)); }
        .fd-main-grid, .fd-insights-grid { grid-template-columns: 1fr; }
    }

    @media (max-width: 640px) {
        .fd-header { flex-direction: column; align-items: flex-start; }
        .fd-header-actions { width: 100%; }
        .fd-header-actions .fd-select, .fd-header-actions .fd-button { flex: 1; min-width: 0; }
        .fd-cards { grid-template-columns: 1fr; }
        .fd-donut-layout { grid-template-columns: 1fr; }
        .fd-score-box { flex-direction: column; align-items: flex-start; }
        .fd-table-header { flex-direction: column; align-items: flex-start; }
    }
</style>

<div class="feedback-dashboard-root" id="feedback-dashboard-root">
    <div class="fd-shell">
        <div class="fd-header">
            <div>
                <h1>360-Degree Feedback Dashboard</h1>
                <p>Performance insights and employee evaluation status</p>
            </div>
            <div class="fd-header-actions">
                <select class="fd-select" id="cycleFilter" aria-label="Select feedback cycle">
                    <option value="">All cycles</option>
                    <?php foreach ($cycles as $cycle): ?>
                        <?php $cycleOption = trim((string) ($cycle['cycle_period'] ?? $cycle['title'] ?? '')) ?: 'Cycle ' . (int) ($cycle['cycle_id'] ?? 0); ?>
                        <option value="<?= htmlspecialchars($cycleOption) ?>" <?= ($selectedCycle === $cycleOption) ? 'selected' : '' ?>><?= htmlspecialchars((string) ($cycle['title'] ?? $cycleOption)) ?></option>
                    <?php endforeach; ?>
                </select>
                <select class="fd-select" id="raterTypeFilter" aria-label="Select rater type">
                    <option value="all" <?= ($selectedRaterType === 'all' || $selectedRaterType === '') ? 'selected' : '' ?>>All Rater Types</option>
                    <option value="Self" <?= ($selectedRaterType === 'Self') ? 'selected' : '' ?>>Self</option>
                    <option value="Manager" <?= ($selectedRaterType === 'Manager') ? 'selected' : '' ?>>Manager</option>
                    <option value="Peer" <?= ($selectedRaterType === 'Peer') ? 'selected' : '' ?>>Peer</option>
                    <option value="Subordinate" <?= ($selectedRaterType === 'Subordinate') ? 'selected' : '' ?>>Subordinate</option>
                    <option value="Other" <?= ($selectedRaterType === 'Other') ? 'selected' : '' ?>>Other</option>
                </select>
            </div>
        </div>

        <div class="fd-cards">
            <div class="fd-card">
                <div class="fd-card-icon"><i class="fa-solid fa-user-group"></i></div>
                <div class="fd-card-data">
                    <p class="fd-card-number"><?= number_format((int) ($stats['active_cycles'] ?? 0)) ?></p>
                    <span class="fd-card-label">Active Cycles</span>
                    <a href="#" class="fd-card-link" data-action="cycles">View all cycles →</a>
                </div>
            </div>
            <div class="fd-card">
                <div class="fd-card-icon"><i class="fa-solid fa-users"></i></div>
                <div class="fd-card-data">
                    <p class="fd-card-number"><?= number_format((int) ($stats['total_employees'] ?? 0)) ?></p>
                    <span class="fd-card-label">Employees</span>
                    <a href="#" class="fd-card-link" data-action="employees">View employees →</a>
                </div>
            </div>
            <div class="fd-card">
                <div class="fd-card-icon"><i class="fa-solid fa-clipboard-list"></i></div>
                <div class="fd-card-data">
                    <p class="fd-card-number"><?= number_format((int) ($stats['completed_feedback'] ?? 0)) ?></p>
                    <span class="fd-card-label">Completed Feedback</span>
                    <a href="#" class="fd-card-link" data-action="results">View progress →</a>
                </div>
            </div>
            <div class="fd-card">
                <div class="fd-card-icon"><i class="fa-solid fa-star"></i></div>
                <div class="fd-card-data">
                    <p class="fd-card-number"><?= formatScore((float) ($stats['overall_avg_score'] ?? 0)) ?></p>
                    <span class="fd-card-label">Overall Avg. Score</span>
                    <a href="#" class="fd-card-link" data-action="score">View results →</a>
                </div>
            </div>
        </div>

        <div class="fd-main-grid">
            <div class="fd-panel">
                <div class="fd-panel-header">
                    <h2>Feedback Overview</h2>
                </div>
                <?php if ($overviewEmpty): ?>
                    <div class="fd-empty"><i class="fa-solid fa-chart-line"></i>No feedback data available yet.</div>
                <?php else: ?>
                    <div class="fd-radar-wrap">
                        <canvas id="feedbackRadarChart"></canvas>
                    </div>
                    <div class="fd-legend" id="radarLegend"></div>
                <?php endif; ?>

                <div class="fd-score-box">
                    <div class="fd-score-ring"><strong><?= number_format((float) ($overall['average'] ?? 0), 2) ?></strong></div>
                    <div class="fd-score-copy">
                        <div class="big"><?= number_format((float) ($overall['average'] ?? 0), 2) ?> / 5.00</div>
                        <span class="tiny">Score based on completed 360-degree feedback evaluations</span>
                        <div class="fd-score-bar"><div class="fd-score-fill" style="width: <?= min(100, max(0, (float) ($overall['percentage'] ?? 0))) ?>%"></div></div>
                    </div>
                </div>
            </div>

            <div class="fd-panel">
                <div class="fd-panel-header">
                    <h3>Feedback by Rater Type</h3>
                    <select class="fd-select-inline" id="raterTypeFilterSecondary">
                        <option value="all" <?= ($selectedRaterType === 'all' || $selectedRaterType === '') ? 'selected' : '' ?>>All Rater Types</option>
                        <option value="Self" <?= ($selectedRaterType === 'Self') ? 'selected' : '' ?>>Self</option>
                        <option value="Manager" <?= ($selectedRaterType === 'Manager') ? 'selected' : '' ?>>Manager</option>
                        <option value="Peer" <?= ($selectedRaterType === 'Peer') ? 'selected' : '' ?>>Peer</option>
                        <option value="Subordinate" <?= ($selectedRaterType === 'Subordinate') ? 'selected' : '' ?>>Subordinate</option>
                        <option value="Other" <?= ($selectedRaterType === 'Other') ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <?php if ($raterEmpty): ?>
                    <div class="fd-empty"><i class="fa-solid fa-chart-pie"></i>No completed feedback yet.</div>
                <?php else: ?>
                    <div class="fd-donut-layout">
                        <div class="fd-donut-wrap">
                            <canvas id="feedbackDonutChart"></canvas>
                            <div class="fd-donut-total"><div><strong><?= (int) $raterBreakdown['total'] ?></strong><span>Total</span></div></div>
                        </div>
                        <div class="fd-breakdown">
                            <?php foreach ($raterBreakdown['rows'] as $row): ?>
                                <div class="fd-breakdown-row">
                                    <div class="fd-breakdown-label"><span class="fd-dot" style="background: <?= ['Self' => '#2dd4bf', 'Manager' => '#3b82f6', 'Peer' => '#8b5cf6', 'Subordinate' => '#f59e0b', 'Other' => '#10b981'][$row['label']] ?? '#94a3b8'; ?>"></span> <?= htmlspecialchars((string) $row['label']) ?></div>
                                    <div class="fd-count"><?= (int) $row['count'] ?></div>
                                    <div class="fd-percent">(<?= number_format((float) $row['percentage'], 1) ?>%)</div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="fd-main-grid">
            <div class="fd-panel">
                <div class="fd-panel-header">
                    <h2>Feedback Progress</h2>
                    <select class="fd-select-inline" id="cycleFilterProgress">
                        <option value="">All cycles</option>
                        <?php foreach ($cycles as $cycle): ?>
                            <?php $cycleOption = trim((string) ($cycle['cycle_period'] ?? $cycle['title'] ?? '')) ?: 'Cycle ' . (int) ($cycle['cycle_id'] ?? 0); ?>
                            <option value="<?= htmlspecialchars($cycleOption) ?>" <?= ($selectedCycle === $cycleOption) ? 'selected' : '' ?>><?= htmlspecialchars((string) ($cycle['title'] ?? $cycleOption)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($progressEmpty): ?>
                    <div class="fd-empty"><i class="fa-solid fa-spinner"></i>No feedback submissions found.</div>
                <?php else: ?>
                    <div class="fd-progress-list">
                        <?php foreach ($progress['items'] as $item): ?>
                            <?php $color = ['Invited' => '#3b82f6', 'In Progress' => '#f59e0b', 'Completed' => '#22c55e', 'Overdue' => '#ef4444'][$item['label']] ?? '#64748b'; ?>
                            <?php $icon = ['Invited' => 'fa-clipboard-user', 'In Progress' => 'fa-hourglass-half', 'Completed' => 'fa-circle-check', 'Overdue' => 'fa-circle-exclamation'][$item['label']] ?? 'fa-circle'; ?>
                            <div class="fd-progress-item">
                                <div style="width: 100%;">
                                    <div class="fd-progress-meta"><i class="fa-solid <?= $icon ?>" style="color:<?= $color ?>;"></i> <?= htmlspecialchars((string) $item['label']) ?></div>
                                    <div class="fd-progress-bar"><span class="fd-progress-fill" style="width: <?= min(100, max(0, (float) $item['percentage'])) ?>%; background: <?= $color ?>;"></span></div>
                                </div>
                                <div class="fd-progress-score"><?= (int) $item['count'] ?> (<?= number_format((float) $item['percentage'], 1) ?>%)</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="fd-link-row"><button type="button" class="fd-button secondary" data-action="detailed-progress">View Detailed Progress →</button></div>
                <?php endif; ?>
            </div>

            <div class="fd-panel">
                <div class="fd-panel-header">
                    <h3>Strengths & Improvement</h3>
                </div>
                <div class="fd-insights-grid" style="margin-bottom:0;">
                    <div>
                        <h4 style="margin:10px 0 8px; font-size:0.82rem; color:#1f2937;">Strengths</h4>
                        <div class="fd-insight-list">
                            <?php if (empty($competencySummary['strengths'])): ?>
                                <div class="fd-empty" style="padding:20px 10px;">No competency data available.</div>
                            <?php else: ?>
                                <?php foreach ($competencySummary['strengths'] as $item): ?>
                                    <div class="fd-insight-item">
                                        <div class="fd-insight-icon"><i class="fa-solid fa-arrow-up"></i></div>
                                        <div>
                                            <strong><?= htmlspecialchars((string) $item['label']) ?></strong>
                                            <span><?= number_format((float) $item['score'], 2) ?> average score</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <h4 style="margin:10px 0 8px; font-size:0.82rem; color:#1f2937;">Areas for Improvement</h4>
                        <div class="fd-insight-list">
                            <?php if (empty($competencySummary['improvements'])): ?>
                                <div class="fd-empty" style="padding:20px 10px;">No competency data available.</div>
                            <?php else: ?>
                                <?php foreach ($competencySummary['improvements'] as $item): ?>
                                    <div class="fd-insight-item warning">
                                        <div class="fd-insight-icon"><i class="fa-solid fa-arrow-down-long"></i></div>
                                        <div>
                                            <strong><?= htmlspecialchars((string) $item['label']) ?></strong>
                                            <span><?= number_format((float) $item['score'], 2) ?> average score</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="fd-table-section" id="recentFeedbackSection">
            <div class="fd-table-header">
                <h3>Recent Feedback Submissions</h3>
                <button type="button" class="fd-button secondary" data-action="all-feedback">View All Feedback</button>
            </div>
            <?php if ($recentEmpty): ?>
                <div class="fd-empty"><i class="fa-solid fa-list"></i>No feedback submissions found.</div>
            <?php else: ?>
                <div class="fd-table-wrap">
                    <table class="fd-table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Rater</th>
                                <th>Rater Type</th>
                                <th>Submitted On</th>
                                <th>Status</th>
                                <th>Score</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentSubmissions as $submission): ?>
                                <?php $status = strtolower((string) ($submission['status'] ?? 'Pending')); ?>
                                <?php $statusClass = $status === 'completed' ? 'completed' : ($status === 'in progress' ? 'in-progress' : ($status === 'overdue' ? 'overdue' : 'pending')); ?>
                                <tr>
                                    <td>
                                        <div class="fd-person">
                                            <div class="fd-avatar"><?= htmlspecialchars(strtoupper(substr((string) ($submission['employee_name'] ?? 'E'), 0, 2))) ?></div>
                                            <div>
                                                <strong><?= htmlspecialchars((string) ($submission['employee_name'] ?? 'Employee')) ?></strong>
                                                <span><?= htmlspecialchars((string) ($submission['department'] ?? 'Department')) ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fd-person">
                                            <div class="fd-avatar" style="background: linear-gradient(135deg, #e0f2fe, #ede9fe); "><?= htmlspecialchars(strtoupper(substr((string) ($submission['rater_name'] ?? 'R'), 0, 2))) ?></div>
                                            <div>
                                                <strong><?= htmlspecialchars((string) ($submission['rater_name'] ?? 'Rater')) ?></strong>
                                                <span><?= htmlspecialchars((string) ($submission['reviewer_type'] ?? 'Manager')) ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars((string) ($submission['rater_type'] ?? 'Other')) ?></td>
                                    <td><?= htmlspecialchars(date('M j, Y, H:i', strtotime((string) ($submission['submitted_on'] ?? $submission['updated_at'] ?? date('Y-m-d H:i:s'))))) ?></td>
                                    <td><span class="fd-status <?= $statusClass ?>"><?= htmlspecialchars(ucfirst((string) ($submission['status'] ?? 'Pending'))) ?></span></td>
                                    <td><span class="fd-score-pill"><?= number_format((float) ($submission['score'] ?? 0), 2) ?> / 5.00</span></td>
                                    <td><button type="button" class="fd-action-link" data-action="view-details" data-feedback-id="<?= (int) ($submission['feedback_id'] ?? 0) ?>" data-details='<?= htmlspecialchars(json_encode($submission), ENT_QUOTES, 'UTF-8') ?>'>View Details</button></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="feedbackDetailModal" class="fd-modal" aria-hidden="true">
    <div class="fd-modal-panel" role="dialog" aria-modal="true" aria-labelledby="feedbackDetailTitle">
        <div class="fd-modal-header">
            <h4 id="feedbackDetailTitle">Feedback Details</h4>
            <button type="button" class="fd-close-btn" aria-label="Close" data-close-modal>&times;</button>
        </div>
        <div class="fd-modal-body">
            <div class="fd-detail-grid">
                <div class="fd-detail-item"><label>Employee</label><strong id="detailEmployee">-</strong></div>
                <div class="fd-detail-item"><label>Rater</label><strong id="detailRater">-</strong></div>
                <div class="fd-detail-item"><label>Rater Type</label><strong id="detailType">-</strong></div>
                <div class="fd-detail-item"><label>Status</label><strong id="detailStatus">-</strong></div>
                <div class="fd-detail-item"><label>Submitted On</label><strong id="detailDate">-</strong></div>
                <div class="fd-detail-item"><label>Score</label><strong id="detailScore">-</strong></div>
            </div>
            <div class="fd-detail-block">
                <div class="fd-detail-item"><label>Comments</label><strong id="detailComments">-</strong></div>
            </div>
        </div>
    </div>
</div>

<script>
    const dashboardState = {
        cycle: '<?= addslashes((string) $selectedCycle) ?>',
        raterType: '<?= addslashes((string) $selectedRaterType ?: 'all') ?>'
    };

    const radarCtx = document.getElementById('feedbackRadarChart');
    const donutCtx = document.getElementById('feedbackDonutChart');
    let radarChart = null;
    let donutChart = null;

    function renderRadarChart(data) {
        if (!radarCtx) return;
        const labels = data.labels || [];
        const datasets = data.datasets || [];

        if (radarChart) radarChart.destroy();
        radarChart = new Chart(radarCtx, {
            type: 'radar',
            data: { labels, datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 5,
                        ticks: { stepSize: 1, display: true },
                        grid: { color: '#dbe5f0' },
                        angleLines: { color: '#dbe5f0' }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });

        const legend = document.getElementById('radarLegend');
        if (legend) {
            const palette = {
                Self: '#2dd4bf',
                Manager: '#3b82f6',
                Peer: '#8b5cf6',
                Subordinate: '#f59e0b',
                Other: '#10b981'
            };
            legend.innerHTML = (datasets || []).map(item => `
                <span class="fd-legend-item">
                    <span class="fd-dot" style="background:${palette[item.label] || '#94a3b8'}"></span>
                    ${item.label}
                </span>
            `).join('');
        }
    }

    function renderDonutChart(data) {
        if (!donutCtx) return;
        const labels = data.labels || [];
        const values = data.data || [];
        const palette = ['#2dd4bf', '#3b82f6', '#8b5cf6', '#f59e0b', '#10b981'];

        if (donutChart) donutChart.destroy();
        donutChart = new Chart(donutCtx, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data: values,
                    backgroundColor: palette,
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }

    function updateFiltersFromState() {
        const cycleFilter = document.getElementById('cycleFilter');
        const progressCycle = document.getElementById('cycleFilterProgress');
        const raterFilter = document.getElementById('raterTypeFilter');
        const raterSecondary = document.getElementById('raterTypeFilterSecondary');

        if (cycleFilter) cycleFilter.value = dashboardState.cycle || '';
        if (progressCycle) progressCycle.value = dashboardState.cycle || '';
        if (raterFilter) raterFilter.value = dashboardState.raterType || 'all';
        if (raterSecondary) raterSecondary.value = dashboardState.raterType || 'all';
    }

    function applyFilters() {
        const cycle = (document.getElementById('cycleFilter') || document.getElementById('cycleFilterProgress'))?.value || '';
        const raterType = (document.getElementById('raterTypeFilter') || document.getElementById('raterTypeFilterSecondary'))?.value || 'all';

        dashboardState.cycle = cycle;
        dashboardState.raterType = raterType;

        const params = new URLSearchParams();
        params.set('page', '360-degree-feedback');
        params.set('ajax', '1');
        if (cycle) params.set('cycle', cycle);
        if (raterType && raterType !== 'all') params.set('rater_type', raterType);

        fetch(`?${params.toString()}`, { credentials: 'same-origin' })
            .then(response => response.json())
            .then(data => {
                if (data && data.overview) renderRadarChart(data.overview);
                if (data && data.raterBreakdown) renderDonutChart(data.raterBreakdown);
                updateDashboardView(data);
                updateFiltersFromState();
            })
            .catch(() => {
                window.location.reload();
            });
    }

    function updateDashboardView(data) {
        const stats = data.stats || {};
        const overview = data.overview || { datasets: [] };
        const raterBreakdown = data.raterBreakdown || { total: 0, rows: [] };
        const progress = data.progress || { items: [] };
        const overall = data.overall || { average: 0, percentage: 0 };
        const strengths = (data.competencySummary && data.competencySummary.strengths) || [];
        const improvements = (data.competencySummary && data.competencySummary.improvements) || [];
        const submissions = data.recentSubmissions || [];

        document.querySelectorAll('.fd-card-number')[0].textContent = Number(stats.active_cycles || 0).toLocaleString();
        document.querySelectorAll('.fd-card-number')[1].textContent = Number(stats.total_employees || 0).toLocaleString();
        document.querySelectorAll('.fd-card-number')[2].textContent = Number(stats.completed_feedback || 0).toLocaleString();
        document.querySelectorAll('.fd-card-number')[3].textContent = Number(stats.overall_avg_score || 0).toFixed(2);

        if (document.querySelector('.fd-score-ring strong')) {
            document.querySelector('.fd-score-ring strong').textContent = Number(overall.average || 0).toFixed(2);
        }
        const scoreBig = document.querySelector('.fd-score-copy .big');
        if (scoreBig) scoreBig.textContent = `${Number(overall.average || 0).toFixed(2)} / 5.00`;

        const scoreFill = document.querySelector('.fd-score-fill');
        if (scoreFill) scoreFill.style.width = `${Math.min(100, Math.max(0, Number(overall.percentage || 0)))}%`;

        const progressList = document.querySelector('.fd-progress-list');
        if (progressList && progress.items && progress.items.length) {
            progressList.innerHTML = progress.items.map(item => {
                const colors = { 'Invited': '#3b82f6', 'In Progress': '#f59e0b', 'Completed': '#22c55e', 'Overdue': '#ef4444' };
                const icons = { 'Invited': 'fa-clipboard-user', 'In Progress': 'fa-hourglass-half', 'Completed': 'fa-circle-check', 'Overdue': 'fa-circle-exclamation' };
                const color = colors[item.label] || '#64748b';
                const icon = icons[item.label] || 'fa-circle';
                return `
                    <div class="fd-progress-item">
                        <div style="width:100%;">
                            <div class="fd-progress-meta"><i class="fa-solid ${icon}" style="color:${color};"></i> ${item.label}</div>
                            <div class="fd-progress-bar"><span class="fd-progress-fill" style="width:${Math.min(100, Number(item.percentage || 0))}%; background:${color};"></span></div>
                        </div>
                        <div class="fd-progress-score">${Number(item.count || 0)} (${Number(item.percentage || 0).toFixed(1)}%)</div>
                    </div>
                `;
            }).join('');
        }

        const strengthsList = document.querySelectorAll('.fd-insight-item')[0];
        const improvementList = document.querySelectorAll('.fd-insight-item.warning')[0];
        if (strengthsList) {
            const listContainer = strengthsList.closest('.fd-insight-list');
            if (listContainer) {
                listContainer.innerHTML = strengths.length ? strengths.map(item => `
                    <div class="fd-insight-item">
                        <div class="fd-insight-icon"><i class="fa-solid fa-arrow-up"></i></div>
                        <div>
                            <strong>${item.label}</strong>
                            <span>${Number(item.score || 0).toFixed(2)} average score</span>
                        </div>
                    </div>
                `).join('') : '<div class="fd-empty" style="padding:20px 10px;">No competency data available.</div>';
            }
        }
        if (improvementList) {
            const listContainer = improvementList.closest('.fd-insight-list');
            if (listContainer) {
                listContainer.innerHTML = improvements.length ? improvements.map(item => `
                    <div class="fd-insight-item warning">
                        <div class="fd-insight-icon"><i class="fa-solid fa-arrow-down-long"></i></div>
                        <div>
                            <strong>${item.label}</strong>
                            <span>${Number(item.score || 0).toFixed(2)} average score</span>
                        </div>
                    </div>
                `).join('') : '<div class="fd-empty" style="padding:20px 10px;">No competency data available.</div>';
            }
        }

        const tbody = document.querySelector('.fd-table tbody');
        if (tbody && submissions.length) {
            tbody.innerHTML = submissions.map(submission => {
                const status = (submission.status || 'Pending').toLowerCase();
                const statusClass = status === 'completed' ? 'completed' : (status === 'in progress' ? 'in-progress' : (status === 'overdue' ? 'overdue' : 'pending'));
                const initials = ((submission.employee_name || 'Employee').trim().split(/\s+/).slice(0,2).map(part => part[0]).join('') || 'EM').toUpperCase();
                const raterInitials = ((submission.rater_name || 'Rater').trim().split(/\s+/).slice(0,2).map(part => part[0]).join('') || 'RA').toUpperCase();
                const submittedOn = submission.submitted_on ? new Date(submission.submitted_on).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : 'N/A';
                return `
                    <tr>
                        <td>
                            <div class="fd-person">
                                <div class="fd-avatar">${initials}</div>
                                <div><strong>${submission.employee_name || 'Employee'}</strong><span>${submission.department || 'Department'}</span></div>
                            </div>
                        </td>
                        <td>
                            <div class="fd-person">
                                <div class="fd-avatar" style="background: linear-gradient(135deg, #e0f2fe, #ede9fe);">${raterInitials}</div>
                                <div><strong>${submission.rater_name || 'Rater'}</strong><span>${submission.reviewer_type || 'Manager'}</span></div>
                            </div>
                        </td>
                        <td>${submission.rater_type || 'Other'}</td>
                        <td>${submittedOn}</td>
                        <td><span class="fd-status ${statusClass}">${status.charAt(0).toUpperCase() + status.slice(1)}</span></td>
                        <td><span class="fd-score-pill">${Number(submission.score || 0).toFixed(2)} / 5.00</span></td>
                        <td><button type="button" class="fd-action-link" data-action="view-details" data-feedback-id="${submission.feedback_id || ''}" data-details='${JSON.stringify(submission).replace(/'/g, '&apos;')}'>View Details</button></td>
                    </tr>
                `;
            }).join('');
        }
    }

    document.addEventListener('change', function (event) {
        const target = event.target;
        if (target && (target.id === 'cycleFilter' || target.id === 'cycleFilterProgress' || target.id === 'raterTypeFilter' || target.id === 'raterTypeFilterSecondary')) {
            applyFilters();
        }
    });

    document.addEventListener('click', function (event) {
        const actionLink = event.target.closest('[data-action]');
        if (!actionLink) return;

        const action = actionLink.dataset.action;
        if (action === 'cycles') {
            document.getElementById('cycleFilter')?.focus();
            return;
        }
        if (action === 'employees') {
            window.location.href = '?page=dashboard-overview';
            return;
        }
        if (action === 'results' || action === 'score') {
            document.getElementById('recentFeedbackSection')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            return;
        }
        if (action === 'detailed-progress') {
            document.getElementById('recentFeedbackSection')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            return;
        }
        if (action === 'all-feedback') {
            document.getElementById('recentFeedbackSection')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            return;
        }
        if (action === 'view-details') {
            const modal = document.getElementById('feedbackDetailModal');
            const raw = actionLink.dataset.details || '{}';
            const details = JSON.parse(raw.replace(/&quot;/g, '"').replace(/&apos;/g, "'"));
            document.getElementById('detailEmployee').textContent = details.employee_name || 'N/A';
            document.getElementById('detailRater').textContent = details.rater_name || 'N/A';
            document.getElementById('detailType').textContent = details.rater_type || 'Other';
            document.getElementById('detailStatus').textContent = details.status || 'Pending';
            document.getElementById('detailDate').textContent = details.submitted_on ? new Date(details.submitted_on).toLocaleString() : 'N/A';
            document.getElementById('detailScore').textContent = `${Number(details.score || 0).toFixed(2)} / 5.00`;
            document.getElementById('detailComments').textContent = details.comments || 'No comments provided.';
            modal.classList.add('visible');
            modal.setAttribute('aria-hidden', 'false');
        }
    });

    document.addEventListener('click', function (event) {
        const close = event.target.closest('[data-close-modal]');
        if (!close) return;
        const modal = document.getElementById('feedbackDetailModal');
        modal.classList.remove('visible');
        modal.setAttribute('aria-hidden', 'true');
    });

    if (window.Chart) {
        renderRadarChart(<?= json_encode($overview) ?>);
        renderDonutChart(<?= json_encode($raterBreakdown) ?>);
    }
    updateFiltersFromState();
</script>
