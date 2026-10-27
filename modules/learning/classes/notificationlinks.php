<?php
/**
 * NotificationLinks — where a bell row opens, decided by this module.
 *
 * The bell menu itself is shared markup: includes/sidebar.php prints it for all twelve
 * modules from the same ld_notification store, and nothing in that markup says where a
 * row leads. The pages a row *does* lead to belong to this module, so the destination is
 * decided here and handed to this module's own scripts (js/bell-links.js applies it,
 * js/notification-highlight.js pins what was clicked). Nothing outside modules/learning
 * has to know about it, which is the point: the shared sidebar, its markup and its
 * endpoint stay as they are.
 *
 * A row opens the page that owns its subject, for the reader's own role — a conference
 * reminder opens the calendar, a certificate expiry the certificates/Results screen, a
 * training request the queue it is actioned from, a transfer plan the knowledge-transfer
 * screen. A type with no page of its own opens the reader's notification list instead, so
 * every row is a link and none is a dead end.
 *
 * Two parameters ride along, and they are what make a link a *destination* rather than a
 * page: `highlight` is the notification the reader clicked, and `record` is the row that
 * notification is about (the conference, the certificate) when it names one. Pages pin
 * whichever one they show — see js/notification-highlight.js.
 *
 * Roles are the module's own (Page::getLearningRole). A learner may not open an admin
 * page, so every mapping is per role and every target stays inside that role's tree —
 * tests/run-tests.php checks both against the router.
 *
 * The rows are read from the same table the shared Notification class reads, with the
 * same ordering, and the harness asserts the two agree row for row. It has to read them
 * here because the destination needs columns the bell's read model does not return
 * (reference_type / reference_id), and reaching into the shared class is not this
 * module's to change.
 */
final class NotificationLinks
{
    /** The roles the module routes for, in the order the nav uses them. */
    private const ROLES = ['admin', 'instructor', 'learner'];

    /**
     * Page each notification type opens, per role. A role missing from a type's entry
     * falls back to that role's notification list, so adding a role here never has to be
     * done for every type at once.
     *
     * The types are the ones this module writes: 'conference_reminder' and
     * 'certificate_expiry' come from cron/send-video-conference-reminder.php and
     * classes/certificate-expiry-checker.php; the rest are the vocabulary Notification's
     * icon map documents and classes/user.php::createNotification() accepts.
     */
    private const PAGES = [
        // One scheduled conference, which lives on the reader's calendar.
        'conference_reminder' => [
            'admin'      => 'admin/calendar',
            'instructor' => 'instructor/calendar',
            'learner'    => 'learner/calendar',
        ],
        // The "starting now" half of the same cron job — same record, same page.
        'conference_starting' => [
            'admin'      => 'admin/calendar',
            'instructor' => 'instructor/calendar',
            'learner'    => 'learner/calendar',
        ],

        // Raised per certificate; learners keep theirs under Results.
        'certificate' => [
            'instructor' => 'instructor/certificate',
            'learner'    => 'learner/result',
        ],
        'certificate_expiry' => [
            'instructor' => 'instructor/certificate',
            'learner'    => 'learner/result',
        ],
        'grade' => [
            'admin'      => 'admin/gradebook',
            'instructor' => 'instructor/gradebook',
            'learner'    => 'learner/result',
        ],
        // An enrollment or an invitation is about one learner on somebody's roster.
        'enrollment' => [
            'admin'      => 'admin/user',
            'instructor' => 'instructor/manage-learners',
            'learner'    => 'learner/study',
        ],
        'invitation' => [
            'admin'      => 'admin/user',
            'instructor' => 'instructor/manage-learners',
            'learner'    => 'learner/catalog',
        ],
        // A training request is actioned from the queue, not from a notification list.
        'training_request' => [
            'admin'      => 'instructor/training-requests',
            'instructor' => 'instructor/training-requests',
        ],
        // A transfer plan; instructors have no page of their own for it, so theirs is the
        // follow-up list rather than a page that cannot show it.
        'knowledge_transfer' => [
            'admin'   => 'admin/knowledge-transfer',
            'learner' => 'learner/knowledge-transfer',
        ],
    ];

    /** The reader's own notification list — also where an unmapped type lands. */
    private const INBOX = [
        'admin'      => 'admin/notification',
        'instructor' => 'instructor/notification',
        'learner'    => 'learner/notification',
    ];

    /** How many recent rows are mapped — the bell shows six; the rest is headroom. */
    public const RECENT_LIMIT = 20;

