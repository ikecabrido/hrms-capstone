<?php

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../classes/Policy.php';

$pageTitle = 'Create Policy';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

$db = (new Database())->getConnection();
$policy = null;
$categories = [];
$em_employees = [];
$em_departments = [];
$positions = [];

$errors = [];
$success = false;

$stats = [];
$totalPolicies = 0;
$published = 0;
$draft = 0;

if ($db instanceof PDO) {
    $policy = new Policy($db);

    try {
        $stats = $policy->getDashboardStats();
    } catch (Throwable $e) {
        $stats = [];
    }

    $totalPolicies = (int) ($stats['policies']['total_policies'] ?? 0);
    $published = (int) ($stats['policies']['published'] ?? 0);
    $draft = (int) ($stats['policies']['draft'] ?? 0);

    try {
        $categories = $policy->getCategories();
        $em_employees = $policy->getEmployeesForAssignment();
        $em_departments = $policy->getDepartments();
        $positions = $policy->getPositions();
    } catch (Throwable $e) {
        $categories = [];
        $em_employees = [];
        $em_departments = [];
        $positions = [];
    }
}

if (!$db instanceof PDO) {
    $errors[] = 'Database connection is not available. Please check the database configuration.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db instanceof PDO && $policy instanceof Policy) {
    $data = [
        'policy_code' => trim((string) ($_POST['policy_code'] ?? '')),
        'title' => trim((string) ($_POST['title'] ?? '')),
        'category_id' => !empty($_POST['category_id']) ? (int) $_POST['category_id'] : null,
        'description' => trim((string) ($_POST['description'] ?? '')),
        'content' => trim((string) ($_POST['content'] ?? '')),
        'version' => trim((string) ($_POST['version'] ?? '1.0')),
        'effective_date' => !empty($_POST['effective_date']) ? $_POST['effective_date'] : null,
        'acknowledgement_deadline' => !empty($_POST['acknowledgement_deadline']) ? $_POST['acknowledgement_deadline'] : null,
        'status' => trim((string) ($_POST['status'] ?? 'Draft')),
        'requires_acknowledgement' => isset($_POST['requires_acknowledgement']) ? 1 : 0,
        'created_by' => $_SESSION['user']['id'] ?? null,
        'published_at' => ($_POST['status'] ?? 'Draft') === 'Published' ? date('Y-m-d H:i:s') : null,
    ];

    if (empty($data['policy_code'])) {
        $errors[] = 'Policy Code is required.';
    }
    if (empty($data['title'])) {
        $errors[] = 'Policy Title is required.';
    }

    $existing = $db->prepare("SELECT id FROM lc_policies WHERE policy_code = :code AND version = :version LIMIT 1");
    $existing->execute([':code' => $data['policy_code'], ':version' => $data['version']]);
    if ($existing->fetch()) {
        $errors[] = "A policy with code '{$data['policy_code']}' and version '{$data['version']}' already exists.";
    }

    if (empty($errors) && $policy->createPolicy($data)) {
        $policyId = (int) $db->lastInsertId();

        $assignAll = isset($_POST['assign_all']);
        $assignByDept = isset($_POST['assign_by_department']);
        $assignByPosition = isset($_POST['assign_by_position']);
        $selectedEmployees = isset($_POST['em_employees']) && is_array($_POST['em_employees']) ? $_POST['em_employees'] : [];

        if ($assignAll && !empty($selectedEmployees)) {
            $policy->assignPolicy($policyId, $selectedEmployees, $data['acknowledgement_deadline']);
        } elseif ($assignByDept && !empty($_POST['department_ids'])) {
            $deptEmps = $db->prepare("SELECT employee_id FROM em_employees WHERE department_id = :dept_id AND employment_status = 'Active'");
            foreach ((array) $_POST['department_ids'] as $deptId) {
                $deptEmps->execute([':dept_id' => (int) $deptId]);
                $empIds = array_column($deptEmps->fetchAll(PDO::FETCH_ASSOC), 'employee_id');
                $policy->assignPolicy($policyId, $empIds, $data['acknowledgement_deadline']);
            }
        } elseif ($assignByPosition && !empty($_POST['position_ids'])) {
            $posEmps = $db->prepare("SELECT employee_id FROM em_employees WHERE position_id = :pos_id AND employment_status = 'Active'");
            foreach ((array) $_POST['position_ids'] as $posId) {
                $posEmps->execute([':pos_id' => (int) $posId]);
                $empIds = array_column($posEmps->fetchAll(PDO::FETCH_ASSOC), 'employee_id');
                $policy->assignPolicy($policyId, $empIds, $data['acknowledgement_deadline']);
            }
        } elseif (!empty($selectedEmployees)) {
            $policy->assignPolicy($policyId, $selectedEmployees, $data['acknowledgement_deadline']);
        }

        $success = true;
        echo '<script>window.location.href = "?page=policy-management";</script>';
        exit;
    }
}

