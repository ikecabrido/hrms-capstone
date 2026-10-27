<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

if (empty($_SESSION['employee_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Your session has expired. Please sign in again.']);
    exit;
}

require_once dirname(__DIR__, 4) . '/classes/skill.php';

$skillId = (int) ($_POST['id'] ?? 0);
$name = preg_replace('/\s+/', ' ', trim((string) ($_POST['name'] ?? '')));
$description = trim((string) ($_POST['description'] ?? ''));
$icon = trim((string) ($_POST['icon'] ?? ''));
$status = trim((string) ($_POST['status'] ?? 'active'));

if ($skillId <= 0 || $name === '' || mb_strlen($name) > 150) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'A valid skill ID and name (up to 150 characters) are required.']);
    exit;
}
if (!Skill::isValidIcon($icon)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Choose an icon from the available list.']);
    exit;
}
if (!in_array($status, ['active', 'archived'], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Choose a valid skill status.']);
    exit;
}

try {
    $pdo = (new Database())->getConnection();
    $existing = $pdo->prepare('SELECT id FROM ld_skill WHERE id = :id LIMIT 1');
    $existing->execute([':id' => $skillId]);
    if (!$existing->fetchColumn()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Skill not found.']);
        exit;
    }

    $duplicate = $pdo->prepare('SELECT id FROM ld_skill WHERE LOWER(name) = LOWER(:name) AND id <> :id LIMIT 1');
    $duplicate->execute([':name' => $name, ':id' => $skillId]);
    if ($duplicate->fetchColumn()) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Another skill already uses that name.']);
        exit;
    }

    $skillService = new Skill($pdo);
    $result = $skillService->update([
        'id' => $skillId,
        'name' => $name,
        'description' => $description,
        'icon' => $icon,
        'status' => $status,
        'suggested' => !empty($_POST['suggested']),
    ]);
    if (empty($result['success'])) {
        http_response_code(422);
        echo json_encode($result);
        exit;
    }

    echo json_encode([
        'success' => true,
        'id' => $skillId,
        'name' => $name,
        'description' => $description,
        'icon' => $icon,
        'status' => $status,
        'message' => 'Skill updated successfully.',
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('update-skill.php failed: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to update skill.']);
}
