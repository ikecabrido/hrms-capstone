<?php
include_once __DIR__ . '/../../classes/Employee.php';
include_once __DIR__ . '/../../classes/Course.php';
require_once dirname(__DIR__, 4) . '/database/db.php';

$employeeClass = new Employee();
$learnerId = (int) ($employeeClass->getEmployeeId() ?? 0);

$catalogItems = [];
$enrolledCourseIds = [];

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Courses
    $courses = $pdo->query("SELECT c.id, c.title, c.description, c.category, c.status, c.thumbnail_path, c.created_at, c.start_date, c.enrollment_deadline, 'course' AS item_type,
        CONCAT(emp.first_name, ' ', emp.last_name) AS instructor_name,
        (SELECT COUNT(*) FROM ld_module m WHERE m.course_id = c.id AND m.status = 'active') AS module_count,
        (SELECT COUNT(*) FROM ld_lesson l JOIN ld_module m2 ON m2.id = l.module_id WHERE m2.course_id = c.id AND l.status = 'active') AS lesson_count,
        (SELECT COUNT(*) FROM ld_enrollment e WHERE e.course_id = c.id) AS enrollment_count
        FROM ld_course c
        LEFT JOIN em_employees emp ON emp.employee_id = c.instructor_id
        WHERE c.status = 'active'
        ORDER BY c.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    $catalogItems = array_merge($catalogItems, $courses);

    // Modules
    $modules = $pdo->query("SELECT m.id, m.title, m.description, c.category, m.status, NULL AS thumbnail_path, m.created_at, 'module' AS item_type,
        CONCAT(emp.first_name, ' ', emp.last_name) AS instructor_name,
        c.title AS course_title, c.id AS course_id,
        (SELECT COUNT(*) FROM ld_lesson l WHERE l.module_id = m.id AND l.status = 'active') AS lesson_count,
        (SELECT COUNT(*) FROM ld_quiz q WHERE q.module_id = m.id AND q.status = 'active') AS quiz_count
        FROM ld_module m JOIN ld_course c ON c.id = m.course_id
        LEFT JOIN em_employees emp ON emp.employee_id = c.instructor_id
        WHERE m.status = 'active' AND c.status = 'active'
        ORDER BY m.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    $catalogItems = array_merge($catalogItems, $modules);

    // Lessons
    $lessons = $pdo->query("SELECT l.id, l.title, COALESCE(l.content_body, l.video_url, 'No description') AS description, c.category, l.status, NULL AS thumbnail_path, l.created_at, 'lesson' AS item_type,
        CONCAT(emp.first_name, ' ', emp.last_name) AS instructor_name,
        c.title AS course_title, c.id AS course_id, m.title AS module_title, m.id AS module_id, l.content_type
        FROM ld_lesson l JOIN ld_module m ON m.id = l.module_id JOIN ld_course c ON c.id = m.course_id
        LEFT JOIN em_employees emp ON emp.employee_id = c.instructor_id
        WHERE l.status = 'active' AND m.status = 'active' AND c.status = 'active'
        ORDER BY l.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    $catalogItems = array_merge($catalogItems, $lessons);

    // Quizzes
    $quizzes = $pdo->query("SELECT q.id, q.title, CONCAT('Pass: ', q.passing_score, '% | ', q.question_count, ' questions') AS description, c.category, q.status, NULL AS thumbnail_path, q.created_at, 'quiz' AS item_type,
        CONCAT(emp.first_name, ' ', emp.last_name) AS instructor_name,
        c.title AS course_title, c.id AS course_id, m.title AS module_title, m.id AS module_id,
        q.duration_seconds, q.passing_score, q.question_count, q.max_attempts
        FROM ld_quiz q JOIN ld_module m ON m.id = q.module_id JOIN ld_course c ON c.id = m.course_id
        LEFT JOIN em_employees emp ON emp.employee_id = c.instructor_id
        WHERE q.status = 'active' AND m.status = 'active' AND c.status = 'active'
        ORDER BY q.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    $catalogItems = array_merge($catalogItems, $quizzes);

    // Learning Paths
    $learningPaths = $pdo->query("SELECT lp.id, lp.title, lp.description, 'Learning Path' AS category, lp.status, NULL AS thumbnail_path, lp.created_at, 'learning-path' AS item_type,
        CONCAT(emp.first_name, ' ', emp.last_name) AS instructor_name,
        (SELECT COUNT(*) FROM ld_learning_path_item lpi WHERE lpi.learning_path_id = lp.id) AS item_count
        FROM ld_learning_path lp LEFT JOIN em_employees emp ON emp.employee_id = lp.instructor_id
        WHERE lp.status = 'active' ORDER BY lp.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    $catalogItems = array_merge($catalogItems, $learningPaths);

    // Video Conferences
    $videoConfs = $pdo->query("SELECT vc.id, vc.title, vc.title AS description, vc.platform AS category, vc.status, NULL AS thumbnail_path, vc.created_at, 'video-conference' AS item_type,
        CONCAT(emp.first_name, ' ', emp.last_name) AS instructor_name,
        vc.scheduled_at, vc.duration_minutes, vc.meeting_link
        FROM ld_video_conference vc LEFT JOIN em_employees emp ON emp.employee_id = vc.instructor_id
        WHERE vc.status = 'scheduled' ORDER BY vc.scheduled_at ASC")->fetchAll(PDO::FETCH_ASSOC);
    $catalogItems = array_merge($catalogItems, $videoConfs);

    // Enrolled IDs
    $stmt = $pdo->prepare("SELECT course_id FROM ld_enrollment WHERE learner_id = :learner_id");
    $stmt->execute([':learner_id' => $learnerId]);
    $enrolledCourseIds = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'course_id');
} catch (Throwable $e) {
    $catalogItems = [];
    $enrolledCourseIds = [];
}

// Category gradient map
$catGradients = [
    'IT' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    'Programming' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
    'Data Science' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
    'Business' => 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
    'Marketing' => 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
    'Design' => 'linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%)',
    'General' => 'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)',
    'default' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
];
$defaultGrad = $catGradients['default'];

function getCatGradient($category, $catGradients, $default) {
    foreach ($catGradients as $key => $grad) {
        if (strcasecmp($key, $category) === 0) return $grad;
    }
    return $default;
}

// Count items by type for tab badges
$typeCounts = ['all' => count($catalogItems)];
foreach ($catalogItems as $item) {
    $t = $item['item_type'] ?? 'course';
    $typeCounts[$t] = ($typeCounts[$t] ?? 0) + 1;
}
?>
<style>
/* ---- Catalog Tab Navigation ---- */
.catalog-tabs {
    display: flex;
    gap: 6px;
    margin-bottom: 1rem;
    overflow-x: auto;
    scrollbar-width: none;
    background: #e8e4f0;
    border-radius: 12px;
    padding: 4px;
}
.catalog-tabs::-webkit-scrollbar { display: none; }
.catalog-tab-btn {
    flex: 1 1 0;
    min-width: 0;
    padding: 0.55rem 0.5rem;
    border: none;
    border-radius: 8px;
    background: rgba(255,255,255,0.7);
    color: var(--text, #333);
    font-size: 0.78rem;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
}
.catalog-tab-btn:hover {
    background: rgba(255,255,255,0.95);
}
.catalog-tab-btn.active {
    background: var(--primary, #320082);
    color: #fff;
    box-shadow: 0 2px 8px rgba(32,0,130,0.3);
}
.catalog-tab-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 22px;
    height: 20px;
    padding: 0 6px;
    border-radius: 999px;
    background: rgba(32,0,130,0.1);
    font-size: 0.7rem;
    font-weight: 700;
    color: var(--text, #333);
}
.catalog-tab-btn.active .catalog-tab-count {
    background: rgba(255,255,255,0.25);
    color: #fff;
}

/* ---- Recommended Section ---- */
.rec-section {
    margin-bottom: 1.5rem;
}
.rec-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}
.rec-header h3 {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--text);
}
.rec-nav {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.rec-nav-btn {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 1.5px solid rgba(32,0,130,0.15);
    background: var(--surface, #fff);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text);
    transition: all 0.15s;
}
.rec-nav-btn:hover {
    background: var(--primary);
    color: #fff;
    border-color: var(--primary);
}
.rec-track {
    display: flex;
    gap: 1rem;
    overflow-x: auto;
    scroll-behavior: smooth;
    scrollbar-width: none;
    padding-bottom: 0.25rem;
}
.rec-track::-webkit-scrollbar { display: none; }
.rec-card {
    flex-shrink: 0;
    width: 260px;
    display: block;
    text-decoration: none;
    color: inherit;
}
.rec-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(32,0,130,0.12);
}

/* ---- Catalog Toolbar ---- */
.catalog-toolbar {
    position: sticky;
    top: calc(var(--header-height, 60px) + 0.75rem);
    z-index: 100;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    background: var(--surface, #fff);
    border-radius: 12px;
    border: 1px solid rgba(32,0,130,0.08);
    margin-bottom: 1rem;
    flex-wrap: wrap;
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}
.catalog-search {
    flex: 1;
    min-width: 200px;
    position: relative;
}
.catalog-search input {
    width: 100%;
    padding: 0.6rem 1rem 0.6rem 2.5rem;
    border: 1.5px solid rgba(32,0,130,0.1);
    border-radius: 10px;
    background: rgba(32,0,130,0.03);
    font-size: 0.88rem;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
    box-sizing: border-box;
}
.catalog-search input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(32,0,130,0.08);
}
.catalog-search i {
    position: absolute;
    left: 0.85rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--muted);
    font-size: 0.85rem;
}
.catalog-view-toggle {
    display: flex;
    border: 1.5px solid rgba(32,0,130,0.1);
    border-radius: 8px;
    overflow: hidden;
}
.catalog-view-toggle button {
    padding: 0.45rem 0.7rem;
    border: none;
    background: transparent;
    color: var(--muted);
    cursor: pointer;
    font-size: 0.85rem;
    transition: all 0.15s;
}
.catalog-view-toggle button.active {
    background: var(--primary);
    color: #fff;
}
.catalog-request-btn {
    padding: 0.5rem 1rem;
    background: var(--primary);
    color: #fff;
    border: none;
    border-radius: 999px;
    font-weight: 700;
    font-size: 0.82rem;
    cursor: pointer;
    white-space: nowrap;
    transition: opacity 0.2s;
}
.catalog-request-btn:hover { opacity: 0.85; }
.catalog-count {
    font-size: 0.8rem;
    color: var(--muted);
    white-space: nowrap;
}

/* ---- Rich Course Cards ---- */
.catalog-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.25rem;
    margin-bottom: 1.5rem;
}
.catalog-card {
    background: var(--surface, #fff);
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid rgba(32,0,130,0.08);
    cursor: pointer;
    transition: transform 0.25s, box-shadow 0.25s, border-color 0.25s;
    position: relative;
}
.catalog-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 35px rgba(32,0,130,0.12);
    border-color: rgba(32,0,130,0.2);
}
.catalog-card-thumb {
    height: 140px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
}
.catalog-card-thumb .thumb-icon {
    font-size: 2.5rem;
    color: rgba(255,255,255,0.9);
    z-index: 1;
}
.catalog-card-thumb .thumb-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, transparent 40%, rgba(0,0,0,0.3) 100%);
}
.catalog-card-type-badge {
    position: absolute;
    top: 0.75rem;
    left: 0.75rem;
    padding: 0.2rem 0.6rem;
    border-radius: 6px;
    background: rgba(255,255,255,0.9);
    color: var(--primary);
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    z-index: 2;
    backdrop-filter: blur(4px);
}
.catalog-card-enrolled-badge {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    padding: 0.2rem 0.6rem;
    border-radius: 6px;
    background: rgba(16,185,129,0.9);
    color: #fff;
    font-size: 0.7rem;
    font-weight: 700;
    z-index: 2;
}
.catalog-card-body { padding: 1rem 1.1rem 0.75rem; }
.catalog-card-body h4 {
    margin: 0 0 0.35rem;
    font-size: 1rem;
    font-weight: 700;
    color: var(--text);
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.catalog-card-body .cc-instructor {
    font-size: 0.8rem;
    color: var(--muted);
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.35rem;
}
.catalog-card-body .cc-instructor i { font-size: 0.7rem; }
.catalog-card-body .cc-desc {
    font-size: 0.82rem;
    color: var(--muted);
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    margin: 0 0 0.75rem;
}
.catalog-card-stats {
    display: flex;
    gap: 0.75rem;
    padding: 0.6rem 1.1rem;
    border-top: 1px solid rgba(32,0,130,0.06);
    font-size: 0.75rem;
    color: var(--muted);
}
.catalog-card-stats span { display: flex; align-items: center; gap: 0.3rem; }
.catalog-card-stats i { color: var(--primary); font-size: 0.7rem; }
.catalog-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.6rem 1.1rem;
    border-top: 1px solid rgba(32,0,130,0.06);
}
.catalog-card-footer .cc-deadline {
    font-size: 0.75rem;
    color: var(--muted);
}
.catalog-card-footer .cc-deadline i { margin-right: 0.25rem; }
.cc-enroll-btn {
    padding: 0.45rem 1rem;
    border-radius: 999px;
    border: none;
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
}
.cc-enroll-btn.enroll {
    background: var(--primary);
    color: #fff;
}
.cc-enroll-btn.enroll:hover { opacity: 0.85; transform: scale(1.03); }
.cc-enroll-btn.enrolled {
    background: rgba(16,185,129,0.12);
    color: #059669;
    cursor: default;
}
.cc-enroll-btn.enrolling {
    background: rgba(32,0,130,0.1);
    color: var(--primary);
    cursor: wait;
}

/* ---- List view ---- */
.catalog-grid.list-view {
    grid-template-columns: 1fr;
    gap: 0.75rem;
}
.catalog-grid.list-view .catalog-card {
    display: flex;
    flex-direction: row;
}
.catalog-grid.list-view .catalog-card-thumb {
    width: 180px;
    height: auto;
    min-height: 120px;
    flex-shrink: 0;
}
.catalog-grid.list-view .catalog-card-body,
.catalog-grid.list-view .catalog-card-stats,
.catalog-grid.list-view .catalog-card-footer {
    border-top: none;
}
.catalog-grid.list-view .catalog-card-body { flex: 1; padding-top: 1rem; }

/* ---- Empty state ---- */
.catalog-empty {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--muted);
}
.catalog-empty i { font-size: 2.5rem; color: var(--border); margin-bottom: 1rem; display: block; }
.catalog-empty h4 { margin: 0 0 0.5rem; color: var(--text); }
.catalog-empty p { margin: 0; font-size: 0.9rem; }

