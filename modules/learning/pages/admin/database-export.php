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
    <main class="module-content">
        <div class="mode-card">
            <h1>Export LD Database</h1>
            <p>Download the Learning module's database tables and records as an SQL file.</p>
            <p><strong><?= $learningTableCount ?></strong> Learning tables will be included from the configured database.</p>
            <a href="pages/admin/ajax/export-ld-database.php" download style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.7rem 1rem;border-radius:6px;background:var(--primary);color:#fff;text-decoration:none;font-weight:700;">
                <i class="fas fa-download" aria-hidden="true"></i> Download SQL export
            </a>
            <p style="margin-top:1rem;color:var(--muted);font-size:0.82rem;">The export includes Learning records that may contain personal information. Store the downloaded file securely.</p>

            <hr style="margin:1.5rem 0;border:0;border-top:1px solid var(--border, #e5e7eb);">

            <h2>Import LD Data</h2>
            <p>Import row data from a SQL export into existing Learning tables. Table definitions are never created, altered, or dropped.</p>
            <p style="color:var(--muted);font-size:0.82rem;">Only literal INSERT statements for existing <code>ld_</code> tables are processed. Duplicate keys or other errors roll back the entire import.</p>
            <form id="ld-data-import-form" data-skip enctype="multipart/form-data" style="display:grid;gap:0.8rem;max-width:620px;">
                <label for="ld-data-import-file" style="font-size:0.85rem;font-weight:600;">Learning SQL export</label>
                <input id="ld-data-import-file" type="file" name="sql_file" accept=".sql,application/sql" required>
                <div style="display:flex;gap:0.6rem;flex-wrap:wrap;">
                    <button type="submit" value="validate" class="mode-button" style="display:inline-flex;align-items:center;gap:0.45rem;">
                        <i class="fas fa-check-circle" aria-hidden="true"></i> Validate File
                    </button>
                    <button type="submit" value="import" class="mode-button" style="display:inline-flex;align-items:center;gap:0.45rem;">
                        <i class="fas fa-file-import" aria-hidden="true"></i> Import Data
                    </button>
                </div>
                <p id="ld-data-import-status" role="status" aria-live="polite" style="margin:0;font-size:0.85rem;"></p>
            </form>
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
            status.style.color = '#047857';
            if (window.showToast) window.showToast(result.message, 'success');
            if (mode === 'import') form.reset();
        })
        .catch(function(error) {
            status.textContent = error.message || 'Unable to process this file.';
            status.style.color = '#b91c1c';
            if (window.showToast) window.showToast(status.textContent, 'error');
        })
        .finally(function() {
            buttons.forEach(function(button) { button.disabled = false; });
        });
    });
})();
</script>