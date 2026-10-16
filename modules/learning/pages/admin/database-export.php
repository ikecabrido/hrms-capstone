<?php
require_once dirname(__DIR__, 4) . '/database/db.php';

$learningTableCount = 0;
try {
    $pdo = (new Database())->getConnection();
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
                    <p>Download the Learning module's tables and records as a portable SQL file, or restore row data from an earlier export.</p>
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
                        <span>Only literal INSERT statements for existing <code>ld_</code> tables are processed. Duplicate keys or any other error roll back the entire import.</span>
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
