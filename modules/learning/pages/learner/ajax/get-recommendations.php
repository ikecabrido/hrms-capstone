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

    // Get learner's completed course IDs
    $completedStmt = $pdo->prepare("SELECT course_id FROM ld_enrollment WHERE learner_id = :lid AND status = 'completed'");
    $completedStmt->execute([':lid' => $learnerId]);
    $completedCourseIds = array_map('intval', $completedStmt->fetchAll(PDO::FETCH_COLUMN));

    // Get learner's enrolled course IDs (to avoid suggesting already-enrolled)
    $enrolledStmt = $pdo->prepare("SELECT course_id FROM ld_enrollment WHERE learner_id = :lid");
    $enrolledStmt->execute([':lid' => $learnerId]);
    $enrolledCourseIds = array_map('intval', $enrolledStmt->fetchAll(PDO::FETCH_COLUMN));

    // Get skills the learner has (from completed courses)
    $learnerSkills = [];
    if (!empty($completedCourseIds)) {
        $skillStmt = $pdo->prepare("SELECT DISTINCT cs.skill_id, s.name FROM ld_course_skill cs JOIN ld_skill s ON s.id = cs.skill_id WHERE cs.course_id IN (" . implode(',', $completedCourseIds) . ")");
        $skillStmt->execute();
        foreach ($skillStmt->fetchAll(PDO::FETCH_ASSOC) as $s) {
            $learnerSkills[(int) $s['skill_id']] = $s['name'];
        }
    }

    // Get all active courses with their skills and enrollment counts
    $allCoursesStmt = $pdo->prepare("
        SELECT c.id, c.title, c.description, c.category, c.status, c.created_at,
               CONCAT(emp.first_name, ' ', emp.last_name) AS instructor_name,
               (SELECT COUNT(*) FROM ld_enrollment e WHERE e.course_id = c.id) AS enrollment_count,
               (SELECT COUNT(*) FROM ld_module m WHERE m.course_id = c.id AND m.status = 'active') AS module_count
        FROM ld_course c
        LEFT JOIN em_employees emp ON emp.employee_id = c.instructor_id
        WHERE c.status = 'active'
        ORDER BY c.created_at DESC
    ");
    $allCoursesStmt->execute();
    $allCourses = $allCoursesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get skills for each course
    $courseSkills = [];
    if (!empty($allCourses)) {
        $courseIds = array_column($allCourses, 'id');
        $csStmt = $pdo->prepare("SELECT cs.course_id, cs.skill_id, s.name FROM ld_course_skill cs JOIN ld_skill s ON s.id = cs.skill_id WHERE cs.course_id IN (" . implode(',', $courseIds) . ")");
        $csStmt->execute();
        foreach ($csStmt->fetchAll(PDO::FETCH_ASSOC) as $cs) {
            $courseSkills[(int) $cs['course_id']][] = ['id' => (int) $cs['skill_id'], 'name' => $cs['name']];
        }
    }

    // Scoring algorithm
    $recommendations = [];
    foreach ($allCourses as $course) {
        $courseId = (int) $course['id'];

        // Skip already enrolled courses
        if (in_array($courseId, $enrolledCourseIds, true)) continue;

        $score = 0;
        $matchReasons = [];

        // 1. Skill gap matching (highest weight)
        $skills = $courseSkills[$courseId] ?? [];
        $missingSkills = 0;
        foreach ($skills as $skill) {
            if (!isset($learnerSkills[$skill['id']])) {
                $missingSkills++;
                $score += 30;
            }
        }
        if ($missingSkills > 0) {
            $matchReasons[] = $missingSkills . ' skill gap' . ($missingSkills !== 1 ? 's' : '') . ' to fill';
        }

        // 2. Category affinity (learner's completed courses in same category)
        $completedCategories = [];
        foreach ($completedCourseIds as $ccid) {
            foreach ($allCourses as $ac) {
                if ((int) $ac['id'] === $ccid) {
                    $completedCategories[] = $ac['category'] ?? '';
                }
            }
        }
        if (in_array($course['category'] ?? '', $completedCategories)) {
            $score += 15;
            $matchReasons[] = 'matches your interest in ' . ($course['category'] ?? '');
        }

        // 3. Popularity bonus
        $enrollmentCount = (int) $course['enrollment_count'];
        if ($enrollmentCount >= 10) $score += 10;
        elseif ($enrollmentCount >= 5) $score += 5;
        if ($enrollmentCount > 0) {
            $matchReasons[] = $enrollmentCount . ' learner' . ($enrollmentCount !== 1 ? 's' : '') . ' enrolled';
        }

        // 4. Newness bonus (created in last 30 days)
        if (strtotime($course['created_at']) > strtotime('-30 days')) {
            $score += 5;
            $matchReasons[] = 'newly added';
        }

        // 5. Has instructor (instructor-led courses preferred)
        if (!empty($course['instructor_name']) && $course['instructor_name'] !== ' ') {
            $score += 3;
        }

        $recommendations[] = [
            'id' => $courseId,
            'title' => $course['title'],
            'description' => mb_substr(strip_tags($course['description'] ?? ''), 0, 120),
            'category' => $course['category'] ?? '',
            'instructor_name' => trim($course['instructor_name'] ?? ''),
            'enrollment_count' => $enrollmentCount,
            'module_count' => (int) $course['module_count'],
            'skills' => array_column($skills, 'name'),
            'score' => $score,
            'reasons' => $matchReasons,
            'link' => '?page=learner/catalog-subpage/course&course_id=' . $courseId,
        ];
    }

    // Sort by score descending, then by enrollment count
    usort($recommendations, function ($a, $b) {
        if ($b['score'] !== $a['score']) return $b['score'] - $a['score'];
        return $b['enrollment_count'] - $a['enrollment_count'];
    });

    // Return top 8
    $recommendations = array_slice($recommendations, 0, 8);

    echo json_encode([
        'success' => true,
        'learner_skills' => array_values($learnerSkills),
        'total_skills' => count($learnerSkills),
        'recommendations' => $recommendations,
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load recommendations.',
        'error' => $e->getMessage(),
    ]);
}
