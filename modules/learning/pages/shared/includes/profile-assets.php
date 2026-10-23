<?php /* Shared styles for every role profile page. */ ?>
<style>
/* ─── Profile header ─────────────────────────────────────────────────────── */
.pfl-header {
    position: relative;
    isolation: isolate;
    overflow: hidden;
    background: linear-gradient(115deg, #22005a 0%, #320082 26%, #4c1d95 52%, #6d28d9 74%, #4c1d95 100%);
    background-size: 220% 220%;
    animation: pfl-gradient-drift 20s ease-in-out infinite;
    color: #fff;
    padding: 2.5rem;
    border-radius: 18px;
    margin-bottom: 1.5rem;
    box-shadow: 0 18px 38px rgba(32, 0, 130, 0.18);
}
.pfl-header::before,
.pfl-header::after {
    content: "";
    position: absolute;
    z-index: 1;
    border-radius: 50%;
    pointer-events: none;
}
.pfl-header::before {
    width: 340px;
    height: 340px;
    top: -220px;
    right: -70px;
    background: radial-gradient(circle, rgba(167, 139, 250, 0.3), transparent 70%);
}
.pfl-header::after {
    width: 230px;
    height: 230px;
    right: 130px;
    bottom: -170px;
    border: 1px solid rgba(255, 255, 255, 0.12);
}
@keyframes pfl-gradient-drift {
    0%,
    100% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
}
.pfl-header-inner { position: relative; z-index: 2; display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap; }
.pfl-avatar-wrap {
    position: relative;
    display: grid;
    place-items: center;
    width: 126px;
    height: 126px;
    flex-shrink: 0;
}
.pfl-avatar { position: relative; z-index: 2; width: 90px; height: 90px; border-radius: 50%; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 2.2rem; font-weight: 700; border: 3px solid rgba(255,255,255,0.4); overflow: hidden; }
.pfl-avatar img { width: 100%; height: 100%; object-fit: cover; }
.pfl-avatar-btn { position: absolute; z-index: 3; bottom: 18px; right: 18px; width: 30px; height: 30px; border-radius: 50%; border: 2px solid #fff; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 0.8rem; box-shadow: 0 2px 8px rgba(0,0,0,0.3); }
.pfl-avatar-btn:hover { filter: brightness(1.1); }
.pfl-seal {
    position: absolute;
    inset: 0;
    z-index: 1;
    width: 126px;
    height: 126px;
    pointer-events: none;
}
.pfl-seal-svg {
    display: block;
    width: 100%;
    height: 100%;
    overflow: visible;
    filter: drop-shadow(0 0 12px rgba(196, 181, 253, 0.22));
}
.pfl-seal-ring {
    fill: rgba(255, 255, 255, 0.035);
    stroke: rgba(255, 255, 255, 0.26);
    stroke-width: 1;
}
.pfl-seal-ring-inner {
    fill: none;
    stroke: rgba(255, 255, 255, 0.2);
    stroke-width: 1;
    stroke-dasharray: 2 4;
}
.pfl-header-meta { flex: 1; min-width: 200px; }
.pfl-header-meta h1 { margin: 0; font-size: 1.8rem; }
.pfl-header-role { margin: 0.25rem 0 0; opacity: 0.85; font-weight: 600; }
.pfl-header-sub { margin: 0.25rem 0 0; opacity: 0.65; font-size: 0.9rem; }
.pfl-photo-actions { margin-top: 0.6rem; gap: 0.5rem; }
.pfl-ghost-btn { padding: 0.3rem 0.7rem; border-radius: 6px; border: 1px solid rgba(255,255,255,0.4); background: rgba(255,255,255,0.1); color: #fff; font-size: 0.75rem; font-weight: 600; cursor: pointer; }
.pfl-ghost-btn:hover { background: rgba(255,255,255,0.2); }

/* ─── Completion meter ───────────────────────────────────────────────────── */
.pfl-completion { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }
.pfl-completion-head { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; }
.pfl-completion-head h3 { margin: 0; }
.pfl-completion-pct { margin-left: auto; font-size: 1.4rem; font-weight: 800; color: var(--primary); font-variant-numeric: tabular-nums; }
.pfl-completion-bar { height: 8px; border-radius: 999px; background: rgba(32,0,130,0.1); overflow: hidden; }
.pfl-completion-bar > span { display: block; height: 100%; border-radius: 999px; background: linear-gradient(90deg, var(--primary), #6d28d9); transition: width 0.35s ease; }
.pfl-completion-list { display: flex; flex-wrap: wrap; gap: 0.5rem; margin: 0.85rem 0 0; padding: 0; list-style: none; }
.pfl-completion-item { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.25rem 0.65rem; border-radius: 999px; font-size: 0.76rem; font-weight: 600; border: 1px solid rgba(32,0,130,0.12); color: var(--muted); background: rgba(32,0,130,0.03); }
.pfl-completion-item i { font-size: 0.72rem; opacity: 0.5; }
.pfl-completion-item.is-done { color: #059669; border-color: rgba(16,185,129,0.3); background: rgba(16,185,129,0.09); }
.pfl-completion-item.is-done i { opacity: 1; }

/* ─── Layout ─────────────────────────────────────────────────────────────── */
.pfl-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
.pfl-stack { display: grid; gap: 1.5rem; }
.pfl-section-title { display: flex; align-items: center; gap: 0.5rem; margin: 0 0 1rem; }
.pfl-section-title i { color: var(--primary); }
.pfl-section-title .pfl-count { margin-left: auto; }

/* ─── Info rows ──────────────────────────────────────────────────────────── */
.pfl-rows { padding: 0.5rem 0 0; }
.pfl-row { display: flex; justify-content: space-between; gap: 1rem; padding: 0.7rem 0; border-bottom: 1px solid rgba(0,0,0,0.06); }
.pfl-row:last-child { border-bottom: 0; }
.pfl-row-label { color: var(--muted); }
.pfl-row-value { font-weight: 600; text-align: right; word-break: break-word; }

/* ─── Editable forms ─────────────────────────────────────────────────────── */
.pfl-field { display: block; margin-bottom: 0.9rem; }
.pfl-field > span { display: block; font-size: 0.72rem; font-weight: 800; letter-spacing: 0.05em; text-transform: uppercase; color: var(--muted); margin-bottom: 0.35rem; }
.pfl-input { width: 100%; box-sizing: border-box; padding: 0.65rem 0.8rem; border: 1.5px solid rgba(32,0,130,0.15); border-radius: 10px; font-size: 0.9rem; font-family: inherit; background: rgba(32,0,130,0.03); color: inherit; outline: none; transition: border-color 0.15s ease, box-shadow 0.15s ease; }
.pfl-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(32,0,130,0.1); }
textarea.pfl-input { resize: vertical; min-height: 84px; }
.pfl-form-actions { display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 0.25rem; }
.pfl-hint { display: block; margin-top: 0.9rem; font-size: 0.75rem; color: var(--muted); }
.pfl-btn { padding: 0.5rem 1.05rem; border-radius: 9px; border: none; font-size: 0.85rem; font-weight: 700; cursor: pointer; }
.pfl-btn--primary { background: var(--primary); color: #fff; }
.pfl-btn--primary:disabled { opacity: 0.6; cursor: default; }
.pfl-btn--ghost { background: rgba(32,0,130,0.06); color: var(--primary); border: 1px solid rgba(32,0,130,0.15); }
.pfl-bio-edit { display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 0.25rem; }
.pfl-bio-text { margin: 0; color: #555; line-height: 1.6; white-space: pre-wrap; }
.pfl-bio-empty { margin: 0; color: #aaa; font-style: italic; }
.pfl-counter { font-size: 0.75rem; color: var(--muted); }

/* ─── Stat tiles ─────────────────────────────────────────────────────────── */
.pfl-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.9rem; }
.pfl-stat { border: 1px solid rgba(32,0,130,0.09); border-radius: 12px; padding: 1rem 1.1rem; background: linear-gradient(180deg, rgba(32,0,130,0.035), rgba(32,0,130,0.008)); }
.pfl-stat-label { display: flex; align-items: center; gap: 0.4rem; font-size: 0.7rem; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase; color: var(--muted); }
.pfl-stat-value { margin-top: 0.35rem; font-size: 1.7rem; font-weight: 800; line-height: 1.1; color: var(--primary); font-variant-numeric: tabular-nums; }
.pfl-stat-value.is-green { color: #059669; }
.pfl-stat-value.is-amber { color: #d97706; }
.pfl-stat-value.is-teal { color: #0d9488; }
.pfl-stat-sub { margin-top: 0.2rem; font-size: 0.76rem; color: var(--muted); }

/* ─── Security ───────────────────────────────────────────────────────────── */
.pfl-security { display: grid; grid-template-columns: 1fr; gap: 0.6rem; }
.pfl-session { display: flex; align-items: center; gap: 0.8rem; padding: 0.8rem 0.9rem; border: 1px solid rgba(32,0,130,0.1); border-radius: 12px; background: var(--surface); }
.pfl-session-icon { width: 38px; height: 38px; flex-shrink: 0; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: rgba(32,0,130,0.08); color: var(--primary); }
.pfl-session-main { flex: 1; min-width: 0; }
.pfl-session-device { font-weight: 700; font-size: 0.9rem; }
.pfl-session-meta { font-size: 0.76rem; color: var(--muted); margin-top: 0.15rem; }
.pfl-tag { padding: 0.15rem 0.55rem; border-radius: 999px; font-size: 0.7rem; font-weight: 800; white-space: nowrap; }
.pfl-tag--current { background: rgba(16,185,129,0.14); color: #059669; }
.pfl-tag--idle { background: rgba(107,114,128,0.14); color: #6b7280; }
.pfl-last-login { display: flex; align-items: center; gap: 0.6rem; padding: 0.6rem 0.9rem; border-radius: 10px; background: rgba(32,0,130,0.04); font-size: 0.85rem; margin-bottom: 0.9rem; }
.pfl-last-login i { color: var(--primary); }
.pfl-empty { margin: 0; font-size: 0.85rem; color: var(--muted); }

/* ─── Grade book ─────────────────────────────────────────────────────────── */
.profile-gb-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem; }
.profile-gb-head h3 { margin: 0; }
.profile-gb-link { display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.8rem; font-weight: 700; color: var(--primary); text-decoration: none; white-space: nowrap; }
.profile-gb-link:hover { text-decoration: underline; }
.profile-gb-stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.9rem; margin-bottom: 1.1rem; }
.profile-gb-stat { border: 1px solid rgba(32,0,130,0.09); border-radius: 12px; padding: 1rem 1.1rem; background: linear-gradient(180deg, rgba(32,0,130,0.035), rgba(32,0,130,0.008)); }
.profile-gb-stat-label { font-size: 0.7rem; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase; color: var(--muted); }
.profile-gb-stat-value { margin-top: 0.35rem; font-size: 1.7rem; font-weight: 800; line-height: 1.1; color: var(--primary); font-variant-numeric: tabular-nums; }
.profile-gb-stat-value.is-green { color: #059669; }
.profile-gb-stat-value.is-amber { color: #d97706; }
.profile-gb-stat-sub { margin-top: 0.2rem; font-size: 0.76rem; color: var(--muted); }
.profile-gb-bar { margin-top: 0.65rem; height: 6px; border-radius: 999px; background: rgba(32,0,130,0.1); overflow: hidden; }
.profile-gb-bar > span { display: block; height: 100%; border-radius: 999px; background: var(--primary); }
.profile-gb-panels { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.profile-gb-panel { border: 1px solid rgba(32,0,130,0.09); border-radius: 12px; padding: 1rem 1.1rem; background: var(--surface); }
.profile-gb-panel-head { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem; font-size: 0.74rem; font-weight: 800; letter-spacing: 0.05em; text-transform: uppercase; }
.profile-gb-panel--mastered .profile-gb-panel-head { color: #059669; }
.profile-gb-panel--gaps .profile-gb-panel-head { color: #b45309; }
.profile-gb-panel-count { margin-left: auto; padding: 0.1rem 0.55rem; border-radius: 999px; font-size: 0.72rem; font-weight: 800; }
.profile-gb-panel--mastered .profile-gb-panel-count { background: rgba(16,185,129,0.13); color: #059669; }
.profile-gb-panel--gaps .profile-gb-panel-count { background: rgba(245,158,11,0.16); color: #b45309; }
.profile-gb-chips { display: flex; flex-wrap: wrap; gap: 0.4rem; }
.profile-gb-chip { padding: 0.28rem 0.7rem; border-radius: 999px; font-size: 0.78rem; font-weight: 600; border: 1px solid transparent; }
.profile-gb-panel--mastered .profile-gb-chip { background: rgba(16,185,129,0.1); color: #059669; border-color: rgba(16,185,129,0.22); }
.profile-gb-panel--gaps .profile-gb-chip { background: rgba(245,158,11,0.1); color: #b45309; border-color: rgba(245,158,11,0.24); }
.profile-gb-panel .profile-gb-empty { margin: 0; font-size: 0.85rem; color: var(--muted); }

/* ─── Shared chips (expertise, quick links) ──────────────────────────────── */
.pfl-chips { display: flex; flex-wrap: wrap; gap: 0.45rem; }
.pfl-chip { padding: 0.32rem 0.75rem; border-radius: 999px; font-size: 0.8rem; font-weight: 600; background: rgba(32,0,130,0.06); color: var(--primary); border: 1px solid rgba(32,0,130,0.12); }
.pfl-quick-links { display: grid; gap: 0.75rem; padding: 0.5rem 0 0; }
.pfl-quick-link { display: flex; align-items: center; gap: 0.75rem; padding: 0.9rem 1rem; background: rgba(32,0,130,0.03); border: 1px solid rgba(32,0,130,0.06); border-radius: 10px; text-decoration: none; color: inherit; }
.pfl-quick-link:hover { background: rgba(32,0,130,0.07); }
.pfl-quick-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: rgba(32,0,130,0.1); color: var(--primary); flex-shrink: 0; }
.pfl-quick-link strong { display: block; }
.pfl-quick-link span { color: var(--muted); font-size: 0.85rem; }

/* ─── Activity feed ──────────────────────────────────────────────────────── */
.pfl-activity { display: flex; align-items: center; gap: 0.75rem; padding: 0.6rem 0; border-bottom: 1px solid rgba(0,0,0,0.06); }
.pfl-activity:last-child { border-bottom: 0; }
.pfl-activity-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.pfl-activity-main { flex: 1; min-width: 0; }
.pfl-activity-title { font-size: 0.9rem; font-weight: 700; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pfl-activity-date { font-size: 0.8rem; color: var(--muted); }
.pfl-status { font-size: 0.72rem; font-weight: 700; padding: 0.2rem 0.55rem; border-radius: 999px; white-space: nowrap; }

/* ─── Display preferences ────────────────────────────────────────────────── */
.pfl-prefs { display: grid; gap: 0.25rem; }
.pfl-pref { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.85rem 0; border-bottom: 1px solid rgba(0,0,0,0.06); }
.pfl-pref:last-of-type { border-bottom: 0; }
.pfl-pref-main { min-width: 0; }
.pfl-pref-label { font-weight: 700; }
.pfl-pref-hint { margin-top: 0.15rem; font-size: 0.8rem; color: var(--muted); }
.pfl-select { width: auto; min-width: 92px; }
.pfl-view-toggle { display: inline-flex; padding: 3px; border-radius: 10px; background: rgba(32,0,130,0.06); border: 1px solid rgba(32,0,130,0.12); }
.pfl-view-btn { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.4rem 0.85rem; border: 0; border-radius: 8px; background: transparent; color: var(--muted); font-family: inherit; font-size: 0.82rem; font-weight: 700; cursor: pointer; }
.pfl-view-btn:hover { color: var(--primary); }
.pfl-view-btn.is-active { background: var(--surface, #fff); color: var(--primary); box-shadow: 0 1px 4px rgba(32,0,130,0.18); }

/* ─── Responsive ─────────────────────────────────────────────────────────── */
@media (max-width: 1024px) {
    .pfl-grid { grid-template-columns: 1fr; }
    .pfl-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .profile-gb-panels { grid-template-columns: 1fr; }
    .pfl-header { padding: 2rem; }
    .pfl-header-inner { gap: 1rem; }
    .pfl-header-meta h1 { font-size: 1.55rem; }
}
@media (max-width: 880px) {
    .pfl-avatar-wrap { width: 90px; height: 90px; }
    .pfl-avatar-btn { bottom: 0; right: 0; }
    .pfl-seal { display: none; }
}
@media (max-width: 640px) {
    .pfl-header { padding: 1.75rem; }
    .pfl-stats { grid-template-columns: 1fr; }
    .profile-gb-stats { grid-template-columns: 1fr; }
    .pfl-row { flex-direction: column; gap: 0.15rem; }
    .pfl-row-value { text-align: left; }
    .pfl-pref { flex-direction: column; align-items: flex-start; gap: 0.6rem; }
}
@media (prefers-reduced-motion: reduce) {
    .pfl-header {
        animation: none;
        background-position: 50% 50%;
    }
}

/* ─── Dark mode ──────────────────────────────────────────────────────────── */
[data-theme="dark"] .pfl-row { border-bottom-color: rgba(255,255,255,0.08); }
[data-theme="dark"] .pfl-stat,
[data-theme="dark"] .profile-gb-stat { border-color: rgba(255,255,255,0.08); background: rgba(255,255,255,0.03); }
[data-theme="dark"] .pfl-input { background: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.12); }
[data-theme="dark"] .pfl-session { border-color: rgba(255,255,255,0.08); }
[data-theme="dark"] .pfl-bio-text { color: #cbd5e1; }
[data-theme="dark"] .pfl-quick-link { background: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.08); }
[data-theme="dark"] .profile-gb-panel { border-color: rgba(255,255,255,0.08); }
[data-theme="dark"] .pfl-activity { border-bottom-color: rgba(255,255,255,0.08); }
[data-theme="dark"] .pfl-pref { border-bottom-color: rgba(255,255,255,0.08); }
[data-theme="dark"] .pfl-view-toggle { background: rgba(255,255,255,0.06); border-color: rgba(255,255,255,0.12); }
[data-theme="dark"] .pfl-view-btn.is-active { background: rgba(255,255,255,0.12); color: #fff; }
</style>
