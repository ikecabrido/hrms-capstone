<?php

require_once __DIR__ . '/../../../../auth/session.php';
require_once __DIR__ . '/../../../../database/db.php';
require_once __DIR__ . '/../../classes/LegalCaseManager.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    exit(0);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$rawQuery = trim((string) ($input['query'] ?? ''));

if ($rawQuery === '') {
    echo json_encode([
        'success' => true,
        'message' => "I can help analyze the legal cases recorded in the system. Try asking about case status, agencies, priority, recent cases, or cases requiring action.",
    ]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    if (!($db instanceof PDO)) {
        throw new RuntimeException('Database connection unavailable.');
    }

    $manager = new LegalCaseManager($db);
    $response = $manager->getCaseChatResponse($rawQuery);

    echo json_encode([
        'success' => true,
        'message' => $response['message'],
    ]);
    exit;
} catch (Exception $e) {
    error_log('Legal Case Analytics Chat error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'I couldn\'t check the case data right now. Please try again in a moment.']);
    exit;
}
