<?php
include_once __DIR__ . '/../../classes/Employee.php';
require_once dirname(__DIR__, 4) . '/database/db.php';

$employeeClass = new Employee();
$instructorId = (int) ($employeeClass->getEmployeeId() ?? 0);
$events = [];

try {
    $pdo = (new Database())->getConnection();

    // My scheduled live sessions (video conferences I host)
    $stmt = $pdo->prepare("SELECT vc.id, vc.title, vc.platform, vc.scheduled_at, vc.duration_minutes, 'video-conference' AS event_type FROM ld_video_conference vc WHERE vc.instructor_id = :iid AND vc.status = 'scheduled' AND vc.scheduled_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY vc.scheduled_at ASC LIMIT 50");
    $stmt->execute([':iid' => $instructorId]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $s) {
        $events[] = ['date'=>date('Y-m-d',strtotime($s['scheduled_at'])),'time'=>date('g:i A',strtotime($s['scheduled_at'])),'title'=>$s['title'],'type'=>'Live Session','color'=>'#2D8CFF','icon'=>'fa-video','meta'=>ucfirst(str_replace('_',' ',$s['platform'])).' &bull; '.$s['duration_minutes'].' min','datetime'=>$s['scheduled_at']];
    }

    // Enrollment deadlines for my courses
    $stmt = $pdo->prepare("SELECT c.title, c.enrollment_deadline FROM ld_course c WHERE c.instructor_id = :iid AND c.status = 'active' AND c.enrollment_deadline IS NOT NULL AND c.enrollment_deadline >= CURDATE() ORDER BY c.enrollment_deadline ASC LIMIT 50");
    $stmt->execute([':iid' => $instructorId]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $d) {
        $events[] = ['date'=>date('Y-m-d',strtotime($d['enrollment_deadline'])),'time'=>'11:59 PM','title'=>$d['title'].' — Deadline','type'=>'Deadline','color'=>'#dc3545','icon'=>'fa-exclamation-triangle','meta'=>'Enrollment deadline','datetime'=>$d['enrollment_deadline']];
    }

    // My courses starting soon
    $stmt = $pdo->prepare("SELECT c.title, c.start_date FROM ld_course c WHERE c.instructor_id = :iid AND c.status = 'active' AND c.start_date IS NOT NULL AND c.start_date >= CURDATE() ORDER BY c.start_date ASC LIMIT 50");
    $stmt->execute([':iid' => $instructorId]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $d) {
        $events[] = ['date'=>date('Y-m-d',strtotime($d['start_date'])),'time'=>'12:00 AM','title'=>$d['title'].' — Course Start','type'=>'Course Start','color'=>'#059669','icon'=>'fa-play-circle','meta'=>'Course opens for learners','datetime'=>$d['start_date'].' 00:00:00'];
    }

    // My user-created calendar events
    $stmt = $pdo->prepare("SELECT id, title, description, event_date, event_time, event_type, color FROM ld_user_event WHERE created_by = :uid ORDER BY event_date ASC");
    $stmt->execute([':uid' => $instructorId]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $ue) {
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

    usort($events, fn($a,$b) => strtotime($a['datetime']) - strtotime($b['datetime']));
} catch (Throwable $e) { $events = []; }

$now = new DateTime();
$year = (int) ($_GET['year'] ?? $now->format('Y'));
$month = (int) ($_GET['month'] ?? $now->format('m'));
$monthDate = new DateTime("$year-$month-01");
$daysInMonth = (int) $monthDate->format('t');
$firstDayOfWeek = (int) $monthDate->format('w');

$eventsByDate = [];
foreach ($events as $ev) { $eventsByDate[$ev['date']][] = $ev; }

$prevMonth = $month - 1; $prevYear = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
$nextMonth = $month + 1; $nextYear = $year;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }
$monthNames = ['', 'January','February','March','April','May','June','July','August','September','October','November','December'];
?>
<div class="module-content">
    <div class="toolbar">
        <div style="display:flex;align-items:center;justify-content:space-between;width:100%;gap:0.75rem;">
            <a href="?page=instructor/calendar" style="padding:0.5rem 1rem;background:var(--primary);color:#fff;border:none;border-radius:8px;text-decoration:none;font-size:0.85rem;font-weight:600;white-space:nowrap;">Today</a>
            <div style="display:flex;align-items:center;gap:0.75rem;flex:1;justify-content:center;">
                <a href="?page=instructor/calendar&year=<?= $prevYear ?>&month=<?= $prevMonth ?>" style="padding:0.4rem 0.8rem;background:#f0f0f0;border:1px solid #ddd;border-radius:6px;text-decoration:none;color:#333;"><i class="fas fa-chevron-left"></i></a>
                <h2 style="margin:0;white-space:nowrap;font-size:1.2rem;color:var(--text);"> <?= $monthNames[$month] ?> <?= $year ?></h2>
                <a href="?page=instructor/calendar&year=<?= $nextYear ?>&month=<?= $nextMonth ?>" style="padding:0.4rem 0.8rem;background:#f0f0f0;border:1px solid #ddd;border-radius:6px;text-decoration:none;color:#333;"><i class="fas fa-chevron-right"></i></a>
            </div>
            <button onclick="openEventModal()" style="display:inline-flex;align-items:center;gap:0.4rem;padding:0.5rem 1rem;background:var(--primary);color:#fff;border:none;border-radius:8px;font-size:0.85rem;font-weight:600;cursor:pointer;white-space:nowrap;"><i class="fas fa-plus"></i> Add Event</button>
        </div>
    </div>

    <div class="mode-card" style="padding:0;overflow:hidden;">
        <div style="display:grid;grid-template-columns:repeat(7,minmax(0,1fr));background:#f5f5f5;border-bottom:2px solid #eee;">
            <?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $dayName): ?>
                <div style="padding:0.75rem;text-align:center;font-weight:600;color:#666;font-size:0.85rem;text-transform:uppercase;"><?= $dayName ?></div>
            <?php endforeach; ?>
        </div>
        <div style="display:grid;grid-template-columns:repeat(7,minmax(0,1fr));">
            <?php $today = $now->format('Y-m-d'); ?>
            <?php for ($i = 0; $i < $firstDayOfWeek; $i++): ?>
                <div style="min-height:100px;padding:0.5rem;background:#fafafa;border:1px solid #f0f0f0;"></div>
            <?php endfor; ?>
            <?php for ($day = 1; $day <= $daysInMonth; $day++):
                $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
                $isToday = $dateStr === $today;
                $dayEvents = $eventsByDate[$dateStr] ?? [];
                $eventsJson = htmlspecialchars(json_encode($dayEvents), ENT_QUOTES);
            ?>
                <div class="cal-day-cell" data-date="<?= $dateStr ?>" data-events='<?= $eventsJson ?>' onclick="openDayModal(this)" style="min-height:100px;padding:0.5rem;border:1px solid #f0f0f0;background:<?= $isToday ? 'rgba(32,0,130,0.05)' : 'var(--surface, #fff)' ?>;cursor:pointer;transition:background 0.15s;">
                    <div style="font-weight:<?= $isToday ? '700' : '500' ?>;color:<?= $isToday ? 'var(--primary)' : '#333' ?>;margin-bottom:0.3rem;<?= $isToday ? 'display:inline-block;background:var(--primary);color:#fff;width:24px;height:24px;border-radius:50%;text-align:center;line-height:24px;font-size:0.85rem;' : '' ?>"><?= $day ?></div>
                    <?php foreach (array_slice($dayEvents, 0, 3) as $ev): ?>
                        <div style="font-size:0.75rem;padding:0.2rem 0.4rem;margin-bottom:2px;max-width:100%;background:<?= $ev['color'] ?>;color:#fff;border-radius:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;pointer-events:none;"><i class="fas <?= $ev['icon'] ?>" style="margin-right:3px;"></i><?= htmlspecialchars($ev['title']) ?></div>
                    <?php endforeach; ?>
                    <?php if (count($dayEvents) > 3): ?><div style="font-size:0.7rem;color:#999;padding:0.1rem 0.4rem;pointer-events:none;">+<?= count($dayEvents) - 3 ?> more</div><?php endif; ?>
                </div>
            <?php endfor; ?>
            <?php $totalCells = $firstDayOfWeek + $daysInMonth; $remaining = (7 - ($totalCells % 7)) % 7; for ($i = 0; $i < $remaining; $i++): ?>
                <div style="min-height:100px;padding:0.5rem;background:#fafafa;border:1px solid #f0f0f0;"></div>
            <?php endfor; ?>
        </div>
    </div>

    <div class="mode-card" style="margin-top:1.5rem;">
        <h3><i class="fas fa-calendar-check" style="color:var(--primary);margin-right:0.5rem;"></i> Upcoming Events</h3>
        <?php if (empty($events)): ?>
            <div style="padding:2rem;text-align:center;color:#999;"><i class="fas fa-calendar-times" style="font-size:2rem;display:block;margin-bottom:0.5rem;"></i>No upcoming events</div>
        <?php else: ?>
            <div style="display:grid;gap:0.75rem;margin-top:1rem;">
                <?php foreach (array_slice($events, 0, 10) as $ev): ?>
                    <div style="display:flex;align-items:center;gap:1rem;padding:1rem;background:#f9f9f9;border-radius:10px;border-left:4px solid <?= $ev['color'] ?>;">
                        <div style="width:40px;height:40px;border-radius:8px;background:<?= $ev['color'] ?>;color:#fff;display:flex;align-items:center;justify-content:center;"><i class="fas <?= $ev['icon'] ?>"></i></div>
                        <div style="flex:1;"><strong><?= htmlspecialchars($ev['title']) ?></strong><p style="margin:0.2rem 0 0;color:#666;font-size:0.85rem;"><?= date('l, M j, Y', strtotime($ev['date'])) ?> at <?= $ev['time'] ?> &bull; <?= htmlspecialchars($ev['meta']) ?></p></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Event Create/Edit Modal -->
