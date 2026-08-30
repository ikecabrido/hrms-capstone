<?php
include_once __DIR__ . '/../../../classes/Employee.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

$employeeClass = new Employee();
$learnerId = (int) ($employeeClass->getEmployeeId() ?? 0);

$videoConferenceId = (int) ($_GET['video_conference_id'] ?? 0);
$conference = null;

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Fetch video conference with instructor and linked course/program
    $stmt = $pdo->prepare("
        SELECT vc.id, vc.title, vc.platform, vc.meeting_link, vc.scheduled_at, vc.duration_minutes,
               vc.status, vc.created_at,
               CONCAT(emp.first_name, ' ', emp.last_name) AS instructor_name,
               vc.course_id, vc.program_id,
               c.title AS course_title,
               p.title AS program_title
        FROM ld_video_conference vc
        LEFT JOIN em_employees emp ON emp.employee_id = vc.instructor_id
        LEFT JOIN ld_course c ON c.id = vc.course_id
        LEFT JOIN ld_program p ON p.id = vc.program_id
        WHERE vc.id = :id AND vc.status = 'scheduled'
        LIMIT 1
    ");
    $stmt->execute([':id' => $videoConferenceId]);
    $conference = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($conference) {
        // Count attendees
        $attendStmt = $pdo->prepare("SELECT COUNT(*) FROM ld_conference_attendance WHERE video_conference_id = :vcid");
        $attendStmt->execute([':vcid' => $videoConferenceId]);
        $attendeeCount = (int) $attendStmt->fetchColumn();

        // Check if learner has joined
        $checkStmt = $pdo->prepare("SELECT attended FROM ld_conference_attendance WHERE video_conference_id = :vcid AND learner_id = :lid LIMIT 1");
        $checkStmt->execute([':vcid' => $videoConferenceId, ':lid' => $learnerId]);
        $attendance = $checkStmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    $conference = null;
}

if (!$conference) {
    echo '<div class="module-content"><div class="mode-card"><h2>Session Not Found</h2><p>The video conference you are looking for does not exist or is no longer scheduled.</p>';
    echo '</div></div>';
    return;
}

$scheduledAt = new DateTime($conference['scheduled_at']);
$now = new DateTime();
$isPast = $now > $scheduledAt;
$platformDisplay = ucfirst(str_replace('_', ' ', $conference['platform']));
$platformColor = match($conference['platform']) {
    'zoom'        => '#2D8CFF',
    'google_meet' => '#00897B',
    default       => '#6c757d',
};
?>

<div class="module-content">
    <div style="margin-bottom:1.5rem;">
        <a href="?page=learner/catalog" style="display:inline-flex; align-items:center; gap:0.4rem; padding:0.5rem 1rem; border:1px solid rgba(32,0,130,0.15); border-radius:8px; background:var(--surface, #fff); color:var(--text); font-size:0.85rem; font-weight:600; text-decoration:none; transition:all 0.2s;">
            <i class="fas fa-arrow-left"></i> Back to Catalog
        </a>
    </div>

    <!-- Video Conference Header -->
    <div class="mode-card" style="margin-bottom:1.5rem;">
        <div style="display:flex; gap:2rem; align-items:flex-start; flex-wrap:wrap;">
            <div style="width:80px; height:80px; border-radius:14px; background:linear-gradient(135deg, rgba(185,28,28,0.85), rgba(239,68,68,0.7)); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i class="fas fa-video" style="color:#fff; font-size:2rem;"></i>
            </div>
            <div style="flex:1; min-width:300px;">
                <div style="display:flex; gap:0.5rem; margin-bottom:0.75rem; flex-wrap:wrap;">
                    <span class="pill" style="background:linear-gradient(135deg, rgba(185,28,28,0.85), rgba(239,68,68,0.7)); color:#fff;">Video Conference</span>
                    <span class="pill" style="background:<?= $platformColor ?>; color:#fff;"><?= htmlspecialchars($platformDisplay) ?></span>
                    <?php if ($isPast): ?>
                        <span class="pill" style="background:#6c757d; color:#fff;">Past Session</span>
                    <?php else: ?>
                        <span class="pill" style="background:#d4edda; color:#155724;">Scheduled</span>
                    <?php endif; ?>
                </div>
                <h1 style="margin:0 0 0.75rem 0; font-size:1.8rem; color:#222;"><?= htmlspecialchars($conference['title']) ?></h1>

                <div style="display:flex; gap:1.5rem; flex-wrap:wrap; margin-bottom:1.5rem;">
                    <?php if (!empty($conference['instructor_name'])): ?>
                        <div style="display:flex; align-items:center; gap:0.5rem;">
                            <i class="fas fa-user-tie" style="color:var(--primary);"></i>
                            <div>
                                <div style="font-size:0.75rem; color:#999; text-transform:uppercase;">Host</div>
                                <div style="font-weight:600; color:#333;"><?= htmlspecialchars($conference['instructor_name']) ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div style="display:flex; align-items:center; gap:0.5rem;">
                        <i class="fas fa-calendar-alt" style="color:#6c757d;"></i>
                        <div>
                            <div style="font-size:0.75rem; color:#999; text-transform:uppercase;">Date & Time</div>
                            <div style="font-weight:600; color:#333;"><?= $scheduledAt->format('l, M j, Y \a\t g:i A') ?></div>
                        </div>
                    </div>
                    <?php if ($conference['duration_minutes']): ?>
                        <div style="display:flex; align-items:center; gap:0.5rem;">
                            <i class="fas fa-hourglass-half" style="color:#6c757d;"></i>
                            <div>
                                <div style="font-size:0.75rem; color:#999; text-transform:uppercase;">Duration</div>
                                <div style="font-weight:600; color:#333;"><?= $conference['duration_minutes'] ?> minutes</div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($attendeeCount)): ?>
                        <div style="display:flex; align-items:center; gap:0.5rem;">
                            <i class="fas fa-users" style="color:#6c757d;"></i>
                            <div>
                                <div style="font-size:0.75rem; color:#999; text-transform:uppercase;">Attendees</div>
                                <div style="font-weight:600; color:#333;"><?= $attendeeCount ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Linked content -->
                <?php if ($conference['course_title'] || $conference['program_title']): ?>
                    <div style="margin-bottom:1.5rem;">
                        <div style="font-size:0.85rem; font-weight:600; color:#333; margin-bottom:0.5rem;">Associated With</div>
                        <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                            <?php if ($conference['course_title']): ?>
                                
                            <?php endif; ?>
                            <?php if ($conference['program_title']): ?>
                                
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Join / Status -->
                <div style="margin-top:1.5rem;">
                    <?php if ($isPast): ?>
                        <div style="padding:1rem 1.5rem; background:rgba(156,163,175,0.08); border-radius:10px; border:1px solid rgba(156,163,175,0.2); display:flex; align-items:center; gap:0.75rem;">
                            <div style="width:40px; height:40px; border-radius:10px; background:rgba(156,163,175,0.15); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                <i class="fas fa-clock" style="color:#6c757d; font-size:1rem;"></i>
                            </div>
                            <div>
                                <div style="font-weight:700; color:#6c757d; font-size:0.95rem;">Session Ended</div>
                                <div style="font-size:0.82rem; color:#9ca3af;">This live session has already concluded.</div>
                            </div>
                        </div>
                    <?php elseif ($conference['meeting_link']): ?>
                        <div style="display:flex; align-items:center; gap:1rem; flex-wrap:wrap;">
                            <a href="<?= htmlspecialchars($conference['meeting_link']) ?>" target="_blank" rel="noopener" id="join-session-btn"
                               style="display:inline-flex; align-items:center; gap:0.6rem; padding:0.85rem 1.75rem; background:<?= $platformColor ?>; color:#fff; border:none; border-radius:10px; cursor:pointer; font-weight:700; font-size:0.95rem; text-decoration:none; box-shadow:0 4px 15px <?= $platformColor ?>55; transition:all 0.2s;">
                                <i class="fas fa-video" style="font-size:1.1rem;"></i> Join Session
                            </a>
                            <?php if (!empty($attendance) && $attendance['attended']): ?>
                                <span style="display:inline-flex; align-items:center; gap:0.4rem; padding:0.5rem 1rem; background:rgba(16,185,129,0.1); color:#059669; border-radius:8px; font-weight:600; font-size:0.85rem;">
                                    <i class="fas fa-check-circle"></i> Attendance recorded
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div style="padding:1rem 1.5rem; background:rgba(245,158,11,0.08); border-radius:10px; border:1px solid rgba(245,158,11,0.25); display:flex; align-items:center; gap:0.75rem;">
                            <div style="width:40px; height:40px; border-radius:10px; background:rgba(245,158,11,0.15); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                <i class="fas fa-exclamation-triangle" style="color:#f59e0b; font-size:1rem;"></i>
                            </div>
                            <div>
                                <div style="font-weight:700; color:#92400e; font-size:0.95rem;">Link Not Available Yet</div>
                                <div style="font-size:0.82rem; color:#92400e;">The meeting link will be available closer to the scheduled time.</div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Session Details -->
    <div class="mode-card">
        <h3 style="margin-bottom:1rem;"><i class="fas fa-info-circle" style="color:var(--primary); margin-right:0.5rem;"></i>Session Details</h3>
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:1rem;">
            <div style="background:var(--bg-subtle); border-radius:10px; padding:1.25rem; text-align:center; border:1px solid rgba(32,0,130,0.06);">
                <i class="fas fa-globe" style="font-size:1.4rem; color:<?= $platformColor ?>; margin-bottom:0.5rem; display:block;"></i>
                <div style="font-size:0.72rem; color:var(--muted); text-transform:uppercase; letter-spacing:0.06em; font-weight:600;">Platform</div>
                <div style="font-weight:700; color:var(--text); margin-top:0.25rem;"><?= htmlspecialchars($platformDisplay) ?></div>
            </div>
            <div style="background:var(--bg-subtle); border-radius:10px; padding:1.25rem; text-align:center; border:1px solid rgba(32,0,130,0.06);">
                <i class="fas fa-clock" style="font-size:1.4rem; color:var(--primary); margin-bottom:0.5rem; display:block;"></i>
                <div style="font-size:0.72rem; color:var(--muted); text-transform:uppercase; letter-spacing:0.06em; font-weight:600;">Time</div>
                <div style="font-weight:700; color:var(--text); margin-top:0.25rem;"><?= $scheduledAt->format('g:i A') ?></div>
            </div>
            <div style="background:var(--bg-subtle); border-radius:10px; padding:1.25rem; text-align:center; border:1px solid rgba(32,0,130,0.06);">
                <i class="fas fa-calendar-day" style="font-size:1.4rem; color:var(--primary); margin-bottom:0.5rem; display:block;"></i>
                <div style="font-size:0.72rem; color:var(--muted); text-transform:uppercase; letter-spacing:0.06em; font-weight:600;">Date</div>
                <div style="font-weight:700; color:var(--text); margin-top:0.25rem;"><?= $scheduledAt->format('M j, Y') ?></div>
            </div>
            <?php if ($conference['duration_minutes']): ?>
                <div style="background:var(--bg-subtle); border-radius:10px; padding:1.25rem; text-align:center; border:1px solid rgba(32,0,130,0.06);">
                    <i class="fas fa-hourglass-half" style="font-size:1.4rem; color:var(--primary); margin-bottom:0.5rem; display:block;"></i>
                    <div style="font-size:0.72rem; color:var(--muted); text-transform:uppercase; letter-spacing:0.06em; font-weight:600;">Duration</div>
                    <div style="font-weight:700; color:var(--text); margin-top:0.25rem;"><?= $conference['duration_minutes'] ?> min</div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Countdown Script -->
    <?php if (!$isPast && $conference['scheduled_at']): ?>
    <script>
    (function() {
        var scheduled = new Date('<?= $conference['scheduled_at'] ?>'.replace(' ', 'T'));
        var btn = document.getElementById('join-session-btn');
        if (!btn) return;
        function updateCountdown() {
            var diff = scheduled.getTime() - Date.now();
            if (diff <= 0) {
                btn.innerHTML = '<i class=\'fas fa-video\'></i> Join Session Now';
                btn.style.animation = 'pulse 2s infinite';
                return;
            }
            var h = Math.floor(diff / 3600000);
            var m = Math.floor((diff % 3600000) / 60000);
            var s = Math.floor((diff % 60000) / 1000);
            var parts = [];
            if (h > 0) parts.push(h + 'h');
            if (m > 0 || h > 0) parts.push(m + 'm');
            parts.push(s + 's');
            btn.innerHTML = '<i class=\'fas fa-clock\'></i> Starts in ' + parts.join(' ');
        }
        updateCountdown();
        setInterval(updateCountdown, 1000);
    })();
    </script>
    <style>@keyframes pulse{0%,100%{opacity:1}50%{opacity:.7}}</style>
    <?php endif; ?>

    <!-- Join Script -->
    <script>
    (function() {
        var btn = document.getElementById('join-session-btn');
        if (!btn) return;
        btn.addEventListener('click', function(e) {
            var vcId = <?= (int)($conference['id'] ?? 0) ?>;
            fetch('pages/learner/catalog-subpage/ajax/enrollment/join-conference.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ video_conference_id: vcId })
            }).then(function(r) { return r.json(); }).then(function(data) {
                if (data.success && data.meeting_link) {
                    window.open(data.meeting_link, '_blank');
                    if (typeof showToast === 'function') showToast('Attendance recorded!', 'success');
                }
            }).catch(function() {});
        });
    })();
    </script>
</div>
