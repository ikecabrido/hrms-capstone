<?php

$enrollment = $enrollmentData['enrollment'] ?? [];

$course = $enrollmentData['course'] ?? [];

$modules = $enrollmentData['modules'] ?? [];

$progress = $enrollmentData['progress'] ?? [];


/*
|--------------------------------------------------------------------------
| Course Progress
|--------------------------------------------------------------------------
*/

$courseProgress = max(
    0,
    min(
        100,
        (int) ($enrollment['progress'] ?? 0)
    )
);


/*
|--------------------------------------------------------------------------
| Course Completion
|--------------------------------------------------------------------------
|
| The enrollment is considered completed when:
| - enrollment status is completed
| OR
| - calculated course progress reaches 100
|
*/

$enrollmentStatus = strtolower(
    $enrollment['enrollment_status']
    ?? $enrollment['status']
    ?? 'enrolled'
);

$courseIsCompleted =
    $enrollmentStatus === 'completed'
    || $courseProgress >= 100;


/*
|--------------------------------------------------------------------------
| Module / Lesson Statistics
|--------------------------------------------------------------------------
*/

$totalModules = 0;

$completedModules = 0;

$remainingModules = 0;

$totalLessons = 0;

$completedLessons = 0;

$remainingLessons = 0;


foreach ($modules as $module) {

    $totalModules++;

    $moduleCompleted =
        ($module['progress_status'] ?? '') === 'completed';

    if ($moduleCompleted) {
        $completedModules++;
    }


    $moduleLessons = $module['lessons'] ?? [];

    foreach ($moduleLessons as $lesson) {

        $totalLessons++;

        $lessonCompleted =
            ($lesson['progress_status'] ?? '') === 'completed'
            || !empty($lesson['completed']);

        if ($lessonCompleted) {
            $completedLessons++;
        }
    }
}


$remainingModules = max(
    0,
    $totalModules - $completedModules
);


$remainingLessons = max(
    0,
    $totalLessons - $completedLessons
);


/*
|--------------------------------------------------------------------------
| Active Lesson
|--------------------------------------------------------------------------
|
| First look for a lesson explicitly marked as current.
| If none exists, use the first incomplete lesson.
|
*/

$activeLesson = null;


foreach ($modules as $module) {

    foreach (($module['lessons'] ?? []) as $lesson) {

        $isCompleted =
            ($lesson['progress_status'] ?? '') === 'completed'
            || !empty($lesson['completed']);

        $isCurrent =
            !empty($lesson['current']);

        if (
            $activeLesson === null
            && $isCurrent
            && !$isCompleted
        ) {

            $activeLesson = $lesson;

            break 2;
        }
    }
}


/*
|--------------------------------------------------------------------------
| Fallback Active Lesson
|--------------------------------------------------------------------------
*/

if ($activeLesson === null) {

    foreach ($modules as $module) {

        foreach (($module['lessons'] ?? []) as $lesson) {

            $isCompleted =
                ($lesson['progress_status'] ?? '') === 'completed'
                || !empty($lesson['completed']);

            if (!$isCompleted) {

                $activeLesson = $lesson;

                break 2;
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| Enrollment Status Label
|--------------------------------------------------------------------------
*/

$status = strtolower(
    $enrollment['enrollment_status']
    ?? $enrollment['status']
    ?? 'enrolled'
);


$statusLabel = match ($status) {

    'in_progress' => 'In Progress',

    'completed' => 'Completed',

    'invited' => 'Invited',

    default => 'Enrolled'
};


/*
|--------------------------------------------------------------------------
| Debug Information
|--------------------------------------------------------------------------
|
| Remove this block after confirming modules are loading.
|
*/

$moduleCount = count($modules);

?>