(function() {
    'use strict';

    // Use global showToast from header.php
    var showToast = window.showToast || function(m,t) { console.log('[' + t + '] ' + m); };

    var PAGE_SIZE = 12;
    var currentPage = 1;
    var currentTab = 'all';
    var searchQuery = '';
    var viewMode = 'grid';
    var allRecommendations = [];

    var grid = document.getElementById('catalog-grid');
    var emptyState = document.getElementById('catalog-empty');
    var countEl = document.getElementById('catalog-count');
    var pageIndicator = document.getElementById('page-indicator');
    var searchInput = document.getElementById('catalog-search-input');
    var allCards = Array.from(grid.querySelectorAll('.catalog-card'));

    function getFilteredCards() {
        return allCards.filter(function(card) {
            var typeMatch = currentTab === 'all' || card.dataset.type === currentTab;
            var searchMatch = !searchQuery || (card.dataset.title + ' ' + card.dataset.desc + ' ' + card.dataset.category).toLowerCase().indexOf(searchQuery) !== -1;
            return typeMatch && searchMatch;
        });
    }

    function renderCards() {
        var filtered = getFilteredCards();
        var total = filtered.length;
        var totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
        if (currentPage > totalPages) currentPage = totalPages;
        var start = (currentPage - 1) * PAGE_SIZE;
        var end = start + PAGE_SIZE;

        allCards.forEach(function(c) { c.style.display = 'none'; });
        filtered.slice(start, end).forEach(function(c) { c.style.display = ''; });

        emptyState.style.display = total === 0 ? 'block' : 'none';
        countEl.textContent = total + ' item' + (total !== 1 ? 's' : '');
        pageIndicator.textContent = 'Page ' + currentPage + ' of ' + totalPages;
        document.getElementById('catalog-pagination').style.display = totalPages > 1 ? '' : 'none';
    }

    // ---- Recommendations ----
    var recTrack = document.getElementById('recommendations-track');
    var recSection = document.getElementById('recommendations-section');
    var recPrev = document.getElementById('rec-prev');
    var recNext = document.getElementById('rec-next');

    function renderRecommendations() {
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
                '<div class="catalog-card-thumb" style="background:linear-gradient(135deg,var(--color1),var(--color2)); height:110px;"><i class="fas fa-graduation-cap thumb-icon"></i><div class="thumb-overlay"></div></div>' +
                '<div class="catalog-card-body" style="padding:0.8rem 1rem;">' +
                    '<h4 style="font-size:0.9rem; margin-bottom:0.25rem;">' + rec.title + '</h4>' +
                    '<div class="cc-instructor" style="margin-bottom:0.3rem;"><i class="fas fa-user-tie"></i> ' + rec.instructor_name + '</div>' +
                '</div>' +
                '<div class="catalog-card-footer" style="padding:0.5rem 1rem;">' +
                    '<span class="cc-deadline" style="font-size:0.72rem;">' + reasons + '</span>' +
                    '<span style="color:var(--color1);font-weight:600;font-size:0.75rem;">View &#8594;</span>' +
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

    // ---- Tab switching ----
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

    // ---- Pagination ----
    document.querySelectorAll('.page-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var action = btn.dataset.action;
            var filtered = getFilteredCards();
            var totalPages = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE));
            if (action === 'prev' && currentPage > 1) currentPage--;
            if (action === 'next' && currentPage < totalPages) currentPage++;
            renderCards();
            grid.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

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
    var typeLabels = { course: 'Course', program: 'Program', 'learning-path': 'Learning Path', 'video-conference': 'Live Session', module: 'Module', lesson: 'Lesson', quiz: 'Quiz' };

    function syncEmTabs() {
        document.querySelectorAll('.entity-content-tab').forEach(function(btn) {
            var tabName = btn.dataset.contentTab || btn.dataset.cemTab;
            var isActive = emState.activeTab === tabName;
            btn.classList.toggle('active', isActive);
            btn.style.background = isActive ? 'rgba(32,0,130,0.08)' : '#fff';
            btn.style.border = isActive ? 'none' : '1px solid rgba(32,0,130,0.12)';
            btn.style.color = isActive ? 'var(--color1)' : 'var(--color2)';
        });
        document.querySelectorAll('.entity-content-panel, .cem-panel').forEach(function(p) {
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

        emState = { type: itemType, id: id, link: link, enrolled: enrolled, activeTab: 'overview', data: { title: title, description: desc, category: cat, instructor_name: instructorName } };
        emTitle.textContent = title;
        emViewBtn.href = link;
        emViewBtn.style.display = link ? '' : 'none';

        // Handle video-conference specific tabs
        var tabBtns = document.querySelectorAll('.entity-content-tab');
        if (itemType === 'video-conference') {
            tabBtns[1].style.display = 'none';
            tabBtns[2].style.display = 'none';
        } else {
            tabBtns[1].style.display = '';
            tabBtns[2].style.display = '';
        }

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
            overviewHtml += '<div><label style="color:var(--color1); font-weight:700; font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase;">Type</label><p style="margin:0.55rem 0 0; font-size:1rem; color:var(--color2);"><i class="fas fa-video" style="color:#ef4444; margin-right:0.4rem;"></i>Video Conference</p></div>';
            overviewHtml += '<div><label style="color:var(--color1); font-weight:700; font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase;">Platform</label><p style="margin:0.55rem 0 0; font-size:1rem;"><span style="padding:0.2rem 0.6rem; border-radius:999px; background:' + pColor + '; color:#fff; font-weight:600; font-size:0.85rem;">' + platformLabel + '</span></p></div>';
            if (scheduledDate) overviewHtml += '<div><label style="color:var(--color1); font-weight:700; font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase;">Scheduled</label><p style="margin:0.55rem 0 0; font-size:1rem; color:var(--color2);">' + scheduledDate + '</p></div>';
            if (duration) overviewHtml += '<div><label style="color:var(--color1); font-weight:700; font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase;">Duration</label><p style="margin:0.55rem 0 0; font-size:1rem; color:var(--color2);">' + duration + ' minutes</p></div>';
            if (instructorName) overviewHtml += '<div><label style="color:var(--color1); font-weight:700; font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase;">Host</label><p style="margin:0.55rem 0 0; font-size:1rem; color:var(--color2);"><i class="fas fa-user-tie" style="margin-right:0.4rem; color:var(--color1);"></i>' + instructorName + '</p></div>';
        } else {
            overviewHtml += '<div><label style="color:var(--color1); font-weight:700; font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase;">Type</label><p style="margin:0.55rem 0 0; font-size:1rem; color:var(--color2);">' + label + '</p></div>';
            overviewHtml += '<div><label style="color:var(--color1); font-weight:700; font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase;">Status</label><p style="margin:0.55rem 0 0; font-size:1rem; color:var(--color2);">' + (enrolled ? 'Enrolled' : 'Available') + '</p></div>';
            overviewHtml += '<div><label style="color:var(--color1); font-weight:700; font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase;">Category</label><p style="margin:0.55rem 0 0; font-size:1rem; color:var(--color2);">' + cat + '</p></div>';
            if (instructorName) overviewHtml += '<div><label style="color:var(--color1); font-weight:700; font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase;">Instructor</label><p style="margin:0.55rem 0 0; font-size:1rem; color:var(--color2);">' + instructorName + '</p></div>';
        }
        emOverviewGrid.innerHTML = overviewHtml;
        emDescription.textContent = desc;

        // Child entities summary
        var childHtml = '<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr)); gap:0.75rem;">';
        childHtml += '<div style="padding:0.8rem; border-radius:10px; background:rgba(32,0,130,0.05); border:1px solid rgba(32,0,130,0.08);"><div style="font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase; color:var(--color1); font-weight:700;">Type</div><div style="margin-top:0.5rem; color:var(--color2); font-weight:600;">' + label + '</div></div>';
        if (itemType === 'video-conference') {
            var vcScheduled = card.dataset.scheduled || '';
            var vcDuration = card.dataset.duration || '';
            var vcPlatform = (card.dataset.platform || '').replace('_', ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); });
            childHtml += '<div style="padding:0.8rem; border-radius:10px; background:rgba(239,68,68,0.05); border:1px solid rgba(239,68,68,0.08);"><div style="font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase; color:#ef4444; font-weight:700;">Platform</div><div style="margin-top:0.5rem; color:var(--color2); font-weight:600;">' + vcPlatform + '</div></div>';
            childHtml += '<div style="padding:0.8rem; border-radius:10px; background:rgba(245,158,11,0.05); border:1px solid rgba(245,158,11,0.08);"><div style="font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase; color:#f59e0b; font-weight:700;">Duration</div><div style="margin-top:0.5rem; color:var(--color2); font-weight:600;">' + (vcDuration || 'TBA') + ' min</div></div>';
        } else {
            childHtml += '<div style="padding:0.8rem; border-radius:10px; background:rgba(16,185,129,0.05); border:1px solid rgba(16,185,129,0.08);"><div style="font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase; color:#10b981; font-weight:700;">Status</div><div style="margin-top:0.5rem; color:var(--color2); font-weight:600;">' + (enrolled ? 'Enrolled' : 'Available') + '</div></div>';
            childHtml += '<div style="padding:0.8rem; border-radius:10px; background:rgba(59,130,246,0.05); border:1px solid rgba(59,130,246,0.08);"><div style="font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase; color:#3b82f6; font-weight:700;">Category</div><div style="margin-top:0.5rem; color:var(--color2); font-weight:600;">' + cat + '</div></div>';
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

    function loadContentStructure() {
        if (!emState.id) return;
        var itemType = emState.type;
        var id = emState.id;
        var endpoint = '';
        if (itemType === 'course') endpoint = 'pages/learner/ajax/get-course-structure.php?course_id=' + id;
        else if (itemType === 'module') endpoint = 'pages/learner/ajax/get-module-structure.php?module_id=' + id;
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
                if (structure.modules) {
                    structure.modules.forEach(function(mod) {
                        html += '<div style="margin-bottom:1rem; padding:1rem; border:1px solid rgba(32,0,130,0.12); border-radius:12px; background:rgba(32,0,130,0.03);">';
                        html += '<div style="font-weight:700; color:var(--color1);"><i class="fas fa-folder" style="margin-right:0.5rem;"></i>' + (mod.title || 'Module') + '</div>';
                        if (mod.lessons && mod.lessons.length) {
                            mod.lessons.forEach(function(lesson) {
                                html += '<div style="margin:0.4rem 0 0.4rem 1.5rem; color:var(--color2);"><i class="fas fa-file-alt" style="margin-right:0.4rem; color:#3b82f6; font-size:0.85rem;"></i>' + (lesson.title || 'Lesson');
                                if (lesson.quizzes && lesson.quizzes.length) {
                                    lesson.quizzes.forEach(function(quiz) {
                                        html += '<div style="margin:0.3rem 0 0.3rem 2rem; color:var(--color2); font-size:0.9rem;"><i class="fas fa-question-circle" style="margin-right:0.4rem; color:#f59e0b; font-size:0.8rem;"></i>' + (quiz.title || 'Quiz') + '</div>';
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
                html += '<div style="font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase; color:var(--color1); font-weight:700;">Overall</div>';
                html += '<div style="margin-top:0.4rem; font-size:1.5rem; font-weight:800; color:' + overallColor + ';">' + overallScore + '%</div>';
                html += '</div>';
                html += '<div style="padding:1rem; border-radius:10px; background:rgba(16,185,129,0.05); border:1px solid rgba(16,185,129,0.08); text-align:center;">';
                html += '<div style="font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase; color:#10b981; font-weight:700;">Modules</div>';
                html += '<div style="margin-top:0.4rem; font-size:1.5rem; font-weight:800; color:var(--color2);">' + completedModules + '<span style="font-size:0.85rem; color:#999;">/' + totalModules + '</span></div>';
                html += '</div>';
                html += '<div style="padding:1rem; border-radius:10px; background:rgba(59,130,246,0.05); border:1px solid rgba(59,130,246,0.08); text-align:center;">';
                html += '<div style="font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase; color:#3b82f6; font-weight:700;">Quizzes</div>';
                html += '<div style="margin-top:0.4rem; font-size:1.5rem; font-weight:800; color:var(--color2);">' + passedQuizzes + '<span style="font-size:0.85rem; color:#999;">/' + totalQuizzes + '</span></div>';
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
                html += '<div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.75rem; flex-wrap:wrap;">';
                html += '<button type="button" class="perf-filter-btn active" data-perf-filter="all" style="padding:0.4rem 0.85rem; border-radius:999px; border:none; font-size:0.78rem; font-weight:600; cursor:pointer; background:var(--color1); color:#fff; transition:all 0.15s;">All</button>';
                html += '<button type="button" class="perf-filter-btn" data-perf-filter="in_progress" style="padding:0.4rem 0.85rem; border-radius:999px; border:1px solid rgba(32,0,130,0.15); font-size:0.78rem; font-weight:600; cursor:pointer; background:var(--color3, #fff); color:var(--color2); transition:all 0.15s;">In progress</button>';
                html += '<button type="button" class="perf-filter-btn" data-perf-filter="completed" style="padding:0.4rem 0.85rem; border-radius:999px; border:1px solid rgba(32,0,130,0.15); font-size:0.78rem; font-weight:600; cursor:pointer; background:var(--color3, #fff); color:var(--color2); transition:all 0.15s;">Completed</button>';
                html += '<button type="button" class="perf-filter-btn" data-perf-filter="not_started" style="padding:0.4rem 0.85rem; border-radius:999px; border:1px solid rgba(32,0,130,0.15); font-size:0.78rem; font-weight:600; cursor:pointer; background:var(--color3, #fff); color:var(--color2); transition:all 0.15s;">Not started</button>';
                html += '<span id="perf-filter-count" style="font-size:0.78rem; color:#999; margin-left:0.25rem;"></span>';
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
                    html += '<strong style="color:var(--color2);">' + icon + (item.title || item.name || 'Item') + '</strong>';
                    html += '<span style="color:var(--color2);">' + score + '%</span>';
                    html += '<span style="padding:0.45rem 0.65rem; border-radius:999px; background:' + statusColor + '; color:' + statusTextColor + '; font-weight:700; font-size:0.75rem; text-align:center;">' + status + '</span>';
                    if (clickable) {
                        html += '<div style="text-align:center; color:var(--color1); font-size:0.75rem;"><i class="fas fa-chevron-down" style="transition:transform 0.2s;"></i></div>';
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
                        if (isActive) {
                            btn.style.background = 'var(--color1)';
                            btn.style.color = '#fff';
                            btn.style.border = 'none';
                            btn.classList.add('active');
                        } else {
                            btn.style.background = 'var(--color3, #fff)';
                            btn.style.color = 'var(--color2)';
                            btn.style.border = '1px solid rgba(32,0,130,0.15)';
                            btn.classList.remove('active');
                        }
                    });
                }

                perfFilterBtns.forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var val = btn.dataset.perfFilter;
                        if (val === 'all') {
                            activeFilters = ['all'];
                        } else {
                            // Remove 'all' if a specific filter is toggled
                            var allIdx = activeFilters.indexOf('all');
                            if (allIdx !== -1) activeFilters.splice(allIdx, 1);
                            var valIdx = activeFilters.indexOf(val);
                            if (valIdx !== -1) {
                                activeFilters.splice(valIdx, 1);
                            } else {
                                activeFilters.push(val);
                            }
                            // If nothing selected, default to 'all'
                            if (activeFilters.length === 0) activeFilters = ['all'];
                        }
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
                html += '<span style="color:var(--color2); ' + textDeco + '">' + (lesson.title || 'Lesson') + '</span>';
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
                html += '<span style="color:var(--color2);">' + (quiz.title || 'Quiz') + '</span>';
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
                    if (summary.best_score !== undefined) html += '<span style="font-size:0.78rem; color:var(--color2);"><strong>Best:</strong> ' + summary.best_score + '%</span>';
                    if (summary.total_attempts !== undefined) html += '<span style="font-size:0.78rem; color:var(--color2);"><strong>Attempts:</strong> ' + summary.total_attempts + '</span>';
                    if (summary.remaining_attempts !== undefined) html += '<span style="font-size:0.78rem; color:var(--color2);"><strong>Remaining:</strong> ' + summary.remaining_attempts + '</span>';
                    if (summary.passing_score !== undefined) html += '<span style="font-size:0.78rem; color:var(--color2);"><strong>Pass:</strong> ' + summary.passing_score + '%</span>';
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
                    html += '<span style="font-size:0.82rem; color:var(--color2); font-weight:600;">' + (attempt.title || 'Attempt') + '</span>';
                    html += '<span style="font-size:0.82rem; color:var(--color2);">' + aScore + '%</span>';
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

    document.querySelectorAll('.entity-content-tab').forEach(function(btn) {
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
        var courseId = btn.dataset.courseId;
        if (!courseId) return;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enrolling...';
        btn.classList.remove('enroll');
        btn.classList.add('enrolling');
        var self = btn;
        fetch('pages/learner/ajax/enroll-course.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
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
})();
