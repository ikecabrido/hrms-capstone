<?php
/**
 * AJAX — run a scheduled Workforce-integration job and report its status.
 *
 * POST JSON: { job: string, force: bool }
 * Header:    X-CSRF-Token (window.CSRF_TOKEN from index.php)
 *
 * Used by pages/admin/workforce-integration.php for two things:
 *   * the "Run now" button on the schedule panel
 *   * the auto-run it fires when the job's slot has passed unrun, which is what keeps
 *     the schedule on a deployment where no OS scheduler was configured
 *
 * Both are admins-only, and a job only starts when it is actually due unless the caller
 * asks for force — so a page load cannot hammer the job and two runs cannot overlap
 * (LearningCron::run refuses the second one).
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit;
}

require_once dirname(__FILE__, 6) . '/database/db.php';
require_once dirname(__FILE__, 4) . '/classes/csrf.php';
require_once dirname(__FILE__, 4) . '/classes/cron.php';
require_once dirname(__FILE__, 4) . '/classes/workforceintegrationview.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // Admins only — this runs a job for the whole module.
    if (empty($_SESSION['employee_id']) || (($_SESSION['learning_role'] ?? '') !== 'admin' && ($_SESSION['is_admin'] ?? 0) != 1)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Only administrators can run the scheduled jobs.']);
        exit;
    }

    CSRF::requireValid();

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid request data.']);
        exit;
    }

    $job = trim((string) ($input['job'] ?? LearningCron::DEFAULT_JOB));
    if (!LearningCron::isJob($job)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'Unknown scheduled job.']);
        exit;
    }

    // A forced run is the admin pressing "Run now"; anything else waits for the slot.
    $force = !empty($input['force']);
    $cron = new LearningCron($pdo);

    if (!$force && !$cron->isDue($job)) {
        $status = $cron->getStatus($job);
        echo json_encode([
            'success' => true,
            'ran'     => false,
            'message' => LearningCron::jobLabel($job) . ' is not due yet.',
            'status'  => $status,
            'panel'   => WorkforceIntegrationView::cronPanelHtml($status, true),
        ]);
        exit;
    }

    // "Run now" is a manual run; the automatic catch-up is the schedule firing, with the
    // admin who happened to open the page recorded as the actor.
    $result = $cron->run($job, $force ? 'manual' : 'scheduled', (int) $_SESSION['employee_id']);
    $status = $result['status'];

    if (!$result['success']) {
        // 409 tells the caller the job is still running; anything else is a real failure.
        http_response_code(!empty($status['running']) ? 409 : 500);
    }

    echo json_encode([
        'success'  => $result['success'],
        'ran'      => true,
        'message'  => $result['message'],
        'duration_ms' => $result['duration_ms'],
        'records_processed' => $result['records_processed'],
        'status'   => $status,
        // The panel is rendered from the status the run just produced, so the page does
        // not need a reload to show the new last-run line.
        'panel'    => WorkforceIntegrationView::cronPanelHtml($status, true),
    ]);
} catch (Throwable $e) {
    error_log('workforce-integration-run-cron error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
