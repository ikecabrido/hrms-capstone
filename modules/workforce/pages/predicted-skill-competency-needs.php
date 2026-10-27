<?php
/**
 * Predicted skill / competency needs.
 *
 * This page intentionally contains its complete page flow:
 * validation, Learning handoff, engagement input, and rendered output.
 */
$employeeClass = $employeeClass ?? new Employee();
$competencyMessage = '';
$competencyError = '';
$validSkills = [
    'data-analysis' => 'Data analysis',
    'digital-literacy' => 'Digital literacy',
    'leadership' => 'Leadership',
    'communication' => 'Communication',
    'project-management' => 'Project management',
    'customer-service' => 'Customer service',
    'problem-solving' => 'Problem solving',
];
$validDepartments = [
    'academic' => 'Academic',
    'administration' => 'Administration',
    'human-resources' => 'Human Resources',
    'information-technology' => 'Information Technology',
    'finance' => 'Finance',
];
$validProficiencies = [
    'beginner' => 'Beginner',
    'intermediate' => 'Intermediate',
    'advanced' => 'Advanced',
    'expert' => 'Expert',
];
$validOutputs = [
    'skills-course' => 'Skills course',
    'completion-report-by-department' => 'Completion report by department',
    'coaching-plan' => 'Coaching plan',
    'assessment-and-progress-report' => 'Assessment and progress report',
];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && in_array($_POST['action'] ?? '', ['send_competency_requirement', 'update_competency_requirement'], true)) {
    $isCompetencyUpdate = ($_POST['action'] ?? '') === 'update_competency_requirement';
    $competencyId = (int) ($_POST['id'] ?? 0);
    $skillName = trim((string) ($_POST['skill_name'] ?? ''));
    $department = trim((string) ($_POST['target_department'] ?? ''));
    $priority = strtolower(trim((string) ($_POST['priority_level'] ?? '')));
    $employeesNeeded = filter_var($_POST['employees_needing_training'] ?? null, FILTER_VALIDATE_INT);
    $targetProficiency = trim((string) ($_POST['target_proficiency'] ?? ''));
    $expectedOutput = trim((string) ($_POST['expected_learning_output'] ?? ''));
    $notes = trim((string) ($_POST['notes'] ?? ''));
    $validPriorities = ['low', 'medium', 'high', 'critical'];

    if (
        $skillName === ''
        || $department === ''
        || !array_key_exists($skillName, $validSkills)
        || !array_key_exists($department, $validDepartments)
        || !in_array($priority, $validPriorities, true)
        || $employeesNeeded === false
        || $employeesNeeded < 0
        || !array_key_exists($targetProficiency, $validProficiencies)
        || !array_key_exists($expectedOutput, $validOutputs)
    ) {
        $competencyError = 'Complete the skill, department, priority, learner count, target proficiency, and expected output.';
    } else {
        try {
            $competencyData = [
                'skill_name' => $validSkills[$skillName],
                'target_department' => $validDepartments[$department],
                'priority_level' => $priority,
                'employees_needing_training' => $employeesNeeded,
                'target_proficiency' => $validProficiencies[$targetProficiency],
                'expected_learning_output' => $validOutputs[$expectedOutput],
                'notes' => $notes,
                'created_by' => (int) ($_SESSION['employee_id'] ?? 0),
            ];
            if ($isCompetencyUpdate && $competencyId > 0) {
                $employeeClass->updateCompetencyRequirement($competencyId, $competencyData);
                $competencyMessage = "Competency requirement #{$competencyId} was updated.";
            } else {
                $requirementId = $employeeClass->saveCompetencyRequirement($competencyData);
                $competencyMessage = "Competency requirement #{$requirementId} was sent to the Learning system.";
            }
        } catch (Throwable $exception) {
            error_log('[workforce] Competency requirement failed: ' . $exception->getMessage());
            $competencyError = 'The competency requirement could not be sent. Please try again.';
        }
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'delete_competency_requirement') {
    $competencyId = (int) ($_POST['id'] ?? 0);
    if ($competencyId > 0) {
        try {
            $employeeClass->deleteCompetencyRequirement($competencyId);
            $competencyMessage = "Competency requirement #{$competencyId} was deleted.";
        } catch (Throwable $exception) {
            error_log('[workforce] Competency requirement deletion failed: ' . $exception->getMessage());
            $competencyError = 'The competency requirement could not be deleted.';
        }
    }
}

$competencyRequirements = $employeeClass->getCompetencyRequirements();
?>
<section class="wf-resource-page">
    <div class="wf-resource-hero">
        <span class="wa-eyebrow">Capability planning</span>
        <h1>Predicted skill / Competency needs</h1>
        <p>Send predicted capability gaps to the Learning system and use learner engagement data to prioritize support.</p>
    </div>

    <section class="wf-resource-send" style="margin-top:1.25rem;">
        <span class="wa-eyebrow">Workforce to Learning</span>
        <h2>Predicted competency need</h2>
        <p class="wf-resource-send-intro">Provide the target input and expected learning output for a predicted skill gap.</p>
        <div class="wf-io-flow wf-competency-flow" aria-label="Workforce to Learning competency flow">
            <div class="wf-io-flow-step wf-io-flow-input">
                <span class="wf-io-flow-number">1</span>
                <div><strong>Workforce input</strong><small>Describe the skill gap, priority, and affected employees.</small></div>
            </div>
            <span class="wf-io-flow-arrow" aria-hidden="true">→</span>
            <div class="wf-io-flow-step wf-io-flow-output">
                <span class="wf-io-flow-number">2</span>
                <div><strong>Learning output</strong><small>Define the expected course or completion result.</small></div>
            </div>
        </div>

        <?php if ($competencyMessage): ?>
            <p class="wf-resource-send-status" role="status"><?= htmlspecialchars($competencyMessage) ?></p>
            <script>
                window.alert(<?= json_encode($competencyMessage, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
                window.location.replace(window.location.pathname + '?page=predicted-skill-competency-needs');
            </script>
        <?php endif; ?>
        <?php if ($competencyError): ?>
            <p class="wf-resource-send-status" style="color:#b42318;" role="alert"><?= htmlspecialchars($competencyError) ?></p>
            <script>
                window.alert(<?= json_encode($competencyError, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
            </script>
        <?php endif; ?>

        <form method="post" class="wf-resource-send-grid">
            <input type="hidden" name="action" value="send_competency_requirement">
            <input type="hidden" name="id" value="">
            <label class="wf-io-input-field">
                Skill / competency
                <select name="skill_name" required>
                    <option value="">Select skill or competency</option>
                    <?php foreach ($validSkills as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="wf-io-input-field">
                Target department
                <select name="target_department" required>
                    <option value="">Select target department</option>
                    <?php foreach ($validDepartments as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="wf-io-input-field">
                Priority
                <select name="priority_level" required>
                    <option value="">Select priority</option>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="critical">Critical</option>
                </select>
            </label>
            <label class="wf-io-input-field">
                Employees needing training
                <input type="number" name="employees_needing_training" min="0" required placeholder="10">
            </label>
            <label class="wf-io-input-field">
                Target proficiency
                <select name="target_proficiency" required>
                    <option value="">Select target proficiency</option>
                    <?php foreach ($validProficiencies as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="wf-resource-send-wide wf-io-output-field">
                Expected Learning output
                <select name="expected_learning_output" required>
                    <option value="">Select expected Learning output</option>
                    <?php foreach ($validOutputs as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="wf-resource-send-wide wf-io-output-field">
                Notes
                <textarea name="notes" rows="3" placeholder="Optional context for the Learning team."></textarea>
            </label>
            <button type="submit" class="wf-resource-send-button"><span aria-hidden="true">↗</span> <span class="wf-submit-label">Send to Learning system</span></button>
        </form>
    </section>

    <section class="wf-engagement-review wf-io-results" style="margin-top:1.25rem;">
        <div class="wf-engagement-review-heading">
            <div>
                <span class="wa-eyebrow">Learning output</span>
                <h2>Saved competency requirements</h2>
                <p>Predicted skill gaps and expected Learning outputs submitted by Workforce.</p>
            </div>
            <?php if ($competencyRequirements): ?><span class="wf-io-results-count"><?= count($competencyRequirements) ?> records</span><?php endif; ?>
        </div>
        <?php if ($competencyRequirements): ?>
            <div class="wa-table-scroll">
                <table class="wa-table">
                    <thead>
                        <tr>
                            <th>Skill / competency</th>
                            <th>Target department</th>
                            <th>Priority</th>
                            <th>Employees needing training</th>
                            <th>Target proficiency</th>
                            <th>Expected Learning output</th>
                            <th>Notes</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($competencyRequirements as $requirement): ?>
                            <tr>
                                <td><?= htmlspecialchars($requirement['skill_name']) ?></td>
                                <td><?= htmlspecialchars($requirement['target_department']) ?></td>
                                <td><span class="wa-pill"><?= htmlspecialchars(ucfirst($requirement['priority_level'])) ?></span></td>
                                <td><?= (int) $requirement['employees_needing_training'] ?></td>
                                <td><?= htmlspecialchars($requirement['target_proficiency']) ?></td>
                                <td><?= htmlspecialchars($requirement['expected_learning_output']) ?></td>
                                <td><?= $requirement['notes'] !== null && $requirement['notes'] !== '' ? nl2br(htmlspecialchars($requirement['notes'])) : '—' ?></td>
                                <td><span class="wa-pill"><?= htmlspecialchars(ucfirst($requirement['status'])) ?></span></td>
                                <td><?= htmlspecialchars(date('M d, Y h:i A', strtotime($requirement['created_at']))) ?></td>
                                <td>
                                    <button type="button" class="wa-button wa-button-small" data-edit-competency="<?= htmlspecialchars(json_encode($requirement), ENT_QUOTES, 'UTF-8') ?>">Edit</button>
                                    <form method="post" style="display:inline" onsubmit="return confirm('Delete this competency requirement?');">
                                        <input type="hidden" name="action" value="delete_competency_requirement">
                                        <input type="hidden" name="id" value="<?= (int) $requirement['id'] ?>">
                                        <button type="submit" class="wa-button wa-button-small">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="wa-empty">No competency requirements have been submitted yet.</p>
        <?php endif; ?>
    </section>
    <script>
    var competencyValueMaps = {
        skill_name: <?= json_encode(array_flip($validSkills), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        target_department: <?= json_encode(array_flip($validDepartments), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        target_proficiency: <?= json_encode(array_flip($validProficiencies), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        expected_learning_output: <?= json_encode(array_flip($validOutputs), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
    };
    document.querySelectorAll('[data-edit-competency]').forEach(function (button) {
        button.addEventListener('click', function () {
            var record = JSON.parse(button.dataset.editCompetency);
            var form = document.querySelector('form.wf-resource-send-grid');
            form.querySelector('[name="action"]').value = 'update_competency_requirement';
            form.querySelector('[name="id"]').value = record.id;
            ['skill_name', 'target_department', 'priority_level', 'employees_needing_training', 'target_proficiency', 'expected_learning_output', 'notes'].forEach(function (name) {
                var field = form.querySelector('[name="' + name + '"]');
                if (field) field.value = competencyValueMaps[name] && competencyValueMaps[name][record[name]] ? competencyValueMaps[name][record[name]] : (record[name] || '');
            });
            form.querySelector('.wf-submit-label').textContent = 'Update competency requirement';
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
    </script>
</section>
