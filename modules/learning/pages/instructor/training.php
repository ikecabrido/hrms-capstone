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

    $viewLearningPathId = (int) ($_GET['view_learning_path'] ?? 0);
    if ($viewLearningPathId > 0) {
        $learningPathRows = $runQuery($pdo, 'SELECT id, title, description, status, created_at, type, is_public FROM ld_learning_path WHERE id = :id', [':id' => $viewLearningPathId]);
    } else {
        $learningPathRows = $runQuery($pdo, "SELECT id, title, description, status, created_at, type, is_public FROM ld_learning_path ORDER BY created_at DESC LIMIT 20");
    }
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
    $typeLabels = ['Learning Path' => 'Learning Path', 'Program' => 'Program', 'Video Conference' => 'Online Training'];
    $typeLinks = [
        'Learning Path' => '?page=instructor/learning-path&id=',
        'Program' => '?page=instructor/training-subpage/program&id=',
        'Video Conference' => '?page=instructor/training-subpage/video-conference&id=',
    ];
    foreach ($items as $item) {
        $id = (int) ($item['id'] ?? 0);
        $title = trim((string) ($item['title'] ?? 'Untitled'));
        $description = trim((string) ($item['description'] ?? ($item['platform'] ?? '')));
        $status = trim((string) ($item['status'] ?? 'active'));
        // Card thumbnail tone: color encodes the training type, so the grid
        // reads as one family instead of four unrelated gradients.
        if ($label === 'Learning Path') {
            $tone = 'learning-path';
        } elseif ($label === 'Program') {
            $tone = 'program';
        } else {
            $platform = $item['platform'] ?? '';
            $tone = $platform === 'zoom' ? 'zoom' : ($platform === 'google_meet' ? 'meet' : 'video');
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
        $isKT = ($item['type'] ?? 'standard') === 'knowledge_transfer';
        $isPublic = !empty($item['is_public']);
        echo '<article class="catalog-card" data-status="' . htmlspecialchars($status) . '" data-type="' . htmlspecialchars($label) . '" data-entity-id="' . $id . '"' . ($isKT ? ' data-kt="1" data-path-id="' . $id . '" data-is-public="' . ($isPublic ? '1' : '0') . '"' : '') . '>';
        echo '<div class="catalog-card-thumb" data-tone="' . $tone . '">';
        echo '<i class="fas ' . $icon . ' thumb-icon"></i>';
        $badgeText = $isKT ? 'Knowledge Transfer' : $typeLabel;
        echo '<span class="catalog-card-type-badge">' . htmlspecialchars($badgeText) . '</span>';
        echo '</div>';
        echo '<div class="catalog-card-body">';
        echo '<h4>' . htmlspecialchars($title) . '</h4>';
        echo '<p class="cc-desc">' . htmlspecialchars($shortDesc) . '</p>';
        echo '</div>';
        if ($footerInfo) {
            echo '<div class="catalog-card-stats">';
            echo '<span><i class="fas fa-circle-info"></i> ' . htmlspecialchars($footerInfo) . '</span>';
            echo '</div>';
        }
        echo '<div class="catalog-card-footer">';
        echo '<span class="cc-deadline">' . htmlspecialchars($status) . '</span>';
        echo '<div class="cat-card-actions">';
        if ($isKT) {
            echo '<button type="button" class="kt-toggle-public-btn ' . ($isPublic ? 'is-public' : 'is-private') . '" data-path-id="' . $id . '" title="Toggle who can see this path">' . ($isPublic ? 'Public' : 'Private') . '</button>';
        }
        echo '<button class="cc-enroll-btn enroll"><i class="fas fa-eye"></i> View</button>';
        echo '</div>';
        echo '</div>';
        echo '</article>';
    }
}
?>

