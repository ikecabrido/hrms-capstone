<?php
/**
 * KnowledgeTransfer — the one writer for exit_knowledge_transfer_plans.
 *
 * The handover tables belong to this (Exit) module, but two screens need to write them:
 * the Exit module's own save endpoint, and L&D's per-employee "Create KT plan" action on
 * its User page. Each used to carry its own copy of the same insert — the same columns,
 * the same status whitelist, the same "employee and dates are required" rule — and the
 * copies had drifted: only Exit's told the successor, so a handover raised from L&D
 * notified nobody at all.
 *
 * The rule now lives here once and both callers go through it. A caller that needs to do
 * more on create (L&D generates a learning path from the plan) reads `created` off the
 * result and does that work itself.
 *
 * Not here on purpose: anything about the learning module's own tables. This class only
 * knows the exit side of a handover.
 */
class KnowledgeTransfer
{
    /** Plan statuses exit_knowledge_transfer_plans accepts; anything else becomes active. */
    public const STATUSES = ['active', 'completed', 'cancelled'];

    /**
     * Create or update one transfer plan, and tell whoever has to act on it.
     *
     * $input    id (0/absent = create), employee_id, successor_id, start_date, end_date, status
     * $actorId  the signed-in employee saving it, recorded as created_by on an insert
     *
     * @param array<string, mixed> $input
     * @return array{success:bool,id:int,message:string,created:bool,error:string}
     */
    public static function savePlan(PDO $pdo, array $input, ?int $actorId = null): array
    {
        $id = !empty($input['id']) ? (int) $input['id'] : 0;
        $employeeId = (int) ($input['employee_id'] ?? 0);
        $successorId = !empty($input['successor_id']) ? (int) $input['successor_id'] : null;
        $startDate = trim((string) ($input['start_date'] ?? ''));
        $endDate = trim((string) ($input['end_date'] ?? ''));
        $status = (string) ($input['status'] ?? 'active');

        if ($employeeId <= 0 || $startDate === '' || $endDate === '') {
            return [
                'success' => false,
                'id'      => 0,
                'message' => '',
                'created' => false,
                'error'   => 'Employee, start date, and end date are required.',
            ];
        }

        if (!in_array($status, self::STATUSES, true)) {
            $status = 'active';
        }

        if ($id > 0) {
            $stmt = $pdo->prepare('
                UPDATE exit_knowledge_transfer_plans
                SET employee_id = :employee_id,
                    successor_id = :successor_id,
                    start_date = :start_date,
                    end_date = :end_date,
                    status = :status
                WHERE id = :id
            ');
            $stmt->execute([
                ':employee_id' => $employeeId,
                ':successor_id' => $successorId,
                ':start_date' => $startDate,
                ':end_date' => $endDate,
                ':status' => $status,
                ':id' => $id,
            ]);

            return ['success' => true, 'id' => $id, 'message' => 'Plan updated.', 'created' => false, 'error' => ''];
        }

        $stmt = $pdo->prepare('
            INSERT INTO exit_knowledge_transfer_plans
                (employee_id, successor_id, start_date, end_date, status, created_by)
            VALUES
                (:employee_id, :successor_id, :start_date, :end_date, :status, :created_by)
        ');
        $stmt->execute([
            ':employee_id' => $employeeId,
            ':successor_id' => $successorId,
            ':start_date' => $startDate,
            ':end_date' => $endDate,
            ':status' => $status,
            ':created_by' => $actorId,
        ]);

        $newId = (int) $pdo->lastInsertId();

        self::notifySuccessor($pdo, $newId, $employeeId, $successorId, $startDate, $endDate);

        return ['success' => true, 'id' => $newId, 'message' => 'Plan created.', 'created' => true, 'error' => ''];
    }

    /**
     * Whoever now has work to do gets told: the successor acts on the handover, and with
     * nobody named the plan is still about the exiting employee.
     *
     * A notification is never allowed to fail the save it belongs to.
     */
    private static function notifySuccessor(
        PDO $pdo,
        int $planId,
        int $employeeId,
        ?int $successorId,
        string $startDate,
        string $endDate
    ): void {
        try {
            require_once dirname(__DIR__, 3) . '/includes/Notification.php';

            (new Notification($pdo))->push(
                $successorId ?? $employeeId,
                'knowledge_transfer',
                $successorId
                    ? 'Knowledge transfer plan assigned to you'
                    : 'Knowledge transfer plan created for you',
                sprintf('Handover runs %s to %s.', $startDate, $endDate),
                'knowledge_transfer_plan',
                $planId
            );
        } catch (Throwable $e) {
            error_log('KnowledgeTransfer::notifySuccessor failed: ' . $e->getMessage());
        }
    }
}
