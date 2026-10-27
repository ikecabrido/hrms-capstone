<?php
require_once dirname(__DIR__, 3) . '/classes/TrainingAuthoring.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

$templateId = (int) ($_GET['id'] ?? 0);
$template = null;
$courses = [];

try {
    $pdo = (new Database())->getConnection();
    $courses = $pdo->query("SELECT id, title FROM ld_course WHERE status <> 'archived' ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);
    if ($templateId > 0) {
        $stmt = $pdo->prepare('SELECT id, title, description, category FROM ld_course_template WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $templateId]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
} catch (Throwable $e) {
    DbError::capture($e, 'instructor/training-subpage/template');
}

$isEdit = $templateId > 0;
$safe = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
?>
<div class="module-content catalog-page catalog-manager training-authoring-page">
    <?= TrainingAuthoring::renderHero(
        $isEdit ? 'Edit Course Template' : 'Add Course Template',
        $isEdit
            ? 'Update template details or refresh its saved structure from an existing course.'
            : 'Create a reusable template from an existing course structure.',
        '?page=instructor/training&tab=template'
    ) ?>

    <?php if ($templateId > 0 && !$template): ?>
        <div class="mode-card" role="alert">
            <h2>Template not found</h2>
            <p>This template may have been deleted. Return to Training to view the available templates.</p>
        </div>
    <?php else: ?>
        <div class="mode-card training-authoring-form-card">
            <form id="training-template-form" class="cat-form" data-skip>
                <input type="hidden" name="id" value="<?= $isEdit ? (int) $template['id'] : '' ?>" />
                <?php if (!$isEdit): ?>
                    <label class="cat-field">
                        <span class="cat-field-label">Source course</span>
                        <select name="course_id" required>
                            <option value="">Choose a course</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= (int) $course['id'] ?>"><?= $safe($course['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <?php if (!$courses): ?>
                        <p class="cat-field-hint">Create a course before saving a course template.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <label class="cat-field">
                        <span class="cat-field-label">Replace structure from course <span class="cat-field-hint">(optional)</span></span>
                        <select name="course_id">
                            <option value="">Keep the current structure</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= (int) $course['id'] ?>"><?= $safe($course['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <p class="cat-field-hint">Selecting a course replaces the saved modules and lessons with a snapshot of that course.</p>
                <?php endif; ?>
                <label class="cat-field">
                    <span class="cat-field-label">Template title</span>
                    <input type="text" name="title" maxlength="255" required value="<?= $isEdit ? $safe($template['title']) : '' ?>" placeholder="e.g. New Employee Onboarding" />
                </label>
                <label class="cat-field">
                    <span class="cat-field-label">Description</span>
                    <textarea name="description" rows="4" placeholder="Describe when this template should be reused."><?= $isEdit ? $safe($template['description']) : '' ?></textarea>
                </label>
                <?php if ($isEdit): ?>
                    <p class="cat-field-hint">Current category: <?= $safe($template['category'] ?: 'Not set') ?>. Replacing the structure also updates the template category.</p>
                <?php endif; ?>
                <p id="training-template-error" role="alert" style="display:none; color:#b91c1c;"></p>
                <div class="mode-actions">
                    <button type="submit" class="mode-button" id="training-template-save"><?= $isEdit ? 'Save Template Changes' : 'Create Template' ?></button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>
<?php if (!$templateId || $isEdit): ?>
<script>
(function () {
    var form = document.getElementById('training-template-form');
    if (!form) return;
    var button = document.getElementById('training-template-save');
    var error = document.getElementById('training-template-error');
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (!form.reportValidity()) return;
        var editing = Boolean(form.elements.id.value);
        button.disabled = true;
        error.style.display = 'none';
        button.textContent = 'Saving...';
        var values = new FormData(form);
        var payload = editing
            ? { id: Number(values.get('id')), title: values.get('title'), description: values.get('description'), course_id: Number(values.get('course_id') || 0) }
            : { course_id: Number(values.get('course_id')), title: values.get('title'), description: values.get('description') };
        fetch(editing
            ? 'pages/instructor/elearning-subpage/ajax/edit-template.php'
            : 'pages/instructor/elearning-subpage/ajax/save-template.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(payload)
        }).then(function (response) {
            return response.json().then(function (result) {
                if (!response.ok || !result.success) throw new Error(result.message || 'Unable to save template.');
                return result;
            });
        }).then(function (result) {
            if (window.showToast) window.showToast(result.message || 'Template saved.', 'success');
            window.location.href = '?page=instructor/training&tab=template';
        }).catch(function (saveError) {
            error.textContent = saveError.message || 'Unable to save template.';
            error.style.display = '';
            if (window.showToast) window.showToast(error.textContent, 'error');
        }).finally(function () {
            button.disabled = false;
            button.textContent = editing ? 'Save Template Changes' : 'Create Template';
        });
    });
})();
</script>
<?php endif; ?>
