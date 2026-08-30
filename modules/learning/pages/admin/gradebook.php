<?php
include_once __DIR__ . '/../../classes/Employee.php';
require_once dirname(__DIR__, 4) . '/database/db.php';

$employeeClass = new Employee();
$adminId = (int) ($employeeClass->getEmployeeId() ?? 0);

$courses = [];
$instructors = [];
$gradebook = [];
$stats = ['totalStudents' => 0, 'avgScore' => 0, 'passRate' => 0, 'topScorer' => '-', 'totalQuizzes' => 0];
$allQuizzes = [];

try {
    $pdo = (new Database())->getConnection();

    // All active courses
    $stmt = $pdo->query("SELECT c.id, c.title, c.status, CONCAT(emp.first_name, ' ', emp.last_name) AS instructor_name, c.instructor_id FROM ld_course c LEFT JOIN em_employees emp ON emp.employee_id = c.instructor_id WHERE c.status = 'active' ORDER BY c.title ASC");
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Distinct instructors
    foreach ($courses as $c) {
        $iid = (int)$c['instructor_id'];
        if ($iid > 0 && !isset($instructors[$iid])) {
            $instructors[$iid] = $c['instructor_name'];
        }
    }

    $courseIds = array_column($courses, 'id');
    if (!empty($courseIds)) {
        $placeholders = implode(',', array_fill(0, count($courseIds), '?'));

        // All quizzes
        $stmt = $pdo->prepare(
            "SELECT q.id, q.title, q.passing_score, q.max_attempts, m.title AS module_title, c.id AS course_id, c.title AS course_title
             FROM ld_quiz q
             JOIN ld_module m ON m.id = q.module_id
             JOIN ld_course c ON c.id = m.course_id
             WHERE c.id IN ($placeholders) AND q.status = 'active'
             ORDER BY c.title, m.title, q.title"
        );
        $stmt->execute($courseIds);
        $allQuizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Enrolled learners
        $stmt = $pdo->prepare(
            "SELECT DISTINCT
                e.learner_id,
                CONCAT(emp.first_name, ' ', emp.last_name) AS learner_name,
                emp.employee_code AS employee_code,
                c.id AS course_id,
                c.title AS course_title,
                c.instructor_id,
                CONCAT(inst.first_name, ' ', inst.last_name) AS instructor_name,
                e.status AS enrollment_status,
                e.enrolled_at,
                e.last_accessed_at
             FROM ld_enrollment e
             JOIN em_employees emp ON emp.employee_id = e.learner_id
             JOIN ld_course c ON c.id = e.course_id
             LEFT JOIN em_employees inst ON inst.employee_id = c.instructor_id
             WHERE c.id IN ($placeholders) AND e.status IN ('enrolled','in_progress','completed')
             ORDER BY emp.last_name, emp.first_name, c.title"
        );
        $stmt->execute($courseIds);
        $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Quiz attempt map
        $attemptMap = [];
        $stmt = $pdo->prepare(
            "SELECT qa.learner_id, qa.quiz_id, qa.score, qa.passed, qa.total_items, qa.attempted_at
             FROM ld_quiz_attempt qa
             JOIN ld_quiz q ON q.id = qa.quiz_id
             JOIN ld_module m ON m.id = q.module_id
             JOIN ld_course c ON c.id = m.course_id
             WHERE c.id IN ($placeholders)
             ORDER BY qa.score DESC"
        );
        $stmt->execute($courseIds);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $lid = (int) $row['learner_id'];
            $qid = (int) $row['quiz_id'];
            if (!isset($attemptMap[$lid][$qid])) {
                $attemptMap[$lid][$qid] = [
                    'score' => (float) $row['score'],
                    'passed' => (int) $row['passed'],
                    'total_items' => (int) $row['total_items'],
                    'attempted_at' => $row['attempted_at'],
                ];
            }
        }

        $gradebook = [];
        $totalScore = 0;
        $scoreCount = 0;
        $passCount = 0;
        $bestScore = 0;
        $bestName = '-';

        foreach ($enrollments as $enroll) {
            $lid = (int) $enroll['learner_id'];
            $cid = (int) $enroll['course_id'];

            $courseQuizzes = array_filter($allQuizzes, fn($q) => (int)$q['course_id'] === $cid);
            $quizScores = [];
            $quizzesAttempted = 0;
            $quizzesPassed = 0;

            foreach ($courseQuizzes as $q) {
                $qid = (int) $q['id'];
                $attempt = $attemptMap[$lid][$qid] ?? null;
                if ($attempt) {
                    $quizzesAttempted++;
                    $quizScores[] = [
                        'quiz_id' => $qid, 'quiz_title' => $q['title'], 'module_title' => $q['module_title'],
                        'score' => $attempt['score'], 'passed' => $attempt['passed'],
                        'total_items' => $attempt['total_items'], 'attempted_at' => $attempt['attempted_at'],
                        'passing_score' => (float) $q['passing_score'],
                    ];
                    if ($attempt['passed']) $quizzesPassed++;
                } else {
                    $quizScores[] = [
                        'quiz_id' => $qid, 'quiz_title' => $q['title'], 'module_title' => $q['module_title'],
                        'score' => null, 'passed' => false, 'total_items' => 0,
                        'attempted_at' => null, 'passing_score' => (float) $q['passing_score'],
                    ];
                }
            }

            $validScores = array_filter($quizScores, fn($q) => $q['score'] !== null);
            $avgScore = count($validScores) > 0 ? round(array_sum(array_column($validScores, 'score')) / count($validScores), 1) : null;

            $letterGrade = '-';
            if ($avgScore !== null) {
                if ($avgScore >= 90) $letterGrade = 'A';
                elseif ($avgScore >= 80) $letterGrade = 'B';
                elseif ($avgScore >= 70) $letterGrade = 'C';
                elseif ($avgScore >= 60) $letterGrade = 'D';
                else $letterGrade = 'F';
            }

            $quizStatus = 'Not Started';
            if ($quizzesAttempted > 0 && $quizzesPassed === count($courseQuizzes) && count($courseQuizzes) > 0) {
                $quizStatus = 'Passed';
            } elseif ($quizzesAttempted > 0) {
                $quizStatus = 'In Progress';
            }

            $gradebook[] = [
                'learner_id' => $lid,
                'learner_name' => $enroll['learner_name'],
                'employee_code' => $enroll['employee_code'],
                'course_id' => $cid,
                'course_title' => $enroll['course_title'],
                'instructor_name' => $enroll['instructor_name'],
                'instructor_id' => $enroll['instructor_id'],
                'enrollment_status' => $enroll['enrollment_status'],
                'enrolled_at' => $enroll['enrolled_at'],
                'last_accessed' => $enroll['last_accessed_at'],
                'total_quizzes' => count($courseQuizzes),
                'quizzes_attempted' => $quizzesAttempted,
                'quizzes_passed' => $quizzesPassed,
                'avg_score' => $avgScore,
                'letter_grade' => $letterGrade,
                'quiz_status' => $quizStatus,
                'quiz_scores' => $quizScores,
            ];

            if ($avgScore !== null) {
                $totalScore += $avgScore;
                $scoreCount++;
                if ($avgScore >= 60) $passCount++;
                if ($avgScore > $bestScore) {
                    $bestScore = $avgScore;
                    $bestName = $enroll['learner_name'];
                }
            }
        }

        $stats['totalStudents'] = count(array_unique(array_column($enrollments, 'learner_id')));
        $stats['avgScore'] = $scoreCount > 0 ? round($totalScore / $scoreCount, 1) : 0;
        $stats['passRate'] = $scoreCount > 0 ? round(($passCount / $scoreCount) * 100) : 0;
        $stats['topScorer'] = $bestName;
        $stats['totalQuizzes'] = count($allQuizzes);
    }
} catch (Throwable $e) {
    $courses = [];
    $gradebook = [];
}

