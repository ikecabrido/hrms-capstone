<?php
require_once __DIR__ . '/../Controller/EmergencyCaseController.php';

$emergencyCaseController = new EmergencyCaseController();
$emergencyAccessError = '';
try {
    $emergencyCaseController->authorize();
} catch (Throwable $exception) {
    $emergencyAccessError = $exception->getMessage();
}

$emergencyJsFile = __DIR__ . '/../js/pages/emergency-cases.js';
$emergencyJsVersion = @file_exists($emergencyJsFile) ? @filemtime($emergencyJsFile) : '1';
?>

<div class="emergency-cases-module module-content">
    <div class="module-header">
        <div>
            <h1>Emergency Cases</h1>
            <p>Record, track, and manage urgent employee and patient emergencies.</p>
        </div>
        <button type="button" id="addEmergencyCaseBtn" class="primary-btn"><i class="fa-solid fa-plus"></i> Add Emergency Case</button>
    </div>

    <?php if ($emergencyAccessError !== ''): ?>
        <div class="module-message error" role="alert" hidden><?= htmlspecialchars($emergencyAccessError) ?></div>
    <?php else: ?>
        <div id="emergencyCaseMessage" class="module-message" role="alert" hidden></div>

        <div class="summary-grid">
            <div class="summary-card">
                <span class="summary-label">Total Cases</span>
                <span id="emergencyTotalCases" class="summary-value">0</span>
                <span class="summary-meta">All emergency records</span>
            </div>
            <div class="summary-card">
                <span class="summary-label">Active</span>
                <span id="emergencyActiveCases" class="summary-value">0</span>
                <span class="summary-meta">Current active incidents</span>
            </div>
            <div class="summary-card">
                <span class="summary-label">Critical</span>
                <span id="emergencyCriticalCases" class="summary-value">0</span>
                <span class="summary-meta">High-priority cases</span>
            </div>
            <div class="summary-card">
                <span class="summary-label">Closed</span>
                <span id="emergencyClosedCases" class="summary-value">0</span>
                <span class="summary-meta">Resolved or closed</span>
            </div>
        </div>

        <div class="toolbar-row">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" id="emergencyCaseSearch" placeholder="Search by case ID, patient, employee, department or complaint">
            </div>
            <div class="filter-box">
                <select id="emergencyStatusFilter">
                    <option value="">All Status</option>
                    <option value="Active">Active</option>
                    <option value="Open">Open</option>
                    <option value="Resolved">Resolved</option>
                    <option value="Transferred">Transferred</option>
                    <option value="Closed">Closed</option>
                </select>
            </div>
            <div class="filter-box">
                <select id="emergencySeverityFilter">
                    <option value="">All Severity</option>
                    <option value="Minor">Minor</option>
                    <option value="Low">Low</option>
                    <option value="Medium">Medium</option>
                    <option value="High">High</option>
                    <option value="Critical">Critical</option>
                </select>
            </div>
            <div class="filter-box">
                <select id="emergencyTypeFilter">
                    <option value="">All Types</option>
                    <option value="Accident">Accident</option>
                    <option value="Medical Emergency">Medical Emergency</option>
                    <option value="Injury">Injury</option>
                    <option value="Illness">Illness</option>
                    <option value="Fainting">Fainting</option>
                    <option value="Allergic Reaction">Allergic Reaction</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="filter-box">
                <select id="emergencyDepartmentFilter">
                    <option value="">All Departments</option>
                    <option value="Administration">Administration</option>
                    <option value="Human Resources">Human Resources</option>
                    <option value="Finance">Finance</option>
                    <option value="IT">IT</option>
                    <option value="Academics">Academics</option>
                    <option value="Nursing">Nursing</option>
                    <option value="Clinic">Clinic</option>
                    <option value="Security">Security</option>
                    <option value="Maintenance">Maintenance</option>
                </select>
            </div>
            <div class="filter-box">
                <select id="emergencySort" aria-label="Sort emergency cases">
                    <option value="date">Sort: Date</option>
                    <option value="employee">Sort: Employee</option>
                    <option value="status">Sort: Status</option>
                </select>
            </div>
        </div>

        <div class="toolbar-row">
            <div class="date-field">
                <label for="emergencyDateFrom">From</label>
                <input type="date" id="emergencyDateFrom">
            </div>
            <div class="date-field">
                <label for="emergencyDateTo">To</label>
                <input type="date" id="emergencyDateTo">
            </div>
            <div class="filter-box">
                <button type="button" id="refreshEmergencyCasesBtn" class="ghost-btn"><i class="fa-solid fa-rotate-right"></i> Refresh</button>
            </div>
        </div>

        <div class="table-card">
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Case ID</th>
                            <th>Employee / Patient</th>
                            <th>Employee ID</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Emergency Type</th>
                            <th>Date &amp; Time</th>
                            <th>Severity</th>
                            <th>Status</th>
                            <th>Attending Staff</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="emergencyCaseTableBody">
                        <tr>
                            <td colspan="11" class="empty-state"><p>Loading emergency cases...</p></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

