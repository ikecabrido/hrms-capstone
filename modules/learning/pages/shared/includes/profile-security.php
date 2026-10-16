<?php
/**
 * Security block: previous sign-in and the list of active sessions, with a
 * "sign out other sessions" action. Expects $profileData.
 */
$previousLogin = $profileData['previous_login'];
$previousLoginLabel = 'This is your first recorded sign-in.';
if (!empty($previousLogin)) {
    $ts = strtotime((string) $previousLogin);
    if ($ts !== false) {
        $previousLoginLabel = date('M j, Y \a\t g:i A', $ts);
    }
}

$sessions = $profileData['sessions'];
$otherSessionCount = 0;
foreach ($sessions as $s) {
    if (empty($s['is_current'])) {
        $otherSessionCount++;
    }
}
?>
<div class="mode-card">
    <h3 class="pfl-section-title">
        <i class="fas fa-shield-halved" aria-hidden="true"></i> Security
        <button type="button" id="profile-signout-others" class="pfl-btn pfl-btn--ghost pfl-count"
                <?= $otherSessionCount === 0 ? 'disabled' : '' ?>>
            <i class="fas fa-right-from-bracket" aria-hidden="true"></i> Sign out other sessions
        </button>
    </h3>

    <div class="pfl-last-login">
        <i class="fas fa-clock-rotate-left" aria-hidden="true"></i>
        <span>Previous sign-in: <strong><?= htmlspecialchars($previousLoginLabel) ?></strong></span>
    </div>

    <div class="pfl-security" id="profile-session-list" data-other-count="<?= (int) $otherSessionCount ?>">
        <?php if (empty($sessions)): ?>
            <p class="pfl-empty">No active sessions were found.</p>
        <?php else: ?>
            <?php foreach ($sessions as $session):
                $isCurrent = !empty($session['is_current']);
                $device = (string) ($session['device_label'] ?? 'Unknown device');
                $isMobile = (stripos($device, 'Android') !== false || stripos($device, 'iOS') !== false);
                $lastSeenTs = strtotime((string) ($session['last_seen_at'] ?? ''));
                $lastSeenLabel = $lastSeenTs !== false ? date('M j, g:i A', $lastSeenTs) : '—';
                $ip = (string) ($session['ip_address'] ?? '');
            ?>
                <div class="pfl-session"<?= $isCurrent ? ' data-current="1"' : '' ?>>
                    <div class="pfl-session-icon">
                        <i class="fas <?= $isMobile ? 'fa-mobile-screen-button' : 'fa-display' ?>" aria-hidden="true"></i>
                    </div>
                    <div class="pfl-session-main">
                        <div class="pfl-session-device"><?= htmlspecialchars($device) ?></div>
                        <div class="pfl-session-meta">
                            Active <?= htmlspecialchars($lastSeenLabel) ?><?= $ip !== '' ? ' · ' . htmlspecialchars($ip) : '' ?>
                        </div>
                    </div>
                    <?php if ($isCurrent): ?>
                        <span class="pfl-tag pfl-tag--current">This device</span>
                    <?php else: ?>
                        <span class="pfl-tag pfl-tag--idle">Active</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