<div class="module-content catalog-page catalog-manager">
    <!-- Entity Detail Modal -->
    <div id="tr-entity-content" class="modal-overlay ld-modal-scrim" role="dialog" aria-modal="true" aria-labelledby="tr-modal-title" style="display:none;">
        <div id="tr-entity-content-panel" class="entity-content-box ld-modal-panel" data-size="standard">
            <div class="entity-content-header ld-modal-header">
                <h2 id="tr-modal-title"></h2>
                <div class="entity-content-actions">
                    <button id="tr-modal-invite-btn" type="button" class="ec-btn is-quiet" style="display:none;">Invite</button>
                    <button id="tr-modal-edit-btn" class="ec-btn is-primary">Edit</button>
                    <button id="tr-modal-archive-btn" class="ec-btn is-quiet">Archive</button>
                    <button id="tr-modal-close-btn" class="ec-btn is-quiet">Close</button>
                </div>
            </div>
            <div class="entity-content-tabs">
                <button type="button" class="entity-content-tab active" data-content-tab="overview" data-label="Overview" data-icon="<i class='fas fa-eye'></i>">Overview</button>
                <button type="button" class="entity-content-tab" data-content-tab="structure" data-label="Structure" data-icon="<i class='fas fa-sitemap'></i>">Structure</button>
                <button type="button" class="entity-content-tab" data-content-tab="monitor" data-label="Monitor" data-icon="<i class='fas fa-chart-line'></i>">Monitor</button>
            </div>
            <div class="entity-content-body ld-modal-body">
                <div id="tr-entity-content-overview" class="entity-content-panel" style="display:block;">
                    <div id="tr-modal-content-grid" class="ec-fields">
                        <div class="ec-field">
                            <span class="ec-field-label">Status</span>
                            <p id="tr-modal-overview-status" class="ec-field-value">Active</p>
                        </div>
                        <div class="ec-field">
                            <span class="ec-field-label">Parent</span>
                            <p id="tr-modal-overview-parent" class="ec-field-value">-</p>
                        </div>
                        <div class="ec-field">
                            <span class="ec-field-label">Children</span>
                            <p id="tr-modal-overview-children" class="ec-field-value">0</p>
                        </div>
                    </div>
                    <div class="ec-section">
                        <span class="ec-section-title">Description</span>
                        <p id="tr-modal-description" class="ec-section-text">No description</p>
                    </div>
                </div>
                <div id="tr-entity-content-structure" class="entity-content-panel" style="display:none;">
                    <div id="tr-entity-content-structure-content"></div>
                </div>
                <div id="tr-entity-content-monitor" class="entity-content-panel" style="display:none;">
                    <div id="tr-entity-content-monitor-content">
                        <div class="ec-empty"><i class="fas fa-chart-line" aria-hidden="true"></i>No monitoring data available yet.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Invite Employees Modal -->
    <div id="training-invite-modal" class="modal-overlay ld-modal-scrim" role="dialog" aria-modal="true" aria-labelledby="training-invite-title" style="display:none;">
        <div class="ld-modal-panel">
            <div class="ld-modal-header">
                <div>
                    <h3 id="training-invite-title">Invite Employees</h3>
                    <p>Select employees to invite to this learning path.</p>
                </div>
                <button id="training-invite-modal-close" type="button" class="cat-modal-close" aria-label="Close"><i class="fas fa-times" aria-hidden="true"></i></button>
            </div>
            <form id="training-invite-form" method="post" class="ld-modal-body cat-form" data-skip>
                <input type="hidden" id="training-invite-path-id" name="id" value="" />
                <label class="cat-field">
                    <span class="cat-field-label">Invite employees</span>
                    <select id="training-invitees" name="invitee_ids[]" multiple size="8" class="cat-invite-select">
                        <option value="" disabled>Loading employees...</option>
                    </select>
                </label>
                <div class="ld-modal-actions">
                    <button type="button" id="training-invite-modal-cancel" class="cat-btn-quiet">Cancel</button>
                    <button type="submit" class="cat-btn-primary">Send Invitations</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Archive Confirm Overlay -->
    <div id="archive-confirm-overlay" class="modal-overlay ld-modal-scrim" role="dialog" aria-modal="true" aria-labelledby="training-archive-title" style="display:none;">
        <div class="ld-modal-panel ld-modal-confirm">
            <h3 id="training-archive-title" class="cat-modal-title">Confirm Archive</h3>
            <p id="archive-confirm-message" class="cat-modal-text">Are you sure you want to archive this item?</p>
            <div class="ld-modal-actions">
                <button id="archive-confirm-cancel" class="cat-btn-quiet">Cancel</button>
                <button id="archive-confirm-ok" class="cat-btn-danger">Archive</button>
            </div>
        </div>
    </div>

    <div class="home-dashboard">
        <section class="home-hero">
            <div class="home-hero-main">
                <h1>Trainings</h1>
                <p>Assemble learning paths, programs and live online sessions, and see which skills your training content builds.</p>
            </div>
            <div class="home-hero-aside">
                <div class="home-hero-chips">
                    <span class="home-hero-chip"><i class="fas fa-route" aria-hidden="true"></i> <?= count($learningPathRows) ?> learning path<?= count($learningPathRows) === 1 ? '' : 's' ?></span>
                    <span class="home-hero-chip"><i class="fas fa-briefcase" aria-hidden="true"></i> <?= count($programRows) ?> program<?= count($programRows) === 1 ? '' : 's' ?></span>
                    <span class="home-hero-chip"><i class="fas fa-video" aria-hidden="true"></i> <?= count($videoConferenceRows) ?> online training<?= count($videoConferenceRows) === 1 ? '' : 's' ?></span>
                    <span class="home-hero-chip"><i class="fas fa-star" aria-hidden="true"></i> <?= count($skillRows) ?> skill<?= count($skillRows) === 1 ? '' : 's' ?></span>
                </div>
            </div>
        </section>

        <div class="catalog-toolbar" id="browse-training">
            <div class="catalog-search">
                <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                <input type="search" id="tr-catalog-search-input" placeholder="Search programs, learning paths, sessions..." aria-label="Search training" />
            </div>
            <div class="catalog-view-toggle" role="group" aria-label="Result layout">
                <button type="button" class="active" data-view="grid" title="Grid view" aria-label="Grid view"><i class="fas fa-th-large" aria-hidden="true"></i></button>
                <button type="button" data-view="list" title="List view" aria-label="List view"><i class="fas fa-list" aria-hidden="true"></i></button>
            </div>
            <span class="catalog-count" id="tr-catalog-count"></span>
            <select id="tr-catalog-page-size" class="cat-page-size" data-display-pref="page_size" aria-label="Items per page" title="Items per page">
                <option value="12">12</option>
                <option value="24">24</option>
                <option value="36">36</option>
            </select>
            <div class="catalog-add-wrap" id="tr-catalog-add-wrap">
                <button type="button" id="tr-catalog-add-btn" class="cat-add-btn" aria-label="Add learning path" title="Add learning path">
                    <i class="fas fa-plus" aria-hidden="true"></i>
                    <span class="catalog-add-tip" id="tr-catalog-add-tip">Add Learning Path</span>
                </button>
            </div>
        </div>

        <div class="catalog-tabs" id="tr-catalog-tabs">
            <button type="button" class="catalog-tab-btn active" data-tab="tab-learning-path">
                <i class="fas fa-route" aria-hidden="true"></i> Learning Path
            </button>
            <button type="button" class="catalog-tab-btn" data-tab="tab-program">
                <i class="fas fa-briefcase" aria-hidden="true"></i> Program
            </button>
            <button type="button" class="catalog-tab-btn" data-tab="tab-video-conference">
                <i class="fas fa-video" aria-hidden="true"></i> Online Training
            </button>
            <button type="button" class="catalog-tab-btn" data-tab="tab-skills">
                <i class="fas fa-star" aria-hidden="true"></i> Skills
            </button>
        </div>

        <div class="tab-content active" data-tab="tab-learning-path">
            <div class="cat-section-intro">
                <h3>Learning Path Management</h3>
                <p>Compose ordered learning paths from courses, quizzes, evaluations, and programs.</p>
            </div>
            <div class="catalog-grid">
                <?php renderTrainingCards($learningPathRows, 'Learning Path', '?page=instructor/learning-path'); ?>
            </div>
        </div>

        <div class="tab-content" data-tab="tab-program">
            <div class="cat-section-intro">
                <h3>Program Management</h3>
                <p>Manage training programs, schedule sessions, and assign program-level learning objectives.</p>
            </div>
            <div class="catalog-grid">
                <?php renderTrainingCards($programRows, 'Program', '?page=instructor/training-subpage/program'); ?>
            </div>
        </div>

        <div class="tab-content" data-tab="tab-video-conference">
            <div class="cat-section-intro">
                <h3>Online Training Management</h3>
                <p>Set up online training sessions, host virtual training, and manage attendance links.</p>
            </div>
            <div class="catalog-grid">
                <?php renderTrainingCards($videoConferenceRows, 'Video Conference', '?page=instructor/training-subpage/video-conference'); ?>
            </div>
        </div>

        <div class="tab-content" data-tab="tab-skills">
            <div class="cat-section-intro">
                <h3>Skills</h3>
                <p>Explore skills linked to e-learning courses and training modules.</p>
            </div>
            <div id="tr-skills-grid" class="catalog-grid">
                <?php
                $skillIcons = ['fas fa-tasks','fas fa-users','fas fa-comments','fas fa-pen','fas fa-chart-bar','fas fa-shield-alt','fas fa-code','fas fa-headset','fas fa-clock','fas fa-lightbulb','fas fa-dollar-sign','fas fa-handshake','fas fa-sync-alt','fas fa-check-double','fas fa-user-tie','fas fa-chess','fas fa-boxes','fas fa-gavel'];
                $skillColors = ['#320082','#5146b7','#7c3aed','#2563eb','#0891b2','#059669','#d97706','#dc2626','#4f46e5','#0d9488','#7c2d12','#1d4ed8','#9333ea','#16a34a','#ca8a04','#e11d48','#0284c7','#7c3aed'];
                foreach ($skillRows as $idx => $skill):
                    $icon = $skillIcons[$idx % count($skillIcons)];
                    $color = $skillColors[$idx % count($skillColors)];
                    $courseCount = count($skill['courses']);
                    $moduleCount = count($skill['modules']);
                    $totalItems = $courseCount + $moduleCount;
                    $skillDesc = (string) ($skill['description'] ?? '');
                ?>
                    <div class="skill-card-item catalog-skill" onclick="openSkillModal(<?= (int) $skill['id'] ?>)">
                        <div class="catalog-skill-head">
                            <div class="catalog-skill-icon" style="background:<?= htmlspecialchars($color) ?>;"><i class="<?= htmlspecialchars($icon) ?>" aria-hidden="true"></i></div>
                            <div style="flex:1; min-width:0;">
                                <h3 class="catalog-skill-name"><?= htmlspecialchars($skill['name']) ?></h3>
                                <span class="catalog-skill-meta"><?= $totalItems ?> linked item<?= $totalItems !== 1 ? 's' : '' ?></span>
                            </div>
                        </div>
                        <p class="catalog-skill-desc"><?= htmlspecialchars(mb_substr($skillDesc, 0, 120)) ?><?= mb_strlen($skillDesc) > 120 ? '&hellip;' : '' ?></p>
                        <div class="catalog-skill-tags">
                            <?php if ($courseCount > 0): ?><span class="catalog-skill-tag"><?= $courseCount ?> Course<?= $courseCount !== 1 ? 's' : '' ?></span><?php endif; ?>
                            <?php if ($moduleCount > 0): ?><span class="catalog-skill-tag"><?= $moduleCount ?> Module<?= $moduleCount !== 1 ? 's' : '' ?></span><?php endif; ?>
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

        <!-- Catalog Empty State -->
        <div class="catalog-empty" id="tr-catalog-empty" style="display:none;">
            <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
            <h4>No items found</h4>
            <p>Try selecting a different tab or adjusting your search.</p>
        </div>

        <!-- Catalog Pagination -->
        <div class="pagination-row" id="tr-catalog-pagination">
            <button type="button" class="page-btn" data-action="prev" disabled>Prev</button>
            <span class="page-indicator">Page 1 of 1</span>
            <button type="button" class="page-btn" data-action="next">Next</button>
        </div>
    </div>

    <!-- Skill Detail Modal -->
    <div id="skill-modal-overlay" class="modal-overlay ld-modal-scrim" role="dialog" aria-modal="true" aria-labelledby="skill-modal-name" style="display:none;">
        <div class="ld-modal-panel">
            <div class="ld-modal-header">
                <div style="display:flex; align-items:center; gap:0.75rem; min-width:0;">
                    <div id="skill-modal-icon" class="catalog-skill-icon" style="background:var(--primary);"></div>
                    <div style="min-width:0;">
                        <h2 id="skill-modal-name"></h2>
                        <span id="skill-modal-count" class="catalog-skill-meta"></span>
                    </div>
                </div>
                <button type="button" class="cat-modal-close" aria-label="Close skill details" onclick="document.getElementById('skill-modal-overlay').style.display='none'"><i class="fas fa-times" aria-hidden="true"></i></button>
            </div>
            <div id="skill-modal-body" class="ld-modal-body"></div>
        </div>
    </div>
