<?php
include_once __DIR__ . '/../../classes/Employee.php';
require_once dirname(__DIR__, 4) . '/database/db.php';

$employeeClass = new Employee();
$instructorId = (int) ($employeeClass->getEmployeeId() ?? 0);

$courses = [];
$gradebook = [];
$stats = ['totalStudents' => 0, 'avgScore' => 0, 'passRate' => 0, 'topScorer' => '-', 'totalQuizzes' => 0];
$allQuizzes = [];

try {
    $pdo = (new Database())->getConnection();

    // Courses by this instructor
    $stmt = $pdo->prepare("SELECT id, title, status FROM ld_course WHERE instructor_id = :iid AND status = 'active' ORDER BY title ASC");
    $stmt->execute([':iid' => $instructorId]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $courseIds = array_column($courses, 'id');
    if (!empty($courseIds)) {
        $placeholders = implode(',', array_fill(0, count($courseIds), '?'));

        // All quizzes for this instructor's courses
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

        // Enrolled learners with their quiz attempts
        $stmt = $pdo->prepare(
            "SELECT DISTINCT
                e.learner_id,
                CONCAT(emp.first_name, ' ', emp.last_name) AS learner_name,
                emp.employee_code AS employee_id,
                c.id AS course_id,
                c.title AS course_title,
                e.status AS enrollment_status,
                e.enrolled_at,
                e.last_accessed_at
             FROM ld_enrollment e
             JOIN em_employees emp ON emp.employee_id = e.learner_id
             JOIN ld_course c ON c.id = e.course_id
             WHERE c.id IN ($placeholders) AND e.status IN ('enrolled','in_progress','completed')
             ORDER BY emp.last_name, emp.first_name, c.title"
        );
        $stmt->execute($courseIds);
        $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Build quiz attempt map: [learner_id][quiz_id] => best attempt
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

        // Build grade entries per learner-course
        $gradebook = [];
        $totalScore = 0;
        $scoreCount = 0;
        $passCount = 0;
        $bestScore = 0;
        $bestName = '-';

        foreach ($enrollments as $enroll) {
            $lid = (int) $enroll['learner_id'];
            $cid = (int) $enroll['course_id'];

            // Get quizzes for this course
            $courseQuizzes = array_filter($allQuizzes, function ($q) use ($cid) {
                return (int) $q['course_id'] === $cid;
            });

            $quizScores = [];
            $quizzesAttempted = 0;
            $quizzesPassed = 0;

            foreach ($courseQuizzes as $q) {
                $qid = (int) $q['id'];
                $attempt = $attemptMap[$lid][$qid] ?? null;
                if ($attempt) {
                    $quizzesAttempted++;
                    $quizScores[] = [
                        'quiz_id' => $qid,
                        'quiz_title' => $q['title'],
                        'module_title' => $q['module_title'],
                        'score' => $attempt['score'],
                        'passed' => $attempt['passed'],
                        'total_items' => $attempt['total_items'],
                        'attempted_at' => $attempt['attempted_at'],
                        'passing_score' => (float) $q['passing_score'],
                    ];
                    if ($attempt['passed']) $quizzesPassed++;
                } else {
                    $quizScores[] = [
                        'quiz_id' => $qid,
                        'quiz_title' => $q['title'],
                        'module_title' => $q['module_title'],
                        'score' => null,
                        'passed' => false,
                        'total_items' => 0,
                        'attempted_at' => null,
                        'passing_score' => (float) $q['passing_score'],
                    ];
                }
            }

            // Calculate average
            $validScores = array_filter($quizScores, fn($q) => $q['score'] !== null);
            $avgScore = count($validScores) > 0
                ? round(array_sum(array_column($validScores, 'score')) / count($validScores), 1)
                : null;

            // Letter grade
            $letterGrade = '-';
            if ($avgScore !== null) {
                if ($avgScore >= 90) $letterGrade = 'A';
                elseif ($avgScore >= 80) $letterGrade = 'B';
                elseif ($avgScore >= 70) $letterGrade = 'C';
                elseif ($avgScore >= 60) $letterGrade = 'D';
                else $letterGrade = 'F';
            }

            // Overall status
            $quizStatus = 'Not Started';
            if ($quizzesAttempted > 0 && $quizzesPassed === count($courseQuizzes) && count($courseQuizzes) > 0) {
                $quizStatus = 'Passed';
            } elseif ($quizzesAttempted > 0) {
                $quizStatus = 'In Progress';
            }

            $gradebook[] = [
                'learner_id' => $lid,
                'learner_name' => $enroll['learner_name'],
                'employee_id' => $enroll['employee_id'],
                'course_id' => $cid,
                'course_title' => $enroll['course_title'],
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

/** Maps a letter grade to its page-scoped modifier class (see css/pages/gradebook.css). */
function instrLetterGradeClass($grade) {
    return in_array($grade, ['A', 'B', 'C', 'D', 'F'], true) ? 'is-' . strtolower($grade) : 'is-none';
}

/** Maps a quiz status to its pill modifier class. */
function instrQuizStatusClass($status) {
    if ($status === 'Passed') return 'is-passed';
    if ($status === 'In Progress') return 'is-progress';
    return 'is-none';
}

function instrGradeBookTimeAgo($datetime) {
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

<div class="module-content gb-page">
    <div class="home-dashboard">
        <section class="home-hero" aria-labelledby="gb-title">
            <div class="home-hero-main">
                <h1 id="gb-title">Grade Book</h1>
                <p>Quiz scores, averages, and letter grades for the learners enrolled in the courses you teach.</p>
            </div>
            <div class="home-hero-aside">
                <div class="home-hero-chips">
                    <span class="home-hero-chip"><i class="fas fa-users"></i> <?= (int) $stats['totalStudents'] ?> student<?= (int) $stats['totalStudents'] === 1 ? '' : 's' ?></span>
                    <span class="home-hero-chip"><i class="fas fa-chart-line"></i> <?= $stats['avgScore'] ?>% average</span>
                    <span class="home-hero-chip"><i class="fas fa-circle-check"></i> <?= (int) $stats['passRate'] ?>% pass rate</span>
                </div>
            </div>
        </section>

        <div class="home-stats">
            <div class="home-stat">
                <div class="home-stat-icon tone-violet"><i class="fas fa-user-graduate"></i></div>
                <div class="home-stat-body">
                    <span class="home-stat-label">Total Students</span>
                    <span class="home-stat-value"><?= (int) $stats['totalStudents'] ?></span>
                    <span class="home-stat-sub">across <?= count($courses) ?> course<?= count($courses) !== 1 ? 's' : '' ?></span>
                </div>
            </div>
            <div class="home-stat">
                <div class="home-stat-icon tone-indigo"><i class="fas fa-percent"></i></div>
                <div class="home-stat-body">
                    <span class="home-stat-label">Average Score</span>
                    <span class="home-stat-value"><?= $stats['avgScore'] ?>%</span>
                    <span class="home-stat-sub">overall average</span>
                </div>
            </div>
            <div class="home-stat">
                <div class="home-stat-icon tone-emerald"><i class="fas fa-circle-check"></i></div>
                <div class="home-stat-body">
                    <span class="home-stat-label">Pass Rate</span>
                    <span class="home-stat-value"><?= (int) $stats['passRate'] ?>%</span>
                    <span class="home-stat-sub">scoring 60% or more</span>
                </div>
            </div>
            <div class="home-stat">
                <div class="home-stat-icon tone-amber"><i class="fas fa-trophy"></i></div>
                <div class="home-stat-body">
                    <span class="home-stat-label">Top Performer</span>
                    <span class="home-stat-value home-stat-value-sm" title="<?= htmlspecialchars($stats['topScorer']) ?>"><?= htmlspecialchars($stats['topScorer']) ?></span>
                    <span class="home-stat-sub">highest average</span>
                </div>
            </div>
        </div>

        <div class="gb-toolbar">
            <label class="gb-search">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" id="gb-search" placeholder="Search students or courses..." aria-label="Search students" />
            </label>
            <label class="gb-select">
                <select id="gb-course-filter" aria-label="Filter by course">
                    <option value="all">All Courses</option>
                    <?php foreach ($courses as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['title']) ?></option>
                    <?php endforeach; ?>
                </select>
                <i class="fas fa-chevron-down" aria-hidden="true"></i>
            </label>
            <label class="gb-select">
                <select id="gb-grade-filter" aria-label="Filter by grade">
                    <option value="all">All Grades</option>
                    <option value="A">A (90-100)</option>
                    <option value="B">B (80-89)</option>
                    <option value="C">C (70-79)</option>
                    <option value="D">D (60-69)</option>
                    <option value="F">F (Below 60)</option>
                    <option value="none">No Score</option>
                </select>
                <i class="fas fa-chevron-down" aria-hidden="true"></i>
            </label>
            <span class="gb-count" id="gb-count" aria-live="polite"></span>
        </div>

        <?php if (empty($gradebook)): ?>
        <div class="gb-empty">
            <i class="fas fa-clipboard-list"></i>
            <strong>No grade data yet</strong>
            <span>Learners in your courses will appear here once they start taking quizzes.</span>
        </div>
        <?php else: ?>
        <div class="gb-card">
            <div class="gb-table-wrap">
                <table class="gb-table" id="gradebook-table">
                    <thead>
                        <tr>
                            <th data-sort="name">Student <span class="gb-sort"><i class="fas fa-sort"></i></span></th>
                            <th data-sort="course">Course <span class="gb-sort"><i class="fas fa-sort"></i></span></th>
                            <th data-sort="quizzes">Quizzes <span class="gb-sort"><i class="fas fa-sort"></i></span></th>
                            <th data-sort="score">Avg Score <span class="gb-sort"><i class="fas fa-sort"></i></span></th>
                            <th data-sort="grade">Grade <span class="gb-sort"><i class="fas fa-sort"></i></span></th>
                            <th data-sort="status">Status <span class="gb-sort"><i class="fas fa-sort"></i></span></th>
                            <th data-sort="last">Last Active <span class="gb-sort"><i class="fas fa-sort"></i></span></th>
                            <th class="gb-th-action"></th>
                        </tr>
                    </thead>
                    <tbody id="gb-tbody">
                        <?php foreach ($gradebook as $idx => $row):
                            $avg = $row['avg_score'];
                            $letter = $row['letter_grade'];
                            $letterClass = instrLetterGradeClass($letter);
                            $status = $row['quiz_status'];
                            $statusClass = instrQuizStatusClass($status);
                            $avgClass = $avg === null ? 'is-none' : ($avg >= 60 ? 'is-pass' : 'is-fail');
                            $statusIcon = $status === 'Passed' ? 'fa-circle-check' : ($status === 'In Progress' ? 'fa-circle-half-stroke' : 'fa-clock');
                        ?>
                        <tr class="gb-row" data-idx="<?= $idx ?>"
                            data-name="<?= htmlspecialchars(strtolower($row['learner_name'])) ?>"
                            data-course="<?= (int)$row['course_id'] ?>"
                            data-score="<?= $avg ?? -1 ?>"
                            data-letter="<?= $letter ?>"
                            data-status="<?= $status === 'Passed' ? 'pass' : ($status === 'In Progress' ? 'progress' : 'none') ?>">
                            <td>
                                <div class="gb-student-name"><?= htmlspecialchars($row['learner_name']) ?></div>
                                <?php if ($row['employee_id']): ?>
                                <div class="gb-student-id"><?= htmlspecialchars($row['employee_id']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><span class="gb-tag" title="<?= htmlspecialchars($row['course_title']) ?>"><?= htmlspecialchars($row['course_title']) ?></span></td>
                            <td><span class="gb-quizzes"><?= (int) $row['quizzes_attempted'] ?> / <?= (int) $row['total_quizzes'] ?></span></td>
                            <td><span class="gb-score <?= $avgClass ?>"><?= $avg !== null ? $avg . '%' : '---' ?></span></td>
                            <td><span class="gb-letter <?= $letterClass ?>"><?= $letter ?></span></td>
                            <td><span class="gb-pill <?= $statusClass ?>"><i class="fas <?= $statusIcon ?>"></i> <?= $status ?></span></td>
                            <td><span class="gb-when"><?= instrGradeBookTimeAgo($row['last_accessed']) ?></span></td>
                            <td>
                                <button type="button" class="gb-expand-btn" data-idx="<?= $idx ?>" title="Show quiz details" aria-label="Show quiz details for <?= htmlspecialchars($row['learner_name']) ?>">
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </td>
                        </tr>
                        <tr class="gb-expand" id="gb-expand-<?= $idx ?>" style="display:none;">
                            <td colspan="8">
                                <div class="gb-detail">
                                    <div class="gb-detail-title">Quiz Scores</div>
                                    <?php if (empty($row['quiz_scores'])): ?>
                                    <p class="gb-detail-empty">No quiz attempts recorded yet.</p>
                                    <?php else: ?>
                                    <div class="gb-quiz-list">
                                        <?php foreach ($row['quiz_scores'] as $qs):
                                            $qsState = $qs['score'] === null ? 'is-none' : ($qs['passed'] ? 'is-pass' : 'is-fail');
                                            $qTime = $qs['attempted_at'] ? instrGradeBookTimeAgo($qs['attempted_at']) : '';
                                        ?>
                                        <div class="gb-quiz <?= $qsState ?>">
                                            <div class="gb-quiz-main">
                                                <div class="gb-quiz-name" title="<?= htmlspecialchars($qs['quiz_title']) ?>"><?= htmlspecialchars($qs['quiz_title']) ?></div>
                                                <div class="gb-quiz-module"><?= htmlspecialchars($qs['module_title']) ?></div>
                                            </div>
                                            <div class="gb-quiz-score <?= $qsState ?>"><?= $qs['score'] !== null ? $qs['score'] . '%' : '---' ?></div>
                                            <span class="gb-quiz-badge <?= $qsState ?>"><?= $qs['score'] !== null ? ($qs['passed'] ? 'Pass' : 'Fail') : 'N/A' ?></span>
                                            <?php if ($qs['score'] !== null): ?>
                                            <span class="gb-quiz-time"><?= $qTime ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="gb-foot">
                <div class="gb-foot-info">
                    <span id="gb-count-foot"></span>
                    <span class="gb-foot-avg" id="gb-avg-display">Average: <?= $stats['avgScore'] ?>%</span>
                </div>
                <div class="pagination-row" id="gb-pagination">
                    <button type="button" class="page-btn" data-action="prev" disabled>Prev</button>
                    <span class="page-indicator">Page 1 of 1</span>
                    <button type="button" class="page-btn" data-action="next">Next</button>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
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
    var gradeFilter = document.getElementById('gb-grade-filter');
    var countEl = document.getElementById('gb-count');
    var footCountEl = document.getElementById('gb-count-foot');
    var avgEl = document.getElementById('gb-avg-display');
    var paginationEl = document.getElementById('gb-pagination');
    var currentSort = { col: null, asc: true };

    function hasActiveFilters() {
        return (searchInput && searchInput.value.trim() !== '')
            || (courseFilter && courseFilter.value !== 'all')
            || (gradeFilter && gradeFilter.value !== 'all');
    }

    function getVisibleRows() {
        var q = searchInput ? searchInput.value.trim().toLowerCase() : '';
        var courseVal = courseFilter ? courseFilter.value : 'all';
        var gradeVal = gradeFilter ? gradeFilter.value : 'all';
        var visible = [], totalScore = 0, scoreCount = 0;

        rows.forEach(function(row) {
            var tag = row.querySelector('.gb-tag');
            var courseText = tag ? tag.textContent.toLowerCase() : '';
            var nameMatch = !q || row.dataset.name.indexOf(q) !== -1 || courseText.indexOf(q) !== -1;
            var show = nameMatch
                && (courseVal === 'all' || row.dataset.course === courseVal)
                && (gradeVal === 'all' || (gradeVal === 'none' && row.dataset.letter === '-') || row.dataset.letter === gradeVal);
            if (show) {
                visible.push(row);
                var s = parseFloat(row.dataset.score);
                if (!isNaN(s) && s >= 0) { totalScore += s; scoreCount++; }
            }
        });
        if (countEl) countEl.textContent = visible.length + ' student' + (visible.length !== 1 ? 's' : '');
        if (avgEl) {
            var avgLabel = hasActiveFilters() ? 'Filtered average: ' : 'Average: ';
            avgEl.textContent = scoreCount > 0
                ? avgLabel + (totalScore / scoreCount).toFixed(1) + '%'
                : avgLabel + '\u2014';
        }
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

        if (footCountEl) {
            footCountEl.textContent = visible.length
                ? 'Showing ' + (start + 1) + '\u2013' + Math.min(start + PAGE_SIZE, visible.length) + ' of ' + visible.length + ' students'
                : 'No students to show';
        }

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

    document.querySelectorAll('.gb-table thead th[data-sort]').forEach(function(th) {
        th.addEventListener('click', function() {
            var col = th.dataset.sort;
            var asc = currentSort.col === col ? !currentSort.asc : true;
            currentSort = { col: col, asc: asc };
            document.querySelectorAll('.gb-table thead th').forEach(function(t) { t.classList.remove('sorted'); });
            th.classList.add('sorted');
            var icon = th.querySelector('.gb-sort i');
            if (icon) icon.className = asc ? 'fas fa-sort-up' : 'fas fa-sort-down';
            rows.sort(function(a, b) {
                var va, vb;
                switch (col) {
                    case 'name': va = a.dataset.name; vb = b.dataset.name; break;
                    case 'course': va = a.dataset.course; vb = b.dataset.course; break;
                    case 'score': va = parseFloat(a.dataset.score); vb = parseFloat(b.dataset.score); break;
                    case 'grade': var gc = {A:5,B:4,C:3,D:2,F:1,'-':0}; va = gc[a.dataset.letter]||0; vb = gc[b.dataset.letter]||0; break;
                    case 'quizzes': va = parseInt((a.querySelector('td:nth-child(3)')||{}).textContent)||0; vb = parseInt((b.querySelector('td:nth-child(3)')||{}).textContent)||0; break;
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
