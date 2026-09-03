<?php
include_once __DIR__ . '/../../../auth/session.php';
require_once __DIR__ . '/../classes/Employee.php';

$employeeClass = new Employee();
$employees = $employeeClass->getEmployees();

$selectedEmployeeId = isset($_GET['employee_id']) ? (int) $_GET['employee_id'] : null;

$documentTypes = ['Government ID', 'Certificate', 'Contract', 'Resume', 'Medical', 'Other'];
$categories = ['Pre-Employment', 'Government', 'Performance', 'Training', 'Other'];
$requirementStatuses = ['Submitted', 'Missing', 'For Follow-up'];
?>

<div class="module-header">
    <h1>Document Management</h1>
    <p>Upload and track employee documents and pre-employment requirements.</p>
</div>

<div class="module-content">
    <div class="form-section">
        <label for="docs-employee-picker"><strong>Select Employee</strong></label>
        <select id="docs-employee-picker">
            <option value="">— Select an employee —</option>
            <?php foreach ($employees as $emp): ?>
                <option value="<?= (int) $emp['employee_id'] ?>" <?= $selectedEmployeeId === (int) $emp['employee_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($emp['employee_code'] . ' — ' . $emp['first_name'] . ' ' . $emp['last_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div id="docs-panel" style="display:none;">
        <div id="employee-summary-card" class="widget-card employee-summary-card"></div>

        <div class="dashboard-secondary-grid">
            <div class="widget-card">
                <h3>Uploaded Documents</h3>
                <div class="alert" style="display:none;"></div>
                <ul id="documents-list" class="document-card-list"></ul>

                <h4 class="form-subheading">Add Document</h4>
                <form id="upload-document-form" data-skip="true" enctype="multipart/form-data">
                    <input type="hidden" name="employee_id">
                    <div class="form-grid">
                        <input type="text" name="document_name" placeholder="Document Name *" required>
                        <select name="document_type">
                            <?php foreach ($documentTypes as $t): ?>
                                <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="category">
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="date" name="expiry_date" placeholder="Expiry Date (if applicable)">
                    </div>
                    <label class="file-drop-label" for="upload-document-file">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <span id="upload-file-filename">Choose a file to upload (PDF, DOC, DOCX, JPG, PNG)</span>
                    </label>
                    <input type="file" id="upload-document-file" name="file" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="file-drop-input">
                    <button type="submit" class="btn-primary">Upload Document</button>
                </form>
            </div>

            <div class="widget-card">
                <div class="widget-card-header">
                    <h3>Requirements Checklist</h3>
                    <span id="requirements-progress-label" class="progress-label"></span>
                </div>
                <div class="progress-bar-track">
                    <div id="requirements-progress-fill" class="progress-bar-fill" style="width: 0%;"></div>
                </div>
                <div class="alert" style="display:none;"></div>
                <ul id="requirements-list" class="simple-list simple-list-deletable"></ul>

                <h4 class="form-subheading">Add Requirement</h4>
                <form data-skip="true" data-action="add_requirement" class="inline-add-form">
                    <input type="hidden" name="employee_id">
                    <input type="text" name="requirement_name" placeholder="Requirement Name *" required>
                    <select name="status">
                        <?php foreach ($requirementStatuses as $s): ?>
                            <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="date" name="follow_up_date" placeholder="Follow-up Date">
                    <button type="submit" class="btn-secondary">+ Add Requirement</button>
                </form>
            </div>
        </div>
    </div>
</div>
