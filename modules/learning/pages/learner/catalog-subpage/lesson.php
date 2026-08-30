<?php
include_once __DIR__ . '/../../../classes/Employee.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

$employeeClass = new Employee();
$learnerId = (int) ($employeeClass->getEmployeeId() ?? 0);

$lessonId = (int) ($_GET['lesson_id'] ?? 0);
$lesson = null;

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Fetch lesson with module and course info
    $stmt = $pdo->prepare("
        SELECT l.id, l.title, l.content_type, l.content_body, l.video_url, l.order_index, l.created_at,
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
        // Fetch attached files
        $fileStmt = $pdo->prepare("
            SELECT id, file_path, title
            FROM ld_lesson_file
            WHERE lesson_id = :lid
            ORDER BY id ASC
        ");
        $fileStmt->execute([':lid' => $lessonId]);
        $files = $fileStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    $lesson = null;
}

if (!$lesson) {
    echo '<div class="module-content"><div class="mode-card"><h2>Lesson Not Found</h2><p>The lesson you are looking for does not exist or is no longer available.</p>';
    echo '<a href="?page=learner/catalog" style="display:inline-block; margin-top:1rem; padding:0.6rem 1.2rem; background:var(--primary); color:#fff; border-radius:6px; text-decoration:none;">Back to Catalog</a></div></div>';
    return;
}

// Determine content type icon
$typeIcon = match($lesson['content_type']) {
    'video' => 'fa-video',
    'text'  => 'fa-file-alt',
    'file'  => 'fa-paperclip',
    'mixed' => 'fa-layer-group',
    default => 'fa-book-open',
};
$typeLabel = ucfirst($lesson['content_type']);

// Extract YouTube video ID if it's a YouTube URL
$youtubeId = null;
if ($lesson['video_url']) {
    if (preg_match('#(?:youtube\.com/(?:watch\?.*v=|embed/|v/)|youtu\.be/)([a-zA-Z0-9_-]{11})#', $lesson['video_url'], $m)) {
        $youtubeId = $m[1];
    }
}
?>

<div class="module-content">
    <!-- Breadcrumb -->
    <div style="margin-bottom:1.5rem; display:flex; align-items:center; gap:0.5rem; font-size:0.9rem; flex-wrap:wrap;">
        <a href="?page=learner/catalog" style="color:var(--primary); text-decoration:none; font-weight:500;">Catalog</a>
        <i class="fas fa-chevron-right" style="color:#ccc; font-size:0.7rem;"></i>
        <a href="?page=learner/catalog-subpage/course&course_id=<?= $lesson['course_id'] ?>" style="color:var(--primary); text-decoration:none; font-weight:500;"><?= htmlspecialchars($lesson['course_title']) ?></a>
        <i class="fas fa-chevron-right" style="color:#ccc; font-size:0.7rem;"></i>
        <a href="?page=learner/catalog-subpage/module&module_id=<?= $lesson['module_id'] ?>" style="color:var(--primary); text-decoration:none; font-weight:500;"><?= htmlspecialchars($lesson['module_title']) ?></a>
        <i class="fas fa-chevron-right" style="color:#ccc; font-size:0.7rem;"></i>
        <span style="color:#666;">Lesson <?= $lesson['order_index'] + 1 ?></span>
    </div>

    <!-- Lesson Header -->
    <div class="mode-card" style="margin-bottom:1.5rem;">
        <div style="display:flex; gap:1rem; align-items:center; margin-bottom:1rem; flex-wrap:wrap;">
            <span class="pill" style="background:var(--primary); color:#fff;"><i class="fas <?= $typeIcon ?>" style="margin-right:4px;"></i><?= $typeLabel ?></span>
            <span class="pill">Module <?= $lesson['module_order'] + 1 ?></span>
        </div>
        <h1 style="margin:0 0 0.5rem 0; font-size:1.6rem; color:#222;"><?= htmlspecialchars($lesson['title']) ?></h1>
    </div>

    <?php if ($youtubeId): ?>
        <!-- YouTube Embed -->
        <div class="mode-card" style="margin-bottom:1.5rem;">
            <h2 style="margin-bottom:0.75rem;"><i class="fab fa-youtube" style="color:#dc3545; margin-right:0.4rem;"></i>Video Lesson</h2>
            <div style="position:relative; padding-bottom:56.25%; height:0; overflow:hidden; border-radius:10px; background:#000;">
                <iframe src="https://www.youtube.com/embed/<?= htmlspecialchars($youtubeId) ?>" style="position:absolute; top:0; left:0; width:100%; height:100%; border:none;" allowfullscreen allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
            </div>
        </div>
    <?php elseif ($lesson['video_url']): ?>
        <!-- External Video Link -->
        <div class="mode-card" style="margin-bottom:1.5rem;">
            <h2 style="margin-bottom:0.75rem;"><i class="fas fa-video" style="color:#dc3545; margin-right:0.4rem;"></i>Video Lesson</h2>
            <a href="<?= htmlspecialchars($lesson['video_url']) ?>" target="_blank" rel="noopener" style="display:inline-flex; align-items:center; gap:0.5rem; padding:0.75rem 1.5rem; background:#dc3545; color:#fff; border-radius:8px; text-decoration:none; font-weight:600;">
                <i class="fas fa-external-link-alt"></i> Open Video
            </a>
        </div>
    <?php endif; ?>

    <?php if ($lesson['content_body']): ?>
        <!-- Lesson Content -->
        <div class="mode-card" style="margin-bottom:1.5rem;">
            <h2 style="margin-bottom:0.75rem;"><i class="fas fa-file-alt" style="color:var(--primary); margin-right:0.4rem;"></i>Lesson Content</h2>
            <div style="line-height:1.8; color:#333; font-size:1rem;">
                <?= $lesson['content_body'] ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($files)): ?>
        <!-- Attached Files -->
        <div class="mode-card" style="margin-bottom:1.5rem;">
            <h2 style="margin-bottom:0.75rem;"><i class="fas fa-paperclip" style="color:#6c757d; margin-right:0.4rem;"></i>Attached Files</h2>
            <div style="display:flex; flex-direction:column; gap:0.5rem;">
                <?php foreach ($files as $file): ?>
                    <a href="<?= htmlspecialchars($file['file_path']) ?>" target="_blank" rel="noopener" style="display:flex; align-items:center; gap:0.75rem; padding:0.85rem 1.25rem; background:#f9f9f9; border:1px solid #e8e8e8; border-radius:8px; text-decoration:none; color:inherit; transition:border-color 0.2s;" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='#e8e8e8'">
                        <i class="fas fa-file" style="color:var(--primary); font-size:1.1rem;"></i>
                        <div style="flex:1;">
                            <div style="font-weight:500; color:#222;"><?= htmlspecialchars($file['title']) ?></div>
                        </div>
                        <i class="fas fa-download" style="color:#999;"></i>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Enroll CTA -->
    <div class="mode-card" style="text-align:center; padding:2rem;">
        <h2 style="margin-bottom:0.5rem;">Want to take this lesson?</h2>
        <p style="color:#666; margin:0 0 1rem 0;">Enroll in the full course to track your progress and complete all lessons.</p>
        <a href="?page=learner/catalog-subpage/course&course_id=<?= $lesson['course_id'] ?>" style="display:inline-flex; align-items:center; gap:0.5rem; padding:0.75rem 1.5rem; background:var(--primary); color:#fff; border-radius:8px; text-decoration:none; font-weight:600;">
            <i class="fas fa-graduation-cap"></i> View Course Details
        </a>
    </div>
</div>
