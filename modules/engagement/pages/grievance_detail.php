<?php
require_once __DIR__ . '/../../../auth/session.php';
require_once __DIR__ . '/../autoload.php';

use App\Controllers\GrievanceController;

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    echo '<div class="alert alert-danger">Invalid grievance ID.</div>';
    exit;
}

$grievanceCtrl = new GrievanceController();
$grievance = $grievanceCtrl->getGrievanceById($id);

if (!$grievance) {
    echo '<div class="alert alert-danger">Grievance not found.</div>';
    exit;
}

// Helper functions
function getStatusBadgeClass($status) {
    $status = strtolower(trim($status));
    switch ($status) {
    case 'submitted':
    case 'pending': return 'warning';
    case 'under review': return 'info';
    
    case 'resolution proposed': return 'primary';
    case 'resolved': return 'success';
    case 'closed': return 'secondary';
    case 'escalated': return 'danger';
        default: return 'light';
    }
}

function getStatusProgressClass($status) {
    $status = strtolower(trim($status));
    switch ($status) {
    case 'submitted':
    case 'pending': return 'warning';
    case 'under review': return 'info';
    
    case 'resolution proposed': return 'primary';
    case 'resolved': return 'success';
    case 'closed': return 'secondary';
    case 'escalated': return 'danger';
        default: return 'light';
    }
}

function getStatusProgress($status) {
    $status = strtolower(trim($status));
    switch ($status) {
    case 'submitted':
    case 'pending': return 25;
    case 'under review': return 40;
    case 'resolution proposed': return 80;
    case 'resolved': return 100;
    case 'closed': return 100;
    case 'escalated': return 75;
    default: return 0;
    }
}
?>

