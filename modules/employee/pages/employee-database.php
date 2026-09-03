<?php
include_once __DIR__ . '/../../../auth/session.php';
require_once __DIR__ . '/../classes/Department.php';
require_once __DIR__ . '/../classes/Position.php';
require_once __DIR__ . '/../classes/Employee.php';

$departmentClass = new Department();
$departments = $departmentClass->getAllDepartments();

$positionClass = new Position();
$positions = $positionClass->getAllPositions();

// Summary cards — reuses the same getDashboardStats() query already used by
// the Dashboard page, no new query added.
$employeeClass = new Employee();
$summaryStats = $employeeClass->getDashboardStats();
$totalEmployees = (int) $summaryStats['total_active'] + (int) $summaryStats['total_archived'];
$departmentCount = count($summaryStats['by_department'] ?? []);

$employmentStatuses = ['Active', 'Resigned', 'Terminated', 'Probationary'];
$employmentTypes = ['Full-time', 'Part-time', 'Laboratory', 'OJT/Training'];
$civilStatuses = ['Single', 'Married', 'Divorced', 'Widowed', 'Separated'];
$graduateLevels = ['None', 'LPT', 'Masteral', 'Doctoral'];
?>

<div class="module-header">
    <h1>Employee Database</h1>
    <p>Search, add, and manage employee records.</p>
</div>

