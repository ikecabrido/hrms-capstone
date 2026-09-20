<?php

require_once __DIR__ . '/../../../../../database/db.php';
$db = (new Database())->getConnection();

require_once __DIR__ . '/../../../lib/ajax/document_template_helper.php';

$templateCode = 'employee_handbook';
$templateRecord = lc_get_active_template($db, $templateCode);

$saveMessage = '';
$saveMessageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_handbook') {
    $content = trim((string) ($_POST['handbook_content'] ?? ''));
    $version = trim((string) ($_POST['version'] ?? '1.0'));
    $effectiveDate = trim((string) ($_POST['effective_date'] ?? ''));
    $status = trim((string) ($_POST['status'] ?? 'Draft'));

    if ($content === '') {
        $saveMessage = 'Handbook content cannot be empty.';
        $saveMessageType = 'error';
    } else {
        try {
            if (!empty($templateRecord['template_id'])) {
                $db->prepare("
                    UPDATE lc_document_templates
                    SET template_content = :content, version = :version, effective_date = :ed, status = :status, updated_at = NOW()
                    WHERE template_id = :id
                ")->execute([
                    ':content' => $content,
                    ':version' => $version,
                    ':ed' => $effectiveDate ?: null,
                    ':status' => $status,
                    ':id' => $templateRecord['template_id'],
                ]);
            } else {
                $db->prepare("
                    INSERT INTO lc_document_templates
                        (template_code, template_name, version, status, template_content, effective_date, created_by_role)
                    VALUES
                        (:code, :name, :version, :status, :content, :ed, 'legal')
                ")->execute([
                    ':code' => $templateCode,
                    ':name' => 'Employee Handbook',
                    ':version' => $version,
                    ':status' => $status,
                    ':content' => $content,
                    ':ed' => $effectiveDate ?: null,
                ]);
            }
            $saveMessage = 'Handbook saved successfully.';
            $saveMessageType = 'success';
            $templateRecord = lc_get_active_template($db, $templateCode);
        } catch (Throwable $e) {
            $saveMessage = 'Failed to save: ' . $e->getMessage();
            $saveMessageType = 'error';
        }
    }
}

$currentContent = (string) ($templateRecord['template_content'] ?? '');
$currentVersion = (string) ($templateRecord['version'] ?? '1.0');
$currentEffectiveDate = (string) ($templateRecord['effective_date'] ?? '');
$currentStatus = (string) ($templateRecord['status'] ?? 'Draft');

$previewUrl = '?page=preview-document&template_code=' . urlencode($templateCode) . '&mode=preview';
?>

<?php if (!empty($saveMessage)): ?>
<div class="dg-template-frame">
    <div class="dg-<?= htmlspecialchars($saveMessageType === 'success' ? 'success' : 'empty') ?>" style="padding:10px 14px; border-radius:8px; margin-bottom:12px; background:<?= $saveMessageType === 'success' ? 'rgba(47,158,110,.12)' : 'rgba(214,72,74,.10)' ?>; color:<?= $saveMessageType === 'success' ? '#1f7a52' : '#a3272a' ?>; border:1px solid <?= $saveMessageType === 'success' ? 'rgba(47,158,110,.25)' : 'rgba(214,72,74,.22)' ?>;">
        <?= htmlspecialchars($saveMessage) ?>
    </div>
</div>
<?php endif; ?>

<div class="dg-template-frame">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; flex-wrap:wrap; gap:10px;">
        <h3 style="margin:0; font-size:.95rem; font-weight:700; color:var(--text-900,#1b2430);">
            <i class="bi bi-book"></i> Edit Employee Handbook
        </h3>
        <a href="<?= htmlspecialchars($previewUrl) ?>" class="dg-btn-cancel" target="_blank">
            <i class="bi bi-eye"></i> Preview
        </a>
    </div>

    <form method="post" action="" style="display:flex; flex-direction:column; gap:14px;">
        <input type="hidden" name="action" value="save_handbook">

        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap:12px;">
            <div class="dg-field">
                <label style="font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--text-400,#8b93a1);">Version</label>
                <input type="text" name="version" value="<?= htmlspecialchars($currentVersion) ?>" style="padding:8px 10px; border-radius:8px; border:1px solid var(--border,#e4e8ee); font-size:.85rem;">
            </div>
            <div class="dg-field">
                <label style="font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--text-400,#8b93a1);">Effective Date</label>
                <input type="date" name="effective_date" value="<?= htmlspecialchars($currentEffectiveDate) ?>" style="padding:8px 10px; border-radius:8px; border:1px solid var(--border,#e4e8ee); font-size:.85rem;">
            </div>
            <div class="dg-field">
                <label style="font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--text-400,#8b93a1);">Status</label>
                <select name="status" style="padding:8px 10px; border-radius:8px; border:1px solid var(--border,#e4e8ee); font-size:.85rem; background:#fff;">
                    <?php foreach (['Draft','Approved','Active','Inactive','Retired'] as $s): ?>
                        <option value="<?= htmlspecialchars($s) ?>" <?= $currentStatus === $s ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div style="display:flex; gap:10px; justify-content:flex-end;">
            <a href="?page=notification-compose&mode=reply&notification_key=warning&to_recipient_email=&to_recipient_name=&template_code=employee_handbook&scenario=general" class="dg-btn-generate"><i class="bi bi-envelope"></i> Send to Email</a>
            <button type="submit" class="dg-btn-generate">
                <i class="bi bi-check-lg"></i> Save Handbook
            </button>
        </div>
    </form>
</div>
