<?php
/**
 * Shared course navigation sidebar for study-subpage (module, lesson, quiz, course).
 * Expects: $pdo, $courseId, $learnerId, $enrollment
 * Sets: $allModules, $progressPercent, $courseTitle
 */
require_once dirname(__DIR__, 4) . '/classes/progress.php';

$allModules = [];
$progressPercent = 0;
$courseTitle = '';

if (!$pdo || !$courseId) return;

$cStmt = $pdo->prepare("SELECT title FROM ld_course WHERE id = :cid LIMIT 1");
$cStmt->execute([':cid' => $courseId]);
$courseTitle = $cStmt->fetchColumn() ?: '';

if (!empty($enrollment)) {
    $progressObj = new Progress($pdo);
    $progressPercent = $progressObj->getPercentComplete((int) $enrollment['id'], $courseId);
}

$allModStmt = $pdo->prepare("
    SELECT m.id, m.title, m.order_index
    FROM ld_module m
    WHERE m.course_id = :cid AND m.status = 'active'
    ORDER BY m.order_index ASC, m.id ASC
");
$allModStmt->execute([':cid' => $courseId]);
$allModules = $allModStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($allModules as &$am) {
    $lStmt = $pdo->prepare("
        SELECT l.id, l.title, l.content_type, l.order_index,
            (SELECT status FROM ld_progress WHERE enrollment_id = :eid AND item_type = 'lesson' AND reference_id = l.id LIMIT 1) AS progress_status
        FROM ld_lesson l
        WHERE l.module_id = :mid AND l.status = 'active'
        ORDER BY l.order_index ASC, l.id ASC
    ");
    $lStmt->execute([':mid' => $am['id'], ':eid' => $enrollment['id'] ?? 0]);
    $am['lessons'] = $lStmt->fetchAll(PDO::FETCH_ASSOC);

    $qStmt = $pdo->prepare("
        SELECT q.id, q.title,
            (SELECT qa.passed FROM ld_quiz_attempt qa
             WHERE qa.learner_id = :lid AND qa.quiz_id = q.id
             ORDER BY qa.id DESC LIMIT 1) AS attempt_status,
            (SELECT qa.score FROM ld_quiz_attempt qa
             WHERE qa.learner_id = :lid AND qa.quiz_id = q.id
             ORDER BY qa.id DESC LIMIT 1) AS last_score,
            (SELECT COUNT(*) FROM ld_quiz_attempt qa
             WHERE qa.learner_id = :lid AND qa.quiz_id = q.id) AS attempt_count
        FROM ld_quiz q
        WHERE q.module_id = :mid AND q.status = 'active'
        ORDER BY q.id ASC
    ");
    $qStmt->execute([':mid' => $am['id'], ':lid' => $learnerId]);
    $am['quizzes'] = $qStmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($am);
?>

<style>
/* ── Sidebar overlay ──────────────────────────────────────────── */
.study-sidebar-overlay{
    position:fixed;top:var(--header-height,60px);left:0;right:0;bottom:0;
    background:rgba(0,0,0,0.35);z-index:998;
    opacity:0;pointer-events:none;transition:opacity 0.3s ease,left 0.3s ease;
}
.study-sidebar-overlay.visible{opacity:1;pointer-events:auto}

/* ── Sidebar (fixed overlay drawer, slides from left) ─────────── */
.study-sidebar{
    position:fixed;top:var(--header-height,60px);bottom:0;left:var(--sidebar-width,252px);
    width:320px;z-index:999;
    background:var(--surface,#fff);
    border-right:1px solid rgba(32,0,130,0.1);
    box-shadow:4px 0 24px rgba(0,0,0,0.12);
    display:flex;flex-direction:column;overflow:hidden;
    border-radius:0;border:none;padding:0;
    transform:translateX(-100%);visibility:hidden;pointer-events:none;
    transition:transform 0.3s ease,visibility 0.3s ease,left 0.3s ease;
}
.study-sidebar.open{transform:translateX(0);visibility:visible;pointer-events:auto}

/* ── Toggle button ────────────────────────────────────────────── */
.study-toggle-btn{
    position:fixed;top:var(--header-height,60px);left:var(--sidebar-width,252px);
    display:flex;align-items:center;justify-content:center;
    width:28px;height:28px;padding:0;z-index:50;
    background:var(--surface,#fff);border:1px solid rgba(32,0,130,0.1);
    border-radius:0 8px 8px 0;font-size:0.65rem;color:var(--primary);
    cursor:pointer;transition:left 0.3s ease,background 0.2s,box-shadow 0.2s;
    box-shadow:2px 2px 8px rgba(32,0,130,0.08);
}
.study-toggle-btn:hover{background:rgba(32,0,130,0.08);box-shadow:2px 2px 12px rgba(32,0,130,0.15)}

/* ── Sidebar inner styles ─────────────────────────────────────── */
.study-sidebar-header{padding:1.25rem 2.5rem 1rem 1.25rem;border-bottom:1px solid rgba(32,0,130,0.06);flex-shrink:0;position:relative}
.study-sidebar-course-title{font-size:0.95rem;font-weight:700;color:var(--text);margin:0 0 0.5rem;line-height:1.3}
.study-sidebar-progress{margin:0}
.study-sidebar-progress-track{height:4px;background:rgba(32,0,130,0.08);border-radius:99px;overflow:hidden;margin-top:0.35rem}
.study-sidebar-progress-fill{height:100%;background:linear-gradient(90deg,var(--primary),rgba(81,70,183,0.8));border-radius:99px;transition:width 0.5s ease}
.study-sidebar-progress-label{font-size:0.72rem;color:var(--muted,#888);font-weight:500}
.study-sidebar-nav{flex:1;overflow-y:auto;padding:0.5rem 0}
.study-sidebar-module{border-bottom:1px solid rgba(32,0,130,0.04)}
.study-sidebar-module-header{display:flex;align-items:center;gap:0.6rem;padding:0.7rem 1.25rem;cursor:pointer;font-size:0.82rem;font-weight:600;color:var(--text);transition:background 0.15s;user-select:none}
.study-sidebar-module-header:hover{background:rgba(32,0,130,0.03)}
.study-sidebar-module-header.active-module{background:rgba(32,0,130,0.05);color:var(--primary)}
.study-sidebar-module-num{width:24px;height:24px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:0.7rem;font-weight:700;flex-shrink:0;background:rgba(32,0,130,0.06);color:var(--primary)}
.study-sidebar-module-num.active-num{background:var(--primary);color:#fff}
.study-sidebar-module-title{flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.study-sidebar-chevron{font-size:0.6rem;color:var(--muted,#aaa);transition:transform 0.2s}
.study-sidebar-chevron.open{transform:rotate(90deg)}
.study-sidebar-items{display:none;padding:0 0 0.25rem}
.study-sidebar-items.expanded{display:block}
.study-sidebar-item{display:flex;align-items:center;gap:0.5rem;padding:0.45rem 1.25rem 0.45rem 2.5rem;font-size:0.78rem;color:var(--muted,#666);text-decoration:none;transition:all 0.15s;border-left:3px solid transparent}
.study-sidebar-item:hover{background:rgba(32,0,130,0.03);color:var(--text)}
.study-sidebar-item.active-item{color:var(--primary);font-weight:600;background:rgba(32,0,130,0.04);border-left:3px solid var(--primary)}
.study-sidebar-item.completed-item{color:var(--muted,#888)}
.study-sidebar-item-icon{width:20px;text-align:center;font-size:0.7rem;flex-shrink:0}
.study-sidebar-item-icon.done{color:#10b981}
.study-sidebar-item-icon.fail{color:#dc3545}
.study-sidebar-close{
    position:absolute;top:1rem;right:0.75rem;
    width:28px;height:28px;border-radius:8px;
    display:flex;align-items:center;justify-content:center;
    background:transparent;border:none;color:var(--muted,#999);
    cursor:pointer;font-size:0.85rem;transition:all 0.15s;
}
.study-sidebar-close:hover{background:rgba(32,0,130,0.06);color:var(--text)}

/* ── Breadcrumb (shared across study pages) ──────────────────── */
.study-breadcrumb{margin-bottom:1.25rem;display:flex;align-items:center;gap:0.5rem;font-size:0.85rem}
.study-breadcrumb a{color:var(--primary);text-decoration:none;font-weight:600;transition:opacity 0.2s}
.study-breadcrumb a:hover{opacity:0.8}
.study-breadcrumb .sep{color:var(--muted,#ccc);font-size:0.65rem}
.study-breadcrumb .current{color:var(--muted,#666);font-weight:500}
</style>

<!-- Overlay -->
<div class="study-sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar drawer -->
<aside class="study-sidebar" id="studySidebar">
    <div class="study-sidebar-header">
        <button class="study-sidebar-close" id="sidebarCloseBtn" title="Close panel">
            <i class="fas fa-times"></i>
        </button>
        <a href="?page=learner/study-subpage/course&course_id=<?= $courseId ?>" style="text-decoration:none;display:block;">
            <div class="study-sidebar-course-title"><?= htmlspecialchars($courseTitle) ?></div>
        </a>
        <?php if (!empty($enrollment)): ?>
            <div class="study-sidebar-progress">
                <div class="study-sidebar-progress-label"><?= round($progressPercent) ?>% complete</div>
                <div class="study-sidebar-progress-track">
                    <div class="study-sidebar-progress-fill" style="width:<?= $progressPercent ?>%;"></div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <nav class="study-sidebar-nav">
        <?php foreach ($allModules as $mIdx => $am):
            $isCurrentModule = ($am['id'] == $moduleId);
        ?>
            <div class="study-sidebar-module">
                <div class="study-sidebar-module-header <?= $isCurrentModule ? 'active-module' : '' ?>" onclick="studyToggleModule(this)">
                    <span class="study-sidebar-module-num <?= $isCurrentModule ? 'active-num' : '' ?>"><?= $mIdx + 1 ?></span>
                    <span class="study-sidebar-module-title"><?= htmlspecialchars($am['title']) ?></span>
                    <i class="fas fa-chevron-right study-sidebar-chevron <?= $isCurrentModule ? 'open' : '' ?>"></i>
                </div>
                <div class="study-sidebar-items <?= $isCurrentModule ? 'expanded' : '' ?>">
                    <?php foreach ($am['lessons'] as $al):
                        $isActive = ($currentPageType === 'lesson' && $currentPageId == $al['id']);
                        $lessonDone = $al['progress_status'] === 'completed';
                    ?>
                        <a href="?page=learner/study-subpage/lesson&lesson_id=<?= $al['id'] ?>&course_id=<?= $courseId ?>" class="study-sidebar-item <?= $isActive ? 'active-item' : '' ?> <?= $lessonDone ? 'completed-item' : '' ?>">
                            <span class="study-sidebar-item-icon <?= $lessonDone ? 'done' : '' ?>">
                                <?php if ($lessonDone): ?><i class="fas fa-check-circle"></i><?php else: ?><i class="fas fa-file-alt"></i><?php endif; ?>
                            </span>
                            <span style="flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($al['title']) ?></span>
                        </a>
                    <?php endforeach; ?>
                    <?php foreach ($am['quizzes'] as $aq):
                        $isActive = ($currentPageType === 'quiz' && $currentPageId == $aq['id']);
                        $quizDone = $aq['attempt_status'] == 1;
                        $quizScore = $aq['last_score'] !== null ? round($aq['last_score'], 1) : null;
                        $quizAttempted = $aq['attempt_count'] > 0;
                    ?>
                        <a href="?page=learner/study-subpage/quiz&quiz_id=<?= $aq['id'] ?>&course_id=<?= $courseId ?>" class="study-sidebar-item <?= $isActive ? 'active-item' : '' ?> <?= $quizDone ? 'completed-item' : '' ?>">
                            <span class="study-sidebar-item-icon <?= $quizDone ? 'done' : ($quizAttempted && !$quizDone ? 'fail' : '') ?>">
                                <?php if ($quizDone): ?><i class="fas fa-check-circle"></i><?php elseif ($quizAttempted): ?><i class="fas fa-times-circle"></i><?php else: ?><i class="fas fa-question-circle"></i><?php endif; ?>
                            </span>
                            <span style="flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($aq['title']) ?></span>
                            <?php if ($quizScore !== null): ?>
                                <span style="font-size:0.68rem;font-weight:600;padding:0.1rem 0.4rem;border-radius:99px;<?= $quizDone ? 'background:rgba(16,185,129,0.1);color:#10b981' : 'background:rgba(220,53,69,0.1);color:#dc3545' ?>"><?= $quizScore ?>%</span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </nav>
</aside>

<!-- Toggle button -->
<button class="study-toggle-btn" id="studyToggleBtn" onclick="studyToggleSidebar()" title="Toggle Course Panel">
    <i class="fas fa-chevron-right"></i>
</button>
