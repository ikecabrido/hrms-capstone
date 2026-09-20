<?php

require_once __DIR__ . '/../../../database/db.php';

$pageTitle = 'Incident Management';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
if (!isset($user) || empty($user)) {
    $user = $_SESSION['user'] ?? [];
}

$db = new PDO('mysql:host=localhost;dbname=hrms;charset=utf8mb4', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$flash = '';
if (isset($_GET['msg'])) {
    $raw = (string) $_GET['msg'];
    if (strpos($raw, '?msg=') !== false) {
        $parts = explode('?msg=', $raw);
        $raw = end($parts);
    }
    $flash = htmlspecialchars($raw, ENT_QUOTES);
}

function ir_value(PDO $db, string $sql, $default = 0) {
    try {
        $row = $db->query($sql)->fetch(PDO::FETCH_NUM);
        return $row[0] ?? $default;
    } catch (Throwable $e) {
        return $default;
    }
}
function ir_all(PDO $db, string $sql, array $params = []): array {
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}

$totalIncidents = (int) ir_value($db, "SELECT COUNT(*) FROM lc_incident_report WHERE incident_type IN ('Workplace Accident','Health Incident','Medical Emergency','Occupational Injury','Environmental & Safety Hazard','Exposure Incident','Near Miss','Return-to-Work Monitoring')", 0);
$openCases     = (int) ir_value($db, "SELECT COUNT(*) FROM lc_incident_report WHERE status NOT IN ('closed','resolved') AND incident_type IN ('Workplace Accident','Health Incident','Medical Emergency','Occupational Injury','Environmental & Safety Hazard','Exposure Incident','Near Miss','Return-to-Work Monitoring')", 0);
$underInvestigation = (int) ir_value($db, "SELECT COUNT(*) FROM lc_incident_report WHERE status = 'investigation' AND incident_type IN ('Workplace Accident','Health Incident','Medical Emergency','Occupational Injury','Environmental & Safety Hazard','Exposure Incident','Near Miss','Return-to-Work Monitoring')", 0);
$pendingCapa   = (int) ir_value($db, "SELECT COUNT(*) FROM lc_incident_report WHERE status IN ('escalated','investigation') AND incident_type IN ('Workplace Accident','Health Incident','Medical Emergency','Occupational Injury','Environmental & Safety Hazard','Exposure Incident','Near Miss','Return-to-Work Monitoring')", 0);
$closedCases   = (int) ir_value($db, "SELECT COUNT(*) FROM lc_incident_report WHERE status = 'closed' AND incident_type IN ('Workplace Accident','Health Incident','Medical Emergency','Occupational Injury','Environmental & Safety Hazard','Exposure Incident','Near Miss','Return-to-Work Monitoring')", 0);
$criticalCount = (int) ir_value($db, "SELECT COUNT(*) FROM lc_incident_report WHERE severity IN ('critical','high') AND incident_type IN ('Workplace Accident','Health Incident','Medical Emergency','Occupational Injury','Environmental & Safety Hazard','Exposure Incident','Near Miss','Return-to-Work Monitoring')", 0);

$validCategories = ['All', 'Workplace Accident', 'Health Incident', 'Medical Emergency', 'Occupational Injury', 'Environmental & Safety Hazard', 'Exposure Incident', 'Near Miss', 'Return-to-Work Monitoring'];
$filterCategory = in_array($_GET['category'] ?? '', $validCategories, true) ? $_GET['category'] : 'All';

$validStatuses = ['All', 'submitted', 'under_review', 'investigation', 'escalated', 'resolved', 'closed'];
$filterStatus = in_array($_GET['status'] ?? '', $validStatuses, true) ? $_GET['status'] : 'All';

$validSeverities = ['All', 'low', 'medium', 'high', 'critical'];
$filterSeverity = in_array($_GET['severity'] ?? '', $validSeverities, true) ? $_GET['severity'] : 'All';

$validTypes = ['All', 'Slip or Fall', 'Trip Incident', 'Stairway Accident', 'Office Injury', 'Laboratory Injury', 'Workshop Injury', 'Sports Facility Accident', 'Parking Area Accident', 'Falling Object Injury', 'Minor Equipment Injury', 'Fever', 'Headache', 'Dizziness', 'Hypertension', 'Asthma Attack', 'Allergic Reaction', 'Stomach Pain', 'Heat Exhaustion', 'Food Poisoning', 'Fatigue', 'Anxiety/Panic Episode', 'Infectious Disease Symptoms', 'Loss of Consciousness', 'Seizure', 'Severe Allergic Reaction', 'Cardiac-Related Emergency', 'Breathing Difficulty', 'Stroke Symptoms', 'Severe Bleeding', 'Fracture', 'Emergency Hospital Referral', 'Minor Cut', 'Laceration', 'Sprain', 'Muscle Strain', 'Back Injury', 'Hand Injury', 'Finger Injury', 'Eye Irritation', 'Burn (Minor)', 'Bruise', 'Wet Floor', 'Broken Stairs', 'Damaged Flooring', 'Poor Lighting', 'Electrical Hazard', 'Fire Safety Concern', 'Chemical Spill (Laboratory)', 'Blocked Emergency Exit', 'Unsafe Classroom Condition', 'Unsafe Office Condition', 'Blood Exposure', 'Chemical Exposure', 'Biological Exposure', 'Smoke Inhalation', 'Infectious Disease Exposure', 'Slip Without Injury', 'Falling Object (No Injury)', 'Laboratory Near Miss', 'Equipment Malfunction', 'Electrical Near Miss', 'Vehicle Near Miss', 'Fire Near Miss', 'Medical Clearance', 'Fit-to-Work Clearance', 'Return After Medical Leave', 'Return After Workplace Injury', 'Work Restrictions'];
$filterType = in_array($_GET['type'] ?? '', $validTypes, true) ? $_GET['type'] : 'All';

$searchQuery = trim($_GET['search'] ?? '');
$dateFrom = $_GET['date_from'] ?? '';
$dateTo   = $_GET['date_to'] ?? '';

$em_departments = ir_all($db, "SELECT id, department_name FROM em_departments ORDER BY department_name ASC");
$filterDept = $_GET['department'] ?? '';

$officers = ir_all($db, "SELECT employee_id, full_name FROM em_employees WHERE employee_id IN (SELECT assigned_to FROM lc_incident_report WHERE assigned_to IS NOT NULL) ORDER BY full_name ASC");
$filterOfficer = $_GET['officer'] ?? '';

$where = [];
$params = [];

$allowedClinicCategories = ['Workplace Accident', 'Health Incident', 'Medical Emergency', 'Occupational Injury', 'Environmental & Safety Hazard', 'Exposure Incident', 'Near Miss', 'Return-to-Work Monitoring'];

if ($filterCategory === 'All') {
    $where[] = 'i.incident_type IN (:cat0, :cat1, :cat2, :cat3, :cat4, :cat5, :cat6, :cat7)';
    $params[':cat0'] = 'Workplace Accident';
    $params[':cat1'] = 'Health Incident';
    $params[':cat2'] = 'Medical Emergency';
    $params[':cat3'] = 'Occupational Injury';
    $params[':cat4'] = 'Environmental & Safety Hazard';
    $params[':cat5'] = 'Exposure Incident';
    $params[':cat6'] = 'Near Miss';
    $params[':cat7'] = 'Return-to-Work Monitoring';
} else {
    $where[] = 'i.incident_type = :category';
    $params[':category'] = $filterCategory;
}
if ($filterStatus !== 'All') {
    $where[] = 'i.status = :status';
    $params[':status'] = $filterStatus;
}
if ($filterSeverity !== 'All') {
    $where[] = 'i.severity = :severity';
    $params[':severity'] = $filterSeverity;
}
if ($filterType !== 'All') {
    $where[] = 'i.type = :type';
    $params[':type'] = $filterType;
}
if ($filterDept !== '') {
    $where[] = 'i.reporter_department = :department';
    $params[':department'] = $filterDept;
}
if ($filterOfficer !== '') {
    $where[] = 'i.assigned_to = :officer';
    $params[':officer'] = (int) $filterOfficer;
}
if ($dateFrom !== '') {
    $where[] = 'i.incident_date >= :date_from';
    $params[':date_from'] = $dateFrom;
}
if ($dateTo !== '') {
    $where[] = 'i.incident_date <= :date_to';
    $params[':date_to'] = $dateTo;
}
if ($searchQuery !== '') {
    $where[] = "(i.title LIKE :search OR i.description LIKE :search OR i.incident_id LIKE :search OR i.reporter_name LIKE :search OR i.reporter_department LIKE :search)";
    $params[':search'] = '%' . $searchQuery . '%';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$cases = ir_all($db, "
    SELECT i.id, i.incident_id, i.incident_type, i.type, i.severity, i.status,
           i.incident_date, i.incident_time, i.location, i.title, i.description,
           COALESCE(i.reporter_name, 'Unassigned') AS reporter_name,
           i.reporter_department,
           i.assigned_to,
           COALESCE(i.assigned_name, 'Unassigned') AS assigned_name,
           i.created_at
    FROM lc_incident_report i
    $whereSql
    ORDER BY
      CASE i.severity WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END,
      i.incident_date DESC,
      i.id DESC
", $params);

$totalCases = count($cases);

function ir_status_class(string $s): string {
    $s = strtolower($s);
    if (in_array($s, ['closed', 'resolved'], true)) return 'compliant';
    if (in_array($s, ['under_review', 'investigation'], true)) return 'info';
    return 'pending';
}
function ir_severity_class(string $s): string {
    $s = strtolower($s);
    if (in_array($s, ['critical', 'high'], true)) return 'high';
    if ($s === 'medium') return 'med';
    return 'low';
}
function ir_status_label(string $s): string {
    $map = [
        'submitted'     => 'Received',
        'under_review'  => 'Under Review',
        'investigation' => 'Investigation',
        'escalated'     => 'Corrective Action',
        'resolved'      => 'Compliance Verification',
        'closed'        => 'Closed',
    ];
    return $map[strtolower($s)] ?? ucfirst($s);
}
function ir_category_icon(string $cat): string {
    $cat = strtolower($cat);
    if (str_contains($cat, 'workplace accident') || str_contains($cat, 'occupational')) return 'bi bi-briefcase';
    if (str_contains($cat, 'health incident')) return 'bi bi-heart-pulse';
    if (str_contains($cat, 'medical emergency')) return 'bi bi-hospital';
    if (str_contains($cat, 'environmental') || str_contains($cat, 'safety hazard')) return 'bi bi-tree';
    if (str_contains($cat, 'exposure')) return 'bi bi-droplet';
    if (str_contains($cat, 'near miss')) return 'bi bi-lightning';
    if (str_contains($cat, 'return-to-work') || str_contains($cat, 'return to work')) return 'bi bi-arrow-return-left';
    return 'bi bi-folder2-open';
}

$typeMap = [
    'Workplace Accident' => ['Slip or Fall', 'Trip Incident', 'Stairway Accident', 'Office Injury', 'Laboratory Injury', 'Workshop Injury', 'Sports Facility Accident', 'Parking Area Accident', 'Falling Object Injury', 'Minor Equipment Injury'],
    'Health Incident' => ['Fever', 'Headache', 'Dizziness', 'Hypertension', 'Asthma Attack', 'Allergic Reaction', 'Stomach Pain', 'Heat Exhaustion', 'Food Poisoning', 'Fatigue', 'Anxiety/Panic Episode', 'Infectious Disease Symptoms'],
    'Medical Emergency' => ['Loss of Consciousness', 'Seizure', 'Severe Allergic Reaction', 'Cardiac-Related Emergency', 'Breathing Difficulty', 'Stroke Symptoms', 'Severe Bleeding', 'Fracture', 'Emergency Hospital Referral'],
    'Occupational Injury' => ['Minor Cut', 'Laceration', 'Sprain', 'Muscle Strain', 'Back Injury', 'Hand Injury', 'Finger Injury', 'Eye Irritation', 'Burn (Minor)', 'Bruise'],
    'Environmental & Safety Hazard' => ['Wet Floor', 'Broken Stairs', 'Damaged Flooring', 'Poor Lighting', 'Electrical Hazard', 'Fire Safety Concern', 'Chemical Spill (Laboratory)', 'Blocked Emergency Exit', 'Unsafe Classroom Condition', 'Unsafe Office Condition'],
    'Exposure Incident' => ['Blood Exposure', 'Chemical Exposure', 'Biological Exposure', 'Smoke Inhalation', 'Infectious Disease Exposure'],
    'Near Miss' => ['Slip Without Injury', 'Falling Object (No Injury)', 'Laboratory Near Miss', 'Equipment Malfunction', 'Electrical Near Miss', 'Vehicle Near Miss', 'Fire Near Miss'],
    'Return-to-Work Monitoring' => ['Medical Clearance', 'Fit-to-Work Clearance', 'Return After Medical Leave', 'Return After Workplace Injury', 'Work Restrictions'],
];
$typesForCategory = $typeMap[$filterCategory] ?? [];

$baseUrl = '?page=incident-reports';
?>
<section class="ir-module">
   <?php if (!empty($flash)): ?>
      <?php [$fc, $fm] = explode('|', $flash, 2); ?>
      <div class="ir-flash <?= htmlspecialchars($fc) ?>"><?= htmlspecialchars($fm) ?></div>
   <?php endif; ?>

   <div class="ir-summary-bar">
      <a class="ir-summary-item <?= $filterStatus === 'All' && $filterCategory === 'All' && $filterSeverity === 'All' && $filterType === 'All' && $filterDept === '' && $filterOfficer === '' && $dateFrom === '' && $dateTo === '' && $searchQuery === '' ? 'ir-summary-active' : '' ?>" href="<?= htmlspecialchars($baseUrl) ?>">
        <div class="ir-summary-icon amber"><i class="bi bi-journal-text"></i></div>
        <div>
          <div class="ir-summary-value"><?= number_format($totalIncidents) ?></div>
          <div class="ir-summary-label">Total Incidents</div>
        </div>
      </a>
      <a class="ir-summary-item <?= in_array($filterStatus, ['submitted', 'under_review', 'investigation', 'escalated'], true) && $filterCategory === 'All' && $filterSeverity === 'All' ? 'ir-summary-active' : '' ?>" href="<?= htmlspecialchars($baseUrl) ?>&status=under_review&category=All&severity=All&type=All&department=&officer=&date_from=&date_to=&search=">
        <div class="ir-summary-icon blue"><i class="bi bi-folder"></i></div>
        <div>
          <div class="ir-summary-value"><?= number_format($openCases) ?></div>
          <div class="ir-summary-label">Open Cases</div>
        </div>
      </a>
      <a class="ir-summary-item <?= $filterStatus === 'investigation' && $filterCategory === 'All' && $filterSeverity === 'All' ? 'ir-summary-active' : '' ?>" href="<?= htmlspecialchars($baseUrl) ?>&status=investigation&category=All&severity=All&type=All&department=&officer=&date_from=&date_to=&search=">
        <div class="ir-summary-icon" style="background:rgba(124,58,237,.10);color:#5b21b6;"><i class="bi bi-binoculars"></i></div>
        <div>
          <div class="ir-summary-value"><?= number_format($underInvestigation) ?></div>
          <div class="ir-summary-label">Under Investigation</div>
        </div>
      </a>
      <a class="ir-summary-item <?= $filterStatus === 'escalated' && $filterCategory === 'All' && $filterSeverity === 'All' ? 'ir-summary-active' : '' ?>" href="<?= htmlspecialchars($baseUrl) ?>&status=escalated&category=All&severity=All&type=All&department=&officer=&date_from=&date_to=&search=">
        <div class="ir-summary-icon red"><i class="bi bi-exclamation-octagon"></i></div>
        <div>
          <div class="ir-summary-value"><?= number_format($pendingCapa) ?></div>
          <div class="ir-summary-label">Pending CAPA</div>
        </div>
      </a>
      <a class="ir-summary-item <?= $filterStatus === 'closed' && $filterCategory === 'All' && $filterSeverity === 'All' ? 'ir-summary-active' : '' ?>" href="<?= htmlspecialchars($baseUrl) ?>&status=closed&category=All&severity=All&type=All&department=&officer=&date_from=&date_to=&search=">
        <div class="ir-summary-icon green"><i class="bi bi-check2-all"></i></div>
        <div>
          <div class="ir-summary-value"><?= number_format($closedCases) ?></div>
          <div class="ir-summary-label">Closed Cases</div>
        </div>
      </a>
    </div>

   <div class="ir-row">
      <div class="ir-col ir-col-main">
         <div class="ir-card">
           <div class="ir-card-head">
              <h3><i class="bi bi-journal-check"></i> Incident Management</h3>
           </div>
           <div class="ir-card-body">
            <?php if (empty($cases)): ?>
              <div class="ir-empty"><i class="bi bi-emoji-smile"></i> No incident records match the current filters.</div>
            <?php else: ?>
            <div class="ir-table-wrap">
              <table class="ir-table">
                 <thead>
                   <tr>
                     <th class="ir-id-cell">Incident ID</th>
                     <th class="ir-emp-cell">Employee</th>
                     <th>Category</th>
                     <th>Status</th>
                     <th>Severity</th>
                     <th class="ir-action-cell" style="text-align:right;">Actions</th>
                   </tr>
                 </thead>
                 <tbody>
                   <?php foreach ($cases as $c):
                     $sevClass = ir_severity_class($c['severity']);
                     $statusClass = ir_status_class($c['status']);
                     $catIcon = ir_category_icon($c['incident_type']);
                   ?>
                    <tr data-rid="<?= (int)$c['id'] ?>" style="cursor:pointer;">
                      <td class="ir-id-cell" data-label="Incident ID">
                        <div class="ir-cnum"><?= htmlspecialchars($c['incident_id'], ENT_QUOTES) ?></div>
                        <div class="ir-emp-no"><?= !empty($c['incident_date']) ? date('M d, Y', strtotime($c['incident_date'])) : '—' ?></div>
                      </td>
                      <td class="ir-emp-cell" data-label="Employee">
                        <div class="ir-emp-name"><?= htmlspecialchars($c['reporter_name'], ENT_QUOTES) ?></div>
                        <div class="ir-emp-no"><?= htmlspecialchars($c['reporter_department'] ?? '—', ENT_QUOTES) ?></div>
                      </td>
                      <td data-label="Category">
                        <span class="ir-type-badge" style="background:rgba(168,121,31,.1);color:#8a6318;border:1px solid rgba(168,121,31,.2);">
                          <i class="bi <?= $catIcon ?>"></i> <?= htmlspecialchars($c['incident_type'] ?? 'Other', ENT_QUOTES) ?>
                        </span>
                      </td>
                      <td data-label="Status">
                        <span class="ir-status-stamp ir-status-stamp--<?= $statusClass ?>"><?= htmlspecialchars(ir_status_label($c['status']), ENT_QUOTES) ?></span>
                      </td>
                      <td data-label="Severity">
                        <span class="ir-severity-pill ir-severity-pill--<?= $sevClass ?>">
                          <span class="ir-severity-dot ir-severity-dot--<?= strtolower($c['severity']) ?>"></span>
                          <?= htmlspecialchars(ucfirst($c['severity']), ENT_QUOTES) ?>
                        </span>
                      </td>
                      <td class="ir-action-cell" data-label="Actions" style="text-align:right;">
                        <button type="button" class="ir-btn ir-btn-ghost ir-btn-xs" onclick="irOpenDetail(<?= (int)$c['id'] ?>)">
                          <i class="bi bi-eye"></i> View
                        </button>
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

       <div class="ir-col ir-col-side">

         <div class="ir-card">
           <div class="ir-card-head">
             <h3><i class="bi bi-bell"></i> Urgent Actions</h3>
             <span class="ir-stamp ir-stamp-overdue" style="font-size:.66rem;font-weight:700;padding:2px 9px;border-radius:999px;white-space:nowrap;"><?= number_format($criticalCount) ?></span>
           </div>
           <div class="ir-reminder-list ir-reminder-list--compact">
            <?php if ($criticalCount > 0): ?>
              <?php
                $criticalCases = array_slice(array_filter($cases, function($c) {
                  return in_array(strtolower($c['severity']), ['critical', 'high'], true);
                }), 0, 5);
              ?>
              <?php foreach ($criticalCases as $c): ?>
                <div class="ir-reminder-row">
                  <div class="ir-reminder-text">
                    <strong><?= htmlspecialchars(ir_status_label($c['status']), ENT_QUOTES) ?></strong>
                    <span><?= htmlspecialchars($c['reporter_name'], ENT_QUOTES) ?> — <?= htmlspecialchars($c['incident_id'], ENT_QUOTES) ?></span>
                    <span class="ir-reminder-step"><?= htmlspecialchars($c['incident_type'] ?? 'Other', ENT_QUOTES) ?></span>
                  </div>
                  <div class="ir-reminder-actions">
                    <button type="button" class="ir-btn ir-btn-ghost ir-btn-xs" onclick="irOpenDetail(<?= (int)$c['id'] ?>)">
                      <i class="bi bi-eye"></i> View
                    </button>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="ir-empty"><i class="bi bi-emoji-smile"></i> No urgent actions required.</div>
             <?php endif; ?>
           </div>
         </div>
       </div>
     </div>
  </section>


 <style>
 .ir-module { padding: 4px 2px 24px; }

.ir-summary-bar { display:flex; gap:14px; margin-bottom:16px; flex-wrap:wrap; }
.ir-summary-item { display:flex; align-items:center; gap:14px; padding:16px 20px; border-radius:14px; background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); flex:1; min-width:180px; text-decoration:none; color:inherit; transition:all .15s ease; cursor:pointer; }
.ir-summary-item:hover { transform:translateY(-2px); box-shadow:var(--shadow-soft,0 4px 12px rgba(13,27,46,.08)); border-color:var(--info-blue,#3b82c4); }
.ir-summary-active { outline:2px solid var(--info-blue,#3b82c4); outline-offset:-2px; box-shadow:0 0 0 3px rgba(59,130,196,.15) !important; }
.ir-summary-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0; }
.ir-summary-icon.green { background:rgba(47,158,110,.12); color:#1f7a52; }
.ir-summary-icon.blue { background:rgba(59,130,196,.12); color:#1c5a8a; }
.ir-summary-icon.amber { background:rgba(217,154,43,.14); color:#a86b13; }
.ir-summary-icon.red { background:rgba(214,72,74,.12); color:#a3272a; }
.ir-summary-value { font-size:1.2rem; font-weight:800; color:var(--text-900,#1b2430); line-height:1; }
.ir-summary-label { font-size:0.72rem; font-weight:700; color:var(--text-700,#3b4252); margin-top:4px; }

.ir-row { display:grid; grid-template-columns:1fr 380px; gap:16px; align-items:start; }
.ir-col-main { min-width:0; }
.ir-col-side { width:380px; flex-shrink:0; }

.ir-card { background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); border-radius:14px; padding:18px; box-shadow:var(--shadow-soft,0 1px 2px rgba(13,27,46,.04)); margin-bottom:16px; }
.ir-card-head { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:14px; flex-wrap:wrap; }
.ir-card-head h3 { margin:0; font-size:0.88rem; font-weight:700; color:var(--text-900,#1b2430); display:flex; align-items:center; gap:8px; }
.ir-empty { padding:24px; text-align:center; color:var(--text-400,#8b93a1); font-size:0.76rem; }

.ir-card-body { display:flex; flex-direction:column; max-height: 540px; overflow: hidden; }
.ir-table-wrap { overflow: auto; flex: 1 1 auto; }
.ir-table { width:100%; border-collapse:collapse; font-size:0.72rem; }
.ir-table th { text-align:left; padding:10px 12px; font-size:0.66rem; font-weight:700; text-transform:uppercase; color:var(--text-400,#8b93a1); border-bottom:1px solid var(--border,#e4e8ee); background:#fafbfc; vertical-align: middle; }
.ir-table td { padding:10px 12px; border-bottom:1px solid var(--border,#e4e8ee); vertical-align: middle; }
.ir-table tr:last-child td { border-bottom:none; }

.ir-table .ir-action-cell { width: 50px; }
.ir-table .ir-emp-cell { width: 140px; }
.ir-table .ir-id-cell { width: 120px; }

.ir-flash { padding: 10px 14px; border-radius: 10px; font-size: 0.76rem; font-weight: 600; margin-bottom: 14px; }
.ir-flash.success { background: rgba(47, 158, 110, .10); color: #1f7a52; border: 1px solid rgba(47, 158, 110, .22); }
.ir-flash.error { background: rgba(214, 72, 74, .10); color: #a3272a; border: 1px solid rgba(214, 72, 74, .22); }

.ir-cnum { font-weight: 600; color: var(--text-800, #2b3340); font-size: 0.68rem; }
.ir-emp-name { font-weight: 600; color: var(--text-800, #2b3340); font-size: 0.68rem; }
.ir-emp-no { font-size: 0.64rem; color: var(--text-400, #8b93a1); }
.ir-type-badge { display: inline-block; padding: 2px 8px; border-radius: 2px; font-size: 0.62rem; font-weight: 700; white-space: nowrap; }

.ir-severity-pill { display: inline-flex; align-items: center; gap: 6px; font-size: 0.62rem; font-weight: 700; padding: 2px 8px; border-radius: 999px; }
.ir-severity-pill--high { background: rgba(214, 72, 74, .10); color: #a3272a; }
.ir-severity-pill--med { background: rgba(217, 154, 43, .12); color: #a86b13; }
.ir-severity-pill--low { background: rgba(47, 158, 110, .10); color: #1f7a52; }
.ir-severity-dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }
.ir-severity-dot--critical { background: #a3272a; }
.ir-severity-dot--high { background: #a3272a; }
.ir-severity-dot--medium { background: #d99a2b; }
.ir-severity-dot--low { background: #1f7a52; }

.ir-status-stamp { display: inline-block; font-size: 0.62rem; font-weight: 700; padding: 3px 10px; border-radius: 999px; white-space: nowrap; }
.ir-status-stamp--compliant { background: rgba(47, 158, 110, .12); color: #1f7a52; }
.ir-status-stamp--info { background: rgba(59, 130, 196, .12); color: #1c5a8a; }
.ir-status-stamp--pending { background: rgba(217, 154, 43, .14); color: #a86b13; }

.ir-btn-icon {
  display: inline-flex; align-items: center; justify-content: center; gap: 3px;
  padding: 2px 4px; border-radius: 5px; border: 1px solid var(--border, #e4e8ee);
  background: var(--card-bg, #fff); color: var(--text-600, #5b6472);
  cursor: pointer; text-decoration: none; white-space: nowrap;
  font-size: 0.62rem; font-weight: 600;
  transition: background 150ms ease, border-color 150ms ease, color 150ms ease, transform 80ms ease;
}
.ir-btn-icon:hover { background: var(--bg-page, #f3f5f9); border-color: var(--border-strong, #d3d9e2); color: var(--text-800, #2b3340); }
.ir-btn-icon:active { transform: translateY(1px); }
.ir-btn-icon .bi { font-size: 0.82rem; line-height: 1; }
.ir-btn-icon .ir-view-text { display: none; }
.ir-action-cell { text-align: right; }

.ir-filter-form { display: flex; flex-direction: column; gap: 10px; }
.ir-filter-row { display: grid; gap: 10px; }
.ir-filter-row--primary { grid-template-columns: 1fr; }
.ir-filter-row--primary > .ir-field--ddl-actions { grid-template-columns: 1fr 1fr auto; }
.ir-filter-row--advanced { grid-template-columns: 1fr 1fr 1fr; }
@media (max-width: 1100px) {
  .ir-filter-row--primary { grid-template-columns: 1fr; }
  .ir-filter-row--primary > .ir-field--ddl-actions { grid-template-columns: 1fr 1fr; }
  .ir-filter-row--advanced { grid-template-columns: 1fr 1fr; }
  .ir-row { grid-template-columns: 1fr; }
  .ir-col-side { position: static; width: auto; }
}
@media (max-width: 600px) {
  .ir-filter-row--primary { grid-template-columns: 1fr; }
  .ir-filter-row--primary > .ir-field--ddl-actions { grid-template-columns: 1fr; }
  .ir-filter-row--advanced { grid-template-columns: 1fr; }
}
.ir-field { display: flex; flex-direction: column; gap: 4px; min-width: 0; }
.ir-field--grow { flex: 1; min-width: 0; }
.ir-field--full { grid-column: 1 / -1; }
.ir-field--ddl-actions {
  display: grid;
  grid-template-columns: 1fr 1fr auto;
  gap: 10px;
  grid-column: 1 / -1;
  align-items: end;
  min-width: 0;
}
.ir-field label {
  font-size: 0.64rem; font-weight: 600; color: var(--text-700,#3b4252); margin-bottom: 3px;
  display: block; text-transform: none; letter-spacing: normal;
}
.ir-field input, .ir-field select {
  width: 100%; padding: 7px 10px; border: 1px solid var(--border,#e4e8ee); border-radius: 8px;
  font-size: 0.72rem; color: var(--text-900,#1b2430); background: var(--card-bg,#fff);
  transition: border-color 150ms ease, box-shadow 150ms ease;
  box-sizing: border-box;
}
.ir-field input:focus, .ir-field select:focus {
  outline: none; border-color: var(--info-blue,#3b82c4);
  box-shadow: 0 0 0 3px rgba(59,130,196,0.12);
}
.ir-btn {
  font-size: 0.68rem; font-weight: 600;
  padding: 3px 9px; border-radius: 6px; border: 1px solid var(--border, #e4e8ee);
  background: var(--card-bg, #fff); color: var(--text-600, #5b6472);
  cursor: pointer; text-decoration: none; white-space: nowrap;
  transition: background 150ms ease, border-color 150ms ease, color 150ms ease;
  display: inline-flex; align-items: center; gap: 4px;
}
.ir-btn:hover { background: var(--bg-page, #f3f5f9); border-color: var(--border-strong, #d3d9e2); }
.ir-btn.primary { background: var(--info-blue, #3b82c4); color: #fff; border-color: var(--info-blue, #3b82c4); }
.ir-btn.primary:hover { background: #1c5a8a; border-color: #1c5a8a; color: #fff; }
.ir-btn.ghost { background: transparent; border-color: transparent; color: var(--text-600, #5b6472); }
.ir-btn.ghost:hover { background: var(--bg-page, #f3f5f9); border-color: var(--border-strong, #d3d9e2); }

.ir-reminder-list { display: flex; flex-direction: column; gap: 10px; }
.ir-reminder-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px; border: 1px solid var(--border, #e4e8ee); border-radius: 8px; background: #fff; transition: background-color 160ms ease, border-color 160ms ease; }
.ir-reminder-row:hover { background: var(--bg-soft, #f8f9fb); border-color: var(--border-strong, #d3d9e2); }
.ir-reminder-text { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.ir-reminder-text strong { font-size: 0.68rem; color: var(--text-900, #1b2430); font-weight: 600; }
.ir-reminder-text span { font-size: 0.64rem; color: var(--text-500, #8b93a1); }
.ir-reminder-step { font-size: 0.62rem; color: var(--text-600, #5b6472); font-weight: 600; }
.ir-reminder-actions { display: flex; gap: 6px; flex-shrink: 0; }
.ir-btn-xs { padding: 2px 8px; font-size: 0.6rem; border-radius: 6px; }
.ir-stamp-overdue { background: rgba(214,72,74,0.10); color: #a3272a; font-size: 0.62rem; }

@media (max-width: 768px) {
  .ir-row { grid-template-columns: 1fr; }
  .ir-col-side { width: auto; }
  .ir-card-body { max-height: none; }
  .ir-table thead { display: none; }
  .ir-table, .ir-table tbody, .ir-table tr, .ir-table td { display: block; width: 100%; }
  .ir-table tr {
    background: var(--card-bg, #fff);
    border: 1px solid var(--border, #e4e8ee);
    border-radius: 12px;
    padding: 12px;
    margin-bottom: 12px;
    box-shadow: var(--shadow-soft, 0 1px 2px rgba(13,27,46,.04));
  }
  .ir-table td {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 8px 0;
    border-bottom: 1px solid var(--border, #e4e8ee);
    text-align: right;
  }
  .ir-table td:last-child { border-bottom: none; padding-bottom: 0; }
  .ir-table td::before {
    content: attr(data-label);
    font-size: 0.64rem;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--text-400, #8b93a1);
    text-align: left;
    flex-shrink: 0;
  }
  .ir-table .ir-id-cell,
  .ir-table .ir-emp-cell,
  .ir-table .ir-action-cell { width: auto; }
  .ir-action-cell { text-align: right; }
  .ir-btn-icon { padding: 6px 10px; font-size: 0.72rem; }
  .ir-btn-icon .bi { font-size: 1rem; }
  .ir-btn-icon .ir-view-text { display: inline; }
  .ir-type-badge { font-size: 0.66rem; padding: 3px 10px; }
  .ir-severity-pill { font-size: 0.66rem; padding: 3px 10px; }
  .ir-status-stamp { font-size: 0.66rem; padding: 3px 10px; }
}
</style>

<script>
(function(){
  window.irOpenDetail = function(id) {
    window.location.href = '?page=incident-workflow&id=' + encodeURIComponent(id);
  };

  document.querySelectorAll('tr[data-rid]').forEach(function(row) {
    row.addEventListener('click', function(e) {
      if (e.target.closest('button, a, input, select, textarea, form, label')) return;
      irOpenDetail(parseInt(row.getAttribute('data-rid'), 10));
    });
  });
})();
</script>

