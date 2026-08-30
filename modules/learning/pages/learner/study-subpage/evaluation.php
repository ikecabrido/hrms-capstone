<?php
include_once __DIR__ . '/../../../classes/Employee.php';
require_once dirname(__DIR__, 3) . '/classes/enrollment.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

$employeeClass = new Employee();
$learnerId = (int) ($employeeClass->getEmployeeId() ?? 0);

$evaluationId = (int) ($_GET['evaluation_id'] ?? 0);
$courseId = (int) ($_GET['course_id'] ?? 0);
$evaluation = null;
$questions = [];
$hasSubmitted = false;

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $stmt = $pdo->prepare("
        SELECT e.id, e.title, e.description, e.course_id,
               c.title AS course_title
        FROM ld_evaluation e
        JOIN ld_course c ON c.id = e.course_id
        WHERE e.id = :id AND e.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([':id' => $evaluationId]);
    $evaluation = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($evaluation) {
        $courseId = (int) $evaluation['course_id'];

        $enrollmentObj = new Enrollment($pdo);
        $enrollment = $enrollmentObj->getByLearnerAndCourse($learnerId, $courseId);

        $checkStmt = $pdo->prepare("SELECT id FROM ld_quiz_session WHERE learner_id = :lid AND session_type = 'evaluation' AND reference_id = :eid AND status = 'submitted' LIMIT 1");
        $checkStmt->execute([':lid' => $learnerId, ':eid' => $evaluationId]);
        $hasSubmitted = (bool) $checkStmt->fetch();

        $qStmt = $pdo->prepare("
            SELECT qq.id, qq.question_text, qq.question_type
            FROM ld_quiz_question qq
            WHERE qq.quiz_id = 0 AND qq.module_id = 0
            LIMIT 0
        ");
        $qStmt->execute();
    }
} catch (Throwable $e) {
    $evaluation = null;
}

if (!$evaluation) {
    echo '<div class="module-content"><div class="mode-card"><h2>Evaluation Not Found</h2><p>The evaluation you are looking for does not exist.</p>';
    echo '<a href="?page=learner/study" style="display:inline-block; margin-top:1rem; padding:0.6rem 1.2rem; background:var(--primary); color:#fff; border-radius:6px; text-decoration:none;">Back to Study</a></div></div>';
    return;
}
?>

<div class="module-content">
    <div style="margin-bottom:1.5rem; display:flex; align-items:center; gap:0.5rem; font-size:0.9rem; flex-wrap:wrap;">
        <a href="?page=learner/study" style="color:var(--primary); text-decoration:none; font-weight:500;">My Study</a>
        <i class="fas fa-chevron-right" style="color:#ccc; font-size:0.7rem;"></i>
        <a href="?page=learner/study-subpage/course&course_id=<?= $courseId ?>" style="color:var(--primary); text-decoration:none; font-weight:500;"><?= htmlspecialchars($evaluation['course_title']) ?></a>
        <i class="fas fa-chevron-right" style="color:#ccc; font-size:0.7rem;"></i>
        <span style="color:#666;">Evaluation</span>
    </div>

    <div class="mode-card" style="max-width:700px; margin:0 auto; text-align:center; padding:2.5rem;">
        <div style="width:80px; height:80px; border-radius:20px; background:rgba(32,0,130,0.1); color:var(--primary); display:flex; align-items:center; justify-content:center; margin:0 auto 1.5rem;">
            <i class="fas fa-clipboard-check" style="font-size:2rem;"></i>
        </div>
        <h1 style="margin:0 0 0.5rem 0; font-size:1.8rem;"><?= htmlspecialchars($evaluation['title']) ?></h1>
        <?php if ($evaluation['description']): ?>
            <p style="color:#555; line-height:1.6; margin:0 0 1.5rem 0;"><?= nl2br(htmlspecialchars($evaluation['description'])) ?></p>
        <?php endif; ?>

        <?php if ($hasSubmitted): ?>
            <div style="padding:1.25rem; background:rgba(40,167,69,0.08); border:1px solid rgba(40,167,69,0.2); border-radius:10px; margin-bottom:1.5rem;">
                <i class="fas fa-check-circle" style="color:#28a745; margin-right:0.4rem;"></i> You have already submitted this evaluation.
            </div>
            <a href="?page=learner/study-subpage/course&course_id=<?= $courseId ?>" style="display:inline-flex; align-items:center; gap:0.5rem; padding:0.75rem 1.5rem; background:var(--primary); color:#fff; border-radius:8px; text-decoration:none; font-weight:600;">
                <i class="fas fa-arrow-left"></i> Back to Course
            </a>
        <?php else: ?>
            <p style="color:#666; margin-bottom:1.5rem;">Please rate and provide feedback on this course. Your responses are anonymous.</p>
            <a href="?page=learner/study-subpage/evaluation&evaluation_id=<?= $evaluationId ?>&course_id=<?= $courseId ?>&action=take" style="display:inline-flex; align-items:center; gap:0.5rem; padding:0.85rem 2rem; background:var(--primary); color:#fff; border-radius:8px; text-decoration:none; font-weight:700;">
                <i class="fas fa-play"></i> Start Evaluation
            </a>
        <?php endif; ?>
    </div>
</div>
