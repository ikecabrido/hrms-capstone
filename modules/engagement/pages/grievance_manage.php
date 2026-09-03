<?php
require_once __DIR__ . '/../../../auth/session.php';
require_once __DIR__ . '/../autoload.php';

use App\Controllers\GrievanceController;
use App\Controllers\EmployeeController;
use App\Controllers\UserController;

session_start();

$userRole = strtolower(trim($_SESSION['user']['role'] ?? $_SESSION['role_name'] ?? $_SESSION['role'] ?? ''));
$isHrAdmin = preg_match('/\b(admin|hr|human resources|human resource|engagement|employee engagement|engagement relations|relations)\b/', $userRole) === 1;

if (!$isHrAdmin) {
    echo '<div class="alert alert-danger">Access denied. Only HR/Admin users can manage grievances.</div>';
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    echo '<div class="alert alert-danger">Invalid grievance ID.</div>';
    exit;
}

$grievanceCtrl = new GrievanceController();
$employeeCtrl = new EmployeeController();
$userCtrl = new UserController();
$complianceRecords = $grievanceCtrl->getComplianceRecords();

$grievance = $grievanceCtrl->getGrievanceById($id);
$employees = $employeeCtrl->index();
$hrUsers = array_values(array_filter($userCtrl->index(), function ($user) {
    $role = strtolower(trim($user['role'] ?? ''));
    return $role === 'admin' || $role === 'hr' || $role === 'hr_admin' || strpos($role, 'hr') !== false || strpos($role, 'admin') !== false;
}));

if (!$grievance) {
    echo '<div class="alert alert-danger">Grievance not found.</div>';
    exit;
}

$currentLevel = $grievance['escalation_level'] ?? 1;
$isFinalized = in_array($grievance['status'], ['Resolved', 'Closed'], true);
?>