<div id="emergencyCaseModal" class="modal" hidden>
    <div class="modal-dialog">
        <div class="modal-header">
            <div>
                <h3 id="emergencyModalTitle">Add New Case</h3>
                <p class="modal-subtitle">Record and manage urgent employee and patient emergencies</p>
            </div>
            <button type="button" id="closeEmergencyCaseModal" class="close-btn" aria-label="Close">&times;</button>
        </div>

        <form id="emergencyCaseForm" class="modal-body">
            <input type="hidden" name="case_id">
            <input type="hidden" name="patient_id">

            <!-- PATIENT SELECTION SECTION -->
            <div class="form-section">
                <div class="section-header">
                    <i class="fa-solid fa-user-injured"></i>
                    <h4>Select Patient / Employee</h4>
                </div>
                <div class="section-content">
                    <div class="patient-search-container">
                        <div class="search-input-wrapper">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input id="patientLookup" type="search" placeholder="Search by name, ID, department, or position">
                        </div>
                        <button type="button" class="secondary-btn" id="searchPatientBtn"><i class="fa-solid fa-search"></i> Find Patient</button>
                    </div>

                    <div id="patientLookupResults" class="patient-search-results"></div>

                    <div class="selected-patient-display">
                        <div class="patient-info-card">
                            <div class="patient-info-header">
                                <i class="fa-solid fa-user-circle"></i>
                                <div>
                                    <span class="patient-info-label">Selected Patient</span>
                                    <p id="selectedPatientName" class="patient-info-value">No patient selected</p>
                                </div>
                            </div>
                        </div>
                        <div class="patient-info-card">
                            <div class="patient-info-header">
                                <i class="fa-solid fa-briefcase"></i>
                                <div>
                                    <span class="patient-info-label">Employee Information</span>
                                    <div id="selectedPatientDetails" class="patient-details-content">
                                        <p class="text-muted">Select a patient to view employee and department information</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- EMERGENCY INFORMATION SECTION -->
            <div class="form-section">
                <div class="section-header">
                    <i class="fa-solid fa-exclamation-triangle"></i>
                    <h4>Emergency Information</h4>
                </div>
                <div class="section-content">
                    <div class="form-grid">
                        <div>
                            <label for="incident_date">Incident Date &amp; Time <span class="required">*</span></label>
                            <input id="incident_date" name="incident_date" type="datetime-local" required>
                        </div>
                        <div>
                            <label for="incident_type">Emergency Type</label>
                            <select id="incident_type" name="incident_type">
                                <option value="Accident">Accident</option>
                                <option value="Medical Emergency">Medical Emergency</option>
                                <option value="Injury">Injury</option>
                                <option value="Illness">Illness</option>
                                <option value="Fainting">Fainting</option>
                                <option value="Allergic Reaction">Allergic Reaction</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label for="severity_level">Severity Level</label>
                            <select id="severity_level" name="severity_level">
                                <option value="Minor">Minor</option>
                                <option value="Low">Low</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="High">High</option>
                                <option value="Critical">Critical</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="full-width">
                            <label for="chief_complaint">Chief Complaint / Symptoms <span class="required">*</span></label>
                            <textarea id="chief_complaint" name="chief_complaint" placeholder="Describe the main symptoms or reason for emergency" required></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MEDICAL RESPONSE SECTION -->
            <div class="form-section">
                <div class="section-header">
                    <i class="fa-solid fa-stethoscope"></i>
                    <h4>Medical Response</h4>
                </div>
                <div class="section-content">
                    <div class="form-grid">
                        <div class="full-width">
                            <label for="initial_assessment">Initial Assessment</label>
                            <textarea id="initial_assessment" name="initial_assessment" placeholder="Preliminary examination findings and observations"></textarea>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="full-width">
                            <label for="treatment_provided">Treatment / First Aid Provided</label>
                            <textarea id="treatment_provided" name="treatment_provided" placeholder="Document immediate treatment, first aid, or interventions applied"></textarea>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div>
                            <label for="attending_staff">Attending Medical Staff <span class="required">*</span></label>
                            <input id="attending_staff" name="attending_staff" type="text" placeholder="Doctor or medical staff name" required>
                        </div>
                        <div>
                            <label for="case_status">Case Status</label>
                            <select id="case_status" name="case_status">
                                <option value="Active">Active</option>
                                <option value="Open">Open</option>
                                <option value="Resolved">Resolved</option>
                                <option value="Transferred">Transferred</option>
                                <option value="Closed">Closed</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- EMERGENCY ACTIONS SECTION -->
            <div class="form-section">
                <div class="section-header">
                    <i class="fa-solid fa-list-check"></i>
                    <h4>Emergency Actions</h4>
                </div>
                <div class="section-content">
                    <div class="checkboxes-grid">
                        <label class="checkbox-item">
                            <input type="checkbox" name="ambulance_called">
                            <span><i class="fa-solid fa-ambulance"></i> Ambulance called</span>
                        </label>
                        <label class="checkbox-item">
                            <input type="checkbox" name="parents_notified">
                            <span><i class="fa-solid fa-phone"></i> Parents notified</span>
                        </label>
                        <label class="checkbox-item">
                            <input type="checkbox" name="follow_up_required">
                            <span><i class="fa-solid fa-calendar-check"></i> Follow-up required</span>
                        </label>
                    </div>

                    <div class="form-grid">
                        <div>
                            <label for="ambulance_arrival_time">Ambulance Arrival</label>
                            <input id="ambulance_arrival_time" name="ambulance_arrival_time" type="datetime-local">
                        </div>
                        <div>
                            <label for="parent_notification_time">Parent Notification Time</label>
                            <input id="parent_notification_time" name="parent_notification_time" type="datetime-local">
                        </div>
                        <div>
                            <label for="follow_up_date">Follow-up Date</label>
                            <input id="follow_up_date" name="follow_up_date" type="date">
                        </div>
                    </div>
                </div>
            </div>

            <!-- ADDITIONAL INFORMATION SECTION -->
            <div class="form-section">
                <div class="section-header">
                    <i class="fa-solid fa-file-lines"></i>
                    <h4>Additional Information</h4>
                </div>
                <div class="section-content">
                    <div class="form-grid">
                        <div>
                            <label for="contact_person">Contact Person</label>
                            <input id="contact_person" name="contact_person" type="text" placeholder="Name of emergency contact">
                        </div>
                        <div>
                            <label for="contact_phone">Contact Phone</label>
                            <input id="contact_phone" name="contact_phone" type="tel" placeholder="Emergency contact number">
                        </div>
                        <div>
                            <label for="transfer_hospital">Transfer Hospital</label>
                            <input id="transfer_hospital" name="transfer_hospital" type="text" placeholder="If transferred to another facility">
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="full-width">
                            <label for="witness_names">Witness Names</label>
                            <input id="witness_names" name="witness_names" type="text" placeholder="Names of witnesses to the incident">
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="full-width">
                            <label for="notes">Additional Notes / Remarks</label>
                            <textarea id="notes" name="notes" placeholder="Any additional observations, notes, or remarks about the emergency case"></textarea>
                        </div>
                    </div>
                </div>
            </div>

        </form>

        <div class="modal-actions">
            <button type="button" id="cancelEmergencyCaseBtn" class="secondary-btn"><i class="fa-solid fa-xmark"></i> Cancel</button>
            <button type="button" id="saveEmergencyCaseBtn" class="primary-btn"><i class="fa-solid fa-floppy-disk"></i> Save Emergency Case</button>
        </div>
    </div>
    <div class="modal-helper" role="note"><i class="fa-solid fa-circle-info"></i> All fields marked with <strong>*</strong> are required.</div>
</div>
</div>
<script src="js/pages/emergency-cases.js?v=<?= $emergencyJsVersion ?>" defer></script>
