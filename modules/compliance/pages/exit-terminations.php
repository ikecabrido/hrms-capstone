<?php
require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../../../auth/session.php';

$pageTitle = 'Exit Terminations';
$activeGroup = 'Exit Acknowledgement';
$activePage = 'exit-terminations';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
if (!isset($user) || empty($user)) {
    $user = $_SESSION['user'] ?? [];
}
if (!isset($db)) {
    $db = (new Database())->getConnection();
}

$extraCssArray = [];
$extraJsArray = [];

$flash = '';
if (isset($_GET['msg'])) {
    $raw = (string) $_GET['msg'];
    if (strpos($raw, '?msg=') !== false) {
        $parts = explode('?msg=', $raw);
        $raw = end($parts);
    }
    $flash = htmlspecialchars($raw, ENT_QUOTES);
}

function et_all(PDO $db, string $sql, array $params = []): array {
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}

$records = et_all($db, "
    SELECT 
        c.id,
        CONCAT('TER-', LPAD(c.id, 6, '0')) AS request_number,
        c.employee_id,
        c.title AS termination_reason,
        c.updated_at AS effective_date,
        c.description AS comments,
        c.assigned_to AS submitted_by,
        c.status,
        NULL AS reviewed_by,
        NULL AS reviewed_at,
        NULL AS review_remarks,
        NULL AS legal_approved_by,
        NULL AS legal_approved_at,
        NULL AS legal_approval_comments,
        NULL AS approved_by,
        NULL AS approved_at,
        c.created_at,
        c.updated_at,
        CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.middle_name, ''), ' ', COALESCE(e.last_name, '')) AS employee_name,
        e.employee_code AS employee_no,
        d.department_name AS department,
        p.position_name AS position,
        CASE 
            WHEN c.status = 'closed_termination_recommended' THEN 'Completed'
            ELSE 'Pending'
        END AS overall_status
    FROM lc_complaints c
    LEFT JOIN em_employees e ON e.employee_id = c.employee_id
    LEFT JOIN em_departments d ON d.department_id = e.department_id
    LEFT JOIN em_positions p ON p.position_id = e.position_id
    WHERE c.status = 'closed_termination_recommended'
    ORDER BY c.created_at DESC
");

function et_overall_class(string $s): string {
    $s = strtolower($s);
    if ($s === 'completed') return 'ch-status-stamp--compliant';
    return 'ch-status-stamp--pending';
}
function et_overall_label(string $s): string {
    $map = [
        'pending'   => 'Pending',
        'completed' => 'Completed',
        'archived'  => 'Archived',
    ];
    return $map[strtolower($s)] ?? ucfirst(str_replace('_', ' ', $s));
}
?>
<section class="ch-module">
   <?php if (!empty($flash)): ?>
     <?php [$fc, $fm] = explode('|', $flash, 2); ?>
     <div class="ch-flash <?= htmlspecialchars($fc) ?>"><?= htmlspecialchars($fm) ?></div>
   <?php endif; ?>

   <div class="ch-row">
      <div class="ch-col ch-col-main">
         <div class="ch-card">
           <div class="ch-card-head">
             <h3><i class="bi bi-journal-check"></i> Exit Acknowledgement Records</h3>
           </div>
           <div class="ch-card-body">
            <?php if (empty($records)): ?>
              <div class="ch-empty"><i class="bi bi-emoji-smile"></i> No exit records found.</div>
            <?php else: ?>
            <div class="ch-table-wrap">
              <table class="ch-table">
                 <thead>
                   <tr>
                     <th class="ch-id-cell">Request #</th>
                     <th class="ch-emp-cell">Employee</th>
                     <th>Department</th>
                     <th>Exit Type</th>
                     <th>Last Working Day</th>
                     <th>Overall Status</th>
                     <th class="ch-action-cell" style="text-align:right;">Actions</th>
                   </tr>
                 </thead>
                 <tbody>
                    <?php foreach ($records as $r): 
                      $overallClass = et_overall_class($r['overall_status']);
                    ?>
                    <tr data-rid="<?= (int)$r['id'] ?>" style="cursor:pointer;">
                      <td class="ch-id-cell">
                        <div class="ch-cnum"><?= htmlspecialchars($r['request_number'] ?? 'N/A', ENT_QUOTES) ?></div>
                        <div class="ch-emp-no"><?= !empty($r['effective_date']) ? date('M d, Y', strtotime($r['effective_date'])) : '—' ?></div>
                      </td>
                      <td class="ch-emp-cell">
                        <div class="ch-emp-name"><?= htmlspecialchars($r['employee_name'] ?: 'N/A', ENT_QUOTES) ?></div>
                        <div class="ch-emp-no"><?= htmlspecialchars($r['position'] ?: '—', ENT_QUOTES) ?></div>
                      </td>
                      <td>
                        <span class="ch-type-badge" style="background:rgba(59,130,196,.08);color:#1c5a8a;border:1px solid rgba(59,130,196,.16);">
                          <?= htmlspecialchars($r['department'] ?: '—', ENT_QUOTES) ?>
                        </span>
                      </td>
                      <td>
                        <span class="ch-type-badge" style="background:rgba(124,58,237,.08);color:#5b21b6;border:1px solid rgba(124,58,237,.16);">
                          <?= htmlspecialchars($r['termination_reason'] ?: 'N/A', ENT_QUOTES) ?>
                        </span>
                      </td>
                      <td>
                        <div class="ch-emp-no"><?= !empty($r['effective_date']) ? date('M d, Y', strtotime($r['effective_date'])) : '—' ?></div>
                      </td>
                     <td>
                       <span class="ch-status-stamp <?= $overallClass ?>"><?= htmlspecialchars(et_overall_label($r['overall_status']), ENT_QUOTES) ?></span>
                     </td>
                     <td class="ch-action-cell" style="text-align:right;">
                       <button type="button" class="ch-btn-icon" onclick="event.stopPropagation(); window.location.href='?page=exit-acknowledgement&id=<?= (int)$r['id'] ?>'">
                         <i class="bi bi-eye"></i>
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
      </div>
   </div>
