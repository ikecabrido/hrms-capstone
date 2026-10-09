<div class="module-content">
    <div style="margin-bottom:1rem;">
        <a href="?page=instructor/training" style="display:inline-flex; align-items:center; gap:0.4rem; padding:0.5rem 1rem; background:var(--primary); color:#fff; border:none; border-radius:8px; text-decoration:none; font-size:0.85rem; font-weight:600; white-space:nowrap;"><i class="fas fa-arrow-left"></i> Back to Trainings</a>
    </div>

    <div class="mode-card">
        <h2>Add Program</h2>
        <p>Programs are managed training bundles and may include multiple sessions or course-related activities.</p>

        <form id="add-program-form" method="post" action="pages/instructor/training-subpage/ajax/add-program.php">
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1rem; margin-top:1rem;">
                <label>
                    <span>Program title</span>
                    <input type="text" name="title" required placeholder="e.g. Leadership Development Program" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" />
                </label>
                <label>
                    <span>Status</span>
                    <select name="status" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);">
                        <option value="active" selected>Active</option>
                        <option value="archived">Archived</option>
                    </select>
                </label>
            </div>

            <label style="display:block; margin-top:1rem;">
                <span>Description</span>
                <textarea name="description" rows="6" placeholder="Describe the program, goals, and audience..." style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border); resize:vertical;"></textarea>
            </label>

            <div style="display:block; margin-top:1rem;">
                <span style="font-weight:600; display:block; margin-bottom:0.35rem;">Skills</span>
                <div style="display:flex; flex-wrap:wrap; gap:0.4rem; padding:0.5rem; border:1px solid var(--border); border-radius:10px; min-height:42px; background:var(--surface, #fff);">
                    <?php
                    try {
                        require_once dirname(__DIR__, 5) . '/database/db.php';
                        $pdo = (new Database())->getConnection();
                        $allSkills = $pdo->query('SELECT id, name FROM ld_skill WHERE status = "active" ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
                    } catch (Throwable $e) { DbError::capture($e, 'instructor/training-subpage/program'); $allSkills = []; }
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
                <button type="submit" class="mode-button">Save Program</button>
                
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    const params = new URLSearchParams(window.location.search);
    const programId = params.get('id');
    
    if (programId) {
        fetch('pages/instructor/training-subpage/ajax/get-program-by-id.php?id=' + programId, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success || !data.data) return;
            
            const program = data.data;
            const form = document.getElementById('add-program-form');
            
            form.parentElement.querySelector('h2').textContent = 'Edit Program';
            form.querySelector('input[name="title"]').value = program.title || '';
            form.querySelector('select[name="status"]').value = program.status || 'active';
            form.querySelector('textarea[name="description"]').value = program.description || '';
            
            if (!form.querySelector('input[name="id"]')) {
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = programId;
                form.appendChild(idInput);
            }
            
            form.action = 'pages/instructor/training-subpage/ajax/edit-program.php';
            form.querySelector('button[type="submit"]').textContent = 'Update Program';
        })
        .catch(e => console.error('Error loading program:', e));
    }

    // Single submit handler for both add and edit
    const addProgramForm = document.getElementById('add-program-form');
    if (addProgramForm) {
        addProgramForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton ? submitButton.textContent : '';
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Saving...';
            }

            const isEdit = this.action.includes('edit-program');
            const programId = this.querySelector('input[name="id"]')?.value;

            if (isEdit && !programId) {
                if (window.showToast) window.showToast('Program ID is missing', 'error');
                if (submitButton) { submitButton.disabled = false; submitButton.textContent = originalText; }
                return;
            }

            const formData = new FormData(this);
            const payload = isEdit ? {
                id: parseInt(programId),
                title: formData.get('title'),
                status: formData.get('status'),
                description: formData.get('description')
            } : formData;

            const fetchOpts = {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            };

            if (isEdit) {
                fetchOpts.headers['Content-Type'] = 'application/json';
                fetchOpts.body = JSON.stringify(payload);
            } else {
                fetchOpts.body = payload;
            }

            fetch(this.action, fetchOpts)
            .then(r => r.json())
            .then(result => {
                if (result.success) {
                    if (window.showToast) {
                        window.showToast(isEdit ? 'Program updated successfully' : (result.message || 'Program created successfully.'), 'success');
                    }
                    setTimeout(() => {
                        if (isEdit) {
                            window.location.href = '?page=instructor/training';
                        } else {
                            window.location.reload();
                        }
                    }, 1000);
                } else {
                    if (window.showToast) window.showToast(result.message || result.error || 'Unable to save program.', 'error');
                }
            })
            .catch(e => {
                if (window.showToast) window.showToast(e.message || 'Network error while saving program.', 'error');
                console.error('Submit error:', e);
            })
            .finally(() => {
                if (submitButton) { submitButton.disabled = false; submitButton.textContent = originalText; }
            });
        });
    }
})();
</script>
