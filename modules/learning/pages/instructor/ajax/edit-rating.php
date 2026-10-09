<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

include dirname(__FILE__, 6) . '/database/db.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['id'])) {
        http_response_code(400);
        die(json_encode(['error' => 'Invalid request data']));
    }

    $ratingId = (int)$data['id'];
    $rating = (int)($data['rating'] ?? 5);
    $review = $data['review'] ?? null;
    $courseId = $data['course_id'] ?? null;

    if ($rating < 1 || $rating > 5) {
        http_response_code(400);
        die(json_encode(['error' => 'Rating must be between 1 and 5']));
    }

    $stmt = $pdo->prepare("
        UPDATE ld_rating 
        SET rating = ?, review = ?, updated_at = NOW()
        WHERE id = ? AND course_id = ?
    ");

    $stmt->execute([
        $rating,
        $review,
        $ratingId,
        $courseId
    ]);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'id' => $ratingId,
        'message' => 'Rating updated successfully'
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
