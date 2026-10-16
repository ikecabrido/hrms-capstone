<?php
include_once __DIR__ . '/../../classes/Employee.php';
require_once __DIR__ . '/../../classes/LearningPathAccess.php';
require_once dirname(__DIR__, 4) . '/database/db.php';

$employeeClass = new Employee();
$learnerId = (int) ($employeeClass->getEmployeeId() ?? 0);
$requestedPathId = (int) ($_GET['path_id'] ?? 0);

$myPaths = [];
$assignedPath = null;
$items = [];

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $myPathsStmt = $pdo->prepare("SELECT DISTINCT lp.id, lp.title
                                FROM ld_learning_path lp
                                LEFT JOIN ld_learning_path_invitation pi
                                    ON pi.learning_path_id = lp.id AND pi.employee_id = :invited_employee
                                WHERE lp.status = 'active'
                                    AND (lp.assigned_to = :assigned_employee OR pi.status = 'enrolled')
                                ORDER BY lp.updated_at DESC, lp.id DESC");
    $myPathsStmt->execute([
        ':invited_employee' => $learnerId,
        ':assigned_employee' => $learnerId,
    ]);
    $myPaths = $myPathsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get the path assigned to this learner
    $pathFilter = $requestedPathId > 0 ? 'AND lp.id = :requested_path_id' : '';
    $stmt = $pdo->prepare("
        SELECT lp.id, lp.title, lp.description, lp.status, lp.type, lp.is_public, lp.assigned_to, lp.instructor_id, lp.created_at,
               CONCAT(emp.first_name, ' ', emp.last_name) AS instructor_name,
               (SELECT COUNT(*) FROM ld_learning_path_item lpi WHERE lpi.learning_path_id = lp.id) AS item_count
        FROM ld_learning_path lp
        LEFT JOIN em_employees emp ON emp.employee_id = lp.instructor_id
        WHERE lp.status = 'active'
            {$pathFilter}
            AND (lp.assigned_to = :assigned_to
                 OR EXISTS (
                     SELECT 1 FROM ld_learning_path_invitation pi
                     WHERE pi.learning_path_id = lp.id
                         AND pi.employee_id = :invited_to
                         AND pi.status = 'enrolled'
                 ))
        ORDER BY COALESCE((SELECT MAX(pi2.invited_at) FROM ld_learning_path_invitation pi2 WHERE pi2.learning_path_id = lp.id AND pi2.employee_id = :ordered_employee AND pi2.status = 'enrolled'), lp.updated_at) DESC
        LIMIT 1
    ");
    $pathParams = [
        ':assigned_to' => $learnerId,
        ':invited_to' => $learnerId,
        ':ordered_employee' => $learnerId,
    ];
    if ($requestedPathId > 0) {
        $pathParams[':requested_path_id'] = $requestedPathId;
    }
    $stmt->execute($pathParams);
    $assignedPath = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($assignedPath && !LearningPathAccess::canView($pdo, (int) $assignedPath['id'], $learnerId)) {
        $assignedPath = null;
    }

    if ($assignedPath) {
        $itemStmt = $pdo->prepare("
            SELECT lpi.id, lpi.item_type, lpi.reference_id, lpi.order_index, lpi.status AS item_status
            FROM ld_learning_path_item lpi
            WHERE lpi.learning_path_id = :lpid AND lpi.status = 'active'
            ORDER BY lpi.order_index ASC, lpi.id ASC
        ");
        $itemStmt->execute([':lpid' => $assignedPath['id']]);
        $rawItems = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

        $tableMap = [
            'course'           => 'ld_course',
            'module'           => 'ld_module',
            'lesson'           => 'ld_lesson',
            'quiz'             => 'ld_quiz',
            'evaluation'       => 'ld_evaluation',
            'program'          => 'ld_program',
            'video-conference' => 'ld_video_conference',
        ];

        $typeLabels = [
            'course'           => 'Course',
            'module'           => 'Module',
            'lesson'           => 'Lesson',
            'quiz'             => 'Quiz',
            'evaluation'       => 'Evaluation',
            'program'          => 'Program',
            'video-conference' => 'Online Training',
        ];

        $typeIcons = [
            'course'           => 'fa-graduation-cap',
            'module'           => 'fa-cube',
            'lesson'           => 'fa-book-open',
            'quiz'             => 'fa-question-circle',
            'evaluation'       => 'fa-clipboard-check',
            'program'          => 'fa-layer-group',
            'video-conference' => 'fa-video',
        ];

        foreach ($rawItems as $item) {
            $table = $tableMap[$item['item_type']] ?? null;
            $item['label'] = $typeLabels[$item['item_type']] ?? ucfirst($item['item_type']);
            $item['icon'] = $typeIcons[$item['item_type']] ?? 'fa-puzzle-piece';
            $item['title'] = 'Item #' . $item['reference_id'];

            if ($table) {
                $titleStmt = $pdo->prepare("SELECT title FROM {$table} WHERE id = :rid LIMIT 1");
                $titleStmt->execute([':rid' => $item['reference_id']]);
                $fetched = $titleStmt->fetchColumn();
                if ($fetched) {
                    $item['title'] = $fetched;
                }
            }

            switch ($item['item_type']) {
                case 'course':
                    $item['link'] = '?page=learner/study-subpage/course&course_id=' . $item['reference_id'];
                    break;
                case 'program':
                    $item['link'] = '?page=learner/catalog-subpage/program&program_id=' . $item['reference_id'];
                    break;
                case 'video-conference':
                    $item['link'] = '?page=learner/catalog-subpage/video-conference&back=learner/my-learning-path&video_conference_id=' . $item['reference_id'];
                    break;
                default:
                    $item['link'] = '';
            }

            $items[] = $item;
        }

        // Resolve step completion in one query instead of one round-trip per step.
        if ($items) {
            $enrolledStmt = $pdo->prepare("SELECT course_id, status FROM ld_enrollment WHERE learner_id = :lid");
            $enrolledStmt->execute([':lid' => $learnerId]);
            $enrolledByCourse = [];
            foreach ($enrolledStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $enrolledByCourse[(int) $row['course_id']] = $row['status'];
            }

            foreach ($items as &$stepItem) {
                $stepItem['is_complete'] = $stepItem['item_type'] === 'course'
                    && (($enrolledByCourse[(int) $stepItem['reference_id']] ?? '') === 'completed');
            }
            unset($stepItem);
        }
    }
} catch (Throwable $e) {
    // Keep going with empty data
}

$completedSteps = count(array_filter($items, fn($i) => !empty($i['is_complete'])));
$totalSteps = count($items);
?>
<div class="module-content lt-page">
    <div class="home-dashboard">

        <section class="home-hero">
            <div class="home-hero-main">
                <h1>My Learning Path</h1>
                <p><?= $assignedPath ? 'Follow your assigned path, step by step.' : 'Your assigned learning journeys will appear here.' ?></p>
            </div>
            <?php if ($assignedPath): ?>
                <div class="home-hero-aside">
                    <div class="home-hero-chips">
                        <span class="home-hero-chip"><i class="fas fa-list-ol"></i> <?= $totalSteps ?> step<?= $totalSteps !== 1 ? 's' : '' ?></span>
                        <span class="home-hero-chip"><i class="fas fa-check-circle"></i> <?= $completedSteps ?> completed</span>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <?php if (count($myPaths) > 1): ?>
            <div class="lt-switcher" role="navigation" aria-label="My learning paths">
                <?php foreach ($myPaths as $myPath): ?>
                    <a href="?page=learner/my-learning-path&amp;path_id=<?= (int) $myPath['id'] ?>"
                       class="<?= (int) $myPath['id'] === (int) ($assignedPath['id'] ?? 0) ? 'is-active' : '' ?>">
                        <?= htmlspecialchars($myPath['title']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($assignedPath): ?>
            <div class="home-card">
                <div class="home-card-head">
                    <h2 class="home-card-title">
                        <span class="home-card-icon"><i class="fas fa-route"></i></span>
                        <?= htmlspecialchars($assignedPath['title']) ?>
                    </h2>
                    <a class="home-card-link" href="?page=learner/catalog-subpage/learning-path&amp;learning_path_id=<?= (int) $assignedPath['id'] ?>">
                        View full path <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <?php if (!empty($assignedPath['description'])): ?>
                    <p class="lt-skill-desc"><?= nl2br(htmlspecialchars($assignedPath['description'])) ?></p>
                <?php endif; ?>

                <div class="home-row-meta" style="margin-top:0.6rem;">
                    <?php if (!empty($assignedPath['instructor_name'])): ?>
                        <i class="fas fa-user-tie"></i> Designed by <?= htmlspecialchars($assignedPath['instructor_name']) ?>
                        &nbsp;&bull;&nbsp;
                    <?php endif; ?>
                    <i class="fas fa-list-ol"></i> <?= $totalSteps ?> step<?= $totalSteps !== 1 ? 's' : '' ?>
                    &nbsp;&bull;&nbsp;
                    <span class="home-badge is-success"><?= ucfirst((string) $assignedPath['status']) ?></span>
                </div>

                <?php if ($totalSteps > 0): ?>
                    <div class="home-progress" style="max-width:none;margin-top:0.85rem;">
                        <span class="home-progress-track">
                            <span class="home-progress-fill" style="width:<?= (int) round(($completedSteps / $totalSteps) * 100) ?>%"></span>
                        </span>
                        <span class="home-progress-val"><?= (int) round(($completedSteps / $totalSteps) * 100) ?>%</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="home-card">
                <div class="home-card-head">
                    <h2 class="home-card-title">
                        <span class="home-card-icon"><i class="fas fa-shoe-prints"></i></span> Your Path Steps
                    </h2>
                    <span class="home-card-count"><?= $completedSteps ?>/<?= $totalSteps ?> done</span>
                </div>

                <?php if (empty($items)): ?>
                    <div class="home-empty">
                        <i class="fas fa-route"></i>
                        <p>Your learning path has no steps yet. Check back later or contact your instructor.</p>
                    </div>
                <?php else: ?>
                    <ol class="lt-steps">
                        <?php foreach ($items as $idx => $item): ?>
                            <li class="lt-step<?= !empty($item['is_complete']) ? ' is-complete' : '' ?>">
                                <span class="lt-step-marker">
                                    <?= !empty($item['is_complete']) ? '<i class="fas fa-check"></i>' : ($idx + 1) ?>
                                </span>
                                <span class="lt-step-icon"><i class="fas <?= htmlspecialchars($item['icon']) ?>"></i></span>
                                <span class="lt-step-body">
                                    <span class="lt-step-title"><?= htmlspecialchars($item['title']) ?></span>
                                    <span class="lt-step-meta"><?= htmlspecialchars($item['label']) ?></span>
                                </span>
                                <span class="lt-step-trail">
                                    <?php if ($item['link']): ?>
                                        <?php if (!empty($item['is_complete'])): ?>
                                            <span class="home-badge is-success"><i class="fas fa-check"></i> Completed</span>
                                        <?php else: ?>
                                            <a href="<?= htmlspecialchars($item['link']) ?>" class="home-cta">
                                                <i class="fas fa-play"></i> Start
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="home-badge is-muted">No link</span>
                                    <?php endif; ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="home-card">
                <div class="home-empty">
                    <i class="fas fa-route"></i>
                    <p>You don't have a learning path assigned yet. Your instructor or admin can assign one to guide your learning journey.</p>
                    <a class="home-cta" href="?page=learner/catalog"><i class="fas fa-compass"></i> Browse catalog</a>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>
