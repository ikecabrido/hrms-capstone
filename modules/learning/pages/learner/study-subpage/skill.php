<?php
include_once __DIR__ . '/../../../classes/Employee.php';
require_once dirname(__DIR__, 5) . '/database/db.php';
require_once dirname(__DIR__, 3) . '/classes/progress.php';

$employeeClass = new Employee();
$learnerId = (int) ($employeeClass->getEmployeeId() ?? 0);

$skills = [];

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

    // Also get skills from module progress.
    //
    // A module counts as completed when all of its active lessons/quizzes are complete
    // (Progress::sqlModuleCompleted). The previous version joined ld_progress on
    // item_type = 'module', a row that is never written, so module-derived skills never
    // appeared as completed.
    $moduleCompletedSql = Progress::sqlModuleCompleted('m', 'e.id');
    $modSkills = $pdo->prepare("
        SELECT DISTINCT s.id, s.name, s.description,
               COUNT(DISTINCT m.course_id) AS course_count,
               COUNT(DISTINCT CASE WHEN " . $moduleCompletedSql . " THEN m.id END) AS completed_courses
        FROM ld_skill s
        INNER JOIN ld_module_skill ms ON ms.skill_id = s.id
        INNER JOIN ld_module m ON m.id = ms.module_id AND m.status = 'active'
        INNER JOIN ld_enrollment e ON e.course_id = m.course_id AND e.learner_id = :learner_id
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
        }
    }
    $skills = array_values($skillMap);
} catch (Throwable $e) {
    DbError::capture($e, 'learner/study-subpage/skill');
    $skills = [];
}

$totalSkills = count($skills);
$proficientCount = count(array_filter($skills, fn($s) => (int) $s['completed_courses'] > 0));
$inProgressCount = $totalSkills - $proficientCount;
?>
<div class="module-content lt-page">
    <div class="home-dashboard">

        <section class="home-hero">
            <div class="home-hero-main">
                <h1>My Skills</h1>
                <p>Skills you are building across your enrolled and completed courses.</p>
            </div>
            <div class="home-hero-aside">
                <div class="home-hero-chips">
                    <span class="home-hero-chip"><i class="fas fa-star"></i> <?= $totalSkills ?> skill<?= $totalSkills !== 1 ? 's' : '' ?></span>
                    <span class="home-hero-chip"><i class="fas fa-check-circle"></i> <?= $proficientCount ?> proficient</span>
                    <span class="home-hero-chip"><i class="fas fa-hourglass-half"></i> <?= $inProgressCount ?> in progress</span>
                </div>
            </div>
        </section>

        <div class="home-card">
            <div class="home-card-head">
                <h2 class="home-card-title">
                    <span class="home-card-icon"><i class="fas fa-star"></i></span> Skill Profile
                </h2>
                <?php if ($totalSkills > 0): ?>
                    <span class="home-card-count"><?= $totalSkills ?> total</span>
                <?php endif; ?>
            </div>

            <?php if (empty($skills)): ?>
                <div class="home-empty">
                    <i class="fas fa-award"></i>
                    <p>No skills yet — enroll in and complete courses to build your skill profile.</p>
                    <a class="home-cta" href="?page=learner/catalog"><i class="fas fa-compass"></i> Browse catalog</a>
                </div>
            <?php else: ?>
                <div class="lt-toolbar">
                    <label class="lt-search">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <input type="search" id="skill-search" placeholder="Search skills…" aria-label="Search skills" />
                    </label>
                </div>

                <div class="lt-skill-grid" id="skills-grid">
                    <?php foreach ($skills as $skill): $proficient = (int) $skill['completed_courses'] > 0; ?>
                        <article class="lt-skill-card<?= $proficient ? ' is-proficient' : '' ?>"
                                 data-search="<?= htmlspecialchars(strtolower($skill['name'] . ' ' . ($skill['description'] ?? ''))) ?>">
                            <span class="lt-skill-icon"><i class="fas <?= $proficient ? 'fa-check-circle' : 'fa-hourglass-half' ?>"></i></span>
                            <div class="lt-skill-body">
                                <div class="lt-skill-head">
                                    <h3 class="lt-skill-name"><?= htmlspecialchars($skill['name']) ?></h3>
                                    <?php if ($proficient): ?>
                                        <span class="home-badge is-success">Proficient</span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($skill['description'])): ?>
                                    <p class="lt-skill-desc"><?= htmlspecialchars($skill['description']) ?></p>
                                <?php endif; ?>
                                <div class="lt-skill-meta">
                                    <span><i class="fas fa-graduation-cap"></i> <?= (int) $skill['course_count'] ?> course<?= (int) $skill['course_count'] !== 1 ? 's' : '' ?></span>
                                    <?php if ((int) $skill['completed_courses'] > 0): ?>
                                        <span><i class="fas fa-check"></i> <?= (int) $skill['completed_courses'] ?> completed</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="home-empty" id="skill-no-results" hidden>
                    <i class="fas fa-search"></i>
                    <p>No skills match your search.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
(function () {
    'use strict';

    var input = document.getElementById('skill-search');
    if (!input) return;

    var cards = Array.prototype.slice.call(document.querySelectorAll('.lt-skill-card'));
    var noResults = document.getElementById('skill-no-results');

    input.addEventListener('input', function () {
        var q = this.value.trim().toLowerCase();
        var visible = 0;

        cards.forEach(function (card) {
            var match = q === '' || (card.getAttribute('data-search') || '').indexOf(q) !== -1;
            card.hidden = !match;
            if (match) visible++;
        });

        if (noResults) noResults.hidden = visible !== 0;
    });
})();
</script>
