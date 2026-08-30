<div class="module-content">
    <div class="toolbar" style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
        <a href="?page=instructor/training" style="display:inline-flex; align-items:center; gap:0.4rem; padding:0.5rem 1rem; background:var(--primary); color:#fff; border:none; border-radius:8px; text-decoration:none; font-size:0.85rem; font-weight:600; white-space:nowrap;"><i class="fas fa-arrow-left"></i> Back to Trainings</a>
        <div class="toolbar-search" style="flex:1;">
            <input type="search" placeholder="Search program form..." aria-label="Search program form" />
        </div>
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
                    } catch (Throwable $e) { $allSkills = []; }
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
(function () {
    const form = document.getElementById('add-program-form');
    if (!form) return;

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton ? submitButton.textContent : '';
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Saving...';
        }

        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(async function (response) {
            const data = await response.json().catch(function () {
                return { success: false, message: 'Request failed.' };
            });

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Unable to create program.');
            }

            // Show notification instead of alert
            const notification = document.createElement('div');
            notification.textContent = 'Saved successfully: ' + (data.message || 'Program created successfully.');
            notification.style.cssText = 'position:fixed;top:20px;right:20px;padding:1rem 1.5rem;border-radius:8px;background:#10b981;color:#fff;z-index:10000;box-shadow:0 4px 12px rgba(0,0,0,0.15);';
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 4000);
            form.reset();
        })
        .catch(function (error) {
            // Show notification instead of alert
            const notification = document.createElement('div');
            notification.textContent = 'Save failed: ' + (error.message || 'Unable to create program.');
            notification.style.cssText = 'position:fixed;top:20px;right:20px;padding:1rem 1.5rem;border-radius:8px;background:#ef4444;color:#fff;z-index:10000;box-shadow:0 4px 12px rgba(0,0,0,0.15);';
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 4000);
        })
        .finally(function () {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            }
        });
    });
})();
</script>
<script>
(function() {
    const params = new URLSearchParams(window.location.search);
    const programId = params.get('id');
    
    if (programId) {
        fetch('/itsar/modules/learning/pages/instructor/training-subpage/ajax/get-program-by-id.php?id=' + programId, {
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

    // Handle form submission for both add and edit
    const addProgramForm = document.getElementById('add-program-form');
    if (addProgramForm) {
        addProgramForm.addEventListener('submit', function(e) {
            if (this.action.includes('edit-program')) {
                e.preventDefault();
                
                const programId = this.querySelector('input[name="id"]')?.value;
                if (!programId) {
                    alert('Program ID is missing');
                    return;
                }

                const formData = new FormData(this);
                const data = {
                    id: parseInt(programId),
                    title: formData.get('title'),
                    status: formData.get('status'),
                    description: formData.get('description')
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
                        const notif = document.createElement('div');
                        notif.textContent = 'Program updated successfully';
                        notif.style.cssText = 'position:fixed;top:20px;right:20px;padding:1rem 1.5rem;border-radius:8px;background:#10b981;color:#fff;z-index:10000;box-shadow:0 4px 12px rgba(0,0,0,0.15);';
                        document.body.appendChild(notif);
                        setTimeout(() => {
                            window.location.href = '?page=instructor/training';
                        }, 1500);
                    } else {
                        const notif = document.createElement('div');
                        notif.textContent = 'Error updating program: ' + (result.error || 'Unknown error');
                        notif.style.cssText = 'position:fixed;top:20px;right:20px;padding:1rem 1.5rem;border-radius:8px;background:#ef4444;color:#fff;z-index:10000;box-shadow:0 4px 12px rgba(0,0,0,0.15);';
                        document.body.appendChild(notif);
                        setTimeout(() => notif.remove(), 4000);
                    }
                })
                .catch(e => {
                    const notif = document.createElement('div');
                    notif.textContent = 'Error: ' + e.message;
                    notif.style.cssText = 'position:fixed;top:20px;right:20px;padding:1rem 1.5rem;border-radius:8px;background:#ef4444;color:#fff;z-index:10000;box-shadow:0 4px 12px rgba(0,0,0,0.15);';
                    document.body.appendChild(notif);
                    setTimeout(() => notif.remove(), 4000);
                    console.error('Update error:', e);
                });
            }
        });
    }
})();
</script>
