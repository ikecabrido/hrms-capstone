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

        <form id="add-learning-path-form" data-skip method="post" action="pages/instructor/ajax/add-learning-path.php">
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1rem; margin-top:1rem;">
                <label>
                    <span>Learning path title</span>
                    <input type="text" name="title" required placeholder="e.g. Data Analyst Bootcamp" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);" />
                </label>
                <label>
                    <span>Assign to learner</span>
                    <select name="assigned_to" id="assigned-to-select" style="width:100%; margin-top:0.35rem; padding:0.8rem; border-radius:10px; border:1px solid var(--border);">
                        <option value="">Loading learners...</option>
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
                    } catch (Throwable $e) { DbError::capture($e, 'instructor/learning-path'); $allSkills = []; }
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

    <!-- Path Items Management (shown when editing) -->
    <div id="lp-items-section" class="mode-card" style="display:none; margin-top:1.5rem;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem;">
            <div>
                <h3 style="margin:0; color:var(--text);"><i class="fas fa-list-ol" style="margin-right:0.4rem; color:var(--primary);"></i> Path Items</h3>
                <p style="margin:0.3rem 0 0; font-size:0.82rem; color:var(--muted);">Add courses and modules to this learning path.</p>
            </div>
            <button type="button" id="lp-add-item-btn" style="padding:0.45rem 0.9rem; background:var(--primary); color:#fff; border:none; border-radius:999px; font-size:0.8rem; font-weight:700; cursor:pointer;">
                <i class="fas fa-plus" style="margin-right:0.3rem;"></i>Add Item
            </button>
        </div>
        <div id="lp-items-list" style="display:grid; gap:0.5rem;"></div>
        <div id="lp-items-empty" style="text-align:center; padding:2rem; color:var(--muted); display:none;">
            <i class="fas fa-inbox" style="font-size:1.5rem; display:block; margin-bottom:0.5rem; opacity:0.4;"></i>
            <p style="margin:0; font-size:0.88rem;">No items yet. Click "Add Item" to get started.</p>
        </div>
    </div>

    <!-- Add Item Modal -->
    <div id="lp-item-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); backdrop-filter:blur(3px); z-index:10000; align-items:center; justify-content:center;">
        <div style="background:var(--surface); border-radius:18px; width:90%; max-width:480px; box-shadow:0 20px 60px rgba(0,0,0,0.25); padding:1.5rem;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem;">
                <h3 style="margin:0; font-size:1.1rem; font-weight:800; color:var(--text);">Add Item to Path</h3>
                <button id="lp-item-modal-close" style="background:none; border:none; font-size:1.2rem; cursor:pointer; color:var(--muted);"><i class="fas fa-times"></i></button>
            </div>
            <div style="display:grid; gap:1rem;">
                <div>
                    <label style="display:block; font-size:0.72rem; font-weight:700; color:var(--primary); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:0.35rem;">Item Type</label>
                    <select id="lp-item-type" style="width:100%; padding:0.6rem 0.75rem; border:1.5px solid rgba(32,0,130,0.1); border-radius:8px; font-size:0.88rem; outline:none; background:#fff;">
                        <option value="course">Course</option>
                        <option value="module">Module</option>
                    </select>
                </div>
                <div>
                    <label style="display:block; font-size:0.72rem; font-weight:700; color:var(--primary); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:0.35rem;">Select Item</label>
                    <select id="lp-item-select" style="width:100%; padding:0.6rem 0.75rem; border:1.5px solid rgba(32,0,130,0.1); border-radius:8px; font-size:0.88rem; outline:none; background:#fff;">
                        <option value="">Loading...</option>
                    </select>
                </div>
            </div>
            <div style="display:flex; gap:0.6rem; justify-content:flex-end; margin-top:1.5rem;">
                <button type="button" id="lp-item-modal-cancel" style="padding:0.5rem 1rem; background:transparent; color:var(--text); border:1.5px solid rgba(32,0,130,0.15); border-radius:999px; font-weight:700; font-size:0.82rem; cursor:pointer;">Cancel</button>
                <button type="button" id="lp-item-modal-save" style="padding:0.5rem 1rem; background:var(--primary); color:var(--surface); border:none; border-radius:999px; font-weight:700; font-size:0.82rem; cursor:pointer;">Add</button>
            </div>
        </div>
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
    // Populate learner dropdown from all employees
    var assignedSelect = document.getElementById('assigned-to-select');
    if (assignedSelect) {
        fetch('pages/instructor/ajax/get-learners.php', { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) { assignedSelect.innerHTML = '<option value="">No learners available</option>'; return; }
                var html = '<option value="">No specific learner</option>';
                (data.learners || []).forEach(function(emp) {
                    html += '<option value="' + emp.employee_id + '">' + (emp.full_name || 'Employee #' + emp.employee_id) + '</option>';
                });
                assignedSelect.innerHTML = html;
            })
            .catch(function() { assignedSelect.innerHTML = '<option value="">Failed to load</option>'; });
    }

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

