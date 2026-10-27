<?php
/**
 * Create a skill from the Trainings → Skills tab.
 *
 * Writes to the existing ld_skill table and returns the created (or matching
 * existing) row as JSON so the skills grid can add it without a reload.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 4) . '/classes/skill.php';

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

$name = trim((string) ($_POST['name'] ?? ''));
$name = preg_replace('/\s+/', ' ', $name);
$description = trim((string) ($_POST['description'] ?? ''));
$icon = trim((string) ($_POST['icon'] ?? Skill::DEFAULT_ICON));

if ($name === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Skill name is required.']);
    exit;
}

if (strlen($name) > 150) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Skill name is too long (150 characters max).']);
    exit;
}
if (!Skill::isValidIcon($icon)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Choose an icon from the available list.']);
    exit;
}

try {
    $pdo = (new Database())->getConnection();

    // ld_skill has no unique index on name, so guard against duplicates here.
    $lookup = $pdo->prepare('SELECT id, name, status, icon FROM ld_skill WHERE LOWER(name) = LOWER(:name) LIMIT 1');
    $lookup->execute([':name' => $name]);
    $existing = $lookup->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        if ((string) ($existing['status'] ?? '') !== 'active') {
            $pdo->prepare("UPDATE ld_skill SET status = 'active' WHERE id = :id")
                ->execute([':id' => (int) $existing['id']]);
        }

        echo json_encode([
            'success' => true,
            'created' => false,
            'id' => (int) $existing['id'],
            'name' => $existing['name'],
            'description' => '',
            'icon' => Skill::isValidIcon((string) ($existing['icon'] ?? '')) ? $existing['icon'] : Skill::DEFAULT_ICON,
            'message' => 'That skill already exists, so it was reused.',
        ]);
        exit;
    }

    $skill = new Skill($pdo);
    $result = $skill->create([
        'name' => $name,
        'description' => $description,
        'icon' => $icon,
        'status' => 'active',
    ]);

    if (empty($result['success'])) {
        http_response_code(422);
        echo json_encode($result);
        exit;
    }

    echo json_encode([
        'success' => true,
        'created' => true,
        'id' => (int) $result['id'],
        'name' => $name,
        'description' => $description,
        'icon' => $icon,
        'message' => 'Skill added.',
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create skill.',
        'error' => $e->getMessage(),
    ]);
}
exit;
