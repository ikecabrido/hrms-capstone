<div class="module-content">
    <div style="margin-bottom:1rem;">
        <a href="?page=instructor/training" style="display:inline-flex; align-items:center; gap:0.4rem; padding:0.5rem 1rem; background:var(--primary); color:#fff; border:none; border-radius:8px; text-decoration:none; font-size:0.85rem; font-weight:600; white-space:nowrap;"><i class="fas fa-arrow-left"></i> Back to Trainings</a>
    </div>

    <div class="mode-card">
        <h2>Add Online Training</h2>
        <p>Schedule an online training session with a meeting link, platform, and date/time as described in the MD schema.</p>

        <form id="add-video-conference-form" data-skip method="post" action="pages/instructor/training-subpage/ajax/add-video-conference.php">
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1rem; margin-top:1rem;">
                <label>
                    <span>Training title</span>
                    <input type="text" name="title" required placeholder="e.g. Live Q&A Session" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" />
                </label>
                <label>
                    <span>Platform</span>
                    <select name="platform" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);">
                        <option value="google_meet" selected>Google Meet</option>
                        <option value="zoom">Zoom</option>
                        <option value="other">Other</option>
                    </select>
                </label>
                <label>
                    <span>Course</span>
                    <select name="course_id" id="video-course-id" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);">
                        <option value="">Select a course</option>
                    </select>
                </label>
                <label>
                    <span>Program</span>
                    <select name="program_id" id="video-program-id" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);">
                        <option value="">Select a program</option>
                    </select>
                </label>
                <label>
                    <span>Scheduled at</span>
                    <input type="datetime-local" name="scheduled_at" required style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" />
                </label>
                <label>
                    <span>Duration (minutes)</span>
                    <input type="number" name="duration_minutes" min="15" value="60" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" />
                </label>
                <label>
                    <span>Status</span>
                    <select name="status" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);">
                        <option value="scheduled" selected>Scheduled</option>
                        <option value="completed">Completed</option>
                        <option value="archived">Archived</option>
                    </select>
                </label>
            </div>

            <label style="display:block; margin-top:1rem;">
                <span>Meeting link</span>
                <input type="url" name="meeting_link" required placeholder="https://meet.google.com/..." style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" />
            </label>

            <div style="display:block; margin-top:1rem;">
                <span style="font-weight:600; display:block; margin-bottom:0.35rem;">Skills</span>
                <div style="display:flex; flex-wrap:wrap; gap:0.4rem; padding:0.5rem; border:1px solid var(--border); border-radius:10px; min-height:42px; background:var(--surface, #fff);">
                    <?php
                    try {
                        require_once dirname(__DIR__, 5) . '/database/db.php';
                        $pdo = (new Database())->getConnection();
                        $allSkills = $pdo->query('SELECT id, name FROM ld_skill WHERE status = "active" ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
                    } catch (Throwable $e) { DbError::capture($e, 'instructor/training-subpage/video-conference'); $allSkills = []; }
                    foreach ($allSkills as $sk):
                    ?>
                    <label style="display:inline-flex; align-items:center; gap:0.3rem; padding:0.3rem 0.6rem; border:1px solid rgba(32,0,130,0.15); border-radius:999px; cursor:pointer; font-size:0.78rem; font-weight:600; background:rgba(32,0,130,0.04); color:var(--text);">
                        <input type="checkbox" name="skill_ids[]" value="<?= (int)$sk['id'] ?>" style="accent-color:var(--primary);" />
                        <?= htmlspecialchars($sk['name']) ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mode-actions" style="margin-top:1.5rem;">
                <button type="submit" class="mode-button">Save Video Conference</button>
                
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const form = document.getElementById('add-video-conference-form');
    if (!form) return;

    const courseField = document.getElementById('video-course-id');
    const programField = document.getElementById('video-program-id');

    function bindLookup(select, sourceUrl, placeholder) {
        fetch(sourceUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (response) { return response.json(); })
            .then(function (result) {
                if (!result.success || !Array.isArray(result.items)) return;

                select.innerHTML = '';
                const placeholderOption = document.createElement('option');
                placeholderOption.value = '';
                placeholderOption.textContent = placeholder;
                select.appendChild(placeholderOption);
                result.items.forEach(function (item) {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = item.name;
                    select.appendChild(option);
                });
                if (select.dataset.selectedId) select.value = select.dataset.selectedId;
            })
            .catch(function () {
                // ignore fetch failure for now
            });

    }

    bindLookup(courseField, 'pages/instructor/elearning-subpage/ajax/get-course.php', 'Select a course');
    bindLookup(programField, 'pages/instructor/training-subpage/ajax/get-program.php', 'Select a program');
})();
</script>
<script>
(function () {
    const params = new URLSearchParams(window.location.search);
    const videoId = params.get('id');
    const form = document.getElementById('add-video-conference-form');
    if (!form) return;

    function toast(message, type) {
        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
            return;
        }

        let container = document.getElementById('video-conference-toast-region');
        if (!container) {
            container = document.createElement('div');
            container.id = 'video-conference-toast-region';
            container.setAttribute('aria-live', 'polite');
            container.style.cssText = 'position:fixed;bottom:2rem;left:50%;transform:translateX(-50%);z-index:100000;display:grid;gap:0.5rem;pointer-events:none;';
            document.body.appendChild(container);
        }

        const notice = document.createElement('div');
        notice.setAttribute('role', type === 'error' ? 'alert' : 'status');
        notice.textContent = message;
        notice.style.cssText = 'padding:0.75rem 1.25rem;border-radius:8px;background:' + (type === 'error' ? '#dc2626' : '#059669') + ';color:#fff;font-size:0.85rem;font-weight:700;box-shadow:0 6px 24px rgba(0,0,0,0.2);';
        container.appendChild(notice);
        setTimeout(function () { notice.remove(); }, 3500);
    }

    if (videoId) {
        fetch('pages/instructor/training-subpage/ajax/get-video-conference-by-id.php?id=' + encodeURIComponent(videoId), {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(result => {
            if (!result.success || !result.data) {
                toast(result.message || 'Unable to load online training for editing.', 'error');
                return;
            }

            const video = result.data;
            form.parentElement.querySelector('h2').textContent = 'Edit Online Training';
            form.querySelector('input[name="title"]').value = video.title || '';
            form.querySelector('select[name="platform"]').value = video.platform || 'google_meet';
            const scheduledAt = video.scheduled_at || '';
            form.querySelector('input[name="scheduled_at"]').value = scheduledAt ? scheduledAt.replace(' ', 'T').slice(0, 16) : '';
            form.querySelector('input[name="duration_minutes"]').value = video.duration_minutes || 60;
            form.querySelector('select[name="status"]').value = video.status || 'scheduled';
            form.querySelector('input[name="meeting_link"]').value = video.meeting_link || '';

            if (video.course_id) {
                const courseField = form.querySelector('#video-course-id');
                courseField.dataset.selectedId = video.course_id;
                courseField.value = video.course_id;
            }
            if (video.program_id) {
                const programField = form.querySelector('#video-program-id');
                programField.dataset.selectedId = video.program_id;
                programField.value = video.program_id;
            }

            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id';
            idInput.value = videoId;
            form.appendChild(idInput);
            form.action = 'pages/instructor/training-subpage/ajax/edit-video-conference.php';
            form.querySelector('button[type="submit"]').textContent = 'Update Online Training';
        })
        .catch(error => {
            toast(error.message || 'Unable to load online training for editing.', 'error');
            console.error('Error loading online training:', error);
        });
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton.textContent;
        submitButton.disabled = true;
        submitButton.textContent = 'Saving...';

        const isEdit = form.action.includes('edit-video-conference');
        const formData = new FormData(form);
        const options = {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        };

        if (isEdit) {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify({
                id: Number(formData.get('id')),
                title: formData.get('title'),
                platform: formData.get('platform'),
                status: formData.get('status'),
                meeting_link: formData.get('meeting_link'),
                scheduled_at: formData.get('scheduled_at'),
                duration_minutes: formData.get('duration_minutes'),
                course_id: formData.get('course_id'),
                program_id: formData.get('program_id')
            });
        } else {
            options.body = formData;
        }

        if (isEdit && !Number(formData.get('id'))) {
            toast('Online training ID is missing.', 'error');
            submitButton.disabled = false;
            submitButton.textContent = originalText;
            return;
        }

        fetch(form.action, options)
        .then(response => response.json())
        .then(result => {
            if (!result.success) {
                toast(result.message || result.error || 'Unable to save online training.', 'error');
                return;
            }

            toast(isEdit ? 'Online training updated successfully.' : 'Online training added successfully.', 'success');
            setTimeout(() => { window.location.href = '?page=instructor/training'; }, 1000);
        })
        .catch(error => {
            toast(error.message || 'Network error while saving online training.', 'error');
            console.error('Save error:', error);
        })
        .finally(() => {
            submitButton.disabled = false;
            submitButton.textContent = originalText;
        });
    });
})();
</script>
