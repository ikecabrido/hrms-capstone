/* Course Mappings CRUD — admin/settings.php (Integrations tab) */
(function() {
    'use strict';

    function escHtml(s) { var d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

    function tableHtml(firstHeader, firstValue, rows) {
        var html = '<table class="settings-table"><thead><tr>';
        html += '<th>' + escHtml(firstHeader) + '</th>';
        html += '<th>Course</th>';
        html += '<th>Actions</th>';
        html += '</tr></thead><tbody>';
        rows.forEach(function(cells) { html += '<tr>' + cells + '</tr>'; });
        html += '</tbody></table>';
        return html;
    }

    function emptyHtml() {
        return '<p class="settings-empty">No mappings yet &mdash; add one above.</p>';
    }

    function loadRecommendationMappings() {
        fetch('pages/admin/ajax/recommendation-map.php?action=list')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) return;
            var items = data.items || [];
            var el = document.getElementById('rec-mapping-table');
            if (!el) return;
            if (items.length === 0) { el.innerHTML = emptyHtml(); return; }
            var rows = items.map(function(item) {
                return '<td>' + escHtml(item.development_area) + '</td>'
                    + '<td>' + escHtml(item.course_title) + '</td>'
                    + '<td>'
                    + '<button type="button" class="settings-row-action" onclick="editRecMap(' + item.id + ')">Edit</button>'
                    + '<button type="button" class="settings-row-action is-danger" onclick="deleteRecMap(' + item.id + ')">Delete</button>'
                    + '</td>';
            });
            el.innerHTML = tableHtml('Development Area', 'development_area', rows);
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
            if (items.length === 0) { el.innerHTML = emptyHtml(); return; }
            var rows = items.map(function(item) {
                return '<td>' + escHtml(item.recognition_category) + '</td>'
                    + '<td>' + escHtml(item.course_title) + '</td>'
                    + '<td>'
                    + '<button type="button" class="settings-row-action" onclick="editRecogMap(' + item.id + ')">Edit</button>'
                    + '<button type="button" class="settings-row-action is-danger" onclick="deleteRecogMap(' + item.id + ')">Delete</button>'
                    + '</td>';
            });
            el.innerHTML = tableHtml('Recognition Category', 'recognition_category', rows);
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
        // Refresh the tables when the Integrations tab is opened.
        var tabContent = document.querySelector('.tab-content[data-tab="tab-integrations"]');
        if (tabContent && window.MutationObserver && !tabContent.dataset.mapObserver) {
            tabContent.dataset.mapObserver = '1';
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