<div id="event-modal-overlay" class="modal-overlay" style="display:none; z-index:10000;">
    <div style="background:var(--surface, #fff); border-radius:16px; width:90%; max-width:480px; max-height:90vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,0.2);">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:1.25rem 1.5rem; border-bottom:1px solid rgba(32,0,130,0.08);">
            <h3 id="event-modal-title" style="margin:0; font-size:1.1rem; font-weight:800; color:var(--text, #222);">New Event</h3>
            <button onclick="document.getElementById('event-modal-overlay').style.display='none'" style="background:none; border:none; font-size:1.2rem; cursor:pointer; color:rgba(32,0,130,0.4); padding:0.25rem;"><i class="fas fa-times"></i></button>
        </div>
        <form id="event-form" style="padding:1.5rem;">
            <input type="hidden" name="event_id" id="event-form-id" value="" />
            <div style="margin-bottom:1rem;">
                <label style="display:block; font-size:0.78rem; font-weight:700; color:var(--primary, #320082); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:0.4rem;">Title *</label>
                <input type="text" name="title" required placeholder="e.g. Team Meeting" style="width:100%; padding:0.6rem 0.8rem; border:1.5px solid rgba(32,0,130,0.12); border-radius:8px; font-size:0.9rem; box-sizing:border-box;" />
            </div>
            <div style="margin-bottom:1rem;">
                <label style="display:block; font-size:0.78rem; font-weight:700; color:var(--primary, #320082); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:0.4rem;">Description</label>
                <textarea name="description" rows="2" placeholder="Optional details..." style="width:100%; padding:0.6rem 0.8rem; border:1.5px solid rgba(32,0,130,0.12); border-radius:8px; font-size:0.9rem; resize:vertical; box-sizing:border-box;"></textarea>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:1rem;">
                <div>
                    <label style="display:block; font-size:0.78rem; font-weight:700; color:var(--primary, #320082); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:0.4rem;">Date *</label>
                    <input type="date" name="event_date" required style="width:100%; padding:0.6rem 0.8rem; border:1.5px solid rgba(32,0,130,0.12); border-radius:8px; font-size:0.9rem; box-sizing:border-box;" />
                </div>
                <div>
                    <label style="display:block; font-size:0.78rem; font-weight:700; color:var(--primary, #320082); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:0.4rem;">Time</label>
                    <input type="time" name="event_time" style="width:100%; padding:0.6rem 0.8rem; border:1.5px solid rgba(32,0,130,0.12); border-radius:8px; font-size:0.9rem; box-sizing:border-box;" />
                </div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:1.25rem;">
                <div>
                    <label style="display:block; font-size:0.78rem; font-weight:700; color:var(--primary, #320082); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:0.4rem;">Type</label>
                    <select name="event_type" style="width:100%; padding:0.6rem 0.8rem; border:1.5px solid rgba(32,0,130,0.12); border-radius:8px; font-size:0.9rem; box-sizing:border-box;">
                        <option value="personal">Personal</option>
                        <option value="meeting">Meeting</option>
                        <option value="deadline">Deadline</option>
                        <option value="reminder">Reminder</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label style="display:block; font-size:0.78rem; font-weight:700; color:var(--primary, #320082); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:0.4rem;">Color</label>
                    <input type="color" name="color" value="#320082" style="width:100%; height:38px; padding:0.2rem; border:1.5px solid rgba(32,0,130,0.12); border-radius:8px; cursor:pointer; box-sizing:border-box;" />
                </div>
            </div>
            <div style="display:flex; gap:0.75rem; justify-content:flex-end;">
                <button type="button" onclick="document.getElementById('event-modal-overlay').style.display='none'" style="padding:0.55rem 1.2rem; border:1.5px solid rgba(32,0,130,0.15); border-radius:8px; background:transparent; font-weight:700; font-size:0.85rem; cursor:pointer;">Cancel</button>
                <button type="submit" id="event-form-submit" style="padding:0.55rem 1.2rem; border:none; border-radius:8px; background:var(--primary, #320082); color:var(--surface, #fff); font-weight:700; font-size:0.85rem; cursor:pointer;"><i class="fas fa-save"></i> Create Event</button>
            </div>
        </form>
    </div>
