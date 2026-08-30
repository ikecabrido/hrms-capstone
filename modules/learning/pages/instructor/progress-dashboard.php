<?php
include_once __DIR__ . '/../../classes/Employee.php';
require_once dirname(__DIR__, 4) . '/database/db.php';

$employeeClass = new Employee();
$instructorId = (int) ($employeeClass->getEmployeeId() ?? 0);

$courses = [];
$learners = [];
$stats = ['totalEnrolled' => 0, 'completed' => 0, 'inProgress' => 0, 'avgScore' => 0, 'atRisk' => 0];
$recentActivity = [];
$gradeDistribution = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'F' => 0];
$courseProgress = [];
$weeklyTrend = [];

try {
    $pdo = (new Database())->getConnection();

    // Courses by this instructor
    $stmt = $pdo->prepare("SELECT id, title, status FROM ld_course WHERE instructor_id = :iid ORDER BY title ASC");
    $stmt->execute([':iid' => $instructorId]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $courseIds = array_column($courses, 'id');
    if (!empty($courseIds)) {
        $placeholders = implode(',', array_fill(0, count($courseIds), '?'));

        // Overall stats
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_enrollment WHERE course_id IN ($placeholders)");
        $stmt->execute($courseIds);
        $stats['totalEnrolled'] = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_enrollment WHERE course_id IN ($placeholders) AND status = 'completed'");
        $stmt->execute($courseIds);
        $stats['completed'] = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM ld_enrollment WHERE course_id IN ($placeholders) AND status IN ('enrolled','in_progress')");
        $stmt->execute($courseIds);
        $stats['inProgress'] = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT ROUND(COALESCE(AVG(final_score), 0), 1) FROM ld_grade WHERE course_id IN ($placeholders)");
        $stmt->execute($courseIds);
        $stats['avgScore'] = (float) $stmt->fetchColumn();

        // At-risk: inactive 14+ days or failing
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT e.learner_id) FROM ld_enrollment e WHERE e.course_id IN ($placeholders) AND e.status IN ('enrolled','in_progress') AND (e.last_accessed_at IS NULL OR e.last_accessed_at < DATE_SUB(NOW(), INTERVAL 14 DAY) OR e.learner_id IN (SELECT learner_id FROM ld_grade WHERE course_id IN ($placeholders) AND status = 'failed'))");
        $stmt->execute(array_merge($courseIds, $courseIds));
        $stats['atRisk'] = (int) $stmt->fetchColumn();

        // Grade distribution
        $stmt = $pdo->prepare("SELECT final_score FROM ld_grade WHERE course_id IN ($placeholders)");
        $stmt->execute($courseIds);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $score) {
            $s = (float) $score;
            if ($s >= 90) $gradeDistribution['A']++;
            elseif ($s >= 80) $gradeDistribution['B']++;
            elseif ($s >= 70) $gradeDistribution['C']++;
            elseif ($s >= 60) $gradeDistribution['D']++;
            else $gradeDistribution['F']++;
        }

        // Per-course progress
        foreach ($courses as $c) {
            $cid = (int) $c['id'];
            $stmt = $pdo->prepare("SELECT e.status, COUNT(*) AS cnt FROM ld_enrollment e WHERE e.course_id = :cid GROUP BY e.status");
            $stmt->execute([':cid' => $cid]);
            $statuses = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            $total = array_sum($statuses);
            $completed = $statuses['completed'] ?? 0;
            $courseProgress[] = [
                'id' => $cid,
                'title' => $c['title'],
                'status' => $c['status'],
                'enrolled' => $statuses['enrolled'] ?? 0,
                'in_progress' => $statuses['in_progress'] ?? 0,
                'completed' => $completed,
                'total' => $total,
                'completion_pct' => $total > 0 ? round(($completed / $total) * 100) : 0,
            ];
        }

        // Recent activity (last 10 enrollments/completions)
        $stmt = $pdo->prepare("SELECT e.learner_id, CONCAT(emp.first_name, ' ', emp.last_name) AS name, c.title AS course_title, e.status, e.last_accessed_at, e.completed_at FROM ld_enrollment e JOIN ld_course c ON c.id = e.course_id JOIN em_employees emp ON emp.employee_id = e.learner_id WHERE e.course_id IN ($placeholders) ORDER BY COALESCE(e.completed_at, e.last_accessed_at, e.created_at) DESC LIMIT 10");
        $stmt->execute($courseIds);
        $recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Weekly enrollment trend (last 8 weeks)
        $stmt = $pdo->prepare("SELECT YEARWEEK(enrolled_at, 1) AS week, COUNT(*) AS cnt FROM ld_enrollment WHERE course_id IN ($placeholders) AND enrolled_at >= DATE_SUB(NOW(), INTERVAL 8 WEEK) GROUP BY week ORDER BY week ASC");
        $stmt->execute($courseIds);
        $weeklyTrend = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    $courses = [];
}
?>
<div class="module-content">
    <div class="toolbar">
        <div class="toolbar-actions" style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
            
            <select id="course-filter" style="padding:0.6rem 1rem; border-radius:8px; border:1px solid var(--border); font-size:0.9rem;">
                <option value="all">All Courses</option>
                <?php foreach ($courses as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['title']) ?></option>
                <?php endforeach; ?>
            </select>
            <button onclick="exportCSV()" style="padding:0.6rem 1.2rem; background:var(--primary); color:white; border:none; border-radius:8px; cursor:pointer; font-weight:600; font-size:0.85rem;">Export CSV</button>
            <button onclick="window.print()" style="padding:0.6rem 1.2rem; background:var(--primary); color:white; border:none; border-radius:8px; cursor:pointer; font-weight:600; font-size:0.85rem;">Print Report</button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:1rem; margin-top:1rem;">
        <div style="padding:1.25rem; background:linear-gradient(135deg, rgba(32,0,130,0.08), rgba(81,70,183,0.04)); border-radius:12px; border:1px solid rgba(32,0,130,0.1);">
            <div style="font-size:0.7rem; font-weight:700; color:var(--primary); text-transform:uppercase; letter-spacing:0.08em;">Total Enrolled</div>
            <div style="font-size:2rem; font-weight:800; color:var(--text); margin-top:0.3rem;"><?= $stats['totalEnrolled'] ?></div>
            <div style="font-size:0.8rem; color:#666; margin-top:0.2rem;">across <?= count($courses) ?> courses</div>
        </div>
        <div style="padding:1.25rem; background:linear-gradient(135deg, rgba(16,185,129,0.08), rgba(16,185,129,0.04)); border-radius:12px; border:1px solid rgba(16,185,129,0.1);">
            <div style="font-size:0.7rem; font-weight:700; color:var(--primary); text-transform:uppercase; letter-spacing:0.08em;">Completed</div>
            <div style="font-size:2rem; font-weight:800; color:var(--text); margin-top:0.3rem;"><?= $stats['completed'] ?></div>
            <div style="font-size:0.8rem; color:#666; margin-top:0.2rem;"><?= $stats['totalEnrolled'] > 0 ? round(($stats['completed'] / $stats['totalEnrolled']) * 100) : 0 ?>% completion rate</div>
        </div>
        <div style="padding:1.25rem; background:linear-gradient(135deg, rgba(59,130,246,0.08), rgba(59,130,246,0.04)); border-radius:12px; border:1px solid rgba(59,130,246,0.1);">
            <div style="font-size:0.7rem; font-weight:700; color:var(--accent); text-transform:uppercase; letter-spacing:0.08em;">In Progress</div>
            <div style="font-size:2rem; font-weight:800; color:var(--text); margin-top:0.3rem;"><?= $stats['inProgress'] ?></div>
            <div style="font-size:0.8rem; color:#666; margin-top:0.2rem;">active learners</div>
        </div>
        <div style="padding:1.25rem; background:linear-gradient(135deg, rgba(245,158,11,0.08), rgba(245,158,11,0.04)); border-radius:12px; border:1px solid rgba(245,158,11,0.1);">
            <div style="font-size:0.7rem; font-weight:700; color:#d97706; text-transform:uppercase; letter-spacing:0.08em;">Avg Score</div>
            <div style="font-size:2rem; font-weight:800; color:var(--text); margin-top:0.3rem;"><?= $stats['avgScore'] ?>%</div>
            <div style="font-size:0.8rem; color:#666; margin-top:0.2rem;">overall average</div>
        </div>
        <div style="padding:1.25rem; background:linear-gradient(135deg, rgba(239,68,68,0.08), rgba(239,68,68,0.04)); border-radius:12px; border:1px solid rgba(239,68,68,0.1);">
            <div style="font-size:0.7rem; font-weight:700; color:var(--danger); text-transform:uppercase; letter-spacing:0.08em;">At Risk</div>
            <div style="font-size:2rem; font-weight:800; color:var(--text); margin-top:0.3rem;"><?= $stats['atRisk'] ?></div>
            <div style="font-size:0.8rem; color:#666; margin-top:0.2rem;">inactive 14+ days</div>
        </div>
    </div>

    <!-- Charts Row -->
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-top:1.5rem;">
        <!-- Grade Distribution -->
        <div style="padding:1.25rem; background:var(--surface, #fff); border:1px solid rgba(32,0,130,0.1); border-radius:12px;">
            <h3 style="margin:0 0 1rem; font-size:1rem; color:var(--text);"><i class="fas fa-chart-bar" style="margin-right:0.4rem; opacity:0.6;"></i> Grade Distribution</h3>
            <?php
            $maxGrade = max(max(array_values($gradeDistribution)), 1);
            // Blue palette: dark to light
            $gradeColors = [
                'A' => '#1a0052',
                'B' => 'var(--primary)',
                'C' => 'var(--accent)',
                'D' => 'rgba(81,70,183,0.6)',
                'F' => 'rgba(81,70,183,0.35)',
            ];
            $totalGrades = array_sum($gradeDistribution);
            ?>
            <div style="display:flex; align-items:flex-end; gap:0.75rem; height:180px; padding:1rem 0.5rem 0;">
                <?php foreach ($gradeDistribution as $grade => $count):
                    $barH = $maxGrade > 0 ? ($count / $maxGrade) * 100 : 0;
                    $pct = $totalGrades > 0 ? round(($count / $totalGrades) * 100) : 0;
                ?>
                <div style="flex:1; display:flex; flex-direction:column; align-items:center; gap:0.35rem; height:100%; justify-content:flex-end;">
                    <span style="font-size:0.75rem; font-weight:800; color:var(--text);"><?= $count ?></span>
                    <span style="font-size:0.65rem; color:rgba(32,0,130,0.4);"><?= $pct ?>%</span>
                    <div style="width:100%; height:<?= $barH ?>%; min-height:4px; background:<?= $gradeColors[$grade] ?>; border-radius:6px 6px 2px 2px; transition:height 0.5s; position:relative;">
                        <div style="position:absolute; inset:0; border-radius:6px 6px 2px 2px; background:linear-gradient(to top, transparent, rgba(255,255,255,0.2));"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <!-- Grade Labels -->
            <div style="display:flex; gap:0.75rem; margin-top:0.5rem; padding:0 0.5rem;">
                <?php foreach ($gradeDistribution as $grade => $count):
                    $labelDescs = ['A' => 'Excellent (90-100)', 'B' => 'Good (80-89)', 'C' => 'Average (70-79)', 'D' => 'Below Avg (60-69)', 'F' => 'Failing (<60)'];
                ?>
                <div style="flex:1; text-align:center;">
                    <div style="width:12px; height:12px; border-radius:3px; background:<?= $gradeColors[$grade] ?>; margin:0 auto 0.2rem;"></div>
                    <div style="font-size:0.7rem; font-weight:800; color:var(--text);"><?= $grade ?></div>
                    <div style="font-size:0.6rem; color:rgba(32,0,130,0.4); line-height:1.2;"><?= $labelDescs[$grade] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <!-- Total -->
            <div style="margin-top:0.75rem; padding-top:0.75rem; border-top:1px solid rgba(32,0,130,0.08); text-align:center;">
                <span style="font-size:0.78rem; color:rgba(32,0,130,0.5);">Total Grades: <strong style="color:var(--text);"><?= $totalGrades ?></strong></span>
            </div>
        </div>

        <!-- Weekly Enrollment Trend -->
        <div style="padding:1.25rem; background:var(--surface, #fff); border:1px solid rgba(32,0,130,0.1); border-radius:12px;">
            <h3 style="margin:0 0 1rem; font-size:1rem; color:var(--text);"> Weekly Enrollments</h3>
            <?php if (empty($weeklyTrend)): ?>
            <p style="color:#999; text-align:center; padding:2rem;">No enrollment data yet.</p>
            <?php else: ?>
            <div style="display:flex; align-items:flex-end; gap:0.5rem; height:120px; padding-top:1rem;">
                <?php
                $maxWeekly = max(max(array_column($weeklyTrend, 'cnt')), 1);
                foreach ($weeklyTrend as $w):
                    $h = ($w['cnt'] / $maxWeekly) * 100;
                    $weekLabel = date('M j', strtotime(substr($w['week'], 0, 4) . '-W' . substr($w['week'], 4) . '-1'));
                ?>
                <div style="flex:1; display:flex; flex-direction:column; align-items:center; gap:0.25rem;">
                    <span style="font-size:0.7rem; font-weight:700; color:var(--primary);"><?= $w['cnt'] ?></span>
                    <div style="width:100%; height:<?= $h ?>%; background:linear-gradient(to top, var(--primary), rgba(81,70,183,0.6)); border-radius:4px 4px 0 0; min-height:4px;"></div>
                    <span style="font-size:0.6rem; color:#999;"><?= $weekLabel ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Course Progress -->
    <div style="margin-top:1.5rem; padding:1.25rem; background:var(--surface, #fff); border:1px solid rgba(32,0,130,0.1); border-radius:12px;">
        <h3 style="margin:0 0 1rem; font-size:1rem; color:var(--text);"> Course Progress</h3>
        <?php if (empty($courseProgress)): ?>
        <p style="color:#999; text-align:center; padding:2rem;">No courses found.</p>
        <?php else: ?>
        <div style="display:grid; gap:0.75rem;">
            <?php foreach ($courseProgress as $cp): ?>
            <div class="progress-course-row" data-course-id="<?= $cp['id'] ?>" style="display:grid; grid-template-columns:1fr 100px 80px 80px 80px 60px; gap:0.75rem; align-items:center; padding:0.75rem; border:1px solid rgba(32,0,130,0.06); border-radius:8px; background:#fafbff;">
                <div>
                    <strong style="color:var(--text); font-size:0.9rem;"><?= htmlspecialchars($cp['title']) ?></strong>
                    <span style="padding:0.15rem 0.4rem; background:rgba(32,0,130,0.06); border-radius:4px; font-size:0.65rem; font-weight:600; margin-left:0.5rem;"><?= ucfirst($cp['status']) ?></span>
                </div>
                <div>
                    <div style="height:8px; background:#e5e7eb; border-radius:4px; overflow:hidden;">
                        <div style="height:100%; width:<?= $cp['completion_pct'] ?>%; background:<?= $cp['completion_pct'] >= 70 ? 'var(--primary)' : ($cp['completion_pct'] >= 30 ? 'var(--accent)' : 'var(--danger)') ?>; border-radius:4px;"></div>
                    </div>
                    <div style="font-size:0.7rem; color:#999; margin-top:0.2rem;"><?= $cp['completion_pct'] ?>%</div>
                </div>
                <div style="text-align:center;"><span style="font-size:0.75rem; color:var(--accent); font-weight:600;"><?= $cp['enrolled'] + $cp['in_progress'] ?></span><br><span style="font-size:0.65rem; color:#999;">active</span></div>
                <div style="text-align:center;"><span style="font-size:0.75rem; color:var(--primary); font-weight:600;"><?= $cp['completed'] ?></span><br><span style="font-size:0.65rem; color:#999;">done</span></div>
                <div style="text-align:center;"><span style="font-size:0.75rem; color:#666; font-weight:600;"><?= $cp['total'] ?></span><br><span style="font-size:0.65rem; color:#999;">total</span></div>
                <div style="text-align:right;"><a href="?page=instructor/analytics&course_id=<?= $cp['id'] ?>" style="font-size:0.75rem; color:var(--primary); text-decoration:none; font-weight:600;">View →</a></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Recent Activity -->
    <div style="margin-top:1.5rem; padding:1.25rem; background:var(--surface, #fff); border:1px solid rgba(32,0,130,0.1); border-radius:12px;">
        <h3 style="margin:0 0 1rem; font-size:1rem; color:var(--text);"> Recent Activity</h3>
        <?php if (empty($recentActivity)): ?>
        <p style="color:#999; text-align:center; padding:2rem;">No recent activity.</p>
        <?php else: ?>
        <div style="display:grid; gap:0.5rem;">
            <?php foreach ($recentActivity as $act):
                $statusColors = ['completed' => 'var(--primary)', 'in_progress' => 'var(--accent)', 'enrolled' => 'var(--accent)', 'withdrawn' => 'var(--danger)'];
                $color = $statusColors[$act['status']] ?? '#666';
                $time = $act['completed_at'] ?? $act['last_accessed_at'] ?? '';
                $timeAgo = $time ? date('M j, g:i A', strtotime($time)) : 'Unknown';
            ?>
            <div style="display:flex; align-items:center; gap:0.75rem; padding:0.6rem 0; border-bottom:1px solid rgba(32,0,130,0.05);">
                <div style="width:8px; height:8px; border-radius:50%; background:<?= $color ?>; flex-shrink:0;"></div>
                <div style="flex:1; min-width:0;">
                    <span style="font-weight:600; color:var(--text); font-size:0.85rem;"><?= htmlspecialchars($act['name']) ?></span>
                    <span style="color:#666; font-size:0.85rem;"> — <?= htmlspecialchars($act['course_title']) ?></span>
                </div>
                <span style="padding:0.2rem 0.5rem; background:<?= $color ?>15; color:<?= $color ?>; border-radius:999px; font-size:0.7rem; font-weight:700; white-space:nowrap;"><?= ucfirst(str_replace('_', ' ', $act['status'])) ?></span>
                <span style="font-size:0.75rem; color:#999; white-space:nowrap;"><?= $time ? date('M j, g:i A', strtotime($time)) : '' ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function exportCSV() {
    var courseId = document.getElementById('course-filter').value;
    var url = 'pages/instructor/ajax/export-progress-report.php?format=csv';
    if (courseId !== 'all') url += '&course_id=' + courseId;
    window.location.href = url;
}

(function() {
    var filter = document.getElementById('course-filter');
    if (!filter) return;
    filter.addEventListener('change', function() {
        var val = this.value;
        document.querySelectorAll('.progress-course-row').forEach(function(row) {
            if (val === 'all' || row.dataset.courseId === val) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
})();
</script>
