<?php
include_once __DIR__ . '/../../classes/Employee.php';
$employeeClass = new Employee();

$learningPathRows = [];
$programRows = [];
$videoConferenceRows = [];
$skillRows = [];

try {
    require_once dirname(__DIR__, 4) . '/database/db.php';
    $database = new Database();
    $pdo = $database->getConnection();

    // Guard each query separately so one failing table can't blank the other tabs.
    $runQuery = function (PDO $pdo, string $sql, array $params = []): array {
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('training.php query failed: ' . $e->getMessage());
            return [];
        }
    };

    $learningPathRows = $runQuery($pdo, "SELECT id, title, description, status, created_at FROM ld_learning_path ORDER BY created_at DESC LIMIT 20");
    $programRows = $runQuery($pdo, "SELECT id, title, description, status, created_at FROM ld_program ORDER BY created_at DESC LIMIT 20");
    $videoConferenceRows = $runQuery($pdo, "SELECT id, title, platform, status, scheduled_at, duration_minutes, created_at FROM ld_video_conference ORDER BY created_at DESC LIMIT 20");

    $skillRows = $runQuery($pdo, "SELECT id, name, description, status FROM ld_skill WHERE status = 'active' ORDER BY name ASC");
    foreach ($skillRows as &$skill) {
        $skill['courses'] = $runQuery($pdo, "SELECT c.id, c.title FROM ld_course_skill cs JOIN ld_course c ON c.id = cs.course_id WHERE cs.skill_id = :sid", [':sid' => (int)$skill['id']]);
        $skill['modules'] = $runQuery($pdo, "SELECT m.id, m.title, c.title AS course_title FROM ld_module_skill ms JOIN ld_module m ON m.id = ms.module_id JOIN ld_course c ON c.id = m.course_id WHERE ms.skill_id = :sid", [':sid' => (int)$skill['id']]);
    }
    unset($skill);
} catch (Throwable $e) {
    error_log('training.php bootstrap failed: ' . $e->getMessage());
}

function renderTrainingCards($items, $label, $pageUrl, $typeIcons = []) {
    if (empty($items)) return;
    $typeLabels = ['Learning Path' => 'Learning Path', 'Program' => 'Program', 'Video Conference' => 'Live Session'];
    $typeLinks = [
        'Learning Path' => '?page=instructor/learning-path&id=',
        'Program' => '?page=instructor/training-subpage/program&id=',
        'Video Conference' => '?page=instructor/training-subpage/video-conference&id=',
    ];
    $gradDefault = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
    $gradZoom = 'linear-gradient(135deg, #2D8CFF, #0066FF)';
    $gradMeet = 'linear-gradient(135deg, #00897B, #004D40)';
    $gradOtherVC = 'linear-gradient(135deg, #6366f1, #4f46e5)';
    foreach ($items as $item) {
        $id = (int) ($item['id'] ?? 0);
        $title = trim((string) ($item['title'] ?? 'Untitled'));
        $description = trim((string) ($item['description'] ?? ($item['platform'] ?? '')));
        $status = trim((string) ($item['status'] ?? 'active'));
        $gradient = $gradDefault;
        if ($label === 'Video Conference') {
            $vp = $item['platform'] ?? 'other';
            $gradient = $vp === 'zoom' ? $gradZoom : ($vp === 'google_meet' ? $gradMeet : $gradOtherVC);
        }
        $typeLabel = $typeLabels[$label] ?? $label;
        $icon = $typeIcons[$label] ?? 'fa-layer-group';
        $link = ($typeLinks[$label] ?? '#') . $id;
        $shortDesc = $description !== '' ? mb_substr(strip_tags($description), 0, 100) : 'No description available.';
        $footerInfo = '';
        if ($label === 'Video Conference') {
            if (!empty($item['scheduled_at'])) $footerInfo = date('M j, g:i A', strtotime($item['scheduled_at']));
            if (!empty($item['duration_minutes'])) $footerInfo .= ($footerInfo ? ' . ' : '') . $item['duration_minutes'] . ' min';
        }
        $createdDate = !empty($item['created_at']) ? date('M j, Y', strtotime($item['created_at'])) : '';
        echo '<article class="catalog-card" data-status="' . htmlspecialchars($status) . '" data-type="' . htmlspecialchars($label) . '" data-entity-id="' . $id . '">';
        echo '<div class="catalog-card-thumb" style="background:' . $gradient . ';">';
        echo '<i class="fas ' . $icon . ' thumb-icon"></i>';
        echo '<div class="thumb-overlay"></div>';
        echo '<span class="catalog-card-type-badge">' . htmlspecialchars($typeLabel) . '</span>';
        echo '</div>';
        echo '<div class="catalog-card-body">';
        echo '<h4>' . htmlspecialchars($title) . '</h4>';
        echo '<p class="cc-desc">' . htmlspecialchars($shortDesc) . '</p>';
        echo '</div>';
        if ($footerInfo) {
            echo '<div class="catalog-card-stats">';
            echo '<span><i class="fas fa-info-circle"></i> ' . htmlspecialchars($footerInfo) . '</span>';
            echo '</div>';
        }
        echo '<div class="catalog-card-footer">';
        echo '<span class="cc-deadline" style="font-size:0.72rem;color:var(--muted);text-transform:capitalize;">' . htmlspecialchars($status) . '</span>';
        echo '<button class="cc-enroll-btn enroll" style="background:rgba(32,0,130,0.08);color:var(--primary);" onclick="event.stopPropagation();window.location.href=\'' . htmlspecialchars($link) . '\';"><i class="fas fa-eye"></i> View</button>';
        echo '</div>';
        echo '</article>';
    }
}
?>

