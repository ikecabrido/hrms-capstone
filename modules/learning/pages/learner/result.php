<?php
include_once __DIR__ . '/../../classes/Employee.php';
include_once __DIR__ . '/../../classes/grade.php';

require_once dirname(__DIR__, 4) . '/database/db.php';

$employeeClass = new Employee();
$learnerId = (int) ($employeeClass->getEmployeeId() ?? 0);

$grades = [];
$certificates = [];
$completedCourses = [];
$progressSummary = ['completed' => 0, 'average_score' => 0, 'total_certificates' => 0];

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Get learner's grades
    $stmt = $pdo->prepare("
        SELECT g.id, g.course_id, g.final_score, g.status, g.issued_at, c.title AS course_title
        FROM ld_grade g
        JOIN ld_course c ON c.id = g.course_id
        WHERE g.learner_id = :learner_id
        ORDER BY g.issued_at DESC
    ");
    $stmt->execute([':learner_id' => $learnerId]);
    $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get learner's certificates
    $stmt = $pdo->prepare("
        SELECT cert.id, cert.course_id, cert.verification_code, cert.issued_at, cert.valid_until, cert.status, c.title AS course_title
        FROM ld_certificate cert
        JOIN ld_course c ON c.id = cert.course_id
        WHERE cert.learner_id = :learner_id AND cert.status = 'active'
        ORDER BY cert.issued_at DESC
    ");
    $stmt->execute([':learner_id' => $learnerId]);
    $certificates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get progress summary
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_enrollment WHERE learner_id = :learner_id AND status = 'completed'");
    $stmt->execute([':learner_id' => $learnerId]);
    $progressSummary['completed'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT AVG(final_score) FROM ld_grade WHERE learner_id = :learner_id");
    $stmt->execute([':learner_id' => $learnerId]);
    $avg = $stmt->fetchColumn();
    $progressSummary['average_score'] = $avg !== null ? round((float) $avg, 2) : 0;

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_certificate WHERE learner_id = :learner_id AND status = 'active'");
    $stmt->execute([':learner_id' => $learnerId]);
    $progressSummary['total_certificates'] = (int) $stmt->fetchColumn();

    // Get completed courses
    $stmt = $pdo->prepare("
        SELECT e.id AS enrollment_id, e.course_id, e.enrolled_at, e.completed_at,
               c.title AS course_title, c.description AS course_description, c.category
        FROM ld_enrollment e
        JOIN ld_course c ON c.id = e.course_id
        WHERE e.learner_id = :learner_id AND e.status = 'completed'
        ORDER BY e.completed_at DESC
    ");
    $stmt->execute([':learner_id' => $learnerId]);
    $completedCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    DbError::capture($e, 'learner/result');
    $grades = [];
    $certificates = [];
    $completedCourses = [];
}

// Course-keyed lookups so each row can show its score / certificate without a nested loop.
$gradeByCourse = [];
foreach ($grades as $grade) {
    $gradeByCourse[(int) $grade['course_id']] = $grade;
}
$certByCourse = [];
foreach ($certificates as $cert) {
    $certByCourse[(int) $cert['course_id']] = $cert;
}

$avgScore = round((float) $progressSummary['average_score'], 1);
$gradedCount = count($grades);

/**
 * Map a grade status to a shared dashboard badge class.
 */
function resultStatusClass($status)
{
    $status = strtolower((string) $status);
    if ($status === 'passed' || $status === 'completed') return 'is-success';
    if ($status === 'failed') return 'is-danger';
    return 'is-muted';
}
?>
<style>
/* ==========================================================================
   Grade Book — page-scoped refinements layered over the shared dashboard
   system (home.css). Everything is namespaced under .gradebook-page so the
   reusable .tab-container / .tab-item rules used by other pages are untouched.
   ========================================================================== */
.gradebook-page .tab-container {
    margin-top: 0;
    padding: 1.15rem 1.3rem 1.3rem;
}

/* Segmented tab control (keeps .tab-item / .tab-content JS hooks) */
.gradebook-page .tab-list {
    display: flex;
    flex-wrap: nowrap;
    gap: 4px;
    margin-bottom: 1.25rem;
    padding: 4px;
    background: rgba(32, 0, 130, 0.05);
    border: 1px solid rgba(32, 0, 130, 0.06);
    border-radius: 13px;
}

.gradebook-page .tab-item {
    flex: 1 1 0;
    min-width: 0;
    width: auto;
    white-space: normal;
    padding: 0.6rem 0.5rem;
    background: transparent;
    border: 1px solid transparent;
    border-radius: 9px;
    color: var(--muted);
    font-size: 0.85rem;
    font-weight: 600;
    text-align: center;
    transition: background 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
}
.gradebook-page .tab-item:hover {
    background: rgba(32, 0, 130, 0.06);
    color: var(--text);
    transform: none;
}
.gradebook-page .tab-item.active {
    background: var(--primary);
    border-color: transparent;
    color: #fff;
    box-shadow: 0 4px 12px rgba(32, 0, 130, 0.24);
}

.gradebook-page .gb-tab-count {
    display: inline-block;
    margin-left: 0.4rem;
    padding: 0.05rem 0.42rem;
    border-radius: 999px;
    font-size: 0.68rem;
    font-weight: 700;
    background: rgba(32, 0, 130, 0.10);
    color: var(--primary);
}
.gradebook-page .tab-item.active .gb-tab-count { background: rgba(255, 255, 255, 0.22); color: #fff; }

/* Panel headers + rows */
.gradebook-page .gb-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.35rem;
}
.gradebook-page .gb-note {
    margin: 0 0 0.35rem;
    font-size: 0.85rem;
    color: var(--muted);
}
.gradebook-page .home-list { margin-top: 0.4rem; }
.gradebook-page .home-row-title { white-space: normal; }
.gradebook-page .home-row-trail { gap: 0.55rem; }
.gradebook-page .gb-code {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 0.72rem;
}
.gradebook-page a.home-cta { text-decoration: none; }
.gradebook-page .gb-loading {
    margin: 0;
    padding: 1rem 0;
    color: var(--muted);
    font-size: 0.85rem;
}

@media (max-width: 640px) {
    .gradebook-page .home-row { align-items: flex-start; }
    .gradebook-page .home-row-trail { flex-direction: column; align-items: flex-end; }
}

/* Dark mode */
[data-theme="dark"] .gradebook-page .tab-list {
    background: rgba(255, 255, 255, 0.05);
    border-color: rgba(255, 255, 255, 0.07);
}
[data-theme="dark"] .gradebook-page .tab-item { background: transparent; color: var(--muted); }
[data-theme="dark"] .gradebook-page .tab-item:hover { background: rgba(255, 255, 255, 0.08); color: var(--text); }
[data-theme="dark"] .gradebook-page .tab-item.active { background: var(--primary); color: #fff; }
[data-theme="dark"] .gradebook-page .gb-tab-count { background: rgba(255, 255, 255, 0.10); color: var(--text); }
</style>

<div class="module-content gradebook-page">
    <div class="home-dashboard">

        <!-- Hero -->
        <section class="home-hero" aria-labelledby="gradebook-title">
            <div class="home-hero-main">
                <h1 id="gradebook-title">Grade Book</h1>
                <p>Track your scores, completed courses, and certificates.</p>
            </div>
            <div class="home-hero-aside">
                <div class="home-hero-chips">
                    <span class="home-hero-chip"><i class="fas fa-check-circle"></i> <?= (int) $progressSummary['completed'] ?> completed</span>
                    <span class="home-hero-chip"><i class="fas fa-star"></i> <?= $avgScore ?>% average</span>
                    <span class="home-hero-chip"><i class="fas fa-certificate"></i> <?= (int) $progressSummary['total_certificates'] ?> certificates</span>
                </div>
            </div>
        </section>

        <!-- Tabs -->
        <div class="tab-container">
            <div class="tab-list" role="tablist" aria-label="Grade book sections">
                <button type="button" class="tab-item active" data-tab="tab-grades" role="tab" aria-selected="true">
                    Grades <span class="gb-tab-count"><?= $gradedCount ?></span>
                </button>
                <button type="button" class="tab-item" data-tab="tab-completed" role="tab" aria-selected="false">
                    Completed Courses <span class="gb-tab-count"><?= count($completedCourses) ?></span>
                </button>
                <button type="button" class="tab-item" data-tab="tab-certificates" role="tab" aria-selected="false">
                    Certificates <span class="gb-tab-count"><?= count($certificates) ?></span>
                </button>
                <button type="button" class="tab-item" data-tab="tab-progress" role="tab" aria-selected="false">
                    Progress Details
                </button>
            </div>

            <!-- Grades -->
            <div class="tab-content active" data-tab="tab-grades">
                <div class="gb-head">
                    <h2 class="home-card-title"><span class="home-card-icon"><i class="fas fa-chart-line"></i></span> Course Grades</h2>
                    <span class="home-card-count"><?= $gradedCount ?> result<?= $gradedCount !== 1 ? 's' : '' ?></span>
                </div>
                <p class="gb-note">Your final scores and completion status for every graded course.</p>

                <?php if (empty($grades)): ?>
                    <div class="home-empty">
                        <i class="fas fa-chart-line"></i>
                        <p>No grades yet — you haven't completed any courses.</p>
                        <a class="home-cta" href="?page=learner/catalog"><i class="fas fa-graduation-cap"></i> Browse catalog</a>
                    </div>
                <?php else: ?>
                    <div class="home-list">
                        <?php foreach ($grades as $grade): $passed = strtolower((string) $grade['status']) === 'passed'; ?>
                        <div class="home-row">
                            <span class="home-score<?= $passed ? '' : ' is-fail' ?>"><?= round((float) $grade['final_score']) ?>%</span>
                            <span class="home-row-body">
                                <span class="home-row-title"><?= htmlspecialchars($grade['course_title']) ?></span>
                                <span class="home-row-meta">
                                    <?= $grade['issued_at'] ? 'Issued ' . date('M j, Y', strtotime($grade['issued_at'])) : 'Not issued' ?>
                                </span>
                            </span>
                            <span class="home-row-trail">
                                <span class="home-badge <?= resultStatusClass($grade['status']) ?>"><?= htmlspecialchars(ucfirst((string) $grade['status'])) ?></span>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Completed courses -->
            <div class="tab-content" data-tab="tab-completed">
                <div class="gb-head">
                    <h2 class="home-card-title"><span class="home-card-icon"><i class="fas fa-check-circle"></i></span> Completed Courses</h2>
                    <span class="home-card-count"><?= count($completedCourses) ?> course<?= count($completedCourses) !== 1 ? 's' : '' ?></span>
                </div>
                <p class="gb-note">Every course you have finished successfully.</p>

                <?php if (empty($completedCourses)): ?>
                    <div class="home-empty">
                        <i class="fas fa-book-open"></i>
                        <p>No completed courses yet — keep learning!</p>
                        <a class="home-cta" href="?page=learner/study"><i class="fas fa-play"></i> Continue learning</a>
                    </div>
                <?php else: ?>
                    <div class="home-list">
                        <?php foreach ($completedCourses as $cc):
                            $gradeForCourse = $gradeByCourse[(int) $cc['course_id']] ?? null;
                            $certForCourse = $certByCourse[(int) $cc['course_id']] ?? null;
                        ?>
                        <div class="home-row">
                            <span class="home-row-icon tone-emerald"><i class="fas fa-graduation-cap"></i></span>
                            <span class="home-row-body">
                                <span class="home-row-title"><?= htmlspecialchars($cc['course_title']) ?></span>
                                <span class="home-row-meta">
                                    <?php if (!empty($cc['category'])): ?><?= htmlspecialchars($cc['category']) ?> &bull; <?php endif; ?>
                                    Completed <?= $cc['completed_at'] ? date('M j, Y', strtotime($cc['completed_at'])) : '—' ?>
                                </span>
                            </span>
                            <span class="home-row-trail">
                                <?php if ($gradeForCourse): ?>
                                    <span class="home-badge <?= resultStatusClass($gradeForCourse['status']) ?>"><?= round((float) $gradeForCourse['final_score']) ?>%</span>
                                <?php endif; ?>
                                <?php if ($certForCourse): ?>
                                    <a class="home-cta" href="?page=learner/result-subpage/certificate-print&amp;back=learner/result&amp;certificate_id=<?= (int) $certForCourse['id'] ?>">
                                        <i class="fas fa-award"></i> Certificate
                                    </a>
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Certificates -->
            <div class="tab-content" data-tab="tab-certificates">
                <div class="gb-head">
                    <h2 class="home-card-title"><span class="home-card-icon"><i class="fas fa-award"></i></span> Your Certificates</h2>
                    <span class="home-card-count"><?= count($certificates) ?> certificate<?= count($certificates) !== 1 ? 's' : '' ?></span>
                </div>
                <p class="gb-note">Download or verify the certificates you have earned.</p>

                <?php if (empty($certificates)): ?>
                    <div class="home-empty">
                        <i class="fas fa-award"></i>
                        <p>No certificates yet — complete a course to earn one.</p>
                    </div>
                <?php else: ?>
                    <div class="home-list">
                        <?php foreach ($certificates as $cert):
                            $cIsExpired = !empty($cert['valid_until']) && strtotime($cert['valid_until']) < time();
                        ?>
                        <div class="home-row">
                            <span class="home-row-icon tone-violet"><i class="fas fa-award"></i></span>
                            <span class="home-row-body">
                                <span class="home-row-title"><?= htmlspecialchars($cert['course_title']) ?></span>
                                <span class="home-row-meta">
                                    Issued <?= $cert['issued_at'] ? date('M j, Y', strtotime($cert['issued_at'])) : 'N/A' ?>
                                    <span class="gb-code"> &bull; <?= htmlspecialchars((string) $cert['verification_code']) ?></span>
                                </span>
                            </span>
                            <span class="home-row-trail">
                                <span class="home-badge <?= $cIsExpired ? 'is-danger' : 'is-success' ?>"><?= $cIsExpired ? 'Expired' : 'Valid' ?></span>
                                <a class="home-cta" href="?page=learner/result-subpage/certificate-print&amp;back=learner/result&amp;certificate_id=<?= (int) $cert['id'] ?>">
                                    <i class="fas fa-external-link-alt"></i> View
                                </a>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Progress -->
            <div class="tab-content" data-tab="tab-progress">
                <div class="gb-head">
                    <h2 class="home-card-title"><span class="home-card-icon"><i class="fas fa-bars-progress"></i></span> Learning Progress</h2>
                </div>
                <p class="gb-note">Completion breakdown across all your enrollments.</p>
                <div id="progress-details" class="home-list">
                    <p class="gb-loading">Loading progress…</p>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
(function () {
    'use strict';

    var wrap = document.getElementById('progress-details');
    if (!wrap) return;

    function esc(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (ch) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
        });
    }

    fetch('pages/learner/ajax/get-progress.php', {
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (!data || !Array.isArray(data.progress) || data.progress.length === 0) {
                wrap.innerHTML = '<div class="home-empty"><i class="fas fa-chart-line"></i><p>No progress data available yet.</p></div>';
                return;
            }

            wrap.innerHTML = data.progress.map(function (p) {
                var pct = Math.max(0, Math.min(100, Number(p.completion_percent) || 0));
                return '<a class="home-row" href="?page=learner/study-subpage/course&amp;course_id=' + encodeURIComponent(p.course_id) + '">'
                    + '<span class="home-row-icon tone-violet"><i class="fas fa-book-open"></i></span>'
                    + '<span class="home-row-body">'
                    + '<span class="home-row-title">' + esc(p.course_title) + '</span>'
                    + '<span class="home-row-meta">' + (Number(p.modules_completed) || 0) + ' of ' + (Number(p.total_modules) || 0) + ' modules completed</span>'
                    + '<span class="home-progress">'
                    + '<span class="home-progress-track"><span class="home-progress-fill" style="width:' + pct + '%"></span></span>'
                    + '<span class="home-progress-val">' + pct + '%</span>'
                    + '</span>'
                    + '</span>'
                    + '<i class="fas fa-chevron-right home-row-chev"></i>'
                    + '</a>';
            }).join('');
        })
        .catch(function () {
            wrap.innerHTML = '<div class="home-empty"><i class="fas fa-triangle-exclamation"></i><p>Could not load progress right now.</p></div>';
        });
})();
</script>
