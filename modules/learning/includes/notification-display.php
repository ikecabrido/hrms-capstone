<?php
/**
 * Presentation helpers shared by the three notification pages
 * (admin/notification.php, instructor/notification.php, learner/notification.php).
 *
 * The pages read from different tables — ld_notification_inbox for admins and learners,
 * ld_announcement + ld_message for instructors — but the type-to-icon mapping, the
 * colour tone and the relative timestamps are the same everywhere. Keeping them in
 * one place stops the three pages from drifting apart.
 *
 * Every function is guarded so a page can still be loaded on its own.
 */

if (!function_exists('ldNotifAge')) {
    /**
     * Compact age label: "just now", "4h ago", or an absolute date once it passes a week.
     *
     * The age (and the absolute date used for older rows) come from SQL columns on the
     * row rather than being derived from the timestamp here: PHP and MySQL run in
     * different timezones on this host, which shifted every relative time by the offset
     * between them.
     *
     * Expects age_seconds (TIMESTAMPDIFF) and optionally created_label (DATE_FORMAT).
     */
    function ldNotifAge(array $row): string
    {
        $seconds = max(0, (int) ($row['age_seconds'] ?? 0));

        if ($seconds < 60) {
            return 'just now';
        }
        if ($seconds < 3600) {
            return floor($seconds / 60) . 'm ago';
        }
        if ($seconds < 86400) {
            return floor($seconds / 3600) . 'h ago';
        }
        if ($seconds < 604800) {
            return floor($seconds / 86400) . 'd ago';
        }

        $label = trim((string) ($row['created_label'] ?? ''));

        return $label !== '' ? $label : 'over a week ago';
    }
}

if (!function_exists('ldNotifIcon')) {
    /** Font Awesome icon class for a notification type. */
    function ldNotifIcon(string $type): string
    {
        $icons = [
            'invitation'          => 'fa-user-plus',
            'certificate'         => 'fa-certificate',
            'certificate_expiry'  => 'fa-triangle-exclamation',
            'announcement'        => 'fa-bullhorn',
            'enrollment'          => 'fa-graduation-cap',
            'grade'               => 'fa-chart-line',
            'reminder'            => 'fa-clock',
            'message'             => 'fa-envelope',
            'update'              => 'fa-rotate',
            'report'              => 'fa-flag',
            'training_request'    => 'fa-paper-plane',
            'conference_reminder' => 'fa-bell-concierge',
            'conference_starting' => 'fa-video',
        ];

        return $icons[$type] ?? 'fa-bell';
    }
}

if (!function_exists('ldNotifTone')) {
    /** Colour tone for the icon bubble; see .ntf-icon.is-* in css/pages/notifications.css. */
    function ldNotifTone(string $type): string
    {
        $tones = [
            'invitation'          => 'violet',
            'certificate'         => 'amber',
            'certificate_expiry'  => 'rose',
            'announcement'        => 'indigo',
            'enrollment'          => 'emerald',
            'grade'               => 'indigo',
            'reminder'            => 'amber',
            'message'             => 'violet',
            'update'              => 'slate',
            'report'              => 'rose',
            'training_request'    => 'slate',
            'conference_reminder' => 'amber',
            'conference_starting' => 'violet',
        ];

        return $tones[$type] ?? 'slate';
    }
}

