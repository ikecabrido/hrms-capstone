<?php
$employeeClass = $employeeClass ?? new Employee();
$learningMessage = '';
$learningError = '';
$validLearnerTeams = ['individual-learner', 'department-team', 'role-group'];
$validDepartments = ['academic', 'administration', 'human-resources', 'information-technology'];
$validActivities = ['course', 'learning-path', 'workshop', 'compliance-training'];
$validStatuses = ['active', 'in-progress', 'low', 'inactive'];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && in_array($_POST['action'] ?? '', ['save_training_engagement', 'update_training_engagement'], true)) {
    $isEngagementUpdate = ($_POST['action'] ?? '') === 'update_training_engagement';
    $engagementId = (int) ($_POST['id'] ?? 0);
    $learningInput = [
        'learner_team' => trim((string) ($_POST['learner_team'] ?? '')),
        'department' => trim((string) ($_POST['department'] ?? '')),
        'training_activity' => trim((string) ($_POST['training_activity'] ?? '')),
        'engagement_status' => trim((string) ($_POST['engagement_status'] ?? '')),
        'engagement_data' => trim((string) ($_POST['engagement_data'] ?? '')),
        'learning_action' => trim((string) ($_POST['learning_action'] ?? '')),
    ];

    if (
        !in_array($learningInput['learner_team'], $validLearnerTeams, true)
        || !in_array($learningInput['department'], $validDepartments, true)
        || !in_array($learningInput['training_activity'], $validActivities, true)
        || !in_array($learningInput['engagement_status'], $validStatuses, true)
        || $learningInput['engagement_data'] === ''
        || $learningInput['learning_action'] === ''
    ) {
        $learningError = 'Complete all learning engagement fields before saving.';
    } else {
        try {
            $engagementData = [
                ...$learningInput,
                'created_by' => (int) ($_SESSION['employee_id'] ?? 0),
            ];
            if ($isEngagementUpdate && $engagementId > 0) {
                $employeeClass->updateTrainingEngagement($engagementId, $engagementData);
                $learningMessage = "Training engagement #{$engagementId} was updated.";
            } else {
                $engagementId = $employeeClass->saveTrainingEngagement($engagementData);
                $learningMessage = "Training engagement #{$engagementId} was saved.";
            }
        } catch (Throwable $exception) {
            error_log('[workforce] Training engagement save failed: ' . $exception->getMessage());
            $learningError = 'The training engagement could not be saved. Please try again.';
        }
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'delete_training_engagement') {
    $engagementId = (int) ($_POST['id'] ?? 0);
    if ($engagementId > 0) {
        try {
            $employeeClass->deleteTrainingEngagement($engagementId);
            $learningMessage = "Training engagement #{$engagementId} was deleted.";
        } catch (Throwable $exception) {
            error_log('[workforce] Training engagement deletion failed: ' . $exception->getMessage());
            $learningError = 'The training engagement could not be deleted.';
        }
    }
}

$trainingEngagementInputs = $employeeClass->getTrainingEngagementInputs();
?>
<section class="wf-resource-page">
    <div class="wf-resource-hero">
        <span class="wa-eyebrow">Learning and development</span>
        <h1>Turnover risk indicator</h1>
        <p>Review participation, progress, and support needs so learning activities remain effective and accessible.</p>
    </div>
    
    <form class="wf-resource-send" method="POST">
            <input type="hidden" name="action" value="save_training_engagement">
            <input type="hidden" name="id" value="">
            <span class="wa-eyebrow">Learning input</span>
            <h2>Record training engagement</h2>
            <p class="wf-resource-send-intro">Capture learning participation data for review and follow-up by the Learning system.</p>
            <?php if ($learningError): ?>
                <p class="wf-resource-send-status" role="alert"><?= htmlspecialchars($learningError) ?></p>
                <script>
                    window.alert(<?= json_encode($learningError, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
                </script>
            <?php endif; ?>
            <?php if ($learningMessage): ?>
                <p class="wf-resource-send-status" role="status"><?= htmlspecialchars($learningMessage) ?></p>
                <script>
                    window.alert(<?= json_encode($learningMessage, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
                    window.location.replace(window.location.pathname + '?page=turnover-risk-indicator');
                </script>
            <?php endif; ?>
            <div class="wf-resource-send-grid">
                <label>
                    Learner or team
                    <select name="learner_team" required>
                        <option value="">Select learner or team</option>
                        <option value="individual-learner">Individual learner</option>
                        <option value="department-team">Department team</option>
                        <option value="role-group">Role group</option>
                    </select>
                </label>
                <label>
                    Department
                    <select name="department" required>
                        <option value="">Select a department</option>
                        <option value="academic">Academic</option>
                        <option value="administration">Administration</option>
                        <option value="human-resources">Human Resources</option>
                        <option value="information-technology">Information Technology</option>
                    </select>
                </label>
                <label>
                    Training activity
                    <select name="training_activity" required>
                        <option value="">Select training activity</option>
                        <option value="course">Course</option>
                        <option value="learning-path">Learning path</option>
                        <option value="workshop">Workshop</option>
                        <option value="compliance-training">Compliance training</option>
                    </select>
                </label>
                <label>
                    Engagement status
                    <select name="engagement_status" required>
                        <option value="">Select engagement status</option>
                        <option value="active">Active</option>
                        <option value="in-progress">In progress</option>
                        <option value="low">Low participation</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </label>
                <label class="wf-resource-send-wide">
                    Training engagement data
                    <textarea name="engagement_data" rows="4" placeholder="Describe attendance, participation, progress, completion, or learner activity." required></textarea>
                </label>
                <label class="wf-resource-send-wide">
                    Recommended learning action
                    <textarea name="learning_action" rows="4" placeholder="Describe the recommended follow-up, such as a reminder, coaching, mentoring, or additional support." required></textarea>
                </label>
            </div>
            <button type="submit" class="wf-resource-send-button">Save learning data</button>
            <p class="wf-resource-send-note">Saved data appears in the Learning data review below.</p>
    </form>
    <section class="wf-engagement-review" aria-labelledby="engagement-review-title">
        <div class="wf-engagement-review-heading">
            <div>
                <span class="wa-eyebrow">Learning data review</span>
                <h2 id="engagement-review-title">Training engagement review</h2>
                <p>Review learner participation and follow-up needs before preparing data for Learning.</p>
            </div>
            <button type="button" class="wf-engagement-review-button">Review learning data</button>
        </div>
        <?php if ($trainingEngagementInputs): ?>
            <div class="wa-table-scroll">
                <table class="wa-table">
                    <thead>
                        <tr>
                            <th>Learner or team</th>
                            <th>Department</th>
                            <th>Training activity</th>
                            <th>Status</th>
                            <th>Engagement data</th>
                            <th>Recommended action</th>
                            <th>Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trainingEngagementInputs as $engagement): ?>
                            <tr>
                                <td><?= htmlspecialchars(ucwords(str_replace('-', ' ', $engagement['learner_team']))) ?></td>
                                <td><?= htmlspecialchars(ucwords(str_replace('-', ' ', $engagement['department']))) ?></td>
                                <td><?= htmlspecialchars(ucwords(str_replace('-', ' ', $engagement['training_activity']))) ?></td>
                                <td><span class="wa-pill"><?= htmlspecialchars(ucwords(str_replace('-', ' ', $engagement['engagement_status']))) ?></span></td>
                                <td><?= nl2br(htmlspecialchars($engagement['engagement_data'])) ?></td>
                                <td><?= nl2br(htmlspecialchars($engagement['learning_action'])) ?></td>
                                <td><?= htmlspecialchars(date('M d, Y h:i A', strtotime($engagement['created_at']))) ?></td>
                                <td>
                                    <button type="button" class="wa-button wa-button-small" data-edit-engagement="<?= htmlspecialchars(json_encode($engagement), ENT_QUOTES, 'UTF-8') ?>">Edit</button>
                                    <form method="post" style="display:inline" onsubmit="return confirm('Delete this training engagement?');">
                                        <input type="hidden" name="action" value="delete_training_engagement">
                                        <input type="hidden" name="id" value="<?= (int) $engagement['id'] ?>">
                                        <button type="submit" class="wa-button wa-button-small">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="wa-empty">No training engagement data has been saved yet.</p>
        <?php endif; ?>
    </section>
    <script>
    document.querySelectorAll('[data-edit-engagement]').forEach(function (button) {
        button.addEventListener('click', function () {
            var record = JSON.parse(button.dataset.editEngagement);
            var form = document.querySelector('form.wf-resource-send');
            form.querySelector('[name="action"]').value = 'update_training_engagement';
            form.querySelector('[name="id"]').value = record.id;
            ['learner_team', 'department', 'training_activity', 'engagement_status', 'engagement_data', 'learning_action'].forEach(function (name) {
                var field = form.querySelector('[name="' + name + '"]');
                if (field) field.value = record[name] || '';
            });
            form.querySelector('button[type="submit"]').textContent = 'Update learning data';
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
    </script>
</section>
