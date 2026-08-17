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
    </div>

<div class="modal fade" id="empQrModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-qrcode mr-2"></i>Employee QR</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body text-center">
        <div class="qr-preview-shell rounded bg-light p-3 d-inline-block">
          <div id="empQrcode"></div>
        </div>
        <h5 id="empQrName" class="mt-3 mb-0"></h5>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-success" id="printEmpQr"><i class="fas fa-print mr-1"></i>Print</button>
      </div>
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