</div>

<!-- Confirm Modal -->
<div id="confirm-modal-overlay" class="modal-overlay" style="display:none; z-index:10001;">
    <div style="background:var(--surface, #fff); border-radius:16px; width:90%; max-width:400px; box-shadow:0 20px 60px rgba(0,0,0,0.2); padding:1.5rem; text-align:center;">
        <div style="font-size:2rem; color:#ef4444; margin-bottom:0.75rem;"><i class="fas fa-exclamation-triangle"></i></div>
        <h3 style="margin:0 0 0.5rem 0; color:var(--text, #222);">Confirm Deletion</h3>
        <p id="confirm-modal-message" style="color:#666; margin:0 0 1.25rem 0; font-size:0.9rem;"></p>
        <div style="display:flex; gap:0.75rem; justify-content:center;">
            <button id="confirm-modal-cancel" style="padding:0.55rem 1.2rem; border:1.5px solid rgba(32,0,130,0.15); border-radius:8px; background:transparent; font-weight:700; font-size:0.85rem; cursor:pointer;">Cancel</button>
            <button id="confirm-modal-ok" style="padding:0.55rem 1.2rem; border:none; border-radius:8px; background:#ef4444; color:#fff; font-weight:700; font-size:0.85rem; cursor:pointer;">Delete</button>
        </div>
    </div>
