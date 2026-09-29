<?php
ob_start();

require_once __DIR__ . '/../../../database/db.php';

$pageTitle = 'Profile Settings';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

$employeeId = $_SESSION['employee_id'] ?? null;

if (!$employeeId) {
    header('Location: /index.php');
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
            $emailInput = trim((string) ($_POST['email'] ?? ''));

            if ($firstName === '' || $lastName === '') {
                $errorMessage = 'First name and last name are required.';
            } elseif ($emailInput === '' || !filter_var($emailInput, FILTER_VALIDATE_EMAIL)) {
                $errorMessage = 'A valid email is required.';
            } elseif (!$db instanceof PDO) {
                $errorMessage = 'Database connection is not available.';
            } else {
                try {
                    $stmt = $db->prepare("
                        UPDATE em_employees
                        SET first_name = :first_name,
                            last_name = :last_name,
                            middle_name = :middle_name,
                            email = :email
                        WHERE employee_id = :id
                    ");
                    $stmt->execute([
                        ':first_name' => $firstName,
                        ':last_name' => $lastName,
                        ':middle_name' => $middleName,
                        ':email' => $emailInput,
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
                            ':description' => 'Updated profile name from "' . $oldName . '" to "' . $newName . '" and email to "' . $emailInput . '"',
                        ]);
                    } catch (Throwable $auditEx) {
                        error_log('ProfileSettings audit trail error: ' . $auditEx->getMessage());
                    }

                    $successMessage = 'Profile updated successfully.';
                    $profile['first_name'] = $firstName;
                    $profile['last_name'] = $lastName;
                    $profile['middle_name'] = $middleName;
                    $profile['email'] = $emailInput;
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
        <div class="cw-col cw-col-side">
            <!-- Profile Summary -->
            <div class="cw-card cw-profile-card">
                <div class="cw-card-head">
                    <div class="cw-card-head-content">
                        <h3>Profile</h3>
                    </div>
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
                        <div class="cw-profile-meta">
                            <?= $position !== '' ? $position : '' ?>
                            <?php if ($position !== '' && $department !== ''): ?>
                                <br>
                            <?php endif; ?>
                            <?= $department !== '' ? $department : '' ?>
                        </div>
                        <div class="cw-profile-status">
                            <span class="cw-stamp cw-stamp-<?= $statusBadgeClass ?>"><?= $statusLabel !== '' ? $statusLabel : 'Unknown' ?></span>
                        </div>
                    </div>
                    <div class="cw-profile-stats">
                        <div class="cw-profile-stat">
                            <div class="cw-profile-stat-label">Email</div>
                            <div class="cw-profile-stat-value"><?= $email !== '' ? $email : '—' ?></div>
                        </div>
                        <?php if ($lastLogin): ?>
                        <div class="cw-profile-stat">
                            <div class="cw-profile-stat-label">Last Login</div>
                            <div class="cw-profile-stat-value"><?= $lastLogin ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="cw-col cw-col-main">
            <!-- Personal Information -->
            <div class="cw-card">
                <div class="cw-card-head">
                    <div class="cw-card-head-content">
                        <h3>Personal Information</h3>
                    </div>
                </div>
                <div class="cw-card-body">
                    <form id="profileForm" method="post" autocomplete="off" data-skip>
                        <input type="hidden" name="action" value="update_profile">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <div class="cw-info-grid">
                            <div class="cw-info-item">
                                <label>First Name</label>
                                <input type="text" name="first_name" value="<?= $firstName ?>" required readonly>
                            </div>
                            <div class="cw-info-item">
                                <label>Last Name</label>
                                <input type="text" name="last_name" value="<?= $lastName ?>" required readonly>
                            </div>
                            <div class="cw-info-item">
                                <label>Middle Name</label>
                                <input type="text" name="middle_name" value="<?= $middleName ?>" readonly>
                            </div>
                            <div class="cw-info-item cw-email-field">
                                <label>Email</label>
                                <input type="email" name="email" value="<?= $email ?>" aria-invalid="false">
                                <div class="cw-field-feedback" aria-live="polite"></div>
                            </div>
                        </div>
                        <div class="cw-card-actions">
                            <button type="button" class="cw-btn primary" onclick="if(validateEmailField()){document.getElementById('profileForm').submit();}">Save Changes</button>
                        </div>
                        <?php if ($successMessage): ?>
                            <div class="cw-flash success"><?= htmlspecialchars($successMessage) ?></div>
                        <?php endif; ?>
                        <?php if ($errorMessage): ?>
                            <div class="cw-flash error"><?= htmlspecialchars($errorMessage) ?></div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Employment Information -->
            <div class="cw-card">
                <div class="cw-card-head">
                    <div class="cw-card-head-content">
                        <h3>Employment Information</h3>
                    </div>
                    <span class="cw-stamp cw-stamp-badge-success"><span class="cw-status-dot"></span> <?= $statusLabel !== '' ? $statusLabel : 'Unknown' ?></span>
                </div>
                <div class="cw-card-body">
                    <div class="cw-info-grid cw-employment-grid">
                        <div class="cw-info-item">
                            <label>Employee ID</label>
                            <div class="cw-value cw-value-id"><?= $employeeCode !== '' ? $employeeCode : '—' ?></div>
                        </div>
                        <div class="cw-info-item">
                            <label>Department</label>
                            <div class="cw-value"><?= $department !== '' ? $department : '—' ?></div>
                        </div>
                        <div class="cw-info-item">
                            <label>Position</label>
                            <div class="cw-value"><?= $position !== '' ? $position : '—' ?></div>
                        </div>
                        <div class="cw-info-item">
                            <label>Employment Status</label>
                            <div class="cw-value"><span class="cw-status-indicator"></span> <?= $employmentStatus !== '' ? $employmentStatus : '—' ?></div>
                        </div>
                        <div class="cw-info-item">
                            <label>Employment Type</label>
                            <div class="cw-value cw-value-type"><?= $employmentType !== 'N/A' ? $employmentType : '—' ?></div>
                        </div>
                        <div class="cw-info-item">
                            <label>Date Hired</label>
                            <div class="cw-value"><?= $hireDate !== 'N/A' ? $hireDate : '—' ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Security -->
            <div class="cw-card">
                <div class="cw-card-head">
                    <div class="cw-card-head-content">
                        <h3>Account Security</h3>
                    </div>
                </div>
                <div class="cw-card-body">
                    <?php if ($passwordSuccess): ?>
                        <div class="cw-flash success"><?= htmlspecialchars($passwordSuccess) ?></div>
                    <?php endif; ?>
                    <?php if ($passwordError): ?>
                        <div class="cw-flash error"><?= htmlspecialchars($passwordError) ?></div>
                    <?php endif; ?>
                    <div class="cw-info-grid">
                        <div class="cw-info-item">
                            <label>Account Email</label>
                            <div class="cw-value"><?= $email !== '' ? $email : '—' ?></div>
                        </div>
                        <div class="cw-info-item">
                            <label>Password Status</label>
                            <div class="cw-value"><?= $hasUserAccount ? 'Set' : 'Not set' ?></div>
                        </div>
                        <div class="cw-info-item">
                            <label>Last Password Change</label>
                            <div class="cw-value"><?= $passwordChanged !== null ? $passwordChanged : 'Never changed' ?></div>
                        </div>
                    </div>
                    <div class="cw-card-actions">
                        <?php if ($hasUserAccount): ?>
                            <button type="button" class="cw-btn primary" onclick="pwOpenModal()">Change Password</button>
                        <?php else: ?>
                            <span class="cw-text-muted">No linked user account found.</span>
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
            <h3 id="pwModalTitle">Change Password</h3>
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

        fetch('/modules/compliance/lib/api/verify_current_password.php', {
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

(function(){
    var flashes = document.querySelectorAll('.cw-flash.success');
    flashes.forEach(function(el){
        requestAnimationFrame(function(){
            el.style.display = 'flex';
            el.offsetHeight;
            el.style.opacity = '1';
            el.style.transform = 'translateY(0)';
        });
        setTimeout(function(){
            el.style.opacity = '0';
            el.style.transform = 'translateY(-4px)';
            el.style.maxHeight = '0';
            el.style.marginTop = '0';
            el.style.marginBottom = '0';
            el.style.paddingTop = '0';
            el.style.paddingBottom = '0';
            el.style.overflow = 'hidden';
            setTimeout(function(){ el.remove(); }, 320);
        }, 5000);
    });
})();

(function(){
    var emailInput = document.querySelector('input[name="email"]');
    if (!emailInput) return;
    var feedback = emailInput.parentElement.querySelector('.cw-field-feedback') || document.createElement('div');
    if (!feedback.parentElement) {
        emailInput.parentElement.appendChild(feedback);
    }
    feedback.className = 'cw-field-feedback';
    var timer = null;
    var currentSuggestion = null;

    var typoMap = {
        'gmail.comm': 'gmail.com',
        'gmail.con': 'gmail.com',
        'gmail.co': 'gmail.com',
        'yahooo.com': 'yahoo.com',
        'hotmial.com': 'hotmail.com',
        'outlok.com': 'outlook.com',
        'gmail.cmo': 'gmail.com',
        'gmail.cim': 'gmail.com',
        'gmail.cpm': 'gmail.com',
        'gmail.c0m': 'gmail.com',
        'yahoo.cm': 'yahoo.com',
        'yaho.com': 'yahoo.com',
        'hotmail.cm': 'hotmail.com',
        'hotmial.cm': 'hotmail.com',
        'outlook.cm': 'outlook.com',
        'outlok.cm': 'outlook.com'
    };

    function escapeHtml(str) {
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function validate() {
        var val = emailInput.value.trim();
        emailInput.classList.remove('is-valid', 'is-invalid', 'is-warning');
        feedback.className = 'cw-field-feedback';
        feedback.innerHTML = '';
        emailInput.setAttribute('aria-invalid', 'false');
        currentSuggestion = null;

        if (val === '') {
            return true;
        }

        if (/\s/.test(val)) {
            showState('invalid', '<span>Please enter a valid email address.</span>');
            emailInput.setAttribute('aria-invalid', 'true');
            return false;
        }

        var atCount = 0;
        var atPos = -1;
        for (var i = 0; i < val.length; i++) {
            if (val[i] === '@') {
                atCount++;
                atPos = i;
            }
        }

        if (atCount !== 1 || atPos === 0 || atPos === val.length - 1) {
            showState('invalid', '<span>Please enter a valid email address.</span>');
            emailInput.setAttribute('aria-invalid', 'true');
            return false;
        }

        var local = val.substring(0, atPos);
        var domain = val.substring(atPos + 1).toLowerCase();

        if (!/^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+$/.test(local)) {
            showState('invalid', '<span>Please enter a valid email address.</span>');
            emailInput.setAttribute('aria-invalid', 'true');
            return false;
        }

        if (!/^[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+$/.test(domain) || domain.startsWith('.') || domain.endsWith('.') || domain.includes('..')) {
            showState('invalid', '<span>Please enter a valid email address.</span>');
            emailInput.setAttribute('aria-invalid', 'true');
            return false;
        }

        var suggestion = typoMap[domain];
        if (suggestion) {
            currentSuggestion = local + '@' + suggestion;
            showState('warning', '<span>Did you mean <a href="mailto:' + escapeHtml(currentSuggestion) + '">' + escapeHtml(currentSuggestion) + '</a>?</span>');
            return true;
        }

        showState('valid', '<span>Valid email address</span>');
        return true;
    }

    function showState(state, html) {
        emailInput.classList.add('is-' + state);
        feedback.innerHTML = html;
        feedback.classList.add('visible');
        feedback.classList.add(state);
    }

    function isValid() {
        return emailInput.classList.contains('is-valid') || emailInput.classList.contains('is-warning');
    }

    emailInput.addEventListener('input', function() {
        if (timer) clearTimeout(timer);
        timer = setTimeout(validate, 200);
    });

    emailInput.addEventListener('blur', function() {
        if (timer) clearTimeout(timer);
        validate();
    });

    window.validateEmailField = function() {
        var val = emailInput.value.trim();
        if (val === '') return true;
        validate();
        return isValid() || emailInput.classList.contains('is-warning');
    };
})();
</script>

<style>
:root {
    --cw-bg: #f4f5f7;
    --cw-card: #ffffff;
    --cw-border: #e1e4e8;
    --cw-border-light: #e8eaed;
    --cw-text: #2f3439;
    --cw-muted: #737b83;
    --cw-primary: #2f6fa8;
    --cw-primary-hover: #285f91;
    --cw-success: #3f8053;
    --cw-danger: #b34b4b;
    --cw-radius: 6px;
}

.cw-module {
    background: var(--cw-bg);
    padding: 0;
}

.cw-flash {
    padding: 10px 12px;
    border-radius: 4px;
    font-size: 11.5px;
    font-weight: 600;
    margin-top: 10px;
    display: none;
    align-items: center;
    gap: 8px;
    line-height: 1.4;
}
.cw-flash:first-child { margin-top: 0; }
.cw-flash.success {
    display: flex;
    background: #f6fbf7;
    color: var(--cw-success);
    border: 1px solid #c8e6d0;
}
.cw-flash.error {
    display: block;
    background: #fdf6f6;
    color: var(--cw-danger);
    border: 1px solid #f5c6c6;
}

.cw-row {
    display: grid;
    grid-template-columns: 31% 1fr;
    gap: 16px;
    align-items: start;
}
.cw-col-main { min-width: 0; }
.cw-col-side { min-width: 0; }
@media (max-width: 768px) {
    .cw-row { grid-template-columns: 1fr; }
    .cw-col-side { position: static; width: auto; }
}

.cw-card {
    background: var(--cw-card);
    border: 1px solid var(--cw-border);
    border-radius: var(--cw-radius);
    box-shadow: none;
    overflow: hidden;
    margin-bottom: 14px;
}
.cw-card-head {
    padding: 10px 15px;
    border-bottom: 1px solid var(--cw-border-light);
    display: flex;
    align-items: center;
    gap: 10px;
}
.cw-card-head-content { flex: 1; min-width: 0; }
.cw-card-head-content h3 {
    margin: 0;
    font-size: 15px;
    line-height: 1.3;
    font-weight: 600;
    color: var(--cw-text);
}
.cw-card-body {
    padding: 12px 15px;
}

.cw-info-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 11px 15px;
}
@media (max-width: 600px) {
    .cw-info-grid { grid-template-columns: 1fr; }
}
.cw-info-item label {
    display: block;
    margin-bottom: 4px;
    font-size: 10.5px;
    line-height: 1.3;
    font-weight: 600;
    color: var(--cw-muted);
}
.cw-info-item input {
    width: 100%;
    height: 38px;
    padding: 6px 9px;
    border: 1px solid var(--cw-border);
    border-radius: 4px;
    background: var(--cw-card);
    color: var(--cw-text);
    font-size: 12.5px;
    line-height: 1.3;
    box-sizing: border-box;
    outline: none;
}
.cw-info-item input:focus {
    border-color: #8ba9c5;
    box-shadow: 0 0 0 2px rgba(37, 99, 166, 0.08);
}
.cw-info-item input[readonly] {
    background: #f7f8f9;
    color: #4e555c;
}
.cw-email-field { position: relative; }
.cw-field-feedback {
    margin-top: 4px;
    font-size: 10.5px;
    line-height: 1.35;
    font-weight: 600;
    min-height: 14px;
}
.cw-field-feedback.success { color: var(--cw-success); }
.cw-field-feedback.error { color: var(--cw-danger); }
.cw-field-feedback.warning { color: #8a6d1a; }
.cw-field-feedback a { color: inherit; text-decoration: underline; font-weight: 700; }

.cw-email-field input.is-valid {
    border-color: var(--cw-success);
    box-shadow: 0 0 0 2px rgba(63, 128, 83, 0.08);
}
.cw-email-field input.is-invalid {
    border-color: var(--cw-danger);
    box-shadow: 0 0 0 2px rgba(179, 75, 75, 0.08);
}
.cw-email-field input.is-warning {
    border-color: #8a6d1a;
    box-shadow: 0 0 0 2px rgba(138, 109, 26, 0.08);
}

.cw-card-actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 8px;
    margin-top: 12px;
}
@media (max-width: 480px) {
    .cw-card-actions { flex-direction: column; }
    .cw-card-actions .cw-btn { width: 100%; justify-content: center; }
}

.cw-stamp { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 4px; white-space: nowrap; }
.cw-stamp-success { background: #f6fbf7; color: var(--cw-success); border: 1px solid #c8e6d0; }
.cw-stamp-warning { background: #fffbf0; color: #8a6d1a; border: 1px solid #f0e4a8; }
.cw-stamp-danger { background: #fdf6f6; color: var(--cw-danger); border: 1px solid #f5c6c6; }
.cw-stamp-secondary { background: #f3f4f6; color: #4b5563; border: 1px solid #e5e7eb; }
.cw-stamp-badge-success {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 3px 10px;
    background: #f6fbf7;
    color: var(--cw-success);
    border: 1px solid #c8e6d0;
    font-size: 11px;
    font-weight: 600;
    border-radius: 4px;
    white-space: nowrap;
}
.cw-status-dot {
    width: 6px;
    height: 6px;
    display: inline-block;
    border-radius: 50%;
    background: var(--cw-success);
    margin-right: 4px;
}
.cw-card-head .cw-stamp { margin-left: auto; }

.cw-employment-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}
@media (max-width: 900px) {
    .cw-employment-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 600px) {
    .cw-employment-grid { grid-template-columns: 1fr; }
}
.cw-value {
    font-size: 12.5px;
    line-height: 1.4;
    font-weight: 500;
    color: var(--cw-text);
}
.cw-value-id {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
    font-size: 12px;
}
.cw-status-indicator {
    width: 6px;
    height: 6px;
    display: inline-block;
    border-radius: 50%;
    background: var(--cw-success);
    margin-right: 4px;
}
.cw-value-type {
    font-size: 12px;
    color: var(--cw-muted);
}

.cw-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-height: 33px;
    padding: 6px 11px;
    border: 1px solid var(--cw-border);
    border-radius: 4px;
    background: var(--cw-card);
    color: var(--cw-text);
    font-size: 11.5px;
    font-weight: 600;
    line-height: 1.2;
    cursor: pointer;
    white-space: nowrap;
    transition: border-color .15s ease, background .15s ease, color .15s ease;
    text-decoration: none;
}
.cw-btn:hover {
    border-color: var(--cw-primary);
    color: var(--cw-primary);
}
.cw-btn:active {
    background: #f3f4f6;
}
.cw-btn:focus-visible {
    outline: 2px solid var(--cw-primary);
    outline-offset: 2px;
}
.cw-btn.primary {
    background: var(--cw-primary);
    border-color: var(--cw-primary);
    color: #ffffff;
}
.cw-btn.primary:hover {
    background: var(--cw-primary-hover);
    border-color: var(--cw-primary-hover);
    color: #ffffff;
}

.cw-profile {
    text-align: center;
    padding: 10px 0 12px;
    border-bottom: 1px solid var(--cw-border-light);
    margin-bottom: 10px;
}
.cw-profile-avatar {
    width: 58px;
    height: 58px;
    border-radius: 50%;
    background: #eef1f5;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    font-weight: 600;
    color: #5b6472;
    margin-bottom: 8px;
    overflow: hidden;
}
.cw-profile-name {
    margin-top: 6px;
    font-size: 15px;
    line-height: 1.3;
    font-weight: 600;
    color: var(--cw-text);
    word-break: break-word;
}
.cw-profile-no {
    margin-top: 2px;
    font-size: 11px;
    color: var(--cw-muted);
}
.cw-profile-meta {
    margin-top: 6px;
    font-size: 11.5px;
    line-height: 1.5;
    color: var(--cw-muted);
}
.cw-profile-status {
    margin-top: 8px;
}
.cw-profile-stats {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid var(--cw-border-light);
}
.cw-profile-stat {
    margin-bottom: 10px;
}
.cw-profile-stat:last-child { margin-bottom: 0; }
.cw-profile-stat-value {
    font-size: 11.5px;
    color: #41484f;
    line-height: 1.4;
    overflow-wrap: anywhere;
    word-break: break-word;
}
.cw-profile-stat-label {
    font-size: 10px;
    color: var(--cw-muted);
    margin-bottom: 3px;
    font-weight: 600;
}

.cw-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.45); z-index: 1050; align-items: center; justify-content: center; padding: 16px; }
.cw-modal-overlay.active { display: flex; }
.cw-modal { background: #fff; border-radius: 6px; box-shadow: 0 4px 20px rgba(15, 23, 42, 0.12); max-width: 480px; width: 100%; max-height: calc(100vh - 32px); overflow-y: auto; }
.cw-modal-head { display: flex; align-items: center; justify-content: space-between; padding: 12px 15px; border-bottom: 1px solid var(--cw-border-light); }
.cw-modal-head h3 { margin: 0; font-size: 15px; font-weight: 600; color: var(--cw-text); }
.cw-modal-close { background: none; border: none; font-size: 1.1rem; cursor: pointer; color: var(--cw-muted); line-height: 1; padding: 3px 5px; border-radius: 4px; }
.cw-modal-close:hover { color: var(--cw-text); background: #f3f4f6; }
.cw-modal-body { padding: 14px 15px; }
.cw-modal .profile-field { margin-bottom: 10px; }
.cw-modal .profile-field:last-child { margin-bottom: 0; }
.cw-modal .profile-field label { display: block; font-size: 10.5px; font-weight: 600; color: var(--cw-text); margin-bottom: 4px; }
.cw-modal .profile-field input { width: 100%; box-sizing: border-box; padding: 6px 9px; border: 1px solid var(--cw-border); border-radius: 4px; font-size: 12.5px; outline: none; background: var(--cw-card); color: var(--cw-text); }
.cw-modal .profile-field input:focus { border-color: var(--cw-primary); box-shadow: 0 0 0 2px rgba(47, 111, 168, 0.08); }
.cw-modal-footer { display: flex; align-items: center; justify-content: flex-end; gap: 8px; padding: 10px 15px; border-top: 1px solid var(--cw-border-light); }
.pw-field-wrap { position: relative; }
.pw-field-feedback { font-size: 10.5px; font-weight: 600; margin-top: 4px; margin-bottom: 6px; display: none; }
.pw-field-feedback.success { display: block; color: var(--cw-success); }
.pw-field-feedback.error { display: block; color: var(--cw-danger); }
.pw-field-wrap.is-valid .profile-field input { border-color: var(--cw-success); box-shadow: 0 0 0 2px rgba(63, 128, 83, 0.08); }
.pw-field-wrap.is-invalid .profile-field input { border-color: var(--cw-danger); box-shadow: 0 0 0 2px rgba(179, 75, 75, 0.08); }
.pw-requirements { display: flex; flex-direction: column; gap: 3px; margin-top: 6px; }
.pw-req { font-size: 10.5px; font-weight: 600; color: var(--cw-muted); display: flex; align-items: center; gap: 5px; }
.pw-req::before { content: '○'; font-size: 0.8rem; }
.pw-req.met { color: var(--cw-success); }
.pw-req.met::before { content: '●'; }

.cw-text-muted {
    font-size: 11.5px;
    color: var(--cw-muted);
    line-height: 1.4;
}

@media (max-width: 600px) {
    .cw-row { grid-template-columns: 1fr; }
    .cw-col-side { position: static; width: auto; }
    .cw-info-grid { grid-template-columns: 1fr; }
    .cw-card-head { padding: 10px 12px; }
    .cw-card-body { padding: 10px 12px; }
    .cw-profile-avatar { width: 50px; height: 50px; font-size: 15px; }
}
</style>

