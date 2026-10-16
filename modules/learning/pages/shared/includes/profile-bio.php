<?php
/**
 * Editable "about me" bio. Expects $profileData.
 */
$bio = $profileData['bio'];
?>
<div class="mode-card" style="margin-bottom:1.5rem;">
    <div class="pfl-bio-edit">
        <h3 class="pfl-section-title" style="margin:0;">
            <i class="fas fa-quote-left" aria-hidden="true"></i> Bio
        </h3>
        <button type="button" id="profile-bio-edit-btn" class="pfl-btn pfl-btn--ghost"
                data-bio="<?= htmlspecialchars($bio, ENT_QUOTES, 'UTF-8') ?>">
            <i class="fas fa-pen" aria-hidden="true"></i> <?= $bio === '' ? 'Add bio' : 'Edit' ?>
        </button>
    </div>
    <div id="profile-bio-view" style="padding-top:0.75rem;">
        <?php if ($bio !== ''): ?>
            <p class="pfl-bio-text"><?= htmlspecialchars($bio, ENT_QUOTES, 'UTF-8') ?></p>
        <?php else: ?>
            <p class="pfl-bio-empty">No bio yet. Tell others a little about yourself.</p>
        <?php endif; ?>
    </div>
    <div id="profile-bio-edit" style="display:none; padding-top:0.75rem;">
        <textarea id="profile-bio-textarea" class="pfl-input" maxlength="1000" rows="4"
                  placeholder="Write a short bio (max 1000 characters)..."><?= htmlspecialchars($bio, ENT_QUOTES, 'UTF-8') ?></textarea>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:0.5rem;">
            <span class="pfl-counter" id="profile-bio-count"><?= mb_strlen($bio) ?> / 1000</span>
            <div style="display:flex; gap:0.5rem;">
                <button type="button" id="profile-bio-cancel" class="pfl-btn pfl-btn--ghost">Cancel</button>
                <button type="button" id="profile-bio-save" class="pfl-btn pfl-btn--primary">Save</button>
            </div>
        </div>
    </div>
</div>
