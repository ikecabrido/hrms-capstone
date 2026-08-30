<?php
include_once __DIR__ . '/../../../classes/Employee.php';
require_once dirname(__DIR__, 3) . '/classes/enrollment.php';
require_once dirname(__DIR__, 3) . '/classes/learningpath.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

$employeeClass = new Employee();
$learnerId = (int) ($employeeClass->getEmployeeId() ?? 0);

$learningPathId = (int) ($_GET['learning_path_id'] ?? 0);
$learningPath = null;
$items = [];

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $stmt = $pdo->prepare("SELECT * FROM ld_learning_path WHERE id = :id AND status = 'active' LIMIT 1");
    $stmt->execute([':id' => $learningPathId]);
    $learningPath = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($learningPath) {
        $itemStmt = $pdo->prepare("
            SELECT lpi.id, lpi.item_type, lpi.reference_id, lpi.order_index, lpi.notes,
                   CASE lpi.item_type
                       WHEN 'course' THEN (SELECT title FROM ld_course WHERE id = lpi.reference_id)
                       WHEN 'module' THEN (SELECT title FROM ld_module WHERE id = lpi.reference_id)
                       WHEN 'lesson' THEN (SELECT title FROM ld_lesson WHERE id = lpi.reference_id)
                       WHEN 'quiz' THEN (SELECT title FROM ld_quiz WHERE id = lpi.reference_id)
                       WHEN 'program' THEN (SELECT title FROM ld_program WHERE id = lpi.reference_id)
                       WHEN 'video-conference' THEN (SELECT title FROM ld_video_conference WHERE id = lpi.reference_id)
                       ELSE 'Unknown'
                   END AS item_title
            FROM ld_learning_path_item lpi
            WHERE lpi.learning_path_id = :lpid
            ORDER BY lpi.order_index ASC, lpi.id ASC
        ");
        $itemStmt->execute([':lpid' => $learningPathId]);
        $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    $learningPath = null;
}

if (!$learningPath) {
    echo '<div class="module-content"><div class="mode-card"><h2>Learning Path Not Found</h2><p>The learning path you are looking for does not exist.</p>';
    echo '<a href="?page=learner/study" style="display:inline-block; margin-top:1rem; padding:0.6rem 1.2rem; background:var(--primary); color:#fff; border-radius:6px; text-decoration:none;">Back to Study</a></div></div>';
    return;
}
?>

<div class="module-content">
    <div style="margin-bottom:1.5rem; display:flex; align-items:center; gap:0.5rem; font-size:0.9rem; flex-wrap:wrap;">
        <a href="?page=learner/study" style="color:var(--primary); text-decoration:none; font-weight:500;">My Study</a>
        <i class="fas fa-chevron-right" style="color:#ccc; font-size:0.7rem;"></i>
        <span style="color:#666;"><?= htmlspecialchars($learningPath['title']) ?></span>
    </div>

    <div class="mode-card" style="margin-bottom:1.5rem;">
        <div style="display:flex; gap:0.5rem; margin-bottom:0.75rem;">
            <span class="pill" style="background:rgba(32,0,130,0.1); color:var(--primary);">Learning Path</span>
            <span class="pill"><?= count($items) ?> steps</span>
        </div>
        <h1 style="margin:0 0 0.5rem 0; font-size:1.6rem;"><?= htmlspecialchars($learningPath['title']) ?></h1>
        <?php if (!empty($learningPath['description'])): ?>
            <p style="color:#555; line-height:1.7; margin:0;"><?= nl2br(htmlspecialchars($learningPath['description'])) ?></p>
        <?php endif; ?>
    </div>

    <div class="mode-card">
        <h2 style="margin-bottom:1.5rem;"><i class="fas fa-route" style="color:var(--primary); margin-right:0.4rem;"></i>Steps</h2>
        <?php if (empty($items)): ?>
            <div style="text-align:center; padding:2rem; color:#999;">No steps in this learning path yet.</div>
        <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:0;">
                <?php foreach ($items as $idx => $item):
                    $typeIcon = match($item['item_type']) {
                        'course' => 'fa-graduation-cap', 'module' => 'fa-cubes', 'lesson' => 'fa-book-open',
                        'quiz' => 'fa-question-circle', 'program' => 'fa-clipboard-list', 'video-conference' => 'fa-video',
                        default => 'fa-circle'
                    };
                    $typeColor = match($item['item_type']) {
                        'course' => 'var(--primary)', 'module' => '#6c757d', 'lesson' => '#28a745',
                        'quiz' => '#ffc107', 'program' => '#17a2b8', 'video-conference' => '#dc3545',
                        default => '#999'
                    };
                    $last = ($idx === count($items) - 1);
                ?>
                    <div style="display:flex; gap:1rem; position:relative;">
                        <!-- Timeline line -->
                        <div style="display:flex; flex-direction:column; align-items:center; width:24px; flex-shrink:0;">
                            <div style="width:24px; height:24px; border-radius:50%; background:<?= $typeColor ?>; color:#fff; display:flex; align-items:center; justify-content:center; font-size:0.7rem; font-weight:700; z-index:1;"><?= $idx + 1 ?></div>
                            <?php if (!$last): ?><div style="width:2px; flex:1; background:rgba(32,0,130,0.15); min-height:30px;"></div><?php endif; ?>
                        </div>
                        <!-- Content -->
                        <div style="flex:1; padding:0 0 1.5rem 0;">
                            <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.25rem;">
                                <span style="padding:0.15rem 0.5rem; background:<?= $typeColor ?>20; color:<?= $typeColor ?>; border-radius:4px; font-size:0.75rem; font-weight:600; text-transform:uppercase;"><?= $item['item_type'] ?></span>
                            </div>
                            <h3 style="margin:0 0 0.25rem 0; font-size:1rem;"><?= htmlspecialchars($item['item_title'] ?? 'Untitled') ?></h3>
                            <?php if (!empty($item['notes'])): ?>
                                <p style="margin:0; color:#666; font-size:0.85rem;"><?= htmlspecialchars($item['notes']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