if (!function_exists('ldNotifLabel')) {
    /** Human label for a notification type, falling back to the title-cased raw value. */
    function ldNotifLabel(string $type): string
    {
        $labels = [
            'invitation'          => 'Invitation',
            'certificate'         => 'Certificate',
            'certificate_expiry'  => 'Certificate expiry',
            'announcement'        => 'Announcement',
            'enrollment'          => 'Enrollment',
            'grade'               => 'Grade',
            'reminder'            => 'Reminder',
            'message'             => 'Message',
            'update'              => 'Update',
            'report'              => 'Report',
            'training_request'    => 'Training request',
            'conference_reminder' => 'Session reminder',
            'conference_starting' => 'Session starting',
        ];

        return $labels[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }
}

if (!function_exists('ntfEmptyState')) {
    /** Shared empty state for a notification list. */
    function ntfEmptyState(string $icon, string $title, string $text): void
    {
        echo '<div class="ntf-empty">'
            . '<i class="fas ' . htmlspecialchars($icon) . '" aria-hidden="true"></i>'
            . '<strong>' . htmlspecialchars($title) . '</strong>'
            . '<span>' . htmlspecialchars($text) . '</span>'
            . '</div>';
    }
}

if (!function_exists('ntfPaginationRow')) {
    /**
     * Page controls for a list. The shared handler looks for .pagination-row as a
     * sibling of .cards-grid, so this is printed right after the grid. Lists that
     * fit on one page get no controls at all.
     */
    function ntfPaginationRow(int $count, int $pageSize = 12): void
    {
        if ($count <= $pageSize) {
            return;
        }

        echo '<div class="pagination-row">'
            . '<button type="button" class="page-btn" data-action="prev" disabled>Prev</button>'
            . '<span class="page-indicator">Page 1 of ' . (int) ceil($count / $pageSize) . '</span>'
            . '<button type="button" class="page-btn" data-action="next">Next</button>'
            . '</div>';
    }
}

if (!function_exists('ldNotifAudienceLabel')) {
    /** Turns the ld_announcement SET value ("learner,instructor") into a readable list. */
    function ldNotifAudienceLabel(?string $audience): string
    {
        $parts = array_filter(array_map('trim', explode(',', (string) $audience)));
        if (empty($parts)) {
            return 'Everyone';
        }

        $labels = [
            'all' => 'Everyone',
            'admin' => 'Admins',
            'instructor' => 'Trainers',
            'learner' => 'Trainees',
        ];

        return implode(', ', array_map(static function ($part) use ($labels) {
            return $labels[$part] ?? ucfirst($part) . 's';
        }, $parts));
    }
}

if (!function_exists('ldNotifPreferenceCatalog')) {
    /**
     * The notification types the preferences panel offers, in the order it lists
     * them. Keys are the `type` values the module writes into
     * ld_notification_inbox, so each switch governs exactly the rows it names; the
     * icon and tone come from the maps above, so a switch looks like its rows.
     *
     * Only types a writer can actually produce are listed — adding a switch for a
     * type nothing sends would be a control that does nothing. A new type is one
     * entry here plus the maps above.
     *
     * The wording is role-neutral: the same panel is printed on the admin,
     * instructor and learner notification pages, and the same type means slightly
     * different things to each (a training request is raised by a learner and
     * actioned by an instructor).
     */
    function ldNotifPreferenceCatalog(): array
    {
        return [
            'training_request' => [
                'label' => 'Training request updates',
                'blurb' => 'When a training request involving you is updated.',
            ],
            'conference_reminder' => [
                'label' => 'Session reminders',
                'blurb' => 'Before a video conference you are booked into.',
            ],
            'conference_starting' => [
                'label' => 'Session starting',
                'blurb' => 'When a scheduled video conference begins.',
            ],
            'certificate_expiry' => [
                'label' => 'Certificate expiry notices',
                'blurb' => 'Before a certificate lapses, and the day it does.',
            ],
        ];
    }
}

if (!function_exists('ntfPreferencesButton')) {
    /**
     * The toolbar button that reveals the preferences panel. Printed by all three
     * notification pages so the control sits in the same place on each.
     */
    function ntfPreferencesButton(): void
    {
        echo '<button type="button" id="ntf-prefs-toggle" class="ntf-btn ghost" aria-expanded="false" aria-controls="ntf-prefs" aria-label="Preferences" title="Preferences">'
            . '<i class="fas fa-sliders-h" aria-hidden="true"></i> Preferences</button>';
    }
}

if (!function_exists('ntfPreferencesPanel')) {
    /**
     * Notification preferences panel plus its behaviour.
     *
     * Printed here rather than written out per page so the three roles cannot
     * drift apart, and so the two shared endpoints are named in exactly one
     * place. The switches are real markup (readable without JavaScript); only
     * their state comes from the endpoint.
     *
     * The script is inline on purpose: the sidebar navigates by swapping page
     * HTML and re-running the scripts inside it, so a page-local handler has to
     * travel with the markup.
     */
    function ntfPreferencesPanel(): void
    {
        echo '<section class="ntf-prefs" id="ntf-prefs" hidden aria-label="Notification preferences">';
        echo '<div class="ntf-prefs-head">'
            . '<h2><i class="fas fa-sliders-h" aria-hidden="true"></i> Notification preferences</h2>'
            . '<p>Choose which notifications this module sends you. Each change is saved as you make it.</p>'
            . '</div>';
        echo '<ul class="ntf-prefs-list">';

        foreach (ldNotifPreferenceCatalog() as $type => $pref) {
            echo '<li class="ntf-pref" data-type="' . htmlspecialchars($type) . '">'
                . '<span class="ntf-icon is-' . htmlspecialchars(ldNotifTone($type)) . '" aria-hidden="true">'
                . '<i class="fas ' . htmlspecialchars(ldNotifIcon($type)) . '"></i></span>'
                . '<span class="ntf-pref-copy">'
                . '<strong>' . htmlspecialchars($pref['label']) . '</strong>'
                . '<span>' . htmlspecialchars($pref['blurb']) . '</span>'
                . '</span>'
                . '<label class="ntf-switch">'
                . '<input type="checkbox" data-pref-toggle aria-label="' . htmlspecialchars($pref['label']) . '" disabled />'
                . '<span class="ntf-switch-track" aria-hidden="true"></span>'
                . '</label>'
                . '</li>';
        }

        echo '</ul>';
        echo '<p class="ntf-prefs-status" id="ntf-prefs-status" role="status" aria-live="polite"></p>';
        echo '</section>';
        ?>
        <script>
        (function () {
            'use strict';

            var panel = document.getElementById('ntf-prefs');
            var toggle = document.getElementById('ntf-prefs-toggle');
            if (!panel || !toggle) return;

            var status = document.getElementById('ntf-prefs-status');
            var loaded = false;
            var csrfToken = window.CSRF_TOKEN
                || (document.querySelector('meta[name="csrf-token"]') || {}).content
                || '';

            function setStatus(message) {
                if (!status) return;
                status.textContent = message || '';
                status.classList.toggle('is-error', Boolean(message));
            }

            function loadPreferences() {
                setStatus('Loading your preferences...');
                fetch('pages/shared/ajax/get-notification-preference.php', {
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        if (!data || data.success !== true) throw new Error('failed');

                        var saved = data.preferences || {};
                        panel.querySelectorAll('[data-pref-toggle]').forEach(function (input) {
                            var row = input.closest('.ntf-pref');
                            if (!row) return;
                            // A type with no saved row is subscribed, which is the
                            // same default the notification writers apply.
                            var on = saved[row.dataset.type] === undefined
                                ? true
                                : saved[row.dataset.type] === true;
                            input.checked = on;
                            input.disabled = false;
                            row.classList.toggle('is-off', !on);
                        });
                        setStatus('');
                    })
                    .catch(function () {
                        setStatus('Could not load your preferences. Reload the page to try again.');
                    });
            }

            toggle.addEventListener('click', function () {
                var opening = panel.hasAttribute('hidden');

                if (opening) {
                    panel.removeAttribute('hidden');
                    if (!loaded) {
                        loaded = true;
                        loadPreferences();
                    }
                } else {
                    panel.setAttribute('hidden', '');
                }

                toggle.setAttribute('aria-expanded', opening ? 'true' : 'false');
                toggle.classList.toggle('is-on', opening);
            });

            panel.addEventListener('change', function (event) {
                var input = event.target.closest('[data-pref-toggle]');
                if (!input || input.disabled) return;

                var row = input.closest('.ntf-pref');
                var type = row ? row.dataset.type : '';
                if (!type) return;

                var wanted = input.checked;
                var labelNode = row.querySelector('strong');
                var label = labelNode ? labelNode.textContent : type;

                input.disabled = true;
                fetch('pages/shared/ajax/edit-notification-preference.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({ notification_type: type, enabled: wanted })
                })
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        if (!data || data.success !== true) throw new Error('failed');

                        row.classList.toggle('is-off', !wanted);
                        if (typeof window.showToast === 'function') {
                            window.showToast(label + (wanted ? ' turned on.' : ' turned off.'), 'success');
                        }
                    })
                    .catch(function () {
                        input.checked = !wanted;
                        if (typeof window.showToast === 'function') {
                            window.showToast('Could not save that preference.', 'error');
                        }
                    })
                    .finally(function () {
                        input.disabled = false;
                    });
            });
        })();
        </script>
        <?php
    }
}
