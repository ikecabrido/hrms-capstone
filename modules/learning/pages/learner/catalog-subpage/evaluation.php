<?php
include_once __DIR__ . '/../../../classes/Employee.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

$employeeClass = new Employee();
$learnerId = (int) ($employeeClass->getEmployeeId() ?? 0);

$evaluationId = (int) ($_GET['evaluation_id'] ?? 0);
$evaluation = null;

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $stmt = $pdo->prepare("
        SELECT e.id, e.title, e.description, e.status, e.created_at,
               c.id AS course_id, c.title AS course_title, c.category
        FROM ld_evaluation e
        JOIN ld_course c ON c.id = e.course_id
        WHERE e.id = :id AND e.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([':id' => $evaluationId]);
    $evaluation = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $evaluation = null;
}

if (!$evaluation) {
    echo '<div class="module-content"><div class="mode-card"><h2>Evaluation Not Found</h2><p>The evaluation you are looking for does not exist or is no longer available.</p>';
    echo '<a href="?page=learner/catalog" style="display:inline-block; margin-top:1rem; padding:0.6rem 1.2rem; background:var(--primary); color:#fff; border-radius:6px; text-decoration:none;">Back to Catalog</a></div></div>';
    return;
}
?>

<div class="module-content">
    <div style="margin-bottom:1.5rem; display:flex; align-items:center; gap:0.5rem; font-size:0.9rem; flex-wrap:wrap;">
        <a href="?page=learner/catalog" style="color:var(--primary); text-decoration:none; font-weight:500;">Catalog</a>
        <i class="fas fa-chevron-right" style="color:#ccc; font-size:0.7rem;"></i>
        <a href="?page=learner/catalog-subpage/course&course_id=<?= $evaluation['course_id'] ?>" style="color:var(--primary); text-decoration:none; font-weight:500;"><?= htmlspecialchars($evaluation['course_title']) ?></a>
        <i class="fas fa-chevron-right" style="color:#ccc; font-size:0.7rem;"></i>
        <span style="color:#666;">Evaluation</span>
    </div>

    <div class="mode-card" style="margin-bottom:1.5rem;">
        <div style="display:flex; gap:0.5rem; margin-bottom:0.75rem; flex-wrap:wrap;">
            <span class="pill" style="background:rgba(32,0,130,0.85); color:#fff;">Evaluation</span>
            <?php if (!empty($evaluation['category'])): ?>
                <span class="pill"><?= htmlspecialchars($evaluation['category']) ?></span>
            <?php endif; ?>
            <span class="pill" style="background:#d4edda; color:#155724;">Active</span>
        </div>
        <h1 style="margin:0 0 0.75rem 0; font-size:1.8rem; color:#222;"><?= htmlspecialchars($evaluation['title']) ?></h1>
        <?php if ($evaluation['description']): ?>
            <p style="color:#555; line-height:1.7; margin:0 0 1.5rem 0;"><?= nl2br(htmlspecialchars($evaluation['description'])) ?></p>
        <?php endif; ?>

        <div style="display:flex; gap:1.5rem; flex-wrap:wrap; margin-bottom:1.5rem;">
            <div style="display:flex; align-items:center; gap:0.5rem;">
                <i class="fas fa-graduation-cap" style="color:var(--primary);"></i>
                <div>
                    <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase;">Course</div>
                    <div style="font-weight:600;"><?= htmlspecialchars($evaluation['course_title']) ?></div>
                </div>
            </div>
        </div>

        <div style="text-align:center; padding:1.5rem; background:rgba(32,0,130,0.04); border-radius:10px;">
            <p style="color:#666; margin:0 0 0.75rem 0;">Enroll in this course to take the evaluation.</p>
            <a href="?page=learner/catalog-subpage/course&course_id=<?= $evaluation['course_id'] ?>" style="display:inline-flex; align-items:center; gap:0.5rem; padding:0.75rem 1.5rem; background:var(--primary); color:#fff; border-radius:8px; text-decoration:none; font-weight:600;">
                <i class="fas fa-graduation-cap"></i> View Course
            </a>
        </div>
    </div>
</div>
