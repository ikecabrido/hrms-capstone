<?php
include_once __DIR__ . '/../../../classes/Employee.php';
require_once dirname(__DIR__, 4) . '/classes/grade.php';
require_once dirname(__DIR__, 7) . '/database/db.php';

$employeeClass = new Employee();
$learnerId = (int) ($employeeClass->getEmployeeId() ?? 0);

$grades = [];
$averageScore = 0;

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $stmt = $pdo->prepare("
        SELECT g.id, g.course_id, g.final_score, g.status, g.issued_at,
               c.title AS course_title, c.category
        FROM ld_grade g
        JOIN ld_course c ON c.id = g.course_id
        WHERE g.learner_id = :lid
        ORDER BY g.issued_at DESC
    ");
    $stmt->execute([':lid' => $learnerId]);
    $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($grades)) {
        $total = 0;
        foreach ($grades as $g) $total += (float) $g['final_score'];
        $averageScore = round($total / count($grades), 1);
    }
} catch (Throwable $e) {
    $grades = [];
}
?>

<div class="module-content">
    <div style="margin-bottom:1.5rem; display:flex; align-items:center; gap:0.5rem; font-size:0.9rem; flex-wrap:wrap;">
        <a href="?page=learner/result" style="color:var(--primary); text-decoration:none; font-weight:500;">Results</a>
        <i class="fas fa-chevron-right" style="color:#ccc; font-size:0.7rem;"></i>
        <span style="color:#666;">Grades</span>
    </div>

    <div class="toolbar">
        <div style="display:flex; align-items:center; gap:0.75rem;">
            <h1 style="margin:0; font-size:1.3rem;"><i class="fas fa-graduation-cap" style="color:var(--primary); margin-right:0.4rem;"></i>My Grades</h1>
        </div>
        <div class="toolbar-search">
            <input type="search" id="grade-search" placeholder="Search courses..." />
        </div>
    </div>

    <div class="analytics-cards" style="margin-bottom:1.5rem;">
        <div class="analytics-card">
            <h2>Total Grades</h2>
            <p class="analytics-value"><?= count($grades) ?></p>
        </div>
        <div class="analytics-card">
            <h2>Average Score</h2>
            <p class="analytics-value"><?= $averageScore ?>%</p>
        </div>
        <div class="analytics-card">
            <h2>Passed</h2>
            <p class="analytics-value"><?= count(array_filter($grades, fn($g) => $g['status'] === 'passed')) ?></p>
        </div>
    </div>

    <div class="mode-card">
        <?php if (empty($grades)): ?>
            <div style="text-align:center; padding:3rem; color:#999;">
                <i class="fas fa-award" style="font-size:2.5rem; margin-bottom:1rem; display:block;"></i>
                <h3>No grades yet</h3>
                <p>Complete courses and quizzes to see your grades here.</p>
            </div>
        <?php else: ?>
            <div class="cards-grid" id="grades-grid">
                <?php foreach ($grades as $grade):
                    $scoreColor = $grade['final_score'] >= 75 ? '#28a745' : ($grade['final_score'] >= 50 ? '#ffc107' : '#dc3545');
                ?>
                    <div class="content-card-item" data-search="<?= htmlspecialchars(strtolower($grade['course_title'])) ?>" style="cursor:default;">
                        <div class="content-card-thumb" style="background:linear-gradient(135deg, <?= $scoreColor ?>20, <?= $scoreColor ?>08);">
                            <span style="font-size:1.8rem; font-weight:700; color:<?= $scoreColor ?>;"><?= round($grade['final_score']) ?>%</span>
                        </div>
                        <div class="content-card-body">
                            <h3 class="content-card-title"><?= htmlspecialchars($grade['course_title']) ?></h3>
                            <div class="content-card-meta">
                                <span class="pill" style="background:<?= $scoreColor ?>20; color:<?= $scoreColor ?>;"><?= ucfirst($grade['status'] ?? 'graded') ?></span>
                                <?php if ($grade['category']): ?>
                                    <span class="pill"><?= htmlspecialchars($grade['category']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="content-card-footer">
                            <span><?= $grade['issued_at'] ? date('M j, Y', strtotime($grade['issued_at'])) : 'N/A' ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('grade-search').addEventListener('input', function() {
    var q = this.value.toLowerCase();
    document.querySelectorAll('.content-card-item[data-search]').forEach(function(card) {
        card.style.display = card.dataset.search.indexOf(q) !== -1 ? '' : 'none';
    });
});
</script>
