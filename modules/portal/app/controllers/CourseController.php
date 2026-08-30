<?php

namespace App\Controllers;

use App\Models\Course;
use Exception;

class CourseController
{
    private Course $courseModel;
    public function __construct()
    {
        $this->courseModel = new Course();
    }
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception('Invalid request method.');
        }

        try {

            /* =====================================================
               FORM DATA
            ===================================================== */

            $data = [
                'title' => trim($_POST['title'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'category' => trim($_POST['category'] ?? ''),
                'start_date' => $_POST['start_date'] ?? null,
                'enrollment_deadline' => $_POST['enrollment_deadline'] ?? null,

                // Course owner
                'instructor_id' => (int) ($_POST['instructor_id'] ?? 0),

                // Child tables
                'co_instructors' => $_POST['co_instructors'] ?? [],
                'skills' => $_POST['skills'] ?? [],
            ];


            /* =====================================================
               BASIC VALIDATION
            ===================================================== */

            if ($data['title'] === '') {
                throw new Exception('Course title is required.');
            }

            if ($data['category'] === '') {
                throw new Exception('Course category is required.');
            }

            if ($data['description'] === '') {
                throw new Exception('Course description is required.');
            }

            if ($data['instructor_id'] <= 0) {
                throw new Exception('Please select a course owner.');
            }


            /* =====================================================
               THUMBNAIL UPLOAD
            ===================================================== */

            $thumbnailPath = null;

            if (
                isset($_FILES['thumbnail']) &&
                $_FILES['thumbnail']['error'] !== UPLOAD_ERR_NO_FILE
            ) {

                /* ---------------------------------------------
                   Check upload error
                --------------------------------------------- */

                if ($_FILES['thumbnail']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception(
                        'Failed to upload course thumbnail. Error code: ' .
                        $_FILES['thumbnail']['error']
                    );
                }


                $uploadDirectory =
                    dirname(__DIR__, 2) .
                    '/public/assets/uploads/learning/';


                if (!is_dir($uploadDirectory)) {

                    if (!mkdir($uploadDirectory, 0775, true)) {
                        throw new Exception(
                            'Unable to create thumbnail upload directory: ' .
                            $uploadDirectory
                        );
                    }
                }


                $tmpFile = $_FILES['thumbnail']['tmp_name'];

                if (!is_uploaded_file($tmpFile)) {
                    throw new Exception(
                        'Invalid thumbnail upload.'
                    );
                }



                $mimeType = mime_content_type($tmpFile);

                $allowedMimeTypes = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp'
                ];

                if (!isset($allowedMimeTypes[$mimeType])) {
                    throw new Exception(
                        'Invalid thumbnail. Only JPG, PNG, and WEBP images are allowed.'
                    );
                }


                if ($_FILES['thumbnail']['size'] > 5 * 1024 * 1024) {
                    throw new Exception(
                        'Thumbnail must not exceed 5 MB.'
                    );
                }


                $extension = $allowedMimeTypes[$mimeType];

                $filename =
                    'course_' .
                    date('Ymd_His') .
                    '_' .
                    bin2hex(random_bytes(5)) .
                    '.' .
                    $extension;


                $destination =
                    $uploadDirectory . $filename;


                if (!move_uploaded_file($tmpFile, $destination)) {
                    throw new Exception(
                        'Unable to save course thumbnail to: ' .
                        $destination
                    );
                }

                $thumbnailPath =
                    'assets/uploads/learning/' . $filename;
            }


            $data['thumbnail_path'] = $thumbnailPath;


            $courseId = $this->courseModel->store($data);


            $_SESSION['success'] =
                'Course created successfully.';

            header(
                'Location: index.php?url=admin-learning-index'
            );

            exit;


        } catch (Exception $e) {

            $_SESSION['error'] =
                $e->getMessage();

            header(
                'Location: index.php?url=admin-learning-index'
            );

            exit;
        }
    }