<script>
(function() {
    var params = new URLSearchParams(window.location.search);
    var pathId = params.get('id');
    if (!pathId) return;

    var section = document.getElementById('lp-items-section');
    var list = document.getElementById('lp-items-list');
    var empty = document.getElementById('lp-items-empty');
    var modal = document.getElementById('lp-item-modal');
    var typeSelect = document.getElementById('lp-item-type');
    var itemSelect = document.getElementById('lp-item-select');

    section.style.display = '';

    function loadItems() {
        fetch('pages/instructor/ajax/get-learning-path-by-id.php?id=' + pathId, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) return;
            var items = data.items || [];
            if (items.length === 0) {
                list.innerHTML = '';
                empty.style.display = '';
                return;
            }
            empty.style.display = 'none';
            var html = '';
            items.forEach(function(item, idx) {
                var typeColors = { course: '#320082', module: '#5146b7', lesson: '#7c3aed', quiz: '#2563eb', evaluation: '#0891b2', program: '#059669', 'video-conference': '#d97706' };
                var color = typeColors[item.item_type] || '#666';
                var icon = item.item_type === 'course' ? 'fa-graduation-cap' : item.item_type === 'module' ? 'fa-cubes' : item.item_type === 'lesson' ? 'fa-file-alt' : item.item_type === 'quiz' ? 'fa-question-circle' : 'fa-layer-group';
                html += '<div style="display:flex; align-items:center; gap:0.75rem; padding:0.85rem 1rem; background:rgba(32,0,130,0.03); border:1px solid rgba(32,0,130,0.08); border-radius:10px;">';
                html += '<div style="width:28px; height:28px; border-radius:8px; background:' + color + '; color:#fff; display:flex; align-items:center; justify-content:center; font-size:0.75rem; flex-shrink:0;"><i class="fas ' + icon + '"></i></div>';
                html += '<div style="flex:1; min-width:0;">';
                html += '<div style="font-weight:700; font-size:0.88rem; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">' + (item.title || 'Untitled') + '</div>';
                html += '<div style="font-size:0.72rem; color:var(--muted); text-transform:capitalize;">' + item.item_type + ' &bull; #' + (idx + 1) + '</div>';
                html += '</div>';
                html += '<button class="lp-remove-item" data-item-id="' + item.id + '" style="padding:0.3rem 0.6rem; font-size:0.72rem; background:rgba(220,53,69,0.08); color:#dc3545; border:1px solid rgba(220,53,69,0.15); border-radius:999px; cursor:pointer; font-weight:600; white-space:nowrap;"><i class="fas fa-times" style="margin-right:0.2rem;"></i>Remove</button>';
                html += '</div>';
            });
            list.innerHTML = html;

            list.querySelectorAll('.lp-remove-item').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    if (!confirm('Remove this item from the path?')) return;
                    fetch('pages/instructor/ajax/delete-learning-path-item.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify({ item_id: btn.dataset.itemId })
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(d) {
                        if (d.success) loadItems();
                        else alert(d.message || 'Failed to remove item.');
                    })
                    .catch(function() { alert('Network error.'); });
                });
            });
        })
        .catch(function() {});
    }

    function loadOptions(type) {
        var endpoints = {
            course: 'pages/instructor/ajax/get-courses-for-path.php',
            module: 'pages/instructor/ajax/get-modules-for-path.php'
        };
        var url = endpoints[type];
        if (!url) { itemSelect.innerHTML = '<option value="">No items available</option>'; return; }
        itemSelect.innerHTML = '<option value="">Loading...</option>';
        fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var items = data.items || data || [];
            if (!items.length) { itemSelect.innerHTML = '<option value="">No items available</option>'; return; }
            var html = '<option value="">Select ' + type + '...</option>';
            items.forEach(function(item) {
                html += '<option value="' + item.id + '">' + (item.title || 'Untitled') + '</option>';
            });
            itemSelect.innerHTML = html;
        })
        .catch(function() { itemSelect.innerHTML = '<option value="">Failed to load</option>'; });
    }

    document.getElementById('lp-add-item-btn').addEventListener('click', function() {
        typeSelect.value = 'course';
        loadOptions('course');
        modal.style.display = 'flex';
    });
    document.getElementById('lp-item-modal-close').addEventListener('click', function() { modal.style.display = 'none'; });
    document.getElementById('lp-item-modal-cancel').addEventListener('click', function() { modal.style.display = 'none'; });
    modal.addEventListener('click', function(e) { if (e.target === modal) modal.style.display = 'none'; });

    typeSelect.addEventListener('change', function() { loadOptions(this.value); });

    document.getElementById('lp-item-modal-save').addEventListener('click', function() {
        var itemType = typeSelect.value;
        var refId = parseInt(itemSelect.value);
        if (!refId) { alert('Please select an item.'); return; }
        this.disabled = true;
        var self = this;
        fetch('pages/instructor/ajax/add-learning-path-item.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ learning_path_id: parseInt(pathId), item_type: itemType, reference_id: refId, order_index: 0 })
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) {
                modal.style.display = 'none';
                loadItems();
            } else {
                alert(d.message || 'Failed to add item.');
            }
        })
        .catch(function() { alert('Network error.'); })
        .finally(function() { self.disabled = false; });
    });

    loadItems();
})();
</script>