<div id="tr-entity-content" style="display:none;">
    <div id="tr-entity-content-panel" class="entity-content-box" data-size="standard">
        <div class="entity-content-header">
            <h2 id="tr-modal-title"></h2>
            <div class="entity-content-actions">
                <button id="tr-modal-edit-btn" style="padding:0.6rem 0.95rem; background:var(--primary); color:var(--surface); border:none; border-radius:999px; cursor:pointer; font-weight:700; box-shadow:0 8px 22px rgba(32, 0, 130, 0.12);">Edit</button>
                <button id="tr-modal-close-btn" style="padding:0.6rem 0.95rem; background:transparent; color:var(--text); border:1px solid var(--border); border-radius:999px; cursor:pointer; font-weight:700;">Close</button>
            </div>
        </div>
        <div class="entity-content-tabs">
                <button type="button" class="entity-content-tab active" data-content-tab="overview" data-label="Overview" data-icon="<i class='fas fa-eye'></i>" style="padding:0.7rem 1rem; border:none; background:rgba(32,0,130,0.08); color:var(--primary); border-radius:999px; font-weight:700; cursor:pointer;"><i class="fas fa-eye" style="margin-right:0.45rem;"></i>Overview</button>
                <button type="button" class="entity-content-tab" data-content-tab="structure" data-label="Structure" data-icon="<i class='fas fa-sitemap'></i>" style="padding:0.7rem 1rem; border:1px solid rgba(32,0,130,0.12); background:#fff; color:var(--text); border-radius:999px; font-weight:700; cursor:pointer;"><i class="fas fa-sitemap" style="margin-right:0.45rem;"></i>Structure</button>
                <button type="button" class="entity-content-tab" data-content-tab="monitor" data-label="Monitor" data-icon="<i class='fas fa-chart-line'></i>" style="padding:0.7rem 1rem; border:1px solid rgba(32,0,130,0.12); background:#fff; color:var(--text); border-radius:999px; font-weight:700; cursor:pointer;"><i class="fas fa-chart-line" style="margin-right:0.45rem;"></i>Monitor</button>
            </div>
        </div>
        <div class="entity-content-body">
            <div id="tr-entity-content-overview" class="entity-content-panel" style="display:none;">
                <div id="tr-modal-content-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
                    <div>
                        <label style="color:var(--primary); font-weight:700; font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase;">Status</label>
                        <p id="tr-modal-overview-status" style="margin:0.55rem 0 0 0; font-size:1rem; color:var(--text);">Active</p>
                    </div>
                    <div>
                        <label style="color:var(--primary); font-weight:700; font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase;">Parent</label>
                        <p id="tr-modal-overview-parent" style="margin:0.55rem 0 0 0; font-size:1rem; color:var(--text);">Program</p>
                    </div>
                    <div>
                        <label style="color:var(--primary); font-weight:700; font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase;">Children</label>
                        <p id="tr-modal-overview-children" style="margin:0.55rem 0 0 0; font-size:1rem; color:var(--text);">0 child items</p>
                    </div>
                    <div>
                        <label style="color:var(--primary); font-weight:700; font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase;">Enrollment</label>
                        <p id="tr-modal-overview-enrollment" style="margin:0.55rem 0 0 0; font-size:1rem; color:var(--text);">Group</p>
                    </div>
                </div>
                <div id="tr-modal-description-section">
                    <label style="color:var(--primary); font-weight:700; font-size:0.74rem; letter-spacing:0.08em; text-transform:uppercase;">Description</label>
                    <p id="tr-modal-description" style="margin:0.75rem 0 0 0; font-size:1rem; line-height:1.7; color:var(--text);">No description</p>
                </div>
            </div>
            <div id="tr-entity-content-structure" class="entity-content-panel" style="display:block;">
                <div id="tr-entity-content-structure-content"></div>
            </div>
            <div id="tr-entity-content-monitor" class="entity-content-panel" style="display:none;">
                <div id="tr-entity-content-monitor-content"></div>
            </div>
        </div>
    </div>
</div>

    <style>
