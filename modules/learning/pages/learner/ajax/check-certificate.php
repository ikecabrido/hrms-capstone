<?php
/**
 * AJAX endpoint — checks whether a learner has completed a course
 * and auto-issues a certificate if so.
 *
 * POST: { course_id }
 * GET:  ?course_id=N
 *
 * Response: { success, issued, certificate, message }
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['employee_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit;
}

include_once __DIR__ . '/../../../classes/progress.php';
include_once __DIR__ . '/../../../classes/certificate.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

$learnerId = (int) $_SESSION['employee_id'];
$courseId = (int) ($_POST['course_id'] ?? $_GET['course_id'] ?? 0);

if ($courseId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Course ID is required.']);
    exit;
}

try {
    $pdo = (new Database())->getConnection();
    $progress = new Progress($pdo);
    $cert = new Certificate($pdo);

    // Find the enrollment
    $stmt = $pdo->prepare("SELECT id, status FROM ld_enrollment WHERE learner_id = :lid AND course_id = :cid LIMIT 1");
    $stmt->execute([':lid' => $learnerId, ':cid' => $courseId]);
    $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$enrollment) {
        echo json_encode(['success' => false, 'message' => 'Not enrolled in this course.']);
        exit;
    }

    $enrollmentId = (int) $enrollment['id'];

    // Get current progress
    $percentComplete = $progress->getPercentComplete($enrollmentId, $courseId);
    $allContentDone = $progress->hasCompletedAllCourseContent($enrollmentId, $courseId);

    // Check if certificate already exists
    $existingCert = $cert->getByEnrollment($enrollmentId);

    $issued = false;
    $certificateData = null;

    if ($existingCert) {
        $certificateData = $existingCert;
    } elseif ($allContentDone && $enrollment['status'] === 'completed') {
        // Auto-issue certificate
        $templateList = $cert->getTemplateList($courseId);
        $templateId = !empty($templateList) ? (int) $templateList[0]['id'] : null;
        $validUntil = date('Y-m-d H:i:s', strtotime('+1 year'));

        $result = $cert->issue($learnerId, $courseId, $enrollmentId, $templateId, $validUntil);

        if ($result['success']) {
            $issued = true;
            $certificateData = $cert->getByEnrollment($enrollmentId);
        }
    }

    // Get course title
    $courseStmt = $pdo->prepare("SELECT title FROM ld_course WHERE id = :cid LIMIT 1");
    $courseStmt->execute([':cid' => $courseId]);
    $courseTitle = $courseStmt->fetchColumn() ?: 'Unknown Course';

    echo json_encode([
        'success' => true,
        'enrollment_status' => $enrollment['status'],
        'percent_complete' => $percentComplete,
        'all_content_done' => $allContentDone,
        'certificate' => $certificateData,
        'issued' => $issued,
        'course_title' => $courseTitle,
        'message' => $issued
            ? 'Congratulations! Your certificate has been issued for completing "' . $courseTitle . '"!'
            : ($certificateData
                ? 'You already have a certificate for this course.'
                : ($allContentDone
                    ? 'Course content completed. Certificate will be issued upon final review.'
                    : round($percentComplete) . '% complete. Keep going!'))
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
