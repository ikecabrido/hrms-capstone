import { qs, esc } from './helpers.js';

export function renderNotes(notes) {
    var container = qs('#ecNotesList');
    if (!container) return;
    if (!notes.length) {
        container.innerHTML = '<div class="lc-empty">No notes yet.</div>';
        return;
    }
    var html = '';
    notes.slice(0, 4).forEach(function (n) {
        html += '<div class="ec-note-item">' +
            '<div class="ec-note-header">' +
            '<span class="ec-note-author">' + esc(n.created_by_name || 'System') + '</span>' +
            '<span class="ec-note-date">' + esc(n.created_at || '') + '</span>' +
            '</div>' +
            '<div class="ec-note-text">' + esc(n.note).replace(/\n/g, '<br>') + '</div>' +
            '</div>';
    });
    container.innerHTML = html;
}
