<?php
include_once __DIR__ . '/../../classes/Employee.php';
$employeeClass = new Employee();
$instructorName = $employeeClass->getEmployeeName();
?>

<style>
/* ── Header ─────────────────────────────────────────────── */
.isg-header { margin-bottom: 1.5rem; }
.isg-header h2 { margin: 0 0 0.35rem; font-size: 1.35rem; font-weight: 800; color: var(--text); }
.isg-header p { margin: 0; font-size: 0.9rem; color: var(--muted); }

/* ── Summary Cards ──────────────────────────────────────── */
.isg-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.isg-summary-card {
    padding: 1.25rem;
    border-radius: 14px;
    text-align: center;
    border: 1px solid rgba(32,0,130,0.08);
    background: var(--surface, #fff);
    transition: transform 0.2s, box-shadow 0.2s;
}
.isg-summary-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.06); }
.isg-summary-card .isg-icon {
    width: 44px; height: 44px; border-radius: 12px;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 1.1rem; margin-bottom: 0.65rem;
}
.isg-summary-card .isg-value { font-size: 1.75rem; font-weight: 800; line-height: 1; margin-bottom: 0.25rem; }
.isg-summary-card .isg-label { font-size: 0.78rem; color: var(--muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; }

/* ── Toolbar (elearning-aligned) ────────────────────────── */
.isg-toolbar {
    position: sticky;
    top: calc(var(--header-height, 60px) + 0.75rem);
    z-index: 100;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    background: linear-gradient(135deg, rgba(32,0,130,0.08), rgba(81,70,183,0.06));
    border-radius: 12px;
    border: 1.5px solid rgba(32,0,130,0.15);
    margin-bottom: 1rem;
    flex-wrap: wrap;
    box-shadow: 0 4px 16px rgba(32,0,130,0.08);
    backdrop-filter: blur(10px);
}
.isg-search {
    flex: 1;
    min-width: 200px;
    position: relative;
}
.isg-search input {
    width: 100%;
    padding: 0.6rem 1rem 0.6rem 2.5rem;
    border: 1.5px solid rgba(32,0,130,0.15);
    border-radius: 10px;
    background: var(--surface, #fff);
    font-size: 0.88rem;
    color: var(--text);
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
    box-sizing: border-box;
}
.isg-search input:focus {
    border-color: var(--primary, #320082);
    box-shadow: 0 0 0 3px rgba(32,0,130,0.08);
}
.isg-search input::placeholder { color: rgba(32,0,130,0.4); }
.isg-search i {
    position: absolute; left: 0.85rem; top: 50%;
    transform: translateY(-50%); color: var(--muted); font-size: 0.85rem;
}
.isg-view-toggle {
    display: flex;
    border: 1.5px solid rgba(32,0,130,0.15);
    border-radius: 8px;
    overflow: hidden;
}
.isg-view-toggle button {
    padding: 0.45rem 0.7rem;
    border: none; background: transparent;
    color: var(--muted); cursor: pointer;
    font-size: 0.85rem; transition: all 0.15s;
}
.isg-view-toggle button.active { background: var(--primary); color: #fff; }
.isg-view-toggle button:hover:not(.active) { color: var(--primary); }
.isg-count { font-size: 0.8rem; color: var(--muted); white-space: nowrap; }
.isg-page-size {
    border: 1.5px solid rgba(32,0,130,0.15);
    background: var(--surface, #fff);
    color: var(--text);
    border-radius: 8px;
    padding: 0.45rem 0.6rem;
    font-size: 0.78rem; font-weight: 600;
    cursor: pointer; outline: none;
}
.isg-export-btn, .isg-print-btn {
    padding: 0.5rem 1rem;
    border-radius: 999px;
    font-weight: 700; font-size: 0.82rem;
    cursor: pointer; white-space: nowrap;
    display: inline-flex; align-items: center; gap: 0.4rem;
    transition: opacity 0.2s;
}
.isg-export-btn {
    background: rgba(32,0,130,0.08);
    color: var(--primary);
    border: 1.5px solid rgba(32,0,130,0.15);
}
.isg-export-btn:hover { background: rgba(32,0,130,0.14); }
.isg-print-btn {
    background: var(--primary);
    color: #fff;
    border: none;
}
.isg-print-btn:hover { opacity: 0.85; }

/* ── Filter Tabs (elearning-aligned catalog-tabs) ───────── */
.isg-filter-tabs {
    display: flex;
    gap: 6px;
    margin-bottom: 1rem;
    background: rgba(32,0,130,0.04);
    border-radius: 12px;
    padding: 4px;
}
.isg-filter-tab {
    flex: 1 1 0;
    min-width: 0;
    padding: 0.55rem 0.5rem;
    border: none;
    border-radius: 8px;
    background: rgba(255,255,255,0.7);
    color: var(--text, #333);
    font-size: 0.78rem; font-weight: 600;
    cursor: pointer; white-space: nowrap;
    transition: all 0.2s;
    display: flex; align-items: center; justify-content: center; gap: 0.35rem;
}
.isg-filter-tab:hover { background: rgba(255,255,255,0.95); }
.isg-filter-tab.active {
    background: var(--primary, #320082);
    color: #fff;
    box-shadow: 0 2px 8px rgba(32,0,130,0.3);
}
.isg-filter-count {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 22px; height: 20px; padding: 0 6px;
    border-radius: 999px;
    background: rgba(32,0,130,0.1);
    font-size: 0.7rem; font-weight: 700;
    color: var(--text, #333);
}
.isg-filter-tab.active .isg-filter-count {
    background: rgba(255,255,255,0.25);
    color: #fff;
}

/* ── Section Title ──────────────────────────────────────── */
.isg-section-title {
    font-size: 1.05rem; font-weight: 700; color: var(--text);
    margin: 1.5rem 0 0.75rem; display: flex; align-items: center; gap: 0.5rem;
}

/* ── Skill Bars ─────────────────────────────────────────── */
.isg-skill-bar {
    background: var(--surface, #fff);
    border: 1px solid rgba(32,0,130,0.08);
    border-radius: 12px;
    padding: 1rem 1.1rem;
    margin-bottom: 0.75rem;
    transition: transform 0.15s, box-shadow 0.15s;
}
.isg-skill-bar:hover { transform: translateY(-1px); box-shadow: 0 4px 16px rgba(0,0,0,0.05); }
.isg-skill-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.6rem; }
.isg-skill-name { font-weight: 700; font-size: 0.92rem; color: var(--text); }
.isg-skill-rate { font-size: 0.82rem; font-weight: 700; }
.isg-skill-rate.high { color: #ef4444; }
.isg-skill-rate.medium { color: #f59e0b; }
.isg-skill-rate.low { color: #10b981; }
.isg-bar-track { height: 8px; background: rgba(32,0,130,0.06); border-radius: 999px; overflow: hidden; margin-bottom: 0.5rem; }
.isg-bar-fill { height: 100%; border-radius: 999px; transition: width 0.5s ease; }
.isg-bar-fill.high { background: #ef4444; }
.isg-bar-fill.medium { background: #f59e0b; }
.isg-bar-fill.low { background: #10b981; }
.isg-missing-list { display: flex; flex-wrap: wrap; gap: 0.35rem; margin-top: 0.4rem; }
.isg-missing-tag {
    padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.72rem; font-weight: 600;
    background: rgba(239,68,68,0.08); color: #dc2626;
}
.isg-missing-more {
    padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.72rem; font-weight: 600;
    background: rgba(32,0,130,0.06); color: var(--muted);
}

/* ── Learner Grid ───────────────────────────────────────── */
.isg-learner-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}
.isg-learner-grid.list-view { grid-template-columns: 1fr; gap: 0.75rem; }
.isg-learner-card {
    background: var(--surface, #fff);
    border: 1px solid rgba(32,0,130,0.08);
    border-radius: 12px;
    padding: 1rem 1.1rem;
    transition: transform 0.15s, box-shadow 0.15s;
}
.isg-learner-card:hover { transform: translateY(-1px); box-shadow: 0 4px 16px rgba(0,0,0,0.05); }
.isg-learner-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
.isg-learner-name { font-weight: 700; font-size: 0.92rem; color: var(--text); }
.isg-learner-pct { font-size: 0.85rem; font-weight: 700; }
.isg-learner-bar { height: 6px; background: rgba(32,0,130,0.06); border-radius: 999px; overflow: hidden; margin-bottom: 0.4rem; }
.isg-learner-fill { height: 100%; border-radius: 999px; background: linear-gradient(90deg, var(--primary), #7c3aed); transition: width 0.5s ease; }
.isg-learner-stats { font-size: 0.78rem; color: var(--muted); }

/* ── Empty & Loading ────────────────────────────────────── */
.isg-empty { text-align: center; padding: 3rem 1rem; color: var(--muted); }
.isg-empty i { font-size: 2.5rem; color: var(--border); margin-bottom: 1rem; display: block; }
.isg-empty h4 { margin: 0 0 0.5rem; color: var(--text); }
.isg-loading { text-align: center; padding: 3rem 1rem; color: var(--muted); }
.isg-loading i { font-size: 1.5rem; margin-bottom: 0.75rem; display: block; animation: isg-spin 1s linear infinite; }
@keyframes isg-spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

/* ── Pagination (elearning-aligned) ─────────────────────── */
.isg-pagination-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(32,0,130,0.08);
}
.isg-pagination-row .page-btn {
    border: 1px solid rgba(32,0,130,0.2);
    background: transparent;
    color: var(--primary);
    border-radius: 999px;
    padding: 0.65rem 0.95rem;
    cursor: pointer;
    font-weight: 700;
}
.isg-pagination-row .page-btn:disabled { opacity: 0.45; cursor: not-allowed; }
.isg-pagination-row .page-indicator {
    color: rgba(32,0,130,0.8);
    font-size: 0.8rem;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

/* ── Print Styles ───────────────────────────────────────── */
@media print {
    .isg-toolbar, .isg-filter-tabs, .isg-pagination-row, .isg-header p { display: none !important; }
    .isg-summary-card { break-inside: avoid; }
    .isg-skill-bar, .isg-learner-card { break-inside: avoid; box-shadow: none !important; }
}

/* ── Dark Mode ──────────────────────────────────────────── */
[data-theme="dark"] .isg-summary-card,
[data-theme="dark"] .isg-skill-bar,
[data-theme="dark"] .isg-learner-card {
    background: var(--surface) !important;
    border-color: rgba(51,65,85,0.5);
    color: var(--text);
}
[data-theme="dark"] .isg-summary-card .isg-value,
[data-theme="dark"] .isg-skill-name,
[data-theme="dark"] .isg-learner-name,
[data-theme="dark"] .isg-section-title { color: var(--text); }
[data-theme="dark"] .isg-summary-card .isg-label,
[data-theme="dark"] .isg-learner-stats,
[data-theme="dark"] .isg-count { color: var(--accent); }
[data-theme="dark"] .isg-bar-track,
[data-theme="dark"] .isg-learner-bar { background: rgba(255,255,255,0.08); }
[data-theme="dark"] .isg-missing-more { background: rgba(255,255,255,0.06); color: var(--accent); }
[data-theme="dark"] .isg-filter-tab { background: rgba(255,255,255,0.06); color: var(--text); }
[data-theme="dark"] .isg-filter-tab:hover { background: rgba(255,255,255,0.12); }
[data-theme="dark"] .isg-filter-tab.active { background: var(--primary); color: #fff; }
[data-theme="dark"] .isg-filter-count { background: rgba(255,255,255,0.08); color: var(--text); }
[data-theme="dark"] .isg-filter-tab.active .isg-filter-count { background: rgba(255,255,255,0.2); color: #fff; }
[data-theme="dark"] .isg-toolbar {
    background: linear-gradient(135deg, rgba(32,0,130,0.25), rgba(81,70,183,0.15));
    border-color: rgba(255,255,255,0.08);
}
[data-theme="dark"] .isg-search input,
[data-theme="dark"] .isg-page-size {
    background: rgba(30,41,59,1) !important;
    color: var(--text) !important;
    border-color: rgba(255,255,255,0.1) !important;
}
[data-theme="dark"] .isg-export-btn {
    background: rgba(255,255,255,0.06);
    border-color: rgba(255,255,255,0.1);
    color: var(--text);
}
[data-theme="dark"] .isg-export-btn:hover { background: rgba(255,255,255,0.12); }
[data-theme="dark"] .isg-print-btn { background: var(--primary); color: #fff; }
[data-theme="dark"] .isg-pagination-row { border-top-color: rgba(255,255,255,0.08); }
[data-theme="dark"] .isg-pagination-row .page-btn { border-color: rgba(255,255,255,0.12); color: var(--text); }
[data-theme="dark"] .isg-pagination-row .page-indicator { color: var(--accent); }
[data-theme="dark"] .isg-empty i { color: rgba(255,255,255,0.15); }
[data-theme="dark"] .isg-empty h4 { color: var(--text); }

/* ── Responsive ─────────────────────────────────────────── */
@media (max-width: 768px) {
    .isg-learner-grid { grid-template-columns: 1fr; }
    .isg-summary { grid-template-columns: repeat(2, 1fr); }
    .isg-toolbar { flex-wrap: nowrap; }
    .isg-search { min-width: 0; flex: 1; }
}
@media (max-width: 480px) {
    .isg-summary { grid-template-columns: 1fr; }
}
</style>

<div class="module-content">
    <div class="isg-header">
        <h2><i class="fas fa-users-rectangle" style="color:var(--primary); margin-right:0.4rem;"></i> Learner Skill Gaps</h2>
        <p>See which skills your learners are missing most and identify at-risk students.</p>
    </div>

    <div class="isg-summary" id="isg-summary">
        <div class="isg-summary-card">
            <div class="isg-icon" style="background:rgba(32,0,130,0.08); color:var(--primary);"><i class="fas fa-users"></i></div>
            <div class="isg-value" id="isg-total-learners" style="color:var(--primary);">—</div>
            <div class="isg-label">My Learners</div>
        </div>
        <div class="isg-summary-card">
            <div class="isg-icon" style="background:rgba(99,102,241,0.08); color:#6366f1;"><i class="fas fa-puzzle-piece"></i></div>
            <div class="isg-value" id="isg-total-skills" style="color:#6366f1;">—</div>
            <div class="isg-label">Total Skills</div>
        </div>
        <div class="isg-summary-card">
            <div class="isg-icon" style="background:rgba(239,68,68,0.08); color:#ef4444;"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="isg-value" id="isg-top-gap-rate" style="color:#ef4444;">—</div>
            <div class="isg-label">Highest Gap Rate</div>
        </div>
        <div class="isg-summary-card">
            <div class="isg-icon" style="background:rgba(245,158,11,0.08); color:#f59e0b;"><i class="fas fa-user-slash"></i></div>
            <div class="isg-value" id="isg-at-risk" style="color:#f59e0b;">—</div>
            <div class="isg-label">Below 50%</div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="isg-toolbar">
        <div class="isg-search">
            <i class="fas fa-search"></i>
            <input type="search" id="isg-search-input" placeholder="Search skills, learner names..." aria-label="Search skill gaps" />
        </div>
        <div class="isg-view-toggle">
            <button type="button" class="active" data-view="grid" title="Grid view"><i class="fas fa-th"></i></button>
            <button type="button" data-view="list" title="List view"><i class="fas fa-list"></i></button>
        </div>
        <select id="isg-page-size" class="isg-page-size" title="Items per page">
            <option value="12">12 per page</option>
            <option value="24">24 per page</option>
            <option value="36">36 per page</option>
        </select>
        <button type="button" class="isg-export-btn" id="isg-export-btn" title="Export to CSV"><i class="fas fa-download"></i> Export</button>
        <button type="button" class="isg-print-btn" id="isg-print-btn" title="Print report"><i class="fas fa-print"></i> Print</button>
        <span class="isg-count" id="isg-count"></span>
    </div>

    <!-- Filter Tabs -->
    <div class="isg-filter-tabs" id="isg-filter-tabs">
        <button type="button" class="isg-filter-tab active" data-filter="skills">
            <i class="fas fa-chart-bar"></i> By Skill
            <span class="isg-filter-count" id="isg-skill-count">0</span>
        </button>
        <button type="button" class="isg-filter-tab" data-filter="learners">
            <i class="fas fa-user-graduate"></i> By Learner
            <span class="isg-filter-count" id="isg-learner-count">0</span>
        </button>
    </div>

    <div id="isg-content">
        <div class="isg-loading"><i class="fas fa-spinner"></i><p>Loading skill gap data...</p></div>
    </div>

    <!-- Pagination -->
    <div class="isg-pagination-row" id="isg-pagination" style="display:none;">
        <button type="button" class="page-btn" data-action="prev" disabled>Prev</button>
        <span class="page-indicator" id="isg-page-indicator">Page 1 of 1</span>
        <button type="button" class="page-btn" data-action="next">Next</button>
    </div>
</div>

<script>
(function() {
    'use strict';

    var contentEl = document.getElementById('isg-content');
    var searchInput = document.getElementById('isg-search-input');
    var countEl = document.getElementById('isg-count');
    var paginationEl = document.getElementById('isg-pagination');
    var pageIndicator = document.getElementById('isg-page-indicator');
    var pageSizeSelect = document.getElementById('isg-page-size');
    var skillCountEl = document.getElementById('isg-skill-count');
    var learnerCountEl = document.getElementById('isg-learner-count');

    var rawData = null;
    var currentFilter = 'skills';
    var currentPage = 1;
    var PAGE_SIZE = 12;
    var isListView = false;

    // ── Helpers ────────────────────────────────────────────
    function esc(s) { var d = document.createElement('div'); d.appendChild(document.createTextNode(s || '')); return d.innerHTML; }

    // ── Filter Tabs ────────────────────────────────────────
    document.querySelectorAll('.isg-filter-tab').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.isg-filter-tab').forEach(function(b) { b.classList.remove('active'); });
            btn.classList.add('active');
            currentFilter = btn.dataset.filter;
            currentPage = 1;
            render();
        });
    });

    // ── View Toggle ────────────────────────────────────────
    document.querySelectorAll('.isg-view-toggle button').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.isg-view-toggle button').forEach(function(b) { b.classList.remove('active'); });
            btn.classList.add('active');
            isListView = btn.dataset.view === 'list';
            render();
        });
    });

    // ── Search ─────────────────────────────────────────────
    searchInput.addEventListener('input', function() { currentPage = 1; render(); });

    // ── Page Size ──────────────────────────────────────────
    pageSizeSelect.addEventListener('change', function() {
        PAGE_SIZE = parseInt(this.value, 10) || 12;
        currentPage = 1;
        render();
    });

    // ── Pagination ─────────────────────────────────────────
    paginationEl.addEventListener('click', function(e) {
        var btn = e.target.closest('[data-action]');
        if (!btn || btn.disabled) return;
        if (btn.dataset.action === 'prev' && currentPage > 1) currentPage--;
        if (btn.dataset.action === 'next') currentPage++;
        render();
    });

    // ── Export CSV ─────────────────────────────────────────
    document.getElementById('isg-export-btn').addEventListener('click', function() {
        if (!rawData) return;
        var rows = [['Type', 'Name', 'Details', 'Gap Rate %', 'Missing Count', 'Total Learners']];
        if (currentFilter === 'skills') {
            (rawData.skills || []).forEach(function(s) {
                rows.push(['Skill', s.name, s.description || '', s.gap_rate, s.missing_count, s.total_learners]);
            });
        } else {
            (rawData.learners || []).forEach(function(l) {
                rows.push(['Learner', l.name, l.acquired_count + ' acquired, ' + l.gap_count + ' gaps', l.percentage, l.gap_count, l.total_skills]);
            });
        }
        var csv = rows.map(function(r) { return r.map(function(c) { return '"' + String(c).replace(/"/g, '""') + '"'; }).join(','); }).join('\n');
        var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'skill-gap-report-' + currentFilter + '-' + new Date().toISOString().slice(0, 10) + '.csv';
        a.click();
        URL.revokeObjectURL(url);
    });

    // ── Print ──────────────────────────────────────────────
    document.getElementById('isg-print-btn').addEventListener('click', function() { window.print(); });

    // ── Render ─────────────────────────────────────────────
    function render() {
        if (!rawData) return;
        if (currentFilter === 'skills') renderSkills();
        else renderLearners();
    }

    function getFilteredSkills() {
        var q = (searchInput.value || '').toLowerCase().trim();
        var skills = rawData.skills || [];
        if (q) {
            skills = skills.filter(function(s) {
                return s.name.toLowerCase().indexOf(q) > -1 ||
                       (s.description || '').toLowerCase().indexOf(q) > -1 ||
                       (s.missing_learners || []).some(function(n) { return n.toLowerCase().indexOf(q) > -1; });
            });
        }
        return skills;
    }

    function getFilteredLearners() {
        var q = (searchInput.value || '').toLowerCase().trim();
        var learners = rawData.learners || [];
        if (q) {
            learners = learners.filter(function(l) {
                return l.name.toLowerCase().indexOf(q) > -1;
            });
        }
        return learners;
    }

    function renderSkills() {
        var filtered = getFilteredSkills();
        var totalPages = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE));
        currentPage = Math.min(currentPage, totalPages);
        var start = (currentPage - 1) * PAGE_SIZE;
        var paged = filtered.slice(start, start + PAGE_SIZE);

        countEl.textContent = filtered.length + ' skill' + (filtered.length !== 1 ? 's' : '');

        if (!filtered.length) {
            contentEl.innerHTML = '<div class="isg-empty"><i class="fas fa-check-circle"></i><h4>No Skills Found</h4><p>' + (searchInput.value ? 'No skills match your search.' : 'No skills have been assigned to courses yet.') + '</p></div>';
            paginationEl.style.display = 'none';
            return;
        }

        var html = '<div class="isg-section-title"><i class="fas fa-chart-bar" style="color:var(--primary);"></i> Skills by Gap Rate</div>';
        paged.forEach(function(s) {
            var rateClass = s.gap_rate >= 70 ? 'high' : s.gap_rate >= 40 ? 'medium' : 'low';
            html += '<div class="isg-skill-bar">';
            html += '<div class="isg-skill-top"><span class="isg-skill-name">' + esc(s.name) + '</span><span class="isg-skill-rate ' + rateClass + '">' + s.gap_rate + '% gap</span></div>';
            html += '<div class="isg-bar-track"><div class="isg-bar-fill ' + rateClass + '" style="width:' + s.gap_rate + '%;"></div></div>';
            html += '<div style="font-size:0.78rem; color:var(--muted);">' + s.missing_count + ' of ' + s.total_learners + ' learners missing this skill</div>';
            if (s.missing_learners && s.missing_learners.length) {
                html += '<div class="isg-missing-list">';
                s.missing_learners.forEach(function(name) { html += '<span class="isg-missing-tag">' + esc(name) + '</span>'; });
                if (s.more_missing > 0) html += '<span class="isg-missing-more">+' + s.more_missing + ' more</span>';
                html += '</div>';
            }
            html += '</div>';
        });
        contentEl.innerHTML = html;
        updatePagination(totalPages);
    }

    function renderLearners() {
        var filtered = getFilteredLearners();
        var totalPages = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE));
        currentPage = Math.min(currentPage, totalPages);
        var start = (currentPage - 1) * PAGE_SIZE;
        var paged = filtered.slice(start, start + PAGE_SIZE);

        countEl.textContent = filtered.length + ' learner' + (filtered.length !== 1 ? 's' : '');

        if (!filtered.length) {
            contentEl.innerHTML = '<div class="isg-empty"><i class="fas fa-users"></i><h4>No Learners Found</h4><p>' + (searchInput.value ? 'No learners match your search.' : 'No learners are enrolled in your courses yet.') + '</p></div>';
            paginationEl.style.display = 'none';
            return;
        }

        var html = '<div class="isg-section-title"><i class="fas fa-user-graduate" style="color:var(--primary);"></i> Learners by Skill Coverage</div>';
        html += '<div class="isg-learner-grid' + (isListView ? ' list-view' : '') + '">';
        paged.forEach(function(l) {
            var pctColor = l.percentage >= 70 ? '#10b981' : l.percentage >= 40 ? '#f59e0b' : '#ef4444';
            html += '<div class="isg-learner-card">';
            html += '<div class="isg-learner-top"><span class="isg-learner-name">' + esc(l.name) + '</span><span class="isg-learner-pct" style="color:' + pctColor + ';">' + l.percentage + '%</span></div>';
            html += '<div class="isg-learner-bar"><div class="isg-learner-fill" style="width:' + l.percentage + '%;"></div></div>';
            html += '<div class="isg-learner-stats">' + l.acquired_count + ' acquired &bull; ' + l.gap_count + ' gaps &bull; ' + l.total_skills + ' total</div>';
            html += '</div>';
        });
        html += '</div>';
        contentEl.innerHTML = html;
        updatePagination(totalPages);
    }

    function updatePagination(totalPages) {
        if (totalPages <= 1) { paginationEl.style.display = 'none'; return; }
        paginationEl.style.display = '';
        pageIndicator.textContent = 'Page ' + currentPage + ' of ' + totalPages;
        paginationEl.querySelector('[data-action="prev"]').disabled = currentPage <= 1;
        paginationEl.querySelector('[data-action="next"]').disabled = currentPage >= totalPages;
    }

    // ── Load Data ──────────────────────────────────────────
    fetch('pages/instructor/ajax/get-instructor-skill-gap.php', { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (!d.success) {
                contentEl.innerHTML = '<div class="isg-empty"><i class="fas fa-exclamation-circle"></i><h4>Error</h4><p>' + esc(d.message) + '</p></div>';
                return;
            }
            rawData = d;

            document.getElementById('isg-total-learners').textContent = d.total_learners;
            document.getElementById('isg-total-skills').textContent = d.total_skills;
            var topRate = d.skills.length ? d.skills[0].gap_rate + '%' : '—';
            document.getElementById('isg-top-gap-rate').textContent = topRate;
            var atRisk = d.learners.filter(function(l) { return l.percentage < 50; }).length;
            document.getElementById('isg-at-risk').textContent = atRisk;

            skillCountEl.textContent = d.skills.length;
            learnerCountEl.textContent = d.learners.length;

            render();
        })
        .catch(function() {
            contentEl.innerHTML = '<div class="isg-empty"><i class="fas fa-wifi"></i><h4>Network Error</h4><p>Unable to load data.</p></div>';
        });
})();
</script>
