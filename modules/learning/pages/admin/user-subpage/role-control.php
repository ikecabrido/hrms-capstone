<?php
$profileLearningRole = LearningRole::forEmployee($pdo, $employeeId);
$roleControlActorId = (int) ($_SESSION['employee_id'] ?? 0);
$roleControlCanManage = $roleControlActorId > 0
    && $roleControlActorId !== $employeeId
    && ($profile['employment_status'] ?? '') === 'Active'
    && LearningRole::forEmployee($pdo, $roleControlActorId) === 'admin';

if ($roleControlCanManage) {
    $accountCheck = $pdo->prepare('SELECT 1 FROM user_account WHERE employee_id = :employee_id LIMIT 1');
    $accountCheck->execute([':employee_id' => $employeeId]);
    $roleControlCanManage = (bool) $accountCheck->fetchColumn();
}
?>
<div class="learning-role-panel" style="margin:1rem auto 0; padding:0.8rem 0 0; border-top:1px solid rgba(32,0,130,0.14); text-align:left;">
    <label for="profile-learning-role" style="display:block; margin-bottom:0.45rem; color:var(--primary); font-size:0.72rem; font-weight:700; text-transform:uppercase;">Learning Role</label>
    <?php if ($roleControlCanManage): ?>
        <div class="learning-role-controls">
            <select id="profile-learning-role" class="learning-role-select" data-current-role="<?= htmlspecialchars($profileLearningRole, ENT_QUOTES, 'UTF-8') ?>">
                <option value="learner" <?= $profileLearningRole === 'learner' ? 'selected' : '' ?>>Learner</option>
                <option value="instructor" <?= $profileLearningRole === 'instructor' ? 'selected' : '' ?>>Instructor</option>
                <option value="admin" <?= $profileLearningRole === 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
            <button type="button" id="save-profile-learning-role" class="learning-role-save" disabled>Save role</button>
        </div>
        <span id="profile-learning-role-status" role="status" aria-live="polite"></span>
        <script>
        (function() {
            var select = document.getElementById('profile-learning-role');
            var button = document.getElementById('save-profile-learning-role');
            var status = document.getElementById('profile-learning-role-status');
            var previousRole = select.dataset.currentRole;

            select.addEventListener('change', function() {
                button.disabled = select.value === previousRole;
                status.textContent = '';
            });

            button.addEventListener('click', function() {
                var nextRole = select.value;
                if ((nextRole === 'admin' || previousRole === 'admin')
                    && !window.confirm('Change this user\'s Learning role from ' + previousRole + ' to ' + nextRole + '?')) {
                    select.value = previousRole;
                    button.disabled = true;
                    return;
                }

                button.disabled = true;
                select.disabled = true;
                status.textContent = 'Saving...';

                fetch('pages/admin/user-subpage/ajax/update-role.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': window.CSRF_TOKEN || ''
                    },
                    body: JSON.stringify({
                        employee_id: <?= (int) $employeeId ?>,
                        learning_role: nextRole
                    })
                }).then(function(response) {
                    return response.json().then(function(data) {
                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'Could not update the Learning role.');
                        }
                        return data;
                    });
                }).then(function() {
                    status.textContent = 'Role updated. Opening the user profile...';
                    var profilePage = nextRole === 'instructor' ? 'instructor' : 'learner';
                    window.location.href = '?page=admin/user-subpage/' + profilePage + '&id=<?= (int) $employeeId ?>';
                }).catch(function(error) {
                    select.value = previousRole;
                    status.textContent = error.message;
                    select.disabled = false;
                    button.disabled = false;
                });
            });
        })();
        </script>
    <?php else: ?>
        <div style="color:var(--text); font-weight:700;"><?= htmlspecialchars(ucfirst($profileLearningRole), ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
</div>

<style>
    .learning-role-select {
        width: 100%;
        min-width: 0;
        min-height: 38px;
        padding: 0.42rem 0.65rem;
        border: 1px solid rgba(32,0,130,0.2);
        border-radius: 6px;
        background: var(--surface);
        color: var(--text);
        font: inherit;
        font-size: 0.82rem;
    }

    .learning-role-controls {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
        gap: 0.5rem;
    }

    .learning-role-save {
        min-height: 38px;
        padding: 0.45rem 0.8rem;
        border: 0;
        border-radius: 6px;
        background: var(--primary);
        color: var(--surface);
        font: inherit;
        font-size: 0.8rem;
        font-weight: 700;
        cursor: pointer;
        white-space: nowrap;
    }

    .learning-role-save:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    #profile-learning-role-status {
        display: block;
        min-height: 1rem;
        margin-top: 0.25rem;
        font-size: 0.72rem;
        color: var(--primary);
    }
</style>