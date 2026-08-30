<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class CourseModule
{
    private $conn;

    private $moduleTable = 'ld_module';
    private $lessonTable = 'ld_lesson';
    private $lessonFileTable = 'ld_lesson_file';
    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    public function getByCourseId(int $courseId): array
    {
        $sql = "SELECT
                m.id AS module_id,
                m.course_id,
                m.title AS module_title,
                m.description AS module_description,
                m.order_index AS module_order,
                m.status AS module_status,

                l.id AS lesson_id,
                l.title AS lesson_title,
                l.content_type,
                l.content_body,
                l.video_url,
                l.order_index AS lesson_order,
                l.status AS lesson_status,

                lf.id AS file_id,
                lf.file_path,
                lf.title AS file_title,
                lf.uploaded_at

            FROM {$this->moduleTable} m

            LEFT JOIN {$this->lessonTable} l
                ON l.module_id = m.id

            LEFT JOIN {$this->lessonFileTable} lf
                ON lf.lesson_id = l.id

            WHERE m.course_id = :course_id

            ORDER BY
                m.order_index ASC,
                m.id ASC,
                l.order_index ASC,
                l.id ASC,
                lf.id ASC";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue(
            ':course_id',
            $courseId,
            PDO::PARAM_INT
        );

        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $modules = [];

        foreach ($rows as $row) {

            $moduleId = (int) $row['module_id'];

            if (!isset($modules[$moduleId])) {
                $modules[$moduleId] = [
                    'id' => $moduleId,
                    'course_id' => (int) $row['course_id'],
                    'title' => $row['module_title'],
                    'description' => $row['module_description'],
                    'order_index' => (int) $row['module_order'],
                    'status' => $row['module_status'],
                    'lessons' => []
                ];
            }

            if ($row['lesson_id'] !== null) {

                $lessonId = (int) $row['lesson_id'];

                if (!isset($modules[$moduleId]['lessons'][$lessonId])) {
                    $modules[$moduleId]['lessons'][$lessonId] = [
                        'id' => $lessonId,
                        'title' => $row['lesson_title'],
                        'content_type' => $row['content_type'],
                        'content_body' => $row['content_body'],
                        'video_url' => $row['video_url'],
                        'order_index' => (int) $row['lesson_order'],
                        'status' => $row['lesson_status'],
                        'files' => []
                    ];
                }

                if ($row['file_id'] !== null) {

                    $fileId = (int) $row['file_id'];

                    $modules[$moduleId]['lessons'][$lessonId]['files'][$fileId] = [
                        'id' => $fileId,
                        'file_path' => $row['file_path'],
                        'title' => $row['file_title'],
                        'uploaded_at' => $row['uploaded_at']
                    ];
                }
            }
        }

        foreach ($modules as &$module) {
            $module['lessons'] = array_values($module['lessons']);

            foreach ($module['lessons'] as &$lesson) {
                $lesson['files'] = array_values($lesson['files']);
            }
        }

        return array_values($modules);
    }
    public function getNextOrderIndex(int $courseId): int
    {
        $sql = "SELECT COALESCE(MAX(order_index), 0) + 1 AS next_order
            FROM {$this->moduleTable}
            WHERE course_id = :course_id";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':course_id', $courseId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }
    public function create(
        int $courseId,
        string $title,
        ?string $description,
        int $orderIndex
    ): bool {
        $sql = "INSERT INTO {$this->moduleTable}
                (course_id, title, description, order_index, status)
                VALUES
                (:course_id, :title, :description, :order_index, 'active')";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue(':course_id', $courseId, PDO::PARAM_INT);
        $stmt->bindValue(':title', trim($title), PDO::PARAM_STR);
        $stmt->bindValue(
            ':description',
            $description !== null && trim($description) !== '' ? trim($description) : null,
            $description !== null && trim($description) !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
        );
        $stmt->bindValue(':order_index', $orderIndex, PDO::PARAM_INT);

        return $stmt->execute();
    }
    public function getNextLessonOrderIndex(int $moduleId): int
    {
        $sql = "SELECT COALESCE(MAX(order_index), 0) + 1
            FROM {$this->lessonTable}
            WHERE module_id = :module_id";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue(
            ':module_id',
            $moduleId,
            PDO::PARAM_INT
        );

        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }
    public function createLesson(
        int $moduleId,
        string $title,
        string $contentType,
        ?string $contentBody,
        ?string $videoUrl,
        int $orderIndex
    ): bool {
        $sql = "INSERT INTO {$this->lessonTable}
            (
                module_id,
                title,
                content_type,
                content_body,
                video_url,
                order_index,
                status
            )
            VALUES
            (
                :module_id,
                :title,
                :content_type,
                :content_body,
                :video_url,
                :order_index,
                'active'
            )";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue(
            ':module_id',
            $moduleId,
            PDO::PARAM_INT
        );

        $stmt->bindValue(
            ':title',
            trim($title),
            PDO::PARAM_STR
        );

        $stmt->bindValue(
            ':content_type',
            $contentType,
            PDO::PARAM_STR
        );

        $stmt->bindValue(
            ':content_body',
            $contentBody !== null && trim($contentBody) !== ''
            ? trim($contentBody)
            : null,
            $contentBody !== null && trim($contentBody) !== ''
            ? PDO::PARAM_STR
            : PDO::PARAM_NULL
        );

        $stmt->bindValue(
            ':video_url',
            $videoUrl !== null && trim($videoUrl) !== ''
            ? trim($videoUrl)
            : null,
            $videoUrl !== null && trim($videoUrl) !== ''
            ? PDO::PARAM_STR
            : PDO::PARAM_NULL
        );

        $stmt->bindValue(
            ':order_index',
            $orderIndex,
            PDO::PARAM_INT
        );

        return $stmt->execute();
    }
    public function getCourseIdByLessonId(int $lessonId): int
    {
        $sql = "SELECT m.course_id
            FROM {$this->lessonTable} l
            INNER JOIN {$this->moduleTable} m
                ON m.id = l.module_id
            WHERE l.id = :lesson_id
            LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':lesson_id', $lessonId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function createFile(
        int $lessonId,
        string $title,
        string $filePath
    ): bool {
        $sql = "INSERT INTO {$this->lessonFileTable}
            (
                lesson_id,
                file_path,
                title
            )
            VALUES
            (
                :lesson_id,
                :file_path,
                :title
            )";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue(':lesson_id', $lessonId, PDO::PARAM_INT);
        $stmt->bindValue(':file_path', $filePath, PDO::PARAM_STR);
        $stmt->bindValue(':title', trim($title), PDO::PARAM_STR);

        return $stmt->execute();
    }
    public function deleteModule(int $moduleId): bool
    {
        try {
            $this->conn->beginTransaction();

            // Delete files belonging to lessons in this module
            $sql = "DELETE lf
                FROM {$this->lessonFileTable} lf
                INNER JOIN {$this->lessonTable} l
                    ON l.id = lf.lesson_id
                WHERE l.module_id = :module_id";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':module_id', $moduleId, PDO::PARAM_INT);
            $stmt->execute();

            // Delete lessons
            $sql = "DELETE FROM {$this->lessonTable}
                WHERE module_id = :module_id";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':module_id', $moduleId, PDO::PARAM_INT);
            $stmt->execute();

            // Delete module
            $sql = "DELETE FROM {$this->moduleTable}
                WHERE id = :module_id";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':module_id', $moduleId, PDO::PARAM_INT);
            $stmt->execute();

            $this->conn->commit();

            return true;

        } catch (\Throwable $e) {

            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            throw $e;
        }
    }
    public function deleteLesson(int $lessonId): bool
    {
        try {
            $this->conn->beginTransaction();

            // Delete files
            $sql = "DELETE FROM {$this->lessonFileTable}
                WHERE lesson_id = :lesson_id";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':lesson_id', $lessonId, PDO::PARAM_INT);
            $stmt->execute();

            // Delete lesson
            $sql = "DELETE FROM {$this->lessonTable}
                WHERE id = :lesson_id";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':lesson_id', $lessonId, PDO::PARAM_INT);
            $stmt->execute();

            $this->conn->commit();

            return true;

        } catch (\Throwable $e) {

            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            throw $e;
        }
    }
    public function deleteFile(int $fileId): bool
    {
        $sql = "DELETE FROM {$this->lessonFileTable}
            WHERE id = :file_id";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue(
            ':file_id',
            $fileId,
            PDO::PARAM_INT
        );

        return $stmt->execute();
    }
}