<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/database/db.php';

try {
    $learnerId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;

    if ($learnerId <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }

    $database = new Database();
    $pdo = $database->getConnection();

    // 1. Get all active skills
    $skillsStmt = $pdo->query("SELECT id, name, description FROM ld_skill WHERE status = 'active' ORDER BY name ASC");
    $allSkills = $skillsStmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Get learner's completed course IDs
    $completedStmt = $pdo->prepare("SELECT course_id FROM ld_enrollment WHERE learner_id = :lid AND status = 'completed'");
    $completedStmt->execute([':lid' => $learnerId]);
    $completedCourseIds = array_map('intval', $completedStmt->fetchAll(PDO::FETCH_COLUMN));

    // 3. Get learner's enrolled (all) course IDs
    $enrolledStmt = $pdo->prepare("SELECT course_id, status FROM ld_enrollment WHERE learner_id = :lid");
    $enrolledStmt->execute([':lid' => $learnerId]);
    $enrollments = $enrolledStmt->fetchAll(PDO::FETCH_ASSOC);
    $enrolledCourseIds = array_map('intval', array_column($enrollments, 'course_id'));

    // 4. Get skills the learner has acquired (from completed courses via course_skill)
    $acquiredSkills = [];
    if (!empty($completedCourseIds)) {
        $skillStmt = $pdo->prepare(
            "SELECT DISTINCT cs.skill_id, s.name
             FROM ld_course_skill cs
             JOIN ld_skill s ON s.id = cs.skill_id
             WHERE cs.course_id IN (" . implode(',', $completedCourseIds) . ")"
        );
        $skillStmt->execute();
        foreach ($skillStmt->fetchAll(PDO::FETCH_ASSOC) as $s) {
            $acquiredSkills[(int) $s['skill_id']] = $s['name'];
        }
    }

    // 5. For each skill, find courses that teach it
    $skillCourses = [];
    if (!empty($allSkills)) {
        $skillIds = array_column($allSkills, 'id');
        $scStmt = $pdo->prepare(
            "SELECT cs.skill_id, c.id AS course_id, c.title, c.category, c.status AS course_status,
                    CONCAT(emp.first_name, ' ', emp.last_name) AS instructor_name,
                    (SELECT COUNT(*) FROM ld_enrollment e WHERE e.course_id = c.id) AS enrollment_count,
                    (SELECT COUNT(*) FROM ld_module m WHERE m.course_id = c.id AND m.status = 'active') AS module_count
             FROM ld_course_skill cs
             JOIN ld_course c ON c.id = cs.course_id
             LEFT JOIN em_employees emp ON emp.employee_id = c.instructor_id
             WHERE cs.skill_id IN (" . implode(',', $skillIds) . ")
               AND c.status = 'active'
             ORDER BY c.created_at DESC"
        );
        $scStmt->execute();
        foreach ($scStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $sid = (int) $row['skill_id'];
            $cid = (int) $row['course_id'];
            $skillCourses[$sid][] = [
                'id' => $cid,
                'title' => $row['title'],
                'category' => $row['category'] ?? '',
                'instructor_name' => trim($row['instructor_name'] ?? ''),
                'enrollment_count' => (int) $row['enrollment_count'],
                'module_count' => (int) $row['module_count'],
                'enrolled' => in_array($cid, $enrolledCourseIds, true),
                'completed' => in_array($cid, $completedCourseIds, true),
                'link' => '?page=learner/study-subpage/course&course_id=' . $cid,
            ];
        }
    }

    // 6. Build skill gap results
    $gaps = [];
    $acquired = [];

    foreach ($allSkills as $skill) {
        $sid = (int) $skill['id'];
        $isAcquired = isset($acquiredSkills[$sid]);
        $courses = $skillCourses[$sid] ?? [];

        // Count how many courses the learner completed that teach this skill
        $completedCount = 0;
        foreach ($courses as $c) {
            if ($c['completed']) $completedCount++;
        }

        $entry = [
            'id' => $sid,
            'name' => $skill['name'],
            'description' => $skill['description'] ?? '',
            'acquired' => $isAcquired,
            'courses_available' => count($courses),
            'courses_completed' => $completedCount,
            'courses' => $courses,
        ];

        if ($isAcquired) {
            $acquired[] = $entry;
        } else {
            // Sort gap courses: unenrolled first, then by enrollment count
            usort($entry['courses'], function ($a, $b) {
                if ($a['enrolled'] !== $b['enrolled']) return $a['enrolled'] ? 1 : -1;
                return $b['enrollment_count'] - $a['enrollment_count'];
            });
            $gaps[] = $entry;
        }
    }

    // Sort gaps by number of available courses (fewer = harder to fill = higher priority)
    usort($gaps, function ($a, $b) {
        return $a['courses_available'] - $b['courses_available'];
    });

    echo json_encode([
        'success' => true,
        'total_skills' => count($allSkills),
        'acquired_count' => count($acquired),
        'gap_count' => count($gaps),
        'acquired' => $acquired,
        'gaps' => $gaps,
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load skill gap data.',
        'error' => $e->getMessage(),
    ]);
}
