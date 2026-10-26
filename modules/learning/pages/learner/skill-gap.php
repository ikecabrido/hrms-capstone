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
<div class="module-content lt-page">
    <div class="home-dashboard">

        <section class="home-hero">
            <div class="home-hero-main">
                <h1>Skills Gap Analysis</h1>
                <p>Automatic analysis of your skill gaps based on completed courses and training performance.</p>
            </div>
            <div class="home-hero-aside">
                <div class="home-hero-chips">
                    <span class="home-hero-chip"><i class="fas fa-puzzle-piece"></i> <b id="sg-total">—</b> skills</span>
                    <span class="home-hero-chip"><i class="fas fa-check-circle"></i> <b id="sg-acquired">—</b> acquired</span>
                    <span class="home-hero-chip"><i class="fas fa-exclamation-triangle"></i> <b id="sg-gaps">—</b> gaps</span>
                    <span class="home-hero-chip"><i class="fas fa-percent"></i> <b id="sg-pct">—</b> complete</span>
                </div>
            </div>
        </section>

        <div class="home-card">
            <div class="home-card-head">
                <h2 class="home-card-title">
                    <span class="home-card-icon"><i class="fas fa-clipboard-list"></i></span> Training Progress
                </h2>
            </div>

            <div class="home-kv">
                <span class="home-kv-label"><i class="fas fa-check-circle"></i> Completed courses</span>
                <span class="home-kv-value"><?= $completedCount ?></span>
            </div>
            <div class="home-kv">
                <span class="home-kv-label"><i class="fas fa-spinner"></i> In progress</span>
                <span class="home-kv-value"><?= $enrolledCount ?></span>
            </div>
            <div class="home-kv">
                <span class="home-kv-label"><i class="fas fa-star"></i> Skills acquired</span>
                <span class="home-kv-value" id="sg-skills-acquired">—</span>
            </div>
            <div class="home-kv">
                <span class="home-kv-label"><i class="fas fa-hourglass-half"></i> Skills remaining</span>
                <span class="home-kv-value" id="sg-skills-remaining">—</span>
            </div>

            <?php if (!empty($completedCourses)): ?>
                <div class="lt-subhead"><i class="fas fa-clock-rotate-left"></i> Recently completed</div>
                <div class="home-list">
                    <?php foreach (array_slice($completedCourses, 0, 5) as $course):
                        $certId = $certMap[(int) $course['course_id']] ?? 0;
                        $link = $certId > 0
                            ? '?page=learner/result-subpage/certificate-print&back=learner/skill-gap&certificate_id=' . $certId
                            : '?page=learner/result-subpage/certificate';
                    ?>
                        <a class="home-row" href="<?= htmlspecialchars($link) ?>">
                            <span class="home-row-icon tone-emerald"><i class="fas fa-graduation-cap"></i></span>
                            <span class="home-row-body">
                                <span class="home-row-title"><?= htmlspecialchars($course['title']) ?></span>
                                <span class="home-row-meta lt-row-chips">
                                    <span><?= !empty($course['completed_at']) ? 'Completed ' . date('M j, Y', strtotime($course['completed_at'])) : 'Completed' ?></span>
                                    <?php if ($certId > 0): ?>
                                        <span class="home-badge is-success"><i class="fas fa-certificate"></i> Certificate</span>
                                    <?php else: ?>
                                        <span class="home-badge is-muted">No certificate</span>
                                    <?php endif; ?>
                                </span>
                            </span>
                            <span class="home-row-trail">
                                <i class="fas fa-chevron-right home-row-chev"></i>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="home-card">
            <div class="home-card-head">
                <h2 class="home-card-title">
                    <span class="home-card-icon"><i class="fas fa-chart-line"></i></span> Skill Breakdown
                </h2>
            </div>
            <div id="sg-content">
                <div class="home-empty">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading skill data…</p>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Request Training Modal -->
