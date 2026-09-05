<?php

require_once __DIR__ . '/../../../../database/db.php';
require_once __DIR__ . '/../../../../auth/session.php';

header('Content-Type: application/json');

$db = (new Database())->getConnection();

if ($db === null) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.', 'evidence' => []]);
    exit;
}

$complaintId = isset($_GET['complaint_id']) ? (int) $_GET['complaint_id'] : 0;
if ($complaintId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid complaint ID.', 'evidence' => []]);
    exit;
}

$uploadDir = __DIR__ . '/../../assets/uploads/complaint_evidence/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'DELETE' || (($method === 'POST') && (($_POST['action'] ?? '') === 'delete'))) {
    $evidenceId = isset($_GET['evidence_id']) ? (int) $_GET['evidence_id'] : (int) ($_POST['evidence_id'] ?? 0);
    if ($evidenceId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid evidence ID.']);
        exit;
    }
    try {
        $stmt = $db->prepare("SELECT file_path FROM lc_complaint_evidence WHERE id = :id AND complaint_id = :complaint_id LIMIT 1");
        $stmt->execute([':id' => $evidenceId, ':complaint_id' => $complaintId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['file_path'])) {
            $fullPath = realpath(__DIR__ . '/../../' . $row['file_path']);
            if ($fullPath && strpos($fullPath, realpath($uploadDir)) === 0 && file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }
        $db->prepare("DELETE FROM lc_complaint_evidence WHERE id = :id AND complaint_id = :complaint_id")->execute([':id' => $evidenceId, ':complaint_id' => $complaintId]);
        echo json_encode(['success' => true, 'message' => 'Evidence removed.']);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($method === 'POST') {
    if (empty($_FILES['evidence'])) {
        echo json_encode(['success' => false, 'message' => 'No files uploaded.']);
        exit;
    }

    $files = $_FILES['evidence'];
    $fileCount = is_array($files['name']) ? count($files['name']) : 1;
    $results = [];
    $successCount = 0;
    $errorCount = 0;

    for ($i = 0; $i < $fileCount; $i++) {
        $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
        $type = is_array($files['type']) ? $files['type'][$i] : $files['type'];
        $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
        $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];
        $size = is_array($files['size']) ? $files['size'][$i] : $files['size'];

        if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($tmpName)) {
            $results[] = ['success' => false, 'file_name' => $name, 'message' => 'Upload error.'];
            $errorCount++;
            continue;
        }

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $baseName = pathinfo($name, PATHINFO_FILENAME);
        $safeBase = preg_replace('/[^A-Za-z0-9_-]+/', '_', $baseName);
        $unique = time() . '_' . $i . '_' . bin2hex(random_bytes(3)) . ($ext ? '.' . $ext : '');
        $savePath = 'uploads/complaint_evidence/' . $unique;
        $fullSavePath = $uploadDir . $unique;

        if (!move_uploaded_file($tmpName, $fullSavePath)) {
            $results[] = ['success' => false, 'file_name' => $name, 'message' => 'Failed to move uploaded file.'];
            $errorCount++;
            continue;
        }

        try {
            $stmt = $db->prepare("INSERT INTO lc_complaint_evidence (complaint_id, file_name, file_path, file_type, file_size, uploaded_by, description) VALUES (:complaint_id, :file_name, :file_path, :file_type, :file_size, :uploaded_by, :description)");
            $stmt->execute([
                ':complaint_id' => $complaintId,
                ':file_name' => $name,
                ':file_path' => $savePath,
                ':file_type' => $type,
                ':file_size' => $size,
                ':uploaded_by' => $_SESSION['employee_id'] ?? null,
                ':description' => null,
            ]);
            $results[] = ['success' => true, 'file_name' => $name, 'evidence_id' => (int) $db->lastInsertId()];
            $successCount++;
        } catch (Throwable $e) {
            @unlink($fullSavePath);
            $results[] = ['success' => false, 'file_name' => $name, 'message' => 'Database error: ' . $e->getMessage()];
            $errorCount++;
        }
    }

    $response = [
        'success' => $successCount > 0,
        'message' => '',
        'results' => $results,
        'success_count' => $successCount,
        'error_count' => $errorCount,
    ];
    if ($successCount > 0 && $errorCount === 0) {
        $response['message'] = "All {$successCount} file(s) uploaded successfully.";
    } elseif ($successCount > 0 && $errorCount > 0) {
        $response['message'] = "{$successCount} file(s) uploaded, {$errorCount} file(s) failed.";
    } else {
        $response['message'] = "All {$errorCount} file(s) failed to upload.";
    }
    echo json_encode($response);
    exit;
}

try {
    $stmt = $db->prepare("SELECT id, file_name, file_path, file_type, file_size, description, created_at FROM lc_complaint_evidence WHERE complaint_id = :complaint_id ORDER BY id ASC");
    $stmt->execute([':complaint_id' => $complaintId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $evidence = [];
    foreach ($rows as $row) {
        $evidence[] = [
            'id' => (int) $row['id'],
            'file_name' => (string) $row['file_name'],
            'file_path' => (string) $row['file_path'],
            'file_type' => (string) ($row['file_type'] ?? ''),
            'file_size' => (int) $row['file_size'],
            'description' => (string) ($row['description'] ?? ''),
            'uploaded_at' => (string) $row['created_at'],
        ];
    }
    echo json_encode(['success' => true, 'evidence' => $evidence]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'evidence' => []]);
}
