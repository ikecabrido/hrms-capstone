<?php
/**
 * Profile completion meter. Expects $profileData.
 * Each criterion carries data-criterion / data-done so the shared script can
 * recompute the meter after a bio or contact change without a page reload.
 *
 * Once every criterion is met there is nothing left to ask for, so the card is not
 * rendered at all — the script hides it in place for the change that reaches 100%
 * without a reload.
 */
$completion = $profileData['completion'];

if ((int) $completion['percent'] >= 100) {
    return;
}
?>
<div class="mode-card" id="profile-completion-card" style="margin-bottom:1.5rem;">
    <div class="pfl-completion-head">
        <h3><i class="fas fa-circle-check" aria-hidden="true"></i> Profile completion</h3>
        <span class="pfl-completion-pct" id="profile-completion-pct"><?= (int) $completion['percent'] ?>%</span>
    </div>
    <div class="pfl-completion-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) $completion['percent'] ?>" aria-label="Profile completion">
        <span id="profile-completion-bar" style="width:<?= (int) $completion['percent'] ?>%;"></span>
    </div>
    <ul class="pfl-completion-list" id="profile-completion-list">
        <?php foreach ($completion['items'] as $item): ?>
            <li class="pfl-completion-item<?= $item['done'] ? ' is-done' : '' ?>"
                id="pfl-crit-<?= htmlspecialchars($item['key'], ENT_QUOTES, 'UTF-8') ?>"
                data-criterion="<?= htmlspecialchars($item['key'], ENT_QUOTES, 'UTF-8') ?>"
                data-done="<?= $item['done'] ? '1' : '0' ?>">
                <i class="fas <?= $item['done'] ? 'fa-check-circle' : 'fa-circle' ?>" aria-hidden="true"></i>
                <?= htmlspecialchars($item['label']) ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
