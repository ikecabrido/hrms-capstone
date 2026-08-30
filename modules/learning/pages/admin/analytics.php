<?php
include_once __DIR__ . '/../../classes/Employee.php';
require_once dirname(__DIR__, 4) . '/database/db.php';

$employeeClass = new Employee();

$stats = ['users' => 0, 'courses' => 0, 'active_enrollments' => 0, 'passed_courses' => 0, 'instructors' => 0, 'learners' => 0, 'completed' => 0, 'avg_score' => 0, 'pending_reports' => 0];
$topCourses = [];
$monthlyEnrollments = [];
$recentGrades = [];

try {
    $pdo = (new Database())->getConnection();
    $stats['users'] = (int) $pdo->query('SELECT COUNT(*) FROM em_employees')->fetchColumn();
    $stats['courses'] = (int) $pdo->query("SELECT COUNT(*) FROM ld_course WHERE status IN ('active','draft')")->fetchColumn();
    $stats['active_enrollments'] = (int) $pdo->query("SELECT COUNT(*) FROM ld_enrollment WHERE status IN ('enrolled','in_progress')")->fetchColumn();
    $stats['completed'] = (int) $pdo->query("SELECT COUNT(*) FROM ld_enrollment WHERE status = 'completed'")->fetchColumn();
    $stats['instructors'] = (int) $pdo->query("SELECT COUNT(DISTINCT instructor_id) FROM ld_course WHERE instructor_id IS NOT NULL")->fetchColumn();
    $stats['learners'] = (int) $pdo->query("SELECT COUNT(DISTINCT learner_id) FROM ld_enrollment")->fetchColumn();
    $stats['avg_score'] = (float) ($pdo->query("SELECT ROUND(COALESCE(AVG(final_score),0),1) FROM ld_grade")->fetchColumn() ?? 0);
    $stats['pending_reports'] = (int) $pdo->query("SELECT COUNT(*) FROM ld_report WHERE status='pending'")->fetchColumn();

    $topCourses = $pdo->query("SELECT c.title, COUNT(e.id) AS enrollment_count, ROUND(COALESCE(AVG(g.final_score),0),1) AS avg_score, SUM(CASE WHEN e.status='completed' THEN 1 ELSE 0 END) AS completed_count FROM ld_course c LEFT JOIN ld_enrollment e ON e.course_id=c.id LEFT JOIN ld_grade g ON g.course_id=c.id WHERE c.status='active' GROUP BY c.id,c.title ORDER BY enrollment_count DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

    $monthlyEnrollments = $pdo->query("SELECT DATE_FORMAT(enrolled_at,'%Y-%m') AS month, COUNT(*) AS cnt FROM ld_enrollment WHERE enrolled_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY month ORDER BY month ASC")->fetchAll(PDO::FETCH_ASSOC);

    $recentGrades = $pdo->query("SELECT g.final_score, g.status, c.title FROM ld_grade g JOIN ld_course c ON c.id=g.course_id ORDER BY g.issued_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {}

$completionRate = ($stats['active_enrollments'] + $stats['completed']) > 0 ? round(($stats['completed'] / max(1, $stats['active_enrollments'] + $stats['completed'])) * 100) : 0;
?>
<div class="module-content analytics-dashboard">
    <h2 style="margin:0 0 1.25rem; font-size:1.2rem; color:var(--text);"><i class="fas fa-chart-bar" style="margin-right:0.4rem;"></i> Analytics Dashboard</h2>

    <div class="analytics-cards" style="margin-bottom:1.5rem;">
        <div class="analytics-card" style="border-top:3px solid var(--primary);"><h2><i class="fas fa-users" style="margin-right:0.4rem;opacity:0.6;"></i> Total Users</h2><p class="analytics-value"><?= $stats['users'] ?></p><div style="font-size:0.85rem;color:#999;"><?= $stats['learners'] ?> learners &bull; <?= $stats['instructors'] ?> instructors</div></div>
        <div class="analytics-card" style="border-top:3px solid var(--primary);"><h2><i class="fas fa-graduation-cap" style="margin-right:0.4rem;opacity:0.6;"></i> Active Courses</h2><p class="analytics-value"><?= $stats['courses'] ?></p><div style="font-size:0.85rem;color:#999;"><?= $completionRate ?>% completion rate</div></div>
        <div class="analytics-card" style="border-top:3px solid var(--accent);"><h2><i class="fas fa-book-open" style="margin-right:0.4rem;opacity:0.6;"></i> Enrollments</h2><p class="analytics-value"><?= $stats['active_enrollments'] + $stats['completed'] ?></p><div style="font-size:0.85rem;color:#999;"><?= $stats['active_enrollments'] ?> active &bull; <?= $stats['completed'] ?> completed</div></div>
        <div class="analytics-card" style="border-top:3px solid var(--accent);"><h2><i class="fas fa-star" style="margin-right:0.4rem;opacity:0.6;"></i> Avg Score</h2><p class="analytics-value"><?= $stats['avg_score'] ?>%</p><div style="font-size:0.85rem;color:#999;">across all courses</div></div>
    </div>

    <div class="tab-container">
        <div class="tab-list">
            <button type="button" class="tab-item active" data-tab="tab-overview">Overview</button>
            <button type="button" class="tab-item" data-tab="tab-courses">Courses</button>
            <button type="button" class="tab-item" data-tab="tab-trends">Trends</button>
        </div>

        <div class="tab-content active" data-tab="tab-overview">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
                <div class="mode-card">
                    <h3><i class="fas fa-chart-pie" style="color:var(--primary);margin-right:0.5rem;"></i> Platform Summary</h3>
                    <div style="padding:1.5rem 0;">
                        <div style="display:flex;align-items:center;gap:2rem;">
                            <div style="position:relative;width:120px;height:120px;">
                                <svg width="120" height="120" viewBox="0 0 120 120">
                                    <circle cx="60" cy="60" r="50" fill="none" stroke="var(--border)" stroke-width="12"/>
                                    <circle cx="60" cy="60" r="50" fill="none" stroke="var(--primary)" stroke-width="12" stroke-dasharray="<?= $completionRate * 3.14 ?> <?= 314 - ($completionRate * 3.14) ?>" stroke-dashoffset="78.5" stroke-linecap="round" style="transform:rotate(-90deg);transform-origin:center;"/>
                                </svg>
                                <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:1.5rem;font-weight:700;color:var(--primary);"><?= $completionRate ?>%</div>
                            </div>
                            <div>
                                <p style="margin:0;font-size:1.1rem;"><strong><?= $stats['completed'] ?></strong> courses completed</p>
                                <p style="margin:0.25rem 0;color:#666;">out of <?= $stats['active_enrollments'] + $stats['completed'] ?> total enrollments</p>
                                <p style="margin:0.25rem 0;color:#999;font-size:0.9rem;"><?= $stats['pending_reports'] ?> pending reports</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mode-card">
                    <h3><i class="fas fa-users" style="color:var(--primary);margin-right:0.5rem;"></i> User Breakdown</h3>
                    <div style="padding:1.5rem 0;">
                        <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1rem;">
                            <span style="width:120px;font-size:0.9rem;color:#666;">Instructors</span>
                            <div style="flex:1;background:var(--border);height:10px;border-radius:5px;overflow:hidden;">
                                <div style="background:var(--accent);height:100%;width:<?= $stats['users'] > 0 ? round(($stats['instructors']/$stats['users'])*100) : 0 ?>%;"></div>
                            </div>
                            <span style="font-weight:600;min-width:30px;text-align:right;"><?= $stats['instructors'] ?></span>
                        </div>
                        <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1rem;">
                            <span style="width:120px;font-size:0.9rem;color:#666;">Learners</span>
                            <div style="flex:1;background:var(--border);height:10px;border-radius:5px;overflow:hidden;">
                                <div style="background:var(--primary);height:100%;width:<?= $stats['users'] > 0 ? round(($stats['learners']/$stats['users'])*100) : 0 ?>%;"></div>
                            </div>
                            <span style="font-weight:600;min-width:30px;text-align:right;"><?= $stats['learners'] ?></span>
                        </div>
                        <div style="display:flex;align-items:center;gap:0.75rem;">
                            <span style="width:120px;font-size:0.9rem;color:#666;">Admins</span>
                            <div style="flex:1;background:var(--border);height:10px;border-radius:5px;overflow:hidden;">
                                <div style="background:var(--danger);height:100%;width:<?= $stats['users'] > 0 ? round((($stats['users']-$stats['instructors']-$stats['learners'])/$stats['users'])*100) : 0 ?>%;"></div>
                            </div>
                            <span style="font-weight:600;min-width:30px;text-align:right;"><?= max(0, $stats['users'] - $stats['instructors'] - $stats['learners']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-content" data-tab="tab-courses">
            <div class="mode-card">
                <h3><i class="fas fa-trophy" style="color:var(--primary);margin-right:0.5rem;"></i> Top Courses by Enrollment</h3>
                <?php if (empty($topCourses)): ?>
                    <p style="color:#999;text-align:center;padding:2rem;">No course data yet.</p>
                <?php else: ?>
                    <div style="overflow-x:auto;margin-top:1rem;">
                        <table style="width:100%;border-collapse:collapse;font-size:0.95rem;">
                            <thead style="background:var(--bg-subtle);border-bottom:2px solid #ddd;"><tr><th style="padding:1rem;text-align:left;">Course</th><th style="padding:1rem;text-align:center;">Enrolled</th><th style="padding:1rem;text-align:center;">Completed</th><th style="padding:1rem;text-align:center;">Completion %</th><th style="padding:1rem;text-align:center;">Avg Score</th></tr></thead>
                            <tbody>
                                <?php foreach ($topCourses as $tc):
                                    $tcComp = $tc['enrollment_count'] > 0 ? round(($tc['completed_count']/$tc['enrollment_count'])*100) : 0;
                                ?>
                                    <tr style="border-bottom:1px solid #eee;">
                                        <td style="padding:1rem;"><strong><?= htmlspecialchars($tc['title']) ?></strong></td>
                                        <td style="padding:1rem;text-align:center;"><?= $tc['enrollment_count'] ?></td>
                                        <td style="padding:1rem;text-align:center;"><?= $tc['completed_count'] ?></td>
                                        <td style="padding:1rem;text-align:center;">
                                            <div style="display:flex;align-items:center;gap:0.5rem;justify-content:center;">
                                                <div style="width:60px;background:var(--border);height:6px;border-radius:3px;overflow:hidden;"><div style="background:var(--primary);height:100%;width:<?= $tcComp ?>%;"></div></div>
                                                <span><?= $tcComp ?>%</span>
                                            </div>
                                        </td>
                                        <td style="padding:1rem;text-align:center;font-weight:600;color:var(--primary);"><?= $tc['avg_score'] ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="tab-content" data-tab="tab-trends">
            <div class="mode-card">
                <h3><i class="fas fa-chart-line" style="color:var(--primary);margin-right:0.5rem;"></i> Enrollment Trends (Last 6 Months)</h3>
                <?php if (empty($monthlyEnrollments)): ?>
                    <p style="color:#999;text-align:center;padding:2rem;">Not enough data yet.</p>
                <?php else: ?>
                    <div style="padding:1.5rem 0;">
                        <div style="display:flex;align-items:flex-end;gap:0.75rem;height:180px;padding:0 0.5rem;">
                            <?php
                            $maxCnt = max(array_column($monthlyEnrollments, 'cnt'));
                            $maxCnt = max($maxCnt, 1);
                            foreach ($monthlyEnrollments as $me):
                                $h = round(($me['cnt'] / $maxCnt) * 160);
                            ?>
                                <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:0.25rem;">
                                    <span style="font-size:0.8rem;font-weight:600;color:var(--primary);"><?= $me['cnt'] ?></span>
                                    <div style="width:100%;max-width:60px;height:<?= $h ?>px;background:linear-gradient(180deg,var(--primary),var(--text));border-radius:6px 6px 0 0;"></div>
                                    <span style="font-size:0.75rem;color:#999;"><?= date('M', strtotime($me['month'].'-01')) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="mode-c<div class="mode-card" style="margin-top:1.5rem;">
    <h3><i class="fas fa-chart-bar" style="color:var(--primary);margin-right:0.5rem;"></i> Grade Distribution</h3>
    <?php
    $distA=0;$distB=0;$distC=0;$distD=0;$distF=0;
    foreach ($recentGrades as $g) {
        $s=(float)$g['final_score'];
        if($s>=90)$distA++;elseif($s>=80)$distB++;elseif($s>=70)$distC++;elseif($s>=60)$distD++;else$distF++;
    }
    $totalG=count($recentGrades);
    $maxG=max(max($distA,$distB,$distC,$distD,$distF),1);
    $bluePalette=['#1a0052','var(--primary)','var(--accent)','rgba(81,70,183,0.6)','rgba(81,70,183,0.35)'];
    $gradeLabels=[['label'=>'A','desc'=>'90-100'],['label'=>'B','desc'=>'80-89'],['label'=>'C','desc'=>'70-79'],['label'=>'D','desc'=>'60-69'],['label'=>'F','desc'=>'<60']];
    $gradeCounts=[$distA,$distB,$distC,$distD,$distF];
    ?>
    <div style="display:flex;align-items:flex-end;gap:0.6rem;height:160px;padding:1rem 0.5rem 0;">
        <?php for($i=0;$i<5;$i++):
            $cnt=$gradeCounts[$i];
            $barH=$maxG>0?($cnt/$maxG)*100:0;
            $pct=$totalG>0?round(($cnt/$totalG)*100):0;
        ?>
        <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:0.25rem;height:100%;justify-content:flex-end;">
            <span style="font-size:0.75rem;font-weight:800;color:var(--text);"><?= $cnt ?></span>
            <span style="font-size:0.6rem;color:rgba(32,0,130,0.4);"><?= $pct ?>%</span>
            <div style="width:100%;height:<?= $barH ?>%;min-height:4px;background:<?= $bluePalette[$i] ?>;border-radius:5px 5px 2px 2px;"></div>
        </div>
        <?php endfor; ?>
    </div>
    <div style="display:flex;gap:0.6rem;padding:0.4rem 0.5rem 0;">
        <?php for($i=0;$i<5;$i++): ?>
        <div style="flex:1;text-align:center;">
            <div style="width:10px;height:10px;border-radius:2px;background:<?= $bluePalette[$i] ?>;margin:0 auto 0.15rem;"></div>
            <div style="font-size:0.7rem;font-weight:800;color:var(--text);"><?= $gradeLabels[$i]['label'] ?></div>
            <div style="font-size:0.55rem;color:rgba(32,0,130,0.4);"><?= $gradeLabels[$i]['desc'] ?></div>
        </div>
        <?php endfor; ?>
    </div>
    <?php if($totalG===0): ?><p style="color:#999;text-align:center;margin-top:0.5rem;">No grades to display.</p><?php endif; ?>
</div>
    </div>
</div>
