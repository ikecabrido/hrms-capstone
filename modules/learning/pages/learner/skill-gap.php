<?php
include_once __DIR__ . '/../../classes/Employee.php';
$employeeClass = new Employee();
$learnerName = $employeeClass->getEmployeeName();
$learnerId = (int) ($employeeClass->getEmployeeId() ?? 0);

$completedCount = 0;
$enrolledCount = 0;
$completedCourses = [];
try {
    require_once dirname(__DIR__, 4) . '/database/db.php';
    $database = new Database();
    $pdo = $database->getConnection();

    $stmt = $pdo->prepare("
        SELECT e.course_id, c.title, e.status, e.completed_at
        FROM ld_enrollment e
        JOIN ld_course c ON c.id = e.course_id
        WHERE e.learner_id = :lid
        ORDER BY e.completed_at DESC
    ");
    $stmt->execute([':lid' => $learnerId]);
    $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($enrollments as $e) {
        if ($e['status'] === 'completed') {
            $completedCount++;
            $completedCourses[] = $e;
        } else {
            $enrolledCount++;
        }
    }

    // Fetch certificate IDs for completed courses
    $certMap = [];
    if ($completedCount > 0) {
        $courseIds = array_column($completedCourses, 'course_id');
        $cStmt = $pdo->prepare("SELECT id, course_id FROM ld_certificate WHERE learner_id = :lid AND course_id IN (" . implode(',', array_map('intval', $courseIds)) . ")");
        $cStmt->execute([':lid' => $learnerId]);
        foreach ($cStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $certMap[(int)$row['course_id']] = (int)$row['id'];
        }
    }
} catch (Throwable $e) {
    // silently fail
}
?>

<style>
/* ---- Skill Gap Overview ---- */
.sg-header { margin-bottom: 1.5rem; }
.sg-header h2 { margin: 0 0 0.35rem; font-size: 1.35rem; font-weight: 800; color: var(--text); }
.sg-header p { margin: 0; font-size: 0.9rem; color: var(--muted); }

/* Summary cards */
.sg-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
.sg-summary-card {
    padding: 1.1rem;
    border-radius: 14px;
    text-align: center;
    border: 1px solid rgba(32,0,130,0.08);
    background: var(--surface, #fff);
    transition: transform 0.2s, box-shadow 0.2s;
}
.sg-summary-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.06); }
.sg-summary-card .sg-icon { width: 40px; height: 40px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; font-size: 1rem; margin-bottom: 0.5rem; }
.sg-summary-card .sg-value { font-size: 1.5rem; font-weight: 800; line-height: 1; margin-bottom: 0.2rem; }
.sg-summary-card .sg-label { font-size: 0.72rem; color: var(--muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; }

/* Training Progress */
.sg-progress-section {
    background: var(--surface, #fff);
    border: 1px solid rgba(32,0,130,0.08);
    border-radius: 14px;
    padding: 1.25rem;
    margin-bottom: 1.25rem;
}
.sg-progress-section h3 {
    margin: 0 0 0.75rem;
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--primary);
    display: flex;
    align-items: center;
    gap: 0.4rem;
}
.sg-progress-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.6rem 0;
    border-bottom: 1px solid rgba(32,0,130,0.06);
}
.sg-progress-row:last-child { border-bottom: none; }
.sg-progress-row .sg-pr-label { font-size: 0.85rem; color: var(--text); font-weight: 600; }
.sg-progress-row .sg-pr-value { font-size: 1.1rem; font-weight: 800; }

