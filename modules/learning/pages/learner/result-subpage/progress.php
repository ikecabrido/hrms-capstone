<?php
include_once __DIR__ . '/../../../classes/Employee.php';
require_once dirname(__DIR__, 4) . '/classes/enrollment.php';
require_once dirname(__DIR__, 4) . '/classes/progress.php';
require_once dirname(__DIR__, 7) . '/database/db.php';

$employeeClass = new Employee();
$learnerId = (int) ($employeeClass->getEmployeeId() ?? 0);

$enrollments = [];

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $stmt = $pdo->prepare("
        SELECT e.id, e.course_id, e.status, e.enrolled_at, e.completed_at,
               c.title AS course_title, c.category
        FROM ld_enrollment e
        JOIN ld_course c ON c.id = e.course_id
        WHERE e.learner_id = :lid
        ORDER BY e.enrolled_at DESC
    ");
    $stmt->execute([':lid' => $learnerId]);
    $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $progressObj = new Progress($pdo);
    foreach ($enrollments as &$en) {
        $en['percent'] = $progressObj->getPercentComplete((int) $en['id'], (int) $en['course_id']);

        $itemStmt = $pdo->prepare("SELECT item_type, reference_id, status FROM ld_progress WHERE enrollment_id = :eid");
        $itemStmt->execute([':eid' => $en['id']]);
        $en['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

        $en['completed_items'] = count(array_filter($en['items'], fn($i) => $i['status'] === 'completed'));
        $en['total_items'] = count($en['items']);
    }
    unset($en);
} catch (Throwable $e) {
    $enrollments = [];
}

$totalEnrolled = count($enrollments);
$totalCompleted = count(array_filter($enrollments, fn($e) => $e['status'] === 'completed'));
$totalInProgress = count(array_filter($enrollments, fn($e) => $e['status'] === 'in_progress' || $e['status'] === 'enrolled'));
?>

<div class="module-content">
    <div style="margin-bottom:1.5rem; display:flex; align-items:center; gap:0.5rem; font-size:0.9rem; flex-wrap:wrap;">
        <a href="?page=learner/result" style="color:var(--primary); text-decoration:none; font-weight:500;">Results</a>
        <i class="fas fa-chevron-right" style="color:#ccc; font-size:0.7rem;"></i>
        <span style="color:#666;">Progress</span>
    </div>

    <div class="toolbar">
        <div style="display:flex; align-items:center; gap:0.75rem;">
            <h1 style="margin:0; font-size:1.3rem;"><i class="fas fa-chart-line" style="color:var(--primary); margin-right:0.4rem;"></i>My Progress</h1>
        </div>
        <div class="toolbar-search">
            <input type="search" id="progress-search" placeholder="Search courses..." />
        </div>
    </div>

    <div class="analytics-cards" style="margin-bottom:1.5rem;">
        <div class="analytics-card">
            <h2>Enrolled</h2>
            <p class="analytics-value"><?= $totalEnrolled ?></p>
        </div>
        <div class="analytics-card">
            <h2>In Progress</h2>
            <p class="analytics-value"><?= $totalInProgress ?></p>
        </div>
        <div class="analytics-card">
            <h2>Completed</h2>
            <p class="analytics-value"><?= $totalCompleted ?></p>
        </div>
        <div class="analytics-card">
            <h2>Completion Rate</h2>
            <p class="analytics-value"><?= $totalEnrolled > 0 ? round(($totalCompleted / $totalEnrolled) * 100) : 0 ?>%</p>
        </div>
    </div>

    <div class="mode-card">
        <?php if (empty($enrollments)): ?>
            <div style="text-align:center; padding:3rem; color:#999;">
                <i class="fas fa-chart-line" style="font-size:2.5rem; margin-bottom:1rem; display:block;"></i>
                <h3>No progress data yet</h3>
                <p>Enroll in courses to start tracking your progress.</p>
            </div>
        <?php else: ?>
            <div class="cards-grid" id="progress-grid">
                <?php foreach ($enrollments as $en):
                    $pct = round($en['percent']);
                    $statusColor = $en['status'] === 'completed' ? '#28a745' : ($pct > 0 ? 'var(--primary)' : '#999');
                ?>
                    <div class="content-card-item" data-search="<?= htmlspecialchars(strtolower($en['course_title'])) ?>">
                        <div class="content-card-body">
                            <h3 class="content-card-title" style="margin-bottom:0.5rem;"><?= htmlspecialchars($en['course_title']) ?></h3>
                            <div style="display:flex; gap:0.5rem; margin-bottom:0.75rem;">
                                <span class="pill" style="background:<?= $statusColor ?>20; color:<?= $statusColor ?>;"><?= ucfirst($en['status']) ?></span>
                                <?php if ($en['category']): ?>
                                    <span class="pill"><?= htmlspecialchars($en['category']) ?></span>
                                <?php endif; ?>
                            </div>
                            <!-- Progress bar -->
                            <div style="margin-bottom:0.5rem;">
                                <div style="display:flex; justify-content:space-between; margin-bottom:0.25rem;">
                                    <span style="font-size:0.8rem; color:var(--text-muted);"><?= $en['completed_items'] ?>/<?= $en['total_items'] ?> items</span>
                                    <span style="font-size:0.8rem; font-weight:600; color:<?= $statusColor ?>;"><?= $pct ?>%</span>
                                </div>
                                <div style="height:6px; background:rgba(32,0,130,0.08); border-radius:99px; overflow:hidden;">
                                    <div style="width:<?= $pct ?>%; height:100%; background:<?= $statusColor ?>; border-radius:99px; transition:width 0.5s;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="content-card-footer">
                            <span><?= $en['enrolled_at'] ? date('M j, Y', strtotime($en['enrolled_at'])) : 'N/A' ?></span>
                            <a href="?page=learner/study-subpage/course&course_id=<?= $en['course_id'] ?>" style="color:var(--primary); text-decoration:none; font-weight:600; font-size:0.85rem;">View</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('progress-search').addEventListener('input', function() {
    var q = this.value.toLowerCase();
    document.querySelectorAll('.content-card-item[data-search]').forEach(function(card) {
        card.style.display = card.dataset.search.indexOf(q) !== -1 ? '' : 'none';
    });
});
</script>
