<?php
include_once __DIR__ . '/../../classes/Employee.php';
require_once dirname(__DIR__, 4) . '/database/db.php';

$employeeClass = new Employee();
$learnerId = (int) ($employeeClass->getEmployeeId() ?? 0);

$notes = [];

try {
    $pdo = (new Database())->getConnection();

    // Get all notes with lesson/course context
    $stmt = $pdo->prepare("
        SELECT n.id, n.item_type, n.reference_id, n.note, n.created_at, n.updated_at,
               l.title AS lesson_title, m.title AS module_title, c.title AS course_title, c.id AS course_id
        FROM ld_note n
        LEFT JOIN ld_lesson l ON l.id = n.reference_id AND n.item_type = 'lesson'
        LEFT JOIN ld_module m ON m.id = l.module_id
        LEFT JOIN ld_course c ON c.id = m.course_id
        WHERE n.learner_id = :learner_id
        ORDER BY n.updated_at DESC
    ");
    $stmt->execute([':learner_id' => $learnerId]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $notes = [];
}

function notesTimeAgo($dt) {
    if (!$dt) return '';
    $d = time() - strtotime($dt);
    if ($d < 60) return 'just now';
    if ($d < 3600) return floor($d / 60) . 'm ago';
    if ($d < 86400) return floor($d / 3600) . 'h ago';
    if ($d < 604800) return floor($d / 86400) . 'd ago';
    return date('M j, Y', strtotime($dt));
}
?>
<div class="module-content">
    <div class="toolbar">
        <div class="toolbar-search">
            <input type="search" id="notes-search" placeholder="Search notes..." aria-label="Search notes" />
        </div>
        <div class="toolbar-actions">
            <span style="color:#666; font-size:0.9rem;"><?= count($notes) ?> note<?= count($notes) !== 1 ? 's' : '' ?></span>
            
        </div>
    </div>

    <div class="mode-card">
        <h2><i class="fas fa-sticky-note" style="color:var(--primary); margin-right:0.5rem;"></i> My Notes</h2>
        <p>All your private notes across courses and lessons. Click any note to edit it.</p>

        <?php if (empty($notes)): ?>
            <div style="padding:3rem; text-align:center; background:#f9f9f9; border-radius:12px; margin-top:1rem;">
                <i class="fas fa-sticky-note" style="font-size:3rem; color:#ddd; margin-bottom:1rem; display:block;"></i>
                <h3>No notes yet</h3>
                <p style="color:#999;">Open any lesson in your study courses to start taking notes.</p>
                <a href="?page=learner/study" style="display:inline-block; margin-top:1rem; padding:0.75rem 1.5rem; background:var(--primary); color:#fff; border-radius:8px; text-decoration:none; font-weight:600;"><i class="fas fa-book-open"></i> Go to My Study</a>
            </div>
        <?php else: ?>
            <div id="notes-list" style="display:grid; gap:0.75rem; margin-top:1rem;">
                <?php foreach ($notes as $note): ?>
                    <div class="note-row" data-search="<?= htmlspecialchars(strtolower($note['note'] . ' ' . ($note['lesson_title'] ?? '') . ' ' . ($note['course_title'] ?? ''))) ?>"
                         style="padding:1.25rem; background:#fff9c4; border-radius:10px; border-left:4px solid #ffc107; cursor:pointer; transition:transform 0.2s;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:1rem;">
                            <div style="flex:1; min-width:0;">
                                <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.5rem; flex-wrap:wrap;">
                                    <?php if (!empty($note['course_title'])): ?>
                                        <span class="pill" style="background:rgba(32,0,130,0.1); color:var(--primary); font-size:0.8rem;"><?= htmlspecialchars($note['course_title']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($note['module_title'])): ?>
                                        <span class="pill" style="background:rgba(0,0,0,0.05); color:#666; font-size:0.8rem;"><?= htmlspecialchars($note['module_title']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($note['lesson_title'])): ?>
                                        <span class="pill" style="background:rgba(255,152,0,0.1); color:#ff9800; font-size:0.8rem;"><?= htmlspecialchars($note['lesson_title']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <p id="note-text-<?= $note['id'] ?>" style="margin:0; color:#333; line-height:1.6; white-space:pre-wrap; word-break:break-word;"><?= htmlspecialchars($note['note']) ?></p>
                            </div>
                            <div style="text-align:right; flex-shrink:0;">
                                <span style="font-size:0.8rem; color:#999;"><?= notesTimeAgo($note['updated_at']) ?></span>
                                <div style="margin-top:0.5rem; display:flex; gap:0.5rem;">
                                    <button type="button" class="edit-note-btn" data-note-id="<?= $note['id'] ?>" data-note-text="<?= htmlspecialchars($note['note']) ?>" style="padding:0.3rem 0.6rem; background:rgba(32,0,130,0.1); color:var(--primary); border:none; border-radius:4px; cursor:pointer; font-size:0.8rem;"><i class="fas fa-edit"></i></button>
                                    <button type="button" class="delete-note-btn" data-note-id="<?= $note['id'] ?>" style="padding:0.3rem 0.6rem; background:rgba(220,53,69,0.1); color:#dc3545; border:none; border-radius:4px; cursor:pointer; font-size:0.8rem;"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                        </div>
                        <!-- Edit form (hidden by default) -->
                        <div id="note-edit-<?= $note['id'] ?>" style="display:none; margin-top:1rem;">
                            <textarea id="note-edit-input-<?= $note['id'] ?>" style="width:100%; min-height:80px; padding:0.75rem; border:1px solid #ddd; border-radius:8px; font-family:inherit; resize:vertical; box-sizing:border-box;"><?= htmlspecialchars($note['note']) ?></textarea>
                            <div style="margin-top:0.5rem; display:flex; gap:0.5rem;">
                                <button type="button" class="save-note-btn" data-note-id="<?= $note['id'] ?>" style="padding:0.4rem 1rem; background:var(--primary); color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:500; font-size:0.85rem;">Save</button>
                                <button type="button" class="cancel-edit-btn" data-note-id="<?= $note['id'] ?>" style="padding:0.4rem 1rem; background:#ccc; color:#333; border:none; border-radius:6px; cursor:pointer; font-size:0.85rem;">Cancel</button>
                            </div>
                        </div>
                    </div>                    <?php endforeach; ?>
            </div>
            <div class="pagination-row" id="notes-pagination">
                <button type="button" class="page-btn" data-action="prev" disabled>Prev</button>
                <span class="page-indicator">Page 1 of 1</span>
                <button type="button" class="page-btn" data-action="next">Next</button>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function() {
    // Search
    document.getElementById('notes-search').addEventListener('input', function() {
        var q = this.value.toLowerCase();
        document.querySelectorAll('.note-row').forEach(function(row) {
            row.style.display = row.dataset.search.indexOf(q) !== -1 ? '' : 'none';
        });
    });

    // Edit
    document.querySelectorAll('.edit-note-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            var noteId = this.dataset.noteId;
            document.getElementById('note-text-' + noteId).style.display = 'none';
            document.getElementById('note-edit-' + noteId).style.display = 'block';
        });
    });

    // Cancel edit
    document.querySelectorAll('.cancel-edit-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            var noteId = this.dataset.noteId;
            document.getElementById('note-text-' + noteId).style.display = 'block';
            document.getElementById('note-edit-' + noteId).style.display = 'none';
        });
    });

    // Save note
    document.querySelectorAll('.save-note-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            var noteId = this.dataset.noteId;
            var textarea = document.getElementById('note-edit-input-' + noteId);
            var noteText = textarea.value.trim();
            if (!noteText) return;

            fetch('pages/learner/ajax/edit-note.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'note_id=' + noteId + '&note=' + encodeURIComponent(noteText)
            }).then(function(r) { return r.json(); }).then(function(data) {
                if (data.success) {
                    document.getElementById('note-text-' + noteId).textContent = noteText;
                    document.getElementById('note-text-' + noteId).style.display = 'block';
                    document.getElementById('note-edit-' + noteId).style.display = 'none';
                    if (window.showToast) window.showToast('Note updated', 'success');
                } else {
                    if (window.showToast) window.showToast('Failed to update note', 'error');
                }
            });
        });
    });

    // Delete
    document.querySelectorAll('.delete-note-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (!confirm('Delete this note?')) return;
            var noteId = this.dataset.noteId;
            fetch('pages/learner/ajax/edit-note.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'note_id=' + noteId + '&action=delete'
            }).then(function(r) { return r.json(); }).then(function(data) {
                if (data.success) {
                    btn.closest('.note-row').remove();
                    if (window.showToast) window.showToast('Note deleted', 'success');
                } else {
                    if (window.showToast) window.showToast('Failed to delete note', 'error');
                }
            });
        });
    });
})();
</script>
