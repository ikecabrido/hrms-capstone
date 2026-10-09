<?php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 6) . '/database/db.php';

function titleForReference(PDO $pdo, string $itemType, int $referenceId): string {
    $tableMap = [
        'course' => ['table' => 'ld_course', 'titleField' => 'title'],
        'module' => ['table' => 'ld_module', 'titleField' => 'title'],
        'lesson' => ['table' => 'ld_lesson', 'titleField' => 'title'],
        'quiz' => ['table' => 'ld_quiz', 'titleField' => 'title'],
        'evaluation' => ['table' => 'ld_evaluation', 'titleField' => 'title'],
        'program' => ['table' => 'ld_program', 'titleField' => 'title'],
        'video-conference' => ['table' => 'ld_video_conference', 'titleField' => 'title'],
    ];

    $config = $tableMap[$itemType] ?? null;
    if (!$config) {
        return 'Untitled item';
    }

    $stmt = $pdo->prepare('SELECT ' . $config['titleField'] . ' AS title FROM ' . $config['table'] . ' WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $referenceId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return trim((string) ($row['title'] ?? 'Untitled item')) !== '' ? $row['title'] : 'Untitled item';
}

function mapItemType(string $itemType): string {
    $map = [
        'course' => 'Course',
        'module' => 'Module',
        'lesson' => 'Lesson',
        'quiz' => 'Quiz',
        'evaluation' => 'Evaluation',
        'program' => 'Program',
        'video-conference' => 'Video Conference',
    ];

    return $map[$itemType] ?? ucfirst(str_replace('-', ' ', $itemType));
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $type = isset($_GET['type']) ? trim((string) $_GET['type']) : '';
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if (!$type || !$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Type and ID are required.']);
        exit;
    }

    $response = [
        'success' => true,
        'data' => [
            'type' => $type,
            'id' => $id,
            'parent' => null,
            'children' => [],
        ],
    ];

    if ($type === 'Learning Path') {
        $stmt = $pdo->prepare('SELECT id, title, description, status FROM ld_learning_path WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Learning path not found.']);
            exit;
        }

        $response['data']['parent'] = [
            'type' => 'Learning Path',
            'id' => (int) $row['id'],
            'title' => $row['title'],
        ];

        $itemStmt = $pdo->prepare('SELECT item_type, reference_id, order_index FROM ld_learning_path_item WHERE learning_path_id = :learning_path_id ORDER BY order_index ASC, id ASC');
        $itemStmt->execute([':learning_path_id' => $id]);

        while ($item = $itemStmt->fetch(PDO::FETCH_ASSOC)) {
            $contentType = (string) ($item['item_type'] ?? '');
            $referenceId = (int) ($item['reference_id'] ?? 0);
            $response['data']['children'][] = [
                'type' => mapItemType($contentType),
                'id' => $referenceId,
                'title' => titleForReference($pdo, $contentType, $referenceId),
                'children' => [],
            ];
        }
    } elseif ($type === 'Program') {
        $stmt = $pdo->prepare('SELECT id, title, description, status FROM ld_program WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Program not found.']);
            exit;
        }

        $response['data']['parent'] = [
            'type' => 'Program',
            'id' => (int) $row['id'],
            'title' => $row['title'],
        ];

        $confStmt = $pdo->prepare('SELECT id, title FROM ld_video_conference WHERE program_id = :program_id ORDER BY scheduled_at DESC, id DESC');
        $confStmt->execute([':program_id' => $id]);

        while ($conference = $confStmt->fetch(PDO::FETCH_ASSOC)) {
            $response['data']['children'][] = [
                'type' => 'Video Conference',
                'id' => (int) $conference['id'],
                'title' => (string) $conference['title'],
                'children' => [],
            ];
        }
    } elseif ($type === 'Video Conference') {
        $stmt = $pdo->prepare('SELECT id, title, program_id, course_id, status, scheduled_at FROM ld_video_conference WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Video conference not found.']);
            exit;
        }

        $parentType = 'Video Conference';
        $parentTitle = $row['title'];
        $parentId = (int) $row['id'];

        if (!empty($row['program_id'])) {
            $programStmt = $pdo->prepare('SELECT id, title FROM ld_program WHERE id = :id LIMIT 1');
            $programStmt->execute([':id' => (int) $row['program_id']]);
            $program = $programStmt->fetch(PDO::FETCH_ASSOC);
            if ($program) {
                $parentType = 'Program';
                $parentTitle = (string) $program['title'];
                $parentId = (int) $program['id'];
            }
        }

        $response['data']['parent'] = [
            'type' => $parentType,
            'id' => $parentId,
            'title' => $parentTitle,
        ];
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unsupported training type.']);
        exit;
    }

    echo json_encode($response);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
