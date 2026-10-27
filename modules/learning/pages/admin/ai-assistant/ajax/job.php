<?php
/**
 * Admin AI generation job endpoint.
 *
 * Actions: create, approve, advance, retry, cancel, get.
 * Every action is admin-only, POST-only, CSRF-protected, and schema-checked by
 * the shared bootstrap.
 */
$ctx = require __DIR__ . '/_bootstrap.php';
aiEndpointPostOnly();
CSRF::requireValid();

require_once dirname(__DIR__, 4) . '/classes/AICourseGenerator.php';

$action = strtolower(trim((string) ($_POST['action'] ?? '')));
$jobId = (int) ($_POST['job_id'] ?? 0);

try {
    $generator = new AICourseGenerator($ctx['pdo'], null, null, $ctx['audit']);

    if ($action === 'create') {
        $selectedSkillIds = $_POST['selected_skill_ids'] ?? [];
        $excludedEvidence = $_POST['excluded'] ?? [];
        if (!is_array($selectedSkillIds)) {
            $selectedSkillIds = [$selectedSkillIds];
        }
        if (!is_array($excludedEvidence)) {
            $excludedEvidence = [$excludedEvidence];
        }

        $result = $generator->createJob(
            $ctx['employeeId'],
            (int) ($_POST['course_owner_id'] ?? 0),
            (int) ($_POST['learner_id'] ?? 0),
            [
                'goal' => (string) ($_POST['goal'] ?? ''),
                'source_notes' => (string) ($_POST['source_notes'] ?? ''),
                'module_count' => (int) ($_POST['module_count'] ?? 4),
                'lessons_per_module' => (int) ($_POST['lessons_per_module'] ?? 3),
                'difficulty' => (string) ($_POST['difficulty'] ?? 'intermediate'),
                'model' => (string) ($_POST['model'] ?? ''),
            ],
            array_slice($selectedSkillIds, 0, 50),
            array_slice($excludedEvidence, 0, 20)
        );
        aiEndpointJson((int) ($result['http_status'] ?? 200), $result);
    }

    if ($action === 'list') {
        aiEndpointJson(200, [
            'success' => true,
            'message' => 'Jobs loaded.',
            'jobs' => $generator->listJobs(25),
        ]);
    }

    if ($action === 'apply') {
        require_once dirname(__DIR__, 4) . '/classes/AICourseWriter.php';
        $writer = new AICourseWriter($ctx['pdo'], $ctx['audit']);
        $result = $writer->apply($jobId, $ctx['employeeId']);
        if (($result['success'] ?? false) === true) {
            aiEndpointJson(200, $result);
        }
        aiEndpointError((int) ($result['http'] ?? 422), (string) ($result['message'] ?? 'The draft course could not be created.'));
    }

    if ($jobId <= 0) {
        aiEndpointError(422, 'A valid job id is required.');
    }

    if ($action === 'approve') {
        $outlineJson = (string) ($_POST['outline_json'] ?? '');
        $outline = json_decode($outlineJson, true);
        if (!is_array($outline)) {
            aiEndpointError(422, 'The outline JSON is not valid.');
        }
        $result = $generator->approveOutline($jobId, $ctx['employeeId'], $outline);
        aiEndpointJson((int) ($result['http_status'] ?? 200), $result);
    }

    if ($action === 'advance') {
        $result = $generator->advance($jobId, $ctx['employeeId']);
        aiEndpointJson((int) ($result['http_status'] ?? 200), $result);
    }

    if ($action === 'retry') {
        $result = $generator->retry($jobId, $ctx['employeeId']);
        aiEndpointJson((int) ($result['http_status'] ?? 200), $result);
    }

    if ($action === 'cancel') {
        $result = $generator->cancel($jobId, $ctx['employeeId']);
        aiEndpointJson((int) ($result['http_status'] ?? 200), $result);
    }

    if ($action === 'get') {
        $job = $generator->getJob($jobId);
        if ($job === null) {
            aiEndpointError(404, 'Job not found.');
        }
        aiEndpointJson(200, [
            'success' => true,
            'message' => 'Job loaded.',
            'job' => $job,
        ]);
    }

    aiEndpointError(422, 'Unknown job action.');
} catch (Throwable $e) {
    aiEndpointError(500, 'The AI job action could not be completed.');
}
