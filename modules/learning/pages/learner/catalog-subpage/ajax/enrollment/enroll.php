<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/classes/enrollment.php';
require_once dirname(__FILE__, 8) . '/database/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed.'
    ]);
    exit;
}

try {
    $learnerId = isset($_SESSION['employee_id'])
        ? (int) $_SESSION['employee_id']
        : 0;

    if ($learnerId <= 0) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized.'
        ]);
        exit;
    }

    $courseId = (int) ($_POST['course_id'] ?? 0);

    if ($courseId <= 0) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => 'Course ID is required.'
        ]);
        exit;
    }

    $database = new Database();
    $pdo = $database->getConnection();

    /*
     * Verify the course exists and is available to learners.
     */
    $courseStmt = $pdo->prepare("
        SELECT
            id,
            title,
            status,
            enrollment_deadline
        FROM ld_course
        WHERE id = :course_id
        LIMIT 1
    ");

    $courseStmt->execute([
        ':course_id' => $courseId
    ]);

    $course = $courseStmt->fetch(PDO::FETCH_ASSOC);

    if (!$course) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Course not found.'
        ]);
        exit;
    }

    if ($course['status'] !== 'active') {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => 'This course is not currently available for enrollment.'
        ]);
        exit;
    }

    if (
        !empty($course['enrollment_deadline']) &&
        strtotime($course['enrollment_deadline']) < time()
    ) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => 'The enrollment deadline for this course has passed.'
        ]);
        exit;
    }

    /*
     * Check prerequisites before creating the enrollment.
     */
    $prerequisiteStmt = $pdo->prepare("
        SELECT
            id,
            required_course_id,
            required_skill_id
        FROM ld_prerequisite
        WHERE course_id = :course_id
    ");

    $prerequisiteStmt->execute([
        ':course_id' => $courseId
    ]);

    $prerequisites = $prerequisiteStmt->fetchAll(PDO::FETCH_ASSOC);

    if ($prerequisites) {
        $completedCourseStmt = $pdo->prepare("
            SELECT DISTINCT course_id
            FROM ld_enrollment
            WHERE learner_id = :learner_id
              AND status = 'completed'
        ");

        $completedCourseStmt->execute([
            ':learner_id' => $learnerId
        ]);

        $completedCourseIds = array_map(
            'intval',
            $completedCourseStmt->fetchAll(PDO::FETCH_COLUMN)
        );

        $completedSkillStmt = $pdo->prepare("
            SELECT DISTINCT cs.skill_id
            FROM ld_course_skill cs
            INNER JOIN ld_enrollment e
                ON e.course_id = cs.course_id
            WHERE e.learner_id = :learner_id
              AND e.status = 'completed'
        ");

        $completedSkillStmt->execute([
            ':learner_id' => $learnerId
        ]);

        $completedSkillIds = array_map(
            'intval',
            $completedSkillStmt->fetchAll(PDO::FETCH_COLUMN)
        );

        $missing = [];

        foreach ($prerequisites as $prerequisite) {
            $requiredCourseId = (int) ($prerequisite['required_course_id'] ?? 0);
            $requiredSkillId = (int) ($prerequisite['required_skill_id'] ?? 0);

            if (
                $requiredCourseId > 0 &&
                !in_array($requiredCourseId, $completedCourseIds, true)
            ) {
                $missing[] = [
                    'type' => 'course',
                    'reference_id' => $requiredCourseId
                ];
            }

            if (
                $requiredSkillId > 0 &&
                !in_array($requiredSkillId, $completedSkillIds, true)
            ) {
                $missing[] = [
                    'type' => 'skill',
                    'reference_id' => $requiredSkillId
                ];
            }
        }

        if ($missing) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'You have not completed all prerequisites for this course.',
                'prerequisites_met' => false,
                'missing' => $missing
            ]);

            exit;
        }
    }

    $enrollment = new Enrollment($pdo);

    $result = $enrollment->enroll(
        $learnerId,
        $courseId
    );

    if (!empty($result['success'])) {
        echo json_encode($result);
        exit;
    }

    http_response_code(422);

    echo json_encode($result);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to enroll.'
    ]);
}