<?php
include_once __DIR__ . '/../../../classes/Employee.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

$employeeClass = new Employee();
$learnerId = (int) ($employeeClass->getEmployeeId() ?? 0);

$quizId = (int) ($_GET['quiz_id'] ?? 0);
$quiz = null;

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $stmt = $pdo->prepare("
        SELECT q.id, q.title, q.description, q.time_limit_minutes, q.passing_score, q.attempt_limit, q.status,
               m.id AS module_id, m.title AS module_title,
               c.id AS course_id, c.title AS course_title, c.category,
               (SELECT COUNT(*) FROM ld_quiz_question qq WHERE qq.quiz_id = q.id AND qq.status = 'active') AS question_count
        FROM ld_quiz q
        JOIN ld_module m ON m.id = q.module_id
        JOIN ld_course c ON c.id = m.course_id
        WHERE q.id = :id AND q.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([':id' => $quizId]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $quiz = null;
}

if (!$quiz) {
    echo '<div class="module-content"><div class="mode-card"><h2>Quiz Not Found</h2><p>The quiz you are looking for does not exist or is no longer available.</p>';
    echo '<a href="?page=learner/catalog" style="display:inline-block; margin-top:1rem; padding:0.6rem 1.2rem; background:var(--primary); color:#fff; border-radius:6px; text-decoration:none;">Back to Catalog</a></div></div>';
    return;
}
?>

<div class="module-content">
    <div style="margin-bottom:1.5rem; display:flex; align-items:center; gap:0.5rem; font-size:0.9rem; flex-wrap:wrap;">
        <a href="?page=learner/catalog" style="color:var(--primary); text-decoration:none; font-weight:500;">Catalog</a>
        <i class="fas fa-chevron-right" style="color:#ccc; font-size:0.7rem;"></i>
        <a href="?page=learner/catalog-subpage/course&course_id=<?= $quiz['course_id'] ?>" style="color:var(--primary); text-decoration:none; font-weight:500;"><?= htmlspecialchars($quiz['course_title']) ?></a>
        <i class="fas fa-chevron-right" style="color:#ccc; font-size:0.7rem;"></i>
        <a href="?page=learner/catalog-subpage/module&module_id=<?= $quiz['module_id'] ?>" style="color:var(--primary); text-decoration:none; font-weight:500;"><?= htmlspecialchars($quiz['module_title']) ?></a>
        <i class="fas fa-chevron-right" style="color:#ccc; font-size:0.7rem;"></i>
        <span style="color:#666;">Quiz</span>
    </div>

    <div class="mode-card" style="margin-bottom:1.5rem; text-align:center; max-width:700px; margin:0 auto 1.5rem;">
        <div style="width:80px; height:80px; border-radius:20px; background:rgba(32,0,130,0.1); color:var(--primary); display:flex; align-items:center; justify-content:center; margin:0 auto 1.5rem;">
            <i class="fas fa-question-circle" style="font-size:2rem;"></i>
        </div>
        <div style="display:flex; gap:0.5rem; margin-bottom:0.75rem; justify-content:center; flex-wrap:wrap;">
            <span class="pill" style="background:rgba(32,0,130,0.85); color:#fff;">Quiz</span>
            <?php if (!empty($quiz['category'])): ?>
                <span class="pill"><?= htmlspecialchars($quiz['category']) ?></span>
            <?php endif; ?>
            <span class="pill" style="background:#d4edda; color:#155724;">Active</span>
        </div>
        <h1 style="margin:0 0 0.75rem 0; font-size:1.8rem; color:#222;"><?= htmlspecialchars($quiz['title']) ?></h1>
        <?php if ($quiz['description']): ?>
            <p style="color:#555; line-height:1.7; margin:0 0 1.5rem 0;"><?= nl2br(htmlspecialchars($quiz['description'])) ?></p>
        <?php endif; ?>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(120px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
            <div style="padding:1rem; background:rgba(32,0,130,0.05); border-radius:10px;">
                <div style="font-size:0.75rem; color:var(--text-muted);">Questions</div>
                <div style="font-size:1.3rem; font-weight:700; color:var(--primary);"><?= $quiz['question_count'] ?></div>
            </div>
            <?php if ($quiz['time_limit_minutes']): ?>
                <div style="padding:1rem; background:rgba(32,0,130,0.05); border-radius:10px;">
                    <div style="font-size:0.75rem; color:var(--text-muted);">Time Limit</div>
                    <div style="font-size:1.3rem; font-weight:700; color:var(--primary);"><?= $quiz['time_limit_minutes'] ?> min</div>
                </div>
            <?php endif; ?>
            <?php if ($quiz['passing_score']): ?>
                <div style="padding:1rem; background:rgba(32,0,130,0.05); border-radius:10px;">
                    <div style="font-size:0.75rem; color:var(--text-muted);">Passing Score</div>
                    <div style="font-size:1.3rem; font-weight:700; color:var(--primary);"><?= $quiz['passing_score'] ?>%</div>
                </div>
            <?php endif; ?>
            <?php if ($quiz['attempt_limit']): ?>
                <div style="padding:1rem; background:rgba(32,0,130,0.05); border-radius:10px;">
                    <div style="font-size:0.75rem; color:var(--text-muted);">Attempts</div>
                    <div style="font-size:1.3rem; font-weight:700; color:var(--primary);"><?= $quiz['attempt_limit'] ?></div>
                </div>
            <?php endif; ?>
        </div>

        <div style="text-align:center;">
            <p style="color:#666; margin-bottom:1rem;">Enroll in this course to take the quiz.</p>
            <a href="?page=learner/catalog-subpage/course&course_id=<?= $quiz['course_id'] ?>" style="display:inline-flex; align-items:center; gap:0.5rem; padding:0.75rem 1.5rem; background:var(--primary); color:#fff; border-radius:8px; text-decoration:none; font-weight:600;">
                <i class="fas fa-graduation-cap"></i> View Course
            </a>
        </div>
    </div>
</div>
