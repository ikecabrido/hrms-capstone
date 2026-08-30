<?php
include_once __DIR__ . '/../../../classes/Employee.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

$employeeClass = new Employee();
$courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;
$course = null;
$modules = [];
$evaluation = null;

if ($courseId > 0) {
    try {
        $pdo = (new Database())->getConnection();

        $stmt = $pdo->prepare("SELECT * FROM ld_course WHERE id = :id");
        $stmt->execute([':id' => $courseId]);
        $course = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($course) {
            // Fetch modules
            $modStmt = $pdo->prepare("SELECT * FROM ld_module WHERE course_id = :cid AND status = 'active' ORDER BY order_index ASC");
            $modStmt->execute([':cid' => $courseId]);
            $modules = $modStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($modules as &$mod) {
                // Lessons
                $lesStmt = $pdo->prepare("SELECT * FROM ld_lesson WHERE module_id = :mid AND status = 'active' ORDER BY order_index ASC");
                $lesStmt->execute([':mid' => $mod['id']]);
                $mod['_lessons'] = $lesStmt->fetchAll(PDO::FETCH_ASSOC);

                // Module quizzes
                $quizStmt = $pdo->prepare("SELECT * FROM ld_quiz WHERE module_id = :mid AND status = 'active' ORDER BY id ASC");
                $quizStmt->execute([':mid' => $mod['id']]);
                $mod['_quizzes'] = $quizStmt->fetchAll(PDO::FETCH_ASSOC);

                // Count questions per quiz
                foreach ($mod['_quizzes'] as &$qz) {
                    $qStmt = $pdo->prepare("SELECT COUNT(*) FROM ld_quiz_question WHERE item_type = 'quiz' AND reference_id = :qid");
                    $qStmt->execute([':qid' => $qz['id']]);
                    $qz['_question_count'] = (int) $qStmt->fetchColumn();
                }
                unset($qz);
            }
            unset($mod);

            // Evaluation
            $evalStmt = $pdo->prepare("SELECT * FROM ld_evaluation WHERE course_id = :cid AND status = 'active' LIMIT 1");
            $evalStmt->execute([':cid' => $courseId]);
            $evaluation = $evalStmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (Throwable $e) {
        // silently fail
    }
}
?>
<div class="module-content">
    <div class="toolbar">
        <div class="toolbar-actions" style="display:flex; gap:0.5rem; align-items:center;">
            <a href="?page=instructor/elearning-subpage/course-structure&course_id=<?= $courseId ?>" class="toolbar-add-btn" title="Back to Structure Builder" style="font-size:0.85em;">❮ Back to Builder</a>
            <?php if ($course && $course['status'] === 'draft'): ?>
            <span style="padding:0.4rem 0.8rem; background:rgba(245,158,11,0.1); color:#d97706; border-radius:999px; font-size:0.8rem; font-weight:700;">DRAFT — Not visible to learners</span>
            <?php elseif ($course && $course['status'] === 'active'): ?>
            <span style="padding:0.4rem 0.8rem; background:rgba(16,185,129,0.1); color:#10b981; border-radius:999px; font-size:0.8rem; font-weight:700;">ACTIVE — Visible to learners</span>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!$course): ?>
    <div style="text-align:center; padding:4rem 2rem; color:#999;">
        <div style="font-size:3rem; margin-bottom:1rem;"></div>
        <h3 style="color:var(--text);">No Course Selected</h3>
        <p>Add <code>?course_id=X</code> to the URL to preview a course.</p>
    </div>
    <?php else: ?>

    <!-- Course Hero -->
    <div style="margin-top:1rem; padding:2rem; background:linear-gradient(135deg, rgba(32,0,130,0.08), rgba(81,70,183,0.05)); border:1px solid rgba(32,0,130,0.1); border-radius:16px; position:relative; overflow:hidden;">
        <div style="position:absolute; top:-20px; right:-20px; font-size:6rem; opacity:0.05; color:var(--primary);"></div>
        <div style="position:relative; z-index:1;">
            <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.5rem;">
                <span style="padding:0.3rem 0.7rem; background:var(--primary); color:var(--surface); border-radius:999px; font-size:0.7rem; font-weight:700; text-transform:uppercase;"><?= htmlspecialchars($course['category'] ?? 'Course') ?></span>
                <span style="padding:0.3rem 0.7rem; background:rgba(32,0,130,0.08); color:var(--primary); border-radius:999px; font-size:0.7rem; font-weight:700;"><?= ucfirst(htmlspecialchars($course['status'])) ?></span>
            </div>
            <h1 style="margin:0; font-size:1.8rem; color:var(--text); line-height:1.3;"><?= htmlspecialchars($course['title']) ?></h1>
            <?php if ($course['description']): ?>
            <p style="margin:0.75rem 0 0; color:#555; font-size:1rem; line-height:1.6; max-width:700px;"><?= nl2br(htmlspecialchars($course['description'])) ?></p>
            <?php endif; ?>

            <!-- Course stats -->
            <div style="display:flex; gap:1.5rem; margin-top:1.25rem; flex-wrap:wrap;">
                <div style="display:flex; align-items:center; gap:0.4rem; font-size:0.9rem; color:var(--text);">
                    <span style="font-size:1.1rem;"></span>
                    <strong><?= count($modules) ?></strong> Modules
                </div>
                <div style="display:flex; align-items:center; gap:0.4rem; font-size:0.9rem; color:var(--text);">
                    <span style="font-size:1.1rem;"></span>
                    <strong><?= array_sum(array_map(function($m) { return count($m['_lessons']); }, $modules)) ?></strong> Lessons
                </div>
                <div style="display:flex; align-items:center; gap:0.4rem; font-size:0.9rem; color:var(--text);">
                    <span style="font-size:1.1rem;">❓</span>
                    <strong><?= array_sum(array_map(function($m) { return count($m['_quizzes']); }, $modules)) ?></strong> Quizzes
                </div>
                <?php if ($evaluation): ?>
                <div style="display:flex; align-items:center; gap:0.4rem; font-size:0.9rem; color:var(--text);">
                    <span style="font-size:1.1rem;">✅</span>
                    <strong>1</strong> Evaluation
                </div>
                <?php endif; ?>
                <?php if ($course['start_date']): ?>
                <div style="display:flex; align-items:center; gap:0.4rem; font-size:0.9rem; color:#666;">
                    <span style="font-size:1.1rem;"></span>
                    Starts <?= htmlspecialchars($course['start_date']) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Course Content Preview -->
    <div style="margin-top:2rem;">
        <h2 style="margin:0 0 1rem 0; color:var(--text); font-size:1.3rem;"> Course Structure</h2>

        <?php if (empty($modules)): ?>
        <div style="text-align:center; padding:3rem; border:2px dashed #ddd; border-radius:12px; color:#999;">
            <p>No modules yet. Add content in the Structure Builder.</p>
        </div>
        <?php else: ?>

        <div style="display:flex; flex-direction:column; gap:1rem;">
            <?php $moduleNum = 0; ?>
            <?php foreach ($modules as $mod): ?>
            <?php $moduleNum++; ?>
            <div style="border:1px solid rgba(32,0,130,0.1); border-radius:12px; overflow:hidden; background:#fff;">
                <!-- Module Header -->
                <div style="padding:1rem 1.25rem; background:linear-gradient(135deg, rgba(32,0,130,0.05), rgba(81,70,183,0.03)); display:flex; align-items:center; gap:0.75rem;">
                    <span style="width:32px; height:32px; display:flex; align-items:center; justify-content:center; background:var(--primary); color:var(--surface); border-radius:8px; font-weight:800; font-size:0.85rem; flex-shrink:0;"><?= $moduleNum ?></span>
                    <div style="flex:1;">
                        <h3 style="margin:0; color:var(--text); font-size:1.05rem;"><?= htmlspecialchars($mod['title']) ?></h3>
                        <?php if ($mod['description']): ?>
                        <p style="margin:0.2rem 0 0; color:#666; font-size:0.85rem;"><?= htmlspecialchars(mb_substr($mod['description'], 0, 120)) ?><?= strlen($mod['description']) > 120 ? '...' : '' ?></p>
                        <?php endif; ?>
                    </div>
                    <span style="font-size:0.75rem; color:#999; white-space:nowrap;"><?= count($mod['_lessons']) ?> lesson<?= count($mod['_lessons']) !== 1 ? 's' : '' ?></span>
                </div>

                <!-- Lessons -->
                <?php if (!empty($mod['_lessons'])): ?>
                <div style="padding:0.5rem 1.25rem;">
                    <?php foreach ($mod['_lessons'] as $les): ?>
                    <div style="display:flex; align-items:center; gap:0.6rem; padding:0.6rem 0; border-bottom:1px solid rgba(32,0,130,0.05);">
                        <?php
                        $typeIcons = ['text' => '', 'video' => '', 'file' => '', 'mixed' => ''];
                        $icon = $typeIcons[$les['content_type']] ?? '';
                        ?>
                        <span style="font-size:1rem;"><?= $icon ?></span>
                        <span style="flex:1; font-size:0.9rem; color:var(--text); font-weight:500;"><?= htmlspecialchars($les['title']) ?></span>
                        <span style="padding:0.2rem 0.5rem; background:rgba(59,130,246,0.06); color:#3b82f6; border-radius:999px; font-size:0.7rem; font-weight:600;"><?= ucfirst($les['content_type']) ?></span>
                        <?php if ($les['video_url']): ?>
                        <span style="font-size:0.75rem; color:#999;"> Has video</span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Module Quizzes -->
                <?php if (!empty($mod['_quizzes'])): ?>
                <div style="padding:0.5rem 1.25rem 0.75rem; border-top:1px dashed rgba(245,158,11,0.2);">
                    <?php foreach ($mod['_quizzes'] as $qz): ?>
                    <div style="display:flex; align-items:center; gap:0.6rem; padding:0.5rem 0;">
                        <span style="font-size:1rem;">❓</span>
                        <span style="flex:1; font-size:0.9rem; color:var(--text); font-weight:500;"><?= htmlspecialchars($qz['title']) ?></span>
                        <span style="font-size:0.75rem; color:#999;"><?= $qz['_question_count'] ?> questions</span>
                        <?php if ($qz['duration_seconds']): ?>
                        <span style="font-size:0.75rem; color:#999;">⏱ <?= round($qz['duration_seconds'] / 60) ?>m</span>
                        <?php endif; ?>
                        <?php if ($qz['passing_score']): ?>
                        <span style="font-size:0.75rem; color:#999;">Pass: <?= $qz['passing_score'] ?>%</span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Evaluation -->
        <?php if ($evaluation): ?>
        <div style="margin-top:1rem; border:2px solid rgba(16,185,129,0.2); border-radius:12px; padding:1.25rem; background:rgba(16,185,129,0.02);">
            <div style="display:flex; align-items:center; gap:0.6rem;">
                <span style="font-size:1.2rem;">✅</span>
                <div style="flex:1;">
                    <h3 style="margin:0; color:var(--text); font-size:1rem;"><?= htmlspecialchars($evaluation['title']) ?></h3>
                    <p style="margin:0.2rem 0 0; color:#666; font-size:0.85rem;">Final course evaluation — taken after completing all content.</p>
                </div>
                <div style="display:flex; gap:0.5rem; font-size:0.75rem; color:#999;">
                    <?php if ($evaluation['duration_seconds']): ?><span>⏱ <?= round($evaluation['duration_seconds'] / 60) ?>m</span><?php endif; ?>
                    <?php if ($evaluation['passing_score']): ?><span>Pass: <?= $evaluation['passing_score'] ?>%</span><?php endif; ?>
                    <?php if ($evaluation['max_attempts']): ?><span>Max <?= $evaluation['max_attempts'] ?> attempts</span><?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php endif; ?>
    </div>

    <!-- Learner CTA Preview -->
    <div style="margin-top:2rem; padding:1.5rem; background:#f9fafb; border:1px solid #e5e7eb; border-radius:12px; text-align:center;">
        <p style="margin:0; color:#666; font-size:0.9rem;">This is how the course appears to learners.</p>
        <div style="margin-top:1rem; display:flex; gap:0.75rem; justify-content:center; flex-wrap:wrap;">
            <a href="?page=instructor/elearning-subpage/course-structure&course_id=<?= $courseId ?>" style="padding:0.7rem 1.5rem; background:var(--primary); color:var(--surface); border-radius:8px; font-weight:700; text-decoration:none;">️ Edit Structure</a>
            <?php if ($course['status'] === 'draft'): ?>
            <button style="padding:0.7rem 1.5rem; background:#10b981; color:#fff; border:none; border-radius:8px; font-weight:700; cursor:pointer;" onclick="if(confirm('Publish this course? Learners will be able to see and enroll in it.')){fetch('pages/instructor/elearning-subpage/ajax/publish-course.php',{method:'POST',headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},body:JSON.stringify({id:<?= $courseId ?>})}).then(r=>r.json()).then(d=>{if(d.success){if(window.showToast)window.showToast('Course published','success');setTimeout(function(){location.reload()},800);}else{if(window.showToast)window.showToast(d.message||'Failed','error');}}).catch(function(){if(window.showToast)window.showToast('Network error','error');});}"> Publish Course</button>
            <?php endif; ?>
        </div>
    </div>

    <?php endif; ?>
</div>