.sg-course-list { margin-top: 0.75rem; }
.sg-course-item {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.6rem 0.85rem;
    border-radius: 10px;
    margin-bottom: 0.4rem;
    background: rgba(32,0,130,0.03);
    border: 1px solid rgba(32,0,130,0.06);
    text-decoration: none;
    color: inherit;
    transition: background 0.15s, transform 0.15s, box-shadow 0.15s;
    cursor: pointer;
}
.sg-course-item:hover {
    background: rgba(32,0,130,0.06);
    transform: translateX(2px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.sg-course-item .sg-ci-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; background: #10b981; }
.sg-course-item .sg-ci-name { flex: 1; font-size: 0.85rem; font-weight: 600; color: var(--text); }
.sg-course-item .sg-ci-arrow { font-size: 0.75rem; color: var(--primary); opacity: 0.6; transition: opacity 0.15s; }
.sg-course-item:hover .sg-ci-arrow { opacity: 1; }
.sg-course-item .sg-ci-status {
    padding: 0.15rem 0.5rem;
    border-radius: 999px;
    font-size: 0.68rem;
    font-weight: 700;
    flex-shrink: 0;
}
.sg-ci-status.completed { background: rgba(16,185,129,0.12); color: #059669; }
.sg-ci-status.no-cert { background: rgba(245,158,11,0.12); color: #d97706; }

/* Skill list */
.sg-section-title { font-size: 0.95rem; font-weight: 700; color: var(--text); margin: 1.25rem 0 0.6rem; display: flex; align-items: center; gap: 0.4rem; }
.sg-skill-list { display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 1rem; }
.sg-skill-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.7rem 1rem;
    border-radius: 10px;
    background: var(--surface, #fff);
    border: 1px solid rgba(32,0,130,0.06);
    transition: transform 0.15s;
}
.sg-skill-row:hover { transform: translateX(2px); }
.sg-skill-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.sg-skill-dot.gap { background: #f59e0b; }
.sg-skill-dot.acquired { background: #10b981; }
.sg-skill-name { flex: 1; font-size: 0.88rem; font-weight: 600; color: var(--text); }
.sg-badge { padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.68rem; font-weight: 700; white-space: nowrap; flex-shrink: 0; }
.sg-badge.gap { background: rgba(245,158,11,0.12); color: #d97706; }
.sg-badge.acquired { background: rgba(16,185,129,0.12); color: #059669; }

.sg-empty { text-align: center; padding: 2rem 1rem; color: var(--muted); }
.sg-empty i { font-size: 2rem; color: var(--border); margin-bottom: 0.75rem; display: block; }
.sg-empty h4 { margin: 0 0 0.4rem; color: var(--text); }
.sg-empty p { margin: 0; font-size: 0.85rem; }
.sg-loading { text-align: center; padding: 3rem 1rem; color: var(--muted); }
.sg-loading i { font-size: 1.5rem; margin-bottom: 0.75rem; display: block; animation: sg-spin 1s linear infinite; }
@keyframes sg-spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

/* Request Training */
.sg-request-btn {
    display: inline-flex; align-items: center; gap: 0.4rem;
    padding: 0.35rem 0.75rem; border-radius: 8px;
    font-size: 0.75rem; font-weight: 700;
    background: rgba(99,102,241,0.1); color: #6366f1;
    border: 1px solid rgba(99,102,241,0.2);
    cursor: pointer; transition: all 0.2s;
    white-space: nowrap; flex-shrink: 0;
}
.sg-request-btn:hover { background: rgba(99,102,241,0.2); transform: translateY(-1px); }
.sg-request-btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
.sg-request-btn.sent { background: rgba(16,185,129,0.1); color: #059669; border-color: rgba(16,185,129,0.2); }

.sg-gap-courses { margin-top: 0.5rem; padding-left: 1.5rem; }
.sg-gap-course-link {
    display: flex; align-items: center; gap: 0.5rem;
    padding: 0.4rem 0.7rem; border-radius: 8px;
    font-size: 0.8rem; color: var(--text); text-decoration: none;
    background: rgba(32,0,130,0.03); border: 1px solid rgba(32,0,130,0.06);
    margin-bottom: 0.3rem; transition: background 0.15s;
}
.sg-gap-course-link:hover { background: rgba(32,0,130,0.08); }
.sg-gap-course-link i { font-size: 0.7rem; color: var(--primary); }

.sg-request-modal-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,0.4);
    display: flex; align-items: center; justify-content: center;
    z-index: 9999; opacity: 0; pointer-events: none; transition: opacity 0.2s;
}
.sg-request-modal-overlay.active { opacity: 1; pointer-events: auto; }
.sg-request-modal {
    background: var(--surface, #fff); border-radius: 14px;
    padding: 1.75rem; width: 90%; max-width: 420px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.15);
    transform: translateY(20px); transition: transform 0.2s;
}
.sg-request-modal-overlay.active .sg-request-modal { transform: translateY(0); }
.sg-request-modal h3 { margin: 0 0 0.5rem; font-size: 1.05rem; color: var(--text); }
.sg-request-modal p { margin: 0 0 1rem; font-size: 0.85rem; color: var(--muted); }
.sg-request-modal textarea {
    width: 100%; padding: 0.7rem; border: 1.5px solid rgba(32,0,130,0.1);
    border-radius: 10px; font-size: 0.88rem; font-family: inherit;
    resize: vertical; min-height: 80px; box-sizing: border-box;
    background: rgba(32,0,130,0.03); outline: none;
}
.sg-request-modal textarea:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(32,0,130,0.08); }
.sg-request-modal .sg-rm-actions { display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1rem; }
.sg-request-modal .sg-rm-btn {
    padding: 0.55rem 1.2rem; border-radius: 8px; font-size: 0.85rem; font-weight: 600;
    border: none; cursor: pointer; transition: all 0.15s;
}
.sg-request-modal .sg-rm-cancel { background: rgba(32,0,130,0.06); color: var(--text); }
.sg-request-modal .sg-rm-cancel:hover { background: rgba(32,0,130,0.12); }
.sg-request-modal .sg-rm-submit { background: var(--primary); color: #fff; }
.sg-request-modal .sg-rm-submit:hover { opacity: 0.9; }

/* Dark mode */
[data-theme="dark"] .sg-summary-card,
[data-theme="dark"] .sg-progress-section,
[data-theme="dark"] .sg-skill-row { background: var(--surface) !important; border-color: rgba(51,65,85,0.5); }

@media (max-width: 768px) {
    .sg-summary { grid-template-columns: repeat(2, 1fr); }
}
</style>

<div class="module-content">
    <div class="sg-header">
        <h2><i class="fas fa-chart-line" style="color:var(--primary); margin-right:0.4rem;"></i> Skills Gap Analysis</h2>
        <p>Automatic analysis of your skill gaps based on completed courses and training performance.</p>
    </div>

    <!-- Summary Cards -->
    <div class="sg-summary" id="sg-summary">
        <div class="sg-summary-card">
            <div class="sg-icon" style="background:rgba(32,0,130,0.08); color:var(--primary);"><i class="fas fa-puzzle-piece"></i></div>
            <div class="sg-value" id="sg-total" style="color:var(--primary);">—</div>
            <div class="sg-label">Total Skills</div>
        </div>
        <div class="sg-summary-card">
            <div class="sg-icon" style="background:rgba(16,185,129,0.08); color:#10b981;"><i class="fas fa-check-circle"></i></div>
            <div class="sg-value" id="sg-acquired" style="color:#10b981;">—</div>
            <div class="sg-label">Acquired</div>
        </div>
        <div class="sg-summary-card">
            <div class="sg-icon" style="background:rgba(245,158,11,0.08); color:#f59e0b;"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="sg-value" id="sg-gaps" style="color:#f59e0b;">—</div>
            <div class="sg-label">Gaps</div>
        </div>
        <div class="sg-summary-card">
            <div class="sg-icon" style="background:rgba(99,102,241,0.08); color:#6366f1;"><i class="fas fa-percent"></i></div>
            <div class="sg-value" id="sg-pct" style="color:#6366f1;">—</div>
            <div class="sg-label">Completion</div>
        </div>
    </div>

    <!-- Training Progress Summary -->
    <div class="sg-progress-section">
        <h3><i class="fas fa-clipboard-list"></i> Your Training Progress Summary</h3>
        <div class="sg-progress-row">
            <span class="sg-pr-label">Completed Courses:</span>
            <span class="sg-pr-value" style="color:#10b981;"><?= $completedCount ?></span>
        </div>
        <div class="sg-progress-row">
            <span class="sg-pr-label">In Progress:</span>
            <span class="sg-pr-value" style="color:#3b82f6;"><?= $enrolledCount ?></span>
        </div>
        <div class="sg-progress-row">
            <span class="sg-pr-label">Skills Acquired:</span>
            <span class="sg-pr-value" style="color:var(--primary);" id="sg-skills-acquired">—</span>
        </div>
        <div class="sg-progress-row">
            <span class="sg-pr-label">Skills Remaining:</span>
            <span class="sg-pr-value" style="color:#f59e0b;" id="sg-skills-remaining">—</span>
        </div>

        <?php if (!empty($completedCourses)): ?>
        <div style="margin-top:0.75rem; padding-top:0.75rem; border-top:1px solid rgba(32,0,130,0.06);">
            <div style="font-size:0.78rem; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:0.5rem;">Recently Completed</div>
            <div class="sg-course-list">
                <?php foreach (array_slice($completedCourses, 0, 5) as $course):
                    $certId = $certMap[(int)$course['course_id']] ?? 0;
                    if ($certId > 0) {
                        $link = '?page=learner/result-subpage/certificate-print&back=learner/skill-gap&certificate_id=' . $certId;
                    } else {
                        $link = '?page=learner/result-subpage/certificate';
                    }
                ?>
                <a href="<?= htmlspecialchars($link) ?>" class="sg-course-item">
                    <span class="sg-ci-dot"></span>
                    <span class="sg-ci-name"><?= htmlspecialchars($course['title']) ?></span>
                    <?php if ($certId > 0): ?>
                        <span class="sg-ci-status completed"><i class="fas fa-certificate" style="margin-right:0.2rem;"></i>View Certificate</span>
                        <span class="sg-ci-arrow"><i class="fas fa-chevron-right"></i></span>
                    <?php else: ?>
                        <span class="sg-ci-status no-cert">No Certificate</span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Skill List -->
    <div id="sg-content">
        <div class="sg-loading"><i class="fas fa-spinner"></i><p>Loading skill data...</p></div>
    </div>
</div>

<!-- Request Training Modal -->
<div class="sg-request-modal-overlay" id="sg-request-overlay">
    <div class="sg-request-modal">
        <h3><i class="fas fa-paper-plane" style="color:var(--primary);margin-right:0.4rem;"></i> Request Training</h3>
        <p>Request a new course for <strong id="sg-rm-skill-name"></strong>. An instructor will review and create training material.</p>
        <textarea id="sg-rm-message" placeholder="Optional: Describe what you'd like to learn or why this skill is important..."></textarea>
        <div class="sg-rm-actions">
            <button class="sg-rm-btn sg-rm-cancel" id="sg-rm-cancel">Cancel</button>
            <button class="sg-rm-btn sg-rm-submit" id="sg-rm-submit">Submit Request</button>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    var contentEl = document.getElementById('sg-content');

    function esc(s) { var d = document.createElement('div'); d.appendChild(document.createTextNode(s || '')); return d.innerHTML; }

    fetch('pages/learner/ajax/get-skill-gap.php', { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) { contentEl.innerHTML = '<div class="sg-empty"><i class="fas fa-exclamation-circle"></i><h4>Error</h4><p>' + esc(data.message || 'Failed to load.') + '</p></div>'; return; }

            document.getElementById('sg-total').textContent = data.total_skills;
            document.getElementById('sg-acquired').textContent = data.acquired_count;
            document.getElementById('sg-gaps').textContent = data.gap_count;
            var pct = data.total_skills > 0 ? Math.round((data.acquired_count / data.total_skills) * 100) : 0;
            document.getElementById('sg-pct').textContent = pct + '%';
            document.getElementById('sg-skills-acquired').textContent = data.acquired_count;
            document.getElementById('sg-skills-remaining').textContent = data.gap_count;

            var gaps = data.gaps || [];
            var acquired = data.acquired || [];
            var html = '';

            if (acquired.length > 0) {
                html += '<div class="sg-section-title" style="color:#10b981;"><i class="fas fa-check-circle"></i> Acquired Skills (' + acquired.length + ')</div>';
                html += '<div class="sg-skill-list">';
                acquired.forEach(function(s) {
                    html += '<div class="sg-skill-row"><span class="sg-skill-dot acquired"></span><span class="sg-skill-name">' + esc(s.name) + '</span><span class="sg-badge acquired">Acquired</span></div>';
                });
                html += '</div>';
            }

            if (gaps.length > 0) {
                html += '<div class="sg-section-title" style="color:#f59e0b;"><i class="fas fa-exclamation-triangle"></i> Skill Gaps (' + gaps.length + ')</div>';
                html += '<div class="sg-skill-list">';
                gaps.forEach(function(s) {
                    var hasCourses = s.courses_available > 0;
                    html += '<div class="sg-skill-row" style="flex-wrap:wrap;">';
                    html += '<span class="sg-skill-dot gap"></span>';
                    html += '<span class="sg-skill-name">' + esc(s.name) + '</span>';
                    if (hasCourses) {
                        html += '<span class="sg-badge gap">' + s.courses_available + ' course' + (s.courses_available > 1 ? 's' : '') + ' available</span>';
                    } else {
                        html += '<button class="sg-request-btn" data-skill-id="' + s.id + '" data-skill-name="' + esc(s.name) + '"><i class="fas fa-paper-plane"></i> Request Training</button>';
                    }
                    html += '</div>';
                    if (hasCourses && s.courses.length > 0) {
                        html += '<div class="sg-gap-courses">';
                        s.courses.slice(0, 3).forEach(function(c) {
                            html += '<a href="' + c.link + '" class="sg-gap-course-link"><i class="fas fa-arrow-right"></i>' + esc(c.title);
                            if (c.category) html += ' <span style="font-size:0.7rem;color:var(--muted);">(' + esc(c.category) + ')</span>';
                            html += '</a>';
                        });
                        if (s.courses.length > 3) {
                            html += '<div style="font-size:0.75rem;color:var(--muted);padding:0.2rem 0.7rem;">+' + (s.courses.length - 3) + ' more courses</div>';
                        }
                        html += '</div>';
                    }
                });
                html += '</div>';
            }

            if (!html) html = '<div class="sg-empty"><i class="fas fa-puzzle-piece"></i><h4>No Skills Available</h4><p>Skills haven\'t been assigned to courses yet.</p></div>';
            contentEl.innerHTML = html;
        })
        .catch(function() { contentEl.innerHTML = '<div class="sg-empty"><i class="fas fa-wifi"></i><h4>Network Error</h4><p>Unable to load skill data.</p></div>'; });

    // Request Training modal
    var overlay = document.getElementById('sg-request-overlay');
    var skillNameEl = document.getElementById('sg-rm-skill-name');
    var messageEl = document.getElementById('sg-rm-message');
    var submitBtn = document.getElementById('sg-rm-submit');
    var cancelBtn = document.getElementById('sg-rm-cancel');
    var currentSkillId = null;

    function openRequestModal(skillId, skillName) {
        currentSkillId = skillId;
        skillNameEl.textContent = skillName;
        messageEl.value = '';
        overlay.classList.add('active');
    }
    function closeRequestModal() {
        overlay.classList.remove('active');
        currentSkillId = null;
    }

    cancelBtn.addEventListener('click', closeRequestModal);
    overlay.addEventListener('click', function(e) { if (e.target === overlay) closeRequestModal(); });

    // Delegate click on request buttons
    contentEl.addEventListener('click', function(e) {
        var btn = e.target.closest('.sg-request-btn');
        if (!btn || btn.disabled) return;
        openRequestModal(btn.dataset.skillId, btn.dataset.skillName);
    });

    submitBtn.addEventListener('click', function() {
        if (!currentSkillId) return;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Sending...';

        fetch('pages/learner/ajax/request-training.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ skill_id: currentSkillId, message: messageEl.value })
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Request';
            if (res.success) {
                closeRequestModal();
                // Update the button to show sent state
                var btn = contentEl.querySelector('.sg-request-btn[data-skill-id="' + currentSkillId + '"]');
                if (btn) {
                    btn.classList.add('sent');
                    btn.innerHTML = '<i class="fas fa-check"></i> Request Sent';
                    btn.disabled = true;
                }
            } else {
                alert(res.message || 'Failed to submit request.');
            }
        })
        .catch(function() {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Request';
            alert('Network error. Please try again.');
        });
    });
})();
</script>
