<?php
require_once __DIR__ . '/../Controller/PatientController.php';

$patientController = new PatientController();
$patientAccessError = '';
try {
    $patientController->authorize();
} catch (Throwable $exception) {
    $patientAccessError = $exception->getMessage();
}
?>

<link rel="stylesheet" href="css/pages/employee-patient.css">

<section class="patient-module">
    <div class="module-header">
        <div>
            <h1>Employee / Patient Module</h1>
            <p>Register employees as clinic patients, review profile details, and manage patient status.</p>
        </div>
    </div>

    <?php if ($patientAccessError !== ''): ?>
        <div class="module-message error" role="alert" hidden><?= htmlspecialchars($patientAccessError) ?></div>
    <?php else: ?>
        <div id="moduleMessage" class="module-message" role="alert" hidden></div>

        <div class="patient-module-layout">
            <section class="patient-panel wide-panel">
                <div class="panel-header">
                    <h2>Existing Employees</h2>
                    <button type="button" id="refreshEmployeeSearch" class="ghost-btn"><i class="fa-solid fa-rotate-right"></i> Refresh</button>
                </div>
                <label for="employeePatientSearch" class="sr-only">Search employee</label>
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search" id="employeePatientSearch" placeholder="Search by employee ID, name, email or position">
                </div>
                <div id="employeeSearchResults" class="entity-list">
                    <div class="empty-state"><i class="fa-solid fa-user-plus"></i><p>Loading employees...</p></div>
                </div>
            </section>

            <section class="patient-panel wide-panel">
                <div class="panel-header">
                    <h2>Employee Summary</h2>
                    <span id="employeeStatusBadge" class="state-badge neutral">No employee selected</span>
                </div>
                <div id="selectedEmployeeCard" class="info-card">
                    <div class="empty-state compact"><i class="fa-solid fa-user-check"></i><p>Select an employee to view details.</p></div>
                </div>
                <div class="action-row">
                    <button type="button" id="registerEmployeePatientBtn" class="primary-btn" disabled><i class="fa-solid fa-user-plus"></i> Register as Patient</button>
                    <button type="button" id="clearSelectionBtn" class="secondary-btn"><i class="fa-solid fa-broom"></i> Clear</button>
                </div>
            </section>

            <section class="patient-panel wide-panel detail-panel">
                <div class="panel-header">
                    <h2>Patient List</h2>
                    <button type="button" id="refreshPatientList" class="ghost-btn"><i class="fa-solid fa-rotate-right"></i> Refresh</button>
                </div>

                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search" id="patientListSearch" placeholder="Search patient records">
                </div>
                <div id="patientListResults" class="entity-list">
                    <div class="empty-state"><i class="fa-solid fa-user-injured"></i><p>Loading patient records...</p></div>
                </div>
            </section>
        </div>

        <section class="patient-panel">
            <div class="panel-header">
                <h2>Patient Profile</h2>
            </div>

            <form id="patientRegistrationForm" class="patient-form">
                <div class="form-grid">
                    <div>
                        <label for="patient_id">Patient ID</label>
                        <input id="patient_id" name="patient_id" type="text" readonly>
                    </div>
                    <div>
                        <label for="employee_id">Employee ID</label>
                        <input id="employee_id" name="employee_id" type="number" min="1" readonly>
                    </div>
                    <div>
                        <label for="patient_type">Patient Type</label>
                        <select id="patient_type" name="patient_type">
                            <option value="Staff">Staff</option>
                            <option value="Student">Student</option>
                            <option value="Faculty">Faculty</option>
                            <option value="Visitor">Visitor</option>
                        </select>
                    </div>

                    <div>
                        <label for="first_name">First Name</label>
                        <input id="first_name" name="first_name" type="text" required>
                    </div>
                    <div>
                        <label for="last_name">Last Name</label>
                        <input id="last_name" name="last_name" type="text" required>
                    </div>
                    <div>
                        <label for="middle_name">Middle Name</label>
                        <input id="middle_name" name="middle_name" type="text">
                    </div>

                    <div>
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email">
                    </div>
                    <div>
                        <label for="phone">Contact Number</label>
                        <input id="phone" name="phone" type="tel">
                    </div>
                    <div>
                        <label for="birth_date">Birth Date</label>
                        <input id="birth_date" name="birth_date" type="date">
                    </div>

                    <div>
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label for="blood_type">Blood Type</label>
                        <input id="blood_type" name="blood_type" type="text" maxlength="10">
                    </div>
                    <div>
                        <label for="status">Patient Status</label>
                        <select id="status" name="status">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="full-width">
                        <label for="address">Address</label>
                        <input id="address" name="address" type="text">
                    </div>
                    <div class="full-width">
                        <label for="allergies">Allergies</label>
                        <textarea id="allergies" name="allergies" placeholder="List the patient's allergies or enter N/A"></textarea>
                    </div>
                    <div class="full-width">
                        <label for="medical_conditions">Medical Conditions</label>
                        <textarea id="medical_conditions" name="medical_conditions" placeholder="List relevant medical conditions"></textarea>
                    </div>
                    <div class="full-width">
                        <label for="current_medications">Current Medications</label>
                        <textarea id="current_medications" name="current_medications" placeholder="List medications currently being taken"></textarea>
                    </div>
                </div>

                <div class="detail-actions">
                    <button type="button" id="savePatientBtn" class="primary-btn" disabled><i class="fa-solid fa-floppy-disk"></i> Save</button>
                    <button type="button" id="deactivatePatientBtn" class="danger-btn" disabled><i class="fa-solid fa-user-slash"></i> Deactivate</button>
                    <button type="button" class="ghost-btn" data-reset-form><i class="fa-solid fa-xmark"></i> Cancel</button>
                </div>
            </form>
        </section>
    <?php endif; ?>
</section>
