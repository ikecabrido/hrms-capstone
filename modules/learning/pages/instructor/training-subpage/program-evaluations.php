<?php
require_once dirname(__DIR__, 3) . '/classes/program.php';
require_once dirname(__DIR__, 3) . '/classes/ProfileLink.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

function pe_h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

if (!function_exists('pm_h')) {
    function pm_h($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

function pe_date($value): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return 'Not scheduled';
    }
    $timestamp = strtotime($value);
    return $timestamp === false ? 'Not scheduled' : date('M j, Y g:i A', $timestamp);
}

function pe_initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= mb_strtoupper(mb_substr($part, 0, 1));
    }
    return $initials !== '' ? $initials : '?';
}

function pe_status_label(array $participant): array
{
    $assessmentStatus = strtolower((string) ($participant['assessment_status'] ?? ''));
    $result = strtolower((string) ($participant['result'] ?? ''));
    if ($assessmentStatus === 'completed' && $result === 'passed') {
        return ['passed', 'Passed'];
    }
    if ($assessmentStatus === 'completed' && $result === 'failed') {
        return ['failed', 'Failed'];
    }
    if ($assessmentStatus === 'draft') {
        return ['draft', 'Draft'];
    }
    if ($assessmentStatus === 'completed') {
        return ['complete', 'Complete'];
    }
    return ['pending', 'Not evaluated'];
}

$programId = (int) ($_GET['id'] ?? 0);
$actorId = (int) ($_SESSION['employee_id'] ?? 0);
$sessionRole = strtolower((string) ($_SESSION['learning_role'] ?? ''));
$isAdmin = $sessionRole === 'admin' || !empty($_SESSION['is_admin']) || !empty($_SESSION['admin_access']);

$programService = null;
$schemaReady = false;
$program = null;
$programs = [];
$participants = [];
$summary = ['total' => 0, 'assessed' => 0, 'passed' => 0, 'failed' => 0, 'pending' => 0, 'average_score' => null];
$courses = [];
$trainers = [];
$allowed = true;
$loadError = '';

try {
    $pdo = (new Database())->getConnection();
    $programService = new Program($pdo);
    $schemaReady = $programService->schemaReady();
    $programs = $programService->getAccessiblePrograms($actorId, $isAdmin);

    if ($programId > 0) {
        $program = $programService->getById($programId);
        if (!$program) {
            $allowed = false;
        } else {
            $allowed = $programService->canEvaluate($programId, $actorId, $isAdmin);
            if ($allowed) {
                $participants = $programService->getParticipants($programId);
                $summary = $programService->getAssessmentSummary($programId);
                $courses = $programService->getCourses($programId);
                $trainers = $programService->getTrainers($programId);
            }
        }
    }
} catch (Throwable $e) {
    DbError::capture($e, 'instructor/training-subpage/program-evaluations');
    $loadError = 'The evaluation page could not load its data.';
}

$listingUrl = '?page=instructor/training-subpage/program-evaluations';
$trainingUrl = '?page=instructor/training';
$progress = (int) $summary['total'] > 0 ? round(((int) $summary['assessed'] / (int) $summary['total']) * 100) : 0;
$avg = $summary['average_score'] !== null ? number_format((float) $summary['average_score'], 1) : '—';
?>