function adminLetterGradeColor($grade) {
    return match($grade) { 'A' => '#10b981', 'B' => '#3b82f6', 'C' => '#f59e0b', 'D' => '#f97316', 'F' => '#ef4444', default => '#9ca3af' };
}

function adminLetterGradeBg($grade) {
    return match($grade) { 'A' => 'rgba(16,185,129,0.1)', 'B' => 'rgba(59,130,246,0.1)', 'C' => 'rgba(245,158,11,0.1)', 'D' => 'rgba(249,115,22,0.1)', 'F' => 'rgba(239,68,68,0.1)', default => 'rgba(156,163,175,0.1)' };
}

function gbTimeAgo($datetime) {
    if (!$datetime) return 'Never';
    $now = new DateTime();
    $past = new DateTime($datetime);
    $diff = $now->diff($past);
    if ($diff->days > 30) return $diff->days . 'd ago';
    if ($diff->days > 0) return $diff->days . 'd ' . $diff->h . 'h';
    if ($diff->h > 0) return $diff->h . 'h ' . $diff->i . 'm';
    return $diff->i . 'm ago';
}
?>

<style>
.gradebook-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
.gradebook-stat-card { padding: 1.25rem; border-radius: 12px; background: var(--surface, #fff); border: 1px solid rgba(32,0,130,0.06); }
.gradebook-stat-card .stat-label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; color: var(--muted); margin-bottom: 0.35rem; }
.gradebook-stat-card .stat-value { font-size: 1.6rem; font-weight: 800; color: var(--text); line-height: 1.2; }
.gradebook-stat-card .stat-sub { font-size: 0.78rem; color: var(--muted); margin-top: 0.25rem; }
.gradebook-table-wrap { background: var(--surface, #fff); border-radius: 14px; border: 1px solid rgba(32,0,130,0.08); overflow-x: auto; }
.gradebook-table { width: 100%; border-collapse: collapse; }
.gradebook-table thead th { padding: 0.85rem 1rem; text-align: left; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--primary); background: rgba(32,0,130,0.03); border-bottom: 1px solid rgba(32,0,130,0.08); white-space: nowrap; cursor: pointer; user-select: none; }
.gradebook-table thead th:hover { background: rgba(32,0,130,0.06); }
.gradebook-table thead th .sort-icon { margin-left: 0.3rem; font-size: 0.65rem; opacity: 0.4; }
.gradebook-table thead th.sorted .sort-icon { opacity: 1; color: var(--primary); }
.gradebook-table tbody tr { border-bottom: 1px solid rgba(32,0,130,0.04); transition: background 0.15s; }
.gradebook-table tbody tr:hover { background: rgba(32,0,130,0.02); }
.gradebook-table td { padding: 0.75rem 1rem; font-size: 0.88rem; color: var(--text); vertical-align: middle; }
.grade-student-name { font-weight: 600; }
.grade-student-id { font-size: 0.75rem; color: var(--muted); }
.grade-course-tag { display: inline-block; padding: 0.2rem 0.6rem; border-radius: 6px; background: rgba(32,0,130,0.06); color: var(--primary); font-size: 0.78rem; font-weight: 600; max-width: 160px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.grade-instructor-tag { display: inline-block; padding: 0.2rem 0.6rem; border-radius: 6px; background: rgba(245,158,11,0.08); color: #b45309; font-size: 0.78rem; font-weight: 600; max-width: 140px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.grade-letter { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; font-weight: 800; font-size: 0.85rem; }
.grade-status-pill { display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.3rem 0.7rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; }
.quiz-score-bar { display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 0.75rem; border: 1px solid rgba(32,0,130,0.06); border-radius: 8px; margin-bottom: 0.5rem; background: var(--surface, #fff); }
.quiz-score-bar .quiz-name { flex: 1; font-size: 0.82rem; font-weight: 600; color: var(--text); min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.quiz-score-bar .quiz-module { font-size: 0.72rem; color: var(--muted); }
.quiz-score-bar .quiz-score-val { font-weight: 700; font-size: 0.85rem; min-width: 45px; text-align: right; }
.quiz-score-bar .quiz-pass-badge { padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.7rem; font-weight: 700; }
.gradebook-empty { text-align: center; padding: 3rem 1rem; color: var(--muted); }
.gradebook-empty i { font-size: 2.5rem; color: var(--border); margin-bottom: 1rem; display: block; }
.gradebook-empty h4 { margin: 0 0 0.5rem; color: var(--text); }
.gradebook-empty p { margin: 0; font-size: 0.9rem; }
.gradebook-footer { display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 1rem; font-size: 0.8rem; color: var(--muted); }
@media (max-width: 900px) { .gradebook-stats { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 768px) { .gradebook-table td, .gradebook-table th { padding: 0.5rem 0.6rem; font-size: 0.8rem; } }

/* Catalog-style toolbar for gradebook */

.catalog-toolbar { display:flex; align-items:center; gap:0.75rem; padding:0.75rem 1rem; background:var(--surface,#fff); border-radius:12px; border:1px solid rgba(32,0,130,0.08); margin-bottom:1rem; flex-wrap:wrap; }
.catalog-search { flex:1; min-width:200px; position:relative; }
.catalog-search input { width:100%; padding:0.6rem 1rem 0.6rem 2.5rem; border:1.5px solid rgba(32,0,130,0.1); border-radius:10px; background:rgba(32,0,130,0.03); font-size:0.88rem; outline:none; transition:border-color 0.2s,box-shadow 0.2s; box-sizing:border-box; }
.catalog-search input:focus { border-color:var(--primary,#320082); box-shadow:0 0 0 3px rgba(32,0,130,0.08); }
.catalog-search i { position:absolute; left:0.85rem; top:50%; transform:translateY(-50%); color:var(--muted,#999); font-size:0.85rem; }
.catalog-count { font-size:0.8rem; color:var(--muted,#999); white-space:nowrap; }
.catalog-tab-select {
    position: relative;
    flex-shrink: 0;
}
.catalog-tab-select select {
    appearance: none;
    -webkit-appearance: none;
    padding: 0.5rem 2rem 0.5rem 0.85rem;
    border: 1.5px solid rgba(32,0,130,0.1);
    border-radius: 10px;
    background: rgba(32,0,130,0.03);
    font-size: 0.85rem;
    font-family: inherit;
    color: var(--text, #1a1a2e);
    cursor: pointer;
    transition: border-color 0.2s, box-shadow 0.2s;
    min-width: 150px;
}
.catalog-tab-select select:focus {
    border-color: var(--primary, #320082);
    box-shadow: 0 0 0 3px rgba(32,0,130,0.08);
    outline: none;
}
.catalog-tab-select .select-icon {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.65rem;
    color: var(--muted, #999);
    pointer-events: none;
}\n</style>

<div class="module-content">
    <!-- Toolbar -->
    <div class="catalog-toolbar" style="margin-bottom:1.25rem;">
        <div class="catalog-search">
            <i class="fas fa-search"></i>
            <input type="search" id="gb-search" placeholder="Search students or courses..." aria-label="Search students" />
        </div>
        <div class="catalog-tab-select">
            <select id="gb-course-filter">
                <option value="all">All Courses</option>
                <?php foreach ($courses as $c): ?>
                <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['title']) ?></option>
                <?php endforeach; ?>
            </select>
            <i class="fas fa-chevron-down select-icon"></i>
        </div>
        <div class="catalog-tab-select">
            <select id="gb-instructor-filter">
                <option value="all">All Instructors</option>
                <?php foreach ($instructors as $iid => $iname): ?>
                <option value="<?= $iid ?>"><?= htmlspecialchars($iname) ?></option>
                <?php endforeach; ?>
            </select>
            <i class="fas fa-chevron-down select-icon"></i>
        </div>
        <div class="catalog-tab-select">
            <select id="gb-grade-filter">
                <option value="all">All Grades</option>
                <option value="A">A (90-100)</option>
                <option value="B">B (80-89)</option>
                <option value="C">C (70-79)</option>
                <option value="D">D (60-69)</option>
                <option value="F">F (Below 60)</option>
                <option value="none">No Score</option>
            </select>
            <i class="fas fa-chevron-down select-icon"></i>
        </div>
        <span class="catalog-count" id="gb-count"></span>
    </div>

    <!-- Header -->
    <div class="module-header">
        <div>
            <h1 class="module-header-title"><i class="fas fa-table" style="color:var(--primary);margin-right:0.5rem;"></i> Grade Book</h1>
            <p class="module-header-subtitle">Student scores, averages, and letter grades across all courses.</p>
        </div>
    </div>

    <div class="gradebook-stats">
        <div class="gradebook-stat-card">
            <div class="stat-label">Total Students</div>
            <div class="stat-value" style="color:var(--primary);"><?= $stats['totalStudents'] ?></div>
            <div class="stat-sub">across <?= count($courses) ?> course<?= count($courses) !== 1 ? 's' : '' ?></div>
        </div>
        <div class="gradebook-stat-card">
            <div class="stat-label">Average Score</div>
            <div class="stat-value" style="color:var(--primary);"><?= $stats['avgScore'] ?>%</div>
            <div class="stat-sub">overall average</div>
        </div>
        <div class="gradebook-stat-card">
            <div class="stat-label">Pass Rate</div>
            <div class="stat-value" style="color:#10b981;"><?= $stats['passRate'] ?>%</div>
            <div class="stat-sub">scoring 60%+</div>
        </div>
        <div class="gradebook-stat-card">
            <div class="stat-label">Top Performer</div>
            <div class="stat-value" style="color:#3b82f6;font-size:1.1rem;"><?= htmlspecialchars($stats['topScorer']) ?></div>
            <div class="stat-sub">highest average</div>
        </div>
    </div>


    <?php if (empty($gradebook)): ?>
    <div class="gradebook-empty">
        <i class="fas fa-clipboard-list"></i>
        <h4>No grade data yet</h4>
        <p>Students will appear here once they start taking quizzes in courses.</p>
    </div>
    <?php else: ?>
    <div class="gradebook-table-wrap">
        <table class="gradebook-table" id="gradebook-table">
            <thead>
                <tr>
                    <th data-sort="name">Student <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                    <th data-sort="course">Course <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                    <th data-sort="instructor">Instructor <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                    <th data-sort="quizzes">Quizzes <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                    <th data-sort="score">Avg Score <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                    <th data-sort="grade">Grade <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                    <th data-sort="status">Status <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                    <th data-sort="last">Last Active <span class="sort-icon"><i class="fas fa-sort"></i></span></th>
                    <th style="width:50px;"></th>
                </tr>
            </thead>
            <tbody id="gb-tbody">
                <?php foreach ($gradebook as $idx => $row):
                    $avg = $row['avg_score'];
                    $letter = $row['letter_grade'];
                    $lColor = adminLetterGradeColor($letter);
                    $lBg = adminLetterGradeBg($letter);
                    $status = $row['quiz_status'];
                    $sColor = match($status) { 'Passed' => '#10b981', 'In Progress' => '#3b82f6', default => '#9ca3af' };
                    $sBg = match($status) { 'Passed' => 'rgba(16,185,129,0.1)', 'In Progress' => 'rgba(59,130,246,0.1)', default => 'rgba(156,163,175,0.1)' };
                ?>
                <tr class="gb-row" data-idx="<?= $idx ?>"
                    data-name="<?= htmlspecialchars(strtolower($row['learner_name'])) ?>"
                    data-course="<?= (int)$row['course_id'] ?>"
                    data-instructor="<?= (int)$row['instructor_id'] ?>"
                    data-score="<?= $avg ?? -1 ?>"
                    data-letter="<?= $letter ?>"
                    data-status="<?= $status === 'Passed' ? 'pass' : ($status === 'In Progress' ? 'progress' : 'none') ?>">
                    <td>
                        <div class="grade-student-name"><?= htmlspecialchars($row['learner_name']) ?></div>
                        <?php if ($row['employee_code']): ?>
                        <div class="grade-student-id"><?= htmlspecialchars($row['employee_code']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><span class="grade-course-tag" title="<?= htmlspecialchars($row['course_title']) ?>"><?= htmlspecialchars(mb_strimwidth($row['course_title'], 0, 20, '...')) ?></span></td>
                    <td><span class="grade-instructor-tag" title="<?= htmlspecialchars($row['instructor_name'] ?? '') ?>"><?= htmlspecialchars(mb_strimwidth($row['instructor_name'] ?? '-', 0, 18, '...')) ?></span></td>
                    <td><?= $row['quizzes_attempted'] ?> / <?= $row['total_quizzes'] ?></td>
                    <td style="font-weight:700;color:<?= $avg !== null ? ($avg >= 60 ? '#10b981' : '#ef4444') : '#9ca3af' ?>;">
                        <?= $avg !== null ? $avg . '%' : '---' ?>
                    </td>
                    <td><span class="grade-letter" style="background:<?= $lBg ?>;color:<?= $lColor ?>;"><?= $letter ?></span></td>
                    <td><span class="grade-status-pill" style="background:<?= $sBg ?>;color:<?= $sColor ?>;"><i class="fas fa-<?= $status === 'Passed' ? 'check-circle' : ($status === 'In Progress' ? 'spinner fa-spin' : 'clock') ?>"></i> <?= $status ?></span></td>
                    <td style="font-size:0.78rem;color:var(--muted);white-space:nowrap;"><?= gbTimeAgo($row['last_accessed']) ?></td>
                    <td><button type="button" class="gb-expand-btn" data-idx="<?= $idx ?>" style="background:none;border:none;cursor:pointer;color:var(--primary);padding:0.3rem;border-radius:4px;" title="Show quiz details"><i class="fas fa-chevron-down"></i></button></td>
                </tr>
                <tr class="gb-expand" id="gb-expand-<?= $idx ?>" style="display:none;">
                    <td colspan="9" style="background:rgba(32,0,130,0.01);padding:0.75rem 1rem 1rem 2rem;">
                        <?php if (empty($row['quiz_scores'])): ?>
                        <p style="color:var(--muted);font-size:0.85rem;margin:0;">No quiz attempts recorded yet.</p>
                        <?php else: ?>
                        <div style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--muted);margin-bottom:0.5rem;">Quiz Scores</div>
                        <?php foreach ($row['quiz_scores'] as $qs):
                            $qsColor = $qs['score'] !== null ? ($qs['passed'] ? '#10b981' : '#ef4444') : '#d1d5db';
                            $qsBadgeBg = $qs['score'] !== null ? ($qs['passed'] ? 'rgba(16,185,129,0.15)' : 'rgba(239,68,68,0.15)') : 'rgba(209,213,219,0.3)';
                            $qsBadgeColor = $qs['score'] !== null ? ($qs['passed'] ? '#059669' : '#dc2626') : '#9ca3af';
                            $qTime = $qs['attempted_at'] ? gbTimeAgo($qs['attempted_at']) : '';
                        ?>
                        <div class="quiz-score-bar" style="border-left:3px solid <?= $qsColor ?>;">
                            <div style="flex:1;min-width:0;">
                                <div class="quiz-name" title="<?= htmlspecialchars($qs['quiz_title']) ?>"><?= htmlspecialchars($qs['quiz_title']) ?></div>
                                <div class="quiz-module"><?= htmlspecialchars($qs['module_title']) ?></div>
                            </div>
                            <div class="quiz-score-val" style="color:<?= $qs['score'] !== null ? ($qs['passed'] ? '#10b981' : '#ef4444') : '#d1d5db' ?>;">
                                <?= $qs['score'] !== null ? $qs['score'] . '%' : '---' ?>
                            </div>
                            <span class="quiz-pass-badge" style="background:<?= $qsBadgeBg ?>;color:<?= $qsBadgeColor ?>;">
                                <?= $qs['score'] !== null ? ($qs['passed'] ? 'Pass' : 'Fail') : 'N/A' ?>
                            </span>
                            <?php if ($qs['score'] !== null): ?>
                            <span style="font-size:0.7rem;color:var(--muted);min-width:60px;text-align:right;"><?= $qTime ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="gradebook-footer">
            <span id="gb-count"></span>
            <span id="gb-avg-display">Average: <?= $stats['avgScore'] ?>%</span>
        </div>
        <div class="pagination-row" id="gb-pagination">
            <button type="button" class="page-btn" data-action="prev" disabled>Prev</button>
            <span class="page-indicator">Page 1 of 1</span>
            <button type="button" class="page-btn" data-action="next">Next</button>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
(function() {
    var tbody = document.getElementById('gb-tbody');
    if (!tbody) return;
    var PAGE_SIZE = 12;
    var currentPage = 1;
    var rows = Array.from(tbody.querySelectorAll('.gb-row'));
    var searchInput = document.getElementById('gb-search');
    var courseFilter = document.getElementById('gb-course-filter');
    var instructorFilter = document.getElementById('gb-instructor-filter');
    var gradeFilter = document.getElementById('gb-grade-filter');
    var countEl = document.getElementById('gb-count');
    var avgEl = document.getElementById('gb-avg-display');
    var paginationEl = document.getElementById('gb-pagination');
    var currentSort = { col: null, asc: true };

    function getVisibleRows() {
        var q = searchInput ? searchInput.value.trim().toLowerCase() : '';
        var courseVal = courseFilter ? courseFilter.value : 'all';
        var instrVal = instructorFilter ? instructorFilter.value : 'all';
        var gradeVal = gradeFilter ? gradeFilter.value : 'all';
        var visible = [], totalScore = 0, scoreCount = 0;

        rows.forEach(function(row) {
            var show = (!q || row.dataset.name.indexOf(q) !== -1)
                && (courseVal === 'all' || row.dataset.course === courseVal)
                && (instrVal === 'all' || row.dataset.instructor === instrVal)
                && (gradeVal === 'all' || (gradeVal === 'none' && row.dataset.letter === '-') || row.dataset.letter === gradeVal);
            if (show) {
                visible.push(row);
                var s = parseFloat(row.dataset.score);
                if (!isNaN(s) && s >= 0) { totalScore += s; scoreCount++; }
            }
        });
        if (countEl) countEl.textContent = visible.length + ' student' + (visible.length !== 1 ? 's' : '');
        if (avgEl) avgEl.textContent = 'Filtered avg: ' + (scoreCount > 0 ? (totalScore / scoreCount).toFixed(1) : '0') + '%';
        return visible;
    }

    function applyFilters() {
        var visible = getVisibleRows();
        var totalPages = Math.max(1, Math.ceil(visible.length / PAGE_SIZE));
        currentPage = Math.min(currentPage, totalPages);
        var start = (currentPage - 1) * PAGE_SIZE;
        var end = start + PAGE_SIZE;

        rows.forEach(function(row) {
            row.style.display = 'none';
            var expandRow = document.getElementById('gb-expand-' + row.dataset.idx);
            if (expandRow) expandRow.style.display = 'none';
            var btn = row.querySelector('.gb-expand-btn i');
            if (btn) { btn.classList.remove('fa-chevron-up'); btn.classList.add('fa-chevron-down'); }
        });

        visible.slice(start, end).forEach(function(row) { row.style.display = ''; });

        if (paginationEl) {
            var indicator = paginationEl.querySelector('.page-indicator');
            var prevBtn = paginationEl.querySelector('[data-action="prev"]');
            var nextBtn = paginationEl.querySelector('[data-action="next"]');
            if (indicator) indicator.textContent = 'Page ' + currentPage + ' of ' + totalPages;
            if (prevBtn) prevBtn.disabled = currentPage <= 1;
            if (nextBtn) nextBtn.disabled = currentPage >= totalPages;
            paginationEl.style.display = totalPages <= 1 ? 'none' : '';
        }
    }

    tbody.addEventListener('click', function(e) {
        var btn = e.target.closest('.gb-expand-btn');
        if (!btn) return;
        var expand = document.getElementById('gb-expand-' + btn.dataset.idx);
        var icon = btn.querySelector('i');
        if (!expand) return;
        if (expand.style.display === 'none') {
            expand.style.display = '';
            if (icon) { icon.classList.remove('fa-chevron-down'); icon.classList.add('fa-chevron-up'); }
        } else {
            expand.style.display = 'none';
            if (icon) { icon.classList.remove('fa-chevron-up'); icon.classList.add('fa-chevron-down'); }
        }
    });

    document.querySelectorAll('.gradebook-table thead th[data-sort]').forEach(function(th) {
        th.addEventListener('click', function() {
            var col = th.dataset.sort;
            var asc = currentSort.col === col ? !currentSort.asc : true;
            currentSort = { col: col, asc: asc };
            document.querySelectorAll('.gradebook-table thead th').forEach(function(t) { t.classList.remove('sorted'); });
            th.classList.add('sorted');
            var icon = th.querySelector('.sort-icon i');
            if (icon) icon.className = asc ? 'fas fa-sort-up' : 'fas fa-sort-down';
            rows.sort(function(a, b) {
                var va, vb;
                switch (col) {
                    case 'name': va = a.dataset.name; vb = b.dataset.name; break;
                    case 'course': va = a.dataset.course; vb = b.dataset.course; break;
                    case 'instructor': va = a.dataset.instructor; vb = b.dataset.instructor; break;
                    case 'score': va = parseFloat(a.dataset.score); vb = parseFloat(b.dataset.score); break;
                    case 'grade': var gc = {A:5,B:4,C:3,D:2,F:1,'-':0}; va = gc[a.dataset.letter]||0; vb = gc[b.dataset.letter]||0; break;
                    case 'quizzes': va = parseInt((a.querySelector('td:nth-child(4)')||{}).textContent)||0; vb = parseInt((b.querySelector('td:nth-child(4)')||{}).textContent)||0; break;
                    case 'status': va = a.dataset.status; vb = b.dataset.status; break;
                    default: va = a.dataset.name; vb = b.dataset.name;
                }
                if (va < vb) return asc ? -1 : 1;
                if (va > vb) return asc ? 1 : -1;
                return 0;
            });
            rows.forEach(function(r) {
                tbody.appendChild(r);
                var exp = document.getElementById('gb-expand-' + r.dataset.idx);
                if (exp) tbody.appendChild(exp);
            });
            applyFilters();
        });
    });

    if (searchInput) searchInput.addEventListener('input', function() { currentPage = 1; applyFilters(); });
    if (courseFilter) courseFilter.addEventListener('change', function() { currentPage = 1; applyFilters(); });
    if (instructorFilter) instructorFilter.addEventListener('change', function() { currentPage = 1; applyFilters(); });
    if (gradeFilter) gradeFilter.addEventListener('change', function() { currentPage = 1; applyFilters(); });

    if (paginationEl) {
        paginationEl.addEventListener('click', function(e) {
            var btn = e.target.closest('[data-action]');
            if (!btn || btn.disabled) return;
            if (btn.dataset.action === 'prev' && currentPage > 1) currentPage--;
            if (btn.dataset.action === 'next') {
                var totalPages = Math.max(1, Math.ceil(getVisibleRows().length / PAGE_SIZE));
                if (currentPage < totalPages) currentPage++;
            }
            applyFilters();
        });
    }

    applyFilters();
})();
</script>
