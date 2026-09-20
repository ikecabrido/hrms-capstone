<?php

$pageTitle = 'Government Contributions';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
if (!isset($user) || empty($user)) {
    $user = $_SESSION['user'] ?? [];
}
if (!isset($db)) {
    if (class_exists('Database')) {
        $db = (new Database())->getConnection();
    } else {
        require_once __DIR__ . '/../../../database/db.php';
        $db = (new Database())->getConnection();
    }
}

function gc_value(?PDO $db, string $sql, $default = 0) {
  if (!$db instanceof PDO) {
    return $default;
  }
    try {
        $row = $db->query($sql)->fetch(PDO::FETCH_NUM);
        return $row[0] ?? $default;
    } catch (Throwable $e) {
        return $default;
    }
}
function gc_all(?PDO $db, string $sql, array $params = []): array {
  if (!$db instanceof PDO) {
    return [];
  }
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}

$activeWhere = "employment_status NOT IN ('Resigned','Terminated')";
$totalEmployees = (int) gc_value($db, "SELECT COUNT(*) FROM employees WHERE $activeWhere");

$agencies = [
    'sss' => [
        'label' => 'SSS',
        'icon' => 'bi-shield-check',
        'color' => '#3b82c4',
        'table' => 'employees_contributions',
        'type' => 'sss',
        'route' => 'sss-contribution',
    ],
    'philhealth' => [
        'label' => 'PhilHealth',
        'icon' => 'bi-heart-pulse',
        'color' => '#d6484a',
        'table' => 'employees_contributions',
        'type' => 'philhealth',
        'route' => 'philhealth-contributions',
    ],
    'pagibig' => [
        'label' => 'Pag-IBIG',
        'icon' => 'bi-house-heart',
        'color' => '#c9a24a',
        'table' => 'employees_contributions',
        'type' => 'pagibig',
        'route' => 'pagibig_monitoring',
    ],
    'bir' => [
        'label' => 'BIR',
        'icon' => 'bi-receipt',
        'color' => '#2f9e6e',
        'table' => 'bir_contributions',
        'type' => null,
        'route' => 'bir-monitoring',
    ],
];

$agencyStats = [];
$overallSubmitted = 0;
$overallTotal = 0;

foreach ($agencies as $key => $agency) {
    if ($agency['type']) {
        $total = (int) gc_value($db, "SELECT COUNT(*) FROM {$agency['table']} WHERE contribution_type = '{$agency['type']}'");
        $submitted = (int) gc_value($db, "SELECT COUNT(*) FROM {$agency['table']} WHERE contribution_type = '{$agency['type']}' AND status IN ('Submitted','Paid')");
        $pending = (int) gc_value($db, "SELECT COUNT(*) FROM {$agency['table']} WHERE contribution_type = '{$agency['type']}' AND status = 'Pending'");
        $overdue = (int) gc_value($db, "SELECT COUNT(*) FROM {$agency['table']} WHERE contribution_type = '{$agency['type']}' AND status = 'Overdue'");
        $rejected = (int) gc_value($db, "SELECT COUNT(*) FROM {$agency['table']} WHERE contribution_type = '{$agency['type']}' AND status = 'Rejected'");
    } else {
        $total = (int) gc_value($db, "SELECT COUNT(*) FROM {$agency['table']}");
        $submitted = (int) gc_value($db, "SELECT COUNT(*) FROM {$agency['table']} WHERE status IN ('Submitted','Paid')");
        $pending = (int) gc_value($db, "SELECT COUNT(*) FROM {$agency['table']} WHERE status = 'Pending'");
        $overdue = (int) gc_value($db, "SELECT COUNT(*) FROM {$agency['table']} WHERE status = 'Overdue'");
        $rejected = (int) gc_value($db, "SELECT COUNT(*) FROM {$agency['table']} WHERE status = 'Rejected'");
    }

    $compliant = $submitted;
    $forReview = $pending + $overdue + $rejected;
    $pct = $total > 0 ? (int) round(($submitted / $total) * 100) : 0;

    $agencyStats[$key] = [
        'label' => $agency['label'],
        'icon' => $agency['icon'],
        'color' => $agency['color'],
        'route' => $agency['route'],
        'total' => $total,
        'submitted' => $submitted,
        'pending' => $pending,
        'overdue' => $overdue,
        'rejected' => $rejected,
        'compliant' => $compliant,
        'for_review' => $forReview,
        'pct' => $pct,
    ];

    $overallSubmitted += $submitted;
    $overallTotal += $total;
}

