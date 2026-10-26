<?php
require_once dirname(__DIR__, 3) . '/classes/program.php';
require_once dirname(__DIR__, 3) . '/classes/ProfileLink.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

function pm_h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function pm_dt_local($value): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }
    $timestamp = strtotime($value);
    return $timestamp === false ? '' : date('Y-m-d\TH:i', $timestamp);
}

function pm_date_label($value): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }
    $timestamp = strtotime($value);
    return $timestamp === false ? '' : date('M j, Y g:i A', $timestamp);
}

function pm_initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= mb_strtoupper(mb_substr($part, 0, 1));
    }
    return $initials !== '' ? $initials : '?';
}

$programId = (int) ($_GET['id'] ?? 0);
$actorId = (int) ($_SESSION['employee_id'] ?? 0);
$sessionRole = strtolower((string) ($_SESSION['learning_role'] ?? ''));
$isAdmin = $sessionRole === 'admin' || !empty($_SESSION['is_admin']) || !empty($_SESSION['admin_access']);

$programService = null;
$program = null;
$programCourses = [];
$programSkills = [];
$programTrainers = [];
$programParticipants = [];
$video = null;
$summary = ['total' => 0, 'assessed' => 0, 'passed' => 0, 'failed' => 0, 'pending' => 0, 'average_score' => null];
$allCourses = [];
$allSkills = [];
$allEmployees = [];
$schemaReady = false;
$loadError = '';

