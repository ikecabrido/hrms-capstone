<?php
require_once __DIR__ . '/../../../../auth/session.php';
require_once __DIR__ . '/../../../../database/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

$key = strtolower(trim((string)($input['notification_key'] ?? $input['scenario'] ?? $input['template_code'] ?? '')));
if ($key === '') {
    echo json_encode(['success' => false, 'message' => 'Missing key']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!($db instanceof PDO)) {
        throw new RuntimeException('Database connection unavailable');
    }

    $stmt = $db->prepare('SELECT component, template_text FROM lc_email_templates WHERE (template_code = :key OR scenario = :key) AND status = "Active" ORDER BY component_order ASC');
    $stmt->execute([':key' => $key]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $subject = '';
    $body = '';
    foreach ($rows as $row) {
        if ($row['component'] === 'subject' && $subject === '') $subject = $row['template_text'];
        if ($row['component'] === 'body' && $body === '') $body = $row['template_text'];
    }

    echo json_encode([
        'success' => true,
        'subject' => $subject,
        'body' => $body
    ]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
