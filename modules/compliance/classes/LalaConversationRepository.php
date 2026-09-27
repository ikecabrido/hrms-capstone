<?php

class LalaConversationRepository
{
    private $conn;

    public function __construct(PDO $pdo = null)
    {
        if ($pdo instanceof PDO) {
            $this->conn = $pdo;
        } else {
            $database = new Database();
            $this->conn = $database->getConnection();
        }
    }

    public function log(array $data): bool
    {
        $sql = "
            INSERT INTO lc_lala_conversations
                (session_id, user_id, question, normalized_question, detected_intent, source, matched_record_id, response, confidence)
            VALUES
                (:session_id, :user_id, :question, :normalized_question, :detected_intent, :source, :matched_record_id, :response, :confidence)
        ";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':session_id' => substr((string) ($data['session_id'] ?? ''), 0, 100),
            ':user_id' => $data['user_id'] ?? null,
            ':question' => (string) ($data['question'] ?? ''),
            ':normalized_question' => (string) ($data['normalized_question'] ?? ''),
            ':detected_intent' => (string) ($data['detected_intent'] ?? ''),
            ':source' => (string) ($data['source'] ?? ''),
            ':matched_record_id' => $data['matched_record_id'] ?? null,
            ':response' => (string) ($data['response'] ?? ''),
            ':confidence' => (string) ($data['confidence'] ?? ''),
        ]);
    }
}
