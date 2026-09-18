<?php

include_once __DIR__ . '/../../../database/db.php';
include_once __DIR__ . '/enrollment.php';
include_once __DIR__ . '/certificate.php';

class Progress
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

/**
 * Marks one valid course item as completed for a given enrollment.
 *
 * Supported item types:
 * - module
 * - lesson
 * - quiz
 * - evaluation
 *
 * The item must actually belong to the course associated with
 * the learner's enrollment before progress can be written.
 *
 * After marking, checks whether the whole course is complete.
 */
public function markComplete(
    int $enrollmentId,
    string $itemType,
    int $referenceId
): array {
    if ($enrollmentId <= 0 || $referenceId <= 0) {
        return [
            'success' => false,
            'message' => 'Enrollment and item reference are required.'
        ];
    }

    if (!in_array($itemType, ['module', 'lesson', 'quiz', 'evaluation'], true)) {
        return [
            'success' => false,
            'message' => 'Invalid item type.'
        ];
    }

    /*
     * ---------------------------------------------------------
     * Get the enrollment and its course.
     * ---------------------------------------------------------
     */
    $enrollmentStmt = $this->conn->prepare(
        'SELECT id, course_id, status
         FROM ld_enrollment
         WHERE id = :enrollment_id
         LIMIT 1'
    );

    $enrollmentStmt->execute([
        ':enrollment_id' => $enrollmentId
    ]);

    $enrollment = $enrollmentStmt->fetch(PDO::FETCH_ASSOC);

    if (!$enrollment) {
        return [
            'success' => false,
            'message' => 'Enrollment not found.'
        ];
    }

    $courseId = (int) $enrollment['course_id'];

    /*
     * ---------------------------------------------------------
     * Verify that the requested item belongs to this course.
     * ---------------------------------------------------------
     */
    $itemExists = false;

    switch ($itemType) {

        case 'module':
            $stmt = $this->conn->prepare(
                'SELECT m.id
                 FROM ld_module m
                 WHERE m.id = :reference_id
                   AND m.course_id = :course_id
                   AND m.status = \'active\'
                 LIMIT 1'
            );

            $stmt->execute([
                ':reference_id' => $referenceId,
                ':course_id' => $courseId
            ]);

            $itemExists = (bool) $stmt->fetchColumn();
            break;

        case 'lesson':
            $stmt = $this->conn->prepare(
                'SELECT l.id
                 FROM ld_lesson l
                 INNER JOIN ld_module m
                    ON m.id = l.module_id
                 WHERE l.id = :reference_id
                   AND m.course_id = :course_id
                   AND l.status = \'active\'
                   AND m.status = \'active\'
                 LIMIT 1'
            );

            $stmt->execute([
                ':reference_id' => $referenceId,
                ':course_id' => $courseId
            ]);

            $itemExists = (bool) $stmt->fetchColumn();
            break;

        case 'quiz':
            $stmt = $this->conn->prepare(
                'SELECT q.id
                 FROM ld_quiz q
                 INNER JOIN ld_module m
                    ON m.id = q.module_id
                 WHERE q.id = :reference_id
                   AND m.course_id = :course_id
                   AND q.status = \'active\'
                   AND m.status = \'active\'
                 LIMIT 1'
            );

            $stmt->execute([
                ':reference_id' => $referenceId,
                ':course_id' => $courseId
            ]);

            $itemExists = (bool) $stmt->fetchColumn();
            break;

case 'evaluation':
    $stmt = $this->conn->prepare(
        'SELECT e.id
         FROM ld_evaluation e
         WHERE e.id = :reference_id
           AND e.course_id = :course_id
           AND e.status = \'active\'
         LIMIT 1'
    );

    $stmt->execute([
        ':reference_id' => $referenceId,
        ':course_id' => $courseId
    ]);

    $itemExists = (bool) $stmt->fetchColumn();
    break;
    }

    /*
     * ---------------------------------------------------------
     * Write or update progress.
     * ---------------------------------------------------------
     */
    $existing = $this->getItem(
        $enrollmentId,
        $itemType,
        $referenceId
    );

    if ($existing) {
        $stmt = $this->conn->prepare(
            "UPDATE ld_progress
             SET status = 'completed',
                 completed_at = NOW()
             WHERE id = :id"
        );

        $stmt->execute([
            ':id' => $existing['id']
        ]);
    } else {
        $stmt = $this->conn->prepare(
            "INSERT INTO ld_progress
                (enrollment_id, item_type, reference_id, status, completed_at)
             VALUES
                (:enrollment_id, :item_type, :reference_id, 'completed', NOW())"
        );

        $stmt->execute([
            ':enrollment_id' => $enrollmentId,
            ':item_type' => $itemType,
            ':reference_id' => $referenceId
        ]);
    }

    /*
     * ---------------------------------------------------------
     * Check whether the entire course is now complete.
     * ---------------------------------------------------------
     */
    $this->maybeCompleteCourse($enrollmentId);

    return [
        'success' => true,
        'message' => 'Marked as complete.',
        'item_type' => $itemType,
        'reference_id' => $referenceId
    ];
}

    public function markInProgress(int $enrollmentId, string $itemType, int $referenceId): array
    {
        if ($enrollmentId <= 0 || $referenceId <= 0) {
            return ['success' => false, 'message' => 'Enrollment and item reference are required.'];
        }

        $existing = $this->getItem($enrollmentId, $itemType, $referenceId);

        if ($existing) {
            if ($existing['status'] === 'completed') {
                // don't downgrade a completed item back to in_progress
                return ['success' => true, 'message' => 'Already completed.'];
            }

            $stmt = $this->conn->prepare("UPDATE ld_progress SET status = 'in_progress' WHERE id = :id");
            $stmt->execute([':id' => $existing['id']]);
        } else {
            $stmt = $this->conn->prepare(
                "INSERT INTO ld_progress (enrollment_id, item_type, reference_id, status)
                 VALUES (:enrollment_id, :item_type, :reference_id, 'in_progress')"
            );
            $stmt->execute([
                ':enrollment_id' => $enrollmentId,
                ':item_type' => $itemType,
                ':reference_id' => $referenceId,
            ]);
        }

        return ['success' => true, 'message' => 'Marked as in progress.'];
    }

    public function getItem(int $enrollmentId, string $itemType, int $referenceId): ?array
    {
        $stmt = $this->conn->prepare(
            'SELECT * FROM ld_progress WHERE enrollment_id = :enrollment_id AND item_type = :item_type AND reference_id = :reference_id LIMIT 1'
        );
        $stmt->execute([
            ':enrollment_id' => $enrollmentId,
            ':item_type' => $itemType,
            ':reference_id' => $referenceId,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * All progress rows for one enrollment — powers the module/lesson checklist UI.
     */
    public function getByEnrollment(int $enrollmentId): array
    {
        $stmt = $this->conn->prepare('SELECT * FROM ld_progress WHERE enrollment_id = :enrollment_id');
        $stmt->execute([':enrollment_id' => $enrollmentId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Canonical definition of "this module is complete".
     *
     * Progress is recorded per Lesson and per Quiz (see markComplete()) — a Module is
     * a container and never gets a ld_progress row of its own. Its completion is
     * therefore *derived*: the module must contain at least one active lesson or quiz,
     * and every one of them must be complete.
     *
     * Every reader that needs module-level completion must use this predicate, so the
     * units counted here stay in step with the units the writer records.
     *
     * $enrollmentExpr is either an integer literal (single-enrollment queries) or a
     * column reference such as "e.id" (correlated subqueries). Do not pass a named
     * placeholder — it would appear twice in the generated SQL.
     */
    public static function sqlModuleCompleted(string $moduleAlias, string $enrollmentExpr): string
    {
        $m = $moduleAlias;
        $e = $enrollmentExpr;

        $total = "(SELECT COUNT(*) FROM ld_lesson l WHERE l.module_id = {$m}.id AND l.status = 'active')"
               . " + (SELECT COUNT(*) FROM ld_quiz q WHERE q.module_id = {$m}.id AND q.status = 'active')";

        $completed = "(SELECT COUNT(*) FROM ld_progress p"
                   . " JOIN ld_lesson l2 ON l2.id = p.reference_id AND l2.module_id = {$m}.id AND l2.status = 'active'"
                   . " WHERE p.item_type = 'lesson' AND p.status = 'completed' AND p.enrollment_id = {$e})"
                   . " + (SELECT COUNT(*) FROM ld_progress p"
                   . " JOIN ld_quiz q2 ON q2.id = p.reference_id AND q2.module_id = {$m}.id AND q2.status = 'active'"
                   . " WHERE p.item_type = 'quiz' AND p.status = 'completed' AND p.enrollment_id = {$e})";

        return "({$total} > 0 AND {$completed} >= {$total})";
    }

    /**
     * Ids of the modules that are complete for one enrollment.
     */
    public function getCompletedModuleIds(int $enrollmentId, int $courseId): array
    {
        $sql = 'SELECT m.id FROM ld_module m'
             . " WHERE m.course_id = :course_id AND m.status = 'active'"
             . ' AND ' . self::sqlModuleCompleted('m', (string) (int) $enrollmentId)
             . ' ORDER BY m.order_index ASC, m.id ASC';

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':course_id' => $courseId]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * Number of modules currently complete for one enrollment.
     */
    public function countCompletedModules(int $enrollmentId, int $courseId): int
    {
        return count($this->getCompletedModuleIds($enrollmentId, $courseId));
    }

    /**
     * Percent complete for one enrollment — counts Lessons + Quizzes as the trackable units.
     * (Modules are containers, not individually "completed" — their completion is implied
     * once all their lessons/quizzes are done.)
     */
    public function getPercentComplete(int $enrollmentId, int $courseId): float
    {
        $total = $this->countTrackableItems($courseId);

        if ($total === 0) {
            return 0.0;
        }

        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) FROM ld_progress
             WHERE enrollment_id = :enrollment_id AND status = 'completed' AND item_type IN ('lesson','quiz')"
        );
        $stmt->execute([':enrollment_id' => $enrollmentId]);
        $completed = (int) $stmt->fetchColumn();

        return round(($completed / $total) * 100, 2);
    }

    /**
     * Used by the Evaluation completion gate (start-evaluation.php) —
     * "has this learner finished every lesson/quiz in the course?"
     */
    public function hasCompletedAllCourseContent(int $enrollmentId, int $courseId): bool
    {
        $total = $this->countTrackableItems($courseId);

        if ($total === 0) {
            return false; // a course with no lessons/quizzes cannot be auto-completed
        }

        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) FROM ld_progress
             WHERE enrollment_id = :enrollment_id AND status = 'completed' AND item_type IN ('lesson','quiz')"
        );
        $stmt->execute([':enrollment_id' => $enrollmentId]);
        $completed = (int) $stmt->fetchColumn();

        return $completed >= $total;
    }

    /**
     * Counts all Lessons + Quizzes across all (active) Modules in a course.
     * This is the denominator for percent-complete and the evaluation gate.
     */
    private function countTrackableItems(int $courseId): int
    {
        $lessonStmt = $this->conn->prepare(
            "SELECT COUNT(*) FROM ld_lesson l
             JOIN ld_module m ON m.id = l.module_id
             WHERE m.course_id = :course_id AND l.status = 'active' AND m.status = 'active'"
        );
        $lessonStmt->execute([':course_id' => $courseId]);
        $lessonCount = (int) $lessonStmt->fetchColumn();

        $quizStmt = $this->conn->prepare(
            "SELECT COUNT(*) FROM ld_quiz q
             JOIN ld_module m ON m.id = q.module_id
             WHERE m.course_id = :course_id AND q.status = 'active' AND m.status = 'active'"
        );
        $quizStmt->execute([':course_id' => $courseId]);
        $quizCount = (int) $quizStmt->fetchColumn();

        return $lessonCount + $quizCount;
    }

    /**
     * If every trackable item in the course is now complete, flip the
     * enrollment itself to 'completed'. Called automatically after markComplete().
     */
    private function maybeCompleteCourse(int $enrollmentId): void
    {
        $enrollmentStmt = $this->conn->prepare('SELECT id, learner_id, course_id, status FROM ld_enrollment WHERE id = :id LIMIT 1');
        $enrollmentStmt->execute([':id' => $enrollmentId]);
        $enrollment = $enrollmentStmt->fetch(PDO::FETCH_ASSOC);

        if (!$enrollment || $enrollment['status'] === 'completed') {
            return;
        }

        $courseId = (int) $enrollment['course_id'];

        $totalItems = $this->countTrackableItems($courseId);
        if ($totalItems > 0 && $this->hasCompletedAllCourseContent($enrollmentId, $courseId)) {
            // Mark enrollment as completed
            $enrollmentClass = new Enrollment($this->conn);
            $result = $enrollmentClass->markCompleted($enrollmentId);

            // Auto-issue certificate after course completion
            if ($result['success']) {
                $this->issueCertificateOnCompletion(
                    (int) $enrollment['learner_id'],
                    $courseId,
                    $enrollmentId
                );
            }
        }
    }

    /**
     * Automatically issues a certificate when a learner completes all
     * trackable items in a course and the enrollment flips to completed.
     */
    private function issueCertificateOnCompletion(int $learnerId, int $courseId, int $enrollmentId): array
    {
        $certClass = new Certificate($this->conn);

        // Check if a certificate already exists for this enrollment
        $existing = $certClass->getByEnrollment($enrollmentId);
        if ($existing) {
            return ['success' => false, 'message' => 'Certificate already issued.'];
        }

        // Look for an active certificate template for this course
        $templateList = $certClass->getTemplateList($courseId);
        $templateId = null;
        if (!empty($templateList)) {
            $templateId = (int) $templateList[0]['id'];
        }

        // Issue the certificate — valid for 1 year by default
        $validUntil = date('Y-m-d H:i:s', strtotime('+1 year'));
        return $certClass->issue($learnerId, $courseId, $enrollmentId, $templateId, $validUntil);
    }
}