<?php
include_once __DIR__ . '/../../../classes/Employee.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

$employeeClass = new Employee();
$learnerId = (int) ($employeeClass->getEmployeeId() ?? 0);

$courseId = (int) ($_GET['course_id'] ?? 0);
$comments = [];
$course = null;

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $stmt = $pdo->prepare("SELECT id, title FROM ld_course WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $courseId]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($course) {
        $cStmt = $pdo->prepare("
            SELECT cm.id, cm.comment_text, cm.created_at,
                   CONCAT(emp.first_name, ' ', emp.last_name) AS author_name,
                   CASE WHEN cm.learner_id = :lid THEN 1 ELSE 0 END AS is_own
            FROM ld_comment cm
            LEFT JOIN em_employees emp ON emp.employee_id = cm.learner_id
            WHERE cm.course_id = :cid AND cm.parent_id IS NULL
            ORDER BY cm.created_at DESC
        ");
        $cStmt->execute([':lid' => $learnerId, ':cid' => $courseId]);
        $comments = $cStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    $comments = [];
}
?>

<div class="module-content">
    <div style="margin-bottom:1.5rem; display:flex; align-items:center; gap:0.5rem; font-size:0.9rem; flex-wrap:wrap;">
        <a href="?page=learner/study" style="color:var(--primary); text-decoration:none; font-weight:500;">My Study</a>
        <i class="fas fa-chevron-right" style="color:#ccc; font-size:0.7rem;"></i>
        <a href="?page=learner/study-subpage/course&course_id=<?= $courseId ?>" style="color:var(--primary); text-decoration:none; font-weight:500;"><?= htmlspecialchars($course['title'] ?? 'Course') ?></a>
        <i class="fas fa-chevron-right" style="color:#ccc; font-size:0.7rem;"></i>
        <span style="color:#666;">Discussion</span>
    </div>

    <div class="mode-card" style="margin-bottom:1.5rem;">
        <h2><i class="fas fa-comments" style="color:var(--primary); margin-right:0.4rem;"></i>Course Discussion</h2>

        <!-- New comment form -->
        <div style="margin-top:1rem; margin-bottom:1.5rem;">
            <textarea id="new-comment" rows="3" placeholder="Ask a question or start a discussion..." style="width:100%; padding:0.75rem 1rem; border:1px solid rgba(32,0,130,0.15); border-radius:8px; font-family:inherit; font-size:0.95rem; resize:vertical;"></textarea>
            <div style="margin-top:0.5rem; text-align:right;">
                <button onclick="postComment()" style="padding:0.6rem 1.25rem; background:var(--primary); color:#fff; border:none; border-radius:8px; font-weight:600; cursor:pointer;">Post Comment</button>
            </div>
        </div>

        <!-- Comments list -->
        <div id="comments-list">
            <?php if (empty($comments)): ?>
                <div style="text-align:center; padding:2rem; color:#999;">No comments yet. Be the first to start a discussion!</div>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                    <div style="padding:1rem; background:var(--surface, #f9f9f9); border-radius:10px; margin-bottom:0.75rem; border-left:3px solid <?= $comment['is_own'] ? 'var(--primary)' : '#ccc' ?>;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
                            <div style="display:flex; align-items:center; gap:0.5rem;">
                                <div style="width:28px; height:28px; border-radius:50%; background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center; font-size:0.7rem; font-weight:700;"><?= strtoupper(substr($comment['author_name'], 0, 1)) ?></div>
                                <span style="font-weight:600; font-size:0.9rem;"><?= htmlspecialchars($comment['author_name']) ?></span>
                            </div>
                            <span style="font-size:0.8rem; color:var(--text-muted);"><?= date('M j, Y g:i A', strtotime($comment['created_at'])) ?></span>
                        </div>
                        <p style="margin:0; color:inherit; line-height:1.6;"><?= nl2br(htmlspecialchars($comment['comment_text'])) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function postComment() {
    var textarea = document.getElementById('new-comment');
    var text = textarea.value.trim();
    if (!text) return;

    var btn = event.target;
    btn.disabled = true;
    btn.textContent = 'Posting...';

    fetch('pages/learner/catalog-subpage/ajax/engagement/ask-question.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'course_id=<?= $courseId ?>&comment=' + encodeURIComponent(text)
    }).then(function(r) { return r.json(); }).then(function(data) {
        if (data.success) {
            textarea.value = '';
            location.reload();
        } else {
            alert(data.message || 'Failed to post comment');
            btn.disabled = false;
            btn.textContent = 'Post Comment';
        }
    }).catch(function() {
        btn.disabled = false;
        btn.textContent = 'Post Comment';
    });
}
</script>
