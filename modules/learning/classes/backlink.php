<?php
/**
 * BackLink — back links that respect where the visitor actually came from.
 *
 * Several pages are reachable from more than one section, so a hardcoded back link
 * sends half their visitors somewhere they never were: the certificate viewer is
 * opened from Results, Skill Gap and My Certificates, and the course builder pages
 * are opened from both E-Learning and the Trainings manager.
 *
 * A linking page appends ?back=<page>; the target page calls BackLink::resolve()
 * with its own default and renders the result. The requested value is matched
 * against the map below, so a query string can only ever produce a destination from
 * this list, and a missing or unknown value falls back to the page's own default.
 */
class BackLink
{
    /**
     * Page path => short name used in the "Back to …" label.
     * This map is also the whitelist of destinations.
     *
     * @var array<string, string>
     */
    private const LABELS = [
        'admin/admin-home'                   => 'Home',
        'admin/user'                         => 'Users',
        'admin/learning-path'                => 'Learning Paths',
        'instructor/instructor-home'         => 'Home',
        'instructor/elearning'               => 'E-Learning',
        'instructor/elearning-subpage/module' => 'Module',
        'instructor/elearning-subpage/lesson' => 'Lesson',
        'instructor/training'                => 'Trainings',
        'instructor/certificate'             => 'Certificates',
        'instructor/analytics'               => 'Analytics',
        'instructor/notification'            => 'Notifications',
        'learner/learner-home'               => 'Home',
        'learner/study'                      => 'Study',
        'learner/catalog'                    => 'Catalog',
        'learner/my-learning-path'           => 'My Learning Path',
        'learner/skill-gap'                  => 'Skill Gap',
        'learner/result'                     => 'Results',
        'learner/result-subpage/certificate' => 'Certificates',
        'learner/study-subpage/course'       => 'Course',
        'learner/study-subpage/skill'        => 'My Skills',
        'learner/study-subpage/evaluation'   => 'Evaluation',
        'learner/notification'               => 'Notifications',
        'learner/calendar'                   => 'Calendar',
    ];

    /**
     * Destination for a back link.
     *
     * A target is a page from the map, optionally followed by a query fragment —
     * e.g. 'instructor/elearning-subpage/module?id=7' — so a parent record can be
     * returned to as well as a parent section.
     *
     * @param string      $defaultTarget Target to return to when nothing valid was requested.
     * @param mixed       $requested     Usually omitted; defaults to ?back= from the current request.
     */
    public static function resolve(string $defaultTarget, $requested = null): string
    {
        if ($requested === null) {
            $requested = $_GET['back'] ?? '';
        }

        if (!is_string($requested)) {
            return $defaultTarget;
        }

        [$page, $query] = self::split(trim($requested));
        $candidate = strtolower($page) . ($query !== '' ? '?' . $query : '');

        return self::isKnown($candidate) ? $candidate : $defaultTarget;
    }

    /**
     * Destinations this class can link back to, as page path => short name.
     *
     * @return array<string, string>
     */
    public static function destinations(): array
    {
        return self::LABELS;
    }

    /**
     * Label for a resolved target, e.g. "Back to Study" or "Back to Module".
     */
    public static function label(string $target): string
    {
        [$page] = self::split($target);

        return isset(self::LABELS[$page]) ? 'Back to ' . self::LABELS[$page] : 'Back';
    }

    /**
     * Href for a resolved target. Uses index.php because back links are ordinary
     * content links, not sidebar links, so they are not intercepted by the SPA
     * navigation in index.php.
     */
    public static function url(string $target): string
    {
        [$page, $query] = self::split($target);
        $url = 'index.php?page=' . urlencode($page);

        return $query !== '' ? $url . '&' . $query : $url;
    }

    /**
     * Split a target into its page and its query fragment.
     *
     * @return array{0: string, 1: string}
     */
    private static function split(string $target): array
    {
        $parts = explode('?', $target, 2);

        return [$parts[0], $parts[1] ?? ''];
    }

    /**
     * A target is usable when its page is in the map and its query fragment is
     * nothing but an id — the only parameter a builder page needs to reopen. So a
     * crafted ?back= can neither reach a page outside the map nor attach
     * parameters of its own to a page that is in it.
     *
     * Public so callers (and the test harness) can validate a target before use.
     */
    public static function isKnown(string $target): bool
    {
        [$page, $query] = self::split($target);

        if (!isset(self::LABELS[$page])) {
            return false;
        }

        return $query === '' || (bool) preg_match('/^id=[0-9]+$/', $query);
    }

    /**
     * Complete anchor for a back link.
     *
     * @param string $defaultTarget Target to return to when nothing valid was requested.
     * @param string $attributes    Extra attributes, e.g. 'style="…"' or 'id="back"'.
     *                              Callers pass literal markup, not user input.
     * @param string $icon          Icon class placed before the label; '' for none.
     */
    public static function anchor(string $defaultTarget, string $attributes = '', string $icon = 'fas fa-arrow-left'): string
    {
        $page = self::resolve($defaultTarget);
        $glyph = $icon !== '' ? '<i class="' . htmlspecialchars($icon) . '"></i> ' : '';

        return '<a href="' . htmlspecialchars(self::url($page)) . '"'
            . ($attributes !== '' ? ' ' . $attributes : '')
            . '>' . $glyph . htmlspecialchars(self::label($page)) . '</a>';
    }
}
