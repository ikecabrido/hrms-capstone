<?php

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../classes/Policy.php';

$pageTitle = 'Edit Policy';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

$db = (new Database())->getConnection();
$policy = new Policy($db);

$policyId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($policyId <= 0) {
    header('Location: ?page=policy-management');
    exit;
}

$policyData = $policy->getPolicyById($policyId);
if (!$policyData) {
    header('Location: ?page=policy-management');
    exit;
}

header('Location: ?page=policy-view&id=' . $policyId);
exit;
