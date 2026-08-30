<?php
include_once __DIR__ . '/../../classes/Employee.php';
require_once dirname(__DIR__, 4) . '/database/db.php';

$employeeClass = new Employee();
$instructorId = (int) ($employeeClass->getEmployeeId() ?? 0);

$courses = [];
$learners = [];

try {
    $pdo = (new Database())->getConnection();

    // Courses by this instructor
    $stmt = $pdo->prepare("SELECT id, title FROM ld_course WHERE instructor_id = :iid ORDER BY title ASC");
    $stmt->execute([':iid' => $instructorId]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Learners enrolled in instructor's courses
    $courseIds = array_column($courses, 'id');
    if (!empty($courseIds)) {
        $ph = implode(',', array_fill(0, count($courseIds), '?'));
        $stmt = $pdo->prepare("
            SELECT DISTINCT e.learner_id, CONCAT(emp.first_name, ' ', emp.last_name) AS name, emp.email,
                COUNT(DISTINCT e.course_id) AS course_count
            FROM ld_enrollment e
            JOIN em_employees emp ON emp.employee_id = e.learner_id
            WHERE e.course_id IN ($ph)
            GROUP BY e.learner_id, emp.first_name, emp.last_name, emp.email
            ORDER BY name ASC
        ");
        $stmt->execute($courseIds);
        $learners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    $courses = [];
    $learners = [];
}
?>
<div class="module-content">
    <div class="toolbar">
        <div class="toolbar-actions" style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
            
            <h2 style="margin:0; font-size:1.2rem; color:var(--text);"> Learner Timeline</h2>
        </div>
    </div>

    <!-- Filters -->
    <div style="display:flex; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap; align-items:center;">
        <div style="flex:1; min-width:200px;">
            <label style="font-size:0.8rem; color:#666; display:block; margin-bottom:0.25rem;">Select Learner</label>
            <select id="learner-select" style="width:100%; padding:0.6rem 0.8rem; border:1px solid #ddd; border-radius:8px; font-size:0.9rem;">
                <option value="">— Choose a learner —</option>
                <?php foreach ($learners as $l): ?>
                    <option value="<?= $l['learner_id'] ?>" data-email="<?= htmlspecialchars($l['email']) ?>">
                        <?= htmlspecialchars($l['name']) ?> (<?= $l['course_count'] ?> courses)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="flex:1; min-width:200px;">
            <label style="font-size:0.8rem; color:#666; display:block; margin-bottom:0.25rem;">Filter by Course</label>
            <select id="course-filter" style="width:100%; padding:0.6rem 0.8rem; border:1px solid #ddd; border-radius:8px; font-size:0.9rem;">
                <option value="0">All Courses</option>
                <?php foreach ($courses as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="display:flex; gap:0.5rem; align-items:end;">
            <button onclick="loadTimeline()" style="padding:0.6rem 1.5rem; background:var(--text); color:white; border:none; border-radius:8px; cursor:pointer; font-size:0.9rem; font-weight:600;">Load Timeline</button>
        </div>
    </div>

    <!-- Stats Row -->
    <div id="timeline-stats" style="display:none; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr)); gap:0.75rem; margin-bottom:1.5rem;"></div>

    <!-- Timeline Container -->
    <div id="timeline-container" style="position:relative;">
        <div id="timeline-empty" style="text-align:center; padding:4rem 1rem; color:#999;">
            <div style="font-size:3rem; margin-bottom:1rem;"></div>
            <h3 style="color:#666; margin-bottom:0.5rem;">Select a Learner</h3>
            <p>Choose a learner above to view their complete activity timeline across all courses.</p>
        </div>
        <div id="timeline-line" style="display:none; position:absolute; left:28px; top:0; bottom:0; width:2px; background:linear-gradient(to bottom, var(--primary), var(--primary)); border-radius:1px;"></div>
        <div id="timeline-events"></div>
    </div>
</div>

<style>
.tl-event {
    display: flex;
    gap: 1rem;
    padding: 0.5rem 0;
    position: relative;
    opacity: 0;
    animation: fadeInUp 0.3s ease forwards;
}
.tl-event:nth-child(1) { animation-delay: 0.05s; }
.tl-event:nth-child(2) { animation-delay: 0.1s; }
.tl-event:nth-child(3) { animation-delay: 0.15s; }
.tl-event:nth-child(4) { animation-delay: 0.2s; }
.tl-event:nth-child(5) { animation-delay: 0.25s; }
.tl-event:nth-child(n+6) { animation-delay: 0.3s; }

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.tl-dot {
    width: 56px;
    height: 56px;
    min-width: 56px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    position: relative;
    z-index: 2;
    border: 3px solid white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.tl-card {
    flex: 1;
    background: white;
    border-radius: 12px;
    padding: 1rem 1.25rem;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    border: 1px solid rgba(0,0,0,0.05);
    transition: transform 0.2s, box-shadow 0.2s;
}
.tl-card:hover {
    transform: translateX(4px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.tl-title {
    font-weight: 700;
    font-size: 0.95rem;
    color: var(--text);
    margin-bottom: 0.25rem;
}

.tl-detail {
    font-size: 0.85rem;
    color: #666;
    margin-bottom: 0.25rem;
}

.tl-time {
    font-size: 0.75rem;
    color: #999;
    font-style: italic;
}

.tl-date-separator {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 0 0.25rem 0;
    position: relative;
    z-index: 2;
}

.tl-date-label {
    background: var(--text);
    color: white;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    white-space: nowrap;
    box-shadow: 0 2px 6px rgba(99,102,241,0.3);
}

.tl-date-line {
    flex: 1;
    height: 1px;
    background: rgba(99,102,241,0.2);
}

.stat-card-mini {
    background: white;
    border-radius: 12px;
    padding: 1rem;
    text-align: center;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    border: 1px solid rgba(0,0,0,0.05);
}
.stat-card-mini .stat-label {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.25rem;
}
.stat-card-mini .stat-value {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--text);
}
.stat-card-mini .stat-sub {
    font-size: 0.7rem;
    color: #999;
}
</style>

<script>
const BASE = 'pages/instructor/ajax/';

function formatDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr.replace(' ', 'T'));
    if (isNaN(d)) return dateStr;
    const opts = { month: 'short', day: 'numeric', year: 'numeric' };
    return d.toLocaleDateString('en-US', opts);
}

function formatTime(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr.replace(' ', 'T'));
    if (isNaN(d)) return '';
    return d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
}

function getDateKey(dateStr) {
    if (!dateStr) return 'unknown';
    const d = new Date(dateStr.replace(' ', 'T'));
    if (isNaN(d)) return 'unknown';
    return d.toISOString().split('T')[0];
}

async function loadTimeline() {
    const learnerId = document.getElementById('learner-select').value;
    const courseId = document.getElementById('course-filter').value;

    if (!learnerId) {
        document.getElementById('timeline-empty').style.display = 'block';
        document.getElementById('timeline-line').style.display = 'none';
        document.getElementById('timeline-events').innerHTML = '';
        document.getElementById('timeline-stats').style.display = 'none';
        return;
    }

    document.getElementById('timeline-empty').style.display = 'none';
    document.getElementById('timeline-events').innerHTML = '<div style="text-align:center; padding:2rem; color:#999;">⏳ Loading timeline...</div>';

    try {
        const params = new URLSearchParams({ learner_id: learnerId, course_id: courseId });
        const resp = await fetch(BASE + 'get-learner-timeline.php?' + params.toString());
        const data = await resp.json();

        if (data.error) {
            document.getElementById('timeline-events').innerHTML = '<div style="text-align:center; padding:2rem; color:var(--danger);">❌ ' + data.error + '</div>';
            return;
        }

        renderStats(data);
        renderTimeline(data.timeline);
    } catch (err) {
        document.getElementById('timeline-events').innerHTML = '<div style="text-align:center; padding:2rem; color:var(--danger);">❌ Failed to load: ' + err.message + '</div>';
    }
}

function renderStats(data) {
    const tl = data.timeline || [];
    const statsEl = document.getElementById('timeline-stats');

    const byType = {};
    tl.forEach(e => {
        const t = e.type.replace('_completed', '').replace('_session', '').replace('_attempt', '');
        byType[t] = (byType[t] || 0) + 1;
    });

    const passed = tl.filter(e => e.type === 'quiz_attempt' && e.detail.includes('Passed')).length;
    const failed = tl.filter(e => e.type === 'quiz_attempt' && e.detail.includes('Failed')).length;
    const days = new Set(tl.map(e => getDateKey(e.timestamp))).size;

    statsEl.innerHTML = `
        <div class="stat-card-mini">
            <div class="stat-label" style="color:var(--primary);">Total Events</div>
            <div class="stat-value">${tl.length}</div>
            <div class="stat-sub">across ${days} active days</div>
        </div>
        <div class="stat-card-mini">
            <div class="stat-label" style="color:var(--primary);">Quizzes Passed</div>
            <div class="stat-value" style="color:var(--primary);">${passed}</div>
            <div class="stat-sub">${failed} failed</div>
        </div>
        <div class="stat-card-mini">
            <div class="stat-label" style="color:var(--accent);">Lessons Done</div>
            <div class="stat-value" style="color:var(--accent);">${byType['lesson'] || 0}</div>
            <div class="stat-sub">lessons completed</div>
        </div>
        <div class="stat-card-mini">
            <div class="stat-label" style="color:var(--accent);">Modules Done</div>
            <div class="stat-value" style="color:var(--accent);">${byType['module'] || 0}</div>
            <div class="stat-sub">modules completed</div>
        </div>
        <div class="stat-card-mini">
            <div class="stat-label" style="color:var(--accent);">Evaluations</div>
            <div class="stat-value" style="color:var(--accent);">${byType['evaluation'] || 0}</div>
            <div class="stat-sub">completed</div>
        </div>
    `;
    statsEl.style.display = 'grid';
}

function renderTimeline(events) {
    const container = document.getElementById('timeline-events');
    const line = document.getElementById('timeline-line');

    if (!events || events.length === 0) {
        container.innerHTML = '<div style="text-align:center; padding:3rem; color:#999;"><div style="font-size:2rem;"></div><p>No activity recorded yet.</p></div>';
        line.style.display = 'none';
        return;
    }

    line.style.display = 'block';

    let html = '';
    let lastDateKey = '';

    events.forEach(function (evt, i) {
        const dk = getDateKey(evt.timestamp);
        if (dk !== lastDateKey) {
            lastDateKey = dk;
            html += '<div class="tl-date-separator" style="padding-left:64px;">';
            html += '<span class="tl-date-label">' + formatDate(evt.timestamp) + '</span>';
            html += '<div class="tl-date-line"></div>';
            html += '</div>';
        }

        html += '<div class="tl-event" style="padding-left:0;">';
        html += '<div class="tl-dot" style="background:' + (evt.color || 'var(--primary)') + '; color:white;">' + (evt.icon || '') + '</div>';
        html += '<div class="tl-card">';
        html += '<div class="tl-title">' + escHtml(evt.title) + '</div>';
        html += '<div class="tl-detail">' + escHtml(evt.detail) + '</div>';
        html += '<div class="tl-time"> ' + formatTime(evt.timestamp) + '</div>';
        html += '</div>';
        html += '</div>';
    });

    container.innerHTML = html;
}

function escHtml(s) {
    if (!s) return '';
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

// Auto-load on learner selection
document.getElementById('learner-select').addEventListener('change', loadTimeline);
document.getElementById('course-filter').addEventListener('change', function () {
    if (document.getElementById('learner-select').value) loadTimeline();
});
</script>