/* ---- Responsive ---- */
@media (max-width: 1200px) {
    .catalog-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 900px) {
    .catalog-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
    .catalog-grid { grid-template-columns: repeat(2, 1fr); gap: 1rem; }
    .catalog-toolbar { flex-wrap: nowrap; }
    .catalog-search { min-width: 0; flex: 1; }
    .catalog-grid.list-view .catalog-card { flex-direction: column; }
    .catalog-grid.list-view .catalog-card-thumb { width: 100%; height: 120px; }
    .rec-card { width: 220px; }
    .catalog-tabs { overflow-x: auto; }
    .catalog-tab-btn { flex: 0 0 auto; padding: 0.55rem 0.75rem; }
}
@media (max-width: 480px) {
    .catalog-grid { grid-template-columns: 1fr; }
}
</style>

<div class="module-content">
    <!-- Toolbar -->
    <div class="catalog-toolbar" id="browse-courses">
        <div class="catalog-search">
            <i class="fas fa-search"></i>
            <input type="search" id="catalog-search-input" placeholder="Search courses, modules, lessons..." aria-label="Search catalog" />
        </div>
        <div class="catalog-view-toggle">
            <button type="button" class="active" data-view="grid" title="Grid view"><i class="fas fa-th"></i></button>
            <button type="button" data-view="list" title="List view"><i class="fas fa-list"></i></button>
        </div>
        <span class="catalog-count" id="catalog-count"></span>
        <button type="button" class="catalog-request-btn" id="request-course-btn">
            <i class="fas fa-plus"></i> Request Course
        </button>
    </div>

    <!-- Tab Buttons -->
    <div class="catalog-tabs" id="catalog-tabs">
        <button type="button" class="catalog-tab-btn active" data-tab="all">
            All
            <span class="catalog-tab-count"><?= $typeCounts['all'] ?? 0 ?></span>
        </button>
        <button type="button" class="catalog-tab-btn" data-tab="course">
            Course
            <span class="catalog-tab-count"><?= $typeCounts['course'] ?? 0 ?></span>
        </button>
        <button type="button" class="catalog-tab-btn" data-tab="module">
            Module
            <span class="catalog-tab-count"><?= $typeCounts['module'] ?? 0 ?></span>
        </button>
        <button type="button" class="catalog-tab-btn" data-tab="lesson">
            Lesson
            <span class="catalog-tab-count"><?= $typeCounts['lesson'] ?? 0 ?></span>
        </button>
        <button type="button" class="catalog-tab-btn" data-tab="quiz">
            Quiz
            <span class="catalog-tab-count"><?= $typeCounts['quiz'] ?? 0 ?></span>
        </button>
        <button type="button" class="catalog-tab-btn" data-tab="learning-path">
            Learning Path
            <span class="catalog-tab-count"><?= $typeCounts['learning-path'] ?? 0 ?></span>
        </button>
        <button type="button" class="catalog-tab-btn" data-tab="video-conference">
            Live Sessions
            <span class="catalog-tab-count"><?= $typeCounts['video-conference'] ?? 0 ?></span>
        </button>
    </div>

    <!-- Recommendations (filtered by selected tab) -->
    <div id="recommendations-section" class="rec-section" style="display:none;">
        <div class="rec-header">
            <h3>Recommended For You</h3>
            <div class="rec-nav">
                <span id="rec-skill-count" style="font-size:0.78rem; color:var(--muted);"></span>
                <button type="button" id="rec-prev" class="rec-nav-btn"><i class="fas fa-chevron-left" style="font-size:0.7rem;"></i></button>
                <button type="button" id="rec-next" class="rec-nav-btn"><i class="fas fa-chevron-right" style="font-size:0.7rem;"></i></button>
            </div>
        </div>
        <div id="recommendations-track" class="rec-track"></div>
    </div>

    <!-- Course Cards Grid -->
    <div class="catalog-grid" id="catalog-grid">
        <?php
        $typeIcons = ['course' => 'fa-graduation-cap', 'program' => 'fa-layer-group', 'learning-path' => 'fa-route',
            'video-conference' => 'fa-video', 'module' => 'fa-cube', 'lesson' => 'fa-book-open', 'quiz' => 'fa-question-circle'];
        $typeLabels = ['course' => 'Course', 'program' => 'Program', 'learning-path' => 'Learning Path',
            'video-conference' => 'Live Session', 'module' => 'Module', 'lesson' => 'Lesson', 'quiz' => 'Quiz'];

        foreach ($catalogItems as $item):
            $id = (int) ($item['id'] ?? 0);
            $title = trim((string) ($item['title'] ?? 'Untitled'));
            $description = trim((string) ($item['description'] ?? ''));
            $itemType = trim((string) ($item['item_type'] ?? 'course'));
            $isEnrolled = $itemType === 'course' && in_array($id, $enrolledCourseIds, true);
            $typeLabel = $typeLabels[$itemType] ?? 'Item';
            $icon = $typeIcons[$itemType] ?? 'fa-book';
            $meta = trim((string) ($item['instructor_name'] ?? ''));
            $instructorName = $meta;
            if (empty($meta)) $meta = trim((string) ($item['course_title'] ?? $item['category'] ?? ''));
            $category = trim((string) ($item['category'] ?? 'General'));
            $gradient = getCatGradient($category, $catGradients, $defaultGrad);
            if ($itemType === 'video-conference') {
                $vcPlat = $item['platform'] ?? 'other';
                $gradient = $vcPlat === 'zoom' ? 'linear-gradient(135deg, #2D8CFF, #0066FF)' : ($vcPlat === 'google_meet' ? 'linear-gradient(135deg, #00897B, #004D40)' : 'linear-gradient(135deg, #6366f1, #4f46e5)');
            }
            $shortDesc = $description !== '' ? mb_substr(strip_tags($description), 0, 100) : 'No description available.';
            $enrollCount = (int) ($item['enrollment_count'] ?? 0);
            $moduleCount = (int) ($item['module_count'] ?? 0);
            $lessonCount = (int) ($item['lesson_count'] ?? 0);

            $link = '';
            if ($itemType === 'course') $link = '?page=learner/catalog-subpage/course&course_id=' . $id;
            elseif ($itemType === 'module' && !empty($item['course_id'])) $link = '?page=learner/catalog-subpage/course&course_id=' . $item['course_id'];
            elseif ($itemType === 'lesson' && !empty($item['course_id'])) $link = '?page=learner/catalog-subpage/course&course_id=' . $item['course_id'];
            elseif ($itemType === 'quiz' && !empty($item['course_id'])) $link = '?page=learner/catalog-subpage/course&course_id=' . $item['course_id'];
            elseif ($itemType === 'program') $link = '?page=learner/catalog-subpage/program&program_id=' . $id;
            elseif ($itemType === 'learning-path') $link = '?page=learner/catalog-subpage/learning-path&learning_path_id=' . $id;
            elseif ($itemType === 'video-conference') $link = '?page=learner/catalog-subpage/video-conference&video_conference_id=' . $id;

            $footerInfo = '';
            if ($itemType === 'course') {
                $parts = [];
                if ($moduleCount > 0) $parts[] = $moduleCount . ' modules';
                if ($lessonCount > 0) $parts[] = $lessonCount . ' lessons';
                $footerInfo = implode(' . ', $parts);
            } elseif ($itemType === 'module') {
                $lc = (int)($item['lesson_count'] ?? 0);
                $qc = (int)($item['quiz_count'] ?? 0);
                $parts = [];
                if ($lc > 0) $parts[] = $lc . ' lessons';
                if ($qc > 0) $parts[] = $qc . ' quizzes';
                $footerInfo = implode(' . ', $parts);
            } elseif ($itemType === 'learning-path') {
                $ic = (int)($item['item_count'] ?? 0);
                $footerInfo = $ic . ' steps';
            } elseif ($itemType === 'video-conference') {
                if (!empty($item['scheduled_at'])) $footerInfo = date('M j, g:i A', strtotime($item['scheduled_at']));
                if (!empty($item['duration_minutes'])) $footerInfo .= ($footerInfo ? ' . ' : '') . $item['duration_minutes'] . ' min';
            } elseif ($itemType === 'quiz') {
                $footerInfo = ($item['question_count'] ?? 0) . ' questions . Pass: ' . ($item['passing_score'] ?? 0) . '%';
            } elseif ($itemType === 'lesson') {
                $footerInfo = ucfirst($item['content_type'] ?? 'text');
            } elseif ($itemType === 'program') {
                $footerInfo = 'Training program';
            }

            $deadline = '';
            if ($itemType === 'course' && !empty($item['enrollment_deadline'])) {
                $dl = strtotime($item['enrollment_deadline']);
                $now = time();
                $daysLeft = max(0, (int)(($dl - $now) / 86400));
                $deadline = $daysLeft . ' days left to enroll';
            }
        ?>
        <article class="catalog-card"
                 data-id="<?= $id ?>"
                 data-type="<?= htmlspecialchars($itemType) ?>"
                 data-category="<?= htmlspecialchars($category) ?>"
                 data-enrolled="<?= $isEnrolled ? 'true' : 'false' ?>"
                 data-link="<?= htmlspecialchars($link) ?>"
                 data-title="<?= htmlspecialchars($title) ?>"
                 data-desc="<?= htmlspecialchars($shortDesc) ?>"
                 data-instructor="<?= htmlspecialchars($instructorName) ?>"
                 <?php if ($itemType === 'video-conference'): ?>
                 data-scheduled="<?= htmlspecialchars($item['scheduled_at'] ?? '') ?>"
                 data-duration="<?= (int)($item['duration_minutes'] ?? 0) ?>"
                 data-platform="<?= htmlspecialchars($item['platform'] ?? '') ?>"
                 data-meeting-link="<?= htmlspecialchars($item['meeting_link'] ?? '') ?>"
                 <?php endif; ?>
                 data-enrollment-count="<?= $enrollCount ?>"
                 data-module-count="<?= $moduleCount ?>"
                 data-lesson-count="<?= $lessonCount ?>">
            <div class="catalog-card-thumb" style="background:<?= $gradient ?>;">
                <i class="fas <?= $icon ?> thumb-icon"></i>
                <div class="thumb-overlay"></div>
                <span class="catalog-card-type-badge"><?= htmlspecialchars($typeLabel) ?></span>
                <?php if ($isEnrolled): ?>
                <span class="catalog-card-enrolled-badge"><i class="fas fa-check"></i> Enrolled</span>
                <?php endif; ?>
            </div>
            <div class="catalog-card-body">
                <h4><?= htmlspecialchars($title) ?></h4>
                <?php if ($meta): ?>
                <div class="cc-instructor"><i class="fas fa-user-tie"></i> <?= htmlspecialchars($meta) ?></div>
                <?php endif; ?>
                <p class="cc-desc"><?= htmlspecialchars($shortDesc) ?></p>
            </div>
            <?php if ($footerInfo): ?>
            <div class="catalog-card-stats">
                <?php if ($enrollCount > 0 && $itemType === 'course'): ?>
                <span><i class="fas fa-users"></i> <?= $enrollCount ?> enrolled</span>
                <?php endif; ?>
                <span><i class="fas fa-info-circle"></i> <?= htmlspecialchars($footerInfo) ?></span>
            </div>
            <?php endif; ?>
            <div class="catalog-card-footer">
                <span class="cc-deadline"><?php if ($deadline): ?><i class="fas fa-clock"></i><?= htmlspecialchars($deadline) ?><?php endif; ?></span>
                <?php if ($itemType === 'course'): ?>
                    <?php if ($isEnrolled): ?>
                    <button class="cc-enroll-btn enrolled" disabled><i class="fas fa-check"></i> Enrolled</button>
                    <?php else: ?>
                    <button class="cc-enroll-btn enroll" data-course-id="<?= $id ?>"><i class="fas fa-plus-circle"></i> Enroll</button>
                    <?php endif; ?>
                <?php elseif ($itemType === 'video-conference'): ?>
                    <?php
                    $vcScheduled = !empty($item['scheduled_at']) ? strtotime($item['scheduled_at']) : 0;
                    $isPast = $vcScheduled > 0 && $vcScheduled < time();
                    $vcPlatform = $item['platform'] ?? 'other';
                    $pColor = $vcPlatform === 'zoom' ? '#2D8CFF' : ($vcPlatform === 'google_meet' ? '#00897B' : '#6c757d');
                    ?>
                    <div style="display:flex; align-items:center; gap:0.4rem;">
                        <span style="font-size:0.7rem; padding:0.2rem 0.5rem; border-radius:999px; background:<?= $pColor ?>; color:#fff; font-weight:700; text-transform:uppercase;"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $vcPlatform))) ?></span>
                        <?php if ($isPast): ?>
                        <button class="cc-enroll-btn enrolled" disabled style="font-size:0.72rem; padding:0.35rem 0.7rem;"><i class="fas fa-check"></i> Ended</button>
                        <?php else: ?>
                        <a href="<?= htmlspecialchars($item['meeting_link'] ?? '#') ?>" target="_blank" rel="noopener" class="cc-enroll-btn enroll" style="background:<?= $pColor ?>; font-size:0.72rem; padding:0.35rem 0.7rem; text-decoration:none;" onclick="event.stopPropagation();"><i class="fas fa-video"></i> Join</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                <button class="cc-enroll-btn enroll" style="background:rgba(32,0,130,0.08);color:var(--primary);" onclick="event.stopPropagation(); window.location.href='<?= htmlspecialchars($link) ?>'"><i class="fas fa-eye"></i> View</button>
                <?php endif; ?>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <!-- Empty State -->
    <div class="catalog-empty" id="catalog-empty" style="display:none;">
        <i class="fas fa-search"></i>
        <h4>No items found</h4>
        <p>Try selecting a different tab or adjusting your search.</p>
    </div>

    <!-- Pagination -->
    <div class="pagination-row" id="catalog-pagination">
        <button type="button" class="page-btn" data-action="prev">Prev</button>
        <span class="page-indicator" id="page-indicator">Page 1 of 1</span>
        <button type="button" class="page-btn" data-action="next">Next</button>
    </div>
