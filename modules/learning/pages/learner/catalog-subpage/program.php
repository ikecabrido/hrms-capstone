<?php
include_once __DIR__ . '/../../../classes/Employee.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

$employeeClass = new Employee();
$learnerId = (int) ($employeeClass->getEmployeeId() ?? 0);

$programId = (int) ($_GET['program_id'] ?? 0);
$program = null;
$courses = [];
$videoConfs = [];

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Fetch program with instructor
    $stmt = $pdo->prepare("
        SELECT p.id, p.title, p.description, p.status, p.created_at,
               CONCAT(emp.first_name, ' ', emp.last_name) AS instructor_name
        FROM ld_program p
        LEFT JOIN em_employees emp ON emp.employee_id = p.instructor_id
        WHERE p.id = :id AND p.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([':id' => $programId]);
    $program = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($program) {
        // Get courses linked via learning_path_item (item_type = 'course' in paths that reference this program)
        // Or courses linked to this program via video_conference
        $confStmt = $pdo->prepare("
            SELECT vc.id AS conf_id, vc.title, vc.scheduled_at, vc.duration_minutes, vc.platform, vc.meeting_link,
                   c.id AS course_id, c.title AS course_title
            FROM ld_video_conference vc
            LEFT JOIN ld_course c ON c.id = vc.course_id
            WHERE vc.program_id = :pid AND vc.status = 'scheduled'
            ORDER BY vc.scheduled_at ASC
        ");
        $confStmt->execute([':pid' => $programId]);
        $videoConfs = $confStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    $program = null;
}

if (!$program) {
    echo '<div class="module-content"><div class="mode-card"><h2>Program Not Found</h2><p>The program you are looking for does not exist or is no longer available.</p>';
    echo '</div></div>';
    return;
}
?>

<div class="module-content">
    <div style="margin-bottom:1.5rem;">
        
    </div>

    <!-- Program Header -->
    <div class="mode-card" style="margin-bottom:1.5rem;">
        <div style="display:flex; gap:2rem; align-items:flex-start; flex-wrap:wrap;">
            <div style="width:80px; height:80px; border-radius:14px; background:linear-gradient(135deg, rgba(16,120,40,0.85), rgba(46,184,92,0.7)); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i class="fas fa-layer-group" style="color:#fff; font-size:2rem;"></i>
            </div>
            <div style="flex:1; min-width:300px;">
                <div style="display:flex; gap:0.5rem; margin-bottom:0.75rem; flex-wrap:wrap;">
                    <span class="pill" style="background:linear-gradient(135deg, rgba(16,120,40,0.85), rgba(46,184,92,0.7)); color:#fff;">Program</span>
                    <span class="pill" style="background:#d4edda; color:#155724;">Active</span>
                </div>
                <h1 style="margin:0 0 0.75rem 0; font-size:1.8rem; color:#222;"><?= htmlspecialchars($program['title']) ?></h1>
                <?php if ($program['description']): ?>
                    <p style="color:#555; line-height:1.7; margin:0 0 1.5rem 0;"><?= nl2br(htmlspecialchars($program['description'])) ?></p>
                <?php endif; ?>

                <div style="display:flex; gap:1.5rem; flex-wrap:wrap;">
                    <?php if (!empty($program['instructor_name'])): ?>
                        <div style="display:flex; align-items:center; gap:0.5rem;">
                            <i class="fas fa-user-tie" style="color:var(--primary);"></i>
                            <div>
                                <div style="font-size:0.75rem; color:#999; text-transform:uppercase;">Coordinator</div>
                                <div style="font-weight:600; color:#333;"><?= htmlspecialchars($program['instructor_name']) ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div style="display:flex; align-items:center; gap:0.5rem;">
                        <i class="fas fa-calendar" style="color:#6c757d;"></i>
                        <div>
                            <div style="font-size:0.75rem; color:#999; text-transform:uppercase;">Created</div>
                            <div style="font-weight:600; color:#333;"><?= date('M j, Y', strtotime($program['created_at'])) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scheduled Sessions -->
    <div class="mode-card">
        <h2 style="margin-bottom:0.5rem;">Scheduled Sessions</h2>
        <p style="color:#666; margin:0 0 1.5rem 0;">Upcoming video conferences and live sessions for this program.</p>

        <?php if (empty($videoConfs)): ?>
            <div style="text-align:center; padding:3rem; color:#999;">
                <i class="fas fa-video" style="font-size:2rem; margin-bottom:0.75rem; display:block;"></i>
                No scheduled sessions for this program yet.
            </div>
        <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:1rem;">
                <?php foreach ($videoConfs as $conf): ?>
                    <div style="background:#f9f9f9; border:1px solid #e8e8e8; border-radius:10px; padding:1.25rem;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem;">
                            <div style="flex:1;">
                                <h3 style="margin:0 0 0.5rem 0; font-size:1rem; color:#222;">
                                    <i class="fas fa-video" style="color:#dc3545; margin-right:0.4rem;"></i>
                                    <?= htmlspecialchars($conf['title']) ?>
                                </h3>
                                <div style="display:flex; gap:1rem; font-size:0.85rem; color:#666; flex-wrap:wrap;">
                                    <span><i class="fas fa-calendar-alt" style="margin-right:4px;"></i><?= date('M j, Y g:i A', strtotime($conf['scheduled_at'])) ?></span>
                                    <?php if ($conf['duration_minutes']): ?>
                                        <span><i class="fas fa-hourglass-half" style="margin-right:4px;"></i><?= $conf['duration_minutes'] ?> min</span>
                                    <?php endif; ?>
                                    <span><i class="fas fa-globe" style="margin-right:4px;"></i><?= ucfirst(str_replace('_', ' ', $conf['platform'])) ?></span>
                                </div>
                            </div>
                            <?php if ($conf['meeting_link']): ?>
                                
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
