<?php

namespace App\Controllers;

use App\Models\CourseModule;
use App\Models\Course;
use Exception;

class ManageCourseController
{
    private CourseModule $courseModuleModel;
    private Course $courseModel;
    public function __construct()
    {
        $this->courseModuleModel = new CourseModule();
        $this->courseModel = new Course();
    }
    public function index(): void
    {
        $courseId = (int) ($_POST['course_id'] ?? $_GET['course_id'] ?? 0);

        if ($courseId <= 0) {
            throw new Exception('Invalid course ID.');
        }

        $modules = $this->courseModuleModel->getByCourseId($courseId);
        $course = $this->courseModel->find($courseId);

        if (!$course) {
            throw new Exception('Course not found.');
        }

        $title = "Manage Course Modules";
        $content = __DIR__ . '/../views/admin-portal/training/course-module/content.php';

        require __DIR__ . '/../views/admin-portal/index.php';
    }
    public function createModule(): void
    {
        $courseId = (int) ($_POST['course_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($courseId <= 0) {
            throw new Exception('Invalid course ID.');
        }

        if ($title === '') {
            throw new Exception('Module title is required.');
        }

        $orderIndex = $this->courseModuleModel->getNextOrderIndex($courseId);

        $created = $this->courseModuleModel->create(
            $courseId,
            $title,
            $description !== '' ? $description : null,
            $orderIndex
        );

        if (!$created) {
            throw new Exception('Failed to create module.');
        }

        $_SESSION['success'] = 'Module created successfully.';

        header('Location: index.php?url=manage-course-module&course_id=' . $courseId);
        exit;
    }
    public function createLesson(): void
    {
        $courseId = (int) ($_POST['course_id'] ?? 0);
        $moduleId = (int) ($_POST['module_id'] ?? 0);

        $title = trim($_POST['title'] ?? '');
        $contentType = $_POST['content_type'] ?? 'text';
        $contentBody = trim($_POST['content_body'] ?? '');
        $videoUrl = trim($_POST['video_url'] ?? '');

        $orderIndex = $this->courseModuleModel->getNextOrderIndex($moduleId);

        $this->courseModuleModel->createLesson(
            $moduleId,
            $title,
            $contentType,
            $contentBody ?: null,
            $videoUrl ?: null,
            $orderIndex
        );

        $_SESSION['success'] = 'Lesson created successfully.';

        header(
            'Location: index.php?url=manage-course-module&course_id=' . $courseId
        );
        exit;
    }
    public function createFile(): void
    {
        $lessonId = (int) ($_POST['lesson_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');

        $file = $_FILES['file'] ?? null;

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload failed.');
        }

        $uploadDir = __DIR__ . '/../../public/assets/uploads/training/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);

        $filename = uniqid('lesson_', true);

        if ($extension !== '') {
            $filename .= '.' . strtolower($extension);
        }

        $destination = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception('Failed to save uploaded file.');
        }

        // Path stored in database
        $filePath = 'assets/uploads/training/' . $filename;

        $created = $this->courseModuleModel->createFile(
            $lessonId,
            $title,
            $filePath
        );

        if (!$created) {
            if (file_exists($destination)) {
                unlink($destination);
            }

            throw new Exception('Failed to save file information.');
        }

        $_SESSION['success'] = 'File uploaded successfully.';

        $courseId = $this->courseModuleModel->getCourseIdByLessonId($lessonId);

        header(
            'Location: index.php?url=manage-course-module&course_id=' . $courseId
        );
        exit;
    }
    public function deleteModule(): void
    {
        $moduleId = (int) ($_POST['module_id'] ?? 0);
        $courseId = (int) ($_POST['course_id'] ?? 0);

        $this->courseModuleModel->deleteModule($moduleId);

        $_SESSION['success'] = 'Module deleted successfully.';

        header(
            'Location: index.php?url=manage-course-module&course_id=' . $courseId
        );
        exit;
    }

    public function deleteLesson(): void
    {
        $lessonId = (int) ($_POST['lesson_id'] ?? 0);
        $courseId = (int) ($_POST['course_id'] ?? 0);

        $this->courseModuleModel->deleteLesson($lessonId);

        $_SESSION['success'] = 'Lesson deleted successfully.';

        header(
            'Location: index.php?url=manage-course-module&course_id=' . $courseId
        );
        exit;
    }

    public function deleteFile(): void
    {
        $fileId = (int) ($_POST['file_id'] ?? 0);
        $courseId = (int) ($_POST['course_id'] ?? 0);

        $this->courseModuleModel->deleteFile($fileId);

        $_SESSION['success'] = 'File deleted successfully.';

        header(
            'Location: index.php?url=manage-course-module&course_id=' . $courseId
        );
        exit;
    }
}