</div>

<!-- Entity Detail Content -->
<div id="catalog-entity-content" style="display:none;">
    <div class="entity-content-box" data-size="standard">
        <div class="entity-content-header">
            <h2 id="cem-title"></h2>
            <div class="entity-content-actions">
                <a id="cem-view-btn" href="#" style="padding:0.6rem 0.95rem; background:var(--primary); color:var(--surface); border:none; border-radius:999px; cursor:pointer; font-weight:700; text-decoration:none; box-shadow:0 4px 15px rgba(32,0,130,0.15);">View Course</a>
                <button id="cem-enroll-btn" style="display:none; padding:0.6rem 0.95rem; background:#10b981; color:#fff; border:none; border-radius:999px; cursor:pointer; font-weight:700; box-shadow:0 4px 15px rgba(16,185,129,0.2);">Enroll Now</button>
                <button id="cem-close-btn" style="padding:0.6rem 0.95rem; background:transparent; color:var(--text); border:1px solid var(--border); border-radius:999px; cursor:pointer; font-weight:700;">Close</button>
            </div>
        </div>
        <div class="entity-content-tabs">
            <button type="button" class="entity-content-tab active" data-content-tab="overview" style="padding:0.7rem 1rem; border:none; background:rgba(32,0,130,0.08); color:var(--primary); border-radius:999px; font-weight:700; cursor:pointer;">Overview</button>
            <button type="button" class="entity-content-tab" data-content-tab="structure" style="padding:0.7rem 1rem; border:1px solid rgba(32,0,130,0.12); background:var(--surface, #fff); color:var(--text); border-radius:999px; font-weight:700; cursor:pointer;">Structure</button>
            <button type="button" class="entity-content-tab" data-content-tab="performance" style="padding:0.7rem 1rem; border:1px solid rgba(32,0,130,0.12); background:var(--surface, #fff); color:var(--text); border-radius:999px; font-weight:700; cursor:pointer;">Performance</button>
        </div>
        <div class="entity-content-body">
            <div id="entity-content-overview" class="entity-content-panel" style="display:block;">
                <div id="cem-overview-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:1rem; margin-bottom:1.5rem;"></div>
                <div>
                    <label style="color:var(--primary); font-weight:700; font-size:0.74rem; letter-spacing:0.08em; text-transform:uppercase;">Description</label>
                    <p id="cem-description" style="margin:0.75rem 0 0; font-size:1rem; line-height:1.7; color:var(--text);"></p>
                </div>
                <div id="cem-child-entities" style="margin-top:2rem; padding-top:1.5rem; border-top:1px solid rgba(32,0,130,0.12);"></div>
            </div>
            <div id="entity-content-structure" class="entity-content-panel" style="display:none;">
                <div id="cem-structure-content"><p style="text-align:center; color:#999;">Loading structure...</p></div>
            </div>
            <div id="entity-content-performance" class="entity-content-panel" style="display:none;">
                <div id="cem-performance-content"><p style="text-align:center; color:#999;">Loading performance data...</p></div>
            </div>
        </div>
    </div>
