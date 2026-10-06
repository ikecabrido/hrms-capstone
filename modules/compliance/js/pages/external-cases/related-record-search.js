import { qs, qsa, esc, cleanJSON } from './helpers.js';

const RELATED_SEARCH_API = '/hrms-capstone/modules/compliance/lib/api/search-related-records.php';

export function initEditRelatedSearch() {
    var typeSelect = qs('#ecEditRelatedType');
    var searchInput = qs('#ecEditRelatedSearchInput');
    var resultsBox = qs('#ecEditRelatedSearchResults');
    var hiddenId = qs('#ecEditRelatedId');
    var searchField = qs('#ecEditRelatedSearchField');

    if (!typeSelect || !searchInput || !resultsBox || !hiddenId) return;

    typeSelect.addEventListener('change', function () {
        var val = this.value;
        if (searchField) {
            searchField.style.display = (val && val !== 'none') ? '' : 'none';
        }
        searchInput.value = '';
        hiddenId.value = '';
        resultsBox.style.display = 'none';
        resultsBox.innerHTML = '';
    });

    var timer;
    searchInput.addEventListener('input', function () {
        clearTimeout(timer);
        var q = searchInput.value.trim();
        if (q.length < 1) {
            resultsBox.style.display = 'none';
            resultsBox.innerHTML = '';
            return;
        }

        var type = typeSelect.value;
        if (!type || type === 'none') {
            resultsBox.style.display = 'none';
            resultsBox.innerHTML = '';
            return;
        }

        timer = setTimeout(function () {
            var url = RELATED_SEARCH_API + '?type=' + encodeURIComponent(type) + '&q=' + encodeURIComponent(q);
            var xhr = new XMLHttpRequest();
            xhr.open('GET', url, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.onreadystatechange = function () {
                if (xhr.readyState !== 4) return;
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        var data = JSON.parse(cleanJSON(xhr.responseText));
                        if (data.success && data.data && data.data.length) {
                            var html = '';
                            data.data.forEach(function (r) {
                                var label = r.case_number ? r.case_number + ' — ' + (r.title || '') : (r.title || '');
                                html += '<div class="ec-search-item" data-rid="' + (r.record_id || 0) + '" data-label="' + esc(label) + '">' +
                                    '<strong>' + esc(label) + '</strong>' +
                                    '<span>' + esc(r.source || '') + ' · ' + esc(r.record_type || '') + '</span>' +
                                    '</div>';
                            });
                            resultsBox.innerHTML = html;
                            qsa('.ec-search-item', resultsBox).forEach(function (item) {
                                item.addEventListener('click', function () {
                                    var rid = item.getAttribute('data-rid');
                                    var label = item.getAttribute('data-label');
                                    hiddenId.value = rid;
                                    searchInput.value = label;
                                    resultsBox.style.display = 'none';
                                    resultsBox.innerHTML = '';
                                });
                            });
                        } else {
                            resultsBox.innerHTML = '<div class="ec-search-item">No records found.</div>';
                        }
                        resultsBox.style.display = 'block';
                    } catch (e) {
                        resultsBox.innerHTML = '<div class="ec-search-item">Invalid response.</div>';
                        resultsBox.style.display = 'block';
                    }
                } else {
                    resultsBox.innerHTML = '<div class="ec-search-item">Request failed.</div>';
                    resultsBox.style.display = 'block';
                }
            };
            xhr.send();
        }, 300);
    });
}
