<style>
/* ═══════════════════════════════════════════════════════════════════════════
   Course Structure Builder
   ═══════════════════════════════════════════════════════════════════════════ */

/* ─── Page Shell ─────────────────────────────────────────────────────────── */
.cs-builder { max-width:960px; margin:0 auto; padding:1.5rem 0 3rem; }

/* ─── Toolbar ────────────────────────────────────────────────────────────── */
.cs-toolbar {
    display:flex; align-items:center; gap:0.75rem;
    padding:0.85rem 1.25rem;
    background:var(--surface, #fff);
    border:1px solid var(--border, rgba(32,0,130,0.1));
    border-radius:14px;
    position:sticky; top:calc(var(--header-height, 60px) + 0.5rem); z-index:50;
    margin-bottom:1rem;
}
.cs-toolbar .cs-search { flex:1; min-width:0; position:relative; }
.cs-toolbar .cs-search i { position:absolute; left:0.85rem; top:50%; transform:translateY(-50%); color:var(--muted, #999); font-size:0.85rem; pointer-events:none; }
.cs-toolbar .cs-search select {
    width:100%; padding:0.65rem 1rem 0.65rem 2.4rem;
    border:1.5px solid rgba(32,0,130,0.12); border-radius:10px;
    background:rgba(32,0,130,0.02); font-size:0.92rem; font-weight:600;
    color:var(--text, #333); cursor:pointer; outline:none;
    transition:border-color .2s, box-shadow .2s; appearance:none;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23999' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-repeat:no-repeat; background-position:right 0.75rem center;
}
.cs-toolbar .cs-search select:focus { border-color:var(--primary, #320082); box-shadow:0 0 0 3px rgba(32,0,130,0.08); }
.cs-toolbar-actions { display:flex; gap:0.4rem; flex-shrink:0; }
.cs-toolbar-actions button {
    display:inline-flex; align-items:center; justify-content:center; gap:0.35rem;
    padding:0.5rem 0.75rem; border:none; border-radius:8px;
    font-size:0.78rem; font-weight:700; cursor:pointer; transition:all .15s;
}
.cs-btn-icon {
    background:var(--surface, #fff); color:var(--text, #333);
    border:1px solid var(--border, rgba(32,0,130,0.1));
}
.cs-btn-icon:hover { background:rgba(32,0,130,0.06); border-color:var(--primary, #320082); }
.cs-btn-primary {
    background:var(--primary, #320082); color:#fff;
    border:none; box-shadow:0 2px 8px rgba(32,0,130,0.25);
}
.cs-btn-primary:hover { box-shadow:0 4px 14px rgba(32,0,130,0.35); transform:translateY(-1px); }
.cs-btn-success {
    background:var(--accent, #10b981); color:#fff;
    border:none; box-shadow:0 2px 8px rgba(16,185,129,0.25);
}
.cs-btn-success:hover { box-shadow:0 4px 14px rgba(16,185,129,0.35); transform:translateY(-1px); }

/* ─── Course Header ──────────────────────────────────────────────────────── */
.cs-course-header { display:none; margin-bottom:0.5rem; }
.cs-course-header.is-visible { display:block; }
.cs-header-top {
    display:flex; align-items:flex-start; justify-content:space-between; gap:1rem;
    padding:1.25rem 1.5rem;
    background:var(--surface, #fff);
    border:1px solid var(--border, rgba(32,0,130,0.1)); border-radius:14px;
}
.cs-header-info { flex:1; min-width:0; }
.cs-header-info h2 { margin:0 0 0.15rem; font-size:1.2rem; font-weight:800; color:var(--text, #1a1a2e); line-height:1.3; word-break:break-word; }
.cs-header-meta { margin:0 0 0.65rem; font-size:0.82rem; color:var(--muted, #888); }
.cs-header-actions { display:flex; gap:0.5rem; flex-shrink:0; align-items:flex-start; }

/* ── Circular Icon Buttons with Tooltip ─────────────────────────────────── */
.cs-circ-btn {
    position:relative; display:inline-flex; align-items:center; justify-content:center;
    width:2.4rem; height:2.4rem; border-radius:50%; border:none;
    font-size:0.95rem; cursor:pointer; transition:all .2s;
    box-shadow:0 2px 8px rgba(0,0,0,0.15);
}
.cs-circ-btn:hover { transform:translateY(-2px); box-shadow:0 4px 14px rgba(0,0,0,0.2); }
.cs-circ-btn-primary { background:var(--primary, #320082); color:#fff; }
.cs-circ-btn-success { background:var(--accent, #10b981); color:#fff; }

.cs-circ-btn .cs-tooltip {
    position:absolute; bottom:calc(100% + 0.5rem); left:50%; transform:translateX(-50%);
    padding:0.3rem 0.65rem; border-radius:6px;
    background:var(--text, #1a1a2e); color:var(--surface, #fff);
    font-size:0.72rem; font-weight:700; white-space:nowrap;
    opacity:0; pointer-events:none; transition:opacity .15s, transform .15s;
    transform:translateX(-50%) translateY(4px);
}
.cs-circ-btn .cs-tooltip::after {
    content:''; position:absolute; top:100%; left:50%; transform:translateX(-50%);
    border:5px solid transparent; border-top-color:var(--text, #1a1a2e);
}
.cs-circ-btn:hover .cs-tooltip { opacity:1; transform:translateX(-50%) translateY(0); }

/* ── Stats Row ───────────────────────────────────────────────────────────── */
.cs-stats-row { display:flex; gap:0; flex-wrap:wrap; }
.cs-stat-chip {
    display:inline-flex; align-items:center; justify-content:center; gap:0.35rem;
    padding:0.45rem 0; border-radius:10px; font-size:0.78rem; font-weight:700;
    flex:1 1 0; min-width:0;
}
.cs-stat-chip i { font-size:0.7rem; }
.cs-stat-modules { background:rgba(32,0,130,0.08); color:var(--primary, #320082); }
.cs-stat-lessons  { background:rgba(59,130,246,0.08); color:#3b82f6; }
.cs-stat-quizzes  { background:rgba(245,158,11,0.08); color:#d97706; }
.cs-stat-evals    { background:rgba(16,185,129,0.08); color:#059669; }

[data-theme="dark"] .cs-header-top { background:rgba(30,30,50,0.95); border-color:rgba(255,255,255,0.08); }
[data-theme="dark"] .cs-header-info h2 { color:rgba(255,255,255,0.95); }
[data-theme="dark"] .cs-stat-chip { border:1px solid rgba(255,255,255,0.06); }
[data-theme="dark"] .cs-tooltip { background:var(--surface, #fff); color:var(--text, #333); }
[data-theme="dark"] .cs-tooltip::after { border-top-color:var(--surface, #fff); }

/* ─── Empty State ────────────────────────────────────────────────────────── */
.cs-empty { text-align:center; padding:4rem 2rem; color:var(--muted, #999); }
.cs-empty-icon { font-size:3.5rem; margin-bottom:1rem; opacity:.4; color:var(--primary, #320082); }
.cs-empty h3 { margin:0 0 0.5rem; color:var(--text, #333); font-size:1.15rem; }
.cs-empty p { margin:0; max-width:38ch; margin-inline:auto; line-height:1.6; font-size:0.9rem; }

/* ─── Structure Tree ─────────────────────────────────────────────────────── */
.cs-tree { display:flex; flex-direction:column; gap:0.75rem; }

/* ── Module Card ─────────────────────────────────────────────────────────── */
.cs-mod {
    border:1.5px solid var(--border, rgba(32,0,130,0.12)); border-radius:14px;
    background:var(--surface, #fff);
    transition:box-shadow .2s, border-color .2s, opacity .2s;
}
.cs-mod.dragging { opacity:.45; border-color:var(--primary); box-shadow:0 8px 30px rgba(32,0,130,0.15); }
.cs-mod.drag-over { border-color:var(--primary); background:rgba(32,0,130,0.02); }
.cs-mod-head {
    display:flex; align-items:center; gap:0.5rem;
    padding:0.85rem 1rem;
    background:rgba(32,0,130,0.03);
    border-radius:12px 12px 0 0; cursor:grab; user-select:none;
}
.cs-mod-head:active { cursor:grabbing; }
.cs-mod-head .drag-handle { color:var(--primary); font-size:1rem; opacity:.35; transition:opacity .2s; cursor:grab; }
.cs-mod-head:hover .drag-handle { opacity:.9; }
.cs-mod-body { padding:0.75rem; }

/* ── Lesson Row ──────────────────────────────────────────────────────────── */
.cs-les {
    display:flex; align-items:center; gap:0.5rem;
    padding:0.6rem 0.75rem; margin-bottom:0.4rem;
    border:1px solid rgba(59,130,246,0.12); border-radius:10px;
    background:rgba(59,130,246,0.03); cursor:grab; user-select:none;
    transition:box-shadow .2s, border-color .2s, opacity .2s;
}
.cs-les:active { cursor:grabbing; }
.cs-les.dragging { opacity:.45; border-color:#3b82f6; box-shadow:0 4px 16px rgba(59,130,246,0.15); }
.cs-les.drag-over { border-color:#3b82f6; background:rgba(59,130,246,0.04); }
.cs-les .drag-handle { color:#3b82f6; font-size:.85rem; opacity:.3; transition:opacity .2s; cursor:grab; }
.cs-les:hover .drag-handle { opacity:.85; }

/* ── Quiz Chip ───────────────────────────────────────────────────────────── */
.cs-qz {
    display:flex; align-items:center; gap:0.45rem;
    padding:0.45rem 0.65rem; margin-bottom:0.3rem;
    border:1px solid rgba(245,158,11,0.15); border-radius:8px;
    background:rgba(245,158,11,0.03); cursor:grab; user-select:none;
    transition:box-shadow .2s, border-color .2s, opacity .2s;
}
.cs-qz:active { cursor:grabbing; }
.cs-qz.dragging { opacity:.45; border-color:#f59e0b; box-shadow:0 4px 16px rgba(245,158,11,0.15); }
.cs-qz.drag-over { border-color:#f59e0b; background:rgba(245,158,11,0.04); }
.cs-qz .drag-handle { color:#f59e0b; font-size:.75rem; opacity:.3; transition:opacity .2s; cursor:grab; }
.cs-qz:hover .drag-handle { opacity:.85; }

/* ── Badges ──────────────────────────────────────────────────────────────── */
.cs-badge {
    display:inline-flex; align-items:center; gap:0.25rem;
    padding:0.15rem 0.55rem; border-radius:999px;
    font-size:0.68rem; font-weight:800; letter-spacing:.02em;
    text-transform:uppercase; white-space:nowrap;
}
.cs-badge-mod  { background:rgba(32,0,130,0.08); color:var(--primary, #320082); }
.cs-badge-les  { background:rgba(59,130,246,0.1); color:#3b82f6; }
.cs-badge-qz   { background:rgba(245,158,11,0.1); color:#d97706; }
.cs-badge-eval { background:rgba(16,185,129,0.1); color:#059669; }
.cs-badge-count { background:rgba(107,114,128,0.08); color:#6b7280; font-size:.62rem; font-weight:700; text-transform:none; letter-spacing:0; }

/* ── Action Buttons ──────────────────────────────────────────────────────── */
.cs-acts { display:flex; gap:0.2rem; margin-left:auto; flex-shrink:0; }
.cs-acts button {
    display:inline-flex; align-items:center; gap:0.25rem;
    padding:0.3rem 0.55rem; border:none; border-radius:6px;
    font-size:0.7rem; font-weight:700; cursor:pointer; transition:all .12s; white-space:nowrap;
}
.cs-act-view   { background:rgba(59,130,246,0.08); color:#3b82f6; }
.cs-act-view:hover { background:rgba(59,130,246,0.18); }
.cs-act-edit   { background:rgba(32,0,130,0.06); color:var(--primary, #320082); }
.cs-act-edit:hover { background:rgba(32,0,130,0.14); }
.cs-act-del    { background:rgba(239,68,68,0.06); color:#ef4444; }
.cs-act-del:hover { background:rgba(239,68,68,0.14); }
.cs-act-add    { background:rgba(16,185,129,0.07); color:#059669; }
.cs-act-add:hover { background:rgba(16,185,129,0.16); }

/* Standalone action buttons (outside .cs-acts) */
.cs-act-del:not(.cs-acts button),
.cs-act-add:not(.cs-acts button) {
    display:inline-flex; align-items:center; gap:0.25rem;
    padding:0.3rem 0.65rem; border:none; border-radius:6px;
    font-size:0.75rem; font-weight:700; cursor:pointer; transition:all .12s; white-space:nowrap;
}
.cs-act-del:not(.cs-acts button) { border:1px solid rgba(239,68,68,0.15); }
.cs-act-add:not(.cs-acts button) { border:1px solid rgba(16,185,129,0.15); }
.cs-act-upload { background:rgba(139,92,246,0.08); color:#7c3aed; }
.cs-act-upload:hover { background:rgba(139,92,246,0.18); }

.cs-title {
    flex:1; min-width:0; font-weight:700; cursor:text;
    overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
}
.cs-meta { font-size:0.72rem; color:var(--muted, #999); white-space:nowrap; }

/* ── Module Section Headers ──────────────────────────────────────────────── */
.cs-section-label {
    display:flex; align-items:center; gap:0.4rem;
    font-size:0.68rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; padding:0.4rem 0; margin-top:0.5rem;
}
.cs-section-label.lessons { color:#3b82f6; }
.cs-section-label.quizzes { color:#d97706; }
.cs-section-label::after { content:''; flex:1; height:1px; background:currentColor; opacity:.15; }

/* ── Drop Zones ──────────────────────────────────────────────────────────── */
.cs-drop { min-height:28px; border:2px dashed transparent; border-radius:8px; transition:all .2s; padding:0.15rem 0; }
.cs-drop.active { border-color:var(--primary); background:rgba(32,0,130,0.03); }
.cs-empty-drop {
    display:flex; align-items:center; justify-content:center;
    padding:0.85rem; border:2px dashed var(--border, rgba(32,0,130,0.12));
    border-radius:10px; color:var(--muted, #aaa); font-size:0.82rem; margin-top:0.35rem;
}

/* ── Add Form ────────────────────────────────────────────────────────────── */
.cs-add-form {
    padding:0.7rem 0.85rem; background:rgba(32,0,130,0.03);
    border:1px solid var(--border, rgba(32,0,130,0.12)); border-radius:10px;
    margin-bottom:0.5rem; animation:csSlideIn .2s ease;
}
.cs-add-form-row { display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap; }
.cs-add-form-row input, .cs-add-form-row select {
    padding:0.5rem 0.7rem; border:1px solid var(--border, #e5e7eb); border-radius:8px; font-size:0.85rem;
}
.cs-add-form-row input[type="text"] { min-width:200px; flex:1; }
.cs-add-form-row input:focus, .cs-add-form-row select:focus { border-color:var(--primary, #320082); outline:none; }
.cs-add-form-row button { padding:0.5rem 1rem; border:none; border-radius:8px; cursor:pointer; font-weight:700; font-size:0.8rem; }
.cs-add-save   { background:var(--primary, #320082); color:#fff; }
.cs-add-cancel { background:var(--border, #e5e7eb); color:var(--text, #374151); }

/* ── Inline Edit ─────────────────────────────────────────────────────────── */
.cs-inline-edit {
    padding:0.2rem 0.5rem; border:2px solid var(--primary, #320082);
    border-radius:6px; font-size:inherit; font-weight:inherit; outline:none; background:var(--surface, #fff);
}

/* ── File Chips ──────────────────────────────────────────────────────────── */
.cs-files { display:flex; flex-wrap:wrap; gap:0.35rem; padding:0.35rem 0 0.2rem 2.25rem; }
.cs-file {
    display:inline-flex; align-items:center; gap:0.3rem;
    padding:0.25rem 0.55rem; background:rgba(59,130,246,0.05);
    border:1px solid rgba(59,130,246,0.15); border-radius:6px; font-size:0.72rem;
}
.cs-file-name { max-width:110px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-weight:500; }
.cs-file-thumb { width:22px; height:22px; border-radius:4px; object-fit:cover; border:1px solid rgba(59,130,246,0.1); }
.cs-file-del {
    padding:0.1rem 0.3rem !important; font-size:.65rem !important;
    background:rgba(239,68,68,0.06) !important; color:#ef4444 !important;
    border:none; border-radius:4px; cursor:pointer;
}
.cs-file-del:hover { background:rgba(239,68,68,0.18) !important; }

/* ── Evaluation ──────────────────────────────────────────────────────────── */
.cs-eval { border:1.5px solid rgba(16,185,129,0.2); border-radius:14px; background:var(--surface, #fff); margin-top:0.5rem; }
.cs-eval .cs-mod-head { background:rgba(16,185,129,0.04); cursor:default; }

/* ═══════════════════════════════════════════════════════════════════════════
   Preview / Detail Drawer
   ═══════════════════════════════════════════════════════════════════════════ */
.cs-drawer-overlay {
    display:none; position:fixed; inset:0; z-index:9000;
}
.cs-drawer-overlay.is-open {
    display:flex; align-items:stretch; justify-content:flex-end;
    background:rgba(15,23,42,0.45); backdrop-filter:blur(3px);
}

.cs-drawer {
    position:relative; width:min(560px, 92vw); max-height:100vh; background:var(--surface, #fff);
    box-shadow:-8px 0 40px rgba(0,0,0,0.12);
    display:flex; flex-direction:column; z-index:1;
    animation:csSlideRight .25s ease;
    overflow:hidden;
}

/* Preview drawer slides from right */
#cs-preview-overlay.is-open { align-items:stretch; justify-content:flex-end; }

/* Template modal centers */
#cs-template-modal.is-open { align-items:center; justify-content:center; }
#cs-template-modal .cs-modal {
    width:min(480px, 90vw);
    max-height:80vh;
    overflow-y:auto;
}
@keyframes csSlideRight { from { transform:translateX(100%); } to { transform:translateX(0); } }
@keyframes csSlideIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:translateY(0); } }

.cs-drawer-head {
    display:flex; justify-content:space-between; align-items:center;
    padding:1rem 1.25rem; border-bottom:1px solid rgba(32,0,130,0.08);
    background:linear-gradient(135deg, rgba(32,0,130,0.04), rgba(81,70,183,0.02));
    flex-shrink:0;
}
.cs-drawer-head h3 { margin:0; font-size:1.05rem; font-weight:800; color:var(--text, #1a1a2e); display:flex; align-items:center; gap:0.5rem; }
.cs-drawer-close {
    background:none; border:none; font-size:1.3rem; cursor:pointer;
    color:var(--muted, #999); padding:0.25rem 0.4rem; border-radius:6px; transition:all .15s;
}
.cs-drawer-close:hover { background:rgba(0,0,0,0.06); color:var(--text, #333); }

.cs-drawer-body { flex:1; overflow-y:auto; padding:1.25rem; }

/* Drawer content sections */
.cs-drawer-section { margin-bottom:1.25rem; }
.cs-drawer-section:last-child { margin-bottom:0; }
.cs-drawer-label {
    font-size:0.7rem; font-weight:800; text-transform:uppercase;
    letter-spacing:.06em; color:var(--muted, #999); margin-bottom:0.5rem;
}
.cs-drawer-desc {
    font-size:0.92rem; line-height:1.7; color:var(--text, #333);
    background:rgba(32,0,130,0.02); padding:0.85rem 1rem; border-radius:10px;
    border:1px solid rgba(32,0,130,0.06);
}
.cs-drawer-desc:empty::before { content:'No description available'; color:var(--muted, #bbb); font-style:italic; }

/* Drawer: stat chips */
.cs-drawer-stats { display:flex; gap:0.5rem; flex-wrap:wrap; margin-bottom:0.75rem; }
.cs-drawer-stat {
    display:inline-flex; align-items:center; gap:0.3rem;
    padding:0.35rem 0.7rem; border-radius:8px; font-size:0.8rem; font-weight:700;
}

/* Drawer: child list (lessons in module, questions in quiz) */
.cs-drawer-list { display:flex; flex-direction:column; gap:0.35rem; }
.cs-drawer-item {
    display:flex; align-items:center; gap:0.5rem;
    padding:0.6rem 0.8rem; border-radius:8px;
    border:1px solid rgba(32,0,130,0.08); background:#fafbff;
    transition:background .15s;
}
.cs-drawer-item:hover { background:rgba(32,0,130,0.03); }
.cs-drawer-item-icon {
    width:28px; height:28px; border-radius:6px; display:flex;
    align-items:center; justify-content:center; font-size:0.7rem; flex-shrink:0;
}
.cs-drawer-item-title { flex:1; font-size:0.85rem; font-weight:600; color:var(--text); }
.cs-drawer-item-meta { font-size:0.72rem; color:var(--muted, #999); }

/* Drawer: quiz questions */
.cs-drawer-q {
    border:1px solid rgba(32,0,130,0.08); border-radius:10px;
    margin-bottom:0.6rem; overflow:hidden; background:#fff;
}
.cs-drawer-q-head {
    display:flex; align-items:center; gap:0.5rem;
    padding:0.55rem 0.75rem; background:rgba(32,0,130,0.02);
}
.cs-drawer-q-num { font-weight:800; color:var(--primary, #320082); font-size:0.78rem; min-width:1.6rem; }
.cs-drawer-q-text { font-weight:600; font-size:0.88rem; flex:1; }
.cs-drawer-q-badge { font-size:0.65rem; }
.cs-drawer-q-opts { padding:0.4rem 0.75rem 0.5rem; display:flex; flex-direction:column; gap:0.25rem; }
.cs-drawer-opt {
    display:flex; align-items:center; gap:0.4rem;
    padding:0.35rem 0.6rem; border-radius:6px; font-size:0.82rem;
}
.cs-drawer-opt.correct { background:rgba(16,185,129,0.08); color:#059669; font-weight:600; }
.cs-drawer-opt-dot {
    width:16px; height:16px; border-radius:50%; border:2px solid #d1d5db;
    display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:0.55rem;
}
.cs-drawer-opt.correct .cs-drawer-opt-dot { border-color:#10b981; background:#10b981; color:#fff; }

/* Drawer: file list */
.cs-drawer-files { display:flex; flex-direction:column; gap:0.3rem; }
.cs-drawer-file {
    display:flex; align-items:center; gap:0.5rem;
    padding:0.5rem 0.75rem; border-radius:8px;
    background:rgba(59,130,246,0.04); border:1px solid rgba(59,130,246,0.1);
}
.cs-drawer-file-icon { width:28px; height:28px; border-radius:6px; display:flex; align-items:center; justify-content:center; font-size:0.75rem; flex-shrink:0; background:rgba(59,130,246,0.1); color:#3b82f6; }
.cs-drawer-file-info { flex:1; min-width:0; }
.cs-drawer-file-name { font-size:0.82rem; font-weight:600; color:var(--text); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.cs-drawer-file-type { font-size:0.7rem; color:var(--muted, #999); }
.cs-drawer-file-thumb { width:36px; height:36px; border-radius:6px; object-fit:cover; border:1px solid rgba(59,130,246,0.1); }

/* Drawer: quiz panel specific (for editing) */
.cs-drawer-footer { padding:0.75rem 1rem; border-top:1px solid rgba(32,0,130,0.08); background:#fafbff; flex-shrink:0; }
.cb-q-card { border:1px solid rgba(32,0,130,0.08); border-radius:10px; margin-bottom:0.7rem; background:#fff; overflow:hidden; }
.cb-q-header { display:flex; align-items:center; gap:0.5rem; padding:0.55rem 0.75rem; background:rgba(32,0,130,0.02); }
.cb-q-num { font-weight:800; color:var(--primary); font-size:0.78rem; min-width:1.6rem; }
.cb-q-text { font-weight:600; font-size:0.88rem; cursor:text; padding:0.15rem 0.3rem; border-radius:4px; flex:1; transition:background .15s; }
.cb-q-text:hover { background:rgba(32,0,130,0.04); }
.cb-q-options { padding:0.4rem 0.75rem 0.5rem; display:flex; flex-direction:column; gap:0.3rem; }
.cb-opt-row { display:flex; align-items:center; gap:0.35rem; }
.cb-opt-correct { cursor:pointer; display:flex; align-items:center; }
.cb-opt-correct input { display:none; }
.cb-opt-dot { width:17px; height:17px; border-radius:50%; border:2px solid #d1d5db; display:flex; align-items:center; justify-content:center; transition:all .15s; }
.cb-opt-correct input:checked + .cb-opt-dot { border-color:#10b981; background:#10b981; }
.cb-opt-correct input:checked + .cb-opt-dot::after { content:'\2713'; color:#fff; font-size:0.6rem; font-weight:700; }
.cb-opt-text { flex:1; padding:0.35rem 0.55rem; border:1px solid transparent; border-radius:6px; font-size:0.82rem; transition:border-color .15s; }
.cb-opt-text:hover { border-color:rgba(32,0,130,0.12); }
.cb-opt-text:focus { border-color:var(--primary); outline:none; }
.cb-opt-add-row { display:flex; gap:0.35rem; margin-top:0.25rem; }
.cb-opt-add-input { flex:1; padding:0.35rem 0.55rem; border:1px dashed #d1d5db; border-radius:6px; font-size:0.82rem; }
.cb-opt-add-input:focus { border-color:var(--primary); outline:none; border-style:solid; }

/* ═══════════════════════════════════════════════════════════════════════════
   Template Name Modal
   ═══════════════════════════════════════════════════════════════════════════ */
.cs-modal {
    position:relative; width:min(420px, 90vw); background:var(--surface, #fff);
    border-radius:16px; box-shadow:0 20px 60px rgba(0,0,0,0.2);
    margin:auto; animation:csModalIn .2s ease;
}
@keyframes csModalIn { from { opacity:0; transform:scale(0.95) translateY(10px); } to { opacity:1; transform:scale(1) translateY(0); } }

.cs-modal-head {
    display:flex; justify-content:space-between; align-items:center;
    padding:1.1rem 1.25rem; border-bottom:1px solid var(--border, rgba(32,0,130,0.08));
}
.cs-modal-head h3 { margin:0; font-size:1rem; font-weight:800; color:var(--text, #1a1a2e); display:flex; align-items:center; gap:0.5rem; }
.cs-modal-close {
    background:none; border:none; font-size:1.2rem; cursor:pointer;
    color:var(--muted, #999); padding:0.2rem 0.35rem; border-radius:6px; transition:all .15s;
}
.cs-modal-close:hover { background:rgba(0,0,0,0.06); color:var(--text, #333); }

.cs-modal-body { padding:1.25rem; }
.cs-modal-label {
    display:block; font-size:0.78rem; font-weight:700; color:var(--muted, #888);
    text-transform:uppercase; letter-spacing:.04em; margin-bottom:0.4rem;
}
.cs-modal-input {
    width:100%; padding:0.65rem 0.85rem;
    border:1.5px solid var(--border, rgba(32,0,130,0.15)); border-radius:10px;
    background:rgba(32,0,130,0.02); font-size:0.95rem; font-weight:600;
    color:var(--text, #333); outline:none; transition:border-color .2s, box-shadow .2s;
    box-sizing:border-box;
}
.cs-modal-input:focus { border-color:var(--primary, #320082); box-shadow:0 0 0 3px rgba(32,0,130,0.08); }
.cs-modal-hint { margin:0.6rem 0 0; font-size:0.78rem; color:var(--muted, #999); line-height:1.5; }

.cs-modal-footer {
    display:flex; justify-content:flex-end; gap:0.5rem;
    padding:0.85rem 1.25rem; border-top:1px solid var(--border, rgba(32,0,130,0.08));
}
.cs-modal-btn-cancel {
    padding:0.5rem 1rem; border:1px solid var(--border, rgba(32,0,130,0.15));
    border-radius:8px; background:transparent; color:var(--text, #333);
    font-size:0.85rem; font-weight:700; cursor:pointer; transition:all .15s;
}
.cs-modal-btn-cancel:hover { background:rgba(0,0,0,0.04); }
.cs-modal-btn-save {
    display:inline-flex; align-items:center; gap:0.35rem;
    padding:0.5rem 1.1rem; border:none; border-radius:8px;
    background:var(--primary, #320082); color:#fff;
    font-size:0.85rem; font-weight:700; cursor:pointer; transition:all .15s;
    box-shadow:0 2px 8px rgba(32,0,130,0.25);
}
.cs-modal-btn-save:hover { box-shadow:0 4px 14px rgba(32,0,130,0.35); transform:translateY(-1px); }

[data-theme="dark"] .cs-modal { background:rgba(25,25,45,0.98); }
[data-theme="dark"] .cs-modal-input { background:rgba(255,255,255,0.05); color:rgba(255,255,255,0.95); }
[data-theme="dark"] .cs-modal-btn-cancel { color:rgba(255,255,255,0.8); }

/* ── Toast ───────────────────────────────────────────────────────────────── */
#toast-container { position:fixed; top:20px; right:20px; z-index:10000; display:flex; flex-direction:column; gap:0.5rem; pointer-events:none; }

/* ── Responsive ──────────────────────────────────────────────────────────── */
@media (max-width:768px) {
    .cs-builder { padding:1rem 0; }
    .cs-toolbar { flex-wrap:wrap; }
    .cs-toolbar .cs-search select { min-width:0; }
    .cs-header-top { flex-direction:column; }
    .cs-header-actions { width:100%; justify-content:center; }
    .cs-acts { flex-wrap:wrap; }
    .cs-mod-head { flex-wrap:wrap; }
    .cs-title { min-width:100px; }
    .cs-stats-row { gap:0.35rem; }
    .cs-stat-chip { padding:0.35rem 0.6rem; font-size:0.72rem; }
}
</style>

<div class="module-content">
    <div class="cs-builder">
        <!-- Toolbar -->
        <div class="cs-toolbar">
            <div class="cs-search">
                <i class="fas fa-book-open"></i>
                <select id="course-selector">
                    <option value="">— Select a Course to Build —</option>
                </select>
            </div>
            <div class="cs-toolbar-actions">
                <button type="button" id="btn-collapse-all" class="cs-btn-icon" title="Collapse All"><i class="fas fa-compress-alt"></i></button>
                <button type="button" id="btn-expand-all" class="cs-btn-icon" title="Expand All"><i class="fas fa-expand-alt"></i></button>
            </div>
        </div>

        <!-- Course Header -->
        <div id="course-header" class="cs-course-header">
            <div class="cs-header-top">
                <div class="cs-header-info">
                    <h2 id="course-title"></h2>
                    <p id="course-meta" class="cs-header-meta"></p>
                    <div id="course-stats" class="cs-stats-row"></div>
                </div>
                <div class="cs-header-actions">
                    <button type="button" id="btn-save-template" class="cs-circ-btn cs-circ-btn-success" title="Save as Template"><i class="fas fa-bookmark"></i><span class="cs-tooltip">Save as Template</span></button>
                    <button type="button" id="btn-add-module" class="cs-circ-btn cs-circ-btn-primary" title="Add Module"><i class="fas fa-plus"></i><span class="cs-tooltip">Add Module</span></button>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div id="empty-state" class="cs-empty">
            <div class="cs-empty-icon"><i class="fas fa-sitemap"></i></div>
            <h3>Course Structure Builder</h3>
            <p>Select a course above to visually build and reorder its modules, lessons, and quizzes with drag-and-drop.</p>
        </div>

        <!-- Structure Tree -->
        <div id="structure-tree" class="cs-tree" style="display:none;"></div>

        <!-- Preview Drawer (shared for module / lesson / quiz views) -->
        <div id="cs-preview-overlay" class="cs-drawer-overlay">
            <div class="cs-drawer">
                <div class="cs-drawer-head">
                    <h3 id="cs-preview-title"><i class="fas fa-eye"></i> <span></span></h3>
                    <button class="cs-drawer-close" id="cs-preview-close"><i class="fas fa-times"></i></button>
                </div>
                <div class="cs-drawer-body" id="cs-preview-body">
                    <p style="text-align:center; color:var(--muted);">Loading...</p>
                </div>
            </div>
        </div>

        <!-- Quiz Question Editor Drawer -->
        <div id="cb-quiz-panel" class="cs-drawer-overlay">
            <div class="cs-drawer">
                <div class="cs-drawer-head">
                    <h3 id="cb-quiz-title"><i class="fas fa-question-circle"></i> <span></span></h3>
                    <div style="display:flex; gap:0.5rem; align-items:center;">
                        <button id="cb-quiz-bulk-toggle" style="padding:0.4rem 0.8rem; background:rgba(59,130,246,0.1); color:#3b82f6; border:1px solid rgba(59,130,246,0.2); border-radius:6px; font-weight:700; font-size:0.8rem; cursor:pointer;"><i class="fas fa-file-import"></i> Bulk Import</button>
                        <button class="cs-drawer-close" id="cb-quiz-close"><i class="fas fa-times"></i></button>
                    </div>
                </div>
                <div id="cb-quiz-bulk-panel" style="display:none; padding:0.75rem 1rem; border-bottom:1px solid rgba(32,0,130,0.1); background:#f8faff;">
                    <p style="margin:0 0 0.5rem; font-size:0.8rem; color:#666;">Paste questions below, one per block. Use <code>A)</code>, <code>B)</code> etc. for options. Prefix the correct answer with <code>*</code>.</p>
                    <textarea id="cb-quiz-bulk-text" rows="6" placeholder="Paste questions here..." style="width:100%; padding:0.5rem; border:1px solid #d1d5db; border-radius:6px; font-size:0.85rem; font-family:monospace; resize:vertical;"></textarea>
                    <div style="display:flex; gap:0.5rem; margin-top:0.5rem; align-items:center;">
                        <button id="cb-quiz-bulk-import" style="padding:0.5rem 1rem; background:#10b981; color:#fff; border:none; border-radius:6px; font-weight:700; cursor:pointer;">Import</button>
                        <label style="padding:0.5rem 1rem; background:rgba(59,130,246,0.1); color:#3b82f6; border:1px solid rgba(59,130,246,0.2); border-radius:6px; font-weight:700; cursor:pointer; font-size:0.85rem;"><i class="fas fa-file-csv"></i> CSV<input type="file" id="cb-quiz-bulk-csv" accept=".csv,.txt" style="display:none;" /></label>
                        <span id="cb-quiz-bulk-status" style="font-size:0.8rem; color:#666;"></span>
                    </div>
                </div>
                <div class="cs-drawer-body" id="cb-quiz-body">
                    <p style="text-align:center; color:var(--muted);">Loading questions...</p>
                </div>
                <div class="cs-drawer-footer">
                    <div style="display:flex; gap:0.5rem; align-items:center;">
                        <select id="cb-quiz-qtype" style="padding:0.5rem; border:1px solid #d1d5db; border-radius:6px; font-size:0.85rem;">
                            <option value="single_choice">Single Choice</option>
                            <option value="multiple_choice">Multiple Choice</option>
                            <option value="true_false">True / False</option>
                        </select>
                        <input type="text" id="cb-quiz-qtext" placeholder="Type a question..." style="flex:1; padding:0.5rem; border:1px solid #d1d5db; border-radius:6px; font-size:0.85rem;" />
                        <button id="cb-quiz-add-q" style="padding:0.5rem 1rem; background:var(--primary, #320082); color:#fff; border:none; border-radius:6px; font-weight:700; cursor:pointer;">+ Add</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Template Name Modal -->
        <div id="cs-template-modal" class="cs-drawer-overlay">
            <div class="cs-modal">
                <div class="cs-modal-head">
                    <h3><i class="fas fa-bookmark" style="color:var(--accent, #10b981);"></i> Save as Template</h3>
                    <button class="cs-modal-close" id="cs-template-close"><i class="fas fa-times"></i></button>
                </div>
                <div class="cs-modal-body">
                    <label class="cs-modal-label">Template Name</label>
                    <input type="text" id="cs-template-input" class="cs-modal-input" placeholder="e.g. JavaScript Fundamentals" autofocus />
                    <p class="cs-modal-hint">This will save the current course structure as a reusable template.</p>
                </div>
                <div class="cs-modal-footer">
                    <button class="cs-modal-btn-cancel" id="cs-template-cancel">Cancel</button>
                    <button class="cs-modal-btn-save" id="cs-template-save"><i class="fas fa-save"></i> Save Template</button>
                </div>
            </div>
        </div>

        <!-- Toast Container -->
        <div id="toast-container"></div>
    </div>
</div>

<script src="js/course-structure.js?v=<?= filemtime(dirname(__DIR__, 3) . '/js/course-structure.js') ?>"></script>
