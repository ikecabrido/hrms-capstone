/* Course Mappings CRUD — admin/settings.php */
(function() {
    'use strict';

    function escHtml(s) { var d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

    function loadRecommendationMappings() {
        fetch('pages/admin/ajax/recommendation-map.php?action=list')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) return;
            var items = data.items || [];
            var el = document.getElementById('rec-mapping-table');
            if (!el) return;
            if (items.length === 0) {
                el.innerHTML = '<p style="color:var(--muted);font-size:0.85rem;">No mappings configured. Add one above.</p>';
                return;
            }
            var html = '<table style="width:100%;border-collapse:collapse;font-size:0.85rem;">';
            html += '<thead><tr style="border-bottom:2px solid var(--border);">';
            html += '<th style="text-align:left;padding:0.5rem;color:var(--primary);">Development Area</th>';
            html += '<th style="text-align:left;padding:0.5rem;color:var(--primary);">Course</th>';
            html += '<th style="text-align:right;padding:0.5rem;color:var(--primary);">Actions</th>';
            html += '</tr></thead><tbody>';
            items.forEach(function(item) {
                html += '<tr style="border-bottom:1px solid var(--border);">';
                html += '<td style="padding:0.5rem;">' + escHtml(item.development_area) + '</td>';
                html += '<td style="padding:0.5rem;">' + escHtml(item.course_title) + '</td>';
                html += '<td style="padding:0.5rem;text-align:right;">';
                html += '<button onclick="editRecMap(' + item.id + ')" style="background:none;border:none;color:var(--primary);cursor:pointer;font-size:0.8rem;margin-right:0.5rem;">Edit</button>';
                html += '<button onclick="deleteRecMap(' + item.id + ')" style="background:none;border:none;color:#dc3545;cursor:pointer;font-size:0.8rem;">Delete</button>';
                html += '</td></tr>';
            });
            html += '</tbody></table>';
            el.innerHTML = html;
        });
    }

    function loadRecognitionMappings() {
        fetch('pages/admin/ajax/recognition-map.php?action=list')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) return;
            var items = data.items || [];
            var el = document.getElementById('recog-mapping-table');
            if (!el) return;
            if (items.length === 0) {
                el.innerHTML = '<p style="color:var(--muted);font-size:0.85rem;">No mappings configured. Add one above.</p>';
                return;
            }
            var html = '<table style="width:100%;border-collapse:collapse;font-size:0.85rem;">';
            html += '<thead><tr style="border-bottom:2px solid var(--border);">';
            html += '<th style="text-align:left;padding:0.5rem;color:var(--primary);">Recognition Category</th>';
            html += '<th style="text-align:left;padding:0.5rem;color:var(--primary);">Course</th>';
            html += '<th style="text-align:right;padding:0.5rem;color:var(--primary);">Actions</th>';
            html += '</tr></thead><tbody>';
            items.forEach(function(item) {
                html += '<tr style="border-bottom:1px solid var(--border);">';
                html += '<td style="padding:0.5rem;">' + escHtml(item.recognition_category) + '</td>';
                html += '<td style="padding:0.5rem;">' + escHtml(item.course_title) + '</td>';
                html += '<td style="padding:0.5rem;text-align:right;">';
                html += '<button onclick="editRecogMap(' + item.id + ')" style="background:none;border:none;color:var(--primary);cursor:pointer;font-size:0.8rem;margin-right:0.5rem;">Edit</button>';
                html += '<button onclick="deleteRecogMap(' + item.id + ')" style="background:none;border:none;color:#dc3545;cursor:pointer;font-size:0.8rem;">Delete</button>';
                html += '</td></tr>';
            });
            html += '</tbody></table>';
            el.innerHTML = html;
        });
    }

    // Add recommendation mapping
    function initRecommendationAdd() {
        var btn = document.getElementById('rec-add-btn');
        if (!btn || btn.dataset.init) return;
        btn.dataset.init = '1';
        btn.addEventListener('click', function() {
            var area = document.getElementById('rec-dev-area').value.trim();
            var courseId = parseInt(document.getElementById('rec-course-id').value, 10);
            if (!area || !courseId) { if (window.showToast) window.showToast('Fill in both fields', 'error'); return; }
            var fd = new FormData(); fd.append('action', 'add'); fd.append('development_area', area); fd.append('course_id', courseId);
            fetch('pages/admin/ajax/recommendation-map.php', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    document.getElementById('rec-dev-area').value = '';
                    document.getElementById('rec-course-id').value = '0';
                    loadRecommendationMappings();
                    if (window.showToast) window.showToast('Mapping added', 'success');
                } else {
                    if (window.showToast) window.showToast(data.message || 'Failed', 'error');
                }
            });
        });
    }

    // Add recognition mapping
    function initRecognitionAdd() {
        var btn = document.getElementById('recog-add-btn');
        if (!btn || btn.dataset.init) return;
        btn.dataset.init = '1';
        btn.addEventListener('click', function() {
            var cat = document.getElementById('recog-category').value.trim();
            var courseId = parseInt(document.getElementById('recog-course-id').value, 10);
            if (!cat || !courseId) { if (window.showToast) window.showToast('Fill in both fields', 'error'); return; }
            var fd = new FormData(); fd.append('action', 'add'); fd.append('recognition_category', cat); fd.append('course_id', courseId);
            fetch('pages/admin/ajax/recognition-map.php', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    document.getElementById('recog-category').value = '';
                    document.getElementById('recog-course-id').value = '0';
                    loadRecognitionMappings();
                    if (window.showToast) window.showToast('Mapping added', 'success');
                } else {
                    if (window.showToast) window.showToast(data.message || 'Failed', 'error');
                }
            });
        });
    }

    // Edit/delete handlers
    window.editRecMap = function(id) {
        fetch('pages/admin/ajax/recommendation-map.php?action=get&id=' + id)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) return;
            var item = data.item;
            var newArea = prompt('Development Area:', item.development_area);
            if (newArea === null) return;
            var fd = new FormData(); fd.append('action', 'edit'); fd.append('id', id); fd.append('development_area', newArea); fd.append('course_id', item.course_id);
            fetch('pages/admin/ajax/recommendation-map.php', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(d) { loadRecommendationMappings(); if (window.showToast) window.showToast(d.message, d.success ? 'success' : 'error'); });
        });
    };

    window.deleteRecMap = function(id) {
        if (!confirm('Delete this mapping?')) return;
        var fd = new FormData(); fd.append('action', 'delete'); fd.append('id', id);
        fetch('pages/admin/ajax/recommendation-map.php', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) { loadRecommendationMappings(); if (window.showToast) window.showToast(d.message, d.success ? 'success' : 'error'); });
    };

    window.editRecogMap = function(id) {
        fetch('pages/admin/ajax/recognition-map.php?action=get&id=' + id)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) return;
            var item = data.item;
            var newCat = prompt('Recognition Category:', item.recognition_category);
            if (newCat === null) return;
            var fd = new FormData(); fd.append('action', 'edit'); fd.append('id', id); fd.append('recognition_category', newCat); fd.append('course_id', item.course_id);
            fetch('pages/admin/ajax/recognition-map.php', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(d) { loadRecognitionMappings(); if (window.showToast) window.showToast(d.message, d.success ? 'success' : 'error'); });
        });
    };

    window.deleteRecogMap = function(id) {
        if (!confirm('Delete this mapping?')) return;
        var fd = new FormData(); fd.append('action', 'delete'); fd.append('id', id);
        fetch('pages/admin/ajax/recognition-map.php', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) { loadRecognitionMappings(); if (window.showToast) window.showToast(d.message, d.success ? 'success' : 'error'); });
    };

    // Init
    function init() {
        initRecommendationAdd();
        initRecognitionAdd();
        if (document.getElementById('rec-mapping-table')) {
            loadRecommendationMappings();
            loadRecognitionMappings();
        }
        // Reload on tab switch
        var tabContent = document.querySelector('[data-tab="tab-course-mappings"]');
        if (tabContent && window.MutationObserver) {
            new MutationObserver(function() {
                if (tabContent.classList.contains('active')) {
                    loadRecommendationMappings();
                    loadRecognitionMappings();
                }
            }).observe(tabContent, { attributes: true, attributeFilter: ['class'] });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