<div class="module-content program-eval-page">
    <div class="pm-topbar">
        <a class="pm-back" href="<?= pm_h($trainingUrl) ?>"><i class="fas fa-arrow-left" aria-hidden="true"></i> Back to Training</a>
        <div class="pm-topbar-actions">
            <?php if ($programId > 0 && $program && $allowed): ?>
                <a class="pm-btn pm-btn-quiet" href="?page=instructor/training-subpage/program&id=<?= (int) $programId ?>"><i class="fas fa-pen-to-square" aria-hidden="true"></i> Edit Program</a>
            <?php endif; ?>
            <a class="pm-btn pm-btn-quiet" href="<?= pm_h($listingUrl) ?>"><i class="fas fa-list-check" aria-hidden="true"></i> All Programs</a>
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
        <div class="pm-notice is-error" role="alert"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i><?= pe_h($loadError) ?></div>
    <?php endif; ?>

    <?php if ($programId <= 0): ?>
        <section class="pm-card pm-eval-hero">
            <div>
                <span class="pm-eyebrow">Trainee assessments</span>
                <h1>Trainee Evaluations</h1>
                <p>Open a program to evaluate each participating trainee one by one. Scores, attendance and feedback are saved against the current program run.</p>
            </div>
            <div class="pm-eval-hero-stat">
                <strong><?= count($programs) ?></strong>
                <span>program<?= count($programs) === 1 ? '' : 's' ?></span>
            </div>
        </section>

        <?php if (!$programs): ?>
            <div class="pm-card pm-empty-state">
                <i class="fas fa-clipboard-check" aria-hidden="true"></i>
                <h2>No programs to evaluate</h2>
                <p>Create a program and add trainees first. Programs you own or train for will appear here.</p>
                <a class="pm-btn pm-btn-primary" href="<?= pm_h($trainingUrl) ?>">Go to Trainings</a>
            </div>
        <?php else: ?>
            <div class="pm-program-list">
                <?php foreach ($programs as $item): ?>
                    <?php
                    $itemId = (int) $item['id'];
                    $itemTotal = (int) ($item['participant_count'] ?? 0);
                    $itemAssessed = (int) ($item['assessed_count'] ?? 0);
                    $itemProgress = $itemTotal > 0 ? round(($itemAssessed / $itemTotal) * 100) : 0;
                    $itemStatus = strtolower((string) ($item['status'] ?? 'active'));
                    $itemExpired = !empty($item['expires_at']) && strtotime((string) $item['expires_at']) !== false && strtotime((string) $item['expires_at']) < time();
                    $itemStatusLabel = $itemStatus === 'archived' ? 'Archived' : ($itemExpired ? 'Expired' : 'Active');
                    $itemStatusClass = $itemStatus === 'archived' ? 'is-archived' : ($itemExpired ? 'is-expired' : 'is-active');
                    ?>
                    <a class="pm-program-card" href="<?= pm_h($listingUrl . '&id=' . $itemId) ?>">
                        <div class="pm-program-card-head">
                            <span class="pm-status <?= pm_h($itemStatusClass) ?>"><?= pm_h($itemStatusLabel) ?></span>
                            <span class="pm-program-mode"><i class="fas <?= ($item['delivery_mode'] ?? '') === 'online' ? 'fa-video' : 'fa-location-dot' ?>" aria-hidden="true"></i> <?= ($item['delivery_mode'] ?? '') === 'online' ? 'Online' : 'Face-to-face' ?></span>
                        </div>
                        <h2><?= pm_h($item['title'] ?? 'Untitled program') ?></h2>
                        <p class="pm-program-date"><i class="fas fa-calendar-day" aria-hidden="true"></i> <?= pm_h(pe_date($item['starts_at'] ?? '')) ?></p>
                        <div class="pm-progress-row">
                            <div class="pm-progress-track"><span style="width:<?= (int) $itemProgress ?>%"></span></div>
                            <span><?= $itemAssessed ?>/<?= $itemTotal ?> evaluated</span>
                        </div>
                        <div class="pm-program-card-foot">
                            <span><i class="fas fa-users" aria-hidden="true"></i> <?= (int) ($item['trainer_count'] ?? 0) ?> trainer<?= (int) ($item['trainer_count'] ?? 0) === 1 ? '' : 's' ?></span>
                            <span class="pm-link-arrow">Open evaluations <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php elseif (!$allowed || !$program): ?>
        <div class="pm-card pm-empty-state">
            <i class="fas fa-lock" aria-hidden="true"></i>
            <h2>Evaluation page unavailable</h2>
            <p>This program does not exist, or you are not assigned as one of its trainers.</p>
            <a class="pm-btn pm-btn-primary" href="<?= pm_h($listingUrl) ?>">Back to evaluations</a>
        </div>
    <?php else: ?>
        <?php
        $programStatus = strtolower((string) ($program['status'] ?? 'active'));
        $programExpired = !empty($program['expires_at']) && strtotime((string) $program['expires_at']) !== false && strtotime((string) $program['expires_at']) < time();
        $programStatusLabel = $programStatus === 'archived' ? 'Archived' : ($programExpired ? 'Expired' : 'Active');
        $programStatusClass = $programStatus === 'archived' ? 'is-archived' : ($programExpired ? 'is-expired' : 'is-active');
        ?>
        <section class="pm-card pm-eval-hero">
            <div>
                <div class="pm-eval-title-row">
                    <span class="pm-status <?= pm_h($programStatusClass) ?>"><?= pm_h($programStatusLabel) ?></span>
                    <span class="pm-program-mode"><i class="fas <?= ($program['delivery_mode'] ?? '') === 'online' ? 'fa-video' : 'fa-location-dot' ?>" aria-hidden="true"></i> <?= ($program['delivery_mode'] ?? '') === 'online' ? 'Online' : 'Face-to-face' ?></span>
                </div>
                <h1><?= pm_h($program['title'] ?? 'Program') ?></h1>
                <p><?= pm_h($program['description'] ?: 'No description has been added for this program.') ?></p>
                <div class="pm-eval-meta">
                    <span><i class="fas fa-calendar-day" aria-hidden="true"></i> <?= pm_h(pe_date($program['starts_at'] ?? '')) ?></span>
                    <?php if (!empty($program['ends_at'])): ?><span><i class="fas fa-calendar-check" aria-hidden="true"></i> Ends <?= pm_h(pe_date($program['ends_at'])) ?></span><?php endif; ?>
                    <?php if (!empty($program['expires_at'])): ?><span><i class="fas fa-hourglass-end" aria-hidden="true"></i> Expires <?= pm_h(pe_date($program['expires_at'])) ?></span><?php endif; ?>
                    <?php if (!empty($program['location'])): ?><span><i class="fas fa-location-dot" aria-hidden="true"></i> <?= pm_h($program['location']) ?></span><?php endif; ?>
                </div>
            </div>
            <div class="pm-eval-hero-stat">
                <strong><?= (int) $summary['assessed'] ?>/<?= (int) $summary['total'] ?></strong>
                <span>evaluated</span>
                <div class="pm-progress-track"><span style="width:<?= (int) $progress ?>%"></span></div>
            </div>
        </section>

        <div class="pm-eval-summary">
            <span class="pm-summary-chip"><i class="fas fa-users" aria-hidden="true"></i> <?= (int) $summary['total'] ?> trainee<?= (int) $summary['total'] === 1 ? '' : 's' ?></span>
            <span class="pm-summary-chip is-success"><i class="fas fa-check" aria-hidden="true"></i> <?= (int) $summary['passed'] ?> passed</span>
            <span class="pm-summary-chip is-danger"><i class="fas fa-xmark" aria-hidden="true"></i> <?= (int) $summary['failed'] ?> failed</span>
            <span class="pm-summary-chip is-info"><i class="fas fa-chart-line" aria-hidden="true"></i> <?= pe_h($avg) ?> average</span>
        </div>

        <?php if ($courses || $trainers): ?>
            <div class="pm-eval-context">
                <?php if ($courses): ?>
                    <section class="pm-card pm-context-card">
                        <h2><i class="fas fa-book-open" aria-hidden="true"></i> Courses</h2>
                        <div class="pm-context-list">
                            <?php foreach ($courses as $course): ?>
                                <span class="pm-context-pill"><?= pm_h($course['title']) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
                <?php if ($trainers): ?>
                    <section class="pm-card pm-context-card">
                        <h2><i class="fas fa-user-tie" aria-hidden="true"></i> Trainers</h2>
                        <div class="pm-context-list">
                            <?php foreach ($trainers as $trainer): ?>
                                <?php $trainerUrl = LearningProfileLink::url((int) $trainer['employee_id'], 'instructor'); ?>
                                <a class="pm-context-pill is-person" href="<?= pm_h($trainerUrl) ?>"><?= pm_h($trainer['full_name']) ?></a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <section class="pm-card">
            <div class="pm-card-head is-compact">
                <div>
                    <h2>Trainee evaluation sheet</h2>
                    <p>Each trainee is evaluated once per program run. All scores are 1–5 and the overall score is their average.</p>
                </div>
                <a class="pm-btn pm-btn-quiet" href="?page=instructor/training-subpage/program&id=<?= (int) $programId ?>"><i class="fas fa-user-plus" aria-hidden="true"></i> Manage trainees</a>
            </div>

            <?php if (!$participants): ?>
                <div class="pm-empty-state is-inline">
                    <i class="fas fa-user-plus" aria-hidden="true"></i>
                    <h3>No trainees assigned</h3>
                    <p>Edit the program and select the employees who will participate.</p>
                    <a class="pm-btn pm-btn-primary" href="?page=instructor/training-subpage/program&id=<?= (int) $programId ?>">Add trainees</a>
                </div>
            <?php else: ?>
                <div class="pm-eval-table-wrap">
                    <table class="pm-eval-table">
                        <thead>
                            <tr>
                                <th scope="col">Trainee</th>
                                <th scope="col">Status</th>
                                <th scope="col">Attendance</th>
                                <th scope="col">Overall</th>
                                <th scope="col">Result</th>
                                <th scope="col">Evaluated by</th>
                                <th scope="col"><span class="pm-sr-only">Action</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($participants as $participant): ?>
                                <?php
                                [$stateClass, $stateLabel] = pe_status_label($participant);
                                $overall = $participant['overall_score'] !== null ? number_format((float) $participant['overall_score'], 1) : '—';
                                $profileUrl = LearningProfileLink::url((int) $participant['employee_id'], 'learner');
                                $evaluatorUrl = LearningProfileLink::url((int) ($participant['trainer_id'] ?? 0), 'instructor');
                                $attendance = strtolower((string) ($participant['attendance'] ?? ''));
                                $attendanceLabel = $attendance !== '' ? ucfirst($attendance) : '—';
                                $assessmentPayload = [
                                    'program_id' => (int) $programId,
                                    'employee_id' => (int) $participant['employee_id'],
                                    'full_name' => (string) $participant['full_name'],
                                    'attendance' => $attendance !== '' ? $attendance : 'present',
                                    'knowledge_score' => $participant['knowledge_score'],
                                    'skills_score' => $participant['skills_score'],
                                    'participation_score' => $participant['participation_score'],
                                    'result' => strtolower((string) ($participant['result'] ?? 'pending')),
                                    'feedback' => (string) ($participant['feedback'] ?? ''),
                                    'recommendations' => (string) ($participant['recommendations'] ?? ''),
                                    'status' => strtolower((string) ($participant['assessment_status'] ?? 'completed')),
                                ];
                                ?>
                                <tr>
                                    <td data-label="Trainee">
                                        <div class="pm-person">
                                            <span class="pm-avatar" aria-hidden="true"><?= pe_h(pe_initials((string) $participant['full_name'])) ?></span>
                                            <span>
                                                <a class="pm-person-name" href="<?= pm_h($profileUrl) ?>"><?= pe_h($participant['full_name']) ?></a>
                                                <small><?= pe_h(trim(($participant['position_name'] ?? '') . (($participant['department_name'] ?? '') !== '' ? ' · ' . $participant['department_name'] : ''), ' ·')) ?></small>
                                            </span>
                                        </div>
                                    </td>
                                    <td data-label="Status"><span class="pm-eval-badge <?= pm_h($stateClass) ?>"><?= pm_h($stateLabel) ?></span></td>
                                    <td data-label="Attendance"><?= pm_h($attendanceLabel) ?></td>
                                    <td data-label="Overall"><span class="pm-score"><?= pm_h($overall) ?></span><?php if ($overall !== '—'): ?><span class="pm-score-max">/5</span><?php endif; ?></td>
                                    <td data-label="Result"><?= pm_h(ucfirst((string) ($participant['result'] ?? 'pending'))) ?></td>
                                    <td data-label="Evaluated by">
                                        <?php if (!empty($participant['trainer_name'])): ?>
                                            <?php if ($evaluatorUrl !== ''): ?>
                                                <a class="pm-person-name" href="<?= pm_h($evaluatorUrl) ?>"><?= pm_h($participant['trainer_name']) ?></a>
                                            <?php else: ?>
                                                <?= pm_h($participant['trainer_name']) ?>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="pm-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Action">
                                        <button type="button" class="pm-btn pm-btn-primary pm-eval-open" data-assessment="<?= pe_h(json_encode($assessmentPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>">
                                            <i class="fas fa-clipboard-check" aria-hidden="true"></i> <?= !empty($participant['assessment_id']) ? 'Edit' : 'Evaluate' ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>

