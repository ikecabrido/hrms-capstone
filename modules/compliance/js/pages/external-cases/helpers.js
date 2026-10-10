import { api, csrf } from './config.js';

export function qs(selector, el) {
    return (el || document).querySelector(selector);
}

export function qsa(selector, el) {
    return Array.prototype.slice.call((el || document).querySelectorAll(selector));
}

export function esc(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

export function postForm(form, onSuccess) {
    var fd = new FormData(form);
    fd.set('csrf_token', csrf);
    var xhr = new XMLHttpRequest();
    xhr.open('POST', api, true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onreadystatechange = function () {
        if (xhr.readyState !== 4) return;
        if (xhr.status >= 200 && xhr.status < 300) {
            try {
                var data = JSON.parse(cleanJSON(xhr.responseText));
                if (data.success) {
                    if (onSuccess) onSuccess(data);
                } else {
                    alert(data.message || 'Operation failed.');
                }
            } catch (e) {
                var raw = String(xhr.responseText || '');
                console.error('Invalid JSON response. Parse error:', e.message);
                console.error('Raw response length:', raw.length);
                console.error('Raw response prefix:', JSON.stringify(raw.slice(0, 200)));
                console.error('Raw response suffix:', JSON.stringify(raw.slice(-200)));
                alert('Invalid server response: ' + e.message + '. Check console for details.');
            }
        } else {
            alert(parseErr(xhr), 'Request Error');
        }
    };
    xhr.send(fd);
}

export function parseErr(xhr) {
    try {
        var err = JSON.parse(cleanJSON(xhr.responseText));
        if (err.message) return err.message;
    } catch (e) {}
    return 'Request failed with status ' + xhr.status;
}

export function cleanJSON(raw) {
    if (typeof raw !== 'string') return raw;
    return raw.replace(/^\uFEFF/, '').trim();
}

export function getJSON(params, onSuccess) {
    var qs = [];
    for (var k in params) {
        if (params.hasOwnProperty(k)) qs.push(encodeURIComponent(k) + '=' + encodeURIComponent(params[k]));
    }
    var xhr = new XMLHttpRequest();
    xhr.open('GET', api + '?' + qs.join('&'), true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onreadystatechange = function () {
        if (xhr.readyState !== 4) return;
        if (xhr.status >= 200 && xhr.status < 300) {
             try {
                 var data = JSON.parse(cleanJSON(xhr.responseText));
                 if (data.success) {
                     if (onSuccess) onSuccess(data);
                 } else {
                     alert((data.message || 'Request failed.'), 'Load Error');
                 }
             } catch (e) {
                 var raw = String(xhr.responseText || '');
                 console.error('Invalid JSON response. Parse error:', e.message);
                 console.error('Raw response length:', raw.length);
                 console.error('Raw response prefix:', JSON.stringify(raw.slice(0, 200)));
                 console.error('Raw response suffix:', JSON.stringify(raw.slice(-200)));
                 alert('Invalid server response: ' + e.message + '. Check console for details.', 'Load Error');
             }
        } else {
            var msg = 'Request failed with status ' + xhr.status;
            try { var err = JSON.parse(cleanJSON(xhr.responseText)); if (err.message) msg = err.message; } catch (e) {}
            alert(msg, 'Load Error');
        }
    };
    xhr.send();
}

export function statusClass(status) {
    if (!status) return '';
    var s = String(status).toLowerCase();
    if (s.indexOf('closed') !== -1 || s.indexOf('resolved') !== -1 || s.indexOf('withdrawn') !== -1 || s.indexOf('archived') !== -1) return 'lc-status-stamp--closed';
    if (s.indexOf('overdue') !== -1 || s.indexOf('awaiting') !== -1) return 'lc-status-stamp--overdue';
    if (s.indexOf('scheduled') !== -1 || s.indexOf('hearing') !== -1 || s.indexOf('conference') !== -1) return 'lc-status-stamp--investigating';
    return 'lc-status-stamp--info';
}

export function priorityClass(p) {
    if (!p) return '';
    var s = String(p).toLowerCase();
    if (s === 'critical') return 'lc-status-stamp--overdue';
    if (s === 'high') return 'lc-status-stamp--investigating';
    return 'lc-status-stamp--info';
}
