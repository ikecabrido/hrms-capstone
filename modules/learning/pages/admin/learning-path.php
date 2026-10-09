<?php include_once __DIR__ . '/../../classes/Employee.php';
$employeeClass = new Employee();

$learningPathRows = [];

try {
    require_once dirname(__DIR__, 4) . '/database/db.php';
    $database = new Database();
    $pdo = $database->getConnection();

    $runQuery = function (PDO $pdo, string $sql, array $params = []): array {
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('admin/learning-path.php query failed: ' . $e->getMessage());
            return [];
        }
    };

    $learningPathRows = $runQuery($pdo, "SELECT lp.id, lp.title, lp.description, lp.status, lp.type, lp.is_public, lp.assigned_to, lp.instructor_id, lp.created_at,
               CONCAT(e.first_name, ' ', e.last_name) AS assigned_to_name,
               
               ic.first_name AS i_first_name, ic.last_name AS i_last_name, ic.employee_id AS i_emp_id, 
               COUNT(lpi.id) AS item_count
        FROM ld_learning_path lp
        LEFT JOIN em_employees e ON e.employee_id = lp.assigned_to
        LEFT JOIN em_employees ic ON ic.employee_id = lp.instructor_id
        LEFT JOIN ld_learning_path_item lpi ON lpi.learning_path_id = lp.id
        GROUP BY lp.id, lp.title, lp.description, lp.status, lp.type, lp.is_public, lp.assigned_to, lp.instructor_id, lp.created_at,
                 e.first_name, e.last_name, ic.first_name, ic.last_name, ic.employee_id
        ORDER BY lp.created_at DESC");
} catch (Throwable $e) {
    error_log('admin/learning-path.php bootstrap failed: ' . $e->getMessage());
}

function userInitials($first, $last) {
    $f = $first ?? '';
    $l = $last ?? '';
    return strtoupper(substr($f, 0, 1) . substr($l, 0, 1));
}

function userBadge($name, $empId) {
    if (!$name) return '<span style="font-size:0.78rem; color:rgba(32,0,130,0.4);">Unassigned</span>';
    $initials = userInitials(explode(' ', $name)[0] ?? '', explode(' ', $name)[1] ?? '');
    return '<div style="display:flex; align-items:center; gap:0.5rem;">
        <div style="width:26px; height:26px; min-width:26px; border-radius:50%; background:linear-gradient(135deg, rgba(32,0,130,0.8), rgba(91,85,255,0.6)); color:#fff; display:flex; align-items:center; justify-content:center; font-size:0.65rem; font-weight:700;">' . $initials . '</div>
        <div><span style="font-size:0.82rem; font-weight:600; color:var(--text);">' . htmlspecialchars($name) . '</span>
        <span style="font-size:0.7rem; color:rgba(32,0,130,0.4);">#'.$empId.'</span></div>
    </div>';
}
?>