</div>

<!-- Day Event Modal -->
<div id="day-modal-overlay" class="modal-overlay" style="display:none; z-index:9999;">
    <div style="background:var(--surface, #fff); border-radius:16px; width:90%; max-width:500px; max-height:80vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,0.2);">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:1.25rem 1.5rem; border-bottom:1px solid rgba(32,0,130,0.08);">
            <div>
                <h3 id="day-modal-date" style="margin:0; font-size:1.1rem; font-weight:800; color:var(--text, #222);"></h3>
                <p id="day-modal-day" style="margin:0.2rem 0 0 0; font-size:0.8rem; color:rgba(32,0,130,0.5);"></p>
            </div>
            <button onclick="document.getElementById('day-modal-overlay').style.display='none'" style="background:none; border:none; font-size:1.2rem; cursor:pointer; color:rgba(32,0,130,0.4); padding:0.25rem;"><i class="fas fa-times"></i></button>
        </div>
        <div id="day-modal-body" style="padding:1rem 1.5rem 1.5rem;"></div>
    </div>
</div>

<script>
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
    dayEl.textContent = dayNames[d.getDay()];
    if (events.length === 0) {
        body.innerHTML = '<div style="text-align:center; padding:2rem; color:rgba(32,0,130,0.4);"><i class="fas fa-calendar-times" style="font-size:2rem; display:block; margin-bottom:0.5rem;"></i><p>No events on this day.</p></div>';
    } else {
        var html = '';
        events.forEach(function(ev) {
            html += '<div style="display:flex; align-items:center; gap:0.75rem; padding:0.85rem; margin-bottom:0.6rem; background:rgba(32,0,130,0.02); border-radius:10px; border-left:4px solid ' + ev.color + ';">';
            html += '<div style="width:38px; height:38px; border-radius:8px; background:' + ev.color + '; color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0;"><i class="fas ' + ev.icon + '"></i></div>';
            html += '<div style="flex:1; min-width:0;">';
            html += '<div style="font-weight:700; color:var(--text, #222); font-size:0.9rem;">' + ev.title + '</div>';
            html += '<div style="font-size:0.8rem; color:rgba(32,0,130,0.5); margin-top:0.15rem;">' + ev.time + ' \u2022 ' + ev.type + '</div>';
            if (ev.meta) html += '<div style="font-size:0.78rem; color:rgba(32,0,130,0.4); margin-top:0.1rem;">' + ev.meta + '</div>';
            html += '</div>';
            if (ev.is_user_event) {
                html += '<div style="display:flex;gap:0.3rem;flex-shrink:0;">';
                html += '<button onclick="editUserEvent(' + ev.event_id + ',\'' + ev.date + '\')" style="padding:0.3rem 0.5rem;border:1px solid rgba(32,0,130,0.15);border-radius:6px;background:transparent;color:var(--primary,#320082);cursor:pointer;font-size:0.75rem;"><i class="fas fa-pen"></i></button>';
                html += '<button onclick="deleteUserEvent(' + ev.event_id + ')" style="padding:0.3rem 0.5rem;border:1px solid rgba(239,68,68,0.2);border-radius:6px;background:transparent;color:#ef4444;cursor:pointer;font-size:0.75rem;"><i class="fas fa-trash"></i></button>';
                html += '</div>';
            }
            html += '</div>';
        });
        body.innerHTML = html;
    }
    overlay.style.display = 'flex';
}
document.getElementById('day-modal-overlay').addEventListener('click', function(e) { if (e.target.id === 'day-modal-overlay') this.style.display = 'none'; });

