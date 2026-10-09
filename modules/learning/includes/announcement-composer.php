<?php $announcementComposerRole = ($announcementComposerRole ?? '') === 'admin' ? 'admin' : 'instructor'; ?>
<dialog id="announcement-composer-modal" class="announcement-composer-overlay" aria-labelledby="announcement-composer-title">
    <section class="announcement-composer-dialog">
        <div class="announcement-composer-header" style="display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1rem;">
            <h2 id="announcement-composer-title" style="display:flex;align-items:center;gap:0.55rem;margin:0;font-size:1.15rem;color:var(--text);">
                <i class="fas fa-bullhorn" style="color:var(--primary);" aria-hidden="true"></i> Create announcement
            </h2>
            <button type="button" id="announcement-composer-close" aria-label="Close announcement dialog" title="Close" style="width:36px;height:36px;display:grid;place-items:center;border:1px solid var(--border);border-radius:50%;background:var(--surface,#fff);color:var(--text);cursor:pointer;">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>
        <form id="announcement-composer-form" data-skip style="display:grid;gap:0.85rem;">
        <label style="display:grid;gap:0.35rem;font-weight:600;">
            Announcement title
            <input type="text" name="title" maxlength="255" required style="width:100%;padding:0.65rem 0.75rem;border:1px solid var(--border);border-radius:6px;">
        </label>
        <label style="display:grid;gap:0.35rem;font-weight:600;">
            Message
            <textarea name="message" rows="4" required style="width:100%;padding:0.65rem 0.75rem;border:1px solid var(--border);border-radius:6px;resize:vertical;"></textarea>
        </label>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:0.85rem;">
            <fieldset style="display:grid;gap:0.55rem;margin:0;padding:0;border:0;">
                <legend style="margin-bottom:0.35rem;font-weight:600;">Audience</legend>
                <div class="announcement-audience-options">
                    <label class="announcement-audience-option"><input type="checkbox" name="audiences[]" value="all" checked><span>Everyone</span></label>
                    <label class="announcement-audience-option"><input type="checkbox" name="audiences[]" value="learner"><span>Learners</span></label>
                    <?php if ($announcementComposerRole === 'admin'): ?>
                        <label class="announcement-audience-option"><input type="checkbox" name="audiences[]" value="instructor"><span>Instructors</span></label>
                        <label class="announcement-audience-option"><input type="checkbox" name="audiences[]" value="admin"><span>Admins</span></label>
                    <?php endif; ?>
                </div>
            </fieldset>
            <label style="display:grid;gap:0.35rem;font-weight:600;">
                Expires (optional)
                <input type="datetime-local" name="expires_at" style="width:100%;padding:0.65rem 0.75rem;border:1px solid var(--border);border-radius:6px;">
            </label>
        </div>
        <div class="announcement-composer-actions">
            <button type="button" id="announcement-composer-cancel" class="mode-button">Cancel</button>
            <button type="submit" class="mode-button">
                <i class="fas fa-paper-plane" aria-hidden="true"></i> Announce
            </button>
        </div>
        <span id="announcement-composer-status" role="status" aria-live="polite" style="font-size:0.85rem;"></span>
        </form>
    </section>
</dialog>

