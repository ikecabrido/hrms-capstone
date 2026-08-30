<?php
include_once __DIR__ . '/../../classes/Employee.php';
$employeeClass = new Employee();
$instructorId = (int) ($employeeClass->getEmployeeId() ?? 0);

$courseRows = [];
$moduleRows = [];
$lessonRows = [];
$quizRows = [];
$evaluationRows = [];

try {
    require_once dirname(__DIR__, 4) . '/database/db.php';
    $database = new Database();
    $pdo = $database->getConnection();

    // Guard each query separately so one failing table can't blank the other tabs.
    $runQuery = function (PDO $pdo, string $sql): array {
        try {
            return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('elearning.php query failed: ' . $e->getMessage());
            return [];
        }
    };

    $courseRows = $runQuery($pdo, "SELECT id, title, description, category, status, created_at FROM ld_course WHERE status IN ('active','draft') ORDER BY created_at DESC");

    $courseIds = array_map('intval', array_column($courseRows, 'id'));
    if (!empty($courseIds)) {
        $cids = implode(',', $courseIds);
        $moduleRows = $runQuery($pdo, "SELECT m.id, m.title, m.description, m.status, m.created_at, c.title AS course_title FROM ld_module m JOIN ld_course c ON c.id = m.course_id WHERE c.id IN ($cids) ORDER BY m.created_at DESC");

        $moduleIds = array_map('intval', array_column($moduleRows, 'id'));
        $mids = !empty($moduleIds) ? implode(',', $moduleIds) : '0';
        // ld_lesson has no description column - fall back to content body / video url.
        $lessonRows = $runQuery($pdo, "SELECT l.id, l.title, COALESCE(NULLIF(l.content_body, ''), l.video_url, '') AS description, l.status, l.created_at, m.title AS module_title FROM ld_lesson l JOIN ld_module m ON m.id = l.module_id WHERE m.id IN ($mids) ORDER BY l.created_at DESC");

        // ld_quiz has no description column - summarize its settings instead.
        $quizRows = $runQuery($pdo, "SELECT q.id, q.title, CONCAT('Pass: ', COALESCE(q.passing_score, 0), '% | ', COALESCE(q.question_count, 0), ' questions') AS description, q.passing_score, q.status, q.created_at, m.title AS module_title FROM ld_quiz q JOIN ld_module m ON m.id = q.module_id WHERE m.id IN ($mids) ORDER BY q.created_at DESC");

        // ld_evaluation has no description column - summarize its settings instead.
        $evaluationRows = $runQuery($pdo, "SELECT e.id, e.title, CONCAT('Passing score: ', COALESCE(e.passing_score, 0), '% | ', COALESCE(e.max_attempts, 0), ' attempts') AS description, e.status, e.created_at, c.title AS course_title FROM ld_evaluation e JOIN ld_course c ON c.id = e.course_id WHERE c.id IN ($cids) ORDER BY e.created_at DESC");
    }
} catch (Throwable $e) {
    error_log('elearning.php bootstrap failed: ' . $e->getMessage());
}

function renderCatalogCards($items, $type, $typeIcons, $catGradients) {
    if (empty($items)) return;
    $catGradients = $catGradients ?? [];
    $gradients = [
        'course' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        'module' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
        'lesson' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
        'quiz' => 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
        'evaluation' => 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
    ];
    $links = [
        'course' => '?page=instructor/elearning-subpage/course&id=',
        'module' => '?page=instructor/elearning-subpage/module&id=',
        'lesson' => '?page=instructor/elearning-subpage/lesson&id=',
        'quiz' => '?page=instructor/elearning-subpage/quiz&id=',
        'evaluation' => '?page=instructor/elearning-subpage/evaluation&id=',
    ];
    foreach ($items as $item) {
        $id = (int)($item['id'] ?? 0);
        $title = htmlspecialchars(trim($item['title'] ?? 'Untitled'));
        $status = htmlspecialchars(trim($item['status'] ?? 'active'));
        $cat = strtolower(trim($item['category'] ?? $type));
        $gradient = $catGradients[$cat] ?? ($gradients[$type] ?? $gradients['course']);
        $icon = $typeIcons[$type] ?? 'fa-cube';
        $link = ($links[$type] ?? '#') . $id;
        $desc = trim($item['description'] ?? '');
        $shortDesc = $desc !== '' ? htmlspecialchars(mb_substr(strip_tags($desc), 0, 100)) : 'No description available.';
        $meta = '';
        if ($type === 'quiz' && isset($item['passing_score'])) $meta = 'Pass: ' . $item['passing_score'] . '%';
        if ($type === 'lesson' && !empty($item['module_title'])) $meta = htmlspecialchars($item['module_title']);
        if ($type === 'module' && !empty($item['course_title'])) $meta = htmlspecialchars($item['course_title']);
        if ($type === 'course' && !empty($item['category'])) $meta = htmlspecialchars($item['category']);

        echo '<article class="catalog-card" data-type="' . $type . '" data-entity-id="' . $id . '" data-status="' . $status . '">';
        echo '<div class="catalog-card-thumb" style="background:' . $gradient . ';">';
        echo '<i class="fas ' . $icon . ' thumb-icon"></i>';
        echo '<div class="thumb-overlay"></div>';
        echo '<span class="catalog-card-type-badge">' . ucfirst(htmlspecialchars($type)) . '</span>';
        echo '</div>';
        echo '<div class="catalog-card-body"><h4>' . $title . '</h4>';
        echo '<p class="cc-desc">' . $shortDesc . '</p></div>';
        if ($meta) {
            echo '<div class="catalog-card-stats"><span><i class="fas fa-info-circle"></i> ' . $meta . '</span></div>';
        }
        echo '<div class="catalog-card-footer">';
        echo '<span class="cc-deadline" style="font-size:0.72rem;color:var(--muted);text-transform:capitalize;">' . $status . '</span>';
        echo '<button class="cc-enroll-btn enroll" style="background:rgba(32,0,130,0.08);color:var(--primary);" onclick="event.stopPropagation();window.location.href=\'' . htmlspecialchars($link) . '\';"><i class="fas fa-eye"></i> View</button>';
        echo '</div></article>';
    }
}
?>

