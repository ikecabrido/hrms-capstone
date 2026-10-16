<?php
include_once __DIR__ . '/../../../classes/Employee.php';
require_once dirname(__DIR__, 3) . '/classes/progress.php';
require_once dirname(__DIR__, 3) . '/classes/enrollment.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

$employeeClass = new Employee();
$learnerId = (int) ($employeeClass->getEmployeeId() ?? 0);

$lessonId = (int) ($_GET['lesson_id'] ?? 0);
$courseId = (int) ($_GET['course_id'] ?? 0);
$lesson = null;
$files = [];
$isCompleted = false;
$enrollment = null;
$moduleId = 0;
$currentPageType = 'lesson';
$currentPageId = $lessonId;
$youtubeId = null;

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $stmt = $pdo->prepare("
        SELECT l.id, l.title, l.content_type, l.content_body, l.video_url, l.order_index,
               m.id AS module_id, m.title AS module_title, m.order_index AS module_order,
               c.id AS course_id, c.title AS course_title
        FROM ld_lesson l
        JOIN ld_module m ON m.id = l.module_id
        JOIN ld_course c ON c.id = m.course_id
        WHERE l.id = :id AND l.status = 'active' AND m.status = 'active' AND c.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([':id' => $lessonId]);
    $lesson = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($lesson) {
        $courseId = (int) $lesson['course_id'];
        $moduleId = (int) $lesson['module_id'];
        $enrollmentObj = new Enrollment($pdo);
        $enrollment = $enrollmentObj->getByLearnerAndCourse($learnerId, $courseId);

        if ($enrollment) {
            $progStmt = $pdo->prepare("SELECT status FROM ld_progress WHERE enrollment_id = :eid AND item_type = 'lesson' AND reference_id = :lid LIMIT 1");
            $progStmt->execute([':eid' => $enrollment['id'], ':lid' => $lessonId]);
            $isCompleted = ($progStmt->fetchColumn() === 'completed');
        }

        $fileStmt = $pdo->prepare("SELECT id, file_path, title FROM ld_lesson_file WHERE lesson_id = :lid ORDER BY id ASC");
        $fileStmt->execute([':lid' => $lessonId]);
        $files = $fileStmt->fetchAll(PDO::FETCH_ASSOC);

        if ($lesson['video_url']) {
            if (preg_match('#(?:youtube\.com/(?:watch\?.*v=|embed/|v/)|youtu\.be/)([a-zA-Z0-9_-]{11})#', $lesson['video_url'], $m)) {
                $youtubeId = $m[1];
            }
        }
    }
} catch (Throwable $e) {
    DbError::capture($e, 'learner/study-subpage/lesson');
    $lesson = null;
}

if (!$lesson) {
    ?>
    <div class="module-content">
        <div class="study-doc">
            <div class="study-panel" style="text-align:center;">
                <i class="fas fa-triangle-exclamation" style="display:block;margin-bottom:0.75rem;color:var(--muted);font-size:2rem;"></i>
                <h2 style="margin:0 0 0.5rem;">Lesson Not Found</h2>
                <p style="margin:0 0 1.25rem;color:var(--muted);">The lesson you are looking for does not exist.</p>
                <a class="study-btn" href="?page=learner/study"><i class="fas fa-arrow-left"></i> Back to Study</a>
            </div>
        </div>
    </div>
    <?php
    return;
}

$typeIcon = match($lesson['content_type']) {
    'video' => 'fa-video',
    'text' => 'fa-file-alt',
    'file' => 'fa-paperclip',
    'mixed' => 'fa-layer-group',
    default => 'fa-book-open',
};
$typeLabel = match($lesson['content_type']) {
    'video' => 'Video',
    'text' => 'Reading',
    'file' => 'Document',
    'mixed' => 'Mixed media',
    default => 'Lesson',
};
?>

