<div class="module-content">
    <div class="toolbar" style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
        <a href="?page=instructor/training" style="display:inline-flex; align-items:center; gap:0.4rem; padding:0.5rem 1rem; background:var(--primary); color:#fff; border:none; border-radius:8px; text-decoration:none; font-size:0.85rem; font-weight:600; white-space:nowrap;"><i class="fas fa-arrow-left"></i> Back to Trainings</a>
        <div class="toolbar-search" style="flex:1;">
            <input type="search" placeholder="Search learning path form..." aria-label="Search learning path form" />
        </div>
    </div>

    <div class="mode-card">
        <h2>Add Learning Path</h2>
        <p>Learning paths group related course content into an ordered sequence that can be assigned to learners.</p>

        <form id="add-learning-path-form" method="post" action="pages/instructor/ajax/add-learning-path.php">
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1rem; margin-top:1rem;">
                <label>
                    <span>Learning path title</span>
                    <input type="text" name="title" required placeholder="e.g. Data Analyst Bootcamp" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" />
                </label>
                <label>
                    <span>Assign to learner</span>
                    <select name="assigned_to" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);">
                        <option value="">No specific learner</option>
                        <option value="1">Learner 1</option>
                        <option value="2">Learner 2</option>
                    </select>
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
                <textarea name="description" rows="6" placeholder="Describe the learning path goals, sequence, and expected outcomes..." style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border); resize:vertical;"></textarea>
            </label>

            <div style="display:block; margin-top:1rem;">
                <span style="font-weight:600; display:block; margin-bottom:0.35rem;">Skills</span>
                <div style="display:flex; flex-wrap:wrap; gap:0.4rem; padding:0.5rem; border:1px solid var(--border); border-radius:10px; min-height:42px; background:#fff;">
                    <?php
                    try {
                        require_once dirname(__DIR__, 4) . '/database/db.php';
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
                <button type="submit" class="mode-button">Save Learning Path</button>
                
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const form = document.getElementById('add-learning-path-form');
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
                throw new Error(data.message || 'Unable to save learning path.');
            }

            // Show notification instead of alert
            const notification = document.createElement('div');
            notification.textContent = 'Saved successfully: ' + (data.message || 'Learning path created successfully.');
            notification.style.cssText = 'position:fixed;top:20px;right:20px;padding:1rem 1.5rem;border-radius:8px;background:#10b981;color:#fff;z-index:10000;box-shadow:0 4px 12px rgba(0,0,0,0.15);';
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 4000);

            form.reset();
        })
        .catch(function (error) {
            // Show notification instead of alert
            const notification = document.createElement('div');
            notification.textContent = 'Save failed: ' + (error.message || 'Unable to save learning path.');
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
    function showNotification(message, type = 'info', duration = 4000) {
        const notification = document.createElement('div');
        notification.style.cssText = `position:fixed;top:20px;right:20px;padding:1rem 1.5rem;border-radius:8px;font-weight:500;z-index:10000;color:#fff;box-shadow:0 4px 12px rgba(0,0,0,0.15);`;
        notification.textContent = message;
        if (type === 'success') notification.style.background = '#10b981';
        else if (type === 'error') notification.style.background = '#ef4444';
        else if (type === 'warning') notification.style.background = '#f59e0b';
        else notification.style.background = '#3b82f6';
        document.body.appendChild(notification);
        if (duration > 0) setTimeout(() => notification.remove(), duration);
    }

    const params = new URLSearchParams(window.location.search);
    const pathId = params.get('id');
    
    if (pathId) {
        fetch('pages/instructor/ajax/get-learning-path-by-id.php?id=' + pathId, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success || !data.data) return;
            
            const path = data.data;
            const form = document.getElementById('add-learning-path-form');
            
            form.parentElement.querySelector('h2').textContent = 'Edit Learning Path';
            form.querySelector('input[name="title"]').value = path.title || '';
            form.querySelector('select[name="status"]').value = path.status || 'active';
            form.querySelector('textarea[name="description"]').value = path.description || '';
            
            if (!form.querySelector('input[name="id"]')) {
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = pathId;
                form.appendChild(idInput);
            }
            
            form.action = 'pages/instructor/ajax/edit-learning-path.php';
            form.querySelector('button[type="submit"]').textContent = 'Update Learning Path';
        })
        .catch(e => console.error('Error loading learning path:', e));
    }

    // Handle form submission for both add and edit
    const addLearningPathForm = document.getElementById('add-learning-path-form');
    if (addLearningPathForm) {
        addLearningPathForm.addEventListener('submit', function(e) {
            if (this.action.includes('edit-learning-path')) {
                e.preventDefault();
                
                const pathId = this.querySelector('input[name="id"]')?.value;
                if (!pathId) {
                    showNotification('Learning Path ID is missing', 'error');
                    return;
                }

                const formData = new FormData(this);
                const data = {
                    id: parseInt(pathId),
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
                        showNotification('Learning Path updated successfully', 'success');
                        setTimeout(() => {
                            window.location.href = '?page=instructor/training';
                        }, 1500);
                    } else {
                        showNotification('Error updating learning path: ' + (result.error || 'Unknown error'), 'error');
                    }
                })
                .catch(e => {
                    showNotification('Error: ' + e.message, 'error');
                    console.error('Update error:', e);
                });
            }
        });
    }
})();
</script>
