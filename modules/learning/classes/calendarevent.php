<?php

include_once __DIR__ . '/../../../database/db.php';

class CalendarEvent
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
     * Get a specific calendar event by ID
     */
    public function getById(int $id): ?array
    {
        $sql = 'SELECT id, instructor_id, type, reference_id, event_date, event_time, duration_minutes, status, created_at, updated_at FROM ld_calendar_event WHERE id = :id LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        return $event ?: null;
    }

    /**
     * Get all calendar events for an instructor
     */
    public function getByInstructor(int $instructorId): array
    {
        $sql = 'SELECT id, type, reference_id, event_date, event_time, duration_minutes, status, created_at FROM ld_calendar_event WHERE instructor_id = :instructor_id AND status = :status ORDER BY event_date ASC, event_time ASC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':instructor_id' => $instructorId,
            ':status' => 'active',
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get upcoming calendar events for an instructor (within next 30 days)
     */
    public function getUpcoming(int $instructorId, int $days = 30): array
    {
        $sql = 'SELECT id, type, reference_id, event_date, event_time, duration_minutes FROM ld_calendar_event WHERE instructor_id = :instructor_id AND status = :status AND event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY) ORDER BY event_date ASC, event_time ASC';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':instructor_id', $instructorId, PDO::PARAM_INT);
        $stmt->bindValue(':status', 'active');
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get calendar events by type
     */
    public function getByType(int $instructorId, string $type): array
    {
        $sql = 'SELECT id, reference_id, event_date, event_time, duration_minutes, status FROM ld_calendar_event WHERE instructor_id = :instructor_id AND type = :type AND status = :status ORDER BY event_date DESC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':instructor_id' => $instructorId,
            ':type' => $type,
            ':status' => 'active',
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create a calendar event
     */
    public function create(array $input): array
    {
        $instructorId = (int) ($input['instructor_id'] ?? 0);
        $type = trim((string) ($input['type'] ?? ''));
        $referenceId = (int) ($input['reference_id'] ?? 0);
        $eventDate = $input['event_date'] ?? null;
        $eventTime = $input['event_time'] ?? null;
        $durationMinutes = isset($input['duration_minutes']) ? (int) $input['duration_minutes'] : null;

        if ($instructorId <= 0) {
            return ['success' => false, 'message' => 'Instructor ID is required.'];
        }

        if (!in_array($type, ['program', 'training', 'video-conference'], true)) {
            return ['success' => false, 'message' => 'Invalid event type.'];
        }

        if ($referenceId <= 0) {
            return ['success' => false, 'message' => 'Reference ID is required.'];
        }

        if (!$eventDate) {
            return ['success' => false, 'message' => 'Event date is required.'];
        }

        $stmt = $this->conn->prepare('INSERT INTO ld_calendar_event (instructor_id, type, reference_id, event_date, event_time, duration_minutes, status) VALUES (:instructor_id, :type, :reference_id, :event_date, :event_time, :duration_minutes, :status)');
        $stmt->execute([
            ':instructor_id' => $instructorId,
            ':type' => $type,
            ':reference_id' => $referenceId,
            ':event_date' => $eventDate,
            ':event_time' => $eventTime,
            ':duration_minutes' => $durationMinutes,
            ':status' => 'active',
        ]);

        return [
            'success' => true,
            'id' => (int) $this->conn->lastInsertId(),
            'message' => 'Calendar event created successfully.',
        ];
    }

    /**
     * Update a calendar event
     */
    public function update(array $input): array
    {
        $id = (int) ($input['id'] ?? 0);
        $eventDate = $input['event_date'] ?? null;
        $eventTime = $input['event_time'] ?? null;
        $durationMinutes = isset($input['duration_minutes']) ? (int) $input['duration_minutes'] : null;

        if ($id <= 0) {
            return ['success' => false, 'message' => 'Calendar event ID is required.'];
        }

        if (!$eventDate) {
            return ['success' => false, 'message' => 'Event date is required.'];
        }

        $stmt = $this->conn->prepare('UPDATE ld_calendar_event SET event_date = :event_date, event_time = :event_time, duration_minutes = :duration_minutes, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':event_date' => $eventDate,
            ':event_time' => $eventTime,
            ':duration_minutes' => $durationMinutes,
            ':id' => $id,
        ]);

        return ['success' => true, 'id' => $id, 'message' => 'Calendar event updated successfully'];
    }

    /**
     * Archive a calendar event
     */
    public function archive(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Calendar event ID is required.'];
        }

        $stmt = $this->conn->prepare("UPDATE ld_calendar_event SET status = 'archived', updated_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $id]);

        return ['success' => true, 'id' => $id, 'message' => 'Calendar event archived successfully'];
    }

    /**
     * Delete a calendar event permanently
     */
    public function delete(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Calendar event ID is required.'];
        }

        $stmt = $this->conn->prepare('DELETE FROM ld_calendar_event WHERE id = :id');
        $stmt->execute([':id' => $id]);

        return ['success' => true, 'id' => $id, 'message' => 'Calendar event deleted successfully'];
    }
}