public function update()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    try {

        $courseId = (int) ($_POST['course_id'] ?? 0);

        if ($courseId <= 0) {
            throw new Exception('Invalid course ID.');
        }

        /*
         * =====================================================
         * COURSE OWNER
         * =====================================================
         */

        $instructorId = (int) ($_POST['instructor_id'] ?? 0);

        if ($instructorId <= 0) {
            throw new Exception('Please select a course owner.');
        }


        /*
         * =====================================================
         * CO-INSTRUCTORS
         * =====================================================
         */

        $coInstructors = $_POST['co_instructors'] ?? [];

        if (!is_array($coInstructors)) {
            $coInstructors = [];
        }

        /*
         * Convert to integers
         * Remove empty values
         * Remove duplicates
         * Remove owner
         */
        $coInstructors = array_map(
            'intval',
            $coInstructors
        );

        $coInstructors = array_filter(
            $coInstructors,
            function ($id) use ($instructorId) {
                return $id > 0 && $id !== $instructorId;
            }
        );

        $coInstructors = array_values(
            array_unique($coInstructors)
        );


        /*
         * =====================================================
         * SKILLS
         * =====================================================
         */

        $skills = $_POST['skills'] ?? [];

        if (!is_array($skills)) {
            $skills = [];
        }

        $skills = array_map(
            'intval',
            $skills
        );

        $skills = array_values(
            array_unique(
                array_filter(
                    $skills,
                    fn($id) => $id > 0
                )
            )
        );


        /*
         * =====================================================
         * FORM DATA
         * =====================================================
         */

        $data = [

            'title' =>
                trim($_POST['title'] ?? ''),

            'description' =>
                trim($_POST['description'] ?? ''),

            'category' =>
                trim($_POST['category'] ?? ''),

            'status' =>
                $_POST['status'] ?? 'draft',

            'start_date' =>
                !empty($_POST['start_date'])
                    ? $_POST['start_date']
                    : null,

            'enrollment_deadline' =>
                !empty($_POST['enrollment_deadline'])
                    ? $_POST['enrollment_deadline']
                    : null,

            'instructor_id' =>
                $instructorId,

            'co_instructors' =>
                $coInstructors,

            'skills' =>
                $skills,

            'lessons' =>
                $_POST['lessons'] ?? []
        ];


        /*
         * =====================================================
         * VALIDATION
         * =====================================================
         */

        if ($data['title'] === '') {
            throw new Exception(
                'Course title is required.'
            );
        }

        if ($data['category'] === '') {
            throw new Exception(
                'Course category is required.'
            );
        }

        if ($data['description'] === '') {
            throw new Exception(
                'Course description is required.'
            );
        }

        if (
            !in_array(
                $data['status'],
                ['draft', 'active', 'archived'],
                true
            )
        ) {
            throw new Exception(
                'Invalid course status.'
            );
        }


        /*
         * =====================================================
         * THUMBNAIL
         * =====================================================
         */

        $thumbnailPath = null;

        if (
            isset($_FILES['thumbnail']) &&
            $_FILES['thumbnail']['error'] !== UPLOAD_ERR_NO_FILE
        ) {

            if (
                $_FILES['thumbnail']['error']
                !== UPLOAD_ERR_OK
            ) {
                throw new Exception(
                    'Failed to upload course thumbnail.'
                );
            }

            $uploadDirectory =
                dirname(__DIR__, 2) .
                '/public/assets/uploads/learning/';

            if (!is_dir($uploadDirectory)) {

                if (
                    !mkdir(
                        $uploadDirectory,
                        0775,
                        true
                    )
                ) {
                    throw new Exception(
                        'Unable to create thumbnail upload directory.'
                    );
                }
            }

            $tmpFile =
                $_FILES['thumbnail']['tmp_name'];

            if (!is_uploaded_file($tmpFile)) {
                throw new Exception(
                    'Invalid thumbnail upload.'
                );
            }

            $mimeType =
                mime_content_type($tmpFile);

            $allowedMimeTypes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp'
            ];

            if (
                !isset(
                    $allowedMimeTypes[$mimeType]
                )
            ) {
                throw new Exception(
                    'Invalid thumbnail.'
                );
            }

            if (
                $_FILES['thumbnail']['size']
                > 5 * 1024 * 1024
            ) {
                throw new Exception(
                    'Thumbnail must not exceed 5 MB.'
                );
            }

            $extension =
                $allowedMimeTypes[$mimeType];

            $filename =
                'course_' .
                date('Ymd_His') .
                '_' .
                bin2hex(random_bytes(5)) .
                '.' .
                $extension;

            $destination =
                $uploadDirectory .
                $filename;

            if (
                !move_uploaded_file(
                    $tmpFile,
                    $destination
                )
            ) {
                throw new Exception(
                    'Unable to save course thumbnail.'
                );
            }

            $thumbnailPath =
                'assets/uploads/learning/' .
                $filename;
        }


        /*
         * =====================================================
         * UPDATE
         * =====================================================
         */

        $this->courseModel->update(
            $courseId,
            $data,
            $thumbnailPath
        );


        $_SESSION['success'] =
            'Course updated successfully.';

        header(
            'Location: index.php?url=admin-learning-index'
        );

        exit;

    } catch (Exception $e) {

        $_SESSION['error'] =
            $e->getMessage();

        header(
            'Location: index.php?url=admin-learning-index'
        );

        exit;
    }
}
    public function toggleStatus()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception('Invalid request method.');
        }

        try {

            $courseId = (int) ($_POST['course_id'] ?? 0);

            if ($courseId <= 0) {
                throw new Exception('Invalid course ID.');
            }

            $newStatus = $this->courseModel->toggleStatus($courseId);

            $_SESSION['success'] =
                $newStatus === 'active'
                ? 'Course is now available.'
                : 'Course has been set to draft.';

        } catch (Exception $e) {

            $_SESSION['error'] = $e->getMessage();
        }

        header(
            'Location: index.php?url=admin-learning-index'
        );

        exit;
    }
    public function delete()
    {
        try {
            $courseId = (int) ($_POST['course_id'] ?? 0);

            if ($courseId <= 0) {
                throw new Exception('Invalid course ID.');
            }
            $this->courseModel->deleteCourse($courseId);
            $_SESSION['success'] =
                'Course deleted successfully.';

        } catch (Exception $e) {
            $_SESSION['error'] =
                $e->getMessage();
        }
        header(
            'Location: index.php?url=admin-learning-index'
        );

        exit;
    }
}