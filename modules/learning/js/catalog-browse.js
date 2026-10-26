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

    // The recommendation rail only exists on the learner catalog. Pages that
    // reuse the catalog card markup for instructors (e.g. the E-Learning page)
    // have no rail and drive their own master/type tabs, search, view toggle and
    // pagination from their inline scripts, so nothing below may bind there.
    if (!recTrack || !recSection) return;

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
            var reasons = (rec.reasons || []).slice(0, 2).join(' · ');
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
                ' data-instructor="' + (rec.instructor_name || '').replace(/"/g, '&quot;') + '"' +
                ' data-instructor-url="' + (rec.instructor_profile_url || '').replace(/"/g, '&quot;') + '">' +
                '<div class="catalog-card-thumb" style="background:linear-gradient(135deg,var(--primary),var(--accent)); height:110px;"><i class="fas fa-graduation-cap thumb-icon"></i><div class="thumb-overlay"></div></div>' +
                '<div class="catalog-card-body" style="padding:0.8rem 1rem;">' +
                    '<h4 style="font-size:0.9rem; margin-bottom:0.25rem;">' + rec.title + '</h4>' +
                    '<div class="cc-instructor" style="margin-bottom:0.3rem;"><i class="fas fa-user-tie"></i> <a href="' + (rec.instructor_profile_url || '#').replace(/"/g, '&quot;') + '" onclick="event.stopPropagation();">' + (rec.instructor_name || '') + '</a></div>' +
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
    var emStructureContent = document.getElementById('cem-structure-content');
    var emPerformanceContent = document.getElementById('cem-performance-content');
    var emEnrollBtn = document.getElementById('cem-enroll-btn');
    var emState = { type: '', id: 0, link: '', enrolled: false, activeTab: 'overview', data: {} };
    var typeLabels = { course: 'Course', program: 'Program', 'learning-path': 'Learning Path', 'video-conference': 'Online Training', module: 'Module', lesson: 'Lesson', quiz: 'Quiz' };

    function syncEmTabs() {
        emOverlay.querySelectorAll('.entity-content-tab').forEach(function(btn) {
            var tabName = btn.dataset.contentTab || btn.dataset.cemTab;
            btn.classList.toggle('active', emState.activeTab === tabName);
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
        var instructorUrl = card.dataset.instructorUrl || '';
        var instructorValue = instructorUrl
            ? '<a href="' + escapeModalText(instructorUrl) + '">' + escapeModalText(instructorName) + '</a>'
            : escapeModalText(instructorName);

        emState = { type: itemType, id: id, link: link, enrolled: enrolled, activeTab: 'overview', data: { title: title, description: desc, category: cat, instructor_name: instructorName, course_id: card.dataset.courseId || '' } };
        emTitle.textContent = title;
        emViewBtn.href = link;
        emViewBtn.style.display = link ? '' : 'none';

        // Handle video-conference specific tabs
        var tabBtns = emOverlay.querySelectorAll('.entity-content-tab');
        var supportsStructureAndPerformance = ['course', 'module', 'learning-path'].indexOf(itemType) !== -1;
        if (tabBtns[1]) tabBtns[1].style.display = supportsStructureAndPerformance ? '' : 'none';
        if (tabBtns[2]) tabBtns[2].style.display = supportsStructureAndPerformance ? '' : 'none';
        // With a single visible tab the segmented bar adds nothing — hide it.
        var emTabsBar = emOverlay.querySelector('.entity-content-tabs');
        if (emTabsBar) emTabsBar.style.display = supportsStructureAndPerformance ? '' : 'none';

        syncEmTabs();

        // Overview grid
        var overviewHtml = '';
        if (itemType === 'video-conference') {
            var scheduled = card.dataset.scheduled || '';
            var duration = card.dataset.duration || '';
            var platform = card.dataset.platform || '';
            var platformLabel = platform.replace('_', ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); });
            var scheduledDate = '';
            if (scheduled) {
                var d = new Date(scheduled.replace(' ', 'T'));
                scheduledDate = d.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) + ' at ' + d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
            }
            overviewHtml += '<div class="ec-field"><span class="ec-field-label">Type</span><p class="ec-field-value"><i class="fas fa-video" aria-hidden="true"></i>Online Training</p></div>';
            if (platformLabel) overviewHtml += '<div class="ec-field"><span class="ec-field-label">Platform</span><p class="ec-field-value"><span class="ec-pill is-primary">' + platformLabel + '</span></p></div>';
            if (scheduledDate) overviewHtml += '<div class="ec-field"><span class="ec-field-label">Scheduled</span><p class="ec-field-value">' + scheduledDate + '</p></div>';
            if (duration) overviewHtml += '<div class="ec-field"><span class="ec-field-label">Duration</span><p class="ec-field-value">' + duration + ' minutes</p></div>';
            if (instructorName) overviewHtml += '<div class="ec-field"><span class="ec-field-label">Host</span><p class="ec-field-value"><i class="fas fa-user-tie" aria-hidden="true"></i>' + instructorValue + '</p></div>';
        } else {
            overviewHtml += '<div class="ec-field"><span class="ec-field-label">Type</span><p class="ec-field-value">' + label + '</p></div>';
            overviewHtml += '<div class="ec-field"><span class="ec-field-label">Status</span><p class="ec-field-value"><span class="ec-pill ' + (enrolled ? 'is-success' : 'is-primary') + '">' + (enrolled ? 'Enrolled' : 'Available') + '</span></p></div>';
            overviewHtml += '<div class="ec-field"><span class="ec-field-label">Category</span><p class="ec-field-value">' + cat + '</p></div>';
            if (instructorName) overviewHtml += '<div class="ec-field"><span class="ec-field-label">Trainer</span><p class="ec-field-value">' + instructorValue + '</p></div>';
        }
        emOverviewGrid.innerHTML = overviewHtml;
        emDescription.textContent = desc;

        // Enroll / Join button
        if (itemType === 'video-conference') {
            var meetingLink = card.dataset.meetingLink || '';
            var vcScheduledTs = card.dataset.scheduled ? new Date(card.dataset.scheduled.replace(' ', 'T')).getTime() : 0;
            var isPast = vcScheduledTs > 0 && vcScheduledTs < Date.now();
            emEnrollBtn.style.display = '';
            emEnrollBtn.classList.remove('is-success', 'is-info', 'is-warn', 'is-muted', 'enrolled');
            if (isPast) {
                emEnrollBtn.textContent = 'Session Ended';
                emEnrollBtn.disabled = true;
                emEnrollBtn.classList.add('is-muted');
                emEnrollBtn.onclick = null;
            } else if (meetingLink) {
                emEnrollBtn.textContent = 'Join Session';
                emEnrollBtn.disabled = false;
                emEnrollBtn.classList.add('is-info');
                emEnrollBtn.onclick = function() { window.open(meetingLink, '_blank'); };
            } else {
                emEnrollBtn.textContent = 'Link Not Available';
                emEnrollBtn.disabled = true;
                emEnrollBtn.classList.add('is-warn');
                emEnrollBtn.onclick = null;
            }
        } else if (itemType === 'course' && !enrolled) {
            emEnrollBtn.style.display = '';
            emEnrollBtn.textContent = 'Enroll Now';
            emEnrollBtn.disabled = false;
            emEnrollBtn.dataset.courseId = id;
            emEnrollBtn.classList.remove('is-info', 'is-warn', 'is-muted', 'enrolled');
            emEnrollBtn.classList.add('is-success');
            emEnrollBtn.onclick = function() { doEnroll(emEnrollBtn); };
        } else if (itemType === 'course' && enrolled) {
            emEnrollBtn.style.display = '';
            emEnrollBtn.textContent = 'Enrolled';
            emEnrollBtn.disabled = true;
            emEnrollBtn.classList.remove('is-info', 'is-warn', 'is-muted');
            emEnrollBtn.classList.add('is-success', 'enrolled');
            emEnrollBtn.onclick = null;
        } else {
            emEnrollBtn.style.display = 'none';
        }

        // Load structure tab lazily
        emStructureContent.innerHTML = '<div class="ec-empty"><i class="fas fa-spinner fa-spin" aria-hidden="true"></i>Loading structure...</div>';
        emPerformanceContent.innerHTML = '<div class="ec-empty"><i class="fas fa-spinner fa-spin" aria-hidden="true"></i>Loading performance data...</div>';

        emOverlay.style.display = 'flex';
    }

    function escapeModalText(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function(character) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[character];
        });
    }

    function loadLearningPathDetails(view) {
        var target = view === 'structure' ? emStructureContent : emPerformanceContent;
        target.innerHTML = '<div class="ec-empty"><i class="fas fa-spinner fa-spin" aria-hidden="true"></i>Loading learning-path details...</div>';
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
                    target.innerHTML = '<div class="ec-empty is-inline"><i class="fas fa-route" aria-hidden="true"></i>This learning path has no active items.</div>';
                    return;
                }
                target.innerHTML = '<div class="ec-list">' + items.map(function(item, index) {
                    var type = escapeModalText((item.item_type || 'item').replace('-', ' '));
                    var parent = item.course_id ? 'Course item' : 'Standalone item';
                    return '<div class="ec-row">' +
                        '<span class="ec-row-num">' + String(index + 1).padStart(2, '0') + '</span>' +
                        '<div class="ec-row-body"><div class="ec-row-title">' + escapeModalText(item.title) + '</div><div class="ec-row-meta">' + type + ' · ' + parent + '</div></div>' +
                        (item.study_status === 'completed' ? '<span class="ec-pill is-success"><i class="fas fa-check" aria-hidden="true"></i>Completed</span>' : '') +
                        '</div>';
                }).join('') + '</div>';
                return;
            }

            var summary = data.summary || { total_steps: 0, completed: 0, in_progress: 0, not_started: 0, progress_percent: 0 };
            target.innerHTML =
                '<div class="ec-meter"><span class="ec-meter-label">Path completion</span><span class="ec-meter-value">' + Number(summary.progress_percent || 0) + '%</span></div>' +
                '<div class="ec-bar"><div class="ec-bar-fill" style="width:' + Number(summary.progress_percent || 0) + '%;"></div></div>' +
                '<div class="ec-stats">' +
                '<span><strong>' + Number(summary.completed || 0) + '</strong> completed</span>' +
                '<span><strong>' + Number(summary.in_progress || 0) + '</strong> in progress</span>' +
                '<span><strong>' + Number(summary.not_started || 0) + '</strong> not started</span>' +
                '<span><strong>' + Number(summary.total_steps || 0) + '</strong> total steps</span>' +
                '</div>' +
                (items.length ? '<div class="ec-list">' + items.map(function(item) {
                    var status = item.study_status === 'completed' ? 'is-success' : item.study_status === 'in_progress' ? 'is-info' : 'is-muted';
                    var label = item.study_status === 'completed' ? 'Completed' : item.study_status === 'in_progress' ? 'In progress' : 'Not started';
                    return '<div class="ec-row is-tight"><div class="ec-row-body"><div class="ec-row-title">' + escapeModalText(item.title) + '</div></div><span class="ec-pill ' + status + '">' + label + '</span></div>';
                }).join('') + '</div>' : '<div class="ec-empty is-inline">No path items to measure yet.</div>');
        })
        .catch(function(error) {
            target.innerHTML = '<div class="ec-empty is-inline is-error"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i>' + escapeModalText(error.message || 'Unable to load learning-path details.') + '</div>';
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
            emStructureContent.innerHTML = '<div class="ec-empty is-inline"><i class="fas fa-diagram-project" aria-hidden="true"></i>No structure available for this item type.</div>';
            return;
        }
        fetch(endpoint, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success || !data.structure) {
                    emStructureContent.innerHTML = '<div class="ec-empty is-inline"><i class="fas fa-diagram-project" aria-hidden="true"></i>No structure data available.</div>';
                    return;
                }
                var html = '<div class="ec-tree">';
                var structure = data.structure;
                if (itemType === 'module') {
                    structure.modules = (structure.modules || []).filter(function(module) { return String(module.id) === String(id); });
                    if (!structure.modules.length) {
                        emStructureContent.innerHTML = '<div class="ec-empty is-inline"><i class="fas fa-diagram-project" aria-hidden="true"></i>No active structure found for this module.</div>';
                        return;
                    }
                }
                if (structure.modules) {
                    structure.modules.forEach(function(mod) {
                        html += '<div class="ec-tree-group">';
                        html += '<div class="ec-tree-head"><i class="fas fa-folder" aria-hidden="true"></i>' + (mod.title || 'Module') + '</div>';
                        if (mod.lessons && mod.lessons.length) {
                            html += '<div class="ec-tree-items">';
                            mod.lessons.forEach(function(lesson) {
                                html += '<div class="ec-tree-item"><i class="fas fa-file-alt" aria-hidden="true"></i><span>' + (lesson.title || 'Lesson') + '</span></div>';
                                if (lesson.quizzes && lesson.quizzes.length) {
                                    lesson.quizzes.forEach(function(quiz) {
                                        html += '<div class="ec-tree-item is-2 is-quiz"><i class="fas fa-question-circle" aria-hidden="true"></i><span>' + (quiz.title || 'Quiz') + '</span></div>';
                                    });
                                }
                            });
                            html += '</div>';
                        }
                        html += '</div>';
                    });
                }
                if (structure.evaluations && structure.evaluations.length) {
                    html += '<div class="ec-tree-group">';
                    html += '<div class="ec-tree-head"><i class="fas fa-clipboard-check" aria-hidden="true"></i>Evaluations<span class="ec-pill is-primary">' + structure.evaluations.length + '</span></div>';
                    html += '<div class="ec-tree-items">';
                    structure.evaluations.forEach(function(ev) {
                        html += '<div class="ec-tree-item is-eval"><i class="fas fa-clipboard-check" aria-hidden="true"></i><span>' + (ev.title || 'Evaluation') + '</span></div>';
                    });
                    html += '</div></div>';
                }
                html += '</div>';
                emStructureContent.innerHTML = html;
            })
            .catch(function() {
                emStructureContent.innerHTML = '<div class="ec-empty is-inline is-error"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i>Unable to load structure.</div>';
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
            emPerformanceContent.innerHTML = '<div class="ec-empty is-inline"><i class="fas fa-chart-line" aria-hidden="true"></i>Performance data not available for this item type.</div>';
            return;
        }
        fetch(endpoint, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) {
                    emPerformanceContent.innerHTML = '<div class="ec-empty is-inline"><i class="fas fa-chart-line" aria-hidden="true"></i>No performance data available yet.</div>';
                    return;
                }
                var rawItems = data.items || data.progress || [];
                if (rawItems.length === 0) {
                    emPerformanceContent.innerHTML = '<div class="ec-empty is-inline"><i class="fas fa-chart-line" aria-hidden="true"></i>No performance data available yet. Start the course to track your progress.</div>';
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
                var overallTone = overallScore >= 70 ? 'is-success' : 'is-warn';

                // Build HTML
                var html = '';

                // Summary tiles
                html += '<div class="ec-cards">';
                html += '<div class="ec-card is-center ' + overallTone + '"><div class="ec-card-label">Overall</div><div class="ec-card-value">' + overallScore + '%</div></div>';
                html += '<div class="ec-card is-center is-success"><div class="ec-card-label">Modules</div><div class="ec-card-value">' + completedModules + '<small>/' + totalModules + '</small></div></div>';
                html += '<div class="ec-card is-center is-info"><div class="ec-card-label">Quizzes</div><div class="ec-card-value">' + passedQuizzes + '<small>/' + totalQuizzes + '</small></div></div>';
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
                html += '<div id="perf-items-grid" class="ec-list">';
                items.forEach(function(item, idx) {
                    var score = item.score || 0;
                    var status = item.status || 'In progress';
                    var isQuiz = item.type === 'quiz';
                    var isModule = !isQuiz && item.type !== 'evaluation' && item.lessons_total !== undefined;
                    var refId = item.reference_id || 0;
                    var statusTone = (status === 'Completed' || status === 'Passed') ? 'is-success' : status === 'In progress' ? 'is-info' : 'is-warn';
                    var icon = item.type === 'evaluation'
                        ? '<i class="fas fa-clipboard-check is-eval" aria-hidden="true"></i>'
                        : isQuiz
                            ? '<i class="fas fa-question-circle is-quiz" aria-hidden="true"></i>'
                            : '<i class="fas fa-book-open is-module" aria-hidden="true"></i>';
                    var clickable = (isQuiz && refId > 0) || (isModule && refId > 0);

                    var statusKey = status === 'Completed' || status === 'Passed' ? 'completed' : status === 'In progress' ? 'in_progress' : 'not_started';
                    html += '<div class="perf-row' + (clickable ? ' is-clickable' : '') + '"' + (clickable ? ' data-quiz-id="' + (isQuiz ? refId : '') + '" data-module-id="' + (isModule ? refId : '') + '" data-row-idx="' + idx + '"' : '') + ' data-status="' + statusKey + '">';
                    html += '<div class="perf-row-main">';
                    html += '<span class="perf-row-title">' + icon + '<span>' + (item.title || item.name || 'Item') + '</span></span>';
                    html += '<span class="perf-row-score">' + score + '%</span>';
                    html += '<span class="ec-pill ' + statusTone + '">' + status + '</span>';
                    if (clickable) {
                        html += '<span class="perf-chevron"><i class="fas fa-chevron-down" aria-hidden="true"></i></span>';
                    }
                    html += '</div>';

                    // Progress bar for modules
                    if (isModule && item.lessons_total > 0) {
                        var pct = item.lessons_completed || 0;
                        var total = item.lessons_total || 1;
                        var barPct = Math.round((pct / total) * 100);
                        var barTone = barPct === 100 ? 'is-success' : barPct > 0 ? 'is-info' : '';
                        html += '<div class="perf-bar-wrap">';
                        html += '<div class="perf-bar-head"><span>' + pct + ' of ' + total + ' lessons</span><strong>' + barPct + '%</strong></div>';
                        html += '<div class="perf-bar"><div class="perf-bar-fill ' + barTone + '" style="width:' + barPct + '%;"></div></div>';
                        html += '</div>';
                    }

                    // Expand panel
                    if (clickable) {
                        html += '<div class="perf-expand" id="perf-expand-' + idx + '" style="display:none;"></div>';
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
                        btn.classList.toggle('active', isActive);
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
                emPerformanceContent.innerHTML = '<div class="ec-empty is-inline is-error"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i>Unable to load performance data.</div>';
            });
    }

    function loadModuleLessons(moduleId, rawItems, container) {
        var mod = null;
        rawItems.forEach(function(item) {
            if (item.reference_id && String(item.reference_id) === String(moduleId)) mod = item;
        });
        if (!mod) { container.innerHTML = '<div class="ec-empty is-inline">No lesson data.</div>'; return; }
        var html = '<div class="ec-tree-items">';
        // Lessons
        var lessons = mod.lesson_details || [];
        if (lessons.length > 0) {
            lessons.forEach(function(lesson) {
                var icon = lesson.completed ? 'fa-check-circle' : 'fa-circle';
                html += '<div class="ec-tree-item' + (lesson.completed ? '' : ' is-pending') + '">';
                html += '<i class="fas ' + icon + (lesson.completed ? ' is-done' : '') + '" aria-hidden="true"></i>';
                html += '<span>' + (lesson.title || 'Lesson') + '</span>';
                if (lesson.content_type) html += '<span class="ec-pill is-info">' + lesson.content_type + '</span>';
                html += '</div>';
            });
        } else {
            html += '<div class="ec-tree-empty">No lessons in this module.</div>';
        }
        html += '</div>';
        // Quizzes summary
        if (mod.quizzes && mod.quizzes.length > 0) {
            html += '<div class="ec-divider"></div><div class="ec-tree-items">';
            mod.quizzes.forEach(function(quiz) {
                var passed = quiz.status === 'Passed';
                html += '<div class="ec-tree-item is-quiz">';
                html += '<i class="fas fa-question-circle" aria-hidden="true"></i>';
                html += '<span>' + (quiz.title || 'Quiz') + '</span>';
                html += '<span class="ec-pill ' + (passed ? 'is-success' : 'is-warn') + '">' + quiz.status + '</span>';
                html += '</div>';
            });
            html += '</div>';
        }
        container.innerHTML = html;
    }

    function loadQuizAttempts(quizId, container) {
        container.innerHTML = '<div class="ec-empty is-inline"><i class="fas fa-spinner fa-spin" aria-hidden="true"></i>Loading attempts...</div>';
        fetch('pages/learner/ajax/get-quiz-result.php?quiz_id=' + quizId, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success || !data.items || data.items.length === 0) {
                    container.innerHTML = '<div class="ec-empty is-inline">No attempts recorded yet.</div>';
                    return;
                }
                var summary = data.summary || {};
                var html = '';
                if (summary.quiz_title) {
                    html += '<div class="ec-stats">';
                    if (summary.best_score !== undefined) html += '<span><strong>Best:</strong> ' + summary.best_score + '%</span>';
                    if (summary.total_attempts !== undefined) html += '<span><strong>Attempts:</strong> ' + summary.total_attempts + '</span>';
                    if (summary.remaining_attempts !== undefined) html += '<span><strong>Remaining:</strong> ' + summary.remaining_attempts + '</span>';
                    if (summary.passing_score !== undefined) html += '<span><strong>Pass:</strong> ' + summary.passing_score + '%</span>';
                    html += '</div>';
                }
                html += '<div class="ec-list">';
                data.items.forEach(function(attempt) {
                    var aScore = attempt.score || 0;
                    var aStatus = attempt.status || 'Unknown';
                    var passed = aStatus === 'Passed';
                    var dateStr = '';
                    if (attempt.completed_at) {
                        var d = new Date(attempt.completed_at);
                        dateStr = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) + ' ' + d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                    }
                    html += '<div class="ec-row is-tight">';
                    html += '<div class="ec-row-body"><div class="ec-row-title">' + (attempt.title || 'Attempt') + '</div>';
                    if (dateStr) html += '<div class="ec-row-meta">' + dateStr + '</div>';
                    html += '</div>';
                    html += '<span class="perf-row-score">' + aScore + '%</span>';
                    html += '<span class="ec-pill ' + (passed ? 'is-success' : 'is-warn') + '">' + aStatus + '</span>';
                    html += '</div>';
                });
                html += '</div>';
                container.innerHTML = html;
            })
            .catch(function() {
                container.innerHTML = '<div class="ec-empty is-inline is-error"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i>Unable to load attempts.</div>';
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
        if (btn.disabled || btn.classList.contains('cc-take-path-btn')) return;
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
            return response.text().then(function(body) {
                var data;
                try {
                    data = body ? JSON.parse(body) : null;
                } catch (error) {
                    throw new Error('The server returned an invalid response while adding this learning path (HTTP ' + response.status + ').');
                }
                if (!data) {
                    throw new Error('The server returned an empty response while adding this learning path (HTTP ' + response.status + ').');
                }
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
