<?php
include_once __DIR__ . '/../../../classes/Employee.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

$employeeClass = new Employee();
$learnerId = (int) ($employeeClass->getEmployeeId() ?? 0);

$moduleId = (int) ($_GET['module_id'] ?? 0);
$module = null;
$lessons = [];
$quizzes = [];
$skills = [];

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Fetch module with course info
    $stmt = $pdo->prepare("
        SELECT m.id, m.title, m.description, m.order_index, m.created_at,
               m.course_id,
               c.title AS course_title
        FROM ld_module m
        JOIN ld_course c ON c.id = m.course_id
        WHERE m.id = :id AND m.status = 'active' AND c.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([':id' => $moduleId]);
    $module = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($module) {
        // Fetch lessons
        $lessonStmt = $pdo->prepare("
            SELECT id, title, content_type, video_url, order_index
            FROM ld_lesson
            WHERE module_id = :mid AND status = 'active'
            ORDER BY order_index ASC, id ASC
        ");
        $lessonStmt->execute([':mid' => $moduleId]);
        $lessons = $lessonStmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch quizzes
        $quizStmt = $pdo->prepare("
            SELECT id, title, duration_seconds, passing_score, max_attempts, question_count
            FROM ld_quiz
            WHERE module_id = :mid AND status = 'active'
            ORDER BY id ASC
        ");
        $quizStmt->execute([':mid' => $moduleId]);
        $quizzes = $quizStmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch skills
        $skillStmt = $pdo->prepare("
            SELECT s.name FROM ld_module_skill ms
            JOIN ld_skill s ON s.id = ms.skill_id
            WHERE ms.module_id = :mid
        ");
        $skillStmt->execute([':mid' => $moduleId]);
        $skills = $skillStmt->fetchAll(PDO::FETCH_COLUMN);
    }
} catch (Throwable $e) {
    $module = null;
}

if (!$module) {
    echo '<div class="module-content"><div class="mode-card"><h2>Module Not Found</h2><p>The module you are looking for does not exist or is no longer available.</p>';
    echo '<a href="?page=learner/catalog" style="display:inline-block; margin-top:1rem; padding:0.6rem 1.2rem; background:var(--primary); color:#fff; border-radius:6px; text-decoration:none;">Back to Catalog</a></div></div>';
    return;
}

$lessonCount = count($lessons);
$quizCount = count($quizzes);

function lessonTypeIcon($type) {
    return match($type) {
        'video' => 'fa-video',
        'text'  => 'fa-file-alt',
        'file'  => 'fa-paperclip',
        'mixed' => 'fa-layer-group',
        default => 'fa-book-open',
    };
}

function lessonTypeLabel($type) {
    return match($type) {
        'video' => 'Video',
        'text'  => 'Text',
        'file'  => 'File',
        'mixed' => 'Mixed',
        default => 'Lesson',
    };
}
?>

<div class="module-content">
    <!-- Breadcrumb -->
    <div style="margin-bottom:1.5rem; display:flex; align-items:center; gap:0.5rem; font-size:0.9rem; flex-wrap:wrap;">
        <a href="?page=learner/catalog" style="color:var(--primary); text-decoration:none; font-weight:500;">Catalog</a>
        <i class="fas fa-chevron-right" style="color:#ccc; font-size:0.7rem;"></i>
        <a href="?page=learner/catalog-subpage/course&course_id=<?= $module['course_id'] ?>" style="color:var(--primary); text-decoration:none; font-weight:500;"><?= htmlspecialchars($module['course_title']) ?></a>
        <i class="fas fa-chevron-right" style="color:#ccc; font-size:0.7rem;"></i>
        <span style="color:#666;">Module <?= $module['order_index'] + 1 ?></span>
    </div>

    <!-- Module Header -->
    <div class="mode-card" style="margin-bottom:1.5rem;">
        <div style="display:flex; gap:2rem; align-items:flex-start; flex-wrap:wrap;">
            <div style="width:70px; height:70px; border-radius:14px; background:linear-gradient(135deg, rgba(32,0,130,0.85), rgba(81,70,183,0.7)); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <span style="color:#fff; font-size:1.6rem; font-weight:700;"><?= $module['order_index'] + 1 ?></span>
            </div>
            <div style="flex:1; min-width:300px;">
                <div style="display:flex; gap:0.5rem; margin-bottom:0.75rem; flex-wrap:wrap;">
                    <span class="pill" style="background:var(--primary); color:#fff;">Module <?= $module['order_index'] + 1 ?></span>
                    <span class="pill"><?= $lessonCount ?> lesson<?= $lessonCount !== 1 ? 's' : '' ?></span>
                    <?php if ($quizCount > 0): ?>
                        <span class="pill"><?= $quizCount ?> quiz<?= $quizCount !== 1 ? 'zes' : '' ?></span>
                    <?php endif; ?>
                </div>
                <h1 style="margin:0 0 0.75rem 0; font-size:1.6rem; color:#222;"><?= htmlspecialchars($module['title']) ?></h1>
                <?php if ($module['description']): ?>
                    <p style="color:#555; line-height:1.7; margin:0;"><?= nl2br(htmlspecialchars($module['description'])) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Skills -->
    <?php if (!empty($skills)): ?>
        <div class="mode-card" style="margin-bottom:1.5rem;">
            <h2 style="margin-bottom:0.75rem;">Skills Covered</h2>
            <div style="display:flex; gap:0.4rem; flex-wrap:wrap;">
                <?php foreach ($skills as $skill): ?>
                    <span style="padding:0.35rem 0.85rem; background:#f0f0f0; border-radius:20px; font-size:0.85rem; color:#555;"><?= htmlspecialchars($skill) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Lessons -->
    <div class="mode-card" style="margin-bottom:1.5rem;">
        <h2 style="margin-bottom:0.5rem;">Lessons</h2>
        <p style="color:#666; margin:0 0 1.5rem 0;"><?= $lessonCount ?> lesson<?= $lessonCount !== 1 ? 's' : '' ?> in this module</p>

        <?php if (empty($lessons)): ?>
            <div style="text-align:center; padding:3rem; color:#999;">
                <i class="fas fa-book-open" style="font-size:2rem; margin-bottom:0.75rem; display:block;"></i>
                No lessons have been added to this module yet.
            </div>
        <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:0.75rem;">
                <?php foreach ($lessons as $idx => $lesson): ?>
                    <a href="?page=learner/catalog-subpage/lesson&lesson_id=<?= $lesson['id'] ?>" style="display:flex; align-items:center; gap:1rem; padding:1rem 1.25rem; background:#f9f9f9; border:1px solid #e8e8e8; border-radius:10px; text-decoration:none; color:inherit; transition:border-color 0.2s;" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='#e8e8e8'">
                        <div style="width:32px; height:32px; border-radius:50%; background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.8rem; flex-shrink:0;">
                            <?= $idx + 1 ?>
                        </div>
                        <div style="flex:1;">
                            <h3 style="margin:0 0 0.2rem 0; font-size:0.95rem; color:#222;"><?= htmlspecialchars($lesson['title']) ?></h3>
                            <div style="display:flex; gap:0.75rem; font-size:0.8rem; color:#888;">
                                <span><i class="fas <?= lessonTypeIcon($lesson['content_type']) ?>" style="margin-right:3px;"></i><?= lessonTypeLabel($lesson['content_type']) ?></span>
                                <?php if ($lesson['video_url']): ?>
                                    <span><i class="fas fa-play-circle" style="margin-right:3px;"></i>Has video</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <i class="fas fa-chevron-right" style="color:#ccc;"></i>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Quizzes -->
    <?php if (!empty($quizzes)): ?>
        <div class="mode-card">
            <h2 style="margin-bottom:0.5rem;">Quizzes</h2>
            <p style="color:#666; margin:0 0 1.5rem 0;"><?= $quizCount ?> quiz<?= $quizCount !== 1 ? 'zes' : '' ?> to test your knowledge</p>

            <div style="display:flex; flex-direction:column; gap:0.75rem;">
                <?php foreach ($quizzes as $idx => $quiz): ?>
                    <div style="display:flex; align-items:center; gap:1rem; padding:1rem 1.25rem; background:#f9f9f9; border:1px solid #e8e8e8; border-radius:10px;">
                        <div style="width:32px; height:32px; border-radius:50%; background:#dc3545; color:#fff; display:flex; align-items:center; justify-content:center; font-size:0.85rem; flex-shrink:0;">
                            <i class="fas fa-question-circle"></i>
                        </div>
                        <div style="flex:1;">
                            <h3 style="margin:0 0 0.2rem 0; font-size:0.95rem; color:#222;"><?= htmlspecialchars($quiz['title']) ?></h3>
                            <div style="display:flex; gap:0.75rem; font-size:0.8rem; color:#888;">
                                <span><i class="fas fa-clock" style="margin-right:3px;"></i><?= intval($quiz['duration_seconds'] / 60) ?> min</span>
                                <?php if ($quiz['question_count']): ?>
                                    <span><i class="fas fa-list" style="margin-right:3px;"></i><?= $quiz['question_count'] ?> questions</span>
                                <?php endif; ?>
                                <?php if ($quiz['passing_score']): ?>
                                    <span><i class="fas fa-check-circle" style="margin-right:3px;"></i>Pass: <?= $quiz['passing_score'] ?>%</span>
                                <?php endif; ?>
                                <span><i class="fas fa-redo" style="margin-right:3px;"></i><?= $quiz['max_attempts'] ?> attempt<?= $quiz['max_attempts'] !== 1 ? 's' : '' ?></span>
                            </div>
                        </div>
                        <span style="padding:0.4rem 0.8rem; background:#fff3cd; color:#856404; border-radius:6px; font-size:0.8rem; font-weight:500;">
                            <i class="fas fa-lock" style="margin-right:3px;"></i>Enroll to take
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
