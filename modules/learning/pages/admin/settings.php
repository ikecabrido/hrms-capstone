<?php
include_once __DIR__ . '/../../classes/Employee.php';
include_once __DIR__ . '/../../classes/Setting.php';
include_once __DIR__ . '/../../classes/coursemap.php';

require_once dirname(__DIR__, 4) . '/database/db.php';

$employeeClass = new Employee();
$settings = [];
$pdo = null;

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $settingClass = new Setting($pdo);
    $settings = $settingClass->getAll();
} catch (Throwable $e) {
    DbError::capture($e, 'admin/settings');
    $settings = [];
}

// getAll() already returns [key => value] pairs.
$sv = is_array($settings) ? $settings : [];

// Reads a setting, falling back to the same default the rest of the module
// uses when the row is missing or left blank.
$val = static function (string $key, string $default = '') use ($sv): string {
    $value = $sv[$key] ?? null;
    if ($value === null || $value === '') {
        $value = $default;
    }
    return htmlspecialchars((string) $value, ENT_QUOTES);
};

$rawTimezone = (string) ($sv['site_timezone'] ?? '') !== '' ? (string) $sv['site_timezone'] : 'Asia/Manila';
$rawPageSize = (string) ($sv['default_page_size'] ?? '') !== '' ? (string) $sv['default_page_size'] : '12';

// Quiz duration is stored in seconds but edited in minutes.
$quizSeconds = (float) ($sv['default_quiz_duration_seconds'] ?? $sv['default_quiz_duration'] ?? 0);
if ($quizSeconds <= 0) {
    $quizSeconds = 600;
}
$quizMinutes = $quizSeconds / 60;
$quizMinutesLabel = rtrim(rtrim(number_format($quizMinutes, 2, '.', ''), '0'), '.');
if ($quizMinutesLabel === '') {
    $quizMinutesLabel = '10';
}

$darkModeOn = (string) ($sv['dark_mode'] ?? '0') === '1';

$timezoneOptions = [
    'Asia/Manila' => 'Asia/Manila (GMT+8)',
    'UTC' => 'UTC',
    'America/New_York' => 'America/New_York (GMT-5)',
];
$pageSizeOptions = [12, 24, 36];

