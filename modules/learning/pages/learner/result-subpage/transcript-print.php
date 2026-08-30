<?php
/**
 * Print-friendly transcript view.
 * Clean, tabular layout for browser printing.
 * Access: ?page=learner/result-subpage/transcript-print
 */
session_start();
$learnerId = isset($_SESSION['employee_id']) ? (int)$_SESSION['employee_id'] : 0;
if ($learnerId <= 0) { header('Location: /itsar/modules/learning/index.php'); exit; }

require_once dirname(__DIR__, 8) . '/database/db.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // Get learner info
    $stmt = $pdo->prepare("SELECT employee_id, first_name, last_name, email FROM em_employees WHERE employee_id = :eid");
    $stmt->execute([':eid' => $learnerId]);
    $learner = $stmt->fetch();
    if (!$learner) { echo 'Learner not found'; exit; }

    // Get all enrollments with grades
    $stmt = $pdo->prepare("
        SELECT e.course_id, c.title, c.category, e.status, e.enrolled_at, e.completed_at,
               g.final_score, g.status AS grade_status,
               ct.verification_code
        FROM ld_enrollment e
        JOIN ld_course c ON c.id = e.course_id
        LEFT JOIN ld_grade g ON g.learner_id = e.learner_id AND g.course_id = e.course_id
        LEFT JOIN ld_certificate ct ON ct.learner_id = e.learner_id AND ct.course_id = e.course_id
        WHERE e.learner_id = :lid
        ORDER BY e.completed_at DESC, e.enrolled_at DESC
    ");
    $stmt->execute([':lid' => $learnerId]);
    $courses = $stmt->fetchAll();

    // Calculate stats
    $total = count($courses);
    $completed = 0;
    $totalScore = 0;
    $scored = 0;
    foreach ($courses as $c) {
        if ($c['status'] === 'completed') {
            $completed++;
            if ($c['final_score'] !== null) {
                $totalScore += (float)$c['final_score'];
                $scored++;
            }
        }
    }
    $avgScore = $scored > 0 ? round($totalScore / $scored, 1) : 0;
    $completionRate = $total > 0 ? round(($completed / $total) * 100, 1) : 0;

    $printDate = date('F j, Y \a\t g:i A');
} catch (Exception $e) { echo 'Error: ' . $e->getMessage(); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Academic Transcript — <?= htmlspecialchars($learner['first_name'] . ' ' . $learner['last_name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #200082; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f5f5; padding: 2rem; }
        .transcript { max-width: 900px; margin: 0 auto; background: #fff; border-radius: 16px; padding: 2.5rem; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .transcript-header { text-align: center; margin-bottom: 2rem; border-bottom: 2px solid var(--primary); padding-bottom: 1.5rem; }
        .transcript-header h1 { font-size: 1.5rem; color: var(--primary); margin-bottom: 0.5rem; }
        .transcript-header p { color: #666; font-size: 0.85rem; }
        .learner-info { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-bottom: 1.5rem; font-size: 0.85rem; }
        .learner-info span { color: #666; }
        .learner-info strong { color: #1a1a2e; }
        .stats { display: flex; gap: 2rem; margin-bottom: 1.5rem; padding: 1rem; background: #f8f8ff; border-radius: 10px; }
        .stat { text-align: center; flex: 1; }
        .stat-value { font-size: 1.4rem; font-weight: 700; color: var(--primary); }
        .stat-label { font-size: 0.75rem; color: #666; margin-top: 0.25rem; }
        table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
        th { background: var(--primary); color: #fff; padding: 0.6rem 0.5rem; text-align: left; font-weight: 600; }
        td { padding: 0.5rem; border-bottom: 1px solid #eee; }
        tr:nth-child(even) { background: #fafafa; }
        .status-completed { color: #16a34a; font-weight: 600; }
        .status-in_progress { color: #d97706; font-weight: 600; }
        .status-enrolled { color: #2563eb; font-weight: 600; }
        .transcript-footer { margin-top: 1.5rem; font-size: 0.75rem; color: #999; text-align: center; }
        @media print {
            body { background: none; padding: 0; }
            .transcript { box-shadow: none; padding: 1rem; }
            .no-print { display: none !important; }
            @page { margin: 1cm; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align:center;margin-bottom:1rem;">
        <button onclick="window.print()" style="padding:0.5rem 1.5rem;background:var(--primary);color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:600;">Print Transcript</button>
    </div>
    <div class="transcript">
        <div class="transcript-header">
            <h1>Academic Transcript</h1>
            <p>Learning &amp; Development — <?= htmlspecialchars($printDate) ?></p>
        </div>
        <div class="learner-info">
            <div><span>Name: </span><strong><?= htmlspecialchars($learner['first_name'] . ' ' . $learner['last_name']) ?></strong></div>
            <div><span>Email: </span><strong><?= htmlspecialchars($learner['email']) ?></strong></div>
            <div><span>Employee ID: </span><strong><?= $learner['employee_id'] ?></strong></div>
            <div><span>Total Courses: </span><strong><?= $total ?></strong></div>
        </div>
        <div class="stats">
            <div class="stat"><div class="stat-value"><?= $completed ?>/<?= $total ?></div><div class="stat-label">Completed</div></div>
            <div class="stat"><div class="stat-value"><?= $completionRate ?>%</div><div class="stat-label">Completion Rate</div></div>
            <div class="stat"><div class="stat-value"><?= $avgScore ?></div><div class="stat-label">Average Score</div></div>
        </div>
        <table>
            <thead>
                <tr><th>Course</th><th>Category</th><th>Status</th><th>Score</th><th>Enrolled</th><th>Completed</th><th>Certificate</th></tr>
            </thead>
            <tbody>
                <?php foreach ($courses as $c): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($c['title']) ?></strong></td>
                    <td><?= htmlspecialchars($c['category'] ?? '-') ?></td>
                    <td class="status-<?= $c['status'] ?>"><?= ucfirst(str_replace('_', ' ', $c['status'])) ?></td>
                    <td><?= $c['final_score'] !== null ? number_format($c['final_score'], 1) . '%' : '-' ?></td>
                    <td><?= $c['enrolled_at'] ? date('M j, Y', strtotime($c['enrolled_at'])) : '-' ?></td>
                    <td><?= $c['completed_at'] ? date('M j, Y', strtotime($c['completed_at'])) : '-' ?></td>
                    <td><?= $c['verification_code'] ? '&#10003;' : '-' ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($courses)): ?>
                <tr><td colspan="7" style="text-align:center;color:#999;padding:2rem;">No enrollment records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="transcript-footer">
            This transcript is an official record of learning activities. Generated <?= htmlspecialchars($printDate) ?>.
        </div>
    </div>
</body>
</html>
