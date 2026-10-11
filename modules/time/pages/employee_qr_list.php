<?php
/**
 * Employee QR List - separate tab for viewing and printing employee QR codes
 */
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../classes/Employee.php';




$employeeModel = new Employee();
$activeEmployees = $employeeModel->getAll('Active');
$departmentList = [];
foreach ($activeEmployees as $emp) {
    $department = trim((string) ($emp['department'] ?? ''));
    if ($department !== '' && !in_array($department, $departmentList, true)) {
        $departmentList[] = $department;
    }
}
sort($departmentList, SORT_STRING);

$current_page = 'employee_qr_list';
$current_role = $_SESSION['user']['role'] ?? $_SESSION['role'] ?? 'time';
?>
<link rel="stylesheet" href="assets/css/hr-template.css">
<link rel="stylesheet" href="assets/css/employee-qr-list.css">

    <div class="module-header">
        <h1>Employee QR</h1>
    </div>

    <div class="module-content">
<div class="stats-grid">
  <div class="stat-card">
    <div class="info-box-icon bg-primary">
      <i class="fas fa-user-friends"></i>
    </div>
    <div class="info-box-content">
      <span class="info-box-text">Total Employees</span>
      <span class="info-box-number"><?= count($activeEmployees) ?></span>
    </div>
  </div>
  <div class="stat-card">
    <div class="info-box-icon bg-success">
      <i class="fas fa-building"></i>
    </div>
    <div class="info-box-content">
      <span class="info-box-text">Departments</span>
      <span class="info-box-number"><?= count($departmentList) ?></span>
    </div>
  </div>
</div>

<div style="margin-top:12px; margin-bottom:18px;">
  <div class="btn-group" role="group" aria-label="Employee QR sections">
    <button type="button" id="showQrListBtn" class="btn btn-sm btn-outline-primary">QR List</button>
    <button type="button" id="showAttendanceHistoryBtn" class="btn btn-sm btn-primary active">Attendance History</button>
  </div>
</div>

<div id="qrSection" class="section-panel" style="display:none;">
  <div class="filter-section">
    <div class="form-group mb-0">
      <label class="font-weight-bold">Search employee</label>
      <input id="empSearch" type="search" class="form-control" placeholder="Search by name, employee no, or department...">
    </div>
  </div>

  <div class="records-table">
    <table id="empTable">
    <thead>
      <tr>
        <th>#</th>
        <th>Employee No</th>
        <th>Name</th>
        <th>Department</th>
        <th class="text-center">Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($activeEmployees as $i => $emp): ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td><?= htmlspecialchars($emp['employee_no'] ?? $emp['employee_id']) ?></td>
          <td><?= htmlspecialchars($emp['full_name']) ?></td>
          <td><?= htmlspecialchars($emp['department'] ?? '') ?></td>
          <td class="text-center">
            <button class="btn btn-primary btn-sm viewQrBtn" data-id="<?= htmlspecialchars($emp['employee_id']) ?>" data-name="<?= htmlspecialchars($emp['full_name']) ?>">
              <i class="fas fa-qrcode mr-1"></i>View QR
            </button>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div id="qrPagination" class="history-pagination" style="margin-top:12px;">
  <div id="qrPageInfo" class="history-page-info"></div>
  <div class="history-pagination-actions">
    <button id="qrPrev" class="btn btn-sm btn-secondary">Previous</button>
    <button id="qrNext" class="btn btn-sm btn-secondary">Next</button>
  </div>
</div>
</div>

<div id="attendanceHistorySection" class="section-panel history-section" style="display:none;">
  <div class="history-toolbar">
    <button id="historyPrevDay" class="btn btn-sm btn-secondary">Prev Day</button>
    <input type="date" id="historyDate" value="<?php echo date('Y-m-d'); ?>" class="history-date-input" />
    <button id="historyNextDay" class="btn btn-sm btn-secondary">Next Day</button>
    <select id="historyLimit" class="form-control history-limit-select">
      <option value="10" selected>10 / page</option>
    </select>
  </div>

  <div class="history-table-wrapper">
    <table id="historyTable" class="history-table">
      <thead>
        <tr>
          <th style="width:100px;">Date</th>
          <th>QR / ID</th>
          <th>Employee</th>
          <th>Department</th>
          <th>Time In</th>
          <th>Time Out</th>
          <th>Duration</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody id="historyBody"></tbody>
    </table>
  </div>

  <div id="historyPagination" class="history-pagination">
    <div id="historyPageInfo" class="history-page-info"></div>
    <div class="history-pagination-actions">
      <button id="historyPrev" class="btn btn-sm btn-secondary">Previous</button>
      <button id="historyNext" class="btn btn-sm btn-secondary">Next</button>
    </div>
  </div>
