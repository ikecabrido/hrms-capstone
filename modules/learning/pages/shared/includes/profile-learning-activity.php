<?php
/**
 * Learning activity sections for an admin viewing another member's profile.
 *
 * Expects the shared profile bootstrap plus:
 *   $enrollments, $quizAttempts, $certificates, $ktPlans, $ktSuccessors
 *
 * The view is read-only; the one action it offers is creating a Knowledge
 * Transfer plan for the member being viewed.
 */
$profileId = (int) ($profileId ?? 0);
$employeeId = (int) ($employeeId ?? $profileId);
$profileReadOnly = !empty($profileReadOnly);

$enrollments = $enrollments ?? [];
$quizAttempts = $quizAttempts ?? [];
$certificates = $certificates ?? [];
$ktPlans = $ktPlans ?? [];
$ktSuccessors = $ktSuccessors ?? [];

$formatDate = static function ($value): string {
    if (empty($value)) {
        return '—';
    }
    $ts = strtotime((string) $value);
    return $ts ? date('M j, Y', $ts) : '—';
};

$timeAgo = static function ($value): string {
    if (empty($value)) {
        return 'N/A';
    }
    $ts = strtotime((string) $value);
    if (!$ts) {
        return 'N/A';
    }
    $diff = time() - $ts;
    if ($diff < 60) {
        return 'just now';
    }
    if ($diff < 3600) {
        return floor($diff / 60) . 'm ago';
    }
    if ($diff < 86400) {
        return floor($diff / 3600) . 'h ago';
    }
    if ($diff < 604800) {
        return floor($diff / 86400) . 'd ago';
    }
    return date('M j, Y', $ts);
};

$scoreLabel = static function ($value): string {
    if ($value === null || $value === '') {
        return '—';
    }
    $formatted = rtrim(rtrim(number_format((float) $value, 1, '.', ''), '0'), '.');
    return ($formatted === '' ? '0' : $formatted) . '%';
};

$enrollmentStatusClass = static function (string $status): string {
    if ($status === 'completed') {
        return 'is-green';
    }
    if ($status === 'in_progress') {
        return 'is-blue';
    }
    if ($status === 'cancelled' || $status === 'dropped') {
        return 'is-red';
    }
    return 'is-amber';
};

$ktStatusClass = static function (string $status): string {
    if ($status === 'active') {
        return 'is-green';
    }
    if ($status === 'completed') {
        return 'is-blue';
    }
    return 'is-red';
};

