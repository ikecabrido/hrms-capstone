<?php
try {
    require_once dirname(__DIR__, 5) . '/database/db.php';
    $pdo = (new Database())->getConnection();
    $allSkills = $pdo->query("SELECT id, name FROM ld_skill WHERE status = 'active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    DbError::capture($e, 'instructor/elearning-subpage/course');
    $allSkills = [];
}
?>
<style>
.course-wizard { margin-top:0; }
.course-wizard-steps {
    display:flex;
    flex-wrap:wrap;
    align-items:center;
    justify-content:flex-end;
    gap:.45rem;
    width:auto;
    margin:0 0 0 auto;
}
.course-wizard-step-button {
    display:flex; align-items:center; justify-content:center; min-width:0;
    gap:.35rem; padding:0.35rem 0.7rem; border:1px solid rgba(255,255,255,.32); border-radius:999px;
    background:rgba(255,255,255,.16); color:#fff; text-align:center;
    font-size:.74rem; font-weight:600; white-space:nowrap;
    cursor:pointer; transition:border-color .15s, background .15s, color .15s;
}
.course-wizard-step-button:disabled { cursor:not-allowed; opacity:1; }
.course-wizard-step-button.is-current {
    border-color:#fff; background:#fff; color:#200082;
}
.course-wizard-step-button.is-complete { color:#fff; border-color:rgba(255,255,255,.42); }
.course-creation-hero {
    flex-direction:row;
    align-items:center;
    justify-content:space-between;
    gap:1rem;
    margin-bottom:1.25rem;
}
.course-creation-hero .home-hero-main h1 {
    margin:0;
}
.course-creation-hero .home-hero-main p {
    margin:.35rem 0 0;
}
.course-creation-hero .home-hero-main { flex:1 1 270px; }
.course-creation-hero .course-wizard-steps {
    position:relative;
    z-index:1;
    display:flex;
    flex:0 1 auto;
    width:auto;
    margin:0 0 0 auto;
}
.course-wizard-progress {
    height:5px; margin-bottom:1.25rem; overflow:hidden;
    border-radius:999px; background:rgba(32,0,130,.08);
}
.course-wizard-progress span {
    display:block; width:25%; height:100%; border-radius:inherit;
    background:var(--primary); transition:width .2s ease;
}
.course-wizard-panel { display:none; }
.course-wizard-panel.is-active { display:block; }
.course-wizard-panel > h3 { margin:0 0 .35rem; color:var(--text); font-size:1.15rem; }
.course-wizard-panel > .course-wizard-help { margin:0 0 1rem; color:var(--muted); font-size:.86rem; line-height:1.5; }
.course-wizard-review {
    display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:.75rem;
    margin:1rem 0; padding:1rem; border:1px solid rgba(32,0,130,.1);
    border-radius:12px; background:rgba(32,0,130,.025);
}
.course-wizard-review div { min-width:0; }
.course-wizard-review span { display:block; color:var(--muted); font-size:.74rem; }
.course-wizard-review strong { display:block; margin-top:.2rem; color:var(--text); font-size:.9rem; overflow-wrap:anywhere; }
.course-wizard-error {
    display:none; margin:0 0 1rem; padding:.75rem 1rem; border-radius:10px;
    background:rgba(239,68,68,.08); color:#b91c1c; font-size:.84rem;
}
.course-wizard-navigation {
    display:flex; align-items:center; justify-content:space-between; gap:.75rem;
    margin-top:1.25rem; padding-top:1rem; border-top:1px solid rgba(32,0,130,.1);
}
.course-wizard-navigation button {
    padding:.7rem 1.15rem; border:1px solid rgba(32,0,130,.2); border-radius:10px;
    background:var(--surface, #fff); color:var(--primary); font:inherit; font-weight:700; cursor:pointer;
}
.course-wizard-navigation button:disabled { cursor:not-allowed; opacity:.5; }
.course-wizard-navigation .course-wizard-next { border-color:var(--primary); background:var(--primary); color:#fff; }
@media (max-width: 1100px) {
    .course-wizard-steps { justify-content:flex-start; }
}
@media (max-width: 420px) {
    .course-wizard-step-button { gap:.3rem; padding:.4rem; border-radius:14px; font-size:.72rem; }
}
@media (max-width: 700px) {
    .course-creation-hero {
        height:auto;
        min-height:0;
        flex-direction:column;
        align-items:stretch;
        justify-content:flex-start;
        gap:.65rem;
        padding:1rem;
    }
    .course-creation-hero .course-wizard-steps {
        width:100%;
        margin:0;
        gap:.35rem;
    }
    .course-creation-hero .home-hero-main {
        flex:0 1 auto;
    }
    .course-creation-hero .course-wizard-step-button {
        gap:.25rem;
        padding:.3rem .45rem;
        font-size:.68rem;
    }
}
/* ── Skills & tags multi-select dropdown ─────────────────────────────── */
.sdd { position: relative; }
.sdd-trigger {
    display: flex; align-items: center; gap: 0.35rem; flex-wrap: wrap;
    min-height: 46px; padding: 0.45rem 2.4rem 0.45rem 0.7rem;
    border: 1.5px solid rgba(32,0,130,0.12); border-radius: 10px;
    background: #fff; cursor: pointer; position: relative;
    transition: border-color .15s, box-shadow .15s;
}
.sdd-trigger:hover { border-color: rgba(32,0,130,0.3); }
.sdd-trigger.is-open,
.sdd-trigger:focus-visible {
    border-color: var(--primary); box-shadow: 0 0 0 3px rgba(32,0,130,0.1); outline: none;
}
.sdd-placeholder { color: #9aa3ae; font-size: 0.85rem; padding: 0 0.15rem; }
.sdd-trigger.has-selection .sdd-placeholder { display: none; }
.sdd-chips { display: flex; flex-wrap: wrap; gap: 0.3rem; }
.sdd-chip {
    display: inline-flex; align-items: center; gap: 0.25rem;
    padding: 0.15rem 0.3rem 0.15rem 0.5rem; border-radius: 999px;
    background: rgba(32,0,130,0.08); color: var(--primary);
    font-size: 0.74rem; font-weight: 600; line-height: 1.6; max-width: 100%;
}
.sdd-chip-x {
    border: none; background: transparent; color: var(--primary);
    font-size: 0.9rem; line-height: 1; cursor: pointer; padding: 0 0.15rem;
    opacity: 0.55; border-radius: 50%;
}
.sdd-chip-x:hover { opacity: 1; background: rgba(32,0,130,0.12); }
.sdd-chevron {
    position: absolute; right: 0.85rem; top: 50%; transform: translateY(-50%);
    color: #9aa3ae; font-size: 0.72rem; transition: transform .18s; pointer-events: none;
}
.sdd-trigger.is-open .sdd-chevron { transform: translateY(-50%) rotate(180deg); }
.sdd-panel {
    display: none; position: absolute; top: calc(100% + 6px); left: 0; right: 0;
    z-index: 60; background: #fff; border: 1px solid rgba(32,0,130,0.12);
    border-radius: 12px; box-shadow: 0 16px 40px rgba(32,0,130,0.16); overflow: hidden;
}
.sdd-panel.is-open { display: block; }
.sdd-list { max-height: 240px; overflow: auto; padding: 0.3rem 0; }
.sdd-opt {
    display: flex; align-items: center; gap: 0.55rem; margin: 0;
    padding: 0.5rem 0.85rem; cursor: pointer; font-size: 0.85rem; color: var(--text);
    transition: background .12s;
}
.sdd-opt:hover { background: rgba(32,0,130,0.04); }
.sdd-opt input { accent-color: var(--primary); width: 16px; height: 16px; margin: 0; cursor: pointer; flex-shrink: 0; }
.sdd-opt-name { flex: 1; color: var(--text); }
.sdd-opt input:checked + .sdd-opt-name { color: var(--primary); font-weight: 600; }
.sdd-empty { padding: 1rem; text-align: center; font-size: 0.8rem; color: #9aa3ae; }
.sdd-foot {
    display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;
    padding: 0.5rem 0.85rem; border-top: 1px solid rgba(32,0,130,0.08);
    background: rgba(32,0,130,0.02); font-size: 0.78rem; color: #6c7a89;
}
.sdd-foot button {
    border: none; background: none; color: var(--primary); font-weight: 700;
    font-size: 0.78rem; cursor: pointer; padding: 0;
}
.sdd-foot button:hover { text-decoration: underline; }
</style>
<div class="module-content">
    <!-- Add Module Modal -->
    <div id="add-module-modal" class="modal-overlay" style="display:none; z-index:2000; ">
        <div style="background:#fff; border:1px solid rgba(32, 0, 130, 0.12); border-radius:18px; width:min(500px, 92vw); max-height:80vh; overflow-y:auto; box-shadow:0 18px 45px rgba(32, 0, 130, 0.18);">
            <div style="display:flex; justify-content:space-between; align-items:center; padding:1.25rem 1.5rem; border-bottom:1px solid rgba(32, 0, 130, 0.12); background:linear-gradient(135deg, rgba(32, 0, 130, 0.08), rgba(81, 70, 183, 0.05));">
                <h2 style="margin:0; font-size:1.1rem; color:var(--primary);">Add Module</h2>
                <button type="button" data-close-modal="add-module-modal" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text);">✕</button>
            </div>
            <div style="padding:1.5rem;">
                <form id="add-module-in-modal-form">
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Module Title *</span>
                        <input type="text" name="title" required placeholder="e.g. Introduction to Basics" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;" />
                    </label>
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Status</span>
                        <select name="status" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;">
                            <option value="active" selected>Active</option>
                            <option value="archived">Archived</option>
                        </select>
                    </label>
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Lesson Body</span>
                        <textarea name="content_body" rows="4" placeholder="Write the lesson content here... (HTML supported)" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box; resize:vertical;"></textarea>
                    </label>
                    <div style="display:flex; gap:0.75rem;">
                        <button type="submit" style="flex:1; padding:0.8rem; background:var(--primary); color:var(--surface); border:none; border-radius:8px; cursor:pointer; font-weight:700;">Add Module</button>
                        <button type="button" data-close-modal="add-module-modal" style="flex:1; padding:0.8rem; background:rgba(32, 0, 130, 0.08); color:var(--primary); border:1px solid rgba(32, 0, 130, 0.18); border-radius:8px; cursor:pointer; font-weight:700;">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="add-lesson-modal" class="modal-overlay" style="display:none; z-index:2000; ">
        <div style="background:#fff; border:1px solid rgba(32, 0, 130, 0.12); border-radius:18px; width:min(500px, 92vw); max-height:80vh; overflow-y:auto; box-shadow:0 18px 45px rgba(32, 0, 130, 0.18);">
            <div style="display:flex; justify-content:space-between; align-items:center; padding:1.25rem 1.5rem; border-bottom:1px solid rgba(32, 0, 130, 0.12); background:linear-gradient(135deg, rgba(32, 0, 130, 0.08), rgba(81, 70, 183, 0.05));">
                <h2 style="margin:0; font-size:1.1rem; color:var(--primary);">Add Lesson</h2>
                <button type="button" data-close-modal="add-lesson-modal" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text);">✕</button>
            </div>
            <div style="padding:1.5rem;">
                <form id="add-lesson-in-modal-form">
                    <input type="hidden" name="module_id" id="add-lesson-module-id" />
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Lesson Title *</span>
                        <input type="text" name="title" required placeholder="e.g. Introduction to Basics" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;" />
                    </label>
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Content Type</span>
                        <select name="content_type" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;">
                            <option value="text" selected>Text</option>
                            <option value="video">Video</option>
                            <option value="file">File</option>
                            <option value="mixed">Mixed</option>
                        </select>
                    </label>
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Status</span>
                        <select name="status" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;">
                            <option value="active" selected>Active</option>
                            <option value="archived">Archived</option>
                        </select>
                    </label>
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Lesson Body</span>
                        <textarea name="content_body" rows="4" placeholder="Write the lesson content here... (HTML supported)" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box; resize:vertical;"></textarea>
                    </label>
                    <div style="display:flex; gap:0.75rem;">
                        <button type="submit" style="flex:1; padding:0.8rem; background:var(--primary); color:var(--surface); border:none; border-radius:8px; cursor:pointer; font-weight:700;">Add Lesson</button>
                        <button type="button" data-close-modal="add-lesson-modal" style="flex:1; padding:0.8rem; background:rgba(32, 0, 130, 0.08); color:var(--primary); border:1px solid rgba(32, 0, 130, 0.18); border-radius:8px; cursor:pointer; font-weight:700;">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="add-quiz-modal" class="modal-overlay" style="display:none; z-index:2000; ">
        <div style="background:#fff; border:1px solid rgba(32, 0, 130, 0.12); border-radius:18px; width:min(500px, 92vw); max-height:80vh; overflow-y:auto; box-shadow:0 18px 45px rgba(32, 0, 130, 0.18);">
            <div style="display:flex; justify-content:space-between; align-items:center; padding:1.25rem 1.5rem; border-bottom:1px solid rgba(32, 0, 130, 0.12); background:linear-gradient(135deg, rgba(32, 0, 130, 0.08), rgba(81, 70, 183, 0.05));">
                <h2 style="margin:0; font-size:1.1rem; color:var(--primary);">Add Quiz</h2>
                <button type="button" data-close-modal="add-quiz-modal" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text);">✕</button>
            </div>
            <div style="padding:1.5rem;">
                <form id="add-quiz-in-modal-form">
                    <input type="hidden" name="module_id" id="add-quiz-module-id" />
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Quiz Title *</span>
                        <input type="text" name="title" required placeholder="e.g. Final Knowledge Check" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;" />
                    </label>
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Duration (minutes)</span>
                        <input type="number" name="duration_minutes" min="1" step="0.01" value="10" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;" />
                    </label>
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Passing score</span>
                        <input type="number" name="passing_score" min="0" max="100" step="0.01" value="75" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;" />
                    </label>
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Max attempts</span>
                        <input type="number" name="max_attempts" min="1" value="2" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;" />
                    </label>
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Question count</span>
                        <input type="number" name="question_count" min="1" value="10" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;" />
                    </label>
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Status</span>
                        <select name="status" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;">
                            <option value="active" selected>Active</option>
                            <option value="archived">Archived</option>
                        </select>
                    </label>
                    <label style="display:flex; align-items:center; gap:0.6rem; margin-bottom:1.5rem;">
                        <input type="checkbox" name="show_answers_after_submit" value="1" />
                        <span>Show answers after submit</span>
                    </label>
                    <div style="display:flex; gap:0.75rem;">
                        <button type="submit" style="flex:1; padding:0.8rem; background:var(--primary); color:var(--surface); border:none; border-radius:8px; cursor:pointer; font-weight:700;">Add Quiz</button>
                        <button type="button" data-close-modal="add-quiz-modal" style="flex:1; padding:0.8rem; background:rgba(32, 0, 130, 0.08); color:var(--primary); border:1px solid rgba(32, 0, 130, 0.18); border-radius:8px; cursor:pointer; font-weight:700;">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="add-evaluation-modal" class="modal-overlay" style="display:none; z-index:2000; ">
        <div style="background:#fff; border:1px solid rgba(32, 0, 130, 0.12); border-radius:18px; width:min(500px, 92vw); max-height:80vh; overflow-y:auto; box-shadow:0 18px 45px rgba(32, 0, 130, 0.18);">
            <div style="display:flex; justify-content:space-between; align-items:center; padding:1.25rem 1.5rem; border-bottom:1px solid rgba(32, 0, 130, 0.12); background:linear-gradient(135deg, rgba(32, 0, 130, 0.08), rgba(81, 70, 183, 0.05));">
                <h2 style="margin:0; font-size:1.1rem; color:var(--primary);">Add Evaluation</h2>
                <button type="button" data-close-modal="add-evaluation-modal" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text);">✕</button>
            </div>
            <div style="padding:1.5rem;">
                <form id="add-evaluation-in-modal-form">
                    <input type="hidden" name="course_id" id="add-evaluation-course-id" />
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Evaluation Title *</span>
                        <input type="text" name="title" required placeholder="e.g. Final Course Assessment" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;" />
                    </label>
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Duration (minutes)</span>
                        <input type="number" name="duration_minutes" min="0" step="0.01" value="30" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;" />
                    </label>
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Passing score</span>
                        <input type="number" name="passing_score" min="0" max="100" step="0.01" value="75" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;" />
                    </label>
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Max attempts</span>
                        <input type="number" name="max_attempts" min="1" value="2" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;" />
                    </label>
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Question count</span>
                        <input type="number" name="question_count" min="1" value="10" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;" />
                    </label>
                    <label style="display:block; margin-bottom:1rem;">
                        <span style="display:block; margin-bottom:0.35rem; color:var(--primary); font-weight:600;">Status</span>
                        <select name="status" style="width:100%; padding:0.8rem; border-radius:8px; border:1px solid var(--border); box-sizing:border-box;">
                            <option value="active" selected>Active</option>
                            <option value="archived">Archived</option>
                        </select>
                    </label>
                    <label style="display:flex; align-items:center; gap:0.6rem; margin-bottom:1.5rem;">
                        <input type="checkbox" name="show_answers_after_submit" value="1" />
                        <span>Show answers after submit</span>
                    </label>
                    <div style="display:flex; gap:0.75rem;">
                        <button type="submit" style="flex:1; padding:0.8rem; background:var(--primary); color:var(--surface); border:none; border-radius:8px; cursor:pointer; font-weight:700;">Add Evaluation</button>
                        <button type="button" data-close-modal="add-evaluation-modal" style="flex:1; padding:0.8rem; background:rgba(32, 0, 130, 0.08); color:var(--primary); border:1px solid rgba(32, 0, 130, 0.18); border-radius:8px; cursor:pointer; font-weight:700;">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php if (AIAuthoring::canUseCourseChat()): ?>
            <button type="button" class="course-ai-chat-launcher" data-course-ai-chat-open aria-controls="course-ai-chat" aria-expanded="false">
                <i class="fas fa-comments" aria-hidden="true"></i>
                <span>Chat for suggestions</span>
            </button>
            <section class="course-ai-chat" id="course-ai-chat" aria-label="Course suggestion chat" data-endpoint="pages/admin/ai-assistant/ajax/course-chat.php" hidden>
                <header class="course-ai-chat-header">
                    <div>
                        <strong><i class="fas fa-wand-magic-sparkles" aria-hidden="true"></i> Course assistant</strong>
                        <p>Ask for suggestions as you build your course.</p>
                    </div>
                    <button type="button" class="course-ai-chat-close" data-course-ai-chat-close aria-label="Close course assistant"><i class="fas fa-times" aria-hidden="true"></i></button>
                </header>
                <div class="course-ai-chat-messages" data-course-ai-chat-messages role="log" aria-live="polite" aria-relevant="additions text">
                    <div class="course-ai-chat-message is-assistant">Hi! I can suggest course titles, learning outcomes, module ideas, lesson activities, and assessment questions. What would you like help with?</div>
                </div>
                <p class="course-ai-chat-status" data-course-ai-chat-status role="status" aria-live="polite"></p>
                <form class="course-ai-chat-form" data-course-ai-chat-form data-skip="true">
                    <label class="course-ai-chat-sr-only" for="course-ai-chat-input">Ask the course assistant</label>
                    <textarea id="course-ai-chat-input" name="question" rows="2" maxlength="1200" placeholder="Ask for a suggestion..." required></textarea>
                    <button type="submit" class="course-ai-chat-send" aria-label="Send message"><i class="fas fa-paper-plane" aria-hidden="true"></i></button>
                </form>
                <p class="course-ai-chat-notice">Your current course details and messages are sent to the configured AI provider. Chat is not saved with the course; do not include sensitive or personal information.</p>
            </section>
    <?php endif; ?>

    <form id="course-form" data-skip="true" method="post" enctype="multipart/form-data" novalidate action="pages/instructor/elearning-subpage/ajax/add-course.php">
        <div class="course-wizard" id="course-wizard">
            <div style="margin-bottom:1rem;">
                <?= BackLink::anchor('instructor/training', 'style="display:inline-flex; align-items:center; gap:0.4rem; padding:0.5rem 1rem; background:var(--primary); color:#fff; border:none; border-radius:8px; text-decoration:none; font-size:0.85rem; font-weight:600; white-space:nowrap;"') ?>
            </div>
            <section class="home-hero course-creation-hero" aria-label="Course creation steps">
                    <div class="home-hero-main">
                        <h1 id="form-title">Add Course</h1>
                        <p>Create a course through four guided steps, from the basics to final review.</p>
                    </div>
                    <div class="course-wizard-steps" role="tablist" aria-label="Course creation steps">
                        <button type="button" class="course-wizard-step-button is-current" role="tab" id="course-step-tab-0" aria-label="1. Details" aria-controls="course-step-panel-0" aria-selected="true" data-course-step-target="0">
                            <i class="fas fa-circle-info" aria-hidden="true"></i> Details
                        </button>
                        <button type="button" class="course-wizard-step-button" role="tab" id="course-step-tab-1" aria-label="2. Content" aria-controls="course-step-panel-1" aria-selected="false" data-course-step-target="1" disabled>
                            <i class="fas fa-cubes" aria-hidden="true"></i> Content
                        </button>
                        <button type="button" class="course-wizard-step-button" role="tab" id="course-step-tab-2" aria-label="3. Evaluation" aria-controls="course-step-panel-2" aria-selected="false" data-course-step-target="2" disabled>
                            <i class="fas fa-clipboard-check" aria-hidden="true"></i> Evaluation
                        </button>
                        <button type="button" class="course-wizard-step-button" role="tab" id="course-step-tab-3" aria-label="4. Review" aria-controls="course-step-panel-3" aria-selected="false" data-course-step-target="3" disabled>
                            <i class="fas fa-eye" aria-hidden="true"></i> Review
                        </button>
                    </div>
            </section>
            <div class="mode-card course-creation-card" style="margin-top:0;">
                <div class="course-wizard-progress" role="progressbar" aria-label="Course creation progress" aria-valuemin="1" aria-valuemax="4" aria-valuenow="1">
                    <span id="course-wizard-progress-fill"></span>
                </div>
                <p id="course-wizard-error" class="course-wizard-error" role="alert"></p>

            <!-- Section 1: Basic Info -->
            <section class="course-wizard-panel is-active" id="course-step-panel-0" role="tabpanel" aria-labelledby="course-step-tab-0" tabindex="-1" data-course-step-panel="0">
                <h3>Course details</h3>
                <p class="course-wizard-help">Start with the course identity and settings. The title is required; the remaining details can be filled in now or later.</p>
            <div style="margin-top:1.5rem; padding-bottom:1rem; border-bottom:1px solid rgba(32,0,130,0.08);">
                <h3 style="margin:0 0 0.75rem 0; font-size:0.9rem; color:var(--primary); text-transform:uppercase; letter-spacing:0.05em; display:flex; align-items:center; gap:0.5rem;">
                    <i class="fas fa-info-circle"></i> Basic Information
                </h3>
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1rem;">
                    <label>
                        <span>Course title *</span>
                        <input type="text" name="title" required placeholder="e.g. Advanced SQL Mastery" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" />
                    </label>
                    <label>
                        <span>Category</span>
                        <input type="text" name="category" list="category-list" placeholder="e.g. Data & Analytics" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" />
                        <datalist id="category-list">
                            <option value="Programming"><option value="Data & Analytics"><option value="Business"><option value="Design & Creative"><option value="Soft Skills"><option value="Compliance & Safety"><option value="Leadership"><option value="Technology"><option value="Marketing"><option value="Finance">
                        </datalist>
                    </label>
                    <label>
                        <span>Difficulty</span>
                        <select name="difficulty" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);">
                            <option value="beginner" selected>Beginner</option>
                            <option value="intermediate">Intermediate</option>
                            <option value="advanced">Advanced</option>
                            <option value="expert">Expert</option>
                        </select>
                    </label>
                    <label>
                        <span>Status</span>
                        <select name="status" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);">
                            <option value="draft">Draft</option>
                            <option value="active" selected>Active</option>
                            <option value="archived">Archived</option>
                        </select>
                    </label>
                    <label>
                        <span>Delivery Mode</span>
                        <select name="delivery_mode" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);">
                            <option value="online" selected>Online</option>
                            <option value="face_to_face">Face-to-Face</option>
                            <option value="hybrid">Hybrid</option>
                        </select>
                    </label>
                </div>
            </div>

            <!-- Section 2: Schedule -->
            <div style="margin-top:1rem; padding-bottom:1rem; border-bottom:1px solid rgba(32,0,130,0.08);">
                <h3 style="margin:0 0 0.75rem 0; font-size:0.9rem; color:var(--primary); text-transform:uppercase; letter-spacing:0.05em; display:flex; align-items:center; gap:0.5rem;">
                    <i class="fas fa-calendar-alt"></i> Schedule & Duration
                </h3>
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:1rem;">
                    <label><span>Start date</span><input type="date" name="start_date" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" /></label>
                    <label><span>Enrollment deadline</span><input type="date" name="enrollment_deadline" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" /></label>
                    <label><span>Estimated hours</span><input type="number" name="estimated_hours" min="0" step="0.5" placeholder="e.g. 12" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" /></label>
                    <label><span>Max trainees</span><input type="number" name="max_learners" min="0" placeholder="0 = unlimited" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" /></label>
                </div>
            </div>

            <!-- Section 3: Cover Photo -->
            <div style="margin-top:1rem; padding-bottom:1rem; border-bottom:1px solid rgba(32,0,130,0.08);">
                <h3 style="margin:0 0 0.75rem 0; font-size:0.9rem; color:var(--primary); text-transform:uppercase; letter-spacing:0.05em; display:flex; align-items:center; gap:0.5rem;">
                    <i class="fas fa-image"></i> Cover Photo
                </h3>
                <div id="course-thumb-dropzone" style="border:2px dashed rgba(32,0,130,0.2); border-radius:12px; padding:2rem; display:flex; flex-direction:column; align-items:center; justify-content:center; cursor:pointer; transition:all 0.2s; background:rgba(32,0,130,0.02);" onclick="document.querySelector('input[name=cover_photo]').click()">
                    <i class="fas fa-cloud-upload-alt" style="font-size:2rem; color:rgba(32,0,130,0.25); margin-bottom:0.5rem; display:block;"></i>
                    <p style="margin:0; color:var(--text); font-size:0.85rem;">Drag & drop or <strong style="color:var(--primary);">browse</strong></p>
                    <p style="margin:0.25rem 0 0; color:#999; font-size:0.75rem;">PNG, JPG, WEBP up to 5MB</p>
                </div>
                <input type="file" name="cover_photo" accept="image/*" style="display:none;" onchange="showCoverPreview(this)" />
                <div id="cover-preview" style="display:none; margin-top:0.75rem; position:relative; border-radius:12px; overflow:hidden; max-height:200px;">
                    <img id="cover-preview-img" style="width:100%; height:200px; object-fit:cover; border-radius:12px;" />
                    <button type="button" onclick="document.getElementById('cover-preview').style.display='none'; document.getElementById('course-thumb-dropzone').style.display='block'; this.closest('form').querySelector('input[name=cover_photo]').value='';" style="position:absolute; top:8px; right:8px; width:28px; height:28px; border-radius:50%; background:rgba(0,0,0,0.6); color:#fff; border:none; cursor:pointer; font-size:0.8rem; display:flex; align-items:center; justify-content:center;"><i class="fas fa-times"></i></button>
                </div>
            </div>

            <!-- Section 4: Skills -->
            <div style="margin-top:1rem; padding-bottom:1rem; border-bottom:1px solid rgba(32,0,130,0.08);">
                <h3 style="margin:0 0 0.75rem 0; font-size:0.9rem; color:var(--primary); text-transform:uppercase; letter-spacing:0.05em; display:flex; align-items:center; gap:0.5rem;">
                    <i class="fas fa-cogs"></i> Skills &amp; Tags
                </h3>
                <p style="color:#999; font-size:0.82rem; margin:0 0 0.75rem 0;">Select skill tags to make this course easier to find in the catalog.</p>
                <div class="sdd" id="skill-dropdown-wrap">
                    <div class="sdd-trigger" id="skill-dropdown-btn" role="button" tabindex="0"
                         aria-haspopup="listbox" aria-expanded="false" aria-controls="skill-dropdown-panel">
                        <span class="sdd-placeholder" id="skill-dropdown-placeholder">Select skills &amp; tags</span>
                        <span class="sdd-chips" id="skill-dropdown-chips"></span>
                        <span class="sdd-chevron" id="skill-dropdown-chevron"><i class="fas fa-chevron-down"></i></span>
                    </div>
                    <div class="sdd-panel" id="skill-dropdown-panel" role="listbox" aria-multiselectable="true" aria-label="Skills and tags">
                        <div class="sdd-list" id="skill-dropdown-list">
                            <?php foreach ($allSkills as $sk): ?>
                            <label class="sdd-opt" for="skill-cb-<?= (int)$sk['id'] ?>">
                                <input type="checkbox"
                                       name="skill_ids[]"
                                       value="<?= (int)$sk['id'] ?>"
                                       id="skill-cb-<?= (int)$sk['id'] ?>"
                                       data-name="<?= htmlspecialchars($sk['name'], ENT_QUOTES, 'UTF-8') ?>">
                                <span class="sdd-opt-name"><?= htmlspecialchars($sk['name'], ENT_QUOTES, 'UTF-8') ?></span>
                            </label>
                            <?php endforeach; ?>
                            <?php if (empty($allSkills)): ?>
                            <div class="sdd-empty">No skills available yet.</div>
                            <?php endif; ?>
                        </div>
                        <div class="sdd-foot">
                            <span id="skill-dropdown-count">0 selected</span>
                            <button type="button" id="skill-select-all-btn">Select all</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 5: Description -->
            <div style="margin-top:1rem; padding-bottom:1rem; border-bottom:1px solid rgba(32,0,130,0.08);">
                <h3 style="margin:0 0 0.75rem 0; font-size:0.9rem; color:var(--primary); text-transform:uppercase; letter-spacing:0.05em; display:flex; align-items:center; gap:0.5rem;">
                    <i class="fas fa-align-left"></i> Description & Outcomes
                </h3>
                <label style="display:block; margin-bottom:0.75rem;">
                    <span>Course description</span>
                    <textarea name="description" rows="5" placeholder="Describe the course objectives, learning outcomes, and target audience..." style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border); resize:vertical;"></textarea>
                </label>
                <label style="display:block;">
                    <span>Prerequisites</span>
                    <textarea name="prerequisites" rows="2" placeholder="What should trainees know before taking this course? (optional)" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border); resize:vertical;"></textarea>
                </label>
            </div>
            </section>

            <!-- Section 6: Modules (Optional) -->
            <section class="course-wizard-panel" id="course-step-panel-1" role="tabpanel" aria-labelledby="course-step-tab-1" tabindex="-1" data-course-step-panel="1">
                <h3>Build the course content</h3>
                <p class="course-wizard-help">Add at least one module with a title and at least one lesson or quiz before moving on. You can add or edit the lesson details in the module builder.</p>
            <div style="margin-top:1rem; padding-bottom:1rem; border-bottom:1px solid rgba(32,0,130,0.08);">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:0.75rem; flex-wrap:wrap; margin-bottom:0.75rem;">
                    <h3 style="margin:0; font-size:0.9rem; color:var(--primary); text-transform:uppercase; letter-spacing:0.05em; display:flex; align-items:center; gap:0.5rem;">
                        <i class="fas fa-cubes"></i> Modules & Content
                    </h3>
                    <button type="button" id="add-module-btn" style="padding:0.6rem 1.2rem; background:var(--primary); color:white; border:none; border-radius:8px; cursor:pointer; font-weight:600; font-size:0.85rem; white-space:nowrap;"><i class="fas fa-plus" style="margin-right:0.3rem;"></i> Add Module</button>
                </div>
                <p style="color:#999; font-size:0.82rem; margin:0 0 0.75rem 0;">Add modules and lessons. Quizzes are added inside lessons, and the final evaluation is managed at course level.</p>
                <div id="modules-list-container">
                    <div style="text-align:center; padding:2rem; color:#999; border:2px dashed rgba(32,0,130,0.1); border-radius:10px;">
                        <i class="fas fa-cubes" style="font-size:2rem; color:rgba(32,0,130,0.15); margin-bottom:0.5rem; display:block;"></i>
                        <p style="margin:0;">No modules added yet. Click <strong>"Add Module"</strong> to build course content.</p>
                    </div>
                </div>
            </div>
            </section>

            <!-- Section 7: Evaluation (Optional) -->
            <section class="course-wizard-panel" id="course-step-panel-2" role="tabpanel" aria-labelledby="course-step-tab-2" tabindex="-1" data-course-step-panel="2">
                <h3>Set up an evaluation</h3>
                <p class="course-wizard-help">A final evaluation is optional. Add one now, or continue to review and save your course without it.</p>
            <div style="margin-top:1rem; padding-bottom:1rem;">
                <h3 style="margin:0 0 0.75rem 0; font-size:0.9rem; color:var(--primary); text-transform:uppercase; letter-spacing:0.05em; display:flex; align-items:center; gap:0.5rem;">
                    <i class="fas fa-clipboard-check"></i> Course Evaluation <span style="font-size:0.75rem; font-weight:400; color:var(--muted); text-transform:none; letter-spacing:0;">(optional)</span>
                </h3>
                <p style="color:#999; font-size:0.82rem; margin:0 0 0.75rem 0;">Add a final evaluation taken after all course content is completed.</p>
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1rem; margin-top:0.5rem;">
                    <label><span>Evaluation title</span><input type="text" name="eval_title" data-eval-field="title" placeholder="e.g. Final Assessment" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" /></label>
                    <label><span>Duration (minutes)</span><input type="number" name="eval_duration_minutes" data-eval-field="duration_minutes" placeholder="e.g. 30" min="1" step="0.01" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" /></label>
                    <label><span>Passing score (%)</span><input type="number" name="eval_passing_score" data-eval-field="passing_score" placeholder="e.g. 70" min="0" max="100" step="0.5" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" /></label>
                    <label><span>Max attempts</span><input type="number" name="eval_max_attempts" data-eval-field="max_attempts" value="2" min="1" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" /></label>
                    <label><span>Question count</span><input type="number" name="eval_question_count" data-eval-field="question_count" placeholder="Leave empty for all" min="1" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" /></label>
                    <label><span>Status</span><select name="eval_status" data-eval-field="status" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);"><option value="active" selected>Active</option><option value="archived">Archived</option></select></label>
                </div>
                <label style="display:flex; align-items:center; gap:0.5rem; margin-top:0.75rem;">
                    <input type="checkbox" name="eval_show_answers" data-eval-field="show_answers_after_submit" />
                    <span style="font-size:0.85rem;">Show answers after submit</span>
                </label>
                <label style="display:block; margin-top:0.75rem;">
                    <span>Evaluation description</span>
                    <textarea name="eval_description" data-eval-field="description" rows="3" placeholder="Evaluation purpose and instructions..." style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border); resize:vertical;"></textarea>
                </label>

                <div style="margin-top:0.9rem; padding-top:0.9rem; border-top:1px dashed rgba(32,0,130,0.15);">
                    <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap; margin-bottom:0.4rem;">
                        <span style="font-size:0.82rem; font-weight:600; color:var(--primary);"><i class="fas fa-question-circle"></i> Evaluation Questions</span>
                        <span style="font-size:0.75rem; color:#999;">question, type, correct answer</span>
                    </div>
                    <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                        <button type="button" class="qq-toggle" data-quiz-id="_eval_" style="padding:0.35rem 0.7rem; background:rgba(32,0,130,0.08); color:var(--primary); border:1px solid rgba(32,0,130,0.18); border-radius:999px; cursor:pointer; font-size:0.78rem; font-weight:600;"><i class="fas fa-question-circle" style="margin-right:0.25rem;"></i>Questions (<span class="qq-count" id="eval-qq-count">0</span>)</button>
                        <button type="button" class="qq-add" data-quiz-id="_eval_" style="padding:0.35rem 0.7rem; background:#f59e0b; color:#fff; border:none; border-radius:999px; cursor:pointer; font-size:0.78rem; font-weight:600;"><i class="fas fa-plus" style="margin-right:0.25rem;"></i>Add Question</button>
                    </div>
                    <div class="qq-area" data-quiz-id="_eval_" id="eval-questions-area" style="display:none; margin-top:0.5rem; border:1.5px dashed rgba(245,158,11,0.5); background:#fffdf7; border-radius:10px; padding:0.75rem;">
                        <p style="margin:0; color:#999; font-size:0.8rem; text-align:center; padding:0.5rem;">No questions yet &mdash; click &quot;Add Question&quot;.</p>
                    </div>
                </div>
            </div>

            <!-- Prerequisites Section (only in edit mode) -->
            <div id="prerequisites-section" style="display:none; margin-top:2rem; padding-top:1.5rem; border-top:1px solid rgba(32,0,130,0.12);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                    <div>
                        <h3 style="margin:0; font-size:1.1rem; color:var(--text);">Prerequisites</h3>
                        <p style="margin:0.25rem 0 0; font-size:0.85rem; color:#999;">Trainees must complete these before enrolling in this course.</p>
                    </div>
                    <button type="button" id="add-prereq-btn" style="padding:0.5rem 1rem; background:var(--primary); color:var(--surface); border:none; border-radius:8px; cursor:pointer; font-weight:600; font-size:0.85rem;"><i class="fas fa-plus" style="margin-right:0.3rem;"></i> Add</button>
                </div>
                <div id="prerequisites-list" style="display:grid; gap:0.5rem;"></div>
                <!-- Add Prerequisite Modal -->
                <div id="prereq-modal" class="modal-overlay" style="display:none; z-index:2000;">
                    <div style="background:var(--surface,#fff); border:1px solid rgba(32,0,130,0.12); border-radius:18px; width:min(450px,92vw); box-shadow:0 18px 45px rgba(32,0,130,0.18);">
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:1.25rem 1.5rem; border-bottom:1px solid rgba(32,0,130,0.08);">
                            <h3 style="margin:0; font-size:1.1rem; color:var(--text);">Add Prerequisite</h3>
                            <button onclick="document.getElementById('prereq-modal').style.display='none'" style="background:none; border:none; font-size:1.2rem; cursor:pointer; color:var(--text);">&times;</button>
                        </div>
                        <div style="padding:1.5rem;">
                            <label style="display:block; margin-bottom:1rem;">
                                <span style="display:block; font-weight:600; color:var(--primary); margin-bottom:0.3rem; font-size:0.85rem;">Type</span>
                                <select id="prereq-type" style="width:100%; padding:0.7rem; border:1px solid var(--border); border-radius:8px;">
                                    <option value="course">Required Course</option>
                                    <option value="skill">Required Skill</option>
                                </select>
                            </label>
                            <label id="prereq-course-label" style="display:block; margin-bottom:1rem;">
                                <span style="display:block; font-weight:600; color:var(--primary); margin-bottom:0.3rem; font-size:0.85rem;">Course</span>
                                <select id="prereq-course-id" style="width:100%; padding:0.7rem; border:1px solid var(--border); border-radius:8px;"></select>
                            </label>
                            <label id="prereq-skill-label" style="display:none; margin-bottom:1rem;">
                                <span style="display:block; font-weight:600; color:var(--primary); margin-bottom:0.3rem; font-size:0.85rem;">Skill</span>
                                <select id="prereq-skill-id" style="width:100%; padding:0.7rem; border:1px solid var(--border); border-radius:8px;"></select>
                            </label>
                            <div style="display:flex; gap:0.75rem;">
                                <button type="button" id="save-prereq-btn" style="flex:1; padding:0.7rem; background:var(--primary); color:var(--surface); border:none; border-radius:8px; cursor:pointer; font-weight:700;">Add Prerequisite</button>
                                <button type="button" onclick="document.getElementById('prereq-modal').style.display='none'" style="flex:1; padding:0.7rem; background:rgba(32,0,130,0.08); color:var(--primary); border:1px solid rgba(32,0,130,0.18); border-radius:8px; cursor:pointer; font-weight:700;">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </section>

            <!-- Form Summary -->
            <section class="course-wizard-panel" id="course-step-panel-3" role="tabpanel" aria-labelledby="course-step-tab-3" tabindex="-1" data-course-step-panel="3">
                <h3>Review &amp; save</h3>
                <p class="course-wizard-help">Check the course summary below before saving. You can return to the course structure builder to make further changes afterward.</p>
                <div class="course-wizard-review" aria-live="polite">
                    <div><span>Course title</span><strong id="course-review-title">Untitled</strong></div>
                    <div><span>Category</span><strong id="course-review-category">Not set</strong></div>
                    <div><span>Modules</span><strong id="course-review-modules">0</strong></div>
                    <div><span>Final evaluation</span><strong id="course-review-evaluation">Not added</strong></div>
                </div>
            <div id="form-summary" style="display:none; padding:0.75rem 1rem; background:rgba(32,0,130,0.03); border:1px solid rgba(32,0,130,0.08); border-radius:10px; margin-top:0.5rem; font-size:0.82rem; color:var(--text);">
                <span id="summary-text"></span>
            </div>

            <!-- Submit -->
            <div class="mode-actions" style="margin-top:1.5rem; display:flex; gap:0.75rem; align-items:center;">
                <button type="button" onclick="previewCourse()" style="padding:0.7rem 1.5rem; background:transparent; color:var(--primary); border:1.5px solid rgba(32,0,130,0.2); border-radius:10px; cursor:pointer; font-weight:600; font-size:0.9rem;"><i class="fas fa-eye" style="margin-right:0.4rem;"></i> Preview</button>
                <button type="submit" class="mode-button" id="save-course-btn"><i class="fas fa-save" style="margin-right:0.4rem;"></i> <span id="save-btn-text">Save Course</span></button>
                <span id="form-status" style="font-size:0.78rem; color:#999; margin-left:0.5rem;"></span>
            </div>
            </section>
                <div class="course-wizard-navigation">
                    <button type="button" id="course-wizard-back" hidden><i class="fas fa-arrow-left" aria-hidden="true"></i> Back</button>
                    <span id="course-wizard-step-label" aria-live="polite">Step 1 of 4</span>
                    <button type="button" id="course-wizard-next" class="course-wizard-next">Next: Course content <i class="fas fa-arrow-right" aria-hidden="true"></i></button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Course Detail Modal -->
