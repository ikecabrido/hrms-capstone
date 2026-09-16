<?php
require_once __DIR__ . '/../../../auth/session.php';
require_once __DIR__ . '/../autoload.php';


$authUser = $_SESSION['user'] ?? [];
$userRole = strtolower(trim((string)($authUser['role_name']
  ?? $authUser['role']
  ?? $_SESSION['role_name']
  ?? $_SESSION['role']
  ?? '')));
$userPosition = strtolower(trim((string)($authUser['position_name']
  ?? $_SESSION['position_name']
  ?? '')));
$userRoleId = (int)($authUser['role_id'] ?? $_SESSION['role_id'] ?? 0);
$isHrAdmin = in_array($userRoleId, [1, 12], true)
  || preg_match('/(^|[^a-z])(admin|hr|human resources|human resource|employee relations|engagement)([^a-z]|$)/', $userRole) === 1
  || preg_match('/(^|[^a-z])(hr staff|hr officer)([^a-z]|$)/', $userPosition) === 1;

if (empty($_SESSION['employee_id']) || !$isHrAdmin) {
    http_response_code(403);
    echo '<div class="alert alert-danger">Access denied. Only HR/Admin users can access this portal.</div>';
    exit;
}

  $validGrievanceTabs = ['all-grievances', 'management', 'reports'];
  $savedGrievanceTab = strtolower(trim((string)($_COOKIE['engagement_grievance_tab'] ?? '')));
  $activeGrievanceTab = in_array($savedGrievanceTab, $validGrievanceTabs, true)
    ? $savedGrievanceTab
    : 'all-grievances';

$payload = [
  'grievances' => [],
  'departments' => [],
];

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