// --- Event Create/Edit Modal ---
function openEventModal(date, eventId) {
    var overlay = document.getElementById('event-modal-overlay');
    var titleEl = document.getElementById('event-modal-title');
    var form = document.getElementById('event-form');
    form.reset();
    document.getElementById('event-form-id').value = '';
    if (date) form.querySelector('[name=event_date]').value = date;
    if (eventId) {
        titleEl.textContent = 'Edit Event';
        document.getElementById('event-form-submit').innerHTML = '<i class="fas fa-save"></i> Save Changes';
        fetch('pages/instructor/ajax/get-calendar-events.php?start=' + date + '&end=' + date)
        .then(function(r){return r.json()}).then(function(data) {
            var ev = (data.events || []).find(function(e) { return e.id == eventId; });
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
        titleEl.textContent = 'New Event';
        document.getElementById('event-form-submit').innerHTML = '<i class="fas fa-save"></i> Create Event';
    }
    overlay.style.display = 'flex';
}
function editUserEvent(eventId, date) {
    document.getElementById('day-modal-overlay').style.display = 'none';
    openEventModal(date, eventId);
}
function deleteUserEvent(eventId) {
    openConfirmModal('Are you sure you want to delete this event?', function() {
        var fd = new FormData();
        fd.append('event_id', eventId);
        fetch('pages/instructor/ajax/delete-calendar-event.php', {method:'POST', body:fd})
        .then(function(r){return r.json()}).then(function(data) {
            if (data.success) { if (window.showToast) window.showToast('Event deleted', 'success'); setTimeout(function(){ location.reload(); }, 800); }
            else { if (window.showToast) window.showToast(data.error || 'Failed to delete', 'error'); }
        });
    });
}
document.getElementById('event-form').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = document.getElementById('event-form');
    var fd = new FormData(form);
    var isEdit = fd.get('event_id') !== '';
    var url = isEdit ? 'pages/instructor/ajax/edit-calendar-event.php' : 'pages/instructor/ajax/add-calendar-event.php';
    var btn = form.querySelector('button[type=submit]');
    btn.disabled = true;
    fetch(url, {method:'POST', body:fd})
    .then(function(r){return r.json()}).then(function(data) {
        if (data.success) { if (window.showToast) window.showToast(isEdit ? 'Event updated' : 'Event created', 'success'); setTimeout(function(){ location.reload(); }, 800); }
        else { if (window.showToast) window.showToast(data.error || 'Failed', 'error'); }
    }).finally(function() { btn.disabled = false; });
});
document.getElementById('event-modal-overlay').addEventListener('click', function(e) {
    if (e.target.id === 'event-modal-overlay') this.style.display = 'none';
});

// --- Confirm Modal ---
var confirmCallback = null;
function openConfirmModal(msg, onConfirm) {
    document.getElementById('confirm-modal-message').textContent = msg;
    document.getElementById('confirm-modal-overlay').style.display = 'flex';
    confirmCallback = onConfirm;
}
document.getElementById('confirm-modal-ok').addEventListener('click', function() {
    document.getElementById('confirm-modal-overlay').style.display = 'none';
    if (confirmCallback) confirmCallback();
});
document.getElementById('confirm-modal-cancel').addEventListener('click', function() {
    document.getElementById('confirm-modal-overlay').style.display = 'none';
    confirmCallback = null;
});
document.getElementById('confirm-modal-overlay').addEventListener('click', function(e) {
    if (e.target.id === 'confirm-modal-overlay') this.style.display = 'none';
});
</script>