<div id="course-modal" class="modal-overlay" style="display:none; z-index:10000;">
    <div style="background:var(--surface, #fff); border-radius:10px; max-width:600px; width:90%; max-height:80vh; overflow:auto; box-shadow:0 4px 20px rgba(0,0,0,0.3);">
        <div style="display:flex; justify-content:space-between; align-items:center; padding:1.5rem; border-bottom:1px solid var(--border); background:#f9f9f9;">
            <h2 id="modal-title" style="margin:0; font-size:1.3rem;"></h2>
            <div style="display:flex; gap:0.5rem;">
                <button id="modal-edit-btn" style="padding:0.5rem 1rem; background:var(--primary); color:white; border:none; border-radius:6px; cursor:pointer; font-weight:500;">Edit</button>
                <button id="modal-archive-btn" style="padding:0.5rem 1rem; background:#ff9800; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:500;">Archive</button>
                <button id="modal-close-btn" style="padding:0.5rem 1rem; background:#ccc; color:#333; border:none; border-radius:6px; cursor:pointer; font-weight:500;">Back</button>
            </div>
        </div>
        <div style="padding:1.5rem;">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1.5rem;">
                <div><label style="color:var(--muted); font-weight:500; font-size:0.9rem;">Category</label><p id="modal-category" style="margin:0.5rem 0 0;"></p></div>
                <div><label style="color:var(--muted); font-weight:500; font-size:0.9rem;">Status</label><p id="modal-status" style="margin:0.5rem 0 0;"></p></div>
                <div><label style="color:var(--muted); font-weight:500; font-size:0.9rem;">Start Date</label><p id="modal-start-date" style="margin:0.5rem 0 0;"></p></div>
                <div><label style="color:var(--muted); font-weight:500; font-size:0.9rem;">Enrollment Deadline</label><p id="modal-deadline" style="margin:0.5rem 0 0;"></p></div>
            </div>
            <div><label style="color:var(--muted); font-weight:500; font-size:0.9rem;">Description</label><p id="modal-description" style="margin:0.5rem 0 0; line-height:1.5;"></p></div>
        </div>
    </div>
