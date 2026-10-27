<?php
require_once dirname(__DIR__, 3) . '/classes/TrainingAuthoring.php';
require_once dirname(__DIR__, 3) . '/classes/skill.php';

$skillId = (int) ($_GET['id'] ?? 0);
$skill = null;
try {
    if ($skillId > 0) {
        $skill = (new Skill())->getById($skillId);
    }
} catch (Throwable $e) {
    DbError::capture($e, 'instructor/training-subpage/skill');
}

$isEdit = $skillId > 0;
$defaultIcon = Skill::DEFAULT_ICON;
$selectedIcon = $isEdit && Skill::isValidIcon((string) ($skill['icon'] ?? ''))
    ? (string) $skill['icon']
    : $defaultIcon;
$safe = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
?>
<div class="module-content catalog-page catalog-manager training-authoring-page">
    <?= TrainingAuthoring::renderHero(
        $isEdit ? 'Edit Skill' : 'Add Skill',
        'Maintain the shared skill catalogue and choose an icon that helps identify this skill.',
        '?page=instructor/training&tab=skills'
    ) ?>

    <?php if ($skillId > 0 && !$skill): ?>
        <div class="mode-card" role="alert">
            <h2>Skill not found</h2>
            <p>This skill may have been removed. Return to Training to view available skills.</p>
        </div>
    <?php else: ?>
        <div class="mode-card training-authoring-form-card">
            <form id="training-skill-form" class="cat-form" data-skip>
                <input type="hidden" name="id" value="<?= $isEdit ? (int) $skill['id'] : '' ?>" />
                <label class="cat-field">
                    <span class="cat-field-label">Skill name</span>
                    <input type="text" name="name" maxlength="150" required value="<?= $isEdit ? $safe($skill['name']) : '' ?>" placeholder="e.g. Incident Investigation" />
                </label>
                <label class="cat-field">
                    <span class="cat-field-label">Description <span class="cat-field-hint">(optional)</span></span>
                    <textarea name="description" rows="3" placeholder="What this skill covers and where it applies."><?= $isEdit ? $safe($skill['description']) : '' ?></textarea>
                </label>
                <?php if ($isEdit): ?>
                    <label class="cat-field">
                        <span class="cat-field-label">Suggested skill</span>
                        <span><input type="checkbox" name="suggested" value="1" <?= !empty($skill['suggested']) ? 'checked' : '' ?> /> Include in suggested skills</span>
                    </label>
                    <label class="cat-field">
                        <span class="cat-field-label">Status</span>
                        <select name="status">
                            <option value="active" <?= ($skill['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="archived" <?= ($skill['status'] ?? '') === 'archived' ? 'selected' : '' ?>>Archived</option>
                        </select>
                    </label>
                <?php endif; ?>
                <fieldset class="cat-field skill-icon-field">
                    <legend class="cat-field-label">Choose an icon</legend>
                    <p class="skill-icon-source">Choose a suggested icon or <a href="https://fontawesome.com/search?ic=free-collection&s=solid&o=r" target="_blank" rel="noopener noreferrer">browse Font Awesome Free icons</a> and paste its solid class below.</p>
                    <label class="cat-field skill-icon-custom-field">
                        <span class="cat-field-label">Font Awesome class <span class="cat-field-hint">(optional)</span></span>
                        <input type="text" id="training-skill-icon-custom" maxlength="64" pattern="fas fa-[a-z0-9]+(-[a-z0-9]+)*" placeholder="e.g. fas fa-award" autocomplete="off" />
                    </label>
                    <div class="skill-icon-preview" aria-live="polite">
                        <i id="training-skill-icon-preview" class="<?= $safe($selectedIcon) ?>" aria-hidden="true"></i>
                        <span id="training-skill-icon-preview-label"><?= $safe(Skill::ICON_OPTIONS[$selectedIcon] ?? $selectedIcon) ?></span>
                    </div>
                    <input type="search" id="training-skill-icon-search" placeholder="Search icons (e.g. teamwork, learning)" aria-label="Search skill icons" autocomplete="off" />
                    <input type="hidden" name="icon" value="<?= $safe($selectedIcon) ?>" />
                    <div class="skill-icon-picker" id="training-skill-icon-picker" role="radiogroup" aria-label="Available skill icons">
                        <?php foreach (Skill::ICON_OPTIONS as $iconClass => $iconLabel): ?>
                            <button type="button" class="skill-icon-option<?= $iconClass === $selectedIcon ? ' is-selected' : '' ?>" data-icon="<?= $safe($iconClass) ?>" data-icon-label="<?= $safe($iconLabel) ?>" role="radio" aria-checked="<?= $iconClass === $selectedIcon ? 'true' : 'false' ?>" aria-label="<?= $safe($iconLabel) ?>" title="<?= $safe($iconLabel) ?>">
                                <i class="<?= $safe($iconClass) ?>" aria-hidden="true"></i>
                                <span><?= $safe($iconLabel) ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <p id="training-skill-icon-empty" class="skill-icon-empty" style="display:none;">No matching icons. Try another search.</p>
                </fieldset>
                <p id="training-skill-error" role="alert" style="display:none; color:#b91c1c;"></p>
                <div class="mode-actions">
                    <button type="submit" class="mode-button" id="training-skill-save"><?= $isEdit ? 'Save Skill Changes' : 'Add Skill' ?></button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>
<?php if (!$skillId || $isEdit): ?>
<script>
(function () {
    var form = document.getElementById('training-skill-form');
    if (!form) return;
    var iconInput = form.elements.icon;
    var customIcon = document.getElementById('training-skill-icon-custom');
    var iconSearch = document.getElementById('training-skill-icon-search');
    var picker = document.getElementById('training-skill-icon-picker');
    var preview = document.getElementById('training-skill-icon-preview');
    var previewLabel = document.getElementById('training-skill-icon-preview-label');
    var empty = document.getElementById('training-skill-icon-empty');
    var button = document.getElementById('training-skill-save');
    var error = document.getElementById('training-skill-error');
    var isEditing = Boolean(form.elements.id.value);
    var selectedIcon = iconInput.value;
    var selectedLabel = previewLabel.textContent;

    function selectIcon(icon, label) {
        if (!/^fas fa-[a-z0-9]+(?:-[a-z0-9]+)*$/.test(icon)) return;
        iconInput.value = icon;
        selectedIcon = icon;
        selectedLabel = label;
        customIcon.value = '';
        customIcon.setCustomValidity('');
        preview.className = icon;
        previewLabel.textContent = label;
        picker.querySelectorAll('.skill-icon-option').forEach(function (option) {
            var selected = option.dataset.icon === icon;
            option.classList.toggle('is-selected', selected);
            option.setAttribute('aria-checked', selected ? 'true' : 'false');
        });
    }

    picker.addEventListener('click', function (event) {
        var option = event.target.closest('.skill-icon-option');
        if (option) selectIcon(option.dataset.icon, option.dataset.iconLabel);
    });
    iconSearch.addEventListener('input', function () {
        var query = iconSearch.value.trim().toLowerCase();
        var visible = 0;
        picker.querySelectorAll('.skill-icon-option').forEach(function (option) {
            option.hidden = query !== '' && (option.dataset.icon + ' ' + option.dataset.iconLabel).toLowerCase().indexOf(query) === -1;
            if (!option.hidden) visible++;
        });
        empty.style.display = visible ? 'none' : '';
    });
    customIcon.addEventListener('input', function () {
        var icon = customIcon.value.trim();
        if (!icon) {
            customIcon.setCustomValidity('');
            selectIcon(selectedIcon, selectedLabel);
            return;
        }
        customIcon.setCustomValidity(/^fas fa-[a-z0-9]+(?:-[a-z0-9]+)*$/.test(icon) ? '' : 'Use a Font Awesome solid class such as fas fa-award.');
        if (customIcon.checkValidity()) {
            iconInput.value = icon;
            preview.className = icon;
            previewLabel.textContent = icon;
            picker.querySelectorAll('.skill-icon-option').forEach(function (option) {
                option.classList.remove('is-selected');
                option.setAttribute('aria-checked', 'false');
            });
        }
    });
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (!form.reportValidity()) return;
        button.disabled = true;
        error.style.display = 'none';
        button.textContent = 'Saving...';
        fetch(isEditing
            ? 'pages/instructor/training-subpage/ajax/update-skill.php'
            : 'pages/instructor/training-subpage/ajax/add-skill.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: new FormData(form)
        }).then(function (response) {
            return response.json().then(function (result) {
                if (!response.ok || !result.success) throw new Error(result.message || 'Unable to save skill.');
                return result;
            });
        }).then(function (result) {
            if (window.showToast) window.showToast(result.message || 'Skill saved.', 'success');
            window.location.href = '?page=instructor/training&tab=skills';
        }).catch(function (saveError) {
            error.textContent = saveError.message || 'Unable to save skill.';
            error.style.display = '';
            if (window.showToast) window.showToast(error.textContent, 'error');
        }).finally(function () {
            button.disabled = false;
            button.textContent = isEditing ? 'Save Skill Changes' : 'Add Skill';
        });
    });
})();
</script>
<?php endif; ?>