$overallPct = $overallTotal > 0 ? (int) round(($overallSubmitted / $overallTotal) * 100) : 0;

$recentQuery = "
    SELECT c.*, e.first_name, e.last_name, e.employee_no
    FROM (
        SELECT id, employee_id, contribution_number, status, created_at, updated_at, 'SSS' AS agency FROM employees_contributions WHERE contribution_type = 'sss'
        UNION ALL
        SELECT id, employee_id, contribution_number, status, created_at, updated_at, 'PhilHealth' AS agency FROM employees_contributions WHERE contribution_type = 'philhealth'
        UNION ALL
        SELECT id, employee_id, contribution_number, status, created_at, updated_at, 'Pag-IBIG' AS agency FROM employees_contributions WHERE contribution_type = 'pagibig'
        UNION ALL
        SELECT id, employee_id, contribution_number, status, created_at, updated_at, 'BIR' AS agency FROM bir_contributions
    ) c
    LEFT JOIN employees e ON e.employee_id = c.employee_id
    ORDER BY c.created_at DESC
    LIMIT 20
";
$recent = gc_all($db, $recentQuery);
?>

<style>
.gc-module { padding: 4px 2px 24px; }
.gc-kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:18px; }
@media (max-width:1100px){ .gc-kpi-grid { grid-template-columns:repeat(2,1fr);} }
@media (max-width:640px){ .gc-kpi-grid { grid-template-columns:1fr;} }