</div>

<script src="js/course-builder.js"></script>
<script src="js/course-ai-chat.js?v=<?= (int) filemtime(dirname(__DIR__, 3) . '/js/course-ai-chat.js') ?>"></script>
<script>
(function(){
    var wrap = document.getElementById('skill-dropdown-wrap');
    if (!wrap) return;
    var trigger = document.getElementById('skill-dropdown-btn');
    var panel = document.getElementById('skill-dropdown-panel');
    var chipsEl = document.getElementById('skill-dropdown-chips');
    var countEl = document.getElementById('skill-dropdown-count');
    var list = document.getElementById('skill-dropdown-list');
    var selectAllBtn = document.getElementById('skill-select-all-btn');
    if (!trigger || !panel || !list || !chipsEl || !countEl || !selectAllBtn) return;

    var boxes = Array.prototype.slice.call(list.querySelectorAll('input[type="checkbox"]'));

    function checkedBoxes() {
        return boxes.filter(function (b) { return b.checked; });
    }

    function renderSelection() {
        var sel = checkedBoxes();

        chipsEl.innerHTML = '';
        sel.forEach(function (box) {
            var chip = document.createElement('span');
            chip.className = 'sdd-chip';

            var label = document.createElement('span');
            label.textContent = box.getAttribute('data-name') || box.value;
            chip.appendChild(label);

            var x = document.createElement('button');
            x.type = 'button';
            x.className = 'sdd-chip-x';
            x.setAttribute('aria-label', 'Remove ' + label.textContent);
            x.innerHTML = '&times;';
            x.addEventListener('click', function (ev) {
                ev.preventDefault();
                ev.stopPropagation();
                box.checked = false;
                renderSelection();
            });
            chip.appendChild(x);

            chipsEl.appendChild(chip);
        });

        trigger.classList.toggle('has-selection', sel.length > 0);
        countEl.textContent = sel.length + ' selected';

        var allSelected = boxes.length > 0 && sel.length === boxes.length;
        selectAllBtn.textContent = allSelected ? 'Deselect all' : 'Select all';
    }

    function isOpen() {
        return panel.classList.contains('is-open');
    }

    function setOpen(open) {
        panel.classList.toggle('is-open', open);
        trigger.classList.toggle('is-open', open);
        trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) {
            var first = list.querySelector('input[type="checkbox"]');
            if (first && first.focus) { /* keep focus on trigger */ }
        }
    }

    trigger.addEventListener('click', function (e) {
        if (e.target.closest && e.target.closest('.sdd-chip')) return;
        setOpen(!isOpen());
    });

    trigger.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
            e.preventDefault();
            setOpen(true);
        } else if (e.key === 'Escape') {
            setOpen(false);
        }
    });

    boxes.forEach(function (box) {
        box.addEventListener('change', renderSelection);
    });

    selectAllBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        var allSelected = boxes.length > 0 && boxes.every(function (b) { return b.checked; });
        boxes.forEach(function (b) { b.checked = !allSelected; });
        renderSelection();
    });

    panel.addEventListener('click', function (e) {
        e.stopPropagation();
    });

    document.addEventListener('click', function (e) {
        if (!wrap.contains(e.target)) setOpen(false);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') setOpen(false);
    });

    // Close the dropdown before submitting the course
    var form = document.getElementById('course-form');
    if (form) {
        form.addEventListener('submit', function () {
            setOpen(false);
            // keep the count in sync (in case values were restored programmatically)
            renderSelection();
        });
    }

    renderSelection();
})();
</script>
<script>
(function () {
    var form = document.getElementById('course-form');
    var wizard = document.getElementById('course-wizard');
    if (!form || !wizard) return;

    var panels = Array.from(wizard.querySelectorAll('[data-course-step-panel]'));
    var tabs = Array.from(wizard.querySelectorAll('[data-course-step-target]'));
    var backButton = document.getElementById('course-wizard-back');
    var nextButton = document.getElementById('course-wizard-next');
    var stepLabel = document.getElementById('course-wizard-step-label');
    var errorMessage = document.getElementById('course-wizard-error');
    var progress = wizard.querySelector('.course-wizard-progress');
    var progressFill = document.getElementById('course-wizard-progress-fill');
    var nextLabels = ['Course content', 'Evaluation', 'Review & save'];
    var currentStep = 0;
    var unlockedStep = 0;

    function showError(message) {
        errorMessage.textContent = message || '';
        errorMessage.style.display = message ? 'block' : 'none';
    }

    function updateReview() {
        var title = form.querySelector('[name="title"]').value.trim();
        var category = form.querySelector('[name="category"]').value.trim();
        var evaluationTitle = form.querySelector('[name="eval_title"]').value.trim();
        var modules = window.courseBuilder && window.courseBuilder.courseData
            ? window.courseBuilder.courseData.modules || []
            : [];
        document.getElementById('course-review-title').textContent = title || 'Untitled';
        document.getElementById('course-review-category').textContent = category || 'Not set';
        document.getElementById('course-review-modules').textContent = String(modules.length);
        document.getElementById('course-review-evaluation').textContent = evaluationTitle || 'Not added';
    }

    function renderStep(focusPanel) {
        panels.forEach(function (panel, index) {
            var isCurrent = index === currentStep;
            panel.classList.toggle('is-active', isCurrent);
            panel.hidden = !isCurrent;
        });
        tabs.forEach(function (tab, index) {
            var isCurrent = index === currentStep;
            tab.classList.toggle('is-current', isCurrent);
            tab.classList.toggle('is-complete', index < currentStep);
            tab.setAttribute('aria-selected', isCurrent ? 'true' : 'false');
            tab.disabled = index > unlockedStep;
        });
        backButton.hidden = currentStep === 0;
        nextButton.hidden = currentStep === panels.length - 1;
        stepLabel.textContent = 'Step ' + (currentStep + 1) + ' of ' + panels.length;
        nextButton.innerHTML = 'Next: ' + nextLabels[currentStep] + ' <i class="fas fa-arrow-right" aria-hidden="true"></i>';
        progress.setAttribute('aria-valuenow', String(currentStep + 1));
        progressFill.style.width = (((currentStep + 1) / panels.length) * 100) + '%';
        if (currentStep === panels.length - 1) updateReview();
        if (focusPanel) {
            panels[currentStep].focus();
            panels[currentStep].scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    function validateVisibleFields() {
        var fields = panels[currentStep].querySelectorAll('input, select, textarea');
        for (var i = 0; i < fields.length; i++) {
            if (!fields[i].checkValidity()) {
                fields[i].reportValidity();
                return false;
            }
        }
        return true;
    }

    function validateStep() {
        showError('');
        if (!validateVisibleFields()) return false;

        if (currentStep === 0) {
            var title = form.querySelector('[name="title"]');
            if (!title.value.trim()) {
                title.setCustomValidity('Enter a course title to continue.');
                title.reportValidity();
                title.addEventListener('input', function clearTitleError() {
                    title.setCustomValidity('');
                    title.removeEventListener('input', clearTitleError);
                });
                return false;
            }
        }

        if (currentStep === 1) {
            var modules = window.courseBuilder && window.courseBuilder.courseData
                ? window.courseBuilder.courseData.modules || []
                : [];
            if (modules.length === 0) {
                showError('Add at least one module before continuing.');
                return false;
            }
            for (var index = 0; index < modules.length; index++) {
                var module = modules[index];
                if (!String(module.title || '').trim()) {
                    showError('Add a title to every module before continuing.');
                    return false;
                }
                var lessons = module.lessons || [];
                var quizzes = module.quizzes || [];
                if (lessons.length === 0 && quizzes.length === 0) {
                    showError('Add at least one lesson or quiz to each module before continuing.');
                    return false;
                }
                for (var lessonIndex = 0; lessonIndex < lessons.length; lessonIndex++) {
                    var lesson = lessons[lessonIndex];
                    if (!String(lesson.title || '').trim()) {
                        showError('Add a title to every lesson before continuing.');
                        return false;
                    }
                    var lessonQuizzes = lesson.quizzes || [];
                    for (var quizIndex = 0; quizIndex < lessonQuizzes.length; quizIndex++) {
                        if (!String(lessonQuizzes[quizIndex].title || '').trim()) {
                            showError('Add a title to every quiz before continuing.');
                            return false;
                        }
                    }
                }
                for (var moduleQuizIndex = 0; moduleQuizIndex < quizzes.length; moduleQuizIndex++) {
                    if (!String(quizzes[moduleQuizIndex].title || '').trim()) {
                        showError('Add a title to every quiz before continuing.');
                        return false;
                    }
                }
            }
        }
        return true;
    }

    nextButton.addEventListener('click', function () {
        if (!validateStep()) return;
        unlockedStep = Math.max(unlockedStep, currentStep + 1);
        currentStep++;
        renderStep(true);
    });

    backButton.addEventListener('click', function () {
        if (currentStep === 0) return;
        showError('');
        currentStep--;
        renderStep(true);
    });

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            var targetStep = Number(tab.dataset.courseStepTarget);
            if (targetStep > unlockedStep) return;
            showError('');
            currentStep = targetStep;
            renderStep(true);
        });
    });

    form.addEventListener('input', function () {
        if (currentStep === panels.length - 1) updateReview();
    });
    form.addEventListener('change', function () {
        if (currentStep === panels.length - 1) updateReview();
    });
    renderStep(false);
})();
</script>
<script>
function showCoverPreview(input) {
    if (!input.files || !input.files[0]) return;
    var file = input.files[0];
    if (!file.type.startsWith('image/')) return;
    var r = new FileReader();
    r.onload = function(ev) {
        var img = document.getElementById('cover-preview-img');
        var pv = document.getElementById('cover-preview');
        var dz = document.getElementById('course-thumb-dropzone');
        if (img && pv) { img.src = ev.target.result; pv.style.display = 'block'; dz.style.display = 'none'; }
    };
    r.readAsDataURL(file);
}
(function() {
    var f = document.getElementById('course-form'); if (!f) return;
    var s = document.getElementById('form-summary'); var st = document.getElementById('summary-text');
    f.addEventListener('input', update); f.addEventListener('change', update);
    function update() {
        var title = f.querySelector('input[name="title"]').value;
        var cat = f.querySelector('input[name="category"]').value;
        var diff = f.querySelector('select[name="difficulty"]').value;
        var h = f.querySelector('input[name="estimated_hours"]').value;
        var sk = f.querySelectorAll('input[name="skill_ids[]"]:checked').length;
        if (!title && !cat && !sk) { s.style.display = 'none'; return; }
        var p = [];
        if (title) p.push('<strong>'+title+'</strong>');
        if (cat) p.push(cat);
        if (diff) p.push(diff.charAt(0).toUpperCase()+diff.slice(1));
        if (h) p.push(h+'h');
        if (sk) p.push(sk+' skill'+(sk>1?'s':''));
        st.innerHTML = '<i class="fas fa-file-alt" style="margin-right:0.4rem;"></i> '+p.join(' &bull; ');
        s.style.display = 'block';
    }
})();
function previewCourse() {
    var f = document.getElementById('course-form'); if (!f) return;
    var t=f.querySelector('input[name="title"]').value||'Untitled';
    var c=f.querySelector('input[name="category"]').value||'General';
    var d=f.querySelector('select[name="difficulty"]').value;
    var st=f.querySelector('select[name="status"]').value;
    var h=f.querySelector('input[name="estimated_hours"]').value;
    var desc=f.querySelector('textarea[name="description"]').value;
    var sk=[]; f.querySelectorAll('input[name="skill_ids[]"]:checked').forEach(function(b){sk.push(b.parentElement.textContent.trim());});
    var dl={beginner:'Beginner',intermediate:'Intermediate',advanced:'Advanced',expert:'Expert'};
    var html='<div style="position:fixed;inset:0;z-index:10000;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;backdrop-filter:blur(3px);" onclick="if(event.target===this)this.remove()"><div style="background:var(--surface,#fff);border-radius:16px;width:min(600px,92vw);max-height:85vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.3);"><div style="padding:1.5rem 2rem;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;"><h2 style="margin:0;font-size:1.2rem;">Course Preview</h2><button onclick="this.closest(\'div[style*=fixed]\').remove()" style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:#999;"><i class="fas fa-times"></i></button></div><div style="padding:1.5rem 2rem 2rem;"><h3 style="margin:0 0 0.5rem;font-size:1.3rem;">'+t+'</h3><div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:1rem;"><span style="padding:0.25rem 0.6rem;background:rgba(32,0,130,0.08);border-radius:999px;font-size:0.78rem;font-weight:600;color:var(--primary);">'+c+'</span><span style="padding:0.25rem 0.6rem;background:rgba(32,0,130,0.1);border-radius:999px;font-size:0.78rem;font-weight:600;">'+(dl[d]||'Beginner')+'</span><span style="padding:0.25rem 0.6rem;background:rgba(16,185,129,0.1);color:#10b981;border-radius:999px;font-size:0.78rem;font-weight:600;">'+st.charAt(0).toUpperCase()+st.slice(1)+'</span>'+(h?'<span style="padding:0.25rem 0.6rem;background:rgba(0,0,0,0.05);border-radius:999px;font-size:0.78rem;font-weight:600;">'+h+'h</span>':'')+'</div>';
    if(sk.length){html+='<div style="display:flex;flex-wrap:wrap;gap:0.3rem;">';sk.forEach(function(s){html+='<span style="padding:0.2rem 0.5rem;background:rgba(32,0,130,0.06);border-radius:999px;font-size:0.75rem;color:var(--primary);font-weight:600;">'+s+'</span>';});html+='</div>';}
    if(desc) html+='<div style="margin-top:1rem;padding:1rem;background:rgba(0,0,0,0.03);border-radius:10px;font-size:0.9rem;line-height:1.6;white-space:pre-wrap;">'+desc.replace(/</g,'&lt;')+'</div>';
    html+='</div></div></div>';
    document.body.insertAdjacentHTML('beforeend',html);
}
(function() {
    var params = new URLSearchParams(window.location.search);
    var courseId = params.get('id');
    if (!courseId) return;
    document.getElementById('form-title').textContent = 'Edit Course';
    document.getElementById('save-btn-text').textContent = 'Update Course';
    document.querySelector('form#course-form').action = 'pages/instructor/elearning-subpage/ajax/edit-course.php';
    fetch('pages/instructor/elearning-subpage/ajax/get-course-by-id.php?id='+courseId, {credentials:'same-origin', headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(function(r){return r.json()}).then(function(data){
        if (!data.success || !data.data) return;
        var course = data.data;
        var form = document.getElementById('course-form');
        form.querySelector('input[name="title"]').value = course.title||'';
        form.querySelector('input[name="category"]').value = course.category||'';
        form.querySelector('select[name="difficulty"]').value = course.difficulty||'beginner';
        form.querySelector('select[name="status"]').value = course.status||'active';
        form.querySelector('select[name="delivery_mode"]').value = course.delivery_mode||'online';
        form.querySelector('input[name="start_date"]').value = course.start_date?course.start_date.split(' ')[0]:'';
        form.querySelector('input[name="enrollment_deadline"]').value = course.enrollment_deadline?course.enrollment_deadline.split(' ')[0]:'';
        form.querySelector('input[name="estimated_hours"]').value = course.estimated_hours||'';
        form.querySelector('input[name="max_learners"]').value = course.max_learners||'';
        form.querySelector('textarea[name="description"]').value = course.description||'';
        form.querySelector('textarea[name="prerequisites"]').value = course.prerequisites||'';
        var skillIds = course.skill_ids || [];
        form.querySelectorAll('input[name="skill_ids[]"]').forEach(function(cb){ cb.checked = skillIds.indexOf(parseInt(cb.value))!==-1; });
        var idInput = document.createElement('input'); idInput.type='hidden'; idInput.name='id'; idInput.value=course.id;
        form.appendChild(idInput);

        // Load the course's existing modules / lessons / quizzes / questions into the builder
        fetch('pages/instructor/elearning-subpage/ajax/get-course-builder-content.php?course_id=' + courseId, {credentials:'same-origin', headers:{'X-Requested-With':'XMLHttpRequest'}})
        .then(function(r){return r.json()})
        .then(function(content){
            if (!content || !content.success) return;
            var attempts = 0;
            function applyContent() {
                if (!window.courseBuilder) {
                    if (attempts++ < 200) { setTimeout(applyContent, 50); return; }
                    return;
                }
                var cb = window.courseBuilder;
                // Ignore any leftover localStorage draft from a previous Add session
                cb.courseData.course = cb.courseData.course || {};
                cb.courseData.modules = content.modules || [];
                cb.courseData.evaluation = content.evaluation || null;
                var evalFields = {
                    eval_title: content.evaluation ? (content.evaluation.title || '') : '',
                    eval_duration_minutes: content.evaluation && content.evaluation.duration_seconds ? Math.round(content.evaluation.duration_seconds / 60 * 100) / 100 : '',
                    eval_passing_score: content.evaluation && content.evaluation.passing_score != null ? content.evaluation.passing_score : '',
                    eval_max_attempts: content.evaluation && content.evaluation.max_attempts ? content.evaluation.max_attempts : 2,
                    eval_question_count: content.evaluation && content.evaluation.question_count ? content.evaluation.question_count : '',
                    eval_status: content.evaluation && content.evaluation.status ? content.evaluation.status : 'active'
                };
                Object.keys(evalFields).forEach(function(n){ var el = form.querySelector('[name="' + n + '"]'); if (el) el.value = evalFields[n]; });
                var showAns = form.querySelector('[name="eval_show_answers"]');
                if (showAns) showAns.checked = !!(content.evaluation && content.evaluation.show_answers_after_submit);
                cb.renderModulesList();
                cb.renderEvaluationQuestions();
            }
            applyContent();
        })
        .catch(function(){});
    });
})();
function showNotif(msg,type){var el=document.createElement('div');el.style.cssText='position:fixed;top:20px;right:20px;padding:0.8rem 1.2rem;border-radius:8px;font-weight:600;font-size:0.85rem;z-index:10000;box-shadow:0 4px 12px rgba(0,0,0,0.15);';el.style.background=type==='success'?'#10b981':type==='error'?'#ef4444':'#3b82f6';el.style.color='#fff';el.textContent=msg;document.body.appendChild(el);setTimeout(function(){el.remove();},3000);}
(function(){var f=document.getElementById('course-form');var ind=document.getElementById('form-status');if(!f||!ind)return;f.addEventListener('input',function(){ind.textContent='* unsaved changes';ind.style.color='var(--primary)';});f.addEventListener('submit',function(){ind.textContent='saving...';ind.style.color='#10b981';});})();

// --- Prerequisites Management ---
(function() {
    var params = new URLSearchParams(window.location.search);
    var courseId = params.get('id');
    if (!courseId) return; // Only show in edit mode

    var section = document.getElementById('prerequisites-section');
    var list = document.getElementById('prerequisites-list');
    var addBtn = document.getElementById('add-prereq-btn');
    var modal = document.getElementById('prereq-modal');
    var typeSelect = document.getElementById('prereq-type');
    var courseLabel = document.getElementById('prereq-course-label');
    var skillLabel = document.getElementById('prereq-skill-label');
    var courseSelect = document.getElementById('prereq-course-id');
    var skillSelect = document.getElementById('prereq-skill-id');
    var saveBtn = document.getElementById('save-prereq-btn');

    if (!section) return;
    section.style.display = 'block';

    // Toggle course/skill dropdowns
    typeSelect.addEventListener('change', function() {
        courseLabel.style.display = typeSelect.value === 'course' ? 'block' : 'none';
        skillLabel.style.display = typeSelect.value === 'skill' ? 'block' : 'none';
    });

    // Load existing prerequisites
    function loadPrereqs() {
        fetch('pages/instructor/elearning-subpage/ajax/get-prerequisite.php?course_id=' + courseId)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success || !data.items.length) {
                list.innerHTML = '<p style="color:#999; font-size:0.85rem; padding:0.5rem;">No prerequisites set.</p>';
                return;
            }
            var html = '';
            data.items.forEach(function(item) {
                var name = item.course_title || item.skill_name || 'Unknown';
                var type = item.required_course_id ? 'Course' : 'Skill';
                var icon = item.required_course_id ? 'fa-graduation-cap' : 'fa-star';
                html += '<div style="display:flex; align-items:center; justify-content:space-between; padding:0.75rem 1rem; background:rgba(32,0,130,0.03); border:1px solid rgba(32,0,130,0.08); border-radius:10px;">';
                html += '<div style="display:flex; align-items:center; gap:0.75rem;">';
                html += '<div style="width:36px; height:36px; border-radius:8px; background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0;"><i class="fas ' + icon + '" style="font-size:0.8rem;"></i></div>';
                html += '<div><div style="font-weight:600; font-size:0.9rem; color:var(--text);">' + name + '</div>';
                html += '<div style="font-size:0.75rem; color:#999;">' + type + '</div></div>';
                html += '</div>';
                html += '<button type="button" class="remove-prereq-btn" data-id="' + item.id + '" style="padding:0.35rem 0.7rem; background:rgba(239,68,68,0.08); color:#ef4444; border:1px solid rgba(239,68,68,0.2); border-radius:6px; cursor:pointer; font-size:0.75rem; font-weight:600;"><i class="fas fa-times" style="margin-right:0.2rem;"></i>Remove</button>';
                html += '</div>';
            });
            list.innerHTML = html;
            // Attach remove handlers
            list.querySelectorAll('.remove-prereq-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    removePrereq(this.dataset.id);
                });
            });
        })
        .catch(function() { list.innerHTML = '<p style="color:#ef4444; font-size:0.85rem;">Failed to load prerequisites.</p>'; });
    }

    // Load courses and skills for the add modal
    function loadDropdowns() {
        fetch('pages/instructor/elearning-subpage/ajax/get-course-by-id.php?id=' + courseId)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            // Populate course dropdown (all active courses except this one)
            return fetch('pages/learner/catalog-subpage/ajax/discover/get-course.php');
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success && data.items) {
                courseSelect.innerHTML = '<option value="">Select a course...</option>';
                data.items.forEach(function(c) {
                    if (c.id == courseId) return;
                    courseSelect.innerHTML += '<option value="' + c.id + '">' + c.title + '</option>';
                });
            }
        })
        .catch(function() {});

        fetch('pages/learner/catalog-subpage/ajax/discover/get-skill.php')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success && data.items) {
                skillSelect.innerHTML = '<option value="">Select a skill...</option>';
                data.items.forEach(function(s) {
                    skillSelect.innerHTML += '<option value="' + s.id + '">' + s.name + '</option>';
                });
            }
        })
        .catch(function() {});
    }

    // Add prerequisite
    saveBtn.addEventListener('click', function() {
        var type = typeSelect.value;
        var payload = { course_id: parseInt(courseId) };
        if (type === 'course') {
            payload.required_course_id = parseInt(courseSelect.value);
            if (!payload.required_course_id) { if (window.showToast) window.showToast('Please select a course', 'error'); return; }
        } else {
            payload.required_skill_id = parseInt(skillSelect.value);
            if (!payload.required_skill_id) { if (window.showToast) window.showToast('Please select a skill', 'error'); return; }
        }
        saveBtn.disabled = true;
        fetch('pages/instructor/elearning-subpage/ajax/add-prerequisite.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            saveBtn.disabled = false;
            if (data.success) {
                modal.style.display = 'none';
                loadPrereqs();
                if (window.showToast) window.showToast('Prerequisite added', 'success');
            } else {
                if (window.showToast) window.showToast(data.error || 'Failed to add prerequisite', 'error');
            }
        })
        .catch(function() { saveBtn.disabled = false; if (window.showToast) window.showToast('Network error', 'error'); });
    });

    // Remove prerequisite
    function removePrereq(id) {
        if (!confirm('Remove this prerequisite?')) return;
        fetch('pages/instructor/elearning-subpage/ajax/remove-prerequisite.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: parseInt(id) })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                loadPrereqs();
                if (window.showToast) window.showToast('Prerequisite removed', 'success');
            } else {
                if (window.showToast) window.showToast(data.error || 'Failed to remove', 'error');
            }
        })
        .catch(function() { if (window.showToast) window.showToast('Network error', 'error'); });
    }

    addBtn.addEventListener('click', function() {
        modal.style.display = 'flex';
        typeSelect.value = 'course';
        typeSelect.dispatchEvent(new Event('change'));
    });

    loadPrereqs();
    loadDropdowns();
})();
</script>