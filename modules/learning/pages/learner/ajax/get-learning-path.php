<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 5) . '/database/db.php';

try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $learnerId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;
    if ($learnerId <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
        exit;
    }

    $database = new Database();
    $pdo = $database->getConnection();

    // Get the path assigned to this learner
    $stmt = $pdo->prepare("
        SELECT lp.id, lp.title, lp.description, lp.status, lp.type, lp.is_public, lp.assigned_to, lp.instructor_id, lp.created_at,
               CONCAT(emp.first_name, ' ', emp.last_name) AS instructor_name,
               (SELECT COUNT(*) FROM ld_learning_path_item lpi WHERE lpi.learning_path_id = lp.id) AS item_count
        FROM ld_learning_path lp
        LEFT JOIN em_employees emp ON emp.employee_id = lp.instructor_id
        WHERE lp.assigned_to = :lid AND lp.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([':lid' => $learnerId]);
    $path = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$path) {
        echo json_encode([
            'success' => true,
            'data' => null,
            'message' => 'No learning path assigned to you yet.'
        ]);
        exit;
    }

    // Fetch items in order with resolved titles
    $itemStmt = $pdo->prepare("
        SELECT lpi.id, lpi.item_type, lpi.reference_id, lpi.order_index, lpi.status AS item_status
        FROM ld_learning_path_item lpi
        WHERE lpi.learning_path_id = :lpid AND lpi.status = 'active'
        ORDER BY lpi.order_index ASC, lpi.id ASC
    ");
    $itemStmt->execute([':lpid' => $path['id']]);
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

    $items = [];
    foreach ($rawItems as $item) {
        $table = $tableMap[$item['item_type']] ?? null;
        $item['label'] = $typeLabels[$item['item_type']] ?? ucfirst($item['item_type']);
        $item['title'] = 'Item #' . $item['reference_id'];

        if ($table) {
            $titleStmt = $pdo->prepare("SELECT title FROM {$table} WHERE id = :rid LIMIT 1");
            $titleStmt->execute([':rid' => $item['reference_id']]);
            $fetched = $titleStmt->fetchColumn();
            if ($fetched) {
                $item['title'] = $fetched;
            }
        }

        // Link for each item type
        switch ($item['item_type']) {
            case 'course':
                $item['link'] = '?page=learner/study-subpage/course&course_id=' . $item['reference_id'];
                break;
            case 'program':
                $item['link'] = '?page=learner/catalog-subpage/program&program_id=' . $item['reference_id'];
                break;
            case 'video-conference':
                $item['link'] = '?page=learner/catalog-subpage/video-conference&video_conference_id=' . $item['reference_id'];
                break;
            case 'quiz':
            case 'lesson':
            case 'evaluation':
                // These need course/module context — link to catalog as fallback
                $item['link'] = '?page=learner/catalog';
                break;
            default:
                $item['link'] = '';
        }

        $items[] = $item;
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'id'            => (int) $path['id'],
            'title'         => $path['title'],
            'description'   => $path['description'] ?? '',
            'status'        => $path['status'],
            'type'          => $path['type'] ?? 'standard',
            'instructor_name' => $path['instructor_name'] ?? '',
            'item_count'    => (int) $path['item_count'],
            'items'         => $items,
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load your learning path.', 'error' => $e->getMessage()]);
}