<?php if ($programId > 0 && $program && $allowed && $participants): ?>
    <div id="pm-eval-modal" class="modal-overlay ld-modal-scrim" role="dialog" aria-modal="true" aria-labelledby="pm-eval-title" style="display:none;">
        <div class="ld-modal-panel pm-modal-panel pm-eval-modal-panel">
            <form id="pm-eval-form" data-skip>
                <div class="ld-modal-header">
                    <div>
                        <h3 id="pm-eval-title">Evaluate trainee</h3>
                        <p id="pm-eval-subtitle">Record attendance, scores and feedback for the selected trainee.</p>
                    </div>
                    <button type="button" class="cat-modal-close" id="pm-eval-close" aria-label="Close"><i class="fas fa-times" aria-hidden="true"></i></button>
                </div>
                <div class="ld-modal-body">
                    <input type="hidden" name="program_id" value="<?= (int) $programId ?>" />
                    <input type="hidden" name="employee_id" id="pm-eval-employee-id" value="" />
                    <input type="hidden" name="status" value="completed" />

                    <div class="pm-eval-modal-grid">
                        <label class="pm-field">
                            <span>Trainee</span>
                            <input class="pm-input" type="text" id="pm-eval-name" value="" readonly />
                        </label>
                        <label class="pm-field">
                            <span>Attendance</span>
                            <select class="pm-input" name="attendance" id="pm-eval-attendance">
                                <option value="present">Present</option>
                                <option value="late">Late</option>
                                <option value="absent">Absent</option>
                                <option value="excused">Excused</option>
                            </select>
                        </label>
                    </div>

                    <div class="pm-eval-criteria">
                        <label class="pm-field">
                            <span>Knowledge <small>1 = needs improvement, 5 = excellent</small></span>
                            <select class="pm-input" name="knowledge_score" id="pm-eval-knowledge">
                                <option value="">Not rated</option>
                                <option value="1">1 — Needs improvement</option>
                                <option value="2">2 — Developing</option>
                                <option value="3">3 — Meets expectations</option>
                                <option value="4">4 — Strong</option>
                                <option value="5">5 — Excellent</option>
                            </select>
                        </label>
                        <label class="pm-field">
                            <span>Skills application</span>
                            <select class="pm-input" name="skills_score" id="pm-eval-skills">
                                <option value="">Not rated</option>
                                <option value="1">1 — Needs improvement</option>
                                <option value="2">2 — Developing</option>
                                <option value="3">3 — Meets expectations</option>
                                <option value="4">4 — Strong</option>
                                <option value="5">5 — Excellent</option>
                            </select>
                        </label>
                        <label class="pm-field">
                            <span>Participation</span>
                            <select class="pm-input" name="participation_score" id="pm-eval-participation">
                                <option value="">Not rated</option>
                                <option value="1">1 — Needs improvement</option>
                                <option value="2">2 — Developing</option>
                                <option value="3">3 — Meets expectations</option>
                                <option value="4">4 — Strong</option>
                                <option value="5">5 — Excellent</option>
                            </select>
                        </label>
                        <label class="pm-field">
                            <span>Result</span>
                            <select class="pm-input" name="result" id="pm-eval-result">
                                <option value="pending">Pending / auto from average</option>
                                <option value="passed">Passed</option>
                                <option value="failed">Failed</option>
                            </select>
                        </label>
                    </div>

                    <label class="pm-field">
                        <span>Feedback</span>
                        <textarea class="pm-input" name="feedback" id="pm-eval-feedback" rows="4" placeholder="What did the trainee do well? What needs follow-up?"></textarea>
                    </label>
                    <label class="pm-field">
                        <span>Recommendations</span>
                        <textarea class="pm-input" name="recommendations" id="pm-eval-recommendations" rows="3" placeholder="Suggested next steps, coaching, or follow-up training."></textarea>
                    </label>
                </div>
                <div class="ld-modal-actions">
                    <button type="button" class="pm-btn pm-btn-quiet" id="pm-eval-cancel">Cancel</button>
                    <button type="submit" class="pm-btn pm-btn-primary" id="pm-eval-submit"><i class="fas fa-floppy-disk" aria-hidden="true"></i> Save Evaluation</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<script>