?>

<link rel="stylesheet" href="/hrms-capstone/modules/compliance/css/pages/policy-create.css">

<section class="pc-module">
   <div class="pc-summary-bar">
      <div class="pc-summary-item">
         <div class="pc-summary-icon blue"><i class="bi bi-folder2-open"></i></div>
         <div>
            <div class="pc-summary-value"><?= number_format($totalPolicies ?? 0) ?></div>
            <div class="pc-summary-label">Policies</div>
            <div class="pc-summary-desc">Total policies</div>
         </div>
      </div>
      <div class="pc-summary-item">
         <div class="pc-summary-icon green"><i class="bi bi-check-circle"></i></div>
         <div>
            <div class="pc-summary-value"><?= number_format($published ?? 0) ?></div>
            <div class="pc-summary-label">Published</div>
            <div class="pc-summary-desc">Active policies</div>
         </div>
      </div>
      <div class="pc-summary-item">
         <div class="pc-summary-icon amber"><i class="bi bi-file-earmark"></i></div>
         <div>
            <div class="pc-summary-value"><?= number_format($draft ?? 0) ?></div>
            <div class="pc-summary-label">Drafts</div>
            <div class="pc-summary-desc">Pending review</div>
         </div>
      </div>
   </div>

   <div class="pc-row">
      <div class="pc-col-main">
         <div class="pc-card">
            <div class="pc-card-head">
               <h3><i class="bi bi-file-earmark-plus"></i> Create Policy</h3>
               <a href="?page=policy-management" class="pc-btn"><i class="bi bi-arrow-left"></i> Back to Policies</a>
            </div>
            <div class="pc-card-body">
               <?php if (!empty($errors)): ?>
                  <div class="alert alert-danger">
                     <ul style="margin:0; padding-left:18px;">
                        <?php foreach ($errors as $error): ?>
                           <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                     </ul>
                  </div>
               <?php endif; ?>

               <form method="post" action="">
                  <div class="pc-form-row">
                     <div class="pc-form-group">
                      <label for="policy_code">Policy Code <span style="color:red;">*</span></label>
                      <input type="text" id="policy_code" name="policy_code" class="pc-form-control" required value="<?= htmlspecialchars($_POST['policy_code'] ?? '') ?>">
                      <small style="font-size:0.78rem; color:var(--text-500,#6b7280); margin-top:4px;">Unique identifier (e.g. HR-POL-001)</small>
                     </div>
                     <div class="pc-form-group">
                      <label for="title">Policy Title <span style="color:red;">*</span></label>
                      <input type="text" id="title" name="title" class="pc-form-control" required value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                     </div>
                  </div>

                  <div class="pc-form-row">
                     <div class="pc-form-group">
                      <label for="category_id">Category</label>
                      <select id="category_id" name="category_id" class="pc-form-control">
                        <option value="">— Select Category —</option>
                        <?php foreach ($categories as $cat): ?>
                          <option value="<?= (int) $cat['id'] ?>" <?= (isset($_POST['category_id']) && (int) $_POST['category_id'] === (int) $cat['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                     </div>
                     <div class="pc-form-group">
                      <label for="version">Version</label>
                      <input type="text" id="version" name="version" class="pc-form-control" value="<?= htmlspecialchars($_POST['version'] ?? '1.0') ?>">
                     </div>
                  </div>

                  <div class="pc-form-row">
                     <div class="pc-form-group">
                      <label for="effective_date">Effective Date</label>
                      <input type="date" id="effective_date" name="effective_date" class="pc-form-control" value="<?= htmlspecialchars($_POST['effective_date'] ?? '') ?>">
                     </div>
                     <div class="pc-form-group">
                      <label for="acknowledgement_deadline">Acknowledgement Deadline</label>
                      <input type="date" id="acknowledgement_deadline" name="acknowledgement_deadline" class="pc-form-control" value="<?= htmlspecialchars($_POST['acknowledgement_deadline'] ?? '') ?>">
                     </div>
                  </div>

                  <div class="pc-form-group">
                   <label for="description">Description</label>
                   <textarea id="description" name="description" rows="2" class="pc-form-control"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                  </div>

                  <div class="pc-form-group">
                   <label for="content">Policy Content</label>
                   <textarea id="content" name="content" rows="8" class="pc-form-control"><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
                  </div>

                  <div class="pc-form-row">
                     <div class="pc-form-group">
                      <label for="status">Status</label>
                      <select id="status" name="status" class="pc-form-control">
                        <option value="Draft" <?= ($_POST['status'] ?? 'Draft') === 'Draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="For Review" <?= ($_POST['status'] ?? '') === 'For Review' ? 'selected' : '' ?>>For Review</option>
                        <option value="Approved" <?= ($_POST['status'] ?? '') === 'Approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="Published" <?= ($_POST['status'] ?? '') === 'Published' ? 'selected' : '' ?>>Published</option>
                        <option value="Archived" <?= ($_POST['status'] ?? '') === 'Archived' ? 'selected' : '' ?>>Archived</option>
                      </select>
                     </div>
                     <div class="pc-form-group" style="display:flex; align-items:flex-end;">
                      <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                        <input type="checkbox" name="requires_acknowledgement" value="1" <?= isset($_POST['requires_acknowledgement']) ? 'checked' : 'checked' ?>>
                        <span>Requires Acknowledgement</span>
                      </label>
                     </div>
                  </div>

                  <hr style="margin:20px 0; border:none; border-top:1px solid var(--border,#e4e8ee);">
                  <h4 style="margin:0 0 14px; font-size:0.98rem; font-weight:700; color:var(--text-900,#1b2430);">Policy Assignment</h4>

                  <div class="pc-form-row">
                     <div class="pc-form-group">
                      <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                        <input type="checkbox" name="assign_all" value="1" id="assignAllCheck" <?= isset($_POST['assign_all']) ? 'checked' : '' ?>>
                        <span>Assign to all active employees</span>
                      </label>
                     </div>
                     <div class="pc-form-group">
                      <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                        <input type="checkbox" name="assign_by_department" value="1" id="assignDeptCheck" <?= isset($_POST['assign_by_department']) ? 'checked' : '' ?>>
                        <span>Assign by department</span>
                      </label>
                     </div>
                     <div class="pc-form-group">
                      <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                        <input type="checkbox" name="assign_by_position" value="1" id="assignPosCheck" <?= isset($_POST['assign_by_position']) ? 'checked' : '' ?>>
                        <span>Assign by position</span>
                      </label>
                     </div>
                  </div>

                  <div class="pc-form-group" id="employeeSelectGroup">
                   <label for="em_employees">Select Employees</label>
                   <select id="em_employees" name="em_employees[]" class="pc-form-control" multiple size="8">
                       <?php foreach ($em_employees as $emp): 
                           $fullName = trim(($emp['first_name'] ?? '') . ' ' . ($emp['middle_name'] ?? '') . ' ' . ($emp['last_name'] ?? ''));
                           $selected = isset($_POST['em_employees']) && in_array((string) $emp['employee_id'], (array) $_POST['em_employees'], true);
                       ?>
                           <option value="<?= (int) $emp['employee_id'] ?>" <?= $selected ? 'selected' : '' ?>>
                               <?= htmlspecialchars($fullName) ?> — <?= htmlspecialchars($emp['department_name'] ?? '') ?> / <?= htmlspecialchars($emp['position_name'] ?? '') ?>
                           </option>
                       <?php endforeach; ?>
                   </select>
                   <small style="font-size:0.78rem; color:var(--text-500,#6b7280); margin-top:4px;">Hold Ctrl (or Cmd) to select multiple employees.</small>
                  </div>

                  <div class="pc-form-group" id="deptSelectGroup" style="display:none;">
                   <label for="department_ids">Select Departments</label>
                   <select id="department_ids" name="department_ids[]" class="pc-form-control" multiple size="6">
                       <?php foreach ($em_departments as $dept):
                           $selected = isset($_POST['department_ids']) && in_array((string) $dept['id'], (array) $_POST['department_ids'], true);
                       ?>
                           <option value="<?= (int) $dept['id'] ?>" <?= $selected ? 'selected' : '' ?>>
                               <?= htmlspecialchars($dept['department_name']) ?>
                           </option>
                       <?php endforeach; ?>
                   </select>
                   <small style="font-size:0.78rem; color:var(--text-500,#6b7280); margin-top:4px;">Hold Ctrl (or Cmd) to select multiple departments.</small>
                  </div>

                  <div class="pc-form-group" id="posSelectGroup" style="display:none;">
                   <label for="position_ids">Select Positions</label>
                   <select id="position_ids" name="position_ids[]" class="pc-form-control" multiple size="6">
                       <?php foreach ($positions as $pos):
                           $selected = isset($_POST['position_ids']) && in_array((string) $pos['position_id'], (array) $_POST['position_ids'], true);
                       ?>
                           <option value="<?= (int) $pos['position_id'] ?>" <?= $selected ? 'selected' : '' ?>>
                               <?= htmlspecialchars($pos['position_name']) ?>
                           </option>
                       <?php endforeach; ?>
                   </select>
                   <small style="font-size:0.78rem; color:var(--text-500,#6b7280); margin-top:4px;">Hold Ctrl (or Cmd) to select multiple positions.</small>
                  </div>

                  <div style="margin-top:18px; padding-top:16px; border-top:1px solid var(--border,#e4e8ee); display:flex; gap:8px; justify-content:flex-end; flex-wrap:wrap;">
                    <button type="submit" class="pc-btn primary"><i class="bi bi-check2-circle"></i> Create Policy</button>
                    <a href="?page=policy-management" class="pc-btn"><i class="bi bi-x-circle"></i> Cancel</a>
                  </div>
               </form>
            </div>
         </div>
      </div>

      <div class="pc-col-side">
         <div class="pc-card">
            <div class="pc-card-head">
               <h3><i class="bi bi-lightbulb"></i> Guidelines</h3>
            </div>
            <div class="pc-card-body">
               <div class="pc-help-item">
                  <div class="pc-help-icon"><i class="bi bi-hash"></i></div>
                  <div>
                     <div class="pc-help-title">Policy Code</div>
                     <div class="pc-help-desc">Use a unique format like HR-POL-001 to avoid duplicates.</div>
                  </div>
               </div>
               <div class="pc-help-item">
                  <div class="pc-help-icon"><i class="bi bi-calendar-check"></i></div>
                  <div>
                     <div class="pc-help-title">Effective Date</div>
                     <div class="pc-help-desc">Set when the policy takes effect. Acknowledgement deadline is optional.</div>
                  </div>
               </div>
               <div class="pc-help-item">
                  <div class="pc-help-icon"><i class="bi bi-people"></i></div>
                  <div>
                     <div class="pc-help-title">Assignment</div>
                     <div class="pc-help-desc">Assign to all employees, by department, by position, or pick individuals.</div>
                  </div>
               </div>
               <div class="pc-help-item">
                  <div class="pc-help-icon"><i class="bi bi-shield-check"></i></div>
                  <div>
                     <div class="pc-help-title">Acknowledgement</div>
                     <div class="pc-help-desc">Enable acknowledgement if employees must confirm they read the policy.</div>
                  </div>
               </div>
            </div>
         </div>

         <div class="pc-card">
            <div class="pc-card-head">
               <h3><i class="bi bi-info-circle"></i> Status Guide</h3>
            </div>
            <div class="pc-card-body">
               <div class="pc-info-grid" style="grid-template-columns:1fr; gap:8px;">
                  <div class="pc-info-item">
                   <label>Draft</label>
                   <div><span class="pc-stamp pc-stamp-draft">Draft</span></div>
                  </div>
                  <div class="pc-info-item">
                   <label>For Review</label>
                   <div><span class="pc-stamp pc-stamp-review">For Review</span></div>
                  </div>
                  <div class="pc-info-item">
                   <label>Approved</label>
                   <div><span class="pc-stamp pc-stamp-approved">Approved</span></div>
                  </div>
                  <div class="pc-info-item">
                   <label>Published</label>
                   <div><span class="pc-stamp pc-stamp-published">Published</span></div>
                  </div>
                  <div class="pc-info-item">
                   <label>Archived</label>
                   <div><span class="pc-stamp pc-stamp-archived">Archived</span></div>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>
</section>

<script>
(function() {
    const assignAll = document.getElementById('assignAllCheck');
    const assignDept = document.getElementById('assignDeptCheck');
    const assignPos = document.getElementById('assignPosCheck');
    const employeeGroup = document.getElementById('employeeSelectGroup');
    const deptGroup = document.getElementById('deptSelectGroup');
    const posGroup = document.getElementById('posSelectGroup');

    function updateVisibility() {
        const useAssignAll = assignAll.checked;
        const useDept = assignDept.checked;
        const usePos = assignPos.checked;

        employeeGroup.style.display = useAssignAll ? 'none' : 'block';
        deptGroup.style.display = useDept ? 'block' : 'none';
        posGroup.style.display = usePos ? 'block' : 'none';
    }

    if (assignAll) assignAll.addEventListener('change', updateVisibility);
    if (assignDept) assignDept.addEventListener('change', updateVisibility);
    if (assignPos) assignPos.addEventListener('change', updateVisibility);
    updateVisibility();
})();
</script>
