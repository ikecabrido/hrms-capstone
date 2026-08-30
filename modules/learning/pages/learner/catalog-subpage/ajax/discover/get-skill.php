<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/classes/skill.php';
require_once dirname(__FILE__, 8) . '/database/db.php';

try {
    /*
     * Learner authentication.
     *
     * Authentication is required for the learner API,
     * but enrollment is NOT required to access available skills.
     */
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

    $id = isset($_GET['id'])
        ? (int) $_GET['id']
        : 0;

    $courseId = isset($_GET['course_id'])
        ? (int) $_GET['course_id']
        : 0;

    $moduleId = isset($_GET['module_id'])
        ? (int) $_GET['module_id']
        : 0;

    $database = new Database();
    $pdo = $database->getConnection();

    $skillModel = new Skill($pdo);

    /*
     * ---------------------------------------------------------
     * Single skill
     * ---------------------------------------------------------
     */
    if ($id > 0) {
        $skill = $skillModel->getById($id);

        if (
            !$skill ||
            !isset($skill['status']) ||
            $skill['status'] !== 'active'
        ) {
            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'Skill not found or unavailable.'
            ]);

            exit;
        }

        echo json_encode([
            'success' => true,
            'item' => $skill
        ]);

        exit;
    }

    /*
     * ---------------------------------------------------------
     * Skill list
     * ---------------------------------------------------------
     *
     * course_id:
     *     Return skills associated with a course.
     *
     * module_id:
     *     Return skills associated with a module.
     *
     * neither:
     *     Return all skills.
     */
    if ($moduleId > 0) {
        $skills = $skillModel->getSkillsByModule($moduleId);

    } elseif ($courseId > 0) {
        $skills = $skillModel->getSkillsByCourse($courseId);

    } else {
        $skills = $skillModel->getList();
    }

    /*
     * Only active skills are available to learners.
     */
    $skills = array_values(
        array_filter(
            $skills,
            static function ($skill): bool {
                return is_array($skill)
                    && isset($skill['status'])
                    && $skill['status'] === 'active';
            }
        )
    );

    echo json_encode([
        'success' => true,
        'items' => $skills,
        'count' => count($skills)
    ]);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to load skills.'
    ]);
}