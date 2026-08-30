<?php
include_once __DIR__ . '/../../../classes/Employee.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

$employeeClass = new Employee();
$learnerId = (int) ($employeeClass->getEmployeeId() ?? 0);

$skills = [];
$totalSkills = 0;
$skillCategories = [];

try {
    $pdo = (new Database())->getConnection();

    // Get all skills from courses the learner has completed or is enrolled in
    $stmt = $pdo->prepare("
        SELECT DISTINCT s.id, s.name, s.description,
               COUNT(DISTINCT e.course_id) AS course_count,
               SUM(CASE WHEN e.status = 'completed' THEN 1 ELSE 0 END) AS completed_courses
        FROM ld_skill s
        INNER JOIN ld_course_skill cs ON cs.skill_id = s.id
        INNER JOIN ld_enrollment e ON e.course_id = cs.course_id AND e.learner_id = :learner_id
        LEFT JOIN ld_grade g ON g.learner_id = :learner_id2 AND g.course_id = e.course_id
        GROUP BY s.id, s.name, s.description
        ORDER BY completed_courses DESC, course_count DESC
    ");
    $stmt->execute([':learner_id' => $learnerId, ':learner_id2' => $learnerId]);
    $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalSkills = count($skills);

    // Also get skills from module progress
    $modSkills = $pdo->prepare("
        SELECT DISTINCT s.id, s.name, s.description,
               COUNT(DISTINCT m.course_id) AS course_count,
               SUM(CASE WHEN p.status = 'completed' THEN 1 ELSE 0 END) AS completed_courses
        FROM ld_skill s
        INNER JOIN ld_module_skill ms ON ms.skill_id = s.id
        INNER JOIN ld_module m ON m.id = ms.module_id
        INNER JOIN ld_enrollment e ON e.course_id = m.course_id AND e.learner_id = :learner_id
        LEFT JOIN ld_progress p ON p.enrollment_id = e.id AND p.item_type = 'module' AND p.reference_id = m.id
        GROUP BY s.id, s.name, s.description
        HAVING completed_courses > 0
    ");
    $modSkills->execute([':learner_id' => $learnerId]);
    $moduleSkills = $modSkills->fetchAll(PDO::FETCH_ASSOC);

    // Merge unique
    $skillMap = [];
    foreach ($skills as $s) {
        $skillMap[$s['id']] = $s;
    }
    foreach ($moduleSkills as $s) {
        if (!isset($skillMap[$s['id']])) {
            $skillMap[$s['id']] = $s;
            $totalSkills++;
        }
    }
    $skills = array_values($skillMap);

} catch (Throwable $e) {
    $skills = [];
}
?>
<div class="module-content">
    <div class="toolbar">
        <div class="toolbar-search">
            <input type="search" id="skill-search" placeholder="Search skills..." aria-label="Search skills" />
        </div>
        
    </div>

    <!-- Skills Summary -->
    <div class="analytics-cards" style="margin-bottom:2rem;">
        <div class="analytics-card">
            <h2>Total Skills</h2>
            <p class="analytics-value"><?= $totalSkills ?></p>
        </div>
        <div class="analytics-card">
            <h2>Proficient</h2>
            <p class="analytics-value"><?= count(array_filter($skills, fn($s) => $s['completed_courses'] > 0)) ?></p>
        </div>
        <div class="analytics-card">
            <h2>In Progress</h2>
            <p class="analytics-value"><?= count(array_filter($skills, fn($s) => $s['completed_courses'] == 0)) ?></p>
        </div>
    </div>

    <!-- Skills List -->
    <div class="mode-card">
        <h3><i class="fas fa-star" style="color:var(--primary); margin-right:0.5rem;"></i> My Skills</h3>
        <?php if (empty($skills)): ?>
            <div style="padding:3rem; text-align:center; background:#f9f9f9; border-radius:12px;">
                <i class="fas fa-award" style="font-size:3rem; color:#ccc; margin-bottom:1rem; display:block;"></i>
                <h3>No skills yet</h3>
                <p style="color:#999;">Enroll in and complete courses to build your skill profile.</p>
            </div>
        <?php else: ?>
            <div id="skills-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:1rem; margin-top:1rem;">
                <?php foreach ($skills as $skill):
                    $proficient = $skill['completed_courses'] > 0;
                ?>
                    <div class="skill-card" data-search="<?= htmlspecialchars(strtolower($skill['name'] . ' ' . ($skill['description'] ?? ''))) ?>" style="padding:1.25rem; background:#f9f9f9; border-radius:12px; border-left:4px solid <?= $proficient ? '#28a745' : 'var(--primary)' ?>; transition: transform 0.2s;">
                        <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:0.75rem;">
                            <div style="width:40px; height:40px; border-radius:10px; background:<?= $proficient ? '#d4edda' : 'rgba(32,0,130,0.1)' ?>; color:<?= $proficient ? '#155724' : 'var(--primary)' ?>; display:flex; align-items:center; justify-content:center;">
                                <i class="fas <?= $proficient ? 'fa-check-circle' : 'fa-hourglass-half' ?>"></i>
                            </div>
                            <div>
                                <strong style="font-size:1.05rem;"><?= htmlspecialchars($skill['name']) ?></strong>
                                <?php if ($proficient): ?>
                                    <span style="display:inline-block; margin-left:0.5rem; font-size:0.75rem; background:#d4edda; color:#155724; padding:0.15rem 0.5rem; border-radius:10px;">Proficient</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!empty($skill['description'])): ?>
                            <p style="margin:0 0 0.75rem 0; color:#666; font-size:0.9rem; line-height:1.5;"><?= htmlspecialchars($skill['description']) ?></p>
                        <?php endif; ?>
                        <div style="display:flex; gap:1rem; color:#999; font-size:0.8rem;">
                            <span><i class="fas fa-graduation-cap" style="margin-right:3px;"></i><?= $skill['course_count'] ?> course<?= $skill['course_count'] != 1 ? 's' : '' ?></span>
                            <?php if ($skill['completed_courses'] > 0): ?>
                                <span><i class="fas fa-check" style="margin-right:3px; color:#28a745;"></i><?= $skill['completed_courses'] ?> completed</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('skill-search').addEventListener('input', function() {
    var q = this.value.toLowerCase();
    document.querySelectorAll('.skill-card').forEach(function(card) {
        card.style.display = card.dataset.search.indexOf(q) !== -1 ? '' : 'none';
    });
});
</script>
