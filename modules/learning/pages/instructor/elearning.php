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

function renderCatalogCards($items, $type, $typeIcons) {
    if (empty($items)) return;
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
        echo '<div class="catalog-card-thumb" data-tone="' . $type . '">';
        echo '<i class="fas ' . $icon . ' thumb-icon"></i>';
        echo '<span class="catalog-card-type-badge">' . ucfirst(htmlspecialchars($type)) . '</span>';
        echo '</div>';
        echo '<div class="catalog-card-body"><h4>' . $title . '</h4>';
        echo '<p class="cc-desc">' . $shortDesc . '</p></div>';
        if ($meta) {
            echo '<div class="catalog-card-stats"><span><i class="fas fa-circle-info"></i> ' . $meta . '</span></div>';
        }
        echo '<div class="catalog-card-footer">';
        echo '<span class="cc-deadline">' . $status . '</span>';
        echo '<button class="cc-enroll-btn enroll" onclick="event.stopPropagation();window.location.href=\'' . htmlspecialchars($link) . '\';"><i class="fas fa-eye"></i> View</button>';
        echo '</div></article>';
    }
}
?>

<div class="module-content catalog-page catalog-manager">
    <div class="home-dashboard">
        <section class="home-hero">
            <div class="home-hero-main">
                <h1>E-Learning</h1>
                <p>Author the courses, modules, lessons, quizzes and evaluations that make up the curriculum your learners work through.</p>
            </div>
            <div class="home-hero-aside">
                <div class="home-hero-chips">
                    <span class="home-hero-chip"><i class="fas fa-graduation-cap" aria-hidden="true"></i> <?= count($courseRows) ?> course<?= count($courseRows) === 1 ? '' : 's' ?></span>
                    <span class="home-hero-chip"><i class="fas fa-cube" aria-hidden="true"></i> <?= count($moduleRows) ?> module<?= count($moduleRows) === 1 ? '' : 's' ?></span>
                    <span class="home-hero-chip"><i class="fas fa-book-open" aria-hidden="true"></i> <?= count($lessonRows) ?> lesson<?= count($lessonRows) === 1 ? '' : 's' ?></span>
                    <?php $assessmentCount = count($quizRows) + count($evaluationRows); ?>
                    <span class="home-hero-chip"><i class="fas fa-clipboard-check" aria-hidden="true"></i> <?= $assessmentCount ?> assessment<?= $assessmentCount === 1 ? '' : 's' ?></span>
                </div>
            </div>
        </section>

        <!-- Toolbar -->
        <div class="catalog-toolbar">
            <div class="catalog-search">
                <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                <input type="search" id="catalog-search-input" placeholder="Search courses, modules, lessons..." aria-label="Search e-learning" />
            </div>
            <div class="catalog-view-toggle" role="group" aria-label="Result layout">
                <button type="button" class="active" data-view="grid" title="Grid view" aria-label="Grid view"><i class="fas fa-th-large" aria-hidden="true"></i></button>
                <button type="button" data-view="list" title="List view" aria-label="List view"><i class="fas fa-list" aria-hidden="true"></i></button>
            </div>
            <span class="catalog-count" id="catalog-count"></span>
            <select id="catalog-page-size" class="cat-page-size" data-display-pref="page_size" aria-label="Items per page" title="Items per page">
                <option value="12">12</option>
                <option value="24">24</option>
                <option value="36">36</option>
            </select>
            <div class="catalog-add-wrap" id="catalog-add-wrap">
                <button type="button" id="catalog-add-btn" class="cat-add-btn">
                    <i class="fas fa-plus" aria-hidden="true"></i>
                    <span class="catalog-add-tip" id="catalog-add-tip">Add Course</span>
                </button>
            </div>
        </div>

        <!-- Tabs -->
        <div class="catalog-tabs" id="catalog-tabs">
            <button type="button" class="catalog-tab-btn active" data-tab="tab-course">
                <i class="fas fa-graduation-cap" aria-hidden="true"></i> Courses
            </button>
            <button type="button" class="catalog-tab-btn" data-tab="tab-module">
                <i class="fas fa-cube" aria-hidden="true"></i> Modules
            </button>
            <button type="button" class="catalog-tab-btn" data-tab="tab-lesson">
                <i class="fas fa-book-open" aria-hidden="true"></i> Lessons
            </button>
            <button type="button" class="catalog-tab-btn" data-tab="tab-quiz">
                <i class="fas fa-circle-question" aria-hidden="true"></i> Quizzes
            </button>
            <button type="button" class="catalog-tab-btn" data-tab="tab-evaluation">
                <i class="fas fa-clipboard-check" aria-hidden="true"></i> Evaluations
            </button>
            <button type="button" class="catalog-tab-btn" data-tab="tab-template">
                <i class="fas fa-layer-group" aria-hidden="true"></i> Templates
            </button>
        </div>

        <!-- Tab Content -->
        <div class="tab-content active" data-tab="tab-course">
            <div class="catalog-grid" id="catalog-grid">
                <?php $typeIcons = ['course' => 'fa-graduation-cap', 'module' => 'fa-cube', 'lesson' => 'fa-book-open', 'quiz' => 'fa-circle-question', 'evaluation' => 'fa-clipboard-check']; renderCatalogCards($courseRows, 'course', $typeIcons); ?>
            </div>
        </div>

        <div class="tab-content" data-tab="tab-module">
            <div class="catalog-grid">
                <?php renderCatalogCards($moduleRows, 'module', $typeIcons); ?>
            </div>
        </div>

        <div class="tab-content" data-tab="tab-lesson">
            <div class="catalog-grid">
                <?php renderCatalogCards($lessonRows, 'lesson', $typeIcons); ?>
            </div>
        </div>

        <div class="tab-content" data-tab="tab-quiz">
            <div class="catalog-grid">
                <?php renderCatalogCards($quizRows, 'quiz', $typeIcons); ?>
            </div>
        </div>

        <div class="tab-content" data-tab="tab-evaluation">
            <div class="catalog-grid">
                <?php renderCatalogCards($evaluationRows, 'evaluation', $typeIcons); ?>
            </div>
        </div>

        <div class="tab-content" data-tab="tab-template">
            <div class="cat-section-intro">
                <h3>Course templates</h3>
                <p>Reuse a proven course outline. Save one from the Structure Builder, then clone it into a new course.</p>
            </div>
            <div class="catalog-grid" id="templates-grid">
                <div class="catalog-empty"><i class="fas fa-spinner fa-spin" aria-hidden="true"></i><p>Loading templates...</p></div>
            </div>
        </div>

        <!-- Empty State -->
        <div class="catalog-empty" id="catalog-empty" style="display:none;">
            <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
            <h4>No items found</h4>
            <p>Try selecting a different tab or adjusting your search.</p>
        </div>

        <!-- Pagination -->
        <div class="pagination-row" id="catalog-pagination">
            <button type="button" class="page-btn" data-action="prev" disabled>Prev</button>
            <span class="page-indicator" id="page-indicator">Page 1 of 1</span>
            <button type="button" class="page-btn" data-action="next">Next</button>
        </div>
    </div>

    <!-- Clone Template Modal -->
    <div id="clone-template-modal" class="modal-overlay ld-modal-scrim" role="dialog" aria-modal="true" aria-labelledby="clone-template-title" style="display:none;">
        <div class="ld-modal-panel cat-modal-panel">
            <div class="ld-modal-header">
                <h2 id="clone-template-title"><i class="fas fa-copy" aria-hidden="true"></i> Clone Template</h2>
                <button type="button" id="clone-modal-close" class="cat-modal-close" aria-label="Close">✕</button>
            </div>
            <div class="ld-modal-body">
                <label class="cat-field">
                    <span class="cat-field-label">New course title</span>
                    <input type="text" id="clone-title-input" placeholder="Enter a title for the new course" />
                </label>
                <div class="ld-modal-actions">
                    <button type="button" id="clone-modal-confirm" class="cat-btn-primary">Clone</button>
                    <button type="button" id="clone-modal-cancel" class="cat-btn-quiet">Cancel</button>
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
            if(templates.length===0){grid.innerHTML='<div class="catalog-empty"><i class="fas fa-layer-group" aria-hidden="true"></i><h4>No templates yet</h4><p>Open the Structure Builder and save a course as a template to reuse it here.</p></div>';return;}
            grid.innerHTML=templates.map(function(t){
                return '<article class="catalog-card" data-status="template"><div class="catalog-card-thumb" data-tone="template"><i class="fas fa-layer-group thumb-icon"></i><span class="catalog-card-type-badge">Template</span></div><div class="catalog-card-body"><h4>'+t.title+'</h4>'+(t.description?'<p class="cc-desc">'+t.description.substring(0,100)+'</p>':'')+'</div><div class="catalog-card-stats"><span><i class="fas fa-cube"></i> '+t.module_count+' modules</span><span><i class="fas fa-book-open"></i> '+t.lesson_count+' lessons</span></div><div class="catalog-card-footer"><span class="cc-deadline is-meta">Created '+new Date(t.created_at).toLocaleDateString()+'</span><button class="cc-enroll-btn enroll" onclick="event.stopPropagation();cloneTemplate('+t.id+');"><i class="fas fa-copy"></i> Clone</button></div></article>';
            }).join('');
        }).catch(function(){var g=document.getElementById('templates-grid');if(g)g.innerHTML='<div class="catalog-empty is-error"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i><h4>Could not load templates</h4><p>Check your connection and try again.</p></div>';});
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
            'tab-template':{label:'Add Template',url:'?page=instructor/elearning-subpage/course-structure'}
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
        if(pageSizeSelect){var ldps=parseInt(window.LD_DEFAULT_PAGE_SIZE,10)||12;pageSizeSelect.value=String(ldps);PAGE_SIZE=ldps;pageSizeSelect.addEventListener('change',function(){PAGE_SIZE=parseInt(this.value,10)||12;currentPage=1;updateGrid();});}
        updateGrid();
        syncAddBtn();
    })();
    </script>
    <!-- Entity Detail Content -->
    <div id="entity-content" class="modal-overlay ld-modal-scrim" role="dialog" aria-modal="true" aria-labelledby="modal-title" style="display:none;">
        <div id="entity-content-panel" class="entity-content-box ld-modal-panel" data-size="standard">
            <div class="entity-content-header ld-modal-header">
                <h2 id="modal-title"></h2>
                <div class="entity-content-actions">
                    <button id="modal-edit-btn" class="ec-btn is-primary">Edit</button>
                    <button id="modal-archive-btn" class="ec-btn is-quiet">Archive</button>
                    <button id="modal-close-btn" class="ec-btn is-quiet">Close</button>
                </div>
            </div>
            <div class="entity-content-tabs">
                <button type="button" class="entity-content-tab active" data-content-tab="overview"><i class="fas fa-eye" aria-hidden="true"></i>Overview</button>
                <button type="button" class="entity-content-tab" data-content-tab="structure"><i class="fas fa-sitemap" aria-hidden="true"></i>Structure</button>
                <button type="button" class="entity-content-tab" data-content-tab="monitor"><i class="fas fa-chart-line" aria-hidden="true"></i>Monitor</button>
            </div>
            <div class="entity-content-body ld-modal-body">
                <div id="entity-content-overview" class="entity-content-panel" style="display:block;">
                    <div id="modal-content-grid" class="ec-fields">
                        <div class="ec-field"><span class="ec-field-label">Status</span><p id="modal-overview-status" class="ec-field-value">Active</p></div>
                        <div class="ec-field"><span class="ec-field-label">Parent</span><p id="modal-overview-parent" class="ec-field-value">-</p></div>
                        <div class="ec-field"><span class="ec-field-label">Children</span><p id="modal-overview-children" class="ec-field-value">0</p></div>
                    </div>
                    <div class="ec-section">
                        <span class="ec-section-title">Description</span>
                        <p id="modal-description" class="ec-section-text">-</p>
                    </div>
                </div>
                <div id="entity-content-structure" class="entity-content-panel" style="display:none;"><div id="entity-content-structure-content"></div></div>
                <div id="entity-content-monitor" class="entity-content-panel" style="display:none;"><div id="entity-content-monitor-content"></div></div>
            </div>
        </div>
    </div>

    <!-- Archive Confirmation Modal -->
    <div id="archive-confirm-overlay" class="modal-overlay ld-modal-scrim" role="dialog" aria-modal="true" aria-labelledby="archive-confirm-title" style="display:none;">
        <div class="ld-modal-panel ld-modal-confirm">
            <h3 id="archive-confirm-title" class="cat-modal-title"><i class="fas fa-box-archive" aria-hidden="true"></i> Archive Item</h3>
            <p id="archive-confirm-message" class="cat-modal-text">Are you sure you want to archive this item?</p>
            <div class="ld-modal-actions">
                <button type="button" id="archive-confirm-cancel" class="cat-btn-quiet">Cancel</button>
                <button type="button" id="archive-confirm-ok" class="cat-btn-danger">Archive</button>
            </div>
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
        entityContent.querySelectorAll('.entity-content-tab').forEach(function(b){
            b.classList.toggle('active',b.dataset.contentTab===state.activeTab);
        });
        entityContent.querySelectorAll('.entity-content-panel').forEach(function(p){p.style.display='none';});
        var active=entityContent.querySelector('#entity-content-'+state.activeTab);
        if(active)active.style.display='block';
    }

    function renderOverview(type,data,desc,status){
        document.getElementById('modal-overview-status').textContent=data.status||status||'Active';
        document.getElementById('modal-description').textContent=data.description||desc||'No description';
        var config=entityConfig[type];
        if(config)document.getElementById('modal-overview-parent').textContent=type;
    }

    function escCat(value){
        return String(value==null?'':value).replace(/[&<>"']/g,function(ch){
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch];
        });
    }

    function renderStructurePanel(type,id){
        var el=document.getElementById('entity-content-structure-content');
        if(!el)return;
        el.innerHTML='<div class="ec-empty"><i class="fas fa-spinner fa-spin" aria-hidden="true"></i>Loading structure...</div>';
        var url=null;
        if(type==='Course')url='pages/instructor/elearning-subpage/ajax/get-lessons-by-course.php?course_id='+id;
        else if(type==='Module')url='pages/instructor/elearning-subpage/ajax/get-lessons-by-module.php?module_id='+id;
        else if(type==='Lesson')url='pages/instructor/elearning-subpage/ajax/get-quizzes-by-lesson.php?lesson_id='+id;
        else{el.innerHTML='<div class="ec-empty is-inline"><i class="fas fa-diagram-project" aria-hidden="true"></i>No structure available for this item.</div>';return;}
        fetch(url,{credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'}})
        .then(function(r){return r.json();})
        .then(function(data){
            var items=data.success&&Array.isArray(data.items)?data.items:[];
            if(items.length===0){el.innerHTML='<div class="ec-empty is-inline"><i class="fas fa-diagram-project" aria-hidden="true"></i>No linked items found.</div>';return;}
            var html='<div class="ec-list">';
            items.forEach(function(item,i){
                var label=escCat(item.title||('Item '+(i+1)));
                var subLabel=escCat(item.module_title||item.course_title||'');
                var status=String(item.status||'active');
                var isActive=status.toLowerCase()==='active';
                html+='<div class="ec-row">';
                html+='<span class="ec-row-num">'+(i+1)+'</span>';
                html+='<div class="ec-row-body"><div class="ec-row-title">'+label+'</div>';
                if(subLabel)html+='<div class="ec-row-meta">'+subLabel+'</div>';
                html+='</div>';
                html+='<span class="ec-pill '+(isActive?'is-success':'is-muted')+'">'+escCat(status)+'</span>';
                html+='</div>';
            });
            html+='</div>';
            el.innerHTML=html;
        })
        .catch(function(){el.innerHTML='<div class="ec-empty is-inline is-error"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i>Failed to load structure.</div>';});
    }

    function renderMonitorPanel(type,id){
        var el=document.getElementById('entity-content-monitor-content');
        el.innerHTML='<div class="ec-empty"><i class="fas fa-spinner fa-spin" aria-hidden="true"></i>Loading performance data...</div>';
        var endpoint=null;
        if(type==='Module')endpoint='elearning-subpage/ajax/get-module-by-id.php?id='+id;
        else if(type==='Quiz')endpoint='elearning-subpage/ajax/get-quiz-by-id.php?id='+id;
        else{el.innerHTML='<div class="ec-empty is-inline"><i class="fas fa-chart-line" aria-hidden="true"></i>No performance data available.</div>';return;}
        fetch(endpoint,{credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'}})
        .then(function(r){return r.json();})
        .then(function(data){
            if(!data.success||!data.data){el.innerHTML='<div class="ec-empty is-inline">No data.</div>';return;}
            var d=data.data;
            var html='<div class="ec-cards">';
            if(d.lesson_count!==undefined)html+='<div class="ec-card is-center is-success"><div class="ec-card-label">Lessons</div><div class="ec-card-value">'+escCat(d.lesson_count)+'</div></div>';
            if(d.quiz_count!==undefined)html+='<div class="ec-card is-center is-info"><div class="ec-card-label">Quizzes</div><div class="ec-card-value">'+escCat(d.quiz_count)+'</div></div>';
            if(d.passing_score!==undefined)html+='<div class="ec-card is-center"><div class="ec-card-label">Pass Score</div><div class="ec-card-value">'+escCat(d.passing_score)+'%</div></div>';
            html+='</div>';
            el.innerHTML=html;
        })
        .catch(function(){el.innerHTML='<div class="ec-empty is-inline is-error"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i>Failed to load data.</div>';});
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
    entityContent.querySelectorAll('.entity-content-tab').forEach(function(b){b.onclick=function(){state.activeTab=b.dataset.contentTab;syncContentTabs();
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
