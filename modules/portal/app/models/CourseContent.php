<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class CourseContent
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
    public function all()
    {
        $moduleSql = "
        SELECT
            id,
            course_id,
            title,
            description,
            order_index,
            status,
            created_at,
            updated_at
        FROM {$this->moduleTable}
        ORDER BY course_id ASC, order_index ASC, id ASC
    ";

        $moduleStmt = $this->conn->prepare($moduleSql);
        $moduleStmt->execute();

        // THIS WAS MISSING
        $modules = $moduleStmt->fetchAll(PDO::FETCH_ASSOC);

        $lessonSql = "
        SELECT
            id,
            module_id,
            title,
            content_type,
            content_body,
            video_url,
            order_index,
            status,
            created_at,
            updated_at
        FROM {$this->lessonTable}
        ORDER BY module_id ASC, order_index ASC, id ASC
    ";

        $lessonStmt = $this->conn->prepare($lessonSql);
        $lessonStmt->execute();

        $lessons = $lessonStmt->fetchAll(PDO::FETCH_ASSOC);

        $fileSql = "
        SELECT
            id,
            lesson_id,
            file_path,
            title,
            uploaded_at
        FROM {$this->lessonFileTable}
        ORDER BY lesson_id ASC, id ASC
    ";

        $fileStmt = $this->conn->prepare($fileSql);
        $fileStmt->execute();

        $files = $fileStmt->fetchAll(PDO::FETCH_ASSOC);

        $filesByLesson = [];

        foreach ($files as $file) {

            $lessonId = (int) $file['lesson_id'];

            if (!isset($filesByLesson[$lessonId])) {
                $filesByLesson[$lessonId] = [];
            }

            $filesByLesson[$lessonId][] = $file;
        }

        $lessonsByModule = [];

        foreach ($lessons as $lesson) {

            $moduleId = (int) $lesson['module_id'];
            $lessonId = (int) $lesson['id'];

            // Attach files to lesson
            $lesson['files'] = $filesByLesson[$lessonId] ?? [];

            if (!isset($lessonsByModule[$moduleId])) {
                $lessonsByModule[$moduleId] = [];
            }

            $lessonsByModule[$moduleId][] = $lesson;
        }


        foreach ($modules as &$module) {

            $moduleId = (int) $module['id'];

            $module['lessons'] =
                $lessonsByModule[$moduleId] ?? [];
        }

        unset($module);


        return $modules;
    }
}