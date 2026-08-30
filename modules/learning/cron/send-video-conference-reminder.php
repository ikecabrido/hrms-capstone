<?php
/**
 * Cron: send-video-conference-reminder.php
 * Sends reminders for upcoming video conferences.
 * Runs via OS cron (every 5 min): php C:/xampp/htdocs/itsar/modules/learning/cron/send-video-conference-reminder.php
 *
 * Dual reminder system:
 *   - First reminder:  configurable minutes before (default: 30)
 *   - Second reminder: configurable minutes before (default: 15)
 *
 * Reads settings from ld_setting table, falls back to defaults.
 * Sends in-app notifications to both instructor and enrolled learners.
 */

// ── Configuration ──────────────────────────────────────────────
$BASE_DIR = dirname(__FILE__, 2);  // learning/
$LOG_FILE = dirname(__FILE__) . '/conference-reminder.log';

// ── Helpers ────────────────────────────────────────────────────
function logMsg(string $msg): void {
    global $LOG_FILE;
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    file_put_contents($LOG_FILE, $line, FILE_APPEND | LOCK_EX);
    if (php_sapi_name() === 'cli') {
        echo $msg . PHP_EOL;
    }
}

// ── Connect to DB ──────────────────────────────────────────────
require_once $BASE_DIR . '/database/db.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
} catch (Exception $e) {
    logMsg('ERROR: Database connection failed: ' . $e->getMessage());
    exit(1);
}

logMsg('=== Conference reminder cron started ===');

// ── Read reminder timing from settings ─────────────────────────
function getSetting(PDO $pdo, string $key, int $default): int {
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM ld_setting WHERE setting_key = :key");
        $stmt->execute([':key' => $key]);
        $row = $stmt->fetch();
        return $row ? max(1, (int)$row['setting_value']) : $default;
    } catch (Exception $e) {
        return $default;
    }
}

$firstMinutes  = getSetting($pdo, 'video_conference_reminder_first_minutes', 30);
$secondMinutes = getSetting($pdo, 'video_conference_reminder_second_minutes', 15);

logMsg("Reminder timings: first={$firstMinutes}min, second={$secondMinutes}min");

$now = new DateTime('now', new DateTimeZone('Asia/Manila'));

// ── Find conferences needing first reminder ────────────────────
// Conferences starting in ~$firstMinutes, first_reminder_sent = false
$firstWindow = clone $now;
$firstWindow->modify("+$firstMinutes minutes");
// Allow 5-minute window: between (firstMinutes-5) and (firstMinutes+5)
$firstFrom = clone $now;
$firstFrom->modify('+' . ($firstMinutes - 5) . ' minutes');
$firstTo = clone $now;
$firstTo->modify('+' . ($firstMinutes + 5) . ' minutes');

