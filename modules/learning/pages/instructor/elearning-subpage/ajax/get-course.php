<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 5) . '/classes/course.php';
require_once dirname(__FILE__, 7) . '/database/db.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $course = new Course($pdo);

    // Rich course list with filterable fields (skills, category, status, program)
    $stmt = $pdo->query(
        "SELECT c.id, c.title, c.status, c.delivery_mode, c.category, c.program_id,
                (SELECT GROUP_CONCAT(s.name SEPARATOR ', ')
                 FROM ld_course_skill cs
                 INNER JOIN ld_skill s ON s.id = cs.skill_id
                 WHERE cs.course_id = c.id) AS skills
         FROM ld_course c
         WHERE c.status IN ('draft','active','archived')
         ORDER BY c.title ASC"
    );
    $items = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    echo json_encode([
        'success' => true,
        'items' => array_map(function ($item) {
            return [
                'id' => (int) $item['id'],
                'name' => trim((string) $item['title']),
                'title' => trim((string) $item['title']),
                'status' => trim((string) $item['status']),
                'delivery_mode' => trim((string) ($item['delivery_mode'] ?? 'online')),
                'category' => $item['category'] !== null ? trim((string) $item['category']) : '',
                'program_id' => $item['program_id'] !== null ? (int) $item['program_id'] : null,
                'skills' => ($item['skills'] !== null && $item['skills'] !== '')
                    ? array_map('trim', explode(',', $item['skills']))
                    : []
            ];
        }, $items)
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load courses.',
        'error' => $e->getMessage()
    ]);
}
