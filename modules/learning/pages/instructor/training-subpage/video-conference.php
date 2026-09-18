<div class="module-content">
    <div class="toolbar" style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
        <a href="?page=instructor/training" style="display:inline-flex; align-items:center; gap:0.4rem; padding:0.5rem 1rem; background:var(--primary); color:#fff; border:none; border-radius:8px; text-decoration:none; font-size:0.85rem; font-weight:600; white-space:nowrap;"><i class="fas fa-arrow-left"></i> Back to Trainings</a>
        <div class="toolbar-search" style="flex:1;">
            <input type="search" placeholder="Search online training form..." aria-label="Search video conference form" />
        </div>
    </div>

    <div class="mode-card">
        <h2>Add Online Training</h2>
        <p>Schedule an online training session with a meeting link, platform, and date/time as described in the MD schema.</p>

        <form id="add-video-conference-form" method="post" action="pages/instructor/training-subpage/ajax/add-video-conference.php">
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
                    <input type="text" list="video-course-list" id="video-course-search" placeholder="Search course by name" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" />
                    <input type="hidden" name="course_id" id="video-course-id" />
                    <datalist id="video-course-list"></datalist>
                </label>
                <label>
                    <span>Program</span>
                    <input type="text" list="video-program-list" id="video-program-search" placeholder="Search program by name" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" />
                    <input type="hidden" name="program_id" id="video-program-id" />
                    <datalist id="video-program-list"></datalist>
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

    const courseSearch = document.getElementById('video-course-search');
    const courseIdField = document.getElementById('video-course-id');
    const courseList = document.getElementById('video-course-list');
    const programSearch = document.getElementById('video-program-search');
    const programIdField = document.getElementById('video-program-id');
    const programList = document.getElementById('video-program-list');

    function bindLookup(searchInput, hiddenInput, dataList, sourceUrl) {
        fetch(sourceUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (response) { return response.json(); })
            .then(function (result) {
                if (!result.success || !Array.isArray(result.items)) return;

                dataList.innerHTML = '';
                result.items.forEach(function (item) {
                    const option = document.createElement('option');
                    option.value = item.name;
                    option.dataset.id = item.id;
                    dataList.appendChild(option);
                });
            })
            .catch(function () {
                // ignore fetch failure for now
            });

        let selectedOption = null;
        searchInput.addEventListener('input', function () {
            selectedOption = Array.from(dataList.options).find(function (option) {
                return option.value === searchInput.value;
            });
            hiddenInput.value = selectedOption ? (selectedOption.dataset.id || '') : '';
        });
    }

    bindLookup(courseSearch, courseIdField, courseList, 'pages/instructor/elearning-subpage/ajax/get-course.php');
    bindLookup(programSearch, programIdField, programList, 'pages/instructor/training-subpage/ajax/get-program.php');
})();
</script>
<script>
(function() {
    const params = new URLSearchParams(window.location.search);
    const videoId = params.get('id');
    
    if (videoId) {
        fetch('pages/instructor/training-subpage/ajax/get-video-conference-by-id.php?id=' + videoId, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success || !data.data) return;
            
            const video = data.data;
            const form = document.getElementById('add-video-conference-form');
            
            form.parentElement.querySelector('h2').textContent = 'Edit Video Conference';
            form.querySelector('input[name="title"]').value = video.title || '';
            form.querySelector('select[name="platform"]').value = video.platform || 'google_meet';
            const rawScheduled = video.scheduled_at || '';
            form.querySelector('input[name="scheduled_at"]').value = rawScheduled ? rawScheduled.replace(' ', 'T').slice(0, 16) : '';
            form.querySelector('input[name="duration_minutes"]').value = video.duration_minutes || 60;
            form.querySelector('select[name="status"]').value = video.status || 'scheduled';
            const descField = form.querySelector('textarea[name="description"]');
            if (descField) descField.value = video.description || '';
            
            if (video.course_id) {
                form.querySelector('#video-course-id').value = video.course_id;
            }
            if (video.program_id) {
                form.querySelector('#video-program-id').value = video.program_id;
            }
            
            if (!form.querySelector('input[name="id"]')) {
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = videoId;
                form.appendChild(idInput);
            }
            
            form.action = 'pages/instructor/training-subpage/ajax/edit-video-conference.php';
            form.querySelector('button[type="submit"]').textContent = 'Update Video Conference';
        })
        .catch(e => console.error('Error loading video conference:', e));
    }

    // Handle form submission for both add and edit
    const addVideoForm = document.getElementById('add-video-conference-form');
    if (addVideoForm) {
        addVideoForm.addEventListener('submit', function(e) {
            if (this.action.includes('edit-video-conference')) {
                e.preventDefault();
                
                const videoId = this.querySelector('input[name="id"]')?.value;
                if (!videoId) {
                    alert('Video Conference ID is missing');
                    return;
                }

                const formData = new FormData(this);
                const data = {
                    id: parseInt(videoId),
                    title: formData.get('title'),
                    platform: formData.get('platform'),
                    status: formData.get('status'),
                    description: formData.get('description'),
                    scheduled_at: formData.get('scheduled_at')
                };

                fetch(this.action, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify(data)
                })
                .then(r => r.json())
                .then(result => {
                    if (result.success) {
                        alert('Video Conference updated successfully');
                        window.location.href = '?page=instructor/training';
                    } else {
                        alert('Error updating video conference: ' + (result.error || 'Unknown error'));
                    }
                })
                .catch(e => {
                    alert('Error: ' + e.message);
                    console.error('Update error:', e);
                });
            }
        });
    }
})();
</script>
