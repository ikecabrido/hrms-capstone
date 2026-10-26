<?php

include_once __DIR__ . '/../../../database/db.php';

/**
 * One assessment per program participant.
 *
 * The trainer evaluation page lists every participant even when no assessment
 * row exists yet. Saving an evaluation upserts this table so the page never
 * has to create empty rows up front.
 */
class ProgramAssessment
{
    private PDO $conn;

    public function __construct($pdo = null)
    {
        if ($pdo instanceof PDO) {
            $this->conn = $pdo;
            return;
        }

        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        try {
            $stmt = $this->conn->prepare('SELECT * FROM ld_program_assessment WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Throwable $e) {
            error_log('ProgramAssessment::getById failed: ' . $e->getMessage());
            return null;
        }
    }

    public function getForParticipant(int $programId, int $employeeId): ?array
    {
        if ($programId <= 0 || $employeeId <= 0) {
            return null;
        }

        try {
            $stmt = $this->conn->prepare('SELECT * FROM ld_program_assessment WHERE program_id = :program_id AND employee_id = :employee_id LIMIT 1');
            $stmt->execute([':program_id' => $programId, ':employee_id' => $employeeId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Throwable $e) {
            error_log('ProgramAssessment::getForParticipant failed: ' . $e->getMessage());
            return null;
        }
    }

    public function save(array $input): array
    {
        $programId = (int) ($input['program_id'] ?? 0);
        $employeeId = (int) ($input['employee_id'] ?? 0);
        $trainerId = (int) ($input['trainer_id'] ?? 0);
        $attendance = strtolower(trim((string) ($input['attendance'] ?? 'present')));
        $knowledge = $this->normaliseScore($input['knowledge_score'] ?? null);
        $skills = $this->normaliseScore($input['skills_score'] ?? null);
        $participation = $this->normaliseScore($input['participation_score'] ?? null);
        $result = strtolower(trim((string) ($input['result'] ?? 'pending')));
        $feedback = trim((string) ($input['feedback'] ?? ''));
        $recommendations = trim((string) ($input['recommendations'] ?? ''));
        $status = strtolower(trim((string) ($input['status'] ?? 'completed')));

        if ($programId <= 0 || $employeeId <= 0) {
            return ['success' => false, 'message' => 'Program and trainee are required.'];
        }
        foreach ([
            'knowledge_score' => $input['knowledge_score'] ?? null,
            'skills_score' => $input['skills_score'] ?? null,
            'participation_score' => $input['participation_score'] ?? null,
        ] as $label => $score) {
            if ($score !== null && $score !== '' && (!is_scalar($score) || !preg_match('/^[1-5]$/D', trim((string) $score)))) {
                return ['success' => false, 'message' => ucfirst(str_replace('_', ' ', $label)) . ' must be a whole number from 1 to 5.'];
            }
        }
        if (!in_array($attendance, ['present', 'late', 'absent', 'excused'], true)) {
            return ['success' => false, 'message' => 'Choose a valid attendance status.'];
        }
        if (!in_array($result, ['pending', 'passed', 'failed'], true)) {
            return ['success' => false, 'message' => 'Choose a valid evaluation result.'];
        }
        if (!in_array($status, ['draft', 'completed'], true)) {
            return ['success' => false, 'message' => 'Choose a valid evaluation status.'];
        }

        try {
            $participantStmt = $this->conn->prepare('SELECT 1 FROM ld_program_participant WHERE program_id = :program_id AND employee_id = :employee_id LIMIT 1');
            $participantStmt->execute([':program_id' => $programId, ':employee_id' => $employeeId]);
            if (!$participantStmt->fetchColumn()) {
                return ['success' => false, 'message' => 'This employee is not assigned to the program.'];
            }

            $scores = array_values(array_filter([$knowledge, $skills, $participation], static function ($score) {
                return $score !== null;
            }));
            $overall = $scores ? round(array_sum($scores) / count($scores), 2) : null;

            if ($result === 'pending' && $overall !== null) {
                $result = $overall >= 3.0 ? 'passed' : 'failed';
            }
            if ($result === 'pending' && $status === 'completed') {
                $status = 'draft';
            }

            $stmt = $this->conn->prepare('INSERT INTO ld_program_assessment
                (program_id, employee_id, trainer_id, attendance, knowledge_score, skills_score,
                 participation_score, overall_score, result, feedback, recommendations, status, assessed_at)
                VALUES
                (:program_id, :employee_id, :trainer_id, :attendance, :knowledge_score, :skills_score,
                 :participation_score, :overall_score, :result, :feedback, :recommendations, :status, NOW())
                ON DUPLICATE KEY UPDATE
                    trainer_id = VALUES(trainer_id),
                    attendance = VALUES(attendance),
                    knowledge_score = VALUES(knowledge_score),
                    skills_score = VALUES(skills_score),
                    participation_score = VALUES(participation_score),
                    overall_score = VALUES(overall_score),
                    result = VALUES(result),
                    feedback = VALUES(feedback),
                    recommendations = VALUES(recommendations),
                    status = VALUES(status),
                    assessed_at = NOW(),
                    updated_at = NOW()');

            $stmt->execute([
                ':program_id' => $programId,
                ':employee_id' => $employeeId,
                ':trainer_id' => $trainerId > 0 ? $trainerId : null,
                ':attendance' => $attendance,
                ':knowledge_score' => $knowledge,
                ':skills_score' => $skills,
                ':participation_score' => $participation,
                ':overall_score' => $overall,
                ':result' => $result,
                ':feedback' => $feedback !== '' ? $feedback : null,
                ':recommendations' => $recommendations !== '' ? $recommendations : null,
                ':status' => $status,
            ]);

            $assessment = $this->getForParticipant($programId, $employeeId);

            return [
                'success' => true,
                'message' => 'Evaluation saved.',
                'assessment' => $assessment,
                'overall_score' => $overall,
                'result' => $result,
            ];
        } catch (Throwable $e) {
            error_log('ProgramAssessment::save failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to save the evaluation.', 'error' => $e->getMessage()];
        }
    }

    private function normaliseScore($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (int) $value;
    }
}