</div>

<script>
var _skillData = <?= json_encode($skillRows, JSON_HEX_APOS | JSON_HEX_TAG) ?>;
var _skillIcons = ['fas fa-tasks','fas fa-users','fas fa-comments','fas fa-pen','fas fa-chart-bar','fas fa-shield-alt','fas fa-code','fas fa-headset','fas fa-clock','fas fa-lightbulb','fas fa-dollar-sign','fas fa-handshake','fas fa-sync-alt','fas fa-check-double','fas fa-user-tie','fas fa-chess','fas fa-boxes','fas fa-gavel'];
var _skillColors = ['#320082','#5146b7','#7c3aed','#2563eb','#0891b2','#059669','#d97706','#dc2626','#4f46e5','#0d9488','#7c2d12','#1d4ed8','#9333ea','#16a34a','#ca8a04','#e11d48','#0284c7','#7c3aed'];
function escSkill(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function (ch) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
    });
}
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
    var body = '<div class="cat-block">';
    body += '<h3 class="cat-mini-title">Description</h3>';
    body += '<p style="margin:0; font-size:0.92rem; line-height:1.7; color:var(--text);">' + escSkill(skill.description || 'No description available.') + '</p>';
    body += '</div>';
    if (courses.length > 0) {
        body += '<div class="cat-block">';
        body += '<h3 class="cat-mini-title"><i class="fas fa-book" aria-hidden="true"></i>Linked Courses (' + courses.length + ')</h3>';
        body += '<div class="cat-list">';
        courses.forEach(function(c) {
            body += '<div class="cat-list-item">';
            body += '<div class="cat-list-icon"><i class="fas fa-book" aria-hidden="true"></i></div>';
            body += '<div class="cat-list-body"><div class="cat-list-title">' + escSkill(c.title) + '</div><div class="cat-list-meta">Course</div></div></div>';
        });
        body += '</div></div>';
    }
    if (modules.length > 0) {
        body += '<div class="cat-block">';
        body += '<h3 class="cat-mini-title"><i class="fas fa-cubes" aria-hidden="true"></i>Linked Modules (' + modules.length + ')</h3>';
        body += '<div class="cat-list">';
        modules.forEach(function(m) {
            body += '<div class="cat-list-item">';
            body += '<div class="cat-list-icon"><i class="fas fa-cubes" aria-hidden="true"></i></div>';
            body += '<div class="cat-list-body"><div class="cat-list-title">' + escSkill(m.title) + '</div><div class="cat-list-meta">' + escSkill(m.course_title || '') + '</div></div></div>';
        });
        body += '</div></div>';
    }
    if (courses.length === 0 && modules.length === 0) {
        body += '<div class="cat-empty-note"><i class="fas fa-link" aria-hidden="true"></i>No courses or modules linked to this skill yet.</div>';
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
        modalInviteBtn: document.getElementById('tr-modal-invite-btn'),
        modalTitle: document.getElementById('tr-modal-title'),
        modalEditBtn: document.getElementById('tr-modal-edit-btn'),
        modalCloseBtn: document.getElementById('tr-modal-close-btn'),
        modalDescription: document.getElementById('tr-modal-description'),
        overviewStatus: document.getElementById('tr-modal-overview-status'),
        overviewParent: document.getElementById('tr-modal-overview-parent'),
        overviewChildren: document.getElementById('tr-modal-overview-children')
    });

    const state = { type: null, id: null, activeTab: 'overview' };

    const entityConfig = {
        'Learning Path': { editUrl: '?page=instructor/learning-path&id=', archiveEndpoint: 'pages/instructor/ajax/archive-learning-path.php' },
        'Program': { editUrl: '?page=instructor/training-subpage/program&id=', archiveEndpoint: 'elearning-subpage/ajax/archive-program.php' },
        'Video Conference': { editUrl: '?page=instructor/training-subpage/video-conference&id=', archiveEndpoint: 'elearning-subpage/ajax/archive-video-conference.php' },
        'Course': { editUrl: '?page=instructor/elearning-subpage/course&back=instructor/training&id=', archiveEndpoint: 'elearning-subpage/ajax/archive-course.php' },
        'Module': { editUrl: '?page=instructor/elearning-subpage/module&back=instructor/training&id=', archiveEndpoint: 'elearning-subpage/ajax/archive-module.php' },
        'Lesson': { editUrl: '?page=instructor/elearning-subpage/lesson&back=instructor/training&id=', archiveEndpoint: 'elearning-subpage/ajax/archive-lesson.php' },
        'Quiz': { editUrl: '?page=instructor/elearning-subpage/quiz&back=instructor/training&id=', archiveEndpoint: 'elearning-subpage/ajax/archive-quiz.php' },
        'Evaluation': { editUrl: '?page=instructor/elearning-subpage/evaluation&back=instructor/training&id=', archiveEndpoint: 'elearning-subpage/ajax/archive-evaluation.php' }
    };

    function getEntityIcon(type) {
        const iconMap = {
            'Learning Path': '<i class="fas fa-route"></i>',
            Program: '<i class="fas fa-briefcase"></i>',
            'Video Conference': '<i class="fas fa-video"></i>'
        };
        return iconMap[type] || '<i class="fas fa-folder"></i>';
    }

    function openModal() {
        const el = getElements().entityContent;
        if (el) {
            el.style.display = 'flex';
            if (typeof window.sizeEntityModal === 'function') {
                window.sizeEntityModal(el, state.type);
            }
        }
    }

    function closeModal() {
        const el = getElements().entityContent;
        if (el) {
            el.style.display = 'none';
        }
    }

    function updateInviteButton() {
        const { modalInviteBtn } = getElements();
        if (!modalInviteBtn) return;
        modalInviteBtn.style.display = state.type === 'Learning Path' ? 'inline-flex' : 'none';
    }

    function loadInviteOptions() {
        const inviteSelect = document.getElementById('training-invitees');
        if (!inviteSelect) return;
        if (inviteSelect.options.length > 0 && inviteSelect.options[0].value !== '') return;

        fetch('pages/instructor/ajax/get-learners.php', {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then((response) => response.json())
        .then((payload) => {
            const learners = payload && Array.isArray(payload.learners) ? payload.learners : [];
            inviteSelect.innerHTML = learners.length ? learners.map((employee) => {
                const name = employee.full_name || ('Employee #' + employee.employee_id);
                return '<option value="' + employee.employee_id + '">' + name + '</option>';
            }).join('') : '<option value="" disabled>No employees available</option>';
        })
        .catch(() => {
            inviteSelect.innerHTML = '<option value="" disabled>Failed to load employees</option>';
        });
    }

    function openInviteModal() {
        if (!state.type || !state.id || state.type !== 'Learning Path') return;

        const inviteModal = document.getElementById('training-invite-modal');
        const pathIdInput = document.getElementById('training-invite-path-id');
        const inviteSelect = document.getElementById('training-invitees');
        if (!inviteModal || !pathIdInput || !inviteSelect) return;

        pathIdInput.value = String(state.id);
        inviteSelect.innerHTML = '<option value="" disabled>Loading employees...</option>';
        inviteModal.style.display = 'flex';
        loadInviteOptions();
    }

    function closeInviteModal() {
        const inviteModal = document.getElementById('training-invite-modal');
        if (inviteModal) inviteModal.style.display = 'none';
    }

    function syncContentTabs() {
        document.querySelectorAll('.entity-content-tab').forEach((btn) => {
            const isActive = state.activeTab === btn.dataset.contentTab;
            const label = btn.dataset.label || btn.textContent.trim();
            const icon = btn.dataset.icon || '';
            btn.innerHTML = `${icon}${label}`;
            btn.classList.toggle('active', isActive);
        });
        document.querySelectorAll('.entity-content-panel').forEach((panel) => {
            panel.style.display = panel.id === 'tr-entity-content-' + state.activeTab ? 'block' : 'none';
        });
    }

    function renderOverviewDetails(type, data, description, status, items) {
        const { modalDescription, overviewStatus, overviewParent, overviewChildren } = getElements();
        if (overviewStatus) overviewStatus.textContent = status || 'Active';
        
        if (type === 'Program') {
            if (overviewParent) overviewParent.textContent = 'Training portfolio';
            if (overviewChildren) overviewChildren.textContent = (data.enrollment_count || 0) + ' enrollments';
        } else if (type === 'Video Conference') {
            if (overviewParent) overviewParent.textContent = 'Program-linked session';
            if (overviewChildren) overviewChildren.textContent = (data.attendance_count || 0) + ' attendees';
        } else {
            if (overviewChildren) {
                const count = Array.isArray(items) ? items.length : 0;
                overviewChildren.textContent = count || '0';
            }
            if (overviewParent) overviewParent.textContent = '-';
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
        container.innerHTML = '<div class="ec-empty"><i class="fas fa-spinner fa-spin" aria-hidden="true"></i>Loading training structure...</div>';

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
                <div class="cat-struct-panel">
                    <div class="cat-mini-title">Parent</div>
                    <div class="cat-struct-row is-parent">
                        <div class="cat-struct-title">${parent.title || 'Untitled parent'}</div>
                        <div class="cat-struct-actions">
                            <button class="modal-view-child-btn cat-mini-btn" data-entity-type="${type}" data-entity-id="${id}">View</button>
                            <button class="modal-edit-child-btn cat-mini-btn cat-mini-btn--primary" data-edit-url="${getEditUrl(type, id)}">Edit</button>
                        </div>
                    </div>
                </div>
            `;

            const childRows = children.length > 0 ? children.map((node) => `
                <div>
                    <div class="modal-toggle-item cat-struct-row is-child" data-toggle-target="training-${type}-${node.id}">
                        <span class="modal-toggle-arrow cat-struct-arrow">•</span>
                        <span class="cat-struct-title">
                            <i class="fas fa-clipboard-list" aria-hidden="true"></i>
                            <span>${node.title || 'Untitled item'}</span>
                        </span>
                        <div class="cat-struct-actions">
                            <button class="modal-view-child-btn cat-mini-btn" data-entity-type="${node.type}" data-entity-id="${node.id}">View</button>
                            <button class="modal-edit-child-btn cat-mini-btn cat-mini-btn--primary" data-edit-url="${getEditUrl(node.type, node.id)}">Edit</button>
                        </div>
                    </div>
                </div>
            `).join('') : '<div class="ec-empty is-inline">No child items</div>';

            container.innerHTML = `
                <div class="cat-struct">
                    ${parentRow}
                    <div class="cat-struct-panel">
                        <div class="cat-mini-title">Children</div>
                        <div class="cat-struct-list">
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
                            renderOverviewDetails(entityType, entity, entity.description || 'No description', entity.status || 'Active', []);
                            loadTrainingTree(entityType, Number(entityId), document.getElementById('tr-entity-content-structure-content'));
                            syncContentTabs();
                            openModal();
                        });
                });
            });
        })
        .catch((error) => {
            console.error('Error loading training structure:', error);
            container.innerHTML = '<div class="ec-empty is-inline is-error"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i>Unable to load training structure</div>';
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
        updateInviteButton();

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
                const pathData = data.success && data.data ? data.data : {};
                if (type === 'Learning Path' && pathData) {
                    window.__lastLearningPathData = pathData;
                }
                const items = data.success && Array.isArray(data.items) ? data.items : [];
                renderOverviewDetails(type, pathData, description, status, items);
                loadTrainingTree(type, id, document.getElementById('tr-entity-content-structure-content'));
                syncContentTabs();
                updateInviteButton();
                openModal();
            })
            .catch(() => {
                if (type === 'Learning Path') window.__lastLearningPathData = { title, description, status };
                renderOverviewDetails(type, {}, description, status, []);
                loadTrainingTree(type, id, document.getElementById('tr-entity-content-structure-content'));
                syncContentTabs();
                updateInviteButton();
                openModal();
            });
    });

    const { modalEditBtn, modalCloseBtn, modalInviteBtn, entityContent: trEntityEl } = getElements();
    if (modalInviteBtn) {
        modalInviteBtn.onclick = () => {
            if (state.type === 'Learning Path') {
                openInviteModal();
            }
        };
    }
    if (modalEditBtn) {
        modalEditBtn.onclick = () => {
            if (!state.type || !state.id) return;
            // Build edit URL directly to avoid map key mismatches
            let editUrl = '';
            if (state.type === 'Learning Path') editUrl = '?page=instructor/learning-path&id=' + state.id;
            else if (state.type === 'Program') editUrl = '?page=instructor/training-subpage/program&id=' + state.id;
            else if (state.type === 'Video Conference') editUrl = '?page=instructor/training-subpage/video-conference&id=' + state.id;
            else if (state.type === 'Course') editUrl = '?page=instructor/elearning-subpage/course&back=instructor/training&id=' + state.id;
            else if (state.type === 'Module') editUrl = '?page=instructor/elearning-subpage/module&back=instructor/training&id=' + state.id;
            else if (state.type === 'Lesson') editUrl = '?page=instructor/elearning-subpage/lesson&back=instructor/training&id=' + state.id;
            else if (state.type === 'Quiz') editUrl = '?page=instructor/elearning-subpage/quiz&back=instructor/training&id=' + state.id;
            else if (state.type === 'Evaluation') editUrl = '?page=instructor/elearning-subpage/evaluation&back=instructor/training&id=' + state.id;
            if (editUrl) window.location.href = editUrl;
        };
    }
    if (document.getElementById('tr-modal-archive-btn')) {
        document.getElementById('tr-modal-archive-btn').onclick = () => {
            if (!state.type || !state.id) return;
            const cfg = entityConfig[state.type];
            if (!cfg) { if (window.showToast) window.showToast('Archive not available', 'error'); return; }
            document.getElementById('archive-confirm-message').textContent = 'Are you sure you want to archive this ' + state.type.toLowerCase() + '?';
            document.getElementById('archive-confirm-overlay').style.display = 'flex';
        };
    }
    if (document.getElementById('archive-confirm-ok')) {
        document.getElementById('archive-confirm-ok').onclick = () => {
            document.getElementById('archive-confirm-overlay').style.display = 'none';
            if (!state.type || !state.id) return;
            const cfg = entityConfig[state.type];
            if (!cfg) return;
            fetch(cfg.archiveEndpoint, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify({ id: state.id }) })
                .then(r => r.json())
                .then(d => { if (d.success) { if (window.showToast) window.showToast('Archived successfully', 'success'); setTimeout(() => location.reload(), 800); } else { if (window.showToast) window.showToast(d.error || 'Failed', 'error'); } })
                .catch(() => { if (window.showToast) window.showToast('Network error', 'error'); });
        };
    }
    if (document.getElementById('archive-confirm-cancel')) {
        document.getElementById('archive-confirm-cancel').onclick = () => {
            document.getElementById('archive-confirm-overlay').style.display = 'none';
        };
    }
    if (document.getElementById('archive-confirm-overlay')) {
        document.getElementById('archive-confirm-overlay').onclick = (e) => {
            if (e.target.id === 'archive-confirm-overlay') document.getElementById('archive-confirm-overlay').style.display = 'none';
        };
    }
    if (modalCloseBtn) modalCloseBtn.onclick = closeModal;
    const inviteModalClose = document.getElementById('training-invite-modal-close');
    if (inviteModalClose) inviteModalClose.onclick = closeInviteModal;
    const inviteModalCancel = document.getElementById('training-invite-modal-cancel');
    if (inviteModalCancel) inviteModalCancel.onclick = closeInviteModal;
    const inviteModal = document.getElementById('training-invite-modal');
    if (inviteModal) {
        inviteModal.onclick = (event) => {
            if (event.target === inviteModal) closeInviteModal();
        };
    }
    const inviteForm = document.getElementById('training-invite-form');
    if (inviteForm) {
        inviteForm.addEventListener('submit', function (event) {
            event.preventDefault();
            const pathId = Number(document.getElementById('training-invite-path-id')?.value || 0);
            const inviteSelect = document.getElementById('training-invitees');
            const selectedIds = inviteSelect ? Array.from(inviteSelect.selectedOptions).map((option) => Number(option.value)).filter(Boolean) : [];

            if (!pathId || selectedIds.length === 0) {
                if (window.showToast) window.showToast('Please select at least one employee to invite.', 'error');
                return;
            }

            const payload = {
                id: pathId,
                title: window.__lastLearningPathData?.title || '',
                description: window.__lastLearningPathData?.description || '',
                status: window.__lastLearningPathData?.status || 'active',
                is_public: window.__lastLearningPathData?.is_public ?? 1,
                invitee_ids: selectedIds
            };

            fetch('pages/instructor/ajax/edit-learning-path.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify(payload)
            })
            .then((response) => response.json())
            .then((data) => {
                if (!data || !data.success) {
                    throw new Error(data && data.message ? data.message : 'Unable to send invitations.');
                }
                closeInviteModal();
                if (window.showToast) window.showToast('Invitations sent successfully.', 'success');
                setTimeout(() => window.location.reload(), 500);
            })
            .catch((error) => {
                if (window.showToast) window.showToast(error.message || 'Unable to send invitations.', 'error');
            });
        });
    }
    if (trEntityEl) {
        trEntityEl.onclick = (event) => {
            if (event.target === trEntityEl) closeModal();
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
    var catalogEmptyEl = document.getElementById('tr-catalog-empty');
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
        'tab-video-conference': { label: 'Add Online Training', url: '?page=instructor/training-subpage/video-conference' },
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
        if (!cfg) return;
        if (cfg.url) window.location.href = cfg.url;
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
        if (catalogEmptyEl) catalogEmptyEl.style.display = (catalogActiveTab !== 'tab-skills' && visible.length === 0) ? '' : 'none';
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
    var trPageSizeSelect = document.getElementById('tr-catalog-page-size');
    if (trPageSizeSelect) {
        var ldps = parseInt(window.LD_DEFAULT_PAGE_SIZE, 10) || 12;
        trPageSizeSelect.value = String(ldps);
        catalogPageSize = ldps;
        trPageSizeSelect.addEventListener('change', function() { catalogPageSize = parseInt(this.value, 10) || 12; catalogPage = 1; updateCatalogGrid(); });
    }
    updateCatalogGrid();
    syncAddBtn();

    var viewPathId = new URLSearchParams(window.location.search).get('view_learning_path');
    if (viewPathId) {
        var viewPathCard = document.querySelector('.catalog-card[data-type="Learning Path"][data-entity-id="' + viewPathId + '"]');
        if (viewPathCard) viewPathCard.click();
    }

    // KT Public Toggle
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.kt-toggle-public-btn');
        if (!btn) return;
        e.stopPropagation();
        btn.disabled = true;
        fetch('pages/instructor/ajax/toggle-kt-path-public.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ path_id: btn.dataset.pathId })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                btn.textContent = data.is_public ? 'Public' : 'Private';
                btn.classList.toggle('is-public', !!data.is_public);
                btn.classList.toggle('is-private', !data.is_public);
            }
            btn.disabled = false;
        })
        .catch(function() { btn.disabled = false; });
    });
})();
</script>


