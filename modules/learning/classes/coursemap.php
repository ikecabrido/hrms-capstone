<?php
/**
 * CourseMap — CRUD for recommendation↔course and recognition↔course mappings.
 * These tables translate external module values (development_area, recognition_category)
 * into Learning's own course IDs, so inbound endpoints can resolve courses without
 * needing the sender to know Learning's internal IDs.
 */
include_once __DIR__ . '/../../../database/db.php';

class CourseMap
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

    // ── Recommendation Course Map ──────────────────────────────────────────

    public function getRecommendationMaps(): array
    {
        $stmt = $this->conn->query("
            SELECT rcm.id, rcm.development_area, rcm.course_id, c.title AS course_title, rcm.created_at
            FROM ld_recommendation_course_map rcm
            JOIN ld_course c ON c.id = rcm.course_id
            ORDER BY rcm.development_area ASC
        ");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function getRecommendationMapById(int $id): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT rcm.id, rcm.development_area, rcm.course_id, c.title AS course_title, rcm.created_at
            FROM ld_recommendation_course_map rcm
            JOIN ld_course c ON c.id = rcm.course_id
            WHERE rcm.id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function addRecommendationMap(string $developmentArea, int $courseId): array
    {
        $developmentArea = trim($developmentArea);
        if ($developmentArea === '') {
            return ['success' => false, 'message' => 'Development area is required.'];
        }
        if ($courseId <= 0) {
            return ['success' => false, 'message' => 'Course is required.'];
        }

        // Check duplicate
        $stmt = $this->conn->prepare("SELECT id FROM ld_recommendation_course_map WHERE development_area = :da AND course_id = :cid LIMIT 1");
        $stmt->execute([':da' => $developmentArea, ':cid' => $courseId]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'This mapping already exists.'];
        }

        $stmt = $this->conn->prepare("INSERT INTO ld_recommendation_course_map (development_area, course_id) VALUES (:da, :cid)");
        $stmt->execute([':da' => $developmentArea, ':cid' => $courseId]);

        return [
            'success' => true,
            'message' => 'Mapping added successfully.',
            'id' => (int) $this->conn->lastInsertId(),
        ];
    }

    public function editRecommendationMap(int $id, string $developmentArea, int $courseId): array
    {
        $developmentArea = trim($developmentArea);
        if ($developmentArea === '') {
            return ['success' => false, 'message' => 'Development area is required.'];
        }
        if ($courseId <= 0) {
            return ['success' => false, 'message' => 'Course is required.'];
        }

        $stmt = $this->conn->prepare("UPDATE ld_recommendation_course_map SET development_area = :da, course_id = :cid, updated_at = NOW() WHERE id = :id");
        $stmt->execute([':da' => $developmentArea, ':cid' => $courseId, ':id' => $id]);

        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'Mapping not found.'];
        }

        return ['success' => true, 'message' => 'Mapping updated successfully.'];
    }

    public function deleteRecommendationMap(int $id): array
    {
        $stmt = $this->conn->prepare("DELETE FROM ld_recommendation_course_map WHERE id = :id");
        $stmt->execute([':id' => $id]);

        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'Mapping not found.'];
        }

        return ['success' => true, 'message' => 'Mapping deleted successfully.'];
    }

    /**
     * Look up which course(s) to enroll an employee into for a given development area.
     * Used by receive-appraisal-data.php at runtime.
     */
    public function getCoursesForDevelopmentArea(string $developmentArea): array
    {
        $stmt = $this->conn->prepare("
            SELECT rcm.course_id, c.title AS course_title
            FROM ld_recommendation_course_map rcm
            JOIN ld_course c ON c.id = rcm.course_id AND c.status = 'active'
            WHERE rcm.development_area = :da
        ");
        $stmt->execute([':da' => trim($developmentArea)]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── Recognition Course Map ─────────────────────────────────────────────

    public function getRecognitionMaps(): array
    {
        $stmt = $this->conn->query("
            SELECT rcm.id, rcm.recognition_category, rcm.course_id, c.title AS course_title, rcm.created_at
            FROM ld_recognition_course_map rcm
            JOIN ld_course c ON c.id = rcm.course_id
            ORDER BY rcm.recognition_category ASC
        ");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function getRecognitionMapById(int $id): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT rcm.id, rcm.recognition_category, rcm.course_id, c.title AS course_title, rcm.created_at
            FROM ld_recognition_course_map rcm
            JOIN ld_course c ON c.id = rcm.course_id
            WHERE rcm.id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function addRecognitionMap(string $recognitionCategory, int $courseId): array
    {
        $recognitionCategory = trim($recognitionCategory);
        if ($recognitionCategory === '') {
            return ['success' => false, 'message' => 'Recognition category is required.'];
        }
        if ($courseId <= 0) {
            return ['success' => false, 'message' => 'Course is required.'];
        }

        $stmt = $this->conn->prepare("SELECT id FROM ld_recognition_course_map WHERE recognition_category = :rc AND course_id = :cid LIMIT 1");
        $stmt->execute([':rc' => $recognitionCategory, ':cid' => $courseId]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'This mapping already exists.'];
        }

        $stmt = $this->conn->prepare("INSERT INTO ld_recognition_course_map (recognition_category, course_id) VALUES (:rc, :cid)");
        $stmt->execute([':rc' => $recognitionCategory, ':cid' => $courseId]);

        return [
            'success' => true,
            'message' => 'Mapping added successfully.',
            'id' => (int) $this->conn->lastInsertId(),
        ];
    }

    public function editRecognitionMap(int $id, string $recognitionCategory, int $courseId): array
    {
        $recognitionCategory = trim($recognitionCategory);
        if ($recognitionCategory === '') {
            return ['success' => false, 'message' => 'Recognition category is required.'];
        }
        if ($courseId <= 0) {
            return ['success' => false, 'message' => 'Course is required.'];
        }

        $stmt = $this->conn->prepare("UPDATE ld_recognition_course_map SET recognition_category = :rc, course_id = :cid, updated_at = NOW() WHERE id = :id");
        $stmt->execute([':rc' => $recognitionCategory, ':cid' => $courseId, ':id' => $id]);

        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'Mapping not found.'];
        }

        return ['success' => true, 'message' => 'Mapping updated successfully.'];
    }

    public function deleteRecognitionMap(int $id): array
    {
        $stmt = $this->conn->prepare("DELETE FROM ld_recognition_course_map WHERE id = :id");
        $stmt->execute([':id' => $id]);

        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'Mapping not found.'];
        }

        return ['success' => true, 'message' => 'Mapping deleted successfully.'];
    }

    /**
     * Look up which course(s) to enroll an employee into for a given recognition category.
     * Used by receive-recognition-eligibility.php at runtime.
     */
    public function getCoursesForRecognitionCategory(string $recognitionCategory): array
    {
        $stmt = $this->conn->prepare("
            SELECT rcm.course_id, c.title AS course_title
            FROM ld_recognition_course_map rcm
            JOIN ld_course c ON c.id = rcm.course_id AND c.status = 'active'
            WHERE rcm.recognition_category = :rc
        ");
        $stmt->execute([':rc' => trim($recognitionCategory)]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
