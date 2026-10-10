<?php

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../classes/DocumentPreviewController.php';

$pageTitle = 'Document Preview';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
if (!isset($user) || empty($user)) {
    $user = $_SESSION['user'] ?? [];
}
if (!isset($db) || empty($db)) {
    $db = (new Database())->getConnection();
}
if ($db === null) {
    throw new RuntimeException('Database connection is unavailable.');
}

$controller = new DocumentPreviewController($db, $user ?? []);
$controller->handleRequest();