<style>
    .announcement-audience-options {
        display:grid;
        grid-template-columns:repeat(2, minmax(0, 1fr));
        gap:0.55rem 1rem;
    }
    .announcement-audience-option {
        display:grid;
        grid-template-columns:1.1rem minmax(0, 1fr);
        align-items:center;
        gap:0.45rem;
        min-height:1.5rem;
        cursor:pointer;
    }
    .announcement-audience-option input { margin:0; }
    .announcement-composer-actions {
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:0.75rem;
    }
    .announcement-composer-actions button {
        width:auto;
        min-height:36px;
        justify-content:center;
        padding:0.4rem 0.8rem;
        font-size:0.85rem;
    }
    .announcement-composer-actions #announcement-composer-cancel {
        width:auto;
        height:36px;
        min-height:36px;
        align-self:center;
        padding:0.35rem 0.7rem;
        font-size:0.82rem;
        background:transparent;
        color:var(--text);
        border:1px solid var(--border);
    }
    .announcement-composer-overlay {
        position:fixed;
        inset:0;
        z-index:100000;
        box-sizing:border-box;
        width:min(600px, calc(100% - 2rem));
        max-width:none;
        max-height:calc(100vh - 2rem);
        margin:auto;
        padding:0;
        overflow:visible;
        border:0;
        background:transparent;
    }
    .announcement-composer-overlay::backdrop { background:rgba(15, 18, 35, 0.58); }
    .announcement-composer-dialog {
        box-sizing:border-box;
        width:100%;
        max-height:calc(100vh - 2rem);
        overflow:auto;
        padding:1.25rem;
        border:1px solid var(--border, #e5e7eb);
        border-radius:10px;
        background:var(--surface, #fff);
        box-shadow:0 24px 70px rgba(0,0,0,0.25);
    }
</style>

<script>
(function() {
    var form = document.getElementById('announcement-composer-form');
    var modal = document.getElementById('announcement-composer-modal');
    var openButton = document.getElementById('announcement-composer-open');
    var closeButton = document.getElementById('announcement-composer-close');
    var cancelButton = document.getElementById('announcement-composer-cancel');
    var audienceInputs = form ? form.querySelectorAll('[name="audiences[]"]') : [];
    if (!form || !modal || !openButton) return;

    var dialog = modal.querySelector('[role="dialog"]');
    var titleInput = form.querySelector('[name="title"]');
    var previousBodyOverflow = '';

    function openModal() {
        previousBodyOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        modal.showModal();
        if (titleInput) titleInput.focus();
    }

    function closeModal() {
        if (modal.open) modal.close();
    }

    function restoreFocus() {
        document.body.style.overflow = previousBodyOverflow;
        openButton.focus();
    }

    openButton.addEventListener('click', openModal);
    if (closeButton) closeButton.addEventListener('click', closeModal);
    if (cancelButton) cancelButton.addEventListener('click', closeModal);
    modal.addEventListener('cancel', function(event) {
        event.preventDefault();
        closeModal();
    });
    modal.addEventListener('close', restoreFocus);
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && modal.open) {
            event.preventDefault();
            event.stopPropagation();
            closeModal();
        }
    }, true);
    modal.addEventListener('click', function(event) {
        if (event.target === modal) closeModal();
    });

    audienceInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            var everyone = form.querySelector('[name="audiences[]"][value="all"]');
            if (input.value === 'all' && input.checked) {
                audienceInputs.forEach(function(option) { if (option !== input) option.checked = false; });
            } else if (input.value !== 'all' && input.checked && everyone) {
                everyone.checked = false;
            }
        });
    });

    form.addEventListener('submit', function(event) {
        event.preventDefault();
        var button = form.querySelector('button[type="submit"]');
        var status = document.getElementById('announcement-composer-status');
        if (!form.querySelector('[name="audiences[]"]:checked')) {
            status.textContent = 'Select at least one audience.';
            status.style.color = '#b91c1c';
            if (window.showToast) window.showToast(status.textContent, 'error');
            return;
        }
        button.disabled = true;
        status.textContent = 'Publishing...';

        fetch('pages/admin/ajax/add-announcement.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: new FormData(form)
        })
        .then(function(response) {
            return response.json().then(function(result) {
                if (!response.ok || !result.success) throw new Error(result.message || 'Unable to publish announcement.');
                return result;
            });
        })
        .then(function(result) {
            status.textContent = result.message || 'Announcement published.';
            status.style.color = '#047857';
            if (window.showToast) window.showToast(status.textContent, 'success');
            setTimeout(function() { window.location.reload(); }, 900);
        })
        .catch(function(error) {
            status.textContent = error.message || 'Unable to publish announcement.';
            status.style.color = '#b91c1c';
            if (window.showToast) window.showToast(status.textContent, 'error');
            button.disabled = false;
        });
    });
})();
</script>