?>
<div class="module-header">
        <h1>Grievances</h1>
    </div>
    <div class="grievance-area">
      <section class="content">
        <div class="container-fluid">
          <div class="row">
            <div class="col-12">
              <!-- Flash Messages -->
              <?php if ($flashSuccess): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                  <i class="fas fa-check-circle"></i> <?= htmlspecialchars($flashSuccess) ?>
                  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
              <?php endif; ?>
              <?php if ($flashError): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                  <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($flashError) ?>
                  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Grievance Management Tabs -->
          <div class="card shadow-sm border-0 grievance-tabs-card">
            <div class="card-header p-0 border-0">
              <ul class="nav nav-tabs grievance-nav-tabs" id="grievance-tabs" role="tablist">
                <li class="nav-item">
                  <a class="nav-link<?= $activeGrievanceTab === 'all-grievances' ? ' active' : '' ?>" id="all-grievances-tab" href="#all-grievances" data-grievance-tab="all-grievances" role="tab" aria-selected="<?= $activeGrievanceTab === 'all-grievances' ? 'true' : 'false' ?>">
                    <i class="fas fa-list"></i> All Grievances
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link<?= $activeGrievanceTab === 'management' ? ' active' : '' ?>" id="management-tab" href="#management" data-grievance-tab="management" role="tab" aria-selected="<?= $activeGrievanceTab === 'management' ? 'true' : 'false' ?>">
                    <i class="fas fa-cogs"></i> Manage Grievance
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link<?= $activeGrievanceTab === 'reports' ? ' active' : '' ?>" id="reports-tab" href="#reports" data-grievance-tab="reports" role="tab" aria-selected="<?= $activeGrievanceTab === 'reports' ? 'true' : 'false' ?>">
                    <i class="fas fa-file-alt"></i> Reports
                  </a>
                </li>
              </ul>
            </div>

            <div class="card-body">
              <div class="tab-content grievance-tab-content-visible" id="grievance-tabs-content" data-report-data="<?= htmlspecialchars(json_encode($payload['grievances'] ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>">
                <div class="tab-pane fade<?= $activeGrievanceTab === 'all-grievances' ? ' show active' : '' ?>" id="all-grievances" role="tabpanel" aria-labelledby="all-grievances-tab">
                  <!-- Record Employee Grievance form removed per request -->

                  <div class="row mb-3">
                     <div class="col-12 mb-2">
                      <p class="text-muted small mb-0">All Grievances.</p>
                    </div>
                    <!-- helper removed per request -->
                    <div class="col-md-2">
                      <select class="form-control" id="status-filter">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="resolved">Resolved</option>
                        <option value="closed">Closed</option>
                        <option value="escalated">Escalated</option>
                      </select>
                    </div>
                    <div class="col-md-2">
                      <select class="form-control" id="category-filter">
                        <option value="">All Categories</option>
                        <option value="Payroll Issues">Payroll Issues</option>
                        <option value="Attendance & Leave">Attendance & Leave</option>
                        <option value="Workplace Harassment">Workplace Harassment</option>
                        <option value="Supervisor/Management Issues">Supervisor/Management Issues</option>
                        <option value="Co-worker Issues">Co-worker Issues</option>
                        <option value="Health and Safety">Health and Safety</option>
                        <option value="Company Policy Violations">Company Policy Violations</option>
                        <option value="Benefits Concerns">Benefits Concerns</option>
                        <option value="Other Employment Concerns">Other Employment Concerns</option>
                        <option value="Other">Other</option>
                      </select>
                    </div>
                    <div class="col-md-2">
                      <select class="form-control" id="department-filter">
                        <option value="">All Departments</option>
                        <?php foreach ($payload['departments'] as $departmentRow): ?>
                          <?php $department = $departmentRow['department_name'] ?? ''; ?>
                          <?php if ($department !== ''): ?>
                            <option value="<?= htmlspecialchars($department) ?>"><?= htmlspecialchars($department) ?></option>
                          <?php endif; ?>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="col-md-2">
                      <input type="date" class="form-control" id="date-filter" placeholder="Date">
                    </div>
                    <div class="col-md-2">
                      <input type="text" class="form-control" id="search-filter" placeholder="Search...">
                    </div>
                  </div>

                  <div class="table-responsive">
                    <table class="table table-striped table-bordered" id="all-grievances-table">
                      <thead>
                        <tr>
                          <th>Grievance ID</th>
                          <th>Employee Name</th>
                          <th>Subject</th>
                          <th>Category</th>
                          <th>Status</th>
                          <th>Priority</th>
                          <th>Date Submitted</th>
                          <th>Payslip</th>
                          <th>Employee Adjustments</th>
                          <th>Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if (!empty($payload['grievances'])): ?>
                          <?php foreach ($payload['grievances'] as $grievance): ?>
                            <?php
                              $payslipLabel = '';
                              $categoryLabelRow = strtolower(trim($grievance['category'] ?? ''));
                              $subjectLabelRow = strtolower(trim($grievance['subject'] ?? ''));
                              $isPayrollRelatedRow = (
                                strpos($categoryLabelRow, 'payroll') !== false ||
                                strpos($categoryLabelRow, 'payslip') !== false ||
                                strpos($categoryLabelRow, 'pay') !== false ||
                                strpos($categoryLabelRow, 'salary') !== false ||
                                strpos($categoryLabelRow, 'benefit') !== false ||
                                strpos($categoryLabelRow, 'deduction') !== false ||
                                strpos($subjectLabelRow, 'payroll') !== false ||
                                strpos($subjectLabelRow, 'payslip') !== false ||
                                strpos($subjectLabelRow, 'pay') !== false ||
                                strpos($subjectLabelRow, 'salary') !== false ||
                                strpos($subjectLabelRow, 'benefit') !== false ||
                                strpos($subjectLabelRow, 'deduction') !== false
                              );

                              if ($isPayrollRelatedRow) {
                                if (!empty($grievance['payslip_information'])) {
                                  $payslipLabel = $grievance['payslip_information'];
                                } elseif (!empty($grievance['payslip_id'])) {
                                  $payslipLabel = 'Payslip ' . (int)$grievance['payslip_id'];
                                  if (!empty($grievance['gross_pay']) || !empty($grievance['net_pay'])) {
                                    $payslipLabel .= ' | Gross ' . number_format((float)$grievance['gross_pay'], 2);
                                    $payslipLabel .= ' | Net ' . number_format((float)$grievance['net_pay'], 2);
                                  }
                                } elseif (!empty($grievance['gross_pay']) || !empty($grievance['net_pay'])) {
                                  $payslipLabel = 'Gross ' . number_format((float)$grievance['gross_pay'], 2) . ' | Net ' . number_format((float)$grievance['net_pay'], 2);
                                }
                              }
                            ?>
                            <tr class="grievance-row"
                                data-id="<?= (int)($grievance['id'] ?? 0) ?>"
                                data-status="<?= strtolower(htmlspecialchars($grievance['status'] ?? '')) ?>"
                                data-category="<?= htmlspecialchars($grievance['category'] ?? '') ?>"
                                data-priority="<?= strtolower(htmlspecialchars($grievance['priority'] ?? '')) ?>"
                                data-department="<?= htmlspecialchars($grievance['department'] ?? '') ?>"
                                data-date="<?= htmlspecialchars(substr($grievance['created_at'] ?? '', 0, 10)) ?>"
                                data-payroll-module="<?= htmlspecialchars(strtolower($grievance['payroll_module'] ?? 'no payroll link')) ?>"
                                data-search="<?= strtolower(htmlspecialchars($grievance['subject'] ?? '') . ' ' . ($grievance['employee_name'] ?? '') . ' ' . ($grievance['category'] ?? '') . ' ' . ($payslipLabel) . ' ' . ($grievance['payroll_module'] ?? '') . ' ' . ($grievance['payroll_reference_id'] ?? '') . ' ' . ($grievance['employee_adjustments'] ?? '')) ?>">
                              <td><?= (int)($grievance['id'] ?? 0) ?></td>
                              <td><?= htmlspecialchars($grievance['employee_name'] ?? 'Unknown') ?></td>
                              <td><?= htmlspecialchars($grievance['subject'] ?? '') ?></td>
                              <td><?= htmlspecialchars($grievance['category'] ?? 'N/A') ?></td>
                              <td><span class="badge badge-secondary"><?= htmlspecialchars(ucfirst($grievance['status'] ?? 'Pending')) ?></span></td>
                              <td><span class="badge badge-<?= strtolower($grievance['priority'] ?? '') === 'high' ? 'danger' : 'secondary' ?>"><?= htmlspecialchars(ucfirst($grievance['priority'] ?? 'Medium')) ?></span></td>
                              <td><?= htmlspecialchars(date('M d, Y', strtotime($grievance['created_at'] ?? 'now'))) ?></td>
                              <td><?= htmlspecialchars($payslipLabel ?: 'None') ?></td>
                              <td>
                                <?php if (!empty($grievance['employee_adjustments'])): ?>
                                  <small title="<?= htmlspecialchars($grievance['employee_adjustments']) ?>"><?= htmlspecialchars(substr($grievance['employee_adjustments'], 0, 50)) ?><?= strlen($grievance['employee_adjustments']) > 50 ? '...' : '' ?></small>
                                <?php else: ?>
                                  <span class="text-muted">None</span>
                                <?php endif; ?>
                              </td>
                              <td>
                                <button type="button" class="btn btn-sm btn-info" title="View Grievance" data-grievance-view="<?= (int)($grievance['id'] ?? 0) ?>"><i class="fas fa-eye"></i></button>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        <?php else: ?>
                          <tr><td colspan="10" class="text-center text-muted">No grievance records found.</td></tr>
                        <?php endif; ?>
                      </tbody>
                    </table>
                  </div>
                </div>

                <div class="tab-pane fade<?= $activeGrievanceTab === 'management' ? ' show active' : '' ?>" id="management" role="tabpanel" aria-labelledby="management-tab">
                  <div class="card card-success">
                    <div class="card-header">
                      <h3 class="card-title"><i class="fas fa-user-shield"></i> Grievance</h3>
                    </div>
                    <div class="card-body">
                      <div class="row">
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>Choose grievance</label>
                            <select class="form-control" id="management-grievance-select" onchange="document.getElementById('management-grievance-id').value=this.value;">
                              <option value="">Select grievance record</option>
                              <?php foreach ($payload['grievances'] as $grievance): ?>
                                <option value="<?= (int)($grievance['id'] ?? 0) ?>"
                                      data-status="<?= strtolower(trim(htmlspecialchars($grievance['status'] ?? ''))) ?>"
                                      data-escalation-level="<?= htmlspecialchars($grievance['escalation_level'] ?? '') ?>"
                                    data-escalation-reason="<?= htmlspecialchars($grievance['escalation_reason'] ?? '') ?>"
                                    data-compliance-record-id="<?= (int)($grievance['compliance_record_id'] ?? 0) ?>">
                                <?= (int)($grievance['id'] ?? 0) ?> - <?= htmlspecialchars($grievance['subject'] ?? '') ?>
                              </option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                        </div>
                        <div class="col-md-6"></div>
                      </div>
                      <div id="management-form-alert" class="alert d-none" role="alert"></div>
                      <form id="management-update-form" method="POST" action="/hrms-capstone/modules/engagement/api/grievance.php?action=update_management" enctype="multipart/form-data" data-skip="true">
                        <input type="hidden" name="management_action" value="1">
                        <input type="hidden" name="grievance_id" id="management-grievance-id" value="">
                        <div class="row">
                          <div class="col-md-6">
                            <div class="form-group">
                              <label>Status</label>
                              <select class="form-control" name="status" id="management-status-select" onchange="var fields=document.getElementById('escalation-fields');var inputs=fields?fields.querySelectorAll('input,textarea'):[];var show=this.value.toLowerCase().trim()==='escalated';if(fields)fields.classList.toggle('d-none',!show);inputs.forEach(function(input){input.disabled=!show;});">
                                <option value="Pending">Pending</option>
                                <option value="Resolved">Resolved</option>
                                <option value="Closed">Closed</option>
                                <option value="Escalated">Escalated</option>
                              </select>
                            </div>
                          </div>
                        </div>
                        <div id="escalation-fields" class="border rounded p-3 mb-3 d-none">
                          <h5 class="mb-2">Escalation Details</h5>
                          <div class="form-group">
                            <label for="management-escalation-level">Escalation Level</label>
                            <input type="number" class="form-control" id="management-escalation-level" name="escalation_level" min="1" placeholder="Enter escalation level" disabled>
                          </div>
                          <div class="form-group">
                            <label for="management-escalation-reason">Escalation Reason</label>
                            <textarea class="form-control" id="management-escalation-reason" name="escalation_reason" rows="3" placeholder="Describe why this grievance was escalated" disabled></textarea>
                          </div>
                        </div>
                        <!-- Investigation Notes removed from management UI per request -->
                        <div class="form-group">
                          <label>HR Remarks</label>
                          <textarea class="form-control" name="hr_remarks" rows="4" placeholder="Add HR remarks or actions taken"></textarea>
                        </div>
                        <div class="form-group">
                          <label>Final Resolution</label>
                          <textarea class="form-control" name="final_resolution" rows="4" placeholder="Record the final resolution"></textarea>
                        </div>
                        <div class="form-group">
                          <label>Supporting Documents</label>
                          <input type="file" class="form-control-file" name="supporting_document">
                        </div>
                        <div class="form-check">
                          <input type="checkbox" class="form-check-input" name="confidential" value="1">
                          <label class="form-check-label">Mark as confidential</label>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3"><i class="fas fa-save"></i> Save Management Update</button>
                      </form>
                    </div>
                  </div>
                </div>

                

                <div class="tab-pane fade<?= $activeGrievanceTab === 'reports' ? ' show active' : '' ?>" id="reports" role="tabpanel" aria-labelledby="reports-tab">
                  <p class="text-muted mb-3">Grievance Reports & Exports – generate and export reports derived from grievance records (not directly from payroll tables).</p>
                  <div class="row">
                    <div class="col-md-4">
                      <div class="card card-primary">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-file-alt"></i> Generate Reports</h3></div>
                        <div class="card-body">
                          <form id="report-form">
                            <div class="form-group"><label>Report Type</label><select class="form-control" id="report-type"><option value="summary">Summary Report</option><option value="detailed">Detailed Report</option><option value="category">Category Analysis</option><option value="resolution">Resolution Report</option></select></div>
                            <div class="form-group"><label>Employee</label><input type="text" class="form-control" id="report-employee" placeholder="Employee name"></div>
                            <div class="form-group"><label>Date Range</label><div class="input-group"><input type="date" class="form-control" id="report-start-date"><div class="input-group-prepend"><span class="input-group-text">to</span></div><input type="date" class="form-control" id="report-end-date"></div></div>
                            <div class="form-group"><label>Department</label><select class="form-control" id="report-department"><option value="">All Departments</option><?php foreach ($payload['departments'] as $departmentRow): ?><?php $department = $departmentRow['department_name'] ?? ''; ?><?php if ($department !== ''): ?><option value="<?= htmlspecialchars($department) ?>"><?= htmlspecialchars($department) ?></option><?php endif; ?><?php endforeach; ?></select></div>
                            <div class="form-group"><label>Category</label><select class="form-control" id="report-category"><option value="">All Categories</option><option value="Payroll Issues">Payroll Issues</option><option value="Attendance & Leave">Attendance & Leave</option><option value="Workplace Harassment">Workplace Harassment</option><option value="Supervisor/Management Issues">Supervisor/Management Issues</option><option value="Co-worker Issues">Co-worker Issues</option><option value="Health and Safety">Health and Safety</option><option value="Company Policy Violations">Company Policy Violations</option><option value="Benefits Concerns">Benefits Concerns</option><option value="Other Employment Concerns">Other Employment Concerns</option><option value="Other">Other</option></select></div>
                            <div class="form-group"><label>Status</label><select class="form-control" id="report-status"><option value="">All Status</option><option value="Pending">Pending</option><option value="Resolved">Resolved</option><option value="Closed">Closed</option><option value="Escalated">Escalated</option></select></div>
                            <div class="form-group"><label>Format</label><select class="form-control" id="report-format"><option value="pdf">PDF</option><option value="excel">Excel</option></select></div>
                            <button type="button" class="btn btn-primary btn-block" onclick="generateCustomReport()"><i class="fas fa-download"></i> Generate Report</button>
                          </form>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-8">
                      <div id="generated-report" class="card mb-3 hidden">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-file-alt"></i> Generated Report Result</h3></div>
                        <div class="card-body"><div id="generated-report-summary" class="mb-3"></div><div class="table-responsive"><table class="table table-bordered table-sm" id="generated-report-table"><thead></thead><tbody></tbody></table></div></div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
    </div>
          </div>


