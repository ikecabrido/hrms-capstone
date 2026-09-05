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

if ($db instanceof PDO) {
    $policy = new Policy($db);
    $categories = $policy->getCategories();
    $em_employees = $policy->getEmployeesForAssignment();
    $em_departments = $policy->getDepartments();
    $positions = $policy->getPositions();
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
        'created_by' => $policy->getCurrentUserEmployeeId(),
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
            $deptEmps = $db->prepare("SELECT employee_id FROM em_employees WHERE department_id = :dept_id AND status = 'Active'");
            foreach ((array) $_POST['department_ids'] as $deptId) {
                $deptEmps->execute([':dept_id' => (int) $deptId]);
                $empIds = array_column($deptEmps->fetchAll(PDO::FETCH_ASSOC), 'employee_id');
                $policy->assignPolicy($policyId, $empIds, $data['acknowledgement_deadline']);
            }
        } elseif ($assignByPosition && !empty($_POST['position_ids'])) {
            $posEmps = $db->prepare("SELECT employee_id FROM em_employees WHERE position_id = :pos_id AND status = 'Active'");
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

<div class="module-content">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h2 style="margin:0;">Create Policy</h2>
        <a href="?page=policy-management" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to Policies</a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul style="margin:0; padding-left:18px;">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card">
        <form method="post" action="">
            <div class="card-body">
                <h4 class="mb-3">Policy Information</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label for="policy_code">Policy Code <span style="color:red;">*</span></label>
                        <input type="text" id="policy_code" name="policy_code" class="form-control" required value="<?= htmlspecialchars($_POST['policy_code'] ?? '') ?>">
                        <small class="form-text text-muted">Unique identifier (e.g. HR-POL-001)</small>
                    </div>
                    <div class="form-group">
                        <label for="title">Policy Title <span style="color:red;">*</span></label>
                        <input type="text" id="title" name="title" class="form-control" required value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id" class="form-control">
                            <option value="">— Select Category —</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= (int) $cat['id'] ?>" <?= (isset($_POST['category_id']) && (int) $_POST['category_id'] === (int) $cat['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="version">Version</label>
                        <input type="text" id="version" name="version" class="form-control" value="<?= htmlspecialchars($_POST['version'] ?? '1.0') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="effective_date">Effective Date</label>
                        <input type="date" id="effective_date" name="effective_date" class="form-control" value="<?= htmlspecialchars($_POST['effective_date'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="acknowledgement_deadline">Acknowledgement Deadline</label>
                        <input type="date" id="acknowledgement_deadline" name="acknowledgement_deadline" class="form-control" value="<?= htmlspecialchars($_POST['acknowledgement_deadline'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="2" class="form-control"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="content">Policy Content</label>
                    <textarea id="content" name="content" rows="8" class="form-control"><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="Draft" <?= ($_POST['status'] ?? 'Draft') === 'Draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="For Review" <?= ($_POST['status'] ?? '') === 'For Review' ? 'selected' : '' ?>>For Review</option>
                            <option value="Approved" <?= ($_POST['status'] ?? '') === 'Approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="Published" <?= ($_POST['status'] ?? '') === 'Published' ? 'selected' : '' ?>>Published</option>
                            <option value="Archived" <?= ($_POST['status'] ?? '') === 'Archived' ? 'selected' : '' ?>>Archived</option>
                        </select>
                    </div>
                    <div class="form-group" style="display:flex; align-items:flex-end;">
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                            <input type="checkbox" name="requires_acknowledgement" value="1" <?= isset($_POST['requires_acknowledgement']) ? 'checked' : 'checked' ?>>
                            <span>Requires Acknowledgement</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="card-footer" style="background:transparent; border-top:1px solid #e5e7eb; padding:16px 24px;">
                <button type="submit" class="btn btn-primary">Create Policy</button>
                <a href="?page=policy-management" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