.gc-kpi { display:flex; gap:14px; align-items:center; padding:18px; border-radius:14px; background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); text-decoration:none; color:inherit; transition:all .15s ease; }
.gc-kpi:hover { transform:translateY(-2px); box-shadow:var(--shadow-soft,0 4px 12px rgba(13,27,46,.08)); border-color:var(--info-blue,#3b82c4); }
.gc-kpi .icon-wrap { width:46px; height:46px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0; }
.gc-kpi-blue .icon-wrap { background:rgba(59,130,196,.12); color:#1c5a8a; }
.gc-kpi-red .icon-wrap { background:rgba(214,72,74,.12); color:#a3272a; }
.gc-kpi-amber .icon-wrap { background:rgba(217,154,43,.14); color:#a86b13; }
.gc-kpi-green .icon-wrap { background:rgba(47,158,110,.12); color:#1f7a52; }
.gc-kpi-body { display:flex; flex-direction:column; min-width:0; }
.gc-kpi-value { font-size:1.6rem; font-weight:800; color:var(--text-900,#1b2430); line-height:1; }
.gc-kpi-label { font-size:0.8rem; font-weight:600; color:var(--text-700,#3b4252); margin-top:4px; }
.gc-kpi-sub { font-size:0.72rem; color:var(--text-400,#8b93a1); margin-top:3px; }

.gc-grid-2 { display:grid; grid-template-columns:1.7fr 1fr; gap:18px; align-items:start; }
@media (max-width:980px){ .gc-grid-2 { grid-template-columns:1fr; } }
.gc-card { background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); border-radius:14px; padding:18px; margin-bottom:16px; }
.gc-card-head { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:14px; flex-wrap:wrap; }
.gc-card-head h3 { margin:0; font-size:0.98rem; font-weight:700; color:var(--text-900,#1b2430); display:flex; align-items:center; gap:8px; }
.gc-empty { padding:24px; text-align:center; color:var(--text-400,#8b93a1); font-size:0.84rem; }

.gc-table-wrap { overflow-x:auto; }
.gc-table { width:100%; border-collapse:collapse; font-size:0.8rem; }
.gc-table th { text-align:left; font-size:0.7rem; text-transform:uppercase; letter-spacing:.03em; color:var(--text-400,#8b93a1); padding:8px 10px; border-bottom:1px solid var(--border,#e4e8ee); }
.gc-table td { padding:10px; border-bottom:1px solid var(--border,#e4e8ee); vertical-align:top; }
.gc-table tr:last-child td { border-bottom:none; }

.gc-stamp { font-size:0.66rem; font-weight:700; padding:2px 9px; border-radius:999px; white-space:nowrap; }
.gc-stamp-compliant { background:rgba(47,158,110,.12); color:#1f7a52; }
.gc-stamp-pending { background:rgba(217,154,43,.14); color:#a86b13; }
.gc-stamp-overdue { background:rgba(214,72,74,.14); color:#a3272a; }
.gc-stamp-rejected { background:rgba(107,114,128,.12); color:#374151; }

.gc-agency-row { display:flex; flex-direction:column; gap:6px; }
.gc-agency-top { display:flex; align-items:center; justify-content:space-between; }
.gc-agency-name { font-weight:600; font-size:0.84rem; color:var(--text-900,#1b2430); display:flex; align-items:center; gap:8px; }
.gc-agency-cnt { font-weight:800; font-size:0.78rem; color:var(--info-blue,#3b82c4); }
.gc-bar { height:7px; border-radius:999px; background:var(--bg-soft,#f3f5f9); overflow:hidden; }
.gc-bar-fill { display:block; height:100%; border-radius:999px; background:#2f9e6e; transition:width .6s ease; }
.gc-agency-meta { font-size:0.68rem; color:var(--text-400,#8b93a1); }

.gc-quick-links { display:flex; flex-direction:column; gap:10px; }
.gc-quick-link { display:flex; align-items:center; gap:10px; padding:12px 14px; border-radius:10px; background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); text-decoration:none; color:inherit; transition:all .15s ease; }
.gc-quick-link:hover { border-color:var(--info-blue,#3b82c4); box-shadow:var(--shadow-soft,0 2px 8px rgba(13,27,46,.06)); }
.gc-quick-link .ql-icon { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; }
.gc-quick-link .ql-info { flex:1; min-width:0; }
.gc-quick-link .ql-label { font-size:0.82rem; font-weight:700; color:var(--text-900,#1b2430); }
.gc-quick-link .ql-desc { font-size:0.72rem; color:var(--text-400,#8b93a1); margin-top:2px; }
.gc-quick-link .ql-arrow { font-size:1.1rem; color:var(--text-400,#8b93a1); }

@media (max-width: 980px) {
  .gc-grid-2 { grid-template-columns:1fr; }
}
</style>

<section class="gc-module">

  <div class="gc-kpi-grid">
    <?php foreach ($agencies as $key => $agency):
      $stat = $agencyStats[$key];
    ?>
    <a href="?page=<?= htmlspecialchars($stat['route']) ?>" class="gc-kpi gc-kpi-<?= in_array($key, ['bir']) ? 'green' : (in_array($key, ['sss']) ? 'blue' : (in_array($key, ['philhealth']) ? 'red' : 'amber')) ?>">
      <div class="icon-wrap"><i class="bi <?= htmlspecialchars($agency['icon']) ?>"></i></div>
      <div class="gc-kpi-body">
        <div class="gc-kpi-value"><?= number_format($stat['pct']) ?>%</div>
        <div class="gc-kpi-label"><?= htmlspecialchars($agency['label']) ?> Compliance</div>
        <div class="gc-kpi-sub"><?= number_format($stat['submitted']) ?>/<?= number_format($stat['total']) ?> submitted</div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>

  <div class="gc-grid-2">
    <div class="gc-card">
      <div class="gc-card-head">
        <h3><i class="bi bi-card-checklist"></i> Recent Contributions</h3>
      </div>
      <div class="gc-table-wrap">
        <table class="gc-table">
          <thead>
            <tr>
              <th>Agency</th>
              <th>Employee</th>
              <th>Reference</th>
              <th>Status</th>
              <th>Updated</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($recent)): ?>
              <tr><td colspan="5"><div class="gc-empty">No contribution records found.</div></td></tr>
            <?php else: ?>
              <?php foreach ($recent as $r):
                $status = strtolower($r['status'] ?? 'pending');
                if (in_array($status, ['submitted', 'paid'])) $stampCls = 'compliant';
                elseif ($status === 'overdue') $stampCls = 'overdue';
                elseif ($status === 'rejected') $stampCls = 'rejected';
                else $stampCls = 'pending';
                $empName = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
                if ($empName === '') $empName = '—';
              ?>
              <tr>
                <td><?= htmlspecialchars($r['agency'] ?? '—') ?></td>
                <td><?= htmlspecialchars($empName) ?></td>
                <td><?= !empty($r['contribution_number']) ? htmlspecialchars($r['contribution_number']) : '—' ?></td>
                <td><span class="gc-stamp gc-stamp-<?= $stampCls ?>"><?= htmlspecialchars(ucfirst($r['status'] ?? 'Pending')) ?></span></td>
                <td><?= !empty($r['updated_at']) ? date('M d, Y', strtotime($r['updated_at'])) : '—' ?></td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="gc-side">
      <div class="gc-card">
        <div class="gc-card-head">
          <h3><i class="bi bi-bank"></i> Coverage by Agency</h3>
        </div>
        <div class="gc-agency-list">
          <?php foreach ($agencies as $key => $agency):
            $stat = $agencyStats[$key];
            $pct = $stat['pct'];
          ?>
            <div class="gc-agency-row">
              <div class="gc-agency-top">
                <span class="gc-agency-name"><i class="bi <?= htmlspecialchars($agency['icon']) ?>"></i> <?= htmlspecialchars($agency['label']) ?></span>
                <span class="gc-agency-cnt"><?= number_format($stat['submitted']) ?>/<?= number_format($stat['total']) ?></span>
              </div>
              <div class="gc-bar"><span class="gc-bar-fill" style="width:<?= $pct ?>%"></span></div>
              <div class="gc-agency-meta"><?= $pct ?>% compliant<?php if ($stat['for_review'] > 0): ?> · <?= number_format($stat['for_review']) ?> for review<?php endif; ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="gc-card">
        <div class="gc-card-head">
          <h3><i class="bi bi-diagram-3"></i> Quick Access</h3>
        </div>
        <div class="gc-quick-links">
          <?php foreach ($agencies as $key => $agency):
            $stat = $agencyStats[$key];
          ?>
            <a href="?page=<?= htmlspecialchars($stat['route']) ?>" class="gc-quick-link">
              <div class="ql-icon" style="background:<?= htmlspecialchars($agency['color']) ?>15; color:<?= htmlspecialchars($agency['color']) ?>;"><i class="bi <?= htmlspecialchars($agency['icon']) ?>"></i></div>
              <div class="ql-info">
                <div class="ql-label"><?= htmlspecialchars($agency['label']) ?> Monitoring</div>
                <div class="ql-desc"><?= number_format($stat['total']) ?> records · <?= number_format($stat['pct']) ?>% compliant</div>
              </div>
              <i class="bi bi-arrow-right-short ql-arrow"></i>
            </a>
          <?php endforeach; ?>
          <a href="?page=government-contribution-brackets" class="gc-quick-link sss-bracket-link">
            <div class="ql-icon" style="background:rgba(59,130,196,.12); color:#1c5a8a;"><i class="bi bi-percent"></i></div>
            <div class="ql-info">
              <div class="ql-label">SSS Contribution Table</div>
              <div class="ql-desc">View contribution brackets and rates</div>
            </div>
            <i class="bi bi-arrow-right-short ql-arrow"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
</section>
