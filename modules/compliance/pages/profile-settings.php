<?php
ob_start();

require_once __DIR__ . '/../../../database/db.php';

$pageTitle = 'Profile Settings';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

$employeeId = $_SESSION['employee_id'] ?? null;

if (!$employeeId) {
    header('Location: /hrms-capstone/index.php');
    exit;
}

if (!isset($db) || !($db instanceof PDO)) {
    try {
        if (class_exists('Database')) {
            $db = (new Database())->getConnection();
        } else {
            require_once __DIR__ . '/../../../database/db.php';
            $db = (new Database())->getConnection();
        }
    } catch (Throwable $e) {
        $db = null;
    }
}

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
if (empty($_SESSION['profile_csrf_token']) || !is_string($_SESSION['profile_csrf_token'])) {
    $_SESSION['profile_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['profile_csrf_token'];

$profile = [
    'employee_id' => '',
    'employee_code' => '',
    'first_name' => '',
    'middle_name' => '',
    'last_name' => '',
    'email' => '',
    'department' => '',
    'position' => '',
    'employment_status' => '',
    'employment_type' => '',
    'hire_date' => '',
    'account_status' => '',
    'password_changed_at' => '',
    'last_login' => '',
    'profile_pic' => '',
];

if ($db instanceof PDO) {
    try {
        $stmt = $db->prepare("
            SELECT 
                e.employee_id,
                e.employee_code,
                e.first_name,
                e.middle_name,
                e.last_name,
                e.email,
                e.employment_status,
                e.employment_type,
                e.hire_date,
                d.department_name AS department,
                p.position_name AS position,
                u.account_status,
                u.password_changed_at,
                u.last_login,
                u.profile_pic
            FROM em_employees e
            LEFT JOIN em_departments d ON e.department_id = d.department_id
            LEFT JOIN em_positions p ON e.position_id = p.position_id
            LEFT JOIN user_account u ON u.employee_id = e.employee_id
            WHERE e.employee_id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $employeeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $profile = array_merge($profile, $row);
        }
    } catch (Throwable $e) {
        error_log('ProfileSettings DB fetch error: ' . $e->getMessage());
    }
}

$successMessage = '';
$errorMessage = '';
$passwordSuccess = '';
$passwordError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'update_profile') {
        if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['profile_csrf_token'] ?? '', (string) $_POST['csrf_token'])) {
            $errorMessage = 'Invalid session. Please refresh and try again.';
        } else {
            $firstName = trim((string) ($_POST['first_name'] ?? ''));
            $lastName = trim((string) ($_POST['last_name'] ?? ''));
            $middleName = trim((string) ($_POST['middle_name'] ?? ''));

            if ($firstName === '' || $lastName === '') {
                $errorMessage = 'First name and last name are required.';
            } elseif (!$db instanceof PDO) {
                $errorMessage = 'Database connection is not available.';
            } else {
                try {
                    $stmt = $db->prepare("
                        UPDATE em_employees
                        SET first_name = :first_name,
                            last_name = :last_name,
                            middle_name = :middle_name
                        WHERE employee_id = :id
                    ");
                    $stmt->execute([
                        ':first_name' => $firstName,
                        ':last_name' => $lastName,
                        ':middle_name' => $middleName,
                        ':id' => $employeeId,
                    ]);

                    $oldName = trim(($profile['first_name'] ?? '') . ' ' . ($profile['middle_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''));
                    $newName = trim($firstName . ' ' . $middleName . ' ' . $lastName);

                    try {
                        $auditStmt = $db->prepare("
                            INSERT INTO lc_audit_trail (table_name, record_id, action, user_type, description, created_at)
                            VALUES (:table_name, :record_id, :action, :user_type, :description, NOW())
                        ");
                        $auditStmt->execute([
                            ':table_name' => 'em_employees',
                            ':record_id' => $employeeId,
                            ':action' => 'UPDATE',
                            ':user_type' => 'Employee',
                            ':description' => 'Updated profile name from "' . $oldName . '" to "' . $newName . '"',
                        ]);
                    } catch (Throwable $auditEx) {
                        error_log('ProfileSettings audit trail error: ' . $auditEx->getMessage());
                    }

                    $successMessage = 'Profile updated successfully.';
                    $profile['first_name'] = $firstName;
                    $profile['last_name'] = $lastName;
                    $profile['middle_name'] = $middleName;
                } catch (Throwable $e) {
                    error_log('ProfileSettings update error: ' . $e->getMessage());
                    $errorMessage = 'Unable to update your profile. Please try again.';
                }
            }
        }
    } elseif ($action === 'change_password') {
        if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['profile_csrf_token'] ?? '', (string) $_POST['csrf_token'])) {
            $passwordError = 'Invalid session. Please refresh and try again.';
        } elseif (empty($profile['account_status']) || $profile['account_status'] !== 'Active') {
            $passwordError = 'Account is not active. Password change is not allowed.';
        } else {
            $currentPassword = (string) ($_POST['current_password'] ?? '');
            $newPassword = (string) ($_POST['new_password'] ?? '');
            $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

            if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
                $passwordError = 'All password fields are required.';
            } elseif ($newPassword !== $confirmPassword) {
                $passwordError = 'New password and confirmation do not match.';
            } elseif (strlen($newPassword) < 8) {
                $passwordError = 'New password must be at least 8 characters.';
            } else {
                try {
                    $stmt = $db->prepare("SELECT password FROM user_account WHERE employee_id = :id LIMIT 1");
                    $stmt->execute([':id' => $employeeId]);
                    $userRow = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$userRow) {
                    } elseif (!password_verify($currentPassword, $userRow['password'])) {
                    } else {
                        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                        $updateStmt = $db->prepare("
                            UPDATE user_account
                            SET password = :password,
                                password_changed_at = NOW()
                            WHERE employee_id = :id
                        ");
                        $updateStmt->execute([
                            ':password' => $hashedPassword,
                            ':id' => $employeeId,
                        ]);

                        try {
                            $auditStmt = $db->prepare("
                                INSERT INTO lc_audit_trail (table_name, record_id, action, user_type, description, created_at)
                                VALUES (:table_name, :record_id, :action, :user_type, :description, NOW())
                            ");
                            $auditStmt->execute([
                                ':table_name' => 'user_account',
                                ':record_id' => $employeeId,
                                ':action' => 'UPDATE',
                                ':user_type' => 'Employee',
                                ':description' => 'Changed account password',
                            ]);
                        } catch (Throwable $auditEx) {
                            error_log('ProfileSettings password audit error: ' . $auditEx->getMessage());
                        }

                        $passwordSuccess = 'Password changed successfully.';
                    }
                } catch (Throwable $e) {
                    error_log('ProfileSettings password change error: ' . $e->getMessage());
                    $passwordError = 'Unable to change password. Please try again.';
                }
            }
        }
    }
}

$statusBadgeClass = 'badge-secondary';
$statusRaw = strtolower((string) ($profile['employment_status'] ?? ''));
if ($statusRaw === 'active') {
    $statusBadgeClass = 'badge-success';
} elseif ($statusRaw === 'probationary') {
    $statusBadgeClass = 'badge-warning';
} elseif ($statusRaw === 'resigned') {
    $statusBadgeClass = 'badge-secondary';
} elseif ($statusRaw === 'terminated') {
    $statusBadgeClass = 'badge-danger';
}
$statusLabel = htmlspecialchars((string) ($profile['employment_status'] ?? ''));

$fullName = trim(($profile['first_name'] ?? '') . ' ' . ($profile['middle_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''));
$displayName = $fullName !== '' ? $fullName : 'Unknown Employee';
$avatarInitials = strtoupper(substr((string) ($profile['first_name'] ?? ''), 0, 1) . substr((string) ($profile['last_name'] ?? ''), 0, 1));
if ($avatarInitials === '') {
    $avatarInitials = '?';
}

$profilePic = !empty($profile['profile_pic']) ? htmlspecialchars((string) $profile['profile_pic']) : '';
$hireDate = !empty($profile['hire_date']) ? date('F j, Y', strtotime((string) $profile['hire_date'])) : 'N/A';
$passwordChanged = !empty($profile['password_changed_at']) ? date('F j, Y g:i A', strtotime((string) $profile['password_changed_at'])) : null;
$lastLogin = !empty($profile['last_login']) ? date('F j, Y g:i A', strtotime((string) $profile['last_login'])) : null;
$hasUserAccount = !empty($profile['account_status']);
$employeeCode = htmlspecialchars((string) ($profile['employee_code'] ?? ''));
$email = htmlspecialchars((string) ($profile['email'] ?? ''));
$department = htmlspecialchars((string) ($profile['department'] ?? ''));
$position = htmlspecialchars((string) ($profile['position'] ?? ''));
$employmentType = !empty($profile['employment_type']) ? htmlspecialchars((string) $profile['employment_type']) : 'N/A';
$employmentStatus = htmlspecialchars((string) ($profile['employment_status'] ?? ''));
$firstName = htmlspecialchars((string) ($profile['first_name'] ?? ''));
$lastName = htmlspecialchars((string) ($profile['last_name'] ?? ''));
$middleName = htmlspecialchars((string) ($profile['middle_name'] ?? ''));

?>
<section class="cw-module">
    <div class="cw-row">
        <div class="cw-col cw-col-main">
            <!-- Personal Information -->
            <div class="cw-card">
                <div class="cw-card-head">
                    <h3><i class="fa-regular fa-user"></i> Personal Information</h3>
                </div>
                <div class="cw-card-body">
                    <form id="profileForm" method="post" autocomplete="off" data-skip>
                        <input type="hidden" name="action" value="update_profile">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <div class="cw-info-grid">
                            <div class="cw-info-item">
                                <label>First Name</label>
                                <input type="text" name="first_name" value="<?= $firstName ?>" required>
                            </div>
                            <div class="cw-info-item">
                                <label>Last Name</label>
                                <input type="text" name="last_name" value="<?= $lastName ?>" required>
                            </div>
                            <div class="cw-info-item">
                                <label>Middle Name</label>
                                <input type="text" name="middle_name" value="<?= $middleName ?>">
                            </div>
                            <div class="cw-info-item">
                                <label>Email</label>
                                <input type="email" value="<?= $email ?>" readonly>
                            </div>
                        </div>
                        <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:16px;">
                            <button type="button" class="cw-btn primary" onclick="document.getElementById('profileForm').submit();"><i class="bi bi-check2-circle"></i> Save Changes</button>
                            <a href="?page=dashboard-overview" class="cw-btn"><i class="bi bi-x-circle"></i> Cancel</a>
                        </div>
                        <?php if ($successMessage): ?>
                            <div class="cw-flash success" style="margin-top:12px;"><?= htmlspecialchars($successMessage) ?></div>
                        <?php endif; ?>
                        <?php if ($errorMessage): ?>
                            <div class="cw-flash error" style="margin-top:12px;"><?= htmlspecialchars($errorMessage) ?></div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Employment Information -->
            <div class="cw-card">
                <div class="cw-card-head">
                    <h3><i class="fa-solid fa-briefcase"></i> Employment Information</h3>
                    <span class="cw-stamp cw-stamp-<?= $statusBadgeClass ?>"><?= $statusLabel !== '' ? $statusLabel : 'Unknown' ?></span>
                </div>
                <div class="cw-card-body">
                    <div class="cw-info-grid">
                        <div class="cw-info-item">
                            <label>Employee ID</label>
                            <div><?= $employeeCode !== '' ? $employeeCode : '—' ?></div>
                        </div>
                        <div class="cw-info-item">
                            <label>Department</label>
                            <div><?= $department !== '' ? $department : '—' ?></div>
                        </div>
                        <div class="cw-info-item">
                            <label>Position</label>
                            <div><?= $position !== '' ? $position : '—' ?></div>
                        </div>
                        <div class="cw-info-item">
                            <label>Employment Status</label>
                            <div><?= $employmentStatus !== '' ? $employmentStatus : '—' ?></div>
                        </div>
                        <div class="cw-info-item">
                            <label>Employment Type</label>
                            <div><?= $employmentType !== 'N/A' ? $employmentType : '—' ?></div>
                        </div>
                        <div class="cw-info-item">
                            <label>Date Hired</label>
                            <div><?= $hireDate !== 'N/A' ? $hireDate : '—' ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Security -->
            <div class="cw-card">
                <div class="cw-card-head">
                    <h3><i class="fa-solid fa-lock"></i> Account Security</h3>
                </div>
                <div class="cw-card-body">
                    <?php if ($passwordSuccess): ?>
                        <div class="cw-flash success" style="margin-bottom:12px;"><?= htmlspecialchars($passwordSuccess) ?></div>
                    <?php endif; ?>
                    <?php if ($passwordError): ?>
                        <div class="cw-flash error" style="margin-bottom:12px;"><?= htmlspecialchars($passwordError) ?></div>
                    <?php endif; ?>
                    <div class="cw-info-grid">
                        <div class="cw-info-item">
                            <label>Account Email</label>
                            <div><?= $email !== '' ? $email : '—' ?></div>
                        </div>
                        <div class="cw-info-item">
                            <label>Password Status</label>
                            <div><?= $hasUserAccount ? 'Set' : 'Not set' ?></div>
                        </div>
                        <div class="cw-info-item">
                            <label>Last Password Change</label>
                            <div><?= $passwordChanged !== null ? $passwordChanged : 'Never changed' ?></div>
                        </div>
                    </div>
                    <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:16px;">
                        <?php if ($hasUserAccount): ?>
                            <button type="button" class="cw-btn primary" onclick="pwOpenModal()">
                                <i class="fa-solid fa-key"></i> Change Password
                            </button>
                        <?php else: ?>
                            <span style="font-size:0.85rem; color:var(--text-500,#6b7280);">No linked user account found.</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="cw-col cw-col-side">
            <!-- Profile Summary -->
            <div class="cw-card">
                <div class="cw-card-head">
                    <h3><i class="fa-regular fa-user"></i> Profile</h3>
                </div>
                <div class="cw-card-body">
                    <div class="cw-profile">
                        <div class="cw-profile-avatar">
                            <?php if ($profilePic): ?>
                                <img src="<?= $profilePic ?>" alt="Profile" style="width:100%; height:100%; object-fit:cover; border-radius:50%;">
                            <?php else: ?>
                                <?= htmlspecialchars($avatarInitials) ?>
                            <?php endif; ?>
                        </div>
                        <div class="cw-profile-name"><?= htmlspecialchars($displayName) ?></div>
                        <div class="cw-profile-no"><?= $employeeCode !== '' ? $employeeCode : 'EMP-???' ?></div>
                        <div style="font-size:.78rem; color:var(--text-500,#6b7280); margin-top:4px;">
                            <?= $position !== '' ? $position : '' ?>
                            <?php if ($position !== '' && $department !== ''): ?>
                                <br>
                            <?php endif; ?>
                            <?= $department !== '' ? $department : '' ?>
                        </div>
                        <div style="margin-top:8px;">
                            <span class="cw-stamp cw-stamp-<?= $statusBadgeClass ?>"><?= $statusLabel !== '' ? $statusLabel : 'Unknown' ?></span>
                        </div>
                    </div>
                    <div class="cw-profile-stats" style="grid-template-columns:1fr;">
                        <div class="cw-profile-stat">
                            <div class="cw-profile-stat-label">Email</div>
                            <div class="cw-profile-stat-value" style="font-size:.82rem; font-weight:600;"><?= $email !== '' ? $email : '—' ?></div>
                        </div>
                        <?php if ($lastLogin): ?>
                        <div class="cw-profile-stat">
                            <div class="cw-profile-stat-label">Last Login</div>
                            <div class="cw-profile-stat-value" style="font-size:.82rem; font-weight:600;"><?= $lastLogin ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Change Password Modal -->
<div class="cw-modal-overlay" id="pwModalBackdrop" onclick="if(event.target===this)pwCloseModal()">
    <div class="cw-modal" role="dialog" aria-modal="true" aria-labelledby="pwModalTitle">
        <div class="cw-modal-head">
            <h3 id="pwModalTitle"><i class="fa-solid fa-key"></i> Change Password</h3>
            <button type="button" class="cw-modal-close" onclick="pwCloseModal()">&times;</button>
        </div>
        <form id="pwModalForm" method="post" autocomplete="off" data-skip>
            <div class="cw-modal-body">
                <input type="hidden" name="action" value="change_password">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <div class="pw-field-wrap">
                    <div class="profile-field">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    <div class="pw-field-feedback" id="currentPasswordFeedback"></div>
                </div>
                <div class="profile-field">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required minlength="8">
                    <div class="pw-requirements" id="pwRequirements">
                        <div class="pw-req" data-req="length">At least 8 characters</div>
                        <div class="pw-req" data-req="uppercase">At least 1 uppercase letter</div>
                        <div class="pw-req" data-req="lowercase">At least 1 lowercase letter</div>
                        <div class="pw-req" data-req="number">At least 1 number</div>
                        <div class="pw-req" data-req="special">At least 1 special character</div>
                    </div>
                </div>
                <div class="profile-field">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                </div>
            </div>
            <div class="cw-modal-footer">
                <button type="button" class="cw-btn" onclick="pwCloseModal()">Cancel</button>
                <button type="submit" class="cw-btn primary">Update Password</button>
            </div>
        </form>
    </div>
</div>

<script>
function pwOpenModal() {
    var modal = document.getElementById('pwModalBackdrop');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        var firstInput = modal.querySelector('input');
        if (firstInput) setTimeout(function(){ firstInput.focus(); }, 50);
    }
}
function pwCloseModal() {
    var modal = document.getElementById('pwModalBackdrop');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') pwCloseModal();
});

if (window.location.hash === '#change-password') {
    setTimeout(function(){ pwOpenModal(); }, 100);
}

(function(){
    var currentInput = document.getElementById('current_password');
    var feedbackEl = document.getElementById('currentPasswordFeedback');
    var wrapper = currentInput ? currentInput.closest('.pw-field-wrap') : null;
    var timer = null;

    if (!currentInput || !feedbackEl || !wrapper) return;

    function validate() {
        var val = currentInput.value;
        if (val === '') {
            wrapper.classList.remove('is-valid', 'is-invalid');
            feedbackEl.className = 'pw-field-feedback';
            feedbackEl.textContent = '';
            return;
        }

        var formData = new FormData();
        formData.append('password', val);

        fetch('/hrms-capstone/modules/compliance/lib/api/verify_current_password.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    wrapper.classList.add('is-valid');
                    wrapper.classList.remove('is-invalid');
                    feedbackEl.className = 'pw-field-feedback success';
                    feedbackEl.textContent = 'Current password is correct';
                } else {
                    wrapper.classList.add('is-invalid');
                    wrapper.classList.remove('is-valid');
                    feedbackEl.className = 'pw-field-feedback error';
                    feedbackEl.textContent = data.message || 'Incorrect password';
                }
            })
            .catch(function() {
                wrapper.classList.add('is-invalid');
                wrapper.classList.remove('is-valid');
                feedbackEl.className = 'pw-field-feedback error';
                feedbackEl.textContent = 'Unable to verify password';
            });
    }

    currentInput.addEventListener('input', function() {
        if (timer) clearTimeout(timer);
        timer = setTimeout(validate, 350);
    });

    currentInput.addEventListener('blur', function() {
        if (timer) clearTimeout(timer);
        validate();
    });
})();

