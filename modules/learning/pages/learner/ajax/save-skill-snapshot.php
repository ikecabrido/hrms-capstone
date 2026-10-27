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

    // Ensure snapshot table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS ld_skill_snapshot (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        learner_id INT UNSIGNED NOT NULL,
        total_skills INT UNSIGNED NOT NULL DEFAULT 0,
        acquired_count INT UNSIGNED NOT NULL DEFAULT 0,
        gap_count INT UNSIGNED NOT NULL DEFAULT 0,
        acquired_skills JSON DEFAULT NULL,
        gap_skills JSON DEFAULT NULL,
        snapshot_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_learner_date (learner_id, snapshot_date),
        INDEX idx_learner (learner_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Get all active skills
    $skillsStmt = $pdo->query("SELECT id, name FROM ld_skill WHERE status = 'active' ORDER BY name ASC");
    $allSkills = $skillsStmt->fetchAll(PDO::FETCH_ASSOC);
    $totalSkills = count($allSkills);

    // Get completed course IDs
    $completedStmt = $pdo->prepare("SELECT course_id FROM ld_enrollment WHERE learner_id = :lid AND status = 'completed'");
    $completedStmt->execute([':lid' => $learnerId]);
    $completedCourseIds = array_map('intval', $completedStmt->fetchAll(PDO::FETCH_COLUMN));

    // Get acquired skills from completed courses
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

    $acquiredCount = count($acquiredSkills);
    $gapCount = $totalSkills - $acquiredCount;
    $acquiredNames = array_values($acquiredSkills);

    // Get gap skill names
    $gapNames = [];
    foreach ($allSkills as $skill) {
        if (!isset($acquiredSkills[(int) $skill['id']])) {
            $gapNames[] = $skill['name'];
        }
    }

    $today = date('Y-m-d');

    // Upsert snapshot
    $stmt = $pdo->prepare("
        INSERT INTO ld_skill_snapshot (learner_id, total_skills, acquired_count, gap_count, acquired_skills, gap_skills, snapshot_date)
        VALUES (:lid, :total, :acquired, :gaps, :acquiredJson, :gapJson, :date)
        ON DUPLICATE KEY UPDATE
            total_skills = VALUES(total_skills),
            acquired_count = VALUES(acquired_count),
            gap_count = VALUES(gap_count),
            acquired_skills = VALUES(acquired_skills),
            gap_skills = VALUES(gap_skills),
            created_at = NOW()
    ");
    $stmt->execute([
        ':lid' => $learnerId,
        ':total' => $totalSkills,
        ':acquired' => $acquiredCount,
        ':gaps' => $gapCount,
        ':acquiredJson' => json_encode($acquiredNames),
        ':gapJson' => json_encode($gapNames),
        ':date' => $today,
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Snapshot saved.',
        'snapshot' => [
            'total_skills' => $totalSkills,
            'acquired_count' => $acquiredCount,
            'gap_count' => $gapCount,
            'date' => $today,
        ],
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save snapshot.',
        'error' => $e->getMessage(),
    ]);
}
