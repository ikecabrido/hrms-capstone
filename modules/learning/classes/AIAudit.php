<?php
/**
 * AIAudit — append-only security and configuration audit trail.
 *
 * Metadata must never contain provider keys, ciphertext, nonces, or raw
 * request bodies. Callers pass display-safe values such as ids, labels, and
 * last-four masks.
 */
class AIAudit
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function log(int $employeeId, string $action, ?string $entityType = null, ?string $entityId = null, array $metadata = []): void
    {
        $json = null;
        if ($metadata !== []) {
            $encoded = json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($encoded === false) {
                $encoded = json_encode(['note' => 'metadata could not be encoded']);
            }
            $json = $encoded === false ? null : $encoded;
        }

        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        if (strlen($ip) > 45) {
            $ip = substr($ip, 0, 45);
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO ld_ai_audit_log
                (employee_id, action, entity_type, entity_id, metadata_json, ip_address)
             VALUES (:employee_id, :action, :entity_type, :entity_id, :metadata_json, :ip_address)'
        );
        $stmt->execute([
            ':employee_id' => $employeeId > 0 ? $employeeId : null,
            ':action' => substr($action, 0, 80),
            ':entity_type' => $entityType !== null ? substr($entityType, 0, 50) : null,
            ':entity_id' => $entityId !== null ? substr($entityId, 0, 64) : null,
            ':metadata_json' => $json,
            ':ip_address' => $ip !== '' ? $ip : null,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function recent(int $limit = 25): array
    {
        $limit = max(1, min(100, $limit));
        $stmt = $this->pdo->query(
            "SELECT a.id, a.employee_id, a.action, a.entity_type, a.entity_id,
                    a.metadata_json, a.ip_address, a.created_at,
                    CONCAT(e.first_name, ' ', e.last_name) AS employee_name
               FROM ld_ai_audit_log a
               LEFT JOIN em_employees e ON e.employee_id = a.employee_id
              ORDER BY a.id DESC
              LIMIT $limit"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
