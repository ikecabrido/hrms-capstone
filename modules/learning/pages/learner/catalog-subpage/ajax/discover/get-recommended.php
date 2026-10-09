<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 8) . '/database/db.php';

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

    $courseId = isset($_GET['course_id'])
        ? (int) $_GET['course_id']
        : 0;

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
     * Make sure the requested course exists and is active.
     */
    $courseStmt = $pdo->prepare("
        SELECT
            id,
            title,
            status
        FROM ld_course
        WHERE id = :course_id
        LIMIT 1
    ");

    $courseStmt->execute([
        ':course_id' => $courseId
    ]);

    $course = $courseStmt->fetch(PDO::FETCH_ASSOC);

    if (!$course || $course['status'] !== 'active') {
        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Course not found.'
        ]);

        exit;
    }

    /*
     * Get all prerequisites configured for this course.
     *
     * A prerequisite can require:
     * - another course
     * - a skill
     *
     * The schema permits either field to be NULL.
     */
    $prerequisiteStmt = $pdo->prepare("
        SELECT
            p.id,
            p.required_course_id,
            p.required_skill_id,

            rc.title AS required_course_title,
            rs.name AS required_skill_name

        FROM ld_prerequisite p

        LEFT JOIN ld_course rc
            ON rc.id = p.required_course_id

        LEFT JOIN ld_skill rs
            ON rs.id = p.required_skill_id

        WHERE p.course_id = :course_id

        ORDER BY p.id ASC
    ");

    $prerequisiteStmt->execute([
        ':course_id' => $courseId
    ]);

    $prerequisites = $prerequisiteStmt->fetchAll(PDO::FETCH_ASSOC);

    /*
     * Find courses the learner has actually completed.
     *
     * We use enrollment.status = completed rather than merely
     * checking progress rows because the enrollment table is the
     * authoritative course-level completion state.
     */
    $completedCourseStmt = $pdo->prepare("
        SELECT DISTINCT
            e.course_id
        FROM ld_enrollment e
        WHERE e.learner_id = :learner_id
          AND e.status = 'completed'
    ");

    $completedCourseStmt->execute([
        ':learner_id' => $learnerId
    ]);

    $completedCourseIds = array_map(
        'intval',
        $completedCourseStmt->fetchAll(PDO::FETCH_COLUMN)
    );

    /*
     * A learner does not have a separate skill-acquisition table
     * in this schema.
     *
     * Therefore a required skill is considered satisfied when the
     * learner has completed a course mapped to that skill.
     */
    $completedSkillStmt = $pdo->prepare("
        SELECT DISTINCT
            cs.skill_id
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
    $satisfied = [];

    foreach ($prerequisites as $prerequisite) {
        $requiredCourseId = (int) ($prerequisite['required_course_id'] ?? 0);
        $requiredSkillId = (int) ($prerequisite['required_skill_id'] ?? 0);

        /*
         * Course prerequisite.
         */
        if ($requiredCourseId > 0) {
            $isSatisfied = in_array(
                $requiredCourseId,
                $completedCourseIds,
                true
            );

            $entry = [
                'id' => (int) $prerequisite['id'],
                'type' => 'course',
                'reference_id' => $requiredCourseId,
                'title' => $prerequisite['required_course_title'],
                'satisfied' => $isSatisfied
            ];

            if ($isSatisfied) {
                $satisfied[] = $entry;
            } else {
                $missing[] = $entry;
            }

            continue;
        }

        /*
         * Skill prerequisite.
         */
        if ($requiredSkillId > 0) {
            $isSatisfied = in_array(
                $requiredSkillId,
                $completedSkillIds,
                true
            );

            $entry = [
                'id' => (int) $prerequisite['id'],
                'type' => 'skill',
                'reference_id' => $requiredSkillId,
                'title' => $prerequisite['required_skill_name'],
                'satisfied' => $isSatisfied
            ];

            if ($isSatisfied) {
                $satisfied[] = $entry;
            } else {
                $missing[] = $entry;
            }
        }
    }

    $canEnroll = count($missing) === 0;

    echo json_encode([
        'success' => true,
        'course' => $course,
        'can_enroll' => $canEnroll,
        'prerequisites_met' => $canEnroll,
        'total_prerequisites' => count($prerequisites),
        'satisfied_count' => count($satisfied),
        'missing_count' => count($missing),
        'satisfied' => $satisfied,
        'missing' => $missing
    ]);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to check course prerequisites.'
    ]);
}