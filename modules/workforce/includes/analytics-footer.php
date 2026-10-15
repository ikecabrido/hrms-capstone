</section>

<?php
$recruitmentRoles = [];
$recruitmentDepartments = [];
$recruitmentManagers = [];

foreach ($employees as $employee) {
    $role = trim((string) ($employee['position_name'] ?? ''));
    $department = trim((string) ($employee['department_name'] ?? ''));
    $managerName = trim(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? ''));
    $managerPosition = strtolower((string) ($employee['position_name'] ?? ''));

    if ($role !== '') {
        $recruitmentRoles[$role] = $role;
    }
    if ($department !== '') {
        $recruitmentDepartments[$department] = $department;
    }
    if ($managerName !== '' && preg_match('/manager|director|supervisor|lead|chief|head/', $managerPosition)) {
        $recruitmentManagers[$managerName] = $managerName;
    }
}

if (empty($recruitmentManagers)) {
    foreach ($employees as $employee) {
        $managerName = trim(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? ''));
        if ($managerName !== '') {
            $recruitmentManagers[$managerName] = $managerName;
        }
    }
}

sort($recruitmentRoles);
sort($recruitmentDepartments);
sort($recruitmentManagers);
?>

<div id="actionModal" class="wa-modal">
    <div class="wa-modal-content">
        <div class="wa-modal-header">
            <h2>Request Applicant</h2>
            <button type="button" class="wa-modal-close" onclick="closeActionModal()">×</button>
        </div>
        <div class="wa-modal-body">
            <p class="wa-modal-description">Submit recruitment details for open roles and employee hiring requests.</p>
            <form id="recruitmentForm" data-skip onsubmit="handleRecruitmentSubmit(event)">
                <div class="wa-form-group">
                    <label for="roleVacancy">Role / Vacancy</label>
                    <select id="roleVacancy" name="roleVacancy" required>
                        <option value="">Select a role or vacancy</option>
                        <?php foreach ($recruitmentRoles as $role): ?>
                            <option value="<?= htmlspecialchars($role) ?>"><?= htmlspecialchars($role) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="wa-form-row">
                    <div class="wa-form-group">
                        <label for="department">Department</label>
                        <select id="department" name="department" required>
                            <option value="">Select a department</option>
                            <?php foreach ($recruitmentDepartments as $department): ?>
                                <option value="<?= htmlspecialchars($department) ?>"><?= htmlspecialchars($department) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="wa-form-group">
                        <label for="hiringManager">Hiring Manager</label>
                        <select id="hiringManager" name="hiringManager" required>
                            <option value="">Select a hiring manager</option>
                            <?php foreach ($recruitmentManagers as $manager): ?>
                                <option value="<?= htmlspecialchars($manager) ?>"><?= htmlspecialchars($manager) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="wa-form-row">
                    <div class="wa-form-group">
                        <label for="openings">Openings</label>
                        <select id="openings" name="openings" required>
                            <option value="">Select number of openings</option>
                            <?php for ($opening = 1; $opening <= 10; $opening++): ?>
                                <option value="<?= $opening ?>"><?= $opening ?> <?= $opening === 1 ? 'opening' : 'openings' ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="wa-form-group">
                        <label for="targetStart">Target Start</label>
                        <input type="date" id="targetStart" name="targetStart" min="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="wa-form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" placeholder="Add any recruitment notes or candidate requirements" rows="4"></textarea>
                </div>
                <div class="wa-modal-actions">
                    <button type="submit" class="wa-button wa-button-primary">Submit Request</button>
                    <button type="button" class="wa-button wa-button-secondary" onclick="closeActionModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.wa-modal{display:none;position:fixed;z-index:1000;left:0;top:0;width:100%;height:100%;background-color:rgba(0,0,0,.5);animation:fadeIn .3s ease-in-out}.wa-modal.show{display:flex;justify-content:center;align-items:center}@keyframes fadeIn{from{opacity:0}to{opacity:1}}.wa-modal-content{background-color:#fff;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.15);width:90%;max-width:600px;animation:slideIn .3s ease-out}@keyframes slideIn{from{transform:translateY(-50px);opacity:0}to{transform:translateY(0);opacity:1}}.wa-modal-header{display:flex;justify-content:space-between;align-items:center;padding:24px 24px 16px;border-bottom:1px solid #e5e7eb}.wa-modal-header h2{margin:0;font-size:18px;font-weight:600;color:#1f2937}.wa-modal-close{background:none;border:0;font-size:28px;cursor:pointer;color:#6b7280;padding:0;width:32px;height:32px;display:flex;align-items:center;justify-content:center}.wa-modal-close:hover{color:#1f2937}.wa-modal-body{padding:24px}.wa-modal-description{color:#6b7280;font-size:14px;margin:0 0 20px}.wa-form-group{margin-bottom:20px}.wa-form-group label{display:block;margin-bottom:8px;font-size:14px;font-weight:500;color:#1f2937}.wa-form-group input,.wa-form-group select,.wa-form-group textarea{width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;font-family:inherit;transition:border-color .2s;background:#fff}.wa-form-group input:focus,.wa-form-group select:focus,.wa-form-group textarea:focus{outline:none;border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1)}.wa-form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}.wa-modal-actions{display:flex;gap:12px;justify-content:flex-end;padding-top:20px;border-top:1px solid #e5e7eb}.wa-button{padding:10px 20px;border:0;border-radius:6px;cursor:pointer;font-size:14px;font-weight:500;transition:all .2s}.wa-button-primary{background-color:#3b82f6;color:#fff}.wa-button-primary:hover{background-color:#2563eb}.wa-button-secondary{background-color:#e5e7eb;color:#1f2937}.wa-button-secondary:hover{background-color:#d1d5db}.wa-button-small{padding:6px 12px;font-size:12px}@media(max-width:600px){.wa-modal-content{width:95%;max-width:calc(100% - 30px)}.wa-form-row{grid-template-columns:1fr}}
</style>

<script>
function openActionModal(button) {
    const modal = document.getElementById('actionModal');
    const row = button.closest('tr');
    const cells = row.querySelectorAll('td');
    const employeeName = cells[0]?.textContent?.trim() || '';
    const department = cells[1]?.textContent?.trim() || '';
    const position = cells[2]?.textContent?.trim() || '';

    setSelectValue('roleVacancy', position);
    setSelectValue('department', department);
    document.getElementById('hiringManager').value = '';
    document.getElementById('openings').value = '1';
    document.getElementById('targetStart').value = '';
    document.getElementById('notes').value = `Recruitment for: ${employeeName}`;
    modal.classList.add('show');
}

function setSelectValue(id, value) {
    const select = document.getElementById(id);
    const optionExists = Array.from(select.options).some(option => option.value === value);
    select.value = optionExists ? value : '';
}

function closeActionModal() {
    const modal = document.getElementById('actionModal');
    modal.classList.remove('show');
    document.getElementById('recruitmentForm').reset();
}

function handleRecruitmentSubmit(event) {
    event.preventDefault();
    const formData = {
        roleVacancy: document.getElementById('roleVacancy').value,
        department: document.getElementById('department').value,
        hiringManager: document.getElementById('hiringManager').value,
        openings: document.getElementById('openings').value,
        targetStart: document.getElementById('targetStart').value,
        notes: document.getElementById('notes').value
    };

    console.log('Recruitment Request Submitted:', formData);
    alert('Recruitment request submitted successfully!');
    closeActionModal();
}

document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('actionModal');
    if (modal) {
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeActionModal();
            }
        });
    }
});
</script>
