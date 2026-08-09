<?php
include_once __DIR__ . '/../../../auth/session.php';
include __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Employee.php';
include __DIR__ . '/../classes/Department.php';
include __DIR__ . '/../classes/Position.php';
include __DIR__ . '/../classes/Role.php';
include __DIR__ . '/../../../auth/guard.php';

/*Employee Class*/
$employeeClass = new Employee();
$employees = $employeeClass->getEmployees();

/*Department Class*/
$departmentsClass = new Department();
$departments = $departmentsClass->getAllDepartments();

/*Position Class*/
$positionClass = new Position();
$positions = $positionClass->getAllPositions();

/*Role Class*/
$roleClass = new Role();
$roles = $roleClass->getRoles();
?>

<div class="module-header">
    <h1>User Management</h1>
    <p>Add and Manage Users within the system.</p>
</div>
<div class="module-content">
    <div class="tab-container">
        <ul class="tab-list">
            <li class="tab-item active" data-tab="employee-list">Employee List</li>
            <li class="tab-item" data-tab="employee-registration">Employee Registration</li>
            <li class="tab-item" data-tab="employee-management">Employee Management</li>
        </ul>

        <div id="employee-list" class="tab-content active">
            <div class="form-section">
                    <div class="form-section-header">
                        <h3>Employee List</h3>
                        <div class="employee-filters">
                            <select id="filter-department">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= htmlspecialchars($dept['department_name']); ?>">
                                        <?= htmlspecialchars($dept['department_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <select id="filter-position">
                                <option value="">All Positions</option>
                                <?php foreach ($positions as $pos): ?>
                                    <option value="<?= htmlspecialchars($pos['position_name']); ?>">
                                        <?= htmlspecialchars($pos['position_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button id="filter-reset-btn">Reset</button>
                        </div>
                    </div>
                <div class="table-wrapper">
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th>First Name</th>
                                <th>Middle Name</th>
                                <th>Last Name</th>
                                <th>Department</th>
                                <th>Position</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="employee-table-body">
                            <?php if (!empty($employees)): ?>
                                <?php foreach ($employees as $e): ?>
                                    <tr class="employee-row">
                                        <td><?= htmlspecialchars($e['first_name'] ?? '—'); ?></td>
                                        <td><?= htmlspecialchars($e['middle_name'] ?? '—'); ?></td>
                                        <td><?= htmlspecialchars($e['last_name'] ?? '—'); ?></td>
                                        <td><?= htmlspecialchars($e['department_name'] ?? $e['department'] ?? '—'); ?></td>
                                        <td><?= htmlspecialchars($e['position_name'] ?? $e['position'] ?? '—'); ?></td>
                                        <td><?= htmlspecialchars(isset($e['status']) && $e['status'] !== '' ? ucfirst(strtolower($e['status'])) : '—'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6">No employees found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="pagination-controls">
                    <button id="emp-prev-btn" disabled>&laquo; Prev</button>
                    <span id="emp-page-info"></span>
                    <button id="emp-next-btn">Next &raquo;</button>
                </div>
            </div>
        </div>

        <div id="employee-registration" class="tab-content">
            <div class="form-section">
                <h3>Employee Registration</h3>
                <div id="registration-message" class="alert" style="display:none;"></div>
                <form id="add-user-form" method="POST" action="">
                    <div class="registration-form-section">
                        <select name="employee" id="employee">
                            <option value="default">Select Employee</option>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?= $employee['employee_id']; ?>">
                                        <?= $employee['first_name'] . ' ' . $employee['last_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                        </select>

                        <select name="department" id="department">
                            <option value="default">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['department_id']; ?>">
                                        <?= $dept['department_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                        </select>

                        <select name="position" id="position" disabled>
                            <option value="">Select Position</option>
                        </select>
                        <input type="text" id="role" name="role" disabled placeholder="Role">
                        <input type="password" id="password" name="password" placeholder="Password">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password">
                        <button class="register-btn" type="submit">Register Employee</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="employee-management" class="tab-content">
            <div class="form-section">
                <h3>Employee Management</h3>
                <div id="management-message" class="alert" style="display:none;"></div>
                <form id="manage-employee-form" action="">
                    <div class="registration-form-section">

                        <!-- Step 1: Pick employee -->
                        <select name="employee_id" id="manage-employee">
                            <option value="default">Select Employee</option>
                            <?php foreach ($employees as $employee): ?>
                                <option value="<?= $employee['employee_id']; ?>">
                                    <?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <!-- Auto-filled details -->
                        <input type="text" id="manage-fullname" placeholder="Full Name" disabled>

                        <select name="department" id="manage-department" disabled>
                            <option value="default">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['department_id']; ?>">
                                    <?= htmlspecialchars($dept['department_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select name="position" id="manage-position" disabled>
                            <option value="">Select Position</option>
                        </select>

                        <input type="text" id="manage-role" name="role" placeholder="Role" disabled>

                        <button class="register-btn" type="submit" id="manage-submit-btn" disabled>
                            Update Employee
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>