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
    $db = new PDO('mysql:host=localhost;dbname=hrms;charset=utf8mb4', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
}

$controller = new DocumentPreviewController($db, $user ?? []);
$controller->handleRequest();
