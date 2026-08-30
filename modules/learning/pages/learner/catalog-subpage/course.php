<?php
include_once __DIR__ . '/../../../classes/Employee.php';
include_once __DIR__ . '/../../../classes/Course.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

$employeeClass = new Employee();
$learnerId = (int) ($employeeClass->getEmployeeId() ?? 0);

$courseId = (int) ($_GET['course_id'] ?? 0);
$course = null;
$modules = [];
$isEnrolled = false;
$enrollmentCount = 0;
$moduleCount = 0;

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Fetch course details with instructor name
    $stmt = $pdo->prepare("
        SELECT c.id, c.title, c.description, c.category, c.status, c.thumbnail_path,
               c.start_date, c.enrollment_deadline, c.created_at,
               CONCAT(emp.first_name, ' ', emp.last_name) AS instructor_name,
               c.instructor_id
        FROM ld_course c
        LEFT JOIN em_employees emp ON emp.employee_id = c.instructor_id
        WHERE c.id = :id AND c.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([':id' => $courseId]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($course) {
        // Count modules
        $modStmt = $pdo->prepare("SELECT COUNT(*) FROM ld_module WHERE course_id = :cid AND status = 'active'");
        $modStmt->execute([':cid' => $courseId]);
        $moduleCount = (int) $modStmt->fetchColumn();

        // Count enrollments
        $enrollStmt = $pdo->prepare("SELECT COUNT(*) FROM ld_enrollment WHERE course_id = :cid");
        $enrollStmt->execute([':cid' => $courseId]);
        $enrollmentCount = (int) $enrollStmt->fetchColumn();

        // Check if already enrolled
        $checkStmt = $pdo->prepare("SELECT id FROM ld_enrollment WHERE learner_id = :lid AND course_id = :cid LIMIT 1");
        $checkStmt->execute([':lid' => $learnerId, ':cid' => $courseId]);
        $isEnrolled = (bool) $checkStmt->fetch();

        // Fetch modules with lesson/quiz counts
        $modulesStmt = $pdo->prepare("
            SELECT m.id, m.title, m.description, m.order_index,
                (SELECT COUNT(*) FROM ld_lesson l WHERE l.module_id = m.id AND l.status = 'active') AS lesson_count,
                (SELECT COUNT(*) FROM ld_quiz q WHERE q.module_id = m.id AND q.status = 'active') AS quiz_count
            FROM ld_module m
            WHERE m.course_id = :cid AND m.status = 'active'
            ORDER BY m.order_index ASC, m.id ASC
        ");
        $modulesStmt->execute([':cid' => $courseId]);
        $modules = $modulesStmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch skills
        $skillsStmt = $pdo->prepare("
            SELECT s.name FROM ld_course_skill cs
            JOIN ld_skill s ON s.id = cs.skill_id
            WHERE cs.course_id = :cid
        ");
        $skillsStmt->execute([':cid' => $courseId]);
        $skills = $skillsStmt->fetchAll(PDO::FETCH_COLUMN);
    }
} catch (Throwable $e) {
    $course = null;
}

if (!$course) {
    echo '<div class="module-content"><div class="mode-card"><h2>Course Not Found</h2><p>The course you are looking for does not exist or is no longer available.</p>';
    echo '</div></div>';
    return;
}
?>

<div class="module-content">
    <!-- Back link -->
    <div style="margin-bottom:1.5rem;">
        
    </div>

    <!-- Course Header -->
    <div class="mode-card" style="margin-bottom:1.5rem;">
        <div style="display:flex; gap:2rem; align-items:flex-start; flex-wrap:wrap;">
            <div style="flex:1; min-width:300px;">
                <div style="display:flex; gap:0.5rem; margin-bottom:0.75rem; flex-wrap:wrap;">
                    <span class="pill" style="background:linear-gradient(135deg, rgba(32,0,130,0.85), rgba(81,70,183,0.7)); color:#fff;">Course</span>
                    <?php if (!empty($course['category'])): ?>
                        <span class="pill"><?= htmlspecialchars($course['category']) ?></span>
                    <?php endif; ?>
                    <span class="pill" style="background:#d4edda; color:#155724;">Active</span>
                </div>
                <h1 style="margin:0 0 0.75rem 0; font-size:1.8rem; color:#222;"><?= htmlspecialchars($course['title']) ?></h1>
                <?php if ($course['description']): ?>
                    <p style="color:#555; line-height:1.7; margin:0 0 1.5rem 0;"><?= nl2br(htmlspecialchars($course['description'])) ?></p>
                <?php endif; ?>

                <div style="display:flex; gap:1.5rem; flex-wrap:wrap; margin-bottom:1.5rem;">
                    <?php if (!empty($course['instructor_name'])): ?>
                        <div style="display:flex; align-items:center; gap:0.5rem;">
                            <i class="fas fa-user-tie" style="color:var(--primary);"></i>
                            <div>
                                <div style="font-size:0.75rem; color:#999; text-transform:uppercase;">Instructor</div>
                                <div style="font-weight:600; color:#333;"><?= htmlspecialchars($course['instructor_name']) ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div style="display:flex; align-items:center; gap:0.5rem;">
                        <i class="fas fa-cubes" style="color:#6c757d;"></i>
                        <div>
                            <div style="font-size:0.75rem; color:#999; text-transform:uppercase;">Modules</div>
                            <div style="font-weight:600; color:#333;"><?= $moduleCount ?></div>
                        </div>
                    </div>
                    <div style="display:flex; align-items:center; gap:0.5rem;">
                        <i class="fas fa-users" style="color:#6c757d;"></i>
                        <div>
                            <div style="font-size:0.75rem; color:#999; text-transform:uppercase;">Enrolled</div>
                            <div style="font-weight:600; color:#333;"><?= $enrollmentCount ?></div>
                        </div>
                    </div>
                    <?php if (!empty($course['start_date'])): ?>
                        <div style="display:flex; align-items:center; gap:0.5rem;">
                            <i class="fas fa-calendar-check" style="color:#6c757d;"></i>
                            <div>
                                <div style="font-size:0.75rem; color:#999; text-transform:uppercase;">Start Date</div>
                                <div style="font-weight:600; color:#333;"><?= date('M j, Y', strtotime($course['start_date'])) ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($course['enrollment_deadline'])): ?>
                        <div style="display:flex; align-items:center; gap:0.5rem;">
                            <i class="fas fa-clock" style="color:#dc3545;"></i>
                            <div>
                                <div style="font-size:0.75rem; color:#999; text-transform:uppercase;">Deadline</div>
                                <div style="font-weight:600; color:#333;"><?= date('M j, Y', strtotime($course['enrollment_deadline'])) ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($skills)): ?>
                    <div style="margin-bottom:1.5rem;">
                        <div style="font-size:0.85rem; font-weight:600; color:#333; margin-bottom:0.5rem;">Skills Covered</div>
                        <div style="display:flex; gap:0.4rem; flex-wrap:wrap;">
                            <?php foreach ($skills as $skill): ?>
                                <span style="padding:0.3rem 0.75rem; background:#f0f0f0; border-radius:20px; font-size:0.8rem; color:#555;"><?= htmlspecialchars($skill) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Enroll / View Actions -->
                <div style="display:flex; gap:0.75rem; align-items:center;">
                    <?php if ($isEnrolled): ?>
                        
                        <span style="padding:0.5rem 1rem; background:#d4edda; color:#155724; border-radius:6px; font-weight:500;">
                            <i class="fas fa-check-circle"></i> Already Enrolled
                        </span>
                    <?php else: ?>
                        <button id="enroll-btn" onclick="handleEnroll()" style="display:inline-flex; align-items:center; gap:0.5rem; padding:0.75rem 1.5rem; background:var(--primary); color:#fff; border:none; border-radius:8px; font-weight:600; cursor:pointer; font-size:1rem;">
                            <i class="fas fa-plus-circle"></i> Enroll Now
                        </button>
                        <span id="enroll-status" style="display:none; padding:0.5rem 1rem; background:#d4edda; color:#155724; border-radius:6px; font-weight:500;">
                            <i class="fas fa-check-circle"></i> Enrolled!
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Course Content / Modules -->
    <div class="mode-card">
        <h2 style="margin-bottom:0.5rem;">Course Content</h2>
        <p style="color:#666; margin:0 0 1.5rem 0;"><?= $moduleCount ?> module<?= $moduleCount !== 1 ? 's' : '' ?> · Self-paced learning</p>

        <?php if (empty($modules)): ?>
            <div style="text-align:center; padding:3rem; color:#999;">
                <i class="fas fa-folder-open" style="font-size:2rem; margin-bottom:0.75rem; display:block;"></i>
                No modules have been added to this course yet.
            </div>
        <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:1rem;">
                <?php foreach ($modules as $idx => $mod): ?>
                    
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function handleEnroll() {
    var btn = document.getElementById('enroll-btn');
    if (btn) btn.disabled = true;

    fetch('pages/learner/ajax/enroll-course.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ course_id: <?= $courseId ?> })
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.success) {
            btn.style.display = 'none';
            var status = document.getElementById('enroll-status');
            status.style.display = 'inline-flex';
            // Replace the button area with Continue Learning link after a moment
            setTimeout(function() {
                var actions = btn.parentElement;
                actions.innerHTML = '' +
                    '<span style="padding:0.5rem 1rem; background:#d4edda; color:#155724; border-radius:6px; font-weight:500;"><i class="fas fa-check-circle"></i> Enrolled!</span>';
            }, 800);
        } else {
            alert('Error: ' + (d.error || 'Failed to enroll'));
            if (btn) btn.disabled = false;
        }
    })
    .catch(function(err) {
        alert('Error enrolling: ' + err.message);
        if (btn) btn.disabled = false;
    });
}
</script>