</div>
    </div>

<div id="empQrModal" class="emp-qr-modal-overlay hidden" aria-hidden="true">
  <div class="emp-qr-modal" role="dialog" aria-modal="true" aria-labelledby="empQrTitle">
    <div class="emp-qr-modal-header">
      <h5 id="empQrTitle"><i class="fas fa-qrcode mr-2"></i>Employee QR</h5>
      <button type="button" class="emp-qr-close" data-dismiss="modal" aria-label="Close">&times;</button>
    </div>
    <div class="emp-qr-modal-body text-center">
      <div class="qr-preview-shell rounded bg-light p-3 d-inline-block">
        <div id="empQrcode"></div>
      </div>
      <h5 id="empQrName" class="mt-3 mb-0"></h5>
    </div>
    <div class="emp-qr-modal-footer">
      <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      <button type="button" class="btn btn-success" id="printEmpQr"><i class="fas fa-print mr-1"></i>Print</button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
  window.__TA_CONFIG = {
    employees: <?= json_encode($activeEmployees, JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_APOS) ?>
  };
</script>
<script src="assets/js/employee-qr-list.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
  const qrBtn = document.getElementById('showQrListBtn');
  const historyBtn = document.getElementById('showAttendanceHistoryBtn');
  const qrSection = document.getElementById('qrSection');
  const historySection = document.getElementById('attendanceHistorySection');

  function showSection(target){
    const showQr = target === 'qr';
    qrSection.style.display = showQr ? 'block' : 'none';
    historySection.style.display = showQr ? 'none' : 'block';

    qrBtn.classList.toggle('btn-primary', showQr);
    qrBtn.classList.toggle('btn-outline-primary', !showQr);
    qrBtn.classList.toggle('active', showQr);

    historyBtn.classList.toggle('btn-primary', !showQr);
    historyBtn.classList.toggle('btn-outline-primary', showQr);
    historyBtn.classList.toggle('active', !showQr);

    if (showQr) {
      const s = document.getElementById('empSearch'); if (s) s.focus();
    }
  }

  if (qrBtn && historyBtn && qrSection && historySection) {
    showSection('history');
    qrBtn.addEventListener('click', function(){ showSection('qr'); });
    historyBtn.addEventListener('click', function(){ showSection('history'); });
  }
});
</script>

