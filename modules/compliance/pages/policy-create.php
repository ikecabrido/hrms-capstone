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

    try {
        $stmt = $db->query("SELECT policy_code FROM lc_policies WHERE policy_code IS NOT NULL AND policy_code <> ''");
        $codes = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'policy_code');
        $nextNum = 1;
        foreach ($codes as $code) {
            if (preg_match('/(\d+)$/', $code, $m)) {
                $nextNum = max($nextNum, (int) $m[1] + 1);
            }
        }
        $autoPolicyCode = 'HR-POL-' . str_pad((string) $nextNum, 3, '0', STR_PAD_LEFT);
    } catch (Throwable $e) {
        $autoPolicyCode = 'HR-POL-001';
    }
} else {
    $autoPolicyCode = 'HR-POL-001';
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
        $data['policy_code'] = $autoPolicyCode;
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

<link rel="stylesheet" href="/hrms-capstone/modules/compliance/css/pages/policy-create.css?v=2">

<style>
:root {
    --pc-bg: #f4f5f7;
    --pc-card: #ffffff;
    --pc-border: #e1e4e8;
    --pc-border-light: #e8eaed;
    --pc-text: #2f3439;
    --pc-muted: #737b83;
    --pc-primary: #2f6fa8;
    --pc-primary-hover: #285f91;
    --pc-success: #3f8053;
    --pc-danger: #b34b4b;
    --pc-radius: 6px;
}
</style>

<section class="pc-module">
   <div class="pc-row">
      <div class="pc-col-side">
          <div class="pc-card">
             <div class="pc-card-head">
                <div class="pc-card-head-content">
                   <h3>Guidelines</h3>
                </div>
             </div>
             <div class="pc-card-body">
                <div class="pc-help-item">
                   <div class="pc-help-icon">#</div>
                   <div>
                      <div class="pc-help-title">Policy Code</div>
                      <div class="pc-help-desc">Use a unique format like HR-POL-001 to avoid duplicates.</div>
                   </div>
                </div>
                <div class="pc-help-item">
                   <div class="pc-help-icon">✓</div>
                   <div>
                      <div class="pc-help-title">Effective Date</div>
                      <div class="pc-help-desc">Set when the policy takes effect. Acknowledgement deadline is optional.</div>
                   </div>
                </div>
                <div class="pc-help-item">
                   <div class="pc-help-icon">#</div>
                   <div>
                      <div class="pc-help-title">Assignment</div>
                      <div class="pc-help-desc">Assign to all employees, by department, by position, or pick individuals.</div>
                   </div>
                </div>
                <div class="pc-help-item">
                   <div class="pc-help-icon">✓</div>
                   <div>
                      <div class="pc-help-title">Acknowledgement</div>
                      <div class="pc-help-desc">Enable acknowledgement if employees must confirm they read the policy.</div>
                   </div>
                </div>
             </div>
          </div>

          <div class="pc-card-divider"></div>

          <div class="pc-card">
             <div class="pc-card-head">
                <div class="pc-card-head-content">
                   <h3>Before You Submit</h3>
                </div>
             </div>
             <div class="pc-card-body">
                <div class="pc-submit-guide-item">
                   <label>Unique Policy Code</label>
                   <div>Ensure no duplicate code/version exists. Format: HR-POL-001. Auto-generated if left empty.</div>
                </div>
                <div class="pc-submit-guide-item">
                   <label>Effective Date</label>
                   <div>Set the date when this policy takes effect. Acknowledgement deadline is optional.</div>
                </div>
                <div class="pc-submit-guide-item">
                   <label>Status Selection</label>
                   <div>Use Draft for work-in-progress. Published makes it active and visible to assigned employees.</div>
                </div>
                <div class="pc-submit-guide-item">
                   <label>Acknowledgement</label>
                   <div>Enable if employees must confirm they have read and understood the policy.</div>
                </div>
             </div>
          </div>
      </div>

      <div class="pc-col-main">
         <div class="pc-card">
            <div class="pc-card-head">
               <div class="pc-card-head-content">
                  <h3>Create Policy</h3>
               </div>
                <nav class="pc-breadcrumb" aria-label="breadcrumb">
                  <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="?page=policy-management">Policies</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Create Policy</li>
                  </ol>
                </nav>
            </div>
            <div class="pc-card-body">
               <?php if (!empty($errors)): ?>
                   <div class="alert alert-danger" style="padding:10px 12px; border-radius:4px; font-size:11.5px; margin-bottom:12px; background:#fdf6f6; color:var(--pc-danger); border:1px solid #f5c6c6;">
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
                      <label for="policy_code">Policy Code <span style="color:var(--pc-danger);">*</span></label>
                      <input type="text" id="policy_code" name="policy_code" class="pc-form-control" required value="<?= htmlspecialchars($_POST['policy_code'] ?? $autoPolicyCode) ?>">
                      <small style="font-size:10.5px; color:var(--pc-muted); margin-top:4px; line-height:1.35;">Unique identifier (e.g. HR-POL-001)</small>
                     </div>
                     <div class="pc-form-group">
                      <label for="title">Policy Title <span style="color:var(--pc-danger);">*</span></label>
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
                       <input type="date" id="effective_date" name="effective_date" class="pc-form-control" min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($_POST['effective_date'] ?? '') ?>">
                      </div>
                      <div class="pc-form-group">
                       <label for="acknowledgement_deadline">Acknowledgement Deadline</label>
                       <input type="date" id="acknowledgement_deadline" name="acknowledgement_deadline" class="pc-form-control" min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($_POST['acknowledgement_deadline'] ?? '') ?>">
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
                       <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:12.5px; color:var(--pc-text);">
                         <input type="checkbox" name="requires_acknowledgement" value="1" <?= isset($_POST['requires_acknowledgement']) ? 'checked' : 'checked' ?> style="width:14px; height:14px; accent-color:var(--pc-primary);">
                         <span>Requires Acknowledgement</span>
                       </label>
                      </div>
                   </div>

                   <div style="margin-top:18px; padding-top:16px; border-top:1px solid var(--pc-border-light); display:flex; gap:8px; justify-content:flex-end; flex-wrap:wrap;">
                     <button type="submit" class="pc-btn primary">Create Policy</button>
                     <a href="?page=policy-management" class="pc-btn">Cancel</a>
                   </div>
                </form>
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
        const useAssignAll = assignAll ? assignAll.checked : false;
        const useDept = assignDept ? assignDept.checked : false;
        const usePos = assignPos ? assignPos.checked : false;

        if (employeeGroup) employeeGroup.style.display = useAssignAll ? 'none' : 'block';
        if (deptGroup) deptGroup.style.display = useDept ? 'block' : 'none';
        if (posGroup) posGroup.style.display = usePos ? 'block' : 'none';
    }

    if (assignAll) assignAll.addEventListener('change', updateVisibility);
    if (assignDept) assignDept.addEventListener('change', updateVisibility);
    if (assignPos) assignPos.addEventListener('change', updateVisibility);
    updateVisibility();
})();
</script>
