<?php

require_once __DIR__ . '/../../../../auth/session.php';
require_once __DIR__ . '/../../../../database/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $database = new Database();
    $db = $database->getConnection();
    if (!($db instanceof PDO)) {
        throw new RuntimeException('Database connection unavailable');
    }

    $q = trim((string) ($_GET['q'] ?? ''));
    if ($q === '') {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    $type = trim((string) ($_GET['type'] ?? ''));
    $like = '%' . $q . '%';

    $data = [];
    if ($type === 'complaint') {
        $stmt = $db->prepare("
            SELECT id, type AS record_type, title, description, id AS record_id, 'Complaint' AS source, CONCAT('CMP-', LPAD(id, 5, '0')) AS case_number
            FROM lc_complaints
            WHERE CAST(id AS CHAR) LIKE :q
            ORDER BY id ASC
            LIMIT 20
        ");
        $stmt->execute([':q' => $like]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $data[] = [
                'record_id'    => (int) ($r['record_id'] ?? 0),
                'source'       => (string) ($r['source'] ?? ''),
                'record_type'  => (string) ($r['record_type'] ?? ''),
                'case_number'  => (string) ($r['case_number'] ?? ''),
                'title'        => (string) ($r['title'] ?? ''),
                'description'  => (string) ($r['description'] ?? ''),
            ];
        }
    } elseif ($type === 'incident') {
        $stmt = $db->prepare("
            SELECT id, incident_type AS record_type, title, description, id AS record_id, 'Incident Report' AS source, incident_id AS case_number
            FROM lc_incident_report
            WHERE CAST(incident_id AS CHAR) LIKE :q
            ORDER BY incident_id ASC
            LIMIT 20
        ");
        $stmt->execute([':q' => $like]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $data[] = [
                'record_id'    => (int) ($r['record_id'] ?? 0),
                'source'       => (string) ($r['source'] ?? ''),
                'record_type'  => (string) ($r['record_type'] ?? ''),
                'case_number'  => (string) ($r['case_number'] ?? ''),
                'title'        => (string) ($r['title'] ?? ''),
                'description'  => (string) ($r['description'] ?? ''),
            ];
        }
    } else {
        $stmt = $db->prepare("
            SELECT id, type AS record_type, title, description, id AS record_id, 'Complaint' AS source, CONCAT('CMP-', LPAD(id, 5, '0')) AS case_number
            FROM lc_complaints
            WHERE CAST(id AS CHAR) LIKE :q
            UNION ALL
            SELECT id, incident_type AS record_type, title, description, id AS record_id, 'Incident Report' AS source, incident_id AS case_number
            FROM lc_incident_report
            WHERE CAST(incident_id AS CHAR) LIKE :q
            ORDER BY case_number ASC
            LIMIT 20
        ");
        $stmt->execute([':q' => $like]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $data[] = [
                'record_id'    => (int) ($r['record_id'] ?? 0),
                'source'       => (string) ($r['source'] ?? ''),
                'record_type'  => (string) ($r['record_type'] ?? ''),
                'case_number'  => (string) ($r['case_number'] ?? ''),
                'title'        => (string) ($r['title'] ?? ''),
                'description'  => (string) ($r['description'] ?? ''),
            ];
        }
    }

    echo json_encode(['success' => true, 'data' => $data]);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'data' => [],
        'error' => $e->getMessage(),
    ]);
    exit;
}
