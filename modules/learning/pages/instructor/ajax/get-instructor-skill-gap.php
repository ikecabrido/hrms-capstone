<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/database/db.php';

try {
    $instructorId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;

    if ($instructorId <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }

    $database = new Database();
    $pdo = $database->getConnection();

    // Get all active skills
    $skillsStmt = $pdo->query("SELECT id, name, description FROM ld_skill WHERE status = 'active' ORDER BY name ASC");
    $allSkills = $skillsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all learners enrolled in this instructor's courses
    $learnersStmt = $pdo->prepare("
        SELECT DISTINCT e.learner_id, CONCAT(emp.first_name, ' ', emp.last_name) AS learner_name
        FROM ld_enrollment e
        JOIN ld_course c ON c.id = e.course_id
        LEFT JOIN em_employees emp ON emp.employee_id = e.learner_id
        WHERE c.instructor_id = :iid
        ORDER BY emp.first_name, emp.last_name
    ");
    $learnersStmt->execute([':iid' => $instructorId]);
    $learners = $learnersStmt->fetchAll(PDO::FETCH_ASSOC);
    $learnerIds = array_column($learners, 'learner_id');
    $learnerNames = [];
    foreach ($learners as $l) {
        $learnerNames[(int) $l['learner_id']] = $l['learner_name'];
    }

    // Get all completed courses per learner
    $learnerCompleted = [];
    if (!empty($learnerIds)) {
        $compStmt = $pdo->prepare("
            SELECT learner_id, course_id
            FROM ld_enrollment
            WHERE learner_id IN (" . implode(',', array_map('intval', $learnerIds)) . ")
              AND status = 'completed'
        ");
        $compStmt->execute();
        foreach ($compStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $learnerCompleted[(int) $row['learner_id']][] = (int) $row['course_id'];
        }
    }

    // Get skills per course
    $courseSkills = [];
    $allCourseIds = [];
    foreach ($learnerCompleted as $cid) {
        $allCourseIds = array_merge($allCourseIds, $cid);
    }
    $allCourseIds = array_unique($allCourseIds);

    if (!empty($allCourseIds)) {
        $csStmt = $pdo->prepare("
            SELECT cs.course_id, cs.skill_id, s.name
            FROM ld_course_skill cs
            JOIN ld_skill s ON s.id = cs.skill_id
            WHERE cs.course_id IN (" . implode(',', $allCourseIds) . ")
        ");
        $csStmt->execute();
        foreach ($csStmt->fetchAll(PDO::FETCH_ASSOC) as $cs) {
            $courseSkills[(int) $cs['course_id']][] = ['id' => (int) $cs['skill_id'], 'name' => $cs['name']];
        }
    }

    // Build per-learner skill status
    $learnerSkillStatus = [];
    foreach ($learnerIds as $lid) {
        $lid = (int) $lid;
        $completedCourses = $learnerCompleted[$lid] ?? [];
        $acquired = [];
        foreach ($completedCourses as $cid) {
            foreach ($courseSkills[$cid] ?? [] as $s) {
                $acquired[$s['id']] = $s['name'];
            }
        }
        $learnerSkillStatus[$lid] = $acquired;
    }

    // Build skill gap summary across all learners
    $skillGapSummary = [];
    foreach ($allSkills as $skill) {
        $sid = (int) $skill['id'];
        $missingCount = 0;
        $acquiredCount = 0;
        $missingLearners = [];

        foreach ($learnerIds as $lid) {
            $lid = (int) $lid;
            if (isset($learnerSkillStatus[$lid][$sid])) {
                $acquiredCount++;
            } else {
                $missingCount++;
                $missingLearners[] = $learnerNames[$lid] ?? 'Unknown';
            }
        }

        $totalLearners = count($learnerIds);
        $gapRate = $totalLearners > 0 ? round(($missingCount / $totalLearners) * 100) : 0;

        $skillGapSummary[] = [
            'id' => $sid,
            'name' => $skill['name'],
            'description' => $skill['description'] ?? '',
            'total_learners' => $totalLearners,
            'acquired_count' => $acquiredCount,
            'missing_count' => $missingCount,
            'gap_rate' => $gapRate,
            'missing_learners' => array_slice($missingLearners, 0, 10),
            'more_missing' => max(0, $missingCount - 10),
        ];
    }

    // Sort by gap rate descending (most gaps first)
    usort($skillGapSummary, function ($a, $b) {
        return $b['gap_rate'] - $a['gap_rate'];
    });

    // Per-learner summary
    $learnerSummaries = [];
    foreach ($learnerIds as $lid) {
        $lid = (int) $lid;
        $acquired = $learnerSkillStatus[$lid] ?? [];
        $acquiredCount = count($acquired);
        $totalSkills = count($allSkills);
        $gapCount = $totalSkills - $acquiredCount;
        $pct = $totalSkills > 0 ? round(($acquiredCount / $totalSkills) * 100) : 0;

        $learnerSummaries[] = [
            'id' => $lid,
            'name' => $learnerNames[$lid] ?? 'Unknown',
            'acquired_count' => $acquiredCount,
            'gap_count' => $gapCount,
            'total_skills' => $totalSkills,
            'percentage' => $pct,
        ];
    }

    // Sort learners by percentage ascending (most gaps first)
    usort($learnerSummaries, function ($a, $b) {
        return $a['percentage'] - $b['percentage'];
    });

    echo json_encode([
        'success' => true,
        'total_skills' => count($allSkills),
        'total_learners' => count($learnerIds),
        'skills' => $skillGapSummary,
        'learners' => $learnerSummaries,
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load instructor skill gap data.',
        'error' => $e->getMessage(),
    ]);
}