<div class="module-content">
    <div class="stat-grid stat-grid-compact">
        <div class="stat-card">
            <div class="stat-icon stat-icon-blue"><i class="fa-solid fa-users"></i></div>
            <div>
                <div class="stat-label">Total Employees</div>
                <h2 class="stat-value"><?= $totalEmployees ?></h2>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-green"><i class="fa-solid fa-user-check"></i></div>
            <div>
                <div class="stat-label">Active Employees</div>
                <h2 class="stat-value"><?= (int) $summaryStats['total_active'] ?></h2>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-gray"><i class="fa-solid fa-box-archive"></i></div>
            <div>
                <div class="stat-label">Archived Employees</div>
                <h2 class="stat-value"><?= (int) $summaryStats['total_archived'] ?></h2>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-orange"><i class="fa-solid fa-building"></i></div>
            <div>
                <div class="stat-label">Departments</div>
                <h2 class="stat-value"><?= $departmentCount ?></h2>
            </div>
        </div>
    </div>

    <div class="form-section">
        <div class="form-section-header">
            <h3>Employee List</h3>
            <button type="button" data-modal-open="add-employee-modal" class="btn-primary">+ Add Employee</button>
        </div>

        <div class="employee-filters">
            <input type="text" id="filter-keyword" placeholder="Search name or employee code…">
            <select id="filter-department">
                <option value="">All Departments</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?= (int) $d['department_id'] ?>"><?= htmlspecialchars($d['department_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select id="filter-position">
                <option value="">All Positions</option>
                <?php foreach ($positions as $p): ?>
                    <option value="<?= (int) $p['position_id'] ?>"><?= htmlspecialchars($p['position_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select id="filter-status">
                <option value="">All Statuses</option>
                <?php foreach ($employmentStatuses as $s): ?>
                    <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="button" id="filter-reset-btn" class="btn-secondary">Reset</button>
        </div>

        <div class="table-wrapper">
            <table class="user-table">
                <thead>
                    <tr>
                        <th>Employee Code</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Position</th>
                        <th>Status</th>
                        <th>Hire Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="employee-table-body">
                    <tr><td colspan="7">Loading…</td></tr>
                </tbody>
            </table>
        </div>

        <div id="employee-pagination" class="pagination-bar" style="display:none;">
            <span id="pagination-summary"></span>
            <div class="pagination-controls">
                <button type="button" id="pagination-prev" class="btn-secondary">&larr; Prev</button>
                <span id="pagination-pages"></span>
                <button type="button" id="pagination-next" class="btn-secondary">Next &rarr;</button>
            </div>
        </div>
    </div>
</div>

<!-- ─────────────── Add Employee Modal ─────────────── -->
<div id="add-employee-modal" class="modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3>Add Employee</h3>
            <button type="button" data-modal-close class="modal-close">&times;</button>
        </div>
        <div id="add-employee-message" class="alert" style="display:none;"></div>
        <form id="add-employee-form" data-skip="true">
            <div class="form-grid">
                <input type="text" name="first_name" placeholder="First Name *" required>
                <input type="text" name="middle_name" placeholder="Middle Name">
                <input type="text" name="last_name" placeholder="Last Name *" required>
                <input type="text" name="suffix" placeholder="Suffix">

                <select name="gender">
                    <option value="">Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
                <input type="date" name="birth_date" placeholder="Birth Date">
                <select name="civil_status">
                    <option value="">Civil Status</option>
                    <?php foreach ($civilStatuses as $cs): ?>
                        <option value="<?= htmlspecialchars($cs) ?>"><?= htmlspecialchars($cs) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="citizenship" placeholder="Citizenship">

                <input type="email" name="email" placeholder="Email *" required>
                <input type="text" name="mobile_no" placeholder="Mobile Number">

                <select name="department_id" required>
                    <option value="">Select Department *</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= (int) $d['department_id'] ?>"><?= htmlspecialchars($d['department_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="position_id" disabled required>
                    <option value="">Select Position *</option>
                </select>

                <input type="date" name="hire_date" placeholder="Hire Date">
                <select name="employment_status">
                    <?php foreach ($employmentStatuses as $s): ?>
                        <option value="<?= htmlspecialchars($s) ?>" <?= $s === 'Probationary' ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="employment_type">
                    <option value="">Employment Type</option>
                    <?php foreach ($employmentTypes as $t): ?>
                        <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="graduate_level">
                    <?php foreach ($graduateLevels as $g): ?>
                        <option value="<?= htmlspecialchars($g) ?>"><?= htmlspecialchars($g) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-primary">Save Employee</button>
        </form>
    </div>
</div>

<!-- ─────────────── Edit Employee Modal ─────────────── -->
<div id="edit-employee-modal" class="modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3>Edit Employee</h3>
            <button type="button" data-modal-close class="modal-close">&times;</button>
        </div>
        <div id="edit-employee-message" class="alert" style="display:none;"></div>
        <form id="edit-employee-form" data-skip="true">
            <input type="hidden" name="employee_id">
            <div class="form-grid">
                <input type="text" name="first_name" placeholder="First Name *" required>
                <input type="text" name="middle_name" placeholder="Middle Name">
                <input type="text" name="last_name" placeholder="Last Name *" required>
                <input type="text" name="suffix" placeholder="Suffix">

                <select name="gender">
                    <option value="">Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
                <input type="date" name="birth_date">
                <select name="civil_status">
                    <option value="">Civil Status</option>
                    <?php foreach ($civilStatuses as $cs): ?>
                        <option value="<?= htmlspecialchars($cs) ?>"><?= htmlspecialchars($cs) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="citizenship" placeholder="Citizenship">

                <input type="email" name="email" placeholder="Email *" required>
                <input type="text" name="mobile_no" placeholder="Mobile Number">

                <select name="department_id" required>
                    <option value="">Select Department *</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= (int) $d['department_id'] ?>"><?= htmlspecialchars($d['department_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="position_id" required>
                    <option value="">Select Position *</option>
                </select>

                <input type="date" name="hire_date">
                <select name="employment_status">
                    <?php foreach ($employmentStatuses as $s): ?>
                        <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="employment_type">
                    <option value="">Employment Type</option>
                    <?php foreach ($employmentTypes as $t): ?>
                        <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="graduate_level">
                    <?php foreach ($graduateLevels as $g): ?>
                        <option value="<?= htmlspecialchars($g) ?>"><?= htmlspecialchars($g) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-primary">Update Employee</button>
        </form>
    </div>
</div>

<!-- ─────────────── View Employee Modal (read-only) ─────────────── -->
<div id="view-employee-modal" class="modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3>Employee Details</h3>
            <button type="button" data-modal-close class="modal-close">&times;</button>
        </div>
        <div id="view-employee-body" class="view-employee-body">
            <p class="empty-item">Loading…</p>
        </div>

        <div class="form-section-header">
            <h3>Employment / Contract</h3>
            <button type="button" id="btn-open-renew-contract" class="btn-primary" data-id="">Renew Contract</button>
        </div>
        <div id="view-employee-contract" class="view-employee-body">
            <p class="empty-item">Loading…</p>
        </div>

        <div class="form-section-header">
            <h3>Contract Renewal History</h3>
        </div>
        <div class="table-wrapper">
            <table class="user-table">
                <thead>
                    <tr>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Employment Type</th>
                        <th>Salary</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="view-employee-contract-history">
                    <tr><td colspan="5">Loading…</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ─────────────── Renew Contract Modal ─────────────── -->
<div id="renew-contract-modal" class="modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3>Renew Contract</h3>
            <button type="button" data-modal-close class="modal-close">&times;</button>
        </div>
        <div id="renew-contract-message" class="alert" style="display:none;"></div>
        <div id="renew-contract-current" class="view-employee-body"></div>
        <form id="renew-contract-form" data-skip="true" enctype="multipart/form-data">
            <input type="hidden" name="employee_id">
            <div class="form-grid">
                <input type="date" name="contract_start_date" placeholder="New Contract Start Date *" required>
                <input type="date" name="contract_end_date" placeholder="New Contract End Date *" required>
                <select name="employment_type">
                    <option value="">Employment Type</option>
                    <?php foreach ($employmentTypes as $t): ?>
                        <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" step="0.01" min="0" name="salary" placeholder="Negotiated Salary">
                <textarea name="remarks" placeholder="Renewal remarks"></textarea>
                <input type="file" name="contract_document" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
            </div>
            <button type="submit" class="btn-primary">Save Renewal</button>
        </form>
    </div>
</div>
