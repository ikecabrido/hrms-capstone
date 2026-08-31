<?php

namespace App\Controllers;

use App\Models\CourseEnrolled;
use App\Models\Employee;
use Exception;

class CourseEnrollmentController
{
    private Employee $employeeModel;

    private CourseEnrolled $courseEnrolledModel;

    public function __construct()
    {
        $this->employeeModel = new Employee();

        $this->courseEnrolledModel = new CourseEnrolled();
    }

    public function index()
    {
        try {

            $userId = $_SESSION['user_id'] ?? null;

            if (!$userId) {
                throw new Exception(
                    'User session not found.'
                );
            }

            $employee = $this->employeeModel->getByUserId(
                $userId
            );

            if (!$employee) {
                throw new Exception(
                    'Employee profile not found.'
                );
            }

            $employeeId = (int) $employee['employee_id'];

            /*
             * Get the employee's active enrollment.
             */
            $enrollment = $this->courseEnrolledModel
                ->getActiveEnrollment(
                    $employeeId
                );

            if (!$enrollment) {

                header(
                    'Location: index.php?url=training'
                );

                exit;
            }

            $enrollmentId = (int) (
                $enrollment['enrollment_id'] ?? 0
            );

            $courseId = (int) (
                $enrollment['course_id'] ?? 0
            );

            if (!$enrollmentId || !$courseId) {

                throw new Exception(
                    'Invalid enrollment or course.'
                );
            }

            /*
             * Get modules and lessons for this
             * exact course + exact enrollment.
             */
            $modules = $this->courseEnrolledModel
                ->getCourseModules(
                    $courseId,
                    $enrollmentId
                );

            /*
             * Calculate progress from the actual
             * modules and lessons returned above.
             */
            $totalModules = count($modules);

            $completedModules = 0;

            $totalLessons = 0;

            $completedLessons = 0;

            foreach ($modules as $module) {

                $moduleCompleted = (
                    ($module['progress_status'] ?? '') === 'completed'
                );

                if ($moduleCompleted) {

                    $completedModules++;
                }

                foreach (
                    ($module['lessons'] ?? [])
                    as $lesson
                ) {

                    $totalLessons++;

                    if (
                        ($lesson['progress_status'] ?? '') === 'completed'
                    ) {

                        $completedLessons++;
                    }
                }
            }

            /*
             * Course progress is based on modules.
             */
            $courseProgress = $totalModules > 0
                ? (int) round(
                    (
                        $completedModules /
                        $totalModules
                    ) * 100
                )
                : 0;

            $courseProgress = max(
                0,
                min(100, $courseProgress)
            );

            $remainingModules = max(
                0,
                $totalModules - $completedModules
            );

            $remainingLessons = max(
                0,
                $totalLessons - $completedLessons
            );

            /*
             * Build one consistent data structure.
             */
            $enrollmentData = [

                'enrollment' => [

                    ...$enrollment,

                    'progress' => $courseProgress
                ],

                'course' => [

                    'id' => $courseId,

                    'title' => $enrollment['title'] ?? '',

                    'description' =>
                        $enrollment['description'] ?? '',

                    'thumbnail_path' =>
                        $enrollment['thumbnail_path'] ?? '',

                    'category' =>
                        $enrollment['category'] ?? '',

                    'status' =>
                        $enrollment['course_status'] ?? '',

                    'start_date' =>
                        $enrollment['start_date'] ?? null,

                    'enrollment_deadline' =>
                        $enrollment['enrollment_deadline'] ?? null
                ],

                'modules' => $modules,

                'progress' => [

                    'courseProgress' =>
                        $courseProgress,

                    'totalModules' =>
                        $totalModules,

                    'completedModules' =>
                        $completedModules,

                    'remainingModules' =>
                        $remainingModules,

                    'totalLessons' =>
                        $totalLessons,

                    'completedLessons' =>
                        $completedLessons,

                    'remainingLessons' =>
                        $remainingLessons
                ]
            ];

            $title =
                'Training, Learning and Development';

            $content =
                __DIR__ .
                '/../views/employee-portal/training/enrolled/content.php';

            require __DIR__ .
                '/../views/employee-portal/index.php';

        } catch (Exception $e) {

            error_log(
                'Course Enrollment Index Error: ' .
                $e->getMessage()
            );

            $_SESSION['error'] =
                $e->getMessage();

            header(
                'Location: index.php?url=training'
            );

            exit;
        }
    }

