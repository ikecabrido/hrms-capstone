<?php
/**
 * Certificate Expiry Checker
 * Generates notifications for certificates expiring within warning thresholds.
 * Safe to call on every page load — only creates notifications once per threshold per certificate.
 */
require_once __DIR__ . '/notificationpreference.php';

function checkCertificateExpiryNotifications($pdo, $learnerId) {
    if ($learnerId <= 0) return;

    // A learner who switched certificate notices off asked for none of them, so
    // stop here rather than generating rows they would never see.
    if (!NotificationPreference::allows($pdo, (int) $learnerId, 'certificate_expiry')) {
        return;
    }

    $thresholds = [
        30 => 'Your certificate for "%s" expires in %d days (%s). Renew or retake the course to maintain your credential.',
        7  => 'Your certificate for "%s" expires in %d days (%s). Take action soon to keep your certificate valid.',
        1  => 'Your certificate for "%s" expires tomorrow (%s). This is your last day to act before it expires.',
        0  => 'Your certificate for "%s" has expired today (%s). Retake the course to earn a new certificate.',
    ];

    // Days remaining and the printed date are worked out by MySQL: PHP runs a
    // different timezone than the database on this host, which shifted both the
    // day count and the "expires on" date in the generated message.
    $stmt = $pdo->prepare("
        SELECT c.id, c.valid_until, c.verification_code, co.title AS course_title,
               DATEDIFF(c.valid_until, CURDATE()) AS days_left,
               DATE_FORMAT(c.valid_until, '%M %e, %Y') AS valid_until_label
        FROM ld_certificate c
        JOIN ld_course co ON co.id = c.course_id
        WHERE c.learner_id = :lid AND c.status = 'active' AND c.valid_until IS NOT NULL AND c.valid_until >= CURDATE()
    ");
    $stmt->execute([':lid' => $learnerId]);
    $certs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($certs as $cert) {
        $daysLeft = (int) $cert['days_left'];

        foreach ($thresholds as $threshold => $template) {
            if ($daysLeft > $threshold) continue;
            // For threshold 0 (expired), only trigger if exactly 0 days left
            if ($threshold === 0 && $daysLeft !== 0) continue;

            $type = 'certificate_expiry';
            $title = $daysLeft === 0
                ? 'Certificate Expired: ' . $cert['course_title']
                : 'Certificate Expiring: ' . $cert['course_title'];
            $message = sprintf(
                $template,
                $cert['course_title'],
                $daysLeft,
                $cert['valid_until_label']
            );
            $refType = 'certificate';
            $refId = (int)$cert['id'];

            // Check if we already generated this notification (by type + reference_id + threshold bucket)
            $bucket = $daysLeft <= 1 ? $daysLeft : ($daysLeft <= 7 ? 7 : 30);
            $checkStmt = $pdo->prepare("
                SELECT COUNT(*) FROM ld_notification_inbox
                WHERE user_id = :uid AND type = :type AND reference_type = :rtype AND reference_id = :rid
                AND title LIKE :pattern
            ");
            $checkStmt->execute([
                ':uid' => $learnerId,
                ':type' => $type,
                ':rtype' => $refType,
                ':rid' => $refId,
                ':pattern' => '%' . $cert['course_title'] . '%'
            ]);

            if ((int)$checkStmt->fetchColumn() === 0) {
                $insStmt = $pdo->prepare("
                    INSERT INTO ld_notification_inbox (user_id, type, title, message, reference_type, reference_id, is_read, created_at)
                    VALUES (:uid, :type, :title, :message, :rtype, :rid, 0, NOW())
                ");
                $insStmt->execute([
                    ':uid' => $learnerId,
                    ':type' => $type,
                    ':title' => $title,
                    ':message' => $message,
                    ':rtype' => $refType,
                    ':rid' => $refId
                ]);
            }
        }
    }
}
