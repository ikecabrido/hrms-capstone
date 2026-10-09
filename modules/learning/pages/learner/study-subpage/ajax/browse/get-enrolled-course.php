<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once dirname(__DIR__, 7) . '/database/db.php';

$learnerId = (int) $_SESSION['employee_id'];
$courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : null;

try {
    $pdo = (new Database())->getConnection();

    if ($courseId) {
        // Single course
        $stmt = $pdo->prepare("
            SELECT e.id AS enrollment_id, e.status, e.enrolled_at, e.last_accessed_at, e.completed_at,
                   c.id, c.title, c.description, c.category, c.status AS course_status,
                   CONCAT(emp.first_name, ' ', emp.last_name) AS instructor_name
            FROM ld_enrollment e
            JOIN ld_course c ON c.id = e.course_id
            LEFT JOIN em_employees emp ON emp.employee_id = c.instructor_id
            WHERE e.learner_id = :lid AND e.course_id = :cid
            LIMIT 1
        ");
        $stmt->execute([':lid' => $learnerId, ':cid' => $courseId]);
        $course = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$course) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Not enrolled in this course']);
            exit;
        }

        // Get progress
        $stmt2 = $pdo->prepare("
            SELECT COUNT(*) AS total,
                   SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed
            FROM ld_progress p
            JOIN ld_enrollment e ON e.id = p.enrollment_id
            WHERE e.learner_id = :lid AND e.course_id = :cid
        ");
        $stmt2->execute([':lid' => $learnerId, ':cid' => $courseId]);
        $progress = $stmt2->fetch(PDO::FETCH_ASSOC);
        $total = (int) ($progress['total'] ?? 0);
        $completed = (int) ($progress['completed'] ?? 0);
        $pct = $total > 0 ? round(($completed / $total) * 100) : 0;

        $course['progress'] = $pct;
        $course['items_completed'] = $completed;
        $course['total_items'] = $total;

        echo json_encode(['success' => true, 'item' => $course]);
    } else {
        // All enrolled courses
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = min(50, max(1, (int) ($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;

        $stmt = $pdo->prepare("
            SELECT e.id AS enrollment_id, e.status, e.enrolled_at, e.last_accessed_at,
                   c.id, c.title, c.description, c.category,
                   CONCAT(emp.first_name, ' ', emp.last_name) AS instructor_name
            FROM ld_enrollment e
            JOIN ld_course c ON c.id = e.course_id
            LEFT JOIN em_employees emp ON emp.employee_id = c.instructor_id
            WHERE e.learner_id = :lid
            ORDER BY e.last_accessed_at DESC, e.enrolled_at DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':lid', $learnerId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM ld_enrollment WHERE learner_id = :lid");
        $countStmt->execute([':lid' => $learnerId]);
        $total = (int) $countStmt->fetchColumn();

        echo json_encode([
            'success' => true,
            'data' => $courses,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