<div class="module-content">
    <div class="toolbar" style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
        <div style="flex:1;"></div>
        <a href="?page=instructor/learning-path" style="display:inline-flex; align-items:center; gap:0.4rem; padding:0.45rem 0.9rem; background:var(--primary); color:#fff; border-radius:999px; text-decoration:none; font-size:0.8rem; font-weight:700;">
            <i class="fas fa-plus"></i> New Learning Path
        </a>
    </div>

    <div class="mode-card">
        <h2>Learning Path Management</h2>
        <p>View and manage all learning paths. Create and edit paths using the instructor learning path editor.</p>
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:0.9rem;">
                <thead>
                    <tr style="border-bottom:2px solid rgba(32,0,130,0.12); text-align:left;">
                        <th style="padding:0.8rem 1rem; font-size:0.7rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700; width:36px;">ID</th>
                        <th style="padding:0.8rem 1rem; font-size:0.7rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">Title</th>
                        <th style="padding:0.8rem 1rem; font-size:0.7rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700; width:140px;">Assigned To</th>
                        <th style="padding:0.8rem 1rem; font-size:0.7rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700; width:140px;">Instructor</th>
                        <th style="padding:0.8rem 1rem; font-size:0.7rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700; text-align:center; width:70px;">Items</th>
                        <th style="padding:0.8rem 1rem; font-size:0.7rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700; text-align:center; width:80px;">Status</th>
                        <th style="padding:0.8rem 1rem; font-size:0.7rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700; text-align:center; width:80px;">Visibility</th>
                        <th style="padding:0.8rem 1rem; font-size:0.7rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700; text-align:center; width:40px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($learningPathRows)): ?>
                        <tr>
                            <td colspan="8" style="padding:3rem; text-align:center; color:var(--muted);">
                                <i class="fas fa-route" style="font-size:1.5rem; display:block; margin-bottom:0.5rem; opacity:0.4;"></i>
                                No learning paths yet. Click "New Learning Path" to create one.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($learningPathRows as $lp): 
                            $initials = userInitials($lp['i_first_name'] ?? '', $lp['i_last_name'] ?? '');
                            $instructorName = ($lp['i_first_name'] ?? '') . ' ' . ($lp['i_last_name'] ?? '');
                            $statusColor = $lp['status'] === 'active' ? '#10b981' : ($lp['status'] === 'archived' ? '#9ca3af' : '#f59e0b');
                            $statusLabel = ucfirst($lp['status'] ?? 'active');
                        ?>
                            <tr style="border-bottom:1px solid rgba(32,0,130,0.06); transition:background 0.15s;" onmouseover="this.style.background='rgba(32,0,130,0.03)'" onmouseout="this.style.background='transparent'">
                                <td style="padding:0.8rem 1rem; font-size:0.82rem; color:rgba(32,0,130,0.4); font-weight:600;">#<?= $lp['id'] ?></td>
                                <td style="padding:0.8rem 1rem;">
                                    <div style="display:flex; align-items:center; gap:0.75rem;">
                                        <div style="width:32px; height:32px; min-width:32px; border-radius:8px; background:linear-gradient(135deg, rgba(99,102,241,0.15), rgba(139,92,246,0.1)); display:flex; align-items:center; justify-content:center; color:#6366f1;">
                                            <i class="fas fa-route"></i>
                                        </div>
                                        <div>
                                            <div style="font-weight:700; color:var(--text);"><?= htmlspecialchars($lp['title']) ?></div>
                                            <?php if (!empty($lp['description'])): ?>
                                                <div style="font-size:0.76rem; color:rgba(32,0,130,0.45); margin-top:0.2rem; max-width:380px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars(mb_substr($lp['description'], 0, 80)) ?>...</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding:0.8rem 1rem;">
                                    <?= userBadge($lp['assigned_to_name'] ?? '', (int)($lp['assigned_to'] ?? 0)) ?>
                                </td>
                                <td style="padding:0.8rem 1rem;">
                                    <?= userBadge($instructorName, (int)($lp['instructor_id'] ?? 0)) ?>
                                </td>
                                <td style="padding:0.8rem 1rem; text-align:center;">
                                    <span style="display:inline-flex; align-items:center; gap:0.3rem; padding:0.25rem 0.6rem; border-radius:999px; font-size:0.75rem; font-weight:700; background:rgba(32,0,130,0.08); color:var(--primary);">
                                        <i class="fas fa-list" style="font-size:0.65rem;"></i> <?= $lp['item_count'] ?>
                                    </span>
                                </td>
                                <td style="padding:0.8rem 1rem; text-align:center;">
                                    <span style="display:inline-flex; align-items:center; gap:0.3rem; padding:0.25rem 0.6rem; border-radius:999px; font-size:0.72rem; font-weight:700; color:<?= $statusColor ?>; background:<?= $statusColor ?>22;">
                                        <span style="width:6px; height:6px; border-radius:50%; background:<?= $statusColor ?>;"></span> <?= $statusLabel ?>
                                    </span>
                                </td>
                                <td style="padding:0.8rem 1rem; text-align:center;">
                                    <?php if (!empty($lp['is_public'])): ?>
                                        <span style="font-size:0.72rem; color:#059669; font-weight:600;">Public</span>
                                    <?php else: ?>
                                        <span style="font-size:0.72rem; color:rgba(32,0,130,0.4);">Private</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding:0.8rem 1rem; text-align:center;">
                                    <div style="display:flex; gap:0.4rem; justify-content:center;">
                                        <a href="?page=instructor/learning-path&id=<?= $lp['id'] ?>" style="padding:0.35rem 0.7rem; font-size:0.72rem; background:rgba(32,0,130,0.08); color:var(--primary); border:1px solid rgba(32,0,130,0.15); border-radius:999px; text-decoration:none; font-weight:700; white-space:nowrap;">
                                            <i class="fas fa-pen"></i> Edit
                                        </a>
                                        <button onclick="confirmArchive(<?= $lp['id'] ?>, '<?= addslashes($lp['title']) ?>')" style="padding:0.35rem 0.7rem; font-size:0.72rem; background:rgba(220,53,69,0.08); color:#dc3545; border:1px solid rgba(220,53,69,0.15); border-radius:999px; cursor:pointer; font-weight:700; white-space:nowrap;">
                                            <i class="fas fa-archive"></i> Archive
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function confirmArchive(pathId, title) {
    if (!confirm('Archive "' + title + '"? This will set its status to archived and it will no longer appear as available.')) return;

    // Archive using the instructor's edit endpoint (sets status=archived)
    fetch('pages/instructor/ajax/edit-learning-path.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ id: pathId, status: 'archived' })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            // Reload the page to reflect the change
            window.location.reload();
        } else {
            alert('Failed to archive: ' + (data.message || data.error || 'Unknown error'));
        }
    })
    .catch(function() { alert('Network error. Please try again.'); });
}
</script>
