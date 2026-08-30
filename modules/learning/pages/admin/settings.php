<?php
include_once __DIR__ . '/../../classes/Employee.php';
include_once __DIR__ . '/../../classes/Setting.php';
include_once __DIR__ . '/../../classes/coursemap.php';

require_once dirname(__DIR__, 4) . '/database/db.php';

$employeeClass = new Employee();
$settings = [];

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $settingClass = new Setting($pdo);
    $settings = $settingClass->getAll();
} catch (Throwable $e) {
    $settings = [];
}

// getAll() already returns [key => value] pairs
$sv = is_array($settings) ? $settings : [];
?>
<div class="module-content">
    <div class="toolbar">
        <div class="toolbar-search">
            <input type="search" placeholder="Search settings..." aria-label="Search settings" />
        </div>
        <div class="toolbar-actions">
            <button type="button" class="toolbar-mode-toggle" data-view="grid" aria-label="Toggle view">Grid</button>
        </div>
    </div>

    <div class="tab-container">
        <div class="tab-list">
            <button type="button" class="tab-item active" data-tab="tab-general">General</button>
            <button type="button" class="tab-item" data-tab="tab-quiz">Quiz Defaults</button>
            <button type="button" class="tab-item" data-tab="tab-moderation">Moderation</button>
            <button type="button" class="tab-item" data-tab="tab-upload">Upload Limits</button>
            <button type="button" class="tab-item" data-tab="tab-certificate">Certificates</button>
            <button type="button" class="tab-item" data-tab="tab-integration">Integration</button>
            <button type="button" class="tab-item" data-tab="tab-course-mappings">Course Mappings</button>
        </div>

        <!-- General Settings -->
        <div class="tab-content active" data-tab="tab-general">
            <div class="mode-card">
                <h2>General Settings</h2>
                <p>Site-wide configuration for the Learning & Development module.</p>
                <form id="settings-general-form" data-skip="true" style="margin-top:1rem;">
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:1.25rem;">
                        <label>
                            <span style="display:block; font-weight:600; color:var(--primary); margin-bottom:0.3rem;">Site Timezone</span>
                            <select name="site_timezone" style="width:100%; padding:0.75rem; border:1px solid var(--border); border-radius:8px;">
                                <option value="Asia/Manila" <?= ($sv['site_timezone'] ?? '') === 'Asia/Manila' ? 'selected' : '' ?>>Asia/Manila (GMT+8)</option>
                                <option value="UTC" <?= ($sv['site_timezone'] ?? '') === 'UTC' ? 'selected' : '' ?>>UTC</option>
                                <option value="America/New_York" <?= ($sv['site_timezone'] ?? '') === 'America/New_York' ? 'selected' : '' ?>>America/New_York</option>
                            </select>
                        </label>
                        <label>
                            <span style="display:block; font-weight:600; color:var(--primary); margin-bottom:0.3rem;">Default Page Size</span>
                            <select name="default_page_size" style="width:100%; padding:0.75rem; border:1px solid var(--border); border-radius:8px;">
                                <option value="12" <?= ($sv['default_page_size'] ?? '12') == '12' ? 'selected' : '' ?>>12</option>
                                <option value="24" <?= ($sv['default_page_size'] ?? '') == '24' ? 'selected' : '' ?>>24</option>
                                <option value="36" <?= ($sv['default_page_size'] ?? '') == '36' ? 'selected' : '' ?>>36</option>
                            </select>
                        </label>
                    </div>
                    <div style="margin-top:1.5rem;">
                        <label style="display:flex; align-items:center; gap:0.75rem; cursor:pointer;">
                            <span style="display:block; font-weight:600; color:var(--primary); margin-bottom:0;">Dark Mode</span>
                            <input type="checkbox" id="admin-dark-mode-toggle" name="dark_mode" <?= ($sv['dark_mode'] ?? '') === '1' ? 'checked' : '' ?> style="width:18px; height:18px; accent-color:var(--primary); cursor:pointer;">
                            <span id="admin-dark-mode-label" style="font-size:0.85rem; color:var(--muted);"></span>
                        </label>
                    </div>
                    <div style="margin-top:1.5rem;">
                        <button type="submit" class="mode-button">Save General Settings</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Quiz Defaults -->
        <div class="tab-content" data-tab="tab-quiz">
            <div class="mode-card">
                <h2>Quiz & Evaluation Defaults</h2>
                <p>Default values used when creating new quizzes and evaluations.</p>
                <form id="settings-quiz-form" data-skip="true" style="margin-top:1rem;">
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:1.25rem;">
                        <label>
                            <span style="display:block; font-weight:600; color:var(--primary); margin-bottom:0.3rem;">Default Quiz Duration (seconds)</span>
                            <input type="number" name="default_quiz_duration" value="<?= htmlspecialchars($sv['default_quiz_duration'] ?? '600') ?>" min="60" style="width:100%; padding:0.75rem; border:1px solid var(--border); border-radius:8px;" />
                        </label>
                        <label>
                            <span style="display:block; font-weight:600; color:var(--primary); margin-bottom:0.3rem;">Default Max Attempts</span>
                            <input type="number" name="default_max_attempts" value="<?= htmlspecialchars($sv['default_max_attempts'] ?? '2') ?>" min="1" style="width:100%; padding:0.75rem; border:1px solid var(--border); border-radius:8px;" />
                        </label>
                        <label>
                            <span style="display:block; font-weight:600; color:var(--primary); margin-bottom:0.3rem;">Default Passing Score (%)</span>
                            <input type="number" name="default_passing_score" value="<?= htmlspecialchars($sv['default_passing_score'] ?? '70') ?>" min="0" max="100" style="width:100%; padding:0.75rem; border:1px solid var(--border); border-radius:8px;" />
                        </label>
                    </div>
                    <div style="margin-top:1.5rem;">
                        <button type="submit" class="mode-button">Save Quiz Defaults</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Moderation -->
        <div class="tab-content" data-tab="tab-moderation">
            <div class="mode-card">
                <h2>Moderation Settings</h2>
                <p>Configure content moderation and report handling.</p>
                <form id="settings-moderation-form" data-skip="true" style="margin-top:1rem;">
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:1.25rem;">
                        <label>
                            <span style="display:block; font-weight:600; color:var(--primary); margin-bottom:0.3rem;">Auto-Archive Reports After (days)</span>
                            <input type="number" name="report_auto_archive_days" value="<?= htmlspecialchars($sv['report_auto_archive_days'] ?? '7') ?>" min="1" style="width:100%; padding:0.75rem; border:1px solid var(--border); border-radius:8px;" />
                        </label>
                    </div>
                    <div style="margin-top:1.5rem;">
                        <button type="submit" class="mode-button">Save Moderation Settings</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Upload Limits -->
        <div class="tab-content" data-tab="tab-upload">
            <div class="mode-card">
                <h2>File Upload Limits</h2>
                <p>Configure maximum file sizes for lesson materials and attachments.</p>
                <form id="settings-upload-form" data-skip="true" style="margin-top:1rem;">
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:1.25rem;">
                        <label>
                            <span style="display:block; font-weight:600; color:var(--primary); margin-bottom:0.3rem;">Max Upload Size (MB)</span>
                            <input type="number" name="max_upload_size_mb" value="<?= htmlspecialchars($sv['max_upload_size_mb'] ?? '50') ?>" min="1" style="width:100%; padding:0.75rem; border:1px solid var(--border); border-radius:8px;" />
                        </label>
                    </div>
                    <div style="margin-top:1.5rem;">
                        <button type="submit" class="mode-button">Save Upload Settings</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Certificates -->
        <div class="tab-content" data-tab="tab-certificate">
            <div class="mode-card">
                <h2>Certificate Settings</h2>
                <p>Configure certificate validity and verification.</p>
                <form id="settings-certificate-form" data-skip="true" style="margin-top:1rem;">
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:1.25rem;">
                        <label>
                            <span style="display:block; font-weight:600; color:var(--primary); margin-bottom:0.3rem;">Certificate Validity (days, 0 = never expires)</span>
                            <input type="number" name="certificate_validity_days" value="<?= htmlspecialchars($sv['certificate_validity_days'] ?? '0') ?>" min="0" style="width:100%; padding:0.75rem; border:1px solid var(--border); border-radius:8px;" />
                        </label>
                    </div>
                    <div style="margin-top:1.5rem;">
                        <button type="submit" class="mode-button">Save Certificate Settings</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Integration -->
        <div class="tab-content" data-tab="tab-integration">
            <div class="mode-card">
                <h2>Integration Settings</h2>
                <p>API keys and cross-module integration configuration.</p>
                <form id="settings-integration-form" data-skip="true" style="margin-top:1rem;">
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:1.25rem;">
                        <label>
                            <span style="display:block; font-weight:600; color:var(--primary); margin-bottom:0.3rem;">Enrollment Invitation Expiry (days)</span>
                            <input type="number" name="invitation_expiry_days" value="<?= htmlspecialchars($sv['invitation_expiry_days'] ?? '7') ?>" min="1" style="width:100%; padding:0.75rem; border:1px solid var(--border); border-radius:8px;" />
                        </label>
                        <label>
                            <span style="display:block; font-weight:600; color:var(--primary); margin-bottom:0.3rem;">Video Conference Reminder First (minutes)</span>
                            <input type="number" name="video_conference_reminder_first_minutes" value="<?= htmlspecialchars($sv['video_conference_reminder_first_minutes'] ?? '30') ?>" min="5" style="width:100%; padding:0.75rem; border:1px solid var(--border); border-radius:8px;" />
                        </label>
                        <label>
                            <span style="display:block; font-weight:600; color:var(--primary); margin-bottom:0.3rem;">Video Conference Reminder Second (minutes)</span>
                            <input type="number" name="video_conference_reminder_second_minutes" value="<?= htmlspecialchars($sv['video_conference_reminder_second_minutes'] ?? '15') ?>" min="5" style="width:100%; padding:0.75rem; border:1px solid var(--border); border-radius:8px;" />
                        </label>
                    </div>
                    <div style="margin-top:1.5rem;">
                        <button type="submit" class="mode-button">Save Integration Settings</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Course Mappings -->
        <div class="tab-content" data-tab="tab-course-mappings">
            <div class="mode-card">
                <h2>Recommendation Course Mappings</h2>
                <p>Map <strong>development_area</strong> values from Performance Management to Learning courses. When an approved appraisal recommendation arrives, the system looks up this table to find which course to auto-assign.</p>
                <div style="margin-top:1rem; display:flex; gap:0.75rem; align-items:end; flex-wrap:wrap;">
                    <label style="flex:1; min-width:180px;">
                        <span style="display:block; font-size:0.8rem; font-weight:600; color:var(--primary); margin-bottom:0.25rem;">Development Area</span>
                        <input type="text" id="rec-dev-area" placeholder="e.g. Leadership" style="width:100%; padding:0.6rem; border:1px solid var(--border); border-radius:6px;">
                    </label>
                    <label style="flex:1; min-width:180px;">
                        <span style="display:block; font-size:0.8rem; font-weight:600; color:var(--primary); margin-bottom:0.25rem;">Course</span>
                        <select id="rec-course-id" style="width:100%; padding:0.6rem; border:1px solid var(--border); border-radius:6px;">
                            <option value="0">— Select course —</option>
                            <?php
                            $courseList = [];
                            try {
                                $courseStmt = $pdo->query("SELECT id, title FROM ld_course WHERE status = 'active' ORDER BY title ASC");
                                $courseList = $courseStmt->fetchAll(PDO::FETCH_ASSOC);
                            } catch (Throwable $e) {}
                            foreach ($courseList as $cl): ?>
                                <option value="<?= (int)$cl['id'] ?>"><?= htmlspecialchars($cl['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button type="button" id="rec-add-btn" style="padding:0.6rem 1.2rem; background:var(--primary); color:#fff; border:none; border-radius:6px; font-weight:600; cursor:pointer; white-space:nowrap;">+ Add</button>
                </div>
                <div id="rec-mapping-table" style="margin-top:1rem;"></div>
            </div>

            <div class="mode-card" style="margin-top:1.5rem;">
                <h2>Recognition Course Mappings</h2>
                <p>Map <strong>recognition_category</strong> values from Employee Recognition to Learning courses. When an approved recognition arrives, the system looks up this table to find which course to auto-assign.</p>
                <div style="margin-top:1rem; display:flex; gap:0.75rem; align-items:end; flex-wrap:wrap;">
                    <label style="flex:1; min-width:180px;">
                        <span style="display:block; font-size:0.8rem; font-weight:600; color:var(--primary); margin-bottom:0.25rem;">Recognition Category</span>
                        <input type="text" id="recog-category" placeholder="e.g. performance" style="width:100%; padding:0.6rem; border:1px solid var(--border); border-radius:6px;">
                    </label>
                    <label style="flex:1; min-width:180px;">
                        <span style="display:block; font-size:0.8rem; font-weight:600; color:var(--primary); margin-bottom:0.25rem;">Course</span>
                        <select id="recog-course-id" style="width:100%; padding:0.6rem; border:1px solid var(--border); border-radius:6px;">
                            <option value="0">— Select course —</option>
                            <?php foreach ($courseList as $cl): ?>
                                <option value="<?= (int)$cl['id'] ?>"><?= htmlspecialchars($cl['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button type="button" id="recog-add-btn" style="padding:0.6rem 1.2rem; background:var(--primary); color:#fff; border:none; border-radius:6px; font-weight:600; cursor:pointer; white-space:nowrap;">+ Add</button>
                </div>
                <div id="recog-mapping-table" style="margin-top:1rem;"></div>
            </div>
        </div>
    </div>
</div>
<script>
(function() {
    'use strict';

    function handleSettingsForm(formId, endpoint) {
        var form = document.getElementById(formId);
        if (!form) return;
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var fd = new FormData(form);
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

    var darkToggle = document.getElementById('admin-dark-mode-toggle');
    var darkLabel = document.getElementById('admin-dark-mode-label');
    function updateDarkModeLabel() {
        if (!darkLabel || !darkToggle) return;
        darkLabel.textContent = darkToggle.checked ? 'Dark mode is on' : 'Dark mode is off';
    }
    if (darkToggle) {
        if (localStorage.getItem('theme') === 'dark') { darkToggle.checked = true; }
        updateDarkModeLabel();
        darkToggle.addEventListener('change', function() {
            if (darkToggle.checked) {
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
            } else {
                document.documentElement.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
            }
            updateDarkModeLabel();
        });
    }

    handleSettingsForm('settings-general-form', 'pages/admin/ajax/edit-settings.php');
    handleSettingsForm('settings-quiz-form', 'pages/admin/ajax/edit-settings.php');
    handleSettingsForm('settings-moderation-form', 'pages/admin/ajax/edit-settings.php');
    handleSettingsForm('settings-upload-form', 'pages/admin/ajax/edit-settings.php');
    handleSettingsForm('settings-certificate-form', 'pages/admin/ajax/edit-settings.php');
    handleSettingsForm('settings-integration-form', 'pages/admin/ajax/edit-settings.php');
})();
</script>
<script src="js/admin/course-mappings.js"></script>
