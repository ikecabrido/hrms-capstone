<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../core/TimeDatabase.php';

try {
    $db = TimeDatabase::getInstance();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT shift_id, shift_name, start_time, end_time, is_active FROM ta_shifts ORDER BY shift_name ASC");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $templates = array_map(function($r){
        return [
            'shift_id' => (int)$r['shift_id'],
            'shift_name' => $r['shift_name'],
            'start_time' => $r['start_time'],
            'end_time' => $r['end_time'],
            'is_active' => (int)$r['is_active']
        ];
    }, $rows ?: []);

    echo json_encode(['success' => true, 'templates' => $templates]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

