<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../../database/db.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) throw new Exception('Invalid JSON payload');

    $shift_id = isset($data['shift_id']) ? (int)$data['shift_id'] : 0;
    if (!$shift_id) throw new Exception('shift_id is required');

    $shift_name = isset($data['shift_name']) ? trim($data['shift_name']) : null;
    $rep_start = isset($data['start_time']) ? $data['start_time'] : null;
    $rep_end = isset($data['end_time']) ? $data['end_time'] : null;
    $break_duration = isset($data['break_duration']) ? (int)$data['break_duration'] : null;
    $description = isset($data['description']) ? trim($data['description']) : null;
    $is_active = isset($data['is_active']) ? (int)$data['is_active'] : null;
    $include_saturday = isset($data['include_saturday']) ? (int)$data['include_saturday'] : null;
    $weekdays = isset($data['weekdays']) ? $data['weekdays'] : [];

    $db = TimeDatabase::getInstance();
    $conn = $db->getConnection();

    $conn->beginTransaction();

    // Update main shift record if values provided
    if ($shift_name !== null || $rep_start !== null || $rep_end !== null) {
        $parts = [];
        $params = [];
        if ($shift_name !== null) { $parts[] = 'shift_name = ?'; $params[] = $shift_name; }
        if ($rep_start !== null) { $parts[] = 'start_time = ?'; $params[] = date('H:i:s', strtotime($rep_start)); }
        if ($rep_end !== null) { $parts[] = 'end_time = ?'; $params[] = date('H:i:s', strtotime($rep_end)); }
        if ($break_duration !== null) { $parts[] = 'break_duration = ?'; $params[] = $break_duration; }
        if ($description !== null) { $parts[] = 'description = ?'; $params[] = $description; }
        if ($is_active !== null) { $parts[] = 'is_active = ?'; $params[] = $is_active; }
        if ($include_saturday !== null) { $parts[] = 'include_saturday = ?'; $params[] = $include_saturday; }
        if (count($parts) > 0) {
            $params[] = $shift_id;
            $sql = "UPDATE ta_shifts SET " . implode(', ', $parts) . ", updated_at = NOW() WHERE shift_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
        }
    }

    // Upsert weekday configs
    $selectWeek = $conn->prepare("SELECT id, start_time, end_time, break_start, break_end FROM ta_shift_weekday_times WHERE shift_id = ? AND weekday = ? LIMIT 1");
    $updateWeek = $conn->prepare("UPDATE ta_shift_weekday_times SET start_time = ?, end_time = ?, break_start = ?, break_end = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
    $insertWeek = $conn->prepare("INSERT INTO ta_shift_weekday_times (shift_id, weekday, start_time, end_time, break_start, break_end, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");

    foreach ($weekdays as $w => $cfg) {
        $wInt = (int)$w;
        if ($wInt === 0) continue; // skip Sundays
        $assigned = !empty($cfg['assigned']);
        $s = isset($cfg['start']) && $cfg['start'] !== '' ? date('H:i:s', strtotime($cfg['start'])) : null;
        $e = isset($cfg['end']) && $cfg['end'] !== '' ? date('H:i:s', strtotime($cfg['end'])) : null;
        $bs = isset($cfg['break_start']) && $cfg['break_start'] !== '' ? date('H:i:s', strtotime($cfg['break_start'])) : null;
        $be = isset($cfg['break_end']) && $cfg['break_end'] !== '' ? date('H:i:s', strtotime($cfg['break_end'])) : null;

        $selectWeek->execute([$shift_id, $wInt]);
        $exists = $selectWeek->fetch(PDO::FETCH_ASSOC);

        if ($assigned) {
            if (!$s || !$e) {
                $conn->rollBack();
                throw new Exception("Weekday {$wInt} requires start and end time");
            }
            if ($exists) {
                $updateWeek->execute([$s, $e, $bs, $be, 1, $exists['id']]);
            } else {
                $insertWeek->execute([$shift_id, $wInt, $s, $e, $bs, $be, 1]);
            }
        } else {
            // if there is an existing row, mark inactive
            if ($exists) {
                $updateWeek->execute([
                    $exists['start_time'],
                    $exists['end_time'],
                    $exists['break_start'],
                    $exists['break_end'],
                    0,
                    $exists['id']
                ]);
            }
        }
    }

    $conn->commit();

    echo json_encode(['success' => true, 'shift_id' => $shift_id]);

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) $conn->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