<div class="grievance-detail-content">

      <!-- Grievance Details Card -->
      <div class="card card-primary">
          <div class="card-header">
          <h3 class="card-title">
            <i class="fas fa-exclamation-triangle"></i>
            <span class="card-title-text">View Grievance #<?= htmlspecialchars($grievance['id']) ?></span>
            <span class="print-only-grievance">Grievance <?= htmlspecialchars($grievance['id']) ?></span>
          </h3>
          <div class="card-tools">
            <span class="badge badge-<?= getStatusBadgeClass($grievance['status']) ?>">
              <?= ucfirst(htmlspecialchars($grievance['status'] ?? 'Pending')) ?>
            </span>
          </div>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <h5><strong>Subject:</strong></h5>
              <p><?= htmlspecialchars($grievance['subject']) ?></p>

              <h5><strong>Category:</strong></h5>
              <p><span class="badge badge-secondary"><?= htmlspecialchars($grievance['category'] ?? 'Uncategorized') ?></span></p>

              <h5><strong>Priority:</strong></h5>
              <p><span class="badge badge-<?= $grievance['priority'] === 'high' ? 'danger' : 'secondary' ?>">
                <?= ucfirst(htmlspecialchars($grievance['priority'] ?? 'normal')) ?>
              </span></p>
            </div>
            <div class="col-md-6">
              <h5><strong>Submitted By:</strong></h5>
              <p>
                <?php if ($grievance['anonymous'] ?? 0): ?>
                  <em>Anonymous</em>
                <?php else: ?>
                  <?= htmlspecialchars($grievance['employee_name'] ?? 'Unknown') ?>
                <?php endif; ?>
              </p>

              <h5><strong>Submitted Date:</strong></h5>
              <p><i class="fas fa-calendar"></i> <?= date('M d, Y H:i', strtotime($grievance['created_at'])) ?></p>

              <?php if (!empty($grievance['updated_at']) && $grievance['updated_at'] !== $grievance['created_at']): ?>
                <h5><strong>Last Updated:</strong></h5>
                <p><i class="fas fa-clock"></i> <?= date('M d, Y H:i', strtotime($grievance['updated_at'])) ?></p>
              <?php endif; ?>
            </div>
          </div>

          <?php
            $isAttendanceRelated = false;
            $isPayrollRelated = false;
            $categoryLabel = strtolower(trim($grievance['category'] ?? ''));
            $subjectLabel = strtolower(trim($grievance['subject'] ?? ''));
            if (strpos($categoryLabel, 'attendance') !== false || strpos($categoryLabel, 'leave') !== false || strpos($subjectLabel, 'attendance') !== false || strpos($subjectLabel, 'late') !== false || strpos($subjectLabel, 'absent') !== false || strpos($subjectLabel, 'early out') !== false || strpos($subjectLabel, 'time') !== false) {
              $isAttendanceRelated = true;
            }
            if (strpos($categoryLabel, 'payroll') !== false || strpos($categoryLabel, 'payslip') !== false || strpos($categoryLabel, 'pay') !== false || strpos($categoryLabel, 'salary') !== false || strpos($categoryLabel, 'benefit') !== false || strpos($categoryLabel, 'deduction') !== false || strpos($subjectLabel, 'payroll') !== false || strpos($subjectLabel, 'payslip') !== false || strpos($subjectLabel, 'pay') !== false || strpos($subjectLabel, 'salary') !== false || strpos($subjectLabel, 'benefit') !== false || strpos($subjectLabel, 'deduction') !== false) {
              $isPayrollRelated = true;
            }
            $showPayrollTab = $isPayrollRelated;
          ?>

          <hr>

          <div class="nav nav-tabs mt-3 mb-3" id="grievance-detail-tabs-<?= (int)($grievance['id'] ?? 0) ?>" role="tablist">
            <a class="nav-link active" id="grievance-overview-tab-<?= (int)($grievance['id'] ?? 0) ?>" data-toggle="tab" href="#grievance-overview-<?= (int)($grievance['id'] ?? 0) ?>" role="tab">Overview</a>
          </div>
          <div class="tab-content">
            <div class="tab-pane fade show active" id="grievance-overview-<?= (int)($grievance['id'] ?? 0) ?>" role="tabpanel">
              <h5><strong>Description:</strong></h5>
              <div class="bg-light p-3 rounded">
                <p class="mb-0"><?= nl2br(htmlspecialchars($grievance['description'])) ?></p>
              </div>

          <?php
            $displayPayslipId = $grievance['payslip_id'] ?? $grievance['payroll_reference_id'] ?? null;
          ?>
          <?php if ($isPayrollRelated && (!empty($displayPayslipId) || !empty($grievance['gross_pay']) || !empty($grievance['total_deductions']) || !empty($grievance['net_pay']) || !empty($grievance['payslip_information']))): ?>
            <hr>
            <h5><strong>Payslip Details:</strong></h5>
            <div class="bg-light p-3 rounded">
              <?php if (!empty($displayPayslipId)): ?>
                <p class="mb-1"><strong>Payslip ID:</strong> <?= (int)($displayPayslipId) ?></p>
              <?php endif; ?>
              <p class="mb-1"><strong>Gross Pay:</strong> <?= !empty($grievance['gross_pay']) ? '₱' . number_format((float)$grievance['gross_pay'], 2) : '—' ?></p>
              <p class="mb-1"><strong>Total Deductions:</strong> <?= !empty($grievance['total_deductions']) ? '₱' . number_format((float)$grievance['total_deductions'], 2) : '—' ?></p>
              <p class="mb-1"><strong>Net Pay:</strong> <?= !empty($grievance['net_pay']) ? '₱' . number_format((float)$grievance['net_pay'], 2) : '—' ?></p>
              <?php if (!empty($grievance['payroll_module'])): ?>
                <p class="mb-1"><strong>Payroll Module:</strong> <?= htmlspecialchars($grievance['payroll_module']) ?></p>
              <?php endif; ?>
              <?php if (!empty($grievance['payroll_reference_id'])): ?>
                <p class="mb-1"><strong>Reference ID:</strong> <?= (int)$grievance['payroll_reference_id'] ?></p>
              <?php endif; ?>
              <?php if (!empty($grievance['payslip_information'])): ?>
                <p class="mb-0"><strong>Information:</strong> <?= htmlspecialchars($grievance['payslip_information']) ?></p>
              <?php endif; ?>
          <?php endif; ?>

          <?php if (!$isPayrollRelated && $isAttendanceRelated): ?>
            <hr>
            <h5><strong>Attendance Evidence:</strong></h5>
            <div class="bg-light p-3 rounded">
              <?php
                $attendanceLinks = [];
                if (!empty($grievance['eer_grievance_id'])) {
                  $attendanceLinks = $grievanceCtrl->getAttendanceLinks((int)$grievance['eer_grievance_id']);
                } elseif (!empty($grievance['id'])) {
                  $attendanceLinks = $grievanceCtrl->getAttendanceLinks((int)$grievance['id']);
                }
              ?>
              <?php if (!empty($attendanceLinks)): ?>
                <div class="table-responsive">
                  <table class="table table-sm table-bordered mb-0">
                    <thead>
                      <tr>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Late (min)</th>
                        <th>Early Out (min)</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($attendanceLinks as $link): ?>
                        <tr>
                          <td><?= htmlspecialchars($link['attendance_date'] ?? '') ?></td>
                          <td><?= htmlspecialchars($link['attendance_status'] ?? 'N/A') ?></td>
                          <td><?= (int)($link['late_minutes'] ?? 0) ?></td>
                          <td><?= (int)($link['early_out_minutes'] ?? 0) ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <p class="mb-0 text-muted">No linked attendance records were found for this grievance.</p>
              <?php endif; ?>
            </div>
          <?php endif; ?>

                <?php
                    $employeeId = $grievance['employee_id'] ?? null;
                    $payslipId = $grievance['payslip_id'] ?? $grievance['payroll_reference_id'] ?? null;
                    $payslipItems = [];
                    $adjustments = [];
                    $benefits = [];
                    $payrollContext = [];

                    if ($isPayrollRelated) {
                        $categoryLabel = strtolower(trim($grievance['category'] ?? ''));
                        if (empty($payslipId) && !empty($employeeId) && strpos($categoryLabel, 'payroll') !== false) {
                          $availablePayslips = $grievanceCtrl->getEmployeePayslips($employeeId);
                          if (!empty($availablePayslips)) {
                            $latest = $availablePayslips[0];
                            $payslipId = $latest['id'];
                            // Populate display fields if not already present on the grievance
                            if (empty($grievance['gross_pay']) && isset($latest['gross_pay'])) {
                              $grievance['gross_pay'] = $latest['gross_pay'];
                            }
                            if (empty($grievance['total_deductions']) && isset($latest['total_deductions'])) {
                              $grievance['total_deductions'] = $latest['total_deductions'];
                            }
                            if (empty($grievance['net_pay']) && isset($latest['net_pay'])) {
                              $grievance['net_pay'] = $latest['net_pay'];
                            }
                            if (empty($grievance['payslip_information'])) {
                              $grievance['payslip_information'] = 'Payslip ' . $payslipId . ': gross=' . number_format($grievance['gross_pay'] ?? 0, 2) . ', deductions=' . number_format($grievance['total_deductions'] ?? 0, 2) . ', net=' . number_format($grievance['net_pay'] ?? 0, 2);
                            }
                          }
                        }

                        if (!empty($employeeId) && !empty($payslipId)) {
                            $payslipItems = $grievanceCtrl->getPayslipItems($employeeId, $payslipId);
                            $adjustments = $grievanceCtrl->getEmployeeAdjustments($employeeId, $payslipId);
                        } elseif (!empty($employeeId)) {
                            $adjustments = $grievanceCtrl->getEmployeeAdjustments($employeeId, null);
                        }

                        $benefits = $grievanceCtrl->getEmployeeBenefits($employeeId);
                        $payrollContext = $grievanceCtrl->getPayrollContext($employeeId, $payslipId);
                        $payslipItems = $payrollContext['payslip_items'] ?? $payslipItems;
                        $benefits = $payrollContext['benefits'] ?? $benefits;
                        $adjustments = $payrollContext['deductions'] ?? $adjustments;
                    }
                ?>

                <?php if ($isPayrollRelated && !empty($payslipItems)): ?>
                  <hr>
                  <h5><strong>Earnings & Deductions</strong></h5>
                  <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                      <thead>
                        <tr><th>Type</th><th>Description</th><th class="text-right">Amount</th></tr>
                      </thead>
                      <tbody>
                        <?php foreach ($payslipItems as $item): ?>
                          <tr>
                            <td><?= htmlspecialchars($item['item_type'] ?? '') ?></td>
                            <td><?= htmlspecialchars($item['description'] ?? '') ?></td>
                            <td class="text-right"><?= '₱' . number_format((float)($item['amount'] ?? 0), 2) ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php endif; ?>

                <?php if ($isPayrollRelated && !empty($adjustments)): ?>
                  <hr>
                  <h5><strong>Payroll Deductions / Adjustments</strong></h5>
                  <ul class="list-unstyled">
                    <?php foreach ($adjustments as $adj): ?>
                      <li>
                        <strong><?= htmlspecialchars(!empty($adj['created_at']) ? date('M d, Y', strtotime($adj['created_at'])) : '') ?>:</strong>
                        <?= htmlspecialchars($adj['description'] ?? $adj['name'] ?? '') ?> —
                        <em><?= '₱' . number_format((float)($adj['amount'] ?? 0), 2) ?></em>
                        <?php if (!empty($adj['deduction_subtype'])): ?> <small class="text-muted">(<?= htmlspecialchars($adj['deduction_subtype']) ?>)</small><?php endif; ?>
                        <?php if (!empty($adj['type'])): ?> <small class="text-muted">(<?= htmlspecialchars($adj['type']) ?>)</small><?php endif; ?>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>

                <?php if ($isPayrollRelated && !empty($payrollContext['tax_tables'])): ?>
                  <hr>
                  <h5><strong>Tax Table Match</strong></h5>
                  <ul class="list-unstyled">
                    <?php foreach ($payrollContext['tax_tables'] as $tax): ?>
                      <li>
                        <strong>Tax <?= htmlspecialchars($tax['tax_id'] ?? '') ?>:</strong>
                        <?= htmlspecialchars(($tax['min_income'] ?? '') . ' - ' . ($tax['max_income'] ?? '')) ?>
                        — Rate <?= htmlspecialchars($tax['tax_rate'] ?? '') ?>%, Fixed Tax <?= '₱' . number_format((float)($tax['fixed_tax'] ?? 0), 2) ?>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>

                <?php if ($isPayrollRelated && !empty($benefits)): ?>
                  <hr>
                  <h5><strong>Employee Benefits</strong></h5>
                  <ul class="list-unstyled">
                    <?php foreach ($benefits as $b): ?>
                      <li>
                        <?php if (!empty($b['has_sss'])): ?>
                          <strong>SSS:</strong> Enrolled <?php if (!empty($b['sss_amount_override'])): ?> — <em><?= '₱' . number_format((float)$b['sss_amount_override'], 2) ?></em><?php endif; ?>
                        <?php else: ?>
                          <strong>SSS:</strong> Not enrolled
                        <?php endif; ?>
                        <br>
                        <?php if (!empty($b['has_philhealth'])): ?>
                          <strong>PhilHealth:</strong> Enrolled <?php if (!empty($b['philhealth_amount_override'])): ?> — <em><?= '₱' . number_format((float)$b['philhealth_amount_override'], 2) ?></em><?php endif; ?>
                        <?php else: ?>
                          <strong>PhilHealth:</strong> Not enrolled
                        <?php endif; ?>
                        <br>
                        <?php if (!empty($b['has_pagibig'])): ?>
                          <strong>Pag-IBIG:</strong> Enrolled <?php if (!empty($b['pagibig_amount_override'])): ?> — <em><?= '₱' . number_format((float)$b['pagibig_amount_override'], 2) ?></em><?php endif; ?>
                        <?php else: ?>
                          <strong>Pag-IBIG:</strong> Not enrolled
                        <?php endif; ?>
                        <br>
                        <small class="text-muted">Record created: <?= htmlspecialchars($b['created_at'] ?? '') ?></small>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>

          <?php if (!$isPayrollRelated && !empty($grievance['attachment_path'])): ?>
            <hr>
            <h5><strong>Attachment:</strong></h5>
            <p><a href="../../<?= htmlspecialchars($grievance['attachment_path']) ?>" target="_blank" class="btn btn-sm btn-info">
              <i class="fas fa-download"></i> View Attachment
            </a></p>
          <?php endif; ?>

          <!-- Progress Bar -->
          <hr>
          <h5><strong>Progress:</strong></h5>
          <div class="progress mb-2">
            <div class="progress-bar bg-<?= getStatusProgressClass($grievance['status']) ?>"
                 style="width: <?= getStatusProgress($grievance['status']) ?>%">
            </div>
          </div>
          <small class="text-muted">Progress: <?= getStatusProgress($grievance['status']) ?>%</small>
        </div>
      </div>

      </div>

      <!-- Resolution Section -->
      <?php if (!empty($grievance['resolution'])): ?>
        <div class="card card-success mt-3">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-check-circle"></i> Resolution</h3>
          </div>
          <div class="card-body">
            <div class="bg-light p-3 rounded">
              <p class="mb-0"><?= nl2br(htmlspecialchars($grievance['resolution'])) ?></p>
            </div>
            <?php if (!empty($grievance['resolved_at'])): ?>
              <small class="text-muted">
                <i class="fas fa-calendar-check"></i> Resolved on: <?= date('M d, Y H:i', strtotime($grievance['resolved_at'])) ?>
              </small>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- Escalation Information -->
      <?php if (strtolower(trim($grievance['status'] ?? '')) === 'escalated'): ?>
        <div class="card card-warning mt-3">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-exclamation-triangle"></i> Escalation Details</h3>
          </div>
          <div class="card-body">
            <p><strong>Escalation Level:</strong> <?= htmlspecialchars($grievance['escalation_level']) ?></p>
            <?php if (!empty($grievance['escalation_reason'])): ?>
              <p><strong>Reason:</strong> <?= htmlspecialchars($grievance['escalation_reason']) ?></p>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- Confidentiality Notice -->
      <?php if ($grievance['confidential'] ?? 0): ?>
        <div class="card card-danger mt-3">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-lock"></i> Confidentiality Notice</h3>
          </div>
          <div class="card-body">
            <p class="text-danger"><i class="fas fa-exclamation-triangle"></i>
              This grievance has been marked as confidential. Please handle with appropriate discretion.
            </p>
          </div>
        </div>
      <?php endif; ?>


      <!-- Status Timeline -->
      <div class="card card-outline card-primary">
        <div class="card-header">
          <h3 class="card-title"><i class="fas fa-history"></i> Status Timeline</h3>
        </div>
        <div class="card-body p-0">
          <div class="timeline timeline-inverse">
            <div class="time-label">
              <span class="bg-primary">
                <?= date('M d, Y', strtotime($grievance['created_at'])) ?>
              </span>
            </div>
            <div>
              <i class="fas fa-plus bg-blue"></i>
              <div class="timeline-item">
                <span class="time"><i class="fas fa-clock"></i> <?= date('H:i', strtotime($grievance['created_at'])) ?></span>
                <h3 class="timeline-header">Grievance Submitted</h3>
                <div class="timeline-body">
                  Status: <span class="badge badge-warning">Pending</span>
                </div>
              </div>
            </div>

            <?php if (strtolower($grievance['status']) !== 'submitted' && strtolower($grievance['status']) !== 'pending'): ?>
              <div>
                <i class="fas fa-edit bg-info"></i>
                <div class="timeline-item">
                  <span class="time"><i class="fas fa-clock"></i> Updated</span>
                  <h3 class="timeline-header">Status Changed</h3>
                  <div class="timeline-body">
                    Status: <span class="badge badge-<?= getStatusBadgeClass($grievance['status']) ?>">
                      <?= ucfirst(htmlspecialchars($grievance['status'] ?? 'Pending')) ?>
                    </span>
                  </div>
                </div>
              </div>
            <?php endif; ?>

            <?php if (in_array(strtolower($grievance['status']), ['resolved', 'closed'], true)): ?>
              <div>
                <i class="fas fa-check bg-success"></i>
                <div class="timeline-item">
                  <span class="time"><i class="fas fa-clock"></i>
                    <?php if (!empty($grievance['resolved_at'])): ?>
                      <?= date('H:i', strtotime($grievance['resolved_at'])) ?>
                    <?php else: ?>
                      Updated
                    <?php endif; ?>
                  </span>
                  <h3 class="timeline-header">Resolution Complete</h3>
                  <div class="timeline-body">
                    <?php if (!empty($grievance['resolution'])): ?>
                      Resolution provided and grievance marked as <?= htmlspecialchars($grievance['status']) ?>.
                    <?php else: ?>
                      Grievance marked as <?= htmlspecialchars($grievance['status']) ?>.
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

</div>


<script src="pages/js/grievance_detail.js"></script>
