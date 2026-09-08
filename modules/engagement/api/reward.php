<?php
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/utils.php';

use App\Controllers\RewardController;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) && empty($_SESSION['employee_id'])) {
    jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
}

$controller = new RewardController();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = inputData();
        $name = trim((string)($data['reward_name'] ?? $data['name'] ?? ''));
        $points = (int)($data['reward_points'] ?? $data['points_required'] ?? 0);

        if ($name === '' || $points < 1) {
            jsonResponse(['success' => false, 'error' => 'Reward name and points are required.'], 422);
        }

        $id = $controller->store([
            'name' => $name,
            'description' => trim((string)($data['reward_description'] ?? $data['description'] ?? '')),
            'points_required' => $points,
        ]);

        jsonResponse(['success' => true, 'id' => $id], 201);
    }

    jsonResponse(['success' => true, 'data' => $controller->index()]);
} catch (Throwable $exception) {
    jsonResponse(['success' => false, 'error' => $exception->getMessage()], 500);
}

