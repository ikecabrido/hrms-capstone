<?php
require_once dirname(__DIR__, 4) . '/database/db.php';
require_once __DIR__ . '/../../classes/LearningDatabaseTools.php';

$learningTableCount = 0;
try {
    $pdo = LearningDatabaseTools::connection();
    $learningTableCount = (int) $pdo->query("SELECT COUNT(*)
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_TYPE = 'BASE TABLE'
          AND LEFT(TABLE_NAME, 3) = 'ld_'")->fetchColumn();
} catch (Throwable $e) {
    DbError::capture($e, 'admin/database-export');
}
?>
<div class="admin-data-layout">
    <?php include __DIR__ . '/../../includes/admin-moderation-navigation.php'; ?>
    <main class="module-content export-page">
        <div class="home-dashboard">
            <section class="home-hero ldx-page-hero">
                <div class="home-hero-main">
                    <h1>Export LD Database</h1>
                    <p>Back up or migrate Learning data. Choose an export, then restore its structure and rows in the right order.</p>
                </div>
            </section>

            <div class="ldx-grid">
                <section class="ldx-card is-wide ldx-export-card">
                    <h2 class="ldx-card-head"><i class="fas fa-database" aria-hidden="true"></i> Download export</h2>
                    <div class="ldx-export-overview">
                        <p class="ldx-text">Choose the contents for your backup or migration file.</p>
                        <p class="ldx-metric">
                            <span class="ldx-metric-value"><?= (int) $learningTableCount ?></span>
                            <span class="ldx-metric-label">Learning table<?= $learningTableCount === 1 ? '' : 's' ?></span>
                        </p>
                    </div>
                    <form class="ldx-form" method="get" action="pages/admin/ajax/export-ld-database.php" data-skip>
                        <div class="ldx-export-controls">
                            <label class="ldx-export-type-field">
                                <span class="ldx-label">Export type</span>
                                <select id="ld-export-type" name="type" required>
                                    <option value="full">Full backup — structure and rows</option>
                                    <option value="schema">Schema only — table structure</option>
                                    <option value="data">Data only — table rows</option>
                                </select>
                            </label>
                            <button class="ldx-btn" type="submit">
                                <i class="fas fa-download" aria-hidden="true"></i> Download SQL
                            </button>
                        </div>
                    </form>
                    <div class="ldx-export-notes">
                        <p class="ldx-note">
                        <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                            <span>Exports may contain personal information. Store files securely.</span>
                        </p>
                        <p class="ldx-note is-info">
                        <i class="fas fa-circle-info" aria-hidden="true"></i>
                            <span>For a full restore, apply a <strong>Schema only</strong> file first, then import a <strong>Data only</strong> file. A combined full backup cannot be used directly in either importer.</span>
                        </p>
                    </div>
                </section>

                <section class="ldx-card">
                    <h2 class="ldx-card-head"><span class="ldx-step-number">1</span><i class="fas fa-table-cells-large" aria-hidden="true"></i> Apply table structure</h2>
                    <p class="ldx-text">Use a <strong>Schema only</strong> file to create or change <code>ld_</code> tables. Validate before applying.</p>
                    <form id="ld-schema-form" class="ldx-form" data-skip enctype="multipart/form-data">
                        <label class="ldx-label" for="ld-schema-file">Learning schema SQL file</label>
                        <input id="ld-schema-file" type="file" name="sql_file" accept=".sql,application/sql" required>
                        <label class="ldx-confirm" for="ld-schema-confirm">
                            <input id="ld-schema-confirm" type="checkbox" name="confirm" value="1">
                            <span>Confirm destructive schema changes. Dropped or rewritten table rows cannot be recovered here.</span>
                        </label>
                        <div class="ldx-actions">
                            <button type="submit" value="validate" class="ldx-btn ghost">
                                <i class="fas fa-circle-check" aria-hidden="true"></i> Validate file
                            </button>
                            <button type="submit" value="apply" class="ldx-btn danger">
                                <i class="fas fa-code" aria-hidden="true"></i> Apply structure
                            </button>
                        </div>
                        <p id="ld-schema-status" class="ldx-status" role="status" aria-live="polite"></p>
                        <ul id="ld-schema-log" class="ldx-log" hidden></ul>
                        <div class="ldx-actions" id="ld-schema-rollback-wrap" hidden>
                            <button type="button" id="ld-schema-rollback" class="ldx-btn ghost">
                                <i class="fas fa-download" aria-hidden="true"></i> Download pre-change definitions
                            </button>
                        </div>
                        <p class="ldx-text ldx-limit-note">No fixed statement-count cap. File size is bounded by the server's PHP upload settings.</p>
                    </form>
                    <p class="ldx-note">
                        <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                        <span>DDL changes take effect immediately and cannot be rolled back. Download a full backup before applying destructive changes.</span>
                    </p>
                </section>

                <section class="ldx-card">
                    <h2 class="ldx-card-head"><span class="ldx-step-number">2</span><i class="fas fa-file-import" aria-hidden="true"></i> Import row data</h2>
                    <p class="ldx-text">After the tables are ready, use a <strong>Data only</strong> file to load rows into existing <code>ld_</code> tables.</p>
                    <form id="ld-data-import-form" class="ldx-form" data-skip enctype="multipart/form-data">
                        <label class="ldx-label" for="ld-data-import-file">Learning data SQL file</label>
                        <input id="ld-data-import-file" type="file" name="sql_file" accept=".sql,application/sql" required>
                        <div class="ldx-actions">
                            <button type="submit" value="validate" class="ldx-btn ghost">
                                <i class="fas fa-circle-check" aria-hidden="true"></i> Validate file
                            </button>
                            <button type="submit" value="import" class="ldx-btn">
                                <i class="fas fa-file-import" aria-hidden="true"></i> Import data
                            </button>
                        </div>
                        <p id="ld-data-import-status" class="ldx-status" role="status" aria-live="polite"></p>
                    </form>
                    <p class="ldx-note is-info">
                        <i class="fas fa-circle-info" aria-hidden="true"></i>
                        <span>Only row insert statements are accepted. Duplicate keys or any other error roll back the entire data import.</span>
                    </p>
                </section>
            </div>
        </div>
    </main>
</div>

<script>
(function() {
    var form = document.getElementById('ld-data-import-form');
    if (!form) return;

    form.addEventListener('submit', function(event) {
        event.preventDefault();
        var mode = event.submitter && event.submitter.value === 'validate' ? 'validate' : 'import';
        var buttons = Array.from(form.querySelectorAll('button[type="submit"]'));
        var status = document.getElementById('ld-data-import-status');
        var formData = new FormData(form);
        formData.append('mode', mode);
        buttons.forEach(function(button) { button.disabled = true; });
        status.classList.remove('is-success', 'is-error');
        status.textContent = mode === 'validate' ? 'Validating file...' : 'Importing data...';

        fetch('pages/admin/ajax/import-ld-data.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(function(response) {
            return response.json().then(function(result) {
                if (!response.ok || !result.success) throw new Error(result.message || 'Import request failed.');
                return result;
            });
        })
        .then(function(result) {
            status.textContent = result.message;
            status.classList.add('is-success');
            if (window.showToast) window.showToast(result.message, 'success');
            if (mode === 'import') form.reset();
        })
        .catch(function(error) {
            status.textContent = error.message || 'Unable to process this file.';
            status.classList.add('is-error');
            if (window.showToast) window.showToast(status.textContent, 'error');
        })
        .finally(function() {
            buttons.forEach(function(button) { button.disabled = false; });
        });
    });
})();
</script>

