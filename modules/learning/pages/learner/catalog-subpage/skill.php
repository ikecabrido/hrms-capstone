<?php
include_once __DIR__ . '/../../../classes/Employee.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

$employeeClass = new Employee();
$learnerId = (int) ($employeeClass->getEmployeeId() ?? 0);

$skillId = (int) ($_GET['skill_id'] ?? 0);
$skill = null;
$courses = [];

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $stmt = $pdo->prepare("SELECT * FROM ld_skill WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $skillId]);
    $skill = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($skill) {
        $cStmt = $pdo->prepare("
            SELECT c.id, c.title, c.description, c.category, c.status,
                   CONCAT(emp.first_name, ' ', emp.last_name) AS instructor_name,
                   (SELECT COUNT(*) FROM ld_enrollment WHERE course_id = c.id) AS enrolled_count
            FROM ld_course c
            JOIN ld_course_skill cs ON cs.course_id = c.id AND cs.skill_id = :sid
            LEFT JOIN em_employees emp ON emp.employee_id = c.instructor_id
            WHERE c.status = 'active'
            ORDER BY c.title ASC
        ");
        $cStmt->execute([':sid' => $skillId]);
        $courses = $cStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    $skill = null;
}

if (!$skill) {
    echo '<div class="module-content"><div class="mode-card"><h2>Skill Not Found</h2><p>The skill you are looking for does not exist.</p>';
    echo '<a href="?page=learner/catalog" style="display:inline-block; margin-top:1rem; padding:0.6rem 1.2rem; background:var(--primary); color:#fff; border-radius:6px; text-decoration:none;">Back to Catalog</a></div></div>';
    return;
}
?>

<div class="module-content">
    <div style="margin-bottom:1.5rem; display:flex; align-items:center; gap:0.5rem; font-size:0.9rem; flex-wrap:wrap;">
        <a href="?page=learner/catalog" style="color:var(--primary); text-decoration:none; font-weight:500;">Catalog</a>
        <i class="fas fa-chevron-right" style="color:#ccc; font-size:0.7rem;"></i>
        <span style="color:#666;"><?= htmlspecialchars($skill['name']) ?></span>
    </div>

    <div class="mode-card" style="margin-bottom:1.5rem;">
        <div style="display:flex; gap:0.5rem; margin-bottom:0.75rem;">
            <span class="pill" style="background:rgba(32,0,130,0.1); color:var(--primary);">Skill</span>
            <span class="pill"><?= count($courses) ?> course<?= count($courses) !== 1 ? 's' : '' ?></span>
        </div>
        <h1 style="margin:0 0 0.5rem 0; font-size:1.6rem;"><?= htmlspecialchars($skill['name']) ?></h1>
        <?php if (!empty($skill['description'])): ?>
            <p style="color:#555; line-height:1.7; margin:0;"><?= nl2br(htmlspecialchars($skill['description'])) ?></p>
        <?php endif; ?>
    </div>

    <div class="mode-card">
        <h2 style="margin-bottom:1rem;"><i class="fas fa-graduation-cap" style="color:var(--primary); margin-right:0.4rem;"></i>Courses Teaching This Skill</h2>
        <?php if (empty($courses)): ?>
            <div style="text-align:center; padding:2rem; color:#999;">No courses teach this skill yet.</div>
        <?php else: ?>
            <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:1rem;">
                <?php foreach ($courses as $course): ?>
                    <a href="?page=learner/catalog-subpage/course&course_id=<?= $course['id'] ?>" style="padding:1.25rem; background:var(--surface, #fff); border:1px solid rgba(32,0,130,0.08); border-radius:12px; text-decoration:none; color:inherit; transition:all 0.2s;">
                        <div style="font-weight:600; margin-bottom:0.25rem;"><?= htmlspecialchars($course['title']) ?></div>
                        <div style="font-size:0.8rem; color:var(--text-muted); margin-bottom:0.5rem;"><?= htmlspecialchars(mb_strimwidth($course['description'] ?? '', 0, 80, '...')) ?></div>
                        <div style="display:flex; gap:0.75rem; font-size:0.8rem; color:var(--text-muted);">
                            <?php if ($course['instructor_name']): ?>
                                <span><i class="fas fa-user" style="margin-right:2px;"></i><?= htmlspecialchars($course['instructor_name']) ?></span>
                            <?php endif; ?>
                            <span><i class="fas fa-users" style="margin-right:2px;"></i><?= $course['enrolled_count'] ?> enrolled</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
