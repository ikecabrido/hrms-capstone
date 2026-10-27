<?php

class CourseTemplateSnapshot
{
    public static function fromCourse(PDO $pdo, int $courseId): ?array
    {
        $courseStmt = $pdo->prepare('SELECT id, title, description, category FROM ld_course WHERE id = :id LIMIT 1');
        $courseStmt->execute([':id' => $courseId]);
        $course = $courseStmt->fetch(PDO::FETCH_ASSOC);
        if (!$course) {
            return null;
        }

        $moduleStmt = $pdo->prepare('SELECT id, title, description, order_index, status FROM ld_module WHERE course_id = :course_id ORDER BY order_index ASC');
        $moduleStmt->execute([':course_id' => $courseId]);
        $modules = $moduleStmt->fetchAll(PDO::FETCH_ASSOC);
        $structure = ['modules' => []];
        $lessonCount = 0;

        foreach ($modules as $module) {
            $lessonStmt = $pdo->prepare('SELECT id, title, content_type, content_body, video_url, order_index, status FROM ld_lesson WHERE module_id = :module_id ORDER BY order_index ASC');
            $lessonStmt->execute([':module_id' => (int) $module['id']]);
            $lessons = $lessonStmt->fetchAll(PDO::FETCH_ASSOC);
            $module['lessons'] = [];

            foreach ($lessons as $lesson) {
                $fileStmt = $pdo->prepare('SELECT file_path, title FROM ld_lesson_file WHERE lesson_id = :lesson_id');
                $fileStmt->execute([':lesson_id' => (int) $lesson['id']]);
                $lesson['files'] = $fileStmt->fetchAll(PDO::FETCH_ASSOC);
                $lesson['quizzes'] = [];
                $module['lessons'][] = $lesson;
                $lessonCount++;
            }

            $structure['modules'][] = $module;
        }

        return [
            'course' => $course,
            'structure' => $structure,
            'module_count' => count($modules),
            'lesson_count' => $lessonCount,
            'quiz_count' => 0,
        ];
    }
}
