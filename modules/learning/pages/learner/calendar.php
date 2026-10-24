<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include_once __DIR__ . '/../../classes/Employee.php';
require_once dirname(__DIR__, 4) . '/database/db.php';

$employeeClass = new Employee();
$learnerId = (int) ($employeeClass->getEmployeeId() ?? 0);

$events = [];
$upcomingDeadlines = [];
$upcomingSessions = [];

try {
    $pdo = (new Database())->getConnection();

    // Get upcoming video conference sessions the learner is enrolled in
    $stmt = $pdo->prepare("
        SELECT vc.id, vc.title, vc.platform, vc.scheduled_at, vc.duration_minutes, vc.meeting_link,
               vc.instructor_id,
               CONCAT(emp.first_name, ' ', emp.last_name) AS host_name,
               'video-conference' AS event_type
        FROM ld_video_conference vc
        LEFT JOIN em_employees emp ON emp.employee_id = vc.instructor_id
        LEFT JOIN ld_conference_attendance vca ON vca.video_conference_id = vc.id
        WHERE vc.status = 'scheduled' AND vc.scheduled_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
          AND (vca.learner_id = :learner_id OR vc.instructor_id = :learner_id2)
        ORDER BY vc.scheduled_at ASC
        LIMIT 50
    ");
    $stmt->execute([':learner_id' => $learnerId, ':learner_id2' => $learnerId]);
    $upcomingSessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get enrollment deadlines (courses not yet completed)
    $stmt = $pdo->prepare("
        SELECT e.course_id, e.enrolled_at, c.title, c.enrollment_deadline, c.start_date,
               'deadline' AS event_type
        FROM ld_enrollment e
        JOIN ld_course c ON c.id = e.course_id
        WHERE e.learner_id = :learner_id AND e.status IN ('enrolled', 'in_progress')
          AND c.enrollment_deadline IS NOT NULL AND c.enrollment_deadline >= CURDATE()
        ORDER BY c.enrollment_deadline ASC
        LIMIT 50
    ");
    $stmt->execute([':learner_id' => $learnerId]);
    $upcomingDeadlines = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Merge into events
    foreach ($upcomingSessions as $s) {
        $events[] = [
            'date' => date('Y-m-d', strtotime($s['scheduled_at'])),
            'time' => date('g:i A', strtotime($s['scheduled_at'])),
            'title' => $s['title'],
            'type' => 'Video Conference',
            'color' => '#007bff',
            'icon' => 'fa-video',
            'meta' => $s['platform'] . ' • ' . $s['duration_minutes'] . ' min',
            'host_name' => $s['host_name'],
            'instructor_profile_url' => LearningProfileLink::url(
                (int) $s['instructor_id'],
                LearningRole::forEmployee($pdo, (int) $s['instructor_id'])
            ),
            'datetime' => $s['scheduled_at'],
            'conference_id' => $s['id'],
        ];
    }
    foreach ($upcomingDeadlines as $d) {
        $events[] = [
            'date' => date('Y-m-d', strtotime($d['enrollment_deadline'])),
            'time' => '11:59 PM',
            'title' => $d['title'] . ' — Deadline',
            'type' => 'Deadline',
            'color' => '#dc3545',
            'icon' => 'fa-exclamation-triangle',
            'meta' => 'Enrollment deadline for course',
            'datetime' => $d['enrollment_deadline'],
        ];
    }

    // User-created calendar events
    $userEvents = [];
    $stmt = $pdo->prepare("SELECT id, title, description, event_date, event_time, event_type, color FROM ld_user_event WHERE created_by = :uid ORDER BY event_date ASC");
    $stmt->execute([':uid' => $learnerId]);
    $userEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($userEvents as $ue) {
        $iconMap = ['meeting'=>'fa-users','deadline'=>'fa-exclamation-triangle','reminder'=>'fa-bell','personal'=>'fa-calendar-day','other'=>'fa-tag'];
        $events[] = [
            'date' => $ue['event_date'],
            'time' => $ue['event_time'] ? date('g:i A', strtotime($ue['event_time'])) : 'All day',
            'title' => $ue['title'],
            'type' => ucfirst($ue['event_type']),
            'color' => $ue['color'] ?: '#320082',
            'icon' => $iconMap[$ue['event_type']] ?? 'fa-calendar-day',
            'meta' => $ue['description'] ?: '',
            'datetime' => $ue['event_date'] . ' ' . ($ue['event_time'] ?? '00:00:00'),
            'is_user_event' => true,
            'event_id' => $ue['id'],
        ];
    }

    usort($events, fn($a, $b) => strtotime($a['datetime']) - strtotime($b['datetime']));
} catch (Throwable $e) {
    DbError::capture($e, 'learner/calendar');
    $events = [];
}

// Build calendar data
$now = new DateTime();
$year = (int) ($_GET['year'] ?? $now->format('Y'));
$month = (int) ($_GET['month'] ?? $now->format('m'));
$monthDate = new DateTime("$year-$month-01");
$daysInMonth = (int) $monthDate->format('t');
$firstDayOfWeek = (int) $monthDate->format('w'); // 0=Sun

// Group events by date
$eventsByDate = [];
foreach ($events as $ev) {
    $eventsByDate[$ev['date']][] = $ev;
}

$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

$monthNames = ['', 'January','February','March','April','May','June','July','August','September','October','November','December'];

// The "upcoming" list takes every event from today onwards.
$upcomingEvents = array_values(array_filter($events, fn($ev) => strtotime($ev['datetime']) >= strtotime('today')));
$upcomingCount = count($upcomingEvents);

// Seven at a time keeps the side panel shorter than the page.
$perPage = 7;
$totalPages = max(1, (int) ceil($upcomingCount / $perPage));
$epage = max(1, min($totalPages, (int) ($_GET['epage'] ?? 1)));
$pageEvents = array_slice($upcomingEvents, ($epage - 1) * $perPage, $perPage);

// Keep the visible month when paging through the list.
$monthQuery = ($year != (int) $now->format('Y') || $month != (int) $now->format('m')) ? '&year=' . $year . '&month=' . $month : '';

// Event colours come from the database; keep them to a hex literal so they are
// safe inside a style attribute, and let the module palette follow the theme.
$lcColor = static function ($color) {
    if (!is_string($color) || !preg_match('/^#[0-9a-fA-F]{3,8}$/', $color)) {
        return 'var(--primary)';
    }
    return strcasecmp($color, '#320082') === 0 ? 'var(--primary)' : $color;
};
?>
<div class="module-content lc-page">
    <div class="home-dashboard lc-dashboard">

        <section class="home-hero">
            <div class="home-hero-main">
                <div class="lc-month-nav">
                    <a class="lc-nav-btn" href="?page=learner/calendar&amp;year=<?= $prevYear ?>&amp;month=<?= $prevMonth ?>" aria-label="Previous month"><i class="fas fa-chevron-left"></i></a>
                    <h1><?= $monthNames[$month] ?> <?= $year ?></h1>
                    <a class="lc-nav-btn" href="?page=learner/calendar&amp;year=<?= $nextYear ?>&amp;month=<?= $nextMonth ?>" aria-label="Next month"><i class="fas fa-chevron-right"></i></a>
                </div>
            </div>
            <div class="lc-hero-actions">
                <a class="lc-hero-btn" href="?page=learner/calendar"><i class="fas fa-calendar-day"></i> Today</a>
                <button type="button" class="lc-hero-cta" onclick="openEventModal()"><i class="fas fa-plus"></i> Add Event</button>
            </div>
        </section>

        <!-- Month grid -->
        <div class="home-card lc-cal-card">
            <div class="lc-weekdays">
                <?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $dayName): ?>
                    <div class="lc-weekday"><?= $dayName ?></div>
                <?php endforeach; ?>
            </div>
            <div class="lc-grid">
                <?php
                $today = $now->format('Y-m-d');
                for ($i = 0; $i < $firstDayOfWeek; $i++):
                ?>
                    <div class="lc-day is-outside"></div>
                <?php endfor; ?>

                <?php for ($day = 1; $day <= $daysInMonth; $day++):
                    $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
                    $isToday = $dateStr === $today;
                    $dayEvents = $eventsByDate[$dateStr] ?? [];
                    $eventsJson = htmlspecialchars(json_encode($dayEvents), ENT_QUOTES);
                ?>
                    <div class="lc-day<?= $isToday ? ' is-today' : '' ?>" data-date="<?= $dateStr ?>" data-events='<?= $eventsJson ?>' onclick="openDayModal(this)">
                        <span class="lc-day-num"><?= $day ?></span>
                        <?php foreach (array_slice($dayEvents, 0, 3) as $ev): ?>
                            <span class="lc-ev" style="--ev:<?= $lcColor($ev['color']) ?>" title="<?= htmlspecialchars($ev['title']) ?>">
                                <i class="fas <?= $ev['icon'] ?>" aria-hidden="true"></i><span><?= htmlspecialchars($ev['title']) ?></span>
                            </span>
                        <?php endforeach; ?>
                        <?php if (count($dayEvents) > 3): ?>
                            <span class="lc-more">+<?= count($dayEvents) - 3 ?> more</span>
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>

                <?php
                $totalCells = $firstDayOfWeek + $daysInMonth;
                $remaining = (7 - ($totalCells % 7)) % 7;
                for ($i = 0; $i < $remaining; $i++):
                ?>
                    <div class="lc-day is-outside"></div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Upcoming events -->
        <div class="home-card lc-upcoming-card">
            <div class="home-card-head">
                <h2 class="home-card-title">
                    <span class="home-card-icon"><i class="fas fa-calendar-check"></i></span> Upcoming Events
                </h2>
                <?php if ($upcomingCount > 0): ?>
                    <span class="home-card-count"><?= $upcomingCount ?> scheduled</span>
                <?php endif; ?>
            </div>
            <?php if (empty($upcomingEvents)): ?>
                <div class="home-empty">
                    <i class="fas fa-calendar-times"></i>
                    <p>Nothing scheduled yet.</p>
                </div>
            <?php else: ?>
                <div class="home-list">
                    <?php foreach ($pageEvents as $ev): ?>
                        <div class="home-row lc-row">
                            <span class="lc-row-icon" style="--ev:<?= $lcColor($ev['color']) ?>"><i class="fas <?= $ev['icon'] ?>"></i></span>
                            <span class="home-row-body">
                                <span class="home-row-title"><?= htmlspecialchars($ev['title']) ?></span>
                                <span class="home-row-meta">
                                    <?= date('D, M j, Y', strtotime($ev['date'])) ?><?= $ev['time'] !== '' ? ' &middot; ' . htmlspecialchars($ev['time']) : '' ?><?= $ev['meta'] !== '' ? ' &middot; ' . htmlspecialchars($ev['meta']) : '' ?>
                                </span>
                            </span>
                            <span class="home-row-trail">
                                <span class="home-badge"><?= htmlspecialchars($ev['type']) ?></span>
                                <?php if ($ev['type'] === 'Video Conference' && !empty($ev['conference_id']) && strtotime($ev['datetime']) > time()): ?>
                                    <a class="home-cta" href="?page=learner/catalog-subpage/video-conference&amp;back=learner/calendar&amp;video_conference_id=<?= (int) $ev['conference_id'] ?>"><i class="fas fa-video"></i> Join</a>
                                <?php endif; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                    <?php
                        $pageLink = fn($p) => '?page=learner/calendar' . $monthQuery . '&epage=' . $p;
                        $startPage = max(1, $epage - 2);
                        $endPage = min($totalPages, $startPage + 4);
                        $startPage = max(1, $endPage - 4);
                    ?>
                    <nav class="lc-pager" aria-label="Upcoming events pages">
                        <?php if ($epage > 1): ?>
                            <a class="lc-page-link" href="<?= $pageLink($epage - 1) ?>" aria-label="Previous page"><i class="fas fa-chevron-left"></i></a>
                        <?php endif; ?>
                        <?php if ($startPage > 1): ?>
                            <a class="lc-page-link<?= $epage === 1 ? ' is-current' : '' ?>" href="<?= $pageLink(1) ?>">1</a>
                            <?php if ($startPage > 2): ?><span class="lc-pager-gap">&hellip;</span><?php endif; ?>
                        <?php endif; ?>
                        <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                            <a class="lc-page-link<?= $p === $epage ? ' is-current' : '' ?>" href="<?= $pageLink($p) ?>"<?= $p === $epage ? ' aria-current="page"' : '' ?>><?= $p ?></a>
                        <?php endfor; ?>
                        <?php if ($endPage < $totalPages): ?>
                            <?php if ($endPage < $totalPages - 1): ?><span class="lc-pager-gap">&hellip;</span><?php endif; ?>
                            <a class="lc-page-link<?= $totalPages === $epage ? ' is-current' : '' ?>" href="<?= $pageLink($totalPages) ?>"><?= $totalPages ?></a>
                        <?php endif; ?>
                        <?php if ($epage < $totalPages): ?>
                            <a class="lc-page-link" href="<?= $pageLink($epage + 1) ?>" aria-label="Next page"><i class="fas fa-chevron-right"></i></a>
                        <?php endif; ?>
                    </nav>
                    <p class="lc-pager-note">Showing <?= ($epage - 1) * $perPage + 1 ?>&ndash;<?= min($epage * $perPage, $upcomingCount) ?> of <?= $upcomingCount ?> upcoming event<?= $upcomingCount === 1 ? '' : 's' ?></p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Day breakdown -->
<div id="day-modal-overlay" class="modal-overlay" style="display:none; z-index:9999;">
    <div class="lc-modal">
        <div class="lc-modal-head">
            <div>
                <h3 id="day-modal-date" class="lc-modal-title"></h3>
                <p id="day-modal-day" class="lc-modal-sub"></p>
            </div>
            <button type="button" class="lc-modal-close" onclick="document.getElementById('day-modal-overlay').style.display='none'" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <div id="day-modal-body" class="lc-modal-body"></div>
    </div>
</div>

<!-- Event create / edit -->
<div id="event-modal-overlay" class="modal-overlay" style="display:none; z-index:10000;">
    <div class="lc-modal">
        <div class="lc-modal-head">
            <h3 id="event-modal-title" class="lc-modal-title">New Event</h3>
            <button type="button" class="lc-modal-close" onclick="document.getElementById('event-modal-overlay').style.display='none'" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <form id="event-form" data-skip>
            <div class="lc-modal-body">
                <input type="hidden" name="event_id" id="event-form-id" value="" />
                <div class="lc-field">
                    <label class="lc-label" for="lc-event-title">Title *</label>
                    <input class="lc-input" type="text" id="lc-event-title" name="title" required placeholder="e.g. Team Meeting" />
                </div>
                <div class="lc-field">
                    <label class="lc-label" for="lc-event-description">Description</label>
                    <textarea class="lc-input" id="lc-event-description" name="description" rows="2" placeholder="Optional details..."></textarea>
                </div>
                <div class="lc-grid-2 lc-field">
                    <div>
                        <label class="lc-label" for="lc-event-date">Date *</label>
                        <input class="lc-input" type="date" id="lc-event-date" name="event_date" required />
                    </div>
                    <div>
                        <label class="lc-label" for="lc-event-time">Time</label>
                        <input class="lc-input" type="time" id="lc-event-time" name="event_time" />
                    </div>
                </div>
                <div class="lc-grid-2">
                    <div>
                        <label class="lc-label" for="lc-event-type">Type</label>
                        <select class="lc-input" id="lc-event-type" name="event_type">
                            <option value="personal">Personal</option>
                            <option value="meeting">Meeting</option>
                            <option value="deadline">Deadline</option>
                            <option value="reminder">Reminder</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="lc-label" for="lc-event-color">Colour</label>
                        <input class="lc-input is-color" type="color" id="lc-event-color" name="color" value="#320082" />
                    </div>
                </div>
            </div>
            <div class="lc-modal-foot">
                <button type="button" class="lc-btn-ghost" onclick="document.getElementById('event-modal-overlay').style.display='none'">Cancel</button>
                <button type="submit" id="event-form-submit" class="lc-btn-primary"><i class="fas fa-save"></i> Create Event</button>
            </div>
        </form>
    </div>
</div>

<!-- Confirm deletion -->
<div id="confirm-modal-overlay" class="modal-overlay" style="display:none; z-index:10001;">
    <div class="lc-modal is-sm">
        <div class="lc-confirm">
            <span class="lc-confirm-icon"><i class="fas fa-exclamation-triangle"></i></span>
            <h3 class="lc-modal-title">Confirm Deletion</h3>
            <p id="confirm-modal-message" class="lc-confirm-text"></p>
        </div>
        <div class="lc-modal-foot is-center">
            <button type="button" id="confirm-modal-cancel" class="lc-btn-ghost">Cancel</button>
            <button type="button" id="confirm-modal-ok" class="lc-btn-danger">Delete</button>
        </div>
    </div>
</div>

<script>
function lcEsc(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function (c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
}
function lcColor(color) {
    if (typeof color !== 'string' || !/^#[0-9a-fA-F]{3,8}$/.test(color)) return 'var(--primary)';
    return color.toLowerCase() === '#320082' ? 'var(--primary)' : color;
}

// --- Day breakdown ---
function openDayModal(cell) {
    var date = cell.getAttribute('data-date');
    var events = JSON.parse(cell.getAttribute('data-events') || '[]');
    var overlay = document.getElementById('day-modal-overlay');
    var dateEl = document.getElementById('day-modal-date');
    var dayEl = document.getElementById('day-modal-day');
    var body = document.getElementById('day-modal-body');

    var d = new Date(date + 'T12:00:00');
    var dayNames = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    var monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    dateEl.textContent = monthNames[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear();
    dayEl.textContent = dayNames[d.getDay()] + ' · ' + events.length + (events.length === 1 ? ' event' : ' events');

    if (events.length === 0) {
        body.innerHTML = '<div class="home-empty"><i class="fas fa-calendar-times"></i><p>No events on this day.</p></div>';
    } else {
        var html = '';
        events.forEach(function (ev) {
            html += '<div class="lc-day-ev" style="--ev:' + lcColor(ev.color) + '">';
            html += '<span class="lc-day-ev-icon"><i class="fas ' + lcEsc(ev.icon) + '"></i></span>';
            html += '<span class="lc-day-ev-body">';
            html += '<span class="lc-day-ev-title">' + lcEsc(ev.title) + '</span>';
            html += '<span class="lc-day-ev-meta">' + lcEsc(ev.time) + ' · ' + lcEsc(ev.type) + '</span>';
            if (ev.meta) html += '<span class="lc-day-ev-meta">' + lcEsc(ev.meta) + '</span>';
            if (ev.host_name) {
                var host = ev.instructor_profile_url
                    ? '<a href="' + lcEsc(ev.instructor_profile_url) + '">' + lcEsc(ev.host_name) + '</a>'
                    : lcEsc(ev.host_name);
                html += '<span class="lc-day-ev-meta">Host: ' + host + '</span>';
            }
            html += '</span>';
            if (ev.is_user_event) {
                var id = parseInt(ev.event_id, 10) || 0;
                html += '<span class="lc-day-ev-actions">';
                html += '<button type="button" class="lc-icon-btn" aria-label="Edit event" onclick="editUserEvent(' + id + ',\'' + lcEsc(ev.date) + '\')"><i class="fas fa-pen"></i></button>';
                html += '<button type="button" class="lc-icon-btn is-danger" aria-label="Delete event" onclick="deleteUserEvent(' + id + ')"><i class="fas fa-trash"></i></button>';
                html += '</span>';
            }
            html += '</div>';
        });
        body.innerHTML = html;
    }
    overlay.style.display = 'flex';
}

document.getElementById('day-modal-overlay').addEventListener('click', function (e) {
    if (e.target.id === 'day-modal-overlay') this.style.display = 'none';
});

// --- Event create / edit ---
function openEventModal(date, eventId) {
    var overlay = document.getElementById('event-modal-overlay');
    var title = document.getElementById('event-modal-title');
    var form = document.getElementById('event-form');
    form.reset();
    document.getElementById('event-form-id').value = '';
    if (date) form.querySelector('[name=event_date]').value = date;
    if (eventId) {
        title.textContent = 'Edit Event';
        document.getElementById('event-form-submit').innerHTML = '<i class="fas fa-save"></i> Save Changes';
        fetch('pages/learner/ajax/get-calendar-events.php?start=' + date + '&end=' + date)
            .then(function (r) { return r.json(); }).then(function (data) {
                var ev = (data.events || []).find(function (e) { return e.id == eventId; });
                if (ev) {
                    document.getElementById('event-form-id').value = ev.id;
                    form.querySelector('[name=title]').value = ev.title;
                    form.querySelector('[name=description]').value = ev.description || '';
                    form.querySelector('[name=event_date]').value = ev.event_date;
                    form.querySelector('[name=event_time]').value = ev.event_time || '';
                    form.querySelector('[name=event_type]').value = ev.event_type;
                    form.querySelector('[name=color]').value = ev.color;
                }
            });
    } else {
        title.textContent = 'New Event';
        document.getElementById('event-form-submit').innerHTML = '<i class="fas fa-save"></i> Create Event';
    }
    overlay.style.display = 'flex';
}

function editUserEvent(eventId, date) {
    document.getElementById('day-modal-overlay').style.display = 'none';
    openEventModal(date, eventId);
}

function deleteUserEvent(eventId) {
    openConfirmModal('Are you sure you want to delete this event?', function () {
        var fd = new FormData();
        fd.append('event_id', eventId);
        fetch('pages/learner/ajax/delete-calendar-event.php', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); }).then(function (data) {
                if (data.success) {
                    if (window.showToast) window.showToast('Event deleted', 'success');
                    setTimeout(function () { location.reload(); }, 800);
                } else if (window.showToast) {
                    window.showToast(data.error || 'Failed to delete', 'error');
                }
            });
    });
}

document.getElementById('event-form').addEventListener('submit', function (e) {
    e.preventDefault();
    var form = document.getElementById('event-form');
    var fd = new FormData(form);
    var isEdit = fd.get('event_id') !== '';
    var url = isEdit ? 'pages/learner/ajax/edit-calendar-event.php' : 'pages/learner/ajax/add-calendar-event.php';
    var btn = form.querySelector('button[type=submit]');
    btn.disabled = true;
    fetch(url, { method: 'POST', body: fd })
        .then(function (r) { return r.json(); }).then(function (data) {
            if (data.success) {
                if (window.showToast) window.showToast(isEdit ? 'Event updated' : 'Event created', 'success');
                setTimeout(function () { location.reload(); }, 800);
            } else if (window.showToast) {
                window.showToast(data.error || 'Failed', 'error');
            }
        }).finally(function () { btn.disabled = false; });
});

document.getElementById('event-modal-overlay').addEventListener('click', function (e) {
    if (e.target.id === 'event-modal-overlay') this.style.display = 'none';
});

// --- Confirm dialog ---
var confirmCallback = null;
function openConfirmModal(msg, onConfirm) {
    document.getElementById('confirm-modal-message').textContent = msg;
    document.getElementById('confirm-modal-overlay').style.display = 'flex';
    confirmCallback = onConfirm;
}
document.getElementById('confirm-modal-ok').addEventListener('click', function () {
    document.getElementById('confirm-modal-overlay').style.display = 'none';
    if (confirmCallback) confirmCallback();
});
document.getElementById('confirm-modal-cancel').addEventListener('click', function () {
    document.getElementById('confirm-modal-overlay').style.display = 'none';
    confirmCallback = null;
});
document.getElementById('confirm-modal-overlay').addEventListener('click', function (e) {
    if (e.target.id === 'confirm-modal-overlay') this.style.display = 'none';
});
</script>
