<?php
include_once __DIR__ . '/../../../classes/Employee.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

$employeeId = (int)($_GET['id'] ?? 0);
$employee = null;
$documents = [];

try {
    $pdo = (new Database())->getConnection();

    $stmt = $pdo->prepare("
        SELECT e.employee_id, e.employee_code, e.first_name, e.middle_name, e.last_name,
               d.department_name, p.position_name
        FROM em_employees e
        LEFT JOIN em_departments d ON d.department_id = e.department_id
        LEFT JOIN em_positions p ON p.position_id = e.position_id
        WHERE e.employee_id = :eid
        LIMIT 1
    ");
    $stmt->execute([':eid' => $employeeId]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($employee) {
        $docStmt = $pdo->prepare("
            SELECT ed.*, CONCAT(up.first_name, ' ', IFNULL(CONCAT(up.middle_name, ' '), ''), up.last_name) AS uploaded_by_name
            FROM employee_documents ed
            LEFT JOIN em_employees up ON up.employee_id = ed.uploaded_by
            WHERE ed.employee_id = :eid
            ORDER BY ed.created_at DESC
        ");
        $docStmt->execute([':eid' => $employeeId]);
        $documents = $docStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    DbError::capture($e, 'admin/user-subpage/documents');
    $documents = [];
}

function formatFileSize($bytes) {
    if (!$bytes) return '—';
    $bytes = (int)$bytes;
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
    return round($bytes / 1048576, 1) . ' MB';
}
?>
<div class="module-header">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem;">
        <div>
            <h1 class="module-header-title">Documents</h1>
            <p class="module-header-subtitle">
                <?php if ($employee): ?>
                    <?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?>
                    <span style="color:#999; font-weight:400;"> — Emp #<?= $employee['employee_id'] ?></span>
                <?php else: ?>
                    Employee Documents
                <?php endif; ?>
            </p>
        </div>
        <a href="?page=admin/user" style="display:inline-flex; align-items:center; gap:0.4rem; padding:0.5rem 1rem; border:1px solid rgba(32,0,130,0.2); border-radius:999px; font-size:0.85rem; font-weight:700; color:var(--primary); text-decoration:none; white-space:nowrap;">
            <i class="fas fa-arrow-left" style="font-size:0.8rem;"></i> Back to Users
        </a>
    </div>
</div>

<div class="module-content">
    <?php if (!$employee): ?>
        <div class="mode-card">
            <div class="content-card-body">
                <h3>Employee not found</h3>
                <p>The requested employee could not be found. <a href="?page=admin/user">Go back to User Management</a></p>
            </div>
        </div>
    <?php else: ?>
        <div style="display:grid; grid-template-columns:300px 1fr; gap:1.5rem; margin-bottom:2rem;">
            <div class="mode-card" style="text-align:center; padding:2rem;">
                <div style="width:80px; height:80px; border-radius:50%; background:linear-gradient(135deg, rgba(32,0,130,0.9), rgba(91,85,255,0.75)); color:var(--surface); display:flex; align-items:center; justify-content:center; font-size:1.8rem; font-weight:800; margin:0 auto 1rem;">
                    <?= strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1)) ?>
                </div>
                <h2 style="margin:0 0 0.25rem; color:var(--text);"><?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?></h2>
                <p style="margin:0 0 0.5rem; color:rgba(32,0,130,0.5); font-size:0.85rem;"><?= htmlspecialchars($employee['employee_code'] ?? '') ?></p>
                <p style="margin:0 0 1rem; color:rgba(32,0,130,0.6); font-size:0.85rem;"><?= htmlspecialchars($employee['email'] ?? '') ?></p>
                <?php if (!empty($documents)): ?>
                    <div style="padding:0.5rem 1rem; border-radius:10px; background:rgba(32,0,130,0.06); display:inline-block;">
                        <div style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">Documents</div>
                        <div style="font-size:1.3rem; font-weight:800; color:var(--text);"><?= count($documents) ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="mode-card" style="margin-bottom:1.5rem;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem;">
                <h3 style="margin:0; color:var(--text);">
                    <i class="fas fa-folder" style="margin-right:0.5rem; color:var(--primary);"></i>
                    Employee Documents
                </h3>
                <button id="doc-upload-btn" style="display:inline-flex; align-items:center; gap:0.4rem; padding:0.5rem 1rem; background:var(--primary); color:var(--surface); border:none; border-radius:999px; font-size:0.85rem; font-weight:700; cursor:pointer;">
                    <i class="fas fa-upload" style="font-size:0.8rem;"></i> Upload Document
                </button>
            </div>

            <?php if (empty($documents)): ?>
                <p style="color:rgba(32,0,130,0.5); padding:1rem;">No documents uploaded yet.</p>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse; font-size:0.92rem;">
                        <thead>
                            <tr style="border-bottom:2px solid rgba(32,0,130,0.12); text-align:left;">
                                <th style="padding:0.8rem 1rem; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">Document</th>
                                <th style="padding:0.8rem 1rem; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">Type</th>
                                <th style="padding:0.8rem 1rem; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">Category</th>
                                <th style="padding:0.8rem 1rem; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">File</th>
                                <th style="padding:0.8rem 1rem; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">Size</th>
                                <th style="padding:0.8rem 1rem; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700;">Expiry</th>
                                <th style="padding:0.8rem 1rem; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--primary); font-weight:700; text-align:center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documents as $doc): ?>
                                <?php
                                    $docIcon = 'fa-file';
                                    $docColor = 'rgba(32,0,130,0.5)';
                                    $dt = strtolower($doc['document_type'] ?? '');
                                    if ($dt === 'resume/cv' || $dt === 'resume' || $dt === 'cv') { $docIcon = 'fa-file-lines'; $docColor = '#6366f1'; }
                                    elseif ($dt === 'contract') { $docIcon = 'fa-file-signature'; $docColor = '#f59e0b'; }
                                    elseif ($dt === 'certificate' || $dt === 'certification') { $docIcon = 'fa-certificate'; $docColor = '#10b981'; }
                                    elseif ($dt === 'identification' || $dt === 'id') { $docIcon = 'fa-id-card'; $docColor = '#3b82f6'; }
                                    elseif ($dt === 'medical') { $docIcon = 'fa-heart-pulse'; $docColor = '#ef4444'; }
                                    elseif ($dt === 'legal') { $docIcon = 'fa-scale-balanced'; $docColor = '#8b5cf6'; }
                                    $expiry = $doc['expiry_date'] ?? null;
                                    $expiryHtml = '';
                                    if ($expiry) {
                                        $daysLeft = ceil((strtotime($expiry) - strtotime('today')) / 86400);
                                        if ($daysLeft < 0) {
                                            $expiryHtml = '<span style="color:#ef4444; font-weight:700; font-size:0.8rem;">Expired ' . abs($daysLeft) . 'd ago</span>';
                                        } elseif ($daysLeft <= 30) {
                                            $expiryHtml = '<span style="color:#f59e0b; font-weight:700; font-size:0.8rem;">Expires in ' . $daysLeft . 'd</span>';
                                        } else {
                                            $expiryHtml = '<span style="color:rgba(32,0,130,0.5); font-size:0.8rem;">' . date('M j, Y', strtotime($expiry)) . '</span>';
                                        }
                                    }
                                ?>
                                <tr style="border-bottom:1px solid rgba(32,0,130,0.06);" onmouseover="this.style.background='rgba(32,0,130,0.03)'" onmouseout="this.style.background='transparent'">
                                    <td style="padding:0.85rem 1rem;">
                                        <div style="display:flex; align-items:center; gap:0.75rem;">
                                            <div style="width:36px; height:36px; min-width:36px; border-radius:8px; background:rgba(32,0,130,0.06); display:flex; align-items:center; justify-content:center;">
                                                <i class="fas <?= $docIcon ?>" style="color:<?= $docColor ?>; font-size:0.95rem;"></i>
                                            </div>
                                            <div>
                                                <div style="font-weight:700; color:var(--text); font-size:0.9rem;"><?= htmlspecialchars($doc['document_name']) ?></div>
                                                <div style="font-size:0.75rem; color:rgba(32,0,130,0.4);"><?= htmlspecialchars($doc['file_name']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding:0.85rem 1rem; font-size:0.85rem;">
                                        <span style="display:inline-block; padding:0.15rem 0.5rem; border-radius:4px; background:rgba(32,0,130,0.04); font-size:0.78rem; font-weight:600; color:<?= $docColor ?>;">
                                            <?= htmlspecialchars($doc['document_type'] ?? 'Other') ?>
                                        </span>
                                    </td>
                                    <td style="padding:0.85rem 1rem; font-size:0.85rem; color:rgba(32,0,130,0.5);"><?= htmlspecialchars($doc['category'] ?? 'Other') ?></td>
                                    <td style="padding:0.85rem 1rem;">
                                        <div style="display:flex; align-items:center; gap:0.4rem;">
                                            <i class="fas fa-file" style="color:rgba(32,0,130,0.3); font-size:0.8rem;"></i>
                                            <span style="font-size:0.85rem; color:rgba(32,0,130,0.6);"><?= formatFileSize($doc['file_size']) ?></span>
                                        </div>
                                    </td>
                                    <td style="padding:0.85rem 1rem; font-size:0.85rem;"><?= $expiryHtml ?: '<span style="color:rgba(32,0,130,0.3); font-size:0.85rem;">No expiry</span>' ?></td>
                                    <td style="padding:0.85rem 1rem; text-align:center;">
                                        <a href="pages/admin/ajax/download-document.php?doc_id=<?= $doc['document_id'] ?>" download="<?= htmlspecialchars($doc['file_name']) ?>"
                                           style="display:inline-flex; align-items:center; gap:0.3rem; padding:0.4rem 0.7rem; border-radius:999px; font-size:0.78rem; font-weight:700; border:1px solid rgba(32,0,130,0.15); color:var(--primary); text-decoration:none; background:transparent; white-space:nowrap;">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Upload Modal -->
<div id="doc-upload-modal" class="modal-overlay" style="display:none; z-index:10000;">
    <div style="background:var(--surface, #fff); border-radius:16px; width:90%; max-width:500px; box-shadow:0 20px 60px rgba(0,0,0,0.2);">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:1.25rem 1.5rem; border-bottom:1px solid rgba(32,0,130,0.08);">
            <h3 id="upload-modal-title" style="margin:0; font-size:1.1rem; font-weight:800; color:var(--text, #222);">Upload Document</h3>
            <button onclick="document.getElementById('doc-upload-modal').style.display='none'" style="background:none; border:none; font-size:1.2rem; cursor:pointer; color:rgba(32,0,130,0.4); padding:0.25rem;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="doc-upload-form" data-skip style="padding:1.5rem;" enctype="multipart/form-data">
            <input type="hidden" name="employee_id" value="<?= $employeeId ?>" />
            <div style="margin-bottom:1rem;">
                <label style="display:block; font-size:0.78rem; font-weight:700; color:var(--primary, #320082); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:0.4rem;">Document Name *</label>
                <input type="text" name="document_name" required placeholder="e.g. Resume, Training Certificate, Contract" style="width:100%; padding:0.6rem 0.8rem; border:1.5px solid rgba(32,0,130,0.12); border-radius:8px; font-size:0.9rem; box-sizing:border-box;" />
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:1rem;">
                <div>
                    <label style="display:block; font-size:0.78rem; font-weight:700; color:var(--primary, #320082); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:0.4rem;">Document Type</label>
                    <select name="document_type" style="width:100%; padding:0.6rem 0.8rem; border:1.5px solid rgba(32,0,130,0.12); border-radius:8px; font-size:0.9rem; box-sizing:border-box; background:#fff;">
                        <option value="Other">Other</option>
                        <option value="Resume/CV">Resume/CV</option>
                        <option value="Contract">Contract</option>
                        <option value="Certificate">Certificate</option>
                        <option value="Identification">Identification</option>
                        <option value="Medical">Medical</option>
                        <option value="Legal">Legal</option>
                    </select>
                </div>
                <div>
                    <label style="display:block; font-size:0.78rem; font-weight:700; color:var(--primary, #320082); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:0.4rem;">Category</label>
                    <input type="text" name="category" placeholder="e.g. Credentials, Resume" value="Other" style="width:100%; padding:0.6rem 0.8rem; border:1.5px solid rgba(32,0,130,0.12); border-radius:8px; font-size:0.9rem; box-sizing:border-box;" />
                </div>
            </div>
            <div style="margin-bottom:1rem;">
                <label style="display:block; font-size:0.78rem; font-weight:700; color:var(--primary, #320082); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:0.4rem;">File *</label>
                <input type="file" name="file" required accept=".pdf,.doc,.docx,.png,.jpg,.jpeg,.gif,.xls,.xlsx,.ppt,.pptx,.txt" style="width:100%; padding:0.5rem; border:1.5px dashed rgba(32,0,130,0.2); border-radius:8px; font-size:0.9rem; cursor:pointer;" />
            </div>
            <div style="margin-bottom:1.25rem;">
                <label style="display:block; font-size:0.78rem; font-weight:700; color:var(--primary, #320082); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:0.4rem;">Expiry Date (optional)</label>
                <input type="date" name="expiry_date" style="width:100%; padding:0.6rem 0.8rem; border:1.5px solid rgba(32,0,130,0.12); border-radius:8px; font-size:0.9rem; box-sizing:border-box;" />
            </div>
            <div style="display:flex; gap:0.75rem; justify-content:flex-end;">
                <button type="button" onclick="document.getElementById('doc-upload-modal').style.display='none'" style="padding:0.55rem 1.2rem; border:1.5px solid rgba(32,0,130,0.15); border-radius:8px; background:transparent; font-weight:700; font-size:0.85rem; cursor:pointer;">Cancel</button>
                <button type="submit" id="doc-upload-submit" style="padding:0.55rem 1.2rem; border:none; border-radius:8px; background:var(--primary, #320082); color:var(--surface, #fff); font-weight:700; font-size:0.85rem; cursor:pointer;">
                    <i class="fas fa-upload"></i> Upload
                </button>
            </div>
        </form>
    </div>
</div>

<div id="doc-upload-response" style="margin-top:1rem;"></div>

<script>
(function() {
    var modal = document.getElementById('doc-upload-modal');
    var uploadBtn = document.getElementById('doc-upload-btn');
    var form = document.getElementById('doc-upload-form');
    var submitBtn = document.getElementById('doc-upload-submit');
    var responseEl = document.getElementById('doc-upload-response');

    if (uploadBtn) {
        uploadBtn.addEventListener('click', function() {
            modal.style.display = 'flex';
        });
    }

    modal.addEventListener('click', function(e) {
        if (e.target.id === 'doc-upload-modal') {
            modal.style.display = 'none';
        }
    });

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
            if (responseEl) responseEl.innerHTML = '';

            var fd = new FormData(form);
            fd.append('employee_id', '<?= $employeeId ?>');

            fetch('pages/admin/ajax/upload-document.php', {
                method: 'POST',
                body: fd,
                credentials: 'same-origin'
            })
            .then(function(r) { return r.json(); })
            .then(function(json) {
                if (json.success) {
                    if (responseEl) {
                        responseEl.innerHTML = '<div style="padding:0.75rem 1rem; background:rgba(16,185,129,0.1); border-left:4px solid #10b981; border-radius:6px; color:#10b981; font-weight:600; font-size:0.9rem;">' +
                            '<i class="fas fa-check-circle"></i> ' + json.message + '</div>';
                    }
                    modal.style.display = 'none';
                    form.reset();
                    setTimeout(function() { location.reload(); }, 1200);
                } else {
                    if (responseEl) {
                        responseEl.innerHTML = '<div style="padding:0.75rem 1rem; background:rgba(239,68,68,0.1); border-left:4px solid #ef4444; border-radius:6px; color:#ef4444; font-weight:600; font-size:0.9rem;">' +
                            '<i class="fas fa-exclamation-circle"></i> ' + (json.error || 'Upload failed') + '</div>';
                    }
                }
            })
            .catch(function(err) {
                if (responseEl) {
                    responseEl.innerHTML = '<div style="padding:0.75rem 1rem; background:rgba(239,68,68,0.1); border-left:4px solid #ef4444; border-radius:6px; color:#ef4444; font-weight:600; font-size:0.9rem;">' +
                        '<i class="fas fa-exclamation-circle"></i> Network error: ' + err.message + '</div>';
                }
            })
            .finally(function() {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-upload"></i> Upload';
                }
            });
        });
    }
})();
</script>