try {
    $pdo = (new Database())->getConnection();
    $programService = new Program($pdo);
    $schemaReady = $programService->schemaReady();

    $allCourses = $pdo->query("SELECT id, title, status FROM ld_course WHERE status <> 'archived' ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);
    $allSkills = $pdo->query("SELECT id, name FROM ld_skill WHERE status = 'active' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $allEmployees = $programService->getEmployeeDirectory();

    if ($programId > 0) {
        $program = $programService->getById($programId);
        if ($program) {
            $programCourses = $programService->getCourses($programId);
            $programSkills = $programService->getSkills($programId);
            $programTrainers = $programService->getTrainers($programId);
            $programParticipants = $programService->getParticipants($programId);
            $video = $programService->getVideoConference($programId);
            $summary = $programService->getAssessmentSummary($programId);
        }
    }
} catch (Throwable $e) {
    DbError::capture($e, 'instructor/training-subpage/program');
    $loadError = 'The program builder could not load its data.';
}

$isEdit = $programId > 0;
$canManage = !$isEdit || ($programService && $program && $programService->canEvaluate($programId, $actorId, $isAdmin));
$formDisabled = !$schemaReady || !$canManage || ($isEdit && !$program);

$selectedCourseIds = array_map(static function ($row) {
    return (int) $row['id'];
}, $programCourses);
$selectedSkillIds = array_map(static function ($row) {
    return (int) $row['id'];
}, $programSkills);
$selectedParticipantIds = array_map(static function ($row) {
    return (int) $row['employee_id'];
}, $programParticipants);

// Make sure the owner is visible as a trainer even on a legacy program whose
// trainer link table was empty before this feature existed.
$trainerMap = [];
foreach ($programTrainers as $trainer) {
    $trainerMap[(int) $trainer['employee_id']] = [
        'employee_id' => (int) $trainer['employee_id'],
        'full_name' => (string) ($trainer['full_name'] ?? ''),
        'email' => (string) ($trainer['email'] ?? ''),
        'department_name' => (string) ($trainer['department_name'] ?? ''),
        'position_name' => (string) ($trainer['position_name'] ?? ''),
        'profile_url' => LearningProfileLink::url((int) $trainer['employee_id'], 'instructor'),
    ];
}
$ownerId = (int) ($program['instructor_id'] ?? 0);
if ($ownerId <= 0 && !$isEdit) {
    $ownerId = $actorId;
}
if ($ownerId > 0 && !isset($trainerMap[$ownerId])) {
    foreach ($allEmployees as $employee) {
        if ((int) $employee['employee_id'] === $ownerId) {
            $trainerMap[$ownerId] = [
                'employee_id' => $ownerId,
                'full_name' => (string) $employee['full_name'],
                'email' => (string) $employee['email'],
                'department_name' => (string) ($employee['department_name'] ?? ''),
                'position_name' => (string) ($employee['position_name'] ?? ''),
                'profile_url' => LearningProfileLink::url($ownerId, 'instructor'),
            ];
            break;
        }
    }
}
$selectedTrainers = array_values($trainerMap);

$programStatus = strtolower((string) ($program['status'] ?? 'active'));
$expiresAt = (string) ($program['expires_at'] ?? '');
$statusClass = 'is-active';
$statusText = 'Active';
if ($programStatus === 'archived') {
    $statusClass = 'is-archived';
    $statusText = 'Archived';
} elseif ($expiresAt !== '' && strtotime($expiresAt) !== false && strtotime($expiresAt) < time()) {
    $statusClass = 'is-expired';
    $statusText = 'Expired';
}
$deliveryMode = (string) ($program['delivery_mode'] ?? 'face_to_face');
$formAction = $isEdit ? 'pages/instructor/training-subpage/ajax/edit-program.php' : 'pages/instructor/training-subpage/ajax/add-program.php';
$evaluationUrl = '?page=instructor/training-subpage/program-evaluations' . ($isEdit ? '&id=' . $programId : '');
$listingUrl = '?page=instructor/training-subpage/program-evaluations';
$backUrl = '?page=instructor/training';
?>

<div class="module-content program-page">
    <div class="pm-topbar">
        <a class="pm-back" href="<?= pm_h($backUrl) ?>"><i class="fas fa-arrow-left" aria-hidden="true"></i> Back to Trainings</a>
        <div class="pm-topbar-actions">
            <?php if ($isEdit): ?>
                <a class="pm-btn pm-btn-quiet" href="<?= pm_h($evaluationUrl) ?>"><i class="fas fa-clipboard-check" aria-hidden="true"></i> Evaluate Trainees <span class="pm-count"><?= (int) $summary['total'] ?></span></a>
            <?php endif; ?>
            <a class="pm-btn pm-btn-quiet" href="<?= pm_h($listingUrl) ?>"><i class="fas fa-list-check" aria-hidden="true"></i> All Evaluations</a>
        </div>
    </div>

    <?php if (!$schemaReady): ?>
        <div class="pm-notice is-warning" role="alert">
            <i class="fas fa-database" aria-hidden="true"></i>
            <div>
                <strong>Database update required.</strong>
                Apply <code>modules/learning/rainzui/ld_program_delivery_assessment_2026-10-26.sql</code> from
                Admin &gt; Moderation &gt; Export LD Database &gt; Apply schema file, then reload this page.
            </div>
        </div>
    <?php endif; ?>

    <?php if ($loadError !== ''): ?>
        <div class="pm-notice is-error" role="alert"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i><?= pm_h($loadError) ?></div>
    <?php endif; ?>

    <?php if ($isEdit && !$program): ?>
        <div class="pm-card pm-empty-state">
            <i class="fas fa-briefcase" aria-hidden="true"></i>
            <h2>Program not found</h2>
            <p>The program may have been archived or removed.</p>
            <a class="pm-btn pm-btn-primary" href="<?= pm_h($backUrl) ?>">Back to Trainings</a>
        </div>
    <?php else: ?>
        <?php if (!$canManage): ?>
            <div class="pm-notice is-warning" role="alert">
                <i class="fas fa-lock" aria-hidden="true"></i>
                <div>You can view this program, but only its assigned trainers or an administrator can change it.</div>
            </div>
        <?php endif; ?>

        <form id="program-form" class="pm-form" method="post" action="<?= pm_h($formAction) ?>" data-disabled="<?= $formDisabled ? '1' : '0' ?>" data-skip>
            <input type="hidden" name="id" value="<?= $programId > 0 ? $programId : '' ?>" />
            <?= class_exists('CSRF') ? CSRF::field() : '' ?>

            <fieldset class="pm-fieldset" <?= $formDisabled ? 'disabled' : '' ?>>
                <section class="pm-card">
                    <div class="pm-card-head">
                        <div>
                            <span class="pm-eyebrow"><?= $isEdit ? 'Program run' : 'New program' ?></span>
                            <h1><?= $isEdit ? 'Edit Program' : 'Create Program' ?></h1>
                            <p>Build the program, choose its delivery format, and connect courses, skills, trainers and trainees.</p>
                        </div>
                        <span class="pm-status <?= pm_h($statusClass) ?>"><?= pm_h($statusText) ?></span>
                    </div>

                    <div class="pm-form-grid">
                        <label class="pm-field pm-span-2">
                            <span>Program title <em>*</em></span>
                            <input class="pm-input" type="text" name="title" maxlength="255" required value="<?= pm_h($program['title'] ?? '') ?>" placeholder="e.g. Leadership Development Program" />
                        </label>

                        <label class="pm-field">
                            <span>Status</span>
                            <select class="pm-input" name="status">
                                <option value="active" <?= ($programStatus === 'active' ? 'selected' : '') ?>>Active</option>
                                <option value="archived" <?= ($programStatus === 'archived' ? 'selected' : '') ?>>Archived</option>
                            </select>
                        </label>

                        <label class="pm-field">
                            <span>Delivery format</span>
                            <select class="pm-input" name="delivery_mode" id="pm-delivery-mode">
                                <option value="face_to_face" <?= $deliveryMode !== 'online' ? 'selected' : '' ?>>Face-to-face</option>
                                <option value="online" <?= $deliveryMode === 'online' ? 'selected' : '' ?>>Online</option>
                            </select>
                        </label>

                        <label class="pm-field">
                            <span>Starts at</span>
                            <input class="pm-input" type="datetime-local" name="starts_at" id="pm-starts-at" value="<?= pm_h(pm_dt_local($program['starts_at'] ?? '')) ?>" />
                        </label>

                        <label class="pm-field">
                            <span>Ends at</span>
                            <input class="pm-input" type="datetime-local" name="ends_at" value="<?= pm_h(pm_dt_local($program['ends_at'] ?? '')) ?>" />
                        </label>

                        <label class="pm-field">
                            <span>Expires at</span>
                            <input class="pm-input" type="datetime-local" name="expires_at" value="<?= pm_h(pm_dt_local($program['expires_at'] ?? '')) ?>" />
                        </label>

                        <label class="pm-field" id="pm-location-field">
                            <span>Venue / location</span>
                            <input class="pm-input" type="text" name="location" maxlength="255" value="<?= pm_h($program['location'] ?? '') ?>" placeholder="e.g. Training Room 2, Head Office" />
                        </label>

                        <label class="pm-field pm-span-2">
                            <span>Description</span>
                            <textarea class="pm-input" name="description" rows="5" placeholder="Describe the program goals, audience and outcomes..."><?= pm_h($program['description'] ?? '') ?></textarea>
                        </label>
                    </div>
                </section>

                <div class="pm-two-col">
                    <section class="pm-card">
                        <div class="pm-card-head is-compact">
                            <div>
                                <h2>Courses</h2>
                                <p>Link one or more courses to this program. Select all or choose individually.</p>
                            </div>
                            <i class="fas fa-book-open pm-head-icon" aria-hidden="true"></i>
                        </div>

                        <details class="pm-picker" data-picker="courses">
                            <summary>
                                <span class="pm-picker-title">Select courses</span>
                                <span class="pm-picker-count" data-picker-count>0 selected</span>
                                <i class="fas fa-chevron-down" aria-hidden="true"></i>
                            </summary>
                            <div class="pm-picker-panel">
                                <div class="pm-picker-tools">
                                    <input type="search" class="pm-input" data-picker-search placeholder="Search courses..." aria-label="Search courses" />
                                    <button type="button" class="pm-mini-btn" data-picker-select-all>Select all</button>
                                    <button type="button" class="pm-mini-btn is-quiet" data-picker-clear>Clear</button>
                                </div>
                                <div class="pm-picker-list">
                                    <?php if (!$allCourses): ?>
                                        <p class="pm-muted">No active courses are available yet.</p>
                                    <?php endif; ?>
                                    <?php foreach ($allCourses as $course): ?>
                                        <label class="pm-check" data-search-text="<?= pm_h(mb_strtolower($course['title'] . ' ' . $course['status'])) ?>">
                                            <input type="checkbox" name="course_ids[]" value="<?= (int) $course['id'] ?>" <?= in_array((int) $course['id'], $selectedCourseIds, true) ? 'checked' : '' ?> />
                                            <span class="pm-check-text">
                                                <strong><?= pm_h($course['title']) ?></strong>
                                                <small><?= pm_h(ucfirst($course['status'])) ?></small>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </details>
                    </section>

                    <section class="pm-card">
                        <div class="pm-card-head is-compact">
                            <div>
                                <h2>Skills / tags</h2>
                                <p>These skills rank trainer suggestions and describe the program outcomes.</p>
                            </div>
                            <i class="fas fa-star pm-head-icon" aria-hidden="true"></i>
                        </div>

                        <details class="pm-picker" data-picker="skills" id="pm-skills-picker">
                            <summary>
                                <span class="pm-picker-title">Select skills</span>
                                <span class="pm-picker-count" data-picker-count>0 selected</span>
                                <i class="fas fa-chevron-down" aria-hidden="true"></i>
                            </summary>
                            <div class="pm-picker-panel">
                                <div class="pm-picker-tools">
                                    <input type="search" class="pm-input" data-picker-search placeholder="Search skills..." aria-label="Search skills" />
                                    <button type="button" class="pm-mini-btn" data-picker-select-all>Select all</button>
                                    <button type="button" class="pm-mini-btn is-quiet" data-picker-clear>Clear</button>
                                </div>
                                <div class="pm-picker-list">
                                    <?php if (!$allSkills): ?>
                                        <p class="pm-muted">No active skills are available yet.</p>
                                    <?php endif; ?>
                                    <?php foreach ($allSkills as $skill): ?>
                                        <label class="pm-check" data-search-text="<?= pm_h(mb_strtolower($skill['name'])) ?>">
                                            <input type="checkbox" name="skill_ids[]" value="<?= (int) $skill['id'] ?>" <?= in_array((int) $skill['id'], $selectedSkillIds, true) ? 'checked' : '' ?> />
                                            <span class="pm-check-text"><strong><?= pm_h($skill['name']) ?></strong></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </details>
                    </section>
                </div>

                <section class="pm-card">
                    <div class="pm-card-head is-compact">
                        <div>
                            <h2>Trainers</h2>
                            <p>Search by name or ask for suggestions. Suggestions are ranked by the skills selected above, then by existing course and program experience.</p>
                        </div>
                        <span class="pm-count" id="pm-trainer-count"><?= count($selectedTrainers) ?> selected</span>
                    </div>

                    <div class="pm-trainer-search">
                        <div class="pm-search-input">
                            <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                            <input type="search" id="pm-trainer-q" class="pm-input" placeholder="Search by name, email, department or position..." autocomplete="off" />
                        </div>
                        <button type="button" class="pm-btn pm-btn-primary" id="pm-trainer-search-btn"><i class="fas fa-wand-magic-sparkles" aria-hidden="true"></i> Suggest trainers</button>
                    </div>
                    <p class="pm-hint" id="pm-trainer-hint">Pick skills above to make the suggestions more precise.</p>

                    <div class="pm-selected-trainers" id="pm-trainer-selected" aria-live="polite"></div>

                    <div class="pm-trainer-results" id="pm-trainer-results">
                        <div class="pm-muted pm-results-empty"><i class="fas fa-users" aria-hidden="true"></i> Search to see trainer suggestions.</div>
                    </div>
                </section>

                <section class="pm-card">
                    <div class="pm-card-head is-compact">
                        <div>
                            <h2>Trainees</h2>
                            <p>Every selected employee gets an evaluation row on the trainee assessment page.</p>
                        </div>
                        <span class="pm-count" id="pm-participant-count"><?= count($selectedParticipantIds) ?> selected</span>
                    </div>

                    <details class="pm-picker" data-picker="participants" id="pm-participants-picker">
                        <summary>
                            <span class="pm-picker-title">Select trainees</span>
                            <span class="pm-picker-count" data-picker-count>0 selected</span>
                            <i class="fas fa-chevron-down" aria-hidden="true"></i>
                        </summary>
                        <div class="pm-picker-panel">
                            <div class="pm-picker-tools">
                                <input type="search" class="pm-input" data-picker-search placeholder="Search employees..." aria-label="Search employees" />
                                <button type="button" class="pm-mini-btn" data-picker-select-all>Select all</button>
                                <button type="button" class="pm-mini-btn is-quiet" data-picker-clear>Clear</button>
                            </div>
                            <div class="pm-picker-list is-tall">
                                <?php if (!$allEmployees): ?>
                                    <p class="pm-muted">No active employees are available.</p>
                                <?php endif; ?>
                                <?php foreach ($allEmployees as $employee): ?>
                                    <?php $employeeProfileUrl = LearningProfileLink::url((int) $employee['employee_id'], 'learner'); ?>
                                    <label class="pm-check" data-search-text="<?= pm_h(mb_strtolower($employee['full_name'] . ' ' . $employee['email'] . ' ' . ($employee['department_name'] ?? '') . ' ' . ($employee['position_name'] ?? ''))) ?>">
                                        <input type="checkbox" name="participant_ids[]" value="<?= (int) $employee['employee_id'] ?>" <?= in_array((int) $employee['employee_id'], $selectedParticipantIds, true) ? 'checked' : '' ?> />
                                        <span class="pm-avatar" aria-hidden="true"><?= pm_h(pm_initials((string) $employee['full_name'])) ?></span>
                                        <span class="pm-check-text">
                                            <strong><a class="pm-person-link" href="<?= pm_h($employeeProfileUrl) ?>"><?= pm_h($employee['full_name']) ?></a></strong>
                                            <small><?= pm_h(trim(($employee['position_name'] ?? '') . (($employee['department_name'] ?? '') !== '' ? ' · ' . $employee['department_name'] : ''), ' ·')) ?></small>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </details>

                    <label class="pm-check pm-enroll-check">
                        <input type="checkbox" name="enroll_participants" value="1" />
                        <span class="pm-check-text">
                            <strong>Also enroll trainees in the selected courses</strong>
                            <small>Adds each trainee to the course roster. Existing enrollments are left untouched.</small>
                        </span>
                    </label>
                </section>

                <section class="pm-card" id="pm-online-card">
                    <div class="pm-card-head is-compact">
                        <div>
                            <h2>Online training session</h2>
                            <p>For online programs, create the live session that trainees will join. You can also manage it later from Online Training.</p>
                        </div>
                        <i class="fas fa-video pm-head-icon" aria-hidden="true"></i>
                    </div>

                    <div class="pm-form-grid">
                        <label class="pm-field pm-span-2">
                            <span>Session title</span>
                            <input class="pm-input" type="text" name="online_title" id="pm-online-title" maxlength="255" value="<?= pm_h($video['title'] ?? '') ?>" placeholder="Defaults to the program title" />
                        </label>
                        <label class="pm-field">
                            <span>Platform</span>
                            <select class="pm-input" name="online_platform">
                                <?php $platform = (string) ($video['platform'] ?? 'google_meet'); ?>
                                <option value="google_meet" <?= $platform === 'google_meet' ? 'selected' : '' ?>>Google Meet</option>
                                <option value="zoom" <?= $platform === 'zoom' ? 'selected' : '' ?>>Zoom</option>
                                <option value="other" <?= $platform === 'other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </label>
                        <label class="pm-field">
                            <span>Session status</span>
                            <select class="pm-input" name="online_status">
                                <?php $onlineStatus = (string) ($video['status'] ?? 'scheduled'); ?>
                                <option value="scheduled" <?= $onlineStatus === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                                <option value="completed" <?= $onlineStatus === 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="archived" <?= $onlineStatus === 'archived' ? 'selected' : '' ?>>Archived</option>
                            </select>
                        </label>
                        <label class="pm-field pm-span-2">
                            <span>Meeting link</span>
                            <input class="pm-input" type="url" name="online_meeting_link" maxlength="500" value="<?= pm_h($video['meeting_link'] ?? '') ?>" placeholder="https://meet.google.com/..." />
                        </label>
                        <label class="pm-field">
                            <span>Session date &amp; time</span>
                            <input class="pm-input" type="datetime-local" name="online_scheduled_at" id="pm-online-scheduled" value="<?= pm_h(pm_dt_local($video['scheduled_at'] ?? ($program['starts_at'] ?? ''))) ?>" />
                        </label>
                        <label class="pm-field">
                            <span>Duration (minutes)</span>
                            <input class="pm-input" type="number" name="online_duration_minutes" min="1" max="1440" value="<?= pm_h($video['duration_minutes'] ?? '') ?>" placeholder="e.g. 90" />
                        </label>
                    </div>
                </section>

                <div class="pm-actions">
                    <button type="submit" class="pm-btn pm-btn-primary" id="pm-save-btn">
                        <i class="fas fa-floppy-disk" aria-hidden="true"></i>
                        <?= $isEdit ? 'Save Changes' : 'Create Program' ?>
                    </button>
                    <?php if ($isEdit): ?>
                        <button type="button" class="pm-btn pm-btn-quiet" id="pm-rerun-open"><i class="fas fa-rotate-right" aria-hidden="true"></i> Re-run Program</button>
                    <?php endif; ?>
                    <a class="pm-btn pm-btn-quiet" href="<?= pm_h($backUrl) ?>">Cancel</a>
                </div>
            </fieldset>
        </form>
    <?php endif; ?>
</div>

<?php if ($isEdit && $program && $canManage): ?>
    <div id="pm-rerun-modal" class="modal-overlay ld-modal-scrim" role="dialog" aria-modal="true" aria-labelledby="pm-rerun-title" style="display:none;">
        <div class="ld-modal-panel pm-modal-panel">
            <form id="pm-rerun-form" data-skip>
                <div class="ld-modal-header">
                    <div>
                        <h3 id="pm-rerun-title">Re-run program</h3>
                        <p>Copies courses, skills, trainers and active trainees into a fresh run. Add a new online session if needed; evaluations start blank.</p>
                    </div>
                    <button type="button" class="cat-modal-close" id="pm-rerun-close" aria-label="Close"><i class="fas fa-times" aria-hidden="true"></i></button>
                </div>
                <div class="ld-modal-body pm-form-grid">
                    <input type="hidden" name="id" value="<?= $programId ?>" />
                    <label class="pm-field pm-span-2">
                        <span>New run title</span>
                        <input class="pm-input" type="text" name="title" maxlength="255" value="<?= pm_h(($program['title'] ?? '') . ' (Re-run)') ?>" />
                    </label>
                    <label class="pm-field">
                        <span>Starts at</span>
                        <input class="pm-input" type="datetime-local" name="starts_at" value="<?= pm_h(pm_dt_local($program['starts_at'] ?? '')) ?>" />
                    </label>
                    <label class="pm-field">
                        <span>Ends at</span>
                        <input class="pm-input" type="datetime-local" name="ends_at" value="<?= pm_h(pm_dt_local($program['ends_at'] ?? '')) ?>" />
                    </label>
                    <label class="pm-field">
                        <span>Expires at</span>
                        <input class="pm-input" type="datetime-local" name="expires_at" value="<?= pm_h(pm_dt_local($program['expires_at'] ?? '')) ?>" />
                    </label>
                    <p class="pm-rerun-note pm-span-2"><i class="fas fa-circle-info" aria-hidden="true"></i> Adjust the dates before creating the new run. The new run opens automatically.</p>
                </div>
                <div class="ld-modal-actions">
                    <button type="button" class="pm-btn pm-btn-quiet" id="pm-rerun-cancel">Cancel</button>
                    <button type="submit" class="pm-btn pm-btn-primary" id="pm-rerun-submit"><i class="fas fa-rotate-right" aria-hidden="true"></i> Create Re-run</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<script>
(function () {
    'use strict';

    var form = document.getElementById('program-form');
    var csrfToken = window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.content || '';
    var schemaReady = <?= $schemaReady ? 'true' : 'false' ?>;
    var selectedTrainers = new Map();
    var initialTrainers = <?= json_encode($selectedTrainers, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    var trainerSearchTimer = null;

    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (character) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[character];
        });
    }

    function showToast(message, type) {
        if (typeof window.showToast === 'function') {
            window.showToast(message, type || 'info');
        }
    }

    function syncDeliveryMode() {
        var mode = document.getElementById('pm-delivery-mode');
        var onlineCard = document.getElementById('pm-online-card');
        var locationField = document.getElementById('pm-location-field');
        if (!mode) return;
        var isOnline = mode.value === 'online';
        if (onlineCard) {
            onlineCard.hidden = !isOnline;
        }
        if (locationField) {
            locationField.hidden = isOnline;
        }
        if (isOnline) {
            var onlineTitle = document.getElementById('pm-online-title');
            var programTitle = form?.querySelector('input[name="title"]');
            if (onlineTitle && !onlineTitle.value && programTitle) {
                onlineTitle.value = programTitle.value;
            }
            var onlineScheduled = document.getElementById('pm-online-scheduled');
            var startsAt = document.getElementById('pm-starts-at');
            if (onlineScheduled && !onlineScheduled.value && startsAt) {
                onlineScheduled.value = startsAt.value;
            }
        }
    }

    function updatePicker(picker) {
        if (!picker) return;
        var boxes = picker.querySelectorAll('input[type="checkbox"]');
        var checked = picker.querySelectorAll('input[type="checkbox"]:checked').length;
        var counter = picker.querySelector('[data-picker-count]');
        if (counter) {
            counter.textContent = checked + ' selected';
        }
        picker.classList.toggle('has-selection', checked > 0);

        if (picker.dataset.picker === 'participants') {
            var participantCount = document.getElementById('pm-participant-count');
            if (participantCount) participantCount.textContent = checked + ' selected';
        }
    }

    function updatePickerFromSearch(picker) {
        var search = picker.querySelector('[data-picker-search]');
        var query = search ? search.value.trim().toLowerCase() : '';
        picker.querySelectorAll('[data-search-text]').forEach(function (item) {
            item.hidden = query !== '' && !item.dataset.searchText.includes(query);
        });
    }

    document.querySelectorAll('.pm-picker').forEach(function (picker) {
        updatePicker(picker);

        picker.addEventListener('change', function (event) {
            if (event.target.matches('input[type="checkbox"]')) {
                updatePicker(picker);
                if (picker.dataset.picker === 'skills') {
                    var hint = document.getElementById('pm-trainer-hint');
                    if (hint) hint.textContent = 'Skills changed — suggestions will refresh.';
                    clearTimeout(trainerSearchTimer);
                    trainerSearchTimer = setTimeout(searchTrainers, 350);
                }
            }
        });

        var search = picker.querySelector('[data-picker-search]');
        if (search) {
            search.addEventListener('input', function () {
                updatePickerFromSearch(picker);
            });
            search.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') event.preventDefault();
            });
        }

        var selectAll = picker.querySelector('[data-picker-select-all]');
        if (selectAll) {
            selectAll.addEventListener('click', function () {
                picker.querySelectorAll('[data-search-text]').forEach(function (item) {
                    if (!item.hidden) {
                        var box = item.querySelector('input[type="checkbox"]');
                        if (box) box.checked = true;
                    }
                });
                updatePicker(picker);
                if (picker.dataset.picker === 'skills') searchTrainers();
            });
        }

        var clear = picker.querySelector('[data-picker-clear]');
        if (clear) {
            clear.addEventListener('click', function () {
                picker.querySelectorAll('input[type="checkbox"]').forEach(function (box) { box.checked = false; });
                updatePicker(picker);
                if (picker.dataset.picker === 'skills') searchTrainers();
            });
        }
    });

    function selectedSkillIds() {
        return Array.from(document.querySelectorAll('#pm-skills-picker input[type="checkbox"]:checked')).map(function (box) {
            return box.value;
        });
    }

    function renderSelectedTrainers() {
        var container = document.getElementById('pm-trainer-selected');
        var count = document.getElementById('pm-trainer-count');
        if (count) count.textContent = selectedTrainers.size + ' selected';
        if (!container) return;

        if (!selectedTrainers.size) {
            container.innerHTML = '<p class="pm-muted"><i class="fas fa-user-plus" aria-hidden="true"></i> No trainers selected yet. The program owner is added automatically on save.</p>';
            return;
        }

        container.innerHTML = Array.from(selectedTrainers.values()).map(function (trainer) {
            var initials = String(trainer.full_name || '?').split(/\s+/).slice(0, 2).map(function (part) { return part.charAt(0); }).join('').toUpperCase();
            var name = escapeHtml(trainer.full_name || 'Employee');
            var nameHtml = trainer.profile_url
                ? '<a class="pm-person-link" href="' + escapeHtml(trainer.profile_url) + '">' + name + '</a>'
                : name;
            return '<div class="pm-selected-chip" data-trainer-id="' + escapeHtml(trainer.employee_id) + '">'
                + '<span class="pm-avatar" aria-hidden="true">' + escapeHtml(initials) + '</span>'
                + '<span class="pm-selected-name"><strong>' + nameHtml + '</strong><small>' + escapeHtml(trainer.position_name || trainer.department_name || trainer.email || '') + '</small></span>'
                + '<button type="button" class="pm-chip-remove" data-remove-trainer="' + escapeHtml(trainer.employee_id) + '" aria-label="Remove trainer"><i class="fas fa-times" aria-hidden="true"></i></button>'
                + '</div>';
        }).join('');
    }

    function addTrainer(trainer) {
        if (!trainer || !trainer.employee_id) return;
        selectedTrainers.set(String(trainer.employee_id), trainer);
        renderSelectedTrainers();
    }

    function renderTrainerResults(trainers) {
        var container = document.getElementById('pm-trainer-results');
        var hint = document.getElementById('pm-trainer-hint');
        if (!container) return;

        if (hint) {
            hint.textContent = trainers.length
                ? 'Ranked by the selected skills and existing training experience.'
                : 'No matching trainers found. Try a different name or fewer skills.';
        }

        if (!trainers.length) {
            container.innerHTML = '<div class="pm-muted pm-results-empty"><i class="fas fa-user-slash" aria-hidden="true"></i> No trainers match this search.</div>';
            return;
        }

        container.innerHTML = trainers.map(function (trainer) {
            var checked = selectedTrainers.has(String(trainer.employee_id)) ? ' checked' : '';
            var initials = String(trainer.full_name || '?').split(/\s+/).slice(0, 2).map(function (part) { return part.charAt(0); }).join('').toUpperCase();
            var meta = [trainer.position_name, trainer.department_name].filter(Boolean).join(' · ');
            var evidence = trainer.matched_skill_count > 0
                ? trainer.matched_skill_count + ' skill' + (trainer.matched_skill_count === 1 ? '' : 's') + ' matched · score ' + trainer.score
                : (trainer.is_trainer ? 'Designated trainer' : 'Employee');
            var matched = trainer.matched_skills ? '<small class="pm-matched-skills">' + escapeHtml(trainer.matched_skills) + '</small>' : '';
            var trainerName = escapeHtml(trainer.full_name || 'Employee');
            var trainerNameHtml = trainer.profile_url
                ? '<a class="pm-person-link" href="' + escapeHtml(trainer.profile_url) + '">' + trainerName + '</a>'
                : trainerName;
            return '<label class="pm-trainer-result" data-profile-url="' + escapeHtml(trainer.profile_url || '') + '">'
                + '<input type="checkbox" data-result-trainer="' + escapeHtml(trainer.employee_id) + '"' + checked + ' />'
                + '<span class="pm-avatar" aria-hidden="true">' + escapeHtml(initials) + '</span>'
                + '<span class="pm-trainer-result-body">'
                + '<strong>' + trainerNameHtml + '</strong>'
                + (meta ? '<small>' + escapeHtml(meta) + '</small>' : '')
                + '<small class="pm-evidence">' + escapeHtml(evidence) + (trainer.is_trainer ? ' · Trainer' : '') + '</small>'
                + matched
                + '</span>'
                + '</label>';
        }).join('');

        container.querySelectorAll('[data-result-trainer]').forEach(function (box) {
            box.addEventListener('change', function () {
                var id = box.dataset.resultTrainer;
                if (!box.checked) {
                    selectedTrainers.delete(id);
                    renderSelectedTrainers();
                    return;
                }
                var card = box.closest('.pm-trainer-result');
                var parts = card ? Array.from(card.querySelectorAll('strong, small')) : [];
                addTrainer({
                    employee_id: id,
                    full_name: parts[0] ? parts[0].textContent : 'Employee',
                    position_name: parts[1] ? parts[1].textContent : '',
                    department_name: '',
                    email: '',
                    profile_url: card ? card.dataset.profileUrl : ''
                });
            });
        });
    }

    function searchTrainers() {
        if (!form || form.dataset.disabled === '1') return;
        var query = document.getElementById('pm-trainer-q');
        var container = document.getElementById('pm-trainer-results');
        if (!container) return;

        container.innerHTML = '<div class="pm-muted pm-results-empty"><i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Finding trainers...</div>';

        var params = new URLSearchParams();
        params.set('q', query ? query.value.trim() : '');
        selectedSkillIds().forEach(function (id) { params.append('skill_ids[]', id); });

        fetch('pages/instructor/training-subpage/ajax/suggest-trainers.php?' + params.toString(), {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (response) { return response.json().then(function (data) { return { ok: response.ok, data: data }; }); })
        .then(function (result) {
            if (!result.ok || !result.data.success) {
                throw new Error(result.data.message || 'Could not load trainers.');
            }
            renderTrainerResults(result.data.trainers || []);
        })
        .catch(function (error) {
            container.innerHTML = '<div class="pm-notice is-error"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i>' + escapeHtml(error.message || 'Could not load trainers.') + '</div>';
            console.error('Trainer search failed:', error);
        });
    }

    document.getElementById('pm-trainer-search-btn')?.addEventListener('click', searchTrainers);
    document.getElementById('pm-trainer-q')?.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            searchTrainers();
        }
    });

    document.getElementById('pm-trainer-selected')?.addEventListener('click', function (event) {
        var button = event.target.closest('[data-remove-trainer]');
        if (!button) return;
        selectedTrainers.delete(String(button.dataset.removeTrainer));
        renderSelectedTrainers();
        document.querySelectorAll('[data-result-trainer]').forEach(function (box) {
            if (String(box.dataset.resultTrainer) === String(button.dataset.removeTrainer)) {
                box.checked = false;
            }
        });
    });

    var deliveryMode = document.getElementById('pm-delivery-mode');
    var previousDeliveryMode = deliveryMode ? deliveryMode.value : '';
    deliveryMode?.addEventListener('change', function () {
        var onlineStatus = form?.querySelector('select[name="online_status"]');
        if (deliveryMode.value === 'online' && previousDeliveryMode !== 'online' && onlineStatus?.value === 'archived') {
            onlineStatus.value = 'scheduled';
        }
        previousDeliveryMode = deliveryMode.value;
        syncDeliveryMode();
    });
    syncDeliveryMode();

    initialTrainers.forEach(addTrainer);
    renderSelectedTrainers();
    if (schemaReady) {
        searchTrainers();
    }

    form?.addEventListener('submit', function (event) {
        event.preventDefault();
        if (form.dataset.disabled === '1') return;

        var submit = document.getElementById('pm-save-btn');
        var original = submit ? submit.innerHTML : '';
        if (submit) {
            submit.disabled = true;
            submit.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Saving...';
        }

        var startsAt = form.querySelector('input[name="starts_at"]');
        var endsAt = form.querySelector('input[name="ends_at"]');
        var expiresAt = form.querySelector('input[name="expires_at"]');
        if (startsAt?.value && endsAt?.value && new Date(endsAt.value) < new Date(startsAt.value)) {
            showToast('The end date must be after the start date.', 'error');
            if (submit) { submit.disabled = false; submit.innerHTML = original; }
            return;
        }
        if (startsAt?.value && expiresAt?.value && new Date(expiresAt.value) < new Date(startsAt.value)) {
            showToast('The expiry date must be after the start date.', 'error');
            if (submit) { submit.disabled = false; submit.innerHTML = original; }
            return;
        }

        var payload = new FormData(form);
        selectedTrainers.forEach(function (trainer) { payload.append('trainer_ids[]', trainer.employee_id); });
        if (document.getElementById('pm-delivery-mode')?.value === 'online') {
            payload.append('online_create', '1');
        }

        fetch(form.action, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken
            },
            body: payload
        })
        .then(function (response) { return response.json().then(function (data) { return { ok: response.ok, data: data }; }); })
        .then(function (result) {
            if (!result.ok || !result.data.success) {
                throw new Error(result.data.message || result.data.error || 'Unable to save the program.');
            }
            var warnings = Array.isArray(result.data.warnings) ? result.data.warnings : [];
            showToast(warnings.length ? warnings[0] : (result.data.message || 'Program saved.'), warnings.length ? 'info' : 'success');
            var nextId = result.data.id || form.querySelector('input[name="id"]')?.value;
            setTimeout(function () {
                if (nextId) {
                    window.location.href = '?page=instructor/training-subpage/program&id=' + encodeURIComponent(nextId);
                } else {
                    window.location.reload();
                }
            }, warnings.length ? 1600 : 900);
        })
        .catch(function (error) {
            showToast(error.message || 'Unable to save the program.', 'error');
            console.error('Program save failed:', error);
        })
        .finally(function () {
            if (submit) {
                submit.disabled = false;
                submit.innerHTML = original;
            }
        });
    });

    var rerunOpen = document.getElementById('pm-rerun-open');
    var rerunModal = document.getElementById('pm-rerun-modal');
    var rerunForm = document.getElementById('pm-rerun-form');
    function closeRerun() {
        if (rerunModal) rerunModal.style.display = 'none';
    }
    rerunOpen?.addEventListener('click', function () {
        if (rerunModal) rerunModal.style.display = 'flex';
    });
    document.getElementById('pm-rerun-close')?.addEventListener('click', closeRerun);
    document.getElementById('pm-rerun-cancel')?.addEventListener('click', closeRerun);
    rerunModal?.addEventListener('click', function (event) {
        if (event.target === rerunModal) closeRerun();
    });

    rerunForm?.addEventListener('submit', function (event) {
        event.preventDefault();
        var submit = document.getElementById('pm-rerun-submit');
        var original = submit ? submit.innerHTML : '';
        if (submit) {
            submit.disabled = true;
            submit.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Creating...';
        }

        fetch('pages/instructor/training-subpage/ajax/rerun-program.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken
            },
            body: new FormData(rerunForm)
        })
        .then(function (response) { return response.json().then(function (data) { return { ok: response.ok, data: data }; }); })
        .then(function (result) {
            if (!result.ok || !result.data.success) {
                throw new Error(result.data.message || result.data.error || 'Unable to re-run the program.');
            }
            showToast(result.data.message || 'Re-run created.', 'success');
            setTimeout(function () {
                window.location.href = result.data.redirect || '?page=instructor/training-subpage/program&id=' + result.data.id;
            }, 700);
        })
        .catch(function (error) {
            showToast(error.message || 'Unable to re-run the program.', 'error');
            console.error('Program rerun failed:', error);
        })
        .finally(function () {
            if (submit) {
                submit.disabled = false;
                submit.innerHTML = original;
            }
        });
    });
})();
</script>
