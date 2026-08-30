<?php
include_once __DIR__ . '/../../classes/Employee.php';
include_once __DIR__ . '/../../classes/Grade.php';

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
    $grades = [];
    $certificates = [];
    $completedCourses = [];
}
?>
<div class="module-content">
    <div class="toolbar">
        <div class="toolbar-search">
            <input type="search" placeholder="Search your results..." aria-label="Search results" />
        </div>
        <div class="toolbar-actions">
            <select class="toolbar-filter" aria-label="Filter results">
                <option value="all">All</option>
                <option value="passed">Passed</option>
                <option value="failed">Failed</option>
            </select>
            <button type="button" class="toolbar-mode-toggle" data-view="grid" aria-label="Toggle view">Grid</button>
            
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="analytics-cards" style="margin-bottom:2rem;">
        <div class="analytics-card">
            <h2>Completed Courses</h2>
            <p class="analytics-value"><?= $progressSummary['completed'] ?></p>
        </div>
        <div class="analytics-card">
            <h2>Average Score</h2>
            <p class="analytics-value"><?= $progressSummary['average_score'] ?>%</p>
        </div>
        <div class="analytics-card">
            <h2>Certificates Earned</h2>
            <p class="analytics-value"><?= $progressSummary['total_certificates'] ?></p>
        </div>
    </div>

    <div class="tab-container">
        <div class="tab-list">
            <button type="button" class="tab-item active" data-tab="tab-grades">Grades</button>
            <button type="button" class="tab-item" data-tab="tab-completed">Completed Courses</button>
            <button type="button" class="tab-item" data-tab="tab-certificates">Certificates</button>
            <button type="button" class="tab-item" data-tab="tab-progress">Progress Details</button>
        </div>

        <div class="tab-content active" data-tab="tab-grades">
            <div class="mode-card">
                <h2>Course Grades</h2>
                <p>Your final scores and completion status for all completed courses.</p>
                <?php if (empty($grades)): ?>
                    <div style="padding:2rem; text-align:center; background:#f9f9f9; border-radius:12px;">
                        <h3>No grades yet</h3>
                        <p>You haven't completed any courses. Visit the catalog to enroll in a course.</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x:auto; margin-top:1rem;">
                        <table style="width:100%; border-collapse:collapse; font-size:0.95rem;">
                            <thead style="background:#f5f5f5; border-bottom:2px solid #ddd;">
                                <tr>
                                    <th style="padding:1rem; text-align:left;">Course</th>
                                    <th style="padding:1rem; text-align:center;">Score</th>
                                    <th style="padding:1rem; text-align:center;">Status</th>
                                    <th style="padding:1rem; text-align:center;">Issued</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($grades as $grade): ?>
                                <tr style="border-bottom:1px solid #eee; hover:background:#fafafa;">
                                    <td style="padding:1rem; text-align:left;">
                                        <strong><?= htmlspecialchars($grade['course_title']) ?></strong>
                                    </td>
                                    <td style="padding:1rem; text-align:center;">
                                        <span style="font-weight:600; color:var(--primary);"><?= round((float) $grade['final_score'], 2) ?>%</span>
                                    </td>
                                    <td style="padding:1rem; text-align:center;">
                                        <?php
                                            $statusColor = $grade['status'] === 'passed' ? '#d4edda' : '#f8d7da';
                                            $statusTextColor = $grade['status'] === 'passed' ? '#155724' : '#721c24';
                                        ?>
                                        <span style="background:<?= $statusColor ?>; color:<?= $statusTextColor ?>; padding:0.35rem 0.8rem; border-radius:6px; font-weight:500;">
                                            <?= ucfirst(htmlspecialchars($grade['status'])) ?>
                                        </span>
                                    </td>
                                    <td style="padding:1rem; text-align:center; color:#666;">
                                        <?= date('M d, Y', strtotime($grade['issued_at'])) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="tab-content" data-tab="tab-completed">
            <div class="mode-card">
                <h2>Completed Courses</h2>
                <p>All courses you have successfully completed.</p>
                <?php if (empty($completedCourses)): ?>
                    <div style="padding:2rem; text-align:center; background:#f9f9f9; border-radius:12px;">
                        <h3>No completed courses yet</h3>
                        <p>Keep learning! Your completed courses will appear here.</p>
                    </div>
                <?php else: ?>
                    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:1rem; margin-top:1rem;">
                        <?php foreach ($completedCourses as $cc):
                            $gradeForCourse = null;
                            foreach ($grades as $g) {
                                if ((int)$g['course_id'] === (int)$cc['course_id']) {
                                    $gradeForCourse = $g;
                                    break;
                                }
                            }
                            $certForCourse = null;
                            foreach ($certificates as $cert) {
                                if ((int)$cert['course_id'] === (int)$cc['course_id']) {
                                    $certForCourse = $cert;
                                    break;
                                }
                            }
                        ?>
                        <div style="background:var(--surface,#fff); border:1px solid rgba(32,0,130,0.08); border-radius:14px; overflow:hidden; transition:transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.08)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
                            <div style="background:linear-gradient(135deg, rgba(16,185,129,0.12), rgba(52,211,153,0.06)); padding:1.25rem; text-align:center; border-bottom:1px solid rgba(16,185,129,0.1);">
                                <div style="font-size:2rem; color:#10b981; margin-bottom:0.4rem;"><i class="fas fa-check-circle"></i></div>
                                <div style="font-weight:700; color:var(--text); font-size:1rem;"><?= htmlspecialchars($cc['course_title']) ?></div>
                                <?php if (!empty($cc['category'])): ?>
                                    <span style="display:inline-block; margin-top:0.3rem; padding:0.15rem 0.5rem; border-radius:999px; font-size:0.65rem; font-weight:700; background:rgba(32,0,130,0.08); color:var(--primary);"><?= htmlspecialchars($cc['category']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div style="padding:1rem 1.25rem;">
                                <?php if ($gradeForCourse): ?>
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem; margin-bottom:0.75rem; font-size:0.8rem;">
                                    <div>
                                        <div style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; color:var(--primary); font-weight:700;">Score</div>
                                        <div style="color:var(--text); margin-top:0.15rem; font-weight:700; font-size:1.05rem;"><?= round((float)$gradeForCourse['final_score'], 1) ?>%</div>
                                    </div>
                                    <div>
                                        <div style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; color:var(--primary); font-weight:700;">Status</div>
                                        <div style="margin-top:0.15rem;">
                                            <span style="padding:0.15rem 0.5rem; border-radius:999px; font-size:0.65rem; font-weight:700; background:rgba(16,185,129,0.1); color:#10b981;">Passed</span>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem; font-size:0.75rem; color:rgba(32,0,130,0.5); margin-bottom:0.75rem;">
                                    <div>
                                        <div style="font-size:0.6rem; text-transform:uppercase; letter-spacing:0.06em; color:var(--primary); font-weight:700;">Enrolled</div>
                                        <div style="margin-top:0.1rem;"><?= $cc['enrolled_at'] ? date('M j, Y', strtotime($cc['enrolled_at'])) : '—' ?></div>
                                    </div>
                                    <div>
                                        <div style="font-size:0.6rem; text-transform:uppercase; letter-spacing:0.06em; color:var(--primary); font-weight:700;">Completed</div>
                                        <div style="margin-top:0.1rem;"><?= $cc['completed_at'] ? date('M j, Y', strtotime($cc['completed_at'])) : '—' ?></div>
                                    </div>
                                </div>
                                <?php if ($certForCourse): ?>
                                <a href="?page=public/verify-certificate&code=<?= htmlspecialchars($certForCourse['verification_code']) ?>" target="_blank" style="display:block; text-align:center; padding:0.5rem; border-radius:8px; font-size:0.8rem; font-weight:700; background:var(--primary); color:#fff; text-decoration:none;"><i class="fas fa-certificate" style="margin-right:0.3rem;"></i>View Certificate</a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="tab-content" data-tab="tab-certificates">
            <div class="mode-card">
                <h2>Your Certificates</h2>
                <p>Download or share your earned certificates of completion.</p>
                <?php if (empty($certificates)): ?>
                    <div style="padding:2rem; text-align:center; background:#f9f9f9; border-radius:12px;">
                        <h3>No certificates yet</h3>
                        <p>Complete a course to earn a certificate.</p>
                    </div>
                <?php else: ?>
                    <div class="cards-grid" style="margin-top:1rem;">
                        <?php foreach ($certificates as $cert): ?>
                        <div class="content-card-item" style="cursor:pointer;">
                            <div class="content-card-thumb" style="background: linear-gradient(135deg, var(--primary), var(--text)); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:bold; font-size:1.2rem;">
                                <i class="fas fa-certificate"></i>
                            </div>
                            <div class="content-card-body">
                                <div class="content-card-meta">
                                    <span class="pill">Certificate</span>
                                    <span class="pill">Active</span>
                                </div>
                                <h3><?= htmlspecialchars($cert['course_title']) ?></h3>
                                <p>Earned on <?= date('M d, Y', strtotime($cert['issued_at'])) ?></p>
                                <div class="content-card-footer">
                                    <button class="view-cert-btn" data-cert-id="<?= $cert['id'] ?>" data-code="<?= htmlspecialchars($cert['verification_code']) ?>" style="padding:0.5rem 1rem; background:var(--primary); color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:500;">View Certificate</button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="tab-content" data-tab="tab-progress">
            <div class="mode-card">
                <h2>Learning Progress</h2>
                <p>Detailed breakdown of your course completion progress across all enrollments.</p>
                <div id="progress-details" style="margin-top:1.5rem;">
                    <!-- Progress details will be loaded dynamically -->
                    <p style="text-align:center; color:#999;">Loading progress details...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Grade Details Modal -->
<div id="grade-modal" class="modal-overlay" style="display:none; z-index:10000; overflow-y:auto; padding:2rem 0;">
    <div style="max-width:600px; margin:2rem auto; background:#fff; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.2);">
        <div style="padding:2rem; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
            <h2 style="margin:0;">Grade Details</h2>
            <button type="button" id="close-grade-modal" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:#999;">&times;</button>
        </div>
        <div id="grade-modal-content" style="padding:2rem;">
            <!-- Content loaded dynamically -->
        </div>
        <div style="padding:2rem; background:#f9f9f9; border-top:1px solid #eee; display:flex; gap:1rem; justify-content:flex-end;">
            <button type="button" id="close-grade-btn" style="padding:0.75rem 1.5rem; background:#ccc; color:#333; border:none; border-radius:6px; cursor:pointer; font-weight:500;">Close</button>
        </div>
    </div>
</div>

<!-- Certificate Details Modal -->
<div id="certificate-modal" class="modal-overlay" style="display:none; z-index:10000; overflow-y:auto; padding:2rem 0;">
    <div style="max-width:600px; margin:2rem auto; background:#fff; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.2);">
        <div style="padding:2rem; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
            <h2 style="margin:0;">Certificate</h2>
            <button type="button" id="close-cert-modal" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:#999;">&times;</button>
        </div>
        <div id="certificate-modal-content" style="padding:2rem;">
            <!-- Content loaded dynamically -->
        </div>
        <div style="padding:2rem; background:#f9f9f9; border-top:1px solid #eee; display:flex; gap:1rem; justify-content:flex-end;">
            <button type="button" id="close-cert-btn" style="padding:0.75rem 1.5rem; background:#ccc; color:#333; border:none; border-radius:6px; cursor:pointer; font-weight:500;">Close</button>
            <button type="button" id="view-cert-btn" style="padding:0.75rem 1.5rem; background:var(--primary); color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:500;" data-code="">View Certificate</button>
        </div>
    </div>
</div>

<script>
(function() {
    // Grades pagination
    var PAGE = 10;
    var gradesPage = 1;
    var gradesRows = Array.from(document.querySelectorAll('#tab-grades tbody tr'));
    function paginateGrades() {
        var tot = Math.max(1, Math.ceil(gradesRows.length / PAGE));
        gradesPage = Math.min(gradesPage, tot);
        var st = (gradesPage - 1) * PAGE;
        gradesRows.forEach(function(r, i) { r.style.display = (i >= st && i < st + PAGE) ? '' : 'none'; });
        var pg = document.getElementById('grades-pagination');
        if (pg) {
            pg.querySelector('.page-indicator').textContent = 'Page ' + gradesPage + ' of ' + tot;
            pg.querySelector('[data-action="prev"]').disabled = gradesPage <= 1;
            pg.querySelector('[data-action="next"]').disabled = gradesPage >= tot;
            pg.style.display = tot <= 1 ? 'none' : '';
        }
    }
    var gradesPg = document.getElementById('grades-pagination');
    if (gradesPg) {
        gradesPg.addEventListener('click', function(e) {
            var btn = e.target.closest('[data-action]');
            if (!btn || btn.disabled) return;
            if (btn.dataset.action === 'prev' && gradesPage > 1) gradesPage--;
            if (btn.dataset.action === 'next') gradesPage++;
            paginateGrades();
        });
    }
    paginateGrades();
})();

// Grade modal handlers
const gradeModal = document.getElementById('grade-modal');
const closeGradeModalBtn = document.getElementById('close-grade-modal');
const closeGradeBtn = document.getElementById('close-grade-btn');

function closeGradeModal() {
    gradeModal.style.display = 'none';
}

closeGradeModalBtn.addEventListener('click', closeGradeModal);
closeGradeBtn.addEventListener('click', closeGradeModal);
gradeModal.addEventListener('click', function(e) {
    if (e.target === this) closeGradeModal();
});

// Certificate modal handlers
const certificateModal = document.getElementById('certificate-modal');
const closeCertModalBtn = document.getElementById('close-cert-modal');
const closeCertBtn = document.getElementById('close-cert-btn');
const viewCertBtn = document.getElementById('view-cert-btn');

function closeCertificateModal() {
    certificateModal.style.display = 'none';
}

closeCertModalBtn.addEventListener('click', closeCertificateModal);
closeCertBtn.addEventListener('click', closeCertificateModal);
certificateModal.addEventListener('click', function(e) {
    if (e.target === this) closeCertificateModal();
});

// Open grade modal on table row click
document.querySelectorAll('table tbody tr').forEach(row => {
    row.addEventListener('click', function() {
        const cells = this.querySelectorAll('td');
        const courseTitle = cells[0].textContent.trim();
        const score = cells[1].textContent.trim();
        const status = cells[2].textContent.trim();
        const issued = cells[3].textContent.trim();
        
        const statusColor = status.toLowerCase() === 'passed' ? '#d4edda' : '#f8d7da';
        const statusTextColor = status.toLowerCase() === 'passed' ? '#155724' : '#721c24';
        
        document.getElementById('grade-modal-content').innerHTML = `
            <div>
                <h3 style="margin-top:0;">${courseTitle}</h3>
                <div style="background:#f0f0f0; padding:1.5rem; border-radius:6px; margin:1.5rem 0;">
                    <p style="margin:0 0 1rem 0; color:#666;"><strong>Final Score:</strong></p>
                    <div style="font-size:2.5rem; font-weight:bold; color:var(--primary); margin:0.5rem 0;">${score}</div>
                    <p style="margin:1rem 0 0 0; color:#666;"><strong>Status:</strong> <span style="background:${statusColor}; color:${statusTextColor}; padding:0.35rem 0.8rem; border-radius:6px;">${status}</span></p>
                    <p style="margin:0.5rem 0 0 0; color:#666;"><strong>Issued:</strong> ${issued}</p>
                </div>
            </div>
        `;
        gradeModal.style.display = 'block';
    });
});

// Open certificate modal on card click
document.querySelectorAll('.content-card-item:has(.view-cert-btn)').forEach(card => {
    card.addEventListener('click', function() {
        const courseTitle = this.querySelector('h3').textContent.trim();
        const dateEarned = this.querySelector('p').textContent.trim();
        const certBtn = this.querySelector('.view-cert-btn');
        const code = certBtn.dataset.code;
        
        document.getElementById('certificate-modal-content').innerHTML = `
            <div style="text-align:center;">
                <div style="font-size:4rem; color:var(--primary); margin-bottom:1rem;"><i class="fas fa-certificate"></i></div>
                <h3 style="margin-top:0;">${courseTitle}</h3>
                <p style="color:#666; margin:0.5rem 0;">${dateEarned}</p>
                <div style="background:#f0f0f0; padding:1rem; border-radius:6px; margin-top:1.5rem;">
                    <p style="margin:0; color:#666; font-size:0.9rem;"><strong>Certificate ID:</strong> ${code}</p>
                </div>
            </div>
        `;
        viewCertBtn.dataset.code = code;
        certificateModal.style.display = 'block';
    });
});

viewCertBtn.addEventListener('click', function(e) {
    e.stopPropagation();
    const code = this.dataset.code;
    window.location.href = '?page=public/verify-certificate&code=' + code;
});

// Load progress details
const progressDetails = document.getElementById('progress-details');
if (progressDetails) {
    fetch('pages/learner/ajax/get-progress.php')
        .then(r => r.json())
        .then(data => {
            if (data.progress && data.progress.length > 0) {
                progressDetails.innerHTML = '<div style="display:grid; gap:1rem;">' + data.progress.map(p => `
                    <div style="padding:1.5rem; background:#f9f9f9; border-radius:12px; border-left:4px solid var(--primary); cursor:pointer;" data-enrollment-id="${p.enrollment_id}">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <h4 style="margin:0 0 0.5rem 0;">${p.course_title}</h4>
                                <p style="margin:0; color:#666; font-size:0.9rem;">${p.modules_completed} of ${p.total_modules} modules completed</p>
                            </div>
                            <div style="text-align:right;">
                                <div style="font-weight:bold; color:var(--primary); font-size:1.2rem;">${p.completion_percent}%</div>
                                <div style="color:#999; font-size:0.85rem;">Complete</div>
                            </div>
                        </div>
                        <div style="background:#e0e0e0; height:8px; border-radius:4px; margin-top:1rem; overflow:hidden;">
                            <div style="background:var(--primary); height:100%; width:${p.completion_percent}%;"></div>
                        </div>
                    </div>
                `).join('') + '</div>';
            } else {
                progressDetails.innerHTML = '<div style="padding:2rem; text-align:center; background:#f9f9f9; border-radius:12px;"><p>No progress data available yet.</p></div>';
            }
        });
}
</script>