</section>

<style>
.ch-module { padding: 4px 2px 24px; }
.ch-row { display:grid; grid-template-columns:1fr 380px; gap:16px; align-items:start; }
.ch-col-main { min-width:0; }
.ch-col-side { width:380px; flex-shrink:0; }

.ch-card { background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); border-radius:14px; padding:18px; box-shadow:var(--shadow-soft,0 1px 2px rgba(13,27,46,.04)); margin-bottom:16px; }
.ch-card-head { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:14px; flex-wrap:wrap; }
.ch-card-head h3 { margin:0; font-size:0.88rem; font-weight:700; color:var(--text-900,#1b2430); display:flex; align-items:center; gap:8px; }
.ch-empty { padding:24px; text-align:center; color:var(--text-400,#8b93a1); font-size:0.76rem; }

.ch-card-body { display:flex; flex-direction:column; max-height: 540px; overflow: hidden; }
.ch-table-wrap { overflow: auto; flex: 1 1 auto; }
.ch-table { width:100%; border-collapse:collapse; font-size:0.72rem; color: #1b2430; }
.ch-table th { text-align:left; padding:10px 12px; font-size:0.66rem; font-weight:700; text-transform:uppercase; color:#8b93a1; border-bottom:1px solid var(--border,#e4e8ee); background:#fafbfc; vertical-align: middle; }
.ch-table td { padding:10px 12px; border-bottom:1px solid var(--border,#e4e8ee); vertical-align: middle; color: #1b2430; min-width: 0; }
.ch-table tr:last-child td { border-bottom:none; }
.ch-table .ch-id-cell { width: 120px; }
.ch-table .ch-emp-cell { width: 160px; }
.ch-table .ch-action-cell { width: 50px; }

.ch-cnum { font-weight: 600; color: #2b3340; font-size: 0.68rem; }
.ch-emp-name { font-weight: 600; color: #2b3340; font-size: 0.68rem; }
.ch-emp-no { font-size: 0.64rem; color: #8b93a1; }
.ch-type-badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 0.62rem; font-weight: 700; white-space: nowrap; }

.ch-status-stamp { display: inline-block; font-size: 0.62rem; font-weight: 700; padding: 3px 10px; border-radius: 999px; white-space: nowrap; }
.ch-status-stamp--compliant { background: rgba(47, 158, 110, .12); color: #1f7a52; }
.ch-status-stamp--info { background: rgba(59, 130, 196, .12); color: #1c5a8a; }
.ch-status-stamp--pending { background: rgba(217, 154, 43, .14); color: #a86b13; }

.ch-btn-icon {
  display: inline-flex; align-items: center; justify-content: center; gap: 3px;
  padding: 2px 4px; border-radius: 5px; border: 1px solid var(--border, #e4e8ee);
  background: #fff; color: #5b6472;
  cursor: pointer; text-decoration: none; white-space: nowrap;
  font-size: 0.62rem; font-weight: 600;
  transition: background 150ms ease, border-color 150ms ease, color 150ms ease, transform 80ms ease;
}
.ch-btn-icon:hover { background: #f3f5f9; border-color: #d3d9e2; color: #2b3340; }
.ch-btn-icon:active { transform: translateY(1px); }
.ch-btn-icon .bi { font-size: 0.82rem; line-height: 1; color: inherit; }
.ch-action-cell { text-align: right; }
</style>

<script>
(function(){
  document.querySelectorAll('tr[data-rid]').forEach(function(row) {
    row.addEventListener('click', function(e) {
      if (e.target.closest('button, a, input, select, textarea, form, label, .ch-btn-icon')) return;
      var rid = parseInt(row.getAttribute('data-rid'), 10);
      if (rid) {
        window.location.href = '?page=exit-acknowledgement&id=' + rid;
      }
    });
  });
})();
</script>
