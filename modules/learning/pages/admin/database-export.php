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
            <section class="home-hero">
                <div class="home-hero-main">
                    <h1>Export LD Database</h1>
                    <p>Download the Learning module's tables and records as a portable SQL file, restore row data from an earlier export, or apply a schema file that creates, changes and drops <code>ld_</code> tables.</p>
                </div>
            </section>

            <div class="ldx-grid">
                <section class="ldx-card">
                    <h2 class="ldx-card-head"><i class="fas fa-database" aria-hidden="true"></i> Download export</h2>
                    <p class="ldx-text">Every <code>ld_</code> table is written to one SQL file with its schema and rows, so the file can be restored on another installation.</p>
                    <p class="ldx-metric">
                        <span class="ldx-metric-value"><?= (int) $learningTableCount ?></span>
                        <span class="ldx-metric-label">Learning table<?= $learningTableCount === 1 ? '' : 's' ?> in this database</span>
                    </p>
                    <div class="ldx-actions">
                        <a class="ldx-btn" href="pages/admin/ajax/export-ld-database.php" download>
                            <i class="fas fa-download" aria-hidden="true"></i> Download SQL export
                        </a>
                    </div>
                    <p class="ldx-note">
                        <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                        <span>The export contains Learning records that may include personal information. Store the downloaded file securely.</span>
                    </p>
                </section>

                <section class="ldx-card">
                    <h2 class="ldx-card-head"><i class="fas fa-file-import" aria-hidden="true"></i> Import row data</h2>
                    <p class="ldx-text">Load rows from an earlier export back into the existing Learning tables. Table definitions are never created, altered, or dropped.</p>
                    <form id="ld-data-import-form" class="ldx-form" data-skip enctype="multipart/form-data">
                        <label class="ldx-label" for="ld-data-import-file">Learning SQL export</label>
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
                        <span>Only literal INSERT statements for existing <code>ld_</code> tables are processed. To create new tables, use <strong>Apply schema file</strong> below. Duplicate keys or any other error roll back the entire import.</span>
                    </p>
                </section>

                <section class="ldx-card is-wide">
                    <h2 class="ldx-card-head"><i class="fas fa-code" aria-hidden="true"></i> Apply schema file</h2>
                    <p class="ldx-text">
                        Run a <code>.sql</code> file against the Learning schema: create new <code>ld_</code> tables, change their
                        columns and indexes, rename them, or drop them. Every statement is authorised before anything runs, and a
                        statement that touches a table without the <code>ld_</code> prefix is refused — as are data changes, which
                        belong in the import above.
                    </p>
                    <form id="ld-schema-form" class="ldx-form" data-skip enctype="multipart/form-data">
                        <label class="ldx-label" for="ld-schema-file">Learning schema SQL file</label>
                        <input id="ld-schema-file" type="file" name="sql_file" accept=".sql,application/sql" required>
                        <label class="ldx-confirm" for="ld-schema-confirm">
                            <input id="ld-schema-confirm" type="checkbox" name="confirm" value="1">
                            <span>I understand this file can drop or rewrite existing Learning tables, and that the rows of a dropped table cannot be recovered here.</span>
                        </label>
                        <div class="ldx-actions">
                            <button type="submit" value="validate" class="ldx-btn ghost">
                                <i class="fas fa-circle-check" aria-hidden="true"></i> Validate file
                            </button>
                            <button type="submit" value="apply" class="ldx-btn danger">
                                <i class="fas fa-code" aria-hidden="true"></i> Apply to database
                            </button>
                        </div>
                        <p id="ld-schema-status" class="ldx-status" role="status" aria-live="polite"></p>
                        <ul id="ld-schema-log" class="ldx-log" hidden></ul>
                        <div class="ldx-actions" id="ld-schema-rollback-wrap" hidden>
                            <button type="button" id="ld-schema-rollback" class="ldx-btn ghost">
                                <i class="fas fa-download" aria-hidden="true"></i> Download pre-change definitions
                            </button>
                        </div>
                        <p class="ldx-text">There is no fixed statement-count limit. Maximum file size is determined by the server's PHP upload settings.</p>
                    </form>
                    <p class="ldx-note">
                        <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                        <span>
                            Table definitions cannot be rolled back — each statement takes effect immediately. Validate first, and keep a
                            fresh SQL export before applying anything that drops or rewrites a table. Only <code>CREATE TABLE</code>,
                            <code>ALTER TABLE</code>, <code>RENAME TABLE</code>, <code>DROP TABLE</code>, <code>CREATE INDEX</code> and
                            <code>DROP INDEX</code> are accepted.
                        </span>
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