<style>
.catalog-toolbar{position:sticky;top:calc(var(--header-height,60px) + 0.75rem);z-index:100;display:flex;align-items:center;gap:0.75rem;padding:0.75rem 1rem;background:linear-gradient(135deg, rgba(32,0,130,0.08), rgba(81,70,183,0.06));border-radius:12px;border:1.5px solid rgba(32,0,130,0.15);margin-bottom:1rem;flex-wrap:wrap;box-shadow:0 4px 16px rgba(32,0,130,0.08);backdrop-filter:blur(10px);}
.catalog-toolbar .catalog-search input,.catalog-toolbar select,.catalog-toolbar .catalog-view-toggle,.catalog-toolbar .catalog-request-btn{background:var(--surface,#fff)!important;border-color:rgba(32,0,130,0.15)!important;}
.catalog-search{flex:1;min-width:200px;position:relative;}
.catalog-search input{width:100%;padding:0.6rem 1rem 0.6rem 2.5rem;border:1.5px solid rgba(32,0,130,0.1);border-radius:10px;background:rgba(32,0,130,0.03);font-size:0.88rem;outline:none;transition:border-color 0.2s,box-shadow 0.2s;box-sizing:border-box;}
.catalog-search input:focus{border-color:var(--primary,#320082);box-shadow:0 0 0 3px rgba(32,0,130,0.08);}
.catalog-search i{position:absolute;left:0.85rem;top:50%;transform:translateY(-50%);color:var(--muted,#999);font-size:0.85rem;}
.catalog-view-toggle{display:flex;border:1.5px solid rgba(32,0,130,0.1);border-radius:8px;overflow:hidden;}
.catalog-view-toggle button{padding:0.45rem 0.7rem;border:none;background:transparent;color:var(--muted);cursor:pointer;font-size:0.85rem;transition:all 0.15s;}
.catalog-view-toggle button.active{background:var(--primary);color:#fff;}
.catalog-request-btn{padding:0.5rem 1rem;background:var(--primary);color:#fff;border:none;border-radius:999px;font-weight:700;font-size:0.82rem;cursor:pointer;text-decoration:none;white-space:nowrap;display:inline-flex;align-items:center;gap:0.4rem;transition:opacity 0.2s;}
.catalog-request-btn:hover{opacity:0.85;}
.catalog-add-wrap{position:relative;display:inline-flex;flex-direction:column;align-items:center;}
.catalog-add-btn{width:2.4rem;height:2.4rem;border:none;border-radius:50%;background:var(--primary);color:#fff;font-size:1.05rem;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(32,0,130,0.25);transition:transform 0.15s, opacity 0.15s;}
.catalog-add-btn:hover{opacity:0.88;transform:scale(1.06);}
.catalog-add-tip{position:absolute;top:calc(100% + 0.5rem);left:50%;transform:translateX(-50%) translateY(-4px);background:var(--primary);color:#fff;padding:0.35rem 0.8rem;border-radius:999px;font-size:0.75rem;font-weight:700;white-space:nowrap;opacity:0;pointer-events:none;transition:opacity 0.18s, transform 0.18s;z-index:50;box-shadow:0 4px 12px rgba(32,0,130,0.3);}
.catalog-add-tip::before{content:'';position:absolute;top:-4px;left:50%;transform:translateX(-50%) rotate(45deg);width:8px;height:8px;background:var(--primary);border-radius:2px;}
.catalog-add-wrap:hover .catalog-add-tip,.catalog-add-wrap:focus-within .catalog-add-tip{opacity:1;transform:translateX(-50%) translateY(0);}
.catalog-count{font-size:0.8rem;color:var(--muted,#999);white-space:nowrap;}
.catalog-tabs{display:flex;gap:6px;margin-bottom:1rem;overflow-x:auto;scrollbar-width:none;background:rgba(32,0,130,0.04);border-radius:12px;padding:4px;}
.catalog-tabs::-webkit-scrollbar{display:none}
.catalog-tab-btn{flex:1 1 0;min-width:0;padding:0.55rem 0.5rem;border:none;border-radius:8px;background:rgba(255,255,255,0.7);color:var(--text,#333);font-size:0.78rem;font-weight:600;cursor:pointer;white-space:nowrap;transition:all 0.2s;display:flex;align-items:center;justify-content:center;gap:0.35rem;}
.catalog-tab-btn:hover{background:rgba(255,255,255,0.95)}
.catalog-tab-btn.active{background:var(--primary,#320082);color:#fff;box-shadow:0 2px 8px rgba(32,0,130,0.3)}
.catalog-tab-count{display:inline-flex;align-items:center;justify-content:center;min-width:22px;height:20px;padding:0 6px;border-radius:999px;background:rgba(32,0,130,0.1);font-size:0.7rem;font-weight:700;color:var(--text,#333)}
.catalog-tab-btn.active .catalog-tab-count{background:rgba(255,255,255,0.25);color:#fff}
.catalog-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1.25rem;margin-bottom:1.5rem;}
.catalog-card{background:var(--surface,#fff);border-radius:14px;overflow:hidden;border:1px solid rgba(32,0,130,0.08);cursor:pointer;transition:transform 0.25s,box-shadow 0.25s,border-color 0.25s;position:relative;}
.catalog-card:hover{transform:translateY(-4px);box-shadow:0 12px 35px rgba(32,0,130,0.12);border-color:rgba(32,0,130,0.2);}
.catalog-card-thumb{height:140px;display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden;}
.catalog-card-thumb .thumb-icon{font-size:2.5rem;color:rgba(255,255,255,0.9);z-index:1;}
.catalog-card-thumb .thumb-overlay{position:absolute;inset:0;background:linear-gradient(180deg,transparent 40%,rgba(0,0,0,0.3) 100%);}
.catalog-card-type-badge{position:absolute;top:0.75rem;left:0.75rem;padding:0.2rem 0.6rem;border-radius:6px;background:rgba(255,255,255,0.9);color:var(--primary);font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;z-index:2;}
.catalog-card-body{padding:1rem 1.1rem 0.75rem;}
.catalog-card-body h4{margin:0 0 0.35rem;font-size:1rem;font-weight:700;color:var(--text);line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
.catalog-card-body .cc-desc{font-size:0.82rem;color:var(--muted);line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin:0 0 0.75rem;}
.catalog-card-stats{display:flex;gap:0.75rem;padding:0.6rem 1.1rem;border-top:1px solid rgba(32,0,130,0.06);font-size:0.75rem;color:var(--muted);}
.catalog-card-stats span{display:flex;align-items:center;gap:0.3rem;}
.catalog-card-stats i{color:var(--primary);font-size:0.7rem;}
.catalog-card-footer{display:flex;align-items:center;justify-content:space-between;padding:0.6rem 1.1rem;border-top:1px solid rgba(32,0,130,0.06);}
.catalog-card-footer .cc-deadline{font-size:0.75rem;color:var(--muted);}
.cc-enroll-btn{padding:0.45rem 1rem;border-radius:999px;border:none;font-size:0.78rem;font-weight:700;cursor:pointer;transition:all 0.2s;}
.cc-enroll-btn.enroll{background:var(--primary);color:#fff;}
.cc-enroll-btn.enroll:hover{opacity:0.85;transform:scale(1.03);}
.catalog-grid.list-view{grid-template-columns:1fr;gap:0.75rem;}
.catalog-grid.list-view .catalog-card{display:flex;flex-direction:row;}
.catalog-grid.list-view .catalog-card-thumb{width:180px;height:auto;min-height:120px;flex-shrink:0;}
.catalog-grid.list-view .catalog-card-body,.catalog-grid.list-view .catalog-card-stats,.catalog-grid.list-view .catalog-card-footer{border-top:none;}
.catalog-grid.list-view .catalog-card-body{flex:1;padding-top:1rem;}
.catalog-empty{grid-column:1/-1;text-align:center;padding:3rem 1rem;color:var(--muted);}
.catalog-empty i{font-size:2rem;margin-bottom:0.5rem;display:block;}
.catalog-empty h4{margin:0 0 0.5rem;}
.catalog-empty p{margin:0;}
@media(max-width:1200px){.catalog-grid{grid-template-columns:repeat(3,1fr)}}
@media(max-width:900px){.catalog-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:768px){.catalog-grid{grid-template-columns:repeat(2,1fr);gap:1rem}.catalog-toolbar{flex-wrap:nowrap}.catalog-search{min-width:0;flex:1}}
@media(max-width:480px){.catalog-grid{grid-template-columns:1fr}}
</style>

<div class="module-content">
    <!-- Toolbar -->
    <div class="catalog-toolbar">
        <div class="catalog-search">
            <i class="fas fa-search"></i>
            <input type="search" id="catalog-search-input" placeholder="Search courses, modules, lessons..." aria-label="Search elearning" />
        </div>
        <div class="catalog-view-toggle">
            <button type="button" class="active" data-view="grid" title="Grid view"><i class="fas fa-th"></i></button>
            <button type="button" data-view="list" title="List view"><i class="fas fa-list"></i></button>
        </div>
        <span class="catalog-count" id="catalog-count"></span>
        <select id="catalog-page-size" style="border:1.5px solid rgba(32,0,130,0.2); background:var(--surface,#fff); color:var(--text,#333); border-radius:8px; padding:0.45rem 0.6rem; font-size:0.78rem; font-weight:600; cursor:pointer; outline:none;">
            <option value="12">12</option>
            <option value="24">24</option>
            <option value="36">36</option>
        </select>
        <div class="catalog-add-wrap" id="catalog-add-wrap">
            <button type="button" id="catalog-add-btn" class="catalog-add-btn" title="Add" aria-label="Add"><i class="fas fa-plus"></i></button>
            <span class="catalog-add-tip" id="catalog-add-tip">Add Course</span>
        </div>
    </div>

    <!-- Tabs -->
    <div class="catalog-tabs" id="catalog-tabs">
        <button type="button" class="catalog-tab-btn active" data-tab="tab-course">
            <i class="fas fa-graduation-cap"></i> Courses
            <span class="catalog-tab-count"><?= count($courseRows) ?></span>
        </button>
        <button type="button" class="catalog-tab-btn" data-tab="tab-module">
            <i class="fas fa-cube"></i> Modules
            <span class="catalog-tab-count"><?= count($moduleRows) ?></span>
        </button>
        <button type="button" class="catalog-tab-btn" data-tab="tab-lesson">
            <i class="fas fa-book-open"></i> Lessons
            <span class="catalog-tab-count"><?= count($lessonRows) ?></span>
        </button>
        <button type="button" class="catalog-tab-btn" data-tab="tab-quiz">
            <i class="fas fa-question-circle"></i> Quizzes
            <span class="catalog-tab-count"><?= count($quizRows) ?></span>
        </button>
        <button type="button" class="catalog-tab-btn" data-tab="tab-evaluation">
            <i class="fas fa-clipboard-check"></i> Evaluations
            <span class="catalog-tab-count"><?= count($evaluationRows) ?></span>
        </button>
        <button type="button" class="catalog-tab-btn" data-tab="tab-template">
            <i class="fas fa-layer-group"></i> Templates
        </button>
    </div>

    <!-- Tab Content -->
    <div class="tab-content active" data-tab="tab-course">
        <div class="catalog-grid" id="catalog-grid">
            <?php $typeIcons = ['course' => 'fa-graduation-cap', 'module' => 'fa-cube', 'lesson' => 'fa-book-open', 'quiz' => 'fa-question-circle', 'evaluation' => 'fa-clipboard-check']; $catGradients = []; renderCatalogCards($courseRows, 'course', $typeIcons, $catGradients); ?>
        </div>
    </div>

    <div class="tab-content" data-tab="tab-module">
        <div class="catalog-grid">
            <?php renderCatalogCards($moduleRows, 'module', $typeIcons, $catGradients); ?>
        </div>
    </div>

    <div class="tab-content" data-tab="tab-lesson">
        <div class="catalog-grid">
            <?php renderCatalogCards($lessonRows, 'lesson', $typeIcons, $catGradients); ?>
        </div>
    </div>

    <div class="tab-content" data-tab="tab-quiz">
        <div class="catalog-grid">
            <?php renderCatalogCards($quizRows, 'quiz', $typeIcons, $catGradients); ?>
        </div>
    </div>

    <div class="tab-content" data-tab="tab-evaluation">
        <div class="catalog-grid">
            <?php renderCatalogCards($evaluationRows, 'evaluation', $typeIcons, $catGradients); ?>
        </div>
    </div>

    <div class="tab-content" data-tab="tab-template">
        <div style="padding:0 0 1rem; display:flex; justify-content:space-between; align-items:center;">
            <p style="margin:0; color:var(--muted); font-size:0.9rem;">Reuse course structures. Save from the Structure Builder.</p>
            <a href="?page=instructor/elearning-subpage/course-structure" class="catalog-request-btn"><i class="fas fa-layer-group"></i> Open Builder</a>
        </div>
        <div class="catalog-grid" id="templates-grid">
            <div style="grid-column:1/-1; text-align:center; padding:2rem; color:var(--muted);">Loading templates...</div>
        </div>
    </div>

    <!-- Empty State -->
    <div class="catalog-empty" id="catalog-empty" style="display:none;">
        <i class="fas fa-search"></i>
        <h4>No items found</h4>
        <p>Try selecting a different tab or adjusting your search.</p>
    </div>

    <!-- Pagination -->
    <div class="pagination-row" id="catalog-pagination">
        <button type="button" class="page-btn" data-action="prev" disabled>Prev</button>
        <span class="page-indicator" id="page-indicator">Page 1 of 1</span>
        <button type="button" class="page-btn" data-action="next">Next</button>
    </div>

    <!-- Clone Template Modal -->
    <div id="clone-template-modal" class="modal-overlay" style="display:none; z-index:10000;">
        <div style="background:var(--surface,#fff); border:1px solid rgba(32,0,130,0.12); border-radius:18px; width:min(440px,92vw); box-shadow:0 18px 45px rgba(32,0,130,0.18);">
            <div style="display:flex; justify-content:space-between; align-items:center; padding:1.25rem 1.5rem; border-bottom:1px solid rgba(32,0,130,0.12); background:linear-gradient(135deg, rgba(32,0,130,0.08), rgba(81,70,183,0.05));">
                <h2 style="margin:0; font-size:1.1rem; color:var(--primary);"><i class="fas fa-copy" style="margin-right:0.4rem;"></i>Clone Template</h2>
                <button type="button" id="clone-modal-close" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text);">✕</button>
            </div>
            <div style="padding:1.5rem;">
                <label style="display:block; margin-bottom:1.25rem;">
                    <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">New course title</span>
                    <input type="text" id="clone-title-input" placeholder="Enter a title for the new course" style="width:100%; padding:0.8rem; border-radius:8px; border:1.5px solid rgba(32,0,130,0.15); box-sizing:border-box; font-size:0.95rem;" />
                </label>
                <div style="display:flex; gap:0.75rem;">
                    <button type="button" id="clone-modal-confirm" style="flex:1; padding:0.75rem; background:var(--primary); color:var(--surface); border:none; border-radius:8px; cursor:pointer; font-weight:700; font-size:0.9rem;">Clone</button>
                    <button type="button" id="clone-modal-cancel" style="flex:1; padding:0.75rem; background:rgba(32,0,130,0.08); color:var(--primary); border:1px solid rgba(32,0,130,0.18); border-radius:8px; cursor:pointer; font-weight:700; font-size:0.9rem;">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function loadTemplates() {
        fetch('pages/instructor/elearning-subpage/ajax/get-templates.php', {credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'}})
        .then(function(r){return r.json();})
        .then(function(data){
            var grid=document.getElementById('templates-grid');
            if(!grid)return;
            var templates=data.success?data.items:[];
            if(templates.length===0){grid.innerHTML='<div style="grid-column:1/-1;text-align:center;padding:3rem 1rem;color:var(--muted);"><i class="fas fa-layer-group" style="font-size:2rem;margin-bottom:0.5rem;display:block;"></i>No templates yet. Go to Structure Builder and save a course as template.</div>';return;}
            grid.innerHTML=templates.map(function(t){
                return '<article class="catalog-card"><div class="catalog-card-thumb" style="background:linear-gradient(135deg,#667eea,#764ba2);"><i class="fas fa-layer-group thumb-icon"></i><div class="thumb-overlay"></div><span class="catalog-card-type-badge">Template</span></div><div class="catalog-card-body"><h4>'+t.title+'</h4>'+(t.description?'<p class="cc-desc">'+t.description.substring(0,100)+'</p>':'')+'</div><div class="catalog-card-stats"><span><i class="fas fa-cube"></i> '+t.module_count+' modules</span><span><i class="fas fa-book-open"></i> '+t.lesson_count+' lessons</span></div><div class="catalog-card-footer"><span class="cc-deadline">Created '+new Date(t.created_at).toLocaleDateString()+'</span><button class="cc-enroll-btn enroll" style="background:rgba(32,0,130,0.08);color:var(--primary);" onclick="event.stopPropagation();cloneTemplate('+t.id+');"><i class="fas fa-copy"></i> Clone</button></div></article>';
            }).join('');
        }).catch(function(){var g=document.getElementById('templates-grid');if(g)g.innerHTML='<div style="grid-column:1/-1;text-align:center;padding:2rem;color:#ef4444;">Failed to load templates.</div>';});
    }
    var _pendingCloneId=null;
    function cloneTemplate(templateId){
        _pendingCloneId=templateId;
        var input=document.getElementById('clone-title-input');
        input.value='(Copy)';
        document.getElementById('clone-template-modal').style.display='flex';
        setTimeout(function(){input.focus();input.select();},50);
    }
    function doCloneTemplate(){
        var title=document.getElementById('clone-title-input').value.trim();
        if(!title){document.getElementById('clone-title-input').focus();return;}
        document.getElementById('clone-template-modal').style.display='none';
        var id=_pendingCloneId;_pendingCloneId=null;
        fetch('pages/instructor/elearning-subpage/ajax/clone-template.php',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},body:JSON.stringify({template_id:id,title:title})})
        .then(function(r){return r.json();})
        .then(function(data){if(data.success){if(window.showToast)window.showToast(data.message||'Course created','success');setTimeout(function(){window.location.href='?page=instructor/elearning-subpage/course-structure&course_id='+data.course_id;},800);}else{if(window.showToast)window.showToast(data.message||'Failed','error');}})
        .catch(function(){if(window.showToast)window.showToast('Network error','error');});
    }
    function closeCloneModal(){document.getElementById('clone-template-modal').style.display='none';_pendingCloneId=null;}
    document.getElementById('clone-modal-confirm').addEventListener('click',doCloneTemplate);
    document.getElementById('clone-modal-cancel').addEventListener('click',closeCloneModal);
    document.getElementById('clone-modal-close').addEventListener('click',closeCloneModal);
    document.getElementById('clone-template-modal').addEventListener('click',function(e){if(e.target.id==='clone-template-modal')closeCloneModal();});
    document.getElementById('clone-title-input').addEventListener('keydown',function(e){if(e.key==='Enter')doCloneTemplate();if(e.key==='Escape')closeCloneModal();});
    </script>
    <script>
    (function(){
        var PAGE_SIZE=12,currentPage=1,activeTab='tab-course';
        var searchInput=document.getElementById('catalog-search-input');
        var countEl=document.getElementById('catalog-count');
        var emptyEl=document.getElementById('catalog-empty');
        var paginationEl=document.getElementById('catalog-pagination');
        var addWrap=document.getElementById('catalog-add-wrap');
        var addBtn=document.getElementById('catalog-add-btn');
        var addTip=document.getElementById('catalog-add-tip');
        var addConfig={
            'tab-course':{label:'Add Course',url:'?page=instructor/elearning-subpage/course'},
            'tab-module':{label:'Add Module',url:'?page=instructor/elearning-subpage/module'},
            'tab-lesson':{label:'Add Lesson',url:'?page=instructor/elearning-subpage/lesson'},
            'tab-quiz':{label:'Add Quiz',url:'?page=instructor/elearning-subpage/quiz'},
            'tab-evaluation':{label:'Add Evaluation',url:'?page=instructor/elearning-subpage/evaluation'},
            'tab-template':null
        };
        function syncAddBtn(){
            if(!addWrap)return;
            var cfg=addConfig[activeTab];
            if(!cfg){addWrap.style.display='none';return;}
            addWrap.style.display='';
            if(addTip)addTip.textContent=cfg.label;
            if(addBtn){addBtn.title=cfg.label;addBtn.setAttribute('aria-label',cfg.label);}
        }
        if(addBtn)addBtn.addEventListener('click',function(){var cfg=addConfig[activeTab];if(cfg)window.location.href=cfg.url;});
        function getGrid(){var tc=document.querySelector('.tab-content[data-tab="'+activeTab+'"]');return tc?tc.querySelector('.catalog-grid'):null;}
        function getCards(){var g=getGrid();return g?Array.from(g.querySelectorAll('.catalog-card')):[];}
        function updateGrid(){
            var cards=getCards(),q=(searchInput?searchInput.value:'').toLowerCase().trim();
            var visible=cards.filter(function(c){return q===''||c.textContent.toLowerCase().indexOf(q)>-1;});
            var totalPages=Math.max(1,Math.ceil(visible.length/PAGE_SIZE));
            currentPage=Math.min(currentPage,totalPages);
            var start=(currentPage-1)*PAGE_SIZE;
            cards.forEach(function(c){c.style.display='none';});
            visible.forEach(function(c,i){c.style.display=(i>=start&&i<start+PAGE_SIZE)?'':'none';});
            if(countEl)countEl.textContent=visible.length+' items';
            if(emptyEl)emptyEl.style.display=visible.length===0?'':'none';
            if(paginationEl){paginationEl.querySelector('.page-indicator').textContent='Page '+currentPage+' of '+totalPages;paginationEl.querySelector('[data-action="prev"]').disabled=currentPage<=1;paginationEl.querySelector('[data-action="next"]').disabled=currentPage>=totalPages;paginationEl.style.display=totalPages<=1?'none':'';}
        }
        document.querySelectorAll('.catalog-tab-btn').forEach(function(btn){
            btn.addEventListener('click',function(){
                document.querySelectorAll('.catalog-tab-btn').forEach(function(b){b.classList.remove('active');});
                btn.classList.add('active');
                var tabId=btn.dataset.tab;
                document.querySelectorAll('.tab-content').forEach(function(tc){tc.classList.remove('active');});
                var target=document.querySelector('.tab-content[data-tab="'+tabId+'"]');
                if(target)target.classList.add('active');
                activeTab=tabId;currentPage=1;updateGrid();syncAddBtn();
                if(tabId==='tab-template')loadTemplates();
            });
        });
        if(searchInput)searchInput.addEventListener('input',function(){currentPage=1;updateGrid();});
        document.querySelectorAll('.catalog-view-toggle button').forEach(function(btn){
            btn.addEventListener('click',function(){
                document.querySelectorAll('.catalog-view-toggle button').forEach(function(b){b.classList.remove('active');});
                btn.classList.add('active');
                var g=getGrid();if(g)g.classList.toggle('list-view',btn.dataset.view==='list');
            });
        });
        if(paginationEl){paginationEl.addEventListener('click',function(e){var b=e.target.closest('[data-action]');if(!b||b.disabled)return;if(b.dataset.action==='prev'&&currentPage>1)currentPage--;if(b.dataset.action==='next')currentPage++;updateGrid();});}
        var pageSizeSelect=document.getElementById('catalog-page-size');
        if(pageSizeSelect){pageSizeSelect.addEventListener('change',function(){PAGE_SIZE=parseInt(this.value,10)||12;currentPage=1;updateGrid();});}
        updateGrid();
        syncAddBtn();
    })();
    </script>
</div>

<!-- Entity Detail Content -->
<div id="entity-content" style="display:none;">
    <div id="entity-content-panel" class="entity-content-box" data-size="standard">
        <div class="entity-content-header">
            <h2 id="modal-title"></h2>
            <div class="entity-content-actions">
                <button id="modal-edit-btn" style="padding:0.6rem 0.95rem; background:var(--primary); color:var(--surface); border:none; border-radius:999px; cursor:pointer; font-weight:700;">Edit</button>
                <button id="modal-archive-btn" style="padding:0.6rem 0.95rem; background:rgba(32,0,130,0.08); color:var(--primary); border:1px solid rgba(32,0,130,0.18); border-radius:999px; cursor:pointer; font-weight:700;">Archive</button>
                <button id="modal-close-btn" style="padding:0.6rem 0.95rem; background:transparent; color:var(--text); border:1px solid var(--border); border-radius:999px; cursor:pointer; font-weight:700;">Close</button>
            </div>
        </div>
        <div class="entity-content-tabs">
            <button type="button" class="entity-content-tab active" data-content-tab="overview" style="padding:0.7rem 1rem; border:none; background:rgba(32,0,130,0.08); color:var(--primary); border-radius:999px; font-weight:700; cursor:pointer;">Overview</button>
            <button type="button" class="entity-content-tab" data-content-tab="structure" style="padding:0.7rem 1rem; border:1px solid rgba(32,0,130,0.12); background:var(--surface, #fff); color:var(--text); border-radius:999px; font-weight:700; cursor:pointer;">Structure</button>
            <button type="button" class="entity-content-tab" data-content-tab="monitor" style="padding:0.7rem 1rem; border:1px solid rgba(32,0,130,0.12); background:var(--surface, #fff); color:var(--text); border-radius:999px; font-weight:700; cursor:pointer;">Monitor</button>
        </div>
        <div class="entity-content-body">
            <div id="entity-content-overview" class="entity-content-panel" style="display:block;">
                <div id="modal-content-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
                    <div><label style="color:var(--primary); font-weight:700; font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase;">Status</label><p id="modal-overview-status" style="margin:0.55rem 0 0 0; font-size:1rem; color:var(--text);">Active</p></div>
                    <div><label style="color:var(--primary); font-weight:700; font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase;">Parent</label><p id="modal-overview-parent" style="margin:0.55rem 0 0 0; font-size:1rem; color:var(--text);">-</p></div>
                    <div><label style="color:var(--primary); font-weight:700; font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase;">Children</label><p id="modal-overview-children" style="margin:0.55rem 0 0 0; font-size:1rem; color:var(--text);">0</p></div>
                </div>
                <div><label style="color:var(--primary); font-weight:700; font-size:0.74rem; letter-spacing:0.08em; text-transform:uppercase;">Description</label><p id="modal-description" style="margin:0.75rem 0 0 0; font-size:1rem; line-height:1.7; color:var(--text);">-</p></div>
            </div>
            <div id="entity-content-structure" class="entity-content-panel" style="display:none;"><div id="entity-content-structure-content"></div></div>
            <div id="entity-content-monitor" class="entity-content-panel" style="display:none;"><div id="entity-content-monitor-content"></div></div>
        </div>
    </div>
</div>

<!-- Archive Confirmation Modal -->
<div id="archive-confirm-overlay" class="modal-overlay" style="display:none; z-index:10001;">
    <div style="background:var(--surface,#fff); border:1px solid rgba(32,0,130,0.12); border-radius:18px; width:min(420px,90vw); box-shadow:0 18px 45px rgba(32,0,130,0.18);">
        <div style="padding:1.5rem 1.5rem 0;">
            <h3 style="margin:0 0 0.5rem; font-size:1.1rem; color:var(--primary);"><i class="fas fa-box-archive" style="margin-right:0.4rem;"></i>Archive Item</h3>
            <p id="archive-confirm-message" style="margin:0; color:var(--text); font-size:0.92rem; line-height:1.5;">Are you sure you want to archive this item?</p>
        </div>
        <div style="display:flex; gap:0.75rem; padding:1.25rem 1.5rem 1.5rem; justify-content:flex-end;">
            <button type="button" id="archive-confirm-cancel" style="padding:0.6rem 1.2rem; background:rgba(32,0,130,0.08); color:var(--primary); border:1px solid rgba(32,0,130,0.18); border-radius:999px; cursor:pointer; font-weight:700; font-size:0.85rem;">Cancel</button>
            <button type="button" id="archive-confirm-ok" style="padding:0.6rem 1.2rem; background:#ef4444; color:#fff; border:none; border-radius:999px; cursor:pointer; font-weight:700; font-size:0.85rem;">Archive</button>
        </div>
    </div>
</div>

<script>
(function(){
    var entityContent=document.getElementById('entity-content');
    var entityModal=document.getElementById('entity-content-panel');
    var modalTitle=document.getElementById('modal-title');
    var modalEditBtn=document.getElementById('modal-edit-btn');
    var modalArchiveBtn=document.getElementById('modal-archive-btn');
    var modalCloseBtn=document.getElementById('modal-close-btn');
    var state={type:null,id:null,activeTab:'overview'};
    var entityConfig={
        'Course':{editUrl:'?page=instructor/elearning-subpage/course',archiveEndpoint:'elearning-subpage/ajax/archive-course.php'},
        'Module':{editUrl:'?page=instructor/elearning-subpage/module',archiveEndpoint:'elearning-subpage/ajax/archive-module.php'},
        'Lesson':{editUrl:'?page=instructor/elearning-subpage/lesson',archiveEndpoint:'elearning-subpage/ajax/archive-lesson.php'},
        'Quiz':{editUrl:'?page=instructor/elearning-subpage/quiz',archiveEndpoint:'elearning-subpage/ajax/archive-quiz.php'},
        'Evaluation':{editUrl:'?page=instructor/elearning-subpage/evaluation',archiveEndpoint:'elearning-subpage/ajax/archive-evaluation.php'}
    };

    function syncContentTabs(){
        document.querySelectorAll('.entity-content-tab').forEach(function(b){
            var isActive=b.dataset.contentTab===state.activeTab;
            b.classList.toggle('active',isActive);
            b.style.background=isActive?'rgba(32,0,130,0.08)':'var(--surface,#fff)';
            b.style.color=isActive?'var(--primary)':'var(--text)';
            b.style.border=isActive?'none':'1px solid rgba(32,0,130,0.12)';
        });
        document.querySelectorAll('.entity-content-panel').forEach(function(p){p.style.display='none';});
        var active=document.getElementById('entity-content-'+state.activeTab);
        if(active)active.style.display='block';
    }

    function renderOverview(type,data,desc,status){
        document.getElementById('modal-overview-status').textContent=data.status||status||'Active';
        document.getElementById('modal-description').textContent=data.description||desc||'No description';
        var config=entityConfig[type];
        if(config)document.getElementById('modal-overview-parent').textContent=type;
    }

    function renderStructurePanel(type,id){
        var el=document.getElementById('entity-content-structure-content');
        if(!el)return;
        el.innerHTML='<p style="color:var(--muted);text-align:center;">Loading structure...</p>';
        var url=null;
        if(type==='Course')url='pages/instructor/elearning-subpage/ajax/get-lessons-by-course.php?course_id='+id;
        else if(type==='Module')url='pages/instructor/elearning-subpage/ajax/get-lessons-by-module.php?module_id='+id;
        else if(type==='Lesson')url='pages/instructor/elearning-subpage/ajax/get-quizzes-by-lesson.php?lesson_id='+id;
        else{el.innerHTML='<p style="color:var(--muted);text-align:center;">No structure available.</p>';return;}
        fetch(url,{credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'}})
        .then(function(r){return r.json();})
        .then(function(data){
            var items=data.success&&Array.isArray(data.items)?data.items:[];
            if(items.length===0){el.innerHTML='<p style="color:var(--muted);text-align:center;">No items found.</p>';return;}
            var html='<div style="display:flex;flex-direction:column;gap:0.5rem;">';
            items.forEach(function(item,i){
                var label=item.title||('Item '+(i+1));
                var subLabel=item.module_title||item.course_title||'';
                html+='<div style="display:flex;align-items:center;gap:0.75rem;padding:0.6rem 0.9rem;border:1px solid rgba(32,0,130,0.08);border-radius:8px;background:var(--surface,#fff);">';
                html+='<span style="width:1.5rem;height:1.5rem;display:flex;align-items:center;justify-content:center;border-radius:50%;background:rgba(32,0,130,0.08);color:var(--primary);font-size:0.7rem;font-weight:700;">'+(i+1)+'</span>';
                html+='<div style="flex:1;"><div style="font-weight:600;font-size:0.88rem;color:var(--text);">'+label+'</div>';
                if(subLabel)html+='<div style="font-size:0.75rem;color:var(--muted);">'+subLabel+'</div>';
                html+='</div>';
                html+='<span style="font-size:0.72rem;padding:0.2rem 0.5rem;border-radius:999px;background:'+(item.status==='active'?'rgba(16,185,129,0.1);color:#10b981':'rgba(107,114,128,0.1);color:#6b7280')+'">'+(item.status||'active')+'</span>';
                html+='</div>';
            });
            html+='</div>';
            el.innerHTML=html;
        })
        .catch(function(){el.innerHTML='<p style="color:var(--muted);text-align:center;">Failed to load structure.</p>';});
    }

    function renderMonitorPanel(type,id){
        var el=document.getElementById('entity-content-monitor-content');
        el.innerHTML='<p style="color:var(--muted);text-align:center;">Loading performance data...</p>';
        var endpoint=null;
        if(type==='Module')endpoint='elearning-subpage/ajax/get-module-by-id.php?id='+id;
        else if(type==='Quiz')endpoint='elearning-subpage/ajax/get-quiz-by-id.php?id='+id;
        else{el.innerHTML='<p style="color:var(--muted);text-align:center;">No performance data available.</p>';return;}
        fetch(endpoint,{credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'}})
        .then(function(r){return r.json();})
        .then(function(data){
            if(!data.success||!data.data){el.innerHTML='<p style="color:var(--muted);text-align:center;">No data.</p>';return;}
            var d=data.data;
            var html='<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:0.75rem;">';
            if(d.lesson_count!==undefined)html+='<div style="text-align:center;padding:0.75rem;border:1px solid rgba(32,0,130,0.08);border-radius:8px;"><div style="font-size:1.3rem;font-weight:700;color:var(--primary);">'+d.lesson_count+'</div><div style="font-size:0.75rem;color:var(--muted);">Lessons</div></div>';
            if(d.quiz_count!==undefined)html+='<div style="text-align:center;padding:0.75rem;border:1px solid rgba(32,0,130,0.08);border-radius:8px;"><div style="font-size:1.3rem;font-weight:700;color:var(--primary);">'+d.quiz_count+'</div><div style="font-size:0.75rem;color:var(--muted);">Quizzes</div></div>';
            if(d.passing_score!==undefined)html+='<div style="text-align:center;padding:0.75rem;border:1px solid rgba(32,0,130,0.08);border-radius:8px;"><div style="font-size:1.3rem;font-weight:700;color:var(--primary);">'+d.passing_score+'%</div><div style="font-size:0.75rem;color:var(--muted);">Pass Score</div></div>';
            html+='</div>';
            el.innerHTML=html;
        })
        .catch(function(){el.innerHTML='<p style="color:var(--muted);text-align:center;">Failed to load data.</p>';});
    }

    if(modalCloseBtn)modalCloseBtn.onclick=function(){entityContent.style.display='none';state.type=null;state.id=null;};
    if(modalArchiveBtn){
        modalArchiveBtn.onclick=function(){
            if(!state.type||!state.id)return;
            var cfg=entityConfig[state.type];
            if(!cfg){if(window.showToast)window.showToast('Archive not available','error');return;}
            document.getElementById('archive-confirm-message').textContent='Are you sure you want to archive this '+state.type.toLowerCase()+'?';
            document.getElementById('archive-confirm-overlay').style.display='flex';
        };
    }
    document.getElementById('archive-confirm-ok').onclick=function(){
        document.getElementById('archive-confirm-overlay').style.display='none';
        if(!state.type||!state.id)return;
        var cfg=entityConfig[state.type];
        if(!cfg)return;
        fetch(cfg.archiveEndpoint,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},body:JSON.stringify({id:state.id})})
        .then(function(r){return r.json();})
        .then(function(d){if(d.success){if(window.showToast)window.showToast('Archived successfully','success');setTimeout(function(){location.reload();},800);}else{if(window.showToast)window.showToast(d.error||'Failed','error');}})
        .catch(function(){if(window.showToast)window.showToast('Network error','error');});
    };
    document.getElementById('archive-confirm-cancel').onclick=function(){document.getElementById('archive-confirm-overlay').style.display='none';};
    document.getElementById('archive-confirm-overlay').onclick=function(e){if(e.target.id==='archive-confirm-overlay')this.style.display='none';};
    if(modalEditBtn){
        modalEditBtn.onclick=function(){
            if(!state.type||!state.id)return;
            var cfg=entityConfig[state.type];if(!cfg)return;
            var url=cfg.editUrl+(cfg.editUrl.indexOf('?')>-1?'&':'?')+'id='+state.id;
            window.location.href=url;
        };
    }
    if(entityModal){entityContent.onclick=function(e){if(e.target===entityModal){entityContent.style.display='none';state.type=null;state.id=null;}};}
    document.querySelectorAll('.entity-content-tab').forEach(function(b){b.onclick=function(){state.activeTab=b.dataset.contentTab;syncContentTabs();
        if(state.activeTab==='structure'&&state.type&&state.id)renderStructurePanel(state.type,state.id);
        if(state.activeTab==='monitor'&&state.type&&state.id)renderMonitorPanel(state.type,state.id);
    };});

    document.body.addEventListener('click',function(e){
        var card=e.target.closest('.catalog-card');
        if(!card)return;
        e.preventDefault();
        var type=card.dataset.type;
        var id=card.dataset.entityId;
        var title=card.querySelector('h4')?card.querySelector('h4').textContent:'Untitled';
        var desc=card.querySelector('.cc-desc')?card.querySelector('.cc-desc').textContent:'No description';
        var status=card.dataset.status||'active';
        state.type=type.charAt(0).toUpperCase()+type.slice(1);state.id=parseInt(id);state.activeTab='overview';
        modalTitle.textContent=title;
        renderOverview(type,{},desc,status);
        syncContentTabs();
        entityContent.style.display='flex';
        renderStructurePanel(type,id);
    });
})();
</script>
