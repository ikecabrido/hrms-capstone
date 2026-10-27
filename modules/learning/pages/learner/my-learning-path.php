<?php
include_once __DIR__ . '/../../classes/Employee.php';
require_once dirname(__DIR__, 4) . '/database/db.php';

$employeeClass = new Employee();
$learnerId = (int) ($employeeClass->getEmployeeId() ?? 0);

$assignedPath = null;
$items = [];

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Get the path assigned to this learner
    $stmt = $pdo->prepare("
        SELECT lp.id, lp.title, lp.description, lp.status, lp.type, lp.is_public, lp.assigned_to, lp.instructor_id, lp.created_at,
               CONCAT(emp.first_name, ' ', emp.last_name) AS instructor_name,
               (SELECT COUNT(*) FROM ld_learning_path_item lpi WHERE lpi.learning_path_id = lp.id) AS item_count
        FROM ld_learning_path lp
        LEFT JOIN em_employees emp ON emp.employee_id = lp.instructor_id
        WHERE lp.assigned_to = :lid AND lp.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([':lid' => $learnerId]);
    $assignedPath = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($assignedPath) {
        $itemStmt = $pdo->prepare("
            SELECT lpi.id, lpi.item_type, lpi.reference_id, lpi.order_index, lpi.status AS item_status
            FROM ld_learning_path_item lpi
            WHERE lpi.learning_path_id = :lpid AND lpi.status = 'active'
            ORDER BY lpi.order_index ASC, lpi.id ASC
        ");
        $itemStmt->execute([':lpid' => $assignedPath['id']]);
        $rawItems = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

        $tableMap = [
            'course'           => 'ld_course',
            'module'           => 'ld_module',
            'lesson'           => 'ld_lesson',
            'quiz'             => 'ld_quiz',
            'evaluation'       => 'ld_evaluation',
            'program'          => 'ld_program',
            'video-conference' => 'ld_video_conference',
        ];

        $typeLabels = [
            'course'           => 'Course',
            'module'           => 'Module',
            'lesson'           => 'Lesson',
            'quiz'             => 'Quiz',
            'evaluation'       => 'Evaluation',
            'program'          => 'Program',
            'video-conference' => 'Online Training',
        ];

        $typeIcons = [
            'course'           => 'fa-graduation-cap',
            'module'           => 'fa-cube',
            'lesson'           => 'fa-book-open',
            'quiz'             => 'fa-question-circle',
            'evaluation'       => 'fa-clipboard-check',
            'program'          => 'fa-layer-group',
            'video-conference' => 'fa-video',
        ];

        foreach ($rawItems as $item) {
            $table = $tableMap[$item['item_type']] ?? null;
            $item['label'] = $typeLabels[$item['item_type']] ?? ucfirst($item['item_type']);
            $item['icon'] = $typeIcons[$item['item_type']] ?? 'fa-puzzle-piece';
            $item['title'] = 'Item #' . $item['reference_id'];

            if ($table) {
                $titleStmt = $pdo->prepare("SELECT title FROM {$table} WHERE id = :rid LIMIT 1");
                $titleStmt->execute([':rid' => $item['reference_id']]);
                $fetched = $titleStmt->fetchColumn();
                if ($fetched) {
                    $item['title'] = $fetched;
                }
            }

            switch ($item['item_type']) {
                case 'course':
                    $item['link'] = '?page=learner/study-subpage/course&course_id=' . $item['reference_id'];
                    break;
                case 'program':
                    $item['link'] = '?page=learner/catalog-subpage/program&program_id=' . $item['reference_id'];
                    break;
                case 'video-conference':
                    $item['link'] = '?page=learner/catalog-subpage/video-conference&back=learner/my-learning-path&video_conference_id=' . $item['reference_id'];
                    break;
                default:
                    $item['link'] = '';
            }

            $items[] = $item;
        }
    }
} catch (Throwable $e) {
    // Keep going with empty data
}
?>

<div class="module-content">
    <div class="toolbar" style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
        <a href="?page=learner/learner-home" style="display:inline-flex; align-items:center; gap:0.4rem; font-size:0.85rem; color:var(--muted); text-decoration:none;"><i class="fas fa-arrow-left"></i> Back to Home</a>
        <div style="flex:1;"></div>
        <?php if ($assignedPath): ?>
            <a href="?page=learner/catalog-subpage/learning-path&learning_path_id=<?= $assignedPath['id'] ?>" style="display:inline-flex; align-items:center; gap:0.4rem; padding:0.45rem 0.9rem; background:var(--primary); color:#fff; border-radius:999px; text-decoration:none; font-size:0.8rem; font-weight:700;">
                <i class="fas fa-eye"></i> View Full Path
            </a>
        <?php endif; ?>
    </div>

    <?php if ($assignedPath): ?>
        <!-- Assigned Path Header -->
        <div class="mode-card" style="margin-bottom:1.5rem;">
            <div style="display:flex; gap:1.5rem; align-items:flex-start; flex-wrap:wrap;">
                <div style="width:72px; height:72px; border-radius:14px; background:linear-gradient(135deg, rgba(32,0,130,0.9), rgba(91,85,255,0.7)); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <i class="fas fa-route" style="color:#fff; font-size:1.8rem;"></i>
                </div>
                <div style="flex:1; min-width:280px;">
                    <div style="display:flex; gap:0.5rem; margin-bottom:0.6rem; flex-wrap:wrap;">
                        <span class="pill" style="background:linear-gradient(135deg, rgba(32,0,130,0.85), rgba(91,85,255,0.7)); color:#fff;">My Learning Path</span>
                        <span class="pill" style="background:rgba(16,185,129,0.15); color:#10b981; border:1px solid rgba(16,185,129,0.2);">Active</span>
                    </div>
                    <h1 style="margin:0 0 0.6rem 0; font-size:1.6rem; color:var(--text);"><?= htmlspecialchars($assignedPath['title']) ?></h1>
                    <?php if (!empty($assignedPath['description'])): ?>
                        <p style="color:rgba(32,0,130,0.55); line-height:1.6; margin:0 0 1rem 0; max-width:680px;"><?= nl2br(htmlspecialchars($assignedPath['description'])) ?></p>
                    <?php endif; ?>
                    <div style="display:flex; gap:1.5rem; flex-wrap:wrap; align-items:center;">
                        <?php if (!empty($assignedPath['instructor_name'])): ?>
                            <div style="display:flex; align-items:center; gap:0.4rem;">
                                <i class="fas fa-user-tie" style="color:var(--primary);"></i>
                                <span style="font-size:0.85rem; color:rgba(32,0,130,0.5);">Designed by <strong style="color:var(--text);"><?= htmlspecialchars($assignedPath['instructor_name']) ?></strong></span>
                            </div>
                        <?php endif; ?>
                        <div style="display:flex; align-items:center; gap:0.4rem;">
                            <i class="fas fa-list-ol" style="color:var(--primary);"></i>
                            <span style="font-size:0.85rem; color:rgba(32,0,130,0.5);"><strong style="color:var(--text);"><?= count($items) ?></strong> step<?= count($items) !== 1 ? 's' : '' ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Path Steps -->
        <div class="mode-card">
            <h2 style="margin-bottom:0.4rem;">Your Path Steps</h2>
            <p style="color:rgba(32,0,130,0.45); margin:0 0 1.25rem 0; font-size:0.88rem;">Follow these steps in order to complete your assigned learning path.</p>

            <?php if (empty($items)): ?>
                <div style="text-align:center; padding:3rem; color:rgba(32,0,130,0.4);">
                    <i class="fas fa-route" style="font-size:2rem; display:block; margin-bottom:0.75rem;"></i>
                    <p style="margin:0; font-size:0.95rem;">Your learning path has no steps yet. Check back later or contact your instructor.</p>
                </div>
            <?php else: ?>
                <div style="position:relative; padding-left:2.25rem;">
                    <div style="position:absolute; left:18px; top:0; bottom:0; width:2px; background:rgba(32,0,130,0.1);"></div>

                    <?php foreach ($items as $idx => $item): ?>
                        <?php
                        $isComplete = ($item['item_type'] === 'course')
                            ? (function() use ($pdo, $learnerId, $item) {
                                $stmt = $pdo->prepare("SELECT status FROM ld_enrollment WHERE learner_id = :lid AND course_id = :cid LIMIT 1");
                                $stmt->execute([':lid' => $learnerId, ':cid' => $item['reference_id']]);
                                $s = $stmt->fetchColumn();
                                return $s === 'completed';
                            })()
                            : false;
                        ?>
                        <div style="position:relative; margin-bottom:1.25rem;">
                            <div style="position:absolute; left:-2.25rem; top:0; width:38px; height:38px; border-radius:50%; background:<?= $isComplete ? 'var(--primary)' : 'var(--primary)' ?>; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.85rem; z-index:1; border:3px solid <?= $isComplete ? '#e8e4f0' : 'var(--primary)' ?>;">
                                <?= $isComplete ? '<i class="fas fa-check" style="font-size:0.75rem;"></i>' : ($idx + 1) ?>
                            </div>
                            <div style="background:var(--bg-subtle); border:1px solid var(--border); border-radius:10px; padding:1rem 1.25rem; margin-left:0.5rem; <?= $isComplete ? 'opacity:0.6;' : '' ?>">
                                <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
                                    <div style="width:36px; height:36px; border-radius:8px; background:<?= $isComplete ? 'rgba(32,0,130,0.08)' : 'linear-gradient(135deg, rgba(99,102,241,0.15), rgba(139,92,246,0.1))' ?>; color:<?= $isComplete ? 'var(--primary)' : 'var(--primary)' ?>; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                        <i class="fas <?= htmlspecialchars($item['icon']) ?>" style="font-size:0.95rem;"></i>
                                    </div>
                                    <div style="flex:1; min-width:200px;">
                                        <h3 style="margin:0 0 0.2rem 0; font-size:0.95rem; color:var(--text); <?= $isComplete ? 'text-decoration:line-through;' : '' ?>">
                                            <?= htmlspecialchars($item['title']) ?>
                                        </h3>
                                        <span style="font-size:0.78rem; color:rgba(32,0,130,0.4); text-transform:capitalize;"><?= htmlspecialchars($item['label']) ?></span>
                                    </div>
                                    <?php if ($item['link']): ?>
                                        <?php if ($isComplete): ?>
                                            <span style="font-size:0.75rem; color:#10b981; font-weight:600; padding:0.3rem 0.6rem; background:rgba(16,185,129,0.1); border-radius:999px;">
                                                <i class="fas fa-check-circle" style="margin-right:0.25rem;"></i> Completed
                                            </span>
                                        <?php else: ?>
                                            <a href="<?= htmlspecialchars($item['link']) ?>" style="padding:0.4rem 0.8rem; background:var(--primary); color:#fff; border-radius:999px; text-decoration:none; font-size:0.78rem; font-weight:700; white-space:nowrap;">
                                                <i class="fas fa-play" style="margin-right:0.3rem;"></i> Start
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="font-size:0.75rem; color:rgba(32,0,130,0.35);">No direct link</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- No Path Assigned -->
        <div class="mode-card" style="text-align:center; padding:4rem 2rem;">
            <div style="width:80px; height:80px; border-radius:50%; background:rgba(32,0,130,0.08); display:flex; align-items:center; justify-content:center; margin:0 auto 1.5rem;">
                <i class="fas fa-route" style="color:var(--primary); font-size:2rem;"></i>
            </div>
            <h2 style="margin:0 0 0.5rem 0; color:var(--text);">No Learning Path Assigned</h2>
            <p style="color:rgba(32,0,130,0.5); max-width:480px; margin:0 auto 1.5rem; line-height:1.6;">
                You don't have a learning path assigned yet. Your instructor or admin can assign one to guide your learning journey.
            </p>
            <div style="display:flex; gap:0.75rem; justify-content:center; flex-wrap:wrap;">
                <a href="?page=learner/catalog" style="padding:0.5rem 1rem; background:var(--primary); color:#fff; border-radius:999px; text-decoration:none; font-size:0.85rem; font-weight:700;">
                    <i class="fas fa-compass"></i> Browse Catalog
                </a>
                <a href="?page=learner/learner-home" style="padding:0.5rem 1rem; background:transparent; color:var(--primary); border:1px solid rgba(32,0,130,0.2); border-radius:999px; text-decoration:none; font-size:0.85rem; font-weight:700;">
                    Back to Home
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>
