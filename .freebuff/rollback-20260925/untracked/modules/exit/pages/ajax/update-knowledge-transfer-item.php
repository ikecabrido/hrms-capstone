<?php
/**
 * AJAX — move one knowledge transfer item along its status.
 *
 * The item list on pages/knowledge-transfer.php is where a handover is actually worked, so the
 * status change belongs to this module rather than to the L&D screen that shows the same rows.
 * The learning module's learner-facing equivalent (its learner/knowledge-transfer page) keeps its
 * own endpoint for the successor's own edits.
 *
 * POST item_id=<id>&status=pending|in_progress|completed
 * Output: {success, status, badge} — badge is the re-rendered pill, so the page need not know
 * how a status looks.
 */
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'POST required.']);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

/** The statuses exit_knowledge_transfer_items accepts, and the colours the pill uses. */
const KT_ITEM_STATUSES = [
    'pending'     => ['rgba(100,116,139,0.12)', '#475569', 'Pending'],
    'in_progress' => ['rgba(217,119,6,0.12)', '#b45309', 'In progress'],
    'completed'   => ['rgba(5,150,105,0.12)', '#047857', 'Completed'],
];

$itemId = (int) ($_POST['item_id'] ?? 0);
$status = (string) ($_POST['status'] ?? '');

if ($itemId <= 0 || !isset(KT_ITEM_STATUSES[$status])) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'A valid item id and status are required.']);
    exit;
}

try {
    require_once dirname(__DIR__, 4) . '/database/db.php';

    $pdo = (new Database())->getConnection();
    $stmt = $pdo->prepare('UPDATE exit_knowledge_transfer_items SET status = :status WHERE id = :id');
    $stmt->execute([':status' => $status, ':id' => $itemId]);

    if ($stmt->rowCount() === 0) {
        // Either the item is gone, or it already had that status. Both are "nothing changed",
        // which the page should not report as a failure — but an unknown id is worth saying.
        $exists = $pdo->prepare('SELECT 1 FROM exit_knowledge_transfer_items WHERE id = :id');
        $exists->execute([':id' => $itemId]);
        if (!$exists->fetchColumn()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'That transfer item no longer exists.']);
            exit;
        }
    }

    [$background, $colour, $label] = KT_ITEM_STATUSES[$status];

    echo json_encode([
        'success' => true,
        'status'  => $status,
        'badge'   => '<span class="kt-item-badge" style="display:inline-block;padding:0.15rem 0.6rem;border-radius:999px;'
            . 'font-size:0.68rem;font-weight:700;background:' . $background . ';color:' . $colour . ';">' . $label . '</span>',
    ]);
} catch (Throwable $e) {
    error_log('update-knowledge-transfer-item error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error.']);
}
