<?php
include_once __DIR__ . '/../../../auth/session.php';
require_once __DIR__ . '/../classes/Employee.php';

$employeeClass = new Employee();
$employees = $employeeClass->getEmployees();

$selectedEmployeeId = isset($_GET['employee_id']) ? (int) $_GET['employee_id'] : null;
?>

<div class="module-header">
    <h1>Personal Information</h1>
    <p>View and manage an employee's full profile: personal details, family background,
        government IDs, dependents, emergency contacts, education, certifications, skills,
        languages, and work experience.</p>
</div>

<div class="module-content">
    <div class="form-section">
        <label for="profile-employee-picker"><strong>Select Employee</strong></label>
        <select id="profile-employee-picker">
            <option value="">— Select an employee —</option>
            <?php foreach ($employees as $emp): ?>
                <option value="<?= (int) $emp['employee_id'] ?>" <?= $selectedEmployeeId === (int) $emp['employee_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($emp['employee_code'] . ' — ' . $emp['first_name'] . ' ' . $emp['last_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div id="profile-panel" style="display:none;">
        <div class="tab-container">
            <ul class="tab-list">
                <li class="tab-item active" data-tab="tab-personal">Personal Info</li>
                <li class="tab-item" data-tab="tab-family">Family Background</li>
                <li class="tab-item" data-tab="tab-govids">Government IDs</li>
                <li class="tab-item" data-tab="tab-dependents">Dependents</li>
                <li class="tab-item" data-tab="tab-emergency">Emergency Contacts</li>
                <li class="tab-item" data-tab="tab-education">Education</li>
                <li class="tab-item" data-tab="tab-certifications">Certifications</li>
                <li class="tab-item" data-tab="tab-skills">Skills</li>
                <li class="tab-item" data-tab="tab-languages">Languages</li>
                <li class="tab-item" data-tab="tab-workexp">Work Experience</li>
            </ul>

            <!-- Personal Info -->
            <div id="tab-personal" class="tab-content active">
                <div class="form-section">
                    <h3>Personal Information</h3>
                    <div id="personal-info-alert" class="alert" style="display:none;"></div>
                    <form id="personal-info-form" data-skip="true" data-action="save_personal_information">
                        <input type="hidden" name="employee_id">
                        <div class="form-grid">
                            <input type="date" name="birth_date" placeholder="Birth Date">
                            <select name="gender">
                                <option value="">Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                            <input type="text" name="birth_place" placeholder="Birth Place">
                            <select name="civil_status">
                                <option value="">Civil Status</option>
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Divorced">Divorced</option>
                                <option value="Widowed">Widowed</option>
                                <option value="Separated">Separated</option>
                            </select>
                            <input type="text" name="citizenship" placeholder="Citizenship">
                            <input type="text" name="religion" placeholder="Religion">
                            <input type="text" name="blood_type" placeholder="Blood Type">
                            <input type="text" name="height" placeholder="Height">
                            <input type="text" name="weight" placeholder="Weight">
                            <input type="text" name="spouse_name" placeholder="Spouse Name">
                            <input type="text" name="spouse_occupation" placeholder="Spouse Occupation">
                            <input type="text" name="father_name" placeholder="Father's Name">
                            <input type="text" name="father_occupation" placeholder="Father's Occupation">
                            <input type="text" name="mother_name" placeholder="Mother's Name">
                            <input type="text" name="mother_occupation" placeholder="Mother's Occupation">
                            <input type="text" name="emergency_contact_name" placeholder="Emergency Contact Name">
                            <input type="text" name="emergency_contact_relationship" placeholder="Emergency Contact Relationship">
                            <input type="text" name="emergency_contact_number" placeholder="Emergency Contact Number">
                            <input type="text" name="disability_info" placeholder="Disability Info (if any)">
                            <textarea name="current_address" placeholder="Current Address"></textarea>
                            <textarea name="permanent_address" placeholder="Permanent Address"></textarea>
                        </div>
                        <button type="submit" class="btn-primary">Save Personal Information</button>
                    </form>
                </div>
            </div>

            <!-- Family Background -->
            <div id="tab-family" class="tab-content">
                <div class="form-section">
                    <h3>Family Background</h3>
                    <div class="alert" style="display:none;"></div>
                    <form id="family-background-form" data-skip="true" data-action="save_family_background">
                        <input type="hidden" name="employee_id">
                        <div class="form-grid">
                            <input type="text" name="father_name" placeholder="Father's Name">
                            <input type="text" name="father_occupation" placeholder="Father's Occupation">
                            <input type="text" name="mother_name" placeholder="Mother's Name">
                            <input type="text" name="mother_occupation" placeholder="Mother's Occupation">
                            <input type="text" name="spouse_name" placeholder="Spouse Name">
                            <input type="text" name="spouse_occupation" placeholder="Spouse Occupation">
                            <input type="number" name="number_of_children" placeholder="Number of Children" min="0">
                        </div>
                        <button type="submit" class="btn-primary">Save Family Background</button>
                    </form>
                </div>
            </div>

            <!-- Government IDs -->
            <div id="tab-govids" class="tab-content">
                <div class="form-section">
                    <h3>Government IDs</h3>
                    <div class="alert" style="display:none;"></div>
                    <form id="government-ids-form" data-skip="true" data-action="save_government_ids">
                        <input type="hidden" name="employee_id">
                        <div class="form-grid">
                            <input type="text" name="sss_no" placeholder="SSS No.">
                            <input type="text" name="philhealth_no" placeholder="PhilHealth No.">
                            <input type="text" name="pagibig_no" placeholder="Pag-IBIG No.">
                            <input type="text" name="tin_no" placeholder="TIN No.">
                        </div>
                        <button type="submit" class="btn-primary">Save Government IDs</button>
                    </form>
                </div>
            </div>

            <!-- Dependents -->
            <div id="tab-dependents" class="tab-content">
                <div class="form-section">
                    <h3>Dependents</h3>
                    <div class="alert" style="display:none;"></div>
                    <ul id="dependents-list" class="simple-list simple-list-deletable"></ul>
                    <form data-skip="true" data-action="add_dependent" class="inline-add-form">
                        <input type="hidden" name="employee_id">
                        <input type="text" name="name" placeholder="Full Name" required>
                        <input type="text" name="relationship" placeholder="Relationship" required>
                        <input type="date" name="birth_date" placeholder="Birth Date">
                        <button type="submit" class="btn-secondary">+ Add Dependent</button>
                    </form>
                </div>
            </div>

            <!-- Emergency Contacts -->
            <div id="tab-emergency" class="tab-content">
                <div class="form-section">
                    <h3>Emergency Contacts</h3>
                    <div class="alert" style="display:none;"></div>
                    <ul id="emergency-contacts-list" class="simple-list simple-list-deletable"></ul>
                    <form data-skip="true" data-action="add_emergency_contact" class="inline-add-form">
                        <input type="hidden" name="employee_id">
                        <input type="text" name="name" placeholder="Full Name" required>
                        <input type="text" name="relationship" placeholder="Relationship" required>
                        <input type="text" name="contact_number" placeholder="Contact Number" required>
                        <input type="text" name="address" placeholder="Address">
                        <button type="submit" class="btn-secondary">+ Add Contact</button>
                    </form>
                </div>
            </div>

            <!-- Education (em_education) -->
            <div id="tab-education" class="tab-content">
                <div class="form-section">
                    <h3>Education</h3>
                    <div class="alert" style="display:none;"></div>
                    <ul id="education-list" class="simple-list simple-list-deletable"></ul>
                    <form data-skip="true" data-action="add_education" class="inline-add-form">
                        <input type="hidden" name="employee_id">
                        <select name="level" required>
                            <option value="">Level *</option>
                            <option value="Elementary">Elementary</option>
                            <option value="High School">High School</option>
                            <option value="Senior High School">Senior High School</option>
                            <option value="College">College</option>
                            <option value="Masteral">Masteral</option>
                            <option value="Doctoral">Doctoral</option>
                        </select>
                        <input type="text" name="school_name" placeholder="School Name" required>
                        <input type="text" name="course" placeholder="Course">
                        <input type="number" name="year_graduated" placeholder="Year Graduated" min="1900" max="2100">
                        <input type="text" name="honors" placeholder="Honors">
                        <button type="submit" class="btn-secondary">+ Add Education</button>
                    </form>
                </div>
            </div>

            <!-- Certifications -->
            <div id="tab-certifications" class="tab-content">
                <div class="form-section">
                    <h3>Certifications</h3>
                    <div class="alert" style="display:none;"></div>
                    <ul id="certifications-list" class="simple-list simple-list-deletable"></ul>
                    <form data-skip="true" data-action="add_certification" class="inline-add-form">
                        <input type="hidden" name="employee_id">
                        <input type="text" name="cert_name" placeholder="Certificate Name" required>
                        <input type="text" name="issuing_organization" placeholder="Issuing Organization" required>
                        <input type="date" name="date_issued" placeholder="Date Issued">
                        <input type="date" name="expiry_date" placeholder="Expiry Date">
                        <button type="submit" class="btn-secondary">+ Add Certification</button>
                    </form>
                </div>
            </div>

            <!-- Skills -->
            <div id="tab-skills" class="tab-content">
                <div class="form-section">
                    <h3>Skills</h3>
                    <div class="alert" style="display:none;"></div>
                    <ul id="skills-list" class="simple-list simple-list-deletable"></ul>
                    <form data-skip="true" data-action="add_skill" class="inline-add-form">
                        <input type="hidden" name="employee_id">
                        <input type="text" name="skill_name" placeholder="Skill Name" required>
                        <select name="proficiency">
                            <option value="Beginner">Beginner</option>
                            <option value="Intermediate" selected>Intermediate</option>
                            <option value="Advanced">Advanced</option>
                            <option value="Expert">Expert</option>
                        </select>
                        <button type="submit" class="btn-secondary">+ Add Skill</button>
                    </form>
                </div>
            </div>

            <!-- Languages -->
            <div id="tab-languages" class="tab-content">
                <div class="form-section">
                    <h3>Languages</h3>
                    <div class="alert" style="display:none;"></div>
                    <ul id="languages-list" class="simple-list simple-list-deletable"></ul>
                    <form data-skip="true" data-action="add_language" class="inline-add-form">
                        <input type="hidden" name="employee_id">
                        <input type="text" name="language_name" placeholder="Language" required>
                        <select name="proficiency">
                            <option value="Beginner">Beginner</option>
                            <option value="Intermediate" selected>Intermediate</option>
                            <option value="Advanced">Advanced</option>
                            <option value="Fluent">Fluent</option>
                            <option value="Native">Native</option>
                        </select>
                        <button type="submit" class="btn-secondary">+ Add Language</button>
                    </form>
                </div>
            </div>

            <!-- Work Experience (employee_work_experience) -->
            <div id="tab-workexp" class="tab-content">
                <div class="form-section">
                    <h3>Work Experience</h3>
                    <div class="alert" style="display:none;"></div>
                    <ul id="work-experience-list" class="simple-list simple-list-deletable"></ul>
                    <form data-skip="true" data-action="add_work_experience" class="inline-add-form">
                        <input type="hidden" name="employee_id">
                        <input type="text" name="company_name" placeholder="Company Name" required>
                        <input type="text" name="position" placeholder="Position" required>
                        <input type="date" name="start_date" placeholder="Start Date" required>
                        <input type="date" name="end_date" placeholder="End Date (leave blank if current)">
                        <input type="number" step="0.01" name="salary" placeholder="Salary">
                        <input type="text" name="reason_for_leaving" placeholder="Reason for Leaving">
                        <button type="submit" class="btn-secondary">+ Add Work Experience</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