(function(){
    var newPwInput = document.getElementById('new_password');
    var requirementsEl = document.getElementById('pwRequirements');
    if (!newPwInput || !requirementsEl) return;

    var reqItems = requirementsEl.querySelectorAll('.pw-req');
    var rules = {
        length: function(v){ return v.length >= 8; },
        uppercase: function(v){ return /[A-Z]/.test(v); },
        lowercase: function(v){ return /[a-z]/.test(v); },
        number: function(v){ return /[0-9]/.test(v); },
        special: function(v){ return /[^A-Za-z0-9]/.test(v); }
    };

    function validateRequirements() {
        var val = newPwInput.value;
        reqItems.forEach(function(item){
            var key = item.getAttribute('data-req');
            if (key && rules[key]) {
                if (rules[key](val)) {
                    item.classList.add('met');
                } else {
                    item.classList.remove('met');
                }
            }
        });
    }

    newPwInput.addEventListener('input', validateRequirements);
})();
</script>

<style>
.cw-module { padding: 4px 2px 24px; }
.cw-flash { padding:10px 14px; border-radius:10px; font-size:.84rem; font-weight:600; margin-bottom:14px; display:none; }
.cw-flash.success { display:block; background:var(--success-50,#ecfdf5); color:var(--success-700,#047857); border:1px solid rgba(16,185,129,.25); }
.cw-flash.error { display:block; background:var(--danger-50,#fef2f2); color:var(--danger-700,#b91c1c); border:1px solid rgba(239,68,68,.25); }

.cw-row { display:grid; grid-template-columns:1fr 360px; gap:16px; align-items:start; }
.cw-col-main { min-width:0; }
.cw-col-side { width:360px; flex-shrink:0; }
@media (max-width: 1100px) {
    .cw-row { grid-template-columns:1fr; }
    .cw-col-side { position:static; width:auto; }
}

.cw-card { background:var(--card-bg,#fff); border:1px solid var(--border,#e4e8ee); border-radius:14px; padding:18px; box-shadow:var(--shadow-soft,0 1px 2px rgba(13,27,46,.04)); margin-bottom:16px; }
.cw-card-head { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:14px; flex-wrap:wrap; }
.cw-card-head h3 { margin:0; font-size:.98rem; font-weight:700; color:var(--text-900,#1b2430); display:flex; align-items:center; gap:8px; }
.cw-card-body { display:flex; flex-direction:column; }

.cw-info-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:12px; }
.cw-info-item label { display:block; font-size:.72rem; font-weight:700; color:var(--text-400,#8b93a1); text-transform:uppercase; letter-spacing:.4px; margin-bottom:4px; }
.cw-info-item input { width:100%; box-sizing:border-box; padding:8px 10px; border:1px solid var(--border,#e4e8ee); border-radius:8px; font-size:.82rem; color:var(--text-900,#1b2430); background:#fff; }
.cw-info-item input[readonly] { background:var(--slate-50,#f8fafc); color:var(--text-500,#6b7280); cursor:not-allowed; }
.cw-info-item input:focus { outline:none; border-color:var(--info-blue,#3b82c4); box-shadow:0 0 0 3px rgba(59,130,196,.08); }

.cw-stamp { display:inline-block; font-size:.66rem; font-weight:700; padding:3px 10px; border-radius:999px; white-space:nowrap; }
.cw-stamp-success { background:rgba(16,185,129,.12); color:#047857; }
.cw-stamp-warning { background:rgba(245,158,11,.12); color:#b45309; }
.cw-stamp-danger { background:rgba(239,68,68,.12); color:#b91c1c; }
.cw-stamp-secondary { background:rgba(100,116,139,.12); color:#334155; }

.cw-btn { display:inline-flex; align-items:center; gap:6px; padding:7px 14px; border-radius:8px; border:1px solid var(--border,#e4e8ee); background:#fff; color:var(--text-700,#3b4252); font-size:.78rem; font-weight:600; cursor:pointer; white-space:nowrap; transition:all .15s ease; text-decoration:none; }
.cw-btn:hover { border-color:var(--info-blue,#3b82c4); color:var(--info-blue,#3b82c4); box-shadow:0 0 0 3px rgba(59,130,196,.08); }
.cw-btn.primary { background:rgba(59,130,196,.08); border-color:rgba(59,130,196,.25); color:#1c5a8a; }
.cw-btn.primary:hover { background:rgba(59,130,196,.14); }

.cw-profile { text-align:center; padding:12px 0 16px; border-bottom:1px solid var(--border,#e4e8ee); margin-bottom:12px; }
.cw-profile-avatar { width:56px; height:56px; border-radius:50%; background:rgba(13,27,46,.06); display:inline-flex; align-items:center; justify-content:center; font-size:1.1rem; font-weight:800; color:var(--text-600,#5b6472); margin-bottom:6px; overflow:hidden; }
.cw-profile-name { font-size:.92rem; font-weight:700; color:var(--text-900,#1b2430); }
.cw-profile-no { font-size:.78rem; color:var(--text-500,#6b7280); }
.cw-profile-stats { display:grid; grid-template-columns:1fr 1fr 1fr; gap:8px; margin-bottom:12px; }
.cw-profile-stat { text-align:center; padding:8px; background:rgba(13,27,46,.02); border-radius:8px; border:1px solid var(--border,#e4e8ee); }
.cw-profile-stat-value { font-size:1.1rem; font-weight:800; color:var(--text-900,#1b2430); }
.cw-profile-stat-label { font-size:.66rem; font-weight:600; color:var(--text-500,#6b7280); text-transform:uppercase; letter-spacing:.04em; margin-top:2px; }

.cw-modal-overlay { display:none; position:fixed; inset:0; background:rgba(13,27,46,.45); z-index:1050; align-items:center; justify-content:center; padding:16px; }
.cw-modal-overlay.active { display:flex; }
.cw-modal { background:#fff; border-radius:14px; box-shadow:0 24px 48px rgba(13,27,46,.18); max-width:480px; width:100%; max-height:calc(100vh - 32px); overflow-y:auto; }
.cw-modal-head { display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid var(--border,#e4e8ee); }
.cw-modal-head h3 { margin:0; font-size:1rem; font-weight:700; color:var(--text-900,#1b2430); display:flex; align-items:center; gap:8px; }
.cw-modal-head h3 i { color:var(--info-blue,#3b82c4); }
.cw-modal-close { background:none; border:none; font-size:1.25rem; cursor:pointer; color:var(--text-400,#8b93a1); line-height:1; padding:4px; border-radius:6px; transition:all .15s ease; }
.cw-modal-close:hover { color:var(--text-900,#1b2430); background:rgba(13,27,46,.05); }
.cw-modal-body { padding:16px 20px 20px; }
.cw-modal .profile-field { margin-bottom:12px; }
.cw-modal .profile-field:last-child { margin-bottom:0; }
.cw-modal .profile-field label { display:block; font-size:.72rem; font-weight:700; color:var(--text-700,#3b4252); text-transform:uppercase; letter-spacing:.3px; margin-bottom:6px; }
.cw-modal .profile-field input { width:100%; box-sizing:border-box; padding:10px 12px; border:1px solid var(--border,#e4e8ee); border-radius:8px; font-size:.88rem; outline:none; background:#fff; color:var(--text-900,#1b2430); transition:border-color .15s ease, box-shadow .15s ease; }
.cw-modal .profile-field input:focus { border-color:var(--info-blue,#3b82c4); box-shadow:0 0 0 3px rgba(59,130,196,.12); }
.cw-modal-footer { display:flex; align-items:center; justify-content:flex-end; gap:10px; padding:12px 20px 16px; border-top:1px solid var(--border,#e4e8ee); }
.pw-field-wrap { position:relative; }
.pw-field-feedback { font-size:.78rem; font-weight:600; margin-top:6px; margin-bottom:8px; display:none; }
.pw-field-feedback.success { display:block; color:#047857; }
.pw-field-feedback.error { display:block; color:#b91c1c; }
.pw-field-wrap.is-valid .profile-field input { border-color:#10b981; box-shadow:0 0 0 3px rgba(16,185,129,.12); }
.pw-field-wrap.is-invalid .profile-field input { border-color:#ef4444; box-shadow:0 0 0 3px rgba(239,68,68,.12); }
.pw-requirements { display:flex; flex-direction:column; gap:4px; margin-top:8px; }
.pw-req { font-size:.72rem; font-weight:600; color:var(--text-400,#8b93a1); display:flex; align-items:center; gap:6px; }
.pw-req::before { content:'○'; font-size:.85rem; }
.pw-req.met { color:#047857; }
.pw-req.met::before { content:'●'; }

@media (max-width: 640px) {
    .cw-row { grid-template-columns:1fr; }
    .cw-col-side { position:static; width:auto; }
    .cw-info-grid { grid-template-columns:1fr; }
}
@media (max-width: 480px) {
    .cw-card { padding:14px; }
    .cw-btn { width:100%; justify-content:center; }
}
</style>