// Courses offered in the mapping builders.
$courseList = [];
if ($pdo instanceof PDO) {
    try {
        $courseStmt = $pdo->query("SELECT id, title FROM ld_course WHERE status = 'active' ORDER BY title ASC");
        $courseList = $courseStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $courseList = [];
    }
}
?>
<div class="module-content settings-page">
    <section class="home-hero">
        <div class="home-hero-main">
            <h1>Settings</h1>
            <p>Defaults and integrations for the Learning &amp; Development module.</p>
        </div>
    </section>

    <div class="tab-container">
        <div class="tab-list">
            <button type="button" class="tab-item active" data-tab="tab-general"><i class="fas fa-sliders"></i> General</button>
            <button type="button" class="tab-item" data-tab="tab-learning"><i class="fas fa-graduation-cap"></i> Learning Defaults</button>
            <button type="button" class="tab-item" data-tab="tab-integrations"><i class="fas fa-plug"></i> Integrations</button>
        </div>

        <!-- ================= General ================= -->
        <div class="tab-content active" id="tab-general" data-tab="tab-general">
            <form id="settings-general-form" data-skip="true">
                <section class="settings-group">
                    <div class="settings-group-head">
                        <h2><i class="fas fa-globe"></i> Locale &amp; display</h2>
                        <p>How the module reads dates and how long its lists are.</p>
                    </div>
                    <div class="settings-rows">
                        <div class="settings-row">
                            <div class="settings-row-label">
                                <label for="set-timezone">Site timezone</label>
                                <span class="settings-hint">Used for class schedules, deadlines and reports.</span>
                            </div>
                            <div class="settings-row-control">
                                <select id="set-timezone" name="site_timezone" class="settings-field">
                                    <?php foreach ($timezoneOptions as $tzValue => $tzLabel): ?>
                                        <option value="<?= htmlspecialchars($tzValue, ENT_QUOTES) ?>" <?= $rawTimezone === $tzValue ? 'selected' : '' ?>><?= htmlspecialchars($tzLabel) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="settings-row-label">
                                <label for="set-page-size">Default list length</label>
                                <span class="settings-hint">Items shown per page in the module's lists and catalogs.</span>
                            </div>
                            <div class="settings-row-control">
                                <select id="set-page-size" name="default_page_size" class="settings-field">
                                    <?php foreach ($pageSizeOptions as $size): ?>
                                        <option value="<?= $size ?>" <?= (string) $size === $rawPageSize ? 'selected' : '' ?>><?= $size ?> items</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="settings-row-label">
                                <label for="admin-dark-mode-toggle">Dark mode</label>
                                <span class="settings-hint">Applies to your account on this device.</span>
                            </div>
                            <div class="settings-row-control">
                                <input type="hidden" name="dark_mode" id="set-dark-mode" value="<?= $darkModeOn ? '1' : '0' ?>">
                                <label class="settings-switch" for="admin-dark-mode-toggle">
                                    <input type="checkbox" id="admin-dark-mode-toggle" <?= $darkModeOn ? 'checked' : '' ?>>
                                    <span class="settings-switch-track"></span>
                                </label>
                                <span id="admin-dark-mode-label" class="settings-suffix"></span>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="settings-actions">
                    <button type="submit" class="home-cta settings-save"><i class="fas fa-floppy-disk"></i> Save changes</button>
                </div>
            </form>
        </div>

        <!-- ================= Learning defaults ================= -->
        <div class="tab-content" id="tab-learning" data-tab="tab-learning">
            <form id="settings-learning-form" data-skip="true">
                <section class="settings-group">
                    <div class="settings-group-head">
                        <h2><i class="fas fa-circle-question"></i> Quizzes &amp; evaluations</h2>
                        <p>Starting values for new assessments. Each quiz can still override them.</p>
                    </div>
                    <div class="settings-rows">
                        <div class="settings-row">
                            <div class="settings-row-label">
                                <label for="set-quiz-minutes">Default time limit</label>
                                <span class="settings-hint">Countdown given to learners when they start an assessment.</span>
                            </div>
                            <div class="settings-row-control">
                                <input type="number" id="set-quiz-minutes" name="default_quiz_duration_minutes" class="settings-field" value="<?= htmlspecialchars($quizMinutesLabel, ENT_QUOTES) ?>" min="1" step="0.5" required>
                                <span class="settings-suffix">minutes</span>
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="settings-row-label">
                                <label for="set-max-attempts">Default attempts allowed</label>
                                <span class="settings-hint">How many times a learner may retake an assessment.</span>
                            </div>
                            <div class="settings-row-control">
                                <input type="number" id="set-max-attempts" name="default_max_quiz_attempts" class="settings-field" value="<?= $val('default_max_quiz_attempts', '2') ?>" min="1" required>
                                <span class="settings-suffix">attempts</span>
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="settings-row-label">
                                <label for="set-passing-score">Default passing score</label>
                                <span class="settings-hint">Score a learner must reach for an assessment to count as passed.</span>
                            </div>
                            <div class="settings-row-control">
                                <input type="number" id="set-passing-score" name="default_passing_score" class="settings-field" value="<?= $val('default_passing_score', '70') ?>" min="0" max="100" required>
                                <span class="settings-suffix">%</span>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="settings-group">
                    <div class="settings-group-head">
                        <h2><i class="fas fa-certificate"></i> Certificates</h2>
                        <p>How long a certificate stays valid after it is issued.</p>
                    </div>
                    <div class="settings-rows">
                        <div class="settings-row">
                            <div class="settings-row-label">
                                <label for="set-certificate-days">Certificate validity</label>
                                <span class="settings-hint">Use 0 to issue certificates that never expire.</span>
                            </div>
                            <div class="settings-row-control">
                                <input type="number" id="set-certificate-days" name="certificate_default_validity_days" class="settings-field" value="<?= $val('certificate_default_validity_days', '0') ?>" min="0" required>
                                <span class="settings-suffix">days</span>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="settings-group">
                    <div class="settings-group-head">
                        <h2><i class="fas fa-cloud-arrow-up"></i> Content &amp; uploads</h2>
                        <p>Limits applied to lesson materials and attachments.</p>
                    </div>
                    <div class="settings-rows">
                        <div class="settings-row">
                            <div class="settings-row-label">
                                <label for="set-upload-mb">Maximum upload size</label>
                                <span class="settings-hint">Largest file a learner or instructor may attach.</span>
                            </div>
                            <div class="settings-row-control">
                                <input type="number" id="set-upload-mb" name="file_upload_max_mb" class="settings-field" value="<?= $val('file_upload_max_mb', '25') ?>" min="1" required>
                                <span class="settings-suffix">MB</span>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="settings-group">
                    <div class="settings-group-head">
                        <h2><i class="fas fa-shield-halved"></i> Moderation</h2>
                        <p>Housekeeping for learner reports.</p>
                    </div>
                    <div class="settings-rows">
                        <div class="settings-row">
                            <div class="settings-row-label">
                                <label for="set-archive-days">Auto-archive reviewed reports</label>
                                <span class="settings-hint">Reports older than this are filed away automatically.</span>
                            </div>
                            <div class="settings-row-control">
                                <input type="number" id="set-archive-days" name="report_auto_archive_days" class="settings-field" value="<?= $val('report_auto_archive_days', '7') ?>" min="1" required>
                                <span class="settings-suffix">days</span>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="settings-actions">
                    <button type="submit" class="home-cta settings-save"><i class="fas fa-floppy-disk"></i> Save changes</button>
                </div>
            </form>
        </div>

        <!-- ================= Integrations ================= -->
        <div class="tab-content" id="tab-integrations" data-tab="tab-integrations">
            <form id="settings-integrations-form" data-skip="true">
                <section class="settings-group">
                    <div class="settings-group-head">
                        <h2><i class="fas fa-envelope-open-text"></i> Enrollments</h2>
                        <p>How long a learner has to accept an enrollment invitation.</p>
                    </div>
                    <div class="settings-rows">
                        <div class="settings-row">
                            <div class="settings-row-label">
                                <label for="set-invitation-days">Invitation expiry</label>
                                <span class="settings-hint">After this the invitation has to be sent again.</span>
                            </div>
                            <div class="settings-row-control">
                                <input type="number" id="set-invitation-days" name="enrollment_invitation_expiry_days" class="settings-field" value="<?= $val('enrollment_invitation_expiry_days', '7') ?>" min="1" required>
                                <span class="settings-suffix">days</span>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="settings-group">
                    <div class="settings-group-head">
                        <h2><i class="fas fa-video"></i> Video conference reminders</h2>
                        <p>Two heads-up notifications sent before a scheduled session starts.</p>
                    </div>
                    <div class="settings-rows">
                        <div class="settings-row">
                            <div class="settings-row-label">
                                <label for="set-reminder-first">First reminder</label>
                                <span class="settings-hint">Sent this long before the session begins.</span>
                            </div>
                            <div class="settings-row-control">
                                <input type="number" id="set-reminder-first" name="video_conference_reminder_first_minutes" class="settings-field" value="<?= $val('video_conference_reminder_first_minutes', '30') ?>" min="5" required>
                                <span class="settings-suffix">min before</span>
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="settings-row-label">
                                <label for="set-reminder-second">Second reminder</label>
                                <span class="settings-hint">Final nudge sent as the session gets close.</span>
                            </div>
                            <div class="settings-row-control">
                                <input type="number" id="set-reminder-second" name="video_conference_reminder_second_minutes" class="settings-field" value="<?= $val('video_conference_reminder_second_minutes', '15') ?>" min="5" required>
                                <span class="settings-suffix">min before</span>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="settings-actions">
                    <button type="submit" class="home-cta settings-save"><i class="fas fa-floppy-disk"></i> Save changes</button>
                </div>
            </form>

            <section class="settings-group">
                <div class="settings-group-head">
                    <h2><i class="fas fa-diagram-project"></i> Recommendation course mappings</h2>
                    <p>Links a <strong>development area</strong> from Performance Management to a course. Approved appraisal recommendations are auto-assigned from here.</p>
                </div>
                <div class="settings-mapping-add">
                    <label>
                        <span>Development area</span>
                        <input type="text" id="rec-dev-area" class="settings-field" placeholder="e.g. Leadership">
                    </label>
                    <label>
                        <span>Course</span>
                        <select id="rec-course-id" class="settings-field">
                            <option value="0">&mdash; Select course &mdash;</option>
                            <?php foreach ($courseList as $cl): ?>
                                <option value="<?= (int) $cl['id'] ?>"><?= htmlspecialchars($cl['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button type="button" id="rec-add-btn" class="settings-add-btn"><i class="fas fa-plus"></i> Add mapping</button>
                </div>
                <div id="rec-mapping-table" class="settings-table-wrap"></div>
            </section>

            <section class="settings-group">
                <div class="settings-group-head">
                    <h2><i class="fas fa-award"></i> Recognition course mappings</h2>
                    <p>Links a <strong>recognition category</strong> from Employee Recognition to a course that is auto-assigned when a recognition is approved.</p>
                </div>
                <div class="settings-mapping-add">
                    <label>
                        <span>Recognition category</span>
                        <input type="text" id="recog-category" class="settings-field" placeholder="e.g. performance">
                    </label>
                    <label>
                        <span>Course</span>
                        <select id="recog-course-id" class="settings-field">
                            <option value="0">&mdash; Select course &mdash;</option>
                            <?php foreach ($courseList as $cl): ?>
                                <option value="<?= (int) $cl['id'] ?>"><?= htmlspecialchars($cl['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button type="button" id="recog-add-btn" class="settings-add-btn"><i class="fas fa-plus"></i> Add mapping</button>
                </div>
                <div id="recog-mapping-table" class="settings-table-wrap"></div>
            </section>
        </div>
    </div>
</div>
<script>
(function() {
    'use strict';

    function handleSettingsForm(formId, endpoint, prepare) {
        var form = document.getElementById(formId);
        if (!form) return;
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var fd = new FormData(form);
            if (typeof prepare === 'function') prepare(fd);
            fetch(endpoint, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    if (window.showToast) window.showToast('Settings saved', 'success');
                } else {
                    if (window.showToast) window.showToast(data.error || 'Failed to save settings', 'error');
                }
            })
            .catch(function() { if (window.showToast) window.showToast('Network error', 'error'); });
        });
    }

    // The time limit is edited in minutes but the module stores seconds.
    function toSeconds(fd) {
        var minutes = parseFloat(fd.get('default_quiz_duration_minutes'));
        fd.delete('default_quiz_duration_minutes');
        if (!isNaN(minutes) && minutes > 0) {
            fd.set('default_quiz_duration_seconds', String(Math.round(minutes * 60)));
        }
    }

    var darkToggle = document.getElementById('admin-dark-mode-toggle');
    var darkLabel = document.getElementById('admin-dark-mode-label');
    var darkValue = document.getElementById('set-dark-mode');

    function applyDarkMode(on) {
        if (on) {
            document.documentElement.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
        } else {
            document.documentElement.removeAttribute('data-theme');
            localStorage.setItem('theme', 'light');
        }
        if (darkValue) darkValue.value = on ? '1' : '0';
        if (darkLabel) darkLabel.textContent = on ? 'On' : 'Off';
    }

    if (darkToggle) {
        darkToggle.checked = localStorage.getItem('theme') === 'dark';
        applyDarkMode(darkToggle.checked);
        darkToggle.addEventListener('change', function() { applyDarkMode(darkToggle.checked); });
    }

    handleSettingsForm('settings-general-form', 'pages/admin/ajax/edit-settings.php');
    handleSettingsForm('settings-learning-form', 'pages/admin/ajax/edit-settings.php', toSeconds);
    handleSettingsForm('settings-integrations-form', 'pages/admin/ajax/edit-settings.php');
})();
</script>
<script src="js/admin/course-mappings.js?v=<?= (int) filemtime(__DIR__ . '/../../js/admin/course-mappings.js') ?>"></script>
