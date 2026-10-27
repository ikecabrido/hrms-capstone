/**
 * Pins what the reader clicked, on the page they landed on.
 *
 * A bell row's link carries two ids (see classes/notificationlinks.php): `highlight` is
 * the notification itself, and `record` is the row that notification is about — the
 * conference behind a reminder, the certificate behind an expiry warning. Without this,
 * "open the calendar" and "open the calendar, at that conference" are the same page, and
 * a reader who clicked one of six conference reminders is left to find it themselves.
 *
 * The page has nothing to configure: an element declares that it *is* one of those rows,
 * and this script rings it and brings it into view.
 *
 *     data-notification-id="12"   the notification, as the bell's own markup uses
 *     data-nid="12"               the same, on the admin list
 *     data-notif-id="12"          the same, on the learner list
 *     data-record-id="4"          the record a notification names
 *
 * The notification wins when both are present: it is what was clicked, and it is the row
 * the reader is looking for. A row inside a tab that is not open is opened first — the
 * certificate a reader was sent to, on the Results page, is behind the Certificates tab,
 * and ringing something nobody can see would be no better than not ringing it at all.
 */
(function () {
    'use strict';

    var params = new URLSearchParams(window.location.search);
    var notificationId = params.get('highlight');
    var recordId = params.get('record');

    if (!notificationId && !recordId) {
        return;
    }

    function byAttribute(attribute, value) {
        if (!value) {
            return null;
        }

        return document.querySelector('[' + attribute + '="' + value + '"]');
    }

    // The notification first: it is what was clicked, and it is what the reader is
    // looking for. The list pages do not share one attribute name — each grew its own
    // before this existed — so all three are accepted.
    var target =
        byAttribute('data-notification-id', notificationId) ||
        byAttribute('data-nid', notificationId) ||
        byAttribute('data-notif-id', notificationId) ||
        byAttribute('data-record-id', recordId);

    if (!target) {
        return;
    }

    function pin() {
        target.classList.add('ld-pinned');
        target.style.outline = '3px solid #ffc107';
        target.style.outlineOffset = '2px';
        target.scrollIntoView({ block: 'center', behavior: 'smooth' });
    }

    // The module's tabs are all tab-item[data-tab] buttons over tab-content[data-tab]
    // panels; clicking the button is how they are opened everywhere else.
    var panel = target.closest('.tab-content[data-tab]');
    if (panel && !panel.classList.contains('active')) {
        var button = document.querySelector('.tab-item[data-tab="' + panel.getAttribute('data-tab') + '"]');

        if (button) {
            button.click();
            // Let the tab's own script show the panel before measuring where to scroll.
            window.setTimeout(pin, 50);
            return;
        }
    }

    pin();
})();
