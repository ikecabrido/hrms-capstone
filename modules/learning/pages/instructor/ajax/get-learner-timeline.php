<?php
header('Content-Type: application/json');
session_start();

include_once dirname(__DIR__, 3) . '/classes/employee.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

try {
    $employeeClass = new Employee();
    $instructorId = (int) ($employeeClass->getEmployeeId() ?? 0);

    $learnerId = isset($_GET['learner_id']) ? (int) $_GET['learner_id'] : 0;
    $courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;

    if ($learnerId <= 0) {
        http_response_code(400);
        die(json_encode(['error' => 'learner_id is required']));
    }

    $pdo = (new Database())->getConnection();

    // Verify instructor has access to this learner via shared courses
    $stmt = $pdo->prepare("
        SELECT e.learner_id
        FROM ld_enrollment e
        WHERE e.learner_id = :lid
        LIMIT 1
    ");
    $stmt->execute([':lid' => $learnerId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        die(json_encode(['error' => 'Learner not found']));
    }

    // Get learner info
    $stmt = $pdo->prepare("SELECT employee_id, first_name, last_name, email FROM em_employees WHERE employee_id = :lid");
    $stmt->execute([':lid' => $learnerId]);
    $learner = $stmt->fetch(PDO::FETCH_ASSOC);

    // Build timeline events
    $events = [];

    // 1. Enrollment events
    $sql = "SELECT e.*, c.title AS course_title
            FROM ld_enrollment e
            JOIN ld_course c ON c.id = e.course_id
            JOIN ld_course co ON co.id = c.id
            WHERE e.learner_id = :lid AND co.instructor_id = :iid";
    $params = [':lid' => $learnerId, ':iid' => $instructorId];
    if ($courseId > 0) {
        $sql .= " AND c.id = :cid";
        $params[':cid'] = $courseId;
    }
    $sql .= " ORDER BY e.enrolled_at ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($enrollments as $en) {
        $events[] = [
            'type' => 'enrollment',
            'icon' => '',
            'color' => '#6366f1',
            'title' => 'Enrolled in ' . $en['course_title'],
            'detail' => 'Status: ' . ucfirst(str_replace('_', ' ', $en['status'])),
            'timestamp' => $en['enrolled_at'],
            'course_id' => $en['course_id'],
        ];
        if ($en['completed_at']) {
            $events[] = [
                'type' => 'completion',
                'icon' => '',
                'color' => '#10b981',
                'title' => 'Completed ' . $en['course_title'],
                'detail' => 'Course finished successfully',
                'timestamp' => $en['completed_at'],
                'course_id' => $en['course_id'],
            ];
        }
    }

    // 2. Progress events (lessons, modules)
    $enrollmentIds = array_column($enrollments, 'id');
    if (!empty($enrollmentIds)) {
        $ph = implode(',', array_fill(0, count($enrollmentIds), '?'));
        $stmt = $pdo->prepare("
            SELECT p.*, 
                CASE p.item_type
                    WHEN 'lesson' THEN (SELECT title FROM ld_lesson WHERE id = p.reference_id)
                    WHEN 'module' THEN (SELECT title FROM ld_module WHERE id = p.reference_id)
                    WHEN 'quiz' THEN (SELECT title FROM ld_quiz WHERE id = p.reference_id)
                    WHEN 'evaluation' THEN (SELECT title FROM ld_evaluation WHERE id = p.reference_id)
                END AS item_title,
                c.title AS course_title
            FROM ld_progress p
            JOIN ld_enrollment e ON e.id = p.enrollment_id
            JOIN ld_course c ON c.id = e.course_id
            WHERE p.enrollment_id IN ($ph) AND p.status = 'completed'
            ORDER BY p.completed_at ASC
        ");
        $stmt->execute($enrollmentIds);
        $progressItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($progressItems as $pi) {
            $typeIcons = ['lesson' => '', 'module' => '', 'quiz' => '❓', 'evaluation' => '✅'];
            $typeColors = ['lesson' => '#3b82f6', 'module' => '#8b5cf6', 'quiz' => '#f59e0b', 'evaluation' => '#10b981'];
            $events[] = [
                'type' => $pi['item_type'] . '_completed',
                'icon' => $typeIcons[$pi['item_type']] ?? '',
                'color' => $typeColors[$pi['item_type']] ?? '#666',
                'title' => ucfirst($pi['item_type']) . ' completed: ' . ($pi['item_title'] ?? 'Unknown'),
                'detail' => 'In ' . $pi['course_title'],
                'timestamp' => $pi['completed_at'],
                'course_id' => $pi['course_id'] ?? 0,
            ];
        }
    }

    // 3. Quiz attempts
    if (!empty($enrollmentIds)) {
        $courseIdFilter = $courseId > 0 ? " AND c.id = $courseId" : "";
        $stmt = $pdo->prepare("
            SELECT qa.*, q.title AS quiz_title, c.title AS course_title, c.id AS cid
            FROM ld_quiz_attempt qa
            JOIN ld_quiz q ON q.id = qa.quiz_id
            JOIN ld_module m ON m.id = q.module_id
            JOIN ld_course c ON c.id = m.course_id
            WHERE qa.learner_id = :lid $courseIdFilter
            ORDER BY qa.attempted_at ASC
        ");
        $stmt->execute([':lid' => $learnerId]);
        $quizAttempts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($quizAttempts as $qa) {
            $passed = $qa['passed'] ? '✅ Passed' : '❌ Failed';
            $scorePct = $qa['total_items'] > 0 ? round(($qa['score'] / $qa['total_items']) * 100, 1) : 0;
            $events[] = [
                'type' => 'quiz_attempt',
                'icon' => '❓',
                'color' => $qa['passed'] ? '#10b981' : '#ef4444',
                'title' => 'Quiz attempt: ' . $qa['quiz_title'],
                'detail' => "$passed — Score: {$scorePct}% ({$qa['score']}/{$qa['total_items']})",
                'timestamp' => $qa['attempted_at'],
                'course_id' => $qa['cid'] ?? 0,
            ];
        }
    }

    // 4. Quiz/Evaluation sessions (more detailed)
    if (!empty($enrollmentIds)) {
        $courseIdFilter = $courseId > 0 ? " AND c.id = $courseId" : "";
        $stmt = $pdo->prepare("
            SELECT qs.*, 
                CASE qs.item_type 
                    WHEN 'quiz' THEN (SELECT title FROM ld_quiz WHERE id = qs.reference_id)
                    WHEN 'evaluation' THEN (SELECT title FROM ld_evaluation WHERE id = qs.reference_id)
                END AS item_title,
                qs.item_type AS qtype,
                c.title AS course_title, c.id AS cid
            FROM ld_quiz_session qs
            LEFT JOIN ld_quiz q ON qs.item_type = 'quiz' AND qs.reference_id = q.id
            LEFT JOIN ld_module m ON q.module_id = m.id
            LEFT JOIN ld_course c ON m.course_id = c.id
            LEFT JOIN ld_evaluation ev ON qs.item_type = 'evaluation' AND qs.reference_id = ev.id
            WHERE qs.learner_id = :lid $courseIdFilter
            ORDER BY qs.started_at ASC
        ");
        $stmt->execute([':lid' => $learnerId]);
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($sessions as $s) {
            $statusLabel = ucfirst(str_replace('_', ' ', $s['status']));
            $detailParts = [$statusLabel];
            if ($s['score'] !== null) {
                $detailParts[] = "Score: {$s['score']}%";
            }
            $detailParts[] = 'In ' . $s['course_title'];

            $events[] = [
                'type' => $s['qtype'] . '_session',
                'icon' => $s['qtype'] === 'quiz' ? '❓' : '✅',
                'color' => $s['status'] === 'submitted' && ($s['passed'] ?? false) ? '#10b981' : '#f59e0b',
                'title' => ucfirst($s['qtype']) . ' session: ' . ($s['item_title'] ?? 'Unknown'),
                'detail' => implode(' — ', $detailParts),
                'timestamp' => $s['started_at'],
                'course_id' => $s['cid'] ?? 0,
            ];
        }
    }

    // Sort all events by timestamp
    usort($events, function ($a, $b) {
        $ta = $a['timestamp'] ?? '1970-01-01';
        $tb = $b['timestamp'] ?? '1970-01-01';
        return strcmp($ta, $tb);
    });

    http_response_code(200);
    echo json_encode([
        'learner' => $learner,
        'timeline' => $events,
        'total_events' => count($events),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