.catalog-tabs{display:flex;gap:6px;margin-bottom:1rem;overflow-x:auto;scrollbar-width:none;background:#e8e4f0;border-radius:12px;padding:4px;}
.catalog-tabs::-webkit-scrollbar{display:none}
.catalog-tab-btn{flex:1 1 0;min-width:0;padding:0.55rem 0.5rem;border:none;border-radius:8px;background:rgba(255,255,255,0.7);color:var(--text,#333);font-size:0.78rem;font-weight:600;cursor:pointer;white-space:nowrap;transition:all 0.2s;display:flex;align-items:center;justify-content:center;gap:0.35rem;}
.catalog-tab-btn:hover{background:rgba(255,255,255,0.95)}
.catalog-tab-btn.active{background:var(--primary,#320082);color:#fff;box-shadow:0 2px 8px rgba(32,0,130,0.3)}
.catalog-tab-count{display:inline-flex;align-items:center;justify-content:center;min-width:22px;height:20px;padding:0 6px;border-radius:999px;background:rgba(32,0,130,0.1);font-size:0.7rem;font-weight:700;color:var(--text,#333)}
.catalog-tab-btn.active .catalog-tab-count{background:rgba(255,255,255,0.25);color:#fff}
.catalog-toolbar{position:sticky;top:calc(var(--header-height,60px) + 0.75rem);z-index:100;display:flex;align-items:center;gap:0.75rem;padding:0.75rem 1rem;background:linear-gradient(135deg, rgba(32,0,130,0.08), rgba(81,70,183,0.06));border-radius:12px;border:1.5px solid rgba(32,0,130,0.15);margin-bottom:1rem;flex-wrap:wrap;box-shadow:0 4px 16px rgba(32,0,130,0.08);backdrop-filter:blur(10px);}
.catalog-toolbar .catalog-search input,.catalog-toolbar select,.catalog-toolbar .catalog-view-toggle,.catalog-toolbar .catalog-request-btn{background:var(--surface,#fff)!important;border-color:rgba(32,0,130,0.15)!important;}
.catalog-search{flex:1;min-width:200px;position:relative;}
.catalog-search input{width:100%;padding:0.6rem 1rem 0.6rem 2.5rem;border:1.5px solid rgba(32,0,130,0.1);border-radius:10px;background:rgba(32,0,130,0.03);font-size:0.88rem;outline:none;transition:border-color 0.2s,box-shadow 0.2s;box-sizing:border-box;}
.catalog-search input:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(32,0,130,0.08);}
.catalog-search i{position:absolute;left:0.85rem;top:50%;transform:translateY(-50%);color:var(--muted);font-size:0.85rem;}
.catalog-view-toggle{display:flex;border:1.5px solid rgba(32,0,130,0.1);border-radius:8px;overflow:hidden;}
.catalog-view-toggle button{padding:0.45rem 0.7rem;border:none;background:transparent;color:var(--muted);cursor:pointer;font-size:0.85rem;transition:all 0.15s;}
.catalog-view-toggle button.active{background:var(--primary);color:#fff;}
.catalog-request-btn{padding:0.5rem 1rem;background:var(--primary);color:#fff;border:none;border-radius:999px;font-weight:700;font-size:0.82rem;cursor:pointer;white-space:nowrap;transition:opacity 0.2s;}
.catalog-request-btn:hover{opacity:0.85}
.catalog-add-wrap{position:relative;display:inline-flex;flex-direction:column;align-items:center;}
.catalog-add-btn{width:2.4rem;height:2.4rem;border:none;border-radius:50%;background:var(--primary);color:#fff;font-size:1.05rem;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(32,0,130,0.25);transition:transform 0.15s, opacity 0.15s;}
.catalog-add-btn:hover{opacity:0.88;transform:scale(1.06);}
.catalog-add-tip{position:absolute;top:calc(100% + 0.5rem);left:50%;transform:translateX(-50%) translateY(-4px);background:var(--primary);color:#fff;padding:0.35rem 0.8rem;border-radius:999px;font-size:0.75rem;font-weight:700;white-space:nowrap;opacity:0;pointer-events:none;transition:opacity 0.18s, transform 0.18s;z-index:50;box-shadow:0 4px 12px rgba(32,0,130,0.3);}
.catalog-add-tip::before{content:'';position:absolute;top:-4px;left:50%;transform:translateX(-50%) rotate(45deg);width:8px;height:8px;background:var(--primary);border-radius:2px;}
.catalog-add-wrap:hover .catalog-add-tip,.catalog-add-wrap:focus-within .catalog-add-tip{opacity:1;transform:translateX(-50%) translateY(0);}
.catalog-count{font-size:0.8rem;color:var(--muted);white-space:nowrap;}
.catalog-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1.25rem;margin-bottom:1.5rem;}
.catalog-card{background:var(--surface,#fff);border-radius:14px;overflow:hidden;border:1px solid rgba(32,0,130,0.08);cursor:pointer;transition:transform 0.25s,box-shadow 0.25s,border-color 0.25s;position:relative;}
.catalog-card:hover{transform:translateY(-4px);box-shadow:0 12px 35px rgba(32,0,130,0.12);border-color:rgba(32,0,130,0.2);}
.catalog-card-thumb{height:140px;display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden;}
.catalog-card-thumb .thumb-icon{font-size:2.5rem;color:rgba(255,255,255,0.9);z-index:1;}
.catalog-card-thumb .thumb-overlay{position:absolute;inset:0;background:linear-gradient(180deg,transparent 40%,rgba(0,0,0,0.3) 100%);}
.catalog-card-type-badge{position:absolute;top:0.75rem;left:0.75rem;padding:0.2rem 0.6rem;border-radius:6px;background:rgba(255,255,255,0.9);color:var(--primary);font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;z-index:2;backdrop-filter:blur(4px);}
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
@media(max-width:1200px){.catalog-grid{grid-template-columns:repeat(3,1fr)}}
@media(max-width:900px){.catalog-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:768px){.catalog-grid{grid-template-columns:repeat(2,1fr);gap:1rem}.catalog-toolbar{flex-wrap:nowrap}.catalog-search{min-width:0;flex:1}}
@media(max-width:480px){.catalog-grid{grid-template-columns:1fr}}
</style>

    <div class="catalog-toolbar" id="browse-training">
        <div class="catalog-search">
            <i class="fas fa-search"></i>
            <input type="search" id="tr-catalog-search-input" placeholder="Search programs, learning paths, sessions..." aria-label="Search training" />
        </div>
        <div class="catalog-view-toggle">
            <button type="button" class="active" data-view="grid" title="Grid view"><i class="fas fa-th"></i></button>
            <button type="button" data-view="list" title="List view"><i class="fas fa-list"></i></button>
        </div>
        <span class="catalog-count" id="tr-catalog-count"></span>
        <select id="tr-catalog-page-size" style="border:1.5px solid rgba(32,0,130,0.2); background:var(--surface,#fff); color:var(--text,#333); border-radius:8px; padding:0.45rem 0.6rem; font-size:0.78rem; font-weight:600; cursor:pointer; outline:none;">
            <option value="12">12</option>
            <option value="24">24</option>
            <option value="36">36</option>
        </select>
        <div class="catalog-add-wrap" id="tr-catalog-add-wrap">
            <button type="button" id="tr-catalog-add-btn" class="catalog-add-btn" title="Add" aria-label="Add"><i class="fas fa-plus"></i></button>
            <span class="catalog-add-tip" id="tr-catalog-add-tip">Add Learning Path</span>
        </div>
    </div>

    <div class="catalog-tabs" id="tr-catalog-tabs">
        <button type="button" class="catalog-tab-btn active" data-tab="tab-learning-path">
            <i class="fas fa-route"></i> Learning Path
            <span class="catalog-tab-count"><?= count($learningPathRows) ?></span>
        </button>
        <button type="button" class="catalog-tab-btn" data-tab="tab-program">
            <i class="fas fa-briefcase"></i> Program
            <span class="catalog-tab-count"><?= count($programRows) ?></span>
        </button>
        <button type="button" class="catalog-tab-btn" data-tab="tab-video-conference">
            <i class="fas fa-video"></i> Live Sessions
            <span class="catalog-tab-count"><?= count($videoConferenceRows) ?></span>
        </button>
        <button type="button" class="catalog-tab-btn" data-tab="tab-skills">
            <i class="fas fa-star"></i> Skills
            <span class="catalog-tab-count"><?= count($skillRows) ?></span>
        </button>
    </div>
<div class="tab-content active" data-tab="tab-learning-path">
            <div class="mode-card">
                <h2>Learning Path Management</h2>
                <p>Compose ordered learning paths from courses, quizzes, evaluations, and programs.</p>
                <div class="catalog-grid">
                <?php renderTrainingCards($learningPathRows, 'Learning Path', '?page=instructor/learning-path'); ?>
                </div>
            </div>
        </div>

        <div class="tab-content" data-tab="tab-program">
            <div class="mode-card">
                <h2>Program Management</h2>
                <p>Manage training programs, schedule sessions, and assign program-level learning objectives.</p>
                <div class="catalog-grid">
                <?php renderTrainingCards($programRows, 'Program', '?page=instructor/training-subpage/program'); ?>
                </div>
            </div>
        </div>

        <div class="tab-content" data-tab="tab-video-conference">
            <div class="mode-card">
                <h2>Video Conference Management</h2>
                <p>Set up live sessions, host virtual training, and manage attendance links.</p>
                <div class="catalog-grid">
                <?php renderTrainingCards($videoConferenceRows, 'Video Conference', '?page=instructor/training-subpage/video-conference'); ?>
                </div>
            </div>
        </div>

        <div class="tab-content" data-tab="tab-skills">
            <div class="mode-card">
                <h2>Skills</h2>
                <p>Explore skills linked to e-learning courses and training modules.</p>
                <div id="tr-skills-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(260px, 1fr)); gap:1rem; margin-top:1rem;">
                    <?php
                    $skillIcons = ['fas fa-tasks','fas fa-users','fas fa-comments','fas fa-pen','fas fa-chart-bar','fas fa-shield-alt','fas fa-code','fas fa-headset','fas fa-clock','fas fa-lightbulb','fas fa-dollar-sign','fas fa-handshake','fas fa-sync-alt','fas fa-check-double','fas fa-user-tie','fas fa-chess','fas fa-boxes','fas fa-gavel'];
                    $skillColors = ['#320082','#5146b7','#7c3aed','#2563eb','#0891b2','#059669','#d97706','#dc2626','#4f46e5','#0d9488','#7c2d12','#1d4ed8','#9333ea','#16a34a','#ca8a04','#e11d48','#0284c7','#7c3aed'];
                    foreach ($skillRows as $idx => $skill):
                        $icon = $skillIcons[$idx % count($skillIcons)];
                        $color = $skillColors[$idx % count($skillColors)];
                        $courseCount = count($skill['courses']);
                        $moduleCount = count($skill['modules']);
                        $totalItems = $courseCount + $moduleCount;
                    ?>
                        <div class="skill-card-item" onclick="openSkillModal(<?= (int)$skill['id'] ?>)" style="cursor:pointer; background:var(--surface, #fff); border:1px solid rgba(32,0,130,0.1); border-radius:14px; padding:1.25rem; transition:transform 0.15s, box-shadow 0.15s;" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 25px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
                            <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:0.85rem;">
                                <div style="width:44px; height:44px; border-radius:12px; background:<?= $color ?>; color:#fff; display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0;"><i class="fas <?= $icon ?>"></i></div>
                                <div style="flex:1; min-width:0;">
                                    <h3 style="margin:0; font-size:0.95rem; font-weight:700; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars($skill['name']) ?></h3>
                                    <span style="font-size:0.72rem; color:rgba(32,0,130,0.45);"><?= $totalItems ?> linked item<?= $totalItems !== 1 ? 's' : '' ?></span>
                                </div>
                            </div>
                            <p style="margin:0; font-size:0.8rem; color:rgba(32,0,130,0.55); line-height:1.5; display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden;"><?= htmlspecialchars(mb_substr($skill['description'], 0, 120)) ?>...</p>
                            <div style="display:flex; gap:0.4rem; margin-top:0.75rem; flex-wrap:wrap;">
                                <?php if ($courseCount > 0): ?><span style="font-size:0.7rem; padding:0.2rem 0.5rem; background:rgba(32,0,130,0.08); border-radius:999px; color:var(--primary); font-weight:600;"><?= $courseCount ?> Course<?= $courseCount !== 1 ? 's' : '' ?></span><?php endif; ?>
                                <?php if ($moduleCount > 0): ?><span style="font-size:0.7rem; padding:0.2rem 0.5rem; background:rgba(81,70,183,0.1); border-radius:999px; color:var(--primary); font-weight:600;"><?= $moduleCount ?> Module<?= $moduleCount !== 1 ? 's' : '' ?></span><?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="pagination-row" id="skills-pagination">
                    <button type="button" class="page-btn" data-action="prev" disabled>Prev</button>
                    <span class="page-indicator">Page 1 of 1</span>
                    <button type="button" class="page-btn" data-action="next">Next</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Catalog Empty State -->
    <div class="catalog-empty" id="tr-catalog-empty" style="display:none;">
        <i class="fas fa-search"></i>
        <h4>No items found</h4>
        <p>Try selecting a different tab or adjusting your search.</p>
    </div>

    <!-- Catalog Pagination -->
    <div class="pagination-row" id="tr-catalog-pagination">
        <button type="button" class="page-btn" data-action="prev" disabled>Prev</button>
        <span class="page-indicator">Page 1 of 1</span>
        <button type="button" class="page-btn" data-action="next">Next</button>
    </div>

<!-- Skill Detail Modal -->
<div id="skill-modal-overlay" class="modal-overlay" style="display:none; z-index:10000; background:rgba(0,0,0,0.45); backdrop-filter:blur(3px);">
    <div style="background:var(--surface, #fff); border-radius:18px; width:90%; max-width:650px; max-height:82vh; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,0.25); display:flex; flex-direction:column;">
        <div style="padding:1.5rem 1.5rem 1rem; border-bottom:1px solid rgba(32,0,130,0.08);">
            <div style="display:flex; align-items:center; justify-content:space-between;">
                <div style="display:flex; align-items:center; gap:0.75rem;">
                    <div id="skill-modal-icon" style="width:48px; height:48px; border-radius:14px; background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center; font-size:1.2rem;"></div>
                    <div>
                        <h2 id="skill-modal-name" style="margin:0; font-size:1.2rem; font-weight:800; color:var(--text);"></h2>
                        <span id="skill-modal-count" style="font-size:0.78rem; color:rgba(32,0,130,0.45);"></span>
                    </div>
                </div>
                <button onclick="document.getElementById('skill-modal-overlay').style.display='none'" style="background:none; border:none; font-size:1.2rem; cursor:pointer; color:rgba(32,0,130,0.4); padding:0.25rem;"><i class="fas fa-times"></i></button>
            </div>
        </div>
        <div id="skill-modal-body" style="padding:1.25rem 1.5rem; overflow-y:auto; flex:1;"></div>
    </div>
</div>

<script>
var _skillData = <?= json_encode($skillRows, JSON_HEX_APOS | JSON_HEX_TAG) ?>;
var _skillIcons = ['fas fa-tasks','fas fa-users','fas fa-comments','fas fa-pen','fas fa-chart-bar','fas fa-shield-alt','fas fa-code','fas fa-headset','fas fa-clock','fas fa-lightbulb','fas fa-dollar-sign','fas fa-handshake','fas fa-sync-alt','fas fa-check-double','fas fa-user-tie','fas fa-chess','fas fa-boxes','fas fa-gavel'];
var _skillColors = ['#320082','#5146b7','#7c3aed','#2563eb','#0891b2','#059669','#d97706','#dc2626','#4f46e5','#0d9488','#7c2d12','#1d4ed8','#9333ea','#16a34a','#ca8a04','#e11d48','#0284c7','#7c3aed'];
function openSkillModal(skillId) {
    var skill = _skillData.find(function(s) { return s.id == skillId; });
    if (!skill) return;
    var idx = _skillData.indexOf(skill);
    var icon = _skillIcons[idx % _skillIcons.length];
    var color = _skillColors[idx % _skillColors.length];
    document.getElementById('skill-modal-icon').innerHTML = '<i class="fas ' + icon + '"></i>';
    document.getElementById('skill-modal-icon').style.background = color;
    document.getElementById('skill-modal-name').textContent = skill.name;
    var courses = skill.courses || [];
    var modules = skill.modules || [];
    document.getElementById('skill-modal-count').textContent = (courses.length + modules.length) + ' linked item' + ((courses.length + modules.length) !== 1 ? 's' : '');
    var body = '<div style="margin-bottom:1.25rem;">';
    body += '<h3 style="margin:0 0 0.5rem; font-size:0.8rem; font-weight:700; color:var(--primary); text-transform:uppercase; letter-spacing:0.06em;">Description</h3>';
    body += '<p style="margin:0; font-size:0.92rem; line-height:1.7; color:var(--text);">' + (skill.description || 'No description available.') + '</p>';
    body += '</div>';
    if (courses.length > 0) {
        body += '<div style="margin-bottom:1.25rem;">';
        body += '<h3 style="margin:0 0 0.6rem; font-size:0.8rem; font-weight:700; color:var(--primary); text-transform:uppercase; letter-spacing:0.06em;"><i class="fas fa-book" style="margin-right:0.4rem;"></i>Linked Courses (' + courses.length + ')</h3>';
        body += '<div style="display:grid; gap:0.5rem;">';
        courses.forEach(function(c) {
            body += '<div style="display:flex; align-items:center; gap:0.75rem; padding:0.8rem 1rem; background:rgba(32,0,130,0.04); border:1px solid rgba(32,0,130,0.08); border-radius:10px;">';
            body += '<div style="width:36px; height:36px; border-radius:8px; background:rgba(32,0,130,0.1); color:var(--primary); display:flex; align-items:center; justify-content:center; flex-shrink:0;"><i class="fas fa-book"></i></div>';
            body += '<div style="flex:1; min-width:0;"><div style="font-weight:700; color:var(--text); font-size:0.9rem;">' + c.title + '</div><div style="font-size:0.75rem; color:rgba(32,0,130,0.45);">Course</div></div></div>';
        });
        body += '</div></div>';
    }
    if (modules.length > 0) {
        body += '<div>';
        body += '<h3 style="margin:0 0 0.6rem; font-size:0.8rem; font-weight:700; color:var(--primary); text-transform:uppercase; letter-spacing:0.06em;"><i class="fas fa-cubes" style="margin-right:0.4rem;"></i>Linked Modules (' + modules.length + ')</h3>';
        body += '<div style="display:grid; gap:0.5rem;">';
        modules.forEach(function(m) {
            body += '<div style="display:flex; align-items:center; gap:0.75rem; padding:0.8rem 1rem; background:rgba(81,70,183,0.04); border:1px solid rgba(81,70,183,0.08); border-radius:10px;">';
            body += '<div style="width:36px; height:36px; border-radius:8px; background:rgba(81,70,183,0.1); color:var(--primary); display:flex; align-items:center; justify-content:center; flex-shrink:0;"><i class="fas fa-cubes"></i></div>';
            body += '<div style="flex:1; min-width:0;"><div style="font-weight:700; color:var(--text); font-size:0.9rem;">' + m.title + '</div><div style="font-size:0.75rem; color:rgba(32,0,130,0.45);">' + (m.course_title || '') + '</div></div></div>';
        });
        body += '</div></div>';
    }
    if (courses.length === 0 && modules.length === 0) {
        body += '<div style="text-align:center; padding:1.5rem; color:rgba(32,0,130,0.35);"><i class="fas fa-link" style="font-size:1.5rem; display:block; margin-bottom:0.5rem;"></i>No courses or modules linked to this skill yet.</div>';
    }
    document.getElementById('skill-modal-body').innerHTML = body;
    document.getElementById('skill-modal-overlay').style.display = 'flex';
}
document.getElementById('skill-modal-overlay').addEventListener('click', function(e) {
    if (e.target.id === 'skill-modal-overlay') this.style.display = 'none';
});

(function () {
    const getElements = () => ({
        entityContent: document.getElementById('tr-entity-content'),
        modalTitle: document.getElementById('tr-modal-title'),
        modalEditBtn: document.getElementById('tr-modal-edit-btn'),
        modalCloseBtn: document.getElementById('tr-modal-close-btn'),
        modalDescription: document.getElementById('tr-modal-description'),
        overviewStatus: document.getElementById('tr-modal-overview-status'),
        overviewParent: document.getElementById('tr-modal-overview-parent'),
        overviewChildren: document.getElementById('tr-modal-overview-children'),
        overviewEnrollment: document.getElementById('tr-modal-overview-enrollment')
    });

    const state = { type: null, id: null, activeTab: 'overview' };

    function getEntityIcon(type) {
        const iconMap = {
            'Learning Path': '<i class="fas fa-route"></i>',
            Program: '<i class="fas fa-briefcase"></i>',
            'Video Conference': '<i class="fas fa-video"></i>'
        };
        return iconMap[type] || '<i class="fas fa-folder"></i>';
    }

    function openModal() {
        const { entityModal } = getElements();
        if (entityModal) {
            entityContent.style.display = 'flex';
            if (typeof window.sizeEntityModal === 'function') {
                window.sizeEntityModal(entityModal, state.type);
            }
        }
    }

    function closeModal() {
        const { entityModal } = getElements();
        if (entityModal) {
            entityContent.style.display = 'none';
        }
    }

    function syncContentTabs() {
        document.querySelectorAll('.entity-content-tab').forEach((btn) => {
            const isActive = state.activeTab === btn.dataset.contentTab;
            const label = btn.dataset.label || btn.textContent.trim();
            const icon = btn.dataset.icon || '';
            btn.classList.toggle('active', isActive);
            btn.innerHTML = `${icon} ${label}`;
            btn.style.background = isActive ? 'rgba(32,0,130,0.08)' : '#fff';
            btn.style.border = isActive ? 'none' : '1px solid rgba(32,0,130,0.12)';
            btn.style.color = isActive ? 'var(--primary)' : 'var(--text)';
        });
        document.querySelectorAll('.entity-content-panel').forEach((panel) => {
            panel.style.display = panel.id === 'tr-entity-content-' + state.activeTab ? 'block' : 'none';
        });
    }

    function renderOverviewDetails(type, data, description, status) {
        const { modalDescription, overviewStatus, overviewParent, overviewChildren, overviewEnrollment } = getElements();
        if (overviewStatus) overviewStatus.textContent = status || 'Active';
        
        // Use real data from AJAX response
        if (type === 'Program') {
            if (overviewParent) overviewParent.textContent = 'Training portfolio';
            if (overviewChildren) overviewChildren.textContent = (data.enrollment_count || 0) + ' enrollments';
            if (overviewEnrollment) overviewEnrollment.textContent = (data.completion_count || 0) + ' completed';
        } else if (type === 'Video Conference') {
            if (overviewParent) overviewParent.textContent = 'Program-linked session';
            if (overviewChildren) overviewChildren.textContent = (data.attendance_count || 0) + ' attendees';
            if (overviewEnrollment) overviewEnrollment.textContent = 'Scheduled attendance';
        } else {
            if (overviewParent) overviewParent.textContent = 'Learning path sequence';
            if (overviewChildren) overviewChildren.textContent = '0 child items';
            if (overviewEnrollment) overviewEnrollment.textContent = 'Assigned group';
        }
        
        if (modalDescription) modalDescription.textContent = description || 'No description provided for this item yet.';
    }

    function getEditUrl(type, id) {
        const map = {
            'Learning Path': '?page=instructor/learning-path&id=' + id,
            Program: '?page=instructor/training-subpage/program&id=' + id,
            'Video Conference': '?page=instructor/training-subpage/video-conference&id=' + id
        };
        return map[type] || '?page=instructor/training';
    }

    function loadTrainingTree(type, id, container) {
        if (!container) return;
        container.innerHTML = '<p style="text-align:center; color:#999;">Loading training structure...</p>';

        fetch(`pages/instructor/ajax/get-training-structure.php?type=${encodeURIComponent(type)}&id=${id}`, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then((response) => response.json())
        .then((payload) => {
            if (!payload.success || !payload.data) {
                throw new Error(payload.error || 'Unable to load training structure.');
            }

            const data = payload.data;
            const parent = data.parent || { title: 'Parent item', type, id };
            const children = Array.isArray(data.children) ? data.children : [];
            const parentRow = `
                <div style="padding:1rem 1.2rem; border:1px solid rgba(32,0,130,0.12); border-radius:12px; background:var(--surface, #fff);">
                    <div style="font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase; font-weight:700; color:var(--primary);">PARENT</div>
                    <div style="margin-top:0.7rem; display:flex; align-items:center; gap:0.75rem; padding:0.75rem 0.9rem; background:rgba(32,0,130,0.06); border-radius:8px; min-height:52px;">
                        <div style="flex:1; color:var(--text); font-weight:700; font-size:1.02rem;">${parent.title || 'Untitled parent'}</div>
                        <div style="display:flex; gap:0.35rem;">
                            <button class="modal-view-child-btn" data-entity-type="${type}" data-entity-id="${id}" style="padding:0.35rem 0.7rem; font-size:0.72rem; background:rgba(32,0,130,0.12); color:var(--primary); border:1px solid rgba(32,0,130,0.2); border-radius:4px; cursor:pointer; font-weight:600;">View</button>
                            <button class="modal-edit-child-btn" data-edit-url="${getEditUrl(type, id)}" style="padding:0.35rem 0.7rem; font-size:0.72rem; background:var(--primary); color:var(--surface); border:none; border-radius:4px; cursor:pointer; font-weight:600;">Edit</button>
                        </div>
                    </div>
                </div>
            `;

            const childRows = children.length > 0 ? children.map((node) => `
                <div style="margin-bottom:0.4rem;">
                    <div class="modal-toggle-item" data-toggle-target="training-${type}-${node.id}" style="display:flex; align-items:center; gap:0.75rem; padding:0.7rem 0.8rem; background:rgba(81,70,183,0.06); border-radius:8px; cursor:pointer; min-height:46px;">
                        <span class="modal-toggle-arrow" style="font-weight:700; color:var(--text); min-width:1.1rem; font-size:1rem;">•</span>
                        <span style="flex:1; font-weight:700; color:var(--text); font-size:1rem; display:flex; align-items:center; gap:0.5rem;">
                            <i class="fas fa-clipboard-list" style="font-size:0.9rem;"></i>
                            <span>${node.title || 'Untitled item'}</span>
                        </span>
                        <div style="display:flex; gap:0.25rem;">
                            <button class="modal-view-child-btn" data-entity-type="${node.type}" data-entity-id="${node.id}" style="padding:0.3rem 0.55rem; font-size:0.72rem; background:rgba(32,0,130,0.12); color:var(--primary); border:1px solid rgba(32,0,130,0.2); border-radius:4px; cursor:pointer; font-weight:600;">View</button>
                            <button class="modal-edit-child-btn" data-edit-url="${getEditUrl(node.type, node.id)}" style="padding:0.3rem 0.55rem; font-size:0.72rem; background:var(--primary); color:var(--surface); border:none; border-radius:4px; cursor:pointer; font-weight:600;">Edit</button>
                        </div>
                    </div>
                </div>
            `).join('') : '<div style="color:#999; font-size:0.85rem;">No child items</div>';

            container.innerHTML = `
                <div style="display:grid; gap:1rem;">
                    ${parentRow}
                    <div style="padding:1rem 1.2rem; border:1px solid rgba(32,0,130,0.12); border-radius:12px; background:var(--surface, #fff);">
                        <div style="font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase; font-weight:700; color:var(--primary);">CHILDREN</div>
                        <div style="margin-top:0.7rem; display:grid; gap:0.5rem; color:var(--text);">
                            ${childRows}
                        </div>
                    </div>
                </div>
            `;

            container.querySelectorAll('.modal-edit-child-btn').forEach((button) => {
                button.addEventListener('click', () => {
                    window.location.href = button.dataset.editUrl;
                });
            });

            container.querySelectorAll('.modal-view-child-btn').forEach((button) => {
                button.addEventListener('click', () => {
                    const entityType = button.dataset.entityType;
                    const entityId = button.dataset.entityId;
                    const detailMap = {
                        'Learning Path': `pages/instructor/ajax/get-learning-path-by-id.php?id=${entityId}`,
                        Program: `pages/instructor/training-subpage/ajax/get-program-by-id.php?id=${entityId}`,
                        'Video Conference': `pages/instructor/training-subpage/ajax/get-video-conference-by-id.php?id=${entityId}`
                    };
                    const url = detailMap[entityType];
                    if (!url) return;
                    fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then((r) => r.json())
                        .then((data) => {
                            if (!data.success || !data.data) return;
                            const entity = data.data;
                            state.type = entityType;
                            state.id = Number(entityId);
                            state.activeTab = 'structure';
                            if (document.getElementById('tr-modal-title')) document.getElementById('tr-modal-title').innerHTML = `${getEntityIcon(entityType)} ${entity.title || 'Untitled'}`;
                            renderOverviewDetails(entityType, entity, entity.description || 'No description', entity.status || 'Active');
                            loadTrainingTree(entityType, Number(entityId), document.getElementById('tr-entity-content-structure-content'));
                            syncContentTabs();
                            openModal();
                        });
                });
            });
        })
        .catch((error) => {
            console.error('Error loading training structure:', error);
            container.innerHTML = '<p style="color:#999; text-align:center;">Unable to load training structure</p>';
        });
    }

    document.body.addEventListener('click', function (event) {
        const card = event.target.closest('.catalog-card');
        if (!card) return;

        const type = card.dataset.type;
        const id = Number(card.dataset.entityId);
        const title = (card.querySelector('h4') || card.querySelector('h3'))?.textContent || 'Untitled';
        const description = (card.querySelector('.cc-desc') || card.querySelector('p'))?.textContent || 'No description';
        const status = card.dataset.status || 'active';

        state.type = type;
        state.id = id;
        state.activeTab = 'overview';

        const { modalTitle } = getElements();
        if (modalTitle) modalTitle.innerHTML = `${getEntityIcon(type)} ${title}`;

        // Fetch real data from AJAX endpoint
        const ajaxMap = {
            'Learning Path': `pages/instructor/ajax/get-learning-path-by-id.php?id=${id}`,
            'Program': `pages/instructor/training-subpage/ajax/get-program-by-id.php?id=${id}`,
            'Video Conference': `pages/instructor/training-subpage/ajax/get-video-conference-by-id.php?id=${id}`
        };

        fetch(ajaxMap[type] || '', { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.data) {
                    renderOverviewDetails(type, data.data, description, status);
                } else {
                    renderOverviewDetails(type, {}, description, status);
                }
                loadTrainingTree(type, id, document.getElementById('tr-entity-content-structure-content'));
                syncContentTabs();
                openModal();
            })
            .catch(() => {
                renderOverviewDetails(type, {}, description, status);
                loadTrainingTree(type, id, document.getElementById('tr-entity-content-structure-content'));
                syncContentTabs();
                openModal();
            });
    });

    const { modalEditBtn, modalCloseBtn, entityModal } = getElements();
    if (modalEditBtn) {
        modalEditBtn.onclick = () => {
            if (!state.type || !state.id) return;
            window.location.href = getEditUrl(state.type, state.id);
        };
    }
    if (modalCloseBtn) modalCloseBtn.onclick = closeModal;
    if (entityModal) {
        entityContent.onclick = (event) => {
            if (event.target === entityModal) closeModal();
        };
    }
    document.querySelectorAll('.entity-content-tab').forEach((button) => {
        button.onclick = () => {
            state.activeTab = button.dataset.contentTab;
            syncContentTabs();
        };
    });

    // Skills pagination
    var skillsPageSize = 9;
    var skillsPage = 1;
    var skillsGrid = document.getElementById('tr-skills-grid');
    if (skillsGrid) {
        var skillCards = Array.from(skillsGrid.querySelectorAll('.skill-card-item'));
        function paginateSkills() {
            var totalPages = Math.max(1, Math.ceil(skillCards.length / skillsPageSize));
            skillsPage = Math.min(skillsPage, totalPages);
            var start = (skillsPage - 1) * skillsPageSize;
            skillCards.forEach(function(c, i) { c.style.display = (i >= start && i < start + skillsPageSize) ? '' : 'none'; });
            var pg = document.getElementById('skills-pagination');
            if (pg) {
                pg.querySelector('.page-indicator').textContent = 'Page ' + skillsPage + ' of ' + totalPages;
                pg.querySelector('[data-action="prev"]').disabled = skillsPage <= 1;
                pg.querySelector('[data-action="next"]').disabled = skillsPage >= totalPages;
                pg.style.display = totalPages <= 1 ? 'none' : '';
            }
        }
        var skillsPg = document.getElementById('skills-pagination');
        if (skillsPg) {
            skillsPg.addEventListener('click', function(e) {
                var btn = e.target.closest('[data-action]');
                if (!btn || btn.disabled) return;
                if (btn.dataset.action === 'prev' && skillsPage > 1) skillsPage--;
                if (btn.dataset.action === 'next') skillsPage++;
                paginateSkills();
            });
        }
        paginateSkills();
    }

    // Catalog navigation (tabs, search, view toggle, pagination)
    var catalogSearchInput = document.getElementById('tr-catalog-search-input');
    var catalogCountEl = document.getElementById('tr-catalog-count');
    var catalogPaginationEl = document.getElementById('tr-catalog-pagination');
    var catalogActiveTab = 'tab-learning-path';
    var catalogPageSize = 12;
    var catalogPage = 1;
    var addWrap = document.getElementById('tr-catalog-add-wrap');
    var addBtn = document.getElementById('tr-catalog-add-btn');
    var addTip = document.getElementById('tr-catalog-add-tip');
    var addConfig = {
        'tab-learning-path': { label: 'Add Learning Path', url: '?page=instructor/learning-path' },
        'tab-program': { label: 'Add Program', url: '?page=instructor/training-subpage/program' },
        'tab-video-conference': { label: 'Add Live Session', url: '?page=instructor/training-subpage/video-conference' },
        'tab-skills': null
    };
    function syncAddBtn() {
        if (!addWrap) return;
        var cfg = addConfig[catalogActiveTab];
        if (!cfg) { addWrap.style.display = 'none'; return; }
        addWrap.style.display = '';
        if (addTip) addTip.textContent = cfg.label;
        if (addBtn) { addBtn.title = cfg.label; addBtn.setAttribute('aria-label', cfg.label); }
    }
    if (addBtn) addBtn.addEventListener('click', function() {
        var cfg = addConfig[catalogActiveTab];
        if (cfg) window.location.href = cfg.url;
    });

    function getCatalogGrid() {
        var tc = document.querySelector('.tab-content[data-tab="' + catalogActiveTab + '"]');
        return tc ? tc.querySelector('.catalog-grid') : null;
    }
    function getCatalogCards() {
        var grid = getCatalogGrid();
        return grid ? Array.from(grid.querySelectorAll('.catalog-card')) : [];
    }
    function updateCatalogGrid() {
        var cards = getCatalogCards();
        var q = (catalogSearchInput ? catalogSearchInput.value : '').toLowerCase().trim();
        var visible = cards.filter(function(c) { return q === '' || c.textContent.toLowerCase().indexOf(q) > -1; });
        var totalPages = Math.max(1, Math.ceil(visible.length / catalogPageSize));
        catalogPage = Math.min(catalogPage, totalPages);
        var start = (catalogPage - 1) * catalogPageSize;
        cards.forEach(function(c) { c.style.display = 'none'; });
        visible.forEach(function(c, i) { c.style.display = (i >= start && i < start + catalogPageSize) ? '' : 'none'; });
        if (catalogCountEl) catalogCountEl.textContent = visible.length + ' items';
        if (catalogPaginationEl) {
            catalogPaginationEl.querySelector('.page-indicator').textContent = 'Page ' + catalogPage + ' of ' + totalPages;
            catalogPaginationEl.querySelector('[data-action="prev"]').disabled = catalogPage <= 1;
            catalogPaginationEl.querySelector('[data-action="next"]').disabled = catalogPage >= totalPages;
            catalogPaginationEl.style.display = totalPages <= 1 ? 'none' : '';
        }
    }
    document.querySelectorAll('.catalog-tab-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.catalog-tab-btn').forEach(function(b) { b.classList.remove('active'); });
            btn.classList.add('active');
            var tabId = btn.dataset.tab;
            document.querySelectorAll('.tab-content').forEach(function(tc) { tc.classList.remove('active'); });
            var target = document.querySelector('.tab-content[data-tab="' + tabId + '"]');
            if (target) target.classList.add('active');
            catalogActiveTab = tabId;
            catalogPage = 1;    updateCatalogGrid();
    syncAddBtn();
    var trPageSizeSelect = document.getElementById('tr-catalog-page-size');
    if (trPageSizeSelect) { trPageSizeSelect.addEventListener('change', function() { catalogPageSize = parseInt(this.value, 10) || 12; catalogPage = 1; updateCatalogGrid(); }); }


        });
    });
    if (catalogSearchInput) catalogSearchInput.addEventListener('input', function() { catalogPage = 1; updateCatalogGrid(); });
    document.querySelectorAll('.catalog-view-toggle button').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.catalog-view-toggle button').forEach(function(b) { b.classList.remove('active'); });
            btn.classList.add('active');
            var grid = getCatalogGrid();
            if (grid) grid.classList.toggle('list-view', btn.dataset.view === 'list');
        });
    });
    if (catalogPaginationEl) {
        catalogPaginationEl.addEventListener('click', function(e) {
            var btn = e.target.closest('[data-action]');
            if (!btn || btn.disabled) return;
            if (btn.dataset.action === 'prev' && catalogPage > 1) catalogPage--;
            if (btn.dataset.action === 'next') catalogPage++;
            updateCatalogGrid();
        });
    }
    updateCatalogGrid();
    syncAddBtn();
})();
</script>


