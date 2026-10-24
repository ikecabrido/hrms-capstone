<?php
include_once __DIR__ . '/../../classes/Employee.php';
require_once dirname(__DIR__, 4) . '/database/db.php';

$employeeClass = new Employee();
$instructorId = (int) ($employeeClass->getEmployeeId() ?? 0);
$instructorProfileUrl = LearningProfileLink::url($instructorId, 'instructor');

$requests = [];
$stats = ['pending' => 0, 'accepted' => 0, 'rejected' => 0, 'completed' => 0, 'total' => 0];

try {
    $pdo = (new Database())->getConnection();

    // Workforce requests may have no learner row, so keep the employee joins optional.
    // ld_training_requests does not store course or learning-path IDs.
    $stmt = $pdo->prepare("
        SELECT tr.*,
               CONCAT_WS(' ', emp.first_name, emp.last_name) AS learner_name,
               emp.employee_code AS learner_code,
               CONCAT_WS(' ', inst.first_name, inst.last_name) AS instructor_name
        FROM ld_training_requests tr
        LEFT JOIN em_employees emp ON emp.employee_id = tr.learner_id
        LEFT JOIN em_employees inst ON inst.employee_id = tr.instructor_id
        WHERE tr.source_module = 'workforce' OR emp.employee_id IS NOT NULL
        ORDER BY
            CASE tr.status
                WHEN 'pending' THEN 0
                WHEN 'accepted' THEN 1
                WHEN 'rejected' THEN 2
                WHEN 'completed' THEN 3
            END,
            tr.created_at DESC
    ");
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($requests as $r) {
        $stats['total']++;
        $s = $r['status'];
        if (isset($stats[$s])) $stats[$s]++;
    }
} catch (Throwable $e) {
    DbError::capture($e, 'instructor/training-requests');
    $requests = [];
}

function trTimeAgo($dt) {
    if (!$dt) return 'Never';
    $d = (new DateTime())->diff(new DateTime($dt));
    if ($d->days > 30) return $d->days . 'd ago';
    if ($d->days > 0) return $d->days . 'd ' . $d->h . 'h ago';
    if ($d->h > 0) return $d->h . 'h ' . $d->i . 'm ago';
    return $d->i . 'm ago';
}
?>

<style>
.tr-page { --tr-line:rgba(32,0,130,0.10); --tr-tint:rgba(32,0,130,0.035); }

.tr-stats { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:0.85rem; margin-bottom:1.1rem; }
.tr-stat {
    display:flex;
    align-items:center;
    gap:0.85rem;
    min-width:0;
    padding:0.9rem 1rem;
    border-radius:12px;
    background:var(--surface,#fff);
    border:1px solid var(--tr-line);
    box-shadow:0 2px 8px rgba(15,23,42,0.035);
    text-align:left;
}
.tr-stat-icon {
    display:grid;
    place-items:center;
    width:2.5rem;
    height:2.5rem;
    flex:0 0 2.5rem;
    border-radius:10px;
    background:var(--tr-tone-bg);
    color:var(--tr-tone);
}
.tr-stat.pending { --tr-tone:#b77908; --tr-tone-bg:rgba(245,158,11,0.12); }
.tr-stat.accepted { --tr-tone:#059669; --tr-tone-bg:rgba(16,185,129,0.12); }
.tr-stat.rejected { --tr-tone:#dc2626; --tr-tone-bg:rgba(239,68,68,0.11); }
.tr-stat.completed { --tr-tone:#2563eb; --tr-tone-bg:rgba(59,130,246,0.11); }
.tr-stat .tr-stat-val { color:var(--text); font-size:1.35rem; font-weight:750; line-height:1.1; }
.tr-stat .tr-stat-label { margin-top:0.2rem; color:var(--muted); font-size:0.73rem; font-weight:600; }
.tr-stat > span:last-child { display:flex; flex-direction:column; }

.tr-page .catalog-toolbar { margin-bottom:0.9rem; box-shadow:0 2px 8px rgba(15,23,42,0.035); }
.tr-list { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); align-items:stretch; gap:0.85rem; }
.tr-card {
    position:relative;
    display:flex;
    flex-direction:column;
    min-width:0;
    padding:1rem 1.05rem 0.95rem 1.15rem;
    overflow:hidden;
    background:var(--surface,#fff);
    border:1px solid var(--tr-line);
    border-left:3px solid var(--tr-status,#94a3b8);
    border-radius:12px;
    box-shadow:0 2px 8px rgba(15,23,42,0.035);
    transition:box-shadow 0.18s ease, transform 0.18s ease;
}
.tr-card.pending { --tr-status:#f59e0b; }
.tr-card.accepted { --tr-status:#10b981; }
.tr-card.rejected { --tr-status:#ef4444; }
.tr-card.completed { --tr-status:#3b82f6; }
.tr-card:hover { box-shadow:0 8px 20px rgba(15,23,42,0.08); transform:translateY(-1px); }

.tr-card-header { display:flex; align-items:center; gap:0.75rem; margin-bottom:0.85rem; }
.tr-card-avatar {
    display:flex;
    align-items:center;
    justify-content:center;
    width:2.65rem;
    height:2.65rem;
    border:2px solid var(--surface,#fff);
    border-radius:50%;
    box-shadow:0 0 0 1px var(--tr-line);
    color:#fff;
    font-size:0.82rem;
    font-weight:700;
    flex-shrink:0;
}
.tr-card-info { flex:1; min-width:0; }
.tr-card-name { color:var(--text); font-size:0.92rem; font-weight:700; }
.tr-card-meta { margin-top:0.15rem; color:var(--muted); font-size:0.74rem; }
.tr-card-status {
    padding:0.26rem 0.6rem;
    border:1px solid transparent;
    border-radius:999px;
    font-size:0.68rem;
    font-weight:700;
}
.tr-card-status.pending { background:rgba(245,158,11,0.12); border-color:rgba(245,158,11,0.2); color:#9a6700; }
.tr-card-status.accepted { background:rgba(16,185,129,0.1); border-color:rgba(16,185,129,0.18); color:#047857; }
.tr-card-status.rejected { background:rgba(239,68,68,0.09); border-color:rgba(239,68,68,0.16); color:#b91c1c; }
.tr-card-status.completed { background:rgba(59,130,246,0.1); border-color:rgba(59,130,246,0.18); color:#1d4ed8; }

.tr-skill-tag {
    display:inline-flex;
    align-items:center;
    align-self:flex-start;
    gap:0.4rem;
    margin-bottom:0.65rem;
    padding:0.35rem 0.65rem;
    border:1px solid rgba(32,0,130,0.09);
    border-radius:8px;
    background:var(--tr-tint);
    color:var(--primary,#320082);
    font-size:0.78rem;
    font-weight:650;
}

/* What answers the request and who owns it, surfaced from the Workforce Inbox */
.tr-assign-row { display:flex; flex-wrap:wrap; align-items:center; gap:0.45rem; margin-bottom:0.75rem; }
.tr-chip {
    display:inline-flex; align-items:center; gap:0.35rem; max-width:100%;
    padding:0.3rem 0.7rem; border-radius:999px; border:1px solid transparent;
    font-size:0.78rem; font-weight:600; text-decoration:none;
}
.tr-chip span { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.tr-chip em {
    flex-shrink:0; padding:0.05rem 0.35rem; border-radius:999px;
    font-style:normal; font-size:0.6rem; font-weight:800;
    letter-spacing:0.04em; text-transform:uppercase;
}
.tr-chip.is-course { background:rgba(99,102,241,0.10); border-color:rgba(99,102,241,0.30); color:#4338ca; }
.tr-chip.is-course em { background:rgba(99,102,241,0.16); }
.tr-chip.is-path { background:rgba(16,185,129,0.10); border-color:rgba(16,185,129,0.30); color:#047857; }
.tr-chip.is-path em { background:rgba(16,185,129,0.16); }
.tr-chip.is-instructor { background:rgba(32,0,130,0.06); border-color:rgba(32,0,130,0.14); color:var(--primary,#320082); }
.tr-chip.is-empty { background:rgba(148,163,184,0.10); border-color:rgba(148,163,184,0.25); color:var(--muted,#999); font-weight:500; }
.tr-chip.is-course:hover, .tr-chip.is-path:hover { box-shadow:0 0 0 2px rgba(32,0,130,0.10); }

.tr-badge-workforce {
    display:inline-block; margin-left:0.4rem; padding:0.1rem 0.45rem; border-radius:999px;
    background:rgba(32,0,130,0.06); color:var(--muted,#999);
    font-size:0.62rem; font-weight:800; letter-spacing:0.06em;
    text-transform:uppercase; vertical-align:middle;
}
.tr-card-message {
    margin:0 0 0.75rem;
    padding:0.7rem 0.8rem;
    border:1px solid var(--tr-line);
    border-radius:9px;
    background:var(--tr-tint);
    color:var(--text);
    font-size:0.82rem;
    line-height:1.5;
}
.tr-card-footer { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:0.6rem; margin-top:auto; padding-top:0.65rem; border-top:1px solid var(--tr-line); }
.tr-card-time { font-size:0.75rem; color:var(--muted); }
.tr-card-actions { display:flex; flex-wrap:wrap; gap:0.45rem; }
.tr-btn {
    padding:0.45rem 0.85rem;
    border-radius:8px;
    font-size:0.78rem;
    font-weight:650;
    border:1px solid transparent;
    cursor:pointer;
    transition:background-color 0.15s ease, border-color 0.15s ease;
}
.tr-btn:disabled { opacity:0.55; cursor:not-allowed; }
.tr-btn:focus-visible, .sg-rm-btn:focus-visible, .tr-page input:focus-visible, .tr-page select:focus-visible {
    outline:3px solid rgba(81,70,183,0.3);
    outline-offset:2px;
}
.tr-btn-accept { background:#10b981; color:#fff; }
.tr-btn-accept:hover:not(:disabled) { background:#059669; }
.tr-btn-reject { background:rgba(239,68,68,0.07); color:#b91c1c; border-color:rgba(239,68,68,0.18); }
.tr-btn-reject:hover:not(:disabled) { background:rgba(239,68,68,0.13); }
.tr-btn-complete { background:rgba(59,130,246,0.08); color:#1d4ed8; border-color:rgba(59,130,246,0.18); }
.tr-btn-complete:hover:not(:disabled) { background:rgba(59,130,246,0.14); }

.tr-instructor-note {
    margin-top:0.75rem; padding:0.6rem 0.75rem;
    border-radius:8px; font-size:0.82rem;
    background:rgba(16,185,129,0.05); border:1px solid rgba(16,185,129,0.1);
    color:var(--text);
}
.tr-instructor-note strong { color:#059669; }

.tr-empty { padding:3.5rem 1rem; border:1px dashed var(--tr-line); border-radius:12px; background:var(--surface,#fff); color:var(--muted); text-align:center; }
.tr-empty i { display:block; margin-bottom:0.85rem; color:var(--primary); font-size:2rem; opacity:0.65; }
.tr-empty h4 { margin:0 0 0.4rem; color:var(--text); font-size:1rem; }
.tr-empty p { margin:0; font-size:0.85rem; }

@media (max-width:900px) { .tr-list { grid-template-columns:1fr; } }
@media (max-width:768px) {
    .tr-stats { grid-template-columns:repeat(2,minmax(0,1fr)); gap:0.65rem; }
    .tr-stat { padding:0.75rem; gap:0.65rem; }
    .tr-stat-icon { width:2.2rem; height:2.2rem; flex-basis:2.2rem; }
}
@media (max-width:420px) {
    .tr-card-header { flex-wrap:wrap; }
    .tr-card-status { margin-left:auto; }
    .tr-card-footer { align-items:flex-start; flex-direction:column; }
    .tr-card-actions { width:100%; }
    .tr-btn { flex:1; }
}
@media (prefers-reduced-motion:reduce) {
    .tr-card, .tr-btn { transition:none; }
}

/* Toolbar */
.catalog-toolbar { display:flex; align-items:center; gap:0.75rem; padding:0.75rem 1rem; background:var(--surface,#fff); border-radius:12px; border:1px solid rgba(32,0,130,0.08); margin-bottom:1rem; flex-wrap:wrap; }
.catalog-search { flex:1; min-width:200px; position:relative; }
.catalog-search input { width:100%; padding:0.6rem 1rem 0.6rem 2.5rem; border:1.5px solid rgba(32,0,130,0.1); border-radius:10px; background:rgba(32,0,130,0.03); font-size:0.88rem; outline:none; transition:border-color 0.2s,box-shadow 0.2s; box-sizing:border-box; }
.catalog-search input:focus { border-color:var(--primary,#320082); box-shadow:0 0 0 3px rgba(32,0,130,0.08); }
.catalog-search i { position:absolute; left:0.85rem; top:50%; transform:translateY(-50%); color:var(--muted,#999); font-size:0.85rem; }
.catalog-count { font-size:0.8rem; color:var(--muted,#999); white-space:nowrap; }
.catalog-tab-select { position:relative; flex-shrink:0; }
.catalog-tab-select select { appearance:none; -webkit-appearance:none; padding:0.5rem 2rem 0.5rem 0.85rem; border:1.5px solid rgba(32,0,130,0.1); border-radius:10px; background:rgba(32,0,130,0.03); font-size:0.85rem; font-family:inherit; color:var(--text,#1a1a2e); cursor:pointer; transition:border-color 0.2s,box-shadow 0.2s; min-width:140px; }
.catalog-tab-select select:focus { border-color:var(--primary,#320082); box-shadow:0 0 0 3px rgba(32,0,130,0.08); outline:none; }
.catalog-tab-select .select-icon { position:absolute; right:0.75rem; top:50%; transform:translateY(-50%); font-size:0.65rem; color:var(--muted,#999); pointer-events:none; }

/* Modal styles */
.sg-request-modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.4); display:flex; align-items:center; justify-content:center; z-index:9999; opacity:0; pointer-events:none; transition:opacity 0.2s; }
.sg-request-modal-overlay.active { opacity:1; pointer-events:auto; }
.sg-request-modal { background:var(--surface,#fff); border-radius:14px; padding:1.75rem; width:90%; max-width:420px; box-shadow:0 20px 60px rgba(0,0,0,0.15); transform:translateY(20px); transition:transform 0.2s; }
.sg-request-modal-overlay.active .sg-request-modal { transform:translateY(0); }
.sg-request-modal h3 { margin:0 0 0.5rem; font-size:1.05rem; color:var(--text); }
.sg-request-modal p { margin:0 0 1rem; font-size:0.85rem; color:var(--muted); }
.sg-request-modal textarea { width:100%; padding:0.7rem; border:1.5px solid rgba(32,0,130,0.1); border-radius:10px; font-size:0.88rem; font-family:inherit; resize:vertical; min-height:80px; box-sizing:border-box; background:rgba(32,0,130,0.03); outline:none; }
.sg-request-modal textarea:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(32,0,130,0.08); }
.sg-rm-actions { display:flex; gap:0.75rem; justify-content:flex-end; margin-top:1rem; }
.sg-rm-btn { padding:0.55rem 1.2rem; border-radius:8px; font-size:0.85rem; font-weight:600; border:none; cursor:pointer; transition:all 0.15s; }
.sg-rm-cancel { background:rgba(32,0,130,0.06); color:var(--text); }
.sg-rm-cancel:hover { background:rgba(32,0,130,0.12); }
</style>

<div class="module-content tr-page">
    <div class="home-dashboard">
        <section class="home-hero" aria-labelledby="training-requests-title">
            <div class="home-hero-main">
                <h1 id="training-requests-title">Training Requests</h1>
                <p>Review learner requests and Workforce training needs, then decide what happens next.</p>
            </div>
            <div class="home-hero-aside">
                <div class="home-hero-chips">
                    <span class="home-hero-chip"><i class="fas fa-inbox" aria-hidden="true"></i> <?= $stats['total'] ?> total request<?= $stats['total'] === 1 ? '' : 's' ?></span>
                    <span class="home-hero-chip"><i class="fas fa-hourglass-half" aria-hidden="true"></i> <?= $stats['pending'] ?> pending</span>
                    <span class="home-hero-chip"><i class="fas fa-flag-checkered" aria-hidden="true"></i> <?= $stats['completed'] ?> completed</span>
                </div>
            </div>
        </section>

        <!-- Stats -->
        <div class="tr-stats">
            <div class="tr-stat pending">
                <span class="tr-stat-icon"><i class="fas fa-hourglass-half" aria-hidden="true"></i></span>
                <span><span class="tr-stat-val"><?= $stats['pending'] ?></span><span class="tr-stat-label">Pending</span></span>
            </div>
            <div class="tr-stat accepted">
                <span class="tr-stat-icon"><i class="fas fa-circle-check" aria-hidden="true"></i></span>
                <span><span class="tr-stat-val"><?= $stats['accepted'] ?></span><span class="tr-stat-label">Accepted</span></span>
            </div>
            <div class="tr-stat rejected">
                <span class="tr-stat-icon"><i class="fas fa-circle-xmark" aria-hidden="true"></i></span>
                <span><span class="tr-stat-val"><?= $stats['rejected'] ?></span><span class="tr-stat-label">Rejected</span></span>
            </div>
            <div class="tr-stat completed">
                <span class="tr-stat-icon"><i class="fas fa-flag-checkered" aria-hidden="true"></i></span>
                <span><span class="tr-stat-val"><?= $stats['completed'] ?></span><span class="tr-stat-label">Completed</span></span>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="catalog-toolbar">
            <div class="catalog-search">
                <i class="fas fa-search"></i>
                <input type="search" id="tr-search" placeholder="Search by learner or skill..." aria-label="Search requests" />
            </div>
            <div class="catalog-tab-select">
                <select id="tr-status-filter">
                    <option value="all">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="accepted">Accepted</option>
                    <option value="rejected">Rejected</option>
                    <option value="completed">Completed</option>
                </select>
                <i class="fas fa-chevron-down select-icon"></i>
            </div>
            <span class="catalog-count" id="tr-count"><?= count($requests) ?> request<?= count($requests) === 1 ? '' : 's' ?></span>
        </div>

        <?php if (empty($requests)): ?>
            <div class="tr-empty">
                <i class="fas fa-inbox"></i>
                <h4>No Training Requests</h4>
                <p>When learners request training for gap skills, or Workforce sends a training need, it appears here.</p>
            </div>
        <?php else: ?>
            <div class="tr-list" id="tr-list">
            <?php foreach ($requests as $req):
            $status = $req['status'];
            $avatarColors = ['pending'=>'#f59e0b','accepted'=>'#10b981','rejected'=>'#ef4444','completed'=>'#3b82f6'];
            $avatarColor = $avatarColors[$status] ?? '#6366f1';

            $isWorkforce = ($req['source_module'] ?? '') === 'workforce';
            $learnerName = trim((string) ($req['learner_name'] ?? ''));
            $displayName = $learnerName !== '' ? $learnerName : ($isWorkforce ? 'Workforce training need' : 'Unknown learner');
            $learnerId = (int) ($req['learner_id'] ?? 0);
            $assignedInstructorId = (int) ($req['instructor_id'] ?? 0);
            $learnerProfileUrl = $learnerId > 0
                ? ($assignedInstructorId === $instructorId
                    ? LearningProfileLink::url($learnerId, 'learner')
                    : LearningProfileLink::publicUrl($learnerId))
                : '';
            $initials = strtoupper(substr($displayName, 0, 1));

            $department = trim((string) ($req['target_department'] ?? ''));
            $instructorName = trim((string) ($req['instructor_name'] ?? ''));
            $courseId = (int) ($req['course_id'] ?? 0);
            $courseTitle = trim((string) ($req['course_title'] ?? ''));
            $pathId = (int) ($req['learning_path_id'] ?? 0);
            $pathTitle = trim((string) ($req['path_title'] ?? ''));

            // "turnover_risk:1" reads as "Turnover risk #1".
            $reference = trim((string) ($req['external_reference_id'] ?? ''));
            if ($reference !== '' && str_contains($reference, ':')) {
                [$kind, $refId] = explode(':', $reference, 2);
                $reference = ucfirst(str_replace('_', ' ', $kind)) . ' #' . $refId;
            }

            $metaParts = [];
            if ($department !== '') {
                $metaParts[] = $department;
            }
            if (trim((string) ($req['learner_code'] ?? '')) !== '') {
                $metaParts[] = trim((string) $req['learner_code']);
            }
            if ($reference !== '') {
                $metaParts[] = 'From ' . $reference;
            }
            $metaParts[] = 'Requested ' . trTimeAgo($req['created_at']);
        ?>
        <div class="tr-card <?= $status ?>"
             data-status="<?= $status ?>"
             data-search="<?= htmlspecialchars(strtolower($displayName . ' ' . $req['skill_name'] . ' ' . $department . ' ' . $instructorName)) ?>"
             data-id="<?= (int)$req['id'] ?>">
            <div class="tr-card-header">
                <div class="tr-card-avatar" style="background:<?= $avatarColor ?>;"><?= htmlspecialchars($initials) ?></div>
                <div class="tr-card-info">
                    <div class="tr-card-name"><?php if ($learnerProfileUrl !== ''): ?><a href="<?= htmlspecialchars($learnerProfileUrl, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($displayName) ?></a><?php else: ?><?= htmlspecialchars($displayName) ?><?php endif; ?><?php if ($isWorkforce): ?> <span class="tr-badge-workforce">Workforce</span><?php endif; ?></div>
                    <div class="tr-card-meta"><?= htmlspecialchars(implode(' · ', $metaParts)) ?></div>
                </div>
                <span class="tr-card-status <?= $status ?>"><?= ucfirst($status) ?></span>
            </div>

            <div class="tr-skill-tag"><i class="fas fa-puzzle-piece"></i> <?= htmlspecialchars($req['skill_name']) ?></div>

            <div class="tr-assign-row">
                <?php if ($courseId > 0 && $courseTitle !== ''): ?>
                <a class="tr-chip is-course" href="?page=instructor/elearning-subpage/course-structure&amp;course_id=<?= $courseId ?>" title="<?= htmlspecialchars($courseTitle) ?> — open in the course builder">
                    <i class="fas fa-book"></i><span><?= htmlspecialchars($courseTitle) ?></span><em>Course</em>
                </a>
                <?php elseif ($pathId > 0 && $pathTitle !== ''): ?>
                <a class="tr-chip is-path" href="?page=instructor/learning-path" title="<?= htmlspecialchars($pathTitle) ?> — open the learning path builder">
                    <i class="fas fa-route"></i><span><?= htmlspecialchars($pathTitle) ?></span><em>Path</em>
                </a>
                <?php else: ?>
                <span class="tr-chip is-empty"><i class="fas fa-link-slash"></i> No learning linked yet</span>
                <?php endif; ?>

                <?php if ($instructorName !== ''): ?>
                <?php if ($assignedInstructorId > 0): ?>
                <a class="tr-chip is-instructor" data-role="instructor" href="<?= htmlspecialchars(LearningProfileLink::url($assignedInstructorId, 'instructor'), ENT_QUOTES, 'UTF-8') ?>"><i class="fas fa-user-tie"></i> <?= htmlspecialchars($instructorName) ?></a>
                <?php else: ?>
                <span class="tr-chip is-instructor" data-role="instructor"><i class="fas fa-user-tie"></i> <?= htmlspecialchars($instructorName) ?></span>
                <?php endif; ?>
                <?php else: ?>
                <span class="tr-chip is-empty" data-role="instructor"><i class="fas fa-user-slash"></i> Unassigned</span>
                <?php endif; ?>
            </div>

            <?php if (!empty($req['message'])): ?>
            <div class="tr-card-message">"<?= htmlspecialchars($req['message']) ?>"</div>
            <?php endif; ?>

            <?php if (!empty($req['instructor_note'])): ?>
            <div class="tr-instructor-note"><strong>Instructor Note:</strong> <?= htmlspecialchars($req['instructor_note']) ?></div>
            <?php endif; ?>

            <div class="tr-card-footer">
                <span class="tr-card-time"><i class="fas fa-clock" style="margin-right:0.3rem;"></i><?= date('M d, Y \a\t g:i A', strtotime($req['created_at'])) ?></span>
                <?php if ($status === 'pending'): ?>
                <div class="tr-card-actions">
                    <button class="tr-btn tr-btn-reject" data-action="reject" data-id="<?= (int)$req['id'] ?>"><i class="fas fa-times"></i> Reject</button>
                    <button class="tr-btn tr-btn-accept" data-action="accept" data-id="<?= (int)$req['id'] ?>"><i class="fas fa-check"></i> Accept</button>
                </div>
                <?php elseif ($status === 'accepted'): ?>
                <div class="tr-card-actions">
                    <button class="tr-btn tr-btn-complete" data-action="complete" data-id="<?= (int)$req['id'] ?>"><i class="fas fa-flag-checkered"></i> Mark Complete</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Reject Note Modal -->
<div class="sg-request-modal-overlay" id="tr-reject-overlay">
    <div class="sg-request-modal">
        <h3><i class="fas fa-times-circle" style="color:#ef4444;margin-right:0.4rem;"></i> Reject Request</h3>
        <p>Optionally provide a reason for rejecting this training request.</p>
        <textarea id="tr-reject-note" placeholder="Reason for rejection (optional)..."></textarea>
        <div class="sg-rm-actions">
            <button class="sg-rm-btn sg-rm-cancel" id="tr-reject-cancel">Cancel</button>
            <button class="sg-rm-btn" id="tr-reject-confirm" style="background:#ef4444;color:#fff;">Reject</button>
        </div>
    </div>
</div>

<script>
var instructorProfileUrl = <?= json_encode($instructorProfileUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
(function() {
    var list = document.getElementById('tr-list');
    if (!list) return;
    var searchInput = document.getElementById('tr-search');
    var statusFilter = document.getElementById('tr-status-filter');
    var countEl = document.getElementById('tr-count');
    var cards = Array.from(list.querySelectorAll('.tr-card'));

    function applyFilters() {
        var q = searchInput ? searchInput.value.trim().toLowerCase() : '';
        var sv = statusFilter ? statusFilter.value : 'all';
        var visible = 0;
        cards.forEach(function(c) {
            var matchSearch = !q || c.dataset.search.indexOf(q) !== -1;
            var matchStatus = sv === 'all' || c.dataset.status === sv;
            c.style.display = (matchSearch && matchStatus) ? '' : 'none';
            if (matchSearch && matchStatus) visible++;
        });
        if (countEl) countEl.textContent = visible + ' request' + (visible !== 1 ? 's' : '');
    }

    if (searchInput) searchInput.addEventListener('input', applyFilters);
    if (statusFilter) statusFilter.addEventListener('change', applyFilters);

    // Action handlers
    var rejectOverlay = document.getElementById('tr-reject-overlay');
    var rejectNote = document.getElementById('tr-reject-note');
    var rejectConfirm = document.getElementById('tr-reject-confirm');
    var rejectCancel = document.getElementById('tr-reject-cancel');
    var rejectId = null;

    function closeRejectModal() { rejectOverlay.classList.remove('active'); rejectId = null; rejectNote.value = ''; }
    rejectCancel.addEventListener('click', closeRejectModal);
    rejectOverlay.addEventListener('click', function(e) { if (e.target === rejectOverlay) closeRejectModal(); });

    function updateCard(id, newStatus, note, instructorName) {
        var card = list.querySelector('.tr-card[data-id="' + id + '"]');
        if (!card) return;
        card.dataset.status = newStatus;
        card.className = 'tr-card ' + newStatus;
        var statusEl = card.querySelector('.tr-card-status');
        if (statusEl) { statusEl.className = 'tr-card-status ' + newStatus; statusEl.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1); }
        // Acting on a request makes the acting instructor its owner, so the chip has
        // to catch up without a reload.
        if (instructorName) {
            var chip = card.querySelector('[data-role="instructor"]');
            if (chip) {
                chip.className = 'tr-chip is-instructor';
                chip.textContent = '';
                var icon = document.createElement('i');
                icon.className = 'fas fa-user-tie';
                chip.appendChild(icon);
                var profileLink = document.createElement('a');
                profileLink.href = instructorProfileUrl;
                profileLink.textContent = ' ' + instructorName;
                chip.appendChild(profileLink);
            }
        }
        var actionsEl = card.querySelector('.tr-card-actions');
        if (actionsEl) {
            if (newStatus === 'accepted') {
                actionsEl.innerHTML = '<button class="tr-btn tr-btn-complete" data-action="complete" data-id="' + id + '"><i class="fas fa-flag-checkered"></i> Mark Complete</button>';
            } else {
                actionsEl.remove();
            }
        }
        if (note) {
            var footer = card.querySelector('.tr-card-footer');
            var noteDiv = document.createElement('div');
            noteDiv.className = 'tr-instructor-note';
            noteDiv.innerHTML = '<strong>Instructor Note:</strong> ' + note;
            card.insertBefore(noteDiv, footer);
        }
    }

    list.addEventListener('click', function(e) {
        var btn = e.target.closest('.tr-btn');
        if (!btn || btn.disabled) return;
        var action = btn.dataset.action;
        var id = btn.dataset.id;

        if (action === 'reject') {
            rejectId = id;
            rejectNote.value = '';
            rejectOverlay.classList.add('active');
            return;
        }

        btn.disabled = true;
        fetch('pages/instructor/ajax/manage-training-request.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ id: id, action: action })
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.success) {
                updateCard(id, res.new_status || action, null, res.instructor_name);
                applyFilters();
            } else {
                btn.disabled = false;
                alert(res.message || 'Failed.');
            }
        })
        .catch(function() { btn.disabled = false; alert('Network error.'); });
    });

    rejectConfirm.addEventListener('click', function() {
        if (!rejectId) return;
        rejectConfirm.disabled = true;
        fetch('pages/instructor/ajax/manage-training-request.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ id: rejectId, action: 'reject', note: rejectNote.value })
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            rejectConfirm.disabled = false;
            if (res.success) {
                closeRejectModal();
                updateCard(rejectId, 'rejected', res.note || null, res.instructor_name);
                applyFilters();
            } else {
                alert(res.message || 'Failed.');
            }
        })
        .catch(function() { rejectConfirm.disabled = false; alert('Network error.'); });
    });
})();
</script>
