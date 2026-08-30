
(function() {
    'use strict';

    var PAGE_SIZE = 12;
    var currentPage = 1;
    var currentType = 'all';
    var currentCategory = '';
    var searchQuery = '';
    var viewMode = 'grid';

    var grid = document.getElementById('catalog-grid');
    var emptyState = document.getElementById('catalog-empty');
    var countEl = document.getElementById('catalog-count');
    var pageIndicator = document.getElementById('page-indicator');
    var searchInput = document.getElementById('catalog-search-input');
    var allCards = Array.from(grid.querySelectorAll('.catalog-card'));

    function getFilteredCards() {
        return allCards.filter(function(card) {
            var typeMatch = currentType === 'all' || card.dataset.type === currentType;
            var catMatch = !currentCategory || card.dataset.category === currentCategory;
            var searchMatch = !searchQuery || (card.dataset.title + ' ' + card.dataset.desc + ' ' + card.dataset.category).toLowerCase().indexOf(searchQuery) !== -1;
            return typeMatch && catMatch && searchMatch;
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

    // Category chips
    document.querySelectorAll('.cat-chip').forEach(function(chip) {
        chip.addEventListener('click', function() {
            document.querySelectorAll('.cat-chip').forEach(function(c) { c.classList.remove('active'); });
            chip.classList.add('active');
            if (chip.dataset.cat) {
                currentType = chip.dataset.cat;
                currentCategory = '';
            }
            if (chip.dataset.catCategory) {
                currentType = 'all';
                currentCategory = chip.dataset.catCategory;
            }
            currentPage = 1;
            renderCards();
        });
    });

    // Search
    var searchTimeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            searchQuery = searchInput.value.trim().toLowerCase();
            currentPage = 1;
            renderCards();
        }, 250);
    });

    // View toggle
    document.querySelectorAll('.catalog-view-toggle button').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.catalog-view-toggle button').forEach(function(b) { b.classList.remove('active'); });
            btn.classList.add('active');
            viewMode = btn.dataset.view;
            grid.classList.toggle('list-view', viewMode === 'list');
        });
    });

    // Pagination
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

    // Card click -> modal
    var emOverlay = document.getElementById('catalog-entity-content');
    var emTitle = document.getElementById('cem-title');
    var emViewBtn = document.getElementById('cem-view-btn');
    var emCloseBtn = document.getElementById('cem-close-btn');
    var emOverviewGrid = document.getElementById('cem-overview-grid');
    var emDescription = document.getElementById('cem-description');
    var emDetailsContent = document.getElementById('cem-details-content');
    var emEnrollBtn = document.getElementById('cem-enroll-btn');
    var emState = { type: '', id: 0, link: '', enrolled: false, activeTab: 'overview' };

    var typeIcons = { course: 'fa-graduation-cap', program: 'fa-layer-group', 'learning-path': 'fa-route', 'video-conference': 'fa-video', module: 'fa-cube', lesson: 'fa-book-open', quiz: 'fa-question-circle' };
    var typeLabels = { course: 'Course', program: 'Program', 'learning-path': 'Learning Path', 'video-conference': 'Live Session', module: 'Module', lesson: 'Lesson', quiz: 'Quiz' };

    function syncEmTabs() {
        document.querySelectorAll('.cem-tab').forEach(function(btn) {
            var isActive = emState.activeTab === btn.dataset.cemTab;
            btn.classList.toggle('active', isActive);
        });
        document.querySelectorAll('.cem-panel').forEach(function(p) {
            p.style.display = p.id === 'cem-panel-' + emState.activeTab ? 'block' : 'none';
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

        emState = { type: itemType, id: id, link: link, enrolled: enrolled, activeTab: 'overview' };
        emTitle.textContent = title;
        emViewBtn.href = link;
        emViewBtn.style.display = link ? '' : 'none';
        syncEmTabs();

        var overviewHtml = '';
        overviewHtml += '<div style="padding:0.8rem; border-radius:10px; background:rgba(32,0,130,0.05); border:1px solid rgba(32,0,130,0.08);"><div style="font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase; color:var(--color1); font-weight:700;">Type</div><div style="margin-top:0.5rem; color:var(--color2); font-weight:600;">' + label + '</div></div>';
        overviewHtml += '<div style="padding:0.8rem; border-radius:10px; background:rgba(16,185,129,0.05); border:1px solid rgba(16,185,129,0.08);"><div style="font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase; color:#10b981; font-weight:700;">Status</div><div style="margin-top:0.5rem; color:var(--color2); font-weight:600;">' + (enrolled ? 'Enrolled' : 'Available') + '</div></div>';
        var cat = card.dataset.category || 'General';
        overviewHtml += '<div style="padding:0.8rem; border-radius:10px; background:rgba(59,130,246,0.05); border:1px solid rgba(59,130,246,0.08);"><div style="font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase; color:#3b82f6; font-weight:700;">Category</div><div style="margin-top:0.5rem; color:var(--color2); font-weight:600;">' + cat + '</div></div>';
        emOverviewGrid.innerHTML = overviewHtml;
        emDescription.textContent = desc;

        var detailsHtml = '<div style="display:grid; gap:0.75rem;">';
        detailsHtml += '<div style="padding:1rem; border:1px solid rgba(32,0,130,0.12); border-radius:12px; background:rgba(32,0,130,0.03);"><div style="font-size:0.72rem; letter-spacing:0.08em; text-transform:uppercase; font-weight:700; color:var(--color1);">Category</div><div style="margin-top:0.5rem; font-size:1rem; color:var(--color2);">' + cat + '</div></div>';
        detailsHtml += '</div>';
        emDetailsContent.innerHTML = detailsHtml;

        if (itemType === 'course' && !enrolled) {
            emEnrollBtn.style.display = '';
            emEnrollBtn.textContent = 'Enroll Now';
            emEnrollBtn.disabled = false;
            emEnrollBtn.dataset.courseId = id;
        } else if (itemType === 'course' && enrolled) {
            emEnrollBtn.style.display = '';
            emEnrollBtn.textContent = 'Enrolled';
            emEnrollBtn.disabled = true;
            emEnrollBtn.style.background = '#10b981';
        } else {
            emEnrollBtn.style.display = 'none';
        }

        emOverlay.style.display = 'flex';
    }

    grid.addEventListener('click', function(e) {
        // Don't open modal if clicking enroll button
        if (e.target.closest('.cc-enroll-btn')) return;
        var card = e.target.closest('.catalog-card');
        if (card) openEntityContent(card);
    });

    // Tab switching in modal
    document.querySelectorAll('.cem-tab').forEach(function(btn) {
        btn.addEventListener('click', function() {
            emState.activeTab = btn.dataset.cemTab;
            syncEmTabs();
        });
    });

    if (emCloseBtn) emCloseBtn.addEventListener('click', function() { emOverlay.style.display = 'none'; });
    if (emOverlay) emOverlay.addEventListener('click', function(e) { if (e.target === this) this.style.display = 'none'; });

    // Enroll from card
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
                // Update modal if open
                if (emOverlay.style.display === 'flex' && emState.id == courseId) {
                    emEnrollBtn.textContent = 'Enrolled';
                    emEnrollBtn.disabled = true;
                    emEnrollBtn.style.background = '#10b981';
                    emState.enrolled = true;
                }
            } else {
                self.classList.remove('enrolling');
                self.classList.add('enroll');
                self.innerHTML = '<i class="fas fa-plus-circle"></i> Enroll';
                self.disabled = false;
                alert(data.error || 'Failed to enroll. Please try again.');
            }
        }).catch(function() {
            self.classList.remove('enrolling');
            self.classList.add('enroll');
            self.innerHTML = '<i class="fas fa-plus-circle"></i> Enroll';
            self.disabled = false;
            alert('Network error. Please try again.');
        });
    }

    // Attach enroll handlers to card buttons
    grid.addEventListener('click', function(e) {
        var btn = e.target.closest('.cc-enroll-btn.enroll');
        if (btn) {
            e.stopPropagation();
            doEnroll(btn);
        }
    });

    // Modal enroll
    emEnrollBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        doEnroll(this);
    });

    // Request Course Modal
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

    // Recommendations carousel
    var recTrack = document.getElementById('recommendations-track');
    var recPrev = document.getElementById('rec-prev');
    var recNext = document.getElementById('rec-next');
    fetch('pages/learner/ajax/get-recommendations.php')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success || !data.recommendations || data.recommendations.length === 0) return;
            var section = document.getElementById('recommendations-section');
            var skillCount = document.getElementById('rec-skill-count');
            if (data.total_skills > 0) skillCount.textContent = data.total_skills + ' skill' + (data.total_skills !== 1 ? 's' : '');
            var html = '';
            data.recommendations.forEach(function(rec) {
                var reasons = rec.reasons.slice(0, 2).join(' \u2022 ');
                html += '<a href="' + rec.link + '" class="catalog-card rec-card" style="text-decoration:none;color:inherit;">' +
                    '<div class="catalog-card-thumb" style="background:linear-gradient(135deg,var(--color1),var(--color2)); height:110px;"><i class="fas fa-graduation-cap thumb-icon"></i><div class="thumb-overlay"></div></div>' +
                    '<div class="catalog-card-body" style="padding:0.8rem 1rem;">
                        <h4 style="font-size:0.9rem; margin-bottom:0.25rem;">' + rec.title + '</h4>
                        <div class="cc-instructor" style="margin-bottom:0.3rem;"><i class="fas fa-user-tie"></i> ' + rec.instructor_name + '</div>
                        <p class="cc-desc" style="margin:0; font-size:0.78rem; -webkit-line-clamp:2;">' + rec.description + '</p>
                    </div>
                    '<div class="catalog-card-footer" style="padding:0.5rem 1rem;">
                        <span class="cc-deadline" style="font-size:0.72rem;">' + reasons + '</span>
                        <span style="color:var(--color1);font-weight:600;font-size:0.75rem;">View \u2192</span>
                    </div>
                </a>';
            });
            recTrack.innerHTML = html;
            section.style.display = 'block';
        })
        .catch(function() {});
    if (recPrev) recPrev.addEventListener('click', function() { recTrack.scrollBy({ left: -540, behavior: 'smooth' }); });
    if (recNext) recNext.addEventListener('click', function() { recTrack.scrollBy({ left: 540, behavior: 'smooth' }); });

    // Initial render
    renderCards();
})();