    /**
     * The notification columns a destination can be built from. reference_type is
     * descriptive only: on a given page there is one kind of record to pin, so
     * reference_id is what identifies it.
     */
    private const COLUMNS = 'id, type, reference_type, reference_id';

    /** The roles this class can build links for. */
    public static function roles(): array
    {
        return self::ROLES;
    }

    public static function isRole(string $role): bool
    {
        return in_array(strtolower($role), self::ROLES, true);
    }

    /**
     * A role we can route for. An unknown role (a session that has not resolved yet) is
     * treated as a learner rather than being given an admin page it may not open.
     */
    public static function normaliseRole(string $role): string
    {
        $role = strtolower($role);

        return in_array($role, self::ROLES, true) ? $role : 'learner';
    }

    /** The notification types this class has an opinion about. */
    public static function types(): array
    {
        return array_keys(self::PAGES);
    }

    /** The reader's notification list — where "View all notifications" leads. */
    public static function inboxPage(string $role): string
    {
        return self::INBOX[self::normaliseRole($role)];
    }

    /**
     * The page a notification of this type opens for this role: the type's own page when
     * it has one for the role, otherwise the role's notification list.
     */
    public static function pageFor(string $role, string $type): string
    {
        $role = self::normaliseRole($role);

        return self::PAGES[$type][$role] ?? self::inboxPage($role);
    }

    /**
     * The href for one notification, in the same shape the module's nav uses
     * (Page::renderLink): a routed query on the module's own entry point, so it resolves
     * wherever the application is deployed.
     *
     * `highlight` is always the notification clicked, so the list it lands on can show
     * which one it was; `record` is only added when the notification names a row, and is
     * what a page showing records (a calendar, a certificate list) pins instead.
     *
     * @param array<string, mixed> $row a row from recentRows()
     */
    public static function hrefFor(string $role, array $row): string
    {
        $params = [
            'page'      => self::pageFor($role, (string) ($row['type'] ?? '')),
            'highlight' => (int) ($row['id'] ?? 0),
        ];

        $record = (int) ($row['reference_id'] ?? 0);
        if ($record > 0) {
            $params['record'] = $record;
        }

        return '?' . http_build_query($params);
    }

    /** The href of the reader's notification list. */
    public static function inboxHref(string $role): string
    {
        return '?page=' . urlencode(self::inboxPage($role));
    }

    /**
     * The href for each of the given notification rows, keyed by notification id — the
     * shape js/bell-links.js looks a row's data-notification-id up in.
     *
     * @param iterable<array<string, mixed>> $rows rows from recentRows()
     * @return array<string, string>
     */
    public static function linksFor(string $role, iterable $rows): array
    {
        $links = [];

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $links[(string) $id] = self::hrefFor($role, $row);
        }

        return $links;
    }

    /**
     * The newest notifications of one employee, in the order the bell shows them. The
     * rows and the links therefore come from one table, one order and one set of columns;
     * tests/run-tests.php holds this against Notification::getRecent().
     *
     * @return list<array<string, mixed>>
     */
    public static function recentRows(int $userId, ?PDO $pdo = null, int $limit = self::RECENT_LIMIT): array
    {
        if ($userId <= 0) {
            return [];
        }

        $limit = max(1, min($limit, 50));

        if (!$pdo instanceof PDO) {
            require_once __DIR__ . '/../../../database/db.php';
            $pdo = (new Database())->getConnection();
        }

        $stmt = $pdo->prepare(
            'SELECT ' . self::COLUMNS . '
               FROM ld_notification
              WHERE user_id = :user_id
              ORDER BY created_at DESC, id DESC
              LIMIT ' . $limit
        );
        $stmt->execute([':user_id' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * The links for the newest notifications of one employee.
     *
     * @return array<string, string>
     */
    public static function recentLinks(string $role, int $userId, ?PDO $pdo = null, int $limit = self::RECENT_LIMIT): array
    {
        return self::linksFor($role, self::recentRows($userId, $pdo, $limit));
    }

    /**
     * What index.php hands to the script: where the reader's list is, and where each row
     * on screen goes.
     *
     * Never throws: the bell is part of every page in the module, so a store that cannot
     * be reached has to leave the menu as it would have been, not take the page down.
     *
     * @return array{inbox:string, links:array<string, string>}
     */
    public static function forScript(string $role, int $userId, ?PDO $pdo = null): array
    {
        $links = [];

        try {
            $links = self::recentLinks($role, $userId, $pdo);
        } catch (Throwable $e) {
            error_log('[learning] Bell links unavailable: ' . $e->getMessage());
        }

        return [
            'inbox' => self::inboxHref($role),
            'links' => $links,
        ];
    }
}
