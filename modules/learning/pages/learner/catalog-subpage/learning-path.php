<?php
include_once __DIR__ . '/../../../classes/Employee.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

$employeeClass = new Employee();
$learnerId = (int) ($employeeClass->getEmployeeId() ?? 0);

$learningPathId = (int) ($_GET['learning_path_id'] ?? 0);
$learningPath = null;
$items = [];

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Fetch learning path with instructor
    $stmt = $pdo->prepare("
        SELECT lp.id, lp.title, lp.description, lp.status, lp.created_at,
               CONCAT(emp.first_name, ' ', emp.last_name) AS instructor_name
        FROM ld_learning_path lp
        LEFT JOIN em_employees emp ON emp.employee_id = lp.instructor_id
        WHERE lp.id = :id AND lp.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([':id' => $learningPathId]);
    $learningPath = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($learningPath) {
        // Fetch path items in order with resolved titles
        $itemStmt = $pdo->prepare("
            SELECT lpi.id, lpi.item_type, lpi.reference_id, lpi.order_index, lpi.status
            FROM ld_learning_path_item lpi
            WHERE lpi.learning_path_id = :lpid AND lpi.status = 'active'
            ORDER BY lpi.order_index ASC, lpi.id ASC
        ");
        $itemStmt->execute([':lpid' => $learningPathId]);
        $rawItems = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

        // Resolve titles for each item
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
            'video-conference' => 'Video Conference',
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
                $fetched = $titleStmt->fetch(PDO::FETCH_COLUMN);
                if ($fetched) {
                    $item['title'] = $fetched;
                }
            }

            $items[] = $item;
        }
    }
} catch (Throwable $e) {
    $learningPath = null;
}

if (!$learningPath) {
    echo '<div class="module-content"><div class="mode-card"><h2>Learning Path Not Found</h2><p>The learning path you are looking for does not exist or is no longer available.</p>';
    echo '</div></div>';
    return;
}

$courseCount = count(array_filter($items, fn($i) => $i['item_type'] === 'course'));
$quizCount = count(array_filter($items, fn($i) => $i['item_type'] === 'quiz'));
$lessonCount = count(array_filter($items, fn($i) => $i['item_type'] === 'lesson'));
?>

