(function() {
    'use strict';

    function initCatalogBrowse() {
    var grid = document.getElementById('catalog-grid');
    if (!grid || grid.dataset.browseInitialized === 'true') return;
    grid.dataset.browseInitialized = 'true';

    // Use global showToast from header.php
    var showToast = window.showToast || function(m,t) { console.log('[' + t + '] ' + m); };

    var PAGE_SIZE = parseInt(window.LD_DEFAULT_PAGE_SIZE, 10) || 12;
    var currentPage = 1;
    var currentMasterTab = 'elearning';
    var currentTab = 'course';
    var currentSort = 'default';
    var searchQuery = '';
    var viewMode = 'grid';
    var allRecommendations = [];

    var emptyState = document.getElementById('catalog-empty');
    var countEl = document.getElementById('catalog-count');
    var pageIndicator = document.getElementById('page-indicator');
    var catalogPagination = document.getElementById('catalog-pagination');
    var pageNumbers = document.getElementById('catalog-page-numbers');
    var searchInput = document.getElementById('catalog-search-input');
    var allCards = Array.from(grid.querySelectorAll('.catalog-card'));

    function getFilteredCards() {
        var filtered = allCards.filter(function(card) {
            var elearningTypes = ['course', 'module', 'lesson', 'quiz'];
            var trainingTypes = ['learning-path', 'program', 'video-conference'];
            var masterMatch = currentMasterTab === 'elearning'
                ? elearningTypes.indexOf(card.dataset.type) !== -1
                : trainingTypes.indexOf(card.dataset.type) !== -1;
            var typeMatch;
            if (currentTab === 'all') {
                typeMatch = true;
            } else if (currentTab === 'enrolled') {
                typeMatch = card.dataset.enrolled === 'true';
            } else {
                typeMatch = card.dataset.type === currentTab;
            }
            var searchMatch = !searchQuery || (card.dataset.title + ' ' + card.dataset.desc + ' ' + card.dataset.category).toLowerCase().indexOf(searchQuery) !== -1;
            return masterMatch && typeMatch && searchMatch;
        });

        // Sort
        if (currentSort === 'name-asc' || currentSort === 'name-desc') {
            filtered.sort(function(a, b) {
                var nameA = (a.dataset.title || '').toLowerCase();
                var nameB = (b.dataset.title || '').toLowerCase();
                var cmp = nameA < nameB ? -1 : nameA > nameB ? 1 : 0;
                return currentSort === 'name-desc' ? -cmp : cmp;
            });
        } else if (currentSort === 'newest') {
            filtered.sort(function(a, b) {
                // Use data-id as a proxy for creation order (higher = newer)
                return (parseInt(b.dataset.id) || 0) - (parseInt(a.dataset.id) || 0);
            });
        } else {
            // Default: push enrolled items to the bottom
            var notEnrolled = [];
            var enrolled = [];
            filtered.forEach(function(card) {
                if (card.dataset.enrolled === 'true') enrolled.push(card);
                else notEnrolled.push(card);
            });
            filtered = notEnrolled.concat(enrolled);
        }
        return filtered;
    }

    function renderCards() {
        var filtered = getFilteredCards();
        var total = filtered.length;
        var totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
        if (currentPage > totalPages) currentPage = totalPages;
        var start = (currentPage - 1) * PAGE_SIZE;
        var end = start + PAGE_SIZE;

        // Reorder DOM: hide all, then append visible ones in the correct order
        allCards.forEach(function(c) { c.style.display = 'none'; });
        var visible = filtered.slice(start, end);
        visible.forEach(function(c) {
            c.style.display = '';
            grid.appendChild(c);
        });

        emptyState.style.display = total === 0 ? 'block' : 'none';
        countEl.textContent = total + ' item' + (total !== 1 ? 's' : '');
        pageIndicator.textContent = 'Page ' + currentPage + ' of ' + totalPages;
        if (pageNumbers) {
            pageNumbers.innerHTML = '';
            for (var pageNumber = 1; pageNumber <= totalPages; pageNumber++) {
                var pageButton = document.createElement('button');
                pageButton.type = 'button';
                pageButton.className = 'page-btn' + (pageNumber === currentPage ? ' active' : '');
                pageButton.dataset.page = String(pageNumber);
                pageButton.textContent = String(pageNumber);
                pageButton.setAttribute('aria-label', 'Page ' + pageNumber);
                if (pageNumber === currentPage) pageButton.setAttribute('aria-current', 'page');
                pageNumbers.appendChild(pageButton);
            }
        }
        if (catalogPagination) {
            var prevButton = catalogPagination.querySelector('[data-action="prev"]');
            var nextButton = catalogPagination.querySelector('[data-action="next"]');
            if (prevButton) prevButton.disabled = currentPage <= 1;
            if (nextButton) nextButton.disabled = currentPage >= totalPages;
            catalogPagination.style.display = totalPages > 1 ? '' : 'none';
        }
    }

    // ---- Recommendations ----
    var recTrack = document.getElementById('recommendations-track');
    var recSection = document.getElementById('recommendations-section');
    var recPrev = document.getElementById('rec-prev');
    var recNext = document.getElementById('rec-next');

    function renderRecommendations() {
        if (currentMasterTab !== 'elearning') {
            recSection.style.display = 'none';
            return;
        }
        if (!allRecommendations.length) {
            recSection.style.display = 'none';
            return;
        }
        var filtered = allRecommendations;
        if (currentTab !== 'all') {
            filtered = allRecommendations.filter(function(rec) { return rec.type === currentTab; });
        }
        if (filtered.length === 0) {
            recSection.style.display = 'none';
            return;
        }
        var html = '';
        filtered.forEach(function(rec) {
            var reasons = (rec.reasons || []).slice(0, 2).join(' . ');
            var recId = rec.id || 0;
            var recType = rec.type || 'course';
            html += '<article class="catalog-card rec-card"' +
                ' data-id="' + recId + '"' +
                ' data-type="' + recType + '"' +
                ' data-category=""' +
                ' data-enrolled="false"' +
                ' data-link="' + rec.link + '"' +
                ' data-title="' + (rec.title || '').replace(/"/g, '&quot;') + '"' +
                ' data-desc="' + reasons.replace(/"/g, '&quot;') + '"' +
                ' data-instructor="' + (rec.instructor_name || '').replace(/"/g, '&quot;') + '">' +
                '<div class="catalog-card-thumb" style="background:linear-gradient(135deg,var(--primary),var(--accent)); height:110px;"><i class="fas fa-graduation-cap thumb-icon"></i><div class="thumb-overlay"></div></div>' +
                '<div class="catalog-card-body" style="padding:0.8rem 1rem;">' +
                    '<h4 style="font-size:0.9rem; margin-bottom:0.25rem;">' + rec.title + '</h4>' +
                    '<div class="cc-instructor" style="margin-bottom:0.3rem;"><i class="fas fa-user-tie"></i> ' + rec.instructor_name + '</div>' +
                '</div>' +
                '<div class="catalog-card-footer" style="padding:0.5rem 1rem;">' +
                    '<span class="cc-deadline" style="font-size:0.72rem;">' + reasons + '</span>' +
                    '<span style="color:var(--primary);font-weight:600;font-size:0.75rem;">View &#8594;</span>' +
                '</div></article>';
        });
        recTrack.innerHTML = html;
        recSection.style.display = '';
    }

    fetch('pages/learner/ajax/get-recommendations.php')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success || !data.recommendations) return;
            allRecommendations = data.recommendations;
            var skillCount = document.getElementById('rec-skill-count');
            if (data.total_skills > 0) skillCount.textContent = data.total_skills + ' skill' + (data.total_skills !== 1 ? 's' : '');
            renderRecommendations();
        })
        .catch(function() {});

    if (recPrev) recPrev.addEventListener('click', function() { recTrack.scrollBy({ left: -540, behavior: 'smooth' }); });
    if (recNext) recNext.addEventListener('click', function() { recTrack.scrollBy({ left: 540, behavior: 'smooth' }); });

    // Recommendation card click -> open modal
    recTrack.addEventListener('click', function(e) {
        var card = e.target.closest('.catalog-card');
        if (card) {
            e.preventDefault();
            e.stopPropagation();
            openEntityContent(card);
        }
    });

    // ---- Master and type tabs ----
    var requestCourseButton = document.getElementById('request-course-btn');
    document.querySelectorAll('.catalog-master-tab').forEach(function(btn) {
        btn.addEventListener('click', function() {
            currentMasterTab = btn.dataset.masterTab;
            document.querySelectorAll('.catalog-master-tab').forEach(function(masterButton) {
                var active = masterButton === btn;
                masterButton.classList.toggle('active', active);
                masterButton.setAttribute('aria-selected', String(active));
            });
            document.querySelectorAll('[data-subtab-group]').forEach(function(group) {
                group.style.display = group.dataset.subtabGroup === currentMasterTab ? '' : 'none';
            });
            currentTab = currentMasterTab === 'elearning' ? 'course' : 'learning-path';
            document.querySelectorAll('.catalog-tab-btn').forEach(function(tabButton) {
                var isActive = tabButton.closest('[data-subtab-group]')?.dataset.subtabGroup === currentMasterTab
                    && tabButton.dataset.tab === currentTab;
                tabButton.classList.toggle('active', isActive);
            });
            currentPage = 1;
            if (requestCourseButton) requestCourseButton.style.display = currentMasterTab === 'elearning' ? '' : 'none';
            renderCards();
            renderRecommendations();
        });
    });

    document.querySelectorAll('.catalog-tab-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.catalog-tab-btn').forEach(function(b) { b.classList.remove('active'); });
            btn.classList.add('active');
            currentTab = btn.dataset.tab;
            currentPage = 1;
            renderCards();
            renderRecommendations();
        });
    });

    // ---- Search ----
    var searchTimeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            searchQuery = searchInput.value.trim().toLowerCase();
            currentPage = 1;
            renderCards();
        }, 250);
    });

    // ---- View toggle ----
    document.querySelectorAll('.catalog-view-toggle button').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.catalog-view-toggle button').forEach(function(b) { b.classList.remove('active'); });
            btn.classList.add('active');
            viewMode = btn.dataset.view;
            grid.classList.toggle('list-view', viewMode === 'list');
        });
    });

    // ---- Sort dropdown ----
    var sortSelect = document.getElementById('catalog-sort-select');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            currentSort = sortSelect.value;
            currentPage = 1;
            renderCards();
        });
    }

    // ---- Pagination ----
    if (catalogPagination) {
        catalogPagination.addEventListener('click', function(event) {
            var btn = event.target.closest('.page-btn');
            if (!btn || btn.disabled) return;
            var action = btn.dataset.action;
            var filtered = getFilteredCards();
            var totalPages = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE));
            if (btn.dataset.page) currentPage = Math.min(totalPages, Math.max(1, parseInt(btn.dataset.page, 10)));
            else if (action === 'prev' && currentPage > 1) currentPage--;
            else if (action === 'next' && currentPage < totalPages) currentPage++;
            renderCards();
            grid.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }

    // ---- Entity Modal ----
    var emOverlay = document.getElementById('catalog-entity-content');
    var emTitle = document.getElementById('cem-title');
    var emViewBtn = document.getElementById('cem-view-btn');
    var emCloseBtn = document.getElementById('cem-close-btn');
    var emOverviewGrid = document.getElementById('cem-overview-grid');
    var emDescription = document.getElementById('cem-description');
    var emChildEntities = document.getElementById('cem-child-entities');
    var emStructureContent = document.getElementById('cem-structure-content');
    var emPerformanceContent = document.getElementById('cem-performance-content');
    var emEnrollBtn = document.getElementById('cem-enroll-btn');
    var emState = { type: '', id: 0, link: '', enrolled: false, activeTab: 'overview', data: {} };
    var typeLabels = { course: 'Course', program: 'Program', 'learning-path': 'Learning Path', 'video-conference': 'Online Training', module: 'Module', lesson: 'Lesson', quiz: 'Quiz' };

    function syncEmTabs() {
        emOverlay.querySelectorAll('.entity-content-tab').forEach(function(btn) {
            var tabName = btn.dataset.contentTab || btn.dataset.cemTab;
            var isActive = emState.activeTab === tabName;
            btn.classList.toggle('active', isActive);
            btn.style.background = isActive ? 'rgba(32,0,130,0.08)' : '#fff';
            btn.style.border = isActive ? 'none' : '1px solid rgba(32,0,130,0.12)';
            btn.style.color = isActive ? 'var(--primary)' : 'var(--text)';
        });
        emOverlay.querySelectorAll('.entity-content-panel, .cem-panel').forEach(function(p) {
            var panelTab = p.id.replace('entity-content-', '').replace('cem-panel-', '');
            p.style.display = panelTab === emState.activeTab ? 'block' : 'none';
        });
    }

    function openEntityContent(card) {
        var itemType = card.dataset.type || 'course';
        var id = parseInt(card.dataset.id) || 0;
        var link = card.dataset.link || '';
        var enrolled = card.dataset.enrolled === 'true';
        var title = card.dataset.title || 'Untitled';
        var desc = card.dataset.desc || 'No description available.';
        var label = typeLabels[itemType] || 'Item';
        var cat = card.dataset.category || 'General';
        var instructorName = card.dataset.instructor || '';

        emState = { type: itemType, id: id, link: link, enrolled: enrolled, activeTab: 'overview', data: { title: title, description: desc, category: cat, instructor_name: instructorName, course_id: card.dataset.courseId || '' } };
        emTitle.textContent = title;
        emViewBtn.href = link;
        emViewBtn.style.display = link ? '' : 'none';

        // Handle video-conference specific tabs
        var tabBtns = emOverlay.querySelectorAll('.entity-content-tab');
        var supportsStructureAndPerformance = ['course', 'module', 'learning-path'].indexOf(itemType) !== -1;
        if (tabBtns[1]) tabBtns[1].style.display = supportsStructureAndPerformance ? '' : 'none';
        if (tabBtns[2]) tabBtns[2].style.display = supportsStructureAndPerformance ? '' : 'none';

        syncEmTabs();

        // Overview grid
        var overviewHtml = '';
        if (itemType === 'video-conference') {
            var scheduled = card.dataset.scheduled || '';
            var duration = card.dataset.duration || '';
            var platform = card.dataset.platform || '';
            var platformLabel = platform.replace('_', ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); });
            var pColor = platform === 'zoom' ? '#2D8CFF' : (platform === 'google_meet' ? '#00897B' : '#6c757d');
            var scheduledDate = '';
            if (scheduled) {
                var d = new Date(scheduled.replace(' ', 'T'));
                scheduledDate = d.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) + ' at ' + d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
            }
            overviewHtml += '<div><label style="color:var(--primary); font-weight:700; font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase;">Type</label><p style="margin:0.55rem 0 0; font-size:1rem; color:var(--text);"><i class="fas fa-video" style="color:var(--primary); margin-right:0.4rem;"></i>Online Training</p></div>';
            overviewHtml += '<div><label style="color:var(--primary); font-weight:700; font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase;">Platform</label><p style="margin:0.55rem 0 0; font-size:1rem;"><span style="padding:0.2rem 0.6rem; border-radius:6px; background:var(--primary); color:#fff; font-weight:600; font-size:0.85rem;">' + platformLabel + '</span></p></div>';
            if (scheduledDate) overviewHtml += '<div><label style="color:var(--primary); font-weight:700; font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase;">Scheduled</label><p style="margin:0.55rem 0 0; font-size:1rem; color:var(--text);">' + scheduledDate + '</p></div>';
            if (duration) overviewHtml += '<div><label style="color:var(--primary); font-weight:700; font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase;">Duration</label><p style="margin:0.55rem 0 0; font-size:1rem; color:var(--text);">' + duration + ' minutes</p></div>';
            if (instructorName) overviewHtml += '<div><label style="color:var(--primary); font-weight:700; font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase;">Host</label><p style="margin:0.55rem 0 0; font-size:1rem; color:var(--text);"><i class="fas fa-user-tie" style="margin-right:0.4rem; color:var(--primary);"></i>' + instructorName + '</p></div>';
        } else {
            overviewHtml += '<div><label style="color:var(--primary); font-weight:700; font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase;">Type</label><p style="margin:0.55rem 0 0; font-size:1rem; color:var(--text);">' + label + '</p></div>';
            overviewHtml += '<div><label style="color:var(--primary); font-weight:700; font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase;">Status</label><p style="margin:0.55rem 0 0; font-size:1rem; color:var(--text);">' + (enrolled ? 'Enrolled' : 'Available') + '</p></div>';
            overviewHtml += '<div><label style="color:var(--primary); font-weight:700; font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase;">Category</label><p style="margin:0.55rem 0 0; font-size:1rem; color:var(--text);">' + cat + '</p></div>';
            if (instructorName) overviewHtml += '<div><label style="color:var(--primary); font-weight:700; font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase;">Instructor</label><p style="margin:0.55rem 0 0; font-size:1rem; color:var(--text);">' + instructorName + '</p></div>';
        }
        emOverviewGrid.innerHTML = overviewHtml;
        emDescription.textContent = desc;

        // Child entities summary
        var childHtml = '<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr)); gap:0.75rem;">';
        childHtml += '<div style="padding:0.8rem; border-radius:10px; background:rgba(32,0,130,0.05); border:1px solid rgba(32,0,130,0.08);"><div style="font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase; color:var(--primary); font-weight:700;">Type</div><div style="margin-top:0.5rem; color:var(--text); font-weight:600;">' + label + '</div></div>';
        if (itemType === 'video-conference') {
            var vcScheduled = card.dataset.scheduled || '';
            var vcDuration = card.dataset.duration || '';
            var vcPlatform = (card.dataset.platform || '').replace('_', ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); });
            childHtml += '<div style="padding:0.8rem; border-radius:10px; background:rgba(32,0,130,0.05); border:1px solid rgba(32,0,130,0.08);"><div style="font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase; color:var(--primary); font-weight:700;">Platform</div><div style="margin-top:0.5rem; color:var(--text); font-weight:600;">' + vcPlatform + '</div></div>';
            childHtml += '<div style="padding:0.8rem; border-radius:10px; background:rgba(32,0,130,0.05); border:1px solid rgba(32,0,130,0.08);"><div style="font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase; color:var(--primary); font-weight:700;">Duration</div><div style="margin-top:0.5rem; color:var(--text); font-weight:600;">' + (vcDuration || 'TBA') + ' min</div></div>';
        } else {
            childHtml += '<div style="padding:0.8rem; border-radius:10px; background:rgba(32,0,130,0.05); border:1px solid rgba(32,0,130,0.08);"><div style="font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase; color:var(--primary); font-weight:700;">Status</div><div style="margin-top:0.5rem; color:var(--text); font-weight:600;">' + (enrolled ? 'Enrolled' : 'Available') + '</div></div>';
            childHtml += '<div style="padding:0.8rem; border-radius:10px; background:rgba(32,0,130,0.05); border:1px solid rgba(32,0,130,0.08);"><div style="font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase; color:var(--primary); font-weight:700;">Category</div><div style="margin-top:0.5rem; color:var(--text); font-weight:600;">' + cat + '</div></div>';
        }
        childHtml += '</div>';
        emChildEntities.innerHTML = childHtml;

        // Enroll / Join button
        if (itemType === 'video-conference') {
            var meetingLink = card.dataset.meetingLink || '';
            var vcScheduledTs = card.dataset.scheduled ? new Date(card.dataset.scheduled.replace(' ', 'T')).getTime() : 0;
            var isPast = vcScheduledTs > 0 && vcScheduledTs < Date.now();
            emEnrollBtn.style.display = '';
            if (isPast) {
                emEnrollBtn.textContent = 'Session Ended';
                emEnrollBtn.disabled = true;
                emEnrollBtn.style.background = '#9ca3af';
                emEnrollBtn.style.color = '#fff';
                emEnrollBtn.onclick = null;
            } else if (meetingLink) {
                emEnrollBtn.textContent = 'Join Session';
                emEnrollBtn.disabled = false;
                emEnrollBtn.style.background = '#2D8CFF';
                emEnrollBtn.style.color = '#fff';
                emEnrollBtn.onclick = function() { window.open(meetingLink, '_blank'); };
            } else {
                emEnrollBtn.textContent = 'Link Not Available';
                emEnrollBtn.disabled = true;
                emEnrollBtn.style.background = '#f59e0b';
                emEnrollBtn.style.color = '#fff';
                emEnrollBtn.onclick = null;
            }
        } else if (itemType === 'course' && !enrolled) {
            emEnrollBtn.style.display = '';
            emEnrollBtn.textContent = 'Enroll Now';
            emEnrollBtn.disabled = false;
            emEnrollBtn.dataset.courseId = id;
            emEnrollBtn.style.background = '#10b981';
            emEnrollBtn.style.color = '#fff';
            emEnrollBtn.onclick = function() { doEnroll(emEnrollBtn); };
        } else if (itemType === 'course' && enrolled) {
            emEnrollBtn.style.display = '';
            emEnrollBtn.textContent = 'Enrolled';
            emEnrollBtn.disabled = true;
            emEnrollBtn.style.background = '#10b981';
            emEnrollBtn.style.color = '#fff';
            emEnrollBtn.onclick = null;
        } else {
            emEnrollBtn.style.display = 'none';
        }

        // Load structure tab lazily
        emStructureContent.innerHTML = '<p style="text-align:center; color:#999;">Loading structure...</p>';
        emPerformanceContent.innerHTML = '<p style="text-align:center; color:#999;">Loading performance data...</p>';

        emOverlay.style.display = 'flex';
    }

    function escapeModalText(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function(character) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[character];
        });
    }

    function loadLearningPathDetails(view) {
        var target = view === 'structure' ? emStructureContent : emPerformanceContent;
        target.innerHTML = '<p style="text-align:center;color:var(--muted);">Loading learning-path details...</p>';
        fetch('pages/learner/ajax/get-learning-path-details.php?learning_path_id=' + emState.id, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (!data.success) throw new Error(data.message || 'Unable to load learning-path details.');
            var items = data.items || [];
            if (view === 'structure') {
                if (!items.length) {
                    target.innerHTML = '<p style="color:var(--muted);text-align:center;">This learning path has no active items.</p>';
                    return;
                }
                target.innerHTML = '<div style="display:grid;gap:0.35rem;">' + items.map(function(item, index) {
                    var type = escapeModalText((item.item_type || 'item').replace('-', ' '));
                    var parent = item.course_id ? 'Course item' : 'Standalone item';
                    return '<div style="display:grid;grid-template-columns:2rem minmax(0,1fr) auto;align-items:center;gap:0.75rem;padding:0.75rem 0;border-bottom:1px solid var(--border);">' +
                        '<span style="color:var(--primary);font-size:0.78rem;font-weight:700;">' + String(index + 1).padStart(2, '0') + '</span>' +
                        '<div style="min-width:0;"><div style="color:var(--text);font-weight:650;overflow-wrap:anywhere;">' + escapeModalText(item.title) + '</div><div style="margin-top:0.18rem;color:var(--muted);font-size:0.74rem;text-transform:capitalize;">' + type + ' · ' + parent + '</div></div>' +
                        '<span style="color:var(--muted);font-size:0.74rem;">' + (item.study_status === 'completed' ? '<i class="fas fa-check" aria-label="Completed"></i>' : '') + '</span></div>';
                }).join('') + '</div>';
                return;
            }

            var summary = data.summary || { total_steps: 0, completed: 0, in_progress: 0, not_started: 0, progress_percent: 0 };
            target.innerHTML = '<div style="display:flex;align-items:flex-end;justify-content:space-between;gap:1rem;margin-bottom:0.4rem;"><strong style="color:var(--text);">Path completion</strong><strong style="color:var(--primary);font-size:1.1rem;">' + Number(summary.progress_percent || 0) + '%</strong></div>' +
                '<div style="height:7px;background:var(--bg-subtle);border-radius:99px;overflow:hidden;margin-bottom:1rem;"><div style="height:100%;width:' + Number(summary.progress_percent || 0) + '%;background:var(--primary);"></div></div>' +
                '<div style="display:flex;flex-wrap:wrap;gap:1rem;margin-bottom:1rem;color:var(--text);font-size:0.82rem;">' +
                '<span><strong>' + Number(summary.completed || 0) + '</strong> completed</span><span><strong>' + Number(summary.in_progress || 0) + '</strong> in progress</span><span><strong>' + Number(summary.not_started || 0) + '</strong> not started</span><span><strong>' + Number(summary.total_steps || 0) + '</strong> total steps</span></div>' +
                (items.length ? '<div style="display:grid;gap:0.35rem;">' + items.map(function(item) {
                    var label = item.study_status === 'completed' ? 'Completed' : item.study_status === 'in_progress' ? 'In progress' : 'Not started';
                    return '<div style="display:flex;justify-content:space-between;gap:1rem;padding:0.65rem 0;border-top:1px solid var(--border);"><span style="color:var(--text);">' + escapeModalText(item.title) + '</span><span style="color:var(--muted);font-size:0.78rem;white-space:nowrap;">' + label + '</span></div>';
                }).join('') + '</div>' : '<p style="color:var(--muted);">No path items to measure yet.</p>');
        })
        .catch(function(error) {
            target.innerHTML = '<p style="color:var(--muted);text-align:center;">' + escapeModalText(error.message || 'Unable to load learning-path details.') + '</p>';
        });
    }

    function loadContentStructure() {
        if (!emState.id) return;
        var itemType = emState.type;
        var id = emState.id;
        var endpoint = '';
        if (itemType === 'learning-path') {
            loadLearningPathDetails('structure');
            return;
        }
        if (itemType === 'course') endpoint = 'pages/learner/ajax/get-course-structure.php?course_id=' + id;
        else if (itemType === 'module' && emState.data.course_id) endpoint = 'pages/learner/ajax/get-course-structure.php?course_id=' + encodeURIComponent(emState.data.course_id);
        else {
            emStructureContent.innerHTML = '<p style="color:#999; text-align:center;">No structure available for this item type.</p>';
            return;
        }
        fetch(endpoint, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success || !data.structure) {
                    emStructureContent.innerHTML = '<p style="color:#999; text-align:center;">No structure data available.</p>';
                    return;
                }
                var html = '<div style="font-size:0.9rem; line-height:1.8;">';
                var structure = data.structure;
                if (itemType === 'module') {
                    structure.modules = (structure.modules || []).filter(function(module) { return String(module.id) === String(id); });
                    if (!structure.modules.length) {
                        emStructureContent.innerHTML = '<p style="color:#999;text-align:center;">No active structure found for this module.</p>';
                        return;
                    }
                }
                if (structure.modules) {
                    structure.modules.forEach(function(mod) {
                        html += '<div style="margin-bottom:1rem; padding:1rem; border:1px solid rgba(32,0,130,0.12); border-radius:12px; background:rgba(32,0,130,0.03);">';
                        html += '<div style="font-weight:700; color:var(--primary);"><i class="fas fa-folder" style="margin-right:0.5rem;"></i>' + (mod.title || 'Module') + '</div>';
                        if (mod.lessons && mod.lessons.length) {
                            mod.lessons.forEach(function(lesson) {
                                html += '<div style="margin:0.4rem 0 0.4rem 1.5rem; color:var(--text);"><i class="fas fa-file-alt" style="margin-right:0.4rem; color:var(--primary); font-size:0.85rem;"></i>' + (lesson.title || 'Lesson');
                                if (lesson.quizzes && lesson.quizzes.length) {
                                    lesson.quizzes.forEach(function(quiz) {
                                        html += '<div style="margin:0.3rem 0 0.3rem 2rem; color:var(--text); font-size:0.9rem;"><i class="fas fa-question-circle" style="margin-right:0.4rem; color:var(--primary); font-size:0.8rem;"></i>' + (quiz.title || 'Quiz') + '</div>';
                                    });
                                }
                                html += '</div>';
                            });
                        }
                        html += '</div>';
                    });
                }
                if (structure.evaluations && structure.evaluations.length) {
                    html += '<div style="margin-top:0.5rem;">';
                    structure.evaluations.forEach(function(ev) {
                        html += '<div style="padding:0.6rem 1rem; border:1px solid rgba(16,185,129,0.15); border-radius:8px; margin-bottom:0.5rem; background:rgba(16,185,129,0.03);"><i class="fas fa-clipboard-check" style="margin-right:0.5rem; color:#10b981;"></i>' + (ev.title || 'Evaluation') + '</div>';
                    });
                    html += '</div>';
                }
                html += '</div>';
                emStructureContent.innerHTML = html;
            })
            .catch(function() {
                emStructureContent.innerHTML = '<p style="color:#999; text-align:center;">Unable to load structure.</p>';
            });
    }

    function loadContentPerformance() {
        if (!emState.id) return;
        var itemType = emState.type;
        var id = emState.id;
        if (itemType === 'learning-path') {
            loadLearningPathDetails('performance');
            return;
        }
        var endpoint = '';
        if (itemType === 'course') endpoint = 'pages/learner/ajax/get-course-progress.php?course_id=' + id;
        else if (itemType === 'module') endpoint = 'pages/learner/ajax/get-module-progress.php?module_id=' + id;
        else if (itemType === 'quiz') endpoint = 'pages/learner/ajax/get-quiz-result.php?quiz_id=' + id;
        else {
            emPerformanceContent.innerHTML = '<p style="color:#999; text-align:center;">Performance data not available for this item type.</p>';
            return;
        }
        fetch(endpoint, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) {
                    emPerformanceContent.innerHTML = '<p style="color:#999; text-align:center;">No performance data available yet.</p>';
                    return;
                }
                var rawItems = data.items || data.progress || [];
                if (rawItems.length === 0) {
                    emPerformanceContent.innerHTML = '<p style="color:#999; text-align:center;">No performance data available yet. Start the course to track your progress.</p>';
                    return;
                }

                // Compute summary stats
                var totalModules = 0, completedModules = 0, totalQuizzes = 0, passedQuizzes = 0, totalScore = 0, scoreCount = 0;
                rawItems.forEach(function(item) {
                    if (item.type === 'quiz') return; // skip quizzes at top level
                    totalModules++;
                    if (item.status === 'Completed') completedModules++;
                    if (item.quizzes) {
                        item.quizzes.forEach(function(q) {
                            totalQuizzes++;
                            if (q.status === 'Passed') passedQuizzes++;
                        });
                    }
                    totalScore += (item.score || 0);
                    scoreCount++;
                });
                var overallScore = scoreCount > 0 ? Math.round(totalScore / scoreCount) : 0;
                var overallColor = overallScore >= 70 ? '#10b981' : overallScore >= 40 ? '#f59e0b' : '#ea580c';

                // Build HTML
                var html = '';

                // Summary header card
                html += '<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(120px, 1fr)); gap:0.75rem; margin-bottom:1rem;">';
                html += '<div style="padding:1rem; border-radius:10px; background:rgba(32,0,130,0.05); border:1px solid rgba(32,0,130,0.08); text-align:center;">';
                html += '<div style="font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase; color:var(--primary); font-weight:700;">Overall</div>';
                html += '<div style="margin-top:0.4rem; font-size:1.5rem; font-weight:800; color:' + overallColor + ';">' + overallScore + '%</div>';
                html += '</div>';
                html += '<div style="padding:1rem; border-radius:10px; background:rgba(16,185,129,0.05); border:1px solid rgba(16,185,129,0.08); text-align:center;">';
                html += '<div style="font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase; color:#10b981; font-weight:700;">Modules</div>';
                html += '<div style="margin-top:0.4rem; font-size:1.5rem; font-weight:800; color:var(--text);">' + completedModules + '<span style="font-size:0.85rem; color:#999;">/' + totalModules + '</span></div>';
                html += '</div>';
                html += '<div style="padding:1rem; border-radius:10px; background:rgba(59,130,246,0.05); border:1px solid rgba(59,130,246,0.08); text-align:center;">';
                html += '<div style="font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase; color:#3b82f6; font-weight:700;">Quizzes</div>';
                html += '<div style="margin-top:0.4rem; font-size:1.5rem; font-weight:800; color:var(--text);">' + passedQuizzes + '<span style="font-size:0.85rem; color:#999;">/' + totalQuizzes + '</span></div>';
                html += '</div>';
                html += '</div>';

                // Flatten modules with quizzes
                var items = [];
                rawItems.forEach(function(item) {
                    items.push(item);
                    if (item.quizzes && item.quizzes.length) {
                        item.quizzes.forEach(function(q) {
                            q.type = 'quiz';
                            items.push(q);
                        });
                    }
                });

                // Status filter toolbar — pill toggle buttons
                html += '<div class="perf-filter-toolbar" role="group" aria-label="Filter performance items">';
                html += '<button type="button" class="perf-filter-btn active" data-perf-filter="all" aria-pressed="true">All</button>';
                html += '<button type="button" class="perf-filter-btn" data-perf-filter="in_progress" aria-pressed="false">In progress</button>';
                html += '<button type="button" class="perf-filter-btn" data-perf-filter="completed" aria-pressed="false">Completed</button>';
                html += '<button type="button" class="perf-filter-btn" data-perf-filter="not_started" aria-pressed="false">Not started</button>';
                html += '<span id="perf-filter-count"></span>';
                html += '</div>';

                // Item rows
                html += '<div id="perf-items-grid" style="display:grid; gap:0.75rem;">';
                items.forEach(function(item, idx) {
                    var score = item.score || 0;
                    var status = item.status || 'In progress';
                    var isQuiz = item.type === 'quiz';
                    var isModule = !isQuiz && item.type !== 'evaluation' && item.lessons_total !== undefined;
                    var refId = item.reference_id || 0;
                    var statusColor, statusTextColor;
                    if (status === 'Completed' || status === 'Passed') {
                        statusColor = 'rgba(16,185,129,0.08)'; statusTextColor = '#10b981';
                    } else if (status === 'In progress') {
                        statusColor = 'rgba(59,130,246,0.08)'; statusTextColor = '#3b82f6';
                    } else {
                        statusColor = 'rgba(234,88,12,0.08)'; statusTextColor = '#ea580c';
                    }
                    var icon = item.type === 'evaluation' ? '<i class="fas fa-clipboard-check" style="color:#10b981;margin-right:0.5rem;font-size:0.85rem;"></i>' : isQuiz ? '<i class="fas fa-question-circle" style="color:#f59e0b;margin-right:0.5rem;font-size:0.85rem;"></i>' : '<i class="fas fa-book-open" style="color:#3b82f6;margin-right:0.5rem;font-size:0.85rem;"></i>';
                    var clickable = (isQuiz && refId > 0) || (isModule && refId > 0);
                    var cursor = clickable ? 'cursor:pointer;' : '';
                    var hoverAttr = clickable ? 'onmouseover="this.style.background=\'rgba(32,0,130,0.02)\'" onmouseout="this.style.background=\'#fff\'"' : '';
                    var gridCols = clickable ? '1.2fr 0.8fr 0.8fr 0.3fr' : '1.5fr 0.8fr 0.8fr';

                    var statusKey = status === 'Completed' || status === 'Passed' ? 'completed' : status === 'In progress' ? 'in_progress' : 'not_started';
                    html += '<div class="perf-row"' + (clickable ? ' data-quiz-id="' + (isQuiz ? refId : '') + '" data-module-id="' + (isModule ? refId : '') + '" data-row-idx="' + idx + '"' : '') + ' data-status="' + statusKey + '" style="border:1px solid rgba(32,0,130,0.12); border-radius:12px; background:#fff; overflow:hidden;">';
                    html += '<div class="perf-row-main" style="display:grid; grid-template-columns:' + gridCols + '; gap:0.75rem; align-items:center; padding:0.9rem 1rem; ' + cursor + '" ' + hoverAttr + '>';
                    html += '<strong style="color:var(--text);">' + icon + (item.title || item.name || 'Item') + '</strong>';
                    html += '<span style="color:var(--text);">' + score + '%</span>';
                    html += '<span style="padding:0.45rem 0.65rem; border-radius:999px; background:' + statusColor + '; color:' + statusTextColor + '; font-weight:700; font-size:0.75rem; text-align:center;">' + status + '</span>';
                    if (clickable) {
                        html += '<div style="text-align:center; color:var(--primary); font-size:0.75rem;"><i class="fas fa-chevron-down" style="transition:transform 0.2s;"></i></div>';
                    }
                    html += '</div>';

                    // Progress bar for modules
                    if (isModule && item.lessons_total > 0) {
                        var pct = item.lessons_completed || 0;
                        var total = item.lessons_total || 1;
                        var barPct = Math.round((pct / total) * 100);
                        var barColor = barPct === 100 ? '#10b981' : barPct > 0 ? '#3b82f6' : '#e5e7eb';
                        html += '<div style="padding:0 1rem 0.6rem 1rem;">';
                        html += '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.35rem;">';
                        html += '<span style="font-size:0.72rem; color:#999;">' + pct + ' of ' + total + ' lessons</span>';
                        html += '<span style="font-size:0.72rem; color:' + barColor + '; font-weight:700;">' + barPct + '%</span>';
                        html += '</div>';
                        html += '<div style="height:6px; background:#f0f0f0; border-radius:999px; overflow:hidden;">';
                        html += '<div style="height:100%; width:' + barPct + '%; background:' + barColor + '; border-radius:999px; transition:width 0.4s ease;"></div>';
                        html += '</div>';
                        html += '</div>';
                    }

                    // Expand panel
                    if (clickable) {
                        html += '<div class="perf-expand" id="perf-expand-' + idx + '" style="display:none; padding:0 1rem 1rem 1rem; border-top:1px solid rgba(32,0,130,0.08);"></div>';
                    }
                    html += '</div>';
                });
                html += '</div>';
                emPerformanceContent.innerHTML = html;

                // Bind click handlers
                emPerformanceContent.querySelectorAll('.perf-row[data-quiz-id], .perf-row[data-module-id]').forEach(function(row) {
                    row.querySelector('.perf-row-main').addEventListener('click', function() {
                        var quizId = row.dataset.quizId;
                        var moduleId = row.dataset.moduleId;
                        var idx = row.dataset.rowIdx;
                        var expand = document.getElementById('perf-expand-' + idx);
                        var chevron = row.querySelector('.fa-chevron-down, .fa-chevron-up');
                        if (expand.style.display === 'none') {
                            expand.style.display = 'block';
                            if (chevron) { chevron.classList.remove('fa-chevron-down'); chevron.classList.add('fa-chevron-up'); }
                            if (expand.innerHTML.trim() === '') {
                                if (quizId) loadQuizAttempts(quizId, expand);
                                else if (moduleId) loadModuleLessons(moduleId, rawItems, expand);
                            }
                        } else {
                            expand.style.display = 'none';
                            if (chevron) { chevron.classList.remove('fa-chevron-up'); chevron.classList.add('fa-chevron-down'); }
                        }
                    });
                });

                // Bind filter pill buttons
                var perfFilterCount = document.getElementById('perf-filter-count');
                var allPerfRows = emPerformanceContent.querySelectorAll('.perf-row[data-status]');
                var activeFilters = ['all'];
                var perfFilterBtns = emPerformanceContent.querySelectorAll('.perf-filter-btn');

                function applyPerfFilter() {
                    var showAll = activeFilters.indexOf('all') !== -1;
                    var visible = 0;
                    allPerfRows.forEach(function(row) {
                        var match = showAll || activeFilters.indexOf(row.dataset.status) !== -1;
                        row.style.display = match ? '' : 'none';
                        if (match) {
                            visible++;
                        } else {
                            // Collapse if hidden
                            var idx = row.dataset.rowIdx;
                            if (idx) {
                                var exp = document.getElementById('perf-expand-' + idx);
                                if (exp) exp.style.display = 'none';
                                var chev = row.querySelector('.fa-chevron-up');
                                if (chev) { chev.classList.remove('fa-chevron-up'); chev.classList.add('fa-chevron-down'); }
                            }
                        }
                    });
                    if (perfFilterCount) perfFilterCount.textContent = visible + ' of ' + allPerfRows.length + ' items';
                }

                function syncFilterBtnStyles() {
                    perfFilterBtns.forEach(function(btn) {
                        var isActive = activeFilters.indexOf(btn.dataset.perfFilter) !== -1;
                        btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                        if (isActive) {
                            btn.style.background = 'var(--primary)';
                            btn.style.color = '#fff';
                            btn.style.border = 'none';
                            btn.classList.add('active');
                        } else {
                            btn.style.background = 'var(--surface)';
                            btn.style.color = 'var(--text)';
                            btn.style.border = '1px solid rgba(32,0,130,0.15)';
                            btn.classList.remove('active');
                        }
                    });
                }

                perfFilterBtns.forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var val = btn.dataset.perfFilter;
                        activeFilters = val === 'all' ? ['all'] : [val];
                        syncFilterBtnStyles();
                        applyPerfFilter();
                    });
                });
                applyPerfFilter();
            })
            .catch(function() {
                emPerformanceContent.innerHTML = '<p style="color:#999; text-align:center;">Unable to load performance data.</p>';
            });
    }

    function loadModuleLessons(moduleId, rawItems, container) {
        var mod = null;
        rawItems.forEach(function(item) {
            if (item.reference_id && String(item.reference_id) === String(moduleId)) mod = item;
        });
        if (!mod) { container.innerHTML = '<p style="color:#999; text-align:center; padding:0.5rem 0; font-size:0.85rem;">No lesson data.</p>'; return; }
        var html = '<div style="padding-top:0.75rem;">';
        // Lessons
        var lessons = mod.lesson_details || [];
        if (lessons.length > 0) {
            lessons.forEach(function(lesson) {
                var checkColor = lesson.completed ? '#10b981' : '#d1d5db';
                var icon = lesson.completed ? 'fa-check-circle' : 'fa-circle';
                var textDeco = lesson.completed ? '' : 'color:#999;';
                html += '<div style="display:flex; align-items:center; gap:0.6rem; padding:0.4rem 0; font-size:0.85rem;">';
                html += '<i class="fas ' + icon + '" style="color:' + checkColor + '; font-size:0.9rem;"></i>';
                html += '<span style="color:var(--text); ' + textDeco + '">' + (lesson.title || 'Lesson') + '</span>';
                if (lesson.content_type) html += '<span style="font-size:0.7rem; padding:0.15rem 0.45rem; border-radius:999px; background:rgba(59,130,246,0.08); color:#3b82f6; font-weight:600;">' + lesson.content_type + '</span>';
                html += '</div>';
            });
        } else {
            html += '<p style="color:#999; font-size:0.85rem; padding:0.5rem 0;">No lessons in this module.</p>';
        }
        // Quizzes summary
        if (mod.quizzes && mod.quizzes.length > 0) {
            html += '<div style="margin-top:0.75rem; padding-top:0.5rem; border-top:1px solid rgba(32,0,130,0.06);">';
            mod.quizzes.forEach(function(quiz) {
                var qColor = quiz.status === 'Passed' ? '#10b981' : '#ea580c';
                var qIcon = quiz.status === 'Passed' ? 'fa-check-circle' : 'fa-times-circle';
                html += '<div style="display:flex; align-items:center; gap:0.6rem; padding:0.4rem 0; font-size:0.85rem;">';
                html += '<i class="fas fa-question-circle" style="color:#f59e0b; font-size:0.85rem;"></i>';
                html += '<span style="color:var(--text);">' + (quiz.title || 'Quiz') + '</span>';
                html += '<span style="margin-left:auto; padding:0.2rem 0.5rem; border-radius:999px; background:rgba(' + (quiz.status === 'Passed' ? '16,185,129' : '234,88,12') + ',0.08); color:' + qColor + '; font-weight:700; font-size:0.7rem;">' + quiz.status + '</span>';
                html += '</div>';
            });
            html += '</div>';
        }
        html += '</div>';
        container.innerHTML = html;
    }

    function loadQuizAttempts(quizId, container) {
        container.innerHTML = '<p style="text-align:center; color:#999; padding:0.5rem 0; font-size:0.85rem;">Loading attempts...</p>';
        fetch('pages/learner/ajax/get-quiz-result.php?quiz_id=' + quizId, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success || !data.items || data.items.length === 0) {
                    container.innerHTML = '<p style="color:#999; text-align:center; padding:0.5rem 0; font-size:0.85rem;">No attempts recorded yet.</p>';
                    return;
                }
                var summary = data.summary || {};
                var html = '';
                if (summary.quiz_title) {
                    html += '<div style="display:flex; gap:1rem; flex-wrap:wrap; margin-bottom:0.75rem; padding-top:0.75rem;">';
                    if (summary.best_score !== undefined) html += '<span style="font-size:0.78rem; color:var(--text);"><strong>Best:</strong> ' + summary.best_score + '%</span>';
                    if (summary.total_attempts !== undefined) html += '<span style="font-size:0.78rem; color:var(--text);"><strong>Attempts:</strong> ' + summary.total_attempts + '</span>';
                    if (summary.remaining_attempts !== undefined) html += '<span style="font-size:0.78rem; color:var(--text);"><strong>Remaining:</strong> ' + summary.remaining_attempts + '</span>';
                    if (summary.passing_score !== undefined) html += '<span style="font-size:0.78rem; color:var(--text);"><strong>Pass:</strong> ' + summary.passing_score + '%</span>';
                    html += '</div>';
                }
                html += '<div style="display:grid; gap:0.5rem;">';
                data.items.forEach(function(attempt) {
                    var aScore = attempt.score || 0;
                    var aStatus = attempt.status || 'Unknown';
                    var aColor = aStatus === 'Passed' ? '#10b981' : '#ef4444';
                    var aBg = aStatus === 'Passed' ? 'rgba(16,185,129,0.08)' : 'rgba(239,68,68,0.08)';
                    var dateStr = '';
                    if (attempt.completed_at) {
                        var d = new Date(attempt.completed_at);
                        dateStr = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) + ' ' + d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                    }
                    html += '<div style="display:grid; grid-template-columns:1fr auto auto auto; gap:0.75rem; align-items:center; padding:0.6rem 0.8rem; border:1px solid rgba(32,0,130,0.08); border-radius:8px; background:' + aBg + ';">';
                    html += '<span style="font-size:0.82rem; color:var(--text); font-weight:600;">' + (attempt.title || 'Attempt') + '</span>';
                    html += '<span style="font-size:0.82rem; color:var(--text);">' + aScore + '%</span>';
                    html += '<span style="padding:0.3rem 0.6rem; border-radius:999px; background:' + (aStatus === 'Passed' ? 'rgba(16,185,129,0.15)' : 'rgba(239,68,68,0.15)') + '; color:' + aColor + '; font-weight:700; font-size:0.7rem; text-align:center;">' + aStatus + '</span>';
                    html += '<span style="font-size:0.72rem; color:#999;">' + dateStr + '</span>';
                    html += '</div>';
                });
                html += '</div>';
                container.innerHTML = html;
            })
            .catch(function() {
                container.innerHTML = '<p style="color:#999; text-align:center; padding:0.5rem 0; font-size:0.85rem;">Unable to load attempts.</p>';
            });
    }

    grid.addEventListener('click', function(e) {
        if (e.target.closest('.cc-enroll-btn')) return;
        var card = e.target.closest('.catalog-card');
        if (card) openEntityContent(card);
    });

                emOverlay.querySelectorAll('.entity-content-tab').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var tabName = btn.dataset.contentTab || btn.dataset.cemTab;
            emState.activeTab = tabName;
            syncEmTabs();
            if (tabName === 'structure') loadContentStructure();
            if (tabName === 'performance') loadContentPerformance();
        });
    });

    if (emCloseBtn) emCloseBtn.addEventListener('click', function() { emOverlay.style.display = 'none'; });
    if (emOverlay) emOverlay.addEventListener('click', function(e) { if (e.target === this) this.style.display = 'none'; });

    // ---- Enroll ----
    function doEnroll(btn) {
        if (btn.classList.contains('cc-take-path-btn')) return;
        var courseId = btn.dataset.courseId;
        if (!courseId) return;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enrolling...';
        btn.classList.remove('enroll');
        btn.classList.add('enrolling');
        var self = btn;
        fetch('pages/learner/ajax/enroll-course.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': window.CSRF_TOKEN || ''
            },
            body: JSON.stringify({ course_id: parseInt(courseId) })
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.success) {
                self.classList.remove('enrolling');
                self.classList.add('enrolled');
                self.innerHTML = '<i class="fas fa-check"></i> Enrolled';
                self.disabled = true;
                var card = document.querySelector('.catalog-card[data-id="' + courseId + '"]');
                if (card) {
                    card.dataset.enrolled = 'true';
                    var badge = document.createElement('span');
                    badge.className = 'catalog-card-enrolled-badge';
                    badge.innerHTML = '<i class="fas fa-check"></i> Enrolled';
                    card.querySelector('.catalog-card-thumb').appendChild(badge);
                }
                // Close modal and show toast
                if (emOverlay.style.display === 'flex') {
                    emOverlay.style.display = 'none';
                }
                var courseName = card ? (card.dataset.title || 'Course') : 'Course';
                showToast('Enrolled in ' + courseName, 'success');
            } else {
                self.classList.remove('enrolling');
                self.classList.add('enroll');
                self.innerHTML = '<i class="fas fa-plus-circle"></i> Enroll';
                self.disabled = false;
                showToast(data.error || 'Failed to enroll. Please try again.', 'error');
            }
        }).catch(function() {
            self.classList.remove('enrolling');
            self.classList.add('enroll');
            self.innerHTML = '<i class="fas fa-plus-circle"></i> Enroll';
            self.disabled = false;
            showToast('Network error. Please try again.', 'error');
        });
    }

    function doTakePath(btn) {
        var pathId = parseInt(btn.dataset.pathId, 10);
        if (!pathId || btn.disabled) return;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';

        fetch('pages/learner/ajax/join-learning-path.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': window.CSRF_TOKEN || ''
            },
            body: JSON.stringify({ learning_path_id: pathId })
        })
        .then(function(response) {
            return response.json().then(function(data) {
                if (!response.ok || !data.success) throw new Error(data.message || 'Unable to take this learning path.');
                return data;
            });
        })
        .then(function(data) {
            showToast(data.message || 'Learning path added to My Learning Path.', 'success');
            window.setTimeout(function() { window.location.reload(); }, 600);
        })
        .catch(function(error) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-route"></i> Take Path';
            showToast(error.message || 'Unable to take this learning path.', 'error');
        });
    }

    grid.addEventListener('click', function(event) {
        var button = event.target.closest('.cc-take-path-btn');
        if (!button) return;
        event.preventDefault();
        event.stopPropagation();
        doTakePath(button);
    });

    grid.addEventListener('click', function(e) {
        var btn = e.target.closest('.cc-enroll-btn.enroll');
        if (btn) { e.stopPropagation(); doEnroll(btn); }
    });

    emEnrollBtn.addEventListener('click', function(e) { e.stopPropagation(); doEnroll(this); });

    // ---- Request Course Modal ----
    var reqBtn = document.getElementById('request-course-btn');
    var reqModal = document.getElementById('request-modal');
    var closeReq = document.getElementById('close-request-modal');
    var cancelReq = document.getElementById('cancel-request-btn');
    var reqForm = document.getElementById('request-course-form');
    if (reqBtn) reqBtn.addEventListener('click', function() { reqModal.style.display = 'block'; });
    if (closeReq) closeReq.addEventListener('click', function() { reqModal.style.display = 'none'; });
    if (cancelReq) cancelReq.addEventListener('click', function() { reqModal.style.display = 'none'; });
    if (reqModal) reqModal.addEventListener('click', function(e) { if (e.target === this) this.style.display = 'none'; });
    if (reqForm) reqForm.addEventListener('submit', function(e) {
        e.preventDefault();
        var data = new FormData(this);
        var status = document.getElementById('request-status');
        fetch('pages/learner/catalog-subpage/ajax/engagement/request-content.php', {
            method: 'POST', body: data
        }).then(function(r) { return r.json(); }).then(function(result) {
            status.style.display = 'block';
            if (result.success) {
                status.style.background = 'rgba(16,185,129,0.15)'; status.style.color = '#059669';
                status.textContent = 'Request submitted!';
                reqForm.reset();
                setTimeout(function() { reqModal.style.display = 'none'; status.style.display = 'none'; }, 3000);
            } else {
                status.style.background = 'rgba(239,68,68,0.1)'; status.style.color = '#dc2626';
                status.textContent = result.message || 'Failed to submit.';
            }
        }).catch(function() {
            status.style.display = 'block';
            status.style.background = 'rgba(239,68,68,0.1)'; status.style.color = '#dc2626';
            status.textContent = 'Network error.';
        });
    });

    // Initial render
    renderCards();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCatalogBrowse, { once: true });
    } else {
        initCatalogBrowse();
    }
    window.addEventListener('page:loaded', initCatalogBrowse);
})();
