<?php
include_once __DIR__ . '/../../classes/Employee.php';
$employeeClass = new Employee();
$instructorName = $employeeClass->getEmployeeName();
?>

<div class="module-content skill-page">
    <div class="home-dashboard">
        <section class="home-hero" aria-labelledby="isg-title">
            <div class="home-hero-main">
                <h1 id="isg-title">Trainee Skill Gaps</h1>
                <p>See which skills your trainees are missing most and identify at-risk trainees.</p>
            </div>
            <div class="home-hero-aside">
                <div class="home-hero-chips">
                    <span class="home-hero-chip"><i class="fas fa-users"></i> Trainees: <b id="isg-total-learners">&mdash;</b></span>
                    <span class="home-hero-chip"><i class="fas fa-puzzle-piece"></i> Skills: <b id="isg-total-skills">&mdash;</b></span>
                    <span class="home-hero-chip"><i class="fas fa-triangle-exclamation"></i> Top gap rate: <b id="isg-top-gap-rate">&mdash;</b></span>
                    <span class="home-hero-chip"><i class="fas fa-user-slash"></i> Below 50%: <b id="isg-at-risk">&mdash;</b></span>
                </div>
            </div>
        </section>

        <!-- Toolbar -->
        <div class="isg-toolbar">
            <div class="isg-search">
                <i class="fas fa-search"></i>
                <input type="search" id="isg-search-input" placeholder="Search skills, trainee names..." aria-label="Search skill gaps" />
            </div>
            <div class="isg-view-toggle">
                <button type="button" class="active" data-view="grid" title="Grid view"><i class="fas fa-th"></i></button>
                <button type="button" data-view="list" title="List view"><i class="fas fa-list"></i></button>
            </div>
            <select id="isg-page-size" class="isg-page-size" data-display-pref="page_size" title="Items per page">
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
                <i class="fas fa-user-graduate"></i> By Trainee
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
        var rows = [['Type', 'Name', 'Details', 'Gap Rate %', 'Missing Count', 'Total Trainees']];
        if (currentFilter === 'skills') {
            (rawData.skills || []).forEach(function(s) {
                rows.push(['Skill', s.name, s.description || '', s.gap_rate, s.missing_count, s.total_learners]);
            });
        } else {
            (rawData.learners || []).forEach(function(l) {
                rows.push(['Trainee', l.name, l.acquired_count + ' acquired, ' + l.gap_count + ' gaps', l.percentage, l.gap_count, l.total_skills]);
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
                       (s.missing_learners || []).some(function(n) {
                           var name = (n && typeof n === 'object') ? (n.name || '') : (n || '');
                           return String(name).toLowerCase().indexOf(q) > -1;
                       });
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
            html += '<div style="font-size:0.78rem; color:var(--muted);">' + s.missing_count + ' of ' + s.total_learners + ' trainees missing this skill</div>';
            if (s.missing_learners && s.missing_learners.length) {
                html += '<div class="isg-missing-list">';
                s.missing_learners.forEach(function(learner) {
                    var missingName = esc(learner.name || learner);
                    var missingTag = learner.profile_url
                        ? '<a href="' + esc(learner.profile_url) + '">' + missingName + '</a>'
                        : missingName;
                    html += '<span class="isg-missing-tag">' + missingTag + '</span>';
                });
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

        countEl.textContent = filtered.length + ' trainee' + (filtered.length !== 1 ? 's' : '');

        if (!filtered.length) {
            contentEl.innerHTML = '<div class="isg-empty"><i class="fas fa-users"></i><h4>No Trainees Found</h4><p>' + (searchInput.value ? 'No trainees match your search.' : 'No trainees are enrolled in your courses yet.') + '</p></div>';
            paginationEl.style.display = 'none';
            return;
        }

        var html = '<div class="isg-section-title"><i class="fas fa-user-graduate" style="color:var(--primary);"></i> Trainees by Skill Coverage</div>';
        html += '<div class="isg-learner-grid' + (isListView ? ' list-view' : '') + '">';
        paged.forEach(function(l) {
            var pctColor = l.percentage >= 70 ? '#10b981' : l.percentage >= 40 ? '#f59e0b' : '#ef4444';
            html += '<div class="isg-learner-card">';
            var learnerName = l.profile_url
                ? '<a href="' + esc(l.profile_url) + '">' + esc(l.name) + '</a>'
                : esc(l.name);
            html += '<div class="isg-learner-top"><span class="isg-learner-name">' + learnerName + '</span><span class="isg-learner-pct" style="color:' + pctColor + ';">' + l.percentage + '%</span></div>';
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
