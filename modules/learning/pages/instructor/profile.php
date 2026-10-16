<?php
/**
 * Instructor profile. Layout comes from the shared profile partials; this file
 * adds teaching stats and the areas-of-expertise chips.
 */
$profileRoleLabel = 'Instructor';
require_once __DIR__ . '/../shared/includes/profile-data.php';

$teaching = ['courses' => 0, 'learners' => 0, 'completed' => 0, 'avg_score' => 0.0, 'avg_rating' => 0.0];
$expertise = [];

if ($pdo && $profileId > 0) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_course WHERE instructor_id = :iid AND status IN ('active','draft')");
        $stmt->execute([':iid' => $profileId]);
        $teaching['courses'] = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT e.learner_id) FROM ld_enrollment e JOIN ld_course c ON c.id = e.course_id WHERE c.instructor_id = :iid");
        $stmt->execute([':iid' => $profileId]);
        $teaching['learners'] = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_enrollment e JOIN ld_course c ON c.id = e.course_id WHERE c.instructor_id = :iid AND e.status = 'completed'");
        $stmt->execute([':iid' => $profileId]);
        $teaching['completed'] = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT ROUND(COALESCE(AVG(g.final_score), 0), 1) FROM ld_grade g JOIN ld_course c ON c.id = g.course_id WHERE c.instructor_id = :iid");
        $stmt->execute([':iid' => $profileId]);
        $teaching['avg_score'] = (float) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT ROUND(COALESCE(AVG(r.rating), 0), 1) FROM ld_rating r JOIN ld_course c ON c.id = r.course_id WHERE c.instructor_id = :iid");
        $stmt->execute([':iid' => $profileId]);
        $teaching['avg_rating'] = (float) $stmt->fetchColumn();

        $stmt = $pdo->prepare(
            "SELECT s.name, COUNT(*) AS course_count
             FROM ld_course_skill cs
             JOIN ld_course c ON c.id = cs.course_id
             JOIN ld_skill s ON s.id = cs.skill_id
             WHERE c.instructor_id = :iid
             GROUP BY s.id, s.name
             ORDER BY course_count DESC, s.name ASC
             LIMIT 8"
        );
        $stmt->execute([':iid' => $profileId]);
        $expertise = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        // keep defaults
    }
}

$gradebookLink = null;
?>
<?php include __DIR__ . '/../shared/includes/profile-assets.php'; ?>
<div class="module-content">
    <?php include __DIR__ . '/../shared/includes/profile-header.php'; ?>
    <?php include __DIR__ . '/../shared/includes/profile-completion.php'; ?>
    <?php include __DIR__ . '/../shared/includes/profile-bio.php'; ?>

    <div class="pfl-grid" style="margin-bottom:1.5rem;">
        <?php include __DIR__ . '/../shared/includes/profile-info.php'; ?>
        <?php include __DIR__ . '/../shared/includes/profile-contact.php'; ?>
    </div>

    <div class="mode-card" style="margin-bottom:1.5rem;">
        <h3 class="pfl-section-title"><i class="fas fa-chalkboard-user" aria-hidden="true"></i> Teaching Overview</h3>
        <div class="pfl-stats">
            <div class="pfl-stat">
                <div class="pfl-stat-label"><i class="fas fa-book" aria-hidden="true"></i> Courses</div>
                <div class="pfl-stat-value"><?= (int) $teaching['courses'] ?></div>
                <div class="pfl-stat-sub">created</div>
            </div>
            <div class="pfl-stat">
                <div class="pfl-stat-label"><i class="fas fa-users" aria-hidden="true"></i> Learners</div>
                <div class="pfl-stat-value is-teal"><?= (int) $teaching['learners'] ?></div>
                <div class="pfl-stat-sub"><?= (int) $teaching['completed'] ?> course<?= (int) $teaching['completed'] !== 1 ? 's' : '' ?> completed</div>
            </div>
            <div class="pfl-stat">
                <div class="pfl-stat-label"><i class="fas fa-star" aria-hidden="true"></i> Avg Score</div>
                <div class="pfl-stat-value is-green"><?= number_format((float) $teaching['avg_score'], 1) ?>%</div>
                <div class="pfl-stat-sub">student average</div>
            </div>
            <div class="pfl-stat">
                <div class="pfl-stat-label"><i class="fas fa-thumbs-up" aria-hidden="true"></i> Avg Rating</div>
                <div class="pfl-stat-value is-amber"><?= number_format((float) $teaching['avg_rating'], 1) ?><span style="font-size:1rem;">/5</span></div>
                <div class="pfl-stat-sub">from course reviews</div>
            </div>
        </div>

        <div style="margin-top:1.25rem; padding-top:1.1rem; border-top:1px solid rgba(0,0,0,0.06);">
            <div class="pfl-stat-label" style="margin-bottom:0.6rem;"><i class="fas fa-lightbulb" aria-hidden="true"></i> Areas of Expertise</div>
            <?php if (empty($expertise)): ?>
                <p class="pfl-empty">Tag your courses with skills to build your expertise profile.</p>
            <?php else: ?>
                <div class="pfl-chips">
                    <?php foreach ($expertise as $skill): ?>
                        <span class="pfl-chip"><?= htmlspecialchars($skill['name'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="pfl-grid">
        <div class="mode-card">
            <h3 class="pfl-section-title"><i class="fas fa-link" aria-hidden="true"></i> Quick Links</h3>
            <div class="pfl-quick-links">
                <a class="pfl-quick-link" href="?page=instructor/elearning" data-page="instructor/elearning">
                    <div class="pfl-quick-icon"><i class="fas fa-book" aria-hidden="true"></i></div>
                    <div><strong>E-Learning</strong><span>Manage your courses</span></div>
                </a>
                <a class="pfl-quick-link" href="?page=instructor/analytics" data-page="instructor/analytics">
                    <div class="pfl-quick-icon"><i class="fas fa-chart-line" aria-hidden="true"></i></div>
                    <div><strong>Analytics</strong><span>Track student performance</span></div>
                </a>
                <a class="pfl-quick-link" href="?page=instructor/calendar" data-page="instructor/calendar">
                    <div class="pfl-quick-icon"><i class="fas fa-calendar" aria-hidden="true"></i></div>
                    <div><strong>Calendar</strong><span>View your schedule</span></div>
                </a>
            </div>
        </div>

        <?php include __DIR__ . '/../shared/includes/profile-security.php'; ?>
    </div>

    <?php include __DIR__ . '/../shared/includes/profile-preferences.php'; ?>
</div>
<?php include __DIR__ . '/../shared/includes/profile-scripts.php'; ?>
