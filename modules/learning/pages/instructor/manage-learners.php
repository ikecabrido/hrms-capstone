<?php
include_once __DIR__ . '/../../classes/Employee.php';
require_once dirname(__DIR__, 4) . '/database/db.php';

$employeeClass = new Employee();
$instructorId = (int) ($employeeClass->getEmployeeId() ?? 0);
$showLearnerImport = empty($hideLearnerImport);

$courses = [];
try {
    $pdo = (new Database())->getConnection();
    $stmt = $pdo->query("SELECT id, title FROM ld_course WHERE status != 'archived' ORDER BY title ASC");
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    DbError::capture($e, 'instructor/manage-learners');
    $courses = [];
}
?>
<div class="module-content">
    <!-- Course Selector -->
    <div style="display:flex; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap; align-items:end;">
        <div style="flex:1; min-width:200px;">
            <label style="font-size:0.8rem; color:#666; display:block; margin-bottom:0.25rem;">Select Course</label>
            <input id="ml-course-search" type="search" list="ml-course-options" placeholder="Search courses by name..." autocomplete="off" aria-label="Search and select a course" style="width:100%; padding:0.6rem 0.8rem; border:1px solid #ddd; border-radius:8px; font-size:0.9rem; box-sizing:border-box;">
            <datalist id="ml-course-options">
                <?php foreach ($courses as $c): ?>
                    <option value="<?= htmlspecialchars($c['title'] . ' (Course #' . $c['id'] . ')', ENT_QUOTES, 'UTF-8') ?>" data-course-id="<?= (int) $c['id'] ?>"></option>
                <?php endforeach; ?>
            </datalist>
            <select id="ml-course-select" aria-hidden="true" tabindex="-1" style="display:none;">
                <option value="">— Choose a course —</option>
                <?php foreach ($courses as $c): ?>
                    <option value="<?= (int) $c['id'] ?>"><?= htmlspecialchars($c['title'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="ml-course-actions" style="display:flex; gap:0.5rem;">
            <button type="button" onclick="loadRoster()" title="Refresh roster" aria-label="Refresh roster" style="padding:0.6rem 1.2rem; background:var(--text); color:white; border:none; border-radius:8px; cursor:pointer; font-weight:600; font-size:0.85rem;"><i class="fas fa-rotate" aria-hidden="true"></i><span class="btn-label">Refresh</span></button>
            <button type="button" onclick="exportCSV()" id="btn-export" title="Export CSV" aria-label="Export CSV" style="padding:0.6rem 1.2rem; background:#10b981; color:white; border:none; border-radius:8px; cursor:pointer; font-weight:600; font-size:0.85rem; display:none;"><i class="fas fa-file-csv" aria-hidden="true"></i><span class="btn-label">Export CSV</span></button>
        </div>
    </div>

    <!-- Stats Row -->
    <div id="ml-stats" class="ml-stats"></div>

    <!-- Tab Switcher -->
    <?php if ($showLearnerImport): ?>
        <div id="ml-tabs" style="display:none; gap:0; margin-bottom:0; border-bottom:2px solid #eee;">
            <button class="ml-tab active" onclick="switchTab('import')" aria-label="Import learners" title="Import learners"><i class="fas fa-file-import" aria-hidden="true"></i> Import Learners</button>
            <button class="ml-tab" onclick="switchTab('roster')" aria-label="Course roster" title="Course roster"><i class="fas fa-clipboard-list" aria-hidden="true"></i> Course Roster</button>
        </div>

        <div id="ml-tab-import" class="ml-tab-content" style="display:none;">
        <div style="background:var(--surface, #fff); border-radius:12px; padding:1.5rem; box-shadow:0 1px 4px rgba(0,0,0,0.06); margin-top:1rem;">
            <h3 style="margin:0 0 0.5rem; color:var(--text); font-size:1rem;">Import Learners</h3>
            <p style="font-size:0.8rem; color:#666; margin:0 0 1rem;">Drop a CSV file below, or paste emails/IDs/names manually.</p>
            
            <div id="ml-csv-dropzone"></div>
            
            <p style="font-size:0.75rem; color:#999; margin:0.75rem 0 0.5rem; text-align:center;">Or paste manually:</p>
            <textarea id="ml-import-text" rows="6" placeholder="user@example.com&#10;1018&#10;Juan Dela Cruz&#10;jane.smith@company.com, 1025, Pedro Santos" style="width:100%; padding:0.8rem; border:1px solid #ddd; border-radius:8px; font-family:monospace; font-size:0.85rem; resize:vertical;"></textarea>
            
            <div style="display:flex; gap:0.5rem; margin-top:0.75rem; align-items:center; flex-wrap:wrap;">
                <button onclick="parseImport()" id="btn-parse" style="padding:0.6rem 1.5rem; background:#6366f1; color:white; border:none; border-radius:8px; cursor:pointer; font-weight:600; font-size:0.85rem;"> Parse & Find</button>
                <span id="ml-parse-status" style="font-size:0.8rem; color:#666;"></span>
            </div>

            <!-- Parsed Results -->
            <div id="ml-parsed-results" style="display:none; margin-top:1.5rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
                    <h4 style="margin:0; color:var(--text);">Found <span id="ml-found-count">0</span> Learners</h4>
                    <div style="display:flex; gap:0.5rem;">
                        <label style="display:flex; align-items:center; gap:0.3rem; font-size:0.8rem; cursor:pointer;">
                            <input type="checkbox" id="ml-select-all" onchange="toggleSelectAll(this.checked)" checked> Select All
                        </label>
                        <button onclick="bulkEnroll('invite')" id="btn-bulk-invite" style="padding:0.4rem 1rem; background:#f59e0b; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; font-size:0.8rem;"> Invite Selected</button>
                        <button onclick="bulkEnroll('direct')" id="btn-bulk-direct" style="padding:0.4rem 1rem; background:#10b981; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; font-size:0.85rem;">Enroll Selected</button>
                    </div>
                </div>
                <div id="ml-parsed-list"></div>
            </div>
        </div>
        </div>
    <?php endif; ?>

    <!-- Roster Tab -->
    <div id="ml-tab-roster" class="ml-tab-content" style="display:<?= $showLearnerImport ? 'none' : 'block' ?>;">
        <div id="ml-roster-empty" style="text-align:center; padding:3rem; color:#999; display:none;">
            <div style="font-size:2rem; margin-bottom:0.5rem;"></div>
            <p>No learners enrolled yet.</p>
        </div>
        <div id="ml-roster-list"></div>
    </div>
</div>

<style>
.ml-course-actions button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
}

@media (max-width: 768px) {
    .ml-course-actions {
        gap: 0.45rem !important;
    }

    .ml-course-actions button {
        flex: 0 0 44px;
        width: 44px;
        height: 44px;
        padding: 0 !important;
    }

    .ml-course-actions .btn-label {
        display: none;
    }
}

.ml-tab {
    padding: 0.7rem 1.25rem;
    border: none;
    background: transparent;
    font-size: 0.85rem;
    font-weight: 600;
    color: #999;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s;
}
.ml-tab.active {
    color: var(--text);
    border-bottom-color: var(--text);
}
.ml-tab:hover:not(.active) {
    color: #666;
}

.ml-learner-card {
    background:var(--surface, #fff);
    border-radius: 10px;
    padding: 0.8rem 1rem;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    border: 1px solid rgba(0,0,0,0.05);
    transition: transform 0.15s, box-shadow 0.15s;
}
.ml-learner-card:hover {
    transform: translateX(2px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.ml-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.8rem;
    color: white;
    flex-shrink: 0;
}

.ml-name {
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--text);
}
.ml-email {
    font-size: 0.75rem;
    color: #999;
}
.ml-badge {
    padding: 0.15rem 0.5rem;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
}
.ml-progress-bar {
    width: 80px;
    height: 6px;
    background: #eee;
    border-radius: 3px;
    overflow: hidden;
    flex-shrink: 0;
}
.ml-progress-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.3s;
}

.ml-stats {
    display: none;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}
.ml-stat-card {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-width: 0;
    padding: 0.85rem 0.95rem;
    border: 1px solid rgba(32, 0, 130, 0.10);
    border-radius: 14px;
    background: var(--surface, #fff);
    box-shadow: 0 2px 8px rgba(32, 0, 130, 0.04);
}
.ml-stat-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    flex-shrink: 0;
    border-radius: 11px;
    font-size: 0.9rem;
}
.ml-stat-icon.tone-violet { background: rgba(99, 102, 241, 0.12); color: #6366f1; }
.ml-stat-icon.tone-blue { background: rgba(59, 130, 246, 0.12); color: #3b82f6; }
.ml-stat-icon.tone-amber { background: rgba(245, 158, 11, 0.14); color: #d97706; }
.ml-stat-icon.tone-emerald { background: rgba(16, 185, 129, 0.12); color: #059669; }
.ml-stat-icon.tone-purple { background: rgba(139, 92, 246, 0.12); color: #8b5cf6; }
.ml-stat-body {
    display: flex;
    flex-direction: column;
    gap: 0.05rem;
    min-width: 0;
}
.ml-stat-label {
    overflow: hidden;
    color: rgba(32, 0, 130, 0.52);
    font-size: 0.63rem;
    font-weight: 800;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    white-space: nowrap;
    text-overflow: ellipsis;
}
.ml-stat-value {
    color: var(--text);
    font-size: 1.15rem;
    font-weight: 800;
    line-height: 1.1;
}
</style>

<script>
var MANAGE_LEARNERS_AJAX_BASE = 'pages/instructor/ajax/';
var parsedLearners = [];
var rosterData = [];
var showLearnerImport = <?= $showLearnerImport ? 'true' : 'false' ?>;

// Initialize CSV dropzone
var csvDrop = showLearnerImport ? document.getElementById('ml-csv-dropzone') : null;
if (csvDrop && typeof DropZone !== 'undefined') {
    DropZone.init(csvDrop, {
        accept: '.csv,.txt',
        label: 'Drop a CSV file here',
        fieldName: 'file',
        onFileSelected: function(file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('ml-import-text').value = e.target.result;
            };
            reader.readAsText(file);
        }
    });
}

function switchTab(tab) {
    document.querySelectorAll('.ml-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.ml-tab-content').forEach(c => c.style.display = 'none');
    
    event.target.classList.add('active');
    document.getElementById('ml-tab-' + tab).style.display = 'block';
}

function updateSelectedCourse() {
    const searchInput = document.getElementById('ml-course-search');
    const courseSelect = document.getElementById('ml-course-select');
    const selectedOption = Array.from(document.getElementById('ml-course-options').options)
        .find(function(option) { return option.value === searchInput.value; });
    courseSelect.value = selectedOption ? selectedOption.dataset.courseId : '';
    courseSelect.dispatchEvent(new Event('change', { bubbles: true }));
}

// Course selection
document.getElementById('ml-course-search').addEventListener('input', updateSelectedCourse);
document.getElementById('ml-course-search').addEventListener('change', updateSelectedCourse);
document.getElementById('ml-course-select').addEventListener('change', function () {
    const cid = this.value;
    if (cid) {
        document.getElementById('ml-stats').style.display = 'grid';
        if (showLearnerImport) {
            document.getElementById('ml-tabs').style.display = 'flex';
        }
        document.getElementById('btn-export').style.display = 'inline-flex';
        loadRoster();
    } else {
        document.getElementById('ml-stats').style.display = 'none';
        document.getElementById('ml-tabs').style.display = 'none';
        document.getElementById('btn-export').style.display = 'none';
    }
});

async function parseImport() {
    const text = document.getElementById('ml-import-text').value.trim();
    const courseId = document.getElementById('ml-course-select').value;
    
    if (!text) {
        document.getElementById('ml-parse-status').textContent = 'Please paste some learner emails, IDs, or names.';
        return;
    }
    if (!courseId) {
        document.getElementById('ml-parse-status').textContent = 'Please select a course first.';
        return;
    }

    document.getElementById('btn-parse').disabled = true;
    document.getElementById('btn-parse').textContent = 'Parsing...';
    document.getElementById('ml-parse-status').textContent = '';

    try {
        const resp = await fetch(MANAGE_LEARNERS_AJAX_BASE + 'bulk-import-learners.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ text, course_id: courseId })
        });
        const data = await resp.json();

        if (data.error) {
            document.getElementById('ml-parse-status').textContent = data.error;
            return;
        }

        parsedLearners = data.found || [];
        document.getElementById('ml-found-count').textContent = parsedLearners.length;
        document.getElementById('ml-parse-status').innerHTML = 
            `Found <strong>${data.total_found}</strong> learners` +
            (data.total_not_found > 0 ? ` · <span style="color:#ef4444;">${data.total_not_found} not found</span>` : '');

        renderParsedList();
        document.getElementById('ml-parsed-results').style.display = 'block';
    } catch (err) {
        document.getElementById('ml-parse-status').textContent = err.message;
    } finally {
        document.getElementById('btn-parse').disabled = false;
        document.getElementById('btn-parse').textContent = ' Parse & Find';
    }
}

function renderParsedList() {
    const colors = ['#6366f1', '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];
    const list = document.getElementById('ml-parsed-list');
    
    list.innerHTML = parsedLearners.map(function (emp, i) {
        const initials = (emp.first_name[0] || '') + (emp.last_name[0] || '');
        const color = colors[i % colors.length];
        const matchLabel = emp.matched_by === 'employee_id' ? 'Employee ID match' : '';
        
        return `<div class="ml-learner-card">
            <input type="checkbox" class="ml-check" value="${emp.employee_id}" checked style="flex-shrink:0;">
            <div class="ml-avatar" style="background:${color};">${initials}</div>
            <div style="flex:1; min-width:0;">
                <div class="ml-name">${esc(emp.first_name)} ${esc(emp.last_name)}</div>
                <div class="ml-email">${esc(emp.email)} ${matchLabel} ${esc(emp.matched_by)}</div>
            </div>
        </div>`;
    }).join('');

    if (parsedLearners.length === 0) {
        list.innerHTML = '<div style="text-align:center; padding:2rem; color:#999;">No matching employees found.</div>';
    }
}

function toggleSelectAll(checked) {
    document.querySelectorAll('.ml-check').forEach(cb => cb.checked = checked);
}

async function bulkEnroll(mode) {
    const courseId = document.getElementById('ml-course-select').value;
    const selected = Array.from(document.querySelectorAll('.ml-check:checked')).map(cb => parseInt(cb.value));

    if (selected.length === 0) {
        if (window.showToast) window.showToast('Please select at least one learner', 'error');
        return;
    }

    const btn = mode === 'invite' ? document.getElementById('btn-bulk-invite') : document.getElementById('btn-bulk-direct');
    const origText = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Processing...';

    try {
        const resp = await fetch(MANAGE_LEARNERS_AJAX_BASE + 'bulk-enroll.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ learner_ids: selected, course_id: courseId, mode })
        });
        const data = await resp.json();

        if (data.success) {
            if (window.showToast) window.showToast(data.message || 'Learners enrolled', 'success');
            loadRoster();
            document.querySelectorAll('.ml-tab')[0].classList.remove('active');
            document.querySelectorAll('.ml-tab')[1].classList.add('active');
            document.getElementById('ml-tab-import').style.display = 'none';
            document.getElementById('ml-tab-roster').style.display = 'block';
        } else {
            if (window.showToast) window.showToast(data.message || 'Enrollment failed', 'error');
        }
    } catch (err) {
        if (window.showToast) window.showToast('Failed: ' + err.message, 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = origText;
    }
}

async function loadRoster() {
    const courseId = document.getElementById('ml-course-select').value;
    if (!courseId) return;

    try {
        const resp = await fetch(MANAGE_LEARNERS_AJAX_BASE + 'get-course-roster.php?course_id=' + courseId);
        const data = await resp.json();

        if (data.error) {
            document.getElementById('ml-roster-list').innerHTML = '<div style="color:#ef4444; padding:2rem;">' + esc(data.error) + '</div>';
            return;
        }

        rosterData = data.roster || [];
        renderStats(data.stats || {});
        renderRoster();
    } catch (err) {
        document.getElementById('ml-roster-list').innerHTML = '<div style="color:#ef4444; padding:2rem;">' + esc(err.message) + '</div>';
    }
}

function renderStats(stats) {
    const el = document.getElementById('ml-stats');
    const cards = [
        { label: 'Total', key: 'total', icon: 'fa-users', tone: 'tone-violet' },
        { label: 'Enrolled', key: 'enrolled', icon: 'fa-user-check', tone: 'tone-blue' },
        { label: 'In Progress', key: 'in_progress', icon: 'fa-spinner', tone: 'tone-amber' },
        { label: 'Completed', key: 'completed', icon: 'fa-circle-check', tone: 'tone-emerald' },
        { label: 'Invited', key: 'invited', icon: 'fa-envelope', tone: 'tone-purple' }
    ];
    el.innerHTML = cards.map(function (card) {
        return '<div class="ml-stat-card">' +
            '<span class="ml-stat-icon ' + card.tone + '"><i class="fas ' + card.icon + '" aria-hidden="true"></i></span>' +
            '<span class="ml-stat-body">' +
                '<span class="ml-stat-label">' + card.label + '</span>' +
                '<span class="ml-stat-value">' + Number(stats[card.key] || 0) + '</span>' +
            '</span>' +
        '</div>';
    }).join('');
}

function renderRoster() {
    const list = document.getElementById('ml-roster-list');
    const empty = document.getElementById('ml-roster-empty');

    if (rosterData.length === 0) {
        list.innerHTML = '';
        empty.style.display = 'block';
        return;
    }

    empty.style.display = 'none';

    const statusColors = {
        'enrolled': '#3b82f6',
        'in_progress': '#f59e0b',
        'completed': '#10b981',
        'invited': '#8b5cf6',
        'withdrawn': '#ef4444',
    };

    const avatarColors = ['#6366f1', '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];

    list.innerHTML = rosterData.map(function (r, i) {
        const initials = (r.first_name[0] || '') + (r.last_name[0] || '');
        const color = avatarColors[i % avatarColors.length];
        const statusColor = statusColors[r.status] || '#666';
        const pct = r.total_items > 0 ? Math.round((r.completed_items / r.total_items) * 100) : 0;
        const score = r.final_score !== null ? r.final_score + '%' : '—';
        const enrolledDate = r.enrolled_at ? new Date(r.enrolled_at.replace(' ', 'T')).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—';

        return `<div class="ml-learner-card">
            <div class="ml-avatar" style="background:${color};">${initials}</div>
            <div style="flex:1; min-width:0;">
                <div class="ml-name">${esc(r.first_name)} ${esc(r.last_name)}</div>
                <div class="ml-email">${esc(r.email)} · Enrolled ${enrolledDate}</div>
            </div>
            <div class="ml-progress-bar" title="${pct}% complete">
                <div class="ml-progress-fill" style="width:${pct}%; background:${statusColor};"></div>
            </div>
            <span style="font-size:0.75rem; color:#666; min-width:35px; text-align:right;">${pct}%</span>
            <span class="ml-badge" style="background:${statusColor}20; color:${statusColor};">${r.status.replace('_', ' ')}</span>
            <span style="font-size:0.75rem; color:#666; min-width:35px; text-align:right;" title="Final score">${score}</span>
            <button onclick="removeLearner(${r.enrollment_id})" title="Withdraw learner" style="background:none; border:none; cursor:pointer; color:#ef4444; font-size:1rem; padding:0.25rem;"></button>
        </div>`;
    }).join('');
}

async function removeLearner(enrollmentId) {
    if (!confirm('Are you sure you want to withdraw this learner from the course?')) return;

    try {
        const resp = await fetch(MANAGE_LEARNERS_AJAX_BASE + 'unenroll-learner.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ enrollment_id: enrollmentId })
        });
        const data = await resp.json();

        if (data.success) {
            loadRoster();
        } else {
            alert(data.message);
        }
    } catch (err) {
        alert('Failed: ' + err.message);
    }
}

function exportCSV() {
    const courseId = document.getElementById('ml-course-select').value;
    if (!courseId) return;
    window.location.href = MANAGE_LEARNERS_AJAX_BASE + 'export-enrollment.php?course_id=' + courseId;
}

function esc(s) {
    if (!s) return '';
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}
</script>
