<?php
$ctx = require __DIR__ . '/_bootstrap.php';
aiEndpointPostOnly();
CSRF::requireValid();

require_once dirname(__DIR__, 4) . '/classes/AICourseContext.php';

$employeeId = (int) ($_POST['employee_id'] ?? 0);
$selectedSkillIds = $_POST['selected_skill_ids'] ?? [];
$excludedEvidence = $_POST['excluded'] ?? [];

if (!is_array($selectedSkillIds)) {
    $selectedSkillIds = [$selectedSkillIds];
}
if (!is_array($excludedEvidence)) {
    $excludedEvidence = [$excludedEvidence];
}

try {
    $result = (new AICourseContext($ctx['pdo']))->build(
        $employeeId,
        array_slice($selectedSkillIds, 0, 50),
        array_slice($excludedEvidence, 0, 20)
    );
    aiEndpointJson($result['success'] ? 200 : 422, $result);
} catch (Throwable $e) {
    aiEndpointError(500, 'The trainee context could not be prepared.');
}
