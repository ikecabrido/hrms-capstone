<?php
/**
 * AJAX endpoint — returns certificate status for all enrolled courses.
 * Used by learner-home and course pages to show certificate badges.
 *
 * Response: { success, items: [{ course_id, course_title, enrolled, completed, has_certificate, verification_code, percent_complete }] }
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['employee_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit;
}

$learnerId = (int) $_SESSION['employee_id'];

include_once __DIR__ . '/../../../classes/progress.php';
include_once __DIR__ . '/../../../classes/certificate.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

try {
    $pdo = (new Database())->getConnection();
    $progress = new Progress($pdo);
    $cert = new Certificate($pdo);

    $stmt = $pdo->prepare("
        SELECT e.id AS enrollment_id, e.course_id, e.status AS enrollment_status,
               c.title AS course_title
        FROM ld_enrollment e
        JOIN ld_course c ON c.id = e.course_id
        WHERE e.learner_id = :lid
        ORDER BY e.enrolled_at DESC
    ");
    $stmt->execute([':lid' => $learnerId]);
    $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $items = [];
    foreach ($enrollments as $en) {
        $eid = (int) $en['enrollment_id'];
        $cid = (int) $en['course_id'];
        $isCompleted = $en['enrollment_status'] === 'completed';
        $pct = $progress->getPercentComplete($eid, $cid);

        $certData = $cert->getByEnrollment($eid);
        $hasCert = $certData !== null;

        // Auto-issue if completed but no cert yet
        if ($isCompleted && !$hasCert) {
            $templateList = $cert->getTemplateList($cid);
            $templateId = !empty($templateList) ? (int) $templateList[0]['id'] : null;
            $validUntil = date('Y-m-d H:i:s', strtotime('+1 year'));
            $cert->issue($learnerId, $cid, $eid, $templateId, $validUntil);
            $certData = $cert->getByEnrollment($eid);
            $hasCert = $certData !== null;
        }

        $items[] = [
            'course_id' => $cid,
            'course_title' => $en['course_title'],
            'enrolled' => true,
            'completed' => $isCompleted,
            'has_certificate' => $hasCert,
            'verification_code' => $certData['verification_code'] ?? null,
            'percent_complete' => $pct,
        ];
    }

    echo json_encode(['success' => true, 'items' => $items]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error.']);
}