(function () {
    'use strict';

    var modal = document.getElementById('pm-eval-modal');
    var form = document.getElementById('pm-eval-form');
    var csrfToken = window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.content || '';

    if (!modal || !form) return;

    function showToast(message, type) {
        if (typeof window.showToast === 'function') window.showToast(message, type || 'info');
    }

    function openModal(data) {
        document.getElementById('pm-eval-employee-id').value = data.employee_id || '';
        document.getElementById('pm-eval-name').value = data.full_name || '';
        document.getElementById('pm-eval-attendance').value = data.attendance || 'present';
        document.getElementById('pm-eval-knowledge').value = data.knowledge_score || '';
        document.getElementById('pm-eval-skills').value = data.skills_score || '';
        document.getElementById('pm-eval-participation').value = data.participation_score || '';
        document.getElementById('pm-eval-result').value = data.result || 'pending';
        document.getElementById('pm-eval-feedback').value = data.feedback || '';
        document.getElementById('pm-eval-recommendations').value = data.recommendations || '';
        document.getElementById('pm-eval-title').textContent = 'Evaluate ' + (data.full_name || 'trainee');
        document.getElementById('pm-eval-subtitle').textContent = 'Record attendance, scores and feedback for this program run.';
        modal.style.display = 'flex';
    }

    function closeModal() {
        modal.style.display = 'none';
    }

    document.querySelectorAll('.pm-eval-open').forEach(function (button) {
        button.addEventListener('click', function () {
            try {
                openModal(JSON.parse(button.dataset.assessment || '{}'));
            } catch (error) {
                console.error('Could not read assessment data:', error);
                showToast('Could not open the evaluation form.', 'error');
            }
        });
    });

    document.getElementById('pm-eval-close')?.addEventListener('click', closeModal);
    document.getElementById('pm-eval-cancel')?.addEventListener('click', closeModal);
    modal.addEventListener('click', function (event) {
        if (event.target === modal) closeModal();
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        var submit = document.getElementById('pm-eval-submit');
        var original = submit ? submit.innerHTML : '';
        if (submit) {
            submit.disabled = true;
            submit.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Saving...';
        }

        fetch('pages/instructor/training-subpage/ajax/save-program-assessment.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken
            },
            body: new FormData(form)
        })
        .then(function (response) { return response.json().then(function (data) { return { ok: response.ok, data: data }; }); })
        .then(function (result) {
            if (!result.ok || !result.data.success) {
                throw new Error(result.data.message || result.data.error || 'Unable to save the evaluation.');
            }
            showToast(result.data.message || 'Evaluation saved.', 'success');
            setTimeout(function () { window.location.reload(); }, 700);
        })
        .catch(function (error) {
            showToast(error.message || 'Unable to save the evaluation.', 'error');
            console.error('Evaluation save failed:', error);
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