<div class="module-content">
<?php require_once __DIR__ . '/includes/course-sidebar.php'; ?>

    <div class="study-breadcrumb">
        <a href="?page=learner/study"><i class="fas fa-book-open"></i> My Study</a>
        <i class="fas fa-chevron-right sep"></i>
        <a href="?page=learner/study-subpage/course&course_id=<?= $courseId ?>"><?= htmlspecialchars($lesson['course_title']) ?></a>
        <i class="fas fa-chevron-right sep"></i>
        <a href="?page=learner/study-subpage/module&module_id=<?= $moduleId ?>&course_id=<?= $courseId ?>"><?= htmlspecialchars($lesson['module_title']) ?></a>
        <i class="fas fa-chevron-right sep"></i>
        <span class="current">Lesson <?= $lesson['order_index'] + 1 ?></span>
    </div>

    <div class="study-doc">
        <!-- Lesson header -->
        <div class="study-header-card">
            <div class="study-pills">
                <span class="study-pill study-pill-primary"><i class="fas <?= $typeIcon ?>"></i><?= $typeLabel ?></span>
                <?php if ($isCompleted): ?>
                    <span class="study-pill study-pill-success"><i class="fas fa-check-circle"></i> Completed</span>
                <?php endif; ?>
            </div>
            <h1 class="study-header-title"><?= htmlspecialchars($lesson['title']) ?></h1>
            <p class="study-header-desc">
                <i class="fas fa-folder-open" style="margin-right:0.35rem;color:var(--primary);"></i><?= htmlspecialchars($lesson['module_title']) ?>
                &middot; Lesson <?= $lesson['order_index'] + 1 ?>
            </p>
        </div>

        <?php if (!empty($youtubeId)): ?>
            <div class="study-panel">
                <h2 class="study-panel-title"><i class="fab fa-youtube" style="color:#dc3545;"></i> Video Lesson</h2>
                <div class="study-video">
                    <iframe src="https://www.youtube.com/embed/<?= htmlspecialchars($youtubeId) ?>" allowfullscreen allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" title="Video lesson"></iframe>
                </div>
            </div>
        <?php elseif (!empty($lesson['video_url'])): ?>
            <div class="study-panel">
                <h2 class="study-panel-title"><i class="fas fa-video" style="color:#dc3545;"></i> Video Lesson</h2>
                <a href="<?= htmlspecialchars($lesson['video_url']) ?>" target="_blank" rel="noopener" class="study-btn" style="background:#dc3545;">
                    <i class="fas fa-external-link-alt"></i> Open Video
                </a>
            </div>
        <?php endif; ?>

        <?php if (!empty($lesson['content_body'])): ?>
            <div class="study-panel">
                <h2 class="study-panel-title"><i class="fas fa-file-alt"></i> Lesson Content</h2>
                <div class="study-prose"><?= $lesson['content_body'] ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($files)): ?>
            <div class="study-panel">
                <h2 class="study-panel-title"><i class="fas fa-paperclip"></i> Attached Files</h2>
                <div style="display:flex;flex-direction:column;gap:0.5rem;">
                    <?php foreach ($files as $file): ?>
                        <a class="study-file" href="pages/learner/study-subpage/ajax/progress/download-material.php?file_id=<?= (int) $file['id'] ?>&course_id=<?= $courseId ?>">
                            <i class="fas fa-file"></i>
                            <span class="study-file-name"><?= htmlspecialchars($file['title']) ?></span>
                            <i class="fas fa-download study-file-dl"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($enrollment) && !$isCompleted): ?>
            <div class="study-panel" style="text-align:center;">
                <p style="margin:0 0 1rem;color:var(--muted);">Finished with this lesson?</p>
                <button type="button" id="mark-complete-btn" class="study-btn study-btn-success" onclick="markLessonComplete()">
                    <i class="fas fa-check-circle"></i> Mark as Complete
                </button>
                <div id="complete-status" style="display:none;margin-top:0.75rem;color:#10b981;font-weight:700;">
                    <i class="fas fa-check"></i> Lesson marked as complete!
                </div>
            </div>
        <?php elseif ($isCompleted): ?>
            <div class="study-panel" style="display:flex;align-items:center;justify-content:center;gap:0.5rem;color:#10b981;font-weight:700;">
                <i class="fas fa-check-circle"></i> You have completed this lesson.
            </div>
        <?php endif; ?>
    </div>

<script>
function markLessonComplete() {
    var btn = document.getElementById('mark-complete-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    fetch('pages/learner/study-subpage/ajax/progress/mark-lesson-complete.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'course_id=<?= $courseId ?>&lesson_id=<?= $lessonId ?>'
    }).then(function(r) { return r.json(); }).then(function(data) {
        if (data.success) {
            btn.style.display = 'none';
            document.getElementById('complete-status').style.display = 'block';
            /* Tick the lesson in the course panel and open it so the learner
               can move straight on to the next item. */
            if (typeof studyMarkActiveItemCompleted === 'function') studyMarkActiveItemCompleted();
            if (typeof studyOpenSidebar === 'function') studyOpenSidebar();
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-circle"></i> Mark as Complete';
            alert(data.message || 'Failed to mark as complete');
        }
    }).catch(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check-circle"></i> Mark as Complete';
    });
}
</script>

<?php require_once __DIR__ . '/includes/course-sidebar-footer.php'; ?>
</div>
