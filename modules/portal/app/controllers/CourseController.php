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
                'lessons' => $_POST['lessons'] ?? [],
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


                /* ---------------------------------------------
                   Correct upload directory

                   CourseController.php:
                   modules/portal/app/Controllers/

                   dirname(__DIR__, 2):
                   modules/portal/

                   Final:
                   modules/portal/public/assets/uploads/learning/
                --------------------------------------------- */

                $uploadDirectory =
                    dirname(__DIR__, 2) .
                    '/public/assets/uploads/learning/';


                /* ---------------------------------------------
                   Create directory if it does not exist
                --------------------------------------------- */

                if (!is_dir($uploadDirectory)) {

                    if (!mkdir($uploadDirectory, 0775, true)) {
                        throw new Exception(
                            'Unable to create thumbnail upload directory: ' .
                            $uploadDirectory
                        );
                    }
                }


                /* ---------------------------------------------
                   Validate uploaded file
                --------------------------------------------- */

                $tmpFile = $_FILES['thumbnail']['tmp_name'];

                if (!is_uploaded_file($tmpFile)) {
                    throw new Exception(
                        'Invalid thumbnail upload.'
                    );
                }


                /* ---------------------------------------------
                   Validate MIME type
                --------------------------------------------- */

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


                /* ---------------------------------------------
                   File size — maximum 5 MB
                --------------------------------------------- */

                if ($_FILES['thumbnail']['size'] > 5 * 1024 * 1024) {
                    throw new Exception(
                        'Thumbnail must not exceed 5 MB.'
                    );
                }


                /* ---------------------------------------------
                   Generate unique filename
                --------------------------------------------- */

                $extension = $allowedMimeTypes[$mimeType];

                $filename =
                    'course_' .
                    date('Ymd_His') .
                    '_' .
                    bin2hex(random_bytes(5)) .
                    '.' .
                    $extension;


                /* ---------------------------------------------
                   Final physical file location
                --------------------------------------------- */

                $destination =
                    $uploadDirectory . $filename;


                /* ---------------------------------------------
                   Move uploaded file
                --------------------------------------------- */

                if (!move_uploaded_file($tmpFile, $destination)) {
                    throw new Exception(
                        'Unable to save course thumbnail to: ' .
                        $destination
                    );
                }


                /* ---------------------------------------------
                   Database path

                   DO NOT store:
                   D:\xampp\htdocs\...

                   Store only:
                   assets/uploads/learning/filename.jpg
                --------------------------------------------- */

                $thumbnailPath =
                    'assets/uploads/learning/' . $filename;
            }


            /* =====================================================
               ADD THUMBNAIL PATH TO COURSE DATA
            ===================================================== */

            $data['thumbnail_path'] = $thumbnailPath;


            /* =====================================================
               STORE COURSE + CHILD TABLES
            ===================================================== */

            $courseId = $this->courseModel->store($data);


            /* =====================================================
               SUCCESS
            ===================================================== */

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

            /* =====================================================
               COURSE ID
            ===================================================== */

            $courseId = (int) ($_POST['course_id'] ?? 0);

            if ($courseId <= 0) {
                throw new Exception('Invalid course ID.');
            }


            /* =====================================================
               FORM DATA
            ===================================================== */

            $data = [
                'title' => trim($_POST['title'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'category' => trim($_POST['category'] ?? ''),
                'status' => $_POST['status'] ?? 'draft',

                'start_date' => $_POST['start_date'] ?? null,
                'enrollment_deadline' => $_POST['enrollment_deadline'] ?? null,

                // Course owner
                'instructor_id' => (int) ($_POST['instructor_id'] ?? 0),

                // Child tables
                'co_instructors' => $_POST['co_instructors'] ?? [],
                'skills' => $_POST['skills'] ?? [],
                'lessons' => $_POST['lessons'] ?? [],
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

            if (
                !in_array(
                    $data['status'],
                    ['draft', 'active', 'archived'],
                    true
                )
            ) {
                throw new Exception('Invalid course status.');
            }


            /* =====================================================
               THUMBNAIL
            ===================================================== */

            $thumbnailPath = null;

            if (
                isset($_FILES['thumbnail']) &&
                $_FILES['thumbnail']['error'] !== UPLOAD_ERR_NO_FILE
            ) {

                /* ---------------------------------------------
                   Upload error
                --------------------------------------------- */

                if ($_FILES['thumbnail']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception(
                        'Failed to upload course thumbnail. Error code: ' .
                        $_FILES['thumbnail']['error']
                    );
                }


                /* ---------------------------------------------
                   Upload directory
                --------------------------------------------- */

                $uploadDirectory =
                    dirname(__DIR__, 2) .
                    '/public/assets/uploads/learning/';


                /* ---------------------------------------------
                   Create directory
                --------------------------------------------- */

                if (!is_dir($uploadDirectory)) {

                    if (!mkdir($uploadDirectory, 0775, true)) {
                        throw new Exception(
                            'Unable to create thumbnail upload directory: ' .
                            $uploadDirectory
                        );
                    }
                }


                /* ---------------------------------------------
                   Validate uploaded file
                --------------------------------------------- */

                $tmpFile = $_FILES['thumbnail']['tmp_name'];

                if (!is_uploaded_file($tmpFile)) {
                    throw new Exception(
                        'Invalid thumbnail upload.'
                    );
                }


                /* ---------------------------------------------
                   Validate MIME
                --------------------------------------------- */

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


                /* ---------------------------------------------
                   File size
                --------------------------------------------- */

                if (
                    $_FILES['thumbnail']['size'] >
                    5 * 1024 * 1024
                ) {
                    throw new Exception(
                        'Thumbnail must not exceed 5 MB.'
                    );
                }


                /* ---------------------------------------------
                   Generate filename
                --------------------------------------------- */

                $extension = $allowedMimeTypes[$mimeType];

                $filename =
                    'course_' .
                    date('Ymd_His') .
                    '_' .
                    bin2hex(random_bytes(5)) .
                    '.' .
                    $extension;


                /* ---------------------------------------------
                   Destination
                --------------------------------------------- */

                $destination =
                    $uploadDirectory . $filename;


                /* ---------------------------------------------
                   Move file
                --------------------------------------------- */

                if (
                    !move_uploaded_file(
                        $tmpFile,
                        $destination
                    )
                ) {
                    throw new Exception(
                        'Unable to save course thumbnail to: ' .
                        $destination
                    );
                }


                /* ---------------------------------------------
                   Database path
                --------------------------------------------- */

                $thumbnailPath =
                    'assets/uploads/learning/' . $filename;
            }


            /* =====================================================
               UPDATE COURSE + CHILD TABLES
            ===================================================== */

            $this->courseModel->update(
                $courseId,
                $data,
                $thumbnailPath
            );


            /* =====================================================
               SUCCESS
            ===================================================== */

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