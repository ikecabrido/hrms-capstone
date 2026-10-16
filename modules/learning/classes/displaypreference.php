<?php
/**
 * DisplayPreference — how one employee's lists and card grids open.
 *
 * The store is ld_display_preference_store, keyed by the same HR employee id the
 * rest of the module identifies people with, so the preference follows the
 * person rather than the browser or the account row.
 *
 * Two values live here today:
 *   page_size — how many cards a list shows before it paginates;
 *   view_mode — whether card grids open as 'grid' or 'list'.
 *
 * The module default for page_size still comes from Admin > Settings > General
 * (Setting::get('default_page_size')), so a preference is an override, not a
 * replacement: nobody who never opens the profile card loses the module-wide
 * default they already had.
 *
 * Theme is deliberately absent. Light/dark is the browser's own setting
 * (localStorage, applied in index.php), and a second store would make the two
 * disagree the moment somebody switched on another device.
 */

include_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/Setting.php';

final class DisplayPreference
{
    /** List lengths the UI offers; anything else is rejected, never silently reset. */
    public const PAGE_SIZES = [12, 24, 36];

    /** Card grid layouts the UI offers. */
    public const VIEW_MODES = ['grid', 'list'];

    /** Used when neither the employee nor the module default says otherwise. */
    public const DEFAULT_PAGE_SIZE = 12;

    /**
     * The module-wide list length from Admin > Settings > General, validated
     * against the lengths this module actually offers.
     */
    public static function modulePageSize(): int
    {
        try {
            $setting = new Setting();
            $value = (int) ($setting->get('default_page_size') ?: self::DEFAULT_PAGE_SIZE);

            return in_array($value, self::PAGE_SIZES, true) ? $value : self::DEFAULT_PAGE_SIZE;
        } catch (Throwable $e) {
            return self::DEFAULT_PAGE_SIZE;
        }
    }

    /**
     * What this employee's lists should do, saved row first and module default
     * second. Always returns both keys, so callers never have to invent a value.
     */
    public static function effective(PDO $pdo, int $employeeId, ?int $fallbackPageSize = null): array
    {
        $fallback = $fallbackPageSize ?? self::modulePageSize();
        if (!in_array($fallback, self::PAGE_SIZES, true)) {
            $fallback = self::DEFAULT_PAGE_SIZE;
        }

        $result = ['page_size' => $fallback, 'view_mode' => 'grid'];

        if ($employeeId <= 0) {
            return $result;
        }

        try {
            $stmt = $pdo->prepare(
                'SELECT page_size, view_mode
                 FROM ld_display_preference_store
                 WHERE user_id = :user_id
                 LIMIT 1'
            );
            $stmt->execute([':user_id' => $employeeId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return $result;
            }

            // Values are validated on write; re-check on read so a row edited by
            // hand cannot push nonsense into the UI.
            $pageSize = (int) $row['page_size'];
            if (in_array($pageSize, self::PAGE_SIZES, true)) {
                $result['page_size'] = $pageSize;
            }

            $viewMode = (string) $row['view_mode'];
            if (in_array($viewMode, self::VIEW_MODES, true)) {
                $result['view_mode'] = $viewMode;
            }
        } catch (Throwable $e) {
            error_log('[learning] display preference lookup failed: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Save one or both values. Fields that are absent keep their stored value,
     * so the profile card and an in-page grid/list toggle can each write the half
     * they own without clobbering the other.
     */
    public static function set(PDO $pdo, int $employeeId, array $input): array
    {
        if ($employeeId <= 0) {
            return ['success' => false, 'message' => 'User ID is required.'];
        }

        $current = self::effective($pdo, $employeeId);
        $pageSize = $current['page_size'];
        $viewMode = $current['view_mode'];

        if (array_key_exists('page_size', $input)) {
            $candidate = (int) $input['page_size'];
            if (!in_array($candidate, self::PAGE_SIZES, true)) {
                return ['success' => false, 'message' => 'Page size must be 12, 24 or 36.'];
            }
            $pageSize = $candidate;
        }

        if (array_key_exists('view_mode', $input)) {
            $candidate = trim((string) $input['view_mode']);
            if (!in_array($candidate, self::VIEW_MODES, true)) {
                return ['success' => false, 'message' => 'View mode must be grid or list.'];
            }
            $viewMode = $candidate;
        }

        try {
            // The unique key on user_id makes this a single upsert, so the first
            // save and the hundredth look the same.
            $stmt = $pdo->prepare(
                'INSERT INTO ld_display_preference_store (user_id, page_size, view_mode)
                 VALUES (:user_id, :page_size, :view_mode)
                 ON DUPLICATE KEY UPDATE page_size = VALUES(page_size), view_mode = VALUES(view_mode)'
            );
            $stmt->execute([
                ':user_id' => $employeeId,
                ':page_size' => $pageSize,
                ':view_mode' => $viewMode,
            ]);
        } catch (Throwable $e) {
            error_log('[learning] display preference save failed: ' . $e->getMessage());

            return ['success' => false, 'message' => 'Could not save your display preference.'];
        }

        return [
            'success' => true,
            'message' => 'Display preferences updated successfully.',
            'preferences' => [
                'page_size' => $pageSize,
                'view_mode' => $viewMode,
            ],
        ];
    }
}
