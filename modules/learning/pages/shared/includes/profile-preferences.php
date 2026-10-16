<?php
/**
 * Display preferences card: how this employee's lists and card grids open.
 *
 * Shared by the admin, instructor and learner profile pages, and the only place
 * these two settings are edited — the grid/list toggles on the catalog and the
 * e-learning pages write the same store, so a choice made there shows up here.
 *
 * Expects $pdo and $profileId from profile-data.php. The current values are
 * rendered server-side so the card is correct before (and without) JavaScript;
 * the state is then re-read from the shared endpoint, and every change is posted
 * back to it.
 */

require_once dirname(__DIR__, 3) . '/classes/displaypreference.php';

$displayPrefs = ['page_size' => DisplayPreference::modulePageSize(), 'view_mode' => 'grid'];
if ($pdo && $profileId > 0) {
    $displayPrefs = DisplayPreference::effective($pdo, $profileId);
}

$pageSizeOptions = DisplayPreference::PAGE_SIZES;
$viewModeLabels = ['grid' => 'Grid', 'list' => 'List'];
$viewModeIcons = ['grid' => 'fa-th', 'list' => 'fa-list'];
$isListView = $displayPrefs['view_mode'] === 'list';
?>
<div class="mode-card" style="margin-bottom:1.5rem;" id="pfl-prefs">
    <h3 class="pfl-section-title"><i class="fas fa-sliders-h" aria-hidden="true"></i> Preferences</h3>

    <div class="pfl-prefs">
        <div class="pfl-pref">
            <div class="pfl-pref-main">
                <div class="pfl-pref-label">Rows per page</div>
                <div class="pfl-pref-hint">How many cards a list shows before it paginates.</div>
            </div>
            <select class="pfl-input pfl-select" data-display-pref="page_size" aria-label="Rows per page">
                <?php foreach ($pageSizeOptions as $size): ?>
                    <option value="<?= (int) $size ?>"<?= (int) $size === (int) $displayPrefs['page_size'] ? ' selected' : '' ?>><?= (int) $size ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="pfl-pref">
            <div class="pfl-pref-main">
                <div class="pfl-pref-label">Default view</div>
                <div class="pfl-pref-hint">Whether catalog and list cards open as a grid or a list.</div>
            </div>
            <div class="pfl-view-toggle" role="group" aria-label="Default view">
                <?php foreach (DisplayPreference::VIEW_MODES as $mode): ?>
                    <button type="button"
                            class="pfl-view-btn<?= $displayPrefs['view_mode'] === $mode ? ' is-active' : '' ?>"
                            data-display-pref-view="<?= htmlspecialchars($mode) ?>"
                            aria-pressed="<?= $displayPrefs['view_mode'] === $mode ? 'true' : 'false' ?>">
                        <i class="fas <?= htmlspecialchars($viewModeIcons[$mode]) ?>" aria-hidden="true"></i>
                        <?= htmlspecialchars($viewModeLabels[$mode]) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <p class="pfl-hint" id="pfl-prefs-status" role="status" aria-live="polite">
            Saved to your account, so every list and card grid in the module follows these.
        </p>
    </div>
</div>
<script>
(function () {
    'use strict';

    var status = document.getElementById('pfl-prefs-status');
    var panel = document.getElementById('pfl-prefs');
    if (!status || !panel) return;

    function setStatus(message, isError) {
        status.textContent = message;
        status.style.color = isError ? '#b91c1c' : '';
    }

    // The card is already correct as rendered; this asks the endpoint for the
    // stored value so a stale page (or a change made elsewhere in the module)
    // cannot leave the controls showing something the module will not do.
    fetch('pages/shared/ajax/get-display-preference.php', {
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (!data || data.success !== true || !data.preferences) return;

            var prefs = data.preferences;
            window.LD_DISPLAY_PREFS = prefs;
            window.LD_DEFAULT_PAGE_SIZE = prefs.page_size;

            var select = panel.querySelector('[data-display-pref="page_size"]');
            if (select && select.value !== String(prefs.page_size)) {
                select.value = String(prefs.page_size);
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }

            panel.querySelectorAll('[data-display-pref-view]').forEach(function (button) {
                var active = button.dataset.displayPrefView === prefs.view_mode;
                button.classList.toggle('is-active', active);
                button.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
        })
        .catch(function () {
            setStatus('Could not reach the server — showing the last saved values.', true);
        });
})();
</script>
