<?php
include_once __DIR__ . '/../../classes/Employee.php';
require_once dirname(__DIR__, 4) . '/database/db.php';

$employeeClass = new Employee();
$instructorId = (int) ($employeeClass->getEmployeeId() ?? 0);

$courses = [];
try {
    $pdo = (new Database())->getConnection();
    $stmt = $pdo->query("SELECT id, title FROM ld_course WHERE status != 'archived' ORDER BY title ASC");
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $courses = [];
}
?>
<div class="module-content">
    <div class="toolbar">
        <div class="toolbar-actions" style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
            
            <h2 style="margin:0; font-size:1.2rem; color:var(--text);"> Manage Learners</h2>
        </div>
    </div>

    <!-- Course Selector -->
    <div style="display:flex; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap; align-items:end;">
        <div style="flex:1; min-width:200px;">
            <label style="font-size:0.8rem; color:#666; display:block; margin-bottom:0.25rem;">Select Course</label>
            <select id="ml-course-select" style="width:100%; padding:0.6rem 0.8rem; border:1px solid #ddd; border-radius:8px; font-size:0.9rem;">
                <option value="">— Choose a course —</option>
                <?php foreach ($courses as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="display:flex; gap:0.5rem;">
            <button onclick="loadRoster()" style="padding:0.6rem 1.2rem; background:var(--text); color:white; border:none; border-radius:8px; cursor:pointer; font-weight:600; font-size:0.85rem;"> Refresh</button>
            <button onclick="exportCSV()" id="btn-export" style="padding:0.6rem 1.2rem; background:#10b981; color:white; border:none; border-radius:8px; cursor:pointer; font-weight:600; font-size:0.85rem; display:none;"> Export CSV</button>
        </div>
    </div>

    <!-- Stats Row -->
    <div id="ml-stats" style="display:none; grid-template-columns:repeat(auto-fit, minmax(120px, 1fr)); gap:0.75rem; margin-bottom:1.5rem;"></div>

    <!-- Tab Switcher -->
    <div id="ml-tabs" style="display:none; gap:0; margin-bottom:0; border-bottom:2px solid #eee;">
        <button class="ml-tab active" onclick="switchTab('import')"> Import Learners</button>
        <button class="ml-tab" onclick="switchTab('roster')"> Course Roster</button>
    </div>

    <!-- Import Tab -->
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
                        <button onclick="bulkEnroll('direct')" id="btn-bulk-direct" style="padding:0.4rem 1rem; background:#10b981; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; font-size:0.85rem;">✅ Enroll Selected</button>
                    </div>
                </div>
                <div id="ml-parsed-list"></div>
            </div>
        </div>
    </div>

    <!-- Roster Tab -->
    <div id="ml-tab-roster" class="ml-tab-content" style="display:none;">
        <div id="ml-roster-empty" style="text-align:center; padding:3rem; color:#999; display:none;">
            <div style="font-size:2rem; margin-bottom:0.5rem;"></div>
            <p>No learners enrolled yet. Use the Import tab to add learners.</p>
        </div>
        <div id="ml-roster-list"></div>
    </div>
</div>

<style>
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

.stat-card-mini {
    background:var(--surface, #fff);
    border-radius: 12px;
    padding: 0.75rem;
    text-align: center;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    border: 1px solid rgba(0,0,0,0.05);
}
.stat-card-mini .stat-label {
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.15rem;
}
.stat-card-mini .stat-value {
    font-size: 1.3rem;
    font-weight: 800;
    color: var(--text);
}
</style>

<script>
const BASE = 'pages/instructor/ajax/';
let parsedLearners = [];
let rosterData = [];

// Initialize CSV dropzone
var csvDrop = document.getElementById('ml-csv-dropzone');
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

// Course selection
document.getElementById('ml-course-select').addEventListener('change', function () {
    const cid = this.value;
    if (cid) {
        document.getElementById('ml-stats').style.display = 'grid';
        document.getElementById('ml-tabs').style.display = 'flex';
        document.getElementById('btn-export').style.display = 'inline-block';
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
    document.getElementById('btn-parse').textContent = '⏳ Parsing...';
    document.getElementById('ml-parse-status').textContent = '';

    try {
        const resp = await fetch(BASE + 'bulk-import-learners.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ text, course_id: courseId })
        });
        const data = await resp.json();

        if (data.error) {
            document.getElementById('ml-parse-status').textContent = '❌ ' + data.error;
            return;
        }

        parsedLearners = data.found || [];
        document.getElementById('ml-found-count').textContent = parsedLearners.length;
        document.getElementById('ml-parse-status').innerHTML = 
            `✅ Found <strong>${data.total_found}</strong> learners` + 
            (data.total_not_found > 0 ? ` · <span style="color:#ef4444;">${data.total_not_found} not found</span>` : '');

        renderParsedList();
        document.getElementById('ml-parsed-results').style.display = 'block';
    } catch (err) {
        document.getElementById('ml-parse-status').textContent = '❌ ' + err.message;
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
        const matchLabel = emp.matched_by === 'email' ? '' : emp.matched_by === 'employee_id' ? '🆔' : '';
        
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
    btn.textContent = '⏳ Processing...';

    try {
        const resp = await fetch(BASE + 'bulk-enroll.php', {
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
        const resp = await fetch(BASE + 'get-course-roster.php?course_id=' + courseId);
        const data = await resp.json();

        if (data.error) {
            document.getElementById('ml-roster-list').innerHTML = '<div style="color:#ef4444; padding:2rem;">❌ ' + data.error + '</div>';
            return;
        }

        rosterData = data.roster || [];
        renderStats(data.stats || {});
        renderRoster();
    } catch (err) {
        document.getElementById('ml-roster-list').innerHTML = '<div style="color:#ef4444; padding:2rem;">❌ ' + err.message + '</div>';
    }
}

function renderStats(stats) {
    const el = document.getElementById('ml-stats');
    el.innerHTML = `
        <div class="stat-card-mini">
            <div class="stat-label" style="color:#6366f1;">Total</div>
            <div class="stat-value">${stats.total || 0}</div>
        </div>
        <div class="stat-card-mini">
            <div class="stat-label" style="color:#3b82f6;">Enrolled</div>
            <div class="stat-value" style="color:#3b82f6;">${stats.enrolled || 0}</div>
        </div>
        <div class="stat-card-mini">
            <div class="stat-label" style="color:#f59e0b;">In Progress</div>
            <div class="stat-value" style="color:#f59e0b;">${stats.in_progress || 0}</div>
        </div>
        <div class="stat-card-mini">
            <div class="stat-label" style="color:#10b981;">Completed</div>
            <div class="stat-value" style="color:#10b981;">${stats.completed || 0}</div>
        </div>
        <div class="stat-card-mini">
            <div class="stat-label" style="color:#8b5cf6;">Invited</div>
            <div class="stat-value" style="color:#8b5cf6;">${stats.invited || 0}</div>
        </div>
    `;
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
        const resp = await fetch(BASE + 'unenroll-learner.php', {
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
    window.location.href = BASE + 'export-enrollment.php?course_id=' + courseId;
}

function esc(s) {
    if (!s) return '';
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}
</script>
