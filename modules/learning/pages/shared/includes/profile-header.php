<?php
/**
 * Profile header: avatar (with upload/remove), name, role and department.
 * Expects $profileData (see profile-data.php).
 */
$employee = $profileData['employee'];
$profileReadOnly = !empty($profileReadOnly);
$positionLabel = trim((string) ($employee['position_name'] ?? ''));
if ($positionLabel === '') {
    $positionLabel = $profileData['role_label'];
}
?>
<div class="pfl-header">
    <div class="pfl-header-inner">
        <div class="pfl-avatar-wrap">
            <div class="pfl-seal" aria-hidden="true">
                <svg class="pfl-seal-svg" viewBox="0 0 126 126" role="presentation" focusable="false">
                    <circle class="pfl-seal-ring" cx="63" cy="63" r="61.5" />
                    <circle class="pfl-seal-ring-inner" cx="63" cy="63" r="49.5" />
                </svg>
            </div>
            <div id="profile-avatar" class="pfl-avatar" data-initials="<?= htmlspecialchars($profileData['initials'], ENT_QUOTES, 'UTF-8') ?>">
                <?php if ($profileData['photo_url']): ?>
                    <img id="profile-avatar-img" src="<?= htmlspecialchars($profileData['photo_url'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($profileData['full_name'], ENT_QUOTES, 'UTF-8') ?>">
                <?php else: ?>
                    <span id="profile-avatar-initials"><?= htmlspecialchars($profileData['initials'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </div>
            <?php if (!$profileReadOnly): ?>
                <button type="button" id="profile-photo-btn" class="pfl-avatar-btn" title="Upload profile picture" aria-label="Upload profile picture">
                    <i class="fas fa-camera" aria-hidden="true"></i>
                </button>
                <input type="file" id="profile-photo-input" accept="image/jpeg,image/png,image/webp" style="display:none;">
            <?php endif; ?>
        </div>
        <div class="pfl-header-meta">
            <h1><?= htmlspecialchars($profileData['full_name']) ?></h1>
            <p class="pfl-header-role"><?= htmlspecialchars($positionLabel) ?></p>
            <?php if (!empty($employee['department_name'])): ?>
                <p class="pfl-header-sub"><?= htmlspecialchars($employee['department_name']) ?></p>
            <?php endif; ?>
            <?php if (!$profileReadOnly): ?>
                <div id="profile-photo-actions" class="pfl-photo-actions" style="display:<?= $profileData['photo_url'] ? 'inline-flex' : 'none' ?>;">
                    <button type="button" id="profile-photo-remove" class="pfl-ghost-btn">
                        <i class="fas fa-trash" aria-hidden="true"></i> Remove
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