    public function enroll()
    {
        try {

            $userId = $_SESSION['user_id'] ?? null;

            if (!$userId) {

                throw new Exception(
                    'User session not found.'
                );
            }

            $employee = $this->employeeModel->getByUserId(
                $userId
            );

            if (!$employee) {

                throw new Exception(
                    'Employee profile not found.'
                );
            }

            $employeeId =
                (int) $employee['employee_id'];

            $courseId =
                $_POST['course_id'] ?? null;

            if (
                !$courseId ||
                !is_numeric($courseId)
            ) {

                $_SESSION['error'] =
                    'Invalid course.';

                header(
                    'Location: index.php?url=training'
                );

                exit;
            }

            $courseId = (int) $courseId;

            /*
             * Prevent multiple active enrollments.
             */
            if (
                $this->courseEnrolledModel
                    ->hasEnrollment(
                        $employeeId
                    )
            ) {

                $_SESSION['error'] =
                    'You already have an active training enrollment.';

                header(
                    'Location: index.php?url=training'
                );

                exit;
            }

            /*
             * Prevent duplicate active enrollment
             * for the same course.
             */
            if (
                $this->courseEnrolledModel
                    ->hasEnrollmentForCourse(
                        $employeeId,
                        $courseId
                    )
            ) {

                $_SESSION['error'] =
                    'You are already enrolled in this course.';

                header(
                    'Location: index.php?url=training'
                );

                exit;
            }

            $success =
                $this->courseEnrolledModel->enroll(
                    $employeeId,
                    $courseId
                );

            if (!$success) {

                throw new Exception(
                    'Unable to enroll in the course.'
                );
            }

            $_SESSION['success'] =
                'You have successfully enrolled in the course.';

            header(
                'Location: index.php?url=is-enroll'
            );

            exit;

        } catch (Exception $e) {

            error_log(
                'Course Enrollment Error: ' .
                $e->getMessage()
            );

            $_SESSION['error'] =
                $e->getMessage();

            header(
                'Location: index.php?url=training'
            );

            exit;
        }
    }

    public function completeModule()
    {
        try {

            $userId =
                $_SESSION['user_id'] ?? null;

            if (!$userId) {

                throw new Exception(
                    'User session not found.'
                );
            }

            $employee =
                $this->employeeModel->getByUserId(
                    $userId
                );

            if (!$employee) {

                throw new Exception(
                    'Employee profile not found.'
                );
            }

            $employeeId =
                (int) $employee['employee_id'];

            $moduleId =
                (int) ($_POST['module_id'] ?? 0);

            $enrollmentId =
                (int) ($_POST['enrollment_id'] ?? 0);

            if (
                !$moduleId ||
                !$enrollmentId
            ) {

                throw new Exception(
                    'Invalid module or enrollment.'
                );
            }

            $enrollment =
                $this->courseEnrolledModel
                    ->getEnrollmentById(
                        $enrollmentId,
                        $employeeId
                    );

            if (!$enrollment) {

                throw new Exception(
                    'Invalid enrollment.'
                );
            }

            $success =
                $this->courseEnrolledModel
                    ->completeModule(
                        $enrollmentId,
                        $moduleId
                    );

            if (!$success) {

                throw new Exception(
                    'Failed to complete module.'
                );
            }

            /*
             * Recalculate progress.
             */
            $progressStats =
                $this->courseEnrolledModel
                    ->getCourseProgressStats(
                        (int) $enrollment['course_id'],
                        $enrollmentId
                    );

            /*
             * Automatically complete enrollment
             * when every module is completed.
             */
            if (
                $progressStats['totalModules'] > 0 &&
                $progressStats['completedModules'] >=
                $progressStats['totalModules']
            ) {

                $this->courseEnrolledModel
                    ->completeEnrollment(
                        $enrollmentId,
                        $employeeId
                    );
            }

            header(
                'Location: index.php?url=is-enroll'
            );

            exit;

        } catch (Exception $e) {

            error_log(
                'Complete Module Error: ' .
                $e->getMessage()
            );

            $_SESSION['error'] =
                $e->getMessage();

            header(
                'Location: index.php?url=is-enroll'
            );

            exit;
        }
    }

    public function completeEnrollment()
    {
        try {

            $userId =
                $_SESSION['user_id'] ?? null;

            if (!$userId) {

                throw new Exception(
                    'User session not found.'
                );
            }

            $employee =
                $this->employeeModel->getByUserId(
                    $userId
                );

            if (!$employee) {

                throw new Exception(
                    'Employee profile not found.'
                );
            }

            $employeeId =
                (int) $employee['employee_id'];

            $enrollmentId =
                (int) ($_POST['enrollment_id'] ?? 0);

            if (!$enrollmentId) {

                throw new Exception(
                    'Invalid enrollment.'
                );
            }

            $enrollment =
                $this->courseEnrolledModel
                    ->getEnrollmentById(
                        $enrollmentId,
                        $employeeId
                    );

            if (!$enrollment) {

                throw new Exception(
                    'Invalid or already completed enrollment.'
                );
            }

            $success =
                $this->courseEnrolledModel
                    ->completeEnrollment(
                        $enrollmentId,
                        $employeeId
                    );

            if (!$success) {

                throw new Exception(
                    'Failed to complete enrollment.'
                );
            }

            $_SESSION['success'] =
                'Training completed successfully. You can now enroll in another training.';

            header(
                'Location: index.php?url=training'
            );

            exit;

        } catch (Exception $e) {

            error_log(
                'Complete Enrollment Error: ' .
                $e->getMessage()
            );

            $_SESSION['error'] =
                $e->getMessage();

            header(
                'Location: index.php?url=is-enroll'
            );

            exit;
        }
    }
}