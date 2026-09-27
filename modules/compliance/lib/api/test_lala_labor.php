<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
session_start();
$_SESSION['employee_id'] = 1;
$_SESSION['employee_name'] = 'Cheska Test';
$_SESSION['employee_code'] = 'TEST001';
$_SESSION['department_id'] = 1;
$_SESSION['department_name'] = 'Testing';
$_SESSION['position_id'] = 1;
$_SESSION['position_name'] = 'Tester';
$_SESSION['role_id'] = 1;
$_SESSION['role_name'] = 'Admin';

require_once __DIR__ . '/../../../../auth/session.php';
require_once __DIR__ . '/../../../../database/db.php';
require_once __DIR__ . '/../../classes/LalaNormalizer.php';
require_once __DIR__ . '/../../classes/LalaIntentDetector.php';
require_once __DIR__ . '/../../classes/LalaContextManager.php';
require_once __DIR__ . '/../../classes/LalaResponseBuilder.php';
require_once __DIR__ . '/../../classes/LalaConversationRepository.php';
require_once __DIR__ . '/../../classes/LalaQuestionPatternRepository.php';
require_once __DIR__ . '/../../classes/LalaPolicyMatcher.php';
require_once __DIR__ . '/../../classes/LalaLaborLawMatcher.php';
require_once __DIR__ . '/../../classes/LalaService.php';

header('Content-Type: application/json');

$tests = ['labor law overtime', 'minimum wage', 'holiday pay', 'maternity leave', 'paternity leave', 'night differential', '13th month pay', 'social security', 'philhealth', 'pagibig', 'data privacy act', 'anti harassment law', 'workplace safety', 'employee termination'];
foreach ($tests as $q) {
    $service = new LalaService();
    $result = $service->handleQuery($q, []);
    echo "QUERY: $q\n";
    echo json_encode($result, JSON_PRETTY_PRINT);
    echo "\n\n";
}