$gradebookLink = $gradebookLink ?? null;
include __DIR__ . '/profile-gradebook.php';
?>
<div class="mode-card" style="margin-bottom:1.5rem;">
    <div class="pfl-card-head">
        <h3 class="pfl-section-title" style="margin:0;">
            <i class="fas fa-book-open" aria-hidden="true"></i> Course Enrollments
        </h3>
        <span class="pfl-count-badge"><?= count($enrollments) ?></span>
    </div>
    <?php if (empty($enrollments)): ?>
        <div class="pfl-empty-state">
            <i class="fas fa-book-open" aria-hidden="true"></i>
            <p>No course enrollments recorded for this member yet.</p>
        </div>
    <?php else: ?>
        <div class="pfl-list">
            <?php foreach ($enrollments as $enrollment):
                $status = strtolower((string) ($enrollment['status'] ?? ''));
                $totalItems = (int) ($enrollment['total_items'] ?? 0);
                $completedItems = (int) ($enrollment['completed_items'] ?? 0);
                $progress = $totalItems > 0 ? (int) round(($completedItems / $totalItems) * 100) : 0;
                $instructorName = trim((string) ($enrollment['inst_first'] ?? '') . ' ' . (string) ($enrollment['inst_last'] ?? ''));
                if ($instructorName === '') {
                    $instructorName = 'Unassigned instructor';
                }
            ?>
                <div class="pfl-list-item">
                    <div class="pfl-list-head">
                        <div>
                            <div class="pfl-list-title"><?= htmlspecialchars((string) ($enrollment['course_title'] ?? 'Untitled course'), ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="pfl-list-sub">by <?php if ((int) ($enrollment['instructor_id'] ?? 0) > 0): ?><a href="<?= htmlspecialchars(LearningProfileLink::url((int) $enrollment['instructor_id'], LearningRole::forEmployee($pdo, (int) $enrollment['instructor_id'])), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($instructorName, ENT_QUOTES, 'UTF-8') ?></a><?php else: ?><?= htmlspecialchars($instructorName, ENT_QUOTES, 'UTF-8') ?><?php endif; ?></div>
                        </div>
                        <span class="pfl-badge <?= $enrollmentStatusClass($status) ?>"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $status !== '' ? $status : 'unknown')), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="pfl-progress-head">
                        <span>Progress</span>
                        <strong><?= $progress ?>%</strong>
                    </div>
                    <div class="pfl-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $progress ?>" aria-label="Course progress">
                        <span style="width:<?= $progress ?>%;"></span>
                    </div>
                    <div class="pfl-meta">
                        <?php if (($enrollment['final_score'] ?? null) !== null): ?>
                            <span><i class="fas fa-star" aria-hidden="true"></i>Score: <strong><?= htmlspecialchars($scoreLabel($enrollment['final_score']), ENT_QUOTES, 'UTF-8') ?></strong></span>
                        <?php endif; ?>
                        <span><i class="fas fa-list-check" aria-hidden="true"></i>Items: <?= $completedItems ?>/<?= $totalItems ?></span>
                        <span><i class="fas fa-calendar" aria-hidden="true"></i>Enrolled <?= htmlspecialchars($timeAgo($enrollment['enrolled_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="mode-card" style="margin-bottom:1.5rem;">
    <div class="pfl-card-head">
        <h3 class="pfl-section-title" style="margin:0;">
            <i class="fas fa-circle-question" aria-hidden="true"></i> Quiz Attempts
        </h3>
        <span class="pfl-count-badge"><?= count($quizAttempts) ?></span>
    </div>
    <?php if (empty($quizAttempts)): ?>
        <div class="pfl-empty-state">
            <i class="fas fa-circle-question" aria-hidden="true"></i>
            <p>No quiz attempts recorded for this member yet.</p>
        </div>
    <?php else: ?>
        <div class="pfl-table-wrap">
            <table class="pfl-table">
                <thead>
                    <tr>
                        <th>Quiz</th>
                        <th>Score</th>
                        <th>Items</th>
                        <th>Result</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($quizAttempts as $attempt):
                        $passed = !empty($attempt['passed']);
                    ?>
                        <tr>
                            <td>
                                <div class="pfl-list-title"><?= htmlspecialchars((string) ($attempt['quiz_title'] ?? 'Untitled quiz'), ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="pfl-table-sub"><?= htmlspecialchars((string) ($attempt['course_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                            </td>
                            <td><strong><?= htmlspecialchars($scoreLabel($attempt['score'] ?? null), ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td><?= (int) ($attempt['total_items'] ?? 0) ?></td>
                            <td><span class="pfl-badge <?= $passed ? 'is-green' : 'is-red' ?>"><?= $passed ? 'Passed' : 'Failed' ?></span></td>
                            <td><?= htmlspecialchars($timeAgo($attempt['attempted_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="mode-card" style="margin-bottom:1.5rem;">
    <div class="pfl-card-head">
        <h3 class="pfl-section-title" style="margin:0;">
            <i class="fas fa-award" aria-hidden="true"></i> Certificates
        </h3>
        <span class="pfl-count-badge"><?= count($certificates) ?></span>
    </div>
    <?php if (empty($certificates)): ?>
        <div class="pfl-empty-state">
            <i class="fas fa-award" aria-hidden="true"></i>
            <p>No certificates earned by this member yet.</p>
        </div>
    <?php else: ?>
        <div class="pfl-cert-grid">
            <?php foreach ($certificates as $certificate): ?>
                <div class="pfl-cert">
                    <div class="pfl-cert-icon"><i class="fas fa-award" aria-hidden="true"></i></div>
                    <div class="pfl-cert-title"><?= htmlspecialchars((string) ($certificate['course_title'] ?? 'Course certificate'), ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="pfl-cert-meta">Issued <?= htmlspecialchars($formatDate($certificate['issued_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></div>
                    <?php if (!empty($certificate['verification_code'])): ?>
                        <div class="pfl-cert-code"><?= htmlspecialchars((string) $certificate['verification_code'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="mode-card" style="margin-bottom:1.5rem;">
    <div class="pfl-card-head">
        <h3 class="pfl-section-title" style="margin:0;">
            <i class="fas fa-right-left" aria-hidden="true"></i> Knowledge Transfer
        </h3>
        <button type="button" id="kt-create-btn" class="pfl-btn pfl-btn--primary">
            <i class="fas fa-plus" aria-hidden="true"></i> Create KT Plan
        </button>
    </div>
    <?php if (empty($ktPlans)): ?>
        <div class="pfl-empty-state">
            <i class="fas fa-right-left" aria-hidden="true"></i>
            <p>No knowledge transfer plans are recorded for this member yet.</p>
        </div>
    <?php else: ?>
        <div class="pfl-list">
            <?php foreach ($ktPlans as $plan):
                $planStatus = strtolower((string) ($plan['status'] ?? 'active'));
                $planEmployeeName = (string) ($plan['employee_name'] ?? $profileData['full_name'] ?? 'N/A');
                $successorName = trim((string) ($plan['successor_name'] ?? ''));
                $planEmployeeId = (int) ($plan['employee_id'] ?? 0);
                $successorId = (int) ($plan['successor_id'] ?? 0);
                if ($successorName === '') {
                    $successorName = 'Not assigned';
                }
                $isSuccessor = (int) ($plan['successor_id'] ?? 0) === $employeeId;
                $relationship = $isSuccessor
                    ? 'Receives transfer from ' . ($planEmployeeId > 0
                        ? '<a href="' . htmlspecialchars(LearningProfileLink::url($planEmployeeId, LearningRole::forEmployee($pdo, $planEmployeeId)), ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($planEmployeeName, ENT_QUOTES, 'UTF-8') . '</a>'
                        : htmlspecialchars($planEmployeeName, ENT_QUOTES, 'UTF-8'))
                    : 'Successor: ' . ($successorId > 0
                        ? '<a href="' . htmlspecialchars(LearningProfileLink::url($successorId, LearningRole::forEmployee($pdo, $successorId)), ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($successorName, ENT_QUOTES, 'UTF-8') . '</a>'
                        : htmlspecialchars($successorName, ENT_QUOTES, 'UTF-8'));
                $startLabel = $formatDate($plan['start_date'] ?? null);
                $endLabel = $formatDate($plan['end_date'] ?? null);
                $dateLabel = ($startLabel !== '—' || $endLabel !== '—') ? $startLabel . ' – ' . $endLabel : 'Dates not set';
                $itemsDone = (int) ($plan['items_done'] ?? 0);
                $itemsTotal = (int) ($plan['item_count'] ?? 0);
                $learningPathId = (int) ($plan['learning_path_id'] ?? 0);
            ?>
                <div class="pfl-kt-row">
                    <div class="pfl-kt-icon"><i class="fas fa-right-left" aria-hidden="true"></i></div>
                    <div class="pfl-kt-main">
                        <div class="pfl-kt-title"><?php if ($planEmployeeId > 0): ?><a href="<?= htmlspecialchars(LearningProfileLink::url($planEmployeeId, LearningRole::forEmployee($pdo, $planEmployeeId)), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($planEmployeeName, ENT_QUOTES, 'UTF-8') ?></a><?php else: ?><?= htmlspecialchars($planEmployeeName, ENT_QUOTES, 'UTF-8') ?><?php endif; ?></div>
                        <div class="pfl-kt-meta">
                            <?= $relationship ?>
                            &bull; <?= htmlspecialchars($dateLabel, ENT_QUOTES, 'UTF-8') ?>
                            &bull; <?= $itemsDone ?>/<?= $itemsTotal ?> items done
                        </div>
                    </div>
                    <span class="pfl-badge <?= $ktStatusClass($planStatus) ?>"><?= htmlspecialchars(ucfirst($planStatus), ENT_QUOTES, 'UTF-8') ?></span>
                    <div class="pfl-kt-actions">
                        <?php if ($learningPathId > 0): ?>
                            <a class="pfl-link-btn" href="?page=instructor/training&amp;view_learning_path=<?= $learningPathId ?>">
                                <i class="fas fa-eye" aria-hidden="true"></i> View
                            </a>
                            <a class="pfl-link-btn pfl-link-btn--amber" href="?page=instructor/learning-path&amp;id=<?= $learningPathId ?>">
                                <i class="fas fa-pen" aria-hidden="true"></i> Edit
                            </a>
                        <?php else: ?>
                            <span class="pfl-table-sub">Plan #<?= (int) ($plan['id'] ?? 0) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div id="kt-modal-overlay" class="pfl-modal-overlay" hidden>
    <div class="pfl-modal" role="dialog" aria-modal="true" aria-labelledby="kt-modal-title">
        <div class="pfl-modal-head">
            <h3 id="kt-modal-title">Create Knowledge Transfer Plan</h3>
            <button type="button" id="kt-modal-close" class="pfl-modal-close" aria-label="Close">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>
        <label class="pfl-field" for="kt-employee-name">
            <span>Employee</span>
            <input type="text" id="kt-employee-name" class="pfl-input" value="<?= htmlspecialchars((string) ($profileData['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" readonly>
        </label>
        <label class="pfl-field" for="kt-successor">
            <span>Successor (optional; can be assigned later)</span>
            <select id="kt-successor" class="pfl-input">
                <option value="">Select successor...</option>
                <?php foreach ($ktSuccessors as $candidate): ?>
                    <option value="<?= (int) ($candidate['employee_id'] ?? 0) ?>"><?= htmlspecialchars((string) ($candidate['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="pfl-modal-grid">
            <label class="pfl-field" for="kt-start-date">
                <span>Start Date</span>
                <input type="date" id="kt-start-date" class="pfl-input">
            </label>
            <label class="pfl-field" for="kt-end-date">
                <span>End Date</span>
                <input type="date" id="kt-end-date" class="pfl-input">
            </label>
        </div>
        <label class="pfl-field" for="kt-status">
            <span>Status</span>
            <select id="kt-status" class="pfl-input">
                <option value="active">Active</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </label>
        <div class="pfl-modal-actions">
            <button type="button" id="kt-modal-cancel" class="pfl-btn pfl-btn--ghost">Cancel</button>
            <button type="button" id="kt-modal-save" class="pfl-btn pfl-btn--primary">Save Plan</button>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    var employeeId = <?= (int) $profileId ?>;
    var employeeName = <?= json_encode((string) ($profileData['full_name'] ?? ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var csrfToken = window.CSRF_TOKEN || (document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '');

    var overlay = document.getElementById('kt-modal-overlay');
    var createBtn = document.getElementById('kt-create-btn');
    var closeBtn = document.getElementById('kt-modal-close');
    var cancelBtn = document.getElementById('kt-modal-cancel');
    var saveBtn = document.getElementById('kt-modal-save');
    var successorSelect = document.getElementById('kt-successor');
    var startDateInput = document.getElementById('kt-start-date');
    var endDateInput = document.getElementById('kt-end-date');
    var statusInput = document.getElementById('kt-status');
    var employeeInput = document.getElementById('kt-employee-name');

    if (!overlay || !createBtn || !saveBtn) {
        return;
    }

    function notify(message, type) {
        if (typeof window.showToast === 'function') {
            window.showToast(message, type || 'success');
        } else if (type === 'error') {
            window.alert(message);
        }
    }

    function openModal() {
        employeeInput.value = employeeName;
        successorSelect.value = '';
        startDateInput.value = '';
        endDateInput.value = '';
        statusInput.value = 'active';
        saveBtn.disabled = false;
        saveBtn.textContent = 'Save Plan';
        overlay.hidden = false;
        startDateInput.focus();
    }

    function closeModal() {
        overlay.hidden = true;
    }

    createBtn.addEventListener('click', openModal);
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', function(event) {
        if (event.target === overlay) {
            closeModal();
        }
    });
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && !overlay.hidden) {
            closeModal();
        }
    });

    saveBtn.addEventListener('click', function() {
        var startDate = startDateInput.value;
        var endDate = endDateInput.value;
        if (startDate && endDate && endDate < startDate) {
            notify('End date must be on or after the start date.', 'error');
            return;
        }

        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving...';

        fetch('pages/admin/ajax/save-kt-plan.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({
                employee_id: employeeId,
                successor_id: successorSelect.value ? parseInt(successorSelect.value, 10) : null,
                start_date: startDate,
                end_date: endDate,
                status: statusInput.value
            })
        })
        .then(function(response) {
            return response.json().then(function(data) {
                if (!response.ok || !data.success) {
                    throw new Error(data.error || 'Could not save the knowledge transfer plan.');
                }
                return data;
            });
        })
        .then(function() {
            notify('Knowledge transfer plan created.', 'success');
            window.setTimeout(function() { window.location.reload(); }, 250);
        })
        .catch(function(error) {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save Plan';
            notify(error.message || 'Could not save the knowledge transfer plan.', 'error');
        });
    });
})();
</script>