<div class="lt-modal-overlay" id="sg-request-overlay">
    <div class="lt-modal" role="dialog" aria-modal="true" aria-labelledby="sg-rm-title">
        <h3 id="sg-rm-title"><i class="fas fa-paper-plane"></i> Request Training</h3>
        <p>Request a new course for <strong id="sg-rm-skill-name"></strong>. A trainer will review and create training material.</p>
        <textarea id="sg-rm-message" placeholder="Optional: Describe what you'd like to learn or why this skill is important..."></textarea>
        <div class="lt-modal-actions">
            <button type="button" class="lt-btn lt-btn-ghost" id="sg-rm-cancel">Cancel</button>
            <button type="button" class="lt-btn lt-btn-primary" id="sg-rm-submit">Submit Request</button>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    var contentEl = document.getElementById('sg-content');

    function esc(s) { var d = document.createElement('div'); d.appendChild(document.createTextNode(s == null ? '' : String(s))); return d.innerHTML; }
    function escAttr(s) { return esc(s).replace(/"/g, '&quot;'); }

    fetch('pages/learner/ajax/get-skill-gap.php', { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) {
                contentEl.innerHTML = '<div class="home-empty"><i class="fas fa-exclamation-circle"></i><p>' + esc(data.message || 'Failed to load skill data.') + '</p></div>';
                return;
            }

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
                html += '<div class="lt-subhead is-success"><i class="fas fa-check-circle"></i> Acquired skills (' + acquired.length + ')</div>';
                html += '<div class="home-list">';
                acquired.forEach(function(s) {
                    html += '<div class="home-row">'
                        + '<span class="home-row-icon tone-emerald"><i class="fas fa-check"></i></span>'
                        + '<span class="home-row-body"><span class="home-row-title">' + esc(s.name) + '</span></span>'
                        + '<span class="home-badge is-success">Acquired</span>'
                        + '</div>';
                });
                html += '</div>';
            }

            if (gaps.length > 0) {
                html += '<div class="lt-subhead is-warn"><i class="fas fa-exclamation-triangle"></i> Skill gaps (' + gaps.length + ')</div>';
                html += '<div class="home-list">';
                gaps.forEach(function(s) {
                    var hasCourses = s.courses_available > 0;
                    var courseList = Array.isArray(s.courses) ? s.courses : [];

                    html += '<div class="home-row' + (hasCourses && courseList.length ? ' lt-row-top' : '') + '">'
                        + '<span class="home-row-icon tone-amber"><i class="fas fa-exclamation-triangle"></i></span>'
                        + '<span class="home-row-body">'
                        + '<span class="home-row-title">' + esc(s.name) + '</span>';

                    if (hasCourses) {
                        html += '<span class="home-row-meta">' + s.courses_available + ' course' + (s.courses_available > 1 ? 's' : '') + ' available</span>';
                    }

                    if (hasCourses && courseList.length > 0) {
                        html += '<span class="lt-gap-courses">';
                        courseList.slice(0, 3).forEach(function(c) {
                            html += '<a href="' + escAttr(c.link) + '" class="lt-gap-course"><i class="fas fa-arrow-right"></i>' + esc(c.title)
                                + (c.category ? ' <span class="lt-gap-cat">(' + esc(c.category) + ')</span>' : '') + '</a>';
                        });
                        if (courseList.length > 3) {
                            html += '<span class="home-row-meta">+' + (courseList.length - 3) + ' more courses</span>';
                        }
                        html += '</span>';
                    }

                    html += '</span><span class="home-row-trail">';
                    if (hasCourses) {
                        html += '<span class="home-badge is-warn">' + s.courses_available + ' available</span>';
                    } else {
                        html += '<button type="button" class="sg-request-btn lt-cta-soft" data-skill-id="' + escAttr(s.id) + '" data-skill-name="' + escAttr(s.name) + '"><i class="fas fa-paper-plane"></i> Request</button>';
                    }
                    html += '</span></div>';
                });
                html += '</div>';
            }

            if (!html) {
                html = '<div class="home-empty"><i class="fas fa-puzzle-piece"></i><p>Skills haven\'t been assigned to courses yet.</p></div>';
            }
            contentEl.innerHTML = html;
        })
        .catch(function() {
            contentEl.innerHTML = '<div class="home-empty"><i class="fas fa-wifi"></i><p>Unable to load skill data.</p></div>';
        });

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