<script>
// History controls (same logic previously used on dashboard)
(function(){
  const historyDate = document.getElementById('historyDate');
  const historyPrevDay = document.getElementById('historyPrevDay');
  const historyNextDay = document.getElementById('historyNextDay');
  const historyLimit = document.getElementById('historyLimit');
  const historyBody = document.getElementById('historyBody');
  const historyPageInfo = document.getElementById('historyPageInfo');
  const historyPrev = document.getElementById('historyPrev');
  const historyNext = document.getElementById('historyNext');
  let historyPage = 1;
  let historyTotalPages = 1;

  function formatHoursHM(decimalHours){
    if (decimalHours === null || decimalHours === undefined || decimalHours === '') return '-';
    const totalMin = Math.round(decimalHours * 60);
    const h = Math.floor(totalMin/60);
    const m = totalMin % 60;
    return h + 'h ' + (m<10?('0'+m):m) + 'm';
  }

  function formatDurationFromTimes(timeIn, timeOut){
    if (!timeIn || !timeOut) return '-';
    const a = new Date(timeIn);
    const b = new Date(timeOut);
    if (isNaN(a) || isNaN(b)) return '-';
    const diff = Math.max(0, Math.round((b - a)/1000));
    const mins = Math.floor(diff/60);
    const h = Math.floor(mins/60);
    const m = mins % 60;
    return h + 'h ' + (m<10?('0'+m):m) + 'm';
  }

  async function loadHistory(){
    const date = historyDate.value;
    const limit = parseInt(historyLimit.value,10)||10;
    const page = historyPage;
    historyBody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:20px;color:#6b7280;">Loading...</td></tr>';
    try{
      const res = await fetch('get_attendance_by_date.php?date='+encodeURIComponent(date)+'&limit='+limit+'&page='+page);
      if (!res.ok) throw new Error('Network');
      const data = await res.json();
      renderHistoryRows(data.records || []);
      const total = data.total || 0;
      historyTotalPages = Math.max(1, Math.ceil(total/limit));
      if (historyPage > historyTotalPages) {
        historyPage = historyTotalPages;
      }
      historyPageInfo.textContent = 'Page '+historyPage+' of '+historyTotalPages+' — '+total+' records';
      historyPrev.disabled = historyPage<=1;
      historyNext.disabled = historyPage>=historyTotalPages;
    }catch(e){
      historyBody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:20px;color:#b91c1c;">Failed to load</td></tr>';
      historyPageInfo.textContent = 'Page 1 of 1 — 0 records';
      historyTotalPages = 1;
      historyPrev.disabled = true;
      historyNext.disabled = true;
    }
  }

  function renderHistoryRows(rows){
    if (!rows || rows.length===0){
      historyBody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:20px;color:#6b7280;">No records for this date.</td></tr>';
      return;
    }
    historyBody.innerHTML = rows.map(r=>{
      const date = r.attendance_date || '';
      const id = r.employee_id || '';
      const emp = (r.full_name||'') + (r.position?(' — '+r.position):'');
      const dept = r.department||'';
      const tin = r.time_in ? r.time_in.replace('T',' ') : '';
      const tout = r.time_out ? r.time_out.replace('T',' ') : '';
      let dur = '-';
      if (r.total_hours_worked) dur = formatHoursHM(parseFloat(r.total_hours_worked));
      else dur = formatDurationFromTimes(r.time_in, r.time_out);
      const status = r.status || '';
      const statusKey = String(status || '').trim().toLowerCase();
      let statusClass = 'status-default';
      if (statusKey.includes('late')) statusClass = 'status-late';
      else if (statusKey.includes('absent') || statusKey.includes('no checkin')) statusClass = 'status-absent';
      else if (statusKey.includes('present') || statusKey.includes('ontime') || statusKey.includes('on time')) statusClass = 'status-present';
      else if (statusKey.includes('overtime')) statusClass = 'status-overtime';
      return '<tr>'+
        '<td>'+escapeHtml(date)+'</td>'+
        '<td>'+escapeHtml(id)+'</td>'+
        '<td>'+escapeHtml(emp)+'</td>'+
        '<td>'+escapeHtml(dept)+'</td>'+
        '<td>'+escapeHtml(tin)+'</td>'+
        '<td>'+escapeHtml(tout)+'</td>'+
        '<td>'+escapeHtml(dur)+'</td>'+
        '<td><span class="history-status-badge '+statusClass+'">'+escapeHtml(status || '—')+'</span></td>'+
      '</tr>';
    }).join('');
  }

  function escapeHtml(s){ if (s===null||s===undefined) return ''; return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

  historyDate.addEventListener('change', ()=>{ historyPage = 1; loadHistory(); });
  historyPrevDay.addEventListener('click', ()=>{ const d = new Date(historyDate.value); d.setDate(d.getDate()-1); historyDate.value = d.toISOString().slice(0,10); historyPage = 1; loadHistory(); });
  historyNextDay.addEventListener('click', ()=>{ const d = new Date(historyDate.value); d.setDate(d.getDate()+1); historyDate.value = d.toISOString().slice(0,10); historyPage = 1; loadHistory(); });
  historyLimit.addEventListener('change', ()=>{ historyPage = 1; loadHistory(); });
  historyPrev.addEventListener('click', ()=>{ if (historyPage > 1) { historyPage -= 1; loadHistory(); } });
  historyNext.addEventListener('click', ()=>{ if (historyPage < historyTotalPages) { historyPage += 1; loadHistory(); } });

  // initial load
  loadHistory();
})();
</script>
