<?php /* Shared behaviour for every role profile page. */ ?>
<script>
(function() {
    'use strict';

    var csrfToken = window.CSRF_TOKEN || (document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '');
    var endpoint = 'pages/shared/ajax/update-profile.php';

    function notify(message, type) {
        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
        }
    }

    function authHeaders() {
        return { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': csrfToken };
    }

    function postForm(formData) {
        return fetch(endpoint, { method: 'POST', credentials: 'same-origin', headers: authHeaders(), body: formData })
            .then(function(r) { return r.json().then(function(d) { return { ok: r.ok, data: d }; }); });
    }

    // ─── Profile completion meter ─────────────────────────────────────────────
    function recomputeCompletion() {
        var list = document.getElementById('profile-completion-list');
        if (!list) return;

        var items = list.querySelectorAll('[data-criterion]');
        var done = 0;
        items.forEach(function(el) {
            var isDone = el.getAttribute('data-done') === '1';
            el.classList.toggle('is-done', isDone);
            var icon = el.querySelector('i');
            if (icon) icon.className = 'fas ' + (isDone ? 'fa-check-circle' : 'fa-circle');
            if (isDone) done++;
        });

        var pct = items.length ? Math.round((done / items.length) * 100) : 0;
        var bar = document.getElementById('profile-completion-bar');
        var label = document.getElementById('profile-completion-pct');
        if (bar) bar.style.width = pct + '%';
        if (label) label.textContent = pct + '%';

        var track = document.querySelector('.pfl-completion-bar');
        if (track) track.setAttribute('aria-valuenow', String(pct));
    }

    function setCriterion(key, isDone) {
        var el = document.getElementById('pfl-crit-' + key);
        if (!el) return;
        el.setAttribute('data-done', isDone ? '1' : '0');
        recomputeCompletion();
    }

    // ─── Avatar helpers ───────────────────────────────────────────────────────
    var avatar = document.getElementById('profile-avatar');

    function syncSidebarAvatar(url) {
        var sidebarAvatar = document.getElementById('sidebar-avatar');
        if (!sidebarAvatar) return;
        if (url) {
            sidebarAvatar.innerHTML = '';
            var img = document.createElement('img');
            img.src = url;
            img.alt = '';
            sidebarAvatar.appendChild(img);
        } else {
            sidebarAvatar.textContent = sidebarAvatar.getAttribute('data-initial') || '';
        }
    }

    function setAvatarImage(url) {
        if (!avatar) return;
        avatar.innerHTML = '';
        var img = document.createElement('img');
        img.id = 'profile-avatar-img';
        img.src = url;
        img.alt = 'Profile picture';
        avatar.appendChild(img);
        syncSidebarAvatar(url);
    }

    function setAvatarInitials() {
        if (!avatar) return;
        avatar.innerHTML = '';
        var span = document.createElement('span');
        span.id = 'profile-avatar-initials';
        span.textContent = avatar.getAttribute('data-initials') || '';
        avatar.appendChild(span);
        syncSidebarAvatar(null);
    }

    // ─── Profile photo ────────────────────────────────────────────────────────
    var photoBtn = document.getElementById('profile-photo-btn');
    var photoInput = document.getElementById('profile-photo-input');
    var photoRemoveBtn = document.getElementById('profile-photo-remove');
    var photoActions = document.getElementById('profile-photo-actions');

    if (photoBtn && photoInput) {
        photoBtn.addEventListener('click', function() { photoInput.click(); });

        photoInput.addEventListener('change', function() {
            var file = photoInput.files && photoInput.files[0];
            if (!file) return;

            if (file.size > 2 * 1024 * 1024) {
                notify('Picture must be 2 MB or smaller.', 'error');
                photoInput.value = '';
                return;
            }

            var formData = new FormData();
            formData.append('action', 'upload_photo');
            formData.append('profile_photo', file);

            postForm(formData)
                .then(function(res) {
                    photoInput.value = '';
                    if (!res.ok || !res.data.success) {
                        notify(res.data.message || 'Upload failed.', 'error');
                        return;
                    }
                    setAvatarImage(res.data.photo_url + '?t=' + Date.now());
                    if (photoActions) photoActions.style.display = 'inline-flex';
                    setCriterion('photo', true);
                    notify('Profile picture updated.', 'success');
                })
                .catch(function() {
                    photoInput.value = '';
                    notify('Network error. Please try again.', 'error');
                });
        });
    }

    if (photoRemoveBtn) {
        photoRemoveBtn.addEventListener('click', function() {
            var formData = new FormData();
            formData.append('action', 'remove_photo');

            postForm(formData)
                .then(function(res) {
                    if (!res.ok || !res.data.success) {
                        notify(res.data.message || 'Could not remove picture.', 'error');
                        return;
                    }
                    setAvatarInitials();
                    if (photoActions) photoActions.style.display = 'none';
                    setCriterion('photo', false);
                    notify('Profile picture removed.', 'success');
                })
                .catch(function() { notify('Network error. Please try again.', 'error'); });
        });
    }

    // ─── Bio ──────────────────────────────────────────────────────────────────
    var bioEditBtn = document.getElementById('profile-bio-edit-btn');
    var bioView = document.getElementById('profile-bio-view');
    var bioEdit = document.getElementById('profile-bio-edit');
    var bioTextarea = document.getElementById('profile-bio-textarea');
    var bioCount = document.getElementById('profile-bio-count');
    var bioSave = document.getElementById('profile-bio-save');
    var bioCancel = document.getElementById('profile-bio-cancel');

    function updateBioCount() {
        if (bioCount && bioTextarea) bioCount.textContent = bioTextarea.value.length + ' / 1000';
    }

    if (bioEditBtn && bioView && bioEdit && bioTextarea) {
        bioEditBtn.addEventListener('click', function() {
            bioTextarea.value = bioEditBtn.getAttribute('data-bio') || '';
            updateBioCount();
            bioView.style.display = 'none';
            bioEdit.style.display = 'block';
            bioTextarea.focus();
        });

        if (bioCancel) {
            bioCancel.addEventListener('click', function() {
                bioEdit.style.display = 'none';
                bioView.style.display = 'block';
            });
        }

        bioTextarea.addEventListener('input', updateBioCount);

        if (bioSave) {
            bioSave.addEventListener('click', function() {
                bioSave.disabled = true;
                bioSave.textContent = 'Saving...';

                var formData = new FormData();
                formData.append('action', 'save_bio');
                formData.append('bio', bioTextarea.value);

                postForm(formData)
                    .then(function(res) {
                        bioSave.disabled = false;
                        bioSave.textContent = 'Save';
                        if (!res.ok || !res.data.success) {
                            notify(res.data.message || 'Could not save bio.', 'error');
                            return;
                        }
                        var saved = res.data.bio || '';
                        bioEditBtn.setAttribute('data-bio', saved);
                        bioView.innerHTML = '';
                        var p = document.createElement('p');
                        if (saved === '') {
                            p.className = 'pfl-bio-empty';
                            p.textContent = 'No bio yet. Tell others a little about yourself.';
                            bioEditBtn.innerHTML = '<i class="fas fa-pen" aria-hidden="true"></i> Add bio';
                        } else {
                            p.className = 'pfl-bio-text';
                            p.textContent = saved;
                            bioEditBtn.innerHTML = '<i class="fas fa-pen" aria-hidden="true"></i> Edit';
                        }
                        bioView.appendChild(p);
                        bioEdit.style.display = 'none';
                        bioView.style.display = 'block';
                        setCriterion('bio', saved !== '');
                        notify('Bio updated.', 'success');
                    })
                    .catch(function() {
                        bioSave.disabled = false;
                        bioSave.textContent = 'Save';
                        notify('Network error. Please try again.', 'error');
                    });
            });
        }
    }

    // ─── Contact details ──────────────────────────────────────────────────────
    var contactForm = document.getElementById('profile-contact-form');
    var contactSave = document.getElementById('profile-contact-save');

    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();

            var mobileEl = document.getElementById('profile-contact-mobile');
            var phoneEl = document.getElementById('profile-contact-phone');
            var addressEl = document.getElementById('profile-contact-address');
            var mobile = mobileEl ? mobileEl.value.trim() : '';
            var phone = phoneEl ? phoneEl.value.trim() : '';
            var address = addressEl ? addressEl.value.trim() : '';

            var phonePattern = /^[0-9+()\- ]*$/;
            if (!phonePattern.test(mobile) || !phonePattern.test(phone)) {
                notify('Phone numbers may only contain digits, spaces and + ( ) -.', 'error');
                return;
            }

            if (contactSave) {
                contactSave.disabled = true;
                contactSave.innerHTML = 'Saving...';
            }

            var formData = new FormData();
            formData.append('action', 'save_contact');
            formData.append('mobile_no', mobile);
            formData.append('phone_no', phone);
            formData.append('current_address', address);

            postForm(formData)
                .then(function(res) {
                    if (contactSave) {
                        contactSave.disabled = false;
                        contactSave.innerHTML = '<i class="fas fa-floppy-disk" aria-hidden="true"></i> Save contact details';
                    }
                    if (!res.ok || !res.data.success) {
                        notify(res.data.message || 'Could not save contact details.', 'error');
                        return;
                    }
                    setCriterion('mobile', mobile !== '');
                    setCriterion('address', address !== '');
                    notify('Contact details updated.', 'success');
                })
                .catch(function() {
                    if (contactSave) {
                        contactSave.disabled = false;
                        contactSave.innerHTML = '<i class="fas fa-floppy-disk" aria-hidden="true"></i> Save contact details';
                    }
                    notify('Network error. Please try again.', 'error');
                });
        });
    }

    // ─── Sign out other sessions ──────────────────────────────────────────────
    var signOutOthers = document.getElementById('profile-signout-others');
    var sessionList = document.getElementById('profile-session-list');

    if (signOutOthers) {
        signOutOthers.addEventListener('click', function() {
            if (signOutOthers.disabled) return;

            var formData = new FormData();
            formData.append('action', 'revoke_other_sessions');

            postForm(formData)
                .then(function(res) {
                    if (!res.ok || !res.data.success) {
                        notify(res.data.message || 'Could not sign out other sessions.', 'error');
                        return;
                    }
                    if (sessionList) {
                        sessionList.querySelectorAll('.pfl-session:not([data-current])').forEach(function(el) { el.remove(); });
                        sessionList.setAttribute('data-other-count', '0');
                        if (!sessionList.querySelector('.pfl-session')) {
                            var empty = document.createElement('p');
                            empty.className = 'pfl-empty';
                            empty.textContent = 'No other active sessions.';
                            sessionList.appendChild(empty);
                        }
                    }
                    signOutOthers.disabled = true;
                    var count = res.data.revoked || 0;
                    notify(count > 0 ? 'Signed out ' + count + ' other session' + (count !== 1 ? 's' : '') + '.' : 'No other sessions to sign out.', 'success');
                })
                .catch(function() { notify('Network error. Please try again.', 'error'); });
        });
    }
})();
</script>