$stmt = $pdo->prepare("
    SELECT vc.*, c.title AS course_title,
           CONCAT(inst.first_name, ' ', inst.last_name) AS instructor_name
    FROM ld_video_conference vc
    LEFT JOIN ld_course c ON c.id = vc.course_id
    LEFT JOIN em_employees inst ON inst.employee_id = vc.instructor_id
    WHERE vc.status = 'scheduled'
      AND vc.first_reminder_sent = 0
      AND vc.scheduled_at BETWEEN :from1 AND :to1
");
$stmt->execute([
    ':from1' => $firstFrom->format('Y-m-d H:i:s'),
    ':to1'   => $firstTo->format('Y-m-d H:i:s'),
]);
$firstConferences = $stmt->fetchAll();

$firstRemindersSent = 0;

foreach ($firstConferences as $conf) {
    $confId = (int)$conf['id'];
    $confTitle = $conf['title'];
    $courseTitle = $conf['course_title'] ?? 'General';
    $scheduledAt = $conf['scheduled_at'];
    $platform = ucfirst(str_replace('_', ' ', $conf['platform']));
    $meetingLink = $conf['meeting_link'];
    $duration = $conf['duration_minutes'] ?? 60;
    $instructorName = $conf['instructor_name'] ?? 'Instructor';
    $courseId = $conf['course_id'] ? (int)$conf['course_id'] : null;

    $title = "Upcoming: $confTitle (starts in {$firstMinutes} min)";
    $message = "A video conference is starting soon!\n\n"
             . "Conference: $confTitle\n"
             . "Course: $courseTitle\n"
             . "Instructor: $instructorName\n"
             . "Platform: $platform\n"
             . "Scheduled: $scheduledAt\n"
             . "Duration: {$duration} minutes\n"
             . "Link: $meetingLink\n\n"
             . "Please join on time.";

    $notified = 0;

    // Notify instructor
    $instructorId = (int)$conf['instructor_id'];
    if ($instructorId > 0) {
        $stmt2 = $pdo->prepare("
            INSERT INTO ld_notification (user_id, type, title, message, reference_type, reference_id)
            VALUES (:uid, 'conference_reminder', :title, :msg, 'video_conference', :refId)
        ");
        $stmt2->execute([
            ':uid'   => $instructorId,
            ':title' => $title,
            ':msg'   => $message,
            ':refId' => $confId,
        ]);
        $notified++;
    }

    // Notify enrolled learners
    if ($courseId) {
        $stmt3 = $pdo->prepare("
            SELECT DISTINCT e.learner_id
            FROM ld_enrollment e
            WHERE e.course_id = :cid AND e.status IN ('enrolled', 'in_progress')
        ");
        $stmt3->execute([':cid' => $courseId]);
        $learners = $stmt3->fetchAll();

        foreach ($learners as $learner) {
            $learnerId = (int)$learner['learner_id'];
            $stmt4 = $pdo->prepare("
                INSERT INTO ld_notification (user_id, type, title, message, reference_type, reference_id)
                VALUES (:uid, 'conference_reminder', :title, :msg, 'video_conference', :refId)
            ");
            $stmt4->execute([
                ':uid'   => $learnerId,
                ':title' => $title,
                ':msg'   => $message,
                ':refId' => $confId,
            ]);
            $notified++;
        }
    }

    // Mark first reminder as sent
    $stmt5 = $pdo->prepare("UPDATE ld_video_conference SET first_reminder_sent = 1 WHERE id = :id");
    $stmt5->execute([':id' => $confId]);

    logMsg("First reminder: '$confTitle' (ID $confId) — notified $notified users");
    $firstRemindersSent++;
}

// ── Find conferences needing second reminder ───────────────────
// Conferences starting in ~$secondMinutes, second_reminder_sent = false
$secondFrom = clone $now;
$secondFrom->modify('+' . ($secondMinutes - 3) . ' minutes');
$secondTo = clone $now;
$secondTo->modify('+' . ($secondMinutes + 3) . ' minutes');

$stmt = $pdo->prepare("
    SELECT vc.*, c.title AS course_title,
           CONCAT(inst.first_name, ' ', inst.last_name) AS instructor_name
    FROM ld_video_conference vc
    LEFT JOIN ld_course c ON c.id = vc.course_id
    LEFT JOIN em_employees inst ON inst.employee_id = vc.instructor_id
    WHERE vc.status = 'scheduled'
      AND vc.second_reminder_sent = 0
      AND vc.scheduled_at BETWEEN :from2 AND :to2
");
$stmt->execute([
    ':from2' => $secondFrom->format('Y-m-d H:i:s'),
    ':to2'   => $secondTo->format('Y-m-d H:i:s'),
]);
$secondConferences = $stmt->fetchAll();

$secondRemindersSent = 0;

foreach ($secondConferences as $conf) {
    $confId = (int)$conf['id'];
    $confTitle = $conf['title'];
    $courseTitle = $conf['course_title'] ?? 'General';
    $scheduledAt = $conf['scheduled_at'];
    $platform = ucfirst(str_replace('_', ' ', $conf['platform']));
    $meetingLink = $conf['meeting_link'];
    $duration = $conf['duration_minutes'] ?? 60;
    $instructorName = $conf['instructor_name'] ?? 'Instructor';
    $courseId = $conf['course_id'] ? (int)$conf['course_id'] : null;

    $title = "Starting NOW: $confTitle ({$secondMinutes} min)";
    $message = "Your video conference is about to begin!\n\n"
             . "Conference: $confTitle\n"
             . "Course: $courseTitle\n"
             . "Instructor: $instructorName\n"
             . "Platform: $platform\n"
             . "Duration: {$duration} minutes\n"
             . "Link: $meetingLink\n\n"
             . "Join now!";

    $notified = 0;

    // Notify instructor
    $instructorId = (int)$conf['instructor_id'];
    if ($instructorId > 0) {
        $stmt2 = $pdo->prepare("
            INSERT INTO ld_notification (user_id, type, title, message, reference_type, reference_id)
            VALUES (:uid, 'conference_starting', :title, :msg, 'video_conference', :refId)
        ");
        $stmt2->execute([
            ':uid'   => $instructorId,
            ':title' => $title,
            ':msg'   => $message,
            ':refId' => $confId,
        ]);
        $notified++;
    }

    // Notify enrolled learners
    if ($courseId) {
        $stmt3 = $pdo->prepare("
            SELECT DISTINCT e.learner_id
            FROM ld_enrollment e
            WHERE e.course_id = :cid AND e.status IN ('enrolled', 'in_progress')
        ");
        $stmt3->execute([':cid' => $courseId]);
        $learners = $stmt3->fetchAll();

        foreach ($learners as $learner) {
            $learnerId = (int)$learner['learner_id'];
            $stmt4 = $pdo->prepare("
                INSERT INTO ld_notification (user_id, type, title, message, reference_type, reference_id)
                VALUES (:uid, 'conference_starting', :title, :msg, 'video_conference', :refId)
            ");
            $stmt4->execute([
                ':uid'   => $learnerId,
                ':title' => $title,
                ':msg'   => $message,
                ':refId' => $confId,
            ]);
            $notified++;
        }
    }

    // Mark second reminder as sent
    $stmt5 = $pdo->prepare("UPDATE ld_video_conference SET second_reminder_sent = 1 WHERE id = :id");
    $stmt5->execute([':id' => $confId]);

    logMsg("Second reminder: '$confTitle' (ID $confId) — notified $notified users");
    $secondRemindersSent++;
}

// ── Auto-complete past conferences ─────────────────────────────
$completed = $pdo->exec("
    UPDATE ld_video_conference
    SET status = 'completed'
    WHERE status = 'scheduled'
      AND scheduled_at < DATE_SUB(NOW(), INTERVAL 2 HOUR)
");

logMsg("Auto-completed $completed past conferences");

logMsg("=== Done — First reminders: $firstRemindersSent, Second reminders: $secondRemindersSent ===");
