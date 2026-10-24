<?php

class LdKnowledgeTransferGateway
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getProgress(int $planId, int $successorId): array
    {
        $pathStmt = $this->db->prepare(
            "SELECT lp.id,
                    lp.title,
                    lp.assigned_to,
                    lp.is_public,
                    lp.status,
                    lp.type,
                    CONCAT(s.first_name, ' ', s.last_name) AS successor_name
             FROM ld_learning_path lp
             LEFT JOIN em_employees s ON s.employee_id = lp.assigned_to
             WHERE lp.kt_plan_id = :plan_id
               AND lp.type = 'knowledge_transfer'
             ORDER BY lp.id DESC
             LIMIT 1"
        );
        $pathStmt->execute([':plan_id' => $planId]);
        $path = $pathStmt->fetch(PDO::FETCH_ASSOC);

        if (!$path) {
            return [
                'status' => 'not_requested',
                'progress_percent' => 0,
                'path_id' => null,
                'path_title' => null,
                'course_count' => 0,
                'completed_count' => 0,
                'items' => [],
                'is_public' => 0,
                'successor_name' => null,
            ];
        }

        $itemStmt = $this->db->prepare(
            "SELECT lpi.reference_id,
                    c.title AS course_title,
                    COALESCE(en.status, 'enrolled') AS successor_status
             FROM ld_learning_path_item lpi
             LEFT JOIN ld_course c ON c.id = lpi.reference_id
             LEFT JOIN ld_enrollment en ON en.course_id = lpi.reference_id AND en.learner_id = :successor_id
             WHERE lpi.learning_path_id = :path_id
             ORDER BY lpi.order_index ASC, lpi.id ASC"
        );
        $itemStmt->execute([
            ':successor_id' => $successorId,
            ':path_id' => (int)($path['id'] ?? 0),
        ]);
        $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

        $courseCount = count($items);
        $completedCount = 0;
        foreach ($items as $item) {
            if (strtolower((string)($item['successor_status'] ?? 'enrolled')) === 'completed') {
                $completedCount++;
            }
        }

        $progressPercent = $courseCount > 0 ? (int)round(($completedCount / $courseCount) * 100) : 0;
        $status = strtolower((string)($path['status'] ?? ''));
        if ($status === 'completed' || $progressPercent >= 100) {
            $status = 'completed';
        } elseif ($status === 'active' || $status === 'in_progress' || $progressPercent > 0) {
            $status = 'in_progress';
        } else {
            $status = 'requested';
        }

        return [
            'status' => $status,
            'progress_percent' => $progressPercent,
            'path_id' => (int)($path['id'] ?? 0),
            'path_title' => $path['title'] ?? 'Knowledge Transfer Path',
            'course_count' => $courseCount,
            'completed_count' => $completedCount,
            'items' => $items,
            'is_public' => (int)($path['is_public'] ?? 0),
            'successor_name' => $path['successor_name'] ?? null,
        ];
    }
}