<div class="grievance-manage-content">
  <?php if ($isFinalized): ?>
    <div class="alert alert-danger">
      <i class="fas fa-lock"></i>
      This grievance is <?= htmlspecialchars($grievance['status']) ?> and can no longer be edited.
    </div>
  <?php endif; ?>

  <div class="card card-primary mb-3">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-file-alt"></i> Grievance #<?= htmlspecialchars($grievance['id']) ?></h3>
    </div>
    <div class="card-body">
      <p><strong>Subject:</strong> <?= htmlspecialchars($grievance['subject'] ?? '') ?></p>
      <p><strong>Employee:</strong> <?= htmlspecialchars($grievance['employee_name'] ?? 'Unknown') ?></p>
      <p><strong>Category:</strong> <?= htmlspecialchars($grievance['category'] ?? 'N/A') ?></p>
      <p><strong>Status:</strong> <span class="badge badge-info"><?= htmlspecialchars($grievance['status'] ?? 'Pending') ?></span></p>

      <?php if (strtolower(trim($grievance['status'] ?? '')) === 'escalated'): ?>
        <p><strong>Escalation Level:</strong> <?= htmlspecialchars($grievance['escalation_level'] ?? 'N/A') ?></p>
        <p><strong>Escalation Details:</strong><br>
          <?= nl2br(htmlspecialchars($grievance['escalation_reason'] ?? 'No escalation details provided.')) ?></p>
      <?php endif; ?>

      <p><strong>Priority:</strong> <?= htmlspecialchars($grievance['priority'] ?? 'Medium') ?></p>
      <p><strong>Description:</strong> <?= htmlspecialchars($grievance['description'] ?? '') ?></p>
      <?php if (!empty($grievance['payslip_id']) || !empty($grievance['gross_pay']) || !empty($grievance['total_deductions']) || !empty($grievance['net_pay']) || !empty($grievance['payslip_information'])): ?>
        <hr>
        <p><strong>Payslip Details</strong></p>
        <?php if (!empty($grievance['payslip_id'])): ?>
          <p class="mb-1"><strong>Payslip ID:</strong> #<?= (int)($grievance['payslip_id']) ?></p>
        <?php endif; ?>
        <p class="mb-1"><strong>Gross Pay:</strong> <?= !empty($grievance['gross_pay']) ? '₱' . number_format((float)$grievance['gross_pay'], 2) : '—' ?></p>
        <p class="mb-1"><strong>Total Deductions:</strong> <?= !empty($grievance['total_deductions']) ? '₱' . number_format((float)$grievance['total_deductions'], 2) : '—' ?></p>
        <p class="mb-1"><strong>Net Pay:</strong> <?= !empty($grievance['net_pay']) ? '₱' . number_format((float)$grievance['net_pay'], 2) : '—' ?></p>
        <?php if (!empty($grievance['payslip_information'])): ?>
          <p class="mb-0"><strong>Information:</strong> <?= htmlspecialchars($grievance['payslip_information']) ?></p>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <form id="management-form" method="POST" action="/hrms-capstone/modules/engagement/api/grievance.php?action=update_management" enctype="multipart/form-data" data-skip="true">
    <input type="hidden" name="management_action" value="1">
    <input type="hidden" name="grievance_id" value="<?= $grievance['id'] ?>">

    <div class="row">
      <div class="col-md-6">
        <div class="form-group">
          <label for="status">Status</label>
          <select class="form-control" id="status" name="status" required>
            <option value="">Select status</option>
            <option value="Pending" <?= strtolower($grievance['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="Resolved" <?= strtolower($grievance['status'] ?? '') === 'resolved' ? 'selected' : '' ?>>Resolved</option>
            <option value="Closed" <?= strtolower($grievance['status'] ?? '') === 'closed' ? 'selected' : '' ?>>Closed</option>
            <option value="Escalated" <?= strtolower($grievance['status'] ?? '') === 'escalated' ? 'selected' : '' ?>>Escalated</option>
          </select>
        </div>
      </div>
      <!-- Assignment field removed from management UI per request -->
    </div>

    <div id="standalone-escalation-fields" class="border rounded p-3 mb-3 <?= strtolower(trim($grievance['status'] ?? '')) === 'escalated' ? '' : 'd-none' ?>">
      <h5 class="mb-2">Escalation Details</h5>
      <div class="form-group">
        <label for="standalone-escalation-level">Escalation Level</label>
        <input type="number" class="form-control" id="standalone-escalation-level" name="escalation_level" min="1" value="<?= htmlspecialchars($currentLevel) ?>" placeholder="Enter escalation level" <?= strtolower(trim($grievance['status'] ?? '')) === 'escalated' ? '' : 'disabled' ?>>
      </div>
      <div class="form-group mb-0">
        <label for="standalone-escalation-reason">Escalation Reason</label>
        <textarea class="form-control" id="standalone-escalation-reason" name="escalation_reason" rows="3" placeholder="Describe why this grievance was escalated" <?= strtolower(trim($grievance['status'] ?? '')) === 'escalated' ? '' : 'disabled' ?>><?= htmlspecialchars($grievance['escalation_reason'] ?? '') ?></textarea>
      </div>
    </div>

    <!-- Investigation Notes removed from management UI per request -->

    <div class="form-group">
      <label for="hr_remarks">HR Remarks</label>
      <textarea class="form-control" id="hr_remarks" name="hr_remarks" rows="4" placeholder="Add HR remarks or actions taken" required></textarea>
    </div>

    <div class="form-group">
      <label for="final_resolution">Final Resolution</label>
      <textarea class="form-control" id="final_resolution" name="final_resolution" rows="4" placeholder="Record the final resolution" required></textarea>
    </div>

    <div class="form-group">
      <label for="supporting_document">Supporting Documents</label>
      <input type="file" class="form-control-file" id="supporting_document" name="supporting_document">
    </div>

    <div class="form-group">
      <div class="form-check">
        <input type="checkbox" class="form-check-input" id="confidential" name="confidential" value="1" <?= ($grievance['confidential'] ?? 0) ? 'checked' : '' ?>>
        <label class="form-check-label" for="confidential">Mark as confidential</label>
      </div>
    </div>

    <div class="mt-4">
      <button type="submit" class="btn btn-primary" <?= $isFinalized ? 'disabled' : '' ?>>
        <i class="fas fa-save"></i> Save Management Update
      </button>
      <button type="button" class="btn btn-secondary ml-2" onclick="closeGlobalModal()">
        <i class="fas fa-times"></i> Cancel
      </button>
    </div>
  </form>
</div>