<div class="module-content">
    <div style="margin-bottom:1.5rem;display:flex;gap:1rem;flex-wrap:wrap;">
        
        
    </div>

    <!-- Learning Path Header -->
    <div class="mode-card" style="margin-bottom:1.5rem;">
        <div style="display:flex; gap:2rem; align-items:flex-start; flex-wrap:wrap;">
            <div style="width:80px; height:80px; border-radius:14px; background:linear-gradient(135deg, rgba(180,83,9,0.85), rgba(245,158,11,0.7)); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i class="fas fa-route" style="color:#fff; font-size:2rem;"></i>
            </div>
            <div style="flex:1; min-width:300px;">
                <div style="display:flex; gap:0.5rem; margin-bottom:0.75rem; flex-wrap:wrap;">
                    <span class="pill" style="background:linear-gradient(135deg, rgba(180,83,9,0.85), rgba(245,158,11,0.7)); color:#fff;">Learning Path</span>
                    <span class="pill" style="background:#d4edda; color:#155724;">Active</span>
                </div>
                <h1 style="margin:0 0 0.75rem 0; font-size:1.8rem; color:#222;"><?= htmlspecialchars($learningPath['title']) ?></h1>
                <?php if ($learningPath['description']): ?>
                    <p style="color:#555; line-height:1.7; margin:0 0 1.5rem 0;"><?= nl2br(htmlspecialchars($learningPath['description'])) ?></p>
                <?php endif; ?>

                <div style="display:flex; gap:1.5rem; flex-wrap:wrap; align-items:center;">
                    <?php if (!empty($learningPath['instructor_name'])): ?>
                        <div style="display:flex; align-items:center; gap:0.5rem;">
                            <i class="fas fa-user-tie" style="color:var(--primary);"></i>
                            <div>
                                <div style="font-size:0.75rem; color:#999; text-transform:uppercase;">Designed By</div>
                                <div style="font-weight:600; color:#333;"><?= htmlspecialchars($learningPath['instructor_name']) ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div style="display:flex; align-items:center; gap:0.5rem;">
                        <i class="fas fa-list-ol" style="color:#6c757d;"></i>
                        <div>
                            <div style="font-size:0.75rem; color:#999; text-transform:uppercase;">Total Steps</div>
                            <div style="font-weight:600; color:#333;"><?= count($items) ?></div>
                        </div>
                    </div>
                    <?php if ($courseCount > 0): ?>
                        <div style="display:flex; align-items:center; gap:0.5rem;">
                            <i class="fas fa-graduation-cap" style="color:#6c757d;"></i>
                            <div>
                                <div style="font-size:0.75rem; color:#999; text-transform:uppercase;">Courses</div>
                                <div style="font-weight:600; color:#333;"><?= $courseCount ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($quizCount > 0): ?>
                        <div style="display:flex; align-items:center; gap:0.5rem;">
                            <i class="fas fa-question-circle" style="color:#6c757d;"></i>
                            <div>
                                <div style="font-size:0.75rem; color:#999; text-transform:uppercase;">Quizzes</div>
                                <div style="font-weight:600; color:#333;"><?= $quizCount ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="margin-top:1.5rem; display:flex; gap:1rem; flex-wrap:wrap;">
                    
                    
                </div>
            </div>
        </div>
    </div>

    <!-- Path Steps -->
    <div class="mode-card">
        <h2 style="margin-bottom:0.5rem;">Path Steps</h2>
        <p style="color:#666; margin:0 0 1.5rem 0;">Follow this structured sequence to complete the learning path.</p>

        <?php if (empty($items)): ?>
            <div style="text-align:center; padding:3rem; color:#999;">
                <i class="fas fa-route" style="font-size:2rem; margin-bottom:0.75rem; display:block;"></i>
                This learning path has no items yet.
            </div>
        <?php else: ?>
            <div style="position:relative; padding-left:2rem;">
                <!-- Vertical line -->
                <div style="position:absolute; left:17px; top:0; bottom:0; width:2px; background:#e0e0e0;"></div>

                <?php foreach ($items as $idx => $item): ?>
                    <div style="position:relative; margin-bottom:1.5rem;">
                        <!-- Step number circle -->
                        <div style="position:absolute; left:-2rem; top:0; width:36px; height:36px; border-radius:50%; background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.85rem; z-index:1;">
                            <?= $idx + 1 ?>
                        </div>

                        <div style="background:#f9f9f9; border:1px solid #e8e8e8; border-radius:10px; padding:1rem 1.25rem; margin-left:0.5rem;">
                            <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
                                <i class="fas <?= htmlspecialchars($item['icon']) ?>" style="color:var(--primary); font-size:1.1rem;"></i>
                                <div style="flex:1; min-width:200px;">
                                    <h3 style="margin:0 0 0.2rem 0; font-size:0.95rem; color:#222;"><?= htmlspecialchars($item['title']) ?></h3>
                                    <span style="font-size:0.8rem; color:#888;"><?= htmlspecialchars($item['label']) ?></span>
                                </div>
                                <?php
                                // Determine if this item should link somewhere
                                $itemLink = '';
                                switch ($item['item_type']) {
                                    case 'course':
                                        $itemLink = '?page=learner/catalog-subpage/course&course_id=' . $item['reference_id'];
                                        break;
                                    case 'module':
                                    case 'lesson':
                                    case 'quiz':
                                    case 'evaluation':
                                        // These need a course context; link to the course catalog as fallback
                                        $itemLink = '?page=learner/catalog';
                                        break;
                                    case 'program':
                                        $itemLink = '?page=learner/catalog-subpage/program&program_id=' . $item['reference_id'];
                                        break;
                                    case 'video-conference':
                                        $itemLink = '?page=learner/catalog-subpage/video-conference&video_conference_id=' . $item['reference_id'];
                                        break;
                                }
                                ?>
                                <?php if ($itemLink): ?>
                                    
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
