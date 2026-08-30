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

        $youtubeId = null;
        if ($lesson['video_url']) {
            if (preg_match('#(?:youtube\.com/(?:watch\?.*v=|embed/|v/)|youtu\.be/)([a-zA-Z0-9_-]{11})#', $lesson['video_url'], $m)) {
                $youtubeId = $m[1];
            }
        }
    }
} catch (Throwable $e) {
    $lesson = null;
}

if (!$lesson) {
    echo '<div class="module-content"><div class="mode-card"><h2>Lesson Not Found</h2><p>The lesson you are looking for does not exist.</p>';
    echo '<a href="?page=learner/study" style="display:inline-block; margin-top:1rem; padding:0.6rem 1.2rem; background:var(--primary); color:#fff; border-radius:8px; text-decoration:none; font-weight:600;">Back to Study</a></div></div>';
    return;
}
$typeIcon = match($lesson['content_type']) { 'video' => 'fa-video', 'text' => 'fa-file-alt', 'file' => 'fa-paperclip', 'mixed' => 'fa-layer-group', default => 'fa-book-open' };
?>

<style>
.study-pill{display:inline-flex;align-items:center;padding:0.3rem 0.75rem;border-radius:999px;font-size:0.72rem;font-weight:700;letter-spacing:0.02em}
.study-pill-primary{background:linear-gradient(135deg,rgba(32,0,130,0.88),rgba(81,70,183,0.75));color:#fff}
.study-pill-success{background:rgba(16,185,129,0.1);color:#10b981}
</style>

<div class="module-content">
<?php require_once __DIR__ . '/includes/course-sidebar.php'; ?>

    <div class="study-breadcrumb">
        <a href="?page=learner/study"><i class="fas fa-book-open" style="margin-right:0.25rem;"></i> My Study</a>
        <i class="fas fa-chevron-right sep"></i>
        <a href="?page=learner/study-subpage/course&course_id=<?= $courseId ?>"><?= htmlspecialchars($lesson['course_title']) ?></a>
        <i class="fas fa-chevron-right sep"></i>
        <a href="?page=learner/study-subpage/module&module_id=<?= $moduleId ?>&course_id=<?= $courseId ?>"><?= htmlspecialchars($lesson['module_title']) ?></a>
        <i class="fas fa-chevron-right sep"></i>
        <span class="current">Lesson <?= $lesson['order_index'] + 1 ?></span>
    </div>

    <div class="mode-card" style="margin-bottom:1.5rem;">
        <div style="display:flex; gap:0.45rem; margin-bottom:0.75rem; flex-wrap:wrap;">
            <span class="study-pill study-pill-primary"><i class="fas <?= $typeIcon ?>" style="margin-right:4px;"></i><?= ucfirst($lesson['content_type']) ?></span>
            <?php if ($isCompleted): ?>
                <span class="study-pill study-pill-success"><i class="fas fa-check-circle" style="margin-right:3px;"></i> Completed</span>
            <?php endif; ?>
        </div>
        <h1 style="margin:0 0 0.5rem 0; font-size:1.55rem; color:var(--text); font-weight:800; letter-spacing:-0.02em;"><?= htmlspecialchars($lesson['title']) ?></h1>
    </div>

    <?php if (!empty($youtubeId)): ?>
        <div class="mode-card" style="margin-bottom:1.5rem;">
            <h2 style="margin-bottom:0.75rem; font-size:1.1rem;"><i class="fab fa-youtube" style="color:#dc3545; margin-right:0.4rem;"></i>Video Lesson</h2>
            <div style="position:relative; padding-bottom:56.25%; height:0; overflow:hidden; border-radius:12px; background:#000;">
                <iframe src="https://www.youtube.com/embed/<?= htmlspecialchars($youtubeId) ?>" style="position:absolute; top:0; left:0; width:100%; height:100%; border:none;" allowfullscreen allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
            </div>
        </div>
    <?php elseif (!empty($lesson['video_url'])): ?>
        <div class="mode-card" style="margin-bottom:1.5rem;">
            <h2 style="margin-bottom:0.75rem; font-size:1.1rem;"><i class="fas fa-video" style="color:#dc3545; margin-right:0.4rem;"></i>Video Lesson</h2>
            <a href="<?= htmlspecialchars($lesson['video_url']) ?>" target="_blank" style="display:inline-flex; align-items:center; gap:0.5rem; padding:0.75rem 1.5rem; background:#dc3545; color:#fff; border-radius:8px; text-decoration:none; font-weight:600;"><i class="fas fa-external-link-alt"></i> Open Video</a>
        </div>
    <?php endif; ?>

    <?php if ($lesson['content_body']): ?>
        <div class="mode-card" style="margin-bottom:1.5rem;">
            <h2 style="margin-bottom:0.75rem; font-size:1.1rem;"><i class="fas fa-file-alt" style="color:var(--primary); margin-right:0.4rem;"></i>Lesson Content</h2>
            <div style="line-height:1.8; font-size:0.95rem; color:var(--text);"><?= $lesson['content_body'] ?></div>
        </div>
    <?php endif; ?>

    <?php if (!empty($files)): ?>
        <div class="mode-card" style="margin-bottom:1.5rem;">
            <h2 style="margin-bottom:0.75rem; font-size:1.1rem;"><i class="fas fa-paperclip" style="color:var(--muted, #888); margin-right:0.4rem;"></i>Attached Files</h2>
            <div style="display:flex; flex-direction:column; gap:0.5rem;">
                <?php foreach ($files as $file): ?>
                    <a href="pages/learner/study-subpage/ajax/progress/download-material.php?file_id=<?= $file['id'] ?>&course_id=<?= $courseId ?>" style="display:flex; align-items:center; gap:0.75rem; padding:0.85rem 1.25rem; background:var(--bg-subtle, #f9f9f9); border:1px solid rgba(32,0,130,0.08); border-radius:10px; text-decoration:none; color:inherit; transition:all 0.2s;">
                        <i class="fas fa-file" style="color:var(--primary);"></i>
                        <div style="flex:1; font-weight:500; color:var(--text);"><?= htmlspecialchars($file['title']) ?></div>
                        <i class="fas fa-download" style="color:var(--muted, #888);"></i>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($enrollment) && !$isCompleted): ?>
        <div class="mode-card" style="text-align:center; padding:2rem;">
            <p style="color:var(--muted, #666); margin-bottom:1rem;">Finished with this lesson?</p>
            <button id="mark-complete-btn" onclick="markLessonComplete()" style="padding:0.75rem 1.5rem; background:#10b981; color:#fff; border:none; border-radius:8px; font-weight:700; cursor:pointer; font-size:0.95rem; display:inline-flex; align-items:center; gap:0.4rem;">
                <i class="fas fa-check-circle"></i> Mark as Complete
            </button>
            <div id="complete-status" style="margin-top:0.75rem; display:none; color:#10b981; font-weight:600;">
                <i class="fas fa-check"></i> Lesson marked as complete!
            </div>
        </div>
    <?php endif; ?>

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
