<?php
/**
 * NotificationPreference — one employee's switch per notification type.
 *
 * The store is ld_notification_preference_store, keyed by the same HR employee id
 * everything else in this module writes notifications with
 * (ld_notification_inbox.user_id), so a switch governs exactly the rows the
 * learner sees on their own notification page.
 *
 * Three callers share this: the reader's preferences panel (get/set) and the
 * writers that create notifications (allows). Keeping the default in one place
 * is what makes the switch honest — no saved row means subscribed, and every
 * lookup here fails open, so a preference problem never silently swallows a
 * notification somebody expected.
 */

include_once __DIR__ . '/../../../database/db.php';

final class NotificationPreference
{
    /**
     * Saved preferences for one employee, as type => bool. A type the employee
     * has never touched is absent rather than false: absent means subscribed.
     */
    public static function get(PDO $pdo, int $employeeId): array
    {
        if ($employeeId <= 0) {
            return [];
        }

        $stmt = $pdo->prepare(
            'SELECT notification_type, enabled
             FROM ld_notification_preference_store
             WHERE user_id = :user_id'
        );
        $stmt->execute([':user_id' => $employeeId]);

        $prefs = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $prefs[$row['notification_type']] = (bool) $row['enabled'];
        }

        return $prefs;
    }

    /**
     * Store one switch. The unique key on (user_id, notification_type) lets this
     * be a single upsert, so two saves in the same second cannot collide.
     */
    public static function set(PDO $pdo, int $employeeId, string $type, bool $enabled): array
    {
        if ($employeeId <= 0) {
            return ['success' => false, 'message' => 'User ID is required.'];
        }

        if ($type === '') {
            return ['success' => false, 'message' => 'Notification type is required.'];
        }

        $stmt = $pdo->prepare(
            'INSERT INTO ld_notification_preference_store (user_id, notification_type, enabled)
             VALUES (:user_id, :type, :enabled)
             ON DUPLICATE KEY UPDATE enabled = VALUES(enabled)'
        );
        $stmt->execute([
            ':user_id' => $employeeId,
            ':type' => $type,
            ':enabled' => $enabled ? 1 : 0,
        ]);

        return ['success' => true, 'message' => 'Notification preference updated successfully.'];
    }

    /**
     * Whether this employee wants notifications of this type.
     *
     * No saved row means subscribed — the same default the panel shows when it
     * has no answer from the store. A lookup that fails returns true: a
     * notification is worth more than the preference that would have hidden it.
     */
    public static function allows(PDO $pdo, int $employeeId, string $type): bool
    {
        if ($employeeId <= 0 || $type === '') {
            return true;
        }

        try {
            $stmt = $pdo->prepare(
                'SELECT enabled
                 FROM ld_notification_preference_store
                 WHERE user_id = :user_id AND notification_type = :type
                 LIMIT 1'
            );
            $stmt->execute([':user_id' => $employeeId, ':type' => $type]);
            $enabled = $stmt->fetchColumn();

            return $enabled === false ? true : (bool) $enabled;
        } catch (Throwable $e) {
            error_log('[learning] notification preference lookup failed: ' . $e->getMessage());
            return true;
        }
    }
}