</div>

<!-- Request Course Modal -->
<div id="request-modal" class="modal-overlay" style="display:none; z-index:10000; overflow-y:auto; padding:2rem 0;">
    <div style="max-width:500px; margin:2rem auto; background:var(--surface, #fff); border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.2);">
        <div style="padding:2rem; border-bottom:1px solid rgba(32,0,130,0.08); display:flex; justify-content:space-between; align-items:center;">
            <h2 style="margin:0;"><i class="fas fa-plus-circle" style="color:var(--primary); margin-right:0.5rem;"></i> Request a Course</h2>
            <button type="button" id="close-request-modal" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text);">&times;</button>
        </div>
        <form id="request-course-form" style="padding:2rem;">
            <label style="display:block; margin-bottom:1.5rem;">
                <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Course Title *</span>
                <input type="text" name="requested_title" required placeholder="e.g. Advanced Leadership Skills" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;">
            </label>
            <label style="display:block; margin-bottom:1.5rem;">
                <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Description</span>
                <textarea name="description" rows="4" placeholder="Why would you like this course?" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box; resize:vertical;"></textarea>
            </label>
            <div id="request-status" style="margin-bottom:1rem; display:none; padding:0.75rem; border-radius:8px; font-weight:500;"></div>
            <div style="display:flex; gap:1rem; justify-content:flex-end;">
                <button type="button" id="cancel-request-btn" style="padding:0.75rem 1.5rem; border:1.5px solid rgba(32,0,130,0.15); border-radius:8px; background:transparent; font-weight:700; font-size:0.85rem; cursor:pointer;">Cancel</button>
                <button type="submit" style="padding:0.75rem 1.5rem; background:var(--primary); color:var(--surface); border:none; border-radius:8px; cursor:pointer; font-weight:700; font-size:0.85rem;">Submit Request</button>
            </div>
        </form>
    </div>
</div>

<script src="js/catalog-browse.js"></script>
