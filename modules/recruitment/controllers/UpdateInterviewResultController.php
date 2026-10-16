<?php
require_once __DIR__ . '/../classes/Interview.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request");
}

$id = (int) ($_POST['id'] ?? 0);
$result = $_POST['result'] ?? '';

if ($id <= 0) {
    die("Invalid interview ID");
}

if (!in_array($result, ['passed', 'failed'])) {
    die("Invalid result");
}

$model = new Interview();

// update result
$model->updateResult($id, $result);

// OPTIONAL: get application id for redirect
$interview = $model->getById($id);

header("Location: index.php?page=schedule-interview&id=" . $interview['application_id'] . "&msg=" . urlencode("Interview marked as $result"));
exit;