<script>
(function() {
    var form = document.getElementById('ld-schema-form');
    if (!form) return;

    var status = document.getElementById('ld-schema-status');
    var logList = document.getElementById('ld-schema-log');
    var confirmBox = document.getElementById('ld-schema-confirm');
    var confirmRow = form.querySelector('.ldx-confirm');
    var rollbackWrap = document.getElementById('ld-schema-rollback-wrap');
    var rollbackButton = document.getElementById('ld-schema-rollback');
    var rollbackSql = '';

    var logIcons = {
        applied: 'fa-check-circle',
        failed: 'fa-circle-xmark',
        skipped: 'fa-minus-circle'
    };
    var logWords = { applied: 'Applied', failed: 'Failed', skipped: 'Skipped' };

    function renderLog(entries) {
        logList.innerHTML = '';
        if (!entries || !entries.length) {
            logList.hidden = true;
            return;
        }

        entries.forEach(function(entry) {
            var item = document.createElement('li');
            item.className = 'is-' + entry.status;

            var icon = document.createElement('i');
            icon.className = 'fas ' + (logIcons[entry.status] || 'fa-circle');
            icon.setAttribute('aria-hidden', 'true');

            var label = document.createElement('span');
            label.className = 'ldx-log-label';
            label.textContent = entry.label + (entry.tables && entry.tables.length ? ': ' + entry.tables.join(', ') : '');

            var meta = document.createElement('span');
            meta.className = 'ldx-log-meta';
            meta.textContent = logWords[entry.status] || entry.status;
            if (entry.line) meta.textContent += ' on line ' + entry.line;
            if (entry.message) meta.textContent += ' — ' + entry.message;

            item.appendChild(icon);
            item.appendChild(label);
            item.appendChild(meta);
            logList.appendChild(item);
        });

        logList.hidden = false;
    }

    form.addEventListener('submit', function(event) {
        event.preventDefault();
        var mode = event.submitter && event.submitter.value === 'validate' ? 'validate' : 'apply';
        var buttons = Array.from(form.querySelectorAll('button[type="submit"]'));
        var formData = new FormData(form);
        formData.append('mode', mode);

        buttons.forEach(function(button) { button.disabled = true; });
        status.classList.remove('is-success', 'is-error');
        confirmRow.classList.remove('is-required');
        logList.hidden = true;
        status.textContent = mode === 'validate' ? 'Checking the file...' : 'Applying changes to the database...';

        fetch('pages/admin/ajax/apply-ld-schema.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': window.CSRF_TOKEN || (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
            },
            body: formData
        })
        .then(function(response) {
            // A failed connection dies before any JSON is written, so fall back
            // to the raw body instead of an "unexpected token" message.
            return response.text().then(function(text) {
                var result;
                try {
                    result = JSON.parse(text);
                } catch (error) {
                    result = { success: false, message: text.slice(0, 300) || 'The server returned an unexpected response.' };
                }
                return { ok: response.ok, result: result };
            });
        })
        .then(function(payload) {
            var result = payload.result || {};
            renderLog(result.log);

            if (result.rollbackSql) {
                rollbackSql = result.rollbackSql;
                rollbackWrap.hidden = false;
            }

            if (!payload.ok || !result.success) {
                status.textContent = result.message || 'The file could not be processed.';
                status.classList.add('is-error');
                if (result.needsConfirm) {
                    confirmBox.checked = false;
                    confirmRow.classList.add('is-required');
                    confirmBox.focus();
                }
                if (window.showToast) window.showToast(status.textContent, 'error');
                return;
            }

            status.textContent = result.message;
            status.classList.add('is-success');
            if (window.showToast) window.showToast(result.message, 'success');
            if (mode === 'apply') form.reset();
        })
        .catch(function(error) {
            status.textContent = error.message || 'Unable to send the file.';
            status.classList.add('is-error');
            if (window.showToast) window.showToast(status.textContent, 'error');
        })
        .finally(function() {
            buttons.forEach(function(button) { button.disabled = false; });
        });
    });

    rollbackButton.addEventListener('click', function() {
        if (!rollbackSql) return;
        var url = URL.createObjectURL(new Blob([rollbackSql], { type: 'application/sql' }));
        var link = document.createElement('a');
        link.href = url;
        link.download = 'ld_schema_before_change.sql';
        document.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(function() { URL.revokeObjectURL(url); }, 1000);
    });
})();
</script>
