<?php
header('Content-Type: application/json');
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit;
}

require_once dirname(__DIR__, 5) . '/database/db.php';

try {
    $pdo = (new Database())->getConnection();
    $input = json_decode(file_get_contents('php://input'), true);

    $pathId = (int)($input['path_id'] ?? 0);
    if ($pathId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Path ID required.']);
        exit;
    }

    // Fetch current state
    $stmt = $pdo->prepare("SELECT id, is_public, type FROM ld_learning_path WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $pathId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => false, 'error' => 'Learning path not found.']);
        exit;
    }

    $newValue = empty($row['is_public']) ? 1 : 0;
    $update = $pdo->prepare("UPDATE ld_learning_path SET is_public = :pub WHERE id = :id");
    $update->execute([':pub' => $newValue, ':id' => $pathId]);

    echo json_encode([
        'success' => true,
        'is_public' => (bool) $newValue,
        'message' => $newValue ? 'Path is now visible in catalog.' : 'Path is now private.'
    ]);
} catch (Throwable $e) {
    error_log('toggle-kt-path-public error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error.']);
}
