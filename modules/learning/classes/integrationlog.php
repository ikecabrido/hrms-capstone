<?php
/**
 * IntegrationLog — Shared logging and idempotency for cross-module API endpoints.
 * Writes to ld_integration_log and ld_integration_event tables.
 */
class IntegrationLog
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Log an API call (both inbound and outbound).
     *
     * @param string $direction   'inbound' or 'outbound'
     * @param string $moduleName  Module name (e.g. 'learning-development')
     * @param string $endpoint    Endpoint name (e.g. 'receive-appraisal-data')
     * @param string $status      'success', 'failed', or 'pending'
     * @param mixed  $payload     The request/response payload (will be json_encoded)
     * @param string|null $errorMessage  Error message if status is 'failed'
     * @return int|null  The inserted log ID, or null on failure
     */
    public function logCall(
        string $direction,
        string $moduleName,
        string $endpoint,
        string $status,
        $payload = null,
        ?string $errorMessage = null
    ): ?int {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO ld_integration_log (direction, module_name, endpoint, status, payload, error_message)
                 VALUES (:direction, :module, :endpoint, :status, :payload, :error)"
            );
            $stmt->execute([
                ':direction' => $direction,
                ':module'    => $moduleName,
                ':endpoint'  => $endpoint,
                ':status'    => $status,
                ':payload'   => is_string($payload) ? $payload : json_encode($payload),
                ':error'     => $errorMessage,
            ]);
            return (int)$this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("IntegrationLog::logCall failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if an inbound event has already been processed (idempotency).
     *
     * @param string $moduleName          Module that sent the event
     * @param string $externalReferenceId Unique ID from the sender
     * @return bool true if already processed
     */
    public function isDuplicate(string $moduleName, string $externalReferenceId): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT id FROM ld_integration_event
                 WHERE module_name = :module AND external_reference_id = :refId
                 LIMIT 1"
            );
            $stmt->execute([':module' => $moduleName, ':refId' => $externalReferenceId]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log("IntegrationLog::isDuplicate failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark an inbound event as processed (for idempotency).
     *
     * @param string $moduleName          Module that sent the event
     * @param string $externalReferenceId Unique ID from the sender
     * @param string $eventType           Type of event (e.g. 'appraisal_recommendation')
     * @return bool true on success
     */
    public function markProcessed(string $moduleName, string $externalReferenceId, string $eventType): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT IGNORE INTO ld_integration_event (module_name, external_reference_id, event_type)
                 VALUES (:module, :refId, :type)"
            );
            $stmt->execute([
                ':module'  => $moduleName,
                ':refId'   => $externalReferenceId,
                ':type'    => $eventType,
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("IntegrationLog::markProcessed failed: " . $e->getMessage());
            return false;
        }
    }
}
